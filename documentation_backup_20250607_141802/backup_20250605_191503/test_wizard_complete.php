<?php
/**
 * Script de test complet pour le wizard AuditDigital
 * Identifie et corrige les erreurs critiques
 */

echo "🔍 TEST COMPLET DU WIZARD AUDITDIGITAL\n";
echo "=====================================\n\n";

// Couleurs pour l'affichage
function print_status($msg) { echo "✅ $msg\n"; }
function print_error($msg) { echo "❌ $msg\n"; }
function print_warning($msg) { echo "⚠️  $msg\n"; }
function print_info($msg) { echo "ℹ️  $msg\n"; }

$base_path = __DIR__ . '/htdocs/custom/auditdigital';

print_info("=== VÉRIFICATION DES FICHIERS CRITIQUES ===");

// 1. Vérifier les fichiers de base
$critical_files = [
    'class/audit.class.php' => 'Classe principale Audit',
    'core/modules/auditdigital/modules_audit.php' => 'Modules de numérotation',
    'core/modules/auditdigital/mod_audit_standard.php' => 'Numérotation standard',
    'core/modules/auditdigital/doc/pdf_audit_tpe.modules.php' => 'PDF TPE',
    'core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php' => 'PDF Collectivité',
    'wizard/index.php' => 'Wizard principal',
    'lib/auditdigital.lib.php' => 'Bibliothèque principale'
];

foreach ($critical_files as $file => $description) {
    $full_path = $base_path . '/' . $file;
    if (file_exists($full_path)) {
        print_status("$description : $file");
    } else {
        print_error("$description MANQUANT : $file");
    }
}

print_info("\n=== VÉRIFICATION DES CLASSES PHP ===");

// 2. Tester l'inclusion des classes
$test_files = [
    $base_path . '/class/audit.class.php',
    $base_path . '/core/modules/auditdigital/modules_audit.php',
    $base_path . '/core/modules/auditdigital/mod_audit_standard.php'
];

foreach ($test_files as $file) {
    if (file_exists($file)) {
        print_info("Test d'inclusion : " . basename($file));
        
        // Capturer les erreurs
        ob_start();
        $error_level = error_reporting(E_ALL);
        
        try {
            // Simuler l'environnement Dolibarr minimal
            if (!defined('DOL_DOCUMENT_ROOT')) {
                define('DOL_DOCUMENT_ROOT', '/usr/share/dolibarr/htdocs');
            }
            if (!defined('MAIN_DB_PREFIX')) {
                define('MAIN_DB_PREFIX', 'llx_');
            }
            
            // Inclure le fichier
            $result = include_once $file;
            
            if ($result) {
                print_status("✓ Inclusion réussie : " . basename($file));
            } else {
                print_error("✗ Échec d'inclusion : " . basename($file));
            }
            
        } catch (ParseError $e) {
            print_error("✗ Erreur de syntaxe dans " . basename($file) . " : " . $e->getMessage());
        } catch (Error $e) {
            print_error("✗ Erreur fatale dans " . basename($file) . " : " . $e->getMessage());
        } catch (Exception $e) {
            print_warning("⚠ Exception dans " . basename($file) . " : " . $e->getMessage());
        }
        
        $output = ob_get_clean();
        error_reporting($error_level);
        
        if (!empty($output)) {
            print_warning("Sortie capturée : " . trim($output));
        }
    }
}

print_info("\n=== VÉRIFICATION DES CONFLITS DE CLASSES ===");

// 3. Vérifier les conflits de classes
$modules_audit_content = file_get_contents($base_path . '/core/modules/auditdigital/modules_audit.php');
$pdf_tpe_content = file_get_contents($base_path . '/core/modules/auditdigital/doc/pdf_audit_tpe.modules.php');

// Chercher les définitions de ModelePDFAudit
$modules_has_modele = strpos($modules_audit_content, 'class ModelePDFAudit') !== false;
$pdf_has_modele = strpos($pdf_tpe_content, 'class ModelePDFAudit') !== false;

if ($modules_has_modele && $pdf_has_modele) {
    print_error("CONFLIT DÉTECTÉ : ModelePDFAudit définie dans modules_audit.php ET pdf_audit_tpe.modules.php");
    print_info("SOLUTION : Supprimer la définition de modules_audit.php ou utiliser une classe de base différente");
} else {
    print_status("Pas de conflit de classe ModelePDFAudit détecté");
}

