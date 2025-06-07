#!/bin/bash

# 🧹 Script de Nettoyage de la Documentation AuditDigital
# Supprime les fichiers redondants et organise la documentation

echo "🧹 Début du nettoyage de la documentation..."

# Créer un dossier de sauvegarde avant nettoyage
BACKUP_DIR="documentation_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "📦 Sauvegarde de la documentation actuelle dans $BACKUP_DIR..."
cp -r docs/ "$BACKUP_DIR/" 2>/dev/null || true
cp -r backup_20250605_191503/ "$BACKUP_DIR/" 2>/dev/null || true
cp *.md "$BACKUP_DIR/" 2>/dev/null || true

echo "🗑️ Suppression des dossiers de backup redondants..."
# Supprimer le dossier backup complet
rm -rf backup_20250605_191503/

# Supprimer le dossier test_deploy qui contient des copies
rm -rf test_deploy/

echo "🗑️ Suppression des fichiers de documentation redondants à la racine..."

# Fichiers de déploiement redondants (garder seulement DEPLOYMENT_GUIDE.md)
rm -f DEPLOIEMENT_SERVEUR.md
rm -f DEPLOIEMENT_SIMPLE.md
rm -f GUIDE_DEPLOIEMENT_FINAL.md
rm -f GUIDE_FINAL_DEPLOIEMENT.md
rm -f INSTALLATION_RAPIDE.md

# Fichiers de corrections redondants (garder seulement CORRECTIONS_FINALES.md)
rm -f CORRECTIONS_MISE_EN_PAGE.md
rm -f CORRECTIONS_SCORES_PDF.md

# Fichiers d'améliorations redondants (garder seulement AMELIORATIONS_FINALES.md)
rm -f AMELIORATIONS_FINALES_ACCOMPLIES.md

# Fichiers de résolution redondants (garder seulement GUIDE_FINAL_RESOLUTION.md)
rm -f RESOLUTION_AUDIT_CREATION.md
rm -f RESOLUTION_UBUNTU.md

# Autres fichiers redondants
rm -f SOLUTION_FORMULAIRE.md
rm -f SYNTHESE_FINALE.md
rm -f MISSION_ACCOMPLIE.md

echo "📝 Consolidation de la documentation dans le dossier docs/..."

# Déplacer les fichiers essentiels restants vers docs/
mv DEPLOYMENT_GUIDE.md docs/ 2>/dev/null || true
mv CORRECTIONS_FINALES.md docs/ 2>/dev/null || true
mv AMELIORATIONS_FINALES.md docs/ 2>/dev/null || true
mv GUIDE_FINAL_RESOLUTION.md docs/ 2>/dev/null || true
mv CHANGELOG.md docs/ 2>/dev/null || true

# Garder seulement le README principal et le README wizard moderne
# Supprimer les autres README redondants dans docs/
rm -f docs/README.md  # Garder celui de la racine

echo "🧹 Nettoyage des scripts redondants..."

# Compter les scripts avant nettoyage
SCRIPTS_BEFORE=$(find . -name "*.sh" | wc -l)

# Supprimer les scripts de déploiement redondants (garder les principaux)
rm -f deploy_dev.sh
rm -f deploy_local.sh
rm -f deploy_ubuntu.sh
rm -f deploy_to_dolibarr.sh
rm -f deploy_complete_wizard.sh

# Supprimer les scripts de fix redondants
rm -f fix_all_issues.sh
rm -f fix_audit_card_and_wizard.sh
rm -f fix_audit_creation.sh
rm -f fix_critical_errors.sh
rm -f fix_csrf_and_fksoc.sh
rm -f fix_definitif_audit.sh
rm -f fix_fksoc_final.sh
rm -f fix_modern_wizard.sh
rm -f fix_pdf_issues.sh
rm -f fix_selection_buttons.sh
rm -f fix_ubuntu_installation.sh
rm -f fix_wizard_form.sh

# Supprimer les scripts de test redondants
rm -f test_complete_wizard.sh
rm -f test_enhanced_features.sh
rm -f test_final_improvements.sh
rm -f test_fixes.sh
rm -f test_installation.sh
rm -f test_scores_fixes.sh
rm -f test_server_connection.sh
rm -f test_wizard_steps.sh

# Supprimer les scripts de diagnostic redondants
rm -f diagnose_and_fix.sh
rm -f diagnose_fksoc.sh
rm -f diagnostic_audit_creation.sh

# Supprimer les scripts d'update redondants
rm -f update_local.sh
rm -f update_modern_wizard.sh
rm -f update_server.sh

# Supprimer les scripts de validation redondants
rm -f validate_structure.sh
rm -f validation_finale.sh
rm -f final_check.sh

# Supprimer le script de réorganisation (plus nécessaire)
rm -f reorganize_repository.sh

# Compter les scripts après nettoyage
SCRIPTS_AFTER=$(find . -name "*.sh" | wc -l)

