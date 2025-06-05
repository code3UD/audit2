# Guide d'Installation - Module AuditDigital

## 🚀 Installation Rapide

### Prérequis

- ✅ **Dolibarr 14.0+** installé et fonctionnel
- ✅ **PHP 7.4+** avec extensions : GD, JSON, MySQL
- ✅ **MySQL/MariaDB 5.7+**
- ✅ **Modules Dolibarr** : Tiers, Projets (recommandé)

### Installation en 5 minutes

#### 1. Téléchargement
```bash
# Cloner le repository
git clone https://github.com/12457845124884/audit.git

# Copier dans Dolibarr
cp -r audit/htdocs/custom/auditdigital /path/to/dolibarr/htdocs/custom/
```

#### 2. Activation
1. Connexion Dolibarr en admin
2. **Configuration → Modules/Applications**
3. Rechercher "AuditDigital"
4. Cliquer **"Activer"**

#### 3. Installation automatique
1. Aller sur `/custom/auditdigital/install.php`
2. Cliquer **"Install AuditDigital Module"**
3. Vérifier que tous les tests passent ✅

#### 4. Configuration des permissions
1. **Configuration → Utilisateurs & Groupes**
2. Attribuer les permissions AuditDigital

#### 5. Test de fonctionnement
1. Aller sur `/custom/auditdigital/test.php`
2. Lancer les tests de vérification
3. Créer des données de démo si souhaité

---

## 📋 Installation Détaillée

### Vérification des prérequis

#### Test de compatibilité
```bash
# Vérifier version PHP
php -v

# Vérifier extensions PHP
php -m | grep -E "(gd|json|mysql|pdo)"

# Vérifier permissions
ls -la /path/to/dolibarr/htdocs/custom/
```

#### Configuration PHP recommandée
```ini
; php.ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 32M
post_max_size = 32M
```

### Installation manuelle

#### 1. Préparation des fichiers
```bash
# Créer le répertoire
mkdir -p /path/to/dolibarr/htdocs/custom/auditdigital

# Copier tous les fichiers du module
cp -r audit/htdocs/custom/auditdigital/* /path/to/dolibarr/htdocs/custom/auditdigital/

# Vérifier les permissions
chown -R www-data:www-data /path/to/dolibarr/htdocs/custom/auditdigital
chmod -R 755 /path/to/dolibarr/htdocs/custom/auditdigital
```

#### 2. Configuration base de données

**Création des tables :**
```sql
-- Se connecter à MySQL
mysql -u root -p dolibarr_db

-- Exécuter les scripts SQL
SOURCE /path/to/dolibarr/htdocs/custom/auditdigital/sql/llx_auditdigital_audit.sql;
SOURCE /path/to/dolibarr/htdocs/custom/auditdigital/sql/llx_auditdigital_audit.key.sql;
SOURCE /path/to/dolibarr/htdocs/custom/auditdigital/sql/llx_auditdigital_solutions.sql;
SOURCE /path/to/dolibarr/htdocs/custom/auditdigital/sql/data.sql;
```

**Vérification :**
```sql
-- Vérifier les tables créées
SHOW TABLES LIKE 'llx_auditdigital_%';

-- Vérifier la structure
DESCRIBE llx_auditdigital_audit;
DESCRIBE llx_auditdigital_solutions;
```

#### 3. Configuration Dolibarr

**Activation du module :**
```sql
-- Activer le module directement en base
INSERT INTO llx_const (name, value, type, entity, visible) 
VALUES ('MAIN_MODULE_AUDITDIGITAL', '1', 'chaine', 1, 0);
```

**Configuration par défaut :**
```sql
-- Masque de numérotation
INSERT INTO llx_const (name, value, type, entity, visible) 
VALUES ('AUDITDIGITAL_AUDIT_MASK', 'AUD{yyyy}{mm}{dd}-{####}', 'chaine', 1, 0);

-- Modèle PDF par défaut
INSERT INTO llx_const (name, value, type, entity, visible) 
VALUES ('AUDIT_ADDON_PDF', 'audit_tpe', 'chaine', 1, 0);
```

#### 4. Chargement des solutions

**Via interface web :**
1. Aller dans **Configuration → AuditDigital → Configuration**
2. Section "Bibliothèque de solutions"
3. Cliquer "Charger les solutions depuis JSON"

**Via ligne de commande :**
```php
<?php
// Script de chargement solutions.php
require_once '/path/to/dolibarr/master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';

$solutionLibrary = new SolutionLibrary($db);
$jsonFile = DOL_DOCUMENT_ROOT.'/custom/auditdigital/data/solutions.json';
$result = $solutionLibrary->loadFromJson($jsonFile);

echo "Solutions chargées : " . $result . "\n";
?>
```

### Configuration des permissions

#### Permissions par rôle

