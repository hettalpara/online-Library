<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = new Database();
$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetBooks($db, $response);
            break;
        case 'POST':
            handleCreateBook($db, $response);
            break;
        case 'PUT':
            handleUpdateBook($db, $response);
            break;
        case 'DELETE':
            handleDeleteBook($db, $response);
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

function handleGetBooks($db, &$response) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 12;
    $offset = ($page - 1) * $limit;
    
    $where_conditions = ['b.is_active = 1'];
    $params = [];
    
    // Featured books filter
    if (isset($_GET['featured']) && $_GET['featured'] == '1') {
        $where_conditions[] = 'b.is_featured = 1';
    }
    
    // Category filter
    if (isset($_GET['category']) && is_numeric($_GET['category'])) {
        $where_conditions[] = 'b.category_id = ?';
        $params[] = $_GET['category'];
    }
    
    // Author filter
    if (isset($_GET['author']) && !empty($_GET['author'])) {
        $where_conditions[] = 'b.author LIKE ?';
        $params[] = '%' . $_GET['author'] . '%';
    }
    
    // Search query
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $where_conditions[] = '(b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)';
        $search_term = '%' . $_GET['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM books b WHERE $where_clause";
    $total_result = $db->fetch($count_sql, $params);
    $total_books = $total_result['total'];
    
    // Get books with ratings
    $sql = "SELECT 
                b.*,
                c.name as category_name,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN reviews r ON b.id = r.book_id
            WHERE $where_clause
            GROUP BY b.id
            ORDER BY b.is_featured DESC, b.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $books = $db->fetchAll($sql, $params);
    
    // Format the response
    $formatted_books = array_map(function($book) {
        return [
            'id' => intval($book['id']),
            'title' => $book['title'],
            'author' => $book['author'],
            'description' => $book['description'],
            'isbn' => $book['isbn'],
            'category_id' => intval($book['category_id']),
            'category_name' => $book['category_name'],
            'cover_image' => $book['cover_image'],
            'file_path' => $book['file_path'],
            'file_size' => intval($book['file_size']),
            'file_type' => $book['file_type'],
            'pages' => intval($book['pages']),
            'language' => $book['language'],
            'published_date' => $book['published_date'],
            'is_featured' => boolval($book['is_featured']),
            'created_at' => $book['created_at'],
            'rating' => [
                'average' => round(floatval($book['avg_rating']), 1),
                'total' => intval($book['review_count'])
            ]
        ];
    }, $books);
    
    $response['success'] = true;
    $response['data'] = $formatted_books;
    $response['pagination'] = [
        'page' => $page,
        'limit' => $limit,
        'total' => intval($total_books),
        'pages' => ceil($total_books / $limit)
    ];
}

function handleCreateBook($db, &$response) {
    require_admin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    $required_fields = ['title', 'author', 'file_path'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            $response['message'] = "Missing required field: $field";
            http_response_code(400);
            return;
        }
    }
    
    $sql = "INSERT INTO books (title, author, description, isbn, category_id, cover_image, 
            file_path, file_size, file_type, pages, language, published_date, is_featured) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        sanitize_input($input['title']),
        sanitize_input($input['author']),
        sanitize_input($input['description'] ?? ''),
        sanitize_input($input['isbn'] ?? ''),
        !empty($input['category_id']) ? intval($input['category_id']) : null,
        sanitize_input($input['cover_image'] ?? ''),
        sanitize_input($input['file_path']),
        intval($input['file_size'] ?? 0),
        sanitize_input($input['file_type'] ?? ''),
        intval($input['pages'] ?? 0),
        sanitize_input($input['language'] ?? 'en'),
        !empty($input['published_date']) ? $input['published_date'] : null,
        boolval($input['is_featured'] ?? false)
    ];
    
    $db->query($sql, $params);
    $book_id = $db->lastInsertId();
    
    $response['success'] = true;
    $response['data'] = ['id' => $book_id];
    $response['message'] = 'Book created successfully';
}

function handleUpdateBook($db, &$response) {
    require_admin();
    
    $book_id = $_GET['id'] ?? null;
    if (!$book_id || !is_numeric($book_id)) {
        $response['message'] = 'Invalid book ID';
        http_response_code(400);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    $update_fields = [];
    $params = [];
    
    $allowed_fields = ['title', 'author', 'description', 'isbn', 'category_id', 
                      'cover_image', 'file_path', 'file_size', 'file_type', 
                      'pages', 'language', 'published_date', 'is_featured', 'is_active'];
    
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $update_fields[] = "$field = ?";
            if ($field === 'category_id' && empty($input[$field])) {
                $params[] = null;
            } else {
                $params[] = sanitize_input($input[$field]);
            }
        }
    }
    
    if (empty($update_fields)) {
        $response['message'] = 'No fields to update';
        http_response_code(400);
        return;
    }
    
    $params[] = $book_id;
    $sql = "UPDATE books SET " . implode(', ', $update_fields) . " WHERE id = ?";
    
    $db->query($sql, $params);
    
    $response['success'] = true;
    $response['message'] = 'Book updated successfully';
}

function handleDeleteBook($db, &$response) {
    require_admin();
    
    $book_id = $_GET['id'] ?? null;
    if (!$book_id || !is_numeric($book_id)) {
        $response['message'] = 'Invalid book ID';
        http_response_code(400);
        return;
    }
    
    // Soft delete by setting is_active to false
    $sql = "UPDATE books SET is_active = 0 WHERE id = ?";
    $db->query($sql, [$book_id]);
    
    $response['success'] = true;
    $response['message'] = 'Book deleted successfully';
}
?>
