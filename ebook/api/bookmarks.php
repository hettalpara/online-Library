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
            handleGetBookmarks($db, $response);
            break;
        case 'POST':
            handleCreateBookmark($db, $response);
            break;
        case 'PUT':
            handleUpdateBookmark($db, $response);
            break;
        case 'DELETE':
            handleDeleteBookmark($db, $response);
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

function handleGetBookmarks($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $book_id = $_GET['book_id'] ?? null;
    
    if ($book_id) {
        // Get bookmarks for specific book
        $sql = "SELECT * FROM bookmarks WHERE user_id = ? AND book_id = ? ORDER BY page_number";
        $bookmarks = $db->fetchAll($sql, [$user_id, $book_id]);
    } else {
        // Get all bookmarks for user
        $sql = "SELECT bm.*, bk.title, bk.author 
                FROM bookmarks bm 
                JOIN books bk ON bm.book_id = bk.id 
                WHERE bm.user_id = ? 
                ORDER BY bm.created_at DESC";
        $bookmarks = $db->fetchAll($sql, [$user_id]);
    }
    
    $formatted_bookmarks = array_map(function($bookmark) {
        return [
            'id' => intval($bookmark['id']),
            'book_id' => intval($bookmark['book_id']),
            'title' => $bookmark['title'] ?? null,
            'author' => $bookmark['author'] ?? null,
            'page_number' => intval($bookmark['page_number']),
            'note' => $bookmark['note'],
            'created_at' => $bookmark['created_at']
        ];
    }, $bookmarks);
    
    $response['success'] = true;
    $response['data'] = $formatted_bookmarks;
}

function handleCreateBookmark($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    $book_id = intval($input['book_id'] ?? 0);
    $page_number = intval($input['page_number'] ?? 0);
    $note = sanitize_input($input['note'] ?? '');
    
    if (!$book_id || !$page_number) {
        $response['message'] = 'Book ID and page number are required';
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
    
    // Check if page number is valid
    if ($book['pages'] && $page_number > $book['pages']) {
        $response['message'] = 'Page number exceeds book length';
        http_response_code(400);
        return;
    }
    
    // Check if bookmark already exists for this page
    $existing = $db->fetch("SELECT id FROM bookmarks WHERE user_id = ? AND book_id = ? AND page_number = ?", 
                          [$user_id, $book_id, $page_number]);
    if ($existing) {
        $response['message'] = 'Bookmark already exists for this page';
        http_response_code(400);
        return;
    }
    
    $sql = "INSERT INTO bookmarks (user_id, book_id, page_number, note) VALUES (?, ?, ?, ?)";
    $db->query($sql, [$user_id, $book_id, $page_number, $note]);
    $bookmark_id = $db->lastInsertId();
    
    $response['success'] = true;
    $response['data'] = ['id' => $bookmark_id];
    $response['message'] = 'Bookmark created successfully';
}

function handleUpdateBookmark($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $bookmark_id = $_GET['id'] ?? null;
    
    if (!$bookmark_id) {
        $response['message'] = 'Bookmark ID is required';
        http_response_code(400);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    // Verify bookmark belongs to user
    $bookmark = $db->fetch("SELECT * FROM bookmarks WHERE id = ? AND user_id = ?", [$bookmark_id, $user_id]);
    if (!$bookmark) {
        $response['message'] = 'Bookmark not found';
        http_response_code(404);
        return;
    }
    
    $update_fields = [];
    $params = [];
    
    if (isset($input['page_number'])) {
        $page_number = intval($input['page_number']);
        
        // Verify book exists and page is valid
        $book = $db->fetch("SELECT pages FROM books WHERE id = ? AND is_active = 1", [$bookmark['book_id']]);
        if ($book && $book['pages'] && $page_number > $book['pages']) {
            $response['message'] = 'Page number exceeds book length';
            http_response_code(400);
            return;
        }
        
        $update_fields[] = "page_number = ?";
        $params[] = $page_number;
    }
    
    if (isset($input['note'])) {
        $update_fields[] = "note = ?";
        $params[] = sanitize_input($input['note']);
    }
    
    if (empty($update_fields)) {
        $response['message'] = 'No fields to update';
        http_response_code(400);
        return;
    }
    
    $params[] = $bookmark_id;
    $sql = "UPDATE bookmarks SET " . implode(', ', $update_fields) . " WHERE id = ?";
    
    $db->query($sql, $params);
    
    $response['success'] = true;
    $response['message'] = 'Bookmark updated successfully';
}

function handleDeleteBookmark($db, &$response) {
    $user_id = $_SESSION['user_id'];
    $bookmark_id = $_GET['id'] ?? null;
    
    if (!$bookmark_id) {
        $response['message'] = 'Bookmark ID is required';
        http_response_code(400);
        return;
    }
    
    // Verify bookmark belongs to user
    $bookmark = $db->fetch("SELECT id FROM bookmarks WHERE id = ? AND user_id = ?", [$bookmark_id, $user_id]);
    if (!$bookmark) {
        $response['message'] = 'Bookmark not found';
        http_response_code(404);
        return;
    }
    
    $sql = "DELETE FROM bookmarks WHERE id = ?";
    $db->query($sql, [$bookmark_id]);
    
    $response['success'] = true;
    $response['message'] = 'Bookmark deleted successfully';
}
?>
