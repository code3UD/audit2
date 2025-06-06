#!/bin/bash

# =============================================================================
# Script de Test des Corrections
# =============================================================================

DOLIBARR_DIR="/usr/share/dolibarr/htdocs"
MODULE_DIR="$DOLIBARR_DIR/custom/auditdigital"

echo "🔍 TEST DES CORRECTIONS APPLIQUÉES"
echo "=================================="
echo

# Test 1: Vérifier audit_card.php
echo "1️⃣ Test audit_card.php :"
if php -l "$MODULE_DIR/audit_card.php" &>/dev/null; then
    echo "  ✅ Syntaxe PHP valide"
else
    echo "  ❌ Erreur de syntaxe"
    php -l "$MODULE_DIR/audit_card.php"
fi

if grep -q "FormProjets" "$MODULE_DIR/audit_card.php"; then
    echo "  ✅ FormProjets corrigé"
else
    echo "  ❌ FormProjets non corrigé"
fi

# Test 2: Vérifier wizard moderne
echo
echo "2️⃣ Test wizard moderne :"
if php -l "$MODULE_DIR/wizard/modern.php" &>/dev/null; then
    echo "  ✅ Syntaxe PHP valide"
else
    echo "  ❌ Erreur de syntaxe"
    php -l "$MODULE_DIR/wizard/modern.php"
fi

if grep -q "selectOption" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Fonction selectOption présente"
else
    echo "  ❌ Fonction selectOption manquante"
fi

if grep -q "audit_type.*digital_maturity" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Champ audit_type ajouté"
else
    echo "  ❌ Champ audit_type manquant"
fi

# Test 3: Test d'accès web
echo
echo "3️⃣ Test d'accès web :"
if command -v curl &>/dev/null; then
    local http_code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/dolibarr/custom/auditdigital/wizard/modern.php" 2>/dev/null || echo "000")
    if [[ "$http_code" == "200" ]]; then
        echo "  ✅ Wizard moderne accessible (HTTP $http_code)"
    else
        echo "  ❌ Problème d'accès (HTTP $http_code)"
    fi
fi

# Test 4: Permissions
echo
echo "4️⃣ Test permissions :"
if [[ -r "$MODULE_DIR/wizard/modern.php" ]]; then
    echo "  ✅ Permissions lecture OK"
else
    echo "  ❌ Problème permissions"
fi

echo
echo "🌐 URLs à tester :"
echo "  • Wizard moderne : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
echo "  • Étape 2 : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php?step=2"
echo "  • Étape 6 : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php?step=6"
echo
echo "📋 Si problème :"
echo "  sudo tail -f /var/log/apache2/error.log"
echo