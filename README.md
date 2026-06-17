# MiniFileServer

File hosting + File manager untuk STB B860H. Semua file tersimpan di SD card atau HDD/SSD USB.

## Instalasi

**1. Install Apache & PHP** (SSH ke STB):
```bash
sudo apt update
sudo apt install apache2 php -y
```

**2. Kirim file dari PC ke STB:**
```bash
scp * user@(ip-stb):/var/www/html/
```

**3. Beri izin folder upload:**
```bash
sudo mkdir -p /var/www/html/uploads
sudo chmod 777 /var/www/html/uploads
sudo systemctl restart apache2
```

**4. Buka browser:**
```
http://(ip-stb)/
http://(ip-stb)/admin.php
```

> Cari IP STB: ketik `hostname -I` di terminal

## Login Admin

Buka `http://(ip-stb)/admin.php`

| Data | Value |
|------|-------|
| Password | `admin123` |

Ubah password: edit `admin.php`, cari `$password = 'admin123';`

## Fitur

| Halaman | Akses | Fungsi |
|---------|-------|--------|
| `index.php` | Publik | Lihat & download file |
| `admin.php` | Password | Upload, hapus, rename, pindah, salin, buat folder |

**Di admin panel:**
- Upload file (bisa banyak & drag-and-drop)
- Buat folder baru
- Rename file/folder (klik ✏)
- Hapus (satu-satu atau centang banyak lalu hapus massal)
- Pindahkan file ke folder lain
- Salin file ke folder lain
- Navigasi folder dengan breadcrumb
- Info kapasitas storage

## Pakai HDD/SSD USB

Colok HDD/SSD ke USB STB, lalu mount:

```bash
lsblk
sudo mount /dev/sda1 /mnt/hdd
sudo chmod 777 /mnt/hdd
```

Ubah storage di `admin.php`: edit baris `$config['storage_path']` atau buka menu Storage di admin panel (login dulu).

## Akses dari Internet (Cloudflare Tunnel)

```bash
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64 -o /usr/local/bin/cloudflared
chmod +x /usr/local/bin/cloudflared
cloudflared tunnel login
cloudflared tunnel create minifs
cloudflared tunnel route dns minifs namafile.anda.com
cloudflared tunnel run minifs
```

Akses via `https://namafile.anda.com`

## Struktur File

```
/var/www/html/
├── index.php         # Halaman publik (download)
├── admin.php         # Admin panel (file manager)
└── login-form.php    # Halaman login
```
