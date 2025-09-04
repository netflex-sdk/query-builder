<?php

namespace Netflex\Query\Facades;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use Netflex\Query\Builder;
use Netflex\Query\QueryableModel;

/**
 * @method static QueryableModel|null first()
 * @method static Builder connection(string $connection)
 * @method static Builder setConnectionName(string $connection)
 * @method static string getConnectionName()
 * @method static Builder append(callable $callback)
 * @method static Builder setModel(string $model)
 * @method static string getModel()
 * @method static Builder cacheResultsWithKey(string $key)
 * @method static Builder score(float $score)
 * @method static Builder fuzzy(int|null $distance = null)
 * @method static Builder debug()
 * @method static string getQuery(bool $scoped = false)
 * @method static string getRequest()
 * @method static Builder raw(string $query)
 * @method static Builder orderBy(string $field, string $direction = null)
 * @method static Builder orderDirection(string $direction)
 * @method static Builder relation(string $relation)
 * @method static Builder relations(array $relations)
 * @method static Builder limit(int $limit)
 * @method static Builder fields(array $fields)
 * @method static Builder field(string $field)
 * @method static Builder includeScores()
 * @method static Builder where(string $field, string $operator, null|array|boolean|integer|string|\DateTime $value)
 * @method static Builder whereIn(string $field, array $values)
 * @method static Builder whereBetween(string $field, null|array|boolean|integer|string|\DateTime $from, null|array|boolean|integer|string|\DateTime $to)
 * @method static Builder whereNotBetween(string $field, null|array|boolean|integer|string|\DateTime $from, null|array|boolean|integer|string|\DateTime $to)
 * @method static Builder whereNot(string $field, string $operator, null|array|boolean|integer|string|\DateTime $value)
 * @method static Builder orWhere(string $field, string $operator, null|array|boolean|integer|string|\DateTime $value)
 * @method static Builder andWhere(string $field, string $operator, null|array|boolean|integer|string|\DateTime $value)
 * @method static Paginator paginate(int $size = 100, int $page = 1)
 * @method static Builder assoc(bool $assoc)
 * @method static object fetch(int|null $size = null, int|null $page = null)
 * @method static callable|null getMapper()
 * @method static Builder setMapper(callable $mapper)
 * @method static Builder ignorePublishingStatus()
 * @method static Builder respectPublishingStatus()
 * @method static Builder if(mixed $clause, callable $then, callable|null $else = null)
 * @method static int size()
 *
 * @see Builder
 */
class QueryBuilder extends Facade
{
  protected static function getFacadeAccessor(): string
  {
    return Builder::class;
  }
}
