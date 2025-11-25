<?php
function registerParticipant($pdo, $participant_id, $event_id) {
    if (empty($participant_id) || empty($event_id)) {
        return ['success' => false, 'message' => 'Participant ID and Event ID are required'];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO registrations (participant_id, event_id) VALUES (?, ?)");
        $stmt->execute([$participant_id, $event_id]);
        return ['success' => true, 'message' => 'Registration successful'];
    } catch (Exception $e) {
        if ($e->getCode() == 23000) { // duplicate key
            return ['success' => false, 'message' => 'Participant already registered for this event'];
        }
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function getRegistrationsByEvent($pdo, $event_id) {
    $stmt = $pdo->prepare("
        SELECT p.name, p.email 
        FROM registrations r
        JOIN participants p ON r.participant_id = p.participant_id
        WHERE r.event_id = ?
    ");
    $stmt->execute([$event_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
