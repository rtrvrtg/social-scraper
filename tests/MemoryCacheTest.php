<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Rtrvrtg\SocialScraper\Cache\MemoryCache;
use Rtrvrtg\SocialScraper\Scraper\Twitter;

/**
 * Tests MemoryCache.
 */
final class MemoryCacheTest extends TestCase {

  /**
   * Test getting a single post.
   */
  public function testCache() {
    $i = new Twitter();
    $i->debug = TRUE;
    static $be;
    $be = new MemoryCache();
    $i->setCachingBackend($be);

    $this->assertEquals([
      'buckets' => 0,
      'hits' => 0,
      'misses' => 0,
      'bucketStats' => [],
    ], $be->stats());

    $result = $i->getPost('dril', '922321981');
    $this->assertEquals('922321981', $result->postId);
    $this->assertEquals('no', $result->text);
    $this->assertEquals([], $i->getErrors(TRUE));

    $this->assertEquals([
      'buckets' => 1,
      'hits' => 0,
      'misses' => 3,
      'bucketStats' => [
        'Twitter::doHTTP' => [
          'hits' => 0,
          'misses' => 3,
        ],
      ],
    ], $be->stats());

    $result = $i->getPost('dril', '922321981');
    $this->assertEquals('922321981', $result->postId);
    $this->assertEquals('no', $result->text);
    $this->assertEquals([], $i->getErrors(TRUE));

    $this->assertEquals([
      'buckets' => 1,
      'hits' => 2,
      'misses' => 3,
      'bucketStats' => [
        'Twitter::doHTTP' => [
          'hits' => 2,
          'misses' => 3,
        ],
      ],
    ], $be->stats());
  }

}
