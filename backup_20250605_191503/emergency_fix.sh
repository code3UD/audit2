#!/bin/bash
# Script de correction d'urgence pour AuditDigital

echo "🚨 CORRECTION D'URGENCE - AuditDigital"
echo "======================================"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Vérifier les privilèges root
if [[ $EUID -ne 0 ]]; then
   print_error "Ce script doit être exécuté en tant que root (sudo)"
   exit 1
fi

DOLIBARR_PATH="/usr/share/dolibarr/htdocs"
MODULE_PATH="$DOLIBARR_PATH/custom/auditdigital"

print_info "=== CORRECTION D'URGENCE ==="

# 1. CORRECTION CRITIQUE : mod_audit_standard.php
MOD_FILE="$MODULE_PATH/core/modules/auditdigital/mod_audit_standard.php"
if [ -f "$MOD_FILE" ]; then
    print_info "🔥 CORRECTION CRITIQUE: mod_audit_standard.php"
    
    # Backup
    cp "$MOD_FILE" "$MOD_FILE.emergency.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Réécrire complètement le fichier avec le bon chemin
    cat > "$MOD_FILE" << 'EOF'
<?php
/* Copyright (C) 2025 Up Digit Agency
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/modules/auditdigital/mod_audit_standard.php
 * \ingroup    auditdigital
 * \brief      File containing class for standard audit numbering module
 */

require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/core/modules/auditdigital/modules_audit.php';

/**
 * Class to manage audit numbering rules standard
 */
class mod_audit_standard extends ModeleNumRefAudit
{
    /**
     * @var string model name
     */
    public $name = 'standard';

    /**
     * @var string model description (short text)
     */
    public $description = "Standard numbering for audits";

    /**
     * @var string Version
     */
    public $version = 'dolibarr';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->name = "standard";
        $this->description = "Standard numbering for audits";
    }

    /**
     * Return description of numbering model
     *
     * @return string Text with description
     */
    public function info()
    {
        global $langs;
        return $langs->trans("StandardModel");
    }

    /**
     * Return an example of numbering
     *
     * @return string Example
     */
    public function getExample()
    {
        return "AUD2025-0001";
    }

    /**
     * Return next free value
     *
     * @param Societe $objsoc Object thirdparty
     * @param Audit $object Object we need a new number for
     * @return string Value if OK, 0 if KO
     */
    public function getNextValue($objsoc, $object)
    {
        global $db, $conf;

        // Get current year
        $year = date('Y');
        
        // Find max number for this year
        $sql = "SELECT MAX(CAST(SUBSTRING(ref, 10) AS UNSIGNED)) as max";
        $sql .= " FROM ".MAIN_DB_PREFIX."auditdigital_audit";
        $sql .= " WHERE ref LIKE 'AUD".$year."-%'";
        $sql .= " AND entity = ".$conf->entity;

        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            $max = $obj->max;
            $db->free($resql);
        } else {
            $max = 0;
        }

        $num = $max + 1;
        return sprintf("AUD%s-%04d", $year, $num);
    }
}
?>
EOF
    
    print_status "mod_audit_standard.php réécrit complètement"
else
    print_error "Fichier mod_audit_standard.php non trouvé"
fi

# 2. CORRECTION CRITIQUE : Supprimer la classe dupliquée ModelePDFAudit
MODULES_AUDIT_FILE="$MODULE_PATH/core/modules/auditdigital/modules_audit.php"
if [ -f "$MODULES_AUDIT_FILE" ]; then
    print_info "🔥 CORRECTION: Suppression classe dupliquée dans modules_audit.php"
    
    # Backup
    cp "$MODULES_AUDIT_FILE" "$MODULES_AUDIT_FILE.emergency.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Supprimer la classe ModelePDFAudit de ce fichier (elle doit être seulement dans les PDF)
    sed -i '/class ModelePDFAudit/,/^}/d' "$MODULES_AUDIT_FILE"
    
    print_status "Classe dupliquée supprimée"
fi

