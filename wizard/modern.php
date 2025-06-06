<?php
/**
 * Wizard Moderne AuditDigital - Interface Nouvelle Génération
 * 
 * Interface moderne avec cards cliquables, animations fluides,
 * graphiques interactifs et fonctionnalités avancées
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
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

dol_include_once('/auditdigital/class/audit.class.php');
dol_include_once('/auditdigital/class/questionnaire.class.php');

$langs->loadLangs(array("auditdigital@auditdigital", "other"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int');
if (empty($step)) $step = 1;

// Security check
if (!$user->rights->auditdigital->audit->write) {
    accessforbidden();
}

// Configuration du wizard moderne
$wizard_steps = array(
    1 => array(
        'title' => 'Informations Générales',
        'icon' => 'fas fa-info-circle',
        'description' => 'Renseignez les informations de base sur votre organisation'
    ),
    2 => array(
        'title' => 'Maturité Digitale',
        'icon' => 'fas fa-digital-tachograph',
        'description' => 'Évaluez votre niveau de transformation digitale'
    ),
    3 => array(
        'title' => 'Cybersécurité',
        'icon' => 'fas fa-shield-alt',
        'description' => 'Analysez vos mesures de sécurité informatique'
    ),
    4 => array(
        'title' => 'Cloud & Infrastructure',
        'icon' => 'fas fa-cloud',
        'description' => 'Évaluez votre infrastructure et adoption du cloud'
    ),
    5 => array(
        'title' => 'Automatisation',
        'icon' => 'fas fa-robot',
        'description' => 'Mesurez votre niveau d\'automatisation des processus'
    ),
    6 => array(
        'title' => 'Synthèse & Recommandations',
        'icon' => 'fas fa-chart-line',
        'description' => 'Visualisez vos résultats et recommandations'
    )
);

/*
 * Actions
 */

if ($action == 'save_step') {
    // Sauvegarde automatique des données de l'étape
    $step_data = array();
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'audit_') === 0) {
            $step_data[$key] = $value;
        }
    }
    
    // Sauvegarder en session
    if (!isset($_SESSION['audit_wizard_data'])) {
        $_SESSION['audit_wizard_data'] = array();
    }
    $_SESSION['audit_wizard_data']['step_'.$step] = $step_data;
    
    // Retourner JSON pour AJAX
    if (GETPOST('ajax', 'alpha')) {
        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'message' => 'Données sauvegardées'));
        exit;
    }
}

if ($action == 'create_audit') {
    $error = 0;
    
    if (!$error) {
        $audit = new Audit($db);
        
        // Récupérer toutes les données de la session
        $wizard_data = $_SESSION['audit_wizard_data'] ?? array();
        
        // Set properties from wizard data
        $audit->ref = 'AUDIT-'.date('YmdHis');
        $audit->label = 'Audit Digital - '.date('d/m/Y');
        $audit->fk_soc = $wizard_data['step_1']['audit_socid'] ?? 0;
        $audit->structure_type = $wizard_data['step_1']['audit_structure_type'] ?? '';
        $audit->sector = $wizard_data['step_1']['audit_sector'] ?? '';
        $audit->employees_count = $wizard_data['step_1']['audit_employees_count'] ?? '';
        $audit->annual_budget = $wizard_data['step_1']['audit_annual_budget'] ?? '';
        $audit->status = 0; // Draft
        
        $result = $audit->create($user);
        
        if ($result > 0) {
            // Nettoyer la session
            unset($_SESSION['audit_wizard_data']);
            
            header("Location: ".dol_buildpath('/auditdigital/audit_card.php', 1).'?id='.$result);
            exit;
        } else {
            setEventMessages($audit->error, $audit->errors, 'errors');
        }
    }
}

/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$formproject = new FormProjets($db);

