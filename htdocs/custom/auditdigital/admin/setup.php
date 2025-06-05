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
 * \file       admin/setup.php
 * \ingroup    auditdigital
 * \brief      AuditDigital setup page.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
    $res = @include "../../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/lib/auditdigital.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';

// Translations
$langs->loadLangs(array("admin", "auditdigital@auditdigital"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('auditdigitalsetup', 'globalsetup'));

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ'); // Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'auditdigital';

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;
$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
if ($useFormSetup && (float) DOL_VERSION >= 15.0) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
    $formSetup = new FormSetup($db);

    // Setup conf AUDITDIGITAL_MYPARAM1 (constant type)
    $item = $formSetup->newItem('AUDITDIGITAL_MYPARAM1');
    $item->setAsString();
    $item->defaultFieldValue = 'default value';
    $item->nameText = $langs->trans('AuditDigitalParam1Label');
    $item->helpText = $langs->trans('AuditDigitalParam1Help');

    // Setup conf AUDITDIGITAL_MYPARAM2 (constant type)
    $item = $formSetup->newItem('AUDITDIGITAL_MYPARAM2');
    $item->setAsString();
    $item->defaultFieldValue = 'default value';
    $item->nameText = $langs->trans('AuditDigitalParam2Label');
    $item->helpText = $langs->trans('AuditDigitalParam2Help');

    // Setup conf AUDITDIGITAL_MYPARAM3 (constant type)
    $item = $formSetup->newItem('AUDITDIGITAL_MYPARAM3');
    $item->setAsYesNo();
    $item->nameText = $langs->trans('AuditDigitalParam3Label');
    $item->helpText = $langs->trans('AuditDigitalParam3Help');

    // Setup conf AUDITDIGITAL_MYPARAM4 (constant type)
    $item = $formSetup->newItem('AUDITDIGITAL_MYPARAM4');
    $item->setAsEmailTemplate();
    $item->nameText = $langs->trans('AuditDigitalParam4Label');
    $item->helpText = $langs->trans('AuditDigitalParam4Help');

    $setupnotempty += count($formSetup->items);
}

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0) {
    include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}
if ($useFormSetup && (float) DOL_VERSION >= 15.0) {
    include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}

