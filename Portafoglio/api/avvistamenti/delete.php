<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
*  eliminare un avvistamento, solo se appartiene all'utente loggato.
* Il frontend (js/list.js -> deleteSpot) invia l'id in JSON via POST.
*
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

$utenteId = richiedeLogin();
$conn = getConnessione();

$input=leggiJsonBody();
$id=$input['id'] ?? null;


$stmt= $conn->prepare('SELECT utente_id FROM avvistamenti WHERE id=:id');
$stmt->execute(['id'=>$id]);
$result = $stmt->fetch();

if ($result != $utenteId){
    rispondiErrore('Avvistamento non trovato');
}
$stmt= $conn->prepare('DELETE FROM avvistamenti WHERE id = :id');
$stmt->execute(['id' => $id]);
rispondiSuccesso('Avvistamento eliminato con successo');


