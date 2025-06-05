<?php
/* Copyright (C) 2025 Up Digit Agency
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/custom/auditdigital/core/modules/auditdigital/doc/pdf_audit_collectivite.modules.php
 * \ingroup    auditdigital
 * \brief      File of class to generate PDF for collectivité audits
 */

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
    /**
     * Base class for PDF generation
     */
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
        
        public function __construct($db)
        {
            $this->db = $db;
            $this->name = "audit_collectivite";
            $this->description = "Template for collectivité audit reports";
            $this->type = 'pdf';
            $this->page_largeur = 210;
            $this->page_hauteur = 297;
            $this->format = array($this->page_largeur, $this->page_hauteur);
            $this->marge_gauche = 10;
            $this->marge_droite = 10;
            $this->marge_haute = 10;
            $this->marge_basse = 10;
        }
        
        public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
        {
            // Simple implementation for now
            return 1;
        }
    }
}

/**
 * Class to generate PDF for collectivité audits
 */
class pdf_audit_collectivite extends ModelePDFAudit
{
    /**
     * @var DoliDb Database handler
     */
    public $db;

    /**
     * @var string model name
     */
    public $name;

    /**
     * @var string model description (short text)
     */
    public $description;

    /**
     * @var int Save the name of generated file as the main doc when generating a doc with this template
     */
    public $update_main_doc_field;

    /**
     * @var string document type
     */
    public $type;

    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 7.0 = array(7, 0)
     */
    public $phpmin = array(7, 0);

    /**
     * Dolibarr version of the loaded document
     * @var string
     */
    public $version = 'dolibarr';

    /**
     * @var int page_largeur
     */
    public $page_largeur;
    /**
     * @var int page_hauteur
     */
    public $page_hauteur;
    /**
     * @var array format
     */
    public $format;
    /**
     * @var int marge_gauche
     */
    public $marge_gauche;
    /**
     * @var int marge_droite
     */
    public $marge_droite;
    /**
     * @var int marge_haute
     */
    public $marge_haute;
    /**
     * @var int marge_basse
     */
    public $marge_basse;

    /**
     * Issuer
     * @var Societe Object that emits
     */
    public $emetteur;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf, $langs, $mysoc;

        // Translations
        $langs->loadLangs(array("main", "companies"));

        $this->db = $db;
        $this->name = "audit_collectivite";
        $this->description = $langs->trans('PDFAuditCollectiviteDescription');
        $this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

        // Page size for A4 format
        $this->type = 'pdf';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
        $this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
        $this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
        $this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

        $this->option_logo = 1; // Display logo
        $this->option_tva = 1; // Manage the vat option FACTURE_TVAOPTION
        $this->option_modereg = 1; // Display payment mode
        $this->option_condreg = 1; // Display payment terms
        $this->option_multilang = 1; // Available in several languages
        $this->option_escompte = 0; // Displays if there has been a discount
        $this->option_credit_note = 0; // Support credit notes
        $this->option_freetext = 1; // Support add of a personalised text
        $this->option_draft_watermark = 1; // Support add of a watermark on drafts

        // Define position of columns
        $this->posxdesc = $this->marge_gauche + 1; // Position of description
        $this->posxtva = $this->page_largeur - $this->marge_droite - 13; // Position of VAT
        $this->posxup = $this->page_largeur - $this->marge_droite - 26; // Position of unit price
        $this->posxqty = $this->page_largeur - $this->marge_droite - 35; // Position of quantity
        $this->posxunit = $this->page_largeur - $this->marge_droite - 47; // Position of unit
        $this->posxdiscount = $this->page_largeur - $this->marge_droite - 59; // Position of discount
        $this->postotalht = $this->page_largeur - $this->marge_droite - 2; // Position of total HT

