<?php

require "server/modules/Utils.php";
require "server/modules/DBConn.php";
require "server/modules/Consts.php";
require "server/modules/S3ArchiveManager.php";

function start () {
  $dbh = new DBConn();

  $sth = $dbh->sql("
    SELECT *
    FROM archives
    WHERE status_id = ?
    ORDER BY created_at
  ", [ARCH_STATUS_PENDING]);

  while ($archive = $sth->fetch()) {
    $arch_manager = new S3ArchiveManager($archive['id']);
    $arch_manager->fetch_and_store();
  }
}

start();
