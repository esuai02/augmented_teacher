<?php
// File: mvp_system/execution/intervention_dispatcher.php (Line 1)
// Mathking Agentic MVP System - Intervention Dispatcher
//
// Purpose: Prepare and dispatch interventions to LMS based on decisions
// Input: Decision object from Decision Layer
// Output: Intervention execution status
// Reference: agents/agent20_intervention_preparation/agent20_intervention_preparation.md

// Load common dependencies
require_once(__DIR__ . '/../config/app.config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/logger.php');

class InterventionDispatcher
{
    /**
     * Intervention Dispatcher
     *
     * Prepares and executes interventions based on decision layer output
     */

    private $db;
    private $logger;
    private $templates;

    public function __construct()
    {
        $this->db = new MVPDatabase();
        $this->logger = new MVPLogger('execution');
        $this->templates = $this->loadTemplates();
    }

    /**
     * Load intervention message templates
     *
     * Templates are based on agent20_intervention_preparation.md
     * and expanded for Calm Break scenario
     *
     * @return array Intervention templates by action type
     */
    private function loadTemplates()
    {
        return [
            'micro_break' => [
                'critical' => [
                    'title' => '긴급 휴식 알림',
                    'message' => '학습 상태가 매우 낮습니다. 5분간 잠시 쉬어주세요. 간단한 호흡 운동을 추천합니다.',
                    'action_button' => '휴식 시작',
                    'urgency' => 'high'
                ],
                'low' => [
                    'title' => '짧은 휴식 제안',
                    'message' => '침착도가 낮아지고 있어요. 3분 정도 휴식 후 다시 시작하면 좋을 것 같습니다.',
                    'action_button' => '3분 휴식',
                    'urgency' => 'medium'
                ],
                'default' => [
                    'title' => '휴식 권장',
                    'message' => '잠시 휴식을 취하시는 것을 권장합니다.',
                    'action_button' => '휴식하기',
                    'urgency' => 'low'
                ]
            ],
            'ask_teacher' => [
                'default' => [
                    'title' => '선생님 확인 필요',
                    'message' => '학생의 학습 상태를 선생님께서 확인해주시면 좋겠습니다.',
                    'action_button' => '선생님께 알림',
                    'urgency' => 'medium'
                ]
            ],
            'none' => [
                'default' => [
                    'title' => '학습 계속',
                    'message' => '현재 상태가 양호합니다. 계속 학습을 진행하세요.',
                    'action_button' => null,
                    'urgency' => 'none'
                ]
            ]
        ];
    }

    /**
     * Get intervention template based on action and parameters
     *
     * @param string $action Action type (micro_break, ask_teacher, none)
     * @param array $params Action parameters from decision
     * @return array Template configuration
     */
    private function getTemplate($action, $params)
    {
        $urgency = $params['urgency'] ?? 'default';

        // Get action templates
        $action_templates = $this->templates[$action] ?? $this->templates['none'];

        // Get specific template by urgency or use default
        $template = $action_templates[$urgency] ?? $action_templates['default'] ?? $action_templates[array_key_first($action_templates)];

        // Customize message with params if needed
        if (isset($params['duration_minutes'])) {
            $template['message'] = str_replace(
                ['3분', '5분'],
                ["{$params['duration_minutes']}분", "{$params['duration_minutes']}분"],
                $template['message']
            );
        }

        return $template;
    }

    /**
     * Prepare intervention from decision
     *
     * @param array $decision Decision object from Decision Layer
     * @return array Intervention data ready for dispatch
     */
    public function prepare($decision)
    {
        $this->logger->info("Preparing intervention for decision", [
            'decision_id' => $decision['id'] ?? null,
            'action' => $decision['action'],
            'student_id' => $decision['student_id']
        ]);

        // Parse params if JSON string
        $params = is_string($decision['params']) ? json_decode($decision['params'], true) : $decision['params'];

        // Get appropriate template
        $template = $this->getTemplate($decision['action'], $params);

        // Generate unique intervention ID
        $intervention_id = 'int-' . uniqid() . '-' . $decision['student_id'];

        // Build intervention data
        $intervention = [
            'intervention_id' => $intervention_id,
            'decision_id' => $decision['id'] ?? null,
            'type' => $decision['action'],
            'target_student_id' => $decision['student_id'],
            'message' => json_encode([
                'title' => $template['title'],
                'body' => $template['message'],
                'action_button' => $template['action_button'],
                'urgency' => $template['urgency']
            ], JSON_UNESCAPED_UNICODE),
            'scheduled_at' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'metadata' => json_encode([
                'template_source' => 'agent20_intervention_preparation.md',
                'decision_confidence' => $decision['confidence'] ?? 0,
                'decision_rule_id' => $decision['rule_id'] ?? 'unknown',
                'params' => $params
            ], JSON_UNESCAPED_UNICODE),
            'retry_count' => 0
        ];

        $this->logger->info("Intervention prepared", [
            'intervention_id' => $intervention_id,
            'type' => $intervention['type']
        ]);

        return $intervention;
    }

