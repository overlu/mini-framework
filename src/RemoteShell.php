<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Support\Str;
use RuntimeException;
use Swoole\Coroutine;
use Swoole\Timer;
use Exception;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Throwable;
use function Mini\Debugger\get_debug_print_backtrace;

class RemoteShell
{
    public const STX = "DEBUG";

    public static array $contexts = [];

    public static $oriPipeMessageCallback = null;

    /**
     * @var \swoole\server
     */
    public static \swoole\server $server;

    public static array $menu = [
        "                   Remote Help Infomation Panel                   ",
        "|****************************************************************|",
        "*  \e[32mprint\e[0m [variant]\t\t打印一个PHP变量的值",
        "*  \e[32mexec\e[0m [code]\t\t\t执行一段PHP代码",
        "*  \e[32mworker\e[0m [id]\t\t\t切换Worker进程",
        "*  \e[32mlist\e[0m\t\t\t\t打印服务器所有连接的fd",
        "*  \e[32mstats\e[0m\t\t\t打印服务器状态",
        "*  \e[32mcoros\e[0m\t\t\t打印协程列表",
        "*  \e[32mcostats\e[0m\t\t\t打印协程状态",
        "*  \e[32melapsed\e[0m [cid]\t\t打印某个协程运行的时间",
        "*  \e[32mtimer_list\e[0m\t\t\t打印当前进程中所有定时器ID",
        "*  \e[32mtimer_info\e[0m [timer_id]\t打印某个定时器信息",
        "*  \e[32mtimer_stats\e[0m\t\t\t打印当前进程中的定时器状态",
        "*  \e[32mbacktrace\e[0m\t\t\t打印协程调用栈",
        "*  \e[32minfo\e[0m [fd]\t\t\t显示某个连接的信息",
        "*  \e[32mhelp\e[0m\t\t\t\t显示帮助界面",
        "*  \e[32mquit\e[0m\t\t\t\t退出终端",
        "|****************************************************************|",
    ];

    public const PAGESIZE = 20;

    /**
     * @param \Swoole\Server $server
     * @param string $host
     * @param int $port
     * @throws Exception
     */
    public static function listen(\Swoole\Server $server, string $host = '127.0.0.1', int $port = 9599): void
    {
        $serv = $server->listen($host, $port, SWOOLE_SOCK_TCP);
        if (!$serv) {
            throw new RuntimeException("listen fail.");
        }
        $serv->set([
            "open_eof_split" => true,
            'package_eof' => "\r\n",
        ]);
        $serv->on('connect', [__CLASS__, 'onConnect']);
        $serv->on('close', [__CLASS__, 'onClose']);
        $serv->on('receive', [__CLASS__, 'onReceive']);

        if (method_exists($server, 'getCallback')) {
            self::$oriPipeMessageCallback = $server->getCallback('PipeMessage');
        }

        $server->on("pipeMessage", [__CLASS__, 'onPipeMessage']);
        self::$server = $server;
    }

    public static function onConnect(\Swoole\Server $server, $fd, $reactor_id): void
    {
        self::$contexts[$fd]['worker_id'] = $server->worker_id;
        self::output($fd, implode("\r\n", self::$menu), false);
    }

    public static function output($fd, $msg, $withDump = true): void
    {
        $msg = $withDump ? self::dump($fd, $msg) : $msg . "\r\n";
        if (!isset(self::$contexts[$fd]['worker_id'])) {
            $msg .= "\r\n\e[32mworker#" . self::$server->worker_id . "$\e[0m ";
        } else {
            $msg .= "\r\n\e[32mworker#" . self::$contexts[$fd]['worker_id'] . "$\e[0m ";
        }
        self::$server->send($fd, "\r\n" . $msg);
    }

    public static function onClose(\Swoole\Server $server, $fd, $reactor_id): void
    {
        unset(self::$contexts[$fd]);
    }

    public static function onPipeMessage(\Swoole\Server $server, $src_worker_id, $message)
    {
        //不是 debug 消息
        if (!is_string($message) || strpos($message, self::STX) !== 0) {
            if (self::$oriPipeMessageCallback === null) {
                return trigger_error("require swoole-4.3.0 or later.", E_USER_WARNING);
            }
            return call_user_func(self::$oriPipeMessageCallback, $server, $src_worker_id, $message);
        }

        $request = unserialize(substr($message, strlen(self::STX)));
        self::call($request['fd'], $request['func'], $request['args']);
        return true;
    }

    public static function call($fd, $func, $args): void
    {
        $result = call_user_func_array($func, $args);
        self::output($fd, $result);
    }

    public static function exec($fd, $func, $args): void
    {
        try {
            //不在当前Worker进程
            if (self::$contexts[$fd]['worker_id'] !== self::$server->worker_id) {
                self::$server->sendMessage(
                    self::STX . serialize(['fd' => $fd, 'func' => $func, 'args' => $args]),
                    self::$contexts[$fd]['worker_id']
                );
            } else {
                self::call($fd, $func, $args);
            }
        } catch (Throwable $e) {
            self::output($fd, $e->getMessage());
        }
    }

    public static function getCoros()
    {
        return iterator_to_array(Coroutine::listCoroutines());
    }

    public static function getCoStats()
    {
        return Coroutine::stats();
    }

    public static function getCoElapsed($cid)
    {
        if (!defined('SWOOLE_VERSION_ID') || SWOOLE_VERSION_ID < 40500) {
            return "require swoole-4.5.0 or later.";
        }
        return Coroutine::getElapsed($cid);
    }

