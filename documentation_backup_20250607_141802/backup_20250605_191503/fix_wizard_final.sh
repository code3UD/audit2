#!/bin/bash
# Script de correction finale pour AuditDigital - Résolution des erreurs critiques

echo "🚨 CORRECTION FINALE - AUDITDIGITAL WIZARD"
echo "=========================================="

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

# Détecter le chemin Dolibarr
DOLIBARR_PATHS=(
    "/usr/share/dolibarr/htdocs"
    "/var/lib/dolibarr/htdocs"
    "/usr/dolibarr/htdocs"
    "/opt/dolibarr/htdocs"
    "/var/www/html/dolibarr/htdocs"
)

DOLIBARR_PATH=""
for path in "${DOLIBARR_PATHS[@]}"; do
    if [ -f "$path/main.inc.php" ]; then
        DOLIBARR_PATH="$path"
        break
    fi
done

if [ -z "$DOLIBARR_PATH" ]; then
    print_error "Installation Dolibarr non trouvée. Spécifiez le chemin :"
    echo "Usage: $0 [chemin_vers_dolibarr_htdocs]"
    exit 1
fi

MODULE_PATH="$DOLIBARR_PATH/custom/auditdigital"
print_info "Dolibarr détecté : $DOLIBARR_PATH"
print_info "Module path : $MODULE_PATH"

if [ ! -d "$MODULE_PATH" ]; then
    print_error "Module AuditDigital non trouvé dans $MODULE_PATH"
    exit 1
fi

print_info "=== CORRECTION 1: CLASSE DUPLIQUÉE ==="

# 1. Supprimer la classe ModelePDFAudit dupliquée de modules_audit.php
MODULES_FILE="$MODULE_PATH/core/modules/auditdigital/modules_audit.php"
if [ -f "$MODULES_FILE" ]; then
    print_info "Correction de modules_audit.php..."
    
    # Backup
    cp "$MODULES_FILE" "$MODULES_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Créer une version corrigée sans la classe ModelePDFAudit
    cat > "$MODULES_FILE.tmp" << 'EOF'
<?php
/* Copyright (C) 2024 Up Digit Agency
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       core/modules/auditdigital/modules_audit.php
 * \ingroup    auditdigital
 * \brief      File that contains parent class for audit numbering models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';

/**
 * Parent class of audit numbering templates
 */
abstract class ModeleNumRefAudit extends CommonNumRefGenerator
{
    // No overload code
}
?>
EOF
    
    mv "$MODULES_FILE.tmp" "$MODULES_FILE"
    print_status "Classe dupliquée supprimée de modules_audit.php"
else
    print_error "Fichier modules_audit.php non trouvé"
fi

print_info "=== CORRECTION 2: PDF TPE AVEC SCANDIR ==="

# 2. Corriger pdf_audit_tpe.modules.php
TPE_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_tpe.modules.php"
if [ -f "$TPE_FILE" ]; then
    print_info "Correction de pdf_audit_tpe.modules.php..."
    
    # Backup
    cp "$TPE_FILE" "$TPE_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Vérifier si scandir est présent
    if ! grep -q "public \$scandir" "$TPE_FILE"; then
        print_warning "Propriété scandir manquante, ajout en cours..."
        
        # Ajouter scandir après marge_basse dans la classe principale
        sed -i '/public $marge_basse;/a\    public $scandir;' "$TPE_FILE"
        
        # Ajouter l'initialisation dans le constructeur
        sed -i '/this->marge_basse = /a\        $this->scandir = DOL_DOCUMENT_ROOT."/custom/auditdigital/core/modules/auditdigital/doc/";' "$TPE_FILE"
        
        print_status "Propriété scandir ajoutée à pdf_audit_tpe.modules.php"
    else
        print_status "Propriété scandir déjà présente dans pdf_audit_tpe.modules.php"
    fi
else
    print_error "Fichier pdf_audit_tpe.modules.php non trouvé"
fi

print_info "=== CORRECTION 3: PDF COLLECTIVITÉ AVEC SCANDIR ==="

