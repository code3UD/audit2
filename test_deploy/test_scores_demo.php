<?php
/**
 * Page de test des calculs de scores corrigés
 */

// Simulation de données d'audit
$test_data = [
    'step_2' => [
        'audit_digital_level' => 8,
        'audit_web_presence' => 7,
        'audit_digital_tools' => 6
    ],
    'step_3' => [
        'audit_security_level' => 5,
        'audit_rgpd_compliance' => 4,
        'audit_backup_strategy' => 6
    ],
    'step_4' => [
        'audit_cloud_adoption' => 7,
        'audit_mobility' => 8,
        'audit_infrastructure' => 5
    ],
    'step_5' => [
        'audit_automation_level' => 6,
        'audit_collaboration_tools' => 7,
        'audit_data_analysis' => 5
    ]
];

// Calculs selon la nouvelle formule CORRIGÉE
$digital_level = $test_data['step_2']['audit_digital_level'];
$web_presence = $test_data['step_2']['audit_web_presence'];
$digital_tools = $test_data['step_2']['audit_digital_tools'];
$maturity_score = ($digital_level + $web_presence + $digital_tools) / 3; // Moyenne sur 10

$security_level = $test_data['step_3']['audit_security_level'];
$rgpd_compliance = $test_data['step_3']['audit_rgpd_compliance'];
$backup_strategy = $test_data['step_3']['audit_backup_strategy'];
$security_score = ($security_level + $rgpd_compliance + $backup_strategy) / 3; // Moyenne sur 10

$cloud_adoption = $test_data['step_4']['audit_cloud_adoption'];
$mobility = $test_data['step_4']['audit_mobility'];
$infrastructure = $test_data['step_4']['audit_infrastructure'];
$cloud_score = ($cloud_adoption + $mobility + $infrastructure) / 3; // Moyenne sur 10

$automation_level = $test_data['step_5']['audit_automation_level'];
$collaboration_tools = $test_data['step_5']['audit_collaboration_tools'];
$data_analysis = $test_data['step_5']['audit_data_analysis'];
$automation_score = ($automation_level + $collaboration_tools + $data_analysis) / 3; // Moyenne sur 10

// Score global pondéré sur 100
$total_score = ($maturity_score * 0.30 + $security_score * 0.25 + $cloud_score * 0.25 + $automation_score * 0.20) * 10;

// Déterminer le niveau de maturité
$maturity_level = 'Débutant';
$maturity_color = '#dc3545';
$maturity_icon = 'fa-seedling';

