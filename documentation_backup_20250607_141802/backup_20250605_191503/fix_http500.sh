#!/bin/bash
# fix_http500.sh
# Script pour corriger l'erreur HTTP 500 du wizard AuditDigital

echo "🔧 Correction de l'erreur HTTP 500 - Wizard AuditDigital"
echo "========================================================"

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

# Détecter le chemin Dolibarr
print_info "Détection de l'installation Dolibarr..."

DOLIBARR_PATHS=(
    "/usr/share/dolibarr/htdocs"
    "/var/lib/dolibarr/htdocs" 
    "/usr/dolibarr/htdocs"
    "/opt/dolibarr/htdocs"
)

DOLIBARR_PATH=""
for path in "${DOLIBARR_PATHS[@]}"; do
    if [ -f "$path/main.inc.php" ]; then
        DOLIBARR_PATH="$path"
        print_status "Dolibarr trouvé : $path"
        break
    fi
done

if [ -z "$DOLIBARR_PATH" ]; then
    print_error "Dolibarr non trouvé"
    exit 1
fi

MODULE_PATH="$DOLIBARR_PATH/custom/auditdigital"

print_info "=== DIAGNOSTIC DE L'ERREUR HTTP 500 ==="

# 1. Vérifier les logs d'erreur
print_info "1. Vérification des logs d'erreur..."

LOG_FILES=(
    "/var/log/apache2/error.log"
    "/var/log/php*.log"
    "/var/log/dolibarr/dolibarr.log"
)

for log_file in "${LOG_FILES[@]}"; do
    if [ -f "$log_file" ] && [ -r "$log_file" ]; then
        print_status "Analyse de $log_file"
        echo "Dernières erreurs liées à auditdigital :"
        tail -50 "$log_file" | grep -i "auditdigital\|wizard\|custom" | tail -5
        echo ""
    fi
done

# 2. Vérifier la syntaxe PHP
print_info "2. Vérification de la syntaxe PHP..."

PHP_FILES=(
    "$MODULE_PATH/wizard/index.php"
    "$MODULE_PATH/class/audit.class.php"
    "$MODULE_PATH/class/questionnaire.class.php"
    "$MODULE_PATH/class/solutionlibrary.class.php"
)

for php_file in "${PHP_FILES[@]}"; do
    if [ -f "$php_file" ]; then
        print_info "Test syntaxe: $(basename $php_file)"
        if php -l "$php_file" > /dev/null 2>&1; then
            print_status "Syntaxe OK"
        else
            print_error "Erreur de syntaxe détectée !"
            php -l "$php_file"
        fi
    else
        print_error "Fichier manquant: $php_file"
    fi
done

# 3. Vérifier les permissions
print_info "3. Vérification des permissions..."

if [ -d "$MODULE_PATH" ]; then
    print_status "Module trouvé : $MODULE_PATH"
    
    # Vérifier les permissions du répertoire
    perms=$(stat -c '%a' "$MODULE_PATH")
    owner=$(stat -c '%U:%G' "$MODULE_PATH")
    print_info "Permissions actuelles : $perms ($owner)"
    
    if [ "$owner" != "www-data:www-data" ]; then
        print_warning "Correction du propriétaire..."
        chown -R www-data:www-data "$MODULE_PATH"
        print_status "Propriétaire corrigé"
    fi
    
    if [ "$perms" != "755" ]; then
        print_warning "Correction des permissions..."
        chmod -R 755 "$MODULE_PATH"
        print_status "Permissions corrigées"
    fi
else
    print_error "Module non trouvé : $MODULE_PATH"
    exit 1
fi

# 4. Vérifier les extensions PHP
print_info "4. Vérification des extensions PHP..."

REQUIRED_EXTENSIONS=("gd" "mysql" "json" "mbstring" "xml")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        print_status "Extension $ext : OK"
    else
        MISSING_EXTENSIONS+=("php-$ext")
        print_error "Extension $ext : manquante"
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    print_info "Installation des extensions manquantes..."
    apt update
    apt install -y "${MISSING_EXTENSIONS[@]}"
    print_status "Extensions installées"
fi

# 5. Vérifier la configuration PHP
print_info "5. Vérification de la configuration PHP..."

# Vérifier memory_limit
memory_limit=$(php -r "echo ini_get('memory_limit');")
print_info "Memory limit actuel : $memory_limit"

if [[ "$memory_limit" =~ ^[0-9]+M$ ]]; then
    memory_value=${memory_limit%M}
    if [ "$memory_value" -lt 128 ]; then
        print_warning "Memory limit trop faible, augmentation recommandée"
    fi
fi

