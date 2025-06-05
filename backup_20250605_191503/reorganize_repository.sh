#!/bin/bash
# Script de réorganisation du repository pour déploiement Git direct

echo "🔄 RÉORGANISATION DU REPOSITORY AUDITDIGITAL"
echo "============================================"

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

# Vérifier qu'on est dans le bon répertoire
if [ ! -d "htdocs/custom/auditdigital" ]; then
    print_error "Structure actuelle non trouvée. Exécutez depuis la racine du repository."
    exit 1
fi

print_info "=== ÉTAPE 1: SAUVEGARDE ==="

# Créer une sauvegarde
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
cp -r htdocs/ "$BACKUP_DIR/"
cp -r *.md *.sh *.php *.txt "$BACKUP_DIR/" 2>/dev/null || true

print_status "Sauvegarde créée dans $BACKUP_DIR"

print_info "=== ÉTAPE 2: DÉPLACEMENT DES FICHIERS DU MODULE ==="

# Déplacer tous les fichiers du module vers la racine
cp -r htdocs/custom/auditdigital/* ./
print_status "Fichiers du module déplacés vers la racine"

print_info "=== ÉTAPE 3: CRÉATION DE LA STRUCTURE DOCS ==="

# Créer un dossier docs pour la documentation
mkdir -p docs/
mv *.md docs/ 2>/dev/null || true
mv GUIDE_* docs/ 2>/dev/null || true
mv DOCUMENTATION_* docs/ 2>/dev/null || true
mv RESOLUTION_* docs/ 2>/dev/null || true
mv TROUBLESHOOTING_* docs/ 2>/dev/null || true
mv INSTALLATION_* docs/ 2>/dev/null || true

print_status "Documentation déplacée dans docs/"

print_info "=== ÉTAPE 4: CRÉATION DE LA STRUCTURE SCRIPTS ==="

# Créer un dossier scripts pour les utilitaires
mkdir -p scripts/
mv *fix*.sh scripts/ 2>/dev/null || true
mv debug_*.* scripts/ 2>/dev/null || true
mv test_*.php scripts/ 2>/dev/null || true
mv install_*.sh scripts/ 2>/dev/null || true
mv find_*.sh scripts/ 2>/dev/null || true
mv quick_*.sh scripts/ 2>/dev/null || true

print_status "Scripts déplacés dans scripts/"

print_info "=== ÉTAPE 5: NETTOYAGE ==="

# Supprimer l'ancienne structure
rm -rf htdocs/

print_status "Ancienne structure supprimée"

print_info "=== ÉTAPE 6: CRÉATION DES SCRIPTS GIT ==="

# Créer le script de déploiement Git
cat > deploy_git.sh << 'EOF'
#!/bin/bash
# Déploiement Git direct sur le serveur

echo "🚀 DÉPLOIEMENT GIT AUDITDIGITAL"
echo "==============================="

SERVER_IP="192.168.1.252"
SERVER_USER="root"
DOLIBARR_PATH="/usr/share/dolibarr/htdocs/custom"
MODULE_NAME="auditdigital"

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

# Test de connectivité
print_info "Test de connectivité..."
if ! ping -c 1 "$SERVER_IP" > /dev/null 2>&1; then
    print_error "Serveur non accessible"
    exit 1
fi

print_status "Serveur accessible"

# Déploiement sur le serveur
print_info "Déploiement sur le serveur..."

ssh "$SERVER_USER@$SERVER_IP" << EOSSH
echo "🔧 DÉPLOIEMENT SUR LE SERVEUR"
echo "============================="

cd "$DOLIBARR_PATH"

# Sauvegarde si le module existe
if [ -d "$MODULE_NAME" ]; then
    echo "Sauvegarde de l'ancien module..."
    mv "$MODULE_NAME" "${MODULE_NAME}.backup.\$(date +%Y%m%d_%H%M%S)"
fi

# Cloner ou mettre à jour le repository
if [ ! -d "${MODULE_NAME}.git" ]; then
    echo "Clonage initial du repository..."
    git clone https://github.com/code2UD/audit2.git "${MODULE_NAME}.git"
else
    echo "Mise à jour du repository..."
    cd "${MODULE_NAME}.git"
    git pull origin main
    cd ..
fi

# Copier les fichiers du module (sans .git, docs, scripts)
echo "Copie des fichiers du module..."
rsync -av --exclude='.git' --exclude='docs' --exclude='scripts' --exclude='backup_*' "${MODULE_NAME}.git/" "$MODULE_NAME/"

# Appliquer les corrections
echo "Application des corrections..."
chown -R www-data:www-data "$MODULE_NAME"
chmod -R 644 "$MODULE_NAME"
find "$MODULE_NAME" -type d -exec chmod 755 {} \;

# Créer les répertoires nécessaires
mkdir -p /var/lib/dolibarr/documents/auditdigital
chown -R www-data:www-data /var/lib/dolibarr/documents/auditdigital
chmod -R 755 /var/lib/dolibarr/documents/auditdigital

# Redémarrer Apache
systemctl restart apache2

echo ""
echo "✅ DÉPLOIEMENT TERMINÉ !"
echo "🧪 Test: http://$SERVER_IP/dolibarr/custom/auditdigital/wizard/index.php"

EOSSH

if [ $? -eq 0 ]; then
    print_status "Déploiement réussi !"
    echo ""
    print_info "🧪 TESTEZ MAINTENANT :"
    echo "http://$SERVER_IP/dolibarr/custom/auditdigital/wizard/index.php"
else
    print_error "Erreur lors du déploiement"
    exit 1
fi
EOF

chmod +x deploy_git.sh

# Créer le script de mise à jour rapide
cat > update_server.sh << 'EOF'
#!/bin/bash
# Mise à jour rapide du serveur

echo "⚡ MISE À JOUR RAPIDE SERVEUR"
echo "============================"

SERVER_IP="192.168.1.252"
SERVER_USER="root"
DOLIBARR_PATH="/usr/share/dolibarr/htdocs/custom"

ssh "$SERVER_USER@$SERVER_IP" << 'EOSSH'
cd /usr/share/dolibarr/htdocs/custom/auditdigital.git
git pull origin main
rsync -av --exclude='.git' --exclude='docs' --exclude='scripts' --exclude='backup_*' ./ ../auditdigital/
chown -R www-data:www-data ../auditdigital
systemctl restart apache2
echo "✅ Mise à jour terminée !"
EOSSH
EOF

chmod +x update_server.sh

print_status "Scripts Git créés : deploy_git.sh et update_server.sh"

print_info "=== ÉTAPE 7: CRÉATION DU NOUVEAU README ==="

# Créer un nouveau README adapté
cat > README.md << 'EOF'
# 🔍 AuditDigital - Module Dolibarr

Module complet d'audit de maturité numérique pour Dolibarr.

## 🚀 Déploiement Rapide

### Première Installation
```bash
./deploy_git.sh
```

### Mises à Jour
```bash
# Depuis votre machine de développement
git add .
git commit -m "Mise à jour"
git push

# Puis mise à jour du serveur
./update_server.sh
```

## 🧪 Test du Module
```
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

## 📁 Structure du Projet

```
/
├── class/              # Classes PHP du module
├── core/               # Modules de numérotation et PDF
├── wizard/             # Interface wizard
├── admin/              # Administration
├── lib/                # Bibliothèques
├── docs/               # Documentation complète
├── scripts/            # Scripts utilitaires
├── deploy_git.sh       # Déploiement initial
└── update_server.sh    # Mise à jour rapide
```

## 🔧 Workflow de Développement

1. **Modifier le code localement**
2. **Tester les modifications**
3. **Commiter et pusher**
   ```bash
   git add .
   git commit -m "Description des modifications"
   git push
   ```
4. **Mettre à jour le serveur**
   ```bash
   ./update_server.sh
   ```

## 📋 Documentation

Consultez le dossier `docs/` pour :
- Guide d'installation
- Documentation technique
- Guide utilisateur
- Résolution des problèmes

---

**Prêt pour production ! 🎉**
EOF

print_status "Nouveau README créé"

print_info "=== RÉSULTAT FINAL ==="

echo ""
print_status "🎉 RÉORGANISATION TERMINÉE !"
echo ""
print_info "📁 NOUVELLE STRUCTURE :"
echo "- Fichiers du module à la racine"
echo "- Documentation dans docs/"
echo "- Scripts dans scripts/"
echo "- Scripts Git : deploy_git.sh et update_server.sh"
echo ""
print_info "🚀 PROCHAINES ÉTAPES :"
echo "1. git add ."
echo "2. git commit -m 'Réorganisation pour déploiement Git direct'"
echo "3. git push"
echo "4. ./deploy_git.sh"
echo ""
print_warning "⚠️  Sauvegarde disponible dans : $BACKUP_DIR"

exit 0