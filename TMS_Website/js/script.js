// Your existing showFields function
function showFields() {
    const role = document.getElementById('role').value;
    const driverFields = document.getElementById('driverFields');
    const adminFields = document.getElementById('adminFields');

    driverFields.style.display = (role === 'driver') ? 'block' : 'none';
    adminFields.style.display = (role === 'admin') ? 'block' : 'none';
}

// New animation functions
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Form flip animation
    const loginLink = document.querySelector('a[href="login.php"]');
    const registerLink = document.querySelector('a[href="register.php"]');
    
    if (loginLink) {
        loginLink.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.container').classList.add('flip');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 600);
        });
    }

    if (registerLink) {
        registerLink.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.container').classList.add('flip');
            setTimeout(() => {
                window.location.href = 'register.php';
            }, 600);
        });
    }
});
