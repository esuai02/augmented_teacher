<?php
/**
 * 양자 붕괴 학습 미로 (Quantum Collapse Learning Maze)
 * 인지맵 시각화 - DB 기반 동적 렌더링
 *
 * React 없이 순수 PHP + Vanilla JS 구현
 * 모든 노드/엣지/개념 데이터를 DB에서 불러옴
 *
 * 파일: quantum_modeling.php
 * 위치: alt42/teachingsupport/AItutor/ui/
 */

// Moodle 통합 (learning_interface.php와 동일한 방식)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// URL 파라미터 (learning_interface.php와 동일)
$analysisId = $_GET['id'] ?? null;
$studentId = $_GET['studentid'] ?? $USER->id;

// 문제 정보 가져오기
$questionData = null;
$imageUrl = null;
$solutionImageUrl = null;
$questionImageUrl = null;
$contentId = null;
$contentsType = null;

// mdl_abessi_messages에서 contentsid, contentstype 가져오기 (learning_interface.php와 동일)
$thisboard = $DB->get_record_sql(
    "SELECT * FROM mdl_abessi_messages WHERE wboardid = ? ORDER BY tlaststroke DESC LIMIT 1", 
    [$analysisId]
);
$contentId = $thisboard->contentsid ?? null;
$contentsType = $thisboard->contentstype ?? null;

// 문제/해설 이미지 추출 (learning_interface.php와 동일)
if ($contentId) {
    $qtext0 = $DB->get_record_sql(
        "SELECT questiontext, generalfeedback FROM mdl_question WHERE id = ? ORDER BY id DESC LIMIT 1", 
        [$contentId]
    );
    
    if ($qtext0) {
        // 해설 이미지 추출
        $htmlDom1 = new DOMDocument;
        @$htmlDom1->loadHTML($qtext0->generalfeedback); 
        $imageTags1 = $htmlDom1->getElementsByTagName('img');
        foreach($imageTags1 as $imageTag1) {
            $imgSrc1 = $imageTag1->getAttribute('src'); 
            $imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
            if(strpos($imgSrc1, 'MATRIX/MATH') !== false && strpos($imgSrc1, 'hintimages') === false) {
                $solutionImageUrl = $imgSrc1;
                break;
            }
        }
        
        // 문제 이미지 추출
        $htmlDom2 = new DOMDocument;
        @$htmlDom2->loadHTML($qtext0->questiontext); 
        $imageTags2 = $htmlDom2->getElementsByTagName('img');
        foreach($imageTags2 as $imageTag2) {
            $imgSrc2 = $imageTag2->getAttribute('src'); 
            $imgSrc2 = str_replace(' ', '%20', $imgSrc2); 
            if(strpos($imgSrc2, 'hintimages') === false && (strpos($imgSrc2, '.png') !== false || strpos($imgSrc2, '.jpg') !== false)) {
                $questionImageUrl = $imgSrc2;
                break;
            }
        }
        
        $imageUrl = $questionImageUrl ?: $solutionImageUrl;
    }
}

// 기존 세션 확인 (자동 복원용)
$hasExistingSession = false;
$existingSessionId = null;
if (!empty($contentId)) {
    try {
        $lastSession = $DB->get_record_sql(
            "SELECT session_id FROM {at_quantum_user_sessions} 
             WHERE user_id = ? AND content_id = ? AND is_complete = 0 
             ORDER BY updated_at DESC LIMIT 1",
            [$studentId, $contentId]
        );
        
        if ($lastSession) {
            $hasExistingSession = true;
            $existingSessionId = $lastSession->session_id;
        }
    } catch (Exception $e) {
        error_log("[quantum_modeling.php:$analysisId] 세션 조회 오류: " . $e->getMessage());
    }
}

// ========================================
// DB에서 인지맵 데이터 조회
// ========================================

$dbNodes = [];
$dbEdges = [];
$dbConcepts = [];
$stageNames = ['시작']; // 기본값
$contentMeta = [
    'title' => '',
    'answer' => ''
];

// ★★★ 수정: 항상 기본 인지맵 데이터를 먼저 불러온 후, 사용자별 데이터 병합 ★★★
$baseContentId = 'default_equilateral';  // 기본 인지맵 데이터 (seed SQL에 저장된 ID)
$userContentId = $contentId;  // 사용자별 추가 데이터 (URL에서 받은 ID)

