#!/bin/bash

# =============================================================================
# Script de Correction - Installation Ubuntu 22.04
# =============================================================================
# 
# Ce script corrige les problèmes spécifiques à l'installation Ubuntu
# et met à jour le module AuditDigital avec les corrections
#
# Usage: sudo ./fix_ubuntu_installation.sh
#
# Auteur: Up Digit Agency
# Version: 1.0.0
# =============================================================================

set -euo pipefail

# Configuration spécifique Ubuntu
DOLIBARR_DIR="/usr/share/dolibarr/htdocs"
MODULE_DIR="$DOLIBARR_DIR/custom/auditdigital"
APACHE_USER="www-data"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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
    esac
}

# Vérification des prérequis
check_prerequisites() {
    log "INFO" "Vérification des prérequis Ubuntu..."
    
    # Vérifier que le script est exécuté en tant que root
    if [[ $EUID -ne 0 ]]; then
        log "ERROR" "Ce script doit être exécuté en tant que root (sudo)"
        exit 1
    fi
    
    # Vérifier l'existence du répertoire Dolibarr
    if [[ ! -d "$DOLIBARR_DIR" ]]; then
        log "ERROR" "Répertoire Dolibarr non trouvé: $DOLIBARR_DIR"
        exit 1
    fi
    
    # Vérifier l'existence du module
    if [[ ! -d "$MODULE_DIR" ]]; then
        log "ERROR" "Module AuditDigital non trouvé: $MODULE_DIR"
        log "INFO" "Veuillez d'abord déployer le module avec: ./deploy.sh -d $DOLIBARR_DIR"
        exit 1
    fi
    
    log "SUCCESS" "Prérequis vérifiés"
}

# Sauvegarde des fichiers existants
backup_existing_files() {
    log "INFO" "Sauvegarde des fichiers existants..."
    
    local backup_dir="/tmp/auditdigital_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_dir"
    
    # Sauvegarder les fichiers critiques
    if [[ -f "$MODULE_DIR/lib/auditdigital.lib.php" ]]; then
        cp "$MODULE_DIR/lib/auditdigital.lib.php" "$backup_dir/"
        log "SUCCESS" "Sauvegarde créée: $backup_dir"
    fi
}

# Correction du fichier lib/auditdigital.lib.php
fix_lib_file() {
    log "INFO" "Correction du fichier lib/auditdigital.lib.php..."
    
    local lib_file="$MODULE_DIR/lib/auditdigital.lib.php"
    
    if [[ ! -f "$lib_file" ]]; then
        log "ERROR" "Fichier non trouvé: $lib_file"
        return 1
    fi
    
    # Copier le fichier corrigé depuis notre dépôt
    if [[ -f "$SCRIPT_DIR/lib/auditdigital.lib.php" ]]; then
        cp "$SCRIPT_DIR/lib/auditdigital.lib.php" "$lib_file"
        log "SUCCESS" "Fichier lib/auditdigital.lib.php corrigé"
    else
        # Correction manuelle si le fichier source n'est pas disponible
        log "INFO" "Application de la correction manuelle..."
        
        # Vérifier si la correction est nécessaire
        if grep -q "global \$db, \$langs, \$conf, \$user;" "$lib_file"; then
            log "INFO" "Correction déjà appliquée"
        else
            # Appliquer la correction
            sed -i 's/global $db, $langs, $conf;/global $db, $langs, $conf, $user;/' "$lib_file"
            log "SUCCESS" "Correction appliquée manuellement"
        fi
    fi
}

# Mise à jour des permissions
fix_permissions() {
    log "INFO" "Correction des permissions..."
    
    # Permissions pour les fichiers
    find "$MODULE_DIR" -type f -name "*.php" -exec chmod 644 {} \;
    find "$MODULE_DIR" -type f -name "*.js" -exec chmod 644 {} \;
    find "$MODULE_DIR" -type f -name "*.css" -exec chmod 644 {} \;
    find "$MODULE_DIR" -type f -name "*.sh" -exec chmod 755 {} \;
    
    # Permissions pour les répertoires
    find "$MODULE_DIR" -type d -exec chmod 755 {} \;
    
    # Répertoires spéciaux avec permissions d'écriture
    local writable_dirs=("documents" "temp" "logs")
    for dir in "${writable_dirs[@]}"; do
        if [[ ! -d "$MODULE_DIR/$dir" ]]; then
            mkdir -p "$MODULE_DIR/$dir"
        fi
        chmod 777 "$MODULE_DIR/$dir"
        chown -R $APACHE_USER:$APACHE_USER "$MODULE_DIR/$dir"
    done
    
    # Propriétaire Apache pour tout le module
    chown -R $APACHE_USER:$APACHE_USER "$MODULE_DIR"
    
    log "SUCCESS" "Permissions corrigées"
}

