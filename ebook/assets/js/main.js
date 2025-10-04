// EBook Library Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the application
    init();
});

function init() {
    // Load featured books
    loadFeaturedBooks();
    
    // Load categories
    loadCategories();
    
    // Initialize mobile menu
    initMobileMenu();
    
    // Initialize search functionality
    initSearch();
}

// Enhanced mobile menu functionality
function initMobileMenu() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const body = document.body;
    
    if (navToggle && navMenu) {
        // Create overlay element
        const overlay = document.createElement('div');
        overlay.className = 'nav-menu-overlay';
        document.body.appendChild(overlay);
        
        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMobileMenu();
        });
        
        // Close menu when clicking overlay
        overlay.addEventListener('click', function() {
            closeMobileMenu();
        });
        
        // Close menu when clicking on a link
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeMobileMenu();
            });
        });
        
        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                closeMobileMenu();
            }
        });
        
        // Prevent body scroll when menu is open
        function toggleMobileMenu() {
            navMenu.classList.toggle('active');
            overlay.classList.toggle('active');
            navToggle.classList.toggle('active');
            
            if (navMenu.classList.contains('active')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = '';
            }
        }
        
        function closeMobileMenu() {
            navMenu.classList.remove('active');
            overlay.classList.remove('active');
            navToggle.classList.remove('active');
            body.style.overflow = '';
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
    }
}

// Load featured books
async function loadFeaturedBooks() {
    const container = document.getElementById('featured-books');
    if (!container) return;
    
    try {
        const response = await fetch('api/books.php?featured=1&limit=6');
        const books = await response.json();
        
        if (books.success) {
            container.innerHTML = books.data.map(book => createBookCard(book)).join('');
        } else {
            container.innerHTML = '<p>No featured books available.</p>';
        }
    } catch (error) {
        console.error('Error loading featured books:', error);
        container.innerHTML = '<p>Error loading books. Please try again later.</p>';
    }
}

// Load categories
async function loadCategories() {
    const container = document.getElementById('categories-grid');
    if (!container) return;
    
    try {
        const response = await fetch('api/categories.php');
        const categories = await response.json();
        
        if (categories.success) {
            container.innerHTML = categories.data.map(category => createCategoryCard(category)).join('');
        } else {
            container.innerHTML = '<p>No categories available.</p>';
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        container.innerHTML = '<p>Error loading categories. Please try again later.</p>';
    }
}

// Create book card HTML
function createBookCard(book) {
    const rating = book.rating || { average: 0, total: 0 };
    const stars = renderStars(rating.average);
    
    return `
        <div class="book-card" onclick="viewBook(${book.id})">
            <div class="book-cover">
                ${book.cover_image ? 
                    `<img src="${book.cover_image}" alt="${book.title}">` : 
                    `<i class="fas fa-book"></i>`
                }
            </div>
            <div class="book-info">
                <h3 class="book-title">${escapeHtml(book.title)}</h3>
                <p class="book-author">by ${escapeHtml(book.author)}</p>
                <div class="book-rating">
                    <div class="stars">${stars}</div>
                    <span class="rating-text">(${rating.total} reviews)</span>
                </div>
                <div class="book-actions">
                    <button class="btn btn-primary" onclick="event.stopPropagation(); readBook(${book.id})">
                        <i class="fas fa-book-open"></i> Read
                    </button>
                    <button class="btn btn-secondary" onclick="event.stopPropagation(); addToFavorites(${book.id})">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Create category card HTML
function createCategoryCard(category) {
    const icons = {
        'Fiction': 'fas fa-feather-alt',
        'Non-Fiction': 'fas fa-book',
        'Science Fiction': 'fas fa-rocket',
        'Mystery': 'fas fa-search',
        'Romance': 'fas fa-heart',
        'Fantasy': 'fas fa-magic',
        'Biography': 'fas fa-user',
        'History': 'fas fa-landmark',
        'Science': 'fas fa-flask',
        'Technology': 'fas fa-laptop-code'
    };
    
    const icon = icons[category.name] || 'fas fa-book';
    
    return `
        <a href="browse.php?category=${category.id}" class="category-card">
            <div class="category-icon">
                <i class="${icon}"></i>
            </div>
            <h3 class="category-name">${escapeHtml(category.name)}</h3>
            <p class="category-count">${category.book_count || 0} books</p>
        </a>
    `;
}

// Render star rating
function renderStars(rating) {
    let stars = '';
    const fullStars = Math.floor(rating);
    const hasHalfStar = (rating - fullStars) >= 0.5;
    
    for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
            stars += '<i class="fas fa-star"></i>';
        } else if (i === fullStars + 1 && hasHalfStar) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        } else {
            stars += '<i class="far fa-star"></i>';
        }
    }
    
    return stars;
}

// Initialize search functionality
function initSearch() {
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = this.querySelector('input[name="q"]').value.trim();
            if (query) {
                window.location.href = `search.php?q=${encodeURIComponent(query)}`;
            }
        });
    }
}

// View book details
function viewBook(bookId) {
    window.location.href = `book.php?id=${bookId}`;
}

// Read book
function readBook(bookId) {
    window.location.href = `reader.php?id=${bookId}`;
}

// Add to favorites (placeholder)
async function addToFavorites(bookId) {
    try {
        const response = await fetch('api/favorites.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ book_id: bookId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Book added to favorites!', 'success');
        } else {
            showNotification(result.message || 'Error adding to favorites', 'error');
        }
    } catch (error) {
        console.error('Error adding to favorites:', error);
        showNotification('Error adding to favorites', 'error');
    }
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Utility function to format file size
function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return `${size.toFixed(1)} ${units[unitIndex]}`;
}

// Utility function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Loading state management
function showLoading(element) {
    element.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
}

function hideLoading(element) {
    const loading = element.querySelector('.loading');
    if (loading) {
        loading.remove();
    }
}

// Infinite scroll for book lists
function initInfiniteScroll(container, loadMoreFunction) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadMoreFunction();
            }
        });
    });
    
    const sentinel = document.createElement('div');
    sentinel.className = 'scroll-sentinel';
    container.appendChild(sentinel);
    observer.observe(sentinel);
}

// Book search with debouncing
function initBookSearch(inputElement, resultsContainer) {
    let searchTimeout;
    
    inputElement.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
                const results = await response.json();
                
                if (results.success) {
                    resultsContainer.innerHTML = results.data.map(book => 
                        `<div class="search-result" onclick="viewBook(${book.id})">
                            <strong>${escapeHtml(book.title)}</strong> by ${escapeHtml(book.author)}
                        </div>`
                    ).join('');
                } else {
                    resultsContainer.innerHTML = '<p>No results found.</p>';
                }
            } catch (error) {
                console.error('Search error:', error);
                resultsContainer.innerHTML = '<p>Search error. Please try again.</p>';
            }
        }, 300);
    });
}
