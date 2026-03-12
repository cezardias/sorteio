<?php
header('Content-Type: text/plain');
echo "LIVE_PATH: " . __FILE__ . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "\n";
echo "REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . "\n";
echo "TIME: " . date('Y-m-d H:i:s') . "\n";
