```js
import { NextApiRequest, NextApiResponse } from 'next';
import crypto from 'crypto';

// Helper for consistent ETag generation
const md5 = (data) => crypto.createHash('md5').update(data).digest('hex');

export default async function handler(req, res) {
  if (req.method !== 'POST') {
    res.setHeader('Allow', ['POST']);
    return res.status(405).json({ success: false, message: 'Method Not Allowed' });
  }

  const { prompt, useGemini = false } = req.body;

  if (!prompt?.trim()) {
    return res.status(400).json({ success: false, message: '"prompt" ist erforderlich' });
  }

  const anthropicApiKey = process.env.ANTHROPIC_API_KEY;
  const geminiApiKey = process.env.GEMINI_API_KEY;

  if (!anthropicApiKey && !geminiApiKey) {
    return res.status(500).json({ success: false, message: 'Keine API-Schlüssel konfiguriert' });
  }

  // Construct cache key
  const cacheKey = md5(`${prompt}-${useGemini}`);
  // Very short cache‐ttl because car symptoms evolve quickly
  res.setHeader('Cache-Control', 'private, max-age=60, stale-while-revalidate=30');
  res.setHeader('ETag', `"${cacheKey}"`);

  if (req.headers['if-none-match'] === `"${cacheKey}"`) {
    return res.status(304).json({});
  }

  // Decide which model to use
  const isGemini = useGemini || /\brezension|review\b/i.test(prompt);

  try {
    const data = isGemini
      ? await callGemini(prompt, geminiApiKey || '')
      : await callClaude(prompt, anthropicApiKey || '');

    return res.status(200).json(data);
  } catch (err: any) {
    console.error('[analyze]', err.message || err);
    return res
      .status(err.status || 500)
      .json({ success: false, message: err.message || 'Server-Fehler' });
  }
}

// --- Claude ----------------------------------------------------------
const SYSTEM_PROMPT_CLAUDE = `You are an experienced automotive master mechanic (KFZ-Meister).  
Return only valid JSON matching the below schema. Never wrap with triple backticks.

Schema:
\`\`\`
{
  possibleCauses: string[],                     // 2-4 plausible faults
  mostLikelyCause: string,                      // single best guess
  diagnosisCertainty: number,                   // 0-100 %
  affectedCategories: string[],               // e.g. ["Electrics","Engine"]
  costUncertaintyReason: string,              // plain text or ""
  recommendation: string,
  urgency: "Niedrig" | "Mittel" | "Hoch",
  estimatedLabor: number,                     // hours, 0.00-99.99
  minCost: number,
  maxCost: number,
  likelyRequiredParts: string[],
  diagnosticStepsNeeded: string[],
  selfCheckPossible: boolean,
  selfCheckDifficulty: "Einfach" | "Mittel" | "Schwer",
  diyTips: string[],
  youtubeSearchQuery: string
}
\`\`\`
`;

async function callClaude(prompt: string, apiKey: string) {
  const res = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'x-api-key': apiKey,
      'anthropic-version': '2023-06-01',
    },
    body: JSON.stringify({
      model: 'claude-3-5-sonnet-20241022',
      max_tokens: 1500,
      temperature: 0,
      system: SYSTEM_PROMPT_CLAUDE,
      messages: [
        { role: 'user', content: prompt }
      ],
    }),
  });

  if (!res.ok) {
    const errorJson = await res.json().catch(() => {});
    throw Object.assign(new Error(errorJson?.error?.message || `Claude HTTP ${res.status}`), { status: res.status });
  }

  const json = await res.json();
  const text = json.content?.[0]?.text?.trim();
  return JSON.parse(text);
}

// --- Gemini -----------------------------------------------------------
const SYSTEM_PROMPT_GEMINI = `You are a German automotive master mechanic.  
Only return pure JSON matching the Claude schema provided (same keys, same meanings).`;

async function callGemini(prompt: string, apiKey: string) {
  const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify({
      contents: [{
        role: 'user',
        parts: [
          { text: SYSTEM_PROMPT_GEMINI },
          { text: prompt }
        ]
      }],
      generationConfig: { maxOutputTokens: 1500, temperature: 0, topP: 0.9 }
    }),
  });

  if (!res.ok) {
    const errorText = await res.text().catch(() => 'Gemini error');
    throw Object.assign(new Error(errorText), { status: res.status });
  }

  const json = await res.json();
  const text = json.candidates?.[0]?.content?.parts?.[0]?.text?.trim();
  return JSON.parse(text);
}
```