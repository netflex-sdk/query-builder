<?php

use Netflex\Query\QueryableModel;

use PHPUnit\Framework\TestCase;

final class CacheIdentifierTest extends TestCase
{
  public function testRelation ()
  {
    $model = new class extends QueryableModel {
      protected $relation = 'page';

      public function proxy ($metod, ...$args) {
        return call_user_func_array([$this, $metod], $args);
      }
    };

    $this->assertSame('page/12345', $model->proxy('getCacheIdentifier', 12345));
    $this->assertSame('pages', $model->proxy('getAllCacheIdentifier'));
  }

  public function testRelationId (): self
  {
    $model = new class extends QueryableModel {
      protected $relation = 'entry';
      protected $relationId = 10000;

      public function proxy ($metod, ...$args) {
        return call_user_func_array([$this, $metod], $args);
      }
    };

    $this->assertSame('entry/10000/12345', $model->proxy('getCacheIdentifier', 12345));
    $this->assertSame('entries/10000', $model->proxy('getAllCacheIdentifier'));

    return $this;
  }
}
