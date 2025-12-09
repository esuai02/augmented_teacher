<?php
/**
 * Shining Stars - Database Initialization
 * Creates and manages database tables for the learning platform
 */

require_once 'includes/config.php';

class ShiningstarsDatabase {
    private $db;
    
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }
    
    /**
     * Initialize all required tables
     */
    public function initialize() {
        try {
            $this->createTables();
            $this->seedDefaultData();
            return ['success' => true, 'message' => 'Database initialized successfully'];
        } catch (Exception $e) {
            error_log("Database initialization error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create all required tables
     */
    private function createTables() {
        $sqlFile = SS_ROOT . '/sql/schema.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("Schema file not found: " . $sqlFile);
        }
        
        $sql = file_get_contents($sqlFile);
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $this->db->execute($statement);
            }
        }
    }
    
    /**
     * Seed default data
     */
    private function seedDefaultData() {
        // Insert default prompt templates
        $this->insertPromptTemplates();
        
        // Create sample journey nodes (for testing)
        $this->createJourneyNodes();
    }
    
    /**
     * Insert AI prompt templates
     */
    private function insertPromptTemplates() {
        $templates = [
            [
                'template_name' => 'reflection_encouragement',
                'template_type' => 'system',
                'template_text' => 'You are a supportive math learning companion. Analyze the student\'s reflection and provide encouraging feedback that builds confidence while addressing their learning needs. Focus on emotional support and motivation.',
                'variables' => json_encode(['student_response', 'emotion_detected', 'confidence_level'])
            ],
            [
                'template_name' => 'insight_generation',
                'template_type' => 'system', 
                'template_text' => 'Based on the student\'s learning journey and reflection, generate meaningful insights about their mathematical understanding. Highlight progress and suggest next steps in a supportive way.',
                'variables' => json_encode(['learning_history', 'current_node', 'student_profile'])
            ],
            [
                'template_name' => 'teacher_alert',
                'template_type' => 'system',
                'template_text' => 'Analyze student data to identify when teacher intervention might be helpful. Consider emotional state, learning progress, and engagement patterns.',
                'variables' => json_encode(['student_data', 'progress_metrics', 'emotional_indicators'])
            ]
        ];
        
        foreach ($templates as $template) {
            // Check if template already exists
            $existing = $this->db->get_record('ss_prompt_templates', ['template_name' => $template['template_name']]);
            if (!$existing) {
                $this->db->insert_record('ss_prompt_templates', (object)$template);
            }
        }
    }
    
    /**
     * Create sample journey nodes for testing
     */
    private function createJourneyNodes() {
        // This would typically be done through an admin interface
        // For now, we'll create a simple structure for testing
        
        $nodes = [
            ['id' => 1, 'name' => 'Numbers and Counting', 'difficulty' => 1],
            ['id' => 2, 'name' => 'Basic Addition', 'difficulty' => 1], 
            ['id' => 3, 'name' => 'Basic Subtraction', 'difficulty' => 1],
            ['id' => 4, 'name' => 'Multiplication Tables', 'difficulty' => 2],
            ['id' => 5, 'name' => 'Division Basics', 'difficulty' => 2],
            ['id' => 6, 'name' => 'Fractions Introduction', 'difficulty' => 3],
            ['id' => 7, 'name' => 'Decimal Numbers', 'difficulty' => 3],
            ['id' => 8, 'name' => 'Basic Algebra', 'difficulty' => 4],
            ['id' => 9, 'name' => 'Geometry Shapes', 'difficulty' => 3],
            ['id' => 10, 'name' => 'Problem Solving', 'difficulty' => 4]
        ];
        
        // Note: In a real implementation, these would be stored in a journey_nodes table
        // For now, we'll use the node_id in the journey_progress table directly
    }
    
    /**
     * Check if database is properly initialized
     */
    public function isInitialized() {
        $requiredTables = [
            'ss_student_profiles',
            'ss_journey_progress', 
            'ss_reflections',
            'ss_ai_feedback',
            'ss_dopamine_events',
            'ss_learning_sessions',
            'ss_prompt_templates',
            'ss_teacher_insights',
            'ss_system_logs',
            'ss_achievements'
        ];
        
        foreach ($requiredTables as $table) {
            if (!$this->db->get_manager()->table_exists($table)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get database status
     */
    public function getStatus() {
        $status = [
            'initialized' => $this->isInitialized(),
            'tables' => [],
            'version' => SS_VERSION
        ];
        
        $requiredTables = [
            'ss_student_profiles',
            'ss_journey_progress', 
            'ss_reflections',
            'ss_ai_feedback',
            'ss_dopamine_events',
            'ss_learning_sessions',
            'ss_prompt_templates',
            'ss_teacher_insights',
            'ss_system_logs',
            'ss_achievements'
        ];
        
        foreach ($requiredTables as $table) {
            $exists = $this->db->get_manager()->table_exists($table);
            $count = $exists ? $this->db->count_records($table) : 0;
            
            $status['tables'][$table] = [
                'exists' => $exists,
                'count' => $count
            ];
        }
        
        return $status;
    }
}

// Usage example
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    $dbManager = new ShiningstarsDatabase();
    
    if (!$dbManager->isInitialized()) {
        echo "Initializing Shining Stars database...\n";
        $result = $dbManager->initialize();
        echo $result['message'] . "\n";
    } else {
        echo "Database already initialized.\n";
        $status = $dbManager->getStatus();
        echo "Status: " . json_encode($status, JSON_PRETTY_PRINT) . "\n";
    }
}
?>