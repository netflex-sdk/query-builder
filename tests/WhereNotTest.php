<?php

use Netflex\Query\Builder;

use Netflex\Query\Exceptions\InvalidValueException;
use Netflex\Query\Exceptions\InvalidOperatorException;

use PHPUnit\Framework\TestCase;

final class WhereNotTest extends TestCase
{
  public function testWhereNotEqualsQuery()
  {
    $query = new Builder(false);
    $query->whereNot('id', 10000);
    $whereEqualsQuery = $query->getQuery();

    $this->assertSame(
      '(NOT id:10000)',
      $whereEqualsQuery
    );

    $query = new Builder(false);
    $query->whereNot('id', '=', 10000);

    $this->assertSame(
      $whereEqualsQuery,
      $query->getQuery()
    );
  }

  public function testWhereNotSpecialEntitiesQuery()
  {
    $query = new Builder(false);
    $query->whereNot('should-encode', 10000);
    $whereEqualsQuery = $query->getQuery();


    $this->assertSame(
      '(NOT should##D##encode:10000)',
      $whereEqualsQuery
    );
  }

  public function testWhereNotInvalidOperatorThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidOperatorException::class);
    $query->whereNot('id', 'invalid_operator', 10000);
  }

  public function testWhereNotInvalidValueThrowsException()
  {
    $query = new Builder(false);

    $this->expectException(InvalidValueException::class);
    $query->whereNot('id', '=', function () {
    });
  }

  public function testWhereNotEqualsNullQuery()
  {
    $query = new Builder(false);
    $query->whereNot('id', null);

    $this->assertSame(
      '(NOT (NOT _exists_:id))',
      $query->getQuery()
    );
  }

  public function testWhereNotEqualsBoolQuery() {
    $query = new Builder(false);
    $query->whereNot('published', true);

    $this->assertSame(
      '(NOT published:1)',
      $query->getQuery()
    );

    $query = new Builder(false);
    $query->whereNot('published', false);

    $this->assertSame(
      '(NOT published:0)',
      $query->getQuery()
    );
  }

  public function testWhereNotEqualsStringQuery() {
    $query = new Builder(false);
    $query->whereNot('name', 'Test string');

    $this->assertSame(
      '(NOT name:"Test string")',
      $query->getQuery()
    );
  }

  public function testWhereNotEqualsArrayQuery()
  {
    $query = new Builder(false);
    $query->whereNot('id', [1, null, 'Test string', true, [1, null, 'Test string']]);

    $this->assertSame(
      '(NOT (id:1 OR (NOT _exists_:id) OR id:"Test string" OR id:1 OR (id:1 OR (NOT _exists_:id) OR id:"Test string")))',
      $query->getQuery()
    );
  }

  public function testWhereNotNotEqualsQuery() {
    $query = new Builder(false);
    $query->whereNot('id', '!=', 10000);

    $this->assertSame(
      '(NOT NOT id:10000)',
      $query->getQuery()
    );
  }

  public function testWhereNotNotEqualsNullQuery()
  {
    $query = new Builder(false);
    $query->whereNot('id', '!=', null);

    $this->assertSame(
      '(NOT NOT (NOT _exists_:id))',
      $query->getQuery()
    );
  }

  public function testWhereNotNotEqualsBoolQuery() {
    $query = new Builder(false);
    $query->whereNot('published', '!=', true);

    $this->assertSame(
      '(NOT NOT published:1)',
      $query->getQuery()
    );

    $query = new Builder(false);
    $query->whereNot('published', '!=', false);

    $this->assertSame(
      '(NOT NOT published:0)',
      $query->getQuery()
    );
  }

  public function testWhereNotNotEqualsStringQuery() {
    $query = new Builder(false);
    $query->whereNot('name', '!=', 'Test string');

    $this->assertSame(
      '(NOT NOT name:"Test string")',
      $query->getQuery()
    );
  }

  public function testWhereNotNotEqualsArrayQuery()
  {
    $query = new Builder(false);
    $query->whereNot('id', '!=', [1, null, 'Test string', true, [1, null, 'Test string']]);

    $this->assertSame(
      '(NOT (NOT id:1 OR NOT (NOT _exists_:id) OR NOT id:"Test string" OR NOT id:1 OR (NOT id:1 OR NOT (NOT _exists_:id) OR NOT id:"Test string")))',
      $query->getQuery()
    );
  }

  public function testScopedWhereNotQuery () {
    $query = new Builder(false);

    $query->whereNot('published', true);

    $query->whereNot(function ($query) {
      return $query->whereNot('id', 10000)
        ->where('name', 'Test string');
    });

    $this->assertSame(
      'NOT ((NOT published:1)) AND ((NOT id:10000) AND name:"Test string")',
      $query->getQuery()
    );
  }
}
