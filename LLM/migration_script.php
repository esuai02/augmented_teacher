<?php
/**
 * Editor Migration Script
 * Safe deployment script for TinyMCE to Summernote migration
 * 
 * Usage:
 * php migration_script.php --action=validate
 * php migration_script.php --action=migrate --stage=1
 * php migration_script.php --action=rollback
 */

class EditorMigrationScript {
    private $stages = [
        1 => 'Enable dual mode for testing',
        2 => 'Set Summernote as default with TinyMCE fallback',
        3 => 'Full migration with TinyMCE cleanup',
        4 => 'Remove TinyMCE dependencies'
    ];
    
    private $backupDir = './migration_backups/';
    private $configFile = './migration_config.json';
    
    public function __construct() {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Main execution function
     */
    public function execute($action, $options = []) {
        switch ($action) {
            case 'validate':
                return $this->validateEnvironment();
            
            case 'migrate':
                $stage = $options['stage'] ?? 1;
                return $this->migrate($stage);
            
            case 'rollback':
                return $this->rollback();
            
            case 'status':
                return $this->getStatus();
            
            default:
                $this->outputError("Unknown action: $action");
                return false;
        }
    }
    
    /**
     * Validate migration environment
     */
    private function validateEnvironment() {
        $this->output("=== Migration Environment Validation ===\n");
        
        $checks = [
            'PHP Version' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'Write Permissions' => is_writable('.'),
            'Backup Directory' => is_writable($this->backupDir),
            'jQuery CDN' => $this->checkURL('https://code.jquery.com/jquery-3.6.0.min.js'),
            'Summernote CDN' => $this->checkURL('https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js'),
            'TinyMCE CDN' => $this->checkURL('https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js'),
        ];
        
        $allPassed = true;
        foreach ($checks as $check => $result) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            $this->output("$check: $status\n");
            if (!$result) $allPassed = false;
        }
        
        if ($allPassed) {
            $this->output("\n✅ All validation checks passed! Ready for migration.\n");
        } else {
            $this->output("\n❌ Validation failed! Please fix issues before migrating.\n");
        }
        
        return $allPassed;
    }
    
    /**
     * Execute migration stage
     */
    private function migrate($stage) {
        if (!$this->validateEnvironment()) {
            $this->outputError("Environment validation failed. Cannot proceed with migration.");
            return false;
        }
        
        $this->output("=== Migration Stage $stage: {$this->stages[$stage]} ===\n");
        
        // Create backup
        $backupFile = $this->createBackup();
        if (!$backupFile) {
            $this->outputError("Failed to create backup. Aborting migration.");
            return false;
        }
        
        $this->output("✅ Backup created: $backupFile\n");
        
        try {
            switch ($stage) {
                case 1:
                    return $this->migrateStage1();
                case 2:
                    return $this->migrateStage2();
                case 3:
                    return $this->migrateStage3();
                case 4:
                    return $this->migrateStage4();
                default:
                    $this->outputError("Invalid migration stage: $stage");
                    return false;
            }
        } catch (Exception $e) {
            $this->outputError("Migration failed: " . $e->getMessage());
            $this->output("Rolling back changes...\n");
            $this->restoreBackup($backupFile);
            return false;
        }
    }
    
