#!/bin/bash
# Setup WiFi cadangan untuk STB B860H (Armbian)
# Jalankan: bash setup-wifi.sh

set -e

echo "=== Setup WiFi Cadangan ==="
echo ""

# Cek NetworkManager
if ! command -v nmcli &>/dev/null; then
    echo "Menginstall NetworkManager..."
    apt update && apt install -y network-manager
    systemctl enable NetworkManager
    systemctl start NetworkManager
    sleep 2
fi

# Cek interface WiFi
WIFI_IFACE=$(nmcli -t -f TYPE,DEVICE d | grep wifi | cut -d: -f2 | head -1)
if [ -z "$WIFI_IFACE" ]; then
    echo "Tidak ada interface WiFi terdeteksi!"
    echo "Pastikan STB memiliki modul WiFi dan sudah aktif."
    exit 1
fi

echo "Interface WiFi terdeteksi: $WIFI_IFACE"
echo ""

# Minta input SSID
read -p "Nama WiFi (SSID): " SSID
read -s -p "Password WiFi: " PASS
echo ""

if [ -z "$SSID" ]; then
    echo "SSID tidak boleh kosong!"
    exit 1
fi

# Connect
echo ""
echo "Menghubungkan ke '$SSID'..."
nmcli d wifi connect "$SSID" password "$PASS" iface "$WIFI_IFACE" 2>/dev/null || {
    # Coba tanpa password (open network)
    nmcli d wifi connect "$SSID" iface "$WIFI_IFACE" 2>/dev/null || {
        echo "Gagal connect! Cek SSID dan password."
        exit 1
    }
}

# Set auto-connect
nmcli con mod "$SSID" connection.autoconnect yes 2>/dev/null || true

# Prioritaskan: LAN utama, WiFi cadangan
nmcli con mod "$SSID" connection.autoconnect-priority 10 2>/dev/null || true

echo ""
echo "=== WiFi siap! ==="
echo ""

# Tunggu IP
sleep 3
WIFI_IP=$(nmcli -t -f IP4.ADDRESS d show "$WIFI_IFACE" 2>/dev/null | head -1 | cut -d: -f2 | cut -d/ -f1)
if [ -n "$WIFI_IP" ]; then
    echo "IP Via WiFi : $WIFI_IP"
fi
LAN_IP=$(hostname -I | awk '{print $1}')
echo "IP Via LAN  : $LAN_IP"
echo ""
echo "Akses MiniFileServer dari WiFi:"
echo "  http://$WIFI_IP/"
echo "  http://$WIFI_IP/admin.php"
echo ""
echo "Akses MiniFileServer dari LAN:"
echo "  http://$LAN_IP/"
echo ""

# Simpan info ke file
echo "{\"wifi_ssid\":\"$SSID\",\"wifi_ip\":\"$WIFI_IP\",\"lan_ip\":\"$LAN_IP\"}" > /mnt/sdcard/minifileserver/wifi-info.json 2>/dev/null || true

echo "Catatan:"
echo "- Jika LAN dicabut, STB otomatis pindah ke WiFi"
echo "- Jika LAN dicolok lagi, prioritas tetap LAN"
echo "- Untuk ganti WiFi, jalankan ulang: bash setup-wifi.sh"
