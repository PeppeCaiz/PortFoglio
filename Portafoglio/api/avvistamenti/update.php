<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondiErrore('Metodo non consentito.', 405);
}

$utenteId = richiedeLogin();
$conn = getConnessione();

$input = leggiJsonBody();
$id = isset($input['id']) ? (int) $input['id'] : null;
if (!$id) {
    rispondiErrore('ID mancante.');
}

$marca = trim((string) ($input['marca'] ?? ''));
$modello = trim((string) ($input['modello'] ?? ''));
$anno = $input['anno'] ?? null;
$categoriaNome = trim((string) ($input['categoria'] ?? ''));
$luogo = trim((string) ($input['luogo'] ?? ''));
$targa = trim((string) ($input['targa'] ?? ''));
$dataAvvistamento = $input['data_avvistamento'] ?? '';
$descrizione = trim((string) ($input['descrizione'] ?? ''));

if ($marca === '' || $modello === '' || $dataAvvistamento === '') {
    rispondiErrore('Marca, modello e data sono obbligatori.');
}

$stmt = $conn->prepare('SELECT utente_id FROM avvistamenti WHERE id = :id');
$stmt->execute(['id' => $id]);
$result = $stmt->fetch();

if (!$result) {
    rispondiErrore('Avvistamento non trovato.', 404);
}

if ((int) $result['utente_id'] !== (int) $utenteId) {
    rispondiErrore('Non sei autorizzato a modificare questo avvistamento.', 403);
}

$categoriaId = null;
if ($categoriaNome !== '') {
    $stmt = $conn->prepare('SELECT id FROM categorie WHERE nome = :nome');
    $stmt->execute(['nome' => $categoriaNome]);
    $categoria = $stmt->fetch();
    $categoriaId = $categoria ? (int) $categoria['id'] : null;
}

$stmt = $conn->prepare('UPDATE avvistamenti
    SET marca = :marca,
        modello = :modello,
        anno = :anno,
        descrizione = :descrizione,
        luogo = :luogo,
        targa = :targa,
        data_avvistamento = :data_avvistamento,
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
    'categoria_id' => $categoriaId,
    'id' => $id,
]);

rispondiSuccesso(['message' => 'Avvistamento aggiornato.']);