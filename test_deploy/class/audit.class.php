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
 * \file        class/audit.class.php
 * \ingroup     auditdigital
 * \brief       This file is a CRUD class file for Audit (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * Class for Audit
 */
class Audit extends CommonObject
{
    /**
     * @var string ID of module.
     */
    public $module = 'auditdigital';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'audit';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'auditdigital_audit';

    /**
     * @var int  Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for audit. Must be the part after the 'object_' into object_audit.png
     */
    public $picto = 'audit@auditdigital';

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_SENT = 2;

    /**
     *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
     *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     *  'label' the translation key.
     *  'picto' is code of a picto to show before value in forms
     *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
     *  'position' is the sort order of field.
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list but not create/update/view forms, 5=Visible on list and view only (not create/not update). 5 is like 4 but also visible on view form). Using a negative value means field is not shown by default on list but can be selected for viewing
     *  'noteditable' says if field is not editable (1 or 0)
     *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     *  'validate' is 1 if need to validate with $this->validateField()
     *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

    // BEGIN MODULEBUILDER PROPERTIES
    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields=array(
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
        'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'validate'=>'1', 'comment'=>"Reference of object"),
        'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth300', 'validate'=>'1', 'showoncombobox'=>'1',),
        'audit_type' => array('type'=>'varchar(50)', 'label'=>'AuditType', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('digital_maturity'=>'Digital Maturity', 'cybersecurity'=>'Cybersecurity', 'cloud_readiness'=>'Cloud Readiness')),
        'structure_type' => array('type'=>'varchar(50)', 'label'=>'StructureType', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('tpe_pme'=>'TPE/PME', 'collectivite'=>'Collectivité')),
        'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php:1', 'label'=>'ThirdParty', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'validate'=>'1',),
        'fk_projet' => array('type'=>'integer:Project:projet/class/project.class.php:1', 'label'=>'Project', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>1, 'index'=>1,),
        'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
        'date_audit' => array('type'=>'datetime', 'label'=>'DateAudit', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>1,),
        'date_valid' => array('type'=>'datetime', 'label'=>'DateValidation', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>-2,),
        'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
        'fk_user_valid' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserValidation', 'enabled'=>'1', 'position'=>520, 'notnull'=>0, 'visible'=>-2,),
        'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'default'=>'0', 'arrayofkeyval'=>array('0'=>'Draft', '1'=>'Validated', '2'=>'Sent')),
        'score_global' => array('type'=>'integer', 'label'=>'GlobalScore', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>1, 'isameasure'=>'1'),
        'score_maturite' => array('type'=>'integer', 'label'=>'MaturityScore', 'enabled'=>'1', 'position'=>110, 'notnull'=>0, 'visible'=>1, 'isameasure'=>'1'),
        'score_cybersecurite' => array('type'=>'integer', 'label'=>'CybersecurityScore', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>1, 'isameasure'=>'1'),
        'score_cloud' => array('type'=>'integer', 'label'=>'CloudScore', 'enabled'=>'1', 'position'=>130, 'notnull'=>0, 'visible'=>1, 'isameasure'=>'1'),
        'score_automatisation' => array('type'=>'integer', 'label'=>'AutomationScore', 'enabled'=>'1', 'position'=>140, 'notnull'=>0, 'visible'=>1, 'isameasure'=>'1'),
        'json_config' => array('type'=>'text', 'label'=>'Configuration', 'enabled'=>'1', 'position'=>200, 'notnull'=>0, 'visible'=>0),
        'json_responses' => array('type'=>'text', 'label'=>'Responses', 'enabled'=>'1', 'position'=>210, 'notnull'=>0, 'visible'=>0),
        'json_recommendations' => array('type'=>'text', 'label'=>'Recommendations', 'enabled'=>'1', 'position'=>220, 'notnull'=>0, 'visible'=>0),
        'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>400, 'notnull'=>0, 'visible'=>0,),
        'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>401, 'notnull'=>0, 'visible'=>0,),
        'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model', 'enabled'=>'1', 'position'=>402, 'notnull'=>0, 'visible'=>0, 'default'=>'standard'),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>0, 'default'=>'1', 'index'=>1),
        'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1002, 'notnull'=>-1, 'visible'=>-2,),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
    );
    public $rowid;
    public $ref;
    public $label;
    public $audit_type;
    public $structure_type;
    public $fk_soc;
    public $fk_projet;
    public $date_creation;
    public $date_audit;
    public $date_valid;
    public $fk_user_creat;
    public $fk_user_valid;
    public $status;
    public $score_global;
    public $score_maturite;
    public $score_cybersecurite;
    public $score_cloud;
    public $score_automatisation;
    public $json_config;
    public $json_responses;
    public $json_recommendations;
    public $note_private;
    public $note_public;
    public $model_pdf;
    public $entity;
    public $import_key;
    public $tms;
    // END MODULEBUILDER PROPERTIES

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        // Example to show how to set values of fields definition dynamically
        /*if ($user->rights->auditdigital->audit->read) {
            $this->fields['myfield']['visible'] = 1;
            $this->fields['myfield']['noteditable'] = 0;
        }*/

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        // Translate some data of arrayofkeyval
        if (is_object($langs)) {
            foreach ($this->fields as $key => $val) {
                if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                    foreach ($val['arrayofkeyval'] as $key2 => $val2) {
                        $this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
                    }
                }
            }
        }
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = false)
    {
        $resultcreate = $this->createCommon($user, $notrigger);

        if ($resultcreate > 0) {
            // Generate reference if needed
            if ($this->ref == '(PROV)') {
                $this->ref = $this->getNextNumRef();
                $this->update($user, 1);
            }
        }

        return $resultcreate;
    }

    /**
     * Clone an object into another one
     *
     * @param  	User 	$user      	User that creates
     * @param  	int 	$fromid     Id of object to clone
     * @return 	mixed 				New object created, <0 if KO
     */
    public function createFromClone(User $user, $fromid)
    {
        global $langs, $extrafields;
        $error = 0;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $object = new self($this->db);

        $this->db->begin();

        // Load source object
        $result = $object->fetchCommon($fromid);
        if ($result > 0 && !empty($object->table_element_line)) {
            $object->fetchLines();
        }

        // get lines so they will be clone
        //foreach($this->lines as $line)
        //	$line->fetch_optionals();

        // Reset some properties
        unset($object->id);
        unset($object->fk_user_creat);
        unset($object->import_key);

        // Clear fields
        if (property_exists($object, 'ref')) {
            $object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
        }
        if (property_exists($object, 'label')) {
            $object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
        }
        if (property_exists($object, 'status')) {
            $object->status = self::STATUS_DRAFT;
        }
        if (property_exists($object, 'date_creation')) {
            $object->date_creation = dol_now();
        }
        if (property_exists($object, 'date_modification')) {
            $object->date_modification = null;
        }
        // ...
        // Clear extrafields that are unique
        if (is_array($object->array_options) && count($object->array_options) > 0) {
            $extrafields->fetch_name_optionals_label($this->table_element);
            foreach ($object->array_options as $key => $option) {
                $shortkey = preg_replace('/options_/', '', $key);
                if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
                    //var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
                    unset($object->array_options[$key]);
                }
            }
        }

        // Create clone
        $object->context['createfromclone'] = 'createfromclone';
        $result = $object->createCommon($user);
        if ($result < 0) {
            $error++;
            $this->error = $object->error;
            $this->errors = $object->errors;
        }

        if (!$error) {
            // copy internal contacts
            if ($this->copy_linked_contact($object, $fromid) < 0) {
                $error++;
            }
        }

        if (!$error) {
            // copy external contacts if same company
            if (property_exists($this, 'fk_soc') && $this->fk_soc == $object->fk_soc) {
                if ($this->copy_linked_contact($object, $fromid, 'external') < 0) {
                    $error++;
                }
            }
        }

        unset($object->context['createfromclone']);

        // End
        if (!$error) {
            $this->db->commit();
            return $object;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Load object in memory from the database
     *
     * @param int    $id   Id object
     * @param string $ref  Ref
     * @return int         <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = null)
    {
        $result = $this->fetchCommon($id, $ref);
        if ($result > 0 && !empty($this->table_element_line)) {
            $this->fetchLines();
        }
        return $result;
    }

    /**
     * Load object lines in memory from the database
     *
     * @return int         <0 if KO, 0 if not found, >0 if OK
     */
    public function fetchLines()
    {
        $this->lines = array();

        $result = $this->fetchLinesCommon();
        return $result;
    }

    /**
     * Load list of objects in memory from the database.
     *
     * @param  string      $sortorder    Sort Order
     * @param  string      $sortfield    Sort field
     * @param  int         $limit        limit
     * @param  int         $offset       Offset
     * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
     * @param  string      $filtermode   Filter mode (AND or OR)
     * @return array|int                 int <0 if KO, array of pages if OK
     */
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = array();

        $sql = "SELECT ";
        $sql .= $this->getFieldList('t');
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
            $sql .= " WHERE t.entity IN (".getEntity($this->element).")";
        } else {
            $sql .= " WHERE 1 = 1";
        }
        // Manage filter
        $sqlwhere = array();
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                if ($key == 't.rowid') {
                    $sqlwhere[] = $key." = ".((int) $value);
                } elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
                    $sqlwhere[] = $key." = '".$this->db->escape($value)."'";
                } elseif ($key == 'customsql') {
                    $sqlwhere[] = $value;
                } elseif (strpos($value, '%') === false) {
                    $sqlwhere[] = $key." IN (".$this->db->sanitize($this->db->escape($value)).")";
                } else {
                    $sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
                }
            }
        }
        if (count($sqlwhere) > 0) {
            $sql .= " AND (".implode(" ".$filtermode." ", $sqlwhere).")";
        }

        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            $sql .= $this->db->plimit($limit, $offset);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                $record = new self($this->db);
                $record->setVarsFromFetchObj($obj);

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = false)
    {
        return $this->updateCommon($user, $notrigger);
    }

    /**
     * Delete object in database
     *
     * @param User $user       User that deletes
     * @param bool $notrigger  false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        return $this->deleteCommon($user, $notrigger);
        //return $this->deleteCommon($user, $notrigger, 1);
    }

    /**
     *  Delete a line of object in database
     *
     *	@param  User	$user       User that delete
     *  @param	int		$idline		Id of line to delete
     *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
     *  @return int         		>0 if OK, <0 if KO
     */
    public function deleteLine(User $user, $idline, $notrigger = false)
    {
        if ($this->status < 0) {
            $this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
            return -2;
        }

        return $this->deleteLineCommon($user, $idline, $notrigger);
    }

    /**
     *	Validate object
     *
     *	@param		User	$user     		User making the validation
     *	@param		bool	$notrigger		1=Does not execute triggers, 0= execute triggers
     *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
     */
    public function validate($user, $notrigger = 0)
    {
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $error = 0;

        // Protection
        if ($this->status == self::STATUS_VALIDATED) {
            dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
            return 0;
        }

        /*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->auditdigital->audit->write))
         || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->auditdigital->audit->audit_advance->validate))))
         {
         $this->error='NotEnoughPermissions';
         dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
         return -1;
         }*/

        $now = dol_now();

        $this->db->begin();

        // Define new ref
        if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
            $num = $this->getNextNumRef();
        } else {
            $num = $this->ref;
        }
        $this->newref = $num;

        if (!empty($num)) {
            // Validate
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
            $sql .= " SET ref = '".$this->db->escape($num)."',";
            $sql .= " status = ".self::STATUS_VALIDATED.",";
            $sql .= " date_valid = '".$this->db->idate($now)."',";
            $sql .= " fk_user_valid = ".((int) $user->id);
            $sql .= " WHERE rowid = ".((int) $this->id);

            dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (!$resql) {
                dol_print_error($this->db);
                $this->error = $this->db->lasterror();
                $error++;
            }

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('AUDIT_VALIDATE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }
        }

        if (!$error) {
            $this->oldref = $this->ref;

            // Rename directory if dir was a temporary ref
            if (preg_match('/^[\(]?PROV/i', $this->ref)) {
                // Now we rename also files into index
                $sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'audit/".$this->db->escape($this->newref)."'";
                $sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'audit/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $error++; $this->error = $this->db->lasterror();
                }

                // We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
                $oldref = dol_sanitizeFileName($this->ref);
                $newref = dol_sanitizeFileName($num);
                $dirsource = $conf->auditdigital->dir_output.'/audit/'.$oldref;
                $dirdest = $conf->auditdigital->dir_output.'/audit/'.$newref;
                if (!$error && file_exists($dirsource)) {
                    dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

                    if (@rename($dirsource, $dirdest)) {
                        dol_syslog("Rename ok");
                        // Rename docs starting with $oldref with $newref
                        $listoffiles = dol_dir_list($conf->auditdigital->dir_output.'/audit/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
                        foreach ($listoffiles as $fileentry) {
                            $dirsource = $fileentry['name'];
                            $dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
                            $dirsource = $fileentry['path'].'/'.$dirsource;
                            $dirdest = $fileentry['path'].'/'.$dirdest;
                            @rename($dirsource, $dirdest);
                        }
                    }
                }
            }
        }

        // Set new ref and current status
        if (!$error) {
            $this->ref = $num;
            $this->status = self::STATUS_VALIDATED;
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *	Set draft status
     *
     *	@param	User	$user			Object user that modify
     *	@param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
     *	@return	int						<0 if KO, >0 if OK
     */
    public function setDraft($user, $notrigger = 0)
    {
        // Protection
        if ($this->status <= self::STATUS_DRAFT) {
            return 0;
        }

        /*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->auditdigital->write))
         || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->auditdigital->audit_advance->validate))))
         {
         $this->error='Permission denied';
         return -1;
         }*/

        return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'AUDIT_UNVALIDATE');
    }

    /**
     *	Set cancel status
     *
     *	@param	User	$user			Object user that modify
     *	@param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
     *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
     */
    public function cancel($user, $notrigger = 0)
    {
        // Protection
        if ($this->status != self::STATUS_VALIDATED) {
            return 0;
        }

        /*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->auditdigital->write))
         || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->auditdigital->audit_advance->validate))))
         {
         $this->error='Permission denied';
         return -1;
         }*/

        return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'AUDIT_CANCEL');
    }

    /**
     *	Set back to validated status
     *
     *	@param	User	$user			Object user that modify
     *	@param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
     *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
     */
    public function reopen($user, $notrigger = 0)
    {
        // Protection
        if ($this->status == self::STATUS_VALIDATED) {
            return 0;
        }

        /*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->auditdigital->write))
         || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->auditdigital->audit_advance->validate))))
         {
         $this->error='Permission denied';
         return -1;
         }*/

        return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'AUDIT_REOPEN');
    }

    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     *  @param  string  $option                     On what the link point to ('nolink', ...)
     *  @param  int     $notooltip                  1=Disable tooltip
     *  @param  string  $morecss                    Add more css on link
     *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @return	string                              String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $conf, $langs, $hookmanager;

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1; // Force disable tooltips
        }

        $result = '';

        $label = img_picto('', $this->picto).(' '.$this->ref);
        if (isset($this->status)) {
            $label .= ' '.$this->getLibStatut(5);
        }
        $label = '<u>'.$langs->trans("Audit").'</u>';
        if (!empty($this->ref)) {
            $label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
        }
        if (!empty($this->label)) {
            $label .= '<br><b>'.$langs->trans('Label').':</b> '.$this->label;
        }

        $url = dol_buildpath('/auditdigital/audit_card.php', 1).'?id='.$this->id;

        if ($option != 'nolink') {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
                $add_save_lastsearch_values = 1;
            }
            if ($add_save_lastsearch_values) {
                $url .= '&save_lastsearch_values=1';
            }
        }

        $linkclose = '';
        if (empty($notooltip)) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans("ShowAudit");
                $linkclose .= ' alt="'.$label.'"';
            }
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
        } else {
            $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
        }

        if ($option == 'nolink') {
            $linkstart = '<span';
        } else {
            $linkstart = '<a href="'.$url.'"';
        }
        $linkstart .= $linkclose.'>';
        if ($option == 'nolink') {
            $linkend = '</span>';
        } else {
            $linkend = '</a>';
        }

        $result .= $linkstart;

        if (empty($this->showphoto_on_popup)) {
            if ($withpicto) {
                $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
            }
        } else {
            if ($withpicto) {
                require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

                list($class, $module) = explode('@', $this->picto);
                $upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
                $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $conf->global->GED_SORT_FIELD, SORT_ASC, 1);
                if (count($filearray)) {
                    $filename = $filearray[0]['name'];
                    $origfile = $upload_dir.'/'.$filename;
                    $file = $upload_dir.'/'.$filename;
                }
                if (!empty($filename)) {
                    $result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($class.'/'.dol_sanitizeFileName($this->ref).'/'.$filename).'"></div></div>';
                } else {
                    $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
                }
            }
        }

        if ($withpicto != 2) {
            $result .= $this->ref;
        }

        $result .= $linkend;
        //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

        global $action, $hookmanager;
        $hookmanager->initHooks(array('auditdao'));
        $parameters = array('id'=>$this->id, 'getnomurl'=>$result);
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) {
            $result = $hookmanager->resPrint;
        } else {
            $result .= $hookmanager->resPrint;
        }

        return $result;
    }

    /**
     *  Return the label of the status
     *
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return	string 			       Label of status
     */
    public function getLabelStatus($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    /**
     *  Return the label of the status
     *
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return	string 			       Label of status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return the status
     *
     *  @param	int		$status        Id status
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return string 			       Label of status
     */
    public function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;
            //$langs->load("auditdigital@auditdigital");
            $this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
            $this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
            $this->labelStatus[self::STATUS_SENT] = $langs->transnoentitiesnoconv('Sent');
            $this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
            $this->labelStatusShort[self::STATUS_SENT] = $langs->transnoentitiesnoconv('Sent');
        }

        $statusType = 'status'.$status;
        //if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
        if ($status == self::STATUS_SENT) {
            $statusType = 'status6';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     *	Load the info information in the object
     *
     *	@param  int		$id       Id of object
     *	@return	void
     */
    public function info($id)
    {
        $sql = "SELECT rowid, date_creation as datec, tms as datem,";
        $sql .= " fk_user_creat, fk_user_valid";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " WHERE t.rowid = ".((int) $id);

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->rowid;
                if (!empty($obj->fk_user_author)) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation = $cuser;
                }

                if (!empty($obj->fk_user_valid)) {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_valid);
                    $this->user_validation = $vuser;
                }

                if (!empty($obj->fk_user_cloture)) {
                    $cluser = new User($this->db);
                    $cluser->fetch($obj->fk_user_cloture);
                    $this->user_cloture = $cluser;
                }

                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datem);
                $this->date_validation   = $this->db->jdate($obj->datev);
            }

            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     * Initialise object with example values
     * Id must be 0 if object instance is a specimen
     *
     * @return void
     */
    public function initAsSpecimen()
    {
        // Set here init that are not commonf fields
        // $this->property1 = ...
        // $this->property2 = ...

        $this->initAsSpecimenCommon();
    }

    /**
     * 	Create an array of lines
     *
     * 	@return array|int		array of lines if OK, <0 if KO
     */
    public function getLinesArray()
    {
        $this->lines = array();

        $objectline = new AuditLine($this->db);
        $result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_audit = '.((int) $this->id)));

        if (is_numeric($result)) {
            $this->error = $objectline->error;
            $this->errors = $objectline->errors;
            return $result;
        } else {
            $this->lines = $result;
            return $this->lines;
        }
    }

    /**
     *  Returns the reference to the following non used object depending on the active numbering module.
     *
     *  @return string      		Object free reference
     */
    public function getNextNumRef()
    {
        global $langs, $conf;
        $langs->load("auditdigital@auditdigital");

        if (empty($conf->global->AUDITDIGITAL_AUDIT_ADDON)) {
            $conf->global->AUDITDIGITAL_AUDIT_ADDON = 'mod_audit_standard';
        }

        if (!empty($conf->global->AUDITDIGITAL_AUDIT_ADDON)) {
            $mybool = false;

            $file = $conf->global->AUDITDIGITAL_AUDIT_ADDON.".php";
            $classname = $conf->global->AUDITDIGITAL_AUDIT_ADDON;

            // Include file with class
            $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
            foreach ($dirmodels as $reldir) {
                $dir = dol_buildpath($reldir."core/modules/auditdigital/");

                // Load file with numbering class (if found)
                $mybool |= @include_once $dir.$file;
            }

            if ($mybool === false) {
                dol_print_error('', "Failed to include file ".$file);
                return '';
            }

            if (class_exists($classname)) {
                $obj = new $classname();
                $numref = $obj->getNextValue($this);

                if ($numref != '' && $numref != '-1') {
                    return $numref;
                } else {
                    $this->error = $obj->error;
                    //dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
                    return "";
                }
            } else {
                print $langs->trans("Error")." ".$langs->trans("ClassNotFound").' '.$classname;
                return "";
            }
        } else {
            print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
            return "";
        }
    }

    /**
     *  Create a document onto disk according to template module.
     *
     *  @param	    string		$modele			Force template to use ('' to not force)
     *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
     *  @param      int			$hidedetails    Hide details of lines
     *  @param      int			$hidedesc       Hide description
     *  @param      int			$hideref        Hide ref
     *  @param      null|array  $moreparams     Array to provide more information
     *  @return     int         				0 if KO, 1 if OK
     */
    public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $conf, $langs;

        $result = 0;
        $includedocgeneration = 1;

        $langs->load("auditdigital@auditdigital");

        if (!dol_strlen($modele)) {
            $modele = 'standard_audit';

            if (!empty($this->model_pdf)) {
                $modele = $this->model_pdf;
            } elseif (!empty($conf->global->AUDIT_ADDON_PDF)) {
                $modele = $conf->global->AUDIT_ADDON_PDF;
            }
        }

        $modelpath = "core/modules/auditdigital/doc/";

        if ($includedocgeneration && !empty($modele)) {
            $result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
        }

        return $result;
    }

    /**
     * Action executed by scheduler
     * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
     * Use public function doScheduledJob($param1, $param2, ...) to get parameters
     *
     * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
     */
    public function doScheduledJob()
    {
        global $conf, $langs;

        //$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

        $error = 0;
        $this->output = '';
        $this->error='';

        dol_syslog(__METHOD__, LOG_DEBUG);

        $now = dol_now();

        $this->db->begin();

        // ...

        $this->db->commit();

        return $error;
    }

    /**
     * Calculate scores based on responses
     *
     * @param array $responses Array of responses
     * @return array Array with calculated scores
     */
    public function calculateScores($responses)
    {
        $scores = array(
            'maturite' => 0,
            'cybersecurite' => 0,
            'cloud' => 0,
            'automatisation' => 0,
            'global' => 0
        );

        // Calculate scores based on responses
        // This is a simplified calculation - you should implement your own scoring logic
        $totalQuestions = count($responses);
        $totalScore = 0;

        foreach ($responses as $category => $categoryResponses) {
            $categoryScore = 0;
            $categoryQuestions = count($categoryResponses);

            foreach ($categoryResponses as $response) {
                if (isset($response['score'])) {
                    $categoryScore += $response['score'];
                }
            }

            if ($categoryQuestions > 0) {
                $scores[$category] = round(($categoryScore / ($categoryQuestions * 5)) * 100); // Assuming max score per question is 5
            }

            $totalScore += $categoryScore;
        }

        if ($totalQuestions > 0) {
            $scores['global'] = round(($totalScore / ($totalQuestions * 5)) * 100);
        }

        return $scores;
    }

    /**
     * Generate recommendations based on scores
     *
     * @param array $scores Array of scores
     * @param string $structureType Type of structure (tpe_pme or collectivite)
     * @return array Array of recommendations
     */
    public function generateRecommendations($scores, $structureType = 'tpe_pme')
    {
        $recommendations = array();

        // Load solutions library
        require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';
        $solutionLibrary = new SolutionLibrary($this->db);
        $solutions = $solutionLibrary->fetchAll('', '', 0, 0, array('active' => 1));

        // Generate recommendations based on low scores
        foreach ($scores as $category => $score) {
            if ($category == 'global') continue;

            if ($score < 60) { // Low score threshold
                // Find relevant solutions for this category
                $categorySolutions = array();
                foreach ($solutions as $solution) {
                    if (strpos($solution->category, $category) !== false) {
                        $targetAudience = explode(',', $solution->target_audience);
                        if (in_array($structureType, $targetAudience) || in_array('all', $targetAudience)) {
                            $categorySolutions[] = $solution;
                        }
                    }
                }

                // Sort by priority
                usort($categorySolutions, function($a, $b) {
                    return $b->priority - $a->priority;
                });

                // Take top 3 solutions for this category
                $recommendations[$category] = array_slice($categorySolutions, 0, 3);
            }
        }

        return $recommendations;
    }

    /**
     * Calculate ROI potential based on suggested improvements
     *
     * @param array $recommendations Array of recommendations
     * @return array ROI analysis with total ROI and breakdown by category
     */
    public function calculateROI($recommendations = array())
    {
        // ROI data based on industry studies and best practices
        $improvementROI = array(
            'cloud_migration' => array(
                'cost' => 50000,
                'annual_savings' => 15000,
                'payback_period' => 36, // months
                'risk_reduction' => 80 // percentage
            ),
            'automation' => array(
                'cost' => 30000,
                'annual_savings' => 25000,
                'payback_period' => 14, // months
                'productivity_gain' => 40 // percentage
            ),
            'security_upgrade' => array(
                'cost' => 20000,
                'annual_savings' => 8000,
                'payback_period' => 30, // months
                'risk_reduction' => 90 // percentage
            ),
            'digital_transformation' => array(
                'cost' => 40000,
                'annual_savings' => 18000,
                'payback_period' => 26, // months
                'revenue_increase' => 15 // percentage
            ),
            'infrastructure_modernization' => array(
                'cost' => 35000,
                'annual_savings' => 12000,
                'payback_period' => 35, // months
                'efficiency_gain' => 25 // percentage
            )
        );

        $roiAnalysis = array(
            'total_investment' => 0,
            'total_annual_savings' => 0,
            'average_payback_period' => 0,
            'three_year_roi' => 0,
            'breakdown' => array(),
            'quick_wins' => array(),
            'medium_term' => array(),
            'long_term' => array()
        );

        $totalInvestment = 0;
        $totalAnnualSavings = 0;
        $weightedPaybackPeriod = 0;

        // If no recommendations provided, use current audit scores to estimate
        if (empty($recommendations)) {
            $scores = $this->calculateScores();
            
            // Estimate improvements needed based on scores
            if ($scores['security'] < 60) {
                $recommendations['security'] = array('type' => 'security_upgrade');
            }
            if ($scores['cloud'] < 50) {
                $recommendations['cloud'] = array('type' => 'cloud_migration');
            }
            if ($scores['automation'] < 40) {
                $recommendations['automation'] = array('type' => 'automation');
            }
            if ($scores['digital'] < 70) {
                $recommendations['digital'] = array('type' => 'digital_transformation');
            }
        }

        foreach ($recommendations as $category => $recommendation) {
            $type = is_array($recommendation) ? $recommendation['type'] : $recommendation;
            
            if (isset($improvementROI[$type])) {
                $roi = $improvementROI[$type];
                
                $investment = $roi['cost'];
                $annualSavings = $roi['annual_savings'];
                $paybackPeriod = $roi['payback_period'];
                
                // Calculate 3-year ROI
                $threeYearSavings = $annualSavings * 3;
                $threeYearROI = (($threeYearSavings - $investment) / $investment) * 100;
                
                $roiAnalysis['breakdown'][$category] = array(
                    'type' => $type,
                    'investment' => $investment,
                    'annual_savings' => $annualSavings,
                    'payback_period' => $paybackPeriod,
                    'three_year_roi' => round($threeYearROI, 2),
                    'priority' => $this->calculatePriority($threeYearROI, $paybackPeriod, $investment)
                );
                
                // Categorize by timeline
                if ($paybackPeriod <= 12) {
                    $roiAnalysis['quick_wins'][] = $category;
                } elseif ($paybackPeriod <= 24) {
                    $roiAnalysis['medium_term'][] = $category;
                } else {
                    $roiAnalysis['long_term'][] = $category;
                }
                
                $totalInvestment += $investment;
                $totalAnnualSavings += $annualSavings;
                $weightedPaybackPeriod += $paybackPeriod * $investment;
            }
        }

        $roiAnalysis['total_investment'] = $totalInvestment;
        $roiAnalysis['total_annual_savings'] = $totalAnnualSavings;
        $roiAnalysis['average_payback_period'] = $totalInvestment > 0 ? round($weightedPaybackPeriod / $totalInvestment, 1) : 0;
        
        // Calculate overall 3-year ROI
        if ($totalInvestment > 0) {
            $totalThreeYearSavings = $totalAnnualSavings * 3;
            $roiAnalysis['three_year_roi'] = round((($totalThreeYearSavings - $totalInvestment) / $totalInvestment) * 100, 2);
        }

        return $roiAnalysis;
    }

    /**
     * Calculate priority score for a recommendation
     *
     * @param float $roi ROI percentage
     * @param int $paybackPeriod Payback period in months
     * @param int $investment Investment amount
     * @return int Priority score (0-100)
     */
    private function calculatePriority($roi, $paybackPeriod, $investment)
    {
        // Priority calculation based on ROI, payback period, and investment size
        $roiScore = min($roi / 2, 50); // Max 50 points for ROI
        $paybackScore = max(0, 30 - ($paybackPeriod / 2)); // Max 30 points for quick payback
        $investmentScore = max(0, 20 - ($investment / 5000)); // Max 20 points for lower investment
        
        return round($roiScore + $paybackScore + $investmentScore);
    }

    /**
     * Generate implementation roadmap based on priorities and dependencies
     *
     * @param array $recommendations Array of recommendations
     * @return array Roadmap with phases and timelines
     */
    public function generateRoadmap($recommendations = array())
    {
        $roadmap = array(
            'phase1_quick_wins' => array(
                'title' => 'Phase 1 : Actions rapides (1-3 mois)',
                'description' => 'Améliorations à impact rapide et ROI élevé',
                'duration' => '1-3 mois',
                'actions' => array(),
                'total_investment' => 0,
                'expected_savings' => 0
            ),
            'phase2_medium_term' => array(
                'title' => 'Phase 2 : Projets structurants (3-12 mois)',
                'description' => 'Transformations importantes avec impact significatif',
                'duration' => '3-12 mois',
                'actions' => array(),
                'total_investment' => 0,
                'expected_savings' => 0
            ),
            'phase3_long_term' => array(
                'title' => 'Phase 3 : Vision long terme (12+ mois)',
                'description' => 'Innovations et optimisations avancées',
                'duration' => '12+ mois',
                'actions' => array(),
                'total_investment' => 0,
                'expected_savings' => 0
            )
        );

        // Get ROI analysis to determine priorities
        $roiAnalysis = $this->calculateROI($recommendations);

        // Categorize actions by priority and effort
        foreach ($roiAnalysis['breakdown'] as $category => $analysis) {
            $action = array(
                'category' => $category,
                'type' => $analysis['type'],
                'investment' => $analysis['investment'],
                'annual_savings' => $analysis['annual_savings'],
                'payback_period' => $analysis['payback_period'],
                'roi' => $analysis['three_year_roi'],
                'priority' => $analysis['priority']
            );

            // Determine phase based on priority and payback period
            if ($analysis['priority'] > 80 && $analysis['payback_period'] <= 6) {
                $roadmap['phase1_quick_wins']['actions'][] = $action;
                $roadmap['phase1_quick_wins']['total_investment'] += $analysis['investment'];
                $roadmap['phase1_quick_wins']['expected_savings'] += $analysis['annual_savings'];
            } elseif ($analysis['priority'] > 50 && $analysis['payback_period'] <= 18) {
                $roadmap['phase2_medium_term']['actions'][] = $action;
                $roadmap['phase2_medium_term']['total_investment'] += $analysis['investment'];
                $roadmap['phase2_medium_term']['expected_savings'] += $analysis['annual_savings'];
            } else {
                $roadmap['phase3_long_term']['actions'][] = $action;
                $roadmap['phase3_long_term']['total_investment'] += $analysis['investment'];
                $roadmap['phase3_long_term']['expected_savings'] += $analysis['annual_savings'];
            }
        }

        // Sort actions within each phase by priority
        foreach ($roadmap as &$phase) {
            if (!empty($phase['actions'])) {
                usort($phase['actions'], function($a, $b) {
                    return $b['priority'] - $a['priority'];
                });
            }
        }

        return $roadmap;
    }

    /**
     * Generate executive summary for the audit
     *
     * @return array Executive summary with key insights
     */
    public function generateExecutiveSummary()
    {
        $scores = $this->calculateScores();
        $roiAnalysis = $this->calculateROI();
        $roadmap = $this->generateRoadmap();

        $summary = array(
            'global_score' => $scores['global'],
            'maturity_level' => $this->getMaturityLevel($scores['global']),
            'key_strengths' => array(),
            'critical_areas' => array(),
            'top_priorities' => array(),
            'investment_summary' => array(
                'total_investment' => $roiAnalysis['total_investment'],
                'annual_savings' => $roiAnalysis['total_annual_savings'],
                'payback_period' => $roiAnalysis['average_payback_period'],
                'three_year_roi' => $roiAnalysis['three_year_roi']
            ),
            'quick_wins' => count($roiAnalysis['quick_wins']),
            'implementation_timeline' => $this->getImplementationTimeline($roadmap)
        );

        // Identify strengths (scores > 70)
        foreach ($scores as $category => $score) {
            if ($category != 'global' && $score >= 70) {
                $summary['key_strengths'][] = array(
                    'category' => $category,
                    'score' => $score,
                    'label' => $this->getCategoryLabel($category)
                );
            }
        }

        // Identify critical areas (scores < 50)
        foreach ($scores as $category => $score) {
            if ($category != 'global' && $score < 50) {
                $summary['critical_areas'][] = array(
                    'category' => $category,
                    'score' => $score,
                    'label' => $this->getCategoryLabel($category),
                    'urgency' => $score < 30 ? 'high' : 'medium'
                );
            }
        }

        // Get top 3 priorities from roadmap
        $allActions = array();
        foreach ($roadmap as $phase) {
            $allActions = array_merge($allActions, $phase['actions']);
        }
        
        usort($allActions, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        $summary['top_priorities'] = array_slice($allActions, 0, 3);

        return $summary;
    }

    /**
     * Get maturity level based on global score
     *
     * @param int $score Global score
     * @return array Maturity level info
     */
    private function getMaturityLevel($score)
    {
        if ($score >= 80) {
            return array(
                'level' => 'expert',
                'label' => 'Expert',
                'description' => 'Maturité digitale excellente',
                'color' => '#28a745'
            );
        } elseif ($score >= 60) {
            return array(
                'level' => 'advanced',
                'label' => 'Avancé',
                'description' => 'Bonne maturité digitale',
                'color' => '#17a2b8'
            );
        } elseif ($score >= 40) {
            return array(
                'level' => 'intermediate',
                'label' => 'Intermédiaire',
                'description' => 'Maturité digitale moyenne',
                'color' => '#ffc107'
            );
        } elseif ($score >= 20) {
            return array(
                'level' => 'beginner',
                'label' => 'Débutant',
                'description' => 'Maturité digitale faible',
                'color' => '#fd7e14'
            );
        } else {
            return array(
                'level' => 'critical',
                'label' => 'Critique',
                'description' => 'Maturité digitale très faible',
                'color' => '#dc3545'
            );
        }
    }

    /**
     * Get category label in French
     *
     * @param string $category Category key
     * @return string French label
     */
    private function getCategoryLabel($category)
    {
        $labels = array(
            'digital' => 'Maturité Digitale',
            'security' => 'Cybersécurité',
            'cloud' => 'Cloud Computing',
            'automation' => 'Automatisation',
            'infrastructure' => 'Infrastructure'
        );
        
        return isset($labels[$category]) ? $labels[$category] : ucfirst($category);
    }

    /**
     * Get implementation timeline summary
     *
     * @param array $roadmap Roadmap data
     * @return array Timeline summary
     */
    private function getImplementationTimeline($roadmap)
    {
        $timeline = array();
        
        foreach ($roadmap as $phaseKey => $phase) {
            if (!empty($phase['actions'])) {
                $timeline[] = array(
                    'phase' => $phase['title'],
                    'duration' => $phase['duration'],
                    'actions_count' => count($phase['actions']),
                    'investment' => $phase['total_investment'],
                    'savings' => $phase['expected_savings']
                );
            }
        }
        
        return $timeline;
    }

    /**
     * Export audit data for external analysis
     *
     * @param string $format Export format (json, csv, xml)
     * @return string|false Exported data or false on error
     */
    public function exportAuditData($format = 'json')
    {
        $data = array(
            'audit_info' => array(
                'id' => $this->id,
                'ref' => $this->ref,
                'date_creation' => $this->date_creation,
                'fk_soc' => $this->fk_soc,
                'structure_type' => $this->structure_type,
                'status' => $this->status
            ),
            'scores' => $this->calculateScores(),
            'executive_summary' => $this->generateExecutiveSummary(),
            'roi_analysis' => $this->calculateROI(),
            'roadmap' => $this->generateRoadmap(),
            'export_date' => date('Y-m-d H:i:s')
        );

        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
            case 'csv':
                // Simplified CSV export for scores
                $csv = "Category,Score,Level\n";
                foreach ($data['scores'] as $category => $score) {
                    $level = $this->getMaturityLevel($score);
                    $csv .= "$category,$score,{$level['label']}\n";
                }
                return $csv;
                
            case 'xml':
                $xml = new SimpleXMLElement('<audit_data/>');
                $this->arrayToXml($data, $xml);
                return $xml->asXML();
                
            default:
                $this->error = "Format d'export non supporté: $format";
                return false;
        }
    }

    /**
     * Convert array to XML
     *
     * @param array $data Data array
     * @param SimpleXMLElement $xml XML element
     */
    private function arrayToXml($data, &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}