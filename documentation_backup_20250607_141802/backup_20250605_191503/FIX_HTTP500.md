# 🚨 Résolution Erreur HTTP 500 - Wizard AuditDigital

## Votre Situation

✅ **Permissions** : Corrigées  
✅ **Module activé** : OK  
❌ **Wizard** : Erreur HTTP 500 sur `http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php`

## 🔧 Solution Rapide (3 minutes)

### Étape 1 : Script de diagnostic automatique

```bash
# Télécharger et exécuter le script de correction HTTP 500
wget https://raw.githubusercontent.com/12457845124884/audit/main/fix_http500.sh
chmod +x fix_http500.sh
sudo ./fix_http500.sh
```

### Étape 2 : Test de diagnostic

Après le script, testez cette URL :
```
http://192.168.1.252/dolibarr/custom/auditdigital/test_wizard.php
```

### Étape 3 : Vérification des logs

```bash
# Voir les erreurs en temps réel
sudo tail -f /var/log/apache2/error.log

# Dans un autre terminal, accédez au wizard pour voir l'erreur exacte
```

## 🔍 Diagnostic Manuel

### 1. Vérifier la syntaxe PHP

```bash
# Tester la syntaxe du wizard
php -l /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/index.php

# Tester les classes
php -l /usr/share/dolibarr/htdocs/custom/auditdigital/class/audit.class.php
php -l /usr/share/dolibarr/htdocs/custom/auditdigital/class/questionnaire.class.php
php -l /usr/share/dolibarr/htdocs/custom/auditdigital/class/solutionlibrary.class.php
```

### 2. Vérifier les chemins d'inclusion

```bash
# Vérifier que main.inc.php est accessible
ls -la /usr/share/dolibarr/htdocs/main.inc.php

# Tester l'inclusion depuis le répertoire wizard
cd /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/
php -r "if(file_exists('../../../main.inc.php')) echo 'OK'; else echo 'NOT FOUND';"
```

### 3. Activer l'affichage des erreurs PHP

```bash
# Éditer la configuration PHP
sudo nano /etc/php/8.1/apache2/php.ini

# Modifier ces lignes :
display_errors = On
error_reporting = E_ALL

# Redémarrer Apache
sudo systemctl restart apache2
```

### 4. Vérifier les extensions PHP

```bash
# Vérifier les extensions requises
php -m | grep -E "(gd|mysql|json|mbstring|xml)"

# Installer les manquantes si nécessaire
sudo apt install php-gd php-mysql php-json php-mbstring php-xml
sudo systemctl restart apache2
```

## 🛠️ Solutions par Type d'Erreur

### Erreur : "Include of main fails"

**Cause** : Chemin vers main.inc.php incorrect

**Solution** :
```bash
# Créer un lien symbolique si nécessaire
sudo ln -sf /usr/share/dolibarr/htdocs/main.inc.php /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/../../../main.inc.php
```

### Erreur : "Class not found"

**Cause** : Classes AuditDigital non chargées

**Solution** :
```bash
# Vérifier les permissions des classes
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital/class/
sudo chmod -R 644 /usr/share/dolibarr/htdocs/custom/auditdigital/class/*.php
```

### Erreur : "Module not enabled"

**Cause** : Module AuditDigital non activé

**Solution** :
```bash
# Activer via base de données
mysql -u dolibarr_user -p dolibarr_db -e "
INSERT INTO llx_const (name, value, type, entity, visible) 
VALUES ('MAIN_MODULE_AUDITDIGITAL', '1', 'chaine', 1, 0) 
ON DUPLICATE KEY UPDATE value='1';"
```

### Erreur : "Permission denied"

**Cause** : Permissions insuffisantes

**Solution** :
```bash
# Permissions complètes
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital/
sudo chmod -R 755 /usr/share/dolibarr/htdocs/custom/auditdigital/
sudo chmod -R 644 /usr/share/dolibarr/htdocs/custom/auditdigital/*.php
sudo chmod -R 644 /usr/share/dolibarr/htdocs/custom/auditdigital/*/*.php
```

## 🔧 Script de Test Manuel

Créez ce fichier pour tester : `/usr/share/dolibarr/htdocs/custom/auditdigital/debug.php`

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug AuditDigital</h1>";

