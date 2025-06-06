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
 * \file       demo_modern.php
 * \ingroup    auditdigital
 * \brief      Demo page for modern audit digital features
 */

// Load Dolibarr environment
$res = 0;

// Méthode 1: Chemins relatifs standards
$paths_to_try = array(
    "../main.inc.php",
    "../../main.inc.php",
    "../../../main.inc.php",
    "../../../../main.inc.php",
    "../../../../../main.inc.php"
);

foreach ($paths_to_try as $path) {
    if (!$res && file_exists($path)) {
        $res = @include $path;
        if ($res) break;
    }
}

if (!$res) {
    die('Error: Cannot load Dolibarr main.inc.php');
}

// Load translation files
$langs->loadLangs(array("main", "companies", "projects"));

// Get parameters
$action = GETPOST('action', 'aZ09');

// Security check
if (!$user->id || $user->socid > 0) {
    accessforbidden();
}

/*
 * View
 */

$title = 'Démonstration - Audit Digital Moderne';
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, array(
    '/custom/auditdigital/css/auditdigital-modern.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/chart.js'
));

?>

<style>
.demo-section {
    margin: 30px 0;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.demo-title {
    color: #0066cc;
    font-size: 1.8rem;
    margin-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.demo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.demo-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #0066cc;
}

.feature-showcase {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 16px;
    text-align: center;
    margin: 20px 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #0066cc;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.demo-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin: 20px 0;
}

.chart-demo {
    height: 300px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 20px 0;
}
</style>

<div class="audit-wizard-modern">
    <!-- Header de démonstration -->
    <div class="audit-wizard-header-modern">
        <h1>🚀 Démonstration - Audit Digital Moderne</h1>
        <p>Découvrez les nouvelles fonctionnalités et l'interface modernisée du module AuditDigital</p>
    </div>
    
    <!-- Section des fonctionnalités -->
    <div class="audit-form-container-modern">
        
        <!-- Présentation générale -->
        <div class="feature-showcase">
            <h2>Interface Nouvelle Génération</h2>
            <p>Une expérience utilisateur repensée avec des animations fluides, des graphiques interactifs et une interface moderne</p>
            <div class="demo-buttons">
                <a href="/custom/auditdigital/wizard/modern.php" class="btn-modern">
                    <i class="fas fa-play"></i> Tester le Wizard Moderne
                </a>
                <a href="/custom/auditdigital/wizard/index.php" class="btn-modern btn-secondary-modern">
                    <i class="fas fa-eye"></i> Comparer avec l'ancienne version
                </a>
            </div>
        </div>
        
        <!-- Statistiques de démonstration -->
        <div class="demo-section">
            <h3 class="demo-title">📊 Aperçu des Améliorations</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">6</div>
                    <div class="stat-label">Étapes du Wizard</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">4</div>
                    <div class="stat-label">Domaines d'Évaluation</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Interface Responsive</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">∞</div>
                    <div class="stat-label">Commentaires & Pièces Jointes</div>
                </div>
            </div>
        </div>
        
        <!-- Démonstration des cards cliquables -->
        <div class="demo-section">
            <h3 class="demo-title">🎯 Cards Cliquables Modernes</h3>
            <p>Remplace les anciens radio buttons par des cards interactives avec animations</p>
            
            <div class="audit-cards-container">
                <div class="audit-option-card" onclick="demoSelectOption(this)">
                    <div class="card-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="card-content">
                        <h4>TPE/PME</h4>
                        <p>Entreprise de moins de 250 employés avec des besoins spécifiques</p>
                    </div>
                    <div class="check-mark">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                
                <div class="audit-option-card" onclick="demoSelectOption(this)">
                    <div class="card-icon">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <div class="card-content">
                        <h4>Collectivité</h4>
                        <p>Administration publique locale avec contraintes réglementaires</p>
                    </div>
                    <div class="check-mark">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                
                <div class="audit-option-card" onclick="demoSelectOption(this)">
                    <div class="card-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="card-content">
                        <h4>Startup</h4>
                        <p>Jeune entreprise innovante en croissance rapide</p>
                    </div>
                    <div class="check-mark">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Démonstration du stepper -->
        <div class="demo-section">
            <h3 class="demo-title">📋 Stepper Visuel Interactif</h3>
            <p>Navigation claire et intuitive entre les étapes de l'audit</p>
            
            <div class="audit-stepper">
                <div class="step completed">
                    <div class="step-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <span>Informations</span>
                </div>
                <div class="step-line completed"></div>
                
                <div class="step active">
                    <div class="step-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span>Maturité</span>
                </div>
                <div class="step-line active"></div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <span>Sécurité</span>
                </div>
                <div class="step-line"></div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <span>Cloud</span>
                </div>
                <div class="step-line"></div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <span>Automatisation</span>
                </div>
                <div class="step-line"></div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <span>Synthèse</span>
                </div>
            </div>
        </div>
        
        <!-- Démonstration des scores -->
        <div class="demo-section">
            <h3 class="demo-title">📈 Visualisation des Scores</h3>
            <p>Affichage en temps réel des scores avec codes couleur et animations</p>
            
            <div class="audit-score-container">
                <div class="audit-score-card">
                    <div class="audit-score-title">Score Global</div>
                    <div class="audit-score-value audit-score-good">72%</div>
                </div>
                <div class="audit-score-card">
                    <div class="audit-score-title">Maturité Digitale</div>
                    <div class="audit-score-value audit-score-excellent">85%</div>
                </div>
                <div class="audit-score-card">
                    <div class="audit-score-title">Cybersécurité</div>
                    <div class="audit-score-value audit-score-average">45%</div>
                </div>
                <div class="audit-score-card">
                    <div class="audit-score-title">Cloud</div>
                    <div class="audit-score-value audit-score-good">68%</div>
                </div>
                <div class="audit-score-card">
                    <div class="audit-score-title">Automatisation</div>
                    <div class="audit-score-value audit-score-poor">32%</div>
                </div>
            </div>
        </div>
        
        <!-- Démonstration des graphiques -->
        <div class="demo-section">
            <h3 class="demo-title">📊 Graphiques Interactifs avec Chart.js</h3>
            <p>Visualisation avancée des données avec graphiques radar et barres</p>
            
            <div class="demo-grid">
                <div class="chart-container">
                    <h4 class="chart-title">Radar des Compétences</h4>
                    <canvas id="demoRadarChart" width="400" height="300"></canvas>
                </div>
                <div class="chart-container">
                    <h4 class="chart-title">Progression par Domaine</h4>
                    <canvas id="demoBarChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Démonstration des commentaires -->
        <div class="demo-section">
            <h3 class="demo-title">💬 Système de Commentaires Enrichi</h3>
            <p>Ajout de commentaires et pièces jointes pour chaque question</p>
            
            <div class="comment-section">
                <button type="button" class="comment-toggle-btn" onclick="toggleDemoComment()">
                    <i class="fas fa-comment"></i> Ajouter un commentaire
                </button>
                <div id="demo-comment-box" class="comment-box" style="display:none;">
                    <textarea class="comment-textarea" placeholder="Vos remarques détaillées sur cette question...">Exemple de commentaire : Notre infrastructure actuelle nécessite une mise à jour importante, notamment au niveau de la sécurité réseau.</textarea>
                    <div class="file-upload">
                        <label class="file-upload-btn">
                            <i class="fas fa-paperclip"></i> Joindre un fichier
                            <input type="file" hidden multiple>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Démonstration des fonctionnalités métier -->
        <div class="demo-section">
            <h3 class="demo-title">🎯 Fonctionnalités Métier Avancées</h3>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h4><i class="fas fa-calculator"></i> Calcul ROI Automatique</h4>
                    <p>Estimation automatique du retour sur investissement basée sur les améliorations suggérées</p>
                    <ul>
                        <li>Analyse coût/bénéfice</li>
                        <li>Période de retour</li>
                        <li>ROI sur 3 ans</li>
                    </ul>
                </div>
                
                <div class="demo-card">
                    <h4><i class="fas fa-road"></i> Roadmap d'Implémentation</h4>
                    <p>Plan d'action structuré en phases avec priorisation intelligente</p>
                    <ul>
                        <li>Actions rapides (1-3 mois)</li>
                        <li>Projets structurants (3-12 mois)</li>
                        <li>Vision long terme (12+ mois)</li>
                    </ul>
                </div>
                
                <div class="demo-card">
                    <h4><i class="fas fa-file-export"></i> Export Multi-format</h4>
                    <p>Export des données d'audit en plusieurs formats</p>
                    <ul>
                        <li>JSON pour intégration</li>
                        <li>CSV pour analyse</li>
                        <li>XML pour échange</li>
                    </ul>
                </div>
                
                <div class="demo-card">
                    <h4><i class="fas fa-file-pdf"></i> PDF Moderne</h4>
                    <p>Rapport PDF avec design moderne et graphiques intégrés</p>
                    <ul>
                        <li>Page de garde professionnelle</li>
                        <li>Synthèse exécutive</li>
                        <li>Graphiques et jauges visuelles</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Boutons d'action -->
        <div class="demo-section">
            <h3 class="demo-title">🚀 Tester les Fonctionnalités</h3>
            <div class="demo-buttons">
                <a href="/custom/auditdigital/wizard/modern.php" class="btn-modern">
                    <i class="fas fa-magic"></i> Wizard Moderne
                </a>
                <a href="/custom/auditdigital/audit_list.php" class="btn-modern btn-secondary-modern">
                    <i class="fas fa-list"></i> Liste des Audits
                </a>
                <button onclick="generateDemoData()" class="btn-modern btn-success-modern">
                    <i class="fas fa-database"></i> Générer Données de Démo
                </button>
                <button onclick="showTechnicalInfo()" class="btn-modern btn-secondary-modern">
                    <i class="fas fa-info-circle"></i> Infos Techniques
                </button>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal pour les infos techniques -->
<div id="technicalModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; max-width: 600px; width: 90%;">
        <h3>🔧 Informations Techniques</h3>
        <div style="margin: 20px 0;">
            <h4>Technologies Utilisées :</h4>
            <ul>
                <li><strong>Frontend :</strong> CSS3 avec variables, animations, glassmorphism</li>
                <li><strong>JavaScript :</strong> ES6+ avec classes, async/await</li>
                <li><strong>Charts :</strong> Chart.js pour les graphiques interactifs</li>
                <li><strong>Icons :</strong> Font Awesome 6.0</li>
                <li><strong>Backend :</strong> PHP 7+ avec POO avancée</li>
                <li><strong>Database :</strong> MySQL avec tables optimisées</li>
            </ul>
            
            <h4>Nouvelles Fonctionnalités :</h4>
            <ul>
                <li>Interface responsive avec design moderne</li>
                <li>Système de commentaires avec pièces jointes</li>
                <li>Calcul ROI automatique et roadmap</li>
                <li>Export multi-format (JSON, CSV, XML)</li>
                <li>PDF avec graphiques intégrés</li>
                <li>Auto-save et restauration de session</li>
            </ul>
        </div>
        <button onclick="closeTechnicalModal()" class="btn-modern">Fermer</button>
    </div>
</div>

<script>
// Démonstration des fonctionnalités JavaScript

function demoSelectOption(element) {
    // Retirer la sélection des autres cards
    const cards = element.parentNode.querySelectorAll('.audit-option-card');
    cards.forEach(card => card.classList.remove('selected', 'bounce-in'));
    
    // Sélectionner la card cliquée
    element.classList.add('selected', 'bounce-in');
    
    // Vibration tactile si supportée
    if ('vibrate' in navigator) {
        navigator.vibrate(50);
    }
    
    // Notification de feedback
    showNotification('Option sélectionnée avec succès !', 'success');
}

function toggleDemoComment() {
    const commentBox = document.getElementById('demo-comment-box');
    if (commentBox.style.display === 'none') {
        commentBox.style.display = 'block';
        commentBox.style.animation = 'slideDown 0.3s ease-out';
    } else {
        commentBox.style.display = 'none';
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function generateDemoData() {
    showNotification('Génération des données de démonstration...', 'info');
    
    // Simuler la génération de données
    setTimeout(() => {
        showNotification('Données de démonstration générées avec succès !', 'success');
    }, 2000);
}

function showTechnicalInfo() {
    document.getElementById('technicalModal').style.display = 'block';
}

function closeTechnicalModal() {
    document.getElementById('technicalModal').style.display = 'none';
}

// Initialisation des graphiques de démonstration
document.addEventListener('DOMContentLoaded', function() {
    // Graphique radar
    const radarCtx = document.getElementById('demoRadarChart');
    if (radarCtx) {
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: ['Maturité Digitale', 'Cybersécurité', 'Cloud', 'Automatisation'],
                datasets: [{
                    label: 'Score Actuel',
                    data: [85, 45, 68, 32],
                    backgroundColor: 'rgba(0, 102, 204, 0.2)',
                    borderColor: 'rgba(0, 102, 204, 1)',
                    pointBackgroundColor: 'rgba(0, 102, 204, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(0, 102, 204, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Graphique en barres
    const barCtx = document.getElementById('demoBarChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Maturité Digitale', 'Cybersécurité', 'Cloud', 'Automatisation'],
                datasets: [{
                    label: 'Score (%)',
                    data: [85, 45, 68, 32],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(253, 126, 20, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(253, 126, 20, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});

// Fermer le modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    const modal = document.getElementById('technicalModal');
    if (e.target === modal) {
        closeTechnicalModal();
    }
});
</script>

<?php

llxFooter();

?>