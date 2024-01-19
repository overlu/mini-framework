<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

use InvalidArgumentException;
use Mini\Application;
use Mini\Support\Command;
use RuntimeException;
use Throwable;

class App
{
    private const COMMAND_CONFIG = [
        'desc' => '',
        'usage' => '',
        'help' => '',
    ];

    /** @var string Current dir */
    private string $pwd = '';

    /**
     * @var array
     */
    private array $metas = [
        'name' => 'Mini application',
        'desc' => 'Mini command application',
    ];

    /**
     * @var array Parsed from `arg0 name=val var2=val2`
     */
    private array $args = [];

    /**
     * @var array Parsed from `--name=val --var2=val2 -d`
     */
    private array $opts = [];

    /**
     * @var string
     */
    private string $script = '';

    /**
     * @var string
     */
    private string $command = '';

    /**
     * @var array User add commands
     */
    private array $commands = [];

    /**
     * @var array Command messages for the commands
     */
    private array $messages = [];

    /**
     * @var int
     */
    private int $keyWidth = 12;

    /**
     * Class constructor.
     *
     * @param array $config
     * @param array|null $argv
     */
    public function __construct(array $config = [], array $argv = null)
    {
        // get current dir
        $this->pwd = (string)getcwd();

        // parse cli argv
        $argv = $argv ?? $_SERVER['argv'];
        if ($config) {
            $this->setMetas($config);
        }

        // get script file
        $this->script = array_shift($argv);

        // parse flags
        [
            $this->args,
            $this->opts
        ] = Flags::parseArgv(array_values($argv), ['mergeOpts' => true]);
    }

    /**
     * @param bool $exit
     *
     * @throws InvalidArgumentException
     */
    public function run(bool $exit = true): void
    {
        $this->findCommand();

        $this->dispatch($exit);
    }

    /**
     * find command name. it is first argument.
     */
    protected function findCommand(): void
    {
        if (!isset($this->args[0])) {
            return;
        }

        $newArgs = [];

        foreach ($this->args as $key => $value) {
            if ($key === 0) {
                $this->command = trim($value);
            } elseif (is_int($key)) {
                $newArgs[] = $value;
            } else {
                $newArgs[$key] = $value;
            }
        }

        $this->args = $newArgs;
    }

    public function call(string $command, array $args = [], array $opts = []): void
    {
        $this->command = $command;
        $this->args = $args;
        $this->opts = $opts;
        $this->dispatch(false);
    }

    /**
     * @param bool $exit
     *
     * @throws InvalidArgumentException
     */
    public function dispatch(bool $exit = true): void
    {
        if (!$command = $this->command) {
            $this->displayHelp();
            return;
        }

        if (!isset($this->commands[$command])) {
            $this->displayHelp("The command '{$command}' is not exists!");
            return;
        }

        if (isset($this->opts['h']) || isset($this->opts['help'])) {
            $this->displayCommandHelp($command);
            return;
        }

        try {
            $status = $this->runHandler($command, $this->commands[$command]);
        } catch (Throwable $e) {
            $status = $this->handleException($e);
        }

        if ($exit) {
            $this->stop($status);
        }
    }

    /**
     * @param mixed $code
     */
    public function stop(mixed $code = 0): void
    {
        if ($code) {
            Command::error('error code: ' . $code);
        }
    }

    /**
     * @param string $command
     * @param mixed $handler
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function runHandler(string $command, mixed $handler): mixed
    {
        if (is_string($handler)) {
            // function name
            if (function_exists($handler)) {
                return $handler($this);
            }

            if (class_exists($handler)) {
                $handler = new $handler;

                // $handler->execute()
                if (method_exists($handler, 'execute')) {
                    return $handler->execute($this);
                }
            }
        }

        // a \Closure OR $handler->__invoke()
        if (is_object($handler) && method_exists($handler, '__invoke')) {
            return $handler($this);
        }

        throw new RuntimeException("Invalid handler of the command: $command");
    }

    /**
     * @param Throwable $throwable
     * @return int
     */
    protected function handleException(Throwable $throwable): int
    {
        $code = $throwable->getCode() !== 0 ? $throwable->getCode() : -1;
        app('exception')->report($throwable);
        return $code;
    }

    /**
     * @param callable $handler
     * @param array $config
     */
    public function addObject(callable $handler, array $config = []): void
    {
        if (method_exists($handler, '__invoke')) {
            // has config method
            if (method_exists($handler, 'getHelpConfig')) {
                $config = $handler->getHelpConfig();
            }

            $this->addByConfig($handler, $config);
            return;
        }

        throw new InvalidArgumentException('Command handler must be an object and has method: __invoke');
    }

