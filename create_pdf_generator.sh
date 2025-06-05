#!/bin/bash
# Création du générateur PDF pour les rapports d'audit

echo "📄 CRÉATION GÉNÉRATEUR PDF RAPPORTS"
echo "==================================="

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

MODULE_PATH="/usr/share/dolibarr/htdocs/custom/auditdigital"

print_info "=== CRÉATION DU GÉNÉRATEUR PDF ==="

# Créer le fichier de génération PDF
PDF_GENERATOR="$MODULE_PATH/generate_pdf.php"

cat << 'EOF' | sudo tee "$PDF_GENERATOR" > /dev/null
<?php
/* Copyright (C) 2024 Up Digit Agency
 * Générateur PDF pour rapports d'audit digital
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("/usr/share/dolibarr/htdocs/main.inc.php")) $res = @include "/usr/share/dolibarr/htdocs/main.inc.php";

if (!$res) {
    die("Error: Could not load main.inc.php");
}

// Check if module is enabled
if (!isModEnabled('auditdigital')) {
    accessforbidden('Module not enabled');
}

// Load required classes
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

if (empty($id)) {
    dol_print_error($db, 'Missing audit ID');
    exit;
}

// Load audit
$audit = new Audit($db);
$result = $audit->fetch($id);

if ($result <= 0) {
    dol_print_error($db, 'Audit not found');
    exit;
}

// Load company
$company = new Societe($db);
$company->fetch($audit->fk_soc);

// Generate PDF
$pdf = generateAuditPDF($audit, $company, $user, $langs);

if ($pdf) {
    // Output PDF
    $filename = 'audit_digital_' . $audit->ref . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    
    echo $pdf;
} else {
    dol_print_error($db, 'Error generating PDF');
}

/**
 * Generate audit PDF report
 */
function generateAuditPDF($audit, $company, $user, $langs) {
    global $conf, $db;
    
    // Create PDF
    $pdf = pdf_getInstance();
    
    if (empty($pdf)) {
        return false;
    }
    
    // Set document properties
    $pdf->SetCreator('Dolibarr AuditDigital');
    $pdf->SetAuthor('Up Digit Agency');
    $pdf->SetTitle('Rapport Audit Digital - ' . $audit->ref);
    $pdf->SetSubject('Audit de maturité numérique');
    
    // Set margins
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 20);
    
    // Add page
    $pdf->AddPage();
    
    // Parse responses
    $responses = json_decode($audit->json_responses, true);
    $recommendations = json_decode($audit->json_recommendations, true);
    
    // Generate content
    generateCoverPage($pdf, $audit, $company);
    generateExecutiveSummary($pdf, $audit, $responses);
    generateScoresRadar($pdf, $audit);
    generateDetailedAnalysis($pdf, $audit, $responses);
    generateRecommendations($pdf, $recommendations);
    generateActionPlan($pdf, $recommendations);
    
    return $pdf->Output('', 'S');
}

/**
 * Generate cover page
 */
function generateCoverPage($pdf, $audit, $company) {
    // Logo and header
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetTextColor(0, 102, 204);
    $pdf->Cell(0, 15, 'RAPPORT AUDIT DIGITAL', 0, 1, 'C');
    
    $pdf->Ln(10);
    
    // Company info
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, $company->name, 0, 1, 'C');
    
    $pdf->Ln(20);
    
    // Audit info box
    $pdf->SetFillColor(240, 248, 255);
    $pdf->Rect(20, $pdf->GetY(), 170, 60, 'F');
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'INFORMATIONS DE L\'AUDIT', 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(50, 6, 'Référence:', 0, 0, 'L');
    $pdf->Cell(0, 6, $audit->ref, 0, 1, 'L');
    
    $pdf->Cell(50, 6, 'Date:', 0, 0, 'L');
    $pdf->Cell(0, 6, dol_print_date($audit->date_creation, 'day'), 0, 1, 'L');
    
    $pdf->Cell(50, 6, 'Type:', 0, 0, 'L');
    $pdf->Cell(0, 6, ucfirst($audit->structure_type), 0, 1, 'L');
    
    $pdf->Cell(50, 6, 'Score global:', 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 150, 0);
    $pdf->Cell(0, 6, $audit->score_global . '/100', 0, 1, 'L');
    
    $pdf->Ln(30);
    
    // Footer
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, 'Généré par Up Digit Agency - ' . date('d/m/Y'), 0, 1, 'C');
}

/**
 * Generate executive summary
 */
