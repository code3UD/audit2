<?php
/**
 * Version minimale du wizard pour identifier le problème exact
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Minimal Wizard</h1>";

// Étape 1: Inclusion Dolibarr
$res = 0;
$paths_to_try = array("../../main.inc.php", "../../../main.inc.php", "../main.inc.php");

foreach ($paths_to_try as $path) {
    if (!$res && file_exists($path)) {
        $res = @include $path;
        if ($res) break;
    }
}

if (!$res) {
    die("Include of main fails");
}

echo "✅ Dolibarr chargé<br>";

// Étape 2: Module
if (!isModEnabled('auditdigital')) {
    die("Module not enabled");
}

echo "✅ Module activé<br>";

// Étape 3: Classes de base
try {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
    echo "✅ FormCompany inclus<br>";
} catch (Exception $e) {
    die("Erreur FormCompany: " . $e->getMessage());
}

// Étape 4: Classes AuditDigital
try {
    require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
    echo "✅ Audit inclus<br>";
    
    require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php';
    echo "✅ Questionnaire inclus<br>";
    
    require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';
    echo "✅ SolutionLibrary inclus<br>";
} catch (Exception $e) {
    die("Erreur classes AuditDigital: " . $e->getMessage());
}

// Étape 5: Traductions
$langs->loadLangs(array("main", "companies", "projects"));
echo "✅ Traductions chargées<br>";

// Étape 6: Paramètres
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int');
echo "✅ Paramètres récupérés<br>";

// Étape 7: Permissions
if (!$user->id || $user->socid > 0) {
    die("Permission refusée");
}
echo "✅ Permissions OK<br>";

// Étape 8: Objets
try {
    $formcompany = new FormCompany($db);
    echo "✅ FormCompany créé<br>";
} catch (Exception $e) {
    die("Erreur FormCompany: " . $e->getMessage());
}

// Étape 9: Test FormProjets (problème potentiel)
echo "<h2>Test FormProjets</h2>";
try {
    if (isModEnabled('project')) {
        echo "Module projet activé<br>";
        
        // Vérifier si la classe existe
        if (class_exists('FormProjets')) {
            echo "Classe FormProjets existe<br>";
            $formproject = new FormProjets($db);
            echo "✅ FormProjets créé<br>";
        } else {
            echo "❌ Classe FormProjets n'existe pas<br>";
            echo "Classes disponibles: " . implode(', ', get_declared_classes()) . "<br>";
        }
    } else {
        echo "Module projet non activé<br>";
        $formproject = null;
    }
} catch (Exception $e) {
    echo "❌ Erreur FormProjets: " . $e->getMessage() . "<br>";
    $formproject = null;
}

// Étape 10: Classes AuditDigital
try {
    $audit = new Audit($db);
    echo "✅ Audit créé<br>";
    
    $questionnaire = new Questionnaire($db);
    echo "✅ Questionnaire créé<br>";
    
    $solutionLibrary = new SolutionLibrary($db);
    echo "✅ SolutionLibrary créé<br>";
} catch (Exception $e) {
    die("Erreur objets AuditDigital: " . $e->getMessage());
}

// Étape 11: Test llxHeader
echo "<h2>Test llxHeader</h2>";
try {
    $title = 'Test Wizard Minimal';
    llxHeader('', $title);
    echo "✅ llxHeader appelé<br>";
} catch (Exception $e) {
    die("Erreur llxHeader: " . $e->getMessage());
}

?>

<div class="fiche">
    <div class="fichetitle">
        <h1>✅ Test Minimal Réussi !</h1>
    </div>
    
    <div class="tabBar">
        <p>Si vous voyez cette page, tous les composants de base fonctionnent.</p>
        
        <h3>Informations :</h3>
        <ul>
            <li><strong>FormCompany :</strong> <?php echo isset($formcompany) ? 'OK' : 'Erreur'; ?></li>
            <li><strong>FormProjets :</strong> <?php echo isset($formproject) ? 'OK' : 'Non créé'; ?></li>
            <li><strong>Audit :</strong> <?php echo isset($audit) ? 'OK' : 'Erreur'; ?></li>
            <li><strong>Questionnaire :</strong> <?php echo isset($questionnaire) ? 'OK' : 'Erreur'; ?></li>
            <li><strong>SolutionLibrary :</strong> <?php echo isset($solutionLibrary) ? 'OK' : 'Erreur'; ?></li>
        </ul>
        
        <h3>Prochaines étapes :</h3>
        <ol>
            <li><a href="simple.php">Tester le wizard simple</a></li>
            <li><a href="index.php">Tester le wizard complet</a></li>
        </ol>
    </div>
</div>

<?php

llxFooter();
$db->close();
?>