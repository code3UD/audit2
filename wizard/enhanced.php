<?php
/**
 * Wizard Amélioré AuditDigital - Version Professionnelle
 * Avec plus de finesse, commentaires et intégration Dolibarr complète
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
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

dol_include_once('/auditdigital/class/audit.class.php');
dol_include_once('/auditdigital/class/questionnaire.class.php');

$langs->loadLangs(array("auditdigital@auditdigital", "other"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int');
if (empty($step)) $step = 1;
$socid = GETPOST('socid', 'int');

// Security check
if (!$user->rights->auditdigital->audit->write) {
    accessforbidden();
}

// Charger les informations de la société si sélectionnée
$societe = null;
if ($socid > 0) {
    $societe = new Societe($db);
    $societe->fetch($socid);
}

/*
 * Actions
 */

if ($action == 'save_step') {
    // Sauvegarde automatique des données de l'étape
    $step_data = array();
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'audit_') === 0 || strpos($key, 'comment_') === 0) {
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
        
        // Calculer les scores avec la nouvelle échelle 1-10
        $digital_level = $wizard_data['step_2']['audit_digital_level'] ?? 0;
        $web_presence = $wizard_data['step_2']['audit_web_presence'] ?? 0;
        $digital_tools = $wizard_data['step_2']['audit_digital_tools'] ?? 0;
        $maturity_score = ($digital_level + $web_presence + $digital_tools) * 10 / 3; // Max 10
        
        $security_level = $wizard_data['step_3']['audit_security_level'] ?? 0;
        $rgpd_compliance = $wizard_data['step_3']['audit_rgpd_compliance'] ?? 0;
        $backup_strategy = $wizard_data['step_3']['audit_backup_strategy'] ?? 0;
        $security_score = ($security_level + $rgpd_compliance + $backup_strategy) * 10 / 3; // Max 10
        
        $cloud_adoption = $wizard_data['step_4']['audit_cloud_adoption'] ?? 0;
        $mobility = $wizard_data['step_4']['audit_mobility'] ?? 0;
        $infrastructure = $wizard_data['step_4']['audit_infrastructure'] ?? 0;
        $cloud_score = ($cloud_adoption + $mobility + $infrastructure) * 10 / 3; // Max 10
        
        $automation_level = $wizard_data['step_5']['audit_automation_level'] ?? 0;
        $collaboration_tools = $wizard_data['step_5']['audit_collaboration_tools'] ?? 0;
        $data_analysis = $wizard_data['step_5']['audit_data_analysis'] ?? 0;
        $automation_score = ($automation_level + $collaboration_tools + $data_analysis) * 10 / 3; // Max 10
        
        $total_score = ($maturity_score + $security_score + $cloud_score + $automation_score) * 2.5; // Max 100
        
        // Set properties from wizard data
        $audit->ref = 'AUDIT-'.date('YmdHis');
        $audit->label = 'Audit Digital - '.$societe->name.' - '.date('d/m/Y');
        $audit->fk_soc = $wizard_data['step_1']['audit_socid'] ?? 0;
        $audit->structure_type = $wizard_data['step_1']['audit_structure_type'] ?? '';
        $audit->audit_type = 'digital_maturity';
        $audit->status = 0; // Draft
        
        // Sauvegarder les scores calculés
        $audit->score_global = round($total_score);
        $audit->score_maturite = round($maturity_score * 10);
        $audit->score_cybersecurite = round($security_score * 10);
        $audit->score_cloud = round($cloud_score * 10);
        $audit->score_automatisation = round($automation_score * 10);
        
        // Sauvegarder les réponses et commentaires en JSON
        $audit->json_responses = json_encode($wizard_data);
        
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
llxHeader("", "Audit Digital Professionnel");

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Digital Professionnel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS Professionnel Amélioré */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #8e44ad;
            --gradient-primary: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --gradient-success: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            --shadow-light: 0 2px 15px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.12);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: #2c3e50;
        }

        .enhanced-wizard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header professionnel */
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
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
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

        .company-info {
            text-align: right;
        }

        .company-info h3 {
            font-size: 1.3rem;
            margin-bottom: 5px;
        }

        .company-info p {
            opacity: 0.8;
        }

        /* Stepper professionnel */
        .enhanced-stepper {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            flex-wrap: wrap;
        }

        .stepper-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 10px 20px;
            position: relative;
        }

        .stepper-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -30px;
            width: 40px;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }

        .stepper-item.completed:not(:last-child)::after {
            background: var(--success-color);
        }

        .stepper-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 10px;
            transition: var(--transition);
            position: relative;
            z-index: 1;
        }

        .stepper-item.active .stepper-number {
            background: var(--secondary-color);
            color: white;
            transform: scale(1.1);
        }

        .stepper-item.completed .stepper-number {
            background: var(--success-color);
            color: white;
        }

        .stepper-label {
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            color: #6c757d;
        }

        .stepper-item.active .stepper-label {
            color: var(--secondary-color);
        }

        .stepper-item.completed .stepper-label {
            color: var(--success-color);
        }

        /* Contenu amélioré */
        .wizard-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 50px;
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
            margin-bottom: 50px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }

        .step-header h2 {
            font-size: 2.2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .step-header p {
            font-size: 1.2rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Questions avec échelle 1-10 */
        .question-section {
            margin-bottom: 50px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: var(--border-radius);
            border-left: 5px solid var(--secondary-color);
        }

        .question-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .question-icon {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-right: 15px;
        }

        .question-title {
            flex: 1;
        }

        .question-title h3 {
            font-size: 1.4rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .question-title p {
            color: #6c757d;
            font-size: 1rem;
        }

        /* Échelle de notation 1-10 */
        .rating-scale {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .scale-labels {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 15px;
        }

        .scale-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
            flex: 1;
        }

        .scale-options {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .scale-option {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid #e9ecef;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .scale-option:hover {
            border-color: var(--secondary-color);
            transform: scale(1.1);
        }

        .scale-option.selected {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
            transform: scale(1.2);
        }

        .scale-option.selected::after {
            content: '✓';
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: var(--success-color);
            border-radius: 50%;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Zone de commentaires */
        .comment-section {
            margin-top: 25px;
        }

        .comment-section label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .comment-textarea {
            width: 100%;
            min-height: 100px;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            transition: var(--transition);
        }

        .comment-textarea:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        /* Navigation professionnelle */
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
            min-width: 150px;
            justify-content: center;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
        }

        .nav-btn.prev-btn {
            background: #6c757d;
        }

        .nav-btn.create-btn {
            background: var(--gradient-success);
        }

        .nav-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .company-info {
                text-align: center;
            }

            .wizard-content {
                padding: 30px 20px;
            }

            .scale-options {
                flex-wrap: wrap;
                gap: 10px;
            }

            .scale-option {
                width: 40px;
                height: 40px;
            }
        }

        /* Indicateur de progression */
        .progress-indicator {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient-primary);
            transition: width 0.8s ease-in-out;
            border-radius: 4px;
        }

        .progress-text {
            text-align: center;
            margin-top: 10px;
            font-weight: 600;
            color: var(--primary-color);
        }
    </style>
</head>
<body>

<div class="enhanced-wizard-container">
    <!-- Header professionnel -->
    <div class="wizard-header">
        <div class="header-content">
            <div class="wizard-title">
                <h1><i class="fas fa-chart-line"></i> Audit Digital Professionnel</h1>
                <p>Évaluation complète de la maturité digitale - Étape <?php echo $step; ?>/6</p>
            </div>
            <?php if ($societe): ?>
            <div class="company-info">
                <h3><?php echo $societe->name; ?></h3>
                <p><?php echo $societe->town; ?> - <?php echo $societe->country; ?></p>
                <p><i class="fas fa-users"></i> <?php echo $societe->effectif; ?> employés</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Indicateur de progression -->
    <div class="progress-indicator">
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo ($step / 6) * 100; ?>%;"></div>
        </div>
        <div class="progress-text">
            Progression : <?php echo round(($step / 6) * 100); ?>% complété
        </div>
    </div>

    <!-- Stepper professionnel -->
    <div class="enhanced-stepper">
        <?php 
        $step_labels = [
            1 => 'Informations',
            2 => 'Maturité Digitale', 
            3 => 'Cybersécurité',
            4 => 'Infrastructure',
            5 => 'Automatisation',
            6 => 'Synthèse'
        ];
        
        for ($i = 1; $i <= 6; $i++): 
        ?>
            <div class="stepper-item <?php echo ($i == $step) ? 'active' : ''; ?> <?php echo ($i < $step) ? 'completed' : ''; ?>">
                <div class="stepper-number">
                    <?php echo ($i < $step) ? '<i class="fas fa-check"></i>' : $i; ?>
                </div>
                <div class="stepper-label"><?php echo $step_labels[$i]; ?></div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Contenu de l'étape -->
    <div class="wizard-content">
        <form id="wizardForm" method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="save_step">
            <input type="hidden" name="step" value="<?php echo $step; ?>">
            <input type="hidden" name="socid" value="<?php echo $socid; ?>">

            <?php if ($step == 1): ?>
                <!-- Étape 1: Informations Générales Améliorées -->
                <div class="step-container">
                    <div class="step-header">
                        <h2><i class="fas fa-building"></i> Informations de l'Organisation</h2>
                        <p>Définissons le contexte de votre audit digital pour une évaluation personnalisée</p>
                    </div>

                    <div class="question-section">
                        <div class="question-header">
                            <div class="question-icon">
                                <i class="fas fa-industry"></i>
                            </div>
                            <div class="question-title">
                                <h3>Type de structure <span style="color: var(--danger-color);">*</span></h3>
                                <p>Sélectionnez le type d'organisation qui correspond le mieux à votre structure</p>
                            </div>
                        </div>

                        <div class="rating-scale">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; width: 100%;">
                                <div class="scale-option structure-option" data-value="tpe" onclick="selectStructure(this, 'audit_structure_type')">
                                    <div style="text-align: center;">
                                        <i class="fas fa-store" style="font-size: 1.5rem; margin-bottom: 5px; display: block;"></i>
                                        <strong>TPE</strong><br>
                                        <small>1-9 employés</small>
                                    </div>
                                </div>
                                <div class="scale-option structure-option" data-value="pme" onclick="selectStructure(this, 'audit_structure_type')">
                                    <div style="text-align: center;">
                                        <i class="fas fa-building" style="font-size: 1.5rem; margin-bottom: 5px; display: block;"></i>
                                        <strong>PME</strong><br>
                                        <small>10-249 employés</small>
                                    </div>
                                </div>
                                <div class="scale-option structure-option" data-value="eti" onclick="selectStructure(this, 'audit_structure_type')">
                                    <div style="text-align: center;">
                                        <i class="fas fa-city" style="font-size: 1.5rem; margin-bottom: 5px; display: block;"></i>
                                        <strong>ETI</strong><br>
                                        <small>250-4999 employés</small>
                                    </div>
                                </div>
                                <div class="scale-option structure-option" data-value="grande_entreprise" onclick="selectStructure(this, 'audit_structure_type')">
                                    <div style="text-align: center;">
                                        <i class="fas fa-globe" style="font-size: 1.5rem; margin-bottom: 5px; display: block;"></i>
                                        <strong>Grande Entreprise</strong><br>
                                        <small>5000+ employés</small>
                                    </div>
                                </div>
                                <div class="scale-option structure-option" data-value="collectivite" onclick="selectStructure(this, 'audit_structure_type')">
                                    <div style="text-align: center;">
                                        <i class="fas fa-landmark" style="font-size: 1.5rem; margin-bottom: 5px; display: block;"></i>
                                        <strong>Collectivité</strong><br>
                                        <small>Secteur public</small>
                                    </div>
                                </div>
                                <div class="scale-option structure-option" data-value="association" onclick="selectStructure(this, 'audit_structure_type')">
                                    <div style="text-align: center;">
                                        <i class="fas fa-hands-helping" style="font-size: 1.5rem; margin-bottom: 5px; display: block;"></i>
                                        <strong>Association</strong><br>
                                        <small>But non lucratif</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="audit_structure_type" id="audit_structure_type" required>
                    </div>

                    <div class="question-section">
                        <div class="question-header">
                            <div class="question-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="question-title">
                                <h3>Société <span style="color: var(--danger-color);">*</span></h3>
                                <p>Sélectionnez la société pour laquelle vous réalisez cet audit</p>
                            </div>
                        </div>

                        <div style="background: white; padding: 20px; border-radius: var(--border-radius);">
                            <?php echo $formcompany->select_company(GETPOST('socid', 'int'), 'audit_socid', '', 'Sélectionnez une société...', 1, 0, null, 0, 'form-control'); ?>
                        </div>
                    </div>

                    <!-- Champ AuditType obligatoire caché -->
                    <input type="hidden" name="audit_type" value="digital_maturity">
                </div>

            <?php elseif ($step == 2): ?>
                <!-- Étape 2: Maturité Digitale Détaillée -->
                <div class="step-container">
                    <div class="step-header">
                        <h2><i class="fas fa-digital-tachograph"></i> Maturité Digitale</h2>
                        <p>Évaluons votre niveau de transformation digitale sur plusieurs dimensions</p>
                    </div>

                    <!-- Question 1: Digitalisation des processus -->
                    <div class="question-section">
                        <div class="question-header">
                            <div class="question-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div class="question-title">
                                <h3>Niveau de digitalisation des processus métier</h3>
                                <p>Dans quelle mesure vos processus métier sont-ils digitalisés ?</p>
                            </div>
                        </div>

                        <div class="rating-scale">
                            <div class="scale-labels">
                                <div class="scale-label">Très faible<br><small>Processus manuels</small></div>
                                <div class="scale-label">Faible<br><small>Quelques outils</small></div>
                                <div class="scale-label">Moyen<br><small>Partiellement digitalisé</small></div>
                                <div class="scale-label">Bon<br><small>Largement digitalisé</small></div>
                                <div class="scale-label">Excellent<br><small>Entièrement automatisé</small></div>
                            </div>
                            <div class="scale-options">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_digital_level')">
                                        <?php echo $i; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <input type="hidden" name="audit_digital_level" id="audit_digital_level" required>

                        <div class="comment-section">
                            <label for="comment_digital_level">Commentaires (optionnel) :</label>
                            <textarea name="comment_digital_level" id="comment_digital_level" class="comment-textarea" 
                                placeholder="Décrivez vos processus digitalisés, les outils utilisés, les défis rencontrés..."></textarea>
                        </div>
                    </div>

                    <!-- Question 2: Présence web -->
                    <div class="question-section">
                        <div class="question-header">
                            <div class="question-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="question-title">
                                <h3>Maturité de votre présence web</h3>
                                <p>Comment évaluez-vous votre présence et vos services en ligne ?</p>
                            </div>
                        </div>

                        <div class="rating-scale">
                            <div class="scale-labels">
                                <div class="scale-label">Très faible<br><small>Pas de site web</small></div>
                                <div class="scale-label">Faible<br><small>Site vitrine basique</small></div>
                                <div class="scale-label">Moyen<br><small>Site interactif</small></div>
                                <div class="scale-label">Bon<br><small>E-commerce/services</small></div>
                                <div class="scale-label">Excellent<br><small>Écosystème digital</small></div>
                            </div>
                            <div class="scale-options">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_web_presence')">
                                        <?php echo $i; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <input type="hidden" name="audit_web_presence" id="audit_web_presence" required>

                        <div class="comment-section">
                            <label for="comment_web_presence">Commentaires (optionnel) :</label>
                            <textarea name="comment_web_presence" id="comment_web_presence" class="comment-textarea" 
                                placeholder="Décrivez votre site web, vos services en ligne, votre stratégie digitale..."></textarea>
                        </div>
                    </div>

                    <!-- Question 3: Outils digitaux -->
                    <div class="question-section">
                        <div class="question-header">
                            <div class="question-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="question-title">
                                <h3>Adoption d'outils digitaux métier</h3>
                                <p>Dans quelle mesure utilisez-vous des outils digitaux spécialisés ?</p>
                            </div>
                        </div>

                        <div class="rating-scale">
                            <div class="scale-labels">
                                <div class="scale-label">Très faible<br><small>Outils bureautiques</small></div>
                                <div class="scale-label">Faible<br><small>Quelques logiciels</small></div>
                                <div class="scale-label">Moyen<br><small>Suite métier</small></div>
                                <div class="scale-label">Bon<br><small>Outils intégrés</small></div>
                                <div class="scale-label">Excellent<br><small>Écosystème complet</small></div>
                            </div>
                            <div class="scale-options">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_digital_tools')">
                                        <?php echo $i; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <input type="hidden" name="audit_digital_tools" id="audit_digital_tools" required>

                        <div class="comment-section">
                            <label for="comment_digital_tools">Commentaires (optionnel) :</label>
                            <textarea name="comment_digital_tools" id="comment_digital_tools" class="comment-textarea" 
                                placeholder="Listez vos outils métier (CRM, ERP, etc.), leur intégration, leur utilisation..."></textarea>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

            <!-- Navigation professionnelle -->
            <div class="wizard-navigation">
                <?php if ($step > 1): ?>
                    <button type="button" class="nav-btn prev-btn" onclick="previousStep()">
                        <i class="fas fa-arrow-left"></i>
                        <span>Précédent</span>
                    </button>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <?php if ($step < 6): ?>
                    <button type="button" class="nav-btn next-btn" onclick="nextStep()" id="nextBtn">
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

<script>
// JavaScript professionnel amélioré
let currentStep = <?php echo $step; ?>;

// Fonction de sélection pour les structures
function selectStructure(element, fieldName) {
    // Retirer la sélection précédente
    const options = document.querySelectorAll('.structure-option');
    options.forEach(option => {
        option.classList.remove('selected');
    });
    
    // Ajouter la sélection
    element.classList.add('selected');
    
    // Mettre à jour le champ caché
    const hiddenField = document.getElementById(fieldName);
    if (hiddenField) {
        hiddenField.value = element.dataset.value;
    }
    
    validateStep();
    showNotification('Structure sélectionnée', 'success');
}

// Fonction de sélection pour les échelles de notation
function selectRating(element, fieldName) {
    // Retirer la sélection précédente
    const parent = element.parentNode;
    const options = parent.querySelectorAll('.scale-option');
    options.forEach(option => {
        option.classList.remove('selected');
    });
    
    // Ajouter la sélection avec animation
    element.classList.add('selected');
    
    // Mettre à jour le champ caché
    const hiddenField = document.getElementById(fieldName);
    if (hiddenField) {
        hiddenField.value = element.dataset.value;
    }
    
    // Vibration tactile sur mobile
    if ('vibrate' in navigator) {
        navigator.vibrate(50);
    }
    
    validateStep();
    showNotification('Note attribuée : ' + element.dataset.value + '/10', 'success');
}

// Navigation entre étapes
function nextStep() {
    if (validateStep()) {
        // Sauvegarder automatiquement
        saveCurrentStep();
        window.location.href = '?step=' + (currentStep + 1) + '&socid=<?php echo $socid; ?>';
    }
}

function previousStep() {
    saveCurrentStep();
    window.location.href = '?step=' + (currentStep - 1) + '&socid=<?php echo $socid; ?>';
}

// Validation améliorée
function validateStep() {
    const requiredFields = document.querySelectorAll('[required]');
    let isValid = true;
    let missingFields = [];
    
    requiredFields.forEach(field => {
        if (!field.value) {
            field.style.border = '2px solid var(--danger-color)';
            isValid = false;
            missingFields.push(field.name);
        } else {
            field.style.border = '';
        }
    });
    
    // Mettre à jour le bouton suivant
    const nextBtn = document.getElementById('nextBtn');
    if (nextBtn) {
        nextBtn.disabled = !isValid;
        nextBtn.style.opacity = isValid ? '1' : '0.6';
    }
    
    if (!isValid && missingFields.length > 0) {
        showNotification('Veuillez compléter tous les champs obligatoires', 'error');
    }
    
    return isValid;
}

// Sauvegarde automatique
function saveCurrentStep() {
    const formData = new FormData(document.getElementById('wizardForm'));
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('audit_') || key.startsWith('comment_')) {
            data[key] = value;
        }
    }
    
    // Sauvegarder en localStorage pour persistance
    localStorage.setItem('audit_wizard_step_' + currentStep, JSON.stringify(data));
    
    // Envoyer au serveur via AJAX
    fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
        method: 'POST',
        body: new URLSearchParams({
            ...data,
            action: 'save_step',
            step: currentStep,
            ajax: '1',
            token: '<?php echo newToken(); ?>'
        })
    });
}

