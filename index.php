<?php
// MiniFileServer - Single file PHP file hosting
// Set password (kosongkan untuk tanpa password)
$password = 'admin123';

// Konfigurasi
$upload_dir = __DIR__ . '/uploads';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$is_auth = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
$msg = ''; $msg_type = '';

session_start();

// Login
if (isset($_POST['login'])) {
    if ($password === '' || $_POST['pass'] === $password) {
        $_SESSION['auth'] = true;
        $is_auth = true;
    } else { $msg = 'Password salah!'; $msg_type = 'error'; }
}

// Logout
if (isset($_GET['logout'])) { session_destroy(); header('Location: ?'); exit; }

// Upload
if ($is_auth && isset($_FILES['file'])) {
    $f = $_FILES['file'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        $name = preg_replace('/[^a-zA-Z0-9._\-\s()]/', '_', basename($f['name']));
        if (move_uploaded_file($f['tmp_name'], $upload_dir . '/' . $name)) {
            $msg = 'File berhasil diupload!'; $msg_type = 'success';
        } else { $msg = 'Gagal upload!'; $msg_type = 'error'; }
    }
}

// Delete
if ($is_auth && isset($_GET['delete'])) {
    $f = basename($_GET['delete']);
    $path = $upload_dir . '/' . $f;
    if (is_file($path) && unlink($path)) {
        $msg = 'File berhasil dihapus!'; $msg_type = 'success';
    } else { $msg = 'Gagal hapus file!'; $msg_type = 'error'; }
}

// Get files
$files = glob($upload_dir . '/*');
$files = array_filter($files, 'is_file');
usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });

function fmt_size($b) {
    $u = ['B','KB','MB','GB']; $i = 0;
    while ($b >= 1024 && $i < 3) { $b /= 1024; $i++; }
    return round($b, 1) . ' ' . $u[$i];
}

