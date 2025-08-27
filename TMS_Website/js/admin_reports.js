document.addEventListener('DOMContentLoaded', function() {
    // Handle form submissions
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const startDate = form.querySelector('input[name="start_date"]').value;
        const endDate = form.querySelector('input[name="end_date"]').value;

        if (startDate && endDate) {
            if (new Date(startDate) > new Date(endDate)) {
                e.preventDefault();
                showToast('End date must be after start date', 'error');
                return;
            }
        }

        // Add loading state
        const submitBtn = form.querySelector('input[type="submit"]');
        submitBtn.value = 'Filtering...';
        submitBtn.style.opacity = '0.7';
        submitBtn.disabled = true;
    });

    // Show toast message
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Add CSS for toast
    const style = document.createElement('style');
    style.textContent = `
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: white;
            color: var(--dark);
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 0.95rem;
            animation: slideIn 0.3s ease;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }
        .toast.error {
            background: var(--warning);
            color: white;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    // Handle success/error messages
    const messages = document.querySelectorAll('.success, .error');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 300);
        }, 5000);
    });
});
