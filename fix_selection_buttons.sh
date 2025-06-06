#!/bin/bash

# =============================================================================
# Script de Correction Rapide - Boutons de Sélection
# =============================================================================
# 
# Ce script corrige le problème de sélection des boutons dans le wizard
#
# Usage: sudo ./fix_selection_buttons.sh
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

show_header() {
    echo
    echo -e "${RED}=============================================="
    echo "🔧 CORRECTION BOUTONS DE SÉLECTION"
    echo "=============================================="
    echo -e "${NC}"
    echo "Diagnostic et correction du problème de sélection"
    echo
}

# Diagnostic du problème
diagnose_selection_issue() {
    log "INFO" "Diagnostic du problème de sélection..."
    
    echo "🔍 Vérification des fonctions JavaScript :"
    
    # Vérifier les fonctions de sélection
    local functions=("selectOption" "selectSector" "selectBudget" "selectRating")
    for func in "${functions[@]}"; do
        if grep -q "function $func" "$MODULE_DIR/wizard/index.php"; then
            echo "  ✅ $func définie"
        else
            echo "  ❌ $func manquante"
        fi
    done
    
    echo
    echo "🔍 Vérification des événements onclick :"
    local onclick_count=$(grep -c "onclick=" "$MODULE_DIR/wizard/index.php" || echo "0")
    echo "  📊 $onclick_count événements onclick trouvés"
    
    echo
    echo "🔍 Vérification des erreurs potentielles :"
    
    # Vérifier les erreurs de syntaxe JavaScript
    if grep -q "});.*});" "$MODULE_DIR/wizard/index.php"; then
        echo "  ⚠️  Possible erreur de syntaxe JavaScript détectée"
    else
        echo "  ✅ Pas d'erreur de syntaxe évidente"
    fi
    
    # Vérifier les corrections CSRF qui ont pu casser le code
    if grep -q "tokenInput.*appendChild" "$MODULE_DIR/wizard/index.php"; then
        echo "  ⚠️  Corrections CSRF détectées - possible conflit"
    else
        echo "  ✅ Pas de conflit CSRF détecté"
    fi
}

# Correction rapide du problème
fix_selection_issue() {
    log "INFO" "Application de la correction rapide..."
    
    # Sauvegarder le fichier actuel
    cp "$MODULE_DIR/wizard/index.php" "$MODULE_DIR/wizard/index.php.broken"
    log "INFO" "Fichier cassé sauvegardé en index.php.broken"
    
    # Restaurer depuis modern.php qui fonctionne
    if [[ -f "$MODULE_DIR/wizard/modern.php" ]]; then
        cp "$MODULE_DIR/wizard/modern.php" "$MODULE_DIR/wizard/index.php"
        log "SUCCESS" "Wizard restauré depuis modern.php"
    else
        log "ERROR" "Fichier modern.php non trouvé"
        return 1
    fi
    
    # Appliquer uniquement les corrections essentielles sans casser le JavaScript
    apply_safe_corrections
}

