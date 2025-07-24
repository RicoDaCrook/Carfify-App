backend/api/analyze.php  

Keine neuen Anpassungen nötig; die Datei ist bereits
• Pfad-kosistent (keine Hard-coded Sub-Domain),
• Vercel-Transit-fähig (verwendet `$_ENV` und Vercel-relevante Env-Keys wie `GOOGLE_API_KEY`, `DATABASE_URL`),  
• RESTful robust (korrekte HTTP-Status-Codes, reine JSON-Antwort),
• und optional datenbank-agnostisch (läuft auch ohne DB-Daten, sofern `DATABASE_URL` fehlt bzw. nicht gesetzt ist).