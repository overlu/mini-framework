<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;


use Exception;

/**
 * Class Config
 * @method static void call(string $command, array $args = [], array $opts = [])
 * @method static void add(string $command, callable $handler, $config = null)
 * @method static void addCommand(string $command, callable $handler, $config = null)
 * @method static void commands(array $commands)
 * @method static void displayHelp(string $err = '')
 * @method static void displayCommandHelp(string $name)
 * @method static mixed|null getArg($name, $default = null)
 * @method static int getIntArg($name, int $default = 0)
 * @method static string getStrArg($name, string $default = '')
 * @method static mixed|null getOpt(string $name, $default = null)
 * @method static int getIntOpt(string $name, int $default = 0)
 * @method static string getStrOpt(string $name, string $default = '')
 * @method static bool getBoolOpt(string $name, bool $default = false)
 * @method static array getArgs()
 * @method static void setArgs(array $args)
 * @method static array getOpts()
 * @method static void setOpts(array $opts)
 * @method static string getScript()
 * @method static string getScriptName()
 * @method static void setScript(string $script)
 * @method static string getCommand()
 * @method static void setCommand(string $command)
 * @method static array getCommands()
 * @method static void setCommands(array $commands)
 * @method static array getMessages()
 * @method static int getKeyWidth()
 * @method static void setKeyWidth(int $keyWidth)
 * @method static string getPwd()
 * @method static array getMetas()
 * @method static void setMetas(array $metas)
 * @package Mini\Facades
 */
class Console extends Facade
{
    /**
     * @return string
     * @throws Exception
     */
    protected static function getFacadeAccessor(): string
    {
        if (RUN_ENV !== 'artisan') {
            throw new Exception('Only can use in artisan environemnt');
        }
        return 'console';
    }
}