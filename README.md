# MiniFileServer

File server ringan untuk STB B860H. Semua file tersimpan di **microSD**.

## Instalasi

SSH ke STB lalu jalankan:

```bash
cd /mnt/sdcard/minifileserver
bash install.sh
```

Buka browser: `http://(ip-stb)/`

> Cari IP: ketik `hostname -I`

## Halaman

| Alamat | Untuk |
|--------|-------|
| `http://(ip-stb)/` | Lihat & download file |
| `http://(ip-stb)/admin.php` | Admin panel (password: `admin123`) |

## Fitur Admin

- Upload file (banyak sekaligus)
- Buat folder baru
- Rename file/folder
- Hapus (satu atau massal)
- Pindahkan / Salin file antar folder
- Putar audio/video di popup player
- Download file

## Storage

Semua file otomatis tersimpan di: `/mnt/sdcard/storage/`

## Ubah Password

Edit `admin.php`, cari `$password = 'admin123';`

## Struktur

```
/mnt/sdcard/
├── minifileserver/
│   ├── index.php
│   ├── admin.php
│   ├── login-form.php
│   ├── serve.php
│   ├── minifs.json
│   └── install.sh
└── storage/        ← file kamu disini
```
