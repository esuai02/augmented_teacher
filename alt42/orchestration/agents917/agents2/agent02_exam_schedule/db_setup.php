<?php
/**
 * Database Setup - Agent02 시험일정 테이블 생성
 *
 * at_exam_schedules 테이블을 생성합니다.
 * 실행: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/db_setup.php
 *
 * @package AugmentedTeacher\Agent02\ExamSchedule
 * @version 1.0
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

// 관리자 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = 22", [$USER->id]);
$role = $userrole ? $userrole->data : '';

if (!is_siteadmin() && $role !== 'admin' && $role !== 'teacher') {
    die(json_encode([
        'success' => false,
        'error' => '관리자/교사 권한이 필요합니다.',
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
        $results[] = [
            'status' => 'ERROR',
            'description' => $description,
            'message' => "[{$currentFile}:" . __LINE__ . "] " . $e->getMessage()
        ];
        return false;
    }
}

// ============================================
// 1. at_exam_schedules 테이블 생성
// ============================================

$tableName1 = 'at_exam_schedules';
if (!tableExists($tableName1)) {
    $sql1 = "CREATE TABLE {$CFG->prefix}{$tableName1} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID',
        exam_name VARCHAR(200) NOT NULL COMMENT '시험명 (예: 2학기 중간고사)',
        exam_subject VARCHAR(100) NULL COMMENT '과목명 (예: 수학, 영어)',
        exam_date DATE NOT NULL COMMENT '시험 날짜',
        exam_time TIME NULL COMMENT '시험 시작 시간',
        exam_type ENUM('midterm', 'final', 'quiz', 'mock', 'certification', 'other') DEFAULT 'other' COMMENT '시험 유형',
        importance TINYINT(1) DEFAULT 3 COMMENT '중요도 (1-5)',
        target_score INT(3) NULL COMMENT '목표 점수',
        current_readiness INT(3) DEFAULT 0 COMMENT '현재 준비도 (0-100%)',
        study_plan TEXT NULL COMMENT '학습 계획 (JSON)',
        reminder_settings TEXT NULL COMMENT '알림 설정 (JSON)',
        status ENUM('active', 'completed', 'cancelled', 'postponed') DEFAULT 'active' COMMENT '상태',
        actual_score INT(3) NULL COMMENT '실제 점수 (시험 후 기록)',
        reflection TEXT NULL COMMENT '시험 후 회고',
        metadata TEXT NULL COMMENT '추가 메타데이터 (JSON)',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',

        INDEX idx_user (user_id),
        INDEX idx_exam_date (exam_date),
        INDEX idx_status (status),
        INDEX idx_exam_type (exam_type),
        INDEX idx_user_date (user_id, exam_date),
        INDEX idx_user_status (user_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='학생 시험 일정 관리 테이블'";

    executeSQL($sql1, "테이블 생성: {$tableName1}");
} else {
    $results[] = [
        'status' => 'SKIP',
        'description' => "테이블 확인: {$tableName1}",
        'message' => '이미 존재함'
    ];
}

// ============================================
// 2. at_exam_study_logs 테이블 생성 (학습 진행 기록)
// ============================================

$tableName2 = 'at_exam_study_logs';
if (!tableExists($tableName2)) {
    $sql2 = "CREATE TABLE {$CFG->prefix}{$tableName2} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID',
        exam_id BIGINT(10) NOT NULL COMMENT '연관된 시험 ID (at_exam_schedules.id)',
        study_date DATE NOT NULL COMMENT '학습 날짜',
        study_duration_min INT(5) DEFAULT 0 COMMENT '학습 시간 (분)',
        study_content TEXT NULL COMMENT '학습 내용',
        study_type ENUM('concept', 'problem', 'review', 'mock_test', 'other') DEFAULT 'other' COMMENT '학습 유형',
        difficulty_level TINYINT(1) NULL COMMENT '체감 난이도 (1-5)',
        confidence_level TINYINT(1) NULL COMMENT '자신감 수준 (1-5)',
        notes TEXT NULL COMMENT '메모',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',

        INDEX idx_user (user_id),
        INDEX idx_exam (exam_id),
        INDEX idx_study_date (study_date),
        INDEX idx_user_exam (user_id, exam_id),
        FOREIGN KEY (exam_id) REFERENCES {$CFG->prefix}at_exam_schedules(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='시험 준비 학습 로그'";

    executeSQL($sql2, "테이블 생성: {$tableName2}");
} else {
    $results[] = [
        'status' => 'SKIP',
        'description' => "테이블 확인: {$tableName2}",
        'message' => '이미 존재함'
    ];
}

// ============================================
// 3. at_agent02_dday_snapshots 테이블 생성 (D-Day 스냅샷)
// ============================================

$tableName3 = 'at_agent02_dday_snapshots';
if (!tableExists($tableName3)) {
    $sql3 = "CREATE TABLE {$CFG->prefix}{$tableName3} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID',
        exam_id BIGINT(10) NOT NULL COMMENT '연관된 시험 ID',
        snapshot_date DATE NOT NULL COMMENT '스냅샷 날짜',
        dday INT(5) NOT NULL COMMENT 'D-Day 값 (음수=지남, 0=당일, 양수=남음)',
        situation VARCHAR(20) NOT NULL COMMENT '상황 코드 (D_URGENT, D_BALANCED, D_CONCEPT, D_FOUNDATION)',
        student_type VARCHAR(10) NULL COMMENT '학생 유형 (P1-P6)',
        persona_id VARCHAR(30) NULL COMMENT '적용된 페르소나 ID',
        readiness_score INT(3) NULL COMMENT '준비도 점수 (0-100)',
        stress_level TINYINT(1) NULL COMMENT '스트레스 수준 (1-5)',
        ai_recommendation TEXT NULL COMMENT 'AI 추천 메시지',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',

        INDEX idx_user (user_id),
        INDEX idx_exam (exam_id),
        INDEX idx_snapshot_date (snapshot_date),
        INDEX idx_situation (situation),
        INDEX idx_user_exam_date (user_id, exam_id, snapshot_date),
        FOREIGN KEY (exam_id) REFERENCES {$CFG->prefix}at_exam_schedules(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='D-Day 상황 스냅샷 (분석용)'";

    executeSQL($sql3, "테이블 생성: {$tableName3}");
} else {
    $results[] = [
        'status' => 'SKIP',
        'description' => "테이블 확인: {$tableName3}",
        'message' => '이미 존재함'
    ];
}

// ============================================
// 4. 공유 테이블 확인 (agent_id='agent02'로 사용)
// ============================================

$sharedTables = [
    'augmented_teacher_personas' => '페르소나 식별 이력',
    'augmented_teacher_sessions' => 'AI 세션 컨텍스트',
    'augmented_teacher_persona_transitions' => '페르소나 전환 이력',
    'augmented_teacher_ai_usage' => 'AI API 사용량 로그'
];

foreach ($sharedTables as $tableName => $description) {
    if (tableExists($tableName)) {
        $results[] = [
            'status' => 'OK',
            'description' => "공유 테이블 확인: {$tableName}",
            'message' => "존재함 (agent_id='agent02'로 사용)"
        ];
    } else {
        $results[] = [
            'status' => 'WARNING',
            'description' => "공유 테이블 누락: {$tableName}",
            'message' => "agent01의 persona_system/engine/db_setup.php를 먼저 실행하세요"
        ];
    }
}

// ============================================
// 5. 샘플 데이터 삽입 옵션
// ============================================

$insertSampleData = isset($_GET['sample']) && $_GET['sample'] === 'true';

if ($insertSampleData && tableExists('at_exam_schedules')) {
    // 기존 샘플 데이터 확인
    $existingSample = $DB->get_record_sql(
        "SELECT id FROM {$CFG->prefix}at_exam_schedules WHERE user_id = ? AND exam_name LIKE '%샘플%'",
        [$USER->id]
    );

    if (!$existingSample) {
        $sampleData = [
            'user_id' => $USER->id,
            'exam_name' => '샘플 - 2학기 중간고사',
            'exam_subject' => '수학',
            'exam_date' => date('Y-m-d', strtotime('+7 days')),
            'exam_type' => 'midterm',
            'importance' => 5,
            'target_score' => 90,
            'current_readiness' => 30,
            'status' => 'active',
            'study_plan' => json_encode([
                'daily_hours' => 2,
                'focus_areas' => ['미분', '적분', '확률'],
                'weak_points' => ['확률 문제']
            ]),
            'created_at' => time()
        ];

        try {
            $DB->insert_record('at_exam_schedules', (object)$sampleData, false);
            $results[] = [
                'status' => 'SUCCESS',
                'description' => '샘플 데이터 삽입',
                'message' => '샘플 시험 일정이 추가되었습니다.'
            ];
        } catch (Exception $e) {
            $results[] = [
                'status' => 'ERROR',
                'description' => '샘플 데이터 삽입',
                'message' => "[{$currentFile}:" . __LINE__ . "] " . $e->getMessage()
            ];
        }
    } else {
        $results[] = [
            'status' => 'SKIP',
            'description' => '샘플 데이터',
            'message' => '이미 샘플 데이터가 존재합니다.'
        ];
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
    <title>Agent02 시험일정 DB 설정</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #2196F3; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 25px; }
        .result { padding: 10px; margin: 10px 0; border-radius: 4px; }
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
        th { background: #2196F3; color: white; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .btn { display: inline-block; padding: 8px 16px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .btn:hover { background: #1976D2; }
        .btn.secondary { background: #757575; }
        .btn.secondary:hover { background: #616161; }
    </style>
</head>
<body>
<div class="container">
    <h1>Agent02 시험일정 DB 설정</h1>

    <p>
        <a href="?sample=true" class="btn">샘플 데이터 추가</a>
        <a href="db_setup.php" class="btn secondary">새로고침</a>
    </p>

    <h2>실행 결과</h2>
    <?php foreach ($results as $result): ?>
    <div class="result <?php echo $result['status']; ?>">
        <span class="status">[<?php echo $result['status']; ?>]</span>
        <span class="description"><?php echo $result['description']; ?></span>
        <div class="message"><?php echo $result['message']; ?></div>
    </div>
    <?php endforeach; ?>

    <div class="summary">
        <h3>Agent02 전용 테이블</h3>
        <table>
            <tr>
                <th>테이블명</th>
                <th>설명</th>
                <th>주요 필드</th>
            </tr>
            <tr>
                <td><code>at_exam_schedules</code></td>
                <td>시험 일정 관리</td>
                <td>exam_name, exam_date, exam_type, target_score, status</td>
            </tr>
            <tr>
                <td><code>at_exam_study_logs</code></td>
                <td>학습 진행 기록</td>
                <td>exam_id, study_date, study_duration_min, study_type</td>
            </tr>
            <tr>
                <td><code>at_agent02_dday_snapshots</code></td>
                <td>D-Day 상황 스냅샷</td>
                <td>dday, situation, student_type, persona_id, readiness_score</td>
            </tr>
        </table>
    </div>

    <div class="summary">
        <h3>공유 테이블 (agent_id='agent02'로 구분)</h3>
        <table>
            <tr>
                <th>테이블명</th>
                <th>용도</th>
                <th>Agent02 사용 방식</th>
            </tr>
            <tr>
                <td><code>augmented_teacher_personas</code></td>
                <td>페르소나 식별 이력</td>
                <td>agent_id='agent02', persona_id='D_URGENT_P1' 등</td>
            </tr>
            <tr>
                <td><code>augmented_teacher_sessions</code></td>
                <td>AI 세션 컨텍스트</td>
                <td>agent_id='agent02', context_data에 D-Day 정보 포함</td>
            </tr>
            <tr>
                <td><code>augmented_teacher_persona_transitions</code></td>
                <td>페르소나 전환 이력</td>
                <td>agent_id='agent02', D-Day 변화에 따른 전환 추적</td>
            </tr>
            <tr>
                <td><code>augmented_teacher_ai_usage</code></td>
                <td>AI API 사용량 로그</td>
                <td>agent_id='agent02', purpose='exam_strategy' 등</td>
            </tr>
        </table>
    </div>

    <div class="summary">
        <h3>D-Day 상황 코드</h3>
        <table>
            <tr>
                <th>코드</th>
                <th>D-Day 범위</th>
                <th>학습 모드</th>
                <th>학습 비율 (개념:문제)</th>
            </tr>
            <tr>
                <td><code>D_URGENT</code></td>
                <td>D-3 이하</td>
                <td>intensive (집중)</td>
                <td>20:80</td>
            </tr>
            <tr>
                <td><code>D_BALANCED</code></td>
                <td>D-4 ~ D-10</td>
                <td>balanced (균형)</td>
                <td>40:60</td>
            </tr>
            <tr>
                <td><code>D_CONCEPT</code></td>
                <td>D-11 ~ D-30</td>
                <td>concept_first (개념우선)</td>
                <td>70:30</td>
            </tr>
            <tr>
                <td><code>D_FOUNDATION</code></td>
                <td>D-31 이상</td>
                <td>foundation (기초)</td>
                <td>80:20</td>
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
 * 관련 DB 테이블 목록
 * ============================================
 *
 * [Agent02 전용 테이블]
 * - at_exam_schedules: 시험 일정 관리
 *   - id, user_id, exam_name, exam_subject, exam_date, exam_time
 *   - exam_type (midterm/final/quiz/mock/certification/other)
 *   - importance (1-5), target_score, current_readiness (0-100)
 *   - study_plan (JSON), status, actual_score, reflection
 *
 * - at_exam_study_logs: 학습 진행 기록
 *   - id, user_id, exam_id, study_date, study_duration_min
 *   - study_type (concept/problem/review/mock_test/other)
 *   - difficulty_level, confidence_level, notes
 *
 * - at_agent02_dday_snapshots: D-Day 상황 스냅샷
 *   - id, user_id, exam_id, snapshot_date
 *   - dday (정수), situation (D_URGENT/D_BALANCED/D_CONCEPT/D_FOUNDATION)
 *   - student_type (P1-P6), persona_id, readiness_score, stress_level
 *
 * [공유 테이블 - agent_id='agent02'로 구분]
 * - augmented_teacher_personas: 페르소나 식별 이력
 * - augmented_teacher_sessions: AI 세션 컨텍스트
 * - augmented_teacher_persona_transitions: 페르소나 전환 이력
 * - augmented_teacher_ai_usage: AI API 사용량 로그
 */
