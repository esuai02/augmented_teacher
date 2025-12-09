#!/usr/bin/env php
<?php
/**
 * Claude Code Subagents CLI
 * 
 * Command-line interface for browsing and using subagents
 * 
 * Usage:
 * php subagent_cli.php list                          - List all subagents
 * php subagent_cli.php show <category> <agent>       - Show specific subagent
 * php subagent_cli.php recommend <task>              - Get recommendations for a task
 * php subagent_cli.php search <keyword>              - Search subagents by keyword
 */

require_once __DIR__ . '/use_subagents.php';

// ANSI color codes for better CLI output
define('COLOR_GREEN', "\033[0;32m");
define('COLOR_BLUE', "\033[0;34m");
define('COLOR_YELLOW', "\033[0;33m");
define('COLOR_RED', "\033[0;31m");
define('COLOR_RESET', "\033[0m");

class SubagentCLI {
    private $manager;
    
    public function __construct() {
        $this->manager = new SubagentManager();
    }
    
    public function run($argv) {
        if (count($argv) < 2) {
            $this->showHelp();
            return;
        }
        
        $command = $argv[1];
        
        switch ($command) {
            case 'list':
                $this->listSubagents();
                break;
                
            case 'show':
                if (count($argv) < 4) {
                    echo COLOR_RED . "Error: Please provide category and agent name\n" . COLOR_RESET;
                    echo "Usage: php subagent_cli.php show <category> <agent>\n";
                    return;
                }
                $this->showSubagent($argv[2], $argv[3]);
                break;
                
            case 'recommend':
                if (count($argv) < 3) {
                    echo COLOR_RED . "Error: Please provide task type\n" . COLOR_RESET;
                    echo "Available task types: api, frontend, database, security, testing, performance, documentation, php, moodle\n";
                    return;
                }
                $this->recommendSubagents($argv[2]);
                break;
                
            case 'search':
                if (count($argv) < 3) {
                    echo COLOR_RED . "Error: Please provide search keyword\n" . COLOR_RESET;
                    return;
                }
                $this->searchSubagents($argv[2]);
                break;
                
            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }
    
    private function showHelp() {
        echo COLOR_BLUE . "=== Claude Code Subagents CLI ===\n" . COLOR_RESET;
        echo "\nUsage:\n";
        echo "  php subagent_cli.php list                    - List all available subagents\n";
        echo "  php subagent_cli.php show <category> <agent> - Show details of a specific subagent\n";
        echo "  php subagent_cli.php recommend <task>        - Get recommendations for a task type\n";
        echo "  php subagent_cli.php search <keyword>        - Search subagents by keyword\n";
        echo "  php subagent_cli.php help                    - Show this help message\n";
        echo "\nTask types for recommendations:\n";
        echo "  api, frontend, database, security, testing, performance, documentation, php, moodle\n";
        echo "\nExample:\n";
        echo "  php subagent_cli.php show 01-core-development backend-developer\n";
        echo "  php subagent_cli.php recommend api\n";
    }
    
    private function listSubagents() {
        echo COLOR_BLUE . "=== Available Subagents ===\n" . COLOR_RESET;
        
        $subagents = $this->manager->listSubagents();
        
        foreach ($subagents as $category => $agents) {
            echo "\n" . COLOR_GREEN . "[$category]" . COLOR_RESET . "\n";
            foreach ($agents as $agent) {
                echo "  • " . $agent . "\n";
            }
        }
        
        echo "\n" . COLOR_YELLOW . "Total categories: " . count($subagents) . COLOR_RESET . "\n";
        
        $totalAgents = 0;
        foreach ($subagents as $agents) {
            $totalAgents += count($agents);
        }
        echo COLOR_YELLOW . "Total subagents: " . $totalAgents . COLOR_RESET . "\n";
    }
    
    private function showSubagent($category, $agentName) {
        $agent = $this->manager->getSubagent($category, $agentName);
        
        if (!$agent) {
            echo COLOR_RED . "Error: Subagent '$agentName' not found in category '$category'\n" . COLOR_RESET;
            return;
        }
        
        echo COLOR_BLUE . "=== Subagent Details ===\n" . COLOR_RESET;
        echo COLOR_GREEN . "Name: " . COLOR_RESET . $agent['name'] . "\n";
        echo COLOR_GREEN . "Category: " . COLOR_RESET . $agent['category'] . "\n";
        echo COLOR_GREEN . "Description: " . COLOR_RESET . $agent['description'] . "\n";
        echo COLOR_GREEN . "Tools: " . COLOR_RESET . $agent['tools'] . "\n";
        echo COLOR_GREEN . "File: " . COLOR_RESET . $agent['file'] . "\n";
        echo "\n" . COLOR_BLUE . "=== Prompt (first 1000 chars) ===" . COLOR_RESET . "\n";
        echo substr($agent['prompt'], 0, 1000) . "\n";
        
        if (strlen($agent['prompt']) > 1000) {
            echo COLOR_YELLOW . "\n... (truncated, full prompt is " . strlen($agent['prompt']) . " characters)" . COLOR_RESET . "\n";
        }
    }
    
    private function recommendSubagents($taskType) {
        echo COLOR_BLUE . "=== Recommendations for '$taskType' ===\n" . COLOR_RESET;
        
        $recommendations = $this->manager->getRecommendations($taskType);
        
        if (empty($recommendations)) {
            echo COLOR_YELLOW . "No specific recommendations found for '$taskType'.\n" . COLOR_RESET;
            echo "Try one of these task types: api, frontend, database, security, testing, performance, documentation, php, moodle\n";
            return;
        }
        
        foreach ($recommendations as $index => $rec) {
            $agent = $this->manager->getSubagent($rec[0], $rec[1]);
            if ($agent) {
                echo "\n" . COLOR_GREEN . ($index + 1) . ". " . $agent['name'] . COLOR_RESET . "\n";
                echo "   Category: " . $rec[0] . "\n";
                echo "   Description: " . $agent['description'] . "\n";
                echo "   Tools: " . $agent['tools'] . "\n";
                echo "   Command: php subagent_cli.php show " . $rec[0] . " " . $rec[1] . "\n";
            }
        }
    }
    
    private function searchSubagents($keyword) {
        echo COLOR_BLUE . "=== Searching for '$keyword' ===\n" . COLOR_RESET;
        
        $keyword = strtolower($keyword);
        $results = [];
        $subagents = $this->manager->listSubagents();
        
        foreach ($subagents as $category => $agents) {
            foreach ($agents as $agentName) {
                $agent = $this->manager->getSubagent($category, $agentName);
                if ($agent) {
                    // Search in name, description, and tools
                    $searchText = strtolower($agent['name'] . ' ' . $agent['description'] . ' ' . $agent['tools']);
                    if (strpos($searchText, $keyword) !== false) {
                        $results[] = [
                            'category' => $category,
                            'name' => $agentName,
                            'agent' => $agent
                        ];
                    }
                }
            }
        }
        
        if (empty($results)) {
            echo COLOR_YELLOW . "No subagents found matching '$keyword'.\n" . COLOR_RESET;
            return;
        }
        
        echo COLOR_GREEN . "Found " . count($results) . " matching subagent(s):\n" . COLOR_RESET;
        
        foreach ($results as $result) {
            echo "\n• " . COLOR_GREEN . $result['agent']['name'] . COLOR_RESET;
            echo " [" . $result['category'] . "]\n";
            echo "  " . $result['agent']['description'] . "\n";
            echo "  Command: php subagent_cli.php show " . $result['category'] . " " . $result['name'] . "\n";
        }
    }
}

// Run the CLI
$cli = new SubagentCLI();
$cli->run($argv);