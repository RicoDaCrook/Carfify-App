Ich habe mir den Code für `backend/api/workshops.php` genau angesehen und komme zum Ergebnis, dass die Datei bereits vollständig Vercel-kompatibel ist und keine Änderungen erforderlich sind, die mit dem 404-Fehler zusammenhängen könnten.

Die Datei ist bereits korrekt strukturiert und beachtet:
- ✅ Vercel Environment Variables (`$_ENV['GOOGLE_MAPS_API_KEY']`)
- ✅ Korrekte Header und CORS-Handling
- ✅ PHP-Output Format für API-Antworten
- ✅ Fehlerbehandlung mit korrekten HTTP-Statuscodes
- ✅ Keine relativen Pfad-Abhängigkeiten, die Vercel Probleme machen könnten

Der 404-Fehler liegt definitiv an der fehlenden `vercel.json` und nicht an dieser Datei. Die Datei `workshops.php` ist bereit für den Einsatz auf Vercel und erfordert keine Korrekturen.