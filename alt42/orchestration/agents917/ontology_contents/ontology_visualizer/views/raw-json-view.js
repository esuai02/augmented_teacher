/**
 * Raw JSON View Module for JSON-LD Ontology Visualizer
 * Provides formatted JSON display with syntax highlighting and copy functionality
 */

/**
 * Initialize raw JSON view with data
 * @param {Object} data - JSON data to display
 * @param {HTMLElement} displayContainer - Container for JSON display
 * @param {HTMLElement} copyButton - Copy to clipboard button
 */
export function initRawJsonView(data, displayContainer, copyButton) {
    if (!displayContainer) {
        console.error('Raw JSON display container not found');
        return;
    }

    // Format and display JSON
    updateJsonDisplay(data, displayContainer);

    // Setup copy button
    if (copyButton) {
        setupCopyButton(data, copyButton);
    }
}

/**
 * Update JSON display with syntax highlighting
 * @param {Object} data - JSON data to display
 * @param {HTMLElement} displayContainer - Container element
 */
export function updateJsonDisplay(data, displayContainer) {
    if (!displayContainer) return;

    const codeElement = displayContainer.querySelector('code') || displayContainer;

    // Format JSON with 2-space indentation
    const formattedJson = JSON.stringify(data, null, 2);

    // Apply syntax highlighting
    const highlightedJson = syntaxHighlight(formattedJson);

    codeElement.innerHTML = highlightedJson;
}

/**
 * Apply syntax highlighting to JSON string
 * @param {string} json - Formatted JSON string
 * @returns {string} HTML with syntax highlighting
 */
function syntaxHighlight(json) {
    // Replace special characters for HTML
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    // Apply syntax highlighting with regex
    return json.replace(
        /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g,
        (match) => {
            let cls = 'json-number';

            // Check for different JSON types
            if (/^"/.test(match)) {
                // String or key
                if (/:$/.test(match)) {
                    // It's a key
                    cls = 'json-key';
                } else {
                    // It's a string value
                    cls = 'json-string';
                }
            } else if (/true|false/.test(match)) {
                // Boolean
                cls = 'json-boolean';
            } else if (/null/.test(match)) {
                // Null
                cls = 'json-null';
            }

            return `<span class="${cls}">${match}</span>`;
        }
    );
}

/**
 * Setup copy to clipboard functionality
 * @param {Object} data - Data to copy
 * @param {HTMLElement} copyButton - Copy button element
 */
function setupCopyButton(data, copyButton) {
    copyButton.addEventListener('click', async () => {
        try {
            const jsonString = JSON.stringify(data, null, 2);
            await navigator.clipboard.writeText(jsonString);

            // Visual feedback
            const originalText = copyButton.innerHTML;
            copyButton.innerHTML = '<span class="btn-icon">✅</span> Copied!';
            copyButton.disabled = true;

            setTimeout(() => {
                copyButton.innerHTML = originalText;
                copyButton.disabled = false;
            }, 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
            alert('Failed to copy to clipboard. Please select and copy manually.');
        }
    });
}

/**
 * Add line numbers to JSON display
 * @param {HTMLElement} displayContainer - Container element
 */
export function addLineNumbers(displayContainer) {
    if (!displayContainer) return;

    const codeElement = displayContainer.querySelector('code') || displayContainer;
    const lines = codeElement.innerHTML.split('\n');

    const numberedHtml = lines.map((line, index) => {
        const lineNumber = index + 1;
        return `<span class="line-number">${lineNumber}</span>${line}`;
    }).join('\n');

    codeElement.innerHTML = numberedHtml;
}

/**
 * Format JSON with custom options
 * @param {Object} data - Data to format
 * @param {Object} options - Formatting options
 * @param {number} options.indent - Indentation spaces (default: 2)
 * @param {boolean} options.sortKeys - Whether to sort object keys (default: false)
 * @returns {string} Formatted JSON string
 */
export function formatJson(data, options = {}) {
    const { indent = 2, sortKeys = false } = options;

    if (sortKeys) {
        // Deep sort object keys
        const sortedData = deepSortKeys(data);
        return JSON.stringify(sortedData, null, indent);
    }

    return JSON.stringify(data, null, indent);
}

/**
 * Recursively sort object keys
 * @param {*} obj - Object to sort
 * @returns {*} Object with sorted keys
 */
function deepSortKeys(obj) {
    if (Array.isArray(obj)) {
        return obj.map(deepSortKeys);
    } else if (obj !== null && typeof obj === 'object') {
        const sorted = {};
        Object.keys(obj).sort().forEach(key => {
            sorted[key] = deepSortKeys(obj[key]);
        });
        return sorted;
    }
    return obj;
}

/**
 * Download JSON as file
 * @param {Object} data - JSON data to download
 * @param {string} filename - Filename for download (default: 'ontology.json')
 */
export function downloadJson(data, filename = 'ontology.json') {
    const jsonString = JSON.stringify(data, null, 2);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    URL.revokeObjectURL(url);
}
