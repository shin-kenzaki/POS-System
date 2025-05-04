document.addEventListener('DOMContentLoaded', function() {
    // Product category filtering
    const categoryButtons = document.querySelectorAll('.category-btn');
    const productItems = document.querySelectorAll('.product-item');
    const productSearch = document.getElementById('product-search');
    
    // Cart variables
    let cart = [];
    const taxRate = 0.075; // 7.5% tax rate
    const cartTable = document.getElementById('cart-table');
    const emptyCartRow = document.querySelector('.empty-cart');
    
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
    
    // Add product to cart when clicked
    productItems.forEach(product => {
        product.addEventListener('click', function() {
            const productId = this.dataset.id;
            const productName = this.querySelector('.product-info h5').textContent;
            const productPrice = parseFloat(this.dataset.price);
            const productStock = parseInt(this.dataset.stock);
            
            if (productStock <= 0) {
                alert('This product is out of stock');
                return;
            }
            
            // Check if product already in cart
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                // Update quantity if product already in cart
                existingItem.quantity += 1;
                updateCartDisplay();
            } else {
                // Add new product to cart
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    quantity: 1,
                    total: productPrice
                });
                updateCartDisplay();
            }
        });
    });
    
    // Update cart display
    function updateCartDisplay() {
        // Hide empty cart message if cart has items
        if (cart.length > 0) {
            emptyCartRow.style.display = 'none';
        } else {
            emptyCartRow.style.display = '';
            emptyCartRow.querySelector('td').setAttribute('colspan', '5');
        }
        
        // Remove existing cart items
        const existingItems = cartTable.querySelectorAll('tbody tr:not(.empty-cart)');
        existingItems.forEach(item => item.remove());
        
        // Add cart items to table
        cart.forEach(item => {
            const row = document.createElement('tr');
            row.dataset.id = item.id;
            
            row.innerHTML = `
                <td>${item.name}</td>
                <td>
                    <div class="quantity-control">
                        <button class="quantity-btn minus"><i class="fas fa-minus"></i></button>
                        <span class="quantity-value">${item.quantity}</span>
                        <button class="quantity-btn plus"><i class="fas fa-plus"></i></button>
                    </div>
                </td>
                <td>$${item.price.toFixed(2)}</td>
                <td>$${(item.price * item.quantity).toFixed(2)}</td>
                <td>
                    <button class="remove-btn"><i class="fas fa-times"></i></button>
                </td>
            `;
            
            cartTable.querySelector('tbody').appendChild(row);
        });
        
        // Update totals
        updateCartTotals();
    }
    
    // Update cart totals
    function updateCartTotals() {
        const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        const discountType = document.getElementById('discount-type').value;
        const discountValue = parseFloat(document.getElementById('discount-amount').value || 0);
        
        let discountAmount = 0;
        if (discountType === 'fixed') {
            discountAmount = discountValue;
        } else { // percentage
            discountAmount = subtotal * (discountValue / 100);
        }
        
        const afterDiscount = subtotal - discountAmount;
        const tax = afterDiscount * taxRate;
        const total = afterDiscount + tax;
        
        // Update display
        document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
        document.getElementById('total').textContent = `$${total.toFixed(2)}`;
        
        // Also update payment modal total
        if (document.getElementById('payment-total-amount')) {
            document.getElementById('payment-total-amount').textContent = `$${total.toFixed(2)}`;
        }
    }
    
    // Handle quantity updates and item removal
    document.addEventListener('click', function(e) {
        // Handle quantity decrease button
        if (e.target.classList.contains('minus') || e.target.parentElement.classList.contains('minus')) {
            const row = e.target.closest('tr');
            if (!row) return;
            
            const productId = row.dataset.id;
            const item = cart.find(item => item.id === productId);
            
            if (item && item.quantity > 1) {
                item.quantity -= 1;
                updateCartDisplay();
            }
        }
        
        // Handle quantity increase button
        if (e.target.classList.contains('plus') || e.target.parentElement.classList.contains('plus')) {
            const row = e.target.closest('tr');
            if (!row) return;
            
            const productId = row.dataset.id;
            const item = cart.find(item => item.id === productId);
            
            if (item) {
                item.quantity += 1;
                updateCartDisplay();
            }
        }
        
        // Handle remove item button
        if (e.target.classList.contains('remove-btn') || e.target.parentElement.classList.contains('remove-btn')) {
            const row = e.target.closest('tr');
            if (!row) return;
            
            const productId = row.dataset.id;
            cart = cart.filter(item => item.id !== productId);
            updateCartDisplay();
        }
    });
    
    // Handle discount changes
    document.getElementById('discount-amount').addEventListener('input', updateCartTotals);
    document.getElementById('discount-type').addEventListener('change', updateCartTotals);
    
    // Handle payment modal
    const paymentModal = document.getElementById('payment-modal');
    const checkoutBtn = document.getElementById('checkout-btn');
    const closeModalBtns = document.querySelectorAll('.close, #cancel-payment-btn');
    
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            if (cart.length === 0) {
                alert('Cart is empty. Please add items to proceed to payment.');
                return;
            }
            
            // Update payment total before showing modal
            const total = document.getElementById('total').textContent;
            document.getElementById('payment-total-amount').textContent = total;
            
            // Set exact amount for cash payment
            const exactAmount = parseFloat(total.replace('$', ''));
            document.getElementById('cash-tendered').value = exactAmount.toFixed(2);
            document.getElementById('change-amount').textContent = '$0.00';
            
            paymentModal.style.display = 'block';
        });
    }
    
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            paymentModal.style.display = 'none';
        });
    });
    
    // Cash payment handling
    document.getElementById('cash-tendered').addEventListener('input', function() {
        const tendered = parseFloat(this.value || 0);
        const total = parseFloat(document.getElementById('total').textContent.replace('$', ''));
        const change = tendered - total;
        
        document.getElementById('change-amount').textContent = `$${Math.max(0, change).toFixed(2)}`;
    });
    
    // Quick cash buttons
    document.querySelectorAll('.quick-cash-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('exact-btn')) {
                const total = parseFloat(document.getElementById('total').textContent.replace('$', ''));
                document.getElementById('cash-tendered').value = total.toFixed(2);
            } else {
                const amount = parseFloat(this.dataset.amount);
                document.getElementById('cash-tendered').value = amount.toFixed(2);
            }
            
            // Trigger input event to calculate change
            document.getElementById('cash-tendered').dispatchEvent(new Event('input'));
        });
    });
    
    // Payment method switching
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Hide all payment sections
            document.querySelectorAll('.payment-section').forEach(section => section.style.display = 'none');
            
            // Show selected payment section
            const method = this.dataset.method;
            if (method === 'cash') {
                document.getElementById('cash-payment-section').style.display = 'block';
            } else if (method === 'credit_card' || method === 'debit_card') {
                document.getElementById('card-payment-section').style.display = 'block';
            } else if (method === 'mobile_payment') {
                document.getElementById('mobile-payment-section').style.display = 'block';
            }
        });
    });
    
    // Complete payment
    document.getElementById('complete-payment-btn').addEventListener('click', function() {
        if (cart.length === 0) {
            alert('Cart is empty.');
            return;
        }
        
        const paymentMethod = document.querySelector('.payment-method-btn.active').dataset.method;
        
        if (paymentMethod === 'cash') {
            const tendered = parseFloat(document.getElementById('cash-tendered').value || 0);
            const total = parseFloat(document.getElementById('total').textContent.replace('$', ''));
            
            if (tendered < total) {
                alert('Insufficient payment amount.');
                return;
            }
        }
        
        // Here you would normally send the sale data to the server
        alert('Sale completed successfully!');
        
        // Reset cart and close modal
        cart = [];
        updateCartDisplay();
        paymentModal.style.display = 'none';
    });
    
    // Cancel sale button
    document.getElementById('cancel-sale-btn').addEventListener('click', function() {
        if (cart.length > 0 && confirm('Are you sure you want to cancel this sale?')) {
            cart = [];
            updateCartDisplay();
        }
    });
    
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
