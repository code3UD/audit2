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
 * JavaScript moderne pour AuditDigital Wizard avec ES6+ et animations fluides
 */

class AuditWizardModern {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 6;
        this.responses = {};
        this.scores = {
            digital: 0,
            security: 0,
            cloud: 0,
            automation: 0,
            global: 0
        };
        this.autoSaveInterval = null;
        this.charts = {};
        
        this.init();
    }
    
    init() {
        // Auto-save toutes les 30 secondes
        this.autoSaveInterval = setInterval(() => this.autoSave(), 30000);
        
        // Animations entre étapes
        this.initTransitions();
        
        // Restaurer les données sauvegardées
        this.loadSavedData();
        
        // Initialiser les événements
        this.bindEvents();
        
        // Mettre à jour l'affichage initial
        this.updateDisplay();
    }
    
    bindEvents() {
        // Gestion du redimensionnement
        window.addEventListener('resize', () => this.handleResize());
        
        // Gestion du clavier
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
        
        // Gestion des formulaires
        document.addEventListener('change', (e) => this.handleFormChange(e));
    }
    
    initTransitions() {
        // Configuration des transitions CSS
        const stepContents = document.querySelectorAll('.audit-step-content');
        stepContents.forEach(step => {
            step.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
        });
    }
    
    selectOption(element, fieldName) {
        // Animation de sélection
        const cards = element.parentNode.querySelectorAll('.audit-option-card');
        cards.forEach(card => {
            card.classList.remove('selected', 'bounce-in');
        });
        
        element.classList.add('selected');
        element.classList.add('bounce-in');
        
        // Mettre à jour l'input radio caché
        const radio = element.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
        }
        
        // Vibration tactile sur mobile
        if ('vibrate' in navigator) {
            navigator.vibrate(50);
        }
        
        // Sauvegarder la réponse
        this.saveResponse(fieldName, element.dataset.value);
        
        // Mise à jour du score en temps réel
        this.updateLiveScore();
        
        // Animation de feedback
        this.showFeedback('Option sélectionnée', 'success');
    }
    
    selectMultipleOption(element, fieldName) {
        // Toggle de sélection pour les checkboxes
        element.classList.toggle('selected');
        element.classList.add('bounce-in');
        
        // Mettre à jour l'input checkbox caché
        const checkbox = element.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.checked = element.classList.contains('selected');
        }
        
        // Vibration tactile
        if ('vibrate' in navigator) {
            navigator.vibrate(30);
        }
        
        // Sauvegarder les réponses multiples
        this.saveMultipleResponse(fieldName);
        
        // Mise à jour du score
        this.updateLiveScore();
    }
    
    saveResponse(fieldName, value) {
        if (!this.responses[this.currentStep]) {
            this.responses[this.currentStep] = {};
        }
        this.responses[this.currentStep][fieldName] = value;
    }
    
    saveMultipleResponse(fieldName) {
        if (!this.responses[this.currentStep]) {
            this.responses[this.currentStep] = {};
        }
        
        const checkboxes = document.querySelectorAll(`input[name="${fieldName}[]"]:checked`);
        this.responses[this.currentStep][fieldName] = Array.from(checkboxes).map(cb => cb.value);
    }
    
    nextStep() {
        if (this.validateCurrentStep()) {
            if (this.currentStep < this.totalSteps) {
                this.animateStepTransition(this.currentStep + 1);
            }
        }
    }
    
    previousStep() {
        if (this.currentStep > 1) {
            this.animateStepTransition(this.currentStep - 1);
        }
    }
    
    animateStepTransition(targetStep) {
        const currentStepElement = document.querySelector(`#step${this.currentStep}`);
        const targetStepElement = document.querySelector(`#step${targetStep}`);
        
        // Animation de sortie
        currentStepElement.style.transform = 'translateX(-100%)';
        currentStepElement.style.opacity = '0';
        
        setTimeout(() => {
            currentStepElement.style.display = 'none';
            currentStepElement.style.transform = 'translateX(0)';
            currentStepElement.style.opacity = '1';
            
            // Animation d'entrée
            targetStepElement.style.display = 'block';
            targetStepElement.style.transform = 'translateX(100%)';
            targetStepElement.style.opacity = '0';
            
            setTimeout(() => {
                targetStepElement.style.transform = 'translateX(0)';
                targetStepElement.style.opacity = '1';
            }, 50);
            
            this.currentStep = targetStep;
            this.updateDisplay();
            this.loadStepFunctionality(targetStep);
            
            // Scroll vers le haut
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
        }, 250);
    }
    
    updateDisplay() {
        // Mettre à jour le stepper
        this.updateStepper();
        
        // Mettre à jour la barre de progression
        this.updateProgressBar();
        
        // Mettre à jour les boutons de navigation
        this.updateNavigationButtons();
        
        // Mettre à jour le champ step caché
        document.getElementById('currentStep').value = this.currentStep;
    }
    
    updateStepper() {
        const steps = document.querySelectorAll('.step');
        const stepLines = document.querySelectorAll('.step-line');
        
        steps.forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'completed');
            
            if (stepNumber === this.currentStep) {
                step.classList.add('active');
            } else if (stepNumber < this.currentStep) {
                step.classList.add('completed');
            }
        });
        
        stepLines.forEach((line, index) => {
            const stepNumber = index + 1;
            line.classList.remove('active', 'completed');
            
            if (stepNumber < this.currentStep) {
                line.classList.add('completed');
            } else if (stepNumber === this.currentStep) {
                line.classList.add('active');
            }
        });
    }
    
    updateProgressBar() {
        const progressPercent = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
        const progressBar = document.getElementById('progressBar');
        if (progressBar) {
            progressBar.style.width = progressPercent + '%';
        }
    }
    
    updateNavigationButtons() {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const finishBtn = document.getElementById('finishBtn');
        
        if (prevBtn) {
            prevBtn.style.display = this.currentStep > 1 ? 'inline-flex' : 'none';
        }
        
        if (nextBtn) {
            nextBtn.style.display = this.currentStep < this.totalSteps ? 'inline-flex' : 'none';
        }
        
        if (finishBtn) {
            finishBtn.style.display = this.currentStep === this.totalSteps ? 'inline-flex' : 'none';
        }
    }
    
    validateCurrentStep() {
        const currentStepElement = document.querySelector(`#step${this.currentStep}`);
        if (!currentStepElement) return true;
        
        const requiredFields = currentStepElement.querySelectorAll('[required]');
        let isValid = true;
        
        // Effacer les erreurs précédentes
        this.clearErrors();
        
        requiredFields.forEach(field => {
            if (!this.isFieldValid(field)) {
                this.showFieldError(field);
                isValid = false;
            }
        });
        
        if (!isValid) {
            this.showError('Veuillez remplir tous les champs obligatoires.');
            this.shakeInvalidFields();
        }
        
        return isValid;
    }
    
    isFieldValid(field) {
        if (field.type === 'radio') {
            const radioGroup = document.querySelectorAll(`input[name="${field.name}"]`);
            return Array.from(radioGroup).some(radio => radio.checked);
        }
        
        if (field.type === 'checkbox') {
            const checkboxGroup = document.querySelectorAll(`input[name="${field.name}"]`);
            return Array.from(checkboxGroup).some(checkbox => checkbox.checked);
        }
        
        return field.value.trim() !== '';
    }
    
    shakeInvalidFields() {
        const invalidCards = document.querySelectorAll('.audit-option-card:not(.selected)');
        invalidCards.forEach(card => {
            card.style.animation = 'shake 0.5s ease-in-out';
            setTimeout(() => {
                card.style.animation = '';
            }, 500);
        });
    }
    
    showFieldError(field) {
        const formGroup = field.closest('.audit-form-group-modern');
        if (formGroup) {
            formGroup.classList.add('has-error');
        }
    }
    
    clearErrors() {
        document.querySelectorAll('.has-error').forEach(element => {
            element.classList.remove('has-error');
        });
        
        const errorContainer = document.querySelector('.audit-error-container');
        if (errorContainer) {
            errorContainer.remove();
        }
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showFeedback(message, type = 'info') {
        this.showNotification(message, type);
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    updateLiveScore() {
        // Calculer les scores par catégorie
        this.calculateScores();
        
        // Mettre à jour l'affichage des scores
        this.updateScoreDisplay();
    }
    
    calculateScores() {
        // Score maturité digitale (étape 2)
        if (this.responses[2] && this.responses[2].digital_tools) {
            const digitalTools = this.responses[2].digital_tools;
            let digitalScore = 0;
            let maxDigitalScore = 100;
            
            digitalTools.forEach(tool => {
                const checkbox = document.querySelector(`input[value="${tool}"]`);
                if (checkbox && checkbox.dataset.score) {
                    digitalScore += parseInt(checkbox.dataset.score);
                }
            });
            
            this.scores.digital = Math.min(digitalScore, maxDigitalScore);
        }
        
        // Score cybersécurité (étape 3)
        if (this.responses[3] && this.responses[3].security_measures) {
            const securityMeasures = this.responses[3].security_measures;
            let securityScore = 0;
            let maxSecurityScore = 100;
            
            securityMeasures.forEach(measure => {
                const checkbox = document.querySelector(`input[value="${measure}"]`);
                if (checkbox && checkbox.dataset.score) {
                    securityScore += parseInt(checkbox.dataset.score);
                }
            });
            
            this.scores.security = Math.min(securityScore, maxSecurityScore);
        }
        
        // Score cloud (étape 4)
        if (this.responses[4] && this.responses[4].cloud_usage) {
            const cloudUsage = this.responses[4].cloud_usage;
            const radio = document.querySelector(`input[value="${cloudUsage}"]`);
            if (radio && radio.dataset.score) {
                this.scores.cloud = parseInt(radio.dataset.score);
            }
        }
        
        // Score automatisation (étape 5)
        if (this.responses[5] && this.responses[5].automation_level) {
            const automationLevel = this.responses[5].automation_level;
            const radio = document.querySelector(`input[value="${automationLevel}"]`);
            if (radio && radio.dataset.score) {
                this.scores.automation = parseInt(radio.dataset.score);
            }
        }
        
        // Score global
        const scores = [this.scores.digital, this.scores.security, this.scores.cloud, this.scores.automation];
        const validScores = scores.filter(score => score > 0);
        if (validScores.length > 0) {
            this.scores.global = Math.round(validScores.reduce((a, b) => a + b, 0) / validScores.length);
        }
    }
    
    updateScoreDisplay() {
        Object.keys(this.scores).forEach(category => {
            const scoreElement = document.querySelector(`#score-${category}`);
            if (scoreElement) {
                scoreElement.textContent = this.scores[category] + '%';
                scoreElement.className = 'audit-score-value ' + this.getScoreClass(this.scores[category]);
            }
        });
    }
    
    getScoreClass(score) {
        if (score >= 80) return 'audit-score-excellent';
        if (score >= 60) return 'audit-score-good';
        if (score >= 40) return 'audit-score-average';
        if (score >= 20) return 'audit-score-poor';
        return 'audit-score-critical';
    }
    
    loadStepFunctionality(stepNumber) {
        switch (stepNumber) {
            case 6:
                this.loadSynthesisStep();
                break;
        }
    }
    
    loadSynthesisStep() {
        // Calculer les scores finaux
        this.calculateScores();
        this.updateScoreDisplay();
        
        // Générer les graphiques
        setTimeout(() => {
            this.generateRadarChart();
            this.generateProgressChart();
            this.loadRecommendations();
        }, 500);
    }
    
    generateRadarChart() {
        const ctx = document.getElementById('radarChart');
        if (!ctx) return;
        
        // Détruire le graphique existant s'il y en a un
        if (this.charts.radar) {
            this.charts.radar.destroy();
        }
        
        const data = {
            labels: ['Maturité Digitale', 'Cybersécurité', 'Cloud', 'Automatisation'],
            datasets: [{
                label: 'Score actuel',
                data: [
                    this.scores.digital,
                    this.scores.security,
                    this.scores.cloud,
                    this.scores.automation
                ],
                backgroundColor: 'rgba(0, 102, 204, 0.2)',
                borderColor: 'rgba(0, 102, 204, 1)',
                pointBackgroundColor: 'rgba(0, 102, 204, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(0, 102, 204, 1)'
            }]
        };
        
        const options = {
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
        };
        
        this.charts.radar = new Chart(ctx, {
            type: 'radar',
            data: data,
            options: options
        });
    }
    
    generateProgressChart() {
        const ctx = document.getElementById('progressChart');
        if (!ctx) return;
        
        // Détruire le graphique existant s'il y en a un
        if (this.charts.progress) {
            this.charts.progress.destroy();
        }
        
        const data = {
            labels: ['Maturité Digitale', 'Cybersécurité', 'Cloud', 'Automatisation'],
            datasets: [{
                label: 'Score (%)',
                data: [
                    this.scores.digital,
                    this.scores.security,
                    this.scores.cloud,
                    this.scores.automation
                ],
                backgroundColor: [
                    'rgba(0, 102, 204, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(0, 102, 204, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 2
            }]
        };
        
        const options = {
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
        };
        
        this.charts.progress = new Chart(ctx, {
            type: 'bar',
            data: data,
            options: options
        });
    }
    
    loadRecommendations() {
        const container = document.getElementById('recommendations-container');
        if (!container) return;
        
        const recommendations = this.generateRecommendations();
        
        let html = '<h3>🎯 Recommandations personnalisées</h3>';
        
        recommendations.forEach(rec => {
            html += `
                <div class="audit-recommendation-card">
                    <div class="audit-recommendation-header">
                        <h4 class="audit-recommendation-title">${rec.title}</h4>
                        <span class="audit-priority-badge audit-priority-${rec.priority}">${rec.priority}</span>
                    </div>
                    <p class="audit-recommendation-description">${rec.description}</p>
                    <div class="audit-recommendation-details">
                        <div class="audit-detail-item">
                            <div class="audit-detail-label">Impact</div>
                            <div class="audit-detail-value">${rec.impact}</div>
                        </div>
                        <div class="audit-detail-item">
                            <div class="audit-detail-label">Effort</div>
                            <div class="audit-detail-value">${rec.effort}</div>
                        </div>
                        <div class="audit-detail-item">
                            <div class="audit-detail-label">Délai</div>
                            <div class="audit-detail-value">${rec.timeline}</div>
                        </div>
                        <div class="audit-detail-item">
                            <div class="audit-detail-label">ROI estimé</div>
                            <div class="audit-detail-value">${rec.roi}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    generateRecommendations() {
        const recommendations = [];
        
        // Recommandations basées sur les scores
        if (this.scores.security < 60) {
            recommendations.push({
                title: 'Renforcer la cybersécurité',
                description: 'Votre niveau de sécurité nécessite des améliorations urgentes. Implémentez une stratégie de sécurité multicouche.',
                priority: 'high',
                impact: 'Élevé',
                effort: 'Moyen',
                timeline: '3-6 mois',
                roi: '300%'
            });
        }
        
        if (this.scores.cloud < 50) {
            recommendations.push({
                title: 'Migration vers le cloud',
                description: 'Adoptez une stratégie cloud pour améliorer la flexibilité et réduire les coûts d\'infrastructure.',
                priority: 'medium',
                impact: 'Élevé',
                effort: 'Élevé',
                timeline: '6-12 mois',
                roi: '250%'
            });
        }
        
        if (this.scores.automation < 40) {
            recommendations.push({
                title: 'Automatisation des processus',
                description: 'Identifiez et automatisez les tâches répétitives pour gagner en efficacité.',
                priority: 'medium',
                impact: 'Moyen',
                effort: 'Moyen',
                timeline: '3-9 mois',
                roi: '200%'
            });
        }
        
        if (this.scores.digital < 70) {
            recommendations.push({
                title: 'Accélération digitale',
                description: 'Développez votre présence digitale et adoptez de nouveaux outils collaboratifs.',
                priority: 'low',
                impact: 'Moyen',
                effort: 'Faible',
                timeline: '1-3 mois',
                roi: '150%'
            });
        }
        
        return recommendations;
    }
    
    autoSave() {
        const data = {
            currentStep: this.currentStep,
            responses: this.responses,
            scores: this.scores,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('audit_wizard_modern_data', JSON.stringify(data));
        this.showNotification('Brouillon sauvegardé automatiquement', 'success');
    }
    
    loadSavedData() {
        const savedData = localStorage.getItem('audit_wizard_modern_data');
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                this.currentStep = data.currentStep || 1;
                this.responses = data.responses || {};
                this.scores = data.scores || {};
                
                // Restaurer les valeurs du formulaire
                this.restoreFormValues();
                
            } catch (e) {
                console.error('Erreur lors du chargement des données sauvegardées:', e);
            }
        }
    }
    
    restoreFormValues() {
        Object.keys(this.responses).forEach(stepNumber => {
            const stepResponses = this.responses[stepNumber];
            
            Object.keys(stepResponses).forEach(fieldName => {
                const value = stepResponses[fieldName];
                
                if (Array.isArray(value)) {
                    // Checkboxes multiples
                    value.forEach(v => {
                        const checkbox = document.querySelector(`input[name="${fieldName}[]"][value="${v}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            const card = checkbox.closest('.audit-option-card');
                            if (card) {
                                card.classList.add('selected');
                            }
                        }
                    });
                } else {
                    // Radio button
                    const radio = document.querySelector(`input[name="${fieldName}"][value="${value}"]`);
                    if (radio) {
                        radio.checked = true;
                        const card = radio.closest('.audit-option-card');
                        if (card) {
                            card.classList.add('selected');
                        }
                    }
                }
            });
        });
    }
    
    handleKeyboard(e) {
        // Navigation au clavier
        if (e.key === 'ArrowRight' || e.key === 'Enter') {
            if (e.ctrlKey) {
                this.nextStep();
            }
        } else if (e.key === 'ArrowLeft') {
            if (e.ctrlKey) {
                this.previousStep();
            }
        }
    }
    
    handleResize() {
        // Redimensionner les graphiques
        Object.values(this.charts).forEach(chart => {
            if (chart && chart.resize) {
                chart.resize();
            }
        });
    }
    
    handleFormChange(e) {
        // Sauvegarder automatiquement les changements
        if (e.target.closest('.audit-form-container-modern')) {
            setTimeout(() => this.autoSave(), 1000);
        }
    }
    
    finishAudit() {
        if (this.validateCurrentStep()) {
            // Animation de finalisation
            this.showNotification('Finalisation de l\'audit en cours...', 'info');
            
            // Simuler l'envoi des données
            setTimeout(() => {
                this.showNotification('Audit créé avec succès !', 'success');
                
                // Redirection après 2 secondes
                setTimeout(() => {
                    window.location.href = '/custom/auditdigital/audit_list.php';
                }, 2000);
            }, 1500);
        }
    }
}

