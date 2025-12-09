<?php
/**
 * 메시지 기반 페르소나 식별 UI 컴포넌트
 * @package AugmentedTeacher\Agent04\QuantumModeling\Components
 * 
 * Required variables:
 * - $selectedContextCode: 선택된 상황 코드
 * - $userMessage: 사용자 입력 메시지
 * - $contextSimResult: 시뮬레이션 결과 (nullable)
 * - $allContextCodes: 모든 상황 코드 배열
 */

if (!isset($selectedContextCode)) {
    echo '<div class="card"><p style="color: var(--danger);">Error: Required variables not set (File: ' . __FILE__ . ', Line: ' . __LINE__ . ')</p></div>';
    return;
}

$userMessage = $userMessage ?? '';
?>

<!-- 메시지 기반 시뮬레이션 -->
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <div class="card-title">💬 메시지 기반 페르소나 식별</div>
        </div>
        
        <form method="GET" action="">
            <input type="hidden" name="context_code" value="<?php echo $selectedContextCode; ?>">
            
            <div class="message-input-area">
                <label class="form-label">학생 메시지 입력 (시뮬레이션용)</label>
                <textarea name="user_message" class="message-input" 
                          placeholder="예: '열심히 하는데 왜 성적이 안 오르는지 모르겠어요...'&#10;'포기하고 싶어요'&#10;'다음 시험에서 100점 맞을 거예요!'"><?php echo htmlspecialchars($userMessage); ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label class="form-label">목표 진행률 (%)</label>
                    <input type="number" name="progress_rate" class="form-control" 
                           value="<?php echo $_GET['progress_rate'] ?? 50; ?>" min="0" max="100">
                </div>
                <div class="form-group">
                    <label class="form-label">활성 목표 수</label>
                    <input type="number" name="goal_count" class="form-control" 
                           value="<?php echo $_GET['goal_count'] ?? 3; ?>" min="0" max="20">
                </div>
                <div class="form-group">
                    <label class="form-label">정체 일수</label>
                    <input type="number" name="stagnation_days" class="form-control" 
                           value="<?php echo $_GET['stagnation_days'] ?? 0; ?>" min="0" max="90">
                </div>
                <div class="form-group">
                    <label class="form-label">감정 상태</label>
                    <select name="emotional_state" class="form-control">
                        <option value="neutral" <?php echo ($_GET['emotional_state'] ?? '') === 'neutral' ? 'selected' : ''; ?>>중립</option>
                        <option value="positive" <?php echo ($_GET['emotional_state'] ?? '') === 'positive' ? 'selected' : ''; ?>>긍정</option>
                        <option value="negative" <?php echo ($_GET['emotional_state'] ?? '') === 'negative' ? 'selected' : ''; ?>>부정</option>
                        <option value="overwhelmed" <?php echo ($_GET['emotional_state'] ?? '') === 'overwhelmed' ? 'selected' : ''; ?>>압도감</option>
                        <option value="frustrated" <?php echo ($_GET['emotional_state'] ?? '') === 'frustrated' ? 'selected' : ''; ?>>좌절</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                🎭 페르소나 시뮬레이션 실행
            </button>
        </form>
        
        <?php if ($contextSimResult): ?>
        <div class="sim-result-box">
            <div class="sim-result-header">
                <div class="sim-context-badge">
                    <?php echo $contextSimResult['context']['context'] ?? ''; ?>: 
                    <?php echo $allContextCodes[$contextSimResult['context']['context']]['name'] ?? ''; ?>
                </div>
                <div class="sim-persona-badge">
                    <?php echo $contextSimResult['dominant_persona']['icon'] ?? ''; ?>
                    <?php echo $contextSimResult['dominant_persona']['name'] ?? 'Unknown'; ?>
                </div>
                <span style="margin-left: auto; color: var(--text-secondary);">
                    신뢰도: <?php echo round(($contextSimResult['context']['confidence'] ?? 0) * 100); ?>%
                </span>
            </div>
            
            <!-- 식별된 페르소나 확률 분포 -->
            <h5 style="margin-bottom: 10px;">📊 페르소나 확률 분포</h5>
            <?php foreach (array_slice($contextSimResult['personas'] ?? [], 0, 4) as $pResult): ?>
            <div style="margin-bottom: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span><?php echo $pResult['persona']['icon'] ?? ''; ?> <?php echo $pResult['persona']['name'] ?? ''; ?></span>
                    <span><?php echo round(($pResult['probability'] ?? 0) * 100); ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill synergy" style="width: <?php echo ($pResult['probability'] ?? 0) * 100; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- 양자 상태 변환 결과 -->
            <h5 style="margin-top: 20px; margin-bottom: 10px;">⚛️ 양자 상태 매핑</h5>
            <div class="quantum-mini-view">
                <?php 
                $quantumLabels = ['S' => '⚡Sprinter', 'D' => '🤿Diver', 'G' => '🎮Gamer', 'A' => '🏛️Architect'];
                foreach ($contextSimResult['quantum_state'] ?? [] as $key => $val): ?>
                <div class="quantum-mini-item">
                    <div class="quantum-mini-label"><?php echo $quantumLabels[$key]; ?></div>
                    <div class="quantum-mini-value"><?php echo round($val * 100); ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 권장 조치 -->
            <h5 style="margin-top: 20px; margin-bottom: 10px;">🎯 권장 조치</h5>
            <div style="padding: 15px; background: var(--bg-dark); border-radius: 10px; margin-bottom: 10px;">
                <strong>톤: </strong>
                <span class="persona-badge" style="background: var(--primary);">
                    <?php echo $contextSimResult['recommendation']['tone']['icon'] ?? ''; ?>
                    <?php echo $contextSimResult['recommendation']['tone']['name'] ?? ''; ?>
                </span>
                <span style="margin-left: 10px; color: var(--text-secondary);">
                    <?php echo $contextSimResult['recommendation']['tone']['description'] ?? ''; ?>
                </span>
            </div>
            
            <div class="sim-actions-list">
                <?php foreach ($contextSimResult['recommendation']['actions'] ?? [] as $action): ?>
                <div class="sim-action-item"><?php echo $action; ?></div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($contextSimResult['context']['matched_keywords'])): ?>
            <div style="margin-top: 15px;">
                <small style="color: var(--text-secondary);">감지된 키워드: </small>
                <?php foreach ($contextSimResult['context']['matched_keywords'] as $kw): ?>
                <span class="persona-tag" style="background: var(--primary);"><?php echo $kw; ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

