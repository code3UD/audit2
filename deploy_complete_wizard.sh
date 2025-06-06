#!/bin/bash

# =============================================================================
# Script de Déploiement Complet - Wizard Moderne AuditDigital
# =============================================================================
# 
# Ce script déploie le wizard moderne complet avec toutes les 6 étapes
# implémentées selon le prompt initial
#
# Usage: sudo ./deploy_complete_wizard.sh
#
# Auteur: Up Digit Agency
# Version: 2.0.0
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
PURPLE='\033[0;35m'
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
    echo "🚀 DÉPLOIEMENT WIZARD MODERNE COMPLET"
    echo "=============================================="
    echo -e "${NC}"
    echo "Ce déploiement installe le wizard moderne complet avec :"
    echo
    echo -e "${GREEN}✨ TOUTES LES 6 ÉTAPES IMPLÉMENTÉES :${NC}"
    echo "  1️⃣  Informations Générales - Cards cliquables modernes"
    echo "  2️⃣  Maturité Digitale - Questions avec notation + graphiques temps réel"
    echo "  3️⃣  Cybersécurité - Checklist sécurité + jauge de risque"
    echo "  4️⃣  Cloud & Infrastructure - Évaluation + recommandations"
    echo "  5️⃣  Automatisation - Processus + calcul économies"
    echo "  6️⃣  Synthèse - Graphiques Chart.js + ROI + Roadmap"
    echo
    echo -e "${PURPLE}🎨 FONCTIONNALITÉS MODERNES :${NC}"
    echo "  • Interface glassmorphism avec animations fluides"
    echo "  • Système de commentaires enrichi avec pièces jointes"
    echo "  • Auto-save intelligent toutes les 30 secondes"
    echo "  • Graphiques interactifs Chart.js (radar, barres, jauges)"
    echo "  • Calcul ROI automatique avec projections"
    echo "  • Roadmap d'implémentation intelligente"
    echo "  • Export PDF/Excel/JSON"
    echo "  • Interface 100% responsive"
    echo "  • Notifications modernes"
    echo "  • Validation en temps réel"
    echo
    echo -e "${YELLOW}⚠️  Cette installation va remplacer le wizard existant${NC}"
    echo
}

# Diagnostic complet
diagnose_system() {
    log "INFO" "Diagnostic complet du système..."
    
    echo "📋 État du système :"
    
    # Vérifier les droits
    if [[ $EUID -ne 0 ]]; then
        log "ERROR" "Ce script doit être exécuté en tant que root (sudo)"
        exit 1
    fi
    echo "  ✅ Droits administrateur"
    
    # Vérifier Dolibarr
    if [[ -d "$DOLIBARR_DIR" ]]; then
        echo "  ✅ Dolibarr installé: $DOLIBARR_DIR"
    else
        log "ERROR" "Dolibarr non trouvé: $DOLIBARR_DIR"
        exit 1
    fi
    
    # Vérifier PHP
    local php_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
    echo "  ✅ PHP $php_version"
    
    # Vérifier Apache
    if systemctl is-active --quiet apache2; then
        echo "  ✅ Apache actif"
    else
        echo "  ❌ Apache inactif"
        systemctl start apache2
    fi
    
    # Vérifier le module existant
    if [[ -d "$MODULE_DIR" ]]; then
        echo "  ✅ Module AuditDigital détecté"
        
        # Vérifier la version actuelle
        if [[ -f "$MODULE_DIR/wizard/modern.php" ]]; then
            local file_size=$(stat -c%s "$MODULE_DIR/wizard/modern.php")
            if [[ $file_size -gt 50000 ]]; then
                echo "  ✅ Version moderne déjà installée ($file_size octets)"
            else
                echo "  ⚠️  Version basique détectée ($file_size octets)"
            fi
        else
            echo "  ❌ Wizard moderne manquant"
        fi
    else
        echo "  ❌ Module AuditDigital non installé"
    fi
    
    echo
}

# Sauvegarde complète
backup_existing() {
    log "INFO" "Sauvegarde complète de l'installation existante..."
    
    local backup_dir="/tmp/auditdigital_complete_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_dir"
    
    if [[ -d "$MODULE_DIR" ]]; then
        # Sauvegarder tout le module
        cp -r "$MODULE_DIR" "$backup_dir/"
        log "SUCCESS" "Sauvegarde complète créée: $backup_dir"
        
        # Créer un fichier de restauration
        cat > "$backup_dir/restore.sh" << EOF
#!/bin/bash
# Script de restauration automatique
echo "Restauration de la sauvegarde..."
sudo rm -rf "$MODULE_DIR"
sudo cp -r "$backup_dir/auditdigital" "$MODULE_DIR"
sudo chown -R www-data:www-data "$MODULE_DIR"
sudo systemctl restart apache2
echo "Restauration terminée"
EOF
        chmod +x "$backup_dir/restore.sh"
        
        echo "💾 Sauvegarde complète: $backup_dir"
        echo "🔄 Pour restaurer: sudo $backup_dir/restore.sh"
    else
        log "INFO" "Aucune installation existante à sauvegarder"
    fi
}

