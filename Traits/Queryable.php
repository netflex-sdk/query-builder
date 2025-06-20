<?php

namespace Netflex\Query\Traits;

use Closure;

use Netflex\Query\Builder;
use Netflex\Query\QueryableModel;

use Netflex\Query\Exceptions\QueryException;
use Netflex\Query\Exceptions\NotQueryableException;
use Illuminate\Contracts\Pagination\Paginator;

trait Queryable
{
  /**
   * Determines the default field to order the query by
   *
   * @var string
   */
  protected $defaultOrderByField;

  /**
   * Determines the default direction to order the query by
   *
   * @var string
   */
  protected $defaultSortDirection;

  /**
   * @param Closure[]
   * @return Builder
   * @throws NotQueryableException If object not queryable
   */
  protected static function makeQueryBuilder($appends = [])
  {
    /** @var QueryableModel $this */

    if (!has_trait(static::class, HasRelation::class)) {
      throw new NotQueryableException;
    }

    /** @var QueryableModel */
    $queryable = (new static);

    $respectPublishingStatus = $queryable->respectPublishingStatus();
    $relation = $queryable->getRelation();
    $relationId = $queryable->getRelationId();
    $hasMapper = method_exists($queryable, 'getMapper');
    $defaultOrderByField = $queryable->defaultOrderByField;
    $defaultSortDirection = $queryable->defaultSortDirection;
    $size = $queryable->perPage ?? null;

    $mapper = $hasMapper ? $queryable->getMapper() : function ($item) {
      return $item;
    };

    $builder = (new Builder($respectPublishingStatus, null, $mapper, $appends))
      ->relation($relation, $relationId)
      ->assoc($hasMapper);

    $builder->setModel(static::class);

    if ($queryable instanceof QueryableModel) {
      $builder->setConnectionName($queryable->getConnectionName());
    }

    if ($size) {
      $minSize = Builder::MIN_QUERY_SIZE;
      $maxSize = Builder::MAX_QUERY_SIZE;

      $size = $size < 0 ? ($maxSize + ($size + 1)) : $size;
      $size = $size > $maxSize ? $maxSize : $size;
      $size = $size < $minSize ? $minSize : $size;

      $builder->limit($size);
    }

    if ($defaultOrderByField) {
      $builder->orderBy($defaultOrderByField);
    }

    if ($defaultSortDirection) {
      $builder->orderDirection($defaultSortDirection);
    }

    return $builder;
  }

  /**
   * Determine if this object respects publishing statuses when performing queries
   *
   * @return bool
   */
  public function respectPublishingStatus()
  {
    if ($this instanceof QueryableModel && !static::$publishingStatusChecksTemporarilyDisabled) {
      /** @var QueryableModel $this */
      return $this->respectPublishingStatus ?? true;
    }

    return false;
  }

  /**
   * Override the publishing status for the model
   *
   * @param bool $disregarding
   * @return Builder
   */
  public static function disregardingPublishingStatus($disregarding = true)
  {
    return static::makeQueryBuilder()
      ->respectPublishingStatus(!$disregarding);
  }

  /**
   * Adds a field that should be retrieved
   *
   * @param string $field
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::field
   */
  public static function field(...$args)
  {
    return static::makeQueryBuilder()->field(...$args);
  }

  /**
   * Sets which fields to retrieve (default: All fields)
   *
   * @param array $fields
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::fields
   */
  public static function fields(...$args)
  {
    return static::makeQueryBuilder()->fields(...$args);
  }

  /**
   * Limits the results to $limit amount of hits
   *
   * @param int $limit
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::limit
   */
  public static function limit(...$args)
  {
    return static::makeQueryBuilder()->limit(...$args);
  }

  /**
   * Sets the field which to order the results by
   *
   * @param string $field
   * @param string $direction
   * @return Builder
   * @throws InvalidSortingDirectionException If an invalid $direction is passed
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::orderBy
   */
  public static function orderBy(...$args)
  {
    return static::makeQueryBuilder()->orderBy(...$args);
  }

  /**
   * Performs a raw query, use carefully.
   *
   * @param string $query
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::raw
   */
  public static function raw(...$args)
  {
    return static::makeQueryBuilder()->raw(...$args);
  }

