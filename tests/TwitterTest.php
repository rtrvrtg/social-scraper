<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Rtrvrtg\SocialScraper\Twitter;

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
    var_dump($result);
    // $this->assertEquals('2263790439947844808', $result['id']);
    $this->assertEquals([], $i->getErrors());
  }

}
