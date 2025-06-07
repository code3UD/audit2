<?php
/* Copyright (C) 2024 Up Digit Agency
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/modules/auditdigital/doc/pdf_audit_enhanced.modules.php
 * \ingroup    auditdigital
 * \brief      Générateur PDF amélioré avec graphiques pour les audits
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

/**
 * Classe pour générer les PDF d'audit avec graphiques
 */
class pdf_audit_enhanced extends CommonDocGenerator
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
     * @var string model description (short text)
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
        $langs->loadLangs(array("main", "bills"));

        $this->db = $db;
        $this->name = "enhanced";
        $this->description = $langs->trans('AuditPDFEnhanced');
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
        if (!$this->emetteur->country_code) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
        }

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
     * @param object $hookmanager Hook manager propagated to allow calling of hooks
     * @return int 1=OK, 0=KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $hookmanager = null)
    {
        global $user, $langs, $conf, $mysoc, $db, $hookmanager;

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF)) {
            $outputlangs->charset_output = 'ISO-8859-1';
        }

        // Load translation files required by the page
        $outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "auditdigital"));

        $nblines = count($object->lines);

        if ($conf->auditdigital->dir_output) {
            $object->fetch_thirdparty();

            $deja_regle = $object->getSommePaiement();
            $amount_credit_notes_included = $object->getSumCreditNotesUsed();
            $amount_deposits_included = $object->getSumDepositsUsed();

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

                // Set nblines with the new lines content after hook
                $nblines = count($object->lines);

                // Create pdf instance
                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
                $pdf->SetAutoPageBreak(1, 0);

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

                // Page de garde
                $this->_pageCouverture($pdf, $object, $outputlangs);

                // Page synthèse exécutive
                $this->_pageSynthese($pdf, $object, $outputlangs);

                // Page scores détaillés avec graphiques
                $this->_pageScores($pdf, $object, $outputlangs);

                // Page recommandations
                $this->_pageRecommandations($pdf, $object, $outputlangs);

                // Page roadmap
                $this->_pageRoadmap($pdf, $object, $outputlangs);

                // Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0) {
                    $this->error = $hookmanager->error;
                    $this->errors = $hookmanager->errors;
                }

                if (!empty($conf->global->MAIN_UMASK)) {
                    @chmod($file, octdec($conf->global->MAIN_UMASK));
                }

                $pdf->Output($file, 'F');

                // Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0) {
                    $this->error = $hookmanager->error;
                    $this->errors = $hookmanager->errors;
                }

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

    /**
     * Page de couverture
     */
    protected function _pageCouverture($pdf, $object, $outputlangs)
    {
        $pdf->AddPage();
        
        // Fond dégradé
        $pdf->SetFillColor(44, 62, 80);
        $pdf->Rect(0, 0, $this->page_largeur, $this->page_hauteur, 'F');
        
        // Logo entreprise si disponible
        if (!empty($this->emetteur->logo)) {
            $logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
            if (is_readable($logo)) {
                $pdf->Image($logo, $this->marge_gauche, $this->marge_haute, 40);
            }
        }
        
        // Titre principal
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('', 'B', 32);
        $pdf->SetXY($this->marge_gauche, 80);
        $pdf->Cell(0, 15, $outputlangs->transnoentities('AuditDigitalReport'), 0, 1, 'L');
        
        // Sous-titre
        $pdf->SetFont('', '', 18);
        $pdf->SetXY($this->marge_gauche, 100);
        $pdf->Cell(0, 10, $outputlangs->transnoentities('DigitalMaturityAssessment'), 0, 1, 'L');
        
        // Informations entreprise
        $pdf->SetFont('', 'B', 16);
        $pdf->SetXY($this->marge_gauche, 140);
        $pdf->Cell(0, 10, $object->thirdparty->name, 0, 1, 'L');
        
        $pdf->SetFont('', '', 12);
        $pdf->SetXY($this->marge_gauche, 155);
        $pdf->Cell(0, 8, $object->thirdparty->address, 0, 1, 'L');
        $pdf->SetXY($this->marge_gauche, 163);
        $pdf->Cell(0, 8, $object->thirdparty->zip.' '.$object->thirdparty->town, 0, 1, 'L');
        
        // Score global en grand
        $pdf->SetFont('', 'B', 48);
        $pdf->SetXY($this->marge_gauche, 200);
        $scoreColor = $this->_getScoreColor($object->score_global);
        $pdf->SetTextColor($scoreColor[0], $scoreColor[1], $scoreColor[2]);
        $pdf->Cell(0, 20, $object->score_global.'/100', 0, 1, 'L');
        
        // Date et référence
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('', '', 10);
        $pdf->SetXY($this->marge_gauche, $this->page_hauteur - 30);
        $pdf->Cell(0, 5, $outputlangs->transnoentities('Date').': '.dol_print_date($object->date_creation, 'day'), 0, 1, 'L');
        $pdf->SetXY($this->marge_gauche, $this->page_hauteur - 25);
        $pdf->Cell(0, 5, $outputlangs->transnoentities('Reference').': '.$object->ref, 0, 1, 'L');
    }

    /**
     * Page synthèse exécutive
     */
    protected function _pageSynthese($pdf, $object, $outputlangs)
    {
        $pdf->AddPage();
        
        // En-tête
        $this->_pageHeader($pdf, $object, $outputlangs, 'Synthèse Exécutive');
        
        $y = 50;
        
        // Score global avec jauge
        $pdf->SetFont('', 'B', 16);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell(0, 10, 'Score Global de Maturité Digitale', 0, 1, 'L');
        
        $y += 20;
        $this->_drawScoreGauge($pdf, $this->marge_gauche, $y, $object->score_global);
        
        $y += 60;
        
        // Niveau de maturité
        $maturityLevel = $this->_getMaturityLevel($object->score_global);
        $pdf->SetFont('', 'B', 14);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell(0, 10, 'Niveau de Maturité: '.$maturityLevel, 0, 1, 'L');
        
        $y += 20;
        
        // Scores par domaine
        $pdf->SetFont('', 'B', 14);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell(0, 10, 'Scores par Domaine', 0, 1, 'L');
        
        $y += 15;
        $domains = [
            'Maturité Digitale' => $object->score_maturite,
            'Cybersécurité' => $object->score_cybersecurite,
            'Cloud & Infrastructure' => $object->score_cloud,
            'Automatisation' => $object->score_automatisation
        ];
        
        foreach ($domains as $domain => $score) {
            $this->_drawDomainScore($pdf, $this->marge_gauche, $y, $domain, $score);
            $y += 15;
        }
        
        $y += 20;
        
        // Points forts et axes d'amélioration
        $pdf->SetFont('', 'B', 14);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell(0, 10, 'Points Forts', 0, 1, 'L');
        
        $y += 15;
        $strengths = $this->_getStrengths($object);
        foreach ($strengths as $strength) {
            $pdf->SetFont('', '', 10);
            $pdf->SetXY($this->marge_gauche + 5, $y);
            $pdf->Cell(0, 6, '• '.$strength, 0, 1, 'L');
            $y += 8;
        }
        
        $y += 10;
        $pdf->SetFont('', 'B', 14);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell(0, 10, 'Axes d\'Amélioration Prioritaires', 0, 1, 'L');
        
        $y += 15;
        $improvements = $this->_getImprovements($object);
        foreach ($improvements as $improvement) {
            $pdf->SetFont('', '', 10);
            $pdf->SetXY($this->marge_gauche + 5, $y);
            $pdf->Cell(0, 6, '• '.$improvement, 0, 1, 'L');
            $y += 8;
        }
    }

    /**
     * Page scores détaillés avec graphiques
     */
    protected function _pageScores($pdf, $object, $outputlangs)
    {
        $pdf->AddPage();
        
        // En-tête
        $this->_pageHeader($pdf, $object, $outputlangs, 'Scores Détaillés');
        
        $y = 50;
        
        // Graphique radar des scores
        $this->_drawRadarChart($pdf, $this->marge_gauche + 20, $y, $object);
        
        $y += 120;
        
        // Tableau détaillé des scores
        $this->_drawScoreTable($pdf, $this->marge_gauche, $y, $object);
    }

    /**
     * Page recommandations
     */
    protected function _pageRecommandations($pdf, $object, $outputlangs)
    {
        $pdf->AddPage();
        
        // En-tête
        $this->_pageHeader($pdf, $object, $outputlangs, 'Recommandations');
        
        $y = 50;
        
        $recommendations = $this->_getDetailedRecommendations($object);
        
        foreach ($recommendations as $category => $recs) {
            $pdf->SetFont('', 'B', 14);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->SetXY($this->marge_gauche, $y);
            $pdf->Cell(0, 10, $category, 0, 1, 'L');
            
            $y += 15;
            
            foreach ($recs as $rec) {
                $pdf->SetFont('', '', 10);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetXY($this->marge_gauche + 5, $y);
                $pdf->MultiCell(0, 6, '• '.$rec, 0, 'L');
                $y += 12;
            }
            
            $y += 10;
        }
    }

    /**
     * Page roadmap
     */
    protected function _pageRoadmap($pdf, $object, $outputlangs)
    {
        $pdf->AddPage();
        
        // En-tête
        $this->_pageHeader($pdf, $object, $outputlangs, 'Roadmap d\'Implémentation');
        
        $y = 50;
        
        $roadmap = $this->_generateRoadmap($object);
        
        foreach ($roadmap as $phase => $details) {
            // Titre de phase
            $pdf->SetFont('', 'B', 14);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->SetXY($this->marge_gauche, $y);
            $pdf->Cell(0, 10, $phase, 0, 1, 'L');
            
            $y += 15;
            
            // Durée
            $pdf->SetFont('', 'I', 10);
            $pdf->SetXY($this->marge_gauche + 5, $y);
            $pdf->Cell(0, 6, 'Durée: '.$details['duration'], 0, 1, 'L');
            
            $y += 10;
            
            // Actions
            foreach ($details['actions'] as $action) {
                $pdf->SetFont('', '', 10);
                $pdf->SetXY($this->marge_gauche + 10, $y);
                $pdf->MultiCell(0, 6, '• '.$action, 0, 'L');
                $y += 8;
            }
            
            $y += 15;
        }
    }

    /**
     * En-tête de page
     */
    protected function _pageHeader($pdf, $object, $outputlangs, $title)
    {
        $pdf->SetFont('', 'B', 18);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->SetXY($this->marge_gauche, $this->marge_haute);
        $pdf->Cell(0, 12, $title, 0, 1, 'L');
        
        // Ligne de séparation
        $pdf->SetDrawColor(52, 152, 219);
        $pdf->SetLineWidth(1);
        $pdf->Line($this->marge_gauche, $this->marge_haute + 15, $this->page_largeur - $this->marge_droite, $this->marge_haute + 15);
    }

    /**
     * Dessiner une jauge de score
     */
    protected function _drawScoreGauge($pdf, $x, $y, $score)
    {
        $width = 150;
        $height = 20;
        
        // Fond de la jauge
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Rect($x, $y, $width, $height, 'F');
        
        // Remplissage selon le score
        $fillWidth = ($score / 100) * $width;
        $color = $this->_getScoreColor($score);
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($x, $y, $fillWidth, $height, 'F');
        
        // Bordure
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($x, $y, $width, $height);
        
        // Texte du score
        $pdf->SetFont('', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x + $width + 10, $y + 5);
        $pdf->Cell(0, 10, $score.'/100', 0, 0, 'L');
    }

    /**
     * Dessiner un score de domaine
     */
    protected function _drawDomainScore($pdf, $x, $y, $domain, $score)
    {
        $pdf->SetFont('', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y);
        $pdf->Cell(80, 8, $domain.':', 0, 0, 'L');
        
        // Barre de score
        $barWidth = 60;
        $barHeight = 8;
        $fillWidth = ($score / 100) * $barWidth;
        
        // Fond
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Rect($x + 85, $y, $barWidth, $barHeight, 'F');
        
        // Remplissage
        $color = $this->_getScoreColor($score);
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($x + 85, $y, $fillWidth, $barHeight, 'F');
        
        // Score numérique
        $pdf->SetXY($x + 150, $y);
        $pdf->Cell(0, 8, $score.'/100', 0, 0, 'L');
    }

    /**
     * Dessiner un graphique radar simplifié
     */
    protected function _drawRadarChart($pdf, $x, $y, $object)
    {
        $centerX = $x + 60;
        $centerY = $y + 60;
        $radius = 50;
        
        // Axes et labels
        $domains = [
            'Maturité' => $object->score_maturite,
            'Sécurité' => $object->score_cybersecurite,
            'Cloud' => $object->score_cloud,
            'Auto' => $object->score_automatisation
        ];
        
        $angles = [0, 90, 180, 270]; // Angles en degrés
        
        // Dessiner les axes
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.5);
        
        for ($i = 0; $i < count($angles); $i++) {
            $angle = deg2rad($angles[$i]);
            $endX = $centerX + $radius * cos($angle);
            $endY = $centerY + $radius * sin($angle);
            $pdf->Line($centerX, $centerY, $endX, $endY);
        }
        
        // Cercles concentriques
        for ($r = 20; $r <= $radius; $r += 20) {
            $pdf->Circle($centerX, $centerY, $r);
        }
        
        // Dessiner les valeurs
        $pdf->SetDrawColor(52, 152, 219);
        $pdf->SetLineWidth(2);
        
        $points = [];
        $i = 0;
        foreach ($domains as $domain => $score) {
            $angle = deg2rad($angles[$i]);
            $distance = ($score / 100) * $radius;
            $pointX = $centerX + $distance * cos($angle);
            $pointY = $centerY + $distance * sin($angle);
            $points[] = [$pointX, $pointY];
            
            // Labels
            $labelX = $centerX + ($radius + 15) * cos($angle);
            $labelY = $centerY + ($radius + 15) * sin($angle);
            $pdf->SetFont('', '', 8);
            $pdf->SetXY($labelX - 10, $labelY - 2);
            $pdf->Cell(20, 4, $domain, 0, 0, 'C');
            
            $i++;
        }
        
        // Connecter les points
        for ($i = 0; $i < count($points); $i++) {
            $nextI = ($i + 1) % count($points);
            $pdf->Line($points[$i][0], $points[$i][1], $points[$nextI][0], $points[$nextI][1]);
        }
    }

    /**
     * Dessiner un tableau de scores
     */
    protected function _drawScoreTable($pdf, $x, $y, $object)
    {
        $pdf->SetFont('', 'B', 12);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, 10, 'Détail des Scores par Domaine', 0, 1, 'L');
        
        $y += 15;
        
        // En-têtes
        $pdf->SetFont('', 'B', 10);
        $pdf->SetXY($x, $y);
        $pdf->Cell(80, 8, 'Domaine', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Score', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Niveau', 1, 0, 'C');
        $pdf->Cell(50, 8, 'Commentaire', 1, 1, 'C');
        
        $y += 8;
        
        // Données
        $domains = [
            'Maturité Digitale' => $object->score_maturite,
            'Cybersécurité' => $object->score_cybersecurite,
            'Cloud & Infrastructure' => $object->score_cloud,
            'Automatisation' => $object->score_automatisation
        ];
        
        $pdf->SetFont('', '', 9);
        foreach ($domains as $domain => $score) {
            $level = $this->_getScoreLevel($score);
            $comment = $this->_getScoreComment($score);
            
            $pdf->SetXY($x, $y);
            $pdf->Cell(80, 8, $domain, 1, 0, 'L');
            $pdf->Cell(30, 8, $score.'/100', 1, 0, 'C');
            $pdf->Cell(30, 8, $level, 1, 0, 'C');
            $pdf->Cell(50, 8, $comment, 1, 1, 'L');
            $y += 8;
        }
    }

    /**
     * Obtenir la couleur selon le score
     */
    protected function _getScoreColor($score)
    {
        if ($score >= 80) return [39, 174, 96]; // Vert
        if ($score >= 60) return [52, 152, 219]; // Bleu
        if ($score >= 40) return [243, 156, 18]; // Orange
        return [231, 76, 60]; // Rouge
    }

    /**
     * Obtenir le niveau de maturité
     */
    protected function _getMaturityLevel($score)
    {
        if ($score >= 80) return 'Expert';
        if ($score >= 60) return 'Avancé';
        if ($score >= 40) return 'Intermédiaire';
        return 'Débutant';
    }

    /**
     * Obtenir le niveau d'un score
     */
    protected function _getScoreLevel($score)
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Bon';
        if ($score >= 40) return 'Moyen';
        return 'Faible';
    }

    /**
     * Obtenir un commentaire sur le score
     */
    protected function _getScoreComment($score)
    {
        if ($score >= 80) return 'Très satisfaisant';
        if ($score >= 60) return 'Satisfaisant';
        if ($score >= 40) return 'À améliorer';
        return 'Critique';
    }

    /**
     * Obtenir les points forts
     */
    protected function _getStrengths($object)
    {
        $strengths = [];
        
        if ($object->score_maturite >= 70) {
            $strengths[] = 'Bonne maturité digitale des processus';
        }
        if ($object->score_cybersecurite >= 70) {
            $strengths[] = 'Niveau de sécurité satisfaisant';
        }
        if ($object->score_cloud >= 70) {
            $strengths[] = 'Infrastructure cloud bien adoptée';
        }
        if ($object->score_automatisation >= 70) {
            $strengths[] = 'Bon niveau d\'automatisation';
        }
        
        if (empty($strengths)) {
            $strengths[] = 'Potentiel d\'amélioration important identifié';
        }
        
        return $strengths;
    }

    /**
     * Obtenir les axes d'amélioration
     */
    protected function _getImprovements($object)
    {
        $improvements = [];
        
        if ($object->score_maturite < 60) {
            $improvements[] = 'Digitaliser davantage les processus métier';
        }
        if ($object->score_cybersecurite < 60) {
            $improvements[] = 'Renforcer la sécurité informatique';
        }
        if ($object->score_cloud < 60) {
            $improvements[] = 'Adopter les technologies cloud';
        }
        if ($object->score_automatisation < 60) {
            $improvements[] = 'Automatiser les tâches répétitives';
        }
        
        return $improvements;
    }

    /**
     * Obtenir les recommandations détaillées
     */
    protected function _getDetailedRecommendations($object)
    {
        $recommendations = [];
        
        if ($object->score_maturite < 60) {
            $recommendations['Maturité Digitale'] = [
                'Mettre en place un ERP/CRM adapté à votre secteur',
                'Digitaliser les processus de facturation et devis',
                'Former les équipes aux outils numériques',
                'Développer une stratégie de transformation digitale'
            ];
        }
        
        if ($object->score_cybersecurite < 60) {
            $recommendations['Cybersécurité'] = [
                'Implémenter une politique de sécurité informatique',
                'Former les utilisateurs aux bonnes pratiques',
                'Mettre en place des sauvegardes automatisées',
                'Renforcer l\'authentification (2FA)'
            ];
        }
        
        if ($object->score_cloud < 60) {
            $recommendations['Cloud & Infrastructure'] = [
                'Migrer vers des solutions cloud sécurisées',
                'Mettre en place des outils de télétravail',
                'Optimiser la mobilité des équipes',
                'Sauvegarder les données dans le cloud'
            ];
        }
        
        if ($object->score_automatisation < 60) {
            $recommendations['Automatisation'] = [
                'Automatiser les tâches répétitives',
                'Implémenter des workflows métier',
                'Utiliser des outils de collaboration modernes',
                'Analyser les données pour optimiser les processus'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Générer la roadmap
     */
    protected function _generateRoadmap($object)
    {
        $roadmap = [];
        
        $roadmap['Phase 1 - Actions Immédiates (0-3 mois)'] = [
            'duration' => '3 mois',
            'actions' => [
                'Audit complet de l\'existant',
                'Formation des équipes aux outils actuels',
                'Mise en place des sauvegardes',
                'Sécurisation des accès'
            ]
        ];
        
        $roadmap['Phase 2 - Optimisation (3-12 mois)'] = [
            'duration' => '9 mois',
            'actions' => [
                'Déploiement d\'outils métier adaptés',
                'Digitalisation des processus prioritaires',
                'Migration vers le cloud',
                'Automatisation des tâches répétitives'
            ]
        ];
        
        $roadmap['Phase 3 - Innovation (12+ mois)'] = [
            'duration' => '12+ mois',
            'actions' => [
                'Intelligence artificielle et machine learning',
                'Analyse prédictive des données',
                'Écosystème digital complet',
                'Innovation continue'
            ]
        ];
        
        return $roadmap;
    }
}