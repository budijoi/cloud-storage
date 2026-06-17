<?php
$config_path = __DIR__ . '/minifs.json';
$config = ['storage_path' => __DIR__ . '/uploads'];
if (is_file($config_path)) $config = json_decode(file_get_contents($config_path), true) ?: $config;
$root = rtrim($config['storage_path'], '/');
if (!is_dir($root)) mkdir($root, 0755, true);

$dir = isset($_GET['dir']) ? str_replace('..', '', $_GET['dir']) : '';
$cur_dir = $root . ($dir ? '/' . $dir : '');
if (!is_dir($cur_dir)) $cur_dir = $root;

$items = [];
$all_files = glob($cur_dir . '/*');
if ($all_files) {
    foreach ($all_files as $f) {
        $name = basename($f);
        $is_dir = is_dir($f);
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
        $items[] = ['name' => $name, 'path' => $f, 'is_dir' => $is_dir, 'size' => is_file($f) ? filesize($f) : 0, 'icon' => $icon];
    }
    usort($items, function($a, $b) { return $b['is_dir'] - $a['is_dir'] ?: strcasecmp($a['name'], $b['name']); });
}

function fmt_size($b) {
    $u = ['B','KB','MB','GB','TB']; $i = 0;
    while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
    return round($b, 1) . ' ' . $u[$i];
}

// Breadcrumbs
$crumbs = [['name' => 'Files', 'path' => '']];
if ($dir) {
    $parts = explode('/', $dir);
    $p = '';
    foreach ($parts as $part) {
        $p .= ($p ? '/' : '') . $part;
        $crumbs[] = ['name' => $part, 'path' => $p];
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MiniFileServer</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0}
.wrap{max-width:800px;margin:0 auto;padding:20px}
.topbar{background:#1e293b;border-radius:12px;padding:20px 24px;margin-bottom:20px;border:1px solid #334155;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.topbar h1{font-size:22px;color:#38bdf8;display:flex;align-items:center;gap:8px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 18px;border-radius:8px;font-size:14px;text-decoration:none;font-weight:500;transition:all .2s}
.btn-primary{background:#0ea5e9;color:#fff}
.btn-primary:hover{background:#0284c7}
.btn-outline{background:transparent;color:#94a3b8;border:1px solid #334155}
.btn-outline:hover{background:#1e293b;color:#e2e8f0}
.breadcrumb{font-size:13px;display:flex;flex-wrap:wrap;gap:4px;align-items:center;margin-bottom:15px}
.breadcrumb a{color:#64748b;text-decoration:none}
.breadcrumb a:hover{color:#38bdf8}
.breadcrumb .sep{color:#334155;margin:0 4px}
.breadcrumb .current{color:#e2e8f0}
.card{background:#1e293b;border-radius:12px;padding:20px 24px;border:1px solid #334155}
.file-item{display:flex;align-items:center;padding:12px 0;border-bottom:1px solid #1e293b;gap:12px}
.file-item:last-child{border-bottom:none}
.file-icon{font-size:22px;flex-shrink:0}
.file-info{flex:1;min-width:0}
.file-name{font-weight:500;color:#e2e8f0;text-decoration:none;word-break:break-all;font-size:14px;display:block}
.file-name:hover{color:#38bdf8}
.file-size{font-size:12px;color:#64748b}
.empty{text-align:center;padding:40px 0;color:#64748b;font-size:14px}
@media(max-width:600px){.topbar{flex-direction:column;text-align:center}}
</style>
</head>
<body>
<div class="wrap">
<div class="topbar">
<h1>📁 MiniFileServer</h1>
<a href="admin.php" class="btn btn-outline">🔒 Admin</a>
</div>

<div class="breadcrumb">
<a href="?dir=">🏠</a>
<?php foreach ($crumbs as $i => $c): ?>
<span class="sep">/</span>
<?php if ($i < count($crumbs)-1): ?><a href="?dir=<?=urlencode($c['path'])?>"><?=htmlspecialchars($c['name'])?></a>
<?php else: ?><span class="current"><?=htmlspecialchars($c['name'])?></span>
<?php endif; ?>
<?php endforeach; ?>
</div>

<div class="card">
<?php if (empty($items)): ?>
<div class="empty">📂 Belum ada file</div>
<?php else: ?>
<?php foreach ($items as $item):
$enc = rawurlencode($item['name']);
$dl_path = $dir ? $dir . '/' . $item['name'] : $item['name'];
?>
<div class="file-item">
<span class="file-icon"><?=$item['icon']?></span>
<div class="file-info">
<?php if ($item['is_dir']): ?>
<a href="?dir=<?=urlencode($dir ? $dir . '/' . $item['name'] : $item['name'])?>" class="file-name"><?=htmlspecialchars($item['name'])?></a>
<?php else: ?>
<a href="uploads/<?=htmlspecialchars($dl_path)?>" class="file-name" download><?=htmlspecialchars($item['name'])?></a>
<span class="file-size"><?=fmt_size($item['size'])?></span>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</body>
</html>
