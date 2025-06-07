<?php
/**
 * Test étape par étape du wizard AuditDigital
 * À placer dans /usr/share/dolibarr/htdocs/custom/auditdigital/
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Étape par Étape - Wizard AuditDigital</h1>";

// Étape 1: Test d'inclusion
echo "<h2>Étape 1: Inclusion Dolibarr</h2>";
$res = 0;
$paths_to_try = array(
    "../../main.inc.php",      // Chemin correct selon votre test
    "../../../main.inc.php",
    "../main.inc.php",
    "../../../../main.inc.php",
    "../../../../../main.inc.php"
);

foreach ($paths_to_try as $path) {
    echo "Test: $path - ";
    if (file_exists($path)) {
        echo "Existe - ";
        try {
            $res = @include $path;
            if ($res) {
                echo "✅ Inclusion réussie<br>";
                break;
            } else {
                echo "❌ Inclusion échouée<br>";
            }
        } catch (Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Non trouvé<br>";
    }
}

if (!$res) {
    die("❌ Impossible de charger Dolibarr");
}

echo "✅ DOL_DOCUMENT_ROOT: " . DOL_DOCUMENT_ROOT . "<br>";

// Étape 2: Test du module activé
echo "<h2>Étape 2: Vérification Module</h2>";
if (function_exists('isModEnabled')) {
    if (isModEnabled('auditdigital')) {
        echo "✅ Module AuditDigital activé<br>";
    } else {
        echo "❌ Module AuditDigital non activé<br>";
        die("Module requis non activé");
    }
} else {
    echo "❌ Fonction isModEnabled non disponible<br>";
}

// Étape 3: Test des classes Dolibarr
echo "<h2>Étape 3: Classes Dolibarr</h2>";
$dolibarr_classes = array(
    DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php' => 'FormCompany',
    DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php' => 'FormProjet'
);

foreach ($dolibarr_classes as $file => $class) {
    echo "Test $class: ";
    if (file_exists($file)) {
        try {
            require_once $file;
            if (class_exists($class)) {
                echo "✅ OK<br>";
            } else {
                echo "❌ Classe non trouvée après inclusion<br>";
            }
        } catch (Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Fichier non trouvé<br>";
    }
}

// Étape 4: Test des classes AuditDigital
echo "<h2>Étape 4: Classes AuditDigital</h2>";
$audit_classes = array(
    DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php' => 'Audit',
    DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php' => 'Questionnaire',
    DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php' => 'SolutionLibrary'
);

foreach ($audit_classes as $file => $class) {
    echo "Test $class: ";
    if (file_exists($file)) {
        try {
            require_once $file;
            if (class_exists($class)) {
                echo "✅ OK<br>";
            } else {
                echo "❌ Classe non trouvée après inclusion<br>";
            }
        } catch (Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Fichier non trouvé<br>";
    }
}

// Étape 5: Test des traductions
echo "<h2>Étape 5: Traductions</h2>";
if (isset($langs)) {
    echo "Objet \$langs disponible: ✅<br>";
    $langs->loadLangs(array("main", "companies", "projects"));
    echo "Traductions de base chargées: ✅<br>";
    
    $lang_file = DOL_DOCUMENT_ROOT.'/custom/auditdigital/langs/'.$langs->defaultlang.'/auditdigital.lang';
    echo "Fichier de traduction: $lang_file<br>";
    if (file_exists($lang_file)) {
        echo "Fichier de traduction existe: ✅<br>";
        try {
            $langs->load("auditdigital@auditdigital");
            echo "Traductions AuditDigital chargées: ✅<br>";
        } catch (Exception $e) {
            echo "❌ Erreur chargement traductions: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Fichier de traduction non trouvé<br>";
    }
} else {
    echo "❌ Objet \$langs non disponible<br>";
}

// Étape 6: Test des paramètres
echo "<h2>Étape 6: Paramètres</h2>";
if (function_exists('GETPOST')) {
    echo "Fonction GETPOST disponible: ✅<br>";
    $action = GETPOST('action', 'aZ09');
    $step = GETPOST('step', 'int');
    $id = GETPOST('id', 'int');
    echo "Paramètres récupérés: action=$action, step=$step, id=$id ✅<br>";
} else {
    echo "❌ Fonction GETPOST non disponible<br>";
}

// Étape 7: Test des permissions
echo "<h2>Étape 7: Permissions</h2>";
if (isset($user)) {
    echo "Objet \$user disponible: ✅<br>";
    echo "User ID: " . $user->id . "<br>";
    echo "User login: " . $user->login . "<br>";
    
    if (isset($user->rights->auditdigital->audit->write)) {
        echo "Permissions AuditDigital définies: ✅<br>";
        if ($user->rights->auditdigital->audit->write) {
            echo "Permission d'écriture: ✅<br>";
        } else {
            echo "❌ Permission d'écriture refusée<br>";
        }
    } else {
        echo "⚠️ Permissions AuditDigital non définies, utilisation du fallback<br>";
        if ($user->id && $user->socid == 0) {
            echo "Permissions fallback OK: ✅<br>";
        } else {
            echo "❌ Permissions fallback refusées<br>";
        }
    }
} else {
    echo "❌ Objet \$user non disponible<br>";
}

// Étape 8: Test de création d'objets
echo "<h2>Étape 8: Création d'Objets</h2>";
if (isset($db)) {
    echo "Objet \$db disponible: ✅<br>";
    
    try {
        $formcompany = new FormCompany($db);
        echo "FormCompany créé: ✅<br>";
    } catch (Exception $e) {
        echo "❌ Erreur FormCompany: " . $e->getMessage() . "<br>";
    }
    
    if (isModEnabled('project')) {
        echo "Module Projet activé: ✅<br>";
        try {
            $formproject = new FormProjets($db);
            echo "FormProjets créé: ✅<br>";
        } catch (Exception $e) {
            echo "❌ Erreur FormProjets: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "⚠️ Module Projet non activé<br>";
    }
    
    try {
        $audit = new Audit($db);
        echo "Audit créé: ✅<br>";
    } catch (Exception $e) {
        echo "❌ Erreur Audit: " . $e->getMessage() . "<br>";
    }
    
    try {
        $questionnaire = new Questionnaire($db);
        echo "Questionnaire créé: ✅<br>";
    } catch (Exception $e) {
        echo "❌ Erreur Questionnaire: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ Objet \$db non disponible<br>";
}

// Étape 9: Test de l'interface
echo "<h2>Étape 9: Test Interface</h2>";
echo "Simulation du début du wizard...<br>";

try {
    // Simuler le début du wizard
    if (!$step) $step = 1;
    echo "Étape actuelle: $step ✅<br>";
    
    // Test de génération HTML basique
    echo "Test génération HTML...<br>";
    ob_start();
    echo '<div class="wizard-container">';
    echo '<h3>Test Wizard Interface</h3>';
    echo '<p>Si vous voyez ceci, l\'interface peut se générer.</p>';
    echo '</div>';
    $html_output = ob_get_clean();
    
    if (!empty($html_output)) {
        echo "Génération HTML: ✅<br>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo $html_output;
        echo "</div>";
    } else {
        echo "❌ Problème génération HTML<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur interface: " . $e->getMessage() . "<br>";
}

echo "<h2>Conclusion</h2>";
echo "<p><strong>Si tous les tests ci-dessus sont OK (✅), le wizard devrait fonctionner.</strong></p>";
echo "<p>Si vous voyez des ❌, corrigez ces problèmes avant de tester le wizard.</p>";

echo "<h3>Prochaines étapes :</h3>";
echo "<ol>";
echo "<li>Si tout est OK, testez le wizard : <a href='wizard/index.php'>wizard/index.php</a></li>";
echo "<li>Si erreur, vérifiez les logs : <code>sudo tail -f /var/log/apache2/error.log</code></li>";
echo "<li>Pour debug avancé, activez display_errors dans PHP</li>";
echo "</ol>";

echo "<h3>Informations de Debug :</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "<br>";
echo "Error Reporting: " . error_reporting() . "<br>";

?>