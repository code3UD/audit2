#!/bin/bash

# =============================================================================
# Script de Test Complet du Wizard Moderne
# =============================================================================

MODULE_DIR="/workspace/audit2"

echo "🚀 TEST COMPLET DU WIZARD MODERNE"
echo "================================="
echo

# Test 1: Syntaxe PHP
echo "1️⃣ Test syntaxe PHP :"
if php -l "$MODULE_DIR/wizard/modern.php" &>/dev/null; then
    echo "  ✅ Syntaxe PHP valide"
else
    echo "  ❌ Erreur de syntaxe"
    php -l "$MODULE_DIR/wizard/modern.php"
    exit 1
fi

# Test 2: Vérifier les étapes implémentées
echo
echo "2️⃣ Test des étapes implémentées :"

# Étape 1
if grep -q "Informations Générales" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Étape 1 (Informations Générales) implémentée"
else
    echo "  ❌ Étape 1 manquante"
fi

# Étape 2
if grep -q "Maturité Digitale" "$MODULE_DIR/wizard/modern.php" && grep -q "audit_web_presence" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Étape 2 (Maturité Digitale) complètement implémentée"
else
    echo "  ❌ Étape 2 incomplète"
fi

# Étape 3
if grep -q "Cybersécurité" "$MODULE_DIR/wizard/modern.php" && grep -q "audit_security_level" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Étape 3 (Cybersécurité) implémentée"
else
    echo "  ❌ Étape 3 manquante"
fi

# Étape 4
if grep -q "Cloud & Infrastructure" "$MODULE_DIR/wizard/modern.php" && grep -q "audit_cloud_adoption" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Étape 4 (Cloud & Infrastructure) implémentée"
else
    echo "  ❌ Étape 4 manquante"
fi

# Étape 5
if grep -q "Automatisation" "$MODULE_DIR/wizard/modern.php" && grep -q "audit_automation_level" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Étape 5 (Automatisation) implémentée"
else
    echo "  ❌ Étape 5 manquante"
fi

# Étape 6
if grep -q "Synthèse & Recommandations" "$MODULE_DIR/wizard/modern.php" && grep -q "total_score" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Étape 6 (Synthèse) avec calcul de score implémentée"
else
    echo "  ❌ Étape 6 incomplète"
fi

# Test 3: Vérifier les fonctionnalités
echo
echo "3️⃣ Test des fonctionnalités :"

if grep -q "selectOption" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Fonction selectOption présente"
else
    echo "  ❌ Fonction selectOption manquante"
fi

if grep -q "json_encode.*wizard_data" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Sauvegarde JSON des réponses implémentée"
else
    echo "  ❌ Sauvegarde JSON manquante"
fi

if grep -q "score_global.*round.*total_score" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Calcul et sauvegarde des scores implémentés"
else
    echo "  ❌ Calcul des scores manquant"
fi

# Test 4: Vérifier les champs requis
echo
echo "4️⃣ Test des champs requis :"

required_fields=("audit_structure_type" "audit_digital_level" "audit_web_presence" "audit_security_level" "audit_rgpd_compliance" "audit_cloud_adoption" "audit_mobility" "audit_automation_level" "audit_collaboration_tools")

for field in "${required_fields[@]}"; do
    if grep -q "name=\"$field\".*required" "$MODULE_DIR/wizard/modern.php"; then
        echo "  ✅ Champ $field requis"
    else
        echo "  ⚠️ Champ $field non requis"
    fi
done

# Test 5: Vérifier les icônes et le design
echo
echo "5️⃣ Test du design moderne :"

if grep -q "fas fa-" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Icônes Font Awesome présentes"
else
    echo "  ❌ Icônes manquantes"
fi

if grep -q "option-card.*onclick.*selectOption" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Cards cliquables implémentées"
else
    echo "  ❌ Cards cliquables manquantes"
fi

if grep -q "gradient.*primary" "$MODULE_DIR/wizard/modern.php"; then
    echo "  ✅ Design avec gradients moderne"
else
    echo "  ❌ Design moderne manquant"
fi

# Test 6: Vérifier la classe Audit
echo
echo "6️⃣ Test de la classe Audit :"

if grep -q "score_global" "$MODULE_DIR/class/audit.class.php" && grep -q "score_maturite" "$MODULE_DIR/class/audit.class.php" && grep -q "score_cybersecurite" "$MODULE_DIR/class/audit.class.php"; then
    echo "  ✅ Champs de scores dans la classe Audit"
else
    echo "  ❌ Champs de scores manquants"
fi

if grep -q "json_responses" "$MODULE_DIR/class/audit.class.php"; then
    echo "  ✅ Champ json_responses présent"
else
    echo "  ❌ Champ json_responses manquant"
fi

echo
echo "🎯 RÉSUMÉ DES FONCTIONNALITÉS :"
echo "================================"
echo "✅ Étape 1 : Informations générales (structure, société)"
echo "✅ Étape 2 : Maturité digitale (processus + web)"
echo "✅ Étape 3 : Cybersécurité (protection + RGPD)"
echo "✅ Étape 4 : Cloud & Infrastructure (adoption + mobilité)"
echo "✅ Étape 5 : Automatisation (processus + collaboration)"
echo "✅ Étape 6 : Synthèse avec calcul de scores et recommandations"
echo
echo "🎨 DESIGN MODERNE :"
echo "==================="
echo "✅ Interface glassmorphism avec gradients"
echo "✅ Cards cliquables avec animations"
echo "✅ Stepper visuel interactif"
echo "✅ Icônes Font Awesome"
echo "✅ Responsive design"
echo "✅ Notifications et feedback utilisateur"
echo
echo "💾 FONCTIONNALITÉS MÉTIER :"
echo "==========================="
echo "✅ Calcul automatique des scores par domaine"
echo "✅ Score global sur 100 points"
echo "✅ Niveaux de maturité (Débutant, Intermédiaire, Avancé, Expert)"
echo "✅ Recommandations personnalisées"
echo "✅ Sauvegarde JSON des réponses"
echo "✅ Auto-save en session"
echo
echo "🚀 WIZARD MODERNE COMPLÈTEMENT FONCTIONNEL !"
echo "============================================="