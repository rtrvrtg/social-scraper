<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Rtrvrtg\SocialScraper\Instagram;

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
    $this->assertEquals('2263790439947844808', $result['id']);
    $this->assertEquals([], $i->getErrors());
  }

}
