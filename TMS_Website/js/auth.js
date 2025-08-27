document.addEventListener('DOMContentLoaded', function() {
    const signInBtn = document.getElementById("sign-in-btn");
    const signUpBtn = document.getElementById("sign-up-btn");
    const container = document.querySelector(".container");
    const signUpForm = document.querySelector(".sign-up-form");
    const signInForm = document.querySelector(".sign-in-form");
    const roleSelect = document.getElementById('role'); // Get the select element here
    const driverFields = document.getElementById('driverFields');


    function validatePassword(password) {
        return password.length >= 8;
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }


    signUpBtn.addEventListener("click", () => {
        container.classList.add("sign-up-mode");
    });

    signInBtn.addEventListener("click", () => {
        container.classList.remove("sign-up-mode");
    });

    function showFields() {
        if (roleSelect.value === 'driver') {
            driverFields.style.display = 'block';
            // Set required attribute for driver fields
            driverFields.querySelectorAll('input').forEach(input => input.required = true);
        } else {
            driverFields.style.display = 'none';
            // Remove required attribute and clear values for other roles
            driverFields.querySelectorAll('input').forEach(input => {
                input.required = false;
                input.value = '';
            });
        }
    }

    // Attach the event listener to the select element
    roleSelect.addEventListener('change', showFields);

    // Call showFields() initially to set the correct state on page load
    showFields();


    // Form submission validation
    signUpForm.addEventListener('submit', function(e) {
        const password = this.querySelector('input[name="password"]').value;
        const email = this.querySelector('input[name="email"]').value;

        if (!validatePassword(password)) {
            e.preventDefault();
            alert('Password must be at least 8 characters long');
            return;
        }

        if (!validateEmail(email)) {
            e.preventDefault();
            alert('Please enter a valid email address');
            return;
        }
    });

    signInForm.addEventListener('submit', function(e) {
        const email = this.querySelector('input[name="email"]').value;

        if (!validateEmail(email)) {
            e.preventDefault();
            alert('Please enter a valid email address');
            return;
        }
    });

    // Initialize fields visibility
    showFields(); // Call showFields() on page load to set initial state
});
