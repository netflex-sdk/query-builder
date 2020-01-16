<?php

namespace Netflex\Query;

use Netflex\Query\Builder;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
  /**
   * @return void
   */
  public function register()
  {
    $this->app->bind('QueryBuilder', function () {
      return new Builder();
    });
  }
}
