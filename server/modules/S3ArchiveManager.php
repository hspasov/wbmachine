<?php

require "deps/aws.phar";

use Aws\S3\S3Client;

class S3ArchiveManager {
  private $archive_id;

  public function __construct(int $archive_id) {
    $this->archive_id = $archive_id;
  }

  public function fetch_and_store () {
    $dbh = new DBConn();

    $sth = $dbh->sql("
      UPDATE archives
      SET status_id = ?
      WHERE id = ?
        AND status_id = ?
    ", [ARCH_STATUS_IN_PROGRESS, $this->archive_id, ARCH_STATUS_PENDING]);

    assert($sth->rowCount() === 1);

    $sth = $dbh->sql("
      SELECT
        A.id_hash,
        S.url
      FROM archives A
      JOIN sites S ON S.id = A.site_id
      WHERE A.id = ?
    ", [$this->archive_id]);

    assert($sth->rowCount() === 1);

    $archive_info = $sth->fetch();

    $target_path = TMP_ARCHIVE_STORE_PATH . "/" . $archive_info['id_hash'];

    mkdir($target_path);
    chmod($target_path, 0755);

    $included_id_hash_target_path = $target_path . "/" . $archive_info['id_hash'];

    mkdir($included_id_hash_target_path);
    chmod($included_id_hash_target_path, 0755);

    $log_file = LOG_FILE;
    `wget --directory-prefix={$included_id_hash_target_path} --convert-links --no-cookies --span-hosts --page-requisites {$archive_info['url']} 2>> {$log_file}`;

    $s3_client = new Aws\S3\S3Client([
      'version' => 'latest',
      'region' => S3_REGION,
      'credentials' => [
        'key' => 'ASIAUACR7BITUUUNMRT5',
        'secret' => 'JAoGV9+ECr+bJP8Z/iDv1UKpiB3t383rLBoVJCdv',
      ],
    ]);

    $transfer_manager = new \Aws\S3\Transfer($s3_client, $target_path, S3_LOCATION);
    $transfer_manager->transfer();

    `rm -r {$target_path}`;

    $sth = $dbh->sql("
      UPDATE archives
      SET status_id = ?
      WHERE id = ?
    ", [ARCH_STATUS_DONE, $this->archive_id]);

    assert($sth->rowCount() === 1);

    $dbh->commit();
  }

  public function view (int $site_id) {
  }
}
