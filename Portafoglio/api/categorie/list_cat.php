<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

$conn = getConnessione();

$stmt = $conn->prepare('SELECT nome FROM categorie ORDER BY nome');
$stmt->execute();

$categoria = $stmt->fetchAll();


rispondiSuccesso($categoria);

