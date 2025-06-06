# 🔧 Résolution des Problèmes Ubuntu 22.04

## Problèmes Identifiés

### 1. Variable `$user` manquante
```
PHP Warning: Undefined variable $user in lib/auditdigital.lib.php on line 120
```

### 2. PHP JSON non installable
```
Package 'php8.1-json' has no installation candidate
```
**Cause :** JSON est intégré dans PHP 8.1, pas besoin de paquet séparé.

### 3. Page inaccessible
**Cause :** Ancienne installation corrompue + permissions incorrectes.

### 4. Avertissement Apache ServerName
```
Could not reliably determine the server's fully qualified domain name
```

## 🚀 Solution Rapide (Recommandée)

### Étape 1 : Télécharger les corrections
```bash
cd /tmp
git pull  # Si vous êtes déjà dans le dépôt
# OU
git clone https://github.com/code2UD/audit2.git
cd audit2
```

### Étape 2 : Exécuter le diagnostic et correction
```bash
sudo ./diagnose_and_fix.sh
```

Ce script va :
- ✅ Diagnostiquer tous les problèmes
- ✅ Nettoyer l'ancienne installation
- ✅ Installer les bonnes dépendances PHP 8.1
- ✅ Corriger la configuration Apache
- ✅ Réinstaller le module proprement
- ✅ Configurer les permissions parfaites
- ✅ Tester l'installation

### Étape 3 : Tester l'accès
```bash
# URLs à tester dans votre navigateur :
http://192.168.1.252/dolibarr/custom/auditdigital/demo_modern.php
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/modern.php
```

## 🔧 Solution Manuelle (Si nécessaire)

### 1. Corriger la variable `$user`
```bash
sudo nano /usr/share/dolibarr/htdocs/custom/auditdigital/lib/auditdigital.lib.php
```

Ligne 76, changer :
```php
global $db, $langs, $conf;
```
En :
```php
global $db, $langs, $conf, $user;
```

### 2. Installer les bonnes dépendances PHP 8.1
```bash
sudo apt-get update
sudo apt-get install -y \
    php8.1-mysqli \
    php8.1-gd \
    php8.1-curl \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-zip \
    libapache2-mod-php8.1

# JSON est déjà intégré dans PHP 8.1, pas besoin de l'installer
```

### 3. Corriger Apache ServerName
```bash
echo "ServerName localhost" | sudo tee -a /etc/apache2/apache2.conf
sudo a2enmod rewrite
sudo a2enmod php8.1
```

### 4. Nettoyer et réinstaller le module
```bash
# Sauvegarder la config si elle existe
sudo cp /usr/share/dolibarr/htdocs/custom/auditdigital/config.php /tmp/config_backup.php 2>/dev/null || true

# Supprimer l'ancienne installation
sudo rm -rf /usr/share/dolibarr/htdocs/custom/auditdigital

# Réinstaller
sudo mkdir -p /usr/share/dolibarr/htdocs/custom/auditdigital
sudo cp -r /tmp/audit2/* /usr/share/dolibarr/htdocs/custom/auditdigital/
```

### 5. Corriger les permissions
```bash
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital
sudo find /usr/share/dolibarr/htdocs/custom/auditdigital -type f -name "*.php" -exec chmod 644 {} \;
sudo find /usr/share/dolibarr/htdocs/custom/auditdigital -type d -exec chmod 755 {} \;
sudo mkdir -p /usr/share/dolibarr/htdocs/custom/auditdigital/{documents,temp,logs}
sudo chmod 777 /usr/share/dolibarr/htdocs/custom/auditdigital/{documents,temp,logs}
```

### 6. Redémarrer Apache
```bash
sudo systemctl restart apache2
sudo systemctl status apache2
```

## 🧪 Tests de Vérification

### 1. Test syntaxe PHP
```bash
php -l /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/index.php
php -l /usr/share/dolibarr/htdocs/custom/auditdigital/lib/auditdigital.lib.php
```

### 2. Test d'accès web
```bash
curl -I http://localhost/dolibarr/custom/auditdigital/demo_modern.php
```

### 3. Vérifier les logs
```bash
sudo tail -f /var/log/apache2/error.log
```

## 📊 Surveillance Continue

### Logs à surveiller
```bash
# Erreurs Apache
sudo tail -f /var/log/apache2/error.log

# Accès Apache
sudo tail -f /var/log/apache2/access.log

# Logs PHP (si configurés)
sudo tail -f /var/log/php8.1-fpm.log
```

### Commandes de diagnostic
```bash
# Statut des services
sudo systemctl status apache2
sudo systemctl status mysql

# Modules Apache
apache2ctl -M | grep -E "(rewrite|php)"

# Extensions PHP
php -m | grep -E "(mysqli|gd|curl|mbstring|json)"

# Permissions
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/
```

## 🆘 En Cas de Problème Persistant

### 1. Vérifier la configuration Dolibarr
- Aller dans l'interface admin Dolibarr
- Vérifier que le module AuditDigital est activé
- Vérifier les permissions utilisateur

### 2. Vérifier les chemins
```bash
# Le module doit être dans :
/usr/share/dolibarr/htdocs/custom/auditdigital/

# Et accessible via :
http://192.168.1.252/dolibarr/custom/auditdigital/
```

### 3. Réinitialisation complète
```bash
# Si tout échoue, réinitialisation complète :
sudo ./diagnose_and_fix.sh
```

## 📞 Support

Si les problèmes persistent après avoir suivi ce guide :

1. **Exécuter le diagnostic** : `sudo ./diagnose_and_fix.sh`
2. **Copier les logs d'erreur** : `sudo tail -20 /var/log/apache2/error.log`
3. **Vérifier la configuration** : `php -l /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/index.php`

---

**Note :** Le script `diagnose_and_fix.sh` est la solution la plus complète et automatisée pour résoudre tous ces problèmes d'un coup.