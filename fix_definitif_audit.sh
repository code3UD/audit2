#!/bin/bash
# Correction définitive des erreurs de création d'audits

echo "🔧 CORRECTION DÉFINITIVE - CRÉATION D'AUDITS"
echo "============================================"

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

MODULE_PATH="/usr/share/dolibarr/htdocs/custom/auditdigital"

print_info "=== 1. ANALYSE PRÉCISE DES ERREURS ==="

# Identifier tous les fichiers contenant ModelePDFAudit
print_info "Recherche de toutes les occurrences de ModelePDFAudit..."
grep -r "class ModelePDFAudit" "$MODULE_PATH" 2>/dev/null || print_info "Aucune classe ModelePDFAudit trouvée"

print_info "\n=== 2. SUPPRESSION DÉFINITIVE DES CLASSES DUPLIQUÉES ==="

# Fichier modules_audit.php - garder seulement ModeleNumRefAudit
MODULES_FILE="$MODULE_PATH/core/modules/auditdigital/modules_audit.php"
if [ -f "$MODULES_FILE" ]; then
    print_info "Nettoyage complet de $MODULES_FILE..."
    
    # Créer une sauvegarde
    sudo cp "$MODULES_FILE" "$MODULES_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Créer une version propre avec seulement ModeleNumRefAudit
    cat << 'EOF' | sudo tee "$MODULES_FILE" > /dev/null
<?php
/* Copyright (C) 2025 Up Digit Agency
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/modules/auditdigital/modules_audit.php
 * \ingroup    auditdigital
 * \brief      File with class to manage audit numbering rules
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/auditdigital/modules_auditdigital.php';

/**
 * Class to manage audit numbering rules standard
 */
class ModeleNumRefAudit extends ModeleNumRefAuditDigital
{
    /**
     * @var string model name
     */
    public $name = 'standard';

    /**
     * @var string model description (short text)
     */
    public $description = "Numbering model for audits";

    /**
     * @var int Automatic numbering
     */
    public $code_auto = 1;

    /**
     * Return next free value
     *
     * @param  Societe $objsoc Object thirdparty
     * @param  Audit   $object Object we need next value for
     * @return string          Value if KO, <0 if KO
     */
    public function getNextValue($objsoc, $object)
    {
        global $db, $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

        $mask = 'AUD{yy}{mm}-{####}';
        
        if (!empty($conf->global->AUDITDIGITAL_AUDIT_ADDON_NUMBER)) {
            $mask = $conf->global->AUDITDIGITAL_AUDIT_ADDON_NUMBER;
        }

        $numExample = $this->getExample();

        $date = (empty($object->date_creation) ? dol_now() : $object->date_creation);
        $numFinal = get_next_value($db, $mask, 'auditdigital_audit', 'ref', '', $objsoc, $date);

        return $numFinal;
    }

    /**
     * Return an example of numbering
     *
     * @return string Example
     */
    public function getExample()
    {
        $example = 'AUD' . date('y') . date('m') . '-0001';
        return $example;
    }

    /**
     * Checks if the numbers already in the database do not
     * cause conflicts that would prevent this numbering working.
     *
     * @param  Object $object Object we need next value for
     * @return boolean        false if conflict, true if ok
     */
    public function canBeActivated($object)
    {
        global $conf, $langs, $db;

        $coyymm = '';
        $max = '';

        $posindice = strlen($this->prefix) + 6;
        $sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
        $sql .= " FROM ".MAIN_DB_PREFIX."auditdigital_audit";
        $sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
        if ($object->ismultientitymanaged == 1) {
            $sql .= " AND entity = ".$conf->entity;
        }

        $resql = $db->query($sql);
        if ($resql) {
            $row = $db->fetch_row($resql);
            if ($row) {
                $coyymm = substr($row[0], 0, 6);
                $max = $row[0];
            }
        }

        if (!$coyymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm)) {
            return true;
        } else {
            $langs->load("errors");
            $this->error = $langs->trans('ErrorNumRefModel', $max);
            return false;
        }
    }
}
EOF

    print_status "Fichier modules_audit.php nettoyé - garde seulement ModeleNumRefAudit"
else
    print_error "Fichier modules_audit.php non trouvé"
fi

print_info "\n=== 3. CORRECTION DES FICHIERS PDF ==="

