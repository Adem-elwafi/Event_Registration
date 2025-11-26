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


function updateEvent($pdo, $id, $title, $description, $event_date) {
    if (empty($id) || empty($title) || empty($event_date)) {
        return ['success' => false, 'message' => 'ID, title, and date are required'];
    }

    try {
        $sql = "UPDATE events SET title = ?, description = ?, event_date = ? WHERE event_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $description, $event_date, $id]);
        return ['success' => true, 'message' => 'Event updated successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function deleteEvent($pdo, $id) {
    if (empty($id)) {
        return ['success' => false, 'message' => 'Event ID is required'];
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => 'Event deleted successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}