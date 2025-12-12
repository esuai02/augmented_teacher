<?php
/**
 * Agent04 Persona System - chat07.php (포모도로)
 *
 * 포모도로 학습법 적용 시 발생하는 인지관성 패턴에 대한 대화형 진단 인터페이스
 * rules07.yaml 기반 3탭 구조 (학생 대화 / 선생님 입력 / 시스템 데이터)
 *
 * @version 3.0.0
 * @since 2025-12-04
 * @author Augmented Teacher Team
 * @reference rules07.yaml
 */

// Moodle 설정 및 인증
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 현재 파일 정보
$current_file = basename(__FILE__);
$current_line = __LINE__;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';

// 현재 탭 결정
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'student';
if ($current_tab === 'teacher' && $role === 'student') {
    $current_tab = 'student';
}

/**
 * 포모도로 진단 질문 클래스
 * rules07.yaml의 9개 sub_items 기반 27개 학생 질문
 */
class PomodoroQuestions {

    // 학생용 대화 질문 (9개 영역 × 3개 = 27개)
    public static $studentQuestions = [
        // S1: 포모도로 효능감 인식 (pomodoro_efficacy)
        'pomodoro_efficacy_1' => [
            'sub_item' => 'pomodoro_efficacy',
            'question' => '포모도로 25분+5분 시간 구성이 나한테 맞는다고 생각해?',
            'description' => '포모도로 기본 시간에 대한 인식',
            'placeholder' => '예: 25분이 너무 길어서 중간에 지쳐요 / 적당해요 / 더 길어도 될 것 같아요...',
            'pattern_hint' => 'pomodoro_skepticism'
        ],
        'pomodoro_efficacy_2' => [
            'sub_item' => 'pomodoro_efficacy',
            'question' => '타이머가 울리면 어떻게 해? 바로 멈추는 편이야?',
            'description' => '타이머 준수 여부',
            'placeholder' => '예: 하던 것 마무리하고 쉬어요 / 타이머 무시할 때가 많아요 / 바로 멈춰요...',
            'pattern_hint' => 'timer_ignorance'
        ],
        'pomodoro_efficacy_3' => [
            'sub_item' => 'pomodoro_efficacy',
            'question' => '5분 휴식 시간에 쉬는 게 편해? 아니면 좀 찜찜해?',
            'description' => '휴식 시간에 대한 심리적 태도',
            'placeholder' => '예: 쉬면 뭔가 불안해요 / 편하게 쉬어요 / 휴식이 아까워요...',
            'pattern_hint' => 'rest_guilt'
        ],

        // S2: 작성내용의 범위 정하기 (scope_setting)
        'scope_setting_1' => [
            'sub_item' => 'scope_setting',
            'question' => '포모도로 시작 전에 이번 25분 동안 뭘 할지 정해?',
            'description' => '세션 목표 설정 습관',
            'placeholder' => '예: 대충 \"공부하자\"로 시작해요 / 구체적으로 정해요 / 정하긴 하는데 지키기 어려워요...',
            'pattern_hint' => 'scope_undefined'
        ],
        'scope_setting_2' => [
            'sub_item' => 'scope_setting',
            'question' => '25분 안에 할 양을 정할 때 보통 적당히 정하는 편이야?',
            'description' => '범위 설정의 현실성',
            'placeholder' => '예: 많이 잡았다가 못 끝내요 / 적당히 잡아요 / 너무 적게 잡는 것 같아요...',
            'pattern_hint' => 'scope_overestimation'
        ],
        'scope_setting_3' => [
            'sub_item' => 'scope_setting',
            'question' => '포모도로 범위 정할 때 어려운 내용도 포함시키는 편이야?',
            'description' => '우선순위 고려 여부',
            'placeholder' => '예: 쉬운 것만 먼저 해요 / 어려운 것도 섞어요 / 어려운 건 나중에 미루게 돼요...',
            'pattern_hint' => 'priority_ignorance'
        ],

        // S3: 내용입력 방법 확인 (input_method)
        'input_method_1' => [
            'sub_item' => 'input_method',
            'question' => '학습한 내용을 기록할 때 어떻게 적어야 할지 알겠어?',
            'description' => '기록 방법에 대한 이해',
            'placeholder' => '예: 뭘 어떻게 적어야 하는지 잘 모르겠어요 / 나만의 방식이 있어요...',
            'pattern_hint' => 'input_confusion'
        ],
        'input_method_2' => [
            'sub_item' => 'input_method',
            'question' => '기록하는 데 시간이 얼마나 걸려? 많이 걸리는 편이야?',
            'description' => '기록 시간 효율성',
            'placeholder' => '예: 기록하느라 시간이 많이 들어요 / 빠르게 핵심만 적어요 / 거의 안 적어요...',
            'pattern_hint' => 'excessive_recording'
        ],
        'input_method_3' => [
            'sub_item' => 'input_method',
            'question' => '학습 기록 자체가 귀찮을 때가 있어?',
            'description' => '기록 습관에 대한 태도',
            'placeholder' => '예: 귀찮아서 안 할 때가 많아요 / 습관이 됐어요 / 필요성을 못 느껴요...',
            'pattern_hint' => 'recording_avoidance'
        ],

        // S4: 귀가검사 준비 (return_check_prep)
        'return_check_prep_1' => [
            'sub_item' => 'return_check_prep',
            'question' => '공부 끝나고 집 가기 전에 오늘 배운 것 점검하는 시간 있어?',
            'description' => '귀가검사에 대한 인식',
            'placeholder' => '예: 그런 시간 없어요 / 가끔 해요 / 매일 하려고 해요...',
            'pattern_hint' => 'return_check_unawareness'
        ],
        'return_check_prep_2' => [
            'sub_item' => 'return_check_prep',
            'question' => '귀가검사 준비할 때 진짜 이해했는지 확인해봐?',
            'description' => '점검의 실질성',
            'placeholder' => '예: 대충 훑어보고 끝내요 / 핵심을 설명할 수 있는지 확인해요 / 그냥 형식적으로 해요...',
            'pattern_hint' => 'superficial_preparation'
        ],
        'return_check_prep_3' => [
            'sub_item' => 'return_check_prep',
            'question' => '귀가검사 준비할 시간을 미리 계획에 넣어둬?',
            'description' => '점검 시간 확보 여부',
            'placeholder' => '예: 시간이 없어서 못 해요 / 마지막 포모도로에 포함시켜요 / 계획에 없어요...',
            'pattern_hint' => 'insufficient_prep_time'
        ],

        // S5: 세션별 성찰활동 (session_reflection)
        'session_reflection_1' => [
            'sub_item' => 'session_reflection',
            'question' => '포모도로 한 세션 끝나면 어땠는지 생각해봐?',
            'description' => '세션 후 성찰 여부',
            'placeholder' => '예: 바로 다음으로 넘어가요 / 잠깐 돌아봐요 / 성찰이 뭔지 잘 모르겠어요...',
            'pattern_hint' => 'reflection_skip'
        ],
        'session_reflection_2' => [
            'sub_item' => 'session_reflection',
            'question' => '성찰할 때 \"잘했다/못했다\" 말고 더 구체적으로 생각해봐?',
            'description' => '성찰의 깊이',
            'placeholder' => '예: 그냥 잘했다/못했다 정도로 끝나요 / 왜 그랬는지까지 생각해요...',
            'pattern_hint' => 'shallow_reflection'
        ],
        'session_reflection_3' => [
            'sub_item' => 'session_reflection',
            'question' => '성찰에서 깨달은 걸 다음 세션에 반영해봐?',
            'description' => '성찰의 실질적 활용',
            'placeholder' => '예: 그냥 생각만 하고 끝나요 / 다음에 적용하려고 해요 / 기억이 안 나요...',
            'pattern_hint' => 'reflection_unutilized'
        ],

        // S6: 입력과정에 대한 성찰 (input_reflection)
        'input_reflection_1' => [
            'sub_item' => 'input_reflection',
            'question' => '내가 기록하는 방식이 효율적인지 생각해본 적 있어?',
            'description' => '기록 방식에 대한 메타인지',
            'placeholder' => '예: 생각해본 적 없어요 / 가끔 돌아봐요 / 계속 개선하려고 해요...',
            'pattern_hint' => 'input_unreflected'
        ],
        'input_reflection_2' => [
            'sub_item' => 'input_reflection',
            'question' => '항상 같은 방식으로 기록해? 다른 방법도 시도해봐?',
            'description' => '기록 방법의 다양성',
            'placeholder' => '예: 늘 같은 방식이에요 / 가끔 다른 방법 써봐요 / 마인드맵도 해봤어요...',
            'pattern_hint' => 'input_habit_fixation'
        ],
        'input_reflection_3' => [
            'sub_item' => 'input_reflection',
            'question' => '기록한 내용이 나중에 볼 때 도움이 되는 편이야?',
            'description' => '기록 품질에 대한 인식',
            'placeholder' => '예: 나중에 봐도 뭔지 모르겠어요 / 복습할 때 도움돼요 / 확인해본 적 없어요...',
            'pattern_hint' => 'recording_quality_neglect'
        ],

        // S7: 감정표현 활용 (emotion_expression)
        'emotion_expression_1' => [
            'sub_item' => 'emotion_expression',
            'question' => '공부하면서 느낀 감정을 표현해본 적 있어?',
            'description' => '감정 표현 여부',
            'placeholder' => '예: 그런 거 안 해요 / 이모지로 남겨요 / 일기처럼 적을 때도 있어요...',
            'pattern_hint' => 'emotion_avoidance'
        ],
        'emotion_expression_2' => [
            'sub_item' => 'emotion_expression',
            'question' => '공부하다 짜증나거나 지루할 때 그 감정을 어떻게 해?',
            'description' => '부정적 감정 처리 방식',
            'placeholder' => '예: 참고 계속해요 / 잠깐 쉬어요 / 기록해두고 왜 그런지 생각해봐요...',
            'pattern_hint' => 'negative_emotion_suppression'
        ],
        'emotion_expression_3' => [
            'sub_item' => 'emotion_expression',
            'question' => '기분이 공부 효율에 영향을 준다고 생각해?',
            'description' => '감정-학습 연결 인식',
            'placeholder' => '예: 별로 관계없는 것 같아요 / 기분 좋을 때 잘 돼요 / 신경 안 써요...',
            'pattern_hint' => 'emotion_learning_disconnect'
        ],

        // S8: 모범사례 구독 (best_practice_subscription)
        'best_practice_subscription_1' => [
            'sub_item' => 'best_practice_subscription',
            'question' => '공부 잘하는 친구들 방법을 참고해본 적 있어?',
            'description' => '모범사례에 대한 관심',
            'placeholder' => '예: 관심 없어요 / 가끔 참고해요 / 적극적으로 찾아봐요...',
            'pattern_hint' => 'best_practice_indifference'
        ],
        'best_practice_subscription_2' => [
            'sub_item' => 'best_practice_subscription',
            'question' => '좋은 방법을 배우면 그대로 따라해? 아니면 나한테 맞게 바꿔?',
            'description' => '모범사례 적용 방식',
            'placeholder' => '예: 그대로 따라해요 / 내 상황에 맞게 조절해요 / 잘 안 맞아서 포기해요...',
            'pattern_hint' => 'blind_imitation'
        ],
        'best_practice_subscription_3' => [
            'sub_item' => 'best_practice_subscription',
            'question' => '다른 친구들 방법을 보고 나랑 비교해서 기분 안 좋을 때 있어?',
            'description' => '비교로 인한 스트레스',
            'placeholder' => '예: 비교하면 위축돼요 / 자극받아요 / 신경 안 써요...',
            'pattern_hint' => 'comparison_stress'
        ],

        // S9: 메모장 활용 (memo_usage)
        'memo_usage_1' => [
            'sub_item' => 'memo_usage',
            'question' => '공부하다가 떠오르는 생각을 메모해둬?',
            'description' => '메모 기능 활용 여부',
            'placeholder' => '예: 안 해요 / 가끔 해요 / 항상 적어둬요...',
            'pattern_hint' => 'memo_non_usage'
        ],
        'memo_usage_2' => [
            'sub_item' => 'memo_usage',
            'question' => '메모를 많이 하는 편이야? 메모하느라 집중 못할 때도 있어?',
            'description' => '메모량의 적절성',
            'placeholder' => '예: 너무 많이 적으려다 시간 낭비해요 / 핵심만 간단히 해요 / 거의 안 해요...',
            'pattern_hint' => 'memo_overload'
        ],
        'memo_usage_3' => [
            'sub_item' => 'memo_usage',
            'question' => '메모한 것들을 나중에 정리하거나 다시 보는 편이야?',
            'description' => '메모 관리 및 활용',
            'placeholder' => '예: 그냥 쌓아두기만 해요 / 정기적으로 정리해요 / 다시 본 적 없어요...',
            'pattern_hint' => 'memo_unorganized'
        ]
    ];

