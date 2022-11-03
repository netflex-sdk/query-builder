<?php

namespace Netflex\Query\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

use Netflex\Query\Builder;

use Netflex\Pages\Page;
use Netflex\Structure\File;
use Netflex\Structure\Image;

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
                    $class = Config::get('auth.providers.users.model', Customer::class);
                }

                if (isset($item['directory_id']) && class_exists(Structure::class)) {
                    $class = Structure::resolveModel($item['directory_id']);
                }

                if (isset($item['group_id']) && isset($item['content'])) {
                    $class = Config::get('pages.model', Page::class);
                }

                if (isset($item['folder_id']) && isset($item['path'])) {
                    $class = File::class;

                    if (isset($item['img_width'])) {
                        $class = Image::class;
                    }
                }

                if (isset($item['status']) && isset($item['secret'])) {
                    $class = Order::class;
                }

                if ($class) {
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
