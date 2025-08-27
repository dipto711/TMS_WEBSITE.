// Wait for all DOM content to be loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize AOS if it exists
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    }

    // Only try to handle loader if it exists
    const loaderWrapper = document.querySelector('.loader-wrapper');
    if (loaderWrapper) {
        setTimeout(() => {
            loaderWrapper.style.opacity = '0';
            setTimeout(() => {
                loaderWrapper.style.display = 'none';
            }, 500);
        }, 1500);
    }

    // Only initialize charts if the element exists
    const chartElement = document.getElementById('shipmentChart');
    if (chartElement) {
        initializeCharts();
    }

    // Initialize counters if they exist
    const counters = document.querySelectorAll('.counter');
    if (counters.length > 0) {
        initializeCounters();
    }

    // Handle table row hover effects if table exists
    const tableRows = document.querySelectorAll('.shipments-table tr');
    if (tableRows.length > 0) {
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.transform = 'scale(1.01)';
                row.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
            });
            
            row.addEventListener('mouseleave', () => {
                row.style.transform = 'scale(1)';
                row.style.boxShadow = 'none';
            });
        });
    }

    // Handle search functionality if search box exists
    const searchBox = document.querySelector('.search-box input');
    if (searchBox) {
        searchBox.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.shipments-table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Handle status filter if it exists
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function(e) {
            const status = e.target.value;
            document.querySelectorAll('.shipments-table tbody tr').forEach(row => {
                if (!status || row.querySelector('.status-badge').textContent.toLowerCase() === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

// Chart Initialization Function
function initializeCharts() {
    const ctx = document.getElementById('shipmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Shipments',
                data: [12, 19, 3, 5, 2, 3],
                borderColor: '#4361ee',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(67, 97, 238, 0.1)'
            }]
        },
        options: {
            responsive: true,
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

// Counter Animation Function
function initializeCounters() {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.innerText);
        let count = 0;
        const increment = target / 100;
        
        const updateCounter = () => {
            if(count < target) {
                count += increment;
                counter.innerText = Math.ceil(count);
                setTimeout(updateCounter, 10);
            } else {
                counter.innerText = target;
            }
        };
        
        updateCounter();
    });
}

// Modal Toggle Function
function toggleAddShipmentForm() {
    const modal = document.getElementById('addShipmentForm');
    if (modal) {
        if (modal.style.display === 'none' || modal.style.display === '') {
            modal.style.display = 'block';
        } else {
            modal.style.display = 'none';
        }
    }
}

// AJAX Status Update
$(document).ready(function() {
    $('.status-select').change(function(e) {
        e.preventDefault();
        const select = $(this);
        const shipmentId = select.data('shipmentid');
        const newStatus = select.val();
        const statusSpan = select.closest('tr').find('.status-badge');

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: { 
                update_status: true, 
                shipment_id: shipmentId, 
                new_status: newStatus 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    statusSpan.removeClass().addClass('status-badge ' + response.newStatus)
                            .text(response.newStatus);
                    // Show success message
                    const msg = $('<div class="success-msg">Updated!</div>')
                        .insertAfter(select)
                        .fadeOut(2000, function() { $(this).remove(); });
                } else {
                    alert('Error updating status: ' + response.message);
                    select.val(statusSpan.text().toLowerCase());
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error:", textStatus, errorThrown);
                alert('Error updating status');
                select.val(statusSpan.text().toLowerCase());
            }
        });
    });
});
