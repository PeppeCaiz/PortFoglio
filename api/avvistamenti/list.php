<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
 * ESERCIZIO 2
 *
 * Obiettivo: restituire l'elenco degli avvistamenti, con filtri
 * opzionali passati via query string, es:
 *   /api/avvistamenti/list.php?marca=ferrari&categoria=Supercar&luogo=milano
**/

$conn = getConnessione();
$marca = $_GET['marca'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$luogo = $_GET['luogo'] ?? '';
$username = $_GET['username'] ?? '';
$dataDA = $_GET['data_da'] ?? '';
$dataA = $_GET['data_a'] ?? '';

$query = 'SELECT a.id, a.marca, a.modello, a.anno, a.descrizione, a.luogo, a.targa, a.data_avvistamento, a.immagine, c.nome AS categoria, u.username
            FROM avvistamenti a
            JOIN utenti u ON a.utente_id = u.id
            LEFT JOIN categorie c ON a.categoria_id = c.id';

$condizioni = [];
$parametri = [];

if ($marca !== '') {
    $condizioni[] = 'a.marca LIKE :marca';
    $parametri['marca'] = '%' . $marca . '%';
}
if ($categoria !== '') {
    $condizioni[] = 'c.nome = :categoria';
    $parametri['categoria'] = $categoria;
}
if ($luogo !== '') {
    $condizioni[] = 'a.luogo LIKE :luogo';
    $parametri['luogo'] = '%' . $luogo . '%';
}
if ($username !== ''){
    $condizioni[]= 'u.username LIKE :username';
    $parametri['username']='%' . $username . '%';
}
if ($dataDA !== '') {
    $condizioni[] = 'a.data_avvistamento >= :data_da';
    $parametri['data_da'] = $dataDA;
}
if ($dataA !== '') {
    $condizioni[] = 'a.data_avvistamento <= :data_a';
    $parametri['data_a'] = $dataA;
}

if (!empty($condizioni)) {
    $query .= ' WHERE ' . implode(' AND ', $condizioni);
}

$stmt = $conn->prepare($query);
$stmt->execute($parametri);
$righe = $stmt->fetchAll();

rispondiSuccesso($righe);

