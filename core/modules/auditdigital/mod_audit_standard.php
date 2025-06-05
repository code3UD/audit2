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
     * @var string model description (short)
     */
    public $description = "Standard audit numbering";

    /**
     * @var string Version
     */
    public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

    /**
     * @var int Automatic numbering
     */
    public $code_auto = 1;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->nom = "standard";
        $this->description = "Standard audit numbering";
    }

    /**
     * Return description of numbering module
     *
     * @param Translate $langs Lang object to use for output
     * @return string Descriptive text
     */
    public function info($langs)
    {
        global $langs, $db;

        $langs->load("auditdigital@auditdigital");

        $form = new Form($db);

        $text = $langs->trans('GenericNumRefModelDesc')."<br>\n";
        $text .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        $text .= '<input type="hidden" name="token" value="'.newToken().'">';
        $text .= '<input type="hidden" name="page_y" value="">';
        $text .= '<input type="hidden" name="action" value="updateMask">';
        $text .= '<input type="hidden" name="maskconstaudit" value="AUDITDIGITAL_AUDIT_MASK">';
        $text .= '<table class="nobordernopadding" width="100%">';

        $tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Audit"), $langs->transnoentities("Audit"));
        $tooltip .= $langs->trans("GenericMaskCodes2");
        $tooltip .= $langs->trans("GenericMaskCodes3");
        $tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Audit"), $langs->transnoentities("Audit"));
        $tooltip .= $langs->trans("GenericMaskCodes5");

        // Setting the prefix
        $text .= '<tr><td>'.$langs->trans("Mask").':</td>';
        $text .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskaudit" value="'.$conf->global->AUDITDIGITAL_AUDIT_MASK.'">', $tooltip, 1, 1).'</td>';

        $text .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button"value="'.$langs->trans("Modify").'"></td>';

        $text .= '</tr>';

        $text .= '</table>';
        $text .= '</form>';

        return $text;
    }

    /**
     * Return an example of numbering
     *
     * @return string Example
     */
    public function getExample()
    {
        global $conf, $langs, $mysoc;

        $old_code_client = $mysoc->code_client;
        $old_code_type = $mysoc->typent_code;
        $mysoc->code_client = 'CCCCCCCCCC';
        $mysoc->typent_code = 'TE';
        $numExample = $this->getNextValue($mysoc, '');
        $mysoc->code_client = $old_code_client;
        $mysoc->typent_code = $old_code_type;

        if (!$numExample) {
            $numExample = $langs->trans('NotConfigured');
        }
        return $numExample;
    }

    /**
     * Return next free value
     *
     * @param Societe $objsoc Object thirdparty
     * @param Audit $object Object we need next value for
     * @return string Value if KO, <0 if KO
     */
    public function getNextValue($objsoc, $object)
    {
        global $db, $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

        // We get cursor rule
        $mask = $conf->global->AUDITDIGITAL_AUDIT_MASK;

        if (!$mask) {
            $this->error = 'NotConfigured';
            return 0;
        }

        $date = (empty($object->date_audit) ? dol_now() : $object->date_audit);

        $numFinal = get_next_value($db, $mask, 'auditdigital_audit', 'ref', '', $objsoc, $date);

        return $numFinal;
    }
}
?>