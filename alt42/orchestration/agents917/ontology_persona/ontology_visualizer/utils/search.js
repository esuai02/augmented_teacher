/**
 * Search and Filter Module for JSON-LD Ontology Visualizer
 * Provides search functionality across Graph, Tree, and Raw JSON views
 */

/**
 * Search through JSON-LD data for matching terms
 * @param {Object|Array} data - JSON-LD data to search
 * @param {string} searchTerm - Term to search for
 * @param {Object} options - Search options
 * @param {boolean} options.caseSensitive - Case sensitive search (default: false)
 * @param {boolean} options.exactMatch - Exact match only (default: false)
 * @param {Array<string>} options.searchIn - Where to search: ['keys', 'values', 'types'] (default: all)
 * @returns {Array} Array of search results with context
 */
export function searchJsonLd(data, searchTerm, options = {}) {
    const {
        caseSensitive = false,
        exactMatch = false,
        searchIn = ['keys', 'values', 'types']
    } = options;

    if (!searchTerm || searchTerm.trim() === '') {
        return [];
    }

    const results = [];
    const normalizedTerm = caseSensitive ? searchTerm : searchTerm.toLowerCase();

    /**
     * Check if a string matches the search term
     */
    function matches(str) {
        if (typeof str !== 'string') return false;

        const normalizedStr = caseSensitive ? str : str.toLowerCase();

        if (exactMatch) {
            return normalizedStr === normalizedTerm;
        } else {
            return normalizedStr.includes(normalizedTerm);
        }
    }

    /**
     * Recursively search through object/array
     */
    function searchRecursive(obj, path = []) {
        if (obj === null || obj === undefined) return;

        if (Array.isArray(obj)) {
            obj.forEach((item, index) => {
                searchRecursive(item, [...path, `[${index}]`]);
            });
            return;
        }

        if (typeof obj === 'object') {
            Object.entries(obj).forEach(([key, value]) => {
                const currentPath = [...path, key];
                const pathString = currentPath.join('.');

                // Search in keys
                if (searchIn.includes('keys') && matches(key)) {
                    results.push({
                        type: 'key',
                        path: pathString,
                        key: key,
                        value: value,
                        context: obj
                    });
                }

                // Search in @type values
                if (searchIn.includes('types') && key === '@type') {
                    if (typeof value === 'string' && matches(value)) {
                        results.push({
                            type: 'type',
                            path: pathString,
                            key: key,
                            value: value,
                            context: obj
                        });
                    } else if (Array.isArray(value)) {
                        value.forEach((typeVal, idx) => {
                            if (matches(typeVal)) {
                                results.push({
                                    type: 'type',
                                    path: `${pathString}[${idx}]`,
                                    key: key,
                                    value: typeVal,
                                    context: obj
                                });
                            }
                        });
                    }
                }

                // Search in primitive values
                if (searchIn.includes('values')) {
                    if (typeof value === 'string' && matches(value) && key !== '@type') {
                        results.push({
                            type: 'value',
                            path: pathString,
                            key: key,
                            value: value,
                            context: obj
                        });
                    } else if (typeof value === 'number' || typeof value === 'boolean') {
                        if (matches(String(value))) {
                            results.push({
                                type: 'value',
                                path: pathString,
                                key: key,
                                value: value,
                                context: obj
                            });
                        }
                    }
                }

                // Recurse into nested objects/arrays
                if (typeof value === 'object' && value !== null) {
                    searchRecursive(value, currentPath);
                }
            });
        }
    }

    searchRecursive(data);
    return results;
}

/**
 * Filter JSON-LD data by node types
 * @param {Object|Array} data - JSON-LD data to filter
 * @param {Array<string>} types - Array of @type values to filter by
 * @returns {Array} Array of nodes matching the specified types
 */
export function filterByType(data, types) {
    if (!types || types.length === 0) {
        return [];
    }

    const results = [];
    const normalizedTypes = types.map(t => t.toLowerCase());

    function findByType(obj, path = []) {
        if (obj === null || obj === undefined) return;

        if (Array.isArray(obj)) {
            obj.forEach((item, index) => {
                findByType(item, [...path, `[${index}]`]);
            });
            return;
        }

        if (typeof obj === 'object') {
            // Check if this object has a matching @type
            if (obj['@type']) {
                const objTypes = Array.isArray(obj['@type']) ? obj['@type'] : [obj['@type']];
                const hasMatch = objTypes.some(type =>
                    normalizedTypes.includes(type.toLowerCase())
                );

                if (hasMatch) {
                    results.push({
                        node: obj,
                        path: path.join('.'),
                        types: objTypes
                    });
                }
            }

            // Recurse into nested objects
            Object.entries(obj).forEach(([key, value]) => {
                if (typeof value === 'object' && value !== null) {
                    findByType(value, [...path, key]);
                }
            });
        }
    }

    findByType(data);
    return results;
}

