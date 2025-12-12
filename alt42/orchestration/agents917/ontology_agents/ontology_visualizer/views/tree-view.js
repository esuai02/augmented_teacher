/**
 * Tree View Module for JSON-LD Ontology Visualizer
 * Provides expandable tree structure rendering for JSON-LD data
 */

/**
 * Initialize tree view with JSON-LD data
 * @param {Object} data - JSON-LD data to render
 * @param {HTMLElement} container - Container element for tree view
 * @param {HTMLElement} breadcrumbContainer - Container for breadcrumb trail
 */
export function initTreeView(data, container, breadcrumbContainer) {
    if (!container) {
        console.error('Tree view container not found');
        return;
    }

    // Clear previous content
    container.innerHTML = '';
    if (breadcrumbContainer) {
        breadcrumbContainer.innerHTML = 'Root';
    }

    // Build tree structure
    const tree = buildTree(data, 'root');
    container.appendChild(tree);
}

/**
 * Build tree structure recursively
 * @param {*} data - Data to render (object, array, or primitive)
 * @param {string} key - Key name for this node
 * @param {number} level - Current nesting level (default: 0)
 * @returns {HTMLElement} Tree node element
 */
function buildTree(data, key, level = 0) {
    const nodeContainer = document.createElement('div');
    nodeContainer.className = 'tree-node';
    nodeContainer.style.marginLeft = `${level * 1.5}rem`;

    const nodeContent = document.createElement('div');
    nodeContent.className = 'tree-node-content';

    // Determine data type and structure
    const dataType = Array.isArray(data) ? 'array' : typeof data;
    const isExpandable = (dataType === 'object' || dataType === 'array') && data !== null;
    const isJsonLdSpecial = typeof key === 'string' && key.startsWith('@');

    // Add expand/collapse icon for objects and arrays
    const expandIcon = document.createElement('span');
    expandIcon.className = 'tree-expand-icon';
    if (isExpandable) {
        expandIcon.textContent = '▶';
        expandIcon.style.cursor = 'pointer';
    } else {
        expandIcon.textContent = '';
        expandIcon.style.width = '1rem';
        expandIcon.style.display = 'inline-block';
    }
    nodeContent.appendChild(expandIcon);

    // Add key name
    const keyElement = document.createElement('span');
    keyElement.className = 'tree-key';
    keyElement.textContent = key;

    // Highlight JSON-LD special properties
    if (isJsonLdSpecial) {
        const typeIndicator = document.createElement('span');
        typeIndicator.className = 'tree-type-indicator';
        typeIndicator.textContent = 'JSON-LD';
        nodeContent.appendChild(typeIndicator);
    }

    nodeContent.appendChild(keyElement);

    // Add colon separator
    const separator = document.createElement('span');
    separator.textContent = ': ';
    separator.style.marginRight = '0.5rem';
    nodeContent.appendChild(separator);

    // Add value or type info
    const valueElement = document.createElement('span');
    valueElement.className = 'tree-value';

    if (isExpandable) {
        // Show type and count
        if (dataType === 'array') {
            valueElement.textContent = `Array[${data.length}]`;
        } else {
            const keys = Object.keys(data);
            valueElement.textContent = `Object{${keys.length}}`;
        }
    } else {
        // Show primitive value
        valueElement.textContent = JSON.stringify(data);
    }

    nodeContent.appendChild(valueElement);
    nodeContainer.appendChild(nodeContent);

    // Create children container (initially hidden)
    if (isExpandable) {
        const childrenContainer = document.createElement('div');
        childrenContainer.className = 'tree-children';
        childrenContainer.style.display = 'none';

        // Build children
        if (dataType === 'array') {
            data.forEach((item, index) => {
                const childNode = buildTree(item, `[${index}]`, level + 1);
                childrenContainer.appendChild(childNode);
            });
        } else {
            Object.entries(data).forEach(([childKey, childValue]) => {
                const childNode = buildTree(childValue, childKey, level + 1);
                childrenContainer.appendChild(childNode);
            });
        }

        nodeContainer.appendChild(childrenContainer);

        // Add expand/collapse functionality
        let isExpanded = false;
        expandIcon.addEventListener('click', () => {
            isExpanded = !isExpanded;
            expandIcon.textContent = isExpanded ? '▼' : '▶';
            childrenContainer.style.display = isExpanded ? 'block' : 'none';
        });
    }

    return nodeContainer;
}

