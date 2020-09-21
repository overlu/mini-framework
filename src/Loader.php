<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Composer\Autoload\ClassLoader;
use Mini\Support\Composer;

/**
 * Class Loader
 * @package Mini
 */
class Loader
{
    use Singleton;

    private ClassLoader $loader;

    public function __construct()
    {
        $this->loader = Composer::getLoader();
    }

    public function bind(string $originClass, string $newClass): void
    {
        if ($file = $this->findFile($newClass)) {
            $this->loader->addClassMap([$originClass => $file]);
        }
    }

    public function findFile(string $class)
    {
        return $this->loader->findFile($class);
    }

    public function loadClass(string $class)
    {
        return $this->loader->loadClass($class);
    }

    public function getClassMap(): array
    {
        return $this->loader->getClassMap();
    }
}