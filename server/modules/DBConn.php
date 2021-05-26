<?php

class DBConn {
  private $connection;

  public function __construct() {
    $dbhost = getenv('DBHOST');
    $dbname = getenv('DBNAME');
    $dbuser = getenv('DBUSER');
    $dbpass = getenv('DBPASS');

    if (empty($dbhost) || empty($dbname) || empty($dbuser) || empty($dbpass)) {
      throw new Exception('Please set all environment variables required: [DBHOST, DBNAME, DBUSER, DBPASS]');
    }

    $this->connection = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass, [
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->connection->beginTransaction();
  }

  public function sql(string $query, array $params=[]) {
    $sth = $this->connection->prepare($query);
    $sth->execute($params);
    return $sth;
  }

  public function commit() {
    $this->connection->commit();
    $this->connection->beginTransaction();
  }
}
