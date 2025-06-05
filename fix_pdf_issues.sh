#!/bin/bash
# Correction des problèmes PDF et audit_card

echo "🔧 CORRECTION PROBLÈMES PDF ET AUDIT_CARD"
echo "========================================="

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

print_info "=== 1. CORRECTION AUDIT_CARD.PHP ==="

AUDIT_CARD_FILE="$MODULE_PATH/audit_card.php"
if [ -f "$AUDIT_CARD_FILE" ]; then
    print_info "Correction de la variable \$audit dans audit_card.php..."
    
    # Créer une sauvegarde
    sudo cp "$AUDIT_CARD_FILE" "$AUDIT_CARD_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Corriger les références à $audit non définie
    sudo sed -i 's/\$audit->status/\$object->status/g' "$AUDIT_CARD_FILE"
    sudo sed -i 's/\$audit->id/\$object->id/g' "$AUDIT_CARD_FILE"
    
    print_status "Variables \$audit corrigées"
else
    print_error "audit_card.php non trouvé"
fi

print_info "\n=== 2. CORRECTION PDF_AUDIT_TPE.MODULES.PHP ==="

PDF_TPE_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_tpe.modules.php"
if [ -f "$PDF_TPE_FILE" ]; then
    print_info "Correction de l'erreur count() dans pdf_audit_tpe.modules.php..."
    
    # Créer une sauvegarde
    sudo cp "$PDF_TPE_FILE" "$PDF_TPE_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Corriger l'erreur count() sur null
    sudo sed -i 's/count(\$this->lines)/(!empty(\$this->lines) ? count(\$this->lines) : 0)/g' "$PDF_TPE_FILE"
    sudo sed -i 's/count(\$object->lines)/(!empty(\$object->lines) ? count(\$object->lines) : 0)/g' "$PDF_TPE_FILE"
    
    print_status "Erreur count() corrigée"
else
    print_error "pdf_audit_tpe.modules.php non trouvé"
fi

print_info "\n=== 3. CRÉATION AUDIT_DOCUMENT.PHP ==="

# Créer le fichier audit_document.php manquant
AUDIT_DOCUMENT_FILE="$MODULE_PATH/audit_document.php"

cat << 'EOF' | sudo tee "$AUDIT_DOCUMENT_FILE" > /dev/null
<?php
/* Copyright (C) 2024 Up Digit Agency
 * Page de gestion des documents d'audit
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

// Security check
if (!$user->rights->auditdigital->audit->read) {
    accessforbidden();
}

// Load audit
$object = new Audit($db);
$result = $object->fetch($id);

if ($result <= 0) {
    dol_print_error($db, 'Audit not found');
    exit;
}

// Define upload directory
$upload_dir = $conf->auditdigital->multidir_output[$object->entity].'/audit/'.$object->ref;

// Create directory if not exists
if (!is_dir($upload_dir)) {
    dol_mkdir($upload_dir);
}

// Handle actions
if ($action == 'builddoc') {
    // Generate PDF
    $result = $object->generateDocument($object->model_pdf, $langs);
    
    if ($result <= 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    } else {
        setEventMessages($langs->trans("FileGenerated"), null, 'mesgs');
    }
    
    $action = '';
}

/*
 * View
 */

$title = $langs->trans('Audit').' - '.$langs->trans('Documents');
$help_url = '';

llxHeader('', $title, $help_url);

$form = new Form($db);
$formfile = new FormFile($db);

$head = audit_prepare_head($object);
dol_fiche_head($head, 'documents', $langs->trans("Audit"), -1, 'audit');

// Object card
$linkback = '<a href="'.dol_buildpath('/custom/auditdigital/audit_list.php', 1).'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
$morehtmlref .= '</div>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

// Build document
$filename = dol_sanitizeFileName($object->ref);
$filedir = $conf->auditdigital->multidir_output[$object->entity].'/audit/'.$filename;
$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
$genallowed = $user->rights->auditdigital->audit->write;
$delallowed = $user->rights->auditdigital->audit->write;

print $formfile->showdocuments('auditdigital:Audit', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);

print '</div>';

dol_fiche_end();

llxFooter();

/**
 * Prepare head for audit
 */
