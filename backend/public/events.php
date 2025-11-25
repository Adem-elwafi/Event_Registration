<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // allow frontend dev server to call

require_once __DIR__ . '/../config/db.php';

try {
    $stmt = $pdo->query("SELECT event_id, title, description, event_date FROM events ORDER BY event_date ASC");
    $events = $stmt->fetchAll();//put all rows in asscociative array 
    echo json_encode($events);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch events']);
}
