# Dépannage AuditDigital - Ubuntu 22.04

## 🚨 Problèmes Courants et Solutions

### 1. Le module n'apparaît pas dans la liste des modules

#### Diagnostic
```bash
# Vérifier l'emplacement du module
sudo find /usr -name "modAuditDigital.class.php" 2>/dev/null
sudo find /var -name "modAuditDigital.class.php" 2>/dev/null

# Vérifier les permissions
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/core/modules/
```

#### Solutions
```bash
# Solution 1 : Vérifier le chemin correct
DOLIBARR_PATHS=(
    "/usr/share/dolibarr/htdocs"
    "/var/lib/dolibarr/htdocs"
    "/usr/dolibarr/htdocs"
    "/var/www/html/dolibarr/htdocs"
)

for path in "${DOLIBARR_PATHS[@]}"; do
    if [ -f "$path/main.inc.php" ]; then
        echo "✅ Dolibarr trouvé : $path"
        DOLIBARR_PATH="$path"
        break
    fi
done

# Solution 2 : Réinstaller dans le bon répertoire
sudo rm -rf "$DOLIBARR_PATH/custom/auditdigital"
cd /tmp
git clone https://github.com/12457845124884/audit.git
sudo cp -r audit/htdocs/custom/auditdigital "$DOLIBARR_PATH/custom/"
sudo chown -R www-data:www-data "$DOLIBARR_PATH/custom/auditdigital"

# Solution 3 : Vérifier la syntaxe PHP
php -l "$DOLIBARR_PATH/custom/auditdigital/core/modules/modAuditDigital.class.php"
```

### 2. install.php non accessible (404 Not Found)

#### Diagnostic
```bash
# Vérifier l'existence du fichier
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/install.php

# Tester l'URL Dolibarr de base
curl -I http://localhost/dolibarr/
curl -I http://localhost/

# Vérifier la configuration Apache
sudo apache2ctl configtest
sudo grep -r "dolibarr" /etc/apache2/sites-available/
```

#### Solutions selon la configuration

**Configuration 1 : Dolibarr dans un sous-répertoire**
```bash
# Si Dolibarr est accessible via http://localhost/dolibarr/
# Alors le module est accessible via :
http://localhost/dolibarr/custom/auditdigital/install.php

# Vérifier l'alias Apache
sudo cat /etc/apache2/sites-available/000-default.conf | grep -i alias
```

**Configuration 2 : Dolibarr à la racine**
```bash
# Si Dolibarr est accessible via http://localhost/
# Alors le module est accessible via :
http://localhost/custom/auditdigital/install.php

# Vérifier le DocumentRoot
sudo grep DocumentRoot /etc/apache2/sites-available/000-default.conf
```

**Configuration 3 : Domaine dédié**
```bash
# Si Dolibarr a son propre domaine/sous-domaine
# Vérifier les virtual hosts
sudo cat /etc/apache2/sites-available/dolibarr.conf
```

### 3. Erreur de permissions (403 Forbidden)

#### Diagnostic
```bash
# Vérifier les permissions du module
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/install.php

# Vérifier le propriétaire
stat /usr/share/dolibarr/htdocs/custom/auditdigital/install.php
```

#### Solutions
```bash
# Solution 1 : Corriger les permissions
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital
sudo chmod -R 755 /usr/share/dolibarr/htdocs/custom/auditdigital
sudo chmod 644 /usr/share/dolibarr/htdocs/custom/auditdigital/install.php

# Solution 2 : Vérifier la configuration Apache
sudo cat /etc/apache2/apache2.conf | grep -A 10 "Directory /usr/share"

# Solution 3 : Ajouter les permissions si nécessaire
sudo tee /etc/apache2/conf-available/dolibarr-custom.conf << EOF
<Directory /usr/share/dolibarr/htdocs/custom>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
EOF

sudo a2enconf dolibarr-custom
sudo systemctl reload apache2
```

### 4. Erreur PHP (500 Internal Server Error)

#### Diagnostic
```bash
# Vérifier les logs d'erreur Apache
sudo tail -f /var/log/apache2/error.log

# Vérifier les logs PHP
sudo tail -f /var/log/php*.log

# Tester la syntaxe PHP
php -l /usr/share/dolibarr/htdocs/custom/auditdigital/install.php
```

