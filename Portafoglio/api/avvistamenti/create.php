<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**

 * Riceve i dati come multipart/form-data  perché include
 * un'immagine, usa FormData, non JSON.stringify.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

$utenteId = richiedeLogin(); // solo un utente loggato può creare un avvistamento

//  Campi testuali (con multipart/form-data arrivano in $_POST) 
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

$file = $_FILES['immagine'] ?? null;
$ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

// Controlli di validazione (early exit)
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    rispondiErrore('Devi caricare una foto.');
}
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
    rispondiErrore('Formato non supportato. Usa jpg, png o webp.');
}
if ($file['size'] > 5 * 1024 * 1024) {
    rispondiErrore('L\'immagine non può superare 5 MB.');
}

// Salvataggio
$nomeFile = bin2hex(random_bytes(8)) . ".$ext"; // Più sicuro di uniqid()
$cartella = __DIR__ . '/../../imgs/';

if (!is_dir($cartella)) {
    mkdir($cartella, 0755, true);
}

move_uploaded_file($file['tmp_name'], $cartella . $nomeFile);
$percorsoImmagine = "imgs/$nomeFile";

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
    VALUES (:utente_id, :marca, :modello, :anno, :descrizione, :luogo, :targa, :data_avvistamento, :immagine, :categoria_id)');
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
