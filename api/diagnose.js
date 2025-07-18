export default async function handler(request, response) {
    if (request.method !== 'POST') {
        return response.status(405).json({ message: 'Method Not Allowed' });
    }

    const anthropicApiKey = process.env.ANTHROPIC_API_KEY;
    if (!anthropicApiKey) {
        return response.status(500).json({ message: 'Claude API-Schlüssel ist auf dem Server nicht gesetzt.' });
    }

    try {
        const { history, mode = 'questions', category, currentCertainty } = request.body;
        if (!history || !Array.isArray(history)) {
            return response.status(400).json({ message: 'Ein "history" Array ist erforderlich.' });
        }

        // Verschiedene Modi für die interaktive Diagnose
        switch (mode) {
            case 'analyze_categories':
                return await handleCategoryAnalysis(history, anthropicApiKey, response);
            case 'category_questions':
                return await handleCategoryQuestions(history, category, currentCertainty, anthropicApiKey, response);
            case 'final_summary':
                return await handleFinalSummary(history, anthropicApiKey, response);
            case 'freetext':
                return await handleFreeTextQuestion(history, anthropicApiKey, response);
            default: // Fallback für alte Logik, falls nötig
                return await handleDiagnosticQuestions(history, anthropicApiKey, response);
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
    const systemPrompt = `Du bist ein KFZ-Diagnose-Experte. Analysiere das Problem und entscheide:
    1. Ist das Problem eindeutig EINER Kategorie zuzuordnen? Dann antworte mit singleCategory: true.
    2. Oder müssen verschiedene Kategorien geprüft werden? Dann liste die relevanten auf.
       Kategorien: Fahrwerk, Rad/Reifen, Motor, Getriebe, Bremsen, Elektronik, Karosserie, Innenraum.
       WICHTIG: Erstelle NUR Kategorien die wirklich zum Problem passen. Keine künstlichen Kategorien!`;

    const prompt = `${history[0].parts[0].text}
        Antworte NUR mit einem JSON-Objekt:
    Entweder: {"singleCategory": true, "categoryName": "Name der Kategorie"}
    Oder: {"singleCategory": false, "categories": [{"name": "Kategorie 1", "probability": 85}, {"name": "Kategorie 2", "probability": 15}]}`;

    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, apiKey);
    return response.status(200).json(claudeResponse);
}

async function handleCategoryQuestions(history, category, currentCertainty, apiKey, response) {
    const conversationHistory = history.map(item => `${item.role}: ${item.parts[0].text}`).join('\n');
    const questionCount = (conversationHistory.match(/model:/g) || []).length;

    const systemPrompt = `Du bist ein KFZ-Diagnose-Experte. Du prüfst systematisch die Kategorie "${category}".
    Stelle einfache, klare Fragen die OHNE Werkstatt beantwortet werden können.
    Nach max. 5 Fragen oder wenn du sicher bist, beende die Kategorie-Prüfung.`;

    const prompt = `Bisheriger Dialog:
    ${conversationHistory}
    Aktuelle Diagnose-Sicherheit: ${currentCertainty}%
    Fragen in dieser Kategorie bisher: ${questionCount}

    Entscheide:
    1. Wenn genug Infos für diese Kategorie: {"categoryComplete": true, "certaintyIncrease": ZAHL, "summary": "Was wir herausgefunden haben"}
    2. Wenn mehr Infos nötig: {"nextQuestion": "Deine nächste Frage hier?", "answers": ["Ja", "Nein", "Weiß nicht"]}`;

    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, apiKey);
    return response.status(200).json(claudeResponse);
}

async function handleFinalSummary(history, apiKey, response) {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Experte. Erstelle eine finale, präzise Diagnose.`;
    const prompt = `Basierend auf allen geprüften Kategorien und Antworten, erstelle die finale Diagnose:
    ${history[0].parts[0].text}
    Antworte mit JSON:
    {
        "finalDiagnosis": "Präzise Beschreibung des Problems",
        "affectedParts": ["Betroffenes Teil 1", "Betroffenes Teil 2"],
        "confidence": "Wie sicher bist du bei dieser Diagnose (Text)",
        "nextSteps": "Was sollte als nächstes getan werden"
    }`;

    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, apiKey);
    return response.status(200).json(claudeResponse);
}

async function handleFreeTextQuestion(history, apiKey, response) {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Assistent. Beantworte die Frage des Nutzers hilfreich und präzise.`;

    const lastUserMessage = history[history.length - 1].parts[0].text;
    const contextHistory = history.slice(0, -1).map(item => `${item.role}: ${item.parts[0].text}`).join('\n');

    const prompt = `Kontext: ${contextHistory}
    Nutzerfrage: ${lastUserMessage}
    Antworte als JSON:
    {
        "answer": "Deine hilfreiche Antwort",
        "suggestedNextSteps": ["Vorschlag 1", "Vorschlag 2"]
    }`;

    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, apiKey);
    return response.status(200).json(claudeResponse);
}

async function handleDiagnosticQuestions(history, apiKey, response) { // Legacy Function
    const conversationHistory = history.map(item => `${item.role}: ${item.parts[0].text}`).join('\n');
    const systemPrompt = `Du bist ein KFZ-Diagnose-Assistent. Stelle einfache, verständliche Fragen zur Problemeingrenzung.`;
    const prompt = `GESPRÄCHSVERLAUF:
    ${conversationHistory}
    Entscheide den nächsten Schritt:
    1. Wenn du GENUG Informationen hast: {"finalDiagnosis": "Präzise Diagnose", "certainty": ZAHL, "affectedParts": ["Teil1", "Teil2"]}
    2. Wenn du MEHR Infos brauchst: {"nextQuestion": "Deine Frage?", "answers": ["Ja", "Nein", "Weiß nicht"]}`;

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

        const jsonMatch = textContent.match(/\{[\s\S]*\}/);
        if (jsonMatch) {
            return JSON.parse(jsonMatch[0]);
        } else {
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
