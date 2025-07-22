```javascript
const { useState, useEffect, useRef } = React;

// Custom Hook für den Schreibmaschinen-Effekt
function useTypewriter(text, speed = 30) {
  const [displayText, setDisplayText] = useState('');
  useEffect(() => {
    if (text == null) return;
    setDisplayText('');
    let i = 0;
    const intervalId = setInterval(() => {
      if (i < text.length) {
        setDisplayText(prev => prev + text[i]);
        i++;
      } else clearInterval(intervalId);
    }, speed);
    return () => clearInterval(intervalId);
  }, [text, speed]);
  return displayText;
}

// Lade-Spinner
const Spinner = ({ text }) => (
  <div className="flex items-center justify-center gap-2">
    <svg className="animate-spin h-5 w-5" viewBox="0 0 24 24">
      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
    </svg>
    <span>{text}</span>
  </div>
);

// Werkstatt-Typ-Info
const WorkshopTypeInfo = ({ type }) => {
  const info = {
    dealership:   { label: 'Vertragswerkstatt', color: 'text-blue-400 bg-blue-900/30 border-blue-800', icon: 'fa-award' },
    chain:        { label: 'Werkstattkette',    color: 'text-orange-400 bg-orange-900/30 border-orange-800', icon: 'fa-link' },
    independent:  { label: 'Freie Werkstatt',   color: 'text-green-400 bg-green-900/30 border-green-800', icon: 'fa-wrench' },
    specialist_transmission: { label: 'Getriebe-Spezialist', color: 'text-purple-400 bg-purple-900/30 border-purple-800', icon: 'fa-cogs' },
    specialist_engine:       { label: 'Motor-Spezialist',    color: 'text-red-400 bg-red-900/30 border-red-800', icon: 'fa-engine' },
  }[type] ?? info.independent;
  return (
    <span className={`inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-semibold border ${info.color}`}>
      <i className={`fa-solid ${info.icon}`} /> {info.label}
    </span>
  );
};

// Kostenschätzung
const CostEstimateDisplay = ({ analysis }) => {
  const min = analysis.minCost ?? 100;
  const max = analysis.maxCost ?? 1000;
  const certainty = analysis.diagnosisCertainty ?? 50;
  const range = max - min;
  const highUncertainty = range > 2000 || certainty < 40;
  const getColor = () => certainty >= 70 ? 'green' : certainty >= 40 ? 'yellow' : 'red';
  const color = getColor();
  const colorClass = { green:'border-green-500/50 bg-green-900/20', yellow:'border-yellow-500/50 bg-yellow-900/20', red:'border-red-500/50 bg-red-900/20' }[color];
  const textColor = { green:'text-green-400', yellow:'text-yellow-400', red:'text-red-400' }[color];
  return (
    <div className={`mt-6 p-6 border rounded-xl ${colorClass} backdrop-blur-sm`}>
      <p className="text-gray-400 text-sm mb-2">Geschätzte Reparaturkosten:</p>
      <div className="flex items-end justify-around h-16">
        <div><p className="text-xs text-gray-500">Minimum</p><p className="text-3xl font-bold text-green-400">{min}€</p></div>
        <div className="flex-1 mx-4">
          <div className="h-3 bg-gray-800 rounded-full"><div className={`h-full rounded-full bg-gradient-to-r from-${color}-500 to-${color}-400`} style={{width:`${certainty}%`}}/></div>
          <p className="text-xs text-center mt-1">Spanne {range}€</p>
        </div>
        <div><p className="text-xs text-gray-500">Maximum</p><p className="text-3xl font-bold text-red-400">{max}€</p></div>
      </div>
      <div className="mt-4 p-3 bg-gray-900/50 rounded">
        <p className="text-sm font-medium">Diagnose-Sicherheit: <span className={textColor}>{certainty}%</span></p>
        <div className="w-full bg-gray-700 rounded-full h-2 mt-1"><div className={`h-full rounded bg-${color}-500`} style={{width:`${certainty}%`}}/></div>
      </div>
      {analysis.diagnosticStepsNeeded && analysis.diagnosticStepsNeeded.length > 0 && (
        <div className="mt-4 p-3 bg-gray-900/50 rounded">
          <p className="text-sm font-semibold mb-1"><i className="fa-solid fa-stethoscope mr-2"/>Notwendige Diagnose-Schritte:</p>
          <ul className="list-disc ml-5 text-xs space-y-1">{analysis.diagnosticStepsNeeded.map((s,i)=><li key={i}>{s}</li>)}</ul>
        </div>
      )}
      {highUncertainty && (
        <div className="mt-3 p-2 bg-red-900/40 border border-red-500/50 rounded">
          <p className="text-xs">{analysis.costUncertaintyReason}</p>
          <button className="mt-1 text-xs bg-red-600 hover:bg-red-700 px-2 py-1 rounded"><i className="fa-solid fa-microscope"/> Interaktive Diagnose</button>
        </div>
      )}
    </div>
  );
};

// Workshop-Karte
const WorkshopCard = ({ workshop, problem }) => {
  const [analysis,setAnalysis]=useState(null);
  const [loading,setLoading]=useState(false);
  const [error,setError]=useState('');
  const [open,setOpen]=useState(false);
  const needsAlign = problem && /fahrwerk|spurstange|querlenker|stoßdämpfer/i.test(problem);
  const analyze = async () => {
    if(!workshop.reviews?.length){setError('Keine Rezensionen');return;}
    setLoading(true);
    setError('');
    try{
      const text = workshop.reviews.slice(0,5).map(r=>r.text).join('\n');
      const res = await fetch('/api/analyze',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({prompt:`Problem: ${problem} — Fahrzeugdaten. Reviews:\n${text}`})});
      setAnalysis(await res.json());
    }catch{setError('Fehlgeschlagen');}finally{setLoading(false);}
  };
  return (
    <div className="p-5 rounded-xl bg-gray-900 border border-gray-700">
      <div className="flex gap-4">
        <img src={workshop.photoUrl||'https://placehold.co/96x96/1e253a/4cc3ee?text=W'} alt={workshop.name}
             className="w-24 h-24 rounded object-cover" onError={e=>e.target.src='https://placehold.co/96x96/1e253a/4cc3ee?text=W'}/>
        <div className="flex-1">
          <div className="flex items-start justify-between">
            <h3 className="font-bold text-white">{workshop.name}</h3>
            <WorkshopTypeInfo type={workshop.workshopType}/>
          </div>
          <p className="text-sm text-gray-400"><i className="fa-solid fa-location-dot"/> {workshop.vicinity}</p>
          <p className="text-sm mt-1"><span>{workshop.rating||'—'}</span> <i className="fa-star text-amber-400"/> <span>({workshop.user_ratings_total||0})</span></p>
        </div>
      </div>
      {needsAlign && workshop.workshopType==='independent' && <p className="mt-2 text-xs text-amber-400">Spureinstellung wird ggf. separat benötigt (80–120 €).</p>}
      <div className="mt-3 flex gap-2">
        <button onClick={()=>setOpen(!open)} className="btn-secondary flex-1"><i className="fa-solid fa-info-circle"/> {open?'Weniger':'Mehr'}</button>
        <button onClick={analyze} disabled={loading} className="btn-primary flex-1 disabled:opacity-50">{loading?<Spinner text="Kurz..."/>:'KI-Analyse'}</button>
      </div>
      {open && (
        <div className="mt-4 pt-4 border-t border-gray-700 text-sm space-y-3">
          {workshop.phone && <a href={`tel:${workshop.phone}`} className="text-[#4cc3ee] flex gap-2"><i className="fa-solid fa-phone"/><span>{workshop.phone}</span></a>}
          {workshop.website && <a href={workshop.website} target="_blank" rel="noreferrer" className="text-[#4cc3ee] flex gap-2"><i className="fa-solid fa-globe"/>Website</a>}
          <a href={`https://maps.google.com/?q=place_id:${workshop.place_id}`} target="_blank" rel="noreferrer" className="inline-flex items-center bg-[#4cc3ee]/10 px