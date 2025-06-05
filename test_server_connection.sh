#!/bin/bash
# Test de connectivité avec le serveur

echo "🌐 TEST DE CONNECTIVITÉ SERVEUR"
echo "==============================="

SERVER_IP="192.168.1.252"
SERVER_USER="root"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Test 1: Ping
print_info "Test 1: Connectivité réseau..."
if ping -c 3 "$SERVER_IP" > /dev/null 2>&1; then
    print_status "Serveur $SERVER_IP accessible"
else
    print_error "Serveur $SERVER_IP non accessible"
    print_info "Vérifiez :"
    echo "- L'adresse IP du serveur"
    echo "- La connectivité réseau"
    echo "- Le firewall"
    exit 1
fi

# Test 2: SSH
print_info "Test 2: Connexion SSH..."
if timeout 10 ssh -o ConnectTimeout=5 -o BatchMode=yes "$SERVER_USER@$SERVER_IP" exit 2>/dev/null; then
    print_status "Connexion SSH réussie"
else
    print_warning "Connexion SSH échouée"
    print_info "Solutions possibles :"
    echo "1. Configurer les clés SSH :"
    echo "   ssh-keygen -t rsa"
    echo "   ssh-copy-id $SERVER_USER@$SERVER_IP"
    echo ""
    echo "2. Ou utiliser le mot de passe :"
    echo "   ssh $SERVER_USER@$SERVER_IP"
    echo ""
    echo "3. Vérifier le service SSH :"
    echo "   sudo systemctl status ssh"
fi

# Test 3: HTTP
print_info "Test 3: Service web..."
if curl -s -o /dev/null -w "%{http_code}" "http://$SERVER_IP" | grep -q "200\|301\|302"; then
    print_status "Service web accessible"
else
    print_warning "Service web non accessible"
    print_info "Vérifiez Apache/Nginx sur le serveur"
fi

# Test 4: Dolibarr
print_info "Test 4: Dolibarr..."
if curl -s "http://$SERVER_IP/dolibarr/" | grep -q -i "dolibarr\|login"; then
    print_status "Dolibarr accessible"
else
    print_warning "Dolibarr non accessible"
    print_info "URL à vérifier : http://$SERVER_IP/dolibarr/"
fi

# Test 5: Module existant
print_info "Test 5: Module AuditDigital existant..."
if timeout 10 ssh -o ConnectTimeout=5 -o BatchMode=yes "$SERVER_USER@$SERVER_IP" "test -d /usr/share/dolibarr/htdocs/custom/auditdigital" 2>/dev/null; then
    print_warning "Module AuditDigital déjà présent"
    print_info "Il sera sauvegardé avant le déploiement"
else
    print_status "Emplacement libre pour le nouveau module"
fi

print_info "\n=== RÉSUMÉ ==="

# Générer les commandes de déploiement
cat << EOF

🚀 COMMANDES DE DÉPLOIEMENT :

1. Déploiement automatique :
   ./deploy_to_server.sh

2. Déploiement manuel :
   # Copier le module
   scp -r htdocs/custom/auditdigital $SERVER_USER@$SERVER_IP:/usr/share/dolibarr/htdocs/custom/
   
   # Copier le script de correction
   scp fix_wizard_final.sh $SERVER_USER@$SERVER_IP:/tmp/
   
   # Se connecter et appliquer
   ssh $SERVER_USER@$SERVER_IP
   chmod +x /tmp/fix_wizard_final.sh
   sudo /tmp/fix_wizard_final.sh

3. Test du wizard :
   http://$SERVER_IP/dolibarr/custom/auditdigital/wizard/index.php

EOF

print_info "📋 Consultez GUIDE_TEST_WIZARD.md pour les détails des tests"

exit 0