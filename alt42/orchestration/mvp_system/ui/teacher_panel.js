/**
 * File: mvp_system/ui/teacher_panel.js (Line 1)
 * Mathking Agentic MVP System - Teacher Approval Panel JavaScript
 *
 * Purpose: Handle AJAX feedback submission and UI interactions
 */

// Global state
let currentDecisionId = null;

/**
 * Submit quick feedback (approve/reject/defer)
 * Called from action buttons in decision cards
 *
 * @param {number} decision_id - Decision ID to provide feedback for
 * @param {string} response - Response type: 'approve', 'reject', or 'defer'
 */
function submitFeedback(decision_id, response) {
    // Validate inputs
    if (!decision_id || !response) {
        showMessage('Error: Invalid parameters at teacher_panel.js:20', 'error');
        return;
    }

    // Show loading overlay
    showLoading();

    // Prepare request data
    const data = {
        decision_id: decision_id,
        response: response,
        comment: '' // No comment for quick actions
    };

    // Send AJAX request to feedback API
    fetch('standalone_feedback_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error ' + response.status + ' at teacher_panel.js:43');
        }
        return response.json();
    })
    .then(data => {
        hideLoading();

        if (data.success) {
            showMessage('✅ Feedback submitted successfully!', 'success');
            // Reload page after short delay to show updated state
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage('❌ Error: ' + (data.error || 'Unknown error') + ' at teacher_panel.js:55', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showMessage('❌ Network error: ' + error.message + ' at teacher_panel.js:61', 'error');
        console.error('Feedback submission error at teacher_panel.js:62:', error);
    });
}

/**
 * Open comment modal for detailed feedback
 *
 * @param {number} decision_id - Decision ID to add comment for
 */
function openCommentModal(decision_id) {
    if (!decision_id) {
        showMessage('Error: Invalid decision ID at teacher_panel.js:73', 'error');
        return;
    }

    // Store current decision ID
    currentDecisionId = decision_id;

    // Set decision ID in hidden form field
    document.getElementById('commentDecisionId').value = decision_id;

    // Reset form fields
    document.getElementById('response').value = 'approve';
    document.getElementById('comment').value = '';

    // Show modal
    document.getElementById('commentModal').style.display = 'block';
}

/**
 * Close comment modal
 */
function closeCommentModal() {
    document.getElementById('commentModal').style.display = 'none';
    currentDecisionId = null;
}

/**
 * Submit comment feedback form
 * Called when comment form is submitted
 *
 * @param {Event} event - Form submit event
 */
function submitCommentFeedback(event) {
    // Prevent default form submission
    event.preventDefault();

    // Get form values
    const decision_id = document.getElementById('commentDecisionId').value;
    const response = document.getElementById('response').value;
    const comment = document.getElementById('comment').value.trim();

    // Validate comment
    if (!comment) {
        showMessage('⚠️ Please enter a comment at teacher_panel.js:119', 'warning');
        return;
    }

    // Close modal
    closeCommentModal();

    // Show loading overlay
    showLoading();

    // Prepare request data
    const data = {
        decision_id: parseInt(decision_id),
        response: response,
        comment: comment
    };

    // Send AJAX request to feedback API
    fetch('standalone_feedback_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error ' + response.status + ' at teacher_panel.js:147');
        }
        return response.json();
    })
    .then(data => {
        hideLoading();

        if (data.success) {
            showMessage('✅ Feedback with comment submitted successfully!', 'success');
            // Reload page after short delay to show updated state
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage('❌ Error: ' + (data.error || 'Unknown error') + ' at teacher_panel.js:161', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showMessage('❌ Network error: ' + error.message + ' at teacher_panel.js:167', 'error');
        console.error('Comment feedback submission error at teacher_panel.js:168:', error);
    });
}

/**
 * Show loading overlay
 */
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * Show notification message to user
 *
 * @param {string} message - Message text to display
 * @param {string} type - Message type: 'success', 'error', or 'warning'
 */
function showMessage(message, type) {
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.textContent = message;
    messageDiv.style.position = 'fixed';
    messageDiv.style.top = '20px';
    messageDiv.style.right = '20px';
    messageDiv.style.padding = '16px 24px';
    messageDiv.style.borderRadius = '8px';
    messageDiv.style.fontSize = '14px';
    messageDiv.style.fontWeight = '600';
    messageDiv.style.zIndex = '3000';
    messageDiv.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    messageDiv.style.animation = 'slideIn 0.3s ease-out';

    // Set colors based on type
    if (type === 'success') {
        messageDiv.style.backgroundColor = '#d1e7dd';
        messageDiv.style.color = '#0f5132';
        messageDiv.style.border = '2px solid #badbcc';
    } else if (type === 'error') {
        messageDiv.style.backgroundColor = '#f8d7da';
        messageDiv.style.color = '#842029';
        messageDiv.style.border = '2px solid #f5c2c7';
    } else if (type === 'warning') {
        messageDiv.style.backgroundColor = '#fff3cd';
        messageDiv.style.color = '#856404';
        messageDiv.style.border = '2px solid #ffeaa7';
    }

    // Add to page
    document.body.appendChild(messageDiv);

    // Remove after 5 seconds
    setTimeout(() => {
        messageDiv.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 300);
    }, 5000);
}

/**
 * Close modal when clicking outside of modal content
 */
window.onclick = function(event) {
    const modal = document.getElementById('commentModal');
    if (event.target === modal) {
        closeCommentModal();
    }
};

/**
 * Add CSS animations for messages
 */
if (!document.getElementById('message-animations')) {
    const style = document.createElement('style');
    style.id = 'message-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Initialize page on load
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Teacher panel initialized at teacher_panel.js:289');

    // Log any initialization info
    const decisions = document.querySelectorAll('.decision-card');
    console.log('Found ' + decisions.length + ' decision cards at teacher_panel.js:293');
});