if ($action == 'updateMask') {
    $maskconst = GETPOST('maskconst', 'aZ09');
    $maskvalue = GETPOST('maskvalue', 'alpha');

    if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
        $res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
        if (!($res > 0)) {
            $error++;
        }
    }

    if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
} elseif ($action == 'specimen') {
    $modele = GETPOST('module', 'alpha');

    $audit = new Audit($db);
    $audit->initAsSpecimen();

    // Search template files
    $file = ''; $classname = ''; $filefound = 0;
    $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
    foreach ($dirmodels as $reldir) {
        $file = dol_buildpath($reldir."core/modules/auditdigital/doc/pdf_".$modele."_audit.modules.php", 0);
        if (file_exists($file)) {
            $filefound = 1;
            $classname = "pdf_".$modele;
            break;
        }
    }

    if ($filefound) {
        require_once $file;

        $module = new $classname($db);

        if ($module->write_file($audit, $langs) > 0) {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=auditdigital&file=SPECIMEN.pdf");
            return;
        } else {
            setEventMessages($module->error, $module->errors, 'errors');
            dol_syslog($module->error, LOG_ERR);
        }
    } else {
        setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
} elseif ($action == 'set') {
    // Activate a model
    $ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
    $ret = delDocumentModel($value, $type);
    if ($ret > 0) {
        if ($conf->global->AUDIT_ADDON_PDF == "$value") {
            dolibarr_del_const($db, 'AUDIT_ADDON_PDF', $conf->entity);
        }
    }
} elseif ($action == 'setmod') {
    // TODO Check if numbering module chosen can be activated by calling method canBeActivated
    dolibarr_set_const($db, "AUDITDIGITAL_AUDIT_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'setdoc') {
    // Set or unset default model
    if (dolibarr_set_const($db, "AUDIT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
        // The constant that was read before the new set
        // We therefore requires a variable to have a coherent view
        $conf->global->AUDIT_ADDON_PDF = $value;
    }

    // We disable/enable the document template (into llx_document_model table)
    $ret = delDocumentModel($value, $type);
    if ($ret > 0) {
        $ret = addDocumentModel($value, $type, $label, $scandir);
    }
} elseif ($action == 'unsetdoc') {
    dolibarr_del_const($db, "AUDIT_ADDON_PDF", $conf->entity);
} elseif ($action == 'load_solutions') {
    // Load solutions from JSON file
    $solutionLibrary = new SolutionLibrary($db);
    $jsonFile = DOL_DOCUMENT_ROOT.'/custom/auditdigital/data/solutions.json';
    
    $result = $solutionLibrary->loadFromJson($jsonFile);
    
    if ($result > 0) {
        setEventMessages($langs->trans("SolutionsLoadedSuccessfully", $result), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("ErrorLoadingSolutions").': '.$solutionLibrary->error, null, 'errors');
    }
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "AuditDigitalSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = auditdigitalAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "auditdigital@auditdigital");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("AuditDigitalSetupPage").'</span><br><br>';

if ($action == 'edit') {
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td class="titlefield">'.$langs->trans("Parameter").'</td>';
    print '<td>'.$langs->trans("Value").'</td>';
    print '</tr>';

    foreach ($arrayofparameters as $constname => $val) {
        if ($val['enabled']==1) {
            $setupnotempty++;
            print '<tr class="oddeven"><td>';
            $tooltiphelp = (($langs->trans($constname.'Tooltip') != $constname.'Tooltip') ? $langs->trans($constname.'Tooltip') : '');
            print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
            print '</td><td>';

            if ($val['type'] == 'textarea') {
                print '<textarea class="flat" name="'.$constname.'" id="'.$constname.'" cols="50" rows="5" wrap="soft">'."\n";
                print dol_escape_htmltag($conf->global->$constname, 1);
                print "</textarea>\n";
            } elseif ($val['type'] == 'html') {
                require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                $doleditor = new DolEditor($constname, $conf->global->$constname, '', 160, 'dolibarr_notes', '', false, false, false, ROWS_5, '90%');
                $doleditor->Create();
            } elseif ($val['type'] == 'yesno') {
                print $form->selectyesno($constname, $conf->global->$constname, 1);
            } elseif (preg_match('/emailtemplate:/', $val['type'])) {
                include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
                $formmail = new FormMail($db);

                $tmp = explode(':', $val['type']);
                $nboftemplates = $formmail->fetchAllEMailTemplate($tmp[1], $user, null, 1); // We set lang=null to get in priority record with no lang, then record with 'auto' lang, then record with lang=$langs->defaultlang
                $arrayofmessagename = array();
                if (is_array($formmail->lines_model)) {
                    foreach ($formmail->lines_model as $modelmail) {
                        //var_dump($modelmail);
                        $moreonlabel = '';
                        if (!empty($arrayofmessagename[$modelmail->label])) {
                            $moreonlabel = ' <span class="opacitymedium">('.$langs->trans("SeveralLangugeVariatFound").')</span>';
                        }
                        // The 'label' is the key that is unique if we exclude the language
                        $arrayofmessagename[$modelmail->label] = $langs->trans('LanguageShort'.$modelmail->lang).$moreonlabel;
                    }
                }
                print $form->selectarray($constname, $arrayofmessagename, $conf->global->$constname, 'None', 0, 0, '', 0, 0, 0, '', 'maxwidth400', 1);
            } elseif (preg_match('/category:/', $val['type'])) {
                require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
                require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
                $formother = new FormOther($db);

                $tmp = explode(':', $val['type']);
                print img_picto('', 'category', 'class="pictofixedwidth"');
                print $formother->select_categories($tmp[1], $conf->global->$constname, $constname, 0, $langs->trans('CustomersProspectsCategoriesShort'));
            } elseif (preg_match('/thirdparty_type/', $val['type'])) {
                require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
                $formcompany = new FormCompany($db);
                print $formcompany->selectProspectCustomerType($conf->global->$constname, $constname);
            } elseif ($val['type'] == 'securekey') {
                print '<input required="required" type="text" class="flat" id="'.$constname.'" name="'.$constname.'" value="'.(GETPOST($constname, 'alpha') ? GETPOST($constname, 'alpha') : $conf->global->$constname).'" size="40">';
                if (!empty($conf->use_javascript_ajax)) {
                    print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token'.$constname.'" class="linkobject"');
                }
                if (!empty($conf->use_javascript_ajax)) {
                    print "\n".'<script type="text/javascript">';
                    print '$(document).ready(function () {
                        $("#generate_token'.$constname.'").click(function() {
                            $.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
                                action: \'getrandompassword\',
                                format: \'takefromtooltip\',
                                length: 40},
                                function(token) {
                                    $("#'.$constname.'").val(token);
                                });
                        });
                    });';
                    print '</script>';
                }
            } elseif ($val['type'] == 'product') {
                if (isModEnabled("product") || isModEnabled("service")) {
                    $selected = (empty($conf->global->$constname) ? '' : $conf->global->$constname);
                    print img_picto('', 'product', 'class="pictofixedwidth"');
                    print $form->select_produits($selected, $constname, '', 0, 0, 1, 2, '', 0, array(), 0, '1', 0, 'maxwidth500 widthcentpercentminusx');
                }
            } else {
                print '<input name="'.$constname.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.$conf->global->$constname.'">';
            }
            print '</td></tr>';
        }
    }
    print '</table>';

    print '<br><div class="center">';
    print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
    print ' &nbsp; ';
    print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

    print '</form>';
    print '<br>';
} else {
    if (!empty($arrayofparameters)) {
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td class="titlefield">'.$langs->trans("Parameter").'</td>';
        print '<td>'.$langs->trans("Value").'</td>';
        print '<td class="center width20">&nbsp;</td>';
        print '</tr>';

        foreach ($arrayofparameters as $constname => $val) {
            if ($val['enabled']==1) {
                $setupnotempty++;
                print '<tr class="oddeven"><td>';
                $tooltiphelp = (($langs->trans($constname.'Tooltip') != $constname.'Tooltip') ? $langs->trans($constname.'Tooltip') : '');
                print $form->textwithpicto($langs->trans($constname), $tooltiphelp);
                print '</td><td>';

                if ($val['type'] == 'textarea') {
                    print dol_nl2br($conf->global->$constname);
                } elseif ($val['type'] == 'html') {
                    print $conf->global->$constname;
                } elseif ($val['type'] == 'yesno') {
                    print ajax_constantonoff($constname);
                } elseif (preg_match('/emailtemplate:/', $val['type'])) {
                    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
                    $formmail = new FormMail($db);

                    $tmp = explode(':', $val['type']);

                    $template = $formmail->getEMailTemplate($db, $tmp[1], $user, $langs, $conf->global->$constname);
                    if ($template<0) {
                        print '<span class="error">'.$langs->trans('ErrorFailedToLoadEmailTemplate', $constname).'</span>';
                    } elseif (empty($template)) {
                        print '<span class="opacitymedium">'.$langs->trans('None').'</span>';
                    } else {
                        print $template->label;
                    }
                } elseif (preg_match('/category:/', $val['type'])) {
                    $c = new Categorie($db);
                    $result = $c->fetch($conf->global->$constname);
                    if ($result < 0) {
                        print '<span class="error">'.$langs->trans('ErrorFailedToLoadCategory', $constname).'</span>';
                    } elseif (empty($c->id)) {
                        print '<span class="opacitymedium">'.$langs->trans('None').'</span>';
                    } else {
                        print $c->getNomUrl(1);
                    }
                } elseif ($val['type'] == 'product') {
                    $product = new Product($db);
                    $resprod = $product->fetch($conf->global->$constname);
                    if ($resprod > 0) {
                        print $product->ref;
                    } elseif ($resprod < 0) {
                        print '<span class="error">'.$langs->trans('ErrorFailedToLoadProduct', $constname).'</span>';
                    }
                } else {
                    print $conf->global->$constname;
                }
                print '</td><td class="center">';
                print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().'#'.$constname.'">';
                print img_edit();
                print '</a>';
                print '</td></tr>';
            }
        }

        print '</table>';

        print '<div class="tabsAction">';
        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
        print '</div>';
    } else {
        print '<br>'.$langs->trans("NothingToSetup");
    }
}

