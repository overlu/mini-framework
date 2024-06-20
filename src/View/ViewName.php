<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

class ViewName
{
    /**
     * Normalize the given view name.
     *
     * @param string $name
     * @return string
     */
    public static function normalize(string $name): string
    {
        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (!str_contains($name, $delimiter)) {
            return str_replace('/', '.', $name);
        }

        [$namespace, $name] = explode($delimiter, $name);

        return $namespace . $delimiter . str_replace('/', '.', $name);
    }
}
