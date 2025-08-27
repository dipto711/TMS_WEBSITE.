document.addEventListener('DOMContentLoaded', function() {
    // Add loading state to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const btn = this.querySelector('input[type="submit"]');
            const originalValue = btn.value;
            btn.value = 'Processing...';
            btn.style.opacity = '0.7';
            btn.disabled = true;

            // Simulate loading for demo (remove in production)
            setTimeout(() => {
                btn.value = originalValue;
                btn.style.opacity = '1';
                btn.disabled = false;
            }, 2000);
        });
    });

    // Add confirmation dialogs
    const confirmForms = document.querySelectorAll('form[action="update_booking_status.php"]');
    confirmForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const status = this.querySelector('input[name="status"]').value;
            const action = status === 'confirmed' ? 'confirm' : 'reject';
            
            if (!confirm(`Are you sure you want to ${action} this booking?`)) {
                e.preventDefault();
            }
        });
    });

    // Add tooltip functionality
    const addTooltip = (element, message) => {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = message;
        
        element.addEventListener('mouseenter', () => {
            document.body.appendChild(tooltip);
            const rect = element.getBoundingClientRect();
            tooltip.style.top = rect.bottom + 10 + 'px';
            tooltip.style.left = rect.left + (rect.width/2) - (tooltip.offsetWidth/2) + 'px';
            setTimeout(() => tooltip.style.opacity = '1', 10);
        });

        element.addEventListener('mouseleave', () => {
            tooltip.style.opacity = '0';
            setTimeout(() => tooltip.remove(), 200);
        });
    };

    // Add tooltips to status badges
    document.querySelectorAll('.status-badge').forEach(badge => {
        addTooltip(badge, `Last updated: ${new Date().toLocaleDateString()}`);
    });

    // Add table row animation
    document.querySelectorAll('tbody tr').forEach((row, index) => {
        row.style.animation = `fadeIn 0.3s ease forwards ${index * 0.1}s`;
        row.style.opacity = '0';
    });

    // Hide error messages after 5 seconds
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 300);
        }, 5000);
    });

    // Add CSS for tooltips
    const style = document.createElement('style');
    style.textContent = `
        .tooltip {
            position: fixed;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.85rem;
            pointer-events: none;
            transition: opacity 0.2s ease;
            opacity: 0;
            z-index: 1000;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
    `;
    document.head.appendChild(style);
});
