<?php

use Netflex\Query\Builder;

use Netflex\Query\Exceptions\InvalidValueException;
use Netflex\Query\Exceptions\InvalidOperatorException;
use Netflex\Query\Exceptions\InvalidAssignmentException;

use PHPUnit\Framework\TestCase;

final class OrWhereTest extends TestCase
{
  public function testOrWhereWithoutLeftHandSideThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidAssignmentException::class);
    $query->orWhere('id', 10000);
  }

  public function testOrWhereEqualsQuery()
  {
    $query = new Builder(false);
    $query->where('id', 10000)
      ->orWhere('id', 10001);

    $orWhereEqualsQuery = $query->getQuery();

    $this->assertSame(
      '(id:10000) OR (id:10001)',
      $orWhereEqualsQuery
    );

    $query = new Builder(false);
    $query->where('id', '=', 10000)
      ->orWhere('id', '=', 10001);

    $this->assertSame(
      $orWhereEqualsQuery,
      $query->getQuery()
    );
  }

  public function testOrWhereSpecialEntitiesQuery()
  {
    $query = new Builder(false);
    $query->where('id', 10000)
      ->orWhere('should-encode', 10001);

    $orWhereEqualsQuery = $query->getQuery();


    $this->assertSame(
      '(id:10000) OR (should##D##encode:10001)',
      $orWhereEqualsQuery
    );
  }

  public function testOrWhereInvalidOperatorThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidOperatorException::class);
    $query->where('id', 10000)
      ->orWhere('id', 'invalid_operator', 10001);
  }

  public function testOrWhereInvalidValueThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidValueException::class);

    $query->where('id', 10000)
      ->orWhere('id', '=', function () {
    });
  }

  public function testOrWhereEqualsNullQuery()
  {
    $query = new Builder(false);
    $query->where('id', 10000)
      ->orWhere('id', null);

    $this->assertSame(
      '(id:10000) OR ((NOT _exists_:id))',
      $query->getQuery()
    );
  }

  public function testOrWhereEqualsBoolQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('published', true);

    $this->assertSame(
      '(id:10000) OR (published:1)',
      $query->getQuery()
    );

    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('published', false);

    $this->assertSame(
      '(id:10000) OR (published:0)',
      $query->getQuery()
    );
  }

  public function testOrWhereEqualsStringQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('name', 'Test string');

    $this->assertSame(
      '(id:10000) OR (name:"Test string")',
      $query->getQuery()
    );
  }

  public function testOrWhereEqualsArrayQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('id', [1, null, 'Test string', true, [1, null, 'Test string']]);

    $this->assertSame(
      '(id:10000) OR ((id:1 OR (NOT _exists_:id) OR id:"Test string" OR id:1 OR (id:1 OR (NOT _exists_:id) OR id:"Test string")))',
      $query->getQuery()
    );
  }

  public function testOrWhereNotEqualsQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('id', '!=', 10001);

    $this->assertSame(
      '(id:10000) OR (NOT id:10001)',
      $query->getQuery()
    );
  }

  public function testOrWhereNotEqualsNullQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('id', '!=', null);

    $this->assertSame(
      '(id:10000) OR (NOT (NOT _exists_:id))',
      $query->getQuery()
    );
  }

  public function testOrWhereNotEqualsBoolQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('published', '!=', true);

    $this->assertSame(
      '(id:10000) OR (NOT published:1)',
      $query->getQuery()
    );

    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('published', '!=', false);

    $this->assertSame(
      '(id:10000) OR (NOT published:0)',
      $query->getQuery()
    );
  }

  public function testOrWhereNotEqualsStringQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('name', '!=', 'Test string');

    $this->assertSame(
      '(id:10000) OR (NOT name:"Test string")',
      $query->getQuery()
    );
  }

  public function testOrWhereNotEqualsArrayQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere('id', '!=', [1, null, 'Test string', true, [1, null, 'Test string']]);

    $this->assertSame(
      '(id:10000) OR ((NOT id:1 OR NOT (NOT _exists_:id) OR NOT id:"Test string" OR NOT id:1 OR (NOT id:1 OR NOT (NOT _exists_:id) OR NOT id:"Test string")))',
      $query->getQuery()
    );
  }

  public function testScopedOrWhereQuery()
  {
    $query = new Builder(false);

    $query->where('id', 10000)
      ->orWhere(function ($query) {
        return $query->where('published', true)
          ->where(function ($query) {
            return $query->where('id', 10001)
            ->orWhere('name', 'Test string');
          });
      });

    $this->assertSame(
      '(id:10000) OR ((published:1) AND ((id:10001) OR (name:"Test string")))',
      $query->getQuery()
    );
  }
}