try {
    // 1. 기본 인지맵에서 컨텐츠 메타데이터 조회 (제목, 정답, 단계 이름)
    $contentRecord = $DB->get_record('at_quantum_contents', ['content_id' => $baseContentId, 'is_active' => 1]);
    if ($contentRecord) {
        $contentMeta['title'] = $contentRecord->title ?? '';
        $contentMeta['answer'] = $contentRecord->answer ?? '';
        $stageNames = json_decode($contentRecord->stage_names ?? '[]', true) ?: ['시작'];
    }
    
    // 2. 기본 인지맵에서 개념(Concepts) 조회
    $conceptsResult = $DB->get_records('at_quantum_concepts', ['content_id' => $baseContentId, 'is_active' => 1], 'order_index ASC');
    if ($conceptsResult) {
        foreach ($conceptsResult as $concept) {
            $dbConcepts[$concept->concept_id] = [
                'id' => $concept->concept_id,
                'name' => $concept->name,
                'icon' => $concept->icon ?? '📌',
                'color' => $concept->color ?? '#64748b'
            ];
        }
    }
    
    // 3. 기본 인지맵에서 노드(Nodes) 조회
    $nodesResult = $DB->get_records('at_quantum_nodes', ['content_id' => $baseContentId, 'is_active' => 1], 'stage ASC, order_index ASC');
    if ($nodesResult) {
        foreach ($nodesResult as $node) {
            $dbNodes[$node->node_id] = [
                'id' => $node->node_id,
                'x' => (int)$node->x,
                'y' => (int)$node->y,
                'label' => $node->label,
                'type' => $node->type,
                'stage' => (int)$node->stage,
                'desc' => $node->description ?? '',
                'concepts' => []
            ];
        }
    }
    
    // 4. 기본 인지맵에서 노드-개념 연결 조회
    $nodeConceptsResult = $DB->get_records('at_quantum_node_concepts', ['content_id' => $baseContentId], 'order_index ASC');
    if ($nodeConceptsResult) {
        foreach ($nodeConceptsResult as $nc) {
            if (isset($dbNodes[$nc->node_id])) {
                $dbNodes[$nc->node_id]['concepts'][] = $nc->concept_id;
            }
        }
    }
    
    // 5. 기본 인지맵에서 엣지(Edges) 조회
    $edgesResult = $DB->get_records('at_quantum_edges', ['content_id' => $baseContentId, 'is_active' => 1]);
    if ($edgesResult) {
        foreach ($edgesResult as $edge) {
            $dbEdges[] = [$edge->source_node_id, $edge->target_node_id];
        }
    }
    
    // DB에 기본 데이터가 없으면 로그 출력
    if (empty($dbNodes)) {
        error_log("[quantum_modeling.php:$analysisId] 경고: 기본 인지맵 데이터가 없습니다. seed_quantum_data.sql을 실행해주세요.");
    }
    
} catch (Exception $e) {
    error_log("[quantum_modeling.php:$analysisId] 기본 인지맵 조회 오류: " . $e->getMessage());
}

// 사용자별 추가 노드/엣지 병합 (AI가 추가한 것들)
if (!empty($userContentId)) {
    try {
        // 사용자 contentId로 추가된 노드 병합
        $additionalNodes = $DB->get_records('at_quantum_nodes', ['content_id' => $userContentId, 'is_active' => 1]);
        if ($additionalNodes) {
            foreach ($additionalNodes as $node) {
                if (!isset($dbNodes[$node->node_id])) {
                    $dbNodes[$node->node_id] = [
                        'id' => $node->node_id,
                        'x' => (int)$node->x,
                        'y' => (int)$node->y,
                        'label' => $node->label,
                        'type' => $node->type,
                        'stage' => (int)$node->stage,
                        'desc' => $node->description ?? '',
                        'concepts' => [],
                        'fromDb' => true  // AI/사용자가 추가한 노드 표시
                    ];
                }
            }
        }
        
        // 사용자 contentId로 추가된 엣지 병합
        $additionalEdges = $DB->get_records('at_quantum_edges', ['content_id' => $userContentId, 'is_active' => 1]);
        if ($additionalEdges) {
            foreach ($additionalEdges as $edge) {
                $edgePair = [$edge->source_node_id, $edge->target_node_id];
                if (!in_array($edgePair, $dbEdges)) {
                    $dbEdges[] = $edgePair;
                }
            }
        }
        
        // 사용자 contentId로 추가된 노드-개념 연결 병합
        $additionalNodeConcepts = $DB->get_records('at_quantum_node_concepts', ['content_id' => $userContentId]);
        if ($additionalNodeConcepts) {
            foreach ($additionalNodeConcepts as $nc) {
                if (isset($dbNodes[$nc->node_id]) && !in_array($nc->concept_id, $dbNodes[$nc->node_id]['concepts'])) {
                    $dbNodes[$nc->node_id]['concepts'][] = $nc->concept_id;
                }
            }
        }
    } catch (Exception $e) {
        error_log("[quantum_modeling.php:$analysisId] 사용자 데이터 병합 오류: " . $e->getMessage());
    }
}

