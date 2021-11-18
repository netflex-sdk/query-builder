<?php

use Netflex\Query\Builder;

use PHPUnit\Framework\TestCase;

final class FieldTest extends TestCase
{
  public function testSetSingleField()
  {
    $query = new Builder(false);
    $query->field('id');

    $this->assertSame(
      'search?fields=id&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testSetSpecialEntityField()
  {
    $query = new Builder(false);
    $query->field('should-encode');

    $this->assertSame(
      'search?fields=should##D##encode&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testSetMultipleFields()
  {
    $query = new Builder(false);
    $query->field('id');
    $query->field('name');

    $this->assertSame(
      'search?fields=id,name&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testSetFields()
  {
    $query = new Builder(false);
    $query->fields(['id', 'name']);

    $this->assertSame(
      'search?fields=id,name&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }
}
