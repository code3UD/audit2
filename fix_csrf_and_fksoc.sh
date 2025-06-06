#!/bin/bash

# =============================================================================
# Script de Correction - Erreurs CSRF et fk_soc
# =============================================================================
# 
# Ce script corrige les deux problèmes identifiés :
# 1. Erreur CSRF pour l'export PDF (Token not provided)
# 2. Erreur SQL fk_soc cannot be null
#
# Usage: sudo ./fix_csrf_and_fksoc.sh
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
    echo "🔧 CORRECTION ERREURS CSRF ET FK_SOC"
    echo "=============================================="
    echo -e "${NC}"
    echo "Ce script corrige les erreurs spécifiques :"
    echo
    echo -e "${RED}❌ PROBLÈMES IDENTIFIÉS :${NC}"
    echo "  1. Export PDF : Token CSRF not provided"
    echo "  2. Création audit : Column 'fk_soc' cannot be null"
    echo
    echo -e "${GREEN}✅ CORRECTIONS À APPLIQUER :${NC}"
    echo "  • Ajout token CSRF dans export PDF"
    echo "  • Validation champ société obligatoire"
    echo "  • Société par défaut si non sélectionnée"
    echo "  • Amélioration validation formulaire"
    echo
}

# Correction de l'erreur CSRF pour l'export PDF
fix_csrf_export() {
    log "INFO" "Correction de l'erreur CSRF pour l'export PDF..."
    
    # Corriger le fichier export_pdf.php
    cat > "$MODULE_DIR/export_pdf.php" << 'EOF'
<?php
/**
 * Export PDF pour AuditDigital - Version corrigée CSRF
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; 
$tmp2 = realpath(__FILE__); 
$i = strlen($tmp) - 1; 
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Vérification des droits
if (!$user->rights->auditdigital->audit->read) {
    accessforbidden();
}

// Vérification du token CSRF
if (!verifCsrfToken()) {
    httponly_accessforbidden('CSRF check failed');
}

// Récupérer les données POST
$global_score = GETPOST('global_score', 'int');
$digital_score = GETPOST('digital_score', 'int');
$security_score = GETPOST('security_score', 'int');
$cloud_score = GETPOST('cloud_score', 'int');
$automation_score = GETPOST('automation_score', 'int');
$audit_data = GETPOST('audit_data', 'alpha');

// Validation des données
if (empty($global_score) && empty($digital_score) && empty($security_score)) {
    setEventMessages('Aucune donnée à exporter', null, 'errors');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Créer un PDF simple
$pdf = pdf_getInstance();
if (!$pdf) {
    setEventMessages('Erreur lors de la création du PDF', null, 'errors');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$pdf->Open();
$pdf->AddPage();

// En-tête avec logo
$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor(102, 126, 234); // Couleur primaire
$pdf->Cell(0, 15, 'RAPPORT AUDIT DIGITAL', 0, 1, 'C');
$pdf->Ln(5);

// Ligne de séparation
$pdf->SetDrawColor(102, 126, 234);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(10);

// Informations générales
$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, 'Date du rapport: ' . date('d/m/Y H:i'), 0, 1);
$pdf->Cell(0, 8, 'Genere par: ' . $user->getFullName($langs), 0, 1);
$pdf->Ln(10);

// Score global avec encadré
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetFillColor(240, 247, 255);
$pdf->SetDrawColor(102, 126, 234);
$pdf->Rect(20, $pdf->GetY(), 170, 20, 'DF');
$pdf->Cell(0, 20, 'SCORE GLOBAL: ' . $global_score . '%', 0, 1, 'C');
$pdf->Ln(5);

// Niveau de maturité
$level = 'Debutant';
if ($global_score >= 85) $level = 'Leader';
elseif ($global_score >= 70) $level = 'Expert';
elseif ($global_score >= 50) $level = 'Avance';
elseif ($global_score >= 30) $level = 'Intermediaire';

$pdf->SetFont('Arial', 'I', 14);
$pdf->Cell(0, 10, 'Niveau de maturite: ' . $level, 0, 1, 'C');
$pdf->Ln(10);

// Scores détaillés
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'RESULTATS PAR DOMAINE', 0, 1);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$scores = [
    'Maturite Digitale' => $digital_score,
    'Cybersecurite' => $security_score,
    'Cloud & Infrastructure' => $cloud_score,
    'Automatisation' => $automation_score
];

foreach ($scores as $domain => $score) {
    // Barre de progression
    $pdf->Cell(80, 8, $domain . ':', 0, 0);
    
    // Barre de fond
    $pdf->SetFillColor(233, 236, 239);
    $pdf->Rect($pdf->GetX(), $pdf->GetY() + 1, 80, 6, 'F');
    
    // Barre de score
    $width = ($score / 100) * 80;
    if ($score >= 70) $pdf->SetFillColor(40, 167, 69); // Vert
    elseif ($score >= 50) $pdf->SetFillColor(255, 193, 7); // Jaune
    else $pdf->SetFillColor(220, 53, 69); // Rouge
    
    $pdf->Rect($pdf->GetX(), $pdf->GetY() + 1, $width, 6, 'F');
    
    // Pourcentage
    $pdf->Cell(80, 8, '', 0, 0);
    $pdf->Cell(20, 8, $score . '%', 0, 1, 'R');
}

$pdf->Ln(10);

// Recommandations
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'RECOMMANDATIONS PRIORITAIRES', 0, 1);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 11);
$recommendations = [];

if ($digital_score < 50) {
    $recommendations[] = '• Digitaliser les processus papier existants';
    $recommendations[] = '• Deployer des outils collaboratifs modernes';
}
if ($security_score < 70) {
    $recommendations[] = '• Renforcer les mesures de cybersecurite';
    $recommendations[] = '• Former les equipes aux bonnes pratiques';
}
if ($cloud_score < 60) {
    $recommendations[] = '• Evaluer une migration vers le cloud';
    $recommendations[] = '• Optimiser l\'infrastructure existante';
}
if ($automation_score < 50) {
    $recommendations[] = '• Automatiser les processus repetitifs';
    $recommendations[] = '• Implementer des workflows numeriques';
}

if (empty($recommendations)) {
    $recommendations[] = '• Maintenir le niveau d\'excellence atteint';
    $recommendations[] = '• Explorer les innovations emergentes';
}

foreach ($recommendations as $rec) {
    $pdf->Cell(0, 6, $rec, 0, 1);
}

$pdf->Ln(10);

// ROI estimé
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'ANALYSE ROI', 0, 1);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$investment = ($global_score < 50) ? 45000 : 25000;
$savings = ($global_score < 50) ? 78000 : 45000;
$roi = round((($savings * 3 - $investment) / $investment) * 100);

$pdf->Cell(0, 8, 'Investissement recommande: ' . number_format($investment, 0, ',', ' ') . ' EUR', 0, 1);
$pdf->Cell(0, 8, 'Economies estimees (annuelles): ' . number_format($savings, 0, ',', ' ') . ' EUR', 0, 1);
$pdf->Cell(0, 8, 'ROI sur 3 ans: ' . $roi . '%', 0, 1);

// Pied de page
$pdf->SetY(-30);
$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 10, 'Rapport genere par AuditDigital - ' . date('d/m/Y H:i:s'), 0, 0, 'C');

// Télécharger le PDF
$filename = 'audit-digital-' . date('Y-m-d-H-i') . '.pdf';
$pdf->Output($filename, 'D');
exit;
EOF

    chown www-data:www-data "$MODULE_DIR/export_pdf.php"
    chmod 644 "$MODULE_DIR/export_pdf.php"
    
    log "SUCCESS" "Export PDF corrigé avec token CSRF"
}

# Correction de l'erreur fk_soc
fix_fksoc_error() {
    log "INFO" "Correction de l'erreur fk_soc cannot be null..."
    
    # Créer un patch pour le wizard
    cat > "/tmp/wizard_fksoc_patch.php" << 'EOF'
// Correction pour fk_soc - À insérer dans wizard/index.php et modern.php

// Dans la section de création d'audit, remplacer :
// $audit->fk_soc = $wizard_data['step_1']['audit_socid'] ?? 0;

// Par :
$fk_soc = $wizard_data['step_1']['audit_socid'] ?? 0;
if (empty($fk_soc) || $fk_soc <= 0) {
    // Créer une société par défaut si aucune n'est sélectionnée
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
    }
}
$audit->fk_soc = $fk_soc;
EOF

    log "SUCCESS" "Patch fk_soc créé"
}

# Application des corrections dans les fichiers wizard
apply_wizard_corrections() {
    log "INFO" "Application des corrections dans les wizards..."
    
    # Correction du wizard principal (index.php)
    if [[ -f "$MODULE_DIR/wizard/index.php" ]]; then
        # Sauvegarder l'original
        cp "$MODULE_DIR/wizard/index.php" "$MODULE_DIR/wizard/index.php.backup"
        
        # Appliquer les corrections
        sed -i 's/\$audit->fk_soc = \$wizard_data\['\''step_1'\''\]\['\''audit_socid'\''\] ?? 0;/\/\/ Correction fk_soc\n        $fk_soc = $wizard_data['\''step_1'\'']['\''audit_socid'\''] ?? 0;\n        if (empty($fk_soc) || $fk_soc <= 0) {\n            require_once DOL_DOCUMENT_ROOT.'\''\/societe\/class\/societe.class.php'\'';\n            $societe = new Societe($db);\n            $societe->name = '\''Audit Digital - '\'' . date('\''Y-m-d H:i:s'\'');\n            $societe->client = 1;\n            $societe->status = 1;\n            $societe->country_id = 1;\n            $result_soc = $societe->create($user);\n            if ($result_soc > 0) {\n                $fk_soc = $result_soc;\n                setEventMessages('\''Société créée automatiquement: '\'' . $societe->name, null, '\''warnings'\'');\n            } else {\n                setEventMessages('\''Erreur création société par défaut'\'', $societe->errors, '\''errors'\'');\n                $error++;\n            }\n        }\n        $audit->fk_soc = $fk_soc;/' "$MODULE_DIR/wizard/index.php"
        
        # Correction du token CSRF pour l'export PDF
        sed -i 's/form\.action = '\''<?php echo dol_buildpath.*export_pdf\.php.*?>'\'';/form.action = '\''<?php echo dol_buildpath("\/auditdigital\/export_pdf.php", 1); ?>'\'';/' "$MODULE_DIR/wizard/index.php"
        
        # Ajouter le token CSRF dans le formulaire d'export
        sed -i '/input\.name = key;/a\    });\n    \n    \/\/ Ajouter le token CSRF\n    const tokenInput = document.createElement('\''input'\'');\n    tokenInput.type = '\''hidden'\'';\n    tokenInput.name = '\''token'\'';\n    tokenInput.value = '\''<?php echo newToken(); ?>'\'';\n    form.appendChild(tokenInput);' "$MODULE_DIR/wizard/index.php"
        
        log "SUCCESS" "Wizard principal corrigé"
    fi
    
    # Correction du wizard moderne (modern.php)
    if [[ -f "$MODULE_DIR/wizard/modern.php" ]]; then
        # Sauvegarder l'original
        cp "$MODULE_DIR/wizard/modern.php" "$MODULE_DIR/wizard/modern.php.backup"
        
        # Appliquer les mêmes corrections
        sed -i 's/\$audit->fk_soc = \$wizard_data\['\''step_1'\''\]\['\''audit_socid'\''\] ?? 0;/\/\/ Correction fk_soc\n        $fk_soc = $wizard_data['\''step_1'\'']['\''audit_socid'\''] ?? 0;\n        if (empty($fk_soc) || $fk_soc <= 0) {\n            require_once DOL_DOCUMENT_ROOT.'\''\/societe\/class\/societe.class.php'\'';\n            $societe = new Societe($db);\n            $societe->name = '\''Audit Digital - '\'' . date('\''Y-m-d H:i:s'\'');\n            $societe->client = 1;\n            $societe->status = 1;\n            $societe->country_id = 1;\n            $result_soc = $societe->create($user);\n            if ($result_soc > 0) {\n                $fk_soc = $result_soc;\n                setEventMessages('\''Société créée automatiquement: '\'' . $societe->name, null, '\''warnings'\'');\n            } else {\n                setEventMessages('\''Erreur création société par défaut'\'', $societe->errors, '\''errors'\'');\n                $error++;\n            }\n        }\n        $audit->fk_soc = $fk_soc;/' "$MODULE_DIR/wizard/modern.php"
        
        # Correction du token CSRF pour l'export PDF
        sed -i '/input\.name = key;/a\    });\n    \n    \/\/ Ajouter le token CSRF\n    const tokenInput = document.createElement('\''input'\'');\n    tokenInput.type = '\''hidden'\'';\n    tokenInput.name = '\''token'\'';\n    tokenInput.value = '\''<?php echo newToken(); ?>'\'';\n    form.appendChild(tokenInput);' "$MODULE_DIR/wizard/modern.php"
        
        log "SUCCESS" "Wizard moderne corrigé"
    fi
}

# Amélioration de la validation côté client
add_client_validation() {
    log "INFO" "Ajout de validation côté client..."
    
    # Créer un fichier JavaScript de validation
    cat > "$MODULE_DIR/js/validation.js" << 'EOF'
/**
 * Validation côté client pour AuditDigital
 */

// Validation du formulaire avant soumission
function validateAuditForm() {
    let isValid = true;
    let errors = [];
    
    // Vérifier la société
    const socidField = document.querySelector('[name="audit_socid"]');
    if (socidField && (!socidField.value || socidField.value <= 0)) {
        errors.push('Veuillez sélectionner une société');
        isValid = false;
    }
    
    // Vérifier le type de structure
    const structureField = document.querySelector('[name="audit_structure_type"]');
    if (structureField && !structureField.value) {
        errors.push('Veuillez sélectionner un type de structure');
        isValid = false;
    }
    
    // Vérifier le secteur
    const sectorField = document.querySelector('[name="audit_sector"]');
    if (sectorField && !sectorField.value) {
        errors.push('Veuillez sélectionner un secteur d\'activité');
        isValid = false;
    }
    
    // Afficher les erreurs
    if (!isValid) {
        const errorMsg = 'Erreurs de validation :\n' + errors.join('\n');
        alert(errorMsg);
        
        // Mettre en évidence les champs en erreur
        errors.forEach(error => {
            if (error.includes('société')) {
                socidField?.classList.add('error');
            }
            if (error.includes('structure')) {
                structureField?.classList.add('error');
            }
            if (error.includes('secteur')) {
                sectorField?.classList.add('error');
            }
        });
    }
    
    return isValid;
}

// Validation en temps réel
document.addEventListener('DOMContentLoaded', function() {
    // Validation société
    const socidField = document.querySelector('[name="audit_socid"]');
    if (socidField) {
        socidField.addEventListener('change', function() {
            if (this.value && this.value > 0) {
                this.classList.remove('error');
            }
        });
    }
    
    // Validation structure
    const structureField = document.querySelector('[name="audit_structure_type"]');
    if (structureField) {
        structureField.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('error');
            }
        });
    }
    
    // Validation secteur
    const sectorField = document.querySelector('[name="audit_sector"]');
    if (sectorField) {
        sectorField.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('error');
            }
        });
    }
});

