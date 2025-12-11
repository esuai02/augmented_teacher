<?php
/**
 * 🌌 마이 궤도 - WXsperta 메인 페이지
 * 리팩토링일: 2025-12-07
 * 
 * 분리된 파일:
 * - wxsperta_styles.css: CSS 스타일시트
 * - wxsperta_app.js: JavaScript 애플리케이션 로직
 */
include_once("/home/moodle/public_html/moodle/config.php");
include_once("config.php"); // OpenAI API 설정 포함
require_once("ai_agents/cards_data.php"); // 공통 카드 데이터
global $DB,$USER;
require_login();

// GET 파라미터에서 userid 가져오기, 없으면 현재 로그인한 사용자 ID 사용
$userid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;
$student_id = isset($_GET["student_id"]) ? $_GET["student_id"] : $userid;
$teacher_id = $USER->id;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student'; // 기본값은 student

// 데이터베이스에서 에이전트 정보 가져오기
try {
    // 테이블 존재 여부 확인
    $tables = $DB->get_tables();
    $wxsperta_tables_exist = false;
    
    foreach ($tables as $table) {
        if (strpos($table, 'wxsperta_') !== false) {
            $wxsperta_tables_exist = true;
            break;
        }
    }
    
    if ($wxsperta_tables_exist) {
        $agents = $DB->get_records('wxsperta_agents', [], 'id ASC');
        $agent_priorities = $DB->get_records('wxsperta_agent_priorities', ['user_id' => $userid]);
        $user_profile = $DB->get_record('wxsperta_user_profiles', ['user_id' => $userid]);
    } else {
        // 테이블이 없으면 빈 배열
        $agents = [];
        $agent_priorities = [];
        $user_profile = null;
    }
} catch (Exception $e) {
    // 오류 발생 시 빈 배열
    $agents = [];
    $agent_priorities = [];
    $user_profile = null;
    error_log("WXsperta DB Error [wxsperta.php]: " . $e->getMessage());
}

// cards_data.php의 데이터를 사용
if (empty($agents)) {
    $agents = array_map(function($card) {
        return [
            'id' => $card['id'], // 폴더 이름 ID (예: 09_vertical_explorer)
            'number' => $card['number'], // 숫자 번호
            'name' => $card['name'],
            'icon' => $card['icon'] ?? '🎯',
            'color' => $card['color'] ?? '#666',
            'category' => $card['category'],
            'description' => $card['description'],
            'shortDesc' => $card['subtitle'],
            'connections' => $card['connections'] ?? []
        ];
    }, $cards_data);
}

// 카테고리별 경로 매핑
$category_paths = [
    'future' => 'future_design',
    'future_design' => 'future_design',
    'execution' => 'execution',
    'branding' => 'branding',
    'knowledge' => 'knowledge_management',
    'knowledge_management' => 'knowledge_management'
];

