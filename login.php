<?php
session_start();

$db_users = 'db_users.json';

function read_users() {
    global $db_users;
    if (!file_exists($db_users)) return [];
    return json_decode(file_get_contents($db_users), true) ?: [];
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $users = read_users();
    $authenticated = false;
    
    foreach ($users as $user) {
        if ($user['username'] === $username && $user['password'] === $password) {
            $_SESSION['user'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username_id'] = $user['id'];
            $_SESSION['sec_level'] = 'Low'; // Default on login
            $authenticated = true;
            header("Location: index.php");
            exit;
        }
    }
    
    if (!$authenticated) {
        $error = 'Username or Password incorrect.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XSS Lab | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; background-color: var(--bg-dark); }
        .login-box {
            background: var(--bg-card);
            padding: 2.5rem;
            border-radius: 15px;
            border: 1px solid var(--border);
            width: 350px;
            box-shadow: 0 0 30px rgba(0, 245, 212, 0.1);
            text-align: center;
        }
        .login-box h2 { color: var(--primary); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 2px; }
        .login-box p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem; }
        .input-group { margin-bottom: 1.5rem; text-align: left; }
        .input-group label { display: block; color: var(--secondary); margin-bottom: 0.5rem; font-size: 0.9rem; }
        .input-group input { width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.5); border: 1px solid var(--border); border-radius: 8px; padding: 10px 15px; color: var(--text); outline: none; transition: 0.3s; }
        .input-group input:focus { border-color: var(--primary); box-shadow: 0 0 10px rgba(0,245,212,0.2); }
        .btn-full { width: 100%; display: block; padding: 12px; margin-top: 1rem; }
        .credentials-hint { margin-top: 2rem; padding: 1rem; background: rgba(255, 255, 255, 0.05); border-radius: 8px; border: 1px dashed var(--border); font-size: 0.8rem; color: var(--text-muted); text-align: left; }
    </style>
</head>
<body class="cyber-bg">

    <div class="login-box">
        <i class="fas fa-biohazard" style="font-size:3rem; color:var(--primary); margin-bottom:1rem; text-shadow:0 0 15px var(--primary-glow);"></i>
        <h2>SYS_LOGIN</h2>
        <p>Authenticate to access the Cyber Range</p>
        
        <?php if($error): ?>
            <div style="color:var(--accent); background:rgba(255,0,85,0.1); border:1px solid var(--accent); padding:10px; border-radius:8px; margin-bottom:15px; font-size:0.9rem;">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">INITIATE CONNECTION</button>
        </form>
        
        <div class="credentials-hint">
            <strong style="color:var(--secondary)">Target Accounts:</strong><br>
            <ul style="padding-left:15px; margin:5px 0;">
                <li>User: <b>Administrator</b> | Pass: <b>password123</b></li>
                <li>User: <b>Guest_309</b> | Pass: <b>guest</b></li>
                <li>User: <b>Guest_7815</b> | Pass: <b>guest</b></li>
            </ul>
        </div>
    </div>

</body>
</html>
