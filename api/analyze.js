export default async function handler(request, response) {
    if (request.method !== 'POST') { return response.status(405).json({ message: 'Method Not Allowed' }); }
    const geminiApiKey = process.env.GEMINI_API_KEY;
    if (!geminiApiKey) { return response.status(500).json({ message: 'Gemini API-Schlüssel ist auf dem Server nicht gesetzt.' }); }
    try {
        const { prompt } = request.body;
        if (!prompt) { return response.status(400).json({ message: 'Ein "prompt" ist erforderlich.' }); }

        // Neuer, präziserer Prompt für die KI
        const newPrompt = `
            Analysiere das folgende Autoproblem. Erstelle eine strukturierte JSON-Antwort.

            WICHTIGE ANWEISUNGEN:
            1.  **possibleCauses**: Erstelle eine Liste möglicher Ursachen. Formatiere JEDEN Eintrag als String, der mit "- " beginnt (Bindestrich gefolgt von einem Leerzeichen).
            2.  **Kostenschätzung**: Identifiziere die EINE, wahrscheinlichste Ursache aus deiner Liste. Die Felder "estimatedLabor" und "estimatedPartsCost" dürfen sich NUR auf diese EINE wahrscheinlichste Ursache beziehen. Addiere NICHT die Kosten für alle möglichen Ursachen.

            Das JSON muss exakt die folgenden Felder enthalten:
            - "possibleCauses": Array von Strings.
            - "recommendation": String.
            - "urgency": String ('Niedrig', 'Mittel', oder 'Hoch').
            - "estimatedLabor": Zahl (nur für die wahrscheinlichste Ursache).
            - "estimatedPartsCost": Zahl (nur für die wahrscheinlichste Ursache).
            - "likelyRequiredParts": Array von Strings.
            - "diyTips": Array von Strings.
            - "youtubeSearchQuery": String.

            Hier ist die Anfrage des Nutzers:
            ${prompt}
        `;

        const payload = { contents: [{ role: "user", parts: [{ text: newPrompt }] }] };
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${geminiApiKey}`;

        const geminiRes = await fetch(apiUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });

        if (!geminiRes.ok) { 
            const errorBody = await geminiRes.text(); 
            console.error("Gemini API Error:", errorBody);
            return response.status(geminiRes.status).json({ message: `Gemini API Fehler`, details: errorBody }); 
        }

        const result = await geminiRes.json();

        if (result.candidates && result.candidates[0]?.content?.parts[0]?.text) {
            const textResponse = result.candidates[0].content.parts[0].text;
            const jsonMatch = textResponse.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                return response.status(200).json(JSON.parse(jsonMatch[0]));
            } else { 
                console.error("Kein JSON in KI-Antwort gefunden:", textResponse);
                return response.status(500).json({ message: 'Kein gültiges JSON in der Gemini-Antwort gefunden' }); 
            }
        } else { 
            console.error("Unerwartetes Format von Gemini:", result);
            return response.status(500).json({ message: 'Unerwartetes Antwortformat von Gemini' }); 
        }
    } catch (error) {
        console.error("Server-Fehler in /api/analyze:", error);
        response.status(500).json({ message: 'Analyse konnte nicht verarbeitet werden', details: error.message });
    }
}
