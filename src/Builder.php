<?php

namespace Netflex\Query;

use Closure;
use DateTime;

use GuzzleHttp\Exception\BadResponseException;

use Netflex\API\Facades\API;

use Netflex\Query\Exceptions\QueryException;
use Netflex\Query\Exceptions\IndexNotFoundException;
use Netflex\Query\Exceptions\InvalidAssignmentException;
use Netflex\Query\Exceptions\InvalidOperatorException;
use Netflex\Query\Exceptions\InvalidSortingDirectionException;
use Netflex\Query\Exceptions\InvalidValueException;
use Netflex\Query\Exceptions\NotFoundException;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Traits\Macroable;

class Builder
{
  use Macroable;

  /** @var int The minimum allowed results per query */
  const MIN_QUERY_SIZE = 1;

  /** @var int The maximum allowed results per query */
  const MAX_QUERY_SIZE = 10000;

  /** @var array Special characters that must be escaped */
  const SPECIAL_CHARS = ['"', '\\'];

  /** @var string The ascending sort direction */
  const DIR_ASC = 'asc';

  /** @var string The decending sort direction */
  const DIR_DESC = 'desc';

  /** @var array The supported value types */
  const VALUE_TYPES = [
    'NULL',
    'array',
    'boolean',
    'integer',
    'string',
    'DateTime'
  ];

  const REPLACEMENT_ENTITIES = [
    '-' => '##D##'
  ];

  /** @var array The valid sorting directions */
  const SORTING_DIRS = [
    Builder::DIR_ASC,
    Builder::DIR_DESC,
  ];

  /** @var string The equals operator */
  const OP_EQ = '=';

  /** @var string The not equals operator */
  const OP_NEQ = '!=';

  /** @var string The less than operator */
  const OP_LT = '<';

  /** @var string The less than or equals operator */
  const OP_LTE = '<=';

  /** @var string The greater than operator */
  const OP_GT = '>';

  /** @var string The greater than or equals operator */
  const OP_GTE = '>=';

  /** @var string The like operator */
  const OP_LIKE = 'like';

  /** @var array The valid operators */
  const OPERATORS = [
    Builder::OP_EQ,
    Builder::OP_NEQ,
    Builder::OP_LT,
    Builder::OP_LTE,
    Builder::OP_GT,
    Builder::OP_GTE,
    Builder::OP_LIKE
  ];

  /** @var array */
  protected $fields;

  /** @var array */
  protected $relations;

  /** @var int */
  protected $relation_id;

  /** @var int */
  protected $size = self::MAX_QUERY_SIZE;

  /** @var string */
  protected $orderBy;

  /** @var string */
  protected $sortDir;

  /** @var array */
  protected $query;

  /** @var bool */
  protected $respectPublishingStatus = true;

  /** @var Closure */
  protected $mapper;

  /** @var bool */
  protected $assoc = false;

  /** @var bool */
  protected $shouldCache = false;

  /** @var string */
  protected $cacheKey;

  /** @var bool */
  protected $debug = false;

  /** @var callable[] */
  protected $appends = [];

  /**
   * @param bool $respectPublishingStatus
   * @param array $query
   */
  public function __construct(?bool $respectPublishingStatus = true, ?array $query = null, ?Closure $mapper = null, $appends = [])
  {
    $this->query = $query ?? [];
    $this->mapper = $mapper;
    $this->respectPublishingStatus = $respectPublishingStatus ?? true;
    $this->appends = $appends;
  }

  /**
   * Cache the results with the given key
   *
   * @param string $key
   * @return static
   */
  public function cacheResultsWithKey($key)
  {
    $this->shouldCache = true;
    $this->cacheKey = $key;

    return $this;
  }

  /**
   * @param string $relation
   * @return bool
   */
  protected function hasRelation($relation)
  {
    return in_array(Str::singular($relation), $this->relations ?? []);
  }

  /**
   * @param null|array|boolean|integer|string|DateTime $value
   * @param string $operator
   * @return mixed
   */
  protected function escapeValue($value, $operator = null)
  {
    if (is_string($value)) {
      if ($operator !== 'like') {
        return "\"{$value}\"";
      }

      return str_replace(' ', '*', $value);
    }

    if (is_bool($value)) {
      $value = (int) $value;
    }

    if (is_object($value)) {
      if ($value instanceof DateTime) {
        return $this->escapeValue($value->format('Y-m-d h:i:s'), $operator);
      }
    }

    return $value;
  }

  /**
   * @param string $field
   * @param null|array|boolean|integer|string|DateTime $value
   * @return string
   */
  protected function compileTermQuery(string $field, $value)
  {
    return "${field}:$value";
  }

