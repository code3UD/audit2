#!/bin/bash
# install_auditdigital_ubuntu.sh
# Script d'installation automatique pour Ubuntu 22.04

set -e

echo "🚀 Installation AuditDigital pour Dolibarr sur Ubuntu 22.04"
echo "============================================================"

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction d'affichage coloré
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Vérifier les privilèges root
if [[ $EUID -ne 0 ]]; then
   print_error "Ce script doit être exécuté en tant que root (sudo)"
   exit 1
fi

echo ""
print_info "Détection de l'installation Dolibarr..."

# Chemins possibles pour Dolibarr sur Ubuntu
DOLIBARR_PATHS=(
    "/usr/share/dolibarr/htdocs"
    "/var/lib/dolibarr/htdocs" 
    "/usr/dolibarr/htdocs"
    "/opt/dolibarr/htdocs"
    "/var/www/html/dolibarr/htdocs"
    "/var/www/dolibarr/htdocs"
    "/srv/dolibarr/htdocs"
)

DOLIBARR_PATH=""

# Utiliser le chemin fourni en paramètre si spécifié
if [ ! -z "$1" ]; then
    if [ -f "$1/main.inc.php" ]; then
        DOLIBARR_PATH="$1"
        print_status "Utilisation du chemin spécifié : $DOLIBARR_PATH"
    else
        print_error "Le chemin spécifié ne contient pas main.inc.php : $1"
        exit 1
    fi
else
    # Détecter automatiquement le chemin
    for path in "${DOLIBARR_PATHS[@]}"; do
        if [ -f "$path/main.inc.php" ]; then
            DOLIBARR_PATH="$path"
            print_status "Dolibarr détecté automatiquement : $path"
            break
        fi
    done
fi

if [ -z "$DOLIBARR_PATH" ]; then
    print_error "Dolibarr non trouvé automatiquement."
    echo ""
    echo "Chemins testés :"
    for path in "${DOLIBARR_PATHS[@]}"; do
        echo "  - $path"
    done
    echo ""
    echo "Usage: $0 /chemin/vers/dolibarr/htdocs"
    echo "Exemple: $0 /usr/share/dolibarr/htdocs"
    exit 1
fi

# Vérifier les prérequis
print_info "Vérification des prérequis..."

# Vérifier PHP
if ! command -v php &> /dev/null; then
    print_error "PHP n'est pas installé"
    exit 1
fi

PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
print_status "PHP version : $PHP_VERSION"

