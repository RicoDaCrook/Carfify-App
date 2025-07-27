/**
 * Carfify – Complete App Module
 * Main application controller with PWA support
 */

import { initDiagnosis } from './diagnosis.js';
import { initSelling } from './selling.js';
import { initAnimations } from './animations.js';

class CarfifyApp {
    constructor() {
        this.currentModal = null;
        this.features = {
            diagnose: new initDiagnosis(),
            sell: new initSelling()
        };
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initProgressBar();
        this.initRippleEffects();
        this.initIntersectionObserver();
        this.initModals();
        this.handleLocationPermission();
        initAnimations();
    }

    setupEventListeners() {
        // Feature card clicks
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.tagName !== 'BUTTON') {
                    const feature = card.dataset.feature;
                    this.handleFeatureClick(feature);
                }
            });
        });

        // Modal close handlers
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => this.closeModal());
        });

        // Click outside modal to close
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal();
            }
        });

        // ESC key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.currentModal) {
                this.closeModal();
            }
        });
    }

    handleFeatureClick(feature) {
        switch(feature) {
            case 'diagnose':
                this.openModal('diagnose-modal');
                this.features.diagnose.start();
                break;
            case 'sell':
                this.openModal('sell-modal');
                this.features.sell.start();
                break;
            case 'maintenance':
            case 'parts':
            case 'reviews':
            case 'forum':
            case 'insurance':
            case 'inspection':
                this.showComingSoon(feature);
                break;
        }
    }

    openModal(modalId) {
        this.currentModal = document.getElementById(modalId);
        if (this.currentModal) {
            this.currentModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            this.showProgress();
        }
    }

    closeModal() {
        if (this.currentModal) {
            this.currentModal.style.display = 'none';
            document.body.style.overflow = '';
            this.currentModal = null;
            this.hideProgress();
        }
    }

    showComingSoon(feature) {
        const featureNames = {
            maintenance: 'Wartungsplaner',
            parts: 'Teilemarkt',
            reviews: 'Werkstatt-Bewertungen',
            forum: 'Community-Forum',
            insurance: 'Versicherungsvergleich',
            inspection: 'TÜV/HU Erinnerung'
        };
        
        alert(`${featureNames[feature]} wird bald verfügbar sein!`);
    }

    initProgressBar() {
        const progressBar = document.getElementById('global-progress');
        if (!progressBar) return;

        // Listen for custom events
        document.addEventListener('carfify:showProgress', () => {
            progressBar.style.transform = 'scaleX(1)';
        });

        document.addEventListener('carfify:hideProgress', () => {
            progressBar.style.transform = 'scaleX(0)';
        });

        document.addEventListener('carfify:updateProgress', (e) => {
            const percent = e.detail.percent || 0;
            progressBar.style.transform = `scaleX(${percent / 100})`;
        });
    }

    showProgress() {
        document.dispatchEvent(new CustomEvent('carfify:showProgress'));
    }

    hideProgress() {
        document.dispatchEvent(new CustomEvent('carfify:hideProgress'));
    }

    initRippleEffects() {
        document.querySelectorAll('.ripple').forEach(button => {
            button.addEventListener('click', this.createRipple);
        });
    }

    createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement('span');
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;

        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
        circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
        circle.classList.add('ripple-effect');

        const ripple = button.getElementsByClassName('ripple-effect')[0];
        if (ripple) {
            ripple.remove();
        }

        button.appendChild(circle);
    }

    initIntersectionObserver() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach(el => {
            observer.observe(el);
        });
    }

    initModals() {
        // Add modal styles if not present
        const style = document.createElement('style');
        style.textContent = `
            .ripple-effect {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
                pointer-events: none;
            }
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    async handleLocationPermission() {
        if ('geolocation' in navigator) {
            const permission = await navigator.permissions.query({ name: 'geolocation' });
            
            if (permission.state === 'granted') {
                this.getUserLocation();
            } else if (permission.state === 'prompt') {
                // Show location explanation
                this.showLocationExplanation();
            }
        }
    }

    showLocationExplanation() {
        // Create location permission modal
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="location-modal">
                <h3>Standortzugriff</h3>
                <p>Wir benötigen Ihren Standort für:</p>
                <ul>
                    <li>Werkstatt-Empfehlungen in Ihrer Nähe</li>
                    <li>Regionale Preisvergleiche</li>
                    <li>Optimierte Services</li>
                </ul>
                <div class="modal-actions">
                    <button class="btn btn--primary" onclick="app.grantLocation()">Erlauben</button>
                    <button class="btn btn--secondary" onclick="app.denyLocation()">Später</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    async grantLocation() {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                localStorage.setItem('userLocation', JSON.stringify({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                }));
                document.querySelector('.location-modal')?.remove();
            },
            (error) => {
                console.error('Location error:', error);
                document.querySelector('.location-modal')?.remove();
            }
        );
    }

    denyLocation() {
        document.querySelector('.location-modal')?.remove();
    }

    getUserLocation() {
        navigator.geolocation.getCurrentPosition((position) => {
            localStorage.setItem('userLocation', JSON.stringify({
                lat: position.coords.latitude,
                lng: position.coords.longitude
            }));
        });
    }
}

// Global functions for onclick handlers
window.startDiagnose = () => {
    window.app.handleFeatureClick('diagnose');
};

window.startSelling = () => {
    window.app.handleFeatureClick('sell');
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.app = new CarfifyApp();
});

// PWA Install Prompt
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show install button
    const installBtn = document.createElement('button');
    installBtn.textContent = 'App installieren';
    installBtn.className = 'install-btn';
    installBtn.onclick = async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`User response: ${outcome}`);
            deferredPrompt = null;
            installBtn.remove();
        }
    };
    document.body.appendChild(installBtn);
});

// Handle online/offline status
window.addEventListener('online', () => {
    document.body.classList.remove('offline');
});

window.addEventListener('offline', () => {
    document.body.classList.add('offline');
});

export default CarfifyApp;