function generateExecutiveSummary($pdf, $audit, $responses) {
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 102, 204);
    $pdf->Cell(0, 10, 'SYNTHÈSE EXÉCUTIVE', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Global score
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 8, 'Score de Maturité Numérique: ' . $audit->score_global . '/100', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Interpretation
    $interpretation = getScoreInterpretation($audit->score_global);
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 6, $interpretation, 0, 'L');
    $pdf->Ln(10);
    
    // Scores by category
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Scores par Domaine:', 0, 1, 'L');
    $pdf->Ln(2);
    
    $categories = array(
        'Maturité Numérique' => $audit->score_maturite,
        'Cybersécurité' => $audit->score_cybersecurite,
        'Cloud & Infrastructure' => $audit->score_cloud,
        'Automatisation' => $audit->score_automatisation
    );
    
    foreach ($categories as $category => $score) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(80, 6, $category . ':', 0, 0, 'L');
        
        // Progress bar
        $barWidth = 80;
        $barHeight = 4;
        $fillWidth = ($score / 100) * $barWidth;
        
        $y = $pdf->GetY() + 1;
        $pdf->Rect(100, $y, $barWidth, $barHeight, 'D');
        
        // Color based on score
        if ($score >= 70) {
            $pdf->SetFillColor(40, 167, 69); // Green
        } elseif ($score >= 40) {
            $pdf->SetFillColor(255, 193, 7); // Yellow
        } else {
            $pdf->SetFillColor(220, 53, 69); // Red
        }
        
        $pdf->Rect(100, $y, $fillWidth, $barHeight, 'F');
        
        $pdf->Cell(20, 6, $score . '%', 0, 1, 'R');
    }
}

/**
 * Generate scores radar chart (simplified)
 */
function generateScoresRadar($pdf, $audit) {
    $pdf->Ln(20);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Graphique de Maturité:', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Simple representation (could be enhanced with actual radar chart)
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 6, 'Le graphique radar détaillé est disponible dans la version interactive de ce rapport. Les scores ci-dessus donnent une vue d\'ensemble de votre maturité numérique dans chaque domaine clé.', 0, 'L');
}

/**
 * Generate detailed analysis
 */
function generateDetailedAnalysis($pdf, $audit, $responses) {
    $pdf->AddPage();
    
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 102, 204);
    $pdf->Cell(0, 10, 'ANALYSE DÉTAILLÉE', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Analyze each step
    for ($step = 1; $step <= 5; $step++) {
        if (isset($responses['step'.$step])) {
            generateStepAnalysis($pdf, $step, $responses['step'.$step]);
        }
    }
}

/**
 * Generate step analysis
 */
function generateStepAnalysis($pdf, $step, $stepData) {
    $stepTitles = array(
        1 => 'Informations Générales',
        2 => 'Maturité Numérique',
        3 => 'Cybersécurité',
        4 => 'Cloud & Infrastructure',
        5 => 'Automatisation'
    );
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 8, $stepTitles[$step], 0, 1, 'L');
    $pdf->Ln(2);
    
    $pdf->SetFont('Arial', '', 10);
    
    foreach ($stepData as $key => $value) {
        if ($key != 'token' && $key != 'action' && $key != 'step') {
            $pdf->Cell(60, 5, ucfirst(str_replace('_', ' ', $key)) . ':', 0, 0, 'L');
            $pdf->Cell(0, 5, $value, 0, 1, 'L');
        }
    }
    
    $pdf->Ln(5);
}

/**
 * Generate recommendations
 */
function generateRecommendations($pdf, $recommendations) {
    $pdf->AddPage();
    
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 102, 204);
    $pdf->Cell(0, 10, 'RECOMMANDATIONS', 0, 1, 'L');
    $pdf->Ln(5);
    
    if (empty($recommendations)) {
        $pdf->SetFont('Arial', '', 11);
        $pdf->MultiCell(0, 6, 'Félicitations ! Votre niveau de maturité numérique est excellent. Continuez à maintenir vos bonnes pratiques et restez à l\'affût des nouvelles technologies.', 0, 'L');
        return;
    }
    
    $priorityColors = array(
        'critical' => array(220, 53, 69),
        'high' => array(255, 193, 7),
        'medium' => array(23, 162, 184),
        'low' => array(40, 167, 69)
    );
    
    $priorityLabels = array(
        'critical' => 'CRITIQUE',
        'high' => 'ÉLEVÉE',
        'medium' => 'MOYENNE',
        'low' => 'FAIBLE'
    );
    
    foreach ($recommendations as $i => $rec) {
        // Priority badge
        $priority = $rec['priority'];
        $color = $priorityColors[$priority];
        
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(25, 6, $priorityLabels[$priority], 0, 0, 'C', true);
        
        // Title
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 6, '  ' . $rec['title'], 0, 1, 'L');
        
        // Description
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 5, $rec['description'], 0, 'L');
        $pdf->Ln(5);
    }
}

/**
 * Generate action plan
 */
