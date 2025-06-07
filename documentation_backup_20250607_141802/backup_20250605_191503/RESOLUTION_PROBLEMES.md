# 🚨 Résolution des Problèmes d'Installation AuditDigital

## Problèmes Identifiés

D'après votre capture d'écran, voici les problèmes à résoudre :

### ❌ **Problème 1 : Permissions d'écriture**
- **Statut :** Not writable
- **Impact :** Le module ne peut pas créer ses fichiers
- **Priorité :** CRITIQUE

### ❌ **Problème 2 : Module Projets désactivé**
- **Statut :** Disabled
- **Impact :** Fonctionnalités de liaison projet indisponibles
- **Priorité :** IMPORTANTE

---

## 🔧 Solution 1 : Corriger les Permissions

### Méthode A : Script Automatique (Recommandé)

```bash
# 1. Télécharger le script de correction
wget https://raw.githubusercontent.com/12457845124884/audit/main/fix_permissions.sh

# 2. Rendre le script exécutable
chmod +x fix_permissions.sh

# 3. Exécuter en tant que root/sudo
sudo ./fix_permissions.sh
```

### Méthode B : Correction Manuelle

#### Étape 1 : Identifier votre installation Dolibarr

```bash
# Trouver le répertoire Dolibarr
find /var/www -name "dolibarr" -type d 2>/dev/null
find /opt -name "dolibarr" -type d 2>/dev/null
```

#### Étape 2 : Identifier l'utilisateur web

```bash
# Pour Apache
ps aux | grep apache | head -1

# Pour Nginx
ps aux | grep nginx | head -1

# Généralement : www-data, apache, ou nginx
```

#### Étape 3 : Créer les répertoires nécessaires

```bash
# Remplacer /var/www/html/dolibarr par votre chemin
DOLIBARR_PATH="/var/www/html/dolibarr"

# Créer les répertoires
sudo mkdir -p "$DOLIBARR_PATH/htdocs/custom/auditdigital"
sudo mkdir -p "$DOLIBARR_PATH/documents/auditdigital/audit"
sudo mkdir -p "$DOLIBARR_PATH/documents/auditdigital/temp"
```

#### Étape 4 : Corriger les permissions

```bash
# Remplacer www-data par votre utilisateur web
WEB_USER="www-data"

# Permissions du module
sudo chown -R $WEB_USER:$WEB_USER "$DOLIBARR_PATH/htdocs/custom/auditdigital"
sudo chmod -R 755 "$DOLIBARR_PATH/htdocs/custom/auditdigital"

# Permissions des documents
sudo chown -R $WEB_USER:$WEB_USER "$DOLIBARR_PATH/documents/auditdigital"
sudo chmod -R 755 "$DOLIBARR_PATH/documents/auditdigital"

# Permissions générales Dolibarr
sudo chown -R $WEB_USER:$WEB_USER "$DOLIBARR_PATH/documents"
sudo chmod -R 755 "$DOLIBARR_PATH/documents"
```

#### Étape 5 : Vérifier les permissions

```bash
# Tester l'écriture
sudo -u $WEB_USER touch "$DOLIBARR_PATH/documents/auditdigital/test.txt"

# Si ça fonctionne, supprimer le fichier test
sudo rm "$DOLIBARR_PATH/documents/auditdigital/test.txt"
```

---

## 📦 Solution 2 : Activer le Module Projets

### Via l'Interface Dolibarr

1. **Connexion Admin**
   - Connectez-vous en tant qu'administrateur

2. **Accès aux Modules**
   - Menu : `Configuration` → `Modules/Applications`

3. **Rechercher "Projets"**
   - Utilisez la recherche ou naviguez dans les catégories
   - Trouvez le module "Projets/Tâches"

4. **Activation**
   - Cliquez sur le bouton `Activer` à droite du module

5. **Vérification**
   - Le statut doit passer à "Activé" ✅

### Via la Base de Données (Si nécessaire)

```sql
-- Se connecter à MySQL
mysql -u root -p dolibarr_database

-- Activer le module Projets
INSERT INTO llx_const (name, value, type, entity, visible) 
VALUES ('MAIN_MODULE_PROJECT', '1', 'chaine', 1, 0)
ON DUPLICATE KEY UPDATE value = '1';

-- Vérifier l'activation
SELECT name, value FROM llx_const WHERE name = 'MAIN_MODULE_PROJECT';
```

