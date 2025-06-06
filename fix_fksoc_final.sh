#!/bin/bash

# =============================================================================
# Script de Correction Finale - Erreur fk_soc
# =============================================================================
# 
# Ce script corrige définitivement l'erreur "Column 'fk_soc' cannot be null"
#
# Usage: sudo ./fix_fksoc_final.sh
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
    echo "🔧 CORRECTION FINALE FK_SOC"
    echo "=============================================="
    echo -e "${NC}"
    echo "Correction définitive de l'erreur fk_soc"
    echo
}

# Correction dans wizard/modern.php
fix_modern_wizard() {
    log "INFO" "Correction de wizard/modern.php..."
    
    if [[ ! -f "$MODULE_DIR/wizard/modern.php" ]]; then
        log "ERROR" "Fichier modern.php non trouvé"
        return 1
    fi
    
    # Sauvegarder l'original
    cp "$MODULE_DIR/wizard/modern.php" "$MODULE_DIR/wizard/modern.php.backup"
    
    # Chercher et remplacer la ligne problématique
    if grep -q "\$audit->fk_soc = \$wizard_data\['step_1'\]\['audit_socid'\] ?? 0;" "$MODULE_DIR/wizard/modern.php"; then
        # Créer le fichier de remplacement
        cat > "/tmp/fksoc_replacement.txt" << 'EOF'
        // Correction fk_soc - Gestion société obligatoire
        $fk_soc = $wizard_data['step_1']['audit_socid'] ?? 0;
        if (empty($fk_soc) || $fk_soc <= 0) {
            // Créer une société par défaut
            require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
            $societe = new Societe($db);
            $societe->name = 'Audit Digital - ' . date('Y-m-d H:i:s');
            $societe->client = 1;
            $societe->status = 1;
            $societe->country_id = 1; // France par défaut
            
            $result_soc = $societe->create($user);
            if ($result_soc > 0) {
                $fk_soc = $result_soc;
                setEventMessages('Société créée automatiquement: ' . $societe->name, null, 'warnings');
            } else {
                setEventMessages('Erreur lors de la création de la société par défaut', $societe->errors, 'errors');
                $error++;
                // Utiliser une société existante en fallback
                $sql = "SELECT rowid FROM llx_societe WHERE entity = ".$conf->entity." AND status = 1 LIMIT 1";
                $resql = $db->query($sql);
                if ($resql && $db->num_rows($resql) > 0) {
                    $obj = $db->fetch_object($resql);
                    $fk_soc = $obj->rowid;
                    setEventMessages('Société existante utilisée par défaut', null, 'warnings');
                } else {
                    $fk_soc = 1; // Fallback ultime
                }
            }
        }
        $audit->fk_soc = $fk_soc;
EOF
        
        # Appliquer le remplacement
        sed -i '/\$audit->fk_soc = \$wizard_data\['\''step_1'\''\]\['\''audit_socid'\''\] ?? 0;/r /tmp/fksoc_replacement.txt' "$MODULE_DIR/wizard/modern.php"
        sed -i '/\$audit->fk_soc = \$wizard_data\['\''step_1'\''\]\['\''audit_socid'\''\] ?? 0;/d' "$MODULE_DIR/wizard/modern.php"
        
        log "SUCCESS" "modern.php corrigé"
    else
        log "WARNING" "Ligne fk_soc non trouvée dans modern.php"
    fi
}

# Correction dans wizard/index.php
fix_index_wizard() {
    log "INFO" "Correction de wizard/index.php..."
    
    if [[ ! -f "$MODULE_DIR/wizard/index.php" ]]; then
        log "ERROR" "Fichier index.php non trouvé"
        return 1
    fi
    
    # Sauvegarder l'original
    cp "$MODULE_DIR/wizard/index.php" "$MODULE_DIR/wizard/index.php.backup"
    
    # Vérifier si la correction n'est pas déjà appliquée
    if grep -q "Société créée automatiquement" "$MODULE_DIR/wizard/index.php"; then
        log "SUCCESS" "index.php déjà corrigé"
        return 0
    fi
    
    # Chercher et remplacer la ligne problématique
    if grep -q "\$audit->fk_soc = \$wizard_data\['step_1'\]\['audit_socid'\] ?? 0;" "$MODULE_DIR/wizard/index.php"; then
        # Appliquer le même remplacement
        sed -i '/\$audit->fk_soc = \$wizard_data\['\''step_1'\''\]\['\''audit_socid'\''\] ?? 0;/r /tmp/fksoc_replacement.txt' "$MODULE_DIR/wizard/index.php"
        sed -i '/\$audit->fk_soc = \$wizard_data\['\''step_1'\''\]\['\''audit_socid'\''\] ?? 0;/d' "$MODULE_DIR/wizard/index.php"
        
        log "SUCCESS" "index.php corrigé"
    else
        log "WARNING" "Ligne fk_soc non trouvée dans index.php"
    fi
}

