<?php
session_start();
date_default_timezone_set('Asia/Manila');

// --- FILE PATHS ---
$DATA_FILE  = 'videos.json';
$KEYS_FILE  = 'keys.json';
$UPLOAD_DIR = 'uploads/';

// --- INITIALIZE SYSTEM FILES ---
if (!is_dir($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0777, true);
}
if (!file_exists($DATA_FILE)) {
    file_put_contents($DATA_FILE, json_encode([]));
}
if (!file_exists($KEYS_FILE)) {
    file_put_contents($KEYS_FILE, json_encode([]));
}

// --- LOAD DATA ---
$all_videos = json_decode(file_get_contents($DATA_FILE), true) ?: [];
$all_keys = json_decode(file_get_contents($KEYS_FILE), true) ?: [];

// --- CATEGORY LISTS ---
// Inalis ang NEW RELEASE sa main array dahil dynamic na ito ngayon
$main_cats = ['VIVAMAX', 'NOWCLIX', 'TBONX', 'CINEPOP', 'Premium'];
$movie_tab_cats = ['Latest Movie', 'Tagalog Dubbed', 'Pinoy Movie', 'Movies'];
$all_cats = array_merge($main_cats, $movie_tab_cats);

// --- ADMIN LOGIN ---
if (isset($_POST['login'])) {
    if ($_POST['user'] === "useradmin" && $_POST['pass'] === "admin123") {
        $_SESSION['admin'] = true;
        header("Location: index.php?phoenixadmin256");
        exit;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- KEY MANAGEMENT ---
if (isset($_POST['gen_key']) && isset($_SESSION['admin'])) {
    $new_key = "PHX-" . strtoupper(substr(md5(microtime()), 0, 8));
    $duration = $_POST['duration'];
    $expiry = time();
    
    if ($duration == '1h') $expiry += 3600;
    else if ($duration == '1d') $expiry += 86400;
    else if ($duration == '1m') $expiry += 2592000;
    else $expiry += 315360000;

    $all_keys[$new_key] = ['expiry' => $expiry, 'type' => $duration];
    file_put_contents($KEYS_FILE, json_encode($all_keys));
    header("Location: index.php?phoenixadmin256&key_created=$new_key");
    exit;
}

if (isset($_GET['del_key']) && isset($_SESSION['admin'])) {
    $target = $_GET['del_key'];
    if (isset($all_keys[$target])) {
        unset($all_keys[$target]);
        file_put_contents($KEYS_FILE, json_encode($all_keys));
    }
    header("Location: index.php?phoenixadmin256");
    exit;
}

// --- VIDEO SAVE/EDIT ---
if (isset($_GET['save']) && isset($_SESSION['admin'])) {
    $is_edit = (isset($_POST['edit_id']) && $_POST['edit_id'] !== "");
    
    $entry = [
        'title'      => $_POST['title'],
        'video'      => $_POST['link'],
        'thumb'      => $_POST['old_thumb'] ?? '',
        'category'   => $_POST['cat'],
        'is_premium' => (isset($_POST['is_premium']) ? 1 : 0),
        'time'       => time()
    ];

    if (isset($_FILES['thumb']) && $_FILES['thumb']['error'] == 0) {
        $tName = 'thumb_' . time() . '.jpg';
        move_uploaded_file($_FILES['thumb']['tmp_name'], $UPLOAD_DIR . $tName);
        $entry['thumb'] = $tName;
    }

    if ($is_edit) {
        // Maintain the original time if editing
        $entry['time'] = $all_videos[$_POST['edit_id']]['time'] ?? time();
        $all_videos[$_POST['edit_id']] = $entry;
    } else {
        array_unshift($all_videos, $entry);
    }

    file_put_contents($DATA_FILE, json_encode($all_videos));
    header("Location: index.php?phoenixadmin256");
    exit;
}

if (isset($_GET['delete']) && isset($_SESSION['admin'])) {
    array_splice($all_videos, (int)$_GET['delete'], 1);
    file_put_contents($DATA_FILE, json_encode($all_videos));
    header("Location: index.php?phoenixadmin256");
    exit;
}

// --- KEY VALIDATION ---
if (isset($_POST['check_key'])) {
    $k = $_POST['check_key'];
    if (isset($all_keys[$k])) {
        if (time() > $all_keys[$k]['expiry']) {
            unset($all_keys[$k]);
            file_put_contents($KEYS_FILE, json_encode($all_keys));
            die("expired");
        }
        die("valid");
    }
    die("invalid");
}

$tab = $_GET['tab'] ?? 'HOME';
$edit_data = (isset($_GET['edit']) && isset($all_videos[$_GET['edit']])) ? $all_videos[$_GET['edit']] : null;

// --- DYNAMIC NEW RELEASES (48 Hours) ---
$new_releases = array_filter($all_videos, function($v) {
    $forty_eight_hours = 48 * 3600;
    return (time() - ($v['time'] ?? 0)) <= $forty_eight_hours;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PINOYMOVIEFLIX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { 
            --accent: #ff007a; 
            --bg: #050505; 
            --card: #111111; 
            --text: #ffffff; 
        }

        body { 
            margin: 0; 
            background: var(--bg); 
            color: var(--text); 
            font-family: 'Inter', sans-serif; 
            padding-bottom: 110px; 
            overflow-x: hidden; 
            -webkit-tap-highlight-color: transparent; 
        }

        header { 
            padding: 15px 20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
            background: rgba(5, 5, 5, 0.9); 
            backdrop-filter: blur(15px); 
            border-bottom: 1px solid #1a1a1a; 
        }

        .logo { 
            font-weight: 900; 
            font-size: 22px; 
            text-transform: uppercase; 
            letter-spacing: -1.2px; 
        }

        .logo span { color: var(--accent); }

        .container { padding: 15px; }

        .section-label { 
            font-size: 15px; 
            font-weight: 900; 
            margin: 25px 0 12px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            text-transform: uppercase; 
        }

        .section-label a { 
            color: var(--accent); 
            font-size: 11px; 
            text-decoration: none; 
            font-weight: 700; 
        }

        /* --- ANIMATED DOT --- */
        .dot {
            height: 10px;
            width: 10px;
            background-color: #ff4500;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            box-shadow: 0 0 0 0 rgba(255, 69, 0, 1);
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 69, 0, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 69, 0, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 69, 0, 0); }
        }

        .slide-row { 
            display: flex; 
            gap: 12px; 
            overflow-x: auto; 
            padding-bottom: 15px; 
            scrollbar-width: none; 
        }

        .slide-row::-webkit-scrollbar { display: none; }

        .slide-item { flex: 0 0 150px; }

        .movie-card { 
            position: relative; 
            border-radius: 12px; 
            overflow: hidden; 
            background: var(--card); 
            border: 1px solid #1a1a1a; 
            cursor: pointer; 
            aspect-ratio: 2/3; 
        }

        .poster { width: 100%; height: 100%; object-fit: cover; }

        .movie-meta { 
            position: absolute; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            padding: 25px 8px 10px; 
            background: linear-gradient(to top, #000, transparent); 
        }

        .movie-title { 
            font-size: 10px; 
            font-weight: 700; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }

        .badge-vip { 
            position: absolute; 
            top: 8px; 
            right: 8px; 
            background: #ffcc00; 
            color: #000; 
            font-size: 8px; 
            font-weight: 900; 
            padding: 3px 7px; 
            border-radius: 4px; 
            z-index: 10; 
        }

        .badge-new { 
            position: absolute; 
            top: 8px; 
            left: 8px; 
            background: var(--accent); 
            color: #fff; 
            font-size: 8px; 
            font-weight: 900; 
            padding: 3px 7px; 
            border-radius: 4px; 
            z-index: 10; 
        }

        .bottom-nav { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            background: rgba(10, 10, 10, 0.95); 
            backdrop-filter: blur(20px); 
            display: flex; 
            justify-content: space-around; 
            padding: 15px 0; 
            border-top: 1px solid #1a1a1a; 
            z-index: 2000; 
        }

        .nav-item { 
            text-decoration: none; 
            color: #555; 
            font-size: 9px; 
            font-weight: 800; 
            text-align: center; 
        }

        .nav-item.active { color: var(--accent); }

        .nav-item svg { 
            width: 24px; 
            height: 24px; 
            margin-bottom: 5px; 
            display: block; 
            margin-left: auto; 
            margin-right: auto; 
            fill: currentColor; 
        }

        #player, #keyPrompt { 
            display: none; 
            position: fixed; 
            inset: 0; 
            background: #000; 
            z-index: 3000; 
            flex-direction: column; 
        }

        .modal-ui { 
            background: #111; 
            padding: 40px 20px; 
            border-radius: 25px; 
            text-align: center; 
            margin: auto; 
            width: 85%; 
            max-width: 380px; 
            border: 1px solid #222; 
        }

        input, select { 
            background: #1a1a1a; 
            border: 1px solid #333; 
            color: #fff; 
            padding: 14px; 
            border-radius: 10px; 
            width: 100%; 
            margin-bottom: 12px; 
            font-size: 14px; 
        }

        .btn-glow { 
            background: var(--accent); 
            color: #fff; 
            border: none; 
            padding: 14px; 
            border-radius: 10px; 
            width: 100%; 
            font-weight: 900; 
            cursor: pointer; 
            text-transform: uppercase; 
        }

        .manage-list { 
            margin-top: 15px; 
            max-height: 200px; 
            overflow-y: auto; 
            background: #000; 
            border-radius: 10px; 
            padding: 10px; 
            border: 1px solid #222; 
        }

        .manage-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 10px; 
            border-bottom: 1px solid #111; 
            font-size: 11px; 
        }

        /* --- DRIVE PROTECTION --- */
        .video-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
        }

        /* Pinipigilan ang pag-pindot sa top area ng drive player */
        .drive-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 15%; /* Tinatakpan ang account/select icons */
            z-index: 10;
            background: transparent;
        }
        
        <style>
    /* Inayos na input box */
    #keyInput {
        width: 100%;
        box-sizing: border-box; /* Mahalaga ito para hindi lumampas ang box */
        margin-bottom: 15px;
        text-align: center;
        border: 1px solid #333;
        outline: none;
    }

    #keyInput:focus {
        border-color: var(--accent);
    }

    /* Telegram Button Style */
    .btn-tg {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #0088cc;
        color: #fff;
        text-decoration: none;
        padding: 12px;
        border-radius: 10px;
        width: 100%;
        font-weight: 700;
        font-size: 13px;
        margin-top: 10px;
        text-transform: uppercase;
        box-sizing: border-box;
    }
    </style>
