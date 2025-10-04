<?php
// Utility functions for the EBook Library

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: index.php');
        exit();
    }
}

function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function get_book_rating($book_id, $db) {
    $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
            FROM reviews 
            WHERE book_id = ? AND rating IS NOT NULL";
    $result = $db->fetch($sql, [$book_id]);
    
    return [
        'average' => $result['avg_rating'] ? round($result['avg_rating'], 1) : 0,
        'total' => $result['total_reviews']
    ];
}

function render_stars($rating) {
    $stars = '';
    $full_stars = floor($rating);
    $has_half_star = ($rating - $full_stars) >= 0.5;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full_stars) {
            $stars .= '<i class="fas fa-star"></i>';
        } elseif ($i == $full_stars + 1 && $has_half_star) {
            $stars .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $stars .= '<i class="far fa-star"></i>';
        }
    }
    
    return $stars;
}

function get_user_reading_progress($user_id, $book_id, $db) {
    $sql = "SELECT * FROM reading_progress WHERE user_id = ? AND book_id = ?";
    return $db->fetch($sql, [$user_id, $book_id]);
}

function update_reading_progress($user_id, $book_id, $current_page, $total_pages, $db) {
    $progress_percentage = ($current_page / $total_pages) * 100;
    
    $sql = "INSERT INTO reading_progress (user_id, book_id, current_page, total_pages, progress_percentage) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            current_page = VALUES(current_page),
            total_pages = VALUES(total_pages),
            progress_percentage = VALUES(progress_percentage),
            last_read_at = CURRENT_TIMESTAMP";
    
    return $db->query($sql, [$user_id, $book_id, $current_page, $total_pages, $progress_percentage]);
}

function log_activity($user_id, $action, $details = '', $db) {
    // This could be expanded to include an activity log table
    error_log("User $user_id performed action: $action - $details");
}
?>