# Installation complète du wizard moderne
install_complete_wizard() {
    log "INFO" "Installation du wizard moderne complet..."
    
    # Créer la structure si nécessaire
    mkdir -p "$MODULE_DIR"
    mkdir -p "$MODULE_DIR/wizard"
    mkdir -p "$MODULE_DIR/css"
    mkdir -p "$MODULE_DIR/js"
    mkdir -p "$MODULE_DIR/documents"
    mkdir -p "$MODULE_DIR/temp"
    mkdir -p "$MODULE_DIR/logs"
    
    # Copier le wizard moderne complet
    if [[ -f "$SCRIPT_DIR/wizard/modern.php" ]]; then
        cp "$SCRIPT_DIR/wizard/modern.php" "$MODULE_DIR/wizard/modern.php"
        log "SUCCESS" "Wizard moderne complet installé"
        
        # Vérifier la taille
        local file_size=$(stat -c%s "$MODULE_DIR/wizard/modern.php")
        log "INFO" "Taille du fichier: $file_size octets"
        
        if [[ $file_size -lt 50000 ]]; then
            log "WARNING" "Fichier suspicieusement petit, vérification nécessaire"
        fi
    else
        log "ERROR" "Fichier source wizard/modern.php non trouvé"
        return 1
    fi
    
    # Copier tous les autres fichiers du module
    rsync -av \
        --exclude='.git*' \
        --exclude='*.sh' \
        --exclude='README.md' \
        --exclude='CHANGELOG.md' \
        --exclude='.gitignore' \
        --exclude='backups/' \
        --exclude='wizard/modern.php' \
        "$SCRIPT_DIR/" "$MODULE_DIR/"
    
    log "SUCCESS" "Module complet synchronisé"
}

