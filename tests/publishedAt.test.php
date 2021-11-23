<?php

use Illuminate\Support\Carbon;
use Netflex\Query\Builder;

use PHPUnit\Framework\TestCase;

final class PublishedAtTest extends TestCase
{
  private function mockPublishedAtQuery ($date) {
    $date = urlencode($date);
    return 'search?size=' . Builder::MAX_QUERY_SIZE . '&q=' . urlencode('(published:1) AND ((use_time:0) OR (((((use_time:1) AND (NOT (NOT _exists_:start) AND NOT (NOT _exists_:stop) AND start:[* TO "') . $date . urlencode('"] AND stop:["') . $date . urlencode('" TO *])) OR (NOT (NOT _exists_:start) AND (NOT _exists_:stop) AND start:[* TO "') . $date . urlencode('"])) OR ((NOT _exists_:start) AND NOT (NOT _exists_:stop) AND stop:["') . $date . urlencode('" TO *])) OR ((NOT _exists_:start) AND (NOT _exists_:stop))))');
  }

  public function testCanPerformPublishedAtDateFromStringQuery()
  {
    $query = new Builder(false);
    $date = '2021-09-15';
    $query->publishedAt($date);

    $this->assertSame(
      $this->mockPublishedAtQuery($date . ' 00:00:00'),
      $query->getRequest()
    );
  }

  public function testCanPerformPublishedAtDateTimeFromTimestampQuery()
  {
    $query = new Builder(false);
    $testDate = Carbon::parse('2021-09-15');
    $timestamp = $testDate->unix();
    $date = $testDate->toDateTimeString();
    $query->publishedAt($timestamp);

    $this->assertSame(
      $this->mockPublishedAtQuery($date),
      $query->getRequest()
    );
  }

  public function testCanPerformPublishedAtDateTimeFromDateTimeInterfaceQuery()
  {
    $query = new Builder(false);
    $date = Carbon::now();
    $query->publishedAt($date);

    $this->assertSame(
      $this->mockPublishedAtQuery($date->toDateTimeString()),
      $query->getRequest()
    );
  }

  public function testCanPerformRespesctsPublishingStatusQuery()
  {
    $query = new Builder(false);

    $now = Carbon::now();
    $date = $now->toDateTimeString();
    $query->respectPublishingStatus();

    $this->assertSame(
      $this->mockPublishedAtQuery($date),
      $query->getRequest()
    );
  }
}
