<?php

namespace Netflex\Query;

use Illuminate\Support\Collection;

/**
 * @method array all()
 * @method \Illuminate\Support\LazyCollection lazy()
 * @method mixed avg($callback = null)
 * @method mixed median($key = null)
 * @method array|null mode($key = null)
 * @method \Illuminate\Support\Collection collapse()
 * @method bool contains($key, $operator = null, $value = null)
 * @method \Illuminate\Support\Collection crossJoin(...$lists)
 * @method \Illuminate\Support\Collection diff($items)
 * @method \Illuminate\Support\Collection diffUsing($items, callable $callback)
 * @method \Illuminate\Support\Collection diffAssoc($items)
 * @method \Illuminate\Support\Collection diffAssocUsing($items, callable $callback)
 * @method \Illuminate\Support\Collection diffKeys($items)
 * @method \Illuminate\Support\Collection diffKeysUsing($items, callable $callback)
 * @method \Illuminate\Support\Collection duplicates($callback = null, $strict = false)
 * @method \Illuminate\Support\Collection duplicatesStrict($callback = null)
 * @method \Illuminate\Support\Collection except($keys)
 * @method Illuminate\Support\Collection filter(callable $callback = null)
 * @method mixed first(callable $callback = null, $default = null)
 * @method \Illuminate\Support\Collection flatten($depth = INF)
 * @method \Illuminate\Support\Collection flip()
 * @method Illuminate\Support\Collection forget($keys)
 * @method mixed get($key, $default = null)
 * @method \Illuminate\Support\Collection groupBy($groupBy, $preserveKeys = false)
 * @method \Illuminate\Support\Collection keyBy($keyBy)
 * @method bool has($key)
 * @method string implode($value, $glue = null)
 * @method \Illuminate\Support\Collection intersect($items)
 * @method \Illuminate\Support\Collection intersectByKeys($items)
 * @method bool isEmpty()
 * @method string join($glue, $finalGlue = '')
 * @method \Illuminate\Support\Collection keys()
 * @method mixed last(callable $callback = null, $default = null)
 * @method \Illuminate\Support\Collection pluck($value, $key = null)
 * @method \Illuminate\Support\Collection map(callable $callback)
 * @method \Illuminate\Support\Collection mapToDictionary(callable $callback)
 * @method \Illuminate\Support\Collection mapWithKeys(callable $callback)
 * @method \Illuminate\Support\Collection merge($items)
 * @method \Illuminate\Support\Collection mergeRecursive($items)
 * @method \Illuminate\Support\Collection combine($values)
 * @method \Illuminate\Support\Collection union($items)
 * @method mixed nth($step, $offset = 0)
 * @method \Illuminate\Support\Collection only($keys)
 * @method mixed pop()
 * @method \Illuminate\Support\Collection prepend($value, $key = null)
 * @method \Illuminate\Support\Collection push($value)
 * @method \Illuminate\Support\Collection concat($source)
 * @method mixed pull($key, $default = null)
 * @method \Illuminate\Support\Collection put($key, $value)
 * @method \Illuminate\Support\Collection|mixed random($number = null)
 * @method mixed reduce(callable $callback, $initial = null)
 * @method \Illuminate\Support\Collection replace($items)
 * @method \Illuminate\Support\Collection replaceRecursive($items)
 * @method \Illuminate\Support\Collection reverse()
 * @method mixed search($value, $strict = false)
 * @method mixed shift()
 * @method \Illuminate\Support\Collection shuffle($seed = null)
 * @method \Illuminate\Support\Collection skip($count)
 * @method \Illuminate\Support\Collection slice($offset, $length = null)
 * @method \Illuminate\Support\Collection split($numberOfGroups)
 * @method \Illuminate\Support\Collection chunk($size)
 * @method \Illuminate\Support\Collection sort(callable $callback = null)
 * @method \Illuminate\Support\Collection sortBy($callback, $options = SORT_REGULAR, $descending = false)
 * @method \Illuminate\Support\Collection sortByDesc($callback, $options = SORT_REGULAR)
 * @method \Illuminate\Support\Collection sortKeys($options = SORT_REGULAR, $descending = false)
 * @method \Illuminate\Support\Collection sortKeysDesc($options = SORT_REGULAR)
 * @method \Illuminate\Support\Collection splice($offset, $length = null, $replacement = [])
 * @method \Illuminate\Support\Collection take($limit)
 * @method \Illuminate\Support\Collection transform(callable $callback)
 * @method \Illuminate\Support\Collection values()
 * @method \Illuminate\Support\Collection zip($items)
 * @method \Illuminate\Support\Collection pad($size, $value)
 * @method \ArrayIterator getIterator()
 * @method int count()
 * @method \Illuminate\Support\Collection add($item)
 * @method \Illuminate\Support\Collection toBase()
 * @method bool offsetExists($key)
 * @method mixed offsetGet($key)
 * @method void offsetSet($key, $value)
 * @method void offsetUnset($key)
 */
class PaginatedResult
{
  /** @var Builder */
  private $query;

  public $total;
  public $per_page;
  public $current_page;
  public $last_page;
  public $from;
  public $to;

  /** @var Collection */
  public $data;

  /**
   * @param Builder $query
   * @param object $result
   */
  public function __construct(Builder $query, $result = null)
  {
    $this->query = $query;

    $this->total = $result ? ($result->total ?? 1) : 1;
    $this->per_page = $result ? ($result->per_page ?? 0) : 0;
    $this->current_page = $result ? ($result->current_page ?? 1) : 1;
    $this->last_page = $result ? ($result->last_page ?? 1) : 1;
    $this->from = $result ? ($result->from ?? 0) : 0;
    $this->to = $result ? ($result->to ?? 0) : 0;
    $this->data = new Collection($result ? $result->data ?? [] : []);
  }

  /**
   * @return static|null
   */
  public function previous()
  {
    $previous = $this->current_page - 1;

    if ($previous < 1) {
      return null;
    }

    return $this->query->paginate($this->per_page, $previous);
  }

  /**
   * @return static|null
   */
  public function next()
  {
    $next = $this->current_page + 1;

    if ($next > $this->last_page) {
      return null;
    }

    return $this->query->paginate($this->per_page, $next);
  }

  public function __call($method, $args)
  {
    return $this->data->{$method}(...$args);
  }
}