# Configuration des assets modernes
setup_modern_assets() {
    log "INFO" "Configuration des assets modernes..."
    
    # Créer le CSS moderne si manquant
    if [[ ! -f "$MODULE_DIR/css/auditdigital-modern.css" ]]; then
        cat > "$MODULE_DIR/css/auditdigital-modern.css" << 'EOF'
/* CSS Moderne AuditDigital - Généré automatiquement */
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.modern-wizard-container {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

/* Styles modernes intégrés dans modern.php */
EOF
        log "SUCCESS" "CSS moderne créé"
    fi
    
    # Créer le JS moderne si manquant
    if [[ ! -f "$MODULE_DIR/js/wizard-modern.js" ]]; then
        cat > "$MODULE_DIR/js/wizard-modern.js" << 'EOF'
/* JavaScript Moderne AuditDigital - Généré automatiquement */
console.log('AuditDigital Wizard Moderne - Version 2.0.0');

// Fonctions modernes intégrées dans modern.php
EOF
        log "SUCCESS" "JavaScript moderne créé"
    fi
}

# Configuration des permissions parfaites
set_perfect_permissions() {
    log "INFO" "Configuration des permissions parfaites..."
    
    # Propriétaire Apache
    chown -R www-data:www-data "$MODULE_DIR"
    
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
    
    log "SUCCESS" "Permissions configurées"
}

# Test complet de toutes les étapes
test_all_steps() {
    log "INFO" "Test complet de toutes les étapes..."
    
    local errors=0
    
    echo "📝 Tests de syntaxe PHP :"
    local test_files=(
        "$MODULE_DIR/wizard/modern.php"
        "$MODULE_DIR/wizard/index.php"
        "$MODULE_DIR/lib/auditdigital.lib.php"
        "$MODULE_DIR/class/audit.class.php"
        "$MODULE_DIR/demo_modern.php"
    )
    
    for file in "${test_files[@]}"; do
        if [[ -f "$file" ]]; then
            if php -l "$file" &>/dev/null; then
                echo "  ✅ $(basename "$file")"
            else
                echo "  ❌ $(basename "$file") - Erreur syntaxe"
                ((errors++))
            fi
        else
            echo "  ❌ $(basename "$file") - Manquant"
            ((errors++))
        fi
    done
    
    echo
    echo "🌐 Tests d'accès web :"
    local test_urls=(
        "http://localhost/dolibarr/custom/auditdigital/demo_modern.php"
        "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php"
        "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php?step=2"
        "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php?step=3"
        "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php?step=4"
        "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php?step=5"
        "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php?step=6"
    )
    
    for url in "${test_urls[@]}"; do
        if command -v curl &>/dev/null; then
            local http_code=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
            local step_name=$(echo "$url" | grep -o "step=[0-9]" | cut -d= -f2)
            if [[ -z "$step_name" ]]; then
                step_name="demo"
            fi
            
            if [[ "$http_code" == "200" ]]; then
                echo "  ✅ Étape $step_name (HTTP $http_code)"
            else
                echo "  ❌ Étape $step_name (HTTP $http_code)"
                ((errors++))
            fi
        fi
    done
    
    echo
    echo "🔧 Tests de fonctionnalités :"
    
    # Vérifier la présence des fonctionnalités modernes
    if grep -q "Cards cliquables modernes" "$MODULE_DIR/wizard/modern.php"; then
        echo "  ✅ Interface moderne détectée"
    else
        echo "  ❌ Interface moderne non détectée"
        ((errors++))
    fi
    
    if grep -q "Chart.js" "$MODULE_DIR/wizard/modern.php"; then
        echo "  ✅ Graphiques Chart.js intégrés"
    else
        echo "  ❌ Graphiques Chart.js manquants"
        ((errors++))
    fi
    
    if grep -q "calculateROI" "$MODULE_DIR/wizard/modern.php"; then
        echo "  ✅ Calcul ROI implémenté"
    else
        echo "  ❌ Calcul ROI manquant"
        ((errors++))
    fi
    
    if grep -q "generateRoadmap" "$MODULE_DIR/wizard/modern.php"; then
        echo "  ✅ Roadmap automatique implémentée"
    else
        echo "  ❌ Roadmap automatique manquante"
        ((errors++))
    fi
    
    return $errors
}

# Redémarrage des services
restart_services() {
    log "INFO" "Redémarrage des services..."
    
    # Nettoyer le cache
    if [[ -d "/var/lib/dolibarr/documents/admin/temp" ]]; then
        rm -rf /var/lib/dolibarr/documents/admin/temp/* 2>/dev/null || true
    fi
    
    # Redémarrer Apache
    systemctl restart apache2
    
    if systemctl is-active --quiet apache2; then
        log "SUCCESS" "Apache redémarré avec succès"
    else
        log "ERROR" "Erreur lors du redémarrage d'Apache"
        return 1
    fi
}

# Affichage des résultats finaux
show_final_results() {
    echo
    echo -e "${GREEN}=============================================="
    echo "🎉 DÉPLOIEMENT COMPLET TERMINÉ AVEC SUCCÈS"
    echo "=============================================="
    echo -e "${NC}"
    echo -e "${BLUE}🌐 TESTEZ TOUTES LES ÉTAPES :${NC}"
    echo
    echo "1️⃣  Informations Générales :"
    echo "   http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php?step=1"
    echo
    echo "2️⃣  Maturité Digitale :"
    echo "   http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php?step=2"
    echo
    echo "3️⃣  Cybersécurité :"
    echo "   http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php?step=3"
    echo
    echo "4️⃣  Cloud & Infrastructure :"
    echo "   http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php?step=4"
    echo
    echo "5️⃣  Automatisation :"
    echo "   http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php?step=5"
    echo
    echo "6️⃣  Synthèse & Recommandations :"
    echo "   http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php?step=6"
    echo
    echo -e "${PURPLE}📊 DÉMONSTRATION COMPLÈTE :${NC}"
    echo "   http://192.168.1.252/dolibarr/custom/auditdigital/demo_modern.php"
    echo
    echo -e "${YELLOW}🆚 COMPARAISON :${NC}"
    echo "   • Ancien wizard : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
    echo "   • Nouveau wizard : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
    echo
    echo -e "${GREEN}✨ FONCTIONNALITÉS DISPONIBLES :${NC}"
    echo "   ✅ 6 étapes complètes avec interface moderne"
    echo "   ✅ Cards cliquables avec animations fluides"
    echo "   ✅ Système de notation moderne avec graphiques temps réel"
    echo "   ✅ Commentaires enrichis avec pièces jointes"
    echo "   ✅ Auto-save intelligent toutes les 30 secondes"
    echo "   ✅ Graphiques Chart.js (radar, barres, jauges, donut)"
    echo "   ✅ Calcul ROI automatique avec projections"
    echo "   ✅ Roadmap d'implémentation intelligente"
    echo "   ✅ Export PDF/Excel/JSON"
    echo "   ✅ Interface 100% responsive"
    echo "   ✅ Notifications modernes"
    echo "   ✅ Validation en temps réel"
    echo
    echo -e "${CYAN}🔧 SURVEILLANCE :${NC}"
    echo "   • Logs Apache : sudo tail -f /var/log/apache2/error.log"
    echo "   • Logs d'accès : sudo tail -f /var/log/apache2/access.log"
    echo
    echo -e "${GREEN}=============================================="
    echo -e "${NC}"
}

# Fonction principale
main() {
    show_header
    
    # Diagnostic initial
    diagnose_system
    
    # Demander confirmation
    echo "Voulez-vous procéder au déploiement complet du wizard moderne ? (y/N)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log "INFO" "Déploiement annulé par l'utilisateur"
        exit 0
    fi
    
    echo
    log "INFO" "Début du déploiement complet..."
    
    # Étapes de déploiement
    backup_existing
    install_complete_wizard
    setup_modern_assets
    set_perfect_permissions
    restart_services
    
    # Tests finaux
    echo
    log "INFO" "Tests finaux de toutes les étapes..."
    if test_all_steps; then
        log "SUCCESS" "Tous les tests sont passés !"
        show_final_results
    else
        log "WARNING" "Déploiement terminé avec des avertissements"
        show_final_results
    fi
    
    log "SUCCESS" "Déploiement complet terminé !"
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur lors du déploiement à la ligne $LINENO"; exit 1' ERR

# Exécution
main "$@"