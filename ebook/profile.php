<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Require login
require_login();

$db = new Database();
$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $update_fields = [];
    $params = [];
    
    if (!empty($first_name)) {
        $update_fields[] = "first_name = ?";
        $params[] = $first_name;
    }
    
    if (!empty($last_name)) {
        $update_fields[] = "last_name = ?";
        $params[] = $last_name;
    }
    
    if (!empty($email) && validate_email($email)) {
        // Check if email is already taken
        $check_sql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?";
        $result = $db->fetch($check_sql, [$email, $user_id]);
        
        if ($result['count'] == 0) {
            $update_fields[] = "email = ?";
            $params[] = $email;
        } else {
            $error_message = 'Email already exists';
        }
    }
    
    if (!empty($new_password)) {
        if (strlen($new_password) >= 6) {
            if ($new_password === $confirm_password) {
                // Verify current password
                $user_sql = "SELECT password_hash FROM users WHERE id = ?";
                $user = $db->fetch($user_sql, [$user_id]);
                
                if (verify_password($current_password, $user['password_hash'])) {
                    $update_fields[] = "password_hash = ?";
                    $params[] = hash_password($new_password);
                } else {
                    $error_message = 'Current password is incorrect';
                }
            } else {
                $error_message = 'New passwords do not match';
            }
        } else {
            $error_message = 'New password must be at least 6 characters long';
        }
    }
    
    if (empty($error_message) && !empty($update_fields)) {
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $db->query($sql, $params);
        $success_message = 'Profile updated successfully!';
    }
}

// Get user data
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user_id]);

// Get user statistics
$stats = $db->fetch("
    SELECT 
        COUNT(DISTINCT rp.book_id) as books_read,
        COUNT(DISTINCT bm.book_id) as books_bookmarked,
        COUNT(DISTINCT r.book_id) as books_reviewed
    FROM users u
    LEFT JOIN reading_progress rp ON u.id = rp.user_id
    LEFT JOIN bookmarks bm ON u.id = bm.user_id
    LEFT JOIN reviews r ON u.id = r.user_id
    WHERE u.id = ?
", [$user_id]);

// Get recent reading activity
$recent_books = $db->fetchAll("
    SELECT b.*, rp.current_page, rp.progress_percentage, rp.last_read_at
    FROM reading_progress rp
    JOIN books b ON rp.book_id = b.id
    WHERE rp.user_id = ?
    ORDER BY rp.last_read_at DESC
    LIMIT 5
", [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - EBook Library</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
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
                    <a href="profile.php" class="nav-link active">Profile</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                </div>
                <div class="nav-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <main class="auth-main">
        <div class="container">
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="member-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo intval($stats['books_read']); ?></h3>
                            <p>Books Read</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo intval($stats['books_bookmarked']); ?></h3>
                            <p>Bookmarks</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo intval($stats['books_reviewed']); ?></h3>
                            <p>Reviews</p>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="profile-form-container">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
                            <p>Update your personal information and preferences.</p>
                        </div>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="auth-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">
                                        <i class="fas fa-user"></i> First Name
                                    </label>
                                    <input type="text" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="last_name">
                                        <i class="fas fa-user"></i> Last Name
                                    </label>
                                    <input type="text" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> Email Address
                                </label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="current_password">
                                    <i class="fas fa-lock"></i> Current Password
                                </label>
                                <input type="password" id="current_password" name="current_password" 
                                       placeholder="Enter current password to change">
                            </div>

                            <div class="form-group">
                                <label for="new_password">
                                    <i class="fas fa-lock"></i> New Password
                                </label>
                                <input type="password" id="new_password" name="new_password" 
                                       placeholder="Enter new password">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i> Confirm New Password
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm new password">
                            </div>

                            <button type="submit" class="btn btn-primary btn-full">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Recent Reading Activity -->
                <?php if (!empty($recent_books)): ?>
                    <div class="recent-activity">
                        <h2><i class="fas fa-history"></i> Recent Reading Activity</h2>
                        <div class="activity-list">
                            <?php foreach ($recent_books as $book): ?>
                                <div class="activity-item">
                                    <div class="activity-book">
                                        <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                                        <p>by <?php echo htmlspecialchars($book['author']); ?></p>
                                    </div>
                                    <div class="activity-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $book['progress_percentage']; ?>%"></div>
                                        </div>
                                        <span class="progress-text">
                                            Page <?php echo $book['current_page']; ?> 
                                            (<?php echo round($book['progress_percentage'], 1); ?>%)
                                        </span>
                                    </div>
                                    <div class="activity-actions">
                                        <a href="reader.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">
                                            Continue Reading
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 EBook Library. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