print_info("\n=== VÉRIFICATION DES PROPRIÉTÉS SCANDIR ===");

// 4. Vérifier la propriété scandir dans les PDF
$pdf_files = [
    $base_path . '/core/modules/auditdigital/doc/pdf_audit_tpe.modules.php',
    $base_path . '/core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php'
];

foreach ($pdf_files as $pdf_file) {
    if (file_exists($pdf_file)) {
        $content = file_get_contents($pdf_file);
        if (strpos($content, 'public $scandir') !== false) {
            print_status("Propriété scandir trouvée dans " . basename($pdf_file));
        } else {
            print_error("Propriété scandir MANQUANTE dans " . basename($pdf_file));
        }
    }
}

print_info("\n=== GÉNÉRATION DU SCRIPT DE CORRECTION ===");

// 5. Générer un script de correction automatique
$fix_script = <<<'EOF'
#!/bin/bash
# Script de correction automatique généré

echo "🔧 CORRECTION AUTOMATIQUE DES ERREURS AUDITDIGITAL"
echo "=================================================="

BASE_PATH="/usr/share/dolibarr/htdocs/custom/auditdigital"

# 1. Supprimer la classe dupliquée ModelePDFAudit de modules_audit.php
echo "1. Suppression de la classe dupliquée..."
if [ -f "$BASE_PATH/core/modules/auditdigital/modules_audit.php" ]; then
    # Backup
    cp "$BASE_PATH/core/modules/auditdigital/modules_audit.php" "$BASE_PATH/core/modules/auditdigital/modules_audit.php.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Supprimer la classe ModelePDFAudit (garder seulement ModeleNumRefAudit)
    sed -i '/^abstract class ModelePDFAudit/,/^}/d' "$BASE_PATH/core/modules/auditdigital/modules_audit.php"
    echo "✅ Classe dupliquée supprimée"
else
    echo "❌ Fichier modules_audit.php non trouvé"
fi

# 2. Vérifier et corriger les propriétés scandir
echo "2. Vérification des propriétés scandir..."

for pdf_file in "pdf_audit_tpe.modules.php" "pdf_audit_collectivite.modules.php"; do
    full_path="$BASE_PATH/core/modules/auditdigital/doc/$pdf_file"
    if [ -f "$full_path" ]; then
        if ! grep -q "public \$scandir" "$full_path"; then
            echo "Ajout de la propriété scandir à $pdf_file"
            # Backup
            cp "$full_path" "$full_path.backup.$(date +%Y%m%d_%H%M%S)"
            
            # Ajouter scandir après marge_basse
            sed -i '/public $marge_basse;/a\    public $scandir;' "$full_path"
            
            # Ajouter l'initialisation dans le constructeur
            sed -i '/this->marge_basse = /a\        $this->scandir = DOL_DOCUMENT_ROOT."/custom/auditdigital/core/modules/auditdigital/doc/";' "$full_path"
            
            echo "✅ Propriété scandir ajoutée à $pdf_file"
        else
            echo "✅ Propriété scandir déjà présente dans $pdf_file"
        fi
    else
        echo "❌ Fichier $pdf_file non trouvé"
    fi
done

# 3. Corriger les permissions
echo "3. Correction des permissions..."
chown -R www-data:www-data "$BASE_PATH"
chmod -R 644 "$BASE_PATH"
find "$BASE_PATH" -type d -exec chmod 755 {} \;
echo "✅ Permissions corrigées"

# 4. Redémarrer Apache
echo "4. Redémarrage d'Apache..."
systemctl restart apache2
echo "✅ Apache redémarré"

echo ""
echo "🎯 CORRECTION TERMINÉE !"
echo "Testez maintenant : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"

EOF;

file_put_contents(__DIR__ . '/fix_wizard_errors.sh', $fix_script);
chmod(__DIR__ . '/fix_wizard_errors.sh', 0755);

print_status("Script de correction généré : fix_wizard_errors.sh");

print_info("\n=== RÉSUMÉ ET RECOMMANDATIONS ===");
print_info("1. Exécutez le script de correction : sudo ./fix_wizard_errors.sh");
print_info("2. Testez le wizard : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php");
print_info("3. Surveillez les logs : sudo tail -f /var/log/apache2/error.log");
print_info("4. Si erreurs persistent, vérifiez la configuration Dolibarr");

echo "\n🔧 TEST TERMINÉ\n";
?>