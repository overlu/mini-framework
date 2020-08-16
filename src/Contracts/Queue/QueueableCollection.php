<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Queue;

interface QueueableCollection
{
    /**
     * Get the type of the entities being queued.
     *
     * @return string|null
     */
    public function getQueueableClass(): ?string;

    /**
     * Get the identifiers for all of the entities.
     *
     * @return array
     */
    public function getQueueableIds(): array;

    /**
     * Get the relationships of the entities being queued.
     *
     * @return array
     */
    public function getQueueableRelations(): array;

    /**
     * Get the connection of the entities being queued.
     *
     * @return string|null
     */
    public function getQueueableConnection(): ?string;
}
