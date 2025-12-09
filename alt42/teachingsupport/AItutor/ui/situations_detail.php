<?php
/**
 * 상황 정의 상세 페이지
 * 
 * AI 튜터 온톨로지에 정의된 모든 상황 매핑을 상세히 표시
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 온톨로지 파일 로드
$ontology = [];
$ontologyPath = dirname(__DIR__) . '/ontology/persona_situation_mapping.php';
if (file_exists($ontologyPath)) {
    $ontology = include($ontologyPath);
}

// 상황 카테고리별 분류
$situationCategories = [
    'writing' => ['name' => '필기 패턴', 'icon' => '✏️', 'situations' => [], 'desc' => '필기 행동 기반 상황 감지'],
    'emotion' => ['name' => '감정 상태', 'icon' => '😊', 'situations' => [], 'desc' => '학생의 감정 상태 분류'],
    'error' => ['name' => '오류 패턴', 'icon' => '❌', 'situations' => [], 'desc' => '학습 중 발생하는 오류 유형'],
    'interaction' => ['name' => '상호작용', 'icon' => '🤝', 'situations' => [], 'desc' => '튜터-학생 간 상호작용 패턴'],
    'learning' => ['name' => '학습 패턴', 'icon' => '📚', 'situations' => [], 'desc' => '학습 진행 상황 및 패턴']
];

// 상황 매핑
$situationMapping = [
    'writing_pause_short' => 'writing',
    'writing_pause_long' => 'writing',
    'writing_speed_slow' => 'writing',
    'writing_speed_fast' => 'writing',
    'erasing_frequent' => 'writing',
    'emotion_confident' => 'emotion',
    'emotion_confused' => 'emotion',
    'emotion_frustrated' => 'emotion',
    'emotion_anxious' => 'emotion',
    'error_calculation' => 'error',
    'error_concept' => 'error',
    'error_repeated' => 'error',
    'hint_requested' => 'interaction',
    'question_asked' => 'interaction',
    'step_completed' => 'learning',
    'step_skipped' => 'learning',
    'progress_stuck' => 'learning'
];

// 상황 상세 정보
$situationDetails = [
    'writing_pause_short' => ['name' => '짧은 필기 멈춤', 'trigger' => '3-10초 멈춤', 'response' => '생각 중 대기'],
    'writing_pause_long' => ['name' => '긴 필기 멈춤', 'trigger' => '10초 이상 멈춤', 'response' => '힌트 제안'],
    'writing_speed_slow' => ['name' => '느린 필기 속도', 'trigger' => '평균 대비 50% 이하', 'response' => '개념 확인 제안'],
    'writing_speed_fast' => ['name' => '빠른 필기 속도', 'trigger' => '평균 대비 150% 이상', 'response' => '검토 권장'],
    'erasing_frequent' => ['name' => '잦은 지우기', 'trigger' => '3회 이상 연속 지우기', 'response' => '접근 방법 재검토 제안'],
    'emotion_confident' => ['name' => '자신감 있는 상태', 'trigger' => '긍정 감정 감지', 'response' => '격려 및 심화 제안'],
    'emotion_confused' => ['name' => '혼란스러운 상태', 'trigger' => '혼란 감정 감지', 'response' => '단계별 설명 제공'],
    'emotion_frustrated' => ['name' => '좌절감 상태', 'trigger' => '부정 감정 강도 높음', 'response' => '휴식 제안, 격려'],
    'emotion_anxious' => ['name' => '불안한 상태', 'trigger' => '불안 감정 감지', 'response' => '호흡 안내, 격려'],
    'error_calculation' => ['name' => '계산 오류', 'trigger' => '수치 계산 실수', 'response' => '계산 과정 확인 유도'],
    'error_concept' => ['name' => '개념 오류', 'trigger' => '개념 적용 실수', 'response' => '개념 재설명'],
    'error_repeated' => ['name' => '반복 오류', 'trigger' => '동일 오류 2회 이상', 'response' => '근본 원인 분석'],
    'hint_requested' => ['name' => '힌트 요청', 'trigger' => '? 제스처 또는 요청', 'response' => '단계적 힌트 제공'],
    'question_asked' => ['name' => '질문 제기', 'trigger' => '질문 입력', 'response' => '맞춤 답변 생성'],
    'step_completed' => ['name' => '단계 완료', 'trigger' => 'V 제스처 또는 진행', 'response' => '칭찬 및 다음 단계 안내'],
    'step_skipped' => ['name' => '단계 건너뜀', 'trigger' => '단계 미완료 진행', 'response' => '중요도 안내'],
    'progress_stuck' => ['name' => '진행 정체', 'trigger' => '5분 이상 동일 단계', 'response' => '접근법 변경 제안']
];

// 온톨로지에서 추가 상황 로드
if (is_array($ontology) && isset($ontology['situations'])) {
    foreach ($ontology['situations'] as $situationId => $situation) {
        $category = $situationMapping[$situationId] ?? 'learning';
        if (isset($situationCategories[$category])) {
            $situationCategories[$category]['situations'][$situationId] = array_merge(
                $situationDetails[$situationId] ?? ['name' => $situationId, 'trigger' => '-', 'response' => '-'],
                $situation
            );
        }
    }
}

// 기본 상황 추가 (온톨로지에 없는 경우)
foreach ($situationDetails as $situationId => $detail) {
    $category = $situationMapping[$situationId] ?? 'learning';
    if (isset($situationCategories[$category]) && !isset($situationCategories[$category]['situations'][$situationId])) {
        $situationCategories[$category]['situations'][$situationId] = $detail;
    }
}

$totalSituations = 0;
foreach ($situationCategories as $cat) {
    $totalSituations += count($cat['situations']);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상황 정의 상세 | AI 튜터</title>
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
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 16px;
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #3b82f6;
        }
        
        .header .count {
            font-size: 3rem;
            font-weight: 700;
            color: #60a5fa;
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
        
        .category-section {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .category-icon {
            font-size: 2rem;
        }
        
        .category-info h2 {
            font-size: 1.25rem;
            color: #f1f5f9;
        }
        
        .category-info .desc {
            font-size: 0.875rem;
            color: #94a3b8;
        }
        
        .category-count {
            margin-left: auto;
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .situations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }
        
        .situation-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
            transition: all 0.3s;
        }
        
        .situation-card:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateY(-2px);
        }
        
        .situation-id {
            font-size: 0.6875rem;
            color: #64748b;
            font-family: 'Fira Code', monospace;
            margin-bottom: 8px;
        }
        
        .situation-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 12px;
        }
        
        .situation-detail {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 0.8125rem;
        }
        
        .detail-label {
            color: #64748b;
            min-width: 50px;
        }
        
        .detail-value {
            color: #94a3b8;
        }
        
        .trigger-tag {
            display: inline-block;
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 8px;
        }
        
        .response-tag {
            display: inline-block;
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 8px;
            margin-left: 4px;
        }
        
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
            <h1>🧠 상황 정의 상세</h1>
            <div class="count"><?php echo $totalSituations; ?></div>
            <p class="subtitle">AI 튜터 온톨로지에 정의된 모든 상황 매핑</p>
        </div>
        
        <?php foreach ($situationCategories as $catId => $category): ?>
        <div class="category-section">
            <div class="category-header">
                <span class="category-icon"><?php echo $category['icon']; ?></span>
                <div class="category-info">
                    <h2><?php echo $category['name']; ?></h2>
                    <div class="desc"><?php echo $category['desc']; ?></div>
                </div>
                <span class="category-count"><?php echo count($category['situations']); ?>개</span>
            </div>
            
            <?php if (!empty($category['situations'])): ?>
            <div class="situations-grid">
                <?php foreach ($category['situations'] as $situationId => $situation): ?>
                <div class="situation-card">
                    <div class="situation-id"><?php echo htmlspecialchars($situationId); ?></div>
                    <div class="situation-name"><?php echo htmlspecialchars($situation['name'] ?? $situationId); ?></div>
                    
                    <div class="situation-detail">
                        <span class="detail-label">트리거:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($situation['trigger'] ?? '-'); ?></span>
                    </div>
                    
                    <div class="situation-detail">
                        <span class="detail-label">응답:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($situation['response'] ?? '-'); ?></span>
                    </div>
                    
                    <div>
                        <span class="trigger-tag">⚡ <?php echo htmlspecialchars($situation['trigger'] ?? '감지'); ?></span>
                        <span class="response-tag">💬 <?php echo htmlspecialchars(mb_substr($situation['response'] ?? '응답', 0, 10)); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <p>이 카테고리에 정의된 상황이 없습니다.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