/**
 * Filter JSON-LD data by property existence
 * @param {Object|Array} data - JSON-LD data to filter
 * @param {Array<string>} properties - Array of property names to filter by
 * @returns {Array} Array of nodes containing the specified properties
 */
export function filterByProperty(data, properties) {
    if (!properties || properties.length === 0) {
        return [];
    }

    const results = [];
    const normalizedProps = properties.map(p => p.toLowerCase());

    function findByProperty(obj, path = []) {
        if (obj === null || obj === undefined) return;

        if (Array.isArray(obj)) {
            obj.forEach((item, index) => {
                findByProperty(item, [...path, `[${index}]`]);
            });
            return;
        }

        if (typeof obj === 'object') {
            // Check if this object has any of the specified properties
            const objKeys = Object.keys(obj).map(k => k.toLowerCase());
            const matchingProps = normalizedProps.filter(prop => objKeys.includes(prop));

            if (matchingProps.length > 0) {
                results.push({
                    node: obj,
                    path: path.join('.'),
                    matchingProperties: matchingProps
                });
            }

            // Recurse into nested objects
            Object.entries(obj).forEach(([key, value]) => {
                if (typeof value === 'object' && value !== null) {
                    findByProperty(value, [...path, key]);
                }
            });
        }
    }

    findByProperty(data);
    return results;
}

/**
 * Get all unique @type values from JSON-LD data
 * @param {Object|Array} data - JSON-LD data
 * @returns {Array<string>} Array of unique @type values
 */
export function getAllTypes(data) {
    const types = new Set();

    function extractTypes(obj) {
        if (obj === null || obj === undefined) return;

        if (Array.isArray(obj)) {
            obj.forEach(item => extractTypes(item));
            return;
        }

        if (typeof obj === 'object') {
            if (obj['@type']) {
                if (Array.isArray(obj['@type'])) {
                    obj['@type'].forEach(type => types.add(type));
                } else {
                    types.add(obj['@type']);
                }
            }

            Object.values(obj).forEach(value => {
                if (typeof value === 'object' && value !== null) {
                    extractTypes(value);
                }
            });
        }
    }

    extractTypes(data);
    return Array.from(types).sort();
}

/**
 * Get all unique property names from JSON-LD data
 * @param {Object|Array} data - JSON-LD data
 * @returns {Array<string>} Array of unique property names
 */
export function getAllProperties(data) {
    const properties = new Set();

    function extractProperties(obj) {
        if (obj === null || obj === undefined) return;

        if (Array.isArray(obj)) {
            obj.forEach(item => extractProperties(item));
            return;
        }

        if (typeof obj === 'object') {
            Object.keys(obj).forEach(key => {
                properties.add(key);
            });

            Object.values(obj).forEach(value => {
                if (typeof value === 'object' && value !== null) {
                    extractProperties(value);
                }
            });
        }
    }

    extractProperties(data);
    return Array.from(properties).sort();
}

/**
 * Highlight search term in text
 * @param {string} text - Text to highlight in
 * @param {string} searchTerm - Term to highlight
 * @param {boolean} caseSensitive - Case sensitive matching
 * @returns {string} HTML string with highlighted terms
 */
export function highlightSearchTerm(text, searchTerm, caseSensitive = false) {
    if (!text || !searchTerm) return text;

    const flags = caseSensitive ? 'g' : 'gi';
    const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(escapedTerm, flags);

    return text.replace(regex, match => `<mark class="search-highlight">${match}</mark>`);
}

/**
 * Get search statistics
 * @param {Array} results - Search results array
 * @returns {Object} Statistics about search results
 */
export function getSearchStats(results) {
    const stats = {
        total: results.length,
        byType: {
            key: 0,
            value: 0,
            type: 0
        },
        paths: new Set(),
        contexts: new Set()
    };

    results.forEach(result => {
        if (result.type) {
            stats.byType[result.type] = (stats.byType[result.type] || 0) + 1;
        }
        if (result.path) {
            stats.paths.add(result.path);
        }
        if (result.context) {
            stats.contexts.add(result.context);
        }
    });

    stats.uniquePaths = stats.paths.size;
    stats.uniqueContexts = stats.contexts.size;

    return stats;
}
