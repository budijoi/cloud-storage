<?php
$config_path = __DIR__ . '/minifs.json';
$config = ['storage_path' => '/mnt/sdcard/storage'];
if (is_file($config_path)) $config = json_decode(file_get_contents($config_path), true) ?: $config;
$root = rtrim($config['storage_path'], '/');

$file = isset($_GET['f']) ? $_GET['f'] : '';
$file = str_replace('..', '', $file);
$path = $root . '/' . ltrim($file, '/');

if (!is_file($path)) { http_response_code(404); echo 'File not found'; exit; }

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime_map = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'bmp' => 'image/bmp', 'webp' => 'image/webp',
    'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'pdf' => 'application/pdf',
    'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'flac' => 'audio/flac',
    'aac' => 'audio/aac', 'm4a' => 'audio/mp4', 'wma' => 'audio/x-ms-wma', 'opus' => 'audio/opus',
    'mp4' => 'video/mp4', 'avi' => 'video/x-msvideo', 'mkv' => 'video/x-matroska',
    'mov' => 'video/quicktime', 'webm' => 'video/webm', 'wmv' => 'video/x-ms-wmv',
    'flv' => 'video/x-flv', '3gp' => 'video/3gpp', 'mpeg' => 'video/mpeg', 'ts' => 'video/mp2t',
    'txt' => 'text/plain', 'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript',
    'json' => 'application/json', 'xml' => 'application/xml', 'zip' => 'application/zip',
    'rar' => 'application/vnd.rar', '7z' => 'application/x-7z-compressed', 'tar' => 'application/x-tar',
    'gz' => 'application/gzip', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'csv' => 'text/csv',
];
$mime = $mime_map[$ext] ?? 'application/octet-stream';

$size = filesize($path);
$name = basename($path);

// Support range requests (for video seeking)
if (isset($_SERVER['HTTP_RANGE'])) {
    preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m);
    $start = intval($m[1]);
    $end = $m[2] !== '' ? intval($m[2]) : $size - 1;
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: " . ($end - $start + 1));
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Content-Disposition: inline; filename="' . $name . '"');
    header('Cache-Control: no-cache');
    $f = fopen($path, 'rb');
    fseek($f, $start);
    $chunk = 8192;
    $pos = $start;
    while ($pos + $chunk <= $end) { echo fread($f, $chunk); $pos += $chunk; }
    if ($pos <= $end) echo fread($f, $end - $pos + 1);
    fclose($f);
    exit;
}

$disposition = isset($_GET['dl']) ? 'attachment' : 'inline';
header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('Accept-Ranges: bytes');
header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
header('Cache-Control: no-cache');
readfile($path);
