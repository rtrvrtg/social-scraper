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
  public function getData(string $bucket, string $key) {
    if ($this->hasData($bucket, $key)) {
      return $this->buckets[$bucket][$key];
    }
    return NULL;
  }

  /**
   * Update stats.
   */
  protected function updateStats(string $bucket, bool $hit) {
    if ($hit) {
      $this->stats['hits'] += 1;
      if (empty($this->stats['bucketStats'][$bucket])) {
        $this->stats['bucketStats'][$bucket] = [
          'hits' => 1,
          'misses' => 0,
        ];
      }
      else {
        $this->stats['bucketStats'][$bucket]['hits'] += 1;
      }
    }
    else {
      $this->stats['misses'] += 1;
      if (empty($this->stats['bucketStats'][$bucket])) {
        $this->stats['bucketStats'][$bucket] = [
          'hits' => 0,
          'misses' => 1,
        ];
      }
      else {
        $this->stats['bucketStats'][$bucket]['misses'] += 1;
      }
    }
  }

  /**
   * Checks if data key is set.
   */
  public function hasData(string $bucket, string $key): bool {
    $result = (
      !empty($this->buckets[$bucket]) &&
      array_key_exists($key, $this->buckets[$bucket])
    );
    $this->updateStats($bucket, $result);
    return $result;
  }

  /**
   * Set a single data key.
   */
  public function setData(string $bucket, string $key, $value): void {
    if (empty($this->buckets[$bucket])) {
      $this->stats['buckets'] += 1;
    }
    $this->buckets[$bucket][$key] = $value;
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
  public function purgeData(string $bucket = NULL): void {
    if (empty($bucket)) {
      $this->buckets = [];
      $this->stats['buckets'] = 0;
      $this->stats['bucketStats'] = [];
      return;
    }
    $this->buckets[$bucket] = [];
    $this->stats['buckets'] -= 1;
    $this->stats['bucketStats'][$bucket] = [];
  }

  /**
   * Stats for a given bucket.
   */
  public function stats(string $bucket = NULL): array {
    if (empty($bucket)) {
      return $this->stats;
    }
    return $this->stats[$bucket];
  }

}
