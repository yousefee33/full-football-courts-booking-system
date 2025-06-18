// Navbar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const header = document.querySelector('header');
    const menuIcon = document.querySelector('.menu-icon');
    const nav = document.querySelector('nav');
    const navLinks = document.querySelectorAll('nav a');

    // Handle scroll effect
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Handle mobile menu toggle
    menuIcon.addEventListener('click', () => {
        nav.classList.toggle('active');
        menuIcon.classList.toggle('active');
        
        // Animate menu icon
        const spans = menuIcon.querySelectorAll('span');
        spans.forEach(span => span.classList.toggle('active'));
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!nav.contains(e.target) && !menuIcon.contains(e.target) && nav.classList.contains('active')) {
            nav.classList.remove('active');
            menuIcon.classList.remove('active');
            const spans = menuIcon.querySelectorAll('span');
            spans.forEach(span => span.classList.remove('active'));
        }
    });

    // Close mobile menu when clicking a link
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            nav.classList.remove('active');
            menuIcon.classList.remove('active');
            const spans = menuIcon.querySelectorAll('span');
            spans.forEach(span => span.classList.remove('active'));
        });
    });

    // Handle active link state
    function setActiveLink() {
        const currentPath = window.location.pathname;
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Set active link on page load
    setActiveLink();

    // Update active link on navigation
    window.addEventListener('popstate', setActiveLink);
}); 