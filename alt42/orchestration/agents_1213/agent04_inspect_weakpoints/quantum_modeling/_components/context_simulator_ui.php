<?php
/**
 * 상황 기반 페르소나 시뮬레이터 UI 컴포넌트
 * @package AugmentedTeacher\Agent04\QuantumModeling\Components
 * 
 * Required variables:
 * - $allContextCodes: 모든 상황 코드 배열
 * - $selectedContextCode: 선택된 상황 코드
 * - $contextPersonas: 선택된 상황의 페르소나들
 * - $userMessage: 사용자 입력 메시지
 */

if (!isset($allContextCodes) || !isset($selectedContextCode) || !isset($contextPersonas)) {
    echo '<div class="card"><p style="color: var(--danger);">Error: Required variables not set (File: ' . __FILE__ . ', Line: ' . __LINE__ . ')</p></div>';
    return;
}
?>

<!-- 상황 기반 페르소나 시뮬레이터 -->
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <div class="card-title">🎭 상황 기반 페르소나 시뮬레이터</div>
            <span class="persona-badge" style="background: var(--secondary);">Agent03 통합</span>
        </div>
        
        <!-- 상황 코드 탭 -->
        <div class="context-tabs">
            <?php foreach ($allContextCodes as $code => $ctx): 
                $isActive = $code === $selectedContextCode;
                $isCritical = $ctx['priority'] === 'Critical';
            ?>
            <a href="?context_code=<?php echo $code; ?>&user_message=<?php echo urlencode($userMessage ?? ''); ?>" 
               class="context-tab <?php echo $isActive ? 'active' : ''; ?> <?php echo $isCritical ? 'critical' : ''; ?>">
                <span class="context-tab-icon"><?php echo $ctx['icon']; ?></span>
                <span class="context-tab-name"><?php echo $ctx['name']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- 선택된 상황 설명 -->
        <div style="padding: 15px; background: var(--bg-dark); border-radius: 10px; margin-bottom: 20px;">
            <strong><?php echo $allContextCodes[$selectedContextCode]['icon']; ?> 
            <?php echo $allContextCodes[$selectedContextCode]['name']; ?></strong>
            <span style="color: var(--text-secondary);"> - <?php echo $allContextCodes[$selectedContextCode]['description']; ?></span>
            <div style="margin-top: 10px;">
                <small style="color: var(--text-secondary);">키워드: </small>
                <?php foreach ($allContextCodes[$selectedContextCode]['keywords'] as $kw): ?>
                <span class="persona-tag"><?php echo $kw; ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 해당 상황의 페르소나들 -->
        <h4 style="margin-bottom: 15px;">📋 <?php echo $selectedContextCode; ?> 상황 페르소나들</h4>
        <div class="persona-grid">
            <?php foreach ($contextPersonas as $pid => $persona): ?>
            <div class="persona-card">
                <div class="persona-card-header">
                    <div class="persona-icon"><?php echo $persona['icon']; ?></div>
                    <div>
                        <div class="persona-name"><?php echo $persona['name']; ?></div>
                        <div class="persona-name-en"><?php echo $persona['name_en']; ?></div>
                    </div>
                </div>
                <div class="persona-desc"><?php echo $persona['description']; ?></div>
                <div class="persona-tags">
                    <?php foreach (array_slice($persona['speech_patterns'] ?? [], 0, 3) as $pattern): ?>
                    <span class="persona-tag">"<?php echo $pattern; ?>"</span>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($persona['intervention'])): ?>
                <div class="persona-intervention">
                    <div class="persona-intervention-title">💡 권장 개입</div>
                    <div class="persona-intervention-content">
                        <strong>톤:</strong> <?php echo $persona['intervention']['tone']; ?> |
                        <strong>패턴:</strong> <?php echo $persona['intervention']['pattern']; ?>
                        <br><small><?php echo $persona['intervention']['strategy']; ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

