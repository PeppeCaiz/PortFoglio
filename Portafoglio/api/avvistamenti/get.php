<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**

 * restituire il dettaglio di UN singolo avvistamento, dato il suo id 
**/

$conn = getConnessione();
$id = $_GET['id'] ?? null;

if ($id === null) {
    rispondiErrore('ID mancante.');
}
else{
    $stmt = $conn->prepare('SELECT a.*,
        c.nome AS categoria
        FROM avvistamenti AS a
        LEFT JOIN categorie AS c ON a.categoria_id = c.id
        WHERE a.id = :id');
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    if($result === false)
        rispondiErrore('Avvistamento non trovato', 407);
    else
        rispondiSuccesso($result);
}
?>