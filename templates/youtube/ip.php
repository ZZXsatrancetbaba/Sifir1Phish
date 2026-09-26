<?php
$ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown';
$time = date('Y-m-d H:i:s');

$data = <<<EOT
IP: $ip
User-Agent: $ua
Language: $lang
Time: $time
---

EOT;

file_put_contents('ip.txt', $data, FILE_APPEND | LOCK_EX);
?>