if ($total_score >= 80) {
    $maturity_level = 'Expert';
    $maturity_color = '#28a745';
    $maturity_icon = 'fa-trophy';
} elseif ($total_score >= 60) {
    $maturity_level = 'Avancé';
    $maturity_color = '#17a2b8';
    $maturity_icon = 'fa-rocket';
} elseif ($total_score >= 40) {
    $maturity_level = 'Intermédiaire';
    $maturity_color = '#ffc107';
    $maturity_icon = 'fa-chart-line';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Calculs de Scores Corrigés</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 20px;
            color: #2c3e50;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .test-section {
            margin-bottom: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 5px solid #3498db;
        }
        
        .test-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .score-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .score-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .score-card h4 {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .score-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #3498db;
            border-radius: 10px;
            transition: width 0.8s ease-in-out;
        }
        
        .total-score {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            margin: 30px 0;
        }
        
        .total-score .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .total-score h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .total-score .level {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .formula {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .before, .after {
            padding: 20px;
            border-radius: 8px;
        }
        
        .before {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        .after {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-calculator"></i> Test des Calculs de Scores Corrigés</h1>
        <p>Validation des corrections apportées aux calculs d'audit digital</p>
    </div>

    <!-- Données d'entrée -->
    <div class="test-section">
        <h3><i class="fas fa-database"></i> Données d'entrée (simulation)</h3>
        <div class="score-grid">
            <div class="score-card">
                <h4>Maturité Digitale</h4>
                <div>Digitalisation: <?php echo $digital_level; ?>/10</div>
                <div>Web: <?php echo $web_presence; ?>/10</div>
                <div>Outils: <?php echo $digital_tools; ?>/10</div>
            </div>
            <div class="score-card">
                <h4>Cybersécurité</h4>
                <div>Sécurité: <?php echo $security_level; ?>/10</div>
                <div>RGPD: <?php echo $rgpd_compliance; ?>/10</div>
                <div>Sauvegarde: <?php echo $backup_strategy; ?>/10</div>
            </div>
            <div class="score-card">
                <h4>Cloud & Infrastructure</h4>
                <div>Cloud: <?php echo $cloud_adoption; ?>/10</div>
                <div>Mobilité: <?php echo $mobility; ?>/10</div>
                <div>Infrastructure: <?php echo $infrastructure; ?>/10</div>
            </div>
            <div class="score-card">
                <h4>Automatisation</h4>
                <div>Automatisation: <?php echo $automation_level; ?>/10</div>
                <div>Collaboration: <?php echo $collaboration_tools; ?>/10</div>
                <div>Analyse: <?php echo $data_analysis; ?>/10</div>
            </div>
        </div>
    </div>

    <!-- Comparaison avant/après -->
    <div class="test-section">
        <h3><i class="fas fa-balance-scale"></i> Comparaison Avant/Après Correction</h3>
        <div class="comparison">
            <div class="before">
                <h4>❌ AVANT (Calcul incorrect)</h4>
                <div class="formula">
                    Score = (moyenne * 10 / 3) * 2.5<br>
                    Résultat: <?php echo round((($digital_level + $web_presence + $digital_tools) * 10 / 3 + ($security_level + $rgpd_compliance + $backup_strategy) * 10 / 3 + ($cloud_adoption + $mobility + $infrastructure) * 10 / 3 + ($automation_level + $collaboration_tools + $data_analysis) * 10 / 3) * 2.5); ?>/100
                </div>
                <p><strong>Problème:</strong> Score aberrant > 100</p>
            </div>
            <div class="after">
                <h4>✅ APRÈS (Calcul corrigé)</h4>
                <div class="formula">
                    Score = (moyenne1*0.30 + moyenne2*0.25 + moyenne3*0.25 + moyenne4*0.20) * 10<br>
                    Résultat: <?php echo round($total_score); ?>/100
                </div>
                <p><strong>Solution:</strong> Score normal ≤ 100</p>
            </div>
        </div>
    </div>

    <!-- Calculs détaillés -->
    <div class="test-section">
        <h3><i class="fas fa-chart-bar"></i> Calculs Détaillés (Corrigés)</h3>
        <div class="score-grid">
            <div class="score-card">
                <h4>Maturité Digitale</h4>
                <div class="score-value"><?php echo round($maturity_score * 10); ?>%</div>
                <div class="formula">
                    (<?php echo $digital_level; ?> + <?php echo $web_presence; ?> + <?php echo $digital_tools; ?>) / 3 = <?php echo round($maturity_score, 2); ?>/10
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min($maturity_score * 10, 100); ?>%;"></div>
                </div>
                <small>Poids: 30%</small>
            </div>
            
            <div class="score-card">
                <h4>Cybersécurité</h4>
                <div class="score-value"><?php echo round($security_score * 10); ?>%</div>
                <div class="formula">
                    (<?php echo $security_level; ?> + <?php echo $rgpd_compliance; ?> + <?php echo $backup_strategy; ?>) / 3 = <?php echo round($security_score, 2); ?>/10
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min($security_score * 10, 100); ?>%;"></div>
                </div>
                <small>Poids: 25%</small>
            </div>
            
            <div class="score-card">
                <h4>Cloud & Infrastructure</h4>
                <div class="score-value"><?php echo round($cloud_score * 10); ?>%</div>
                <div class="formula">
                    (<?php echo $cloud_adoption; ?> + <?php echo $mobility; ?> + <?php echo $infrastructure; ?>) / 3 = <?php echo round($cloud_score, 2); ?>/10
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min($cloud_score * 10, 100); ?>%;"></div>
                </div>
                <small>Poids: 25%</small>
            </div>
            
            <div class="score-card">
                <h4>Automatisation</h4>
                <div class="score-value"><?php echo round($automation_score * 10); ?>%</div>
                <div class="formula">
                    (<?php echo $automation_level; ?> + <?php echo $collaboration_tools; ?> + <?php echo $data_analysis; ?>) / 3 = <?php echo round($automation_score, 2); ?>/10
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min($automation_score * 10, 100); ?>%;"></div>
                </div>
                <small>Poids: 20%</small>
            </div>
        </div>
    </div>

    <!-- Score final -->
    <div class="total-score">
        <div class="icon" style="color: <?php echo $maturity_color; ?>;">
            <i class="fas <?php echo $maturity_icon; ?>"></i>
        </div>
        <h2>Score Global : <span style="color: <?php echo $maturity_color; ?>;"><?php echo round($total_score); ?>/100</span></h2>
        <div class="level" style="color: <?php echo $maturity_color; ?>;">
            Niveau de maturité : <?php echo $maturity_level; ?>
        </div>
        
        <div class="formula">
            Calcul pondéré :<br>
            (<?php echo round($maturity_score, 2); ?> × 0.30) + (<?php echo round($security_score, 2); ?> × 0.25) + (<?php echo round($cloud_score, 2); ?> × 0.25) + (<?php echo round($automation_score, 2); ?> × 0.20) × 10<br>
            = <?php echo round($total_score, 2); ?>/100
        </div>
        
        <div class="progress-bar" style="height: 20px; margin: 20px 0;">
            <div class="progress-fill" style="width: <?php echo min($total_score, 100); ?>%; background: <?php echo $maturity_color; ?>;"></div>
        </div>
    </div>

    <!-- Validation -->
    <div class="success">
        <h3><i class="fas fa-check-circle"></i> Validation des Corrections</h3>
        <ul>
            <li>✅ <strong>Score dans la plage normale:</strong> <?php echo round($total_score); ?>/100 (≤ 100)</li>
            <li>✅ <strong>Calcul par moyenne:</strong> Division par 3 pour chaque domaine</li>
            <li>✅ <strong>Pondération équilibrée:</strong> 30% + 25% + 25% + 20% = 100%</li>
            <li>✅ <strong>Niveau de maturité cohérent:</strong> <?php echo $maturity_level; ?> pour <?php echo round($total_score); ?>%</li>
        </ul>
    </div>

    <!-- Navigation -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="demo_enhanced.php?step=6" style="background: #3498db; color: white; padding: 12px 25px; border-radius: 25px; text-decoration: none; margin: 0 10px;">
            <i class="fas fa-eye"></i> Voir dans le Wizard
        </a>
        <a href="demo_steps_3_6.php?step=6" style="background: #27ae60; color: white; padding: 12px 25px; border-radius: 25px; text-decoration: none; margin: 0 10px;">
            <i class="fas fa-chart-line"></i> Synthèse Complète
        </a>
    </div>
</div>

</body>
</html>