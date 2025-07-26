// Globale Funktionen für Carfify

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

// Chat-Funktionalität
let chatOpen = false;

function toggleChat() {
    const chatWindow = document.getElementById('chat-window');
    chatOpen = !chatOpen;
    
    if (chatOpen) {
        chatWindow.classList.remove('hidden');
        document.getElementById('chat-input-field').focus();
    } else {
        chatWindow.classList.add('hidden');
    }
}

function sendChatMessage() {
    const input = document.getElementById('chat-input-field');
    const message = input.value.trim();
    
    if (!message) return;
    
    addChatMessage(message, 'user');
    input.value = '';
    
    // Simulierte Antwort von Meister Müller
    setTimeout(() => {
        const responses = [
            'Das klingt nach einem bekannten Problem. Lassen Sie uns das gemeinsam analysieren.',
            'Ich empfehle Ihnen, zuerst eine gründliche Diagnose durchzuführen.',
            'Für dieses Symptom gibt es mehrere mögliche Ursachen.',
            'Haben Sie schon versucht, die Fehlercodes auszulesen?'
        ];
        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        addChatMessage(randomResponse, 'assistant');
    }, 1000);
}

function addChatMessage(text, sender) {
    const messagesContainer = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${sender}`;
    messageDiv.textContent = text;
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Enter-Taste im Chat
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
        const chatInput = document.getElementById('chat-input-field');
        if (chatInput) {
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendChatMessage();
                }
            });
        }
    });
}