<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

use Mini\Support\Arr;
use RuntimeException;

class ModelNotFoundException extends RuntimeException
{
    /**
     * Name of the affected Eloquent model.
     *
     * @var string
     */
    protected string $model;

    /**
     * The affected model IDs.
     *
     * @var int|array
     */
    protected int|array $ids;

    /**
     * Set the affected Eloquent model and instance ids.
     *
     * @param string $model
     * @param int|array|string $ids
     * @return $this
     */
    public function setModel(string $model, int|array|string $ids = []): self
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);

        $this->message = "No query results for model [{$model}]";

        if (count($this->ids) > 0) {
            $this->message .= ' ' . implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

        return $this;
    }

    /**
     * Get the affected Eloquent model.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the affected Eloquent model IDs.
     *
     * @return int|array
     */
    public function getIds(): array|int
    {
        return $this->ids;
    }
}