# Corriger pdf_audit_tpe.modules.php
PDF_TPE_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_tpe.modules.php"
if [ -f "$PDF_TPE_FILE" ]; then
    print_info "Correction de pdf_audit_tpe.modules.php..."
    
    sudo cp "$PDF_TPE_FILE" "$PDF_TPE_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # S'assurer que la propriété scandir est définie une seule fois
    if ! grep -q "public \$scandir" "$PDF_TPE_FILE"; then
        sudo sed -i '/class.*ModelePDFAudit/a\    public $scandir = DOLI_DATA_ROOT."/doctemplates/auditdigital/";' "$PDF_TPE_FILE"
    fi
    
    print_status "pdf_audit_tpe.modules.php corrigé"
fi

# Corriger pdf_audit_collectivite.modules.php
PDF_COLL_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php"
if [ -f "$PDF_COLL_FILE" ]; then
    print_info "Correction de pdf_audit_collectivite.modules.php..."
    
    sudo cp "$PDF_COLL_FILE" "$PDF_COLL_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # S'assurer que la propriété scandir est définie une seule fois
    if ! grep -q "public \$scandir" "$PDF_COLL_FILE"; then
        sudo sed -i '/class.*ModelePDFAudit/a\    public $scandir = DOLI_DATA_ROOT."/doctemplates/auditdigital/";' "$PDF_COLL_FILE"
    fi
    
    print_status "pdf_audit_collectivite.modules.php corrigé"
fi

print_info "\n=== 4. CORRECTION DES CHEMINS D'INCLUSION ==="

# Corriger mod_audit_standard.php
MOD_FILE="$MODULE_PATH/core/modules/auditdigital/mod_audit_standard.php"
if [ -f "$MOD_FILE" ]; then
    print_info "Correction des chemins dans mod_audit_standard.php..."
    
    sudo cp "$MOD_FILE" "$MOD_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Corriger tous les chemins d'inclusion
    sudo sed -i "s|DOL_DOCUMENT_ROOT.'/core/modules/auditdigital/|DOL_DOCUMENT_ROOT.'/custom/auditdigital/core/modules/auditdigital/|g" "$MOD_FILE"
    
    print_status "Chemins corrigés dans mod_audit_standard.php"
fi

print_info "\n=== 5. CORRECTION DE LA CLASSE AUDIT ==="

# Vérifier et corriger audit.class.php
AUDIT_CLASS="$MODULE_PATH/class/audit.class.php"
if [ -f "$AUDIT_CLASS" ]; then
    print_info "Vérification de la classe Audit..."
    
    # Vérifier si la méthode create existe
    if ! grep -q "function create" "$AUDIT_CLASS"; then
        print_info "Ajout de la méthode create manquante..."
        
        sudo cp "$AUDIT_CLASS" "$AUDIT_CLASS.backup.$(date +%Y%m%d_%H%M%S)"
        
        # Ajouter la méthode create avant la dernière accolade
        sudo sed -i '$i\
\
    /**\
     * Create audit in database\
     *\
     * @param  User $user      User that creates\
     * @param  int  $notrigger 0=launch triggers after, 1=disable triggers\
     * @return int             <0 if KO, Id of created object if OK\
     */\
    public function create($user, $notrigger = 0)\
    {\
        global $conf, $langs;\
        \
        $error = 0;\
        \
        // Clean parameters\
        if (isset($this->ref)) $this->ref = trim($this->ref);\
        if (isset($this->label)) $this->label = trim($this->label);\
        if (isset($this->audit_type)) $this->audit_type = trim($this->audit_type);\
        if (isset($this->structure_type)) $this->structure_type = trim($this->structure_type);\
        \
        // Check parameters\
        if (empty($this->ref)) {\
            $this->ref = $this->getNextNumRef();\
        }\
        \
        if (empty($this->label)) {\
            $this->error = "ErrorFieldRequired";\
            $this->errors[] = "Label is required";\
            return -1;\
        }\
        \
        $this->db->begin();\
        \
        // Insert request\
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."auditdigital_audit(";\
        $sql .= "ref,";\
        $sql .= "label,";\
        $sql .= "audit_type,";\
        $sql .= "structure_type,";\
        $sql .= "fk_soc,";\
        $sql .= "fk_projet,";\
        $sql .= "date_creation,";\
        $sql .= "fk_user_creat,";\
        $sql .= "status,";\
        $sql .= "entity";\
        $sql .= ") VALUES (";\
        $sql .= "'"'"'.$this->db->escape($this->ref)."'"'"',";\
        $sql .= "'"'"'.$this->db->escape($this->label)."'"'"',";\
        $sql .= "'"'"'.$this->db->escape($this->audit_type)."'"'"',";\
        $sql .= "'"'"'.$this->db->escape($this->structure_type)."'"'"',";\
        $sql .= " ".((int) $this->fk_soc).",";\
        $sql .= " ".((int) $this->fk_projet).",";\
        $sql .= " '"'"'.$this->db->idate(dol_now())."'"'"',";\
        $sql .= " ".((int) $user->id).",";\
        $sql .= " ".((int) $this->status).",";\
        $sql .= " ".((int) $conf->entity);\
        $sql .= ")";\
        \
        $resql = $this->db->query($sql);\
        \
        if (!$resql) {\
            $error++;\
            $this->errors[] = "Error ".$this->db->lasterror();\
        }\
        \
        if (!$error) {\
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."auditdigital_audit");\
            \
            if (!$notrigger) {\
                // Call triggers\
                $result = $this->call_trigger("AUDIT_CREATE", $user);\
                if ($result < 0) {\
                    $error++;\
                }\
            }\
        }\
        \
        // Commit or rollback\
        if ($error) {\
            foreach ($this->errors as $errmsg) {\
                dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);\
                $this->error .= ($this->error ? ", ".$errmsg : $errmsg);\
            }\
            $this->db->rollback();\
            return -1 * $error;\
        } else {\
            $this->db->commit();\
            return $this->id;\
        }\
    }\
\
    /**\
     * Get next reference\
     *\
     * @return string Next reference\
     */\
    public function getNextNumRef()\
    {\
        global $conf, $db;\
        \
        $mask = "AUD{yy}{mm}-{####}";\
        \
        require_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";\
        return get_next_value($db, $mask, "auditdigital_audit", "ref", "", null, dol_now());\
    }' "$AUDIT_CLASS"
        
        print_status "Méthode create ajoutée à la classe Audit"
    else
        print_status "Méthode create déjà présente"
    fi
