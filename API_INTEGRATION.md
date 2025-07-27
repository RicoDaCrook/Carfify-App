# 🔌 CARFIFY API INTEGRATION

## Mobile.de API
- **Endpoint:** https://services.mobile.de/
- **Verwendung:** Fahrzeugpreise, Marktanalyse
- **Auth:** OAuth 2.0
- **Rate Limit:** 1000 Calls/Tag

## Claude API (Anthropic)
- **Endpoint:** https://api.anthropic.com/v1/messages
- **Verwendung:** Meister Müller Diagnose-KI
- **Auth:** Bearer Token
- **Model:** claude-3-sonnet-20240229

## Google Maps API
- **Endpoint:** https://maps.googleapis.com/maps/api/
- **Verwendung:** Werkstattsuche, Standorte
- **Auth:** API Key
- **Services:** Places, Geocoding

## Umgebungsvariablen Setup
```bash
# .env Datei erstellen
CLAUDE_API_KEY=sk-ant-...
MOBILE_DE_API_KEY=...
GOOGLE_MAPS_API_KEY=...
GEMINI_API_KEY=...
```
