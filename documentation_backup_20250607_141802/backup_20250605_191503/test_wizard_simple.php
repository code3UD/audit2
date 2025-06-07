<?php
/**
 * Test simple du wizard AuditDigital
 * Simule l'environnement Dolibarr pour tester les inclusions
 */

echo "🧪 TEST SIMPLE DU WIZARD AUDITDIGITAL\n";
echo "====================================\n\n";

// Simuler l'environnement Dolibarr minimal
if (!defined('DOL_DOCUMENT_ROOT')) {
    define('DOL_DOCUMENT_ROOT', '/usr/share/dolibarr/htdocs');
}
if (!defined('MAIN_DB_PREFIX')) {
    define('MAIN_DB_PREFIX', 'llx_');
}

$base_path = __DIR__ . '/htdocs/custom/auditdigital';

echo "📁 Chemin de base : $base_path\n\n";

// Test 1: Vérifier l'existence des fichiers critiques
echo "🔍 Test 1: Fichiers critiques\n";
echo "-----------------------------\n";

$critical_files = [
    'class/audit.class.php',
    'core/modules/auditdigital/modules_audit.php',
    'core/modules/auditdigital/mod_audit_standard.php',
    'wizard/index.php'
];

foreach ($critical_files as $file) {
    $full_path = $base_path . '/' . $file;
    if (file_exists($full_path)) {
        echo "✅ $file\n";
    } else {
        echo "❌ $file MANQUANT\n";
    }
}

// Test 2: Vérifier la syntaxe des fichiers PHP
echo "\n🔍 Test 2: Syntaxe PHP\n";
echo "---------------------\n";

foreach ($critical_files as $file) {
    $full_path = $base_path . '/' . $file;
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        
        // Vérifications basiques
        if (strpos($content, '<?php') === 0) {
            echo "✅ $file - Balise PHP correcte\n";
        } else {
            echo "⚠️  $file - Balise PHP manquante ou incorrecte\n";
        }
        
        // Vérifier les accolades
        $open_braces = substr_count($content, '{');
        $close_braces = substr_count($content, '}');
        if ($open_braces === $close_braces) {
            echo "✅ $file - Accolades équilibrées\n";
        } else {
            echo "❌ $file - Accolades déséquilibrées ($open_braces ouvertes, $close_braces fermées)\n";
        }
    }
}

// Test 3: Vérifier les classes
echo "\n🔍 Test 3: Définitions de classes\n";
echo "---------------------------------\n";

$modules_file = $base_path . '/core/modules/auditdigital/modules_audit.php';
if (file_exists($modules_file)) {
    $content = file_get_contents($modules_file);
    
    if (strpos($content, 'class ModeleNumRefAudit') !== false) {
        echo "✅ Classe ModeleNumRefAudit trouvée\n";
    } else {
        echo "❌ Classe ModeleNumRefAudit manquante\n";
    }
    
    // Vérifier qu'il n'y a plus de classe dupliquée
    if (strpos($content, 'class ModelePDFAudit') === false) {
        echo "✅ Pas de classe ModelePDFAudit dupliquée\n";
    } else {
        echo "⚠️  Classe ModelePDFAudit encore présente (peut causer des conflits)\n";
    }
}

// Test 4: Vérifier les propriétés scandir
echo "\n🔍 Test 4: Propriétés scandir\n";
echo "-----------------------------\n";

$pdf_files = [
    'core/modules/auditdigital/doc/pdf_audit_tpe.modules.php',
    'core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php'
];

foreach ($pdf_files as $file) {
    $full_path = $base_path . '/' . $file;
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        if (strpos($content, 'public $scandir') !== false) {
            echo "✅ " . basename($file) . " - Propriété scandir présente\n";
        } else {
            echo "❌ " . basename($file) . " - Propriété scandir manquante\n";
        }
    }
}

// Test 5: Générer un rapport de correction
echo "\n📋 RAPPORT DE CORRECTION\n";
echo "========================\n";

$corrections_needed = [];

// Vérifier modules_audit.php
$modules_content = file_get_contents($base_path . '/core/modules/auditdigital/modules_audit.php');
if (strpos($modules_content, 'class ModelePDFAudit') !== false) {
    $corrections_needed[] = "Supprimer la classe ModelePDFAudit dupliquée de modules_audit.php";
}

// Vérifier les propriétés scandir
foreach ($pdf_files as $file) {
    $full_path = $base_path . '/' . $file;
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        if (strpos($content, 'public $scandir') === false) {
            $corrections_needed[] = "Ajouter la propriété scandir à " . basename($file);
        }
    }
}

if (empty($corrections_needed)) {
    echo "🎉 AUCUNE CORRECTION NÉCESSAIRE !\n";
    echo "Le module semble prêt pour les tests.\n\n";
    echo "🚀 PROCHAINES ÉTAPES :\n";
    echo "1. Déployez le module sur le serveur\n";
    echo "2. Testez : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php\n";
    echo "3. Créez un audit de test\n";
    echo "4. Vérifiez la génération PDF\n";
} else {
    echo "⚠️  CORRECTIONS NÉCESSAIRES :\n";
    foreach ($corrections_needed as $i => $correction) {
        echo ($i + 1) . ". $correction\n";
    }
    echo "\n🔧 Utilisez le script fix_wizard_final.sh pour appliquer les corrections.\n";
}

echo "\n✅ TEST TERMINÉ\n";
?>