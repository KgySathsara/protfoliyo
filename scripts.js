document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for navigation links
    const links = document.querySelectorAll('nav ul li a');
    const burger = document.querySelector('.burger');
    const navLinks = document.querySelector('.nav-links');

    for (const link of links) {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);

            window.scrollTo({
                top: targetSection.offsetTop - 60, // Adjust scroll position for fixed header
                behavior: 'smooth'
            });

            // Close the mobile menu after clicking a link
            if (window.innerWidth < 768) {
                navLinks.style.display = 'none';
            }
        });
    }

    // Toggle mobile menu
    burger.addEventListener('click', function() {
        if (navLinks.style.display === 'flex') {
            navLinks.style.display = 'none';
        } else {
            navLinks.style.display = 'flex';
        }
    });
});
