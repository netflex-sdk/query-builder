<?php

namespace Netflex\Query\Traits;

use Netflex\Contracts\ApiClient;

use Netflex\Query\Builder;
use Netflex\Query\PaginatedResult;
use Netflex\Query\Traits\HasRelation;

use Netflex\Query\Exceptions\QueryException;
use Netflex\Query\Exceptions\NotQueryableException;

trait Queryable
{
  /**
   * @return Builder
   * @throws NotQueryableException If object not queryable
   */
  protected static function makeQueryBuilder()
  {
    if (!has_trait(static::class, HasRelation::class)) {
      throw new NotQueryableException;
    }

    $queryable = (new static);

    $respectPublishingStatus = $queryable->respectPublishingStatus();
    $relation = $queryable->getRelation();
    $relationId = $queryable->getRelationId();
    $hasMapper = method_exists($queryable, 'getMapper');
    $mapper = $hasMapper ? $queryable->getMapper() : function ($item) { return $item; };

    return (new Builder($respectPublishingStatus, null, $mapper))
      ->relation($relation, $relationId)
      ->assoc($hasMapper);
  }

  /**
   * Determine if this object respects publishing statuses when performing queries
   *
   * @return bool
   */
  public function respectPublishingStatus()
  {
    return $this->respectPublishingStatus ?? true;
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
   * Performs a 'where' query
   *
   * If a closure is passed as the only argument, a new query scope will be created.
   * If $value is omitted, $operator is used as the $value, and the $operator will be set to '='.
   *
   * @param Closure|string $field
   * @param string $operator
   * @param null|array|boolean|integer|string|DateTime $value
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
   * @param null|array|boolean|integer|string|DateTime $value
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
   * @param @param null|array|boolean|integer|string|DateTime $from
   * @param @param null|array|boolean|integer|string|DateTime $to
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
   * @param @param null|array|boolean|integer|string|DateTime $from
   * @param @param null|array|boolean|integer|string|DateTime $to
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
   * @return PaginatedResult
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   * @see \Netflex\Query\Builder::paginate
   */
  public static function paginate(...$args)
  {
    $args[0] = $args[0] ?? (new static)->perPage ?? 15;
    return static::makeQueryBuilder()->paginate(...$args);
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
}
