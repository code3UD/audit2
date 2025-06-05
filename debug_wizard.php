<?php
/**
 * Script de diagnostic pour le wizard AuditDigital
 * À placer dans /usr/share/dolibarr/htdocs/custom/auditdigital/
 */

echo "<h1>Diagnostic Wizard AuditDigital</h1>";

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>1. Test des chemins d'inclusion</h2>";

$paths_to_test = array(
    "../main.inc.php",
    "../../main.inc.php", 
    "../../../main.inc.php",
    "../../../../main.inc.php",
    "../../../../../main.inc.php"
);

foreach ($paths_to_test as $path) {
    $full_path = __DIR__ . "/wizard/" . $path;
    echo "Test: $path → $full_path<br>";
    if (file_exists($full_path)) {
        echo "✅ <strong>TROUVÉ</strong>: $path<br>";
        break;
    } else {
        echo "❌ Non trouvé: $path<br>";
    }
}

echo "<h2>2. Recherche de main.inc.php</h2>";

// Rechercher main.inc.php depuis le répertoire actuel
$current_dir = __DIR__;
echo "Répertoire actuel: $current_dir<br>";

for ($i = 1; $i <= 6; $i++) {
    $test_path = $current_dir . str_repeat('/..', $i) . '/main.inc.php';
    $real_path = realpath($test_path);
    echo "Niveau $i: $test_path<br>";
    if ($real_path && file_exists($real_path)) {
        echo "✅ <strong>TROUVÉ</strong>: $real_path<br>";
        break;
    } else {
        echo "❌ Non trouvé<br>";
    }
}

echo "<h2>3. Structure des répertoires</h2>";

// Afficher la structure
$base_dir = dirname(__DIR__);
echo "Répertoire de base: $base_dir<br>";

function listDirectory($dir, $level = 0) {
    if ($level > 3) return; // Limiter la profondeur
    
    $items = @scandir($dir);
    if ($items === false) return;
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $full_path = $dir . '/' . $item;
        $indent = str_repeat('&nbsp;&nbsp;', $level * 2);
        
        if (is_dir($full_path)) {
            echo $indent . "📁 $item/<br>";
            if ($level < 2) {
                listDirectory($full_path, $level + 1);
            }
        } else {
            echo $indent . "📄 $item<br>";
        }
    }
}

listDirectory($base_dir);

echo "<h2>4. Test d'inclusion manuelle</h2>";

// Tenter d'inclure main.inc.php manuellement
$possible_paths = array(
    '/usr/share/dolibarr/htdocs/main.inc.php',
    '/var/lib/dolibarr/htdocs/main.inc.php',
    '/usr/dolibarr/htdocs/main.inc.php',
    '/opt/dolibarr/htdocs/main.inc.php'
);

foreach ($possible_paths as $path) {
    echo "Test inclusion: $path<br>";
    if (file_exists($path)) {
        echo "✅ Fichier existe<br>";
        try {
            include_once $path;
            echo "✅ <strong>INCLUSION RÉUSSIE</strong><br>";
            echo "DOL_DOCUMENT_ROOT: " . (defined('DOL_DOCUMENT_ROOT') ? DOL_DOCUMENT_ROOT : 'Non défini') . "<br>";
            break;
        } catch (Exception $e) {
            echo "❌ Erreur d'inclusion: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Fichier non trouvé<br>";
    }
}

echo "<h2>5. Test des classes AuditDigital</h2>";

if (defined('DOL_DOCUMENT_ROOT')) {
    $classes_to_test = array(
        DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php',
        DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php',
        DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php'
    );
    
    foreach ($classes_to_test as $class_file) {
        echo "Test classe: $class_file<br>";
        if (file_exists($class_file)) {
            echo "✅ Fichier existe<br>";
            try {
                require_once $class_file;
                echo "✅ Inclusion réussie<br>";
            } catch (Exception $e) {
                echo "❌ Erreur: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Fichier non trouvé<br>";
        }
    }
} else {
    echo "❌ DOL_DOCUMENT_ROOT non défini<br>";
}

echo "<h2>6. Informations PHP</h2>";
echo "Version PHP: " . phpversion() . "<br>";
echo "Memory limit: " . ini_get('memory_limit') . "<br>";
echo "Max execution time: " . ini_get('max_execution_time') . "<br>";

echo "<h2>7. Test de création d'objets</h2>";

if (defined('DOL_DOCUMENT_ROOT') && class_exists('Audit')) {
    try {
        // Simuler une connexion DB simple pour le test
        $db = new stdClass();
        $audit = new Audit($db);
        echo "✅ Objet Audit créé avec succès<br>";
    } catch (Exception $e) {
        echo "❌ Erreur création Audit: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>8. Logs d'erreur récents</h2>";

$log_files = array(
    '/var/log/apache2/error.log',
    '/var/log/php_errors.log',
    '/var/log/dolibarr/dolibarr.log'
);

foreach ($log_files as $log_file) {
    if (file_exists($log_file) && is_readable($log_file)) {
        echo "<h3>$log_file (dernières 10 lignes)</h3>";
        $lines = file($log_file);
        $recent_lines = array_slice($lines, -10);
        echo "<pre>" . htmlspecialchars(implode('', $recent_lines)) . "</pre>";
    }
}

echo "<hr>";
echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Copiez ce fichier dans /usr/share/dolibarr/htdocs/custom/auditdigital/debug_wizard.php</li>";
echo "<li>Accédez à http://votre-dolibarr/custom/auditdigital/debug_wizard.php</li>";
echo "<li>Analysez les résultats pour identifier le problème</li>";
echo "</ol>";
?>