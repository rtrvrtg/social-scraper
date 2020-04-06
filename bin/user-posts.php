<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Rtrvrtg\SocialScraper\Scraper\Instagram;
use Rtrvrtg\SocialScraper\Scraper\Twitter;

$service = $argv[1];
$user = $argv[2];

if (empty($service) || empty($user)) {
  exit(1);
}

$posts = NULL;
if (strtolower($service) === 'instagram') {
  $service = new Instagram();
  $posts = $service->userList($user);
}
elseif (strtolower($service) === 'twitter') {
  $service = new Twitter();
  $posts = $service->userList($user);
}

var_dump($posts);
// print PHP_EOL . PHP_EOL;
// print $post->raw . PHP_EOL;
