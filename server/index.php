<?php

require "server/modules/utils.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $endpoint = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

  switch($endpoint) {
  case '/':
    $title = 'Wayback Machine';
    $view = 'home';
    break;
  case '/archive':
    $title = 'Archive page';
    $view = 'archive';
    $schedule_intervals = [
      [
        "id" => 1,
        "name" => "Test1",
      ],
      [
        "id" => 2,
        "name" => "Test2",
      ],
    ];
    break;
  default:
    http_response_code(404);
    die;
  }

  require "views/layout.html";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {

} else {
  die;
}
