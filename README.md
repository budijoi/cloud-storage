# MiniFileServer

File server ringan untuk STB B860H. Semua file tersimpan di **microSD**.
Akses file dari HP, laptop, atau TV — cukup lewat browser.

---

## Yang Dibutuhkan

| Barang | Keterangan |
|--------|-----------|
| STB B860H | Sudah terinstall Armbian di SD Card |
| Kabel LAN / WiFi | STB terhubung ke jaringan rumah |
| PC / Laptop | Untuk transfer file dan setting awal |

---

## Cara Install (5 menit)

### Langkah 1: Cari IP STB

SSH ke STB, lalu ketik:

```bash
hostname -I
```

Contoh hasil: `192.168.1.100`

> **Catat angka IP ini.** Kamu akan pakai untuk akses dari browser.

---

### Langkah 2: Transfer File dari PC ke STB

Buka terminal/CMD di PC, lalu kirim file aplikasi:

```bash
scp "D:\path\ke\file\*" root@192.168.1.100:/mnt/sdcard/minifileserver/
```

> Ganti `192.168.1.100` dengan IP STB kamu.
> Masukkan password root STB jika diminta.

Atau pakai flashdisk:
1. Copy semua file ke flashdisk
2. Colok ke USB STB
3. SSH ke STB lalu ketik:
```bash
cp /media/usb*/* /mnt/sdcard/minifileserver/
```

---

### Langkah 3: Jalankan Installer

SSH ke STB lalu ketik:

```bash
bash /mnt/sdcard/minifileserver/install.sh
```

Tunggu sampai selesai (1-2 menit). Installer otomatis:
- Install Apache & PHP
- Konfigurasi upload file besar
- Buat folder storage di microSD
- Setup auto-mount USB HDD/SSD

---

### Langkah 4: Buka di Browser

Buka browser di HP / laptop / TV yang terhubung ke jaringan yang sama:

```
http://192.168.1.100/
```

Ganti `192.168.1.100` dengan IP STB kamu.

---

## Aktifkan WiFi Cadangan

Biar STB tetap bisa diakses meski kabel LAN dicabut.

### Cara 1: Setup WiFi (rekomendasi)

SSH ke STB, jalankan:

```bash
bash /mnt/sdcard/minifileserver/setup-wifi.sh
```

Masukkan SSID dan password WiFi saat diminta.

```
=== Setup WiFi Cadangan ===

Interface WiFi terdeteksi: wlan0

Nama WiFi (SSID): Pohon Mangga
Password WiFi:
Menghubungkan ke 'Pohon Mangga'...

=== WiFi siap! ===

IP Via WiFi : 192.168.1.105
IP Via LAN  : 192.168.1.100

Akses MiniFileServer dari WiFi:
  http://192.168.1.105/
  http://192.168.1.105/admin.php
```

### Cara 2: Manual via armbian-config

```bash
armbian-config
```

Pilih: **Network** → **WiFi** → pilih SSID → masukkan password.

### Cara Kerja

- **LAN** sebagai koneksi utama (prioritas tinggi)
- **WiFi** sebagai cadangan (prioritas rendah)
- Jika LAN dicabut, STB otomatis pindah ke WiFi
- Jika LAN dicolok lagi, balik ke LAN

> Untuk ganti WiFi, jalankan ulang `bash setup-wifi.sh`

---

## Halaman Aplikasi

Ada 2 halaman:

| Alamat | Untuk | Password |
|--------|-------|----------|
| `http://(ip-stb)/` | Melihat & download file (publik) | Tidak perlu |
| `http://(ip-stb)/admin.php` | Mengelola file (admin) | `admin123` |

---

## Panduan Fitur Admin

### Login

```
1. Buka http://(ip-stb)/admin.php
2. Masukkan password: admin123
3. Klik Login
```
<img width="359" height="340" alt="Screenshot 2026-06-18 103456" src="https://github.com/user-attachments/assets/32b25451-0de2-4f96-9ebc-0a3e37e70367" />

---

### Upload File

```
1. Klik tombol [📤 Upload] di toolbar
2. Pilih satu atau banyak file
3. Klik Upload
```

File akan tersimpan di microSD (`/mnt/sdcard/storage/`).

---

### Buat Folder Baru

```
1. Klik tombol [📁 Folder Baru]
2. Masukkan nama folder
3. Klik Buat
```

