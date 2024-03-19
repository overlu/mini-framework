<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator;

class ErrorBag
{

    /** @var array */
    protected array $messages = [];

    /**
     * Constructor
     * @param array $messages
     * @return void
     */
    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }

    /**
     * Add message for given key and rule
     * @param string $key
     * @param string $rule
     * @param string $message
     * @return void
     */
    public function add(string $key, string $rule, string $message): void
    {
        if (!isset($this->messages[$key])) {
            $this->messages[$key] = [];
        }

        $this->messages[$key][$rule] = $message;
    }

    /**
     * Get messages count
     * @return int
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Check given key is existed
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        [$key, $ruleName] = $this->parsekey($key);
        if ($this->isWildcardKey($key)) {
            $messages = $this->filterMessagesForWildcardKey($key, $ruleName);
            return count(Helper::arrayDot($messages)) > 0;
        }

        $messages = $this->messages[$key] ?? null;

        if (!$ruleName) {
            return !empty($messages);
        }

        return !empty($messages) and isset($messages[$ruleName]);
    }

    /**
     * Get the first value of array
     * @param string $key
     * @param string $format
     * @return mixed
     */
    public function first(string $key = '', string $format = ':message'): mixed
    {
        if (empty($key)) {
            foreach ($this->messages as $keyMessages) {
                foreach ($keyMessages as $message) {
                    return $this->formatMessage($message, $format);
                }
            }
            return null;
        }
        [$key, $ruleName] = $this->parsekey($key);
        if ($this->isWildcardKey($key)) {
            $messages = $this->filterMessagesForWildcardKey($key, $ruleName);
            $flattenMessages = Helper::arrayDot($messages);
            return array_shift($flattenMessages);
        }

        $keyMessages = $this->messages[$key] ?? [];

        if (empty($keyMessages)) {
            return null;
        }

        if ($ruleName) {
            if (empty($keyMessages[$ruleName])) {
                return null;
            }
            return $this->formatMessage($keyMessages[$ruleName], $format);
        }

        return $this->formatMessage(array_shift($keyMessages), $format);
    }

    /**
     * Get messages from given key, can be use custom format
     * @param string $key
     * @param string $format
     * @return array
     */
    public function get(string $key, string $format = ':message'): array
    {
        [$key, $ruleName] = $this->parsekey($key);
        $results = [];
        if ($this->isWildcardKey($key)) {
            $messages = $this->filterMessagesForWildcardKey($key, $ruleName);
            foreach ($messages as $explicitKey => $keyMessages) {
                foreach ($keyMessages as $rule => $message) {
                    $results[$explicitKey][$rule] = $this->formatMessage($message, $format);
                }
            }
        } else {
            $keyMessages = $this->messages[$key] ?? [];
            foreach ($keyMessages as $rule => $message) {
                if ($ruleName && $ruleName !== $rule) {
                    continue;
                }
                $results[$rule] = $this->formatMessage($message, $format);
            }
        }

        return $results;
    }

    /**
     * Get all messages
     * @param string $format
     * @return array
     */
    public function all(string $format = ':message'): array
    {
        $messages = $this->messages;
        $results = [];
        foreach ($messages as $key => $keyMessages) {
            foreach ($keyMessages as $message) {
                $results[] = $this->formatMessage($message, $format);
            }
        }
        return $results;
    }

    /**
     * Get the first message from existing keys
     * @param string $format
     * @param boolean $dotNotation
     * @return array
     */
    public function firstOfAll(string $format = ':message', bool $dotNotation = false): array
    {
        $messages = $this->messages;
        $results = [];
        foreach ($messages as $key => $keyMessages) {
            if ($dotNotation) {
                $results[$key] = $this->formatMessage(array_shift($messages[$key]), $format);
            } else {
                Helper::arraySet($results, $key, $this->formatMessage(array_shift($messages[$key]), $format));
            }
        }
        return $results;
    }

    /**
     * Get plain array messages
     * @return array
     */
    public function toArray(): array
    {
        return $this->messages;
    }

    /**
     * Parse $key to get the array of $key and $ruleName
     * @param string $key
     * @return array
     */
    protected function parseKey(string $key): array
    {
        $arr = explode(':', $key, 2);
        return [$arr[0], $arr[1] ?? null];
    }

    /**
     * Check the $key is wildcard
     * @param mixed $key
     * @return bool
     */
    protected function isWildcardKey(string $key): bool
    {
        return str_contains($key, '*');
    }

    /**
     * Filter messages with wildcard key
     * @param string $key
     * @param mixed|null $ruleName
     * @return array
     */
    protected function filterMessagesForWildcardKey(string $key, mixed $ruleName = null): array
    {
        $messages = $this->messages;
        $pattern = preg_quote($key, '#');
        $pattern = str_replace('\*', '.*', $pattern);

        $filteredMessages = [];

        foreach ($messages as $k => $keyMessages) {
            if ((bool)preg_match('#^' . $pattern . '\z#u', $k) === false) {
                continue;
            }

            foreach ($keyMessages as $rule => $message) {
                if ($ruleName && $rule !== $ruleName) {
                    continue;
                }
                $filteredMessages[$k][$rule] = $message;
            }
        }

        return $filteredMessages;
    }

    /**
     * Get formatted message
     * @param string $message
     * @param string $format
     * @return string
     */
    protected function formatMessage(string $message, string $format): string
    {
        return str_replace(':message', $message, $format);
    }
}
