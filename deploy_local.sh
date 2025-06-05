#!/bin/bash
# Déploiement local sur le serveur (vous êtes déjà sur le serveur)

echo "🚀 DÉPLOIEMENT LOCAL AUDITDIGITAL"
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
REPO_URL="https://github.com/code2UD/audit2.git"

print_info "=== VÉRIFICATION DES PERMISSIONS ==="

# Vérifier qu'on peut utiliser sudo
if ! sudo -n true 2>/dev/null; then
    print_info "Saisie du mot de passe sudo nécessaire..."
fi

print_info "=== PRÉPARATION ==="

# Aller dans le répertoire custom de Dolibarr
cd "$DOLIBARR_PATH" || {
    print_error "Impossible d'accéder à $DOLIBARR_PATH"
    exit 1
}

print_status "Répertoire de travail : $(pwd)"

print_info "=== SAUVEGARDE ==="

# Sauvegarder l'ancien module s'il existe
if [ -d "$MODULE_NAME" ]; then
    BACKUP_NAME="${MODULE_NAME}.backup.$(date +%Y%m%d_%H%M%S)"
    print_info "Sauvegarde de l'ancien module..."
    sudo mv "$MODULE_NAME" "$BACKUP_NAME"
    print_status "Ancien module sauvegardé : $BACKUP_NAME"
fi

print_info "=== CLONAGE DU REPOSITORY ==="

# Cloner le repository avec le bon nom
if [ -d "${MODULE_NAME}.git" ]; then
    print_info "Mise à jour du repository existant..."
    cd "${MODULE_NAME}.git"
    sudo git pull origin main
    cd ..
else
    print_info "Clonage initial du repository..."
    sudo git clone "$REPO_URL" "${MODULE_NAME}.git"
fi

print_status "Repository cloné/mis à jour"

print_info "=== COPIE DES FICHIERS DU MODULE ==="

# Créer le répertoire du module
sudo mkdir -p "$MODULE_NAME"

# Copier les fichiers du module (exclure .git, docs, scripts, backup)
print_info "Copie des fichiers du module..."
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

print_status "Fichiers du module copiés"

print_info "=== CORRECTION DES PERMISSIONS ==="

# Appliquer les bonnes permissions
sudo chown -R www-data:www-data "$MODULE_NAME"
sudo chmod -R 644 "$MODULE_NAME"
sudo find "$MODULE_NAME" -type d -exec chmod 755 {} \;

print_status "Permissions corrigées"

print_info "=== CRÉATION DES RÉPERTOIRES DOCUMENTS ==="

# Créer le répertoire documents
DOCS_PATH="/var/lib/dolibarr/documents/auditdigital"
sudo mkdir -p "$DOCS_PATH"
sudo chown -R www-data:www-data "$DOCS_PATH"
sudo chmod -R 755 "$DOCS_PATH"

print_status "Répertoire documents créé : $DOCS_PATH"

print_info "=== REDÉMARRAGE D'APACHE ==="

# Redémarrer Apache
sudo systemctl restart apache2
print_status "Apache redémarré"

print_info "=== VÉRIFICATION ==="

# Vérifier la structure
print_info "Structure du module :"
ls -la "$MODULE_NAME/" | head -10

print_info "=== RÉSULTAT ==="

print_status "🎉 DÉPLOIEMENT TERMINÉ AVEC SUCCÈS !"
echo ""
print_info "📋 INFORMATIONS :"
echo "- Module installé dans : $DOLIBARR_PATH/$MODULE_NAME"
echo "- Documents dans : $DOCS_PATH"
echo "- Repository Git dans : $DOLIBARR_PATH/${MODULE_NAME}.git"
echo ""
print_info "🧪 TESTEZ MAINTENANT :"
echo "http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo ""
print_info "🔍 SURVEILLANCE DES LOGS :"
echo "sudo tail -f /var/log/apache2/error.log | grep auditdigital"

exit 0