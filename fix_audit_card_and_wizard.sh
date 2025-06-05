#!/bin/bash
# Correction de audit_card.php et création du wizard complet

echo "🔧 CORRECTION AUDIT_CARD + WIZARD COMPLET"
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
    print_info "Correction de la classe FormProject dans audit_card.php..."
    
    # Créer une sauvegarde
    sudo cp "$AUDIT_CARD_FILE" "$AUDIT_CARD_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Corriger la classe FormProject
    sudo sed -i 's/FormProject/FormProjets/g' "$AUDIT_CARD_FILE"
    
    # Ajouter une gestion d'erreur pour la classe projet
    sudo sed -i '/require_once.*formproject/i\
// Try different project form class names\
$formproject = null;\
if (isModEnabled("project")) {\
    $projectClassNames = array("FormProjets", "FormProjet", "FormProject");\
    foreach ($projectClassNames as $className) {\
        if (class_exists($className)) {\
            try {\
                $formproject = new $className($db);\
                break;\
            } catch (Exception $e) {\
                continue;\
            }\
        }\
    }\
}' "$AUDIT_CARD_FILE"
    
    print_status "audit_card.php corrigé"
else
    print_error "audit_card.php non trouvé"
fi

print_info "\n=== 2. CRÉATION DU WIZARD COMPLET MULTI-ÉTAPES ==="

WIZARD_FILE="$MODULE_PATH/wizard/index.php"

# Créer le wizard complet avec toutes les étapes
cat << 'EOF' | sudo tee "$WIZARD_FILE" > /dev/null
<?php
/* Copyright (C) 2024 Up Digit Agency
 * Wizard d'audit digital complet multi-étapes
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("/usr/share/dolibarr/htdocs/main.inc.php")) $res = @include "/usr/share/dolibarr/htdocs/main.inc.php";

if (!$res) {
    die("Error: Could not load main.inc.php");
}

// Check if module is enabled
if (!isModEnabled('auditdigital')) {
    accessforbidden('Module not enabled');
}

// Load required classes
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';

// Load translation files
$langs->loadLangs(array("main", "companies", "projects"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int');
if (empty($step)) $step = 1;
$id = GETPOST('id', 'int');

// Security check
if (!$user->rights->auditdigital->audit->write) {
    accessforbidden();
}

// Initialize objects
$formcompany = new FormCompany($db);

// Initialize session for wizard data
if (!isset($_SESSION['audit_wizard'])) {
    $_SESSION['audit_wizard'] = array();
}

$error = 0;
$message = '';
$success = false;

// Handle form submission
if ($action == 'next_step' && $_POST) {
    // Save current step data
    $_SESSION['audit_wizard']['step'.$step] = $_POST;
    
    // Validate current step
    $validation = validateStep($step, $_POST);
    if ($validation['valid']) {
        $step++; // Go to next step
        if ($step > 6) {
            // Create audit and finish
            $audit_id = createAuditFromWizard($_SESSION['audit_wizard'], $db, $user);
            if ($audit_id > 0) {
                // Clear session and redirect
                unset($_SESSION['audit_wizard']);
                header('Location: ' . dol_buildpath('/custom/auditdigital/audit_card.php?id='.$audit_id, 1));
                exit;
            } else {
                $error++;
                $message = 'Erreur lors de la création de l\'audit';
                $step = 6; // Stay on last step
            }
        }
    } else {
        $error++;
        $message = $validation['message'];
    }
}

if ($action == 'prev_step') {
    $step--;
    if ($step < 1) $step = 1;
}

// Questionnaire data structure
$questionnaire = getQuestionnaireData();

/*
 * Functions
 */

function validateStep($step, $data) {
    switch ($step) {
        case 1:
            if (empty($data['structure_type'])) return array('valid' => false, 'message' => 'Type de structure obligatoire');
            if (empty($data['fk_soc'])) return array('valid' => false, 'message' => 'Société obligatoire');
            if (empty($data['sector'])) return array('valid' => false, 'message' => 'Secteur obligatoire');
            break;
        case 2:
            if (empty($data['website_exists'])) return array('valid' => false, 'message' => 'Présence web obligatoire');
            break;
        case 3:
            if (empty($data['password_policy'])) return array('valid' => false, 'message' => 'Politique de mots de passe obligatoire');
            break;
        case 4:
            if (empty($data['hosting_type'])) return array('valid' => false, 'message' => 'Type d\'hébergement obligatoire');
            break;
        case 5:
            if (empty($data['manual_processes'])) return array('valid' => false, 'message' => 'Processus manuels obligatoire');
            break;
    }
    return array('valid' => true, 'message' => '');
}