/**
 * Update breadcrumb trail based on current navigation
 * @param {Array<string>} path - Array of path segments
 * @param {HTMLElement} breadcrumbContainer - Container element
 */
export function updateBreadcrumb(path, breadcrumbContainer) {
    if (!breadcrumbContainer) return;

    if (!path || path.length === 0) {
        breadcrumbContainer.innerHTML = 'Root';
        return;
    }

    const breadcrumbHTML = ['Root', ...path].map((segment, index) => {
        if (index === 0) {
            return '<span class="breadcrumb-segment">Root</span>';
        }
        return `<span class="breadcrumb-separator"> › </span><span class="breadcrumb-segment">${segment}</span>`;
    }).join('');

    breadcrumbContainer.innerHTML = breadcrumbHTML;
}

/**
 * Expand all nodes up to a certain depth
 * @param {HTMLElement} container - Tree view container
 * @param {number} maxDepth - Maximum depth to expand (default: 1)
 */
export function expandToDepth(container, maxDepth = 1) {
    if (!container) return;

    const nodes = container.querySelectorAll('.tree-node');
    nodes.forEach(node => {
        const level = parseInt(node.style.marginLeft) / 1.5 || 0;
        if (level < maxDepth) {
            const expandIcon = node.querySelector('.tree-expand-icon');
            const childrenContainer = node.querySelector('.tree-children');
            if (expandIcon && childrenContainer && expandIcon.textContent === '▶') {
                expandIcon.textContent = '▼';
                childrenContainer.style.display = 'block';
            }
        }
    });
}

/**
 * Collapse all expanded nodes
 * @param {HTMLElement} container - Tree view container
 */
export function collapseAll(container) {
    if (!container) return;

    const expandIcons = container.querySelectorAll('.tree-expand-icon');
    expandIcons.forEach(icon => {
        if (icon.textContent === '▼') {
            icon.textContent = '▶';
            const node = icon.closest('.tree-node');
            const childrenContainer = node?.querySelector('.tree-children');
            if (childrenContainer) {
                childrenContainer.style.display = 'none';
            }
        }
    });
}

/**
 * Search tree view for matching terms
 * @param {HTMLElement} container - Tree view container
 * @param {string} searchTerm - Term to search for
 * @param {Object} options - Search options
 * @returns {Array<HTMLElement>} Array of matching tree nodes
 */
export function searchTreeView(container, searchTerm, options = {}) {
    const {
        caseSensitive = false,
        exactMatch = false,
        searchKeys = true,
        searchValues = true
    } = options;

    if (!container || !searchTerm || searchTerm.trim() === '') {
        return [];
    }

    const matches = [];
    const normalizedTerm = caseSensitive ? searchTerm : searchTerm.toLowerCase();

    // Helper function to check if text matches search term
    function matchesSearchTerm(text) {
        if (typeof text !== 'string') return false;
        const normalizedText = caseSensitive ? text : text.toLowerCase();

        if (exactMatch) {
            return normalizedText === normalizedTerm;
        } else {
            return normalizedText.includes(normalizedTerm);
        }
    }

    // Search through all tree nodes
    const nodes = container.querySelectorAll('.tree-node');
    nodes.forEach(node => {
        const keyElement = node.querySelector('.tree-key');
        const valueElement = node.querySelector('.tree-value');

        let isMatch = false;

        // Search in keys
        if (searchKeys && keyElement) {
            const keyText = keyElement.textContent;
            if (matchesSearchTerm(keyText)) {
                isMatch = true;
            }
        }

        // Search in values
        if (searchValues && valueElement) {
            const valueText = valueElement.textContent;
            if (matchesSearchTerm(valueText)) {
                isMatch = true;
            }
        }

        if (isMatch) {
            matches.push(node);
        }
    });

    return matches;
}

