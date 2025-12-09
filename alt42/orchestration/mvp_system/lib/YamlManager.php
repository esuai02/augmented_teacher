<?php
// 파일: mvp_system/lib/YamlManager.php (Line 1)
// Mathking Agentic MVP System - YAML Caching Manager
//
// Purpose: File modification time-based memory caching for 22 agent YAML files
// Performance: Resolves I/O bottleneck from repeated YAML loading
// Architecture: Wraps existing parseYamlFile() without modification

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/logger.php');

/**
 * YAML Manager with Memory Caching
 *
 * Implements filemtime()-based cache invalidation for 22 agent YAML files.
 * Wraps existing parseYamlFile() function for backwards compatibility.
 *
 * Cache Strategy:
 * - Static memory cache (persistent within single PHP request)
 * - Cache key: md5(absolute_file_path)
 * - Invalidation: filemtime() comparison
 * - Lazy loading: Load only when accessed
 *
 * Performance Targets:
 * - First load: File I/O (variable, typically 5-20ms per file)
 * - Cached load: < 1ms (memory access only)
 * - 22 agents sequential load: < 100ms with caching
 * - Memory footprint: < 1MB for all 22 YAML files
 */
class YamlManager {
    /**
     * Static memory cache
     * Structure: [
     *   'cache_key' => [
     *     'data' => parsed_yaml_array,
     *     'mtime' => file_modification_timestamp,
     *     'file' => absolute_file_path
     *   ]
     * ]
     */
    private static $cache = [];

    /**
     * Cache statistics for monitoring
     */
    private static $stats = [
        'hits' => 0,
        'misses' => 0,
        'invalidations' => 0
    ];

    /**
     * Logger instance
     */
    private static $logger = null;

    /**
     * Initialize logger (lazy)
     */
    private static function init_logger() {
        if (self::$logger === null) {
            self::$logger = new MVPLogger('yaml_manager');
        }
    }

