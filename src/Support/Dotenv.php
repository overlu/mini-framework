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

    public function getAllValues(): array
    {
        return $this->env ?: $this->parseEnvFile();
    }

    /**
     * @return array
     */
    public function parseEnvFile(): array
    {
        if (is_file($this->path)) {
            $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if ($line === '' || $line[0] === '#') {
                    continue;
                }
                if ($pos = strpos($line, ' #')) {
                    $line = substr($line, 0, $pos);
                }
                $line = trim($line);
                if (count($parts = explode('=', $line, 2)) === 2) {
                    list($key, $value) = $parts;
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
                        default:
                            if (str_contains($value, '${')) {
                                preg_match_all('#\${([\w.]+)}#', $value, $matches);
                                foreach ((array)$matches[1] as $match) {
                                    if (isset($this->env[$match])) {
                                        $value = strtr($value, ['${' . $match . '}' => $this->env[$match]]);
                                    }
                                }
                            } elseif (str_contains($value, '$')) {
                                preg_match_all('#\$([A-Z_\d]+)#', $value, $matches);
                                foreach ((array)$matches[1] as $match) {
                                    if (isset($this->env[$match])) {
                                        $value = strtr($value, ['$' . $match => $this->env[$match]]);
                                    }
                                }
                            }
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