/**
 * Carfify – Animations-Modul
 * Kleine, wiederverwendbare Animationen wie Konfetti etc.
 */

export function initAnimations() {
  'use strict';

  /**
   * Löst Konfetti-Animation aus.
   * @param {HTMLElement} target - Element, an dem die Animation erscheinen soll
   */
  function showConfetti(target) {
    if (!target) return;
    const colors = ['#ff595e', '#ffca3a', '#8ac926', '#1982c4', '#6a4c93'];
    const count = 60;

    for (let i = 0; i < count; i++) {
      const confetti = document.createElement('div');
      confetti.className = 'confetti';
      confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
      confetti.style.left = Math.random() * 100 + '%';
      confetti.style.animationDuration = (Math.random() * 1 + 0.5) + 's';
      confetti.style.animationDelay = (Math.random() * 0.5) + 's';
      target.appendChild(confetti);

      setTimeout(() => confetti.remove(), 2000);
    }
  }

  // Event-Listener für Konfetti (Beispiel)
  document.addEventListener('carfify:showConfetti', (e) => {
    showConfetti(e.detail.target);
  });

  return {
    showConfetti
  };
}