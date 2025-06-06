#!/bin/bash

# =============================================================================
# Script de Diagnostic - Erreur fk_soc
# =============================================================================
# 
# Ce script diagnostique l'erreur "Column 'fk_soc' cannot be null"
#
# Usage: sudo ./diagnose_fksoc.sh
#
# Auteur: Up Digit Agency
# Version: 1.0.0
# =============================================================================

set -euo pipefail

# Configuration
DOLIBARR_DIR="/usr/share/dolibarr/htdocs"
MODULE_DIR="$DOLIBARR_DIR/custom/auditdigital"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

echo
echo -e "${CYAN}=============================================="
echo "🔍 DIAGNOSTIC ERREUR FK_SOC"
echo "=============================================="
echo -e "${NC}"

echo "📁 Vérification des fichiers :"
echo

# Vérifier les fichiers wizard
files=("wizard/index.php" "wizard/modern.php" "class/audit.class.php")
for file in "${files[@]}"; do
    if [[ -f "$MODULE_DIR/$file" ]]; then
        echo "  ✅ $file existe"
    else
        echo "  ❌ $file manquant"
    fi
done

echo
echo "🔍 Recherche des lignes fk_soc :"
echo

# Chercher les lignes contenant fk_soc
for file in "${files[@]}"; do
    if [[ -f "$MODULE_DIR/$file" ]]; then
        echo "📄 Dans $file :"
        grep -n "fk_soc" "$MODULE_DIR/$file" | head -5 || echo "  Aucune occurrence trouvée"
        echo
    fi
done

echo "🔍 Recherche de la création d'audit :"
echo

# Chercher où l'audit est créé
for file in "${files[@]}"; do
    if [[ -f "$MODULE_DIR/$file" ]]; then
        echo "📄 Dans $file :"
        grep -n -A 3 -B 3 "audit.*create\|create.*audit" "$MODULE_DIR/$file" | head -10 || echo "  Aucune création d'audit trouvée"
        echo
    fi
done

echo "🔍 Vérification de la correction appliquée :"
echo

# Vérifier si la correction est déjà appliquée
for file in "wizard/index.php" "wizard/modern.php"; do
    if [[ -f "$MODULE_DIR/$file" ]]; then
        echo "📄 Dans $file :"
        if grep -q "Société créée automatiquement" "$MODULE_DIR/$file"; then
            echo "  ✅ Correction fk_soc appliquée"
        else
            echo "  ❌ Correction fk_soc NON appliquée"
        fi
        
        if grep -q "audit_type.*digital_maturity" "$MODULE_DIR/$file"; then
            echo "  ✅ Champ audit_type présent"
        else
            echo "  ❌ Champ audit_type manquant"
        fi
        echo
    fi
done

echo "🔍 Vérification de la base de données :"
echo

# Vérifier la structure de la table audit
if command -v mysql &>/dev/null; then
    echo "📊 Structure table llx_auditdigital_audit :"
    mysql -u root -p -e "DESCRIBE dolibarr.llx_auditdigital_audit;" 2>/dev/null | grep -E "fk_soc|audit_type" || echo "  Erreur d'accès à la base de données"
    echo
fi

echo "🔍 Logs d'erreur récents :"
echo

# Afficher les dernières erreurs Apache
if [[ -f "/var/log/apache2/error.log" ]]; then
    echo "📋 Dernières erreurs Apache (fk_soc) :"
    tail -20 /var/log/apache2/error.log | grep -i "fk_soc\|cannot be null" || echo "  Aucune erreur fk_soc récente"
    echo
fi

echo "🔍 Test de syntaxe PHP :"
echo

# Tester la syntaxe des fichiers PHP
for file in "wizard/index.php" "wizard/modern.php"; do
    if [[ -f "$MODULE_DIR/$file" ]]; then
        echo "📄 Test $file :"
        if php -l "$MODULE_DIR/$file" &>/dev/null; then
            echo "  ✅ Syntaxe PHP valide"
        else
            echo "  ❌ Erreur de syntaxe PHP"
            php -l "$MODULE_DIR/$file" 2>&1 | head -3
        fi
        echo
    fi
done

echo -e "${YELLOW}=============================================="
echo "📋 RÉSUMÉ DU DIAGNOSTIC"
echo "=============================================="
echo -e "${NC}"

echo "Pour corriger l'erreur fk_soc, exécutez :"
echo "  sudo ./fix_fksoc_final.sh"
echo

echo "Pour surveiller les erreurs en temps réel :"
echo "  sudo tail -f /var/log/apache2/error.log"
echo

echo "URL de test :"
echo "  http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php"
echo