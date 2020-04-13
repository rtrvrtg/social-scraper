<?php

namespace Rtrvrtg\SocialScraper\Scraper\Instagram;

/**
 * Fetches posts from Instagram.
 */
class QueryGenerator {

  const BASE_URL = 'https://www.instagram.com';

  /**
   * Generates a query for a hashtag.
   */
  public function hashtagQuery($hashtag, $cursor = NULL, $limit = 50) {
    $variables = [
      'tag_name' => $hashtag,
      'first' => $limit,
      'after' => $cursor,
    ];
    return [
      'method' => 'GET',
      'url' => static::BASE_URL . '/graphql/query/',
      'query' => [
        'query_hash' => 'ded47faa9a1aaded10161a2ff32abb6b',
        'variables' => json_encode($variables),
      ],
    ];
  }

  /**
   * Generates a query for a user.
   */
  public function userQuery($user_name, $cursor = NULL, $limit = 50) {
    $variables = [
      'id' => $user_name,
      'first' => $limit,
      'after' => $cursor,
    ];
    return [
      'method' => 'GET',
      'url' => static::BASE_URL . '/graphql/query/',
      'query' => [
        'query_hash' => '42323d64886122307be10013ad2dcc44',
        'variables' => json_encode($variables),
      ],
    ];
  }

}
