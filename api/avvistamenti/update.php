<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
 * ESERCIZIO 5
 *
 * Obiettivo: modificare un avvistamento esistente, ma SOLO se
 * appartiene all'utente loggato. Per semplicità qui non gestiamo
 * la sostituzione della foto: si aggiornano solo i campi testuali,
 * quindi i dati arrivano in JSON (non multipart come in create.php).
 *
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

$utenteId = richiedeLogin();
$conn = getConnessione();

$input = leggiJsonBody();
$id = $input['id'] ?? null;
if (!$id) {
    rispondiErrore('ID mancante.');
}

$stmt= $conn->prepare('SELECT id FROM avvistamenti WHERE id = :id');
$stmt->execute(['id' => $id]);
$result = $stmt->fetch();
if(!$result){
    rispondiErrore('Avvistamento non trovato');
}
if ($result['utente_id'] != $utenteId) {
    rispondiErrore('Non sei autorizzato a modificare questo avvistamento.', 403);
}

$marca = trim($_POST['marca'] ?? '');
$modello = trim($_POST['modello'] ?? '');
$anno = $_POST['anno'] ?? null;
$categoriaNome = trim($_POST['categoria'] ?? '');
$luogo = trim($_POST['luogo'] ?? '');
$targa = trim($_POST['targa'] ?? '');
$dataAvvistamento = $_POST['data_avvistamento'] ?? '';
$descrizione = trim($_POST['descrizione'] ?? '');

if (empty($_FILES['immagine']) || $_FILES['immagine']['error'] !== UPLOAD_ERR_OK) {
    rispondiErrore('Devi caricare una foto.');
}

$file = $_FILES['immagine'];
$estensioniConsentite = ['jpg', 'jpeg', 'png', 'webp'];
$estensione = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($estensione, $estensioniConsentite)) {
    rispondiErrore('Formato immagine non supportato. Usa jpg, png o webp.');
}
if ($file['size'] > 5 * 1024 * 1024) { // limite 5 MB
    rispondiErrore('L\'immagine non può superare 5 MB.');
}

// Nome file univoco, per evitare che due upload si sovrascrivano
$nomeFile = uniqid('spot_', true) . '.' . $estensione;
$cartellaUpload = __DIR__ . '/../../uploads/';
if (!is_dir($cartellaUpload)) {
    mkdir($cartellaUpload, 0755, true);
}
move_uploaded_file($file['tmp_name'], $cartellaUpload . $nomeFile);

// Percorso relativo salvato nel DB: il frontend lo userà come <img src="...">
$percorsoImmagine = 'uploads/' . $nomeFile;
if (empty($_FILES['immagine']) || $_FILES['immagine']['error'] !== UPLOAD_ERR_OK) {
    rispondiErrore('Devi caricare una foto.');
}

$file = $_FILES['immagine'];
$estensioniConsentite = ['jpg', 'jpeg', 'png', 'webp'];
$estensione = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($estensione, $estensioniConsentite)) {
    rispondiErrore('Formato immagine non supportato. Usa jpg, png o webp.');
}
if ($file['size'] > 5 * 1024 * 1024) { // limite 5 MB
    rispondiErrore('L\'immagine non può superare 5 MB.');
}

// Nome file univoco, per evitare che due upload si sovrascrivano
$nomeFile = uniqid('spot_', true) . '.' . $estensione;
$cartellaUpload = __DIR__ . '/../../uploads/';
if (!is_dir($cartellaUpload)) {
    mkdir($cartellaUpload, 0755, true);
}
move_uploaded_file($file['tmp_name'], $cartellaUpload . $nomeFile);

// Percorso relativo salvato nel DB: il frontend lo userà come <img src="...">
$percorsoImmagine = 'uploads/' . $nomeFile;



$stmt = $conn->prepare('UPDATE avvistamenti
    SET marca = :marca, modello = :modello, anno = :anno,
        descrizione = :descrizione, luogo = :luogo,
        data_avvistamento = :data_avvistamento, categoria_id = :categoria_id
    WHERE id = :id');

$stmt->execute([
    'utente_id' => $utenteId,
    'marca' => $marca,
    'modello' => $modello,
    'anno' => $anno,
    'descrizione' => $descrizione,
    'luogo' => $luogo,
    'targa'=> $targa,
    'data_avvistamento' => $dataAvvistamento,
    'immagine' => $percorsoImmagine,
    'categoria_id' => $categoriaId,
]);

rispondiSuccesso(['message' => 'Avvistamento aggiornato.']);