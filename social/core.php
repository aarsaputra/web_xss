<?php
session_start();

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

// Generate an anonymous identity if not logged in via the main portal
if (!isset($_SESSION['user'])) { 
    $_SESSION['user'] = 'Guest_' . rand(1000, 9999); 
    $_SESSION['role'] = 'guest'; 
    $_SESSION['username_id'] = uniqid();
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
