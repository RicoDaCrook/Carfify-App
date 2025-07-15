export default async function handler(request, response) {
    if (request.method !== 'POST') { 
        return response.status(405).json({ message: 'Method Not Allowed' }); 
    }
    
    const geminiApiKey = process.env.GEMINI_API_KEY;
    if (!geminiApiKey) { 
        return response.status(500).json({ message: 'Gemini API-Schlüssel ist auf dem Server nicht gesetzt.' }); 
    }
    
    try {
        const { prompt } = request.body;
        if (!prompt) { 
            return response.status(400).json({ message: 'Ein "prompt" ist erforderlich.' }); 
        }
        
        // Intelligenterer Prompt ohne Einschränkungen
        const improvedPrompt = `
Du bist ein erfahrener KFZ-Meister mit 20 Jahren Berufserfahrung. Analysiere das folgende Autoproblem präzise.

${prompt}

DEINE AUFGABE:
1. Liste ALLE möglichen Ursachen auf - von den wahrscheinlichsten zu den unwahrscheinlichsten
2. Identifiziere die EINE wahrscheinlichste Ursache basierend auf den Symptomen
3. Bewerte die Diagnose-Sicherheit: Wie eindeutig sind die Symptome? (0-100%)
4. Schätze für die wahrscheinlichste Ursache:
   - Minimale Kosten (best case - z.B. nur ein Sensor)
   - Maximale Kosten (worst case - z.B. Motorschaden)
   - Arbeitszeit in Stunden
5. Erkläre, warum die Kostenpanne so groß sein könnte

Antworte NUR mit einem JSON-Objekt:
{
    "possibleCauses": ["Alle möglichen Ursachen sortiert nach Wahrscheinlichkeit"],
    "mostLikelyCause": "Die wahrscheinlichste Ursache",
    "diagnosisCertainty": ZAHL_0_BIS_100,
    "costUncertaintyReason": "Erklärung warum die Kosten stark variieren können",
    "recommendation": "Deine Empfehlung",
    "urgency": "Niedrig|Mittel|Hoch|Kritisch",
    "estimatedLabor": ARBEITSZEIT_IN_STUNDEN,
    "minCost": MINIMALE_GESAMTKOSTEN,
    "maxCost": MAXIMALE_GESAMTKOSTEN,
    "likelyRequiredParts": ["Mögliche benötigte Teile"],
    "diagnosticStepsNeeded": ["Welche Tests/Diagnosen sind nötig"],
    "diyTips": ["Tipps für Selbermacher"],
    "youtubeSearchQuery": "Suchbegriff für YouTube"
}

Nutze dein Fachwissen über aktuelle Arbeitskosten (80-120€/h) und realistische Teilepreise.
`;

        const payload = { 
            contents: [{ 
                role: "user", 
                parts: [{ text: improvedPrompt }] 
            }],
            generationConfig: {
                temperature: 0.7,
                topK: 40,
                topP: 0.95,
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
            
            // Verbesserte JSON-Extraktion
            const jsonMatch = textResponse.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                try {
                    const parsedJson = JSON.parse(jsonMatch[0]);
                    
                    // Rückwärtskompatibilität sicherstellen
                    if (!parsedJson.estimatedPartsCost && parsedJson.minCost && parsedJson.maxCost) {
                        // Berechne geschätzte Teilekosten aus min/max für Kompatibilität
                        const avgCost = (parsedJson.minCost + parsedJson.maxCost) / 2;
                        const laborCost = (parsedJson.estimatedLabor || 2) * 90;
                        parsedJson.estimatedPartsCost = Math.max(0, avgCost - laborCost);
                    }
                    
                    // Sicherstellen, dass mostLikelyCause existiert
                    if (!parsedJson.mostLikelyCause && parsedJson.possibleCauses && parsedJson.possibleCauses.length > 0) {
                        parsedJson.mostLikelyCause = parsedJson.possibleCauses[0];
                    }
                    
                    // Setze Standardwerte wenn nötig
                    parsedJson.diagnosisCertainty = parsedJson.diagnosisCertainty || 50;
                    parsedJson.minCost = parsedJson.minCost || 100;
                    parsedJson.maxCost = parsedJson.maxCost || 1000;
                    
                    return response.status(200).json(parsedJson);
                } catch (parseError) {
                    console.error("JSON Parse Error:", parseError);
                    return response.status(500).json({ 
                        message: 'Fehler beim Parsen der KI-Antwort' 
                    });
                }
            } else { 
                console.error("Kein JSON in KI-Antwort gefunden:", textResponse);
                return response.status(500).json({ 
                    message: 'Kein gültiges JSON in der Gemini-Antwort gefunden' 
                }); 
            }
        } else { 
            console.error("Unerwartetes Format von Gemini:", result);
            return response.status(500).json({ 
                message: 'Unerwartetes Antwortformat von Gemini' 
            }); 
        }
    } catch (error) {
        console.error("Server-Fehler in /api/analyze:", error);
        response.status(500).json({ 
            message: 'Analyse konnte nicht verarbeitet werden', 
            details: error.message 
        });
    }
}
