<?php

namespace Netflex\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static ?object first()
 * @method static \Illuminate\Support\Collection get()
 * @method static \Netflex\Query\Builder andWhere(string $field, string $operator, null|array|boolean|integer|string|\DateTime $value)
 * @method static \Netflex\Query\Builder field(string $field)
 * @method static \Netflex\Query\Builder fields(array $fields)
 * @method static \Netflex\Query\Builder ignorePublishingStatus()
 * @method static \Netflex\Query\Builder limit(int $limit)
 * @method static \Netflex\Query\Builder orWhere(string $field, string $operator, null|array|boolean|integer|string|\DateTime $value)
 * @method static \Netflex\Query\Builder orderBy(string $field, string $direction = null)
 * @method static \Netflex\Query\Builder orderDirection(string $direction)
 * @method static \Netflex\Query\Builder raw(string $query)
 * @method static \Netflex\Query\Builder relation(string $relation)
 * @method static \Netflex\Query\Builder relations(array $relations)
 * @method static \Netflex\Query\Builder respectPublishingStatus()
 * @method static \Netflex\Query\Builder where(string $field, string $operator, null|array|boolean|integer|string|\DateTime $value)
 * @method static \Netflex\Query\Builder whereNot(string $field, string $operator, null|array|boolean|integer|string|\DateTime $value)
 * @method static \Netflex\Query\Page paginate(int $size = 15, int $page = 1)
 * @method static int count()
 * @method static string getQuery(bool $scoped = false)
 * @method static string getRequest()
 * @see \Netflex\Query\Builder
 */
class QueryBuilderFacade extends Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
    return 'QueryBuilder';
  }
}
