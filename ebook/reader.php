<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Require login
require_login();

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

// Get user's reading progress
$user_id = $_SESSION['user_id'];
$progress = get_user_reading_progress($user_id, $book_id, $db);

// Get bookmarks
$bookmarks = $db->fetchAll("
    SELECT * FROM bookmarks 
    WHERE user_id = ? AND book_id = ? 
    ORDER BY page_number
", [$user_id, $book_id]);

// Get reviews
$reviews = $db->fetchAll("
    SELECT r.*, u.username, u.first_name, u.last_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.book_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 10
", [$book_id]);

// Get book rating
$rating = get_book_rating($book_id, $db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - EBook Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/reader.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="reader-layout">
        <!-- Reader Header -->
        <header class="reader-header">
            <div class="header-left">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><?php echo htmlspecialchars($book['title']); ?></h1>
            </div>
            
            <div class="header-center">
                <div class="reading-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress ? $progress['progress_percentage'] : 0; ?>%"></div>
                    </div>
                    <span class="progress-text">
                        <?php echo $progress ? $progress['current_page'] : 1; ?> / <?php echo $book['pages'] ?: '?'; ?> pages
                    </span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn btn-secondary" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <button class="btn btn-primary" onclick="toggleFullscreen()">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
        </header>

        <!-- Main Reader Content -->
        <main class="reader-main">
            <div class="reader-content">
                <?php if ($book['file_type'] === 'application/pdf'): ?>
                    <!-- PDF Reader -->
                    <div class="pdf-reader">
                        <iframe src="<?php echo htmlspecialchars($book['file_path']); ?>" 
                                width="100%" height="100%" 
                                frameborder="0">
                            <p>Your browser does not support PDFs. 
                               <a href="<?php echo htmlspecialchars($book['file_path']); ?>" target="_blank">Download the PDF</a>.
                            </p>
                        </iframe>
                    </div>
                <?php else: ?>
                    <!-- Text Reader -->
                    <div class="text-reader">
                        <div class="reader-page" id="reader-page">
                            <div class="page-content">
                                <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                                <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                
                                <?php if ($book['description']): ?>
                                    <div class="book-description">
                                        <h3>Description</h3>
                                        <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="book-info">
                                    <div class="info-item">
                                        <strong>Category:</strong> <?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?>
                                    </div>
                                    <?php if ($book['pages']): ?>
                                        <div class="info-item">
                                            <strong>Pages:</strong> <?php echo number_format($book['pages']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($book['language']): ?>
                                        <div class="info-item">
                                            <strong>Language:</strong> <?php echo strtoupper($book['language']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($book['published_date']): ?>
                                        <div class="info-item">
                                            <strong>Published:</strong> <?php echo date('F j, Y', strtotime($book['published_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="book-rating">
                                    <h3>Rating</h3>
                                    <div class="rating-display">
                                        <div class="stars">
                                            <?php echo render_stars($rating['average']); ?>
                                        </div>
                                        <span class="rating-text">
                                            <?php echo $rating['average']; ?>/5 (<?php echo $rating['total']; ?> reviews)
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="reading-actions">
                                    <button class="btn btn-primary" onclick="startReading()">
                                        <i class="fas fa-play"></i> Start Reading
                                    </button>
                                    <button class="btn btn-secondary" onclick="downloadBook()">
                                        <i class="fas fa-download"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Reader Sidebar -->
        <aside class="reader-sidebar" id="reader-sidebar">
            <div class="sidebar-header">
                <h3>Reading Tools</h3>
                <button class="btn btn-sm btn-secondary" onclick="toggleSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="sidebar-content">
                <!-- Table of Contents -->
                <div class="sidebar-section">
                    <h4><i class="fas fa-list"></i> Table of Contents</h4>
                    <div class="toc-list">
                        <div class="toc-item active">
                            <a href="#page-1" onclick="goToPage(1)">Chapter 1: Introduction</a>
                        </div>
                        <div class="toc-item">
                            <a href="#page-5" onclick="goToPage(5)">Chapter 2: Getting Started</a>
                        </div>
                        <div class="toc-item">
                            <a href="#page-10" onclick="goToPage(10)">Chapter 3: Advanced Topics</a>
                        </div>
                    </div>
                </div>
                
                <!-- Bookmarks -->
                <div class="sidebar-section">
                    <h4><i class="fas fa-bookmark"></i> Bookmarks</h4>
                    <div class="bookmarks-list">
                        <?php if (empty($bookmarks)): ?>
                            <p class="no-bookmarks">No bookmarks yet</p>
                        <?php else: ?>
                            <?php foreach ($bookmarks as $bookmark): ?>
                                <div class="bookmark-item">
                                    <a href="#page-<?php echo $bookmark['page_number']; ?>" 
                                       onclick="goToPage(<?php echo $bookmark['page_number']; ?>)">
                                        Page <?php echo $bookmark['page_number']; ?>
                                    </a>
                                    <?php if ($bookmark['note']): ?>
                                        <p class="bookmark-note"><?php echo htmlspecialchars($bookmark['note']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="addBookmark()">
                        <i class="fas fa-plus"></i> Add Bookmark
                    </button>
                </div>
                
                <!-- Reading Settings -->
                <div class="sidebar-section">
                    <h4><i class="fas fa-cog"></i> Reading Settings</h4>
                    <div class="settings-group">
                        <label for="font-size">Font Size:</label>
                        <input type="range" id="font-size" min="12" max="24" value="16" 
                               onchange="changeFontSize(this.value)">
                        <span id="font-size-value">16px</span>
                    </div>
                    
                    <div class="settings-group">
                        <label for="line-height">Line Height:</label>
                        <input type="range" id="line-height" min="1.2" max="2.0" step="0.1" value="1.5" 
                               onchange="changeLineHeight(this.value)">
                        <span id="line-height-value">1.5</span>
                    </div>
                    
                    <div class="settings-group">
                        <label for="theme">Theme:</label>
                        <select id="theme" onchange="changeTheme(this.value)">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                            <option value="sepia">Sepia</option>
                        </select>
                    </div>
                </div>
                
                <!-- Reviews -->
                <div class="sidebar-section">
                    <h4><i class="fas fa-star"></i> Reviews</h4>
                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                            <p class="no-reviews">No reviews yet</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></strong>
                                        <div class="review-stars">
                                            <?php echo render_stars($review['rating']); ?>
                                        </div>
                                    </div>
                                    <?php if ($review['review_text']): ?>
                                        <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="addReview()">
                        <i class="fas fa-plus"></i> Add Review
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <!-- Bookmark Modal -->
    <div class="modal" id="bookmark-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Bookmark</h3>
                <button class="btn btn-sm btn-secondary" onclick="closeModal('bookmark-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="bookmark-form">
                    <div class="form-group">
                        <label for="bookmark-page">Page Number:</label>
                        <input type="number" id="bookmark-page" name="page_number" min="1" 
                               max="<?php echo $book['pages'] ?: 999; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="bookmark-note">Note (optional):</label>
                        <textarea id="bookmark-note" name="note" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="saveBookmark()">Save Bookmark</button>
                <button class="btn btn-secondary" onclick="closeModal('bookmark-modal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal" id="review-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Review</h3>
                <button class="btn btn-sm btn-secondary" onclick="closeModal('review-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="review-form">
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
                        <label for="review-text">Review:</label>
                        <textarea id="review-text" name="review_text" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="saveReview()">Save Review</button>
                <button class="btn btn-secondary" onclick="closeModal('review-modal')">Cancel</button>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/reader.js"></script>
    <script>
        // Initialize reader with book data
        const bookData = {
            id: <?php echo $book['id']; ?>,
            title: "<?php echo addslashes($book['title']); ?>",
            author: "<?php echo addslashes($book['author']); ?>",
            pages: <?php echo $book['pages'] ?: 0; ?>,
            filePath: "<?php echo addslashes($book['file_path']); ?>",
            fileType: "<?php echo $book['file_type']; ?>"
        };
        
        const userProgress = {
            currentPage: <?php echo $progress ? $progress['current_page'] : 1; ?>,
            totalPages: <?php echo $book['pages'] ?: 0; ?>,
            progressPercentage: <?php echo $progress ? $progress['progress_percentage'] : 0; ?>
        };
        
        initReader(bookData, userProgress);
    </script>
</body>
</html>
