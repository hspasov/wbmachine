<?php

class ArchiveManager {
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

    $target_path = ARCHIVE_STORE_PATH . "/" . $archive_info['id_hash'];

    mkdir($target_path);
    chmod($target_path, 0755);

    $log_file = LOG_FILE;
    `wget --directory-prefix={$target_path} --convert-links --no-cookies --span-hosts --page-requisites {$archive_info['url']} 2>> {$log_file}`;

    $sth = $dbh->sql("
      UPDATE archives
      SET status_id = ?
      WHERE id = ?
    ", [ARCH_STATUS_DONE, $this->archive_id]);

    assert($sth->rowCount() === 1);

    $dbh->commit();
  }

  public function view (int $site_id) {
    $sth = $dbh->sql("
      SELECT id_hash
      FROM archives
      WHERE id = ?
    ", [$site_id]);

    return $sth;
  }
}
