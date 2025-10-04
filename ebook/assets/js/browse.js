// Browse Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initBrowsePage();
});

function initBrowsePage() {
    // Initialize sort functionality
    initSorting();
    
    // Initialize filter form
    initFilters();
    
    // Initialize infinite scroll for mobile
    initInfiniteScroll();
    
    // Initialize mobile filters
    initMobileFilters();
}

// Initialize sorting functionality
function initSorting() {
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', this.value);
            currentUrl.searchParams.delete('page'); // Reset to first page
            window.location.href = currentUrl.toString();
        });
    }
}

// Initialize filter form
function initFilters() {
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        // Auto-submit on category change
        const categorySelect = document.getElementById('category');
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        // Debounced search
        const searchInput = document.getElementById('search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        filterForm.submit();
                    }
                }, 500);
            });
        }
        
        // Debounced author filter
        const authorInput = document.getElementById('author');
        if (authorInput) {
            let authorTimeout;
            authorInput.addEventListener('input', function() {
                clearTimeout(authorTimeout);
                authorTimeout = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        filterForm.submit();
                    }
                }, 500);
            });
        }
    }
}

// Initialize infinite scroll for mobile
function initInfiniteScroll() {
    if (window.innerWidth <= 768) {
        const booksGrid = document.querySelector('.books-grid');
        if (booksGrid) {
            const sentinel = document.createElement('div');
            sentinel.className = 'scroll-sentinel';
            sentinel.style.height = '20px';
            booksGrid.appendChild(sentinel);
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        loadMoreBooks();
                    }
                });
            });
            
            observer.observe(sentinel);
        }
    }
}

// Load more books for infinite scroll
async function loadMoreBooks() {
    const currentPage = getCurrentPage();
    const nextPage = currentPage + 1;
    const totalPages = getTotalPages();
    
    if (nextPage > totalPages) {
        return; // No more pages to load
    }
    
    try {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('page', nextPage);
        
        const response = await fetch(currentUrl.toString());
        const html = await response.text();
        
        // Parse the response to extract new books
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newBooks = doc.querySelectorAll('.book-card');
        
        if (newBooks.length > 0) {
            const booksGrid = document.querySelector('.books-grid');
            newBooks.forEach(book => {
                booksGrid.appendChild(book);
            });
            
            // Update pagination info
            updatePaginationInfo(nextPage, totalPages);
        }
    } catch (error) {
        console.error('Error loading more books:', error);
    }
}

// Get current page number
function getCurrentPage() {
    const urlParams = new URLSearchParams(window.location.search);
    return parseInt(urlParams.get('page')) || 1;
}

// Get total pages
function getTotalPages() {
    const pagination = document.querySelector('.pagination');
    if (pagination) {
        const lastPageLink = pagination.querySelector('.pagination-number:last-child');
        if (lastPageLink) {
            return parseInt(lastPageLink.textContent);
        }
    }
    return 1;
}

// Update pagination info
function updatePaginationInfo(currentPage, totalPages) {
    const resultsInfo = document.querySelector('.results-info p');
    if (resultsInfo) {
        const currentCount = document.querySelectorAll('.book-card').length;
        resultsInfo.textContent = `Showing ${currentCount} books (Page ${currentPage} of ${totalPages})`;
    }
}

// Enhanced book card interactions
function initBookCardInteractions() {
    const bookCards = document.querySelectorAll('.book-card');
    
    bookCards.forEach(card => {
        // Add hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
        
        // Add click tracking
        card.addEventListener('click', function() {
            const bookId = this.getAttribute('data-book-id');
            if (bookId) {
                trackBookView(bookId);
            }
        });
    });
}

// Track book view for analytics
async function trackBookView(bookId) {
    try {
        await fetch('api/analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'book_view',
                book_id: bookId,
                page: 'browse'
            })
        });
    } catch (error) {
        console.error('Error tracking book view:', error);
    }
}

