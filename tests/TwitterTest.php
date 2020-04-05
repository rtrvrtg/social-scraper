<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Rtrvrtg\SocialScraper\Scraper\Twitter;

/**
 * Tests Twitter scraping.
 */
final class TwitterTest extends TestCase {

  /**
   * Test getting a single post.
   */
  public function testGetSinglePost() {
    $i = new Twitter();
    $result = $i->getPost('dril', '922321981');
    $this->assertEquals('922321981', $result->postId);
    $this->assertEquals([], $i->getErrors());
  }

  /**
   * Test getting a user's posts.
   */
  public function testGetUserPosts() {
    $i = new Twitter();
    $result = $i->userList('dril');
    var_dump($result);
    $this->assertNotEmpty($result);
    $this->assertEquals([], $i->getErrors());
  }

}