</head>
<body>

<header>
    <div class="logo">PINOY<span>MOVIEFLIX</span></div>
    <?php if (isset($_SESSION['admin'])): ?>
        <a href="?logout=1" style="color:red; font-size:11px; text-decoration:none; font-weight:900;">LOGOUT</a>
    <?php endif; ?>
</header>

<div class="container">
    
    <?php if ($tab == 'HOME' || $tab == 'MOVIES_TAB'): ?>
        
        <?php if (!empty($new_releases) && $tab == 'HOME'): ?>
            <div class="section-label">
                <span><span class="dot"></span> NEW RELEASE</span>
            </div>
            <div class="slide-row">
                <?php foreach ($new_releases as $v): 
                    $isP = (($v['category'] ?? '') == 'Premium' || ($v['is_premium'] ?? 0) == 1); 
                ?>
                    <div class="slide-item">
                        <div class="movie-card" onclick="checkAccess('<?= $isP ? 1 : 0 ?>', '<?= addslashes($v['video']) ?>')">
                            <img class="poster" src="uploads/<?= $v['thumb'] ?>" loading="lazy">
                            <div class="badge-new">NEW</div>
                            <?php if($isP): ?><div class="badge-vip">VIP</div><?php endif; ?>
                            <div class="movie-meta">
                                <div class="movie-title"><?= htmlspecialchars($v['title']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php 
        $display_cats = ($tab == 'HOME') ? $main_cats : $movie_tab_cats;
        foreach ($display_cats as $cat):
            $filtered = array_filter($all_videos, fn($v) => ($v['category'] ?? '') === $cat);
            if (!empty($filtered)):
        ?>
            <div class="section-label">
                <span><?= $cat ?></span>
                <a href="?tab=<?= urlencode($cat) ?>">SEE ALL</a>
            </div>
            <div class="slide-row">
                <?php foreach ($filtered as $v): 
                    $isP = (($v['category'] ?? '') == 'Premium' || ($v['is_premium'] ?? 0) == 1); 
                ?>
                    <div class="slide-item">
                        <div class="movie-card" onclick="checkAccess('<?= $isP ? 1 : 0 ?>', '<?= addslashes($v['video']) ?>')">
                            <img class="poster" src="uploads/<?= $v['thumb'] ?>" loading="lazy">
                            <?php if($isP): ?><div class="badge-vip">VIP</div><?php endif; ?>
                            <div class="movie-meta">
                                <div class="movie-title"><?= htmlspecialchars($v['title']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; endforeach; ?>

    <?php else: ?>
        
        <div class="section-label"><?= htmlspecialchars($tab) ?></div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <?php foreach ($all_videos as $v): 
                if (($v['category'] ?? '') === $tab): 
                    $isP = (($v['category'] ?? '') == 'Premium' || ($v['is_premium'] ?? 0) == 1); 
            ?>
                <div class="movie-card" onclick="checkAccess('<?= $isP ? 1 : 0 ?>', '<?= addslashes($v['video']) ?>')">
                    <img class="poster" src="uploads/<?= $v['thumb'] ?>">
                    <?php if($isP): ?><div class="badge-vip">VIP</div><?php endif; ?>
                    <div class="movie-meta">
                        <div class="movie-title"><?= htmlspecialchars($v['title']) ?></div>
                    </div>
                </div>
            <?php endif; endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="bottom-nav">
    <a href="?tab=HOME" class="nav-item <?= ($tab == 'HOME') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>HOME
    </a>
    <a href="?tab=VIVAMAX" class="nav-item <?= ($tab == 'VIVAMAX') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 9H3V5h9v7z"/></svg>VIVA
    </a>
    <a href="?tab=MOVIES_TAB" class="nav-item <?= ($tab == 'MOVIES_TAB') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2z"/></svg>MOVIES
    </a>
        <a href="?tab=CINEPOP" class="nav-item <?= ($tab == 'VIVAMAX') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 9H3V5h9v7z"/></svg>CINEPOP
    </a>
    <a href="?tab=TBONX" class="nav-item <?= ($tab == 'TBONX') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>TBONX
    </a>
    <a href="?tab=Premium" class="nav-item <?= ($tab == 'Premium') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M12 2L1 9l11 7 11-7-11-7z"/></svg>Vip
    </a>
</div>

<div id="player">
    <div style="padding: 15px; display: flex; justify-content: space-between; align-items: center; background: #111;">
        <span style="font-weight: 900; color: var(--accent); font-size: 12px;">STREAMING</span>
        <span onclick="closePlayer()" style="font-size: 24px; cursor: pointer;">✕</span>
    </div>
    <div id="iframe-target" class="video-wrapper"></div>
</div>

<div id="keyPrompt">
    <div class="modal-ui">
        <h2 style="margin-top:0;">💎 VIP ACCESS</h2>
        <p style="font-size: 11px; color: #aaa; margin-bottom: 20px;">Enter your activation key to unlock premium content.</p>
        
        <input type="text" id="keyInput" placeholder="Paste Key Here...">
        
        <button class="btn-glow" onclick="validateKey()">UNLOCK NOW</button>

        <div style="margin: 15px 0; border-top: 1px solid #222; position: relative;">
            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #111; padding: 0 10px; font-size: 10px; color: #555;">OR</span>
        </div>

        <a href="https://t.me/Xoptima" target="_blank" class="btn-tg">
            <svg style="width:18px; height:18px;" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
            GET VIP KEY (ADMIN)
        </a>

        <p onclick="document.getElementById('keyPrompt').style.display='none'" style="margin-top: 20px; cursor: pointer; color: #555; font-size: 11px; font-weight: bold;">CANCEL</p>
    </div>
</div>

<script>
    function checkAccess(isP, vid) {
        if (isP == 1 && !localStorage.getItem('vault_key')) {
            document.getElementById('keyPrompt').style.display = 'flex';
        } else {
            playVideo(vid);
        }
    }

    function validateKey() {
        let k = document.getElementById('keyInput').value.trim();
        let fd = new FormData(); 
        fd.append('check_key', k);
        
        fetch('', {method:'POST', body:fd})
            .then(r => r.text())
            .then(res => {
                if(res == 'valid'){ 
                    localStorage.setItem('vault_key', k); 
                    location.reload(); 
                } else {
                    alert('Invalid or Expired!');
                }
            });
    }

    function playVideo(u) {
        let target = document.getElementById('iframe-target');
        if(u.includes('drive.google.com')) {
            let id = u.split('/d/')[1]?.split('/')[0] || u.split('id=')[1]?.split('&')[0];
            target.innerHTML = `
                <div class="drive-overlay"></div>
                <iframe src="https://drive.google.com/file/d/${id}/preview" style="width:100%; height:100%; border:none;" allowfullscreen></iframe>
            `;
        } else {
            target.innerHTML = `<video src="uploads/${u}" controls autoplay style="width:100%; height:100%;"></video>`;
        }
        document.getElementById('player').style.display = 'flex';
    }

    function closePlayer() { 
        document.getElementById('player').style.display = 'none'; 
        document.getElementById('iframe-target').innerHTML = ''; 
    }
</script>

<?php if (isset($_GET['phoenixadmin256'])): ?>
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 10000; display: flex; overflow-y: auto; padding: 20px;">
        <div style="background: #111; padding: 25px; border-radius: 20px; width: 100%; max-width: 450px; margin: auto; border: 1px solid #222;">
            
            <?php if(!isset($_SESSION['admin'])): ?>
                <h3 style="text-align:center; color:var(--accent);">ADMIN</h3>
                <form method="post">
                    <input name="user" placeholder="User">
                    <input type="password" name="pass" placeholder="Pass">
                    <button name="login" class="btn-glow">LOGIN</button>
                </form>
            <?php else: ?>
                <h3 style="color:var(--accent);">UPLOAD / EDIT</h3>
                <form action="?save=1" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" value="<?= $_GET['edit'] ?? '' ?>">
                    <input type="hidden" name="old_thumb" value="<?= $edit_data['thumb'] ?? '' ?>">
                    <input name="title" placeholder="Title" value="<?= $edit_data['title'] ?? '' ?>" required>
                    <select name="cat">
                        <?php foreach($all_cats as $c): ?>
                            <option value="<?= $c ?>" <?= ($edit_data && $edit_data['category'] == $c) ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="file" name="thumb" <?= $edit_data ? '' : 'required' ?>>
                    <input name="link" placeholder="Link / Filename" value="<?= $edit_data['video'] ?? '' ?>" required>
                    <label style="font-size:12px; display:block; margin-bottom:10px;">
                        <input type="checkbox" name="is_premium" <?= ($edit_data && ($edit_data['is_premium'] ?? 0)) ? 'checked' : '' ?>> SET AS VIP ONLY
                    </label>
                    <button class="btn-glow">SAVE VIDEO</button>
                </form>

                <h3 style="color:var(--accent); margin-top:20px;">KEY MANAGEMENT</h3>
                <form method="post">
                    <select name="duration">
                        <option value="1h">1 HOUR</option>
                        <option value="1d">1 DAY</option>
                        <option value="1m">1 MONTH</option>
                        <option value="life">LIFETIME</option>
                    </select>
                    <button name="gen_key" class="btn-glow" style="background:#333;">GENERATE KEY</button>
                </form>

                <div class="manage-list">
                    <?php foreach($all_keys as $k_str => $k_val): ?>
                        <div class="manage-item">
                            <span><?= $k_str ?> (<?= $k_val['type'] ?>)</span> 
                            <a href="?phoenixadmin256&del_key=<?= $k_str ?>" style="color:red; font-weight:bold; text-decoration:none;">DELETE</a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3 style="color:var(--accent); margin-top:20px;">VIDEOS LIST</h3>
                <div class="manage-list">
                    <?php foreach($all_videos as $i => $v): ?>
                        <div class="manage-item">
                            <span><?= htmlspecialchars($v['title']) ?></span> 
                            <div>
                                <a href="?phoenixadmin256&edit=<?= $i ?>" style="color:cyan; text-decoration:none;">EDIT</a> | 
                                <a href="?delete=<?= $i ?>" style="color:red; text-decoration:none;">DEL</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <a href="index.php" style="display:block; text-align:center; color:#555; margin-top:20px; font-weight:bold; text-decoration:none;">CLOSE ADMIN</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