// JSON으로 전달할 데이터
$initialData = json_encode([
    'analysisId' => $analysisId,
    'contentId' => $contentId,
    'mapContentId' => $baseContentId,
    'contentsType' => $contentsType,
    'questionData' => $questionData,
    'imageUrl' => $imageUrl,
    'questionImageUrl' => $questionImageUrl,
    'solutionImageUrl' => $solutionImageUrl,
    'userId' => $studentId,
    'userName' => $USER->firstname ?? 'Guest',
    'sessionId' => $existingSessionId,
    'hasExistingSession' => $hasExistingSession,
    // 인지맵 데이터 (DB에서 불러옴)
    'nodes' => $dbNodes,
    'edges' => $dbEdges,
    'concepts' => $dbConcepts,
    'stageNames' => $stageNames,
    'contentMeta' => $contentMeta
], JSON_UNESCAPED_UNICODE);

// 동적 타이틀/설명 생성
$pageTitle = !empty($contentMeta['title']) ? $contentMeta['title'] : '🔮 인지맵 - 양자 경로 분석';
$pageDesc = !empty($contentMeta['answer']) ? "정답: {$contentMeta['answer']} | 모든 가능한 풀이/오류 경로 시각화" : "모든 가능한 풀이/오류 경로 시각화";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            color: white;
        }

        /* 애니메이션 */
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.1; }
        }
        @keyframes rotate-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-pulse-glow { animation: pulse-glow 1.5s ease-in-out infinite; }
        .animate-rotate-slow { animation: rotate-slow 8s linear infinite; }

        /* 노드 스타일 */
        .quantum-node { cursor: grab; }
        .quantum-node:active { cursor: grabbing; }
        .quantum-node circle { transition: stroke-width 0.2s ease, filter 0.2s ease, opacity 0.2s ease; }
        .quantum-node:hover circle { stroke-width: 4; filter: url(#glow) brightness(1.15); }
        .quantum-node text { transition: fill 0.2s ease; pointer-events: none; user-select: none; }
        .quantum-node:hover text { fill: #fff; }

        /* 스크롤바 */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); }
        ::-webkit-scrollbar-thumb { background: rgba(139, 92, 246, 0.5); border-radius: 3px; }

        /* 슬라이더 스타일 */
        input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
        }
        input[type="range"]::-webkit-slider-track {
            height: 8px;
            background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
            border-radius: 4px;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
            border-radius: 50%;
            cursor: pointer;
            margin-top: -5px;
            box-shadow: 0 0 10px rgba(139, 92, 246, 0.5);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        input[type="range"]::-webkit-slider-thumb:hover {
            transform: scale(1.15);
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.8);
        }
        input[type="range"]::-moz-range-track {
            height: 8px;
            background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
            border-radius: 4px;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        input[type="range"]::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
            border-radius: 50%;
            cursor: pointer;
            border: none;
            box-shadow: 0 0 10px rgba(139, 92, 246, 0.5);
        }
    </style>
