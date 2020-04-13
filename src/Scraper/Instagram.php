<?php

namespace Rtrvrtg\SocialScraper\Scraper;

use Rtrvrtg\SocialScraper\Scraper\GenericScraper;
use Rtrvrtg\SocialScraper\Scraper\Instagram\QueryGenerator;
use Rtrvrtg\SocialScraper\Post;
use Rtrvrtg\SocialScraper\PostList;

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
   * Variables from Instagram Commons JS file.
   *
   * @var array
   */
  protected $commonsVars = [];

  /**
   * Referrer string for each request.
   *
   * @var string
   */
  protected $referrer;

  /**
   * Query generator.
   *
   * @var Rtrvrtg\SocialScraper\Scraper\Instagram\QueryGenerator
   */
  protected $queryGen;

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();
    $this->queryGen = new QueryGenerator();
  }

  /**
   * {@inheritdoc}
   */
  protected function doHttpHeaders() {
    $headers = parent::doHttpHeaders();
    if (!empty($this->commonsVars['instagramWebDesktopFBAppId'])) {
      $headers['X-IG-App-ID'] = $this->commonsVars['instagramWebDesktopFBAppId'];
    }
    if (!empty($this->commonsVars['csrfToken'])) {
      $headers['X-CSRFToken'] = $this->commonsVars['csrfToken'];
    }
    if (!empty($this->commonsVars['rolloutHash'])) {
      $headers['X-Instagram-AJAX'] = $this->commonsVars['rolloutHash'];
    }
    if (!empty($this->commonsVars['igWWWClaim'])) {
      $headers['X-IG-WWW-Claim'] = $this->commonsVars['igWWWClaim'];
    }
    if (!empty($this->commonsVars['igGis'])) {
      $headers['X-Instagram-GIS'] = $this->commonsVars['igGis'];
    }
    if (!empty($this->referrer)) {
      $headers['Referer'] = $this->referrer;
    }
    return $headers;
  }

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
    $is_json = FALSE;
    $method = 'GET';
    $url = 'https://instagram.com/' . $user_name . '/';
    $body = $this->doHttp($method, $url);
    if (!empty($cursor)) {
      $this->extractUserInfoHtml($body);
      $is_json = TRUE;
      $this->referrer = $url;
      $commons_js_url = $this->findConsumerLibCommonsJsUrl($body);
      $profile_js_url = $this->findProfilePageContainerJsUrl($body);
      if ($this->debug) {
        print_r([
          'commons_url' => $commons_js_url,
          'module_url' => $profile_js_url,
        ]);
        print PHP_EOL;
      }
      $this->setInstagramCommonsVars($commons_js_url, $profile_js_url, $body);
      $user_info = $this->getUserInfo($user_name);
      if (empty($user_info)) {
        throw new \Exception('Instagram::userList: Could not obtain user id for username ' . $user_name);
      }
      $body = $this->graphLookup('user', $user_info['id'], 20, $cursor);
    }
    return $this->decodeUserListHtml($body, $is_json);
  }

  /**
   * Fetches a list of posts for a hashtag.
   */
  public function hashtagList(string $hashtag, $cursor = NULL) {
    $is_json = FALSE;
    $method = 'GET';
    $url = 'https://www.instagram.com/explore/tags/' . urlencode($hashtag) . '/';
    $body = $this->doHttp($method, $url);
    if (!empty($cursor)) {
      $is_json = TRUE;
      $this->referrer = $url;
      $commons_js_url = $this->findConsumerLibCommonsJsUrl($body);
      $tag_js_url = $this->findTagPageContainerJsUrl($body);
      if ($this->debug) {
        print_r([
          'commons_url' => $commons_js_url,
          'module_url' => $tag_js_url,
        ]);
        print PHP_EOL;
      }
      $this->setInstagramCommonsVars($commons_js_url, $tag_js_url, $body);
      $body = $this->graphLookup('hashtag', $hashtag, 20, $cursor);
    }
    return $this->decodeTagListHtml($body, $is_json);
  }

  /**
   * Do a GraphQL lookup.
   */
  protected function graphLookup($lookup_type, $lookup_value, $limit = 20, $cursor = NULL) {
    $query = [];
    if ($lookup_type === 'hashtag') {
      $query = $this->queryGen->hashtagQuery($lookup_value, $cursor, $limit);
    }
    elseif ($lookup_type === 'user') {
      $query = $this->queryGen->userQuery($lookup_value, $cursor, $limit);
    }

    if (empty($query)) {
      return FALSE;
    }

    $this->commonsVars['igGis'] = md5(
      ($this->commonsVars['igGis'] ?? '') .
      ':' .
      $query['query']['variables'] ?? '{}'
    );

    return $this->doHttp($query['method'], $query['url'], $query['query']);
  }

  /**
   * Extract user info.
   */
  protected function extractUserInfoHtml($body) {
    $parsed = $this->findSharedData($body);

    // Set user data on first user page load.
    if (!empty($parsed['entry_data']['ProfilePage'][0]['graphql']['user'])) {
      $this->setUserInfo($parsed['entry_data']['ProfilePage'][0]['graphql']['user']);
    }
  }

  /**
   * Decodes a getPost request.
   */
  protected function decodeUserListHtml($body, bool $is_json = FALSE) {
    $parsed = (
      $is_json ?
      json_decode($body, TRUE) :
      $this->findSharedData($body)
    );

    // Set user data on first user page load.
    if (
      !$is_json &&
      !empty($parsed['entry_data']['ProfilePage'][0]['graphql']['user'])
    ) {
      $this->setUserInfo($parsed['entry_data']['ProfilePage'][0]['graphql']['user']);
    }

    $user_data = [];
    if (
      !empty($parsed) &&
      !empty($parsed['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media'])
    ) {
      $user_data = $parsed['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media'];
    }
    elseif (
      !empty($parsed) &&
      !empty($parsed['data']['user'])
    ) {
      $user_data = $parsed['data']['user']['edge_owner_to_timeline_media'];
    }

    if (!empty($user_data)) {
      $posts = array_map(function ($e) {
        $node = $e['node'];
        list($images, $videos) = $this->postMedia($node);
        $user_info = $this->getPostUserInfo($node) ?? [
          'userName' => $node['owner']['username'],
          'userDisplayName' => '',
          'userUrl' => 'https://instagram.com/' . $node['owner']['username'] . '/',
          'userAvatarUrl' => '',
        ];
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
          'accessibilityCaption' => $node['accessibility_caption'] ?? '',
          'images' => $images,
          'videos' => $videos,
          'intents' => [],
          'raw' => $node,
        ]);

        return $post;
      }, $user_data['edges'] ?? []);

      return new PostList([
        'service' => 'instagram',
        'posts' => $posts,
        'nextCursor' => $user_data['end_cursor'] ?? NULL,
      ]);
    }

    return NULL;
  }

  /**
   * Get post user information from a cache, or one of their posts.
   */
  protected function getPostUserInfo($node) {
    $user_id = $node['owner']['id'];

    list($cache_bucket, $cache_key) = $this->cacheBucketKey('getPostUserInfo', $user_id);
    return $this->useCache($cache_bucket, $cache_key, function () use ($user_id, $node) {
      if (
        empty($this->userIdCache[$user_id]) ||
        empty($this->userIdCache[$user_id]['userName'])
      ) {
        $user_info = [
          'id' => NULL,
          'userName' => NULL,
          'userDisplayName' => NULL,
          'userUrl' => NULL,
          'userAvatarUrl' => NULL,
        ];
        $post = $this->getPost($node['shortcode']);
        if (!empty($post)) {
          $user_info = [
            'id' => $user_id,
            'userName' => $post->userName,
            'userDisplayName' => $post->userDisplayName,
            'userUrl' => $post->userUrl,
            'userAvatarUrl' => $post->userAvatarUrl,
          ];
        }
        else {
          $this->errors[] = 'Instagram::getPostUserInfo: No post found for shortcode ' . $node['shortcode'];
        }
        if (!empty($user_info)) {
          $this->userIdCache[$user_id] = $user_info;
        }
      }
      return $this->userIdCache[$user_id] ?? NULL;
    });
  }

  /**
   * Set user info from user page data.
   */
  protected function setUserInfo(array $user_data) {
    $user_info = [
      'id' => $user_data['id'],
      'userName' => $user_data['username'],
      'userDisplayName' => $user_data['full_name'] ?? '',
      'userUrl' => 'https://instagram.com/' . $user_data['username'] . '/',
      'userAvatarUrl' => $user_data['profile_pic_url'],
    ];
    $this->userIdCache[$user_info['id']] = $user_info;
  }

  /**
   * Get user info by username.
   */
  protected function getUserInfo(string $username) {
    $candidates = array_filter($this->userIdCache, function ($i) use ($username) {
      return $i['userName'] == $username;
    });
    return !empty($candidates) ? reset($candidates) : NULL;
  }

  /**
   * Decodes a hashtagList request.
   */
  protected function decodeTagListHtml($body, bool $is_json = FALSE) {
    $parsed = (
      $is_json ?
      json_decode($body, TRUE) :
      $this->findSharedData($body)
    );

    $hashtag_data = [];
    if (
      !empty($parsed) &&
      !empty($parsed['entry_data']['TagPage'][0]['graphql']['hashtag']['edge_hashtag_to_media'])
    ) {
      $hashtag_data = $parsed['entry_data']['TagPage'][0]['graphql']['hashtag']['edge_hashtag_to_media'];
    }
    elseif (
      !empty($parsed) &&
      !empty($parsed['data']['hashtag']['edge_hashtag_to_media'])
    ) {
      $hashtag_data = $parsed['data']['hashtag']['edge_hashtag_to_media'];
    }

    if (!empty($hashtag_data)) {
      $posts = array_map(function ($e) {
        $node = $e['node'];
        list($images, $videos) = $this->postMedia($node);
        $user_info = $this->getPostUserInfo($node) ?? [
          'userName' => $node['owner']['username'],
          'userDisplayName' => '',
          'userUrl' => 'https://instagram.com/' . $node['owner']['username'] . '/',
          'userAvatarUrl' => '',
        ];
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
          'accessibilityCaption' => $node['accessibility_caption'] ?? '',
          'images' => $images,
          'videos' => $videos,
          'intents' => [],
          'raw' => $node,
        ]);

        return $post;
      }, $hashtag_data['edges'] ?? []);

      return new PostList([
        'service' => 'instagram',
        'posts' => $posts,
        'nextCursor' => $hashtag_data['end_cursor'] ?? NULL,
      ]);
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

  /**
   * Find the main JS URL.
   */
  protected function findConsumerLibCommonsJsUrl($body) {
    $reg = '/<script.+?src="(\/static\/bundles\/es6\/ConsumerLibCommons\.js\/[a-f0-9]+\.js)".*?><\/script>/';

    $matched = preg_match($reg, $body, $matches);
    if ($matched) {
      return 'https://www.instagram.com' . $matches[1];
    }
    return NULL;
  }

  /**
   * Find the profile page JS URL.
   */
  protected function findProfilePageContainerJsUrl($body) {
    $reg = '/<script.+?src="(\/static\/bundles\/es6\/ProfilePageContainer\.js\/[a-f0-9]+\.js)".*?><\/script>/';

    $matched = preg_match($reg, $body, $matches);
    if ($matched) {
      return 'https://www.instagram.com' . $matches[1];
    }
    return NULL;
  }

  /**
   * Find the profile page JS URL.
   */
  protected function findTagPageContainerJsUrl($body) {
    $reg = '/<script.+?src="(\/static\/bundles\/es6\/TagPageContainer\.js\/[a-f0-9]+\.js)".*?><\/script>/';

    $matched = preg_match($reg, $body, $matches);
    if ($matched) {
      return 'https://www.instagram.com' . $matches[1];
    }
    return NULL;
  }

  /**
   * Find variables from the Instagram Commons JS file.
   */
  protected function setInstagramCommonsVars($commons_url, $module_url, $body) {
    $commons_body = $this->doHttp('get', $commons_url);
    $module_body = $this->doHttp('get', $module_url);
    $vars = [
      'instagramWebDesktopFBAppId' => NULL,
      'csrfToken' => NULL,
      'igWWWClaim' => NULL,
      'rolloutHash' => NULL,
      'queryId' => NULL,
      'igGis' => $this->commonsVars['igGis'] ?? NULL,
    ];

    $web_desktop_fb_app_id_reg = '/e\.instagramWebDesktopFBAppId=\'([0-9]+)\'/';
    $wdaid_matched = preg_match($web_desktop_fb_app_id_reg, $commons_body, $wdaid_matches);
    if ($wdaid_matched) {
      $vars['instagramWebDesktopFBAppId'] = $wdaid_matches[1];
    }

    $shared_data = $this->findSharedData($body);
    if (!empty($shared_data['config']['csrf_token'])) {
      $vars['csrfToken'] = $shared_data['config']['csrf_token'];
    }
    if (!empty($shared_data['config']['rollout_hash'])) {
      $vars['rolloutHash'] = $shared_data['config']['rollout_hash'];
    }

    if (!empty($this->lastHeaders['x-ig-set-www-claim'])) {
      $vars['igWWWClaim'] = $this->lastHeaders['x-ig-set-www-claim'];
    }

    $this->commonsVars = $vars;
  }

}
