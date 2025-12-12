<?php
/**
 * collect.php
 *
 * 프론트엔드(ActivityTracker)에서 전송한 이벤트를 수집하여
 * - EventBus에 publish
 * - TriggerRuleEngine으로 규칙 평가 후 에이전트 실행
 *
 * @package ALT42\Events
 * @version 1.0.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/event_bus.php');
require_once(__DIR__ . '/trigger_engine.php');

try {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        throw new Exception("Invalid JSON body [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }

    $studentId = isset($input['student_id']) ? (int)$input['student_id'] : (int)($USER->id ?? 0);
    if ($studentId <= 0) {
        throw new Exception("Invalid student_id [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }

    $sessionId = isset($input['session_id']) ? (string)$input['session_id'] : uniqid('sess_', true);
    $events = $input['events'] ?? null;
    if (!is_array($events)) {
        throw new Exception("Missing events array [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }

    $bus = \ALT42\Events\EventBus::getInstance();
    $engine = new TriggerRuleEngine($studentId);

    $processed = [];
    $triggered = [];

    foreach ($events as $evt) {
        if (!is_array($evt)) continue;
        $eventType = $evt['event_type'] ?? null;
        if (!$eventType) continue;

        $normalized = [
            'event_id' => $evt['event_id'] ?? uniqid('evt_', true),
            'event_type' => (string)$eventType,
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'content_id' => isset($input['content_id']) ? (int)$input['content_id'] : (isset($evt['context']['content_id']) ? (int)$evt['context']['content_id'] : null),
            'analysis_id' => $input['analysis_id'] ?? ($evt['context']['analysis_id'] ?? null),
            'timestamp' => $evt['timestamp'] ?? (int)(microtime(true) * 1000),
            'priority' => isset($evt['priority']) ? (int)$evt['priority'] : 5,
            'data' => $evt['data'] ?? [],
            'context' => $evt['context'] ?? [],
            'source' => 'activity_tracker'
        ];

        // EventBus publish
        $topic = 'student.activity.' . $normalized['event_type'];
        $bus->publish($topic, $normalized, $normalized['priority']);

        // Trigger evaluate + execute
        $matches = $engine->evaluate($normalized);
        foreach ($matches as $m) {
            $triggered[] = $m['agent_id'];
            $engine->execute($m);
        }

        $processed[] = $normalized['event_id'];
    }

    echo json_encode([
        'success' => true,
        'student_id' => $studentId,
        'session_id' => $sessionId,
        'processed_count' => count($processed),
        'processed_event_ids' => $processed,
        'triggered_agents' => array_values(array_unique($triggered))
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