function generateActionPlan($pdf, $recommendations) {
    $pdf->AddPage();
    
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 102, 204);
    $pdf->Cell(0, 10, 'PLAN D\'ACTION', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 6, 'Voici un plan d\'action recommandé pour améliorer votre maturité numérique, organisé par priorité et échéance.', 0, 'L');
    $pdf->Ln(10);
    
    // Timeline
    $timelines = array(
        'Court terme (1-3 mois)' => array('critical', 'high'),
        'Moyen terme (3-6 mois)' => array('medium'),
        'Long terme (6-12 mois)' => array('low')
    );
    
    foreach ($timelines as $period => $priorities) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, $period, 0, 1, 'L');
        $pdf->Ln(2);
        
        $hasItems = false;
        foreach ($recommendations as $rec) {
            if (in_array($rec['priority'], $priorities)) {
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(5, 5, '•', 0, 0, 'L');
                $pdf->MultiCell(0, 5, $rec['title'], 0, 'L');
                $hasItems = true;
            }
        }
        
        if (!$hasItems) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 5, 'Aucune action prioritaire pour cette période', 0, 1, 'L');
        }
        
        $pdf->Ln(5);
    }
    
    // Contact info
    $pdf->Ln(20);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'CONTACT', 0, 1, 'L');
    $pdf->Ln(2);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 5, 'Pour toute question ou pour être accompagné dans la mise en œuvre de ces recommandations, contactez Up Digit Agency.', 0, 'L');
}

/**
 * Get score interpretation
 */
function getScoreInterpretation($score) {
    if ($score >= 80) {
        return "Excellent ! Votre organisation présente un niveau de maturité numérique très élevé. Vous êtes bien positionnés pour tirer parti des opportunités du digital et faire face aux défis technologiques actuels.";
    } elseif ($score >= 60) {
        return "Bon niveau de maturité numérique. Votre organisation a mis en place de bonnes bases, mais il existe encore des opportunités d'amélioration pour optimiser votre transformation digitale.";
    } elseif ($score >= 40) {
        return "Niveau de maturité numérique moyen. Votre organisation a entamé sa transformation digitale, mais des efforts supplémentaires sont nécessaires pour rester compétitive dans l'économie numérique.";
    } else {
        return "Niveau de maturité numérique faible. Il est urgent de mettre en place une stratégie de transformation digitale pour ne pas prendre de retard face à la concurrence et aux évolutions du marché.";
    }
}

?>
EOF

print_status "Générateur PDF créé"

print_info "\n=== CRÉATION DU BOUTON PDF DANS AUDIT_CARD ==="

# Ajouter le bouton PDF dans audit_card.php
AUDIT_CARD_FILE="$MODULE_PATH/audit_card.php"

if [ -f "$AUDIT_CARD_FILE" ]; then
    # Ajouter le bouton PDF après les autres boutons d'action
    sudo sed -i '/print dolGetButtonAction/a\
\
// PDF Generation button\
if ($audit->status > 0) {\
    print dolGetButtonAction("Générer PDF", "", "default", dol_buildpath("/custom/auditdigital/generate_pdf.php?id=".$audit->id, 1), "", $permissiontoadd);\
}' "$AUDIT_CARD_FILE"

    print_status "Bouton PDF ajouté à audit_card.php"
fi

print_info "\n=== CRÉATION DU LIEN PDF DANS LA LISTE ==="

# Ajouter une colonne PDF dans audit_list.php
AUDIT_LIST_FILE="$MODULE_PATH/audit_list.php"

if [ -f "$AUDIT_LIST_FILE" ]; then
    # Ajouter une colonne PDF
    sudo sed -i '/print_liste_field_titre.*Action/i\
print_liste_field_titre("PDF", $_SERVER["PHP_SELF"], "", "", "", "", "", $sortfield, $sortorder);' "$AUDIT_LIST_FILE"

    # Ajouter le lien PDF dans les lignes
    sudo sed -i '/print dolGetButtonAction.*Edit/a\
\
// PDF link\
if ($obj->status > 0) {\
    print "<a href=\"".dol_buildpath("/custom/auditdigital/generate_pdf.php?id=".$obj->rowid, 1)."\" class=\"butAction\" target=\"_blank\">PDF</a>";\
} else {\
    print "<span class=\"butActionRefused\">PDF</span>";\
}' "$AUDIT_LIST_FILE"

    print_status "Liens PDF ajoutés à audit_list.php"
fi

print_info "\n=== REDÉMARRAGE APACHE ==="
sudo systemctl restart apache2
print_status "Apache redémarré"

print_info "\n=== RÉSULTAT ==="
print_status "🎉 GÉNÉRATEUR PDF CRÉÉ !"
echo ""
print_info "📄 FONCTIONNALITÉS PDF :"
echo "1. Page de couverture avec logo et infos"
echo "2. Synthèse exécutive avec scores"
echo "3. Graphique de maturité (représentation)"
echo "4. Analyse détaillée par domaine"
echo "5. Recommandations prioritaires"
echo "6. Plan d'action avec timeline"
echo "7. Informations de contact"
echo ""
print_info "🔗 ACCÈS PDF :"
echo "- Bouton dans la fiche audit"
echo "- Lien dans la liste des audits"
echo "- URL directe: /custom/auditdigital/generate_pdf.php?id=X"

exit 0