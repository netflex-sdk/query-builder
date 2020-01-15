<?php

namespace Netflex\Query;

class Page
{
  /** @var Builder */
  private $query;

  public $total;
  public $per_page;
  public $current_page;
  public $last_page;
  public $from;
  public $to;

  /** @var array */
  public $data;

  public function __construct(Builder $query, $result = null)
  {
    $this->query = $query;

    if ($result) {
      $this->total = $result->total ?? 1;
      $this->per_page = $result->per_page ?? 0;
      $this->current_page = $result->current_page ?? 1;
      $this->last_page = $result->last_page ?? 1;
      $this->from = $result->from ?? 0;
      $this->to = $result->to ?? 0;
      $this->data = $result->data ?? [];
    }
  }

  public function previous()
  {
    $previous = $this->current_page - 1;

    if ($previous < 1) {
      return null;
    }

    return $this->query->paginate($this->per_page, $previous);
  }

  public function next()
  {
    $next = $this->current_page + 1;

    if ($next > $this->last_page) {
      return null;
    }

    return $this->query->paginate($this->per_page, $next);
  }
}
