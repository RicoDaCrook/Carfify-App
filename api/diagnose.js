export default async function handler(request, response) {
    if (request.method !== 'POST') {
        return response.status(405).json({ message: 'Method Not Allowed' });
    }
    
    const geminiApiKey = process.env.GEMINI_API_KEY;
    if (!geminiApiKey) {
        return response.status(500).json({ message: 'Gemini API-Schlüssel ist auf dem Server nicht gesetzt.' });
    }
    
    try {
        const { history } = request.body;
        if (!history || !Array.isArray(history)) {
            return response.status(400).json({ message: 'Ein "history" Array ist erforderlich.' });
        }
        
        // Verbesserte Prompt-Struktur
        const conversationHistory = history.map(item => {
            const role = item.role === 'user' ? 'Nutzer' : 'Assistent';
            const text = item.parts[0].text;
            return `${role}: ${text}`;
        }).join('\n');
        
        const prompt = `
Du bist ein KFZ-Diagnose-Assistent. Analysiere den folgenden Gesprächsverlauf und entscheide den nächsten Schritt.

GESPRÄCHSVERLAUF:
${conversationHistory}

DEINE AUFGABE:
1. Wenn du GENUG Informationen für eine präzise Diagnose hast, gib eine finale Diagnose.
2. Wenn du MEHR Informationen brauchst, stelle EINE gezielte Ja/Nein-Frage oder Multiple-Choice-Frage.

ANTWORTFORMAT:
- Für finale Diagnose: {"finalDiagnosis": "Präzise Diagnose mit konkreter Ursache"}
- Für weitere Fragen: {"nextQuestion": "Deine Frage?", "answers": ["Ja", "Nein", "Manchmal"]}

Antworte NUR mit dem JSON-Objekt, ohne zusätzlichen Text.`;

        const payload = { 
            contents: [{ 
                role: "user", 
                parts: [{ text: prompt }] 
            }],
            generationConfig: {
                temperature: 0.5,
                topK: 20,
                topP: 0.8,
            }
        };
        
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${geminiApiKey}`;
        
        const geminiRes = await fetch(apiUrl, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(payload) 
        });
        
        if (!geminiRes.ok) {
            const errorBody = await geminiRes.text();
            console.error("Gemini API Error:", errorBody);
            return response.status(geminiRes.status).json({ 
                message: `Gemini API Fehler`, 
                details: errorBody 
            });
        }
        
        const result = await geminiRes.json();
        
        if (result.candidates && result.candidates[0]?.content?.parts[0]?.text) {
            const textResponse = result.candidates[0].content.parts[0].text;
            console.log("Gemini Response:", textResponse); // Debug-Log
            
            // Robustere JSON-Extraktion
            let jsonMatch = textResponse.match(/\{[^{}]*\}/);
            if (!jsonMatch) {
                // Fallback für verschachtelte JSONs
                jsonMatch = textResponse.match(/\{[\s\S]*\}/);
            }
            
            if (jsonMatch) {
                try {
                    const parsed = JSON.parse(jsonMatch[0]);
                    
                    // Validierung der Antwort
                    if (parsed.finalDiagnosis || (parsed.nextQuestion && parsed.answers)) {
                        return response.status(200).json(parsed);
                    } else {
                        // Fallback-Antwort wenn KI falsch antwortet
                        return response.status(200).json({
                            nextQuestion: "Tritt das Problem nur bei bestimmten Geschwindigkeiten auf?",
                            answers: ["Ja, nur bei niedrigen Geschwindigkeiten", "Ja, nur bei hohen Geschwindigkeiten", "Nein, immer"]
                        });
                    }
                } catch (parseError) {
                    console.error("JSON Parse Error:", parseError, "Original:", jsonMatch[0]);
                    // Fallback-Antwort bei Parse-Fehler
                    return response.status(200).json({
                        nextQuestion: "Können Sie das Geräusch genauer beschreiben?",
                        answers: ["Quietschen", "Klopfen", "Schleifen", "Anderes Geräusch"]
                    });
                }
            } else {
                console.error("Kein JSON gefunden in:", textResponse);
                // Fallback-Antwort
                return response.status(200).json({
                    nextQuestion: "Wann tritt das Problem auf?",
                    answers: ["Beim Bremsen", "Beim Beschleunigen", "Im Leerlauf", "Immer"]
                });
            }
        } else {
            console.error("Unerwartetes Format:", result);
            return response.status(500).json({ 
                message: 'Unerwartetes Antwortformat von Gemini' 
            });
        }
    } catch (error) {
        console.error("Server-Fehler in /api/diagnose:", error);
        response.status(500).json({ 
            message: 'Diagnose konnte nicht verarbeitet werden', 
            details: error.message 
        });
    }
}
