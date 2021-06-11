<?php

require "server/modules/Utils.php";
require "server/modules/DBConn.php";
require "server/modules/Consts.php";

$dbh = new DBConn();

$endpoint = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  switch($endpoint) {
  case '/':
    $title = 'Wayback Machine';
    $view = 'home';
    break;
  case '/home':
    $title = 'Wayback Machine';
    $view = 'home';
    break;

  case '/archive':
    $title = 'Archive page';
    $view = 'archive';

    $sth = $dbh->sql("
      SELECT *
      FROM schedule_intervals
      ORDER BY id
      ");
    $schedule_intervals = $sth->fetchAll();

    break;
  case '/view':
    $title = 'View page';
    $view = 'view';
    $url = $_GET['url'];

    if (!empty($url)) {
       $sth = $dbh->sql("
         SELECT id_hash, created_at
	 FROM archives
         WHERE site_id = (
         SELECT id
         FROM sites
         WHERE url = ?
	 ) ORDER BY created_at
         ", [$url]);
       
       $parsed_url = parse_url($url)['host'];       
       $timestamps = [];
       $id_hashes = [];
       while($contents = $sth->fetch()){
          array_push($timestamps, $contents['created_at']);
          array_push($id_hashes, $contents['id_hash']);
       }
    }
   
    if(empty($timestamps) && !empty($url)){
        $msg = "No previous archives for site $url found.";
    }

    break;
  default:
    http_response_code(404);
    die;
  }

  require "views/layout.html";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  switch($endpoint) {
  case '/archive':
    assert_peer(!empty($_POST['url']), "Missing param `url`");
    assert_peer(!empty($_POST['schedule_interval_id']), "Missing param `schedule_interval_id`");

    $title = 'Archive page';
    $view = 'archive';

    $sth = $dbh->sql("
      SELECT *
      FROM schedule_intervals
      ORDER BY id
    ");

    $schedule_intervals = $sth->fetchAll();

    if ($_POST['schedule_interval_id'] == SCHD_INTVL_NONE) {
      $dbh->sql("
        DELETE
        FROM archive_schedules
        WHERE site_id IN (
          SELECT id
          FROM sites
          WHERE url = ?
        )
      ", [$_POST['url']]);

      $success_msg = "Successfully removed archive schedule!";
      http_response_code(200);
      break;
    }

    $dbh->sql("
      INSERT INTO sites (url)
      SELECT ?
      WHERE NOT EXISTS (
        SELECT 1
        FROM sites
        WHERE url = ?
      )
    ", [$_POST['url'], $_POST['url']]);

    $sth = $dbh->sql("
      SELECT *
      FROM sites
      WHERE url = ?
    ", [$_POST['url']]);

    $row = $sth->fetch();
    $site_id = $row['id'];

    if ($_POST['schedule_interval_id'] == SCHD_INTVL_NOW) {
      $dbh->sql("
        INSERT INTO archives (site_id, status_id)
        SELECT ?, ?
        WHERE NOT EXISTS (
          SELECT 1
          FROM archives
          WHERE site_id = ?
            AND status_id = ?
        )
      ", [$site_id, ARCH_STATUS_PENDING, $site_id, ARCH_STATUS_PENDING]);

      $success_msg = "Successfully added site to be archived!";
      http_response_code(200);
      break;
    }

    $dbh->sql("
      UPDATE archive_schedules
      SET schedule_interval_id = ?
      WHERE site_id = ?
    ", [$_POST['schedule_interval_id'], $site_id]);

    $sth = $dbh->sql("
      INSERT INTO archive_schedules (schedule_interval_id, site_id)
      SELECT ?, ?
      WHERE NOT EXISTS (
        SELECT 1
        FROM archive_schedules
        WHERE site_id = ?
      )
    ", [$_POST['schedule_interval_id'], $site_id, $site_id]);

    if ($sth->rowCount() > 0) {
      $dbh->sql("
        INSERT INTO archives (site_id, status_id)
        SELECT ?, ?
        WHERE NOT EXISTS (
          SELECT 1
          FROM archives
          WHERE site_id = ?
        )
      ", [$site_id, ARCH_STATUS_PENDING, $site_id]);
    }

    $success_msg = "Successfully set archive schedule!";
    http_response_code(200);
    break;
  default:
    http_response_code(400);
    die;
  }

  if (!empty($_POST['is_ui'])) {
    require "views/layout.html";
  }
} else {
  die;
}

$dbh->commit();
