<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/participants.php';

echo json_encode(getParticipants($pdo));
