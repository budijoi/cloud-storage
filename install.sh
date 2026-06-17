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
PHP_INI=$(find /etc/php -name php.ini -path "*/apache2/*" | head -1)
if [ -n "$PHP_INI" ]; then
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 2G/' "$PHP_INI"
    sed -i 's/^post_max_size = .*/post_max_size = 2G/' "$PHP_INI"
    sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
fi

# 3. Copy file aplikasi
echo "[3/4] Menyalin aplikasi ke /var/www/html..."
cp index.php /var/www/html/index.php
mkdir -p /var/www/html/uploads
chmod 777 /var/www/html/uploads

# 4. Setup auto-mount USB
echo "[4/4] Setup auto-mount USB HDD/SSD..."
if [ -f setup-automount.sh ]; then
    bash setup-automount.sh
fi

# Selesai
IP=$(hostname -I | awk '{print $1}')
echo ""
echo "=== SELESAI! ==="
echo ""
echo "Akses MiniFileServer di browser:"
echo "  http://$IP/"
echo ""
echo "Default password: admin123"
echo "Ubah di index.php baris: \$password = 'admin123';"
echo ""
