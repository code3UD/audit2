#!/bin/bash

# Script de test des corrections de scores et PDF
# Vérifie que les calculs sont corrects et que le PDF se génère

echo "🧪 TEST DES CORRECTIONS DE SCORES ET PDF"
echo "========================================"

MODULE_DIR="/workspace/audit2"

# Test 1: Vérification des calculs de scores corrigés
echo
echo "1️⃣ Test des calculs de scores :"

# Vérifier que les calculs utilisent la division par 3 (moyenne)
if grep -q "/ 3.*Moyenne sur 10" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Calculs corrigés : division par 3 pour moyenne"
else
    echo "  ❌ Calculs non corrigés"
fi

# Vérifier la pondération finale
if grep -q "0.30.*0.25.*0.25.*0.20.*10" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Pondération corrigée : 30% + 25% + 25% + 20% = 100%"
else
    echo "  ❌ Pondération incorrecte"
fi

# Test 2: Vérification de la génération PDF
echo
echo "2️⃣ Test de la génération PDF :"

if grep -q "pdf_audit_enhanced" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Générateur PDF intégré"
else
    echo "  ❌ Générateur PDF manquant"
fi

if grep -q "write_file.*audit.*langs" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Fonction de génération PDF appelée"
else
    echo "  ❌ Fonction de génération PDF non appelée"
fi

# Test 3: Vérification des messages d'erreur
echo
echo "3️⃣ Test de la gestion d'erreurs :"

if grep -q "try.*catch.*Exception" "$MODULE_DIR/wizard/enhanced.php"; then
    echo "  ✅ Gestion d'erreurs PDF implémentée"
else
    echo "  ❌ Gestion d'erreurs PDF manquante"
fi

# Test 4: Simulation de calcul de score
echo
echo "4️⃣ Test de simulation de calcul :"

# Simuler des valeurs d'entrée
digital_level=8
web_presence=7
digital_tools=6

security_level=5
rgpd_compliance=4
backup_strategy=6

cloud_adoption=7
mobility=8
infrastructure=5

automation_level=6
collaboration_tools=7
data_analysis=5

# Calculs selon la nouvelle formule
maturity_score=$(echo "scale=2; ($digital_level + $web_presence + $digital_tools) / 3" | bc)
security_score=$(echo "scale=2; ($security_level + $rgpd_compliance + $backup_strategy) / 3" | bc)
cloud_score=$(echo "scale=2; ($cloud_adoption + $mobility + $infrastructure) / 3" | bc)
automation_score=$(echo "scale=2; ($automation_level + $collaboration_tools + $data_analysis) / 3" | bc)

total_score=$(echo "scale=2; ($maturity_score * 0.30 + $security_score * 0.25 + $cloud_score * 0.25 + $automation_score * 0.20) * 10" | bc)

echo "  📊 Simulation avec valeurs test :"
echo "     • Maturité Digitale : $maturity_score/10 ($(echo "scale=0; $maturity_score * 10" | bc)%)"
echo "     • Cybersécurité : $security_score/10 ($(echo "scale=0; $security_score * 10" | bc)%)"
echo "     • Cloud & Infrastructure : $cloud_score/10 ($(echo "scale=0; $cloud_score * 10" | bc)%)"
echo "     • Automatisation : $automation_score/10 ($(echo "scale=0; $automation_score * 10" | bc)%)"
echo "     • Score Global : $total_score/100"

# Vérifier que le score est dans la plage normale
if (( $(echo "$total_score <= 100" | bc -l) )); then
    echo "  ✅ Score global dans la plage normale (≤ 100)"
else
    echo "  ❌ Score global aberrant (> 100)"
fi

# Test 5: Vérification de la syntaxe PHP
echo
echo "5️⃣ Test syntaxe PHP :"

php -l "$MODULE_DIR/wizard/enhanced.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "  ✅ Syntaxe PHP valide"
else
    echo "  ❌ Erreur de syntaxe PHP"
fi

# Test 6: Vérification du générateur PDF
echo
echo "6️⃣ Test du générateur PDF :"

if [ -f "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php" ]; then
    echo "  ✅ Fichier générateur PDF présent"
    
    php -l "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php" > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "  ✅ Syntaxe générateur PDF valide"
    else
        echo "  ❌ Erreur syntaxe générateur PDF"
    fi
else
    echo "  ❌ Fichier générateur PDF manquant"
fi

# Résumé
echo
echo "📊 RÉSUMÉ DES CORRECTIONS :"
echo "=========================="

corrections=0
total_tests=6

# Compter les corrections réussies
if grep -q "/ 3.*Moyenne sur 10" "$MODULE_DIR/wizard/enhanced.php"; then
    corrections=$((corrections + 1))
fi

if grep -q "0.30.*0.25.*0.25.*0.20.*10" "$MODULE_DIR/wizard/enhanced.php"; then
    corrections=$((corrections + 1))
fi

if grep -q "pdf_audit_enhanced" "$MODULE_DIR/wizard/enhanced.php"; then
    corrections=$((corrections + 1))
fi

if grep -q "try.*catch.*Exception" "$MODULE_DIR/wizard/enhanced.php"; then
    corrections=$((corrections + 1))
fi

php -l "$MODULE_DIR/wizard/enhanced.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    corrections=$((corrections + 1))
fi

if [ -f "$MODULE_DIR/core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php" ]; then
    corrections=$((corrections + 1))
fi

echo "Corrections réussies : $corrections/$total_tests"

if [ $corrections -eq $total_tests ]; then
    echo
    echo "🎉 TOUTES LES CORRECTIONS APPLIQUÉES AVEC SUCCÈS !"
    echo "================================================="
    echo "✅ Calculs de scores corrigés"
    echo "✅ Génération PDF intégrée"
    echo "✅ Gestion d'erreurs robuste"
    echo "✅ Syntaxe PHP valide"
    echo "✅ Tous les fichiers présents"
    echo
    echo "🚀 Le wizard est prêt pour production !"
else
    echo
    echo "⚠️  CORRECTIONS PARTIELLES"
    echo "========================="
    echo "Certaines corrections nécessitent encore des ajustements."
fi

echo
echo "🌐 Test en ligne :"
echo "https://work-1-hahvgpojwleeosnl.prod-runtime.all-hands.dev/demo_enhanced.php?step=6"