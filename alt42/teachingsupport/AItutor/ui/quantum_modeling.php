<?php
/**
 * 양자 붕괴 학습 미로 (Quantum Collapse Learning Maze)
 * y=x²-ax 정삼각형 문제 - 양자 경로 분석
 *
 * React 없이 순수 PHP + Vanilla JS 구현
 * 정답: a=2√3 | 모든 가능한 풀이/오류 경로 시각화
 *
 * 파일: quantum_modeling.php
 * 위치: alt42/teachingsupport/AItutor/ui/
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/moodle/config.php');

// 컨텐츠 ID 받기
$contentsId = optional_param('id', '', PARAM_RAW);

// 데이터베이스에서 컨텐츠 정보 조회
global $DB;

// 문제 정보 가져오기
$questionData = null;
$imageUrl = null;
$solutionImageUrl = null;
$questionImageUrl = null;
$contentId = null;
$contentsType = null;

// $thisboard에서 contentsid, contentstype 가져오기
try {
    if (!empty($contentsId)) {
        $thisboard = $DB->get_record_sql(
            "SELECT * FROM mdl_abessi_messages WHERE wboardid = ? ORDER BY tlaststroke DESC LIMIT 1",
            [$contentsId]
        );

        if ($thisboard) {
            $contentId = $thisboard->contentsid;
            $contentsType = $thisboard->contentstype;

            // mdl_question에서 문제/해설 텍스트 가져오기
            $qtext0 = $DB->get_record_sql(
                "SELECT questiontext, generalfeedback FROM mdl_question WHERE id = ? ORDER BY id DESC LIMIT 1",
                [$contentId]
            );

            if ($qtext0) {
                // 해설 이미지 추출
                $htmlDom1 = new DOMDocument;
                @$htmlDom1->loadHTML('<?xml encoding="UTF-8">' . $qtext0->generalfeedback);
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
                @$htmlDom2->loadHTML('<?xml encoding="UTF-8">' . $qtext0->questiontext);
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
    }
} catch (Exception $e) {
    error_log("[quantum_modeling.php:$contentsId] thisboard 조회 오류: " . $e->getMessage());
}

// 기존 세션 확인 (자동 복원용)
$hasExistingSession = false;
$existingSessionId = null;
if (!empty($contentId)) {
    try {
        global $DB;
        $lastSession = $DB->get_record_sql(
            "SELECT session_id FROM {at_quantum_user_sessions} 
             WHERE user_id = ? AND content_id = ? AND is_complete = 0 
             ORDER BY updated_at DESC LIMIT 1",
            [$USER->id ?? 0, $contentId]
        );
        
        if ($lastSession) {
            $hasExistingSession = true;
            $existingSessionId = $lastSession->session_id;
        }
    } catch (Exception $e) {
        error_log("[quantum_modeling.php:$contentsId] 세션 조회 오류: " . $e->getMessage());
    }
}

// JSON으로 전달할 데이터
$initialData = json_encode([
    'contentsId' => $contentsId,
    'contentId' => $contentId,
    'contentsType' => $contentsType,
    'questionData' => $questionData,
    'imageUrl' => $imageUrl,
    'questionImageUrl' => $questionImageUrl,
    'solutionImageUrl' => $solutionImageUrl,
    'userId' => $USER->id ?? 0,
    'userName' => $USER->firstname ?? 'Guest',
    'sessionId' => $existingSessionId,
    'hasExistingSession' => $hasExistingSession
], JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔮 y=x²-ax 정삼각형 문제 - 양자 경로 분석</title>

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
        .quantum-node { cursor: pointer; transition: all 0.2s ease; }
        .quantum-node:hover { transform: scale(1.05); }

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
                <h1 class="text-xl font-bold bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                    🔮 y=x²-ax 정삼각형 문제 - 양자 경로 분석
                </h1>
                <p class="text-slate-400 text-sm">정답: a=2√3 | 모든 가능한 풀이/오류 경로 시각화</p>
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
                        진행도: <span id="activated-count" class="text-white font-bold">0</span>/<span id="total-concepts">10</span>
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
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-slate-400">🗺️ 맵 크기</span>
                        <span id="map-scale-value" class="text-sm text-white font-medium px-2 py-1 bg-purple-500/20 rounded">100%</span>
                    </div>
                    <input type="range" id="map-scale-slider"
                        min="50" max="200" value="100" step="10"
                        class="w-full cursor-pointer"
                        oninput="if(typeof updateMapScale==='function')updateMapScale(this.value)">
                    <div class="flex justify-between text-xs text-slate-500 mt-2">
                        <span>50%</span>
                        <span>100%</span>
                        <span>200%</span>
                    </div>
                    <!-- 빠른 조절 버튼 -->
                    <div class="flex gap-2 mt-3">
                        <button onclick="document.getElementById('map-scale-slider').value=50;if(typeof updateMapScale==='function')updateMapScale(50)"
                            class="flex-1 px-2 py-1.5 text-xs rounded bg-slate-800 hover:bg-slate-700 transition">축소</button>
                        <button onclick="document.getElementById('map-scale-slider').value=100;if(typeof updateMapScale==='function')updateMapScale(100)"
                            class="flex-1 px-2 py-1.5 text-xs rounded bg-slate-800 hover:bg-slate-700 transition">기본</button>
                        <button onclick="document.getElementById('map-scale-slider').value=200;if(typeof updateMapScale==='function')updateMapScale(200)"
                            class="flex-1 px-2 py-1.5 text-xs rounded bg-slate-800 hover:bg-slate-700 transition">확대</button>
                    </div>
                </div>
            </aside>

            <!-- 중앙: 미로 (스크롤 가능) -->
            <main class="flex-1 bg-slate-900/50 backdrop-blur rounded-xl border border-white/10 overflow-auto" style="max-height: 75vh;">
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
 * 테이블: mdl_abessi_messages
 * - wboardid (VARCHAR): 화이트보드 ID (파라미터로 받음)
 * - contentsid (VARCHAR): 콘텐츠 ID
 * - contentstype (VARCHAR): 콘텐츠 유형
 * - tlaststroke (INT): 마지막 스트로크 타임스탬프
 *
 * 테이블: mdl_question
 * - id (INT): 문제 ID
 * - questiontext (TEXT): 문제 텍스트 (HTML)
 * - generalfeedback (TEXT): 해설 텍스트 (HTML)
 *
 * 테이블: ktm_teaching_interactions (선택적)
 * - contentsid (VARCHAR): 콘텐츠 ID
 * - narration_text (TEXT): 나레이션 텍스트
 * - image_url (VARCHAR): 이미지 URL
 * - faqtext (TEXT): FAQ 텍스트
 *
 * 테이블: ktm_teaching_contents (선택적)
 * - contentsid (VARCHAR): 콘텐츠 ID
 * - questiontext (TEXT): 문제 텍스트
 * - questionimage (VARCHAR): 문제 이미지 URL
 */
?>
