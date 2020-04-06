<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Rtrvrtg\SocialScraper\Scraper\Instagram;

/**
 * Tests Instagram scraping.
 */
final class InstagramTest extends TestCase {

  /**
   * Test getting a single post.
   */
  public function testGetSinglePost() {
    $i = new Instagram();
    $result = $i->getPost('B9qmWk7BDzI');
    $this->assertNotEmpty($result->images);
    $this->assertEquals('2263790439947844808', $result->postId);
    $this->assertEquals([], $i->getErrors());
  }

  /**
   * Test getting a user list.
   */
  public function testGetUserList() {
    $i = new Instagram();
    $result = $i->userList('retrovertigo');
    $this->assertNotEmpty($result->posts);
    $this->assertNotEmpty($result->posts[0]->images);
    $this->assertEquals('retrovertigo', $result->posts[0]->userName);
    $this->assertEquals([], $i->getErrors());
  }

}
