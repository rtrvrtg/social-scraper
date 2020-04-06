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
    $result = $this->doHttp('GET', 'https://twitter.com/' . $user . '/status/' . $post_id);

    if (!empty($result)) {
      // Necessary to get XPath to work.
      // @see https://github.com/Masterminds/html5-php/issues/123
      $html5 = new HTML5([
        'disable_html_ns' => TRUE,
      ]);
      $dom = $html5->loadHTML($result);
      $xpath = new \DOMXPath($dom);
      $stream_items = $xpath->query(
        '//*' . $this->xpathClass('permalink-tweet-container')
      );
      if ($stream_items->count() > 0) {
        return $this->parseStreamItem($xpath, $stream_items[0], 'permalink-tweet');
      }
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
    $result = $this->doHttp('GET', 'https://twitter.com/i/profiles/show/' . $user_name . '/timeline/tweets?' . (!empty($cursor) ? 'cursor=' . $cursor : ''));

    if (!empty($result)) {
      $raw = json_decode($result, TRUE);

      $posts = [];

      // Necessary to get XPath to work.
      // @see https://github.com/Masterminds/html5-php/issues/123
      $html5 = new HTML5([
        'disable_html_ns' => TRUE,
      ]);
      $dom = $html5->loadHTML($raw['items_html'] ?? '');
      $xpath = new \DOMXPath($dom);
      $stream_items = $xpath->query(
        '//*' . $this->xpathClass('stream-item')
      );
      if ($stream_items->count() > 0) {
        foreach ($stream_items as $stream_item) {
          $posts[] = $this->parseStreamItem($xpath, $stream_item);
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
   * Parse a HTML stream item as a tweet.
   */
  protected function parseStreamItem($xpath, $stream_item, $search_class = 'js-stream-tweet') {
    $post_id = $stream_item->getAttribute('data-item-id');

    $internal_tweet = $xpath->query(
      './div' . $this->xpathClass($search_class),
      $stream_item
    );
    if ($internal_tweet->count() === 0) {
      throw new \Exception(
        'Could not find class ' . $search_class . ' in following node: ' .
        $stream_item->ownerDocument->saveXML($stream_item)
      );
    }

    if (empty($post_id)) {
      $post_id = $internal_tweet[0]->getAttribute('data-item-id');
    }

    $content_container = NULL;
    $this->forEachClassName(
      function ($node) use (&$content_container) {
        $content_container = $node;
      },
      $xpath,
      './div',
      'content',
      $internal_tweet[0]
    );

    if (is_null($content_container)) {
      throw new \Exception(
        'Could not find class content in following node: ' .
        $internal_tweet[0]->ownerDocument->saveXML($internal_tweet[0])
      );
    }

    $text_content = '';
    $this->forEachClassName(
      function ($node) use (&$text_content) {
        $text_content .= $node->textContent;
      },
      $xpath,
      './/p',
      'tweet-text',
      $internal_tweet[0]
    );

    $avatar_url = '';
    $this->forEachClassName(
      function ($node) use (&$avatar_url) {
        $avatar_url = $node->getAttribute('src');
      },
      $xpath,
      './/img',
      'js-action-profile-avatar',
      $content_container
    );

    $timestamp = 0;
    $this->forEachClassName(
      function ($node) use (&$timestamp) {
        $timestamp = intval($node->getAttribute('data-time'));
      },
      $xpath,
      './/span',
      'js-short-timestamp',
      $content_container
    );

    $post_user_name = $internal_tweet[0]->getAttribute('data-screen-name');
    $post_user_display_name = $internal_tweet[0]->getAttribute('data-name');

    $images = [];
    $this->forEachClassName(
      function ($node) use (&$images) {
        $images[] = $node->getAttribute('data-image-url');
      },
      $xpath,
      './/div',
      'AdaptiveMedia-photoContainer',
      $internal_tweet[0]
    );

    return new Post([
      'service' => 'twitter',
      'postId' => $post_id,
      'postUrl' => 'https://twitter.com/' . $post_user_name . '/status/' . $post_id,
      'userName' => $post_user_name,
      'userDisplayName' => $post_user_display_name,
      'userUrl' => 'https://twitter.com/' . $post_user_name,
      'userAvatarUrl' => $avatar_url,
      'created' => $timestamp,
      'text' => $text_content,
      'accessibilityCaption' => '',
      'images' => $images,
      'videos' => [],
      'intents' => [],
      'raw' => $this->nodeXml($stream_item),
    ]);
  }

  /**
   * Dump node as XML.
   */
  protected function nodeXml($node) {
    return $node->ownerDocument->saveXML($node);
  }

  /**
   * Class XPath selectors.
   */
  protected function xpathClass(string $class_name) {
    return '[' . implode(' or ', [
      '@class="' . $class_name . '"',
      'starts-with(@class, "' . $class_name . ' ")',
      // ends-with is not supported in XPath 1.0.
      // 'ends-with(@class, " ' . $class_name . ' ")'.
      'contains(@class, " ' . $class_name . ' ")',
      'contains(@class, " ' . $class_name . '")',
    ]) . ']';
  }

  /**
   * Perform a callback for each node matching a given class name.
   */
  protected function forEachClassName(
    callable $for_each_func,
    $xpath,
    string $query_prefix,
    string $class_name,
    \DOMElement $context_node = NULL
  ) {
    $nodes = $xpath->query(
      $query_prefix . $this->xpathClass($class_name),
      $context_node
    );
    $count = 0;
    foreach ($nodes as $node) {
      $class_names = array_filter(explode(' ', $node->getAttribute('class') ?? ''));
      if (in_array($class_name, $class_names)) {
        $for_each_func($node);
        $count++;
      }
    }
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
