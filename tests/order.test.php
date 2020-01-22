<?php

use Netflex\Query\Builder;
use Netflex\Query\Exceptions\InvalidSortingDirectionException;

use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
  public function testDefaultOrder()
  {
    $query = new Builder();

    $this->assertSame(
      'search?size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testCanOrderByField()
  {
    $query = new Builder();
    $query->orderBy('name');

    $this->assertSame(
      'search?order=name&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testCanSetSortingDirection()
  {
    $query = new Builder();
    $query->orderBy('name', Builder::DIR_DESC);

    $this->assertSame(
      'search?order=name&dir=' . Builder::DIR_DESC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );

    $query = new Builder();
    $query->orderBy('name', Builder::DIR_ASC);

    $this->assertSame(
      'search?order=name&dir=' . Builder::DIR_ASC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testCanSetSortingDirectionStandalone()
  {
    $query = new Builder();
    $query->orderBy('name');
    $query->orderDirection(Builder::DIR_DESC);

    $this->assertSame(
      'search?order=name&dir=' . Builder::DIR_DESC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );

    $query = new Builder();
    $query->orderBy('name');
    $query->orderDirection(Builder::DIR_ASC);

    $this->assertSame(
      'search?order=name&dir=' . Builder::DIR_ASC . '&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testHandlesInvalidSortingDirection()
  {
    $query = new Builder();

    $this->expectException(InvalidSortingDirectionException::class);
    $query->orderDirection('invalid');
  }
}
