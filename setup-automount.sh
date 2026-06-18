#!/bin/bash
# Auto-mount USB HDD/SSD untuk STB B860H (Armbian)
# Jalankan: sudo bash setup-automount.sh

set -e

echo "=== Setup Auto-Mount USB Drive untuk MiniFileServer ==="

# 1. Install udisks2 untuk auto-mount
apt update
apt install -y udisks2 udev

# 2. Buat aturan udev untuk auto-mount
cat > /etc/udev/rules.d/99-usb-automount.rules << 'RULES'
# Auto-mount USB mass storage devices
ACTION=="add", KERNEL=="sd[a-z][0-9]", RUN+="/usr/local/bin/usb-mount.sh %k"
ACTION=="add", KERNEL=="mmcblk[0-9]p[0-9]", RUN+="/usr/local/bin/usb-mount.sh %k"
ACTION=="remove", KERNEL=="sd[a-z][0-9]", RUN+="/usr/local/bin/usb-umount.sh %k"
ACTION=="remove", KERNEL=="mmcblk[0-9]p[0-9]", RUN+="/usr/local/bin/usb-umount.sh %k"
RULES

# 3. Buat script mount
cat > /usr/local/bin/usb-mount.sh << 'SCRIPT'
#!/bin/bash
DEVICE="/dev/$1"
MOUNT_BASE="/media"

# Tunggu device siap
sleep 2

# Ambil label atau gunakan device name
LABEL=$(blkid -s LABEL -o value "$DEVICE" 2>/dev/null)
if [ -z "$LABEL" ]; then
    LABEL=$(basename "$DEVICE")
fi

MOUNT_POINT="$MOUNT_BASE/$LABEL"
mkdir -p "$MOUNT_POINT"

# Cek filesystem dan mount
FSTYPE=$(blkid -s TYPE -o value "$DEVICE" 2>/dev/null)
if [ "$FSTYPE" = "ntfs" ]; then
    mount -t ntfs-3g "$DEVICE" "$MOUNT_POINT" 2>/dev/null || mount "$DEVICE" "$MOUNT_POINT" 2>/dev/null
elif [ "$FSTYPE" = "exfat" ]; then
    mount -t exfat "$DEVICE" "$MOUNT_POINT" 2>/dev/null || mount "$DEVICE" "$MOUNT_POINT" 2>/dev/null
else
    mount "$DEVICE" "$MOUNT_POINT" 2>/dev/null
fi

# Set permission agar bisa ditulis web server
if mountpoint -q "$MOUNT_POINT"; then
    chmod -R 777 "$MOUNT_POINT"
    # Buat folder uploads
    mkdir -p "$MOUNT_POINT/minifs_uploads"
    chmod 777 "$MOUNT_POINT/minifs_uploads"
fi

# Trigger MiniFileServer refresh
touch /mnt/sdcard/storage/.refresh 2>/dev/null || true
SCRIPT

# 4. Buat script umount
cat > /usr/local/bin/usb-umount.sh << 'SCRIPT'
#!/bin/bash
DEVICE="/dev/$1"

# Cari mount point
MOUNT_POINT=$(mount | grep "^$DEVICE " | awk '{print $3}')
if [ -n "$MOUNT_POINT" ]; then
    sync
    umount "$MOUNT_POINT" 2>/dev/null
    rmdir "$MOUNT_POINT" 2>/dev/null
fi
SCRIPT

# 5. Set permission
chmod +x /usr/local/bin/usb-mount.sh
chmod +x /usr/local/bin/usb-umount.sh
udevadm control --reload-rules

echo ""
echo "=== Selesai! ==="
echo ""
echo "Sekarang setiap USB HDD/SSD yang dicolok akan otomatis:"
echo "  - Ter-mount di /media/<label>"
echo "  - Terdeteksi otomatis di MiniFileServer"
echo "  - Siap dipilih sebagai storage di halaman Admin"
echo ""
echo "Coba colokkan HDD/SSD sekarang, lalu refresh halaman MiniFileServer."
