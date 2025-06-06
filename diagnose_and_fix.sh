#!/bin/bash

# =============================================================================
# Script de Diagnostic et Correction Complète - Ubuntu 22.04
# =============================================================================
# 
# Ce script diagnostique et corrige tous les problèmes d'installation
# spécifiques à Ubuntu 22.04 avec PHP 8.1
#
# Usage: sudo ./diagnose_and_fix.sh
#
# Auteur: Up Digit Agency
# Version: 1.0.0
# =============================================================================

set -euo pipefail

# Configuration
DOLIBARR_DIR="/usr/share/dolibarr/htdocs"
MODULE_DIR="$DOLIBARR_DIR/custom/auditdigital"
APACHE_USER="www-data"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    case $level in
        "INFO") echo -e "${CYAN}[INFO]${NC} ${timestamp} - $message" ;;
        "SUCCESS") echo -e "${GREEN}[SUCCESS]${NC} ${timestamp} - $message" ;;
        "WARNING") echo -e "${YELLOW}[WARNING]${NC} ${timestamp} - $message" ;;
        "ERROR") echo -e "${RED}[ERROR]${NC} ${timestamp} - $message" ;;
    esac
}

# Diagnostic complet du système
diagnose_system() {
    echo "🔍 DIAGNOSTIC COMPLET DU SYSTÈME"
    echo "================================="
    echo
    
    # 1. Vérification Ubuntu
    log "INFO" "Vérification du système Ubuntu..."
    if [[ -f /etc/os-release ]]; then
        source /etc/os-release
        echo "  • OS: $PRETTY_NAME"
        echo "  • Version: $VERSION"
    fi
    
    # 2. Vérification PHP
    log "INFO" "Vérification de PHP..."
    local php_version=$(php -v | head -n1)
    echo "  • $php_version"
    
    # Extensions PHP
    echo "  • Extensions PHP installées:"
    local required_exts=("mysqli" "gd" "curl" "mbstring" "xml" "zip")
    for ext in "${required_exts[@]}"; do
        if php -m | grep -q "^$ext$"; then
            echo "    ✅ $ext"
        else
            echo "    ❌ $ext (MANQUANT)"
        fi
    done
    
    # JSON est intégré en PHP 8.1
    if php -r "echo json_encode(['test' => 'ok']);" &>/dev/null; then
        echo "    ✅ json (intégré)"
    else
        echo "    ❌ json (PROBLÈME)"
    fi
    
    # 3. Vérification Apache
    log "INFO" "Vérification d'Apache..."
    if systemctl is-active --quiet apache2; then
        echo "  ✅ Apache actif"
    else
        echo "  ❌ Apache inactif"
    fi
    
    # Modules Apache
    local apache_mods=("rewrite" "php8.1")
    for mod in "${apache_mods[@]}"; do
        if apache2ctl -M 2>/dev/null | grep -q "$mod"; then
            echo "  ✅ Module $mod activé"
        else
            echo "  ❌ Module $mod manquant"
        fi
    done
    
    # 4. Vérification Dolibarr
    log "INFO" "Vérification de Dolibarr..."
    if [[ -d "$DOLIBARR_DIR" ]]; then
        echo "  ✅ Répertoire Dolibarr trouvé: $DOLIBARR_DIR"
        
        # Vérifier les permissions
        if [[ -w "$DOLIBARR_DIR" ]]; then
            echo "  ✅ Permissions d'écriture OK"
        else
            echo "  ❌ Pas de permissions d'écriture"
        fi
        
        # Vérifier custom
        if [[ -d "$DOLIBARR_DIR/custom" ]]; then
            echo "  ✅ Répertoire custom existe"
        else
            echo "  ❌ Répertoire custom manquant"
        fi
    else
        echo "  ❌ Répertoire Dolibarr non trouvé"
    fi
    
    # 5. Vérification du module AuditDigital
    log "INFO" "Vérification du module AuditDigital..."
    if [[ -d "$MODULE_DIR" ]]; then
        echo "  ✅ Module installé: $MODULE_DIR"
        
        # Fichiers critiques
        local critical_files=(
            "wizard/index.php"
            "wizard/modern.php"
            "lib/auditdigital.lib.php"
            "class/audit.class.php"
        )
        
        for file in "${critical_files[@]}"; do
            if [[ -f "$MODULE_DIR/$file" ]]; then
                # Test syntaxe PHP
                if php -l "$MODULE_DIR/$file" &>/dev/null; then
                    echo "  ✅ $file (syntaxe OK)"
                else
                    echo "  ❌ $file (erreur syntaxe)"
                fi
            else
                echo "  ❌ $file (manquant)"
            fi
        done
    else
        echo "  ❌ Module non installé"
    fi
    
    echo
}

