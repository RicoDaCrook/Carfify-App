/**
 * Carfify Animation Engine
 * Provides smooth, performant animations and micro-interactions
 * Uses Web Animations API for best mobile performance
 * Version: 1.0.0
 */

class CarfifyAnimations {
    constructor() {
        this.init();
    }

    init() {
        this.setupGlobalStyles();
        this.setupScrollAnimations();
        this.setupCardAnimations();
        this.setupLoadingStates();
        this.setupProgressIndicators();
        this.setupButtonAnimations();
    }

    /**
     * Global CSS custom properties for animations
     */
    setupGlobalStyles() {
        const style = document.createElement('style');
        style.textContent = `
            :root {
                --transition-fast: 0.15s;
                --transition-normal: 0.3s;
                --transition-slow: 0.5s;
                --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
                --ease-in-out-quart: cubic-bezier(0.76, 0, 0.24, 1);
            }

            /* Base animation classes */
            .fade-in { opacity: 0; }
            .slide-up { transform: translateY(30px); opacity: 0; }
            .scale-up { transform: scale(0.9); opacity: 0; }

            /* Reduced motion fallback */
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }

            /* Glass morphism backdrop */
            .glass-backdrop {
                backdrop-filter: blur(20px) saturate(180%);
                background: rgba(255, 255, 255, 0.75);
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Scroll-triggered animations
     */
    setupScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateEntry(entry.target);
                }
            });
        }, { threshold: 0.1 });

        // Observe all animated elements
        document.querySelectorAll('[data-animate]').forEach(el => {
            observer.observe(el);
        });
    }

    /**
     * Element entry animations
     * @param {Element} element
     */
    animateEntry(element) {
        const animation = element.dataset.animate;
        const delay = parseInt(element.dataset.delay) || 0;

        switch (animation) {
            case 'fade-in':
                element.animate(
                    [{ opacity: 0 }, { opacity: 1 }],
                    { duration: 600, delay, fill: 'forwards', easing: 'ease-out' }
                );
                break;

            case 'slide-up':
                element.animate(
                    [
                        { transform: 'translateY(30px)', opacity: 0 },
                        { transform: 'translateY(0)', opacity: 1 }
                    ],
                    { duration: 500, delay, fill: 'forwards', easing: 'var(--ease-out-expo)' }
                );
                break;

            case 'scale-up':
                element.animate(
                    [
                        { transform: 'scale(0.9)', opacity: 0 },
                        { transform: 'scale(1)', opacity: 1 }
                    ],
                    { duration: 400, delay, fill: 'forwards', easing: 'var(--ease-out-expo)' }
                );
                break;

            case 'stagger':
                const children = element.children;
                [...children].forEach((child, index) => {
                    child.animate(
                        [
                            { transform: 'translateY(20px)', opacity: 0 },
                            { transform: 'translateY(0)', opacity: 1 }
                        ],
                        { duration: 400, delay: delay + index * 100, fill: 'forwards' }
                    );
                });
                break;
        }
    }

    /**
     * Card hover effects for diagnosis results
     */
    setupCardAnimations() {
        const cards = document.querySelectorAll('.diagnosis-card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.animate(
                    [
                        { transform: 'translateY(0px)' },
                        { transform: 'translateY(-2px)' }
                    ],
                    { duration: 200, fill: 'forwards', easing: 'ease-out' }
                );

                // Glass reflection effect
                const glass = card.querySelector('.glass-card');
                if (glass) {
                    glass.animate(
                        [
                            { background: 'rgba(255,255,255,0.1)' },
                            { background: 'rgba(255,255,255,0.2)' }
                        ],
                        { duration: 300, fill: 'forwards' }
                    );
                }
            });

            card.addEventListener('mouseleave', () => {
                card.animate(
                    [{ transform: 'translateY(-2px)' }, { transform: 'translateY(0px)' }],
                    { duration: 200, fill: 'forwards' }
                );
            });
        });
    }

    /**
     * Loading state animations
     */
    setupLoadingStates() {
        // Skeleton loading
        const skeletonTemplate = `
            <div class="animate-pulse">
                <div class="bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded-lg h-24 mb-4"></div>
                <div class="h-4 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded w-3/4 mb-2"></div>
                <div class="h-4 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded w-1/2"></div>
            </div>
        `;

        window.startLoading = (container) => {
            container.innerHTML = skeletonTemplate;
            container.classList.add('loading');
        };

        window.stopLoading = (container, content) => {
            container.classList.add('fade-out');
            setTimeout(() => {
                container.innerHTML = content;
                container.classList.remove('fade-out', 'loading');
                container.animate(
                    [{ opacity: 0 }, { opacity: 1 }],
                    { duration: 300, fill: 'forwards' }
                );
            }, 150);
        };
    }

    /**
     * Progress indicator animations
     */
    setupProgressIndicators() {
        // Smooth progress bar
        const progressBar = document.querySelector('.diagnosis-progress');
        if (progressBar) {
            progressBar.animate(
                [
                    { transform: 'scaleX(0)', transformOrigin: 'left' },
                    { transform: 'scaleX(1)', transformOrigin: 'left' }
                ],
                { duration: 2000, easing: 'ease-in-out' }
            );
        }

        // Step indicators
        const steps = document.querySelectorAll('.step-indicator');
        steps.forEach((step, index) => {
            step.animate(
                [
                    { opacity: 0, transform: 'scale(0)' },
                    { opacity: 1, transform: 'scale(1)' }
                ],
                { duration: 500, delay: index * 200, fill: 'forwards' }
            );
        });
    }

    /**
     * Button micro-interactions
     */
    setupButtonAnimations() {
        // Ripple effect for material buttons
        document.querySelectorAll('.btn-ripple').forEach(button => {
            button.addEventListener('click', (e) => {
                const ripple = document.createElement('span');
                const rect = button.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255,255,255,0.4);
                    transform: scale(0);
                    animation: ripple 600ms linear;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    pointer-events: none;
                `;

                button.style.position = 'relative';
                button.style.overflow = 'hidden';
                button.appendChild(ripple);

                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Success check animation
        window.animateSuccess = (element) => {
            const check = document.createElement('div');
            check.innerHTML = '<svg viewBox="0 0 24 24" class="w-6 h-6 text-green-500"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>';
            check.style.cssText = 'animation: checkmark 0.5s ease-in-out forwards';
            element.appendChild(check);
            
            // Auto-remove after animation
            setTimeout(() => check.remove(), 1000);
        };
    }

