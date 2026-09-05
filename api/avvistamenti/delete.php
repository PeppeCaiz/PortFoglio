<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
 * ESERCIZIO 6 — l'ultimo, segue esattamente lo stesso schema di update.php.
 *
 * Obiettivo: eliminare un avvistamento, solo se appartiene all'utente loggato.
 * Il frontend (js/list.js -> deleteSpot) invia l'id in JSON via POST.
 *
 * COSA DEVI FARE:
 * 1. $utenteId = richiedeLogin();  (già scritto sotto)
 * 2. $input = leggiJsonBody(); $id = $input['id'] ?? null;
 * 3. Verifica la proprietà ESATTAMENTE come in update.php:
 *    SELECT utente_id FROM avvistamenti WHERE id = :id,
 *    poi confronta con $utenteId prima di procedere.
 * 4. (Bonus opzionale) Recupera anche la colonna "immagine" nella stessa
 *    SELECT del punto 3, e cancella il file fisico con:
 *      @unlink(__DIR__ . '/../../' . $riga['immagine']);
 *    prima di eliminare la riga — così non lasci file orfani nella cartella uploads/.
 * 5. DELETE FROM avvistamenti WHERE id = :id
 * 6. rispondiSuccesso(['message' => 'Avvistamento eliminato.']);
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

$utenteId = richiedeLogin();
$conn = getConnessione();


