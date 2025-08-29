<?php

namespace Netflex\Query\Traits;

use Closure;
use Netflex\Query\Builder;

/**
 * @method static static|\Netflex\Query\Builder withTrashed()
 * @method static static|\Netflex\Query\Builder onlyTrashed()
 * @method static static|\Netflex\Query\Builder withoutTrashed()
 */
trait SoftDeletes
{
    /**
     * Indicates if the model is currently force deleting.
     *
     * @var bool
     */
    protected $forceDeleting = false;

    public static function bootSoftDeletes()
    {
        static::retrieved(function ($model) {
            $model->initializeSoftDeletes();
        });
    }

    protected static function withTrashed($withTrashed = true)
    {
        return static::makeQueryBuilder()
            ->withTrashed($withTrashed);
    }

    protected static function onlyTrashed()
    {
        return static::makeQueryBuilder()
            ->onlyTrashed();
    }

    protected static function withoutTrashed()
    {
        return static::makeQueryBuilder()
            ->withoutTrashed();
    }

    protected static function makeQueryBuilder($appends = [])
    {
        $model = new static;
        $appends = array_merge($appends, [
            function (Builder $builder, $scoped = false) use ($model) {
                if (!$scoped && ((!property_exists($builder, 'appendSoftDeletesQuery')) || $builder->appendSoftDeletesQuery)) {
                    return $builder->where(function (Builder $query) use ($model) {
                        return $query->where($model->getDeletedAtColumn(), null)
                            ->orWhere($model->getDeletedAtColumn(), '>=', date('Y-m-d H:i:s', time()));
                    });
                }
            }
        ]);

        $builder = parent::makeQueryBuilder($appends);

        $builder->macro('withTrashed', function ($withTrashed = true) use ($model) {
            $this->appendSoftDeletesQuery = false;

            if (!$withTrashed) {
                return $this->withoutTrashed();
            }

            return $this->where(function (Builder $query) use ($model) {
                return $query->where($model->getDeletedAtColumn(), null)
                    ->orWhere($model->getDeletedAtColumn(), '<=', date('Y-m-d H:i:s', time()));
            });
        });

        $builder->macro('withoutTrashed', function () use ($model) {
            $this->appendSoftDeletesQuery = false;

            return $this->where(function (Builder $query) use ($model) {
                return $query->where($model->getDeletedAtColumn(), null)
                    ->orWhere($model->getDeletedAtColumn(), '>=', date('Y-m-d H:i:s', time()));
            });
        });

        $builder->macro('onlyTrashed', function () use ($model) {
            $this->appendSoftDeletesQuery = false;

            return $this->where(function (Builder $query) use ($model) {
                return $query->where($model->getDeletedAtColumn(), '!=', null)
                    ->andWhere($model->getDeletedAtColumn(), '<=', date('Y-m-d H:i:s', time()));
            });
        });

        return $builder;
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftDeletes()
    {
        /** @var QueryableModel $this */

        if (!isset($this->casts[$this->getDeletedAtColumn()])) {
            $this->casts[$this->getDeletedAtColumn()] = 'datetime';
        }
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        /** @var QueryableModel $this */

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
        /** @var QueryableModel $this */

        if ($this->forceDeleting) {
            $this->exists = false;

            return parent::performDeleteOnModel();
        }

        return $this->runSoftDelete();
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        /** @var QueryableModel $this */

        $this->attributes[$this->getDeletedAtColumn()] = $this->freshTimestamp();
        return (bool) $this->save();
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore()
    {
        /** @var QueryableModel $this */

        if ($this->exists) {
            // If the restoring event does not return false, we will proceed with this
            // restore operation. Otherwise, we bail out so the developer will stop
            // the restore totally. We will clear the deleted timestamp and save.
            if ($this->fireModelEvent('restoring') === false) {
                return false;
            }

            $this->attributes[$this->getDeletedAtColumn()] = '';

            // Once we have saved the model, we will fire the "restored" event so this
            // developer will do anything they need to after a restore operation is
            // totally finished. Then we will return the result of the save call.
            $this->exists = true;

            $result = $this->save();

            $this->fireModelEvent('restored', false);

            return $result;
        }

        return false;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        /** @var QueryableModel $this */
        return !!($this->attributes[$this->getDeletedAtColumn()] ?? false);
    }

    /**
     * Register a "restoring" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a "restored" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback)
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Register a "forceDeleted" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function forceDeleted($callback)
    {
        static::registerModelEvent('forceDeleted', $callback);
    }

    /**
     * Determine if the model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting()
    {
        /** @var QueryableModel $this */
        return $this->forceDeleting;
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        /** @var QueryableModel $this */
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }
}