# Vérifier les extensions PHP requises
REQUIRED_EXTENSIONS=("gd" "mysql" "json" "mbstring")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "^$ext$"; then
        MISSING_EXTENSIONS+=("php-$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    print_warning "Extensions PHP manquantes : ${MISSING_EXTENSIONS[*]}"
    print_info "Installation des extensions manquantes..."
    apt update
    apt install -y "${MISSING_EXTENSIONS[@]}"
    print_status "Extensions PHP installées"
fi

# Vérifier Apache
if ! systemctl is-active --quiet apache2; then
    print_warning "Apache2 n'est pas actif"
    systemctl start apache2
    print_status "Apache2 démarré"
fi

# Télécharger le module
print_info "Téléchargement du module AuditDigital..."
cd /tmp
rm -rf audit
if ! git clone https://github.com/12457845124884/audit.git; then
    print_error "Échec du téléchargement. Vérifiez votre connexion internet."
    exit 1
fi
print_status "Module téléchargé"

# Créer le répertoire custom s'il n'existe pas
print_info "Préparation de l'installation..."
mkdir -p "$DOLIBARR_PATH/custom"

# Supprimer l'ancienne installation si elle existe
if [ -d "$DOLIBARR_PATH/custom/auditdigital" ]; then
    print_warning "Ancienne installation détectée, suppression..."
    rm -rf "$DOLIBARR_PATH/custom/auditdigital"
fi

# Copier le module
print_info "Installation du module..."
cp -r audit/htdocs/custom/auditdigital "$DOLIBARR_PATH/custom/"
print_status "Fichiers copiés"

# Configuration des permissions
print_info "Configuration des permissions..."
chown -R www-data:www-data "$DOLIBARR_PATH/custom/auditdigital"
find "$DOLIBARR_PATH/custom/auditdigital" -type d -exec chmod 755 {} \;
find "$DOLIBARR_PATH/custom/auditdigital" -type f -exec chmod 644 {} \;

# Rendre les scripts exécutables
chmod 755 "$DOLIBARR_PATH/custom/auditdigital/install.php"
chmod 755 "$DOLIBARR_PATH/custom/auditdigital/test.php"
chmod 755 "$DOLIBARR_PATH/custom/auditdigital/demo.php"
print_status "Permissions configurées"

# Créer le répertoire documents
print_info "Configuration des répertoires de documents..."
DOLIBARR_DOCUMENTS_PATHS=(
    "/var/lib/dolibarr/documents"
    "/var/www/documents"
    "/opt/dolibarr/documents"
    "/srv/dolibarr/documents"
)

DOLIBARR_DOCUMENTS=""
for path in "${DOLIBARR_DOCUMENTS_PATHS[@]}"; do
    if [ -d "$path" ]; then
        DOLIBARR_DOCUMENTS="$path"
        break
    fi
done

if [ ! -z "$DOLIBARR_DOCUMENTS" ]; then
    mkdir -p "$DOLIBARR_DOCUMENTS/auditdigital"
    chown -R www-data:www-data "$DOLIBARR_DOCUMENTS/auditdigital"
    chmod -R 755 "$DOLIBARR_DOCUMENTS/auditdigital"
    print_status "Répertoire documents configuré : $DOLIBARR_DOCUMENTS/auditdigital"
else
    print_warning "Répertoire documents Dolibarr non trouvé"
fi

# Redémarrer Apache
print_info "Redémarrage d'Apache..."
systemctl restart apache2
print_status "Apache redémarré"

# Vérification de l'installation
print_info "Vérification de l'installation..."

# Vérifier les fichiers clés
FILES_TO_CHECK=(
    "core/modules/modAuditDigital.class.php"
    "install.php"
    "class/audit.class.php"
    "wizard/index.php"
    "data/solutions.json"
)

ALL_FILES_OK=true
for file in "${FILES_TO_CHECK[@]}"; do
    if [ -f "$DOLIBARR_PATH/custom/auditdigital/$file" ]; then
        print_status "Fichier OK : $file"
    else
        print_error "Fichier manquant : $file"
        ALL_FILES_OK=false
    fi
done

# Détecter l'URL de base Dolibarr
print_info "Détection de l'URL Dolibarr..."

# Vérifier la configuration Apache
DOLIBARR_URL=""
if [ -f "/etc/apache2/sites-available/dolibarr.conf" ]; then
    DOLIBARR_URL="http://localhost/dolibarr"
elif grep -q "dolibarr" /etc/apache2/sites-available/000-default.conf 2>/dev/null; then
    DOLIBARR_URL="http://localhost/dolibarr"
else
    DOLIBARR_URL="http://localhost"
fi

# Nettoyage
rm -rf /tmp/audit

echo ""
echo "============================================================"
if [ "$ALL_FILES_OK" = true ]; then
    print_status "Installation terminée avec succès !"
else
    print_error "Installation terminée avec des erreurs"
fi
echo "============================================================"
echo ""

print_info "📋 Prochaines étapes :"
echo "1. Accédez à votre interface Dolibarr"
echo "2. Allez dans Configuration → Modules/Applications"
echo "3. Recherchez 'AuditDigital' et activez-le"
echo ""

print_info "🔗 URLs utiles :"
echo "- Installation automatique : $DOLIBARR_URL/custom/auditdigital/install.php"
echo "- Tests du module : $DOLIBARR_URL/custom/auditdigital/test.php"
echo "- Données de démo : $DOLIBARR_URL/custom/auditdigital/demo.php"
echo ""

print_info "📁 Chemins importants :"
echo "- Module installé dans : $DOLIBARR_PATH/custom/auditdigital"
if [ ! -z "$DOLIBARR_DOCUMENTS" ]; then
    echo "- Documents générés dans : $DOLIBARR_DOCUMENTS/auditdigital"
fi
echo ""

print_info "🔧 En cas de problème :"
echo "- Vérifiez les logs : tail -f /var/log/apache2/error.log"
echo "- Testez les permissions : ls -la $DOLIBARR_PATH/custom/auditdigital"
echo "- Relancez le script avec : sudo $0 $DOLIBARR_PATH"
echo ""

print_warning "N'oubliez pas de configurer les permissions utilisateurs dans Dolibarr !"

exit 0