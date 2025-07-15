const { useState, useEffect, useRef } = React;

// Custom Hook für den Schreibmaschinen-Effekt
function useTypewriter(text, speed = 30) {
    const [displayText, setDisplayText] = useState('');
    useEffect(() => {
        if (text === null || text === undefined) return;
        setDisplayText('');
        let i = 0;
        const intervalId = setInterval(() => {
            if (i < text.length) {
                setDisplayText(prev => prev + text.charAt(i));
                i++;
            } else {
                clearInterval(intervalId);
            }
        }, speed);
        return () => clearInterval(intervalId);
    }, [text, speed]);
    return displayText;
}

// Lade-Spinner Komponente
const Spinner = ({ text }) => ( <div className="flex items-center justify-center gap-2"><svg className="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>{text}</span></div> );

// Neue Komponente für die intelligente Kostenanzeige
const CostEstimateDisplay = ({ analysis }) => {
    const minCost = analysis.minCost || 100;
    const maxCost = analysis.maxCost || 1000;
    const certainty = analysis.diagnosisCertainty || 50;
    const costRange = maxCost - minCost;
    const isHighUncertainty = costRange > 2000 || certainty < 40;
    
    // Farbcodierung basierend auf Unsicherheit
    const getUncertaintyColor = () => {
        if (certainty >= 70) return 'green';
        if (certainty >= 40) return 'yellow';
        return 'red';
    };
    
    const colorClass = {
        green: 'bg-green-50 border-green-200',
        yellow: 'bg-yellow-50 border-yellow-200',
        red: 'bg-red-50 border-red-200'
    }[getUncertaintyColor()];
    
    const textColorClass = {
        green: 'text-green-800',
        yellow: 'text-yellow-800',
        red: 'text-red-800'
    }[getUncertaintyColor()];

    return (
        <div className={`!mt-6 p-4 border rounded-lg ${colorClass}`}>
            <div className="text-center">
                <p className="text-slate-600 text-sm">Geschätzte Reparaturkosten:</p>
                
                {/* Visuelle Kostendarstellung */}
                <div className="mt-3 mb-3">
                    <div className="flex justify-between items-end h-16">
                        <div className="text-center">
                            <p className="text-xs text-slate-500">Minimum</p>
                            <p className="text-2xl font-bold text-green-600">{minCost}€</p>
                        </div>
                        
                        {/* Kostenbereich-Visualisierung */}
                        <div className="flex-1 mx-4 relative">
                            <div className="h-2 bg-slate-200 rounded-full relative">
                                <div 
                                    className={`h-full rounded-full ${
                                        getUncertaintyColor() === 'green' ? 'bg-green-500' :
                                        getUncertaintyColor() === 'yellow' ? 'bg-yellow-500' : 'bg-red-500'
                                    }`}
                                    style={{width: '100%'}}
                                />
                            </div>
                            <p className="text-xs text-center mt-1 text-slate-500">
                                Spanne: {costRange}€
                            </p>
                        </div>
                        
                        <div className="text-center">
                            <p className="text-xs text-slate-500">Maximum</p>
                            <p className="text-2xl font-bold text-red-600">{maxCost}€</p>
                        </div>
                    </div>
                </div>
                
                {/* Diagnose-Sicherheit */}
                <div className="mt-4 p-3 bg-white rounded-lg">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-sm font-semibold">Diagnose-Sicherheit:</span>
                        <span className={`text-lg font-bold ${textColorClass}`}>
                            {certainty}%
                        </span>
                    </div>
                    <div className="w-full bg-slate-200 rounded-full h-3">
                        <div 
                            className={`h-3 rounded-full transition-all ${
                                getUncertaintyColor() === 'green' ? 'bg-green-500' :
                                getUncertaintyColor() === 'yellow' ? 'bg-yellow-500' : 'bg-red-500'
                            }`}
                            style={{width: `${certainty}%`}}
                        />
                    </div>
                </div>
                
                {/* Erklärung zur Kostenunsicherheit */}
                {analysis.costUncertaintyReason && (
                    <div className="mt-3 p-2 bg-amber-100 border border-amber-200 rounded-md">
                        <p className="text-xs text-amber-800">
                            <i className="fa-solid fa-info-circle mr-1"></i>
                            {analysis.costUncertaintyReason}
                        </p>
                    </div>
                )}
                
                {/* Call-to-Action bei hoher Unsicherheit */}
                {isHighUncertainty && (
                    <div className="mt-3 p-2 bg-blue-100 border border-blue-200 rounded-md">
                        <p className="text-xs text-blue-800 font-semibold">
                            <i className="fa-solid fa-microscope mr-1"></i>
                            Empfehlung: Starten Sie die interaktive Diagnose für eine genauere Eingrenzung
                        </p>
                    </div>
                )}
            </div>
            
            {/* Zusätzliche Diagnose-Schritte wenn nötig */}
            {analysis.diagnosticStepsNeeded && analysis.diagnosticStepsNeeded.length > 0 && (
                <div className="mt-4 pt-4 border-t border-slate-300">
                    <p className="text-sm font-semibold text-slate-700 mb-2">
                        <i className="fa-solid fa-stethoscope mr-2"></i>
                        Notwendige Diagnose-Schritte:
                    </p>
                    <ul className="text-xs text-slate-600 space-y-1">
                        {analysis.diagnosticStepsNeeded.map((step, i) => (
                            <li key={i} className="flex items-start">
                                <i className="fa-solid fa-check-circle text-blue-500 mr-2 mt-0.5 text-xs"></i>
                                {step}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
};

// Interaktive Diagnose Komponente
function InteractiveDiagnosis({ initialProblem, vehicleInfo, onDiagnosisComplete }) {
    const [history, setHistory] = useState([{ role: "user", parts: [{ text: `Ursprüngliches Problem: ${initialProblem}. Fahrzeug: ${vehicleInfo}` }] }]);
    const [currentQuestion, setCurrentQuestion] = useState(null);
    const [currentAnswers, setCurrentAnswers] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');
    const displayedQuestion = useTypewriter(currentQuestion);
    const endOfChatRef = useRef(null);

    useEffect(() => { fetchNextStep(); }, []);
    useEffect(() => { if (!isLoading) { endOfChatRef.current?.scrollIntoView({ behavior: 'smooth' }); } }, [displayedQuestion, isLoading]);

    const fetchNextStep = async (newHistory) => {
        setIsLoading(true); 
        setError(''); 
        setCurrentQuestion(''); 
        setCurrentAnswers([]);
        
        try {
            const response = await fetch('/api/diagnose', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ history: newHistory || history }) 
            });
            
            if (!response.ok) { 
                const errorText = await response.text();
                console.error("Diagnose API Error:", errorText);
                throw new Error(`Server-Fehler (${response.status})`);
            }
            
            const data = await response.json();
            console.log("Diagnose Response:", data); // Debug
            
            if (data.finalDiagnosis) {
                // Finale Diagnose mit Animation
                setCurrentQuestion("✓ Diagnose abgeschlossen!");
                setTimeout(() => {
                    onDiagnosisComplete(data.finalDiagnosis);
                }, 1500);
            } else if (data.nextQuestion && data.answers) {
                setCurrentQuestion(data.nextQuestion);
                setCurrentAnswers(data.answers);
            } else { 
                throw new Error("Unerwartete Antwort von der KI erhalten."); 
            }
        } catch (err) { 
            console.error("Diagnose Error:", err);
            setError('Fehler im Dialog: ' + err.message); 
            // Retry-Button anzeigen
            setCurrentAnswers(['Erneut versuchen']);
        } finally { 
            setIsLoading(false); 
        }
    };

    const handleAnswerClick = (answer) => {
        if (answer === 'Erneut versuchen') {
            fetchNextStep();
            return;
        }
        
        // Füge visuelle Bestätigung hinzu
        const button = event.target;
        button.classList.add('bg-green-600');
        button.disabled = true;
        
        setTimeout(() => {
            const updatedHistory = [
                ...history, 
                { role: "model", parts: [{ text: currentQuestion }] }, 
                { role: "user", parts: [{ text: answer }] }
            ];
            setHistory(updatedHistory);
            fetchNextStep(updatedHistory);
        }, 300);
    };

    return ( <div className="p-5 bg-slate-200 border border-slate-300 rounded-xl mt-6"><h2 className="text-lg font-bold text-slate-800 mb-4">Interaktive Diagnose zur Verfeinerung</h2><div className="space-y-4"><div className="p-4 bg-white rounded-lg min-h-[6rem] flex items-center"><p className="text-slate-700 font-semibold">{displayedQuestion}</p>{isLoading && <Spinner text="KI denkt nach..." />}</div>{!isLoading && currentAnswers.length > 0 && ( <div className="flex flex-wrap gap-2 justify-center">{currentAnswers.map((answer, i) => ( <button key={i} onClick={() => handleAnswerClick(answer)} className="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">{answer}</button> ))}</div> )}<div ref={endOfChatRef} />{error && <p className="text-sm text-red-500 text-center">{error}</p>}</div></div> );
}

// Werkstatt-Karte Komponente
function WorkshopCard({ workshop }) {
    const [analysis, setAnalysis] = useState(null); const [isAnalyzing, setIsAnalyzing] = useState(false); const [error, setError] = useState('');
    const handleAnalyzeReviews = async () => {
        if (!workshop.reviews || workshop.reviews.length === 0) { setError("Für diese Werkstatt liegen keine Rezensionen vor."); return; }
        setIsAnalyzing(true); setError(''); setAnalysis(null);
        try {
            const reviewText = workshop.reviews.map(r => `- "${r.text}"`).join('\n');
            const prompt = `Analysiere die folgenden Kundenrezensionen für eine Autowerkstatt. Erstelle eine strukturierte Zusammenfassung im JSON-Format. Das JSON-Objekt muss exakt diese Struktur haben: {"summary": "Eine kurze Gesamtzusammenfassung in 2-3 Sätzen.","pros": ["Ein positiver Punkt"], "cons": ["Ein negativer Punkt"]}. Hier sind die Rezensionen: ${reviewText}`;
            const response = await fetch('/api/analyze', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ prompt }) });
            if (!response.ok) { const errorData = await response.json().catch(() => ({ message: 'Unbekannter Analyse-Fehler' })); throw new Error(errorData.message || `Analyse API Fehler (${response.status})`); }
            const parsedAnalysis = await response.json(); setAnalysis(parsedAnalysis);
        } catch (err) { setError('Analyse fehlgeschlagen: ' + err.message); } finally { setIsAnalyzing(false); }
    };
    return ( <div className="bg-white p-4 rounded-lg border border-slate-200/80 shadow-sm hover:shadow-lg hover:border-blue-500 workshop-card"><div className="flex gap-4"><img src={workshop.photoUrl} alt={workshop.name} className="w-24 h-24 rounded-md object-cover bg-slate-100" onError={(e) => e.target.src='https://placehold.co/400x400/94a3b8/ffffff?text=Carfify'}/><div className="flex-1"><h3 className="font-bold text-slate-900">{workshop.name}</h3><p className="text-sm text-slate-500 mt-1"><i className="fa-solid fa-location-dot mr-2 text-slate-400"></i>{workshop.vicinity}</p><div className="text-sm text-slate-600 mt-1 flex items-center gap-2"><span className="font-bold text-amber-500">{workshop.rating}</span><i className="fa-solid fa-star text-amber-500"></i><span>({workshop.user_ratings_total} Bewertungen)</span></div></div></div><div className="mt-4">{isAnalyzing ? ( <div className="w-full bg-slate-600 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center"><Spinner text="Analysiere Rezensionen..." /></div> ) : ( <button onClick={handleAnalyzeReviews} className="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded-lg transition duration-300 text-sm"><i className="fa-solid fa-wand-magic-sparkles mr-2"></i>KI-Analyse der Rezensionen</button> )}</div>{error && <p className="text-xs text-red-500 mt-2">{error}</p>}{analysis && ( <div className="mt-4 pt-4 border-t border-slate-200 fade-in"><h4 className="font-semibold text-slate-700 mb-2">KI-Zusammenfassung:</h4>{analysis.summary && <p className="text-sm text-slate-600 italic mb-3">"{analysis.summary}"</p>}<div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">{analysis.pros && analysis.pros.length > 0 && <div><h5 className="font-semibold text-green-600 flex items-center"><i className="fa-solid fa-circle-plus mr-2"></i>Pro</h5><ul className="list-disc list-inside text-slate-600 mt-1">{analysis.pros.map((pro, i) => <li key={i}>{pro}</li>)}</ul></div>}{analysis.cons && analysis.cons.length > 0 && <div><h5 className="font-semibold text-red-600 flex items-center"><i className="fa-solid fa-circle-minus mr-2"></i>Contra</h5><ul className="list-disc list-inside text-slate-600 mt-1">{analysis.cons.map((con, i) => <li key={i}>{con}</li>)}</ul></div>}</div></div> )}</div> );
}

// Komponente für die HSN/TSN Eingabe
function VehicleIdentifier({ hsn, setHsn, tsn, setTsn, onFind, isFinding }) {
    return (
        <div className="p-5 bg-slate-50 border border-slate-200/80 rounded-xl mb-6">
            <h2 className="text-lg font-bold text-slate-800 mb-3">1. Fahrzeug identifizieren <span className="text-sm font-normal text-slate-500">(Optional)</span></h2>
            <div className="flex flex-col sm:flex-row items-start gap-4">
                <div className="w-full sm:w-auto flex-1"><label htmlFor="hsn" className="block text-slate-700 text-sm font-semibold mb-1">HSN</label><input type="text" id="hsn" maxLength="4" className="w-full p-2 border border-slate-300 rounded-lg" placeholder="z.B. 0603" value={hsn} onChange={(e) => setHsn(e.target.value)} /></div>
                <div className="w-full sm:w-auto flex-1"><label htmlFor="tsn" className="block text-slate-700 text-sm font-semibold mb-1">TSN</label><input type="text" id="tsn" maxLength="3" className="w-full p-2 border border-slate-300 rounded-lg" placeholder="z.B. BJM" value={tsn} onChange={(e) => setTsn(e.target.value.toUpperCase())} /></div>
                <div className="w-full sm:w-auto self-end"><button onClick={onFind} disabled={isFinding} className="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded-lg transition disabled:bg-slate-400 flex items-center justify-center gap-2">{isFinding ? <Spinner text="Suchen..."/> : <><i className="fa-solid fa-search"></i> <span>Finden</span></>}</button></div>
            </div>
        </div>
    );
}

// Haupt-App-Komponente
function App() {
    const [hsn, setHsn] = useState(''); const [tsn, setTsn] = useState(''); const [problemText, setProblemText] = useState(''); const [foundVehicle, setFoundVehicle] = useState(null); const [isFindingVehicle, setIsFindingVehicle] = useState(false); const [aiAnalysis, setAiAnalysis] = useState(null); const [isLoading, setIsLoading] = useState(false); const [error, setError] = useState(''); const [workshops, setWorkshops] = useState([]); const [startInteractive, setStartInteractive] = useState(false);
    const handleFindVehicle = async () => { const vehicleDatabase = { "0603-BJM": { name: "VW Golf VIII 2.0 TDI", ps: "150 PS", year: "2019-heute", imageUrl: "https://placehold.co/600x400/e0e0e0/000000?text=VW+Golf+VIII" }}; setIsFindingVehicle(true); setFoundVehicle(null); setError(''); await new Promise(resolve => setTimeout(resolve, 1000)); const key = `${hsn}-${tsn.toUpperCase()}`; const vehicle = vehicleDatabase[key]; if (vehicle) { setFoundVehicle(vehicle); } else { setError('Fahrzeug nicht gefunden. Bitte prüfen Sie die HSN/TSN.'); } setIsFindingVehicle(false); };

    const handleSubmit = async () => {
        if (!problemText.trim()) { setError('Bitte beschreiben Sie zuerst Ihr Problem.'); return; }
        setIsLoading(true); setAiAnalysis(null); setWorkshops([]); setError(''); setStartInteractive(false);
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
        const prompt = `Analysiere das folgende Autoproblem. Problem: "${problemText}", Fahrzeug: ${vehicleInfo}`;
        const response = await fetch('/api/analyze', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ prompt }) });
        if (!response.ok) { const errorData = await response.json().catch(() => ({ message: 'Unbekannter Problem-Analyse-Fehler' })); throw new Error(errorData.message || `Problem-Analyse API Fehler (${response.status})`); }
        return await response.json();
    };

    const fetchWorkshops = async (latitude, longitude) => { 
        const response = await fetch(`/api/workshops?lat=${latitude}&lon=${longitude}`); 
        if (!response.ok) { const errorData = await response.json().catch(() => ({ message: 'Unbekannter Werkstatt-Suche-Fehler' })); throw new Error(errorData.message || `Werkstatt-Suche API Fehler (${response.status})`); } 
        return await response.json(); 
    };

    return ( <div className="min-h-screen p-4 md:p-8 flex justify-center items-start"><div className="bg-white p-6 md:p-8 rounded-2xl shadow-lg w-full max-w-4xl border"><header className="text-center mb-8"><img src="/logo.png" alt="Carfify Logo" className="mx-auto h-24 w-auto" /></header>{error && <div className="p-3 mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg"><p>{error}</p></div>}{!aiAnalysis && (<div><VehicleIdentifier hsn={hsn} setHsn={setHsn} tsn={tsn} setTsn={setTsn} onFind={handleFindVehicle} isFinding={isFindingVehicle} />{foundVehicle && ( <div className="p-5 mb-6 bg-blue-50 border border-blue-200 rounded-xl fade-in"><div className="flex flex-col sm:flex-row items-center gap-4"><img src={foundVehicle.imageUrl} onError={(e) => e.target.src='https://placehold.co/600x400/e0e0e0/000000?text=Bild+fehlt'} alt={foundVehicle.name} className="w-32 h-auto rounded-lg bg-white object-cover" /><div><p className="font-bold text-lg text-slate-800">{foundVehicle.name}</p><p className="text-sm text-slate-600">Leistung: {foundVehicle.ps}</p><p className="text-sm text-slate-600">Bauzeitraum: {foundVehicle.year}</p></div></div></div> )}<div className="p-5 bg-slate-50 border rounded-xl mb-6"><h2 className="text-lg font-bold text-slate-800 mb-3">2. Problem beschreiben</h2><textarea id="problem" className="w-full p-3 border rounded-lg" value={problemText} onChange={(e) => setProblemText(e.target.value)} rows="4"></textarea></div><button onClick={handleSubmit} className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg" disabled={isLoading || !problemText}>{isLoading ? <Spinner text="Analysiere & Suche..." /> : <span><i className="fa-solid fa-search-dollar mr-2"></i>Analyse & Werkstätten finden</span>}</button></div>)}{aiAnalysis && ( <div className="fade-in"><button onClick={() => {setAiAnalysis(null); setStartInteractive(false);}} className="mb-4 text-sm text-blue-600 hover:underline">&larr; Neue Diagnose starten</button><VehicleIdentifier hsn={hsn} setHsn={setHsn} tsn={tsn} setTsn={setTsn} onFind={handleFindVehicle} isFinding={isFindingVehicle} />{!foundVehicle && <div className="p-4 mb-6 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 rounded-lg"><h3 className="font-bold"><i className="fa-solid fa-triangle-exclamation mr-2"></i>Hinweis zur Genauigkeit</h3><p className="text-sm">Für eine präzisere Analyse, identifizieren Sie bitte Ihr Fahrzeug.</p></div>}<div className="p-5 bg-slate-100 border rounded-xl"><h2 className="text-xl font-bold text-slate-800 mb-4">KI-Analyse & Kostenschätzung</h2><div className="space-y-4">{aiAnalysis.possibleCauses && (
                        <div>
                            <strong className="text-slate-600 block mb-1">Mögliche Ursachen:</strong>
                            <ul className="list-disc list-inside ml-4 mt-1 text-slate-800 space-y-1">
                                {aiAnalysis.possibleCauses.map((cause, i) => (
                                    <li key={i} className={i === 0 ? "font-semibold text-blue-700" : ""}>
                                        {cause}
                                        {i === 0 && aiAnalysis.mostLikelyCause && (
                                            <span className="text-sm text-blue-600 ml-2">(Wahrscheinlichste Ursache)</span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}{aiAnalysis.mostLikelyCause && (
                        <div className="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <strong className="text-blue-700">Wahrscheinlichste Diagnose:</strong>
                            <p className="text-slate-700 mt-1">{aiAnalysis.mostLikelyCause}</p>
                        </div>
                    )}{aiAnalysis.recommendation && <div><strong className="text-slate-600 block mb-1">Empfehlung:</strong> <span className="text-slate-800">{aiAnalysis.recommendation}</span></div>}{aiAnalysis.urgency && (
                        <div className="flex items-center gap-2">
                            <strong className="text-slate-600">Dringlichkeit:</strong>
                            <span className={`font-bold px-3 py-1 rounded-full text-sm flex items-center gap-1 ${
                                aiAnalysis.urgency === 'Kritisch' ? 'bg-purple-100 text-purple-800 animate-pulse' : 
                                aiAnalysis.urgency === 'Hoch' ? 'bg-red-100 text-red-800' : 
                                aiAnalysis.urgency === 'Mittel' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-green-100 text-green-800'
                            }`}>
                                <i className={`fa-solid ${
                                    aiAnalysis.urgency === 'Kritisch' ? 'fa-exclamation-triangle' :
                                    aiAnalysis.urgency === 'Hoch' ? 'fa-exclamation-circle' :
                                    aiAnalysis.urgency === 'Mittel' ? 'fa-info-circle' :
                                    'fa-check-circle'
                                }`}></i>
                                {aiAnalysis.urgency}
                            </span>
                            {aiAnalysis.urgency === 'Kritisch' && (
                                <span className="text-xs text-purple-600 italic">
                                    Sofortiges Handeln erforderlich!
                                </span>
                            )}
                        </div>
                    )}{aiAnalysis.likelyRequiredParts && aiAnalysis.likelyRequiredParts.length > 0 && <div><strong className="text-slate-600 block mb-1">Benötigte Teile (Vorschläge):</strong> <div className="flex flex-wrap gap-2 mt-1">{aiAnalysis.likelyRequiredParts.map(part => (<a key={part} href={`https://www.autodoc.de/search?keyword=${encodeURIComponent(part)}`} target="_blank" rel="noopener noreferrer" className="text-sm bg-slate-200 hover:bg-slate-300 text-slate-800 px-2 py-1 rounded-md transition">{part} <i className="fa-solid fa-arrow-up-right-from-square text-xs"></i></a>))}</div></div>}<CostEstimateDisplay analysis={aiAnalysis} />{aiAnalysis.diyTips && aiAnalysis.diyTips.length > 0 && <div><strong>Für Selbermacher:</strong><ul className="list-disc list-inside ml-4 mt-1 text-sm text-slate-700">{aiAnalysis.diyTips.map((tip, i) => <li key={i}>{tip}</li>)}{aiAnalysis.youtubeSearchQuery && <li><a href={`https://www.youtube.com/results?search_query=${encodeURIComponent(aiAnalysis.youtubeSearchQuery)}`} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">Passende YouTube-Tutorials ansehen <i className="fa-solid fa-arrow-up-right-from-square text-xs"></i></a></li>}</ul></div>}</div>{!startInteractive && ( <div className="mt-6 pt-4 border-t"><p className="text-sm text-center text-slate-600 mb-2">Um das Problem weiter einzugrenzen, können wir eine interaktive Diagnose starten.</p><button onClick={() => setStartInteractive(true)} className="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded-lg"><i className="fa-solid fa-comments mr-2"></i>Interaktive Diagnose starten</button></div> )}{startInteractive && ( <InteractiveDiagnosis initialProblem={problemText} vehicleInfo={foundVehicle ? foundVehicle.name : 'Nicht angegeben'} onDiagnosisComplete={(finalDiagnosis) => { setAiAnalysis(prev => ({ ...prev, possibleCauses: [finalDiagnosis], mostLikelyCause: finalDiagnosis, recommendation: "Basierend auf der interaktiven Diagnose wurde das Problem weiter eingegrenzt.", diagnosisCertainty: 85 })); setStartInteractive(false); }} /> )}</div>{workshops.length > 0 && ( <div className="mt-8"> <h2 className="text-xl font-bold text-slate-800 mb-4">Passende Werkstätten in deiner Nähe</h2> <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">{workshops.map(shop => <WorkshopCard key={shop.place_id} workshop={shop} />)}</div> </div> )}</div> )}</div></div> );
}
const container = document.getElementById('root');
const root = ReactDOM.createRoot(container);
root.render(<App />);
