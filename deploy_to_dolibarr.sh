#!/bin/bash

# =============================================================================
# Script de Déploiement vers Dolibarr
# Copie les fichiers depuis /tmp/audit2 vers /usr/share/dolibarr/htdocs/custom/auditdigital/
# =============================================================================

SOURCE_DIR="/tmp/audit2"
TARGET_DIR="/usr/share/dolibarr/htdocs/custom/auditdigital"
BACKUP_DIR="/tmp/auditdigital_backup_$(date +%Y%m%d_%H%M%S)"

echo "🚀 DÉPLOIEMENT AUDITDIGITAL VERS DOLIBARR"
echo "=========================================="
echo

# Vérifications préliminaires
echo "1️⃣ Vérifications préliminaires :"

if [[ ! -d "$SOURCE_DIR" ]]; then
    echo "  ❌ Répertoire source $SOURCE_DIR introuvable"
    exit 1
fi
echo "  ✅ Répertoire source trouvé : $SOURCE_DIR"

if [[ ! -d "/usr/share/dolibarr/htdocs" ]]; then
    echo "  ❌ Installation Dolibarr introuvable"
    exit 1
fi
echo "  ✅ Installation Dolibarr trouvée"

if [[ ! -w "/usr/share/dolibarr/htdocs/custom" ]]; then
    echo "  ❌ Permissions insuffisantes sur /usr/share/dolibarr/htdocs/custom"
    echo "  💡 Exécutez le script avec sudo : sudo $0"
    exit 1
fi
echo "  ✅ Permissions suffisantes"

# Sauvegarde de l'existant
echo
echo "2️⃣ Sauvegarde de l'installation existante :"

if [[ -d "$TARGET_DIR" ]]; then
    echo "  📦 Création de la sauvegarde..."
    cp -r "$TARGET_DIR" "$BACKUP_DIR"
    echo "  ✅ Sauvegarde créée : $BACKUP_DIR"
else
    echo "  ℹ️ Première installation, pas de sauvegarde nécessaire"
fi

# Création du répertoire cible
echo
echo "3️⃣ Préparation du répertoire cible :"

mkdir -p "$TARGET_DIR"
echo "  ✅ Répertoire cible créé/vérifié : $TARGET_DIR"

# Copie des fichiers
echo
echo "4️⃣ Copie des fichiers :"

# Fichiers principaux
files_to_copy=(
    "wizard/modern.php"
    "wizard/enhanced.php"
    "wizard/index.php"
    "class/audit.class.php"
    "class/questionnaire.class.php"
    "class/solutionlibrary.class.php"
    "audit_card.php"
    "audit_list.php"
    "admin/setup.php"
    "lib/auditdigital.lib.php"
    "css/auditdigital.css"
    "css/auditdigital-modern.css"
    "js/wizard.js"
    "js/wizard-modern.js"
    "langs/fr_FR/"
    "sql/"
    "img/"
    "core/"
    "data/"
)

