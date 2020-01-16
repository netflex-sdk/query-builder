<?php

use Netflex\Query\Builder;

use PHPUnit\Framework\TestCase;

final class RelationTest extends TestCase
{
  public function testSetSingleRelation()
  {
    $query = new Builder(false);
    $query->relation('entry');

    $this->assertSame(
      'search?relation=entry&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testSetSingleRelationWithPlural()
  {
    $query = new Builder(false);
    $query->relation('entries');

    $this->assertSame(
      'search?relation=entry&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testSetMultipleRelations()
  {
    $query = new Builder(false);
    $query->relation('entry');
    $query->relation('page');

    $this->assertSame(
      'search?relation=entry,page&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testSetMultipleRelationsWithPlural()
  {
    $query = new Builder(false);
    $query->relation('entries');
    $query->relation('pages');

    $this->assertSame(
      'search?relation=entry,page&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testSetRelations()
  {
    $query = new Builder(false);
    $query->relations(['entry', 'page']);

    $this->assertSame(
      'search?relation=entry,page&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testSetRelationsWithPlural()
  {
    $query = new Builder(false);
    $query->relations(['entries', 'pages']);

    $this->assertSame(
      'search?relation=entry,page&size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }
}
