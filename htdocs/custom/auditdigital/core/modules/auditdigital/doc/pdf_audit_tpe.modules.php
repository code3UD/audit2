<?php
/* Copyright (C) 2024 Up Digit Agency
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       core/modules/auditdigital/doc/pdf_audit_tpe.modules.php
 * \ingroup    auditdigital
 * \brief      File of class to generate PDF for TPE/PME audits
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/pdf/modules_pdf.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/lib/auditdigital.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

/**
 * Class to generate PDF for TPE/PME audits
 */
class pdf_audit_tpe extends ModelePDFAudit
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
     * @var string model description (short)
     */
    public $description;

    /**
     * @var int     Save the name of generated file as the main doc when generating a doc with this template
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
     * @var Societe
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
        $langs->loadLangs(array("main", "bills", "auditdigital@auditdigital"));

        $this->db = $db;
        $this->name = "audit_tpe";
        $this->description = $langs->trans('PDFAuditTPEDescription');
        $this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

        // Dimension page
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
        $this->option_draft_watermark = 0; // Support add of a watermark on drafts

        // Get source company
        $this->emetteur = $mysoc;
        if (empty($this->emetteur->country_code)) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
        }

        // Define position of columns
        $this->posxdesc = $this->marge_gauche + 1; // Position of description
        $this->posxtva = 111;
        $this->posxup = 126;
        $this->posxqty = 145;
        $this->posxunit = 161;
        $this->posxdiscount = 171;
        $this->postotalht = 174;

        $this->tva = array();
        $this->localtax1 = array();
        $this->localtax2 = array();
        $this->atleastoneratenotnull = 0;
        $this->atleastonediscount = 0;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Function to build pdf onto disk
     *
     * @param Audit $object Object to generate
     * @param Translate $outputlangs Lang output object
     * @param string $srctemplatepath Full path of source filename for generator using a template file
     * @param int $hidedetails Do not show line details
     * @param int $hidedesc Do not show desc
     * @param int $hideref Do not show ref
     * @param object $hookmanager Hook manager instance
     * @return int 1=OK, 0=KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $hookmanager = null)
    {
        // phpcs:enable
        global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

        dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF)) {
            $outputlangs->charset_output = 'ISO-8859-1';
        }

        // Load translation files required by the page
        $outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "auditdigital@auditdigital"));

        $nblines = count($object->lines);

        if ($conf->auditdigital->dir_output) {
            $object->fetch_thirdparty();

            $deja_regle = 0;

            // Definition of $dir and $file
            if ($object->specimen) {
                $dir = $conf->auditdigital->dir_output;
                $file = $dir."/SPECIMEN.pdf";
            } else {
                $objectref = dol_sanitizeFileName($object->ref);
                $dir = $conf->auditdigital->dir_output."/".$objectref;
                $file = $dir."/".$objectref.".pdf";
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                // Add pdfgeneration hook
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

                // Set nblines with the new audit lines content after hook
                $nblines = count($object->lines);
                $nbpayments = count($object->getListOfPayments());

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
                if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
                    $pdf->SetCompression(false);
                }

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

                // New page
                $pdf->AddPage();
                if (!empty($tplidx)) {
                    $pdf->useTemplate($tplidx);
                }
                $pagenb++;
                $top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, ''); // Set interline to 3
                $pdf->SetTextColor(0, 0, 0);

                $tab_top = 90;
                $tab_top_newpage = (!empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 80);
                $tab_height = $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfooter - $heightforfreetext;

                // Display audit content
                $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfooter - $heightforfreetext, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangs);

                // Display scores and recommendations
                $this->_displayScores($pdf, $object, $outputlangs);
                $this->_displayRecommendations($pdf, $object, $outputlangs);

                // Pagefoot
                $this->_pagefoot($pdf, $object, $outputlangs, 1);
                if (method_exists($pdf, 'AliasNbPages')) {
                    $pdf->AliasNbPages();
                }

                $pdf->Close();

                $pdf->Output($file, 'F');

                // Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

                dolChmod($file);

                $this->result = array('fullpath'=>$file);

                return 1; // No error
            } else {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $langs->transnoentities("ErrorConstantNotDefined", "AUDIT_OUTPUTDIR");
            return 0;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Show table for lines
     *
     * @param TCPDF $pdf Object PDF
     * @param string $tab_top Top position of table
     * @param string $tab_height Height of table (rectangle)
     * @param int $nexY Y (not used)
     * @param Translate $outputlangs Langs object
     * @param int $hidetop 1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
     * @param int $hidebottom Hide bottom bar of array
     * @param string $currency Currency code
     * @param Translate $outputlangsbis Langs object bis
     * @return void
     */
    protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null)
    {
        // phpcs:enable
        global $conf;

        // Force to disable hidetop and hidebottom
        $hidebottom = 0;
        if ($hidetop) {
            $hidetop = -1;
        }

        $currency = !empty($currency) ? $currency : $conf->currency;
        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Amount in (at tab_top - 1)
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', $default_font_size - 2);

        if (empty($hidetop)) {
            $titre = $outputlangs->transnoentities("AuditDigitalResults");
            $pdf->SetXY($this->marge_gauche, $tab_top - 4);
            $pdf->MultiCell(180, 3, $titre, 0, 'C', 1);
        }

        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetFont('', '', $default_font_size - 1);

        // Output Rect
        $this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter

        $pdf->SetY($tab_top + 5);
    }

    /**
     * Display audit scores
     *
     * @param TCPDF $pdf Object PDF
     * @param Audit $object Audit object
     * @param Translate $outputlangs Langs object
     * @return void
     */
    protected function _displayScores(&$pdf, $object, $outputlangs)
    {
        $pdf->SetFont('', 'B', 12);
        $pdf->SetXY($this->marge_gauche, $pdf->GetY() + 10);
        $pdf->Cell(0, 8, $outputlangs->transnoentities("AuditScores"), 0, 1, 'L');

        $pdf->SetFont('', '', 10);
        $y = $pdf->GetY() + 5;

        // Global Score
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell(60, 6, $outputlangs->transnoentities("GlobalScore").':', 0, 0, 'L');
        $pdf->Cell(30, 6, ($object->score_global ?: 0).'%', 0, 1, 'L');

        // Maturity Score
        $pdf->SetXY($this->marge_gauche, $pdf->GetY());
        $pdf->Cell(60, 6, $outputlangs->transnoentities("MaturityScore").':', 0, 0, 'L');
        $pdf->Cell(30, 6, ($object->score_maturite ?: 0).'%', 0, 1, 'L');

        // Cybersecurity Score
        $pdf->SetXY($this->marge_gauche, $pdf->GetY());
        $pdf->Cell(60, 6, $outputlangs->transnoentities("CybersecurityScore").':', 0, 0, 'L');
        $pdf->Cell(30, 6, ($object->score_cybersecurite ?: 0).'%', 0, 1, 'L');

        // Cloud Score
        $pdf->SetXY($this->marge_gauche, $pdf->GetY());
        $pdf->Cell(60, 6, $outputlangs->transnoentities("CloudScore").':', 0, 0, 'L');
        $pdf->Cell(30, 6, ($object->score_cloud ?: 0).'%', 0, 1, 'L');

        // Automation Score
        $pdf->SetXY($this->marge_gauche, $pdf->GetY());
        $pdf->Cell(60, 6, $outputlangs->transnoentities("AutomationScore").':', 0, 0, 'L');
        $pdf->Cell(30, 6, ($object->score_automatisation ?: 0).'%', 0, 1, 'L');
    }

    /**
     * Display recommendations
     *
     * @param TCPDF $pdf Object PDF
     * @param Audit $object Audit object
     * @param Translate $outputlangs Langs object
     * @return void
     */
    protected function _displayRecommendations(&$pdf, $object, $outputlangs)
    {
        $pdf->SetFont('', 'B', 12);
        $pdf->SetXY($this->marge_gauche, $pdf->GetY() + 10);
        $pdf->Cell(0, 8, $outputlangs->transnoentities("RecommendedSolutions"), 0, 1, 'L');

        $pdf->SetFont('', '', 10);

        if (!empty($object->json_recommendations)) {
            $recommendations = json_decode($object->json_recommendations, true);
            
            if (is_array($recommendations)) {
                foreach ($recommendations as $category => $categoryRecs) {
                    if (is_array($categoryRecs) && !empty($categoryRecs)) {
                        $pdf->SetFont('', 'B', 10);
                        $pdf->SetXY($this->marge_gauche, $pdf->GetY() + 5);
                        $pdf->Cell(0, 6, ucfirst($category), 0, 1, 'L');
                        
                        $pdf->SetFont('', '', 9);
                        foreach ($categoryRecs as $rec) {
                            if (isset($rec->label)) {
                                $pdf->SetXY($this->marge_gauche + 5, $pdf->GetY());
                                $pdf->Cell(0, 5, '• '.$rec->label, 0, 1, 'L');
                            }
                        }
                    }
                }
            }
        } else {
            $pdf->SetXY($this->marge_gauche, $pdf->GetY() + 5);
            $pdf->Cell(0, 6, $outputlangs->transnoentities("NoRecommendationsAvailable"), 0, 1, 'L');
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Show header of page. Return height of header.
     *
     * @param TCPDF $pdf Object PDF
     * @param Audit $object Object to show
     * @param int $showaddress 0=no, 1=yes
     * @param Translate $outputlangs Object lang for output
     * @param Translate $outputlangsbis Object lang for output bis
     * @return int Return height of header
     */
    protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
    {
        // phpcs:enable
        global $conf, $langs;

        // Load translation files required by the page
        $outputlangs->loadLangs(array("main", "bills", "propal", "companies", "auditdigital@auditdigital"));

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
        if (!empty($this->emetteur->logo)) {
            $logodir = $conf->mycompany->dir_output;
            if (!empty($conf->mycompany->multidir_output[$object->entity])) {
                $logodir = $conf->mycompany->multidir_output[$object->entity];
            }
            if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
                $logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
            } else {
                $logo = $logodir.'/logos/'.$this->emetteur->logo;
            }
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

        // Title
        $pdf->SetFont('', 'B', $default_font_size + 3);
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $title = $outputlangs->transnoentities("AuditDigital");
        $pdf->MultiCell($w, 3, $title, '', 'R');

        $pdf->SetFont('', 'B', $default_font_size);

        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell($w, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref), '', 'R');

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

        if (!empty($object->date_audit)) {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("DateAudit")." : ".dol_print_date($object->date_audit, "day", false, $outputlangs, true), '', 'R');
        }

        if ($object->thirdparty->code_client) {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
        }

        // Show list of linked objects
        $posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);

        if ($showaddress) {
            // Sender properties
            $carac_emetteur = '';
            // Add internal contact of proposal if defined
            $arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
            if (count($arrayidcontact) > 0) {
                $object->fetch_user(reset($arrayidcontact));
                $carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
            }

            $carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

            // Show sender
            $posy = 42;
            $posx = $this->marge_gauche;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
                $posx = $this->page_largeur - $this->marge_droite - 80;
            }

            $hautcadre = 40;

            // Show sender frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx, $posy - 5);
            $pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom"), 0, 'L');
            $pdf->SetXY($posx, $posy);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
            $pdf->SetTextColor(0, 0, 60);

            // Show sender name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
            $posy = $pdf->getY();

            // Show sender information
            $pdf->SetXY($posx + 2, $posy);
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');

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
            $widthrecbox = 100;
            if ($this->page_largeur < 210) {
                $widthrecbox = 84; // To work with US executive format
            }
            $posy = 42;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
                $posx = $this->marge_gauche;
            }

            // Show recipient frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + 2, $posy - 5);
            $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo"), 0, 'L');
            $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

            // Show recipient name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

            $posy = $pdf->getY();

            // Show recipient information
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->SetXY($posx + 2, $posy);
            $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
        }

        $pdf->SetTextColor(0, 0, 0);

        return $posy;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Show footer of page. Need this->emetteur object
     *
     * @param TCPDF $pdf PDF
     * @param Audit $object Object to show
     * @param Translate $outputlangs Object lang for output
     * @param int $hidefreetext 1=Hide free text
     * @return int Return height of bottom margin including footer text
     */
    protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
    {
        // phpcs:enable
        $showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
        return pdf_pagefoot($pdf, $outputlangs, 'AUDIT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
    }

    /**
     * Define Array Column Field
     *
     * @param array $object object
     * @param Translate $outputlangs langs
     * @param int $hidedetails Do not show line details
     * @param int $hidedesc Do not show desc
     * @param int $hideref Do not show ref
     * @return null
     */
    public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $conf, $hookmanager;

        // Default field style for content
        $this->defaultContentsFieldsStyle = array(
            'align' => 'R', // R,C,L
            'padding' => array(1, 0.5, 1, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
        );

        // Default field style for content
        $this->defaultTitlesFieldsStyle = array(
            'align' => 'C', // R,C,L
            'padding' => array(0.5, 0, 0.5, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
        );

        /*
         * For exemple
         $this->cols['theColKey'] = array(
             'rank' => $rank, // int : use for ordering columns
             'width' => 20, // the column width in mm
             'title' => array(
                 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
                 'label' => ' ', // the final label : used fore final generated text
                 'align' => 'L', // text alignement :  R,C,L
                 'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
             ),
             'content' => array(
                 'align' => 'L', // text alignement :  R,C,L
                 'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
             ),
         );
         */

        $rank = 0; // do not use negative rank
        $this->cols['desc'] = array(
            'rank' => $rank,
            'width' => false, // only for desc
            'status' => true,
            'title' => array(
                'textkey' => 'Designation', // use lang key is usefull in somme case with module
                'align' => 'L',
                // 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
                // 'label' => ' ', // the final label
                'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
            ),
            'content' => array(
                'align' => 'L',
                'padding' => array(1, 0.5, 1, 1.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
            ),
        );

        $rank = $rank + 10;
        $this->cols['vat'] = array(
            'rank' => $rank,
            'status' => false,
            'width' => 16, // in mm
            'title' => array(
                'textkey' => 'VAT'
            ),
            'border-left' => true, // add left line separator
        );

        $rank = $rank + 10;
        $this->cols['subprice'] = array(
            'rank' => $rank,
            'width' => 19, // in mm
            'status' => false,
            'title' => array(
                'textkey' => 'PriceUHT'
            ),
            'border-left' => true, // add left line separator
        );

        $rank = $rank + 10;
        $this->cols['qty'] = array(
            'rank' => $rank,
            'width' => 16, // in mm
            'status' => false,
            'title' => array(
                'textkey' => 'Qty'
            ),
            'border-left' => true, // add left line separator
        );

        $rank = $rank + 10;
        $this->cols['unit'] = array(
            'rank' => $rank,
            'width' => 11, // in mm
            'status' => false,
            'title' => array(
                'textkey' => 'Unit'
            ),
            'border-left' => true, // add left line separator
        );

        $rank = $rank + 10;
        $this->cols['discount'] = array(
            'rank' => $rank,
            'width' => 13, // in mm
            'status' => false,
            'title' => array(
                'textkey' => 'ReductionShort'
            ),
            'border-left' => true, // add left line separator
        );

        $rank = $rank + 1000; // add a big offset to be sure is the last col because default extrafield rank is 100
        $this->cols['totalexcltax'] = array(
            'rank' => $rank,
            'width' => 26, // in mm
            'status' => false,
            'title' => array(
                'textkey' => 'TotalHT'
            ),
            'border-left' => true, // add left line separator
        );

        // Add extrafields cols
        if (!empty($object->lines)) {
            $line = reset($object->lines);
            $this->defineColumnExtrafield($line, $outputlangs, $hidedetails);
        }

        $parameters = array(
            'object' => $object,
            'outputlangs' => $outputlangs,
            'hidedetails' => $hidedetails,
            'hidedesc' => $hidedesc,
            'hideref' => $hideref
        );

        $reshook = $hookmanager->executeHooks('defineColumnField', $parameters, $this); // Note that $object and $action may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        } elseif (empty($reshook)) {
            $this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
        } else {
            $this->cols = $hookmanager->resArray;
        }
    }
}