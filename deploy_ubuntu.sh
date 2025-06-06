#!/bin/bash

# =============================================================================
# Script de Déploiement Ubuntu 22.04 - Module AuditDigital Moderne
# =============================================================================
# 
# Ce script automatise le déploiement complet sur Ubuntu 22.04
# avec les chemins et configurations spécifiques
#
# Usage: sudo ./deploy_ubuntu.sh [options]
# Options:
#   -h, --help          Afficher cette aide
#   -u, --update        Mode mise à jour (ne pas écraser la config)
#   -t, --test          Mode test (simulation)
#   -v, --verbose       Mode verbeux
#
# Auteur: Up Digit Agency
# Version: 1.0.0
# =============================================================================

set -euo pipefail

# Configuration spécifique Ubuntu 22.04
DOLIBARR_DIR="/usr/share/dolibarr/htdocs"
MODULE_DIR="$DOLIBARR_DIR/custom/auditdigital"
APACHE_USER="www-data"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
UPDATE_MODE=false
TEST_MODE=false
VERBOSE=false

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Fonction d'affichage avec couleurs
log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    case $level in
        "INFO")
            echo -e "${CYAN}[INFO]${NC} ${timestamp} - $message"
            ;;
        "SUCCESS")
            echo -e "${GREEN}[SUCCESS]${NC} ${timestamp} - $message"
            ;;
        "WARNING")
            echo -e "${YELLOW}[WARNING]${NC} ${timestamp} - $message"
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} ${timestamp} - $message"
            ;;
        "DEBUG")
            if [[ $VERBOSE == true ]]; then
                echo -e "${PURPLE}[DEBUG]${NC} ${timestamp} - $message"
            fi
            ;;
    esac
}

# Fonction d'aide
show_help() {
    cat << EOF
🚀 Script de Déploiement Ubuntu 22.04 - Module AuditDigital Moderne

Usage: sudo $0 [options]

Options:
    -h, --help          Afficher cette aide
    -u, --update        Mode mise à jour (préserver la configuration)
    -t, --test          Mode test (simulation sans modification)
    -v, --verbose       Mode verbeux

Exemples:
    sudo $0                     # Déploiement complet
    sudo $0 -u                  # Mise à jour sans écraser la config
    sudo $0 -t -v               # Test en mode verbeux

Prérequis:
    - Ubuntu 22.04 LTS
    - Dolibarr installé dans /usr/share/dolibarr/htdocs
    - Apache2 et PHP configurés
    - Droits sudo

EOF
}

# Analyse des arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -u|--update)
                UPDATE_MODE=true
                shift
                ;;
            -t|--test)
                TEST_MODE=true
                shift
                ;;
            -v|--verbose)
                VERBOSE=true
                shift
                ;;
            *)
                log "ERROR" "Option inconnue: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Vérification des prérequis Ubuntu
check_ubuntu_prerequisites() {
    log "INFO" "Vérification des prérequis Ubuntu 22.04..."
    
    # Vérifier que le script est exécuté en tant que root
    if [[ $EUID -ne 0 ]]; then
        log "ERROR" "Ce script doit être exécuté en tant que root (sudo)"
        exit 1
    fi
    
    # Vérifier la version Ubuntu
    if [[ -f /etc/os-release ]]; then
        source /etc/os-release
        if [[ "$ID" != "ubuntu" ]]; then
            log "WARNING" "Ce script est optimisé pour Ubuntu (détecté: $ID)"
        fi
        log "INFO" "Système détecté: $PRETTY_NAME"
    fi
    
    # Vérifier l'existence du répertoire Dolibarr
    if [[ ! -d "$DOLIBARR_DIR" ]]; then
        log "ERROR" "Répertoire Dolibarr non trouvé: $DOLIBARR_DIR"
        log "INFO" "Veuillez installer Dolibarr d'abord:"
        log "INFO" "sudo apt-get install dolibarr"
        exit 1
    fi
    
    # Vérifier Apache
    if ! systemctl is-active --quiet apache2; then
        log "ERROR" "Apache2 n'est pas actif"
        log "INFO" "Démarrage d'Apache2..."
        if [[ $TEST_MODE == false ]]; then
            systemctl start apache2
        fi
    fi
    
    # Vérifier PHP
    if ! command -v php &> /dev/null; then
        log "ERROR" "PHP non installé"
        exit 1
    fi
    
    local php_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
    log "INFO" "Version PHP: $php_version"
    
    log "SUCCESS" "Prérequis Ubuntu vérifiés"
}

