<?php
/* Copyright (C) 2024 Up Digit Agency
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       debug.php
 * \ingroup    auditdigital
 * \brief      Debug page for AuditDigital module
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

// Security check
if (!$user->admin) {
    accessforbidden('Admin access required');
}

$title = "AuditDigital Debug";
llxHeader('', $title, '');

print load_fiche_titre($title, '', 'title_setup');

echo "<h2>🔍 Diagnostic AuditDigital</h2>";

// 1. Check module activation
echo "<h3>1. État du module</h3>";
echo "<table class='noborder centpercent'>";
echo "<tr class='liste_titre'>";
echo "<td>Vérification</td>";
echo "<td>Statut</td>";
echo "<td>Détails</td>";
echo "</tr>";

// Module enabled
$moduleEnabled = isModEnabled('auditdigital');
echo "<tr class='oddeven'>";
echo "<td>Module activé</td>";
echo "<td>".($moduleEnabled ? '✅' : '❌')."</td>";
echo "<td>".($moduleEnabled ? 'Activé' : 'Désactivé')."</td>";
echo "</tr>";

// Files exist
$files = array(
    'Module principal' => DOL_DOCUMENT_ROOT.'/custom/auditdigital/core/modules/modAuditDigital.class.php',
    'Classe Audit' => DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php',
    'Classe Questionnaire' => DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php',
    'Wizard' => DOL_DOCUMENT_ROOT.'/custom/auditdigital/wizard/index.php',
    'Liste audits' => DOL_DOCUMENT_ROOT.'/custom/auditdigital/audit_list.php'
);

foreach ($files as $name => $file) {
    $exists = file_exists($file);
    echo "<tr class='oddeven'>";
    echo "<td>$name</td>";
    echo "<td>".($exists ? '✅' : '❌')."</td>";
    echo "<td>".($exists ? 'Présent' : 'Manquant')."</td>";
    echo "</tr>";
}

echo "</table>";

// 2. Check database
echo "<h3>2. Base de données</h3>";
echo "<table class='noborder centpercent'>";
echo "<tr class='liste_titre'>";
echo "<td>Table</td>";
echo "<td>Statut</td>";
echo "<td>Détails</td>";
echo "</tr>";

$tables = array(
    'llx_auditdigital_audit',
    'llx_auditdigital_solutions'
);

foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = $db->query($sql);
    $exists = ($result && $db->num_rows($result) > 0);
    
    echo "<tr class='oddeven'>";
    echo "<td>$table</td>";
    echo "<td>".($exists ? '✅' : '❌')."</td>";
    echo "<td>".($exists ? 'Existe' : 'Manquante')."</td>";
    echo "</tr>";
}

echo "</table>";

// 3. Check permissions
echo "<h3>3. Permissions</h3>";
echo "<table class='noborder centpercent'>";
echo "<tr class='liste_titre'>";
echo "<td>Permission</td>";
echo "<td>Statut</td>";
echo "<td>Détails</td>";
echo "</tr>";

$permissions = array(
    'auditdigital.audit.read' => isset($user->rights->auditdigital->audit->read) ? $user->rights->auditdigital->audit->read : false,
    'auditdigital.audit.write' => isset($user->rights->auditdigital->audit->write) ? $user->rights->auditdigital->audit->write : false,
    'auditdigital.audit.delete' => isset($user->rights->auditdigital->audit->delete) ? $user->rights->auditdigital->audit->delete : false
);

foreach ($permissions as $perm => $value) {
    echo "<tr class='oddeven'>";
    echo "<td>$perm</td>";
    echo "<td>".($value ? '✅' : '❌')."</td>";
    echo "<td>".($value ? 'Accordée' : 'Non accordée')."</td>";
    echo "</tr>";
}

echo "</table>";

// 4. Check configuration
echo "<h3>4. Configuration</h3>";
echo "<table class='noborder centpercent'>";
echo "<tr class='liste_titre'>";
echo "<td>Paramètre</td>";
echo "<td>Valeur</td>";
echo "</tr>";

$configs = array(
    'MAIN_MODULE_AUDITDIGITAL' => $conf->global->MAIN_MODULE_AUDITDIGITAL ?? 'Non défini',
    'AUDITDIGITAL_AUDIT_MASK' => $conf->global->AUDITDIGITAL_AUDIT_MASK ?? 'Non défini',
    'AUDIT_ADDON_PDF' => $conf->global->AUDIT_ADDON_PDF ?? 'Non défini'
);

foreach ($configs as $name => $value) {
    echo "<tr class='oddeven'>";
    echo "<td>$name</td>";
    echo "<td>$value</td>";
    echo "</tr>";
}

echo "</table>";

// 5. Test class loading
echo "<h3>5. Test de chargement des classes</h3>";
echo "<table class='noborder centpercent'>";
echo "<tr class='liste_titre'>";
echo "<td>Classe</td>";
echo "<td>Statut</td>";
echo "<td>Erreur</td>";
echo "</tr>";

$classes = array(
    'Audit' => DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/audit.class.php',
    'Questionnaire' => DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/questionnaire.class.php',
    'SolutionLibrary' => DOL_DOCUMENT_ROOT.'/custom/auditdigital/class/solutionlibrary.class.php'
);

foreach ($classes as $className => $file) {
    $error = '';
    $loaded = false;
    
    try {
        if (file_exists($file)) {
            require_once $file;
            if (class_exists($className)) {
                $loaded = true;
            } else {
                $error = 'Classe non trouvée dans le fichier';
            }
        } else {
            $error = 'Fichier non trouvé';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (ParseError $e) {
        $error = 'Erreur de syntaxe: '.$e->getMessage();
    }
    
    echo "<tr class='oddeven'>";
    echo "<td>$className</td>";
    echo "<td>".($loaded ? '✅' : '❌')."</td>";
    echo "<td>$error</td>";
    echo "</tr>";
}

echo "</table>";

// 6. PHP Info
echo "<h3>6. Informations PHP</h3>";
echo "<table class='noborder centpercent'>";
echo "<tr class='liste_titre'>";
echo "<td>Information</td>";
echo "<td>Valeur</td>";
echo "</tr>";

$phpInfo = array(
    'Version PHP' => phpversion(),
    'Memory Limit' => ini_get('memory_limit'),
    'Max Execution Time' => ini_get('max_execution_time').'s',
    'Error Reporting' => ini_get('error_reporting'),
    'Display Errors' => ini_get('display_errors') ? 'On' : 'Off'
);

foreach ($phpInfo as $name => $value) {
    echo "<tr class='oddeven'>";
    echo "<td>$name</td>";
    echo "<td>$value</td>";
    echo "</tr>";
}

echo "</table>";

// 7. Actions rapides
echo "<h3>7. Actions rapides</h3>";
echo "<div class='center'>";
echo "<a href='".dol_buildpath('/auditdigital/install.php', 1)."' class='button'>Réinstaller le module</a> ";
echo "<a href='".dol_buildpath('/auditdigital/test.php', 1)."' class='button'>Lancer les tests</a> ";
echo "<a href='".dol_buildpath('/auditdigital/wizard/index.php', 1)."' class='button'>Tester le wizard</a>";
echo "</div>";

// 8. Logs récents
echo "<h3>8. Logs récents</h3>";
$logFile = $conf->syslog_file;
if ($logFile && file_exists($logFile)) {
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto;'>";
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -50); // 50 dernières lignes
    foreach ($recentLines as $line) {
        if (stripos($line, 'audit') !== false || stripos($line, 'error') !== false) {
            echo htmlspecialchars($line)."\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>Fichier de log non trouvé ou non configuré.</p>";
}

llxFooter();
?>