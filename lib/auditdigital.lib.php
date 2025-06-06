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
 * \file       lib/auditdigital.lib.php
 * \ingroup    auditdigital
 * \brief      Library files with common functions for AuditDigital
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function auditdigitalAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("auditdigital@auditdigital");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/auditdigital/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/auditdigital/admin/solutions.php", 1);
    $head[$h][1] = $langs->trans("SolutionLibrary");
    $head[$h][2] = 'solutions';
    $h++;

    $head[$h][0] = dol_buildpath("/auditdigital/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@auditdigital:/auditdigital/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@auditdigital:/auditdigital/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'auditdigital@auditdigital');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'auditdigital@auditdigital', 'remove');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	Audit	$object		Object company shown
 * @return 	array				Array of tabs
 */
function audit_prepare_head($object)
{
    global $db, $langs, $conf, $user;

    $langs->load("auditdigital@auditdigital");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/auditdigital/audit_card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("AuditCard");
    $head[$h][2] = 'card';
    $h++;

    if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
        $nbNote = 0;
        if (!empty($object->note_private)) {
            $nbNote++;
        }
        if (!empty($object->note_public)) {
            $nbNote++;
        }
        $head[$h][0] = dol_buildpath('/auditdigital/audit_note.php', 1).'?id='.$object->id;
        $head[$h][1] = $langs->trans('Notes');
        if ($nbNote > 0) {
            $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
        }
        $head[$h][2] = 'note';
        $h++;
    }

    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    $upload_dir = $conf->auditdigital->dir_output."/audit/".dol_sanitizeFileName($object->ref);
    $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    $nbLinks = Link::count($db, $object->element, $object->id);
    $head[$h][0] = dol_buildpath('/auditdigital/audit_document.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans('Documents');
    if (($nbFiles + $nbLinks) > 0) {
        $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
    }
    $head[$h][2] = 'document';
    $h++;

    $head[$h][0] = dol_buildpath('/auditdigital/audit_agenda.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Events");
    if (isModEnabled('agenda') && ($user->rights->agenda->myactions->read || $user->rights->agenda->allactions->read)) {
        $nbEvent = 0;
        // Enable caching of thirdparty count actioncomm
        require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
        $cachekey = 'count_events_audit_'.$object->id;
        $dataretrieved = dol_getcache($cachekey);
        if (!is_null($dataretrieved)) {
            $nbEvent = $dataretrieved;
        } else {
            $sql = "SELECT COUNT(a.id) as nb";
            $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
            $sql .= " WHERE a.fk_element = ".((int) $object->id);
            $sql .= " AND a.elementtype = 'audit@auditdigital'";
            $resql = $db->query($sql);
            if ($resql) {
                $obj = $db->fetch_object($resql);
                $nbEvent = $obj->nb;
            } else {
                dol_syslog('Failed to count actioncomm '.$db->lasterror(), LOG_ERR);
            }
            dol_setcache($cachekey, $nbEvent, 120);		// If setting cache fails, this is not a problem, so we do not test result.
        }

        $head[$h][1] .= '/';
        $head[$h][1] .= $langs->trans("Agenda");
        if ($nbEvent > 0) {
            $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbEvent.'</span>';
        }
    }
    $head[$h][2] = 'agenda';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@auditdigital:/auditdigital/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@auditdigital:/auditdigital/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'audit@auditdigital');

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'audit@auditdigital', 'remove');

    return $head;
}

/**
 * Get score class based on score value
 *
 * @param int $score Score value (0-100)
 * @return string CSS class name
 */
function getScoreClass($score)
{
    if ($score >= 80) {
        return 'audit-score-excellent';
    } elseif ($score >= 60) {
        return 'audit-score-good';
    } elseif ($score >= 40) {
        return 'audit-score-average';
    } elseif ($score >= 20) {
        return 'audit-score-poor';
    } else {
        return 'audit-score-critical';
    }
}

