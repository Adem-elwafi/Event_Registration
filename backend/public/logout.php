<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../src/auth.php';

echo json_encode(logoutAdmin());
