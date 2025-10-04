<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$book_id) {
    header('Location: index.php');
    exit();
}

// Get book details
$book = $db->fetch("
    SELECT b.*, c.name as category_name 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.id = ? AND b.is_active = 1
", [$book_id]);

if (!$book) {
    header('Location: index.php');
    exit();
}

// Get book rating
$rating = get_book_rating($book_id, $db);

// Get user's reading progress if logged in
$progress = null;
if (isset($_SESSION['user_id'])) {
    $progress = get_user_reading_progress($_SESSION['user_id'], $book_id, $db);
}

// Get recent reviews
$reviews = $db->fetchAll("
    SELECT r.*, u.username, u.first_name, u.last_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.book_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 5
", [$book_id]);

// Get related books
$related_books = $db->fetchAll("
    SELECT b.*, c.name as category_name,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(r.id) as review_count
    FROM books b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN reviews r ON b.id = r.book_id
    WHERE b.category_id = ? AND b.id != ? AND b.is_active = 1
    GROUP BY b.id
    ORDER BY b.is_featured DESC, b.created_at DESC
    LIMIT 4
", [$book['category_id'], $book_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - EBook Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/book-details.css">
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
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="index.php">Home</a>
                <span class="separator">/</span>
                <a href="browse.php">Browse</a>
                <span class="separator">/</span>
                <a href="browse.php?category=<?php echo $book['category_id']; ?>"><?php echo htmlspecialchars($book['category_name'] ?? 'Books'); ?></a>
                <span class="separator">/</span>
                <span class="current"><?php echo htmlspecialchars($book['title']); ?></span>
            </nav>

            <!-- Book Details -->
            <div class="book-details">
                <div class="book-cover-large">
                    <?php if ($book['cover_image']): ?>
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="cover-placeholder">
                            <i class="fas fa-book"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="book-info">
                    <h1><?php echo htmlspecialchars($book['title']); ?></h1>
                    <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                    
                    <div class="book-meta">
                        <div class="meta-item">
                            <strong>Category:</strong> 
                            <a href="browse.php?category=<?php echo $book['category_id']; ?>">
                                <?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?>
                            </a>
                        </div>
                        
                        <?php if ($book['pages']): ?>
                            <div class="meta-item">
                                <strong>Pages:</strong> <?php echo number_format($book['pages']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($book['language']): ?>
                            <div class="meta-item">
                                <strong>Language:</strong> <?php echo strtoupper($book['language']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($book['published_date']): ?>
                            <div class="meta-item">
                                <strong>Published:</strong> <?php echo date('F j, Y', strtotime($book['published_date'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($book['isbn']): ?>
                            <div class="meta-item">
                                <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="book-rating">
                        <div class="rating-display">
                            <div class="stars">
                                <?php echo render_stars($rating['average']); ?>
                            </div>
                            <span class="rating-text">
                                <?php echo $rating['average']; ?>/5 (<?php echo $rating['total']; ?> reviews)
                            </span>
                        </div>
                    </div>

                    <div class="book-actions">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="reader.php?id=<?php echo $book['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-book-open"></i> 
                                <?php echo $progress ? 'Continue Reading' : 'Start Reading'; ?>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login to Read
                            </a>
                        <?php endif; ?>
                        
                        <button class="btn btn-secondary" onclick="addToFavorites(<?php echo $book['id']; ?>)">
                            <i class="fas fa-heart"></i> Add to Favorites
                        </button>
                        
                        <a href="<?php echo htmlspecialchars($book['file_path']); ?>" 
                           class="btn btn-secondary" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>

                    <?php if ($progress): ?>
                        <div class="reading-progress">
                            <h3>Your Progress</h3>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress['progress_percentage']; ?>%"></div>
                            </div>
                            <p>Page <?php echo $progress['current_page']; ?> of <?php echo $book['pages'] ?: '?'; ?> 
                               (<?php echo round($progress['progress_percentage'], 1); ?>% complete)</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Book Description -->
            <?php if ($book['description']): ?>
                <div class="book-description">
                    <h2>Description</h2>
                    <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <h2>Reviews (<?php echo $rating['total']; ?>)</h2>
                
                <?php if (empty($reviews)): ?>
                    <p class="no-reviews">No reviews yet. Be the first to review this book!</p>
                <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></strong>
                                        <span class="review-date"><?php echo time_ago($review['created_at']); ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?php echo render_stars($review['rating']); ?>
                                    </div>
                                </div>
                                <?php if ($review['review_text']): ?>
                                    <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="add-review">
                        <h3>Write a Review</h3>
                        <form id="review-form" onsubmit="submitReview(event)">
                            <div class="form-group">
                                <label>Rating:</label>
                                <div class="rating-input">
                                    <input type="radio" id="star5" name="rating" value="5">
                                    <label for="star5"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star4" name="rating" value="4">
                                    <label for="star4"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star3" name="rating" value="3">
                                    <label for="star3"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star2" name="rating" value="2">
                                    <label for="star2"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star1" name="rating" value="1">
                                    <label for="star1"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="review-text">Your Review:</label>
                                <textarea id="review-text" name="review_text" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Related Books -->
            <?php if (!empty($related_books)): ?>
                <div class="related-books">
                    <h2>Related Books</h2>
                    <div class="books-grid">
                        <?php foreach ($related_books as $related_book): ?>
                            <div class="book-card" onclick="viewBook(<?php echo $related_book['id']; ?>)">
                                <div class="book-cover">
                                    <?php if ($related_book['cover_image']): ?>
                                        <img src="<?php echo htmlspecialchars($related_book['cover_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($related_book['title']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-book"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="book-info">
                                    <h3 class="book-title"><?php echo htmlspecialchars($related_book['title']); ?></h3>
                                    <p class="book-author">by <?php echo htmlspecialchars($related_book['author']); ?></p>
                                    <div class="book-rating">
                                        <div class="stars">
                                            <?php 
                                            $related_rating = floatval($related_book['avg_rating']);
                                            $full_stars = floor($related_rating);
                                            $has_half_star = ($related_rating - $full_stars) >= 0.5;
                                            
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
                                        <span class="rating-text">(<?php echo intval($related_book['review_count']); ?>)</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 EBook Library. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        function viewBook(bookId) {
            window.location.href = `book.php?id=${bookId}`;
        }

        async function submitReview(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const reviewData = {
                book_id: <?php echo $book['id']; ?>,
                rating: parseInt(formData.get('rating')),
                review_text: formData.get('review_text')
            };
            
            if (!reviewData.rating) {
                alert('Please select a rating');
                return;
            }
            
            try {
                const response = await fetch('api/reviews.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(reviewData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Review submitted successfully!');
                    location.reload();
                } else {
                    alert(result.message || 'Error submitting review');
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                alert('Error submitting review');
            }
        }
    </script>
</body>
</html>
