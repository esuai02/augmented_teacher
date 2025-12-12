<?php
/**
 * 하이브리드 상태 안정화 시스템 UI 컴포넌트
 * Kalman Filter + Active Ping 시뮬레이터
 * 
 * @package AugmentedTeacher\Agent04\QuantumModeling\Components
 * 
 * Required variables:
 * - $hybridStabilizer: HybridStateStabilizer 인스턴스
 * - $hybridState: 현재 상태
 * - $hybridSimResult: 시뮬레이션 결과 (nullable)
 */

$hybridState = $hybridState ?? null;
$hybridSimResult = $hybridSimResult ?? null;
?>

<style>
/* 하이브리드 시스템 전용 스타일 */
.hybrid-dashboard {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 1200px) {
    .hybrid-dashboard {
        grid-template-columns: 1fr;
    }
}

.state-meter {
    position: relative;
    height: 30px;
    background: var(--bg-dark);
    border-radius: 15px;
    overflow: hidden;
    margin: 10px 0;
}

.state-meter-fill {
    height: 100%;
    border-radius: 15px;
    transition: width 0.5s ease, background 0.3s ease;
}

.state-meter-label {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: 700;
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.confidence-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border-radius: 10px;
    margin: 15px 0;
}

.confidence-indicator.high {
    background: rgba(16, 185, 129, 0.2);
    border: 1px solid var(--success);
}

.confidence-indicator.medium {
    background: rgba(245, 158, 11, 0.2);
    border: 1px solid var(--warning);
}

.confidence-indicator.low {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid var(--danger);
}

.ping-button {
    padding: 15px 25px;
    border-radius: 12px;
    border: 2px solid var(--border);
    background: var(--bg-dark);
    color: var(--text-primary);
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.ping-button:hover {
    border-color: var(--primary);
    transform: translateY(-3px);
}

.ping-button.active {
    background: var(--primary);
    border-color: var(--primary);
}

.ping-icon {
    font-size: 2rem;
}

.event-button {
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-dark);
    color: var(--text-primary);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}

.event-button:hover {
    border-color: var(--primary);
    background: rgba(99, 102, 241, 0.1);
}

.event-button.positive {
    border-color: var(--success);
}

.event-button.positive:hover {
    background: rgba(16, 185, 129, 0.2);
}

.event-button.negative {
    border-color: var(--danger);
}

.event-button.negative:hover {
    background: rgba(239, 68, 68, 0.2);
}

.kalman-visualization {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    background: var(--bg-dark);
    border-radius: 12px;
    margin: 15px 0;
}

.kalman-box {
    text-align: center;
    padding: 15px;
    border-radius: 10px;
    min-width: 100px;
}

.kalman-box.prediction {
    background: rgba(99, 102, 241, 0.2);
    border: 2px solid var(--primary);
}

.kalman-box.measurement {
    background: rgba(245, 158, 11, 0.2);
    border: 2px solid var(--warning);
}

.kalman-box.result {
    background: rgba(16, 185, 129, 0.2);
    border: 2px solid var(--success);
}

.kalman-arrow {
    font-size: 1.5rem;
    color: var(--text-secondary);
}

.kalman-gain {
    text-align: center;
    padding: 10px;
    background: var(--bg-card);
    border-radius: 8px;
}

