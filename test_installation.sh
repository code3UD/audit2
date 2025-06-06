#!/bin/bash

# =============================================================================
# Script de Test Rapide - Installation AuditDigital
# =============================================================================

DOLIBARR_DIR="/usr/share/dolibarr/htdocs"
MODULE_DIR="$DOLIBARR_DIR/custom/auditdigital"

echo "🔍 TEST RAPIDE DE L'INSTALLATION"
echo "================================="
echo

# Test des fichiers critiques
echo "📁 Fichiers critiques :"
files=(
    "wizard/index.php"
    "wizard/modern.php"
    "demo_modern.php"
    "lib/auditdigital.lib.php"
    "css/auditdigital-modern.css"
    "js/wizard-modern.js"
)

for file in "${files[@]}"; do
    if [[ -f "$MODULE_DIR/$file" ]]; then
        if php -l "$MODULE_DIR/$file" &>/dev/null 2>&1 || [[ ! "$file" =~ \.php$ ]]; then
            echo "  ✅ $file"
        else
            echo "  ❌ $file (erreur syntaxe)"
        fi
    else
        echo "  ❌ $file (manquant)"
    fi
done

echo
echo "🌐 Tests d'accès web :"

# Test des URLs
urls=(
    "http://localhost/dolibarr/custom/auditdigital/demo_modern.php"
    "http://localhost/dolibarr/custom/auditdigital/wizard/index.php"
    "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php"
)

for url in "${urls[@]}"; do
    if command -v curl &>/dev/null; then
        http_code=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
        filename=$(basename "$url")
        
        if [[ "$http_code" == "200" ]]; then
            echo "  ✅ $filename (HTTP $http_code)"
        else
            echo "  ❌ $filename (HTTP $http_code)"
        fi
    fi
done

echo
echo "🔧 Permissions :"
if [[ -r "$MODULE_DIR/wizard/modern.php" ]]; then
    echo "  ✅ Lecture des fichiers"
else
    echo "  ❌ Problème de lecture"
fi

if [[ -w "$MODULE_DIR/documents" ]]; then
    echo "  ✅ Écriture dans documents/"
else
    echo "  ❌ Problème d'écriture"
fi

echo
echo "📊 Résumé :"
echo "  • Interface classique : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo "  • Interface moderne   : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
echo "  • Démonstration       : http://192.168.1.252/dolibarr/custom/auditdigital/demo_modern.php"
echo
echo "🆘 Si problème avec modern.php :"
echo "  sudo ./fix_modern_wizard.sh"
echo