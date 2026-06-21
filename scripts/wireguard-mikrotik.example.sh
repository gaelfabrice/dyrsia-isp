#!/usr/bin/env bash
# Exemple WireGuard : tunnel VPS (Render/cloud) ↔ MikroTik
# Adapter les IP et clés avant déploiement.
#
# Sur le VPS (Ubuntu) :
#   apt install wireguard
#   wg genkey | tee privatekey | wg pubkey > publickey
#
# Sur MikroTik (Terminal) :
#   /interface wireguard add name=wg-dyrsia listen-port=13231
#   /interface wireguard peers add interface=wg-dyrsia \
#     public-key="<VPS_PUBLIC_KEY>" \
#     endpoint-address=<VPS_IP> endpoint-port=51820 \
#     allowed-address=10.88.0.1/32 persistent-keepalive=25s
#
# DYRSIA : configurer tbl_routers.ip_address = 10.88.0.2:8728 (IP tunnel MikroTik)

set -euo pipefail

echo "=== WireGuard DYRSIA — guide rapide ==="
echo "1. VPS : wg0 = 10.88.0.1/24, port 51820"
echo "2. MikroTik : wg-dyrsia = 10.88.0.2/24"
echo "3. Firewall MikroTik : autoriser 10.88.0.1 → API 8728"
echo "4. Tester depuis VPS : php mtik_diag.php"
echo ""
echo "Voir docs/integration-stack.md pour la matrice complète."
