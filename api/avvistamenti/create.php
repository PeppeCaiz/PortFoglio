<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
 * ESERCIZIO 4 — il più articolato, perché gestisce anche l'upload di un file.
 *
 * Riceve i dati come multipart/form-data (non JSON!) perché include
 * un'immagine — guarda js/form.js: usa FormData, non JSON.stringify.
 * Per questo qui i campi si leggono da $_POST e $_FILES, non da leggiJsonBody().
 *
 * La parte di upload qui sotto è già completa e commentata: studiala,
 * perché è un meccanismo diverso da tutto ciò che hai visto finora.
 * Il TUO compito è la sezione finale: risolvere la categoria e fare la INSERT.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

$utenteId = richiedeLogin(); // solo un utente loggato può creare un avvistamento

// --- Campi testuali (con multipart/form-data arrivano in $_POST) ---
$marca = trim($_POST['marca'] ?? '');
$modello = trim($_POST['modello'] ?? '');
$anno = $_POST['anno'] ?? null;
$categoriaNome = trim($_POST['categoria'] ?? '');
$luogo = trim($_POST['luogo'] ?? '');
$targa = trim($_POST['targa'] ?? '');
$dataAvvistamento = $_POST['data_avvistamento'] ?? '';
$descrizione = trim($_POST['descrizione'] ?? '');

if ($marca === '' || $modello === '' || $dataAvvistamento === '') {
    rispondiErrore('Marca, modello e data sono obbligatori.');
}

// --- Upload immagine (sezione completa, studiala) ---
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

$conn = getConnessione();


$categoriaId = null;
if ($categoriaNome !== '') {
    $stmt = $conn->prepare('SELECT id FROM categorie WHERE nome = :nome');
    $stmt->execute(['nome' => $categoriaNome]);
    $riga = $stmt->fetch();
    $categoriaId = $riga ? (int) $riga['id'] : null;
}

$stmt = $conn->prepare('INSERT INTO avvistamenti
    (utente_id, marca, modello, anno, descrizione, luogo, targa , data_avvistamento, immagine, categoria_id)
    VALUES (:utente_id, :marca, :modello, :anno, :descrizione, :luogo, :data_avvistamento, :immagine, :categoria_id)');
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

rispondiSuccesso(['id' => $conn->lastInsertId()]);
