# 🔍 AuditDigital - Module Dolibarr

Module complet d'audit de maturité numérique pour Dolibarr.

## 🚀 Déploiement Rapide

### Première Installation
```bash
./deploy_git.sh
```

### Mises à Jour
```bash
# Depuis votre machine de développement
git add .
git commit -m "Mise à jour"
git push

# Puis mise à jour du serveur
./update_server.sh
```

## 🧪 Test du Module
```
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

## 📁 Structure du Projet

```
/
├── class/              # Classes PHP du module
├── core/               # Modules de numérotation et PDF
├── wizard/             # Interface wizard
├── admin/              # Administration
├── lib/                # Bibliothèques
├── docs/               # Documentation complète
├── scripts/            # Scripts utilitaires
├── deploy_git.sh       # Déploiement initial
└── update_server.sh    # Mise à jour rapide
```

## 🔧 Workflow de Développement

1. **Modifier le code localement**
2. **Tester les modifications**
3. **Commiter et pusher**
   ```bash
   git add .
   git commit -m "Description des modifications"
   git push
   ```
4. **Mettre à jour le serveur**
   ```bash
   ./update_server.sh
   ```

## 📋 Documentation

Consultez le dossier `docs/` pour :
- Guide d'installation
- Documentation technique
- Guide utilisateur
- Résolution des problèmes

---

**Prêt pour production ! 🎉**
