/**
 * api.js
 * Punto unico di comunicazione con il backend PHP.
 */
// URL di base utilizzato per tutte le chiamate agli endpoint PHP.
var baseUrl = './api';

// Invia una richiesta al backend e restituisce solo i dati della risposta.
async function apiRequest(endpoint, opzioni) {
  if (!opzioni) opzioni = {};

  try {
    // Configura metodo, header, corpo e cookie di sessione della richiesta.
    var risposta = await fetch(baseUrl + endpoint, {
      method: opzioni.method || 'GET',
      headers: opzioni.headers || {},
      body: opzioni.body || null,
      credentials: 'include'
    });

    // Gli endpoint restituiscono le risposte in formato JSON.
    var dati = await risposta.json();

    if (!risposta.ok || dati.success === false) {
      // Una sessione scaduta viene gestita senza mostrare un alert all'utente.
      if (risposta.status === 401 || dati.error === "Nessuna sessione attiva.") {
        console.warn("Sessione non attiva:", dati.error);
        return null;
      }

      // Per gli altri errori mostra un messaggio informativo.
      alert(dati.error || "Errore durante la richiesta");
      return null;
    }

    // In caso di successo restituisce il payload contenuto in data.
    return dati.data;
  } catch (err) {
    // Gestisce errori di rete o risposte non leggibili come JSON.
    console.log("Errore:", err);
    alert("Impossibile connettersi al server");
  }
}

// Converte un oggetto in una stringa di parametri per l'URL.
function buildQuery(parametri) {
  var str = '?';
  for (var chiave in parametri) {
    var valore = parametri[chiave];
    // Esclude i parametri privi di valore.
    if (valore !== '' && valore !== null && valore !== undefined) {
      str += chiave + '=' + valore + '&';
    }
  }
  // Rimuove l'ultimo carattere (& oppure ?) dalla stringa generata.
  return str.slice(0, -1);
}

// Converte una data ISO in formato leggibile per l'interfaccia.
function formatDate(dataIso) {
  if (!dataIso) return '';
  
  var parti = dataIso.split('-');
  if (parti.length !== 3) return dataIso;

  // La data ISO attesa è composta da anno, mese e giorno separati da '-'.
  var anno = parti[0];
  var mese = parti[1];
  var giorno = parti[2];

  return giorno + '/' + mese + '/' + anno;
}

// Escapa i caratteri speciali prima di inserire il testo nell'HTML.
function pulisciTesto(testo) {
  if (!testo) return '';
  // Previene l'interpretazione del contenuto come markup HTML.
  return testo.toString()
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}