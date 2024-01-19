<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Queue;

interface QueueableEntity
{
    /**
     * Get the queueable identity for the entity.
     *
     * @return mixed
     */
    public function getQueueableId(): mixed;

    /**
     * Get the relationships for the entity.
     *
     * @return array
     */
    public function getQueueableRelations(): array;

    /**
     * Get the connection of the entity.
     *
     * @return string|null
     */
    public function getQueueableConnection(): ?string;
}
