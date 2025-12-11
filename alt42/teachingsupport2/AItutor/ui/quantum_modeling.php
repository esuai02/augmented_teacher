<?php
/**
 * 양자 붕괴 학습 미로 (Quantum Collapse Learning Maze)
 * 
 * 문제 풀이 경로를 양자 상태 붕괴 개념으로 시각화
 * - OpenAI API를 통해 문제 분석 및 노드/엣지 자동 생성
 * - React Flow 기반 인터랙티브 미로
 * - 유기적 뉴런 배양 시스템
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/moodle/config.php');

// 컨텐츠 ID 받기
$contentsId = required_param('id', PARAM_RAW);

// 데이터베이스에서 컨텐츠 정보 조회
global $DB;

// 문제 정보 가져오기
$questionData = null;
$imageUrl = null;

try {
    $interaction = $DB->get_record_sql(
        "SELECT * FROM {ktm_teaching_interactions} WHERE contentsid = ? ORDER BY id DESC LIMIT 1",
        [$contentsId]
    );
    
    if ($interaction) {
        // narration_text에서 문제 정보 추출
        $questionData = [
            'narration_text' => $interaction->narration_text ?? '',
            'image_url' => $interaction->image_url ?? '',
            'faqtext' => $interaction->faqtext ?? null
        ];
        $imageUrl = $interaction->image_url ?? '';
    }
} catch (Exception $e) {
    // 테이블이 없거나 오류 발생 시 무시
    error_log("[quantum_modeling.php] interactions 조회 오류: " . $e->getMessage());
}

// 기본값 설정
if (!$questionData) {
    $questionData = [
        'narration_text' => '',
        'image_url' => '',
        'faqtext' => null
    ];
}

// teaching_contents에서 추가 정보 조회 (테이블 존재 여부 확인)
try {
    $dbman = $DB->get_manager();
    if ($dbman->table_exists('ktm_teaching_contents')) {
        $teachingContent = $DB->get_record_sql(
            "SELECT * FROM {ktm_teaching_contents} WHERE contentsid = ?",
            [$contentsId]
        );
        
        if ($teachingContent) {
            if (!$questionData) $questionData = [];
            $questionData['question_text'] = $teachingContent->questiontext ?? '';
            $questionData['question_image'] = $teachingContent->questionimage ?? '';
            if (empty($imageUrl) && !empty($teachingContent->questionimage)) {
                $imageUrl = $teachingContent->questionimage;
            }
        }
    }
} catch (Exception $e) {
    // 테이블이 없거나 오류 발생 시 무시
    error_log("[quantum_modeling.php] teaching_contents 조회 오류: " . $e->getMessage());
}

// JSON으로 전달할 데이터
$initialData = json_encode([
    'contentsId' => $contentsId,
    'questionData' => $questionData,
    'imageUrl' => $imageUrl,
    'userId' => $USER->id ?? 0,
    'userName' => $USER->firstname ?? 'Guest'
], JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔮 양자 붕괴 학습 미로</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="quantum_modeling.css">
    
    <style>
        /* 기본 스타일 */
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            color: white;
        }
        
        /* 글로우 효과 */
        .glow-cyan { box-shadow: 0 0 20px rgba(6, 182, 212, 0.5); }
        .glow-purple { box-shadow: 0 0 20px rgba(139, 92, 246, 0.5); }
        .glow-green { box-shadow: 0 0 20px rgba(16, 185, 129, 0.5); }
        .glow-red { box-shadow: 0 0 20px rgba(239, 68, 68, 0.5); }
        .glow-amber { box-shadow: 0 0 20px rgba(245, 158, 11, 0.5); }
        
        /* 애니메이션 */
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.6; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        @keyframes collapse {
            0% { transform: scale(1.5); opacity: 0; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .animate-pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
        .animate-float { animation: float 3s ease-in-out infinite; }
        .animate-collapse { animation: collapse 0.5s ease-out; }
        
        /* 노드 스타일 */
        .quantum-node {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .quantum-node:hover {
            transform: scale(1.1);
            filter: brightness(1.2);
        }
        .quantum-node.selected {
            transform: scale(1.15);
        }
        .quantum-node.available {
            animation: pulse-glow 1.5s ease-in-out infinite;
        }
        
        /* 경로 스타일 */
        .quantum-edge {
            transition: all 0.3s ease;
        }
        .quantum-edge.active {
            stroke-width: 3;
            filter: drop-shadow(0 0 8px currentColor);
        }
        
        /* 개념 패널 */
        .concept-item {
            transition: all 0.3s ease;
        }
        .concept-item.active {
            background: rgba(255, 255, 255, 0.1);
        }
        .concept-item.collapsing {
            animation: collapse 0.5s ease-out;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.8);
        }
        
        /* 로딩 */
        .loading-spinner {
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid #06b6d4;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* 스크롤바 */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); }
        ::-webkit-scrollbar-thumb { background: rgba(139, 92, 246, 0.5); border-radius: 3px; }
    </style>
</head>
<body>
    <div id="quantum-app">
        <!-- 로딩 화면 -->
        <div id="loading-screen" class="fixed inset-0 flex items-center justify-center bg-slate-950 z-50">
            <div class="text-center">
                <div class="loading-spinner mx-auto mb-4"></div>
                <p class="text-cyan-400 text-sm">🔮 양자 상태 분석 중...</p>
                <p class="text-slate-500 text-xs mt-2" id="loading-status">문제 데이터 로딩</p>
            </div>
        </div>
        
        <!-- 메인 컨테이너 -->
        <div id="main-container" class="hidden min-h-screen p-4">
            <!-- 헤더 -->
            <header class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent flex items-center gap-2">
                        <span class="text-2xl">🔮</span> 양자 붕괴 학습 미로
                    </h1>
                    <p class="text-slate-400 text-xs">경로를 선택하며 개념을 붕괴시키세요</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="addNewPath()" class="px-3 py-1.5 rounded-lg bg-purple-500/20 hover:bg-purple-500/30 text-purple-400 text-xs transition ring-1 ring-purple-500/30">
                        ✨ 내 풀이로 길 만들기
                    </button>
                    <button onclick="resetMaze()" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs transition">
                        🔄 초기화
                    </button>
                    <button onclick="window.close()" class="px-3 py-1.5 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-400 text-xs transition">
                        ✕ 닫기
                    </button>
                </div>
            </header>
            
            <!-- 메인 레이아웃 -->
            <div class="flex gap-4" style="height: calc(100vh - 120px);">
                <!-- 왼쪽: 개념 패널 -->
                <aside id="concept-panel" class="w-52 flex-shrink-0">
                    <div class="bg-slate-900/80 backdrop-blur rounded-xl border border-white/10 p-3 h-full overflow-auto">
                        <h3 class="text-sm font-bold text-white mb-3 flex items-center gap-2">
                            <span class="text-lg">🧠</span> 개념 붕괴 현황
                        </h3>
                        <div id="concept-list" class="space-y-2">
                            <!-- 동적으로 생성됨 -->
                        </div>
                        <div class="mt-4 pt-3 border-t border-white/10">
                            <div class="text-xs text-slate-400">
                                활성화: <span id="activated-count" class="text-white font-bold">0</span> / <span id="total-concepts">0</span>
                            </div>
                            <div class="mt-1 h-1.5 bg-slate-700 rounded-full overflow-hidden">
                                <div id="concept-progress" class="h-full bg-gradient-to-r from-cyan-500 to-purple-500 transition-all duration-500" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </aside>
                
                <!-- 중앙: 미로 시각화 -->
                <main class="flex-1 flex flex-col gap-4">
                    <!-- SVG 미로 -->
                    <div class="flex-1 bg-slate-900/50 backdrop-blur rounded-xl border border-white/10 p-2 overflow-hidden">
                        <svg id="maze-svg" viewBox="0 0 700 580" class="w-full h-full">
                            <defs>
                                <filter id="glow">
                                    <feGaussianBlur stdDeviation="2.5" result="c"/>
                                    <feMerge>
                                        <feMergeNode in="c"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                                <filter id="strongGlow">
                                    <feGaussianBlur stdDeviation="4" result="c"/>
                                    <feMerge>
                                        <feMergeNode in="c"/>
                                        <feMergeNode in="c"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                                <linearGradient id="pathGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#06b6d4"/>
                                    <stop offset="100%" stop-color="#8b5cf6"/>
                                </linearGradient>
                                <pattern id="grid" width="30" height="30" patternUnits="userSpaceOnUse">
                                    <path d="M 30 0 L 0 0 0 30" fill="none" stroke="rgba(148,163,184,0.08)" stroke-width="0.5"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#grid)"/>
                            <g id="edges-layer"></g>
                            <g id="nodes-layer"></g>
                        </svg>
                    </div>
                    
                    <!-- 하단 패널 -->
                    <div id="bottom-panel" class="bg-slate-900/80 backdrop-blur rounded-xl border border-white/10 p-4">
                        <!-- 양자 상태 + 선택지 -->
                        <div id="game-panel" class="flex gap-6">
                            <!-- 양자 상태 벡터 -->
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs text-slate-400">양자 상태</span>
                                    <span class="text-xs font-mono text-purple-400">|ψ⟩</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-16 text-xs text-slate-400">α 정답</span>
                                        <div class="flex-1 h-2 bg-slate-700 rounded-full overflow-hidden">
                                            <div id="alpha-bar" class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-500" style="width: 33%"></div>
                                        </div>
                                        <span id="alpha-value" class="w-10 text-xs text-right text-slate-400">33%</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-16 text-xs text-slate-400">β 오개념</span>
                                        <div class="flex-1 h-2 bg-slate-700 rounded-full overflow-hidden">
                                            <div id="beta-bar" class="h-full bg-gradient-to-r from-rose-500 to-rose-400 transition-all duration-500" style="width: 33%"></div>
                                        </div>
                                        <span id="beta-value" class="w-10 text-xs text-right text-slate-400">33%</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-16 text-xs text-slate-400">γ 혼란</span>
                                        <div class="flex-1 h-2 bg-slate-700 rounded-full overflow-hidden">
                                            <div id="gamma-bar" class="h-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-500" style="width: 34%"></div>
                                        </div>
                                        <span id="gamma-value" class="w-10 text-xs text-right text-slate-400">34%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 선택지 -->
                            <div class="flex-1">
                                <p class="text-xs text-slate-400 mb-2">다음 단계 선택</p>
                                <div id="choices-container" class="flex flex-wrap gap-2">
                                    <!-- 동적으로 생성됨 -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- 완료 패널 -->
                        <div id="complete-panel" class="hidden text-center py-4">
                            <div id="complete-icon" class="text-4xl mb-2">🎉</div>
                            <h3 id="complete-title" class="text-lg font-bold text-emerald-400">정답 붕괴!</h3>
                            <p id="complete-desc" class="text-xs text-slate-400 mb-3">
                                학습된 개념: <span id="complete-concepts">0</span>개 | 경로: <span id="complete-steps">0</span>단계
                            </p>
                            <div class="flex gap-2 justify-center">
                                <button onclick="backtrackOne()" class="px-4 py-2 bg-emerald-500/20 text-emerald-400 rounded-lg text-xs font-medium ring-1 ring-emerald-500/30 hover:bg-emerald-500/30 transition">
                                    ↩ 되돌리기
                                </button>
                                <button onclick="resetMaze()" class="px-4 py-2 bg-purple-500/20 text-purple-400 rounded-lg text-xs font-medium ring-1 ring-purple-500/30 hover:bg-purple-500/30 transition">
                                    🔄 처음부터
                                </button>
                            </div>
                        </div>
                    </div>
                </main>
                
                <!-- 오른쪽: 문제 정보 -->
                <aside id="question-panel" class="w-64 flex-shrink-0">
                    <div class="bg-slate-900/80 backdrop-blur rounded-xl border border-white/10 p-3 h-full overflow-auto">
                        <h3 class="text-sm font-bold text-white mb-3 flex items-center gap-2">
                            <span class="text-lg">📝</span> 문제 정보
                        </h3>
                        <div id="question-image-container" class="mb-3 rounded-lg overflow-hidden bg-slate-800">
                            <img id="question-image" src="" alt="문제 이미지" class="w-full hidden">
                            <div id="no-image" class="p-4 text-center text-slate-500 text-xs">이미지 없음</div>
                        </div>
                        <div id="question-text" class="text-xs text-slate-300 leading-relaxed">
                            <!-- 문제 텍스트 -->
                        </div>
                        
                        <!-- 유형 뱃지 -->
                        <div class="mt-4 pt-3 border-t border-white/10">
                            <h4 class="text-xs text-slate-400 mb-2">학습 유형</h4>
                            <div id="learner-badges" class="flex flex-wrap gap-1">
                                <!-- 동적으로 생성됨 -->
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
        
        <!-- 새 경로 추가 모달 -->
        <div id="add-path-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-900 rounded-2xl border border-white/10 w-full max-w-lg">
                <div class="p-4 border-b border-white/10">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <span>✨</span> 내 풀이로 길 만들기
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">나만의 풀이 방법을 입력하면 AI가 새로운 경로를 만들어줍니다</p>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">풀이 제목</label>
                        <input type="text" id="new-path-title" class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="예: 그래프로 직관적 접근">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">풀이 설명 (수식 가능)</label>
                        <textarea id="new-path-desc" rows="4" class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="풀이 과정을 자세히 설명해주세요..."></textarea>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">연결할 노드</label>
                        <select id="new-path-parent" class="w-full px-3 py-2 bg-slate-800 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <!-- 동적으로 생성됨 -->
                        </select>
                    </div>
                </div>
                <div class="p-4 border-t border-white/10 flex justify-end gap-2">
                    <button onclick="closeAddPathModal()" class="px-4 py-2 bg-slate-800 text-slate-400 rounded-lg text-sm hover:bg-slate-700 transition">
                        취소
                    </button>
                    <button onclick="submitNewPath()" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-500 transition">
                        🚀 경로 생성
                    </button>
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

