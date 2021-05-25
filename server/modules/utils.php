<?php

function trace($arg) {
  error_log(print_r($arg, true));
}
