<?php

ini_set('assert.exception', 1);

define('ARCHIVE_STORE_PATH', '/var/lib/wbmachine');
define('LOG_FILE', '/var/log/wbmachinelog');

define('SCHD_INTVL_NONE', 10);
define('SCHD_INTVL_NOW', 20);
define('SCHD_INTVL_EVERY_MONTH', 30);
define('SCHD_INTVL_EVERY_6_MONTHS', 40);
define('SCHD_INTVL_EVERY_YEAR', 50);
define('SCHD_INTVL_EVERY_3_YEARS', 60);
define('SCHD_INTVL_EVERY_5_YEARS', 70);
define('SCHD_INTVL_EVERY_10_YEARS', 80);

define('ARCH_STATUS_PENDING', 10);
define('ARCH_STATUS_IN_PROGRESS', 20);
define('ARCH_STATUS_DONE', 30);