function audit_prepare_head($object) {
    global $langs, $conf;
    
    $langs->load("auditdigital@auditdigital");
    
    $h = 0;
    $head = array();
    
    $head[$h][0] = dol_buildpath("/custom/auditdigital/audit_card.php", 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;
    
    $head[$h][0] = dol_buildpath("/custom/auditdigital/audit_document.php", 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Documents");
    $head[$h][2] = 'documents';
    $h++;
    
    return $head;
}
?>
EOF

print_status "audit_document.php créé"

print_info "\n=== 4. CORRECTION GÉNÉRATEUR PDF SIMPLE ==="

# Créer un générateur PDF plus simple et robuste
SIMPLE_PDF_FILE="$MODULE_PATH/pdf_simple.php"

cat << 'EOF' | sudo tee "$SIMPLE_PDF_FILE" > /dev/null
<?php
/* Copyright (C) 2024 Up Digit Agency
 * Générateur PDF simple pour audits
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

// Get parameters
$id = GETPOST('id', 'int');

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

// Parse responses
$responses = json_decode($audit->json_responses, true);
if (!$responses) {
    $responses = array();
}

// Generate simple HTML report
$html = generateSimpleReport($audit, $company, $responses);

// Output as PDF using browser print
header('Content-Type: text/html; charset=UTF-8');
echo $html;

function generateSimpleReport($audit, $company, $responses) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rapport Audit Digital - ' . $audit->ref . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #0066CC; padding-bottom: 20px; margin-bottom: 30px; }
        .company-name { font-size: 24px; font-weight: bold; color: #0066CC; }
        .audit-ref { font-size: 18px; margin-top: 10px; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 18px; font-weight: bold; color: #0066CC; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 15px; }
        .score-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .score-item { display: flex; justify-content: space-between; margin: 5px 0; }
        .score-bar { width: 200px; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
        .score-fill { height: 100%; background: linear-gradient(90deg, #dc3545, #ffc107, #28a745); }
        .recommendations { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; }
        .print-button { background: #0066CC; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 20px 0; }
        @media print { .print-button { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">' . htmlspecialchars($company->name) . '</div>
        <div class="audit-ref">Rapport Audit Digital - ' . htmlspecialchars($audit->ref) . '</div>
        <div>Date: ' . dol_print_date($audit->date_creation, 'day') . '</div>
    </div>
    
    <button class="print-button" onclick="window.print()">🖨️ Imprimer / Sauvegarder en PDF</button>
    
    <div class="section">
        <div class="section-title">📊 Scores de Maturité Numérique</div>
        <div class="score-box">
            <div class="score-item">
                <span><strong>Score Global:</strong></span>
                <span><strong>' . $audit->score_global . '/100</strong></span>
            </div>
            <div class="score-item">
                <span>Maturité Numérique:</span>
                <span>' . $audit->score_maturite . '/100</span>
            </div>
            <div class="score-item">
                <span>Cybersécurité:</span>
                <span>' . $audit->score_cybersecurite . '/100</span>
            </div>
            <div class="score-item">
                <span>Cloud & Infrastructure:</span>
                <span>' . $audit->score_cloud . '/100</span>
            </div>
            <div class="score-item">
                <span>Automatisation:</span>
                <span>' . $audit->score_automatisation . '/100</span>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">📋 Informations Générales</div>';
        
    if (isset($responses['step1'])) {
        $step1 = $responses['step1'];
        $html .= '<p><strong>Type de structure:</strong> ' . htmlspecialchars($step1['structure_type'] ?? 'Non renseigné') . '</p>';
        $html .= '<p><strong>Secteur d\'activité:</strong> ' . htmlspecialchars($step1['sector'] ?? 'Non renseigné') . '</p>';
        $html .= '<p><strong>Nombre d\'employés:</strong> ' . htmlspecialchars($step1['employees_count'] ?? 'Non renseigné') . '</p>';
        $html .= '<p><strong>Budget IT annuel:</strong> ' . htmlspecialchars($step1['it_budget'] ?? 'Non renseigné') . '</p>';
    }
    
    $html .= '</div>';
    
    // Interpretation
    $interpretation = getScoreInterpretation($audit->score_global);
    $html .= '<div class="section">
        <div class="section-title">💡 Interprétation</div>
        <p>' . $interpretation . '</p>
    </div>';
    
    // Recommendations
    $recommendations = json_decode($audit->json_recommendations, true);
    if (!empty($recommendations)) {
        $html .= '<div class="section">
            <div class="section-title">🎯 Recommandations</div>
            <div class="recommendations">';
        
        foreach ($recommendations as $rec) {
            $priority = strtoupper($rec['priority'] ?? 'MEDIUM');
            $html .= '<div style="margin-bottom: 15px;">
                <strong>[' . $priority . '] ' . htmlspecialchars($rec['title'] ?? '') . '</strong><br>
                ' . htmlspecialchars($rec['description'] ?? '') . '
            </div>';
        }
        
        $html .= '</div></div>';
    }
    
    $html .= '
    <div class="section">
        <div class="section-title">📞 Contact</div>
        <p>Pour toute question ou accompagnement dans la mise en œuvre de ces recommandations, contactez <strong>Up Digit Agency</strong>.</p>
    </div>
    
    <div style="text-align: center; margin-top: 50px; color: #666; font-size: 12px;">
        Rapport généré le ' . date('d/m/Y à H:i') . ' par AuditDigital - Up Digit Agency
    </div>
    
</body>
</html>';
    
    return $html;
}

function getScoreInterpretation($score) {
    if ($score >= 80) {
        return "🎉 <strong>Excellent !</strong> Votre organisation présente un niveau de maturité numérique très élevé. Vous êtes bien positionnés pour tirer parti des opportunités du digital.";
    } elseif ($score >= 60) {
        return "👍 <strong>Bon niveau</strong> de maturité numérique. Votre organisation a mis en place de bonnes bases, mais il existe encore des opportunités d'amélioration.";
    } elseif ($score >= 40) {
        return "⚠️ <strong>Niveau moyen.</strong> Votre organisation a entamé sa transformation digitale, mais des efforts supplémentaires sont nécessaires.";
    } else {
        return "🚨 <strong>Niveau faible.</strong> Il est urgent de mettre en place une stratégie de transformation digitale pour rester compétitif.";
    }
}
?>
EOF

print_status "Générateur PDF simple créé"

print_info "\n=== 5. AJOUT BOUTON PDF DANS AUDIT_CARD ==="

# Ajouter le bouton PDF dans audit_card.php de manière plus robuste
if [ -f "$AUDIT_CARD_FILE" ]; then
    # Chercher où ajouter le bouton PDF
    if ! grep -q "pdf_simple.php" "$AUDIT_CARD_FILE"; then
        # Ajouter le bouton PDF après les autres boutons
        sudo sed -i '/print dolGetButtonAction.*Modify/a\
\
// PDF Report button\
if ($object->status >= 0) {\
    print dolGetButtonAction("📄 Rapport PDF", "", "default", dol_buildpath("/custom/auditdigital/pdf_simple.php?id=".$object->id, 1), "", $permissiontoadd, array("target" => "_blank"));\
}' "$AUDIT_CARD_FILE"
        
        print_status "Bouton PDF ajouté à audit_card.php"
    else
        print_info "Bouton PDF déjà présent"
    fi
fi

print_info "\n=== 6. CORRECTION LIENS DANS AUDIT_LIST ==="

AUDIT_LIST_FILE="$MODULE_PATH/audit_list.php"
if [ -f "$AUDIT_LIST_FILE" ]; then
    # Ajouter un lien PDF simple dans la liste
    if ! grep -q "pdf_simple.php" "$AUDIT_LIST_FILE"; then
        # Chercher la ligne avec les actions et ajouter le lien PDF
        sudo sed -i '/print.*dolGetButtonAction.*Edit/a\
print " ";\
print dolGetButtonAction("PDF", "", "default", dol_buildpath("/custom/auditdigital/pdf_simple.php?id=".$obj->rowid, 1), "", $permissiontoadd, array("target" => "_blank"));' "$AUDIT_LIST_FILE"
        
        print_status "Lien PDF ajouté à audit_list.php"
    else
        print_info "Lien PDF déjà présent"
    fi
fi

print_info "\n=== 7. REDÉMARRAGE APACHE ==="
sudo systemctl restart apache2
print_status "Apache redémarré"

print_info "\n=== RÉSULTAT ==="
print_status "🎉 PROBLÈMES PDF CORRIGÉS !"
echo ""
print_info "✅ CORRECTIONS APPLIQUÉES :"
echo "1. Variable \$audit corrigée dans audit_card.php"
echo "2. Erreur count() corrigée dans pdf_audit_tpe.modules.php"
echo "3. Fichier audit_document.php créé"
echo "4. Générateur PDF simple et robuste créé"
echo "5. Boutons PDF ajoutés dans les interfaces"
echo ""
print_info "🔗 ACCÈS PDF :"
echo "- Bouton '📄 Rapport PDF' dans la fiche audit"
echo "- Lien 'PDF' dans la liste des audits"
echo "- URL directe: /custom/auditdigital/pdf_simple.php?id=X"
echo ""
print_info "💡 UTILISATION :"
echo "1. Cliquez sur le bouton PDF"
echo "2. Utilisez Ctrl+P pour imprimer/sauvegarder en PDF"
echo "3. Le rapport s'affiche dans un nouvel onglet"

exit 0