<?php
/* Copyright (C) 2024 Up Digit Agency
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       wizard/modern.php
 * \ingroup    auditdigital
 * \brief      Modern wizard page for creating digital audits with enhanced UI/UX
 */

// Load Dolibarr environment
$res = 0;

// Méthode 1: Chemins relatifs standards
$paths_to_try = array(
    "../../main.inc.php",
    "../../../main.inc.php",
    "../main.inc.php",
    "../../../../main.inc.php",
    "../../../../../main.inc.php"
);

foreach ($paths_to_try as $path) {
    if (!$res && file_exists($path)) {
        $res = @include $path;
        if ($res) break;
    }
}

if (!$res) {
    die('Error: Cannot load Dolibarr main.inc.php');
}

// Check if module is enabled
if (!isModEnabled('auditdigital')) {
    accessforbidden('Module not enabled');
}

// Load required classes
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Try to load project form class if it exists
if (file_exists(DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php')) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
} elseif (file_exists(DOL_DOCUMENT_ROOT.'/core/class/html.formproject.class.php')) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formproject.class.php';
}

// Load our custom classes
try {
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php')) {
        require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
    }
    
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php')) {
        require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php';
    }
} catch (Exception $e) {
    // Continue without custom classes for now
}

// Load translation files
$langs->loadLangs(array("main", "companies", "projects"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int');
$id = GETPOST('id', 'int');

// Security check
if (isset($user->rights->auditdigital->audit->write)) {
    if (!$user->rights->auditdigital->audit->write) {
        accessforbidden();
    }
} else {
    if (!$user->id || $user->socid > 0) {
        accessforbidden();
    }
}

// Initialize objects
$formcompany = new FormCompany($db);

// Try to load project form class
$formproject = null;
if (isModEnabled('project')) {
    $projectClassNames = array('FormProjets', 'FormProjet', 'FormProject');
    
    foreach ($projectClassNames as $className) {
        if (class_exists($className)) {
            try {
                $formproject = new $className($db);
                break;
            } catch (Exception $e) {
                continue;
            }
        }
    }
}

$hookmanager->initHooks(array('auditdigitalwizard'));

// Handle AJAX requests
if (GETPOST('ajax', 'alpha')) {
    header('Content-Type: application/json');
    
    if ($action == 'save_step') {
        if (!isset($_SESSION['audit_wizard'])) {
            $_SESSION['audit_wizard'] = array();
        }
        $_SESSION['audit_wizard']['step'.$step] = $_POST;
        echo json_encode(array('success' => true));
        exit;
    }
    
    if ($action == 'add_comment') {
        $questionId = GETPOST('question_id', 'int');
        $comment = GETPOST('comment', 'restricthtml');
        $auditId = GETPOST('audit_id', 'int');
        
        // For now, just save to session
        if (!isset($_SESSION['audit_comments'])) {
            $_SESSION['audit_comments'] = array();
        }
        $_SESSION['audit_comments'][$questionId] = array(
            'comment' => $comment,
            'date' => date('Y-m-d H:i:s'),
            'attachments' => array()
        );
        
        echo json_encode(array('success' => true, 'message' => 'Commentaire ajouté'));
        exit;
    }
    
    if ($action == 'finish_audit') {
        echo json_encode(array(
            'success' => true,
            'message' => 'Audit créé avec succès',
            'redirect' => dol_buildpath('/auditdigital/audit_list.php', 1)
        ));
        exit;
    }
}

// Questionnaire data structure
$questionnaireSteps = array(
    1 => array(
        'title' => 'Informations générales',
        'description' => 'Renseignez les informations de base sur votre structure.',
        'icon' => 'fas fa-info-circle',
        'questions' => array(
            'structure_type' => array(
                'type' => 'cards',
                'label' => 'Type de structure',
                'required' => true,
                'options' => array(
                    'tpe_pme' => array(
                        'label' => 'TPE/PME',
                        'description' => 'Entreprise de moins de 250 employés',
                        'icon' => 'fas fa-building'
                    ),
                    'collectivite' => array(
                        'label' => 'Collectivité territoriale',
                        'description' => 'Administration publique locale',
                        'icon' => 'fas fa-landmark'
                    ),
                    'association' => array(
                        'label' => 'Association',
                        'description' => 'Organisation à but non lucratif',
                        'icon' => 'fas fa-hands-helping'
                    ),
                    'startup' => array(
                        'label' => 'Startup',
                        'description' => 'Jeune entreprise innovante',
                        'icon' => 'fas fa-rocket'
                    )
                )
            )
        )
    ),
    2 => array(
        'title' => 'Maturité digitale',
        'description' => 'Évaluez votre niveau de digitalisation actuel.',
        'icon' => 'fas fa-chart-line',
        'questions' => array(
            'digital_tools' => array(
                'type' => 'cards',
                'label' => 'Quels outils digitaux utilisez-vous ?',
                'required' => true,
                'multiple' => true,
                'options' => array(
                    'crm' => array(
                        'label' => 'CRM',
                        'description' => 'Gestion de la relation client',
                        'icon' => 'fas fa-users',
                        'score' => 20
                    ),
                    'erp' => array(
                        'label' => 'ERP',
                        'description' => 'Progiciel de gestion intégré',
                        'icon' => 'fas fa-cogs',
                        'score' => 25
                    ),
                    'website' => array(
                        'label' => 'Site web',
                        'description' => 'Présence en ligne',
                        'icon' => 'fas fa-globe',
                        'score' => 15
                    ),
                    'ecommerce' => array(
                        'label' => 'E-commerce',
                        'description' => 'Vente en ligne',
                        'icon' => 'fas fa-shopping-cart',
                        'score' => 20
                    ),
                    'social_media' => array(
                        'label' => 'Réseaux sociaux',
                        'description' => 'Marketing digital',
                        'icon' => 'fas fa-share-alt',
                        'score' => 10
                    ),
                    'analytics' => array(
                        'label' => 'Analytics',
                        'description' => 'Analyse de données',
                        'icon' => 'fas fa-chart-bar',
                        'score' => 10
                    )
                )
            )
        )
    ),
    3 => array(
        'title' => 'Cybersécurité',
        'description' => 'Évaluez votre niveau de sécurité informatique.',
        'icon' => 'fas fa-shield-alt',
        'questions' => array(
            'security_measures' => array(
                'type' => 'cards',
                'label' => 'Quelles mesures de sécurité avez-vous mises en place ?',
                'required' => true,
                'multiple' => true,
                'options' => array(
                    'antivirus' => array(
                        'label' => 'Antivirus',
                        'description' => 'Protection contre les malwares',
                        'icon' => 'fas fa-virus-slash',
                        'score' => 15
                    ),
                    'firewall' => array(
                        'label' => 'Pare-feu',
                        'description' => 'Filtrage du trafic réseau',
                        'icon' => 'fas fa-fire',
                        'score' => 20
                    ),
                    'backup' => array(
                        'label' => 'Sauvegarde',
                        'description' => 'Copie de sécurité des données',
                        'icon' => 'fas fa-save',
                        'score' => 25
                    ),
                    'vpn' => array(
                        'label' => 'VPN',
                        'description' => 'Réseau privé virtuel',
                        'icon' => 'fas fa-user-secret',
                        'score' => 15
                    ),
                    'mfa' => array(
                        'label' => 'Authentification 2FA',
                        'description' => 'Double authentification',
                        'icon' => 'fas fa-key',
                        'score' => 20
                    ),
                    'training' => array(
                        'label' => 'Formation sécurité',
                        'description' => 'Sensibilisation des équipes',
                        'icon' => 'fas fa-graduation-cap',
                        'score' => 5
                    )
                )
            )
        )
    ),
    4 => array(
        'title' => 'Cloud et infrastructure',
        'description' => 'Évaluez votre utilisation du cloud computing.',
        'icon' => 'fas fa-cloud',
        'questions' => array(
            'cloud_usage' => array(
                'type' => 'cards',
                'label' => 'Quel est votre niveau d\'adoption du cloud ?',
                'required' => true,
                'options' => array(
                    'no_cloud' => array(
                        'label' => 'Aucun cloud',
                        'description' => 'Infrastructure 100% on-premise',
                        'icon' => 'fas fa-server',
                        'score' => 0
                    ),
                    'basic_cloud' => array(
                        'label' => 'Cloud basique',
                        'description' => 'Quelques services cloud (email, stockage)',
                        'icon' => 'fas fa-cloud-upload-alt',
                        'score' => 25
                    ),
                    'hybrid_cloud' => array(
                        'label' => 'Cloud hybride',
                        'description' => 'Mix cloud/on-premise',
                        'icon' => 'fas fa-cloud-download-alt',
                        'score' => 50
                    ),
                    'full_cloud' => array(
                        'label' => 'Full cloud',
                        'description' => 'Infrastructure 100% cloud',
                        'icon' => 'fas fa-cloud',
                        'score' => 75
                    ),
                    'multi_cloud' => array(
                        'label' => 'Multi-cloud',
                        'description' => 'Plusieurs fournisseurs cloud',
                        'icon' => 'fas fa-clouds',
                        'score' => 100
                    )
                )
            )
        )
    ),
    5 => array(
        'title' => 'Automatisation',
        'description' => 'Évaluez votre niveau d\'automatisation des processus.',
        'icon' => 'fas fa-robot',
        'questions' => array(
            'automation_level' => array(
                'type' => 'cards',
                'label' => 'Quel est votre niveau d\'automatisation ?',
                'required' => true,
                'options' => array(
                    'manual' => array(
                        'label' => 'Processus manuels',
                        'description' => 'Peu ou pas d\'automatisation',
                        'icon' => 'fas fa-hand-paper',
                        'score' => 0
                    ),
                    'basic_automation' => array(
                        'label' => 'Automatisation basique',
                        'description' => 'Quelques tâches automatisées',
                        'icon' => 'fas fa-play',
                        'score' => 25
                    ),
                    'workflow_automation' => array(
                        'label' => 'Workflows automatisés',
                        'description' => 'Processus métier automatisés',
                        'icon' => 'fas fa-project-diagram',
                        'score' => 50
                    ),
                    'ai_automation' => array(
                        'label' => 'IA et automatisation',
                        'description' => 'Intelligence artificielle intégrée',
                        'icon' => 'fas fa-brain',
                        'score' => 75
                    ),
                    'full_automation' => array(
                        'label' => 'Automatisation complète',
                        'description' => 'Processus entièrement automatisés',
                        'icon' => 'fas fa-robot',
                        'score' => 100
                    )
                )
            )
        )
    ),
    6 => array(
        'title' => 'Synthèse et recommandations',
        'description' => 'Découvrez vos résultats et recommandations personnalisées.',
        'icon' => 'fas fa-chart-pie',
        'questions' => array()
    )
);

/*
 * View
 */

$title = 'Nouvel Audit Digital - Interface Moderne';
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, array(
    '/custom/auditdigital/css/auditdigital-modern.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/chart.js'
));

?>

<div class="audit-wizard-modern">
    <!-- Header moderne -->
    <div class="audit-wizard-header-modern">
        <h1>🎯 Audit Digital Moderne</h1>
        <p>Évaluez la maturité numérique de votre organisation avec notre interface nouvelle génération</p>
    </div>
    
    <!-- Stepper visuel moderne -->
    <div class="audit-stepper">
        <?php foreach ($questionnaireSteps as $stepNum => $stepData) { ?>
        <div class="step" data-step="<?php echo $stepNum; ?>" <?php echo $stepNum == 1 ? 'class="step active"' : ''; ?>>
            <div class="step-icon">
                <i class="<?php echo $stepData['icon']; ?>"></i>
            </div>
            <span><?php echo $stepData['title']; ?></span>
        </div>
        <?php if ($stepNum < count($questionnaireSteps)) { ?>
        <div class="step-line"></div>
        <?php } ?>
        <?php } ?>
    </div>
    
    <!-- Container du formulaire -->
    <div class="audit-form-container-modern">
        <form id="auditWizardForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="create_audit">
            <input type="hidden" id="currentStep" name="current_step" value="1">
            
            <!-- Étape 1: Informations générales -->
            <div id="step1" class="audit-step-content" style="display: block;">
                <h2 class="audit-step-title"><?php echo $questionnaireSteps[1]['title']; ?></h2>
                <p class="audit-step-description"><?php echo $questionnaireSteps[1]['description']; ?></p>
                
                <!-- Type de structure avec cards -->
                <div class="audit-form-group-modern">
                    <label class="audit-form-label-modern required">Type de structure</label>
                    <div class="audit-cards-container">
                        <?php foreach ($questionnaireSteps[1]['questions']['structure_type']['options'] as $value => $option) { ?>
                        <div class="audit-option-card" data-value="<?php echo $value; ?>" onclick="selectOption(this, 'structure_type')">
                            <div class="card-icon">
                                <i class="<?php echo $option['icon']; ?>"></i>
                            </div>
                            <div class="card-content">
                                <h4><?php echo $option['label']; ?></h4>
                                <p><?php echo $option['description']; ?></p>
                            </div>
                            <div class="check-mark">
                                <i class="fas fa-check"></i>
                            </div>
                            <input type="radio" name="structure_type" value="<?php echo $value; ?>" style="display: none;" required>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                
                <!-- Société -->
                <div class="audit-form-group-modern">
                    <label class="audit-form-label-modern required">Société</label>
                    <?php echo $formcompany->select_company(GETPOST('fk_soc', 'int'), 'fk_soc', '', 'Sélectionner une société', 1, 0, null, 0, 'audit-form-control-modern'); ?>
                </div>
                
                <!-- Projet (optionnel) -->
                <?php if ($formproject) { ?>
                <div class="audit-form-group-modern">
                    <label class="audit-form-label-modern">Projet (optionnel)</label>
                    <?php echo $formproject->select_projects(-1, GETPOST('fk_projet', 'int'), 'fk_projet', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'audit-form-control-modern'); ?>
                </div>
                <?php } ?>
            </div>
            
            <!-- Étape 2: Maturité digitale -->
            <div id="step2" class="audit-step-content" style="display: none;">
                <h2 class="audit-step-title"><?php echo $questionnaireSteps[2]['title']; ?></h2>
                <p class="audit-step-description"><?php echo $questionnaireSteps[2]['description']; ?></p>
                
                <div class="audit-form-group-modern">
                    <label class="audit-form-label-modern required"><?php echo $questionnaireSteps[2]['questions']['digital_tools']['label']; ?></label>
                    <div class="audit-cards-container">
                        <?php foreach ($questionnaireSteps[2]['questions']['digital_tools']['options'] as $value => $option) { ?>
                        <div class="audit-option-card" data-value="<?php echo $value; ?>" onclick="selectMultipleOption(this, 'digital_tools')">
                            <div class="card-icon">
                                <i class="<?php echo $option['icon']; ?>"></i>
                            </div>
                            <div class="card-content">
                                <h4><?php echo $option['label']; ?></h4>
                                <p><?php echo $option['description']; ?></p>
                            </div>
                            <div class="check-mark">
                                <i class="fas fa-check"></i>
                            </div>
                            <input type="checkbox" name="digital_tools[]" value="<?php echo $value; ?>" data-score="<?php echo $option['score']; ?>" style="display: none;">
                        </div>
                        
                        <!-- Section commentaires -->
                        <div class="comment-section" style="display: none;">
                            <button type="button" class="comment-toggle-btn" onclick="toggleComment('digital_tools_<?php echo $value; ?>')">
                                <i class="fas fa-comment"></i> Ajouter un commentaire
                            </button>
                            <div id="comment-digital_tools_<?php echo $value; ?>" class="comment-box" style="display:none;">
                                <textarea class="comment-textarea" placeholder="Vos remarques sur <?php echo $option['label']; ?>..."></textarea>
                                <div class="file-upload">
                                    <label class="file-upload-btn">
                                        <i class="fas fa-paperclip"></i> Joindre un fichier
                                        <input type="file" hidden multiple>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            
            <!-- Étape 3: Cybersécurité -->
            <div id="step3" class="audit-step-content" style="display: none;">
                <h2 class="audit-step-title"><?php echo $questionnaireSteps[3]['title']; ?></h2>
                <p class="audit-step-description"><?php echo $questionnaireSteps[3]['description']; ?></p>
                
                <div class="audit-form-group-modern">
                    <label class="audit-form-label-modern required"><?php echo $questionnaireSteps[3]['questions']['security_measures']['label']; ?></label>
                    <div class="audit-cards-container">
                        <?php foreach ($questionnaireSteps[3]['questions']['security_measures']['options'] as $value => $option) { ?>
                        <div class="audit-option-card" data-value="<?php echo $value; ?>" onclick="selectMultipleOption(this, 'security_measures')">
                            <div class="card-icon">
                                <i class="<?php echo $option['icon']; ?>"></i>
                            </div>
                            <div class="card-content">
                                <h4><?php echo $option['label']; ?></h4>
                                <p><?php echo $option['description']; ?></p>
                            </div>
                            <div class="check-mark">
                                <i class="fas fa-check"></i>
                            </div>
                            <input type="checkbox" name="security_measures[]" value="<?php echo $value; ?>" data-score="<?php echo $option['score']; ?>" style="display: none;">
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            
            <!-- Étape 4: Cloud -->
            <div id="step4" class="audit-step-content" style="display: none;">
                <h2 class="audit-step-title"><?php echo $questionnaireSteps[4]['title']; ?></h2>
                <p class="audit-step-description"><?php echo $questionnaireSteps[4]['description']; ?></p>
                
                <div class="audit-form-group-modern">
                    <label class="audit-form-label-modern required"><?php echo $questionnaireSteps[4]['questions']['cloud_usage']['label']; ?></label>
                    <div class="audit-cards-container">
                        <?php foreach ($questionnaireSteps[4]['questions']['cloud_usage']['options'] as $value => $option) { ?>
                        <div class="audit-option-card" data-value="<?php echo $value; ?>" onclick="selectOption(this, 'cloud_usage')">
                            <div class="card-icon">
                                <i class="<?php echo $option['icon']; ?>"></i>
                            </div>
                            <div class="card-content">
                                <h4><?php echo $option['label']; ?></h4>
                                <p><?php echo $option['description']; ?></p>
                            </div>
                            <div class="check-mark">
                                <i class="fas fa-check"></i>
                            </div>
                            <input type="radio" name="cloud_usage" value="<?php echo $value; ?>" data-score="<?php echo $option['score']; ?>" style="display: none;" required>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            
            <!-- Étape 5: Automatisation -->
            <div id="step5" class="audit-step-content" style="display: none;">
                <h2 class="audit-step-title"><?php echo $questionnaireSteps[5]['title']; ?></h2>
                <p class="audit-step-description"><?php echo $questionnaireSteps[5]['description']; ?></p>
                
                <div class="audit-form-group-modern">
                    <label class="audit-form-label-modern required"><?php echo $questionnaireSteps[5]['questions']['automation_level']['label']; ?></label>
                    <div class="audit-cards-container">
                        <?php foreach ($questionnaireSteps[5]['questions']['automation_level']['options'] as $value => $option) { ?>
                        <div class="audit-option-card" data-value="<?php echo $value; ?>" onclick="selectOption(this, 'automation_level')">
                            <div class="card-icon">
                                <i class="<?php echo $option['icon']; ?>"></i>
                            </div>
                            <div class="card-content">
                                <h4><?php echo $option['label']; ?></h4>
                                <p><?php echo $option['description']; ?></p>
                            </div>
                            <div class="check-mark">
                                <i class="fas fa-check"></i>
                            </div>
                            <input type="radio" name="automation_level" value="<?php echo $value; ?>" data-score="<?php echo $option['score']; ?>" style="display: none;" required>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            
            <!-- Étape 6: Synthèse -->
            <div id="step6" class="audit-step-content" style="display: none;">
                <h2 class="audit-step-title"><?php echo $questionnaireSteps[6]['title']; ?></h2>
                <p class="audit-step-description"><?php echo $questionnaireSteps[6]['description']; ?></p>
                
                <!-- Scores par catégorie -->
                <div class="audit-score-container">
                    <div class="audit-score-card">
                        <div class="audit-score-title">Score Global</div>
                        <div id="score-global" class="audit-score-value">0%</div>
                    </div>
                    <div class="audit-score-card">
                        <div class="audit-score-title">Maturité Digitale</div>
                        <div id="score-digital" class="audit-score-value">0%</div>
                    </div>
                    <div class="audit-score-card">
                        <div class="audit-score-title">Cybersécurité</div>
                        <div id="score-security" class="audit-score-value">0%</div>
                    </div>
                    <div class="audit-score-card">
                        <div class="audit-score-title">Cloud</div>
                        <div id="score-cloud" class="audit-score-value">0%</div>
                    </div>
                    <div class="audit-score-card">
                        <div class="audit-score-title">Automatisation</div>
                        <div id="score-automation" class="audit-score-value">0%</div>
                    </div>
                </div>
                
                <!-- Graphiques -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h3 class="chart-title">Radar des compétences</h3>
                            <canvas id="radarChart" width="400" height="400"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h3 class="chart-title">Progression par domaine</h3>
                            <canvas id="progressChart" width="400" height="400"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Recommandations -->
                <div id="recommendations-container" class="audit-recommendations">
                    <!-- Les recommandations seront générées dynamiquement -->
                </div>
            </div>
            
        </form>
    </div>
    
    <!-- Navigation moderne -->
    <div class="audit-navigation">
        <button type="button" id="prevBtn" class="btn-modern btn-secondary-modern" onclick="previousStep()" style="display: none;">
            <i class="fas fa-arrow-left"></i> Précédent
        </button>
        
        <div class="progress-container">
            <div class="progress-bar-animated" id="progressBar" style="width: 16.67%;"></div>
        </div>
        
        <button type="button" id="nextBtn" class="btn-modern" onclick="nextStep()">
            Suivant <i class="fas fa-arrow-right"></i>
        </button>
        
        <button type="button" id="finishBtn" class="btn-modern btn-success-modern" onclick="finishAudit()" style="display: none;">
            <i class="fas fa-check"></i> Terminer l'audit
        </button>
    </div>
</div>

<script src="/custom/auditdigital/js/wizard-modern.js"></script>

<?php

llxFooter();

?>