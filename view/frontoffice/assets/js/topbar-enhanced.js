/**
 * Enhanced Top Bar JavaScript
 * Handles:
 * - Active link detection and highlighting
 * - Scroll detection for shadow animation
 * - Optional: Hide/show navbar on scroll
 * - Mobile menu toggle
 * - Smooth scroll behavior
 */

(function() {
    'use strict';

    // ============================================
    // CONFIGURATION
    // ============================================
    const CONFIG = {
        enableScrollHide: false, // Set to true to hide navbar on scroll down
        scrollThreshold: 100, // Pixels scrolled before shadow appears
        activeLinkClass: 'active',
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches
    };

    // ============================================
    // INITIALIZATION
    // ============================================
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        setupActiveLinks();
        setupScrollDetection();
        setupMobileMenu();
        setupSmoothScroll();
        setupNotificationBadge();
    }

    // ============================================
    // ACTIVE LINK DETECTION
    // ============================================
    function setupActiveLinks() {
        const currentPath = window.location.pathname;
        const links = document.querySelectorAll('.top-bar .profile-link, .top-bar .logout-link');
        
        links.forEach(link => {
            const href = link.getAttribute('href');
            if (!href) return;

            // Check if link matches current page
            const linkPath = new URL(href, window.location.origin).pathname;
            const currentPage = currentPath.split('/').pop() || 'index.php';
            const linkPage = linkPath.split('/').pop() || 'index.php';

            // Special handling for index.php
            if ((currentPage === 'index.php' || currentPage === '') && 
                (linkPage === 'index.php' || linkPage === '')) {
                link.classList.add(CONFIG.activeLinkClass);
            }
            // Match other pages
            else if (currentPage === linkPage) {
                link.classList.add(CONFIG.activeLinkClass);
            }
            // Match profile.php with user_id parameter
            else if (currentPage.includes('profile.php') && linkPage.includes('profile.php')) {
                link.classList.add(CONFIG.activeLinkClass);
            }
        });
    }

    // ============================================
    // SCROLL DETECTION
    // ============================================
    let lastScrollTop = 0;
    let ticking = false;

    function setupScrollDetection() {
        if (CONFIG.reducedMotion) return;

        const topBar = document.querySelector('.top-bar');
        if (!topBar) return;

        function updateScrollState() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Add shadow on scroll
            if (scrollTop > CONFIG.scrollThreshold) {
                topBar.classList.add('scrolled');
            } else {
                topBar.classList.remove('scrolled');
            }

            // Hide/show navbar on scroll (optional)
            if (CONFIG.enableScrollHide) {
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    // Scrolling down
                    topBar.classList.add('hidden');
                } else {
                    // Scrolling up
                    topBar.classList.remove('hidden');
                }
            }

            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                window.requestAnimationFrame(updateScrollState);
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestTick, { passive: true });
    }

    // ============================================
    // MOBILE MENU
    // ============================================
    function setupMobileMenu() {
        const hamburger = document.querySelector('.hamburger-menu');
        const overlay = document.querySelector('.mobile-menu-overlay');
        const body = document.body;

        if (!hamburger || !overlay) return;

        hamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isActive = hamburger.classList.toggle('active');
            overlay.classList.toggle('show', isActive);
            body.classList.toggle('menu-open', isActive);
        });

        // Close menu when clicking overlay
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                hamburger.classList.remove('active');
                overlay.classList.remove('show');
                body.classList.remove('menu-open');
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay.classList.contains('show')) {
                hamburger.classList.remove('active');
                overlay.classList.remove('show');
                body.classList.remove('menu-open');
            }
        });
    }

    // ============================================
    // SMOOTH SCROLL
    // ============================================
    function setupSmoothScroll() {
        if (CONFIG.reducedMotion) return;

        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || !href) return;

                const target = document.querySelector(href);
                if (!target) return;

                e.preventDefault();
                
                const topBarHeight = document.querySelector('.top-bar')?.offsetHeight || 60;
                const targetPosition = target.offsetTop - topBarHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Update URL without jumping
                history.pushState(null, null, href);
            });
        });
    }

    // ============================================
    // NOTIFICATION BADGE ANIMATION
    // ============================================
    function setupNotificationBadge() {
        const badge = document.getElementById('notificationBadge');
        if (!badge) return;

        // Show badge with animation when count > 0
        function updateBadge(count) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.add('show');
            } else {
                badge.classList.remove('show');
            }
        }

        // Initial check
        if (badge.textContent && parseInt(badge.textContent) > 0) {
            badge.classList.add('show');
        }
    }

    // ============================================
    // NOTIFICATION DROPDOWN ANIMATION
    // ============================================
    function toggleNotificationDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        const isVisible = dropdown.classList.contains('show');
        
        if (isVisible) {
            dropdown.classList.remove('show');
        } else {
            dropdown.classList.add('show');
            // Focus first notification for keyboard navigation
            const firstItem = dropdown.querySelector('.notification-item');
            if (firstItem) {
                firstItem.setAttribute('tabindex', '0');
            }
        }
    }

    // Make function globally available
    window.toggleNotificationDropdown = toggleNotificationDropdown;

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const container = document.querySelector('.notification-container');
        const dropdown = document.getElementById('notificationDropdown');
        
        if (container && dropdown && !container.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    // ============================================
    // KEYBOARD NAVIGATION
    // ============================================
    function setupKeyboardNavigation() {
        const focusableElements = document.querySelectorAll(
            '.top-bar a, .top-bar button, .notification-dropdown .notification-item'
        );

        focusableElements.forEach(element => {
            element.addEventListener('keydown', function(e) {
                // Enter or Space to activate
                if (e.key === 'Enter' || e.key === ' ') {
                    if (this.tagName === 'A') {
                        this.click();
                    } else if (this.tagName === 'BUTTON') {
                        e.preventDefault();
                        this.click();
                    }
                }
            });
        });
    }

    // Initialize keyboard navigation
    setupKeyboardNavigation();

    // ============================================
    // START
    // ============================================
    init();
})();





