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
            handleGetProgress($db, $response);
            break;
        case 'POST':
            handleUpdateProgress($db, $response);
            break;
        case 'DELETE':
            handleDeleteProgress($db, $response);
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

function handleGetProgress($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $book_id = $_GET['book_id'] ?? null;
    
    if ($book_id) {
        // Get progress for specific book
        $sql = "SELECT * FROM reading_progress WHERE user_id = ? AND book_id = ?";
        $progress = $db->fetch($sql, [$user_id, $book_id]);
        
        if ($progress) {
            $response['success'] = true;
            $response['data'] = [
                'book_id' => intval($progress['book_id']),
                'current_page' => intval($progress['current_page']),
                'total_pages' => intval($progress['total_pages']),
                'progress_percentage' => floatval($progress['progress_percentage']),
                'last_read_at' => $progress['last_read_at']
            ];
        } else {
            $response['success'] = true;
            $response['data'] = null;
        }
    } else {
        // Get all reading progress for user
        $sql = "SELECT rp.*, b.title, b.author, b.cover_image 
                FROM reading_progress rp 
                JOIN books b ON rp.book_id = b.id 
                WHERE rp.user_id = ? 
                ORDER BY rp.last_read_at DESC";
        
        $progress_list = $db->fetchAll($sql, [$user_id]);
        
        $formatted_progress = array_map(function($item) {
            return [
                'book_id' => intval($item['book_id']),
                'title' => $item['title'],
                'author' => $item['author'],
                'cover_image' => $item['cover_image'],
                'current_page' => intval($item['current_page']),
                'total_pages' => intval($item['total_pages']),
                'progress_percentage' => floatval($item['progress_percentage']),
                'last_read_at' => $item['last_read_at']
            ];
        }, $progress_list);
        
        $response['success'] = true;
        $response['data'] = $formatted_progress;
    }
}

function handleUpdateProgress($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    $book_id = intval($input['book_id'] ?? 0);
    $current_page = intval($input['current_page'] ?? 1);
    $total_pages = intval($input['total_pages'] ?? 0);
    $progress_percentage = floatval($input['progress_percentage'] ?? 0);
    
    if (!$book_id) {
        $response['message'] = 'Book ID is required';
        http_response_code(400);
        return;
    }
    
    // Verify book exists
    $book = $db->fetch("SELECT id, pages FROM books WHERE id = ? AND is_active = 1", [$book_id]);
    if (!$book) {
        $response['message'] = 'Book not found';
        http_response_code(404);
        return;
    }
    
    // Use book's page count if not provided
    if (!$total_pages && $book['pages']) {
        $total_pages = intval($book['pages']);
    }
    
    // Calculate progress percentage if not provided
    if (!$progress_percentage && $total_pages > 0) {
        $progress_percentage = ($current_page / $total_pages) * 100;
    }
    
    $sql = "INSERT INTO reading_progress (user_id, book_id, current_page, total_pages, progress_percentage) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            current_page = VALUES(current_page),
            total_pages = VALUES(total_pages),
            progress_percentage = VALUES(progress_percentage),
            last_read_at = CURRENT_TIMESTAMP";
    
    $db->query($sql, [$user_id, $book_id, $current_page, $total_pages, $progress_percentage]);
    
    $response['success'] = true;
    $response['data'] = [
        'book_id' => $book_id,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'progress_percentage' => $progress_percentage
    ];
    $response['message'] = 'Reading progress updated successfully';
}

function handleDeleteProgress($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $book_id = $_GET['book_id'] ?? null;
    
    if (!$book_id) {
        $response['message'] = 'Book ID is required';
        http_response_code(400);
        return;
    }
    
    $sql = "DELETE FROM reading_progress WHERE user_id = ? AND book_id = ?";
    $db->query($sql, [$user_id, $book_id]);
    
    $response['success'] = true;
    $response['message'] = 'Reading progress deleted successfully';
}
?>
