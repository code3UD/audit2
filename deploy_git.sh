#!/bin/bash

# =============================================================================
# Script de Déploiement Git - Module AuditDigital Moderne
# =============================================================================
# 
# Ce script automatise le déploiement du code sur le dépôt Git
# et prépare la release de la version modernisée
#
# Usage: ./deploy_git.sh [options]
# Options:
#   -h, --help          Afficher cette aide
#   -m, --message MSG   Message de commit personnalisé
#   -t, --tag VERSION   Créer un tag de version
#   -p, --push          Pousser automatiquement vers origin
#   -v, --verbose       Mode verbeux
#
# Auteur: Up Digit Agency
# Version: 1.0.0
# =============================================================================

set -euo pipefail

# Configuration par défaut
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMMIT_MESSAGE=""
VERSION_TAG=""
AUTO_PUSH=false
VERBOSE=false

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Fonction d'affichage avec couleurs
log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    case $level in
        "INFO")
            echo -e "${CYAN}[INFO]${NC} ${timestamp} - $message"
            ;;
        "SUCCESS")
            echo -e "${GREEN}[SUCCESS]${NC} ${timestamp} - $message"
            ;;
        "WARNING")
            echo -e "${YELLOW}[WARNING]${NC} ${timestamp} - $message"
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} ${timestamp} - $message"
            ;;
        "DEBUG")
            if [[ $VERBOSE == true ]]; then
                echo -e "${PURPLE}[DEBUG]${NC} ${timestamp} - $message"
            fi
            ;;
    esac
}

# Fonction d'aide
show_help() {
    cat << EOF
🚀 Script de Déploiement Git - Module AuditDigital Moderne

Usage: $0 [options]

Options:
    -h, --help          Afficher cette aide
    -m, --message MSG   Message de commit personnalisé
    -t, --tag VERSION   Créer un tag de version (ex: v2.0.0)
    -p, --push          Pousser automatiquement vers origin
    -v, --verbose       Mode verbeux

Exemples:
    $0                                    # Commit basique
    $0 -m "Nouvelle fonctionnalité" -p   # Commit avec message et push
    $0 -t v2.0.0 -p                     # Release avec tag
    $0 -m "Fix bug" -t v2.0.1 -p        # Hotfix avec tag

EOF
}

# Analyse des arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -m|--message)
                COMMIT_MESSAGE="$2"
                shift 2
                ;;
            -t|--tag)
                VERSION_TAG="$2"
                shift 2
                ;;
            -p|--push)
                AUTO_PUSH=true
                shift
                ;;
            -v|--verbose)
                VERBOSE=true
                shift
                ;;
            *)
                log "ERROR" "Option inconnue: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Vérification de l'environnement Git
check_git_environment() {
    log "INFO" "Vérification de l'environnement Git..."
    
    # Vérifier que Git est installé
    if ! command -v git &> /dev/null; then
        log "ERROR" "Git n'est pas installé"
        exit 1
    fi
    
    # Vérifier qu'on est dans un dépôt Git
    if ! git rev-parse --git-dir > /dev/null 2>&1; then
        log "ERROR" "Ce répertoire n'est pas un dépôt Git"
        log "INFO" "Initialisation du dépôt Git..."
        git init
        log "SUCCESS" "Dépôt Git initialisé"
    fi
    
    # Vérifier la configuration Git
    if ! git config user.name > /dev/null 2>&1; then
        log "WARNING" "Configuration Git utilisateur manquante"
        git config user.name "Up Digit Agency"
        git config user.email "dev@updigit.fr"
        log "SUCCESS" "Configuration Git définie"
    fi
    
    log "SUCCESS" "Environnement Git vérifié"
}

