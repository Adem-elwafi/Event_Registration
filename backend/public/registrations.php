<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/registrations.php';

if (isset($_GET['event_id']) && ctype_digit($_GET['event_id'])) {
    echo json_encode(getRegistrationsByEvent($pdo, (int)$_GET['event_id']));
} else {
    echo json_encode(['success' => false, 'message' => 'Event ID required']);
}
