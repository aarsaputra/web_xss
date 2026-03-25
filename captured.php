<?php
session_start();
$log_file = 'captured.json';

// Authentication check (admin only)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("<h1 style='color:red;font-family:monospace;text-align:center;margin-top:20%;'>[ ACCESS DENIED ]<br>UNAUTHORIZED PERSONNEL</h1>");
}

// Action: Clear Logs
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    file_put_contents($log_file, json_encode([]));
    header("Location: captured.php");
    exit;
}

$captured_data = [];
if (file_exists($log_file)) {
    $data_raw = file_get_contents($log_file);
    $captured_data = json_decode($data_raw, true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOC Terminal - Active Exfiltrations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #050810;
            color: #00f5d4;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 2rem;
            overflow-x: hidden;
        }
        .terminal-header {
            border-bottom: 2px solid #00f5d4;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 { margin: 0; font-size: 1.5rem; text-transform: uppercase; text-shadow: 0 0 10px rgba(0,245,212,0.5); }
        
        .controls a {
            color: #050810;
            background: #00f5d4;
            text-decoration: none;
            padding: 0.5rem 1rem;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
            transition: 0.3s;
        }
        .controls a:hover {
            box-shadow: 0 0 15px #00f5d4;
        }
        .controls .btn-danger {
            background: transparent;
            color: #ff0055;
            border: 1px solid #ff0055;
            margin-left: 1rem;
        }
        .controls .btn-danger:hover {
            background: #ff0055;
            color: #fff;
            box-shadow: 0 0 15px #ff0055;
        }

        .log-entry {
            background: rgba(0, 245, 212, 0.05);
            border-left: 4px solid #00f5d4;
            padding: 1rem;
            margin-bottom: 1rem;
            animation: fadeIn 0.5s ease-out;
            word-wrap: break-word;
        }
        .log-time { color: #8892b0; font-size: 0.8rem; margin-bottom: 0.5rem; }
        .log-data { color: #fff; }
        .log-ip { color: #ff0055; font-weight: bold; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .blinking-cursor {
            display: inline-block;
            width: 10px;
            height: 1.2rem;
            background-color: #00f5d4;
            animation: blink 1s step-end infinite;
            vertical-align: bottom;
        }
        @keyframes blink { 50% { opacity: 0; } }
        
        /* Scanline effect */
        body::after {
            content: " ";
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 2;
            background-size: 100% 2px, 3px 100%;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="terminal-header">
        <h1><i class="fas fa-satellite-dish"></i> ACTIVE_SESSIONS_INTERCEPTED</h1>
        <div class="controls">
            <a href="index.php?page=dashboard"><i class="fas fa-arrow-left"></i> RETURN TO HUB</a>
            <a href="?action=clear" class="btn-danger" onclick="return confirm('WARNING: PURGE ALL LOGS?');"><i class="fas fa-trash-alt"></i> PURGE LOGS</a>
        </div>
    </div>

    <div id="logs-container">
        <?php if(empty($captured_data)): ?>
            <p style="color: #8892b0;">> Listening for incoming transmissions...<span class="blinking-cursor"></span></p>
        <?php else: ?>
            <?php foreach($captured_data as $log): ?>
                <div class="log-entry">
                    <div class="log-time">[<?= htmlspecialchars($log['time']) ?>] SOURCE: <span class="log-ip"><?= htmlspecialchars($log['ip']) ?></span></div>
                    <div>UA: <span style="color:#8892b0"><?= htmlspecialchars($log['user_agent']) ?></span></div>
                    <div class="log-data">PAYLOAD: <?= htmlspecialchars($log['cookie']) ?></div>
                </div>
            <?php endforeach; ?>
            <p>> End of transmission.<span class="blinking-cursor"></span></p>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh the terminal every 5 seconds to show new captures
        setTimeout(() => {
            location.reload();
        }, 5000);
        
        // Auto scroll to bottom
        window.scrollTo(0, document.body.scrollHeight);
    </script>
</body>
</html>
