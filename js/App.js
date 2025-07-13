const { useState, useEffect, useRef } = React;

// Custom Hook für den Schreibmaschinen-Effekt
function useTypewriter(text, speed = 50) {
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
const Spinner = ({ text }) => (
    <div className="flex items-center justify-center gap-2">
        <svg className="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span>{text}</span>
    </div>
);

// Interaktive Diagnose-Komponente
function InteractiveDiagnosis({ initialProblem, vehicleInfo, onDiagnosisComplete }) {
    const [history, setHistory] = useState([{ role: "user", parts: [{ text: `Ursprüngliches Problem: ${initialProblem}. Fahrzeug: ${vehicleInfo}` }] }]);
    const [currentQuestion, setCurrentQuestion] = useState(null);
    const [currentAnswers, setCurrentAnswers] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');
    const displayedQuestion = useTypewriter(currentQuestion);
    const endOfChatRef = useRef(null);

    useEffect(() => {
        fetchNextStep();
    }, []); // Nur beim ersten Mal ausführen

    useEffect(() => {
        if (!isLoading) {
            endOfChatRef.current?.scrollIntoView({ behavior: 'smooth' });
        }
    }, [displayedQuestion, isLoading]);

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

            // Verbesserte Fehlerbehandlung
            if (!response.ok) {
                const errorText = await response.text();
                let errorJson = {};
                try {
                    errorJson = JSON.parse(errorText);
                    throw new Error(errorJson.message || `Dialog-API Fehler (${response.status})`);
                } catch (e) {
                    // Wenn das Parsen fehlschlägt, war die Antwort kein JSON. Zeige den rohen Text an.
                    throw new Error(`Server-Fehler (${response.status}): ${errorText}`);
                }
            }

            const data = await response.json();
            if (data.finalDiagnosis) {
                onDiagnosisComplete(data.finalDiagnosis);
            } else if (data.nextQuestion) {
                setCurrentQuestion(data.nextQuestion);
                setCurrentAnswers(data.answers);
            } else {
                throw new Error("Unerwartete Antwort von der KI erhalten.");
            }
        } catch (err) {
            setError('Fehler im Dialog: ' + err.message);
        } finally {
            setIsLoading(false);
        }
    };

    const handleAnswerClick = (answer) => {
        const updatedHistory = [
            ...history,
            { role: "model", parts: [{ text: currentQuestion }] },
            { role: "user", parts: [{ text: `Antwort: ${answer}` }] }
        ];
        setHistory(updatedHistory);
        fetchNextStep(updatedHistory);
    };

    return (
        <div className="p-5 bg-slate-100 border border-slate-200/80 rounded-xl">
            <h2 className="text-xl font-bold text-slate-800 mb-4">Interaktive Diagnose</h2>
            <div className="space-y-4">
                <div className="p-4 bg-white rounded-lg min-h-[6rem] flex items-center">
                    <p className="text-slate-700 font-semibold">{displayedQuestion}</p>
                    {isLoading && <Spinner text="KI denkt nach..." />}
                </div>
                {!isLoading && currentAnswers.length > 0 && (
                    <div className="flex flex-wrap gap-2 justify-center">
                        {currentAnswers.map((answer, i) => (
                            <button key={i} onClick={() => handleAnswerClick(answer)} className="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                                {answer}
                            </button>
                        ))}
                    </div>
                )}
                <div ref={endOfChatRef} />
                {error && <p className="text-sm text-red-500 text-center">{error}</p>}
            </div>
        </div>
    );
}