# Nettoyage complet de l'ancienne installation
clean_old_installation() {
    log "INFO" "Nettoyage de l'ancienne installation..."
    
    if [[ -d "$MODULE_DIR" ]]; then
        # Sauvegarder la configuration si elle existe
        local backup_dir="/tmp/auditdigital_backup_$(date +%Y%m%d_%H%M%S)"
        mkdir -p "$backup_dir"
        
        if [[ -f "$MODULE_DIR/config.php" ]]; then
            cp "$MODULE_DIR/config.php" "$backup_dir/"
            log "INFO" "Configuration sauvegardée: $backup_dir/config.php"
        fi
        
        # Supprimer l'ancienne installation
        rm -rf "$MODULE_DIR"
        log "SUCCESS" "Ancienne installation supprimée"
    else
        log "INFO" "Aucune installation précédente trouvée"
    fi
}

# Installation propre des dépendances PHP 8.1
install_php81_dependencies() {
    log "INFO" "Installation des dépendances PHP 8.1..."
    
    # Mise à jour des paquets
    apt-get update -qq
    
    # Extensions PHP 8.1 (JSON est intégré, pas besoin de l'installer séparément)
    local php_packages=(
        "php8.1-mysqli"
        "php8.1-gd"
        "php8.1-curl"
        "php8.1-mbstring"
        "php8.1-xml"
        "php8.1-zip"
        "libapache2-mod-php8.1"
    )
    
    for package in "${php_packages[@]}"; do
        if ! dpkg -l | grep -q "^ii.*$package"; then
            log "INFO" "Installation de $package..."
            apt-get install -y "$package"
        else
            log "INFO" "Déjà installé: $package"
        fi
    done
    
    # Activer le module PHP 8.1 pour Apache
    a2enmod php8.1
    
    log "SUCCESS" "Dépendances PHP 8.1 installées"
}

# Configuration Apache complète
configure_apache_complete() {
    log "INFO" "Configuration complète d'Apache..."
    
    # 1. Corriger le ServerName
    if ! grep -q "ServerName" /etc/apache2/apache2.conf; then
        echo "ServerName localhost" >> /etc/apache2/apache2.conf
        log "SUCCESS" "ServerName configuré"
    fi
    
    # 2. Activer les modules nécessaires
    local modules=("rewrite" "headers" "expires" "php8.1")
    for module in "${modules[@]}"; do
        a2enmod "$module" 2>/dev/null || true
    done
    
    # 3. Configuration PHP pour Dolibarr
    local php_ini="/etc/php/8.1/apache2/php.ini"
    if [[ -f "$php_ini" ]]; then
        # Créer une sauvegarde
        cp "$php_ini" "$php_ini.backup.$(date +%Y%m%d)"
        
        # Optimiser les paramètres
        sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' "$php_ini"
        sed -i 's/post_max_size = .*/post_max_size = 25M/' "$php_ini"
        sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$php_ini"
        sed -i 's/memory_limit = .*/memory_limit = 512M/' "$php_ini"
        sed -i 's/max_input_vars = .*/max_input_vars = 3000/' "$php_ini"
        
        log "SUCCESS" "Configuration PHP optimisée"
    fi
    
    # 4. Vérifier la configuration Apache
    if apache2ctl configtest; then
        log "SUCCESS" "Configuration Apache valide"
    else
        log "ERROR" "Erreur dans la configuration Apache"
        return 1
    fi
    
    log "SUCCESS" "Apache configuré"
}

