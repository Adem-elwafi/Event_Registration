<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // allow frontend calls
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/events.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true); //php://input When the frontend sends a POST request with data, PHP puts that data here

    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $event_date = $data['date'] ?? '';

    $result = createEvent($pdo, $title, $description, $event_date);
    echo json_encode($result);//sends that back to the front end 
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