---

### Rename File/Folder

```
1. Cari file/folder yang ingin diubah
2. Klik ikon ✏️ di kolom Aksi
3. Masukkan nama baru
4. Klik Simpan
```

---

### Hapus File/Folder

**Hapus satu:**
```
1. Cari file/folder yang ingin dihapus
2. Klik ikon 🗑️ di kolom Aksi
3. Konfirmasi dengan klik OK
```

**Hapus banyak sekaligus:**
```
1. Centang file/folder yang ingin dihapus
2. Klik tombol [🗑 Hapus] di toolbar
3. Konfirmasi
```

---

### Pindahkan atau Salin File

```
1. Centang file yang ingin dipindah/disalin
2. Klik [✂ Pindah] atau [📋 Salin] di toolbar
3. Pilih folder tujuan
4. Klik Pindah / Salin
```

---

### Preview Image

```
1. Klik nama file gambar (.jpg, .png, .gif, dll)
2. Muncul pop-up dengan preview gambar
3. Klik [📥 Download] untuk menyimpan
4. Klik ✕ atau tekan ESC untuk tutup
```

<img width="660" height="550" alt="Screenshot 2026-06-18 103621" src="https://github.com/user-attachments/assets/85671b23-241d-4731-848f-07720e1c5607" />


---

### Putar Audio/Video

```
1. Klik nama file .mp3 / .mp4 / .mkv / dll
2. Muncul pop-up dengan player
3. Klik ▶️ untuk play
4. Klik [📥 Download] untuk menyimpan
```

<img width="687" height="552" alt="Screenshot 2026-06-18 103227" src="https://github.com/user-attachments/assets/125e207f-056c-419b-bb11-6db70cd2f67c" />

<img width="709" height="552" alt="Screenshot 2026-06-18 103208" src="https://github.com/user-attachments/assets/bc69f1cd-2a56-4fbc-b02b-38801c699efb" />

---

### Navigasi Folder

- Klik folder untuk masuk
- Klik **🏠** atau breadcrumb untuk kembali ke folder sebelumnya

```
🏠 / Videos / Movies
    ↑ Klik untuk naik level
```

---

## Ganti Password

Edit file `admin.php` di baris paling atas:

```php
$password = 'admin123';
```

Ganti `admin123` dengan password baru.

---

## Pakai HDD/SSD USB

Colok HDD/SSD ke port USB STB. Jika sudah menjalankan `install.sh`, drive akan otomatis ter-mount.

Untuk mengganti penyimpanan ke HDD/SSD:

```bash
# Cek apakah HDD terbaca
lsblk

# Mount manual (contoh)
sudo mount /dev/sda1 /mnt/hdd
sudo chmod 777 /mnt/hdd

# Ubah config storage
echo '{"storage_path":"/mnt/hdd"}' > /mnt/sdcard/minifileserver/minifs.json
```

---

## Akses dari Luar Rumah (Internet)

### Cara termudah: Cloudflare Tunnel

```bash
# Install cloudflared
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64 -o /usr/local/bin/cloudflared
chmod +x /usr/local/bin/cloudflared

# Login
cloudflared tunnel login

# Buat tunnel
cloudflared tunnel create minifs

# Arahkan domain
cloudflared tunnel route dns minifs namafile.anda.com

# Jalankan
cloudflared tunnel run minifs
```

Akses dari mana saja: `https://namafile.anda.com`

---

## Struktur File di STB

```
/mnt/sdcard/
├── minifileserver/        ← Folder aplikasi
│   ├── index.php          ← Halaman publik
│   ├── admin.php          ← Admin panel
│   ├── login-form.php     ← Halaman login
│   ├── serve.php          ← File server (streaming & preview)
│   ├── minifs.json        ← Konfigurasi penyimpanan
│   ├── install.sh         ← Installer (jalankan sekali)
│   └── setup-wifi.sh      ← Setup WiFi cadangan
│
└── storage/               ← File-file kamu disini
```

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `admin.php` tidak bisa diakses | Jalankan `bash install.sh` dulu |
| Gagal upload | Cek izin folder: `chmod 777 /mnt/sdcard/storage` |
| File tidak muncul | Refresh browser (F5) |
| Player tidak muncul | Update file `index.php` & `admin.php` |
| Lupa password | Edit `admin.php`, cari `$password =` |
