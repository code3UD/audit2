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
 * \file       core/modules/auditdigital/modules_audit.php
 * \ingroup    auditdigital
 * \brief      File that contains parent class for audit numbering models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';

/**
 * Parent class of audit numbering templates
 */
abstract class ModeleNumRefAudit extends CommonNumRefGenerator
{
    // No overload code
}

/**
 * Parent class for audit document generators
 */
abstract class ModelePDFAudit extends CommonDocGenerator
{
    /**
     * @var string Error code (or message)
     */
    public $error = '';

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Return list of active generation modules
     *
     * @param DoliDB $db Database handler
     * @param integer $maxfilenamelength Max length of value to show
     * @return array List of templates
     */
    public static function liste_modeles($db, $maxfilenamelength = 0)
    {
        // phpcs:enable
        $type = 'audit';
        $list = array();

        include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        $list = getListOfModels($db, $type, $maxfilenamelength);

        return $list;
    }
}

/**
 * Parent class for audit document generators
 */
abstract class CommonDocGeneratorAudit
{
    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var DoliDB Database handler
     */
    protected $db;

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

        $this->db = $db;

        $this->name = "";
        $this->description = "";

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
        $this->option_multilang = 1; // Available in several languages
        $this->option_freetext = 1; // Support add of a personalised text
        $this->option_draft_watermark = 0; // Support add of a watermark on drafts

        // Get source company
        $this->emetteur = $mysoc;
        if (empty($this->emetteur->country_code)) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
        }
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
        // Must be implemented in child class
    }

    /**
     * Function to build a document on disk using the generic odt module.
     *
     * @param Audit $object Object source to build document
     * @param Translate $outputlangs Lang output object
     * @param string $srctemplatepath Full path of source filename for generator using a template file
     * @param int $hidedetails Do not show line details
     * @param int $hidedesc Do not show desc
     * @param int $hideref Do not show ref
     * @return int 1 if OK, <=0 if KO
     */
    abstract public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0);
}
?>