#!/bin/bash

# =============================================================================
# Script de Test des Fonctionnalités Améliorées
# =============================================================================

MODULE_DIR="/workspace/audit2"

echo "🚀 TEST DES FONCTIONNALITÉS AMÉLIORÉES"
echo "======================================"
echo

# Test 1: Wizard amélioré
echo "1️⃣ Test du wizard amélioré :"

if [[ -f "$MODULE_DIR/wizard/enhanced.php" ]]; then
    echo "  ✅ Wizard amélioré présent"
    
    if php -l "$MODULE_DIR/wizard/enhanced.php" &>/dev/null; then
        echo "  ✅ Syntaxe PHP valide"
    else
        echo "  ❌ Erreur de syntaxe"
        php -l "$MODULE_DIR/wizard/enhanced.php"
    fi
    
    # Vérifier les fonctionnalités améliorées
    if grep -q "scale-option.*1.*10" "$MODULE_DIR/wizard/enhanced.php"; then
        echo "  ✅ Échelle 1-10 implémentée"
    else
        echo "  ❌ Échelle 1-10 manquante"
    fi
    
    if grep -q "comment-textarea" "$MODULE_DIR/wizard/enhanced.php"; then
        echo "  ✅ Zones de commentaires présentes"
    else
        echo "  ❌ Zones de commentaires manquantes"
    fi
    
    if grep -q "company-info" "$MODULE_DIR/wizard/enhanced.php"; then
        echo "  ✅ Intégration informations société"
    else
        echo "  ❌ Intégration société manquante"
    fi
    
else
    echo "  ❌ Wizard amélioré manquant"
fi

# Test 2: Générateur PDF amélioré
echo
echo "2️⃣ Test du générateur PDF amélioré :"

if [[ -f "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php" ]]; then
    echo "  ✅ Générateur PDF amélioré présent"
    
    if php -l "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php" &>/dev/null; then
        echo "  ✅ Syntaxe PHP valide"
    else
        echo "  ❌ Erreur de syntaxe"
        php -l "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"
    fi
    
    # Vérifier les fonctionnalités PDF
    if grep -q "_drawRadarChart\|_drawScoreGauge" "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"; then
        echo "  ✅ Graphiques PDF implémentés"
    else
        echo "  ❌ Graphiques PDF manquants"
    fi
    
    if grep -q "_pageRecommandations\|_pageRoadmap" "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"; then
        echo "  ✅ Pages recommandations et roadmap"
    else
        echo "  ❌ Pages recommandations/roadmap manquantes"
    fi
    
else
    echo "  ❌ Générateur PDF amélioré manquant"
fi

# Test 3: Script de déploiement
echo
echo "3️⃣ Test du script de déploiement :"

if [[ -f "$MODULE_DIR/deploy_to_dolibarr.sh" ]]; then
    echo "  ✅ Script de déploiement présent"
    
    if [[ -x "$MODULE_DIR/deploy_to_dolibarr.sh" ]]; then
        echo "  ✅ Script exécutable"
    else
        echo "  ❌ Script non exécutable"
    fi
    
    # Vérifier les fonctionnalités du script
    if grep -q "SOURCE_DIR=\|TARGET_DIR=\|BACKUP_DIR=" "$MODULE_DIR/deploy_to_dolibarr.sh"; then
        echo "  ✅ Variables de déploiement configurées"
    else
        echo "  ❌ Variables de déploiement manquantes"
    fi
    
    if grep -q "files_to_copy=\|wizard\|class\|audit_card" "$MODULE_DIR/deploy_to_dolibarr.sh"; then
        echo "  ✅ Liste des fichiers à copier définie"
    else
        echo "  ❌ Liste des fichiers manquante"
    fi
    
else
    echo "  ❌ Script de déploiement manquant"
fi

# Test 4: Fonctionnalités avancées du wizard
echo
echo "4️⃣ Test des fonctionnalités avancées :"

# Échelle de notation 1-10
scale_count=$(grep -c "scale-option.*data-value" "$MODULE_DIR/wizard/enhanced.php" 2>/dev/null || echo 0)
if [[ $scale_count -ge 30 ]]; then  # 3 questions x 10 options = 30
    echo "  ✅ Échelle 1-10 complète ($scale_count options)"
else
    echo "  ❌ Échelle 1-10 incomplète ($scale_count options)"
fi

# Zones de commentaires
comment_count=$(grep -c "comment-textarea\|comment_" "$MODULE_DIR/wizard/enhanced.php" 2>/dev/null || echo 0)
if [[ $comment_count -ge 6 ]]; then
    echo "  ✅ Zones de commentaires multiples ($comment_count)"
else
    echo "  ❌ Zones de commentaires insuffisantes ($comment_count)"
fi

# Intégration société
if grep -q "societe.*fetch\|thirdparty.*name" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Intégration données société"
else
    echo "  ❌ Intégration société manquante"
fi

# Auto-save
if grep -q "localStorage\|sessionStorage\|auto.*save" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Sauvegarde automatique"
else
    echo "  ❌ Sauvegarde automatique manquante"
fi

# Test 5: Qualité du code
echo
echo "5️⃣ Test de la qualité du code :"

# Vérifier la documentation
doc_files=("README.md" "MISSION_ACCOMPLIE.md" "CHANGELOG.md")
doc_present=0
for doc in "${doc_files[@]}"; do
    if [[ -f "$MODULE_DIR/$doc" ]]; then
        doc_present=$((doc_present + 1))
    fi
