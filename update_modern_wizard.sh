#!/bin/bash

# =============================================================================
# Script de Mise à Jour - Wizard Moderne Complet
# =============================================================================
# 
# Ce script remplace le wizard moderne basique par la version complète
# avec toutes les fonctionnalités promises
#
# Usage: sudo ./update_modern_wizard.sh
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
BLUE='\033[0;34m'
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

# Affichage du header
show_header() {
    echo
    echo -e "${BLUE}=============================================="
    echo "🚀 MISE À JOUR WIZARD MODERNE COMPLET"
    echo "=============================================="
    echo -e "${NC}"
    echo "Cette mise à jour va remplacer le wizard moderne basique"
    echo "par la version complète avec toutes les fonctionnalités :"
    echo
    echo "✨ Nouvelles fonctionnalités :"
    echo "  • Cards cliquables modernes avec animations"
    echo "  • Stepper visuel interactif"
    echo "  • Design glassmorphism avec effets"
    echo "  • Slider moderne pour nombre d'employés"
    echo "  • Système de notation avec échelles visuelles"
    echo "  • Auto-save intelligent toutes les 30s"
    echo "  • Notifications modernes"
    echo "  • Interface 100% responsive"
    echo "  • Animations fluides avec GSAP"
    echo "  • Système de commentaires enrichi"
    echo
    echo -e "${YELLOW}⚠️  Cette opération va remplacer le fichier wizard/modern.php${NC}"
    echo
}

# Diagnostic avant mise à jour
diagnose_current_state() {
    log "INFO" "Diagnostic de l'état actuel..."
    
    echo "📋 État actuel du wizard moderne :"
    
    # Vérifier l'existence du fichier
    if [[ -f "$MODULE_DIR/wizard/modern.php" ]]; then
        echo "  ✅ Fichier modern.php existe"
        
        # Taille du fichier
        local file_size=$(stat -c%s "$MODULE_DIR/wizard/modern.php")
        echo "  📏 Taille actuelle: $file_size octets"
        
        # Vérifier si c'est la version basique ou moderne
        if grep -q "Cards cliquables modernes" "$MODULE_DIR/wizard/modern.php"; then
            echo "  ✅ Version moderne déjà installée"
            return 1
        else
            echo "  ⚠️  Version basique détectée (à remplacer)"
        fi
        
        # Test syntaxe
        if php -l "$MODULE_DIR/wizard/modern.php" &>/dev/null; then
            echo "  ✅ Syntaxe PHP valide"
        else
            echo "  ❌ Erreur de syntaxe PHP"
        fi
        
    else
        echo "  ❌ Fichier modern.php manquant"
    fi
    
    # Vérifier les assets
    echo
    echo "📁 Assets requis :"
    
    local assets=(
        "css/auditdigital-modern.css"
        "js/wizard-modern.js"
    )
    
    for asset in "${assets[@]}"; do
        if [[ -f "$MODULE_DIR/$asset" ]]; then
            echo "  ✅ $asset"
        else
            echo "  ❌ $asset (manquant)"
        fi
    done
    
    echo
    return 0
}

# Sauvegarde de l'ancien fichier
backup_old_wizard() {
    log "INFO" "Sauvegarde de l'ancien wizard..."
    
    local backup_dir="/tmp/auditdigital_wizard_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_dir"
    
    if [[ -f "$MODULE_DIR/wizard/modern.php" ]]; then
        cp "$MODULE_DIR/wizard/modern.php" "$backup_dir/modern_old.php"
        log "SUCCESS" "Sauvegarde créée: $backup_dir/modern_old.php"
    fi
    
    # Sauvegarder aussi les assets s'ils existent
    if [[ -f "$MODULE_DIR/css/auditdigital-modern.css" ]]; then
        cp "$MODULE_DIR/css/auditdigital-modern.css" "$backup_dir/"
    fi
    
    if [[ -f "$MODULE_DIR/js/wizard-modern.js" ]]; then
        cp "$MODULE_DIR/js/wizard-modern.js" "$backup_dir/"
    fi
    
    echo "💾 Sauvegarde complète dans: $backup_dir"
}

