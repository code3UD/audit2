#!/bin/bash
# Diagnostic complet : Pourquoi aucun audit n'est créé ?

echo "🔍 DIAGNOSTIC CRÉATION D'AUDITS - AUDITDIGITAL"
echo "=============================================="

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

ERRORS=0
WARNINGS=0

print_info "=== 1. VÉRIFICATION DE LA STRUCTURE DU MODULE ==="

MODULE_PATH="/usr/share/dolibarr/htdocs/custom/auditdigital"

if [ ! -d "$MODULE_PATH" ]; then
    print_error "Module non installé : $MODULE_PATH"
    ((ERRORS++))
    exit 1
else
    print_status "Module trouvé : $MODULE_PATH"
fi

print_info "\n=== 2. VÉRIFICATION DES FICHIERS CRITIQUES ==="

# Fichiers essentiels pour la création d'audits
CRITICAL_FILES=(
    "$MODULE_PATH/class/audit.class.php"
    "$MODULE_PATH/wizard/index.php"
    "$MODULE_PATH/admin/setup.php"
    "$MODULE_PATH/core/modules/modAuditDigital.class.php"
    "$MODULE_PATH/sql/llx_auditdigital_audit.sql"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "Fichier présent : $(basename $file)"
    else
        print_error "Fichier manquant : $file"
        ((ERRORS++))
    fi
done

print_info "\n=== 3. VÉRIFICATION DES ERREURS PHP ==="

# Vérifier les erreurs de syntaxe PHP
print_info "Test syntaxe PHP des fichiers critiques..."

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        if php -l "$file" > /dev/null 2>&1; then
            print_status "Syntaxe OK : $(basename $file)"
        else
            print_error "Erreur syntaxe : $file"
            php -l "$file"
            ((ERRORS++))
        fi
    fi
done

print_info "\n=== 4. VÉRIFICATION DES CLASSES DUPLIQUÉES ==="

# Chercher les classes dupliquées
print_info "Recherche de classes dupliquées..."

DUPLICATE_CLASSES=$(grep -r "class ModelePDFAudit" "$MODULE_PATH" 2>/dev/null | wc -l)
if [ "$DUPLICATE_CLASSES" -gt 1 ]; then
    print_error "Classe ModelePDFAudit dupliquée ($DUPLICATE_CLASSES fois) :"
    grep -r "class ModelePDFAudit" "$MODULE_PATH" 2>/dev/null
    ((ERRORS++))
else
    print_status "Pas de classe dupliquée détectée"
fi

print_info "\n=== 5. VÉRIFICATION DE LA BASE DE DONNÉES ==="

# Vérifier si les tables existent
print_info "Vérification des tables de base de données..."

DB_CONFIG="/etc/dolibarr/conf.php"
if [ -f "$DB_CONFIG" ]; then
    # Extraire les informations de connexion
    DB_HOST=$(grep '$dolibarr_main_db_host' "$DB_CONFIG" | cut -d'"' -f2)
    DB_NAME=$(grep '$dolibarr_main_db_name' "$DB_CONFIG" | cut -d'"' -f2)
    DB_USER=$(grep '$dolibarr_main_db_user' "$DB_CONFIG" | cut -d'"' -f2)
    DB_PASS=$(grep '$dolibarr_main_db_pass' "$DB_CONFIG" | cut -d'"' -f2)
    
    print_info "Base de données : $DB_NAME sur $DB_HOST"
    
    # Vérifier les tables
    TABLES_QUERY="SHOW TABLES LIKE 'llx_auditdigital_%'"
    TABLES_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$TABLES_QUERY" 2>/dev/null | wc -l)
    
    if [ "$TABLES_COUNT" -gt 1 ]; then
        print_status "Tables AuditDigital trouvées ($((TABLES_COUNT-1)) tables)"
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$TABLES_QUERY" 2>/dev/null
    else
        print_error "Aucune table AuditDigital trouvée"
        print_info "Exécution des scripts SQL..."
        
        for sql_file in "$MODULE_PATH"/sql/*.sql; do
            if [ -f "$sql_file" ]; then
                print_info "Exécution : $(basename $sql_file)"
                mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file" 2>/dev/null
            fi
        done
        ((WARNINGS++))
    fi
else
    print_warning "Configuration DB non trouvée : $DB_CONFIG"
    ((WARNINGS++))
fi

print_info "\n=== 6. VÉRIFICATION DU MODULE DOLIBARR ==="

# Vérifier si le module est activé
print_info "Vérification de l'activation du module..."

DOLIBARR_CONFIG="/var/lib/dolibarr/documents/install.lock"
if [ -f "$DOLIBARR_CONFIG" ]; then
    print_status "Dolibarr installé"
else
    print_warning "Dolibarr peut ne pas être complètement installé"
    ((WARNINGS++))
fi

print_info "\n=== 7. VÉRIFICATION DES PERMISSIONS ==="

# Vérifier les permissions
print_info "Vérification des permissions..."

OWNER=$(stat -c '%U:%G' "$MODULE_PATH")
if [ "$OWNER" = "www-data:www-data" ]; then
    print_status "Permissions correctes : $OWNER"
else
    print_warning "Permissions incorrectes : $OWNER (devrait être www-data:www-data)"
    print_info "Correction des permissions..."
    sudo chown -R www-data:www-data "$MODULE_PATH"
    ((WARNINGS++))
fi

print_info "\n=== 8. TEST DE CRÉATION D'AUDIT ==="

# Créer un script de test PHP
TEST_SCRIPT="/tmp/test_audit_creation.php"
cat > "$TEST_SCRIPT" << 'EOF'
<?php
// Test de création d'audit

// Configuration Dolibarr
$dolibarr_main_document_root = '/usr/share/dolibarr/htdocs';
require_once $dolibarr_main_document_root.'/main.inc.php';

// Inclure la classe Audit
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';

echo "=== TEST CRÉATION AUDIT ===\n";

try {
    // Créer une instance d'audit
    $audit = new Audit($db);
    echo "✅ Classe Audit chargée avec succès\n";
    
    // Tester les propriétés de base
    $audit->ref = 'TEST-' . date('YmdHis');
    $audit->label = 'Test Audit Diagnostic';
    $audit->audit_type = 'tpe_pme';
    $audit->structure_type = 'tpe_pme';
    $audit->fk_soc = 1; // Société par défaut
    $audit->fk_user_creat = 1; // Utilisateur admin
    $audit->date_creation = date('Y-m-d H:i:s');
    $audit->status = 0;
    
    echo "✅ Propriétés définies\n";
    
    // Tenter la création
    $result = $audit->create($user);
    
    if ($result > 0) {
        echo "✅ AUDIT CRÉÉ AVEC SUCCÈS ! ID: $result\n";
        echo "✅ Référence: " . $audit->ref . "\n";
        
        // Tester la récupération
        $audit2 = new Audit($db);
        $fetch_result = $audit2->fetch($result);
        
        if ($fetch_result > 0) {
            echo "✅ Audit récupéré avec succès\n";
            echo "✅ Label: " . $audit2->label . "\n";
        } else {
            echo "❌ Erreur lors de la récupération\n";
        }
        
    } else {
        echo "❌ ERREUR CRÉATION AUDIT\n";
        echo "❌ Code erreur: $result\n";
        echo "❌ Erreurs: " . implode(', ', $audit->errors) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "❌ Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    echo "❌ Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN TEST ===\n";
?>
EOF

print_info "Exécution du test de création d'audit..."
php "$TEST_SCRIPT" 2>&1

print_info "\n=== 9. VÉRIFICATION DES LOGS APACHE ==="

# Vérifier les dernières erreurs Apache
print_info "Dernières erreurs Apache liées à auditdigital..."
sudo tail -20 /var/log/apache2/error.log | grep -i auditdigital || print_info "Aucune erreur récente trouvée"

print_info "\n=== 10. GÉNÉRATION DU RAPPORT DE DIAGNOSTIC ==="

# Créer un rapport de diagnostic
REPORT_FILE="/tmp/diagnostic_audit_$(date +%Y%m%d_%H%M%S).txt"
cat > "$REPORT_FILE" << EOF
RAPPORT DE DIAGNOSTIC - AUDITDIGITAL
====================================
Date: $(date)
Serveur: $(hostname)
Module: $MODULE_PATH

RÉSUMÉ:
- Erreurs critiques: $ERRORS
- Avertissements: $WARNINGS

FICHIERS VÉRIFIÉS:
$(for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file"
    fi
done)

PERMISSIONS:
- Propriétaire: $(stat -c '%U:%G' "$MODULE_PATH" 2>/dev/null || echo "Erreur")

BASE DE DONNÉES:
- Tables AuditDigital: $((TABLES_COUNT-1)) trouvées

RECOMMANDATIONS:
EOF

if [ $ERRORS -gt 0 ]; then
    echo "1. Corriger les erreurs critiques identifiées" >> "$REPORT_FILE"
    echo "2. Vérifier la synchronisation avec GitHub" >> "$REPORT_FILE"
    echo "3. Réinstaller le module si nécessaire" >> "$REPORT_FILE"
else
    echo "1. Module semble correctement installé" >> "$REPORT_FILE"
    echo "2. Tester la création d'audit via l'interface" >> "$REPORT_FILE"
fi

print_status "Rapport généré : $REPORT_FILE"

print_info "\n=== RÉSULTAT FINAL ==="

if [ $ERRORS -eq 0 ]; then
    print_status "🎉 DIAGNOSTIC TERMINÉ - Aucune erreur critique"
    print_info "Le module semble correctement installé."
    print_info "Testez la création d'audit : http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
else
    print_error "🚨 $ERRORS ERREUR(S) CRITIQUE(S) DÉTECTÉE(S)"
    print_info "Consultez le rapport : $REPORT_FILE"
    print_info "Appliquez les corrections suggérées"
fi

if [ $WARNINGS -gt 0 ]; then
    print_warning "⚠️  $WARNINGS AVERTISSEMENT(S)"
fi

exit $ERRORS