// JSON으로 변환
$agents_json = json_encode(array_values($agents));
$priorities_json = json_encode(array_values($agent_priorities));
$profile_json = json_encode($user_profile ?: new stdClass());
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌌 마이 궤도 - My Orbit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="version_control_ui.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="wxsperta_styles.css">
</head>
<body>
    <div id="mainContainer" class="main-container">
        <div class="orbit-container">
            <!-- 헤더 -->
            <header class="orbit-header">
                <h1 class="orbit-title">🌌 마이 궤도</h1>
                <p class="orbit-subtitle">
                    네 궤도대로 가도 돼. 모든 별은 자기 속도로 빛나니까 ✨
                </p>
            </header>
            
            <!-- 감정 날씨 체크 -->
            <div class="mood-checker" id="moodChecker">
                <p class="mood-question">오늘 네 우주의 날씨는 어때? 🌌</p>
                <div class="mood-options">
                    <button class="mood-btn" data-mood="sunny" title="최고!">☀️</button>
                    <button class="mood-btn" data-mood="cloudy" title="괜찮아">🌤️</button>
                    <button class="mood-btn" data-mood="overcast" title="흐림">☁️</button>
                    <button class="mood-btn" data-mood="rainy" title="지쳤어">🌧️</button>
                </div>
                <p class="mood-response" id="moodResponse" style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--starlight);"></p>
            </div>
            
            <!-- 뷰 스위칭 버튼 (좌측 상단) -->
            <div class="view-switcher" style="position: absolute; top: 1rem; left: 1rem; display: flex; gap: 0.5rem; padding: 0.25rem; background: rgba(255,255,255,0.08); border-radius: 12px; border: 1px solid rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <button id="gridViewBtn" class="view-btn active" onclick="switchView('grid')" title="카드 뷰">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4zM4 16h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4z"/></svg>
                </button>
                <button id="networkViewBtn" class="view-btn" onclick="switchView('network')" title="네트워크 뷰">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                </button>
            </div>
            
            <!-- 미션 그리드 (Grid View) -->
            <div id="missionGrid" class="view-container active">
                <!-- 섹터별로 동적 렌더링 -->
            </div>
            
            <!-- 네트워크 뷰 (Network View) -->
            <div id="networkView" class="view-container" style="display: none;">
                <div class="network-container">
                    <svg id="networkSvg" width="100%" height="600">
                        <defs>
                            <!-- 화살표 마커 -->
                            <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto">
                                <polygon points="0 0, 10 3.5, 0 7" fill="var(--starlight)" opacity="0.5"/>
                            </marker>
                            <!-- 카테고리별 그라데이션 -->
                            <linearGradient id="grad-voyage" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:var(--cat-voyage);stop-opacity:0.8"/>
                                <stop offset="100%" style="stop-color:var(--cat-voyage);stop-opacity:0.4"/>
                            </linearGradient>
                            <linearGradient id="grad-mission" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:var(--cat-mission);stop-opacity:0.8"/>
                                <stop offset="100%" style="stop-color:var(--cat-mission);stop-opacity:0.4"/>
                            </linearGradient>
                            <linearGradient id="grad-flag" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:var(--cat-flag);stop-opacity:0.8"/>
                                <stop offset="100%" style="stop-color:var(--cat-flag);stop-opacity:0.4"/>
                            </linearGradient>
                            <linearGradient id="grad-resource" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:var(--cat-resource);stop-opacity:0.8"/>
                                <stop offset="100%" style="stop-color:var(--cat-resource);stop-opacity:0.4"/>
                            </linearGradient>
                            <!-- 글로우 필터 -->
                            <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                                <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                <feMerge>
                                    <feMergeNode in="coloredBlur"/>
                                    <feMergeNode in="SourceGraphic"/>
                                </feMerge>
                            </filter>
                        </defs>
                        <!-- 연결선 그룹 -->
                        <g id="connectionsGroup"></g>
                        <!-- 노드 그룹 -->
                        <g id="nodesGroup"></g>
                    </svg>
                    
                    <!-- 네트워크 컨트롤 -->
                    <div class="network-controls">
                        <button class="network-ctrl-btn" onclick="zoomNetwork(1.2)" title="확대">➕</button>
                        <button class="network-ctrl-btn" onclick="zoomNetwork(0.8)" title="축소">➖</button>
                        <button class="network-ctrl-btn" onclick="resetNetworkView()" title="초기화">🔄</button>
                        <button class="network-ctrl-btn" onclick="toggleAnimation()" title="애니메이션 토글" id="animToggle">▶️</button>
                    </div>
                    
                </div>
            </div>
            
            
            <!-- 상태 표시 -->
            <div style="text-align: center; padding: 1.5rem; font-size: 0.75rem; color: var(--starlight);">
                <?php echo $role === 'teacher' ? '🛠️ 관제탑 모드 (편집 가능)' : '🚀 탐험가 모드'; ?>
                · 미션을 클릭하면 관제탑과 대화할 수 있어요
            </div>
        </div>
    </div>

    <!-- Modal Overlay & Popup -->
    <div id="modalOverlay" class="modal-overlay" onclick="handleCancel()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div id="modalBody">
                <!-- 모달 내용이 여기에 동적으로 추가됩니다 -->
            </div>
        </div>
    </div>

    <!-- 채팅 패널 -->
    <div id="chatPanel" class="chat-panel">
        <!-- 채팅 내용이 여기에 동적으로 추가됩니다 -->
    </div>

    <!-- PHP 데이터를 JavaScript로 전달 -->
    <script>
        const phpData = {
            agents: <?php echo $agents_json; ?>,
            priorities: <?php echo $priorities_json; ?>,
            userProfile: <?php echo $profile_json; ?>,
            userId: <?php echo $userid; ?>,
            role: '<?php echo $role; ?>',
            apiUrl: '<?php echo WXSPERTA_BASE_URL; ?>api.php',
            categoryPaths: <?php echo json_encode($category_paths); ?>
        };
    </script>
    <script src="wxsperta_app.js"></script>
</body>
</html>
