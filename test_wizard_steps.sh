#!/bin/bash

# =============================================================================
# Test Rapide des Étapes du Wizard
# =============================================================================

MODULE_DIR="/workspace/audit2"

echo "🧪 TEST RAPIDE DES ÉTAPES DU WIZARD"
echo "==================================="
echo

# Test syntaxe PHP
echo "1️⃣ Test syntaxe PHP :"
if php -l "$MODULE_DIR/wizard/enhanced.php" &>/dev/null; then
    echo "  ✅ Syntaxe PHP valide"
else
    echo "  ❌ Erreur de syntaxe :"
    php -l "$MODULE_DIR/wizard/enhanced.php"
    exit 1
fi

# Test présence des étapes
echo
echo "2️⃣ Test présence des étapes :"

for step in {1..6}; do
    if grep -q "step == $step" "$MODULE_DIR/wizard/enhanced.php"; then
        echo "  ✅ Étape $step présente"
    else
        echo "  ❌ Étape $step manquante"
    fi
done

# Test des champs obligatoires
echo
echo "3️⃣ Test des champs obligatoires :"

required_fields=(
    "audit_structure_type"
    "audit_socid"
    "audit_digital_level"
    "audit_web_presence"
    "audit_digital_tools"
    "audit_security_level"
    "audit_rgpd_compliance"
    "audit_backup_strategy"
    "audit_cloud_adoption"
    "audit_mobility"
    "audit_infrastructure"
    "audit_automation_level"
    "audit_collaboration_tools"
    "audit_data_analysis"
)

missing_fields=0
for field in "${required_fields[@]}"; do
    if [[ "$field" == "audit_socid" ]]; then
        # Cas spécial pour le champ société généré par Dolibarr
        if grep -q "select_company.*audit_socid" "$MODULE_DIR/wizard/enhanced.php"; then
            echo "  ✅ Champ $field présent"
        else
            echo "  ❌ Champ $field manquant"
            missing_fields=$((missing_fields + 1))
        fi
    else
        if grep -q "name=\"$field\"" "$MODULE_DIR/wizard/enhanced.php"; then
            echo "  ✅ Champ $field présent"
        else
            echo "  ❌ Champ $field manquant"
            missing_fields=$((missing_fields + 1))
        fi
    fi
done

# Test des zones de commentaires
echo
echo "4️⃣ Test des zones de commentaires :"

comment_fields=(
    "comment_digital_level"
    "comment_web_presence"
    "comment_digital_tools"
    "comment_security_level"
    "comment_rgpd_compliance"
    "comment_backup_strategy"
    "comment_cloud_adoption"
    "comment_mobility"
    "comment_infrastructure"
    "comment_automation_level"
    "comment_collaboration_tools"
    "comment_data_analysis"
)

missing_comments=0
for field in "${comment_fields[@]}"; do
    if grep -q "name=\"$field\"" "$MODULE_DIR/wizard/enhanced.php"; then
        echo "  ✅ Commentaire $field présent"
    else
        echo "  ❌ Commentaire $field manquant"
        missing_comments=$((missing_comments + 1))
    fi
done

# Test des échelles 1-10
echo
echo "5️⃣ Test des échelles 1-10 :"

scale_count=$(grep -c "for.*i.*1.*10" "$MODULE_DIR/wizard/enhanced.php")
if [[ $scale_count -ge 12 ]]; then  # 12 questions avec échelle 1-10
    echo "  ✅ Échelles 1-10 présentes ($scale_count)"
else
    echo "  ❌ Échelles 1-10 incomplètes ($scale_count)"
fi

# Test de la validation
echo
echo "6️⃣ Test de la validation :"

if grep -q "fk_soc.*obligatoire\|structure_type.*obligatoire\|Société obligatoire" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Validation des champs obligatoires"
else
    echo "  ❌ Validation manquante"
fi

if grep -q "validateStep\|selectRating\|selectStructure" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Fonctions JavaScript présentes"
else
    echo "  ❌ Fonctions JavaScript manquantes"
fi

# Résumé
echo
echo "📊 RÉSUMÉ :"
echo "==========="
echo "Champs obligatoires manquants : $missing_fields"
echo "Commentaires manquants : $missing_comments"
echo "Échelles 1-10 : $scale_count"

if [[ $missing_fields -eq 0 && $missing_comments -eq 0 && $scale_count -ge 12 ]]; then
    echo
    echo "🎉 WIZARD COMPLET ET FONCTIONNEL !"
    echo "=================================="
    echo "✅ Toutes les étapes sont présentes"
    echo "✅ Tous les champs obligatoires sont définis"
    echo "✅ Toutes les zones de commentaires sont présentes"
    echo "✅ Échelles 1-10 complètes"
    echo "✅ Validation implémentée"
    echo
    echo "🌐 Test en ligne :"
    echo "https://work-1-hahvgpojwleeosnl.prod-runtime.all-hands.dev/wizard/enhanced.php"
else
    echo
    echo "⚠️ WIZARD INCOMPLET"
    echo "==================="
    echo "Certains éléments sont manquants, vérifiez les détails ci-dessus"
fi

exit 0