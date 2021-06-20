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
      
       $parsed_url = preg_replace('#^https?://#', '', $url);   
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

  case '/site':
    $archive_store = '/sites';

    $s3_enabled = getenv('S3_ENABLED');
    $s3_url = getenv('S3_URL');

    if (!empty($s3_enabled) && $s3_enabled != 1 && $s3_enabled != 0) {
      throw new Exception('Environment variable S3_ENABLED must be equal to 0 or 1!');
    }

    if ($s3_enabled == 1 && empty($s3_url)) {
      throw new Exception('Please set environment variables required for using AWS S3 service: [S3_URL]');
    }

    if ($s3_enabled == 1) {
      $archive_store = $s3_url;
    }

    $title = 'View Page';
    $view = 'site';
    $parsed_url=$_GET['parsed_url'];
    $id_hash=$_GET['id_hash'];
    $url=$_GET['url'];
    $timestamp=$_GET['timestamp'];
  
    $sth = $dbh->sql("
      SELECT id_hash, created_at
      FROM archives
      WHERE site_id = (
      SELECT id
      FROM sites
      WHERE url = ?
      ) ORDER BY created_at
      ", [$url]);

    $timestamps = [];
    $id_hashes = [];
    while($contents = $sth->fetch()){
      array_push($timestamps, $contents['created_at']);
      array_push($id_hashes, $contents['id_hash']);
    }

    break;
  case '/view-api':
    $url = $_GET['url'];

    $sth = $dbh->sql("
      SELECT id_hash, created_at
      FROM archives
      WHERE site_id = (
        SELECT id
        FROM sites
        WHERE url = ?
      )
      ORDER BY created_at
    ", [$url]);

    $archives = [];

    while($contents = $sth->fetch()) {
      array_push($archives, [
        'timestamp' => $contents['created_at'],
        'id_hash' => $contents['id_hash'],
      ]);
    }

    $result = [
      'status' => 'ok',
      'archives' => $archives,
    ];

    echo json_encode($result);

    http_response_code(200);

    die;
  case '/site-api':
    $url=$_GET['url'];
    $id_hash=$_GET['id_hash'];
    $host=$_GET['host'];

    assert_peer(!empty($url) || !empty($id_hash), "Please provide either param `url` or `id_hash`");

    if (empty($id_hash)) {
      $sth = $dbh->sql("
        SELECT id_hash, created_at
        FROM archives
        WHERE site_id = (
          SELECT id
          FROM sites
          WHERE url = ?
        )
        ORDER BY created_at DESC
        LIMIT 1
      ", [$url]);

      assert_peer($sth->rowCount() == 1, 'Archive not found!');

      $archive_data = $sth->fetch();
      $id_hash = $archive_data['id_hash'];
    } else {
      $sth = $dbh->sql("
        SELECT *
        FROM sites
        WHERE id = (
          SELECT site_id
          FROM archives
          WHERE id_hash = ?
        )
      ", [$id_hash]);

      assert_peer($sth->rowCount() == 1, 'Archive not found!');

      $site_data = $sth->fetch();
      $url = $site_data['url'];
    }

    if (empty($host)) {
      $host = '/sites';
    }

    $parsed_url = preg_replace('#^https?://#', '', $url);

    $location = $host . '/' . $id_hash . '/' . $parsed_url;

    header("Location: $location");

    die;

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
