<?php
/**
 * Claude Code Subagents Utility
 * 
 * This script helps you use the awesome-claude-code-subagents collection
 * for various development tasks in your MathKing project.
 * 
 * Usage:
 * 1. Include this file in your PHP scripts
 * 2. Call getSubagent() to get a specific subagent definition
 * 3. Use the subagent information for task delegation
 */

class SubagentManager {
    private $basePath;
    private $categories;
    
    public function __construct() {
        $this->basePath = __DIR__ . '/awesome-claude-code-subagents-main/categories/';
        $this->loadCategories();
    }
    
    /**
     * Load all available categories
     */
    private function loadCategories() {
        $this->categories = [
            '01-core-development' => 'Core Development',
            '02-language-specialists' => 'Language Specialists',
            '03-infrastructure' => 'Infrastructure',
            '04-quality-security' => 'Quality & Security',
            '05-data-ai' => 'Data & AI',
            '06-developer-experience' => 'Developer Experience',
            '07-specialized-domains' => 'Specialized Domains',
            '08-business-product' => 'Business & Product',
            '09-meta-orchestration' => 'Meta Orchestration',
            '10-research-analysis' => 'Research & Analysis'
        ];
    }
    
    /**
     * Get list of all available subagents
     */
    public function listSubagents() {
        $subagents = [];
        
        foreach ($this->categories as $category => $name) {
            $categoryPath = $this->basePath . $category;
            if (is_dir($categoryPath)) {
                $files = glob($categoryPath . '/*.md');
                foreach ($files as $file) {
                    $agentName = basename($file, '.md');
                    $subagents[$category][] = $agentName;
                }
            }
        }
        
        return $subagents;
    }
    
    /**
     * Get a specific subagent definition
     * 
     * @param string $category Category folder name
     * @param string $agentName Agent file name (without .md)
     * @return array|null Agent metadata and prompt
     */
    public function getSubagent($category, $agentName) {
        $filePath = $this->basePath . $category . '/' . $agentName . '.md';
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $content = file_get_contents($filePath);
        
        // Parse the frontmatter
        $metadata = $this->parseFrontmatter($content);
        
        // Extract the prompt (everything after frontmatter)
        $parts = explode('---', $content, 3);
        $prompt = isset($parts[2]) ? trim($parts[2]) : '';
        
        return [
            'name' => $metadata['name'] ?? $agentName,
            'description' => $metadata['description'] ?? '',
            'tools' => $metadata['tools'] ?? '',
            'prompt' => $prompt,
            'category' => $this->categories[$category] ?? $category,
            'file' => $filePath
        ];
    }
    
    /**
     * Parse YAML frontmatter from markdown file
     */
    private function parseFrontmatter($content) {
        $metadata = [];
        
        if (strpos($content, '---') === 0) {
            $parts = explode('---', $content, 3);
            if (count($parts) >= 2) {
                $yaml = $parts[1];
                $lines = explode("\n", $yaml);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $colonPos = strpos($line, ':');
                    if ($colonPos !== false) {
                        $key = trim(substr($line, 0, $colonPos));
                        $value = trim(substr($line, $colonPos + 1));
                        $metadata[$key] = $value;
                    }
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * Get recommended subagents for specific tasks
     */
    public function getRecommendations($taskType) {
        $recommendations = [
            'api' => [
                ['01-core-development', 'api-designer'],
                ['01-core-development', 'backend-developer'],
                ['01-core-development', 'graphql-architect']
            ],
            'frontend' => [
                ['01-core-development', 'frontend-developer'],
                ['01-core-development', 'ui-designer'],
                ['02-language-specialists', 'react-specialist'],
                ['02-language-specialists', 'vue-expert']
            ],
            'database' => [
                ['03-infrastructure', 'database-administrator'],
                ['02-language-specialists', 'sql-pro']
            ],
            'security' => [
                ['04-quality-security', 'security-expert'],
                ['04-quality-security', 'penetration-tester']
            ],
            'testing' => [
                ['04-quality-security', 'test-engineer'],
                ['04-quality-security', 'qa-specialist']
            ],
            'performance' => [
                ['04-quality-security', 'performance-engineer'],
                ['03-infrastructure', 'database-administrator']
            ],
            'documentation' => [
                ['06-developer-experience', 'documentation-writer'],
                ['06-developer-experience', 'api-documenter']
            ],
            'php' => [
                ['02-language-specialists', 'php-pro'],
                ['02-language-specialists', 'laravel-specialist']
            ],
            'moodle' => [
                ['07-specialized-domains', 'education-specialist'],
                ['01-core-development', 'backend-developer']
            ]
        ];
        
        return $recommendations[$taskType] ?? [];
    }
}

// Example usage functions
function displaySubagentInfo($agent) {
    if (!$agent) {
        echo "Subagent not found.\n";
        return;
    }
    
    echo "=== Subagent Information ===\n";
    echo "Name: " . $agent['name'] . "\n";
    echo "Category: " . $agent['category'] . "\n";
    echo "Description: " . $agent['description'] . "\n";
    echo "Tools: " . $agent['tools'] . "\n";
    echo "\n=== Prompt ===\n";
    echo substr($agent['prompt'], 0, 500) . "...\n";
}

// Initialize the manager
$subagentManager = new SubagentManager();

// Example: Get backend developer subagent
// $backendDev = $subagentManager->getSubagent('01-core-development', 'backend-developer');
// displaySubagentInfo($backendDev);

// Example: List all available subagents
// $allSubagents = $subagentManager->listSubagents();
// print_r($allSubagents);

// Example: Get recommendations for API development
// $apiRecommendations = $subagentManager->getRecommendations('api');
// foreach ($apiRecommendations as $rec) {
//     $agent = $subagentManager->getSubagent($rec[0], $rec[1]);
//     echo "Recommended: " . $agent['name'] . " - " . $agent['description'] . "\n";
// }