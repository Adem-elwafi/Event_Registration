<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the raw input
$rawInput = file_get_contents('php://input');
file_put_contents('delete_participant_debug.log', date('Y-m-d H:i:s') . " - Raw input: " . $rawInput . "\n", FILE_APPEND);

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once __DIR__ . '/../config/db.php';

// Get JSON input
try {
    $input = json_decode($rawInput, true);
    
    // Log the decoded input
    file_put_contents('delete_participant_debug.log', date('Y-m-d H:i:s') . " - Decoded input: " . print_r($input, true) . "\n", FILE_APPEND);
    
    if ($input === null) {
        throw new Exception('Invalid JSON input');
    }
    
    // Try to get the participant ID from different possible parameter names
    $participantId = null;
    $possibleKeys = ['participant_id', 'id', 'participantId'];
    
    foreach ($possibleKeys as $key) {
        if (isset($input[$key]) && is_numeric($input[$key])) {
            $participantId = (int)$input[$key];
            break;
        }
    }
    
    if ($participantId === null) {
        throw new Exception('No valid participant ID found in request');
    }
    
    // Log the participant ID being used
    file_put_contents('delete_participant_debug.log', date('Y-m-d H:i:s') . " - Attempting to delete participant with ID: " . $participantId . "\n", FILE_APPEND);
    
    // First, check if the participant exists
    $checkStmt = $pdo->prepare("SELECT participant_id FROM participants WHERE participant_id = ?");
    $checkStmt->execute([$participantId]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('Participant not found with ID: ' . $participantId);
    }
    
    // Prepare and execute the delete query
    $stmt = $pdo->prepare("DELETE FROM participants WHERE participant_id = ?");
    $result = $stmt->execute([$participantId]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Participant deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Participant not found or could not be deleted']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
