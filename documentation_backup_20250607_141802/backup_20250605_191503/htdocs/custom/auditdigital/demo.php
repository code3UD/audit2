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
 * \file       demo.php
 * \ingroup    auditdigital
 * \brief      Demo data creation for AuditDigital module
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

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php';

// Load translation files
$langs->loadLangs(array("admin", "auditdigital@auditdigital"));

// Security check
if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');

/*
 * Demo Data
 */

$demoCompanies = array(
    array(
        'name' => 'Boulangerie Martin',
        'code_client' => 'BOUL001',
        'typent_code' => 'TE',
        'address' => '15 rue de la Paix',
        'zip' => '75001',
        'town' => 'Paris',
        'country_code' => 'FR',
        'phone' => '01 42 36 78 90',
        'email' => 'contact@boulangerie-martin.fr',
        'structure_type' => 'tpe_pme',
        'sector' => 'commerce'
    ),
    array(
        'name' => 'Cabinet Comptable Dupont',
        'code_client' => 'COMP001',
        'typent_code' => 'TE',
        'address' => '25 avenue des Champs',
        'zip' => '69000',
        'town' => 'Lyon',
        'country_code' => 'FR',
        'phone' => '04 78 45 67 89',
        'email' => 'info@cabinet-dupont.fr',
        'structure_type' => 'tpe_pme',
        'sector' => 'services'
    ),
    array(
        'name' => 'Mairie de Beauville',
        'code_client' => 'MAIRE001',
        'typent_code' => 'TE',
        'address' => '1 place de la République',
        'zip' => '33000',
        'town' => 'Bordeaux',
        'country_code' => 'FR',
        'phone' => '05 56 78 90 12',
        'email' => 'mairie@beauville.fr',
        'structure_type' => 'collectivite',
        'sector' => 'administration'
    )
);

$demoAudits = array(
    array(
        'label' => 'Audit Digital - Boulangerie Martin',
        'structure_type' => 'tpe_pme',
        'audit_type' => 'digital_maturity',
        'responses' => array(
            'step1_general' => array(
                'structure_type' => 'tpe_pme',
                'sector' => 'commerce',
                'employees_count' => '1-10',
                'it_budget' => '0-5k',
                'main_objectives' => array('efficiency', 'costs')
            ),
            'step2_maturite' => array(
                'website_presence' => 1,
                'social_media' => 1,
                'collaborative_tools' => 0,
                'process_digitalization' => 0,
                'team_training' => 0
            ),
            'step3_cybersecurite' => array(
                'password_policy' => 0,
                'backup_strategy' => 0,
                'antivirus_firewall' => 1,
                'security_training' => 0,
                'gdpr_compliance' => 1
            ),
            'step4_cloud' => array(
                'current_hosting' => 0,
                'cloud_services' => 0,
                'storage_needs' => 0,
                'remote_work' => 0,
                'network_performance' => 1
            ),
            'step5_automatisation' => array(
                'manual_processes' => array('invoicing', 'inventory'),
                'automation_tools' => 0,
                'integration_needs' => array('accounting'),
                'time_savings' => 1,
                'automation_budget' => '0-2k'
            )
        ),
        'scores' => array(
            'maturite' => 40,
            'cybersecurite' => 40,
            'cloud' => 20,
            'automatisation' => 30,
            'global' => 32
        )
    ),
    array(
        'label' => 'Audit Digital - Cabinet Dupont',
        'structure_type' => 'tpe_pme',
        'audit_type' => 'digital_maturity',
        'responses' => array(
            'step1_general' => array(
                'structure_type' => 'tpe_pme',
                'sector' => 'services',
                'employees_count' => '11-50',
                'it_budget' => '5k-15k',
                'main_objectives' => array('security', 'compliance', 'efficiency')
            ),
            'step2_maturite' => array(
                'website_presence' => 2,
                'social_media' => 1,
                'collaborative_tools' => 1,
                'process_digitalization' => 1,
                'team_training' => 1
            ),
            'step3_cybersecurite' => array(
                'password_policy' => 1,
                'backup_strategy' => 2,
                'antivirus_firewall' => 2,
                'security_training' => 1,
                'gdpr_compliance' => 2
            ),
            'step4_cloud' => array(
                'current_hosting' => 1,
                'cloud_services' => 1,
                'storage_needs' => 1,
                'remote_work' => 1,
                'network_performance' => 2
            ),
            'step5_automatisation' => array(
                'manual_processes' => array('reporting', 'data_entry'),
                'automation_tools' => 1,
                'integration_needs' => array('crm_erp', 'accounting'),
                'time_savings' => 2,
                'automation_budget' => '5k-10k'
            )
        ),
        'scores' => array(
            'maturite' => 60,
            'cybersecurite' => 80,
            'cloud' => 60,
            'automatisation' => 70,
            'global' => 67
        )
    ),
    array(
        'label' => 'Audit Digital - Mairie de Beauville',
        'structure_type' => 'collectivite',
        'audit_type' => 'digital_maturity',
        'responses' => array(
            'step1_general' => array(
                'structure_type' => 'collectivite',
                'sector' => 'administration',
                'employees_count' => '51-250',
                'it_budget' => '50k-100k',
                'main_objectives' => array('compliance', 'innovation', 'efficiency')
            ),
            'step2_maturite' => array(
                'website_presence' => 1,
                'social_media' => 0,
                'collaborative_tools' => 0,
                'process_digitalization' => 0,
                'team_training' => 0
            ),
            'step3_cybersecurite' => array(
                'password_policy' => 1,
                'backup_strategy' => 1,
                'antivirus_firewall' => 1,
                'security_training' => 0,
                'gdpr_compliance' => 1
            ),
            'step4_cloud' => array(
                'current_hosting' => 0,
                'cloud_services' => 0,
                'storage_needs' => 0,
                'remote_work' => 0,
                'network_performance' => 1
            ),
            'step5_automatisation' => array(
                'manual_processes' => array('invoicing', 'reporting', 'communication'),
                'automation_tools' => 0,
                'integration_needs' => array('website_crm'),
                'time_savings' => 2,
                'automation_budget' => '10k+'
            )
        ),
        'scores' => array(
            'maturite' => 20,
            'cybersecurite' => 60,
            'cloud' => 20,
            'automatisation' => 40,
            'global' => 35
        )
    )
);

