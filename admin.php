<?php
session_start();
$password = 'moch1234';
$config_path = __DIR__ . '/minifs.json';
$config = ['storage_path' => __DIR__ . '/uploads'];
if (is_file($config_path)) $config = json_decode(file_get_contents($config_path), true) ?: $config;
$root = rtrim($config['storage_path'], '/');
if (!is_dir($root)) mkdir($root, 0755, true);

// Auth
$is_auth = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
$msg = ''; $msg_type = '';

if (isset($_POST['login'])) {
    if ($password === '' || $_POST['pass'] === $password) {
        $_SESSION['auth'] = true;
        $is_auth = true;
    } else { $msg = 'Password salah!'; $msg_type = 'error'; }
}

if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if (!$is_auth && $password !== '') { include 'login-form.php'; exit; }

$dir = isset($_GET['dir']) ? $_GET['dir'] : '';
$dir = str_replace('..', '', $dir);
$cur_dir = $root . ($dir ? '/' . $dir : '');
$rel_dir = $dir ? $dir . '/' : '';

if (!is_dir($cur_dir)) mkdir($cur_dir, 0755, true);

// --- Actions ---

// Upload
if (isset($_FILES['file'])) {
    $files_arr = $_FILES['file'];
    $count = 0;
    if (is_array($files_arr['name'])) {
        foreach ($files_arr['name'] as $i => $name) {
            if ($files_arr['error'][$i] === UPLOAD_ERR_OK) {
                $fname = preg_replace('/[^a-zA-Z0-9._\-\s()]/', '_', basename($name));
                if (move_uploaded_file($files_arr['tmp_name'][$i], $cur_dir . '/' . $fname)) $count++;
            }
        }
    } else {
        if ($files_arr['error'] === UPLOAD_ERR_OK) {
            $fname = preg_replace('/[^a-zA-Z0-9._\-\s()]/', '_', basename($files_arr['name']));
            if (move_uploaded_file($files_arr['tmp_name'], $cur_dir . '/' . $fname)) $count++;
        }
    }
    if ($count) { $msg = "$count file berhasil diupload!"; $msg_type = 'success'; }
    else { $msg = 'Gagal upload!'; $msg_type = 'error'; }
}

// Create folder
if (isset($_POST['mkdir'])) {
    $name = trim(basename($_POST['name']));
    if ($name && !is_dir($cur_dir . '/' . $name)) {
        mkdir($cur_dir . '/' . $name, 0755);
        $msg = "Folder '$name' berhasil dibuat!"; $msg_type = 'success';
    } else { $msg = 'Nama folder tidak valid atau sudah ada!'; $msg_type = 'error'; }
}

// Rename
if (isset($_POST['rename'])) {
    $old = basename($_POST['old']);
    $new = basename(trim($_POST['new']));
    $old_path = $cur_dir . '/' . $old;
    $new_path = $cur_dir . '/' . $new;
    if ($old && $new && file_exists($old_path) && !file_exists($new_path)) {
        rename($old_path, $new_path);
        $msg = "'$old' berhasil diubah jadi '$new'!"; $msg_type = 'success';
    } else { $msg = 'Gagal rename!'; $msg_type = 'error'; }
}

// Delete (single)
if (isset($_GET['delete'])) {
    $name = basename($_GET['delete']);
    $path = $cur_dir . '/' . $name;
    if (is_file($path)) { unlink($path); $msg = "'$name' berhasil dihapus!"; $msg_type = 'success'; }
    elseif (is_dir($path)) { rmdir_recursive($path); $msg = "'$name' berhasil dihapus!"; $msg_type = 'success'; }
    else { $msg = 'Gagal hapus!'; $msg_type = 'error'; }
}

// Bulk delete
if (isset($_POST['bulk_delete'])) {
    $items = $_POST['items'] ?? [];
    $c = 0;
    foreach ($items as $item) {
        $path = $cur_dir . '/' . basename($item);
        if (is_file($path)) { unlink($path); $c++; }
        elseif (is_dir($path)) { rmdir_recursive($path); $c++; }
    }
    if ($c) { $msg = "$c item berhasil dihapus!"; $msg_type = 'success'; }
}

