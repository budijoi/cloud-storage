# MiniFileServer

File hosting + File manager untuk STB B860H. Bisa diakses dari HP, laptop, TV.

## Instalasi (3 langkah)

**1. Kirim file ke STB** (dari PC):
```bash
scp * user@(ip-stb):/var/www/html/
```

**2. SSH ke STB, jalankan:**
```bash
cd /var/www/html && bash install.sh
```

**3. Buka browser:**
```
http://(ip-stb)/
```

> Cari IP STB: ketik `hostname -I` di terminal STB

## Halaman

| Halaman | Akses | Fungsi |
|---------|-------|--------|
| `index.php` | Publik | Lihat & download file |
| `admin.php` | Password: `admin123` | Upload, hapus, rename, pindah, salin, buat folder |

## Fitur Admin

Buka `http://(ip-stb)/admin.php` lalu login.

| Fitur | Cara |
|-------|------|
| Upload | Klik tombol Upload, pilih file (bisa banyak) |
| Buat folder | Klik Folder Baru, masukkan nama |
| Rename | Klik ikon ✏ di samping file/folder |
| Hapus | Klik ikon 🗑 atau centang lalu Hapus |
| Pindahkan | Centang file, klik **Pindahkan**, pilih folder tujuan |
| Salin | Centang file, klik **Salin**, pilih folder tujuan |
| Download | Klik nama file |
| Ganti storage | Buka `index.php`, login, pilih HDD/SSD di bagian Storage |

## Default Password

```
admin123
```

Ubah di file `index.php` dan `admin.php`, cari `$password = 'admin123';`

## Struktur File

```
/var/www/html/
├── index.php         # Halaman publik (download)
├── admin.php         # Admin panel (file manager lengkap)
├── login-form.php    # Halaman login admin
├── install.sh        # Instalasi otomatis (jalankan sekali)
├── setup-automount.sh # Auto-mount USB (dijalankan install.sh)
├── minifs.json       # Konfigurasi storage (auto)
└── uploads/          # File-file kamu
```
