/**
 * UI Enhancer - Adds smooth animations and interactions to the POS System UI
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('button, .btn, .sidebar .nav-links a, .login-form button');
    
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const x = e.clientX - e.target.getBoundingClientRect().left;
            const y = e.clientY - e.target.getBoundingClientRect().top;
            
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Smooth scroll for page navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset;
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Enhanced hover effect for cards and panels
    const cards = document.querySelectorAll('.card, .panel, .widget');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Calculate rotation based on mouse position
            const midX = rect.width / 2;
            const midY = rect.height / 2;
            
            // Limit the rotation to a small amount (3deg max)
            const rotateX = ((y - midY) / midY) * 1.5;
            const rotateY = ((midX - x) / midX) * 1.5;
            
            // Apply the transform
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
            this.style.zIndex = "10";
        });
        
        card.addEventListener('mouseleave', function() {
            // Reset the transform when mouse leaves
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
            this.style.zIndex = "1";
        });
    });
    
    // Add CSS for the ripple effect
    const style = document.createElement('style');
    style.textContent = `
        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.4);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
            z-index: 10;
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        button, .btn, .sidebar .nav-links a, .login-form button {
            position: relative;
            overflow: hidden;
        }
        
        /* Enhanced focus styles */
        input:focus, button:focus, select:focus, textarea:focus, a:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 102, 102, 0.2);
        }
        
        /* Enhanced transitions */
        .card, .panel, .widget, .btn, input, select, textarea, .nav-link, .sidebar .nav-links li, table tr {
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1) !important;
        }
    `;
    document.head.appendChild(style);
});