// Move
if (isset($_POST['move']) && isset($_POST['dest'])) {
    $dest_base = str_replace('..', '', trim($_POST['dest'], '/'));
    $dest_path = $root . '/' . $dest_base;
    if (!is_dir($dest_path)) mkdir($dest_path, 0755, true);
    $items = $_POST['items'] ?? []; $c = 0;
    foreach ($items as $item) {
        $src = $cur_dir . '/' . basename($item);
        $dst = $dest_path . '/' . basename($item);
        if (file_exists($src) && !file_exists($dst)) { rename($src, $dst); $c++; }
    }
    if ($c) { $msg = "$c item berhasil dipindahkan!"; $msg_type = 'success'; }
    else { $msg = 'Tidak ada yang dipindahkan!'; $msg_type = 'error'; }
}

// Copy
if (isset($_POST['copy']) && isset($_POST['dest'])) {
    $dest_base = str_replace('..', '', trim($_POST['dest'], '/'));
    $dest_path = $root . '/' . $dest_base;
    if (!is_dir($dest_path)) mkdir($dest_path, 0755, true);
    $items = $_POST['items'] ?? []; $c = 0;
    foreach ($items as $item) {
        $src = $cur_dir . '/' . basename($item);
        $dst = $dest_path . '/' . basename($item);
        if (file_exists($src) && !file_exists($dst)) { copy($src, $dst); $c++; }
    }
    if ($c) { $msg = "$c item berhasil disalin!"; $msg_type = 'success'; }
    else { $msg = 'Tidak ada yang disalin!'; $msg_type = 'error'; }
}

function rmdir_recursive($path) {
    foreach (glob($path . '/*') as $f) {
        is_dir($f) ? rmdir_recursive($f) : unlink($f);
    }
    rmdir($path);
}

// Get items
$items = [];
foreach (glob($cur_dir . '/*') as $f) {
    $name = basename($f);
    $is_dir = is_dir($f);
    $size = $is_dir ? 0 : filesize($f);
    $mtime = filemtime($f);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $icon = '📄';
    if ($is_dir) $icon = '📁';
    else { switch($ext) {
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'bmp': case 'webp': case 'svg': $icon = '🖼️'; break;
        case 'pdf': $icon = '📄'; break;
        case 'doc': case 'docx': $icon = '📝'; break;
        case 'xls': case 'xlsx': case 'csv': $icon = '📊'; break;
        case 'zip': case 'rar': case '7z': case 'tar': case 'gz': $icon = '📦'; break;
        case 'mp3': case 'wav': case 'ogg': case 'flac': $icon = '🎵'; break;
        case 'mp4': case 'avi': case 'mkv': case 'mov': case 'webm': $icon = '🎬'; break;
        case 'php': case 'html': case 'js': case 'css': $icon = '💻'; break;
    } }
    $items[] = ['name' => $name, 'path' => $f, 'is_dir' => $is_dir, 'size' => $size, 'mtime' => $mtime, 'ext' => $ext, 'icon' => $icon];
}
usort($items, function($a, $b) { return $b['is_dir'] - $a['is_dir'] ?: strcasecmp($a['name'], $b['name']); });

// Breadcrumbs
$crumbs = [['name' => basename($root), 'path' => '']];
if ($dir) {
    $parts = explode('/', $dir);
    $p = '';
    foreach ($parts as $part) {
        $p .= ($p ? '/' : '') . $part;
        $crumbs[] = ['name' => $part, 'path' => $p];
    }
}

// All dirs for move/copy destination
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

