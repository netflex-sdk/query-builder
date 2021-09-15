<?php

namespace Netflex\Query\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

use Netflex\Query\Builder;

use Netflex\Customers\Customer;
use Netflex\Structure\Structure;

class SearchServiceProvider extends ServiceProvider
{
  /**
   * @return void
   */
  public function register()
  {
    $this->app->bind('Search', function () {
        /** @var Builder $search */
        $search = (new Builder());
        $defaultMapper = $search->getMapper();

        $search = $search->orderBy('id')
            ->respectPublishingStatus(true)
            ->assoc(true)
            ->setMapper(function ($item) use ($defaultMapper) {
                /** @var QueryableModel|null $model */
                $class = null;

                if (isset($item['group_id']) && isset($item['mail'])) {
                    $customerClass = Config::get('auth.providers.users.model', Customer::class);
                    
                    if (class_exists($customerClass)) {
                        $class = $customerClass;
                    }
                }

                if (isset($item['directory_id']) && class_exists(Structure::class)) {
                    $entryClass = Structure::resolveModel($item['directory_id']);
                    
                    if (class_exists($entryClass)) {
                        $class = $entryClass;
                    }
                }

                if (isset($item['group_id']) && isset($item['content']) && class_exists(Page::class)) {
                    $class = Page::class;
                }

                if (isset($item['status']) && isset($item['secret']) && class_exists(Order::class)) {
                    $class = Order::class;
                }

                if ($class && class_exists($class)) {
                    /** @var QueryableModel $model */
                    $model = new $class;
                    return $model->newFromBuilder($item);
                }

                return $defaultMapper ? $defaultMapper($item) : $item;
            });

        return $search;
    });
  }   
}