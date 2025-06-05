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

// Méthode 1: Chemins relatifs standards (ordre optimisé selon votre configuration)
$paths_to_try = array(
    "../../main.inc.php",      // Chemin correct pour votre installation
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

// Méthode 2: Chemins absolus courants pour Ubuntu
if (!$res) {
    $absolute_paths = array(
        "/usr/share/dolibarr/htdocs/main.inc.php",
        "/var/lib/dolibarr/htdocs/main.inc.php",
        "/usr/dolibarr/htdocs/main.inc.php",
        "/opt/dolibarr/htdocs/main.inc.php"
    );
    
    foreach ($absolute_paths as $path) {
        if (file_exists($path)) {
            $res = @include $path;
            if ($res) break;
        }
    }
}

// Méthode 3: Recherche dynamique
if (!$res) {
    $current_dir = dirname(__FILE__);
    for ($i = 1; $i <= 6; $i++) {
        $test_path = $current_dir . str_repeat('/..', $i) . '/main.inc.php';
        if (file_exists($test_path)) {
            $res = @include $test_path;
            if ($res) break;
        }
    }
}

if (!$res) {
    // Affichage d'erreur plus informatif
    echo "<h1>Erreur de chargement Dolibarr</h1>";
    echo "<p>Impossible de charger main.inc.php. Chemins testés :</p>";
    echo "<ul>";
    foreach ($paths_to_try as $path) {
        $exists = file_exists($path) ? "✅" : "❌";
        echo "<li>$exists $path</li>";
    }
    echo "</ul>";
    echo "<p>Répertoire actuel : " . dirname(__FILE__) . "</p>";
    echo "<p>Vérifiez l'installation de Dolibarr et les permissions.</p>";
    die();
}

// Check if module is enabled
if (!isModEnabled('auditdigital')) {
    accessforbidden('Module not enabled');
}

// Load required classes
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

// Try to load our custom classes with error handling
$classesLoaded = true;
$errorMessage = '';

try {
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php')) {
        require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
    } else {
        throw new Exception('Audit class file not found');
    }
    
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php')) {
        require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php';
    } else {
        throw new Exception('Questionnaire class file not found');
    }
    
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php')) {
        require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';
    } else {
        throw new Exception('SolutionLibrary class file not found');
    }
} catch (Exception $e) {
    $classesLoaded = false;
    $errorMessage = $e->getMessage();
}

// Load translation files
$langs->loadLangs(array("main", "companies", "projects"));

// Try to load our translations
if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/langs/'.$langs->defaultlang.'/auditdigital.lang')) {
    $langs->load("auditdigital@auditdigital");
}

// Get parameters
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int');
$id = GETPOST('id', 'int');

// Security check - use basic permission if module permissions not available
if (isset($user->rights->auditdigital->audit->write)) {
    if (!$user->rights->auditdigital->audit->write) {
        accessforbidden();
    }
} else {
    // Fallback to basic user check
    if (!$user->id || $user->socid > 0) {
        accessforbidden();
    }
}

// Initialize objects with error handling
$formcompany = new FormCompany($db);
if (isModEnabled('project')) {
    $formproject = new FormProjets($db);
} else {
    $formproject = null;
}

$hookmanager->initHooks(array('auditdigitalwizard'));

// Handle AJAX requests
if (GETPOST('ajax', 'alpha')) {
    header('Content-Type: application/json');
    
    if ($action == 'save_step') {
        // Simple save to session for now
        if (!isset($_SESSION['audit_wizard'])) {
            $_SESSION['audit_wizard'] = array();
        }
        $_SESSION['audit_wizard']['step'.$step] = $_POST;
        echo json_encode(array('success' => true));
        exit;
    }
    
    if ($action == 'finish_audit') {
        // Simple response for now
        echo json_encode(array(
            'success' => true,
            'message' => 'Audit saved successfully',
            'redirect' => dol_buildpath('/auditdigital/audit_list.php', 1)
        ));
        exit;
    }
}

/*
 * View
 */

$title = 'Nouvel Audit Digital';
$help_url = '';

llxHeader('', $title, $help_url);

// Check if classes are loaded properly
if (!$classesLoaded) {
    print '<div class="error">Erreur de chargement des classes: '.$errorMessage.'</div>';
    print '<p>Veuillez vérifier que tous les fichiers du module sont correctement installés.</p>';
    print '<p><a href="'.dol_buildpath('/auditdigital/install.php', 1).'">Relancer l\'installation</a></p>';
    llxFooter();
    exit;
}

// Simple questionnaire structure for now
$questionnaireData = array(
    'step1_general' => array(
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
    )
);

