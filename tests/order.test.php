<?php

use Netflex\Query\Builder;
use Netflex\Query\Exceptions\InvalidSortingDirectionException;

use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
  public function testDefaultOrder()
  {
    $query = new Builder(false);

    $this->assertSame(
      'search?size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testCanOrderByField()
  {
    $query = new Builder(false);
    $query->orderBy('name');

    $this->assertSame(
      'search?order=name&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testCanOrderBySpecialEntitiesField()
  {
    $query = new Builder(false);
    $query->orderBy('should-encode');

    $this->assertSame(
      'search?order=should%23%23D%23%23encode&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testCanSetSortingDirection()
  {
    $query = new Builder(false);
    $query->orderBy('name', Builder::DIR_DESC);

    $this->assertSame(
      'search?order=name&dir=' . Builder::DIR_DESC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );

    $query = new Builder(false);
    $query->orderBy('name', Builder::DIR_ASC);

    $this->assertSame(
      'search?order=name&dir=' . Builder::DIR_ASC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testCanSetSortingDirectionStandalone()
  {
    $query = new Builder(false);
    $query->orderBy('name');
    $query->orderDirection(Builder::DIR_DESC);

    $this->assertSame(
      'search?order=name&dir=' . Builder::DIR_DESC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );

    $query = new Builder(false);
    $query->orderBy('name');
    $query->orderDirection(Builder::DIR_ASC);

    $this->assertSame(
      'search?order=name&dir=' . Builder::DIR_ASC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testHandlesInvalidSortingDirection()
  {
    $query = new Builder(false);

    $this->expectException(InvalidSortingDirectionException::class);
    $query->orderDirection('invalid');
  }
}