    /**
     * Glass morphism background parallax
     */
    setupParallaxGlass() {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const glass = document.querySelector('.glass-backdrop');
            
            if (glass) {
                glass.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    }

    /**
     * Lottie-style confetti animation for success states
     */
    animateConfetti(duration = 1500) {
        const colors = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe'];
        const shapes = ['🔧', '⚡', '🚗', '🔍', '✅'];
        
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            const color = colors[Math.floor(Math.random() * colors.length)];
            const shape = shapes[Math.floor(Math.random() * shapes.length)];
            
            particle.innerHTML = shape;
            particle.style.cssText = `
                position: fixed;
                top: -10px;
                left: ${Math.random() * 100}vw;
                font-size: ${Math.random() * 20 + 10}px;
                pointer-events: none;
                animation: fall ${Math.random() * 1 + 0.5}s linear forwards;
                z-index: 10000;
            `;

            document.body.appendChild(particle);
            setTimeout(() => particle.remove(), duration);
        }
    }

    /**
     * Typewriter effect for diagnosis results
     */
    typeWriterEffect(element, text, speed = 25) {
        element.textContent = '';
        let index = 0;

        const timer = setInterval(() => {
            if (index < text.length) {
                element.textContent += text.charAt(index);
                index++;
            } else {
                clearInterval(timer);
            }
        }, speed);
    }
}

// CSS keyframes for custom animations
const animationStyles = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    @keyframes checkmark {
        