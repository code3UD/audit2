# Installation AuditDigital - Ubuntu 22.04 (Paquets .deb)

## 🔍 Identification des Chemins Dolibarr

### Chemins typiques selon l'installation

**Installation via paquets .deb Ubuntu :**
```bash
# Chemins les plus courants
/usr/share/dolibarr/htdocs/
/var/lib/dolibarr/htdocs/
/usr/dolibarr/htdocs/
```

**Vérification de votre installation :**
```bash
# Méthode 1 : Recherche des fichiers Dolibarr
sudo find / -name "main.inc.php" -type f 2>/dev/null | grep dolibarr

# Méthode 2 : Via les paquets installés
dpkg -L dolibarr | grep htdocs

# Méthode 3 : Configuration Apache/Nginx
sudo grep -r "dolibarr" /etc/apache2/sites-available/
sudo grep -r "dolibarr" /etc/nginx/sites-available/

# Méthode 4 : Via les processus
ps aux | grep dolibarr
```

## 📁 Installation du Module

### Étape 1 : Identifier le bon chemin

```bash
# Tester les chemins courants
for path in "/usr/share/dolibarr/htdocs" "/var/lib/dolibarr/htdocs" "/usr/dolibarr/htdocs" "/opt/dolibarr/htdocs"; do
    if [ -d "$path" ]; then
        echo "✅ Trouvé : $path"
        if [ -f "$path/main.inc.php" ]; then
            echo "✅ Confirmé : $path contient main.inc.php"
            DOLIBARR_PATH="$path"
            break
        fi
    else
        echo "❌ Non trouvé : $path"
    fi
done

echo "Chemin Dolibarr détecté : $DOLIBARR_PATH"
```

### Étape 2 : Créer le répertoire custom

```bash
# Utiliser le chemin détecté (remplacez par votre chemin réel)
DOLIBARR_PATH="/usr/share/dolibarr/htdocs"  # Ajustez selon votre installation

# Créer le répertoire custom s'il n'existe pas
sudo mkdir -p "$DOLIBARR_PATH/custom"

# Vérifier les permissions
ls -la "$DOLIBARR_PATH/"
```

### Étape 3 : Copier le module

```bash
# Cloner le repository
cd /tmp
git clone https://github.com/12457845124884/audit.git

# Copier le module dans le bon répertoire
sudo cp -r audit/htdocs/custom/auditdigital "$DOLIBARR_PATH/custom/"

# Ajuster les permissions
sudo chown -R www-data:www-data "$DOLIBARR_PATH/custom/auditdigital"
sudo chmod -R 755 "$DOLIBARR_PATH/custom/auditdigital"
```

### Étape 4 : Vérifier l'installation

```bash
# Vérifier que les fichiers sont bien copiés
ls -la "$DOLIBARR_PATH/custom/auditdigital/"

# Vérifier les fichiers clés
test -f "$DOLIBARR_PATH/custom/auditdigital/core/modules/modAuditDigital.class.php" && echo "✅ Module principal OK"
test -f "$DOLIBARR_PATH/custom/auditdigital/install.php" && echo "✅ Script d'installation OK"
test -f "$DOLIBARR_PATH/custom/auditdigital/class/audit.class.php" && echo "✅ Classes métier OK"
```

## 🌐 Accès via le Navigateur

### URLs d'accès selon votre configuration

**Si Dolibarr est accessible via :**
- `http://localhost/dolibarr` → `http://localhost/dolibarr/custom/auditdigital/install.php`
- `http://votre-domaine.com/dolibarr` → `http://votre-domaine.com/dolibarr/custom/auditdigital/install.php`
- `http://localhost` (racine) → `http://localhost/custom/auditdigital/install.php`

### Vérification de l'URL Dolibarr

```bash
# Vérifier la configuration Apache
sudo cat /etc/apache2/sites-available/000-default.conf | grep -i dolibarr
sudo cat /etc/apache2/sites-available/dolibarr.conf 2>/dev/null

# Vérifier les alias Apache
sudo grep -r "Alias.*dolibarr" /etc/apache2/

# Tester l'accès Dolibarr principal
curl -I http://localhost/dolibarr/ 2>/dev/null | head -1
```

## 🔧 Configuration Spécifique Ubuntu

### Permissions et Sécurité

```bash
# Permissions recommandées pour Ubuntu
sudo chown -R www-data:www-data "$DOLIBARR_PATH/custom/auditdigital"
sudo find "$DOLIBARR_PATH/custom/auditdigital" -type d -exec chmod 755 {} \;
sudo find "$DOLIBARR_PATH/custom/auditdigital" -type f -exec chmod 644 {} \;

# Rendre les scripts PHP exécutables
sudo chmod 755 "$DOLIBARR_PATH/custom/auditdigital/install.php"
sudo chmod 755 "$DOLIBARR_PATH/custom/auditdigital/test.php"
sudo chmod 755 "$DOLIBARR_PATH/custom/auditdigital/demo.php"
```

### Configuration PHP pour Ubuntu

```bash
# Vérifier la configuration PHP
php -v
php -m | grep -E "(gd|json|mysql|pdo)"

# Installer les extensions manquantes si nécessaire
sudo apt update
sudo apt install php-gd php-mysql php-json php-mbstring php-xml php-zip

# Redémarrer Apache
sudo systemctl restart apache2
```

### Répertoires de Documents

