<?php

use Netflex\Query\Builder;

use PHPUnit\Framework\TestCase;

final class LimitTest extends TestCase
{
  public function testDefaultLimit()
  {
    $query = new Builder(false);

    $this->assertSame(
      'search?size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testCanOverrideLimit()
  {
    $query = new Builder(false);
    $query->limit(1);

    $this->assertSame(
      'search?size=1',
      $query->getRequest()
    );
  }

  public function testCanNotOverrideMaxLimit()
  {
    $query = new Builder(false);
    $query->limit(Builder::MAX_QUERY_SIZE + 1);

    $this->assertSame(
      'search?size=' . Builder::MAX_QUERY_SIZE,
      $query->getRequest()
    );
  }

  public function testCanNotOverrideMinLimit()
  {
    $query = new Builder(false);
    $query->limit(Builder::MIN_QUERY_SIZE - 1);

    $this->assertSame(
      'search?size=' . Builder::MIN_QUERY_SIZE,
      $query->getRequest()
    );
  }
}
