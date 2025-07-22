```javascript
import { NextApiRequest, NextApiResponse } from 'next';
import { z } from 'zod';

const ALLOWED_METHODS = ['POST'] as const;

const HistoryItemSchema = z.object({
  role: z.enum(['user', 'model']),
  parts: z.array(
    z.object({
      text: z.string(),
    })
  ),
});

const RequestSchema = z.object({
  history: z.array(HistoryItemSchema).min(1, 'Ein nicht-leeres "history" Array ist erforderlich.'),
  mode: z.enum(['analyze_categories', 'category_questions', 'final_summary', 'freetext', 'questions']).default('questions'),
  category: z.string().optional(),
  currentCertainty: z.number().min(0).max(100).optional(),
});

const ClaudeResponseSchema = z.object({
  id: z.string(),
  type: z.literal('message'),
  role: z.literal('assistant'),
  content: z.array(z.object({ type: z.literal('text'), text: z.string() })),
  model: z.string(),
  stop_reason: z.string(),
  usage: z.object({ input_tokens: z.number(), output_tokens: z.number() }),
});

const ErrorResponseSchema = z.object({
  error: z.object({ message: z.string() }),
});

type ModeContext = {
  category?: string;
  currentCertainty?: number;
  anthropicApiKey: string;
  res: NextApiResponse;
};

type ModeHandler = (history: z.infer<typeof HistoryItemSchema>[], ctx: ModeContext) => Promise<void>;

const HEADERS = {
  'Content-Type': 'application/json',
  'Anthropic-Version': '2023-06-01',
} as const;

const extractText = (entry: z.infer<typeof HistoryItemSchema>) => entry.parts?.[0]?.text ?? '';

const buildPrompt = (history: z.infer<typeof HistoryItemSchema>[]) =>
  history.map((h) => `${h.role}: ${extractText(h)}`).join('\n');

const categorizationPrompt = `Du bist ein KFZ-Diagnose-Experte. Im Folgenden beschreibt der Nutzer sein Problem.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"singleCategory":true,"categoryName":"KATEGORIE"} falls genau eine Kategorie passt,
{"singleCategory":false,"categories":[{"name":"KATEGORIE1","probability":INT},{"name":"KATEGORIE2","probability":INT}]} falls mehrere infrage kommen.
Erlaubte Kategorien: Fahrwerk, Rad/Reifen, Motor, Getriebe, Bremsen, Elektronik, Karosserie, Innenraum.`;

const categoryQuestionsPrompt = `Du bist ein KFZ-Diagnose-Experte. Prüfe systematisch die Kategorie "{category}".
Stelle maximal 5 fahrzeugbezogene Fragen, die ohne Werkstatt beantwortbar sind (Geräusche, Fahrverhalten, Warnleuchten etc.).
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"categoryComplete":true,"certaintyIncrease":0–25,"summary":"Zusammenfassung"} wenn abgeschlossen,
{"nextQuestion":"Frage?","answers":["Ja","Nein","Weiß nicht"]} wenn mehr Infos nötig.`;

const finalSummaryPrompt = `Du bist ein KFZ-Diagnose-Experte. Erstelle anhand des gesamten Gesprächsverlaufs eine präzise Diagnose.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"finalDiagnosis":"Klare Problembeschreibung","affectedParts":["Teil 1","Teil 2"],"confidence":"Textuelle Einschätzung","nextSteps":"Sinnvolle weiteren Schritte ohne Werkstattbesuch"}.`;

const freetextPrompt = `Du bist ein KFZ-Diagnose-Assistent. Beantworte die gestellte Frage knapp, konkret und praxisorientiert.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"answer":"Deine prägnante Antwort","suggestedNextSteps":["Erster Vorschlag","Zweiter Vorschlag"]}.`;

const questionsPrompt = `Du bist ein fahrkundiger KFZ-Assistent. Stelle maximal 10 einfache Verständnisfragen um das Problem einzugrenzen.
Antworte AUSSCHLIEßLICH mit gültigem JSON:
{"finalDiagnosis":"Präzise Problembeschreibung","certainty":0–100,"affectedParts":["Teil1","Teil2"]} oder
{"nextQuestion":"Fragetext?","answers":["Ja","Nein","Teilweise","Weiß nicht"]}.`;

const handlers: Record<string, ModeHandler> = {
  analyze_categories: async (history, { anthropicApiKey, res }) => {
    const prompt = extractText(history[0]);
    const result = await invokeClaudeAPI(categorizationPrompt, prompt, anthropicApiKey, 512);
    res.status(200).json(result);
  },

  category_questions: async (history, { category, currentCertainty, anthropicApiKey, res }) => {
    const conversation = buildPrompt(history);
    const questionCount = (conversation.match(/^model:/gm) || []).length;
    const prompt = `Kontext:\n${conversation}\n\nAktuelle Sicherheit: ${currentCertainty}%\nKategorierelevante Fragen bisher: ${questionCount}`;
    const systemPrompt = categoryQuestionsPrompt.replace('{category}', category ?? 'allgemein');
    const result = await invokeClaudeAPI(systemPrompt, prompt, anthropicApiKey);
    res.status(200).json(result);
  },

  final_summary: async (history, { anthropicApiKey, res }) => {
    const prompt = buildPrompt(history);
    const result = await invokeClaudeAPI(finalSummaryPrompt, prompt, anthropicApiKey, 1200);
    res.status(200).json(result);
  },

  freetext: async (history, { anthropicApiKey, res }) => {
    const context = history.slice(0, -1).map((h) => `${h.role}: ${extractText(h)}`).join('\n');
    const question = extractText(history.at(-1) ?? { role: 'user', parts: [] });
    const prompt = `Diskussionskontext:\n${context}\n\nNutzerfrage: ${question}`;
    const result = await invokeClaudeAPI(freetextPrompt, prompt, anthropicApiKey);
    res.status(200).json(result);
  },

  questions: async (history, { anthropicApiKey, res }) => {
    const prompt = buildPrompt(history);
    const result = await invokeClaudeAPI(questionsPrompt, prompt, anthropicApiKey);
    res.status(200).json(result);
  },
};

async function invokeClaudeAPI(systemPrompt: string, userPrompt: string, apiKey: string, maxTokens = 1024) {
  const payload = {
    model: 'claude-3-5-sonnet-20241022',
    max_tokens: maxTokens,
    temperature: 0.2,
    system: systemPrompt,
    messages: [{ role: 'user', content: userPrompt }],
  };

  const response = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: { ...HEADERS, 'x-api-key': apiKey },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: { message: 'Netzwerkfehler' } }));
    throw new Error(ErrorResponseSchema.parse(error).error.message || `Claude API Fehler (${response.status})`);
  }

  const data = ClaudeResponseSchema.parse(await response.json());
  const raw = data.content?.[0]?.text?.trim();
  if (!raw) throw new Error('Leere API-Antwort');

  try {
    return JSON.parse(raw.match(/\{[\s\S]*\}/)?.[0] ?? '');
  } catch {
    throw new Error('Antwort konnte nicht verarbeitet werden');
  }
}

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (!ALLOWED_METHODS.includes(req.method as any)) {
    res.setHeader('Allow', ALLOWED_METHODS);
    return res.status(405).json({ message: 'Method Not Allowed' });
  }

  const anthropicApiKey = process.env.ANTHROPIC_API_KEY;
  if (!anthropicApiKey) {
    return res.status(500).json({ message: 'Claude API-Schlüssel ist auf dem Server nicht gesetzt.' });
  }

  try {
    const { history, mode, category, currentCertainty } = RequestSchema.parse(req.body);

    const handler = handlers[mode];
    if (!handler) {
      throw new Error(`Unbekannter Modus: ${mode}`);
    }
    await handler(history, { category, currentCertainty, anthropicApiKey, res });
  } catch (error) {
    console.error('Server-Fehler in /api/diagnose:', error);
    return res.status(500).json({
      message: 'Diagnose konnte nicht verarbeitet werden',
      details: error instanceof Error ? error.message : 'Unbekannter Fehler',
    });
  }
}
```