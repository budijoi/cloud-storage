<?php
session_start();
$password = 'admin123';

$config_path = __DIR__ . '/minifs.json';
$config = ['storage_path' => '/mnt/sdcard/storage'];
if (is_file($config_path)) $config = json_decode(file_get_contents($config_path), true) ?: $config;
$root = rtrim($config['storage_path'], '/');
if (!is_dir($root)) mkdir($root, 0755, true);

$is_auth = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
$msg = ''; $msg_type = '';

if (isset($_POST['login'])) {
    if ($password === '' || $_POST['pass'] === $password) {
        $_SESSION['auth'] = true; $is_auth = true;
    } else { $msg = 'Password salah!'; $msg_type = 'error'; }
}

if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if (!$is_auth && $password !== '') { include 'login-form.php'; exit; }

$dir = isset($_GET['dir']) ? $_GET['dir'] : '';
$dir = str_replace('..', '', $dir);
$cur_dir = $root . ($dir ? '/' . $dir : '');
if (!is_dir($cur_dir)) mkdir($cur_dir, 0755, true);

$audio_exts = ['mp3','wav','ogg','flac','aac','m4a','wma','opus'];
$video_exts = ['mp4','avi','mkv','mov','webm','wmv','flv','3gp','mpeg','ts'];
$image_exts = ['jpg','jpeg','png','gif','bmp','webp','svg','ico'];

// --- Actions ---

if (isset($_FILES['file'])) {
    $c = 0;
    $files_arr = $_FILES['file'];
    $names = is_array($files_arr['name']) ? $files_arr['name'] : [$files_arr['name']];
    $tmp = is_array($files_arr['tmp_name']) ? $files_arr['tmp_name'] : [$files_arr['tmp_name']];
    $err = is_array($files_arr['error']) ? $files_arr['error'] : [$files_arr['error']];
    foreach ($names as $i => $name) {
        if (isset($err[$i]) && $err[$i] === UPLOAD_ERR_OK && isset($tmp[$i])) {
            $fname = preg_replace('/[^a-zA-Z0-9._\-\s()]/', '_', basename($names[$i]));
            if (move_uploaded_file($tmp[$i], $cur_dir . '/' . $fname)) $c++;
        }
    }
    if ($c) { $msg = "$c file berhasil diupload!"; $msg_type = 'success'; }
    else { $msg = 'Gagal upload!'; $msg_type = 'error'; }
}

if (isset($_POST['mkdir'])) {
    $name = trim(basename($_POST['name']));
    $target = $cur_dir . '/' . $name;
    if ($name && !is_dir($target) && !is_file($target)) {
        mkdir($target, 0755);
        $msg = "Folder '$name' berhasil dibuat!"; $msg_type = 'success';
    } else { $msg = 'Nama tidak valid atau sudah ada!'; $msg_type = 'error'; }
}

if (isset($_POST['rename'])) {
    $old = basename($_POST['old']);
    $new = basename(trim($_POST['new']));
    if ($old && $new && $old !== $new && file_exists($cur_dir . '/' . $old) && !file_exists($cur_dir . '/' . $new)) {
        rename($cur_dir . '/' . $old, $cur_dir . '/' . $new);
        $msg = "'$old' -> '$new' berhasil!"; $msg_type = 'success';
    } else { $msg = 'Gagal rename!'; $msg_type = 'error'; }
}

if (isset($_GET['delete'])) {
    $name = basename($_GET['delete']);
    $path = $cur_dir . '/' . $name;
    if (is_file($path)) { unlink($path); $msg = "'$name' dihapus!"; $msg_type = 'success'; }
    elseif (is_dir($path)) { rmdir_recursive($path); $msg = "'$name' dihapus!"; $msg_type = 'success'; }
    else { $msg = 'Gagal hapus!'; $msg_type = 'error'; }
}

if (isset($_POST['bulk_delete'])) {
    $items_post = $_POST['items'] ?? []; $c = 0;
    foreach ($items_post as $item) {
        $path = $cur_dir . '/' . basename($item);
        if (is_file($path)) { unlink($path); $c++; }
        elseif (is_dir($path)) { rmdir_recursive($path); $c++; }
    }
    if ($c) { $msg = "$c item dihapus!"; $msg_type = 'success'; }
}

