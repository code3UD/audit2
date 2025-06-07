# 🚀 Déploiement sur le Serveur - AuditDigital

## 🎯 Situation Actuelle

Vous êtes **déjà sur le serveur** (192.168.1.252) avec l'utilisateur **vince**.

## ✅ Solution Corrigée

### 1. Déploiement Initial (Depuis le serveur)

```bash
# Vous êtes déjà dans /tmp/audit2/
sudo ./deploy_local.sh
```

### 2. Mises à Jour Futures

```bash
# Depuis votre machine de développement
git add .
git commit -m "Modifications"
git push

# Puis sur le serveur
cd /usr/share/dolibarr/htdocs/custom
sudo ./update_local.sh
```

## 🔧 Scripts Corrigés

### `deploy_local.sh` - Déploiement Initial
- ✅ Fonctionne avec l'utilisateur **vince** + sudo
- ✅ Clone dans `/usr/share/dolibarr/htdocs/custom/`
- ✅ Nomme le dossier final **auditdigital**
- ✅ Exclut docs/, scripts/, *.md
- ✅ Applique les permissions www-data

### `update_local.sh` - Mise à Jour Rapide
- ✅ Met à jour le repository Git
- ✅ Synchronise uniquement les fichiers du module
- ✅ Corrige les permissions
- ✅ Redémarre Apache

## 📋 Étapes Détaillées

### 1. Déploiement Immédiat
```bash
# Vous êtes dans /tmp/audit2/
sudo ./deploy_local.sh
```

**Résultat :**
- Repository cloné dans `/usr/share/dolibarr/htdocs/custom/auditdigital.git/`
- Module installé dans `/usr/share/dolibarr/htdocs/custom/auditdigital/`
- Permissions www-data appliquées
- Apache redémarré

### 2. Test du Module
```
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

### 3. Structure Finale
```
/usr/share/dolibarr/htdocs/custom/
├── auditdigital/           # ✅ Module Dolibarr
│   ├── class/
│   ├── wizard/
│   ├── admin/
│   └── ...
└── auditdigital.git/       # Repository Git (caché)
    ├── docs/
    ├── scripts/
    └── ...
```

## 🔄 Workflow de Développement

### Développement Local (Votre Machine)
```bash
# Modifier le code
git add .
git commit -m "Nouvelles fonctionnalités"
git push
```

### Mise à Jour Serveur
```bash
# Sur le serveur (vince@192.168.1.252)
cd /usr/share/dolibarr/htdocs/custom
sudo ./update_local.sh
```

## 🧪 Tests Immédiats

### 1. Déployer Maintenant
```bash
sudo ./deploy_local.sh
```

### 2. Vérifier l'Installation
```bash
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/
```

### 3. Tester le Wizard
```
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

## 🔍 Surveillance

### Logs Apache
```bash
sudo tail -f /var/log/apache2/error.log | grep auditdigital
```

### Statut du Module
```bash
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/
```

## 🚨 Résolution Rapide

### Si Erreur de Permissions
```bash
cd /usr/share/dolibarr/htdocs/custom
sudo chown -R www-data:www-data auditdigital
sudo systemctl restart apache2
```

### Si Erreur HTTP 500
```bash
sudo tail /var/log/apache2/error.log | grep auditdigital
```

## 🎉 Avantages de cette Solution

- ✅ **Fonctionne avec vince + sudo**
- ✅ **Dossier final nommé "auditdigital"**
- ✅ **Structure Dolibarr respectée**
- ✅ **Mises à jour Git simples**
- ✅ **Sauvegardes automatiques**

---

## 🚀 Commande Magique

```bash
sudo ./deploy_local.sh
```

**Le module sera installé et fonctionnel ! 🎉**