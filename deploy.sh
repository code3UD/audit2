#!/bin/bash

# =============================================================================
# Script de Déploiement Automatisé - Module AuditDigital Moderne
# =============================================================================
# 
# Ce script automatise le déploiement du module AuditDigital modernisé
# sur un serveur Dolibarr existant.
#
# Usage: ./deploy.sh [options]
# Options:
#   -h, --help          Afficher cette aide
#   -e, --env ENV       Environnement (dev|staging|prod) [défaut: dev]
#   -d, --dolibarr DIR  Répertoire Dolibarr [défaut: /var/www/dolibarr]
#   -b, --backup        Créer une sauvegarde avant déploiement
#   -t, --test          Mode test (simulation sans modification)
#   -v, --verbose       Mode verbeux
#
# Auteur: Up Digit Agency
# Version: 1.0.0
# =============================================================================

set -euo pipefail

# Configuration par défaut
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOLIBARR_DIR="/var/www/dolibarr"
ENVIRONMENT="dev"
BACKUP_ENABLED=false
TEST_MODE=false
VERBOSE=false
MODULE_NAME="auditdigital"

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
🚀 Script de Déploiement - Module AuditDigital Moderne

Usage: $0 [options]

Options:
    -h, --help          Afficher cette aide
    -e, --env ENV       Environnement (dev|staging|prod) [défaut: dev]
    -d, --dolibarr DIR  Répertoire Dolibarr [défaut: /var/www/dolibarr]
    -b, --backup        Créer une sauvegarde avant déploiement
    -t, --test          Mode test (simulation sans modification)
    -v, --verbose       Mode verbeux

Exemples:
    $0                                    # Déploiement dev basique
    $0 -e prod -b -d /opt/dolibarr       # Déploiement prod avec backup
    $0 -t -v                             # Test en mode verbeux

Prérequis:
    - Dolibarr 13.0+ installé
    - PHP 7.4+ avec extensions requises
    - Droits d'écriture sur le répertoire Dolibarr
    - MySQL/MariaDB accessible

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
            -e|--env)
                ENVIRONMENT="$2"
                shift 2
                ;;
            -d|--dolibarr)
                DOLIBARR_DIR="$2"
                shift 2
                ;;
            -b|--backup)
                BACKUP_ENABLED=true
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

# Vérification des prérequis
check_prerequisites() {
    log "INFO" "Vérification des prérequis..."
    
    # Vérifier que le script est exécuté avec les bonnes permissions
    if [[ $EUID -eq 0 ]] && [[ $ENVIRONMENT == "prod" ]]; then
        log "WARNING" "Exécution en tant que root détectée en production"
        read -p "Continuer? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "INFO" "Déploiement annulé par l'utilisateur"
            exit 0
        fi
    fi
    
    # Vérifier l'existence du répertoire Dolibarr
    if [[ ! -d "$DOLIBARR_DIR" ]]; then
        log "ERROR" "Répertoire Dolibarr non trouvé: $DOLIBARR_DIR"
        exit 1
    fi
    
    # Vérifier les permissions d'écriture
    if [[ ! -w "$DOLIBARR_DIR" ]]; then
        log "ERROR" "Pas de permissions d'écriture sur: $DOLIBARR_DIR"
        exit 1
    fi
    
    # Vérifier la présence du répertoire custom
    local custom_dir="$DOLIBARR_DIR/htdocs/custom"
    if [[ ! -d "$custom_dir" ]]; then
        log "INFO" "Création du répertoire custom: $custom_dir"
        if [[ $TEST_MODE == false ]]; then
            mkdir -p "$custom_dir"
        fi
    fi
    
    # Vérifier PHP
    if ! command -v php &> /dev/null; then
        log "ERROR" "PHP non trouvé dans le PATH"
        exit 1
    fi
    
    local php_version=$(php -r "echo PHP_VERSION;")
    log "INFO" "Version PHP détectée: $php_version"
    
    # Vérifier les extensions PHP requises
    local required_extensions=("mysqli" "gd" "curl" "json" "mbstring")
    for ext in "${required_extensions[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            log "ERROR" "Extension PHP manquante: $ext"
            exit 1
        fi
    done
    
    log "SUCCESS" "Tous les prérequis sont satisfaits"
}