# 3. Corriger pdf_audit_collectivite.modules.php
COLL_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php"
if [ -f "$COLL_FILE" ]; then
    print_info "Correction de pdf_audit_collectivite.modules.php..."
    
    # Backup
    cp "$COLL_FILE" "$COLL_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Vérifier si scandir est présent
    if ! grep -q "public \$scandir" "$COLL_FILE"; then
        print_warning "Propriété scandir manquante, ajout en cours..."
        
        # Ajouter scandir après marge_basse
        sed -i '/public $marge_basse;/a\    public $scandir;' "$COLL_FILE"
        
        # Ajouter l'initialisation dans le constructeur
        sed -i '/this->marge_basse = /a\        $this->scandir = DOL_DOCUMENT_ROOT."/custom/auditdigital/core/modules/auditdigital/doc/";' "$COLL_FILE"
        
        print_status "Propriété scandir ajoutée à pdf_audit_collectivite.modules.php"
    else
        print_status "Propriété scandir déjà présente dans pdf_audit_collectivite.modules.php"
    fi
else
    print_error "Fichier pdf_audit_collectivite.modules.php non trouvé"
fi

print_info "=== CORRECTION 4: WIZARD INDEX.PHP ==="

# 4. Vérifier et corriger le wizard
WIZARD_FILE="$MODULE_PATH/wizard/index.php"
if [ -f "$WIZARD_FILE" ]; then
    print_info "Vérification du wizard..."
    
    # Vérifier la syntaxe PHP
    if php -l "$WIZARD_FILE" > /dev/null 2>&1; then
        print_status "Syntaxe PHP correcte dans wizard/index.php"
    else
        print_error "Erreur de syntaxe dans wizard/index.php"
        php -l "$WIZARD_FILE"
    fi
else
    print_error "Fichier wizard/index.php non trouvé"
fi

print_info "=== CORRECTION 5: PERMISSIONS ==="

# 5. Corriger les permissions
print_info "Correction des permissions..."
chown -R www-data:www-data "$MODULE_PATH"
chmod -R 644 "$MODULE_PATH"
find "$MODULE_PATH" -type d -exec chmod 755 {} \;
find "$MODULE_PATH" -name "*.php" -exec chmod 644 {} \;

print_status "Permissions corrigées"

print_info "=== CORRECTION 6: RÉPERTOIRES DOCUMENTS ==="

# 6. Créer les répertoires de documents
DOCS_PATH="/var/lib/dolibarr/documents/auditdigital"
if [ ! -d "$DOCS_PATH" ]; then
    mkdir -p "$DOCS_PATH"
    chown -R www-data:www-data "$DOCS_PATH"
    chmod -R 755 "$DOCS_PATH"
    print_status "Répertoire documents créé : $DOCS_PATH"
else
    print_status "Répertoire documents existe : $DOCS_PATH"
fi

print_info "=== CORRECTION 7: REDÉMARRAGE SERVICES ==="

# 7. Redémarrer les services
print_info "Redémarrage d'Apache..."
if systemctl restart apache2; then
    print_status "Apache redémarré avec succès"
else
    print_warning "Échec du redémarrage d'Apache (normal si pas systemd)"
fi

# Redémarrer PHP-FPM si présent
if systemctl is-active --quiet php8.1-fpm; then
    systemctl restart php8.1-fpm
    print_status "PHP-FPM redémarré"
elif systemctl is-active --quiet php7.4-fpm; then
    systemctl restart php7.4-fpm
    print_status "PHP-FPM redémarré"
fi

print_info "=== VÉRIFICATION FINALE ==="

# 8. Vérifications finales
print_info "Vérification des fichiers critiques..."

CRITICAL_FILES=(
    "$MODULE_PATH/class/audit.class.php"
    "$MODULE_PATH/core/modules/auditdigital/modules_audit.php"
    "$MODULE_PATH/core/modules/auditdigital/mod_audit_standard.php"
    "$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_tpe.modules.php"
    "$MODULE_PATH/wizard/index.php"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "✓ $(basename "$file")"
    else
        print_error "✗ $(basename "$file") MANQUANT"
    fi
done

print_info "=== RÉSULTAT ==="
print_status "🎯 CORRECTION FINALE TERMINÉE !"
echo ""
print_info "📋 TESTS À EFFECTUER :"
echo "1. http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo "2. http://192.168.1.252/dolibarr/custom/auditdigital/admin/setup.php"
echo ""
print_info "🔍 SURVEILLANCE DES LOGS :"
echo "sudo tail -f /var/log/apache2/error.log | grep auditdigital"
echo ""
print_info "🚀 Si le wizard fonctionne, créez un audit de test !"

exit 0