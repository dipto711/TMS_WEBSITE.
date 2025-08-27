document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true
    });

    // Handle form submissions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const select = form.querySelector('select[name="new_role"]');
            const originalRole = select.querySelector('option[selected]')?.value;
            const newRole = select.value;

            if (originalRole === newRole) {
                e.preventDefault();
                showToast('No changes made to role');
                return;
            }

            // Add loading state
            const submitBtn = form.querySelector('input[type="submit"]');
            submitBtn.value = 'Updating...';
            submitBtn.style.opacity = '0.7';
            submitBtn.disabled = true;
        });
    });

    // Handle role select changes
    const roleSelects = document.querySelectorAll('select[name="new_role"]');
    roleSelects.forEach(select => {
        const originalValue = select.value;
        
        select.addEventListener('change', function() {
            const row = this.closest('tr');
            const submitBtn = row.querySelector('input[type="submit"]');
            
            if (this.value !== originalValue) {
                submitBtn.style.background = 'var(--gradient)';
                submitBtn.style.transform = 'scale(1.05)';
            } else {
                submitBtn.style.background = '';
                submitBtn.style.transform = '';
            }
        });
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
