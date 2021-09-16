<?php

use Netflex\Query\Builder;

use PHPUnit\Framework\TestCase;

final class RawTest extends TestCase
{
  public function testCanPerformRawQuery()
  {
    $raw = '(id:1)';
    $query = new Builder(false);
    $query->raw($raw);

    $this->assertSame(
      'search?size=' . Builder::MAX_QUERY_SIZE . '&q=' . urlencode($raw),
      $query->getRequest()
    );
  }

  public function testCanPerformMultipleRawQueries()
  {
    $queries = [
      '(id:1)',
      '(id:2)'
    ];

    $query = new Builder(false);
    $query->raw($queries[0]);
    $query->raw($queries[1]);

    $this->assertSame(
      'search?size=' . Builder::MAX_QUERY_SIZE . '&q=' . urlencode($queries[0] . ' AND ' . $queries[1]),
      $query->getRequest()
    );
  }
}