function file_icon($ext) {
    return match($ext) {
        'jpg','jpeg','png','gif','bmp','webp','svg' => '🖼️',
        'pdf' => '📄', 'doc','docx' => '📝', 'xls','xlsx','csv' => '📊',
        'zip','rar','7z','tar','gz' => '📦', 'mp3','wav','ogg','flac' => '🎵',
        'mp4','avi','mkv','mov','webm' => '🎬', 'php','html','js','css' => '💻',
        default => '📁'
    };
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MiniFileServer</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}
.container{max-width:800px;margin:0 auto;padding:20px}
.header{background:#1e293b;padding:25px 30px;border-radius:12px;margin-bottom:20px;border:1px solid #334155;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.header h1{font-size:22px;color:#38bdf8;display:flex;align-items:center;gap:8px}
.header h1 small{font-size:13px;color:#64748b;font-weight:400}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 18px;border-radius:8px;font-size:14px;text-decoration:none;border:none;cursor:pointer;transition:all .2s;font-weight:500}
.btn-primary{background:#0ea5e9;color:#fff}
.btn-primary:hover{background:#0284c7}
.btn-danger{background:#ef4444;color:#fff}
.btn-danger:hover{background:#dc2626}
.btn-outline{background:transparent;color:#94a3b8;border:1px solid #334155}
.btn-outline:hover{background:#1e293b;color:#e2e8f0}
.btn-sm{padding:5px 12px;font-size:12px}
.card{background:#1e293b;border-radius:12px;padding:25px 30px;margin-bottom:20px;border:1px solid #334155}
.card h2{font-size:17px;color:#94a3b8;margin-bottom:15px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.upload-area{border:2px dashed #334155;border-radius:10px;padding:40px 20px;text-align:center;transition:all .3s;cursor:pointer}
.upload-area:hover,.upload-area.dragover{border-color:#0ea5e9;background:#0f172a}
.upload-area p{color:#64748b;font-size:14px;margin-top:8px}
.upload-area .icon{font-size:40px;color:#0ea5e9}
.upload-area input[type=file]{display:none}
.file-item{display:flex;align-items:center;padding:12px 0;border-bottom:1px solid #1e293b;gap:12px}
.file-item:last-child{border-bottom:none}
.file-icon{font-size:22px;flex-shrink:0}
.file-info{flex:1;min-width:0}
.file-name{display:block;font-weight:500;color:#e2e8f0;word-break:break-all;font-size:14px}
.file-size{font-size:12px;color:#64748b}
.file-actions{display:flex;gap:6px;flex-shrink:0}
.empty{text-align:center;padding:40px 0;color:#64748b;font-size:14px}
.login-box{max-width:380px;margin:60px auto;background:#1e293b;padding:35px;border-radius:12px;border:1px solid #334155}
.login-box h1{text-align:center;color:#38bdf8;font-size:22px;margin-bottom:5px}
.login-box p{text-align:center;color:#64748b;font-size:13px;margin-bottom:20px}
.form-group{margin-bottom:14px}
.form-group label{display:block;margin-bottom:5px;font-size:13px;color:#94a3b8;font-weight:500}
.form-group input{width:100%;padding:10px 14px;background:#0f172a;border:1px solid #334155;border-radius:8px;color:#e2e8f0;font-size:15px;outline:none;transition:border .2s}
.form-group input:focus{border-color:#0ea5e9}
.btn-block{width:100%;justify-content:center;padding:11px}
.msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:15px}
.msg.success{background:#064e3b;color:#6ee7b7;border:1px solid #065f46}
.msg.error{background:#450a0a;color:#fca5a5;border:1px solid #7f1d1d}
.login-icon{text-align:center;font-size:50px;margin-bottom:10px}
@media(max-width:600px){.header{flex-direction:column;text-align:center}.file-actions{flex-direction:column}}
</style>
</head>
<body>
<div class="container">
<?php if (!$is_auth && $password !== ''): ?>
<div class="login-box">
<div class="login-icon">🔒</div>
<h1>MiniFileServer</h1>
<p>Masukkan password untuk mengakses</p>
<?php if ($msg): ?><div class="msg <?=$msg_type?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<form method="post">
<div class="form-group"><label>Password</label><input type="password" name="pass" required autofocus></div>
<button type="submit" name="login" class="btn btn-primary btn-block">Login</button>
</form>
</div>
<?php else: ?>
<div class="header">
<div><h1>📁 MiniFileServer <small><?=count($files)?> file</small></h1></div>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<?php if ($is_auth): ?>
<a href="?logout=1" class="btn btn-outline btn-sm">Logout</a>
<?php endif; ?>
</div>
</div>

<?php if ($msg): ?>
<div class="msg <?=$msg_type?>"><?=htmlspecialchars($msg)?></div>
<?php endif; ?>

<?php if ($is_auth): ?>
<div class="card">
<h2>Upload File</h2>
<form method="post" enctype="multipart/form-data" id="upload-form">
<div class="upload-area" id="drop-area">
<div class="icon">☁️</div>
<p><strong>Klik atau tarik file ke sini</strong></p>
<p style="font-size:12px;margin-top:4px">Maksimal 2GB per file</p>
<input type="file" name="file" id="file-input" required>
</div>
</form>
</div>
<?php endif; ?>

<div class="card">
<h2>Daftar File</h2>
<?php if (empty($files)): ?>
<div class="empty">Belum ada file.</div>
<?php else: ?>
<?php foreach ($files as $f): $name = basename($f); $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION)); ?>
<div class="file-item">
<span class="file-icon"><?=file_icon($ext)?></span>
<div class="file-info">
<span class="file-name"><?=htmlspecialchars($name)?></span>
<span class="file-size"><?=fmt_size(filesize($f))?></span>
</div>
<div class="file-actions">
<a href="uploads/<?=rawurlencode($name)?>" class="btn btn-primary btn-sm" download>Download</a>
<?php if ($is_auth): ?>
<a href="?delete=<?=rawurlencode($name)?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus file ini?')">Hapus</a>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php endif; ?>
</div>

<script>
const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('file-input');
if (dropArea && fileInput) {
['dragenter','dragover','dragleave','drop'].forEach(e => { dropArea.addEventListener(e, e.preventDefault()); });
['dragenter','dragover'].forEach(e => { dropArea.addEventListener(e, () => dropArea.classList.add('dragover')); });
['dragleave','drop'].forEach(e => { dropArea.addEventListener(e, () => dropArea.classList.remove('dragover')); });
dropArea.addEventListener('click', () => fileInput.click());
dropArea.addEventListener('drop', (e) => { fileInput.files = e.dataTransfer.files; document.getElementById('upload-form').submit(); });
fileInput.addEventListener('change', () => { if (fileInput.files.length) document.getElementById('upload-form').submit(); });
}
</script>
</body>
</html>