// Test 1: Inclusion Dolibarr
echo "<h2>Test inclusion Dolibarr</h2>";
$paths = array(
    "../main.inc.php",
    "../../main.inc.php", 
    "../../../main.inc.php",
    "/usr/share/dolibarr/htdocs/main.inc.php"
);

$loaded = false;
foreach ($paths as $path) {
    echo "Test: $path - ";
    if (file_exists($path)) {
        echo "Existe - ";
        try {
            include_once $path;
            echo "✅ OK<br>";
            $loaded = true;
            break;
        } catch (Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Non trouvé<br>";
    }
}

if (!$loaded) {
    die("❌ Impossible de charger Dolibarr");
}

// Test 2: Classes
echo "<h2>Test classes AuditDigital</h2>";
$classes = array(
    'class/audit.class.php' => 'Audit',
    'class/questionnaire.class.php' => 'Questionnaire',
    'class/solutionlibrary.class.php' => 'SolutionLibrary'
);

foreach ($classes as $file => $class) {
    echo "Test $class: ";
    $full_path = DOL_DOCUMENT_ROOT . '/custom/auditdigital/' . $file;
    if (file_exists($full_path)) {
        try {
            require_once $full_path;
            echo "✅ OK<br>";
        } catch (Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Fichier non trouvé: $full_path<br>";
    }
}

// Test 3: Module
echo "<h2>Test module</h2>";
if (function_exists('isModEnabled')) {
    echo "isModEnabled disponible: ✅<br>";
    if (isModEnabled('auditdigital')) {
        echo "Module AuditDigital: ✅ Activé<br>";
    } else {
        echo "Module AuditDigital: ❌ Non activé<br>";
    }
} else {
    echo "isModEnabled: ❌ Non disponible<br>";
}

echo "<h2>Informations système</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "DOL_DOCUMENT_ROOT: " . (defined('DOL_DOCUMENT_ROOT') ? DOL_DOCUMENT_ROOT : 'Non défini') . "<br>";
echo "Répertoire actuel: " . __DIR__ . "<br>";

phpinfo();
?>
```

## 📋 Commandes de Vérification Complètes

```bash
# 1. Vérification complète des permissions
sudo find /usr/share/dolibarr/htdocs/custom/auditdigital -type f -name "*.php" -exec chmod 644 {} \;
sudo find /usr/share/dolibarr/htdocs/custom/auditdigital -type d -exec chmod 755 {} \;
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital

# 2. Test de syntaxe de tous les fichiers PHP
find /usr/share/dolibarr/htdocs/custom/auditdigital -name "*.php" -exec php -l {} \;

# 3. Vérification des logs
sudo tail -f /var/log/apache2/error.log | grep -i "auditdigital\|wizard\|custom"

# 4. Test d'accès web
curl -I http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php

# 5. Redémarrage complet
sudo systemctl restart apache2
sudo systemctl restart mysql
```

## 🚨 Solution d'Urgence

Si rien ne fonctionne, créez un wizard simplifié pour tester :

```bash
# Créer un wizard minimal
sudo tee /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/test.php << 'EOF'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Wizard Minimal</h1>";

// Inclusion simple
if (file_exists("../../../main.inc.php")) {
    include_once "../../../main.inc.php";
    echo "✅ Dolibarr chargé<br>";
    echo "DOL_DOCUMENT_ROOT: " . DOL_DOCUMENT_ROOT . "<br>";
    echo "Version: " . DOL_VERSION . "<br>";
} else {
    echo "❌ main.inc.php non trouvé<br>";
}

echo "<p>Si ce test fonctionne, le problème vient du wizard principal.</p>";
?>
EOF

# Tester avec :
# http://192.168.1.252/dolibarr/custom/auditdigital/wizard/test.php
```

## 📞 Informations pour le Support

Si le problème persiste, collectez ces informations :

```bash
# Informations système
echo "=== SYSTÈME ==="
lsb_release -a
apache2 -v
php -v

echo "=== PERMISSIONS ==="
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/class/

echo "=== SYNTAXE PHP ==="
php -l /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/index.php

echo "=== LOGS RÉCENTS ==="
sudo tail -20 /var/log/apache2/error.log | grep -i "auditdigital\|wizard\|custom"
```

---

**⏱️ Temps de résolution estimé : 3-10 minutes selon la cause**