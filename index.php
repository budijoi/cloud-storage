<?php
$config_path = __DIR__ . '/minifs.json';
$config = ['storage_path' => '/mnt/sdcard/storage'];
if (is_file($config_path)) $config = json_decode(file_get_contents($config_path), true) ?: $config;
$root = rtrim($config['storage_path'], '/');
if (!is_dir($root)) mkdir($root, 0755, true);

$dir = isset($_GET['dir']) ? str_replace('..', '', $_GET['dir']) : '';
$cur_dir = $root . ($dir ? '/' . $dir : '');
if (!is_dir($cur_dir)) $cur_dir = $root;

$audio_exts = ['mp3','wav','ogg','flac','aac','m4a','wma','opus'];
$video_exts = ['mp4','avi','mkv','mov','webm','wmv','flv','3gp','mpeg','ts'];
$image_exts = ['jpg','jpeg','png','gif','bmp','webp','svg','ico'];

$items = [];
foreach (glob($cur_dir . '/*') as $f) {
    $name = basename($f);
    $is_dir = is_dir($f);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $size = $is_dir ? 0 : filesize($f);
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
    $items[] = compact('name', 'is_dir', 'size', 'ext', 'icon');
}
usort($items, function($a, $b) { return $b['is_dir'] - $a['is_dir'] ?: strcasecmp($a['name'], $b['name']); });

$crumbs = [['name' => 'Files', 'path' => '']];
if ($dir) {
    $parts = explode('/', $dir);
    $p = '';
    foreach ($parts as $part) { $p .= ($p ? '/' : '') . $part; $crumbs[] = ['name' => $part, 'path' => $p]; }
}

