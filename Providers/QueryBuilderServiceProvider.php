<?php

namespace Netflex\Query\Providers;

use Netflex\Query\QueryableModel;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
  public function boot()
  {
    if ($this->app->bound('events')) {
      QueryableModel::setEventDispatcher($this->app['events']);
    }
  }
}