/*
 * Actions
 */

$message = '';
$error = 0;

if ($action == 'create_demo') {
    $db->begin();
    
    try {
        $createdCompanies = 0;
        $createdAudits = 0;
        
        // Create demo companies
        foreach ($demoCompanies as $index => $companyData) {
            $company = new Societe($db);
            
            // Check if company already exists
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE code_client = '".$db->escape($companyData['code_client'])."'";
            $resql = $db->query($sql);
            if ($resql && $db->num_rows($resql) > 0) {
                continue; // Company already exists
            }
            
            // Set company properties
            foreach ($companyData as $key => $value) {
                if (property_exists($company, $key)) {
                    $company->$key = $value;
                }
            }
            
            $company->client = 1; // Customer
            $company->fournisseur = 0; // Not supplier
            $company->date_creation = dol_now();
            
            $result = $company->create($user);
            if ($result > 0) {
                $createdCompanies++;
                
                // Create corresponding audit
                if (isset($demoAudits[$index])) {
                    $auditData = $demoAudits[$index];
                    
                    $audit = new Audit($db);
                    $audit->ref = '(PROV)';
                    $audit->label = $auditData['label'];
                    $audit->audit_type = $auditData['audit_type'];
                    $audit->structure_type = $auditData['structure_type'];
                    $audit->fk_soc = $company->id;
                    $audit->date_creation = dol_now();
                    $audit->date_audit = dol_now();
                    $audit->fk_user_creat = $user->id;
                    $audit->status = Audit::STATUS_VALIDATED;
                    
                    // Set scores
                    $audit->score_global = $auditData['scores']['global'];
                    $audit->score_maturite = $auditData['scores']['maturite'];
                    $audit->score_cybersecurite = $auditData['scores']['cybersecurite'];
                    $audit->score_cloud = $auditData['scores']['cloud'];
                    $audit->score_automatisation = $auditData['scores']['automatisation'];
                    
                    // Set JSON data
                    $audit->json_responses = json_encode($auditData['responses']);
                    $audit->json_config = json_encode(array(
                        'demo_data' => true,
                        'created_date' => dol_now()
                    ));
                    
                    // Generate recommendations
                    $recommendations = $audit->generateRecommendations($auditData['scores'], $auditData['structure_type']);
                    $audit->json_recommendations = json_encode($recommendations);
                    
                    $result = $audit->create($user);
                    if ($result > 0) {
                        $createdAudits++;
                    } else {
                        throw new Exception('Error creating audit: '.$audit->error);
                    }
                }
            } else {
                throw new Exception('Error creating company: '.$company->error);
            }
        }
        
        $db->commit();
        $message = "✓ Demo data created successfully!<br>";
        $message .= "✓ ".$createdCompanies." companies created<br>";
        $message .= "✓ ".$createdAudits." audits created<br>";
        $message .= "<br>You can now explore the module with realistic data.";
        
    } catch (Exception $e) {
        $db->rollback();
        $error++;
        $message = "❌ Error creating demo data: ".$e->getMessage();
    }
}

