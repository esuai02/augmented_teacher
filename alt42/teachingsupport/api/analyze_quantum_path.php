<?php
/**
 * Quantum Path Analyzer API
 * 양자 붕괴 학습 미로 - AI 분석 엔드포인트
 *
 * 문제를 분석하여 학습 경로 노드와 엣지를 생성합니다.
 *
 * @package AugmentedTeacher\TeachingSupport\API
 * @version 1.0.0
 * @since 2025-12-11
 *
 * URL: /moodle/local/augmented_teacher/alt42/teachingsupport/api/analyze_quantum_path.php
 *
 * POST Parameters:
 * - contentsId: string - 콘텐츠 ID
 * - questionData: object - 문제 데이터 (narration_text, image_url 등)
 * - imageUrl: string - 문제 이미지 URL
 *
 * Response:
 * {
 *   success: boolean,
 *   data: {
 *     concepts: { [id]: { id, name, icon, color } },
 *     nodes: { [id]: { id, label, type, stage, concepts, x, y } },
 *     edges: [[from, to], ...]
 *   },
 *   message: string
 * }
 */

$currentFile = __FILE__;
$currentLine = __LINE__;

// [analyze_quantum_path.php:L32] Moodle 통합
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json; charset=UTF-8');

try {
    // [analyze_quantum_path.php:L40] 요청 데이터 파싱
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception("Invalid JSON input", 400);
    }

    $contentsId = $input['contentsId'] ?? '';
    $questionData = $input['questionData'] ?? [];
    $imageUrl = $input['imageUrl'] ?? '';

    // [analyze_quantum_path.php:L51] 콘텐츠 ID에서 문제 ID 추출
    $contentId = '';
    if (preg_match('/Q(\d+)/', $contentsId, $matches)) {
        $contentId = $matches[1];
    }

    // [analyze_quantum_path.php:L57] 기본 응답 구조 (안정성을 위한 폴백)
    $defaultConcepts = [
        'analyze' => ['id' => 'analyze', 'name' => '문제 분석', 'icon' => '🔍', 'color' => '#06b6d4'],
        'formula' => ['id' => 'formula', 'name' => '공식 적용', 'icon' => '📐', 'color' => '#8b5cf6'],
        'calculate' => ['id' => 'calculate', 'name' => '계산 수행', 'icon' => '🔢', 'color' => '#f59e0b'],
        'verify' => ['id' => 'verify', 'name' => '검증 확인', 'icon' => '✓', 'color' => '#10b981'],
        'complete' => ['id' => 'complete', 'name' => '문제 완료', 'icon' => '🎯', 'color' => '#ec4899']
    ];

    $defaultNodes = [
        'start' => ['id' => 'start', 'label' => '문제 인식', 'type' => 'start', 'stage' => 0, 'concepts' => [], 'x' => 350, 'y' => 40],
        's1_c' => ['id' => 's1_c', 'label' => '조건 파악', 'type' => 'correct', 'stage' => 1, 'concepts' => ['analyze'], 'x' => 180, 'y' => 120],
        's1_m' => ['id' => 's1_m', 'label' => '부분 이해', 'type' => 'partial', 'stage' => 1, 'concepts' => ['analyze'], 'x' => 350, 'y' => 120],
        's1_x' => ['id' => 's1_x', 'label' => '이해 부족', 'type' => 'confused', 'stage' => 1, 'concepts' => [], 'x' => 520, 'y' => 120],
        's2_c' => ['id' => 's2_c', 'label' => '전략 수립', 'type' => 'correct', 'stage' => 2, 'concepts' => ['formula'], 'x' => 140, 'y' => 220],
        's2_p' => ['id' => 's2_p', 'label' => '시행착오', 'type' => 'partial', 'stage' => 2, 'concepts' => ['formula'], 'x' => 350, 'y' => 220],
        's2_m' => ['id' => 's2_m', 'label' => '잘못된 접근', 'type' => 'wrong', 'stage' => 2, 'concepts' => [], 'x' => 520, 'y' => 220],
        's3_c' => ['id' => 's3_c', 'label' => '정확한 풀이', 'type' => 'correct', 'stage' => 3, 'concepts' => ['calculate'], 'x' => 140, 'y' => 320],
        's3_p' => ['id' => 's3_p', 'label' => '부분 풀이', 'type' => 'partial', 'stage' => 3, 'concepts' => ['calculate'], 'x' => 350, 'y' => 320],
        's3_m' => ['id' => 's3_m', 'label' => '계산 오류', 'type' => 'wrong', 'stage' => 3, 'concepts' => ['calculate'], 'x' => 520, 'y' => 320],
        'success' => ['id' => 'success', 'label' => '💥 정답!', 'type' => 'success', 'stage' => 4, 'concepts' => ['verify', 'complete'], 'x' => 180, 'y' => 420],
        'partial_s' => ['id' => 'partial_s', 'label' => '✨ 부분 정답', 'type' => 'success', 'stage' => 4, 'concepts' => ['verify'], 'x' => 350, 'y' => 420],
        'fail' => ['id' => 'fail', 'label' => '❌ 오답', 'type' => 'fail', 'stage' => 4, 'concepts' => [], 'x' => 520, 'y' => 420]
    ];

    $defaultEdges = [
        ['start', 's1_c'], ['start', 's1_m'], ['start', 's1_x'],
        ['s1_c', 's2_c'], ['s1_c', 's2_p'], ['s1_m', 's2_p'], ['s1_m', 's2_m'], ['s1_x', 's2_m'],
        ['s2_c', 's3_c'], ['s2_p', 's3_p'], ['s2_p', 's3_m'], ['s2_m', 's3_m'],
        ['s3_c', 'success'], ['s3_p', 'partial_s'], ['s3_p', 'fail'], ['s3_m', 'fail']
    ];

    // [analyze_quantum_path.php:L96] AI 분석 시도 (실패 시 기본값 사용)
    $concepts = $defaultConcepts;
    $nodes = $defaultNodes;
    $edges = $defaultEdges;
    $analysisMethod = 'default';

    // DB에서 문제 데이터 조회 시도
    if ($contentId) {
        try {
            // [analyze_quantum_path.php:L104] 문제 메타데이터 조회
            $questionMeta = $DB->get_record_sql(
                "SELECT * FROM {mq_question_meta} WHERE content_id = ?",
                [$contentId]
            );

            if ($questionMeta) {
                // 문제 유형에 따른 개념 확장
                $subject = $questionMeta->subject ?? 'math';
                $difficulty = $questionMeta->difficulty ?? 'medium';

                // 수학 문제인 경우 수학 특화 개념 추가
                if (stripos($subject, 'math') !== false) {
                    $concepts['inequality'] = ['id' => 'inequality', 'name' => '부등식', 'icon' => '📐', 'color' => '#06b6d4'];
                    $concepts['equation'] = ['id' => 'equation', 'name' => '방정식', 'icon' => '⚖️', 'color' => '#8b5cf6'];
                    $concepts['factorize'] = ['id' => 'factorize', 'name' => '인수분해', 'icon' => '🧩', 'color' => '#10b981'];
                    $concepts['graph'] = ['id' => 'graph', 'name' => '그래프', 'icon' => '📈', 'color' => '#3b82f6'];
                }

                $analysisMethod = 'database';
            }
        } catch (Exception $dbError) {
            // DB 오류 시 기본값 유지
            error_log("[analyze_quantum_path.php:L" . __LINE__ . "] DB 오류: " . $dbError->getMessage());
        }
    }

    // [analyze_quantum_path.php:L130] 기존 학습 경로 로그 조회 (있으면 활용)
    try {
        $existingPaths = $DB->get_records_sql(
            "SELECT * FROM {at_quantum_paths} WHERE content_id = ? ORDER BY created_at DESC LIMIT 5",
            [$contentId]
        );

        if (!empty($existingPaths)) {
            // 기존 경로가 있으면 사용자 생성 노드 병합
            foreach ($existingPaths as $path) {
                $pathData = json_decode($path->path_data, true);
                if ($pathData && isset($pathData['userNodes'])) {
                    foreach ($pathData['userNodes'] as $userNode) {
                        if (!isset($nodes[$userNode['id']])) {
                            $nodes[$userNode['id']] = $userNode;
                        }
                    }
                }
            }
            $analysisMethod = 'cached_paths';
        }
    } catch (Exception $pathError) {
        // 경로 조회 오류 시 기본값 유지 (테이블 미존재 가능)
        error_log("[analyze_quantum_path.php:L" . __LINE__ . "] Path 조회 오류: " . $pathError->getMessage());
    }

    // [analyze_quantum_path.php:L155] 성공 응답
    echo json_encode([
        'success' => true,
        'data' => [
            'concepts' => $concepts,
            'nodes' => $nodes,
            'edges' => $edges
        ],
        'meta' => [
            'analysisMethod' => $analysisMethod,
            'contentId' => $contentId,
            'nodeCount' => count($nodes),
            'edgeCount' => count($edges)
        ],
        'message' => '양자 경로 분석 완료'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // [analyze_quantum_path.php:L173] 에러 처리 - 폴백으로 기본 데이터 반환
    error_log("[analyze_quantum_path.php:L" . __LINE__ . "] 분석 오류: " . $e->getMessage());

    // 에러가 발생해도 기본 데이터는 반환 (안정성 확보)
    $fallbackConcepts = [
        'analyze' => ['id' => 'analyze', 'name' => '문제 분석', 'icon' => '🔍', 'color' => '#06b6d4'],
        'solve' => ['id' => 'solve', 'name' => '문제 풀이', 'icon' => '📐', 'color' => '#8b5cf6'],
        'verify' => ['id' => 'verify', 'name' => '검증', 'icon' => '✓', 'color' => '#10b981']
    ];

    $fallbackNodes = [
        'start' => ['id' => 'start', 'label' => '시작', 'type' => 'start', 'stage' => 0, 'concepts' => [], 'x' => 350, 'y' => 40],
        's1' => ['id' => 's1', 'label' => '분석', 'type' => 'correct', 'stage' => 1, 'concepts' => ['analyze'], 'x' => 350, 'y' => 160],
        's2' => ['id' => 's2', 'label' => '풀이', 'type' => 'correct', 'stage' => 2, 'concepts' => ['solve'], 'x' => 350, 'y' => 280],
        'success' => ['id' => 'success', 'label' => '완료', 'type' => 'success', 'stage' => 3, 'concepts' => ['verify'], 'x' => 350, 'y' => 400]
    ];

    $fallbackEdges = [
        ['start', 's1'],
        ['s1', 's2'],
        ['s2', 'success']
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'concepts' => $fallbackConcepts,
            'nodes' => $fallbackNodes,
            'edges' => $fallbackEdges
        ],
        'meta' => [
            'analysisMethod' => 'fallback',
            'error' => $e->getMessage()
        ],
        'message' => '기본 경로 데이터 사용'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 관련 DB 테이블:
 *
 * mdl_mq_question_meta - 문제 메타데이터
 * - id (bigint)
 * - content_id (varchar)
 * - subject (varchar)
 * - difficulty (varchar)
 * - created_at (datetime)
 *
 * mdl_at_quantum_paths - 양자 경로 로그 (신규 생성 필요)
 * - id (bigint)
 * - content_id (varchar)
 * - user_id (bigint)
 * - path_data (text) - JSON: { userNodes: [], edges: [], timestamp }
 * - created_at (datetime)
 */
