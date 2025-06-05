#!/bin/bash
# Déploiement Git direct sur le serveur

echo "🚀 DÉPLOIEMENT GIT AUDITDIGITAL"
echo "==============================="

SERVER_IP="192.168.1.252"
SERVER_USER="root"
DOLIBARR_PATH="/usr/share/dolibarr/htdocs/custom"
MODULE_NAME="auditdigital"

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

# Test de connectivité
print_info "Test de connectivité..."
if ! ping -c 1 "$SERVER_IP" > /dev/null 2>&1; then
    print_error "Serveur non accessible"
    exit 1
fi

print_status "Serveur accessible"

# Déploiement sur le serveur
print_info "Déploiement sur le serveur..."

ssh "$SERVER_USER@$SERVER_IP" << EOSSH
echo "🔧 DÉPLOIEMENT SUR LE SERVEUR"
echo "============================="

cd "$DOLIBARR_PATH"

# Sauvegarde si le module existe
if [ -d "$MODULE_NAME" ]; then
    echo "Sauvegarde de l'ancien module..."
    mv "$MODULE_NAME" "${MODULE_NAME}.backup.\$(date +%Y%m%d_%H%M%S)"
fi

# Cloner ou mettre à jour le repository
if [ ! -d "${MODULE_NAME}.git" ]; then
    echo "Clonage initial du repository..."
    git clone https://github.com/code2UD/audit2.git "${MODULE_NAME}.git"
else
    echo "Mise à jour du repository..."
    cd "${MODULE_NAME}.git"
    git pull origin main
    cd ..
fi

# Copier les fichiers du module (sans .git, docs, scripts)
echo "Copie des fichiers du module..."
rsync -av --exclude='.git' --exclude='docs' --exclude='scripts' --exclude='backup_*' "${MODULE_NAME}.git/" "$MODULE_NAME/"

# Appliquer les corrections
echo "Application des corrections..."
chown -R www-data:www-data "$MODULE_NAME"
chmod -R 644 "$MODULE_NAME"
find "$MODULE_NAME" -type d -exec chmod 755 {} \;

# Créer les répertoires nécessaires
mkdir -p /var/lib/dolibarr/documents/auditdigital
chown -R www-data:www-data /var/lib/dolibarr/documents/auditdigital
chmod -R 755 /var/lib/dolibarr/documents/auditdigital

# Redémarrer Apache
systemctl restart apache2

echo ""
echo "✅ DÉPLOIEMENT TERMINÉ !"
echo "🧪 Test: http://$SERVER_IP/dolibarr/custom/auditdigital/wizard/index.php"

EOSSH

if [ $? -eq 0 ]; then
    print_status "Déploiement réussi !"
    echo ""
    print_info "🧪 TESTEZ MAINTENANT :"
    echo "http://$SERVER_IP/dolibarr/custom/auditdigital/wizard/index.php"
else
    print_error "Erreur lors du déploiement"
    exit 1
fi
