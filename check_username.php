<?php

header('Content-Type: application/json');

$username = trim($_GET['username'] ?? '');

if ($username === '') {
    echo json_encode(['exists' => false]);
    exit;
}

try {

    $pdo = new PDO("mysql:host=localhost;dbname=beautystore;charset=utf8mb4", "beautyuser", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT count(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    $exists = $stmt->fetchColumn() > 0;

    echo json_encode(['exists' => $exists]);

} catch (Exception $e) {

    echo json_encode(['error' => true, 'message' => 'Database error']);
}
