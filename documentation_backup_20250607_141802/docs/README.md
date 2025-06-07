# Module AuditDigital pour Dolibarr

## Description

Le module AuditDigital est un module complet pour Dolibarr permettant de réaliser des audits de maturité numérique pour TPE/PME et collectivités territoriales. Il propose un wizard interactif, la génération de PDF personnalisés et des recommandations commerciales intégrées.

## Fonctionnalités

### 🎯 Wizard d'audit multi-étapes
- **Étape 1** : Informations générales (type de structure, secteur, budget IT)
- **Étape 2** : Maturité numérique (présence web, outils collaboratifs, digitalisation)
- **Étape 3** : Cybersécurité (politique mots de passe, sauvegardes, RGPD)
- **Étape 4** : Cloud et infrastructure (hébergement, services cloud, télétravail)
- **Étape 5** : Automatisation (processus manuels, outils d'automatisation)
- **Étape 6** : Synthèse et recommandations

### 📊 Système de scoring
- Calcul automatique des scores par domaine
- Score global de maturité numérique
- Graphique radar de visualisation
- Recommandations personnalisées

### 📄 Génération PDF
- Modèles spécialisés TPE/PME et Collectivités
- Rapport complet avec scores et recommandations
- Proposition commerciale intégrée
- Charte graphique Up Digit Agency

### 💡 Bibliothèque de solutions
- Base de données de solutions techniques
- Import depuis fichier JSON
- Recommandations automatiques basées sur les scores
- Calcul de ROI et temps d'implémentation

## Installation

### Prérequis
- Dolibarr 14.0+
- PHP 7.4+
- MySQL/MariaDB
- Modules Dolibarr requis : Tiers, Projets

### Étapes d'installation

1. **Copier le module**
   ```bash
   cp -r auditdigital /path/to/dolibarr/htdocs/custom/
   ```

2. **Activer le module**
   - Aller dans Configuration > Modules/Applications
   - Rechercher "AuditDigital"
   - Cliquer sur "Activer"

3. **Configurer les permissions**
   - Aller dans Configuration > Utilisateurs & Groupes
   - Attribuer les permissions AuditDigital aux utilisateurs/groupes

4. **Charger les solutions**
   - Aller dans Configuration > Modules > AuditDigital > Configuration
   - Cliquer sur "Charger les solutions depuis JSON"

## Configuration

### Permissions disponibles
- **Lire les audits** : Consulter les audits existants
- **Créer/modifier les audits** : Créer et modifier des audits
- **Supprimer les audits** : Supprimer des audits
- **Valider les audits** : Valider et finaliser des audits

### Paramètres configurables
- Masque de numérotation des audits
- Modèle PDF par défaut
- Configuration email
- Paramètres de la bibliothèque de solutions

## Utilisation

### Créer un nouvel audit

1. **Accès au wizard**
   - Menu AuditDigital > Nouvel audit
   - Ou depuis la liste des audits

2. **Remplir le questionnaire**
   - Suivre les 6 étapes du wizard
   - Répondre aux questions de chaque domaine
   - Les scores se calculent automatiquement

3. **Finaliser l'audit**
   - Vérifier la synthèse
   - Consulter les recommandations
   - Générer le PDF

### Gérer les audits

- **Liste des audits** : Vue d'ensemble avec filtres et recherche
- **Fiche audit** : Détails complets avec actions possibles
- **Génération PDF** : Export du rapport complet
- **Envoi email** : Transmission automatique du rapport

## Structure technique

### Base de données
```sql
-- Table principale des audits
llx_auditdigital_audit
- Informations générales
- Scores par domaine
- Données JSON (réponses, configuration, recommandations)
- Statuts et dates

-- Table bibliothèque de solutions
llx_auditdigital_solutions
- Solutions techniques disponibles
- Critères de ciblage
- Données ROI et implémentation
```

### Architecture des fichiers
```
auditdigital/
├── core/modules/modAuditDigital.class.php    # Module principal
├── class/                                      # Classes métier
│   ├── audit.class.php
│   ├── questionnaire.class.php
│   └── solutionlibrary.class.php
├── wizard/                                     # Interface wizard
├── admin/                                      # Configuration
├── sql/                                        # Scripts base de données
├── css/                                        # Styles
├── js/                                         # JavaScript
└── langs/fr_FR/                               # Traductions
```

## Personnalisation

### Ajouter de nouvelles solutions

1. **Modifier le fichier JSON**
   ```json
   {
     "solutions": {
       "nouvelle_categorie": {
         "nouvelle_solution": {
           "ref": "SOL-XXX-001",
           "label": "Nom de la solution",
           "category": "nouvelle_categorie",
           "target_audience": ["tpe", "pme"],
           "price_range": "10k",
           "features": [...],
           "benefits": [...],
           "requirements": [...]
         }
       }
     }
   }
   ```

2. **Recharger les solutions**
   - Interface d'administration > Charger les solutions

### Personnaliser le questionnaire

Modifier le fichier `class/questionnaire.class.php` :
- Ajouter de nouvelles questions
- Modifier les options de réponse
- Ajuster les règles de scoring

### Créer un nouveau modèle PDF

1. Copier `core/modules/auditdigital/doc/pdf_audit_tpe.modules.php`
2. Renommer et personnaliser
3. Enregistrer dans le même répertoire

## Support et maintenance

### Logs et débogage
- Logs Dolibarr : `/var/log/dolibarr/`
- Logs module : Utilise le système de logs Dolibarr
- Mode debug : Activer dans Configuration > Divers

### Sauvegarde
- Base de données : Tables `llx_auditdigital_*`
- Fichiers : Répertoire `/custom/auditdigital/`
- Documents générés : `/documents/auditdigital/`

### Mise à jour
1. Sauvegarder les données
2. Remplacer les fichiers du module
3. Exécuter les scripts de migration si nécessaire
4. Vider le cache Dolibarr

## Développement

### Contribuer
- Fork du projet
- Créer une branche feature
- Respecter les conventions Dolibarr
- Tests et documentation
- Pull request

### API REST
Endpoints disponibles :
- `GET /api/auditdigital/audits` : Liste des audits
- `POST /api/auditdigital/audits` : Créer un audit
- `GET /api/auditdigital/audits/{id}` : Détail audit
- `PUT /api/auditdigital/audits/{id}` : Modifier audit

## Licence

Ce module est distribué sous licence GPL v3+.

## Auteur

**Up Digit Agency**
- Site web : [updigit.fr](https://updigit.fr)
- Email : contact@updigit.fr
- Version : 1.0.0

## Changelog

### v1.0.0 (2024-06-05)
- Version initiale
- Wizard multi-étapes complet
- Génération PDF TPE/PME
- Bibliothèque de solutions
- Interface d'administration
- Traductions françaises complètes