    public static function getTimerList()
    {
        if (!defined('SWOOLE_VERSION_ID') || SWOOLE_VERSION_ID < 40400) {
            return "require swoole-4.4.0 or later.";
        }
        return iterator_to_array(Timer::list());
    }

    public static function getTimerInfo($timer_id)
    {
        if (!defined('SWOOLE_VERSION_ID') || SWOOLE_VERSION_ID < 40400) {
            return "require swoole-4.4.0 or later.";
        }
        return Timer::info($timer_id);
    }

    public static function getTimerStats()
    {
        if (!defined('SWOOLE_VERSION_ID') || SWOOLE_VERSION_ID < 40400) {
            return "require swoole-4.4.0 or later.";
        }
        return Timer::stats();
    }

    public static function getBackTrace($_cid)
    {
        $info = Coroutine::getBackTrace($_cid);
        if (!$info) {
            return "coroutine $_cid not found.";
        }

        return get_debug_print_backtrace($info);
    }

    /**
     * 打印一个PHP变量的值
     * @param $var
     * @return mixed
     */
    public static function printVariant($var)
    {
        return $var;
    }

    /**
     * 执行一段PHP代码
     * @param $code
     * @return mixed|string
     */
    public static function evalCode($code)
    {
        try {
            $code = Str::startsWith(trim($code), 'return') ? $code : 'return ' . $code;
            return eval($code . ' ;');
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * @param \Swoole\Server $server
     * @param $fd
     * @param $reactor_id
     * @param $data
     */
    public static function onReceive(\Swoole\Server $server, $fd, $reactor_id, $data): void
    {
        $args = explode(" ", $data, 2);
        $cmd = trim($args[0]);
        unset($args[0]);
        switch ($cmd) {
            case 'w':
            case 'worker':
                if (!isset($args[1])) {
                    self::output($fd, "invalid command.", false);
                    break;
                }
                self::$contexts[$fd]['worker_id'] = (int)$args[1];
                self::output($fd, "[switching to worker " . self::$contexts[$fd]['worker_id'] . "]");
                break;
            case 'e':
            case 'exec':
                if (!isset($args[1])) {
                    self::output($fd, "invalid command.", false);
                    break;
                }
                $var = trim($args[1]);
                self::exec($fd, 'self::evalCode', [$var]);
                break;
            case 'p':
            case 'print':
                if (empty($args[1])) {
                    break;
                }
                $var = trim($args[1]);
                self::exec($fd, 'self::printVariant', [$var]);
                break;
            case 'h':
            case 'help':
                self::output($fd, implode("\r\n", self::$menu), false);
                break;
            case 's':
            case 'stats':
                $stats = $server->stats();
                self::output($fd, $stats);
                break;
            case 'c':
            case 'coros':
                self::exec($fd, 'self::getCoros', []);
                break;
            /**
             * 获取协程状态
             * @link https://wiki.swoole.com/#/coroutine/coroutine?id=stats
             */
            case 'cs':
            case 'costats':
                self::exec($fd, 'self::getCoStats', []);
                break;
            /**
             * 获取协程运行的时间
             * @link https://wiki.swoole.com/#/coroutine/coroutine?id=getelapsed
             */
            case 'el':
            case 'elapsed':
                $cid = 0;
                if (isset($args[1])) {
                    $cid = intval($args[1]);
                }
                self::exec($fd, 'self::getCoElapsed', [$cid]);
                break;
            /**
             * 查看协程堆栈
             * @link https://wiki.swoole.com/#/coroutine/coroutine?id=getbacktrace
             */
            case 'bt':
            case 'b':
            case 'backtrace':
                if (empty($args[1])) {
                    self::output($fd, "invalid command.", false);
                    break;
                }
                $_cid = (int)$args[1];
                self::exec($fd, 'self::getBackTrace', [$_cid]);
                break;
            /**
             * 返回定时器列表
             * @link https://wiki.swoole.com/#/timer?id=list
             */
            case 'tl':
            case 'timer_list':
                self::exec($fd, 'self::getTimerList', []);
                break;
            /**
             * 返回 timer 的信息
             * @link https://wiki.swoole.com/#/timer?id=info
             */
            case 'ti':
            case 'timer_info':
                $timer_id = 0;
                if (isset($args[1])) {
                    $timer_id = (int)$args[1];
                }
                self::exec($fd, 'self::getTimerInfo', [$timer_id]);
                break;
            /**
             * 查看定时器状态
             * @link https://wiki.swoole.com/#/timer?id=stats
             */
            case 'ts':
            case 'timer_stats':
                self::exec($fd, 'self::getTimerStats', []);
                break;
            case 'i':
            case 'info':
                if (empty($args[1])) {
                    self::output($fd, "invalid command.", false);
                    break;
                }
                $_fd = (int)$args[1];
                $info = $server->getClientInfo($_fd);
                if (!$info) {
                    self::output($fd, "connection $_fd not found.", false);
                } else {
                    self::output($fd, $info);
                }
                break;
            case 'l':
            case 'list':
                $tmp = array();
                foreach ($server->connections as $fd) {
                    $tmp[] = $fd;
                    if (count($tmp) > self::PAGESIZE) {
                        self::output($fd, $tmp);
                        $tmp = array();
                    }
                }
                if (count($tmp) > 0) {
                    self::output($fd, $tmp);
                }
                break;
            case 'q':
            case 'quit':
                $server->close($fd);
                break;
            default:
                self::output($fd, "unknow command[$cmd]", false);
                break;
        }
    }

    public static function dump($fd, $msg)
    {
        $cloner = new VarCloner();
        $dumper = new CliDumper();
        $dumper->setColors(true);
        return $dumper->dump($cloner->cloneVar($msg), true);
    }
}
