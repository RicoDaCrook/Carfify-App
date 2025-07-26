// Diagnose-Funktionalität

function startDiagnosis() {
    showModal('diagnosis-modal');
    loadDiagnosisStep(1);
}

function loadDiagnosisStep(step, sessionUuid = null, answer = null) {
    const content = document.getElementById('diagnosis-content');
    
    fetch('api/diagnose.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `step=${step}&session_uuid=${sessionUuid || ''}&answer=${answer || ''}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.diagnosis) {
            showDiagnosisResult(data.diagnosis);
        } else if (data.question) {
            showQuestion(data.question, data.session_uuid);
        }
    })
    .catch(error => {
        content.innerHTML = '<p>Fehler beim Laden der Diagnose</p>';
    });
}

function showQuestion(question, sessionUuid) {
    const content = document.getElementById('diagnosis-content');
    let html = `
        <h3>${question.question_text_de}</h3>
        <div class="question-options">
    `;
    
    if (question.options) {
        const options = JSON.parse(question.options);
        Object.entries(options).forEach(([key, value]) => {
            html += `
                <button class="option-button" onclick="loadDiagnosisStep(${question.step}, '${sessionUuid}', '${key}')">
                    ${value}
                </button>
            `;
        });
    }
    
    html += '</div>';
    content.innerHTML = html;
}

function showDiagnosisResult(diagnosis) {
    const content = document.getElementById('diagnosis-content');
    
    let html = `
        <h3>Diagnose-Ergebnis</h3>
        <div class="diagnosis-result">
            <h4>${diagnosis.problem}</h4>
            <p>${diagnosis.description}</p>
            <div class="severity ${diagnosis.severity}">
                Schweregrad: ${diagnosis.severity}
            </div>
            <div class="estimated-cost">
                Geschätzte Kosten: ${diagnosis.estimated_cost}
            </div>
            <h5>Nächste Schritte:</h5>
            <ul>
                ${diagnosis.next_steps.map(step => `<li>${step}</li>`).join('')}
            </ul>
            <button class="cta-button" onclick="findWorkshops()">Geeignete Werkstatt finden</button>
        </div>
    `;
    
    content.innerHTML = html;
}

function findWorkshops() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            loadWorkshops(position.coords.latitude, position.coords.longitude);
        });
    } else {
        // Standard: Berlin
        loadWorkshops(52.5200, 13.4050);
    }
}

function loadWorkshops(lat, lng, page = 1) {
    const content = document.getElementById('diagnosis-content');
    
    fetch(`api/workshops.php?lat=${lat}&lng=${lng}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            displayWorkshops(data.workshops, page);
        });
}

function displayWorkshops(workshops, page) {
    const content = document.getElementById('diagnosis-content');
    
    let html = '<h3>Empfohlene Werkstätten</h3><div class="workshops-list">';
    
    workshops.forEach(workshop => {
        html += `
            <div class="workshop-card">
                <h4>${workshop.name}</h4>
                <p>${workshop.address}</p>
                <div class="workshop-rating">
                    ${'★'.repeat(Math.floor(workshop.rating))}${'☆'.repeat(5-Math.floor(workshop.rating))}
                    ${workshop.rating} (${workshop.review_count} Bewertungen)
                </div>
                <div class="workshop-specializations">
                    ${workshop.specializations.map(spec => `<span class="specialization-tag">${spec}</span>`).join('')}
                </div>
                <button onclick="analyzeReviews(${workshop.id})">Bewertungen analysieren</button>
            </div>
        `;
    });
    
    html += '</div>';
    
    if (workshops.length === 10) {
        html += `<button onclick="loadMoreWorkshops(${page + 1})">Mehr laden</button>`;
    }
    
    content.innerHTML = html;
}

function analyzeReviews(workshopId) {
    fetch('api/analyze_reviews.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `workshop_id=${workshopId}`
    })
    .then(response => response.json())
    .then(data => {
        alert(`KI-Analyse:\n${data.summary}\n\nPositiv: ${data.pros.join(', ')}\nNegativ: ${data.cons.join(', ')}`);
    });
}

function loadMoreWorkshops(page) {
    // Implementation für Infinite Scroll
    const lastWorkshop = document.querySelector('.workshops-list').lastElementChild;
    const lat = lastWorkshop.dataset.lat;
    const lng = lastWorkshop.dataset.lng;
    loadWorkshops(lat, lng, page);
}