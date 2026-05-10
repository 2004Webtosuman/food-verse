<?php
// includes/auth.php
require_once 'db.php';

/**
 * Register a new user
 */
/**
 * Register a new user
 */
function register_user($pdo, $full_name, $email, $password, $role = 'user', $province = null, $district = null, $municipality = null) {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $allowed_roles = ['user', 'delivery'];
    if (!in_array($role, $allowed_roles)) $role = 'user';
    
    $address = ($province && $district && $municipality) ? "$municipality, $district, $province" : null;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, province, district, municipality, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$full_name, $email, $password_hash, $role, $province, $district, $municipality, $address]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Login a user
 */
function login_user($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Check if user is suspended
        if (isset($user['status']) && $user['status'] === 'suspended') {
            $_SESSION['flash_message'] = "Your account has been suspended. Please contact support.";
            return false;
        }

        // 2FA Injection
        if (!empty($user['is_2fa_enabled'])) {
            $_SESSION['pending_2fa_user_id'] = $user['id'];
            $_SESSION['pending_2fa_role'] = $user['role'];
            $_SESSION['pending_2fa_email'] = $user['email'];
            return 'requires_2fa';
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
        return true;
    }
    return false;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is a delivery boy
 */
function is_delivery() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'delivery';
}

/**
 * Logout user
 */
function logout_user() {
    session_unset();
    session_destroy();
}
?>
