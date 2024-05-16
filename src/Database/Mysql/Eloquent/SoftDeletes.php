<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

use Closure;

/**
 * @method static static|Builder|\Mini\Database\Mysql\Query\Builder withTrashed()
 * @method static static|Builder|\Mini\Database\Mysql\Query\Builder onlyTrashed()
 * @method static static|Builder|\Mini\Database\Mysql\Query\Builder withoutTrashed()
 */
trait SoftDeletes
{
    /**
     * Indicates if the model is currently force deleting.
     *
     * @var bool
     */
    protected bool $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes(): void
    {
        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftDeletes(): void
    {
        $this->dates[] = $this->getDeletedAtColumn();
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    public function forceDelete(): ?bool
    {
        $this->forceDeleting = true;

        return tap($this->delete(), function ($deleted) {
            $this->forceDeleting = false;

            if ($deleted) {
                $this->fireModelEvent('forceDeleted', false);
            }
        });
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            $this->exists = false;

            return $this->setKeysForSaveQuery($this->newModelQuery())->forceDelete();
        }

        return $this->runSoftDelete();
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete(): void
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];

        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->timestamps && !is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore(): ?bool
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed(): bool
    {
        return !is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Register a "restoring" model event callback with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function restoring(string|Closure $callback): void
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a "restored" model event callback with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function restored(string|Closure $callback): void
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Register a "forceDeleted" model event callback with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function forceDeleted(string|Closure $callback): void
    {
        static::registerModelEvent('forceDeleted', $callback);
    }

    /**
     * Determine if the model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting(): bool
    {
        return $this->forceDeleting;
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn(): string
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn(): string
    {
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }
}
