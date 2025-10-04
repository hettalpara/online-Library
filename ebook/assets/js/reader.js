// Book Reader JavaScript

let currentBook = null;
let currentProgress = null;
let isSidebarOpen = false;
let isFullscreen = false;

// Initialize reader
function initReader(bookData, userProgress) {
    currentBook = bookData;
    currentProgress = userProgress;
    
    // Initialize reading settings
    loadReadingSettings();
    
    // Initialize event listeners
    initEventListeners();
    
    // Update progress display
    updateProgressDisplay();
    
    // Load book content if it's a text file
    if (currentBook.fileType !== 'application/pdf') {
        loadBookContent();
    }
}

// Initialize event listeners
function initEventListeners() {
    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyboardShortcuts);
    
    // Window resize
    window.addEventListener('resize', handleWindowResize);
    
    // Before unload - save progress
    window.addEventListener('beforeunload', saveReadingProgress);
    
    // Visibility change - save progress when tab becomes hidden
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            saveReadingProgress();
        }
    });
}

// Handle keyboard shortcuts
function handleKeyboardShortcuts(e) {
    // Don't trigger shortcuts when typing in inputs
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }
    
    switch(e.key) {
        case 'Escape':
            if (isSidebarOpen) {
                toggleSidebar();
            } else if (isFullscreen) {
                toggleFullscreen();
            }
            break;
        case 'ArrowLeft':
            e.preventDefault();
            previousPage();
            break;
        case 'ArrowRight':
            e.preventDefault();
            nextPage();
            break;
        case 'b':
        case 'B':
            e.preventDefault();
            addBookmark();
            break;
        case 'f':
        case 'F':
            e.preventDefault();
            toggleFullscreen();
            break;
        case 's':
        case 'S':
            e.preventDefault();
            toggleSidebar();
            break;
    }
}

// Toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('reader-sidebar');
    isSidebarOpen = !isSidebarOpen;
    
    if (isSidebarOpen) {
        sidebar.classList.add('open');
    } else {
        sidebar.classList.remove('open');
    }
}

// Toggle fullscreen
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            isFullscreen = true;
            updateFullscreenButton();
        });
    } else {
        document.exitFullscreen().then(() => {
            isFullscreen = false;
            updateFullscreenButton();
        });
    }
}

// Update fullscreen button icon
function updateFullscreenButton() {
    const button = document.querySelector('[onclick="toggleFullscreen()"]');
    if (button) {
        const icon = button.querySelector('i');
        if (isFullscreen) {
            icon.className = 'fas fa-compress';
        } else {
            icon.className = 'fas fa-expand';
        }
    }
}

// Load book content
async function loadBookContent() {
    try {
        const response = await fetch(`api/books.php?id=${currentBook.id}&action=content`);
        const data = await response.json();
        
        if (data.success) {
            displayBookContent(data.content);
        } else {
            showNotification('Error loading book content', 'error');
        }
    } catch (error) {
        console.error('Error loading book content:', error);
        showNotification('Error loading book content', 'error');
    }
}

// Display book content
function displayBookContent(content) {
    const readerPage = document.getElementById('reader-page');
    if (readerPage) {
        readerPage.innerHTML = `
            <div class="page-content">
                ${content}
            </div>
        `;
    }
}

// Start reading
function startReading() {
    // This would typically load the actual book content
    // For now, we'll show a placeholder
    const readerPage = document.getElementById('reader-page');
    if (readerPage) {
        readerPage.innerHTML = `
            <div class="page-content">
                <h2>${currentBook.title}</h2>
                <p class="author">by ${currentBook.author}</p>
                <div class="book-content">
                    <p>This is where the book content would be displayed. In a real implementation, 
                    this would load the actual book text from the file.</p>
                    
                    <p>You can use the sidebar to navigate through chapters, add bookmarks, 
                    and adjust reading settings.</p>
                    
                    <p>Use keyboard shortcuts for quick navigation:</p>
                    <ul>
                        <li>Arrow keys: Navigate pages</li>
                        <li>B: Add bookmark</li>
                        <li>F: Toggle fullscreen</li>
                        <li>S: Toggle sidebar</li>
                        <li>Escape: Close sidebar/fullscreen</li>
                    </ul>
                </div>
            </div>
        `;
    }
    
    // Update progress
    updateReadingProgress(1);
}

// Go to specific page
function goToPage(pageNumber) {
    if (pageNumber < 1 || (currentBook.pages && pageNumber > currentBook.pages)) {
        return;
    }
    
    currentProgress.currentPage = pageNumber;
    updateProgressDisplay();
    updateReadingProgress(pageNumber);
    
    // Update TOC active item
    updateTOCActiveItem(pageNumber);
}

