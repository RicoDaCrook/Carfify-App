// HINWEIS: Dies ist die endgültig korrigierte Version, die das "weiße Fenster"-Problem behebt.

// Da React und ReactDOM global über die <script>-Tags in index.html geladen werden,
// können wir direkt darauf zugreifen.
const { useState } = React;

// HAUPT-APP-KOMPONENTE
function App() {
    // State-Management
    const [hsn, setHsn] = useState('');
    const [tsn, setTsn] = useState('');
    const [problemText, setProblemText] = useState('');
    const [foundVehicle, setFoundVehicle] = useState(null);
    const [isFindingVehicle, setIsFindingVehicle] = useState(false);
    const [aiAnalysis, setAiAnalysis] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const [showResults, setShowResults] = useState(false);

    // Simulierter API Call zur Fahrzeugdatenbank
    const handleFindVehicle = async () => {
        const vehicleDatabase = {
            "0603-BJM": { name: "VW Golf VIII 2.0 TDI", ps: "150 PS", year: "2019-heute", imageUrl: "https://placehold.co/600x400/e0e0e0/000000?text=VW+Golf+VIII" },
            "0005-CKY": { name: "BMW 3er (G20) 320d", ps: "190 PS", year: "2018-heute", imageUrl: "https://placehold.co/600x400/e0e0e0/000000?text=BMW+3er+(G20)" },
        };
        setIsFindingVehicle(true);
        setFoundVehicle(null);
        setError('');
        await new Promise(resolve => setTimeout(resolve, 1000));
        const key = `${hsn}-${tsn.toUpperCase()}`;
        const vehicle = vehicleDatabase[key];
        if (vehicle) {
            setFoundVehicle(vehicle);
        } else {
            setError('Fahrzeug nicht gefunden. Bitte prüfen Sie die HSN/TSN.');
        }
        setIsFindingVehicle(false);
    };

    // API Call zur Gemini AI
    const handleSubmit = async () => {
        if (!problemText.trim()) {
            setError('Bitte beschreiben Sie zuerst Ihr Problem.');
            return;
        }
        setIsLoading(true);
        setAiAnalysis(null);
        setError('');
        setShowResults(false);

        try {
            const vehicleInfo = foundVehicle ? `User's car model: "${foundVehicle.name}"` : "User's car model: Not specified.";
            const prompt = `Analyze the following car problem and provide a structured JSON response. Do not include any text outside of the JSON object. ${vehicleInfo} User's problem description: "${problemText}" Your JSON output must follow this exact schema: {"possibleCause": "A brief, clear explanation of the likely cause.","recommendation": "What the user should do next.","urgency": "Rate the urgency as 'Niedrig', 'Mittel', or 'Hoch'.","estimatedLabor": 2.5,"likelyRequiredParts": ["Bremsscheiben vorne", "Bremsbeläge vorne"],"estimatedPartsCost": 150}`;
            
            const payload = { contents: [{ role: "user", parts: [{ text: prompt }] }] };
            const apiKey = ""; // Wird von der Umgebung bereitgestellt
            const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;
            const response = await fetch(apiUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            if (!response.ok) throw new Error(`API-Fehler: ${response.status}.`);

            const result = await response.json();
            if (result.candidates && result.candidates[0]?.content?.parts[0]?.text) {
                const textResponse = result.candidates[0].content.parts[0].text;
                const cleanedJsonString = textResponse.replace(/```json/g, '').replace(/```/g, '').trim();
                const parsedAnalysis = JSON.parse(cleanedJsonString);
                setAiAnalysis(parsedAnalysis);
                setShowResults(true);
            } else {
                throw new Error('Die KI-Antwort hatte ein unerwartetes Format.');
            }
        } catch (err) {
            console.error("Error during analysis:", err);
            setError(`Ein Fehler ist aufgetreten: ${err.message}.`);
        } finally {
            setIsLoading(false);
        }
    };
    
    // Die JSX-Struktur, die die App anzeigt
    return (
        <div className="min-h-screen p-4 md:p-8 flex justify-center items-start" style={{ fontFamily: "'Inter', sans-serif", backgroundColor: '#f1f5f9' }}>
            <div className="bg-white p-6 md:p-8 rounded-2xl shadow-lg shadow-slate-200/50 w-full max-w-4xl border border-slate-200/80">
                <header className="text-center mb-8">
                    <img src="./logo.png" alt="Carfify Logo" className="mx-auto h-24 w-auto" />
                </header>

                <div className="p-5 bg-slate-50 border border-slate-200/80 rounded-xl mb-6">
                    <h2 className="text-lg font-bold text-slate-800 mb-3">1. Fahrzeug identifizieren <span className="text-sm font-normal text-slate-500">(Optional)</span></h2>
                    <div className="flex flex-col sm:flex-row items-start gap-4">
                        <div className="w-full sm:w-auto flex-1"><label htmlFor="hsn" className="block text-slate-700 text-sm font-semibold mb-1">HSN</label><input type="text" id="hsn" maxLength="4" className="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500" placeholder="z.B. 0603" value={hsn} onChange={(e) => setHsn(e.target.value)} /></div>
                        <div className="w-full sm:w-auto flex-1"><label htmlFor="tsn" className="block text-slate-700 text-sm font-semibold mb-1">TSN</label><input type="text" id="tsn" maxLength="3" className="w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500" placeholder="z.B. BJM" value={tsn} onChange={(e) => setTsn(e.target.value.toUpperCase())} /></div>
                        <div className="w-full sm:w-auto self-end"><button onClick={handleFindVehicle} disabled={isFindingVehicle || hsn.length !== 4 || tsn.length !== 3} className="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded-lg transition disabled:bg-slate-400 flex items-center justify-center gap-2"><i className="fa-solid fa-search"></i> <span>Finden</span></button></div>
                    </div>
                </div>

                {error && <div className="fade-in mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg text-sm"><p>{error}</p></div>}
                
                {foundVehicle && (
                    <div className="fade-in p-5 bg-blue-50 border border-blue-200 rounded-xl mb-6">
                        <div className="flex flex-col sm:flex-row items-center gap-4">
                            <img src={foundVehicle.imageUrl} onError={(e) => e.target.src='https://placehold.co/600x400/e0e0e0/000000?text=Bild+fehlt'} alt={foundVehicle.name} className="w-32 h-auto rounded-lg bg-white object-cover" />
                            <div><p className="font-bold text-lg text-slate-800">{foundVehicle.name}</p><p className="text-sm text-slate-600">Leistung: {foundVehicle.ps}</p><p className="text-sm text-slate-600">Bauzeitraum: {foundVehicle.year}</p></div>
                        </div>
                    </div>
                )}

                <div className="p-5 bg-slate-50 border border-slate-200/80 rounded-xl mb-6">
                    <h2 className="text-lg font-bold text-slate-800 mb-3">2. Problem beschreiben</h2>
                    <textarea id="problem" className="w-full p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500" placeholder="z.B. Mein Auto macht vorne rechts ein schleifendes Geräusch beim Lenken..." value={problemText} onChange={(e) => setProblemText(e.target.value)} rows="3"></textarea>
                </div>
                <button onClick={handleSubmit} className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-blue-400 disabled:scale-100 flex items-center justify-center gap-2" disabled={isLoading || !problemText}>
                    {isLoading ? (
                        <div className="flex items-center justify-center gap-2">
                            <svg className="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Analysiere...</span>
                        </div>
                    ) : (
                        <span><i className="fa-solid fa-microchip mr-2"></i>Analyse & Kostenschätzung starten</span>
                    )}
                </button>

                {showResults && aiAnalysis && (
                     <div className="fade-in mt-8 space-y-6">
                       <p>Analyse erfolgreich! (Hier würden die Ergebnisse angezeigt)</p>
                    </div>
                )}
            </div>
        </div>
    );
}

// DER ZÜNDSCHLÜSSEL
// Dieser Code sucht das <div id="root"> in deiner index.html und sagt React,
// dass es die <App /> Komponente dort hineinzeichnen soll.
const container = document.getElementById('root');
const root = ReactDOM.createRoot(container);
root.render(<App />);