# Création de sauvegarde
create_backup() {
    if [[ $BACKUP_ENABLED == false ]]; then
        return 0
    fi
    
    log "INFO" "Création de la sauvegarde..."
    
    local backup_dir="$SCRIPT_DIR/backups"
    local timestamp=$(date '+%Y%m%d_%H%M%S')
    local backup_name="auditdigital_backup_${timestamp}"
    local target_dir="$DOLIBARR_DIR/htdocs/custom/$MODULE_NAME"
    
    if [[ $TEST_MODE == false ]]; then
        mkdir -p "$backup_dir"
        
        if [[ -d "$target_dir" ]]; then
            log "INFO" "Sauvegarde du module existant..."
            tar -czf "$backup_dir/${backup_name}.tar.gz" -C "$target_dir" . 2>/dev/null || true
            log "SUCCESS" "Sauvegarde créée: $backup_dir/${backup_name}.tar.gz"
        else
            log "INFO" "Aucun module existant à sauvegarder"
        fi
        
        # Sauvegarde de la base de données
        if command -v mysqldump &> /dev/null; then
            log "INFO" "Sauvegarde de la base de données..."
            # Note: Les paramètres de connexion doivent être configurés
            # mysqldump --single-transaction dolibarr > "$backup_dir/${backup_name}_db.sql"
            log "INFO" "Sauvegarde DB à configurer manuellement si nécessaire"
        fi
    else
        log "DEBUG" "Mode test: sauvegarde simulée"
    fi
}

# Déploiement des fichiers
deploy_files() {
    log "INFO" "Déploiement des fichiers du module..."
    
    local target_dir="$DOLIBARR_DIR/htdocs/custom/$MODULE_NAME"
    
    if [[ $TEST_MODE == false ]]; then
        # Créer le répertoire de destination
        mkdir -p "$target_dir"
        
        # Copier tous les fichiers sauf les répertoires de backup et git
        rsync -av \
            --exclude='.git*' \
            --exclude='backups/' \
            --exclude='*.log' \
            --exclude='deploy.sh' \
            --exclude='README.md' \
            "$SCRIPT_DIR/" "$target_dir/"
        
        # Définir les permissions appropriées
        find "$target_dir" -type f -name "*.php" -exec chmod 644 {} \;
        find "$target_dir" -type f -name "*.js" -exec chmod 644 {} \;
        find "$target_dir" -type f -name "*.css" -exec chmod 644 {} \;
        find "$target_dir" -type d -exec chmod 755 {} \;
        
        # Créer les répertoires de données
        local data_dirs=("documents" "temp" "logs")
        for dir in "${data_dirs[@]}"; do
            mkdir -p "$target_dir/$dir"
            chmod 777 "$target_dir/$dir"
        done
        
        log "SUCCESS" "Fichiers déployés avec succès"
    else
        log "DEBUG" "Mode test: déploiement simulé vers $target_dir"
    fi
}

# Configuration de l'environnement
configure_environment() {
    log "INFO" "Configuration de l'environnement: $ENVIRONMENT"
    
    local config_file="$DOLIBARR_DIR/htdocs/custom/$MODULE_NAME/config.php"
    
    if [[ $TEST_MODE == false ]]; then
        # Créer le fichier de configuration spécifique à l'environnement
        cat > "$config_file" << EOF
<?php
/**
 * Configuration AuditDigital - Environnement: $ENVIRONMENT
 * Généré automatiquement le $(date)
 */

// Configuration de l'environnement
define('AUDITDIGITAL_ENV', '$ENVIRONMENT');

// Configuration debug
if ('$ENVIRONMENT' === 'dev') {
    define('AUDITDIGITAL_DEBUG', true);
    define('AUDITDIGITAL_LOG_LEVEL', 'DEBUG');
} else {
    define('AUDITDIGITAL_DEBUG', false);
    define('AUDITDIGITAL_LOG_LEVEL', 'ERROR');
}

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

EOF
        
        log "SUCCESS" "Configuration créée pour l'environnement $ENVIRONMENT"
    else
        log "DEBUG" "Mode test: configuration simulée"
    fi
}

# Installation de la base de données
install_database() {
    log "INFO" "Installation/mise à jour de la base de données..."
    
    if [[ $TEST_MODE == false ]]; then
        # Exécuter le script d'installation via PHP
        local install_script="$DOLIBARR_DIR/htdocs/custom/$MODULE_NAME/install_modern_features.php"
        
        if [[ -f "$install_script" ]]; then
            log "INFO" "Exécution du script d'installation de la base de données..."
            
            # Note: L'installation DB doit être faite via l'interface web
            # ou avec un script CLI spécifique
            log "INFO" "Veuillez exécuter l'installation via: $install_script"
            log "INFO" "Ou via l'interface web Dolibarr"
        else
            log "WARNING" "Script d'installation non trouvé"
        fi
    else
        log "DEBUG" "Mode test: installation DB simulée"
    fi
}