    // 선생님용 관찰 기록 질문 (6개)
    public static $teacherQuestions = [
        'teacher_pomodoro_observation' => [
            'question' => '학생의 포모도로 시간 관리 패턴은 어떤가요?',
            'type' => 'textarea',
            'placeholder' => '타이머 준수, 휴식 활용, 시간 조절 등에 대한 관찰...'
        ],
        'teacher_scope_observation' => [
            'question' => '세션 목표 설정과 범위 결정 능력은 어떤가요?',
            'type' => 'textarea',
            'placeholder' => '목표의 구체성, 현실적 범위 설정, 우선순위 고려 등...'
        ],
        'teacher_recording_observation' => [
            'question' => '학습 기록 및 메모 습관은 어떤가요?',
            'type' => 'textarea',
            'placeholder' => '기록의 질, 효율성, 활용도 등에 대한 관찰...'
        ],
        'teacher_reflection_observation' => [
            'question' => '세션 후 성찰 활동은 어떻게 이루어지나요?',
            'type' => 'textarea',
            'placeholder' => '성찰의 깊이, 실질적 적용, 자기 인식 수준 등...'
        ],
        'teacher_emotion_observation' => [
            'question' => '학습 중 감정 표현과 관리는 어떤가요?',
            'type' => 'textarea',
            'placeholder' => '감정 인식, 표현 방식, 감정-학습 연결 인식 등...'
        ],
        'teacher_overall_assessment' => [
            'question' => '포모도로 학습법 적용에 대한 종합 평가',
            'type' => 'textarea',
            'placeholder' => '전반적인 포모도로 활용 수준과 개선이 필요한 부분...'
        ]
    ];