# Installation des dépendances Ubuntu
install_ubuntu_dependencies() {
    log "INFO" "Installation des dépendances Ubuntu..."
    
    if [[ $TEST_MODE == true ]]; then
        log "DEBUG" "Mode test: installation simulée"
        return 0
    fi
    
    # Mise à jour des paquets
    apt-get update -qq
    
    # Extensions PHP requises
    local php_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
    local php_packages=(
        "php$php_version-mysqli"
        "php$php_version-gd"
        "php$php_version-curl"
        "php$php_version-json"
        "php$php_version-mbstring"
        "php$php_version-xml"
        "php$php_version-zip"
    )
    
    for package in "${php_packages[@]}"; do
        if ! dpkg -l | grep -q "^ii.*$package"; then
            log "INFO" "Installation de $package..."
            apt-get install -y "$package"
        else
            log "DEBUG" "Paquet déjà installé: $package"
        fi
    done
    
    # Outils système utiles
    local system_packages=("git" "curl" "wget" "unzip")
    for package in "${system_packages[@]}"; do
        if ! command -v "$package" &> /dev/null; then
            log "INFO" "Installation de $package..."
            apt-get install -y "$package"
        fi
    done
    
    log "SUCCESS" "Dépendances Ubuntu installées"
}

# Déploiement des fichiers du module
deploy_module_files() {
    log "INFO" "Déploiement des fichiers du module..."
    
    if [[ $TEST_MODE == true ]]; then
        log "DEBUG" "Mode test: déploiement simulé vers $MODULE_DIR"
        return 0
    fi
    
    # Créer le répertoire de destination
    mkdir -p "$MODULE_DIR"
    
    # Sauvegarder la configuration existante en mode mise à jour
    local config_backup=""
    if [[ $UPDATE_MODE == true && -f "$MODULE_DIR/config.php" ]]; then
        config_backup="/tmp/auditdigital_config_$(date +%Y%m%d_%H%M%S).php"
        cp "$MODULE_DIR/config.php" "$config_backup"
        log "INFO" "Configuration sauvegardée: $config_backup"
    fi
    
    # Copier tous les fichiers sauf les exclusions
    rsync -av \
        --exclude='.git*' \
        --exclude='backups/' \
        --exclude='*.log' \
        --exclude='deploy_*.sh' \
        --exclude='fix_*.sh' \
        --exclude='README.md' \
        --exclude='CHANGELOG.md' \
        --exclude='.gitignore' \
        "$SCRIPT_DIR/" "$MODULE_DIR/"
    
    # Restaurer la configuration en mode mise à jour
    if [[ $UPDATE_MODE == true && -n "$config_backup" && -f "$config_backup" ]]; then
        cp "$config_backup" "$MODULE_DIR/config.php"
        log "INFO" "Configuration restaurée"
    fi
    
    log "SUCCESS" "Fichiers du module déployés"
}

