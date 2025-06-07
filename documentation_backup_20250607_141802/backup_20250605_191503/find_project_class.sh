#!/bin/bash
# Script pour trouver la classe de formulaire des projets dans Dolibarr

echo "🔍 Recherche de la classe FormProject dans Dolibarr"
echo "=================================================="

# Chercher les fichiers liés aux projets
echo "1. Recherche des fichiers de formulaire projet..."
find /usr/share/dolibarr/htdocs -name "*formproj*" -type f 2>/dev/null

echo ""
echo "2. Recherche des classes FormProj*..."
grep -r "class FormProj" /usr/share/dolibarr/htdocs/core/class/ 2>/dev/null

echo ""
echo "3. Contenu du fichier html.formprojet.class.php..."
if [ -f "/usr/share/dolibarr/htdocs/core/class/html.formprojet.class.php" ]; then
    head -20 /usr/share/dolibarr/htdocs/core/class/html.formprojet.class.php | grep -E "class|function"
else
    echo "Fichier non trouvé"
fi

echo ""
echo "4. Recherche alternative..."
find /usr/share/dolibarr/htdocs -name "*project*" -path "*/class/*" -name "*.php" | head -10

echo ""
echo "5. Recherche dans les includes..."
grep -r "FormProj" /usr/share/dolibarr/htdocs/core/class/html.form*.php 2>/dev/null | head -5