<?php

namespace Rtrvrtg\SocialScraper\Scraper;

use Masterminds\HTML5;
use Rtrvrtg\SocialScraper\Scraper\GenericScraper;
use Rtrvrtg\SocialScraper\Post;
use Rtrvrtg\SocialScraper\PostList;

/**
 * Fetches posts from Twitter.
 */
class Twitter extends GenericScraper {

  /**
   * Bearer token for Authorization headers.
   *
   * @var string
   */
  protected $bearerToken;

  /**
   * Referrer string for each request.
   *
   * @var string
   */
  protected $referrer;

  /**
   * Is the current request a JSON request.
   *
   * @var bool
   */
  protected $isJson;

  /**
   * Cache linking usernames to user IDs.
   *
   * @var array
   */
  protected $userIdCache = [];

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->bearerToken = '';
  }

  /**
   * {@inheritdoc}
   */
  protected function doHttpHeaders() {
    $headers = parent::doHttpHeaders();
    if ($this->isJson) {
      $headers['Accept'] = 'application/json, text/javascript, */*; q=0.01';
    }
    $headers['X-Twitter-Active-User'] = 'yes';
    $headers['X-Requested-With'] = 'XMLHttpRequest';
    $headers['Referer'] = $this->referrer;
    if (!empty($this->bearerToken)) {
      $headers['Authorization'] = 'Bearer ' . $this->bearerToken;
    }
    return $headers;
  }

  /**
   * Fetches a single post.
   */
  public function getPost($user, $post_id) {
    $this->referrer = 'https://twitter.com/' . $user . '/status/' . $post_id;
    $this->doBootstrapQuery('status', $user, $post_id);
    $result = $this->doHttp('get', 'https://api.twitter.com/2/timeline/conversation/' . $post_id . '.json?include_profile_interstitial_type=1&include_blocking=1&include_blocked_by=1&include_followed_by=1&include_want_retweets=1&include_mute_edge=1&include_can_dm=1&include_can_media_tag=1&skip_status=1&cards_platform=Web-12&include_cards=1&include_composer_source=true&include_ext_alt_text=true&include_reply_count=1&tweet_mode=extended&include_entities=true&include_user_entities=true&include_ext_media_color=true&include_ext_media_availability=true&send_error_codes=true&simple_quoted_tweets=true&count=20&ext=mediaStats%2ChighlightedLabel%2CcameraMoment');

    if (!empty($result)) {
      $raw = json_decode($result, TRUE);
      $tweet = $raw['globalObjects']['tweets'][$post_id];
      $user_id = $tweet['user_id'];
      $user_info = $raw['globalObjects']['users'][$user_id];

      return new Post([
        'service' => 'twitter',
        'postId' => $tweet['id_str'],
        'postUrl' => 'https://twitter.com/' . $user . '/status/' . $post_id,
        'userName' => $user_info['screen_name'],
        'userDisplayName' => $user_info['name'],
        'userUrl' => 'https://twitter.com/' . $user_info['screen_name'],
        'userAvatarUrl' => $user_info['profile_image_url_https'],
        'created' => strtotime($tweet['created_at']),
        'text' => $tweet['full_text'],
        'accessibilityCaption' => '',
        'images' => [],
        'videos' => [],
        'intents' => [],
        'raw' => $tweet,
      ]);
    }
    return FALSE;
  }

  /**
   * Fetches a list of user posts.
   */
  public function userList($user_name) {
    $this->referrer = 'https://twitter.com/' . $user_name;
    $this->doBootstrapQuery('profile', $user_name);
    $user_id = '';
    $cursor = '';
    $user_id = $this->userIdCache[$user_name];
    $result = $this->doHttp('get', 'https://twitter.com/i/profiles/show/' . $user_name . '/timeline/tweets?' . (!empty($cursor) ? 'cursor=' . $cursor : ''));

    if (!empty($result)) {
      $raw = json_decode($result, TRUE);

      // Necessary to get XPath to work.
      // @see https://github.com/Masterminds/html5-php/issues/123
      $html5 = new HTML5([
        'disable_html_ns' => TRUE,
      ]);
      $dom = $html5->loadHTML($raw['items_html'] ?? '');

      $xpath = new \DOMXPath($dom);
      $post_items = $xpath->query("//li[contains(@class, 'stream-item')]");
      $posts = [];
      if ($post_items->count() > 0) {
        foreach ($post_items as $post_item) {
          $post_id = $post_item->getAttribute('data-item-id');

          $internal_tweet = $xpath->query(
            './div[contains(@class, "js-stream-tweet")]',
            $post_item
          );
          print $internal_tweet[0]->ownerDocument->saveXML($internal_tweet[0]) . PHP_EOL;
          $text_content = $xpath->query(
            './div[@class="content"]//p[contains(@class, "tweet-text")]',
            $internal_tweet[0]
          );
          $avatar = $xpath->query(
            './div[@class="content"]//img[contains(@class, "js-action-profile-avatar")]',
            $internal_tweet[0]
          );
          $timestamp = $xpath->query(
            './div[@class="content"]//span[contains(@class, "js-short-timestamp")]',
            $internal_tweet[0]
          );

          $post_user_name = $internal_tweet[0]->getAttribute('data-screen-name');
          $post_user_display_name = $internal_tweet[0]->getAttribute('data-name');

          $posts[] = new Post([
            'service' => 'twitter',
            'postId' => $post_id,
            'postUrl' => 'https://twitter.com/' . $post_user_name . '/status/' . $post_id,
            'userName' => $post_user_name,
            'userDisplayName' => $post_user_display_name,
            'userUrl' => 'https://twitter.com/' . $post_user_name,
            'userAvatarUrl' => $avatar[0]->getAttribute('src'),
            'created' => intval($timestamp[0]->getAttribute('data-time')),
            'text' => $text_content->count() > 0 ? $text_content[0]->textContent : '',
            'accessibilityCaption' => '',
            'images' => [],
            'videos' => [],
            'intents' => [],
            'raw' => $post_item->ownerDocument->saveXML($post_item),
          ]);
        }
      }

      return new PostList([
        'posts' => $posts,
        'prevCursor' => $raw['min_position'],
        'nextCursor' => $raw['max_position'],
      ]);
    }
    return FALSE;
  }

  /**
   * Fetches a list of posts for a hashtag.
   */
  public function hashtagList() {
    $this->referrer = 'https://search.twitter.com/';
  }

  /**
   * Do a bootstrap query to get a session.
   *
   * 1. Get page.
   * 2. Get main.*.js.
   * 3. Extract bearer token.
   */
  protected function doBootstrapQuery($type = 'hashtag', $user = '', $query = '') {
    if (!empty($this->bearerToken)) {
      return;
    }

    $markup = '';
    $this->isJson = FALSE;
    if ($type === 'status') {
      $markup = $this->doHttp('GET', 'https://twitter.com/' . $user . '/status/' . $query . '?lang=en');
    }
    elseif ($type === 'profile') {
      $markup = $this->doHttp('GET', 'https://twitter.com/i/profiles/show/' . $user . '/timeline/tweets');
      $user_id = $this->findUserIdInHtml($markup, $user);
      if (!empty($user_id)) {
        $this->userIdCache[$user] = $user_id;
      }
    }
    else {
      $markup = $this->doHttp('GET', 'https://twitter.com/i/search/timeline?f=tweets&vertical=default&q=' . $query . '&src=tyah&reset_error_state=false');
    }

    $main_js_url = $this->findMainJsUrl($markup);
    if (empty($main_js_url)) {
      return FALSE;
    }

    $this->isJson = TRUE;
    $main_js = $this->doHttp('GET', $main_js_url);
    $bearer_token = $this->findBearerToken($main_js);
    if (!empty($bearer_token)) {
      $this->bearerToken = $bearer_token;
    }
  }

  /**
   * Find the main JS URL.
   */
  protected function findUserIdInHtml($body, $user) {
    $reg = '/a.+?class=\\\\".*?js-action-profile.*?\\\\".+?href=\\\\"\\\\\/' . $user . '\\\\".+?data-user-id=\\\\"([0-9]+)\\\\"/';
    $matched = preg_match($reg, $body, $matches);
    if ($matched) {
      return $matches[1];
    }
    return NULL;
  }

  /**
   * Find the main JS URL.
   */
  protected function findMainJsUrl($body) {
    $reg = '/<script.+?src="(https:\/\/[a-z]+\.twimg\.com\/k\/[a-z]{2}\/init\.[a-z]{2}\.[a-z0-9]+\.js)".*?><\/script>/';
    $matched = preg_match($reg, $body, $matches);
    if ($matched) {
      return $matches[1];
    }
    return NULL;
  }

  /**
   * Find the bearer token.
   */
  protected function findBearerToken($body) {
    $reg = '/[a-zA-Z]+="([a-zA-Z0-9%]{96,})"/';
    $matched = preg_match($reg, $body, $matches);
    if ($matched) {
      return $matches[1];
    }
    return NULL;
  }

}
