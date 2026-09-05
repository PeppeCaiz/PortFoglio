<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
 * REGISTER — file di riferimento, completo e commentato.
 *
 * Studialo con calma: il pattern "leggi input -> valida -> query
 * preparata -> rispondi" è quello che riuserai in quasi tutti gli
 * altri endpoint che completerai tu (in avvistamenti/ e categorie/).
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

// 1. Leggo i dati inviati dal frontend come JSON (vedi js/auth.js -> initRegisterForm)
$input = leggiJsonBody();
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// 2. Validazione base lato server (quella lato client si può sempre aggirare!)
if ($username === '' || $email === '' || $password === '') {
    rispondiErrore('Tutti i campi sono obbligatori.');
}
if (strlen($password) < 8) {
    rispondiErrore('La password deve avere almeno 8 caratteri.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    rispondiErrore('Email non valida.');
}

$conn = getConnessione();

// 3. Controllo che email o username non siano già usati.
//    ":email" e ":username" sono placeholder: conn li sostituisce in modo
//    sicuro, impedendo la SQL injection (non concatenare mai $email a mano nella query!).
$stmt = $conn->prepare('SELECT id FROM utenti WHERE email = :email OR username = :username');
$stmt->execute(['email' => $email, 'username' => $username]);
if ($stmt->fetch()) {
    rispondiErrore('Email o username già registrati.');
}

// 4. Hash della password. NON salvare mai la password in chiaro nel database.
//    password_hash() genera un hash con salt incluso, sicuro di suo.
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// 5. Inserimento nel database
$stmt = $conn->prepare('INSERT INTO utenti (username, email, password) VALUES (:username, :email, :password)');
$stmt->execute([
    'username' => $username,
    'email' => $email,
    'password' => $passwordHash
]);

// 6. Rispondo al frontend con i dati essenziali del nuovo utente
rispondiSuccesso([
    'id' => $conn->lastInsertId(),
    'username' => $username
]);
