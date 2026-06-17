# MiniFileServer

Web file hosting ringan berbasis single-file PHP. Cocok untuk STB B860H / mini server dengan resource terbatas.

## Fitur

- Single file (1 file PHP, tanpa database)
- Upload file (drag & drop atau klik)
- Download file
- Hapus file
- Proteksi password
- Dark mode UI
- Tanpa dependensi tambahan

## Persyaratan

- PHP 7.4+
- Web server (Apache / Nginx / PHP built-in server)

## Instalasi di STB B860H (Armbian)

### 1. Install Web Server & PHP

```bash
sudo apt update
sudo apt install apache2 php php-mbstring -y
```

### 2. Copy File

```bash
# Hapus file default Apache
sudo rm /var/www/html/index.html

# Copy MiniFileServer
sudo cp index.php /var/www/html/

# Buat folder uploads
sudo mkdir /var/www/html/uploads
sudo chmod 777 /var/www/html/uploads
```

### 3. Konfigurasi PHP (upload larger files)

```bash
sudo nano /etc/php/*/apache2/php.ini
```

Cari dan ubah:

```ini
upload_max_filesize = 2G
post_max_size = 2G
max_execution_time = 300
```

### 4. Restart Apache & Cek IP

```bash
sudo systemctl restart apache2
hostname -I
```

### 5. Akses

Buka browser dari perangkat lain di jaringan yang sama:

```
http://<ip-stb>/
```

Contoh: `http://192.168.1.100/`

## Login

Default password: `admin123`

Ubah password di baris `$password = 'admin123';` pada file `index.php`.

Set `$password = '';` jika ingin tanpa login.

## Akses dari Internet

### Opsi 1: Port Forwarding (tidak disarankan)

1. Set IP STB static di router
2. Forward port 80 (atau 8080) ke IP STB
3. Gunakan DDNS jika IP publik dinamis

### Opsi 2: Cloudflare Tunnel (disarankan)

```bash
# Install cloudflared
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64 -o /usr/local/bin/cloudflared
chmod +x /usr/local/bin/cloudflared

# Login & tunnel
cloudflared tunnel login
cloudflared tunnel create mini-file-server
cloudflared tunnel route dns mini-file-server subdomain.anda.com
cloudflared tunnel run mini-file-server
```

Atau buat service auto-start:

```bash
sudo cloudflared service install $(cat ~/.cloudflared/*.json | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
```

## Penggunaan

| Aksi | Cara |
|------|------|
| Upload | Klik area upload atau drag & drop file |
| Download | Klik tombol Download |
| Hapus | Klik tombol Hapus (perlu login) |
| Login | Klik Logout jika sudah login, atau akses ulang halaman |
| Logout | Klik tombol Logout |

## Struktur Direktori

```
/var/www/html/
├── index.php       # MiniFileServer (single file)
└── uploads/        # Folder penyimpanan file
```
