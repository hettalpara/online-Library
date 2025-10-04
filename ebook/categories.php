<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();

// Get categories with book counts
$categories = $db->fetchAll("
    SELECT 
        c.*,
        COUNT(b.id) as book_count
    FROM categories c
    LEFT JOIN books b ON c.id = b.category_id AND b.is_active = 1
    GROUP BY c.id
    ORDER BY c.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - EBook Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                    <a href="browse.php" class="nav-link">Browse</a>
                    <a href="categories.php" class="nav-link active">Categories</a>
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

    <main class="main-content">
        <section class="hero">
            <div class="hero-content">
                <h1>Browse by Category</h1>
                <p>Discover books organized by subject and genre</p>
            </div>
        </section>

        <section class="categories">
            <div class="container">
                <h2>All Categories</h2>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <a href="browse.php?category=<?php echo $category['id']; ?>" class="category-card">
                            <div class="category-icon">
                                <?php
                                $icons = [
                                    'Fiction' => 'fas fa-feather-alt',
                                    'Non-Fiction' => 'fas fa-book',
                                    'Science Fiction' => 'fas fa-rocket',
                                    'Mystery' => 'fas fa-search',
                                    'Romance' => 'fas fa-heart',
                                    'Fantasy' => 'fas fa-magic',
                                    'Biography' => 'fas fa-user',
                                    'History' => 'fas fa-landmark',
                                    'Science' => 'fas fa-flask',
                                    'Technology' => 'fas fa-laptop-code'
                                ];
                                $icon = $icons[$category['name']] ?? 'fas fa-book';
                                ?>
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p class="category-count"><?php echo intval($category['book_count']); ?> books</p>
                            <?php if ($category['description']): ?>
                                <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 EBook Library. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