# Vérification de la configuration Apache
check_apache_config() {
    log "INFO" "Vérification de la configuration Apache..."
    
    # Vérifier que mod_rewrite est activé
    if apache2ctl -M | grep -q rewrite; then
        log "SUCCESS" "Module Apache mod_rewrite activé"
    else
        log "WARNING" "Module Apache mod_rewrite non activé"
        log "INFO" "Activation du module mod_rewrite..."
        a2enmod rewrite
        systemctl reload apache2
        log "SUCCESS" "Module mod_rewrite activé"
    fi
    
    # Vérifier la configuration PHP
    local php_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
    log "INFO" "Version PHP détectée: $php_version"
    
    # Vérifier les extensions PHP requises
    local required_extensions=("mysqli" "gd" "curl" "json" "mbstring")
    local missing_extensions=()
    
    for ext in "${required_extensions[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            missing_extensions+=("$ext")
        fi
    done
    
    if [[ ${#missing_extensions[@]} -gt 0 ]]; then
        log "WARNING" "Extensions PHP manquantes: ${missing_extensions[*]}"
        log "INFO" "Installation des extensions manquantes..."
        
        for ext in "${missing_extensions[@]}"; do
            apt-get update -qq
            apt-get install -y "php$php_version-$ext"
        done
        
        systemctl reload apache2
        log "SUCCESS" "Extensions PHP installées"
    else
        log "SUCCESS" "Toutes les extensions PHP requises sont présentes"
    fi
}

# Nettoyage du cache Dolibarr
clear_dolibarr_cache() {
    log "INFO" "Nettoyage du cache Dolibarr..."
    
    # Répertoires de cache Dolibarr
    local cache_dirs=(
        "/var/lib/dolibarr/documents/admin/temp"
        "$DOLIBARR_DIR/../documents/admin/temp"
        "/tmp/dolibarr_*"
    )
    
    for cache_dir in "${cache_dirs[@]}"; do
        if [[ -d "$cache_dir" ]]; then
            rm -rf "$cache_dir"/* 2>/dev/null || true
            log "INFO" "Cache nettoyé: $cache_dir"
        fi
    done
    
    log "SUCCESS" "Cache Dolibarr nettoyé"
}

# Test de l'installation
test_installation() {
    log "INFO" "Test de l'installation..."
    
    # Tester l'accès aux fichiers PHP
    local test_files=(
        "$MODULE_DIR/wizard/index.php"
        "$MODULE_DIR/wizard/modern.php"
        "$MODULE_DIR/demo_modern.php"
    )
    
    for file in "${test_files[@]}"; do
        if [[ -f "$file" ]]; then
            # Test de syntaxe PHP
            if php -l "$file" > /dev/null 2>&1; then
                log "SUCCESS" "Syntaxe PHP valide: $(basename "$file")"
            else
                log "ERROR" "Erreur de syntaxe PHP: $(basename "$file")"
                php -l "$file"
            fi
        else
            log "WARNING" "Fichier non trouvé: $(basename "$file")"
        fi
    done
}

# Redémarrage des services
restart_services() {
    log "INFO" "Redémarrage des services..."
    
    # Redémarrer Apache
    systemctl restart apache2
    if systemctl is-active --quiet apache2; then
        log "SUCCESS" "Apache redémarré avec succès"
    else
        log "ERROR" "Erreur lors du redémarrage d'Apache"
        systemctl status apache2
        return 1
    fi
    
    # Redémarrer PHP-FPM si présent
    local php_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
    if systemctl is-enabled --quiet "php$php_version-fpm" 2>/dev/null; then
        systemctl restart "php$php_version-fpm"
        log "SUCCESS" "PHP-FPM redémarré"
    fi
}

# Affichage des informations de test
show_test_info() {
    echo
    echo "=============================================="
    echo "🎉 CORRECTION UBUNTU TERMINÉE AVEC SUCCÈS"
    echo "=============================================="
    echo
    echo "📋 URLs de test:"
    echo "  • Interface moderne: http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
    echo "  • Interface classique: http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
    echo "  • Démonstration: http://192.168.1.252/dolibarr/custom/auditdigital/demo_modern.php"
    echo "  • Installation: http://192.168.1.252/dolibarr/custom/auditdigital/install_modern_features.php"
    echo
    echo "🔧 Corrections appliquées:"
    echo "  ✅ Variable \$user ajoutée dans lib/auditdigital.lib.php"
    echo "  ✅ Permissions corrigées pour Apache"
    echo "  ✅ Extensions PHP vérifiées"
    echo "  ✅ Cache Dolibarr nettoyé"
    echo "  ✅ Services redémarrés"
    echo
    echo "📊 Surveillance des logs:"
    echo "  sudo tail -f /var/log/apache2/error.log"
    echo
    echo "🆘 En cas de problème:"
    echo "  1. Vérifier les logs Apache: sudo tail -f /var/log/apache2/error.log"
    echo "  2. Vérifier les permissions: ls -la $MODULE_DIR"
    echo "  3. Tester la syntaxe PHP: php -l $MODULE_DIR/wizard/index.php"
    echo
    echo "=============================================="
}

# Fonction principale
main() {
    echo "🔧 Correction Installation Ubuntu 22.04 - AuditDigital Moderne"
    echo "=============================================================="
    echo
    
    log "INFO" "Début de la correction pour Ubuntu 22.04"
    log "INFO" "Répertoire Dolibarr: $DOLIBARR_DIR"
    log "INFO" "Module AuditDigital: $MODULE_DIR"
    
    # Exécution des étapes de correction
    check_prerequisites
    backup_existing_files
    fix_lib_file
    fix_permissions
    check_apache_config
    clear_dolibarr_cache
    test_installation
    restart_services
    
    show_test_info
    
    log "SUCCESS" "Correction terminée avec succès!"
    exit 0
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur lors de la correction à la ligne $LINENO"; exit 1' ERR

# Point d'entrée
main "$@"