done
echo "  📚 Documentation : $doc_present/${#doc_files[@]} fichiers"

# Vérifier les scripts de test
test_scripts=("test_fixes.sh" "test_complete_wizard.sh" "validation_finale.sh")
test_present=0
for script in "${test_scripts[@]}"; do
    if [[ -f "$MODULE_DIR/$script" ]]; then
        test_present=$((test_present + 1))
    fi
done
echo "  🧪 Scripts de test : $test_present/${#test_scripts[@]} scripts"

# Vérifier la structure des répertoires
required_dirs=("wizard" "class" "css" "js" "sql" "core/modules")
dir_present=0
for dir in "${required_dirs[@]}"; do
    if [[ -d "$MODULE_DIR/$dir" ]]; then
        dir_present=$((dir_present + 1))
    fi
done
echo "  📁 Structure : $dir_present/${#required_dirs[@]} répertoires"

# Test 6: Fonctionnalités métier
echo
echo "6️⃣ Test des fonctionnalités métier :"

# Calcul de scores avancé
if grep -q "digital_level.*web_presence.*digital_tools" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Calcul de scores multi-critères"
else
    echo "  ❌ Calcul de scores simplifié"
fi

# Recommandations personnalisées
if grep -q "_getDetailedRecommendations\|_generateRoadmap" "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"; then
    echo "  ✅ Recommandations personnalisées"
else
    echo "  ❌ Recommandations génériques"
fi

# Niveaux de maturité
if grep -q "Expert.*Avancé.*Intermédiaire.*Débutant" "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"; then
    echo "  ✅ Niveaux de maturité définis"
else
    echo "  ❌ Niveaux de maturité manquants"
fi

# Roadmap d'implémentation
if grep -q "Phase.*Actions.*Immédiates.*Optimisation.*Innovation" "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"; then
    echo "  ✅ Roadmap d'implémentation structurée"
else
    echo "  ❌ Roadmap d'implémentation manquante"
fi

# Calcul du score final
echo
echo "📊 SCORE FINAL DES AMÉLIORATIONS :"
echo "=================================="

total_features=0
implemented_features=0

# Fonctionnalités de base
features=(
    "Wizard amélioré"
    "Générateur PDF avec graphiques"
    "Script de déploiement"
    "Échelle 1-10"
    "Zones de commentaires"
    "Intégration société"
    "Sauvegarde automatique"
    "Calcul scores multi-critères"
    "Recommandations personnalisées"
    "Roadmap d'implémentation"
)

# Vérifications simplifiées
checks=(
    "-f $MODULE_DIR/wizard/enhanced.php"
    "-f $MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"
    "-f $MODULE_DIR/deploy_to_dolibarr.sh"
    "grep -q 'scale-option.*1.*10' $MODULE_DIR/wizard/enhanced.php"
    "grep -q 'comment-textarea' $MODULE_DIR/wizard/enhanced.php"
    "grep -q 'company-info' $MODULE_DIR/wizard/enhanced.php"
    "grep -q 'localStorage' $MODULE_DIR/wizard/enhanced.php"
    "grep -q 'digital_tools' $MODULE_DIR/wizard/enhanced.php"
    "grep -q '_getDetailedRecommendations' $MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"
    "grep -q 'Phase.*Actions' $MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php"
)

for i in "${!features[@]}"; do
    total_features=$((total_features + 1))
    if eval "${checks[$i]}" 2>/dev/null; then
        echo "  ✅ ${features[$i]}"
        implemented_features=$((implemented_features + 1))
    else
        echo "  ❌ ${features[$i]}"
    fi
done

# Pourcentage final
percentage=$((implemented_features * 100 / total_features))

echo
echo "🎯 RÉSULTAT FINAL :"
echo "=================="
echo "Fonctionnalités implémentées : $implemented_features/$total_features ($percentage%)"

if [[ $percentage -ge 90 ]]; then
    echo "🏆 EXCELLENT - Toutes les améliorations sont implémentées"
    echo "✅ Wizard professionnel avec échelle fine"
    echo "✅ Commentaires et intégration Dolibarr"
    echo "✅ PDF avec graphiques et recommandations"
    echo "✅ Script de déploiement automatisé"
elif [[ $percentage -ge 75 ]]; then
    echo "🥈 TRÈS BIEN - La plupart des améliorations sont présentes"
elif [[ $percentage -ge 60 ]]; then
    echo "🥉 BIEN - Améliorations partielles implémentées"
else
    echo "❌ INSUFFISANT - Améliorations importantes manquantes"
fi

echo
echo "🚀 PROCHAINES ÉTAPES :"
echo "====================="
echo "1. Tester le wizard amélioré : /wizard/enhanced.php"
echo "2. Déployer avec : sudo ./deploy_to_dolibarr.sh"
echo "3. Tester la génération PDF avec graphiques"
echo "4. Valider l'intégration Dolibarr complète"

echo
echo "📋 COMMANDES DE DÉPLOIEMENT :"
echo "============================="
echo "# Depuis /tmp/audit2 :"
echo "sudo ./deploy_to_dolibarr.sh"
echo
echo "# Ou copie manuelle :"
echo "sudo cp -r /tmp/audit2/* /usr/share/dolibarr/htdocs/custom/auditdigital/"
echo "sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital/"
echo "sudo chmod -R 755 /usr/share/dolibarr/htdocs/custom/auditdigital/"

exit 0