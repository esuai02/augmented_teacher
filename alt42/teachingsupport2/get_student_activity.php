<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$studentid = $_GET['studentid'] ?? $USER->id;
$page = $_GET['page'] ?? 0;
$perpage = $_GET['perpage'] ?? 10;

try {
    // 권한 확인
    $context = context_system::instance();
    if ($studentid != $USER->id && !has_capability('moodle/site:config', $context)) {
        throw new Exception('접근 권한이 없습니다.');
    }

    // 학생 정보 확인
    $student = $DB->get_record('user', array('id' => $studentid));
    if (!$student) {
        throw new Exception('학생 정보를 찾을 수 없습니다.');
    }

    $offset = $page * $perpage;
    $activities = array();

    // 1. ktm_teaching_interactions에서 최근 활동 가져오기
    if ($DB->get_manager()->table_exists('ktm_teaching_interactions')) {
        $interactions = $DB->get_records_select(
            'ktm_teaching_interactions',
            'userid = ?',
            array($studentid),
            'timecreated DESC',
            '*',
            0,
            $perpage
        );

        foreach ($interactions as $interaction) {
            $activity = new stdClass();
            $activity->type = ($interaction->status === 'completed') ? 'completed' : 'start';
            $activity->title = getActivityTitle($interaction->status, $interaction->problem_type);
            $activity->description = getActivityDescription($interaction->status, $interaction->problem_type);
            $activity->timecreated = $interaction->timecreated;
            $activity->related_id = $interaction->id;
            $activities[] = $activity;
        }
    }

    // 2. ktm_teaching_events에서 최근 활동 가져오기
    if ($DB->get_manager()->table_exists('ktm_teaching_events')) {
        $events = $DB->get_records_select(
            'ktm_teaching_events',
            'userid = ?',
            array($studentid),
            'timecreated DESC',
            '*',
            0,
            $perpage
        );

        foreach ($events as $event) {
            $activity = new stdClass();
            $activity->type = mapEventType($event->event_type);
            $activity->title = $event->event_description;
            $activity->description = getEventDescription($event->event_type, $event->metadata);
            $activity->timecreated = $event->timecreated;
            $activity->related_id = $event->id;
            $activities[] = $activity;
        }
    }

    // 3. 메시지 활동 가져오기
    $messages = $DB->get_records_select(
        'messages',
        'useridto = ? AND (subject LIKE ? OR subject LIKE ?)',
        array($studentid, '%문제 해설%', '%하이튜터링%'),
        'timecreated DESC',
        '*',
        0,
        $perpage
    );

    foreach ($messages as $message) {
        $activity = new stdClass();
        $activity->type = 'message';
        $activity->title = '새로운 문제 해설 메시지';
        $activity->description = '선생님이 문제 해설을 보내주셨습니다.';
        $activity->timecreated = $message->timecreated;
        $activity->related_id = $message->id;
        $activities[] = $activity;
    }

    // 시간순으로 정렬
    usort($activities, function($a, $b) {
        return $b->timecreated - $a->timecreated;
    });

    // 페이지네이션 적용
    $total_activities = count($activities);
    $activities = array_slice($activities, $offset, $perpage);

    // 결과 반환
    echo json_encode(array(
        'success' => true,
        'activities' => $activities,
        'total' => $total_activities,
        'page' => $page,
        'perpage' => $perpage
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}

// 활동 제목 생성
function getActivityTitle($status, $problem_type) {
    switch ($status) {
        case 'completed':
            return '문제 해설 완료';
        case 'started':
            return '문제 해설 시작';
        case 'in_progress':
            return '문제 해설 진행 중';
        case 'error':
            return '문제 해설 오류';
        default:
            return '문제 해설 활동';
    }
}

// 활동 설명 생성
function getActivityDescription($status, $problem_type) {
    $type_text = $problem_type ? "({$problem_type})" : '';
    
    switch ($status) {
        case 'completed':
            return "하이튜터링 문제 해설이 완료되었습니다. {$type_text}";
        case 'started':
            return "새로운 문제 해설을 시작했습니다. {$type_text}";
        case 'in_progress':
            return "문제 해설이 진행 중입니다. {$type_text}";
        case 'error':
            return "문제 해설 중 오류가 발생했습니다. {$type_text}";
        default:
            return "문제 해설 관련 활동이 있었습니다. {$type_text}";
    }
}

// 이벤트 타입 매핑
function mapEventType($event_type) {
    switch ($event_type) {
        case 'message_sent':
            return 'message';
        case 'message_read':
            return 'view';
        case 'problem_analyzed':
            return 'start';
        case 'narration_generated':
            return 'start';
        case 'tts_generated':
            return 'start';
        case 'explanation_completed':
            return 'completed';
        case 'error':
            return 'error';
        default:
            return 'question';
    }
}

// 이벤트 설명 생성
function getEventDescription($event_type, $metadata_json) {
    $metadata = json_decode($metadata_json, true);
    
    switch ($event_type) {
        case 'message_sent':
            return '선생님이 문제 해설 메시지를 전송했습니다.';
        case 'message_read':
            return '문제 해설 메시지를 확인했습니다.';
        case 'problem_analyzed':
            return '문제 분석이 완료되었습니다.';
        case 'narration_generated':
            return '설명 대화가 생성되었습니다.';
        case 'tts_generated':
            return '음성 해설이 생성되었습니다.';
        case 'explanation_completed':
            return '전체 해설 과정이 완료되었습니다.';
        case 'error':
            $error_msg = isset($metadata['error']) ? $metadata['error'] : '알 수 없는 오류';
            return "오류 발생: {$error_msg}";
        default:
            return '학습 활동이 기록되었습니다.';
    }
}
?>