// Quick filter buttons
function initQuickFilters() {
    const quickFilters = document.querySelectorAll('.quick-filter');
    
    quickFilters.forEach(filter => {
        filter.addEventListener('click', function() {
            const filterType = this.getAttribute('data-filter');
            const filterValue = this.getAttribute('data-value');
            
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set(filterType, filterValue);
            currentUrl.searchParams.delete('page'); // Reset to first page
            
            window.location.href = currentUrl.toString();
        });
    });
}

// Search suggestions
function initSearchSuggestions() {
    const searchInput = document.getElementById('search');
    if (!searchInput) return;
    
    let suggestionTimeout;
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'search-suggestions';
    suggestionsContainer.style.display = 'none';
    searchInput.parentNode.appendChild(suggestionsContainer);
    
    searchInput.addEventListener('input', function() {
        clearTimeout(suggestionTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            suggestionsContainer.style.display = 'none';
            return;
        }
        
        suggestionTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`api/search-suggestions.php?q=${encodeURIComponent(query)}`);
                const suggestions = await response.json();
                
                if (suggestions.success && suggestions.data.length > 0) {
                    displaySuggestions(suggestions.data, suggestionsContainer);
                } else {
                    suggestionsContainer.style.display = 'none';
                }
            } catch (error) {
                console.error('Error fetching suggestions:', error);
            }
        }, 300);
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
}

// Display search suggestions
function displaySuggestions(suggestions, container) {
    container.innerHTML = suggestions.map(suggestion => 
        `<div class="suggestion-item" onclick="selectSuggestion('${suggestion.text}')">
            <i class="fas fa-search"></i>
            <span>${escapeHtml(suggestion.text)}</span>
        </div>`
    ).join('');
    
    container.style.display = 'block';
}

// Select suggestion
function selectSuggestion(text) {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.value = text;
        searchInput.form.submit();
    }
}

// Filter by rating
function filterByRating(rating) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('min_rating', rating);
    currentUrl.searchParams.delete('page');
    window.location.href = currentUrl.toString();
}

// Filter by publication year
function filterByYear(year) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('year', year);
    currentUrl.searchParams.delete('page');
    window.location.href = currentUrl.toString();
}

// Clear all filters
function clearAllFilters() {
    window.location.href = 'browse.php';
}

// Initialize mobile filters
function initMobileFilters() {
    const filterToggle = document.querySelector('.mobile-filter-toggle');
    const filterSidebar = document.getElementById('filters-sidebar');
    
    if (filterToggle && filterSidebar) {
        // Close filters when clicking outside
        document.addEventListener('click', function(e) {
            if (!filterSidebar.contains(e.target) && !filterToggle.contains(e.target)) {
                closeMobileFilters();
            }
        });
        
        // Close filters on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && filterSidebar.classList.contains('show')) {
                closeMobileFilters();
            }
        });
    }
}

// Toggle mobile filters
function toggleMobileFilters() {
    const filterSidebar = document.getElementById('filters-sidebar');
    const body = document.body;
    
    if (filterSidebar) {
        if (filterSidebar.classList.contains('show')) {
            closeMobileFilters();
        } else {
            openMobileFilters();
        }
    }
}

// Open mobile filters
function openMobileFilters() {
    const filterSidebar = document.getElementById('filters-sidebar');
    const body = document.body;
    
    if (filterSidebar) {
        filterSidebar.classList.add('show');
        body.style.overflow = 'hidden';
    }
}

// Close mobile filters
function closeMobileFilters() {
    const filterSidebar = document.getElementById('filters-sidebar');
    const body = document.body;
    
    if (filterSidebar) {
        filterSidebar.classList.remove('show');
        body.style.overflow = '';
    }
}

// Export functions for global access
window.viewBook = viewBook;
window.readBook = readBook;
window.addToFavorites = addToFavorites;
window.filterByRating = filterByRating;
window.filterByYear = filterByYear;
window.clearAllFilters = clearAllFilters;
window.selectSuggestion = selectSuggestion;
window.toggleMobileFilters = toggleMobileFilters;
