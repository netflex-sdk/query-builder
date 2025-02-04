<?php

namespace Netflex\Query;

use Closure;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * @method void setPath(string $path)
 */
class PaginatedResult extends LengthAwarePaginator
{
  /**
   * The default pagination view.
   *
   * @var string
   */
  public static $defaultView = 'pagination::bootstrap-4';

  /**
   * The default "simple" pagination view.
   *
   * @var string
   */
  public static $defaultSimpleView = 'pagination::simple-bootstrap-4';

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
