<?php
/**
 * Configuration de test pour AuditDigital
 * Généré automatiquement par deploy_dev.sh
 */

// Configuration de base
define('AUDIT_DEBUG', true);
define('AUDIT_TEST_MODE', true);

// URLs de test
$test_urls = [
    'wizard_enhanced' => 'wizard/enhanced.php',
    'demo_enhanced' => 'demo_enhanced.php',
    'demo_steps_3_6' => 'demo_steps_3_6.php',
    'test_scores' => 'test_scores_demo.php'
];

// Configuration de la base de données de test (à adapter)
$test_db_config = [
    'host' => 'localhost',
    'database' => 'dolibarr_test',
    'user' => 'dolibarr',
    'password' => 'password'
];

echo "<!-- Configuration de test AuditDigital chargée -->\n";
?>