</head>
<body>
    <div id="quantum-app" class="min-h-screen p-4">
        <!-- 헤더 -->
        <header class="flex items-center justify-between mb-4">
            <div>
                <h1 id="page-title" class="text-xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                    <?php echo htmlspecialchars($pageTitle); ?>
                </h1>
                <p id="page-desc" class="text-slate-400 text-sm"><?php echo htmlspecialchars($pageDesc); ?></p>
            </div>
            <div class="flex gap-2">
                <!-- 인지맵 성장시키기 버튼 -->
                <div class="relative" id="growth-menu-container">
                    <button onclick="toggleGrowthMenu()" class="px-4 py-2 rounded-lg bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 hover:from-emerald-500/30 hover:to-cyan-500/30 text-sm font-medium transition border border-emerald-500/30 flex items-center gap-2">
                        <span>🌱</span>
                        <span>인지맵 성장시키기</span>
                        <svg class="w-4 h-4 transition-transform" id="growth-menu-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <!-- 드롭다운 메뉴 -->
                    <div id="growth-menu" class="absolute right-0 top-full mt-2 w-72 bg-slate-800/95 backdrop-blur rounded-xl border border-white/10 shadow-2xl hidden z-50">
                        <div class="p-2">
                            <button onclick="openGrowthModal('new_solution')" class="w-full text-left px-4 py-3 rounded-lg hover:bg-white/10 transition group">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">✨</span>
                                    <div>
                                        <div class="font-medium text-white group-hover:text-emerald-400">새로운 풀이 탐색</div>
                                        <div class="text-xs text-slate-400">기존과 다른 정답 경로 제안</div>
                                    </div>
                                </div>
                            </button>
                            <button onclick="openGrowthModal('misconception')" class="w-full text-left px-4 py-3 rounded-lg hover:bg-white/10 transition group">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">🔍</span>
                                    <div>
                                        <div class="font-medium text-white group-hover:text-amber-400">오개념 풀이 탐색</div>
                                        <div class="text-xs text-slate-400">학생들의 흔한 실수 경로 제안</div>
                                    </div>
                                </div>
                            </button>
                            <button onclick="openGrowthModal('custom_input')" class="w-full text-left px-4 py-3 rounded-lg hover:bg-white/10 transition group">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">📝</span>
                                    <div>
                                        <div class="font-medium text-white group-hover:text-purple-400">풀이 입력하여 제안</div>
                                        <div class="text-xs text-slate-400">직접 입력한 풀이를 분석</div>
                                    </div>
                                </div>
                            </button>
                        </div>
                        <div class="border-t border-white/10 p-2">
                            <button onclick="openVersionHistory()" class="w-full text-left px-4 py-2 rounded-lg hover:bg-white/10 transition text-sm text-slate-400 hover:text-white flex items-center gap-2">
                                <span>📜</span>
                                <span>버전 히스토리</span>
                            </button>
                        </div>
                    </div>
                </div>
                <button onclick="resetMaze()" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-sm font-medium transition">
                    🔄 초기화
                </button>
            </div>
        </header>

        <!-- 단계 진행 표시 -->
        <div id="stage-progress" class="flex gap-2 mb-4 overflow-x-auto pb-2">
            <!-- JS에서 동적 생성 -->
        </div>

        <!-- 메인 레이아웃 -->
        <div class="flex gap-4">
            <!-- 왼쪽: 개념 패널 + 노드 상세 -->
            <aside class="w-64 flex-shrink-0 space-y-3">
                <!-- 개념 붕괴 현황 -->
                <div class="bg-slate-900/90 backdrop-blur rounded-xl border border-white/10 p-4">
                    <h3 class="text-base font-bold text-white mb-3">🧠 개념 붕괴 현황</h3>
                    <div id="concept-list" class="space-y-2">
                        <!-- JS에서 동적 생성 -->
                    </div>
                    <div class="mt-3 pt-3 border-t border-white/10 text-sm text-slate-400">
                        진행도: <span id="activated-count" class="text-white font-bold">0</span>/<span id="total-concepts">0</span>
                        <div class="mt-2 h-2 bg-slate-700 rounded-full overflow-hidden">
                            <div id="concept-progress" class="h-full bg-gradient-to-r from-cyan-500 to-purple-500 transition-all" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- 노드 상세 정보 -->
                <div id="node-detail" class="bg-slate-800/80 rounded-xl p-4 border-l-4 border-cyan-500 hidden">
                    <div class="flex items-center gap-2 mb-2">
                        <span id="detail-label" class="font-bold text-white text-lg"></span>
                        <span id="detail-type" class="text-xs px-2 py-1 rounded bg-emerald-500/20 text-emerald-400"></span>
                    </div>
                    <p id="detail-desc" class="text-sm text-slate-300 leading-relaxed"></p>
                    <div id="detail-concepts" class="flex gap-2 mt-3 flex-wrap">
                        <!-- JS에서 동적 생성 -->
                    </div>
                </div>

                <!-- 맵 크기 조절 슬라이더 -->
                <div class="bg-slate-900/90 backdrop-blur rounded-xl border border-white/10 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-slate-400">🗺️ 맵 크기</span>
                        <span id="map-scale-value" class="text-sm text-white font-medium">100%</span>
                    </div>
                    <input type="range" id="map-scale-slider"
                        min="50" max="200" value="100" step="10"
                        class="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-purple-500"
                        oninput="updateMapScale(this.value)">
                    <div class="flex justify-between text-xs text-slate-500 mt-1">
                        <span>50%</span>
                        <span>100%</span>
                        <span>200%</span>
                    </div>
                </div>
            </aside>

            <!-- 중앙: 미로 (스크롤 가능) -->
            <main class="flex-1 bg-slate-900/50 backdrop-blur rounded-xl border border-white/10 overflow-auto relative" style="max-height: 75vh;">
                <!-- 고정 단계 라벨 (맵 크기 변경에 영향받지 않음) -->
                <div id="stage-labels-fixed" class="absolute left-2 top-0 z-10 pointer-events-none" style="width: 80px;">
                    <!-- JS에서 동적 생성 -->
                </div>
                <svg id="maze-svg" viewBox="0 0 1000 1150" class="w-full min-w-[800px]">
                    <defs>
                        <filter id="glow">
                            <feGaussianBlur stdDeviation="3"/>
                            <feMerge><feMergeNode/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                        <linearGradient id="pathG" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#06b6d4"/>
                            <stop offset="100%" stop-color="#8b5cf6"/>
                        </linearGradient>
                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(148,163,184,0.08)" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)"/>
                    <g id="edges-layer"></g>
                    <g id="nodes-layer"></g>
                    <g id="stage-labels"></g>
                </svg>
            </main>

            <!-- 오른쪽: 상태 & 선택지 -->
            <aside class="w-64 flex-shrink-0 space-y-4">
                <!-- 양자 상태 벡터 -->
                <div class="bg-slate-900/80 backdrop-blur rounded-xl border border-white/10 p-4">
                    <div class="text-sm text-slate-400 mb-3">양자 상태 |ψ⟩</div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="w-16 text-sm text-slate-400">α 정답</span>
                            <div class="flex-1 h-3 bg-slate-700 rounded-full overflow-hidden">
                                <div id="alpha-bar" class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-500" style="width: 33%"></div>
                            </div>
                            <span id="alpha-value" class="w-10 text-sm text-right text-slate-400">33%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-16 text-sm text-slate-400">β 오개념</span>
                            <div class="flex-1 h-3 bg-slate-700 rounded-full overflow-hidden">
                                <div id="beta-bar" class="h-full bg-gradient-to-r from-rose-500 to-rose-400 transition-all duration-500" style="width: 33%"></div>
                            </div>
                            <span id="beta-value" class="w-10 text-sm text-right text-slate-400">33%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-16 text-sm text-slate-400">γ 혼란</span>
                            <div class="flex-1 h-3 bg-slate-700 rounded-full overflow-hidden">
                                <div id="gamma-bar" class="h-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-500" style="width: 34%"></div>
                            </div>
                            <span id="gamma-value" class="w-10 text-sm text-right text-slate-400">34%</span>
                        </div>
                    </div>
                </div>

                <!-- 선택지 -->
                <div id="choices-panel" class="bg-slate-900/80 backdrop-blur rounded-xl border border-white/10 p-4">
                    <p class="text-sm text-slate-400 mb-3">다음 단계 (<span id="avail-count">0</span>개)</p>
                    <div id="choices-container" class="space-y-2 max-h-64 overflow-y-auto">
                        <!-- JS에서 동적 생성 -->
                    </div>
                </div>

                <!-- 완료 패널 -->
                <div id="complete-panel" class="bg-slate-900/80 backdrop-blur rounded-xl border border-white/10 p-4 hidden">
                    <div class="text-center py-4">
                        <div id="complete-icon" class="text-4xl mb-2">🎉</div>
                        <h3 id="complete-title" class="text-lg font-bold text-emerald-400">정답!</h3>
                        <p id="complete-label" class="text-sm text-slate-400 mb-3"></p>
                        <div class="flex gap-2 justify-center">
                            <button onclick="backtrackOne()" class="px-3 py-2 bg-emerald-500/20 text-emerald-400 rounded-lg text-sm font-medium">↩ 복귀</button>
                            <button onclick="resetMaze()" class="px-3 py-2 bg-purple-500/20 text-purple-400 rounded-lg text-sm font-medium">🔄 처음</button>
                        </div>
                    </div>
                </div>

                <!-- 경로 히스토리 -->
                <div class="bg-slate-900/80 backdrop-blur rounded-xl border border-white/10 p-4">
                    <p class="text-sm text-slate-400 mb-2">경로 (<span id="path-count">0</span>단계)</p>
                    <div id="path-history" class="flex flex-wrap gap-1.5">
                        <!-- JS에서 동적 생성 -->
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- AI 제안 생성 모달 -->
    <div id="growth-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800 rounded-2xl border border-white/10 shadow-2xl w-full max-w-lg">
            <!-- 모달 헤더 -->
            <div class="flex items-center justify-between p-4 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <span id="growth-modal-icon" class="text-2xl">✨</span>
                    <h3 id="growth-modal-title" class="text-lg font-bold text-white">새로운 풀이 탐색</h3>
                </div>
                <button onclick="closeGrowthModal()" class="p-2 hover:bg-white/10 rounded-lg transition">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- 모달 바디 -->
            <div class="p-4">
                <p id="growth-modal-desc" class="text-slate-400 text-sm mb-4">
                    AI가 기존 인지맵을 분석하여 새로운 정답 풀이 경로를 제안합니다.
                </p>
                
                <!-- 풀이 입력 영역 (custom_input인 경우만 표시) -->
                <div id="custom-input-area" class="hidden mb-4">
                    <label class="block text-sm font-medium text-white mb-2">풀이 입력</label>
                    <textarea id="custom-solution-input" 
                        class="w-full h-32 bg-slate-900/50 border border-white/10 rounded-xl p-3 text-white placeholder-slate-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none resize-none"
                        placeholder="예: 1단계: x(x-a)=0으로 인수분해하여 x=0, x=a를 구한다.&#10;2단계: 꼭짓점 공식을 사용하여 C(a/2, -a²/4)를 구한다.&#10;3단계: ..."></textarea>
                </div>
                
                <!-- 로딩 상태 -->
                <div id="growth-loading" class="hidden">
                    <div class="flex flex-col items-center justify-center py-8">
                        <div class="relative w-16 h-16">
                            <div class="absolute inset-0 border-4 border-purple-500/20 rounded-full"></div>
                            <div class="absolute inset-0 border-4 border-transparent border-t-purple-500 rounded-full animate-spin"></div>
                        </div>
                        <p class="text-slate-400 mt-4">AI가 새로운 경로를 탐색하고 있습니다...</p>
                        <p class="text-slate-500 text-sm mt-1">약 5-10초 소요됩니다</p>
                    </div>
                </div>
                
                <!-- 에러 메시지 -->
                <div id="growth-error" class="hidden bg-rose-500/10 border border-rose-500/30 rounded-xl p-4 mb-4">
                    <div class="flex items-start gap-3">
                        <span class="text-rose-500">⚠️</span>
                        <div>
                            <p class="text-rose-400 font-medium">오류가 발생했습니다</p>
                            <p id="growth-error-message" class="text-rose-300/80 text-sm mt-1"></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 모달 푸터 -->
            <div id="growth-modal-footer" class="flex justify-end gap-2 p-4 border-t border-white/10">
                <button onclick="closeGrowthModal()" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-sm font-medium transition">
                    취소
                </button>
                <button onclick="generateSuggestion()" id="generate-btn" class="px-4 py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-600 hover:to-cyan-600 text-sm font-medium transition text-white">
                    🚀 생성하기
                </button>
            </div>
        </div>
    </div>
    
    <!-- AI 제안 미리보기/승인 패널 -->
    <div id="suggestion-panel" class="fixed bottom-0 left-0 right-0 bg-slate-800/95 backdrop-blur border-t border-white/10 transform translate-y-full transition-transform duration-300 z-40">
        <div class="max-w-4xl mx-auto p-4">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl">🎯</span>
                        <div>
                            <h4 id="suggestion-title" class="font-bold text-white">AI 제안</h4>
                            <p id="suggestion-desc" class="text-sm text-slate-400"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-slate-400">
                            <span class="text-emerald-400 font-medium" id="suggestion-nodes-count">0</span> 노드
                        </span>
                        <span class="text-slate-400">
                            <span class="text-cyan-400 font-medium" id="suggestion-edges-count">0</span> 연결
                        </span>
                        <span class="text-slate-400">
                            신뢰도: <span class="text-purple-400 font-medium" id="suggestion-confidence">-</span>
                        </span>
                    </div>
                </div>
                <div class="flex gap-2 ml-4">
                    <button onclick="rejectSuggestion()" class="px-4 py-2 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-400 text-sm font-medium transition flex items-center gap-2">
                        <span>✕</span>
                        <span>거절</span>
                    </button>
                    <button onclick="approveSuggestion()" class="px-4 py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-600 hover:to-cyan-600 text-white text-sm font-medium transition flex items-center gap-2">
                        <span>✓</span>
                        <span>승인하여 반영</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 버전 히스토리 모달 -->
    <div id="version-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800 rounded-2xl border border-white/10 shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col">
            <!-- 모달 헤더 -->
            <div class="flex items-center justify-between p-4 border-b border-white/10 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📜</span>
                    <h3 class="text-lg font-bold text-white">버전 히스토리</h3>
                </div>
                <button onclick="closeVersionHistory()" class="p-2 hover:bg-white/10 rounded-lg transition">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- 버전 목록 -->
            <div class="flex-1 overflow-y-auto p-4">
                <div id="version-list" class="space-y-3">
                    <!-- JS에서 동적 생성 -->
                    <div class="text-center py-8 text-slate-500">
                        <div class="animate-spin w-8 h-8 border-2 border-purple-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                        버전 히스토리를 불러오는 중...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 초기 데이터 -->
    <script>
        window.QUANTUM_DATA = <?php echo $initialData; ?>;
    </script>

    <!-- 메인 스크립트 -->
    <script src="quantum_modeling.js"></script>
