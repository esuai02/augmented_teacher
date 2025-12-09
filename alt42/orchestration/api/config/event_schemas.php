<?php
/**
 * Event Validation Schemas for ALT42 Orchestration System
 * Defines validation rules for all event types in the system
 */

namespace ALT42\Config;

/**
 * Event schema definitions
 * Each event type has its required fields and validation rules
 */
class EventSchemas
{
    /**
     * Get all event schemas
     * @return array Array of event schemas
     */
    public static function getAllSchemas(): array
    {
        return [
            'learning.problem_submitted' => self::getLearningProblemSchema(),
            'learning.answer_correct' => self::getLearningAnswerSchema(),
            'learning.answer_wrong' => self::getLearningAnswerSchema(),
            'bio.stress_spike' => self::getBioStressSchema(),
            'bio.concentration_drop' => self::getBioConcentrationSchema(),
            'cron.heartbeat_30m' => self::getCronHeartbeatSchema(),
            'cron.daily_analysis' => self::getCronDailySchema(),
            'teacher.manual_intervention' => self::getTeacherInterventionSchema(),
            'system.agent_response' => self::getAgentResponseSchema(),
            'system.error' => self::getSystemErrorSchema(),
            'system.new_student' => self::getNewStudentSchema()
        ];
    }

    /**
     * Validate event against schema
     * @param string $eventType Event type
     * @param array $data Event data
     * @return array Validation result with errors if any
     */
    public static function validateEvent(string $eventType, array $data): array
    {
        $schemas = self::getAllSchemas();
        
        if (!isset($schemas[$eventType])) {
            return [
                'valid' => false,
                'errors' => ['Unknown event type: ' . $eventType . ' at ' . __FILE__ . ':' . __LINE__]
            ];
        }
        
        $schema = $schemas[$eventType];
        $errors = [];
        
        // Check required fields
        foreach ($schema['required'] as $field) {
            if (!isset($data[$field])) {
                $errors[] = "Required field '{$field}' is missing";
            }
        }
        
        // Validate field types and constraints
        foreach ($schema['properties'] as $field => $rules) {
            if (!isset($data[$field])) {
                continue; // Skip validation if field is not present
            }
            
            $value = $data[$field];
            
            // Type validation
            if (isset($rules['type'])) {
                if (!self::validateFieldType($value, $rules['type'])) {
                    $errors[] = "Field '{$field}' must be of type {$rules['type']}";
                    continue;
                }
            }
            
            // Additional validations
            if (isset($rules['min']) && is_numeric($value) && $value < $rules['min']) {
                $errors[] = "Field '{$field}' must be >= {$rules['min']}";
            }
            
            if (isset($rules['max']) && is_numeric($value) && $value > $rules['max']) {
                $errors[] = "Field '{$field}' must be <= {$rules['max']}";
            }
            
            if (isset($rules['enum']) && !in_array($value, $rules['enum'])) {
                $errors[] = "Field '{$field}' must be one of: " . implode(', ', $rules['enum']);
            }
            
            if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                $errors[] = "Field '{$field}' does not match required pattern";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate field type
     * @param mixed $value Value to validate
     * @param string $type Expected type
     * @return bool True if valid
     */
    private static function validateFieldType($value, string $type): bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'integer':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            case 'float':
            case 'number':
                return is_numeric($value);
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'timestamp':
                return self::validateTimestamp($value);
            default:
                return true;
        }
    }
    
    /**
     * Validate timestamp format
     * @param string $timestamp Timestamp to validate
     * @return bool True if valid
     */
    private static function validateTimestamp(string $timestamp): bool
    {
        return (bool) strtotime($timestamp);
    }
    
