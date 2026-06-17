<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $dest = 'uploads/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $message = 'File berhasil diupload!';
        } else {
            $error = 'Gagal mengupload file.';
        }
    } else {
        $error = 'Terjadi kesalahan saat upload.';
    }
}

$files = glob('uploads/*');
$files = array_filter($files, function($f) { return $f !== 'uploads/.gitkeep'; });
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - FileShare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dashboard Admin</h1>
            <p>Selamat datang, Admin</p>
            <a href="index.php" class="btn">Halaman Publik</a>
            <a href="logout.php" class="btn btn-logout">Logout</a>
        </div>

        <div class="upload-section">
            <h2>Upload File Baru</h2>
            <?php if ($message): ?>
                <p class="success"><?= $message ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="form-group">
                    <label for="file">Pilih File</label>
                    <input type="file" id="file" name="file" required>
                </div>
                <button type="submit" class="btn btn-submit">Upload</button>
            </form>
        </div>

        <div class="file-list">
            <h2>File Terupload</h2>
            <?php if (empty($files)): ?>
                <p class="empty">Belum ada file.</p>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <?php
                        $filename = basename($file);
                        $size = filesize($file);
                        $unit = ['B', 'KB', 'MB', 'GB'];
                        $i = 0;
                        while ($size >= 1024 && $i < count($unit)-1) {
                            $size /= 1024;
                            $i++;
                        }
                        $size = round($size, 1) . ' ' . $unit[$i];
                    ?>
                    <div class="file-item">
                        <div class="file-info">
                            <span class="file-name"><?= htmlspecialchars($filename) ?></span>
                            <span class="file-size"><?= $size ?></span>
                        </div>
                        <div class="file-actions">
                            <a href="<?= $file ?>" class="btn btn-download" download>Download</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
