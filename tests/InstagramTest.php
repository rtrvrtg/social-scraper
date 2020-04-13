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
    $i->debug = FALSE;
    $result = $i->getPost('B9qmWk7BDzI');
    $this->assertEquals([], $i->getErrors());
    $this->assertNotEmpty($result->images);
    $this->assertEquals('2263790439947844808', $result->postId);
  }

  /**
   * Test getting a user list.
   */
  public function testGetUserList() {
    $i = new Instagram();
    $i->debug = FALSE;
    $result = $i->userList('retrovertigo');
    $this->assertEquals([], $i->getErrors());
    $this->assertNotEmpty($result->posts);
    $this->assertNotEmpty($result->posts[0]->images);
    $this->assertEquals('retrovertigo', $result->posts[0]->userName);
  }

  /**
   * Test getting a user list with cursor.
   */
  public function testGetUserListWithCursor() {
    $i = new Instagram();
    $i->debug = TRUE;
    $result = $i->userList('retrovertigo', 'QVFBXzFiQklxd2x1Mk0waHJncnFPYUVXTmlRdVVudXEwTmxPOUI0dW85T3RpdWRkV241VkQxYW9jMWE3cVZWRXdEMG95eDh6S2I2MFJobVplRHZUUmhjTQ==');
    $this->assertEquals([], $i->getErrors());
    $this->assertNotEmpty($result->posts);
    $this->assertNotEmpty($result->posts[0]->images);
    $this->assertEquals('retrovertigo', $result->posts[0]->userName);
  }

  /**
   * Test getting a hashtag list.
   */
  public function testGetHashtagList() {
    $i = new Instagram();
    $i->debug = FALSE;
    $result = $i->hashtagList('ibis');
    $this->assertEquals([], $i->getErrors());
    $this->assertNotEmpty($result->posts);
    $this->assertNotEmpty($result->posts[0]->images);
  }

  /**
   * Test getting a hashtag list with cursor.
   */
  public function testGetHashtagListWithCursor() {
    $i = new Instagram();
    $i->debug = TRUE;
    $result = $i->hashtagList('ibis', 'QVFCWEFTNFJCMWt4ZUtfQXV6UVpzYzdJUWhpM056RkVtb3dVdlBZVGNKcFdRYXBKSFlJMTZtNU02ai02X0tSNDBmS2lnYUNmUFpYZTc4T3AtclVJX1p4cA==');
    $this->assertEquals([], $i->getErrors());
    $this->assertNotEmpty($result->posts);
    $this->assertNotEmpty($result->posts[0]->images);
  }

}
