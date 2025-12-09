<?php
/**
 * Agent Data Access Layer for ALT42 Orchestration System
 * Provides secure database access with connection pooling and caching
 */

namespace ALT42\Database;

require_once(__DIR__ . '/../config/event_schemas.php');

use ALT42\Config\EventSchemas;

/**
 * Database access layer with connection pooling and security features
 */
class AgentDataLayer
{
    private static $connection = null;
    private static $connectionPool = array();
    private static $cache = array();
    private static $cacheTimeout = 300; // 5 minutes
    
    /**
     * Get database connection with pooling
     * @return \PDO Database connection
     * @throws Exception If connection fails
     */
    public static function getConnection()
    {
        if (self::$connection === null) {
            self::initializeConnection();
        }
        
        return self::$connection;
    }
    
    /**
     * Initialize database connection
     * @throws Exception If connection fails
     */
    private static function initializeConnection()
    {
        try {
            // Use Moodle database configuration if available
            global $CFG;
            
            if (isset($CFG)) {
                $dsn = "mysql:host={$CFG->dbhost};dbname={$CFG->dbname};charset=utf8mb4";
                $username = $CFG->dbuser;
                $password = $CFG->dbpass;
            } else {
                // Fallback configuration for testing
                $dsn = "mysql:host=localhost;dbname=alt42_orchestration;charset=utf8mb4";
                $username = 'alt42_user';
                $password = 'secure_password';
            }
            
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            self::$connection = new \PDO($dsn, $username, $password, $options);
            
            // Test connection
            self::$connection->query("SELECT 1");
            
        } catch (\PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Execute prepared statement safely
     * @param $query SQL query with placeholders
     * @param array $params Parameters for the query
     * @return PDOStatement Executed statement
     * @throws Exception If execution fails
     */
    public static function executeQuery($query, $params = array())
    {
        try {
            $conn = self::getConnection();
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            error_log("Query execution failed: {$e->getMessage()}, Query: {$query} at " . __FILE__ . ":" . __LINE__);
            throw new \Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Begin transaction
     * @return bool True on success
     */
    public static function beginTransaction()
    {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     * @return bool True on success
     */
    public static function commit()
    {
        return self::getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     * @return bool True on success
     */
    public static function rollback()
    {
        return self::getConnection()->rollBack();
    }
    
    /**
     * Store event in database
     * @param array $eventData Event data
     * @return string Event ID
     * @throws Exception If storage fails
     */
    public static function storeEvent($eventData)
    {
        $eventId = self::generateUUID();
        $priority = EventSchemas::getEventPriority($eventData['topic']);
        
        $query = "
            INSERT INTO alt42_events 
            (event_id, event_type, student_id, teacher_id, priority, status, event_data, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
        ";
        
        $params = [
            $eventId,
            $eventData['topic'],
            $eventData['student_id'] ?? null,
            $eventData['teacher_id'] ?? null,
            $priority,
            json_encode($eventData)
        ];
        
        self::executeQuery($query, $params);
        
        return $eventId;
    }
    
    /**
     * Get pending events by priority
     * @param $limit Maximum number of events to retrieve
     * @return array Array of pending events
     */
    public static function getPendingEvents($limit = 10)
    {
        $cacheKey = "pending_events_{$limit}";
        $cached = self::getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $query = "
            SELECT event_id, event_type, student_id, teacher_id, priority, event_data, created_at 
            FROM alt42_events 
            WHERE status = 'pending' 
            ORDER BY priority ASC, created_at ASC 
            LIMIT ?
        ";
        
        $stmt = self::executeQuery($query, [$limit]);
        $events = $stmt->fetchAll();
        
        // Decode JSON data
        foreach ($events as &$event) {
            $event['event_data'] = json_decode($event['event_data'], true);
        }
        
        self::setCache($cacheKey, $events, 30); // Cache for 30 seconds
        
        return $events;
    }
    
    /**
     * Update event status
     * @param $eventId Event ID
     * @param $status New status
     * @param string|null $errorMessage Error message if any
     * @return bool True on success
     */
    public static function updateEventStatus($eventId, $status, $errorMessage = null)
    {
        $query = "
            UPDATE alt42_events 
            SET status = ?, processed_at = NOW(), error_message = ?
            WHERE event_id = ?
        ";
        
        $stmt = self::executeQuery($query, [$status, $errorMessage, $eventId]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get agent subscriptions
     * @param $eventType Event type
     * @return array Array of subscribed agents
     */
    public static function getAgentSubscriptions($eventType)
    {
        $cacheKey = "agent_subscriptions_{$eventType}";
        $cached = self::getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $query = "
            SELECT agent_id, conditions 
            FROM alt42_event_subscriptions 
            WHERE event_type = ? AND is_active = 1
        ";
        
        $stmt = self::executeQuery($query, [$eventType]);
        $subscriptions = $stmt->fetchAll();
        
        // Decode JSON conditions
        foreach ($subscriptions as &$subscription) {
            $subscription['conditions'] = json_decode($subscription['conditions'] ?? '{}', true);
        }
        
        self::setCache($cacheKey, $subscriptions, 600); // Cache for 10 minutes
        
        return $subscriptions;
    }
    
    /**
     * Log agent processing
     * @param $eventId Event ID
     * @param $agentId Agent ID
     * @param $status Processing status
     * @param array|null $responseData Response data
     * @param string|null $errorMessage Error message
     * @return bool True on success
     */
    public static function logAgentProcessing($eventId, $agentId, $status, $responseData = null, $errorMessage = null)
    {
        $query = "
            INSERT INTO alt42_event_processing_log 
            (event_id, agent_id, processing_start, processing_end, status, response_data, error_message) 
            VALUES (?, ?, NOW(), NOW(), ?, ?, ?)
        ";
        
        $params = [
            $eventId,
            $agentId,
            $status,
            $responseData ? json_encode($responseData) : null,
            $errorMessage
        ];
        
        $stmt = self::executeQuery($query, $params);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get student learning data
     * @param $studentId Student ID
     * @param $days Number of days to look back
     * @return array Student learning data
     */
    public static function getStudentLearningData($studentId, $days = 7)
    {
        $cacheKey = "student_data_{$studentId}_{$days}";
        $cached = self::getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Get from incorrect_answers table (from existing system)
        $query = "
            SELECT problem_id, concept_area, error_type, timestamp 
            FROM incorrect_answers 
            WHERE student_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY timestamp DESC
        ";
        
        $stmt = self::executeQuery($query, [$studentId, $days]);
        $incorrectAnswers = $stmt->fetchAll();
        
        // Get correct answers if table exists
        $correctAnswers = [];
        try {
            $query = "
                SELECT problem_id, concept_area, timestamp 
                FROM correct_answers 
                WHERE student_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY timestamp DESC
            ";
            $stmt = self::executeQuery($query, [$studentId, $days]);
            $correctAnswers = $stmt->fetchAll();
        } catch (\Exception $e) {
            // Table might not exist in test environment
            error_log("correct_answers table not found: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
        }
        
        $data = [
            'student_id' => $studentId,
            'incorrect_answers' => $incorrectAnswers,
            'correct_answers' => $correctAnswers,
            'total_problems' => count($incorrectAnswers) + count($correctAnswers),
            'accuracy_rate' => count($correctAnswers) / max(1, count($incorrectAnswers) + count($correctAnswers))
        ];
        
        self::setCache($cacheKey, $data, 300); // Cache for 5 minutes
        
        return $data;
    }
    
    /**
     * Get student state
     * 학생 현재 상태 조회 (v_student_state 뷰 또는 여러 테이블에서 수집)
     * 
     * @param string $studentId Student ID
     * @return array Student state
     */
    public static function getStudentState($studentId)
    {
        try {
            // v_student_state 뷰가 있다면 사용
            $sql = "
                SELECT 
                    student_id,
                    emotion_state,
                    immersion_level,
                    stress_level,
                    concentration_level,
                    engagement_score,
                    math_confidence,
                    learning_progress,
                    updated_at
                FROM mdl_alt42_v_student_state
                WHERE student_id = ?
                LIMIT 1
            ";
            
            $stmt = self::executeQuery($sql, [$studentId]);
            $state = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($state) {
                return $state;
            }
            
            // Fallback: 기본 학생 정보만 반환
            return ['student_id' => $studentId];
            
        } catch (\Exception $e) {
            error_log("Error getting student state for {$studentId}: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return ['student_id' => $studentId];
        }
    }
    
    /**
     * Get system health metrics
     * @return array System health data
     */
    public static function getSystemHealthMetrics()
    {
        $cacheKey = 'system_health';
        $cached = self::getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Get event processing statistics
        $query = "
            SELECT 
                COUNT(*) as total_events,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_events,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_events,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_events,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_events
            FROM alt42_events 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ";
        
        $stmt = self::executeQuery($query);
        $eventStats = $stmt->fetch();
        
        // Get agent processing performance
        $query = "
            SELECT 
                agent_id,
                COUNT(*) as total_processed,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                AVG(TIMESTAMPDIFF(MICROSECOND, processing_start, processing_end) / 1000) as avg_response_time_ms
            FROM alt42_event_processing_log 
            WHERE processing_start >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY agent_id
        ";
        
        $stmt = self::executeQuery($query);
        $agentStats = $stmt->fetchAll();
        
        $health = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_stats' => $eventStats,
            'agent_performance' => $agentStats,
            'database_connection' => 'healthy'
        ];
        
        self::setCache($cacheKey, $health, 60); // Cache for 1 minute
        
        return $health;
    }
    
    /**
     * Cache management methods
     */
    private static function getFromCache($key)
    {
        if (!isset(self::$cache[$key])) {
            return null;
        }
        
        $cached = self::$cache[$key];
        if ($cached['expires'] < time()) {
            unset(self::$cache[$key]);
            return null;
        }
        
        return $cached['data'];
    }
    
    private static function setCache($key, $data, $ttl = null)
    {
        $ttl = $ttl ?? self::$cacheTimeout;
        self::$cache[$key] = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        
        // Simple cache cleanup - remove expired entries
        if (count(self::$cache) > 100) {
            self::cleanupCache();
        }
    }
    
    private static function cleanupCache()
    {
        $now = time();
        self::$cache = array_filter(self::$cache, function($item) use ($now) {
            return $item['expires'] > $now;
        });
    }
    
    /**
     * Generate UUID v4
     * @return string UUID
     */
    private static function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Create database tables if they don't exist
     * @return bool True on success
     */
    public static function createTables()
    {
        try {
            $conn = self::getConnection();
            
            // Events table
            $eventsTable = "
                CREATE TABLE IF NOT EXISTS alt42_events (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    event_id VARCHAR(36) UNIQUE NOT NULL,
                    event_type VARCHAR(100) NOT NULL,
                    student_id VARCHAR(20) NULL,
                    teacher_id VARCHAR(20) NULL,
                    priority TINYINT DEFAULT 5,
                    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
                    event_data JSON NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    processed_at TIMESTAMP NULL,
                    retry_count TINYINT DEFAULT 0,
                    error_message TEXT NULL,
                    INDEX idx_status_priority (status, priority),
                    INDEX idx_student_id (student_id),
                    INDEX idx_event_type (event_type),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            // Event subscriptions table
            $subscriptionsTable = "
                CREATE TABLE IF NOT EXISTS alt42_event_subscriptions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    agent_id TINYINT NOT NULL,
                    event_type VARCHAR(100) NOT NULL,
                    conditions JSON NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_agent_event (agent_id, event_type),
                    INDEX idx_event_type (event_type),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            // Event processing log table
            $processingLogTable = "
                CREATE TABLE IF NOT EXISTS alt42_event_processing_log (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    event_id VARCHAR(36) NOT NULL,
                    agent_id TINYINT NOT NULL,
                    processing_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    processing_end TIMESTAMP NULL,
                    status ENUM('success', 'error', 'timeout') NOT NULL,
                    response_data JSON NULL,
                    error_message TEXT NULL,
                    INDEX idx_event_id (event_id),
                    INDEX idx_agent_id (agent_id),
                    INDEX idx_processing_start (processing_start)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $conn->exec($eventsTable);
            $conn->exec($subscriptionsTable);
            $conn->exec($processingLogTable);
            
            return true;
            
        } catch (\PDOException $e) {
            error_log("Failed to create tables: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return false;
        }
    }
    
    /**
     * Insert default agent subscriptions
     * @return bool True on success
     */
    public static function insertDefaultSubscriptions()
    {
        try {
            $subscriptions = [
                // Agent 14 - Error Notes Analysis
                [14, 'learning.answer_wrong', null],
                [14, 'learning.problem_submitted', json_encode(['result' => 'wrong'])],
                
                // Agent 8 - Concentration Analysis
                [8, 'bio.stress_spike', null],
                [8, 'bio.concentration_drop', null],
                
                // Agent 15 - Teacher Feedback
                [15, 'teacher.manual_intervention', null],
                
                // Agent 1 - Student Profile (responds to all events for context)
                [1, 'learning.answer_wrong', null],
                [1, 'learning.answer_correct', null],
                [1, 'bio.stress_spike', null],
                
                // OA - Orchestrator Agent (responds to high-priority events)
                [0, 'teacher.manual_intervention', null],
                [0, 'bio.stress_spike', json_encode(['stress_level' => ['min' => 8]])],
                [0, 'system.error', null]
            ];
            
            foreach ($subscriptions as $subscription) {
                $query = "
                    INSERT IGNORE INTO alt42_event_subscriptions 
                    (agent_id, event_type, conditions, is_active) 
                    VALUES (?, ?, ?, 1)
                ";
                
                self::executeQuery($query, $subscription);
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Failed to insert default subscriptions: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return false;
        }
    }
}

/**
 * Database Schema Summary:
 * 
 * alt42_events:
 * - Stores all incoming events with priority-based queuing
 * - JSON event_data field for flexible event payloads
 * - Status tracking for processing pipeline
 * 
 * alt42_event_subscriptions:
 * - Defines which agents respond to which event types
 * - Supports conditional subscriptions via JSON conditions
 * - Can be dynamically updated for system tuning
 * 
 * alt42_event_processing_log:
 * - Tracks agent processing performance and results
 * - Enables debugging and performance monitoring
 * - Stores agent responses for audit trails
 */
?>

