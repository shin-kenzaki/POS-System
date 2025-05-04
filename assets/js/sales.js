document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const exportBtn = document.getElementById('export-sales-btn');
    const applyFiltersBtn = document.getElementById('apply-filters');
    const resetFiltersBtn = document.getElementById('reset-filters');
    const viewButtons = document.querySelectorAll('.view-sale');
    const printReceiptButtons = document.querySelectorAll('.print-receipt');
    const processRefundButtons = document.querySelectorAll('.process-refund');
    const closeButtons = document.querySelectorAll('.close');
    const closeViewSaleBtn = document.getElementById('close-view-sale');
    const closeReceiptBtn = document.getElementById('close-receipt-btn');
    const cancelRefundBtn = document.getElementById('cancel-refund');
    const printViewSaleBtn = document.getElementById('print-view-sale');
    const printReceiptBtn = document.getElementById('print-receipt-btn');
    const refundForm = document.getElementById('refund-form');
    const refundReasonSelect = document.getElementById('refund-reason');
    const otherReasonGroup = document.getElementById('other-reason-group');
    
    // Modals
    const viewSaleModal = document.getElementById('view-sale-modal');
    const receiptModal = document.getElementById('receipt-modal');
    const refundModal = document.getElementById('refund-modal');

    // Event Listeners
    
    // Export sales
    if (exportBtn) {
        exportBtn.addEventListener('click', exportSales);
    }

    // Apply filters
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyFilters);
    }

    // Reset filters
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
    }

    // View sale details
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const saleId = this.getAttribute('data-id');
            viewSaleDetails(saleId);
        });
    });

    // Print receipt
    printReceiptButtons.forEach(button => {
        button.addEventListener('click', function() {
            const saleId = this.getAttribute('data-id');
            showReceipt(saleId);
        });
    });

    // Process refund
    processRefundButtons.forEach(button => {
        button.addEventListener('click', function() {
            const saleId = this.getAttribute('data-id');
            showRefundForm(saleId);
        });
    });

    // Close all modals
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Specific close buttons
    if (closeViewSaleBtn) {
        closeViewSaleBtn.addEventListener('click', function() {
            viewSaleModal.style.display = 'none';
        });
    }

    if (closeReceiptBtn) {
        closeReceiptBtn.addEventListener('click', function() {
            receiptModal.style.display = 'none';
        });
    }

    if (cancelRefundBtn) {
        cancelRefundBtn.addEventListener('click', function() {
            refundModal.style.display = 'none';
        });
    }

    // Print view sale
    if (printViewSaleBtn) {
        printViewSaleBtn.addEventListener('click', function() {
            printSale();
        });
    }

    // Print receipt
    if (printReceiptBtn) {
        printReceiptBtn.addEventListener('click', function() {
            printReceipt();
        });
    }

    // Show/hide other reason field
    if (refundReasonSelect) {
        refundReasonSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                otherReasonGroup.style.display = 'block';
                document.getElementById('other-reason').required = true;
            } else {
                otherReasonGroup.style.display = 'none';
                document.getElementById('other-reason').required = false;
            }
        });
    }

    // Submit refund form
    if (refundForm) {
        refundForm.addEventListener('submit', function(event) {
            event.preventDefault();
            processRefund();
        });
    }

    // Functions

    // Export sales as CSV
    function exportSales() {
        const dateFrom = document.getElementById('date-from').value;
        const dateTo = document.getElementById('date-to').value;
        const status = document.getElementById('status-filter').value;
        const paymentMethod = document.getElementById('payment-method-filter').value;
        const search = document.getElementById('sales-search').value;

        let url = 'api/export_sales.php?';
        const params = [];

        if (dateFrom) params.push(`date_from=${encodeURIComponent(dateFrom)}`);
        if (dateTo) params.push(`date_to=${encodeURIComponent(dateTo)}`);
        if (status) params.push(`status=${encodeURIComponent(status)}`);
        if (paymentMethod) params.push(`payment_method=${encodeURIComponent(paymentMethod)}`);
        if (search) params.push(`search=${encodeURIComponent(search)}`);

        url += params.join('&');

        // Open in a new tab to download the file
        window.open(url, '_blank');
    }

    // Apply search and filters
    function applyFilters() {
        // In a real implementation, this would fetch filtered data from server
        // For now, we'll just reload the page with query parameters
        const dateFrom = document.getElementById('date-from').value;
        const dateTo = document.getElementById('date-to').value;
        const status = document.getElementById('status-filter').value;
        const paymentMethod = document.getElementById('payment-method-filter').value;
        const search = document.getElementById('sales-search').value;

        let url = 'sales.php?';
        const params = [];

        if (dateFrom) params.push(`date_from=${encodeURIComponent(dateFrom)}`);
        if (dateTo) params.push(`date_to=${encodeURIComponent(dateTo)}`);
        if (status) params.push(`status=${encodeURIComponent(status)}`);
        if (paymentMethod) params.push(`payment_method=${encodeURIComponent(paymentMethod)}`);
        if (search) params.push(`search=${encodeURIComponent(search)}`);

        url += params.join('&');
        window.location.href = url;
    }

    // Reset filters
    function resetFilters() {
        document.getElementById('date-from').value = '';
        document.getElementById('date-to').value = '';
        document.getElementById('status-filter').value = '';
        document.getElementById('payment-method-filter').value = '';
        document.getElementById('sales-search').value = '';
    }

    // View sale details
    function viewSaleDetails(saleId) {
        fetch(`api/get_sale.php?sale_id=${saleId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.sale) {
                    const sale = data.sale;
                    
                    // Update sale header
                    document.getElementById('sale-id-badge').textContent = '#SALE-' + sale.sale_id.toString().padStart(6, '0');
                    
                    // Update basic information
                    document.getElementById('sale-date').textContent = new Date(sale.sale_date).toLocaleString();
                    document.getElementById('sale-customer').textContent = sale.customer_name || 'Walk-in Customer';
                    
                    const statusBadge = document.getElementById('sale-status');
                    statusBadge.textContent = capitalizeFirstLetter(sale.payment_status);
                    statusBadge.className = 'status-badge ' + sale.payment_status;
                    
                    document.getElementById('sale-payment-method').textContent = capitalizeFirstLetter(sale.payment_method.replace(/_/g, ' '));
                    document.getElementById('sale-reference').textContent = sale.payment_reference || '-';
                    document.getElementById('sale-user').textContent = sale.cashier_name || 'System';
                    document.getElementById('sale-notes').textContent = sale.notes || '-';
                    
                    // Update items purchased
                    const saleItemsContainer = document.getElementById('sale-items');
                    if (sale.items && sale.items.length > 0) {
                        saleItemsContainer.innerHTML = sale.items.map(item => `
                            <tr>
                                <td>${item.name}</td>
                                <td>${item.quantity}</td>
                                <td>$${parseFloat(item.unit_price).toFixed(2)}</td>
                                <td>$${parseFloat(item.discount).toFixed(2)}</td>
                                <td>$${parseFloat(item.subtotal).toFixed(2)}</td>
                            </tr>
                        `).join('');
                    } else {
                        saleItemsContainer.innerHTML = '<tr><td colspan="5" class="text-center">No items found</td></tr>';
                    }
                    
                    // Update summary
                    document.getElementById('sale-subtotal').textContent = '$' + parseFloat(sale.subtotal).toFixed(2);
                    document.getElementById('sale-tax').textContent = '$' + parseFloat(sale.tax_amount).toFixed(2);
                    document.getElementById('sale-discount').textContent = '$' + parseFloat(sale.discount_amount).toFixed(2);
                    document.getElementById('sale-total').textContent = '$' + parseFloat(sale.total_amount).toFixed(2);
                    
                    // Show the modal
                    viewSaleModal.style.display = 'block';
                } else {
                    alert('Error loading sale details: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading sale details. Please try again.');
            });
    }

    // Show receipt
    function showReceipt(saleId) {
        document.getElementById('receipt-content').innerHTML = '<div class="receipt-loading">Loading receipt...</div>';
        receiptModal.style.display = 'block';
        
        fetch(`api/get_receipt.php?sale_id=${saleId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('receipt-content').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('receipt-content').innerHTML = '<div class="error-message">Error loading receipt. Please try again.</div>';
            });
    }

    // Show refund form
    function showRefundForm(saleId) {
        document.getElementById('refund-sale-id').value = saleId;
        document.getElementById('refund-items').innerHTML = '<tr><td colspan="5" class="text-center">Loading items...</td></tr>';
        refundModal.style.display = 'block';
        
        // Reset form
        refundForm.reset();
        otherReasonGroup.style.display = 'none';
        
        fetch(`api/get_sale.php?sale_id=${saleId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.sale) {
                    const sale = data.sale;
                    const refundItemsContainer = document.getElementById('refund-items');
                    
                    if (sale.items && sale.items.length > 0) {
                        refundItemsContainer.innerHTML = sale.items.map(item => `
                            <tr data-item-id="${item.sale_item_id}" data-price="${item.unit_price}">
                                <td>
                                    <input type="checkbox" name="refund_item_${item.sale_item_id}" value="1" 
                                        class="refund-item-checkbox" data-id="${item.sale_item_id}" onclick="updateRefundTotal()">
                                </td>
                                <td>${item.name}</td>
                                <td>${item.quantity}</td>
                                <td>
                                    <input type="number" min="1" max="${item.quantity}" value="1" 
                                        class="refund-quantity" data-id="${item.sale_item_id}" 
                                        onchange="updateRefundTotal()" disabled>
                                </td>
                                <td>$<span class="refund-amount" data-id="${item.sale_item_id}">
                                    ${parseFloat(item.unit_price).toFixed(2)}
                                </span></td>
                            </tr>
                        `).join('');
                        
                        // Add event listeners to checkboxes
                        document.querySelectorAll('.refund-item-checkbox').forEach(checkbox => {
                            checkbox.addEventListener('change', function() {
                                const row = this.closest('tr');
                                const quantityInput = row.querySelector('.refund-quantity');
                                if (this.checked) {
                                    quantityInput.disabled = false;
                                } else {
                                    quantityInput.disabled = true;
                                }
                                updateRefundTotal();
                            });
                        });
                        
                        // Add event listeners to quantity inputs
                        document.querySelectorAll('.refund-quantity').forEach(input => {
                            input.addEventListener('change', function() {
                                updateRefundTotal();
                            });
                        });
                    } else {
                        refundItemsContainer.innerHTML = '<tr><td colspan="5" class="text-center">No items found</td></tr>';
                    }
                    
                    // Initialize refund total
                    updateRefundTotal();
                } else {
                    alert('Error loading sale details: ' + (data.message || 'Unknown error'));
                    refundModal.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading sale details. Please try again.');
                refundModal.style.display = 'none';
            });
    }

    // Process refund
    function processRefund() {
        const saleId = document.getElementById('refund-sale-id').value;
        const reason = document.getElementById('refund-reason').value;
        const otherReason = document.getElementById('other-reason').value;
        const notes = document.getElementById('refund-notes').value;
        
        // Collect selected items
        const selectedItems = [];
        document.querySelectorAll('.refund-item-checkbox:checked').forEach(checkbox => {
            const itemId = checkbox.getAttribute('data-id');
            const row = checkbox.closest('tr');
            const quantity = parseInt(row.querySelector('.refund-quantity').value);
            
            selectedItems.push({
                sale_item_id: itemId,
                quantity: quantity,
                selected: 1
            });
        });
        
        if (selectedItems.length === 0) {
            alert('Please select at least one item to refund.');
            return;
        }
        
        // Disable the submit button
        const submitButton = document.getElementById('process-refund-btn');
        submitButton.disabled = true;
        submitButton.textContent = 'Processing...';
        
        // Prepare form data
        const formData = new FormData();
        formData.append('sale_id', saleId);
        formData.append('reason', reason);
        if (reason === 'other') {
            formData.append('other_reason', otherReason);
        }
        formData.append('notes', notes);
        formData.append('refund_items', JSON.stringify(selectedItems));
        
        // Send request
        fetch('api/process_refund.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Refund processed successfully!');
                refundModal.style.display = 'none';
                // Reload the page to show updated sale status
                window.location.reload();
            } else {
                alert('Error processing refund: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing refund. Please try again.');
        })
        .finally(() => {
            // Re-enable the submit button
            submitButton.disabled = false;
            submitButton.textContent = 'Process Refund';
        });
    }

    // Print functions
    function printSale() {
        const printContent = document.querySelector('.sale-detail-container').innerHTML;
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>Sale Details</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h2, h3 { margin-bottom: 10px; }
                        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background-color: #f2f2f2; }
                        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; }
                        .completed { background-color: #d4edda; color: #155724; }
                        .refunded { background-color: #f8d7da; color: #721c24; }
                        .pending { background-color: #fff3cd; color: #856404; }
                        .summary-row { display: flex; justify-content: space-between; padding: 5px 0; }
                        .total-row { font-weight: bold; border-top: 1px solid #ddd; padding-top: 10px; }
                    </style>
                </head>
                <body>
                    <h2>Sale Details: ${document.getElementById('sale-id-badge').textContent}</h2>
                    ${printContent}
                </body>
            </html>
        `);
        
        printWindow.document.close();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.onafterprint = function() {
                printWindow.close();
            };
        }, 500);
    }

    function printReceipt() {
        const printContent = document.getElementById('receipt-content').innerHTML;
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>Receipt</title>
                    <style>
                        body { font-family: 'Courier New', monospace; font-size: 12px; margin: 0; padding: 10px; }
                        .receipt-header { text-align: center; margin-bottom: 10px; }
                        .receipt-header h2 { margin: 0; font-size: 16px; }
                        .receipt-header p { margin: 5px 0; }
                        .receipt-items { width: 100%; border-top: 1px dashed #000; border-bottom: 1px dashed #000; margin: 10px 0; padding: 10px 0; }
                        .receipt-item { margin-bottom: 5px; }
                        .receipt-summary { text-align: right; }
                        .receipt-summary div { margin: 5px 0; }
                        .receipt-total { font-weight: bold; font-size: 14px; }
                        .receipt-footer { text-align: center; margin-top: 10px; font-size: 11px; }
                        .refunded-status { color: #dc3545; font-weight: bold; text-align: center; margin-top: 10px; border-top: 1px dashed #000; padding-top: 5px; }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
            </html>
        `);
        
        printWindow.document.close();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.onafterprint = function() {
                printWindow.close();
            };
        }, 500);
    }

    // Helper function to update refund total - make this available globally
    window.updateRefundTotal = function() {
        let total = 0;
        document.querySelectorAll('.refund-item-checkbox:checked').forEach(checkbox => {
            const row = checkbox.closest('tr');
            const quantity = parseInt(row.querySelector('.refund-quantity').value);
            const price = parseFloat(row.getAttribute('data-price'));
            const amount = quantity * price;
            
            row.querySelector('.refund-amount').textContent = amount.toFixed(2);
            total += amount;
        });
        
        document.getElementById('refund-total').textContent = '$' + total.toFixed(2);
    };

    // Helper function to capitalize first letter
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
});
