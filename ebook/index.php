<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>EBook Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
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

    <main class="main-content">
        <section class="hero">
            <div class="hero-content">
                <h1>Welcome to EBook Library</h1>
                <p>Discover thousands of books at your fingertips</p>
                <div class="search-container">
                    <form action="search.php" method="GET" class="search-form">
                        <input type="text" name="q" placeholder="Search for books..." class="search-input">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section class="featured-books">
            <div class="container">
                <h2>Featured Books</h2>
                <div class="books-grid" id="featured-books">
                    <!-- Books will be loaded here via JavaScript -->
                </div>
            </div>
        </section>

        <section class="categories">
            <div class="container">
                <h2>Browse by Category</h2>
                <div class="categories-grid" id="categories-grid">
                    <!-- Categories will be loaded here via JavaScript -->
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
    <script src="assets/js/mobile.js"></script>
</body>
</html>