/**
 * Highlight tree search results
 * @param {Array<HTMLElement>} matches - Array of matching tree nodes
 * @param {string} searchTerm - Original search term for highlighting
 * @param {boolean} caseSensitive - Case sensitive highlighting
 */
export function highlightTreeMatches(matches, searchTerm, caseSensitive = false) {
    if (!matches || matches.length === 0) return;

    matches.forEach((node, index) => {
        // Add highlight class to node
        node.classList.add('tree-search-match');

        // Add match index for reference
        node.dataset.matchIndex = index;

        // Expand parent nodes to reveal this match
        expandParentNodes(node);

        // Highlight search term in text
        const keyElement = node.querySelector('.tree-key');
        const valueElement = node.querySelector('.tree-value');

        if (keyElement) {
            highlightTextInElement(keyElement, searchTerm, caseSensitive);
        }
        if (valueElement) {
            highlightTextInElement(valueElement, searchTerm, caseSensitive);
        }
    });
}

/**
 * Highlight search term within an element
 * @param {HTMLElement} element - Element to highlight text in
 * @param {string} searchTerm - Term to highlight
 * @param {boolean} caseSensitive - Case sensitive matching
 */
function highlightTextInElement(element, searchTerm, caseSensitive = false) {
    const originalText = element.textContent;
    const flags = caseSensitive ? 'g' : 'gi';
    const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(escapedTerm, flags);

    const highlightedHTML = originalText.replace(regex, match =>
        `<mark class="search-highlight">${match}</mark>`
    );

    // Only update if there are actual highlights
    if (highlightedHTML !== originalText) {
        element.innerHTML = highlightedHTML;
    }
}

/**
 * Expand all parent nodes to reveal a target node
 * @param {HTMLElement} targetNode - Node to reveal
 */
function expandParentNodes(targetNode) {
    let currentNode = targetNode.parentElement;

    while (currentNode) {
        if (currentNode.classList.contains('tree-children')) {
            // This is a children container, find its parent node
            const parentNode = currentNode.parentElement;
            if (parentNode && parentNode.classList.contains('tree-node')) {
                const expandIcon = parentNode.querySelector('.tree-expand-icon');
                if (expandIcon && expandIcon.textContent === '▶') {
                    expandIcon.textContent = '▼';
                    currentNode.style.display = 'block';
                }
            }
        }
        currentNode = currentNode.parentElement;
    }
}

/**
 * Clear tree search highlights
 * @param {HTMLElement} container - Tree view container
 */
export function clearTreeHighlights(container) {
    if (!container) return;

    // Remove highlight classes
    const highlightedNodes = container.querySelectorAll('.tree-search-match');
    highlightedNodes.forEach(node => {
        node.classList.remove('tree-search-match', 'tree-search-focused');
        delete node.dataset.matchIndex;
    });

    // Remove inline highlights from text
    const highlightedText = container.querySelectorAll('.tree-key, .tree-value');
    highlightedText.forEach(element => {
        const text = element.textContent;
        element.textContent = text; // This removes all HTML tags including <mark>
    });
}

/**
 * Focus on a specific tree search result
 * @param {HTMLElement} container - Tree view container
 * @param {number} resultIndex - Index of result to focus on
 */
export function focusTreeResult(container, resultIndex) {
    if (!container) return;

    // Clear previous focus
    const previousFocused = container.querySelector('.tree-search-focused');
    if (previousFocused) {
        previousFocused.classList.remove('tree-search-focused');
    }

    // Find and focus target result
    const targetNode = container.querySelector(`.tree-search-match[data-match-index="${resultIndex}"]`);
    if (targetNode) {
        targetNode.classList.add('tree-search-focused');

        // Scroll into view
        targetNode.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
}

/**
 * Get total count of tree search matches
 * @param {HTMLElement} container - Tree view container
 * @returns {number} Number of matches
 */
export function getTreeMatchCount(container) {
    if (!container) return 0;
    return container.querySelectorAll('.tree-search-match').length;
}
