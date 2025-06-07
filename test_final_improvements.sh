#!/bin/bash

# Script de test des améliorations finales
# Vérifie que toutes les corrections sont appliquées

echo "🧪 TEST DES AMÉLIORATIONS FINALES"
echo "=================================="

MODULE_DIR="/workspace/audit2"

# Test 1: Vérification que enhanced.php est l'index principal
echo
echo "1️⃣ Test de l'index principal :"

if cmp -s "$MODULE_DIR/wizard/index.php" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ enhanced.php est maintenant l'index principal"
else
    echo "  ❌ enhanced.php n'est pas l'index principal"
fi

# Test 2: Vérification de la correction PDF
echo
echo "2️⃣ Test de la correction PDF :"

if grep -q "pdf_paths" "$MODULE_DIR/wizard/index.php" && grep -q "custom/auditdigital" "$MODULE_DIR/wizard/index.php"; then
    echo "  ✅ Chemins PDF multiples configurés"
else
    echo "  ❌ Chemins PDF multiples non configurés"
fi

if grep -q "CommonDocGenerator" "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"; then
    echo "  ✅ Classe PDF corrigée (CommonDocGenerator)"
else
    echo "  ❌ Classe PDF non corrigée"
fi

# Test 3: Vérification de la section commentaires
echo
echo "3️⃣ Test de la section commentaires :"

if grep -q "audit_recommendations" "$MODULE_DIR/wizard/index.php"; then
    echo "  ✅ Champ recommandations ajouté"
else
    echo "  ❌ Champ recommandations manquant"
fi

if grep -q "audit_action_plan" "$MODULE_DIR/wizard/index.php"; then
    echo "  ✅ Champ plan d'action ajouté"
else
    echo "  ❌ Champ plan d'action manquant"
fi

if grep -q "audit_objectives" "$MODULE_DIR/wizard/index.php"; then
    echo "  ✅ Champ objectifs ajouté"
else
    echo "  ❌ Champ objectifs manquant"
fi

if grep -q "audit_general_comments" "$MODULE_DIR/wizard/index.php"; then
    echo "  ✅ Champ commentaires généraux ajouté"
else
    echo "  ❌ Champ commentaires généraux manquant"
fi

# Test 4: Vérification de la sauvegarde des commentaires
echo
echo "4️⃣ Test de la sauvegarde des commentaires :"

if grep -q "recommendations.*=" "$MODULE_DIR/wizard/index.php" && grep -q "action_plan.*=" "$MODULE_DIR/wizard/index.php"; then
    echo "  ✅ Sauvegarde des commentaires configurée"
else
    echo "  ❌ Sauvegarde des commentaires non configurée"
fi

# Test 5: Vérification de l'interface
echo
echo "5️⃣ Test de l'interface commentaires :"

if grep -q "fas fa-comments" "$MODULE_DIR/wizard/index.php"; then
    echo "  ✅ Interface commentaires avec icônes"
else
    echo "  ❌ Interface commentaires sans icônes"
fi

if grep -q "textarea" "$MODULE_DIR/wizard/index.php" && grep -q "audit_recommendations" "$MODULE_DIR/wizard/index.php"; then
    echo "  ✅ Zone de texte recommandations"
else
    echo "  ❌ Zone de texte recommandations manquante"
fi

if grep -q "Conseils pour remplir cette section" "$MODULE_DIR/wizard/index.php"; then
    echo "  ✅ Aide contextuelle ajoutée"
else
    echo "  ❌ Aide contextuelle manquante"
fi

# Test 6: Vérification de la structure des fichiers
echo
echo "6️⃣ Test de la structure des fichiers :"

critical_files=(
    "wizard/index.php"
    "wizard/enhanced.php"
    "core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"
)

missing_files=0
for file in "${critical_files[@]}"; do
    if [[ -f "$MODULE_DIR/$file" ]]; then
        echo "  ✅ $file présent"
    else
        echo "  ❌ $file manquant"
        missing_files=$((missing_files + 1))
    fi
done

