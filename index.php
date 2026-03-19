<?php
session_start();

// --- CONFIGURATION ---
$db_file = 'database_komen.json';
if (!file_exists($db_file)) {
    file_put_contents($db_file, json_encode([]));
}

// --- AUTH LOGIC ---
if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    if ($user == 'admin' && $pass == 'admin') {
        $_SESSION['user'] = 'Administrator';
        $_SESSION['username_id'] = 'admin';
        $_SESSION['role'] = 'admin';
        setcookie("Secret_Admin_Token", "SUPER_SECRET_ADMIN_KEY_12345", time() + 3600, "/");
        header("Location: index.php?page=dashboard");
        exit;
    } 
    elseif ($user == 'user' && $pass == 'user') {
        $_SESSION['user'] = 'User Biasa';
        $_SESSION['username_id'] = 'user'; 
        $_SESSION['role'] = 'user';
        header("Location: index.php?page=dashboard");
        exit;
    } 
    else {
        $error = "Login Gagal! (Hint: admin/admin atau user/user)";
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    setcookie("Secret_Admin_Token", "", time() - 3600, "/");
    header("Location: index.php");
    exit;
}

// profile update (Stored XSS)
if (isset($_POST['update_profile']) && isset($_SESSION['user'])) {
    $_SESSION['user'] = $_POST['display_name']; 
    $msg_profile = "Nama profil diperbarui!";
}

// --- COMMENT LOGIC (STORED XSS) ---
if (isset($_POST['comment'])) {
    $current_data = json_decode(file_get_contents($db_file), true);
    if (!is_array($current_data)) $current_data = [];

    if (isset($_SESSION['user'])) {
        $sender = $_SESSION['user'];
        $sender_id = $_SESSION['username_id'];
        $role = $_SESSION['role'];
    } else {
        $sender = $_POST['guest_name'] ? $_POST['guest_name'] : "Guest"; 
        $sender_id = 'guest_' . time();
        $role = 'guest';
    }

    $new_comment = [
        'id' => uniqid(),
        'name' => $sender,
        'role' => $role,
        'sender_id' => $sender_id,
        'message' => $_POST['comment'],
        'time' => date("H:i")
    ];

    array_unshift($current_data, $new_comment);
    file_put_contents($db_file, json_encode($current_data));
}