    /**
     * Execute intervention (dispatch to LMS)
     *
     * @param array $intervention Intervention data from prepare()
     * @return array Execution result with status
     */
    public function execute($intervention)
    {
        $this->logger->info("Executing intervention", [
            'intervention_id' => $intervention['intervention_id'],
            'type' => $intervention['type'],
            'student_id' => $intervention['target_student_id']
        ]);

        $start_time = microtime(true);

        try {
            // Store intervention in database first
            $intervention_db_id = $this->db->insert('intervention_execution', $intervention);

            // Dispatch to LMS (Moodle)
            $lms_result = $this->dispatchToLMS($intervention);

            // Update execution record
            $update_data = [
                'executed_at' => date('Y-m-d H:i:s'),
                'status' => $lms_result['success'] ? 'sent' : 'failed',
                'lms_response' => json_encode($lms_result, JSON_UNESCAPED_UNICODE)
            ];

            $this->db->update(
                'intervention_execution',
                $update_data,
                ['id' => $intervention_db_id]
            );

            $execution_time = round((microtime(true) - $start_time) * 1000, 2);

            // Record performance metric
            $this->db->insert('system_metrics', [
                'metric_name' => 'intervention_execution_time',
                'metric_value' => $execution_time,
                'unit' => 'ms',
                'context' => json_encode([
                    'intervention_id' => $intervention['intervention_id'],
                    'type' => $intervention['type']
                ]),
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $this->logger->info("Intervention executed successfully", [
                'intervention_id' => $intervention['intervention_id'],
                'execution_time_ms' => $execution_time,
                'lms_status' => $lms_result['status']
            ]);

            return [
                'success' => true,
                'intervention_id' => $intervention['intervention_id'],
                'intervention_db_id' => $intervention_db_id,
                'status' => $update_data['status'],
                'lms_result' => $lms_result,
                'execution_time_ms' => $execution_time
            ];

        } catch (Exception $e) {
            $this->logger->error("Intervention execution failed", $e, [
                'intervention_id' => $intervention['intervention_id']
            ]);

            // Update status to failed if record exists
            if (isset($intervention_db_id)) {
                $this->db->update(
                    'intervention_execution',
                    [
                        'status' => 'failed',
                        'lms_response' => json_encode(['error' => $e->getMessage()])
                    ],
                    ['id' => $intervention_db_id]
                );
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'intervention_id' => $intervention['intervention_id']
            ];
        }
    }

    /**
     * Dispatch intervention to Moodle LMS
     *
     * MVP: Simulated LMS integration
     * Production: Integrate with Moodle messaging API
     *
     * @param array $intervention Intervention data
     * @return array LMS response
     */
    private function dispatchToLMS($intervention)
    {
        // MVP Simulation: Log to file instead of actual LMS dispatch
        // Production: Use Moodle message_send() or custom plugin API

        $message_data = json_decode($intervention['message'], true);

        // Simulate LMS message creation
        // In production, this would be:
        // message_send($message_object) or custom Moodle plugin API call

        $lms_message_id = time() + rand(1000, 9999);

        $this->logger->info("LMS dispatch (simulated)", [
            'student_id' => $intervention['target_student_id'],
            'message_title' => $message_data['title'] ?? 'N/A',
            'lms_message_id' => $lms_message_id
        ]);

        // Simulate successful LMS response
        return [
            'success' => true,
            'status' => 'sent',
            'message_id' => $lms_message_id,
            'timestamp' => date('Y-m-d H:i:s'),
            'delivery_method' => 'moodle_message',
            'note' => 'MVP simulation - production will use actual Moodle API'
        ];
    }

    /**
     * Get intervention execution status
     *
     * @param string $intervention_id Intervention ID
     * @return array|null Intervention record or null if not found
     */
    public function getStatus($intervention_id)
    {
        $result = $this->db->query(
            "SELECT * FROM mdl_mvp_intervention_execution WHERE intervention_id = ?",
            [$intervention_id]
        );

        return $result[0] ?? null;
    }

    /**
     * Retry failed intervention
     *
     * @param string $intervention_id Intervention ID
     * @param int $max_retries Maximum retry attempts
     * @return array Retry result
     */
    public function retry($intervention_id, $max_retries = 3)
    {
        $intervention = $this->getStatus($intervention_id);

        if (!$intervention) {
            return ['success' => false, 'error' => 'Intervention not found'];
        }

        if ($intervention['status'] === 'sent' || $intervention['status'] === 'delivered') {
            return ['success' => false, 'error' => 'Intervention already succeeded'];
        }

        if ($intervention['retry_count'] >= $max_retries) {
            return ['success' => false, 'error' => 'Max retries exceeded'];
        }

        // Increment retry count
        $this->db->update(
            'intervention_execution',
            ['retry_count' => $intervention['retry_count'] + 1],
            ['id' => $intervention['id']]
        );

        // Re-execute
        return $this->execute($intervention);
    }
}


// =============================================================================
// Usage Examples
// =============================================================================
//
// From PHP:
// $dispatcher = new InterventionDispatcher();
// $decision = [...]; // From Decision Layer
// $intervention = $dispatcher->prepare($decision);
// $result = $dispatcher->execute($intervention);
//
// Check status:
// $status = $dispatcher->getStatus('int-123456');
//
// Retry failed:
// $retry_result = $dispatcher->retry('int-123456');
//
// =============================================================================