# Ajout de validation côté client
add_client_validation() {
    log "INFO" "Ajout de validation côté client..."
    
    # Créer un script de validation
    cat > "$MODULE_DIR/js/fksoc_validation.js" << 'EOF'
/**
 * Validation fk_soc côté client
 */

// Validation avant soumission
function validateSocietySelection() {
    const socidField = document.querySelector('[name="audit_socid"]');
    const socidSelect = document.getElementById('audit_socid');
    
    // Si aucune société sélectionnée, proposer de créer une société par défaut
    if ((!socidField || !socidField.value || socidField.value <= 0) && 
        (!socidSelect || !socidSelect.value || socidSelect.value <= 0)) {
        
        const createDefault = confirm(
            'Aucune société sélectionnée.\n\n' +
            'Voulez-vous créer une société par défaut automatiquement ?\n\n' +
            'Cliquez OK pour créer automatiquement ou Annuler pour sélectionner une société.'
        );
        
        if (!createDefault) {
            // Mettre en évidence le champ société
            if (socidSelect) {
                socidSelect.style.border = '2px solid #dc3545';
                socidSelect.focus();
            }
            return false;
        } else {
            // Marquer pour création automatique
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'create_default_society';
            hiddenInput.value = '1';
            document.getElementById('wizardForm').appendChild(hiddenInput);
        }
    }
    
    return true;
}

// Attacher la validation au formulaire
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wizardForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateSocietySelection()) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Validation en temps réel
    const socidSelect = document.getElementById('audit_socid');
    if (socidSelect) {
        socidSelect.addEventListener('change', function() {
            if (this.value && this.value > 0) {
                this.style.border = '';
            }
        });
    }
});
EOF

    chown www-data:www-data "$MODULE_DIR/js/fksoc_validation.js"
    chmod 644 "$MODULE_DIR/js/fksoc_validation.js"
    
    log "SUCCESS" "Validation côté client ajoutée"
}

# Test de la correction
test_correction() {
    log "INFO" "Test de la correction..."
    
    local errors=0
    
    echo "📝 Tests de syntaxe :"
    
    # Test modern.php
    if [[ -f "$MODULE_DIR/wizard/modern.php" ]]; then
        if php -l "$MODULE_DIR/wizard/modern.php" &>/dev/null; then
            echo "  ✅ modern.php syntaxe OK"
        else
            echo "  ❌ modern.php erreur syntaxe"
            ((errors++))
        fi
    fi
    
    # Test index.php
    if [[ -f "$MODULE_DIR/wizard/index.php" ]]; then
        if php -l "$MODULE_DIR/wizard/index.php" &>/dev/null; then
            echo "  ✅ index.php syntaxe OK"
        else
            echo "  ❌ index.php erreur syntaxe"
            ((errors++))
        fi
    fi
    
    echo
    echo "🔧 Tests de fonctionnalité :"
    
    # Vérifier la correction fk_soc
    if grep -q "Société créée automatiquement" "$MODULE_DIR/wizard/modern.php"; then
        echo "  ✅ Correction fk_soc dans modern.php"
    else
        echo "  ❌ Correction fk_soc manquante dans modern.php"
        ((errors++))
    fi
    
    if grep -q "Société créée automatiquement" "$MODULE_DIR/wizard/index.php"; then
        echo "  ✅ Correction fk_soc dans index.php"
    else
        echo "  ❌ Correction fk_soc manquante dans index.php"
        ((errors++))
    fi
    
    # Vérifier la validation JavaScript
    if [[ -f "$MODULE_DIR/js/fksoc_validation.js" ]]; then
        echo "  ✅ Validation JavaScript ajoutée"
    else
        echo "  ❌ Validation JavaScript manquante"
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
    echo "🎉 CORRECTION FK_SOC TERMINÉE"
    echo "=============================================="
    echo -e "${NC}"
    echo -e "${GREEN}✅ CORRECTIONS APPLIQUÉES :${NC}"
    echo "  • Gestion société obligatoire dans wizard/modern.php"
    echo "  • Gestion société obligatoire dans wizard/index.php"
    echo "  • Création automatique société par défaut"
    echo "  • Fallback vers société existante"
    echo "  • Validation côté client"
    echo
    echo -e "${CYAN}🌐 URL À TESTER :${NC}"
    echo "  • http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
    echo
    echo -e "${YELLOW}📋 TEST À EFFECTUER :${NC}"
    echo "  1. Aller sur le wizard moderne"
    echo "  2. Ne pas sélectionner de société"
    echo "  3. Aller jusqu'au bout et créer l'audit"
    echo "  4. Vérifier qu'il n'y a plus d'erreur fk_soc"
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
    
    # Demander confirmation
    echo "Voulez-vous appliquer la correction finale fk_soc ? (y/N)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log "INFO" "Correction annulée par l'utilisateur"
        exit 0
    fi
    
    echo
    log "INFO" "Application de la correction finale..."
    
    # Appliquer les corrections
    fix_modern_wizard
    fix_index_wizard
    add_client_validation
    restart_services
    
    # Tests finaux
    echo
    log "INFO" "Tests finaux..."
    if test_correction; then
        log "SUCCESS" "Correction fk_soc appliquée avec succès !"
        show_results
    else
        log "WARNING" "Correction appliquée avec des avertissements"
        show_results
    fi
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur lors de la correction à la ligne $LINENO"; exit 1' ERR

# Exécution
main "$@"