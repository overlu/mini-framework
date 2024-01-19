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
    protected string $environmentFile;
    /**
     * @var bool
     */
    private bool $override = false;

    private array $environmentVariables = [];

    /**
     * Dotenv constructor.
     * @param string $path
     */
    private function __construct(string $path = '')
    {
        $this->environmentFile = $path ?: BASE_PATH . '/.env';
        if (!is_file($this->environmentFile)) {
            file_put_contents($this->environmentFile, '');
        }
        $this->parseEnvFile();
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null): mixed
    {
        return $this->environmentVariables[$key] ?? $default;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->environmentVariables[$key]);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->environmentVariables;
    }

    /**
     * @return array
     */
    protected function parseEnvFile(): array
    {
        if (is_file($this->environmentFile)) {
            $lines = file($this->environmentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if ($line === '' || $line[0] === '#') {
                    continue;
                }
                if ($pos = strpos($line, ' #')) {
                    $line = substr($line, 0, $pos);
                }
                $line = trim($line);
                if (count($parts = explode('=', $line, 2)) === 2) {
                    [$key, $value] = $parts;
                    $this->environmentVariables[$key] = $this->praseEnvironmentVariable($value);
                }
            }
        }
        return $this->environmentVariables;
    }


    /**
     * @param string $key
     * @param null $value
     */
    public function set(string $key, $value = null): void
    {
        if (isset($this->environmentVariables[$key])) {
            if (is_bool($this->environmentVariables[$key])) {
                $old = $this->environmentVariables[$key] ? 'true' : 'false';
            } elseif ($this->environmentVariables[$key] === null) {
                $old = 'null';
            } else {
                $old = $this->environmentVariables[$key];
            }
            file_put_contents($this->environmentFile, str_replace("$key=" . $old, "$key=" . $value, file_get_contents($this->environmentFile)));
        } else {
            file_put_contents($this->environmentFile, "$key=" . $value . PHP_EOL, FILE_APPEND);
        }
        $this->environmentVariables[$key] = $value;
    }

    /**
     * @param array $data
     */
    public function setMany(array $data): void
    {
        if (!$this->isLastLineEmpty()) {
            file_put_contents($this->environmentFile, PHP_EOL, FILE_APPEND);
        }
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @return bool
     */
    private function isLastLineEmpty(): bool
    {
        $data = file($this->environmentFile);
        $num = count($data);
        return ($data[$num - 1] === PHP_EOL);
    }

    /**
     * @param $value
     * @return bool|mixed|string|null
     */
    protected function praseEnvironmentVariable($value): mixed
    {
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
                        if (isset($this->environmentVariables[$match])) {
                            $value = strtr($value, ['${' . $match . '}' => $this->environmentVariables[$match]]);
                        }
                    }
                } elseif (str_contains($value, '$')) {
                    preg_match_all('#\$([A-Z_\d]+)#', $value, $matches);
                    foreach ((array)$matches[1] as $match) {
                        if (isset($this->environmentVariables[$match])) {
                            $value = strtr($value, ['$' . $match => $this->environmentVariables[$match]]);
                        }
                    }
                }
        }
        return $value;
    }
}