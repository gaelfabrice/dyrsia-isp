# MikroTik RB2011 — Nettoyage réseau DYRSIA
# Objectif :
#   bridge-pppoe  → 10.10.10.0/24  (gateway 10.10.10.1, ports ether2-5)
#   bridge-hotspot → 192.168.88.0/24 (gateway 192.168.88.1, ports ether9 + wlan1 + ether8)
#   Pas d'IP 10.0.0.1 sur ether8 : le hotspot vit sur bridge-hotspot (voir mikrotik-align-hotspot-bridge.rsc)
#   Dyrsia-VPN    → 10.0.0.3/24    (tunnel WireGuard)
#
# Import : uploadez sur le routeur puis  /import file-name=mikrotik-cleanup-network.rsc
# Coupure PPPoE ~30 s : les clients CPE se reconnecteront avec une IP du pool.

# ===== 1. PPPoE : une seule gateway sur bridge-pppoe (pas sur lo) =====
/ip address remove [find comment="DYRSIA PPPOE" interface=lo]
/ip address remove [find interface=bridge-pppoe address~"10.10.10.1/"]
:if ([:len [/ip address find interface=bridge-pppoe address~"10.10.10.1/"]]=0) do={
    /ip address add address=10.10.10.1/24 interface=bridge-pppoe comment="DYRSIA PPPOE"
}

# ===== 2. Hotspot : gateway 192.168.88.1 sur bridge-hotspot (pas .5) =====
/ip address remove [find interface=bridge-hotspot address~"192.168.88."]
/ip address add address=192.168.88.1/24 interface=bridge-hotspot comment="DYRSIA HOTSPOT"

# ===== 3. Supprimer le réseau legacy 10.5.50 sur ether9 (conflit avec bridge-hotspot) =====
/ip address remove [find interface=ether9 address~"10.5.50."]

# ===== 4. DHCP : uniquement sur bridge-hotspot (pas sur bridge-pppoe) =====
/ip dhcp-server remove [find name=dhcp-pppoe]
/ip dhcp-server network remove [find address=10.5.50.0/24]
/ip dhcp-server network set [find address=192.168.88.0/24] gateway=192.168.88.1 dns-server=192.168.88.1,8.8.8.8

# ===== 5. Pools inutilisés (legacy 10.5.50) =====
/ip pool remove [find name=hs-pool]
/ip pool remove [find name=hotspot100]

# ===== 6. NAT : retirer les masquerades 10.5.50 dupliquées =====
/ip firewall nat remove [find chain=srcnat src-address=10.5.50.0/24]

# ===== 7. Walled-garden : supprimer WhatsApp =====
/ip hotspot walled-garden ip remove [find dst-host="wa.me"]
/ip hotspot walled-garden ip remove [find dst-host="api.whatsapp.com"]
/ip hotspot walled-garden ip remove [find dst-host="web.whatsapp.com"]
/ip hotspot walled-garden ip remove [find dst-host~"whatsapp"]
/ip hotspot walled-garden remove [find dst-host="wa.me"]
/ip hotspot walled-garden remove [find dst-host~"whatsapp"]

# Doublons campay : resynchronisez le hotspot depuis DYRSIA après import si besoin.

# ===== 8. Forcer reconnexion PPPoE (IP pool correcte après fix lo) =====
/ppp active remove [find name=romuald]

# ===== 9. Vérification (affichage, n'exécute rien) =====
:put "=== Adresses IP ==="
/ip address print where interface~"bridge|ether8|Dyrsia"
:put "=== Pools actifs ==="
/ip pool print
:put "=== Walled-garden WhatsApp restant ==="
/ip hotspot walled-garden ip print where dst-host~"whatsapp|wa.me"
:put "=== Nettoyage terminé ==="
