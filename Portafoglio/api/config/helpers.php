<?php
/**
 * helpers.php
 * Funzioni condivise da tutti gli endpoint dell'API.
 */

session_start(); // deve essere chiamato prima di qualunque output, gestisce il cookie di sessione

header('Content-Type: application/json; charset=utf-8');


header('Access-Control-Allow-Origin:http://localhost/app/Portafoglio');
header('Access-Control-Allow-Credentials: true');

/** Invia una risposta di successo in JSON e termina lo script. */
function rispondiSuccesso($data) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

/** Invia una risposta di errore in JSON con codice HTTP e termina lo script. */
function rispondiErrore($messaggio, $codiceHttp = 400) {
    http_response_code($codiceHttp);
    echo json_encode(['success' => false, 'error' => $messaggio]);
    exit;
}

/** Legge e decodifica il body JSON di una richiesta (usato per login, register, update, delete). */
function leggiJsonBody() {
    $input = json_decode(file_get_contents('php://input'), true);
    return $input ?? [];
}

/**
 * Blocca l'esecuzione con errore 401 se l'utente non ha una sessione attiva.
 * Se invece è loggato, restituisce il suo id.
 */
function richiedeLogin() {
    if (empty($_SESSION['utente_id'])) {
        rispondiErrore('Devi effettuare il login.', 401);
    }
    return $_SESSION['utente_id'];
}
