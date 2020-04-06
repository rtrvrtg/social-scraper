<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Rtrvrtg\SocialScraper\Scraper\Instagram;
use Rtrvrtg\SocialScraper\Scraper\Twitter;

$service = $argv[1];
$user = $argv[2];
$post_id = $argv[3];

if (empty($service) || empty($user) || empty($post_id)) {
  exit(1);
}

$post = NULL;
if (strtolower($service) === 'instagram') {
  $service = new Instagram();
  $post = $service->getPost($post_id);
}
elseif (strtolower($service) === 'twitter') {
  $service = new Twitter();
  $post = $service->getPost($user, $post_id);
}

var_dump($post);
// print PHP_EOL . PHP_EOL;
// print $post->raw . PHP_EOL;
