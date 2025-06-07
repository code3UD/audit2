#!/bin/bash
# fix_installation_issues.sh
# Script pour corriger les problèmes d'installation AuditDigital

set -e

echo "🔧 Correction des problèmes d'installation AuditDigital"
echo "======================================================="

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
print_info "Détection de l'installation Dolibarr..."

DOLIBARR_PATHS=(
    "/usr/share/dolibarr/htdocs"
    "/var/lib/dolibarr/htdocs" 
    "/usr/dolibarr/htdocs"
    "/opt/dolibarr/htdocs"
    "/var/www/html/dolibarr/htdocs"
    "/var/www/dolibarr/htdocs"
)

DOLIBARR_PATH=""
for path in "${DOLIBARR_PATHS[@]}"; do
    if [ -f "$path/main.inc.php" ]; then
        DOLIBARR_PATH="$path"
        print_status "Dolibarr trouvé : $path"
        break
    fi
done

if [ -z "$DOLIBARR_PATH" ]; then
    print_error "Dolibarr non trouvé. Spécifiez le chemin manuellement :"
    echo "Usage: $0 /chemin/vers/dolibarr/htdocs"
    exit 1
fi

# Utiliser le chemin fourni en paramètre si spécifié
if [ ! -z "$1" ]; then
    DOLIBARR_PATH="$1"
    print_info "Utilisation du chemin spécifié : $DOLIBARR_PATH"
fi

echo ""
print_info "=== PROBLÈME 1 : CORRECTION DES PERMISSIONS ==="

# Trouver le répertoire documents Dolibarr
print_info "Recherche du répertoire documents Dolibarr..."

DOLIBARR_DOCUMENTS_PATHS=(
    "/var/lib/dolibarr/documents"
    "/var/www/documents"
    "/opt/dolibarr/documents"
    "/srv/dolibarr/documents"
    "/home/dolibarr/documents"
    "/usr/share/dolibarr/documents"
)

DOLIBARR_DOCUMENTS=""
for path in "${DOLIBARR_DOCUMENTS_PATHS[@]}"; do
    if [ -d "$path" ]; then
        DOLIBARR_DOCUMENTS="$path"
        print_status "Répertoire documents trouvé : $path"
        break
    fi
done

# Si pas trouvé, chercher dans la configuration
if [ -z "$DOLIBARR_DOCUMENTS" ]; then
    print_info "Recherche dans la configuration Dolibarr..."
    
    # Chercher le fichier conf.php
    CONF_PATHS=(
        "/etc/dolibarr/conf.php"
        "/var/lib/dolibarr/conf.php"
        "$DOLIBARR_PATH/../conf/conf.php"
        "$DOLIBARR_PATH/conf/conf.php"
    )
    
    for conf_path in "${CONF_PATHS[@]}"; do
        if [ -f "$conf_path" ]; then
            print_status "Configuration trouvée : $conf_path"
            
            # Extraire le chemin documents
            doc_path=$(grep "dolibarr_main_data_root" "$conf_path" | cut -d'"' -f2 2>/dev/null || echo "")
            if [ ! -z "$doc_path" ] && [ -d "$doc_path" ]; then
                DOLIBARR_DOCUMENTS="$doc_path"
                print_status "Répertoire documents depuis config : $doc_path"
                break
            fi
        fi
    done
fi

# Créer le répertoire documents si nécessaire
if [ -z "$DOLIBARR_DOCUMENTS" ]; then
    print_warning "Répertoire documents non trouvé, création par défaut..."
    DOLIBARR_DOCUMENTS="/var/lib/dolibarr/documents"
    mkdir -p "$DOLIBARR_DOCUMENTS"
    print_status "Répertoire créé : $DOLIBARR_DOCUMENTS"
fi

# Corriger les permissions du module
print_info "Correction des permissions du module AuditDigital..."
if [ -d "$DOLIBARR_PATH/custom/auditdigital" ]; then
    chown -R www-data:www-data "$DOLIBARR_PATH/custom/auditdigital"
    chmod -R 755 "$DOLIBARR_PATH/custom/auditdigital"
    print_status "Permissions module corrigées"
else
    print_error "Module AuditDigital non trouvé dans $DOLIBARR_PATH/custom/auditdigital"
fi

# Corriger les permissions du répertoire documents
print_info "Correction des permissions du répertoire documents..."
chown -R www-data:www-data "$DOLIBARR_DOCUMENTS"
chmod -R 755 "$DOLIBARR_DOCUMENTS"

# Créer le répertoire spécifique AuditDigital
mkdir -p "$DOLIBARR_DOCUMENTS/auditdigital"
chown -R www-data:www-data "$DOLIBARR_DOCUMENTS/auditdigital"
chmod -R 755 "$DOLIBARR_DOCUMENTS/auditdigital"
print_status "Permissions documents corrigées"

# Corriger les permissions du répertoire custom
print_info "Correction des permissions du répertoire custom..."
chown -R www-data:www-data "$DOLIBARR_PATH/custom"
chmod -R 755 "$DOLIBARR_PATH/custom"
print_status "Permissions custom corrigées"

echo ""
print_info "=== PROBLÈME 2 : ACTIVATION DU MODULE PROJETS ==="

# Chercher la base de données Dolibarr
print_info "Recherche de la configuration base de données..."

DB_HOST="localhost"
DB_NAME=""
DB_USER=""
DB_PASS=""

