<?php
/**
 * Test pour identifier la classe de formulaire des projets
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclusion Dolibarr
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

echo "<h1>Test Classe Formulaire Projets</h1>";

echo "<h2>1. Vérification du module Projets</h2>";
if (isModEnabled('project')) {
    echo "✅ Module Projets activé<br>";
} else {
    echo "❌ Module Projets non activé<br>";
    die();
}

echo "<h2>2. Recherche des fichiers de formulaire</h2>";
$projectFiles = array(
    DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php',
    DOL_DOCUMENT_ROOT.'/core/class/html.formproject.class.php',
    DOL_DOCUMENT_ROOT.'/core/class/html.formprojets.class.php'
);

foreach ($projectFiles as $file) {
    echo "Test: " . basename($file) . " - ";
    if (file_exists($file)) {
        echo "✅ Existe<br>";
        
        // Lire le contenu pour trouver la classe
        $content = file_get_contents($file);
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            echo "&nbsp;&nbsp;→ Classe trouvée: <strong>" . $matches[1] . "</strong><br>";
        }
    } else {
        echo "❌ Non trouvé<br>";
    }
}

echo "<h2>3. Test d'inclusion et de création</h2>";

// Tester l'inclusion du fichier qui existe
$projectClassFile = null;
$projectClassName = null;

foreach ($projectFiles as $file) {
    if (file_exists($file)) {
        $projectClassFile = $file;
        require_once $file;
        
        // Extraire le nom de la classe
        $content = file_get_contents($file);
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $projectClassName = $matches[1];
        }
        break;
    }
}

if ($projectClassFile && $projectClassName) {
    echo "Fichier inclus: " . basename($projectClassFile) . "<br>";
    echo "Classe détectée: $projectClassName<br>";
    
    // Tester la création
    if (class_exists($projectClassName)) {
        echo "Classe existe: ✅<br>";
        try {
            $formproject = new $projectClassName($db);
            echo "Objet créé: ✅<br>";
            echo "Type d'objet: " . get_class($formproject) . "<br>";
        } catch (Exception $e) {
            echo "❌ Erreur création: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Classe n'existe pas après inclusion<br>";
    }
} else {
    echo "❌ Aucun fichier de formulaire projet trouvé<br>";
}

echo "<h2>4. Classes disponibles contenant 'Form'</h2>";
$allClasses = get_declared_classes();
$formClasses = array_filter($allClasses, function($class) {
    return stripos($class, 'form') !== false && stripos($class, 'proj') !== false;
});

if (!empty($formClasses)) {
    echo "Classes Form*Proj* trouvées:<br>";
    foreach ($formClasses as $class) {
        echo "- $class<br>";
    }
} else {
    echo "Aucune classe Form*Proj* trouvée<br>";
}

echo "<h2>5. Alternative: Recherche dans tous les fichiers</h2>";
$coreClassDir = DOL_DOCUMENT_ROOT.'/core/class/';
if (is_dir($coreClassDir)) {
    $files = scandir($coreClassDir);
    $projectRelatedFiles = array_filter($files, function($file) {
        return stripos($file, 'proj') !== false && stripos($file, 'form') !== false;
    });
    
    if (!empty($projectRelatedFiles)) {
        echo "Fichiers liés aux projets trouvés:<br>";
        foreach ($projectRelatedFiles as $file) {
            echo "- $file<br>";
        }
    } else {
        echo "Aucun fichier Form*Proj* trouvé dans core/class/<br>";
    }
}

echo "<h2>6. Solution de contournement</h2>";
echo "<p>Si aucune classe de formulaire projet n'est trouvée, le wizard peut fonctionner sans.</p>";
echo "<p>Le formulaire de sélection de projet peut être créé manuellement.</p>";

echo "<h3>Test du wizard sans FormProjets :</h3>";
echo "<a href='index.php'>Tester le wizard principal</a><br>";
echo "<a href='simple.php'>Tester le wizard simple</a><br>";

?>