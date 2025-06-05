#!/bin/bash
# Correction spécifique pour la création d'audits

echo "🔧 CORRECTION CRÉATION D'AUDITS - AUDITDIGITAL"
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

MODULE_PATH="/usr/share/dolibarr/htdocs/custom/auditdigital"

print_info "=== 1. CORRECTION DES CLASSES DUPLIQUÉES ==="

# Corriger modules_audit.php - supprimer ModelePDFAudit
MODULES_FILE="$MODULE_PATH/core/modules/auditdigital/modules_audit.php"
if [ -f "$MODULES_FILE" ]; then
    print_info "Correction de $MODULES_FILE..."
    
    # Créer une sauvegarde
    sudo cp "$MODULES_FILE" "$MODULES_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Supprimer la classe ModelePDFAudit et CommonDocGeneratorAudit
    sudo sed -i '/^class ModelePDFAudit/,/^}/d' "$MODULES_FILE"
    sudo sed -i '/^class CommonDocGeneratorAudit/,/^}/d' "$MODULES_FILE"
    
    print_status "Classes dupliquées supprimées"
else
    print_error "Fichier non trouvé : $MODULES_FILE"
fi

print_info "\n=== 2. CORRECTION DES CHEMINS D'INCLUSION ==="

# Corriger mod_audit_standard.php
MOD_FILE="$MODULE_PATH/core/modules/auditdigital/mod_audit_standard.php"
if [ -f "$MOD_FILE" ]; then
    print_info "Correction des chemins dans $MOD_FILE..."
    
    sudo cp "$MOD_FILE" "$MOD_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Corriger le chemin d'inclusion
    sudo sed -i "s|require_once DOL_DOCUMENT_ROOT.'/core/modules/auditdigital/modules_audit.php';|require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/core/modules/auditdigital/modules_audit.php';|g" "$MOD_FILE"
    
    print_status "Chemins corrigés"
fi

print_info "\n=== 3. CORRECTION DE LA CLASSE AUDIT ==="

# Vérifier et corriger audit.class.php
AUDIT_CLASS="$MODULE_PATH/class/audit.class.php"
if [ -f "$AUDIT_CLASS" ]; then
    print_info "Vérification de la classe Audit..."
    
    # Vérifier si la méthode create existe
    if grep -q "function create" "$AUDIT_CLASS"; then
        print_status "Méthode create trouvée"
    else
        print_warning "Méthode create manquante - ajout..."
        
        # Ajouter la méthode create si manquante
        sudo cp "$AUDIT_CLASS" "$AUDIT_CLASS.backup.$(date +%Y%m%d_%H%M%S)"
        
        cat << 'EOF' | sudo tee -a "$AUDIT_CLASS" > /dev/null

    /**
     * Create audit in database
     *
     * @param  User $user      User that creates
     * @param  int  $notrigger 0=launch triggers after, 1=disable triggers
     * @return int             <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        
        $error = 0;
        
        // Clean parameters
        if (isset($this->ref)) $this->ref = trim($this->ref);
        if (isset($this->label)) $this->label = trim($this->label);
        if (isset($this->audit_type)) $this->audit_type = trim($this->audit_type);
        if (isset($this->structure_type)) $this->structure_type = trim($this->structure_type);
        
        // Check parameters
        if (empty($this->ref)) {
            $this->ref = $this->getNextNumRef();
        }
        
        if (empty($this->label)) {
            $this->error = 'ErrorFieldRequired';
            $this->errors[] = 'Label is required';
            return -1;
        }
        
        $this->db->begin();
        
        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."auditdigital_audit(";
        $sql .= "ref,";
        $sql .= "label,";
        $sql .= "audit_type,";
        $sql .= "structure_type,";
        $sql .= "fk_soc,";
        $sql .= "fk_projet,";
        $sql .= "date_creation,";
        $sql .= "fk_user_creat,";
        $sql .= "status,";
        $sql .= "entity";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."',";
        $sql .= "'".$this->db->escape($this->label)."',";
        $sql .= "'".$this->db->escape($this->audit_type)."',";
        $sql .= "'".$this->db->escape($this->structure_type)."',";
        $sql .= " ".((int) $this->fk_soc).",";
        $sql .= " ".((int) $this->fk_projet).",";
        $sql .= " '".$this->db->idate(dol_now())."',";
        $sql .= " ".((int) $user->id).",";
        $sql .= " ".((int) $this->status).",";
        $sql .= " ".((int) $conf->entity);
        $sql .= ")";
        
        $this->db->query($sql);
        
        if (!$this->db->query($sql)) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }
        
        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."auditdigital_audit");
            
            if (!$notrigger) {
                // Call triggers
                $result = $this->call_trigger('AUDIT_CREATE', $user);
                if ($result < 0) {
                    $error++;
                }
            }
        }
        
        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }
    
    /**
     * Get next reference
     *
     * @return string Next reference
     */
    public function getNextNumRef()
    {
        global $conf, $db;
        
        $mask = 'AUD{yy}{mm}-{####}';
        
        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        return get_next_value($db, $mask, 'auditdigital_audit', 'ref', '', null, dol_now());
    }
