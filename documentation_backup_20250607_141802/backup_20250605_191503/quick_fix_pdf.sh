#!/bin/bash
# Script de correction rapide pour les erreurs PDF

echo "🔧 Correction Rapide - Erreurs PDF AuditDigital"
echo "==============================================="

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Vérifier les privilèges root
if [[ $EUID -ne 0 ]]; then
   print_error "Ce script doit être exécuté en tant que root (sudo)"
   exit 1
fi

DOLIBARR_PATH="/usr/share/dolibarr/htdocs"
MODULE_PATH="$DOLIBARR_PATH/custom/auditdigital"

print_info "=== CORRECTION PDF MODULES ==="

# 1. Corriger pdf_audit_tpe.modules.php
TPE_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_tpe.modules.php"
if [ -f "$TPE_FILE" ]; then
    print_info "Correction du fichier PDF TPE..."
    
    # Backup
    cp "$TPE_FILE" "$TPE_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Remplacer l'inclusion problématique
    sed -i 's|require_once DOL_DOCUMENT_ROOT.*modules_pdf.php.*;|// PDF base class inclusion with fallback\nif (file_exists(DOL_DOCUMENT_ROOT.\"/core/modules/pdf/modules_pdf.php\")) {\n    require_once DOL_DOCUMENT_ROOT.\"/core/modules/pdf/modules_pdf.php\";\n} elseif (file_exists(DOL_DOCUMENT_ROOT.\"/core/class/pdf.class.php\")) {\n    require_once DOL_DOCUMENT_ROOT.\"/core/class/pdf.class.php\";\n}|' "$TPE_FILE"
    
    print_status "PDF TPE corrigé"
else
    print_error "Fichier PDF TPE non trouvé"
fi

# 2. Corriger admin/setup.php si pas encore fait
SETUP_FILE="$MODULE_PATH/admin/setup.php"
if [ -f "$SETUP_FILE" ]; then
    if grep -q "setAsEmailTemplate();" "$SETUP_FILE"; then
        print_info "Correction de admin/setup.php..."
        sed -i 's/->setAsEmailTemplate();/->setAsEmailTemplate("auditdigital");/' "$SETUP_FILE"
        print_status "Setup.php corrigé"
    else
        print_status "Setup.php déjà corrigé"
    fi
fi

# 3. Corriger audit_list.php si pas encore fait
LIST_FILE="$MODULE_PATH/audit_list.php"
if [ -f "$LIST_FILE" ]; then
    if ! grep -q "mode = GETPOST" "$LIST_FILE"; then
        print_info "Correction de audit_list.php..."
        sed -i '/\$action = GETPOST/a\\n// Initialize missing variables\n$mode = GETPOST("mode", "alpha");\n$permissiontoadd = $user->rights->auditdigital->audit->write ?? ($user->id && $user->socid == 0);' "$LIST_FILE"
        print_status "audit_list.php corrigé"
    else
        print_status "audit_list.php déjà corrigé"
    fi
fi

# 4. Corriger les permissions
print_info "Correction des permissions..."
chown -R www-data:www-data "$MODULE_PATH"
chmod -R 644 "$MODULE_PATH"
find "$MODULE_PATH" -type d -exec chmod 755 {} \;

print_status "Permissions corrigées"

# 5. Redémarrer Apache
print_info "Redémarrage d'Apache..."
systemctl restart apache2
print_status "Apache redémarré"

print_info "=== VÉRIFICATION ==="
print_status "Corrections appliquées !"
echo ""
print_info "📋 Tests à effectuer :"
echo "1. http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo "2. http://192.168.1.252/dolibarr/custom/auditdigital/admin/setup.php"
echo ""
print_info "🔍 Surveiller les logs :"
echo "sudo tail -f /var/log/apache2/error.log | grep auditdigital"

exit 0