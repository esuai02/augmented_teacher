<?php
/**
 * Agent 01 - Onboarding
 * File: agents/agent01_onboarding/agent.php
 * 학생 프로필 및 학습 이력 로드
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 학생 ID 가져오기
$studentid = $_GET["userid"] ?? $USER->id;

try {
    // 학생 기본 정보
    $student = $DB->get_record('user', ['id' => $studentid], '*', MUST_EXIST);

    // MBTI 정보 (mdl_alt42_student_profiles 테이블에서)
    $profile = null;
    try {
        if ($DB->get_manager()->table_exists(new xmldb_table('alt42_student_profiles'))) {
            $profile = $DB->get_record('alt42_student_profiles', ['user_id' => $studentid]);
        }
    } catch (Exception $profileError) {
        error_log("Profile fetch error: " . $profileError->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }

    // MBTI from mdl_abessi_mbtilog (latest record)
    $mbtiType = 'INTJ'; // default
    try {
        if ($DB->get_manager()->table_exists(new xmldb_table('abessi_mbtilog'))) {
            $mbtiLog = $DB->get_record_sql(
                "SELECT * FROM {abessi_mbtilog}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT 1",
                [$studentid]
            );
            if ($mbtiLog && !empty($mbtiLog->mbti)) {
                $mbtiType = strtoupper($mbtiLog->mbti);
            }
        }
    } catch (Exception $mbtiError) {
        error_log("MBTI fetch error: " . $mbtiError->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }

    // 지식파일 로드
    $knowledgePath = __DIR__ . '/agent01_onboarding.md';
    $knowledgeText = file_exists($knowledgePath) ? file_get_contents($knowledgePath) : '';

    $response = [
        'success' => true,
        'data' => [
            'student_id' => $studentid,
            'student_name' => $student->firstname . ' ' . $student->lastname,
            'email' => $student->email,
            'mbti' => $mbtiType,
            'profile_complete' => $profile ? true : false,
            'last_login' => $student->lastaccess,
            'learning_history' => [
                'total_sessions' => 0,
                'completed_activities' => 0,
                'average_score' => 0
            ]
        ],
        'knowledge' => $knowledgeText,
        'message' => '학생 프로필이 로드되었습니다.'
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Error in agent.php line ' . __LINE__ . ': ' . $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ];
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