    /**
     * Learning problem submission schema
     */
    private static function getLearningProblemSchema(): array
    {
        return [
            'required' => ['student_id', 'problem_id', 'result', 'timestamp'],
            'properties' => [
                'student_id' => [
                    'type' => 'string',
                    'pattern' => '/^S\d{7}$/'
                ],
                'problem_id' => [
                    'type' => 'string'
                ],
                'result' => [
                    'type' => 'string',
                    'enum' => ['correct', 'wrong', 'partial']
                ],
                'difficulty_level' => [
                    'type' => 'integer',
                    'min' => 1,
                    'max' => 10
                ],
                'time_spent' => [
                    'type' => 'integer',
                    'min' => 0
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * Learning answer schema (correct/wrong)
     */
    private static function getLearningAnswerSchema(): array
    {
        return [
            'required' => ['student_id', 'result', 'timestamp'],
            'properties' => [
                'student_id' => [
                    'type' => 'string',
                    'pattern' => '/^S\d{7}$/'
                ],
                'result' => [
                    'type' => 'string',
                    'enum' => ['correct', 'wrong']
                ],
                'problem_type' => [
                    'type' => 'string'
                ],
                'concept_area' => [
                    'type' => 'string'
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * Bio stress spike schema
     */
    private static function getBioStressSchema(): array
    {
        return [
            'required' => ['student_id', 'stress_level', 'timestamp'],
            'properties' => [
                'student_id' => [
                    'type' => 'string',
                    'pattern' => '/^S\d{7}$/'
                ],
                'stress_level' => [
                    'type' => 'float',
                    'min' => 0,
                    'max' => 10
                ],
                'trigger_activity' => [
                    'type' => 'string'
                ],
                'duration_minutes' => [
                    'type' => 'integer',
                    'min' => 0
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * Bio concentration drop schema
     */
    private static function getBioConcentrationSchema(): array
    {
        return [
            'required' => ['student_id', 'concentration_level', 'timestamp'],
            'properties' => [
                'student_id' => [
                    'type' => 'string',
                    'pattern' => '/^S\d{7}$/'
                ],
                'concentration_level' => [
                    'type' => 'float',
                    'min' => 0,
                    'max' => 10
                ],
                'activity_type' => [
                    'type' => 'string'
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * Cron heartbeat schema
     */
    private static function getCronHeartbeatSchema(): array
    {
        return [
            'required' => ['type', 'timestamp'],
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['periodic', 'manual']
                ],
                'interval_minutes' => [
                    'type' => 'integer',
                    'min' => 1
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * Cron daily analysis schema
     */
    private static function getCronDailySchema(): array
    {
        return [
            'required' => ['analysis_date', 'timestamp'],
            'properties' => [
                'analysis_date' => [
                    'type' => 'string',
                    'pattern' => '/^\d{4}-\d{2}-\d{2}$/'
                ],
                'student_count' => [
                    'type' => 'integer',
                    'min' => 0
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * Teacher manual intervention schema
     */
    private static function getTeacherInterventionSchema(): array
    {
        return [
            'required' => ['teacher_id', 'student_id', 'intervention_type', 'timestamp'],
            'properties' => [
                'teacher_id' => [
                    'type' => 'string',
                    'pattern' => '/^T\d{7}$/'
                ],
                'student_id' => [
                    'type' => 'string',
                    'pattern' => '/^S\d{7}$/'
                ],
                'intervention_type' => [
                    'type' => 'string',
                    'enum' => ['guidance', 'correction', 'encouragement', 'assessment']
                ],
                'priority' => [
                    'type' => 'integer',
                    'min' => 1,
                    'max' => 10
                ],
                'message' => [
                    'type' => 'string'
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * System agent response schema
     */
    private static function getAgentResponseSchema(): array
    {
        return [
            'required' => ['agent_id', 'request_id', 'status', 'timestamp'],
            'properties' => [
                'agent_id' => [
                    'type' => 'integer',
                    'min' => 1,
                    'max' => 21
                ],
                'request_id' => [
                    'type' => 'string'
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['success', 'error', 'partial', 'timeout']
                ],
                'response_data' => [
                    'type' => 'array'
                ],
                'execution_time_ms' => [
                    'type' => 'integer',
                    'min' => 0
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * System error schema
     */
    private static function getSystemErrorSchema(): array
    {
        return [
            'required' => ['error_code', 'error_message', 'timestamp'],
            'properties' => [
                'error_code' => [
                    'type' => 'string'
                ],
                'error_message' => [
                    'type' => 'string'
                ],
                'component' => [
                    'type' => 'string'
                ],
                'stack_trace' => [
                    'type' => 'string'
                ],
                'severity' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high', 'critical']
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * New student onboarding schema
     */
    private static function getNewStudentSchema(): array
    {
        return [
            'required' => ['student_id', 'student_name', 'email', 'grade_level', 'timestamp'],
            'properties' => [
                'student_id' => [
                    'type' => 'string',
                    'pattern' => '/^S\d{7}$/'
                ],
                'student_name' => [
                    'type' => 'string'
                ],
                'email' => [
                    'type' => 'string',
                    'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
                ],
                'grade_level' => [
                    'type' => 'integer',
                    'min' => 1,
                    'max' => 12
                ],
                'parent_email' => [
                    'type' => 'string',
                    'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
                ],
                'preferred_language' => [
                    'type' => 'string',
                    'enum' => ['en', 'ko', 'zh', 'ja', 'es']
                ],
                'initial_assessment_requested' => [
                    'type' => 'boolean'
                ],
                'timestamp' => [
                    'type' => 'timestamp'
                ]
            ]
        ];
    }
    
    /**
     * Get event priority based on type
     * @param string $eventType Event type
     * @return int Priority level (1=highest, 10=lowest)
     */
    public static function getEventPriority(string $eventType): int
    {
        $priorities = [
            'system.error' => 1,
            'system.new_student' => 2,
            'teacher.manual_intervention' => 3,
            'bio.stress_spike' => 4,
            'bio.concentration_drop' => 5,
            'learning.answer_wrong' => 6,
            'learning.answer_correct' => 7,
            'learning.problem_submitted' => 8,
            'system.agent_response' => 9,
            'cron.daily_analysis' => 10,
            'cron.heartbeat_30m' => 10
        ];
        
        return $priorities[$eventType] ?? 10;
    }
    
    /**
     * Check if event requires immediate processing
     * @param string $eventType Event type
     * @return bool True if requires immediate processing
     */
    public static function requiresImmediateProcessing(string $eventType): bool
    {
        $immediateEvents = [
            'system.error',
            'system.new_student',
            'teacher.manual_intervention',
            'bio.stress_spike',
            'bio.concentration_drop'
        ];
        
        return in_array($eventType, $immediateEvents);
    }
}

/**
 * Database Schema:
 * 
 * Events Table:
 * - id (INT PRIMARY KEY AUTO_INCREMENT)
 * - event_id (VARCHAR(36) UNIQUE) // UUID
 * - event_type (VARCHAR(100) NOT NULL)
 * - student_id (VARCHAR(20) INDEX)
 * - teacher_id (VARCHAR(20) INDEX)
 * - priority (TINYINT DEFAULT 5)
 * - status (ENUM('pending', 'processing', 'completed', 'failed'))
 * - event_data (JSON)
 * - created_at (TIMESTAMP)
 * - processed_at (TIMESTAMP NULL)
 * - retry_count (TINYINT DEFAULT 0)
 * - error_message (TEXT NULL)
 * 
 * Event Subscriptions Table:
 * - id (INT PRIMARY KEY AUTO_INCREMENT)
 * - agent_id (TINYINT NOT NULL)
 * - event_type (VARCHAR(100) NOT NULL)
 * - conditions (JSON NULL) // Additional filtering conditions
 * - is_active (BOOLEAN DEFAULT TRUE)
 * - created_at (TIMESTAMP)
 * 
 * Event Processing Log:
 * - id (INT PRIMARY KEY AUTO_INCREMENT)
 * - event_id (VARCHAR(36))
 * - agent_id (TINYINT)
 * - processing_start (TIMESTAMP)
 * - processing_end (TIMESTAMP NULL)
 * - status (ENUM('success', 'error', 'timeout'))
 * - response_data (JSON NULL)
 * - error_message (TEXT NULL)
 */
?>