  /**
   * @param string $field
   * @return string
   */
  protected function compileNullQuery($field)
  {
    return "(NOT _exists_:{$field})";
  }

  /**
   * @param array $args
   * @param string $operator
   * @return string
   */
  protected function compileScopedQuery(array $args, string $operator = 'AND')
  {
    $callback = count($args) === 1 ? array_pop($args) : function (self $scope) use ($args) {
      return $scope->where(...$args);
    };

    $builder = new static(false, []);

    $scopedQuery  = (function ($builder, $callback) {
      $callback($builder);
      return $builder->getQuery(true);
    })($builder, $callback);

    $compiledQuery = $this->compileQuery(true);

    if ($operator) {
      $compiledQuery = $compiledQuery ? "($compiledQuery)" : $compiledQuery;
      $scopedQuery = $compiledQuery ? "($scopedQuery)" : $scopedQuery;
      $operator = ($compiledQuery && $scopedQuery && $operator) ? " $operator " : null;
    }

    return "{$compiledQuery}{$operator}$scopedQuery";
  }

  /**
   * Compiles the field name into something ES can understand
   *
   * @param string $field
   * @return string
   */
  protected function compileField($field)
  {
    foreach (static::REPLACEMENT_ENTITIES as $entity => $replacement) {
      $field = str_replace($entity, $replacement, $field);
    }

    return $field;
  }

  /**
   * @param string $field
   * @param string $operator|
   * @param null|array|Collection|boolean|integer|QueryableModel|string|DateTime $value
   * @return string
   * @throws InvalidOperatorException If an invalid operator is passed
   */
  protected function compileWhereQuery($field, $operator, $value)
  {
    $field = $this->compileField($field);

    if (is_object($value) && $value instanceof Collection) {
      /** @var Collection */
      $value = $value;
      $value = $value->toArray();
    }

    if (is_object($value) && $value instanceof QueryableModel) {
      /** @var QueryableModel */
      $value = $value;
      $value = $value->getKey();
    }

    if (is_object($value) && method_exists($value, '__toString')) {
      /** @var object */
      $value = $value;
      $value = $value->__toString();
    }

    if (!in_array(gettype($value), static::VALUE_TYPES) || (is_object($value) && !in_array(get_class($value), static::VALUE_TYPES))) {
      throw new InvalidValueException($value);
    }

    if (is_array($value)) {
      $queries = [];
      foreach ($value as $item) {
        $queries[] = $this->compileWhereQuery($field, $operator, $item);
      }

      return '(' . implode(' OR ', $queries) . ')';
    }

    $value = $this->escapeValue($value, $operator);
    $term = $value === null ? $this->compileNullQuery($field) : $this->compileTermQuery($field, $value);

    switch ($operator) {
      case static::OP_EQ:
        return $term;
      case static::OP_NEQ:
        return "NOT $term";
      case static::OP_GT:
        if ($value === null) {
          return null;
        }

        if (is_string($value)) {
          return "($field:[$value TO *] AND (NOT $value))";
        }

        return "$field:>$value";
      case static::OP_GTE:
        if ($value === null) {
          $this->query = [$this->compileWhereQuery($field, '!=', null)];
          return null;
        }

        if (is_string($value)) {
          return "$field:[$value TO *]";
        }

        return "$field:>=$value";
      case static::OP_LT:
        if ($value === null) {
          return null;
        }

        if (is_string($value)) {
          return "($field:[* TO $value] AND (NOT $value))";
        }

        return "$field:<$value";
      case static::OP_LTE:
        if ($value === null) {
          $this->query = [$this->compileWhereQuery($field, '=', null)];
          return null;
        }

        if (is_string($value)) {
          return "$field:[* TO $value]";
        }

        return "$field:<=$value";
      case static::OP_LIKE:
        return $term;
      default:
        throw new InvalidOperatorException($operator);
        break;
    }
  }

  /**
   * Sets the debug flag of the query
   * Making the API reflect the compiled query in the output
   * 
   * @return static
   */
  public function debug()
  {
    $this->debug = true;
    return $this;
  }

  /**
   * Compiles the query and retrieves the query string.
   * Used for debugging purposes.
   *
   * @param bool $scoped Determines if the query shouls be compiled in a scoped context.
   * @return string
   */
  public function getQuery($scoped = false)
  {
    return $this->compileQuery($scoped);
  }

  /**
   * Compiles the query and retrieves the query string.
   * Used for debugging purposes.
   *
   * @param bool $scoped Determines if the query shouls be compiled in a scoped context.
   * @return string
   */
  public function getRequest()
  {
    return $this->compileRequest();
  }

