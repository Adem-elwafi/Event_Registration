<?php
session_start();

function loginAdmin($pdo, $username, $password) {
    // Debug: Log the received data
    error_log("=== Login Attempt ===");
    error_log("Username received: " . $username);
    error_log("Password received: " . $password);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debug: Check if admin was found
        error_log("Database query result: " . print_r($admin, true));
        
        if (!$admin) {
            error_log("No admin found with username: " . $username);
            return ['success' => false, 'message' => 'Invalid username'];
        }
        
        // Debug: Check password verification
        error_log("Stored hash: " . $admin['password']);
        error_log("Password being verified: " . $password);
        $verifyResult = password_verify($password, $admin['password']);
        error_log("Password verify result: " . ($verifyResult ? 'true' : 'false'));
        
        // Debug: Check if the stored hash is a valid bcrypt hash
        $hashInfo = password_get_info($admin['password']);
        error_log("Hash info: " . print_r($hashInfo, true));
        
        if ($verifyResult) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            error_log("Login successful for user: " . $username);
            return ['success' => true, 'message' => 'Login successful'];
        }
        
        error_log("Password verification failed for user: " . $username);
        return ['success' => false, 'message' => 'Invalid password'];
        
    } catch (Exception $e) {
        error_log("Error during login: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login error: ' . $e->getMessage()];
    }
}

function logoutAdmin() {
    session_start();
    session_destroy();
    return ['success' => true, 'message' => 'Logged out'];
}

function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}