echo "📊 Nettoyage des fichiers wizard redondants..."

# Dans le dossier wizard, supprimer les versions obsolètes
cd wizard/
rm -f index_broken.php
rm -f index_old.php
rm -f index_old_backup.php
rm -f modern_broken.php
rm -f modern_old.php
rm -f test_project_class.php
cd ..

echo "📊 Nettoyage des scripts dans le dossier scripts/..."
# Supprimer les scripts redondants dans le dossier scripts/
rm -f scripts/debug_wizard_500.sh
rm -f scripts/emergency_fix.sh
rm -f scripts/find_project_class.sh
rm -f scripts/fix_all_errors.sh
rm -f scripts/fix_final_errors.sh
rm -f scripts/fix_http500.sh
rm -f scripts/fix_installation_issues.sh
rm -f scripts/fix_permissions.sh
rm -f scripts/fix_wizard_final.sh
rm -f scripts/install_ubuntu.sh
rm -f scripts/quick_fix_pdf.sh
rm -f scripts/test_wizard_complete.php
rm -f scripts/test_wizard_simple.php
rm -f scripts/test_wizard_step_by_step.php

echo "📝 Création d'un README consolidé pour la documentation..."

# Créer un index de la documentation
cat > docs/README.md << 'EOF'
# 📚 Documentation AuditDigital

## 📋 Index de la Documentation

### 🚀 Installation et Déploiement
- **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** - Guide complet de déploiement Git
- **[GUIDE_INSTALLATION.md](GUIDE_INSTALLATION.md)** - Instructions d'installation détaillées

### 📖 Documentation Utilisateur
- **[DOCUMENTATION_UTILISATEUR.md](DOCUMENTATION_UTILISATEUR.md)** - Guide d'utilisation complet
- **[GUIDE_COMMERCIAL.md](GUIDE_COMMERCIAL.md)** - Présentation commerciale du module

### 🔧 Documentation Technique
- **[DOCUMENTATION_TECHNIQUE.md](DOCUMENTATION_TECHNIQUE.md)** - Architecture et développement
- **[MODERNISATION_COMPLETE.md](MODERNISATION_COMPLETE.md)** - Guide de modernisation

### 🐛 Résolution de Problèmes
- **[GUIDE_FINAL_RESOLUTION.md](GUIDE_FINAL_RESOLUTION.md)** - Solutions aux problèmes courants
- **[TROUBLESHOOTING_UBUNTU.md](TROUBLESHOOTING_UBUNTU.md)** - Dépannage spécifique Ubuntu
- **[FIX_HTTP500.md](FIX_HTTP500.md)** - Correction des erreurs HTTP 500

### 🔄 Historique et Améliorations
- **[CHANGELOG.md](CHANGELOG.md)** - Journal des modifications
- **[AMELIORATIONS_FINALES.md](AMELIORATIONS_FINALES.md)** - Dernières améliorations
- **[CORRECTIONS_FINALES.md](CORRECTIONS_FINALES.md)** - Corrections appliquées

### 🧪 Tests et Validation
- **[GUIDE_TEST_WIZARD.md](GUIDE_TEST_WIZARD.md)** - Guide de test du wizard
- **[QUICK_FIX.md](QUICK_FIX.md)** - Solutions rapides

## 📁 Organisation

La documentation est organisée de manière logique :
- **Installation** : Tout ce qui concerne l'installation et le déploiement
- **Utilisation** : Guides pour les utilisateurs finaux
- **Technique** : Documentation pour les développeurs
- **Dépannage** : Solutions aux problèmes courants

## 🚀 Démarrage Rapide

1. **Installation** : Suivez [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
2. **Configuration** : Consultez [GUIDE_INSTALLATION.md](GUIDE_INSTALLATION.md)
3. **Utilisation** : Lisez [DOCUMENTATION_UTILISATEUR.md](DOCUMENTATION_UTILISATEUR.md)
4. **Problèmes** : Référez-vous à [GUIDE_FINAL_RESOLUTION.md](GUIDE_FINAL_RESOLUTION.md)

EOF

echo "✅ Nettoyage terminé !"
echo ""
echo "📊 Résumé du nettoyage :"
echo "   📁 Dossiers supprimés : backup_20250605_191503/, test_deploy/"
echo "   📝 Fichiers MD avant : 52"
echo "   📝 Fichiers MD après : $(find . -name "*.md" | wc -l)"
echo "   🔧 Scripts avant : $SCRIPTS_BEFORE"
echo "   🔧 Scripts après : $SCRIPTS_AFTER"
echo ""
echo "💾 Sauvegarde créée dans : $BACKUP_DIR"
echo "📚 Documentation consolidée dans : docs/"
echo ""
echo "🎯 Prochaines étapes :"
echo "   1. Vérifiez le contenu de docs/"
echo "   2. Testez que l'application fonctionne toujours"
echo "   3. Commitez les changements si tout est OK"