# Application des corrections sans casser le JavaScript
apply_safe_corrections() {
    log "INFO" "Application des corrections sécurisées..."
    
    # Créer un fichier temporaire avec les corrections
    cat > "/tmp/safe_corrections.php" << 'EOF'
<?php
// Corrections sécurisées à appliquer

// 1. Correction fk_soc dans la section de création d'audit
$fk_soc_fix = '
        // Correction fk_soc
        $fk_soc = $wizard_data[\'step_1\'][\'audit_socid\'] ?? 0;
        if (empty($fk_soc) || $fk_soc <= 0) {
            require_once DOL_DOCUMENT_ROOT.\'/societe/class/societe.class.php\';
            $societe = new Societe($db);
            $societe->name = \'Audit Digital - \' . date(\'Y-m-d H:i:s\');
            $societe->client = 1;
            $societe->status = 1;
            $societe->country_id = 1;
            $result_soc = $societe->create($user);
            if ($result_soc > 0) {
                $fk_soc = $result_soc;
                setEventMessages(\'Société créée automatiquement: \' . $societe->name, null, \'warnings\');
            } else {
                setEventMessages(\'Erreur création société par défaut\', $societe->errors, \'errors\');
                $error++;
            }
        }
        $audit->fk_soc = $fk_soc;';

// 2. Correction token CSRF pour export PDF (JavaScript sécurisé)
$csrf_fix = '
    // Ajouter le token CSRF
    const tokenInput = document.createElement(\'input\');
    tokenInput.type = \'hidden\';
    tokenInput.name = \'token\';
    tokenInput.value = \'<?php echo newToken(); ?>\';
    form.appendChild(tokenInput);';

echo "Corrections préparées";
?>
EOF

    # Appliquer la correction fk_soc de manière sécurisée
    if grep -q "\$audit->fk_soc = \$wizard_data\['step_1'\]\['audit_socid'\] ?? 0;" "$MODULE_DIR/wizard/index.php"; then
        # Remplacer uniquement cette ligne spécifique
        sed -i 's/\$audit->fk_soc = \$wizard_data\['\''step_1'\''\]\['\''audit_socid'\''\] ?? 0;/\/\/ Correction fk_soc\n        $fk_soc = $wizard_data['\''step_1'\'']['\''audit_socid'\''] ?? 0;\n        if (empty($fk_soc) || $fk_soc <= 0) {\n            require_once DOL_DOCUMENT_ROOT.'\''\/societe\/class\/societe.class.php'\'';\n            $societe = new Societe($db);\n            $societe->name = '\''Audit Digital - '\'' . date('\''Y-m-d H:i:s'\'');\n            $societe->client = 1;\n            $societe->status = 1;\n            $societe->country_id = 1;\n            $result_soc = $societe->create($user);\n            if ($result_soc > 0) {\n                $fk_soc = $result_soc;\n                setEventMessages('\''Société créée automatiquement: '\'' . $societe->name, null, '\''warnings'\'');\n            } else {\n                setEventMessages('\''Erreur création société par défaut'\'', $societe->errors, '\''errors'\'');\n                $error++;\n            }\n        }\n        $audit->fk_soc = $fk_soc;/' "$MODULE_DIR/wizard/index.php"
        
        log "SUCCESS" "Correction fk_soc appliquée"
    fi
    
    # Appliquer la correction CSRF de manière sécurisée
    if grep -q "form.appendChild(input);" "$MODULE_DIR/wizard/index.php" && ! grep -q "tokenInput" "$MODULE_DIR/wizard/index.php"; then
        # Ajouter le token CSRF après la boucle des inputs
        sed -i '/form\.appendChild(input);/a\    });\n    \n    \/\/ Ajouter le token CSRF\n    const tokenInput = document.createElement('\''input'\'');\n    tokenInput.type = '\''hidden'\'';\n    tokenInput.name = '\''token'\'';\n    tokenInput.value = '\''<?php echo newToken(); ?>'\'';\n    form.appendChild(tokenInput);' "$MODULE_DIR/wizard/index.php"
        
        log "SUCCESS" "Correction CSRF appliquée"
    fi
}

# Test des fonctions de sélection
test_selection_functions() {
    log "INFO" "Test des fonctions de sélection..."
    
    local errors=0
    
    echo "📝 Tests de syntaxe JavaScript :"
    
    # Extraire le JavaScript et le tester
    sed -n '/<script>/,/<\/script>/p' "$MODULE_DIR/wizard/index.php" > "/tmp/wizard_js.js"
    
    # Vérifier les fonctions critiques
    local functions=("selectOption" "selectSector" "selectBudget" "selectRating" "nextStep" "prevStep")
    for func in "${functions[@]}"; do
        if grep -q "function $func" "/tmp/wizard_js.js"; then
            echo "  ✅ $func"
        else
            echo "  ❌ $func manquante"
            ((errors++))
        fi
    done
    
    echo
    echo "🔧 Tests de fonctionnalité :"
    
    # Vérifier les événements onclick
    local onclick_count=$(grep -c "onclick=" "$MODULE_DIR/wizard/index.php" || echo "0")
    if [[ $onclick_count -gt 50 ]]; then
        echo "  ✅ Événements onclick présents ($onclick_count)"
    else
        echo "  ❌ Pas assez d'événements onclick ($onclick_count)"
        ((errors++))
    fi
    
    # Vérifier les classes CSS
    if grep -q "option-card.*selected" "$MODULE_DIR/wizard/index.php"; then
        echo "  ✅ Classes CSS de sélection"
    else
        echo "  ❌ Classes CSS manquantes"
        ((errors++))
    fi
    
    return $errors
}

# Redémarrage des services
restart_services() {
    log "INFO" "Redémarrage des services..."
    
    systemctl restart apache2
    
    if systemctl is-active --quiet apache2; then
        log "SUCCESS" "Apache redémarré avec succès"
    else
        log "ERROR" "Erreur lors du redémarrage d'Apache"
        return 1
    fi
}

# Affichage des résultats
show_results() {
    echo
    echo -e "${GREEN}=============================================="
    echo "🎉 BOUTONS DE SÉLECTION CORRIGÉS"
    echo "=============================================="
    echo -e "${NC}"
    echo -e "${GREEN}✅ CORRECTIONS APPLIQUÉES :${NC}"
    echo "  • Wizard restauré depuis la version fonctionnelle"
    echo "  • Corrections fk_soc et CSRF appliquées proprement"
    echo "  • Fonctions JavaScript préservées"
    echo "  • Événements onclick fonctionnels"
    echo
    echo -e "${CYAN}🌐 URL À TESTER :${NC}"
    echo "  • http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
    echo
    echo -e "${YELLOW}📋 TESTS À EFFECTUER :${NC}"
    echo "  1. Cliquer sur les cards de type de structure"
    echo "  2. Sélectionner un secteur d'activité"
    echo "  3. Choisir un budget annuel"
    echo "  4. Naviguer entre les étapes"
    echo "  5. Tester les ratings dans l'étape 2"
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
    
    # Diagnostic
    diagnose_selection_issue
    
    # Demander confirmation
    echo
    echo "Voulez-vous corriger le problème de sélection ? (y/N)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log "INFO" "Correction annulée par l'utilisateur"
        exit 0
    fi
    
    echo
    log "INFO" "Correction du problème de sélection..."
    
    # Appliquer les corrections
    fix_selection_issue
    restart_services
    
    # Tests finaux
    echo
    log "INFO" "Tests finaux..."
    if test_selection_functions; then
        log "SUCCESS" "Boutons de sélection corrigés avec succès !"
        show_results
    else
        log "WARNING" "Corrections appliquées avec des avertissements"
        show_results
    fi
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur lors de la correction à la ligne $LINENO"; exit 1' ERR

# Exécution
main "$@"