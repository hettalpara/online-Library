// Mobile-specific JavaScript optimizations

document.addEventListener('DOMContentLoaded', function() {
    initMobileOptimizations();
});

function initMobileOptimizations() {
    // Initialize touch interactions
    initTouchInteractions();
    
    // Initialize mobile gestures
    initMobileGestures();
    
    // Initialize mobile performance optimizations
    initMobilePerformance();
    
    // Initialize mobile-specific features
    initMobileFeatures();
}

// Touch interactions for mobile devices
function initTouchInteractions() {
    // Add touch feedback to interactive elements
    const touchElements = document.querySelectorAll('.book-card, .category-card, .btn, .nav-link');
    
    touchElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            this.classList.add('touch-active');
        });
        
        element.addEventListener('touchend', function() {
            setTimeout(() => {
                this.classList.remove('touch-active');
            }, 150);
        });
        
        element.addEventListener('touchcancel', function() {
            this.classList.remove('touch-active');
        });
    });
}

// Mobile gestures
function initMobileGestures() {
    let startX, startY, startTime;
    
    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        startTime = Date.now();
    });
    
    document.addEventListener('touchend', function(e) {
        if (!startX || !startY) return;
        
        const endX = e.changedTouches[0].clientX;
        const endY = e.changedTouches[0].clientY;
        const endTime = Date.now();
        
        const diffX = startX - endX;
        const diffY = startY - endY;
        const diffTime = endTime - startTime;
        
        // Swipe detection
        if (diffTime < 300) {
            if (Math.abs(diffX) > Math.abs(diffY)) {
                // Horizontal swipe
                if (diffX > 50) {
                    handleSwipeLeft();
                } else if (diffX < -50) {
                    handleSwipeRight();
                }
            } else {
                // Vertical swipe
                if (diffY > 50) {
                    handleSwipeUp();
                } else if (diffY < -50) {
                    handleSwipeDown();
                }
            }
        }
        
        startX = startY = null;
    });
}

// Handle swipe gestures
function handleSwipeLeft() {
    // Close mobile menu if open
    const navMenu = document.querySelector('.nav-menu');
    if (navMenu && navMenu.classList.contains('active')) {
        closeMobileMenu();
    }
    
    // Close mobile filters if open
    const filterSidebar = document.getElementById('filters-sidebar');
    if (filterSidebar && filterSidebar.classList.contains('show')) {
        closeMobileFilters();
    }
}

function handleSwipeRight() {
    // Open mobile menu if closed
    const navMenu = document.querySelector('.nav-menu');
    if (navMenu && !navMenu.classList.contains('active') && window.innerWidth <= 768) {
        openMobileMenu();
    }
}

function handleSwipeUp() {
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function handleSwipeDown() {
    // Scroll to bottom
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}

// Mobile performance optimizations
function initMobilePerformance() {
    // Lazy load images
    initLazyLoading();
    
    // Optimize scroll performance
    initScrollOptimization();
    
    // Reduce animations on low-end devices
    optimizeAnimations();
}

// Lazy loading for images
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        images.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
}

// Scroll optimization
function initScrollOptimization() {
    let ticking = false;
    
    function updateScrollPosition() {
        // Add scroll-based effects here
        const scrollTop = window.pageYOffset;
        const header = document.querySelector('.header');
        
        if (header) {
            if (scrollTop > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
        
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateScrollPosition);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', requestTick);
}

// Optimize animations for low-end devices
function optimizeAnimations() {
    // Check if device is low-end
    const isLowEndDevice = navigator.hardwareConcurrency <= 2 || 
                          navigator.deviceMemory <= 2 ||
                          /Android.*Mobile|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isLowEndDevice) {
        // Disable complex animations
        document.documentElement.style.setProperty('--animation-duration', '0.1s');
        document.documentElement.style.setProperty('--transition-duration', '0.1s');
        
        // Remove hover effects
        const style = document.createElement('style');
        style.textContent = `
            .book-card:hover,
            .category-card:hover,
            .btn:hover {
                transform: none !important;
                box-shadow: none !important;
            }
        `;
        document.head.appendChild(style);
    }
}

// Mobile-specific features
function initMobileFeatures() {
    // Add mobile-specific classes
    if (window.innerWidth <= 768) {
        document.body.classList.add('mobile-device');
    }
    
    // Handle orientation changes
    window.addEventListener('orientationchange', function() {
        setTimeout(() => {
            // Recalculate layouts after orientation change
            window.dispatchEvent(new Event('resize'));
        }, 100);
    });
    
    // Add pull-to-refresh functionality
    initPullToRefresh();
    
    // Add mobile-specific keyboard handling
    initMobileKeyboard();
}

// Pull to refresh
function initPullToRefresh() {
    let startY = 0;
    let currentY = 0;
    let isPulling = false;
    let pullDistance = 0;
    
    document.addEventListener('touchstart', function(e) {
        if (window.scrollY === 0) {
            startY = e.touches[0].clientY;
            isPulling = true;
        }
    });
    
    document.addEventListener('touchmove', function(e) {
        if (!isPulling) return;
        
        currentY = e.touches[0].clientY;
        pullDistance = currentY - startY;
        
        if (pullDistance > 0 && pullDistance < 100) {
            e.preventDefault();
            // Add pull-to-refresh visual feedback
            document.body.style.transform = `translateY(${pullDistance * 0.5}px)`;
        }
    });
    
    document.addEventListener('touchend', function(e) {
        if (!isPulling) return;
        
        isPulling = false;
        document.body.style.transform = '';
        
        if (pullDistance > 80) {
            // Trigger refresh
            location.reload();
        }
        
        pullDistance = 0;
    });
}

// Mobile keyboard handling
function initMobileKeyboard() {
    const inputs = document.querySelectorAll('input, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            // Scroll input into view on mobile
            setTimeout(() => {
                this.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        });
    });
}

// Mobile menu functions
function openMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    const overlay = document.querySelector('.nav-menu-overlay');
    const body = document.body;
    
    if (navMenu) {
        navMenu.classList.add('active');
        if (overlay) overlay.classList.add('active');
        body.style.overflow = 'hidden';
    }
}

function closeMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    const overlay = document.querySelector('.nav-menu-overlay');
    const body = document.body;
    
    if (navMenu) {
        navMenu.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        body.style.overflow = '';
    }
}

// Mobile filter functions
function openMobileFilters() {
    const filterSidebar = document.getElementById('filters-sidebar');
    const body = document.body;
    
    if (filterSidebar) {
        filterSidebar.classList.add('show');
        body.style.overflow = 'hidden';
    }
}

function closeMobileFilters() {
    const filterSidebar = document.getElementById('filters-sidebar');
    const body = document.body;
    
    if (filterSidebar) {
        filterSidebar.classList.remove('show');
        body.style.overflow = '';
    }
}

// Add CSS for touch feedback
const touchStyles = document.createElement('style');
touchStyles.textContent = `
    .touch-active {
        transform: scale(0.98);
        opacity: 0.8;
        transition: all 0.1s ease;
    }
    
    .header.scrolled {
        box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        backdrop-filter: blur(10px);
    }
    
    .lazy {
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .lazy.loaded {
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .mobile-device .book-card:hover,
        .mobile-device .category-card:hover {
            transform: none;
            box-shadow: none;
        }
    }
`;
document.head.appendChild(touchStyles);

// Export functions for global access
window.openMobileMenu = openMobileMenu;
window.closeMobileMenu = closeMobileMenu;
window.openMobileFilters = openMobileFilters;
window.closeMobileFilters = closeMobileFilters;
