<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$tarih = $_GET['tarih'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE reservation_date = ?
    ORDER BY person_count DESC, child_count DESC, reservation_time
");
$stmt->execute([$tarih]);

echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
