# MiniFileServer

File hosting untuk STB B860H. Simpan file di SD card, HDD, atau SSD. Bisa diakses dari HP, laptop, TV — dari rumah atau dari luar via internet.

## Cara 1: Instalasi Otomatis (Paling Mudah)

Copy semua file ke STB, lalu jalankan:

```bash
cd /var/www/html
bash install.sh
```

Selesai. Buka browser: `http://(ip-stb)/`

Transfer file dari PC ke STB bisa pakai:

```bash
# Dari PC:
scp index.php install.sh setup-automount.sh user@(ip-stb):/var/www/html/
```

## Cara 2: Manual (Jika Ingin Tahu Prosesnya)

### Yang dibutuhkan
- STB B860H sudah terinstall Armbian di SD card
- Koneksi internet

### Langkah

**1. Install Apache & PHP**
```bash
sudo apt update
sudo apt install apache2 php -y
```

**2. Copy file MiniFileServer**
```bash
sudo cp index.php /var/www/html/
sudo mkdir /var/www/html/uploads
sudo chmod 777 /var/www/html/uploads
```

**3. Selesai**
```bash
sudo systemctl restart apache2
```

Buka browser: `http://(ip-stb)/`

> Cari IP STB: ketik `hostname -I` di terminal STB

## Login Admin

Klik **Login Admin** di halaman utama.

| Data | Value |
|------|-------|
| Username | `admin` |
| Password | `admin123` |

Ubah password: edit file `index.php`, cari baris `$password = 'admin123';`, ganti angkanya.

## Ganti Storage ke HDD/SSD (USB)

1. Colok HDD/SSD ke USB STB
2. Login admin
3. Di bagian **Storage**, akan muncul drive yang terdeteksi
4. Klik drive, lalu klik **Gunakan Storage Terpilih**

> File baru akan tersimpan di HDD/SSD. File lama tetap di tempat sebelumnya.

## Biar Bisa Diakses dari Luar Rumah (Internet)

### Pakai Cloudflare Tunnel (Gratis, Disarankan)

```bash
# Install
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64 -o /usr/local/bin/cloudflared
chmod +x /usr/local/bin/cloudflared

# Login (ikuti link yang muncul)
cloudflared tunnel login

# Buat tunnel
cloudflared tunnel create minifs
cloudflared tunnel route dns minifs namafile.anda.com
cloudflared tunnel run minifs
```

Nanti akses via `https://namafile.anda.com`

> Punya domain? Beli di Niagahoster, Namecheap, dll. Gratis? Pakai [nip.io](http://nip.io) — tinggal akses `http://(ip-stb).nip.io`

## Ganti Password

Edit `index.php` baris paling atas:

```php
$password = 'admin123';
```

Ganti `admin123` dengan password baru. Kalau ingin tanpa password:

```php
$password = '';
```

## File yang Ada

| File | Untuk |
|------|-------|
| `index.php` | Aplikasi utama (1 file doang) |
| `install.sh` | Instal otomatis (jalankan sekali) |
| `setup-automount.sh` | Biar HDD/SSD otomatis kebaca (dijalankan install.sh) |
| `README.md` | Panduan ini |
