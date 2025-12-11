<?php
/**
 * Neuron Path Analyzer API
 * 유기적 뉴런 배양 시스템 - 사용자 풀이 경로 분석
 *
 * 사용자가 제출한 풀이 방법을 분석하여 새로운 학습 경로 노드를 생성합니다.
 *
 * @package AugmentedTeacher\TeachingSupport\API
 * @version 1.0.0
 * @since 2025-12-11
 *
 * URL: /moodle/local/augmented_teacher/alt42/teachingsupport/api/analyze_neuron_path.php
 *
 * POST Parameters:
 * - parentNodeId: string - 분기할 부모 노드 ID
 * - pathType: string - 경로 유형 (alternative, misconception, shortcut)
 * - title: string - 풀이 제목
 * - description: string - 풀이 설명
 * - questionId: string - 문제 ID
 * - existingNodes: array - 기존 노드 목록
 *
 * Response:
 * {
 *   success: boolean,
 *   isSimilar: boolean,
 *   similarNode: string (optional),
 *   node: {
 *     id, label, desc, concepts, learnerType, creator, creatorId
 *   }
 * }
 */

$currentFile = __FILE__;
$currentLine = __LINE__;

// [analyze_neuron_path.php:L34] Moodle 통합
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json; charset=UTF-8');

