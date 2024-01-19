<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Debugger {

    function get_debug_print_backtrace($traces): string
    {
        $ret = array();
        foreach ($traces as $i => $call) {
            $object = '';
            if (isset($call['class'])) {
                $object = $call['class'] . $call['type'];
                if (is_array($call['args'])) {
                    foreach ($call['args'] as &$arg) {
                        get_arg($arg);
                    }
                }
            }

            $ret[] = '#' . str_pad($i, 3, ' ')
                . $object . $call['function'] . '(' . implode(', ', $call['args'])
                . ') called at [' . $call['file'] . ':' . $call['line'] . ']';
        }

        return implode("\n", $ret);
    }

    function get_arg(&$arg)
    {
        if (is_object($arg)) {
            $arr = (array)$arg;
            $args = array();
            foreach ($arr as $key => $value) {
                if (str_contains($key, chr(0))) {
                    $key = '';    // Private variable found
                }
                $args[] = '[' . $key . '] => ' . get_arg($value);
            }

            $arg = get_class($arg) . ' Object (' . implode(',', $args) . ')';
        }
    }
}
