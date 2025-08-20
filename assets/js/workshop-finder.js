class WorkshopFinder {
    constructor() {
        this.currentLocation = null;
        this.currentDiagnosis = null;
        this.workshops = [];
        this.radius = 10;
        this.sortBy = 'relevance';
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.getCurrentLocation();
    }
    
    setupEventListeners() {
        document.getElementById('radius-slider').addEventListener('input', (e) => {
            this.radius = parseInt(e.target.value);
            document.getElementById('radius-value').textContent = this.radius + ' km';
            this.searchWorkshops();
        });
        
        document.getElementById('sort-select').addEventListener('change', (e) => {
            this.sortBy = e.target.value;
            this.renderWorkshops();
        });
        
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });
    }
    
    getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.currentLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    this.searchWorkshops();
                },
                () => {
                    this.currentLocation = { lat: 52.5200, lng: 13.4050 }; // Berlin fallback
                    this.searchWorkshops();
                }
            );
        }
    }
    
    async searchWorkshops() {
        if (!this.currentLocation) return;
        
        const loadingEl = document.getElementById('workshops-loading');
        loadingEl.style.display = 'block';
        
        try {
            const response = await fetch('/api/workshop-service.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    lat: this.currentLocation.lat,
                    lng: this.currentLocation.lng,
                    radius: this.radius,
                    diagnosis: this.currentDiagnosis
                })
            });
            
            this.workshops = await response.json();
            this.renderWorkshops();
        } catch (error) {
            console.error('Error fetching workshops:', error);
        } finally {
            loadingEl.style.display = 'none';
        }
    }
    
    renderWorkshops() {
        const container = document.getElementById('workshops-list');
        container.innerHTML = '';
        
        this.sortWorkshops();
        
        this.workshops.forEach(workshop => {
            const card = this.createWorkshopCard(workshop);
            container.appendChild(card);
        });
    }
    
    sortWorkshops() {
        switch (this.sortBy) {
            case 'distance':
                this.workshops.sort((a, b) => a.distance - b.distance);
                break;
            case 'rating':
                this.workshops.sort((a, b) => b.rating - a.rating);
                break;
            case 'reviews':
                this.workshops.sort((a, b) => b.user_ratings_total - a.user_ratings_total);
                break;
            case 'relevance':
                this.workshops.sort((a, b) => (b.match_score || 0) - (a.match_score || 0));
                break;
        }
    }
    
    createWorkshopCard(workshop) {
        const card = document.createElement('div');
        card.className = 'workshop-card';
        card.innerHTML = `
            <div class="workshop-header">
                <h3>${workshop.name}</h3>
                <div class="rating">
                    <span class="stars">${this.renderStars(workshop.rating)}</span>
                    <span class="rating-text">${workshop.rating} (${workshop.user_ratings_total} Bewertungen)</span>
                </div>
            </div>
            
            <div class="workshop-info">
                <p class="address">${workshop.address}</p>
                <p class="distance">${workshop.distance} km entfernt</p>
                
                ${workshop.match_score ? `
                    <div class="match-score">
                        <span class="score">${workshop.match_score}% Match</span>
                        <span class="match-text">für Ihr ${this.currentDiagnosis}-Problem</span>
                    </div>
                ` : ''}
            </div>
            
            <div class="ai-analysis">
                <div class="pros">
                    <h4>Positiv</h4>
                    <ul>
                        ${workshop.ai_analysis.pros.slice(0, 3).map(pro => 
                            `<li>${pro.point} (${pro.count}x)</li>`
                        ).join('')}
                    </ul>
                </div>
                
                <div class="cons">
                    <h4>Negativ</h4>
                    <ul>
                        ${workshop.ai_analysis.cons.slice(0, 3).map(con => 
                            `<li>${con.point} (${con.count}x)</li>`
                        ).join('')}
                    </ul>
                </div>
            </div>
            
            <div class="trend-indicator">
                <span class="trend ${workshop.trend}">
                    ${this.renderTrendIcon(workshop.trend)}
                </span>
                <span class="price-range">Preis: ${workshop.ai_analysis.price_range}</span>
            </div>
            
            <button class="btn-primary" onclick="workshopFinder.showDetails('${workshop.place_id}')">
                Details & Termin
            </button>
        `;
        
        return card;
    }
    
    renderStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        let stars = '';
        
        for (let i = 0; i < fullStars; i++) {
            stars += '★';
        }
        if (hasHalfStar) {
            stars += '☆';
        }
        
        return stars;
    }
    
    renderTrendIcon(trend) {
        switch (trend) {
            case 'up': return '↗️ Verbessert sich';
            case 'down': return '↘️ Wird schlechter';
            case 'stable': return '➡️ Stabil';
            default: return '❓ Keine Daten';
        }
    }
    
    async showDetails(placeId) {
        const workshop = this.workshops.find(w => w.place_id === placeId);
        if (!workshop) return;
        
        const modal = document.getElementById('workshop-modal');
        const modalContent = document.getElementById('modal-content');
        
        modalContent.innerHTML = `
            <div class="modal-header">
                <h2>${workshop.name}</h2>
                <button class="close-btn" onclick="workshopFinder.closeModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="workshop-details">
                    <p><strong>Adresse:</strong> ${workshop.address}</p>
                    <p><strong>Entfernung:</strong> ${workshop.distance} km</p>
                    <p><strong>Bewertung:</strong> ${workshop.rating}/5 (${workshop.user_ratings_total} Bewertungen)</p>
                    
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed/v1/place?key=${GOOGLE_API_KEY}&q=place_id:${workshop.place_id}"
                            width="100%" 
                            height="300" 
                            style="border:0;" 
                            allowfullscreen=""
                            loading="lazy">
                        </iframe>
                    </div>
                    
                    <div class="full-analysis">
                        <h3>KI-Analyse aller Bewertungen</h3>
                        <div class="pros-cons-full">
                            <div class="pros-full">
                                <h4>✅ Häufig gelobt</h4>
                                <ul>
                                    ${workshop.ai_analysis.pros.map(pro => 
                                        `<li><strong>${pro.point}</strong> (${pro.count} Kunden)</li>`
                                    ).join('')}
                                </ul>
                            </div>
                            
                            <div class="cons-full">
                                <h4>❌ Häufig kritisiert</h4>
                                <ul>
                                    ${workshop.ai_analysis.cons.map(con => 
                                        `<li><strong>${con.point}</strong> (${con.count} Kunden)</li>`
                                    ).join('')}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="cta-section">
                    <button class="btn-primary large" onclick="workshopFinder.requestAppointment('${workshop.place_id}')">
                        Jetzt Termin anfragen
                    </button>
                </div>
            </div>
        `;
        
        modal.style.display = 'block';
    }
    
    closeModal() {
        document.getElementById('workshop-modal').style.display = 'none';
    }
    
    requestAppointment(placeId) {
        const workshop = this.workshops.find(w => w.place_id === placeId);
        if (!workshop) return;
        
        window.open(`https://www.google.com/maps/place/?q=place_id:${placeId}`, '_blank');
    }
    
    switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.getElementById(`${tabName}-tab`).style.display = 'block';
        event.target.classList.add('active');
    }
    
    setDiagnosis(diagnosis) {
        this.currentDiagnosis = diagnosis;
        this.searchWorkshops();
    }
}

const workshopFinder = new WorkshopFinder();