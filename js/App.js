```javascript
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

// Custom Hook für VehicleIdentifier Component
function useVehicleIdentifier() {
    const [isFinding, setIsFinding] = useState(false);
    const [error, setError] = useState('');
    
    return { isFinding, error };
}

// WorkshopTypeInfo Component
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

// CostEstimateDisplay Component
const CostEstimateDisplay = ({ analysis }) => {
    const minCost = analysis.minCost || 100;
    const maxCost = analysis.maxCost || 1000;
    const certainty = analysis.diagnosisCertainty || 50;
    const costRange = maxCost - minCost;
    const isHighUncertainty = costRange > 2000 || certainty < 40;

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
                                    className={`h-full rounded-full bg-gradient-to-r from-green-500 to-green-400`}
                                    style={{width: `${certainty}%`}}
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
                            className={`h-3 rounded-full transition-all duration-500`}
                            style={{width: `${certainty}%`}}
                        />
                    </div>
                </div>

                {/* Preisschätzung nach Werkstatt-Typ */}
                {(minCost && maxCost) && (
                    <div className="mt-6 p-4 bg-gray-900/50 rounded-lg">
                        <p className="text-sm font-semibold text-gray-300 mb-3">
                            <i className="fa-solid fa-coins mr-1 text-[#4cc3ee]"></i>
                            Preisschätzung nach Werkstatt-Typ:
                        </p>
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between items-center p-2 rounded bg-gray-800/50">
                                <span className="text-gray-400 flex items-center">
                                    <i className="fa-solid fa-award mr-2 text-blue-400"></i>Vertragswerkstatt:
                                </span>
                                <span className="font-semibold text-blue-400">{Math.round(maxCost * 0.9)}€ - {maxCost}€</span>
                            </div>
                            <div className="flex justify-between items-center p-2 rounded bg-gray-800/50">
                                <span className="text-gray-400 flex items-center">
                                    <i className="fa-solid fa-link mr-2 text-orange-400"></i>Werkstattkette:
                                </span>
                                <span className="font-semibold text-orange-400">
                                    {Math.round(minCost + (maxCost - minCost) * 0.4)}€ -
                                    {Math.round(minCost + (maxCost - minCost) * 0.7)}€
                                </span>
                            </div>
                            <div className="flex justify-between items-center p-2 rounded bg-gray-800/50">
                                <span className="text-gray-400 flex items-center">
                                    <i className="fa-solid fa-wrench mr-2 text-green-400"></i>Freie Werkstatt:
                                </span>
                                <span className="font-semibold text-green-400">{minCost}€ - {Math.round(minCost + (maxCost - minCost) * 0.5)}€</span>
                            </div>
                        </div>
                    </div>
                )}

                {/* Diagnose-Sicherheit und Empfehlungen */}
                {analysis.diagnosticStepsNeeded && analysis.diagnosticStepsNeeded.length > 0 && (
                    <div className="mt-6 p-4 bg-gray-900/50 rounded-lg">
                        <p className="text-sm font-semibold text-gray-300 mb-3">
                            <i className="fa-solid fa-stethoscope mr-2"></i>
                            Notwendige Diagnose-Schritte:
                        </p>
                        <ul className="list-disc list-inside ml-4 text-sm space-y-2">
                            {analysis.diagnosticStepsNeeded.map((step, i) => (
                                <li key={i} className="text-gray-400">{step}</li>
                            ))}
                        </ul>
                    </div>
                )}

                {isHighUncertainty && (
                    <div className="mt-4 p-3 bg-red-900/20 border border-red-500/50 rounded-lg">
                        <p className="text-xs text-red-400">
                            <i className="fa-solid fa-info-circle mr-1"></i>
                            {analysis.costUncertaintyReason}
                        </p>
                        <div className="mt-3">
                            <button className="text-xs bg-red-600 hover:bg-red-700 text-white py-2 px-3 rounded-md transition-colors">
                                <i className="fa-solid fa-microscope mr-1"></i>
                                Interaktive Diagnose starten
                                    </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

// WorkshopCard Component
const WorkshopCard = ({ workshop, problem }) => {
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
                const errorData = await response.json().catch(() => ({ message: 'Unbekannter Rezensions-Analyse-Fehler' }));
                throw new Error(errorData.message || `API Fehler (${response.status})`);
            }

            const parsedAnalysis = await response.json();
            setAnalysis(parsedAnalysis);
        } catch (err) {
            setError('Analyse fehlgeschlagen. Versuchen Sie es später erneut.');
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
                        <h3 className="font-bold text-lg text-gray-100">{workshop.name}</h3>
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
                        <i className="fa-solid fa-robot mr-2 text-[#4cc3ee]"></i>
                        KI-Analyse der Rezensionen:
                    </h4>
                    {analysis.summary && (
                        <p className="text-sm text-gray-200 italic mb-3">"{analysis.summary}"</p>
                    )}

                    {analysis.priceIndication && analysis.priceIndication !== 'Unklar' && (
                        <div className="mb-3">
                            <span className="text-xs font-semibold text-gray-400">Preisniveau: </span>
                            <span className={`text-xs px-2 py-0.5 rounded-full ${
                                analysis.priceIndication === 'Günstig' ? 'bg-green-900/50 text-green-400' :
                                analysis.priceIndication === 'Mittel' ? 'bg-yellow-900/50 text-yellow-400' :
                                'bg-red-900/50 text-red-400'
                            }`}>
                                {analysis.priceIndication}
                            </span>
                        </div>
                    )}

                    <CostEstimateDisplay analysis={analysis} />

                    {/* YouTube Tutorial Links für Top 3 Ursachen */}
                    <div className="mt-4">
                        <h5 className="font-semibold text-gray-200 mb-2">Kosten für verschiedene Werkstatt-Typen:</h5>
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between items-center p-2 rounded bg-gray-800/50">
                                <span className="text-gray-400">Vertragswerkstatt:</span>
                                <span className="font-semibold text-blue-400">{Math.round(analysis.maxCost * 0.9)}€ - {analysis.maxCost}€</span>
                            </div>
                            {/* Additional workshop type comparisons... */}
                        </div>
                    </div>

                    {/* YouTube Tutorial Links */}
                    <div className="mt-4">
                        <h5 className="font-semibold text-gray-200 mb-2">DIY Video-Tutorials:</h5>
                        <div className="flex flex-wrap gap-2">
                            {analysis.possibleCauses?.slice(0, 3).map((cause, i) => (
                                <a
                                    key={i}
                                    href={`https://www.youtube.com/results?search_query=${encodeURIComponent(
                                        cause.replace(/[-:]/g, '') + ' Selbstdiagnose Anleitung'
                                    )}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-md border border-gray-700"
                                >
                                    {cause.split(':')[0]} Tutorial
                                    <i className="fa-solid fa-external-link-alt text-xs ml-1"></i>
                                </a>
                            ))}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

