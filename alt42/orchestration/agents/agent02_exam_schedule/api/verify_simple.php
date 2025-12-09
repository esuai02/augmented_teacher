<?php
/**
 * Agent02 간단 검증 API - 단계별 테스트
 *
 * @package AugmentedTeacher\Agent02\API
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// OPcache 클리어
if (function_exists('opcache_reset')) {
    opcache_reset();
}

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

$currentFile = __FILE__;
$basePath = dirname(dirname(__FILE__));
$step = isset($_GET['step']) ? intval($_GET['step']) : 0;
$results = ['step' => $step, 'file' => $currentFile];

try {
    switch ($step) {
        case 0:
            // 기본 정보
            $results['message'] = 'Agent02 시스템 검증 시작';
            $results['base_path'] = $basePath;
            $results['php_version'] = phpversion();
            $results['next'] = '?step=1';
            break;

        case 1:
            // 파일 존재 확인
            $files = [
                'persona_system/engine/Agent02DataContext.php',
                'persona_system/engine/Agent02PersonaRuleEngine.php',
                'persona_system/engine/RuleParser.php',
                'persona_system/rules.yaml',
                'persona_system/personas.md'
            ];
            foreach ($files as $f) {
                $results['files'][$f] = file_exists($basePath . '/' . $f);
            }
            $results['next'] = '?step=2';
            break;

        case 2:
            // DB 연결 테스트
            include_once("/home/moodle/public_html/moodle/config.php");
            global $DB, $CFG;
            $results['db_connected'] = true;
            $results['db_prefix'] = $CFG->prefix;
            $results['next'] = '?step=3';
            break;

        case 3:
            // 테이블 존재 확인
            include_once("/home/moodle/public_html/moodle/config.php");
            global $DB, $CFG;
            $tables = ['at_exam_schedules', 'augmented_teacher_sessions', 'augmented_teacher_personas'];
            foreach ($tables as $t) {
                try {
                    $results['tables'][$t] = $DB->get_manager()->table_exists($t);
                } catch (Exception $e) {
                    $results['tables'][$t] = 'error: ' . $e->getMessage();
                }
            }
            $results['next'] = '?step=4';
            break;

        case 4:
            // rules.yaml 파싱 테스트
            $rulesPath = $basePath . '/persona_system/rules.yaml';
            if (file_exists($rulesPath)) {
                $content = file_get_contents($rulesPath);
                $results['yaml_size'] = strlen($content);
                $results['yaml_lines'] = substr_count($content, "\n");

                // 간단한 YAML 키 추출
                preg_match_all('/^  ([a-zA-Z_]+):/m', $content, $matches);
                $results['yaml_sections'] = array_unique($matches[1] ?? []);
            } else {
                $results['yaml_error'] = 'File not found';
            }
            $results['next'] = '?step=5';
            break;

        case 5:
            // DataContext 로드 테스트
            require_once($basePath . '/persona_system/engine/Agent02DataContext.php');
            $ctx = new Agent02DataContext();
            $results['data_context_loaded'] = true;
            // determineSituationByDDay 메서드로 D-Day 상황 계산 테스트
            $results['test_dday_5'] = $ctx->determineSituationByDDay(5);    // D_BALANCED
            $results['test_dday_2'] = $ctx->determineSituationByDDay(2);    // D_URGENT
            $results['test_dday_15'] = $ctx->determineSituationByDDay(15);  // D_CONCEPT
            $results['test_dday_40'] = $ctx->determineSituationByDDay(40);  // D_FOUNDATION
            $results['next'] = '?step=6';
            break;

        case 6:
            // RuleParser 로드 테스트
            require_once($basePath . '/persona_system/engine/RuleParser.php');
            $parser = new RuleParser();
            $results['rule_parser_loaded'] = true;
            $results['parser_class'] = get_class($parser);
            $results['next'] = '?step=7';
            break;

        case 7:
            // RuleEngine 로드 테스트
            require_once($basePath . '/persona_system/engine/Agent02DataContext.php');
            require_once($basePath . '/persona_system/engine/Agent02PersonaRuleEngine.php');
            $engine = new Agent02PersonaRuleEngine([
                'rules_path' => $basePath . '/persona_system/rules.yaml',
                'cache_enabled' => false
            ]);
            $results['rule_engine_loaded'] = true;
            $results['next'] = '?step=8';
            break;

        case 8:
            // 페르소나 식별 테스트
            $enginePath = $basePath . '/persona_system/engine/Agent02PersonaRuleEngine.php';
            require_once($enginePath);

            // 디버그: 파일 수정 시간 확인
            $results['engine_file_mtime'] = date('Y-m-d H:i:s', filemtime($enginePath));
            $results['engine_file_size'] = filesize($enginePath);

            // 파일 내용에서 student_type 체크 로직 존재 여부 확인
            $engineContent = file_get_contents($enginePath);
            $results['has_student_type_check'] = strpos($engineContent, "context['student_type']") !== false;

            $engine = new Agent02PersonaRuleEngine([
                'cache_enabled' => false
            ]);

            // 테스트 컨텍스트 (배열)
            $testContext = [
                'user_id' => 1,
                'user_message' => '시험이 3일 남았는데 너무 불안해요',
                'd_day' => 3,
                'situation' => 'D_URGENT',
                'student_type' => 'P2'
            ];

            // Reflection으로 determineStudentType 직접 테스트
            $reflection = new ReflectionClass($engine);
            $method = $reflection->getMethod('determineStudentType');
            $method->setAccessible(true);
            $directStudentType = $method->invoke($engine, $testContext);
            $results['debug_determineStudentType'] = $directStudentType;

            $persona = $engine->identifyPersona($testContext);
            $results['test_input'] = [
                'd_day' => 3,
                'situation' => 'D_URGENT',
                'student_type' => 'P2',
                'message' => '시험이 3일 남았는데 너무 불안해요'
            ];
            $results['identified_persona'] = $persona;
            $results['expected_persona'] = 'D_URGENT_P2';
            $results['persona_match'] = ($persona['persona_id'] ?? '') === 'D_URGENT_P2';
            $results['all_tests_complete'] = true;
            break;

        case 9:
            // determineStudentType 메서드 직접 테스트 (Reflection 사용)
            require_once($basePath . '/persona_system/engine/Agent02PersonaRuleEngine.php');

            $engine = new Agent02PersonaRuleEngine(['cache_enabled' => false]);

            // Reflection으로 private 메서드 접근
            $reflection = new ReflectionClass($engine);
            $method = $reflection->getMethod('determineStudentType');
            $method->setAccessible(true);

            // 테스트 케이스들
            $testCases = [
                [
                    'name' => 'direct_student_type_P2',
                    'context' => ['student_type' => 'P2'],
                    'expected' => 'P2'
                ],
                [
                    'name' => 'direct_student_type_P1',
                    'context' => ['student_type' => 'P1'],
                    'expected' => 'P1'
                ],
                [
                    'name' => 'detected_type_high_confidence',
                    'context' => ['detected_student_type' => 'P3', 'type_confidence' => 0.8],
                    'expected' => 'P3'
                ],
                [
                    'name' => 'detected_type_low_confidence',
                    'context' => ['detected_student_type' => 'P3', 'type_confidence' => 0.3, 'student_type' => 'P2'],
                    'expected' => 'P2'  // 신뢰도 낮으면 direct student_type 사용
                ],
                [
                    'name' => 'no_type_default_P5',
                    'context' => [],
                    'expected' => 'P5'
                ],
                [
                    'name' => 'inferred_type',
                    'context' => ['inferred_student_type' => 'P4'],
                    'expected' => 'P4'
                ]
            ];

            $results['test_cases'] = [];
            foreach ($testCases as $tc) {
                $actual = $method->invoke($engine, $tc['context']);
                $results['test_cases'][$tc['name']] = [
                    'context' => $tc['context'],
                    'expected' => $tc['expected'],
                    'actual' => $actual,
                    'pass' => $actual === $tc['expected']
                ];
            }

            $results['all_passed'] = !in_array(false, array_column($results['test_cases'], 'pass'));
            break;

        default:
            $results['error'] = 'Unknown step';
    }

    $results['success'] = true;

} catch (Exception $e) {
    $results['success'] = false;
    $results['error'] = $e->getMessage();
    $results['error_file'] = $e->getFile();
    $results['error_line'] = $e->getLine();
    $results['trace'] = $e->getTraceAsString();
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
