#!/bin/bash
# MiniFileServer - Instalasi otomatis untuk STB B860H
# Jalankan: bash install.sh

set -e
echo "=== MiniFileServer - Instalasi Otomatis ==="
echo ""

# 1. Update dan install Apache + PHP
echo "[1/4] Menginstall Apache dan PHP..."
apt update
apt install -y apache2 php php-mbstring

# 2. Konfigurasi PHP untuk upload besar
echo "[2/4] Konfigurasi PHP untuk upload file besar..."
INI=$(find /etc/php -name php.ini -path "*/apache2/*" | head -1)
if [ -n "$INI" ]; then
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 2G/' "$INI"
    sed -i 's/^post_max_size = .*/post_max_size = 2G/' "$INI"
    sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$INI"
fi

# 3. Copy file aplikasi
echo "[3/4] Menyalin aplikasi..."
cp index.php admin.php login-form.php serve.php /mnt/sdcard/minifileserver/ 2>/dev/null

# 4. Buat folder storage di microSD
echo "[4/4] Membuat folder storage di microSD..."
mkdir -p /mnt/sdcard/storage
chmod 777 /mnt/sdcard/storage

# Buat config default
echo '{"storage_path":"/mnt/sdcard/storage"}' > /mnt/sdcard/minifileserver/minifs.json

# 5. Setup auto-mount USB
if [ -f setup-automount.sh ]; then
    bash setup-automount.sh 2>/dev/null || true
fi

# Selesai
IP=$(hostname -I | awk '{print $1}')
echo ""
echo "=== SELESAI! ==="
echo ""
echo "Akses MiniFileServer di browser:"
echo "  http://$IP/"
echo "  http://$IP/admin.php"
echo ""
echo "Password admin: admin123"
echo "Semua file tersimpan di: /mnt/sdcard/storage/"
echo ""