        $this->tva = array();
        $this->localtax1 = array();
        $this->localtax2 = array();
        $this->atleastoneratenotnull = 0;
        $this->atleastonediscount = 0;
        $this->atleastoneref = 0;
    }

    /**
     * Function to build pdf onto disk
     *
     * @param Audit $object Object to generate
     * @param Translate $outputlangs Lang output object
     * @param string $srctemplatepath Full path of source filename for generator using a template file
     * @param int $hidedetails Do not show line details
     * @param int $hidedesc Do not show desc
     * @param int $hideref Do not show ref
     * @return int 1=OK, 0=KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

        dol_syslog("pdf_audit_collectivite::write_file", LOG_DEBUG);

        if (!is_object($outputlangs)) $outputlangs = $langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

        // Load translation files required by the page
        $outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "orders", "deliveries"));

        // Show Draft Watermark
        if ($object->status == $object::STATUS_DRAFT && (!empty($conf->global->AUDIT_DRAFT_WATERMARK))) {
            $this->watermark = $conf->global->AUDIT_DRAFT_WATERMARK;
        }

        global $outputlangsbis;
        $outputlangsbis = null;
        if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
            $outputlangsbis = new Translate('', $conf);
            $outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
            $outputlangsbis->loadLangs(array("main", "dict", "companies", "bills", "products", "orders", "deliveries"));
        }

        $nblines = count($object->lines);

        $hidetop = 0;
        if (!empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
            $hidetop = $conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE;
        }

        // Create pdf instance
        $pdf = pdf_getInstance($this->format);
        $default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
        $pdf->SetAutoPageBreak(1, 0);

        $heightforinfotot = 40; // Height reserved to output the info and total part
        $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
        $heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));
        // Set path to the background PDF File
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
        $pdf->SetKeywords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Audit")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
        if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

        // Add page
        $pdf->AddPage();
        if (!empty($tplidx)) $pdf->useTemplate($tplidx);
        $pagenb++;
        $top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->MultiCell(0, 3, ''); // Set interline to 3
        $pdf->SetTextColor(0, 0, 0);

        $tab_top = 90;
        $tab_top_newpage = (!empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 80);
        $tab_height = $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfooter - $heightforfreetext;

        // Incoterm
        $height_incoterms = 0;
        if (isModEnabled('incoterm')) {
            $desc_incoterms = $object->getIncotermsForPDF();
            if ($desc_incoterms) {
                $tab_top -= 2;

                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
                $nexY = $pdf->GetY();
                $height_incoterms = $nexY - $tab_top;

                // Rect takes a length in 3rd parameter
                $pdf->SetDrawColor(192, 192, 192);
                $pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_incoterms + 1);

                $tab_top = $nexY + 6;
                $height_incoterms += 4;
            }
        }

        // Display notes
        $notetoshow = empty($object->note_public) ? '' : $object->note_public;
        if (!empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE)) {
            // Get first sale rep
            if (is_object($object->thirdparty)) {
                $salereparray = $object->thirdparty->getSalesRepresentatives($user);
                $salerepobj = new User($this->db);
                $salerepobj->fetch($salereparray[0]['id']);
                if (!empty($salerepobj->signature)) $notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
            }
        }

        // Extrafields in note
        $extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
        if (!empty($extranote)) {
            $notetoshow = dol_concatdesc($notetoshow, $extranote);
        }

        $pagenb = $pdf->getPage();
        if ($notetoshow) {
            $tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
            $pageposbeforenote = $pagenb;

            $substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
            complete_substitutions_array($substitutionarray, $outputlangs, $object);
            $notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
            $notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

            $tab_top -= 2;

            $pdf->startTransaction();
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
            // Description
            $pageposafternote = $pdf->getPage();
            $posyafter = $pdf->GetY();

            if ($pageposafternote > $pageposbeforenote) {
                $pdf->rollbackTransaction(true);

                // prepare pages to receive notes
                while ($pagenb < $pageposafternote) {
                    $pdf->AddPage();
                    $pagenb++;
                    if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                    // $this->_pagefoot($pdf,$object,$outputlangs,1);
                    $pdf->setTopMargin($tab_top_newpage);
                    // The only function to edit the bottom margin of current page to set it.
                    $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
                }

                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
                $pageposafternote = $pdf->getPage();

                $posyafter = $pdf->GetY();

                if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) { // There is no space left for total+free text
                    $pdf->AddPage('', '', true);
                    $pagenb++;
                    if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                    $pdf->setTopMargin($tab_top_newpage);
                    // The only function to edit the bottom margin of current page to set it.
                    $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
                    //$posyafter = $tab_top_newpage;
                }

                // apply note frame to previous pages
                $i = $pageposbeforenote;
                while ($i < $pageposafternote) {
                    $pdf->setPage($i);

                    $pdf->SetDrawColor(128, 128, 128);
                    // Draw note frame
                    if ($i > $pageposbeforenote) {
                        $height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
                        $pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
                    } else {
                        $height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
                        $pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);
                    }

                    // Add footer
                    $pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
                    $this->_pagefoot($pdf, $object, $outputlangs, 1);

                    $i++;
                }

                // apply note frame to last page
                $pdf->setPage($pageposafternote);
                if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

                $height_note = $posyafter - $tab_top_newpage;
                $pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
            } else { // No pagebreak
                $pdf->commitTransaction();
                $posyafter = $pdf->GetY();
                $height_note = $posyafter - $tab_top;
                $pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);

                if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
                    // not enough space, need to add page
                    $pdf->AddPage('', '', true);
                    $pagenb++;
                    if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                    $pdf->setTopMargin($tab_top_newpage);
                    // The only function to edit the bottom margin of current page to set it.
                    $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

                    $posyafter = $tab_top_newpage;
                }
            }

            $tab_height = $tab_height - $height_note;
            $tab_top = $posyafter + 6;
        } else {
            $height_note = 0;
        }

        // Use new auto column system
        $this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

        // Table simulation to know the height of the title line
        $pdf->startTransaction();
        $this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
        $pdf->rollbackTransaction(true);

        $nexY = $tab_top + $this->tabTitleHeight;

        // Loop on each lines
        $pageposbeforeprintlines = $pdf->getPage();
        $pagenb = $pageposbeforeprintlines;
        for ($i = 0; $i < $nblines; $i++) {
            $curY = $nexY;
            $pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
            $pdf->SetTextColor(0, 0, 0);

            // Define size of image if we need it
            $imglinesize = array();
            if (!empty($realpatharray[$i])) $imglinesize = pdf_getSizeForImage($realpatharray[$i]);

            $pdf->setTopMargin($tab_top_newpage);
            $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
            $pageposbefore = $pdf->getPage();

            $showpricebeforepagebreak = 1;
            $posYAfterImage = 0;
            $posYAfterDescription = 0;

            if ($this->getColumnStatus('photo')) {
                // We start with Photo of product line
                if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) { // If photo too high, we moved completely on new page
                    $pdf->AddPage('', '', true);
                    if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                    $pdf->setTopMargin($tab_top_newpage);
                    // The only function to edit the bottom margin of current page to set it.
                    $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot);
                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

                    $pagenb++;
                    $curY = $tab_top_newpage;
                }

                $oldY = $curY;

                if (isset($imglinesize['width']) && isset($imglinesize['height'])) {
                    $curY = $pdf->getImageScale($realpatharray[$i], $this->getColumnContentXStart('photo'), $curY, $this->getColumnContentWidth('photo'), $imglinesize['height']);
                    $posYAfterImage = $curY;
                } else {
                    $posYAfterImage = $curY;
                }
            }

            // Description of product line
            if ($this->getColumnStatus('desc')) {
                $pdf->startTransaction();

                $this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
                $pageposafter = $pdf->getPage();

                if ($pageposafter > $pageposbefore) { // There is a pagebreak
                    $pdf->rollbackTransaction(true);
                    $pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.

                    $this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);

                    $pageposafter = $pdf->getPage();
                    $posyafter = $pdf->GetY();
                    if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) { // There is no space left for total+free text
                        if ($i == ($nblines - 1)) { // No more lines, and no space left to show total, so we create a new page
                            $pdf->AddPage('', '', true);
                            if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                            $pdf->setTopMargin($tab_top_newpage);
                            // The only function to edit the bottom margin of current page to set it.
                            $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot);
                            if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

                            $pagenb++;
                        }
                    }
                    $showpricebeforepagebreak = 0;
                } else { // No pagebreak
                    $pdf->commitTransaction();
                }
                $posYAfterDescription = $pdf->GetY();
            }

            $nexY = max($pdf->GetY(), $posYAfterImage);
            $pageposafter = $pdf->getPage();

            $pdf->setPage($pageposbefore);
            $pdf->setTopMargin($this->marge_haute);
            $pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

            // We suppose that a too long description or photo were moved completely on next page
            if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
                $pdf->setPage($pageposafter);
                $curY = $tab_top_newpage;
            }

            $pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

            // VAT Rate
            if ($this->getColumnStatus('vat')) {
                $vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
                $this->printColDescContent($pdf, $curY, 'vat', $object, $i, $outputlangs, $hideref, $hidedesc, $vat_rate);
                $nexY = max($pdf->GetY(), $nexY);
            }

            // Unit price before discount
            if ($this->getColumnStatus('subprice')) {
                $up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
                $this->printColDescContent($pdf, $curY, 'subprice', $object, $i, $outputlangs, $hideref, $hidedesc, $up_excl_tax);
                $nexY = max($pdf->GetY(), $nexY);
            }

            // Quantity
            // Enough for 6 chars
            if ($this->getColumnStatus('qty')) {
                $qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
                $this->printColDescContent($pdf, $curY, 'qty', $object, $i, $outputlangs, $hideref, $hidedesc, $qty);
                $nexY = max($pdf->GetY(), $nexY);
            }

            // Unit
            if ($this->getColumnStatus('unit')) {
                $unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
                $this->printColDescContent($pdf, $curY, 'unit', $object, $i, $outputlangs, $hideref, $hidedesc, $unit);
                $nexY = max($pdf->GetY(), $nexY);
            }

            // Discount on line
            if ($this->getColumnStatus('discount') && $object->lines[$i]->remise_percent) {
                $remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
                $this->printColDescContent($pdf, $curY, 'discount', $object, $i, $outputlangs, $hideref, $hidedesc, $remise_percent);
                $nexY = max($pdf->GetY(), $nexY);
            }

            // Total excl tax line (HT)
            if ($this->getColumnStatus('totalexcltax')) {
                $total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
                $this->printColDescContent($pdf, $curY, 'totalexcltax', $object, $i, $outputlangs, $hideref, $hidedesc, $total_excl_tax);
                $nexY = max($pdf->GetY(), $nexY);
            }

            // Progress
            if ($this->getColumnStatus('progress')) {
                $progress = pdf_getlineprogress($object, $i, $outputlangs, $hidedetails);
                $this->printColDescContent($pdf, $curY, 'progress', $object, $i, $outputlangs, $hideref, $hidedesc, $progress);
                $nexY = max($pdf->GetY(), $nexY);
            }

            // Extrafields
            if (!empty($object->lines[$i]->array_options)) {
                foreach ($object->lines[$i]->array_options as $extrafieldColKey => $extrafieldValue) {
                    if ($this->getColumnStatus($extrafieldColKey)) {
                        $extrafieldValue = $this->getExtrafieldContent($object->lines[$i], $extrafieldColKey, $outputlangs);
                        $this->printColDescContent($pdf, $curY, $extrafieldColKey, $object, $i, $outputlangs, $hideref, $hidedesc, $extrafieldValue);

                        $nexY = max($pdf->GetY(), $nexY);
                    }
                }
            }

            $parameters = array(
                'object' => $object,
                'i' => $i,
                'pdf' =>& $pdf,
                'curY' =>& $curY,
                'nexY' =>& $nexY,
                'outputlangs' => $outputlangs,
                'hidedetails' => $hidedetails
            );
            $reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this); // Note that $object may have been modified by hook

            // Collection of totals by value of VAT in $this->tva["rate"] = total_tva
            if (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) $tvaligne = $object->lines[$i]->multicurrency_total_tva;
            else $tvaligne = $object->lines[$i]->total_tva;

            $localtax1ligne = $object->lines[$i]->total_localtax1;
            $localtax2ligne = $object->lines[$i]->total_localtax2;
            $localtax1_rate = $object->lines[$i]->localtax1_tx;
            $localtax2_rate = $object->lines[$i]->localtax2_tx;
            $localtax1_type = $object->lines[$i]->localtax1_type;
            $localtax2_type = $object->lines[$i]->localtax2_type;

            // TODO remise_percent is an obsolete field for object parent
            if ($object->remise_percent) $tvaligne -= ($tvaligne * $object->remise_percent) / 100;
            if ($object->remise_percent) $localtax1ligne -= ($localtax1ligne * $object->remise_percent) / 100;
            if ($object->remise_percent) $localtax2ligne -= ($localtax2ligne * $object->remise_percent) / 100;

            $vatrate = (string) $object->lines[$i]->tva_tx;

            // Retrieve type from database for backward compatibility with old records
            if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '') // if tax type not defined
            && (!empty($localtax1_rate) || !empty($localtax2_rate))) { // and there is local tax
                $localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $mysoc);
                $localtax1_type = isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
                $localtax2_type = isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
            }

            // retrieve global local tax
            if ($localtax1_type && $localtax1ligne != 0) $this->localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;
            if ($localtax2_type && $localtax2ligne != 0) $this->localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;

            if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate .= '*';

            // Fill $this->tva["rate"] = total_tva
            if (!isset($this->tva[$vatrate])) $this->tva[$vatrate] = 0;
            $this->tva[$vatrate] += $tvaligne;

            $nexY += 2; // Add space between lines

            // Looking for the next line
            if ($i < ($nblines - 1)) { // If it's not last line
                // Allows data in the first page if description is long enough to break in multiples pages
                if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
                    $showpricebeforepagebreak = 1;
                } else {
                    $showpricebeforepagebreak = 0;
                }
            }

            // Detect if some page were added automatically and output _tableau for past pages
            while ($pagenb < $pageposafter) {
                $pdf->setPage($pagenb);
                if ($pagenb == $pageposbeforeprintlines) {
                    $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangsbis);
                } else {
                    $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code, $outputlangsbis);
                }
                $this->_pagefoot($pdf, $object, $outputlangs, 1);
                $pagenb++;
                $pdf->setPage($pagenb);
                $pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
                if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
            }

            if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
                if ($pagenb == $pageposafter) {
                    $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangsbis);
                } else {
                    $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code, $outputlangsbis);
                }
                $this->_pagefoot($pdf, $object, $outputlangs, 1);
                // New page
                $pdf->AddPage();
                if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                $pagenb++;
                if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
            }
        }

        // Show square
        if ($pagenb == $pageposbeforeprintlines) {
            $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, $hidetop, 0, $object->multicurrency_code, $outputlangsbis);
            $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
        } else {
            $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code, $outputlangsbis);
            $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
        }

        // Display infos area
        //$posy = $this->drawInfoTable($pdf, $object, $bottomlasttab, $outputlangs);

        // Display total zone
        //$posy = $this->drawTotalTable($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

        // Display payment area
        /*
        if (($deja_regle || $amount_credit_notes_included || $amount_deposits_included) && empty($conf->global->INVOICE_NO_PAYMENT_DETAILS)) {
            $posy = $this->drawPaymentsTable($pdf, $object, $posy, $outputlangs);
        }
        */

        // Pagefoot
        $this->_pagefoot($pdf, $object, $outputlangs);
        if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

        $pdf->Close();

        $pdf->Output($file, 'F');

        // Add pdfgeneration hook
        $hookmanager->initHooks(array('pdfgeneration'));
        $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
        global $action;
        $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $object may have been modified by hook

        dolChmod($file);

        $this->result = array('fullpath'=>$file);

        return 1; // No error
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     *  Show table for lines
     *
     *  @param	TCPDF		$pdf     		Object PDF
     *  @param	string		$tab_top		Top position of table
     *  @param	string		$tab_height		Height of table (rectangle)
     *  @param	int			$nexY			Y (not used)
     *  @param	Translate	$outputlangs	Langs object
     *  @param	int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
     *  @param	int			$hidebottom		Hide bottom bar of array
     *  @param	string		$currency		Currency code
     *  @param	Translate	$outputlangsbis	Langs object bis
     *  @return	void
     */
    protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null)
    {
        global $conf;

        // Force to disable hidetop and hidebottom
        $hidebottom = 0;
        if ($hidetop) $hidetop = -1;

        $currency = !empty($currency) ? $currency : $conf->currency;
        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Amount in (at tab_top - 1)
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', $default_font_size - 2);

        if (empty($hidetop)) {
            $titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
            $pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
            $pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

            //$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
            if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 5, 'F', null, explode(',', $conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
        }

        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetFont('', '', $default_font_size - 1);

        // Output Rect
        $this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter

        $this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);

        if (empty($hidetop)) {
            $pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5); // line takes a position y in 2nd parameter and 4th parameter
        }
    }

    /**
     *  Show top header of page.
     *
     *  @param	TCPDF		$pdf     		Object PDF
     *  @param  Audit	$object     	Object to show
     *  @param  int	    $showaddress    0=hide address, 1=show address
     *  @param  Translate	$outputlangs	Object lang for output
     *  @param	Translate	$outputlangsbis	Object lang for output bis
     *  @return	int							Return topshift value
     */
    protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
    {
        global $conf, $langs, $hookmanager;

        // Load translation files required by the page
        $outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        // Show Draft Watermark
        if ($object->status == $object::STATUS_DRAFT && (!empty($conf->global->AUDIT_DRAFT_WATERMARK))) {
            pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->AUDIT_DRAFT_WATERMARK);
        }

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $w = 110;

        $posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - $w;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        if (!empty($conf->global->MAIN_INFO_SOCIETE_LOGO)) {
            $logodir = $conf->mycompany->dir_output;
            if (!empty($conf->mycompany->multidir_output[$object->entity])) $logodir = $conf->mycompany->multidir_output[$object->entity];
            if (empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL)) $logo = $logodir.'/logos/'.$conf->global->MAIN_INFO_SOCIETE_LOGO;
            else $logo = $logodir.'/logos/thumbs/'.$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;
            if (is_readable($logo)) {
                $height = pdf_getHeightForLogo($logo);
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else {
            $text = $this->emetteur->name;
            $pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
        }

        $pdf->SetFont('', 'B', $default_font_size + 3);
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $title = $outputlangs->transnoentities("PdfTitle");
        if (empty($title)) $title = $outputlangs->transnoentities("Audit");
        $pdf->MultiCell($w, 3, $title, '', 'R');

        $pdf->SetFont('', 'B', $default_font_size);

        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
        if ($object->status == $object::STATUS_DRAFT) {
            $pdf->SetTextColor(128, 0, 0);
            $textref .= ' - '.$outputlangs->transnoentities("NotValidated");
        }
        $pdf->MultiCell($w, 4, $textref, '', 'R');

        $posy += 1;
        $pdf->SetFont('', '', $default_font_size - 2);

        if ($object->ref_client) {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefCustomer")." : ".dol_trunc($outputlangs->convToOutputCharset($object->ref_client), 65), '', 'R');
        }

        if (!empty($object->project->ref)) {
            $posy += 3;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("Project")." : ".(empty($object->project->title) ? '' : $object->project->title), '', 'R');
        }

        if (!empty($object->project->ref)) {
            $outputlangs->load("projects");
            $posy += 3;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefProject")." : ".(empty($object->project->ref) ? '' : $object->project->ref), '', 'R');
        }

        $posy += 4;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);

        $title = $outputlangs->transnoentities("DateAudit");
        if (!empty($object->date_audit)) $pdf->MultiCell($w, 3, $title." : ".dol_print_date($object->date_audit, "day", false, $outputlangs, true), '', 'R');
        else {
            $pdf->SetTextColor(255, 0, 0);
            $pdf->MultiCell($w, 3, strtolower($title), '', 'R');
        }

        if (!empty($object->thirdparty->code_client)) {
            $posy += 3;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
        }

        // Get contact
        if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP)) {
            $arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
            if (count($arrayidcontact) > 0) {
                $usertmp = new User($this->db);
                $usertmp->fetch($arrayidcontact[0]);
                $posy += 4;
                $pdf->SetXY($posx, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($w, 3, $langs->transnoentities("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
            }
        }

        $posy += 2;

        $top_shift = 0;
        // Show list of linked objects
        $current_y = $pdf->getY();
        $posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
        if ($current_y < $pdf->getY()) {
            $top_shift = $pdf->getY() - $current_y;
        }

        if ($showaddress) {
            // Sender properties
            $carac_emetteur = '';
            // Add internal contact of proposal if defined
            $arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
            if (count($arrayidcontact) > 0) {
                $object->fetch_user($arrayidcontact[0]);
                $labelbeforecontactname = ($outputlangs->transnoentities("FromContactName") != 'FromContactName' ? $outputlangs->transnoentities("FromContactName") : $outputlangs->transnoentities("Name"));
                $carac_emetteur .= ($carac_emetteur ? "\n" : '').$labelbeforecontactname." ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
                $carac_emetteur .= (empty($object->user->email) ? '' : "\n".$outputlangs->transnoentities("Email").": ".$object->user->email);
                $carac_emetteur .= (empty($object->user->office_phone) ? '' : "\n".$outputlangs->transnoentities("Phone").": ".$object->user->office_phone);
            }

            $carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

            // Show sender
            $posy = (!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42) + $top_shift;
            $posx = $this->marge_gauche;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->page_largeur - $this->marge_droite - 80;

            $hautcadre = (!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 38 : 40);
            $widthrecbox = (!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 82);

            // Show sender frame
            if (empty($conf->global->MAIN_PDF_NO_SENDER_FRAME)) $pdf->Rect($posx, $posy - 5, $widthrecbox, $hautcadre, 'D');

            // Show sender name
            if (empty($conf->global->MAIN_PDF_HIDE_SENDER_NAME)) {
                $pdf->SetTextColor(0, 0, 60);
                $pdf->SetFont('', 'B', $default_font_size);
                $pdf->SetXY($posx + 2, $posy + 3);
                $pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
                $posy = $pdf->getY();
            }

            // Show sender information
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->SetXY($posx + 2, $posy);
            $pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');

            // If CUSTOMER contact defined, we use it
            $usecontact = false;
            $arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
            if (count($arrayidcontact) > 0) {
                $usecontact = true;
                $result = $object->fetch_contact($arrayidcontact[0]);
            }

            // Recipient name
            if ($usecontact && ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
                $thirdparty = $object->contact;
            } else {
                $thirdparty = $object->thirdparty;
            }

            $carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

            $mode = 'target';
            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object);

            // Show recipient
            $widthrecbox = (!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 100);
            if ($this->page_largeur < 210) $widthrecbox = 84; // To work with US executive format
            $posy = (!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42) + $top_shift;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->marge_gauche;

            // Show recipient frame
            if (empty($conf->global->MAIN_PDF_NO_RECIPENT_FRAME)) $pdf->Rect($posx, $posy - 5, $widthrecbox, $hautcadre, 'D');

            // Show recipient name
            $pdf->SetTextColor(0, 0, 60);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

            $posy = $pdf->getY();

            // Show recipient information
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->SetXY($posx + 2, $posy);
            $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
        }

        $pdf->SetTextColor(0, 0, 0);

        return $top_shift;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     *   	Show footer of page. Need this->emetteur object
     *
     *   	@param	TCPDF		$pdf     			PDF
     * 		@param	Audit		$object				Object to show
     *      @param	Translate	$outputlangs		Object lang for output
     *      @param	int			$hidefreetext		1=Hide free text
     *      @return	int								Return height of bottom margin including footer text
     */
    protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
    {
        $showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
        return pdf_pagefoot($pdf, $outputlangs, 'AUDIT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
    }
}
?>