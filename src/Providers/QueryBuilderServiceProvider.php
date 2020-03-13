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
    $this->app->bind('QueryBuilder', function () {
      return (new Builder(false))
        ->orderBy('id'); // Not all indexes has the default 'created' field
    });

    $this->app->bind('QueryBuilder.assoc', function () {
      return (new Builder(false))
        ->assoc(true)
        ->orderBy('id'); // Not all indexes has the default 'created' field
    });
  }

  public function boot()
  {
    if ($this->app->bound('events')) {
      QueryableModel::setEventDispatcher($this->app['events']);
    }
  }
}
