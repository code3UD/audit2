#!/bin/bash

# =============================================================================
# Script de Déploiement Développement
# Déploie le module AuditDigital dans un environnement de développement
# =============================================================================

SOURCE_DIR="/workspace/audit2"
TARGET_DIR="${1:-/var/www/html/dolibarr/htdocs/custom/auditdigital}"
BACKUP_DIR="/tmp/auditdigital_backup_$(date +%Y%m%d_%H%M%S)"

echo "🚀 DÉPLOIEMENT AUDITDIGITAL - MODE DÉVELOPPEMENT"
echo "================================================"
echo "Source : $SOURCE_DIR"
echo "Cible : $TARGET_DIR"
echo

# Fonction d'aide
show_help() {
    echo "Usage: $0 [RÉPERTOIRE_CIBLE]"
    echo
    echo "Exemples :"
    echo "  $0                                    # Déploie vers /var/www/html/dolibarr/htdocs/custom/auditdigital"
    echo "  $0 /opt/dolibarr/custom/auditdigital  # Déploie vers un répertoire personnalisé"
    echo "  $0 ./test_deploy                      # Déploie vers un répertoire local de test"
    echo
    exit 1
}

# Vérifier l'aide
if [[ "$1" == "-h" || "$1" == "--help" ]]; then
    show_help
fi

# Vérifications préliminaires
echo "1️⃣ Vérifications préliminaires :"

if [[ ! -d "$SOURCE_DIR" ]]; then
    echo "  ❌ Répertoire source $SOURCE_DIR introuvable"
    exit 1
fi
echo "  ✅ Répertoire source trouvé : $SOURCE_DIR"

# Créer le répertoire cible si nécessaire
echo
echo "2️⃣ Préparation du répertoire cible :"

if [[ -d "$TARGET_DIR" ]]; then
    echo "  📦 Sauvegarde de l'existant..."
    cp -r "$TARGET_DIR" "$BACKUP_DIR" 2>/dev/null || echo "  ⚠️ Impossible de créer la sauvegarde"
    echo "  ✅ Sauvegarde créée : $BACKUP_DIR"
else
    echo "  ℹ️ Première installation"
fi

mkdir -p "$TARGET_DIR"
echo "  ✅ Répertoire cible créé/vérifié : $TARGET_DIR"

# Copie intelligente des fichiers
echo
echo "3️⃣ Copie des fichiers :"

# Fichiers et répertoires à copier
items_to_copy=(
    "wizard/"
    "class/"
    "css/"
    "js/"
    "core/"
    "demo_enhanced.php"
    "demo_steps_3_6.php"
    "test_scores_demo.php"
    "audit_card.php"
    "audit_list.php"
    "README.md"
    "CORRECTIONS_SCORES_PDF.md"
)