function createAuditFromWizard($wizard_data, $db, $user) {
    try {
        $audit = new Audit($db);
        
        // Basic info from step 1
        $step1 = $wizard_data['step1'];
        $audit->ref = 'AUD' . date('ymd') . '-' . sprintf('%04d', rand(1, 9999));
        $audit->label = 'Audit Digital - ' . date('Y-m-d H:i:s');
        $audit->audit_type = $step1['structure_type'];
        $audit->structure_type = $step1['structure_type'];
        $audit->fk_soc = $step1['fk_soc'];
        $audit->fk_projet = !empty($step1['fk_projet']) ? $step1['fk_projet'] : 0;
        $audit->status = 0;
        
        // Calculate scores
        $scores = calculateScores($wizard_data);
        $audit->score_global = $scores['global'];
        $audit->score_maturite = $scores['maturite'];
        $audit->score_cybersecurite = $scores['cybersecurite'];
        $audit->score_cloud = $scores['cloud'];
        $audit->score_automatisation = $scores['automatisation'];
        
        // Store all responses as JSON
        $audit->json_responses = json_encode($wizard_data);
        
        // Generate recommendations
        $audit->json_recommendations = json_encode(generateRecommendations($scores, $step1['structure_type']));
        
        $result = $audit->create($user);
        return $result;
        
    } catch (Exception $e) {
        return -1;
    }
}

function calculateScores($wizard_data) {
    $scores = array(
        'maturite' => 0,
        'cybersecurite' => 0,
        'cloud' => 0,
        'automatisation' => 0,
        'global' => 0
    );
    
    // Maturité numérique (step 2)
    if (isset($wizard_data['step2'])) {
        $step2 = $wizard_data['step2'];
        $maturite_score = 0;
        
        if ($step2['website_exists'] == 'yes') $maturite_score += 20;
        if ($step2['social_media'] == 'active') $maturite_score += 15;
        if ($step2['collaborative_tools'] == 'advanced') $maturite_score += 25;
        if ($step2['process_digitalization'] == 'high') $maturite_score += 25;
        if ($step2['team_training'] == 'regular') $maturite_score += 15;
        
        $scores['maturite'] = min(100, $maturite_score);
    }
    
    // Cybersécurité (step 3)
    if (isset($wizard_data['step3'])) {
        $step3 = $wizard_data['step3'];
        $cyber_score = 0;
        
        if ($step3['password_policy'] == 'strong') $cyber_score += 25;
        if ($step3['backup_strategy'] == 'automated') $cyber_score += 25;
        if ($step3['antivirus_firewall'] == 'enterprise') $cyber_score += 25;
        if ($step3['security_training'] == 'regular') $cyber_score += 15;
        if ($step3['gdpr_compliance'] == 'full') $cyber_score += 10;
        
        $scores['cybersecurite'] = min(100, $cyber_score);
    }
    
    // Cloud (step 4)
    if (isset($wizard_data['step4'])) {
        $step4 = $wizard_data['step4'];
        $cloud_score = 0;
        
        if ($step4['hosting_type'] == 'cloud') $cloud_score += 30;
        if ($step4['cloud_services'] == 'multiple') $cloud_score += 25;
        if ($step4['remote_work'] == 'full') $cloud_score += 25;
        if ($step4['network_performance'] == 'excellent') $cloud_score += 20;
        
        $scores['cloud'] = min(100, $cloud_score);
    }
    
    // Automatisation (step 5)
    if (isset($wizard_data['step5'])) {
        $step5 = $wizard_data['step5'];
        $auto_score = 0;
        
        if ($step5['manual_processes'] == 'few') $auto_score += 30;
        if ($step5['automation_tools'] == 'advanced') $auto_score += 30;
        if ($step5['integrations'] == 'many') $auto_score += 25;
        if ($step5['time_savings'] == 'high') $auto_score += 15;
        
        $scores['automatisation'] = min(100, $auto_score);
    }
    
    // Score global (moyenne pondérée)
    $scores['global'] = round(
        ($scores['maturite'] * 0.3) +
        ($scores['cybersecurite'] * 0.25) +
        ($scores['cloud'] * 0.25) +
        ($scores['automatisation'] * 0.2)
    );
    
    return $scores;
}

