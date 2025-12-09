// Get URL parameters
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Display user information
function displayUserInfo(data) {
    const userRoleElement = document.getElementById('user-role');
    const studentIdElement = document.getElementById('student-id');
    
    userRoleElement.textContent = `User Role: ${data.user_role}`;
    studentIdElement.textContent = `Student ID: ${data.student_id || 'Not specified'}`;
    
    // Add additional user info if available
    if (data.username) {
        const usernameElement = document.createElement('p');
        usernameElement.textContent = `Username: ${data.username}`;
        document.querySelector('.user-info').appendChild(usernameElement);
    }
    
    if (data.email) {
        const emailElement = document.createElement('p');
        emailElement.textContent = `Email: ${data.email}`;
        document.querySelector('.user-info').appendChild(emailElement);
    }
}

// Show error message
function showError(message) {
    const contentDiv = document.getElementById('content');
    contentDiv.innerHTML = `
        <div class="error-message text-error text-center">
            <h2>Error</h2>
            <p>${message}</p>
        </div>
    `;
    contentDiv.style.display = 'block';
}

// Load user data from API
async function loadUserData() {
    try {
        const studentId = getUrlParameter('userid');
        let apiUrl = 'api.php';
        
        if (studentId) {
            apiUrl += `?userid=${encodeURIComponent(studentId)}`;
        }
        
        const response = await fetch(apiUrl);
        const result = await response.json();
        
        if (result.success) {
            displayUserInfo(result.data);
            document.getElementById('loading').style.display = 'none';
            document.getElementById('content').style.display = 'block';
        } else {
            throw new Error(result.error || 'Failed to load user data');
        }
    } catch (error) {
        console.error('Error loading user data:', error);
        document.getElementById('loading').style.display = 'none';
        showError(error.message || 'An error occurred while loading user data');
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    loadUserData();
});

// Handle authentication errors
window.addEventListener('error', function(event) {
    if (event.message.includes('login') || event.message.includes('authentication')) {
        showError('Please log in to access this page');
    }
});