# Lire la configuration depuis conf.php
for conf_path in "${CONF_PATHS[@]}"; do
    if [ -f "$conf_path" ]; then
        print_status "Lecture configuration : $conf_path"
        
        # Extraire les paramètres de base de données
        DB_HOST=$(grep "dolibarr_main_db_host" "$conf_path" | cut -d'"' -f2 2>/dev/null || echo "localhost")
        DB_NAME=$(grep "dolibarr_main_db_name" "$conf_path" | cut -d'"' -f2 2>/dev/null || echo "")
        DB_USER=$(grep "dolibarr_main_db_user" "$conf_path" | cut -d'"' -f2 2>/dev/null || echo "")
        DB_PASS=$(grep "dolibarr_main_db_pass" "$conf_path" | cut -d'"' -f2 2>/dev/null || echo "")
        
        if [ ! -z "$DB_NAME" ]; then
            print_status "Base de données trouvée : $DB_NAME"
            break
        fi
    fi
done

if [ -z "$DB_NAME" ]; then
    print_error "Configuration base de données non trouvée"
    print_warning "Vous devrez activer manuellement le module Projets dans Dolibarr"
else
    # Activer le module Projets
    print_info "Activation du module Projets..."
    
    # Construire la commande MySQL
    MYSQL_CMD="mysql -h$DB_HOST -u$DB_USER"
    if [ ! -z "$DB_PASS" ]; then
        MYSQL_CMD="$MYSQL_CMD -p$DB_PASS"
    fi
    MYSQL_CMD="$MYSQL_CMD $DB_NAME"
    
    # Vérifier si le module est déjà activé
    MODULE_STATUS=$($MYSQL_CMD -e "SELECT value FROM llx_const WHERE name='MAIN_MODULE_PROJET' LIMIT 1;" 2>/dev/null | tail -n1 || echo "")
    
    if [ "$MODULE_STATUS" = "1" ]; then
        print_status "Module Projets déjà activé"
    else
        # Activer le module Projets
        $MYSQL_CMD -e "INSERT INTO llx_const (name, value, type, entity, visible) VALUES ('MAIN_MODULE_PROJET', '1', 'chaine', 1, 0) ON DUPLICATE KEY UPDATE value='1';" 2>/dev/null
        
        if [ $? -eq 0 ]; then
            print_status "Module Projets activé avec succès"
        else
            print_error "Échec de l'activation automatique du module Projets"
            print_warning "Activez-le manuellement : Configuration → Modules → Projets"
        fi
    fi
fi

echo ""
print_info "=== VÉRIFICATIONS SUPPLÉMENTAIRES ==="

# Vérifier Apache
if systemctl is-active --quiet apache2; then
    print_status "Apache2 actif"
else
    print_warning "Redémarrage d'Apache2..."
    systemctl restart apache2
    print_status "Apache2 redémarré"
fi

# Vérifier les extensions PHP
print_info "Vérification des extensions PHP..."
REQUIRED_EXTENSIONS=("gd" "mysql" "json" "mbstring" "xml" "zip")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        print_status "Extension PHP $ext : OK"
    else
        MISSING_EXTENSIONS+=("php-$ext")
        print_error "Extension PHP $ext : manquante"
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    print_info "Installation des extensions manquantes..."
    apt update
    apt install -y "${MISSING_EXTENSIONS[@]}"
    systemctl restart apache2
    print_status "Extensions installées et Apache redémarré"
fi

# Test final des permissions
print_info "Test final des permissions..."
TEST_FILE="$DOLIBARR_DOCUMENTS/auditdigital/test_write.txt"
if sudo -u www-data touch "$TEST_FILE" 2>/dev/null; then
    rm -f "$TEST_FILE"
    print_status "Test d'écriture : OK"
else
    print_error "Test d'écriture : ÉCHEC"
    print_info "Correction supplémentaire..."
    
    # Permissions plus permissives si nécessaire
    chmod -R 777 "$DOLIBARR_DOCUMENTS/auditdigital"
    
    if sudo -u www-data touch "$TEST_FILE" 2>/dev/null; then
        rm -f "$TEST_FILE"
        print_status "Test d'écriture après correction : OK"
    else
        print_error "Problème de permissions persistant"
    fi
fi

echo ""
echo "============================================================"
print_status "Correction terminée !"
echo "============================================================"
echo ""

print_info "📋 Prochaines étapes :"
echo "1. Actualisez la page d'installation AuditDigital"
echo "2. Cliquez sur 'INSTALL AUDITDIGITAL MODULE'"
echo "3. Vérifiez que tous les tests passent maintenant"
echo ""

print_info "🔗 URLs utiles :"
echo "- Installation : http://votre-dolibarr/custom/auditdigital/install.php"
echo "- Tests : http://votre-dolibarr/custom/auditdigital/test.php"
echo ""

print_info "📁 Chemins configurés :"
echo "- Module : $DOLIBARR_PATH/custom/auditdigital"
echo "- Documents : $DOLIBARR_DOCUMENTS/auditdigital"
echo ""

if [ ! -z "$DB_NAME" ]; then
    print_info "🗄️ Base de données : $DB_NAME sur $DB_HOST"
else
    print_warning "⚠️  Activez manuellement le module Projets dans Dolibarr"
fi

echo ""
print_info "🔧 En cas de problème persistant :"
echo "- Vérifiez les logs : tail -f /var/log/apache2/error.log"
echo "- Relancez ce script : sudo $0"
echo ""

exit 0