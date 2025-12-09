<?php
/**
 * Quantum Modeling API 엔드포인트
 * 
 * Python 양자 모델링 시스템과의 통신을 담당합니다.
 * 
 * @package quantum_modeling
 * @version 0.1.0
 * @see IMPLEMENTATION_GUIDE.md
 */

// [quantum modeling/php/api/quantum_api.php:L12] Moodle 통합
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * Python Quantum API 호출
 * 
 * @param string $endpoint API 엔드포인트 (예: 'wavefunction/calculate')
 * @param array $data 요청 데이터
 * @return array|null 응답 데이터 또는 null (실패 시)
 */
function call_quantum_api($endpoint, $data) {
    $base_url = 'http://localhost:5000/api/';  // Python 서버 URL
    $url = $base_url . $endpoint;
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log("[quantum modeling/php/api/quantum_api.php:L" . __LINE__ . "] Python API 호출 실패: $url");
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * 파동함수 계산 요청
 * 
 * @param int $student_id 학생 ID
 * @param array $student_data 학생 데이터
 * @return array|null 13종 파동함수 계산 결과
 */
function calculate_wavefunctions($student_id, $student_data) {
    return call_quantum_api('wavefunction/calculate', [
        'student_id' => $student_id,
        'data' => $student_data
    ]);
}

/**
 * IDE 개입 판단 요청
 * 
 * @param int $student_id 학생 ID
 * @param int $trigger_agent 트리거 에이전트 (1~22)
 * @param array $student_state 학생 상태 (64차원)
 * @param array $wavefunctions 13종 파동함수 결과
 * @return array|null IDE 판단 결과
 */
function request_ide_decision($student_id, $trigger_agent, $student_state, $wavefunctions) {
    return call_quantum_api('ide/decide', [
        'student_id' => $student_id,
        'trigger_agent' => $trigger_agent,
        'student_state' => $student_state,
        'wavefunctions' => $wavefunctions
    ]);
}

/**
 * 학생 데이터 조회 (Moodle DB)
 * 
 * @param int $student_id 학생 ID
 * @return array 학생 학습 데이터
 */
function get_student_learning_data($student_id) {
    global $DB;
    
    // 침착도 데이터
    $calmness = $DB->get_records_sql(
        "SELECT * FROM mdl_calmness_data WHERE userid = ? ORDER BY timecreated DESC LIMIT 10",
        [$student_id]
    );
    
    // 퀴즈 결과
    $quiz_results = $DB->get_records_sql(
        "SELECT qa.* FROM mdl_quiz_attempts qa 
         WHERE qa.userid = ? 
         ORDER BY qa.timefinish DESC LIMIT 10",
        [$student_id]
    );
    
    // 데이터 정규화 및 반환
    return [
        'correct_rate' => calculate_correct_rate($quiz_results),
        'calmness_score' => calculate_calmness_score($calmness),
        // ... 추가 데이터
    ];
}

/**
 * 정답률 계산
 */
function calculate_correct_rate($quiz_results) {
    if (empty($quiz_results)) return 0.5;
    
    $total = 0;
    $correct = 0;
    foreach ($quiz_results as $result) {
        $total++;
        if ($result->sumgrades >= $result->maxgrade * 0.7) {
            $correct++;
        }
    }
    
    return $total > 0 ? $correct / $total : 0.5;
}

/**
 * 침착도 점수 계산
 */
function calculate_calmness_score($calmness_data) {
    if (empty($calmness_data)) return 0.5;
    
    $sum = 0;
    foreach ($calmness_data as $data) {
        $sum += $data->score;
    }
    
    return $sum / count($calmness_data);
}