# Configuration spécifique Ubuntu
configure_ubuntu_environment() {
    log "INFO" "Configuration de l'environnement Ubuntu..."
    
    if [[ $TEST_MODE == true ]]; then
        log "DEBUG" "Mode test: configuration simulée"
        return 0
    fi
    
    # Créer le fichier de configuration
    cat > "$MODULE_DIR/config.php" << EOF
<?php
/**
 * Configuration AuditDigital - Ubuntu 22.04
 * Généré automatiquement le $(date)
 */

// Configuration de l'environnement
define('AUDITDIGITAL_ENV', 'production');
define('AUDITDIGITAL_DEBUG', false);
define('AUDITDIGITAL_LOG_LEVEL', 'ERROR');

// Configuration des fonctionnalités modernes
define('AUDITDIGITAL_MODERN_UI_ENABLED', true);
define('AUDITDIGITAL_COMMENTS_ENABLED', true);
define('AUDITDIGITAL_CHARTS_ENABLED', true);
define('AUDITDIGITAL_ROI_CALCULATION_ENABLED', true);

// Configuration des uploads
define('AUDITDIGITAL_MAX_UPLOAD_SIZE', 10485760); // 10MB
define('AUDITDIGITAL_ALLOWED_EXTENSIONS', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif');

// Configuration auto-save
define('AUDITDIGITAL_AUTO_SAVE_INTERVAL', 30); // secondes

// Chemins spécifiques Ubuntu
define('AUDITDIGITAL_UBUNTU_INSTALL', true);
define('AUDITDIGITAL_DOLIBARR_PATH', '$DOLIBARR_DIR');
define('AUDITDIGITAL_APACHE_USER', '$APACHE_USER');

EOF
    
    log "SUCCESS" "Configuration Ubuntu créée"
}

# Correction des permissions Ubuntu
fix_ubuntu_permissions() {
    log "INFO" "Configuration des permissions Ubuntu..."
    
    if [[ $TEST_MODE == true ]]; then
        log "DEBUG" "Mode test: permissions simulées"
        return 0
    fi
    
    # Permissions pour les fichiers
    find "$MODULE_DIR" -type f -name "*.php" -exec chmod 644 {} \;
    find "$MODULE_DIR" -type f -name "*.js" -exec chmod 644 {} \;
    find "$MODULE_DIR" -type f -name "*.css" -exec chmod 644 {} \;
    find "$MODULE_DIR" -type f -name "*.sh" -exec chmod 755 {} \;
    
    # Permissions pour les répertoires
    find "$MODULE_DIR" -type d -exec chmod 755 {} \;
    
    # Répertoires avec permissions d'écriture
    local writable_dirs=("documents" "temp" "logs")
    for dir in "${writable_dirs[@]}"; do
        mkdir -p "$MODULE_DIR/$dir"
        chmod 777 "$MODULE_DIR/$dir"
        chown -R $APACHE_USER:$APACHE_USER "$MODULE_DIR/$dir"
    done
    
    # Propriétaire Apache pour tout le module
    chown -R $APACHE_USER:$APACHE_USER "$MODULE_DIR"
    
    log "SUCCESS" "Permissions Ubuntu configurées"
}

# Configuration Apache Ubuntu
configure_apache_ubuntu() {
    log "INFO" "Configuration Apache Ubuntu..."
    
    if [[ $TEST_MODE == true ]]; then
        log "DEBUG" "Mode test: configuration Apache simulée"
        return 0
    fi
    
    # Activer les modules Apache nécessaires
    local apache_modules=("rewrite" "headers" "expires")
    for module in "${apache_modules[@]}"; do
        if ! apache2ctl -M | grep -q "$module"; then
            log "INFO" "Activation du module Apache: $module"
            a2enmod "$module"
        fi
    done
    
    # Configuration PHP pour Dolibarr
    local php_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
    local php_ini="/etc/php/$php_version/apache2/php.ini"
    
    if [[ -f "$php_ini" ]]; then
        # Augmenter les limites PHP si nécessaire
        sed -i 's/upload_max_filesize = .*/upload_max_filesize = 10M/' "$php_ini"
        sed -i 's/post_max_size = .*/post_max_size = 12M/' "$php_ini"
        sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$php_ini"
        sed -i 's/memory_limit = .*/memory_limit = 256M/' "$php_ini"
        
        log "SUCCESS" "Configuration PHP mise à jour"
    fi
    
    # Redémarrer Apache
    systemctl reload apache2
    
    log "SUCCESS" "Apache Ubuntu configuré"
}

# Test de l'installation Ubuntu
test_ubuntu_installation() {
    log "INFO" "Test de l'installation Ubuntu..."
    
    # Tester l'accès aux fichiers
    local test_files=(
        "$MODULE_DIR/wizard/index.php"
        "$MODULE_DIR/wizard/modern.php"
        "$MODULE_DIR/demo_modern.php"
        "$MODULE_DIR/lib/auditdigital.lib.php"
    )
    
    local errors=0
    for file in "${test_files[@]}"; do
        if [[ -f "$file" ]]; then
            # Test de syntaxe PHP
            if php -l "$file" > /dev/null 2>&1; then
                log "SUCCESS" "✓ $(basename "$file")"
            else
                log "ERROR" "✗ Erreur syntaxe: $(basename "$file")"
                ((errors++))
            fi
        else
            log "WARNING" "? Fichier manquant: $(basename "$file")"
            ((errors++))
        fi
    done
    
    # Test de connectivité web
    local base_url="http://localhost/dolibarr/custom/auditdigital"
    if command -v curl &> /dev/null; then
        if curl -s -o /dev/null -w "%{http_code}" "$base_url/demo_modern.php" | grep -q "200"; then
            log "SUCCESS" "✓ Accès web fonctionnel"
        else
            log "WARNING" "? Accès web à vérifier: $base_url"
        fi
    fi
    
    if [[ $errors -eq 0 ]]; then
        log "SUCCESS" "Tous les tests sont passés"
    else
        log "WARNING" "$errors erreur(s) détectée(s)"
    fi
    
    return $errors
}

# Affichage du résumé Ubuntu
show_ubuntu_summary() {
    echo
    echo "=============================================="
    echo "🎉 DÉPLOIEMENT UBUNTU TERMINÉ AVEC SUCCÈS"
    echo "=============================================="
    echo
    echo "📋 Configuration Ubuntu 22.04:"
    echo "  • Répertoire Dolibarr: $DOLIBARR_DIR"
    echo "  • Module AuditDigital: $MODULE_DIR"
    echo "  • Utilisateur Apache: $APACHE_USER"
    echo "  • Mode mise à jour: $([ $UPDATE_MODE == true ] && echo "Activé" || echo "Désactivé")"
    echo
    echo "🌐 URLs d'accès:"
    echo "  • Interface moderne: http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
    echo "  • Interface classique: http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
    echo "  • Démonstration: http://192.168.1.252/dolibarr/custom/auditdigital/demo_modern.php"
    echo "  • Installation: http://192.168.1.252/dolibarr/custom/auditdigital/install_modern_features.php"
    echo
    echo "🔧 Commandes utiles Ubuntu:"
    echo "  • Logs Apache: sudo tail -f /var/log/apache2/error.log"
    echo "  • Redémarrer Apache: sudo systemctl restart apache2"
    echo "  • Vérifier permissions: ls -la $MODULE_DIR"
    echo "  • Test syntaxe PHP: php -l $MODULE_DIR/wizard/index.php"
    echo
    echo "🆘 En cas de problème:"
    echo "  1. Exécuter le script de correction: sudo ./fix_ubuntu_installation.sh"
    echo "  2. Vérifier les logs: sudo tail -f /var/log/apache2/error.log"
    echo "  3. Vérifier la configuration Dolibarr"
    echo
    echo "=============================================="
}

# Fonction principale
main() {
    echo "🚀 Déploiement Ubuntu 22.04 - Module AuditDigital Moderne"
    echo "========================================================="
    echo
    
    parse_arguments "$@"
    
    log "INFO" "Début du déploiement Ubuntu 22.04"
    log "INFO" "Mode mise à jour: $([ $UPDATE_MODE == true ] && echo "Activé" || echo "Désactivé")"
    log "INFO" "Mode test: $([ $TEST_MODE == true ] && echo "Activé" || echo "Désactivé")"
    
    # Exécution des étapes de déploiement
    check_ubuntu_prerequisites
    install_ubuntu_dependencies
    deploy_module_files
    configure_ubuntu_environment
    fix_ubuntu_permissions
    configure_apache_ubuntu
    
    if test_ubuntu_installation; then
        show_ubuntu_summary
        log "SUCCESS" "Déploiement Ubuntu terminé avec succès!"
    else
        log "WARNING" "Déploiement terminé avec des avertissements"
        log "INFO" "Exécutez: sudo ./fix_ubuntu_installation.sh pour corriger les problèmes"
    fi
    
    exit 0
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur lors du déploiement Ubuntu à la ligne $LINENO"; exit 1' ERR

# Point d'entrée
main "$@"