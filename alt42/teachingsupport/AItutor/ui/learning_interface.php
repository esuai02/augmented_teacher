<?php
/**
 * AI 튜터 학습 인터페이스
 * 분석 완료 후 실제 학습이 진행되는 화면
 * 문항 이미지 분석 → 맞춤형 페르소나 생성 → 장기기억 집중숙련 지원
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    2.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(__DIR__ . '/../includes/question_persona_generator.php');
 
$analysisId = $_GET['id'] ?? null;
$studentId = $_GET['studentid'] ?? $USER->id;
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
 
$thisboard = $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid=? ORDER BY tlaststroke DESC LIMIT 1", [$analysisId]); 
$contentId = $thisboard->contentsid;
$contentsType = $thisboard->contentstype;

// 문제/해설 이미지 추출
$imgSrc1 = null; // 해설 이미지
$imgSrc2 = null; // 문제 이미지

$qtext0 = $DB->get_record_sql("SELECT questiontext,generalfeedback FROM mdl_question WHERE id=? ORDER BY id DESC LIMIT 1", [$contentId]);
if ($qtext0) {
    // 해설 이미지 추출
    $htmlDom1 = new DOMDocument;
    @$htmlDom1->loadHTML($qtext0->generalfeedback); 
    $imageTags1 = $htmlDom1->getElementsByTagName('img');
    foreach($imageTags1 as $imageTag1) {
        $imgSrc1 = $imageTag1->getAttribute('src'); 
        $imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
        if(strpos($imgSrc1, 'MATRIX/MATH') !== false && strpos($imgSrc1, 'hintimages') === false) break;
    }
    
    // 문제 이미지 추출
    $htmlDom2 = new DOMDocument;
    @$htmlDom2->loadHTML($qtext0->questiontext); 
    $imageTags2 = $htmlDom2->getElementsByTagName('img');
    foreach($imageTags2 as $imageTag2) {
        $imgSrc2 = $imageTag2->getAttribute('src'); 
        $imgSrc2 = str_replace(' ', '%20', $imgSrc2); 
        if(strpos($imgSrc2, 'hintimages') === false && (strpos($imgSrc2, '.png') !== false || strpos($imgSrc2, '.jpg') !== false)) break;
    }
}

// ========== 문항 분석 및 페르소나 생성 (OpenAI Vision) ==========
$generator = new QuestionPersonaGenerator();
$analysisResult = null;
$itemPersonas = [];
$masteryRecommendations = [];

// 캐시된 분석 결과 확인 (자동 분석 비활성화 - 버튼 클릭 시에만 실행)
if (!$forceRefresh) {
    $analysisResult = $generator->getCachedAnalysis($analysisId, $studentId);
}

// 자동 분석 비활성화 - 사용자가 버튼을 클릭해야만 분석 시작
$needsAnalysis = false;
$canAnalyze = !$analysisResult && $imgSrc2; // 분석 가능 여부 (버튼 활성화용)

if ($analysisResult) {
    $itemPersonas = $analysisResult['persona'] ?? [];
    $masteryRecommendations = $analysisResult['mastery_recommendations'] ?? [];
}

// 화이트보드 URL 결정 (컨텐츠 유형별)
$whiteboardBaseUrl = '/moodle/local/augmented_teacher/whiteboard/';

// 화이트보드 파일: board_question.php로 고정
$whiteboardFile = 'board_question.php';

// 화이트보드 ID 생성 (URL의 id 파라미터 우선 사용)
$whiteboardId = $_GET['wboardid'] ?? ($analysisId ?? "jnrsorksqcrark{$contentId}_user{$studentId}");
// board_topic.php에 필요한 파라미터 포함
$whiteboardUrl = $_GET['whiteboard'] ?? "{$whiteboardBaseUrl}{$whiteboardFile}?id={$whiteboardId}&studentid={$studentId}&contentsid={$contentId}";

// 분석 결과 로드
$analysisData = null;
if ($analysisId) {
    require_once(__DIR__ . '/../includes/db_manager.php');
    $dbManager = new DBManager();
    $analysisData = $dbManager->getAnalysisResult($analysisId);
}

// 기존 TTS 상호작용 확인 (contentsid와 contentstype으로 조회)
$existingTts = null;
$existingTtsId = null;
$existingAudioUrl = null;
try {
    // contentsid와 contentstype으로 ktm_teaching_interactions에서 조회
    if ($contentId && $contentsType !== null) {
        $existingTts = $DB->get_record_sql(
            "SELECT * FROM {ktm_teaching_interactions} WHERE contentsid = ? AND contentstype = ? AND audio_url IS NOT NULL AND audio_url != '' ORDER BY id DESC LIMIT 1",
            [$contentId, $contentsType]
        );
        error_log("[learning_interface.php] contentsid: {$contentId}, contentstype: {$contentsType} 로 조회");
    }
    
    // contentstype 없이 contentsid로만 조회 (fallback)
    if (!$existingTts && $contentId) {
        $existingTts = $DB->get_record_sql(
            "SELECT * FROM {ktm_teaching_interactions} WHERE contentsid = ? AND audio_url IS NOT NULL AND audio_url != '' ORDER BY id DESC LIMIT 1",
            [$contentId]
        );
        error_log("[learning_interface.php] contentsid: {$contentId} 로만 조회 (fallback)");
    }
    
    // wboardid로도 조회 (추가 fallback)
    if (!$existingTts && $whiteboardId) {
        $existingTts = $DB->get_record_sql(
            "SELECT * FROM {ktm_teaching_interactions} WHERE wboardid = ? AND audio_url IS NOT NULL AND audio_url != '' ORDER BY id DESC LIMIT 1",
            [$whiteboardId]
        );
        error_log("[learning_interface.php] wboardid: {$whiteboardId} 로 조회 (fallback)");
    }
    
    if ($existingTts) {
        $existingTtsId = $existingTts->id;
        $existingAudioUrl = $existingTts->audio_url;
        error_log("[learning_interface.php] 기존 TTS 발견 - ID: {$existingTtsId}, contentsid: " . ($existingTts->contentsid ?? 'null') . ", contentstype: " . ($existingTts->contentstype ?? 'null') . ", audio_url: " . ($existingAudioUrl ?? 'null'));
    }
} catch (Exception $e) {
    error_log("[learning_interface.php] 기존 TTS 확인 오류: " . $e->getMessage());
}

// 단원 정보
$unitName = '수학 문제';
$unitCode = '';
if ($analysisData && isset($analysisData['dialogue_analysis']['unit'])) {
    $unitName = $analysisData['dialogue_analysis']['unit']['korean'] ?? '수학 문제';
    $unitCode = $analysisData['dialogue_analysis']['unit']['code'] ?? '';
}

// ========== 풀이 단계별 페르소나 시스템 ==========
// 풀이 단계 정의 (통일: 문제해석, 식세우기, 풀이과정, 점검, 장기기억화)
$solvingStages = [
    '문제해석' => [
        'icon' => '📖',
        'subtitle' => '문제를 읽고 조건을 파악하는 단계',
        'ids' => [15, 20, 31, 42, 48, 49]
    ],
    '식세우기' => [
        'icon' => '🚀',
        'subtitle' => '어떻게 풀지 전략을 세우고 방정식 설정하는 단계',
        'ids' => [2, 3, 7, 12, 19, 35, 37, 41]
    ],
    '풀이과정' => [
        'icon' => '✏️',
        'subtitle' => '실제로 풀이를 진행하며 시간/감정을 조절하는 단계',
        'ids' => [1, 4, 5, 6, 10, 11, 13, 14, 17, 22, 23, 24, 25, 26, 27, 28, 33, 38, 39, 43, 44, 46, 50, 53, 54, 55, 56]
    ],
    '점검' => [
        'icon' => '🔍',
        'subtitle' => '중간·최종 검산 및 피로 관리 단계',
        'ids' => [16, 21, 29, 32, 34, 36, 45, 47, 51, 52]
    ],
    '장기기억화' => [
        'icon' => '🏁',
        'subtitle' => '반복 연습으로 장기기억에 정착시키는 단계',
        'ids' => [8, 9, 18, 30, 40, 57, 58, 59, 60]
    ]
];

// 기본 페르소나 유형 (12가지) - QuestionPersonaGenerator에서 가져옴
$basePersonas = $generator->getBasePersonas();

// 각 페르소나에 가이드 추가
$personaGuidance = [
    'avoider' => '작은 단계부터 시작해보자! 한 걸음씩 가면 돼 👣',
    'checker' => '네 판단을 믿어봐! 스스로 검증하는 힘을 키우자 🔍',
    'emotion_driven' => '한 문제는 한 문제일 뿐! 차분하게 다음으로 가자 🌊',
    'speed_miss' => '마지막 10초 검증! 속도보다 정확도가 진짜 실력 ✅',
    'attention_hopper' => '지금 이 문장에만 집중! 한 곳에 시선 고정해보자 👀',
    'pattern_seeker' => '원리를 찾는 건 좋아! 구조부터 파악하고 가자 🗺️',
    'efficiency_max' => '핵심 규칙 20%로 80% 해결! 스마트하게 가자 💡',
    'over_focus' => '여기까지만 확인! 완벽주의 내려놓기 연습 🧘',
    'concrete_learner' => '예시 하나로 시작! 구체적인 것부터 추상으로 🪜',
    'interactive' => '내 안의 선생님 깨우기! 스스로에게 질문해봐 💭',
    'low_drive' => '초단위 목표 달성! 지금 이 한 문제만 집중 🎮',
    'meta_high' => '고난도 도전! 네 전략을 더 날카롭게 만들자 ⚔️'
];

foreach ($basePersonas as $key => &$persona) {
    $persona['guidance'] = $personaGuidance[$key] ?? '';
}
unset($persona);

// 분석 결과에서 문항 정보 추출 (없으면 기본값)
$problemItems = [];
if (!empty($analysisResult['question_analysis']['problems'])) {
    foreach ($analysisResult['question_analysis']['problems'] as $idx => $p) {
        $problemItems[] = [
            'id' => $p['id'] ?? ($idx + 1),
            'text' => $p['text'] ?? '',
            'topic' => $analysisResult['question_analysis']['topic']['name'] ?? '수학 문제',
            'difficulty' => $p['difficulty'] ?? 'medium'
        ];
    }
}

// 현재 선택된 문항 페르소나 (기본값: 첫 번째 단계 '문제해석')
$currentStage = '문제해석';
$currentItemPersona = null;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlphaTutor - 학습 중</title>
    <link rel="stylesheet" href="learning_interface.css">
</head>
<body>
    <div id="app" class="app-container">
        <!-- 상단 헤더 -->
        <header class="header">
            <div class="header-left">
                <span class="logo">AlphaTutor</span>
                <span class="unit-name"><?php echo htmlspecialchars($unitName); ?></span>
            </div>
            
            <!-- 중앙: 감정 표현 -->
            <div class="header-center">
                <div class="emotion-selector-center">
                    <button id="emotionBtn" class="emotion-btn-center" onclick="toggleEmotionPicker()">
                        <span id="currentEmotionIcon" class="emotion-icon-large">😐</span>
                    </button>
                    
                    <!-- FAQ 점층 말풍선 -->
                    <div id="faqBubble" class="faq-speech-bubble hidden">
                        <div class="faq-bubble-content">
                            <span id="faqBubbleLabel" class="faq-bubble-label">🔹 단축</span>
                            <p id="faqBubbleText" class="faq-bubble-text"></p>
                        </div>
                        <div class="faq-bubble-progress">
                            <span id="faqBubbleProgress">1/6</span>
                        </div>
                    </div>
                    
                    <div id="emotionPicker" class="emotion-picker-center hidden">
                        <p class="picker-hint-small">지금 기분은?</p>
                        <div class="emotion-options-row">
                            <button class="emotion-option-btn" data-type="confident" onclick="selectEmotion('confident')">😊</button>
                            <button class="emotion-option-btn" data-type="neutral" onclick="selectEmotion('neutral')">😐</button>
                            <button class="emotion-option-btn" data-type="confused" onclick="selectEmotion('confused')">🤔</button>
                            <button class="emotion-option-btn" data-type="stuck" onclick="selectEmotion('stuck')">😵</button>
                            <button class="emotion-option-btn" data-type="anxious" onclick="selectEmotion('anxious')">😰</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 긍정 페르소나 유도 문구 배너 -->
            <div id="positiveGuidanceBanner" class="positive-guidance-banner hidden">
                <span id="positiveGuidanceIcon" class="guidance-icon">💪</span>
                <span id="positiveGuidanceText" class="guidance-text"></span>
                <button class="guidance-close" onclick="hidePositiveGuidance()">×</button>
            </div>
            
            <!-- 우측 상단: TTS 단계별 플레이어 -->
            <div class="header-right-controls">
                <div id="headerTtsPlayer" class="header-step-player hidden">
                    <!-- 현재 단계 표시 -->
                    <span id="ttsCurrentStep" class="tts-current-step">1/5</span>
                    
                    <!-- 재생/일시정지 -->
                    <button id="ttsPlayPauseBtn" class="step-play-btn" onclick="toggleTtsPlayPause()" title="재생/일시정지">
                        <span id="ttsPlayIcon">▶</span>
                    </button>
                    
                    <!-- 이전 단계 -->
                    <button id="ttsPrevBtn" class="step-nav-btn" onclick="ttsPrevSection()" title="이전 단계">
                        <span>◀◀</span>
                    </button>
                    
                    <!-- 단계 표시 (점) -->
                    <div id="ttsStepDots" class="step-dots">
                        <!-- JavaScript에서 동적 생성 -->
                    </div>
                    
                    <!-- 다음 단계 -->
                    <button id="ttsNextBtn" class="step-nav-btn" onclick="ttsNextSection()" title="다음 단계">
                        <span>▶▶</span>
                    </button>
                    
                    <!-- 재생 속도 -->
                    <button id="ttsSpeedBtn" class="step-control-btn speed-btn" onclick="cycleTtsSpeed()" title="재생 속도">
                        <span id="ttsSpeedLabel">1.0x</span>
                    </button>
                    
                    <!-- 자동재생 토글 -->
                    <button id="ttsAutoBtn" class="step-control-btn auto-btn" onclick="toggleTtsAutoPlay()" title="자동재생">
                        <span id="ttsAutoLabel">auto</span>
                    </button>
                </div>
            </div>
            
            <!-- 페르소나 오버레이 및 피커 (전역) -->
            <div id="personaPickerOverlay" class="persona-picker-overlay hidden" onclick="togglePersonaPicker()"></div>
            <div id="personaPicker" class="persona-picker hidden">
                <div class="picker-header">
                    <h2 class="picker-title">📊 풀이 단계별 인지 페르소나</h2>
                    <span id="pickerSourceBadge" class="ai-diagnosis-badge"><span class="ai-icon">📖</span> <span id="currentStageLabel">문제해석</span> 단계</span>
                </div>
                <p class="picker-hint">현재 풀이 단계에 맞는 인지 페르소나를 확인하세요. 카드를 클릭하면 상세 정보를 볼 수 있어요.<br><strong>📖 문제해석 → 🚀 식세우기 → ✏️ 풀이과정 → 🔍 점검 → 🏁 장기기억화</strong></p>
                
                <!-- 풀이 단계 탭 -->
                <div class="stage-tabs">
                    <?php 
                    $stageIcons = ['문제해석' => '📖', '식세우기' => '🚀', '풀이과정' => '✏️', '점검' => '🔍', '장기기억화' => '🏁'];
                    $stageNum = 1;
                    foreach ($solvingStages as $stageName => $stageData): 
                    ?>
                    <button class="stage-tab <?php echo $stageName === '문제해석' ? 'active' : ''; ?>" 
                            data-stage="<?php echo htmlspecialchars($stageName); ?>"
                            onclick="selectStageTab('<?php echo htmlspecialchars($stageName); ?>')">
                        <span class="stage-tab-icon"><?php echo $stageIcons[$stageName]; ?></span>
                        <span class="stage-tab-name"><?php echo $stageNum; ?>. <?php echo htmlspecialchars($stageName); ?></span>
                    </button>
                    <?php $stageNum++; endforeach; ?>
                </div>
                
                <!-- 페르소나 카드 그리드 (JavaScript로 동적 생성) -->
                <div id="stagePersonaGrid" class="stage-persona-grid">
                    <!-- JavaScript에서 현재 단계에 맞는 페르소나 카드 렌더링 -->
                    <p class="loading-text">페르소나 로딩 중...</p>
                </div>
                
                <!-- 전체 페르소나 보기 링크 -->
                <div class="persona-all-link">
                    <a href="math-persona-system.php" target="_blank" class="view-all-btn">
                        📚 60개 전체 인지 페르소나 도감 보기 →
                    </a>
                </div>
            </div>
        </header>
        
        <!-- AI 피드백 배너 (감정 글머리 스타일) -->
        <div id="aiFeedback" class="ai-feedback hidden">
            <span id="feedbackEmotion" class="feedback-emotion">😐</span>
            <span id="feedbackText" class="feedback-text"></span>
        </div>
        
        <!-- 메인 영역 -->
        <div class="main-container">
            <!-- 좌측: 화이트보드 -->
            <main class="whiteboard-area">
                <!-- 화이트보드 iframe -->
                <div class="whiteboard-container">
                    <iframe 
                        id="whiteboardFrame"
                        src="<?php echo htmlspecialchars($whiteboardUrl); ?>"
                        class="whiteboard-iframe"
                        title="화이트보드"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope"
                    ></iframe>
                    
                    <!-- 화이트보드 로딩 오버레이 (로딩 중에만 표시) -->
                    <div id="whiteboardOverlay" class="whiteboard-overlay" style="display: none;">
                        <div class="overlay-content">
                            <div class="overlay-icon">⏳</div>
                            <p class="overlay-title">화이트보드 로딩 중...</p>
                            <p class="overlay-desc">잠시만 기다려주세요</p>
                        </div>
                    </div>
                </div>
                
                <!-- 하단: 펜 제스처 -->
                <div class="gesture-area-bottom">
                    <div class="gesture-box" id="gestureBox">
                        <div class="gesture-box-header">
                            <span class="gesture-title">✏️ 제스처</span>
                            <div class="gesture-hints">
                                <span class="hint-item" title="이해했어">✓</span>
                                <span class="hint-item" title="아니야">✗</span>
                                <span class="hint-item" title="모르겠어">?</span>
                                <span class="hint-item" title="확인해줘">○</span>
                            </div>
                        </div>
                        <div class="gesture-container">
                            <canvas 
                                id="gestureCanvas" 
                                width="150" 
                                height="150"
                            ></canvas>
                        </div>
                        <div id="gestureLabel" class="gesture-label hidden"></div>
                    </div>
                </div>
            </main>
            
            <!-- 우측: 풀이 단계 / AI 채팅 -->
            <aside class="steps-sidebar">
                <div class="steps-header">
                    <h3 id="sidebarTitle">풀이 단계</h3>
                    <button id="chatToggleBtn" class="chat-toggle-btn" onclick="toggleSidebarChat()">
                        <span class="btn-icon">💬</span>
                        <span id="chatToggleLabel">AI 튜터</span>
                    </button>
                </div>
                
                <!-- 풀이 단계 콘텐츠 -->
                <div id="stepsContent" class="steps-content">
                    <div id="stepsList" class="steps-list">
                        <!-- JavaScript로 동적 생성 (5단계 아래에 장기기억 카운터 포함) -->
                    </div>
                </div>
                
                <!-- 사이드바 내장 채팅 인터페이스 -->
                <div id="sidebarChatContainer" class="sidebar-chat-container">
                    <div class="sidebar-chat-header">
                        <div class="chat-avatar">🎓</div>
                        <div class="chat-info">
                            <div class="chat-name">AI 튜터</div>
                            <div class="chat-status">함께 공부 중</div>
                        </div>
                        <button class="close-chat-btn" onclick="toggleSidebarChat()">×</button>
                    </div>
                    
                    <div id="sidebarChatMessages" class="sidebar-chat-messages">
                        <!-- 채팅 메시지 동적 생성 -->
                    </div>
                    
                    <div class="sidebar-chat-input">
                        <div class="sidebar-chat-input-hint">
                            위 <em>버튼</em>을 눌러 대답해주세요
                        </div>
                    </div>
                </div>
                
                <!-- 현재 단계 추천 페르소나 섹션 -->
                <div class="recommended-persona-section" id="recommendedPersonaSection">
                    <div class="persona-recommend-header">
                        <span class="recommend-badge">📊 AI 추천</span>
                        <span id="currentStepName" class="current-step-name">문제해석</span>
                    </div>
                    
                    <!-- 추천 페르소나 카드 (축약) -->
                    <div class="persona-recommend-card" id="recommendedPersonaCard">
                        <div class="persona-card-main">
                            <span id="recommendedPersonaIcon" class="persona-recommend-icon">👁️</span>
                            <div class="persona-recommend-info">
                                <span id="recommendedPersonaName" class="persona-recommend-name">조건 회피-추론 생략형</span>
                                <span id="recommendedPersonaCategory" class="persona-recommend-category">검증/확인 부재</span>
                            </div>
                            <span id="recommendedPersonaPriority" class="persona-priority-badge high">중요</span>
                        </div>
                        <button class="persona-detail-toggle" id="personaDetailToggle" onclick="openPersonaDetailModal()">
                            <span>자세히 보기</span>
                            <span class="toggle-arrow">→</span>
                        </button>
                    </div>
                </div>
                
                <!-- 진행률 -->
                <div class="progress-section">
                    <div class="progress-header">
                        <span>진행률</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="progress-bar">
                        <div id="progressFill" class="progress-fill"></div>
                    </div>
                </div>
                
                <!-- AI 분석 및 TTS 생성 버튼 -->
                <div class="sidebar-action-buttons">
                    <button id="aiAnalysisBtn" class="sidebar-action-btn analysis-btn <?php echo $analysisResult ? 'completed' : ($canAnalyze ? '' : 'disabled'); ?>" 
                            onclick="startAiAnalysis()" 
                            <?php echo (!$canAnalyze && !$analysisResult) ? 'disabled' : ''; ?>>
                        <span class="btn-icon" id="aiAnalysisBtnIcon"><?php echo $analysisResult ? '✅' : '🔬'; ?></span>
                        <span class="btn-text" id="aiAnalysisBtnText"><?php echo $analysisResult ? 'AI 분석 완료' : 'AI 분석'; ?></span>
                        <span id="aiAnalysisSpinner" class="spinner hidden"></span>
                    </button>
                    <button id="ttsGenerateBtn" class="sidebar-action-btn tts-btn" onclick="handleTtsButtonClick()">
                        <span class="btn-icon" id="ttsBtnIcon">🔊</span>
                        <span class="btn-text" id="ttsBtnText">TTS 생성</span>
                        <span id="ttsSpinner" class="spinner hidden"></span>
                    </button>
                </div>
                
                <!-- 장기기억 도달 시 집중숙련 추천 (숨김 상태로 시작) -->
                <div id="masterySection" class="mastery-section hidden">
                    <div class="mastery-header">
                        <span class="mastery-icon">🏆</span>
                        <h4>장기기억 달성!</h4>
                    </div>
                    <p class="mastery-desc">집중숙련으로 더 확실하게 기억하세요</p>
                    
                    <div id="masteryRecommendations" class="mastery-recommendations">
                        <?php if (!empty($masteryRecommendations)): ?>
                            <?php foreach ($masteryRecommendations as $rec): ?>
                            <div class="mastery-item <?php echo $rec['completed'] ? 'completed' : ''; ?>" 
                                 data-id="<?php echo $rec['id']; ?>"
                                 onclick="showMasteryDetail(<?php echo $rec['id']; ?>)">
                                <span class="mastery-check"><?php echo $rec['completed'] ? '✅' : '⬜'; ?></span>
                                <div class="mastery-content">
                                    <span class="mastery-concept"><?php echo htmlspecialchars($rec['concept']); ?></span>
                                    <span class="mastery-importance <?php echo $rec['importance']; ?>"><?php echo strtoupper($rec['importance']); ?></span>
                                </div>
                                <span class="mastery-arrow">→</span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="mastery-loading">분석 결과를 불러오는 중...</p>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
            
            <!-- 집중숙련 상세 모달 -->
            <div id="masteryModal" class="mastery-modal hidden">
                <div class="mastery-modal-content">
                    <div class="mastery-modal-header">
                        <h3 id="masteryModalTitle">집중숙련</h3>
                        <button class="mastery-modal-close" onclick="closeMasteryModal()">×</button>
                    </div>
                    <div class="mastery-modal-body">
                        <div class="mastery-concept-display">
                            <span class="mastery-label">📝 핵심 개념</span>
                            <p id="masteryModalConcept"></p>
                        </div>
                        
                        <div class="mastery-practice-area">
                            <span class="mastery-label">✍️ 반복필기 내용</span>
                            <div id="masteryPracticeContent" class="practice-content-box"></div>
                        </div>
                        
                        <div class="mastery-repetition">
                            <span class="mastery-label">🔄 반복 횟수</span>
                            <div class="repetition-counter">
                                <span id="masteryRepCompleted">0</span> / <span id="masteryRepTarget">3</span>
                            </div>
                        </div>
                        
                        <div class="mastery-writing-area">
                            <span class="mastery-label">📋 직접 필기해보세요</span>
                            <canvas id="masteryCanvas" width="400" height="200"></canvas>
                            <div class="mastery-canvas-controls">
                                <button onclick="clearMasteryCanvas()">지우기</button>
                                <button onclick="completeMasteryRep()">완료 ✓</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 페르소나 상세 모달 (슬라이드) -->
            <div id="personaDetailOverlay" class="persona-detail-overlay hidden" onclick="closePersonaDetailModal()"></div>
            <div id="personaDetailModal" class="persona-detail-modal hidden">
                <div class="persona-modal-header">
                    <div class="modal-header-left">
                        <span id="modalPersonaIcon" class="modal-persona-icon">👁️</span>
                        <div class="modal-persona-info">
                            <h2 id="modalPersonaName" class="modal-persona-name">조건 회피-추론 생략형</h2>
                            <span id="modalPersonaCategory" class="modal-persona-category">검증/확인 부재</span>
                        </div>
                    </div>
                    <div class="modal-header-right">
                        <span id="modalPersonaPriority" class="modal-priority-badge high">중요</span>
                        <button class="modal-close-btn" onclick="closePersonaDetailModal()">×</button>
                    </div>
                </div>
                
                <div class="persona-modal-body">
                    <!-- 현재 단계 표시 -->
                    <div class="modal-step-badge">
                        <span class="step-icon">📍</span>
                        <span id="modalCurrentStep">문제해석</span> 단계에서 추천
                    </div>
                    
                    <!-- 상세 설명 -->
                    <div class="modal-section">
                        <div class="modal-section-label">📖 상세 설명</div>
                        <div class="modal-section-content">
                            <p id="modalPersonaDesc" class="modal-desc-text">복잡한 조건을 '시야 밖'으로 밀어두고 직감만으로 추론을 강행하는 패턴입니다. 문제의 조건을 꼼꼼히 읽고 하나씩 체크하는 습관이 필요합니다.</p>
                        </div>
                    </div>
                    
                    <!-- 음성 컨텐츠 -->
                    <div class="modal-section">
                        <div class="modal-section-label">🔊 이 페르소나 정복하는 방법</div>
                        <div class="modal-audio-player" id="modalAudioPlayer">
                            <!-- AI 비주얼라이저 -->
                            <div class="modal-ai-visualizer" id="modalAiVisualizer">
                                <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                                <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                                <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                                <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                                <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                                <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                                <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                            </div>
                            <!-- 프로그레스 바 -->
                            <div class="modal-audio-progress-container">
                                <div class="modal-audio-progress-bar" id="modalAudioProgressBar">
                                    <div class="modal-audio-progress-fill" id="modalAudioProgressFill"></div>
                                </div>
                            </div>
                            <!-- 컨트롤 -->
                            <div class="modal-audio-controls">
                                <span class="modal-audio-time" id="modalAudioTime">0:00 / 0:00</span>
                                <button class="modal-audio-play-btn" id="modalAudioPlayBtn" onclick="toggleModalAudio()">▶</button>
                            </div>
                            <!-- 에러 메시지 -->
                            <div class="modal-audio-error" id="modalAudioError"></div>
                        </div>
                        <audio id="modalAudioElement"></audio>
                    </div>
                    
                    <!-- 극복 상태 입력 -->
                    <div class="modal-section">
                        <div class="modal-section-label">✍️ 나의 극복 상태</div>
                        <div class="modal-overcome-form">
                            <div class="overcome-level-row">
                                <span class="overcome-level-label">현재 상태:</span>
                                <div class="overcome-level-buttons" id="overcomeLevelBtns">
                                    <button class="overcome-level-btn" data-level="1" onclick="setOvercomeLevel(1)" title="시작 전">
                                        <span class="level-emoji">😰</span>
                                        <span class="level-text">시작 전</span>
                                    </button>
                                    <button class="overcome-level-btn" data-level="2" onclick="setOvercomeLevel(2)" title="인식함">
                                        <span class="level-emoji">🤔</span>
                                        <span class="level-text">인식함</span>
                                    </button>
                                    <button class="overcome-level-btn" data-level="3" onclick="setOvercomeLevel(3)" title="노력 중">
                                        <span class="level-emoji">💪</span>
                                        <span class="level-text">노력 중</span>
                                    </button>
                                    <button class="overcome-level-btn" data-level="4" onclick="setOvercomeLevel(4)" title="개선됨">
                                        <span class="level-emoji">😊</span>
                                        <span class="level-text">개선됨</span>
                                    </button>
                                    <button class="overcome-level-btn" data-level="5" onclick="setOvercomeLevel(5)" title="극복 완료">
                                        <span class="level-emoji">🌟</span>
                                        <span class="level-text">극복!</span>
                                    </button>
                                </div>
                            </div>
                            <textarea id="overcomeNotes" class="modal-overcome-notes" placeholder="극복 과정이나 느낀 점을 적어보세요... (예: 조급해지려 할 때 심호흡을 했더니 차분해졌어요)" rows="3"></textarea>
                            <button class="modal-save-btn" onclick="saveOvercomeStatus()">
                                <span>💾</span>
                                <span>저장하기</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- 극복 히스토리 -->
                    <div class="modal-section">
                        <div class="modal-section-label">📈 나의 극복 기록</div>
                        <div id="overcomeHistory" class="modal-overcome-history">
                            <p class="history-empty-text">아직 기록이 없습니다. 첫 번째 기록을 남겨보세요!</p>
                        </div>
                    </div>
                </div>
                
                <div class="persona-modal-footer">
                    <a href="math-persona-system.php" target="_blank" class="modal-footer-btn secondary">
                        📚 전체 60개 페르소나 보기
                    </a>
                    <button class="modal-footer-btn primary" onclick="closePersonaDetailModal()">
                        확인
                    </button>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- 분석 데이터 및 페르소나 전달 -->
    <script>
        window.ANALYSIS_DATA = <?php echo json_encode($analysisResult ?? [], JSON_UNESCAPED_UNICODE); ?>;
        window.STUDENT_ID = <?php echo json_encode($studentId); ?>;
        window.ANALYSIS_ID = <?php echo json_encode($analysisId); ?>;
        window.WBOARD_ID = <?php echo json_encode($analysisId); ?>;
        window.CONTENT_ID = <?php echo json_encode($contentId); ?>;
        window.WHITEBOARD_ID = <?php echo json_encode($whiteboardId); ?>;
        window.WHITEBOARD_URL = <?php echo json_encode($whiteboardUrl); ?>;
        
        // 문제/해설 이미지 URL
        window.QUESTION_IMAGE = <?php echo json_encode($imgSrc2); ?>;
        window.SOLUTION_IMAGE = <?php echo json_encode($imgSrc1); ?>;
        
        // 페르소나 시스템 데이터
        window.BASE_PERSONAS = <?php echo json_encode($basePersonas, JSON_UNESCAPED_UNICODE); ?>;
        window.PROBLEM_ITEMS = <?php echo json_encode($problemItems, JSON_UNESCAPED_UNICODE); ?>;
        
        // 풀이 단계별 페르소나 ID 매핑
        window.SOLVING_STAGES = <?php echo json_encode($solvingStages, JSON_UNESCAPED_UNICODE); ?>;
        
        // 60개 인지 페르소나 데이터 (math-persona-system.php와 동일)
        window.PERSONAS_60 = [
            {id:1,name:"아이디어 해방 자동발화형",desc:"번쩍이는 아이디어가 떠오르면 검증 없이 바로 써 내려가 결국 오답을 양산하는 패턴.",category:"인지 과부하",icon:"🧠",priority:"high"},
            {id:2,name:"3초 패배 예감형",desc:"'못 풀 것 같다'는 느낌이 3초 만에 뇌를 잠그고, 관련 개념 연결이 끊어지는 패턴.",category:"자신감 왜곡",icon:"😰",priority:"high"},
            {id:3,name:"과신-시야 협착형",desc:"과한 자신감으로 숫자·기호의 미세한 차이를 인식하지 못하는 패턴.",category:"자신감 왜곡",icon:"🎯",priority:"medium"},
            {id:4,name:"무의식 연쇄 실수형",desc:"손이 먼저 움직여 사소한 계산 실수가 꼬리를 무는 패턴.",category:"실수 패턴",icon:"⚡",priority:"high"},
            {id:5,name:"모순 확신-답불가형",desc:"'틀린 곳이 없다'는 집착으로 시야가 좁아져 교정을 못 하는 패턴.",category:"자신감 왜곡",icon:"🔒",priority:"medium"},
            {id:6,name:"작업기억 ⅔ 할당형",desc:"다음 일정·잡생각이 머릿속을 스치며 2/3만 집중하는 패턴.",category:"인지 과부하",icon:"🧩",priority:"high"},
            {id:7,name:"반(半)포기 창의 탐색형",desc:"'어차피 틀릴 것'이라며 낮은 확률의 창의 풀이만 헤매는 패턴.",category:"접근 전략 오류",icon:"🎨",priority:"medium"},
            {id:8,name:"해설지-혼합 착각형",desc:"내 생각과 해설 내용을 섞어 쓰다 근거가 뒤섞이는 패턴.",category:"학습 습관",icon:"📖",priority:"medium"},
            {id:9,name:"연습 회피 관성형",desc:"'이해했어' 착각으로 반복 연습을 건너뛰고 넘어가는 패턴.",category:"학습 습관",icon:"🏃",priority:"high"},
            {id:10,name:"불확실 강행형",desc:"근거 부족인데도 '일단 적용'해서 오류가 연쇄되는 패턴.",category:"접근 전략 오류",icon:"🎲",priority:"medium"},
            {id:11,name:"속도 압박 억제형",desc:"시험 시간이 눈에 들어올 때마다 '빨리 해야 한다'는 압박이 새 아이디어와 기억을 눌러 버리는 패턴.",category:"시간/압박 관리",icon:"⏰",priority:"high"},
            {id:12,name:"시험 트라우마 악수형",desc:"과거에 시험을 망친 기억이 문제 순서·전략에 투영돼 '악수'를 두는 패턴.",category:"시간/압박 관리",icon:"💔",priority:"high"},
            {id:13,name:"징검다리 난도적형",desc:"청킹 없이 산발적으로 추론해 전역 구조를 놓치는 패턴.",category:"접근 전략 오류",icon:"🪨",priority:"medium"},
            {id:14,name:"무의식 재현 루프형",desc:"예전에 성공했던 공식을 맹목적으로 재사용하며 문제 특성을 무시하는 패턴.",category:"학습 습관",icon:"🔄",priority:"low"},
            {id:15,name:"조건 회피-추론 생략형",desc:"복잡한 조건을 '시야 밖'으로 밀어두고 직감만으로 추론을 강행하는 패턴.",category:"검증/확인 부재",icon:"👁️",priority:"high"},
            {id:16,name:"확률적 답안 던지기형",desc:"근거가 부족한데도 '일단 찍어보자' 식으로 답을 기입해 오류가 연쇄되는 패턴.",category:"접근 전략 오류",icon:"🎯",priority:"medium"},
            {id:17,name:"방심 단기 기억 증발형",desc:"잠깐 산만해지면서 방금 세운 관계식이나 조건을 잊어버리는 패턴.",category:"기타 장애",icon:"💭",priority:"low"},
            {id:18,name:"도구 의존 과적형",desc:"CAS·계산기에 과도하게 의존해 개념 이해·추론 회로가 비활성화되는 패턴.",category:"기타 장애",icon:"🔧",priority:"low"},
            {id:19,name:"과거 방식 고착형",desc:"새로운 유형도 과거에 익숙했던 공식·방법만 고집하는 패턴.",category:"학습 습관",icon:"📚",priority:"medium"},
            {id:20,name:"불완전 개념 종결형",desc:"정의·조건을 끝까지 읽지 않고 '충분해'라고 판단해 풀이를 서둘러 종결하는 패턴.",category:"검증/확인 부재",icon:"✂️",priority:"high"},
            {id:21,name:"피로-오답 포용형",desc:"체력이 떨어질수록 오류 감지력이 급감해 '이 정도면 됐겠지' 하고 넘어가는 패턴.",category:"기타 장애",icon:"😴",priority:"medium"},
            {id:22,name:"감정 전염 스트레스형",desc:"옆 친구·교사 표정 / 소음에 불안이 증폭돼 작업기억 용량이 급락하는 패턴.",category:"기타 장애",icon:"😟",priority:"medium"},
            {id:23,name:"과다 정보 섭취형",desc:"한 문제를 풀며 해설·영상·블로그 등 여러 자료를 동시에 열어 인지 부하가 폭발하는 패턴.",category:"인지 과부하",icon:"📱",priority:"medium"},
            {id:24,name:"이론-연산 전도형",desc:"개념 증명·이론에 깊게 몰입하다가 정작 필수 계산(연산)을 뒤로 밀어 실수를 유발하는 패턴.",category:"접근 전략 오류",icon:"🔢",priority:"low"},
            {id:25,name:"단일 예시 착시형",desc:"특정 예제에서 성공한 방식을 새 문제에 그대로 적용해 예외 상황을 놓치는 패턴.",category:"학습 습관",icon:"🔍",priority:"medium"},
            {id:26,name:"시간 왜곡 긴장형",desc:"제한 시간을 실제보다 덜/더 급하게 느껴 불필요한 조급함·지연을 만드는 패턴.",category:"시간/압박 관리",icon:"⏳",priority:"medium"},
            {id:27,name:"보상 심리 도박형",desc:"앞선 실수를 만회하려는 집착으로 복잡한(때론 불필요한) 해법을 억지로 채택하는 패턴.",category:"기타 장애",icon:"🎰",priority:"medium"},
            {id:28,name:"공간-시각 혼선형",desc:"도형·그래프·좌표를 머릿속에 잘못 배치해 관계를 뒤집어 버리는 패턴.",category:"실수 패턴",icon:"📐",priority:"medium"},
            {id:29,name:"자기긍정 과열형",desc:"'이건 내가 잘하던 유형'이라는 자기암시로 검산·근거 검토를 생략하는 패턴.",category:"자신감 왜곡",icon:"💪",priority:"low"},
            {id:30,name:"메타인지 고갈형",desc:"문제 진행 중 '내가 뭘 모르는지' 평가 기능이 고갈돼 학습이 무의식적 반복으로 변하는 패턴.",category:"기타 장애",icon:"🎯",priority:"medium"},
            {id:31,name:"개념-용어 혼동형",desc:"정의·기호를 모호하게 기억해 비슷한 단어와 혼동, 조건 매칭에 실패하는 패턴.",category:"검증/확인 부재",icon:"🏷️",priority:"medium"},
            {id:32,name:"역추적 단절형",desc:"답을 먼저 보고 거꾸로 이유를 찾다 논리 사다리가 중간에서 끊기는 패턴.",category:"접근 전략 오류",icon:"⬆️",priority:"medium"},
            {id:33,name:"사다리 건너뛰기형",desc:"중간 논증을 생략하고 결론으로 직행, 근거 빈칸을 스스로 인식하지 못하는 패턴.",category:"접근 전략 오류",icon:"🪜",priority:"high"},
            {id:34,name:"조건 재정렬 미흡형",desc:"복합 조건의 순서를 무시해 필수·보조 정보를 혼선시키는 패턴.",category:"검증/확인 부재",icon:"📋",priority:"medium"},
            {id:35,name:"공식 암기 과신형",desc:"문제 특성과 무관하게 외운 공식만 기계적으로 대입, 오적용 위험이 큰 패턴.",category:"학습 습관",icon:"📖",priority:"medium"},
            {id:36,name:"근사치 타협형",desc:"'대략 맞겠지' 하고 근사 계산으로 풀이를 종료, 오차 검증을 생략하는 패턴.",category:"검증/확인 부재",icon:"≈",priority:"low"},
            {id:37,name:"개념-문제 불일치 간과형",desc:"문제에서 요구하는 개념과 다른 영역 해법을 고집해 방향이 어긋나는 패턴.",category:"접근 전략 오류",icon:"🎭",priority:"medium"},
            {id:38,name:"단위 무시형",desc:"길이·각도·π 변환 등 단위 체크를 생략해 결과가 엇갈리는 패턴.",category:"실수 패턴",icon:"📏",priority:"high"},
            {id:39,name:"시각화 회피형",desc:"그래프·도형 그리기를 귀찮아해 공간적 관계를 착시·오독하는 패턴.",category:"실수 패턴",icon:"📊",priority:"medium"},
            {id:40,name:"메모 불능 기억 과신형",desc:"'머릿속에 다 있어'라며 메모 없이 진행, 항목 순서가 뒤섞이는 패턴.",category:"기타 장애",icon:"🧠",priority:"medium"},
            {id:41,name:"지식-실행 단절형",desc:"개념은 이해했지만 문제 적용 단계에서 멈칫해 '알아도 못 푸는' 상황이 반복되는 패턴.",category:"학습 습관",icon:"🔗",priority:"high"},
            {id:42,name:"노이즈 필터 실패형",desc:"지문 속 중요치 않은 숫자·문장이 작업기억을 점유해 핵심 정보를 덮어버리는 패턴.",category:"인지 과부하",icon:"🔇",priority:"medium"},
            {id:43,name:"인터럽트 리셋 불능형",desc:"알림·대화 등 외부 방해 후 이전 맥락을 복구하지 못해 흐름이 끊기는 패턴.",category:"기타 장애",icon:"🔄",priority:"medium"},
            {id:44,name:"감정 보상 과다형",desc:"작은 성공에 과도한 도파민 보상이 발생해 주의력이 이완되고 다음 단계가 느슨해지는 패턴.",category:"기타 장애",icon:"🎉",priority:"low"},
            {id:45,name:"휴식 부족 저하형",desc:"장시간 집중 후 인지 피로가 누적돼 오류 검출률이 급락하는 패턴.",category:"기타 장애",icon:"😪",priority:"high"},
            {id:46,name:"전환 비용 과소평가형",desc:"여러 문제·풀이법을 빈번히 바꾸며 작업기억을 재로딩, 집중 에너지를 낭비하는 패턴.",category:"시간/압박 관리",icon:"💱",priority:"medium"},
            {id:47,name:"반례 무시형",desc:"풀이가 순조로우면 '예외 없겠지'라며 반례 검증을 생략하는 패턴.",category:"검증/확인 부재",icon:"❌",priority:"high"},
            {id:48,name:"관성적 읽기 스킵형",desc:"익숙해 보이는 문제라 생각해 지문의 끝을 읽지 않고 풀이를 시작하는 패턴.",category:"실수 패턴",icon:"⏭️",priority:"medium"},
            {id:49,name:"조건 재해석 과잉형",desc:"애매한 문구를 자의적으로 해석해 핵심 의미를 빗나가는 패턴.",category:"검증/확인 부재",icon:"🔮",priority:"medium"},
            {id:50,name:"단계 통합 과속형",desc:"두세 단계를 한 줄로 압축해 적으면서 오류 추적이 불가능해지는 패턴.",category:"실수 패턴",icon:"🏃‍♂️",priority:"medium"},
            {id:51,name:"중간점검 생략형",desc:"풀이가 절반쯤 진행됐을 때 검산 없이 끝까지 돌진, 오류를 초기에 놓치는 패턴.",category:"검증/확인 부재",icon:"⏸️",priority:"high"},
            {id:52,name:"검산 회피형",desc:"시간 아까워 검산을 건너뛰어 정답률이 흔들리는 패턴.",category:"검증/확인 부재",icon:"🚫",priority:"high"},
            {id:53,name:"계산 체계 혼합형",desc:"분수↔소수, 라디안↔도 등 단위를 혼용하다 값이 뒤섞이는 패턴.",category:"실수 패턴",icon:"🔀",priority:"medium"},
            {id:54,name:"음운 혼동형",desc:"'sine'↔'sign', 'root'↔'route' 등 비슷한 발음을 착각해 기호·용어를 바꾸는 패턴.",category:"실수 패턴",icon:"🗣️",priority:"low"},
            {id:55,name:"참조 프레임 불일치형",desc:"좌표 원점·축 방향 전환을 놓쳐 그래프·변수를 잘못 배치하는 패턴.",category:"실수 패턴",icon:"🧭",priority:"medium"},
            {id:56,name:"전략 중복 추적 피로형",desc:"동시에 3가지 이상 풀이를 전개하다 작업기억이 분산-탈진하는 패턴.",category:"인지 과부하",icon:"🤹",priority:"medium"},
            {id:57,name:"목표-행동 단절형",desc:"'개념 학습'이 '풀이 수집'으로 변질돼 원래 목표를 잊는 패턴.",category:"학습 습관",icon:"🎯",priority:"high"},
            {id:58,name:"피드백 과민형",desc:"작은 지적에도 불안이 급등해 작업기억 용량이 급락하는 패턴.",category:"기타 장애",icon:"😣",priority:"medium"},
            {id:59,name:"다중 문제 스위칭 과부하형",desc:"시험 직전에 여러 문제를 빠르게 훑다 인지 세트업이 실패하는 패턴.",category:"시간/압박 관리",icon:"📚",priority:"high"},
            {id:60,name:"자기평가 누적 오류형",desc:"진행 중 정확도 추정이 계속 어긋나 자기효능감이 왜곡되는 패턴.",category:"기타 장애",icon:"📊",priority:"medium"}
        ];
        
        // 집중숙련 추천 데이터
        window.MASTERY_RECOMMENDATIONS = <?php echo json_encode($masteryRecommendations, JSON_UNESCAPED_UNICODE); ?>;
        window.NEEDS_ANALYSIS = <?php echo json_encode($needsAnalysis); ?>;
        window.CAN_ANALYZE = <?php echo json_encode($canAnalyze); ?>;
        window.HAS_ANALYSIS = <?php echo json_encode($analysisResult !== null); ?>;
        
        // TTS 생성용 데이터
        window.TTS_CONFIG = {
            studentId: <?php echo json_encode($studentId); ?>,
            contentId: <?php echo json_encode($contentId); ?>,
            contentsType: <?php echo json_encode($contentsType); ?>,
            analysisId: <?php echo json_encode($analysisId); ?>,
            whiteboardId: <?php echo json_encode($whiteboardId); ?>,
            questionImage: <?php echo json_encode($imgSrc2); ?>,
            solutionImage: <?php echo json_encode($imgSrc1); ?>,
            apiUrl: '/moodle/local/augmented_teacher/alt42/teachingsupport/api/',
            sectionDataUrl: '/moodle/local/augmented_teacher/alt42/teachingsupport/get_interaction_data.php',
            existingTtsId: <?php echo json_encode($existingTtsId); ?>,
            existingAudioUrl: <?php echo json_encode($existingAudioUrl); ?>,
            hasTts: <?php echo json_encode($existingTtsId !== null && $existingAudioUrl !== null); ?>
        };
    </script>
    
    <!-- Step-by-Step TTS Player Styles -->
    <link rel="stylesheet" href="/moodle/local/augmented_teacher/alt42/teachingsupport/css/step_player_modal.css">
    
    <script src="learning_interface.js"></script>
    
    <!-- Step-by-Step TTS Player Modal Component -->
    <?php
    define('MOODLE_INTERNAL', true);
    require_once(__DIR__ . '/../../components/step_player_modal.php');
    ?>
    
    <!-- Step-by-Step TTS Player Script -->
    <script src="/moodle/local/augmented_teacher/alt42/teachingsupport/js/step_player.js"></script>
    
    <!-- AI Tutor Chat Interface -->
    <?php include(__DIR__ . '/chat_interface.php'); ?>
    
    <!-- AI Tutor Integration Script -->
    <script src="tutor_integration.js"></script>
    
    <script>
    // AI 튜터 초기화
    document.addEventListener('DOMContentLoaded', function() {
        AITutor.init({
            studentId: <?php echo json_encode($studentId); ?>,
            contentId: <?php echo json_encode($contentId); ?>,
            unitName: '<?php echo isset($unitName) ? addslashes($unitName) : "수학"; ?>',
            debugMode: true
        });
    });
    </script>
</body>
</html>

