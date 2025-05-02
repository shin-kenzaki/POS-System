document.addEventListener('DOMContentLoaded', function() {
    // Make sure Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error("Chart.js library is not loaded!");
        document.querySelectorAll('.chart').forEach(chart => {
            chart.innerHTML = "<p class='error'>Chart could not be loaded. Please refresh the page.</p>";
        });
        return;
    }

    try {
        // Sales Chart
        const salesCtx = document.getElementById('salesChart');
        if (!salesCtx) {
            console.error("Sales chart canvas not found");
        } else {
            const salesChart = new Chart(salesCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Sales 2023',
                        data: [15000, 17000, 16500, 19000, 18500, 21000, 22500, 23000, 24500, 26000, 27500, 29000],
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(52, 152, 219, 1)'
                    }, {
                        label: 'Sales 2022',
                        data: [12000, 14500, 13500, 14000, 15500, 17000, 18500, 19000, 20500, 22000, 23500, 25000],
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(46, 204, 113, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Product Chart
        const productCtx = document.getElementById('productChart');
        if (!productCtx) {
            console.error("Product chart canvas not found");
        } else {
            const productChart = new Chart(productCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
                    datasets: [{
                        label: 'Units Sold',
                        data: [127, 96, 84, 71, 65],
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(155, 89, 182, 0.7)',
                            'rgba(241, 196, 15, 0.7)',
                            'rgba(231, 76, 60, 0.7)'
                        ],
                        borderColor: [
                            'rgba(52, 152, 219, 1)',
                            'rgba(46, 204, 113, 1)',
                            'rgba(155, 89, 182, 1)',
                            'rgba(241, 196, 15, 1)',
                            'rgba(231, 76, 60, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error("Error initializing charts:", error);
        document.querySelectorAll('.chart').forEach(chart => {
            chart.innerHTML = "<p class='error'>Chart could not be loaded due to an error. Please check console for details.</p>";
        });
    }

    // Responsive sidebar toggle - fixed implementation
    const createToggleButton = () => {
        // Remove any existing toggle button first
        const existingBtn = document.querySelector('.sidebar-toggle');
        if (existingBtn) existingBtn.remove();
        
        // Create toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.classList.add('sidebar-toggle');
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.style.display = window.innerWidth <= 576 ? 'block' : 'none';
        
        // Append to beginning of topbar
        const topBar = document.querySelector('.top-bar');
        if (topBar) {
            topBar.prepend(toggleBtn);
            
            // Add event listener
            toggleBtn.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) sidebar.classList.toggle('active');
            });
        }
    };

    // Create toggle button initially
    createToggleButton();
    
    // Recreate on resize
    window.addEventListener('resize', function() {
        createToggleButton();
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 576) {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            // Enhanced: Only close if sidebar is open and click is outside both sidebar and toggle button
            if (
                sidebar && sidebar.classList.contains('active') &&
                !sidebar.contains(e.target) &&
                (!toggleBtn || !toggleBtn.contains(e.target))
            ) {
                sidebar.classList.remove('active');
            }
        }
    });

    // Notification dropdown
    const notificationIcon = document.querySelector('.notifications');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            console.log('Notifications clicked');
        });
    }

    // Profile dropdown
    const profileSection = document.querySelector('.profile');
    if (profileSection) {
        profileSection.addEventListener('click', function() {
            console.log('Profile clicked');
        });
    }
});

// Added error handling for AJAX requests
function fetchDashboardData() {
    fetch('api/dashboard-data.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Update dashboard with new data
            updateDashboard(data);
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
            // Show user-friendly error notification
            showNotification('Could not update dashboard data. Please check your connection.', 'error');
        });
}

// New function to update dashboard with data
function updateDashboard(data) {
    console.log('Dashboard updated with new data');
}

// Show notifications to users
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 500);
    }, 5000);
}
