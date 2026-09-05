/**
 * api.js
 * Punto unico di comunicazione con il backend PHP.
 *
 * tutte le pagine passeranno automaticamente a usare le vere chiamate REST.
 */

const API_BASE = './api';    // percorso base delle API PHP


/**
 * Esegue una richiesta verso l'API PHP e restituisce il campo "data" della risposta.
 * Lancia un Error con il messaggio del backend in caso di errore.
 */
async function apiRequest(endpoint, options = {}) {
  const res = await fetch(`${API_BASE}${endpoint}`, {
    credentials: 'include', // necessario per inviare/ricevere il cookie di sessione PHP
    headers: {
      'Accept': 'application/json',
      ...(options.headers || {})
    },
    ...options
  });

  let payload = null;
  try {
    payload = await res.json();
  } catch (e) {
    // risposta non JSON (es. errore PHP non gestito)
  }

  if (!res.ok || !payload || payload.success === false) {
    const msg = (payload && payload.error) ? payload.error : `Errore del server (${res.status})`;
    throw new Error(msg);
  }

  return payload.data;
}

/** Costruisce una query string da un oggetto, omettendo i valori vuoti. */
function buildQuery(params) {
  const usp = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      usp.append(key, value);
    }
  });
  const qs = usp.toString();
  return qs ? `?${qs}` : '';
}

/** Formatta una data ISO (YYYY-MM-DD) in formato leggibile italiano. */
function formatDate(isoDate) {
  if (!isoDate) return '';
  const d = new Date(isoDate + 'T00:00:00');
  if (isNaN(d)) return isoDate;
  return d.toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' });
}

/** Escape base per contenuti inseriti via innerHTML, per prevenire injection dai dati utente. */
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
