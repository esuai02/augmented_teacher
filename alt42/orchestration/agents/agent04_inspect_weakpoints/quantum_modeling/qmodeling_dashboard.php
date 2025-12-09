<?php
/**
 * Quantum Modeling Dashboard
 * 양자 모델링 기반 페르소나 상태 조망 대시보드
 *
 * @package AugmentedTeacher\Agent04\QuantumModeling
 * @version 1.0.0
 * @since 2025-12-06
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(__DIR__ . '/QuantumPersonaEngine.php');
require_once(__DIR__ . '/PersonaContextSimulator.php');
require_once(__DIR__ . '/HybridStateStabilizer.php');

// 현재 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid=? AND fieldid=22", [$USER->id]);
$role = $userrole->data ?? 'student';

// 학생 목록 조회 (교사인 경우)
$students = [];
if ($role === 'teacher' || $role === 'admin') {
    $students = $DB->get_records_sql("
        SELECT u.id, u.firstname, u.lastname, u.email 
        FROM {user} u 
        WHERE u.deleted = 0 AND u.suspended = 0 
        ORDER BY u.lastname, u.firstname 
        LIMIT 100
    ");
}

// 선택된 학생 ID
$selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $USER->id;

// 양자 엔진 초기화
$quantumEngine = new QuantumPersonaEngine($selectedUserId);

// 페르소나 컨텍스트 시뮬레이터 초기화
$contextSimulator = new PersonaContextSimulator();

// 선택된 상황 코드
$selectedContextCode = $_GET['context_code'] ?? 'G1';
$userMessage = $_GET['user_message'] ?? '';

// 상황별 페르소나 시뮬레이션
$contextSimResult = null;
if (!empty($userMessage)) {
    $contextSimResult = $contextSimulator->runSimulation($userMessage, [
        'goal_progress_rate' => floatval($_GET['progress_rate'] ?? 50),
        'active_goal_count' => intval($_GET['goal_count'] ?? 3),
        'emotional_state' => $_GET['emotional_state'] ?? 'neutral',
        'stagnation_days' => intval($_GET['stagnation_days'] ?? 0),
    ]);
}

// 선택된 상황의 페르소나들
$contextPersonas = $contextSimulator->getPersonasByContext($selectedContextCode);
$allContextCodes = $contextSimulator->getAllContextCodes();
$allTones = $contextSimulator->getAllTones();

// 시뮬레이션 컨텍스트 (GET 파라미터 또는 기본값)
$context = [
    'onboarding' => [
        'mbti' => $_GET['mbti'] ?? '',
        'learning_style' => $_GET['learning_style'] ?? ''
    ],
    'time_pressure' => floatval($_GET['time_pressure'] ?? 0.3),
    'fatigue' => floatval($_GET['fatigue'] ?? 0.2),
    'emotion' => floatval($_GET['emotion'] ?? 0.5),
    'resilience' => floatval($_GET['resilience'] ?? 0.6),
    'difficulty' => floatval($_GET['difficulty'] ?? 0.5),
    'elapsed' => intval($_GET['elapsed'] ?? 0)
];

// 시뮬레이션 실행
$simulation = null;
if (isset($_GET['simulate']) && $_GET['simulate'] === '1') {
    $simulation = $quantumEngine->runFullSimulation($selectedUserId, $context);
}

// 최근 상태 조회
$recentState = $quantumEngine->getRecentQuantumState($selectedUserId);
$stateHistory = $quantumEngine->getQuantumStateHistory($selectedUserId, 10);

// 엔진 정보
$engineInfo = $quantumEngine->getEngineInfo();

// 하이브리드 상태 안정화 시스템 초기화
$hybridStabilizer = new HybridStateStabilizer($selectedUserId);
$hybridState = $hybridStabilizer->getFullState();

// 하이브리드 시뮬레이션 (POST로 이벤트 전송 시)
$hybridSimResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hybrid_action'])) {
    $action = $_POST['hybrid_action'];
    
    switch ($action) {
        case 'sensor':
            $sensorData = [
                'mouse_velocity' => floatval($_POST['mouse_velocity'] ?? 0),
                'scroll_rate' => floatval($_POST['scroll_rate'] ?? 0),
                'pause_duration' => floatval($_POST['pause_duration'] ?? 0),
            ];
            $hybridSimResult = $hybridStabilizer->fastLoopPredict($sensorData);
            break;
            
        case 'event':
            $eventType = $_POST['event_type'] ?? 'page_view';
            $eventData = json_decode($_POST['event_data'] ?? '{}', true);
            $hybridSimResult = $hybridStabilizer->kalmanCorrection($eventType, $eventData);
            break;
            
        case 'ping':
            $level = intval($_POST['ping_level'] ?? 1);
            $hybridSimResult = $hybridStabilizer->firePing($level);
            break;
            
        case 'ping_response':
            $pingId = $_POST['ping_id'] ?? '';
            $responded = $_POST['responded'] === 'true';
            $responseTime = floatval($_POST['response_time'] ?? 0);
            $hybridSimResult = $hybridStabilizer->processPingResponse($pingId, $responded, $responseTime);
            break;
    }
    
    $hybridState = $hybridStabilizer->getFullState();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>양자 모델링 대시보드 | Agent04</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php include __DIR__ . '/_components/styles.php'; ?>
    <?php include __DIR__ . '/_components/persona_styles.php'; ?>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <div class="header">
            <h1>
                ⚛️ 양자 모델링 대시보드
                <span class="version">v<?php echo $engineInfo['version']; ?></span>
            </h1>
            <div style="display: flex; align-items: center; gap: 20px;">
                <!-- 뷰 모드 전환 -->
                <div class="view-mode-switch">
                    <button type="button" class="view-mode-btn active" data-mode="tab" onclick="setViewMode('tab')">
                        📑 탭뷰
                    </button>
                    <button type="button" class="view-mode-btn" data-mode="scroll" onclick="setViewMode('scroll')">
                        📜 스크롤뷰
                    </button>
                </div>
                <div>
                    <span class="status-indicator active"></span>
                    Agent04 - <?php echo $engineInfo['agent_id']; ?>
                </div>
            </div>
        </div>
        
        <!-- 메인 탭 네비게이션 -->
        <div class="main-tabs" id="mainTabs">
            <button type="button" class="main-tab active" data-tab="quantum" onclick="switchTab('quantum')">
                ⚛️ 양자 시뮬레이션
            </button>
            <button type="button" class="main-tab" data-tab="persona" onclick="switchTab('persona')">
                🎭 상황별 페르소나
            </button>
            <button type="button" class="main-tab" data-tab="message" onclick="switchTab('message')">
                💬 메시지 분석
            </button>
            <button type="button" class="main-tab" data-tab="dynamics" onclick="switchTab('dynamics')">
                📊 학습 역학
            </button>
            <button type="button" class="main-tab" data-tab="switching" onclick="switchTab('switching')">
                🛤️ 경로 & 히스토리
            </button>
            <button type="button" class="main-tab" data-tab="hybrid" onclick="switchTab('hybrid')">
                🔄 하이브리드 안정화
            </button>
        </div>
        
        <div class="dashboard-content tab-view" id="dashboardContent">
        
        <!-- TAB 1: 양자 시뮬레이션 -->
        <div class="tab-content active" data-tab-content="quantum">
        <div class="grid">
            <!-- 컨트롤 패널 -->
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🎛️ 시뮬레이션 컨트롤</div>
                    </div>
                    
                    <form method="GET" action="">
                        <?php if (!empty($students)): ?>
                        <div class="form-group">
                            <label class="form-label">학생 선택</label>
                            <select name="user_id" class="form-control">
                                <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s->id; ?>" <?php echo $s->id == $selectedUserId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s->lastname . $s->firstname); ?> (<?php echo $s->email; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label class="form-label">MBTI (선택)</label>
                            <select name="mbti" class="form-control">
                                <option value="">선택 안함</option>
                                <?php 
                                $mbtis = ['INTJ','INTP','ENTJ','ENTP','INFJ','INFP','ENFJ','ENFP',
                                          'ISTJ','ISFJ','ESTJ','ESFJ','ISTP','ISFP','ESTP','ESFP'];
                                foreach ($mbtis as $m): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($context['onboarding']['mbti'] ?? '') === $m ? 'selected' : ''; ?>>
                                    <?php echo $m; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">시간 압박도</label>
                            <div class="range-container">
                                <input type="range" name="time_pressure" class="range-slider" 
                                       min="0" max="1" step="0.1" value="<?php echo $context['time_pressure']; ?>"
                                       oninput="this.nextElementSibling.textContent = this.value">
                                <span class="range-value"><?php echo $context['time_pressure']; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">피로도</label>
                            <div class="range-container">
                                <input type="range" name="fatigue" class="range-slider" 
                                       min="0" max="1" step="0.1" value="<?php echo $context['fatigue']; ?>"
                                       oninput="this.nextElementSibling.textContent = this.value">
                                <span class="range-value"><?php echo $context['fatigue']; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">감정 상태 (0=부정, 1=긍정)</label>
                            <div class="range-container">
                                <input type="range" name="emotion" class="range-slider" 
                                       min="0" max="1" step="0.1" value="<?php echo $context['emotion']; ?>"
                                       oninput="this.nextElementSibling.textContent = this.value">
                                <span class="range-value"><?php echo $context['emotion']; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">회복 탄력성</label>
                            <div class="range-container">
                                <input type="range" name="resilience" class="range-slider" 
                                       min="0" max="1" step="0.1" value="<?php echo $context['resilience']; ?>"
                                       oninput="this.nextElementSibling.textContent = this.value">
                                <span class="range-value"><?php echo $context['resilience']; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">문제 난이도</label>
                            <div class="range-container">
                                <input type="range" name="difficulty" class="range-slider" 
                                       min="0" max="1" step="0.1" value="<?php echo $context['difficulty']; ?>"
                                       oninput="this.nextElementSibling.textContent = this.value">
                                <span class="range-value"><?php echo $context['difficulty']; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">경과 시간 (초)</label>
                            <input type="number" name="elapsed" class="form-control" 
                                   value="<?php echo $context['elapsed']; ?>" min="0" max="600">
                        </div>
                        
                        <input type="hidden" name="simulate" value="1">
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                            ⚛️ 양자 시뮬레이션 실행
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- 상태 벡터 시각화 -->
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🌊 양자 상태 벡터 (Wave Function)</div>
                        <?php if ($simulation): ?>
                        <span class="persona-badge">
                            <?php echo $simulation['measurement']['dominant_icon'] ?? ''; ?>
                            <?php echo $simulation['measurement']['dominant_name'] ?? 'Unknown'; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($simulation): ?>
                    <div class="state-vector">
                        <?php 
                        $personas = QuantumPersonaEngine::PERSONA_BASIS;
                        $probs = $simulation['measurement']['all_probabilities'] ?? [];
                        foreach ($personas as $key => $info): 
                            $prob = $probs[$key] ?? 0;
                            $class = $prob > 0.4 ? 'high' : ($prob > 0.2 ? 'medium' : 'low');
                        ?>
                        <div class="state-item">
                            <div class="state-icon"><?php echo $info['icon']; ?></div>
                            <div class="state-name"><?php echo $info['name']; ?></div>
                            <div class="state-value <?php echo $class; ?>"><?php echo round($prob * 100); ?>%</div>
                            <div class="progress-bar">
                                <div class="progress-fill synergy" style="width: <?php echo $prob * 100; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: var(--bg-dark); border-radius: 12px;">
                        <strong>상태 해석:</strong> <?php echo $simulation['measurement']['state_description'] ?? ''; ?>
                        <br><small style="color: var(--text-secondary);">
                            중첩 수준: <?php echo $simulation['measurement']['superposition_level'] ?? 'unknown'; ?>
                        </small>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 60px; color: var(--text-secondary);">
                        <div style="font-size: 3rem; margin-bottom: 15px;">⚛️</div>
                        <p>시뮬레이션을 실행하여 양자 상태를 확인하세요</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 레이더 차트 -->
                    <div class="chart-container">
                        <canvas id="personaRadar"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- 학습 역학 (시너지/역효과) -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 감쇠 진동 모델 (Learning Dynamics)</div>
                    </div>
                    
                    <?php if ($simulation): ?>
                    <div class="dynamics-grid">
                        <div class="dynamics-item">
                            <div class="dynamics-label">시너지 확률</div>
                            <div class="dynamics-value synergy"><?php echo round(($simulation['dynamics']['synergy'] ?? 0) * 100); ?>%</div>
                            <div class="progress-bar">
                                <div class="progress-fill synergy" style="width: <?php echo ($simulation['dynamics']['synergy'] ?? 0) * 100; ?>%"></div>
                            </div>
                        </div>
                        <div class="dynamics-item">
                            <div class="dynamics-label">역효과 확률</div>
                            <div class="dynamics-value backfire"><?php echo round(($simulation['dynamics']['backfire'] ?? 0) * 100); ?>%</div>
                            <div class="progress-bar">
                                <div class="progress-fill backfire" style="width: <?php echo ($simulation['dynamics']['backfire'] ?? 0) * 100; ?>%"></div>
                            </div>
                        </div>
                        <div class="dynamics-item">
                            <div class="dynamics-label">골든 타임</div>
                            <div class="dynamics-value golden"><?php echo $simulation['dynamics']['golden_time'] ?? 0; ?>초</div>
                            <small style="color: var(--text-secondary);">개입 권장 시점</small>
                        </div>
                    </div>
                    
                    <?php if ($simulation['dynamics']['should_intervene'] ?? false): ?>
                    <div class="recommendation-box critical">
                        <div class="recommendation-title">🚨 즉시 개입 필요</div>
                        <p>역효과 확률이 시너지를 초과했거나 골든 타임에 임박했습니다.</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 시간 그래프 -->
                    <div class="chart-container">
                        <canvas id="dynamicsChart"></canvas>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        시뮬레이션 후 역학 데이터가 표시됩니다
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 간섭 효과 -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🌀 양자 간섭 효과 (Interference)</div>
                    </div>
                    
                    <?php if ($simulation && isset($simulation['interference'])): 
                        $interference = $simulation['interference'];
                    ?>
                    <div class="interference-display">
                        <div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">진폭</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo round($interference['amplitude'], 2); ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">보강 계수</div>
                            <div style="font-size: 2rem; font-weight: 700;"><?php echo round($interference['constructive_factor'], 2); ?></div>
                        </div>
                        <div class="interference-type <?php echo $interference['interference_type']; ?>">
                            <?php 
                            $typeLabels = ['constructive' => '보강 간섭', 'destructive' => '상쇄 간섭', 'neutral' => '중립'];
                            echo $typeLabels[$interference['interference_type']] ?? '알 수 없음';
                            ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: var(--bg-dark); border-radius: 12px;">
                        <strong>💡 추천:</strong> <?php echo $interference['recommendation']; ?>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        시뮬레이션 후 간섭 효과가 표시됩니다
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 종합 추천 -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🎯 AI 개입 추천</div>
                    </div>
                    
                    <?php if ($simulation && isset($simulation['recommendation'])): 
                        $rec = $simulation['recommendation'];
                        $urgencyClass = $rec['urgency'] ?? 'normal';
                    ?>
                    <div class="recommendation-box <?php echo $urgencyClass; ?>">
                        <div class="recommendation-title">
                            <?php echo $urgencyClass === 'critical' ? '🚨' : ($urgencyClass === 'high' ? '⚠️' : '✅'); ?>
                            긴급도: <?php echo strtoupper($urgencyClass); ?>
                        </div>
                        <p><strong><?php echo $rec['summary'] ?? ''; ?></strong></p>
                        
                        <div style="margin-top: 15px;">
                            <?php foreach ($rec['actions'] ?? [] as $action): ?>
                            <div class="recommendation-item">• <?php echo $action; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
        <!-- END TAB 1: 양자 시뮬레이션 -->
        
        <!-- TAB 2: 상황별 페르소나 -->
        <div class="tab-content" data-tab-content="persona">
        <div class="grid">
            <?php include __DIR__ . '/_components/context_simulator_ui.php'; ?>
        </div>
        </div>
        <!-- END TAB 2 -->
        
        <!-- TAB 3: 메시지 분석 -->
        <div class="tab-content" data-tab-content="message">
        <div class="grid">
            <?php include __DIR__ . '/_components/message_simulator_ui.php'; ?>
        </div>
        </div>
        <!-- END TAB 3 -->
        
        <!-- TAB 4: 학습 역학 -->
        <div class="tab-content" data-tab-content="dynamics">
        <div class="grid">
            <?php include __DIR__ . '/_components/tone_guide_ui.php'; ?>
        </div>
        </div>
        <!-- END TAB 4 -->
        
        <!-- TAB 5: 경로 & 히스토리 -->
        <div class="tab-content" data-tab-content="switching">
        <div class="grid">
            <!-- 페르소나 스위칭 경로 시뮬레이터 -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🛤️ 페르소나 스위칭 경로</div>
                    </div>
                    
                    <form id="switchingForm">
                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">현재 페르소나</label>
                                <select id="currentPersona" class="form-control">
                                    <option value="S">⚡ Sprinter</option>
                                    <option value="D">🤿 Diver</option>
                                    <option value="G">🎮 Gamer</option>
                                    <option value="A">🏛️ Architect</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">목표 페르소나</label>
                                <select id="targetPersona" class="form-control">
                                    <option value="D">🤿 Diver</option>
                                    <option value="S">⚡ Sprinter</option>
                                    <option value="G">🎮 Gamer</option>
                                    <option value="A">🏛️ Architect</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" onclick="calculatePath()" class="btn btn-secondary">경로 계산</button>
                    </form>
                    
                    <div id="pathResult"></div>
                </div>
            </div>
            
            <!-- 상태 히스토리 -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📜 양자 상태 히스토리</div>
                    </div>
                    
                    <?php if (!empty($stateHistory)): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>시간</th>
                                <th>지배 페르소나</th>
                                <th>시너지</th>
                                <th>역효과</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stateHistory as $state): ?>
                            <tr>
                                <td><?php echo $state['created_at']; ?></td>
                                <td>
                                    <span class="persona-badge" style="font-size: 0.75rem; padding: 4px 8px;">
                                        <?php echo $state['dominant_persona']; ?>
                                    </span>
                                </td>
                                <td style="color: var(--success);"><?php echo round($state['synergy'] * 100); ?>%</td>
                                <td style="color: var(--danger);"><?php echo round($state['backfire'] * 100); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        아직 기록된 히스토리가 없습니다
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
        <!-- END TAB 5: 경로 & 히스토리 -->
        
        <!-- TAB 6: 하이브리드 상태 안정화 -->
        <div class="tab-content" data-tab-content="hybrid">
        <div class="grid">
            <?php include __DIR__ . '/_components/hybrid_stabilizer_ui.php'; ?>
        </div>
        </div>
        <!-- END TAB 6: 하이브리드 상태 안정화 -->
        
        </div><!-- END dashboard-content -->
    </div>
    
    <script>
        // ============================================================
        // 뷰 모드 전환 (탭뷰/스크롤뷰)
        // ============================================================
        
        function setViewMode(mode) {
            const content = document.getElementById('dashboardContent');
            const buttons = document.querySelectorAll('.view-mode-btn');
            const tabs = document.getElementById('mainTabs');
            
            buttons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.mode === mode) {
                    btn.classList.add('active');
                }
            });
            
            if (mode === 'scroll') {
                content.classList.remove('tab-view');
                content.classList.add('scroll-view');
                // 스크롤뷰에서는 모든 탭 컨텐츠 표시
                document.querySelectorAll('.tab-content').forEach(tc => {
                    tc.classList.add('active');
                });
            } else {
                content.classList.remove('scroll-view');
                content.classList.add('tab-view');
                // 탭뷰에서는 현재 선택된 탭만 표시
                const activeTab = document.querySelector('.main-tab.active');
                if (activeTab) {
                    switchTab(activeTab.dataset.tab);
                }
            }
            
            // 로컬 스토리지에 저장
            localStorage.setItem('qmodeling_view_mode', mode);
        }
        
        // ============================================================
        // 탭 전환
        // ============================================================
        
        function switchTab(tabId) {
            // 탭 버튼 활성화
            document.querySelectorAll('.main-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.tab === tabId) {
                    tab.classList.add('active');
                }
            });
            
            // 탭뷰 모드일 때만 컨텐츠 전환
            const content = document.getElementById('dashboardContent');
            if (content.classList.contains('tab-view')) {
                document.querySelectorAll('.tab-content').forEach(tc => {
                    tc.classList.remove('active');
                    if (tc.dataset.tabContent === tabId) {
                        tc.classList.add('active');
                    }
                });
            }
            
            // 로컬 스토리지에 저장
            localStorage.setItem('qmodeling_active_tab', tabId);
        }
        
        // 페이지 로드 시 저장된 설정 복원
        document.addEventListener('DOMContentLoaded', function() {
            const savedMode = localStorage.getItem('qmodeling_view_mode') || 'tab';
            const savedTab = localStorage.getItem('qmodeling_active_tab') || 'quantum';
            
            setViewMode(savedMode);
            if (savedMode === 'tab') {
                switchTab(savedTab);
            }
        });
        
        // ============================================================
        // 차트 및 기존 기능
        // ============================================================
        // 레이더 차트 데이터
        const radarData = <?php echo json_encode($simulation['measurement']['all_probabilities'] ?? ['S'=>0.25,'D'=>0.25,'G'=>0.25,'A'=>0.25]); ?>;
        
        // 레이더 차트 생성
        const radarCtx = document.getElementById('personaRadar').getContext('2d');
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: ['⚡ Sprinter', '🤿 Diver', '🎮 Gamer', '🏛️ Architect'],
                datasets: [{
                    label: '페르소나 확률',
                    data: [radarData.S * 100, radarData.D * 100, radarData.G * 100, radarData.A * 100],
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(99, 102, 241, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                        pointLabels: { color: '#f1f5f9', font: { size: 14 } }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
        
        // 역학 차트 (시간에 따른 시너지/역효과)
        <?php if ($simulation): ?>
        const dynamicsCtx = document.getElementById('dynamicsChart').getContext('2d');
        
        // 시간에 따른 데이터 시뮬레이션
        const timePoints = [];
        const synergyPoints = [];
        const backfirePoints = [];
        
        const resilience = <?php echo $context['resilience']; ?>;
        const difficulty = <?php echo $context['difficulty']; ?>;
        const goldenTime = <?php echo $simulation['dynamics']['golden_time'] ?? 60; ?>;
        
        for (let t = 0; t <= Math.min(goldenTime * 1.5, 120); t += 2) {
            timePoints.push(t);
            
            const omega = 2 * Math.PI * (0.1 + difficulty * 0.2);
            const gamma = 0.05 * (1.5 - resilience);
            const synergy = 0.5 * (1 + Math.cos(omega * t) * Math.exp(-gamma * t));
            const backfire = Math.min((1 - synergy) + (0.01 * t), 1);
            
            synergyPoints.push((synergy * 100).toFixed(1));
            backfirePoints.push((backfire * 100).toFixed(1));
        }
        
        new Chart(dynamicsCtx, {
            type: 'line',
            data: {
                labels: timePoints,
                datasets: [
                    {
                        label: '시너지 확률',
                        data: synergyPoints,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: '역효과 확률',
                        data: backfirePoints,
                        borderColor: 'rgba(239, 68, 68, 1)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: { display: true, text: '경과 시간 (초)', color: '#94a3b8' },
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        title: { display: true, text: '확률 (%)', color: '#94a3b8' },
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                },
                plugins: {
                    legend: { labels: { color: '#f1f5f9' } },
                    annotation: {
                        annotations: {
                            goldenLine: {
                                type: 'line',
                                xMin: goldenTime,
                                xMax: goldenTime,
                                borderColor: 'rgba(245, 158, 11, 1)',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                label: {
                                    display: true,
                                    content: '골든 타임',
                                    position: 'start'
                                }
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        // 페르소나 스위칭 경로 계산
        const transitionCosts = {
            'S': {'S': 0, 'D': 5, 'G': 1, 'A': 2},
            'D': {'S': 5, 'D': 0, 'G': 2, 'A': 1},
            'G': {'S': 1, 'D': 2, 'G': 0, 'A': 5},
            'A': {'S': 2, 'D': 1, 'G': 5, 'A': 0}
        };
        
        const personaNames = {
            'S': '⚡ Sprinter',
            'D': '🤿 Diver',
            'G': '🎮 Gamer',
            'A': '🏛️ Architect'
        };
        
        const transitions = {
            'S→G': "🎮 [도전장] '이 문제, 전교생의 80%가 틀렸어. 넌 맞출 수 있을까?'",
            'G→D': "🤿 [힌트 탐색] '이기려면 무기가 필요해. 개념노트에 숨겨진 공식을 찾아봐.'",
            'D→A': "🏛️ [조망] '이 문제는 전체 숲에서 보면 작은 나무일 뿐이야.'",
            'A→S': "⚡ [실행] '전략은 섰으니 이제 질주할 차례야. 5분 타임어택!'",
            'S→D': "🤿 [함정 찾기] '함정은 부등호 방향에 숨어 있어.'",
            'D→G': "🎮 [승부욕] '다 이해했으니, 이제 실력으로 증명할 차례야!'",
            'G→A': "🏛️ [세이브포인트] '지금까지 얻은 점수가 상위 10%야. 저장하고 갈래?'",
            'A→D': "🤿 [깊은 이해] '계획은 완벽해. 이제 왜 이렇게 되는지 파헤쳐볼까?'"
        };
        
        function dijkstra(start, end) {
            const nodes = ['S', 'D', 'G', 'A'];
            const distances = {};
            const previous = {};
            const queue = {};
            
            nodes.forEach(n => {
                distances[n] = (n === start) ? 0 : Infinity;
                previous[n] = null;
                queue[n] = distances[n];
            });
            
            while (Object.keys(queue).length > 0) {
                let current = Object.keys(queue).reduce((a, b) => queue[a] < queue[b] ? a : b);
                delete queue[current];
                
                if (current === end) break;
                
                nodes.forEach(neighbor => {
                    if (queue[neighbor] === undefined) return;
                    const cost = transitionCosts[current][neighbor];
                    const alt = distances[current] + cost;
                    if (alt < distances[neighbor]) {
                        distances[neighbor] = alt;
                        previous[neighbor] = current;
                        queue[neighbor] = alt;
                    }
                });
            }
            
            // 경로 재구성
            const path = [];
            let curr = end;
            while (curr !== null) {
                path.unshift(curr);
                curr = previous[curr];
            }
            
            return { path, cost: distances[end] };
        }
        
        function calculatePath() {
            const current = document.getElementById('currentPersona').value;
            const target = document.getElementById('targetPersona').value;
            
            if (current === target) {
                document.getElementById('pathResult').innerHTML = `
                    <div style="padding: 20px; text-align: center; color: var(--text-secondary);">
                        현재와 목표 페르소나가 같습니다.
                    </div>
                `;
                return;
            }
            
            const { path, cost } = dijkstra(current, target);
            
            let pathHtml = '<div class="switching-path">';
            path.forEach((p, i) => {
                pathHtml += `<div class="path-node">${personaNames[p]}</div>`;
                if (i < path.length - 1) {
                    pathHtml += '<span class="path-arrow">→</span>';
                }
            });
            pathHtml += '</div>';
            
            let scriptHtml = '<div style="margin-top: 20px;">';
            scriptHtml += `<p style="color: var(--text-secondary); margin-bottom: 10px;">총 비용: ${cost} | 예상 시간: ${cost * 30}초</p>`;
            
            for (let i = 0; i < path.length - 1; i++) {
                const key = `${path[i]}→${path[i+1]}`;
                if (transitions[key]) {
                    scriptHtml += `<div class="recommendation-item"><strong>Step ${i+1}:</strong> ${transitions[key]}</div>`;
                }
            }
            scriptHtml += '</div>';
            
            document.getElementById('pathResult').innerHTML = pathHtml + scriptHtml;
        }
    </script>
</body>
</html>