if (isset($_GET['delete_id'])) {
    $id_to_delete = $_GET['delete_id'];
    $current_data = json_decode(file_get_contents($db_file), true);
    $new_data = [];
    foreach ($current_data as $row) {
        if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || (isset($_SESSION['username_id']) && $_SESSION['username_id'] == $row['sender_id']))) {
            if ($row['id'] == $id_to_delete) continue;
        }
        $new_data[] = $row;
    }
    file_put_contents($db_file, json_encode($new_data));
    header("Location: index.php?page=stored");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'reset' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    file_put_contents($db_file, json_encode([]));
    header("Location: index.php?page=stored");
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOTA1337 | XSS Pentest Lab</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="cyber-bg">

    <nav class="navbar glass">
        <a href="index.php" class="brand">LOTA1337 LABS</a>
        <div class="nav-links">
            <a href="?page=home" class="nav-link <?= $page == 'home' ? 'active' : '' ?>">Home</a>
            <a href="?page=reflected" class="nav-link <?= $page == 'reflected' ? 'active' : '' ?>">Reflected</a>
            <a href="?page=stored" class="nav-link <?= $page == 'stored' ? 'active' : '' ?>">Stored</a>
            <a href="?page=dom" class="nav-link <?= $page == 'dom' ? 'active' : '' ?>">DOM</a>
            <?php if(isset($_SESSION['user'])): ?>
                <a href="?page=dashboard" class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="?action=logout" class="nav-link logout-btn">Logout</a>
            <?php else: ?>
                <a href="?page=login" class="nav-link <?= $page == 'login' ? 'active' : '' ?>">Login</a>
            <?php endif; ?>
        </div>
        <button onclick="togglePresenterMode()" class="btn btn-outline presenter-btn">
            <i class="fas fa-chalkboard-teacher"></i> Presenter Mode
        </button>
    </nav>

    <div class="container">
        
        <div class="lab-grid">
            <aside class="sidebar glass">
                <h3 class="sidebar-title">Vulnerability Menu</h3>
                <ul class="sidebar-menu">
                    <li onclick="location.href='?page=home'" class="sidebar-item <?= $page == 'home' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Welcome Home
                    </li>
                    <li onclick="location.href='?page=reflected'" class="sidebar-item <?= $page == 'reflected' ? 'active' : '' ?>">
                        <i class="fas fa-search"></i> Reflected XSS
                    </li>
                    <li onclick="location.href='?page=stored'" class="sidebar-item <?= $page == 'stored' ? 'active' : '' ?>">
                        <i class="fas fa-database"></i> Stored XSS
                    </li>
                    <li onclick="location.href='?page=dom'" class="sidebar-item <?= $page == 'dom' ? 'active' : '' ?>">
                        <i class="fas fa-code"></i> DOM-based XSS
                    </li>
                    <li onclick="location.href='captured.php'" class="sidebar-item">
                        <i class="fas fa-terminal"></i> Cookie Stealer
                    </li>
                </ul>
            </aside>

            <main class="main-content">
                <?php switch($page): 
                    case 'home': ?>
                        <div class="card glass">
                            <h1>Welcome to XSS Research Lab</h1>
                            <p>Lab ini dirancang khusus untuk demonstrasi kerentanan XSS (Cross-Site Scripting). Gunakan menu di samping untuk mulai mengeksplorasi berbagai jenis XSS.</p>
                            <div class="presenter-mode">
                                <strong>💡 Tips Presentasi:</strong>
                                <p>Jelaskan alur bagaimana browser mengeksekusi script yang tidak terpercaya. Gunakan payload sederhana seperti <code>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</code> untuk memulai.</p>
                            </div>
                        </div>
                    <?php break;

                    case 'reflected': ?>
                        <div class="card glass">
                            <h2><i class="fas fa-search"></i> Reflected XSS Lab</h2>
                            <p>Input yang Anda masukkan akan ditampilkan kembali di halaman hasil tanpa sanitasi yang tepat.</p>
                            
                            <form method="GET" class="search-form">
                                <input type="hidden" name="page" value="reflected">
                                <input type="text" name="q" placeholder="Cari sesuatu..." value="<?= isset($_GET['q']) ? $_GET['q'] : '' ?>">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>

                            <?php if(isset($_GET['q'])): ?>
                                <div class="alert-box">
                                    <p>Hasil pencarian untuk: <strong class="neon-text"><?php echo $_GET['q']; ?></strong></p>
                                </div>
                                <div class="presenter-mode">
                                    <strong>📖 Penjelasan:</strong>
                                    <p>Input <code>q</code> langsung dicetak ke dalam tag <code>&lt;strong&gt;</code> tanpa <code>htmlspecialchars()</code>.</p>
                                    <strong>🔓 Payload:</strong>
                                    <div class="debug-info">&lt;script&gt;alert('Reflected XSS!')&lt;/script&gt;</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php break;

                    case 'stored': ?>
                        <div class="card glass">
                            <h2><i class="fas fa-database"></i> Stored XSS (Forum)</h2>
                            <p>Pesan yang Anda kirim akan disimpan secara permanen di server dan dieksekusi oleh setiap pengguna yang memuat halaman ini.</p>
                            
                            <form method="POST" class="post-form glass">
                                <?php if(!isset($_SESSION['user'])): ?>
                                    <input type="text" name="guest_name" placeholder="Nama Anda (Guest)">
                                <?php else: ?>
                                    <p>Posting as: <strong class="neon-text"><?= $_SESSION['user'] ?></strong></p>
                                <?php endif; ?>
                                <textarea name="comment" rows="3" placeholder="Tulis pesan rahasia..."></textarea>
                                <button type="submit" class="btn btn-primary">Post Message</button>
                            </form>

                            <h3>Feed Diskusi:</h3>
                            <div class="comments-list">
                                <?php 
                                $comments = json_decode(file_get_contents($db_file), true) ?? [];
                                foreach($comments as $c): 
                                    $badge = 'badge-guest';
                                    if($c['role'] == 'admin') $badge = 'badge-admin';
                                    if($c['role'] == 'user') $badge = 'badge-user';
                                ?>
                                    <div class="comment glass">
                                        <div class="comment-header">
                                            <span>
                                                <span class="badge <?= $badge ?>"><?= strtoupper($c['role']) ?></span>
                                                <b class="neon-text"><?= $c['name'] ?></b>
                                            </span>
                                            <span>
                                                <?= $c['time'] ?>
                                                <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || (isset($c['sender_id']) && $_SESSION['username_id'] == $c['sender_id']))): ?>
                                                    | <a href="?delete_id=<?= $c['id'] ?>" class="delete-link"><i class="fas fa-trash"></i></a>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="comment-body">
                                            <?= $c['message'] ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="presenter-mode">
                                <strong>📖 Penjelasan:</strong>
                                <p>Input <code>comment</code> disimpan ke JSON dan dirender langsung. Ini adalah jenis XSS yang paling berbahaya.</p>
                                <strong>🔓 Payload:</strong>
                                <div class="debug-info">&lt;img src=x onerror="alert(document.cookie)"&gt;</div>
                            </div>
                        </div>
                    <?php break;

                    case 'dom': ?>
                        <div class="card glass">
                            <h2><i class="fas fa-code"></i> DOM-based XSS</h2>
                            <p>Data diambil langsung dari URL fragment (hash) dan dimasukkan ke dalam <code>innerHTML</code>.</p>
                            
                            <div id="dom-output">
                                <!-- JS content -->
                                Sedang memuat konten dari hash...
                            </div>

                            <div class="presenter-mode" style="display: block;">
                                <strong>📖 Bagaimana cara kerjanya?</strong>
                                <p>Coba payload berikut di URL Anda:</p>
                                <div class="debug-info">index.php?page=dom#&lt;img src=x onerror=alert('DOM_XSS')&gt;</div>
                                <p>Script klien akan membaca <code>window.location.hash</code> dan mengeksekusinya.</p>
                            </div>
                            
                            <script>
                                function updateDOM() {
                                    const hash = decodeURIComponent(window.location.hash.substring(1));
                                    const output = document.getElementById('dom-output');
                                    if (output) {
                                        if (hash) {
                                            output.innerHTML = "<h3 class='neon-text'>Welcome, " + hash + "</h3>";
                                        } else {
                                            output.innerHTML = "Gunakan URL Fragment (misal: <code>#Hacker</code>) di akhir URL.";
                                        }
                                    }
                                }
                                window.addEventListener('hashchange', updateDOM);
                                window.addEventListener('load', updateDOM);
                                // Initial load if hash exists
                                updateDOM();
                            </script>
                        </div>
                    <?php break;

                    case 'login': ?>
                        <div class="card glass login-card">
                            <h2 style="text-align: center;">Member Login</h2>
                            <?php if(isset($error)) echo "<p class='error-text'>$error</p>"; ?>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" placeholder="admin / user">
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" name="password" placeholder="admin / user">
                                </div>
                                <button type="submit" name="login" class="btn btn-primary full-width">ACCESS SYSTEM</button>
                            </form>
                        </div>
                    <?php break;

                    case 'dashboard': 
                        if(!isset($_SESSION['user'])) { echo "<script>window.location='index.php?page=login';</script>"; break; }
                        ?>
                        <div class="card glass">
                            <h2>Dashboard: <?= $_SESSION['role'] ?></h2>
                            <p>Hello, <strong class="neon-text"><?= $_SESSION['user'] ?></strong>. Access granted.</p>
                            
                            <?php if($_SESSION['role'] == 'admin'): ?>
                                <div class="admin-panel glass">
                                    <h4><i class="fas fa-shield-alt"></i> Admin Terminal</h4>
                                    <p>Secret Token: <code>SUPER_SECRET_ADMIN_KEY_12345</code></p>
                                    <a href="?action=reset" class="btn btn-danger">Emergency Wipe Forum</a>
                                </div>
                            <?php endif; ?>

                            <hr class="divider">
                            
                            <h3>Edit Profile</h3>
                            <form method="POST" class="profile-form">
                                <label>Username Display</label>
                                <input type="text" name="display_name" value="<?= $_SESSION['user'] ?>">
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </form>
                            <?php if(isset($msg_profile)) echo "<p class='success-text'>$msg_profile</p>"; ?>
                        </div>
                    <?php break;

                endswitch; ?>
            </main>
        </div>
    </div>

    <footer class="footer">
        &copy; 2026 LOTA1337 | Advanced XSS Lab Platform
    </footer>

    <script>
        function togglePresenterMode() {
            const notes = document.querySelectorAll('.presenter-mode');
            notes.forEach(note => {
                note.style.display = note.style.display === 'block' ? 'none' : 'block';
            });
            localStorage.setItem('presenterMode', notes[0]?.style.display === 'block');
        }

        window.addEventListener('load', () => {
            if (localStorage.getItem('presenterMode') === 'true') {
                document.querySelectorAll('.presenter-mode').forEach(n => n.style.display = 'block');
            }
        });
    </script>
</body>
</html>
