const { useState, useEffect, useRef } = React;

// Custom Hook für den Schreibmaschinen-Effekt
function useTypewriter(text, speed = 50) {
    const [displayText, setDisplayText] = useState('');
    useEffect(() => {
        if (!text) return;
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

// Interaktive Diagnose-Komponente
function InteractiveDiagnosis({ initialProblem, onDiagnosisComplete }) {
    const [history, setHistory] = useState([{ role: "user", parts: [{ text: `Ursprüngliches Problem: ${initialProblem}` }] }]);
    const [currentQuestion, setCurrentQuestion] = useState(null);
    const [currentAnswers, setCurrentAnswers] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');
    const displayedQuestion = useTypewriter(currentQuestion);
    const endOfChatRef = useRef(null);

    useEffect(() => {
        fetchNextStep();
    }, [history]);

    useEffect(() => {
        endOfChatRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [displayedQuestion]);

    const fetchNextStep = async () => {
        setIsLoading(true);
        setError('');
        try {
            const response = await fetch('/api/diagnose', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ history })
            });
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Dialog-API Fehler');
            }
            const data = await response.json();
            if (data.finalDiagnosis) {
                onDiagnosisComplete(data.finalDiagnosis);
            } else if (data.nextQuestion) {
                setCurrentQuestion(data.nextQuestion);
                setCurrentAnswers(data.answers);
            }
        } catch (err) {
            setError('Fehler im Dialog: ' + err.message);
        } finally {
            setIsLoading(false);
        }
    };

    const handleAnswerClick = (answer) => {
        const newHistory = [
            ...history,
            { role: "model", parts: [{ text: currentQuestion }] },
            { role: "user", parts: [{ text: `Antwort: ${answer}` }] }
        ];
        setHistory(newHistory);
        setCurrentQuestion(null);
        setCurrentAnswers([]);
    };

    return (
        <div className="p-5 bg-slate-100 border border-slate-200/80 rounded-xl">
            <h2 className="text-xl font-bold text-slate-800 mb-4">Interaktive Diagnose</h2>
            <div className="space-y-4">
                <div className="p-4 bg-white rounded-lg min-h-[6rem]">
                    <p className="text-slate-700 font-semibold">{displayedQuestion}</p>
                    {isLoading && !currentQuestion && <Spinner text="KI denkt nach..." />}
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
    const [problemText, setProblemText] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const [initialAnalysis, setInitialAnalysis] = useState(null);
    const [startInteractive, setStartInteractive] = useState(false);
    const [finalDiagnosis, setFinalDiagnosis] = useState(null);

    const handleSubmit = async () => {
        if (!problemText.trim()) { setError('Bitte beschreiben Sie zuerst Ihr Problem.'); return; }
        setIsLoading(true);
        setError('');
        setInitialAnalysis(null);
        setStartInteractive(false);
        setFinalDiagnosis(null);

        // Für dieses Beispiel starten wir direkt den interaktiven Dialog
        // anstatt einer ersten Analyse.
        setStartInteractive(true);
        setIsLoading(false);
    };

    const handleDiagnosisComplete = (diagnosis) => {
        setFinalDiagnosis(diagnosis);
        setStartInteractive(false); // Dialog beenden
    };

    return (
        <div className="min-h-screen p-4 md:p-8 flex justify-center items-start">
            <div className="bg-white p-6 md:p-8 rounded-2xl shadow-lg shadow-slate-200/50 w-full max-w-4xl border border-slate-200/80">
                <header className="text-center mb-8"><img src="/logo.png" alt="Carfify Logo" className="mx-auto h-24 w-auto" /></header>

                {!startInteractive && !finalDiagnosis && (
                    <div className="fade-in">
                        <div className="p-5 bg-slate-50 border border-slate-200/80 rounded-xl mb-6">
                            <h2 className="text-lg font-bold text-slate-800 mb-3">Problem beschreiben</h2>
                            <textarea id="problem" className="w-full p-3 border border-slate-300 rounded-lg" placeholder="z.B. Mein Auto quietscht beim Bremsen und zieht nach links..." value={problemText} onChange={(e) => setProblemText(e.target.value)} rows="4"></textarea>
                        </div>
                        <button onClick={handleSubmit} className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg" disabled={isLoading || !problemText}>
                            {isLoading ? <Spinner text="Starte Assistent..." /> : <span><i className="fa-solid fa-comments mr-2"></i>Interaktive Diagnose starten</span>}
                        </button>
                    </div>
                )}

                {error && <div className="p-3 mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg"><p>{error}</p></div>}

                {startInteractive && (
                    <InteractiveDiagnosis 
                        initialProblem={problemText} 
                        onDiagnosisComplete={handleDiagnosisComplete} 
                    />
                )}

                {finalDiagnosis && (
                    <div className="p-5 bg-green-50 border border-green-200 rounded-xl fade-in">
                        <h2 className="text-xl font-bold text-green-800 mb-4">Finale KI-Diagnose</h2>
                        <p className="text-slate-700 whitespace-pre-wrap">{finalDiagnosis}</p>
                    </div>
                )}
            </div>
        </div>
    );
}

const container = document.getElementById('root');
const root = ReactDOM.createRoot(container);
root.render(<App />);