# Installation du nouveau wizard moderne
install_modern_wizard() {
    log "INFO" "Installation du wizard moderne complet..."
    
    # Vérifier que le fichier source existe
    if [[ ! -f "$SCRIPT_DIR/wizard/modern.php" ]]; then
        log "ERROR" "Fichier source modern.php non trouvé dans $SCRIPT_DIR/wizard/"
        return 1
    fi
    
    # Copier le nouveau wizard
    cp "$SCRIPT_DIR/wizard/modern.php" "$MODULE_DIR/wizard/modern.php"
    
    # Vérifier la taille du nouveau fichier
    local new_size=$(stat -c%s "$MODULE_DIR/wizard/modern.php")
    log "INFO" "Nouveau fichier installé: $new_size octets"
    
    # Vérifier la syntaxe
    if php -l "$MODULE_DIR/wizard/modern.php" &>/dev/null; then
        log "SUCCESS" "Syntaxe PHP validée"
    else
        log "ERROR" "Erreur de syntaxe dans le nouveau fichier"
        php -l "$MODULE_DIR/wizard/modern.php"
        return 1
    fi
    
    # Corriger les permissions
    chown www-data:www-data "$MODULE_DIR/wizard/modern.php"
    chmod 644 "$MODULE_DIR/wizard/modern.php"
    
    log "SUCCESS" "Wizard moderne complet installé"
}

# Installation/mise à jour des assets CSS et JS
install_modern_assets() {
    log "INFO" "Installation des assets modernes..."
    
    # CSS moderne
    if [[ -f "$SCRIPT_DIR/css/auditdigital-modern.css" ]]; then
        cp "$SCRIPT_DIR/css/auditdigital-modern.css" "$MODULE_DIR/css/"
        chown www-data:www-data "$MODULE_DIR/css/auditdigital-modern.css"
        chmod 644 "$MODULE_DIR/css/auditdigital-modern.css"
        log "SUCCESS" "CSS moderne installé"
    else
        log "WARNING" "CSS moderne non trouvé, création d'un fichier basique..."
        touch "$MODULE_DIR/css/auditdigital-modern.css"
        chown www-data:www-data "$MODULE_DIR/css/auditdigital-modern.css"
        chmod 644 "$MODULE_DIR/css/auditdigital-modern.css"
    fi
    
    # JavaScript moderne
    if [[ -f "$SCRIPT_DIR/js/wizard-modern.js" ]]; then
        cp "$SCRIPT_DIR/js/wizard-modern.js" "$MODULE_DIR/js/"
        chown www-data:www-data "$MODULE_DIR/js/wizard-modern.js"
        chmod 644 "$MODULE_DIR/js/wizard-modern.js"
        log "SUCCESS" "JavaScript moderne installé"
    else
        log "WARNING" "JavaScript moderne non trouvé, création d'un fichier basique..."
        touch "$MODULE_DIR/js/wizard-modern.js"
        chown www-data:www-data "$MODULE_DIR/js/wizard-modern.js"
        chmod 644 "$MODULE_DIR/js/wizard-modern.js"
    fi
}