if ($action == 'delete_demo') {
    $db->begin();
    
    try {
        $deletedAudits = 0;
        $deletedCompanies = 0;
        
        // Delete demo audits
        foreach ($demoCompanies as $companyData) {
            $sql = "SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe s WHERE s.code_client = '".$db->escape($companyData['code_client'])."'";
            $resql = $db->query($sql);
            if ($resql && $db->num_rows($resql) > 0) {
                $obj = $db->fetch_object($resql);
                $socid = $obj->rowid;
                
                // Delete audits for this company
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."auditdigital_audit WHERE fk_soc = ".((int) $socid);
                $result = $db->query($sql);
                if ($result) {
                    $deletedAudits += $db->affected_rows($result);
                }
                
                // Delete company
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."societe WHERE rowid = ".((int) $socid);
                $result = $db->query($sql);
                if ($result) {
                    $deletedCompanies++;
                }
            }
        }
        
        $db->commit();
        $message = "✓ Demo data deleted successfully!<br>";
        $message .= "✓ ".$deletedAudits." audits deleted<br>";
        $message .= "✓ ".$deletedCompanies." companies deleted<br>";
        
    } catch (Exception $e) {
        $db->rollback();
        $error++;
        $message = "❌ Error deleting demo data: ".$e->getMessage();
    }
}

/*
 * View
 */

$title = "AuditDigital Demo Data";
llxHeader('', $title, '');

print load_fiche_titre($title, '', 'title_setup');

if ($error) {
    print '<div class="error">'.$message.'</div>';
} elseif ($message) {
    print '<div class="ok">'.$message.'</div>';
}

if (empty($action) || $action == 'create_demo' || $action == 'delete_demo') {
    print '<div class="info">';
    print '<h3>Demo Data Management</h3>';
    print '<p>This page allows you to create or delete demo data for testing the AuditDigital module.</p>';
    print '<p><strong>Demo data includes:</strong></p>';
    print '<ul>';
    print '<li>3 demo companies (TPE, PME, Collectivité)</li>';
    print '<li>3 corresponding audits with realistic scores</li>';
    print '<li>Sample responses for all questionnaire steps</li>';
    print '<li>Generated recommendations based on scores</li>';
    print '</ul>';
    print '</div>';
    
    // Check if demo data exists
    $demoExists = false;
    foreach ($demoCompanies as $companyData) {
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE code_client = '".$db->escape($companyData['code_client'])."'";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            $demoExists = true;
            break;
        }
    }
    
    print '<div class="center">';
    if (!$demoExists) {
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display: inline-block; margin-right: 10px;">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="create_demo">';
        print '<input type="submit" class="button" value="Create Demo Data">';
        print '</form>';
    } else {
        print '<div class="warning">Demo data already exists.</div><br>';
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display: inline-block; margin-right: 10px;">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="delete_demo">';
        print '<input type="submit" class="button" value="Delete Demo Data" onclick="return confirm(\'Are you sure you want to delete all demo data?\');">';
        print '</form>';
    }
    print '</div>';
    
    // Show demo data preview
    print '<br><h3>Demo Data Preview</h3>';
    
    print '<h4>Companies</h4>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Name</td>';
    print '<td>Type</td>';
    print '<td>Sector</td>';
    print '<td>City</td>';
    print '<td>Status</td>';
    print '</tr>';
    
    foreach ($demoCompanies as $companyData) {
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE code_client = '".$db->escape($companyData['code_client'])."'";
        $resql = $db->query($sql);
        $exists = ($resql && $db->num_rows($resql) > 0);
        
        print '<tr class="oddeven">';
        print '<td>'.$companyData['name'].'</td>';
        print '<td>'.ucfirst(str_replace('_', '/', $companyData['structure_type'])).'</td>';
        print '<td>'.ucfirst($companyData['sector']).'</td>';
        print '<td>'.$companyData['town'].'</td>';
        print '<td>'.($exists ? '✓ Exists' : '○ Not created').'</td>';
        print '</tr>';
    }
    print '</table>';
    
    print '<h4>Audits</h4>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Label</td>';
    print '<td>Type</td>';
    print '<td>Global Score</td>';
    print '<td>Maturity</td>';
    print '<td>Security</td>';
    print '<td>Cloud</td>';
    print '<td>Automation</td>';
    print '</tr>';
    
    foreach ($demoAudits as $auditData) {
        print '<tr class="oddeven">';
        print '<td>'.$auditData['label'].'</td>';
        print '<td>'.ucfirst(str_replace('_', ' ', $auditData['audit_type'])).'</td>';
        print '<td><strong>'.$auditData['scores']['global'].'%</strong></td>';
        print '<td>'.$auditData['scores']['maturite'].'%</td>';
        print '<td>'.$auditData['scores']['cybersecurite'].'%</td>';
        print '<td>'.$auditData['scores']['cloud'].'%</td>';
        print '<td>'.$auditData['scores']['automatisation'].'%</td>';
        print '</tr>';
    }
    print '</table>';
}

llxFooter();
$db->close();
?>