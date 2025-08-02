// Carfify PWA Manager
class CarfifyPWA {
    constructor() {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.isOnline = navigator.onLine;
        
        this.init();
    }
    
    init() {
        console.log('Carfify PWA: Initializing...');
        
        // Register Service Worker
        this.registerServiceWorker();
        
        // Setup Install Prompt
        this.setupInstallPrompt();
        
        // Setup Online/Offline Detection
        this.setupNetworkDetection();
        
        // Check if already installed
        this.checkInstallStatus();
    }
    
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/service-worker.js');
                console.log('Carfify SW: Registered successfully', registration);
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    console.log('Carfify SW: Update found');
                    this.showUpdateNotification();
                });
                
            } catch (error) {
                console.error('Carfify SW: Registration failed', error);
            }
        }
    }
    
    setupInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('Carfify PWA: Install prompt available');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });
        
        window.addEventListener('appinstalled', () => {
            console.log('Carfify PWA: App installed');
            this.isInstalled = true;
            this.hideInstallButton();
            this.showInstalledMessage();
        });
    }
    
    showInstallButton() {
        if (document.getElementById('pwa-install-btn')) return;
        
        const installBtn = document.createElement('button');
        installBtn.id = 'pwa-install-btn';
        installBtn.className = 'pwa-install-btn';
        installBtn.innerHTML = `
            <span class="btn-icon">📱</span>
            App installieren
        `;
        
        installBtn.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            padding: 1rem 1.5rem;
            border-radius: 25px;
            background: linear-gradient(45deg, #4fc2ee, #00d4aa);
            color: white;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(79, 194, 238, 0.3);
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            animation: pulseInstall 2s infinite;
        `;
        
        installBtn.addEventListener('click', () => this.installApp());
        document.body.appendChild(installBtn);
        
        // CSS Animation
        if (!document.getElementById('pwa-styles')) {
            const style = document.createElement('style');
            style.id = 'pwa-styles';
            style.textContent = `
                @keyframes pulseInstall {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                    100% { transform: scale(1); }
                }
                .pwa-install-btn:hover {
                    transform: scale(1.1) !important;
                    box-shadow: 0 6px 20px rgba(79, 194, 238, 0.4) !important;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    async installApp() {
        if (!this.deferredPrompt) return;
        
        try {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            console.log(`Carfify PWA: User ${outcome} the install prompt`);
            
            if (outcome === 'accepted') {
                this.hideInstallButton();
            }
            
            this.deferredPrompt = null;
        } catch (error) {
            console.error('Carfify PWA: Install failed', error);
        }
    }
    
    hideInstallButton() {
        const btn = document.getElementById('pwa-install-btn');
        if (btn) btn.remove();
    }
    
    setupNetworkDetection() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateNetworkStatus();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateNetworkStatus();
        });
        
        this.updateNetworkStatus();
    }
    
    updateNetworkStatus() {
        document.body.classList.toggle('offline', !this.isOnline);
        
        if (!this.isOnline) {
            this.showOfflineMessage();
        } else {
            this.hideOfflineMessage();
        }
    }
    
    showOfflineMessage() {
        if (document.getElementById('offline-message')) return;
        
        const msg = document.createElement('div');
        msg.id = 'offline-message';
        msg.innerHTML = `
            <span style="margin-right: 0.5rem;">📡</span>
            Offline-Modus aktiv
        `;
        msg.style.cssText = `
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff9500;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            z-index: 1001;
            font-size: 0.9rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        document.body.appendChild(msg);
    }
    
    hideOfflineMessage() {
        const msg = document.getElementById('offline-message');
        if (msg) msg.remove();
    }
    
    checkInstallStatus() {
        // Check if running as PWA
        if (window.matchMedia('(display-mode: standalone)').matches) {
            this.isInstalled = true;
            console.log('Carfify PWA: Running as installed app');
        }
    }
    
    showInstalledMessage() {
        const msg = document.createElement('div');
        msg.innerHTML = '🎉 Carfify erfolgreich installiert!';
        msg.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #00d4aa;
            color: white;
            padding: 1rem 2rem;
            border-radius: 25px;
            z-index: 1001;
            box-shadow: 0 4px 15px rgba(0, 212, 170, 0.3);
        `;
        document.body.appendChild(msg);
        
        setTimeout(() => msg.remove(), 3000);
    }
    
    showUpdateNotification() {
        if (document.getElementById('update-notification')) return;
        
        const notification = document.createElement('div');
        notification.id = 'update-notification';
        notification.innerHTML = `
            <span>🔄 App-Update verfügbar</span>
            <button onclick="window.location.reload()" style="margin-left: 1rem; padding: 0.3rem 0.8rem; border: none; border-radius: 15px; background: white; color: #4fc2ee; cursor: pointer;">
                Aktualisieren
            </button>
        `;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #4fc2ee;
            color: white;
            padding: 1rem;
            border-radius: 25px;
            z-index: 1001;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 15px rgba(79, 194, 238, 0.3);
        `;
        document.body.appendChild(notification);
    }
}

// Initialize PWA when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.carfifyPWA = new CarfifyPWA();
    });
} else {
    window.carfifyPWA = new CarfifyPWA();
}

// Expose for debugging
window.CarfifyPWA = CarfifyPWA;