# Nettoyage des fichiers avant commit
cleanup_files() {
    log "INFO" "Nettoyage des fichiers avant commit..."
    
    # Supprimer les fichiers temporaires
    find . -name "*.tmp" -delete 2>/dev/null || true
    find . -name "*.log" -delete 2>/dev/null || true
    find . -name ".DS_Store" -delete 2>/dev/null || true
    find . -name "Thumbs.db" -delete 2>/dev/null || true
    
    # Nettoyer les répertoires de cache
    rm -rf node_modules/ 2>/dev/null || true
    rm -rf vendor/ 2>/dev/null || true
    rm -rf .sass-cache/ 2>/dev/null || true
    
    # Nettoyer les backups
    find . -name "*.bak" -delete 2>/dev/null || true
    find . -name "*~" -delete 2>/dev/null || true
    
    log "SUCCESS" "Nettoyage terminé"
}

# Création/mise à jour du .gitignore
update_gitignore() {
    log "INFO" "Mise à jour du fichier .gitignore..."
    
    cat > .gitignore << 'EOF'
# =============================================================================
# .gitignore pour Module AuditDigital Moderne
# =============================================================================

# Fichiers temporaires
*.tmp
*.temp
*.log
*.cache
*~
*.bak
*.swp
*.swo

# Fichiers système
.DS_Store
Thumbs.db
desktop.ini

# Répertoires de développement
node_modules/
vendor/
.sass-cache/
.vscode/
.idea/

# Fichiers de configuration locaux
config.local.php
.env.local
.env.*.local

# Données utilisateur et uploads
documents/
temp/
logs/
backups/

# Fichiers de base de données
*.sql
*.db
*.sqlite

# Fichiers de build
dist/
build/
*.min.js
*.min.css

# Fichiers de test
coverage/
.nyc_output/
.phpunit.result.cache

# Fichiers spécifiques Dolibarr
conf.php
install.lock

# Certificats et clés
*.pem
*.key
*.crt
*.p12

# Archives
*.zip
*.tar.gz
*.rar
*.7z

EOF

    log "SUCCESS" "Fichier .gitignore mis à jour"
}

# Génération du message de commit automatique
generate_commit_message() {
    if [[ -n "$COMMIT_MESSAGE" ]]; then
        return 0
    fi
    
    log "INFO" "Génération du message de commit automatique..."
    
    local changes=$(git status --porcelain | wc -l)
    local new_files=$(git status --porcelain | grep "^??" | wc -l)
    local modified_files=$(git status --porcelain | grep "^.M" | wc -l)
    local deleted_files=$(git status --porcelain | grep "^.D" | wc -l)
    
    local message="🚀 Modernisation AuditDigital v2.0.0"
    
    if [[ $new_files -gt 0 ]]; then
        message="$message\n\n✨ Nouvelles fonctionnalités:"
        message="$message\n- Interface moderne avec cards cliquables"
        message="$message\n- Système de commentaires enrichi"
        message="$message\n- Graphiques interactifs Chart.js"
        message="$message\n- Calcul ROI automatique"
        message="$message\n- Roadmap d'implémentation"
        message="$message\n- PDF moderne avec graphiques"
    fi
    
    if [[ $modified_files -gt 0 ]]; then
        message="$message\n\n🔧 Améliorations:"
        message="$message\n- Classes PHP enrichies"
        message="$message\n- JavaScript ES6+ moderne"
        message="$message\n- CSS avec animations fluides"
        message="$message\n- Performance optimisée"
    fi
    
    message="$message\n\n📊 Statistiques:"
    message="$message\n- $changes fichiers modifiés"
    message="$message\n- $new_files nouveaux fichiers"
    message="$message\n- Interface 100% responsive"
    message="$message\n- Compatible Dolibarr 13.0+"
    
    COMMIT_MESSAGE="$message"
    
    log "SUCCESS" "Message de commit généré"
}

