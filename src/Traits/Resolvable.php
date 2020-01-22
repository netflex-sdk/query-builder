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


trait Resolvable
{
  protected function getPrimaryField()
  {
    return $this->primaryField ?? 'id';
  }

  protected function getResolvableField()
  {
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

    throw new NotFoundException;
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
   * @return Collection|LazyCollection
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function all()
  {
    $perPage = (new static)->perPage ?? 15;

    if (static::count() <= $perPage) {
      return Collection::make(static::raw('*')->get());
    }

    return LazyCollection::make(function () use ($perPage) {
      $page = static::paginate($perPage);

      while ($page && ($item = $page->first()) !== null) {
        yield $item;
        $page = $page->next();
      }
    });
  }

  /**
   * Resolves an instance
   *
   * @param mixed $resolveBy
   * @return static|Collection|null
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function resolve($resolveBy)
  {
    return static::resolvableContext(function ($resolvable) use ($resolveBy) {
      $query = static::where($resolvable->getResolvableField(), Builder::OP_EQ, $resolveBy);
      return is_array($resolveBy) ? $query->get() : $query->first();
    });
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
   * @param mixed|array $findBy
   * @return static|Collection|null
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function find($findBy)
  {
    return static::resolvableContext(function ($resolvable) use ($findBy) {
      $query = static::where($resolvable->getPrimaryField(), Builder::OP_EQ, $findBy);

      $query->limit = 1;

      if (is_array($findBy)) {
        $query->limit(count($findBy));
      }

      return is_array($findBy) ? $query->get() : $query->first();
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

    throw new NotFoundException;
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
