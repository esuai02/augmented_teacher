/**
 * File: mvp_system/ui/rule_manager.js
 * Mathking Agentic MVP System - Rule Manager Client-Side JavaScript
 * Purpose: Handle rule CRUD operations and UI interactions
 */

// Global state
let currentRules = window.rulesData || [];
let editingRuleIndex = -1;

/**
 * Open modal to add new rule
 */
function openAddRuleModal() {
    document.getElementById('modalTitle').textContent = 'Add New Rule';
    document.getElementById('ruleIndex').value = '-1';
    document.getElementById('ruleForm').reset();
    document.getElementById('ruleModal').style.display = 'block';
}

/**
 * Open modal to edit existing rule
 */
function editRule(index) {
    const rule = currentRules[index];
    if (!rule) {
        alert('Rule not found at rule_manager.js:' + 28);
        return;
    }

    document.getElementById('modalTitle').textContent = 'Edit Rule';
    document.getElementById('ruleIndex').value = index;
    document.getElementById('ruleId').value = rule.rule_id || '';
    document.getElementById('priority').value = rule.priority || 0;
    document.getElementById('description').value = rule.description || '';
    document.getElementById('action').value = rule.action || '';
    document.getElementById('confidence').value = rule.confidence || 0;
    document.getElementById('rationale').value = rule.rationale || '';

    document.getElementById('ruleModal').style.display = 'block';
}

/**
 * Close rule modal
 */
function closeRuleModal() {
    document.getElementById('ruleModal').style.display = 'none';
    document.getElementById('ruleForm').reset();
}

/**
 * Delete rule with confirmation
 */
function deleteRule(index, ruleId) {
    if (!confirm(`Are you sure you want to delete rule "${ruleId}"?\n\nThis action cannot be undone.`)) {
        return;
    }

    showLoading();

    // Send delete request
    fetch('rule_manager_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete',
            rule_index: index
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();

        if (data.success) {
            alert('✅ Rule deleted successfully!');
            location.reload();
        } else {
            alert('❌ Error: ' + (data.error || 'Failed to delete rule') + '\n\nLocation: rule_manager.js:' + 80);
        }
    })
    .catch(error => {
        hideLoading();
        alert('❌ Network error: ' + error.message + '\n\nLocation: rule_manager.js:' + 85);
        console.error('Delete error at rule_manager.js:86', error);
    });
}

/**
 * Submit rule (add or edit)
 */
function submitRule(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const ruleIndex = formData.get('rule_index');
    const isEdit = ruleIndex !== '-1';

    const ruleData = {
        rule_id: formData.get('rule_id'),
        priority: parseInt(formData.get('priority')),
        description: formData.get('description'),
        action: formData.get('action'),
        confidence: parseFloat(formData.get('confidence')),
        rationale: formData.get('rationale'),
        conditions: [], // TODO: Add conditions editor
        params: {}      // TODO: Add params editor
    };

    // Validation
    if (!ruleData.rule_id || !ruleData.description || !ruleData.action || !ruleData.rationale) {
        alert('⚠️ Please fill in all required fields.\n\nLocation: rule_manager.js:' + 114);
        return;
    }

    if (ruleData.priority < 0 || ruleData.priority > 100) {
        alert('⚠️ Priority must be between 0 and 100.\n\nLocation: rule_manager.js:' + 119);
        return;
    }

    if (ruleData.confidence < 0 || ruleData.confidence > 1) {
        alert('⚠️ Confidence must be between 0.0 and 1.0.\n\nLocation: rule_manager.js:' + 124);
        return;
    }

    showLoading();

    // Send save request
    fetch('rule_manager_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: isEdit ? 'update' : 'create',
            rule_index: isEdit ? parseInt(ruleIndex) : undefined,
            rule_data: ruleData
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();

        if (data.success) {
            alert(isEdit ? '✅ Rule updated successfully!' : '✅ Rule created successfully!');
            location.reload();
        } else {
            alert('❌ Error: ' + (data.error || 'Failed to save rule') + '\n\nLocation: rule_manager.js:' + 151);
        }
    })
    .catch(error => {
        hideLoading();
        alert('❌ Network error: ' + error.message + '\n\nLocation: rule_manager.js:' + 156);
        console.error('Submit error at rule_manager.js:157', error);
    });
}

/**
 * Refresh rules list
 */
function refreshRules() {
    location.reload();
}

/**
 * Show loading overlay
 */
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('ruleModal');
    if (event.target === modal) {
        closeRuleModal();
    }
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Rule Manager initialized at rule_manager.js:193');
    console.log('Loaded rules:', currentRules.length);
});
