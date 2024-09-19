<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Concerns;

trait HasUniqueIds
{
    /**
     * Indicates if the model uses unique ids.
     *
     * @var bool
     */
    public bool $usesUniqueIds = false;

    /**
     * Determine if the model uses unique ids.
     *
     * @return bool
     */
    public function usesUniqueIds(): bool
    {
        return $this->usesUniqueIds;
    }

    /**
     * Generate unique keys for the model.
     *
     * @return void
     */
    public function setUniqueIds(): void
    {
        foreach ($this->uniqueIds() as $column) {
            if (empty($this->{$column})) {
                $this->{$column} = $this->newUniqueId();
            }
        }
    }

    /**
     * Generate a new key for the model.
     *
     * @return string|null
     */
    public function newUniqueId(): ?string
    {
        return null;
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds(): array
    {
        return [];
    }
}
