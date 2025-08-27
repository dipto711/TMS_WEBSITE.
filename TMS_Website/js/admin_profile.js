document.addEventListener('DOMContentLoaded', function() {
    // Edit Profile Button Click Handler
    const editProfileBtn = document.getElementById('editProfileBtn');
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Send AJAX request to switch to edit mode
            fetch('admin_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'edit_profile=true'
            })
            .then(response => response.text())
            .then(html => {
                // Replace the content of the container with the edit form
                document.querySelector('.container').innerHTML = html;
                initializeFormHandlers(); // Re-initialize handlers for the new form
            })
            .catch(error => console.error('Error:', error));
        });
    }

    // Initialize form handlers for both initial load and after AJAX
    initializeFormHandlers();
});

function initializeFormHandlers() {
    // Update Profile Form Submit Handler
    const updateProfileForm = document.getElementById('updateProfileForm');
    if (updateProfileForm) {
        updateProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('update_profile', 'true');

            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            fetch('admin_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                document.querySelector('.container').innerHTML = html;
                initializeFormHandlers(); // Re-initialize handlers
                
                // Show success message
                const successMessage = document.createElement('div');
                successMessage.className = 'success-message';
                successMessage.textContent = 'Profile updated successfully!';
                document.querySelector('.container').insertBefore(successMessage, document.querySelector('.container').firstChild);
                
                // Auto-hide success message
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    setTimeout(() => successMessage.remove(), 300);
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                
                // Show error message
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'An error occurred. Please try again.';
                document.querySelector('.container').insertBefore(errorMessage, document.querySelector('.container').firstChild);
            });
        });
    }

    // Cancel Edit Button Click Handler
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.reload(); // Simply reload the page to cancel editing
        });
    }

    // File Input Preview
    const fileInput = document.getElementById('profile_pic');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.profile-pic.preview');
                    if (preview) {
                        preview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
}