  /**
   * Performs a raw query, use carefully.
   *
   * @param string $query
   * @return static
   */
  public function raw(string $query)
  {
    $this->query[] = $query;
    return $this;
  }

  /**
   * Sets the field which to order the results by
   *
   * @param string $field
   * @param string $direction
   * @return static
   * @throws InvalidSortingDirectionException If an invalid $direction is passed
   */
  public function orderBy($field, $direction = null)
  {
    $this->orderBy = $this->compileField($field);

    if ($direction) {
      $this->orderDirection($direction);
    }

    return $this;
  }

  /**
   * Sets the direction to order the results by
   *
   * @param string $direction
   * @return static
   * @throws InvalidSortingDirectionException If an invalid $direction is passed
   */
  public function orderDirection($direction)
  {
    if (!in_array($direction, static::SORTING_DIRS)) {
      throw new InvalidSortingDirectionException($direction);
    }

    $this->sortDir = $direction;
    return $this;
  }

  /**
   * Sets the relation for the query
   *
   * @param string $relation
   * @param int $relation_id
   * @return static
   */
  public function relation(?string $relation, ?int $relation_id = null)
  {
    if ($relation) {
      $this->relations = $this->relations ?? [];
      $this->relations[] = Str::singular($relation);
      $this->relations = array_filter(array_unique($this->relations));
    }

    if ($relation_id) {
      $this->relation_id = $relation_id;
    }

    return $this;
  }

  public function relations(array $relations)
  {
    foreach ($relations as $relation) {
      $this->relation($relation);
    }

    return $this;
  }

  /**
   * Limits the results to $limit amount of hits
   *
   * @param int $limit
   * @return static
   */
  public function limit(int $limit)
  {
    $limit = $limit > static::MAX_QUERY_SIZE ? static::MAX_QUERY_SIZE : $limit;
    $limit = $limit < static::MIN_QUERY_SIZE ? static::MIN_QUERY_SIZE : $limit;
    $this->size = $limit;
    return $this;
  }

  /**
   * Sets which fields to retrieve (default: All fields)
   *
   * @param array $fields
   * @return static
   */
  public function fields(array $fields)
  {
    foreach ($fields as $field) {
      $this->field($field);
    }

    return $this;
  }