if (isset($_POST['move']) && isset($_POST['dest'])) {
    $dest_base = str_replace('..', '', trim($_POST['dest'], '/'));
    $dest_path = $root . ($dest_base ? '/' . $dest_base : '');
    if (!is_dir($dest_path)) mkdir($dest_path, 0755, true);
    $items_post = $_POST['items'] ?? []; $c = 0;
    foreach ($items_post as $item) {
        $src = $cur_dir . '/' . basename($item);
        $dst = $dest_path . '/' . basename($item);
        if (file_exists($src) && !file_exists($dst)) { rename($src, $dst); $c++; }
    }
    if ($c) { $msg = "$c item dipindahkan!"; $msg_type = 'success'; }
    else { $msg = 'Tidak ada yang dipindahkan!'; $msg_type = 'error'; }
}

if (isset($_POST['copy']) && isset($_POST['dest'])) {
    $dest_base = str_replace('..', '', trim($_POST['dest'], '/'));
    $dest_path = $root . ($dest_base ? '/' . $dest_base : '');
    if (!is_dir($dest_path)) mkdir($dest_path, 0755, true);
    $items_post = $_POST['items'] ?? []; $c = 0;
    foreach ($items_post as $item) {
        $src = $cur_dir . '/' . basename($item);
        $dst = $dest_path . '/' . basename($item);
        if (is_file($src) && !file_exists($dst)) { copy($src, $dst); $c++; }
    }
    if ($c) { $msg = "$c item disalin!"; $msg_type = 'success'; }
    else { $msg = 'Tidak ada yang disalin!'; $msg_type = 'error'; }
}

function rmdir_recursive($path) {
    foreach (glob($path . '/*') as $f) {
        is_dir($f) ? rmdir_recursive($f) : unlink($f);
    }
    rmdir($path);
}

// Build file list
$items = [];
foreach (glob($cur_dir . '/*') as $f) {
    $name = basename($f);
    $is_dir = is_dir($f);
    $size = $is_dir ? 0 : filesize($f);
    $mtime = filemtime($f);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $icon = '📄';
    if ($is_dir) $icon = '📁';
    else switch ($ext) {
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'bmp': case 'webp': case 'svg': $icon = '🖼️'; break;
        case 'pdf': $icon = '📄'; break;
        case 'doc': case 'docx': $icon = '📝'; break;
        case 'xls': case 'xlsx': case 'csv': $icon = '📊'; break;
        case 'zip': case 'rar': case '7z': case 'tar': case 'gz': $icon = '📦'; break;
        case 'mp3': case 'wav': case 'ogg': case 'flac': case 'aac': case 'm4a': case 'wma': $icon = '🎵'; break;
        case 'mp4': case 'avi': case 'mkv': case 'mov': case 'webm': case 'wmv': case 'flv': case '3gp': $icon = '🎬'; break;
        case 'php': case 'html': case 'js': case 'css': $icon = '💻'; break;
    }
    $items[] = compact('name', 'is_dir', 'size', 'mtime', 'ext', 'icon');
}
usort($items, function($a, $b) { return $b['is_dir'] - $a['is_dir'] ?: strcasecmp($a['name'], $b['name']); });

// Breadcrumbs
$crumbs = [['name' => basename($root), 'path' => '']];
if ($dir) {
    $parts = explode('/', $dir);
    $p = '';
    foreach ($parts as $part) { $p .= ($p ? '/' : '') . $part; $crumbs[] = compact('part', 'p'); }
    // rebuild: crumbs is array of ['name','path'] - fix after loop
    $crumbs = [['name' => basename($root), 'path' => '']];
    $p = '';
    foreach ($parts as $part) { $p .= ($p ? '/' : '') . $part; $crumbs[] = ['name' => $part, 'path' => $p]; }
}

// All dirs for move/copy destination
$all_dirs = [];
function list_dirs($base, $prefix = '') {
    $dirs = [];
    foreach (glob($base . '/' . $prefix . '*', GLOB_ONLYDIR) as $d) {
        $name = basename($d);
        $rel = ($prefix ? $prefix : '') . $name;
        $dirs[] = $rel;
        $dirs = array_merge($dirs, list_dirs($base, $rel . '/'));
    }
    return $dirs;
}
$all_dirs = list_dirs($root);