    /**
     * @param callable $handler
     * @param array $config
     */
    public function addByConfig(callable $handler, array $config): void
    {
        if (empty($config['name'])) {
            throw new InvalidArgumentException('Invalid arguments for add command');
        }

        $this->addCommand($config['name'], $handler, $config);
    }

    /**
     * @param string $command
     * @param callable $handler
     * @param array|string|null $config
     */
    public function add(string $command, callable $handler, array|string $config = null): void
    {
        $this->addCommand($command, $handler, $config);
    }

    /**
     * @param string $command
     * @param callable $handler
     * @param array|string|null $config
     */
    public function addCommand(string $command, callable $handler, array|string $config = null): void
    {
        if (!$command) {
            throw new InvalidArgumentException('Invalid arguments for add command');
        }

        if (($len = strlen($command)) > $this->keyWidth) {
            $this->keyWidth = $len;
        }

        $this->commands[$command] = $handler;

        if (is_string($config)) {
            $desc = trim($config);
            $config = self::COMMAND_CONFIG;

            // append desc
            $config['desc'] = $desc;

            // save
            $this->messages[$command] = $config;
        } elseif (is_array($config)) {
            $this->messages[$command] = array_merge(self::COMMAND_CONFIG, $config);
        }
    }

    /**
     * @param array $commands
     *
     * @throws InvalidArgumentException
     */
    public function commands(array $commands): void
    {
        foreach ($commands as $command => $handler) {
            $desc = '';

            if (is_array($handler)) {
                $conf = array_values($handler);
                $handler = $conf[0];
                $desc = $conf[1] ?? '';
            }

            $this->addCommand($command, $handler, $desc);
        }
    }

    /****************************************************************************
     * helper methods
     ****************************************************************************/

    /**
     * @param string $err
     */
    public function displayHelp(string $err = ''): void
    {
        if ($err) {
            echo Color::render("<red>Error</red>: $err\n\n");
        }

        // help
        $help = ucfirst($this->metas['desc']);
        $help .= "(<red>" . Application::VERSION . "</red>)";

        $usage = "<cyan>{$this->script} command [options] [arguments]</cyan>";

        $help = "$help\n\n<comment>Commands:</comment>\n";
        $data = $this->messages;
        ksort($data);

        foreach ($data as $command => $item) {
            $command = str_pad($command, $this->keyWidth, ' ');
            $desc = $item['desc'] ? ucfirst($item['desc']) : 'No description for the command';
            $help .= "<green>$command</green>   $desc\n";
        }

        $help .= "\nFor command usage please run: <cyan>{$this->script} command [options] [arguments]</cyan>";

        echo Color::render($help) . PHP_EOL;
        exit(0);
    }

    /**
     * @param string $name
     */
    public function displayCommandHelp(string $name): void
    {
        $checkVar = false;
        $fullCmd = $this->script . " $name";

        $config = $this->messages[$name] ?? [];
        $usage = "$fullCmd [args ...] [--opts ...]";

        if (!$config) {
            $nodes = [
                'No description for the command',
                "<comment>Usage:</comment> \n  $usage"
            ];
        } else {
            $checkVar = true;
            $userHelp = rtrim($config['help'], "\n");

            $usage = $config['usage'] ?: $usage;
            $nodes = [
                ucfirst($config['desc']),
                "<comment>Usage:</comment> \n  $usage\n",
                $userHelp ? $userHelp . "\n" : ''
            ];
        }

        $help = implode("\n", $nodes);

        if ($checkVar && strpos($help, '{{')) {
            $help = strtr($help, [
                '{{command}}' => $name,
                '{{fullCmd}}' => $fullCmd,
                '{{workDir}}' => $this->pwd,
                '{{pwdDir}}' => $this->pwd,
                '{{script}}' => $this->script,
            ]);
        }

        echo Color::render($help);
    }

    /**
     * @param int|string $name
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function getArg(int|string $name, mixed $default = null): mixed
    {
        return $this->args[$name] ?? $default;
    }

    /**
     * @param int|string $name
     * @param null $default
     * @return mixed
     */
    public function argument(int|string $name, $default = null): mixed
    {
        return $this->getArg($name, $default);
    }

    /**
     * @param string|int $name
     * @param int $default
     *
     * @return int
     */
    public function getIntArg(int|string $name, int $default = 0): int
    {
        return (int)$this->getArg($name, $default);
    }

    /**
     * @param string|int $name
     * @param string $default
     *
     * @return string
     */
    public function getStrArg(int|string $name, string $default = ''): string
    {
        return (string)$this->getArg($name, $default);
    }

