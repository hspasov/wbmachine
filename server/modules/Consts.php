<?php

define('ARCHIVE_STORE_PATH', '/usr/share/wbmachine/views/public/sites');
define('TMP_ARCHIVE_STORE_PATH', '/tmp/wbmachine/');
define('LOG_FILE', '/var/log/wbmachinelog');

define('S3_LOCATION', 's3://wbmachine');
define('S3_REGION', 'us-east-1');

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
