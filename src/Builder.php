<?php

namespace Netflex\Query;

use Exception;

use Netflex\API;
use Netflex\Query\Exception\InvalidOperatorException;
use Netflex\Query\Exception\InvalidSortingDirectionException;

use Illuminate\Support\Str;

class Builder
{
  /** @var int The minimum allowed results per query */
  const MIN_QUERY_SIZE = 1;

  /** @var int The maximum allowed results per query */
  const MAX_QUERY_SIZE = 10000;

  /** @var string The ascending sort direction */
  const DIR_ASC = 'asc';

  /** @var string The decending sort direction */
  const DIR_DESC = 'desc';

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
  protected $grammar = ['(', ')', ' AND ', ' OR '];

  /** @var array */
  private $fields = null;

  /** @var array */
  private $relations = null;

  /** @var int */
  private $relation_id;

  /** @var int */
  private $size = self::MAX_QUERY_SIZE;

  /** @var string */
  private $orderBy = null;

  /** @var string */
  private $sortDir = null;

  /** @var array */
  private $query = [];

  /** @var int */
  private $page = null;

  /** @var bool */
  private $respectPublishingStatus = true;

  /**
   * @param bool $respectPublishingStatus
   * @param array $query
   */
  public function __construct($respectPublishingStatus = true, array $query = [])
  {
    $this->query = $query;
    $this->respectPublishingStatus = $respectPublishingStatus;
  }

  /**
   * @param string $relation
   * @return bool
   */
  private function hasRelation($relation)
  {
    return in_array(Str::singular($relation), $this->relations ?? []);
  }

  /**
   * @param mixed $value
   * @param string $operator
   * @return mixed
   */
  private function escapeValue($value, $operator = null)
  {
    if (is_string($value)) {
      if ($operator !== 'like') {
        return "\"{$value}\"";
      }

      return str_replace(' ', '*', $value);
    }

    if (is_bool($value)) {
      return $value ? 1 : 0;
    }

    if (is_array($value)) {
      if (count($value) === 1) {
        return $this->escapeValue(array_pop($value));
      }

      return '(' . implode(' ', array_map([$this, 'escapeValue'], $value)) . ')';
    }

    return $value;
  }

  /**
   * @param string $field
   * @param mixed $value
   * @return string
   */
  private function compileTermQuery(string $field, $value)
  {
    return "${field}:$value";
  }

  /**
   * @param string $field
   * @return string
   */
  private function compileNullQuery($field)
  {
    return "_exists_:{$field}";
  }

  /**
   * @param array $args
   * @param string $operator
   * @return string
   */
  private function compileScopedQuery(array $args, string $operator = 'AND')
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
   * @param string $field
   * @param string $operator|
   * @param mixed $value
   * @return string
   * @throws \Netflex\Query\Exception\InvalidOperatorException If an invalid operator is passed
   */
  private function compileWhereQuery($field, $operator, $value)
  {
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
   * Performs a raw Lucene query, use carefully.
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
   */
  public function orderBy($field, $direction = 'asc')
  {
    $this->orderBy = $field;
    $this->sortDirection($direction);
    return $this;
  }

  /**
   * Sets the direction to order the results by
   *
   * @param string $direction
   * @return static
   */
  public function orderDirection($direction)
  {
    return $this->sortDirection($direction);
  }

  /**
   * @see \Netflex\Query\Builder::orderBy
   * @param string $field
   * @param string $direction
   * @return static
   */
  public function sortBy($field, $direction = 'asc')
  {
    return $this->orderBy($field, $direction);
  }

  /**
   * @see \Netflex\Query\Builder::orderDirection
   * @param string $direction
   * @throws * @throws \Netflex\Query\Exception\InvalidSortingDirectionException If an invalid $direction is passed
   * @return static
   */
  public function sortDirection($direction)
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
  public function relation(string $relation, int $relation_id = null)
  {
    $this->relations = $this->relations ?? [];
    $this->relations[] = Str::singular($relation);
    $this->relation_id = $relation_id;
    $this->relations = array_filter(array_unique($this->relations));
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
    $this->fields[] = $field;
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
   * @param mixed $value
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

    if (is_null($value) && !is_null($operator) && !in_array($operator, $this->operators, true)) {
      $value = $operator;
      $operator = '=';
    }

    $this->query[] = $this->compileWhereQuery($field, $operator, $value);

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
   * @param mixed $value
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

    if (is_null($value) && !is_null($operator) && !in_array($operator, $this->operators, true)) {
      $value = $operator;
      $operator = '=';
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
   * @param mixed $value
   * @return static
   */
  public function orWhere(...$args)
  {
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
   * @param mixed $value
   * @return static
   */
  public function andWhere(...$args)
  {
    $this->query = [$this->compileScopedQuery($args, 'AND')];
    return $this;
  }

  /**
   * Creates a paginated result
   *
   * @param int $size
   * @param int $page
   * @return Page
   */
  public function paginate($size = 15, $page = 1)
  {
    return new Page($this, $this->fetch($page, $size));
  }

  /**
   * Retrieves the raw query result from the API
   *
   * @param int $page
   * @param int $size
   * @return object
   */
  private function fetch($page = null, $size = null)
  {
    $client = API::getClient();
    $this->page = $page ?? $this->page;
    $this->size = $size ?? $this->size;
    return $client->get($this->compileRequest($size));
  }

  /**
   * Retrieves the results of the query
   *
   * @return array
   */
  public function get()
  {
    return $this->fetch()->data ?? [];
  }

  /**
   * Retrieves the first result
   *
   * @return object|null
   */
  public function first()
  {
    $size = $this->size;
    $this->size = 1;
    $first = array_pop($this->get());
    $this->size = $size;
    return $first;
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
   * @return static
   */
  public function respectPublishingStatus()
  {
    $this->respectPublishingStatus = true;
    return $this;
  }

  /**
   * Get the count of items matching the current query
   *
   * @return int
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
  private function compileQuery($scoped = false)
  {
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
                      ->where('start', '>=', date('Y-m-d H:i:s', time()))
                      ->where('stop', '<=', date('Y-m-d H:i:s', time()));
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
  private function compileRequest()
  {
    $params = [
      'order' => $this->orderBy,
      'dir' => $this->sortDir,
      'relation' => $this->relations ? implode(',', $this->relations) : null,
      'fields' => $this->fields ? implode(',', $this->fields) : null,
      'relation_id' => $this->relation_id,
      'size' => $this->size,
      'page' => $this->page,
      'q' => $this->compileQuery()
    ];

    $params = array_filter(array_map(function ($key) use ($params) {
      if ($params[$key]) {
        return "{$key}={$params[$key]}";
      }
    }, array_keys($params)));

    $url = 'search?' . implode('&', $params);

    return $url;
  }
}