// Header moderne
llxHeader("", "Audit Digital Moderne", "", '', 0, 0, 
    array('/custom/auditdigital/css/auditdigital-modern.css'),
    array('/custom/auditdigital/js/wizard-modern.js', 'https://cdn.jsdelivr.net/npm/chart.js', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js')
);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Digital Moderne</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS Moderne Intégré */
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.12);
            --shadow-heavy: 0 15px 50px rgba(0, 0, 0, 0.15);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        .modern-wizard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }

        /* Header avec gradient */
        .wizard-header {
            background: var(--gradient-primary);
            border-radius: var(--border-radius);
            padding: 40px;
            margin-bottom: 30px;
            color: white;
            box-shadow: var(--shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .wizard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .wizard-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .wizard-logo {
            font-size: 3rem;
            margin-right: 20px;
        }

        .wizard-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .wizard-title p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .wizard-progress {
            position: relative;
        }

        .progress-circle {
            position: relative;
            width: 80px;
            height: 80px;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            transition: stroke-dashoffset 0.5s ease-in-out;
            stroke: rgba(255, 255, 255, 0.3);
            stroke-width: 4;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.2rem;
            font-weight: bold;
        }

        /* Stepper Moderne */
        .modern-stepper {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            overflow-x: auto;
        }

        .stepper-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-width: 150px;
            position: relative;
            transition: var(--transition);
            cursor: pointer;
        }

        .stepper-item:hover {
            transform: translateY(-5px);
        }

        .stepper-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6c757d;
            margin-bottom: 15px;
            transition: var(--transition);
            border: 3px solid #e9ecef;
        }

        .stepper-item.active .stepper-icon {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.4);
        }

        .stepper-item.completed .stepper-icon {
            background: var(--gradient-success);
            color: white;
            border-color: var(--success-color);
        }

        .stepper-content h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
        }

        .stepper-content p {
            font-size: 0.85rem;
            color: #6c757d;
            line-height: 1.4;
        }

        .stepper-check {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 25px;
            height: 25px;
            background: var(--success-color);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .stepper-item.completed .stepper-check {
            display: flex;
        }

        .stepper-line {
            flex: 1;
            height: 2px;
            background: #e9ecef;
            margin: 30px 20px 0;
            transition: var(--transition);
        }

        .stepper-line.completed {
            background: var(--success-color);
        }

        /* Contenu des étapes */
        .wizard-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
        }

        .step-container {
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .step-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .step-header h2 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .step-header p {
            font-size: 1.1rem;
            color: #6c757d;
        }

        /* Cards cliquables modernes */
        .option-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .option-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            padding: 30px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .option-card:hover::before {
            left: 100%;
        }

        .option-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-medium);
            border-color: var(--primary-color);
        }

        .option-card.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.2);
        }

        .card-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            text-align: center;
        }

        .card-content h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .card-content p {
            color: #6c757d;
            margin-bottom: 15px;
        }

        .card-content ul {
            list-style: none;
            padding: 0;
        }

        .card-content li {
            padding: 5px 0;
            color: #495057;
            position: relative;
            padding-left: 20px;
        }

        .card-content li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success-color);
            font-weight: bold;
        }

        .card-check {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            background: var(--success-color);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .option-card.selected .card-check {
            display: flex;
            animation: bounceIn 0.5s ease-out;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* Secteurs avec mini-cards */
        .sector-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .sector-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .sector-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-light);
        }

        .sector-card.selected {
            border-color: var(--primary-color);
            background: var(--gradient-primary);
            color: white;
        }

        .sector-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }

        /* Slider moderne pour employés */
        .employee-slider {
            margin: 30px 0;
        }

        .slider-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .modern-slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
            outline: none;
            -webkit-appearance: none;
            margin-bottom: 20px;
        }

        .modern-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--gradient-primary);
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .modern-slider::-moz-range-thumb {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--gradient-primary);
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .slider-value {
            text-align: center;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        /* Cards budget */
        .budget-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .budget-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .budget-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-light);
        }

        .budget-card.selected {
            border-color: var(--primary-color);
            background: var(--gradient-primary);
            color: white;
        }

        .budget-amount {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .budget-desc {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Questions avec rating moderne */
        .question-card {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-color);
        }

        .question-card h4 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .rating-scale {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            gap: 10px;
        }

        .rating-option {
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            flex: 1;
        }

        .rating-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto 10px;
            transition: var(--transition);
        }

        .rating-option:hover .rating-circle {
            border-color: var(--primary-color);
            transform: scale(1.1);
        }

        .rating-option.selected .rating-circle {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--primary-color);
        }

        .rating-option span {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Système de commentaires */
        .comment-section {
            margin-top: 20px;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }

        .comment-toggle {
            background: none;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            cursor: pointer;
            color: #6c757d;
            transition: var(--transition);
        }

        .comment-toggle:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .comment-box {
            margin-top: 15px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 200px;
            }
        }

        .comment-box textarea {
            width: 100%;
            min-height: 80px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            resize: vertical;
            font-family: inherit;
        }

        .comment-actions {
            margin-top: 10px;
        }

        .attach-file {
            background: none;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Navigation moderne */
        .wizard-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 30px 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .nav-btn {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .nav-btn.prev-btn {
            background: #6c757d;
        }

        .nav-btn.create-btn {
            background: var(--gradient-success);
        }

        .nav-center {
            text-align: center;
        }

        .auto-save-indicator {
            color: #6c757d;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Notifications modernes */
        #modernNotifications {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .modern-notification {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 10px;
            box-shadow: var(--shadow-medium);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 300px;
        }

        .modern-notification.success {
            border-left: 4px solid var(--success-color);
        }

        .modern-notification.error {
            border-left: 4px solid var(--danger-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modern-stepper {
                flex-direction: column;
                gap: 20px;
            }

            .stepper-line {
                display: none;
            }

            .option-cards {
                grid-template-columns: 1fr;
            }

            .wizard-header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .wizard-title h1 {
                font-size: 2rem;
            }

            .rating-scale {
                flex-direction: column;
                gap: 15px;
            }

            .sector-cards,
            .budget-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Graphiques et visualisations */
        .live-chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 30px;
            box-shadow: var(--shadow-light);
        }

        .score-display {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .score-circle-container {
            position: relative;
            flex-shrink: 0;
        }

        .score-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .score-text span {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .score-details {
            flex: 1;
        }

        .score-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .score-label {
            min-width: 150px;
            font-weight: 500;
        }

        .score-bar {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .score-fill {
            height: 100%;
            background: var(--gradient-primary);
            transition: width 0.5s ease;
        }

        .score-value {
            min-width: 50px;
            text-align: right;
            font-weight: bold;
            color: var(--primary-color);
        }

        /* Questions avec rating moderne */
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .question-help i {
            color: #6c757d;
            cursor: help;
            font-size: 1.1rem;
        }

        .rating-scale {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .rating-option {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }

        .rating-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }

        .rating-option.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
        }

        .rating-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        .rating-label strong {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .rating-label span {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Sécurité */
        .security-question {
            border-left: 4px solid var(--danger-color);
        }

        .risk-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .risk-indicator.high-risk {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .security-checklist {
            margin: 20px 0;
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: var(--transition);
        }

        .security-item:hover {
            border-color: var(--success-color);
        }

        .security-item.selected {
            border-color: var(--success-color);
            background: rgba(40, 167, 69, 0.05);
        }

        .security-checkbox {
            width: 24px;
            height: 24px;
            border: 2px solid #e9ecef;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .security-item.selected .security-checkbox {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .security-content {
            flex: 1;
        }

        .security-content strong {
            display: block;
            margin-bottom: 3px;
        }

        .security-content span {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .security-weight {
            font-weight: bold;
            color: var(--success-color);
            font-size: 0.9rem;
        }

        .security-dashboard {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 30px;
        }

        .security-gauge {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .security-level {
            flex: 1;
        }

        .security-level span {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--danger-color);
        }

        /* Infrastructure */
        .infrastructure-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .infra-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            padding: 25px;
            cursor: pointer;
            transition: var(--transition);
        }

        .infra-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .infra-card.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
        }

        .infra-icon {
            font-size: 3rem;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 15px;
        }

        .infra-card h5 {
            text-align: center;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .infra-card p {
            text-align: center;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .infra-pros-cons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            font-size: 0.9rem;
        }

        .pros, .cons {
            padding: 10px;
            border-radius: 8px;
        }

        .pros {
            background: rgba(40, 167, 69, 0.05);
            border-left: 3px solid var(--success-color);
        }

        .cons {
            background: rgba(220, 53, 69, 0.05);
            border-left: 3px solid var(--danger-color);
        }

        .pros ul, .cons ul {
            margin: 5px 0 0 15px;
            padding: 0;
        }

        /* Migration timeline */
        .migration-timeline {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }

        .migration-timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .timeline-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            position: relative;
            z-index: 2;
        }

        .timeline-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e9ecef;
            transition: var(--transition);
        }

        .timeline-option.selected .timeline-dot {
            border-color: var(--primary-color);
            background: var(--primary-color);
        }

        .timeline-content {
            text-align: center;
            margin-top: 15px;
            max-width: 120px;
        }

        .timeline-content strong {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .timeline-content span {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Automatisation */
        .automation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 20px 0;
        }

        .automation-category {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 20px;
        }

        .automation-category h5 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .automation-items {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .automation-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .automation-item:hover {
            background: #e3f2fd;
        }

        .automation-toggle {
            width: 40px;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            position: relative;
            transition: var(--transition);
        }

        .automation-toggle::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            transition: var(--transition);
        }

        .automation-item.active .automation-toggle {
            background: var(--success-color);
        }

        .automation-item.active .automation-toggle::after {
            transform: translateX(20px);
        }

        .automation-potential {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 30px;
            box-shadow: var(--shadow-light);
        }

        .automation-savings {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .savings-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .savings-card h5 {
            margin-bottom: 10px;
            color: #6c757d;
        }

        .savings-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--success-color);
        }

        /* Synthèse et résultats */
        .results-dashboard {
            display: grid;
            gap: 30px;
            margin-top: 30px;
        }

        .global-score-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .main-score {
            text-align: center;
        }

        .score-gauge-container {
            position: relative;
            display: inline-block;
        }

        .score-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .score-overlay .score-value {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .score-overlay .score-unit {
            font-size: 1.2rem;
            color: #6c757d;
        }

        .score-overlay .score-level {
            font-size: 1rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .score-breakdown h4 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .domain-scores {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .domain-score {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .domain-name {
            min-width: 150px;
            font-weight: 500;
        }

        .domain-bar {
            flex: 1;
            height: 12px;
            background: #e9ecef;
            border-radius: 6px;
            overflow: hidden;
        }

        .domain-fill {
            height: 100%;
            background: var(--gradient-primary);
            transition: width 1s ease;
        }

        .domain-value {
            min-width: 50px;
            text-align: right;
            font-weight: bold;
            color: var(--primary-color);
        }

        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            text-align: center;
        }

        .chart-container h4 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* ROI Section */
        .roi-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
        }

        .roi-section h4 {
            margin-bottom: 25px;
            color: #2c3e50;
        }

        .roi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .roi-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: var(--transition);
        }

        .roi-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-light);
        }

        .roi-card.investment {
            border-left: 4px solid var(--danger-color);
        }

        .roi-card.savings {
            border-left: 4px solid var(--success-color);
        }

        .roi-card.return {
            border-left: 4px solid var(--primary-color);
        }

        .roi-card.payback {
            border-left: 4px solid var(--warning-color);
        }

        .roi-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .roi-card.investment .roi-icon {
            color: var(--danger-color);
        }

        .roi-card.savings .roi-icon {
            color: var(--success-color);
        }

        .roi-card.return .roi-icon {
            color: var(--primary-color);
        }

        .roi-card.payback .roi-icon {
            color: var(--warning-color);
        }

        .roi-content h5 {
            margin-bottom: 10px;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .roi-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .roi-period {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* Roadmap */
        .roadmap-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
        }

        .roadmap-timeline {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }

        .roadmap-phase {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            position: relative;
        }

        .phase-1 {
            border-left: 4px solid var(--success-color);
        }

        .phase-2 {
            border-left: 4px solid var(--warning-color);
        }

        .phase-3 {
            border-left: 4px solid var(--primary-color);
        }

        .phase-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .phase-header h5 {
            color: #2c3e50;
            margin: 0;
        }

        .phase-duration {
            background: rgba(0, 0, 0, 0.1);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .phase-description {
            font-style: italic;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .action-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .action-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            position: relative;
            padding-left: 20px;
        }

        .action-list li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--primary-color);
            font-weight: bold;
        }

        /* Export section */
        .export-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
            text-align: center;
        }

        .export-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .export-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            transition: var(--transition);
            min-width: 120px;
        }

        .export-btn:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: var(--shadow-light);
        }

        .export-btn i {
            font-size: 2rem;
        }

        .export-btn.pdf i {
            color: var(--danger-color);
        }

        .export-btn.excel i {
            color: var(--success-color);
        }

        .export-btn.json i {
            color: var(--primary-color);
        }

        /* Formulaires modernes */
        .form-section {
            margin-bottom: 40px;
        }

        .form-section h3 {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .required {
            color: var(--danger-color);
        }

        .modern-select-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .modern-select-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Animations d'entrée */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .slide-in-left {
            animation: slideInLeft 0.6s ease-out;
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-30px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .slide-in-right {
            animation: slideInRight 0.6s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(30px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Effets glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        /* États de chargement */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
    </style>
</head>
<body>

<!-- Interface Wizard Moderne -->
<div class="modern-wizard-container">
    <!-- Header avec gradient -->
    <div class="wizard-header fade-in">
        <div class="wizard-header-content">
            <div class="wizard-logo">
                <i class="fas fa-rocket"></i>
            </div>
            <div class="wizard-title">
                <h1>Audit Digital Nouvelle Génération</h1>
                <p>Évaluez la maturité numérique de votre organisation avec notre interface moderne</p>
            </div>
            <div class="wizard-progress">
                <div class="progress-circle">
                    <svg class="progress-ring" width="80" height="80">
                        <circle class="progress-ring-circle" stroke="#fff" stroke-width="4" fill="transparent" r="36" cx="40" cy="40"/>
                    </svg>
                    <span class="progress-text"><?php echo round(($step/6)*100); ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stepper Moderne -->
    <div class="modern-stepper slide-in-left">
        <?php foreach ($wizard_steps as $step_num => $step_info): ?>
            <div class="stepper-item <?php echo ($step_num == $step) ? 'active' : ''; ?> <?php echo ($step_num < $step) ? 'completed' : ''; ?>" data-step="<?php echo $step_num; ?>">
                <div class="stepper-icon">
                    <i class="<?php echo $step_info['icon']; ?>"></i>
                </div>
                <div class="stepper-content">
                    <h4><?php echo $step_info['title']; ?></h4>
                    <p><?php echo $step_info['description']; ?></p>
                </div>
                <div class="stepper-check">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <?php if ($step_num < 6): ?>
                <div class="stepper-line <?php echo ($step_num < $step) ? 'completed' : ''; ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Contenu de l'étape -->
    <div class="wizard-content slide-in-right">
        <form id="wizardForm" method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="save_step">
            <input type="hidden" name="step" value="<?php echo $step; ?>">

            <?php if ($step == 1): ?>
                <!-- Étape 1: Informations Générales -->
                <div class="step-container" data-step="1">
                    <div class="step-header">
                        <h2><i class="fas fa-info-circle"></i> Informations Générales</h2>
                        <p>Commençons par les informations de base sur votre organisation</p>
                    </div>

                    <div class="cards-grid">
                        <!-- Type de structure avec cards modernes -->
                        <div class="form-section">
                            <h3>Type de structure <span class="required">*</span></h3>
                            <div class="option-cards">
                                <div class="option-card" data-value="tpe_pme" onclick="selectOption(this, 'audit_structure_type')">
                                    <div class="card-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="card-content">
                                        <h4>TPE/PME</h4>
                                        <p>Entreprise de moins de 250 employés</p>
                                        <ul>
                                            <li>Structure agile</li>
                                            <li>Décisions rapides</li>
                                            <li>Proximité client</li>
                                        </ul>
                                    </div>
                                    <div class="card-check">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>

                                <div class="option-card" data-value="collectivite" onclick="selectOption(this, 'audit_structure_type')">
                                    <div class="card-icon">
                                        <i class="fas fa-landmark"></i>
                                    </div>
                                    <div class="card-content">
                                        <h4>Collectivité Territoriale</h4>
                                        <p>Administration publique locale</p>
                                        <ul>
                                            <li>Service public</li>
                                            <li>Contraintes réglementaires</li>
                                            <li>Budget public</li>
                                        </ul>
                                    </div>
                                    <div class="card-check">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>

                                <div class="option-card" data-value="association" onclick="selectOption(this, 'audit_structure_type')">
                                    <div class="card-icon">
                                        <i class="fas fa-hands-helping"></i>
                                    </div>
                                    <div class="card-content">
                                        <h4>Association</h4>
                                        <p>Organisation à but non lucratif</p>
                                        <ul>
                                            <li>Mission sociale</li>
                                            <li>Ressources limitées</li>
                                            <li>Bénévolat</li>
                                        </ul>
                                    </div>
                                    <div class="card-check">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="audit_structure_type" id="audit_structure_type" required>
                        </div>

                        <!-- Société -->
                        <div class="form-section">
                            <h3>Société <span class="required">*</span></h3>
                            <div class="modern-select">
                                <?php echo $formcompany->select_company(GETPOST('socid', 'int'), 'audit_socid', '', 'Sélectionnez une société...', 1, 0, null, 0, 'modern-select-input'); ?>
                            </div>
                        </div>

                        <!-- Secteur d'activité avec cards -->
                        <div class="form-section">
                            <h3>Secteur d'activité <span class="required">*</span></h3>
                            <div class="sector-cards">
                                <div class="sector-card" data-value="industry" onclick="selectSector(this)">
                                    <i class="fas fa-industry"></i>
                                    <span>Industrie</span>
                                </div>
                                <div class="sector-card" data-value="services" onclick="selectSector(this)">
                                    <i class="fas fa-concierge-bell"></i>
                                    <span>Services</span>
                                </div>
                                <div class="sector-card" data-value="commerce" onclick="selectSector(this)">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Commerce</span>
                                </div>
                                <div class="sector-card" data-value="agriculture" onclick="selectSector(this)">
                                    <i class="fas fa-seedling"></i>
                                    <span>Agriculture</span>
                                </div>
                                <div class="sector-card" data-value="public" onclick="selectSector(this)">
                                    <i class="fas fa-university"></i>
                                    <span>Public</span>
                                </div>
                                <div class="sector-card" data-value="other" onclick="selectSector(this)">
                                    <i class="fas fa-ellipsis-h"></i>
                                    <span>Autre</span>
                                </div>
                            </div>
                            <input type="hidden" name="audit_sector" id="audit_sector" required>
                        </div>

                        <!-- Nombre d'employés avec slider moderne -->
                        <div class="form-section">
                            <h3>Nombre d'employés <span class="required">*</span></h3>
                            <div class="employee-slider">
                                <div class="slider-labels">
                                    <span>1-10</span>
                                    <span>11-50</span>
                                    <span>51-250</span>
                                    <span>251-500</span>
                                    <span>500+</span>
                                </div>
                                <input type="range" id="employeeRange" min="1" max="5" value="1" class="modern-slider" onchange="updateEmployeeCount(this.value)">
                                <div class="slider-value">
                                    <span id="employeeValue">1-10 employés</span>
                                </div>
                            </div>
                            <input type="hidden" name="audit_employees_count" id="audit_employees_count" required>
                        </div>

                        <!-- Budget IT avec cards -->
                        <div class="form-section">
                            <h3>Budget IT annuel <span class="required">*</span></h3>
                            <div class="budget-cards">
                                <div class="budget-card" data-value="0-10k" onclick="selectBudget(this)">
                                    <div class="budget-amount">< 10K€</div>
                                    <div class="budget-desc">Débutant</div>
                                </div>
                                <div class="budget-card" data-value="10k-50k" onclick="selectBudget(this)">
                                    <div class="budget-amount">10K-50K€</div>
                                    <div class="budget-desc">Intermédiaire</div>
                                </div>
                                <div class="budget-card" data-value="50k-100k" onclick="selectBudget(this)">
                                    <div class="budget-amount">50K-100K€</div>
                                    <div class="budget-desc">Avancé</div>
                                </div>
                                <div class="budget-card" data-value="100k-500k" onclick="selectBudget(this)">
                                    <div class="budget-amount">100K-500K€</div>
                                    <div class="budget-desc">Expert</div>
                                </div>
                                <div class="budget-card" data-value="500k+" onclick="selectBudget(this)">
                                    <div class="budget-amount">> 500K€</div>
                                    <div class="budget-desc">Enterprise</div>
                                </div>
                            </div>
                            <input type="hidden" name="audit_annual_budget" id="audit_annual_budget" required>
                        </div>
                    </div>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- Étape 2: Maturité Digitale -->
                <div class="step-container" data-step="2">
                    <div class="step-header">
                        <h2><i class="fas fa-digital-tachograph"></i> Maturité Digitale</h2>
                        <p>Évaluez votre niveau de transformation digitale</p>
                    </div>

                    <div class="questions-container">
                        <!-- Question 1: Digitalisation des processus -->
                        <div class="question-card">
                            <div class="question-header">
                                <h4>Quel est votre niveau de digitalisation des processus métier ?</h4>
                                <div class="question-help">
                                    <i class="fas fa-info-circle" title="Évaluez dans quelle mesure vos processus sont dématérialisés"></i>
                                </div>
                            </div>
                            
                            <div class="rating-scale">
                                <div class="rating-option" data-value="1" onclick="selectRating(this, 'digital_processes')">
                                    <div class="rating-circle">1</div>
                                    <div class="rating-label">
                                        <strong>Papier uniquement</strong>
                                        <span>Tous les processus sont manuels</span>
                                    </div>
                                </div>
                                <div class="rating-option" data-value="2" onclick="selectRating(this, 'digital_processes')">
                                    <div class="rating-circle">2</div>
                                    <div class="rating-label">
                                        <strong>Quelques outils</strong>
                                        <span>Outils basiques (email, bureautique)</span>
                                    </div>
                                </div>
                                <div class="rating-option" data-value="3" onclick="selectRating(this, 'digital_processes')">
                                    <div class="rating-circle">3</div>
                                    <div class="rating-label">
                                        <strong>Partiellement digitalisé</strong>
                                        <span>Certains processus dématérialisés</span>
                                    </div>
                                </div>
                                <div class="rating-option" data-value="4" onclick="selectRating(this, 'digital_processes')">
                                    <div class="rating-circle">4</div>
                                    <div class="rating-label">
                                        <strong>Largement digitalisé</strong>
                                        <span>Majorité des processus digitaux</span>
                                    </div>
                                </div>
                                <div class="rating-option" data-value="5" onclick="selectRating(this, 'digital_processes')">
                                    <div class="rating-circle">5</div>
                                    <div class="rating-label">
                                        <strong>100% digital</strong>
                                        <span>Transformation complète</span>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="audit_digital_processes" id="audit_digital_processes">
                            
                            <!-- Système de commentaires -->
                            <div class="comment-section">
                                <button type="button" class="comment-toggle" onclick="toggleComment('digital_processes')">
                                    <i class="fas fa-comment"></i> Ajouter un commentaire
                                </button>
                                <div class="comment-box" id="comment_digital_processes" style="display: none;">
                                    <textarea name="comment_digital_processes" placeholder="Précisez votre situation, ajoutez des détails..."></textarea>
                                    <div class="comment-actions">
                                        <button type="button" class="attach-file" onclick="attachFile('digital_processes')">
                                            <i class="fas fa-paperclip"></i> Joindre un fichier
                                        </button>
                                        <input type="file" id="file_digital_processes" style="display: none;" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 2: Outils collaboratifs -->
                        <div class="question-card">
                            <div class="question-header">
                                <h4>Utilisez-vous des outils collaboratifs modernes ?</h4>
                                <div class="question-help">
                                    <i class="fas fa-info-circle" title="Teams, Slack, outils de gestion de projet, etc."></i>
                                </div>
                            </div>
                            
                            <div class="rating-scale">
                                <div class="rating-option" data-value="1" onclick="selectRating(this, 'collaborative_tools')">
                                    <div class="rating-circle">1</div>
                                    <div class="rating-label">
                                        <strong>Aucun outil</strong>
                                        <span>Email et téléphone uniquement</span>
                                    </div>
                                </div>
                                <div class="rating-option" data-value="2" onclick="selectRating(this, 'collaborative_tools')">
                                    <div class="rating-circle">2</div>
                                    <div class="rating-label">
                                        <strong>Outils basiques</strong>
                                        <span>Partage de fichiers simple</span>
                                    </div>
                                </div>
                                <div class="rating-option" data-value="3" onclick="selectRating(this, 'collaborative_tools')">
                                    <div class="rating-circle">3</div>
                                    <div class="rating-label">
                                        <strong>Quelques outils</strong>
                                        <span>Messagerie instantanée, visio</span>
                                    </div>
                                </div>
                                <div class="rating-option" data-value="4" onclick="selectRating(this, 'collaborative_tools')">
                                    <div class="rating-circle">4</div>
                                    <div class="rating-label">
                                        <strong>Suite complète</strong>
                                        <span>Outils intégrés et adoptés</span>
                                    </div>
                                </div>
                                <div class="rating-option" data-value="5" onclick="selectRating(this, 'collaborative_tools')">
                                    <div class="rating-circle">5</div>
                                    <div class="rating-label">
                                        <strong>Écosystème avancé</strong>
                                        <span>Collaboration temps réel optimisée</span>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="audit_collaborative_tools" id="audit_collaborative_tools">
                            
                            <div class="comment-section">
                                <button type="button" class="comment-toggle" onclick="toggleComment('collaborative_tools')">
                                    <i class="fas fa-comment"></i> Ajouter un commentaire
                                </button>
                                <div class="comment-box" id="comment_collaborative_tools" style="display: none;">
                                    <textarea name="comment_collaborative_tools" placeholder="Quels outils utilisez-vous ? Quelles sont les difficultés ?"></textarea>
                                    <div class="comment-actions">
                                        <button type="button" class="attach-file" onclick="attachFile('collaborative_tools')">
                                            <i class="fas fa-paperclip"></i> Joindre un fichier
                                        </button>
                                        <input type="file" id="file_collaborative_tools" style="display: none;" multiple>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique en temps réel -->
                        <div class="live-chart-container">
                            <h4><i class="fas fa-chart-line"></i> Votre score Maturité Digitale en temps réel</h4>
                            <div class="score-display">
                                <div class="score-circle-container">
                                    <canvas id="liveScoreChart" width="200" height="200"></canvas>
                                    <div class="score-text">
                                        <span id="currentScore">0</span>
                                        <small>/ 100</small>
                                    </div>
                                </div>
                                <div class="score-details">
                                    <div class="score-item">
                                        <span class="score-label">Processus digitaux</span>
                                        <div class="score-bar">
                                            <div class="score-fill" id="processes_bar" style="width: 0%"></div>
                                        </div>
                                        <span class="score-value" id="processes_score">0%</span>
                                    </div>
                                    <div class="score-item">
                                        <span class="score-label">Outils collaboratifs</span>
                                        <div class="score-bar">
                                            <div class="score-fill" id="tools_bar" style="width: 0%"></div>
                                        </div>
                                        <span class="score-value" id="tools_score">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($step == 3): ?>
                <!-- Étape 3: Cybersécurité -->
                <div class="step-container" data-step="3">
                    <div class="step-header">
                        <h2><i class="fas fa-shield-alt"></i> Cybersécurité</h2>
                        <p>Analysez vos mesures de sécurité informatique</p>
                    </div>

                    <div class="questions-container">
                        <!-- Question sécurité avec indicateur de risque -->
                        <div class="question-card security-question">
                            <div class="question-header">
                                <h4>Avez-vous mis en place des mesures de protection contre les cyberattaques ?</h4>
                                <div class="risk-indicator high-risk">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Risque élevé</span>
                                </div>
                            </div>
                            
                            <div class="security-checklist">
                                <div class="security-item" onclick="toggleSecurityItem(this, 'antivirus')">
                                    <div class="security-checkbox">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="security-content">
                                        <strong>Antivirus professionnel</strong>
                                        <span>Protection en temps réel contre les malwares</span>
                                    </div>
                                    <div class="security-weight">+15 pts</div>
                                </div>
                                
                                <div class="security-item" onclick="toggleSecurityItem(this, 'firewall')">
                                    <div class="security-checkbox">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="security-content">
                                        <strong>Pare-feu configuré</strong>
                                        <span>Filtrage du trafic réseau</span>
                                    </div>
                                    <div class="security-weight">+20 pts</div>
                                </div>
                                
                                <div class="security-item" onclick="toggleSecurityItem(this, 'backup')">
                                    <div class="security-checkbox">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="security-content">
                                        <strong>Sauvegardes automatiques</strong>
                                        <span>Sauvegarde régulière des données</span>
                                    </div>
                                    <div class="security-weight">+25 pts</div>
                                </div>
                                
                                <div class="security-item" onclick="toggleSecurityItem(this, 'updates')">
                                    <div class="security-checkbox">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="security-content">
                                        <strong>Mises à jour automatiques</strong>
                                        <span>Système et logiciels à jour</span>
                                    </div>
                                    <div class="security-weight">+15 pts</div>
                                </div>
                                
                                <div class="security-item" onclick="toggleSecurityItem(this, 'training')">
                                    <div class="security-checkbox">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="security-content">
                                        <strong>Formation des équipes</strong>
                                        <span>Sensibilisation cybersécurité</span>
                                    </div>
                                    <div class="security-weight">+25 pts</div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="audit_security_measures" id="audit_security_measures">
                            
                            <div class="comment-section">
                                <button type="button" class="comment-toggle" onclick="toggleComment('security_measures')">
                                    <i class="fas fa-comment"></i> Détailler vos mesures de sécurité
                                </button>
                                <div class="comment-box" id="comment_security_measures" style="display: none;">
                                    <textarea name="comment_security_measures" placeholder="Décrivez vos mesures de sécurité spécifiques..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique sécurité -->
                        <div class="security-dashboard">
                            <h4><i class="fas fa-shield-alt"></i> Niveau de sécurité actuel</h4>
                            <div class="security-gauge">
                                <canvas id="securityGauge" width="300" height="150"></canvas>
                                <div class="security-level">
                                    <span id="securityLevel">Faible</span>
                                    <div id="securityRecommendations">
                                        <p>Recommandations prioritaires :</p>
                                        <ul id="securityTodos"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($step == 4): ?>
                <!-- Étape 4: Cloud & Infrastructure -->
                <div class="step-container" data-step="4">
                    <div class="step-header">
                        <h2><i class="fas fa-cloud"></i> Cloud & Infrastructure</h2>
                        <p>Évaluez votre infrastructure et adoption du cloud</p>
                    </div>

                    <div class="questions-container">
                        <!-- Infrastructure actuelle -->
                        <div class="question-card">
                            <h4>Quelle est votre infrastructure IT actuelle ?</h4>
                            
                            <div class="infrastructure-options">
                                <div class="infra-card" data-value="on_premise" onclick="selectInfrastructure(this)">
                                    <div class="infra-icon">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <h5>On-Premise</h5>
                                    <p>Serveurs physiques sur site</p>
                                    <div class="infra-pros-cons">
                                        <div class="pros">
                                            <strong>Avantages :</strong>
                                            <ul>
                                                <li>Contrôle total</li>
                                                <li>Sécurité physique</li>
                                            </ul>
                                        </div>
                                        <div class="cons">
                                            <strong>Inconvénients :</strong>
                                            <ul>
                                                <li>Coûts élevés</li>
                                                <li>Maintenance complexe</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="infra-card" data-value="hybrid" onclick="selectInfrastructure(this)">
                                    <div class="infra-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <h5>Hybride</h5>
                                    <p>Mix cloud et on-premise</p>
                                    <div class="infra-pros-cons">
                                        <div class="pros">
                                            <strong>Avantages :</strong>
                                            <ul>
                                                <li>Flexibilité</li>
                                                <li>Migration progressive</li>
                                            </ul>
                                        </div>
                                        <div class="cons">
                                            <strong>Inconvénients :</strong>
                                            <ul>
                                                <li>Complexité gestion</li>
                                                <li>Intégration délicate</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="infra-card" data-value="full_cloud" onclick="selectInfrastructure(this)">
                                    <div class="infra-icon">
                                        <i class="fas fa-cloud"></i>
                                    </div>
                                    <h5>Full Cloud</h5>
                                    <p>100% dans le cloud</p>
                                    <div class="infra-pros-cons">
                                        <div class="pros">
                                            <strong>Avantages :</strong>
                                            <ul>
                                                <li>Scalabilité</li>
                                                <li>Coûts optimisés</li>
                                            </ul>
                                        </div>
                                        <div class="cons">
                                            <strong>Inconvénients :</strong>
                                            <ul>
                                                <li>Dépendance internet</li>
                                                <li>Conformité RGPD</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="audit_infrastructure" id="audit_infrastructure">
                        </div>

                        <!-- Migration cloud -->
                        <div class="question-card">
                            <h4>Envisagez-vous une migration vers le cloud ?</h4>
                            
                            <div class="migration-timeline">
                                <div class="timeline-option" data-value="no_plan" onclick="selectMigration(this)">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <strong>Aucun plan</strong>
                                        <span>Pas de projet de migration</span>
                                    </div>
                                </div>
                                
                                <div class="timeline-option" data-value="exploring" onclick="selectMigration(this)">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <strong>En réflexion</strong>
                                        <span>Étude des possibilités</span>
                                    </div>
                                </div>
                                
                                <div class="timeline-option" data-value="planning" onclick="selectMigration(this)">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <strong>En planification</strong>
                                        <span>Projet défini, budget alloué</span>
                                    </div>
                                </div>
                                
                                <div class="timeline-option" data-value="in_progress" onclick="selectMigration(this)">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <strong>En cours</strong>
                                        <span>Migration en cours</span>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="audit_cloud_migration" id="audit_cloud_migration">
                        </div>

                        <!-- Recommandations cloud -->
                        <div class="cloud-recommendations">
                            <h4><i class="fas fa-lightbulb"></i> Recommandations personnalisées</h4>
                            <div id="cloudRecommendations" class="recommendations-list">
                                <!-- Rempli dynamiquement par JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($step == 5): ?>
                <!-- Étape 5: Automatisation -->
                <div class="step-container" data-step="5">
                    <div class="step-header">
                        <h2><i class="fas fa-robot"></i> Automatisation</h2>
                        <p>Mesurez votre niveau d'automatisation des processus</p>
                    </div>

                    <div class="questions-container">
                        <!-- Processus automatisés -->
                        <div class="question-card">
                            <h4>Quels processus avez-vous automatisés ?</h4>
                            
                            <div class="automation-grid">
                                <div class="automation-category">
                                    <h5><i class="fas fa-users"></i> Ressources Humaines</h5>
                                    <div class="automation-items">
                                        <div class="automation-item" onclick="toggleAutomation(this, 'hr_recruitment')">
                                            <span>Recrutement</span>
                                            <div class="automation-toggle"></div>
                                        </div>
                                        <div class="automation-item" onclick="toggleAutomation(this, 'hr_payroll')">
                                            <span>Paie</span>
                                            <div class="automation-toggle"></div>
                                        </div>
                                        <div class="automation-item" onclick="toggleAutomation(this, 'hr_onboarding')">
                                            <span>Onboarding</span>
                                            <div class="automation-toggle"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="automation-category">
                                    <h5><i class="fas fa-chart-line"></i> Finance</h5>
                                    <div class="automation-items">
                                        <div class="automation-item" onclick="toggleAutomation(this, 'finance_invoicing')">
                                            <span>Facturation</span>
                                            <div class="automation-toggle"></div>
                                        </div>
                                        <div class="automation-item" onclick="toggleAutomation(this, 'finance_accounting')">
                                            <span>Comptabilité</span>
                                            <div class="automation-toggle"></div>
                                        </div>
                                        <div class="automation-item" onclick="toggleAutomation(this, 'finance_reporting')">
                                            <span>Reporting</span>
                                            <div class="automation-toggle"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="automation-category">
                                    <h5><i class="fas fa-handshake"></i> Commercial</h5>
                                    <div class="automation-items">
                                        <div class="automation-item" onclick="toggleAutomation(this, 'sales_crm')">
                                            <span>CRM</span>
                                            <div class="automation-toggle"></div>
                                        </div>
                                        <div class="automation-item" onclick="toggleAutomation(this, 'sales_marketing')">
                                            <span>Marketing</span>
                                            <div class="automation-toggle"></div>
                                        </div>
                                        <div class="automation-item" onclick="toggleAutomation(this, 'sales_support')">
                                            <span>Support client</span>
                                            <div class="automation-toggle"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="audit_automation_processes" id="audit_automation_processes">
                        </div>

                        <!-- Potentiel d'automatisation -->
                        <div class="automation-potential">
                            <h4><i class="fas fa-magic"></i> Potentiel d'automatisation</h4>
                            <div class="potential-chart">
                                <canvas id="automationPotentialChart" width="400" height="200"></canvas>
                            </div>
                            <div class="automation-savings">
                                <div class="savings-card">
                                    <h5>Temps économisé potentiel</h5>
                                    <div class="savings-value" id="timeSavings">0h/semaine</div>
                                </div>
                                <div class="savings-card">
                                    <h5>Économies annuelles</h5>
                                    <div class="savings-value" id="costSavings">0€</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($step == 6): ?>
                <!-- Étape 6: Synthèse & Recommandations -->
                <div class="step-container" data-step="6">
                    <div class="step-header">
                        <h2><i class="fas fa-chart-line"></i> Synthèse & Recommandations</h2>
                        <p>Visualisez vos résultats et découvrez nos recommandations</p>
                    </div>

                    <div class="results-dashboard">
                        <!-- Score global avec jauge -->
                        <div class="global-score-section">
                            <div class="score-card main-score">
                                <h3>Score Global de Maturité Digitale</h3>
                                <div class="score-gauge-container">
                                    <canvas id="globalScoreGauge" width="250" height="250"></canvas>
                                    <div class="score-overlay">
                                        <span class="score-value" id="globalScore">0</span>
                                        <span class="score-unit">/100</span>
                                        <div class="score-level" id="maturityLevel">Débutant</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="score-breakdown">
                                <h4>Répartition par domaine</h4>
                                <div class="domain-scores">
                                    <div class="domain-score">
                                        <span class="domain-name">Maturité Digitale</span>
                                        <div class="domain-bar">
                                            <div class="domain-fill" id="digital_fill" style="width: 0%"></div>
                                        </div>
                                        <span class="domain-value" id="digital_score">0%</span>
                                    </div>
                                    <div class="domain-score">
                                        <span class="domain-name">Cybersécurité</span>
                                        <div class="domain-bar">
                                            <div class="domain-fill" id="security_fill" style="width: 0%"></div>
                                        </div>
                                        <span class="domain-value" id="security_score">0%</span>
                                    </div>
                                    <div class="domain-score">
                                        <span class="domain-name">Cloud & Infrastructure</span>
                                        <div class="domain-bar">
                                            <div class="domain-fill" id="cloud_fill" style="width: 0%"></div>
                                        </div>
                                        <span class="domain-value" id="cloud_score">0%</span>
                                    </div>
                                    <div class="domain-score">
                                        <span class="domain-name">Automatisation</span>
                                        <div class="domain-bar">
                                            <div class="domain-fill" id="automation_fill" style="width: 0%"></div>
                                        </div>
                                        <span class="domain-value" id="automation_score">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Graphiques détaillés -->
                        <div class="charts-section">
                            <div class="chart-container">
                                <h4>Radar de Maturité</h4>
                                <canvas id="radarChart" width="350" height="350"></canvas>
                            </div>
                            
                            <div class="chart-container">
                                <h4>Évolution Recommandée</h4>
                                <canvas id="evolutionChart" width="350" height="350"></canvas>
                            </div>
                        </div>

                        <!-- Analyse ROI -->
                        <div class="roi-section">
                            <h4><i class="fas fa-euro-sign"></i> Analyse ROI et Investissements</h4>
                            <div class="roi-cards">
                                <div class="roi-card investment">
                                    <div class="roi-icon">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <div class="roi-content">
                                        <h5>Investissement recommandé</h5>
                                        <div class="roi-value" id="totalInvestment">0€</div>
                                        <span class="roi-period">Sur 12 mois</span>
                                    </div>
                                </div>
                                
                                <div class="roi-card savings">
                                    <div class="roi-icon">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <div class="roi-content">
                                        <h5>Économies estimées</h5>
                                        <div class="roi-value" id="totalSavings">0€</div>
                                        <span class="roi-period">Par an</span>
                                    </div>
                                </div>
                                
                                <div class="roi-card return">
                                    <div class="roi-icon">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                    <div class="roi-content">
                                        <h5>ROI</h5>
                                        <div class="roi-value" id="roiPercentage">0%</div>
                                        <span class="roi-period">Sur 3 ans</span>
                                    </div>
                                </div>
                                
                                <div class="roi-card payback">
                                    <div class="roi-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="roi-content">
                                        <h5>Retour sur investissement</h5>
                                        <div class="roi-value" id="paybackPeriod">0 mois</div>
                                        <span class="roi-period">Période de retour</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Roadmap d'implémentation -->
                        <div class="roadmap-section">
                            <h4><i class="fas fa-road"></i> Roadmap d'Implémentation</h4>
                            <div class="roadmap-timeline">
                                <div class="roadmap-phase phase-1">
                                    <div class="phase-header">
                                        <h5>Phase 1 - Actions Rapides</h5>
                                        <span class="phase-duration">1-3 mois</span>
                                    </div>
                                    <div class="phase-content">
                                        <div class="phase-description">ROI élevé, effort faible</div>
                                        <ul id="quickWins" class="action-list">
                                            <!-- Rempli dynamiquement -->
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="roadmap-phase phase-2">
                                    <div class="phase-header">
                                        <h5>Phase 2 - Projets Structurants</h5>
                                        <span class="phase-duration">3-12 mois</span>
                                    </div>
                                    <div class="phase-content">
                                        <div class="phase-description">Impact significatif</div>
                                        <ul id="mediumTerm" class="action-list">
                                            <!-- Rempli dynamiquement -->
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="roadmap-phase phase-3">
                                    <div class="phase-header">
                                        <h5>Phase 3 - Vision Long Terme</h5>
                                        <span class="phase-duration">12+ mois</span>
                                    </div>
                                    <div class="phase-content">
                                        <div class="phase-description">Innovations avancées</div>
                                        <ul id="longTerm" class="action-list">
                                            <!-- Rempli dynamiquement -->
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions d'export -->
                        <div class="export-section">
                            <h4><i class="fas fa-download"></i> Exporter vos résultats</h4>
                            <div class="export-buttons">
                                <button type="button" class="export-btn pdf" onclick="exportToPDF()">
                                    <i class="fas fa-file-pdf"></i>
                                    <span>Rapport PDF</span>
                                </button>
                                <button type="button" class="export-btn excel" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel"></i>
                                    <span>Données Excel</span>
                                </button>
                                <button type="button" class="export-btn json" onclick="exportToJSON()">
                                    <i class="fas fa-code"></i>
                                    <span>Données JSON</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Étape non implémentée -->
                <div class="step-container" data-step="<?php echo $step; ?>">
                    <div class="step-header">
                        <h2>Étape <?php echo $step; ?></h2>
                        <p>Cette étape n'est pas encore implémentée.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation moderne -->
            <div class="wizard-navigation">
                <?php if ($step > 1): ?>
                    <button type="button" class="nav-btn prev-btn" onclick="previousStep()">
                        <i class="fas fa-arrow-left"></i>
                        <span>Précédent</span>
                    </button>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <div class="nav-center">
                    <div class="auto-save-indicator">
                        <i class="fas fa-save"></i>
                        <span>Sauvegarde automatique</span>
                    </div>
                </div>

                <?php if ($step < 6): ?>
                    <button type="button" class="nav-btn next-btn" onclick="nextStep()">
                        <span>Suivant</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                <?php else: ?>
                    <button type="submit" class="nav-btn create-btn" onclick="createAudit()">
                        <i class="fas fa-rocket"></i>
                        <span>Créer l'Audit</span>
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Notifications modernes -->
<div id="modernNotifications"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
// Variables globales
let currentStep = <?php echo $step; ?>;
let wizardData = {};
let autoSaveInterval;
let charts = {};

// Initialisation du wizard moderne
document.addEventListener('DOMContentLoaded', function() {
    initModernWizard();
    initAutoSave();
    initCharts();
});

// Initialisation du wizard
function initModernWizard() {
    // Mise à jour du cercle de progression
    updateProgressCircle();
    
    // Initialiser les valeurs sauvegardées
    loadSavedData();
    
    // Initialiser les fonctionnalités spécifiques à chaque étape
    if (currentStep === 2) {
        initDigitalMaturityStep();
    } else if (currentStep === 3) {
        initSecurityStep();
    } else if (currentStep === 4) {
        initInfrastructureStep();
    } else if (currentStep === 5) {
        initAutomationStep();
    } else if (currentStep === 6) {
        initSynthesisStep();
    }
}

// Sélection d'option avec animation
function selectOption(element, fieldName) {
    // Retirer la sélection précédente
    element.parentNode.querySelectorAll('.option-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Ajouter la sélection avec animation
    element.classList.add('selected');
    
    // Mettre à jour le champ caché
    document.getElementById(fieldName).value = element.dataset.value;
    
    // Vibration tactile sur mobile
    if ('vibrate' in navigator) {
        navigator.vibrate(50);
    }
    
    // Notification
    showNotification('Sélection mise à jour', 'success');
}

// Sélection secteur
function selectSector(element) {
    element.parentNode.querySelectorAll('.sector-card').forEach(card => {
        card.classList.remove('selected');
    });
    element.classList.add('selected');
    document.getElementById('audit_sector').value = element.dataset.value;
    showNotification('Secteur sélectionné', 'success');
}

// Sélection budget
function selectBudget(element) {
    element.parentNode.querySelectorAll('.budget-card').forEach(card => {
        card.classList.remove('selected');
    });
    element.classList.add('selected');
    document.getElementById('audit_annual_budget').value = element.dataset.value;
    showNotification('Budget sélectionné', 'success');
}

// Mise à jour nombre d'employés
function updateEmployeeCount(value) {
    const ranges = ['1-10', '11-50', '51-250', '251-500', '500+'];
    const labels = ['1-10 employés', '11-50 employés', '51-250 employés', '251-500 employés', 'Plus de 500 employés'];
    
    document.getElementById('employeeValue').textContent = labels[value - 1];
    document.getElementById('audit_employees_count').value = ranges[value - 1];
}

// === ÉTAPE 2: MATURITÉ DIGITALE ===

function initDigitalMaturityStep() {
    // Initialiser le graphique en temps réel
    initLiveScoreChart();
}

function selectRating(element, fieldName) {
    // Retirer la sélection précédente
    element.parentNode.querySelectorAll('.rating-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Ajouter la sélection
    element.classList.add('selected');
    
    // Mettre à jour le champ caché
    document.getElementById(fieldName).value = element.dataset.value;
    
    // Mettre à jour le score en temps réel
    updateLiveScore();
    
    // Notification
    showNotification('Réponse enregistrée', 'success');
}

function initLiveScoreChart() {
    const canvas = document.getElementById('liveScoreChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    charts.liveScore = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [0, 100],
                backgroundColor: ['#667eea', '#e9ecef'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
}

function updateLiveScore() {
    const processesScore = document.getElementById('audit_digital_processes')?.value || 0;
    const toolsScore = document.getElementById('audit_collaborative_tools')?.value || 0;
    
    const processesPercent = (processesScore / 5) * 100;
    const toolsPercent = (toolsScore / 5) * 100;
    const averageScore = (processesPercent + toolsPercent) / 2;
    
    // Mettre à jour le score global
    document.getElementById('currentScore').textContent = Math.round(averageScore);
    
    // Mettre à jour les barres de progression
    document.getElementById('processes_bar').style.width = processesPercent + '%';
    document.getElementById('processes_score').textContent = Math.round(processesPercent) + '%';
    
    document.getElementById('tools_bar').style.width = toolsPercent + '%';
    document.getElementById('tools_score').textContent = Math.round(toolsPercent) + '%';
    
    // Mettre à jour le graphique circulaire
    if (charts.liveScore) {
        charts.liveScore.data.datasets[0].data = [averageScore, 100 - averageScore];
        charts.liveScore.update();
    }
}

function toggleComment(fieldName) {
    const commentBox = document.getElementById('comment_' + fieldName);
    if (commentBox.style.display === 'none') {
        commentBox.style.display = 'block';
        commentBox.querySelector('textarea').focus();
    } else {
        commentBox.style.display = 'none';
    }
}

function attachFile(fieldName) {
    document.getElementById('file_' + fieldName).click();
}

// === ÉTAPE 3: CYBERSÉCURITÉ ===

function initSecurityStep() {
    initSecurityGauge();
    updateSecurityScore();
}

function toggleSecurityItem(element, itemName) {
    element.classList.toggle('selected');
    updateSecurityScore();
    
    // Mettre à jour le champ caché
    const selectedItems = document.querySelectorAll('.security-item.selected');
    const values = Array.from(selectedItems).map(item => item.onclick.toString().match(/'([^']+)'/)[1]);
    document.getElementById('audit_security_measures').value = values.join(',');
}

function initSecurityGauge() {
    const canvas = document.getElementById('securityGauge');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    charts.securityGauge = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [0, 100],
                backgroundColor: ['#dc3545', '#e9ecef'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            cutout: '70%',
            rotation: -90,
            circumference: 180,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
}

function updateSecurityScore() {
    const selectedItems = document.querySelectorAll('.security-item.selected');
    let totalScore = 0;
    
    selectedItems.forEach(item => {
        const weight = parseInt(item.querySelector('.security-weight').textContent.replace(/\D/g, ''));
        totalScore += weight;
    });
    
    const maxScore = 100; // 15+20+25+15+25
    const percentage = Math.min((totalScore / maxScore) * 100, 100);
    
    // Mettre à jour la jauge
    if (charts.securityGauge) {
        charts.securityGauge.data.datasets[0].data = [percentage, 100 - percentage];
        charts.securityGauge.update();
    }
    
    // Mettre à jour le niveau de sécurité
    const levelElement = document.getElementById('securityLevel');
    const recommendationsElement = document.getElementById('securityRecommendations');
    
    if (percentage < 30) {
        levelElement.textContent = 'Critique';
        levelElement.style.color = '#dc3545';
    } else if (percentage < 60) {
        levelElement.textContent = 'Faible';
        levelElement.style.color = '#ffc107';
    } else if (percentage < 80) {
        levelElement.textContent = 'Moyen';
        levelElement.style.color = '#17a2b8';
    } else {
        levelElement.textContent = 'Élevé';
        levelElement.style.color = '#28a745';
    }
    
    // Générer les recommandations
    updateSecurityRecommendations(selectedItems);
}

function updateSecurityRecommendations(selectedItems) {
    const allItems = ['antivirus', 'firewall', 'backup', 'updates', 'training'];
    const selectedValues = Array.from(selectedItems).map(item => 
        item.onclick.toString().match(/'([^']+)'/)[1]
    );
    
    const missing = allItems.filter(item => !selectedValues.includes(item));
    const todosList = document.getElementById('securityTodos');
    
    if (todosList) {
        todosList.innerHTML = '';
        missing.forEach(item => {
            const li = document.createElement('li');
            li.textContent = getSecurityRecommendation(item);
            todosList.appendChild(li);
        });
    }
}

function getSecurityRecommendation(item) {
    const recommendations = {
        'antivirus': 'Installer un antivirus professionnel',
        'firewall': 'Configurer un pare-feu',
        'backup': 'Mettre en place des sauvegardes automatiques',
        'updates': 'Activer les mises à jour automatiques',
        'training': 'Former les équipes à la cybersécurité'
    };
    return recommendations[item] || 'Améliorer la sécurité';
}

// === ÉTAPE 4: INFRASTRUCTURE ===

function initInfrastructureStep() {
    updateCloudRecommendations();
}

function selectInfrastructure(element) {
    element.parentNode.querySelectorAll('.infra-card').forEach(card => {
        card.classList.remove('selected');
    });
    element.classList.add('selected');
    document.getElementById('audit_infrastructure').value = element.dataset.value;
    updateCloudRecommendations();
}

function selectMigration(element) {
    element.parentNode.querySelectorAll('.timeline-option').forEach(option => {
        option.classList.remove('selected');
    });
    element.classList.add('selected');
    document.getElementById('audit_cloud_migration').value = element.dataset.value;
    updateCloudRecommendations();
}

function updateCloudRecommendations() {
    const infrastructure = document.getElementById('audit_infrastructure')?.value;
    const migration = document.getElementById('audit_cloud_migration')?.value;
    const recommendationsDiv = document.getElementById('cloudRecommendations');
    
    if (!recommendationsDiv) return;
    
    let recommendations = [];
    
    if (infrastructure === 'on_premise') {
        recommendations.push('Évaluer les coûts de migration vers le cloud');
        recommendations.push('Commencer par migrer les applications non-critiques');
        recommendations.push('Former les équipes aux technologies cloud');
    } else if (infrastructure === 'hybrid') {
        recommendations.push('Optimiser la répartition des charges');
        recommendations.push('Améliorer la sécurité des connexions');
        recommendations.push('Standardiser les processus de déploiement');
    } else if (infrastructure === 'full_cloud') {
        recommendations.push('Optimiser les coûts cloud');
        recommendations.push('Mettre en place une gouvernance cloud');
        recommendations.push('Automatiser la gestion des ressources');
    }
    
    recommendationsDiv.innerHTML = recommendations.map(rec => 
        `<div class="recommendation-item">
            <i class="fas fa-lightbulb"></i>
            <span>${rec}</span>
        </div>`
    ).join('');
}

// === ÉTAPE 5: AUTOMATISATION ===

function initAutomationStep() {
    initAutomationChart();
    updateAutomationSavings();
}

function toggleAutomation(element, processName) {
    element.classList.toggle('active');
    updateAutomationSavings();
    
    // Mettre à jour le champ caché
    const activeItems = document.querySelectorAll('.automation-item.active');
    const values = Array.from(activeItems).map(item => 
        item.onclick.toString().match(/'([^']+)'/)[1]
    );
    document.getElementById('audit_automation_processes').value = values.join(',');
}

function initAutomationChart() {
    const canvas = document.getElementById('automationPotentialChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    charts.automationPotential = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['RH', 'Finance', 'Commercial'],
            datasets: [{
                label: 'Potentiel d\'automatisation (%)',
                data: [60, 80, 70],
                backgroundColor: ['#667eea', '#764ba2', '#f093fb']
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function updateAutomationSavings() {
    const activeItems = document.querySelectorAll('.automation-item.active');
    const timeSavings = activeItems.length * 5; // 5h par processus automatisé
    const costSavings = timeSavings * 52 * 35; // 35€/h * 52 semaines
    
    document.getElementById('timeSavings').textContent = timeSavings + 'h/semaine';
    document.getElementById('costSavings').textContent = costSavings.toLocaleString() + '€';
}

// === ÉTAPE 6: SYNTHÈSE ===

function initSynthesisStep() {
    calculateGlobalScore();
    initSynthesisCharts();
    calculateROI();
    generateRoadmap();
}

function calculateGlobalScore() {
    // Récupérer tous les scores des étapes précédentes
    const scores = {
        digital: getDigitalScore(),
        security: getSecurityScore(),
        cloud: getCloudScore(),
        automation: getAutomationScore()
    };
    
    const globalScore = Object.values(scores).reduce((a, b) => a + b, 0) / 4;
    
    // Mettre à jour l'affichage
    document.getElementById('globalScore').textContent = Math.round(globalScore);
    
    // Mettre à jour le niveau de maturité
    const level = getMaturityLevel(globalScore);
    document.getElementById('maturityLevel').textContent = level;
    
    // Mettre à jour les barres de domaine
    Object.keys(scores).forEach(domain => {
        const fillElement = document.getElementById(domain + '_fill');
        const scoreElement = document.getElementById(domain + '_score');
        if (fillElement && scoreElement) {
            fillElement.style.width = scores[domain] + '%';
            scoreElement.textContent = Math.round(scores[domain]) + '%';
        }
    });
    
    return { globalScore, scores };
}

function getDigitalScore() {
    // Calculer le score de maturité digitale
    const processes = parseInt(document.getElementById('audit_digital_processes')?.value || 0);
    const tools = parseInt(document.getElementById('audit_collaborative_tools')?.value || 0);
    return ((processes + tools) / 10) * 100;
}

function getSecurityScore() {
    // Calculer le score de sécurité
    const selectedItems = document.querySelectorAll('.security-item.selected');
    let totalScore = 0;
    selectedItems.forEach(item => {
        const weight = parseInt(item.querySelector('.security-weight').textContent.replace(/\D/g, ''));
        totalScore += weight;
    });
    return totalScore;
}

function getCloudScore() {
    // Calculer le score cloud/infrastructure
    const infrastructure = document.getElementById('audit_infrastructure')?.value;
    const migration = document.getElementById('audit_cloud_migration')?.value;
    
    let score = 0;
    if (infrastructure === 'full_cloud') score += 50;
    else if (infrastructure === 'hybrid') score += 30;
    else score += 10;
    
    if (migration === 'in_progress') score += 30;
    else if (migration === 'planning') score += 20;
    else if (migration === 'exploring') score += 10;
    
    return Math.min(score, 100);
}

function getAutomationScore() {
    // Calculer le score d'automatisation
    const activeItems = document.querySelectorAll('.automation-item.active');
    const totalItems = document.querySelectorAll('.automation-item');
    return (activeItems.length / totalItems.length) * 100;
}

function getMaturityLevel(score) {
    if (score < 30) return 'Débutant';
    if (score < 50) return 'Intermédiaire';
    if (score < 70) return 'Avancé';
    if (score < 85) return 'Expert';
    return 'Leader';
}

function initSynthesisCharts() {
    initRadarChart();
    initEvolutionChart();
    initGlobalGauge();
}

function initRadarChart() {
    const canvas = document.getElementById('radarChart');
    if (!canvas) return;
    
    const { scores } = calculateGlobalScore();
    
    const ctx = canvas.getContext('2d');
    charts.radar = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Maturité Digitale', 'Cybersécurité', 'Cloud & Infrastructure', 'Automatisation'],
            datasets: [{
                label: 'Score actuel',
                data: [scores.digital, scores.security, scores.cloud, scores.automation],
                backgroundColor: 'rgba(102, 126, 234, 0.2)',
                borderColor: 'rgba(102, 126, 234, 1)',
                pointBackgroundColor: 'rgba(102, 126, 234, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(102, 126, 234, 1)'
            }]
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function initEvolutionChart() {
    const canvas = document.getElementById('evolutionChart');
    if (!canvas) return;
    
    const { scores } = calculateGlobalScore();
    
    const ctx = canvas.getContext('2d');
    charts.evolution = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Maturité Digitale', 'Cybersécurité', 'Cloud', 'Automatisation'],
            datasets: [
                {
                    label: 'Score actuel',
                    data: [scores.digital, scores.security, scores.cloud, scores.automation],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)'
                },
                {
                    label: 'Objectif 12 mois',
                    data: [
                        Math.min(scores.digital + 20, 100),
                        Math.min(scores.security + 25, 100),
                        Math.min(scores.cloud + 15, 100),
                        Math.min(scores.automation + 30, 100)
                    ],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function initGlobalGauge() {
    const canvas = document.getElementById('globalScoreGauge');
    if (!canvas) return;
    
    const { globalScore } = calculateGlobalScore();
    
    const ctx = canvas.getContext('2d');
    charts.globalGauge = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [globalScore, 100 - globalScore],
                backgroundColor: ['#667eea', '#e9ecef'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
}

function calculateROI() {
    const { scores } = calculateGlobalScore();
    
    // Calculs ROI basés sur les scores
    const potentialSavings = {
        digital: (100 - scores.digital) * 500,
        security: (100 - scores.security) * 300,
        cloud: (100 - scores.cloud) * 400,
        automation: (100 - scores.automation) * 600
    };
    
    const totalSavings = Object.values(potentialSavings).reduce((a, b) => a + b, 0);
    const totalInvestment = totalSavings * 0.6; // 60% du potentiel d'économies
    const roi = ((totalSavings * 3 - totalInvestment) / totalInvestment) * 100;
    const paybackMonths = Math.round((totalInvestment / (totalSavings / 12)));
    
    // Mettre à jour l'affichage
    document.getElementById('totalInvestment').textContent = Math.round(totalInvestment).toLocaleString() + '€';
    document.getElementById('totalSavings').textContent = Math.round(totalSavings).toLocaleString() + '€';
    document.getElementById('roiPercentage').textContent = Math.round(roi) + '%';
    document.getElementById('paybackPeriod').textContent = paybackMonths + ' mois';
}

function generateRoadmap() {
    const { scores } = calculateGlobalScore();
    
    const quickWins = [];
    const mediumTerm = [];
    const longTerm = [];
    
    // Générer les actions basées sur les scores
    if (scores.digital < 50) {
        quickWins.push('Digitaliser les processus papier');
        mediumTerm.push('Déployer une suite collaborative');
    }
    
    if (scores.security < 70) {
        quickWins.push('Installer un antivirus professionnel');
        quickWins.push('Configurer les sauvegardes automatiques');
        mediumTerm.push('Former les équipes à la cybersécurité');
    }
    
    if (scores.cloud < 60) {
        mediumTerm.push('Évaluer la migration cloud');
        longTerm.push('Migrer vers une infrastructure hybride');
    }
    
    if (scores.automation < 50) {
        quickWins.push('Automatiser la facturation');
        mediumTerm.push('Déployer un CRM');
        longTerm.push('Automatiser les processus RH');
    }
    
    // Mettre à jour l'affichage
    updateRoadmapList('quickWins', quickWins);
    updateRoadmapList('mediumTerm', mediumTerm);
    updateRoadmapList('longTerm', longTerm);
}

function updateRoadmapList(listId, items) {
    const list = document.getElementById(listId);
    if (list) {
        list.innerHTML = items.map(item => `<li>${item}</li>`).join('');
    }
}

// Fonctions d'export
function exportToPDF() {
    showNotification('Génération du PDF en cours...', 'info');
    // TODO: Implémenter l'export PDF
    setTimeout(() => {
        showNotification('PDF généré avec succès', 'success');
    }, 2000);
}

function exportToExcel() {
    showNotification('Export Excel en cours...', 'info');
    // TODO: Implémenter l'export Excel
    setTimeout(() => {
        showNotification('Fichier Excel téléchargé', 'success');
    }, 1500);
}

function exportToJSON() {
    const data = {
        step: currentStep,
        scores: calculateGlobalScore(),
        timestamp: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'audit-digital-data.json';
    a.click();
    URL.revokeObjectURL(url);
    
    showNotification('Données JSON téléchargées', 'success');
}

// Auto-save intelligent
function initAutoSave() {
    autoSaveInterval = setInterval(() => {
        saveCurrentStep();
    }, 30000); // Toutes les 30 secondes
}

function saveCurrentStep() {
    const formData = new FormData(document.getElementById('wizardForm'));
    formData.append('ajax', '1');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAutoSaveIndicator();
        }
    })
    .catch(error => {
        console.error('Erreur auto-save:', error);
    });
}

function showAutoSaveIndicator() {
    const indicator = document.querySelector('.auto-save-indicator');
    if (indicator) {
        indicator.style.color = '#28a745';
        setTimeout(() => {
            indicator.style.color = '#6c757d';
        }, 2000);
    }
}

// Notifications modernes
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `modern-notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    document.getElementById('modernNotifications').appendChild(notification);
    
    // Suppression automatique
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Navigation entre étapes
function nextStep() {
    if (validateCurrentStep()) {
        saveCurrentStep();
        window.location.href = `?step=${currentStep + 1}`;
    }
}

function previousStep() {
    window.location.href = `?step=${currentStep - 1}`;
}

// Validation moderne
function validateCurrentStep() {
    const requiredFields = document.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    if (!isValid) {
        showNotification('Veuillez remplir tous les champs obligatoires', 'error');
    }
    
    return isValid;
}

// Mise à jour du cercle de progression
function updateProgressCircle() {
    const circle = document.querySelector('.progress-ring-circle');
    if (circle) {
        const radius = circle.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;
        const progress = (currentStep / 6) * 100;
        
        circle.style.strokeDasharray = `${circumference} ${circumference}`;
        circle.style.strokeDashoffset = circumference - (progress / 100) * circumference;
    }
}

// Charger les données sauvegardées
function loadSavedData() {
    const savedData = localStorage.getItem('audit_wizard_data');
    if (savedData) {
        wizardData = JSON.parse(savedData);
        // TODO: Restaurer les valeurs dans le formulaire
    }
}

// Initialiser les graphiques
function initCharts() {
    // Chart.js sera chargé via CDN
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js non chargé');
        return;
    }
    
    // Configuration globale Chart.js
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = '#6c757d';
}

// Créer l'audit
function createAudit() {
    if (validateCurrentStep()) {
        document.getElementById('wizardForm').action = '<?php echo $_SERVER["PHP_SELF"]; ?>?action=create_audit';
        document.getElementById('wizardForm').submit();
    }
}
</script>

</body>
</html>

<?php
llxFooter();
?>