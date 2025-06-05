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
 * \file       install.php
 * \ingroup    auditdigital
 * \brief      Installation script for AuditDigital module
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
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';

// Load translation files
$langs->loadLangs(array("admin", "auditdigital@auditdigital"));

// Security check
if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

$error = 0;
$message = '';

if ($action == 'install') {
    $db->begin();
    
    try {
        // 1. Activate required modules first
        $requiredModules = array(
            'MAIN_MODULE_PROJET' => '1',  // Projects module
            'MAIN_MODULE_SOCIETE' => '1'  // Third parties module
        );
        
        foreach ($requiredModules as $module => $value) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name, value, type, entity, visible) ";
            $sql .= "VALUES ('".$db->escape($module)."', '".$db->escape($value)."', 'chaine', 1, 0) ";
            $sql .= "ON DUPLICATE KEY UPDATE value = '".$db->escape($value)."'";
            
            $result = $db->query($sql);
            if (!$result) {
                throw new Exception('Error activating module: '.$module);
            }
        }
        
        // 2. Set default configuration
        $configs = array(
            'AUDITDIGITAL_AUDIT_MASK' => 'AUD{yyyy}{mm}{dd}-{####}',
            'AUDIT_ADDON_PDF' => 'audit_tpe',
            'AUDITDIGITAL_MYPARAM1' => 'default_value_1',
            'AUDITDIGITAL_MYPARAM2' => 'default_value_2',
            'AUDITDIGITAL_MYPARAM3' => '1'
        );
        
        foreach ($configs as $name => $value) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name, value, type, entity, visible) ";
            $sql .= "VALUES ('".$db->escape($name)."', '".$db->escape($value)."', 'chaine', 1, 0) ";
            $sql .= "ON DUPLICATE KEY UPDATE value = '".$db->escape($value)."'";
            
            $result = $db->query($sql);
            if (!$result) {
                throw new Exception('Error setting configuration: '.$name);
            }
        }
        
        // 2. Load default solutions
        $solutionLibrary = new SolutionLibrary($db);
        $jsonFile = DOL_DOCUMENT_ROOT.'/custom/auditdigital/data/solutions.json';
        
        if (file_exists($jsonFile)) {
            $result = $solutionLibrary->loadFromJson($jsonFile);
            if ($result < 0) {
                throw new Exception('Error loading solutions: '.$solutionLibrary->error);
            }
            $message .= "✓ ".$result." solutions loaded successfully<br>";
        }
        
        // 3. Create directories
        $dirs = array(
            $conf->auditdigital->dir_output,
            $conf->auditdigital->dir_output.'/audit',
            $conf->auditdigital->dir_output.'/temp'
        );
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                if (!dol_mkdir($dir)) {
                    throw new Exception('Cannot create directory: '.$dir);
                }
            }
        }
        
        $db->commit();
        $message .= "✓ Module AuditDigital installed successfully!<br>";
        $message .= "✓ Default configuration applied<br>";
        $message .= "✓ Directories created<br>";
        $message .= "<br><strong>Next steps:</strong><br>";
        $message .= "1. Go to Configuration > Modules and activate AuditDigital<br>";
        $message .= "2. Configure user permissions<br>";
        $message .= "3. Start creating audits!<br>";
        
    } catch (Exception $e) {
        $db->rollback();
        $error++;
        $message = "❌ Installation failed: ".$e->getMessage();
    }
}

/*
 * View
 */

$title = "AuditDigital Module Installation";
llxHeader('', $title, '');

print load_fiche_titre($title, '', 'title_setup');

if ($error) {
    print '<div class="error">'.$message.'</div>';
} elseif ($message) {
    print '<div class="ok">'.$message.'</div>';
}

if (empty($action)) {
    print '<div class="info">';
    print '<h3>Welcome to AuditDigital Module Installation</h3>';
    print '<p>This script will help you install and configure the AuditDigital module for Dolibarr.</p>';
    print '<p><strong>What will be installed:</strong></p>';
    print '<ul>';
    print '<li>Default module configuration</li>';
    print '<li>Solutions library with predefined solutions</li>';
    print '<li>Required directories</li>';
    print '<li>Default PDF templates</li>';
    print '</ul>';
    print '<p><strong>Prerequisites:</strong></p>';
    print '<ul>';
    print '<li>Dolibarr 14.0+ installed and configured</li>';
    print '<li>PHP 7.4+ with required extensions</li>';
    print '<li>MySQL/MariaDB database</li>';
    print '<li>Write permissions on Dolibarr directories</li>';
    print '</ul>';
    print '</div>';
    
    print '<div class="center">';
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="install">';
    print '<input type="submit" class="button" value="Install AuditDigital Module">';
    print '</form>';
    print '</div>';
    
    // System check
    print '<br><h3>System Check</h3>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Component</td>';
    print '<td>Status</td>';
    print '<td>Details</td>';
    print '</tr>';
    
    // PHP Version
    $phpversion = phpversion();
    $phpok = version_compare($phpversion, '7.4.0', '>=');
    print '<tr class="oddeven">';
    print '<td>PHP Version</td>';
    print '<td>'.($phpok ? '✓' : '❌').'</td>';
    print '<td>'.$phpversion.($phpok ? ' (OK)' : ' (Requires 7.4+)').'</td>';
    print '</tr>';
    
    // Dolibarr Version
    $dolversion = DOL_VERSION;
    $dolok = version_compare($dolversion, '14.0.0', '>=');
    print '<tr class="oddeven">';
    print '<td>Dolibarr Version</td>';
    print '<td>'.($dolok ? '✓' : '❌').'</td>';
    print '<td>'.$dolversion.($dolok ? ' (OK)' : ' (Requires 14.0+)').'</td>';
    print '</tr>';
    
    // Database
    print '<tr class="oddeven">';
    print '<td>Database Connection</td>';
    print '<td>'.($db->connected ? '✓' : '❌').'</td>';
    print '<td>'.($db->connected ? 'Connected' : 'Not connected').'</td>';
    print '</tr>';
    
    // Write permissions
    $writeable = is_writable($conf->auditdigital->dir_output);
    print '<tr class="oddeven">';
    print '<td>Write Permissions</td>';
    print '<td>'.($writeable ? '✓' : '❌').'</td>';
    print '<td>'.$conf->auditdigital->dir_output.($writeable ? ' (Writable)' : ' (Not writable)').'</td>';
    print '</tr>';
    
    // Required modules
    $requiredModules = array(
        'societe' => 'Third Parties',
        'projet' => 'Projects'
    );
    
    foreach ($requiredModules as $module => $name) {
        $enabled = isModEnabled($module);
        print '<tr class="oddeven">';
        print '<td>Module: '.$name.'</td>';
        print '<td>'.($enabled ? '✓' : '❌').'</td>';
        print '<td>'.($enabled ? 'Enabled' : 'Disabled').'</td>';
        print '</tr>';
    }
    
    print '</table>';
}

llxFooter();
$db->close();
?>