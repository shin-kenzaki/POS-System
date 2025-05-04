document.addEventListener('DOMContentLoaded', function() {
    // Product category filtering
    const categoryButtons = document.querySelectorAll('.category-btn');
    const productItems = document.querySelectorAll('.product-item');
    const productSearch = document.getElementById('product-search');
    
    // Handle category filtering
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            const selectedCategory = this.dataset.category;
            
            // Filter products
            productItems.forEach(product => {
                if (selectedCategory === 'all' || product.dataset.category === selectedCategory) {
                    product.style.display = '';
                } else {
                    product.style.display = 'none';
                }
            });
            
            // Clear search when changing categories
            if (productSearch.value) {
                productSearch.value = '';
            }
        });
    });
    
    // Product search functionality
    productSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm === '') {
            // If search is cleared, respect category filter
            const activeCategory = document.querySelector('.category-btn.active').dataset.category;
            productItems.forEach(product => {
                if (activeCategory === 'all' || product.dataset.category === activeCategory) {
                    product.style.display = '';
                } else {
                    product.style.display = 'none';
                }
            });
            return;
        }
        
        // Search in all products regardless of category
        productItems.forEach(product => {
            const productName = product.querySelector('.product-info h5').textContent.toLowerCase();
            const productSku = product.querySelector('.product-info .sku').textContent.toLowerCase();
            
            if (productName.includes(searchTerm) || productSku.includes(searchTerm)) {
                product.style.display = '';
            } else {
                product.style.display = 'none';
            }
        });
    });
    
    // Clear search button
    const clearSearchBtn = document.getElementById('clear-search-btn');
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            productSearch.value = '';
            productSearch.dispatchEvent(new Event('input'));
        });
    }
    
    // Handle numpad toggle
    const toggleNumpadButtons = document.querySelectorAll('.toggle-numpad');
    const numpad = document.querySelector('.numpad');
    const closeNumpadBtn = document.querySelector('.close-numpad');
    
    toggleNumpadButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (numpad.style.display === 'none') {
                numpad.style.display = 'block';
            } else {
                numpad.style.display = 'none';
            }
        });
    });
    
    if (closeNumpadBtn) {
        closeNumpadBtn.addEventListener('click', function() {
            numpad.style.display = 'none';
        });
    }
    
    // Handle quantity updates (from pos.js)
    document.addEventListener('click', function(e) {
        // Handle quantity decrease button
        if (e.target.classList.contains('minus') || e.target.parentElement.classList.contains('minus')) {
            const quantityElement = e.target.closest('td').querySelector('.quantity-value');
            if (quantityElement) {
                let quantity = parseInt(quantityElement.textContent);
                if (quantity > 1) {
                    quantityElement.textContent = quantity - 1;
                    updateCartItem(e.target.closest('tr').dataset.id, quantity - 1);
                }
            }
        }
        
        // Handle quantity increase button
        if (e.target.classList.contains('plus') || e.target.parentElement.classList.contains('plus')) {
            const quantityElement = e.target.closest('td').querySelector('.quantity-value');
            if (quantityElement) {
                let quantity = parseInt(quantityElement.textContent);
                quantityElement.textContent = quantity + 1;
                updateCartItem(e.target.closest('tr').dataset.id, quantity + 1);
            }
        }
    });
    
    // Function to update cart item
    function updateCartItem(productId, quantity) {
        console.log(`Updating product ${productId} to quantity ${quantity}`);
        updateCartTotals();
    }
    
    // Update cart totals
    function updateCartTotals() {
        // Placeholder for cart total calculations
        // Implementation would update the subtotal, tax, and total displays
    }
    
    // Add keyboard shortcuts for POS operations
    document.addEventListener('keydown', function(e) {
        // F2 - New Customer
        if (e.key === 'F2') {
            e.preventDefault();
            document.getElementById('new-customer-btn').click();
        }
        
        // F3 - Focus Search
        if (e.key === 'F3') {
            e.preventDefault();
            productSearch.focus();
        }
        
        // F8 - Hold Sale
        if (e.key === 'F8') {
            e.preventDefault();
            document.getElementById('hold-sale-btn').click();
        }
        
        // F12 - Payment
        if (e.key === 'F12') {
            e.preventDefault();
            document.getElementById('checkout-btn').click();
        }
        
        // Escape - Clear search or close modals
        if (e.key === 'Escape') {
            if (productSearch.value) {
                productSearch.value = '';
                productSearch.dispatchEvent(new Event('input'));
            }
        }
    });
});