</body>
</html>
<?php
/**
 * ========================================
 * DB 참조 정보
 * ========================================
 *
 * 테이블: mdl_at_quantum_contents
 * - content_id (VARCHAR): 콘텐츠 ID
 * - title (VARCHAR): 문제 제목
 * - answer (VARCHAR): 정답
 * - stage_names (TEXT): JSON 형태의 단계 이름 배열
 *
 * 테이블: mdl_at_quantum_concepts
 * - concept_id (VARCHAR): 개념 ID
 * - content_id (VARCHAR): 콘텐츠 ID
 * - name (VARCHAR): 개념 이름
 * - icon (VARCHAR): 아이콘
 * - color (VARCHAR): 색상 코드
 *
 * 테이블: mdl_at_quantum_nodes
 * - node_id (VARCHAR): 노드 ID
 * - content_id (VARCHAR): 콘텐츠 ID
 * - label (VARCHAR): 노드 라벨
 * - type (VARCHAR): 노드 타입 (start/correct/wrong/partial/confused/success/fail)
 * - stage (INT): 단계 번호
 * - x, y (INT): 좌표
 * - description (TEXT): 설명
 *
 * 테이블: mdl_at_quantum_node_concepts
 * - node_id (VARCHAR): 노드 ID
 * - concept_id (VARCHAR): 개념 ID
 * - content_id (VARCHAR): 콘텐츠 ID
 *
 * 테이블: mdl_at_quantum_edges
 * - source_node_id (VARCHAR): 출발 노드 ID
 * - target_node_id (VARCHAR): 도착 노드 ID
 * - content_id (VARCHAR): 콘텐츠 ID
 */
?>
