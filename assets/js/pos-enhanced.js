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
            productSearch.focus(); // Focus on the search field after clearing
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
            const newCustomerBtn = document.getElementById('new-customer-btn');
            if (newCustomerBtn) newCustomerBtn.click();
        }
        
        // F3 - Focus Search
        if (e.key === 'F3') {
            e.preventDefault();
            if (productSearch) productSearch.focus();
        }
        
        // F8 - Hold Sale
        if (e.key === 'F8') {
            e.preventDefault();
            const holdSaleBtn = document.getElementById('hold-sale-btn');
            if (holdSaleBtn) holdSaleBtn.click();
        }
        
        // F12 - Payment
        if (e.key === 'F12') {
            e.preventDefault();
            const checkoutBtn = document.getElementById('checkout-btn');
            if (checkoutBtn && cart.length > 0) checkoutBtn.click();
        }
        
        // Escape - Various uses
        if (e.key === 'Escape') {
            // First priority: Close modals if open
            if (paymentModal && paymentModal.style.display === 'block') {
                paymentModal.style.display = 'none';
                return;
            }
            
            if (customerModal && customerModal.style.display === 'block') {
                customerModal.style.display = 'none';
                return;
            }
            
            if (heldSalesModal && heldSalesModal.style.display === 'block') {
                heldSalesModal.style.display = 'none';
                return;
            }
            
            // Second priority: Clear search if it has value
            if (productSearch && productSearch.value) {
                productSearch.value = '';
                productSearch.dispatchEvent(new Event('input'));
                return;
            }
            
            // Third priority: Close numpad if open
            if (numpad && numpad.style.display === 'block') {
                numpad.style.display = 'none';
            }
        }
    });
    
    // Handle cart expand/maximize button
    const cartExpandBtn = document.getElementById('cart-expand-btn');
    const productSearchContainer = document.querySelector('.product-search-container');
    const cartContainer = document.querySelector('.cart-container');
    let cartExpanded = false;
    
    if (cartExpandBtn) {
        cartExpandBtn.addEventListener('click', function() {
            if (cartExpanded) {
                // Restore to original layout
                productSearchContainer.style.display = '';
                cartContainer.style.gridColumn = '';
                cartExpandBtn.innerHTML = '<i class="fas fa-expand-alt"></i>';
                cartExpandBtn.title = "Expand Cart";
            } else {
                // Expand cart to full width
                productSearchContainer.style.display = 'none';
                cartContainer.style.gridColumn = '1 / -1';
                cartExpandBtn.innerHTML = '<i class="fas fa-compress-alt"></i>';
                cartExpandBtn.title = "Restore View";
            }
            cartExpanded = !cartExpanded;
        });
    }
    
    // Hold Sale functionality
    const holdSaleBtn = document.getElementById('hold-sale-btn');
    if (holdSaleBtn) {
        holdSaleBtn.addEventListener('click', function() {
            if (cart.length === 0) {
                alert('There are no items to hold.');
                return;
            }
            
            // Send cart to the server using fetch API
            const saleToHold = {
                items: cart,
                customer: document.getElementById('customer-select').value,
                note: '' // You could add a note field in the UI if needed
            };
            
            fetch('api/held_sales.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saleToHold)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear the cart after holding
                    cart = [];
                    updateCartDisplay();
                    alert('Sale has been placed on hold');
                } else {
                    alert('Error: ' + (data.message || 'Could not hold the sale'));
                }
            })
            .catch(error => {
                console.error('Error holding sale:', error);
                alert('Error holding sale. Please try again.');
            });
        });
    }
    
    // View Held Sales Functionality
    const viewHeldBtn = document.getElementById('view-held-btn');
    const heldSalesModal = document.getElementById('held-sales-modal');
    const heldSalesClose = document.querySelector('.held-sales-close');
    const closeHeldSalesBtn = document.getElementById('close-held-sales');
    const heldSalesContainer = document.getElementById('held-sales-container');
    
    if (viewHeldBtn && heldSalesModal) {
        viewHeldBtn.addEventListener('click', function() {
            displayHeldSales();
            heldSalesModal.style.display = 'block';
        });
    }
    
    if (heldSalesClose) {
        heldSalesClose.addEventListener('click', function() {
            heldSalesModal.style.display = 'none';
        });
    }
    
    if (closeHeldSalesBtn) {
        closeHeldSalesBtn.addEventListener('click', function() {
            heldSalesModal.style.display = 'none';
        });
    }
    
    function displayHeldSales() {
        if (!heldSalesContainer) return;
        
        // Show loading indicator
        heldSalesContainer.innerHTML = `
            <div class="no-held-sales">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading held sales...</p>
            </div>
        `;
        
        // Get held sales from server
        fetch('api/held_sales.php')
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.held_sales || data.held_sales.length === 0) {
                    heldSalesContainer.innerHTML = `
                        <div class="no-held-sales">
                            <i class="fas fa-pause-circle"></i>
                            <p>No held sales found</p>
                        </div>
                    `;
                    return;
                }
                
                // Generate HTML for held sales
                let html = '<div class="held-sales-list">';
                
                data.held_sales.forEach(sale => {
                    // Format date for display
                    const saleDate = new Date(sale.timestamp);
                    const formattedDate = saleDate.toLocaleDateString() + ' ' + saleDate.toLocaleTimeString();
                    
                    html += `
                        <div class="held-sale-item" data-id="${sale.id}">
                            <div class="held-sale-info">
                                <div class="held-sale-header">
                                    <h4>Held Sale #${sale.id.split('-')[1]}</h4>
                                    <span class="held-sale-date">${formattedDate}</span>
                                </div>
                                <div class="held-sale-customer">
                                    <i class="fas fa-user"></i> ${sale.customer_name}
                                </div>
                                <div class="held-sale-summary">
                                    <span>${sale.item_count} item(s)</span>
                                    <span class="held-sale-total">$${sale.total_amount.toFixed(2)}</span>
                                </div>
                            </div>
                            <div class="held-sale-actions">
                                <button class="btn-icon load-held-sale" title="Load Sale"><i class="fas fa-shopping-cart"></i></button>
                                <button class="btn-icon delete-held-sale" title="Delete Sale"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                heldSalesContainer.innerHTML = html;
                
                // Add event listeners for load and delete buttons
                document.querySelectorAll('.load-held-sale').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const saleId = this.closest('.held-sale-item').dataset.id;
                        loadHeldSale(saleId);
                    });
                });
                
                document.querySelectorAll('.delete-held-sale').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const saleId = this.closest('.held-sale-item').dataset.id;
                        deleteHeldSale(saleId);
                    });
                });
            })
            .catch(error => {
                console.error('Error fetching held sales:', error);
                heldSalesContainer.innerHTML = `
                    <div class="no-held-sales">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Error loading held sales</p>
                    </div>
                `;
            });
    }
    
    function loadHeldSale(saleId) {
        // Extract the numeric ID from the string format 'hold-123'
        const numericId = saleId.split('-')[1];
        
        fetch(`api/held_sales.php?id=${numericId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Could not find the held sale.');
                    return;
                }
                
                // Confirm if current cart has items
                if (cart.length > 0) {
                    if (!confirm('Loading a held sale will replace your current cart. Continue?')) {
                        return;
                    }
                }
                
                // Load the held items into cart
                cart = [...data.sale.items];
                
                // Set the customer if applicable
                if (data.sale.customer !== null && data.sale.customer !== '0') {
                    document.getElementById('customer-select').value = data.sale.customer;
                }
                
                // Update cart display
                updateCartDisplay();
                
                // Remove the held sale from server
                deleteHeldSale(saleId);
                
                // Close the modal
                heldSalesModal.style.display = 'none';
            })
            .catch(error => {
                console.error('Error loading held sale:', error);
                alert('Error loading the held sale. Please try again.');
            });
    }
    
    function deleteHeldSale(saleId) {
        if (!confirm('Are you sure you want to delete this held sale?')) {
            return;
        }
        
        fetch('api/held_sales.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: saleId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh the display
                displayHeldSales();
            } else {
                alert('Error: ' + (data.message || 'Could not delete the held sale'));
            }
        })
        .catch(error => {
            console.error('Error deleting held sale:', error);
            alert('Error deleting the held sale. Please try again.');
        });
    }
    
    // Enhance New Customer button functionality
    const newCustomerBtns = document.querySelectorAll('#new-customer-btn');
    const customerModal = document.getElementById('customer-modal');
    const customerModalClose = document.querySelector('.customer-close');
    const customerCancelBtn = document.querySelector('.customer-cancel');
    const customerForm = document.getElementById('customer-form');
    
    if (newCustomerBtns.length > 0) {
        newCustomerBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                customerModal.style.display = 'block';
            });
        });
    }
    
    if (customerModalClose) {
        customerModalClose.addEventListener('click', function() {
            customerModal.style.display = 'none';
        });
    }
    
    if (customerCancelBtn) {
        customerCancelBtn.addEventListener('click', function() {
            customerModal.style.display = 'none';
        });
    }
    
    if (customerForm) {
        customerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const name = document.getElementById('customer-name').value;
            const phone = document.getElementById('customer-phone').value;
            const email = document.getElementById('customer-email').value;
            const address = document.getElementById('customer-address').value;
            
            // Create form data object
            const formData = new FormData();
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('email', email);
            formData.append('address', address);
            
            // Save button reference and show loading state
            const saveButton = customerForm.querySelector('button[type="submit"]');
            const originalText = saveButton.textContent;
            saveButton.disabled = true;
            saveButton.textContent = 'Saving...';
            
            // Send data to the server
            fetch('api/customer_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add the new customer to the dropdown
                    const customerSelect = document.getElementById('customer-select');
                    const option = document.createElement('option');
                    option.value = data.customer.id;
                    option.textContent = `${data.customer.name} (${data.customer.phone || 'No phone'})`;
                    customerSelect.appendChild(option);
                    
                    // Select the newly added customer
                    customerSelect.value = data.customer.id;
                    
                    // Show success message
                    showNotification(`Customer "${name}" has been added successfully!`, 'success');
                    
                    // Reset form and close modal
                    customerForm.reset();
                    customerModal.style.display = 'none';
                } else {
                    // Show error message
                    showNotification(data.message || 'Could not save customer', 'error');
                }
            })
            .catch(error => {
                console.error('Error saving customer:', error);
                showNotification('Error saving customer. Please try again.', 'error');
            })
            .finally(() => {
                // Restore button state
                saveButton.disabled = false;
                saveButton.textContent = originalText;
            });
        });
    }
    
    // Add notification function if not already present
    function showNotification(message, type = 'info') {
        // Create notification container if it doesn't exist
        let container = document.querySelector('.notification-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            </div>
            <div class="notification-content">${message}</div>
        `;
        
        // Add to container
        container.appendChild(notification);
        
        // Remove after delay
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 500);
        }, 5000);
    }
    
    // Implement Numpad functionality
    const numpadButtons = document.querySelectorAll('.numpad-btn');
    const numpadInput = document.getElementById('numpad-input');
    const numpadActions = document.querySelectorAll('.numpad-action');
    
    if (numpadButtons.length > 0 && numpadInput) {
        numpadButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const value = this.textContent;
                
                if (value === 'Enter') {
                    // Process the entered value based on current action
                    const currentAction = document.querySelector('.numpad-action.active');
                    if (currentAction) {
                        processNumpadAction(currentAction.dataset.action, parseFloat(numpadInput.value));
                    }
                } else if (value === '.') {
                    // Only add decimal if there isn't one already
                    if (!numpadInput.value.includes('.')) {
                        numpadInput.value = numpadInput.value + '.';
                    }
                } else {
                    // For numbers, if the current value is just 0, replace it
                    if (numpadInput.value === '0' && value !== '.') {
                        numpadInput.value = value;
                    } else {
                        numpadInput.value = numpadInput.value + value;
                    }
                }
            });
        });
    }
    
    if (numpadActions.length > 0) {
        numpadActions.forEach(action => {
            action.addEventListener('click', function() {
                // Remove active class from all actions
                numpadActions.forEach(a => a.classList.remove('active'));
                
                // Add active class to clicked action
                this.classList.add('active');
                
                // Reset numpad input
                numpadInput.value = '0';
                
                // Update numpad title based on action
                const numpadHeader = document.querySelector('.numpad-header h3');
                if (numpadHeader) {
                    switch(this.dataset.action) {
                        case 'quantity':
                            numpadHeader.textContent = 'Enter Quantity';
                            break;
                        case 'discount':
                            numpadHeader.textContent = 'Enter Discount';
                            break;
                        case 'price':
                            numpadHeader.textContent = 'Enter Custom Price';
                            break;
                        default:
                            numpadHeader.textContent = 'Numpad';
                    }
                }
            });
        });
    }
    
    // Function to process numpad actions
    function processNumpadAction(action, value) {
        if (!action || isNaN(value)) return;
        
        const selectedRow = document.querySelector('#cart-table tr.selected');
        
        switch(action) {
            case 'quantity':
                if (selectedRow && value > 0) {
                    const productId = selectedRow.dataset.id;
                    const item = cart.find(item => item.id === productId);
                    if (item) {
                        item.quantity = value;
                        updateCartDisplay();
                    }
                } else {
                    alert('Please select a cart item first to change quantity');
                }
                break;
                
            case 'discount':
                document.getElementById('discount-amount').value = value;
                document.getElementById('discount-type').value = 'fixed';
                updateCartTotals();
                numpad.style.display = 'none';
                break;
                
            case 'price':
                if (selectedRow && value > 0) {
                    const productId = selectedRow.dataset.id;
                    const item = cart.find(item => item.id === productId);
                    if (item) {
                        item.price = value;
                        updateCartDisplay();
                    }
                } else {
                    alert('Please select a cart item first to change price');
                }
                break;
        }
    }
    
    // Add click handler to select cart rows for actions
    document.addEventListener('click', function(e) {
        const cartRow = e.target.closest('#cart-table tbody tr:not(.empty-cart)');
        if (cartRow) {
            // Remove selected class from all rows
            document.querySelectorAll('#cart-table tbody tr').forEach(row => {
                row.classList.remove('selected');
            });
            
            // Add selected class to clicked row
            cartRow.classList.add('selected');
        }
    });
    
    // Enhanced Payment Note functionality
    const noteHeader = document.querySelector('.note-header');
    const noteToggle = document.querySelector('.note-toggle');
    const noteContent = document.querySelector('.note-content');
    const paymentNote = document.getElementById('payment-note');
    const characterCount = document.querySelector('.character-count');
    
    if (noteHeader && noteContent) {
        noteHeader.addEventListener('click', function(e) {
            // Don't toggle if the textarea itself was clicked
            if (e.target === paymentNote) return;
            
            noteContent.classList.toggle('collapsed');
            noteToggle.classList.toggle('collapsed');
            
            // Change icon based on state
            const icon = noteToggle.querySelector('i');
            if (noteContent.classList.contains('collapsed')) {
                icon.className = 'fas fa-chevron-right';
            } else {
                icon.className = 'fas fa-chevron-down';
            }
        });
    }
    
    // Character counter for payment note
    if (paymentNote && characterCount) {
        const maxLength = 200; // Maximum characters allowed
        
        // Set maxlength attribute on textarea
        paymentNote.setAttribute('maxlength', maxLength);
        
        paymentNote.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            characterCount.textContent = `${this.value.length}/${maxLength} characters`;
            
            // Update color based on remaining characters
            characterCount.classList.remove('limit-near', 'limit-reached');
            if (this.value.length > maxLength * 0.8) {
                characterCount.classList.add('limit-near');
            }
            if (this.value.length >= maxLength) {
                characterCount.classList.add('limit-reached');
            }
        });
    }
    
    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn-primary, .btn-secondary, .btn-warning, .btn-success, .category-btn, .payment-method-btn, .quick-cash-btn');
    
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Remove existing ripples
            const existingRipples = this.querySelectorAll('.ripple');
            existingRipples.forEach(ripple => ripple.remove());
            
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            
            const diameter = Math.max(this.offsetWidth, this.offsetHeight);
            const radius = diameter / 2;
            
            ripple.style.width = ripple.style.height = `${diameter}px`;
            ripple.style.left = `${e.offsetX - radius}px`;
            ripple.style.top = `${e.offsetY - radius}px`;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Fix for payment method buttons to ensure only one is active
    const paymentMethodButtons = document.querySelectorAll('.payment-method-btn');
    paymentMethodButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            paymentMethodButtons.forEach(b => b.classList.remove('active'));
            
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
    
    // Make buttons provide tactile feedback
    const allButtons = document.querySelectorAll('button, .btn, .btn-icon');
    allButtons.forEach(button => {
        button.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        button.addEventListener('mouseup', function() {
            this.style.transform = '';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
});
