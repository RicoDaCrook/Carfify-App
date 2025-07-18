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
const Spinner = ({ text, light = false }) => (
    <div className="flex items-center justify-center gap-2">
        <svg className="loading-spinner h-5 w-5" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
            <path className={light ? "opacity-100" : "opacity-75"} fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span className={light ? "text-gray-900" : ""}>{text}</span>
    </div>
);

// Neue Workshop-Kategorien Komponente
const WorkshopTypeInfo = ({ type }) => {
    const typeInfo = {
        dealership: {
            label: 'Vertragswerkstatt',
            color: 'text-blue-400 bg-blue-900/30 border-blue-800',
            icon: 'fa-award',
            description: 'Höhere Preise, aber optimal für Garantie & Wiederverkaufswert'
        },
        chain: {
            label: 'Werkstattkette',
            color: 'text-orange-400 bg-orange-900/30 border-orange-800',
            icon: 'fa-link',
            description: 'Mittlere Preise, standardisierte Qualität'
        },
        independent: {
            label: 'Freie Werkstatt',
            color: 'text-green-400 bg-green-900/30 border-green-800',
            icon: 'fa-wrench',
            description: 'Günstige Preise, Qualität variiert'
        },
        specialist_transmission: {
            label: 'Getriebe-Spezialist',
            color: 'text-purple-400 bg-purple-900/30 border-purple-800',
            icon: 'fa-cogs',
            description: 'Spezialisiert auf Getriebe-Reparaturen'
        },
        specialist_engine: {
            label: 'Motor-Spezialist',
            color: 'text-red-400 bg-red-900/30 border-red-800',
            icon: 'fa-engine',
            description: 'Spezialisiert auf Motor-Reparaturen'
        }
    };

    const info = typeInfo[type] || typeInfo.independent;

    return (
        <div className={`inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold border ${info.color}`}>
            <i className={`fa-solid ${info.icon}`}></i>
            <span>{info.label}</span>
        </div>
    );
};

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
        green: 'border-green-500/50 bg-green-900/20',
        yellow: 'border-yellow-500/50 bg-yellow-900/20',
        red: 'border-red-500/50 bg-red-900/20'
    }[getUncertaintyColor()];

    const textColorClass = {
        green: 'text-green-400',
        yellow: 'text-yellow-400',
        red: 'text-red-400'
    }[getUncertaintyColor()];

    return (
        <div className={`!mt-6 p-6 border rounded-xl ${colorClass} backdrop-blur-sm`}>
            <div className="text-center">
                <p className="text-gray-400 text-sm mb-4">Geschätzte Reparaturkosten:</p>

                {/* Visuelle Kostendarstellung */}
                <div className="mt-3 mb-3">
                    <div className="flex justify-between items-end h-16">
                        <div className="text-center">
                            <p className="text-xs text-gray-500">Minimum</p>
                            <p className="text-3xl font-bold text-green-400">{minCost}€</p>
                        </div>

                        {/* Kostenbereich-Visualisierung */}
                        <div className="flex-1 mx-4 relative">
                            <div className="h-3 bg-gray-800 rounded-full relative overflow-hidden">
                                <div
                                    className={`h-full rounded-full progress-bar ${
                                        getUncertaintyColor() === 'green' ? 'opacity-100' :
                                        getUncertaintyColor() === 'yellow' ? 'opacity-75' : 'opacity-50'
                                    }`}
                                />
                            </div>
                            <p className="text-xs text-center mt-2 text-gray-500">
                                Spanne: {costRange}€
                            </p>
                        </div>

                        <div className="text-center">
                            <p className="text-xs text-gray-500">Maximum</p>
                            <p className="text-3xl font-bold text-red-400">{maxCost}€</p>
                        </div>
                    </div>
                </div>

                {/* Diagnose-Sicherheit */}
                <div className="mt-6 p-4 bg-gray-900/50 rounded-lg">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-sm font-medium text-gray-300">Diagnose-Sicherheit:</span>
                        <span className={`text-lg font-bold ${textColorClass}`}>
                            {certainty}%
                        </span>
                    </div>
                    <div className="w-full bg-gray-800 rounded-full h-3 overflow-hidden">
                        <div
                            className={`h-3 rounded-full transition-all duration-500 ${
                                getUncertaintyColor() === 'green' ? 'bg-gradient-to-r from-green-500 to-green-400' :
                                getUncertaintyColor() === 'yellow' ? 'bg-gradient-to-r from-yellow-500 to-yellow-400' :
                                'bg-gradient-to-r from-red-500 to-red-400'
                            }`}
                            style={{width: `${certainty}%`}}
                        />
                    </div>
                </div>

                {/* Preisschätzung nach Werkstatt-Typ */}
                {minCost && maxCost && (
                    <div className="mt-6 p-4 bg-gray-900/50 rounded-lg">
                        <p className="text-xs font-semibold text-gray-300 mb-3">
                            <i className="fa-solid fa-coins mr-1 text-[#4cc3ee]"></i>
                            Preisschätzung nach Werkstatt-Typ:
                        </p>
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between items-center p-2 rounded bg-gray-800/50">
                                <span className="text-gray-400">
                                    <i className="fa-solid fa-award text-blue-400 mr-2"></i>
                                    Vertragswerkstatt:
                                </span>
                                <span className="font-semibold text-blue-400">{Math.round(maxCost * 0.9)}€ - {maxCost}€</span>
                            </div>
                            <div className="flex justify-between items-center p-2 rounded bg-gray-800/50">
                                <span className="text-gray-400">
                                    <i className="fa-solid fa-link text-orange-400 mr-2"></i>
                                    Werkstattkette:
                                </span>
                                <span className="font-semibold text-orange-400">
                                    {Math.round(minCost + (maxCost - minCost) * 0.4)}€ -
                                    {Math.round(minCost + (maxCost - minCost) * 0.7)}€
                                </span>
                            </div>
                            <div className="flex justify-between items-center p-2 rounded bg-gray-800/50">
                                <span className="text-gray-400">
                                    <i className="fa-solid fa-wrench text-green-400 mr-2"></i>
                                    Freie Werkstatt:
                                </span>
                                <span className="font-semibold text-green-400">{minCost}€ - {Math.round(minCost + (maxCost - minCost) * 0.5)}€</span>
                            </div>
                        </div>
                    </div>
                )}

                {/* Erklärung zur Kostenunsicherheit */}
                {analysis.costUncertaintyReason && (
                    <div className="mt-4 p-3 bg-amber-900/20 border border-amber-500/50 rounded-lg">
                        <p className="text-xs text-amber-400">
                            <i className="fa-solid fa-info-circle mr-1"></i>
                            {analysis.costUncertaintyReason}
                        </p>
                    </div>
                )}

                {/* Call-to-Action bei hoher Unsicherheit */}
                {isHighUncertainty && (
                    <div className="mt-4 p-3 bg-blue-900/20 border border-[#4cc3ee]/50 rounded-lg">
                        <p className="text-xs text-[#4cc3ee] font-medium">
                            <i className="fa-solid fa-microscope mr-1"></i>
                            Empfehlung: Starten Sie die interaktive Diagnose für eine genauere Eingrenzung
                        </p>
                    </div>
                )}
            </div>

            {/* Zusätzliche Diagnose-Schritte wenn nötig */}
            {analysis.diagnosticStepsNeeded && analysis.diagnosticStepsNeeded.length > 0 && (
                <div className="mt-6 pt-6 border-t border-gray-700">
                    <p className="text-sm font-semibold text-gray-300 mb-3">
                        <i className="fa-solid fa-stethoscope mr-2 text-[#4cc3ee]"></i>
                        Notwendige Diagnose-Schritte:
                    </p>
                    <ul className="text-xs text-gray-400 space-y-2">
                        {analysis.diagnosticStepsNeeded.map((step, i) => (
                            <li key={i} className="flex items-start">
                                <i className="fa-solid fa-check-circle text-[#4cc3ee] mr-2 mt-0.5 text-xs"></i>
                                {step}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
};

// Neue Kategoriebasierte Interaktive Diagnose Komponente
function InteractiveDiagnosisV2({ initialProblem, vehicleInfo, onDiagnosisComplete }) {
    const [stage, setStage] = useState('categories'); // categories, questions, final
    const [categories, setCategories] = useState([]);
    const [currentCategory, setCurrentCategory] = useState(null);
    const [currentQuestion, setCurrentQuestion] = useState(null);
    const [currentAnswers, setCurrentAnswers] = useState([]);
    const [questionProgress, setQuestionProgress] = useState({});
    const [history, setHistory] = useState([{
        role: "user",
        parts: [{ text: `Problem: ${initialProblem}. Fahrzeug: ${vehicleInfo}` }]
    }]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');
    const [showFreeText, setShowFreeText] = useState(false);
    const [freeTextInput, setFreeTextInput] = useState('');
    const [selfCheckGuide, setSelfCheckGuide] = useState(null);

    const displayedQuestion = useTypewriter(currentQuestion);

    // Initial: Kategorien laden
    useEffect(() => {
        if (stage === 'categories') {
            fetchCategories();
        }
    }, []);

    const fetchCategories = async () => {
        setIsLoading(true);
        setError('');
        try {
            const response = await fetch('/api/diagnose', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    history: history,
                    // KORRIGIERT: Richtiger Mode-Name
                    mode: 'analyze_categories'
                })
            });
            if (!response.ok) throw new Error(`Server-Fehler (${response.status})`);
            const data = await response.json();
            setCategories(data.categories);
            setStage('overview');
        } catch (err) {
            setError('Fehler beim Laden der Kategorien: ' + err.message);
        } finally {
            setIsLoading(false);
        }
    };

    const startCategoryDiagnosis = (category) => {
        setCurrentCategory(category);
        setStage('questions');
        setQuestionProgress({ ...questionProgress, [category.name]: 0 });
        fetchNextQuestion(category);
    };

    const fetchNextQuestion = async (category, newHistory = null) => {
        setIsLoading(true);
        setError('');
        setCurrentQuestion('');
        setCurrentAnswers([]);
        try {
            const response = await fetch('/api/diagnose', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    history: newHistory || history,
                    // KORRIGIERT: Richtiger Mode-Name
                    mode: 'category_questions'
                })
            });
            if (!response.ok) throw new Error(`Server-Fehler (${response.status})`);
            const data = await response.json();
            if (data.finalDiagnosis) {
                setStage('final');
                onDiagnosisComplete({
                    diagnosis: data.finalDiagnosis,
                    certainty: data.certainty,
                    affectedParts: data.affectedParts
                });
            } else if (data.nextQuestion) {
                setCurrentQuestion(data.nextQuestion);
                setCurrentAnswers(data.answers);
                if (data.questionNumber && data.totalQuestions) {
                    setQuestionProgress({
                        ...questionProgress,
                        [category.name]: (data.questionNumber / data.totalQuestions) * 100
                    });
                }
            }
        } catch (err) {
            setError('Fehler bei der Diagnose: ' + err.message);
        } finally {
            setIsLoading(false);
        }
    };

    const handleAnswer = (answer) => {
        if (answer === 'Weiß nicht') {
            // Zeige Selbstprüfungs-Anleitung
            showSelfCheckGuide();
            return;
        }
        const updatedHistory = [
            ...history,
            { role: "model", parts: [{ text: currentQuestion }] },
            { role: "user", parts: [{ text: answer }] }
        ];
        setHistory(updatedHistory);
        fetchNextQuestion(currentCategory, updatedHistory);
    };

    const showSelfCheckGuide = () => {
        setSelfCheckGuide({
            title: "So prüfen Sie es selbst:",
            steps: [
                "Stellen Sie sich neben das Auto",
                "Drücken Sie die Karosserie am betroffenen Rad herunter",
                "Lassen Sie los und achten Sie auf Geräusche",
                "Wiederholen Sie dies mehrmals"
            ],
            videoLink: `https://www.youtube.com/results?search_query=${encodeURIComponent(currentCategory.name + ' prüfen ohne Werkstatt')}`
        });
    };

    const handleFreeTextSubmit = async () => {
        if (!freeTextInput.trim()) return;
        setIsLoading(true);
        const updatedHistory = [
            ...history,
            { role: "user", parts: [{ text: freeTextInput }] }
        ];
        try {
            const response = await fetch('/api/diagnose', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    history: updatedHistory,
                    mode: 'freetext'
                })
            });
            const data = await response.json();
            // Zeige Antwort in einem Modal oder füge sie zur Historie hinzu
            alert(data.answer); // Temporary - sollte schöner dargestellt werden
            setFreeTextInput('');
            setShowFreeText(false);
        } catch (err) {
            setError('Fehler bei der Frage: ' + err.message);
        } finally {
            setIsLoading(false);
        }
    };

    // Render verschiedene Stages
    if (stage === 'overview') {
        return (
            <div className="p-6 bg-gray-900/50 border border-gray-700 rounded-xl mt-6 backdrop-blur-sm">
                <h2 className="text-lg font-bold text-gray-100 mb-4">
                    Mögliche Problembereiche identifiziert:
                </h2>
                <div className="space-y-3">
                    {categories.map((cat, i) => (
                        <div
                            key={i}
                            className={`p-4 rounded-lg border cursor-pointer transition-all ${
                                cat.status === 'critical' ? 'border-red-500 bg-red-900/20 hover:bg-red-900/30' :
                                cat.status === 'warning' ? 'border-yellow-500 bg-yellow-900/20 hover:bg-yellow-900/30' :
                                'border-green-500 bg-green-900/20 hover:bg-green-900/30'
                            }`}
                            onClick={() => startCategoryDiagnosis(cat)}
                        >
                            <div className="flex justify-between items-center">
                                <div>
                                    <h3 className="font-semibold text-lg flex items-center gap-2">
                                        {cat.status === 'critical' && <i className="fa-solid fa-exclamation-circle text-red-400"></i>}
                                        {cat.status === 'warning' && <i className="fa-solid fa-exclamation-triangle text-yellow-400"></i>}
                                        {cat.status === 'ok' && <i className="fa-solid fa-check-circle text-green-400"></i>}
                                        {cat.name}
                                    </h3>
                                    <p className="text-sm text-gray-400 mt-1">
                                        Wahrscheinlichkeit: {cat.probability}%
                                    </p>
                                </div>
                                <div className="text-right">
                                    <button className="btn-primary text-sm">
                                        Diagnose starten
                                        <i className="fa-solid fa-arrow-right ml-2"></i>
                                    </button>
                                </div>
                            </div>
                            {/* Progress bar wenn bereits Fragen beantwortet */}
                            {questionProgress[cat.name] > 0 && (
                                <div className="mt-3">
                                    <div className="w-full bg-gray-800 rounded-full h-2">
                                        <div
                                            className="h-2 rounded-full bg-gradient-to-r from-blue-500 to-[#4cc3ee]"
                                            style={{width: `${questionProgress[cat.name]}%`}}
                                        />
                                    </div>
                                    <p className="text-xs text-gray-400 mt-1">
                                        {Math.round(questionProgress[cat.name])}% abgeschlossen
                                    </p>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
                <div className="mt-6 text-center">
                    <button
                        onClick={() => setShowFreeText(!showFreeText)}
                        className="text-sm text-[#4cc3ee] hover:text-[#3da5cd] transition-colors"
                    >
                        <i className="fa-solid fa-comment-dots mr-2"></i>
                        Eigene Frage stellen
                    </button>
                </div>
            </div>
        );
    }

    if (stage === 'questions') {
        return (
            <div className="p-6 bg-gray-900/50 border border-gray-700 rounded-xl mt-6 backdrop-blur-sm">
                <div className="mb-4 flex justify-between items-center">
                    <h2 className="text-lg font-bold text-gray-100">
                        Diagnose: {currentCategory.name}
                    </h2>
                    <button
                        onClick={() => setStage('overview')}
                        className="text-sm text-gray-400 hover:text-gray-200"
                    >
                        <i className="fa-solid fa-arrow-left mr-1"></i>
                        Zurück zur Übersicht
                    </button>
                </div>
                {/* Progress */}
                {questionProgress[currentCategory.name] > 0 && (
                    <div className="mb-4">
                        <div className="w-full bg-gray-800 rounded-full h-2">
                            <div
                                className="h-2 rounded-full progress-bar"
                                style={{width: `${questionProgress[currentCategory.name]}%`}}
                            />
                        </div>
                        <p className="text-xs text-gray-400 mt-1">
                            Fortschritt: {Math.round(questionProgress[currentCategory.name])}%
                        </p>
                    </div>
                )}
                <div className="space-y-4">
                    <div className="p-4 bg-gray-800/50 rounded-lg min-h-[6rem] flex items-center">
                        <p className="text-gray-200 font-medium">{displayedQuestion}</p>
                        {isLoading && <Spinner text="KI analysiert..." />}
                    </div>
                    {!isLoading && currentAnswers.length > 0 && (
                        <div className="flex flex-wrap gap-2 justify-center">
                            {currentAnswers.map((answer, i) => (
                                <button
                                    key={i}
                                    onClick={() => handleAnswer(answer)}
                                    className={`py-2 px-4 rounded-lg transition-all duration-300 border ${
                                        answer === 'Weiß nicht'
                                            ? 'bg-gray-700 hover:bg-gray-600 border-gray-600 text-gray-300'
                                            : 'bg-[#1e253a] hover:bg-[#2a3246] border-gray-700 hover:border-[#4cc3ee] text-white hover:shadow-lg hover:shadow-[#4cc3ee]/20'
                                    }`}
                                >
                                    {answer}
                                </button>
                            ))}
                        </div>
                    )}
                    {error && <p className="text-sm text-red-400 text-center">{error}</p>}
                </div>
                {/* Selbstprüfungs-Anleitung */}
                {selfCheckGuide && (
                    <div className="mt-4 p-4 bg-blue-900/20 border border-[#4cc3ee]/50 rounded-lg">
                        <h3 className="font-semibold text-[#4cc3ee] mb-2">
                            <i className="fa-solid fa-info-circle mr-2"></i>
                            {selfCheckGuide.title}
                        </h3>
                        <ol className="list-decimal list-inside text-sm text-gray-300 space-y-1">
                            {selfCheckGuide.steps.map((step, i) => (
                                <li key={i}>{step}</li>
                            ))}
                        </ol>
                        <div className="mt-3 flex gap-2">
                            <a
                                href={selfCheckGuide.videoLink}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm bg-[#4cc3ee]/20 text-[#4cc3ee] px-3 py-1.5 rounded-md hover:bg-[#4cc3ee]/30 transition-colors"
                            >
                                <i className="fa-solid fa-video mr-2"></i>
                                Video-Anleitung
                            </a>
                            <button
                                onClick={() => {
                                    setSelfCheckGuide(null);
                                    handleAnswer('Geprüft - keine Auffälligkeiten');
                                }}
                                className="text-sm bg-green-900/20 text-green-400 px-3 py-1.5 rounded-md hover:bg-green-900/30"
                            >
                                Erledigt
                            </button>
                        </div>
                    </div>
                )}
            </div>
        );
    }

    // Initial Loading
    if (isLoading && stage === 'categories') {
        return (
            <div className="p-6 bg-gray-900/50 border border-gray-700 rounded-xl mt-6 backdrop-blur-sm text-center">
                <Spinner text="Analysiere Problem..." />
            </div>
        );
    }
    
    // NEU: FEHLER-FALLBACK
    if (error && !isLoading) {
        return (
            <div className="p-6 bg-gray-900/50 border border-gray-700 rounded-xl mt-6 backdrop-blur-sm">
                <div className="text-center">
                    <i className="fa-solid fa-exclamation-triangle text-red-400 text-4xl mb-4"></i>
                    <p className="text-red-400 mb-4">{error}</p>
                    <button
                        onClick={() => window.location.reload()}
                        className="btn-primary"
                    >
                        Neu laden
                    </button>
                </div>
            </div>
        );
    }
    
    return null;
}


// Erweiterte WorkshopCard Komponente
function WorkshopCard({ workshop, problem }) {
    const [analysis, setAnalysis] = useState(null);
    const [isAnalyzing, setIsAnalyzing] = useState(false);
    const [error, setError] = useState('');
    const [showDetails, setShowDetails] = useState(false);

    const handleAnalyzeReviews = async () => {
        if (!workshop.reviews || workshop.reviews.length === 0) {
            setError("Für diese Werkstatt liegen keine Rezensionen vor.");
            return;
        }

        setIsAnalyzing(true);
        setError('');
        setAnalysis(null);

        try {
            const reviewText = workshop.reviews.slice(0, 5).map(r => `"${r.text}"`).join('\n');
            const prompt = `Analysiere diese Google-Rezensionen für eine Autowerkstatt. Erstelle eine JSON-Antwort mit genau dieser Struktur: {"summary": "Kurze Zusammenfassung in 2-3 Sätzen", "pros": ["Maximal 3 positive Punkte"], "cons": ["Maximal 3 negative Punkte"], "priceIndication": "Günstig|Mittel|Teuer|Unklar"}. Rezensionen:\n${reviewText}`;

            const response = await fetch('/api/analyze', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt })
            });

            if (!response.ok) {
                throw new Error(`API Fehler (${response.status})`);
            }

            const parsedAnalysis = await response.json();

            if (!parsedAnalysis.summary) {
                parsedAnalysis.summary = "Analyse konnte nicht vollständig durchgeführt werden.";
            }
            if (!Array.isArray(parsedAnalysis.pros)) {
                parsedAnalysis.pros = [];
            }
            if (!Array.isArray(parsedAnalysis.cons)) {
                parsedAnalysis.cons = [];
            }

            setAnalysis(parsedAnalysis);
        } catch (err) {
            console.error('Analyse Error:', err);
            setError('Analyse fehlgeschlagen. Bitte versuchen Sie es später erneut.');
        } finally {
            setIsAnalyzing(false);
        }
    };

    const needsAlignment = problem && (
        problem.toLowerCase().includes('fahrwerk') ||
        problem.toLowerCase().includes('spurstange') ||
        problem.toLowerCase().includes('querlenker') ||
        problem.toLowerCase().includes('stoßdämpfer')
    );

    return (
        <div className="card-gradient p-5 rounded-xl workshop-card glow-effect-hover">
            <div className="flex gap-4">
                <img
                    src={workshop.photoUrl}
                    alt={workshop.name}
                    className="w-24 h-24 rounded-lg object-cover bg-gray-800"
                    onError={(e) => e.target.src='https://placehold.co/400x400/1e253a/4cc3ee?text=Carfify'}
                />
                <div className="flex-1">
                    <div className="flex items-start justify-between">
                        <h3 className="font-bold text-gray-100">{workshop.name}</h3>
                        <WorkshopTypeInfo type={workshop.workshopType} />
                    </div>
                    <p className="text-sm text-gray-400 mt-1">
                        <i className="fa-solid fa-location-dot mr-2 text-gray-500"></i>
                        {workshop.vicinity}
                    </p>
                    <div className="text-sm text-gray-300 mt-2 flex items-center gap-3">
                        <span className="flex items-center gap-1">
                            <span className="font-bold text-amber-400">{workshop.rating || 'N/A'}</span>
                            <i className="fa-solid fa-star text-amber-400"></i>
                            <span className="text-gray-400">({workshop.user_ratings_total || 0})</span>
                        </span>
                        <span className="text-xs text-gray-500">
                            <i className="fa-brands fa-google mr-1"></i>
                            Google verifiziert
                        </span>
                    </div>
                </div>
            </div>

            {needsAlignment && workshop.workshopType === 'independent' && (
                <div className="mt-3 p-3 bg-amber-900/20 border border-amber-500/50 rounded-lg">
                    <p className="text-xs text-amber-400">
                        <i className="fa-solid fa-info-circle mr-1"></i>
                        <strong>Hinweis:</strong> Nach dieser Reparatur ist eine Spureinstellung nötig (ca. 80-120€ extra).
                        Freie Werkstätten bieten dies nicht immer an.
                    </p>
                </div>
            )}

            <div className="mt-4 flex gap-2">
                {!showDetails ? (
                    <>
                        <button
                            onClick={() => setShowDetails(true)}
                            className="flex-1 btn-secondary icon-bounce"
                        >
                            <i className="fa-solid fa-info-circle mr-2"></i>Details
                        </button>
                        {isAnalyzing ? (
                            <div className="flex-1 btn-secondary opacity-50 cursor-not-allowed flex items-center justify-center">
                                <Spinner text="Analysiere..." />
                            </div>
                        ) : (
                            <button
                                onClick={handleAnalyzeReviews}
                                className="flex-1 btn-secondary icon-bounce"
                            >
                                <i className="fa-solid fa-wand-magic-sparkles mr-2"></i>KI-Analyse
                            </button>
                        )}
                    </>
                ) : (
                    <button
                        onClick={() => setShowDetails(false)}
                        className="w-full btn-secondary"
                    >
                        <i className="fa-solid fa-chevron-up mr-2"></i>Details ausblenden
                    </button>
                )}
            </div>

            {showDetails && (
                <div className="mt-4 pt-4 border-t border-gray-700 space-y-3 fade-in">
                    {workshop.phone && (
                        <a
                            href={`tel:${workshop.phone}`}
                            className="flex items-center gap-2 text-sm text-[#4cc3ee] hover:text-[#3da5cd] transition-colors"
                        >
                            <i className="fa-solid fa-phone"></i>
                            <span>{workshop.phone}</span>
                        </a>
                    )}

                    {workshop.website && (
                        <a
                            href={workshop.website}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-2 text-sm text-[#4cc3ee] hover:text-[#3da5cd] transition-colors"
                        >
                            <i className="fa-solid fa-globe"></i>
                            <span>Website besuchen</span>
                        </a>
                    )}

                    {workshop.opening_hours && (
                        <div className="text-sm">
                            <p className="font-semibold text-gray-300 mb-1">
                                <i className="fa-solid fa-clock mr-2 text-[#4cc3ee]"></i>Öffnungszeiten:
                            </p>
                            <div className="text-xs text-gray-400 space-y-0.5 ml-5">
                                {workshop.opening_hours.weekday_text?.map((day, i) => (
                                    <p key={i}>{day}</p>
                                ))}
                            </div>
                        </div>
                    )}

                    <a
                        href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(workshop.name)}&query_place_id=${workshop.place_id}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 text-sm bg-[#4cc3ee]/20 text-[#4cc3ee] px-3 py-1.5 rounded-md hover:bg-[#4cc3ee]/30 transition-colors"
                    >
                        <i className="fa-solid fa-map"></i>
                        In Google Maps öffnen
                    </a>
                </div>
            )}

            {error && <p className="text-xs text-red-400 mt-2">{error}</p>}

            {analysis && (
                <div className="mt-4 pt-4 border-t border-gray-700 fade-in">
                    <h4 className="font-semibold text-gray-200 mb-2 flex items-center">
                        <i className="fa-solid fa-robot mr-2 text-[#4cc3ee]"></i>KI-Zusammenfassung:
                    </h4>
                    {analysis.summary && (
                        <p className="text-sm text-gray-300 italic mb-3">"{analysis.summary}"</p>
                    )}

                    {analysis.priceIndication && analysis.priceIndication !== 'Unklar' && (
                        <div className="mb-3">
                            <span className="text-xs font-semibold text-gray-400">Preisniveau: </span>
                            <span className={`text-xs px-2 py-0.5 rounded-full ${
                                analysis.priceIndication === 'Günstig' ? 'bg-green-900/50 text-green-400 border border-green-700' :
                                analysis.priceIndication === 'Mittel' ? 'bg-yellow-900/50 text-yellow-400 border border-yellow-700' :
                                'bg-red-900/50 text-red-400 border border-red-700'
                            }`}>
                                {analysis.priceIndication}
                            </span>
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        {analysis.pros && analysis.pros.length > 0 && (
                            <div>
                                <h5 className="font-semibold text-green-400 flex items-center mb-1">
                                    <i className="fa-solid fa-circle-plus mr-2"></i>Pro
                                </h5>
                                <ul className="list-disc list-inside text-gray-300 space-y-0.5">
                                    {analysis.pros.map((pro, i) => <li key={i}>{pro}</li>)}
                                </ul>
                            </div>
                        )}
                        {analysis.cons && analysis.cons.length > 0 && (
                            <div>
                                <h5 className="font-semibold text-red-400 flex items-center mb-1">
                                    <i className="fa-solid fa-circle-minus mr-2"></i>Contra
                                </h5>
                                <ul className="list-disc list-inside text-gray-300 space-y-0.5">
                                    {analysis.cons.map((con, i) => <li key={i}>{con}</li>)}
                                </ul>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

// Komponente für die HSN/TSN Eingabe
function VehicleIdentifier({ hsn, setHsn, tsn, setTsn, onFind, isFinding }) {
    return (
        <div className="p-6 bg-gray-900/50 border border-gray-700 rounded-xl mb-6 backdrop-blur-sm">
            <h2 className="text-lg font-bold text-gray-100 mb-4">
                1. Fahrzeug identifizieren
                <span className="text-sm font-normal text-gray-400 ml-2">(Optional)</span>
            </h2>
            <div className="flex flex-col sm:flex-row items-start gap-4">
                <div className="w-full sm:w-auto flex-1">
                    <label htmlFor="hsn" className="block text-gray-300 text-sm font-semibold mb-2">HSN</label>
                    <input
                        type="text"
                        id="hsn"
                        maxLength="4"
                        className="w-full p-3"
                        placeholder="z.B. 0603"
                        value={hsn}
                        onChange={(e) => setHsn(e.target.value)}
                    />
                </div>
                <div className="w-full sm:w-auto flex-1">
                    <label htmlFor="tsn" className="block text-gray-300 text-sm font-semibold mb-2">TSN</label>
                    <input
                        type="text"
                        id="tsn"
                        maxLength="3"
                        className="w-full p-3"
                        placeholder="z.B. BJM"
                        value={tsn}
                        onChange={(e) => setTsn(e.target.value.toUpperCase())}
                    />
                </div>
                <div className="w-full sm:w-auto self-end">
                    <button
                        onClick={onFind}
                        disabled={isFinding}
                        className="w-full btn-primary disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        {isFinding ? <Spinner text="Suchen..." light={true}/> : <><i className="fa-solid fa-search"></i> <span>Finden</span></>}
                    </button>
                </div>
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
    const [aiAnalysis, setAiAnalysis] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const [workshops, setWorkshops] = useState([]);
    const [startInteractive, setStartInteractive] = useState(false);

    const handleFindVehicle = async () => {
        const vehicleDatabase = {
            "0603-BJM": { name: "VW Golf VIII 2.0 TDI", ps: "150 PS", year: "2019-heute", imageUrl: "https://placehold.co/600x400/1e253a/4cc3ee?text=VW+Golf+VIII" }
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

    const handleSubmit = async () => {
        if (!problemText.trim()) {
            setError('Bitte beschreiben Sie zuerst Ihr Problem.');
            return;
        }
        setIsLoading(true);
        setAiAnalysis(null);
        setWorkshops([]);
        setError('');
        setStartInteractive(false);

        navigator.geolocation.getCurrentPosition(async (position) => {
            const { latitude, longitude } = position.coords;
            try {
                const [aiResult, workshopsResult] = await Promise.all([
                    fetchAiAnalysis(),
                    fetchWorkshops(latitude, longitude)
                ]);
                setAiAnalysis(aiResult);
                setWorkshops(workshopsResult);
            } catch (err) {
                setError("Ein Fehler ist aufgetreten: " + err.message);
            } finally {
                setIsLoading(false);
            }
        }, (err) => {
            setError("Standort konnte nicht abgerufen werden. Bitte erteilen Sie die Erlaubnis.");
            setIsLoading(false);
        });
    };

    const fetchAiAnalysis = async () => {
        const vehicleInfo = foundVehicle ? `User's car model: "${foundVehicle.name}"` : "User's car model: Not specified.";
        const prompt = `Analysiere das folgende Autoproblem. Problem: "${problemText}", Fahrzeug: ${vehicleInfo}`;
        const response = await fetch('/api/analyze', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt })
        });
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Unbekannter Problem-Analyse-Fehler' }));
            throw new Error(errorData.message || `Problem-Analyse API Fehler (${response.status})`);
        }
        return await response.json();
    };

    const fetchWorkshops = async (latitude, longitude) => {
        const vehicleInfo = foundVehicle ? foundVehicle.name.split(' ')[0] : '';
        const response = await fetch(`/api/workshops?lat=${latitude}&lon=${longitude}&problem=${encodeURIComponent(problemText)}&vehicleBrand=${encodeURIComponent(vehicleInfo)}`);
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Unbekannter Werkstatt-Suche-Fehler' }));
            throw new Error(errorData.message || `Werkstatt-Suche API Fehler (${response.status})`);
        }
        const data = await response.json();
        return data.workshops || data;
    };

    return (
        <div className="min-h-screen p-4 md:p-8 flex justify-center items-start">
            <div className="card-gradient p-6 md:p-8 rounded-2xl shadow-2xl w-full max-w-4xl border border-gray-700">
                <header className="text-center mb-10">
                    <img src="/logo.png" alt="Carfify Logo" className="mx-auto h-24 w-auto drop-shadow-2xl" />
                    <p className="text-gray-400 text-sm mt-2">Dein intelligenter KFZ-Assistent</p>
                </header>

                {error && (
                    <div className="p-4 mb-6 bg-red-900/20 border-l-4 border-red-500 text-red-400 rounded-lg">
                        <p className="flex items-center">
                            <i className="fa-solid fa-exclamation-circle mr-2"></i>
                            {error}
                        </p>
                    </div>
                )}

                {!aiAnalysis && (
                    <div className="fade-in">
                        <VehicleIdentifier
                            hsn={hsn}
                            setHsn={setHsn}
                            tsn={tsn}
                            setTsn={setTsn}
                            onFind={handleFindVehicle}
                            isFinding={isFindingVehicle}
                        />

                        {foundVehicle && (
                            <div className="p-5 mb-6 bg-blue-900/20 border border-blue-500/50 rounded-xl fade-in">
                                <div className="flex flex-col sm:flex-row items-center gap-4">
                                    <img
                                        src={foundVehicle.imageUrl}
                                        onError={(e) => e.target.src='https://placehold.co/600x400/1e253a/4cc3ee?text=Bild+fehlt'}
                                        alt={foundVehicle.name}
                                        className="w-32 h-auto rounded-lg bg-gray-800 object-cover"
                                    />
                                    <div>
                                        <p className="font-bold text-lg text-gray-100">{foundVehicle.name}</p>
                                        <p className="text-sm text-gray-300">Leistung: {foundVehicle.ps}</p>
                                        <p className="text-sm text-gray-300">Bauzeitraum: {foundVehicle.year}</p>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="p-6 bg-gray-900/50 border border-gray-700 rounded-xl mb-6 backdrop-blur-sm">
                            <h2 className="text-lg font-bold text-gray-100 mb-4">2. Problem beschreiben</h2>
                            <textarea
                                id="problem"
                                className="w-full p-3 min-h-[120px]"
                                value={problemText}
                                onChange={(e) => setProblemText(e.target.value)}
                                placeholder="Beschreiben Sie das Problem so genau wie möglich..."
                                rows="4"
                            ></textarea>
                        </div>

                        <button
                            onClick={handleSubmit}
                            className="w-full btn-primary text-lg"
                            disabled={isLoading || !problemText}
                        >
                            {isLoading ? <Spinner text="Analysiere & Suche..." light={true} /> : <span><i className="fa-solid fa-search-dollar mr-2"></i>Analyse & Werkstätten finden</span>}
                        </button>
                    </div>
                )}

                {aiAnalysis && (
                    <div className="fade-in">
                        <button
                            onClick={() => {setAiAnalysis(null); setStartInteractive(false);}}
                            className="mb-6 text-sm text-[#4cc3ee] hover:text-[#3da5cd] transition-colors flex items-center"
                        >
                            <i className="fa-solid fa-arrow-left mr-2"></i>
                            Neue Diagnose starten
                        </button>

                        <VehicleIdentifier
                            hsn={hsn}
                            setHsn={setHsn}
                            tsn={tsn}
                            setTsn={setTsn}
                            onFind={handleFindVehicle}
                            isFinding={isFindingVehicle}
                        />

                        {!foundVehicle && (
                            <div className="p-4 mb-6 bg-yellow-900/20 border-l-4 border-yellow-500 text-yellow-400 rounded-lg">
                                <h3 className="font-bold flex items-center">
                                    <i className="fa-solid fa-triangle-exclamation mr-2"></i>
                                    Hinweis zur Genauigkeit
                                </h3>
                                <p className="text-sm mt-1">Für eine präzisere Analyse, identifizieren Sie bitte Ihr Fahrzeug.</p>
                            </div>
                        )}

                        <div className="p-6 bg-gray-900/50 border border-gray-700 rounded-xl backdrop-blur-sm">
                            <h2 className="text-xl font-bold text-gray-100 mb-6 flex items-center">
                                <i className="fa-solid fa-stethoscope mr-3 text-[#4cc3ee]"></i>
                                KI-Analyse & Kostenschätzung
                            </h2>
                            <div className="space-y-6">
                                {aiAnalysis.possibleCauses && (
                                    <div>
                                        <strong className="text-gray-300 block mb-2">
                                            {aiAnalysis.finalDiagnosis ? 'Diagnose-Ergebnis:' : 'Mögliche Ursachen:'}
                                        </strong>
                                        {aiAnalysis.finalDiagnosis ? (
                                            <div className="p-4 bg-green-900/20 border border-green-500/50 rounded-lg">
                                                <p className="text-green-400 font-medium">{aiAnalysis.finalDiagnosis}</p>
                                                <div className="mt-2 flex items-center gap-2">
                                                    <span className="text-sm text-gray-400">Sicherheit:</span>
                                                    <div className="flex-1 max-w-xs">
                                                        <div className="w-full bg-gray-800 rounded-full h-2">
                                                            <div
                                                                className="h-2 rounded-full bg-gradient-to-r from-green-500 to-green-400"
                                                                style={{width: `${aiAnalysis.diagnosisCertainty}%`}}
                                                            />
                                                        </div>
                                                    </div>
                                                    <span className="text-sm font-bold text-green-400">{aiAnalysis.diagnosisCertainty}%</span>
                                                </div>
                                            </div>
                                        ) : (
                                            <ul className="list-disc list-inside ml-4 space-y-2">
                                                {aiAnalysis.possibleCauses.map((cause, i) => (
                                                    <li key={i} className={i === 0 ? "font-semibold text-[#4cc3ee]" : "text-gray-400"}>
                                                        {cause}
                                                        {i === 0 && aiAnalysis.mostLikelyCause && (
                                                            <span className="text-sm text-[#3da5cd] ml-2">(Wahrscheinlichste Ursache)</span>
                                                        )}
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                )}

                                {aiAnalysis.mostLikelyCause && (
                                    <div className="mt-4 p-4 bg-blue-900/20 border border-[#4cc3ee]/50 rounded-lg">
                                        <strong className="text-[#4cc3ee]">Wahrscheinlichste Diagnose:</strong>
                                        <p className="text-gray-200 mt-1">{aiAnalysis.mostLikelyCause}</p>
                                    </div>
                                )}

                                {aiAnalysis.recommendation && (
                                    <div>
                                        <strong className="text-gray-300 block mb-1">Empfehlung:</strong>
                                        <span className="text-gray-200">{aiAnalysis.recommendation}</span>
                                    </div>
                                )}

                                {aiAnalysis.urgency && (
                                    <div className="flex items-center gap-3">
                                        <strong className="text-gray-300">Dringlichkeit:</strong>
                                        <span className={`font-bold px-4 py-2 rounded-full text-sm flex items-center gap-2 ${
                                            aiAnalysis.urgency === 'Kritisch' ? 'bg-purple-900/50 text-purple-400 border border-purple-500 pulse-animation' :
                                            aiAnalysis.urgency === 'Hoch' ? 'bg-red-900/50 text-red-400 border border-red-500' :
                                            aiAnalysis.urgency === 'Mittel' ? 'bg-yellow-900/50 text-yellow-400 border border-yellow-500' :
                                            'bg-green-900/50 text-green-400 border border-green-500'
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
                                            <span className="text-xs text-purple-400 italic">
                                                Sofortiges Handeln erforderlich!
                                            </span>
                                        )}
                                    </div>
                                )}

                                {aiAnalysis.likelyRequiredParts && aiAnalysis.likelyRequiredParts.length > 0 && (
                                    <div>
                                        <strong className="text-gray-300 block mb-2">Benötigte Teile (Vorschläge):</strong>
                                        <div className="flex flex-wrap gap-2">
                                            {aiAnalysis.likelyRequiredParts.map(part => (
                                                <a
                                                    key={part}
                                                    href={`https://www.autodoc.de/search?keyword=${encodeURIComponent(part)}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-sm bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-md transition-colors border border-gray-700 hover:border-[#4cc3ee]"
                                                >
                                                    {part}
                                                    <i className="fa-solid fa-arrow-up-right-from-square text-xs ml-1"></i>
                                                </a>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                <CostEstimateDisplay analysis={aiAnalysis} />

                                {aiAnalysis.diyTips && aiAnalysis.diyTips.length > 0 && (
                                    <div>
                                        <strong className="text-gray-300 block mb-2">Für Selbermacher:</strong>
                                        <ul className="list-disc list-inside ml-4 text-sm text-gray-400 space-y-1">
                                            {aiAnalysis.diyTips.map((tip, i) => <li key={i}>{tip}</li>)}
                                            {aiAnalysis.youtubeSearchQuery && (
                                                <li>
                                                    <a
                                                        href={`https://www.youtube.com/results?search_query=${encodeURIComponent(aiAnalysis.youtubeSearchQuery)}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-[#4cc3ee] hover:text-[#3da5cd] transition-colors"
                                                    >
                                                        Passende YouTube-Tutorials ansehen
                                                        <i className="fa-solid fa-arrow-up-right-from-square text-xs ml-1"></i>
                                                    </a>
                                                </li>
                                            )}
                                        </ul>
                                    </div>
                                )}

                                {aiAnalysis.selfCheckPossible && (
                                    <div className="mt-6 p-4 bg-blue-900/20 border border-[#4cc3ee]/50 rounded-lg">
                                        <h3 className="font-semibold text-[#4cc3ee] mb-3">
                                            <i className="fa-solid fa-wrench mr-2"></i>
                                            Selbstdiagnose möglich
                                        </h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <p className="text-sm text-gray-300 mb-2">Schwierigkeit:</p>
                                                <div className="flex items-center gap-1">
                                                    {['Einfach', 'Mittel', 'Schwer'].includes(aiAnalysis.selfCheckDifficulty) && (
                                                        <>
                                                            {[1,2,3,4,5].map(i => (
                                                                <i
                                                                    key={i}
                                                                    className={`fa-solid fa-star text-sm ${
                                                                        i <= (aiAnalysis.selfCheckDifficulty === 'Einfach' ? 2 :
                                                                            aiAnalysis.selfCheckDifficulty === 'Mittel' ? 3 : 5)
                                                                        ? 'text-yellow-400' : 'text-gray-600'
                                                                    }`}
                                                                ></i>
                                                            ))}
                                                            <span className="text-sm text-gray-400 ml-2">{aiAnalysis.selfCheckDifficulty}</span>
                                                        </>
                                                    )}
                                                </div>
                                            </div>
                                            <div>
                                                <p className="text-sm text-gray-300 mb-2">Benötigtes Werkzeug:</p>
                                                <p className="text-sm text-gray-400">Keine speziellen Werkzeuge nötig</p>
                                            </div>
                                        </div>
                                        {/* YouTube Tutorial Links für Top 3 Ursachen */}
                                        <div className="mt-4">
                                            <p className="text-sm text-gray-300 mb-2">Video-Anleitungen zur Selbstdiagnose:</p>
                                            <div className="flex flex-wrap gap-2">
                                                {aiAnalysis.possibleCauses.slice(0, 3).map((cause, i) => (
                                                    <a
                                                        key={i}
                                                        href={`https://www.youtube.com/results?search_query=${encodeURIComponent(
                                                            cause.replace(/[-:]/g, '') + ' Symptome prüfen'
                                                        )}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-md transition-colors border border-gray-700 hover:border-[#4cc3ee]"
                                                    >
                                                        <i className="fa-brands fa-youtube text-red-500 mr-1"></i>
                                                        {cause.split(':')[0].trim()} prüfen
                                                    </a>
                                                ))}
                                            </div>
                                        </div>
                                        {/* Hybrid-Option */}
                                        <div className="mt-4 p-3 bg-gray-800/50 rounded-lg">
                                            <p className="text-sm text-gray-300">
                                                <i className="fa-solid fa-lightbulb text-yellow-400 mr-2"></i>
                                                <strong>Tipp:</strong> Sie können das Problem selbst diagnostizieren, die Teile online bestellen und dann von einer Werkstatt einbauen lassen. Das spart oft 30-50% der Kosten!
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {!startInteractive && (
                                <div className="mt-8 pt-6 border-t border-gray-700">
                                    <p className="text-sm text-center text-gray-400 mb-4">
                                        Um das Problem weiter einzugrenzen, können wir eine interaktive Diagnose starten.
                                    </p>
                                    <button
                                        onClick={() => setStartInteractive(true)}
                                        className="w-full btn-primary"
                                    >
                                        <i className="fa-solid fa-comments mr-2"></i>
                                        Interaktive Diagnose starten
                                    </button>
                                </div>
                            )}

                            {startInteractive && (
                                <InteractiveDiagnosisV2
                                    initialProblem={problemText}
                                    vehicleInfo={foundVehicle ? foundVehicle.name : 'Nicht angegeben'}
                                    onDiagnosisComplete={(result) => {
                                        setAiAnalysis(prev => ({
                                            ...prev,
                                            possibleCauses: result.affectedParts || [result.diagnosis],
                                            mostLikelyCause: result.diagnosis,
                                            recommendation: "Basierend auf der interaktiven Diagnose wurde das Problem weiter eingegrenzt.",
                                            diagnosisCertainty: result.certainty || 85,
                                            // Neue Felder für bessere Darstellung
                                            finalDiagnosis: result.diagnosis,
                                            affectedParts: result.affectedParts
                                        }));
                                        setStartInteractive(false);
                                    }}
                                />
                            )}
                        </div>

                        {workshops.length > 0 && (
                            <div className="mt-8">
                                <h2 className="text-xl font-bold text-gray-100 mb-6 flex items-center">
                                    <i className="fa-solid fa-wrench mr-3 text-[#4cc3ee]"></i>
                                    Passende Werkstätten in deiner Nähe
                                </h2>

                                {!foundVehicle && (
                                    <div className="mb-6 p-4 bg-blue-900/20 border border-[#4cc3ee]/50 rounded-lg">
                                        <p className="text-sm text-[#4cc3ee]">
                                            <i className="fa-solid fa-info-circle mr-2"></i>
                                            <strong>Tipp:</strong> Identifizieren Sie Ihr Fahrzeug oben für markenspezifische Vertragswerkstätten.
                                        </p>
                                    </div>
                                )}

                                <div className="mb-6 p-5 bg-gray-900/50 border border-gray-700 rounded-xl backdrop-blur-sm">
                                    <h3 className="font-semibold text-gray-200 mb-3">Werkstatt-Kategorien:</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div className="flex items-start gap-3">
                                            <i className="fa-solid fa-award text-blue-400 mt-0.5"></i>
                                            <div>
                                                <strong className="text-gray-200">Vertragswerkstätten:</strong>
                                                <p className="text-xs text-gray-400 mt-1">Teurer, aber optimal für Garantie & Wiederverkaufswert bei neueren Fahrzeugen</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-3">
                                            <i className="fa-solid fa-link text-orange-400 mt-0.5"></i>
                                            <div>
                                                <strong className="text-gray-200">Werkstattketten (ATU, etc.):</strong>
                                                <p className="text-xs text-gray-400 mt-1">Mittlere Preise, standardisierte Qualität</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-3">
                                            <i className="fa-solid fa-wrench text-green-400 mt-0.5"></i>
                                            <div>
                                                <strong className="text-gray-200">Freie Werkstätten:</strong>
                                                <p className="text-xs text-gray-400 mt-1">Günstigste Preise, Qualität variiert je nach Betrieb</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-3">
                                            <i className="fa-solid fa-cogs text-purple-400 mt-0.5"></i>
                                            <div>
                                                <strong className="text-gray-200">Spezialisten:</strong>
                                                <p className="text-xs text-gray-400 mt-1">Für spezifische Probleme (Motor/Getriebe) oft die beste Wahl</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {workshops.map(shop => (
                                        <WorkshopCard
                                            key={shop.place_id}
                                            workshop={shop}
                                            problem={problemText}
                                        />
                                    ))}
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
