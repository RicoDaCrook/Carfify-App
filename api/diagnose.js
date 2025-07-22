```javascript
import { NextApiRequest, NextApiResponse } from 'next';

/**
 * @param {NextApiRequest} req
 * @param {NextApiResponse} res
 */
export default async function handler(req, res) {
  if (req.method !== 'POST') {
    res.setHeader('Allow', ['POST']);
    return res.status(405).json({ message: 'Method Not Allowed' });
  }

  const anthropicApiKey = process.env.ANTHROPIC_API_KEY;
  if (!anthropicApiKey) {
    return res.status(500).json({ message: 'Claude API-Schlüssel ist auf dem Server nicht gesetzt.' });
  }

  try {
    const { history, mode = 'questions', category, currentCertainty } = req.body;

    if (!Array.isArray(history) || history.length === 0) {
      return res.status(400).json({ message: 'Ein nicht-leeres "history" Array ist erforderlich.' });
    }

    const handlerFn = MODES[mode] ?? modesHandler.questions;
    return await handlerFn(history, { category, currentCertainty, anthropicApiKey, res });
  } catch (error) {
    console.error('Server-Fehler in /api/diagnose:', error);
    return res.status(500).json({
      message: 'Diagnose konnte nicht verarbeitet werden',
      details: error.message,
    });
  }
}

const extractText = (entry) => entry?.parts?.[0]?.text ?? '';

const buildPrompt = (history) => history.map((h) => `${h.role}: ${extractText(h)}`).join('\n');

/**
 * @typedef {Object} Context
 * @property {string} [category]
 * @property {number} [currentCertainty]
 * @property {string} anthropicApiKey
 * @property {NextApiResponse} res
 * @param {Array} history
 * @param {Context} ctx
 */

const modesHandler = Object.freeze({
  analyze_categories: async (history, { anthropicApiKey, res }) => {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Experte. Im Folgenden beschreibt der Nutzer sein Problem.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"singleCategory":true,"categoryName":"KATEGORIE"} falls genau eine Kategorie passt,
{"singleCategory":false,"categories":[{"name":"KATEGORIE1","probability":INT},{"name":"KATEGORIE2","probability":INT}]} falls mehrere infrage kommen.
Erlaubte Kategorien: Fahrwerk, Rad/Reifen, Motor, Getriebe, Bremsen, Elektronik, Karosserie, Innenraum.`;

    const prompt = extractText(history[0]);
    const result = await makeClaudeRequest(systemPrompt, prompt, anthropicApiKey, 512);
    return res.status(200).json(result);
  },

  category_questions: async (history, { category, currentCertainty, anthropicApiKey, res }) => {
    const conversation = buildPrompt(history);
    const questionCount = (conversation.match(/^model:/gm) || []).length;

    const systemPrompt = `Du bist ein KFZ-Diagnose-Experte. Prüfe systematisch die Kategorie "${category}".
Stelle maximal 5 fahrzeugbezogene Fragen, die ohne Werkstatt beantwortbar sind (Geräusche, Fahrverhalten, Warnleuchten etc.).
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"categoryComplete":true,"certaintyIncrease":0–25,"summary":"Zusammenfassung"} wenn abgeschlossen,
{"nextQuestion":"Frage?","answers":["Ja","Nein","Weiß nicht"]} wenn mehr Infos nötig.`;

    const prompt = `Kontext:\n${conversation}\n\nAktuelle Sicherheit: ${currentCertainty}%\nKategorierelevante Fragen bisher: ${questionCount}`;
    const result = await makeClaudeRequest(systemPrompt, prompt, anthropicApiKey);
    return res.status(200).json(result);
  },

  final_summary: async (history, { anthropicApiKey, res }) => {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Experte. Erstelle anhand des gesamten Gesprächsverlaufs eine präzise Diagnose.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"finalDiagnosis":"Klare Problembeschreibung","affectedParts":["Teil 1","Teil 2"],"confidence":"Textuelle Einschätzung","nextSteps":"Sinnvolle weiteren Schritte ohne Werkstattbesuch"}.`;

    const prompt = buildPrompt(history);
    const result = await makeClaudeRequest(systemPrompt, prompt, anthropicApiKey, 1200);
    return res.status(200).json(result);
  },

  freetext: async (history, { anthropicApiKey, res }) => {
    const systemPrompt = `Du bist ein KFZ-Diagnose-Assistent. Beantworte die gestellte Frage knapp, konkret und praxisorientiert.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"answer":"Deine prägnante Antwort","suggestedNextSteps":["Erster Vorschlag","Zweiter Vorschlag"]}.`;

    const context = history.slice(0, -1).map((h) => `${h.role}: ${extractText(h)}`).join('\n');
    const question = extractText(history.at(-1));

    const prompt = `Diskussionskontext:\n${context}\n\nNutzerfrage: ${question}`;
    const result = await makeClaudeRequest(systemPrompt, prompt, anthropicApiKey);
    return res.status(200).json(result);
  },

  questions: async (history, { anthropicApiKey, res }) => {
    const systemPrompt = `Du bist ein fahrkundiger KFZ-Assistent. Stelle maximal 10 einfache Verständnisfragen um das Problem einzugrenzen.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"finalDiagnosis":"Präzise Problembeschreibung","certainty":0–100,"affectedParts":["Teil1","Teil2"]} oder
{"nextQuestion":"Fragetext?","answers":["Ja","Nein","Teilweise","Weiß nicht"]}.`;

    const prompt = buildPrompt(history);
    const result = await makeClaudeRequest(systemPrompt, prompt, anthropicApiKey);
    return res.status(200).json(result);
  },
});

// freeze complete dispatcher object
const MODES = Object.freeze({ ...modesHandler });

/**
 * @param {string} systemPrompt
 * @param {string} userPrompt
 * @param {string} apiKey
 * @param {number} [maxTokens=1024]
 */
async function makeClaudeRequest(systemPrompt, userPrompt, apiKey, maxTokens = 1024) {
  const payload = {
    model: 'claude-3-5-sonnet-20241022',
    max_tokens: maxTokens,
    temperature: 0.2,
    system: systemPrompt,
    messages: [{ role: 'user', content: userPrompt }],
  };

  const resp = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'anthropic-version': '2023-06-01',
      'x-api-key': apiKey,
    },
    body: JSON.stringify(payload),
  });

  if (!resp.ok) {
    const error = await resp.json().catch(() => ({ error: { message: 'Netzwerkfehler' } }));
    throw new Error(error.error?.message || `Claude API Fehler (${resp.status})`);
  }

  const { content } = await resp.json();
  const raw = content?.[0]?.text?.trim();

  if (!raw) throw new Error('Leere API-Antwort');

  try {
    return JSON.parse(raw.match(/\{[\s\S]*\}/)?.[0] ?? '');
  } catch {
    throw new Error('Antwort konnte nicht verarbeitet werden');
  }
}
```