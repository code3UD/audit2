#!/bin/bash
# Script de déploiement rapide vers le serveur

echo "🚀 DÉPLOIEMENT AUDITDIGITAL VERS SERVEUR"
echo "========================================"

# Configuration
SERVER_IP="192.168.1.252"
SERVER_USER="root"  # Ajustez selon votre configuration
DOLIBARR_PATH="/usr/share/dolibarr/htdocs"
MODULE_PATH="$DOLIBARR_PATH/custom/auditdigital"

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

# Vérifier la connectivité
print_info "Test de connectivité vers $SERVER_IP..."
if ping -c 1 "$SERVER_IP" > /dev/null 2>&1; then
    print_status "Serveur accessible"
else
    print_error "Serveur non accessible. Vérifiez l'IP et la connectivité."
    exit 1
fi

# Créer l'archive du module
print_info "Création de l'archive du module..."
cd "$(dirname "$0")"
tar -czf auditdigital_corrected.tar.gz -C htdocs/custom auditdigital/
print_status "Archive créée : auditdigital_corrected.tar.gz"

# Copier vers le serveur
print_info "Copie vers le serveur..."
if scp auditdigital_corrected.tar.gz "$SERVER_USER@$SERVER_IP:/tmp/"; then
    print_status "Archive copiée sur le serveur"
else
    print_error "Échec de la copie. Vérifiez les permissions SSH."
    exit 1
fi

# Copier le script de correction
if scp fix_wizard_final.sh "$SERVER_USER@$SERVER_IP:/tmp/"; then
    print_status "Script de correction copié"
else
    print_warning "Échec de la copie du script de correction"
fi

# Exécuter le déploiement sur le serveur
print_info "Déploiement sur le serveur..."
ssh "$SERVER_USER@$SERVER_IP" << 'EOF'
echo "🔧 DÉPLOIEMENT SUR LE SERVEUR"
echo "============================="

# Backup de l'ancien module
if [ -d "/usr/share/dolibarr/htdocs/custom/auditdigital" ]; then
    echo "Sauvegarde de l'ancien module..."
    mv /usr/share/dolibarr/htdocs/custom/auditdigital /usr/share/dolibarr/htdocs/custom/auditdigital.backup.$(date +%Y%m%d_%H%M%S)
    echo "✅ Sauvegarde effectuée"
fi

# Extraire le nouveau module
echo "Extraction du nouveau module..."
cd /usr/share/dolibarr/htdocs/custom/
tar -xzf /tmp/auditdigital_corrected.tar.gz
echo "✅ Module extrait"

# Appliquer les corrections
if [ -f "/tmp/fix_wizard_final.sh" ]; then
    echo "Application des corrections..."
    chmod +x /tmp/fix_wizard_final.sh
    /tmp/fix_wizard_final.sh
else
    echo "⚠️  Script de correction non trouvé, application manuelle..."
    
    # Corrections manuelles de base
    chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital
    chmod -R 644 /usr/share/dolibarr/htdocs/custom/auditdigital
    find /usr/share/dolibarr/htdocs/custom/auditdigital -type d -exec chmod 755 {} \;
    
    # Créer le répertoire documents
    mkdir -p /var/lib/dolibarr/documents/auditdigital
    chown -R www-data:www-data /var/lib/dolibarr/documents/auditdigital
    chmod -R 755 /var/lib/dolibarr/documents/auditdigital
    
    # Redémarrer Apache
    systemctl restart apache2
    
    echo "✅ Corrections de base appliquées"
fi

echo ""
echo "🎯 DÉPLOIEMENT TERMINÉ !"
echo ""
echo "📋 TESTS À EFFECTUER :"
echo "1. http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo "2. http://192.168.1.252/dolibarr/custom/auditdigital/admin/setup.php"
echo ""
echo "🔍 SURVEILLANCE DES LOGS :"
echo "tail -f /var/log/apache2/error.log | grep auditdigital"

EOF

if [ $? -eq 0 ]; then
    print_status "Déploiement réussi !"
else
    print_error "Erreur lors du déploiement"
    exit 1
fi

# Nettoyer les fichiers temporaires
rm -f auditdigital_corrected.tar.gz

print_info "=== DÉPLOIEMENT TERMINÉ ==="
print_status "🎯 Module déployé avec succès !"
echo ""
print_info "📋 PROCHAINES ÉTAPES :"
echo "1. Testez le wizard : http://$SERVER_IP/dolibarr/custom/auditdigital/wizard/index.php"
echo "2. Créez un audit de test"
echo "3. Vérifiez la génération PDF"
echo "4. Surveillez les logs d'erreur"
echo ""
print_info "🔧 En cas de problème :"
echo "ssh $SERVER_USER@$SERVER_IP"
echo "tail -f /var/log/apache2/error.log | grep auditdigital"

exit 0