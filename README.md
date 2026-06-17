# MiniFileServer

Web file hosting ringan berbasis single-file PHP. Cocok untuk STB B860H / mini server dengan resource terbatas.

Semua file dan data tersimpan di **SD Card**. Saat HDD/SSD USB dicolok, **otomatis terdeteksi** dan bisa dipilih sebagai storage langsung dari web.

## Fitur

- Single file (1 file PHP, tanpa database)
- Auto-detect HDD/SSD USB — langsung muncul di halaman admin
- Pilih storage dari web — file tersimpan di SD card, HDD, atau SSD
- Upload file (drag & drop atau klik)
- Download & Hapus file
- Proteksi password
- Dark mode UI
- Auto-mount USB via udev (setup sekali)

## Instalasi di STB B860H (Armbian)

### 1. Pasang & Mount SD Card

```bash
lsblk  # Cek apakah SD card terbaca

# Format ext4 (jika SD card baru):
sudo mkfs.ext4 /dev/mmcblk1

# Mount
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
sudo mkdir -p /mnt/sdcard/minifileserver
sudo mkdir -p /mnt/sdcard/minifileserver/uploads

# Transfer file dari PC:
# scp index.php user@192.168.1.100:/mnt/sdcard/minifileserver/

sudo cp index.php /mnt/sdcard/minifileserver/
sudo chmod -R 777 /mnt/sdcard/minifileserver/uploads
```

### 4. Konfigurasi Apache

```bash
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
sudo a2dissite 000-default.conf
sudo a2ensite minifileserver.conf
sudo systemctl restart apache2
```

### 5. Konfigurasi PHP (upload file besar)

```bash
sudo nano /etc/php/*/apache2/php.ini
```

Ubah:

```ini
upload_max_filesize = 2G
post_max_size = 2G
max_execution_time = 300
```

```bash
sudo systemctl restart apache2
```

### 6. Setup Auto-Mount USB HDD/SSD

Jalankan script setup sekali:

```bash
sudo bash /mnt/sdcard/minifileserver/setup-automount.sh
```

Setelah ini, setiap HDD/SSD yang dicolok via USB akan otomatis:
- Ter-mount di `/media/<nama-drive>`
- Terdeteksi di MiniFileServer
- Bisa langsung dipilih sebagai storage dari halaman admin

## Cara Penggunaan

### Ganti Storage ke HDD/SSD

1. Login sebagai admin (password: `admin123`)
2. Di bagian **Storage**, semua drive yang terdeteksi akan muncul
3. Klik drive yang diinginkan, lalu klik **Gunakan Storage Terpilih**
4. File upload berikutnya akan tersimpan di drive tersebut

### Upload File

- Login admin, drag & drop file ke area upload, atau klik untuk memilih file

### Download / Hapus

- Setiap file punya tombol **Download** (publik) dan **Hapus** (admin saja)

## Login

Default password: `admin123`

Ubah di baris `$password = 'admin123';` pada file `index.php`.

Set `$password = '';` untuk tanpa login.

## Struktur Direktori

```
/mnt/sdcard/minifileserver/
├── index.php            # MiniFileServer (single file)
├── setup-automount.sh   # Script auto-mount USB (jalankan sekali)
├── minifs.json          # Konfigurasi storage aktif (auto-generated)
└── uploads/             # Folder file (default, bisa diubah ke HDD/SSD)
```

## Tips

- File yang sudah diupload tidak otomatis pindah saat ganti storage — hanya file baru yang masuk ke storage baru
- Untuk memindahkan file lama, login SSH dan gunakan `mv` manual
- Jika HDD/SSD dicabut, storage akan kembali fallback ke folder `uploads/` default