try {
    // [analyze_neuron_path.php:L42] 인증 확인
    if (!isloggedin() || isguestuser()) {
        throw new Exception("로그인이 필요합니다.", 401);
    }

    // [analyze_neuron_path.php:L47] 요청 데이터 파싱
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception("Invalid JSON input", 400);
    }

    $parentNodeId = $input['parentNodeId'] ?? '';
    $pathType = $input['pathType'] ?? 'alternative';
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $questionId = $input['questionId'] ?? '';
    $existingNodes = $input['existingNodes'] ?? [];

    // [analyze_neuron_path.php:L60] 유효성 검증
    if (empty($title) || strlen($title) < 3 || strlen($title) > 50) {
        throw new Exception("제목은 3~50자 사이로 입력해주세요.", 400);
    }

    if (empty($description) || strlen($description) < 10) {
        throw new Exception("설명을 10자 이상 입력해주세요.", 400);
    }

    // [analyze_neuron_path.php:L70] 유사도 분석 - 기존 노드와 비교
    $isSimilar = false;
    $similarNode = null;

    foreach ($existingNodes as $node) {
        $nodeLabel = $node['label'] ?? '';
        $nodeDesc = $node['desc'] ?? '';

        // 제목 유사도 체크 (Levenshtein 거리)
        $titleSimilarity = 1 - (levenshtein(mb_strtolower($title), mb_strtolower($nodeLabel)) / max(strlen($title), strlen($nodeLabel), 1));

        // 설명 유사도 체크 (간단한 키워드 매칭)
        $descWords = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', mb_strtolower($description))));
        $nodeWords = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', mb_strtolower($nodeDesc))));

        $commonWords = array_intersect($descWords, $nodeWords);
        $descSimilarity = count($commonWords) / max(count($descWords), 1);

        // 유사도가 70% 이상이면 유사하다고 판단
        if ($titleSimilarity > 0.7 || $descSimilarity > 0.5) {
            $isSimilar = true;
            $similarNode = $nodeLabel;
            break;
        }
    }

    // [analyze_neuron_path.php:L95] 개념 추출 (간단한 키워드 기반)
    $concepts = [];
    $conceptKeywords = [
        'inequality' => ['부등식', '크다', '작다', '이상', '이하', '>', '<'],
        'equation' => ['방정식', '등식', '='],
        'factorize' => ['인수분해', '인수', '분해'],
        'graph' => ['그래프', '좌표', 'x축', 'y축', '그림'],
        'formula' => ['공식', '정리', '법칙'],
        'calculate' => ['계산', '풀이', '대입'],
        'shortcut' => ['빠른', '간단', '꿀팁', '암기']
    ];

    $lowerDesc = mb_strtolower($description);
    foreach ($conceptKeywords as $concept => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_strpos($lowerDesc, mb_strtolower($keyword)) !== false) {
                $concepts[] = $concept;
                break;
            }
        }
    }

    // 최소 하나의 개념은 추가
    if (empty($concepts)) {
        $concepts[] = $pathType === 'shortcut' ? 'shortcut' : 'analyze';
    }

    // [analyze_neuron_path.php:L122] 학습자 유형 추론
    $learnerType = 'general';
    $visualKeywords = ['그래프', '그림', '시각', '보면', '그리면'];
    $analyticalKeywords = ['원리', '이유', '왜', '증명', '논리'];
    $proceduralKeywords = ['순서', '단계', '먼저', '그다음', '절차'];

    foreach ($visualKeywords as $kw) {
        if (mb_strpos($lowerDesc, $kw) !== false) {
            $learnerType = 'visual';
            break;
        }
    }
    if ($learnerType === 'general') {
        foreach ($analyticalKeywords as $kw) {
            if (mb_strpos($lowerDesc, $kw) !== false) {
                $learnerType = 'analytical';
                break;
            }
        }
    }
    if ($learnerType === 'general') {
        foreach ($proceduralKeywords as $kw) {
            if (mb_strpos($lowerDesc, $kw) !== false) {
                $learnerType = 'procedural';
                break;
            }
        }
    }

    // [analyze_neuron_path.php:L152] 노드 ID 생성
    $nodeId = 'user_' . uniqid() . '_' . $USER->id;

    // [analyze_neuron_path.php:L155] 사용자 정보
    $userName = '';
    try {
        $userRecord = $DB->get_record('user', ['id' => $USER->id], 'firstname, lastname, username');
        if ($userRecord) {
            $userName = trim($userRecord->firstname . ' ' . $userRecord->lastname);
            if (empty($userName)) {
                $userName = $userRecord->username;
            }
        }
    } catch (Exception $userError) {
        $userName = 'User' . $USER->id;
    }

    // [analyze_neuron_path.php:L168] 경로 유형에 따른 라벨 아이콘
    $typeIcons = [
        'alternative' => '💡',
        'misconception' => '⚠️',
        'shortcut' => '⚡'
    ];
    $typeIcon = $typeIcons[$pathType] ?? '💡';

    // [analyze_neuron_path.php:L177] DB에 저장 (선택적)
    try {
        // 테이블 존재 여부 확인 후 저장
        $tableExists = $DB->get_manager()->table_exists('at_neuron_paths');
        if ($tableExists) {
            $record = new stdClass();
            $record->node_id = $nodeId;
            $record->parent_node_id = $parentNodeId;
            $record->question_id = $questionId;
            $record->user_id = $USER->id;
            $record->title = $title;
            $record->description = $description;
            $record->path_type = $pathType;
            $record->concepts = json_encode($concepts);
            $record->learner_type = $learnerType;
            $record->status = 'pending'; // 검증 대기
            $record->verify_count = 0;
            $record->created_at = time();

            $DB->insert_record('at_neuron_paths', $record);
        }
    } catch (Exception $dbError) {
        // DB 저장 실패해도 응답은 반환 (테이블 미존재 가능)
        error_log("[analyze_neuron_path.php:L" . __LINE__ . "] DB 저장 오류: " . $dbError->getMessage());
    }

    // [analyze_neuron_path.php:L204] 성공 응답
    echo json_encode([
        'success' => true,
        'isSimilar' => $isSimilar,
        'similarNode' => $similarNode,
        'node' => [
            'id' => $nodeId,
            'label' => $typeIcon . ' ' . $title,
            'desc' => $description,
            'concepts' => array_unique($concepts),
            'learnerType' => $learnerType,
            'creator' => $userName,
            'creatorId' => $USER->id,
            'pathType' => $pathType,
            'status' => 'pending'
        ],
        'message' => $isSimilar ? '유사한 경로가 발견되었습니다.' : '새 경로가 생성되었습니다.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // [analyze_neuron_path.php:L223] 에러 응답
    $httpCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($httpCode);

    error_log("[analyze_neuron_path.php:L" . __LINE__ . "] 오류: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $currentFile,
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 관련 DB 테이블:
 *
 * mdl_at_neuron_paths - 사용자 생성 학습 경로 (신규 생성 필요)
 * - id (bigint) PRIMARY KEY
 * - node_id (varchar 100) - 노드 고유 ID
 * - parent_node_id (varchar 100) - 부모 노드 ID
 * - question_id (varchar 50) - 문제 ID
 * - user_id (bigint) - 생성자 ID
 * - title (varchar 100) - 경로 제목
 * - description (text) - 경로 설명
 * - path_type (varchar 20) - 경로 유형 (alternative, misconception, shortcut)
 * - concepts (text) - JSON: ["concept1", "concept2"]
 * - learner_type (varchar 20) - 학습자 유형 (visual, analytical, procedural, general)
 * - status (varchar 20) - 상태 (pending, verified, rejected)
 * - verify_count (int) - 검증 횟수
 * - created_at (int) - 생성 시간
 */
