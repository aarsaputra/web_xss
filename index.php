<?php
session_start();

// --- CONFIGURATION ---
$db_file = 'database_komen.json';
if (!file_exists($db_file)) {
    // Initialize with empty array and strict permissions if newly created
    file_put_contents($db_file, json_encode([]));
    chmod($db_file, 0666);
}

$report_file = 'database_reports.json';
if (!file_exists($report_file)) {
    file_put_contents($report_file, json_encode([]));
    chmod($report_file, 0666);
}

// Security Level Management
if (!isset($_SESSION['sec_level'])) {
    $_SESSION['sec_level'] = 'Low';
}

if (isset($_GET['set_level'])) {
    $allowed_levels = ['Low', 'Medium', 'High'];
    if (in_array($_GET['set_level'], $allowed_levels)) {
        $_SESSION['sec_level'] = $_GET['set_level'];
        header("Location: index.php?page=" . (isset($_GET['page']) ? $_GET['page'] : 'home'));
        exit;
    }
}

// The Core XSS Filter Function
function xss_filter($data) {
    $level = $_SESSION['sec_level'];
    
    if ($level === 'Low') {
        // No filter - 100% Vulnerable
        return $data;
    } 
    elseif ($level === 'Medium') {
        // Basic Blacklist: Strip <script> tags and javascript: protocol
        // This can be bypassed with tags like <svg>, <img>, <details>
        $data = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $data);
        $data = preg_replace('/javascript:/i', "blocked:", $data);
        return $data;
    } 
    elseif ($level === 'High') {
        // Strict Output Encoding
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

// Safe JSON Reader
function read_db($file) {
    if (!file_exists($file)) return [];
    $data = file_get_contents($file);
    return json_decode($data, true) ?: [];
}

// Safe JSON Writer with File Locking to prevent corruption during live demos
function write_db($file, $data) {
    $fp = fopen($file, 'w');
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, json_encode($data));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
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
    $current_data = read_db($db_file);

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
    write_db($db_file, $current_data);
}

