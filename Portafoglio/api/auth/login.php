<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
 * LOGIN — file di riferimento, completo e commentato.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

$input = leggiJsonBody();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || $password === '') {
    rispondiErrore('Email e password sono obbligatorie.');
}

$conn = getConnessione();

$stmt = $conn->prepare('SELECT id, username, password FROM utenti WHERE email = :email');
$stmt->execute(['email' => $email]);
$utente = $stmt->fetch();


// password_verify() confronta la password inviata con l'hash salvato nel DB.
// Non serve "de-hashare" nulla: funziona sempre in questo verso.
if (!$utente || !password_verify($password, $utente['password'])) {
    // Messaggio generico: non specifichiamo se a sbagliare
    // è stata l'email o la password, per non facilitare chi tenta accessi.
    rispondiErrore('Email o password non corrette.', 401);
}

// Salvo l'id utente nella sessione PHP. Da questo momento, il cookie di
// sessione che il browser riceve identificherà l'utente in ogni richiesta
// successiva (per questo il frontend usa sempre credentials: 'include').
$_SESSION['utente_id'] = $utente['id'];
$_SESSION['username'] = $utente['username'];

rispondiSuccesso([
    'id' => $utente['id'],
    'username' => $utente['username']
]);