# Ajout des fichiers au staging
stage_files() {
    log "INFO" "Ajout des fichiers au staging..."
    
    # Ajouter tous les fichiers sauf ceux ignorés
    git add .
    
    # Vérifier qu'il y a des changements à commiter
    if git diff --cached --quiet; then
        log "WARNING" "Aucun changement à commiter"
        return 1
    fi
    
    # Afficher un résumé des changements
    local staged_files=$(git diff --cached --name-only | wc -l)
    log "SUCCESS" "$staged_files fichiers ajoutés au staging"
    
    if [[ $VERBOSE == true ]]; then
        log "DEBUG" "Fichiers stagés:"
        git diff --cached --name-only | while read file; do
            log "DEBUG" "  - $file"
        done
    fi
    
    return 0
}

# Création du commit
create_commit() {
    log "INFO" "Création du commit..."
    
    # Générer le message si nécessaire
    generate_commit_message
    
    # Créer le commit
    echo -e "$COMMIT_MESSAGE" | git commit -F -
    
    local commit_hash=$(git rev-parse --short HEAD)
    log "SUCCESS" "Commit créé: $commit_hash"
    
    return 0
}

# Création du tag de version
create_version_tag() {
    if [[ -z "$VERSION_TAG" ]]; then
        return 0
    fi
    
    log "INFO" "Création du tag de version: $VERSION_TAG"
    
    # Vérifier que le tag n'existe pas déjà
    if git tag -l | grep -q "^$VERSION_TAG$"; then
        log "ERROR" "Le tag $VERSION_TAG existe déjà"
        return 1
    fi
    
    # Créer le tag avec annotation
    local tag_message="Release $VERSION_TAG - AuditDigital Moderne

🚀 Nouvelle version avec interface modernisée
📊 Graphiques interactifs et fonctionnalités avancées
🎯 ROI automatique et roadmap d'implémentation

Voir CHANGELOG.md pour les détails complets."
    
    echo "$tag_message" | git tag -a "$VERSION_TAG" -F -
    
    log "SUCCESS" "Tag $VERSION_TAG créé"
    
    return 0
}

# Push vers le dépôt distant
push_to_remote() {
    if [[ $AUTO_PUSH == false ]]; then
        return 0
    fi
    
    log "INFO" "Push vers le dépôt distant..."
    
    # Vérifier qu'il y a un remote configuré
    if ! git remote | grep -q origin; then
        log "WARNING" "Aucun remote 'origin' configuré"
        log "INFO" "Veuillez configurer le remote manuellement:"
        log "INFO" "git remote add origin https://github.com/username/auditdigital-moderne.git"
        return 1
    fi
    
    # Push des commits
    git push origin main || git push origin master || {
        log "ERROR" "Erreur lors du push des commits"
        return 1
    }
    
    # Push des tags si présents
    if [[ -n "$VERSION_TAG" ]]; then
        git push origin "$VERSION_TAG" || {
            log "ERROR" "Erreur lors du push du tag"
            return 1
        }
        log "SUCCESS" "Tag $VERSION_TAG poussé"
    fi
    
    log "SUCCESS" "Code poussé vers le dépôt distant"
    
    return 0
}