    /**
     * Load YAML file with caching
     *
     * @param string $yaml_file Absolute path to YAML file
     * @return array|null Parsed YAML data or null on error
     * @throws Exception if file not found
     */
    public static function load($yaml_file) {
        self::init_logger();

        // Validate file exists
        if (!file_exists($yaml_file)) {
            $error_msg = "YAML file not found: {$yaml_file}";
            self::$logger->error($error_msg, null, [
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            throw new Exception($error_msg . " at " . __FILE__ . ":" . __LINE__);
        }

        // Generate cache key
        $cache_key = md5(realpath($yaml_file));
        $mtime = filemtime($yaml_file);

        // Check cache validity
        if (isset(self::$cache[$cache_key])) {
            // Cache exists - check if still valid
            if (self::$cache[$cache_key]['mtime'] >= $mtime) {
                // Cache HIT
                self::$stats['hits']++;

                self::$logger->info("YAML cache hit", [
                    'file' => basename($yaml_file),
                    'cache_key' => substr($cache_key, 0, 8)
                ]);

                return self::$cache[$cache_key]['data'];
            } else {
                // Cache INVALIDATED (file modified)
                self::$stats['invalidations']++;

                self::$logger->info("YAML cache invalidated (file modified)", [
                    'file' => basename($yaml_file),
                    'old_mtime' => self::$cache[$cache_key]['mtime'],
                    'new_mtime' => $mtime
                ]);
            }
        }

        // Cache MISS or INVALIDATED - load from file
        self::$stats['misses']++;

        self::$logger->info("YAML cache miss - loading from disk", [
            'file' => basename($yaml_file)
        ]);

        // Use existing parseYamlFile() function
        $parsed_data = self::parse_yaml_file($yaml_file);

        if ($parsed_data === null) {
            self::$logger->warning("Failed to parse YAML file", null, [
                'file' => $yaml_file
            ]);
            return null;
        }

        // Store in cache
        self::$cache[$cache_key] = [
            'data' => $parsed_data,
            'mtime' => $mtime,
            'file' => $yaml_file
        ];

        self::$logger->info("YAML loaded and cached", [
            'file' => basename($yaml_file),
            'rules_count' => count($parsed_data['rules'] ?? [])
        ]);

        return $parsed_data;
    }

    /**
     * Load only active agents based on mdl_mvp_agent_status table
     *
     * Implements lazy loading - only loads YAML for agents marked as active.
     * Performance optimization for 22-agent system.
     *
     * @return array Associative array [agent_id => yaml_data]
     * @throws Exception on database error
     */
    public static function load_active_agents() {
        global $DB;
        self::init_logger();

        $start_time = microtime(true);

        // Query active agents from database
        try {
            $active_agents = $DB->get_records_sql(
                "SELECT agent_id, agent_name, is_active
                 FROM mdl_mvp_agent_status
                 WHERE is_active = 1
                 ORDER BY agent_id"
            );
        } catch (Exception $e) {
            $error_msg = "Failed to query active agents from database";
            self::$logger->error($error_msg, $e, [
                'file' => __FILE__,
                'line' => __LINE__
            ]);
            throw new Exception($error_msg . " at " . __FILE__ . ":" . __LINE__);
        }

        if (empty($active_agents)) {
            self::$logger->warning("No active agents found in database", null, []);
            return [];
        }

        $loaded_agents = [];
        $agents_dir = __DIR__ . '/../../agents';

        foreach ($active_agents as $agent) {
            $agent_id = $agent->agent_id;
            $yaml_file = "{$agents_dir}/{$agent_id}/rules.yaml";

            // Check if YAML file exists
            if (!file_exists($yaml_file)) {
                self::$logger->warning("YAML file not found for active agent", null, [
                    'agent_id' => $agent_id,
                    'expected_path' => $yaml_file
                ]);
                continue;
            }

            // Load with caching
            try {
                $yaml_data = self::load($yaml_file);
                if ($yaml_data !== null) {
                    $loaded_agents[$agent_id] = $yaml_data;
                }
            } catch (Exception $e) {
                self::$logger->error("Failed to load YAML for agent", $e, [
                    'agent_id' => $agent_id,
                    'file' => $yaml_file
                ]);
            }
        }

        $duration_ms = (microtime(true) - $start_time) * 1000;

        self::$logger->info("Active agents loaded", [
            'count' => count($loaded_agents),
            'duration_ms' => round($duration_ms, 2),
            'cache_hits' => self::$stats['hits'],
            'cache_misses' => self::$stats['misses']
        ]);

        return $loaded_agents;
    }

    /**
     * Clear cache for specific agent
     *
     * @param string|null $agent_id Agent ID (e.g., 'agent01_onboarding') or null to clear all
     */
    public static function clear_cache($agent_id = null) {
        self::init_logger();

        if ($agent_id === null) {
            // Clear entire cache
            $cleared_count = count(self::$cache);
            self::$cache = [];
            self::$stats = ['hits' => 0, 'misses' => 0, 'invalidations' => 0];

            self::$logger->info("Entire YAML cache cleared", [
                'entries_cleared' => $cleared_count
            ]);
        } else {
            // Clear specific agent's cache
            $agents_dir = __DIR__ . '/../../agents';
            $yaml_file = "{$agents_dir}/{$agent_id}/rules.yaml";

            if (file_exists($yaml_file)) {
                $cache_key = md5(realpath($yaml_file));

                if (isset(self::$cache[$cache_key])) {
                    unset(self::$cache[$cache_key]);

                    self::$logger->info("Agent cache cleared", [
                        'agent_id' => $agent_id
                    ]);
                } else {
                    self::$logger->info("Agent cache not found (already cleared)", [
                        'agent_id' => $agent_id
                    ]);
                }
            }
        }
    }

    /**
     * Get cache statistics
     *
     * @return array Statistics [hits, misses, invalidations, hit_rate]
     */
    public static function get_stats() {
        $total = self::$stats['hits'] + self::$stats['misses'];
        $hit_rate = $total > 0 ? round((self::$stats['hits'] / $total) * 100, 2) : 0;

        return [
            'hits' => self::$stats['hits'],
            'misses' => self::$stats['misses'],
            'invalidations' => self::$stats['invalidations'],
            'hit_rate_percent' => $hit_rate,
            'cached_files' => count(self::$cache)
        ];
    }

    /**
     * Parse YAML file (wrapper for existing function)
     *
     * Reuses parseYamlFile() from rule_manager.php for backwards compatibility.
     * In production, consider migrating to symfony/yaml library.
     *
     * @param string $file_path Absolute path to YAML file
     * @return array|null Parsed YAML data
     */
    private static function parse_yaml_file($file_path) {
        if (!file_exists($file_path)) {
            error_log("[YamlManager] YAML file not found at " . __FILE__ . ":" . __LINE__);
            return null;
        }

        $content = file_get_contents($file_path);

        // Simple YAML parser (for basic structure)
        // This is a copy of parseYamlFile() from rule_manager.php
        $lines = explode("\n", $content);
        $data = [
            'version' => '',
            'scenario' => '',
            'description' => '',
            'rules' => []
        ];

        $current_rule = null;
        $current_section = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip comments and empty lines
            if (empty($trimmed) || $trimmed[0] === '#') {
                continue;
            }

            // Parse top-level fields
            if (preg_match('/^version:\s*"([^"]+)"/', $line, $matches)) {
                $data['version'] = $matches[1];
            } elseif (preg_match('/^scenario:\s*"([^"]+)"/', $line, $matches)) {
                $data['scenario'] = $matches[1];
            } elseif (preg_match('/^description:\s*"([^"]+)"/', $line, $matches)) {
                $data['description'] = $matches[1];
            } elseif (preg_match('/^rules:/', $line)) {
                $current_section = 'rules';
            } elseif ($current_section === 'rules' && preg_match('/^\s*-\s*rule_id:\s*"([^"]+)"/', $line, $matches)) {
                // New rule
                if ($current_rule !== null) {
                    $data['rules'][] = $current_rule;
                }
                $current_rule = [
                    'rule_id' => $matches[1],
                    'priority' => 0,
                    'description' => '',
                    'conditions' => [],
                    'action' => '',
                    'params' => [],
                    'confidence' => 0,
                    'rationale' => ''
                ];
            } elseif ($current_rule !== null) {
                // Parse rule fields
                if (preg_match('/^\s*priority:\s*(\d+)/', $line, $matches)) {
                    $current_rule['priority'] = (int)$matches[1];
                } elseif (preg_match('/^\s*description:\s*"([^"]+)"/', $line, $matches)) {
                    $current_rule['description'] = $matches[1];
                } elseif (preg_match('/^\s*action:\s*"([^"]+)"/', $line, $matches)) {
                    $current_rule['action'] = $matches[1];
                } elseif (preg_match('/^\s*confidence:\s*([\d.]+)/', $line, $matches)) {
                    $current_rule['confidence'] = (float)$matches[1];
                } elseif (preg_match('/^\s*rationale:\s*"(.+)"/', $line, $matches)) {
                    $current_rule['rationale'] = $matches[1];
                }
            }
        }

        // Add last rule
        if ($current_rule !== null) {
            $data['rules'][] = $current_rule;
        }

        return $data;
    }
}

/**
 * Database Tables Used:
 * - mdl_mvp_agent_status: Agent status tracking (agent_id, agent_name, is_active)
 *   Fields: id, agent_id (VARCHAR 50), agent_name (VARCHAR 100), is_active (TINYINT 1)
 *
 * File Dependencies:
 * - /agents/{agent_id}/rules.yaml: Individual agent YAML rule files
 *
 * Performance Notes:
 * - First load: ~5-20ms per file (disk I/O)
 * - Cached load: <1ms (memory access)
 * - 22 agents with cache: <100ms total
 * - Memory usage: ~1MB for all cached YAML files
 */
