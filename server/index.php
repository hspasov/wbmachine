<?php

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  include "views/layout.html";
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {

} else {
  exit;
}
