<?php
function createParticipant($pdo, $name, $email) {
    if (empty($name) || empty($email)) {
        return ['success' => false, 'message' => 'Name and email are required'];
    }

    try {
        // enforce unique email
        $stmt = $pdo->prepare("INSERT INTO participants (name, email) VALUES (?, ?)");
        $stmt->execute([$name, $email]);
        return ['success' => true, 'message' => 'Participant added successfully'];
    } catch (Exception $e) {
        if ($e->getCode() == 23000) { // 23000  duplicate email
            return ['success' => false, 'message' => 'Email already exists'];
        }
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function getParticipants($pdo) {
    $stmt = $pdo->query("SELECT * FROM participants ORDER BY participant_id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