else
    print_error "Classe Audit non trouvée"
fi

print_info "\n=== 6. CORRECTION DU SETUP ADMIN ==="

# Corriger admin/setup.php
SETUP_FILE="$MODULE_PATH/admin/setup.php"
if [ -f "$SETUP_FILE" ]; then
    print_info "Correction de admin/setup.php..."
    
    sudo cp "$SETUP_FILE" "$SETUP_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Ajouter l'inclusion de la classe Audit si manquante
    if ! grep -q "audit.class.php" "$SETUP_FILE"; then
        sudo sed -i '/require.*main.inc.php/a require_once DOL_DOCUMENT_ROOT."/custom/auditdigital/class/audit.class.php";' "$SETUP_FILE"
        print_status "Inclusion classe Audit ajoutée"
    fi
    
    # Corriger les chemins vers les fichiers admin
    sudo sed -i 's|/auditdigital/admin/|/custom/auditdigital/admin/|g' "$SETUP_FILE"
    
    print_status "Setup admin corrigé"
fi

print_info "\n=== 7. INSTALLATION/VÉRIFICATION DES TABLES ==="

# Installer les tables SQL
print_info "Vérification et installation des tables..."

DB_CONFIG="/etc/dolibarr/conf.php"
if [ -f "$DB_CONFIG" ]; then
    DB_HOST=$(grep '$dolibarr_main_db_host' "$DB_CONFIG" | cut -d'"' -f2)
    DB_NAME=$(grep '$dolibarr_main_db_name' "$DB_CONFIG" | cut -d'"' -f2)
    DB_USER=$(grep '$dolibarr_main_db_user' "$DB_CONFIG" | cut -d'"' -f2)
    DB_PASS=$(grep '$dolibarr_main_db_pass' "$DB_CONFIG" | cut -d'"' -f2)
    
    # Vérifier si les tables existent
    TABLES_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE 'llx_auditdigital_%'" 2>/dev/null | wc -l)
    
    if [ "$TABLES_COUNT" -lt 2 ]; then
        print_info "Installation des tables SQL..."
        for sql_file in "$MODULE_PATH"/sql/*.sql; do
            if [ -f "$sql_file" ]; then
                print_info "Exécution : $(basename $sql_file)"
                mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file" 2>/dev/null
            fi
        done
    else
        print_status "Tables déjà présentes ($TABLES_COUNT tables)"
    fi
else
    print_warning "Configuration DB non trouvée"
fi

print_info "\n=== 8. TEST DE CRÉATION D'AUDIT ==="

# Créer un script de test PHP complet
TEST_SCRIPT="/tmp/test_audit_creation_complet.php"
cat > "$TEST_SCRIPT" << 'EOF'
<?php
// Test complet de création d'audit

// Configuration Dolibarr
$dolibarr_main_document_root = '/usr/share/dolibarr/htdocs';
require_once $dolibarr_main_document_root.'/main.inc.php';

echo "=== TEST COMPLET CRÉATION AUDIT ===\n";

try {
    // Inclure la classe Audit
    require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
    echo "✅ Classe Audit chargée\n";
    
    // Créer une instance d'audit
    $audit = new Audit($db);
    echo "✅ Instance Audit créée\n";
    
    // Définir les propriétés
    $audit->ref = 'TEST-' . date('YmdHis');
    $audit->label = 'Test Audit Création - ' . date('Y-m-d H:i:s');
    $audit->audit_type = 'tpe_pme';
    $audit->structure_type = 'tpe_pme';
    $audit->fk_soc = 1;
    $audit->fk_user_creat = 1;
    $audit->status = 0;
    
    echo "✅ Propriétés définies\n";
    echo "   - Ref: " . $audit->ref . "\n";
    echo "   - Label: " . $audit->label . "\n";
    echo "   - Type: " . $audit->audit_type . "\n";
    
    // Tenter la création
    $result = $audit->create($user);
    
    if ($result > 0) {
        echo "✅ AUDIT CRÉÉ AVEC SUCCÈS !\n";
        echo "   - ID: $result\n";
        echo "   - Référence: " . $audit->ref . "\n";
        
        // Vérifier en base
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."auditdigital_audit WHERE rowid = ".(int)$result;
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            echo "✅ Audit trouvé en base :\n";
            echo "   - ID: " . $obj->rowid . "\n";
            echo "   - Ref: " . $obj->ref . "\n";
            echo "   - Label: " . $obj->label . "\n";
            echo "   - Date création: " . $obj->date_creation . "\n";
        } else {
            echo "❌ Audit non trouvé en base\n";
        }
        
    } else {
        echo "❌ ERREUR CRÉATION AUDIT\n";
        echo "   - Code erreur: $result\n";
        if (!empty($audit->errors)) {
            echo "   - Erreurs: " . implode(', ', $audit->errors) . "\n";
        }
        if (!empty($audit->error)) {
            echo "   - Erreur: " . $audit->error . "\n";
        }
    }
    
    // Compter le nombre total d'audits
    $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."auditdigital_audit";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        echo "📊 Nombre total d'audits en base: " . $obj->nb . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "   - Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    echo "   - Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN TEST ===\n";
?>
EOF

print_info "Exécution du test complet de création d'audit..."
php "$TEST_SCRIPT" 2>&1

print_info "\n=== 9. REDÉMARRAGE ET NETTOYAGE ==="

# Redémarrer Apache
sudo systemctl restart apache2
print_status "Apache redémarré"

# Nettoyer le cache PHP si nécessaire
if command -v php >/dev/null 2>&1; then
    sudo php -r "if (function_exists('opcache_reset')) opcache_reset();"
    print_status "Cache PHP nettoyé"
fi

print_info "\n=== 10. VÉRIFICATION FINALE ==="

# Test d'accès au wizard
TEST_URL="http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$TEST_URL" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    print_status "Wizard accessible (HTTP $HTTP_CODE)"
else
    print_warning "Problème d'accès au wizard (HTTP $HTTP_CODE)"
fi

print_info "\n=== RÉSULTAT FINAL ==="

print_status "🎉 CORRECTIONS DÉFINITIVES APPLIQUÉES !"
echo ""
print_info "🧪 TESTEZ MAINTENANT :"
echo "1. Wizard: $TEST_URL"
echo "2. Créez un audit de test"
echo "3. Vérifiez la sauvegarde en base"
echo ""
print_info "🔍 SURVEILLANCE :"
echo "sudo tail -f /var/log/apache2/error.log | grep auditdigital"
echo ""
print_info "📊 VÉRIFICATION BASE :"
echo "mysql -u dolibarr -p dolibarr -e \"SELECT * FROM llx_auditdigital_audit ORDER BY rowid DESC LIMIT 5;\""

exit 0