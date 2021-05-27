<?php

class PeerError extends AssertionError {};

function trace($arg) {
  error_log(print_r($arg, true));
}

function assert_peer(bool $cond, string $msg="") {
  if (!$cond) {
    throw new PeerError($msg);
  }
}
