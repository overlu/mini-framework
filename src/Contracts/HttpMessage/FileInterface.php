<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\HttpMessage;

interface FileInterface
{
    public function getFilename(): string;
}
