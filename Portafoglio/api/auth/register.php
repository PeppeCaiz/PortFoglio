<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
 * REGISTER codice per la registrazione di un nuovo utente.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

//  Leggo i dati inviati dal frontend come JSON 
$input = leggiJsonBody();
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

//  Validazione dei dati lato server 
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

//  Controllo che email o username non siano già usati.
//    ":email" e ":username" sono placeholder: conn li sostituisce in modo
//    sicuro, impedendo la SQL injection .
$stmt = $conn->prepare('SELECT id FROM utenti WHERE email = :email OR username = :username');
$stmt->execute(['email' => $email, 'username' => $username]);
if ($stmt->fetch()) {
    rispondiErrore('Email o username già registrati.');
}

//  Hash della password. 
//    password_hash() genera un hash con salt incluso.
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

//  Inserimento nel database
$stmt = $conn->prepare('INSERT INTO utenti (username, email, password) VALUES (:username, :email, :password)');
$stmt->execute([
    'username' => $username,
    'email' => $email,
    'password' => $passwordHash
]);

// Invio al frontend  i dati essenziali del nuovo utente
rispondiSuccesso([
    'id' => $conn->lastInsertId(),
    'username' => $username
]);
