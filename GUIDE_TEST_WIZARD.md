# 🧪 Guide de Test du Wizard AuditDigital

## ✅ État Actuel
- **Module complet** : 10,000+ lignes de code
- **Corrections appliquées** : Erreurs de classe résolues
- **Fichiers vérifiés** : Syntaxe PHP validée
- **Prêt pour déploiement** : Aucune erreur détectée

## 🚀 Déploiement Rapide

### 1. Déployer sur le serveur
```bash
# Depuis votre machine de développement
./deploy_to_server.sh
```

### 2. Ou déploiement manuel
```bash
# Copier les fichiers
scp -r htdocs/custom/auditdigital root@192.168.1.252:/usr/share/dolibarr/htdocs/custom/

# Se connecter au serveur
ssh root@192.168.1.252

# Appliquer les corrections
sudo ./fix_wizard_final.sh
```

## 🧪 Tests à Effectuer

### Test 1: Accès au Wizard
```
URL: http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

**Résultat attendu :**
- ✅ Page se charge sans erreur HTTP 500
- ✅ Interface du wizard s'affiche
- ✅ Formulaire de sélection du type d'audit

### Test 2: Création d'un Audit TPE
1. Cliquer sur **"Audit TPE/PME"**
2. Remplir les informations :
   - Nom de l'entreprise : "Test TPE"
   - Secteur : "Commerce"
   - Nombre d'employés : "5"
3. Cliquer sur **"Commencer l'audit"**

**Résultat attendu :**
- ✅ Redirection vers le questionnaire
- ✅ Questions s'affichent correctement
- ✅ Possibilité de répondre aux questions

### Test 3: Génération PDF
1. Compléter quelques questions
2. Cliquer sur **"Générer le rapport"**

**Résultat attendu :**
- ✅ PDF généré sans erreur
- ✅ Téléchargement automatique
- ✅ Contenu du PDF cohérent

### Test 4: Administration
```
URL: http://192.168.1.252/dolibarr/custom/auditdigital/admin/setup.php
```

**Résultat attendu :**
- ✅ Page d'administration accessible
- ✅ Configuration du module
- ✅ Paramètres modifiables

## 🔍 Surveillance des Erreurs

### Logs Apache
```bash
# Sur le serveur
sudo tail -f /var/log/apache2/error.log | grep auditdigital
```

### Logs PHP
```bash
# Vérifier les erreurs PHP
sudo tail -f /var/log/apache2/error.log | grep -E "(Fatal|Parse|Warning)"
```

## 🚨 Résolution des Problèmes

### Erreur HTTP 500
```bash
# Vérifier les permissions
sudo chown -R www-data:www-data /usr/share/dolibarr/htdocs/custom/auditdigital
sudo chmod -R 755 /usr/share/dolibarr/htdocs/custom/auditdigital

# Redémarrer Apache
sudo systemctl restart apache2
```

### Classe non trouvée
```bash
# Appliquer les corrections
sudo ./fix_wizard_final.sh
```

### Problème de PDF
```bash
# Vérifier le répertoire documents
sudo mkdir -p /var/lib/dolibarr/documents/auditdigital
sudo chown -R www-data:www-data /var/lib/dolibarr/documents/auditdigital
```

## 📋 Checklist de Validation

### ✅ Fonctionnalités de Base
- [ ] Wizard accessible sans erreur
- [ ] Sélection du type d'audit
- [ ] Formulaire de création d'audit
- [ ] Questionnaire interactif
- [ ] Sauvegarde des réponses

### ✅ Génération de Rapports
- [ ] PDF TPE généré
- [ ] PDF Collectivité généré
- [ ] Contenu cohérent
- [ ] Téléchargement fonctionnel

### ✅ Administration
- [ ] Page d'administration accessible
- [ ] Configuration du module
- [ ] Gestion des templates
- [ ] Paramètres sauvegardés

### ✅ Intégration Dolibarr
- [ ] Module activé dans Dolibarr
- [ ] Permissions correctes
- [ ] Menu accessible
- [ ] Pas de conflit avec autres modules

## 🎯 Objectifs de Performance

### Temps de Réponse
- **Wizard** : < 2 secondes
- **Questionnaire** : < 1 seconde par question
- **Génération PDF** : < 5 secondes

### Compatibilité
- **PHP** : 7.4+ et 8.1+
- **Dolibarr** : 21.0.1+
- **Navigateurs** : Chrome, Firefox, Safari, Edge

## 📞 Support

### En cas de problème
1. **Vérifier les logs** : `sudo tail -f /var/log/apache2/error.log`
2. **Réappliquer les corrections** : `sudo ./fix_wizard_final.sh`
3. **Redémarrer Apache** : `sudo systemctl restart apache2`
4. **Vérifier les permissions** : Voir section résolution des problèmes

### Informations de débogage
```bash
# Informations système
lsb_release -a
php -v
apache2 -v

# État du module
ls -la /usr/share/dolibarr/htdocs/custom/auditdigital/
ls -la /var/lib/dolibarr/documents/auditdigital/

# Permissions
sudo -u www-data php -l /usr/share/dolibarr/htdocs/custom/auditdigital/wizard/index.php
```

---

## 🚀 Prêt pour les Tests !

Le module AuditDigital est maintenant **prêt pour les tests en production**. 

**Commande de déploiement :**
```bash
./deploy_to_server.sh
```

**URL de test :**
```
http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
```

**Bonne chance ! 🎉**