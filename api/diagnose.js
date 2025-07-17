export default async function handler(request, response) {
    if (request.method !== 'POST') {
        return response.status(405).json({ message: 'Method Not Allowed' });
    }
    
    const anthropicApiKey = process.env.ANTHROPIC_API_KEY;
    if (!anthropicApiKey) {
        return response.status(500).json({ message: 'Claude API-Schlüssel ist auf dem Server nicht gesetzt.' });
    }
    
    try {
        const { history, mode = 'questions' } = request.body;
        if (!history || !Array.isArray(history)) {
            return response.status(400).json({ message: 'Ein "history" Array ist erforderlich.' });
        }
        
        // Verschiedene Modi für die interaktive Diagnose
        if (mode === 'categories') {
            return await handleCategoryAnalysis(history, anthropicApiKey, response);
        } else if (mode === 'questions') {
            return await handleDiagnosticQuestions(history, anthropicApiKey, response);
        } else if (mode === 'freetext') {
            return await handleFreeTextQuestion(history, anthropicApiKey, response);
        }
        
    } catch (error) {
        console.error("Server-Fehler in /api/diagnose:", error);
        response.status(500).json({ 
            message: 'Diagnose konnte nicht verarbeitet werden', 
            details: error.message 
        });
    }
}

async function handleCategoryAnalysis(history, apiKey, response) {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Assistent. Analysiere das Problem und identifiziere betroffene Kategorien.
    Kategorien: Fahrwerk, Rad/Reifen, Motor, Getriebe, Bremsen, Elektronik, Karosserie, Innenraum`;
    
    const prompt = `Basierend auf dieser Problembeschreibung, identifiziere die wahrscheinlich betroffenen Kategorien:
    ${history[0].parts[0].text}
    
    Antworte NUR mit einem JSON-Objekt:
    {
        "categories": [
            {
                "name": "Kategoriename",
                "probability": ZAHL_0_BIS_100,
                "status": "critical|warning|ok",
                "questions": ["Frage 1", "Frage 2", "Frage 3"]
            }
        ],
        "mostLikelyCategory": "Kategoriename"
    }`;
    
    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, apiKey);
    return response.status(200).json(claudeResponse);
}

async function handleDiagnosticQuestions(history, apiKey, response) {
    const conversationHistory = history.map(item => {
        const role = item.role === 'user' ? 'Nutzer' : 'Assistent';
        const text = item.parts[0].text;
        return `${role}: ${text}`;
    }).join('\n');
    
    const systemPrompt = `Du bist ein KFZ-Diagnose-Assistent. Stelle einfache, verständliche Fragen zur Problemeingrenzung.
    Regeln:
    - Maximal 5 Fragen pro Kategorie
    - Fragen müssen ohne Werkstatt/Fachkenntnisse beantwortbar sein
    - Biete immer "Weiß nicht" als Option an
    - Wenn genug Informationen vorhanden sind, gib eine finale Diagnose`;
    
    const prompt = `GESPRÄCHSVERLAUF:
    ${conversationHistory}
    
    Entscheide den nächsten Schritt:
    1. Wenn du GENUG Informationen hast: {"finalDiagnosis": "Präzise Diagnose", "certainty": ZAHL_0_BIS_100, "affectedParts": ["Teil1", "Teil2"]}
    2. Wenn du MEHR Infos brauchst: {"nextQuestion": "Deine Frage?", "answers": ["Ja", "Nein", "Weiß nicht"], "category": "Kategoriename", "questionNumber": X, "totalQuestions": Y}
    
    Antworte NUR mit dem JSON-Objekt.`;
    
    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, apiKey);
    return response.status(200).json(claudeResponse);
}

async function handleFreeTextQuestion(history, apiKey, response) {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Assistent. Beantworte die Frage des Nutzers präzise und hilfreich.`;
    
    const lastUserMessage = history[history.length - 1].parts[0].text;
    const contextHistory = history.slice(0, -1).map(item => `${item.role}: ${item.parts[0].text}`).join('\n');
    
    const prompt = `Kontext:
    ${contextHistory}
    
    Nutzerfrage: ${lastUserMessage}
    
    Antworte hilfreich und präzise. Gib deine Antwort als JSON:
    {
        "answer": "Deine Antwort",
        "suggestedNextSteps": ["Vorschlag 1", "Vorschlag 2"]
    }`;
    
    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, apiKey);
    return response.status(200).json(claudeResponse);
}

async function makeClaudeRequest(systemPrompt, userPrompt, apiKey) {
    try {
        const claudeResponse = await fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'x-api-key': apiKey,
                'anthropic-version': '2023-06-01'
            },
            body: JSON.stringify({
                model: 'claude-3-5-sonnet-20241022',
                max_tokens: 1024,
                temperature: 0.3,
                system: systemPrompt,
                messages: [{
                    role: 'user',
                    content: userPrompt
                }]
            })
        });
        
        if (!claudeResponse.ok) {
            const errorData = await claudeResponse.json();
            console.error("Claude API Error:", errorData);
            throw new Error(errorData.error?.message || 'Claude API Fehler');
        }
        
        const result = await claudeResponse.json();
        const textContent = result.content[0].text;
        
        // JSON extrahieren
        const jsonMatch = textContent.match(/\{[\s\S]*\}/);
        if (jsonMatch) {
            return JSON.parse(jsonMatch[0]);
        } else {
            // Fallback
            return {
                error: "Keine strukturierte Antwort erhalten",
                rawResponse: textContent
            };
        }
        
    } catch (error) {
        console.error("Claude Request Error:", error);
        throw error;
    }
}
