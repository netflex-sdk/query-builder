<?php

use Netflex\Query\Builder;

use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
  public function testRequestCompilation()
  {
    $query = new Builder(false);

    $this->assertSame(
      'search?size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testSizeParemter()
  {
    $query = new Builder(false);
    $query->limit(1);

    $this->assertSame(
      'search?size=1',
      $query->getRequest()
    );

    $this->expectException(TypeError::class);

    $query = new Builder(false);
    $query->limit('abc');
  }

  public function testRelationParemter()
  {
    $query = new Builder(false);
    $query->relation('entry');

    $this->assertSame(
      'search?relation=entry&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );

    $query = new Builder(false);
    $query->relation('entry', 10000);

    $this->assertSame(
      'search?relation=entry&relation_id=10000&size=' . Builder::MAX_QUERY_SIZE . '&q=' . urlencode('directory_id:10000'),
      $query->getRequest()
    );

    $this->expectException(TypeError::class);

    $query = new Builder(false);
    $query->relation(1337, 'abc');
  }

  public function testOrderParemter()
  {
    $query = new Builder(false);
    $query->orderBy('id');

    $this->assertSame(
      'search?order=id&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );

    $query = new Builder(false);
    $query->orderBy('id', Builder::DIR_ASC);

    $this->assertSame(
      'search?order=id&dir=' . Builder::DIR_ASC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );

    $query = new Builder(false);
    $query->orderBy('id', Builder::DIR_DESC);

    $this->assertSame(
      'search?order=id&dir=' . Builder::DIR_DESC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testFieldsParemter()
  {
    $query = new Builder(false);
    $query->field('id');

    $this->assertSame(
      'search?fields=id&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );

    $query = new Builder(false);
    $query->fields(['id', 'name']);

    $this->assertSame(
      'search?fields=id,name&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testDebugParemter()
  {
    $query = new Builder(false);

    $this->assertSame(
      'search?size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );

    $query = new Builder(false);
    $query->debug();

    $this->assertSame(
      'search?size=' . Builder::MAX_QUERY_SIZE . '&debug=1',
      $query->getRequest()
    );
  }
}
