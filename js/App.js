const { useState } = React;

// Lade-Spinner Komponente
const Spinner = ({ text }) => (
    <div className="flex items-center justify-center gap-2">
        <svg className="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span>{text}</span>
    </div>
);

// Werkstatt-Karte Komponente
function WorkshopCard({ workshop }) {
    const [analysis, setAnalysis] = useState(null);
    const [isAnalyzing, setIsAnalyzing] = useState(false);
    const [error, setError] = useState('');

    const handleAnalyzeReviews = async () => {
        if (!workshop.reviews || workshop.reviews.length === 0) { setError("Für diese Werkstatt liegen keine Rezensionen vor."); return; }
        setIsAnalyzing(true); setError(''); setAnalysis(null);
        try {
            const reviewText = workshop.reviews.map(r => `- "${r.text}"`).join('\n');
            const prompt = `Analysiere die folgenden Kundenrezensionen für eine Autowerkstatt. Erstelle eine strukturierte Zusammenfassung im JSON-Format. Das JSON-Objekt muss exakt diese Struktur haben: {"summary": "Eine kurze Gesamtzusammenfassung in 2-3 Sätzen.","pros": ["Ein positiver Punkt"], "cons": ["Ein negativer Punkt"]}. Hier sind die Rezensionen: ${reviewText}`;
            const response = await fetch('/api/analyze', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ prompt }) });
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Unbekannter Analyse-Fehler' }));
                throw new Error(errorData.message || `Analyse API Fehler (${response.status})`);
            }
            const parsedAnalysis = await response.json();
            setAnalysis(parsedAnalysis);
        } catch (err) { setError('Analyse fehlgeschlagen: ' + err.message); } finally { setIsAnalyzing(false); }
    };

    return (
        <div className="bg-white p-4 rounded-lg border border-slate-200/80 shadow-sm hover:shadow-lg hover:border-blue-500 workshop-card">
            <div className="flex gap-4">
                <img src={workshop.photoUrl} alt={workshop.name} className="w-24 h-24 rounded-md object-cover bg-slate-100" onError={(e) => e.target.src='https://placehold.co/400x400/94a3b8/ffffff?text=Carfify'}/>
                <div className="flex-1">
                    <h3 className="font-bold text-slate-900">{workshop.name}</h3>
                    <p className="text-sm text-slate-500 mt-1"><i className="fa-solid fa-location-dot mr-2 text-slate-400"></i>{workshop.vicinity}</p>
                    <div className="text-sm text-slate-600 mt-1 flex items-center gap-2">
                        <span className="font-bold text-amber-500">{workshop.rating}</span>
                        <i className="fa-solid fa-star text-amber-500"></i>
                        <span>({workshop.user_ratings_total} Bewertungen)</span>
                    </div>
                </div>
            </div>
            <div className="mt-4">
                {isAnalyzing ? ( <div className="w-full bg-slate-600 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center"><Spinner text="Analysiere Rezensionen..." /></div> ) : ( <button onClick={handleAnalyzeReviews} className="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded-lg transition duration-300 text-sm"><i className="fa-solid fa-wand-magic-sparkles mr-2"></i>KI-Analyse der Rezensionen</button> )}
            </div>
            {error && <p className="text-xs text-red-500 mt-2">{error}</p>}
            {analysis && ( <div className="mt-4 pt-4 border-t border-slate-200 fade-in"><h4 className="font-semibold text-slate-700 mb-2">KI-Zusammenfassung:</h4>{analysis.summary && <p className="text-sm text-slate-600 italic mb-3">"{analysis.summary}"</p>}<div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">{analysis.pros && analysis.pros.length > 0 && <div><h5 className="font-semibold text-green-600 flex items-center"><i className="fa-solid fa-circle-plus mr-2"></i>Pro</h5><ul className="list-disc list-inside text-slate-600 mt-1">{analysis.pros.map((pro, i) => <li key={i}>{pro}</li>)}</ul></div>}{analysis.cons && analysis.cons.length > 0 && <div><h5 className="font-semibold text-red-600 flex items-center"><i className="fa-solid fa-circle-minus mr-2"></i>Contra</h5><ul className="list-disc list-inside text-slate-600 mt-1">{analysis.cons.map((con, i) => <li key={i}>{con}</li>)}</ul></div>}</div></div> )}
        </div>
    );
}

// Haupt-App-Komponente
function App() {
    const [hsn, setHsn] = useState(''); const [tsn, setTsn] = useState(''); const [problemText, setProblemText] = useState(''); const [foundVehicle, setFoundVehicle] = useState(null); const [isFindingVehicle, setIsFindingVehicle] = useState(false); const [aiAnalysis, setAiAnalysis] = useState(null); const [isLoading, setIsLoading] = useState(false); const [error, setError] = useState(''); const [workshops, setWorkshops] = useState([]);
    
    const handleFindVehicle = async () => { 
        const vehicleDatabase = { "0603-BJM": { name: "VW Golf VIII 2.0 TDI", ps: "150 PS", year: "2019-heute", imageUrl: "https://placehold.co/600x400/e0e0e0/000000?text=VW+Golf+VIII" }}; 
        setIsFindingVehicle(true); setFoundVehicle(null); setError(''); await new Promise(resolve => setTimeout(resolve, 1000)); const key = `${hsn}-${tsn.toUpperCase()}`; const vehicle = vehicleDatabase[key]; if (vehicle) { setFoundVehicle(vehicle); } else { setError('Fahrzeug nicht gefunden. Bitte prüfen Sie die HSN/TSN.'); } setIsFindingVehicle(false); 
    };
    
    const handleSubmit = async () => {
        if (!problemText.trim()) { setError('Bitte beschreiben Sie zuerst Ihr Problem.'); return; }
        setIsLoading(true); setAiAnalysis(null); setWorkshops([]); setError('');
        navigator.geolocation.getCurrentPosition(async (position) => {
            const { latitude, longitude } = position.coords;
            try {
                const [aiResult, workshopsResult] = await Promise.all([ fetchAiAnalysis(), fetchWorkshops(latitude, longitude) ]);
                setAiAnalysis(aiResult); setWorkshops(workshopsResult);
            } catch (err) { setError("Ein Fehler ist aufgetreten: " + err.message); } finally { setIsLoading(false); }
        }, (err) => { setError("Standort konnte nicht abgerufen werden. Bitte erteilen Sie die Erlaubnis."); setIsLoading(false); });
    };

    const fetchAiAnalysis = async () => {
        const vehicleInfo = foundVehicle ? `User's car model: "${foundVehicle.name}"` : "User's car model: Not specified.";
        const prompt = `Analysiere das folgende Autoproblem. Erstelle eine strukturierte JSON-Antwort mit den folgenden Feldern: "possibleCauses" (als Array von Strings), "recommendation" (als String), "urgency" ('Niedrig', 'Mittel', 'Hoch'), "estimatedLabor" (als Zahl), "estimatedPartsCost" (als Zahl), "likelyRequiredParts" (als Array von Strings), "diyTips" (als Array von Strings) und "youtubeSearchQuery" (als String). Problem: "${problemText}", Fahrzeug: ${vehicleInfo}`;
        const response = await fetch('/api/analyze', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ prompt }) });
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Unbekannter Problem-Analyse-Fehler' }));
            throw new Error(errorData.message || `Problem-Analyse API Fehler (${response.status})`);
        }
        return await response.json();
    };

    const fetchWorkshops = async (latitude, longitude) => { 
        const response = await fetch(`/api/workshops?lat=${latitude}&lon=${longitude}`); 
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Unbekannter Werkstatt-Suche-Fehler' }));
            throw new Error(errorData.message || `Werkstatt-Suche API Fehler (${response.status})`);
        }
        return await response.json(); 
    };

    const calculateCost = (analysis) => {
        if (!analysis || !analysis.estimatedLabor) return { min: 0, max: 0 };
        const averageLaborRate = 95; const partsMarkup = 1.2; const uncertaintyFactor = foundVehicle ? 0.20 : 0.40; const baseCost = (analysis.estimatedLabor * averageLaborRate) + (analysis.estimatedPartsCost * partsMarkup); const minCost = baseCost * (1 - uncertaintyFactor); const maxCost = baseCost * (1 + uncertaintyFactor); return { min: Math.round(minCost / 10) * 10, max: Math.round(maxCost / 10) * 10 };
    };
    const estimatedCost = calculateCost(aiAnalysis);

    return ( 
        <div className="min-h-screen p-4 md:p-8 flex justify-center items-start">
            <div className="bg-white p-6 md:p-8 rounded-2xl shadow-lg shadow-slate-200/50 w-full max-w-4xl border border-slate-200/80">
                <header className="text-center mb-8"><img src="/logo.png" alt="Carfify Logo" className="mx-auto h-24 w-auto" /></header>
                
                <div className="p-5 bg-slate-50 border border-slate-200/80 rounded-xl mb-6">
                    <h2 className="text-lg font-bold text-slate-800 mb-3">1. Fahrzeug identifizieren <span className="text-sm font-normal text-slate-500">(Optional)</span></h2>
                    <div className="flex flex-col sm:flex-row items-start gap-4">
                        <div className="w-full sm:w-auto flex-1"><label htmlFor="hsn" className="block text-slate-700 text-sm font-semibold mb-1">HSN</label><input type="text" id="hsn" maxLength="4" className="w-full p-2 border border-slate-300 rounded-lg" placeholder="z.B. 0603" value={hsn} onChange={(e) => setHsn(e.target.value)} /></div>
                        <div className="w-full sm:w-auto flex-1"><label htmlFor="tsn" className="block text-slate-700 text-sm font-semibold mb-1">TSN</label><input type="text" id="tsn" maxLength="3" className="w-full p-2 border border-slate-300 rounded-lg" placeholder="z.B. BJM" value={tsn} onChange={(e) => setTsn(e.target.value.toUpperCase())} /></div>
                        <div className="w-full sm:w-auto self-end"><button onClick={handleFindVehicle} disabled={isFindingVehicle} className="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded-lg transition disabled:bg-slate-400 flex items-center justify-center gap-2">{isFindingVehicle ? <Spinner text="Suchen..."/> : <><i className="fa-solid fa-search"></i> <span>Finden</span></>}</button></div>
                    </div>
                </div>
                {foundVehicle && ( <div className="p-5 mb-6 bg-blue-50 border border-blue-200 rounded-xl fade-in"><div className="flex flex-col sm:flex-row items-center gap-4"><img src={foundVehicle.imageUrl} onError={(e) => e.target.src='https://placehold.co/600x400/e0e0e0/000000?text=Bild+fehlt'} alt={foundVehicle.name} className="w-32 h-auto rounded-lg bg-white object-cover" /><div><p className="font-bold text-lg text-slate-800">{foundVehicle.name}</p><p className="text-sm text-slate-600">Leistung: {foundVehicle.ps}</p><p className="text-sm text-slate-600">Bauzeitraum: {foundVehicle.year}</p></div></div></div> )}

                <div className="p-5 bg-slate-50 border border-slate-200/80 rounded-xl mb-6">
                    <h2 className="text-lg font-bold text-slate-800 mb-3">2. Problem beschreiben</h2>
                    <textarea id="problem" className="w-full p-3 border border-slate-300 rounded-lg" placeholder="z.B. Mein Auto quietscht beim Bremsen..." value={problemText} onChange={(e) => setProblemText(e.target.value)} rows="3"></textarea>
                </div>

                <button onClick={handleSubmit} className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg" disabled={isLoading || !problemText}>
                    {isLoading ? <Spinner text="Analysiere & Suche..." /> : <span><i className="fa-solid fa-search-dollar mr-2"></i>Analyse & Werkstätten finden</span>}
                </button>

                {error && <div className="p-3 mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg"><p>{error}</p></div>}
                
                {(aiAnalysis || workshops.length > 0) && (
                    <div className="mt-8 space-y-8 fade-in">
                        {!foundVehicle && aiAnalysis && <div className="p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 rounded-lg"><h3 className="font-bold"><i className="fa-solid fa-triangle-exclamation mr-2"></i>Hinweis zur Genauigkeit</h3><p className="text-sm">Für eine präzisere Analyse, identifizieren Sie bitte Ihr Fahrzeug.</p></div>}
                        {aiAnalysis && (
                            <div className="p-5 bg-slate-100 border border-slate-200/80 rounded-xl">
                                <h2 className="text-xl font-bold text-slate-800 mb-4">KI-Analyse & Kostenschätzung</h2>
                                <div className="space-y-4">
                                    {aiAnalysis.possibleCauses && aiAnalysis.possibleCauses.length > 0 && <div><strong className="text-slate-600 block mb-1">Mögliche Ursachen:</strong><ul className="list-disc list-inside ml-4 mt-1 text-slate-800"> {aiAnalysis.possibleCauses.map((cause, i) => <li key={i}>{cause}</li>)} </ul></div>}
                                    {aiAnalysis.recommendation && <div><strong className="text-slate-600 block mb-1">Empfehlung:</strong> <span className="text-slate-800">{aiAnalysis.recommendation}</span></div>}
                                    {aiAnalysis.urgency && <div><strong className="text-slate-600">Dringlichkeit:</strong> <span className={`font-bold px-2 py-0.5 rounded-full text-sm ${aiAnalysis.urgency === 'Hoch' ? 'bg-red-100 text-red-800' : aiAnalysis.urgency === 'Mittel' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'}`}>{aiAnalysis.urgency}</span></div>}
                                    
                                    {aiAnalysis.likelyRequiredParts && aiAnalysis.likelyRequiredParts.length > 0 && <div><strong className="text-slate-600 block mb-1">Benötigte Teile (Vorschläge):</strong> <div className="flex flex-wrap gap-2 mt-1">{aiAnalysis.likelyRequiredParts.map(part => (<a key={part} href={`https://www.autodoc.de/search?keyword=${encodeURIComponent(part)}`} target="_blank" rel="noopener noreferrer" className="text-sm bg-slate-200 hover:bg-slate-300 text-slate-800 px-2 py-1 rounded-md transition">{part} <i className="fa-solid fa-arrow-up-right-from-square text-xs"></i></a>))}</div></div>}
                                    
                                    {aiAnalysis.estimatedLabor && aiAnalysis.estimatedPartsCost != null && <div className="!mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-center"><p className="text-slate-600">Geschätzte Reparaturkosten:</p><p className="text-3xl font-bold text-blue-600">ca. {estimatedCost.min} - {estimatedCost.max} €</p><p className="text-xs text-slate-500 mt-1">Basiert auf geschätzter Arbeitszeit ({aiAnalysis.estimatedLabor}h) und Teilekosten.</p></div>}

                                    {aiAnalysis.diyTips && aiAnalysis.diyTips.length > 0 && <div><strong>Für Selbermacher:</strong><ul className="list-disc list-inside ml-4 mt-1 text-sm text-slate-700">{aiAnalysis.diyTips.map((tip, i) => <li key={i}>{tip}</li>)}{aiAnalysis.youtubeSearchQuery && <li><a href={`https://www.youtube.com/results?search_query=${encodeURIComponent(aiAnalysis.youtubeSearchQuery)}`} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">Passende YouTube-Tutorials ansehen <i className="fa-solid fa-arrow-up-right-from-square text-xs"></i></a></li>}</ul></div>}
                                </div>
                            </div>
                        )}
                        {workshops.length > 0 && (
                            <div>
                                <h2 className="text-xl font-bold text-slate-800 mb-4">Passende Werkstätten in deiner Nähe</h2>
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    {workshops.map(shop => <WorkshopCard key={shop.place_id} workshop={shop} />)}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div> 
    );
}

const container = document.getElementById('root');
const root = ReactDOM.createRoot(container);
root.render(<App />);
