<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

/**
 * Class FlagsParse - console argument and option parse
 */
class Flags
{
    // These words will be as a Boolean value
    private const TRUE_WORDS = '|on|yes|true|';

    private const FALSE_WORDS = '|off|no|false|';

    /**
     * @param array $argv
     *
     * @return array [$args, $opts]
     */
    public static function simpleParseArgv(array $argv): array
    {
        $args = $opts = [];
        foreach ($argv as $key => $value) {
            // opts
            if (str_starts_with($value, '-')) {
                $value = trim($value, '-');

                // invalid
                if (!$value) {
                    continue;
                }

                if (strpos($value, '=')) {
                    [$n, $v] = explode('=', $value);
                    $opts[$n] = $v;
                } else {
                    $opts[$value] = true;
                }
            } elseif (strpos($value, '=')) {
                [$n, $v] = explode('=', $value);
                $args[$n] = $v;
            } else {
                $args[] = $value;
            }
        }

        return [$args, $opts];
    }

    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     * eg:
     *
     * ```
     * php cli.php run name=john city=chengdu -s=test --page=23 -d -rf --debug --task=off -y=false -D -e dev -v vvv
     * ```
     *
     * ```php
     * $argv = $_SERVER['argv'];
     * // notice: must shift first element.
     * $script = \array_shift($argv);
     * $result = Flags::parseArgv($argv);
     * ```
     *
     * Supports args:
     * <value>
     * arg=<value>
     * Supports opts:
     * -e
     * -e <value>
     * -e=<value>
     * --long-opt
     * --long-opt <value>
     * --long-opt=<value>
     *
     * @link http://php.net/manual/zh/function.getopt.php#83414
     *
     * @param array $params
     * @param array $config
     *
     * @return array [args, short-opts, long-opts]
     *               If 'mergeOpts' is True, will return [args, opts]
     */
    public static function parseArgv(array $params, array $config = []): array
    {
        if (!$params) {
            return [[], [], []];
        }

        $config = array_merge([
            // List of parameters without values(bool option keys)
            'boolOpts' => [], // ['debug', 'h']
            // Whether merge short-opts and long-opts
            'mergeOpts' => false,
            // want parsed options. if not empty, will ignore no matched
            'wantParsedOpts' => [],
            // list of option allow array values.
            'arrayOpts' => [], // ['names', 'status']
        ], $config);

        $args = $sOpts = $lOpts = [];
        // config
        $boolOpts = array_flip((array)$config['boolOpts']);
        $arrayOpts = array_flip((array)$config['arrayOpts']);

        // each() will deprecated at 7.2. so,there use current and next instead it.
        // while (list(,$p) = each($params)) {
        while (false !== ($p = current($params))) {
            next($params);

            // Are options
            if ($p[0] === '-') {
                $value = true;
                $option = substr($p, 1);
                $isLong = false;

                // long-opt: (--<opt>)
                if (str_starts_with($option, '-')) {
                    $option = substr($option, 1);
                    $isLong = true;

                    // long-opt: value specified inline (--<opt>=<value>)
                    if (str_contains($option, '=')) {
                        [$option, $value] = explode('=', $option, 2);
                    }

                    // short-opt: value specified inline (-<opt>=<value>)
                } elseif (isset($option[1]) && $option[1] === '=') {
                    [$option, $value] = explode('=', $option, 2);
                }

                // check if next parameter is a descriptor or a value
                $nxt = current($params);

                // next elem is value. fix: allow empty string ''
                if ($value === true && !isset($boolOpts[$option]) && self::nextIsValue($nxt)) {
                    // list(,$val) = each($params);
                    $value = $nxt;
                    next($params);

                    // short-opt: bool opts. like -e -abc
                } elseif (!$isLong && $value === true) {
                    foreach (str_split($option) as $char) {
                        $sOpts[$char] = true;
                    }
                    continue;
                }

                $value = self::filterBool($value);
                $isArray = isset($arrayOpts[$option]);

                if ($isLong) {
                    if ($isArray) {
                        $lOpts[$option][] = $value;
                    } else {
                        $lOpts[$option] = $value;
                    }
                } elseif ($isArray) { // short
                    $sOpts[$option][] = $value;
                } else { // short
                    $sOpts[$option] = $value;
                }

                continue;
            }

            // parse arguments:
            // - param doesn't belong to any option, define it is args

            // value specified inline (<arg>=<value>)
            if (str_contains($p, '=')) {
                [$name, $value] = explode('=', $p, 2);

                if (self::isValidArgName($name)) {
                    $args[$name] = self::filterBool($value);
                } else {
                    $args[] = $p;
                }
            } else {
                $args[] = $p;
            }
        }

        if ($config['mergeOpts']) {
            return [$args, array_merge($sOpts, $lOpts)];
        }

        return [$args, $sOpts, $lOpts];
    }

    /**
     * parse custom array params
     * ```php
     * $result = Flags::parseArray([
     *  'arg' => 'val',
     *  '--lp' => 'val2',
     *  '--s' => 'val3',
     *  '-h' => true,
     * ]);
     * ```
     *
     * @param array $params
     *
     * @return array
     */
    public static function parseArray(array $params): array
    {
        $args = $sOpts = $lOpts = [];

        foreach ($params as $key => $val) {
            if (is_int($key)) { // as argument
                $args[$key] = $val;
                continue;
            }

            $cleanKey = trim((string)$key, '-');

            if ('' === $cleanKey) { // as argument
                $args[] = $val;
                continue;
            }

            if (str_starts_with($key, '--')) { // long option
                $lOpts[$cleanKey] = $val;
            } elseif (str_starts_with($key, '-')) { // short option
                $sOpts[$cleanKey] = $val;
            } else {
                $args[$key] = $val;
            }
        }

        return [$args, $sOpts, $lOpts];
    }

    /**
     * parse flags from a string
     *
     * ```php
     * $result = Flags::parseString('foo --bar="foobar"');
     * ```
     *
     * @param string $string
     *
     * @todo ...
     */
    public static function parseString(string $string): void
    {
    }

    /**
     * @param mixed|bool $val
     * @param bool $enable
     *
     * @return bool|mixed
     */
    public static function filterBool(mixed $val, bool $enable = true): mixed
    {
        if ($enable) {
            if (is_bool($val) || is_numeric($val)) {
                return $val;
            }

            // check it is a bool value.
            if (false !== stripos(self::TRUE_WORDS, "|$val|")) {
                return true;
            }

            if (false !== stripos(self::FALSE_WORDS, "|$val|")) {
                return false;
            }
        }

        return $val;
    }

    /**
     * @param mixed $val
     *
     * @return bool
     */
    public static function nextIsValue(mixed $val): bool
    {
        // current() fetch error, will return FALSE
        if ($val === false) {
            return false;
        }

        // if is: '', 0
        if (!$val) {
            return true;
        }

        // it isn't option or named argument
        return $val[0] !== '-' && !str_contains($val, '=');
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function isValidArgName(string $name): bool
    {
        return preg_match('#^\w+$#', $name) === 1;
    }

    /**
     * Escapes a token through escapement if it contains unsafe chars.
     *
     * @param string $token
     *
     * @return string
     */
    public static function escapeToken(string $token): string
    {
        return preg_match('{^[\w-]+$}', $token) ? $token : escapeshellarg($token);
    }
}
