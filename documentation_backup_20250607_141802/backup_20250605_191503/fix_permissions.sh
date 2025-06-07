#!/bin/bash

# Script de correction des permissions pour AuditDigital
echo "=== Correction des permissions AuditDigital ==="

# Détecter le répertoire Dolibarr
DOLIBARR_PATH=""
if [ -d "/var/www/html/dolibarr" ]; then
    DOLIBARR_PATH="/var/www/html/dolibarr"
elif [ -d "/var/www/dolibarr" ]; then
    DOLIBARR_PATH="/var/www/dolibarr"
elif [ -d "/opt/dolibarr" ]; then
    DOLIBARR_PATH="/opt/dolibarr"
else
    echo "❌ Répertoire Dolibarr non trouvé. Veuillez spécifier le chemin :"
    read -p "Chemin vers Dolibarr : " DOLIBARR_PATH
fi

echo "📁 Répertoire Dolibarr détecté : $DOLIBARR_PATH"

# Vérifier si le répertoire existe
if [ ! -d "$DOLIBARR_PATH" ]; then
    echo "❌ Erreur : Le répertoire $DOLIBARR_PATH n'existe pas"
    exit 1
fi

# Détecter l'utilisateur web
WEB_USER="www-data"
if command -v apache2 >/dev/null 2>&1; then
    WEB_USER="www-data"
elif command -v nginx >/dev/null 2>&1; then
    WEB_USER="nginx"
elif command -v httpd >/dev/null 2>&1; then
    WEB_USER="apache"
fi

echo "👤 Utilisateur web détecté : $WEB_USER"

# Créer les répertoires nécessaires
echo "📂 Création des répertoires..."
mkdir -p "$DOLIBARR_PATH/htdocs/custom/auditdigital"
mkdir -p "$DOLIBARR_PATH/documents/auditdigital"
mkdir -p "$DOLIBARR_PATH/documents/auditdigital/audit"
mkdir -p "$DOLIBARR_PATH/documents/auditdigital/temp"

# Corriger les permissions du module
echo "🔧 Correction des permissions du module..."
chown -R $WEB_USER:$WEB_USER "$DOLIBARR_PATH/htdocs/custom/auditdigital"
chmod -R 755 "$DOLIBARR_PATH/htdocs/custom/auditdigital"

# Corriger les permissions des documents
echo "📄 Correction des permissions des documents..."
chown -R $WEB_USER:$WEB_USER "$DOLIBARR_PATH/documents/auditdigital"
chmod -R 755 "$DOLIBARR_PATH/documents/auditdigital"

# Permissions spéciales pour les fichiers de configuration
if [ -f "$DOLIBARR_PATH/htdocs/conf/conf.php" ]; then
    chown $WEB_USER:$WEB_USER "$DOLIBARR_PATH/htdocs/conf/conf.php"
    chmod 644 "$DOLIBARR_PATH/htdocs/conf/conf.php"
fi

# Vérifier les permissions
echo "✅ Vérification des permissions..."
if [ -w "$DOLIBARR_PATH/htdocs/custom/auditdigital" ]; then
    echo "✅ Module AuditDigital : Permissions OK"
else
    echo "❌ Module AuditDigital : Permissions KO"
fi

if [ -w "$DOLIBARR_PATH/documents/auditdigital" ]; then
    echo "✅ Documents AuditDigital : Permissions OK"
else
    echo "❌ Documents AuditDigital : Permissions KO"
fi

echo ""
echo "=== Résumé des actions ==="
echo "✅ Répertoires créés"
echo "✅ Permissions corrigées pour $WEB_USER"
echo "✅ Module prêt à être installé"
echo ""
echo "📋 Prochaines étapes :"
echo "1. Activer le module Projets dans Dolibarr"
echo "2. Relancer l'installation du module AuditDigital"
echo "3. Vérifier que tous les tests passent"