**Consultant :**
```sql
-- Permissions de base
INSERT INTO llx_rights_def (id, libelle, module, perms, subperms, type, bydefault) 
VALUES 
(500101, 'Lire les audits', 'auditdigital', 'audit', 'read', 'r', 1),
(500102, 'Créer/modifier les audits', 'auditdigital', 'audit', 'write', 'w', 1);
```

**Manager :**
```sql
-- Permissions étendues
INSERT INTO llx_rights_def (id, libelle, module, perms, subperms, type, bydefault) 
VALUES 
(500103, 'Supprimer les audits', 'auditdigital', 'audit', 'delete', 'd', 0),
(500104, 'Valider les audits', 'auditdigital', 'audit', 'validate', 'w', 0);
```

**Attribution aux utilisateurs :**
```sql
-- Attribuer permissions à un utilisateur
INSERT INTO llx_user_rights (fk_user, fk_id) 
SELECT 1, id FROM llx_rights_def WHERE module = 'auditdigital';
```

### Configuration avancée

#### Personnalisation des répertoires

**Configuration dans conf.php :**
```php
// Répertoire de sortie personnalisé
$dolibarr_main_data_root = '/custom/path/documents';

// Répertoire spécifique AuditDigital
$conf->auditdigital->dir_output = $dolibarr_main_data_root.'/auditdigital';
```

**Création des répertoires :**
```bash
mkdir -p /custom/path/documents/auditdigital/audit
mkdir -p /custom/path/documents/auditdigital/temp
chown -R www-data:www-data /custom/path/documents/auditdigital
```

#### Configuration email

**SMTP Dolibarr :**
```php
// Dans conf.php
$dolibarr_main_mail_sendmode = 'smtp';
$dolibarr_main_mail_smtp_server = 'smtp.gmail.com';
$dolibarr_main_mail_smtp_port = '587';
$dolibarr_main_mail_smtp_auth = 1;
$dolibarr_main_mail_email_from = 'contact@updigit.fr';
$dolibarr_main_mail_email_from_name = 'Up Digit Agency';
```

**Test d'envoi :**
```php
<?php
// test_email.php
require_once '/path/to/dolibarr/master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

$mail = new CMailFile(
    'Test AuditDigital',
    'test@example.com',
    'contact@updigit.fr',
    'Test d\'envoi depuis AuditDigital',
    array(),
    array(),
    array(),
    '',
    '',
    0,
    -1
);

$result = $mail->sendfile();
echo $result ? "Email envoyé ✅" : "Erreur envoi ❌";
?>
```

---

## 🔧 Configuration Post-Installation

### Vérification de l'installation

#### Tests automatiques
```bash
# Accéder à la page de test
curl http://votre-dolibarr.com/custom/auditdigital/test.php
```

#### Tests manuels

**1. Test création audit :**
1. Menu **AuditDigital → Nouvel audit**
2. Remplir le wizard complet
3. Vérifier génération PDF

**2. Test solutions :**
1. **Configuration → AuditDigital → Solutions**
2. Vérifier présence des solutions
3. Tester recommandations

**3. Test permissions :**
1. Connecter différents utilisateurs
2. Vérifier accès selon rôles
3. Tester actions autorisées

### Optimisation des performances

#### Configuration MySQL

**Optimisations recommandées :**
```sql
-- Index pour performances
CREATE INDEX idx_audit_soc ON llx_auditdigital_audit(fk_soc);
CREATE INDEX idx_audit_date ON llx_auditdigital_audit(date_audit);
CREATE INDEX idx_audit_status ON llx_auditdigital_audit(status);
CREATE INDEX idx_solutions_category ON llx_auditdigital_solutions(category);
CREATE INDEX idx_solutions_target ON llx_auditdigital_solutions(target_audience);
```

**Configuration my.cnf :**
```ini
[mysqld]
innodb_buffer_pool_size = 256M
query_cache_size = 64M
query_cache_type = 1
tmp_table_size = 64M
max_heap_table_size = 64M
```

#### Configuration Apache/Nginx

**Apache (.htaccess) :**
```apache
# Cache des ressources statiques
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>
```

**Nginx :**
```nginx
# Cache des ressources
location ~* \.(css|js|svg)$ {
    expires 1M;
    add_header Cache-Control "public, immutable";
}

# Compression
gzip on;
gzip_types text/css application/javascript application/json;
```

### Monitoring et logs

#### Configuration des logs

**Activation logs AuditDigital :**
```php
// Dans conf.php
$dolibarr_syslog_level = 7; // DEBUG
$dolibarr_syslog_file = '/var/log/dolibarr/auditdigital.log';
```

**Rotation des logs :**
```bash
# /etc/logrotate.d/auditdigital
/var/log/dolibarr/auditdigital.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
}
```

#### Monitoring des performances