# 3. CORRECTION CRITIQUE : PDF TPE avec scandir
TPE_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_tpe.modules.php"
if [ -f "$TPE_FILE" ]; then
    print_info "🔥 CORRECTION: pdf_audit_tpe.modules.php"
    
    # Backup
    cp "$TPE_FILE" "$TPE_FILE.emergency.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Réécrire la classe de base avec scandir
    cat > "$TPE_FILE.tmp" << 'EOF'
<?php
/* Copyright (C) 2025 Up Digit Agency */

// Try to include PDF base class with fallback
if (file_exists(DOL_DOCUMENT_ROOT.'/core/modules/pdf/modules_pdf.php')) {
    require_once DOL_DOCUMENT_ROOT.'/core/modules/pdf/modules_pdf.php';
} elseif (file_exists(DOL_DOCUMENT_ROOT.'/core/class/pdf.class.php')) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/pdf.class.php';
}

require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/lib/auditdigital.lib.php';

// Include other required files with error handling
if (file_exists(DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php')) {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
}
if (file_exists(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php')) {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
}
if (file_exists(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php')) {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
}

// Define base PDF class if not exists
if (!class_exists('ModelePDFAudit')) {
    class ModelePDFAudit
    {
        public $db;
        public $name;
        public $description;
        public $type;
        public $page_largeur;
        public $page_hauteur;
        public $format;
        public $marge_gauche;
        public $marge_droite;
        public $marge_haute;
        public $marge_basse;
        public $scandir;
        
        public function __construct($db)
        {
            $this->db = $db;
            $this->name = "audit_tpe";
            $this->description = "Template for TPE/PME audit reports";
            $this->type = 'pdf';
            $this->page_largeur = 210;
            $this->page_hauteur = 297;
            $this->format = array($this->page_largeur, $this->page_hauteur);
            $this->marge_gauche = 10;
            $this->marge_droite = 10;
            $this->marge_haute = 10;
            $this->marge_basse = 10;
            $this->scandir = DOL_DOCUMENT_ROOT.'/custom/auditdigital/core/modules/auditdigital/doc/';
        }
        
        public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
        {
            return 1;
        }
    }
}

class pdf_audit_tpe extends ModelePDFAudit
{
    public $db;
    public $name;
    public $description;
    public $update_main_doc_field;
    public $type;
    public $phpmin = array(7, 0);
    public $version = 'dolibarr';
    public $page_largeur;
    public $page_hauteur;
    public $format;
    public $marge_gauche;
    public $marge_droite;
    public $marge_haute;
    public $marge_basse;
    public $scandir;
    public $emetteur;

    public function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $langs->loadLangs(array("main", "companies"));

        $this->db = $db;
        $this->name = "audit_tpe";
        $this->description = $langs->trans('PDFAuditTPEDescription');
        $this->update_main_doc_field = 1;

        $this->type = 'pdf';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
        $this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
        $this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
        $this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

        $this->scandir = DOL_DOCUMENT_ROOT.'/custom/auditdigital/core/modules/auditdigital/doc/';

        $this->option_logo = 1;
        $this->option_tva = 1;
        $this->option_modereg = 1;
        $this->option_condreg = 1;
        $this->option_multilang = 1;
        $this->option_escompte = 0;
        $this->option_credit_note = 0;
        $this->option_freetext = 1;
        $this->option_draft_watermark = 1;

        $this->posxdesc = $this->marge_gauche + 1;
        $this->posxtva = $this->page_largeur - $this->marge_droite - 13;
        $this->posxup = $this->page_largeur - $this->marge_droite - 26;
        $this->posxqty = $this->page_largeur - $this->marge_droite - 35;
        $this->posxunit = $this->page_largeur - $this->marge_droite - 47;
        $this->posxdiscount = $this->page_largeur - $this->marge_droite - 59;
        $this->postotalht = $this->page_largeur - $this->marge_droite - 2;

        $this->tva = array();
        $this->localtax1 = array();
        $this->localtax2 = array();
        $this->atleastoneratenotnull = 0;
        $this->atleastonediscount = 0;
        $this->atleastoneref = 0;
    }

    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

        dol_syslog("pdf_audit_tpe::write_file", LOG_DEBUG);

        if (!is_object($outputlangs)) $outputlangs = $langs;

        $outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "orders", "deliveries"));

        if ($object->status == $object::STATUS_DRAFT && (!empty($conf->global->AUDIT_DRAFT_WATERMARK))) {
            $this->watermark = $conf->global->AUDIT_DRAFT_WATERMARK;
        }

        $nblines = 0;

        $hidetop = 0;
        if (!empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
            $hidetop = $conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE;
        }

        $pdf = pdf_getInstance($this->format);
        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $pdf->SetAutoPageBreak(1, 0);

        $heightforinfotot = 40;
        $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);
        $heightforfooter = $this->marge_basse + 8;

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
            $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
            $tplidx = $pdf->importPage(1);
        }

        $pdf->Open();
        $pagenb = 0;
        $pdf->SetDrawColor(128, 128, 128);

        $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
        $pdf->SetSubject($outputlangs->transnoentities("Audit"));
        $pdf->SetCreator("Dolibarr ".DOL_VERSION);
        $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
        $pdf->SetKeywords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Audit"));
        if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

        $pdf->AddPage();
        if (!empty($tplidx)) $pdf->useTemplate($tplidx);
        $pagenb++;

        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->MultiCell(0, 3, '');
        $pdf->SetTextColor(0, 0, 0);

        // Add content here
        $pdf->SetXY(10, 30);
        $pdf->SetFont('', 'B', 16);
        $pdf->Cell(0, 10, 'Audit Digital - '.$object->ref, 0, 1, 'C');

        $pdf->SetXY(10, 50);
        $pdf->SetFont('', '', 12);
        $pdf->Cell(0, 10, 'Rapport d\'audit de maturité numérique', 0, 1, 'C');

        if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

        $file = $conf->auditdigital->dir_output.'/'.$object->ref.'/'.$object->ref.'.pdf';
        if (!file_exists($conf->auditdigital->dir_output.'/'.$object->ref)) {
            if (dol_mkdir($conf->auditdigital->dir_output.'/'.$object->ref) < 0) {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $conf->auditdigital->dir_output.'/'.$object->ref);
                return 0;
            }
        }

        $pdf->Output($file, 'F');

        dolChmod($file);

        $this->result = array('fullpath'=>$file);

        return 1;
    }
}
?>
EOF
    
    mv "$TPE_FILE.tmp" "$TPE_FILE"
    print_status "pdf_audit_tpe.modules.php réécrit"
