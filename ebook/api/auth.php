<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();
$db = new Database();
$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            $action = $_GET['action'] ?? '';
            switch ($action) {
                case 'login':
                    handleLogin($db, $response);
                    break;
                case 'register':
                    handleRegister($db, $response);
                    break;
                case 'logout':
                    handleLogout($response);
                    break;
                default:
                    $response['message'] = 'Invalid action';
                    http_response_code(400);
            }
            break;
        case 'GET':
            $action = $_GET['action'] ?? '';
            switch ($action) {
                case 'profile':
                    handleGetProfile($db, $response);
                    break;
                case 'check':
                    handleCheckAuth($response);
                    break;
                default:
                    $response['message'] = 'Invalid action';
                    http_response_code(400);
            }
            break;
        case 'PUT':
            handleUpdateProfile($db, $response);
            break;
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

function handleLogin($db, &$response) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    $username = sanitize_input($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $response['message'] = 'Username and password are required';
        http_response_code(400);
        return;
    }
    
    // Check if user exists
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $user = $db->fetch($sql, [$username, $username]);
    
    if (!$user || !verify_password($password, $user['password_hash'])) {
        $response['message'] = 'Invalid username or password';
        http_response_code(401);
        return;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
    
    $response['success'] = true;
    $response['data'] = [
        'id' => intval($user['id']),
        'username' => $user['username'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role']
    ];
    $response['message'] = 'Login successful';
}

function handleRegister($db, &$response) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    $username = sanitize_input($input['username'] ?? '');
    $email = sanitize_input($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $first_name = sanitize_input($input['first_name'] ?? '');
    $last_name = sanitize_input($input['last_name'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $response['message'] = 'All fields are required';
        http_response_code(400);
        return;
    }
    
    if (!validate_email($email)) {
        $response['message'] = 'Invalid email format';
        http_response_code(400);
        return;
    }
    
    if (strlen($password) < 6) {
        $response['message'] = 'Password must be at least 6 characters long';
        http_response_code(400);
        return;
    }
    
    // Check if username or email already exists
    $check_sql = "SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?";
    $result = $db->fetch($check_sql, [$username, $email]);
    
    if ($result['count'] > 0) {
        $response['message'] = 'Username or email already exists';
        http_response_code(400);
        return;
    }
    
    // Create user
    $password_hash = hash_password($password);
    $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name) 
            VALUES (?, ?, ?, ?, ?)";
    
    $db->query($sql, [$username, $email, $password_hash, $first_name, $last_name]);
    $user_id = $db->lastInsertId();
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['user_role'] = 'user';
    $_SESSION['user_email'] = $email;
    
    $response['success'] = true;
    $response['data'] = [
        'id' => intval($user_id),
        'username' => $username,
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'role' => 'user'
    ];
    $response['message'] = 'Registration successful';
}

function handleLogout(&$response) {
    session_destroy();
    $response['success'] = true;
    $response['message'] = 'Logout successful';
}

function handleGetProfile($db, &$response) {
    require_login();
    
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT id, username, email, first_name, last_name, role, created_at 
            FROM users WHERE id = ?";
    $user = $db->fetch($sql, [$user_id]);
    
    if (!$user) {
        $response['message'] = 'User not found';
        http_response_code(404);
        return;
    }
    
    // Get reading statistics
    $stats_sql = "SELECT 
                    COUNT(DISTINCT rp.book_id) as books_read,
                    COUNT(DISTINCT bm.book_id) as books_bookmarked,
                    COUNT(DISTINCT r.book_id) as books_reviewed
                  FROM users u
                  LEFT JOIN reading_progress rp ON u.id = rp.user_id
                  LEFT JOIN bookmarks bm ON u.id = bm.user_id
                  LEFT JOIN reviews r ON u.id = r.user_id
                  WHERE u.id = ?";
    
    $stats = $db->fetch($stats_sql, [$user_id]);
    
    $response['success'] = true;
    $response['data'] = [
        'user' => [
            'id' => intval($user['id']),
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'created_at' => $user['created_at']
        ],
        'stats' => [
            'books_read' => intval($stats['books_read']),
            'books_bookmarked' => intval($stats['books_bookmarked']),
            'books_reviewed' => intval($stats['books_reviewed'])
        ]
    ];
}

function handleCheckAuth(&$response) {
    if (is_logged_in()) {
        $response['success'] = true;
        $response['data'] = [
            'logged_in' => true,
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['user_role']
        ];
    } else {
        $response['success'] = true;
        $response['data'] = ['logged_in' => false];
    }
}

function handleUpdateProfile($db, &$response) {
    require_login();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $update_fields = [];
    $params = [];
    
    if (isset($input['first_name'])) {
        $update_fields[] = "first_name = ?";
        $params[] = sanitize_input($input['first_name']);
    }
    
    if (isset($input['last_name'])) {
        $update_fields[] = "last_name = ?";
        $params[] = sanitize_input($input['last_name']);
    }
    
    if (isset($input['email'])) {
        $email = sanitize_input($input['email']);
        if (!validate_email($email)) {
            $response['message'] = 'Invalid email format';
            http_response_code(400);
            return;
        }
        
        // Check if email is already taken by another user
        $check_sql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?";
        $result = $db->fetch($check_sql, [$email, $user_id]);
        
        if ($result['count'] > 0) {
            $response['message'] = 'Email already exists';
            http_response_code(400);
            return;
        }
        
        $update_fields[] = "email = ?";
        $params[] = $email;
    }
    
    if (isset($input['current_password']) && isset($input['new_password'])) {
        // Verify current password
        $user_sql = "SELECT password_hash FROM users WHERE id = ?";
        $user = $db->fetch($user_sql, [$user_id]);
        
        if (!verify_password($input['current_password'], $user['password_hash'])) {
            $response['message'] = 'Current password is incorrect';
            http_response_code(400);
            return;
        }
        
        if (strlen($input['new_password']) < 6) {
            $response['message'] = 'New password must be at least 6 characters long';
            http_response_code(400);
            return;
        }
        
        $update_fields[] = "password_hash = ?";
        $params[] = hash_password($input['new_password']);
    }
    
    if (empty($update_fields)) {
        $response['message'] = 'No fields to update';
        http_response_code(400);
        return;
    }
    
    $params[] = $user_id;
    $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
    
    $db->query($sql, $params);
    
    $response['success'] = true;
    $response['message'] = 'Profile updated successfully';
}
?>
