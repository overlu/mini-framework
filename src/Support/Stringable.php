<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Closure;
use Mini\Support\Traits\Conditionable;
use Mini\Support\Traits\Macroable;
use Symfony\Component\VarDumper\VarDumper;

class Stringable
{
    use Conditionable, Macroable;

    /**
     * The underlying string value.
     *
     * @var string
     */
    protected string $value;

    /**
     * Create a new instance of the class.
     *
     * @param string $value
     * @return void
     */
    public function __construct(string $value = '')
    {
        $this->value = $value;
    }

    /**
     * Return the remainder of a string after the first occurrence of a given value.
     *
     * @param string $search
     * @return static
     */
    public function after(string $search): Stringable
    {
        return new static(Str::after($this->value, $search));
    }

    /**
     * Return the remainder of a string after the last occurrence of a given value.
     *
     * @param string $search
     * @return static
     */
    public function afterLast(string $search): Stringable
    {
        return new static(Str::afterLast($this->value, $search));
    }

    /**
     * Append the given values to the string.
     *
     * @param array $values
     * @return static
     */
    public function append(...$values): Stringable
    {
        return new static($this->value . implode('', $values));
    }

    /**
     * Transliterate a UTF-8 value to ASCII.
     *
     * @param string $language
     * @return static
     */
    public function ascii(string $language = 'en'): Stringable
    {
        return new static(Str::ascii($this->value, $language));
    }