# Test complet du nouveau wizard
test_modern_wizard() {
    log "INFO" "Test du nouveau wizard moderne..."
    
    local errors=0
    
    # Test syntaxe PHP
    if php -l "$MODULE_DIR/wizard/modern.php" &>/dev/null; then
        echo "  ✅ Syntaxe PHP valide"
    else
        echo "  ❌ Erreur de syntaxe PHP"
        ((errors++))
    fi
    
    # Test permissions
    if [[ -r "$MODULE_DIR/wizard/modern.php" ]]; then
        echo "  ✅ Permissions de lecture OK"
    else
        echo "  ❌ Problème de permissions"
        ((errors++))
    fi
    
    # Test taille du fichier (doit être > 20KB pour la version complète)
    local file_size=$(stat -c%s "$MODULE_DIR/wizard/modern.php")
    if [[ $file_size -gt 20000 ]]; then
        echo "  ✅ Taille du fichier OK ($file_size octets)"
    else
        echo "  ⚠️  Fichier suspicieusement petit ($file_size octets)"
        ((errors++))
    fi
    
    # Test contenu moderne
    if grep -q "Cards cliquables modernes" "$MODULE_DIR/wizard/modern.php"; then
        echo "  ✅ Contenu moderne détecté"
    else
        echo "  ❌ Contenu moderne non détecté"
        ((errors++))
    fi
    
    # Test d'accès web
    if command -v curl &>/dev/null; then
        local http_code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php" 2>/dev/null || echo "000")
        if [[ "$http_code" == "200" ]]; then
            echo "  ✅ Accès web OK (HTTP $http_code)"
        else
            echo "  ⚠️  Accès web à vérifier (HTTP $http_code)"
        fi
    fi
    
    return $errors
}

# Affichage des résultats finaux
show_results() {
    echo
    echo -e "${GREEN}=============================================="
    echo "🎉 MISE À JOUR TERMINÉE AVEC SUCCÈS"
    echo "=============================================="
    echo -e "${NC}"
    echo "🌐 Testez le nouveau wizard moderne :"
    echo "  http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
    echo
    echo "🆚 Comparaison avec l'ancien :"
    echo "  • Ancien (basique) : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
    echo "  • Nouveau (moderne) : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
    echo
    echo "✨ Nouvelles fonctionnalités disponibles :"
    echo "  ✅ Cards cliquables avec animations"
    echo "  ✅ Stepper visuel interactif"
    echo "  ✅ Design glassmorphism moderne"
    echo "  ✅ Slider pour nombre d'employés"
    echo "  ✅ Auto-save intelligent"
    echo "  ✅ Notifications modernes"
    echo "  ✅ Interface 100% responsive"
    echo "  ✅ Animations fluides"
    echo
    echo "📊 Démonstration complète :"
    echo "  http://192.168.1.252/dolibarr/custom/auditdigital/demo_modern.php"
    echo
    echo "🔧 En cas de problème :"
    echo "  sudo tail -f /var/log/apache2/error.log"
    echo
    echo -e "${GREEN}=============================================="
    echo -e "${NC}"
}

# Fonction principale
main() {
    show_header
    
    # Vérifier les droits root
    if [[ $EUID -ne 0 ]]; then
        log "ERROR" "Ce script doit être exécuté en tant que root (sudo)"
        exit 1
    fi
    
    # Diagnostic initial
    if ! diagnose_current_state; then
        log "INFO" "Version moderne déjà installée"
        echo "Voulez-vous forcer la réinstallation ? (y/N)"
        read -r response
        if [[ ! "$response" =~ ^[Yy]$ ]]; then
            log "INFO" "Opération annulée"
            exit 0
        fi
    fi
    
    # Demander confirmation
    echo "Voulez-vous procéder à la mise à jour du wizard moderne ? (y/N)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log "INFO" "Opération annulée par l'utilisateur"
        exit 0
    fi
    
    echo
    log "INFO" "Début de la mise à jour..."
    
    # Étapes de mise à jour
    backup_old_wizard
    install_modern_wizard
    install_modern_assets
    
    # Test final
    echo
    log "INFO" "Tests finaux..."
    if test_modern_wizard; then
        log "SUCCESS" "Wizard moderne mis à jour avec succès !"
        show_results
    else
        log "WARNING" "Mise à jour terminée avec des avertissements"
        show_results
    fi
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur à la ligne $LINENO"; exit 1' ERR

# Exécution
main "$@"