---

## 🔄 Solution 3 : Réinstallation Complète

Si les solutions précédentes ne fonctionnent pas :

### Étape 1 : Nettoyage

```bash
# Supprimer l'installation actuelle
sudo rm -rf /var/www/html/dolibarr/htdocs/custom/auditdigital
sudo rm -rf /var/www/html/dolibarr/documents/auditdigital
```

### Étape 2 : Réinstallation

```bash
# Télécharger le module
git clone https://github.com/12457845124884/audit.git
cd audit

# Copier avec les bonnes permissions
sudo cp -r htdocs/custom/auditdigital /var/www/html/dolibarr/htdocs/custom/
sudo chown -R www-data:www-data /var/www/html/dolibarr/htdocs/custom/auditdigital
sudo chmod -R 755 /var/www/html/dolibarr/htdocs/custom/auditdigital
```

### Étape 3 : Réactiver le module

1. Aller dans `Configuration` → `Modules/Applications`
2. Désactiver AuditDigital (si activé)
3. Réactiver AuditDigital
4. Relancer l'installation

---

## 🧪 Tests de Vérification

### Test 1 : Permissions

```bash
# Tester l'écriture dans le répertoire du module
sudo -u www-data touch /var/www/html/dolibarr/htdocs/custom/auditdigital/test.txt
sudo -u www-data rm /var/www/html/dolibarr/htdocs/custom/auditdigital/test.txt

# Tester l'écriture dans les documents
sudo -u www-data touch /var/www/html/dolibarr/documents/auditdigital/test.txt
sudo -u www-data rm /var/www/html/dolibarr/documents/auditdigital/test.txt
```

### Test 2 : Module Projets

1. Aller dans le menu principal de Dolibarr
2. Vérifier la présence du menu "Projets"
3. Essayer de créer un nouveau projet

### Test 3 : Installation AuditDigital

1. Accéder à `/custom/auditdigital/install.php`
2. Vérifier que tous les tests passent ✅
3. Cliquer sur "Install AuditDigital Module"

---

## 🆘 Dépannage Avancé

### Problème : Permissions toujours incorrectes

**Cause possible :** SELinux activé

```bash
# Vérifier SELinux
sestatus

# Si activé, configurer les contextes
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_unified 1
sudo restorecon -R /var/www/html/dolibarr
```

### Problème : Module ne s'active pas

**Cause possible :** Erreur PHP

```bash
# Vérifier les logs d'erreur
sudo tail -f /var/log/apache2/error.log
# ou
sudo tail -f /var/log/nginx/error.log

# Vérifier la syntaxe PHP
php -l /var/www/html/dolibarr/htdocs/custom/auditdigital/core/modules/modAuditDigital.class.php
```

### Problème : Base de données

**Cause possible :** Tables non créées

```sql
-- Vérifier les tables
SHOW TABLES LIKE 'llx_auditdigital_%';

-- Si absentes, les créer manuellement
SOURCE /var/www/html/dolibarr/htdocs/custom/auditdigital/sql/llx_auditdigital_audit.sql;
SOURCE /var/www/html/dolibarr/htdocs/custom/auditdigital/sql/llx_auditdigital_solutions.sql;
```

---

## 📞 Support

Si les problèmes persistent :

### Informations à collecter

```bash
# Informations système
uname -a
php -v
mysql --version

# Permissions actuelles
ls -la /var/www/html/dolibarr/htdocs/custom/
ls -la /var/www/html/dolibarr/documents/

# Logs d'erreur récents
sudo tail -20 /var/log/apache2/error.log
sudo tail -20 /var/log/dolibarr/dolibarr.log
```

### Contact

- **GitHub Issues :** https://github.com/12457845124884/audit/issues
- **Documentation :** Consultez les guides d'installation détaillés

---

## ✅ Checklist de Résolution

- [ ] Permissions d'écriture corrigées
- [ ] Module Projets activé
- [ ] AuditDigital réinstallé si nécessaire
- [ ] Tests de vérification passés
- [ ] Installation réussie

Une fois tous ces points validés, votre module AuditDigital devrait fonctionner parfaitement ! 🎉