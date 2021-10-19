<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Session;

use SessionHandlerInterface;

interface ExistenceAwareInterface
{
    /**
     * Set the existence state for the session.
     *
     * @param bool $value
     * @return SessionHandlerInterface
     */
    public function setExists(bool $value): SessionHandlerInterface;
}