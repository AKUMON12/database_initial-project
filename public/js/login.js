document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');
    
    // Check if user is already logged in
    fetch('/api/user')
        .then(response => response.json())
        .then(data => {
            if (data.user) {
                // Redirect based on role
                if (data.user.role === 'admin') {
                    window.location.href = '/admin/index.html';
                } else {
                    window.location.href = '/user/index.html';
                }
            }
        })
        .catch(error => console.error('Error checking user session:', error));
    
    // Handle login form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        // Reset error message
        errorMessage.style.display = 'none';
        
        // Send login request
        fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect based on role
                if (data.user.role === 'admin') {
                    window.location.href = '/admin/index.html';
                } else {
                    window.location.href = '/user/index.html';
                }
            } else {
                // Display error message
                errorMessage.textContent = data.error || 'Login failed. Please try again.';
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error during login:', error);
            errorMessage.textContent = 'An error occurred. Please try again.';
            errorMessage.style.display = 'block';
        });
    });
});