  /**
   * Adds a field that should be retrieved
   *
   * @param string $field
   * @return static
   */
  public function field(string $field)
  {
    $this->fields = $this->fields ?? [];
    $this->fields[] = $this->compileField($field);
    $this->fields = array_filter(array_unique($this->fields));
    return $this;
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
   * @return static
   */
  public function where(...$args)
  {
    if (count($args) === 1) {
      $this->query = [$this->compileScopedQuery([array_pop($args)])];
      return $this;
    }

    $field = $args[0] ?? null;
    $operator = $args[1] ?? null;
    $value = $args[2] ?? null;

    if (!array_key_exists(2, $args)) {
      $value = $args[1] ?? null;
      $operator = static::OP_EQ;
    }

    $this->query[] = $this->compileWhereQuery($field, $operator, $value);

    return $this;
  }

  /**
   * Queries where field exists in the values
   *
   * @param string $field
   * @param array $values
   * @return static
   */
  public function whereIn(string $field, array $values)
  {
    return $this->where($field, '=', $values);
  }

  /**
   * Queries where field is between $from and $to
   *
   * @param string $field
   * @param null|array|boolean|integer|string|DateTime $from
   * @param null|array|boolean|integer|string|DateTime $to
   * @return static
   */
  public function whereBetween(string $field, $from, $to)
  {
    $field = $this->compileField($field);
    $from = $this->escapeValue($from, '=');
    $to = $this->escapeValue($to, '=');
    $this->query[] =  "($field:[$from TO $to])";
    return $this;
  }

  /**
   * Queries where field is not between $from and $to
   *
   * @param string $field
   * @param null|array|boolean|integer|string|DateTime $from
   * @param null|array|boolean|integer|string|DateTime $to
   * @return static
   */
  public function whereNotBetween(string $field, $from, $to)
  {
    $field = $this->compileField($field);
    $from = $this->escapeValue($from, '=');
    $to = $this->escapeValue($to, '=');
    $this->query[] =  "(NOT ($field:[$from TO $to]))";
    return $this;
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
   * @return static
   */
  public function whereNot(...$args)
  {
    if (count($args) === 1) {
      $this->query =  ['NOT ' . $this->compileScopedQuery([array_pop($args)])];
      return $this;
    }

    $field = $args[0] ?? null;
    $operator = $args[1] ?? null;
    $value = $args[2] ?? null;

    if (!array_key_exists(2, $args)) {
      $value = $args[1] ?? null;
      $operator = static::OP_EQ;
    }

    $this->query[] = '(NOT ' . $this->compileWhereQuery($field, $operator, $value) . ')';

    return $this;
  }

  /**
   * Performs a 'orWhere' query
   *
   * If a closure is passed as the only argument, a new query scope will be created.
   * If $value is omitted, $operator is used as the $value, and the $operator will be set to '='.
   *
   * @param Closure|string $field
   * @param string $operator
   * @param null|array|boolean|integer|string|DateTime $value
   * @return static
   * @throws InvalidAssignmentException If left hand side of query is not set
   */
  public function orWhere(...$args)
  {
    if (!$this->query || !count($this->query)) {
      throw new InvalidAssignmentException('orWhere');
    }

    $this->query = [$this->compileScopedQuery($args, 'OR')];
    return $this;
  }

  /**
   * Performs a 'andWhere' query
   *
   * If a closure is passed as the only argument, a new query scope will be created.
   * If $value is omitted, $operator is used as the $value, and the $operator will be set to '='.
   *
   * @param Closure|string $field
   * @param string $operator
   * @param null|array|boolean|integer|string|DateTime $value
   * @return static
   * @throws InvalidAssignmentException If left hand side of query is not set
   */
  public function andWhere(...$args)
  {
    if (!$this->query || !count($this->query)) {
      throw new InvalidAssignmentException('andWhere');
    }

    $this->query = [$this->compileScopedQuery($args, 'AND')];
    return $this;
  }

  /**
   * Creates a paginated result
   *
   * @param int $size
   * @param int $page
   * @return PaginatedResult
   * @throws QueryException
   */
  public function paginate($size = 100, $page = 1)
  {
    return new PaginatedResult($this, (object) $this->fetch($size, $page), $this->mapper);
  }

  /**
   * Determines if we should return values as array or object
   *
   * @param bool $assoc
   * @return static
   */
  public function assoc(bool $assoc)
  {
    $this->assoc = $assoc;
    return $this;
  }

  /**
   * Retrieves the raw query result from the API
   *
   * @param int $page
   * @param int $size
   * @return object
   * @throws IndexNotFoundException|QueryException
   */
  protected function fetch($size = null, $page = null)
  {
    try {
      $fetch = function () use ($size, $page) {
        return API::get($this->compileRequest($size, $page), $this->assoc);
      };

      if ($this->shouldCache) {
        if (Facade::getFacadeApplication() && Facade::getFacadeApplication()->has('cache')) {
          return Cache::rememberForever($this->cacheKey, $fetch);
        }
      }

      return $fetch();
    } catch (BadResponseException $e) {
      $response = $e->getResponse();
      $index = $this->relations ? implode(',', $this->relations) : null;
      $index .= $this->relation_id ? ('_' . $this->relation_id) : null;

      if ($response->getStatusCode() === 500) {
        throw new IndexNotFoundException($index);
      }

      $error = json_decode($e->getResponse()->getBody());

      throw new QueryException($this->getQuery(true), $error);
    }
  }

  /**
   * Retrieves the results of the query
   *
   * @return \Illuminate\Support\Collection
   * @throws QueryException
   */
  public function get()
  {
    $result = $this->fetch();
    $hits = new Collection(($this->assoc ? $result['data'] : $result->data) ?? []);

    if ($this->mapper) {
      return $hits->map($this->mapper);
    }

    return $hits;
  }

  /**
   * Retrieves the first result
   *
   * @return object|null
   * @throws QueryException
   */
  public function first()
  {
    $size = $this->size;
    $this->size = 1;
    $first = $this->get()->first();
    $this->size = $size;

    return $first;
  }

  /**
   * Retrieves the first result
   *
   * @return object|null
   * @throws NotFoundException
   * @throws QueryException
   */
  public function firstOrFail()
  {
    if ($model = $this->first()) {
      return $model;
    }

    throw new NotFoundException;
  }

  /**
   * Retrives all results for the given query, ignoring the query limit
   * @return Collection
   */
  public function all()
  {
    $size = $this->size;
    $this->size = static::MAX_QUERY_SIZE;
    $results = $this->get();
    $this->size = $size;

    return $results;
  }

  /**
   * Returns random results for the given query
   * @param int|null $amount If not provided, will use the current query limit
   * @return Collection
   * @throws QueryException
   */
  public function random($amount = null)
  {
    if ($amount) {
      $this->limit($amount);
    }

    $size = $this->size;
    $fields = $this->fields;
    $query = $this->query;

    $this->size = $amount;
    $this->fields = ['id'];

    $result = array_map(function ($result) {
      return $result['id'];
    }, $this->fetch()['data']);

    $random = [];

    if (count($result)) {
      $amount = min(($amount ?? count($result)), count($result));
      $keys = array_rand($result, $amount);
      $keys = !is_array($keys) ? [$keys] : $keys;
      $keys = array_values($keys);

      foreach ($keys as $key) {
        $random[] = $result[$key];
      }
    }

    $this->fields = $fields;
    $this->query = [];

    $orderBy = $this->orderBy;
    $this->orderBy = null;

    $result = $this->where('id', $random)->get();

    $this->query = $query;
    $this->size = $size;
    $this->orderBy = $orderBy;

    return $result->shuffle()->values();
  }
  /**
   * Also include unpublished results
   * Only applies to entry and page relations
   *
   * @return static
   */
  public function ignorePublishingStatus()
  {
    $this->respectPublishingStatus = false;
    return $this;
  }

  /**
   * Only include published results
   * Only applies to entry and page relations
   * 
   * @param bool
   *
   * @return static
   */
  public function respectPublishingStatus($respect = true)
  {
    $this->respectPublishingStatus = $respect;
    return $this;
  }

  /**
   * Get the count of items matching the current query
   *
   * @return int
   * @throws QueryException
   */
  public function count()
  {
    $size = $this->size;
    $fields = $this->fields;
    $this->size = static::MAX_QUERY_SIZE;
    $this->fields = ['id'];
    $count = $this->get()->count();
    $this->size = $size;
    $this->fields = $fields;

    return $count;
  }

  /**
   * @param bool $scoped
   * @return string
   */
  protected function compileQuery($scoped = false)
  {
    foreach ($this->appends as $append) {
      $append($this, $scoped);
    }

    $query = $this->query;
    $compiledQuery =  implode(' AND ', array_filter(array_map(function ($term) {
      return trim($term) === '()' ? null : $term;
    }, $query)));

    $respectPublishedQuery = null;

    if (!$scoped && ($this->respectPublishingStatus && ($this->hasRelation('entry') || $this->hasRelation('page')))) {
      $respectPublishedQuery = $this->compileScopedQuery([function ($query) {
        return $query->where('published', true)
          ->andWhere(function ($query) {
            return $query->where('use_time', false)
              ->orWhere(function ($query) {
                return $query->where('use_time', true)
                  ->where(function ($query) {
                    return $query->where('start', '!=', null)
                      ->where('stop', '!=', null)
                      ->where('start', '<=', date('Y-m-d H:i:s', time()))
                      ->where('stop', '>=', date('Y-m-d H:i:s', time()));
                  })->orWhere(function ($query) {
                    return $query->where('start', '!=', null)
                      ->where('stop', '=', null)
                      ->where('start', '<=', date('Y-m-d H:i:s', time()));
                  })->orWhere(function ($query) {
                    return $query->where('start',  '=', null)
                      ->where('stop', '!=', null)
                      ->where('stop', '>=', date('Y-m-d H:i:s', time()));
                  })->orWhere(function ($query) {
                    return $query->where('start', '=', null)
                      ->where('stop', '=', null);
                  });
              });
          });
      }]);


      $compiledQuery = '(' . implode(' AND ', array_filter([
        ($this->hasRelation('entry') && $this->relation_id)
          ? $this->compileWhereQuery('directory_id', '=', $this->relation_id)
          : null,
        $respectPublishedQuery,
        $compiledQuery
          ? "($compiledQuery)"
          : $compiledQuery
      ])) . ')';
    }

    return $compiledQuery;
  }

  /**
   * @return string
   */
  protected function compileRequest($size = null, $page = null)
  {
    $params = [
      'order' => $this->orderBy,
      'dir' => $this->sortDir,
      'relation' => $this->relations ? implode(',', $this->relations) : null,
      'fields' => $this->fields ? implode(',', $this->fields) : null,
      'relation_id' => $this->relation_id,
      'size' => $size ?? $this->size,
      'page' => $page,
      'q' => urlencode($this->compileQuery()),
    ];

    if ($this->debug) {
      $params['debug'] = 1;
    }

    $params = array_filter(array_map(function ($key) use ($params) {
      if ($params[$key]) {
        return "{$key}={$params[$key]}";
      }
    }, array_keys($params)));

    $url = 'search?' . implode('&', $params);

    return $url;
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return $this->compileQuery();
  }
}
