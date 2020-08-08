<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console\Util;

use Mini\Console\ColorTag;

class Format
{
    /**
     * @param mixed $val
     * @return string
     */
    public static function typeToString($val): string
    {
        if (null === $val) {
            return '(Null)';
        }
        if (is_bool($val)) {
            return $val ? '(True)' : '(False)';
        }
        return (string)$val;
    }

    /**
     * @param string $string
     * @param int $indent
     * @param string $indentChar
     * @return string
     */
    public static function applyIndent(string $string, int $indent = 2, string $indentChar = ' '): string
    {
        if (!$string || $indent <= 0) {
            return $string;
        }
        $new = '';
        $list = explode("\n", $string);
        $indentStr = str_repeat($indentChar ?: ' ', $indent);
        foreach ($list as $value) {
            $new .= $indentStr . trim($value) . "\n";
        }
        return $new;
    }

    /**
     * Word wrap text with indentation to fit the screen size
     * @param string $text the text to be wrapped
     * @param integer $indent number of spaces to use for indentation.
     * @param integer $width
     * @return string the wrapped text.
     */
    public static function wrapText($text, $indent = 0, $width = 0): string
    {
        if (!$text) {
            return $text;
        }

        if ((int)$width <= 0) {
            $size = self::getScreenSize();
            if ($size === false || $size[0] <= $indent) {
                return $text;
            }
            $width = $size[0];
        }

        $pad = str_repeat(' ', $indent);
        $lines = explode("\n", wordwrap($text, $width - $indent, "\n", true));
        $first = true;

        foreach ($lines as $i => $line) {
            if ($first) {
                $first = false;
                continue;
            }
            $lines[$i] = $pad . $line;
        }

        return $pad . '  ' . implode("\n", $lines);
    }

    /**
     * @param array $options
     * @return array
     */
    public static function alignOptions(array $options): array
    {
        // e.g '-h, --help'
        $hasShort = (bool)strpos(implode('', array_keys($options)), ',');

        if (!$hasShort) {
            return $options;
        }
        $formatted = [];
        foreach ($options as $name => $des) {
            if (!$name = trim($name, ', ')) {
                continue;
            }
            if (!strpos($name, ',')) {
                // padding length equals to '-h, '
                $name = '    ' . $name;
            } else {
                $name = str_replace([' ', ','], ['', ', '], $name);
            }
            $formatted[$name] = $des;
        }

        return $formatted;
    }

    /**
     * @param float $memory
     * @return string
     */
    public static function memoryUsage($memory = null): string
    {
        $memory = $memory ?: memory_get_usage(true);
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.2f Gb', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.2f Mb', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%.2f Kb', $memory / 1024);
        }

        return sprintf('%d B', $memory);
    }

    /**
     * format timestamp to how long ago
     * @param int $secs
     * @return string
     */
    public static function howLongAgo(int $secs): string
    {
        static $timeFormats = [
            [0, '< 1 sec'],
            [1, '1 sec'],
            [2, 'secs', 1],
            [60, '1 min'],
            [120, 'mins', 60],
            [3600, '1 hr'],
            [7200, 'hrs', 3600],
            [86400, '1 day'],
            [172800, 'days', 86400],
        ];
        foreach ($timeFormats as $index => $format) {
            if ($secs >= $format[0]) {
                $next = $timeFormats[$index + 1] ?? false;

                if (($next && $secs < $next[0]) || $index === count($timeFormats) - 1) {
                    if (2 === count($format)) {
                        return $format[1];
                    }
                    return floor($secs / $format[2]) . ' ' . $format[1];
                }
            }
        }
        return date('Y-m-d H:i:s', $secs);
    }

    /**
     * splice Array
     * @param array $data
     *     e.g [
     *     'system'  => 'Linux',
     *     'version'  => '4.4.5',
     *     ]
     * @param array $opts
     * @return string
     */
    public static function spliceKeyValue(array $data, array $opts = []): string
    {
        $text = '';
        $opts = array_merge([
            'leftChar' => '',   // e.g '  ', ' * '
            'sepChar' => ' ',  // e.g ' | ' OUT: key | value
            'keyStyle' => '',   // e.g 'info','comment'
            'valStyle' => '',   // e.g 'info','comment'
            'keyMinWidth' => 8,
            'keyMaxWidth' => null, // if not set, will automatic calculation
            'ucFirst' => true,  // upper first char for value
        ], $opts);

        if (!is_numeric($opts['keyMaxWidth'])) {
            $opts['keyMaxWidth'] = self::getKeyMaxWidth($data);
        }

        // compare
        if ((int)$opts['keyMinWidth'] > $opts['keyMaxWidth']) {
            $opts['keyMaxWidth'] = $opts['keyMinWidth'];
        }

        $keyStyle = trim($opts['keyStyle']);

        foreach ($data as $key => $value) {
            $hasKey = !is_int($key);
            $text .= $opts['leftChar'];

            if ($hasKey && $opts['keyMaxWidth']) {
                $key = str_pad((string)$key, $opts['keyMaxWidth'], ' ');
                $text .= ColorTag::wrap($key, $keyStyle) . $opts['sepChar'];
            }

            // if value is array, translate array to string
            if (is_array($value)) {
                $temp = '[';

                /** @var array $value */
                foreach ($value as $k => $val) {
                    if (is_bool($val)) {
                        $val = $val ? '(True)' : '(False)';
                    } else {
                        $val = is_scalar($val) ? (string)$val : gettype($val);
                    }

                    $temp .= (!is_numeric($k) ? "$k: " : '') . "$val, ";
                }

                $value = rtrim($temp, ' ,') . ']';
            } elseif (is_bool($value)) {
                $value = $value ? '(True)' : '(False)';
            } else {
                $value = (string)$value;
            }

            $value = $hasKey && $opts['ucFirst'] ? ucfirst($value) : $value;
            $text .= ColorTag::wrap($value, $opts['valStyle']) . "\n";
        }

        return $text;
    }

    /**
     * @param bool $refresh
     * @return array|bool
     */
    public static function getScreenSize(bool $refresh = false)
    {
        $stty = [];
        if (exec('stty -a 2>&1', $stty) && preg_match('/rows\s+(\d+);\s*columns\s+(\d+);/mi', implode(' ', $stty), $matches)) {
            return [$matches[2], $matches[1]];
        }
        if (($width = (int)exec('tput cols 2>&1')) > 0 && ($height = (int)exec('tput lines 2>&1')) > 0) {
            return [$width, $height];
        }
        if (($width = (int)getenv('COLUMNS')) > 0 && ($height = (int)getenv('LINES')) > 0) {
            return [$width, $height];
        }
        return false;
    }

    /**
     * get key Max Width
     *
     * @param array $data
     *     [
     *     'key1'      => 'value1',
     *     'key2-test' => 'value2',
     *     ]
     * @param bool $expectInt
     * @return int
     */
    public static function getKeyMaxWidth(array $data, bool $expectInt = false): int
    {
        $keyMaxWidth = 0;
        foreach ($data as $key => $value) {
            // key is not a integer
            if (!$expectInt || !is_numeric($key)) {
                $width = mb_strlen((string)$key, 'UTF-8');
                $keyMaxWidth = $width > $keyMaxWidth ? $width : $keyMaxWidth;
            }
        }
        return $keyMaxWidth;
    }
}