function generateRecommendations($scores, $structure_type) {
    $recommendations = array();
    
    if ($scores['maturite'] < 50) {
        $recommendations[] = array(
            'priority' => 'high',
            'category' => 'maturite',
            'title' => 'Améliorer la présence digitale',
            'description' => 'Développer un site web moderne et optimiser la présence sur les réseaux sociaux'
        );
    }
    
    if ($scores['cybersecurite'] < 60) {
        $recommendations[] = array(
            'priority' => 'critical',
            'category' => 'cybersecurite',
            'title' => 'Renforcer la cybersécurité',
            'description' => 'Mettre en place une politique de sécurité robuste et former les équipes'
        );
    }
    
    if ($scores['cloud'] < 40) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'cloud',
            'title' => 'Migration vers le cloud',
            'description' => 'Évaluer les bénéfices d\'une migration cloud pour améliorer la flexibilité'
        );
    }
    
    if ($scores['automatisation'] < 50) {
        $recommendations[] = array(
            'priority' => 'medium',
            'category' => 'automatisation',
            'title' => 'Automatiser les processus',
            'description' => 'Identifier et automatiser les tâches répétitives pour gagner en efficacité'
        );
    }
    
    return $recommendations;
}

function getQuestionnaireData() {
    return array(
        1 => array(
            'title' => 'Informations générales',
            'description' => 'Renseignez les informations de base sur votre structure.',
            'questions' => array(
                'structure_type' => array(
                    'type' => 'radio',
                    'label' => 'Type de structure',
                    'required' => true,
                    'options' => array(
                        'tpe_pme' => 'TPE/PME',
                        'collectivite' => 'Collectivité territoriale'
                    )
                ),
                'sector' => array(
                    'type' => 'select',
                    'label' => 'Secteur d\'activité',
                    'required' => true,
                    'options' => array(
                        'commerce' => 'Commerce',
                        'services' => 'Services',
                        'industrie' => 'Industrie',
                        'administration' => 'Administration',
                        'sante' => 'Santé',
                        'education' => 'Éducation'
                    )
                ),
                'employees_count' => array(
                    'type' => 'select',
                    'label' => 'Nombre d\'employés',
                    'required' => true,
                    'options' => array(
                        '1-10' => '1 à 10 employés',
                        '11-50' => '11 à 50 employés',
                        '51-250' => '51 à 250 employés',
                        '250+' => 'Plus de 250 employés'
                    )
                ),
                'it_budget' => array(
                    'type' => 'select',
                    'label' => 'Budget IT annuel',
                    'required' => true,
                    'options' => array(
                        '0-5k' => 'Moins de 5 000€',
                        '5k-15k' => '5 000€ à 15 000€',
                        '15k-50k' => '15 000€ à 50 000€',
                        '50k+' => 'Plus de 50 000€'
                    )
                )
            )
        ),
        2 => array(
            'title' => 'Maturité numérique',
            'description' => 'Évaluez votre niveau de digitalisation actuel.',
            'questions' => array(
                'website_exists' => array(
                    'type' => 'radio',
                    'label' => 'Avez-vous un site web ?',
                    'required' => true,
                    'options' => array(
                        'yes' => 'Oui, moderne et responsive',
                        'basic' => 'Oui, mais basique',
                        'no' => 'Non'
                    )
                ),
                'social_media' => array(
                    'type' => 'radio',
                    'label' => 'Présence sur les réseaux sociaux',
                    'required' => true,
                    'options' => array(
                        'active' => 'Active et régulière',
                        'basic' => 'Présence basique',
                        'none' => 'Aucune présence'
                    )
                ),
                'collaborative_tools' => array(
                    'type' => 'radio',
                    'label' => 'Outils collaboratifs utilisés',
                    'required' => true,
                    'options' => array(
                        'advanced' => 'Suite complète (Teams, Slack, etc.)',
                        'basic' => 'Outils basiques (email)',
                        'none' => 'Aucun outil spécifique'
                    )
                ),
                'process_digitalization' => array(
                    'type' => 'radio',
                    'label' => 'Niveau de digitalisation des processus',
                    'required' => true,
                    'options' => array(
                        'high' => 'Élevé (>70% des processus)',
                        'medium' => 'Moyen (30-70%)',
                        'low' => 'Faible (<30%)'
                    )
                ),
                'team_training' => array(
                    'type' => 'radio',
                    'label' => 'Formation des équipes au numérique',
                    'required' => true,
                    'options' => array(
                        'regular' => 'Formations régulières',
                        'occasional' => 'Formations ponctuelles',
                        'none' => 'Aucune formation'
                    )
                )
            )
        ),
        3 => array(
            'title' => 'Cybersécurité',
            'description' => 'Évaluez votre niveau de sécurité informatique.',
            'questions' => array(
                'password_policy' => array(
                    'type' => 'radio',
                    'label' => 'Politique de mots de passe',
                    'required' => true,
                    'options' => array(
                        'strong' => 'Politique stricte avec 2FA',
                        'basic' => 'Politique basique',
                        'none' => 'Aucune politique'
                    )
                ),
                'backup_strategy' => array(
                    'type' => 'radio',
                    'label' => 'Stratégie de sauvegarde',
                    'required' => true,
                    'options' => array(
                        'automated' => 'Sauvegardes automatisées et testées',
                        'manual' => 'Sauvegardes manuelles',
                        'none' => 'Aucune sauvegarde régulière'
                    )
                ),
                'antivirus_firewall' => array(
                    'type' => 'radio',
                    'label' => 'Protection antivirus/firewall',
                    'required' => true,
                    'options' => array(
                        'enterprise' => 'Solution entreprise complète',
                        'basic' => 'Protection basique',
                        'none' => 'Protection minimale'
                    )
                ),
                'security_training' => array(
                    'type' => 'radio',
                    'label' => 'Formation cybersécurité des équipes',
                    'required' => true,
                    'options' => array(
                        'regular' => 'Formations régulières',
                        'occasional' => 'Sensibilisation ponctuelle',
                        'none' => 'Aucune formation'
                    )
                ),
                'gdpr_compliance' => array(
                    'type' => 'radio',
                    'label' => 'Conformité RGPD',
                    'required' => true,
                    'options' => array(
                        'full' => 'Conformité complète',
                        'partial' => 'Conformité partielle',
                        'none' => 'Non conforme'
                    )
                )
            )
        ),
        4 => array(
            'title' => 'Cloud et infrastructure',
            'description' => 'Évaluez votre infrastructure et usage du cloud.',
            'questions' => array(
                'hosting_type' => array(
                    'type' => 'radio',
                    'label' => 'Type d\'hébergement actuel',
                    'required' => true,
                    'options' => array(
                        'cloud' => 'Cloud public (AWS, Azure, GCP)',
                        'hybrid' => 'Infrastructure hybride',
                        'onpremise' => 'Serveurs sur site'
                    )
                ),
                'cloud_services' => array(
                    'type' => 'radio',
                    'label' => 'Services cloud utilisés',
                    'required' => true,
                    'options' => array(
                        'multiple' => 'Multiples services (SaaS, PaaS)',
                        'basic' => 'Services basiques (stockage)',
                        'none' => 'Aucun service cloud'
                    )
                ),
                'remote_work' => array(
                    'type' => 'radio',
                    'label' => 'Capacité de télétravail',
                    'required' => true,
                    'options' => array(
                        'full' => 'Télétravail complet possible',
                        'partial' => 'Télétravail partiel',
                        'none' => 'Pas de télétravail'
                    )
                ),
                'network_performance' => array(
                    'type' => 'radio',
                    'label' => 'Performance réseau',
                    'required' => true,
                    'options' => array(
                        'excellent' => 'Excellente (>100 Mbps)',
                        'good' => 'Bonne (10-100 Mbps)',
                        'poor' => 'Limitée (<10 Mbps)'
                    )
                )
            )
        ),
        5 => array(
            'title' => 'Automatisation',
            'description' => 'Évaluez votre niveau d\'automatisation des processus.',
            'questions' => array(
                'manual_processes' => array(
                    'type' => 'radio',
                    'label' => 'Processus manuels identifiés',
                    'required' => true,
                    'options' => array(
                        'few' => 'Peu de processus manuels',
                        'some' => 'Quelques processus manuels',
                        'many' => 'Beaucoup de processus manuels'
                    )
                ),
                'automation_tools' => array(
                    'type' => 'radio',
                    'label' => 'Outils d\'automatisation existants',
                    'required' => true,
                    'options' => array(
                        'advanced' => 'Outils avancés (RPA, workflows)',
                        'basic' => 'Outils basiques (scripts)',
                        'none' => 'Aucun outil d\'automatisation'
                    )
                ),
                'integrations' => array(
                    'type' => 'radio',
                    'label' => 'Intégrations entre systèmes',
                    'required' => true,
                    'options' => array(
                        'many' => 'Nombreuses intégrations',
                        'some' => 'Quelques intégrations',
                        'none' => 'Systèmes isolés'
                    )
                ),
                'time_savings' => array(
                    'type' => 'radio',
                    'label' => 'Gains de temps recherchés',
                    'required' => true,
                    'options' => array(
                        'high' => 'Gains importants (>20h/semaine)',
                        'medium' => 'Gains modérés (5-20h/semaine)',
                        'low' => 'Gains faibles (<5h/semaine)'
                    )
                )
            )
        ),
        6 => array(
            'title' => 'Synthèse et validation',
            'description' => 'Vérifiez vos réponses avant de finaliser l\'audit.',
            'questions' => array()
        )
    );
}