```bash
# Identifier le répertoire documents Dolibarr
sudo find /var -name "documents" -type d | grep dolibarr

# Créer le répertoire pour AuditDigital
DOLIBARR_DOCUMENTS="/var/lib/dolibarr/documents"  # Ajustez selon votre config
sudo mkdir -p "$DOLIBARR_DOCUMENTS/auditdigital"
sudo chown -R www-data:www-data "$DOLIBARR_DOCUMENTS/auditdigital"
sudo chmod -R 755 "$DOLIBARR_DOCUMENTS/auditdigital"
```

## 🚀 Installation Automatisée

### Script d'installation complet

```bash
#!/bin/bash
# install_auditdigital_ubuntu.sh

echo "🔍 Détection de l'installation Dolibarr..."

# Détecter le chemin Dolibarr
DOLIBARR_PATHS=(
    "/usr/share/dolibarr/htdocs"
    "/var/lib/dolibarr/htdocs" 
    "/usr/dolibarr/htdocs"
    "/opt/dolibarr/htdocs"
    "/var/www/html/dolibarr/htdocs"
)

DOLIBARR_PATH=""
for path in "${DOLIBARR_PATHS[@]}"; do
    if [ -f "$path/main.inc.php" ]; then
        DOLIBARR_PATH="$path"
        echo "✅ Dolibarr trouvé : $path"
        break
    fi
done

if [ -z "$DOLIBARR_PATH" ]; then
    echo "❌ Dolibarr non trouvé. Veuillez spécifier le chemin manuellement."
    echo "Usage: $0 /chemin/vers/dolibarr/htdocs"
    exit 1
fi

# Utiliser le chemin fourni en paramètre si spécifié
if [ ! -z "$1" ]; then
    DOLIBARR_PATH="$1"
    echo "📁 Utilisation du chemin spécifié : $DOLIBARR_PATH"
fi

echo "📦 Téléchargement du module AuditDigital..."
cd /tmp
rm -rf audit
git clone https://github.com/12457845124884/audit.git

echo "📁 Installation du module..."
sudo mkdir -p "$DOLIBARR_PATH/custom"
sudo cp -r audit/htdocs/custom/auditdigital "$DOLIBARR_PATH/custom/"

echo "🔐 Configuration des permissions..."
sudo chown -R www-data:www-data "$DOLIBARR_PATH/custom/auditdigital"
sudo chmod -R 755 "$DOLIBARR_PATH/custom/auditdigital"

echo "🌐 Redémarrage d'Apache..."
sudo systemctl restart apache2

echo "✅ Installation terminée !"
echo ""
echo "📋 Prochaines étapes :"
echo "1. Accédez à votre Dolibarr"
echo "2. Allez dans Configuration → Modules/Applications"
echo "3. Recherchez 'AuditDigital' et activez-le"
echo "4. Ou accédez directement à l'installation : http://votre-dolibarr/custom/auditdigital/install.php"
echo ""
echo "🔗 URLs utiles :"
echo "- Installation : http://localhost/dolibarr/custom/auditdigital/install.php"
echo "- Tests : http://localhost/dolibarr/custom/auditdigital/test.php"
echo "- Démo : http://localhost/dolibarr/custom/auditdigital/demo.php"
```

### Utilisation du script

```bash
# Télécharger et exécuter le script
wget https://raw.githubusercontent.com/12457845124884/audit/main/install_ubuntu.sh
chmod +x install_ubuntu.sh

# Installation automatique
sudo ./install_ubuntu.sh

# Ou avec un chemin spécifique
sudo ./install_ubuntu.sh /usr/share/dolibarr/htdocs
```

## 🔍 Diagnostic des Problèmes

### Le module n'apparaît pas dans la liste

```bash
# Vérifier que le fichier principal existe
test -f "$DOLIBARR_PATH/custom/auditdigital/core/modules/modAuditDigital.class.php" && echo "✅ Fichier module OK" || echo "❌ Fichier module manquant"

# Vérifier les permissions
ls -la "$DOLIBARR_PATH/custom/auditdigital/core/modules/"

# Vérifier les logs d'erreur
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/dolibarr/dolibarr.log 2>/dev/null
```

### install.php non accessible

```bash
# Vérifier l'existence du fichier
ls -la "$DOLIBARR_PATH/custom/auditdigital/install.php"

# Tester l'URL directement
curl -I http://localhost/dolibarr/custom/auditdigital/install.php

# Vérifier la configuration Apache
sudo apache2ctl configtest
```

### Erreurs de permissions

```bash
# Réinitialiser toutes les permissions
sudo chown -R www-data:www-data "$DOLIBARR_PATH/custom/auditdigital"
sudo find "$DOLIBARR_PATH/custom/auditdigital" -type d -exec chmod 755 {} \;
sudo find "$DOLIBARR_PATH/custom/auditdigital" -type f -exec chmod 644 {} \;

# Vérifier les permissions du répertoire parent
ls -la "$DOLIBARR_PATH/custom/"
```

## 📞 Support Spécifique Ubuntu

### Commandes de diagnostic

```bash
# Informations système
lsb_release -a
apache2 -v
php -v
mysql --version

# Status des services
sudo systemctl status apache2
sudo systemctl status mysql

# Logs en temps réel
sudo tail -f /var/log/apache2/error.log &
sudo tail -f /var/log/apache2/access.log &
```

### Redémarrage complet

```bash
# Redémarrer tous les services
sudo systemctl restart apache2
sudo systemctl restart mysql

# Vider les caches
sudo rm -rf /tmp/dolibarr_*
sudo service apache2 reload
```

---

**Note :** Remplacez `/usr/share/dolibarr/htdocs` par le chemin réel de votre installation Dolibarr détecté lors de l'étape de vérification.