// Fonctions globales pour la compatibilité
function selectOption(element, fieldName) {
    if (window.auditWizard) {
        window.auditWizard.selectOption(element, fieldName);
    }
}

function selectMultipleOption(element, fieldName) {
    if (window.auditWizard) {
        window.auditWizard.selectMultipleOption(element, fieldName);
    }
}

function nextStep() {
    if (window.auditWizard) {
        window.auditWizard.nextStep();
    }
}

function previousStep() {
    if (window.auditWizard) {
        window.auditWizard.previousStep();
    }
}

function finishAudit() {
    if (window.auditWizard) {
        window.auditWizard.finishAudit();
    }
}

function toggleComment(commentId) {
    const commentBox = document.getElementById(commentId);
    if (commentBox) {
        if (commentBox.style.display === 'none' || !commentBox.style.display) {
            commentBox.style.display = 'block';
            commentBox.style.animation = 'slideDown 0.3s ease-out';
        } else {
            commentBox.style.display = 'none';
        }
    }
}

// Animation shake pour les champs invalides
const shakeKeyframes = `
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}
`;

// Ajouter les keyframes au document
if (!document.querySelector('#shake-animation')) {
    const style = document.createElement('style');
    style.id = 'shake-animation';
    style.textContent = shakeKeyframes;
    document.head.appendChild(style);
}

// Initialiser le wizard quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    window.auditWizard = new AuditWizardModern();
});

// Gestion de la fermeture de la page
window.addEventListener('beforeunload', function(e) {
    if (window.auditWizard && Object.keys(window.auditWizard.responses).length > 0) {
        e.preventDefault();
        e.returnValue = 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter ?';
    }
});