EOF
        
        print_status "Méthode create ajoutée"
    fi
else
    print_error "Classe Audit non trouvée : $AUDIT_CLASS"
fi

print_info "\n=== 4. CORRECTION DU SETUP ADMIN ==="

# Corriger admin/setup.php
SETUP_FILE="$MODULE_PATH/admin/setup.php"
if [ -f "$SETUP_FILE" ]; then
    print_info "Correction de $SETUP_FILE..."
    
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

print_info "\n=== 5. INSTALLATION DES TABLES ==="

# Installer les tables SQL
print_info "Installation des tables de base de données..."

DB_CONFIG="/etc/dolibarr/conf.php"
if [ -f "$DB_CONFIG" ]; then
    DB_HOST=$(grep '$dolibarr_main_db_host' "$DB_CONFIG" | cut -d'"' -f2)
    DB_NAME=$(grep '$dolibarr_main_db_name' "$DB_CONFIG" | cut -d'"' -f2)
    DB_USER=$(grep '$dolibarr_main_db_user' "$DB_CONFIG" | cut -d'"' -f2)
    DB_PASS=$(grep '$dolibarr_main_db_pass' "$DB_CONFIG" | cut -d'"' -f2)
    
    for sql_file in "$MODULE_PATH"/sql/*.sql; do
        if [ -f "$sql_file" ]; then
            print_info "Exécution : $(basename $sql_file)"
            mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file" 2>/dev/null
            if [ $? -eq 0 ]; then
                print_status "$(basename $sql_file) exécuté"
            else
                print_warning "Erreur avec $(basename $sql_file)"
            fi
        fi
    done
else
    print_warning "Configuration DB non trouvée"
fi

print_info "\n=== 6. CORRECTION DES PERMISSIONS ==="

# Corriger les permissions
print_info "Correction des permissions..."
sudo chown -R www-data:www-data "$MODULE_PATH"
sudo chmod -R 644 "$MODULE_PATH"
sudo find "$MODULE_PATH" -type d -exec chmod 755 {} \;
print_status "Permissions corrigées"

print_info "\n=== 7. REDÉMARRAGE D'APACHE ==="

# Redémarrer Apache
sudo systemctl restart apache2
print_status "Apache redémarré"

print_info "\n=== 8. TEST FINAL ==="

# Test final de création d'audit
TEST_URL="http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
print_info "Test d'accès au wizard..."

if curl -s -o /dev/null -w "%{http_code}" "$TEST_URL" | grep -q "200"; then
    print_status "Wizard accessible"
else
    print_warning "Problème d'accès au wizard"
fi

print_info "\n=== RÉSULTAT ==="

print_status "🎉 CORRECTIONS APPLIQUÉES !"
echo ""
print_info "🧪 TESTEZ MAINTENANT :"
echo "1. Accédez au wizard : $TEST_URL"
echo "2. Créez un audit de test"
echo "3. Vérifiez la génération PDF"
echo ""
print_info "🔍 SURVEILLANCE :"
echo "sudo tail -f /var/log/apache2/error.log | grep auditdigital"

exit 0