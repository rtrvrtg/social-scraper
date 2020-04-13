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
    $i->debug = FALSE;
    $result = $i->getPost('dril', '922321981');
    $this->assertEquals([], $i->getErrors());
    $this->assertEquals('922321981', $result->postId);
    $this->assertEquals('no', $result->text);
  }

  /**
   * Test getting a user's posts.
   */
  public function testGetUserPosts() {
    $i = new Twitter();
    $i->debug = FALSE;
    $result = $i->userList('dril');
    $this->assertEquals([], $i->getErrors());
    $this->assertNotEmpty($result);
  }

}
