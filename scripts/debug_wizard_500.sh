#!/bin/bash
# Script pour diagnostiquer l'erreur HTTP 500 spécifique du wizard

echo "🔍 Diagnostic Erreur HTTP 500 - Wizard Index"
echo "============================================="

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Vérifier les privilèges root
if [[ $EUID -ne 0 ]]; then
   print_error "Ce script doit être exécuté en tant que root (sudo)"
   exit 1
fi

DOLIBARR_PATH="/usr/share/dolibarr/htdocs"
WIZARD_FILE="$DOLIBARR_PATH/custom/auditdigital/wizard/index.php"

print_info "=== DIAGNOSTIC SPÉCIFIQUE WIZARD INDEX.PHP ==="

# 1. Vérifier le fichier wizard
print_info "1. Vérification du fichier wizard..."
if [ -f "$WIZARD_FILE" ]; then
    print_status "Fichier wizard trouvé : $WIZARD_FILE"
    
    # Vérifier les permissions
    perms=$(stat -c '%a' "$WIZARD_FILE")
    owner=$(stat -c '%U:%G' "$WIZARD_FILE")
    print_info "Permissions : $perms ($owner)"
    
    # Vérifier la syntaxe PHP
    print_info "Test syntaxe PHP..."
    if php -l "$WIZARD_FILE" > /dev/null 2>&1; then
        print_status "Syntaxe PHP : OK"
    else
        print_error "Erreur de syntaxe PHP !"
        php -l "$WIZARD_FILE"
        exit 1
    fi
else
    print_error "Fichier wizard non trouvé : $WIZARD_FILE"
    exit 1
fi

# 2. Activer l'affichage des erreurs temporairement
print_info "2. Activation temporaire de l'affichage des erreurs..."

PHP_INI=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)
if [ -f "$PHP_INI" ]; then
    print_info "Fichier PHP.ini : $PHP_INI"
    
    # Backup
    cp "$PHP_INI" "$PHP_INI.backup.debug.$(date +%Y%m%d_%H%M%S)"
    
    # Activer l'affichage des erreurs
    sed -i 's/display_errors = Off/display_errors = On/' "$PHP_INI"
    sed -i 's/;display_errors = On/display_errors = On/' "$PHP_INI"
    sed -i 's/error_reporting = .*/error_reporting = E_ALL/' "$PHP_INI"
    
    print_status "Affichage des erreurs activé"
    
    # Redémarrer Apache
    systemctl restart apache2
    print_status "Apache redémarré"
fi

# 3. Créer un script de debug spécifique
print_info "3. Création d'un script de debug spécifique..."

