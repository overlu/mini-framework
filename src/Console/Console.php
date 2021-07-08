<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

use Mini\Singleton;

/**
 * Class Console
 * @package Mini\Console
 * @mixin App
 */
class Console
{
    private ?App $app = null;

    public function setApp(App $app)
    {
        $this->app = $app;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (!$this->app) {
            throw new \Exception('Console not initialize yet');
        }
        return $this->app->$name(...$arguments);
    }
}