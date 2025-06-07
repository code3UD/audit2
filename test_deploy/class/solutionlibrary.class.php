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
 * \file        class/solutionlibrary.class.php
 * \ingroup     auditdigital
 * \brief       This file is a CRUD class file for SolutionLibrary (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for SolutionLibrary
 */
class SolutionLibrary extends CommonObject
{
    /**
     * @var string ID of module.
     */
    public $module = 'auditdigital';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'solutionlibrary';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'auditdigital_solutions';

    /**
     * @var int  Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string String with name of icon for solution. Must be the part after the 'object_' into object_solution.png
     */
    public $picto = 'solution@auditdigital';

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields=array(
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
        'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'validate'=>'1', 'comment'=>"Reference of solution"),
        'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth300', 'validate'=>'1', 'showoncombobox'=>'1',),
        'category' => array('type'=>'varchar(100)', 'label'=>'Category', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('maturite_numerique'=>'Maturité Numérique', 'cybersecurite'=>'Cybersécurité', 'cloud'=>'Cloud', 'automatisation'=>'Automatisation')),
        'sub_category' => array('type'=>'varchar(100)', 'label'=>'SubCategory', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1),
        'solution_type' => array('type'=>'varchar(100)', 'label'=>'SolutionType', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>1),
        'target_audience' => array('type'=>'varchar(100)', 'label'=>'TargetAudience', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>1, 'arrayofkeyval'=>array('tpe'=>'TPE', 'pme'=>'PME', 'collectivite'=>'Collectivité', 'all'=>'Tous')),
        'price_range' => array('type'=>'varchar(50)', 'label'=>'PriceRange', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>1, 'arrayofkeyval'=>array('5k'=>'< 5k€', '10k'=>'5-10k€', '15k'=>'10-15k€', '20k'=>'15-20k€', '20k+'=>'> 20k€')),
        'implementation_time' => array('type'=>'integer', 'label'=>'ImplementationTime', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>1, 'isameasure'=>'1'),
        'priority' => array('type'=>'integer', 'label'=>'Priority', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1'),
        'roi_percentage' => array('type'=>'integer', 'label'=>'ROIPercentage', 'enabled'=>'1', 'position'=>110, 'notnull'=>0, 'visible'=>1, 'isameasure'=>'1'),
        'roi_months' => array('type'=>'integer', 'label'=>'ROIMonths', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>1, 'isameasure'=>'1'),
        'json_features' => array('type'=>'text', 'label'=>'Features', 'enabled'=>'1', 'position'=>200, 'notnull'=>0, 'visible'=>0),
        'json_benefits' => array('type'=>'text', 'label'=>'Benefits', 'enabled'=>'1', 'position'=>210, 'notnull'=>0, 'visible'=>0),
        'json_requirements' => array('type'=>'text', 'label'=>'Requirements', 'enabled'=>'1', 'position'=>220, 'notnull'=>0, 'visible'=>0),
        'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>300, 'notnull'=>0, 'visible'=>1),
        'active' => array('type'=>'integer', 'label'=>'Active', 'enabled'=>'1', 'position'=>400, 'notnull'=>0, 'visible'=>1, 'default'=>'1', 'arrayofkeyval'=>array('0'=>'No', '1'=>'Yes')),
        'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>0, 'default'=>'1', 'index'=>1),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
    );

    public $rowid;
    public $ref;
    public $label;
    public $category;
    public $sub_category;
    public $solution_type;
    public $target_audience;
    public $price_range;
    public $implementation_time;
    public $priority;
    public $roi_percentage;
    public $roi_months;
    public $json_features;
    public $json_benefits;
    public $json_requirements;
    public $description;
    public $active;
    public $date_creation;
    public $entity;
    public $tms;

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
        return $this->createCommon($user, $notrigger);
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
    }

    /**
     * Get features as array
     *
     * @return array Array of features
     */
    public function getFeatures()
    {
        if (!empty($this->json_features)) {
            return json_decode($this->json_features, true);
        }
        return array();
    }

    /**
     * Get benefits as array
     *
     * @return array Array of benefits
     */
    public function getBenefits()
    {
        if (!empty($this->json_benefits)) {
            return json_decode($this->json_benefits, true);
        }
        return array();
    }

    /**
     * Get requirements as array
     *
     * @return array Array of requirements
     */
    public function getRequirements()
    {
        if (!empty($this->json_requirements)) {
            return json_decode($this->json_requirements, true);
        }
        return array();
    }

    /**
     * Set features from array
     *
     * @param array $features Array of features
     * @return void
     */
    public function setFeatures($features)
    {
        $this->json_features = json_encode($features);
    }

    /**
     * Set benefits from array
     *
     * @param array $benefits Array of benefits
     * @return void
     */
    public function setBenefits($benefits)
    {
        $this->json_benefits = json_encode($benefits);
    }

    /**
     * Set requirements from array
     *
     * @param array $requirements Array of requirements
     * @return void
     */
    public function setRequirements($requirements)
    {
        $this->json_requirements = json_encode($requirements);
    }

    /**
     * Get solutions by category and target audience
     *
     * @param string $category Category to filter
     * @param string $targetAudience Target audience to filter
     * @param int $limit Limit number of results
     * @return array Array of solutions
     */
    public function getSolutionsByCategory($category, $targetAudience = '', $limit = 0)
    {
        $filter = array('category' => $category, 'active' => 1);
        
        if (!empty($targetAudience)) {
            $filter['customsql'] = "(target_audience LIKE '%".$this->db->escape($targetAudience)."%' OR target_audience LIKE '%all%')";
        }

        return $this->fetchAll('priority', 'DESC', $limit, 0, $filter);
    }

    /**
     * Get top priority solutions for a target audience
     *
     * @param string $targetAudience Target audience
     * @param int $limit Limit number of results
     * @return array Array of solutions
     */
    public function getTopSolutions($targetAudience = '', $limit = 5)
    {
        $filter = array('active' => 1);
        
        if (!empty($targetAudience)) {
            $filter['customsql'] = "(target_audience LIKE '%".$this->db->escape($targetAudience)."%' OR target_audience LIKE '%all%')";
        }

        return $this->fetchAll('priority', 'DESC', $limit, 0, $filter);
    }

    /**
     * Load solutions from JSON file
     *
     * @param string $jsonFile Path to JSON file
     * @return int Number of solutions loaded, <0 if error
     */
    public function loadFromJson($jsonFile)
    {
        global $user;

        if (!file_exists($jsonFile)) {
            $this->error = 'JSON file not found: '.$jsonFile;
            return -1;
        }

        $jsonContent = file_get_contents($jsonFile);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = 'Invalid JSON format: '.json_last_error_msg();
            return -2;
        }

        $loaded = 0;
        $this->db->begin();

        if (isset($data['solutions'])) {
            foreach ($data['solutions'] as $categoryKey => $categoryData) {
                foreach ($categoryData as $solutionKey => $solutionData) {
                    $solution = new self($this->db);
                    
                    // Map JSON data to object properties
                    $solution->ref = $solutionData['ref'];
                    $solution->label = $solutionData['label'];
                    $solution->category = $solutionData['category'];
                    $solution->sub_category = $solutionData['sub_category'];
                    $solution->solution_type = $solutionData['solution_type'] ?? 'service';
                    $solution->target_audience = implode(',', $solutionData['target_audience']);
                    $solution->price_range = $solutionData['price_range'];
                    $solution->implementation_time = $solutionData['implementation_time'];
                    $solution->priority = $solutionData['priority'];
                    $solution->roi_percentage = $solutionData['roi_percentage'];
                    $solution->roi_months = $solutionData['roi_months'];
                    $solution->description = $solutionData['description'] ?? '';
                    $solution->date_creation = dol_now();
                    $solution->entity = 1;
                    
                    // Set JSON fields
                    $solution->setFeatures($solutionData['features']);
                    $solution->setBenefits($solutionData['benefits']);
                    $solution->setRequirements($solutionData['requirements']);

                    // Check if solution already exists
                    $existing = $this->fetchAll('', '', 0, 0, array('ref' => $solution->ref));
                    if (empty($existing)) {
                        $result = $solution->create($user);
                        if ($result > 0) {
                            $loaded++;
                        } else {
                            $this->error = $solution->error;
                            $this->db->rollback();
                            return -3;
                        }
                    }
                }
            }
        }

        $this->db->commit();
        return $loaded;
    }
}