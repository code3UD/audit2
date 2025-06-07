#!/bin/bash
# Script de correction finale pour tous les problèmes AuditDigital

echo "🔧 Correction Finale - Tous les Problèmes AuditDigital"
echo "====================================================="

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

print_info "=== CORRECTION FINALE ==="

# 1. Corriger mod_audit_standard.php
MOD_FILE="$MODULE_PATH/core/modules/auditdigital/mod_audit_standard.php"
if [ -f "$MOD_FILE" ]; then
    print_info "Correction de mod_audit_standard.php..."
    
    # Backup
    cp "$MOD_FILE" "$MOD_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Corriger le chemin d'inclusion
    sed -i 's|/core/modules/auditdigital/modules_audit.php|/custom/auditdigital/core/modules/auditdigital/modules_audit.php|' "$MOD_FILE"
    
    print_status "mod_audit_standard.php corrigé"
else
    print_error "Fichier mod_audit_standard.php non trouvé"
fi

# 2. Corriger pdf_audit_tpe.modules.php
TPE_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_tpe.modules.php"
if [ -f "$TPE_FILE" ]; then
    print_info "Correction de pdf_audit_tpe.modules.php..."
    
    # Backup
    cp "$TPE_FILE" "$TPE_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Ajouter la propriété scandir si elle n'existe pas
    if ! grep -q "public \$scandir;" "$TPE_FILE"; then
        sed -i '/public \$marge_basse;/a\        public $scandir;' "$TPE_FILE"
    fi
    
    # Ajouter l'initialisation de scandir si elle n'existe pas
    if ! grep -q "this->scandir" "$TPE_FILE"; then
        sed -i '/this->marge_basse = 10;/a\            $this->scandir = DOL_DOCUMENT_ROOT."/custom/auditdigital/core/modules/auditdigital/doc/";' "$TPE_FILE"
    fi
    
    print_status "pdf_audit_tpe.modules.php corrigé"
else
    print_error "Fichier pdf_audit_tpe.modules.php non trouvé"
fi

# 3. Corriger pdf_audit_collectivite.modules.php
COLL_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php"
if [ -f "$COLL_FILE" ]; then
    print_info "Correction de pdf_audit_collectivite.modules.php..."
    
    # Backup
    cp "$COLL_FILE" "$COLL_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Ajouter la propriété scandir si elle n'existe pas
    if ! grep -q "public \$scandir;" "$COLL_FILE"; then
        sed -i '/public \$marge_basse;/a\    public $scandir;' "$COLL_FILE"
    fi
    
    # Ajouter l'initialisation de scandir si elle n'existe pas
    if ! grep -q "this->scandir" "$COLL_FILE"; then
        sed -i '/this->marge_basse = isset/a\        $this->scandir = DOL_DOCUMENT_ROOT."/custom/auditdigital/core/modules/auditdigital/doc/";' "$COLL_FILE"
    fi
    
    print_status "pdf_audit_collectivite.modules.php corrigé"
else
    print_error "Fichier pdf_audit_collectivite.modules.php non trouvé"
fi

# 4. Corriger admin/setup.php si pas encore fait
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

# 5. Corriger audit_list.php si pas encore fait
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

# 6. Corriger wizard/index.php si pas encore fait
WIZARD_FILE="$MODULE_PATH/wizard/index.php"
if [ -f "$WIZARD_FILE" ]; then
    if ! grep -q "FormProjets" "$WIZARD_FILE"; then
        print_info "Correction de wizard/index.php..."
        
        # Ajouter la gestion robuste de FormProjets
        cat >> "$WIZARD_FILE.tmp" << 'EOF'
// Enhanced FormProjets handling with fallback
$formproject = null;
if (file_exists(DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php')) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
    if (class_exists('FormProjets')) {
        $formproject = new FormProjets($db);
    }
} elseif (file_exists(DOL_DOCUMENT_ROOT.'/core/class/html.formproject.class.php')) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formproject.class.php';
    if (class_exists('FormProject')) {
        $formproject = new FormProject($db);
    }
}
EOF
        
        # Remplacer le contenu
        head -n 50 "$WIZARD_FILE" > "$WIZARD_FILE.new"
        cat "$WIZARD_FILE.tmp" >> "$WIZARD_FILE.new"
        tail -n +51 "$WIZARD_FILE" >> "$WIZARD_FILE.new"
        mv "$WIZARD_FILE.new" "$WIZARD_FILE"
        rm "$WIZARD_FILE.tmp"
        
        print_status "wizard/index.php corrigé"
    else
        print_status "wizard/index.php déjà corrigé"
    fi
fi

# 7. Corriger les permissions
print_info "Correction des permissions..."
chown -R www-data:www-data "$MODULE_PATH"
chmod -R 644 "$MODULE_PATH"
find "$MODULE_PATH" -type d -exec chmod 755 {} \;

print_status "Permissions corrigées"

# 8. Redémarrer Apache
print_info "Redémarrage d'Apache..."
systemctl restart apache2
print_status "Apache redémarré"

print_info "=== VÉRIFICATION ==="
print_status "Toutes les corrections appliquées !"
echo ""
print_info "📋 Tests à effectuer :"
echo "1. http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo "2. http://192.168.1.252/dolibarr/custom/auditdigital/admin/setup.php"
echo "3. Créer un nouvel audit depuis le wizard"
echo ""
print_info "🔍 Surveiller les logs :"
echo "sudo tail -f /var/log/apache2/error.log | grep auditdigital"

exit 0