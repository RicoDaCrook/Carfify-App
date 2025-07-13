export default async function handler(request, response) {
    if (request.method !== 'POST') { return response.status(405).json({ error: 'Method Not Allowed' }); }
    // KORREKTUR: Der Name des API-Schlüssels wurde geändert.
    const geminiApiKey = process.env.GEMINI_API_KEY;

    if (!geminiApiKey) { return response.status(500).json({ error: 'Gemini API key not configured on server' }); }
    try {
        const { prompt } = request.body;
        if (!prompt) { return response.status(400).json({ error: 'Prompt is required' }); }
        const payload = { contents: [{ role: "user", parts: [{ text: prompt }] }] };
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${geminiApiKey}`;
        const geminiRes = await fetch(apiUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        if (!geminiRes.ok) { const errorBody = await geminiRes.text(); return response.status(geminiRes.status).json({ error: `Gemini API Error`, details: errorBody }); }
        const result = await geminiRes.json();
        if (result.candidates && result.candidates[0]?.content?.parts[0]?.text) {
            const textResponse = result.candidates[0].content.parts[0].text;
            const jsonMatch = textResponse.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                const parsedJson = JSON.parse(jsonMatch[0]);
                return response.status(200).json(parsedJson);
            } else { return response.status(500).json({ error: 'No valid JSON found in Gemini response' }); }
        } else { return response.status(500).json({ error: 'Unexpected response format from Gemini' }); }
    } catch (error) {
        response.status(500).json({ error: 'Failed to process analysis request', details: error.message });
    }
}