/**
 * Get score label based on score value
 *
 * @param int $score Score value (0-100)
 * @return string Score label
 */
function getScoreLabel($score)
{
    global $langs;
    
    if ($score >= 80) {
        return $langs->trans('ScoreExcellent');
    } elseif ($score >= 60) {
        return $langs->trans('ScoreGood');
    } elseif ($score >= 40) {
        return $langs->trans('ScoreAverage');
    } elseif ($score >= 20) {
        return $langs->trans('ScorePoor');
    } else {
        return $langs->trans('ScoreCritical');
    }
}

/**
 * Generate radar chart data for audit scores
 *
 * @param array $scores Array of scores by category
 * @return array Chart data
 */
function generateRadarChartData($scores)
{
    global $langs;
    
    $chartData = array(
        'labels' => array(
            $langs->trans('MaturityScore'),
            $langs->trans('CybersecurityScore'),
            $langs->trans('CloudScore'),
            $langs->trans('AutomationScore')
        ),
        'datasets' => array(
            array(
                'label' => $langs->trans('Scores'),
                'data' => array(
                    $scores['maturite'] ?? 0,
                    $scores['cybersecurite'] ?? 0,
                    $scores['cloud'] ?? 0,
                    $scores['automatisation'] ?? 0
                ),
                'backgroundColor' => 'rgba(0, 102, 204, 0.2)',
                'borderColor' => 'rgba(0, 102, 204, 1)',
                'borderWidth' => 2,
                'pointBackgroundColor' => 'rgba(0, 102, 204, 1)',
                'pointBorderColor' => '#fff',
                'pointHoverBackgroundColor' => '#fff',
                'pointHoverBorderColor' => 'rgba(0, 102, 204, 1)'
            )
        )
    );
    
    return $chartData;
}

/**
 * Format price range for display
 *
 * @param string $priceRange Price range code
 * @return string Formatted price range
 */
function formatPriceRange($priceRange)
{
    global $langs;
    
    $ranges = array(
        '5k' => $langs->trans('Under5k'),
        '10k' => $langs->trans('From5to10k'),
        '15k' => $langs->trans('From10to15k'),
        '20k' => $langs->trans('From15to20k'),
        '20k+' => $langs->trans('Over20k')
    );
    
    return isset($ranges[$priceRange]) ? $ranges[$priceRange] : $priceRange;
}

/**
 * Format target audience for display
 *
 * @param string $targetAudience Target audience codes (comma separated)
 * @return string Formatted target audience
 */
function formatTargetAudience($targetAudience)
{
    global $langs;
    
    $audiences = array(
        'tpe' => $langs->trans('TPE'),
        'pme' => $langs->trans('PME'),
        'collectivite' => $langs->trans('Collectivity'),
        'all' => $langs->trans('All')
    );
    
    $audienceArray = explode(',', $targetAudience);
    $formattedAudiences = array();
    
    foreach ($audienceArray as $audience) {
        $audience = trim($audience);
        if (isset($audiences[$audience])) {
            $formattedAudiences[] = $audiences[$audience];
        }
    }
    
    return implode(', ', $formattedAudiences);
}

/**
 * Get priority badge HTML
 *
 * @param int $priority Priority level
 * @return string HTML badge
 */
function getPriorityBadge($priority)
{
    global $langs;
    
    if ($priority >= 9) {
        $class = 'audit-priority-high';
        $label = $langs->trans('High');
    } elseif ($priority >= 6) {
        $class = 'audit-priority-medium';
        $label = $langs->trans('Medium');
    } else {
        $class = 'audit-priority-low';
        $label = $langs->trans('Low');
    }
    
    return '<span class="audit-priority-badge '.$class.'">'.$label.'</span>';
}

/**
 * Generate audit reference
 *
 * @param string $structureType Structure type (tpe_pme or collectivite)
 * @param int $year Year
 * @param int $sequence Sequence number
 * @return string Generated reference
 */
