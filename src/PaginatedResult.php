<?php

namespace Netflex\Query;

use Closure;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PaginatedResult extends LengthAwarePaginator
{
  /**
   * @param Builder $query
   * @param object $result
   * @param Closure $mapper
   */
  protected function __construct($data, $total, $per_page, $current_page, $onEachSide = 0)
  {
    parent::__construct($data, $total, $per_page, $current_page);
    $this->onEachSide($onEachSide);
  }

  public static function fromBuilder(Builder $query, $page = 1, $onEachSide = 0)
  {
    $result = $query->fetch($query->getSize(), $page);
    $total = $result ? ($result->total ?? 1) : 1;
    $per_page = $query->getSize();
    $current_page = $result ? ($result->current_page ?? 1) : 1;
    $data = new Collection($result ? $result->data ?? [] : []);

    if ($mapper = $query->getMapper()) {
      $data = $data->map($mapper);
    }

    return new static($data, $total, $per_page, $current_page, $onEachSide);
  }
}
