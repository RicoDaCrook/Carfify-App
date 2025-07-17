export default async function handler(request, response) {
    if (request.method !== 'POST') { 
        return response.status(405).json({ message: 'Method Not Allowed' }); 
    }
    
    const anthropicApiKey = process.env.ANTHROPIC_API_KEY;
    const geminiApiKey = process.env.GEMINI_API_KEY;
    
    if (!anthropicApiKey || !geminiApiKey) { 
        return response.status(500).json({ message: 'API-Schlüssel fehlen auf dem Server.' }); 
    }
    
    try {
        const { prompt, useGemini = false } = request.body;
        if (!prompt) { 
            return response.status(400).json({ message: 'Ein "prompt" ist erforderlich.' }); 
        }
        
        // Verwende Gemini für Werkstatt-Reviews
        if (useGemini || prompt.includes('Rezensionen')) {
            return await handleGeminiRequest(prompt, geminiApiKey, response);
        }
        
        // Verwende Claude für alles andere (Diagnosen)
        return await handleClaudeRequest(prompt, anthropicApiKey, response);
        
    } catch (error) {
        console.error("Server-Fehler in /api/analyze:", error);
        response.status(500).json({ 
            message: 'Analyse konnte nicht verarbeitet werden', 
            details: error.message 
        });
    }
}

async function handleClaudeRequest(prompt, apiKey, response) {
    // Verbesserter Prompt für Claude
    const systemPrompt = `Du bist ein erfahrener KFZ-Meister mit 20 Jahren Berufserfahrung. 
    Du gibst präzise, strukturierte Diagnosen und achtest auf hohe Genauigkeit.
    Antworte IMMER im JSON-Format.`;
    
    const improvedPrompt = `
${prompt}

WICHTIG: Deine Antwort MUSS ein valides JSON-Objekt sein mit exakt dieser Struktur:
{
    "possibleCauses": ["Liste aller möglichen Ursachen, sortiert nach Wahrscheinlichkeit"],
    "mostLikelyCause": "Die wahrscheinlichste Ursache (kurz und prägnant)",
    "diagnosisCertainty": ZAHL_0_BIS_100,
    "affectedCategories": ["Fahrwerk", "Motor", "Getriebe", etc.],
    "costUncertaintyReason": "Erklärung warum die Kosten variieren können",
    "recommendation": "Deine Empfehlung",
    "urgency": "Niedrig|Mittel|Hoch|Kritisch",
    "estimatedLabor": ARBEITSZEIT_IN_STUNDEN,
    "minCost": MINIMALE_GESAMTKOSTEN,
    "maxCost": MAXIMALE_GESAMTKOSTEN,
    "likelyRequiredParts": ["Mögliche benötigte Teile"],
    "diagnosticStepsNeeded": ["Welche Tests/Diagnosen sind nötig"],
    "selfCheckPossible": true/false,
    "selfCheckDifficulty": "Einfach|Mittel|Schwer",
    "diyTips": ["Tipps für Selbermacher"],
    "youtubeSearchQuery": "Suchbegriff für YouTube"
}`;
    
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
                max_tokens: 2048,
                temperature: 0.3, // Niedrig für konsistente Antworten
                system: systemPrompt,
                messages: [{
                    role: 'user',
                    content: improvedPrompt
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
        
        // JSON aus der Antwort extrahieren
        const jsonMatch = textContent.match(/\{[\s\S]*\}/);
        if (jsonMatch) {
            const parsedJson = JSON.parse(jsonMatch[0]);
            
            // Validierung und Standardwerte
            parsedJson.diagnosisCertainty = parsedJson.diagnosisCertainty || 50;
            parsedJson.minCost = parsedJson.minCost || 100;
            parsedJson.maxCost = parsedJson.maxCost || 1000;
            parsedJson.affectedCategories = parsedJson.affectedCategories || [];
            parsedJson.selfCheckPossible = parsedJson.selfCheckPossible !== false;
            parsedJson.selfCheckDifficulty = parsedJson.selfCheckDifficulty || "Mittel";
            
            return response.status(200).json(parsedJson);
        } else {
            throw new Error('Kein gültiges JSON in der Claude-Antwort gefunden');
        }
        
    } catch (error) {
        console.error("Claude Request Error:", error);
        throw error;
    }
}

async function handleGeminiRequest(prompt, apiKey, response) {
    // Bestehende Gemini-Logik für Werkstatt-Reviews
    const payload = { 
        contents: [{ 
            role: "user", 
            parts: [{ text: prompt }] 
        }],
        generationConfig: {
            temperature: 0.7,
            topK: 40,
            topP: 0.95,
        }
    };
    
    const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;
    
    const geminiRes = await fetch(apiUrl, { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(payload) 
    });
    
    if (!geminiRes.ok) { 
        const errorBody = await geminiRes.text(); 
        console.error("Gemini API Error:", errorBody);
        throw new Error('Gemini API Fehler');
    }
    
    const result = await geminiRes.json();
    
    if (result.candidates && result.candidates[0]?.content?.parts[0]?.text) {
        const textResponse = result.candidates[0].content.parts[0].text;
        const jsonMatch = textResponse.match(/\{[\s\S]*\}/);
        
        if (jsonMatch) {
            return response.status(200).json(JSON.parse(jsonMatch[0]));
        }
    }
    
    throw new Error('Keine gültige Antwort von Gemini erhalten');
}