#### Solutions courantes
```bash
# Solution 1 : Vérifier les extensions PHP
php -m | grep -E "(gd|json|mysql|pdo|mbstring)"

# Installer les extensions manquantes
sudo apt update
sudo apt install php-gd php-mysql php-json php-mbstring php-xml php-zip

# Solution 2 : Vérifier la configuration PHP
php -i | grep -E "(memory_limit|max_execution_time)"

# Augmenter les limites si nécessaire
sudo nano /etc/php/8.1/apache2/php.ini
# Modifier :
# memory_limit = 256M
# max_execution_time = 300

sudo systemctl restart apache2

# Solution 3 : Vérifier les chemins dans le code
grep -n "main.inc.php" /usr/share/dolibarr/htdocs/custom/auditdigital/install.php
```

### 5. Base de données non accessible

#### Diagnostic
```bash
# Vérifier MySQL/MariaDB
sudo systemctl status mysql
sudo systemctl status mariadb

# Tester la connexion
mysql -u root -p -e "SHOW DATABASES;"

# Vérifier la configuration Dolibarr
sudo find /etc -name "conf.php" | grep dolibarr
sudo cat /etc/dolibarr/conf.php | grep -E "(dolibarr_main_db_|dolibarr_main_data_root)"
```

#### Solutions
```bash
# Solution 1 : Redémarrer MySQL
sudo systemctl restart mysql

# Solution 2 : Vérifier les permissions utilisateur Dolibarr
mysql -u root -p
SHOW GRANTS FOR 'dolibarr'@'localhost';

# Solution 3 : Recréer l'utilisateur si nécessaire
CREATE USER 'dolibarr'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON dolibarr.* TO 'dolibarr'@'localhost';
FLUSH PRIVILEGES;
```

## 🔧 Script de Diagnostic Automatique

```bash
#!/bin/bash
# diagnostic_auditdigital.sh

echo "🔍 Diagnostic AuditDigital pour Ubuntu 22.04"
echo "=============================================="

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

check_ok() { echo -e "${GREEN}✅ $1${NC}"; }
check_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
check_error() { echo -e "${RED}❌ $1${NC}"; }

echo ""
echo "1. Vérification de l'environnement système"
echo "----------------------------------------"

# Version Ubuntu
ubuntu_version=$(lsb_release -rs)
check_ok "Ubuntu version : $ubuntu_version"

# PHP
if command -v php &> /dev/null; then
    php_version=$(php -v | head -n1 | cut -d' ' -f2)
    check_ok "PHP version : $php_version"
    
    # Extensions PHP
    extensions=("gd" "mysql" "json" "mbstring")
    for ext in "${extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            check_ok "Extension PHP $ext : installée"
        else
            check_error "Extension PHP $ext : manquante"
        fi
    done
else
    check_error "PHP non installé"
fi

# Apache
if systemctl is-active --quiet apache2; then
    check_ok "Apache2 : actif"
else
    check_error "Apache2 : inactif"
fi

# MySQL/MariaDB
if systemctl is-active --quiet mysql; then
    check_ok "MySQL : actif"
elif systemctl is-active --quiet mariadb; then
    check_ok "MariaDB : actif"
else
    check_error "MySQL/MariaDB : inactif"
fi

echo ""
echo "2. Détection de Dolibarr"
echo "------------------------"

# Recherche Dolibarr
dolibarr_paths=(
    "/usr/share/dolibarr/htdocs"
    "/var/lib/dolibarr/htdocs"
    "/usr/dolibarr/htdocs"
    "/var/www/html/dolibarr/htdocs"
)

dolibarr_found=""
for path in "${dolibarr_paths[@]}"; do
    if [ -f "$path/main.inc.php" ]; then
        dolibarr_found="$path"
        check_ok "Dolibarr trouvé : $path"
        break
    fi
done

if [ -z "$dolibarr_found" ]; then
    check_error "Dolibarr non trouvé dans les emplacements standards"
    echo "Chemins testés :"
    for path in "${dolibarr_paths[@]}"; do
        echo "  - $path"
    done
    exit 1
fi

echo ""
echo "3. Vérification du module AuditDigital"
echo "-------------------------------------"

module_path="$dolibarr_found/custom/auditdigital"

if [ -d "$module_path" ]; then
    check_ok "Répertoire module : $module_path"
    
    # Fichiers clés
    key_files=(
        "core/modules/modAuditDigital.class.php"
        "install.php"
        "class/audit.class.php"
        "wizard/index.php"
        "data/solutions.json"
    )
    
    for file in "${key_files[@]}"; do
        if [ -f "$module_path/$file" ]; then
            check_ok "Fichier $file : présent"
        else
            check_error "Fichier $file : manquant"
        fi
    done
    
    # Permissions
    owner=$(stat -c '%U:%G' "$module_path")
    if [ "$owner" = "www-data:www-data" ]; then
        check_ok "Propriétaire : $owner"
    else
        check_warning "Propriétaire : $owner (devrait être www-data:www-data)"
    fi
    
    perms=$(stat -c '%a' "$module_path")
    if [ "$perms" = "755" ]; then
        check_ok "Permissions répertoire : $perms"
    else
        check_warning "Permissions répertoire : $perms (devrait être 755)"
    fi
    
else
    check_error "Module non installé : $module_path"
fi

echo ""
echo "4. Test d'accès web"
echo "------------------"

# Détecter l'URL de base
if grep -q "Alias /dolibarr" /etc/apache2/sites-available/* 2>/dev/null; then
    base_url="http://localhost/dolibarr"
elif grep -q "dolibarr" /etc/apache2/sites-available/000-default.conf 2>/dev/null; then
    base_url="http://localhost/dolibarr"
else
    base_url="http://localhost"
fi

check_ok "URL de base détectée : $base_url"

# Test d'accès
urls=(
    "$base_url/"
    "$base_url/custom/auditdigital/install.php"
    "$base_url/custom/auditdigital/test.php"
)

for url in "${urls[@]}"; do
    if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|302"; then
        check_ok "Accès $url : OK"
    else
        check_error "Accès $url : Échec"
    fi
done

echo ""
echo "5. Recommandations"
echo "-----------------"

if [ -d "$module_path" ]; then
    echo "✅ Module installé. URLs d'accès :"
    echo "   - Installation : $base_url/custom/auditdigital/install.php"
    echo "   - Tests : $base_url/custom/auditdigital/test.php"
    echo "   - Démo : $base_url/custom/auditdigital/demo.php"
else
    echo "❌ Module non installé. Commandes d'installation :"
    echo "   wget https://raw.githubusercontent.com/12457845124884/audit/main/install_ubuntu.sh"
    echo "   chmod +x install_ubuntu.sh"
    echo "   sudo ./install_ubuntu.sh"
fi

echo ""
echo "📋 Logs utiles :"
echo "   sudo tail -f /var/log/apache2/error.log"
echo "   sudo tail -f /var/log/apache2/access.log"
```

