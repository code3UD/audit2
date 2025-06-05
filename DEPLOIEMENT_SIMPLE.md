# 🚀 Déploiement Simple - AuditDigital

## ✅ Problème Résolu !

L'architecture a été **complètement réorganisée** pour permettre un déploiement Git direct et simple.

## 🎯 Solution Finale

### 1. Déploiement Initial (Une seule fois)
```bash
./deploy_git.sh
```

### 2. Mises à Jour (À chaque modification)
```bash
# Sur votre machine de développement
git add .
git commit -m "Description des modifications"
git push

# Puis mise à jour du serveur
./update_server.sh
```

## 🔧 Comment ça Marche

### Architecture Avant (Problématique)
```
Repository:
├── htdocs/custom/auditdigital/  ❌ Structure complexe
└── scripts/

Serveur:
└── /usr/share/dolibarr/htdocs/custom/auditdigital/  ❌ Incompatible
```

### Architecture Après (Solution)
```
Repository:
├── class/              ✅ Directement les fichiers du module
├── wizard/
├── admin/
├── core/
└── ...

Serveur:
└── /usr/share/dolibarr/htdocs/custom/auditdigital/  ✅ Compatible !
```

## 🚀 Scripts Automatiques

### `deploy_git.sh` - Déploiement Initial
- Clone le repository sur le serveur
- Copie uniquement les fichiers du module
- Applique les permissions www-data
- Redémarre Apache
- Crée les répertoires nécessaires

### `update_server.sh` - Mise à Jour Rapide
- Met à jour le repository sur le serveur
- Synchronise les fichiers modifiés
- Applique les permissions
- Redémarre Apache

## 📋 Workflow Quotidien

### Développement
1. **Modifier le code** localement
2. **Tester** les modifications
3. **Commiter** : `git add . && git commit -m "Description"`
4. **Pusher** : `git push`
5. **Déployer** : `./update_server.sh`
6. **Tester** : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php

### Avantages
- ✅ **Une seule commande** pour déployer
- ✅ **Synchronisation automatique** des fichiers
- ✅ **Historique Git complet** sur le serveur
- ✅ **Sauvegardes automatiques** avant chaque mise à jour
- ✅ **Rollback facile** en cas de problème

## 🧪 Test Immédiat

### 1. Déployer Maintenant
```bash
./deploy_git.sh
```

### 2. Tester le Wizard
```
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

### 3. Créer un Audit de Test
- Sélectionner "Audit TPE/PME"
- Remplir les informations
- Compléter le questionnaire
- Générer le PDF

## 🔍 Surveillance

### Logs en Temps Réel
```bash
ssh root@192.168.1.252 "tail -f /var/log/apache2/error.log | grep auditdigital"
```

### Statut du Module
```bash
ssh root@192.168.1.252 "ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/"
```

## 🚨 Résolution Rapide

### Si Erreur HTTP 500
```bash
ssh root@192.168.1.252 "cd /usr/share/dolibarr/htdocs/custom && ./scripts/fix_wizard_final.sh"
```

### Rollback d'Urgence
```bash
ssh root@192.168.1.252 "cd /usr/share/dolibarr/htdocs/custom && mv auditdigital auditdigital.broken && mv auditdigital.backup.* auditdigital && systemctl restart apache2"
```

## 🎉 Résultat

**Fini les problèmes d'architecture !**

Vous avez maintenant :
- ✅ Déploiement Git moderne et simple
- ✅ Mises à jour en une seule commande
- ✅ Structure compatible serveur
- ✅ Workflow de développement fluide

---

## 🚀 Commande Magique

```bash
./deploy_git.sh
```

**C'est tout ! Le module sera déployé et fonctionnel. 🎉**