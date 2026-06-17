# MiniFileServer

Web file hosting ringan berbasis single-file PHP. Cocok untuk STB B860H / mini server dengan resource terbatas.

Semua file dan data tersimpan di **SD Card**.

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
- SD Card sudah terpasang di STB

## Instalasi di STB B860H (Armbian)

### 1. Pasang & Mount SD Card

```bash
# Cek apakah SD card sudah terbaca
lsblk

# Biasanya terdeteksi sebagai /dev/mmcblk1 atau /dev/sda
# Format ext4 (jika SD card baru):
sudo mkfs.ext4 /dev/mmcblk1

# Mount ke direktori permanen
sudo mkdir -p /mnt/sdcard
sudo mount /dev/mmcblk1 /mnt/sdcard

# Auto-mount saat boot
echo '/dev/mmcblk1 /mnt/sdcard ext4 defaults 0 0' | sudo tee -a /etc/fstab
```

### 2. Install Web Server & PHP

```bash
sudo apt update
sudo apt install apache2 php php-mbstring -y
```

### 3. Copy Aplikasi ke SD Card

```bash
# Buat folder aplikasi di SD card
sudo mkdir -p /mnt/sdcard/minifileserver
sudo mkdir -p /mnt/sdcard/minifileserver/uploads

# Copy file index.php (transfer dulu dari PC ke STB via scp/flashdisk)
# Contoh via scp dari PC:
# scp index.php user@192.168.1.100:/mnt/sdcard/minifileserver/

sudo cp index.php /mnt/sdcard/minifileserver/
sudo chmod -R 777 /mnt/sdcard/minifileserver/uploads
```

### 4. Konfigurasi Apache Agar Serve dari SD Card

```bash
# Buat virtual host untuk aplikasi
sudo nano /etc/apache2/sites-available/minifileserver.conf
```

Isi:

```apache
<VirtualHost *:80>
    DocumentRoot /mnt/sdcard/minifileserver
    <Directory /mnt/sdcard/minifileserver>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
# Nonaktifkan default site, aktifkan konfigurasi baru
sudo a2dissite 000-default.conf
sudo a2ensite minifileserver.conf
sudo systemctl restart apache2
```

### 5. Konfigurasi PHP (upload large files)

```bash
sudo nano /etc/php/*/apache2/php.ini
```

Cari dan ubah:

```ini
upload_max_filesize = 2G
post_max_size = 2G
max_execution_time = 300
```

### 6. Restart Apache & Cek IP

```bash
sudo systemctl restart apache2
hostname -I
```

### 7. Akses

Buka browser dari perangkat lain di jaringan yang sama:

```
http://<ip-stb>/
```

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
/mnt/sdcard/minifileserver/
├── index.php       # MiniFileServer (single file)
└── uploads/        # Folder penyimpanan file (di SD card)
```
