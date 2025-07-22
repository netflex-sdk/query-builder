<?php

namespace Netflex\Query\Providers;

use Netflex\Query\Builder;
use Netflex\Query\QueryableModel;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
  /**
   * @return void
   */
  public function register()
  {
    $this->app->bind(Builder::class, Builder::class);
  }

  public function boot()
  {
    if ($this->app->bound('events')) {
      QueryableModel::setEventDispatcher($this->app['events']);
    }
  }
}
