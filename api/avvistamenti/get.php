<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
 * ESERCIZIO 3
 *
 * Obiettivo: restituire il dettaglio di UN singolo avvistamento,
 * dato il suo id in query string: /api/avvistamenti/get.php?id=5
**/

$conn = getConnessione();
$id = $_GET['id'] ?? null;

if ($id === null) {
    rispondiErrore('ID mancante.');
}
else{
    $stmt = $conn->prepare('SELECT * FROM avvistamenti as a WHERE a.id==:id');
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    if($result === false)
        rispondiErrore('Avvistamento non trovato', 407);
    else
        rispondiSuccesso($result);
}
?>