  /**
   * Performs a 'publishedAt' query
   *
   * @param string|DateTimeInterface|null $date
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::publishedAt
   */
  public static function publishedAt(...$args)
  {
    return static::makeQueryBuilder()->publishedAt(...$args);
  }

  /**
   * Performs a 'where' query
   *
   * If a closure is passed as the only argument, a new query scope will be created.
   * If $value is omitted, $operator is used as the $value, and the $operator will be set to '='.
   *
   * @param Closure|string $field
   * @param string $operator
   * @param null|array|boolean|integer|string|DateTimeInterface $value
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::where
   */
  public static function where(...$args)
  {
    return static::makeQueryBuilder()->where(...$args);
  }

  /**
   * Queries where field exists in the values
   *
   * @param string $field
   * @param array $values
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::whereIn
   */
  public static function whereIn(...$args)
  {
    return static::makeQueryBuilder()->whereIn(...$args);
  }

  /**
   * Performs a 'whereNot' query
   *
   * If a closure is passed as the only argument, a new query scope will be created.
   * If $value is omitted, $operator is used as the $value, and the $operator will be set to '='.
   *
   * @param Closure|string $field
   * @param string $operator
   * @param null|array|boolean|integer|string|DateTimeInterface $value
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::whereNot
   */
  public static function whereNot(...$args)
  {
    return static::makeQueryBuilder()->whereNot(...$args);
  }

  /**
   * Queries where field is between $from and $to
   *
   * @param string $field
   * @param @param null|array|boolean|integer|string|DateTimeInterface $from
   * @param @param null|array|boolean|integer|string|DateTimeInterface $to
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::whereBetween
   */
  public static function whereBetween(...$args)
  {
    return static::makeQueryBuilder()->whereBetween(...$args);
  }

  /**
   * Queries where field is not between $from and $to
   *
   * @param string $field
   * @param @param null|array|boolean|integer|string|DateTimeInterface $from
   * @param @param null|array|boolean|integer|string|DateTimeInterface $to
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::whereNotBetween
   */
  public static function whereNotBetween(...$args)
  {
    return static::makeQueryBuilder()->whereNotBetween(...$args);
  }

  /**
   * Creates a paginated result
   *
   * @param int $size
   * @param int $page
   * @return Paginator
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   * @see \Netflex\Query\Builder::paginate
   */
  public static function paginate(...$args)
  {
    $args[0] = $args[0] ?? (new static)->perPage ?? 100;
    $maxSize = Builder::MAX_QUERY_SIZE;
    $args[0] = $args[0] < 0 ? ($maxSize + ($args[0] + 1)) : $args[0];
    return static::makeQueryBuilder()->paginate(...$args);
  }

  /**
   * Cache the results with the given key if $shouldCache is true
   *
   * @param string $key
   * @param bool $shouldCache
   * @return Builder
   * @see \Netflex\Query\Builder::cacheResultsWithKey
   */
  public static function maybeCacheResults($key, $shouldCache)
  {
    if (!static::$cachingTemporarilyDisabled) {
      if ($shouldCache) {
        return static::cacheResults($key);
      }
    }

    return static::makeQueryBuilder();
  }

  /**
   * Cache the results with the given key
   *
   * @param string $key
   * @return Builder
   * @see \Netflex\Query\Builder::cacheResultsWithKey
   */
  public static function cacheResults($key)
  {
    return static::makeQueryBuilder()->cacheResultsWithKey($key);
  }

  /**
   * Get the count of items matching the current query
   *
   * @return int
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   * @see \Netflex\Query\Builder::count
   */
  public static function count(...$args)
  {
    return static::makeQueryBuilder()->count(...$args);
  }

  /**
   * Picks random items
   *
   * @param int $amount
   * @return static|Collection
   */
  public static function random(int $amount = 1)
  {
    $result = static::makeQueryBuilder()->random($amount);

    return $amount === 1 ? $result->first() : $result;
  }

  /**
   * @param string $query
   * @return Builder
   * @throws NotQueryableException
   */
  public static function query($query = '*')
  {
    return static::raw($query);
  }

  /**
   * @param boolean|Closure $clause
   * @param Closure $then
   * @param null|Closure $else
   * @return Builder
   * @throws NotQueryableException If object not queryable
   * @see \Netflex\Query\Builder::if
   */
  public static function if(...$args)
  {
    return static::makeQueryBuilder()->if(...$args);
  }
}