    /**
     * Get the trailing name component of the path.
     *
     * @param string $suffix
     * @return static
     */
    public function basename(string $suffix = ''): Stringable
    {
        return new static(basename($this->value, $suffix));
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     *
     * @param string $search
     * @return static
     */
    public function before(string $search): Stringable
    {
        return new static(Str::before($this->value, $search));
    }

    /**
     * Get the portion of a string before the last occurrence of a given value.
     *
     * @param string $search
     * @return static
     */
    public function beforeLast(string $search): Stringable
    {
        return new static(Str::beforeLast($this->value, $search));
    }

    /**
     * Get the portion of a string between two given values.
     *
     * @param string $from
     * @param string $to
     * @return static
     */
    public function between(string $from, string $to): Stringable
    {
        return new static(Str::between($this->value, $from, $to));
    }

    /**
     * Convert a value to camel case.
     *
     * @return static
     */
    public function camel(): Stringable
    {
        return new static(Str::camel($this->value));
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param array|string $needles
     * @return bool
     */
    public function contains(array|string $needles): bool
    {
        return Str::contains($this->value, $needles);
    }

    /**
     * Determine if a given string contains all array values.
     *
     * @param array $needles
     * @return bool
     */
    public function containsAll(array $needles): bool
    {
        return Str::containsAll($this->value, $needles);
    }

    /**
     * Get the parent directory's path.
     *
     * @param int $levels
     * @return static
     */
    public function dirname(int $levels = 1): Stringable
    {
        return new static(dirname($this->value, $levels));
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param array|string $needles
     * @return bool
     */
    public function endsWith(array|string $needles): bool
    {
        return Str::endsWith($this->value, $needles);
    }

    /**
     * Determine if the string is an exact match with the given value.
     *
     * @param string $value
     * @return bool
     */
    public function exactly(string $value): bool
    {
        return $this->value === $value;
    }

    /**
     * Explode the string into an array.
     *
     * @param string $delimiter
     * @param int $limit
     * @return Collection
     */
    public function explode(string $delimiter, int $limit = PHP_INT_MAX): Collection
    {
        return collect(explode($delimiter, $this->value, $limit));
    }

    /**
     * Split a string using a regular expression.
     *
     * @param string $pattern
     * @param int $limit
     * @param int $flags
     * @return Collection
     */
    public function split(string $pattern, int $limit = -1, int $flags = 0): Collection
    {
        $segments = preg_split($pattern, $this->value, $limit, $flags);

        return !empty($segments) ? collect($segments) : collect();
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @param string $cap
     * @return static
     */
    public function finish(string $cap): static
    {
        return new static(Str::finish($this->value, $cap));
    }

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param array|string $pattern
     * @return bool
     */
    public function is(array|string $pattern): bool
    {
        return Str::is($pattern, $this->value);
    }

    /**
     * Determine if a given string is 7 bit ASCII.
     *
     * @return bool
     */
    public function isAscii(): bool
    {
        return Str::isAscii($this->value);
    }

    /**
     * Determine if the given string is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    /**
     * Determine if the given string is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Convert a string to kebab case.
     *
     * @return static
     */
    public function kebab(): Stringable
    {
        return new static(Str::kebab($this->value));
    }

    /**
     * Return the length of the given string.
     *
     * @param string|null $encoding
     * @return int
     */
    public function length(string $encoding = null): int
    {
        return Str::length($this->value, $encoding);
    }

    /**
     * Limit the number of characters in a string.
     *
     * @param int $limit
     * @param string $end
     * @return static
     */
    public function limit(int $limit = 100, string $end = '...'): static
    {
        return new static(Str::limit($this->value, $limit, $end));
    }

    /**
     * Convert the given string to lower-case.
     *
     * @return static
     */
    public function lower(): static
    {
        return new static(Str::lower($this->value));
    }

    /**
     * Get the string matching the given pattern.
     *
     * @param string $pattern
     * @return static
     */
    public function match(string $pattern): static
    {
        preg_match($pattern, $this->value, $matches);

        if (!$matches) {
            return new static;
        }

        return new static($matches[1] ?? $matches[0]);
    }

    /**
     * Get the string matching the given pattern.
     *
     * @param string $pattern
     * @return Collection
     */
    public function matchAll(string $pattern): Collection
    {
        preg_match_all($pattern, $this->value, $matches);

        if (empty($matches[0])) {
            return collect();
        }

        return collect($matches[1] ?? $matches[0]);
    }

    /**
     * Parse a Class@method style callback into class and method.
     *
     * @param string|null $default
     * @return array
     */
    public function parseCallback(string $default = null): array
    {
        return Str::parseCallback($this->value, $default);
    }

    /**
     * Get the plural form of an English word.
     *
     * @param int $count
     * @return static
     */
    public function plural(int $count = 2): static
    {
        return new static(Str::plural($this->value, $count));
    }

    /**
     * Pluralize the last word of an English, studly caps case string.
     *
     * @param int $count
     * @return static
     */
    public function pluralStudly(int $count = 2): static
    {
        return new static(Str::pluralStudly($this->value, $count));
    }

    /**
     * Prepend the given values to the string.
     *
     * @param array $values
     * @return static
     */
    public function prepend(...$values): static
    {
        return new static(implode('', $values) . $this->value);
    }

    /**
     * Replace the given value in the given string.
     *
     * @param string|string[] $search
     * @param string|string[] $replace
     * @return static
     */
    public function replace(array|string $search, array|string $replace): static
    {
        return new static(str_replace($search, $replace, $this->value));
    }

    /**
     * Replace a given value in the string sequentially with an array.
     *
     * @param string $search
     * @param array $replace
     * @return static
     */
    public function replaceArray(string $search, array $replace): static
    {
        return new static(Str::replaceArray($search, $replace, $this->value));
    }

    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @return static
     */
    public function replaceFirst(string $search, string $replace): static
    {
        return new static(Str::replaceFirst($search, $replace, $this->value));
    }

    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @return static
     */
    public function replaceLast(string $search, string $replace): static
    {
        return new static(Str::replaceLast($search, $replace, $this->value));
    }

    /**
     * Replace the patterns matching the given regular expression.
     *
     * @param string $pattern
     * @param string|Closure $replace
     * @param int $limit
     * @return static
     */
    public function replaceMatches(string $pattern, string|Closure $replace, int $limit = -1): static
    {
        if ($replace instanceof Closure) {
            return new static(preg_replace_callback($pattern, $replace, $this->value, $limit));
        }

        return new static(preg_replace($pattern, $replace, $this->value, $limit));
    }

    /**
     * Begin a string with a single instance of a given value.
     *
     * @param string $prefix
     * @return static
     */
    public function start(string $prefix): static
    {
        return new static(Str::start($this->value, $prefix));
    }

    /**
     * Convert the given string to upper-case.
     *
     * @return static
     */
    public function upper(): static
    {
        return new static(Str::upper($this->value));
    }

    /**
     * Convert the given string to title case.
     *
     * @return static
     */
    public function title(): static
    {
        return new static(Str::title($this->value));
    }

    /**
     * Get the singular form of an English word.
     *
     * @return static
     */
    public function singular(): static
    {
        return new static(Str::singular($this->value));
    }

    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param string $separator
     * @param string|null $language
     * @return static
     */
    public function slug(string $separator = '-', ?string $language = 'en'): static
    {
        return new static(Str::slug($this->value, $separator, $language));
    }

    /**
     * Convert a string to snake case.
     *
     * @param string $delimiter
     * @return static
     */
    public function snake(string $delimiter = '_'): static
    {
        return new static(Str::snake($this->value, $delimiter));
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param array|string $needles
     * @return bool
     */
    public function startsWith(array|string $needles): bool
    {
        return Str::startsWith($this->value, $needles);
    }

    /**
     * Convert a value to studly caps case.
     *
     * @return static
     */
    public function studly(): Stringable
    {
        return new static(Str::studly($this->value));
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     *
     * @param int $start
     * @param int|null $length
     * @return static
     */
    public function substr(int $start, int $length = null): static
    {
        return new static(Str::substr($this->value, $start, $length));
    }

    /**
     * Returns the number of substring occurrences.
     *
     * @param string $needle
     * @param int|null $offset
     * @param int|null $length
     * @return int
     */
    public function substrCount(string $needle, int $offset = null, int $length = null): int
    {
        return Str::substrCount($this->value, $needle, $offset, $length);
    }

    /**
     * Trim the string of the given characters.
     *
     * @param string|null $characters
     * @return static
     */
    public function trim(string $characters = null): static
    {
        return new static(trim(...array_merge([$this->value], func_get_args())));
    }

    /**
     * Left trim the string of the given characters.
     *
     * @param string|null $characters
     * @return static
     */
    public function ltrim(string $characters = null): static
    {
        return new static(ltrim(...array_merge([$this->value], func_get_args())));
    }

    /**
     * Right trim the string of the given characters.
     *
     * @param string|null $characters
     * @return static
     */
    public function rtrim(string $characters = null): static
    {
        return new static(rtrim(...array_merge([$this->value], func_get_args())));
    }

    /**
     * Make a string's first character uppercase.
     *
     * @return static
     */
    public function ucfirst(): static
    {
        return new static(Str::ucfirst($this->value));
    }

    /**
     * Execute the given callback if the string is empty.
     *
     * @param callable $callback
     * @return static
     */
    public function whenEmpty(callable $callback): static
    {
        if ($this->isEmpty()) {
            $result = $callback($this);

            return is_null($result) ? $this : $result;
        }

        return $this;
    }

    /**
     * Limit the number of words in a string.
     *
     * @param int $words
     * @param string $end
     * @return static
     */
    public function words(int $words = 100, string $end = '...'): static
    {
        return new static(Str::words($this->value, $words, $end));
    }

    /**
     * Dump the string.
     *
     * @return $this
     */
    public function dump(): self
    {
        VarDumper::dump($this->value);

        return $this;
    }

    /**
     * Dump the string and end the script.
     *
     * @return void
     */
    public function dd(): void
    {
        $this->dump();
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Proxy dynamic properties onto methods.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->{$key}();
    }

    /**
     * Get the raw string value.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}
