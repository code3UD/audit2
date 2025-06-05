<?php
/**
 * Version simplifiée du wizard pour test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Dolibarr environment
$res = 0;
$paths_to_try = array(
    "../../main.inc.php",      // Chemin correct selon votre test
    "../../../main.inc.php",
    "../main.inc.php"
);

foreach ($paths_to_try as $path) {
    if (!$res && file_exists($path)) {
        $res = @include $path;
        if ($res) break;
    }
}

if (!$res) {
    die("Include of main fails");
}

// Check if module is enabled
if (!isModEnabled('auditdigital')) {
    accessforbidden('Module not enabled');
}

// Load required classes
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Try to load our custom classes
try {
    require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';
} catch (Exception $e) {
    die("Error loading classes: " . $e->getMessage());
}

// Load translation files
$langs->loadLangs(array("main", "companies", "projects"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int');

// Security check
if (!$user->id || $user->socid > 0) {
    accessforbidden();
}

// Initialize objects
$formcompany = new FormCompany($db);
$audit = new Audit($db);
$questionnaire = new Questionnaire($db);

if (!$step) $step = 1;

/*
 * View
 */
$title = "Wizard Audit Digital - Test Simple";
llxHeader('', $title);

?>

<div class="fiche">
    <div class="fichetitle">
        <h1><?php echo $title; ?></h1>
    </div>
    
    <div class="tabBar">
        <div class="wizard-container">
            <h2>✅ Test Réussi !</h2>
            <p>Si vous voyez cette page, le wizard fonctionne correctement.</p>
            
            <div class="info-box">
                <h3>Informations de Debug :</h3>
                <ul>
                    <li><strong>DOL_DOCUMENT_ROOT:</strong> <?php echo DOL_DOCUMENT_ROOT; ?></li>
                    <li><strong>Version Dolibarr:</strong> <?php echo DOL_VERSION; ?></li>
                    <li><strong>Utilisateur:</strong> <?php echo $user->login; ?> (ID: <?php echo $user->id; ?>)</li>
                    <li><strong>Module AuditDigital:</strong> <?php echo isModEnabled('auditdigital') ? 'Activé' : 'Désactivé'; ?></li>
                    <li><strong>Étape actuelle:</strong> <?php echo $step; ?></li>
                    <li><strong>Action:</strong> <?php echo $action ? $action : 'Aucune'; ?></li>
                </ul>
            </div>
            
            <div class="test-objects">
                <h3>Test des Objets :</h3>
                <ul>
                    <li><strong>FormCompany:</strong> <?php echo isset($formcompany) ? '✅ OK' : '❌ Erreur'; ?></li>
                    <li><strong>Audit:</strong> <?php echo isset($audit) ? '✅ OK' : '❌ Erreur'; ?></li>
                    <li><strong>Questionnaire:</strong> <?php echo isset($questionnaire) ? '✅ OK' : '❌ Erreur'; ?></li>
                </ul>
            </div>
            
            <div class="next-steps">
                <h3>Prochaines Étapes :</h3>
                <p>Maintenant que ce test fonctionne, vous pouvez :</p>
                <ol>
                    <li><a href="index.php">Tester le wizard complet</a></li>
                    <li><a href="../admin/setup.php">Configurer le module</a></li>
                    <li><a href="../test.php">Lancer les tests complets</a></li>
                </ol>
            </div>
            
            <div class="wizard-navigation">
                <a href="simple.php?step=1" class="button">Étape 1</a>
                <a href="simple.php?step=2" class="button">Étape 2</a>
                <a href="simple.php?step=3" class="button">Étape 3</a>
                <a href="index.php" class="button butAction">Wizard Complet</a>
            </div>
        </div>
    </div>
</div>

<style>
.wizard-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.info-box, .test-objects, .next-steps {
    margin: 20px 0;
    padding: 15px;
    background: white;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.wizard-navigation {
    text-align: center;
    margin-top: 30px;
}

.wizard-navigation .button {
    margin: 0 10px;
    padding: 10px 20px;
    text-decoration: none;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #f0f0f0;
    color: #333;
}

.wizard-navigation .button:hover {
    background: #e0e0e0;
}

.wizard-navigation .butAction {
    background: #0066cc;
    color: white;
    border-color: #0066cc;
}
</style>

<?php

llxFooter();
$db->close();
?>