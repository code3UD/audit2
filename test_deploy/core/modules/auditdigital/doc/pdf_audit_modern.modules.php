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
 * \file       core/modules/auditdigital/doc/pdf_audit_modern.modules.php
 * \ingroup    auditdigital
 * \brief      Modern PDF template for audit reports with enhanced design and charts
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/auditdigital/modules_audit.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

/**
 * Class to generate modern PDF audit reports
 */
class pdf_audit_modern extends ModeleNumRefAudit
{
    /**
     * @var DoliDB Database handler
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
     * @var int Save the name of generated file as the main doc when generating a doc with this template
     */
    public $update_main_doc_field;

    /**
     * @var string Document type
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
        $langs->loadLangs(array("main", "bills"));

        $this->db = $db;
        $this->name = "modern";
        $this->description = $langs->trans('AuditModernPDFDescription');
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

        $this->emetteur = $mysoc;
        if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined

        // Define position of columns
        $this->posxdesc = $this->marge_gauche + 1;
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
     * @param object $moreparams Object to enable any extra information
     * @return int 1=OK, 0=KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

        dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

        if (!is_object($outputlangs)) $outputlangs = $langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

        // Load translation files required by the page
        $outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "auditdigital@auditdigital"));

        // Show Draft Watermark
        if ($object->status == $object::STATUS_DRAFT && (!empty($conf->global->AUDIT_DRAFT_WATERMARK))) {
            $this->watermark = $conf->global->AUDIT_DRAFT_WATERMARK;
        }

        global $outputlangsbis;
        $outputlangsbis = null;

        $nblines = count($object->lines);

        // Create doc folder if it doesn't exist
        if (!empty($conf->auditdigital->multidir_output[$conf->entity])) {
            $dir = $conf->auditdigital->multidir_output[$conf->entity];
            $file = $dir . "/" . $object->ref . ".pdf";
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

            // Complete object by loading several other informations
            $sql = "SELECT rowid, ref, label, description, note_public, note_private, date_creation, tms as date_modification, fk_user_author, fk_user_modif, fk_statut as status";
            $sql .= " FROM ".MAIN_DB_PREFIX."auditdigital_audit";
            $sql .= " WHERE rowid = ".((int) $object->id);

            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                $object->ref = $obj->ref;
                $object->label = $obj->label;
                $object->description = $obj->description;
                $object->note_public = $obj->note_public;
                $object->note_private = $obj->note_private;
                $object->date_creation = $obj->date_creation;
                $object->date_modification = $obj->date_modification;
                $object->fk_user_author = $obj->fk_user_author;
                $object->fk_user_modif = $obj->fk_user_modif;
                $object->status = $obj->status;
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

            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

            // Page 1 - Cover page
            $this->_pagehead_cover($pdf, $object, 1, $outputlangs);
            
            // Page 2 - Executive Summary
            $this->_pagehead_summary($pdf, $object, 1, $outputlangs);
            
            // Page 3 - Detailed Scores
            $this->_pagehead_scores($pdf, $object, 1, $outputlangs);
            
            // Page 4 - Recommendations
            $this->_pagehead_recommendations($pdf, $object, 1, $outputlangs);
            
            // Page 5 - ROI Analysis
            $this->_pagehead_roi($pdf, $object, 1, $outputlangs);
            
            // Page 6 - Implementation Roadmap
            $this->_pagehead_roadmap($pdf, $object, 1, $outputlangs);

            // Add PDF generation hook
            $hookmanager->initHooks(array('pdfgeneration'));
            $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
            global $action;
            $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
            if ($reshook < 0) {
                $this->error = $hookmanager->error;
                $this->errors = $hookmanager->errors;
            }

            // We save the generated file
            if (!empty($conf->global->MAIN_UMASK))
                @chmod($file, octdec($conf->global->MAIN_UMASK));

            $pdf->Output($file, 'F');

            // Add pdfgeneration hook
            $hookmanager->initHooks(array('pdfgeneration'));
            $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
            global $action;
            $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
            if ($reshook < 0) {
                $this->error = $hookmanager->error;
                $this->errors = $hookmanager->errors;
            }

            dolChmod($file);

            $this->result = array('fullpath'=>$file);

            return 1; // No error
        } else {
            $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
            return 0;
        }
    }

    /**
     * Show header of page for cover page
     *
     * @param TCPDF $pdf Object PDF
     * @param Audit $object Object to show
     * @param int $showaddress 0=no, 1=yes
     * @param Translate $outputlangs Object lang for output
     * @return int top shift of linked object lines
     */
    protected function _pagehead_cover(&$pdf, $object, $showaddress, $outputlangs)
    {
        global $conf, $langs, $mysoc;

        $pdf->AddPage();
        
        // Modern cover page with gradient background
        $pdf->SetFillColor(0, 102, 204); // Primary blue
        $pdf->Rect(0, 0, 210, 80, 'F');
        
        // Add subtle pattern overlay
        $pdf->SetFillColor(255, 255, 255, 10); // Semi-transparent white
        for ($i = 0; $i < 210; $i += 20) {
            $pdf->Rect($i, 0, 10, 80, 'F');
        }
        
        // Title section
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('', 'B', 28);
        $pdf->SetY(25);
        $pdf->Cell(0, 15, $outputlangs->transnoentities('AuditDigitalReport'), 0, 1, 'C');
        
        $pdf->SetFont('', '', 16);
        $pdf->Cell(0, 10, $object->ref . ' - ' . dol_print_date($object->date_creation, 'day'), 0, 1, 'C');
        
        // Company information section
        $pdf->SetY(90);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', 'B', 18);
        $pdf->Cell(0, 10, $outputlangs->transnoentities('CompanyInformation'), 0, 1, 'L');
        
        $pdf->SetFont('', '', 12);
        $y = 105;
        
        if ($object->thirdparty) {
            $pdf->SetY($y);
            $pdf->Cell(50, 8, $outputlangs->transnoentities('Company') . ':', 0, 0, 'L');
            $pdf->SetFont('', 'B', 12);
            $pdf->Cell(0, 8, $object->thirdparty->name, 0, 1, 'L');
            $y += 8;
            
            $pdf->SetFont('', '', 12);
            if ($object->thirdparty->address) {
                $pdf->SetY($y);
                $pdf->Cell(50, 6, $outputlangs->transnoentities('Address') . ':', 0, 0, 'L');
                $pdf->Cell(0, 6, $object->thirdparty->address, 0, 1, 'L');
                $y += 6;
            }
            
            if ($object->thirdparty->zip || $object->thirdparty->town) {
                $pdf->SetY($y);
                $pdf->Cell(50, 6, '', 0, 0, 'L');
                $pdf->Cell(0, 6, $object->thirdparty->zip . ' ' . $object->thirdparty->town, 0, 1, 'L');
                $y += 6;
            }
        }
        
        // Audit information
        $pdf->SetY($y + 10);
        $pdf->SetFont('', 'B', 18);
        $pdf->Cell(0, 10, $outputlangs->transnoentities('AuditInformation'), 0, 1, 'L');
        
        $y += 25;
        $pdf->SetFont('', '', 12);
        
        $pdf->SetY($y);
        $pdf->Cell(50, 6, $outputlangs->transnoentities('AuditDate') . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, dol_print_date($object->date_creation, 'day'), 0, 1, 'L');
        $y += 8;
        
        $pdf->SetY($y);
        $pdf->Cell(50, 6, $outputlangs->transnoentities('AuditType') . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, ucfirst($object->structure_type), 0, 1, 'L');
        $y += 8;
        
        $pdf->SetY($y);
        $pdf->Cell(50, 6, $outputlangs->transnoentities('Status') . ':', 0, 0, 'L');
        $statusLabel = $object->status == 1 ? $outputlangs->transnoentities('Validated') : $outputlangs->transnoentities('Draft');
        $pdf->Cell(0, 6, $statusLabel, 0, 1, 'L');
        
        // Footer with company logo and info
        $pdf->SetY(250);
        $pdf->SetFont('', 'I', 10);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 6, $outputlangs->transnoentities('GeneratedBy') . ' ' . $mysoc->name, 0, 1, 'C');
        $pdf->Cell(0, 6, dol_print_date(dol_now(), 'dayhour'), 0, 1, 'C');
        
