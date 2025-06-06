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

        /* Graphiques */
        .live-chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 30px;
            box-shadow: var(--shadow-light);
        }

        .results-dashboard {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-top: 30px;
        }

        .score-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow-light);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-light);
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

            <?php else: ?>
                <!-- Autres étapes à implémenter -->
                <div class="step-container" data-step="<?php echo $step; ?>">
                    <div class="step-header">
                        <h2><i class="<?php echo $wizard_steps[$step]['icon']; ?>"></i> <?php echo $wizard_steps[$step]['title']; ?></h2>
                        <p><?php echo $wizard_steps[$step]['description']; ?></p>
                    </div>
                    
                    <div class="text-center">
                        <p>Cette étape sera implémentée avec les fonctionnalités avancées :</p>
                        <ul style="text-align: left; max-width: 500px; margin: 20px auto;">
                            <li>Questions avec système de notation moderne</li>
                            <li>Commentaires enrichis avec pièces jointes</li>
                            <li>Graphiques en temps réel</li>
                            <li>Calcul ROI automatique</li>
                            <li>Recommandations intelligentes</li>
                        </ul>
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

// Initialisation du wizard moderne
document.addEventListener('DOMContentLoaded', function() {
    initModernWizard();
    initAutoSave();
});

// Initialisation du wizard
function initModernWizard() {
    // Mise à jour du cercle de progression
    updateProgressCircle();
    
    // Initialiser les valeurs sauvegardées
    loadSavedData();
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
    indicator.style.color = '#28a745';
    setTimeout(() => {
        indicator.style.color = '#6c757d';
    }, 2000);
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
    const radius = circle.r.baseVal.value;
    const circumference = radius * 2 * Math.PI;
    const progress = (currentStep / 6) * 100;
    
    circle.style.strokeDasharray = `${circumference} ${circumference}`;
    circle.style.strokeDashoffset = circumference - (progress / 100) * circumference;
}

// Charger les données sauvegardées
function loadSavedData() {
    // Récupérer les données depuis le localStorage ou la session
    const savedData = localStorage.getItem('audit_wizard_data');
    if (savedData) {
        wizardData = JSON.parse(savedData);
        // Restaurer les valeurs dans le formulaire
        // TODO: Implémenter la restauration des valeurs
    }
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