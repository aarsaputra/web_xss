<?php
require 'core.php';

// Handle Post Creation
if (isset($_POST['create_post'])) {
    $content = trim($_POST['post_content']);
    if (!empty($content)) {
        $posts = read_db($db_posts);
        $new_post = [
            'id' => uniqid('post_'),
            'author' => $_SESSION['user'],
            'author_id' => $_SESSION['username_id'],
            'role' => $_SESSION['role'],
            'content' => $content,
            'time' => date("Y-m-d H:i")
        ];
        array_unshift($posts, $new_post);
        write_db($db_posts, $posts);
        header("Location: index.php");
        exit;
    }
}

// Handle Post Deletion
if (isset($_GET['delete_post'])) {
    $id = $_GET['delete_post'];
    $posts = read_db($db_posts);
    $filtered = [];
    foreach ($posts as $p) {
        if ($p['id'] == $id && ($_SESSION['role'] === 'admin' || $p['author_id'] === $_SESSION['username_id'])) {
            continue; // Skip appending (deletes it)
        }
        $filtered[] = $p;
    }
    write_db($db_posts, $filtered);
    
    // Also delete associated comments
    $comments = read_db($db_comments);
    $filtered_comments = array_filter($comments, function($c) use ($id) { return $c['post_id'] !== $id; });
    write_db($db_comments, array_values($filtered_comments));
    
    header("Location: index.php");
    exit;
}

// Handle Comment Creation
if (isset($_POST['create_comment'])) {
    $content = trim($_POST['comment_content']);
    $post_id = $_POST['post_id'];
    if (!empty($content) && !empty($post_id)) {
        $comments = read_db($db_comments);
        $new_comment = [
            'id' => uniqid('cmt_'),
            'post_id' => $post_id,
            'author' => $_SESSION['user'],
            'author_id' => $_SESSION['username_id'],
            'content' => $content,
            'time' => date("H:i")
        ];
        $comments[] = $new_comment;
        write_db($db_comments, $comments);
        header("Location: index.php");
        exit;
    }
}

// Handle Comment Deletion
if (isset($_GET['delete_comment'])) {
    $id = $_GET['delete_comment'];
    $comments = read_db($db_comments);
    $filtered = [];
    foreach ($comments as $c) {
        if ($c['id'] == $id && ($_SESSION['role'] === 'admin' || $c['author_id'] === $_SESSION['username_id'])) {
            continue;
        }
        $filtered[] = $c;
    }
    write_db($db_comments, $filtered);
    header("Location: index.php");
    exit;
}

