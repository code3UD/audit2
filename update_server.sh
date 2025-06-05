#!/bin/bash
# Mise à jour rapide du serveur

echo "⚡ MISE À JOUR RAPIDE SERVEUR"
echo "============================"

SERVER_IP="192.168.1.252"
SERVER_USER="root"
DOLIBARR_PATH="/usr/share/dolibarr/htdocs/custom"

ssh "$SERVER_USER@$SERVER_IP" << 'EOSSH'
cd /usr/share/dolibarr/htdocs/custom/auditdigital.git
git pull origin main
rsync -av --exclude='.git' --exclude='docs' --exclude='scripts' --exclude='backup_*' ./ ../auditdigital/
chown -R www-data:www-data ../auditdigital
systemctl restart apache2
echo "✅ Mise à jour terminée !"
EOSSH
