<?php
/**
 * database.php
 * Crea e restituisce una connessione PDO al database MySQL.
 *
 * COSA DEVI FARE TU:
 * Se usi XAMPP/MAMP in locale, questi valori di default vanno quasi
 * sempre bene così come sono (utente "root", password vuota).
 * Se il tuo ambiente è diverso, modificali qui sotto.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'carspot';
$DB_USER = 'root';
$DB_PASS = '';

/** Restituisce una connessione PDO pronta all'uso, con gli errori attivati. */
function getConnessione() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;

    try {
        $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
        return new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // le query errate lanciano eccezioni invece di fallire in silenzio
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // fetch() / fetchAll() restituiscono array associativi
        ]);
    } catch (PDOException $e) {
        // Se la connessione fallisce, rispondiamo comunque in JSON
        // (non con la pagina di errore HTML di default di PHP).
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Connessione al database fallita: ' . $e->getMessage()]);
        exit;
    }
}
