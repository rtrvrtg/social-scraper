<?php

namespace Rtrvrtg\SocialScraper\Scraper;

use GuzzleHttp\Client;

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
   * Constructor.
   */
  public function __construct() {
    $this->client = new Client(['cookies' => TRUE]);
    $this->errors = [];
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
   * Do HTTP request.
   */
  protected function doHttp($method, $url) {
    try {
      $result = $this->client->request(strtoupper($method), $url);
      return $result->getBody();
    }
    catch (\Throwable $e) {
      $this->errors[] = $e->__toString();
      return FALSE;
    }
  }

}
