<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

use Mini\Context;

trait CoroutineProxy
{
    public function __call($name, $arguments)
    {
        return $this->getTargetObject()->{$name}(...$arguments);
    }

    public function __get($name)
    {
        return $this->getTargetObject()->{$name};
    }

    public function __set($name, $value)
    {
        $target = $this->getTargetObject();
        return $target->{$name} = $value;
    }

    /**
     * @return mixed
     */
    protected function getTargetObject(): mixed
    {
        if (!isset($this->proxyKey)) {
            throw new \RuntimeException('$proxyKey property of class missing.');
        }
        return Context::get($this->proxyKey);
    }
}