    // 시스템 데이터 필드
    public static $systemDataFields = [
        'session_count' => '총 세션 수',
        'avg_session_completion' => '평균 세션 완료율',
        'timer_compliance_rate' => '타이머 준수율',
        'rest_utilization_rate' => '휴식 활용률',
        'scope_achievement_rate' => '목표 달성률',
        'recording_frequency' => '기록 빈도',
        'reflection_depth_score' => '성찰 깊이 점수',
        'emotion_expression_count' => '감정 표현 횟수',
        'memo_count' => '메모 개수',
        'pattern_detected' => '탐지된 패턴',
        'last_updated' => '마지막 업데이트'
    ];

    /**
     * 서브 아이템별 질문 그룹화
     */
    public static function getQuestionsBySubItem() {
        $grouped = [];
        foreach (self::$studentQuestions as $key => $q) {
            $subItem = $q['sub_item'];
            if (!isset($grouped[$subItem])) {
                $grouped[$subItem] = [];
            }
            $grouped[$subItem][$key] = $q;
        }
        return $grouped;
    }

    /**
     * 서브 아이템 라벨 반환
     */
    public static function getSubItemLabels() {
        return [
            'pomodoro_efficacy' => '🍅 포모도로 효능감',
            'scope_setting' => '🎯 범위 설정',
            'input_method' => '✍️ 입력 방법',
            'return_check_prep' => '🏠 귀가검사 준비',
            'session_reflection' => '🔄 세션 성찰',
            'input_reflection' => '📝 입력 성찰',
            'emotion_expression' => '💭 감정 표현',
            'best_practice_subscription' => '⭐ 모범사례 참고',
            'memo_usage' => '📋 메모 활용'
        ];
    }
}

