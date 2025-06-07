<?php
/**
 * Wizard Moderne AuditDigital - Version Corrigée et Fonctionnelle
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
        
        // Calculer les scores
        $digital_level = $wizard_data['step_2']['audit_digital_level'] ?? 0;
        $web_presence = $wizard_data['step_2']['audit_web_presence'] ?? 0;
        $maturity_score = ($digital_level + $web_presence) * 3;
        
        $security_level = $wizard_data['step_3']['audit_security_level'] ?? 0;
        $rgpd_compliance = $wizard_data['step_3']['audit_rgpd_compliance'] ?? 0;
        $security_score = ($security_level + $rgpd_compliance) * 2.5;
        
        $cloud_adoption = $wizard_data['step_4']['audit_cloud_adoption'] ?? 0;
        $mobility = $wizard_data['step_4']['audit_mobility'] ?? 0;
        $cloud_score = ($cloud_adoption + $mobility) * 2.5;
        
        $automation_level = $wizard_data['step_5']['audit_automation_level'] ?? 0;
        $collaboration_tools = $wizard_data['step_5']['audit_collaboration_tools'] ?? 0;
        $automation_score = ($automation_level + $collaboration_tools) * 2;
        
        $total_score = $maturity_score + $security_score + $cloud_score + $automation_score;
        
        // Set properties from wizard data
        $audit->ref = 'AUDIT-'.date('YmdHis');
        $audit->label = 'Audit Digital - '.date('d/m/Y');
        $audit->fk_soc = $wizard_data['step_1']['audit_socid'] ?? 0;
        $audit->structure_type = $wizard_data['step_1']['audit_structure_type'] ?? '';
        $audit->audit_type = 'digital_maturity'; // CHAMP OBLIGATOIRE AJOUTÉ
        $audit->status = 0; // Draft
        
        // Sauvegarder les scores calculés
        $audit->score_global = round($total_score);
        $audit->score_maturite = round($maturity_score);
        $audit->score_cybersecurite = round($security_score);
        $audit->score_cloud = round($cloud_score);
        $audit->score_automatisation = round($automation_score);
        
        // Sauvegarder les réponses en JSON
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
llxHeader("", "Audit Digital Moderne");

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Digital Moderne</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS Moderne Simplifié et Fonctionnel */
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.12);
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
            text-align: center;
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

        /* Stepper simple */
        .modern-stepper {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .stepper-item {
            display: flex;
            align-items: center;
            margin: 0 15px;
        }

        .stepper-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
            transition: var(--transition);
        }

        .stepper-item.active .stepper-number {
            background: var(--primary-color);
            color: white;
        }

        .stepper-item.completed .stepper-number {
            background: var(--success-color);
            color: white;
        }

        /* Contenu */
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
            text-align: center;
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

        /* Formulaires */
        .form-section {
            margin-bottom: 30px;
        }

        .form-section h3 {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .required {
            color: var(--danger-color);
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        /* Responsive */
        @media (max-width: 768px) {
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
        }

        /* Notifications */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: var(--shadow-medium);
            z-index: 1000;
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<!-- Interface Wizard Moderne -->
<div class="modern-wizard-container">
    <!-- Header avec gradient -->
    <div class="wizard-header">
        <div class="wizard-title">
            <h1><i class="fas fa-rocket"></i> Audit Digital Moderne</h1>
            <p>Interface nouvelle génération - Étape <?php echo $step; ?>/6</p>
        </div>
    </div>

    <!-- Stepper simple -->
    <div class="modern-stepper">
        <?php for ($i = 1; $i <= 6; $i++): ?>
            <div class="stepper-item <?php echo ($i == $step) ? 'active' : ''; ?> <?php echo ($i < $step) ? 'completed' : ''; ?>">
                <div class="stepper-number"><?php echo $i; ?></div>
                <span><?php 
                    $steps = ['Infos', 'Digital', 'Sécurité', 'Cloud', 'Auto', 'Synthèse'];
                    echo $steps[$i-1]; 
                ?></span>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Contenu de l'étape -->
    <div class="wizard-content">
        <form id="wizardForm" method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="save_step">
            <input type="hidden" name="step" value="<?php echo $step; ?>">

            <?php if ($step == 1): ?>
                <!-- Étape 1: Informations Générales -->
                <div class="step-container">
                    <div class="step-header">
                        <h2><i class="fas fa-info-circle"></i> Informations Générales</h2>
                        <p>Commençons par les informations de base sur votre organisation</p>
                    </div>

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
                        <?php echo $formcompany->select_company(GETPOST('socid', 'int'), 'audit_socid', '', 'Sélectionnez une société...', 1, 0, null, 0, 'form-control'); ?>
                    </div>

                    <!-- Champ AuditType obligatoire caché -->
                    <input type="hidden" name="audit_type" value="digital_maturity">
                </div>

            <?php elseif ($step == 2): ?>
                <!-- Étape 2: Maturité Digitale -->
                <div class="step-container">
                    <div class="step-header">
                        <h2><i class="fas fa-digital-tachograph"></i> Maturité Digitale</h2>
                        <p>Évaluez votre niveau de transformation digitale</p>
                    </div>

                    <div class="form-section">
                        <h3>Niveau de digitalisation des processus <span class="required">*</span></h3>
                        <div class="option-cards">
                            <div class="option-card" data-value="1" onclick="selectOption(this, 'audit_digital_level')">
                                <div class="card-icon">
                                    <i class="fas fa-seedling"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Niveau 1 - Débutant</h4>
                                    <p>Processus principalement manuels, peu d'outils digitaux</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="3" onclick="selectOption(this, 'audit_digital_level')">
                                <div class="card-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Niveau 3 - Intermédiaire</h4>
                                    <p>Certains processus digitalisés, outils métier en place</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="5" onclick="selectOption(this, 'audit_digital_level')">
                                <div class="card-icon">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Niveau 5 - Avancé</h4>
                                    <p>Processus largement digitalisés, automatisation poussée</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <input type="hidden" name="audit_digital_level" id="audit_digital_level" required>
                    </div>

                    <div class="form-section">
                        <h3>Présence sur le web <span class="required">*</span></h3>
                        <div class="option-cards">
                            <div class="option-card" data-value="1" onclick="selectOption(this, 'audit_web_presence')">
                                <div class="card-icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Site vitrine simple</h4>
                                    <p>Site web basique, peu de fonctionnalités</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="3" onclick="selectOption(this, 'audit_web_presence')">
                                <div class="card-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Site interactif</h4>
                                    <p>E-commerce, formulaires, interactions utilisateur</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="5" onclick="selectOption(this, 'audit_web_presence')">
                                <div class="card-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Écosystème digital</h4>
                                    <p>Multi-canal, mobile, réseaux sociaux intégrés</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <input type="hidden" name="audit_web_presence" id="audit_web_presence" required>
                    </div>
                </div>

            <?php elseif ($step == 3): ?>
                <!-- Étape 3: Cybersécurité -->
                <div class="step-container">
                    <div class="step-header">
                        <h2><i class="fas fa-shield-alt"></i> Cybersécurité</h2>
                        <p>Évaluez votre niveau de protection et de sécurité informatique</p>
                    </div>

                    <div class="form-section">
                        <h3>Niveau de protection des données <span class="required">*</span></h3>
                        <div class="option-cards">
                            <div class="option-card" data-value="1" onclick="selectOption(this, 'audit_security_level')">
                                <div class="card-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Protection basique</h4>
                                    <p>Antivirus simple, pas de politique de sécurité formelle</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="3" onclick="selectOption(this, 'audit_security_level')">
                                <div class="card-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Protection intermédiaire</h4>
                                    <p>Firewall, sauvegardes régulières, sensibilisation utilisateurs</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="5" onclick="selectOption(this, 'audit_security_level')">
                                <div class="card-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Protection avancée</h4>
                                    <p>Chiffrement, authentification forte, monitoring 24/7</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <input type="hidden" name="audit_security_level" id="audit_security_level" required>
                    </div>

                    <div class="form-section">
                        <h3>Conformité RGPD <span class="required">*</span></h3>
                        <div class="option-cards">
                            <div class="option-card" data-value="1" onclick="selectOption(this, 'audit_rgpd_compliance')">
                                <div class="card-icon">
                                    <i class="fas fa-question-circle"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Non conforme</h4>
                                    <p>Pas de démarche RGPD initiée</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="3" onclick="selectOption(this, 'audit_rgpd_compliance')">
                                <div class="card-icon">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div class="card-content">
                                    <h4>En cours</h4>
                                    <p>Démarche initiée, registre des traitements en cours</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="5" onclick="selectOption(this, 'audit_rgpd_compliance')">
                                <div class="card-icon">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Conforme</h4>
                                    <p>RGPD respecté, DPO nommé, procédures en place</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <input type="hidden" name="audit_rgpd_compliance" id="audit_rgpd_compliance" required>
                    </div>
                </div>

            <?php elseif ($step == 4): ?>
                <!-- Étape 4: Cloud & Infrastructure -->
                <div class="step-container">
                    <div class="step-header">
                        <h2><i class="fas fa-cloud"></i> Cloud & Infrastructure</h2>
                        <p>Évaluez votre infrastructure informatique et votre adoption du cloud</p>
                    </div>

                    <div class="form-section">
                        <h3>Niveau d'adoption du cloud <span class="required">*</span></h3>
                        <div class="option-cards">
                            <div class="option-card" data-value="1" onclick="selectOption(this, 'audit_cloud_adoption')">
                                <div class="card-icon">
                                    <i class="fas fa-server"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Infrastructure locale</h4>
                                    <p>Serveurs physiques, pas de cloud</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="3" onclick="selectOption(this, 'audit_cloud_adoption')">
                                <div class="card-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Cloud hybride</h4>
                                    <p>Mix infrastructure locale et services cloud</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="5" onclick="selectOption(this, 'audit_cloud_adoption')">
                                <div class="card-icon">
                                    <i class="fas fa-cloud"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Cloud natif</h4>
                                    <p>Infrastructure entièrement dans le cloud</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <input type="hidden" name="audit_cloud_adoption" id="audit_cloud_adoption" required>
                    </div>

                    <div class="form-section">
                        <h3>Mobilité et télétravail <span class="required">*</span></h3>
                        <div class="option-cards">
                            <div class="option-card" data-value="1" onclick="selectOption(this, 'audit_mobility')">
                                <div class="card-icon">
                                    <i class="fas fa-desktop"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Travail sur site uniquement</h4>
                                    <p>Pas d'accès distant, postes fixes</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="3" onclick="selectOption(this, 'audit_mobility')">
                                <div class="card-icon">
                                    <i class="fas fa-laptop"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Mobilité occasionnelle</h4>
                                    <p>VPN, quelques outils mobiles</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="5" onclick="selectOption(this, 'audit_mobility')">
                                <div class="card-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Mobilité complète</h4>
                                    <p>Télétravail, applications mobiles, BYOD</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <input type="hidden" name="audit_mobility" id="audit_mobility" required>
                    </div>
                </div>

            <?php elseif ($step == 5): ?>
                <!-- Étape 5: Automatisation -->
                <div class="step-container">
                    <div class="step-header">
                        <h2><i class="fas fa-robot"></i> Automatisation</h2>
                        <p>Évaluez votre niveau d'automatisation des processus métier</p>
                    </div>

                    <div class="form-section">
                        <h3>Automatisation des processus <span class="required">*</span></h3>
                        <div class="option-cards">
                            <div class="option-card" data-value="1" onclick="selectOption(this, 'audit_automation_level')">
                                <div class="card-icon">
                                    <i class="fas fa-hand-paper"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Processus manuels</h4>
                                    <p>Peu ou pas d'automatisation</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="3" onclick="selectOption(this, 'audit_automation_level')">
                                <div class="card-icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Automatisation partielle</h4>
                                    <p>Quelques processus automatisés</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="5" onclick="selectOption(this, 'audit_automation_level')">
                                <div class="card-icon">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Automatisation avancée</h4>
                                    <p>IA, RPA, workflows automatisés</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <input type="hidden" name="audit_automation_level" id="audit_automation_level" required>
                    </div>

                    <div class="form-section">
                        <h3>Outils de collaboration <span class="required">*</span></h3>
                        <div class="option-cards">
                            <div class="option-card" data-value="1" onclick="selectOption(this, 'audit_collaboration_tools')">
                                <div class="card-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Email uniquement</h4>
                                    <p>Communication par email principalement</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="3" onclick="selectOption(this, 'audit_collaboration_tools')">
                                <div class="card-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Outils de base</h4>
                                    <p>Chat, visioconférence, partage de fichiers</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="option-card" data-value="5" onclick="selectOption(this, 'audit_collaboration_tools')">
                                <div class="card-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Suite collaborative</h4>
                                    <p>Plateforme intégrée, gestion de projets, workflows</p>
                                </div>
                                <div class="card-check"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <input type="hidden" name="audit_collaboration_tools" id="audit_collaboration_tools" required>
                    </div>
                </div>

            <?php elseif ($step == 6): ?>
                <!-- Étape 6: Synthèse -->
                <div class="step-container">
                    <div class="step-header">
                        <h2><i class="fas fa-chart-line"></i> Synthèse & Recommandations</h2>
                        <p>Voici le résumé de votre audit digital</p>
                    </div>

                    <?php
                    // Calculer le score basé sur les données de session
                    $wizard_data = $_SESSION['audit_wizard_data'] ?? array();
                    $total_score = 0;
                    $max_score = 0;
                    $scores_detail = array();
                    
                    // Étape 2: Maturité Digitale (poids: 30%)
                    $digital_level = $wizard_data['step_2']['audit_digital_level'] ?? 0;
                    $web_presence = $wizard_data['step_2']['audit_web_presence'] ?? 0;
                    $maturity_score = ($digital_level + $web_presence) * 3; // Max 30
                    $scores_detail['Maturité Digitale'] = $maturity_score;
                    
                    // Étape 3: Cybersécurité (poids: 25%)
                    $security_level = $wizard_data['step_3']['audit_security_level'] ?? 0;
                    $rgpd_compliance = $wizard_data['step_3']['audit_rgpd_compliance'] ?? 0;
                    $security_score = ($security_level + $rgpd_compliance) * 2.5; // Max 25
                    $scores_detail['Cybersécurité'] = $security_score;
                    
                    // Étape 4: Cloud & Infrastructure (poids: 25%)
                    $cloud_adoption = $wizard_data['step_4']['audit_cloud_adoption'] ?? 0;
                    $mobility = $wizard_data['step_4']['audit_mobility'] ?? 0;
                    $cloud_score = ($cloud_adoption + $mobility) * 2.5; // Max 25
                    $scores_detail['Cloud & Infrastructure'] = $cloud_score;
                    
                    // Étape 5: Automatisation (poids: 20%)
                    $automation_level = $wizard_data['step_5']['audit_automation_level'] ?? 0;
                    $collaboration_tools = $wizard_data['step_5']['audit_collaboration_tools'] ?? 0;
                    $automation_score = ($automation_level + $collaboration_tools) * 2; // Max 20
                    $scores_detail['Automatisation'] = $automation_score;
                    
                    $total_score = $maturity_score + $security_score + $cloud_score + $automation_score;
                    $max_score = 100;
                    
                    // Déterminer le niveau de maturité
                    $maturity_level = 'Débutant';
                    $maturity_color = '#dc3545';
                    $maturity_icon = 'fa-seedling';
                    
                    if ($total_score >= 80) {
                        $maturity_level = 'Expert';
                        $maturity_color = '#28a745';
                        $maturity_icon = 'fa-trophy';
                    } elseif ($total_score >= 60) {
                        $maturity_level = 'Avancé';
                        $maturity_color = '#17a2b8';
                        $maturity_icon = 'fa-rocket';
                    } elseif ($total_score >= 40) {
                        $maturity_level = 'Intermédiaire';
                        $maturity_color = '#ffc107';
                        $maturity_icon = 'fa-chart-line';
                    }
                    ?>

                    <div style="text-align: center; padding: 40px;">
                        <div style="font-size: 4rem; color: <?php echo $maturity_color; ?>; margin-bottom: 20px;">
                            <i class="fas <?php echo $maturity_icon; ?>"></i>
                        </div>
                        <h3>Audit Digital Complété !</h3>
                        <p>Votre évaluation de maturité digitale est prête.</p>
                        
                        <div style="margin: 30px 0; padding: 30px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <h4 style="font-size: 1.8rem; margin-bottom: 15px;">
                                Score global : <span style="color: <?php echo $maturity_color; ?>; font-weight: bold;"><?php echo round($total_score); ?>/100</span>
                            </h4>
                            <p style="font-size: 1.2rem; margin-bottom: 20px;">
                                Niveau de maturité : <strong style="color: <?php echo $maturity_color; ?>;"><?php echo $maturity_level; ?></strong>
                            </p>
                            
                            <!-- Barre de progression -->
                            <div style="background: #e9ecef; border-radius: 10px; height: 20px; margin: 20px 0; overflow: hidden;">
                                <div style="background: <?php echo $maturity_color; ?>; height: 100%; width: <?php echo $total_score; ?>%; transition: width 0.8s ease-in-out; border-radius: 10px;"></div>
                            </div>
                        </div>
                        
                        <!-- Détail des scores par domaine -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
                            <?php foreach ($scores_detail as $domain => $score): ?>
                                <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                    <h5 style="margin-bottom: 10px; color: #2c3e50;"><?php echo $domain; ?></h5>
                                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                        <?php echo round($score); ?><?php 
                                        $max_domain = ($domain == 'Maturité Digitale') ? 30 : 
                                                     (($domain == 'Cybersécurité' || $domain == 'Cloud & Infrastructure') ? 25 : 20);
                                        echo '/'.$max_domain; 
                                        ?>
                                    </div>
                                    <div style="background: #e9ecef; border-radius: 5px; height: 8px; margin-top: 10px; overflow: hidden;">
                                        <div style="background: var(--primary-color); height: 100%; width: <?php echo ($score/$max_domain)*100; ?>%; border-radius: 5px;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Recommandations rapides -->
                        <div style="margin-top: 40px; padding: 25px; background: #fff3cd; border-radius: 12px; border-left: 5px solid #ffc107;">
                            <h5 style="color: #856404; margin-bottom: 15px;">
                                <i class="fas fa-lightbulb"></i> Recommandations prioritaires
                            </h5>
                            <div style="text-align: left; color: #856404;">
                                <?php if ($total_score < 40): ?>
                                    <p>• Commencer par digitaliser les processus de base</p>
                                    <p>• Mettre en place une politique de sécurité informatique</p>
                                    <p>• Former les équipes aux outils numériques</p>
                                <?php elseif ($total_score < 60): ?>
                                    <p>• Optimiser les processus digitaux existants</p>
                                    <p>• Renforcer la sécurité et la conformité RGPD</p>
                                    <p>• Explorer les solutions cloud</p>
                                <?php elseif ($total_score < 80): ?>
                                    <p>• Automatiser davantage de processus</p>
                                    <p>• Implémenter des solutions d'intelligence artificielle</p>
                                    <p>• Développer l'écosystème digital</p>
                                <?php else: ?>
                                    <p>• Maintenir l'excellence opérationnelle</p>
                                    <p>• Innover avec les technologies émergentes</p>
                                    <p>• Partager les bonnes pratiques</p>
                                <?php endif; ?>
                            </div>
                        </div>
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

<script>
// JavaScript corrigé et simplifié
let currentStep = <?php echo $step; ?>;

// Fonction de sélection d'option CORRIGÉE
function selectOption(element, fieldName) {
    console.log('selectOption appelée:', element, fieldName);
    
    // Retirer la sélection précédente
    const parent = element.parentNode;
    const cards = parent.querySelectorAll('.option-card');
    cards.forEach(card => {
        card.classList.remove('selected');
    });
    
    // Ajouter la sélection avec animation
    element.classList.add('selected');
    
    // Mettre à jour le champ caché
    const hiddenField = document.getElementById(fieldName);
    if (hiddenField) {
        hiddenField.value = element.dataset.value;
        console.log('Valeur mise à jour:', fieldName, '=', element.dataset.value);
    }
    
    // Vibration tactile sur mobile
    if ('vibrate' in navigator) {
        navigator.vibrate(50);
    }
    
    // Notification de succès
    showNotification('Sélection mise à jour', 'success');
}

// Navigation entre étapes
function nextStep() {
    if (validateCurrentStep()) {
        window.location.href = '?step=' + (currentStep + 1);
    }
}

function previousStep() {
    window.location.href = '?step=' + (currentStep - 1);
}

// Validation moderne
function validateCurrentStep() {
    const requiredFields = document.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value) {
            field.style.border = '2px solid var(--danger-color)';
            isValid = false;
        } else {
            field.style.border = '';
        }
    });
    
    if (!isValid) {
        showNotification('Veuillez remplir tous les champs obligatoires', 'error');
    }
    
    return isValid;
}

// Créer l'audit
function createAudit() {
    if (validateCurrentStep()) {
        document.getElementById('wizardForm').action = '<?php echo $_SERVER["PHP_SELF"]; ?>?action=create_audit';
        document.getElementById('wizardForm').submit();
    }
}

// Notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
    
    if (type === 'error') {
        notification.style.background = 'var(--danger-color)';
    }
    
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
    console.log('Wizard moderne initialisé - Étape', currentStep);
    
    // Restaurer les sélections depuis la session si disponible
    const savedData = sessionStorage.getItem('audit_wizard_step_' + currentStep);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const field = document.getElementById(key);
            if (field) {
                field.value = data[key];
                // Restaurer la sélection visuelle
                const card = document.querySelector(`[data-value="${data[key]}"]`);
                if (card) {
                    card.classList.add('selected');
                }
            }
        });
    }
});

// Auto-save simple
setInterval(() => {
    const formData = new FormData(document.getElementById('wizardForm'));
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('audit_')) {
            data[key] = value;
        }
    }
    sessionStorage.setItem('audit_wizard_step_' + currentStep, JSON.stringify(data));
}, 10000); // Toutes les 10 secondes
</script>

</body>
</html>

<?php
llxFooter();
?>