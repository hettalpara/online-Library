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
            handleGetCategories($db, $response);
            break;
        case 'POST':
            handleCreateCategory($db, $response);
            break;
        case 'PUT':
            handleUpdateCategory($db, $response);
            break;
        case 'DELETE':
            handleDeleteCategory($db, $response);
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

function handleGetCategories($db, &$response) {
    $sql = "SELECT 
                c.*,
                COUNT(b.id) as book_count
            FROM categories c
            LEFT JOIN books b ON c.id = b.category_id AND b.is_active = 1
            GROUP BY c.id
            ORDER BY c.name";
    
    $categories = $db->fetchAll($sql);
    
    $formatted_categories = array_map(function($category) {
        return [
            'id' => intval($category['id']),
            'name' => $category['name'],
            'description' => $category['description'],
            'book_count' => intval($category['book_count']),
            'created_at' => $category['created_at']
        ];
    }, $categories);
    
    $response['success'] = true;
    $response['data'] = $formatted_categories;
}

function handleCreateCategory($db, &$response) {
    require_admin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        return;
    }
    
    if (!isset($input['name']) || empty($input['name'])) {
        $response['message'] = 'Category name is required';
        http_response_code(400);
        return;
    }
    
    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $params = [
        sanitize_input($input['name']),
        sanitize_input($input['description'] ?? '')
    ];
    
    $db->query($sql, $params);
    $category_id = $db->lastInsertId();
    
    $response['success'] = true;
    $response['data'] = ['id' => $category_id];
    $response['message'] = 'Category created successfully';
}

function handleUpdateCategory($db, &$response) {
    require_admin();
    
    $category_id = $_GET['id'] ?? null;
    if (!$category_id || !is_numeric($category_id)) {
        $response['message'] = 'Invalid category ID';
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
    
    if (isset($input['name'])) {
        $update_fields[] = "name = ?";
        $params[] = sanitize_input($input['name']);
    }
    
    if (isset($input['description'])) {
        $update_fields[] = "description = ?";
        $params[] = sanitize_input($input['description']);
    }
    
    if (empty($update_fields)) {
        $response['message'] = 'No fields to update';
        http_response_code(400);
        return;
    }
    
    $params[] = $category_id;
    $sql = "UPDATE categories SET " . implode(', ', $update_fields) . " WHERE id = ?";
    
    $db->query($sql, $params);
    
    $response['success'] = true;
    $response['message'] = 'Category updated successfully';
}

function handleDeleteCategory($db, &$response) {
    require_admin();
    
    $category_id = $_GET['id'] ?? null;
    if (!$category_id || !is_numeric($category_id)) {
        $response['message'] = 'Invalid category ID';
        http_response_code(400);
        return;
    }
    
    // Check if category has books
    $check_sql = "SELECT COUNT(*) as count FROM books WHERE category_id = ? AND is_active = 1";
    $result = $db->fetch($check_sql, [$category_id]);
    
    if ($result['count'] > 0) {
        $response['message'] = 'Cannot delete category with active books';
        http_response_code(400);
        return;
    }
    
    $sql = "DELETE FROM categories WHERE id = ?";
    $db->query($sql, [$category_id]);
    
    $response['success'] = true;
    $response['message'] = 'Category deleted successfully';
}
?>
