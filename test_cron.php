<?php
echo "Cron работает! Время: " . date('Y-m-d H:i:s') . "\n";
file_put_contents('/home/sanapostgr/abdrupa2/web/cron_test.txt', date('Y-m-d H:i:s') . "\n", FILE_APPEND);