## 🚀 Installation Manuelle Étape par Étape

Si le script automatique ne fonctionne pas, voici la procédure manuelle :

### Étape 1 : Identifier Dolibarr
```bash
# Méthode 1 : Recherche du fichier principal
sudo find / -name "main.inc.php" -path "*/dolibarr/*" 2>/dev/null

# Méthode 2 : Via les paquets
dpkg -L dolibarr | grep htdocs | head -1

# Méthode 3 : Configuration Apache
sudo grep -r "dolibarr" /etc/apache2/sites-available/
```

### Étape 2 : Télécharger le module
```bash
cd /tmp
wget https://github.com/12457845124884/audit/archive/main.zip
unzip main.zip
```

### Étape 3 : Installer manuellement
```bash
# Remplacez par votre chemin Dolibarr réel
DOLIBARR_PATH="/usr/share/dolibarr/htdocs"

sudo mkdir -p "$DOLIBARR_PATH/custom"
sudo cp -r audit-main/htdocs/custom/auditdigital "$DOLIBARR_PATH/custom/"
sudo chown -R www-data:www-data "$DOLIBARR_PATH/custom/auditdigital"
sudo chmod -R 755 "$DOLIBARR_PATH/custom/auditdigital"
```

### Étape 4 : Tester l'accès
```bash
# Tester la syntaxe PHP
php -l "$DOLIBARR_PATH/custom/auditdigital/install.php"

# Redémarrer Apache
sudo systemctl restart apache2

# Tester l'URL (ajustez selon votre configuration)
curl -I http://localhost/dolibarr/custom/auditdigital/install.php
```

## 📞 Support et Aide

### Informations à collecter pour le support

```bash
# Informations système
echo "=== INFORMATIONS SYSTÈME ==="
lsb_release -a
apache2 -v
php -v
mysql --version

echo "=== CHEMINS DOLIBARR ==="
sudo find / -name "main.inc.php" -path "*/dolibarr/*" 2>/dev/null

echo "=== CONFIGURATION APACHE ==="
sudo apache2ctl -S
sudo grep -r "dolibarr" /etc/apache2/sites-available/

echo "=== PERMISSIONS MODULE ==="
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/ 2>/dev/null || echo "Module non trouvé"

echo "=== LOGS RÉCENTS ==="
sudo tail -20 /var/log/apache2/error.log
```

### Contact
- **GitHub Issues** : https://github.com/12457845124884/audit/issues
- **Documentation** : Consultez les fichiers README et guides d'installation