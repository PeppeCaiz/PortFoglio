<?php
require_once __DIR__ . '/../config/helpers.php';

/**
 * SESSION — dice al frontend se c'è un utente loggato.
 * Usato da js/auth.js (renderUserArea) per decidere se mostrare
 * "Accedi/Registrati" oppure "@username / Esci" nell'header.
 */

if (empty($_SESSION['utente_id'])) {
    rispondiErrore('Nessuna sessione attiva.', 401);
}

rispondiSuccesso([
    'id' => $_SESSION['utente_id'],
    'username' => $_SESSION['username']
]);
