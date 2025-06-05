# 🚨 Résolution Rapide - Problèmes d'Installation

## Votre Situation Actuelle

D'après votre capture d'écran, vous avez deux problèmes :
- ❌ **Write Permissions** (Not writable)
- ❌ **Module: Projects** (Disabled)

## 🔧 Solution Rapide (2 minutes)

### Étape 1 : Télécharger et exécuter le script de correction

```bash
# Télécharger le script de correction
wget https://raw.githubusercontent.com/12457845124884/audit/main/fix_installation_issues.sh

# Rendre exécutable
chmod +x fix_installation_issues.sh

# Exécuter avec les privilèges root
sudo ./fix_installation_issues.sh
```

### Étape 2 : Si le script ne trouve pas Dolibarr automatiquement

```bash
# Trouver votre installation Dolibarr
sudo find / -name "main.inc.php" -path "*/dolibarr/*" 2>/dev/null

# Utiliser le chemin trouvé (exemple)
sudo ./fix_installation_issues.sh /usr/share/dolibarr/htdocs
```

### Étape 3 : Actualiser la page d'installation

1. Retournez sur votre page d'installation AuditDigital
2. Actualisez la page (F5)
3. Vérifiez que les ❌ sont maintenant des ✅
4. Cliquez sur "INSTALL AUDITDIGITAL MODULE"

## 🔧 Solution Manuelle (si le script ne fonctionne pas)

### Problème 1 : Write Permissions

```bash
# Trouver le répertoire documents Dolibarr
sudo find /var -name "documents" -type d | grep dolibarr

# Exemple de correction (ajustez le chemin)
sudo chown -R www-data:www-data /var/lib/dolibarr/documents
sudo chmod -R 755 /var/lib/dolibarr/documents

# Créer le répertoire AuditDigital
sudo mkdir -p /var/lib/dolibarr/documents/auditdigital
sudo chown -R www-data:www-data /var/lib/dolibarr/documents/auditdigital
sudo chmod -R 755 /var/lib/dolibarr/documents/auditdigital

# Corriger aussi le module
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital
sudo chmod -R 755 /usr/share/dolibarr/htdocs/custom/auditdigital
```

### Problème 2 : Module Projects

**Option A : Via l'interface Dolibarr (recommandé)**
1. Allez dans **Configuration → Modules/Applications**
2. Recherchez "Projet" ou "Project"
3. Cliquez sur **"Activer"**

**Option B : Via la base de données**
```bash
# Se connecter à MySQL (ajustez les paramètres)
mysql -u dolibarr_user -p dolibarr_db

# Activer le module Projets
INSERT INTO llx_const (name, value, type, entity, visible) 
VALUES ('MAIN_MODULE_PROJET', '1', 'chaine', 1, 0) 
ON DUPLICATE KEY UPDATE value='1';

# Quitter MySQL
exit
```

## 🔍 Vérification Rapide

### Test des permissions
```bash
# Tester l'écriture dans le répertoire documents
sudo -u www-data touch /var/lib/dolibarr/documents/auditdigital/test.txt
ls -la /var/lib/dolibarr/documents/auditdigital/test.txt
sudo rm /var/lib/dolibarr/documents/auditdigital/test.txt
```

### Vérifier le module Projets
```bash
# Vérifier dans la base de données
mysql -u dolibarr_user -p dolibarr_db -e "SELECT value FROM llx_const WHERE name='MAIN_MODULE_PROJET';"
```

## 📋 Commandes Complètes par Système

### Ubuntu 22.04 (installation .deb)

```bash
# Correction complète automatique
wget https://raw.githubusercontent.com/12457845124884/audit/main/fix_installation_issues.sh
chmod +x fix_installation_issues.sh
sudo ./fix_installation_issues.sh

# OU correction manuelle
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital
sudo chmod -R 755 /usr/share/dolibarr/htdocs/custom/auditdigital
sudo chown -R www-data:www-data /var/lib/dolibarr/documents
sudo chmod -R 755 /var/lib/dolibarr/documents
sudo mkdir -p /var/lib/dolibarr/documents/auditdigital
sudo systemctl restart apache2
```

### Autres distributions

```bash
# Adapter les chemins selon votre installation
DOLIBARR_PATH="/chemin/vers/dolibarr/htdocs"
DOLIBARR_DOCS="/chemin/vers/dolibarr/documents"

sudo chown -R www-data:www-data "$DOLIBARR_PATH/custom/auditdigital"
sudo chmod -R 755 "$DOLIBARR_PATH/custom/auditdigital"
sudo chown -R www-data:www-data "$DOLIBARR_DOCS"
sudo chmod -R 755 "$DOLIBARR_DOCS"
sudo mkdir -p "$DOLIBARR_DOCS/auditdigital"
sudo systemctl restart apache2
```

## 🚨 Si Ça Ne Fonctionne Toujours Pas

### Diagnostic avancé

```bash
# Vérifier les logs Apache
sudo tail -f /var/log/apache2/error.log

# Vérifier les permissions détaillées
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/
ls -la /var/lib/dolibarr/documents/

# Vérifier la configuration PHP
php -m | grep -E "(gd|mysql|json)"

# Tester l'accès web
curl -I http://localhost/dolibarr/custom/auditdigital/install.php
```

### Permissions ultra-permissives (temporaire)

```bash
# En dernier recours, permissions très ouvertes
sudo chmod -R 777 /var/lib/dolibarr/documents/auditdigital
sudo chmod -R 777 /usr/share/dolibarr/htdocs/custom/auditdigital

# ATTENTION : Remettre des permissions correctes après installation
sudo chmod -R 755 /var/lib/dolibarr/documents/auditdigital
sudo chmod -R 755 /usr/share/dolibarr/htdocs/custom/auditdigital
sudo chown -R www-data:www-data /var/lib/dolibarr/documents/auditdigital
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital
```

## ✅ Résultat Attendu

Après correction, votre page d'installation devrait afficher :

```
✅ PHP Version: 8.1.2-1ubuntu2.21 (OK)
✅ Dolibarr Version: 21.0.1 (OK)  
✅ Database Connection: Connected
✅ Write Permissions: (Writable)
✅ Module: Third Parties: Enabled
✅ Module: Projects: Enabled
```

## 📞 Support

Si les problèmes persistent :

1. **Exécutez le diagnostic** :
   ```bash
   wget https://raw.githubusercontent.com/12457845124884/audit/main/TROUBLESHOOTING_UBUNTU.md
   # Copiez et exécutez le script de diagnostic inclus
   ```

2. **Collectez les informations** :
   ```bash
   # Informations système
   lsb_release -a
   apache2 -v
   php -v
   
   # Permissions
   ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/
   ls -la /var/lib/dolibarr/documents/
   
   # Logs
   sudo tail -20 /var/log/apache2/error.log
   ```

3. **Créez un issue GitHub** avec ces informations

---

**⏱️ Temps estimé de résolution : 2-5 minutes avec le script automatique**