/*
 * View
 */

$title = 'Audit Digital - Étape ' . $step . '/6';
llxHeader('', $title, '');

?>

<style>
.audit-wizard {
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.audit-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #0066CC, #004499);
    color: white;
    border-radius: 8px;
}

.audit-progress {
    margin-bottom: 30px;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0066CC, #28a745);
    transition: width 0.3s ease;
    width: <?php echo ($step / 6) * 100; ?>%;
}

.progress-text {
    text-align: center;
    margin-top: 10px;
    font-weight: bold;
    color: #666;
}

.audit-form-group {
    margin-bottom: 25px;
}

.audit-form-label {
    display: block;
    margin-bottom: 12px;
    font-weight: bold;
    color: #333;
    font-size: 16px;
}

.audit-form-label.required:after {
    content: ' *';
    color: #e74c3c;
}

.audit-form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.audit-form-control:focus {
    outline: none;
    border-color: #0066CC;
}

.audit-radio-group {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 10px;
}

.audit-radio-item {
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
}

.audit-radio-item:hover {
    border-color: #0066CC;
    background-color: #f8f9fa;
}

.audit-radio-item input[type="radio"] {
    margin-right: 15px;
    transform: scale(1.2);
}

.audit-radio-item input[type="radio"]:checked {
    accent-color: #0066CC;
}

