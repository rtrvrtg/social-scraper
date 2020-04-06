<?php

namespace Rtrvrtg\SocialScraper\Cache;

/**
 * Basic memory cache.
 */
class MemoryCache implements CachingBackend {

  /**
   * Buckets of data.
   *
   * @var array
   */
  protected $buckets;

  /**
   * Stats about data.
   *
   * @var array
   */
  protected $stats;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->buckets = [];
    $this->stats = [
      'buckets' => 0,
      'hits' => 0,
      'misses' => 0,
      'bucketStats' => [],
    ];
  }

  /**
   * Get a single data key.
   */
  public function getData(string $bucket, string $key): \Serializable {
    if (!empty($this->buckets[$bucket][$key])) {
      $this->stats['hits'] += 1;
      $this->stats['bucketStats'][$bucket]['hits'] += 1;
      return unserialize($this->buckets[$bucket][$key]);
    }
    $this->stats['misses'] += 1;
    $this->stats['bucketStats'][$bucket]['misses'] += 1;
    return NULL;
  }

  /**
   * Set a single data key.
   */
  public function setData(string $bucket, string $key, \Serializable $value): void {
    if (empty($this->buckets[$bucket])) {
      $this->stats['buckets'] += 1;
    }
    $this->buckets[$bucket][$key] = serialize($value);
  }

  /**
   * Delete a single data key.
   */
  public function deleteData(string $bucket, string $key): void {
    unset($this->buckets[$bucket][$key]);
    if (empty($this->buckets[$bucket])) {
      $this->stats['buckets'] -= 1;
    }
  }

  /**
   * Number of items in a given bucket.
   */
  public function bucketSize(string $bucket): int {
    return count($this->buckets[$bucket]);
  }

  /**
   * Purge an entire bucket.
   */
  public function purgeData(string $bucket): void {
    $this->buckets[$bucket] = [];
    $this->stats['buckets'] = 0;
    $this->stats['bucketStats'] = [];
  }

  /**
   * Stats for a given bucket.
   */
  public function stats(string $bucket): array {
    return $this->stats;
  }

}
