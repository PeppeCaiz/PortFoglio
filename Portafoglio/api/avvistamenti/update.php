<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// L'endpoint accetta solo richieste POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

// Verifica che l'utente sia autenticato e apre la connessione al database.
$utenteId = richiedeLogin();
$conn = getConnessione();

// Recupera e valida l'identificativo dell'avvistamento da modificare.
$id = isset($_POST['id']) ? (int) $_POST['id'] : null;
if (!$id) {
    rispondiErrore('ID mancante.');
}

// Legge e normalizza i dati inviati dal form.
$marca = trim((string) ($_POST['marca'] ?? ''));
$modello = trim((string) ($_POST['modello'] ?? ''));
$anno = $_POST['anno'] ?? null;
$categoriaNome = trim((string) ($_POST['categoria'] ?? ''));
$luogo = trim((string) ($_POST['luogo'] ?? ''));
$targa = trim((string) ($_POST['targa'] ?? ''));
$dataAvvistamento = $_POST['data_avvistamento'] ?? '';
$descrizione = trim((string) ($_POST['descrizione'] ?? ''));

if ($marca === '' || $modello === '' || $dataAvvistamento === '') {
    rispondiErrore('Marca, modello e data sono obbligatori.');
}

// Recupera l'avvistamento e l'immagine attuale per controllare che esista.
$stmt = $conn->prepare('SELECT utente_id, immagine FROM avvistamenti WHERE id = :id');
$stmt->execute(['id' => $id]);
$result = $stmt->fetch();

if (!$result) {
    rispondiErrore('Avvistamento non trovato.', 404);
}

if ((int) $result['utente_id'] !== (int) $utenteId) {
    rispondiErrore('Non sei autorizzato a modificare questo avvistamento.', 403);
}

// Controlla il nuovo file immagine e ne ricava l'estensione.
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

// Crea un nome univoco e salva l'immagine nella cartella pubblica.
$nomeFile = bin2hex(random_bytes(8)) . ".$ext";
$cartella = __DIR__ . '/../../imgs/';

if (!is_dir($cartella)) {
    mkdir($cartella, 0755, true);
}

move_uploaded_file($file['tmp_name'], $cartella . $nomeFile);
$percorsoImmagine = "imgs/$nomeFile";

// Converte il nome della categoria nel relativo identificativo, se presente.
$categoriaId = null;
if ($categoriaNome !== '') {
    $stmt = $conn->prepare('SELECT id FROM categorie WHERE nome = :nome');
    $stmt->execute(['nome' => $categoriaNome]);
    $categoria = $stmt->fetch();
    $categoriaId = $categoria ? (int) $categoria['id'] : null;
}

// Aggiorna tutti i dati dell'avvistamento usando una query parametrizzata.
$stmt = $conn->prepare('UPDATE avvistamenti
    SET marca = :marca,
        modello = :modello,
        anno = :anno,
        descrizione = :descrizione,
        luogo = :luogo,
        targa = :targa,
        data_avvistamento = :data_avvistamento,
        immagine = :immagine,
        categoria_id = :categoria_id
    WHERE id = :id');

$stmt->execute([
    'marca' => $marca,
    'modello' => $modello,
    'anno' => $anno,
    'descrizione' => $descrizione,
    'luogo' => $luogo,
    'targa' => $targa,
    'data_avvistamento' => $dataAvvistamento,
    'immagine' => $percorsoImmagine,
    'categoria_id' => $categoriaId,
    'id' => $id,
]);

// Dopo l'aggiornamento elimina dal disco la vecchia immagine non più utilizzata.
$old_img = $result['immagine'] ?? '';

if ($old_img && $old_img !== $percorsoImmagine) {
    $path = dirname(__DIR__, 2) . '/' . ltrim($old_img, '/\\');
    if (is_file($path)) 
        unlink($path);
}

rispondiSuccesso(['message' => 'Avvistamento aggiornato.']);