// AJAX 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $action = $_POST['action'];
        $userId = $USER->id;

        switch ($action) {
            case 'save_response':
                $questionKey = $_POST['question_key'] ?? '';
                $response = $_POST['response'] ?? '';
                $dataType = $_POST['data_type'] ?? 'student_pomodoro';

                if (empty($questionKey) || empty($response)) {
                    throw new Exception("필수 데이터가 누락되었습니다. [chat07.php:" . __LINE__ . "]");
                }

                // 기존 데이터 조회
                $existing = $DB->get_record('mdl_agent04_chat_data', [
                    'userid' => $userId,
                    'question_key' => $questionKey,
                    'data_type' => $dataType
                ]);

                $now = time();

                if ($existing) {
                    // 업데이트
                    $existing->response = $response;
                    $existing->updated_at = $now;
                    $DB->update_record('mdl_agent04_chat_data', $existing);
                    $recordId = $existing->id;
                } else {
                    // 신규 저장
                    $record = new stdClass();
                    $record->userid = $userId;
                    $record->question_key = $questionKey;
                    $record->response = $response;
                    $record->data_type = $dataType;
                    $record->created_at = $now;
                    $record->updated_at = $now;
                    $recordId = $DB->insert_record('mdl_agent04_chat_data', $record);
                }

                echo json_encode([
                    'success' => true,
                    'message' => '저장되었습니다.',
                    'record_id' => $recordId
                ]);
                break;

            case 'load_responses':
                $dataType = $_POST['data_type'] ?? 'student_pomodoro';

                $records = $DB->get_records('mdl_agent04_chat_data', [
                    'userid' => $userId,
                    'data_type' => $dataType
                ]);

                $responses = [];
                foreach ($records as $record) {
                    $responses[$record->question_key] = $record->response;
                }

                echo json_encode([
                    'success' => true,
                    'responses' => $responses
                ]);
                break;

            case 'save_teacher_input':
                if ($role === 'student') {
                    throw new Exception("권한이 없습니다. [chat07.php:" . __LINE__ . "]");
                }

                $targetUserId = $_POST['target_user_id'] ?? '';
                $questionKey = $_POST['question_key'] ?? '';
                $response = $_POST['response'] ?? '';

                if (empty($targetUserId) || empty($questionKey)) {
                    throw new Exception("필수 데이터가 누락되었습니다. [chat07.php:" . __LINE__ . "]");
                }

                $dataType = 'teacher_pomodoro';
                $compositeKey = $questionKey . '_' . $userId;

                $existing = $DB->get_record('mdl_agent04_chat_data', [
                    'userid' => $targetUserId,
                    'question_key' => $compositeKey,
                    'data_type' => $dataType
                ]);

                $now = time();

                if ($existing) {
                    $existing->response = $response;
                    $existing->updated_at = $now;
                    $DB->update_record('mdl_agent04_chat_data', $existing);
                } else {
                    $record = new stdClass();
                    $record->userid = $targetUserId;
                    $record->question_key = $compositeKey;
                    $record->response = $response;
                    $record->data_type = $dataType;
                    $record->created_at = $now;
                    $record->updated_at = $now;
                    $DB->insert_record('mdl_agent04_chat_data', $record);
                }

                echo json_encode([
                    'success' => true,
                    'message' => '선생님 입력이 저장되었습니다.'
                ]);
                break;

            case 'get_student_list':
                if ($role === 'student') {
                    throw new Exception("권한이 없습니다. [chat07.php:" . __LINE__ . "]");
                }

                // 학생 목록 조회 (간단한 버전)
                $students = $DB->get_records_sql("
                    SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                    FROM {user} u
                    JOIN {mdl_user_info_data} uid ON u.id = uid.userid
                    WHERE uid.fieldid = 22 AND uid.data = 'student'
                    ORDER BY u.lastname, u.firstname
                    LIMIT 100
                ");

                $studentList = [];
                foreach ($students as $s) {
                    $studentList[] = [
                        'id' => $s->id,
                        'name' => $s->lastname . $s->firstname,
                        'email' => $s->email
                    ];
                }

                echo json_encode([
                    'success' => true,
                    'students' => $studentList
                ]);
                break;

            default:
                throw new Exception("알 수 없는 액션입니다. [chat07.php:" . __LINE__ . "]");
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 질문 데이터 준비
$studentQuestions = PomodoroQuestions::$studentQuestions;
$teacherQuestions = PomodoroQuestions::$teacherQuestions;
$systemDataFields = PomodoroQuestions::$systemDataFields;
$groupedQuestions = PomodoroQuestions::getQuestionsBySubItem();
$subItemLabels = PomodoroQuestions::getSubItemLabels();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍅 포모도로 진단 - Agent04 Persona System</title>
    <style>
        :root {
            --primary: #ef4444;
            --primary-dark: #dc2626;
            --primary-light: #fca5a5;
            --secondary: #fef2f2;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
            --bg: #f9fafb;
            --white: #ffffff;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* 헤더 */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 16px;
            color: white;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        /* 탭 네비게이션 */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            background: var(--white);
            padding: 5px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-light);
            transition: all 0.2s;
        }

        .tab-btn:hover {
            background: var(--secondary);
            color: var(--primary);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* 탭 컨텐츠 */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* 채팅 영역 */
        .chat-container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .chat-messages {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }

        /* 아코디언 질문 */
        .question-group {
            margin-bottom: 15px;
        }

        .question-group-header {
            background: var(--secondary);
            padding: 15px 20px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--primary-dark);
            transition: all 0.2s;
        }

        .question-group-header:hover {
            background: var(--primary-light);
        }

        .question-group-header .arrow {
            transition: transform 0.3s;
        }

        .question-group.expanded .arrow {
            transform: rotate(180deg);
        }

        .question-group-content {
            display: none;
            padding: 15px;
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 12px 12px;
            margin-top: -10px;
        }

        .question-group.expanded .question-group-content {
            display: block;
        }

        /* 메시지 버블 */
        .message {
            margin-bottom: 20px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.bot {
            display: flex;
            gap: 12px;
        }

        .message.bot .avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .message.bot .bubble {
            background: var(--secondary);
            padding: 15px 20px;
            border-radius: 18px 18px 18px 4px;
            max-width: 80%;
        }

        .message.user {
            display: flex;
            justify-content: flex-end;
        }

        .message.user .bubble {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            border-radius: 18px 18px 4px 18px;
            max-width: 80%;
        }

        /* 입력 영역 */
        .input-area {
            margin-top: 15px;
        }

        .input-area textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--border);
            border-radius: 12px;
            resize: none;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .input-area textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .input-area .actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            gap: 10px;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* 진행 상태 */
        .progress-bar {
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-bar .fill {
            height: 100%;
            background: var(--primary);
            transition: width 0.3s;
        }

        .progress-text {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 10px;
        }

        /* 선생님 탭 */
        .teacher-section {
            padding: 20px;
        }

        .student-selector {
            margin-bottom: 20px;
        }

        .student-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
        }

        .teacher-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .teacher-form .form-group {
            background: var(--secondary);
            padding: 20px;
            border-radius: 12px;
        }

        .teacher-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-dark);
        }

        /* 시스템 탭 */
        .system-data {
            padding: 20px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .data-card {
            background: var(--secondary);
            padding: 15px;
            border-radius: 12px;
        }

        .data-card .label {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .data-card .value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        /* 저장 완료 표시 */
        .saved-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--success);
            font-size: 0.85rem;
        }

        /* 타이핑 효과 */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 10px;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: var(--primary-light);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-4px); }
        }

        /* 반응형 */
        @media (max-width: 640px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .tabs {
                flex-direction: column;
            }

            .message.bot .bubble,
            .message.user .bubble {
                max-width: 90%;
            }
        }

        /* 파일 전환 드랍업 메뉴 */
        .file-switcher {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }

        .file-switcher-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary, #4f46e5), var(--primary-dark, #3730a3));
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .file-switcher-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
        }

        .file-switcher-btn.active {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .file-switcher-menu {
            position: absolute;
            bottom: 70px;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            min-width: 180px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .file-switcher-menu.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .file-switcher-menu-header {
            padding: 12px 16px;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .file-switcher-menu-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            color: #4b5563;
            text-decoration: none;
            transition: background 0.2s;
            font-size: 14px;
        }

        .file-switcher-menu-item:hover {
            background: #f3f4f6;
        }

        .file-switcher-menu-item.current {
            background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(79,70,229,0.05));
            color: var(--primary, #4f46e5);
            font-weight: 600;
        }

        .file-switcher-menu-item .num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .file-switcher-menu-item.current .num {
            background: var(--primary, #4f46e5);
            color: white;
        }

        .file-switcher-menu-item:last-child {
            border-radius: 0 0 12px 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍅 포모도로 진단</h1>
            <p>포모도로 학습법 적용 시 인지관성 패턴을 파악합니다</p>
        </div>

        <!-- 탭 네비게이션 -->
        <div class="tabs">
            <button class="tab-btn <?php echo $current_tab === 'student' ? 'active' : ''; ?>"
                    onclick="switchTab('student')">
                💬 학생 대화
            </button>
            <?php if ($role !== 'student'): ?>
            <button class="tab-btn <?php echo $current_tab === 'teacher' ? 'active' : ''; ?>"
                    onclick="switchTab('teacher')">
                📋 선생님 입력
            </button>
            <?php else: ?>
            <button class="tab-btn" disabled title="선생님 전용">
                🔒 선생님 입력
            </button>
            <?php endif; ?>
            <button class="tab-btn <?php echo $current_tab === 'system' ? 'active' : ''; ?>"
                    onclick="switchTab('system')">
                📊 시스템 데이터
            </button>
        </div>

        <!-- 학생 대화 탭 -->
        <div id="tab-student" class="tab-content <?php echo $current_tab === 'student' ? 'active' : ''; ?>">
            <div class="progress-text">
                진행률: <span id="progress-percent">0</span>%
            </div>
            <div class="progress-bar">
                <div class="fill" id="progress-fill" style="width: 0%"></div>
            </div>

            <div class="chat-container">
                <div class="chat-messages" id="chat-messages">
                    <?php foreach ($groupedQuestions as $subItem => $questions): ?>
                    <div class="question-group" data-subitem="<?php echo $subItem; ?>">
                        <div class="question-group-header" onclick="toggleQuestionGroup(this)">
                            <span><?php echo $subItemLabels[$subItem]; ?></span>
                            <span class="arrow">▼</span>
                        </div>
                        <div class="question-group-content">
                            <?php foreach ($questions as $key => $q): ?>
                            <div class="question-item" data-key="<?php echo $key; ?>">
                                <div class="message bot">
                                    <div class="avatar">🍅</div>
                                    <div class="bubble">
                                        <strong><?php echo htmlspecialchars($q['question']); ?></strong>
                                        <p style="font-size: 0.85rem; color: var(--text-light); margin-top: 5px;">
                                            <?php echo htmlspecialchars($q['description']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="input-area">
                                    <textarea
                                        id="input-<?php echo $key; ?>"
                                        rows="3"
                                        placeholder="<?php echo htmlspecialchars($q['placeholder']); ?>"
                                        data-key="<?php echo $key; ?>"
                                        data-pattern="<?php echo $q['pattern_hint']; ?>"
                                    ></textarea>
                                    <div class="actions">
                                        <span class="saved-indicator" id="saved-<?php echo $key; ?>" style="display: none;">
                                            ✓ 저장됨
                                        </span>
                                        <button class="btn btn-primary" onclick="saveResponse('<?php echo $key; ?>')">
                                            저장
                                        </button>
                                    </div>
                                </div>
                                <div class="user-response" id="response-<?php echo $key; ?>" style="display: none;">
                                    <div class="message user">
                                        <div class="bubble" id="bubble-<?php echo $key; ?>"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 선생님 입력 탭 -->
        <?php if ($role !== 'student'): ?>
        <div id="tab-teacher" class="tab-content <?php echo $current_tab === 'teacher' ? 'active' : ''; ?>">
            <div class="chat-container">
                <div class="teacher-section">
                    <div class="student-selector">
                        <label>학생 선택:</label>
                        <select id="target-student" onchange="loadStudentData()">
                            <option value="">-- 학생을 선택하세요 --</option>
                        </select>
                    </div>

                    <div class="teacher-form" id="teacher-form">
                        <?php foreach ($teacherQuestions as $key => $q): ?>
                        <div class="form-group">
                            <label><?php echo htmlspecialchars($q['question']); ?></label>
                            <textarea
                                id="teacher-<?php echo $key; ?>"
                                rows="4"
                                placeholder="<?php echo htmlspecialchars($q['placeholder']); ?>"
                                data-key="<?php echo $key; ?>"
                            ></textarea>
                            <div class="actions" style="margin-top: 10px;">
                                <span class="saved-indicator" id="teacher-saved-<?php echo $key; ?>" style="display: none;">
                                    ✓ 저장됨
                                </span>
                                <button class="btn btn-primary" onclick="saveTeacherInput('<?php echo $key; ?>')">
                                    저장
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 시스템 데이터 탭 -->
        <div id="tab-system" class="tab-content <?php echo $current_tab === 'system' ? 'active' : ''; ?>">
            <div class="chat-container">
                <div class="system-data">
                    <h3 style="margin-bottom: 20px; color: var(--primary-dark);">📊 시스템 수집 데이터</h3>
                    <div class="data-grid">
                        <?php foreach ($systemDataFields as $key => $label): ?>
                        <div class="data-card">
                            <div class="label"><?php echo htmlspecialchars($label); ?></div>
                            <div class="value" id="system-<?php echo $key; ?>">-</div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 30px; padding: 20px; background: var(--secondary); border-radius: 12px;">
                        <h4 style="margin-bottom: 10px; color: var(--primary-dark);">💡 데이터 설명</h4>
                        <p style="font-size: 0.9rem; color: var(--text-light);">
                            이 데이터는 학생의 포모도로 학습 패턴을 분석하기 위해 자동으로 수집됩니다.
                            타이머 준수율, 세션 완료율, 성찰 활동 등의 지표를 통해
                            포모도로 학습법 적용 수준을 파악하고 개선점을 도출합니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 전역 변수
        let responses = {};
        let savedCount = 0;
        const totalQuestions = <?php echo count($studentQuestions); ?>;

        // 초기화
        document.addEventListener('DOMContentLoaded', function() {
            loadResponses();
            <?php if ($role !== 'student'): ?>
            loadStudentList();
            <?php endif; ?>
        });

        // 탭 전환
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');

            // URL 업데이트
            history.pushState(null, '', `?tab=${tabName}`);
        }

        // 질문 그룹 토글
        function toggleQuestionGroup(header) {
            const group = header.parentElement;
            group.classList.toggle('expanded');
        }

        // 응답 저장
        async function saveResponse(key) {
            const textarea = document.getElementById(`input-${key}`);
            const response = textarea.value.trim();

            if (!response) {
                alert('답변을 입력해주세요.');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'save_response');
                formData.append('question_key', key);
                formData.append('response', response);
                formData.append('data_type', 'student_pomodoro');

                const result = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(res => res.json());

                if (result.success) {
                    // 저장 표시
                    document.getElementById(`saved-${key}`).style.display = 'inline-flex';

                    // 사용자 응답 표시
                    const responseDiv = document.getElementById(`response-${key}`);
                    const bubble = document.getElementById(`bubble-${key}`);
                    bubble.textContent = response;
                    responseDiv.style.display = 'block';

                    // 진행률 업데이트
                    if (!responses[key]) {
                        savedCount++;
                        responses[key] = response;
                    }
                    updateProgress();

                    // 잠시 후 저장 표시 숨기기
                    setTimeout(() => {
                        document.getElementById(`saved-${key}`).style.display = 'none';
                    }, 2000);
                } else {
                    alert('저장 실패: ' + result.error);
                }
            } catch (error) {
                alert('저장 중 오류가 발생했습니다. [chat07.php]');
                console.error(error);
            }
        }

        // 저장된 응답 불러오기
        async function loadResponses() {
            try {
                const formData = new FormData();
                formData.append('action', 'load_responses');
                formData.append('data_type', 'student_pomodoro');

                const result = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(res => res.json());

                if (result.success) {
                    responses = result.responses;
                    savedCount = Object.keys(responses).length;

                    // UI에 반영
                    for (const [key, value] of Object.entries(responses)) {
                        const textarea = document.getElementById(`input-${key}`);
                        if (textarea) {
                            textarea.value = value;
                        }

                        const responseDiv = document.getElementById(`response-${key}`);
                        const bubble = document.getElementById(`bubble-${key}`);
                        if (responseDiv && bubble) {
                            bubble.textContent = value;
                            responseDiv.style.display = 'block';
                        }
                    }

                    updateProgress();
                }
            } catch (error) {
                console.error('응답 불러오기 실패:', error);
            }
        }

        // 진행률 업데이트
        function updateProgress() {
            const percent = Math.round((savedCount / totalQuestions) * 100);
            document.getElementById('progress-percent').textContent = percent;
            document.getElementById('progress-fill').style.width = percent + '%';
        }

        <?php if ($role !== 'student'): ?>
        // 학생 목록 불러오기
        async function loadStudentList() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_student_list');

                const result = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(res => res.json());

                if (result.success) {
                    const select = document.getElementById('target-student');
                    result.students.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.id;
                        option.textContent = `${student.name} (${student.email})`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('학생 목록 불러오기 실패:', error);
            }
        }

        // 학생 데이터 불러오기
        async function loadStudentData() {
            const studentId = document.getElementById('target-student').value;
            if (!studentId) return;

            // TODO: 선택한 학생의 기존 데이터 불러오기
            console.log('Loading data for student:', studentId);
        }

        // 선생님 입력 저장
        async function saveTeacherInput(key) {
            const studentId = document.getElementById('target-student').value;
            if (!studentId) {
                alert('학생을 먼저 선택해주세요.');
                return;
            }

            const textarea = document.getElementById(`teacher-${key}`);
            const response = textarea.value.trim();

            try {
                const formData = new FormData();
                formData.append('action', 'save_teacher_input');
                formData.append('target_user_id', studentId);
                formData.append('question_key', key);
                formData.append('response', response);

                const result = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(res => res.json());

                if (result.success) {
                    document.getElementById(`teacher-saved-${key}`).style.display = 'inline-flex';
                    setTimeout(() => {
                        document.getElementById(`teacher-saved-${key}`).style.display = 'none';
                    }, 2000);
                } else {
                    alert('저장 실패: ' + result.error);
                }
            } catch (error) {
                alert('저장 중 오류가 발생했습니다. [chat07.php]');
                console.error(error);
            }
        }
        <?php endif; ?>

        // 타이핑 효과 (선택적 사용)
        function typeText(element, text, speed = 30) {
            return new Promise(resolve => {
                let i = 0;
                element.textContent = '';
                const timer = setInterval(() => {
                    if (i < text.length) {
                        element.textContent += text.charAt(i);
                        i++;
                    } else {
                        clearInterval(timer);
                        resolve();
                    }
                }, speed);
            });
        }
    </script>
</body>
</html>
<?php
/**
 * DB 테이블 참조:
 * - mdl_agent04_chat_data
 *   - id: int(11) PRIMARY KEY AUTO_INCREMENT
 *   - userid: int(11) 사용자 ID
 *   - question_key: varchar(100) 질문 키
 *   - response: text 응답 내용
 *   - data_type: varchar(50) 데이터 유형 (student_pomodoro, teacher_pomodoro)
 *   - created_at: int(11) 생성 시간
 *   - updated_at: int(11) 수정 시간
 */
?>
