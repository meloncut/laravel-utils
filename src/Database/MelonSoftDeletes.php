<?php

namespace Meloncut\LaravelUtils\Database;

/**
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withTrashed(bool $withTrashed = true)
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlyTrashed()
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withoutTrashed()
 */
trait MelonSoftDeletes {
    /**
     * Indicates if the Model is currently force deleting.
     *
     * @var bool
     */
    protected $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a Model.
     *
     * @return void
     */
    public static function bootMelonSoftDeletes () {
        static::addGlobalScope(new MelonSoftDeletingScope);
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftDeletes () {
        if (!isset($this->casts[$this->getDeletedColumn()])) {
            $this->casts[$this->getDeletedColumn()] = 'bool';
            $this->casts[$this->getDeletedAtColumn()] = 'datetime';
        }
    }

    /**
     * Force a hard delete on a soft deleted Model.
     *
     * @return bool|null
     */
    public function forceDelete () {
        $this->forceDeleting = true;

        return tap($this->delete(), function ($deleted) {
            $this->forceDeleting = false;

            if ($deleted) {
                $this->fireModelEvent('forceDeleted', false);
            }
        });
    }

    /**
     * Perform the actual delete query on this Model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel () {
        if ($this->forceDeleting) {
            return tap($this->setKeysForSaveQuery($this->newModelQuery())->forceDelete(), function () {
                $this->exists = false;
            });
        }

        return $this->runSoftDelete();
    }

    /**
     * Perform the actual delete query on this Model instance.
     *
     * @return void
     */
    protected function runSoftDelete () {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $columns = [
            $this->getDeletedColumn()   => true,
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
        ];

        $this->{$this->getDeletedColumn()} = true;

        if ($this->timestamps && !is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $this->getQualifiedDeletedColumn();

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));

        $this->fireModelEvent('trashed', false);
    }

    /**
     * Restore a soft-deleted Model instance.
     *
     * @return bool|null
     */
    public function restore () {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedColumn()} = false;

        // Once we have saved the Model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Determine if the Model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed () {
        return $this->{$this->getDeletedColumn()} == true;
    }

    /**
     * Register a "softDeleted" Model event callback with the dispatcher.
     *
     * @param \Closure|string $callback
     * @return void
     */
    public static function softDeleted ($callback) {
        static::registerModelEvent('trashed', $callback);
    }

    /**
     * Register a "restoring" Model event callback with the dispatcher.
     *
     * @param \Closure|string $callback
     * @return void
     */
    public static function restoring ($callback) {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a "restored" Model event callback with the dispatcher.
     *
     * @param \Closure|string $callback
     * @return void
     */
    public static function restored ($callback) {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Register a "forceDeleted" Model event callback with the dispatcher.
     *
     * @param \Closure|string $callback
     * @return void
     */
    public static function forceDeleted ($callback) {
        static::registerModelEvent('forceDeleted', $callback);
    }

    /**
     * Determine if the Model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting () {
        return $this->forceDeleting;
    }

    /**
     * Get the name of the "deleted" column.
     *
     * @return string
     */
    public function getDeletedColumn () {
        return defined('static::DELETED') ? static::DELETED : 'deleted';
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn () {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedColumn () {
        return $this->qualifyColumn($this->getDeletedColumn());
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn () {
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }
}