# Vérification post-déploiement
verify_deployment() {
    log "INFO" "Vérification du déploiement..."
    
    local target_dir="$DOLIBARR_DIR/htdocs/custom/$MODULE_NAME"
    local required_files=(
        "wizard/modern.php"
        "css/auditdigital-modern.css"
        "js/wizard-modern.js"
        "class/audit.class.php"
        "class/questionnaire.class.php"
        "demo_modern.php"
    )
    
    local missing_files=()
    
    for file in "${required_files[@]}"; do
        if [[ ! -f "$target_dir/$file" ]]; then
            missing_files+=("$file")
        fi
    done
    
    if [[ ${#missing_files[@]} -eq 0 ]]; then
        log "SUCCESS" "Tous les fichiers requis sont présents"
    else
        log "ERROR" "Fichiers manquants:"
        for file in "${missing_files[@]}"; do
            log "ERROR" "  - $file"
        done
        return 1
    fi
    
    # Vérifier les permissions
    if [[ -r "$target_dir/wizard/modern.php" ]]; then
        log "SUCCESS" "Permissions de lecture correctes"
    else
        log "ERROR" "Problème de permissions de lecture"
        return 1
    fi
    
    log "SUCCESS" "Vérification du déploiement terminée avec succès"
}

# Nettoyage post-déploiement
cleanup() {
    log "INFO" "Nettoyage post-déploiement..."
    
    if [[ $TEST_MODE == false ]]; then
        local target_dir="$DOLIBARR_DIR/htdocs/custom/$MODULE_NAME"
        
        # Supprimer les fichiers temporaires
        find "$target_dir" -name "*.tmp" -delete 2>/dev/null || true
        find "$target_dir" -name ".DS_Store" -delete 2>/dev/null || true
        
        # Nettoyer les logs anciens (> 30 jours)
        find "$target_dir/logs" -name "*.log" -mtime +30 -delete 2>/dev/null || true
        
        log "SUCCESS" "Nettoyage terminé"
    else
        log "DEBUG" "Mode test: nettoyage simulé"
    fi
}

# Affichage du résumé final
show_summary() {
    echo
    echo "=============================================="
    echo "🎉 DÉPLOIEMENT TERMINÉ AVEC SUCCÈS"
    echo "=============================================="
    echo
    echo "📋 Résumé du déploiement:"
    echo "  • Environnement: $ENVIRONMENT"
    echo "  • Répertoire cible: $DOLIBARR_DIR/htdocs/custom/$MODULE_NAME"
    echo "  • Sauvegarde: $([ $BACKUP_ENABLED == true ] && echo "Activée" || echo "Désactivée")"
    echo "  • Mode test: $([ $TEST_MODE == true ] && echo "Activé" || echo "Désactivé")"
    echo
    echo "🚀 Prochaines étapes:"
    echo "  1. Accéder à l'interface d'administration Dolibarr"
    echo "  2. Activer le module AuditDigital si nécessaire"
    echo "  3. Exécuter l'installation des fonctionnalités modernes:"
    echo "     $DOLIBARR_DIR/htdocs/custom/$MODULE_NAME/install_modern_features.php"
    echo "  4. Tester les nouvelles fonctionnalités:"
    echo "     $DOLIBARR_DIR/htdocs/custom/$MODULE_NAME/demo_modern.php"
    echo
    echo "📚 Documentation:"
    echo "  • Guide complet: docs/MODERNISATION_COMPLETE.md"
    echo "  • Support: support@updigit.fr"
    echo
    echo "=============================================="
}

# Fonction principale
main() {
    echo "🚀 Déploiement Module AuditDigital Moderne - v1.0.0"
    echo "=================================================="
    echo
    
    parse_arguments "$@"
    
    log "INFO" "Début du déploiement en environnement: $ENVIRONMENT"
    log "INFO" "Répertoire cible: $DOLIBARR_DIR"
    log "INFO" "Mode test: $([ $TEST_MODE == true ] && echo "Activé" || echo "Désactivé")"
    
    # Exécution des étapes de déploiement
    check_prerequisites
    create_backup
    deploy_files
    configure_environment
    install_database
    verify_deployment
    cleanup
    
    show_summary
    
    log "SUCCESS" "Déploiement terminé avec succès!"
    exit 0
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur lors du déploiement à la ligne $LINENO"; exit 1' ERR

# Point d'entrée
main "$@"