// Navigate to previous page
function previousPage() {
    if (currentProgress.currentPage > 1) {
        goToPage(currentProgress.currentPage - 1);
    }
}

// Navigate to next page
function nextPage() {
    if (!currentBook.pages || currentProgress.currentPage < currentBook.pages) {
        goToPage(currentProgress.currentPage + 1);
    }
}

// Update progress display
function updateProgressDisplay() {
    const progressFill = document.querySelector('.progress-fill');
    const progressText = document.querySelector('.progress-text');
    
    if (progressFill && progressText) {
        const percentage = currentBook.pages ? 
            (currentProgress.currentPage / currentBook.pages) * 100 : 0;
        
        progressFill.style.width = `${percentage}%`;
        progressText.textContent = `${currentProgress.currentPage} / ${currentBook.pages || '?'} pages`;
    }
}

// Update TOC active item
function updateTOCActiveItem(pageNumber) {
    const tocItems = document.querySelectorAll('.toc-item');
    tocItems.forEach(item => {
        item.classList.remove('active');
        const link = item.querySelector('a');
        if (link) {
            const href = link.getAttribute('href');
            const targetPage = parseInt(href.match(/#page-(\d+)/)?.[1] || '0');
            if (pageNumber >= targetPage) {
                item.classList.add('active');
            }
        }
    });
}

// Update reading progress
function updateReadingProgress(pageNumber) {
    currentProgress.currentPage = pageNumber;
    
    if (currentBook.pages) {
        currentProgress.progressPercentage = (pageNumber / currentBook.pages) * 100;
    }
    
    // Save progress to server
    saveReadingProgress();
}

// Save reading progress
async function saveReadingProgress() {
    try {
        await fetch('api/reading-progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                book_id: currentBook.id,
                current_page: currentProgress.currentPage,
                total_pages: currentBook.pages,
                progress_percentage: currentProgress.progressPercentage
            })
        });
    } catch (error) {
        console.error('Error saving reading progress:', error);
    }
}

// Add bookmark
function addBookmark() {
    const modal = document.getElementById('bookmark-modal');
    const pageInput = document.getElementById('bookmark-page');
    
    if (pageInput) {
        pageInput.value = currentProgress.currentPage;
    }
    
    modal.classList.add('show');
}

// Save bookmark
async function saveBookmark() {
    const form = document.getElementById('bookmark-form');
    const formData = new FormData(form);
    
    const bookmarkData = {
        book_id: currentBook.id,
        page_number: parseInt(formData.get('page_number')),
        note: formData.get('note')
    };
    
    try {
        const response = await fetch('api/bookmarks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(bookmarkData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Bookmark added successfully!', 'success');
            closeModal('bookmark-modal');
            loadBookmarks(); // Refresh bookmarks list
        } else {
            showNotification(result.message || 'Error adding bookmark', 'error');
        }
    } catch (error) {
        console.error('Error saving bookmark:', error);
        showNotification('Error adding bookmark', 'error');
    }
}

// Add review
function addReview() {
    const modal = document.getElementById('review-modal');
    modal.classList.add('show');
}

// Save review
async function saveReview() {
    const form = document.getElementById('review-form');
    const formData = new FormData(form);
    
    const reviewData = {
        book_id: currentBook.id,
        rating: parseInt(formData.get('rating')),
        review_text: formData.get('review_text')
    };
    
    if (!reviewData.rating) {
        showNotification('Please select a rating', 'error');
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
            showNotification('Review added successfully!', 'success');
            closeModal('review-modal');
            loadReviews(); // Refresh reviews list
        } else {
            showNotification(result.message || 'Error adding review', 'error');
        }
    } catch (error) {
        console.error('Error saving review:', error);
        showNotification('Error adding review', 'error');
    }
}