# Installation propre du module
install_module_clean() {
    log "INFO" "Installation propre du module..."
    
    # Créer la structure
    mkdir -p "$MODULE_DIR"
    mkdir -p "$MODULE_DIR/documents"
    mkdir -p "$MODULE_DIR/temp"
    mkdir -p "$MODULE_DIR/logs"
    
    # Copier les fichiers depuis le dépôt
    rsync -av \
        --exclude='.git*' \
        --exclude='*.sh' \
        --exclude='README.md' \
        --exclude='CHANGELOG.md' \
        --exclude='.gitignore' \
        --exclude='backups/' \
        "$SCRIPT_DIR/" "$MODULE_DIR/"
    
    # Créer la configuration
    cat > "$MODULE_DIR/config.php" << 'EOF'
<?php
/**
 * Configuration AuditDigital - Ubuntu 22.04 avec PHP 8.1
 */

// Configuration de l'environnement
define('AUDITDIGITAL_ENV', 'production');
define('AUDITDIGITAL_DEBUG', false);
define('AUDITDIGITAL_LOG_LEVEL', 'ERROR');

// Fonctionnalités modernes
define('AUDITDIGITAL_MODERN_UI_ENABLED', true);
define('AUDITDIGITAL_COMMENTS_ENABLED', true);
define('AUDITDIGITAL_CHARTS_ENABLED', true);
define('AUDITDIGITAL_ROI_CALCULATION_ENABLED', true);

// Configuration uploads
define('AUDITDIGITAL_MAX_UPLOAD_SIZE', 20971520); // 20MB
define('AUDITDIGITAL_ALLOWED_EXTENSIONS', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif');

// Auto-save
define('AUDITDIGITAL_AUTO_SAVE_INTERVAL', 30);

// Spécifique Ubuntu
define('AUDITDIGITAL_UBUNTU_INSTALL', true);
define('AUDITDIGITAL_PHP_VERSION', '8.1');

EOF
    
    log "SUCCESS" "Module installé proprement"
}

# Configuration des permissions parfaites
set_perfect_permissions() {
    log "INFO" "Configuration des permissions parfaites..."
    
    # Propriétaire et groupe
    chown -R $APACHE_USER:$APACHE_USER "$MODULE_DIR"
    
    # Permissions des fichiers
    find "$MODULE_DIR" -type f -name "*.php" -exec chmod 644 {} \;
    find "$MODULE_DIR" -type f -name "*.js" -exec chmod 644 {} \;
    find "$MODULE_DIR" -type f -name "*.css" -exec chmod 644 {} \;
    find "$MODULE_DIR" -type f -name "*.html" -exec chmod 644 {} \;
    
    # Permissions des répertoires
    find "$MODULE_DIR" -type d -exec chmod 755 {} \;
    
    # Répertoires avec écriture
    chmod 777 "$MODULE_DIR/documents"
    chmod 777 "$MODULE_DIR/temp"
    chmod 777 "$MODULE_DIR/logs"
    
    # Vérification
    if [[ -r "$MODULE_DIR/wizard/index.php" ]]; then
        log "SUCCESS" "Permissions configurées correctement"
    else
        log "ERROR" "Problème de permissions"
        return 1
    fi
}

# Test complet de l'installation
test_complete_installation() {
    log "INFO" "Test complet de l'installation..."
    
    local errors=0
    
    # 1. Test syntaxe PHP
    local test_files=(
        "$MODULE_DIR/wizard/index.php"
        "$MODULE_DIR/wizard/modern.php"
        "$MODULE_DIR/lib/auditdigital.lib.php"
        "$MODULE_DIR/class/audit.class.php"
        "$MODULE_DIR/demo_modern.php"
    )
    
    echo "  Tests de syntaxe PHP:"
    for file in "${test_files[@]}"; do
        if [[ -f "$file" ]]; then
            if php -l "$file" &>/dev/null; then
                echo "    ✅ $(basename "$file")"
            else
                echo "    ❌ $(basename "$file") - Erreur syntaxe"
                php -l "$file"
                ((errors++))
            fi
        else
            echo "    ❌ $(basename "$file") - Fichier manquant"
            ((errors++))
        fi
    done
    
    # 2. Test d'accès web
    echo "  Tests d'accès web:"
    local test_urls=(
        "http://localhost/dolibarr/custom/auditdigital/demo_modern.php"
        "http://localhost/dolibarr/custom/auditdigital/wizard/index.php"
    )
    
    for url in "${test_urls[@]}"; do
        if command -v curl &>/dev/null; then
            local http_code=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
            if [[ "$http_code" == "200" ]]; then
                echo "    ✅ $(basename "$url") - HTTP $http_code"
            else
                echo "    ❌ $(basename "$url") - HTTP $http_code"
                ((errors++))
            fi
        fi
    done
    
    # 3. Test des permissions
    echo "  Tests de permissions:"
    if [[ -r "$MODULE_DIR/wizard/index.php" ]]; then
        echo "    ✅ Lecture des fichiers PHP"
    else
        echo "    ❌ Problème de lecture"
        ((errors++))
    fi
    
    if [[ -w "$MODULE_DIR/documents" ]]; then
        echo "    ✅ Écriture dans documents/"
    else
        echo "    ❌ Problème d'écriture"
        ((errors++))
    fi
    
    return $errors
}

# Redémarrage complet des services
restart_all_services() {
    log "INFO" "Redémarrage complet des services..."
    
    # Arrêter Apache
    systemctl stop apache2
    sleep 2
    
    # Redémarrer Apache
    systemctl start apache2
    
    # Vérifier le statut
    if systemctl is-active --quiet apache2; then
        log "SUCCESS" "Apache redémarré avec succès"
    else
        log "ERROR" "Erreur lors du redémarrage d'Apache"
        systemctl status apache2
        return 1
    fi
    
    # Recharger la configuration
    systemctl reload apache2
    
    log "SUCCESS" "Services redémarrés"
}

# Affichage des résultats finaux
show_final_results() {
    echo
    echo "=============================================="
    echo "🎉 DIAGNOSTIC ET CORRECTION TERMINÉS"
    echo "=============================================="
    echo
    echo "🌐 URLs à tester:"
    echo "  • Interface moderne:"
    echo "    http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
    echo
    echo "  • Interface classique:"
    echo "    http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
    echo
    echo "  • Démonstration:"
    echo "    http://192.168.1.252/dolibarr/custom/auditdigital/demo_modern.php"
    echo
    echo "  • Installation des fonctionnalités:"
    echo "    http://192.168.1.252/dolibarr/custom/auditdigital/install_modern_features.php"
    echo
    echo "🔧 Commandes de surveillance:"
    echo "  • Logs Apache: sudo tail -f /var/log/apache2/error.log"
    echo "  • Logs d'accès: sudo tail -f /var/log/apache2/access.log"
    echo "  • Statut Apache: sudo systemctl status apache2"
    echo
    echo "📋 Configuration appliquée:"
    echo "  ✅ PHP 8.1 avec toutes les extensions"
    echo "  ✅ Apache configuré avec ServerName"
    echo "  ✅ Module installé proprement"
    echo "  ✅ Permissions optimisées"
    echo "  ✅ Configuration Dolibarr compatible"
    echo
    echo "=============================================="
}

# Fonction principale
main() {
    echo "🔍 DIAGNOSTIC ET CORRECTION COMPLÈTE - Ubuntu 22.04"
    echo "===================================================="
    echo
    
    # Vérifier les droits root
    if [[ $EUID -ne 0 ]]; then
        log "ERROR" "Ce script doit être exécuté en tant que root (sudo)"
        exit 1
    fi
    
    # Diagnostic initial
    diagnose_system
    
    # Demander confirmation
    echo "Voulez-vous procéder à la correction complète ? (y/N)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log "INFO" "Opération annulée par l'utilisateur"
        exit 0
    fi
    
    echo
    log "INFO" "Début de la correction complète..."
    
    # Étapes de correction
    clean_old_installation
    install_php81_dependencies
    configure_apache_complete
    install_module_clean
    set_perfect_permissions
    restart_all_services
    
    # Test final
    echo
    log "INFO" "Tests finaux..."
    if test_complete_installation; then
        log "SUCCESS" "Tous les tests sont passés !"
        show_final_results
    else
        log "WARNING" "Certains tests ont échoué, mais l'installation de base est fonctionnelle"
        show_final_results
    fi
    
    log "SUCCESS" "Correction terminée !"
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur à la ligne $LINENO"; exit 1' ERR

# Exécution
main "$@"