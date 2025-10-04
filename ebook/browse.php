<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$author = isset($_GET['author']) ? sanitize_input($_GET['author']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");

// Get books
$where_conditions = ['b.is_active = 1'];
$params = [];

if ($category_id) {
    $where_conditions[] = 'b.category_id = ?';
    $params[] = $category_id;
}

if ($author) {
    $where_conditions[] = 'b.author LIKE ?';
    $params[] = '%' . $author . '%';
}

if ($search) {
    $where_conditions[] = '(b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM books b WHERE $where_clause";
$total_result = $db->fetch($count_sql, $params);
$total_books = $total_result['total'];

// Get books with pagination
$limit = 12;
$offset = ($page - 1) * $limit;

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

$total_pages = ceil($total_books / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books - EBook Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/browse.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2><i class="fas fa-book"></i> EBook Library</h2>
                </div>
                <div class="nav-menu">
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="browse.php" class="nav-link active">Browse</a>
                    <a href="categories.php" class="nav-link">Categories</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="nav-link">Profile</a>
                        <a href="logout.php" class="nav-link">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">Login</a>
                        <a href="register.php" class="nav-link">Register</a>
                    <?php endif; ?>
                </div>
                <div class="nav-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <main class="browse-main">
        <div class="container">
            <div class="browse-header">
                <h1>Browse Books</h1>
                <p>Discover amazing books in our collection</p>
            </div>

            <div class="browse-content">
                <!-- Mobile Filter Toggle -->
                <button class="mobile-filter-toggle" onclick="toggleMobileFilters()">
                    <i class="fas fa-filter"></i> Filters
                </button>

                <!-- Filters Sidebar -->
                <aside class="filters-sidebar" id="filters-sidebar">
                    <div class="filter-section">
                        <h3><i class="fas fa-filter"></i> Filters</h3>
                        
                        <form method="GET" class="filter-form">
                            <div class="filter-group">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search books...">
                            </div>

                            <div class="filter-group">
                                <label for="category">Category</label>
                                <select id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="author">Author</label>
                                <input type="text" id="author" name="author" 
                                       value="<?php echo htmlspecialchars($author); ?>" 
                                       placeholder="Filter by author...">
                            </div>

                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Apply Filters
                                </button>
                                <a href="browse.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </aside>

                <!-- Books Grid -->
                <div class="books-content">
                    <div class="books-header">
                        <div class="results-info">
                            <h2>
                                <?php if ($search): ?>
                                    Search Results for "<?php echo htmlspecialchars($search); ?>"
                                <?php elseif ($category_id): ?>
                                    <?php 
                                    $selected_category = array_filter($categories, function($cat) use ($category_id) {
                                        return $cat['id'] == $category_id;
                                    });
                                    $selected_category = reset($selected_category);
                                    echo htmlspecialchars($selected_category['name'] ?? 'Category');
                                    ?>
                                <?php else: ?>
                                    All Books
                                <?php endif; ?>
                            </h2>
                            <p><?php echo $total_books; ?> book<?php echo $total_books != 1 ? 's' : ''; ?> found</p>
                        </div>

                        <div class="sort-options">
                            <label for="sort">Sort by:</label>
                            <select id="sort" name="sort">
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="title">Title A-Z</option>
                                <option value="author">Author A-Z</option>
                                <option value="rating">Highest Rated</option>
                            </select>
                        </div>
                    </div>

                    <?php if (empty($books)): ?>
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <h3>No books found</h3>
                            <p>Try adjusting your search criteria or browse all books.</p>
                            <a href="browse.php" class="btn btn-primary">Browse All Books</a>
                        </div>
                    <?php else: ?>
                        <div class="books-grid">
                            <?php foreach ($books as $book): ?>
                                <div class="book-card" onclick="viewBook(<?php echo $book['id']; ?>)">
                                    <div class="book-cover">
                                        <?php if ($book['cover_image']): ?>
                                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($book['title']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-book"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-info">
                                        <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                        <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                        <p class="book-category"><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></p>
                                        
                                        <div class="book-rating">
                                            <div class="stars">
                                                <?php 
                                                $rating = floatval($book['avg_rating']);
                                                $full_stars = floor($rating);
                                                $has_half_star = ($rating - $full_stars) >= 0.5;
                                                
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $full_stars) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } elseif ($i == $full_stars + 1 && $has_half_star) {
                                                        echo '<i class="fas fa-star-half-alt"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <span class="rating-text">(<?php echo intval($book['review_count']); ?> reviews)</span>
                                        </div>

                                        <div class="book-actions">
                                            <button class="btn btn-primary" onclick="event.stopPropagation(); readBook(<?php echo $book['id']; ?>)">
                                                <i class="fas fa-book-open"></i> Read
                                            </button>
                                            <button class="btn btn-secondary" onclick="event.stopPropagation(); addToFavorites(<?php echo $book['id']; ?>)">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="pagination-btn">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>

                                <div class="pagination-numbers">
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                           class="pagination-number <?php echo $i == $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="pagination-btn">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 EBook Library. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/browse.js"></script>
</body>
</html>