fi

# 4. CORRECTION CRITIQUE : PDF Collectivité avec scandir
COLL_FILE="$MODULE_PATH/core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php"
if [ -f "$COLL_FILE" ]; then
    print_info "🔥 CORRECTION: pdf_audit_collectivite.modules.php"
    
    # Backup
    cp "$COLL_FILE" "$COLL_FILE.emergency.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Ajouter scandir au début de la classe
    sed -i '/public $marge_basse;/a\    public $scandir;' "$COLL_FILE"
    sed -i '/this->marge_basse = isset/a\        $this->scandir = DOL_DOCUMENT_ROOT."/custom/auditdigital/core/modules/auditdigital/doc/";' "$COLL_FILE"
    
    print_status "pdf_audit_collectivite.modules.php corrigé"
fi

# 5. Corriger les permissions
print_info "Correction des permissions..."
chown -R www-data:www-data "$MODULE_PATH"
chmod -R 644 "$MODULE_PATH"
find "$MODULE_PATH" -type d -exec chmod 755 {} \;

print_status "Permissions corrigées"

# 6. Redémarrer Apache
print_info "Redémarrage d'Apache..."
systemctl restart apache2
print_status "Apache redémarré"

print_info "=== VÉRIFICATION ==="
print_status "🚨 CORRECTION D'URGENCE TERMINÉE !"
echo ""
print_info "📋 Tests URGENTS à effectuer :"
echo "1. http://192.168.1.252/dolibarr/custom/auditdigital/admin/setup.php"
echo "2. http://192.168.1.252/dolibarr/custom/auditdigital/wizard/index.php"
echo ""
print_info "🔍 Surveiller les logs :"
echo "sudo tail -f /var/log/apache2/error.log | grep auditdigital"

exit 0