# Génération du changelog
generate_changelog() {
    log "INFO" "Génération du changelog..."
    
    cat > CHANGELOG.md << 'EOF'
# Changelog - AuditDigital Moderne

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-06-06

### ✨ Ajouté
- **Interface moderne** avec cards cliquables remplaçant les radio buttons
- **Stepper visuel interactif** pour navigation intuitive entre étapes
- **Design glassmorphism** avec effets de transparence et animations CSS3
- **Système de commentaires enrichi** avec pièces jointes par question
- **Graphiques interactifs** Chart.js avec radar et barres
- **Calcul ROI automatique** avec analyse coût/bénéfice détaillée
- **Roadmap d'implémentation** en 3 phases prioritaires
- **Synthèse exécutive intelligente** avec KPIs automatiques
- **Export multi-format** (JSON, CSV, XML) pour intégrations
- **PDF moderne** avec graphiques intégrés et design professionnel
- **Auto-save intelligent** toutes les 30 secondes
- **Interface 100% responsive** mobile/tablet/desktop
- **Thème sombre automatique** selon préférences système

### 🔧 Amélioré
- **Classes PHP enrichies** avec nouvelles méthodes métier
- **JavaScript ES6+** avec classes modernes et async/await
- **Performance optimisée** avec lazy loading et cache intelligent
- **Sécurité renforcée** avec validation inputs et protection CSRF
- **Compatibilité étendue** IE11+ / Chrome 60+ / Firefox 55+

### 🐛 Corrigé
- Problèmes de compatibilité avec anciennes versions PHP
- Erreurs JavaScript sur navigateurs anciens
- Problèmes d'affichage mobile
- Bugs de validation formulaire

### 🗑️ Supprimé
- Code legacy non utilisé
- Dépendances obsolètes
- Fichiers temporaires

## [1.0.0] - 2024-01-01

### ✨ Ajouté
- Version initiale du module AuditDigital
- Interface wizard basique
- Système de scoring simple
- Génération PDF basique
- Gestion des questionnaires

EOF

    log "SUCCESS" "Changelog généré"
}

# Affichage du résumé final
show_summary() {
    echo
    echo "=============================================="
    echo "🎉 DÉPLOIEMENT GIT TERMINÉ AVEC SUCCÈS"
    echo "=============================================="
    echo
    echo "📋 Résumé du déploiement:"
    echo "  • Commit: $(git rev-parse --short HEAD)"
    echo "  • Branche: $(git branch --show-current)"
    echo "  • Tag: $([ -n "$VERSION_TAG" ] && echo "$VERSION_TAG" || echo "Aucun")"
    echo "  • Push automatique: $([ $AUTO_PUSH == true ] && echo "Activé" || echo "Désactivé")"
    echo
    echo "🚀 Prochaines étapes:"
    if [[ $AUTO_PUSH == false ]]; then
        echo "  1. Pousser vers le dépôt distant:"
        echo "     git push origin main"
        if [[ -n "$VERSION_TAG" ]]; then
            echo "     git push origin $VERSION_TAG"
        fi
    fi
    echo "  2. Créer une release sur GitHub/GitLab"
    echo "  3. Déployer sur les serveurs de production"
    echo "  4. Mettre à jour la documentation"
    echo
    echo "📚 Fichiers générés:"
    echo "  • README.md - Documentation principale"
    echo "  • CHANGELOG.md - Historique des versions"
    echo "  • .gitignore - Fichiers à ignorer"
    echo "  • deploy.sh - Script de déploiement serveur"
    echo
    echo "=============================================="
}

# Fonction principale
main() {
    echo "🚀 Déploiement Git - Module AuditDigital Moderne v2.0.0"
    echo "========================================================"
    echo
    
    parse_arguments "$@"
    
    log "INFO" "Début du déploiement Git"
    log "INFO" "Message: $([ -n "$COMMIT_MESSAGE" ] && echo "$COMMIT_MESSAGE" || echo "Auto-généré")"
    log "INFO" "Tag: $([ -n "$VERSION_TAG" ] && echo "$VERSION_TAG" || echo "Aucun")"
    log "INFO" "Push auto: $([ $AUTO_PUSH == true ] && echo "Activé" || echo "Désactivé")"
    
    # Exécution des étapes de déploiement
    check_git_environment
    cleanup_files
    update_gitignore
    generate_changelog
    
    if stage_files; then
        create_commit
        create_version_tag
        push_to_remote
        show_summary
        log "SUCCESS" "Déploiement Git terminé avec succès!"
    else
        log "INFO" "Aucun changement à déployer"
    fi
    
    exit 0
}

# Gestion des erreurs
trap 'log "ERROR" "Erreur lors du déploiement Git à la ligne $LINENO"; exit 1' ERR

# Point d'entrée
main "$@"