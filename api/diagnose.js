```javascript
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

        if (!Array.isArray(history) || history.length === 0) {
            return response.status(400).json({ message: 'Ein nicht-leeres "history" Array ist erforderlich.' });
        }

        const handlers = {
            analyze_categories:  handleCategoryAnalysis,
            category_questions:  handleCategoryQuestions,
            final_summary:       handleFinalSummary,
            freetext:            handleFreeTextQuestion,
            questions:           handleDiagnosticQuestions
        };

        const handlerFn = handlers[mode] || handlers.questions;
        return await handlerFn(history, { category, currentCertainty, anthropicApiKey, response });

    } catch (error) {
        console.error('Server-Fehler in /api/diagnose:', error);
        return response.status(500).json({
            message: 'Diagnose konnte nicht verarbeitet werden',
            details: error.message
        });
    }
}

async function handleCategoryAnalysis(history, { anthropicApiKey, response }) {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Experte. Im Folgenden beschreibt der Nutzer sein Problem.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"singleCategory":true,"categoryName":"KATEGORIE"} falls genau eine Kategorie passt,
{"singleCategory":false,"categories":[{"name":"KATEGORIE1","probability":INT},{"name":"KATEGORIE2","probability":INT}]} falls mehrere infrage kommen.
Erlaubte Kategorien: Fahrwerk, Rad/Reifen, Motor, Getriebe, Bremsen, Elektronik, Karosserie, Innenraum.`;

    const prompt = history[0].parts[0].text.trim();
    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, anthropicApiKey, 512);
    return response.status(200).json(claudeResponse);
}

async function handleCategoryQuestions(history, { category, currentCertainty, anthropicApiKey, response }) {
    const conversation = history.map(item => `${item.role}: ${item.parts[0].text}`).join('\n');
    const questionCount = (conversation.match(/model:/g) || []).length;

    const systemPrompt = `Du bist ein KFZ-Diagnose-Experte. Prüfe systematisch die Kategorie "${category}".
Stelle maximal 5 fahrzeugbezogene Fragen, die ohne Werkstatt beantwortbar sind (Geräusche, Fahrverhalten, Warnleuchten etc.).
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"categoryComplete":true,"certaintyIncrease":0–25,"summary":"Zusammenfassung"} wenn abgeschlossen,
{"nextQuestion":"Frage?","answers":["Ja","Nein","Weiß nicht"]} wenn mehr Infos nötig.`;

    const prompt = `Kontext:\n${conversation}\n\nAktuelle Sicherheit: ${currentCertainty}%\nKategorierelevante Fragen bisher: ${questionCount}`;
    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, anthropicApiKey);
    return response.status(200).json(claudeResponse);
}

async function handleFinalSummary(history, { anthropicApiKey, response }) {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Experte. Erstelle anhand des gesamten Gesprächsverlaufs eine präzise Diagnose.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"finalDiagnosis":"Klare Problembeschreibung","affectedParts":["Teil 1","Teil 2"],"confidence":"Textuelle Einschätzung","nextSteps":"Sinnvolle weiteren Schritte ohne Werkstattbesuch"}.`;

    const prompt = history.map(item => `${item.role}: ${item.parts[0].text}`).join('\n');
    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, anthropicApiKey, 1200);
    return response.status(200).json(claudeResponse);
}

async function handleFreeTextQuestion(history, { anthropicApiKey, response }) {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Assistent. Beantworte die gestellte Frage knapp, konkret und praxisorientiert.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"answer":"Deine prägnante Antwort","suggestedNextSteps":["Erster Vorschlag","Zweiter Vorschlag"]}.`;

    const lastIdx = history.length - 1;
    const context   = history.slice(0, lastIdx).map(item => `${item.role}: ${item.parts[0].text}`).join('\n');
    const question  = history[lastIdx].parts[0].text;

    const prompt = `Diskussionskontext:\n${context}\n\nNutzerfrage: ${question}`;
    const claudeResponse = await makeClaudeRequest(systemPrompt, prompt, anthropicApiKey);
    return response.status(200).json(claudeResponse);
}

async function handleDiagnosticQuestions(history, { anthropicApiKey, response }) {
    const conversation = history.map(item => `${item.role}: ${item.parts[0].text}`).join('\n');

    const systemPrompt = `Du bist ein fahrkundiger KFZ-Assistent. Stelle maximal 10 einfache Verständnisfragen um das Problem einzugrenzen.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"finalDiagnosis":"Präzise Problembeschreibung","certainty":0–100,"affectedParts":["Teil1","Teil2"]} oder
{"nextQuestion":"Fragetext?","answers":["Ja","Nein","Teilweise","Weiß nicht"]}.`;

    const claudeResponse = await makeClaudeRequest(systemPrompt, conversation, anthropicApiKey);
    return response.status(200).json(claudeResponse);
}

async function makeClaudeRequest(systemPrompt, userPrompt, apiKey, maxTokens = 1024) {
    const payload = {
        model: 'claude-3-5-sonnet-20241022',
        max_tokens: maxTokens,
        temperature: 0.2,
        system: systemPrompt,
        messages: [{ role: 'user', content: userPrompt }]
    };

    const resp = await fetch('https://api.anthropic.com/v1/messages', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'x-api-key': apiKey,
            'anthropic-version': '2023-06-01'
        },
        body: JSON.stringify(payload)
    });

    if (!resp.ok) {
        const err = await resp.json().catch(() => ({ error: { message: 'Netzwerkfehler' } }));
        console.error('Claude API Error:', err);
        throw new Error(err.error?.message || 'Claude API Fehler');
    }

    const { content } = await resp.json();
    const raw = content?.[0]?.text || '';

    try {
        const jsonMatch = raw.match(/\{[\s\S]*?\}/);
        if (!jsonMatch) throw new Error('Kein JSON im Response gefunden');
        return JSON.parse(jsonMatch[0]);
    } catch {
        throw new Error(`Antwort konnte nicht verarbeitet werden: ${raw}`);
    }
}
```