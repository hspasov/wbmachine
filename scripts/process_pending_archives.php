<?php

require "server/modules/Utils.php";
require "server/modules/DBConn.php";
require "server/modules/Consts.php";
require "deps/aws.phar";

use Aws\S3\S3Client;

function set_status_pending (DBConn $dbh, int $archive_id) {
  $sth = $dbh->sql("
    UPDATE archives
    SET status_id = ?
    WHERE id = ?
      AND status_id = ?
  ", [ARCH_STATUS_IN_PROGRESS, $archive_id, ARCH_STATUS_PENDING]);

  assert($sth->rowCount() === 1);
}


function set_status_done (DBConn $dbh, int $archive_id) {
  $sth = $dbh->sql("
    UPDATE archives
    SET status_id = ?
    WHERE id = ?
  ", [ARCH_STATUS_DONE, $archive_id]);

  assert($sth->rowCount() === 1);
}


function get_archive_info (DBConn $dbh, int $archive_id) {
  $sth = $dbh->sql("
    SELECT
      A.id_hash,
      S.url
    FROM archives A
    JOIN sites S ON S.id = A.site_id
    WHERE A.id = ?
  ", [$archive_id]);

  assert($sth->rowCount() === 1);

  return $sth->fetch();
}


function fetch_site_local (string $id_hash, string $url) {
  $log_file = getenv('LOG_FILE');

  if (empty($log_file)) {
    throw new Exception('Please set environment variables required for fetching pages to be archived: [LOG_FILE]');
  }

  $target_path = ARCHIVE_STORE_PATH . "/" . $id_hash;

  mkdir($target_path);
  chmod($target_path, 0755);

  `wget --directory-prefix={$target_path} --convert-links --no-cookies --span-hosts --page-requisites {$url} 2>> {$log_file}`;
}


function fetch_site_s3 (string $id_hash, string $url) {
  $s3_region = getenv('S3_REGION');
  $s3_location = getenv('S3_LOCATION');
  $log_file = getenv('LOG_FILE');

  if (empty($s3_region) || empty($s3_location)) {
    throw new Exception('Please set environment variables required for using AWS S3 service: [S3_REGION, S3_LOCATION]');
  }

  if (empty($log_file)) {
    throw new Exception('Please set environment variables required for fetching pages to be archived: [LOG_FILE]');
  }

  if (!file_exists(TMP_ARCHIVE_STORE_PATH)) {
    mkdir(TMP_ARCHIVE_STORE_PATH);
    chmod(TMP_ARCHIVE_STORE_PATH, 0755);
  }

  $target_path = TMP_ARCHIVE_STORE_PATH . "/" . $id_hash;

  mkdir($target_path);
  chmod($target_path, 0755);

  $included_id_hash_target_path = $target_path . "/" . $id_hash;

  mkdir($included_id_hash_target_path);
  chmod($included_id_hash_target_path, 0755);

  `wget --directory-prefix={$included_id_hash_target_path} --convert-links --no-cookies --span-hosts --page-requisites {$url} 2>> {$log_file}`;

  $s3_client = new Aws\S3\S3Client([
    'version' => 'latest',
    'region' => $s3_region,
  ]);

  $transfer_manager = new \Aws\S3\Transfer($s3_client, $target_path, $s3_location);
  $transfer_manager->transfer();

  `rm -r {$target_path}`;
}


function start () {
  $s3_enabled = getenv('S3_ENABLED');

  if (!empty($s3_enabled) && $s3_enabled != 1 && $s3_enabled != 0) {
    throw new Exception('Environment variable S3_ENABLED must be equal to 0 or 1!');
  }

  $dbh = new DBConn();

  $sth = $dbh->sql("
    SELECT *
    FROM archives
    WHERE status_id = ?
    ORDER BY created_at
  ", [ARCH_STATUS_PENDING]);

  while ($archive = $sth->fetch()) {
    set_status_pending($dbh, $archive['id']);
    $archive_info = get_archive_info($dbh, $archive['id']);

    if ($s3_enabled == 1) {
      fetch_site_s3($archive_info['id_hash'], $archive_info['url']);
    } else {
      fetch_site_local($archive_info['id_hash'], $archive_info['url']);
    }

    set_status_done($dbh, $archive['id']);

    $dbh->commit();
  }
}

start();
