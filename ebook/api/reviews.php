<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();
require_login();

$db = new Database();
$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetReviews($db, $response);
            break;
        case 'POST':
            handleCreateReview($db, $response);
            break;
        case 'PUT':
            handleUpdateReview($db, $response);
            break;
        case 'DELETE':
            handleDeleteReview($db, $response);
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

function handleGetReviews($db, &$response) {
    $book_id = $_GET['book_id'] ?? null;
    $user_id = $_GET['user_id'] ?? null;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;
    
    $where_conditions = [];
    $params = [];
    
    if ($book_id) {
        $where_conditions[] = 'r.book_id = ?';
        $params[] = $book_id;
    }
    
    if ($user_id) {
        $where_conditions[] = 'r.user_id = ?';
        $params[] = $user_id;
    }
    
    $where_clause = empty($where_conditions) ? '1=1' : implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM reviews r WHERE $where_clause";
    $total_result = $db->fetch($count_sql, $params);
    $total_reviews = $total_result['total'];
    
    // Get reviews
    $sql = "SELECT r.*, u.username, u.first_name, u.last_name, b.title as book_title, b.author as book_author
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN books b ON r.book_id = b.id
            WHERE $where_clause
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $reviews = $db->fetchAll($sql, $params);
    
    $formatted_reviews = array_map(function($review) {
        return [
            'id' => intval($review['id']),
            'user_id' => intval($review['user_id']),
            'book_id' => intval($review['book_id']),
            'book_title' => $review['book_title'],
            'book_author' => $review['book_author'],
            'username' => $review['username'],
            'first_name' => $review['first_name'],
            'last_name' => $review['last_name'],
            'rating' => intval($review['rating']),
            'review_text' => $review['review_text'],
            'created_at' => $review['created_at'],
            'updated_at' => $review['updated_at']
        ];
    }, $reviews);
    
    $response['success'] = true;
    $response['data'] = $formatted_reviews;
    $response['pagination'] = [
        'page' => $page,
        'limit' => $limit,
        'total' => intval($total_reviews),
        'pages' => ceil($total_reviews / $limit)
    ];
}

function handleCreateReview($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    $book_id = intval($input['book_id'] ?? 0);
    $rating = intval($input['rating'] ?? 0);
    $review_text = sanitize_input($input['review_text'] ?? '');
    
    if (!$book_id) {
        $response['message'] = 'Book ID is required';
        http_response_code(400);
        return;
    }
    
    if ($rating < 1 || $rating > 5) {
        $response['message'] = 'Rating must be between 1 and 5';
        http_response_code(400);
        return;
    }
    
    // Verify book exists
    $book = $db->fetch("SELECT id FROM books WHERE id = ? AND is_active = 1", [$book_id]);
    if (!$book) {
        $response['message'] = 'Book not found';
        http_response_code(404);
        return;
    }
    
    // Check if user already reviewed this book
    $existing = $db->fetch("SELECT id FROM reviews WHERE user_id = ? AND book_id = ?", [$user_id, $book_id]);
    if ($existing) {
        $response['message'] = 'You have already reviewed this book';
        http_response_code(400);
        return;
    }
    
    $sql = "INSERT INTO reviews (user_id, book_id, rating, review_text) VALUES (?, ?, ?, ?)";
    $db->query($sql, [$user_id, $book_id, $rating, $review_text]);
    $review_id = $db->lastInsertId();
    
    $response['success'] = true;
    $response['data'] = ['id' => $review_id];
    $response['message'] = 'Review created successfully';
}

function handleUpdateReview($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $review_id = $_GET['id'] ?? null;
    
    if (!$review_id) {
        $response['message'] = 'Review ID is required';
        http_response_code(400);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    // Verify review belongs to user
    $review = $db->fetch("SELECT * FROM reviews WHERE id = ? AND user_id = ?", [$review_id, $user_id]);
    if (!$review) {
        $response['message'] = 'Review not found';
        http_response_code(404);
        return;
    }
    
    $update_fields = [];
    $params = [];
    
    if (isset($input['rating'])) {
        $rating = intval($input['rating']);
        if ($rating < 1 || $rating > 5) {
            $response['message'] = 'Rating must be between 1 and 5';
            http_response_code(400);
            return;
        }
        
        $update_fields[] = "rating = ?";
        $params[] = $rating;
    }
    
    if (isset($input['review_text'])) {
        $update_fields[] = "review_text = ?";
        $params[] = sanitize_input($input['review_text']);
    }
    
    if (empty($update_fields)) {
        $response['message'] = 'No fields to update';
        http_response_code(400);
        return;
    }
    
    $params[] = $review_id;
    $sql = "UPDATE reviews SET " . implode(', ', $update_fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    
    $db->query($sql, $params);
    
    $response['success'] = true;
    $response['message'] = 'Review updated successfully';
}

function handleDeleteReview($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $review_id = $_GET['id'] ?? null;
    
    if (!$review_id) {
        $response['message'] = 'Review ID is required';
        http_response_code(400);
        return;
    }
    
    // Verify review belongs to user
    $review = $db->fetch("SELECT id FROM reviews WHERE id = ? AND user_id = ?", [$review_id, $user_id]);
    if (!$review) {
        $response['message'] = 'Review not found';
        http_response_code(404);
        return;
    }
    
    $sql = "DELETE FROM reviews WHERE id = ?";
    $db->query($sql, [$review_id]);
    
    $response['success'] = true;
    $response['message'] = 'Review deleted successfully';
}
?>