.audit-radio-item.selected {
    border-color: #0066CC;
    background-color: #e3f2fd;
}

.audit-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.audit-btn {
    padding: 15px 30px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.audit-btn-primary {
    background-color: #0066CC;
    color: white;
}

.audit-btn-primary:hover {
    background-color: #004499;
    transform: translateY(-2px);
}

.audit-btn-secondary {
    background-color: #6c757d;
    color: white;
}

.audit-btn-secondary:hover {
    background-color: #545b62;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

.success {
    background-color: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
}

.step-summary {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.summary-item {
    margin-bottom: 10px;
    padding: 10px;
    background: white;
    border-radius: 4px;
    border-left: 4px solid #0066CC;
}
</style>

<div class="audit-wizard">
    <div class="audit-header">
        <h1>🎯 Audit Digital - Étape <?php echo $step; ?>/6</h1>
        <p><?php echo $questionnaire[$step]['title']; ?></p>
    </div>
    
    <!-- Progress Bar -->
    <div class="audit-progress">
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        <div class="progress-text">
            Étape <?php echo $step; ?> sur 6 - <?php echo round(($step / 6) * 100); ?>% complété
        </div>
    </div>
    
    <?php if ($error) { ?>
    <div class="error">
        <?php echo $message; ?>
    </div>
    <?php } ?>
    
    <?php if ($step == 6) { ?>
        <!-- Summary Step -->
        <div class="step-summary">
            <h3>📋 Récapitulatif de vos réponses</h3>
            <?php
            for ($i = 1; $i <= 5; $i++) {
                if (isset($_SESSION['audit_wizard']['step'.$i])) {
                    echo '<div class="summary-item">';
                    echo '<strong>Étape '.$i.' - '.$questionnaire[$i]['title'].':</strong><br>';
                    foreach ($_SESSION['audit_wizard']['step'.$i] as $key => $value) {
                        if ($key != 'token' && $key != 'action' && $key != 'step') {
                            echo '• '.$key.': '.$value.'<br>';
                        }
                    }
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="next_step">
            <input type="hidden" name="step" value="<?php echo $step; ?>">
            
            <div class="audit-buttons">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?step=<?php echo ($step-1); ?>" class="audit-btn audit-btn-secondary">
                    ← Étape précédente
                </a>
                <button type="submit" class="audit-btn audit-btn-primary">
                    🎯 Finaliser l'audit
                </button>
            </div>
        </form>
        
    <?php } else { ?>
        <!-- Regular Step -->
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="next_step">
            <input type="hidden" name="step" value="<?php echo $step; ?>">
            
            <div style="margin-bottom: 20px;">
                <p style="font-size: 16px; color: #666; line-height: 1.6;">
                    <?php echo $questionnaire[$step]['description']; ?>
                </p>
            </div>
            
            <?php if ($step == 1) { ?>
                <!-- Step 1: General Information -->
                
                <!-- Structure Type -->
                <div class="audit-form-group">
                    <label class="audit-form-label required">Type de structure</label>
                    <div class="audit-radio-group">
                        <?php foreach ($questionnaire[1]['questions']['structure_type']['options'] as $value => $label) { ?>
                        <div class="audit-radio-item">
                            <input type="radio" id="structure_type_<?php echo $value; ?>" name="structure_type" value="<?php echo $value; ?>" 
                                   <?php echo (isset($_SESSION['audit_wizard']['step1']['structure_type']) && $_SESSION['audit_wizard']['step1']['structure_type'] == $value) ? 'checked' : ''; ?> required>
                            <label for="structure_type_<?php echo $value; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                
                <!-- Company -->
                <div class="audit-form-group">
                    <label class="audit-form-label required">Société</label>
                    <?php 
                    $selected_soc = isset($_SESSION['audit_wizard']['step1']['fk_soc']) ? $_SESSION['audit_wizard']['step1']['fk_soc'] : 0;
                    echo $formcompany->select_company($selected_soc, 'fk_soc', '', 'Sélectionner une société', 1, 0, null, 0, 'audit-form-control'); 
                    ?>
                </div>
                
                <!-- Project (optional) -->
                <div class="audit-form-group">
                    <label class="audit-form-label">Projet (optionnel)</label>
                    <select name="fk_projet" class="audit-form-control">
                        <option value="">-- Sélectionner --</option>
                    </select>
                </div>
                
                <!-- Sector -->
                <div class="audit-form-group">
                    <label class="audit-form-label required">Secteur d'activité</label>
                    <select name="sector" class="audit-form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php 
                        $selected_sector = isset($_SESSION['audit_wizard']['step1']['sector']) ? $_SESSION['audit_wizard']['step1']['sector'] : '';
                        foreach ($questionnaire[1]['questions']['sector']['options'] as $value => $label) { ?>
                        <option value="<?php echo $value; ?>" <?php echo ($selected_sector == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                </div>
                
                <!-- Employees Count -->
                <div class="audit-form-group">
                    <label class="audit-form-label required">Nombre d'employés</label>
                    <select name="employees_count" class="audit-form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php 
                        $selected_employees = isset($_SESSION['audit_wizard']['step1']['employees_count']) ? $_SESSION['audit_wizard']['step1']['employees_count'] : '';
                        foreach ($questionnaire[1]['questions']['employees_count']['options'] as $value => $label) { ?>
                        <option value="<?php echo $value; ?>" <?php echo ($selected_employees == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                </div>
                
                <!-- IT Budget -->
                <div class="audit-form-group">
                    <label class="audit-form-label required">Budget IT annuel</label>
                    <select name="it_budget" class="audit-form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php 
                        $selected_budget = isset($_SESSION['audit_wizard']['step1']['it_budget']) ? $_SESSION['audit_wizard']['step1']['it_budget'] : '';
                        foreach ($questionnaire[1]['questions']['it_budget']['options'] as $value => $label) { ?>
                        <option value="<?php echo $value; ?>" <?php echo ($selected_budget == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                </div>
                
            <?php } else { ?>
                <!-- Steps 2-5: Questions -->
                <?php foreach ($questionnaire[$step]['questions'] as $question_key => $question) { ?>
                <div class="audit-form-group">
                    <label class="audit-form-label <?php echo $question['required'] ? 'required' : ''; ?>">
                        <?php echo $question['label']; ?>
                    </label>
                    
                    <?php if ($question['type'] == 'radio') { ?>
                    <div class="audit-radio-group">
                        <?php foreach ($question['options'] as $value => $label) { ?>
                        <div class="audit-radio-item">
                            <input type="radio" id="<?php echo $question_key.'_'.$value; ?>" name="<?php echo $question_key; ?>" value="<?php echo $value; ?>" 
                                   <?php echo (isset($_SESSION['audit_wizard']['step'.$step][$question_key]) && $_SESSION['audit_wizard']['step'.$step][$question_key] == $value) ? 'checked' : ''; ?> 
                                   <?php echo $question['required'] ? 'required' : ''; ?>>
                            <label for="<?php echo $question_key.'_'.$value; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            <?php } ?>
            
            <!-- Buttons -->
            <div class="audit-buttons">
                <?php if ($step > 1) { ?>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?step=<?php echo ($step-1); ?>" class="audit-btn audit-btn-secondary">
                    ← Étape précédente
                </a>
                <?php } else { ?>
                <div></div>
                <?php } ?>
                
                <button type="submit" class="audit-btn audit-btn-primary">
                    <?php echo ($step < 6) ? 'Étape suivante →' : 'Finaliser l\'audit'; ?>
                </button>
            </div>
        </form>
    <?php } ?>
</div>

<script>
// Améliorer l'UX des boutons radio
document.querySelectorAll('.audit-radio-item').forEach(function(item) {
    item.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            
            // Retirer la classe selected des autres items du même groupe
            const groupName = radio.name;
            document.querySelectorAll('input[name="' + groupName + '"]').forEach(function(otherRadio) {
                otherRadio.closest('.audit-radio-item').classList.remove('selected');
            });
            
            // Ajouter la classe selected à l'item sélectionné
            this.classList.add('selected');
        }
    });
});

// Marquer les items déjà sélectionnés
document.querySelectorAll('input[type="radio"]:checked').forEach(function(radio) {
    radio.closest('.audit-radio-item').classList.add('selected');
});

// Validation côté client
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[required]');
    let hasError = false;
    
    requiredFields.forEach(function(field) {
        if (field.type === 'radio') {
            const radioGroup = document.querySelectorAll('input[name="' + field.name + '"]');
            let isChecked = false;
            radioGroup.forEach(function(radio) {
                if (radio.checked) isChecked = true;
            });
            if (!isChecked) {
                hasError = true;
                radioGroup.forEach(function(radio) {
                    radio.closest('.audit-radio-item').style.borderColor = '#e74c3c';
                });
            } else {
                radioGroup.forEach(function(radio) {
                    radio.closest('.audit-radio-item').style.borderColor = '#ddd';
                });
            }
        } else if (!field.value) {
            field.style.borderColor = '#e74c3c';
            hasError = true;
        } else {
            field.style.borderColor = '#ddd';
        }
    });
    
    if (hasError) {
        e.preventDefault();
        alert('Veuillez remplir tous les champs obligatoires');
        window.scrollTo(0, 0);
    }
});
</script>

<?php

llxFooter();
?>
EOF

print_status "Wizard complet créé avec 6 étapes"

print_info "\n=== 3. REDÉMARRAGE APACHE ==="
sudo systemctl restart apache2
print_status "Apache redémarré"

print_info "\n=== RÉSULTAT ==="
print_status "🎉 CORRECTIONS APPLIQUÉES !"
echo ""
print_info "✅ PROBLÈMES RÉSOLUS :"
echo "1. Erreur FormProject dans audit_card.php"
echo "2. Wizard complet avec 6 étapes"
echo "3. Calcul automatique des scores"
echo "4. Génération de recommandations"
echo "5. Interface moderne et responsive"
echo ""
print_info "🧪 TESTEZ MAINTENANT :"
echo "1. Wizard: http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo "2. Parcourez les 6 étapes complètes"
echo "3. Vérifiez la création d'audit avec scores"
echo "4. Testez l'accès à audit_card.php"

exit 0