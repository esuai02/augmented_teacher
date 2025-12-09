/**
 * JSON-LD Processing Tools Module
 * Provides JSON-LD transformation and validation functions
 */

// Import jsonld library
import jsonld from 'https://cdn.jsdelivr.net/npm/jsonld@8.3.3/+esm';

/**
 * Expand JSON-LD document with full IRIs
 * @param {Object} data - JSON-LD document to expand
 * @returns {Promise<Object>} Expanded JSON-LD document
 */
export async function expandContext(data) {
    if (!data) {
        throw new Error('No data provided for expansion');
    }

    try {
        // Diagnostic: Check jsonld state
        if (typeof jsonld === 'undefined') {
            throw new Error(`jsonld is undefined - typeof: ${typeof jsonld}, import failed`);
        }
        if (typeof jsonld.expand !== 'function') {
            throw new Error(`jsonld.expand is not a function - typeof expand: ${typeof jsonld.expand}, jsonld keys: ${Object.keys(jsonld).join(',')}`);
        }

        // Use jsonld library to expand context
        // This will replace all compact IRIs with full IRIs
        const expanded = await jsonld.expand(data);

        return {
            success: true,
            result: expanded,
            operation: 'expand',
            timestamp: new Date().toISOString()
        };
    } catch (error) {
        return {
            success: false,
            error: error.message,
            operation: 'expand',
            timestamp: new Date().toISOString()
        };
    }
}

/**
 * Compact JSON-LD document with context
 * @param {Object} data - JSON-LD document to compact
 * @param {Object} context - Context to use for compacting (optional)
 * @returns {Promise<Object>} Compacted JSON-LD document
 */
export async function compactContext(data, context = null) {
    if (!data) {
        throw new Error('No data provided for compaction');
    }

    try {
        // Use jsonld library to compact with context
        // If no context provided, extract from data or use empty context
        const contextToUse = context || data['@context'] || {};
        const compacted = await jsonld.compact(data, contextToUse);

        return {
            success: true,
            result: compacted,
            operation: 'compact',
            timestamp: new Date().toISOString()
        };
    } catch (error) {
        return {
            success: false,
            error: error.message,
            operation: 'compact',
            timestamp: new Date().toISOString()
        };
    }
}

/**
 * Flatten JSON-LD document to remove nesting
 * @param {Object} data - JSON-LD document to flatten
 * @param {Object} context - Context to use for flattening (optional)
 * @returns {Promise<Object>} Flattened JSON-LD document
 */
export async function flattenJsonLd(data, context = null) {
    if (!data) {
        throw new Error('No data provided for flattening');
    }

    try {
        // Use jsonld library to flatten document
        // Flattening creates a graph of nodes with no nesting
        const flattened = await jsonld.flatten(data, context);

        return {
            success: true,
            result: flattened,
            operation: 'flatten',
            timestamp: new Date().toISOString()
        };
    } catch (error) {
        return {
            success: false,
            error: error.message,
            operation: 'flatten',
            timestamp: new Date().toISOString()
        };
    }
}

/**
 * Validate JSON-LD document structure
 * @param {Object} data - JSON-LD document to validate
 * @returns {Promise<Object>} Validation result with detailed error information
 */
export async function validateJsonLd(data) {
    const errors = [];
    const warnings = [];

    try {
        // Check if data is object
        if (typeof data !== 'object' || data === null) {
            errors.push({
                type: 'error',
                message: 'JSON-LD document must be an object or array',
                line: null
            });
            return {
                success: false,
                valid: false,
                errors,
                warnings,
                operation: 'validate',
                timestamp: new Date().toISOString()
            };
        }

        // Check for @context (recommended but not required)
        if (!data['@context']) {
            warnings.push({
                type: 'warning',
                message: 'No @context found. Document may not be properly linked data.',
                line: null
            });
        }

        // If @graph is present, validate it's an array
        if (data['@graph'] && !Array.isArray(data['@graph'])) {
            errors.push({
                type: 'error',
                message: '@graph must be an array',
                line: null
            });
        }

        // Try to expand the document to validate structure
        // This will catch many JSON-LD structural errors
        try {
            await jsonld.expand(data);
        } catch (expandError) {
            errors.push({
                type: 'error',
                message: `JSON-LD expansion failed: ${expandError.message}`,
                line: null,
                details: expandError.details || null
            });
        }

        // Check for common issues
        validateCommonIssues(data, errors, warnings);

        return {
            success: true,
            valid: errors.length === 0,
            errors,
            warnings,
            operation: 'validate',
            timestamp: new Date().toISOString()
        };

    } catch (error) {
        return {
            success: false,
            valid: false,
            errors: [{
                type: 'error',
                message: `Validation error: ${error.message}`,
                line: null
            }],
            warnings,
            operation: 'validate',
            timestamp: new Date().toISOString()
        };
    }
}

/**
 * Validate common JSON-LD issues
 * @private
 * @param {Object} data - JSON-LD document
 * @param {Array} errors - Array to collect errors
 * @param {Array} warnings - Array to collect warnings
 */
function validateCommonIssues(data, errors, warnings) {
    // Check for @id format
    if (data['@id'] && typeof data['@id'] !== 'string') {
        errors.push({
            type: 'error',
            message: '@id must be a string (IRI)',
            line: null
        });
    }

    // Check for @type format
    if (data['@type']) {
        if (typeof data['@type'] !== 'string' && !Array.isArray(data['@type'])) {
            errors.push({
                type: 'error',
                message: '@type must be a string or array of strings',
                line: null
            });
        }
    }

    // Check for @value and @type/@language conflicts
    if (data['@value']) {
        if (data['@type'] && data['@language']) {
            errors.push({
                type: 'error',
                message: '@value cannot have both @type and @language',
                line: null
            });
        }
    }

    // Recursively check nested objects
    Object.entries(data).forEach(([key, value]) => {
        if (key.startsWith('@')) return; // Skip JSON-LD keywords

        if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
            validateCommonIssues(value, errors, warnings);
        } else if (Array.isArray(value)) {
            value.forEach(item => {
                if (typeof item === 'object' && item !== null) {
                    validateCommonIssues(item, errors, warnings);
                }
            });
        }
    });
}

/**
 * Get processing operation statistics
 * @param {Object} data - JSON-LD document
 * @returns {Object} Statistics about the document
 */
export function getDocumentStats(data) {
    const stats = {
        nodeCount: 0,
        propertyCount: 0,
        typeCount: 0,
        contextSize: 0,
        hasGraph: false,
        types: new Set()
    };

    if (!data || typeof data !== 'object') {
        return stats;
    }

    // Count @context size
    if (data['@context']) {
        stats.contextSize = JSON.stringify(data['@context']).length;
    }

    // Check for @graph
    stats.hasGraph = '@graph' in data;

    // Count nodes and properties recursively
    function countNodes(obj) {
        if (typeof obj !== 'object' || obj === null) return;

        stats.nodeCount++;

        Object.entries(obj).forEach(([key, value]) => {
            if (!key.startsWith('@')) {
                stats.propertyCount++;
            }

            if (key === '@type') {
                stats.typeCount++;
                if (typeof value === 'string') {
                    stats.types.add(value);
                } else if (Array.isArray(value)) {
                    value.forEach(t => stats.types.add(t));
                }
            }

            if (Array.isArray(value)) {
                value.forEach(item => countNodes(item));
            } else if (typeof value === 'object' && value !== null) {
                countNodes(value);
            }
        });
    }

    countNodes(data);
    stats.types = Array.from(stats.types);

    return stats;
}
