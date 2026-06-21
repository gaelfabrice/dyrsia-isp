# MikroTik — Aligner le Hotspot sur bridge-hotspot (192.168.88.0/24)
# Problème : IP 10.0.0.1 sur ether8 + IP 192.168.88.1 sur bridge-hotspot = deux réseaux hotspot mélangés.
# Le WiFi (wlan1) et ether9 sont sur bridge-hotspot mais le serveur /ip hotspot pointe souvent encore vers ether8.
#
# Import : /import file-name=mikrotik-align-hotspot-bridge.rsc
# Puis resynchronisez le hotspot depuis DYRSIA (Settings → Hotspot).

# ===== 1. Vérifier ports du bridge hotspot =====
:put "Ports bridge-hotspot :"
/interface bridge port print where bridge=bridge-hotspot

# ===== 2. Adresse gateway sur bridge-hotspot uniquement =====
/ip address remove [find interface=bridge-hotspot address~"192.168.88."]
/ip address add address=192.168.88.1/24 interface=bridge-hotspot comment="DYRSIA HOTSPOT"

# Retirer l'ancien réseau 10.0.0.x sur ether8 (hotspot migré vers le bridge)
/ip address remove [find interface=ether8 address~"10.0.0."]

# Optionnel : brancher ether8 sur le même LAN hotspot (clients filaires WiFi zone)
:if ([:len [/interface bridge port find bridge=bridge-hotspot interface=ether8]]=0) do={
    /interface bridge port add bridge=bridge-hotspot interface=ether8 comment="DYRSIA HOTSPOT LAN"
}

# ===== 3. Pool DHCP clients hotspot =====
:if ([:len [/ip pool find name=defautl-dhcp]]=0) do={
    /ip pool add name=defautl-dhcp ranges=192.168.88.50-192.168.88.254 comment="DYRSIA HOTSPOT clients"
}

# ===== 4. Réseau hotspot MikroTik =====
/ip hotspot network remove [find address~"10.0.0."]
/ip hotspot network remove [find address~"10.5.50."]
:if ([:len [/ip hotspot network find address=192.168.88.0/24]]=0) do={
    /ip hotspot network add address=192.168.88.0/24 gateway=192.168.88.1 dns-server=192.168.88.1
}

# ===== 5. Serveur hotspot sur bridge-hotspot =====
:if ([:len [/ip hotspot find]]>0) do={
    /ip hotspot set [find] interface=bridge-hotspot address-pool=defautl-dhcp disabled=no
} else={
    :put "ATTENTION : aucun serveur /ip hotspot — créez-le depuis DYRSIA Hotspot Setup"
}

# ===== 6. DHCP standalone : désactiver (le hotspot gère les IP clients) =====
/ip dhcp-server disable [find interface=bridge-hotspot]

# ===== 7. NAT API : proxy vers backend DYRSIA (10.0.0.2:8080) =====
/ip firewall nat remove [find comment="WifiZone hotspot API proxy"]
/ip firewall nat add chain=dstnat action=dst-nat protocol=tcp dst-address=192.168.88.1 dst-port=8080 \
    to-addresses=10.0.0.2 to-ports=8080 comment="WifiZone hotspot API proxy" place-before=0

# ===== 8. Affichage final =====
:put "=== Adresses ==="
/ip address print where interface~"bridge-hotspot|ether8"
:put "=== Hotspot ==="
/ip hotspot print
/ip hotspot network print
:put "=== Terminé — resync DYRSIA avec interface=bridge-hotspot local=192.168.88.1/24 ==="