.disk-bar{background:#0f172a;border-radius:8px;padding:10px 16px;margin-bottom:16px;border:1px solid #1e293b;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.disk-bar .label{font-size:12px;color:#64748b}
.disk-bar .size{font-size:13px;color:#94a3b8}
.disk-bar .bar-wrap{flex:1;min-width:100px}
.disk-bar .bar{height:6px;background:#1e293b;border-radius:3px;overflow:hidden}
.disk-bar .bar-fill{height:100%;border-radius:3px;background:<?=($disk_pct<25)?'#10b981':(($disk_pct<50)?'#eab308':'#ef4444')?>;width:<?=100-$disk_pct?>%}

.toolbar{background:#1e293b;border-radius:12px;padding:12px 20px;margin-bottom:16px;border:1px solid #334155;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.toolbar .sep{width:1px;height:24px;background:#334155}

.breadcrumb{display:flex;flex-wrap:wrap;gap:4px;align-items:center;font-size:13px;margin-bottom:14px}
.breadcrumb a{color:#64748b;text-decoration:none}
.breadcrumb a:hover{color:#38bdf8}
.breadcrumb .sep{color:#334155;margin:0 4px}
.breadcrumb .current{color:#e2e8f0}

.file-table{width:100%;border-collapse:collapse}
.file-table th{padding:8px 12px;text-align:left;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #1e293b;font-weight:600}
.file-table td{padding:8px 12px;border-bottom:1px solid #1e293b;font-size:14px;vertical-align:middle}
.file-table tr:hover td{background:#0f172a}
.file-table .icon{font-size:20px;margin-right:10px}
.file-table .name{color:#e2e8f0;text-decoration:none;font-weight:500;display:flex;align-items:center}
.file-table .name:hover{color:#38bdf8}
.file-table .size{color:#64748b;font-size:13px}
.file-table .date{color:#64748b;font-size:13px}
.file-table .actions{display:flex;gap:4px}
.file-table input[type=checkbox]{width:16px;height:16px;accent-color:#0ea5e9;cursor:pointer}

.empty-state{text-align:center;padding:50px 20px;color:#64748b;font-size:14px}

.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999;align-items:center;justify-content:center}
.modal.show{display:flex}
.modal-box{background:#1e293b;border-radius:12px;padding:28px;width:90%;max-width:420px;border:1px solid #334155}
.modal-box h3{font-size:17px;margin-bottom:15px;color:#e2e8f0}
.modal-box .form-group{margin-bottom:14px}
.modal-box .form-group label{display:block;font-size:12px;color:#94a3b8;margin-bottom:4px}
.modal-box .form-group input,.modal-box .form-group select{width:100%;padding:9px 12px;background:#0f172a;border:1px solid #334155;border-radius:6px;color:#e2e8f0;font-size:14px;outline:none}
.modal-box .form-group input:focus,.modal-box .form-group select:focus{border-color:#0ea5e9}
.modal-box .modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:18px}

.bulk-bar{display:none;background:#0ea5e9;color:#fff;border-radius:8px;padding:10px 16px;margin-bottom:10px;align-items:center;gap:10px;font-size:13px}
.bulk-bar.show{display:flex}
.bulk-bar .count{font-weight:600}
.bulk-bar .btn{background:rgba(255,255,255,.2);color:#fff}
.bulk-bar .btn:hover{background:rgba(255,255,255,.3)}

.msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px}
.msg.success{background:#064e3b;color:#6ee7b7;border:1px solid #065f46}
.msg.error{background:#450a0a;color:#fca5a5;border:1px solid #7f1d1d}

@media(max-width:600px){
.file-table .date,.file-table .size{display:none}
.toolbar{flex-direction:column;align-items:stretch}
}
</style>
</head>
<body>
<div class="admin-wrap">
<div class="topbar">
<div><h1>📁 MiniFileServer <small>Admin Panel</small></h1></div>
<div class="topbar-right">
<a href="index.php" class="btn btn-outline">Halaman Publik</a>
<a href="?logout=1" class="btn btn-danger">Logout</a>
</div>
</div>

<div class="disk-bar">
<span class="label">💾 Storage</span>
<span class="size"><?=htmlspecialchars($root)?></span>
<div class="bar-wrap"><div class="bar"><div class="bar-fill"></div></div></div>
<span class="size"><?=fmt_size($disk_free)?> tersisa dari <?=fmt_size($disk_total)?></span>
</div>

<?php if ($msg): ?><div class="msg <?=$msg_type?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="breadcrumb">
<a href="?dir=">🏠</a>
<?php foreach ($crumbs as $i => $c): ?>
<span class="sep">/</span>
<?php if ($i < count($crumbs)-1): ?><a href="?dir=<?=urlencode($c['path'])?>"><?=htmlspecialchars($c['name'])?></a>
<?php else: ?><span class="current"><?=htmlspecialchars($c['name'])?></span>
<?php endif; ?>
<?php endforeach; ?>
</div>

<div class="toolbar" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
<button class="btn btn-primary" onclick="showModal('upload')">📤 Upload</button>
<button class="btn btn-success" onclick="showModal('mkdir')">📁 Folder Baru</button>
<div class="sep"></div>
<button class="btn btn-warning" id="moveBtn" onclick="showModal('move')" disabled>✂ Pindahkan</button>
<button class="btn btn-primary" id="copyBtn" onclick="showModal('copy')" disabled>📋 Salin</button>
<button class="btn btn-danger" id="deleteBtn" onclick="showModal('bulk_delete')" disabled>🗑 Hapus</button>
<div class="sep"></div>
<button class="btn btn-outline" id="selectAll" onclick="toggleAll()">☐ Semua</button>
</div>

<div class="bulk-bar" id="bulkBar">
<span><span class="count" id="selCount">0</span> item dipilih</span>
</div>

<form method="post" id="bulkForm">
<input type="hidden" name="items" id="bulkItems">

<table class="file-table">
<thead><tr>
<th style="width:30px">☐</th>
<th>Nama</th>
<th style="width:90px" class="size-col">Ukuran</th>
<th style="width:140px" class="date-col">Terakhir Diubah</th>
<th style="width:120px">Aksi</th>
</tr></thead>
<tbody>
<?php foreach ($items as $item):
$enc = rawurlencode($item['name']);
$dl_link = 'uploads/' . ($dir ? $dir . '/' : '') . $enc;
$is_public = substr(realpath($item['path']) . '/', 0, strlen(realpath($root) . '/')) !== realpath($root) . '/';
?>
<tr>
<td><input type="checkbox" value="<?=htmlspecialchars($item['name'])?>" onchange="updateBulk()"></td>
<td>
<?php if ($item['is_dir']): ?>
<a href="?dir=<?=urlencode($dir ? $dir . '/' . $item['name'] : $item['name'])?>" class="name"><span class="icon"><?=$item['icon']?></span><?=htmlspecialchars($item['name'])?></a>
<?php else: ?>
<a href="<?=htmlspecialchars($dl_link)?>" class="name" download><span class="icon"><?=$item['icon']?></span><?=htmlspecialchars($item['name'])?></a>
<?php endif; ?>
</td>
<td class="size-col"><span class="size"><?=$item['is_dir'] ? '—' : fmt_size($item['size'])?></span></td>
<td class="date-col"><span class="date"><?=date('d M Y H:i', $item['mtime'])?></span></td>
<td>
<div class="actions">
<button class="btn btn-outline" style="padding:4px 10px;font-size:12px" onclick="showModal('rename','<?=htmlspecialchars($item['name'],ENT_QUOTES)?>')">✏</button>
<a href="?dir=<?=urlencode($dir)?>&delete=<?=$enc?>" class="btn btn-danger" style="padding:4px 10px;font-size:12px" onclick="return confirm('Hapus <?=htmlspecialchars($item['name'])?>?')">🗑</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if (empty($items)): ?>
<div class="empty-state">📂 Folder ini kosong</div>
<?php endif; ?>
</form>

<!-- Modal Upload -->
<div class="modal" id="modal-upload">
<form class="modal-box" method="post" enctype="multipart/form-data">
<h3>📤 Upload File</h3>
<div class="form-group"><label>Pilih file (bisa banyak)</label><input type="file" name="file[]" multiple required></div>
<div class="modal-actions"><button type="button" class="btn btn-outline" onclick="hideModal('upload')">Batal</button><button type="submit" class="btn btn-primary">Upload</button></div>
</form>
</div>

<!-- Modal New Folder -->
<div class="modal" id="modal-mkdir">
<form class="modal-box" method="post">
<h3>📁 Buat Folder Baru</h3>
<div class="form-group"><label>Nama folder</label><input type="text" name="name" required autofocus></div>
<div class="modal-actions"><button type="button" class="btn btn-outline" onclick="hideModal('mkdir')">Batal</button><button type="submit" name="mkdir" class="btn btn-success">Buat</button></div>
</form>
</div>

<!-- Modal Rename -->
<div class="modal" id="modal-rename">
<form class="modal-box" method="post">
<h3>✏ Rename</h3>
<input type="hidden" name="old" id="rename-old">
<div class="form-group"><label>Nama baru</label><input type="text" name="new" id="rename-new" required autofocus></div>
<div class="modal-actions"><button type="button" class="btn btn-outline" onclick="hideModal('rename')">Batal</button><button type="submit" name="rename" class="btn btn-primary">Simpan</button></div>
</form>
</div>

<!-- Modal Move -->
<div class="modal" id="modal-move">
<form class="modal-box" method="post" id="moveForm">
<h3>✂ Pindahkan ke folder</h3>
<input type="hidden" name="items" class="bulk-hidden">
<div class="form-group"><label>Tujuan</label>
<?php if (empty($all_dirs)): ?><p style="color:#64748b;font-size:13px">Tidak ada folder tujuan.</p>
<?php else: ?>
<select name="dest" size="8" style="width:100%;background:#0f172a;border:1px solid #334155;border-radius:6px;color:#e2e8f0;padding:6px">
<option value="">/ (root)</option>
<?php foreach ($all_dirs as $d): if ($d === $dir) continue; ?>
<option value="<?=htmlspecialchars($d)?>">/<?=htmlspecialchars($d)?></option>
<?php endforeach; ?>
</select>
<?php endif; ?>
</div>
<div class="modal-actions"><button type="button" class="btn btn-outline" onclick="hideModal('move')">Batal</button><button type="submit" name="move" class="btn btn-warning">Pindahkan</button></div>
</form>
</div>

<!-- Modal Copy -->
<div class="modal" id="modal-copy">
<form class="modal-box" method="post" id="copyForm">
<h3>📋 Salin ke folder</h3>
<input type="hidden" name="items" class="bulk-hidden">
<div class="form-group"><label>Tujuan</label>
<?php if (empty($all_dirs)): ?><p style="color:#64748b;font-size:13px">Tidak ada folder tujuan.</p>
<?php else: ?>
<select name="dest" size="8" style="width:100%;background:#0f172a;border:1px solid #334155;border-radius:6px;color:#e2e8f0;padding:6px">
<option value="">/ (root)</option>
<?php foreach ($all_dirs as $d): ?>
<option value="<?=htmlspecialchars($d)?>">/<?=htmlspecialchars($d)?></option>
<?php endforeach; ?>
</select>
<?php endif; ?>
</div>
<div class="modal-actions"><button type="button" class="btn btn-outline" onclick="hideModal('copy')">Batal</button><button type="submit" name="copy" class="btn btn-primary">Salin</button></div>
</form>
</div>

<!-- Modal Bulk Delete -->
<div class="modal" id="modal-bulk_delete">
<form class="modal-box" method="post" id="deleteForm">
<h3>🗑 Hapus item</h3>
<input type="hidden" name="items" class="bulk-hidden">
<p style="color:#94a3b8;font-size:14px;margin-bottom:14px">Yakin ingin menghapus <strong><span class="count">0</span> item</strong>? Tidak bisa dikembalikan.</p>
<div class="modal-actions"><button type="button" class="btn btn-outline" onclick="hideModal('bulk_delete')">Batal</button><button type="submit" name="bulk_delete" class="btn btn-danger">Hapus Semua</button></div>
</form>
</div>
</div>

<script>
function showModal(id, val) {
if (id === 'rename' && val) {
document.getElementById('rename-old').value = val;
document.getElementById('rename-new').value = val;
}
let bulk = document.querySelectorAll('input[type=checkbox]:checked');
let names = Array.from(bulk).map(c => c.value);
if (['move','copy','bulk_delete'].includes(id)) {
document.querySelectorAll('#'+id+'Form .bulk-hidden, #'+id+'Form .bulk-hidden').forEach(el => {
if (el.name === 'items') el.value = JSON.stringify(names);
});
document.querySelector('#modal-'+id+' .count').textContent = names.length;
}
document.getElementById('modal-'+id).classList.add('show');
}
function hideModal(id) {
document.getElementById('modal-'+id).classList.remove('show');
}
function toggleAll() {
let checked = document.querySelectorAll('input[type=checkbox]');
let allChecked = Array.from(checked).every(c => c.checked);
checked.forEach(c => c.checked = !allChecked);
updateBulk();
}
function updateBulk() {
let checked = document.querySelectorAll('input[type=checkbox]:checked');
document.getElementById('selCount').textContent = checked.length;
document.getElementById('bulkBar').classList.toggle('show', checked.length > 0);
document.getElementById('moveBtn').disabled = checked.length === 0;
document.getElementById('copyBtn').disabled = checked.length === 0;
document.getElementById('deleteBtn').disabled = checked.length === 0;
}
</script>
</body>
</html>
