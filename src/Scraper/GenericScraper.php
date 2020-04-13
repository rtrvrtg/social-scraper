<?php

namespace Rtrvrtg\SocialScraper\Scraper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

/**
 * Generic site scraper.
 */
class GenericScraper {

  /**
   * HTTP client.
   *
   * @var GuzzleHttp\Client
   */
  protected $client;

  /**
   * List of stored errors.
   *
   * @var array
   */
  protected $errors;

  /**
   * Caching backend.
   *
   * @var array
   */
  protected $cachingBackend = NULL;

  /**
   * Debug.
   *
   * @var array
   */
  public $debug = FALSE;

  /**
   * Last received headers.
   *
   * @var array
   */
  public $lastHeaders = [];

  /**
   * Constructor.
   */
  public function __construct() {
    $this->client = new Client([
      'cookies' => TRUE,
    ]);
    $this->errors = [];
  }

  /**
   * Generate a cache bucket and key name.
   */
  protected function cacheBucketKey($function_name, $cache_key_base) {
    $path = explode('\\', get_called_class());
    $class_name = array_pop($path);
    return [
      $class_name . '::' . $function_name,
      hash('sha256', $cache_key_base),
    ];
  }

  /**
   * Set caching backend for scraper.
   */
  public function setCachingBackend($caching_backend) {
    $this->cachingBackend = $caching_backend;
  }

  /**
   * Get all logged errors.
   */
  public function getErrors(bool $purge = FALSE): array {
    $errors = $this->errors;
    if ($purge) {
      $this->errors = [];
    }
    return $errors;
  }

  /**
   * Headers for HTTP requests.
   */
  protected function doHttpHeaders() {
    return [
      'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3506.71 Safari/537.36',
    ];
  }

  /**
   * Use caching backend to get data.
   */
  protected function useCache(string $cache_bucket, string $cache_key, callable $callback) {
    if (
      !empty($this->cachingBackend) &&
      $this->cachingBackend->hasData($cache_bucket, $cache_key)
    ) {
      return @unserialize($this->cachingBackend->getData($cache_bucket, $cache_key));
    }
    $result = $callback();
    if (!empty($this->cachingBackend)) {
      $ser_result = @serialize($result);
      $this->cachingBackend->setData($cache_bucket, $cache_key, $ser_result);
    }
    return $result;
  }

  /**
   * Do HTTP request.
   */
  protected function doHttp(string $method, string $url, array $body = []) {
    list($cache_bucket, $cache_key) = $this->cacheBucketKey(
      'doHTTP',
      $method . ' ' . $url . ' ' . json_encode($body)
    );
    return $this->useCache($cache_bucket, $cache_key, function () use ($method, $url, $body) {
      $headers = $this->doHttpHeaders();
      $req_params = ['headers' => $headers];
      if (!empty($body)) {
        if (strtolower($method) === 'get') {
          $req_params['query'] = $body;
        }
      }
      if ($this->debug) {
        print $method . ' ' . $url . ': ' .
          print_r($req_params, TRUE) . PHP_EOL;
      }
      try {
        $result = $this->client->request(
          strtoupper($method),
          $url,
          $req_params
        );
        $this->lastHeaders = $result->getHeaders();
        $body = $result->getBody()->getContents();
        if ($this->debug) {
          print $result->getStatusCode() . ': ' .
            strlen($body) . ', ' .
            print_r($this->lastHeaders, TRUE) . PHP_EOL;
        }
        return $body;
      }
      catch (TransferException $e) {
        if ($this->debug) {
          print $method . ' ' . $url . ': ' .
            $e->getResponse()->getStatusCode() . ': ' .
            $e->getMessage() . PHP_EOL;
        }
        $this->lastHeaders = $e->getResponse()->getHeaders();
        $this->errors[] = $e->getResponse()->getStatusCode() . ': ' .
          $e->getMessage();
        return FALSE;
      }
    });
  }

}
