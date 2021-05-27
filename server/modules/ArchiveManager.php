<?php

class ArchiveManager {
  private $archive_id;

  public function __construct(int $_archive_id) {
    $archive_id = $_archive_id;
  }

  public function fetch_and_store () {
    $dbh = new DBConn();

    $sth = $dbh->sql("
      UPDATE archives
      SET status_id = ?
      WHERE id = ?
    ", [ARCH_STATUS_IN_PROGRESS, $this->archive_id]);

    assert.exception($sth->rowCount() === 1);

    $sth = $dbh->sql("
      SELECT
        A.id_hash,
        S.url
      FROM archives A
      JOIN sites S ON S.id = A.site_id
      WHERE A.id = ?
    ", [$this->archive_id]);

    assert.exception($sth->rowCount() === 1);

    $archive_info = $sth->fetch();

    $target_path = realpath(ARCHIVE_STORE_PATH . "/" . $archive_info['id_hash']);

    mkdir($target_path, 0666);

    `wget --directory-prefix={$target_path} --convert-links --no-cookies --span-hosts --page-requisites {$archive_info['url']} >> ${LOG_PATH}`;

    $sth = $dbh->sql("
      UPDATE archives
      SET status_id = ?
      WHERE id = ?
    ", [ARCH_STATUS_DONE, $this->archive_id]);

    assert.exception($sth->rowCount() === 1);
  }

  public function view () {
    # TODO
  }
}
