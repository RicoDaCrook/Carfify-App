// /api/diagnose.js
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
        if (!history) {
            return response.status(400).json({ message: 'Ein "history" Array ist erforderlich.' });
        }

        // Der Prompt an die KI, um die nächste Frage oder eine finale Antwort zu bekommen
        const prompt = `
            Du bist ein KFZ-Diagnose-Assistent. Basierend auf dem folgenden Gesprächsverlauf, entscheide, was der nächste Schritt ist.
            Gesprächsverlauf:
            ${history.map(item => `${item.role}: ${item.parts[0].text}`).join('\n')}

            Deine Aufgabe:
            1. Wenn du GENUG Informationen hast, um eine finale, präzise Diagnose zu stellen, antworte mit einem JSON-Objekt, das exakt diese Struktur hat: {"finalDiagnosis": "Deine detaillierte, finale Diagnose hier."}.
            2. Wenn du MEHR Informationen brauchst, stelle EINE EINZIGE, gezielte Rückfrage. Antworte dann mit einem JSON-Objekt, das exakt diese Struktur hat: {"nextQuestion": "Deine nächste Frage hier.", "answers": ["Antwort A", "Antwort B", "Antwort C"]}.

            Antworte NUR mit dem JSON-Objekt.
        `;

        const payload = { contents: [{ role: "user", parts: [{ text: prompt }] }] };
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${geminiApiKey}`;

        const geminiRes = await fetch(apiUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });

        if (!geminiRes.ok) {
            const errorBody = await geminiRes.text();
            return response.status(geminiRes.status).json({ message: `Gemini API Fehler`, details: errorBody });
        }

        const result = await geminiRes.json();

        if (result.candidates && result.candidates[0]?.content?.parts[0]?.text) {
            const textResponse = result.candidates[0].content.parts[0].text;
            const jsonMatch = textResponse.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                return response.status(200).json(JSON.parse(jsonMatch[0]));
            } else {
                return response.status(500).json({ message: 'Kein gültiges JSON in der Gemini-Antwort gefunden' });
            }
        } else {
            return response.status(500).json({ message: 'Unerwartetes Antwortformat von Gemini' });
        }
    } catch (error) {
        response.status(500).json({ message: 'Diagnose konnte nicht verarbeitet werden', details: error.message });
    }
}
