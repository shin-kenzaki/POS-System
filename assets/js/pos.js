// Add this function to your existing pos.js file or create it if it doesn't exist

document.addEventListener('DOMContentLoaded', function() {
    // Delegate event listener for quantity buttons
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
    
    // Function to update cart item (connect this to your existing cart update logic)
    function updateCartItem(productId, quantity) {
        // If you already have a function for updating cart items, call it here
        // Otherwise implement the update logic
        
        // Example:
        // 1. Update quantity in your cart data structure
        // 2. Recalculate item total (price * quantity)
        // 3. Recalculate cart subtotal, tax, and total
        
        // This is a placeholder - replace with your actual cart update logic
        console.log(`Updating product ${productId} to quantity ${quantity}`);
        
        // After updating the quantity, make sure to trigger price recalculations
        updateCartTotals();
    }
    
    // Placeholder for updating cart totals - connect to your existing function
    function updateCartTotals() {
        // Connect this to your existing cart total calculation function
        // or implement the calculation logic here
        
        // This will update the subtotal, tax, and total displays
    }
});
