<?php

namespace Netflex\Query\Traits;

use Closure;

trait HasMapper
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
