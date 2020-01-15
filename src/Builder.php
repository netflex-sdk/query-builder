<?php

namespace Netflex\Query;

use Exception;

use Netflex\API;

class Builder
{
  const MAX_QUERY_SIZE = 10000;

  /** @var API */
  private $client;

  /** @var array */
  protected $operators = ['=', '!=', '<=', '<', '>=', '>', 'like'];

  /** @var array */
  protected $grammar = ['(', ')', ' AND ', ' OR '];

  /** @var array */
  private $fields = [];

  /** @var array */
  private $relations = [];

  /** @var int */
  private $relation_id;

  /** @var int */
  private $size = self::MAX_QUERY_SIZE;

  /** @var int */
  private $page_size = 15;

  /** @var string */
  private $orderBy = null;

  /** @var string */
  private $sortDir = null;

  /** @var array */
  private $query = [];

  /** @var int */
  private $page = null;

  /** @var bool */
  private $paginate = false;

  /** @var bool */
  private $respectPublishingStatus = true;

  /** @var array */
  private $hits = [];

  public function __construct(self $parent = null, $query = [], $respectPublishingStatus = true)
  {
    $this->client = API::getClient();
    $this->parent = $parent;
    $this->query = $query;
    $this->respectPublishingStatus = $respectPublishingStatus;
  }

  /**
   * @param string $relation
   * @return bool
   */
  private function hasRelation($relation)
  {
    return in_array($relation, $this->relations);
  }

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

  private function compileTermQuery($field, $value)
  {
    return "${field}:$value";
  }

  private function compileNullQuery($field)
  {
    return "_exists_:{$field}";
  }

  private function compileScopedQuery(array $args, string $operator = 'AND')
  {
    $callback = count($args) === 1 ? array_pop($args) : function (self $scope) use ($args) {
      return $scope->where(...$args);
    };

    $builder = new static($this, [], false);

    $scopedQuery  = (function ($builder, $callback) {
      $callback($builder);
      return $builder->compileQuery(true);
    })($builder, $callback);

    $compiledQuery = $this->compileQuery(true);

    if ($operator) {
      $compiledQuery = $compiledQuery ? "($compiledQuery)" : $compiledQuery;
      $scopedQuery = $compiledQuery ? "($scopedQuery)" : $scopedQuery;
      $operator = ($compiledQuery && $scopedQuery && $operator) ? " $operator " : null;
    }

    return "{$compiledQuery}{$operator}$scopedQuery";
  }

  private function compileWhereQuery($field, $operator, $value)
  {
    $value = $this->escapeValue($value, $operator);
    $term = $value === null ? $this->compileNullQuery($field) : $this->compileTermQuery($field, $value);

    switch ($operator) {
      case '=':
        return $term;
      case '!=':
        return "NOT $term";
      case '>':
        if ($value === null) {
          return null;
        }

        if (is_string($value)) {
          return "($field:[$value TO *] AND (NOT $value))";
        }

        return "$field:>$value";
      case '>=':
        if ($value === null) {
          $this->query = [$this->compileWhereQuery($field, '!=', null)];
          return null;
        }

        if (is_string($value)) {
          return "$field:[$value TO *]";
        }

        return "$field:>=$value";
      case '<':
        if ($value === null) {
          return null;
        }

        if (is_string($value)) {
          return "($field:[* TO $value] AND (NOT $value))";
        }

        return "$field:<$value";
      case '<=':
        if ($value === null) {
          $this->query = [$this->compileWhereQuery($field, '=', null)];
          return null;
        }

        if (is_string($value)) {
          return "$field:[* TO $value]";
        }

        return "$field:<=$value";
      case 'like':
        return $term;
      default:
        throw new Exception("Unexpected operator '$operator'");
        break;
    }
  }

  public function getQuery () {
    return $this->compileQuery();
  }

  public function raw($query)
  {
    $this->query[] = $query;
  }

  public function orderBy($field, $direction = 'asc')
  {
    $this->orderBy = $field;
    $this->sortDirection($direction);
    return $this;
  }

  public function orderDirection($direction)
  {
    return $this->sortDirection($direction);
  }

  public function sortBy($field, $direction = 'asc')
  {
    return $this->orderBy($field, $direction);
  }

  public function sortDirection($direction)
  {
    if (!in_array($direction, ['asc', 'desc'])) {
      throw new Exception("Unexpected sortDirection '$direction'");
    }

    $this->sortDir = $direction;
    return $this;
  }

  public function relation(string $relation, int $relation_id = null)
  {
    $this->relations[] = $relation;
    $this->relation_id = $relation_id;
    $this->relations = array_filter(array_unique($this->relations));
    return $this;
  }

  public function limit(int $limit)
  {
    $this->size = $limit;
    return $this;
  }

  public function fields(array $fields)
  {
    foreach ($fields as $field) {
      $this->field($field);
    }

    return $this;
  }

  public function field(string $field)
  {
    $this->fields[] = $field;
    $this->fields = array_filter(array_unique($this->fields));
    return $this;
  }

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

  public function orWhere(...$args)
  {
    $this->query = [$this->compileScopedQuery($args, 'OR')];
    return $this;
  }

  public function andWhere(...$args)
  {
    $this->query = [$this->compileScopedQuery($args, 'AND')];
    return $this;
  }

  public function paginate($size = 15, $page = 1)
  {
    return new Page($this, $this->fetch($page, $size));
  }

  public function fetch($page = null, $size = null)
  {
    $this->page = $page ?? $this->page;
    $this->size = $size ?? $this->size;

    return $this->client->get($this->compileRequest());
  }

  public function get()
  {
    return $this->fetch()->data ?? [];
  }

  public function compileQuery($scoped = false)
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

  private function compileRequest()
  {
    $params = [
      'order' => $this->orderBy,
      'dir' => $this->sortDir,
      'relation' => implode(',', $this->relations),
      'fields' => count($this->fields) ? implode(',', $this->fields) : null,
      'relation_id' => $this->relation_id,
      'size' => $this->size,
      'page' => $this->page,
      'q' => $this->compileQuery()
    ];

    $params = array_filter(array_map(function ($key) use ($params) {
      if ($params[$key]) {
        $key = urlencode($key);
        $value = urlencode($params[$key]);
        return "{$key}={$value}";
      }
    }, array_keys($params)));

    $url = 'search?' . implode('&', $params);

    return $url;
  }
}
