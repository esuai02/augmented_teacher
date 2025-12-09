/**
 * Module Loader - Common functionality for loading module data from database
 * This replaces hardcoded data in individual module files
 */

class ModuleLoader {
    constructor(apiEndpoint = 'module_data_api.php') {
        this.apiEndpoint = apiEndpoint;
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes cache
    }

    /**
     * Fetch module data from API with caching
     */
    async fetchModuleData(categoryKey) {
        // Check cache first
        const cached = this.cache.get(categoryKey);
        if (cached && Date.now() - cached.timestamp < this.cacheTimeout) {
            return cached.data;
        }

        try {
            const response = await fetch(`${this.apiEndpoint}?action=getModuleData&category=${categoryKey}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Failed to fetch module data');
            }
            
            // Cache the successful result
            this.cache.set(categoryKey, {
                data: result,
                timestamp: Date.now()
            });
            
            return result;
            
        } catch (error) {
            console.error(`Failed to fetch module data for ${categoryKey}:`, error);
            
            // Return fallback structure
            return {
                success: false,
                data: {
                    title: categoryKey,
                    description: 'Failed to load module data',
                    tabs: []
                },
                error: error.message
            };
        }
    }

    /**
     * Create a module object with dynamic data loading
     */
    createModule(categoryKey, customMethods = {}) {
        const module = {
            // Module data will be loaded dynamically
            data: null,
            loading: false,
            error: null,
            
            // Initialize module data
            async initialize() {
                if (this.loading) return;
                
                this.loading = true;
                this.error = null;
                
                try {
                    const result = await window.moduleLoader.fetchModuleData(categoryKey);
                    
                    if (result.success) {
                        this.data = result.data;
                        
                        // Update agent info if available
                        if (result.agent && window.agents && window.agents[categoryKey]) {
                            Object.assign(window.agents[categoryKey], result.agent);
                        }
                    } else {
                        this.error = result.error;
                        // Provide minimal fallback data
                        this.data = {
                            title: categoryKey,
                            description: 'Module data unavailable',
                            tabs: []
                        };
                    }
                } catch (error) {
                    console.error(`Error initializing ${categoryKey} module:`, error);
                    this.error = error.message;
                    this.data = {
                        title: categoryKey,
                        description: 'Module initialization failed',
                        tabs: []
                    };
                } finally {
                    this.loading = false;
                }
            },
            
            // Get module data (with lazy loading)
            async getData() {
                if (!this.data && !this.loading) {
                    await this.initialize();
                }
                return this.data || { title: categoryKey, description: 'Loading...', tabs: [] };
            },
            
            // Force refresh data from server
            async refresh() {
                // Clear cache
                window.moduleLoader.cache.delete(categoryKey);
                // Reinitialize
                await this.initialize();
                return this.data;
            }
        };
        
        // Add custom methods
        Object.assign(module, customMethods);
        
        return module;
    }

    /**
     * Clear all cached data
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Preload multiple modules
     */
    async preloadModules(categoryKeys) {
        const promises = categoryKeys.map(key => this.fetchModuleData(key));
        return Promise.allSettled(promises);
    }
}

// Create global instance
window.moduleLoader = new ModuleLoader();

// Helper function for backward compatibility
window.createDynamicModule = function(categoryKey, customMethods) {
    return window.moduleLoader.createModule(categoryKey, customMethods);
};