    /**
     * @param string $name
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function getOpt(string $name, mixed $default = null): mixed
    {
        return $this->opts[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function option(string $name, mixed $default = null): mixed
    {
        return $this->getOpt($name, $default);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->opts[$name]);
    }

    /**
     * @param string $name
     * @param int $default
     *
     * @return int
     */
    public function getIntOpt(string $name, int $default = 0): int
    {
        return (int)$this->getOpt($name, $default);
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function getStrOpt(string $name, string $default = ''): string
    {
        return (string)$this->getOpt($name, $default);
    }

    /**
     * @param string $name
     * @param bool $default
     *
     * @return bool
     */
    public function getBoolOpt(string $name, bool $default = false): bool
    {
        return (bool)$this->getOpt($name, $default);
    }

    /****************************************************************************
     * getter/setter methods
     ****************************************************************************/

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    /**
     * @return array
     */
    public function getOpts(): array
    {
        return $this->opts;
    }

    /**
     * @param array $opts
     */
    public function setOpts(array $opts): void
    {
        $this->opts = $opts;
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * @return string
     */
    public function getScriptName(): string
    {
        return basename($this->script);
    }

    /**
     * @param string $script
     */
    public function setScript(string $script): void
    {
        $this->script = $script;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param array $commands
     */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @return int
     */
    public function getKeyWidth(): int
    {
        return $this->keyWidth;
    }

    /**
     * @param int $keyWidth
     */
    public function setKeyWidth(int $keyWidth): void
    {
        $this->keyWidth = $keyWidth > 1 ? $keyWidth : 12;
    }

    /**
     * @return string
     */
    public function getPwd(): string
    {
        return $this->pwd;
    }

    /**
     * @return array
     */
    public function getMetas(): array
    {
        return $this->metas;
    }

    /**
     * @param array $metas
     */
    public function setMetas(array $metas): void
    {
        $this->metas = array_merge($this->metas, $metas);
    }

    /**
     * @param string $string
     */
    public function alert(string $string): void
    {
        Cli::writeln('<alert>' . $string . '</alert>');
    }

    /**
     * @param string $string
     */
    public function warning(string $string): void
    {
        Cli::writeln('<warning>' . $string . '</warning>');
    }

    /**
     * @param string $string
     */
    public function error(string $string): void
    {
        Cli::writeln('<error>' . $string . '</error>');
    }

    /**
     * @param string $string
     */
    public function critical(string $string): void
    {
        Cli::writeln('<critical>' . $string . '</critical>');
    }

    /**
     * @param string $string
     */
    public function notice(string $string): void
    {
        Cli::writeln('<notice>' . $string . '</notice>');
    }

    /**
     * @param string $string
     */
    public function info(string $string): void
    {
        Cli::writeln('<info>' . $string . '</info>');
    }

    public function line(int $line = 1): void
    {
        printf(str_pad(PHP_EOL, $line));
    }

    public function message(string $message = '', bool $newLine = true): void
    {
        printf('%s', Color::render($message . ($newLine ? PHP_EOL : '')));
    }

    /**
     * @param string $string
     */
    public function success(string $string): void
    {
        Cli::writeln('<success>' . $string . '</success>');
    }

    /**
     * @param string $string
     */
    public function comment(string $string): void
    {
        Cli::writeln('<comment>' . $string . '</comment>');
    }

    /**
     * @param string $string
     * @param string $default
     * @param string $type
     * @return bool
     */
    public function confirm(string $string, string $default = 'y', string $type = 'info'): bool
    {
        $result = Cli::read('<' . $type . '>' . $string . '</' . $type . '> (' . $default . '): ');
        return ($result ?: $default) === 'y';
    }

    /**
     * @param string $string
     * @param string $default
     * @param string $type
     * @return bool
     */
    public function confirmLn(string $string, string $default = 'y', string $type = 'info'): bool
    {
        $result = Cli::read('<' . $type . '>' . $string . '</' . $type . '> (' . $default . '): ', true);
        return ($result ?: $default) === 'y';
    }

    /**
     * @param string $question
     * @param null $default
     * @param string $type
     * @return string|null
     */
    public function ask(string $question, $default = null, string $type = 'info'): ?string
    {
        $question = '<' . $type . '>' . $question . '</' . $type . '>';
        $result = Cli::read($question . ($default ? ' (' . $default . ')' : ''));
        return $result ?: $default;
    }

    /**
     * @param string $question
     * @param null $default
     * @param string $type
     * @return string|null
     */
    public function askLn(string $question, $default = null, string $type = 'info'): ?string
    {
        $question = '<' . $type . '>' . $question . '</' . $type . '>';
        $result = Cli::read($question . ($default ? ' (' . $default . ')' : ''), true);
        return $result ?: $default;
    }
}
