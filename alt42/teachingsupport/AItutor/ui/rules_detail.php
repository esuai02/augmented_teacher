<?php
/**
 * 룰 정의 상세 페이지
 * 
 * AI 튜터 시스템에 정의된 모든 룰을 상세히 표시
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 룰 파일 로드 (complete_rules.php + persona_rules.php)
$rules = [];

// 1. 완결성 룰셋 로드
$completeRulesPath = dirname(__DIR__) . '/rules/complete_rules.php';
if (file_exists($completeRulesPath)) {
    $completeRules = include($completeRulesPath);
    if (is_array($completeRules)) {
        $rules = array_merge($rules, $completeRules);
    }
}

// 2. 페르소나별 룰셋 로드
$personaRulesPath = dirname(__DIR__) . '/rules/persona_rules.php';
if (file_exists($personaRulesPath)) {
    $personaRules = include($personaRulesPath);
    if (is_array($personaRules)) {
        foreach ($personaRules as $persona) {
            if (isset($persona['rules']) && is_array($persona['rules'])) {
                foreach ($persona['rules'] as $rule) {
                    $rule['layer'] = 'persona';
                    $rule['persona_name'] = $persona['name'] ?? '';
                    $rule['name'] = $rule['description'] ?? $rule['rule_id'];
                    $rules[$rule['rule_id']] = $rule;
                }
            }
        }
    }
}

// 3. 즉시 개입 룰셋 로드
$immediateRulesPath = dirname(__DIR__) . '/rules/immediate_rules.php';
if (file_exists($immediateRulesPath)) {
    $immediateRules = include($immediateRulesPath);
    if (is_array($immediateRules)) {
        foreach ($immediateRules as $key => $rule) {
            $rule['layer'] = 'immediate';
            $rules[$key] = $rule;
        }
    }
}

// 룰 레이어별 분류
$ruleLayers = [
    'session' => ['name' => '세션 생명주기', 'icon' => '🎬', 'rules' => [], 'desc' => '세션 시작/종료, 학습 흐름 관리'],
    'writing' => ['name' => '필기 패턴', 'icon' => '✏️', 'rules' => [], 'desc' => '필기 일시정지, 속도, 지우기 패턴 감지'],
    'hint' => ['name' => '힌트 제공', 'icon' => '💡', 'rules' => [], 'desc' => '적절한 힌트 제공 시점 판단'],
    'gesture' => ['name' => '제스처 반응', 'icon' => '👆', 'rules' => [], 'desc' => '펜 제스처 인식 및 반응'],
    'emotion' => ['name' => '감정 반응', 'icon' => '😊', 'rules' => [], 'desc' => '학생 감정 상태 감지 및 대응'],
    'answer' => ['name' => '답 검증', 'icon' => '✅', 'rules' => [], 'desc' => '정답/오답 처리 및 피드백'],
    'memory' => ['name' => '장기기억', 'icon' => '🧠', 'rules' => [], 'desc' => '장기기억화 단계 지원'],
    'persona' => ['name' => '페르소나', 'icon' => '👤', 'rules' => [], 'desc' => '페르소나별 맞춤 개입'],
    'immediate' => ['name' => '즉시 개입', 'icon' => '⚡', 'rules' => [], 'desc' => '즉각 대응이 필요한 상황']
];

// 룰 분류
if (is_array($rules)) {
    foreach ($rules as $ruleId => $rule) {
        $layer = $rule['layer'] ?? 'session';
        if (!isset($ruleLayers[$layer])) {
            // 알 수 없는 레이어는 session에 추가
            $layer = 'session';
        }
        $rule['rule_id'] = $rule['rule_id'] ?? $ruleId;
        $ruleLayers[$layer]['rules'][] = $rule;
    }
}

$totalRules = count($rules);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>룰 정의 상세 | AI 튜터</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Pretendard', -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 16px;
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #10b981;
        }
        
        .header .count {
            font-size: 3rem;
            font-weight: 700;
            color: #34d399;
        }
        
        .header .subtitle {
            color: #94a3b8;
            margin-top: 10px;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #e2e8f0;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .layer-section {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .layer-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .layer-icon {
            font-size: 2rem;
        }
        
        .layer-info h2 {
            font-size: 1.25rem;
            color: #f1f5f9;
        }
        
        .layer-info .desc {
            font-size: 0.875rem;
            color: #94a3b8;
        }
        
        .layer-count {
            margin-left: auto;
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .rules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 16px;
        }
        
        .rule-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
            transition: all 0.3s;
        }
        
        .rule-card:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(16, 185, 129, 0.3);
            transform: translateY(-2px);
        }
        
        .rule-id {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 8px;
        }
        
        .rule-name {
            font-size: 1rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 8px;
        }
        
        .rule-condition {
            font-size: 0.8125rem;
            color: #94a3b8;
            background: rgba(0,0,0,0.2);
            padding: 8px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-family: 'Fira Code', monospace;
        }
        
        .rule-action {
            font-size: 0.8125rem;
            color: #a78bfa;
        }
        
        .rule-priority {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.6875rem;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .priority-high { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .priority-medium { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .priority-low { background: rgba(96, 165, 250, 0.2); color: #60a5fa; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="ontology_roadmap.php" class="back-btn">← 로드맵으로 돌아가기</a>
        
        <div class="header">
            <h1>📋 룰 정의 상세</h1>
            <div class="count"><?php echo $totalRules; ?></div>
            <p class="subtitle">AI 튜터 시스템에 정의된 모든 룰</p>
        </div>
        
        <?php foreach ($ruleLayers as $layerId => $layer): ?>
        <div class="layer-section">
            <div class="layer-header">
                <span class="layer-icon"><?php echo $layer['icon']; ?></span>
                <div class="layer-info">
                    <h2><?php echo $layer['name']; ?></h2>
                    <div class="desc"><?php echo $layer['desc']; ?></div>
                </div>
                <span class="layer-count"><?php echo count($layer['rules']); ?>개</span>
            </div>
            
            <?php if (!empty($layer['rules'])): ?>
            <div class="rules-grid">
                <?php foreach ($layer['rules'] as $rule): 
                    $ruleId = $rule['rule_id'] ?? $rule['id'] ?? 'N/A';
                    $ruleName = $rule['name'] ?? $rule['description'] ?? '이름 없음';
                    $conditions = $rule['conditions'] ?? $rule['condition'] ?? [];
                    $actions = $rule['actions'] ?? [];
                    $action = $rule['action'] ?? '';
                    $priorityNum = $rule['priority'] ?? 50;
                    $priorityClass = $priorityNum >= 90 ? 'high' : ($priorityNum >= 80 ? 'medium' : 'low');
                    $message = $rule['message'] ?? '';
                    $confidence = $rule['confidence'] ?? 0;
                ?>
                <div class="rule-card">
                    <div class="rule-id">#<?php echo htmlspecialchars($ruleId); ?></div>
                    <div class="rule-name"><?php echo htmlspecialchars($ruleName); ?></div>
                    <?php if (!empty($conditions)): ?>
                    <div class="rule-condition">
                        <?php 
                        if (is_array($conditions)) {
                            $condStrs = [];
                            foreach ($conditions as $cond) {
                                if (isset($cond['field'])) {
                                    $condStrs[] = $cond['field'] . ' ' . ($cond['op'] ?? $cond['operator'] ?? '=') . ' ' . json_encode($cond['value'] ?? '', JSON_UNESCAPED_UNICODE);
                                }
                            }
                            echo htmlspecialchars(implode(' AND ', $condStrs));
                        } else {
                            echo htmlspecialchars($conditions);
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($actions)): ?>
                    <div class="rule-action">
                        <?php 
                        $actionStrs = [];
                        foreach ($actions as $act) {
                            if (isset($act['type'])) {
                                $actionStrs[] = $act['type'] . ': ' . ($act['message'] ?? $act['action'] ?? $act['id'] ?? '');
                            }
                        }
                        echo '동작: ' . htmlspecialchars(implode(' → ', $actionStrs));
                        ?>
                    </div>
                    <?php elseif (!empty($action)): ?>
                    <div class="rule-action">동작: <?php echo htmlspecialchars($action); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($message)): ?>
                    <div class="rule-message" style="margin-top: 8px; padding: 8px; background: rgba(139, 92, 246, 0.1); border-radius: 6px; font-size: 0.8125rem; color: #c4b5fd;">
                        💬 "<?php echo htmlspecialchars($message); ?>"
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; gap: 8px; margin-top: 8px; align-items: center;">
                        <span class="rule-priority priority-<?php echo $priorityClass; ?>">
                            우선순위 <?php echo $priorityNum; ?>
                        </span>
                        <?php if ($confidence > 0): ?>
                        <span style="font-size: 0.6875rem; color: #64748b;">
                            신뢰도 <?php echo round($confidence * 100); ?>%
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <p>이 레이어에 정의된 룰이 없습니다.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

