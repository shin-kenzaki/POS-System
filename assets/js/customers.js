document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const customerTable = document.getElementById('customers-table');
    const customerSearch = document.getElementById('customer-search');
    const loyaltyFilter = document.getElementById('loyalty-filter');
    const paginationControls = document.getElementById('pagination-controls');
    
    // Customer modal elements
    const customerModal = document.getElementById('customer-modal');
    const viewCustomerModal = document.getElementById('view-customer-modal');
    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const customerForm = document.getElementById('customer-form');
    
    // Buttons
    const addCustomerBtn = document.getElementById('add-customer-btn');
    const cancelCustomerBtn = document.getElementById('cancel-customer');
    const closeViewCustomerBtn = document.getElementById('close-view-customer');
    const editFromViewBtn = document.getElementById('edit-from-view');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    
    // Pagination variables
    let currentPage = 1;
    const itemsPerPage = 10;
    let totalCustomers = 0;
    let filteredCustomers = [];
    
    // Load customers when page loads
    loadCustomers();
    
    // Search functionality
    customerSearch.addEventListener('input', function() {
        currentPage = 1;
        filterCustomers();
    });
    
    // Loyalty filter
    loyaltyFilter.addEventListener('change', function() {
        currentPage = 1;
        filterCustomers();
    });
    
    // Add new customer button
    addCustomerBtn.addEventListener('click', function() {
        document.getElementById('modal-title').textContent = 'Add New Customer';
        customerForm.reset();
        document.getElementById('customer-id').value = '';
        document.getElementById('loyalty-points-container').style.display = 'none';
        customerModal.style.display = 'block';
    });
    
    // Cancel button in customer form
    cancelCustomerBtn.addEventListener('click', function() {
        customerModal.style.display = 'none';
    });
    
    // Close button in view customer modal
    closeViewCustomerBtn.addEventListener('click', function() {
        viewCustomerModal.style.display = 'none';
    });
    
    // Edit button in view customer modal
    editFromViewBtn.addEventListener('click', function() {
        const customerId = viewCustomerModal.dataset.customerId;
        viewCustomerModal.style.display = 'none';
        editCustomer(customerId);
    });
    
    // Cancel delete
    cancelDeleteBtn.addEventListener('click', function() {
        deleteConfirmModal.style.display = 'none';
    });
    
    // Confirm delete
    confirmDeleteBtn.addEventListener('click', function() {
        const customerId = deleteConfirmModal.dataset.customerId;
        deleteCustomer(customerId);
    });
    
    // Close modals when clicking on the X button
    document.querySelectorAll('.modal .close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
    
    // Handle customer form submission
    customerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const customerId = document.getElementById('customer-id').value;
        const saveButton = document.getElementById('save-customer');
        
        // Disable button during submission
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
        
        // Determine if this is an add or update operation
        const url = 'api/customer_handler.php' + (customerId ? `?customer_id=${customerId}` : '');
        const method = customerId ? 'PUT' : 'POST';
        
        // Send request
        fetch(url, {
            method: method,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, true);
                customerModal.style.display = 'none';
                loadCustomers();
            } else {
                showToast(data.message, false);
            }
        })
        .catch(error => {
            console.error('Error saving customer:', error);
            showToast('An error occurred while saving the customer', false);
        })
        .finally(() => {
            saveButton.disabled = false;
            saveButton.textContent = 'Save Customer';
        });
    });
    
    // Function to load customers
    function loadCustomers() {
        const loadingRow = customerTable.querySelector('.loading-row');
        if (!loadingRow) {
            const tbody = customerTable.querySelector('tbody');
            tbody.innerHTML = '<tr class="loading-row"><td colspan="8" class="text-center">Loading customers...</td></tr>';
        }
        
        fetch('api/customer_handler.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    filteredCustomers = data.customers;
                    totalCustomers = filteredCustomers.length;
                    filterCustomers();
                } else {
                    showToast(data.message, false);
                    customerTable.querySelector('tbody').innerHTML = 
                        '<tr><td colspan="8" class="text-center">Failed to load customers</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading customers:', error);
                customerTable.querySelector('tbody').innerHTML = 
                    '<tr><td colspan="8" class="text-center">Error loading customers</td></tr>';
            });
    }
    
    // Function to filter customers
    function filterCustomers() {
        const searchTerm = customerSearch.value.toLowerCase();
        const loyaltyValue = loyaltyFilter.value;
        
        // Apply filters
        const filteredResults = filteredCustomers.filter(customer => {
            const matchesSearch = 
                customer.name.toLowerCase().includes(searchTerm) ||
                (customer.email && customer.email.toLowerCase().includes(searchTerm)) ||
                (customer.phone && customer.phone.includes(searchTerm));
                
            // Apply loyalty filter
            let matchesLoyalty = true;
            if (loyaltyValue) {
                const points = parseInt(customer.loyalty_points || 0);
                if (loyaltyValue === 'high') matchesLoyalty = points >= 100;
                else if (loyaltyValue === 'medium') matchesLoyalty = points >= 50 && points < 100;
                else if (loyaltyValue === 'low') matchesLoyalty = points > 0 && points < 50;
                else if (loyaltyValue === 'none') matchesLoyalty = points === 0;
            }
            
            return matchesSearch && matchesLoyalty;
        });
        
        // Update the display
        displayCustomers(filteredResults);
        updatePagination(filteredResults.length);
    }
    
    // Function to display customers
    function displayCustomers(customers) {
        const tbody = customerTable.querySelector('tbody');
        
        // Calculate pagination
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, customers.length);
        const customersToShow = customers.slice(startIndex, endIndex);
        
        if (customersToShow.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No customers found</td></tr>';
            return;
        }
        
        // Generate table rows
        tbody.innerHTML = customersToShow.map(customer => `
            <tr data-id="${customer.customer_id}">
                <td>${customer.customer_id}</td>
                <td>${customer.name}</td>
                <td>${customer.email || '-'}</td>
                <td>${customer.phone || '-'}</td>
                <td>${customer.loyalty_points || '0'}</td>
                <td class="truncate-text">${customer.address || '-'}</td>
                <td>${new Date(customer.created_at).toLocaleDateString()}</td>
                <td class="actions">
                    <button class="btn-icon view-customer" title="View Details"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon edit-customer" title="Edit Customer"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon delete-customer" title="Delete Customer"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
        
        // Add event listeners to action buttons
        addActionButtonListeners();
    }
    
    // Function to update pagination controls
    function updatePagination(totalItems) {
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        let paginationHTML = '';
        
        if (totalPages > 1) {
            paginationHTML += `<button class="pagination-btn ${currentPage === 1 ? 'disabled' : ''}" 
                data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>`;
            
            const maxPages = 5;
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + maxPages - 1);
            
            if (endPage - startPage < maxPages - 1) {
                startPage = Math.max(1, endPage - maxPages + 1);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" 
                    data-page="${i}">${i}</button>`;
            }
            
            paginationHTML += `<button class="pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" 
                data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>`;
        }
        
        paginationControls.innerHTML = paginationHTML;
        
        // Add event listeners to pagination buttons
        document.querySelectorAll('.pagination-btn:not(.disabled)').forEach(btn => {
            btn.addEventListener('click', function() {
                currentPage = parseInt(this.dataset.page);
                filterCustomers();
            });
        });
    }
    
    // Add event listeners to action buttons in the table
    function addActionButtonListeners() {
        document.querySelectorAll('.view-customer').forEach(btn => {
            btn.addEventListener('click', function() {
                const customerId = this.closest('tr').dataset.id;
                viewCustomer(customerId);
            });
        });
        
        document.querySelectorAll('.edit-customer').forEach(btn => {
            btn.addEventListener('click', function() {
                const customerId = this.closest('tr').dataset.id;
                editCustomer(customerId);
            });
        });
        
        document.querySelectorAll('.delete-customer').forEach(btn => {
            btn.addEventListener('click', function() {
                const customerId = this.closest('tr').dataset.id;
                const customerName = this.closest('tr').cells[1].textContent;
                confirmDelete(customerId, customerName);
            });
        });
    }
    
    // Function to view customer details
    function viewCustomer(customerId) {
        fetch(`api/customer_handler.php?customer_id=${customerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.customer) {
                    const customer = data.customer;
                    
                    // Set customer details in the view modal
                    document.getElementById('view-customer-name').textContent = customer.name;
                    document.getElementById('view-customer-email').textContent = customer.email || '-';
                    document.getElementById('view-customer-phone').textContent = customer.phone || '-';
                    document.getElementById('view-customer-address').textContent = customer.address || '-';
                    document.getElementById('view-customer-created').textContent = new Date(customer.created_at).toLocaleString();
                    document.getElementById('view-customer-updated').textContent = new Date(customer.updated_at).toLocaleString();
                    
                    // Set loyalty badge
                    const loyaltyBadge = document.getElementById('loyalty-badge');
                    loyaltyBadge.textContent = `${customer.loyalty_points || 0} Points`;
                    
                    // Clear loyalty badge classes
                    loyaltyBadge.classList.remove('high', 'medium', 'low', 'none');
                    
                    // Add appropriate class based on points
                    const points = parseInt(customer.loyalty_points || 0);
                    if (points >= 100) loyaltyBadge.classList.add('high');
                    else if (points >= 50) loyaltyBadge.classList.add('medium');
                    else if (points > 0) loyaltyBadge.classList.add('low');
                    else loyaltyBadge.classList.add('none');
                    
                    // Store customer ID for edit button
                    viewCustomerModal.dataset.customerId = customerId;
                    
                    // Load purchase history if available
                    if (data.purchases && data.purchases.length > 0) {
                        const purchasesContainer = document.querySelector('.purchases-container');
                        purchasesContainer.innerHTML = `
                            <table class="purchases-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.purchases.map(purchase => `
                                        <tr>
                                            <td>${new Date(purchase.sale_date).toLocaleDateString()}</td>
                                            <td>$${parseFloat(purchase.total_amount).toFixed(2)}</td>
                                            <td>${purchase.item_count}</td>
                                            <td><span class="status ${purchase.payment_status.toLowerCase()}">${purchase.payment_status}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        `;
                    } else {
                        document.querySelector('.purchases-container').innerHTML = 
                            '<p class="no-purchases">No purchase history available</p>';
                    }
                    
                    // Show the modal
                    viewCustomerModal.style.display = 'block';
                } else {
                    showToast('Failed to load customer details', false);
                }
            })
            .catch(error => {
                console.error('Error viewing customer:', error);
                showToast('An error occurred while loading customer details', false);
            });
    }
    
    // Function to prepare edit customer form
    function editCustomer(customerId) {
        fetch(`api/customer_handler.php?customer_id=${customerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.customer) {
                    const customer = data.customer;
                    
                    // Set form values
                    document.getElementById('modal-title').textContent = 'Edit Customer';
                    document.getElementById('customer-id').value = customer.customer_id;
                    document.getElementById('customer-name').value = customer.name;
                    document.getElementById('customer-email').value = customer.email || '';
                    document.getElementById('customer-phone').value = customer.phone || '';
                    document.getElementById('customer-address').value = customer.address || '';
                    document.getElementById('customer-loyalty').value = customer.loyalty_points || 0;
                    
                    // Show loyalty points field for existing customers
                    document.getElementById('loyalty-points-container').style.display = 'block';
                    
                    // Show the modal
                    customerModal.style.display = 'block';
                } else {
                    showToast('Failed to load customer data for editing', false);
                }
            })
            .catch(error => {
                console.error('Error editing customer:', error);
                showToast('An error occurred while loading customer data', false);
            });
    }
    
    // Function to confirm customer deletion
    function confirmDelete(customerId, customerName) {
        document.getElementById('delete-customer-name').textContent = customerName;
        deleteConfirmModal.dataset.customerId = customerId;
        deleteConfirmModal.style.display = 'block';
    }
    
    // Function to delete a customer
    function deleteCustomer(customerId) {
        fetch(`api/customer_handler.php?customer_id=${customerId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, true);
                deleteConfirmModal.style.display = 'none';
                loadCustomers();
            } else {
                showToast(data.message, false);
            }
        })
        .catch(error => {
            console.error('Error deleting customer:', error);
            showToast('An error occurred while deleting the customer', false);
        });
    }
    
    // Function to show toast notifications
    function showToast(message, isSuccess) {
        // Check if toast container exists, if not create it
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${isSuccess ? 'success' : 'error'}`;
        
        const icon = document.createElement('i');
        icon.className = `fas ${isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
        toast.appendChild(icon);
        
        const messageEl = document.createElement('div');
        messageEl.textContent = message;
        toast.appendChild(messageEl);
        
        toastContainer.appendChild(toast);
        
        // Show toast with animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
});
