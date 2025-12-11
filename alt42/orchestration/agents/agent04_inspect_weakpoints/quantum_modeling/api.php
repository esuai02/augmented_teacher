<?php
/**
 * Quantum Modeling API Endpoints
 * 양자 모델링 상태 조회 및 업데이트 API
 *
 * @package AugmentedTeacher\Agent04\QuantumModeling
 * @version 1.0.0
 * @since 2025-12-06
 */

header('Content-Type: application/json; charset=utf-8');

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 인증 확인 (API 호출에서는 선택적)
try {
    require_login();
} catch (Exception $e) {
    // 세션이 없는 경우 허용 (외부 API 호출용)
}

require_once(__DIR__ . '/QuantumPersonaEngine.php');

// 현재 파일 경로 (에러 출력용)
$currentFile = __FILE__;

// 요청 메서드 및 액션 확인
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 응답 헬퍼 함수
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function errorResponse($message, $code = 400, $file = '', $line = 0) {
    jsonResponse([
        'success' => false,
        'error' => $message,
        'error_location' => $file ? "{$file}:{$line}" : null,
        'timestamp' => date('Y-m-d H:i:s')
    ], $code);
}

try {
    // 사용자 ID 확인
    $userId = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : ($USER->id ?? 0);
    
    if ($userId <= 0 && !in_array($action, ['engine_info', 'calculate_path'])) {
        errorResponse('유효하지 않은 사용자 ID입니다.', 400, $currentFile, __LINE__);
    }
    
    // 양자 엔진 초기화
    $engine = new QuantumPersonaEngine($userId);
    
    switch ($action) {
        // ============================================================
        // 엔진 정보 조회
        // ============================================================
        case 'engine_info':
            jsonResponse([
                'success' => true,
                'data' => $engine->getEngineInfo(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 상태 벡터 초기화
        // ============================================================
        case 'initialize_state':
            $onboardingData = [
                'mbti' => $_REQUEST['mbti'] ?? '',
                'learning_style' => $_REQUEST['learning_style'] ?? ''
            ];
            
            $stateVector = $engine->initializeStateVector($onboardingData);
            $probabilities = $engine->calculateProbabilities($stateVector);
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'state_vector' => $stateVector,
                    'probabilities' => $probabilities,
                    'onboarding' => $onboardingData
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 전체 시뮬레이션 실행
        // ============================================================
        case 'simulate':
            $context = [
                'onboarding' => [
                    'mbti' => $_REQUEST['mbti'] ?? '',
                    'learning_style' => $_REQUEST['learning_style'] ?? ''
                ],
                'time_pressure' => floatval($_REQUEST['time_pressure'] ?? 0),
                'fatigue' => floatval($_REQUEST['fatigue'] ?? 0),
                'emotion' => floatval($_REQUEST['emotion'] ?? 0.5),
                'resilience' => floatval($_REQUEST['resilience'] ?? 0.5),
                'difficulty' => floatval($_REQUEST['difficulty'] ?? 0.5),
                'elapsed' => intval($_REQUEST['elapsed'] ?? 0)
            ];
            
            $result = $engine->runFullSimulation($userId, $context);
            
            jsonResponse([
                'success' => true,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 학습 역학 계산 (시너지/역효과)
        // ============================================================
        case 'calculate_dynamics':
            $resilience = floatval($_REQUEST['resilience'] ?? 0.5);
            $difficulty = floatval($_REQUEST['difficulty'] ?? 0.5);
            $elapsed = intval($_REQUEST['elapsed'] ?? 0);
            
            $dynamics = $engine->calculateLearningDynamics($resilience, $difficulty, $elapsed);
            
            jsonResponse([
                'success' => true,
                'data' => $dynamics,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 간섭 효과 계산
        // ============================================================
        case 'calculate_interference':
            $emotionScore = floatval($_REQUEST['emotion'] ?? 0.5);
            $fatigueScore = floatval($_REQUEST['fatigue'] ?? 0.5);
            
            $interference = $engine->applyInterference($emotionScore, $fatigueScore);
            
            jsonResponse([
                'success' => true,
                'data' => $interference,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 페르소나 스위칭 경로 계산
        // ============================================================
        case 'calculate_path':
            $current = strtoupper($_REQUEST['current'] ?? 'S');
            $target = strtoupper($_REQUEST['target'] ?? 'D');
            
            // 유효성 검사
            $validPersonas = ['S', 'D', 'G', 'A'];
            if (!in_array($current, $validPersonas) || !in_array($target, $validPersonas)) {
                errorResponse('유효하지 않은 페르소나입니다. (S, D, G, A 중 하나)', 400, $currentFile, __LINE__);
            }
            
            $path = $engine->getOptimalSwitchingPath($current, $target);
            
            jsonResponse([
                'success' => true,
                'data' => $path,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 환경 연산자 적용
        // ============================================================
        case 'apply_context':
            $stateVector = json_decode($_REQUEST['state_vector'] ?? '{}', true);
            
            if (empty($stateVector)) {
                // 초기 상태 생성
                $stateVector = $engine->initializeStateVector();
            }
            
            $timePressure = floatval($_REQUEST['time_pressure'] ?? 0);
            $fatigue = floatval($_REQUEST['fatigue'] ?? 0);
            $emotion = floatval($_REQUEST['emotion'] ?? 0);
            
            $newState = $engine->applyContextOperator($stateVector, $timePressure, $fatigue, $emotion);
            $measurement = $engine->measurePersona($newState);
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'original_state' => $stateVector,
                    'updated_state' => $newState,
                    'measurement' => $measurement,
                    'context_applied' => [
                        'time_pressure' => $timePressure,
                        'fatigue' => $fatigue,
                        'emotion' => $emotion
                    ]
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 페르소나 측정 (관측)
        // ============================================================
        case 'measure':
            $stateVector = json_decode($_REQUEST['state_vector'] ?? '{}', true);
            
            if (empty($stateVector)) {
                $stateVector = $engine->initializeStateVector();
            }
            
            $measurement = $engine->measurePersona($stateVector);
            
            jsonResponse([
                'success' => true,
                'data' => $measurement,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 필기 데이터 분석 (실시간)
        // ============================================================
        case 'analyze_stroke':
            $velocity = floatval($_REQUEST['velocity'] ?? 1);
            $pauseDuration = floatval($_REQUEST['pause_duration'] ?? 0);
            $jitterScore = floatval($_REQUEST['jitter'] ?? 0);
            $entropyScore = floatval($_REQUEST['entropy'] ?? 0);
            
            $currentState = json_decode($_REQUEST['state_vector'] ?? '{}', true);
            if (empty($currentState)) {
                $currentState = $engine->initializeStateVector();
            }
            
            $analysis = $engine->analyzeStrokeData(
                $velocity,
                $pauseDuration,
                $jitterScore,
                $entropyScore,
                $currentState
            );
            
            jsonResponse([
                'success' => true,
                'data' => $analysis,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 최근 양자 상태 조회
        // ============================================================
        case 'get_state':
            $state = $engine->getRecentQuantumState($userId);
            
            if ($state === null) {
                jsonResponse([
                    'success' => true,
                    'data' => null,
                    'message' => '저장된 양자 상태가 없습니다.',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                jsonResponse([
                    'success' => true,
                    'data' => $state,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
            break;
            
        // ============================================================
        // 양자 상태 히스토리 조회
        // ============================================================
        case 'get_history':
            $limit = intval($_REQUEST['limit'] ?? 20);
            $limit = min(max($limit, 1), 100); // 1~100 제한
            
            $history = $engine->getQuantumStateHistory($userId, $limit);
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'history' => $history,
                    'count' => count($history)
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 골든 타임 계산
        // ============================================================
        case 'calculate_golden_time':
            $resilience = floatval($_REQUEST['resilience'] ?? 0.5);
            $difficulty = floatval($_REQUEST['difficulty'] ?? 0.5);
            
            // 다양한 시간대에서 역학 계산
            $timeline = [];
            for ($t = 0; $t <= 120; $t += 5) {
                $dynamics = $engine->calculateLearningDynamics($resilience, $difficulty, $t);
                $timeline[] = [
                    'time' => $t,
                    'synergy' => $dynamics['synergy'],
                    'backfire' => $dynamics['backfire'],
                    'should_intervene' => $dynamics['should_intervene']
                ];
            }
            
            // 골든 타임 찾기
            $goldenTime = $engine->calculateLearningDynamics($resilience, $difficulty, 0)['golden_time'];
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'golden_time' => $goldenTime,
                    'recommended_intervention' => max($goldenTime - 5, 0),
                    'timeline' => $timeline,
                    'parameters' => [
                        'resilience' => $resilience,
                        'difficulty' => $difficulty
                    ]
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        // ============================================================
        // 기본: 알 수 없는 액션
        // ============================================================
        default:
            errorResponse('알 수 없는 액션입니다: ' . $action, 400, $currentFile, __LINE__);
    }
    
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500, $currentFile, $e->getLine());
}

/**
 * API 사용 예시:
 * 
 * 1. 엔진 정보 조회
 *    GET api.php?action=engine_info
 * 
 * 2. 상태 벡터 초기화
 *    GET api.php?action=initialize_state&mbti=INTJ&learning_style=visual
 * 
 * 3. 전체 시뮬레이션
 *    GET api.php?action=simulate&user_id=123&time_pressure=0.5&fatigue=0.3&emotion=0.7
 * 
 * 4. 학습 역학 계산
 *    GET api.php?action=calculate_dynamics&resilience=0.6&difficulty=0.5&elapsed=30
 * 
 * 5. 페르소나 스위칭 경로
 *    GET api.php?action=calculate_path&current=S&target=D
 * 
 * 6. 간섭 효과 계산
 *    GET api.php?action=calculate_interference&emotion=0.8&fatigue=0.2
 * 
 * 7. 최근 상태 조회
 *    GET api.php?action=get_state&user_id=123
 * 
 * 8. 히스토리 조회
 *    GET api.php?action=get_history&user_id=123&limit=10
 * 
 * 9. 골든 타임 계산
 *    GET api.php?action=calculate_golden_time&resilience=0.6&difficulty=0.7
 * 
 * 10. 필기 데이터 분석
 *     POST api.php?action=analyze_stroke
 *     Body: velocity=1.2&pause_duration=2.5&jitter=0.3&entropy=0.4
 */