        return 0;
    }

    /**
     * Show executive summary page
     *
     * @param TCPDF $pdf Object PDF
     * @param Audit $object Object to show
     * @param int $showaddress 0=no, 1=yes
     * @param Translate $outputlangs Object lang for output
     * @return int top shift of linked object lines
     */
    protected function _pagehead_summary(&$pdf, $object, $showaddress, $outputlangs)
    {
        $pdf->AddPage();
        
        // Page header
        $this->_pageheader($pdf, $object, $outputlangs, 'ExecutiveSummary');
        
        $y = 40;
        
        // Get executive summary data
        $summary = $object->generateExecutiveSummary();
        
        // Global score with visual gauge
        $pdf->SetY($y);
        $pdf->SetFont('', 'B', 16);
        $pdf->Cell(0, 10, $outputlangs->transnoentities('GlobalScore'), 0, 1, 'L');
        
        $y += 15;
        $this->drawGauge($pdf, 30, $y, $summary['global_score'], $outputlangs->transnoentities('GlobalScore'));
        
        // Maturity level
        $pdf->SetXY(100, $y);
        $pdf->SetFont('', 'B', 14);
        $pdf->Cell(0, 8, $outputlangs->transnoentities('MaturityLevel') . ':', 0, 1, 'L');
        $pdf->SetXY(100, $y + 10);
        $pdf->SetFont('', '', 12);
        $pdf->SetTextColor(hexdec(substr($summary['maturity_level']['color'], 1, 2)), 
                          hexdec(substr($summary['maturity_level']['color'], 3, 2)), 
                          hexdec(substr($summary['maturity_level']['color'], 5, 2)));
        $pdf->Cell(0, 8, $summary['maturity_level']['label'], 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
        
        $y += 40;
        
        // Key strengths
        if (!empty($summary['key_strengths'])) {
            $pdf->SetY($y);
            $pdf->SetFont('', 'B', 14);
            $pdf->SetFillColor(212, 237, 218);
            $pdf->Cell(0, 8, ' ' . $outputlangs->transnoentities('KeyStrengths'), 1, 1, 'L', true);
            $y += 10;
            
            $pdf->SetFont('', '', 11);
            foreach ($summary['key_strengths'] as $strength) {
                $pdf->SetY($y);
                $pdf->Cell(10, 6, '✓', 0, 0, 'C');
                $pdf->Cell(0, 6, $strength['label'] . ' (' . $strength['score'] . '%)', 0, 1, 'L');
                $y += 6;
            }
            $y += 5;
        }
        
        // Critical areas
        if (!empty($summary['critical_areas'])) {
            $pdf->SetY($y);
            $pdf->SetFont('', 'B', 14);
            $pdf->SetFillColor(248, 215, 218);
            $pdf->Cell(0, 8, ' ' . $outputlangs->transnoentities('CriticalAreas'), 1, 1, 'L', true);
            $y += 10;
            
            $pdf->SetFont('', '', 11);
            foreach ($summary['critical_areas'] as $area) {
                $pdf->SetY($y);
                $urgencyIcon = $area['urgency'] == 'high' ? '⚠' : '!';
                $pdf->Cell(10, 6, $urgencyIcon, 0, 0, 'C');
                $pdf->Cell(0, 6, $area['label'] . ' (' . $area['score'] . '%)', 0, 1, 'L');
                $y += 6;
            }
            $y += 5;
        }
        
        // Investment summary
        $pdf->SetY($y);
        $pdf->SetFont('', 'B', 14);
        $pdf->SetFillColor(217, 237, 247);
        $pdf->Cell(0, 8, ' ' . $outputlangs->transnoentities('InvestmentSummary'), 1, 1, 'L', true);
        $y += 10;
        
        $pdf->SetFont('', '', 11);
        $pdf->SetY($y);
        $pdf->Cell(80, 6, $outputlangs->transnoentities('TotalInvestment') . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, number_format($summary['investment_summary']['total_investment'], 0, ',', ' ') . ' €', 0, 1, 'L');
        $y += 6;
        
        $pdf->SetY($y);
        $pdf->Cell(80, 6, $outputlangs->transnoentities('AnnualSavings') . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, number_format($summary['investment_summary']['annual_savings'], 0, ',', ' ') . ' €', 0, 1, 'L');
        $y += 6;
        
        $pdf->SetY($y);
        $pdf->Cell(80, 6, $outputlangs->transnoentities('PaybackPeriod') . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, $summary['investment_summary']['payback_period'] . ' ' . $outputlangs->transnoentities('months'), 0, 1, 'L');
        $y += 6;
        
        $pdf->SetY($y);
        $pdf->Cell(80, 6, $outputlangs->transnoentities('ThreeYearROI') . ':', 0, 0, 'L');
        $pdf->SetFont('', 'B', 11);
        $pdf->Cell(0, 6, $summary['investment_summary']['three_year_roi'] . '%', 0, 1, 'L');
        
        return 0;
    }

    /**
     * Show detailed scores page
     *
     * @param TCPDF $pdf Object PDF
     * @param Audit $object Object to show
     * @param int $showaddress 0=no, 1=yes
     * @param Translate $outputlangs Object lang for output
     * @return int top shift of linked object lines
     */
    protected function _pagehead_scores(&$pdf, $object, $showaddress, $outputlangs)
    {
        $pdf->AddPage();
        
        // Page header
        $this->_pageheader($pdf, $object, $outputlangs, 'DetailedScores');
        
        $y = 40;
        $scores = $object->calculateScores();
        
        // Scores grid
        $categories = array(
            'digital' => $outputlangs->transnoentities('DigitalMaturity'),
            'security' => $outputlangs->transnoentities('Cybersecurity'),
            'cloud' => $outputlangs->transnoentities('CloudComputing'),
            'automation' => $outputlangs->transnoentities('Automation')
        );
        
        $x = 20;
        $cardWidth = 40;
        $cardHeight = 50;
        
        foreach ($categories as $key => $label) {
            if (isset($scores[$key])) {
                $this->drawScoreCard($pdf, $x, $y, $cardWidth, $cardHeight, $scores[$key], $label);
                $x += $cardWidth + 10;
                if ($x > 150) {
                    $x = 20;
                    $y += $cardHeight + 15;
                }
            }
        }
        
        // Radar chart placeholder (would need chart generation library)
        $y += 80;
        $pdf->SetY($y);
        $pdf->SetFont('', 'B', 14);
        $pdf->Cell(0, 10, $outputlangs->transnoentities('ScoreRadarChart'), 0, 1, 'C');
        
        $pdf->SetY($y + 15);
        $pdf->SetFont('', '', 10);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Rect(50, $y + 15, 110, 80, 'F');
        $pdf->SetY($y + 50);
        $pdf->Cell(0, 10, $outputlangs->transnoentities('ChartPlaceholder'), 0, 1, 'C');
        
        return 0;
    }

    /**
     * Show recommendations page
     *
     * @param TCPDF $pdf Object PDF
     * @param Audit $object Object to show
     * @param int $showaddress 0=no, 1=yes
     * @param Translate $outputlangs Object lang for output
     * @return int top shift of linked object lines
     */
    protected function _pagehead_recommendations(&$pdf, $object, $showaddress, $outputlangs)
    {
        $pdf->AddPage();
        
        // Page header
        $this->_pageheader($pdf, $object, $outputlangs, 'Recommendations');
        
        $y = 40;
        $scores = $object->calculateScores();
        $recommendations = $object->generateRecommendations($scores, $object->structure_type);
        
        foreach ($recommendations as $category => $recs) {
            if (empty($recs)) continue;
            
            $pdf->SetY($y);
            $pdf->SetFont('', 'B', 14);
            $pdf->Cell(0, 8, ucfirst($category), 0, 1, 'L');
            $y += 10;
            
            foreach ($recs as $rec) {
                $pdf->SetY($y);
                $pdf->SetFont('', 'B', 12);
                $pdf->Cell(0, 6, '• ' . $rec->title, 0, 1, 'L');
                $y += 8;
                
                $pdf->SetY($y);
                $pdf->SetFont('', '', 10);
                $pdf->MultiCell(0, 4, $rec->description, 0, 'L');
                $y += 12;
                
                // Priority and effort indicators
                $pdf->SetY($y);
                $pdf->SetFont('', '', 9);
                $pdf->Cell(40, 5, $outputlangs->transnoentities('Priority') . ': ' . $rec->priority, 0, 0, 'L');
                $pdf->Cell(40, 5, $outputlangs->transnoentities('Effort') . ': ' . $rec->effort, 0, 0, 'L');
                $pdf->Cell(0, 5, $outputlangs->transnoentities('Timeline') . ': ' . $rec->timeline, 0, 1, 'L');
                $y += 8;
                
                if ($y > 250) {
                    $pdf->AddPage();
                    $this->_pageheader($pdf, $object, $outputlangs, 'Recommendations');
                    $y = 40;
                }
            }
            $y += 5;
        }
        
        return 0;
    }

    /**
     * Show ROI analysis page
     *
     * @param TCPDF $pdf Object PDF
     * @param Audit $object Object to show
     * @param int $showaddress 0=no, 1=yes
     * @param Translate $outputlangs Object lang for output
     * @return int top shift of linked object lines
     */
    protected function _pagehead_roi(&$pdf, $object, $showaddress, $outputlangs)
    {
        $pdf->AddPage();
        
        // Page header
        $this->_pageheader($pdf, $object, $outputlangs, 'ROIAnalysis');
        
        $y = 40;
        $roiAnalysis = $object->calculateROI();
        
        // ROI Summary table
        $pdf->SetY($y);
        $pdf->SetFont('', 'B', 14);
        $pdf->Cell(0, 10, $outputlangs->transnoentities('ROISummary'), 0, 1, 'L');
        $y += 15;
        
        // Table headers
        $pdf->SetY($y);
        $pdf->SetFont('', 'B', 10);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(60, 8, $outputlangs->transnoentities('Category'), 1, 0, 'C', true);
        $pdf->Cell(30, 8, $outputlangs->transnoentities('Investment'), 1, 0, 'C', true);
        $pdf->Cell(30, 8, $outputlangs->transnoentities('AnnualSavings'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, $outputlangs->transnoentities('Payback'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, $outputlangs->transnoentities('ROI3Y'), 1, 1, 'C', true);
        $y += 8;
        
        // Table data
        $pdf->SetFont('', '', 9);
        foreach ($roiAnalysis['breakdown'] as $category => $data) {
            $pdf->SetY($y);
            $pdf->Cell(60, 6, ucfirst($category), 1, 0, 'L');
            $pdf->Cell(30, 6, number_format($data['investment'], 0, ',', ' ') . '€', 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($data['annual_savings'], 0, ',', ' ') . '€', 1, 0, 'R');
            $pdf->Cell(25, 6, $data['payback_period'] . 'm', 1, 0, 'C');
            $pdf->Cell(25, 6, $data['three_year_roi'] . '%', 1, 1, 'R');
            $y += 6;
        }
        
        // Total row
        $pdf->SetY($y);
        $pdf->SetFont('', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 6, $outputlangs->transnoentities('Total'), 1, 0, 'L', true);
        $pdf->Cell(30, 6, number_format($roiAnalysis['total_investment'], 0, ',', ' ') . '€', 1, 0, 'R', true);
        $pdf->Cell(30, 6, number_format($roiAnalysis['total_annual_savings'], 0, ',', ' ') . '€', 1, 0, 'R', true);
        $pdf->Cell(25, 6, $roiAnalysis['average_payback_period'] . 'm', 1, 0, 'C', true);
        $pdf->Cell(25, 6, $roiAnalysis['three_year_roi'] . '%', 1, 1, 'R', true);
        
        return 0;
    }

    /**
     * Show implementation roadmap page
     *
     * @param TCPDF $pdf Object PDF
     * @param Audit $object Object to show
     * @param int $showaddress 0=no, 1=yes
     * @param Translate $outputlangs Object lang for output
     * @return int top shift of linked object lines
     */
    protected function _pagehead_roadmap(&$pdf, $object, $showaddress, $outputlangs)
    {
        $pdf->AddPage();
        
        // Page header
        $this->_pageheader($pdf, $object, $outputlangs, 'ImplementationRoadmap');
        
        $y = 40;
        $roadmap = $object->generateRoadmap();
        
        foreach ($roadmap as $phaseKey => $phase) {
            if (empty($phase['actions'])) continue;
            
            $pdf->SetY($y);
            $pdf->SetFont('', 'B', 14);
            $pdf->SetFillColor(220, 235, 255);
            $pdf->Cell(0, 10, $phase['title'], 1, 1, 'L', true);
            $y += 12;
            
            $pdf->SetY($y);
            $pdf->SetFont('', '', 10);
            $pdf->MultiCell(0, 5, $phase['description'], 0, 'L');
            $y += 10;
            
            // Actions in this phase
            foreach ($phase['actions'] as $action) {
                $pdf->SetY($y);
                $pdf->SetFont('', 'B', 11);
                $pdf->Cell(0, 6, '• ' . ucfirst($action['category']), 0, 1, 'L');
                $y += 8;
                
                $pdf->SetY($y);
                $pdf->SetFont('', '', 9);
                $pdf->Cell(50, 5, $outputlangs->transnoentities('Investment') . ': ' . number_format($action['investment'], 0, ',', ' ') . '€', 0, 0, 'L');
                $pdf->Cell(50, 5, $outputlangs->transnoentities('AnnualSavings') . ': ' . number_format($action['annual_savings'], 0, ',', ' ') . '€', 0, 0, 'L');
                $pdf->Cell(0, 5, $outputlangs->transnoentities('Priority') . ': ' . $action['priority'] . '/100', 0, 1, 'L');
                $y += 8;
            }
            
            // Phase summary
            $pdf->SetY($y);
            $pdf->SetFont('', 'I', 9);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(0, 6, $outputlangs->transnoentities('PhaseTotal') . ': ' . 
                      number_format($phase['total_investment'], 0, ',', ' ') . '€ - ' .
                      $outputlangs->transnoentities('ExpectedSavings') . ': ' . 
                      number_format($phase['expected_savings'], 0, ',', ' ') . '€/an', 1, 1, 'C', true);
            $y += 15;
            
            if ($y > 240) {
                $pdf->AddPage();
                $this->_pageheader($pdf, $object, $outputlangs, 'ImplementationRoadmap');
                $y = 40;
            }
        }
        
        return 0;
    }

    /**
     * Draw a gauge for score visualization
     *
     * @param TCPDF $pdf PDF object
     * @param int $x X position
     * @param int $y Y position
     * @param int $score Score value (0-100)
     * @param string $label Label text
     */
    protected function drawGauge(&$pdf, $x, $y, $score, $label)
    {
        // Draw gauge background
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Circle($x + 15, $y + 15, 15, 0, 360, 'F');
        
        // Draw score arc
        $color = $this->getScoreColor($score);
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        
        // Simplified gauge - just a circle with color
        $pdf->Circle($x + 15, $y + 15, 12, 0, 360, 'F');
        
        // Score text
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('', 'B', 14);
        $pdf->SetXY($x + 5, $y + 12);
        $pdf->Cell(20, 6, $score . '%', 0, 1, 'C');
        
        // Label
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', 10);
        $pdf->SetXY($x - 5, $y + 32);
        $pdf->Cell(40, 5, $label, 0, 1, 'C');
    }

    /**
     * Draw a score card
     *
     * @param TCPDF $pdf PDF object
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Card width
     * @param int $height Card height
     * @param int $score Score value
     * @param string $label Card label
     */
    protected function drawScoreCard(&$pdf, $x, $y, $width, $height, $score, $label)
    {
        // Card background
        $pdf->SetFillColor(250, 250, 250);
        $pdf->Rect($x, $y, $width, $height, 'F');
        $pdf->Rect($x, $y, $width, $height, 'D');
        
        // Score
        $color = $this->getScoreColor($score);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetFont('', 'B', 20);
        $pdf->SetXY($x, $y + 15);
        $pdf->Cell($width, 10, $score . '%', 0, 1, 'C');
        
        // Label
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', 9);
        $pdf->SetXY($x, $y + 30);
        $pdf->Cell($width, 8, $label, 0, 1, 'C');
    }

    /**
     * Get color based on score
     *
     * @param int $score Score value
     * @return array RGB color array
     */
    protected function getScoreColor($score)
    {
        if ($score >= 80) return array(40, 167, 69);   // Green
        if ($score >= 60) return array(23, 162, 184);  // Blue
        if ($score >= 40) return array(255, 193, 7);   // Yellow
        if ($score >= 20) return array(253, 126, 20);  // Orange
        return array(220, 53, 69);                     // Red
    }

    /**
     * Show page header
     *
     * @param TCPDF $pdf PDF object
     * @param Audit $object Object
     * @param Translate $outputlangs Output language
     * @param string $title Page title
     */
    protected function _pageheader(&$pdf, $object, $outputlangs, $title)
    {
        global $mysoc;
        
        // Header background
        $pdf->SetFillColor(0, 102, 204);
        $pdf->Rect(0, 0, 210, 25, 'F');
        
        // Title
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('', 'B', 16);
        $pdf->SetY(8);
        $pdf->Cell(0, 10, $outputlangs->transnoentities($title), 0, 1, 'C');
        
        // Reset colors
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
    }
}