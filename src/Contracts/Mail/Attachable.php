<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Mail;

use Mini\Mail\Attachment;

interface Attachable
{
    /**
     * Get an attachment instance for this entity.
     *
     * @return Attachment
     */
    public function toMailAttachment(): Attachment;
}
