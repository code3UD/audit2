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
 * \file       test.php
 * \ingroup    auditdigital
 * \brief      Test script for AuditDigital module
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

require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php';

// Load translation files
$langs->loadLangs(array("admin", "auditdigital@auditdigital"));

// Security check
if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');

/*
 * Test Functions
 */

function testDatabaseConnection($db) {
    $result = array('status' => 'OK', 'message' => 'Database connection successful');
    
    if (!$db->connected) {
        $result['status'] = 'ERROR';
        $result['message'] = 'Database not connected';
        return $result;
    }
    
    // Test table existence
    $tables = array('auditdigital_audit', 'auditdigital_solutions');
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX.$table."'";
        $resql = $db->query($sql);
        if (!$resql || $db->num_rows($resql) == 0) {
            $result['status'] = 'WARNING';
            $result['message'] = 'Table '.$table.' not found. Please install the module first.';
            return $result;
        }
    }
    
    return $result;
}

function testAuditClass($db) {
    $result = array('status' => 'OK', 'message' => 'Audit class working correctly');
    
    try {
        $audit = new Audit($db);
        
        // Test basic properties
        if (empty($audit->fields)) {
            throw new Exception('Audit fields not defined');
        }
        
        // Test specimen creation
        $audit->initAsSpecimen();
        if (empty($audit->ref)) {
            throw new Exception('Specimen creation failed');
        }
        
    } catch (Exception $e) {
        $result['status'] = 'ERROR';
        $result['message'] = 'Audit class error: '.$e->getMessage();
    }
    
    return $result;
}

function testQuestionnaireClass($db) {
    $result = array('status' => 'OK', 'message' => 'Questionnaire class working correctly');
    
    try {
        $questionnaire = new Questionnaire($db);
        
        // Test questionnaire structure
        $structure = $questionnaire->getQuestionnaire();
        if (empty($structure)) {
            throw new Exception('Questionnaire structure not loaded');
        }
        
        // Test steps
        $steps = $questionnaire->getSteps();
        if (count($steps) < 5) {
            throw new Exception('Not enough questionnaire steps');
        }
        
        // Test validation
        $testResponses = array('structure_type' => 'tpe_pme');
        $validation = $questionnaire->validateStep('step1_general', $testResponses);
        if (!isset($validation['valid'])) {
            throw new Exception('Validation method not working');
        }
        
    } catch (Exception $e) {
        $result['status'] = 'ERROR';
        $result['message'] = 'Questionnaire class error: '.$e->getMessage();
    }
    
    return $result;
}

function testSolutionLibraryClass($db) {
    $result = array('status' => 'OK', 'message' => 'SolutionLibrary class working correctly');
    
    try {
        $solutionLibrary = new SolutionLibrary($db);
        
        // Test fetching solutions
        $solutions = $solutionLibrary->fetchAll('', '', 5);
        if (!is_array($solutions)) {
            throw new Exception('Cannot fetch solutions');
        }
        
        // Test JSON loading
        $jsonFile = DOL_DOCUMENT_ROOT.'/custom/auditdigital/data/solutions.json';
        if (file_exists($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON format in solutions file');
            }
        }
        
    } catch (Exception $e) {
        $result['status'] = 'ERROR';
        $result['message'] = 'SolutionLibrary class error: '.$e->getMessage();
    }
    
    return $result;
}

function testFilePermissions() {
    $result = array('status' => 'OK', 'message' => 'File permissions OK');
    
    $paths = array(
        DOL_DOCUMENT_ROOT.'/custom/auditdigital/',
        DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/',
        DOL_DOCUMENT_ROOT.'/custom/auditdigital/wizard/',
        DOL_DOCUMENT_ROOT.'/custom/auditdigital/css/',
        DOL_DOCUMENT_ROOT.'/custom/auditdigital/js/'
    );
    
    foreach ($paths as $path) {
        if (!file_exists($path)) {
            $result['status'] = 'ERROR';
            $result['message'] = 'Path not found: '.$path;
            return $result;
        }
        
        if (!is_readable($path)) {
            $result['status'] = 'ERROR';
            $result['message'] = 'Path not readable: '.$path;
            return $result;
        }
    }
    
    return $result;
}