// Haupt-App-Komponente
function App() {
    const [hsn, setHsn] = useState('');
    const [tsn, setTsn] = useState('');
    const [problemText, setProblemText] = useState('');
    const [foundVehicle, setFoundVehicle] = useState(null);
    const [isFindingVehicle, setIsFindingVehicle] = useState(false);
    const [error, setError] = useState('');
    const [startInteractive, setStartInteractive] = useState(false);
    const [finalDiagnosis, setFinalDiagnosis] = useState(null);

    const handleStartDiagnosis = () => {
        if (!problemText.trim()) {
            setError('Bitte beschreiben Sie zuerst Ihr Problem.');
            return;
        }
        setError('');
        setStartInteractive(true);
    };

    const handleDiagnosisComplete = (diagnosis) => {
        setFinalDiagnosis(diagnosis);
        setStartInteractive(false);
    };

    return (
        <div className="min-h-screen p-4 md:p-8 flex justify-center items-start">
            <div className="bg-white p-6 md:p-8 rounded-2xl shadow-lg shadow-slate-200/50 w-full max-w-4xl border border-slate-200/80">
                <header className="text-center mb-8"><img src="/logo.png" alt="Carfify Logo" className="mx-auto h-24 w-auto" /></header>

                {error && <div className="p-3 mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg"><p>{error}</p></div>}

                {!startInteractive && !finalDiagnosis && (
                    <div className="fade-in">
                        <div className="p-5 bg-slate-50 border border-slate-200/80 rounded-xl mb-6">
                            <h2 className="text-lg font-bold text-slate-800 mb-3">1. Fahrzeug identifizieren <span className="text-sm font-normal text-slate-500">(Optional)</span></h2>
                            <div className="flex flex-col sm:flex-row items-start gap-4">
                                <div className="w-full sm:w-auto flex-1"><label htmlFor="hsn" className="block text-slate-700 text-sm font-semibold mb-1">HSN</label><input type="text" id="hsn" maxLength="4" className="w-full p-2 border border-slate-300 rounded-lg" placeholder="z.B. 0603" value={hsn} onChange={(e) => setHsn(e.target.value)} /></div>
                                <div className="w-full sm:w-auto flex-1"><label htmlFor="tsn" className="block text-slate-700 text-sm font-semibold mb-1">TSN</label><input type="text" id="tsn" maxLength="3" className="w-full p-2 border border-slate-300 rounded-lg" placeholder="z.B. BJM" value={tsn} onChange={(e) => setTsn(e.target.value.toUpperCase())} /></div>
                                <div className="w-full sm:w-auto self-end"><button disabled={isFindingVehicle} className="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-2 px-4 rounded-lg transition disabled:bg-slate-400 flex items-center justify-center gap-2">{isFindingVehicle ? <Spinner text="Suchen..."/> : <><i className="fa-solid fa-search"></i> <span>Finden</span></>}</button></div>
                            </div>
                        </div>
                        <div className="p-5 bg-slate-50 border border-slate-200/80 rounded-xl mb-6">
                            <h2 className="text-lg font-bold text-slate-800 mb-3">2. Problem beschreiben</h2>
                            <textarea id="problem" className="w-full p-3 border border-slate-300 rounded-lg" placeholder="z.B. Mein Auto quietscht beim Bremsen und zieht nach links..." value={problemText} onChange={(e) => setProblemText(e.target.value)} rows="4"></textarea>
                        </div>
                        <button onClick={handleStartDiagnosis} className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg" disabled={!problemText}>
                            <span><i className="fa-solid fa-comments mr-2"></i>Interaktive Diagnose starten</span>
                        </button>
                    </div>
                )}

                {startInteractive && (
                    <InteractiveDiagnosis 
                        initialProblem={problemText} 
                        vehicleInfo={foundVehicle ? foundVehicle.name : 'Nicht angegeben'}
                        onDiagnosisComplete={handleDiagnosisComplete} 
                    />
                )}

                {finalDiagnosis && (
                    <div className="p-5 bg-green-50 border border-green-200 rounded-xl fade-in">
                        <h2 className="text-xl font-bold text-green-800 mb-4">Finale KI-Diagnose</h2>
                        <p className="text-slate-700 whitespace-pre-wrap">{finalDiagnosis}</p>
                        <button onClick={() => { setFinalDiagnosis(null); setProblemText(''); }} className="mt-4 bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold py-2 px-4 rounded-lg transition">Neue Diagnose starten</button>
                    </div>
                )}
            </div>
        </div>
    );
}

const container = document.getElementById('root');
const root = ReactDOM.createRoot(container);
root.render(<App />);
