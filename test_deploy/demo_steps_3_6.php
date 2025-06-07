<?php
/**
 * Page de démonstration spécifique pour les étapes 3-6
 * Avec mise en page corrigée
 */

// Simulation des variables Dolibarr
$step = $_GET['step'] ?? 3;
$socid = $_GET['socid'] ?? 1;

// Simulation d'une société
$societe = (object)[
    'name' => 'Société de Démonstration',
    'town' => 'Paris',
    'country' => 'France',
    'effectif' => '50'
];

// Simulation des fonctions Dolibarr
function newToken() { return 'demo_token'; }
function GETPOST($param, $type = '') { return $_GET[$param] ?? $_POST[$param] ?? ''; }

// Démarrer la session pour les tests
session_start();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Démo Étapes 3-6 - Audit Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS optimisé pour les étapes 3-6 */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #8e44ad;
            --gradient-primary: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --gradient-success: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            --shadow-light: 0 2px 15px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.12);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: #2c3e50;
        }

        .enhanced-wizard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header professionnel */
        .wizard-header {
            background: var(--gradient-primary);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            box-shadow: var(--shadow-medium);
            text-align: center;
        }

        .wizard-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .wizard-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Contenu amélioré */
        .wizard-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
        }

        .step-container {
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .step-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }

        .step-header h2 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .step-header p {
            font-size: 1.1rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Questions avec échelle 1-10 - MISE EN PAGE CORRIGÉE */
        .question-section {
            margin-bottom: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: var(--border-radius);
            border-left: 5px solid var(--secondary-color);
        }

        .question-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .question-icon {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-right: 15px;
            margin-top: 5px;
        }

        .question-title {
            flex: 1;
        }

        .question-title h3 {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .question-title p {
            color: #6c757d;
            font-size: 1rem;
            line-height: 1.5;
        }

        /* ÉCHELLE CORRIGÉE - Textes au-dessus des boutons */
        .rating-scale {
            margin: 25px 0;
            padding: 25px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .scale-labels {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 25px;
            padding: 0 10px;
        }

        .scale-label {
            font-size: 0.85rem;
            color: #495057;
            text-align: center;
            flex: 1;
            padding: 0 8px;
            font-weight: 500;
        }

        .scale-label small {
            display: block;
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 3px;
            font-weight: 400;
        }

        .scale-options {
            display: flex;
            justify-content: space-between;
            width: 100%;
            padding: 0 10px;
        }

        .scale-option {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid #e9ecef;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            color: #495057;
        }

        .scale-option:hover {
            border-color: var(--secondary-color);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .scale-option.selected {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
            transform: scale(1.15);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .scale-option.selected::after {
            content: '✓';
            position: absolute;
            top: -8px;
            right: -8px;
            width: 20px;
            height: 20px;
            background: var(--success-color);
            border-radius: 50%;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Zone de commentaires */
        .comment-section {
            margin-top: 25px;
        }

        .comment-section label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .comment-textarea {
            width: 100%;
            min-height: 100px;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            transition: var(--transition);
            background: #fafafa;
        }

        .comment-textarea:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            background: white;
        }

        /* Navigation */
        .wizard-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 25px 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .nav-btn {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            justify-content: center;
            text-decoration: none;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
        }

        .nav-btn.prev-btn {
            background: #6c757d;
        }

        .nav-btn.create-btn {
            background: var(--gradient-success);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .wizard-content {
                padding: 25px 15px;
            }

            .scale-options {
                flex-wrap: wrap;
                gap: 8px;
                justify-content: center;
            }

            .scale-option {
                width: 40px;
                height: 40px;
                font-size: 0.9rem;
            }

            .scale-labels {
                flex-direction: column;
                gap: 5px;
                margin-bottom: 20px;
            }

            .scale-label {
                text-align: center;
                padding: 5px;
            }
        }

        /* Notice de démonstration */
        .demo-notice {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .demo-notice h4 {
            color: #1976d2;
            margin-bottom: 5px;
        }

        .demo-notice p {
            color: #1976d2;
            margin: 0;
        }

        /* Stepper simple */
        .step-indicator {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .step-indicator h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .step-indicator p {
            color: #6c757d;
        }
    </style>
</head>
<body>

<div class="enhanced-wizard-container">
    <!-- Notice de démonstration -->
    <div class="demo-notice">
        <h4><i class="fas fa-info-circle"></i> Démonstration Étapes 3-6</h4>
        <p>Test de la mise en page corrigée avec textes au-dessus des boutons</p>
    </div>

    <!-- Header -->
    <div class="wizard-header">
        <h1><i class="fas fa-chart-line"></i> Audit Digital - Étape <?php echo $step; ?>/6</h1>
        <p>Démonstration des étapes avancées avec mise en page optimisée</p>
    </div>

    <!-- Indicateur d'étape -->
    <div class="step-indicator">
        <h3>
            <?php 
            $step_titles = [
                3 => 'Cybersécurité',
                4 => 'Cloud & Infrastructure', 
                5 => 'Automatisation',
                6 => 'Synthèse'
            ];
            echo $step_titles[$step] ?? 'Étape inconnue';
            ?>
        </h3>
        <p>Étape <?php echo $step; ?> sur 6</p>
    </div>

    <!-- Contenu de l'étape -->
    <div class="wizard-content">
        <?php if ($step == 3): ?>
            <!-- Étape 3: Cybersécurité -->
            <div class="step-container">
                <div class="step-header">
                    <h2><i class="fas fa-shield-alt"></i> Cybersécurité</h2>
                    <p>Évaluons votre niveau de protection et de sécurité informatique</p>
                </div>

                <!-- Question 1: Protection des données -->
                <div class="question-section">
                    <div class="question-header">
                        <div class="question-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="question-title">
                            <h3>Niveau de protection des données</h3>
                            <p>Comment évaluez-vous votre niveau de sécurité informatique actuel ? Considérez vos antivirus, firewall, politiques de sécurité et formation des utilisateurs.</p>
                        </div>
                    </div>

                    <div class="rating-scale">
                        <div class="scale-labels">
                            <div class="scale-label">Très faible<br><small>Pas de protection</small></div>
                            <div class="scale-label">Faible<br><small>Antivirus basique</small></div>
                            <div class="scale-label">Moyen<br><small>Firewall + antivirus</small></div>
                            <div class="scale-label">Bon<br><small>Sécurité renforcée</small></div>
                            <div class="scale-label">Excellent<br><small>Sécurité avancée</small></div>
                        </div>
                        <div class="scale-options">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_security_level')">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="comment-section">
                        <label for="comment_security_level">Commentaires détaillés (optionnel) :</label>
                        <textarea id="comment_security_level" class="comment-textarea" 
                            placeholder="Décrivez vos mesures de sécurité actuelles : antivirus utilisé, firewall, politiques de mots de passe, formation des utilisateurs, incidents de sécurité rencontrés..."></textarea>
                    </div>
                </div>

                <!-- Question 2: Conformité RGPD -->
                <div class="question-section">
                    <div class="question-header">
                        <div class="question-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="question-title">
                            <h3>Conformité RGPD et protection des données personnelles</h3>
                            <p>Où en êtes-vous dans votre mise en conformité RGPD ? Avez-vous un registre des traitements, un DPO, des procédures de gestion des données ?</p>
                        </div>
                    </div>

                    <div class="rating-scale">
                        <div class="scale-labels">
                            <div class="scale-label">Très faible<br><small>Pas de démarche</small></div>
                            <div class="scale-label">Faible<br><small>Sensibilisation</small></div>
                            <div class="scale-label">Moyen<br><small>En cours</small></div>
                            <div class="scale-label">Bon<br><small>Largement conforme</small></div>
                            <div class="scale-label">Excellent<br><small>Totalement conforme</small></div>
                        </div>
                        <div class="scale-options">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_rgpd_compliance')">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="comment-section">
                        <label for="comment_rgpd_compliance">Commentaires détaillés (optionnel) :</label>
                        <textarea id="comment_rgpd_compliance" class="comment-textarea" 
                            placeholder="Décrivez votre démarche RGPD : registre des traitements, DPO désigné, procédures de consentement, gestion des demandes d'accès, formation des équipes..."></textarea>
                    </div>
                </div>

                <!-- Question 3: Stratégie de sauvegarde -->
                <div class="question-section">
                    <div class="question-header">
                        <div class="question-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="question-title">
                            <h3>Stratégie de sauvegarde et continuité d'activité</h3>
                            <p>Comment gérez-vous la sauvegarde de vos données critiques ? Avez-vous un plan de continuité d'activité en cas de sinistre ?</p>
                        </div>
                    </div>

                    <div class="rating-scale">
                        <div class="scale-labels">
                            <div class="scale-label">Très faible<br><small>Pas de sauvegarde</small></div>
                            <div class="scale-label">Faible<br><small>Sauvegarde manuelle</small></div>
                            <div class="scale-label">Moyen<br><small>Sauvegarde automatique</small></div>
                            <div class="scale-label">Bon<br><small>Multi-supports</small></div>
                            <div class="scale-label">Excellent<br><small>Stratégie complète</small></div>
                        </div>
                        <div class="scale-options">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_backup_strategy')">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="comment-section">
                        <label for="comment_backup_strategy">Commentaires détaillés (optionnel) :</label>
                        <textarea id="comment_backup_strategy" class="comment-textarea" 
                            placeholder="Décrivez votre stratégie de sauvegarde : fréquence, supports utilisés (cloud, disques externes), tests de restauration, plan de continuité d'activité..."></textarea>
                    </div>
                </div>
            </div>

        <?php elseif ($step == 4): ?>
            <!-- Étape 4: Cloud & Infrastructure -->
            <div class="step-container">
                <div class="step-header">
                    <h2><i class="fas fa-cloud"></i> Cloud & Infrastructure</h2>
                    <p>Évaluons votre infrastructure informatique et votre adoption des technologies cloud</p>
                </div>

                <!-- Question 1: Adoption du cloud -->
                <div class="question-section">
                    <div class="question-header">
                        <div class="question-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="question-title">
                            <h3>Niveau d'adoption des technologies cloud</h3>
                            <p>Dans quelle mesure utilisez-vous les services cloud (SaaS, PaaS, IaaS) ? Vos données et applications sont-elles hébergées dans le cloud ?</p>
                        </div>
                    </div>

                    <div class="rating-scale">
                        <div class="scale-labels">
                            <div class="scale-label">Très faible<br><small>Serveurs locaux</small></div>
                            <div class="scale-label">Faible<br><small>Quelques services</small></div>
                            <div class="scale-label">Moyen<br><small>Cloud hybride</small></div>
                            <div class="scale-label">Bon<br><small>Largement cloud</small></div>
                            <div class="scale-label">Excellent<br><small>Cloud natif</small></div>
                        </div>
                        <div class="scale-options">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_cloud_adoption')">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="comment-section">
                        <label for="comment_cloud_adoption">Commentaires détaillés (optionnel) :</label>
                        <textarea id="comment_cloud_adoption" class="comment-textarea" 
                            placeholder="Décrivez vos services cloud actuels : fournisseurs (AWS, Azure, Google Cloud), types de services utilisés, stratégie de migration, avantages constatés..."></textarea>
                    </div>
                </div>

                <!-- Question 2: Mobilité et télétravail -->
                <div class="question-section">
                    <div class="question-header">
                        <div class="question-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="question-title">
                            <h3>Mobilité et capacités de télétravail</h3>
                            <p>Comment gérez-vous la mobilité de vos équipes ? Vos collaborateurs peuvent-ils travailler efficacement à distance ?</p>
                        </div>
                    </div>

                    <div class="rating-scale">
                        <div class="scale-labels">
                            <div class="scale-label">Très faible<br><small>Travail sur site</small></div>
                            <div class="scale-label">Faible<br><small>Accès limité</small></div>
                            <div class="scale-label">Moyen<br><small>VPN occasionnel</small></div>
                            <div class="scale-label">Bon<br><small>Mobilité fluide</small></div>
                            <div class="scale-label">Excellent<br><small>Nomadisme complet</small></div>
                        </div>
                        <div class="scale-options">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_mobility')">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="comment-section">
                        <label for="comment_mobility">Commentaires détaillés (optionnel) :</label>
                        <textarea id="comment_mobility" class="comment-textarea" 
                            placeholder="Décrivez vos outils de mobilité : VPN, applications mobiles, accès distant, équipements portables, politique de télétravail..."></textarea>
                    </div>
                </div>

                <!-- Question 3: Infrastructure technique -->
                <div class="question-section">
                    <div class="question-header">
                        <div class="question-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="question-title">
                            <h3>Qualité et modernité de l'infrastructure technique</h3>
                            <p>Comment évaluez-vous votre infrastructure IT actuelle ? Vos serveurs, réseaux et équipements sont-ils modernes et performants ?</p>
                        </div>
                    </div>

                    <div class="rating-scale">
                        <div class="scale-labels">
                            <div class="scale-label">Très faible<br><small>Infrastructure obsolète</small></div>
                            <div class="scale-label">Faible<br><small>Matériel vieillissant</small></div>
                            <div class="scale-label">Moyen<br><small>Infrastructure correcte</small></div>
                            <div class="scale-label">Bon<br><small>Infrastructure moderne</small></div>
                            <div class="scale-label">Excellent<br><small>Infrastructure optimale</small></div>
                        </div>
                        <div class="scale-options">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_infrastructure')">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="comment-section">
                        <label for="comment_infrastructure">Commentaires détaillés (optionnel) :</label>
                        <textarea id="comment_infrastructure" class="comment-textarea" 
                            placeholder="Décrivez votre infrastructure : âge des serveurs, qualité du réseau, maintenance, virtualisation, monitoring, plans de renouvellement..."></textarea>
                    </div>
                </div>
            </div>

        <?php elseif ($step == 5): ?>
            <!-- Étape 5: Automatisation -->
            <div class="step-container">
                <div class="step-header">
                    <h2><i class="fas fa-robot"></i> Automatisation</h2>
                    <p>Évaluons votre niveau d'automatisation et d'optimisation des processus métier</p>
                </div>

                <!-- Question 1: Automatisation des processus -->
                <div class="question-section">
                    <div class="question-header">
                        <div class="question-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="question-title">
                            <h3>Automatisation des processus métier</h3>
                            <p>Dans quelle mesure vos processus métier sont-ils automatisés ? Utilisez-vous des workflows, de la RPA ou de l'intelligence artificielle ?</p>
                        </div>
                    </div>

                    <div class="rating-scale">
                        <div class="scale-labels">
                            <div class="scale-label">Très faible<br><small>Tout manuel</small></div>
                            <div class="scale-label">Faible<br><small>Quelques automatismes</small></div>
                            <div class="scale-label">Moyen<br><small>Processus partiels</small></div>
                            <div class="scale-label">Bon<br><small>Largement automatisé</small></div>
                            <div class="scale-label">Excellent<br><small>IA et workflows</small></div>
                        </div>
                        <div class="scale-options">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_automation_level')">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="comment-section">
                        <label for="comment_automation_level">Commentaires détaillés (optionnel) :</label>
                        <textarea id="comment_automation_level" class="comment-textarea" 
                            placeholder="Décrivez vos processus automatisés : workflows métier, RPA, IA, automatisation des tâches répétitives, gains de productivité constatés..."></textarea>
                    </div>
                </div>

                <!-- Question 2: Outils de collaboration -->
                <div class="question-section">
                    <div class="question-header">
                        <div class="question-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="question-title">
                            <h3>Outils de collaboration et communication</h3>
                            <p>Comment évaluez-vous vos outils de travail collaboratif ? Vos équipes disposent-elles d'outils modernes de communication et partage ?</p>
                        </div>
                    </div>

                    <div class="rating-scale">
                        <div class="scale-labels">
                            <div class="scale-label">Très faible<br><small>Email uniquement</small></div>
                            <div class="scale-label">Faible<br><small>Outils basiques</small></div>
                            <div class="scale-label">Moyen<br><small>Suite collaborative</small></div>
                            <div class="scale-label">Bon<br><small>Outils intégrés</small></div>
                            <div class="scale-label">Excellent<br><small>Écosystème complet</small></div>
                        </div>
                        <div class="scale-options">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_collaboration_tools')">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="comment-section">
                        <label for="comment_collaboration_tools">Commentaires détaillés (optionnel) :</label>
                        <textarea id="comment_collaboration_tools" class="comment-textarea" 
                            placeholder="Décrivez vos outils de collaboration : messagerie instantanée, visioconférence, partage de documents, gestion de projets, réseaux sociaux d'entreprise..."></textarea>
                    </div>
                </div>

                <!-- Question 3: Analyse de données -->
                <div class="question-section">
                    <div class="question-header">
                        <div class="question-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="question-title">
                            <h3>Analyse et exploitation des données</h3>
                            <p>Comment exploitez-vous vos données pour optimiser votre activité ? Disposez-vous d'outils de Business Intelligence ou d'analyse prédictive ?</p>
                        </div>
                    </div>

                    <div class="rating-scale">
                        <div class="scale-labels">
                            <div class="scale-label">Très faible<br><small>Pas d'analyse</small></div>
                            <div class="scale-label">Faible<br><small>Rapports basiques</small></div>
                            <div class="scale-label">Moyen<br><small>Tableaux de bord</small></div>
                            <div class="scale-label">Bon<br><small>Analytics avancés</small></div>
                            <div class="scale-label">Excellent<br><small>BI et prédictif</small></div>
                        </div>
                        <div class="scale-options">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="scale-option" data-value="<?php echo $i; ?>" onclick="selectRating(this, 'audit_data_analysis')">
                                    <?php echo $i; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="comment-section">
                        <label for="comment_data_analysis">Commentaires détaillés (optionnel) :</label>
                        <textarea id="comment_data_analysis" class="comment-textarea" 
                            placeholder="Décrivez vos outils d'analyse : KPI suivis, tableaux de bord, outils BI, analyse prédictive, aide à la décision, ROI des données..."></textarea>
                    </div>
                </div>
            </div>

        <?php elseif ($step == 6): ?>
            <!-- Étape 6: Synthèse -->
            <div class="step-container">
                <div class="step-header">
                    <h2><i class="fas fa-chart-line"></i> Synthèse & Recommandations</h2>
                    <p>Voici le résumé de votre audit digital avec recommandations personnalisées</p>
                </div>

                <?php
                // Simulation de scores pour la démonstration
                $scores_demo = [
                    'Maturité Digitale' => 75,
                    'Cybersécurité' => 60,
                    'Cloud & Infrastructure' => 45,
                    'Automatisation' => 55
                ];
                $total_score = array_sum($scores_demo) / count($scores_demo);
                
                // Déterminer le niveau de maturité
                $maturity_level = 'Débutant';
                $maturity_color = '#dc3545';
                $maturity_icon = 'fa-seedling';
                
                if ($total_score >= 80) {
                    $maturity_level = 'Expert';
                    $maturity_color = '#28a745';
                    $maturity_icon = 'fa-trophy';
                } elseif ($total_score >= 65) {
                    $maturity_level = 'Avancé';
                    $maturity_color = '#17a2b8';
                    $maturity_icon = 'fa-rocket';
                } elseif ($total_score >= 50) {
                    $maturity_level = 'Intermédiaire';
                    $maturity_color = '#ffc107';
                    $maturity_icon = 'fa-chart-line';
                }
                ?>

                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 4rem; color: <?php echo $maturity_color; ?>; margin-bottom: 20px;">
                        <i class="fas <?php echo $maturity_icon; ?>"></i>
                    </div>
                    <h3>Audit Digital Complété !</h3>
                    <p>Votre évaluation de maturité digitale est prête.</p>
                    
                    <div style="margin: 30px 0; padding: 30px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <h4 style="font-size: 1.8rem; margin-bottom: 15px;">
                            Score global : <span style="color: <?php echo $maturity_color; ?>; font-weight: bold;"><?php echo round($total_score); ?>/100</span>
                        </h4>
                        <p style="font-size: 1.2rem; margin-bottom: 20px;">
                            Niveau de maturité : <strong style="color: <?php echo $maturity_color; ?>;"><?php echo $maturity_level; ?></strong>
                        </p>
                        
                        <!-- Barre de progression -->
                        <div style="background: #e9ecef; border-radius: 10px; height: 20px; margin: 20px 0; overflow: hidden;">
                            <div style="background: <?php echo $maturity_color; ?>; height: 100%; width: <?php echo min($total_score, 100); ?>%; transition: width 0.8s ease-in-out; border-radius: 10px;"></div>
                        </div>
                    </div>
                    
                    <!-- Détail des scores par domaine -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
                        <?php foreach ($scores_demo as $domain => $score): ?>
                            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                <h5 style="margin-bottom: 10px; color: #2c3e50;"><?php echo $domain; ?></h5>
                                <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                    <?php echo $score; ?>/100
                                </div>
                                <div style="background: #e9ecef; border-radius: 5px; height: 8px; margin-top: 10px; overflow: hidden;">
                                    <div style="background: var(--primary-color); height: 100%; width: <?php echo min($score, 100); ?>%; border-radius: 5px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Recommandations rapides -->
                    <div style="margin-top: 40px; padding: 25px; background: #fff3cd; border-radius: 12px; border-left: 5px solid #ffc107;">
                        <h5 style="color: #856404; margin-bottom: 15px;">
                            <i class="fas fa-lightbulb"></i> Recommandations prioritaires
                        </h5>
                        <div style="text-align: left; color: #856404;">
                            <p>• Renforcer la sécurité informatique et la conformité RGPD</p>
                            <p>• Accélérer l'adoption des technologies cloud</p>
                            <p>• Développer l'automatisation des processus métier</p>
                            <p>• Améliorer les outils de collaboration et d'analyse</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Étape par défaut -->
            <div class="step-container">
                <div class="step-header">
                    <h2><i class="fas fa-question"></i> Étape non définie</h2>
                    <p>Cette étape n'est pas encore configurée</p>
                </div>
                <div style="text-align: center; padding: 60px;">
                    <p>Veuillez sélectionner une étape entre 3 et 6.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="wizard-navigation">
            <?php if ($step > 3): ?>
                <a href="?step=<?php echo $step - 1; ?>" class="nav-btn prev-btn">
                    <i class="fas fa-arrow-left"></i>
                    <span>Précédent</span>
                </a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <?php if ($step < 6): ?>
                <a href="?step=<?php echo $step + 1; ?>" class="nav-btn next-btn">
                    <span>Suivant</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            <?php else: ?>
                <button type="button" class="nav-btn create-btn" onclick="alert('Démonstration terminée ! Dans la version complète, ceci créerait l\'audit avec tous les scores calculés.')">
                    <i class="fas fa-rocket"></i>
                    <span>Créer l'Audit</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation rapide -->
    <div style="text-align: center; margin-top: 20px;">
        <p style="color: #6c757d; margin-bottom: 10px;">Navigation rapide :</p>
        <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
            <?php for ($i = 3; $i <= 6; $i++): ?>
                <a href="?step=<?php echo $i; ?>" 
                   style="padding: 8px 15px; background: <?php echo $i == $step ? 'var(--secondary-color)' : '#e9ecef'; ?>; 
                          color: <?php echo $i == $step ? 'white' : '#495057'; ?>; 
                          text-decoration: none; border-radius: 20px; font-size: 0.9rem;">
                    Étape <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
// JavaScript pour la démonstration
function selectRating(element, fieldName) {
    // Retirer la sélection précédente
    const parent = element.parentNode;
    const options = parent.querySelectorAll('.scale-option');
    options.forEach(option => {
        option.classList.remove('selected');
    });
    
    // Ajouter la sélection avec animation
    element.classList.add('selected');
    
    // Vibration tactile sur mobile
    if ('vibrate' in navigator) {
        navigator.vibrate(50);
    }
    
    showNotification('Note attribuée : ' + element.dataset.value + '/10', 'success');
}

// Notifications
function showNotification(message, type = 'success') {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? 'var(--success-color)' : 'var(--danger-color)'};
        color: white;
        padding: 15px 20px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-medium);
        z-index: 1000;
        animation: slideInRight 0.3s ease-out;
        max-width: 300px;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> 
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Suppression automatique
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Démo étapes 3-6 initialisée - Étape', <?php echo $step; ?>);
    showNotification('Démo étapes 3-6 chargée avec mise en page corrigée', 'success');
});
</script>

</body>
</html>