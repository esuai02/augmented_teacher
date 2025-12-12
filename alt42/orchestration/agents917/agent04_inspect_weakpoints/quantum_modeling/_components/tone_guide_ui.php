<?php
/**
 * 대응 톤 가이드 UI 컴포넌트
 * @package AugmentedTeacher\Agent04\QuantumModeling\Components
 * 
 * Required variables:
 * - $allTones: 모든 톤 정의
 * - $contextSimResult: 시뮬레이션 결과 (nullable)
 */

if (!isset($allTones)) {
    echo '<div class="card"><p style="color: var(--danger);">Error: Required variables not set (File: ' . __FILE__ . ', Line: ' . __LINE__ . ')</p></div>';
    return;
}
?>

<!-- 톤 가이드 -->
<div class="col-6">
    <div class="card">
        <div class="card-header">
            <div class="card-title">🎨 대응 톤 가이드</div>
        </div>
        
        <div class="tone-grid">
            <?php foreach ($allTones as $toneKey => $tone): 
                $isRecommended = ($contextSimResult['recommendation']['tone']['type'] ?? '') === $toneKey;
            ?>
            <div class="tone-card <?php echo $isRecommended ? 'active' : ''; ?>">
                <div class="tone-icon"><?php echo $tone['icon']; ?></div>
                <div class="tone-name"><?php echo $tone['name']; ?></div>
                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 5px;">
                    <?php echo $toneKey; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($contextSimResult && !empty($contextSimResult['recommendation']['tone']['example'])): ?>
        <div style="margin-top: 20px; padding: 15px; background: var(--bg-dark); border-radius: 10px;">
            <strong>💬 예시 발화:</strong>
            <p style="margin-top: 8px; font-style: italic; color: var(--text-secondary);">
                "<?php echo $contextSimResult['recommendation']['tone']['example']; ?>"
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