    /**
     * Stage 1: Enable dual mode for testing
     */
    private function migrateStage1() {
        $config = [
            'stage' => 1,
            'dual_mode_enabled' => true,
            'default_editor' => 'tinymce',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->saveMigrationConfig($config);
        
        $this->output("✅ Stage 1 completed: Dual mode enabled for testing\n");
        $this->output("📝 Users can now test both editors side by side\n");
        $this->output("🔗 Test URL: editprompt.php?dual_mode=1\n");
        
        return true;
    }
    
    /**
     * Stage 2: Set Summernote as default
     */
    private function migrateStage2() {
        $config = $this->getMigrationConfig();
        $config['stage'] = 2;
        $config['default_editor'] = 'summernote';
        $config['timestamp'] = date('Y-m-d H:i:s');
        
        $this->saveMigrationConfig($config);
        
        $this->output("✅ Stage 2 completed: Summernote is now the default editor\n");
        $this->output("📝 TinyMCE still available as fallback\n");
        
        return true;
    }
    
    /**
     * Stage 3: Full migration
     */
    private function migrateStage3() {
        $config = $this->getMigrationConfig();
        $config['stage'] = 3;
        $config['tinymce_disabled'] = true;
        $config['timestamp'] = date('Y-m-d H:i:s');
        
        $this->saveMigrationConfig($config);
        
        $this->output("✅ Stage 3 completed: Full migration to Summernote\n");
        $this->output("📝 TinyMCE disabled but kept for emergency rollback\n");
        
        return true;
    }
    
    /**
     * Stage 4: Remove TinyMCE
     */
    private function migrateStage4() {
        $config = $this->getMigrationConfig();
        $config['stage'] = 4;
        $config['migration_complete'] = true;
        $config['timestamp'] = date('Y-m-d H:i:s');
        
        $this->saveMigrationConfig($config);
        
        $this->output("✅ Stage 4 completed: Migration fully complete\n");
        $this->output("🎉 TinyMCE dependencies can be removed\n");
        
        return true;
    }
    
    /**
     * Rollback migration
     */
    private function rollback() {
        $this->output("=== Migration Rollback ===\n");
        
        $backups = glob($this->backupDir . 'editprompt_*.php');
        if (empty($backups)) {
            $this->outputError("No backups found for rollback.");
            return false;
        }
        
        // Get latest backup
        $latestBackup = array_reduce($backups, function($a, $b) {
            return filemtime($a) > filemtime($b) ? $a : $b;
        });
        
        if ($this->restoreBackup($latestBackup)) {
            // Reset migration config
            $config = [
                'stage' => 0,
                'rolled_back' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $this->saveMigrationConfig($config);
            
            $this->output("✅ Rollback completed successfully\n");
            $this->output("📄 Restored from: $latestBackup\n");
            return true;
        }
        
        return false;
    }
    
    /**
     * Create backup
     */
    private function createBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupDir . "editprompt_{$timestamp}.php";
        
        if (copy('editprompt.php', $backupFile)) {
            return $backupFile;
        }
        
        return false;
    }
    
    /**
     * Restore from backup
     */
    private function restoreBackup($backupFile) {
        if (!file_exists($backupFile)) {
            $this->outputError("Backup file not found: $backupFile");
            return false;
        }
        
        return copy($backupFile, 'editprompt.php');
    }
    
    /**
     * Get migration status
     */
    private function getStatus() {
        $config = $this->getMigrationConfig();
        
        $this->output("=== Migration Status ===\n");
        $this->output("Current Stage: " . ($config['stage'] ?? 0) . "\n");
        $this->output("Default Editor: " . ($config['default_editor'] ?? 'tinymce') . "\n");
        $this->output("Dual Mode: " . ($config['dual_mode_enabled'] ?? false ? 'Enabled' : 'Disabled') . "\n");
        $this->output("Last Update: " . ($config['timestamp'] ?? 'Never') . "\n");
        
        if ($config['migration_complete'] ?? false) {
            $this->output("✅ Migration Status: COMPLETE\n");
        } elseif (($config['stage'] ?? 0) > 0) {
            $this->output("🔄 Migration Status: IN PROGRESS\n");
        } else {
            $this->output("⭐ Migration Status: NOT STARTED\n");
        }
        
        return true;
    }
    
    /**
     * Check URL availability
     */
    private function checkURL($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    /**
     * Migration config management
     */
    private function getMigrationConfig() {
        if (file_exists($this->configFile)) {
            return json_decode(file_get_contents($this->configFile), true) ?: [];
        }
        return [];
    }
    
    private function saveMigrationConfig($config) {
        file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
    }
    
    /**
     * Output helpers
     */
    private function output($message) {
        echo $message;
    }
    
    private function outputError($message) {
        echo "❌ ERROR: $message\n";
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['action:', 'stage:']);
    
    if (!isset($options['action'])) {
        echo "Usage: php migration_script.php --action=<action> [--stage=<stage>]\n";
        echo "Actions: validate, migrate, rollback, status\n";
        echo "Stages: 1-4 (for migrate action)\n";
        exit(1);
    }
    
    $migration = new EditorMigrationScript();
    $success = $migration->execute($options['action'], $options);
    
    exit($success ? 0 : 1);
}
?>