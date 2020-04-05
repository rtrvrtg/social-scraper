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
   * Constructor.
   */
  public function __construct() {
    $this->client = new Client([
      'cookies' => TRUE,
    ]);
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
   * Headers for HTTP requests.
   */
  protected function doHttpHeaders() {
    return [
      'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3506.71 Safari/537.36',
    ];
  }

  /**
   * Do HTTP request.
   */
  protected function doHttp($method, $url) {
    $headers = $this->doHttpHeaders();
    try {
      $result = $this->client->request(
        strtoupper($method),
        $url,
        ['headers' => $headers]
      );
      return $result->getBody()->getContents();
    }
    catch (TransferException $e) {
      $this->errors[] = $e->getResponse()->getStatusCode() . ': ' . $e->getMessage();
      return FALSE;
    }
  }

}
