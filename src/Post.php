<?php

namespace Rtrvrtg\SocialScraper;

/**
 * A social media post.
 */
class Post {

  /**
   * Name of the service used.
   *
   * @var string
   */
  protected $service;

  /**
   * ID of the post.
   *
   * @var string
   */
  protected $postId;

  /**
   * URL of the post.
   *
   * @var string
   */
  protected $postUrl;

  /**
   * Name of the user that posted this post.
   *
   * @var string
   */
  protected $userName;

  /**
   * Display name for the user.
   *
   * @var string
   */
  protected $userDisplayName;

  /**
   * URL of the user that posted this post.
   *
   * @var string
   */
  protected $userUrl;

  /**
   * URL of the user's avatar.
   *
   * @var string
   */
  protected $userAvatarUrl;

  /**
   * Created timestamp for this post.
   *
   * @var int
   */
  protected $created;

  /**
   * Text content of this post.
   *
   * @var string
   */
  protected $text;

  /**
   * Accessibility caption for this post's media.
   *
   * @var string
   */
  protected $accessibilityCaption;

  /**
   * All images referenced in this post.
   *
   * @var array
   */
  protected $images;

  /**
   * All videos referenced in this post.
   *
   * @var string
   */
  protected $videos;

  /**
   * Intents for this post.
   *
   * @var string
   */
  protected $intents;

  /**
   * Raw post data.
   *
   * @var mixed
   */
  protected $raw;

  /**
   * Constructor.
   */
  public function __construct(array $props = []) {
    foreach ($props as $prop => $value) {
      $this->__set($prop, $value);
    }
  }

  /**
   * Magic get function.
   */
  public function __get($prop) {
    if (property_exists($this, $prop) && !empty($this->{$prop})) {
      return $this->{$prop};
    }
  }

  /**
   * Magic set function.
   */
  public function __set($prop, $value) {
    if (property_exists($this, $prop)) {
      $this->{$prop} = $value;
    }
  }

  /**
   * Magic isset function.
   */
  public function __isset($prop) {
    return property_exists($this, $prop) && !empty($this->{$prop});
  }

}