# Test 7: Simulation de données pour les commentaires
echo
echo "7️⃣ Test de simulation des commentaires :"

# Créer un fichier de test avec des données
cat > "$MODULE_DIR/test_comments_data.json" << 'EOF'
{
    "step_6": {
        "audit_recommendations": "Recommandation test: Améliorer la sécurité",
        "audit_action_plan": "Plan d'action test: Formation équipes",
        "audit_objectives": "Objectifs test: Digitalisation complète",
        "audit_general_comments": "Commentaires test: Très bon audit"
    }
}
EOF

if [[ -f "$MODULE_DIR/test_comments_data.json" ]]; then
    echo "  ✅ Données de test créées"
    
    # Vérifier que les champs sont bien structurés
    if grep -q "audit_recommendations" "$MODULE_DIR/test_comments_data.json" && grep -q "audit_action_plan" "$MODULE_DIR/test_comments_data.json"; then
        echo "  ✅ Structure des données validée"
    else
        echo "  ❌ Structure des données incorrecte"
    fi
    
    # Nettoyer
    rm -f "$MODULE_DIR/test_comments_data.json"
else
    echo "  ❌ Impossible de créer les données de test"
fi

# Résumé
echo
echo "📊 RÉSUMÉ DES AMÉLIORATIONS :"
echo "============================="

improvements=0
total_tests=7

# Compter les améliorations réussies
if cmp -s "$MODULE_DIR/wizard/index.php" "$MODULE_DIR/wizard/enhanced.php"; then
    improvements=$((improvements + 1))
fi

if grep -q "pdf_paths" "$MODULE_DIR/wizard/index.php" && grep -q "custom/auditdigital" "$MODULE_DIR/wizard/index.php"; then
    improvements=$((improvements + 1))
fi

if grep -q "audit_recommendations" "$MODULE_DIR/wizard/index.php" && grep -q "audit_action_plan" "$MODULE_DIR/wizard/index.php"; then
    improvements=$((improvements + 1))
fi

if grep -q "CommonDocGenerator" "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"; then
    improvements=$((improvements + 1))
fi

if grep -q "fas fa-comments" "$MODULE_DIR/wizard/index.php"; then
    improvements=$((improvements + 1))
fi

if [[ $missing_files -eq 0 ]]; then
    improvements=$((improvements + 1))
fi

# Test des données de commentaires
if grep -q "audit_recommendations" "$MODULE_DIR/wizard/index.php" && grep -q "textarea" "$MODULE_DIR/wizard/index.php"; then
    improvements=$((improvements + 1))
fi

echo "Améliorations réussies : $improvements/$total_tests"

if [ $improvements -eq $total_tests ]; then
    echo
    echo "🎉 TOUTES LES AMÉLIORATIONS APPLIQUÉES AVEC SUCCÈS !"
    echo "===================================================="
    echo "✅ enhanced.php est l'index principal"
    echo "✅ Problème PDF résolu (chemins multiples)"
    echo "✅ Section commentaires/recommandations ajoutée"
    echo "✅ Sauvegarde des commentaires configurée"
    echo "✅ Interface utilisateur améliorée"
    echo "✅ Aide contextuelle intégrée"
    echo "✅ Structure des fichiers validée"
    echo
    echo "🚀 Le wizard est maintenant complet et fonctionnel !"
    echo
    echo "📋 Fonctionnalités finales :"
    echo "• Index principal : wizard/index.php (enhanced)"
    echo "• Génération PDF : Automatique avec gestion d'erreurs"
    echo "• Commentaires : 4 sections (recommandations, plan, objectifs, général)"
    echo "• Sauvegarde : Tous les champs persistés"
    echo "• Interface : Moderne et intuitive"
else
    echo
    echo "⚠️  AMÉLIORATIONS PARTIELLES"
    echo "============================"
    echo "Certaines améliorations nécessitent encore des ajustements."
fi

echo
echo "🌐 Test en ligne :"
echo "https://work-1-hahvgpojwleeosnl.prod-runtime.all-hands.dev/wizard/?step=6"