**Script de monitoring :**
```bash
#!/bin/bash
# monitor_auditdigital.sh

# Vérifier espace disque
df -h /path/to/dolibarr/documents/auditdigital

# Vérifier processus MySQL
mysqladmin -u root -p processlist | grep auditdigital

# Vérifier logs d'erreur
tail -n 50 /var/log/dolibarr/auditdigital.log | grep ERROR

# Statistiques d'utilisation
mysql -u root -p -e "
SELECT 
    COUNT(*) as total_audits,
    COUNT(CASE WHEN status = 1 THEN 1 END) as validated_audits,
    AVG(score_global) as avg_score
FROM llx_auditdigital_audit 
WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY);"
```

---

## 🚨 Dépannage

### Problèmes courants

#### Module ne s'active pas

**Symptômes :**
- Erreur lors de l'activation
- Module absent de la liste

**Solutions :**
```bash
# Vérifier permissions
ls -la /path/to/dolibarr/htdocs/custom/auditdigital/

# Vérifier syntaxe PHP
php -l /path/to/dolibarr/htdocs/custom/auditdigital/core/modules/modAuditDigital.class.php

# Vérifier logs
tail -f /var/log/dolibarr/dolibarr.log
```

#### Erreurs base de données

**Symptômes :**
- Tables non créées
- Erreurs SQL

**Solutions :**
```sql
-- Vérifier existence tables
SHOW TABLES LIKE 'llx_auditdigital_%';

-- Recréer si nécessaire
DROP TABLE IF EXISTS llx_auditdigital_audit;
DROP TABLE IF EXISTS llx_auditdigital_solutions;

-- Réexécuter scripts
SOURCE /path/to/sql/llx_auditdigital_audit.sql;
```

#### Problèmes PDF

**Symptômes :**
- PDF non généré
- Erreur mémoire

**Solutions :**
```php
// Augmenter mémoire PHP
ini_set('memory_limit', '512M');

// Vérifier extension GD
if (!extension_loaded('gd')) {
    echo "Extension GD manquante";
}

// Vérifier permissions répertoire
$dir = $conf->auditdigital->dir_output;
if (!is_writable($dir)) {
    echo "Répertoire non accessible en écriture";
}
```

### Outils de diagnostic

#### Script de diagnostic complet

```php
<?php
// diagnostic.php
require_once '/path/to/dolibarr/master.inc.php';

echo "=== DIAGNOSTIC AUDITDIGITAL ===\n\n";

// Version PHP
echo "PHP Version: " . phpversion() . "\n";

// Extensions PHP
$extensions = ['gd', 'json', 'mysql', 'pdo'];
foreach ($extensions as $ext) {
    echo "Extension $ext: " . (extension_loaded($ext) ? "✅" : "❌") . "\n";
}

// Base de données
echo "\nBase de données:\n";
$tables = ['llx_auditdigital_audit', 'llx_auditdigital_solutions'];
foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = $db->query($sql);
    echo "Table $table: " . ($db->num_rows($result) > 0 ? "✅" : "❌") . "\n";
}

// Permissions fichiers
echo "\nPermissions:\n";
$dirs = [
    DOL_DOCUMENT_ROOT.'/custom/auditdigital',
    $conf->auditdigital->dir_output
];
foreach ($dirs as $dir) {
    echo "Répertoire $dir: " . (is_writable($dir) ? "✅" : "❌") . "\n";
}

// Configuration
echo "\nConfiguration:\n";
$configs = ['AUDITDIGITAL_AUDIT_MASK', 'AUDIT_ADDON_PDF'];
foreach ($configs as $config) {
    echo "$config: " . ($conf->global->$config ?? "Non défini") . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
?>
```

#### Commandes utiles

```bash
# Vérifier processus PHP
ps aux | grep php

# Vérifier espace disque
df -h

# Vérifier logs en temps réel
tail -f /var/log/dolibarr/dolibarr.log | grep -i audit

# Tester connectivité base
mysql -u dolibarr_user -p -e "SELECT 1"

# Vérifier configuration Apache
apache2ctl configtest

# Redémarrer services
systemctl restart apache2
systemctl restart mysql
```

---

## 📞 Support

### Niveaux de support

**Niveau 1 - Documentation :**
- Guide d'installation
- Documentation utilisateur
- FAQ en ligne

**Niveau 2 - Communauté :**
- Forum Dolibarr
- GitHub Issues
- Documentation technique

**Niveau 3 - Professionnel :**
- **Up Digit Agency**
- Email : support@updigit.fr
- Tél : +33 1 23 45 67 89

### Informations à fournir

Pour toute demande de support, merci de fournir :

```
✅ Version Dolibarr
✅ Version PHP  
✅ Version MySQL
✅ Système d'exploitation
✅ Version module AuditDigital
✅ Description détaillée du problème
✅ Étapes de reproduction
✅ Messages d'erreur complets
✅ Captures d'écran si pertinentes
✅ Logs d'erreur
```

---

**© 2024 Up Digit Agency - Guide d'installation v1.0.0**