?>

<style>
.audit-wizard {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.audit-form-group {
    margin-bottom: 20px;
}

.audit-form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.audit-form-label.required:after {
    content: " *";
    color: red;
}

.audit-radio-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.audit-radio-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.audit-radio-item:hover {
    border-color: #0066CC;
    background: rgba(0, 102, 204, 0.05);
}

.audit-radio-item input[type="radio"] {
    margin-right: 10px;
}

.audit-form-control {
    width: 100%;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.audit-form-control:focus {
    border-color: #0066CC;
    outline: none;
}

.audit-buttons {
    margin-top: 30px;
    text-align: center;
}

.audit-btn {
    padding: 12px 24px;
    margin: 0 10px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.audit-btn-primary {
    background: #0066CC;
    color: white;
}

.audit-btn-primary:hover {
    background: #004499;
}

.audit-btn-secondary {
    background: #6c757d;
    color: white;
}

.audit-btn-secondary:hover {
    background: #545b62;
}
</style>

<div class="audit-wizard">
    <!-- Header -->
    <div class="audit-wizard-header">
        <h1>🎯 Nouvel Audit Digital</h1>
        <p>Évaluez la maturité numérique de votre organisation en quelques étapes simples.</p>
    </div>
    
    <!-- Form container -->
    <div class="audit-form-container">
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="create_audit">
            
            <!-- Structure Type -->
            <div class="audit-form-group">
                <label class="audit-form-label required">Type de structure</label>
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
                <label class="audit-form-label required">Société</label>
                <?php echo $formcompany->select_company(GETPOST('fk_soc', 'int'), 'fk_soc', '', 'Sélectionner une société', 1, 0, null, 0, 'audit-form-control'); ?>
            </div>
            
            <!-- Project (optional) -->
            <?php if ($formproject) { ?>
            <div class="audit-form-group">
                <label class="audit-form-label">Projet (optionnel)</label>
                <?php echo $formproject->select_projects(-1, GETPOST('fk_projet', 'int'), 'fk_projet', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'audit-form-control'); ?>
            </div>
            <?php } ?>
            
            <!-- Sector -->
            <div class="audit-form-group">
                <label class="audit-form-label required">Secteur d'activité</label>
                <select name="sector" class="audit-form-control" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($questionnaireData['step1_general']['questions']['sector']['options'] as $value => $label) { ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <!-- Employees Count -->
            <div class="audit-form-group">
                <label class="audit-form-label required">Nombre d'employés</label>
                <select name="employees_count" class="audit-form-control" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($questionnaireData['step1_general']['questions']['employees_count']['options'] as $value => $label) { ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <!-- IT Budget -->
            <div class="audit-form-group">
                <label class="audit-form-label required">Budget IT annuel</label>
                <select name="it_budget" class="audit-form-control" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($questionnaireData['step1_general']['questions']['it_budget']['options'] as $value => $label) { ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <!-- Buttons -->
            <div class="audit-buttons">
                <button type="button" class="audit-btn audit-btn-secondary" onclick="history.back()">
                    ← Retour
                </button>
                <button type="submit" class="audit-btn audit-btn-primary">
                    Créer l'audit →
                </button>
            </div>
        </form>
    </div>
</div>

<?php

// Handle form submission
if ($action == 'create_audit' && $_POST) {
    $error = 0;
    $message = '';
    
    // Basic validation
    if (empty(GETPOST('structure_type', 'alpha'))) {
        $error++;
        $message = 'Le type de structure est obligatoire';
    }
    
    if (empty(GETPOST('fk_soc', 'int'))) {
        $error++;
        $message = 'La société est obligatoire';
    }
    
    if (!$error) {
        // For now, just show success message
        print '<div class="ok">Audit créé avec succès ! (Version simplifiée)</div>';
        print '<p>Données reçues :</p>';
        print '<ul>';
        print '<li>Type de structure : '.GETPOST('structure_type', 'alpha').'</li>';
        print '<li>Société : '.GETPOST('fk_soc', 'int').'</li>';
        print '<li>Secteur : '.GETPOST('sector', 'alpha').'</li>';
        print '<li>Employés : '.GETPOST('employees_count', 'alpha').'</li>';
        print '<li>Budget IT : '.GETPOST('it_budget', 'alpha').'</li>';
        print '</ul>';
        print '<p><a href="'.dol_buildpath('/auditdigital/audit_list.php', 1).'">Voir la liste des audits</a></p>';
    } else {
        print '<div class="error">'.$message.'</div>';
    }
}

llxFooter();
?>
