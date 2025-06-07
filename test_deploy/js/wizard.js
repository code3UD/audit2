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
 * JavaScript for AuditDigital Wizard
 */

class AuditWizard {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 6;
        this.responses = {};
        this.scores = {};
        
        this.init();
    }
    
    init() {
        this.updateProgress();
        this.bindEvents();
        this.loadSavedData();
    }
    
    bindEvents() {
        // Navigation buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('audit-btn-next')) {
                e.preventDefault();
                this.nextStep();
            }
            
            if (e.target.classList.contains('audit-btn-prev')) {
                e.preventDefault();
                this.prevStep();
            }
            
            if (e.target.classList.contains('audit-btn-finish')) {
                e.preventDefault();
                this.finishWizard();
            }
        });
        
        // Form inputs
        document.addEventListener('change', (e) => {
            if (e.target.closest('.audit-form-container')) {
                this.handleInputChange(e);
            }
        });
        
        // Radio button styling
        document.addEventListener('change', (e) => {
            if (e.target.type === 'radio') {
                this.updateRadioSelection(e.target);
            }
        });
        
        // Checkbox styling
        document.addEventListener('change', (e) => {
            if (e.target.type === 'checkbox') {
                this.updateCheckboxSelection(e.target);
            }
        });
        
        // Auto-save
        setInterval(() => {
            this.saveData();
        }, 30000); // Save every 30 seconds
    }
    
    updateProgress() {
        const progressPercent = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
        const progressBar = document.querySelector('.audit-progress-fill');
        if (progressBar) {
            progressBar.style.width = progressPercent + '%';
        }
        
        // Update step indicators
        document.querySelectorAll('.audit-step').forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'completed');
            
            if (stepNumber === this.currentStep) {
                step.classList.add('active');
            } else if (stepNumber < this.currentStep) {
                step.classList.add('completed');
            }
        });
    }
    
    nextStep() {
        if (this.validateCurrentStep()) {
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.showStep(this.currentStep);
                this.updateProgress();
                this.saveData();
            }
        }
    }
    
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.showStep(this.currentStep);
            this.updateProgress();
        }
    }
    
    showStep(stepNumber) {
        // Hide all steps
        document.querySelectorAll('.audit-step-content').forEach(step => {
            step.style.display = 'none';
        });
        
        // Show current step
        const currentStepElement = document.querySelector(`#step${stepNumber}`);
        if (currentStepElement) {
            currentStepElement.style.display = 'block';
        }
        
        // Update navigation buttons
        this.updateNavigationButtons();
        
        // Scroll to top
        window.scrollTo(0, 0);
        
        // Load step-specific functionality
        this.loadStepFunctionality(stepNumber);
    }
    
    updateNavigationButtons() {
        const prevBtn = document.querySelector('.audit-btn-prev');
        const nextBtn = document.querySelector('.audit-btn-next');
        const finishBtn = document.querySelector('.audit-btn-finish');
        
        if (prevBtn) {
            prevBtn.style.display = this.currentStep > 1 ? 'inline-block' : 'none';
        }
        
        if (nextBtn) {
            nextBtn.style.display = this.currentStep < this.totalSteps ? 'inline-block' : 'none';
        }
        
        if (finishBtn) {
            finishBtn.style.display = this.currentStep === this.totalSteps ? 'inline-block' : 'none';
        }
    }
    
    validateCurrentStep() {
        const currentStepElement = document.querySelector(`#step${this.currentStep}`);
        if (!currentStepElement) return true;
        
        const requiredFields = currentStepElement.querySelectorAll('[required]');
        let isValid = true;
        
        // Clear previous errors
        this.clearErrors();
        
        requiredFields.forEach(field => {
            if (!this.isFieldValid(field)) {
                this.showFieldError(field);
                isValid = false;
            }
        });
        
        if (!isValid) {
            this.showError('Veuillez remplir tous les champs obligatoires.');
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
    
    showFieldError(field) {
        const formGroup = field.closest('.audit-form-group');
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
            errorContainer.innerHTML = '';
        }
    }
    
    showError(message) {
        let errorContainer = document.querySelector('.audit-error-container');
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.className = 'audit-error-container';
            const formContainer = document.querySelector('.audit-form-container');
            if (formContainer) {
                formContainer.insertBefore(errorContainer, formContainer.firstChild);
            }
        }
        
        errorContainer.innerHTML = `<div class="audit-error">${message}</div>`;
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    showSuccess(message) {
        let successContainer = document.querySelector('.audit-success-container');
        if (!successContainer) {
            successContainer = document.createElement('div');
            successContainer.className = 'audit-success-container';
            const formContainer = document.querySelector('.audit-form-container');
            if (formContainer) {
                formContainer.insertBefore(successContainer, formContainer.firstChild);
            }
        }
        
        successContainer.innerHTML = `<div class="audit-success">${message}</div>`;
        successContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    handleInputChange(e) {
        const input = e.target;
        const stepId = input.closest('.audit-step-content').id;
        const stepNumber = stepId.replace('step', '');
        
        if (!this.responses[stepNumber]) {
            this.responses[stepNumber] = {};
        }
        
        if (input.type === 'checkbox') {
            if (!this.responses[stepNumber][input.name]) {
                this.responses[stepNumber][input.name] = [];
            }
            
            if (input.checked) {
                if (!this.responses[stepNumber][input.name].includes(input.value)) {
                    this.responses[stepNumber][input.name].push(input.value);
                }
            } else {
                const index = this.responses[stepNumber][input.name].indexOf(input.value);
                if (index > -1) {
                    this.responses[stepNumber][input.name].splice(index, 1);
                }
            }
        } else {
            this.responses[stepNumber][input.name] = input.value;
        }
        
        // Calculate scores for scoring steps
        if (['2', '3', '4', '5'].includes(stepNumber)) {
            this.calculateStepScore(stepNumber);
        }
    }
    
    updateRadioSelection(radio) {
        const radioGroup = document.querySelectorAll(`input[name="${radio.name}"]`);
        radioGroup.forEach(r => {
            const item = r.closest('.audit-radio-item');
            if (item) {
                item.classList.remove('selected');
            }
        });
        
        const selectedItem = radio.closest('.audit-radio-item');
        if (selectedItem) {
            selectedItem.classList.add('selected');
        }
    }
    
    updateCheckboxSelection(checkbox) {
        const item = checkbox.closest('.audit-checkbox-item');
        if (item) {
            if (checkbox.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        }
    }
    
    calculateStepScore(stepNumber) {
        // This would be implemented based on the scoring logic
        // For now, we'll use a placeholder
        const stepResponses = this.responses[stepNumber];
        if (!stepResponses) return;
        
        // Placeholder scoring logic
        let score = 0;
        let maxScore = 0;
        
        Object.keys(stepResponses).forEach(questionName => {
            const questionElement = document.querySelector(`input[name="${questionName}"]`);
            if (questionElement && questionElement.dataset.scoreMapping) {
                const scoreMapping = JSON.parse(questionElement.dataset.scoreMapping);
                const response = stepResponses[questionName];
                
                if (scoreMapping[response] !== undefined) {
                    score += scoreMapping[response];
                }
                
                maxScore += Math.max(...Object.values(scoreMapping));
            }
        });
        
        const stepScore = maxScore > 0 ? Math.round((score / maxScore) * 100) : 0;
        
        const categoryMapping = {
            '2': 'maturite',
            '3': 'cybersecurite',
            '4': 'cloud',
            '5': 'automatisation'
        };
        
        if (categoryMapping[stepNumber]) {
            this.scores[categoryMapping[stepNumber]] = stepScore;
        }
        
        this.updateScoreDisplay();
    }
    
    updateScoreDisplay() {
        Object.keys(this.scores).forEach(category => {
            const scoreElement = document.querySelector(`#score-${category}`);
            if (scoreElement) {
                scoreElement.textContent = this.scores[category] + '%';
                scoreElement.className = 'audit-score-value ' + this.getScoreClass(this.scores[category]);
            }
        });
        
        // Calculate global score
        const scores = Object.values(this.scores);
        if (scores.length > 0) {
            const globalScore = Math.round(scores.reduce((a, b) => a + b, 0) / scores.length);
            this.scores.global = globalScore;
            
            const globalScoreElement = document.querySelector('#score-global');
            if (globalScoreElement) {
                globalScoreElement.textContent = globalScore + '%';
                globalScoreElement.className = 'audit-score-value ' + this.getScoreClass(globalScore);
            }
        }
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
        // Generate radar chart
        this.generateRadarChart();
        
        // Load recommendations
        this.loadRecommendations();
    }
    
    generateRadarChart() {
        const radarContainer = document.querySelector('#radar-chart');
        if (!radarContainer) return;
        
        // This would integrate with a charting library like Chart.js
        // For now, we'll show a placeholder
        radarContainer.innerHTML = '<p>Graphique radar des scores (à implémenter avec Chart.js)</p>';
    }
    
    loadRecommendations() {
        const recommendationsContainer = document.querySelector('#recommendations-container');
        if (!recommendationsContainer) return;
        
        // This would load recommendations based on scores
        // For now, we'll show a placeholder
        recommendationsContainer.innerHTML = '<p>Recommandations basées sur les scores (à implémenter)</p>';
    }
    
    saveData() {
        const data = {
            currentStep: this.currentStep,
            responses: this.responses,
            scores: this.scores,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('audit_wizard_data', JSON.stringify(data));
    }
    
    loadSavedData() {
        const savedData = localStorage.getItem('audit_wizard_data');
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                this.currentStep = data.currentStep || 1;
                this.responses = data.responses || {};
                this.scores = data.scores || {};
                
                // Restore form values
                this.restoreFormValues();
                
                // Show current step
                this.showStep(this.currentStep);
            } catch (e) {
                console.error('Error loading saved data:', e);
            }
        }
    }
    
    restoreFormValues() {
        Object.keys(this.responses).forEach(stepNumber => {
            const stepResponses = this.responses[stepNumber];
            
            Object.keys(stepResponses).forEach(fieldName => {
                const value = stepResponses[fieldName];
                
                if (Array.isArray(value)) {
                    // Checkbox group
                    value.forEach(v => {
                        const checkbox = document.querySelector(`input[name="${fieldName}"][value="${v}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            this.updateCheckboxSelection(checkbox);
                        }
                    });
                } else {
                    // Radio or select
                    const input = document.querySelector(`input[name="${fieldName}"][value="${value}"], select[name="${fieldName}"]`);
                    if (input) {
                        if (input.type === 'radio') {
                            input.checked = true;
                            this.updateRadioSelection(input);
                        } else {
                            input.value = value;
                        }
                    }
                }
            });
        });
    }
    
    finishWizard() {
        if (this.validateCurrentStep()) {
            this.saveData();
            this.submitAudit();
        }
    }
    
    submitAudit() {
        const submitBtn = document.querySelector('.audit-btn-finish');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="audit-spinner"></span> Finalisation...';
        }
        
        // Prepare data for submission
        const auditData = {
            responses: this.responses,
            scores: this.scores,
            structure_type: this.responses['1'] ? this.responses['1']['structure_type'] : '',
            timestamp: new Date().toISOString()
        };
        
        // Submit via AJAX
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(auditData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showSuccess('Audit finalisé avec succès !');
                
                // Clear saved data
                localStorage.removeItem('audit_wizard_data');
                
                // Redirect to audit card or PDF generation
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                }
            } else {
                this.showError(data.error || 'Erreur lors de la finalisation de l\'audit');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showError('Erreur de communication avec le serveur');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Finaliser l\'audit';
            }
        });
    }
    
    // Utility methods
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize wizard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.audit-wizard')) {
        window.auditWizard = new AuditWizard();
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuditWizard;
}