if ($useFormSetup && (float) DOL_VERSION >= 15.0 && !empty($formSetup->items)) {
    print $formSetup->generateOutput(true);
    print '<br>';
} elseif (!$useFormSetup) {
    print '<br>';
}

// Page end
print dol_get_fiche_end();

/*
 * Document templates generators
 */
print load_fiche_titre($langs->trans("DocumentModels"), '', '');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$db->escape($type)."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $i = 0;
    $num_rows = $db->num_rows($resql);
    while ($i < $num_rows) {
        $array = $db->fetch_array($resql);
        if (is_array($array)) {
            array_push($def, $array[0]);
        }
        $i++;
    }
} else {
    dol_print_error($db);
}

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td class="center" width="60">'.$langs->trans("Status").'</td>'."\n";
print '<td class="center" width="60">'.$langs->trans("Default").'</td>'."\n";
print '<td class="center" width="38">'.$langs->trans("ShortInfo").'</td>'."\n";
print '<td class="center" width="38">'.$langs->trans("Preview").'</td>'."\n";
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
    foreach (array('', '/doc') as $valdir) {
        $realpath = $reldir."core/modules/auditdigital".$valdir;
        $dir = dol_buildpath($realpath);

        if (is_dir($dir)) {
            $handle = opendir($dir);
            if (is_resource($handle)) {
                while (($file = readdir($handle)) !== false) {
                    $filelist[] = $file;
                }
                closedir($handle);
                arsort($filelist);

                foreach ($filelist as $file) {
                    if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
                        if (file_exists($dir.'/'.$file)) {
                            $name = substr($file, 4, dol_strlen($file) - 16);
                            $classname = substr($file, 0, dol_strlen($file) - 12);

                            require_once $dir.'/'.$file;
                            $module = new $classname($db);

                            $modulequalified = 1;
                            if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
                                $modulequalified = 0;
                            }
                            if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
                                $modulequalified = 0;
                            }

                            if ($modulequalified) {
                                print '<tr class="oddeven"><td width="100">';
                                print (empty($module->name) ? $name : $module->name);
                                print "</td><td>\n";
                                if (method_exists($module, 'info')) {
                                    print $module->info($langs); // @phan-suppress-current-line PhanUndeclaredMethod
                                } else {
                                    print $module->description;
                                }
                                print '</td>';

                                // Active
                                if (in_array($name, $def)) {
                                    print '<td class="center">'."\n";
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'">';
                                    print img_picto($langs->trans("Enabled"), 'switch_on');
                                    print '</a>';
                                    print '</td>';
                                } else {
                                    print '<td class="center">'."\n";
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
                                    print "</td>";
                                }

                                // Default
                                print '<td class="center">';
                                if ($conf->global->AUDIT_ADDON_PDF == $name) {
                                    print img_picto($langs->trans("Default"), 'on');
                                } else {
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
                                }
                                print '</td>';

                                // Info
                                $htmltooltip = ''.$langs->trans("Name").': '.$module->name;
                                $htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
                                if ($module->type == 'pdf') {
                                    $htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
                                }
                                $htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

                                $htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
                                $htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
                                $htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);

                                print '<td class="center">';
                                print $form->textwithpicto('', $htmltooltip, 1, 0);
                                print '</td>';

                                // Preview
                                print '<td class="center">';
                                if ($module->type == 'pdf') {
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'&token='.newToken().'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
                                } else {
                                    print img_object($langs->trans("PreviewNotAvailable"), 'generic');
                                }
                                print '</td>';

                                print "</tr>\n";
                            }
                        }
                    }
                }
            }
        }
    }
}

