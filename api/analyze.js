```js
import { NextApiRequest, NextApiResponse } from 'next';
import crypto from 'crypto';

const md5 = (data: string) =>
  crypto.createHash('md5').update(data).digest('hex');

export default async function handler(
  req: NextApiRequest,
  res: NextApiResponse
) {
  if (req.method !== 'POST') {
    res.setHeader('Allow', ['POST']);
    return res.status(405).json({ success: false, message: 'Method Not Allowed' });
  }

  const { prompt, useGemini = false } = req.body as {
    prompt?: string;
    useGemini?: boolean;
  };

  if (!prompt?.trim()) {
    return res.status(400).json({ success: false, message: '"prompt" ist erforderlich' });
  }

  const anthropicApiKey = process.env.ANTHROPIC_API_KEY;
  const geminiApiKey = process.env.GEMINI_API_KEY;

  if (!anthropicApiKey && !geminiApiKey) {
    return res.status(500).json({ success: false, message: 'Keine API-Schlüssel konfiguriert' });
  }

  const isGemini = useGemini || /\brezension|review\b/i.test(prompt);

  const cacheKey = md5(`${prompt}-${isGemini}`);
  res.setHeader('Cache-Control', 'private, max-age=60, stale-while-revalidate=30');
  res.setHeader('ETag', `"${cacheKey}"`);

  if (req.headers['if-none-match'] === `"${cacheKey}"`) {
    return res.status(304).send('');
  }

  try {
    const data = isGemini
      ? await callGemini(prompt.trim(), geminiApiKey!)
      : await callClaude(prompt.trim(), anthropicApiKey!);

    return res.status(200).json(data);
  } catch (err: any) {
    console.error('[analyze]', err.message || err);
    const code = err.status || 500;
    const msg = err.message || 'Server-Fehler';
    return res.status(code).json({ success: false, message: msg });
  }
}

const SYSTEM_PROMPT_CLAUDE = `You are an experienced automotive master mechanic (KFZ-Meister).  
Return only pure, valid JSON matching this schema:

{
  "possibleCauses": ["<string>"],
  "mostLikelyCause": "<string>",
  "diagnosisCertainty": 0-100,
  "affectedCategories": ["<string>"],
  "costUncertaintyReason": "<string>|\"\"",
  "recommendation": "<string>",
  "urgency": "Niedrig|Mittel|Hoch",
  "estimatedLabor": 0.00-99.99,
  "minCost": 0,
  "maxCost": 0,
  "likelyRequiredParts": ["<string>"],
  "diagnosticStepsNeeded": ["<string>"],
  "selfCheckPossible": true|false,
  "selfCheckDifficulty": "Einfach|Mittel|Schwer",
  "diyTips": ["<string>"],
  "youtubeSearchQuery": "<string>"
}`;

async function callClaude(prompt: string, apiKey: string) {
  const body = {
    model: 'claude-3-5-sonnet-20241022',
    max_tokens: 1500,
    temperature: 0,
    system: SYSTEM_PROMPT_CLAUDE,
    messages: [{ role: 'user', content: prompt }],
  };
  const res = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'x-api-key': apiKey,
      'anthropic-version': '2023-06-01',
    },
    body: JSON.stringify(body),
  });

  if (!res.ok) {
    const err = await res.json().catch(() => res.statusText);
    throw Object.assign(new Error(err?.error?.message || res.statusText), { status: res.status });
  }

  const json = await res.json();
  return JSON.parse(json.content?.[0]?.text);
}

const SYSTEM_PROMPT_GEMINI = 'Return pure JSON with the same keys as the Claude schema.';

async function callGemini(prompt: string, apiKey: string) {
  const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;
  const body = {
    contents: [
      {
        role: 'user',
        parts: [
          { text: SYSTEM_PROMPT_GEMINI },
          { text: prompt },
        ],
      },
    ],
    generationConfig: { maxOutputTokens: 1500, temperature: 0, topP: 0.9 },
  };
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify(body),
  });

  if (!res.ok) {
    const err = await res.text().catch(() => 'Gemini error');
    throw Object.assign(new Error(err), { status: res.status });
  }

  const json = await res.json();
  return JSON.parse(json.candidates[0].content.parts[0].text);
}
```