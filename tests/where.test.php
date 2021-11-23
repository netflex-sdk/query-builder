<?php

use Netflex\Query\Builder;

use Netflex\Query\Exceptions\InvalidValueException;
use Netflex\Query\Exceptions\InvalidOperatorException;

use PHPUnit\Framework\TestCase;

final class WhereTest extends TestCase
{
  public function testWhereEqualsQuery()
  {
    $query = new Builder(false);
    $query->where('id', 10000);
    $whereEqualsQuery = $query->getQuery();

    $this->assertSame(
      'id:10000',
      $whereEqualsQuery
    );

    $query = new Builder(false);
    $query->where('id', '=', 10000);

    $this->assertSame(
      $whereEqualsQuery,
      $query->getQuery()
    );
  }

  public function testWhereSpecialEntitiesQuery()
  {
    $query = new Builder(false);
    $query->where('should-encode', 10000);
    $whereEqualsQuery = $query->getQuery();


    $this->assertSame(
      'should##D##encode:10000',
      $whereEqualsQuery
    );
  }

  public function testWhereInvalidOperatorThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidOperatorException::class);
    $query->where('id', 'invalid_operator', 10000);
  }

  public function testWhereInvalidValueThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidValueException::class);
    $query->where('id', '=', function () {
    });
  }

  public function testWhereEqualsNullQuery()
  {
    $query = new Builder(false);
    $query->where('id', null);

    $this->assertSame(
      '(NOT _exists_:id)',
      $query->getQuery()
    );
  }

  public function testWhereEqualsBoolQuery()
  {
    $query = new Builder(false);
    $query->where('published', true);

    $this->assertSame(
      'published:1',
      $query->getQuery()
    );

    $query = new Builder(false);
    $query->where('published', false);

    $this->assertSame(
      'published:0',
      $query->getQuery()
    );
  }

  public function testWhereEqualsStringQuery()
  {
    $query = new Builder(false);
    $query->where('name', 'Test string');

    $this->assertSame(
      'name:"Test string"',
      $query->getQuery()
    );
  }

  public function testWhereEqualsArrayQuery()
  {
    $query = new Builder(false);
    $query->where('id', [1, null, 'Test string', true, [1, null, 'Test string']]);

    $this->assertSame(
      '(id:1 OR (NOT _exists_:id) OR id:"Test string" OR id:1 OR (id:1 OR (NOT _exists_:id) OR id:"Test string"))',
      $query->getQuery()
    );
  }

  public function testWhereNotEqualsQuery()
  {
    $query = new Builder(false);
    $query->where('id', '!=', 10000);

    $this->assertSame(
      'NOT id:10000',
      $query->getQuery()
    );
  }

  public function testWhereNotEqualsNullQuery()
  {
    $query = new Builder(false);
    $query->where('id', '!=', null);

    $this->assertSame(
      'NOT (NOT _exists_:id)',
      $query->getQuery()
    );
  }

  public function testWhereNotEqualsBoolQuery()
  {
    $query = new Builder(false);
    $query->where('published', '!=', true);

    $this->assertSame(
      'NOT published:1',
      $query->getQuery()
    );

    $query = new Builder(false);
    $query->where('published', '!=', false);

    $this->assertSame(
      'NOT published:0',
      $query->getQuery()
    );
  }

  public function testWhereNotEqualsStringQuery()
  {
    $query = new Builder(false);
    $query->where('name', '!=', 'Test string');

    $this->assertSame(
      'NOT name:"Test string"',
      $query->getQuery()
    );
  }

  public function testWhereNotEqualsArrayQuery()
  {
    $query = new Builder(false);
    $query->where('id', '!=', [1, null, 'Test string', true, [1, null, 'Test string']]);

    $this->assertSame(
      '(NOT id:1 OR NOT (NOT _exists_:id) OR NOT id:"Test string" OR NOT id:1 OR (NOT id:1 OR NOT (NOT _exists_:id) OR NOT id:"Test string"))',
      $query->getQuery()
    );
  }

  public function testScopedWhereQuery()
  {
    $query = new Builder(false);

    $query->where('published', true);

    $query->where(function ($query) {
      return $query->where('id', 10000)
        ->where('name', 'Test string');
    });

    $this->assertSame(
      '(published:1) AND (id:10000 AND name:"Test string")',
      $query->getQuery()
    );
  }
}
