<?php
function createEvent($pdo, $title, $description, $event_date) {
    // Basic validation: title and date required
    if (empty($title) || empty($event_date)) {
        return ['success' => false, 'message' => 'Title and date are required'];
    }

    try {
        $sql = "INSERT INTO events (title, description, event_date) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);//im waiting for three values 
        $stmt->execute([$title, $description, $event_date]);//here is your three values  

        return ['success' => true, 'message' => 'Event created successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
