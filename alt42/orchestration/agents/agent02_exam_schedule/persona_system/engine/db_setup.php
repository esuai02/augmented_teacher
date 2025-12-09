<?php
/**
 * DB Setup - Agent02 페르소나 시스템 공유 테이블 검증
 *
 * 공유 테이블 존재 확인 및 agent02용 뷰/인덱스 추가
 * 실행: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/persona_system/engine/db_setup.php
 *
 * @package AugmentedTeacher\Agent02\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

// 관리자 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = 22", [$USER->id]);
$role = $userrole ? $userrole->data : '';

if (!is_siteadmin() && $role !== 'admin') {
    die(json_encode([
        'success' => false,
        'error' => '관리자 권한이 필요합니다.',
        'file' => __FILE__,
        'line' => __LINE__
    ]));
}

$currentFile = __FILE__;
$results = [];

/**
 * 테이블 존재 여부 확인
 */
function tableExists($tableName) {
    global $DB;
    try {
        return $DB->get_manager()->table_exists($tableName);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * SQL 실행 및 결과 기록
 */
function executeSQL($sql, $description) {
    global $DB, $results, $currentFile;
    try {
        $DB->execute($sql);
        $results[] = [
            'status' => 'SUCCESS',
            'description' => $description,
            'message' => '성공적으로 실행됨'
        ];
        return true;
    } catch (Exception $e) {
        // 중복 인덱스/뷰 에러는 무시
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            $results[] = [
                'status' => 'SKIP',
                'description' => $description,
                'message' => '이미 존재함'
            ];
            return true;
        }
        $results[] = [
            'status' => 'ERROR',
            'description' => $description,
            'message' => "[{$currentFile}:" . __LINE__ . "] " . $e->getMessage()
        ];
        return false;
    }
}

// ============================================
// 1. 공유 테이블 존재 확인
// ============================================

$requiredTables = [
    'augmented_teacher_personas' => [
        'description' => '페르소나 식별 이력',
        'required_columns' => ['user_id', 'agent_id', 'persona_id', 'situation', 'confidence']
    ],
    'augmented_teacher_sessions' => [
        'description' => 'AI 세션 컨텍스트',
        'required_columns' => ['user_id', 'agent_id', 'session_key', 'current_situation', 'current_persona']
    ],
    'augmented_teacher_persona_transitions' => [
        'description' => '페르소나 전환 이력',
        'required_columns' => ['user_id', 'agent_id', 'from_persona', 'to_persona']
    ],
    'augmented_teacher_ai_usage' => [
        'description' => 'AI API 사용량 로그',
        'required_columns' => ['user_id', 'agent_id', 'model', 'total_tokens']
    ]
];

$allTablesExist = true;
foreach ($requiredTables as $tableName => $info) {
    if (tableExists($tableName)) {
        $results[] = [
            'status' => 'OK',
            'description' => "공유 테이블: {$tableName}",
            'message' => "{$info['description']} - 존재함"
        ];
    } else {
        $allTablesExist = false;
        $results[] = [
            'status' => 'ERROR',
            'description' => "공유 테이블 누락: {$tableName}",
            'message' => "agent01의 persona_system/engine/db_setup.php를 먼저 실행하세요"
        ];
    }
}

// ============================================
// 2. Agent02 전용 테이블 확인
// ============================================

$agent02Tables = [
    'at_exam_schedules' => '시험 일정 관리',
    'at_exam_study_logs' => '학습 진행 기록',
    'at_agent02_dday_snapshots' => 'D-Day 상황 스냅샷'
];

foreach ($agent02Tables as $tableName => $description) {
    if (tableExists($tableName)) {
        $results[] = [
            'status' => 'OK',
            'description' => "Agent02 테이블: {$tableName}",
            'message' => "{$description} - 존재함"
        ];
    } else {
        $results[] = [
            'status' => 'WARNING',
            'description' => "Agent02 테이블 누락: {$tableName}",
            'message' => "../../../db_setup.php를 먼저 실행하세요"
        ];
    }
}

// ============================================
// 3. Agent02용 뷰 생성 (선택적)
// ============================================

if ($allTablesExist) {
    // Agent02 활성 세션 뷰
    $viewName1 = 'v_agent02_active_sessions';
    $sql1 = "CREATE OR REPLACE VIEW {$CFG->prefix}{$viewName1} AS
        SELECT
            s.id,
            s.user_id,
            s.session_key,
            s.current_situation,
            s.current_persona,
            s.context_data,
            s.message_count,
            s.last_activity,
            u.username,
            u.firstname,
            u.lastname
        FROM {$CFG->prefix}augmented_teacher_sessions s
        LEFT JOIN {$CFG->prefix}user u ON s.user_id = u.id
        WHERE s.agent_id = 'agent02'
        AND s.last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    executeSQL($sql1, "뷰 생성: {$viewName1}");

    // Agent02 최근 페르소나 식별 뷰
    $viewName2 = 'v_agent02_recent_personas';
    $sql2 = "CREATE OR REPLACE VIEW {$CFG->prefix}{$viewName2} AS
        SELECT
            p.id,
            p.user_id,
            p.persona_id,
            p.situation,
            p.confidence,
            p.matched_rule,
            p.created_at,
            u.username,
            u.firstname
        FROM {$CFG->prefix}augmented_teacher_personas p
        LEFT JOIN {$CFG->prefix}user u ON p.user_id = u.id
        WHERE p.agent_id = 'agent02'
        ORDER BY p.created_at DESC";
    executeSQL($sql2, "뷰 생성: {$viewName2}");

    // Agent02 D-Day 요약 뷰 (시험 일정과 조인)
    if (tableExists('at_exam_schedules') && tableExists('at_agent02_dday_snapshots')) {
        $viewName3 = 'v_agent02_dday_summary';
        $sql3 = "CREATE OR REPLACE VIEW {$CFG->prefix}{$viewName3} AS
            SELECT
                e.id AS exam_id,
                e.user_id,
                e.exam_name,
                e.exam_subject,
                e.exam_date,
                e.exam_type,
                e.importance,
                e.target_score,
                e.current_readiness,
                e.status,
                DATEDIFF(e.exam_date, CURDATE()) AS dday,
                CASE
                    WHEN DATEDIFF(e.exam_date, CURDATE()) <= 3 THEN 'D_URGENT'
                    WHEN DATEDIFF(e.exam_date, CURDATE()) <= 10 THEN 'D_BALANCED'
                    WHEN DATEDIFF(e.exam_date, CURDATE()) <= 30 THEN 'D_CONCEPT'
                    ELSE 'D_FOUNDATION'
                END AS situation,
                CASE
                    WHEN DATEDIFF(e.exam_date, CURDATE()) <= 3 THEN 'critical'
                    WHEN DATEDIFF(e.exam_date, CURDATE()) <= 10 THEN 'high'
                    WHEN DATEDIFF(e.exam_date, CURDATE()) <= 30 THEN 'moderate'
                    ELSE 'low'
                END AS urgency_level,
                u.username,
                u.firstname,
                u.lastname
            FROM {$CFG->prefix}at_exam_schedules e
            LEFT JOIN {$CFG->prefix}user u ON e.user_id = u.id
            WHERE e.status = 'active'
            ORDER BY dday ASC";
        executeSQL($sql3, "뷰 생성: {$viewName3}");
    }
}

// ============================================
// 4. Agent02 데이터 통계 조회
// ============================================

$stats = [];

// 세션 통계
if (tableExists('augmented_teacher_sessions')) {
    try {
        $sessionStats = $DB->get_record_sql(
            "SELECT
                COUNT(*) as total_sessions,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(message_count) as total_messages
            FROM {$CFG->prefix}augmented_teacher_sessions
            WHERE agent_id = 'agent02'"
        );
        $stats['sessions'] = $sessionStats;
    } catch (Exception $e) {
        $stats['sessions'] = null;
    }
}

// 페르소나 통계
if (tableExists('augmented_teacher_personas')) {
    try {
        $personaStats = $DB->get_records_sql(
            "SELECT persona_id, COUNT(*) as count
            FROM {$CFG->prefix}augmented_teacher_personas
            WHERE agent_id = 'agent02'
            GROUP BY persona_id
            ORDER BY count DESC
            LIMIT 10"
        );
        $stats['personas'] = $personaStats;
    } catch (Exception $e) {
        $stats['personas'] = null;
    }
}

// 시험 일정 통계
if (tableExists('at_exam_schedules')) {
    try {
        $examStats = $DB->get_record_sql(
            "SELECT
                COUNT(*) as total_exams,
                COUNT(DISTINCT user_id) as students_with_exams,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_exams,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_exams
            FROM {$CFG->prefix}at_exam_schedules"
        );
        $stats['exams'] = $examStats;
    } catch (Exception $e) {
        $stats['exams'] = null;
    }
}

// ============================================
// 결과 출력
// ============================================

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Agent02 페르소나 시스템 DB 검증</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #9C27B0; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 25px; }
        .result { padding: 10px; margin: 8px 0; border-radius: 4px; }
        .result.SUCCESS { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .result.ERROR { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .result.SKIP { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .result.OK { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .result.WARNING { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status { font-weight: bold; }
        .description { font-size: 14px; }
        .message { font-size: 12px; color: #666; margin-top: 5px; }
        .summary { margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #9C27B0; color: white; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .stat-box { display: inline-block; background: #f8f9fa; padding: 15px; margin: 5px; border-radius: 8px; min-width: 150px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #9C27B0; }
        .stat-label { font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 8px 16px; background: #9C27B0; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .btn:hover { background: #7B1FA2; }
    </style>
</head>
<body>
<div class="container">
    <h1>Agent02 페르소나 시스템 DB 검증</h1>

    <p>
        <a href="../../../db_setup.php" class="btn">Agent02 테이블 설정</a>
        <a href="db_setup.php" class="btn">새로고침</a>
    </p>

    <h2>테이블 검증 결과</h2>
    <?php foreach ($results as $result): ?>
    <div class="result <?php echo $result['status']; ?>">
        <span class="status">[<?php echo $result['status']; ?>]</span>
        <span class="description"><?php echo $result['description']; ?></span>
        <div class="message"><?php echo $result['message']; ?></div>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($stats)): ?>
    <div class="summary">
        <h3>Agent02 데이터 통계</h3>

        <?php if (isset($stats['sessions']) && $stats['sessions']): ?>
        <h4>세션 통계</h4>
        <div class="stat-box">
            <div class="stat-value"><?php echo $stats['sessions']->total_sessions ?? 0; ?></div>
            <div class="stat-label">총 세션</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $stats['sessions']->unique_users ?? 0; ?></div>
            <div class="stat-label">고유 사용자</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $stats['sessions']->total_messages ?? 0; ?></div>
            <div class="stat-label">총 메시지</div>
        </div>
        <?php endif; ?>

        <?php if (isset($stats['exams']) && $stats['exams']): ?>
        <h4>시험 일정 통계</h4>
        <div class="stat-box">
            <div class="stat-value"><?php echo $stats['exams']->total_exams ?? 0; ?></div>
            <div class="stat-label">총 시험</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $stats['exams']->active_exams ?? 0; ?></div>
            <div class="stat-label">진행중</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $stats['exams']->completed_exams ?? 0; ?></div>
            <div class="stat-label">완료</div>
        </div>
        <?php endif; ?>

        <?php if (isset($stats['personas']) && $stats['personas']): ?>
        <h4>자주 감지된 페르소나 (Top 10)</h4>
        <table>
            <tr>
                <th>페르소나 ID</th>
                <th>감지 횟수</th>
            </tr>
            <?php foreach ($stats['personas'] as $persona): ?>
            <tr>
                <td><code><?php echo $persona->persona_id; ?></code></td>
                <td><?php echo $persona->count; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="summary">
        <h3>생성된 뷰</h3>
        <table>
            <tr>
                <th>뷰 이름</th>
                <th>설명</th>
                <th>용도</th>
            </tr>
            <tr>
                <td><code>v_agent02_active_sessions</code></td>
                <td>활성 세션 목록</td>
                <td>최근 24시간 내 활성 세션 조회</td>
            </tr>
            <tr>
                <td><code>v_agent02_recent_personas</code></td>
                <td>최근 페르소나 식별</td>
                <td>최근 페르소나 감지 이력 조회</td>
            </tr>
            <tr>
                <td><code>v_agent02_dday_summary</code></td>
                <td>D-Day 요약</td>
                <td>활성 시험의 D-Day 현황 조회</td>
            </tr>
        </table>
    </div>

    <div class="summary">
        <h3>Agent02 페르소나 ID 체계</h3>
        <table>
            <tr>
                <th>구분</th>
                <th>패턴</th>
                <th>예시</th>
            </tr>
            <tr>
                <td>기본 페르소나 (24개)</td>
                <td><code>{상황}_{학생유형}</code></td>
                <td>D_URGENT_P1, D_BALANCED_P2, D_CONCEPT_P3</td>
            </tr>
            <tr>
                <td>특수 페르소나 - Cold Start (3개)</td>
                <td><code>C_{학생유형}</code></td>
                <td>C_P1, C_P2, C_P3</td>
            </tr>
            <tr>
                <td>특수 페르소나 - Exam Complete (3개)</td>
                <td><code>E_{학생유형}</code></td>
                <td>E_P1, E_P2, E_P3</td>
            </tr>
            <tr>
                <td>특수 페르소나 - Question Mode (3개)</td>
                <td><code>Q_{학생유형}</code></td>
                <td>Q_P1, Q_P2, Q_P3</td>
            </tr>
        </table>
    </div>

    <p style="margin-top: 20px; color: #666; font-size: 12px;">
        실행 시간: <?php echo date('Y-m-d H:i:s'); ?><br>
        실행 사용자: <?php echo $USER->username; ?> (ID: <?php echo $USER->id; ?>)<br>
        파일 위치: <?php echo __FILE__; ?>
    </p>
</div>
</body>
</html>
<?php

/*
 * ============================================
 * Agent02 DB 구조 요약
 * ============================================
 *
 * [공유 테이블 - agent_id='agent02'로 사용]
 * - augmented_teacher_personas: 페르소나 식별 이력
 *   - situation: D_URGENT, D_BALANCED, D_CONCEPT, D_FOUNDATION, C, E, Q
 *   - persona_id: D_URGENT_P1 ~ D_FOUNDATION_P6, C_P1~P3, E_P1~P3, Q_P1~P3
 *
 * - augmented_teacher_sessions: AI 세션 컨텍스트
 *   - context_data에 D-Day, exam_id 등 Agent02 특화 정보 저장
 *
 * - augmented_teacher_persona_transitions: 페르소나 전환 이력
 *   - D-Day 변화에 따른 자동 전환 추적
 *   - trigger_type: 'dday_change', 'user_mood_shift', 'exam_complete' 등
 *
 * - augmented_teacher_ai_usage: AI API 사용량 로그
 *   - purpose: 'exam_strategy', 'study_plan', 'motivation' 등
 *
 * [Agent02 전용 테이블]
 * - at_exam_schedules: 시험 일정 관리
 * - at_exam_study_logs: 학습 진행 기록
 * - at_agent02_dday_snapshots: D-Day 상황 스냅샷
 *
 * [생성된 뷰]
 * - v_agent02_active_sessions: 24시간 내 활성 세션
 * - v_agent02_recent_personas: 최근 페르소나 식별 이력
 * - v_agent02_dday_summary: 활성 시험 D-Day 현황
 */
