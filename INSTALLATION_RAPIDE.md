# 🚀 Installation Rapide - AuditDigital Moderne

## Installation en 3 étapes

### 1. Téléchargement
```bash
# Cloner le dépôt
git clone https://github.com/code2UD/audit2.git auditdigital-moderne
cd auditdigital-moderne
```

### 2. Déploiement automatique
```bash
# Déploiement sur serveur de production
./deploy.sh -e prod -b -d /var/www/dolibarr

# Ou déploiement de développement
./deploy.sh -e dev -d /path/to/dolibarr
```

### 3. Installation des fonctionnalités
```bash
# Via navigateur web
https://votre-dolibarr.com/custom/auditdigital/install_modern_features.php

# Cocher "Installer les données de démonstration"
# Cliquer sur "Lancer l'Installation"
```

## Test de l'installation

### Démonstration interactive
```bash
https://votre-dolibarr.com/custom/auditdigital/demo_modern.php
```

### Wizard moderne
```bash
https://votre-dolibarr.com/custom/auditdigital/wizard/modern.php
```

### Interface classique (comparaison)
```bash
https://votre-dolibarr.com/custom/auditdigital/wizard/index.php
```

## Résolution de problèmes

### Permissions
```bash
chmod -R 755 /var/www/dolibarr/htdocs/custom/auditdigital/
chmod -R 777 /var/www/dolibarr/htdocs/custom/auditdigital/documents/
```

### Extensions PHP manquantes
```bash
# Ubuntu/Debian
sudo apt-get install php-mysqli php-gd php-curl php-json php-mbstring

# CentOS/RHEL
sudo yum install php-mysqli php-gd php-curl php-json php-mbstring
```

### Base de données
```sql
-- Vérifier que les tables sont créées
SHOW TABLES LIKE 'llx_auditdigital%';

-- Si la table commentaires n'existe pas
SOURCE /var/www/dolibarr/htdocs/custom/auditdigital/sql/llx_auditdigital_comments.sql;
```

## Support

- 📧 **Email** : support@updigit.fr
- 🐛 **Issues** : https://github.com/code2UD/audit2/issues
- 📖 **Documentation** : [docs/MODERNISATION_COMPLETE.md](docs/MODERNISATION_COMPLETE.md)

---

**Installation réussie ? Découvrez toutes les nouvelles fonctionnalités dans la [démonstration interactive](demo_modern.php) !**