// Search Query (Reflected XSS)
$search_query = isset($_GET['q']) ? $_GET['q'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOCial | Secure Network?</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="cyber-bg" id="social-body">

    <!-- Top Navbar -->
    <nav class="social-nav">
        <a href="index.php" class="social-brand" style="text-shadow:0 0 10px var(--primary-glow);">SOCial<span style="color:#fff;">Net</span></a>
        
        <form method="GET" class="search-box vulnerable" data-vuln="Reflected XSS (Input)">
            <i class="fas fa-search" style="color:var(--text-muted)"></i>
            <input type="text" name="q" placeholder="Cari teman atau postingan (Payload)..." value="<?= htmlspecialchars($search_query, ENT_QUOTES) ?>">
        </form>

        <div style="display:flex; align-items:center; gap: 1rem;">
            <!-- Inherited Security Level Dropdown -->
            <div class="nav-link dropdown" style="position:relative; cursor:pointer; padding:5px 10px; border:1px solid var(--border); border-radius:20px;">
                <span style="color:var(--text-muted); font-size:0.8rem;">WAF Level: <strong style="color:var(--primary)"><?= $_SESSION['sec_level'] ?></strong></span>
                <div class="dropdown-content" style="position:absolute; top:100%; right:0; background:var(--bg-card); border:1px solid var(--border); border-radius:8px; display:none; flex-direction:column; min-width:120px; z-index:1001; overflow:hidden; margin-top: 5px;">
                    <a href="?set_level=Low" style="color:#00f5d4; padding:10px 15px; text-decoration:none; display:block; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.85rem;">Low (0% Filter)</a>
                    <a href="?set_level=Medium" style="color:orange; padding:10px 15px; text-decoration:none; display:block; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.85rem;">Medium (Tag Block)</a>
                    <a href="?set_level=High" style="color:#ff0055; padding:10px 15px; text-decoration:none; display:block; font-size:0.85rem;">High (Full Entity)</a>
                </div>
            </div>
            <style>
                /* Using a pseudo-element bridge to prevent hover gap issues */
                .dropdown::after { content:''; position:absolute; top:100%; left:0; right:0; height:10px; }
                .dropdown:hover .dropdown-content { display: flex !important; }
                .dropdown-content a:hover { background: rgba(0,245,212,0.1); }
            </style>
            
            <button onclick="toggleHackerVision()" class="btn btn-outline" id="hkv-btn" style="border-color:var(--accent); color:var(--accent);">
                <i class="fas fa-eye"></i> Hacker Vision
            </button>
            <a href="../index.php?page=dashboard" class="menu-item" style="padding: 5px 10px; background:rgba(0,0,0,0.5);"><img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']) ?>&background=0D8ABC&color=fff" width="30" style="border-radius:50%"></a>
        </div>
    </nav>

    <div class="social-wrapper">
        <!-- Left Sidebar -->
        <aside class="left-menu">
            <a href="index.php" class="menu-item active"><i class="fas fa-home"></i> Beranda</a>
            <a href="#profile=<?= urlencode($_SESSION['user']) ?>" class="menu-item vulnerable" data-vuln="DOM XSS (Hash Routing)"><i class="fas fa-user-astronaut"></i> Profil Saya</a>
            <a href="index.php" class="menu-item"><i class="fas fa-bell"></i> Notifikasi</a>
            <hr style="border-color:var(--border); margin:1rem 0;">
            <a href="../index.php" class="menu-item" style="color:var(--text-muted)"><i class="fas fa-sign-out-alt"></i> Kembali ke Lab Dasar</a>
            <a href="../captured.php" target="_blank" class="menu-item" style="color:var(--accent)"><i class="fas fa-terminal"></i> Terminal Interceptor</a>
        </aside>

        <!-- Center Feed -->
        <main class="feed-zone">
            
            <!-- REFLECTED XSS TRIGGER -->
            <?php if (!empty($search_query)): ?>
            <div class="create-post vulnerable" data-vuln="Reflected XSS (Output)" style="padding: 1rem; margin-bottom: 2rem;">
                <p>Hasil pencarian untuk: <strong style="color:var(--primary); font-size:1.2rem;"><?= xss_filter($search_query) ?></strong></p>
                <p style="color:var(--text-muted); font-size:0.9rem;">Tidak menemukan hasil yang cocok dengan kueri tersebut di server kami.</p>
            </div>
            <?php endif; ?>

            <div id="central-feed-area">
                <!-- CREATE POST -->
                <div class="create-post">
                    <form method="POST">
                        <textarea name="post_content" rows="3" placeholder="Apa yang sedang Anda retas hari ini, <?= $_SESSION['user'] ?>?"></textarea>
                        <div class="post-actions-top">
                            <button type="submit" name="create_post" class="btn btn-primary" style="padding: 8px 20px; border-radius: 20px;">
                                <i class="fas fa-bolt"></i> Broadcast
                            </button>
                        </div>
                    </form>
                </div>

                <!-- FEED STREAM (STORED XSS) -->
                <div id="feed-stream">
                    <?php 
                    $posts = read_db($db_posts);
                    if (empty($posts)) {
                        echo "<div class='post-card' style='text-align:center; padding:3rem;'>
                                <i class='fas fa-satellite-dish' style='font-size:3rem; color:var(--border); margin-bottom:1rem;'></i>
                                <p style='color:var(--text-muted);'>Belum ada transmisi di jaringan ini.</p>
                              </div>";
                    }
                    foreach ($posts as $post): 
                    ?>
                    <div class="post-card vulnerable" data-vuln="Stored XSS (Post Content)">
                        <div class="post-header">
                            <div class="author-info">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($post['author']) ?>&background=random&color=fff" class="author-avatar">
                                <div class="author-meta">
                                    <span class="author-name"><?= xss_filter($post['author']) ?> <?php if($post['role']=='admin') echo "<i class='fas fa-check-circle' title='Admin' style='font-size:0.8rem;'></i>"; ?></span>
                                    <span class="post-date"><?= $post['time'] ?></span>
                                </div>
                            </div>
                            <?php if($_SESSION['role'] === 'admin' || $post['author_id'] === $_SESSION['username_id']): ?>
                            <div class="dropdown">
                                <button class="post-btn"><i class="fas fa-ellipsis-h"></i></button>
                                <div class="dropdown-content">
                                    <a href="?delete_post=<?= $post['id'] ?>" style="color:var(--accent)"><i class="fas fa-trash"></i> Hapus Broadcast</a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-content">
                            <?= xss_filter($post['content']) ?>
                        </div>
                        
                        <div class="post-footer">
                            <button class="post-btn" onclick="this.style.color='#ff0055'; this.innerHTML='<i class=\'fas fa-heart\'></i> Liked';"><i class="far fa-heart"></i> Like</button>
                            <button class="post-btn vulnerable" data-vuln="Stored XSS (Comment Area)" onclick="document.getElementById('comment-box-<?= $post['id'] ?>').style.display='block'; document.querySelector('#comment-box-<?= $post['id'] ?> input[name=comment_content]').focus();"><i class="far fa-comment-alt"></i> Comment</button>
                            <!-- BLIND XSS TRIGGER -->
                            <form method="POST" action="../index.php?page=contact" style="display:inline;">
                                <input type="hidden" name="reporter_name" value="<?= $_SESSION['user'] ?>">
                                <!-- Prompting user via JS to inject payload -->
                                <button type="button" class="post-btn danger vulnerable" data-vuln="Blind XSS" onclick="if(p=prompt('Laporkan postingan ini ke Admin. Masukkan alasan / payload:')) { this.nextElementSibling.value=p; this.parentNode.submit(); }">
                                    <i class="fas fa-flag"></i> Report
                                </button>
                                <input type="hidden" name="report_message" value="">
                                <input type="hidden" name="report_bug" value="1">
                            </form>
                        </div>

                        <!-- COMMENTS SECTION -->
                        <div class="comments-section" style="padding: 1rem 1.5rem; background: rgba(0,0,0,0.3); border-top: 1px solid var(--border);">
                            <?php
                            $comments = read_db($db_comments);
                            $post_comments = array_filter($comments, function($c) use ($post) { return $c['post_id'] === $post['id']; });
                            foreach($post_comments as $comment):
                            ?>
                            <div class="comment-item vulnerable" data-vuln="Stored XSS (Rendered Comment)" style="display:flex; gap:10px; margin-bottom:1rem;">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($comment['author']) ?>&background=random&color=fff&size=30" style="border-radius:50%; width:30px; height:30px;">
                                <div style="background:var(--bg-dark); padding:10px 15px; border-radius:15px; border:1px solid var(--border); width:100%;">
                                    <div style="display:flex; justify-content:space-between;">
                                        <strong style="color:var(--primary); font-size:0.9rem;"><?= xss_filter($comment['author']) ?></strong>
                                        <?php if($_SESSION['role'] === 'admin' || $comment['author_id'] === $_SESSION['username_id']): ?>
                                            <a href="?delete_comment=<?= $comment['id'] ?>" style="color:var(--text-muted); font-size:0.8rem;"><i class="fas fa-times"></i></a>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color:var(--text); margin-top:5px; font-size:0.9rem; word-break: break-word;">
                                        <?= xss_filter($comment['content']) ?>
                                    </div>
                                    <div style="margin-top:8px;">
                                        <button class="post-btn" style="font-size:0.75rem; padding:0; display:inline-block;" onclick="document.getElementById('comment-box-<?= $post['id'] ?>').style.display='block'; const inp = document.querySelector('#comment-box-<?= $post['id'] ?> input[name=comment_content]'); inp.value='@<?= xss_filter($comment['author']) ?> '; inp.focus();"><i class="fas fa-reply"></i> Balas</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- COMMENT FORM -->
                            <div id="comment-box-<?= $post['id'] ?>" style="display:none; margin-top:10px;">
                                <form method="POST">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <div style="display:flex; gap:10px;">
                                        <input type="text" name="comment_content" placeholder="Tulis komentar (Payload)..." style="flex:1; background:var(--bg-dark); border:1px solid var(--border); border-radius:15px; padding:8px 15px; color:var(--text); outline:none;" required>
                                        <button type="submit" name="create_comment" class="btn btn-outline" style="border-radius:15px;"><i class="fas fa-paper-plane"></i></button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>

                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- DOM XSS Profile View Area -->
            <div id="dom-profile-view" style="display:none;" class="create-post vulnerable" data-vuln="DOM XSS Execution"></div>

        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <div class="widget-box">
                <h4 class="widget-title">Active Connections (<?= rand(3, 10) ?>)</h4>
                <div style="display:flex; align-items:center; gap: 10px; margin-bottom:15px;">
                    <div style="width:10px; height:10px; background:#00f5d4; border-radius:50%; box-shadow:0 0 5px #00f5d4;"></div>
                    <span><strong style="color:var(--primary);"><?= $_SESSION['user'] ?></strong> (You)</span>
                </div>
                <!-- Fake users -->
                <div style="display:flex; align-items:center; gap: 10px; margin-bottom:15px; color:var(--text-muted);">
                    <div style="width:10px; height:10px; background:#ff0055; border-radius:50%;"></div>
                    <span>Administrator (Idle)</span>
                </div>
                <div style="display:flex; align-items:center; gap: 10px; color:var(--text-muted);">
                    <div style="width:10px; height:10px; background:#8892b0; border-radius:50%;"></div>
                    <span>Guest_<?= rand(100,999) ?> (Offline)</span>
                </div>
            </div>
            <div class="widget-box">
                <h4 class="widget-title">Network Info</h4>
                <p style="font-size:0.8rem; color:var(--text-muted); line-height:1.6;">
                    WAF Status: <strong style="color:var(--primary);"><?= $_SESSION['sec_level'] ?> Mode</strong><br>
                    Intrusion Alert: <span style="color:var(--accent);">OFFLINE</span><br>
                    Server Log: <a href="../captured.php" target="_blank" style="color:var(--secondary);">Monitor.exe</a>
                </p>
            </div>
        </aside>
    </div>

    <script>
        // DOM XSS Logic for Profile Hash
        function handleRouting() {
            const hash = window.location.hash;
            const centralArea = document.getElementById('central-feed-area');
            const profileView = document.getElementById('dom-profile-view');
            
            if (hash.startsWith('#profile=')) {
                // DOM XSS executes here via innerHTML!
                const username = decodeURIComponent(hash.split('=')[1]);
                centralArea.style.display = 'none';
                
                profileView.style.display = 'block';
                profileView.innerHTML = `
                    <div style="text-align:center; padding: 2rem;">
                        <img src="https://ui-avatars.com/api/?name=${username}&background=random&color=fff&size=100" style="border-radius:50%; margin-bottom:1rem; border:3px solid var(--primary);">
                        <h2 style="color:var(--primary);">${username}</h2>
                        <p style="color:var(--text-muted)">User ini menolak akses ke timeline mereka.</p>
                        <button class="btn btn-outline" onclick="window.location.hash=''" style="margin-top:1rem;">Kembali ke Feed</button>
                    </div>
                `;
            } else {
                centralArea.style.display = 'block';
                profileView.style.display = 'none';
                profileView.innerHTML = '';
            }
        }

        window.addEventListener('hashchange', handleRouting);
        window.addEventListener('load', handleRouting);

        // Hacker Vision Toggle (Outlines vulnerable elements)
        function toggleHackerVision() {
            const body = document.getElementById('social-body');
            body.classList.toggle('hacker-vision-active');
            
            const isActive = body.classList.contains('hacker-vision-active');
            localStorage.setItem('hacker_vision', isActive);
            
            // Visual Update for button
            const btn = document.getElementById('hkv-btn');
            if (isActive) {
                btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hacker Vision ON';
                btn.style.background = 'rgba(255,0,85,0.2)';
                btn.style.boxShadow = '0 0 10px #ff0055';
            } else {
                btn.innerHTML = '<i class="fas fa-eye"></i> Hacker Vision OFF';
                btn.style.background = 'transparent';
                btn.style.boxShadow = 'none';
            }
        }

        // Restore state on load
        window.addEventListener('load', () => {
            if (localStorage.getItem('hacker_vision') === 'true') {
                document.getElementById('social-body').classList.add('hacker-vision-active');
                const btn = document.getElementById('hkv-btn');
                btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hacker Vision ON';
                btn.style.background = 'rgba(255,0,85,0.2)';
                btn.style.boxShadow = '0 0 10px #ff0055';
            }
        });
    </script>
</body>
</html>
