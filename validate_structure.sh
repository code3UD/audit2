#!/bin/bash
# Validation de la nouvelle structure

echo "🔍 VALIDATION DE LA NOUVELLE STRUCTURE"
echo "======================================"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

ERRORS=0

print_info "=== VÉRIFICATION DES DOSSIERS PRINCIPAUX ==="

# Vérifier les dossiers principaux du module
MAIN_DIRS=("class" "core" "wizard" "admin" "lib" "sql" "langs" "css" "js" "img")

for dir in "${MAIN_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_status "Dossier $dir présent"
    else
        print_error "Dossier $dir manquant"
        ((ERRORS++))
    fi
done

print_info "\n=== VÉRIFICATION DES FICHIERS CRITIQUES ==="

# Vérifier les fichiers critiques
CRITICAL_FILES=(
    "class/audit.class.php"
    "wizard/index.php"
    "admin/setup.php"
    "lib/auditdigital.lib.php"
    "core/modules/auditdigital/modules_audit.php"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "Fichier $file présent"
    else
        print_error "Fichier $file manquant"
        ((ERRORS++))
    fi
done

print_info "\n=== VÉRIFICATION DES SCRIPTS GIT ==="

if [ -f "deploy_git.sh" ] && [ -x "deploy_git.sh" ]; then
    print_status "Script deploy_git.sh prêt"
else
    print_error "Script deploy_git.sh manquant ou non exécutable"
    ((ERRORS++))
fi

if [ -f "update_server.sh" ] && [ -x "update_server.sh" ]; then
    print_status "Script update_server.sh prêt"
else
    print_error "Script update_server.sh manquant ou non exécutable"
    ((ERRORS++))
fi

print_info "\n=== VÉRIFICATION DE LA DOCUMENTATION ==="

if [ -d "docs" ] && [ "$(ls -A docs/)" ]; then
    print_status "Documentation organisée dans docs/"
else
    print_warning "Dossier docs vide ou manquant"
fi

if [ -d "scripts" ] && [ "$(ls -A scripts/)" ]; then
    print_status "Scripts organisés dans scripts/"
else
    print_warning "Dossier scripts vide ou manquant"
fi

print_info "\n=== VÉRIFICATION DU README ==="

if [ -f "README.md" ]; then
    if grep -q "deploy_git.sh" README.md; then
        print_status "README mis à jour avec les nouveaux scripts"
    else
        print_warning "README ne mentionne pas les nouveaux scripts"
    fi
else
    print_error "README.md manquant"
    ((ERRORS++))
fi

print_info "\n=== VÉRIFICATION GIT ==="

# Vérifier le statut Git
if git status > /dev/null 2>&1; then
    print_status "Repository Git valide"
    
    # Compter les fichiers modifiés
    MODIFIED=$(git status --porcelain | wc -l)
    if [ "$MODIFIED" -gt 0 ]; then
        print_info "Fichiers modifiés à commiter : $MODIFIED"
    else
        print_status "Aucun fichier en attente de commit"
    fi
else
    print_error "Problème avec le repository Git"
    ((ERRORS++))
fi

print_info "\n=== GÉNÉRATION DU GUIDE DE DÉPLOIEMENT ==="

cat > DEPLOYMENT_GUIDE.md << 'EOF'
# 🚀 Guide de Déploiement Git - AuditDigital

## 📋 Nouvelle Architecture

Le repository a été réorganisé pour un déploiement Git direct :

```
/
├── class/              # Classes PHP du module
├── core/               # Modules de numérotation et PDF  
├── wizard/             # Interface wizard
├── admin/              # Administration
├── lib/                # Bibliothèques
├── sql/                # Scripts SQL
├── langs/              # Traductions
├── css/js/img/         # Assets
├── docs/               # Documentation
├── scripts/            # Scripts utilitaires
├── deploy_git.sh       # 🚀 Déploiement initial
└── update_server.sh    # ⚡ Mise à jour rapide
```

## 🚀 Déploiement Initial

### 1. Première Installation sur le Serveur
```bash
./deploy_git.sh
```

Ce script :
- Clone le repository sur le serveur
- Copie les fichiers du module (sans .git, docs, scripts)
- Applique les permissions correctes
- Redémarre Apache

### 2. Test du Module
```
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

## ⚡ Workflow de Mise à Jour

### 1. Développement Local
```bash
# Modifier le code
# Tester localement

# Commiter les changements
git add .
git commit -m "Description des modifications"
git push
```

### 2. Mise à Jour du Serveur
```bash
./update_server.sh
```

Ce script :
- Met à jour le repository sur le serveur
- Synchronise les fichiers du module
- Applique les permissions
- Redémarre Apache

## 🔧 Commandes Utiles

### Vérification du Statut
```bash
# Statut local
git status

# Statut sur le serveur
ssh root@192.168.1.252 "cd /usr/share/dolibarr/htdocs/custom/auditdigital.git && git status"
```

### Logs du Serveur
```bash
ssh root@192.168.1.252 "tail -f /var/log/apache2/error.log | grep auditdigital"
```

### Rollback d'Urgence
```bash
ssh root@192.168.1.252 "cd /usr/share/dolibarr/htdocs/custom && mv auditdigital auditdigital.broken && mv auditdigital.backup.YYYYMMDD_HHMMSS auditdigital && systemctl restart apache2"
```

## 🎯 Avantages de cette Architecture

1. **Déploiement Simple** : Une seule commande pour déployer
2. **Mises à Jour Rapides** : `git push` + `./update_server.sh`
3. **Historique Complet** : Toutes les versions dans Git
4. **Rollback Facile** : Retour à une version précédente simple
5. **Synchronisation Automatique** : Pas de copie manuelle de fichiers

## 🚨 Important

- Les dossiers `docs/` et `scripts/` ne sont PAS déployés sur le serveur
- Seuls les fichiers du module Dolibarr sont synchronisés
- Les sauvegardes automatiques sont créées à chaque déploiement

---

**Prêt pour un déploiement Git moderne ! 🎉**
EOF

print_status "Guide de déploiement créé : DEPLOYMENT_GUIDE.md"

print_info "\n=== RÉSULTAT FINAL ==="

if [ $ERRORS -eq 0 ]; then
    print_status "🎉 STRUCTURE VALIDÉE AVEC SUCCÈS !"
    echo ""
    print_info "🚀 PROCHAINES ÉTAPES :"
    echo "1. git add ."
    echo "2. git commit -m 'Réorganisation pour déploiement Git direct'"
    echo "3. git push"
    echo "4. ./deploy_git.sh"
    echo ""
    print_info "📋 WORKFLOW DE DÉVELOPPEMENT :"
    echo "- Modifier le code localement"
    echo "- git add . && git commit -m 'Description' && git push"
    echo "- ./update_server.sh"
else
    print_error "🚨 $ERRORS ERREUR(S) DÉTECTÉE(S) !"
    print_info "Corrigez les erreurs avant de continuer."
fi

exit $ERRORS