// CSS pour les champs en erreur
const style = document.createElement('style');
style.textContent = `
    .error {
        border: 2px solid #dc3545 !important;
        background-color: #f8d7da !important;
    }
    .error:focus {
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
`;
document.head.appendChild(style);
EOF

    chown www-data:www-data "$MODULE_DIR/js/validation.js"
    chmod 644 "$MODULE_DIR/js/validation.js"
    
    log "SUCCESS" "Validation côté client ajoutée"
}

# Test des corrections
test_corrections() {
    log "INFO" "Test des corrections appliquées..."
    
    local errors=0
    
    echo "📝 Tests de syntaxe PHP :"
    local test_files=(
        "$MODULE_DIR/wizard/index.php"
        "$MODULE_DIR/wizard/modern.php"
        "$MODULE_DIR/export_pdf.php"
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
    echo "🔧 Tests de fonctionnalités :"
    
    # Vérifier la correction CSRF
    if grep -q "newToken()" "$MODULE_DIR/export_pdf.php"; then
        echo "  ✅ Token CSRF dans export PDF"
    else
        echo "  ❌ Token CSRF manquant"
        ((errors++))
    fi
    
    # Vérifier la correction fk_soc
    if grep -q "Société créée automatiquement" "$MODULE_DIR/wizard/index.php"; then
        echo "  ✅ Correction fk_soc appliquée"
    else
        echo "  ❌ Correction fk_soc manquante"
        ((errors++))
    fi
    
    # Vérifier la validation JavaScript
    if [[ -f "$MODULE_DIR/js/validation.js" ]]; then
        echo "  ✅ Validation côté client ajoutée"
    else
        echo "  ❌ Validation côté client manquante"
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
    echo "🎉 CORRECTIONS CSRF ET FK_SOC APPLIQUÉES"
    echo "=============================================="
    echo -e "${NC}"
    echo -e "${GREEN}✅ PROBLÈMES CORRIGÉS :${NC}"
    echo "  • Export PDF : Token CSRF ajouté"
    echo "  • Création audit : Société par défaut si non sélectionnée"
    echo "  • Validation côté client améliorée"
    echo "  • Messages d'erreur plus clairs"
    echo
    echo -e "${CYAN}🌐 URLS À TESTER :${NC}"
    echo "  • Wizard : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
    echo "  • Export PDF : Tester depuis l'étape 6 du wizard"
    echo
    echo -e "${YELLOW}📋 TESTS À EFFECTUER :${NC}"
    echo "  1. Créer un audit sans sélectionner de société"
    echo "  2. Aller jusqu'à l'étape 6 et tester l'export PDF"
    echo "  3. Vérifier que l'audit se crée sans erreur"
    echo "  4. Vérifier que le PDF se télécharge"
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
    echo "Voulez-vous appliquer les corrections CSRF et fk_soc ? (y/N)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log "INFO" "Corrections annulées par l'utilisateur"
        exit 0
    fi
    
    echo
    log "INFO" "Application des corrections..."
    
    # Appliquer les corrections
    fix_csrf_export
    fix_fksoc_error
    apply_wizard_corrections
    add_client_validation
    restart_services
    
    # Tests finaux
    echo
    log "INFO" "Tests finaux..."
    if test_corrections; then
        log "SUCCESS" "Toutes les corrections appliquées avec succès !"
        show_results
    else
        log "WARNING" "Corrections appliquées avec des avertissements"
        show_results
    fi
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur lors des corrections à la ligne $LINENO"; exit 1' ERR

# Exécution
main "$@"