// Download book
function downloadBook() {
    if (currentBook.filePath) {
        const link = document.createElement('a');
        link.href = currentBook.filePath;
        link.download = `${currentBook.title}.${getFileExtension(currentBook.filePath)}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Get file extension
function getFileExtension(filePath) {
    return filePath.split('.').pop();
}

// Close modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
}

// Change font size
function changeFontSize(size) {
    const readerPage = document.querySelector('.reader-page');
    if (readerPage) {
        readerPage.style.fontSize = `${size}px`;
    }
    
    const sizeDisplay = document.getElementById('font-size-value');
    if (sizeDisplay) {
        sizeDisplay.textContent = `${size}px`;
    }
    
    saveReadingSettings();
}

// Change line height
function changeLineHeight(height) {
    const readerPage = document.querySelector('.reader-page');
    if (readerPage) {
        readerPage.style.lineHeight = height;
    }
    
    const heightDisplay = document.getElementById('line-height-value');
    if (heightDisplay) {
        heightDisplay.textContent = height;
    }
    
    saveReadingSettings();
}

// Change theme
function changeTheme(theme) {
    const layout = document.querySelector('.reader-layout');
    if (layout) {
        // Remove existing theme classes
        layout.classList.remove('dark-theme', 'sepia-theme');
        
        if (theme !== 'light') {
            layout.classList.add(`${theme}-theme`);
        }
    }
    
    saveReadingSettings();
}

// Load reading settings
function loadReadingSettings() {
    const settings = JSON.parse(localStorage.getItem('readingSettings') || '{}');
    
    if (settings.fontSize) {
        changeFontSize(settings.fontSize);
        document.getElementById('font-size').value = settings.fontSize;
    }
    
    if (settings.lineHeight) {
        changeLineHeight(settings.lineHeight);
        document.getElementById('line-height').value = settings.lineHeight;
    }
    
    if (settings.theme) {
        changeTheme(settings.theme);
        document.getElementById('theme').value = settings.theme;
    }
}

// Save reading settings
function saveReadingSettings() {
    const settings = {
        fontSize: document.getElementById('font-size')?.value || 16,
        lineHeight: document.getElementById('line-height')?.value || 1.5,
        theme: document.getElementById('theme')?.value || 'light'
    };
    
    localStorage.setItem('readingSettings', JSON.stringify(settings));
}

// Load bookmarks
async function loadBookmarks() {
    try {
        const response = await fetch(`api/bookmarks.php?book_id=${currentBook.id}`);
        const result = await response.json();
        
        if (result.success) {
            displayBookmarks(result.data);
        }
    } catch (error) {
        console.error('Error loading bookmarks:', error);
    }
}

// Display bookmarks
function displayBookmarks(bookmarks) {
    const bookmarksList = document.querySelector('.bookmarks-list');
    if (!bookmarksList) return;
    
    if (bookmarks.length === 0) {
        bookmarksList.innerHTML = '<p class="no-bookmarks">No bookmarks yet</p>';
        return;
    }
    
    bookmarksList.innerHTML = bookmarks.map(bookmark => `
        <div class="bookmark-item">
            <a href="#page-${bookmark.page_number}" onclick="goToPage(${bookmark.page_number})">
                Page ${bookmark.page_number}
            </a>
            ${bookmark.note ? `<p class="bookmark-note">${escapeHtml(bookmark.note)}</p>` : ''}
        </div>
    `).join('');
}

// Load reviews
async function loadReviews() {
    try {
        const response = await fetch(`api/reviews.php?book_id=${currentBook.id}`);
        const result = await response.json();
        
        if (result.success) {
            displayReviews(result.data);
        }
    } catch (error) {
        console.error('Error loading reviews:', error);
    }
}

// Display reviews
function displayReviews(reviews) {
    const reviewsList = document.querySelector('.reviews-list');
    if (!reviewsList) return;
    
    if (reviews.length === 0) {
        reviewsList.innerHTML = '<p class="no-reviews">No reviews yet</p>';
        return;
    }
    
    reviewsList.innerHTML = reviews.map(review => `
        <div class="review-item">
            <div class="review-header">
                <strong>${escapeHtml(review.first_name + ' ' + review.last_name)}</strong>
                <div class="review-stars">${renderStars(review.rating)}</div>
            </div>
            ${review.review_text ? `<p class="review-text">${escapeHtml(review.review_text)}</p>` : ''}
        </div>
    `).join('');
}

// Handle window resize
function handleWindowResize() {
    // Adjust layout for mobile
    if (window.innerWidth <= 768) {
        if (isSidebarOpen) {
            // On mobile, sidebar takes full width
            const sidebar = document.getElementById('reader-sidebar');
            if (sidebar) {
                sidebar.style.width = '100%';
            }
        }
    } else {
        // Reset sidebar width on desktop
        const sidebar = document.getElementById('reader-sidebar');
        if (sidebar) {
            sidebar.style.width = '350px';
        }
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${message}
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Position notification
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '3000';
    notification.style.maxWidth = '300px';
    
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

// Render stars
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

// Export functions for global access
window.toggleSidebar = toggleSidebar;
window.toggleFullscreen = toggleFullscreen;
window.goToPage = goToPage;
window.addBookmark = addBookmark;
window.saveBookmark = saveBookmark;
window.addReview = addReview;
window.saveReview = saveReview;
window.downloadBook = downloadBook;
window.closeModal = closeModal;
window.changeFontSize = changeFontSize;
window.changeLineHeight = changeLineHeight;
window.changeTheme = changeTheme;
window.startReading = startReading;