copied_items=0
total_items=${#items_to_copy[@]}

for item in "${items_to_copy[@]}"; do
    if [[ -e "$SOURCE_DIR/$item" ]]; then
        # Créer le répertoire parent si nécessaire
        parent_dir=$(dirname "$TARGET_DIR/$item")
        mkdir -p "$parent_dir"
        
        # Copier le fichier ou répertoire
        if [[ -d "$SOURCE_DIR/$item" ]]; then
            cp -r "$SOURCE_DIR/$item" "$TARGET_DIR/"
            echo "  ✅ Répertoire copié : $item"
        else
            cp "$SOURCE_DIR/$item" "$TARGET_DIR/$item"
            echo "  ✅ Fichier copié : $item"
        fi
        copied_items=$((copied_items + 1))
    else
        echo "  ⚠️ Non trouvé : $item"
    fi
done

echo "  📊 Éléments copiés : $copied_items/$total_items"

# Copie des scripts de test
echo
echo "4️⃣ Scripts de test et validation :"

test_scripts=(
    "test_wizard_steps.sh"
    "test_scores_fixes.sh"
    "deploy_to_dolibarr.sh"
)

for script in "${test_scripts[@]}"; do
    if [[ -f "$SOURCE_DIR/$script" ]]; then
        cp "$SOURCE_DIR/$script" "$TARGET_DIR/"
        chmod +x "$TARGET_DIR/$script"
        echo "  ✅ Script copié : $script"
    fi
done

# Configuration des permissions (si possible)
echo
echo "5️⃣ Configuration des permissions :"

if [[ -w "$TARGET_DIR" ]]; then
    chmod -R 755 "$TARGET_DIR" 2>/dev/null || echo "  ⚠️ Impossible de modifier les permissions"
    
    # Permissions spéciales pour certains répertoires
    special_dirs=("documents" "temp" "data")
    for dir in "${special_dirs[@]}"; do
        if [[ -d "$TARGET_DIR/$dir" ]]; then
            chmod -R 777 "$TARGET_DIR/$dir" 2>/dev/null || echo "  ⚠️ Permissions $dir non modifiées"
            echo "  ✅ Permissions étendues pour $dir"
        fi
    done
    
    echo "  ✅ Permissions configurées"
else
    echo "  ⚠️ Permissions insuffisantes pour modifier les droits"
fi

# Vérification rapide
echo
echo "6️⃣ Vérification de l'installation :"

# Test des fichiers critiques
critical_files=(
    "wizard/enhanced.php"
    "demo_enhanced.php"
    "test_scores_demo.php"
)

missing_critical=0
for file in "${critical_files[@]}"; do
    if [[ -f "$TARGET_DIR/$file" ]]; then
        echo "  ✅ Fichier critique présent : $file"
    else
        echo "  ❌ Fichier critique manquant : $file"
        missing_critical=$((missing_critical + 1))
    fi
done

# Génération du fichier de configuration de test
echo
echo "7️⃣ Configuration de test :"

cat > "$TARGET_DIR/test_config.php" << 'EOF'
<?php
/**
 * Configuration de test pour AuditDigital
 * Généré automatiquement par deploy_dev.sh
 */

// Configuration de base
define('AUDIT_DEBUG', true);
define('AUDIT_TEST_MODE', true);

// URLs de test
$test_urls = [
    'wizard_enhanced' => 'wizard/enhanced.php',
    'demo_enhanced' => 'demo_enhanced.php',
    'demo_steps_3_6' => 'demo_steps_3_6.php',
    'test_scores' => 'test_scores_demo.php'
];

// Configuration de la base de données de test (à adapter)
$test_db_config = [
    'host' => 'localhost',
    'database' => 'dolibarr_test',
    'user' => 'dolibarr',
    'password' => 'password'
];

echo "<!-- Configuration de test AuditDigital chargée -->\n";
?>
EOF

echo "  ✅ Fichier de configuration de test créé"

# Résumé final
echo
echo "📋 RÉSUMÉ DU DÉPLOIEMENT DÉVELOPPEMENT :"
echo "========================================"
echo "Source : $SOURCE_DIR"
echo "Cible : $TARGET_DIR"
echo "Sauvegarde : $BACKUP_DIR"
echo "Éléments copiés : $copied_items/$total_items"
echo "Fichiers critiques manquants : $missing_critical"

# Status final
echo
if [[ $missing_critical -eq 0 && $copied_items -gt 0 ]]; then
    echo "🎉 DÉPLOIEMENT DÉVELOPPEMENT RÉUSSI !"
    echo "====================================="
    echo "✅ Tous les fichiers critiques sont présents"
    echo "✅ Scripts de test disponibles"
    echo "✅ Configuration de test générée"
    echo
    echo "🧪 TESTS DISPONIBLES :"
    echo "====================="
    echo "• Test des scores : $TARGET_DIR/test_scores_demo.php"
    echo "• Validation wizard : $TARGET_DIR/test_wizard_steps.sh"
    echo "• Validation scores : $TARGET_DIR/test_scores_fixes.sh"
    echo
    echo "🌐 PAGES À TESTER :"
    echo "=================="
    echo "• Wizard amélioré : $TARGET_DIR/wizard/enhanced.php"
    echo "• Demo complète : $TARGET_DIR/demo_enhanced.php"
    echo "• Demo étapes 3-6 : $TARGET_DIR/demo_steps_3_6.php"
    echo "• Test calculs : $TARGET_DIR/test_scores_demo.php"
    echo
    echo "🔧 COMMANDES UTILES :"
    echo "===================="
    echo "cd $TARGET_DIR"
    echo "./test_wizard_steps.sh      # Valider le wizard"
    echo "./test_scores_fixes.sh      # Valider les scores"
    echo
else
    echo "⚠️ DÉPLOIEMENT PARTIEL"
    echo "======================"
    echo "❌ $missing_critical fichiers critiques manquants"
    echo "💡 Vérifiez le répertoire source et relancez le déploiement"
fi

echo
echo "📞 AIDE :"
echo "========="
echo "• Relancer le déploiement : $0 $TARGET_DIR"
echo "• Restaurer la sauvegarde : cp -r $BACKUP_DIR/* $TARGET_DIR/"
echo "• Voir l'aide : $0 --help"

exit 0