// VehicleIdentifier Component
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

// InteractiveDiagnosisV2 Component
function InteractiveDiagnosisV2({ initialProblem, vehicleInfo, onDiagnosisComplete }) {
    const [stage, setStage] = useState('categories');
    const [categories, setCategories] = useState([]);
    const [currentCategory, setCurrentCategory] = useState(null);
    const [currentQuestion, setCurrentQuestion] = useState(null);
    const [currentAnswers, setCurrentAnswers] = useState([]);
    const [questionProgress, setQuestionProgress] = useState({});
    const [history, setHistory] = useState([{
        role: "user",
        parts: [{ text: `Problem: ${initialProblem}. Fahrzeug: ${vehicleInfo}` }]
    }]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const [showFreeText, setShowFreeText] = useState(false);
    const [freeTextInput, setFreeTextInput] = useState('');
    const [selfCheckGuide, setSelfCheckGuide] = useState(null);
    const [conversationHistory, setConversationHistory] = useState([]);
    const [tentativeDiagnosis, setTentativeDiagnosis] = useState(null);
    const [diagnosisCertainty, setDiagnosisCertainty] = useState(0);
    const [affectedParts, setAffectedParts] = useState([]);
    const [recommendedActions, setRecommendedActions] = useState([]);
    const [finalCalculation, setFinalCalculation] = useState(null);

    const displayedQuestion = useTypewriter(currentQuestion);

    // Initial: Dialog-Kategorien laden
    useEffect(() => {
        if (stage === 'categories') {
            const initialCategories = [
                { name: "Motor & Antrieb", status: 'pending', probability: 85 },
                { name: "Elektrik & Elektronik", status: 'pending', probability: 45 },
                { name: "Fahrwerk & Bremsen", status: 'pending', probability: 60 },
                { name: "Kühlung & Klima", status: 'pending', probability: 35 }
            ];
            setCategories(initialCategories);
            setStage('overview');
        }
    }, [stage]);

    const startCategoryDiagnosis = (category) => {
        setCurrentCategory(category);
        setStage('questions');
        setQuestionProgress({ [category.name]: 0 });
        setCurrentQuestion(`Bei "${category.name}": $initialProblem}. Beschreiben Sie das Problem genauer.`);
        setCurrentAnswers([]);
    };

    const fetchNextQuestion = async (category, updatedHistory) => {
        setIsLoading(true);
        try {
            const response = await fetch('/api/interact', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    history: updatedHistory,
                    mode: 'interactive'
                })
            });
            const data = await response.json();
            if (data.finalDiagnosis) {
                onDiagnosisComplete(data);
            } else if (data.nextQuestion) {
                setCurrentQuestion(data.nextQuestion);
                setCurrentAnswers(data.answers);
                setTentativeDiagnosis(data.tentativeDiagnosis);
                setDiagnosisCertainty(data.diagnosisCertainty || 75);
                setAffectedParts(data.affectedParts || []);
                setRecommendedActions(data.recommendedActions || []);
                setFinalCalculation(data.finalCalculation);
            }
        } catch (err) {
            setError('Fehler bei der interaktiven Diagnose: ' + err.message);
        } finally {
            setIsLoading(false);
        }
    };

    // Weitere Methoden und Event-Handler...

    return null; // Das wird später ausgebaut
}

// App Component
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

    const handleAnalyzeAndFindWorkshops = async () => {
        if (!problemText.trim()) {
            setError('Bitte beschreiben Sie zuerst Ihr Problem.');
            return;
        }

        setIsLoading(true);
        const vehicleInfo = foundVehicle ? foundVehicle.name : 'Nicht angegeben';
        const prompt = `Problem: ${problemText}, Fahrzeug: ${vehicleInfo}`;

        try {
            const [analysisResult, workshopsResult] = await Promise.all([
                fetch('/api/analyze', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ prompt }) }).then(r => r.json()),
                navigator.geolocation.getCurrentPosition().then(position => {
                    const { latitude, longitude } = position.coords;
                    return fetch(`/api/workshops?lat=${latitude}&lon=${longitude}&problem=${encodeURIComponent(problemText)}`).then(r => r.json());
                })
            ]);

            setAiAnalysis(analysisResult);
            setWorkshops(workshopsResult.workshops || workshopsResult);
        } catch (err) {
            setError("Fehler aufgetreten: " + err.message);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-900 text-gray-100">
            <div className="container mx-auto px-4 py-8">
                <App />
            </div>
        </div>
    );
}

ReactDOM.render(<App />, document.getElementById('root'));
```