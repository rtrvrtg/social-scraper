<?php

namespace Rtrvrtg\SocialScraper\Cache;

/**
 * Interface for compatible caching backends.
 */
interface CachingBackend {

  /**
   * Get a single data key.
   */
  public function getData(string $bucket, string $key);

  /**
   * Checks if data key is set.
   */
  public function hasData(string $bucket, string $key): bool;

  /**
   * Set a single data key.
   */
  public function setData(string $bucket, string $key, $value): void;

  /**
   * Delete a single data key.
   */
  public function deleteData(string $bucket, string $key): void;

  /**
   * Number of items in a given bucket.
   */
  public function bucketSize(string $bucket): int;

  /**
   * Purge an entire bucket.
   */
  public function purgeData(string $bucket = NULL): void;

  /**
   * Stats for a given bucket.
   */
  public function stats(string $bucket = NULL): array;

}