print '</table>';
print '</div>';

/*
 * Numbering models
 */
print load_fiche_titre($langs->trans("NumberingModels"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td class="nowrap">'.$langs->trans("Example").'</td>'."\n";
print '<td class="center" width="60">'.$langs->trans("Status").'</td>'."\n";
print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>'."\n";
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
    $dir = dol_buildpath($reldir."core/modules/auditdigital");

    if (is_dir($dir)) {
        $handle = opendir($dir);
        if (is_resource($handle)) {
            while (($file = readdir($handle)) !== false) {
                if (preg_match('/\.php$/', $file) && preg_match('/^(mod_audit_[a-z]+)/', $file)) {
                    $file = substr($file, 0, dol_strlen($file) - 4);

                    require_once $dir.'/'.$file.'.php';

                    $module = new $file($db);

                    // Show modules according to features level
                    if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
                        continue;
                    }
                    if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
                        continue;
                    }

                    if ($module->isEnabled()) {
                        dol_include_once('/auditdigital/class/audit.class.php');

                        print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
                        print $module->info($langs);
                        print '</td>';

                        // Show example of numbering model
                        print '<td class="nowrap">';
                        $tmp = $module->getExample();
                        if (preg_match('/^Error/', $tmp)) {
                            $langs->load("errors");
                            print '<div class="error">'.$langs->trans($tmp).'</div>';
                        } elseif ($tmp == 'NotConfigured') {
                            print '<span class="opacitymedium">'.$langs->trans($tmp).'</span>';
                        } else {
                            print $tmp;
                        }
                        print '</td>'."\n";

                        print '<td class="center">';
                        if ($conf->global->AUDITDIGITAL_AUDIT_ADDON == $file) {
                            print img_picto($langs->trans("Activated"), 'switch_on');
                        } else {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.urlencode($file).'">';
                            print img_picto($langs->trans("Disabled"), 'switch_off');
                            print '</a>';
                        }
                        print '</td>';

                        $audit = new Audit($db);
                        $audit->initAsSpecimen();

                        // Info
                        $htmltooltip = ''.$langs->trans("Version").': '.$module->getVersion().'<br>';
                        $nextval = $module->getNextValue($audit);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                            $htmltooltip .= ''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
                                    $nextval = $langs->trans($nextval);
                                }
                                $htmltooltip .= $nextval.'<br>';
                            } else {
                                $htmltooltip .= $langs->trans($module->error).'<br>';
                            }
                        }

                        print '<td class="center">';
                        print $form->textwithpicto('', $htmltooltip, 1, 0);
                        print '</td>';

                        print "</tr>\n";
                    }
                }
            }
            closedir($handle);
        }
    }
}
print "</table><br>\n";
print '</div>';

