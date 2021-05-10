<?php

namespace Netflex\Query;

use Closure;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @method void setPath(string $path)
 */
class PaginatedResult extends LengthAwarePaginator
{
  /**
   * @param Builder $query
   * @param object $result
   * @param Closure $mapper
   */
  protected function __construct($data, $total, $per_page, $current_page, $onEachSide = 0)
  {
    static::useBootstrap();
    parent::__construct($data, $total, $per_page, $current_page);
    $this->onEachSide($onEachSide);
  }

  public static function fromBuilder(Builder $query, $page = 1, $onEachSide = 0)
  {
    $result = $query->fetch($query->getSize(), $page);
    $total = $result['total'] ?? 0;
    $per_page = $result['per_page'] ?? 0;
    $current_page = $result['current_page'] ?? 1;
    $data = new Collection($result['data'] ?? []);

    if ($mapper = $query->getMapper()) {
      $data = $data->map($mapper);
    }

    return new static($data, $total, $per_page, $current_page, $onEachSide);
  }
}
