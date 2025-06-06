#!/bin/bash

# =============================================================================
# Script de Correction - Wizard Moderne
# =============================================================================
# 
# Ce script corrige spécifiquement le problème du wizard moderne
# qui ne fonctionne pas sur Ubuntu 22.04
#
# Usage: sudo ./fix_modern_wizard.sh
#
# Auteur: Up Digit Agency
# Version: 1.0.0
# =============================================================================

set -euo pipefail

# Configuration
DOLIBARR_DIR="/usr/share/dolibarr/htdocs"
MODULE_DIR="$DOLIBARR_DIR/custom/auditdigital"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
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

# Diagnostic du wizard moderne
diagnose_modern_wizard() {
    log "INFO" "Diagnostic du wizard moderne..."
    
    echo "📋 État actuel :"
    
    # Vérifier l'existence du fichier
    if [[ -f "$MODULE_DIR/wizard/modern.php" ]]; then
        echo "  ✅ Fichier modern.php existe"
        
        # Tester la syntaxe
        if php -l "$MODULE_DIR/wizard/modern.php" &>/dev/null; then
            echo "  ✅ Syntaxe PHP valide"
        else
            echo "  ❌ Erreur de syntaxe PHP"
            php -l "$MODULE_DIR/wizard/modern.php"
        fi
        
        # Vérifier les permissions
        if [[ -r "$MODULE_DIR/wizard/modern.php" ]]; then
            echo "  ✅ Permissions de lecture OK"
        else
            echo "  ❌ Problème de permissions"
        fi
        
        # Taille du fichier
        local file_size=$(stat -c%s "$MODULE_DIR/wizard/modern.php")
        echo "  📏 Taille du fichier: $file_size octets"
        
        if [[ $file_size -lt 1000 ]]; then
            echo "  ⚠️  Fichier suspicieusement petit"
        fi
        
    else
        echo "  ❌ Fichier modern.php manquant"
    fi
    
    # Vérifier l'accès web
    echo
    echo "🌐 Test d'accès web :"
    local test_url="http://localhost/dolibarr/custom/auditdigital/wizard/modern.php"
    
    if command -v curl &>/dev/null; then
        local http_code=$(curl -s -o /dev/null -w "%{http_code}" "$test_url" 2>/dev/null || echo "000")
        echo "  📡 Code HTTP: $http_code"
        
        if [[ "$http_code" == "200" ]]; then
            echo "  ✅ Accès web OK"
        elif [[ "$http_code" == "500" ]]; then
            echo "  ❌ Erreur serveur (500)"
        elif [[ "$http_code" == "404" ]]; then
            echo "  ❌ Fichier non trouvé (404)"
        else
            echo "  ❌ Problème d'accès ($http_code)"
        fi
    fi
    
    echo
}

# Correction du wizard moderne
fix_modern_wizard() {
    log "INFO" "Correction du wizard moderne..."
    
    # Sauvegarder l'ancien fichier s'il existe
    if [[ -f "$MODULE_DIR/wizard/modern.php" ]]; then
        local backup_file="/tmp/modern_wizard_backup_$(date +%Y%m%d_%H%M%S).php"
        cp "$MODULE_DIR/wizard/modern.php" "$backup_file"
        log "INFO" "Sauvegarde créée: $backup_file"
    fi
    
    # Copier le nouveau fichier depuis le dépôt
    if [[ -f "$SCRIPT_DIR/wizard/modern.php" ]]; then
        cp "$SCRIPT_DIR/wizard/modern.php" "$MODULE_DIR/wizard/modern.php"
        log "SUCCESS" "Fichier modern.php mis à jour"
    else
        log "ERROR" "Fichier source modern.php non trouvé dans $SCRIPT_DIR/wizard/"
        return 1
    fi
    
    # Corriger les permissions
    chown www-data:www-data "$MODULE_DIR/wizard/modern.php"
    chmod 644 "$MODULE_DIR/wizard/modern.php"
    
    # Vérifier la syntaxe
    if php -l "$MODULE_DIR/wizard/modern.php" &>/dev/null; then
        log "SUCCESS" "Syntaxe PHP validée"
    else
        log "ERROR" "Erreur de syntaxe dans le nouveau fichier"
        php -l "$MODULE_DIR/wizard/modern.php"
        return 1
    fi
}