function generateAuditRef($structureType, $year = null, $sequence = 1)
{
    if ($year === null) {
        $year = date('Y');
    }
    
    $prefix = ($structureType === 'collectivite') ? 'AUD-COLL' : 'AUD-ENT';
    
    return sprintf('%s-%04d-%04d', $prefix, $year, $sequence);
}

/**
 * Validate email address
 *
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize filename for audit documents
 *
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitizeAuditFilename($filename)
{
    // Remove special characters and spaces
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    
    // Remove leading/trailing underscores
    $filename = trim($filename, '_');
    
    return $filename;
}

/**
 * Get audit status icon
 *
 * @param int $status Status code
 * @return string HTML icon
 */
function getAuditStatusIcon($status)
{
    switch ($status) {
        case 0: // Draft
            return '<i class="fa fa-edit" style="color: #666;"></i>';
        case 1: // Validated
            return '<i class="fa fa-check-circle" style="color: #28a745;"></i>';
        case 2: // Sent
            return '<i class="fa fa-paper-plane" style="color: #007bff;"></i>';
        default:
            return '<i class="fa fa-question-circle" style="color: #666;"></i>';
    }
}

/**
 * Calculate ROI display value
 *
 * @param int $roiPercentage ROI percentage
 * @param int $roiMonths ROI months
 * @return string Formatted ROI display
 */
function formatROI($roiPercentage, $roiMonths)
{
    global $langs;
    
    if ($roiPercentage > 0 && $roiMonths > 0) {
        return sprintf('%d%% en %d mois', $roiPercentage, $roiMonths);
    } elseif ($roiPercentage > 0) {
        return $roiPercentage.'%';
    } else {
        return $langs->trans('NotApplicable');
    }
}

/**
 * Get implementation time display
 *
 * @param int $days Number of days
 * @return string Formatted time display
 */
function formatImplementationTime($days)
{
    global $langs;
    
    if ($days <= 0) {
        return $langs->trans('NotSpecified');
    } elseif ($days == 1) {
        return '1 '.$langs->trans('Day');
    } elseif ($days < 7) {
        return $days.' '.$langs->trans('Days');
    } elseif ($days < 30) {
        $weeks = round($days / 7);
        return $weeks.' '.($weeks == 1 ? $langs->trans('Week') : $langs->trans('Weeks'));
    } else {
        $months = round($days / 30);
        return $months.' '.($months == 1 ? $langs->trans('Month') : $langs->trans('Months'));
    }
}

/**
 * Check if user can access audit
 *
 * @param User $user Current user
 * @param Audit $audit Audit object
 * @return bool True if user can access, false otherwise
 */
function canUserAccessAudit($user, $audit)
{
    // Admin can access all audits
    if ($user->admin) {
        return true;
    }
    
    // Check if user has read permission
    if (!$user->rights->auditdigital->audit->read) {
        return false;
    }
    
    // Check if user is the creator
    if ($audit->fk_user_creat == $user->id) {
        return true;
    }
    
    // Check if user belongs to the same company (for external users)
    if ($user->socid > 0 && $audit->fk_soc == $user->socid) {
        return true;
    }
    
    // For internal users, check if they have access to the company
    if ($user->socid == 0) {
        return true;
    }
    
    return false;
}

/**
 * Log audit action
 *
 * @param string $action Action performed
 * @param int $auditId Audit ID
 * @param int $userId User ID
 * @param string $details Additional details
 * @return void
 */
function logAuditAction($action, $auditId, $userId, $details = '')
{
    global $db;
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."events (";
    $sql .= "type, label, dateevent, fk_user, elementtype, fk_element, note";
    $sql .= ") VALUES (";
    $sql .= "'audit_action', '".$db->escape($action)."', '".$db->idate(dol_now())."', ";
    $sql .= ((int) $userId).", 'audit@auditdigital', ".((int) $auditId).", '".$db->escape($details)."'";
    $sql .= ")";
    
    $db->query($sql);
}
?>