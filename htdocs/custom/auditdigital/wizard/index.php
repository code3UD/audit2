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
 * \file       wizard/index.php
 * \ingroup    auditdigital
 * \brief      Main wizard page for creating digital audits
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
    $res = @include "../../../../main.inc.php";
}
if (!$res && file_exists("../../../../../main.inc.php")) {
    $res = @include "../../../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';

// Load translation files required by the page
$langs->loadLangs(array("auditdigital@auditdigital", "other"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int');
$id = GETPOST('id', 'int');

// Security check
if (!$user->rights->auditdigital->audit->write) {
    accessforbidden();
}

// Initialize objects
$object = new Audit($db);
$questionnaire = new Questionnaire($db);
$formcompany = new FormCompany($db);
$formproject = new FormProject($db);

$hookmanager->initHooks(array('auditdigitalwizard'));

// Handle AJAX requests
if (GETPOST('ajax', 'alpha')) {
    header('Content-Type: application/json');
    
    if ($action == 'save_step') {
        $stepData = json_decode(file_get_contents('php://input'), true);
        
        // Validate step data
        $validation = $questionnaire->validateStep('step'.$step, $stepData['responses']);
        
        if ($validation['valid']) {
            // Save to session
            if (!isset($_SESSION['audit_wizard'])) {
                $_SESSION['audit_wizard'] = array();
            }
            $_SESSION['audit_wizard']['step'.$step] = $stepData['responses'];
            
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'errors' => $validation['errors']));
        }
        exit;
    }
    
    if ($action == 'finish_audit') {
        $auditData = json_decode(file_get_contents('php://input'), true);
        
        try {
            $db->begin();
            
            // Create new audit
            $object->ref = '(PROV)';
            $object->label = 'Audit Digital - '.dol_print_date(dol_now(), '%d/%m/%Y %H:%M');
            $object->audit_type = 'digital_maturity';
            $object->structure_type = $auditData['structure_type'] ?: 'tpe_pme';
            $object->fk_soc = GETPOST('fk_soc', 'int') ?: 0;
            $object->fk_projet = GETPOST('fk_projet', 'int') ?: 0;
            $object->date_creation = dol_now();
            $object->date_audit = dol_now();
            $object->fk_user_creat = $user->id;
            $object->status = Audit::STATUS_DRAFT;
            
            // Set scores
            if (isset($auditData['scores'])) {
                $object->score_global = $auditData['scores']['global'] ?? 0;
                $object->score_maturite = $auditData['scores']['maturite'] ?? 0;
                $object->score_cybersecurite = $auditData['scores']['cybersecurite'] ?? 0;
                $object->score_cloud = $auditData['scores']['cloud'] ?? 0;
                $object->score_automatisation = $auditData['scores']['automatisation'] ?? 0;
            }
            
            // Set JSON data
            $object->json_responses = json_encode($auditData['responses']);
            $object->json_config = json_encode(array(
                'wizard_version' => '1.0',
                'completion_date' => dol_now(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ));
            
            // Generate recommendations
            $recommendations = $object->generateRecommendations($auditData['scores'], $auditData['structure_type']);
            $object->json_recommendations = json_encode($recommendations);
            
            $result = $object->create($user);
            
            if ($result > 0) {
                $db->commit();
                
                // Clear session data
                unset($_SESSION['audit_wizard']);
                
                echo json_encode(array(
                    'success' => true,
                    'audit_id' => $object->id,
                    'redirect' => dol_buildpath('/auditdigital/audit_card.php?id='.$object->id, 1)
                ));
            } else {
                $db->rollback();
                echo json_encode(array('success' => false, 'error' => $object->error));
            }
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
        exit;
    }
}

/*
 * View
 */

$title = $langs->trans('NewAudit');
$help_url = '';

llxHeader('', $title, $help_url);

// Get questionnaire structure
$questionnaireData = $questionnaire->getQuestionnaire();

?>

<div class="audit-wizard">
    <!-- Header -->
    <div class="audit-wizard-header">
        <h1><?php echo $langs->trans('NewAudit'); ?></h1>
        <p><?php echo $langs->trans('AuditDigitalDesc'); ?></p>
    </div>
    
    <!-- Progress bar -->
    <div class="audit-progress">
        <div class="audit-progress-bar">
            <div class="audit-progress-fill" style="width: 0%"></div>
        </div>
        <div class="audit-steps">
            <?php
            $stepLabels = array(
                1 => $langs->trans('Step1General'),
                2 => $langs->trans('Step2Maturity'),
                3 => $langs->trans('Step3Cybersecurity'),
                4 => $langs->trans('Step4Cloud'),
                5 => $langs->trans('Step5Automation'),
                6 => $langs->trans('Step6Synthesis')
            );
            
            foreach ($stepLabels as $stepNum => $stepLabel) {
                echo '<div class="audit-step" data-step="'.$stepNum.'">';
                echo '<div class="audit-step-number">'.$stepNum.'</div>';
                echo '<div class="audit-step-label">'.dol_trunc($stepLabel, 15).'</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
    
    <!-- Form container -->
    <div class="audit-form-container">
        <!-- Step 1: General Information -->
        <div id="step1" class="audit-step-content" style="display: block;">
            <h2 class="audit-step-title"><?php echo $langs->trans('Step1General'); ?></h2>
            <p class="audit-step-description"><?php echo $questionnaireData['step1_general']['description']; ?></p>
            
            <form id="step1-form">
                <!-- Structure Type -->
                <div class="audit-form-group">
                    <label class="audit-form-label required"><?php echo $langs->trans('StructureTypeQuestion'); ?></label>
                    <div class="audit-radio-group">
                        <?php foreach ($questionnaireData['step1_general']['questions']['structure_type']['options'] as $value => $label) { ?>
                        <div class="audit-radio-item">
                            <input type="radio" name="structure_type" value="<?php echo $value; ?>" id="structure_type_<?php echo $value; ?>" required>
                            <label for="structure_type_<?php echo $value; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                
                <!-- Third Party -->
                <div class="audit-form-group">
                    <label class="audit-form-label required"><?php echo $langs->trans('ThirdParty'); ?></label>
                    <?php echo $formcompany->select_company(GETPOST('fk_soc', 'int'), 'fk_soc', '', 'SelectThirdParty', 1, 0, null, 0, 'minwidth300'); ?>
                </div>
                
                <!-- Project (optional) -->
                <div class="audit-form-group">
                    <label class="audit-form-label"><?php echo $langs->trans('Project'); ?></label>
                    <?php echo $formproject->select_projects(-1, GETPOST('fk_projet', 'int'), 'fk_projet', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'minwidth300'); ?>
                </div>
                
                <!-- Sector -->
                <div class="audit-form-group">
                    <label class="audit-form-label required"><?php echo $langs->trans('SectorQuestion'); ?></label>
                    <select name="sector" class="audit-form-control" required>
                        <option value="">-- <?php echo $langs->trans('Select'); ?> --</option>
                        <?php foreach ($questionnaireData['step1_general']['questions']['sector']['options'] as $value => $label) { ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                </div>
                
                <!-- Employees Count -->
                <div class="audit-form-group">
                    <label class="audit-form-label required"><?php echo $langs->trans('EmployeesCountQuestion'); ?></label>
                    <select name="employees_count" class="audit-form-control" required>
                        <option value="">-- <?php echo $langs->trans('Select'); ?> --</option>
                        <?php foreach ($questionnaireData['step1_general']['questions']['employees_count']['options'] as $value => $label) { ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                </div>
                
                <!-- IT Budget -->
                <div class="audit-form-group">
                    <label class="audit-form-label required"><?php echo $langs->trans('ITBudgetQuestion'); ?></label>
                    <select name="it_budget" class="audit-form-control" required>
                        <option value="">-- <?php echo $langs->trans('Select'); ?> --</option>
                        <?php foreach ($questionnaireData['step1_general']['questions']['it_budget']['options'] as $value => $label) { ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                </div>
                
                <!-- Main Objectives -->
                <div class="audit-form-group">
                    <label class="audit-form-label required"><?php echo $langs->trans('MainObjectivesQuestion'); ?></label>
                    <div class="audit-checkbox-group">
                        <?php foreach ($questionnaireData['step1_general']['questions']['main_objectives']['options'] as $value => $label) { ?>
                        <div class="audit-checkbox-item">
                            <input type="checkbox" name="main_objectives[]" value="<?php echo $value; ?>" id="main_objectives_<?php echo $value; ?>">
                            <label for="main_objectives_<?php echo $value; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Step 2: Digital Maturity -->
        <div id="step2" class="audit-step-content" style="display: none;">
            <h2 class="audit-step-title"><?php echo $langs->trans('Step2Maturity'); ?></h2>
            <p class="audit-step-description"><?php echo $questionnaireData['step2_maturite']['description']; ?></p>
            
            <form id="step2-form">
                <?php foreach ($questionnaireData['step2_maturite']['questions'] as $questionId => $question) { ?>
                <div class="audit-form-group">
                    <label class="audit-form-label <?php echo $question['required'] ? 'required' : ''; ?>"><?php echo $question['label']; ?></label>
                    
                    <?php if ($question['type'] == 'radio') { ?>
                    <div class="audit-radio-group">
                        <?php foreach ($question['options'] as $value => $label) { ?>
                        <div class="audit-radio-item">
                            <input type="radio" 
                                   name="<?php echo $questionId; ?>" 
                                   value="<?php echo $value; ?>" 
                                   id="<?php echo $questionId.'_'.$value; ?>"
                                   <?php echo $question['required'] ? 'required' : ''; ?>
                                   <?php if (isset($question['score_mapping'])) echo 'data-score-mapping="'.htmlspecialchars(json_encode($question['score_mapping'])).'"'; ?>>
                            <label for="<?php echo $questionId.'_'.$value; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            </form>
        </div>
        
        <!-- Step 3: Cybersecurity -->
        <div id="step3" class="audit-step-content" style="display: none;">
            <h2 class="audit-step-title"><?php echo $langs->trans('Step3Cybersecurity'); ?></h2>
            <p class="audit-step-description"><?php echo $questionnaireData['step3_cybersecurite']['description']; ?></p>
            
            <form id="step3-form">
                <?php foreach ($questionnaireData['step3_cybersecurite']['questions'] as $questionId => $question) { ?>
                <div class="audit-form-group">
                    <label class="audit-form-label <?php echo $question['required'] ? 'required' : ''; ?>"><?php echo $question['label']; ?></label>
                    
                    <?php if ($question['type'] == 'radio') { ?>
                    <div class="audit-radio-group">
                        <?php foreach ($question['options'] as $value => $label) { ?>
                        <div class="audit-radio-item">
                            <input type="radio" 
                                   name="<?php echo $questionId; ?>" 
                                   value="<?php echo $value; ?>" 
                                   id="<?php echo $questionId.'_'.$value; ?>"
                                   <?php echo $question['required'] ? 'required' : ''; ?>
                                   <?php if (isset($question['score_mapping'])) echo 'data-score-mapping="'.htmlspecialchars(json_encode($question['score_mapping'])).'"'; ?>>
                            <label for="<?php echo $questionId.'_'.$value; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            </form>
        </div>
        
        <!-- Step 4: Cloud -->
        <div id="step4" class="audit-step-content" style="display: none;">
            <h2 class="audit-step-title"><?php echo $langs->trans('Step4Cloud'); ?></h2>
            <p class="audit-step-description"><?php echo $questionnaireData['step4_cloud']['description']; ?></p>
            
            <form id="step4-form">
                <?php foreach ($questionnaireData['step4_cloud']['questions'] as $questionId => $question) { ?>
                <div class="audit-form-group">
                    <label class="audit-form-label <?php echo $question['required'] ? 'required' : ''; ?>"><?php echo $question['label']; ?></label>
                    
                    <?php if ($question['type'] == 'radio') { ?>
                    <div class="audit-radio-group">
                        <?php foreach ($question['options'] as $value => $label) { ?>
                        <div class="audit-radio-item">
                            <input type="radio" 
                                   name="<?php echo $questionId; ?>" 
                                   value="<?php echo $value; ?>" 
                                   id="<?php echo $questionId.'_'.$value; ?>"
                                   <?php echo $question['required'] ? 'required' : ''; ?>
                                   <?php if (isset($question['score_mapping'])) echo 'data-score-mapping="'.htmlspecialchars(json_encode($question['score_mapping'])).'"'; ?>>
                            <label for="<?php echo $questionId.'_'.$value; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            </form>
        </div>
        
        <!-- Step 5: Automation -->
        <div id="step5" class="audit-step-content" style="display: none;">
            <h2 class="audit-step-title"><?php echo $langs->trans('Step5Automation'); ?></h2>
            <p class="audit-step-description"><?php echo $questionnaireData['step5_automatisation']['description']; ?></p>
            
            <form id="step5-form">
                <?php foreach ($questionnaireData['step5_automatisation']['questions'] as $questionId => $question) { ?>
                <div class="audit-form-group">
                    <label class="audit-form-label <?php echo $question['required'] ? 'required' : ''; ?>"><?php echo $question['label']; ?></label>
                    
                    <?php if ($question['type'] == 'radio') { ?>
                    <div class="audit-radio-group">
                        <?php foreach ($question['options'] as $value => $label) { ?>
                        <div class="audit-radio-item">
                            <input type="radio" 
                                   name="<?php echo $questionId; ?>" 
                                   value="<?php echo $value; ?>" 
                                   id="<?php echo $questionId.'_'.$value; ?>"
                                   <?php echo $question['required'] ? 'required' : ''; ?>
                                   <?php if (isset($question['score_mapping'])) echo 'data-score-mapping="'.htmlspecialchars(json_encode($question['score_mapping'])).'"'; ?>>
                            <label for="<?php echo $questionId.'_'.$value; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } elseif ($question['type'] == 'checkbox') { ?>
                    <div class="audit-checkbox-group">
                        <?php foreach ($question['options'] as $value => $label) { ?>
                        <div class="audit-checkbox-item">
                            <input type="checkbox" 
                                   name="<?php echo $questionId; ?>[]" 
                                   value="<?php echo $value; ?>" 
                                   id="<?php echo $questionId.'_'.$value; ?>">
                            <label for="<?php echo $questionId.'_'.$value; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } elseif ($question['type'] == 'select') { ?>
                    <select name="<?php echo $questionId; ?>" class="audit-form-control" <?php echo $question['required'] ? 'required' : ''; ?>>
                        <option value="">-- <?php echo $langs->trans('Select'); ?> --</option>
                        <?php foreach ($question['options'] as $value => $label) { ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                    <?php } ?>
                </div>
                <?php } ?>
            </form>
        </div>
        
        <!-- Step 6: Synthesis -->
        <div id="step6" class="audit-step-content" style="display: none;">
            <h2 class="audit-step-title"><?php echo $langs->trans('Step6Synthesis'); ?></h2>
            <p class="audit-step-description">Récapitulatif de votre audit et recommandations personnalisées</p>
            
            <!-- Scores display -->
            <div class="audit-score-container">
                <div class="audit-score-card">
                    <div class="audit-score-title"><?php echo $langs->trans('GlobalScore'); ?></div>
                    <div id="score-global" class="audit-score-value">0%</div>
                </div>
                <div class="audit-score-card">
                    <div class="audit-score-title"><?php echo $langs->trans('MaturityScore'); ?></div>
                    <div id="score-maturite" class="audit-score-value">0%</div>
                </div>
                <div class="audit-score-card">
                    <div class="audit-score-title"><?php echo $langs->trans('CybersecurityScore'); ?></div>
                    <div id="score-cybersecurite" class="audit-score-value">0%</div>
                </div>
                <div class="audit-score-card">
                    <div class="audit-score-title"><?php echo $langs->trans('CloudScore'); ?></div>
                    <div id="score-cloud" class="audit-score-value">0%</div>
                </div>
                <div class="audit-score-card">
                    <div class="audit-score-title"><?php echo $langs->trans('AutomationScore'); ?></div>
                    <div id="score-automatisation" class="audit-score-value">0%</div>
                </div>
            </div>
            
            <!-- Radar chart -->
            <div class="audit-radar-container">
                <h3 class="audit-radar-title">Graphique de maturité</h3>
                <div id="radar-chart"></div>
            </div>
            
            <!-- Recommendations -->
            <div class="audit-recommendations">
                <h3><?php echo $langs->trans('RecommendedSolutions'); ?></h3>
                <div id="recommendations-container"></div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="audit-navigation">
        <button type="button" class="audit-btn audit-btn-secondary audit-btn-prev" style="display: none;">
            <?php echo $langs->trans('Previous'); ?>
        </button>
        
        <div class="audit-navigation-center">
            <span class="audit-step-indicator">Étape <span id="current-step">1</span> sur 6</span>
        </div>
        
        <button type="button" class="audit-btn audit-btn-primary audit-btn-next">
            <?php echo $langs->trans('Next'); ?>
        </button>
        
        <button type="button" class="audit-btn audit-btn-success audit-btn-finish" style="display: none;">
            <?php echo $langs->trans('Finish'); ?>
        </button>
    </div>
</div>

<script>
// Update step indicator
document.addEventListener('DOMContentLoaded', function() {
    const wizard = window.auditWizard;
    if (wizard) {
        const updateStepIndicator = function() {
            const indicator = document.getElementById('current-step');
            if (indicator) {
                indicator.textContent = wizard.currentStep;
            }
        };
        
        // Override showStep to update indicator
        const originalShowStep = wizard.showStep;
        wizard.showStep = function(stepNumber) {
            originalShowStep.call(this, stepNumber);
            updateStepIndicator();
        };
        
        updateStepIndicator();
    }
});
</script>

<?php

llxFooter();
$db->close();
?>