/**
 * Carfify – Haupt-App-Modul
 * Initialisiert alle weiteren Module und kümmert sich um globale UX-Features.
 */

import { initDiagnosis } from './diagnosis.js';
import { initAnimations } from './animations.js';

(function () {
  'use strict';

  /**
   * Globale App-Instanz (Singleton)
   * @type {Object}
   */
  window.CarfifyApp = {
    diagnosis: null,
    animations: null,
    init() {
      this.initProgressBar();
      this.initRippleEffects();
      this.initIntersectionObserver();
      this.diagnosis = initDiagnosis();
      this.animations = initAnimations();
    },

    /**
     * Zeigt / versteckt die Fortschrittsleiste am oberen Bildschirmrand.
     */
    initProgressBar() {
      const progressBar = document.getElementById('global-progress');
      if (!progressBar) return;

      let visible = false;
      window.addEventListener('carfify:showProgress', () => {
        if (!visible) {
          progressBar.style.transform = 'scaleX(1)';
          visible = true;
        }
      });

      window.addEventListener('carfify:hideProgress', () => {
        if (visible) {
          progressBar.style.transform = 'scaleX(0)';
          visible = false;
        }
      });
    },

    /**
     * Fügt Ripple-Effekte zu allen Buttons mit der Klasse .ripple hinzu.
     */
    initRippleEffects() {
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('.ripple');
        if (!btn) return;

        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        const ripple = document.createElement('span');
        ripple.classList.add('ripple-effect');
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';

        btn.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
      });
    },

    /**
     * Lazy-Loading für Bilder und Module via Intersection Observer.
     */
    initIntersectionObserver() {
      const images = document.querySelectorAll('img[data-src]');
      if (!images.length || !('IntersectionObserver' in window)) return;

      const imgObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            imgObserver.unobserve(img);
          }
        });
      });

      images.forEach((img) => imgObserver.observe(img));
    }
  };

  // Automatische Initialisierung nach DOM-Ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.CarfifyApp.init());
  } else {
    window.CarfifyApp.init();
  }
})();