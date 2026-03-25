<?php
session_start();

// Require authentication
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Inherit security level, default to Low
if (isset($_GET['set_level'])) {
    $allowed_levels = ['Low', 'Medium', 'High'];
    if (in_array($_GET['set_level'], $allowed_levels)) {
        $_SESSION['sec_level'] = $_GET['set_level'];
        header("Location: index.php");
        exit;
    }
}

if (!isset($_SESSION['sec_level'])) { 
    $_SESSION['sec_level'] = 'Low'; 
}

// User Tracking for Active Sidebar
function get_active_users() {
    $db_users = '../db_users.json';
    if (!file_exists($db_users)) return [];
    return json_decode(file_get_contents($db_users), true) ?: [];
}

function xss_filter($data) {
    $level = $_SESSION['sec_level'];
    if ($level === 'Low') {
        return $data;
    } 
    elseif ($level === 'Medium') {
        $data = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $data);
        $data = preg_replace('/javascript:/i', "blocked:", $data);
        return $data;
    } 
    elseif ($level === 'High') {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

function read_db($file) {
    if (!file_exists($file)) { 
        file_put_contents($file, json_encode([])); 
        chmod($file, 0666); 
    }
    return json_decode(file_get_contents($file), true) ?: [];
}

function write_db($file, $data) {
    $fp = fopen($file, 'w');
    if (flock($fp, LOCK_EX)) { 
        fwrite($fp, json_encode($data)); 
        flock($fp, LOCK_UN); 
    }
    fclose($fp);
}

// File Paths
$db_posts = 'db_posts.json';
$db_comments = 'db_comments.json';
$db_notifications = 'db_notifications.json';

// Notifications Core Logic
function add_notification($target_user, $type, $actor, $message, $link) {
    global $db_notifications;
    $notifs = read_db($db_notifications);
    $new_notif = [
        'id' => uniqid('notif_'),
        'target_user' => $target_user,
        'type' => $type, // 'like', 'comment', 'post'
        'actor' => $actor,
        'message' => $message,
        'link' => $link,
        'time' => date("Y-m-d H:i"),
        'read' => false
    ];
    array_unshift($notifs, $new_notif);
    write_db($db_notifications, $notifs);
}

