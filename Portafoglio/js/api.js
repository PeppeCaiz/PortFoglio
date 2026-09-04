/**
 * api.js
 * Punto unico di comunicazione con il backend PHP.
 *
 * tutte le pagine passeranno automaticamente a usare le vere chiamate REST.
 */
// URL base per le chiamate php
var baseUrl = './api';

// Funzione per fare le chiamate al server
async function apiRequest(endpoint, opzioni) {
  if (!opzioni) opzioni = {};

  try {
    var risposta = await fetch(baseUrl + endpoint, {
      method: opzioni.method || 'GET',
      headers: opzioni.headers || {},
      body: opzioni.body || null,
      credentials: 'include'
    });

    var dati = await risposta.json();

    if (!risposta.ok || dati.success === false) {
      // Ignore or custom-handle session errors quietly
      if (risposta.status === 401 || dati.error === "Nessuna sessione attiva.") {
        console.warn("Sessione non attiva:", dati.error);
        return null;
      }

      // Show alerts only for other unexpected errors
      alert(dati.error || "Errore durante la richiesta");
      return null;
    }

    return dati.data;
  } catch (err) {
    console.log("Errore:", err);
    alert("Impossibile connettersi al server");
  }
}

// Crea la stringa per i parametri dell'URL
function buildQuery(parametri) {
  var str = '?';
  for (var chiave in parametri) {
    var valore = parametri[chiave];
    if (valore !== '' && valore !== null && valore !== undefined) {
      str += chiave + '=' + valore + '&';
    }
  }
  // Rimuovo l'ultima & se presente
  return str.slice(0, -1);
}

// Formatta la data da YYYY-MM-DD a DD/MM/YYYY
function formatDate(dataIso) {
  if (!dataIso) return '';
  
  var parti = dataIso.split('-');
  if (parti.length !== 3) return dataIso;

  var anno = parti[0];
  var mese = parti[1];
  var giorno = parti[2];

  return giorno + '/' + mese + '/' + anno;
}

// Pulisce le stringhe per evitare problemi HTML
function pulisciTesto(testo) {
  if (!testo) return '';
  return testo.toString()
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}