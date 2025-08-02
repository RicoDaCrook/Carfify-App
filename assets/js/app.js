// Carfify - Core JavaScript
// Phase 1.1 - Main Menu Interactions

class CarfifyApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupProgressIndicator();
        this.setupRippleEffects();
        this.setupFeatureCards();
        this.setupSmoothScroll();
    }

    // Progress Indicator Animation
    setupProgressIndicator() {
        const progressBar = document.querySelector('.progress-bar');
        
        if (progressBar) {
            // Animate progress on page load
            setTimeout(() => {
                progressBar.style.width = '100%';
            }, 100);

            // Reset on navigation
            window.addEventListener('beforeunload', () => {
                progressBar.style.width = '0%';
            });
        }
    }

    // Ripple Effect for Cards
    setupRippleEffects() {
        const cards = document.querySelectorAll('.feature-card.active');
        
        cards.forEach(card => {
            card.addEventListener('click', (e) => {
                const ripple = document.createElement('span');
                const rect = card.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;

                card.style.position = 'relative';
                card.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    // Feature Card Interactions
    setupFeatureCards() {
        const comingSoonCards = document.querySelectorAll('.feature-card.coming-soon');
        
        comingSoonCards.forEach(card => {
            card.addEventListener('click', (e) => {
                e.preventDefault();
                this.showComingSoonNotification();
            });
        });
    }

    // Coming Soon Notification
    showComingSoonNotification() {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'coming-soon-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-clock"></i>
                <h3>Coming Soon!</h3>
                <p>Dieses Feature ist in Entwicklung und bald verfügbar.</p>
            </div>
        `;

        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            z-index: 1001;
            animation: fadeInScale 0.3s ease;
        `;

        const content = notification.querySelector('.notification-content');
        content.style.cssText = `
            color: white;
        `;

        const icon = notification.querySelector('i');
        icon.style.cssText = `
            font-size: 3rem;
            color: #4fc2ee;
            margin-bottom: 1rem;
        `;

        // Add to DOM
        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'fadeOutScale 0.3s ease';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Smooth Scroll
    setupSmoothScroll() {
        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }

            @keyframes fadeInScale {
                from {
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.8);
                }
                to {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
            }

            @keyframes fadeOutScale {
                from {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
                to {
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.8);
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Utility: Add loading state
    addLoadingState(element) {
        element.classList.add('loading');
        element.style.pointerEvents = 'none';
    }

    // Utility: Remove loading state
    removeLoadingState(element) {
        element.classList.remove('loading');
        element.style.pointerEvents = 'auto';
    }
}

// Initialize App
document.addEventListener('DOMContentLoaded', () => {
    new CarfifyApp();
});

// Service Worker Registration (for PWA)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}