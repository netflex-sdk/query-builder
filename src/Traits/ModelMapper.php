<?php

namespace Netflex\Query\Traits;

use Closure;

trait ModelMapper
{
  /**
   * @return Closure
   */
  protected function getMapper()
  {
    return function ($attributes) {
      return (new static)->newFromBuilder($attributes);
    };
  }
}
