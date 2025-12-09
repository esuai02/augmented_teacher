<?php
/**
 * PersonaEventPublisher - 페르소나 이벤트 발행기
 *
 * @package AugmentedTeacher\PersonaEngine\Comm
 * @version 1.0
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class PersonaEventPublisher {

    private $agentId;
    private $currentFile = __FILE__;
    private $subscribers = [];
    private $communicator;
    private $config = [
        'log_enabled' => true,
        'persist_events' => true,
        'event_ttl' => 604800
    ];

    public function __construct(string $agentId, array $config = []) {
        $this->agentId = $agentId;
        $this->config = array_merge($this->config, $config);

        require_once(__DIR__ . '/AgentCommunicator.php');
        $this->communicator = new AgentCommunicator($agentId);
    }

    public function publish(string $eventType, array $eventData, array $targetAgents = []): array {
        global $DB;

        try {
            $eventId = null;
            if ($this->config['persist_events']) {
                $eventId = $this->persistEvent($eventType, $eventData);
            }

            $localResults = $this->notifyLocalSubscribers($eventType, $eventData);

            $remoteResults = [];
            if (empty($targetAgents)) {
                $remoteResults = $this->communicator->broadcast('persona_event_' . $eventType, ['event_id' => $eventId, 'data' => $eventData]);
            } else {
                $remoteResults = $this->communicator->multicast($targetAgents, 'persona_event_' . $eventType, ['event_id' => $eventId, 'data' => $eventData]);
            }

            return ['success' => true, 'event_id' => $eventId, 'event_type' => $eventType];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function persistEvent(string $eventType, array $eventData): int {
        global $DB;

        $event = new stdClass();
        $event->agent_id = $this->agentId;
        $event->event_type = $eventType;
        $event->event_data = json_encode($eventData);
        $event->user_id = $eventData['user_id'] ?? 0;
        $event->persona_id = $eventData['persona_id'] ?? null;
        $event->created_at = time();
        $event->expires_at = time() + $this->config['event_ttl'];

        return $DB->insert_record('at_persona_events', $event);
    }

    public function subscribe(string $eventType, callable $callback): void {
        if (!isset($this->subscribers[$eventType])) {
            $this->subscribers[$eventType] = [];
        }
        $this->subscribers[$eventType][] = $callback;
    }

    private function notifyLocalSubscribers(string $eventType, array $eventData): array {
        $results = [];
        if (isset($this->subscribers[$eventType])) {
            foreach ($this->subscribers[$eventType] as $callback) {
                try { $results[] = $callback($eventData); } catch (Exception $e) {}
            }
        }
        return $results;
    }

    public function publishPersonaChange(int $userId, string $fromPersona, string $toPersona, array $context = []): array {
        return $this->publish('persona_changed', [
            'user_id' => $userId,
            'from_persona' => $fromPersona,
            'to_persona' => $toPersona,
            'reason' => $context['reason'] ?? 'rule_match',
            'confidence' => $context['confidence'] ?? 0.5
        ]);
    }

    public function publishInterventionRequest(int $userId, string $interventionType, array $params = []): array {
        return $this->publish('intervention_requested', [
            'user_id' => $userId,
            'intervention_type' => $interventionType,
            'params' => $params,
            'requester_agent' => $this->agentId
        ], ['agent20']);
    }

    public function getEventHistory(int $userId, array $types = [], int $limit = 50): array {
        global $DB;
        try {
            $sql = "SELECT * FROM {at_persona_events} WHERE user_id = ?";
            $params = [$userId];
            if (!empty($types)) {
                $placeholders = implode(',', array_fill(0, count($types), '?'));
                $sql .= " AND event_type IN ({$placeholders})";
                $params = array_merge($params, $types);
            }
            $sql .= " ORDER BY created_at DESC LIMIT {$limit}";

            $events = $DB->get_records_sql($sql, $params);
            $result = [];
            foreach ($events as $event) {
                $result[] = [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'event_data' => json_decode($event->event_data, true),
                    'created_at' => $event->created_at
                ];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }
}

/*
 * 관련 DB 테이블:
 * - mdl_at_persona_events
 *   - id INT PRIMARY KEY AUTO_INCREMENT
 *   - agent_id VARCHAR(20)
 *   - event_type VARCHAR(50)
 *   - event_data TEXT
 *   - user_id INT
 *   - persona_id VARCHAR(50)
 *   - created_at INT
 *   - expires_at INT
 */
