<?php

namespace Rtrvrtg\SocialScraper;

use Rtrvrtg\SocialScraper\Scraper\GenericScraper;

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
      return json_decode($result, TRUE);
    }
    return FALSE;
  }

  /**
   * Fetches a list of user posts.
   */
  public function userList() {
    $this->referrer = 'https://twitter.com/' . $user;
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
