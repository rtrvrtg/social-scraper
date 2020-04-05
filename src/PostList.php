<?php

namespace Rtrvrtg\SocialScraper;

/**
 * A list of social media posts.
 */
class PostList {

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
  public function __get($name) {
    if (property_exists($this, $name) && !empty($this->${name})) {
      return $this->${name};
    }
  }

  /**
   * Magic set function.
   */
  public function __set($name, $value) {
    if (property_exists($this, $name)) {
      $this->${name} = $value;
    }
  }

  /**
   * Magic isset function.
   */
  public function __isset($name) {
    return property_exists($this, $name) && !empty($this->${name});
  }

}