DEBUG_SCRIPT="$DOLIBARR_PATH/custom/auditdigital/wizard/debug_index.php"
cat > "$DEBUG_SCRIPT" << 'EOF'
<?php
/**
 * Debug spécifique pour wizard/index.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Debug Wizard Index.php</h1>";

// Capturer toutes les erreurs
set_error_handler(function($severity, $message, $file, $line) {
    echo "<div style='color: red; border: 1px solid red; padding: 10px; margin: 5px;'>";
    echo "<strong>ERREUR PHP:</strong><br>";
    echo "Message: $message<br>";
    echo "Fichier: $file<br>";
    echo "Ligne: $line<br>";
    echo "Sévérité: $severity<br>";
    echo "</div>";
});

// Capturer les exceptions
set_exception_handler(function($exception) {
    echo "<div style='color: red; border: 1px solid red; padding: 10px; margin: 5px;'>";
    echo "<strong>EXCEPTION:</strong><br>";
    echo "Message: " . $exception->getMessage() . "<br>";
    echo "Fichier: " . $exception->getFile() . "<br>";
    echo "Ligne: " . $exception->getLine() . "<br>";
    echo "Trace: <pre>" . $exception->getTraceAsString() . "</pre>";
    echo "</div>";
});

echo "<h2>Étape 1: Test d'inclusion Dolibarr</h2>";

// Test d'inclusion exactement comme dans index.php
$res = 0;
$paths_to_try = array(
    "../../main.inc.php",
    "../../../main.inc.php",
    "../main.inc.php",
    "../../../../main.inc.php",
    "../../../../../main.inc.php"
);

foreach ($paths_to_try as $path) {
    echo "Test: $path - ";
    if (!$res && file_exists($path)) {
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
            echo "❌ Exception: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Non trouvé<br>";
    }
}

if (!$res) {
    die("❌ Impossible de charger Dolibarr");
}

echo "<h2>Étape 2: Test isModEnabled</h2>";
try {
    if (function_exists('isModEnabled')) {
        echo "Fonction isModEnabled: ✅<br>";
        if (isModEnabled('auditdigital')) {
            echo "Module auditdigital: ✅ Activé<br>";
        } else {
            echo "Module auditdigital: ❌ Non activé<br>";
            die("Module non activé");
        }
    } else {
        echo "❌ Fonction isModEnabled non disponible<br>";
        die("Fonction isModEnabled manquante");
    }
} catch (Exception $e) {
    echo "❌ Erreur isModEnabled: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Étape 3: Test des classes Dolibarr</h2>";
try {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
    echo "FormCompany: ✅<br>";
    
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
    echo "FormProjet: ✅<br>";
} catch (Exception $e) {
    echo "❌ Erreur classes Dolibarr: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Étape 4: Test des classes AuditDigital</h2>";
$classesLoaded = true;
$errorMessage = '';

try {
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php')) {
        require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
        echo "Audit class: ✅<br>";
    } else {
        throw new Exception('Audit class file not found');
    }
    
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php')) {
        require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php';
        echo "Questionnaire class: ✅<br>";
    } else {
        throw new Exception('Questionnaire class file not found');
    }
    
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php')) {
        require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';
        echo "SolutionLibrary class: ✅<br>";
    } else {
        throw new Exception('SolutionLibrary class file not found');
    }
} catch (Exception $e) {
    $classesLoaded = false;
    $errorMessage = $e->getMessage();
    echo "❌ Erreur classes: $errorMessage<br>";
    die();
}

echo "<h2>Étape 5: Test des traductions</h2>";
try {
    $langs->loadLangs(array("main", "companies", "projects"));
    echo "Traductions de base: ✅<br>";
    
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/auditdigital/langs/'.$langs->defaultlang.'/auditdigital.lang')) {
        $langs->load("auditdigital@auditdigital");
        echo "Traductions AuditDigital: ✅<br>";
    } else {
        echo "⚠️ Traductions AuditDigital non trouvées<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur traductions: " . $e->getMessage() . "<br>";
}

echo "<h2>Étape 6: Test GETPOST</h2>";
try {
    $action = GETPOST('action', 'aZ09');
    $step = GETPOST('step', 'int');
    $id = GETPOST('id', 'int');
    echo "GETPOST: ✅ (action=$action, step=$step, id=$id)<br>";
} catch (Exception $e) {
    echo "❌ Erreur GETPOST: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Étape 7: Test des permissions</h2>";
try {
    if (isset($user->rights->auditdigital->audit->write)) {
        if (!$user->rights->auditdigital->audit->write) {
            echo "❌ Permission d'écriture refusée<br>";
            die("Permission refusée");
        } else {
            echo "Permissions AuditDigital: ✅<br>";
        }
    } else {
        // Fallback
        if (!$user->id || $user->socid > 0) {
            echo "❌ Permission fallback refusée<br>";
            die("Permission fallback refusée");
        } else {
            echo "Permissions fallback: ✅<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur permissions: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Étape 8: Test création d'objets</h2>";
try {
    $formcompany = new FormCompany($db);
    echo "FormCompany créé: ✅<br>";
    
    if (isModEnabled('project')) {
        $formproject = new FormProjets($db);
        echo "FormProjets créé: ✅<br>";
    }
    
    $audit = new Audit($db);
    echo "Audit créé: ✅<br>";
    
    $questionnaire = new Questionnaire($db);
    echo "Questionnaire créé: ✅<br>";
    
    $solutionLibrary = new SolutionLibrary($db);
    echo "SolutionLibrary créé: ✅<br>";
    
} catch (Exception $e) {
    echo "❌ Erreur création objets: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>✅ TOUS LES TESTS PASSENT !</h2>";
echo "<p>Si vous voyez ce message, le problème ne vient pas des étapes de base.</p>";
echo "<p>Le problème pourrait venir de :</p>";
echo "<ul>";
echo "<li>Une erreur dans la partie HTML/affichage</li>";
echo "<li>Un problème de mémoire ou de timeout</li>";
echo "<li>Une erreur dans une fonction spécifique</li>";
echo "</ul>";

echo "<h3>Informations supplémentaires :</h3>";
echo "Memory usage: " . memory_get_usage(true) . " bytes<br>";
echo "Memory limit: " . ini_get('memory_limit') . "<br>";
echo "Max execution time: " . ini_get('max_execution_time') . "<br>";

?>
EOF

chown www-data:www-data "$DEBUG_SCRIPT"
chmod 644 "$DEBUG_SCRIPT"

print_status "Script de debug créé : $DEBUG_SCRIPT"

# 4. Surveiller les logs en temps réel
print_info "4. Surveillance des logs..."

echo ""
print_info "📋 Instructions :"
echo "1. Testez le script de debug : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/debug_index.php"
echo "2. Surveillez les logs : sudo tail -f /var/log/apache2/error.log"
echo "3. Testez ensuite le wizard : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo ""

print_info "🔧 Pour voir les logs en temps réel :"
echo "sudo tail -f /var/log/apache2/error.log | grep -E 'auditdigital|wizard|PHP'"

echo ""
print_warning "⚠️  N'oubliez pas de restaurer PHP.ini après debug :"
echo "sudo cp $PHP_INI.backup.debug.* $PHP_INI"
echo "sudo systemctl restart apache2"

exit 0