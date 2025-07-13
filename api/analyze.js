export default async function handler(request, response) {
    if (request.method !== 'POST') {
        return response.status(405).json({ error: 'Method Not Allowed' });
    }
    // Liest den API-Schlüssel aus den Vercel-Einstellungen.
    const geminiApiKey = process.env.GEMINI_API_KEY;

    // Schritt 1: Überprüfen, ob der Schlüssel auf dem Server gefunden wurde.
    if (!geminiApiKey) {
        console.error("SERVER FEHLER: GEMINI_API_KEY wurde in den Vercel-Einstellungen nicht gefunden.");
        return response.status(500).json({ error: 'Server-Konfigurationsfehler', message: 'Gemini API-Schlüssel ist auf dem Server nicht gesetzt.' });
    }

    try {
        const { prompt } = request.body;
        if (!prompt) {
            return response.status(400).json({ error: 'Fehlende Anfrageparameter', message: 'Ein "prompt" ist erforderlich.' });
        }
        const payload = { contents: [{ role: "user", parts: [{ text: prompt }] }] };
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${geminiApiKey}`;

        const geminiRes = await fetch(apiUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });

        // Schritt 2: Überprüfen, ob die Anfrage an Google erfolgreich war.
        if (!geminiRes.ok) {
            const errorBody = await geminiRes.text();
            console.error("GEMINI API FEHLER:", errorBody);
            return response.status(geminiRes.status).json({ error: `Gemini API Fehler`, details: errorBody });
        }

        const result = await geminiRes.json();

        if (result.candidates && result.candidates[0]?.content?.parts[0]?.text) {
            const textResponse = result.candidates[0].content.parts[0].text;
            const jsonMatch = textResponse.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                const parsedJson = JSON.parse(jsonMatch[0]);
                // Schritt 3: Erfolgreiche Antwort senden.
                return response.status(200).json(parsedJson);
            } else {
                return response.status(500).json({ error: 'Kein gültiges JSON in der Gemini-Antwort gefunden' });
            }
        } else {
            return response.status(500).json({ error: 'Unerwartetes Antwortformat von Gemini' });
        }
    } catch (error) {
        console.error("UNERWARTETER SERVER FEHLER:", error);
        response.status(500).json({ error: 'Analyse konnte nicht verarbeitet werden', details: error.message });
    }
}
