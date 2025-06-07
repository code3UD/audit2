# 🚀 Guide de Déploiement Git - AuditDigital

## 📋 Nouvelle Architecture

Le repository a été réorganisé pour un déploiement Git direct :

```
/
├── class/              # Classes PHP du module
├── core/               # Modules de numérotation et PDF  
├── wizard/             # Interface wizard
├── admin/              # Administration
├── lib/                # Bibliothèques
├── sql/                # Scripts SQL
├── langs/              # Traductions
├── css/js/img/         # Assets
├── docs/               # Documentation
├── scripts/            # Scripts utilitaires
├── deploy_git.sh       # 🚀 Déploiement initial
└── update_server.sh    # ⚡ Mise à jour rapide
```

## 🚀 Déploiement Initial

### 1. Première Installation sur le Serveur
```bash
./deploy_git.sh
```

Ce script :
- Clone le repository sur le serveur
- Copie les fichiers du module (sans .git, docs, scripts)
- Applique les permissions correctes
- Redémarre Apache

### 2. Test du Module
```
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

## ⚡ Workflow de Mise à Jour

### 1. Développement Local
```bash
# Modifier le code
# Tester localement

# Commiter les changements
git add .
git commit -m "Description des modifications"
git push
```

### 2. Mise à Jour du Serveur
```bash
./update_server.sh
```

Ce script :
- Met à jour le repository sur le serveur
- Synchronise les fichiers du module
- Applique les permissions
- Redémarre Apache

## 🔧 Commandes Utiles

### Vérification du Statut
```bash
# Statut local
git status

# Statut sur le serveur
ssh root@192.168.1.252 "cd /usr/share/dolibarr/htdocs/custom/auditdigital.git && git status"
```

### Logs du Serveur
```bash
ssh root@192.168.1.252 "tail -f /var/log/apache2/error.log | grep auditdigital"
```

### Rollback d'Urgence
```bash
ssh root@192.168.1.252 "cd /usr/share/dolibarr/htdocs/custom && mv auditdigital auditdigital.broken && mv auditdigital.backup.YYYYMMDD_HHMMSS auditdigital && systemctl restart apache2"
```

## 🎯 Avantages de cette Architecture

1. **Déploiement Simple** : Une seule commande pour déployer
2. **Mises à Jour Rapides** : `git push` + `./update_server.sh`
3. **Historique Complet** : Toutes les versions dans Git
4. **Rollback Facile** : Retour à une version précédente simple
5. **Synchronisation Automatique** : Pas de copie manuelle de fichiers

## 🚨 Important

- Les dossiers `docs/` et `scripts/` ne sont PAS déployés sur le serveur
- Seuls les fichiers du module Dolibarr sont synchronisés
- Les sauvegardes automatiques sont créées à chaque déploiement

---

**Prêt pour un déploiement Git moderne ! 🎉**
