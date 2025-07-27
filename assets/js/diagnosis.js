/**
 * Carfify – Diagnose-Modul
 * Verwaltet die gesamte Logik der Fehlerdiagnose:
 *  - dynamisches 3-Säulen-Layout
 *  - Berechnung der Sicherheit
 *  - Anzeige von Anleitungen
 *  - permanenter Chat-Button
 */

export function initDiagnosis() {
  'use strict';

  const state = {
    currentStep: 0,
    answers: [],
    questions: [],
    certainty: 0,
    chatOpen: false
  };

  const elements = {
    container: document.getElementById('diagnosis-container'),
    left: document.getElementById('diagnosis-left'),
    center: document.getElementById('diagnosis-center'),
    right: document.getElementById('diagnosis-right'),
    chatBtn: document.getElementById('chat-button')
  };

  if (!elements.container) return;

  /**
   * Lädt die Fragen aus der API und startet den Diagnose-Workflow.
   */
  async function loadQuestions() {
    window.dispatchEvent(new CustomEvent('carfify:showProgress'));
    try {
      const res = await fetch('/api/diagnosis/questions.php');
      state.questions = await res.json();
      renderStep();
    } catch (err) {
      console.error('Fehler beim Laden der Fragen:', err);
      elements.container.innerHTML = '<p class="error">Fehler beim Laden der Diagnose-Daten.</p>';
    } finally {
      window.dispatchEvent(new CustomEvent('carfify:hideProgress'));
    }
  }

  /**
   * Rendert die aktuelle Frage und passt das Layout dynamisch an.
   */
  function renderStep() {
    const q = state.questions[state.currentStep];
    if (!q) {
      showResults();
      return;
    }

    // Linke Spalte: Frage + Fortschritt
    elements.left.innerHTML = `
      <h2>Schritt ${state.currentStep + 1} / ${state.questions.length}</h2>
      <p>${q.text}</p>
      <progress max="${state.questions.length}" value="${state.currentStep}"></progress>
    `;

    // Mittlere Spalte: Antwort-Optionen
    elements.center.innerHTML = '';
    q.options.forEach((opt) => {
      const btn = document.createElement('button');
      btn.className = 'btn ripple';
      btn.textContent = opt.label;
      btn.dataset.value = opt.value;
      btn.addEventListener('click', () => selectAnswer(opt));
      elements.center.appendChild(btn);
    });

    // Rechte Spalte: Hinweis / Anleitung
    elements.right.innerHTML = q.hint
      ? `<div class="hint"><strong>Hinweis:</strong> ${q.hint}</div>`
      : '';

    updateCertainty();
  }

  /**
   * Wird aufgerufen, wenn der Nutzer eine Antwort auswählt.
   * @param {Object} option - Gewählte Antwort-Option
   */
  function selectAnswer(option) {
    state.answers.push(option);
    state.currentStep++;
    renderStep();
  }

  /**
   * Berechnet die aktuelle Sicherheit basierend auf den Antworten.
   * @returns {number} Sicherheit in Prozent (0-100)
   */
  function calculateCertainty() {
    if (!state.answers.length) return 0;
    const totalWeight = state.answers.reduce((sum, ans) => sum + (ans.weight || 1), 0);
    const maxWeight = state.questions.length * 1; // Annahme: max Gewicht = 1 pro Frage
    return Math.min(100, Math.round((totalWeight / maxWeight) * 100));
  }

  /**
   * Aktualisiert die Sicherheitsanzeige.
   */
  function updateCertainty() {
    state.certainty = calculateCertainty();
    const bar = document.getElementById('certainty-bar');
    if (bar) {
      bar.style.width = state.certainty + '%';
      bar.setAttribute('aria-valuenow', state.certainty);
    }
  }

  /**
   * Zeigt die Ergebnisübersicht nach Abschluss der Diagnose.
   */
  function showResults() {
    elements.left.innerHTML = '<h2>Diagnose abgeschlossen</h2>';
    elements.center.innerHTML = `
      <p>Basierend auf Ihren Angaben haben wir eine Sicherheit von <strong>${state.certainty}%</strong> erreicht.</p>
      <button class="btn primary ripple" id="restart-diagnosis">Neue Diagnose starten</button>
    `;
    elements.right.innerHTML = '';
    document.getElementById('restart-diagnosis').addEventListener('click', resetDiagnosis);
  }

  /**
   * Setzt den Diagnose-Workflow zurück.
   */
  function resetDiagnosis() {
    state.currentStep = 0;
    state.answers = [];
    state.certainty = 0;
    renderStep();
  }

  /**
   * Öffnet / schließt den permanenten Chat-Button.
   */
  function toggleChat() {
    state.chatOpen = !state.chatOpen;
    elements.chatBtn.setAttribute('aria-expanded', state.chatOpen);
    const panel = document.getElementById('chat-panel');
    if (panel) {
      panel.hidden = !state.chatOpen;
    }
  }

  // Event-Listener für Chat-Button
  if (elements.chatBtn) {
    elements.chatBtn.addEventListener('click', toggleChat);
  }

  // Initial laden
  loadQuestions();

  // Öffentliche API des Moduls
  return {
    reset: resetDiagnosis,
    getCertainty: () => state.certainty
  };
}