function fmt_size($b) {
    $u = ['B','KB','MB','GB','TB']; $i = 0;
    while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
    return round($b, 1) . ' ' . $u[$i];
}

$disk_total = disk_total_space($root);
$disk_free = disk_free_space($root);
$disk_pct = $disk_total > 0 ? round(($disk_free / $disk_total) * 100) : 0;
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - MiniFileServer</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}
.admin-wrap{max-width:1100px;margin:0 auto;padding:20px}
.topbar{background:#1e293b;border-radius:12px;padding:16px 24px;margin-bottom:20px;border:1px solid #334155;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.topbar h1{font-size:20px;color:#38bdf8;display:flex;align-items:center;gap:8px}
.topbar h1 small{font-size:12px;color:#64748b;font-weight:400}
.topbar-right{display:flex;gap:8px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 16px;border-radius:8px;font-size:13px;text-decoration:none;border:none;cursor:pointer;transition:all .2s;font-weight:500;white-space:nowrap}
.btn-primary{background:#0ea5e9;color:#fff}
.btn-primary:hover{background:#0284c7}
.btn-success{background:#10b981;color:#fff}
.btn-success:hover{background:#059669}
.btn-danger{background:#ef4444;color:#fff}
.btn-danger:hover{background:#dc2626}
.btn-warning{background:#eab308;color:#000}
.btn-warning:hover{background:#ca8a04}
.btn-outline{background:transparent;color:#94a3b8;border:1px solid #334155}
.btn-outline:hover{background:#1e293b;color:#e2e8f0}
.disk-bar{background:#0f172a;border-radius:8px;padding:10px 16px;margin-bottom:16px;border:1px solid #1e293b;display:flex;align-items:center;gap:14px;flex-wrap:wrap;font-size:13px}
.disk-bar .lbl{color:#64748b}
.disk-bar .val{color:#94a3b8}
.disk-bar .bar-wrap{flex:1;min-width:100px}
.disk-bar .bar{height:6px;background:#1e293b;border-radius:3px;overflow:hidden}
.disk-bar .fill{height:100%;border-radius:3px;width:<?=100-$disk_pct?>%;background:<?=($disk_pct<25)?'#10b981':(($disk_pct<50)?'#eab308':'#ef4444')?>}
.toolbar{background:#1e293b;border-radius:12px;padding:12px 20px;margin-bottom:16px;border:1px solid #334155;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.toolbar .sep{width:1px;height:24px;background:#334155}
.breadcrumb{font-size:13px;display:flex;flex-wrap:wrap;gap:4px;align-items:center;margin-bottom:14px}
.breadcrumb a{color:#64748b;text-decoration:none}
.breadcrumb a:hover{color:#38bdf8}
.breadcrumb .s{color:#334155;margin:0 4px}
.breadcrumb .c{color:#e2e8f0}
.file-table{width:100%;border-collapse:collapse}
.file-table th{padding:8px 12px;text-align:left;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #1e293b;font-weight:600}
.file-table td{padding:8px 12px;border-bottom:1px solid #1e293b;font-size:14px;vertical-align:middle}
.file-table tr:hover td{background:#0f172a}
.file-table .nm{color:#e2e8f0;text-decoration:none;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer}
.file-table .nm:hover{color:#38bdf8}
.file-table .sz{color:#64748b;font-size:13px}
.file-table .dt{color:#64748b;font-size:13px}
.file-table .ac{display:flex;gap:4px}
.file-table input[type=checkbox]{width:16px;height:16px;accent-color:#0ea5e9;cursor:pointer}
.empty-state{text-align:center;padding:50px 20px;color:#64748b;font-size:14px}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999;align-items:center;justify-content:center}
.modal.show{display:flex}
.modal-box{background:#1e293b;border-radius:12px;padding:28px;width:90%;max-width:420px;border:1px solid #334155}
.modal-box h3{font-size:17px;margin-bottom:15px;color:#e2e8f0}
.modal-box .fg{margin-bottom:14px}
.modal-box .fg label{display:block;font-size:12px;color:#94a3b8;margin-bottom:4px}
.modal-box .fg input,.modal-box .fg select{width:100%;padding:9px 12px;background:#0f172a;border:1px solid #334155;border-radius:6px;color:#e2e8f0;font-size:14px;outline:none}
.modal-box .fg input:focus,.modal-box .fg select:focus{border-color:#0ea5e9}
.modal-box .ma{display:flex;gap:8px;justify-content:flex-end;margin-top:18px}
.bulk-bar{display:none;background:#0ea5e9;color:#fff;border-radius:8px;padding:10px 16px;margin-bottom:10px;align-items:center;gap:10px;font-size:13px}
.bulk-bar.show{display:flex}
.bulk-bar .cnt{font-weight:600}
.msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px}
.msg.success{background:#064e3b;color:#6ee7b7;border:1px solid #065f46}
.msg.error{background:#450a0a;color:#fca5a5;border:1px solid #7f1d1d}
.mp-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:999;align-items:center;justify-content:center}
.mp-overlay.show{display:flex}
.mp-box{background:#1e293b;border-radius:12px;padding:24px;width:90%;max-width:720px;border:1px solid #334155;max-height:90vh;overflow:auto}
.mp-box .mtop{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px}
.mp-box .mtitle{font-size:15px;color:#e2e8f0;font-weight:600;word-break:break-all;flex:1}
.mp-box .mclose{font-size:28px;color:#64748b;cursor:pointer;background:none;border:none;flex-shrink:0;line-height:1}
.mp-box .mclose:hover{color:#ef4444}
.mp-box video,.mp-box audio{width:100%;border-radius:8px;background:#000}
.mp-box audio{margin:20px 0}
.mp-box .mact{display:flex;gap:8px;margin-top:14px}
@media(max-width:600px){.file-table .dt,.file-table .sz{display:none}.toolbar{flex-direction:column;align-items:stretch}.mp-box{padding:16px}}
</style>
</head>
<body>
<div class="admin-wrap">
<div class="topbar">
<div><h1>📁 MiniFileServer <small>Admin</small></h1></div>
<div class="topbar-right">
<a href="index.php" class="btn btn-outline">Beranda</a>
<a href="?logout=1" class="btn btn-danger">Logout</a>
</div>
</div>

<div class="disk-bar">
<span class="lbl">💾</span>
<span class="val"><?=htmlspecialchars($root)?></span>
<div class="bar-wrap"><div class="bar"><div class="fill"></div></div></div>
<span class="val"><?=fmt_size($disk_free)?> / <?=fmt_size($disk_total)?></span>
</div>

<?php if ($msg): ?><div class="msg <?=$msg_type?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="breadcrumb">
<a href="?dir=">🏠</a>
<?php foreach ($crumbs as $i => $c): ?>
<span class="s">/</span>
<?php if ($i < count($crumbs)-1): ?><a href="?dir=<?=urlencode($c['path'])?>"><?=htmlspecialchars($c['name'])?></a>
<?php else: ?><span class="c"><?=htmlspecialchars($c['name'])?></span>
<?php endif; ?>
<?php endforeach; ?>
</div>

<div class="toolbar">
<button class="btn btn-primary" onclick="showModal('upload')">📤 Upload</button>
<button class="btn btn-success" onclick="showModal('mkdir')">📁 Folder Baru</button>
<div class="sep"></div>
<button class="btn btn-warning" id="moveBtn" onclick="showModal('move')" disabled>✂ Pindah</button>
<button class="btn btn-primary" id="copyBtn" onclick="showModal('copy')" disabled>📋 Salin</button>
<button class="btn btn-danger" id="deleteBtn" onclick="showModal('bulk_delete')" disabled>🗑 Hapus</button>
<div class="sep"></div>
<button class="btn btn-outline" onclick="toggleAll()">☐ Semua</button>
</div>

<div class="bulk-bar" id="bulkBar"><span><span class="cnt" id="selCount">0</span> item dipilih</span></div>

<table class="file-table">
<thead><tr>
<th style="width:30px">☐</th>
<th>Nama</th>
<th style="width:90px" class="sz">Ukuran</th>
<th style="width:140px" class="dt">Diubah</th>
<th style="width:90px">Aksi</th>
</tr></thead>
<tbody>
<?php foreach ($items as $item):
$enc = rawurlencode($item['name']);
$url = 'serve.php?f=' . urlencode(($dir ? $dir . '/' : '') . $item['name']);
$is_media = !$item['is_dir'] && (in_array($item['ext'], $audio_exts) || in_array($item['ext'], $video_exts) || in_array($item['ext'], $image_exts));
?>
<tr>
<td><input type="checkbox" value="<?=htmlspecialchars($item['name'])?>" onchange="updateBulk()"></td>
<td>
<?php if ($item['is_dir']): ?>
<a href="?dir=<?=urlencode($dir ? $dir . '/' . $item['name'] : $item['name'])?>" class="nm"><span><?=$item['icon']?></span><?=htmlspecialchars($item['name'])?></a>
<?php else: ?>
<a <?php if ($is_media): ?>onclick='openPlayer(<?=json_encode($url)?>,<?=json_encode($item['name'])?>,<?=json_encode($item['ext'])?>);return false' href="#"<?php else: ?>href="<?=htmlspecialchars($url)?>"<?php endif; ?> class="nm"><span><?=$item['icon']?></span><?=htmlspecialchars($item['name'])?></a>
<?php endif; ?>
</td>
<td class="sz"><?=$item['is_dir'] ? '—' : fmt_size($item['size'])?></td>
<td class="dt"><?=date('d M Y H:i', $item['mtime'])?></td>
<td>
<div class="ac">
<button class="btn btn-outline" style="padding:4px 10px;font-size:12px" onclick="showModal('rename','<?=htmlspecialchars($item['name'],ENT_QUOTES)?>')">✏</button>
<a href="?dir=<?=urlencode($dir)?>&delete=<?=$enc?>" class="btn btn-danger" style="padding:4px 10px;font-size:12px" onclick="return confirm('Hapus <?=htmlspecialchars($item['name'])?>?')">🗑</a>
</div>
</td>
</tr>
<?php endforeach; ?>
<?php if (empty($items)): ?>
<tr><td colspan="5"><div class="empty-state">📂 Folder kosong</div></td></tr>
<?php endif; ?>
</tbody>
</table>

<!-- Modal Upload -->
<div class="modal" id="modal-upload">
<form class="modal-box" method="post" enctype="multipart/form-data">
<h3>📤 Upload File</h3>
<div class="fg"><label>Pilih file</label><input type="file" name="file[]" multiple required></div>
<div class="ma"><button type="button" class="btn btn-outline" onclick="hideModal('upload')">Batal</button><button type="submit" class="btn btn-primary">Upload</button></div>
</form>
</div>

<!-- Modal New Folder -->
<div class="modal" id="modal-mkdir">
<form class="modal-box" method="post">
<h3>📁 Buat Folder Baru</h3>
<div class="fg"><label>Nama folder</label><input type="text" name="name" required autofocus></div>
<div class="ma"><button type="button" class="btn btn-outline" onclick="hideModal('mkdir')">Batal</button><button type="submit" name="mkdir" class="btn btn-success">Buat</button></div>
</form>
</div>

<!-- Modal Rename -->
<div class="modal" id="modal-rename">
<form class="modal-box" method="post">
<h3>✏ Rename</h3>
<input type="hidden" name="old" id="rename-old">
<div class="fg"><label>Nama baru</label><input type="text" name="new" id="rename-new" required autofocus></div>
<div class="ma"><button type="button" class="btn btn-outline" onclick="hideModal('rename')">Batal</button><button type="submit" name="rename" class="btn btn-primary">Simpan</button></div>
</form>
</div>

<!-- Modal Move -->
<div class="modal" id="modal-move">
<form class="modal-box" method="post" id="moveForm">
<h3>✂ Pindahkan ke folder</h3>
<input type="hidden" name="items">
<div class="fg"><label>Tujuan</label>
<select name="dest" size="6" style="width:100%">
<option value="">/ (root)</option>
<?php foreach ($all_dirs as $d): if ($d === $dir) continue; ?>
<option value="<?=htmlspecialchars($d)?>">/<?=htmlspecialchars($d)?></option>
<?php endforeach; ?>
</select>
</div>
<div class="ma"><button type="button" class="btn btn-outline" onclick="hideModal('move')">Batal</button><button type="submit" name="move" class="btn btn-warning">Pindah</button></div>
</form>
</div>

<!-- Modal Copy -->
<div class="modal" id="modal-copy">
<form class="modal-box" method="post" id="copyForm">
<h3>📋 Salin ke folder</h3>
<input type="hidden" name="items">
<div class="fg"><label>Tujuan</label>
<select name="dest" size="6" style="width:100%">
<option value="">/ (root)</option>
<?php foreach ($all_dirs as $d): ?>
<option value="<?=htmlspecialchars($d)?>">/<?=htmlspecialchars($d)?></option>
<?php endforeach; ?>
</select>
</div>
<div class="ma"><button type="button" class="btn btn-outline" onclick="hideModal('copy')">Batal</button><button type="submit" name="copy" class="btn btn-primary">Salin</button></div>
</form>
</div>

<!-- Modal Bulk Delete -->
<div class="modal" id="modal-bulk_delete">
<form class="modal-box" method="post" id="deleteForm">
<h3>🗑 Hapus</h3>
<input type="hidden" name="items">
<p style="color:#94a3b8;font-size:14px;margin-bottom:14px">Yakin hapus <strong><span class="cnt">0</span> item</strong>?</p>
<div class="ma"><button type="button" class="btn btn-outline" onclick="hideModal('bulk_delete')">Batal</button><button type="submit" name="bulk_delete" class="btn btn-danger">Hapus</button></div>
</form>
</div>

<!-- Media Player Modal -->
<div class="mp-overlay" id="playerModal" onclick="if(event.target===this)closePlayer()">
<div class="mp-box">
<div class="mtop">
<span class="mtitle" id="playerTitle">-</span>
<button class="mclose" onclick="closePlayer()">&times;</button>
</div>
<div id="playerContainer"></div>
<div class="mact">
<a id="playerDownload" class="btn btn-primary" download>📥 Download</a>
</div>
</div>
</div>

</div>

<script>
var audioExts = <?=json_encode($audio_exts)?>;
var videoExts = <?=json_encode($video_exts)?>;
var imageExts = <?=json_encode($image_exts)?>;
var playerModal = document.getElementById('playerModal');
var playerTitle = document.getElementById('playerTitle');
var playerContainer = document.getElementById('playerContainer');
var playerDownload = document.getElementById('playerDownload');

function openPlayer(url, name, ext) {
playerContainer.innerHTML = '';
playerTitle.textContent = name;
playerDownload.href = url + (url.indexOf('?') > -1 ? '&' : '?') + 'dl=1';
var el;
if (audioExts.indexOf(ext) !== -1) { el = document.createElement('audio'); el.controls = true; el.autoplay = true; }
else if (videoExts.indexOf(ext) !== -1) { el = document.createElement('video'); el.controls = true; el.autoplay = true; el.style.maxHeight = '60vh'; }
else { el = document.createElement('img'); el.style.maxWidth = '100%'; el.style.maxHeight = '70vh'; el.style.objectFit = 'contain'; el.style.borderRadius = '8px'; }
el.src = url;
playerContainer.appendChild(el);
playerModal.classList.add('show');
}

function closePlayer() {
playerContainer.innerHTML = '';
playerModal.classList.remove('show');
}

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePlayer(); });

function showModal(id, val) {
if (id === 'rename' && val) {
document.getElementById('rename-old').value = val;
document.getElementById('rename-new').value = val;
}
var checked = document.querySelectorAll('input[type=checkbox]:checked');
var names = Array.from(checked).map(function(c) { return c.value; });
if (['move','copy','bulk_delete'].indexOf(id) !== -1) {
document.querySelector('#' + id + 'Form input[name="items"]').value = JSON.stringify(names);
document.querySelector('#modal-' + id + ' .cnt').textContent = names.length;
}
document.getElementById('modal-' + id).classList.add('show');
}

function hideModal(id) {
document.getElementById('modal-' + id).classList.remove('show');
}

function toggleAll() {
var cbs = document.querySelectorAll('input[type=checkbox]');
var all = Array.from(cbs).every(function(c) { return c.checked; });
cbs.forEach(function(c) { c.checked = !all; });
updateBulk();
}

function updateBulk() {
var checked = document.querySelectorAll('input[type=checkbox]:checked');
document.getElementById('selCount').textContent = checked.length;
document.getElementById('bulkBar').classList.toggle('show', checked.length > 0);
document.getElementById('moveBtn').disabled = checked.length === 0;
document.getElementById('copyBtn').disabled = checked.length === 0;
document.getElementById('deleteBtn').disabled = checked.length === 0;
}
</script>
</body>
</html>
