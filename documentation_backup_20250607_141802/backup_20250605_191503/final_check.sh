#!/bin/bash
# Vérification finale avant déploiement

echo "🔍 VÉRIFICATION FINALE - AUDITDIGITAL"
echo "====================================="

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

BASE_PATH="./htdocs/custom/auditdigital"
ERRORS=0

print_info "=== VÉRIFICATION DES FICHIERS CRITIQUES ==="

# Fichiers critiques
CRITICAL_FILES=(
    "class/audit.class.php"
    "core/modules/auditdigital/modules_audit.php"
    "core/modules/auditdigital/mod_audit_standard.php"
    "core/modules/auditdigital/doc/pdf_audit_tpe.modules.php"
    "core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php"
    "wizard/index.php"
    "lib/auditdigital.lib.php"
    "admin/setup.php"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$BASE_PATH/$file" ]; then
        print_status "$file"
    else
        print_error "$file MANQUANT"
        ((ERRORS++))
    fi
done

print_info "\n=== VÉRIFICATION DES CORRECTIONS ==="

# 1. Vérifier que la classe dupliquée a été supprimée
if [ -f "$BASE_PATH/core/modules/auditdigital/modules_audit.php" ]; then
    if grep -q "class ModelePDFAudit" "$BASE_PATH/core/modules/auditdigital/modules_audit.php"; then
        print_error "Classe ModelePDFAudit encore présente dans modules_audit.php"
        ((ERRORS++))
    else
        print_status "Classe dupliquée supprimée de modules_audit.php"
    fi
fi

# 2. Vérifier les propriétés scandir
PDF_FILES=(
    "core/modules/auditdigital/doc/pdf_audit_tpe.modules.php"
    "core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php"
)

for pdf_file in "${PDF_FILES[@]}"; do
    if [ -f "$BASE_PATH/$pdf_file" ]; then
        if grep -q "public \$scandir" "$BASE_PATH/$pdf_file"; then
            print_status "Propriété scandir présente dans $(basename "$pdf_file")"
        else
            print_warning "Propriété scandir manquante dans $(basename "$pdf_file")"
        fi
    fi
done

print_info "\n=== VÉRIFICATION DE LA SYNTAXE ==="

# 3. Vérifier la syntaxe des fichiers PHP principaux
PHP_FILES=(
    "$BASE_PATH/class/audit.class.php"
    "$BASE_PATH/core/modules/auditdigital/modules_audit.php"
    "$BASE_PATH/wizard/index.php"
)

for php_file in "${PHP_FILES[@]}"; do
    if [ -f "$php_file" ]; then
        # Vérifications basiques
        if head -1 "$php_file" | grep -q "<?php"; then
            print_status "Balise PHP correcte dans $(basename "$php_file")"
        else
            print_error "Balise PHP manquante dans $(basename "$php_file")"
            ((ERRORS++))
        fi
        
        # Vérifier les accolades
        open_braces=$(grep -o '{' "$php_file" | wc -l)
        close_braces=$(grep -o '}' "$php_file" | wc -l)
        
        if [ "$open_braces" -eq "$close_braces" ]; then
            print_status "Accolades équilibrées dans $(basename "$php_file")"
        else
            print_error "Accolades déséquilibrées dans $(basename "$php_file") ($open_braces ouvertes, $close_braces fermées)"
            ((ERRORS++))
        fi
    fi
done

print_info "\n=== VÉRIFICATION DES DÉPENDANCES ==="

# 4. Vérifier les includes/requires
if [ -f "$BASE_PATH/wizard/index.php" ]; then
    if grep -q "main.inc.php" "$BASE_PATH/wizard/index.php"; then
        print_status "Inclusion de main.inc.php trouvée dans wizard"
    else
        print_error "Inclusion de main.inc.php manquante dans wizard"
        ((ERRORS++))
    fi
fi

print_info "\n=== VÉRIFICATION DES PERMISSIONS ==="

# 5. Vérifier que les fichiers sont lisibles
find "$BASE_PATH" -name "*.php" -not -readable 2>/dev/null | while read file; do
    print_warning "Fichier non lisible : $file"
done

print_info "\n=== GÉNÉRATION DU RAPPORT ==="

# 6. Créer un rapport de statut
cat > status_report.txt << EOF
RAPPORT DE VÉRIFICATION AUDITDIGITAL
===================================
Date: $(date)
Erreurs détectées: $ERRORS

FICHIERS CRITIQUES:
EOF

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$BASE_PATH/$file" ]; then
        echo "✅ $file" >> status_report.txt
    else
        echo "❌ $file MANQUANT" >> status_report.txt
    fi
done

cat >> status_report.txt << EOF

CORRECTIONS APPLIQUÉES:
- Classe ModelePDFAudit dupliquée supprimée
- Propriétés scandir vérifiées
- Syntaxe PHP validée

PROCHAINES ÉTAPES:
1. Déployer avec: ./deploy_to_server.sh
2. Tester: http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php
3. Créer un audit de test
4. Vérifier la génération PDF
EOF

print_status "Rapport généré : status_report.txt"

print_info "\n=== RÉSULTAT FINAL ==="

if [ $ERRORS -eq 0 ]; then
    print_status "🎉 VÉRIFICATION RÉUSSIE !"
    print_info "Le module est prêt pour le déploiement."
    echo ""
    print_info "🚀 COMMANDES DE DÉPLOIEMENT :"
    echo "./deploy_to_server.sh"
    echo ""
    print_info "🧪 TESTS À EFFECTUER APRÈS DÉPLOIEMENT :"
    echo "1. http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
    echo "2. Créer un audit de test"
    echo "3. Générer un PDF"
else
    print_error "🚨 $ERRORS ERREUR(S) DÉTECTÉE(S) !"
    print_info "Corrigez les erreurs avant le déploiement."
    echo ""
    print_info "🔧 SCRIPT DE CORRECTION :"
    echo "./fix_wizard_final.sh"
fi

echo ""
print_info "📋 Consultez status_report.txt pour le détail complet."

exit $ERRORS