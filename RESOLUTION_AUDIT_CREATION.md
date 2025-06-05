# 🔍 Résolution : Pourquoi aucun audit n'est créé ?

## 🚨 Problème Identifié

D'après les logs du serveur, plusieurs erreurs critiques empêchent la création d'audits :

### Erreurs Principales
1. **Classe ModelePDFAudit dupliquée** 
2. **Propriété scandir redéclarée**
3. **Classe Audit non trouvée** dans admin/setup.php
4. **Chemins incorrects** dans les inclusions

## 🎯 Solution Immédiate

### Étape 1 : Diagnostic Complet
```bash
# Sur le serveur (vous êtes déjà connecté)
cd /tmp/audit2
sudo ./diagnostic_audit_creation.sh
```

### Étape 2 : Correction Automatique
```bash
# Appliquer toutes les corrections
sudo ./fix_audit_creation.sh
```

### Étape 3 : Déploiement des Corrections GitHub
```bash
# Déployer la version corrigée depuis GitHub
sudo ./deploy_local.sh
```

## 🔧 Corrections Manuelles (Si Nécessaire)

### 1. Supprimer la Classe Dupliquée
```bash
sudo nano /usr/share/dolibarr/htdocs/custom/auditdigital/core/modules/auditdigital/modules_audit.php
```

**Supprimer complètement :**
```php
// SUPPRIMER CETTE CLASSE ENTIÈRE
class ModelePDFAudit extends CommonDocGenerator
{
    // ... tout le contenu de cette classe
}
```

### 2. Corriger les Chemins d'Inclusion
```bash
sudo nano /usr/share/dolibarr/htdocs/custom/auditdigital/core/modules/auditdigital/mod_audit_standard.php
```

**Ligne 24, changer :**
```php
// AVANT (incorrect)
require_once DOL_DOCUMENT_ROOT.'/core/modules/auditdigital/modules_audit.php';

// APRÈS (correct)
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/core/modules/auditdigital/modules_audit.php';
```

### 3. Ajouter l'Inclusion de la Classe Audit
```bash
sudo nano /usr/share/dolibarr/htdocs/custom/auditdigital/admin/setup.php
```

**Ajouter après la ligne `require_once '../main.inc.php';` :**
```php
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
```

### 4. Vérifier la Méthode create()
```bash
sudo nano /usr/share/dolibarr/htdocs/custom/auditdigital/class/audit.class.php
```

**Vérifier que la méthode `create()` existe et est complète.**

## 🧪 Test de Validation

### 1. Vérifier les Erreurs Apache
```bash
sudo tail -f /var/log/apache2/error.log | grep auditdigital
```

### 2. Tester l'Accès au Wizard
```
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

### 3. Tester la Création d'Audit
1. Accéder au wizard
2. Remplir le formulaire étape 1
3. Vérifier que l'audit est sauvegardé
4. Contrôler en base de données :

```bash
mysql -u dolibarr -p dolibarr -e "SELECT * FROM llx_auditdigital_audit ORDER BY rowid DESC LIMIT 5;"
```

## 📋 Checklist de Validation

- [ ] **Aucune erreur PHP** dans les logs Apache
- [ ] **Wizard accessible** sans erreur HTTP 500
- [ ] **Formulaire étape 1** se soumet correctement
- [ ] **Audit créé** en base de données
- [ ] **Navigation entre étapes** fonctionnelle
- [ ] **Génération PDF** opérationnelle

## 🔍 Diagnostic Avancé

### Vérifier les Tables de Base de Données
```sql
SHOW TABLES LIKE 'llx_auditdigital_%';
DESCRIBE llx_auditdigital_audit;
SELECT COUNT(*) FROM llx_auditdigital_audit;
```

### Vérifier les Permissions
```bash
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital/
```

### Test PHP Direct
```bash
php -r "
require_once '/usr/share/dolibarr/htdocs/main.inc.php';
require_once '/usr/share/dolibarr/htdocs/custom/auditdigital/class/audit.class.php';
echo 'Classe Audit chargée avec succès';
"
```

## 🚨 Si le Problème Persiste

### 1. Réinstallation Complète
```bash
# Sauvegarder
sudo mv /usr/share/dolibarr/htdocs/custom/auditdigital /tmp/auditdigital.backup

# Réinstaller
cd /tmp/audit2
sudo ./deploy_local.sh
```

### 2. Vérification des Logs Détaillés
```bash
# Activer le debug PHP
sudo nano /etc/php/*/apache2/php.ini
# Définir : display_errors = On, log_errors = On

sudo systemctl restart apache2
```

### 3. Test Minimal
Créer un fichier de test simple :
```php
<?php
require_once '/usr/share/dolibarr/htdocs/main.inc.php';
echo "Dolibarr chargé : " . DOL_VERSION . "\n";
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
echo "Classe Audit chargée\n";
$audit = new Audit($db);
echo "Instance créée\n";
?>
```

## 🎯 Objectif Final

**Résultat attendu :** Création d'audits fonctionnelle avec :
- Wizard multi-étapes opérationnel
- Sauvegarde en base de données
- Génération PDF
- Interface d'administration accessible

---

## 🚀 Commandes Rapides

```bash
# Diagnostic + Correction + Test
sudo ./diagnostic_audit_creation.sh && sudo ./fix_audit_creation.sh && curl -I http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

**Le module devrait être pleinement fonctionnel après ces corrections ! 🎉**