// --- REPORT LOGIC (BLIND XSS) ---
if (isset($_POST['report_bug'])) {
    $current_reports = read_db($report_file);
    
    $new_report = [
        'id' => uniqid(),
        'sender' => isset($_POST['reporter_name']) && $_POST['reporter_name'] ? $_POST['reporter_name'] : 'Anonymous',
        'message' => $_POST['report_message'],
        'time' => date("H:i")
    ];

    array_unshift($current_reports, $new_report);
    write_db($report_file, $current_reports);
    $msg_report = "Laporan berhasil dikirim ke Admin Support!";
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
    write_db($db_file, $new_data);
    header("Location: index.php?page=stored");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'reset' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    write_db($db_file, []);
    write_db($report_file, []);
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
            
            <div class="nav-link dropdown">
                Level: <span class="neon-text"><?= $_SESSION['sec_level'] ?></span>
                <div class="dropdown-content">
                    <a href="?set_level=Low&page=<?= $page ?>" class="level-low">Low</a>
                    <a href="?set_level=Medium&page=<?= $page ?>" class="level-med">Medium</a>
                    <a href="?set_level=High&page=<?= $page ?>" class="level-high">High</a>
                </div>
            </div>

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
                    <li onclick="location.href='?page=contact'" class="sidebar-item <?= $page == 'contact' ? 'active' : '' ?>">
                        <i class="fas fa-envelope-open-text"></i> Blind XSS
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
                                    <p>Hasil pencarian untuk: <strong class="neon-text"><?php echo xss_filter($_GET['q']); ?></strong></p>
                                </div>
                                
                                <div class="presenter-mode">
                                    <?php if($_SESSION['sec_level'] == 'Low'): ?>
                                        <strong>📖 Catatan Presenter (Level LOW):</strong>
                                        <p>Input tidak disaring sama sekali. Payload standar akan berhasil dieksekusi.</p>
                                        <div class="debug-info">&lt;script&gt;alert('Reflected XSS!')&lt;/script&gt;</div>
                                    <?php elseif($_SESSION['sec_level'] == 'Medium'): ?>
                                        <strong>📖 Catatan Presenter (Level MEDIUM):</strong>
                                        <p>Penyaring mencoba menghapus tag <code>&lt;script&gt;</code>. Tunjukkan bypass dengan tag HTML yang bisa memicu event (menggunakan atribut onerror/onload).</p>
                                        <div class="debug-info">&lt;img src=x onerror=alert('Bypass_Reflected!')&gt;</div>
                                    <?php else: ?>
                                        <strong>📖 Catatan Presenter (Level HIGH):</strong>
                                        <p>Fungsi `htmlspecialchars()` membuat input menjadi statis dan aman. Demonstrasikan bahwa payload di atas kini dirender sebagai teks biasa.</p>
                                    <?php endif; ?>
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
                                $comments = read_db($db_file);
                                foreach($comments as $c): 
                                    $badge = 'badge-guest';
                                    if($c['role'] == 'admin') $badge = 'badge-admin';
                                    if($c['role'] == 'user') $badge = 'badge-user';
                                ?>
                                    <div class="comment glass">
                                        <div class="comment-header">
                                            <span>
                                                <span class="badge <?= $badge ?>"><?= strtoupper($c['role']) ?></span>
                                                <b class="neon-text"><?= xss_filter($c['name']) ?></b>
                                            </span>
                                            <span>
                                                <?= $c['time'] ?>
                                                <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || (isset($c['sender_id']) && $_SESSION['username_id'] == $c['sender_id']))): ?>
                                                    | <a href="?delete_id=<?= $c['id'] ?>" class="delete-link"><i class="fas fa-trash"></i></a>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="comment-body">
                                            <?= xss_filter($c['message']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="presenter-mode">
                                <?php if($_SESSION['sec_level'] == 'Low'): ?>
                                    <strong>📖 Catatan Presenter (Level LOW):</strong>
                                    <p>Input disimpan ke JSON dan dirender langsung tanpa proteksi.</p>
                                    <div class="debug-info">&lt;script&gt;alert('Stored_XSS')&lt;/script&gt;</div>
                                <?php elseif($_SESSION['sec_level'] == 'Medium'): ?>
                                    <strong>📖 Catatan Presenter (Level MEDIUM):</strong>
                                    <p>Filter memblokir <code>&lt;script&gt;</code>. Coba payload ini untuk menyimpan celah (Bypass via event handler):</p>
                                    <div class="debug-info">&lt;svg onload="alert('Bypass_Stored')"&gt;</div>
                                <?php else: ?>
                                    <strong>📖 Catatan Presenter (Level HIGH):</strong>
                                    <p>Output sudah dienkode dengan entitas HTML sehingga aman sepenuhnya.</p>
                                <?php endif; ?>
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

                    case 'contact': ?>
                        <div class="card glass">
                            <h2><i class="fas fa-envelope-open-text"></i> Contact Support (Blind XSS Target)</h2>
                            <p>Laporkan masalah atau bug secara anonim. Laporan ini hanya bisa dilihat secara rahasia oleh <strong>Administrator</strong> di Dashboard mereka.</p>
                            
                            <?php if(isset($msg_report)): ?>
                                <p class="success-text" style="color:#00f5d4;"><?= $msg_report ?></p>
                            <?php endif; ?>

                            <form method="POST" class="post-form glass">
                                <input type="text" name="reporter_name" placeholder="Nama / Anonim">
                                <textarea name="report_message" rows="4" placeholder="Jelaskan detail bug... (Insert Payload Here)"></textarea>
                                <button type="submit" name="report_bug" class="btn btn-primary">Submit Report</button>
                            </form>

                            <div class="presenter-mode">
                                <?php if($_SESSION['sec_level'] == 'Low'): ?>
                                    <strong>📖 Catatan Presenter (Level LOW):</strong>
                                    <p>Serangan Blind XSS tidak meledak di halaman ini. Anda harus mensimulasikan korban dengan masuk sebagai Administrator dan membuka Dashboard untuk melihat eksekusinya yang tersembunyi.</p>
                                    <div class="debug-info">&lt;script&gt;fetch('stealer.php?c=Blind_XSS_Success')&lt;/script&gt;</div>
                                <?php elseif($_SESSION['sec_level'] == 'Medium'): ?>
                                    <strong>📖 Catatan Presenter (Level MEDIUM):</strong>
                                    <p>Sama seperti Stored XSS, bypass proteksi dengan menggunakan HTML tag yang mengeksekusi fetch secara pasif di Dashboard Admin.</p>
                                    <div class="debug-info">&lt;img src=x onerror="fetch('stealer.php?c=Bypass_Blind')"&gt;</div>
                                <?php else: ?>
                                    <strong>📖 Catatan Presenter (Level HIGH):</strong>
                                    <p>Sistem aman. Laporan yang masuk akan dirender sebagai teks murni di panel Administrator.</p>
                                <?php endif; ?>
                            </div>
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
                                <div class="admin-panel glass" style="padding:1rem; border:1px solid var(--accent); border-radius:8px;">
                                    <h4><i class="fas fa-shield-alt"></i> Admin Privileges</h4>
                                    <p>Secret Token: <code>SUPER_SECRET_ADMIN_KEY_12345</code></p>
                                    <a href="?action=reset" class="btn btn-danger btn-sm">Emergency Wipe Database</a>
                                    
                                    <hr class="divider" style="margin:2rem 0;">
                                    <h4 style="color:var(--accent);"><i class="fas fa-inbox"></i> Support Reports (Blind XSS Target Area)</h4>
                                    <p>Log pelaporan masalah dari publik. <strong>JIKA ADA PAYLOAD BERBAHAYA, AKAN MELEDAK DI SINI.</strong></p>
                                    
                                    <div class="reports-list" style="max-height: 400px; overflow-y: auto;">
                                        <?php 
                                        $reports = read_db($report_file);
                                        if(empty($reports)) echo "<p style='color:var(--text-muted);'>Tidak ada laporan baru.</p>";
                                        foreach($reports as $r): 
                                        ?>
                                            <div class="comment" style="background:rgba(255,0,85,0.05); border-left: 3px solid var(--accent); margin-bottom:1rem; padding:1rem;">
                                                <div class="comment-header" style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.5rem;">
                                                    <b>Reporter: <?= xss_filter($r['sender']) ?></b> | Waktu: <?= $r['time'] ?>
                                                </div>
                                                <div class="comment-body" style="color:#fff;">
                                                    <!-- BLIND XSS EXECUTION HAPPENS HERE -->
                                                    <?= xss_filter($r['message']) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
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
