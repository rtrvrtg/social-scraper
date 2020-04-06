<?php

namespace Rtrvrtg\SocialScraper\Scraper;

use Rtrvrtg\SocialScraper\Scraper\GenericScraper;
use Rtrvrtg\SocialScraper\Post;

/**
 * Fetches posts from Instagram.
 */
class Instagram extends GenericScraper {

  /**
   * Cache linking usernames to user IDs.
   *
   * @var array
   */
  protected $userIdCache = [];

  /**
   * Fetches a single post.
   */
  public function getPost(string $post_shortcode) {
    $method = 'GET';
    $url = 'https://instagram.com/p/' . $post_shortcode . '/';
    $body = $this->doHttp($method, $url);
    return $this->decodePostHtml($body);
  }

  /**
   * Fetches a list of user posts.
   */
  public function userList(string $user_name, $cursor = NULL) {
    $method = 'GET';
    $url = 'https://instagram.com/' . $user_name . '/';
    $body = $this->doHttp($method, $url);
    return $this->decodeUserListHtml($body);
  }

  /**
   * Fetches a list of posts for a hashtag.
   */
  public function hashtagList(string $hashtag, $cursor = NULL) {
    $method = 'GET';
    $url = 'https://www.instagram.com/explore/tags/' . urlencode($hashtag) . '/';
    $body = $this->doHttp($method, $url);
    return $this->decodeTagListHtml($body);
  }

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
        $node = $e['node'];
        list($images, $videos) = $this->postMedia($node);
        var_dump($node);
        die();
        $post = new Post([
          'service' => 'instagram',
          'postId' => $node['id'],
          'postUrl' => 'https://instagram.com/p/' . $node['shortcode'] . '/',
          'userName' => $node['owner']['username'],
          'userDisplayName' => '',
          'userUrl' => 'https://instagram.com/' . $node['owner']['username'] . '/',
          'userAvatarUrl' => '',
          'created' => $node['taken_at_timestamp'],
          'text' => $node['edge_media_to_caption']['edges'][0]['node']['text'] ?? '',
          'accessibilityCaption' => $node['accessibility_caption'],
          'images' => $images,
          'videos' => $videos,
          'intents' => [],
          'raw' => $node,
        ]);

        return $post;
      }, $parsed['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges']);
    }

    return NULL;
  }

  /**
   * Get post user information from a cache, or one of their posts.
   */
  protected function getPostUserInfo($node) {
    $user_id = $node['owner']['id'];
    if (!empty($this->userIdCache[$user_id])) {
      return $this->userIdCache[$user_id];
    }
    $post = $this->getPost($node['shortcode']);
    $this->userIdCache[$user_id] = [
      'userName' => $post->userName,
      'userDisplayName' => $post->userDisplayName,
      'userUrl' => $post->userUrl,
      'userAvatarUrl' => $post->userAvatarUrl,
    ];
    return $this->userIdCache[$user_id];
  }

  /**
   * Decodes a hashtagList request.
   */
  protected function decodeTagListHtml($body) {
    $parsed = $this->findSharedData($body);

    if (
      !empty($parsed) &&
      !empty($parsed['entry_data']['TagPage'][0]['graphql']['hashtag']['edge_hashtag_to_media']['edges'])
    ) {
      return array_map(function ($e) {
        $node = $e['node'];
        list($images, $videos) = $this->postMedia($node);
        $user_info = $this->getPostUserInfo($node);
        $post = new Post([
          'service' => 'instagram',
          'postId' => $node['id'],
          'postUrl' => 'https://instagram.com/p/' . $node['shortcode'] . '/',
          'userName' => $user_info['userName'],
          'userDisplayName' => $user_info['userDisplayName'],
          'userUrl' => $user_info['userUrl'],
          'userAvatarUrl' => $user_info['userAvatarUrl'],
          'created' => $node['taken_at_timestamp'],
          'text' => $node['edge_media_to_caption']['edges'][0]['node']['text'] ?? '',
          'accessibilityCaption' => $node['accessibility_caption'],
          'images' => $images,
          'videos' => $videos,
          'intents' => [],
          'raw' => $node,
        ]);

        return $post;
      }, $parsed['entry_data']['TagPage'][0]['graphql']['hashtag']['edge_hashtag_to_media']['edges']);
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
      $base = $parsed['entry_data']['PostPage'][0]['media'];
      list($images, $videos) = $this->postMedia($base);
      $user_info = $this->getPostUserInfo($node);
      return new Post([
        'service' => 'instagram',
        'postId' => $base['id'],
        'postUrl' => 'https://instagram.com/p/' . $base['shortcode'] . '/',
        'userName' => $user_info['userName'],
        'userDisplayName' => $user_info['userDisplayName'],
        'userUrl' => $user_info['userUrl'],
        'userAvatarUrl' => $user_info['userAvatarUrl'],
        'created' => $base['taken_at_timestamp'],
        'text' => $base['edge_media_to_caption']['edges'][0]['node']['text'] ?? '',
        'accessibilityCaption' => $base['accessibility_caption'] ?? '',
        'images' => $images,
        'videos' => $videos,
        'intents' => [],
        'raw' => $base,
      ]);
    }
    elseif (
      !empty($parsed) &&
      !empty($parsed['entry_data']['PostPage'][0]['graphql']['shortcode_media'])
    ) {
      $base = $parsed['entry_data']['PostPage'][0]['graphql']['shortcode_media'];
      list($images, $videos) = $this->postMedia($base);
      return new Post([
        'service' => 'instagram',
        'postId' => $base['id'],
        'postUrl' => 'https://instagram.com/p/' . $base['shortcode'] . '/',
        'userName' => $base['owner']['username'],
        'userDisplayName' => '',
        'userUrl' => 'https://instagram.com/' . $base['owner']['username'] . '/',
        'userAvatarUrl' => '',
        'created' => $base['taken_at_timestamp'],
        'text' => $base['edge_media_to_caption']['edges'][0]['node']['text'] ?? '',
        'accessibilityCaption' => $base['accessibility_caption'] ?? '',
        'images' => $images,
        'videos' => $videos,
        'intents' => [],
        'raw' => $base,
      ]);
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

  /**
   * Get list of all images on a post.
   */
  protected function postMedia($post) {
    $images = [];
    $videos = [];
    if (!empty($post['edge_sidecar_to_children']['edges'])) {
      foreach ($post['edge_sidecar_to_children']['edges'] as $edge) {
        $node = $edge['node'];
        if ($node['__typename'] === 'GraphVideo') {
          $videos[] = $node['video_url'];
        }
        elseif ($node['__typename'] === 'GraphImage') {
          $images[] = $node['display_url'];
        }
      }
    }
    elseif (!empty($post['images']['standard_resolution'])) {
      $images = [$post['images']['standard_resolution']['url']];
    }
    elseif (!empty($post['display_src'])) {
      $images = [$post['display_src']];
    }
    elseif (!empty($post['display_url'])) {
      $images = [$post['display_url']];
    }
    return [$images, $videos];
  }

}
