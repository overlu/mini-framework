<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Mini\Singleton;

class Dotenv
{
    use Singleton;

    /**
     * @var string
     */
    protected ?string $path;
    /**
     * @var bool
     */
    private bool $override = false;

    private array $env = [];

    /**
     * Dotenv constructor.
     * @param $path
     */
    private function __construct($path = null)
    {
        $this->path = $path ?: BASE_PATH . '/.env';
        if (!is_file($this->path)) {
            file_put_contents($this->path, '');
        }
    }

    /**
     * 强制覆盖
     * @return void
     */
    public function setOverride(): void
    {
        $this->override = true;
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function getValue(string $key, $default = null)
    {
        if (!$this->override && isset($this->env[$key])) {
            return $this->env[$key];
        }
        $data = $this->parseEnvFile();
        return $data[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function parseEnvFile(): array
    {
        if (is_file($this->path)) {
            $temps = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($temps as $temp) {
                if (count($data = explode('=', $temp, 2)) === 2) {
                    $key = trim($data[0]);
                    $value = trim($data[1]);
                    switch (strtolower($value)) {
                        case 'true':
                        case '(true)':
                            $value = true;
                            break;
                        case 'false':
                        case '(false)':
                            $value = false;
                            break;
                        case 'empty':
                        case '(empty)':
                            $value = '';
                            break;
                        case 'null':
                        case '(null)':
                            $value = null;
                            break;
                    }
                    $this->env[$key] = $value;
                }
            }
        }
        return $this->env;
    }


    /**
     * @param string $key
     * @param null $value
     */
    public function setValue(string $key, $value = null): void
    {
        if (isset($this->env[$key])) {
            if (is_bool($this->env[$key])) {
                $old = $this->env[$key] ? 'true' : 'false';
            } elseif ($this->env[$key] === null) {
                $old = 'null';
            } else {
                $old = $this->env[$key];
            }
            file_put_contents($this->path, str_replace("$key=" . $old, "$key=" . $value, file_get_contents($this->path)));
        } else {
            file_put_contents($this->path, "$key=" . $value . PHP_EOL, FILE_APPEND);
        }
        $this->env[$key] = $value;
    }

    /**
     * @param array $data
     */
    public function setValues(array $data): void
    {
        if (!$this->isLastLineEmpty()) {
            file_put_contents($this->path, PHP_EOL, FILE_APPEND);
        }
        foreach ($data as $key => $value) {
            $this->setValue($key, $value);
        }
    }

    /**
     * @return bool
     */
    private function isLastLineEmpty(): bool
    {
        $data = file($this->path);
        $num = count($data);
        return ($data[$num - 1] === PHP_EOL);
    }
}