.state-vector-bars {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.state-bar-container {
    flex: 1;
    text-align: center;
}

.state-bar {
    height: 100px;
    background: var(--bg-dark);
    border-radius: 8px;
    position: relative;
    overflow: hidden;
}

.state-bar-fill {
    position: absolute;
    bottom: 0;
    width: 100%;
    border-radius: 8px;
    transition: height 0.5s ease;
}

.state-bar-fill.focus { background: linear-gradient(to top, #10b981, #34d399); }
.state-bar-fill.flow { background: linear-gradient(to top, #6366f1, #818cf8); }
.state-bar-fill.struggle { background: linear-gradient(to top, #f59e0b, #fbbf24); }
.state-bar-fill.lost { background: linear-gradient(to top, #ef4444, #f87171); }

.simulation-log {
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    background: var(--bg-dark);
    border-radius: 8px;
    font-family: monospace;
    font-size: 0.8rem;
}

.log-entry {
    padding: 5px 10px;
    border-bottom: 1px solid var(--border);
}

.log-entry.prediction { border-left: 3px solid var(--primary); }
.log-entry.event { border-left: 3px solid var(--success); }
.log-entry.ping { border-left: 3px solid var(--warning); }
</style>

<!-- 하이브리드 상태 안정화 시스템 -->
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <div class="card-title">🔄 하이브리드 상태 안정화 시스템</div>
            <span class="persona-badge" style="background: var(--success);">Kalman + Active Ping</span>
        </div>
        
        <div class="hybrid-dashboard">
            <!-- 왼쪽: 상태 모니터 -->
            <div>
                <h4 style="margin-bottom: 15px;">📊 실시간 상태 모니터</h4>
                
                <!-- 집중도 미터 -->
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>집중도 (Predicted State)</span>
                        <span id="predictedStateValue"><?php echo round(($hybridState['predicted_state'] ?? 0.5) * 100); ?>%</span>
                    </div>
                    <div class="state-meter">
                        <div class="state-meter-fill" id="stateMeterFill" 
                             style="width: <?php echo ($hybridState['predicted_state'] ?? 0.5) * 100; ?>%; 
                                    background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981);"></div>
                        <span class="state-meter-label" id="stateMeterLabel">
                            <?php echo ucfirst($hybridState['dominant_state'] ?? 'focus'); ?>
                        </span>
                    </div>
                </div>
                
                <!-- 확신도 표시 -->
                <?php 
                $conf = $hybridState['confidence'] ?? 1.0;
                $confClass = $conf >= 0.6 ? 'high' : ($conf >= 0.3 ? 'medium' : 'low');
                ?>
                <div class="confidence-indicator <?php echo $confClass; ?>" id="confidenceIndicator">
                    <span style="font-size: 1.5rem;">
                        <?php echo $confClass === 'high' ? '✅' : ($confClass === 'medium' ? '⚠️' : '❓'); ?>
                    </span>
                    <div>
                        <strong>확신도:</strong> <span id="confidenceValue"><?php echo round($conf * 100); ?>%</span>
                        <br>
                        <small style="color: var(--text-secondary);">
                            <?php 
                            if ($confClass === 'high') echo 'AI가 학생 상태를 확신합니다';
                            elseif ($confClass === 'medium') echo '약간의 불확실성이 있습니다';
                            else echo 'Active Ping이 필요합니다!';
                            ?>
                        </small>
                    </div>
                    <div style="margin-left: auto;">
                        불확실성: <strong id="uncertaintyValue"><?php echo round(($hybridState['uncertainty'] ?? 0.1) * 100); ?>%</strong>
                    </div>
                </div>
                
                <!-- 상태 벡터 바 차트 -->
                <h5 style="margin: 20px 0 10px;">상태 분포</h5>
                <div class="state-vector-bars">
                    <?php 
                    $stateVector = $hybridState['state_vector'] ?? ['focus' => 0.5, 'flow' => 0, 'struggle' => 0, 'lost' => 0.5];
                    $stateLabels = ['focus' => '집중', 'flow' => '몰입', 'struggle' => '고군분투', 'lost' => '이탈'];
                    foreach ($stateVector as $state => $value): 
                    ?>
                    <div class="state-bar-container">
                        <div class="state-bar">
                            <div class="state-bar-fill <?php echo $state; ?>" 
                                 style="height: <?php echo $value * 100; ?>%;" 
                                 id="stateBar_<?php echo $state; ?>"></div>
                        </div>
                        <div style="margin-top: 5px; font-size: 0.75rem;">
                            <?php echo $stateLabels[$state]; ?><br>
                            <strong id="stateValue_<?php echo $state; ?>"><?php echo round($value * 100); ?>%</strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 오른쪽: 컨트롤 패널 -->
            <div>
                <h4 style="margin-bottom: 15px;">🎛️ 시뮬레이션 컨트롤</h4>
                
                <!-- Active Ping 버튼 -->
                <div style="margin-bottom: 20px;">
                    <h5 style="margin-bottom: 10px;">📡 Active Ping (능동 관측)</h5>
                    <div style="display: flex; gap: 10px;">
                        <button class="ping-button" onclick="firePing(1)" id="pingBtn1">
                            <span class="ping-icon">💡</span>
                            <span>Subtle</span>
                            <small style="color: var(--text-secondary);">미세 자극</small>
                        </button>
                        <button class="ping-button" onclick="firePing(2)" id="pingBtn2">
                            <span class="ping-icon">💬</span>
                            <span>Nudge</span>
                            <small style="color: var(--text-secondary);">넛지</small>
                        </button>
                        <button class="ping-button" onclick="firePing(3)" id="pingBtn3">
                            <span class="ping-icon">❓</span>
                            <span>Alert</span>
                            <small style="color: var(--text-secondary);">직접 질문</small>
                        </button>
                    </div>
                </div>
                
                <!-- 이벤트 시뮬레이션 -->
                <div style="margin-bottom: 20px;">
                    <h5 style="margin-bottom: 10px;">⚡ 이벤트 시뮬레이션 (Kalman Correction)</h5>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <button class="event-button positive" onclick="simulateEvent('correct_answer')">✅ 정답</button>
                        <button class="event-button positive" onclick="simulateEvent('quick_response')">⚡ 빠른 응답</button>
                        <button class="event-button positive" onclick="simulateEvent('scroll_active')">📜 스크롤</button>
                        <button class="event-button" onclick="simulateEvent('page_view')">👁️ 페이지 보기</button>
                        <button class="event-button negative" onclick="simulateEvent('hint_click')">💡 힌트 클릭</button>
                        <button class="event-button negative" onclick="simulateEvent('wrong_answer')">❌ 오답</button>
                        <button class="event-button negative" onclick="simulateEvent('skip_problem')">⏭️ 건너뛰기</button>
                        <button class="event-button negative" onclick="simulateEvent('long_pause')">⏸️ 긴 멈춤</button>
                    </div>
                </div>
                
                <!-- Fast Loop 시뮬레이션 -->
                <div style="margin-bottom: 20px;">
                    <h5 style="margin-bottom: 10px;">🔄 Fast Loop (센서 데이터)</h5>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-secondary" onclick="simulateSensor('active')" style="flex: 1;">
                            🖱️ 활발한 활동
                        </button>
                        <button class="btn btn-secondary" onclick="simulateSensor('idle')" style="flex: 1;">
                            😴 Idle (무활동)
                        </button>
                    </div>
                </div>
                
                <!-- Kalman 시각화 -->
                <div id="kalmanVisualization" style="display: none;">
                    <h5 style="margin-bottom: 10px;">⚖️ Kalman Filter 보정</h5>
                    <div class="kalman-visualization">
                        <div class="kalman-box prediction">
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">예측값</div>
                            <div style="font-size: 1.5rem; font-weight: 700;" id="kalmanPrediction">-</div>
                        </div>
                        <div class="kalman-arrow">→</div>
                        <div class="kalman-gain">
                            <div style="font-size: 0.8rem;">Kalman Gain</div>
                            <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary);" id="kalmanGain">-</div>
                        </div>
                        <div class="kalman-arrow">→</div>
                        <div class="kalman-box measurement">
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">측정값</div>
                            <div style="font-size: 1.5rem; font-weight: 700;" id="kalmanMeasurement">-</div>
                        </div>
                        <div class="kalman-arrow">→</div>
                        <div class="kalman-box result">
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">보정값</div>
                            <div style="font-size: 1.5rem; font-weight: 700;" id="kalmanResult">-</div>
                        </div>
                    </div>
                    <p id="kalmanInterpretation" style="text-align: center; color: var(--text-secondary); margin-top: 10px;"></p>
                </div>
            </div>
        </div>
        
        <!-- 시뮬레이션 로그 -->
        <div style="margin-top: 20px;">
            <h5 style="margin-bottom: 10px;">📋 시뮬레이션 로그</h5>
            <div class="simulation-log" id="simulationLog">
                <div class="log-entry prediction">🚀 시스템 초기화 완료. 초기 상태: 50% 집중</div>
            </div>
        </div>
    </div>
</div>

<script>
// 하이브리드 상태 관리
let hybridState = {
    predictedState: <?php echo $hybridState['predicted_state'] ?? 0.5; ?>,
    uncertainty: <?php echo $hybridState['uncertainty'] ?? 0.1; ?>,
    confidence: <?php echo $hybridState['confidence'] ?? 1.0; ?>,
    stateVector: <?php echo json_encode($hybridState['state_vector'] ?? ['focus' => 0.5, 'flow' => 0, 'struggle' => 0, 'lost' => 0.5]); ?>
};

const CONFIDENCE_DECAY = 0.99;
const UNCERTAINTY_GROWTH = 1.05;
const PING_THRESHOLD = 0.4;

const EVENT_SIGNALS = {
    'correct_answer': 0.9,
    'quick_response': 0.85,
    'scroll_active': 0.7,
    'mouse_movement': 0.6,
    'click_problem': 0.75,
    'page_view': 0.5,
    'idle_short': 0.4,
    'hint_click': 0.2,
    'wrong_answer': 0.3,
    'skip_problem': 0.15,
    'long_pause': 0.25,
    'tab_switch': 0.1,
    'idle_long': 0.1
};

// UI 업데이트
function updateUI() {
    // 집중도 미터
    const stateMeter = document.getElementById('stateMeterFill');
    const stateValue = document.getElementById('predictedStateValue');
    const stateLabel = document.getElementById('stateMeterLabel');
    
    stateMeter.style.width = (hybridState.predictedState * 100) + '%';
    stateValue.textContent = Math.round(hybridState.predictedState * 100) + '%';
    
    // 지배 상태 결정
    let dominant = 'focus';
    let maxVal = 0;
    for (const [state, val] of Object.entries(hybridState.stateVector)) {
        if (val > maxVal) {
            maxVal = val;
            dominant = state;
        }
    }
    stateLabel.textContent = {'focus': 'Focus', 'flow': 'Flow', 'struggle': 'Struggle', 'lost': 'Lost'}[dominant];
    
    // 확신도
    const confIndicator = document.getElementById('confidenceIndicator');
    const confValue = document.getElementById('confidenceValue');
    const uncertValue = document.getElementById('uncertaintyValue');
    
    confValue.textContent = Math.round(hybridState.confidence * 100) + '%';
    uncertValue.textContent = Math.round(hybridState.uncertainty * 100) + '%';
    
    confIndicator.className = 'confidence-indicator ' + 
        (hybridState.confidence >= 0.6 ? 'high' : (hybridState.confidence >= 0.3 ? 'medium' : 'low'));
    
    // 상태 벡터 바
    for (const [state, val] of Object.entries(hybridState.stateVector)) {
        const bar = document.getElementById('stateBar_' + state);
        const value = document.getElementById('stateValue_' + state);
        if (bar) bar.style.height = (val * 100) + '%';
        if (value) value.textContent = Math.round(val * 100) + '%';
    }
}

// 로그 추가
function addLog(message, type = 'prediction') {
    const log = document.getElementById('simulationLog');
    const entry = document.createElement('div');
    entry.className = 'log-entry ' + type;
    entry.textContent = new Date().toLocaleTimeString() + ' - ' + message;
    log.insertBefore(entry, log.firstChild);
    
    // 최대 50개 로그 유지
    while (log.children.length > 50) {
        log.removeChild(log.lastChild);
    }
}

// 상태 벡터 업데이트
function updateStateVector() {
    const state = hybridState.predictedState;
    
    if (state >= 0.7) {
        hybridState.stateVector = {
            focus: state,
            flow: state - 0.2,
            struggle: 0.1,
            lost: 0.0
        };
    } else if (state >= 0.4) {
        hybridState.stateVector = {
            focus: state,
            flow: Math.max(0, state - 0.4),
            struggle: 0.5 - Math.abs(state - 0.5),
            lost: Math.max(0, 0.4 - state)
        };
    } else {
        hybridState.stateVector = {
            focus: state,
            flow: 0.0,
            struggle: state,
            lost: 1.0 - state
        };
    }
    
    // 정규화
    const total = Object.values(hybridState.stateVector).reduce((a, b) => a + b, 0);
    if (total > 0) {
        for (const key in hybridState.stateVector) {
            hybridState.stateVector[key] = hybridState.stateVector[key] / total;
        }
    }
}

// Fast Loop 시뮬레이션
function simulateSensor(type) {
    if (type === 'active') {
        // 활발한 활동
        hybridState.predictedState = Math.min(1.0, hybridState.predictedState + 0.05);
        hybridState.confidence = Math.min(1.0, hybridState.confidence * 1.02);
        addLog('🖱️ 활발한 활동 감지 → 집중도 상승', 'prediction');
    } else {
        // Idle - Decoherence
        hybridState.confidence *= CONFIDENCE_DECAY;
        hybridState.uncertainty *= UNCERTAINTY_GROWTH;
        hybridState.uncertainty = Math.min(1.0, hybridState.uncertainty);
        
        // 중앙값으로 드리프트
        if (hybridState.predictedState > 0.5) {
            hybridState.predictedState -= 0.02;
        } else {
            hybridState.predictedState += 0.02;
        }
        
        addLog('😴 Idle 감지 → 확신도 감쇠 (Decoherence)', 'prediction');
    }
    
    updateStateVector();
    updateUI();
    
    // Ping 필요 여부 확인
    if (hybridState.confidence < PING_THRESHOLD) {
        addLog('⚠️ 확신도가 임계값 이하입니다. Active Ping을 권장합니다!', 'ping');
    }
}

// Active Ping 발사
function firePing(level) {
    const pingNames = {1: 'Subtle (미세 자극)', 2: 'Nudge (넛지)', 3: 'Alert (직접 질문)'};
    addLog('📡 Active Ping 발사: ' + pingNames[level], 'ping');
    
    // 버튼 활성화 표시
    document.querySelectorAll('.ping-button').forEach(btn => btn.classList.remove('active'));
    document.getElementById('pingBtn' + level).classList.add('active');
    
    // 시뮬레이션: 랜덤 반응
    setTimeout(() => {
        const responded = Math.random() > 0.3; // 70% 확률로 반응
        const responseTime = Math.random() * 3;
        
        if (responded) {
            hybridState.confidence = Math.min(1.0, hybridState.confidence + 0.5);
            if (responseTime < 1.0) {
                hybridState.predictedState = Math.min(1.0, hybridState.predictedState + 0.2);
            } else if (responseTime < 3.0) {
                hybridState.predictedState = Math.min(1.0, hybridState.predictedState + 0.1);
            }
            addLog('✅ 반응 감지! (' + responseTime.toFixed(1) + '초) → 상태 붕괴: Focus', 'event');
        } else {
            hybridState.predictedState = Math.max(0.0, hybridState.predictedState - 0.3);
            hybridState.confidence = 0.8;
            addLog('❌ 무반응 → 상태 붕괴: Lost', 'event');
        }
        
        updateStateVector();
        updateUI();
        
        document.querySelectorAll('.ping-button').forEach(btn => btn.classList.remove('active'));
    }, 1500);
}

// 이벤트 시뮬레이션 (Kalman Correction)
function simulateEvent(eventType) {
    const measuredValue = EVENT_SIGNALS[eventType] || 0.5;
    const previousState = hybridState.predictedState;
    
    // Kalman Gain 계산
    const measurementNoise = ['correct_answer', 'wrong_answer', 'hint_click', 'skip_problem'].includes(eventType) ? 0.05 : 0.15;
    const kalmanGain = hybridState.uncertainty / (hybridState.uncertainty + measurementNoise);
    
    // 상태 업데이트
    hybridState.predictedState = previousState + kalmanGain * (measuredValue - previousState);
    
    // 불확실성 감소
    const prevUncertainty = hybridState.uncertainty;
    hybridState.uncertainty = (1 - kalmanGain) * hybridState.uncertainty;
    
    // 확신도 회복
    hybridState.confidence = Math.min(1.0, hybridState.confidence + kalmanGain * 0.5);
    
    // Kalman 시각화 업데이트
    document.getElementById('kalmanVisualization').style.display = 'block';
    document.getElementById('kalmanPrediction').textContent = Math.round(previousState * 100) + '%';
    document.getElementById('kalmanMeasurement').textContent = Math.round(measuredValue * 100) + '%';
    document.getElementById('kalmanGain').textContent = kalmanGain.toFixed(2);
    document.getElementById('kalmanResult').textContent = Math.round(hybridState.predictedState * 100) + '%';
    
    const change = hybridState.predictedState - previousState;
    let interpretation = '';
    if (Math.abs(change) < 0.05) {
        interpretation = '이벤트가 예측과 일치합니다. 상태 안정적.';
    } else if (change > 0) {
        interpretation = '집중도가 ' + Math.round(Math.abs(change) * 100) + '% 상향 보정되었습니다.';
    } else {
        interpretation = '집중도가 ' + Math.round(Math.abs(change) * 100) + '% 하향 보정되었습니다.';
    }
    document.getElementById('kalmanInterpretation').textContent = interpretation;
    
    addLog('⚡ [' + eventType + '] Kalman 보정: ' + Math.round(previousState * 100) + '% → ' + Math.round(hybridState.predictedState * 100) + '% (K=' + kalmanGain.toFixed(2) + ')', 'event');
    
    updateStateVector();
    updateUI();
}

// 초기 UI 업데이트
updateUI();
</script>