# Copier les fichiers CSS et JS modernes
copy_modern_assets() {
    log "INFO" "Copie des assets modernes..."
    
    # CSS moderne
    if [[ -f "$SCRIPT_DIR/css/auditdigital-modern.css" ]]; then
        cp "$SCRIPT_DIR/css/auditdigital-modern.css" "$MODULE_DIR/css/"
        chown www-data:www-data "$MODULE_DIR/css/auditdigital-modern.css"
        chmod 644 "$MODULE_DIR/css/auditdigital-modern.css"
        log "SUCCESS" "CSS moderne copié"
    fi
    
    # JavaScript moderne
    if [[ -f "$SCRIPT_DIR/js/wizard-modern.js" ]]; then
        cp "$SCRIPT_DIR/js/wizard-modern.js" "$MODULE_DIR/js/"
        chown www-data:www-data "$MODULE_DIR/js/wizard-modern.js"
        chmod 644 "$MODULE_DIR/js/wizard-modern.js"
        log "SUCCESS" "JavaScript moderne copié"
    fi
}

# Test final du wizard moderne
test_modern_wizard() {
    log "INFO" "Test final du wizard moderne..."
    
    local errors=0
    
    # Test syntaxe
    if php -l "$MODULE_DIR/wizard/modern.php" &>/dev/null; then
        echo "  ✅ Syntaxe PHP valide"
    else
        echo "  ❌ Erreur de syntaxe"
        ((errors++))
    fi
    
    # Test permissions
    if [[ -r "$MODULE_DIR/wizard/modern.php" ]]; then
        echo "  ✅ Permissions OK"
    else
        echo "  ❌ Problème de permissions"
        ((errors++))
    fi
    
    # Test d'accès web
    if command -v curl &>/dev/null; then
        local http_code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php" 2>/dev/null || echo "000")
        if [[ "$http_code" == "200" ]]; then
            echo "  ✅ Accès web OK (HTTP $http_code)"
        else
            echo "  ❌ Problème d'accès web (HTTP $http_code)"
            ((errors++))
        fi
    fi
    
    return $errors
}

# Affichage des résultats
show_results() {
    echo
    echo "=============================================="
    echo "🎉 CORRECTION WIZARD MODERNE TERMINÉE"
    echo "=============================================="
    echo
    echo "🌐 URL à tester :"
    echo "  http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
    echo
    echo "🔍 Comparaison :"
    echo "  • Interface classique :"
    echo "    http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
    echo "  • Interface moderne :"
    echo "    http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
    echo
    echo "📊 Démonstration :"
    echo "  http://192.168.1.252/dolibarr/custom/auditdigital/demo_modern.php"
    echo
    echo "🔧 En cas de problème :"
    echo "  sudo tail -f /var/log/apache2/error.log"
    echo
    echo "=============================================="
}

# Fonction principale
main() {
    echo "🔧 Correction du Wizard Moderne - AuditDigital"
    echo "=============================================="
    echo
    
    # Vérifier les droits root
    if [[ $EUID -ne 0 ]]; then
        log "ERROR" "Ce script doit être exécuté en tant que root (sudo)"
        exit 1
    fi
    
    # Diagnostic initial
    diagnose_modern_wizard
    
    # Demander confirmation
    echo "Voulez-vous corriger le wizard moderne ? (y/N)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log "INFO" "Opération annulée"
        exit 0
    fi
    
    # Corrections
    fix_modern_wizard
    copy_modern_assets
    
    # Test final
    echo
    if test_modern_wizard; then
        log "SUCCESS" "Wizard moderne corrigé avec succès !"
        show_results
    else
        log "WARNING" "Correction terminée avec des avertissements"
        show_results
    fi
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur à la ligne $LINENO"; exit 1' ERR

# Exécution
main "$@"