function fmt_size($b) {
    $u = ['B','KB','MB','GB','TB']; $i = 0;
    while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
    return round($b, 1) . ' ' . $u[$i];
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
.top{background:#1e293b;border-radius:12px;padding:20px 24px;margin-bottom:20px;border:1px solid #334155;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.top h1{font-size:22px;color:#38bdf8;display:flex;align-items:center;gap:8px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 18px;border-radius:8px;font-size:14px;text-decoration:none;font-weight:500;transition:all .2s}
.btn-p{background:#0ea5e9;color:#fff}
.btn-p:hover{background:#0284c7}
.btn-o{background:transparent;color:#94a3b8;border:1px solid #334155}
.btn-o:hover{background:#1e293b;color:#e2e8f0}
.crumb{font-size:13px;display:flex;flex-wrap:wrap;gap:4px;align-items:center;margin-bottom:15px}
.crumb a{color:#64748b;text-decoration:none}
.crumb a:hover{color:#38bdf8}
.crumb .s{color:#334155;margin:0 4px}
.crumb .c{color:#e2e8f0}
.card{background:#1e293b;border-radius:12px;padding:20px 24px;border:1px solid #334155}
.fi{display:flex;align-items:center;padding:12px 0;border-bottom:1px solid #1e293b;gap:12px}
.fi:last-child{border-bottom:none}
.fn{font-weight:500;color:#e2e8f0;text-decoration:none;word-break:break-all;font-size:14px;display:block;cursor:pointer}
.fn:hover{color:#38bdf8}
.fs{font-size:12px;color:#64748b}
.em{text-align:center;padding:40px 0;color:#64748b;font-size:14px}
.mp-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:999;align-items:center;justify-content:center}
.mp-overlay.show{display:flex}
.mp-box{background:#1e293b;border-radius:12px;padding:24px;width:90%;max-width:720px;border:1px solid #334155;max-height:90vh;overflow:auto}
.mp-box .mt{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px}
.mp-box .mtl{font-size:15px;color:#e2e8f0;font-weight:600;word-break:break-all;flex:1}
.mp-box .mc{font-size:28px;color:#64748b;cursor:pointer;background:none;border:none;flex-shrink:0;line-height:1}
.mp-box .mc:hover{color:#ef4444}
.mp-box video,.mp-box audio{width:100%;border-radius:8px;background:#000}
.mp-box audio{margin:20px 0}
.mp-box .ma{display:flex;gap:8px;margin-top:14px}
@media(max-width:600px){.top{flex-direction:column;text-align:center}.mp-box{padding:16px}}
</style>
</head>
<body>
<div class="wrap">
<div class="top">
<h1>📁 MiniFileServer</h1>
<a href="admin.php" class="btn btn-o">🔒 Admin</a>
</div>

<div class="crumb">
<a href="?dir=">🏠</a>
<?php foreach ($crumbs as $i => $c): ?>
<span class="s">/</span>
<?php if ($i < count($crumbs)-1): ?><a href="?dir=<?=urlencode($c['path'])?>"><?=htmlspecialchars($c['name'])?></a>
<?php else: ?><span class="c"><?=htmlspecialchars($c['name'])?></span>
<?php endif; ?>
<?php endforeach; ?>
</div>

<div class="card">
<?php if (empty($items)): ?>
<div class="em">📂 Belum ada file</div>
<?php else: ?>
<?php foreach ($items as $item):
$url = 'serve.php?f=' . urlencode(($dir ? $dir . '/' : '') . $item['name']);
$is_media = !$item['is_dir'] && (in_array($item['ext'], $audio_exts) || in_array($item['ext'], $video_exts) || in_array($item['ext'], $image_exts));
?>
<div class="fi">
<span style="font-size:22px;flex-shrink:0"><?=$item['icon']?></span>
<div style="flex:1;min-width:0">
<?php if ($item['is_dir']): ?>
<a href="?dir=<?=urlencode($dir ? $dir . '/' . $item['name'] : $item['name'])?>" class="fn"><?=htmlspecialchars($item['name'])?></a>
<?php else: ?>
<a <?php if ($is_media): ?>onclick='openPlayer(<?=json_encode($url)?>,<?=json_encode($item['name'])?>,<?=json_encode($item['ext'])?>);return false' href="#"<?php else: ?>href="<?=htmlspecialchars($url)?>"<?php endif; ?> class="fn"><?=htmlspecialchars($item['name'])?></a>
<span class="fs"><?=fmt_size($item['size'])?></span>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>

<div class="mp-overlay" id="playerModal" onclick="if(event.target===this)closePlayer()">
<div class="mp-box">
<div class="mt">
<span class="mtl" id="playerTitle">-</span>
<button class="mc" onclick="closePlayer()">&times;</button>
</div>
<div id="playerContainer"></div>
<div class="ma">
<a id="playerDownload" class="btn btn-p" download>📥 Download</a>
</div>
</div>
</div>

<script>
var audioExts = <?=json_encode($audio_exts)?>;
var videoExts = <?=json_encode($video_exts)?>;
var imageExts = <?=json_encode($image_exts)?>;
var pm = document.getElementById('playerModal');
var pt = document.getElementById('playerTitle');
var pc = document.getElementById('playerContainer');
var pd = document.getElementById('playerDownload');

function openPlayer(url, name, ext) {
pc.innerHTML = '';
pt.textContent = name;
pd.href = url + (url.indexOf('?') > -1 ? '&' : '?') + 'dl=1';
var el;
if (audioExts.indexOf(ext) !== -1) { el = document.createElement('audio'); el.controls = true; el.autoplay = true; }
else if (videoExts.indexOf(ext) !== -1) { el = document.createElement('video'); el.controls = true; el.autoplay = true; el.style.maxHeight = '60vh'; }
else { el = document.createElement('img'); el.style.maxWidth = '100%'; el.style.maxHeight = '70vh'; el.style.objectFit = 'contain'; el.style.borderRadius = '8px'; }
el.src = url;
pc.appendChild(el);
pm.classList.add('show');
}

function closePlayer() { pc.innerHTML = ''; pm.classList.remove('show'); }
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePlayer(); });
</script>
</body>
</html>
