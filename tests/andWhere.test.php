<?php

use Netflex\Query\Builder;

use Netflex\Query\Exceptions\InvalidValueException;
use Netflex\Query\Exceptions\InvalidOperatorException;
use Netflex\Query\Exceptions\InvalidAssignmentException;

use PHPUnit\Framework\TestCase;

final class AndWhereTest extends TestCase
{
  public function testAndWhereWithoutLeftHandSideThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidAssignmentException::class);
    $query->andWhere('id', 10000);
  }

  public function testAndWhereEqualsQuery()
  {
    $query = new Builder(false);
    $query->where('id', 10000)
      ->andWhere('id', 10001);

    $orWhereEqualsQuery = $query->getQuery();

    $this->assertSame(
      '(id:10000) AND (id:10001)',
      $orWhereEqualsQuery
    );

    $query = new Builder(false);
    $query->where('id', '=', 10000)
      ->andWhere('id', '=', 10001);

    $this->assertSame(
      $orWhereEqualsQuery,
      $query->getQuery()
    );
  }

  public function testAndWhereSpecialEntitiesQuery()
  {
    $query = new Builder(false);
    $query->where('id', 10000)
      ->andWhere('should-encode', 10001);

    $orWhereEqualsQuery = $query->getQuery();

    $this->assertSame(
      '(id:10000) AND (should##D##encode:10001)',
      $orWhereEqualsQuery
    );

    $query = new Builder(false);
    $query->where('id', '=', 10000)
      ->andWhere('should-encode', '=', 10001);

    $this->assertSame(
      $orWhereEqualsQuery,
      $query->getQuery()
    );
  }

  public function testAndWhereInvalidOperatorThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidOperatorException::class);
    $query->where('id', 10000)
      ->andWhere('id', 'invalid_operator', 10001);
  }

  public function testAndWhereInvalidValueThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidValueException::class);

    $query->where('id', 10000)
      ->andWhere('id', '=', function () {
    });
  }

  public function testAndWhereEqualsNullQuery()
  {
    $query = new Builder(false);
    $query->where('id', 10000)
      ->andWhere('id', null);

    $this->assertSame(
      '(id:10000) AND ((NOT _exists_:id))',
      $query->getQuery()
    );
  }

  public function testAndWhereEqualsBoolQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('published', true);

    $this->assertSame(
      '(id:10000) AND (published:1)',
      $query->getQuery()
    );

    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('published', false);

    $this->assertSame(
      '(id:10000) AND (published:0)',
      $query->getQuery()
    );
  }

  public function testAndWhereEqualsStringQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('name', 'Test string');

    $this->assertSame(
      '(id:10000) AND (name:"Test string")',
      $query->getQuery()
    );
  }

  public function testAndWhereEqualsArrayQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('id', [1, null, 'Test string', true, [1, null, 'Test string']]);

    $this->assertSame(
      '(id:10000) AND ((id:1 OR (NOT _exists_:id) OR id:"Test string" OR id:1 OR (id:1 OR (NOT _exists_:id) OR id:"Test string")))',
      $query->getQuery()
    );
  }

  public function testAndWhereNotEqualsQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('id', '!=', 10001);

    $this->assertSame(
      '(id:10000) AND (NOT id:10001)',
      $query->getQuery()
    );
  }

  public function testAndWhereNotEqualsNullQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('id', '!=', null);

    $this->assertSame(
      '(id:10000) AND (NOT (NOT _exists_:id))',
      $query->getQuery()
    );
  }

  public function testAndWhereNotEqualsBoolQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('published', '!=', true);

    $this->assertSame(
      '(id:10000) AND (NOT published:1)',
      $query->getQuery()
    );

    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('published', '!=', false);

    $this->assertSame(
      '(id:10000) AND (NOT published:0)',
      $query->getQuery()
    );
  }

  public function testAndWhereNotEqualsStringQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('name', '!=', 'Test string');

    $this->assertSame(
      '(id:10000) AND (NOT name:"Test string")',
      $query->getQuery()
    );
  }

  public function testAndWhereNotEqualsArrayQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere('id', '!=', [1, null, 'Test string', true, [1, null, 'Test string']]);

    $this->assertSame(
      '(id:10000) AND ((NOT id:1 OR NOT (NOT _exists_:id) OR NOT id:"Test string" OR NOT id:1 OR (NOT id:1 OR NOT (NOT _exists_:id) OR NOT id:"Test string")))',
      $query->getQuery()
    );
  }

  public function testScopedAndWhereQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->andWhere(function ($query) {
        return $query->where('published', true)
          ->where(function ($query) {
            return $query->where('id', 10001)
            ->andWhere('name', 'Test string');
          });
      });

    $this->assertSame(
      '(id:10000) AND ((published:1) AND ((id:10001) AND (name:"Test string")))',
      $query->getQuery()
    );
  }
}
