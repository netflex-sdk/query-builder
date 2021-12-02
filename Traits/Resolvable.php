<?php

namespace Netflex\Query\Traits;

use Closure;

use Netflex\Query\Builder;
use Netflex\Query\Traits\Queryable;

use Netflex\Query\Exceptions\QueryException;
use Netflex\Query\Exceptions\NotFoundException;
use Netflex\Query\Exceptions\NotQueryableException;
use Netflex\Query\Exceptions\ResolutionFailedException;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Netflex\Query\PaginatedResult;

trait Resolvable
{
  protected function getPrimaryField()
  {
    /** @var QueryableModel $this */
    return $this->primaryField ?? 'id';
  }

  protected function getResolvableField()
  {
    /** @var QueryableModel $this */
    return $this->resolvableField ?? 'url';
  }

  /**
   * Exectures the context if object can be resolved
   *
   * @param Closure $context
   * @return mixed
   * @throws NotQueryableException If object not queryable
   */
  protected static function resolvableContext(Closure $context)
  {
    if (has_trait(static::class, Queryable::class)) {
      return $context(new static);
    }

    throw new NotQueryableException;
  }

  /**
   * Retrieves the first instance
   *
   * @return static|null
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function first()
  {
    return static::resolvableContext(function ($resolvable) {
      return static::orderBy($resolvable->getPrimaryField(), Builder::DIR_ASC)
        ->first();
    });
  }

  /**
   * Retrieves the first instance or fails
   *
   * @return static
   * @throws NotFoundException If not found
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function firstOrFail()
  {
    if ($first = static::first()) {
      return $first;
    }

    $e = new NotFoundException;
    $e->setModel(static::class);

    throw $e;
  }

  /**
   * Retrieves the latest instance
   *
   * @return static|null
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function last()
  {
    return static::resolvableContext(function ($resolvable) {
      return static::orderBy($resolvable->getPrimaryField(), Builder::DIR_DESC)
        ->first();
    });
  }

  /**
   * Retrieves all instances
   *
   * @return Collection|LazyCollection Returns LazyCollection if chunking is enabled on the model.
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function all()
  {
    return static::resolvableContext(function ($resolvable) {
      if ($resolvable->useChunking) {
        return static::chunked();
      }

      return static::maybeCacheResults(
        $resolvable->getAllCacheIdentifier(),
        $resolvable->cachesResults
      )
        ->raw('*')
        ->get();
    });
  }

  public static function chunked($size = null)
  {
    return static::resolvableContext(function ($resolvable) use ($size) {
      $size = $size ?? $resolvable->perPage ?? 100;
      return LazyCollection::make(function () use ($resolvable, $size) {
        /** @var PaginatedResult $page */
        $chunk = $resolvable::paginate($size);
        foreach ($chunk->all() as $item) {
          yield $item;
        }
        while ($chunk->hasMorePages()) {
          /** @var PaginatedResult $page */
          $chunk = $resolvable::paginate($size, $chunk->currentPage() + 1);
          foreach ($chunk->all() as $item) {
            yield $item;
          }
        }
      });
    });
  }

  /**
   * Resolves an instance
   *
   * @param mixed $resolveBy
   * @param  string|null $field
   * @return static|Collection|null
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function resolve($resolveBy, $field = null)
  {
    try {
      return static::resolvableContext(function ($resolvable) use ($resolveBy, $field) {
        $query = static::where($field ?? $resolvable->getResolvableField(), Builder::OP_EQ, $resolveBy);
        return is_array($resolveBy) ? $query->get() : $query->first();
      });
    } catch (ResolutionFailedException $e) {
      return null;
    }
  }

  /**
   * Resolves an instance or throws an exception
   *
   * @param mixed $resolveBy
   * @return static|Collection
   * @throws ResolutionFailedException If the instance(s) could not be resolved
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function resolveOrFail($resolveBy)
  {
    if ($resolved = static::resolve($resolveBy)) {
      return $resolved;
    }

    throw new ResolutionFailedException;
  }

  /**
   * Resolves multiple instances by their primary fields
   *
   * @param array $findBy
   * @return Collection
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function resolveMany(array $resolveBy)
  {
    return static::resolve($resolveBy);
  }

  /**
   * Finds an instance by its primary field
   *
   * @param mixed|array|Collection $findBy
   * @return static|Collection|null
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function find($findBy)
  {
    if (is_array($findBy) || $findBy instanceof Collection) {
      return Collection::make($findBy)->flatten()->map(function ($findBy) {
        return static::find($findBy);
      });
    }

    return static::resolvableContext(function ($resolvable) use ($findBy) {
      return static::maybeCacheResults(
        $resolvable->getCacheIdentifier($findBy),
        $resolvable->cachesResults
      )->where($resolvable->getPrimaryField(), Builder::OP_EQ, $findBy)
        ->limit(1)
        ->first();
    });
  }

  /**
   * Finds an instance by its primary field or throws an exception
   *
   * @param mixed|array $findBy
   * @return static|Collection
   * @throws NotFoundException If the instance(s) could not be found
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function findOrFail($findBy)
  {
    if ($found = static::find($findBy)) {
      return $found;
    }

    $e = new NotFoundException;
    $e->setModel(static::class, collect($findBy)->flatten()->toArray());

    throw $e;
  }

  /**
   * Finds multiple instances by their primary fields
   *
   * @param array $findBy
   * @return Collection
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function findMany(array $findBy)
  {
    return static::find($findBy);
  }
}