// Créer l'audit
function createAudit() {
    if (validateStep()) {
        document.getElementById('wizardForm').action = '<?php echo $_SERVER["PHP_SELF"]; ?>?action=create_audit';
        document.getElementById('wizardForm').submit();
    }
}

// Notifications améliorées
function showNotification(message, type = 'success') {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? 'var(--success-color)' : 'var(--danger-color)'};
        color: white;
        padding: 15px 20px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-medium);
        z-index: 1000;
        animation: slideInRight 0.3s ease-out;
        max-width: 300px;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> 
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Suppression automatique
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Wizard professionnel initialisé - Étape', currentStep);
    
    // Restaurer les données sauvegardées
    const savedData = localStorage.getItem('audit_wizard_step_' + currentStep);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const field = document.getElementById(key);
            if (field) {
                field.value = data[key];
                
                // Restaurer la sélection visuelle pour les échelles
                if (key.startsWith('audit_') && !key.includes('socid')) {
                    const option = document.querySelector(`[data-value="${data[key]}"]`);
                    if (option) {
                        option.classList.add('selected');
                    }
                }
            }
        });
    }
    
    // Validation initiale
    validateStep();
    
    // Auto-save toutes les 30 secondes
    setInterval(saveCurrentStep, 30000);
});

// Gestion du changement de société
document.addEventListener('change', function(e) {
    if (e.target.name === 'audit_socid') {
        const socid = e.target.value;
        if (socid) {
            window.location.href = '?step=' + currentStep + '&socid=' + socid;
        }
    }
});
</script>

</body>
</html>

<?php
llxFooter();
?>