<?php
/**
 * Functions to record user account activities
 */

/**
 * Record a user account activity
 * 
 * @param int $user_id User ID
 * @param string $activity_type Type of activity (login, logout, etc.)
 * @param string $additional_info Optional additional information
 * @return bool True if successful, false otherwise
 */
function recordUserActivity($conn, $user_id, $activity_type, $additional_info = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO user_account_activities 
                           (user_id, activity_type, ip_address, user_agent, additional_info) 
                           VALUES (?, ?, ?, ?, ?)");
    
    $stmt->bind_param("issss", $user_id, $activity_type, $ip_address, $user_agent, $additional_info);
    return $stmt->execute();
}

/**
 * Record user login
 */
function recordUserLogin($conn, $user_id) {
    return recordUserActivity($conn, $user_id, 'login');
}

/**
 * Record user logout
 */
function recordUserLogout($conn, $user_id) {
    return recordUserActivity($conn, $user_id, 'logout');
}

/**
 * Record failed login attempt
 */
function recordFailedLogin($conn, $user_id) {
    return recordUserActivity($conn, $user_id, 'failed_login');
}

/**
 * Record password change
 */
function recordPasswordChange($conn, $user_id) {
    return recordUserActivity($conn, $user_id, 'password_change');
}

/**
 * Record profile update
 * 
 * @param int $user_id User ID
 * @param array $updated_fields Array of field names that were updated
 */
function recordProfileUpdate($conn, $user_id, $updated_fields = []) {
    $fields_info = !empty($updated_fields) ? implode(', ', $updated_fields) : null;
    return recordUserActivity($conn, $user_id, 'profile_update', $fields_info);
}

/**
 * Record account creation
 */
function recordAccountCreation($conn, $user_id) {
    return recordUserActivity($conn, $user_id, 'account_created');
}

/**
 * Record password reset request
 */
function recordPasswordResetRequest($conn, $user_id) {
    return recordUserActivity($conn, $user_id, 'password_reset_requested');
}

/**
 * Record password reset completion
 */
function recordPasswordResetCompletion($conn, $user_id) {
    return recordUserActivity($conn, $user_id, 'password_reset_completed');
}