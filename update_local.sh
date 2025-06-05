#!/bin/bash
# Mise à jour locale rapide du module

echo "⚡ MISE À JOUR LOCALE AUDITDIGITAL"
echo "================================="

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

# Configuration
DOLIBARR_PATH="/usr/share/dolibarr/htdocs/custom"
MODULE_NAME="auditdigital"

print_info "=== MISE À JOUR DU REPOSITORY ==="

cd "$DOLIBARR_PATH/${MODULE_NAME}.git" || {
    print_error "Repository Git non trouvé. Utilisez deploy_local.sh d'abord."
    exit 1
}

# Mettre à jour le repository
sudo git pull origin main
print_status "Repository mis à jour"

print_info "=== SYNCHRONISATION DES FICHIERS ==="

cd "$DOLIBARR_PATH"

# Synchroniser les fichiers
sudo rsync -av \
    --exclude='.git' \
    --exclude='docs' \
    --exclude='scripts' \
    --exclude='backup_*' \
    --exclude='*.md' \
    --exclude='deploy_*.sh' \
    --exclude='update_*.sh' \
    --exclude='validate_*.sh' \
    --exclude='reorganize_*.sh' \
    --exclude='final_check.sh' \
    --exclude='test_server_connection.sh' \
    --exclude='status_report.txt' \
    "${MODULE_NAME}.git/" "$MODULE_NAME/"

print_status "Fichiers synchronisés"

print_info "=== CORRECTION DES PERMISSIONS ==="

# Appliquer les permissions
sudo chown -R www-data:www-data "$MODULE_NAME"
sudo chmod -R 644 "$MODULE_NAME"
sudo find "$MODULE_NAME" -type d -exec chmod 755 {} \;

print_status "Permissions corrigées"

print_info "=== REDÉMARRAGE D'APACHE ==="

sudo systemctl restart apache2
print_status "Apache redémarré"

print_status "🎉 MISE À JOUR TERMINÉE !"
echo ""
print_info "🧪 TESTEZ :"
echo "http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"

exit 0