/*
 * Solutions Library Management
 */
print load_fiche_titre($langs->trans("SolutionLibrary"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Action").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td class="center">'.$langs->trans("Status").'</td>'."\n";
print '</tr>'."\n";

// Load solutions from JSON
print '<tr class="oddeven">';
print '<td>'.$langs->trans("LoadSolutionsFromJSON").'</td>';
print '<td>'.$langs->trans("LoadSolutionsFromJSONDesc").'</td>';
print '<td class="center">';
print '<a href="'.$_SERVER["PHP_SELF"].'?action=load_solutions&token='.newToken().'" class="button">';
print $langs->trans("LoadSolutions");
print '</a>';
print '</td>';
print '</tr>';

// Count existing solutions
$solutionLibrary = new SolutionLibrary($db);
$solutions = $solutionLibrary->fetchAll();
$nbSolutions = is_array($solutions) ? count($solutions) : 0;

print '<tr class="oddeven">';
print '<td>'.$langs->trans("CurrentSolutions").'</td>';
print '<td>'.$langs->trans("NumberOfSolutionsInLibrary", $nbSolutions).'</td>';
print '<td class="center">';
if ($nbSolutions > 0) {
    print '<a href="'.dol_buildpath('/auditdigital/admin/solutions.php', 1).'" class="button">';
    print $langs->trans("ManageSolutions");
    print '</a>';
} else {
    print '<span class="opacitymedium">'.$langs->trans("NoSolutions").'</span>';
}
print '</td>';
print '</tr>';

print "</table>\n";
print '</div>';

llxFooter();
$db->close();
?>