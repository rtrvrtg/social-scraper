<?php

namespace Rtrvrtg\SocialScraper\Scraper;

use Rtrvrtg\SocialScraper\Scraper\GenericScraper;

/**
 * Fetches posts from Instagram.
 */
class Instagram extends GenericScraper {

  /**
   * Fetches a single post.
   */
  public function getPost($post_shortcode) {
    $method = 'GET';
    $url = 'https://instagram.com/p/' . $post_shortcode . '/';
    $body = $this->doHttp($method, $url);
    return $this->decodePostHtml($body);
  }

  /**
   * Fetches a list of user posts.
   */
  public function userList($user_name) {
    $method = 'GET';
    $url = 'https://instagram.com/' . $user_name . '/';
    $body = $this->doHttp($method, $url);
    return $this->decodeUserListHtml($body);
  }

  /**
   * Fetches a list of posts for a hashtag.
   */
  public function hashtagList() {}

  /**
   * Decodes a getPost request.
   */
  protected function decodeUserListHtml($body) {
    $parsed = $this->findSharedData($body);

    if (
      !empty($parsed) &&
      !empty($parsed['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'])
    ) {
      return array_map(function ($e) {
        return $e['node'];
      }, $parsed['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges']);
    }

    return NULL;
  }

  /**
   * Decodes a getPost request.
   */
  protected function decodePostHtml($body) {
    $parsed = $this->findSharedData($body);

    if (
      !empty($parsed) &&
      !empty($parsed['entry_data']['PostPage'][0]['media'])
    ) {
      return $parsed['entry_data']['PostPage'][0]['media'];
    }
    elseif (
      !empty($parsed) &&
      !empty($parsed['entry_data']['PostPage'][0]['graphql']['shortcode_media'])
    ) {
      return $parsed['entry_data']['PostPage'][0]['graphql']['shortcode_media'];
    }

    return NULL;
  }

  /**
   * Find the sharedData JS blob.
   */
  protected function findSharedData($body) {
    $reg = '/<script type="text\/javascript">window\._sharedData = (.+?)(;)?<\/script>/';
    $matched = preg_match($reg, $body, $matches);
    if ($matched) {
      return json_decode($matches[1] ?? '{}', TRUE);
    }
    return [];
  }

}