# Vérifier display_errors pour le debug
display_errors=$(php -r "echo ini_get('display_errors');")
if [ "$display_errors" != "1" ]; then
    print_info "Activation temporaire de l'affichage des erreurs pour debug..."
    
    # Trouver le fichier php.ini
    PHP_INI=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)
    if [ -f "$PHP_INI" ]; then
        print_info "Fichier PHP.ini : $PHP_INI"
        # Backup et modification temporaire
        cp "$PHP_INI" "$PHP_INI.backup.$(date +%Y%m%d_%H%M%S)"
        sed -i 's/display_errors = Off/display_errors = On/' "$PHP_INI"
        sed -i 's/;display_errors = On/display_errors = On/' "$PHP_INI"
        print_status "Affichage des erreurs activé temporairement"
    fi
fi

# 6. Test du wizard
print_info "6. Test d'accès au wizard..."

# Créer un script de test simple
TEST_SCRIPT="$MODULE_PATH/test_wizard.php"
cat > "$TEST_SCRIPT" << 'EOF'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Wizard AuditDigital</h1>";

// Test 1: Inclusion de main.inc.php
echo "<h2>Test 1: Inclusion Dolibarr</h2>";
$res = 0;
$paths = array(
    "../main.inc.php",
    "../../main.inc.php", 
    "../../../main.inc.php",
    "/usr/share/dolibarr/htdocs/main.inc.php",
    "/var/lib/dolibarr/htdocs/main.inc.php"
);

foreach ($paths as $path) {
    if (file_exists($path)) {
        echo "✅ Trouvé: $path<br>";
        try {
            include_once $path;
            echo "✅ Inclusion réussie<br>";
            $res = 1;
            break;
        } catch (Exception $e) {
            echo "❌ Erreur inclusion: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Non trouvé: $path<br>";
    }
}

if (!$res) {
    die("❌ Impossible de charger Dolibarr");
}

// Test 2: Vérification des constantes
echo "<h2>Test 2: Constantes Dolibarr</h2>";
echo "DOL_DOCUMENT_ROOT: " . (defined('DOL_DOCUMENT_ROOT') ? DOL_DOCUMENT_ROOT : 'Non défini') . "<br>";
echo "DOL_VERSION: " . (defined('DOL_VERSION') ? DOL_VERSION : 'Non défini') . "<br>";

// Test 3: Classes AuditDigital
echo "<h2>Test 3: Classes AuditDigital</h2>";
$classes = array(
    'audit.class.php' => 'Audit',
    'questionnaire.class.php' => 'Questionnaire', 
    'solutionlibrary.class.php' => 'SolutionLibrary'
);

foreach ($classes as $file => $class) {
    $class_file = DOL_DOCUMENT_ROOT . '/custom/auditdigital/class/' . $file;
    echo "Test $file: ";
    if (file_exists($class_file)) {
        try {
            require_once $class_file;
            if (class_exists($class)) {
                echo "✅ OK<br>";
            } else {
                echo "❌ Classe $class non trouvée<br>";
            }
        } catch (Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Fichier non trouvé<br>";
    }
}

// Test 4: Module activé
echo "<h2>Test 4: Module AuditDigital</h2>";
if (function_exists('isModEnabled')) {
    if (isModEnabled('auditdigital')) {
        echo "✅ Module AuditDigital activé<br>";
    } else {
        echo "❌ Module AuditDigital non activé<br>";
    }
} else {
    echo "❌ Fonction isModEnabled non disponible<br>";
}

echo "<h2>Conclusion</h2>";
echo "Si tous les tests sont OK, le wizard devrait fonctionner.<br>";
echo "Sinon, vérifiez les erreurs ci-dessus.<br>";
?>
EOF

chown www-data:www-data "$TEST_SCRIPT"
chmod 644 "$TEST_SCRIPT"

print_status "Script de test créé : $TEST_SCRIPT"

# 7. Redémarrer Apache
print_info "7. Redémarrage d'Apache..."
systemctl restart apache2
print_status "Apache redémarré"

echo ""
echo "============================================================"
print_status "Diagnostic et corrections terminés !"
echo "============================================================"
echo ""

print_info "📋 Prochaines étapes :"
echo "1. Testez le script de diagnostic :"
echo "   http://192.168.1.252/dolibarr/custom/auditdigital/test_wizard.php"
echo ""
echo "2. Si le test passe, essayez le wizard :"
echo "   http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo ""
echo "3. Vérifiez les logs en temps réel :"
echo "   sudo tail -f /var/log/apache2/error.log"
echo ""

print_info "🔧 Si l'erreur persiste :"
echo "- Consultez les logs d'erreur affichés ci-dessus"
echo "- Exécutez le script de test pour plus de détails"
echo "- Vérifiez que tous les modules Dolibarr requis sont activés"

# Restaurer display_errors si modifié
if [ -f "$PHP_INI.backup."* ]; then
    print_info "Pour restaurer la configuration PHP après debug :"
    echo "sudo cp $PHP_INI.backup.* $PHP_INI"
    echo "sudo systemctl restart apache2"
fi

exit 0