copied_files=0
total_files=${#files_to_copy[@]}

for file in "${files_to_copy[@]}"; do
    if [[ -e "$SOURCE_DIR/$file" ]]; then
        # Créer le répertoire parent si nécessaire
        parent_dir=$(dirname "$TARGET_DIR/$file")
        mkdir -p "$parent_dir"
        
        # Copier le fichier ou répertoire
        cp -r "$SOURCE_DIR/$file" "$TARGET_DIR/$file"
        echo "  ✅ Copié : $file"
        copied_files=$((copied_files + 1))
    else
        echo "  ⚠️ Non trouvé : $file"
    fi
done

echo "  📊 Fichiers copiés : $copied_files/$total_files"

# Copie des fichiers de configuration
echo
echo "5️⃣ Configuration :"

if [[ -f "$SOURCE_DIR/config.php.example" ]]; then
    if [[ ! -f "$TARGET_DIR/config.php" ]]; then
        cp "$SOURCE_DIR/config.php.example" "$TARGET_DIR/config.php"
        echo "  ✅ Fichier de configuration créé"
    else
        echo "  ℹ️ Fichier de configuration existant conservé"
    fi
fi

# Permissions
echo
echo "6️⃣ Configuration des permissions :"

# Permissions générales
chown -R www-data:www-data "$TARGET_DIR" 2>/dev/null || chown -R apache:apache "$TARGET_DIR" 2>/dev/null || echo "  ⚠️ Impossible de changer le propriétaire"
chmod -R 755 "$TARGET_DIR"
echo "  ✅ Permissions de base configurées"

# Permissions spéciales pour les répertoires de données
special_dirs=("documents" "temp" "data")
for dir in "${special_dirs[@]}"; do
    if [[ -d "$TARGET_DIR/$dir" ]]; then
        chmod -R 777 "$TARGET_DIR/$dir"
        echo "  ✅ Permissions étendues pour $dir"
    fi
done

# Vérification de l'installation
echo
echo "7️⃣ Vérification de l'installation :"

# Test syntaxe PHP
php_errors=0
for php_file in "$TARGET_DIR"/*.php "$TARGET_DIR"/wizard/*.php "$TARGET_DIR"/class/*.php; do
    if [[ -f "$php_file" ]]; then
        if ! php -l "$php_file" &>/dev/null; then
            echo "  ❌ Erreur syntaxe : $(basename "$php_file")"
            php_errors=$((php_errors + 1))
        fi
    fi
done

if [[ $php_errors -eq 0 ]]; then
    echo "  ✅ Syntaxe PHP validée"
else
    echo "  ❌ $php_errors erreurs de syntaxe détectées"
fi

# Test des fichiers critiques
critical_files=(
    "wizard/modern.php"
    "class/audit.class.php"
    "audit_card.php"
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

# Résumé final
echo
echo "📋 RÉSUMÉ DU DÉPLOIEMENT :"
echo "=========================="
echo "Source : $SOURCE_DIR"
echo "Cible : $TARGET_DIR"
echo "Sauvegarde : $BACKUP_DIR"
echo "Fichiers copiés : $copied_files/$total_files"
echo "Erreurs PHP : $php_errors"
echo "Fichiers critiques manquants : $missing_critical"

# Status final
echo
if [[ $php_errors -eq 0 && $missing_critical -eq 0 && $copied_files -gt 0 ]]; then
    echo "🎉 DÉPLOIEMENT RÉUSSI !"
    echo "=============================="
    echo "✅ Le module AuditDigital a été déployé avec succès"
    echo "✅ Tous les fichiers critiques sont présents"
    echo "✅ Aucune erreur de syntaxe détectée"
    echo
    echo "🔧 PROCHAINES ÉTAPES :"
    echo "====================="
    echo "1. Connectez-vous à l'interface d'administration Dolibarr"
    echo "2. Allez dans Configuration > Modules"
    echo "3. Activez le module 'AuditDigital'"
    echo "4. Configurez les permissions utilisateurs"
    echo "5. Testez le wizard moderne : /custom/auditdigital/wizard/modern.php"
    echo
    echo "🌐 URLS À TESTER :"
    echo "=================="
    echo "• Wizard moderne : http://votre-dolibarr.com/custom/auditdigital/wizard/modern.php"
    echo "• Wizard amélioré : http://votre-dolibarr.com/custom/auditdigital/wizard/enhanced.php"
    echo "• Liste des audits : http://votre-dolibarr.com/custom/auditdigital/audit_list.php"
    echo
elif [[ $missing_critical -gt 0 ]]; then
    echo "⚠️ DÉPLOIEMENT PARTIEL"
    echo "======================"
    echo "❌ $missing_critical fichiers critiques manquants"
    echo "💡 Vérifiez le répertoire source et relancez le déploiement"
elif [[ $php_errors -gt 0 ]]; then
    echo "⚠️ DÉPLOIEMENT AVEC ERREURS"
    echo "==========================="
    echo "❌ $php_errors erreurs de syntaxe PHP détectées"
    echo "💡 Corrigez les erreurs avant d'utiliser le module"
else
    echo "❌ ÉCHEC DU DÉPLOIEMENT"
    echo "======================"
    echo "💡 Vérifiez les permissions et le répertoire source"
fi

echo
echo "📞 SUPPORT :"
echo "============"
echo "En cas de problème :"
echo "• Vérifiez les logs Apache : tail -f /var/log/apache2/error.log"
echo "• Vérifiez les permissions : ls -la $TARGET_DIR"
echo "• Restaurez la sauvegarde si nécessaire : cp -r $BACKUP_DIR $TARGET_DIR"

exit 0