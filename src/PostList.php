<?php

namespace Rtrvrtg\SocialScraper;

/**
 * A list of social media posts.
 */
class PostList {

  /**
   * Name of the service used.
   *
   * @var string
   */
  protected $service;

  /**
   * List of all the posts.
   *
   * @var array
   */
  protected $posts;

  /**
   * Key used for the previous page of posts.
   *
   * @var string
   */
  protected $prevCursor;

  /**
   * Key used for the next page of posts.
   *
   * @var string
   */
  protected $nextCursor;

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

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    return json_encode([
      'service' => $this->service,
      'posts' => $this->posts,
      'prevCursor' => $this->prevCursor,
      'nextCursor' => $this->nextCursor,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    $props = json_decode($serialized, TRUE);
    foreach ($props as $prop => $value) {
      $this->__set($prop, $value);
    }
  }

}
