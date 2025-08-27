document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true
    });

    // Form visibility toggle
    window.toggleAddVehicleForm = function() {
        const form = document.getElementById('addVehicleForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    };

    // Delete confirmation
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm('Are you sure you want to delete this vehicle?')) {
                e.preventDefault();
            }
        });
    });

    // Status update handling
    const statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', () => {
            const originalColor = select.style.backgroundColor;
            select.style.backgroundColor = '#e2e2e2';
            
            setTimeout(() => {
                select.style.backgroundColor = originalColor;
            }, 300);
        });
    });

    // Success message fade out
    const successMessage = document.querySelector('.success');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.opacity = '0';
            setTimeout(() => {
                successMessage.remove();
            }, 300);
        }, 3000);
    }
});