function testModuleConfiguration() {
    global $conf;
    
    $result = array('status' => 'OK', 'message' => 'Module configuration OK');
    
    // Check if module is enabled
    if (empty($conf->auditdigital->enabled)) {
        $result['status'] = 'WARNING';
        $result['message'] = 'Module not enabled. Please activate it in Configuration > Modules.';
        return $result;
    }
    
    // Check required configurations
    $requiredConfigs = array(
        'AUDITDIGITAL_AUDIT_MASK',
        'AUDIT_ADDON_PDF'
    );
    
    foreach ($requiredConfigs as $config) {
        if (empty($conf->global->$config)) {
            $result['status'] = 'WARNING';
            $result['message'] = 'Configuration missing: '.$config;
            return $result;
        }
    }
    
    return $result;
}

/*
 * Actions
 */

$tests = array();

if ($action == 'run_tests' || empty($action)) {
    $tests['database'] = testDatabaseConnection($db);
    $tests['audit_class'] = testAuditClass($db);
    $tests['questionnaire_class'] = testQuestionnaireClass($db);
    $tests['solution_library_class'] = testSolutionLibraryClass($db);
    $tests['file_permissions'] = testFilePermissions();
    $tests['module_configuration'] = testModuleConfiguration();
}

/*
 * View
 */

$title = "AuditDigital Module Tests";
llxHeader('', $title, '');

print load_fiche_titre($title, '', 'title_setup');

if (empty($action)) {
    print '<div class="info">';
    print '<p>This page allows you to test the AuditDigital module installation and configuration.</p>';
    print '<p>Click the button below to run all tests.</p>';
    print '</div>';
    
    print '<div class="center">';
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="run_tests">';
    print '<input type="submit" class="button" value="Run Tests">';
    print '</form>';
    print '</div>';
}

if (!empty($tests)) {
    print '<h3>Test Results</h3>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Test</td>';
    print '<td>Status</td>';
    print '<td>Message</td>';
    print '</tr>';
    
    $overallStatus = 'OK';
    foreach ($tests as $testName => $testResult) {
        $statusIcon = '✓';
        $statusClass = 'ok';
        
        if ($testResult['status'] == 'WARNING') {
            $statusIcon = '⚠';
            $statusClass = 'warning';
            if ($overallStatus == 'OK') $overallStatus = 'WARNING';
        } elseif ($testResult['status'] == 'ERROR') {
            $statusIcon = '❌';
            $statusClass = 'error';
            $overallStatus = 'ERROR';
        }
        
        print '<tr class="oddeven">';
        print '<td>'.ucfirst(str_replace('_', ' ', $testName)).'</td>';
        print '<td class="'.$statusClass.'">'.$statusIcon.' '.$testResult['status'].'</td>';
        print '<td>'.$testResult['message'].'</td>';
        print '</tr>';
    }
    
    print '</table>';
    
    // Overall status
    print '<br><div class="center">';
    if ($overallStatus == 'OK') {
        print '<div class="ok"><strong>✓ All tests passed! The module is ready to use.</strong></div>';
    } elseif ($overallStatus == 'WARNING') {
        print '<div class="warning"><strong>⚠ Some warnings detected. The module should work but may need configuration.</strong></div>';
    } else {
        print '<div class="error"><strong>❌ Some tests failed. Please fix the issues before using the module.</strong></div>';
    }
    print '</div>';
    
    // Additional information
    print '<br><h3>System Information</h3>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Component</td>';
    print '<td>Value</td>';
    print '</tr>';
    
    $systemInfo = array(
        'PHP Version' => phpversion(),
        'Dolibarr Version' => DOL_VERSION,
        'Module Version' => '1.0.0',
        'Database Type' => $db->type,
        'Memory Limit' => ini_get('memory_limit'),
        'Max Execution Time' => ini_get('max_execution_time').'s',
        'Upload Max Filesize' => ini_get('upload_max_filesize'),
        'Post Max Size' => ini_get('post_max_size')
    );
    
    foreach ($systemInfo as $key => $value) {
        print '<tr class="oddeven">';
        print '<td>'.$key.'</td>';
        print '<td>'.$value.'</td>';
        print '</tr>';
    }
    
    print '</table>';
}

llxFooter();
$db->close();
?>