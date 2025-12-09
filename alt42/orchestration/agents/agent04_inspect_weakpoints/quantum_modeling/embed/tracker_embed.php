<?php
/**
 * HybridStateTracker 삽입 코드
 * 
 * 학습 페이지에 이 파일을 include하면 자동으로 하이브리드 상태 추적 시작
 * 
 * 사용법:
 * <?php include '/path/to/tracker_embed.php'; ?>
 * 
 * 또는 특정 옵션으로:
 * <?php 
 *   $trackerOptions = ['debug' => true, 'fastLoopInterval' => 1000];
 *   include '/path/to/tracker_embed.php'; 
 * ?>
 *
 * @package AugmentedTeacher\Agent04\QuantumModeling\Embed
 * @version 1.0.0
 * @since 2025-12-06
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER, $PAGE;

// 로그인 확인
if (!isloggedin() || isguestuser()) {
    return;
}

// 옵션 설정 (외부에서 $trackerOptions로 전달 가능)
$trackerOptions = $trackerOptions ?? [];
$options = array_merge([
    'debug' => false,
    'fastLoopInterval' => 500,
    'pingThreshold' => 0.4,
    'autoStart' => true,
    'showStatusBadge' => false,
], $trackerOptions);

$userId = $USER->id;
$baseUrl = '/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/quantum_modeling';
?>

<!-- HybridStateTracker 스크립트 -->
<script src="<?php echo $baseUrl; ?>/assets/js/HybridStateTracker.js"></script>

<script>
(function() {
    'use strict';
    
    // 설정
    const config = {
        userId: <?php echo $userId; ?>,
        apiEndpoint: '<?php echo $baseUrl; ?>/api/hybrid_state_api.php',
        debug: <?php echo $options['debug'] ? 'true' : 'false'; ?>,
        fastLoopInterval: <?php echo $options['fastLoopInterval']; ?>,
        pingThreshold: <?php echo $options['pingThreshold']; ?>
    };
    
    // 상태 변경 콜백
    function onStateChange(newState, oldState) {
        // 커스텀 이벤트 발생 (다른 스크립트에서 수신 가능)
        document.dispatchEvent(new CustomEvent('hybridStateChange', {
            detail: { newState, oldState }
        }));
        
        <?php if ($options['showStatusBadge']): ?>
        updateStatusBadge(newState);
        <?php endif; ?>
    }
    
    // 핑 발사 콜백
    function onPingFired(ping) {
        document.dispatchEvent(new CustomEvent('hybridPingFired', {
            detail: { ping }
        }));
    }
    
    // Kalman 보정 콜백
    function onCorrectionMade(result, eventType) {
        document.dispatchEvent(new CustomEvent('hybridCorrectionMade', {
            detail: { result, eventType }
        }));
    }
    
    // 트래커 초기화
    window.hybridTracker = new HybridStateTracker({
        ...config,
        onStateChange: onStateChange,
        onPingFired: onPingFired,
        onCorrectionMade: onCorrectionMade
    });
    
    <?php if ($options['autoStart']): ?>
    // 자동 시작
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.hybridTracker.start();
        });
    } else {
        window.hybridTracker.start();
    }
    <?php endif; ?>
    
    <?php if ($options['showStatusBadge']): ?>
    // 상태 배지 생성
    function createStatusBadge() {
        const badge = document.createElement('div');
        badge.id = 'hybrid-status-badge';
        badge.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: rgba(30, 41, 59, 0.95);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 12px;
            font-family: system-ui, -apple-system, sans-serif;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        `;
        badge.innerHTML = `
            <span class="badge-icon">🎯</span>
            <span class="badge-state">집중</span>
            <span class="badge-value" style="font-weight: bold;">50%</span>
        `;
        document.body.appendChild(badge);
    }
    
    function updateStatusBadge(state) {
        const badge = document.getElementById('hybrid-status-badge');
        if (!badge) return;
        
        const stateIcons = {
            'focus': '🎯',
            'flow': '🌊',
            'struggle': '😓',
            'lost': '😴'
        };
        const stateLabels = {
            'focus': '집중',
            'flow': '몰입',
            'struggle': '고군분투',
            'lost': '이탈'
        };
        
        const dominant = state.dominant_state || 'focus';
        const value = Math.round((state.predicted_state || 0.5) * 100);
        
        badge.querySelector('.badge-icon').textContent = stateIcons[dominant] || '🎯';
        badge.querySelector('.badge-state').textContent = stateLabels[dominant] || '집중';
        badge.querySelector('.badge-value').textContent = value + '%';
        
        // 색상 변경
        if (value >= 70) {
            badge.style.background = 'rgba(16, 185, 129, 0.95)';
        } else if (value >= 40) {
            badge.style.background = 'rgba(99, 102, 241, 0.95)';
        } else {
            badge.style.background = 'rgba(239, 68, 68, 0.95)';
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createStatusBadge);
    } else {
        createStatusBadge();
    }
    <?php endif; ?>
    
    // Mathking 전용 이벤트 연동
    // 정답/오답 버튼 감지
    document.addEventListener('click', function(e) {
        const target = e.target;
        
        // 정답 클릭
        if (target.matches('.correct-answer, .answer-correct, [data-correct="true"]')) {
            window.hybridTracker.triggerEvent('correct_answer', {
                time_taken: window.hybridTracker.getTimeOnPage()
            });
        }
        
        // 오답 클릭
        if (target.matches('.wrong-answer, .answer-wrong, [data-correct="false"]')) {
            window.hybridTracker.triggerEvent('wrong_answer', {});
        }
        
        // 힌트 클릭
        if (target.matches('.hint-btn, .hint-button, [data-hint]')) {
            window.hybridTracker.triggerEvent('hint_click', {});
        }
        
        // 건너뛰기
        if (target.matches('.skip-btn, .skip-button, [data-skip]')) {
            window.hybridTracker.triggerEvent('skip_problem', {});
        }
        
        // 다음 문제
        if (target.matches('.next-problem, .next-btn, [data-next]')) {
            window.hybridTracker.triggerEvent('click_problem', {});
        }
    });
    
    // 비디오 이벤트
    document.querySelectorAll('video').forEach(video => {
        video.addEventListener('play', () => {
            window.hybridTracker.triggerEvent('scroll_active', {});
        });
        video.addEventListener('pause', () => {
            window.hybridTracker.triggerEvent('idle_short', {});
        });
    });
    
    // 페이지 언로드 시 상태 저장
    window.addEventListener('beforeunload', function() {
        if (window.hybridTracker) {
            // 동기 요청으로 상태 저장 (navigator.sendBeacon 사용)
            const data = JSON.stringify({
                action: 'get_state', // 또는 save_state
                user_id: config.userId
            });
            navigator.sendBeacon(config.apiEndpoint, data);
        }
    });
    
    // 콘솔 로그 (디버그 모드)
    if (config.debug) {
        console.log('[HybridTracker] 초기화 완료', config);
    }
})();
</script>

<?php if ($options['debug']): ?>
<!-- 디버그 패널 -->
<style>
#hybrid-debug-panel {
    position: fixed;
    top: 10px;
    right: 10px;
    width: 300px;
    background: rgba(15, 23, 42, 0.95);
    color: #e2e8f0;
    border-radius: 12px;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 11px;
    z-index: 10000;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    overflow: hidden;
}
#hybrid-debug-panel .panel-header {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    padding: 10px 15px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
#hybrid-debug-panel .panel-header button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 14px;
}
#hybrid-debug-panel .panel-content {
    padding: 15px;
    max-height: 400px;
    overflow-y: auto;
}
#hybrid-debug-panel .state-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}
#hybrid-debug-panel .state-value {
    font-weight: bold;
    color: #10b981;
}
</style>
<div id="hybrid-debug-panel">
    <div class="panel-header">
        🔧 Hybrid Debug
        <button onclick="this.parentElement.parentElement.style.display='none'">✕</button>
    </div>
    <div class="panel-content">
        <div class="state-row">
            <span>집중도</span>
            <span class="state-value" id="debug-focus">-</span>
        </div>
        <div class="state-row">
            <span>확신도</span>
            <span class="state-value" id="debug-confidence">-</span>
        </div>
        <div class="state-row">
            <span>불확실성</span>
            <span class="state-value" id="debug-uncertainty">-</span>
        </div>
        <div class="state-row">
            <span>지배 상태</span>
            <span class="state-value" id="debug-dominant">-</span>
        </div>
        <div class="state-row">
            <span>핑 필요</span>
            <span class="state-value" id="debug-ping">-</span>
        </div>
        <div id="debug-log" style="margin-top: 10px; font-size: 10px; color: #94a3b8;"></div>
    </div>
</div>
<script>
document.addEventListener('hybridStateChange', function(e) {
    const state = e.detail.newState;
    document.getElementById('debug-focus').textContent = Math.round((state.predicted_state || 0.5) * 100) + '%';
    document.getElementById('debug-confidence').textContent = Math.round((state.confidence || 1) * 100) + '%';
    document.getElementById('debug-uncertainty').textContent = Math.round((state.uncertainty || 0.1) * 100) + '%';
    document.getElementById('debug-dominant').textContent = state.dominant_state || 'focus';
    document.getElementById('debug-ping').textContent = state.needs_ping ? '⚠️ Yes' : '✅ No';
});
document.addEventListener('hybridCorrectionMade', function(e) {
    const log = document.getElementById('debug-log');
    const entry = document.createElement('div');
    entry.textContent = new Date().toLocaleTimeString() + ' - ' + e.detail.eventType;
    log.insertBefore(entry, log.firstChild);
    if (log.children.length > 10) log.removeChild(log.lastChild);
});
</script>
<?php endif; ?>


