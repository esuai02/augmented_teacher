<?php
/**
 * 에이전트 진행 현황 대시보드
 * 
 * 파일: progress_dashboard.php
 * 위치: alt42/orchestration/agents/agent_orchestration/
 * 생성일: 2025-12-06
 * PHP 버전: 7.1.9 호환
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 에이전트 데이터 정의
$agents = array(
    // Phase 1: Daily Information Collection
    array('id' => '01', 'name' => '온보딩', 'name_en' => 'Onboarding', 'phase' => 1, 'category' => 'core', 
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '학생 초기 프로필 수집 및 분석',
     'dependencies' => array(), 'outputs' => array('02', '03', '07', '09', '14', '18')),
    
    array('id' => '02', 'name' => '시험일정', 'name_en' => 'Exam Schedule', 'phase' => 1, 'category' => 'support',
     'progress' => 40, 'standardized' => false, 'priority' => 'high',
     'description' => '시험 대비 계획 수립 및 학습 전략',
     'dependencies' => array('01', '03', '14'), 'outputs' => array('09', '17', '07')),
    
    array('id' => '03', 'name' => '목표분석', 'name_en' => 'Goals Analysis', 'phase' => 1, 'category' => 'support',
     'progress' => 60, 'standardized' => false, 'priority' => 'medium',
     'description' => '학습 목표 설정 및 분석',
     'dependencies' => array('01'), 'outputs' => array('02', '09', '14', '17')),
    
    array('id' => '04', 'name' => '취약점검사', 'name_en' => 'Inspect Weakpoints', 'phase' => 1, 'category' => 'core',
     'progress' => 80, 'standardized' => true, 'priority' => 'low',
     'description' => '학습 활동 취약점 분석 및 페르소나 연결',
     'dependencies' => array('09'), 'outputs' => array('05', '07', '11', '16', '18')),
    
    array('id' => '05', 'name' => '학습감정', 'name_en' => 'Learning Emotion', 'phase' => 1, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '감정 패턴 식별 및 페르소나 분석',
     'dependencies' => array(), 'outputs' => array('07', '12', '13', '20', '21')),
    
    array('id' => '06', 'name' => '교사피드백', 'name_en' => 'Teacher Feedback', 'phase' => 1, 'category' => 'support',
     'progress' => 40, 'standardized' => false, 'priority' => 'medium',
     'description' => '교사 피드백 통합 및 분석',
     'dependencies' => array(), 'outputs' => array('07', '09')),
    
    // Phase 2: Real-time Interaction
    array('id' => '07', 'name' => '상호작용타겟팅', 'name_en' => 'Interaction Targeting', 'phase' => 2, 'category' => 'support',
     'progress' => 80, 'standardized' => false, 'priority' => 'medium',
     'description' => '상호작용 타겟 결정 및 우선순위 설정',
     'dependencies' => array('05', '14'), 'outputs' => array('16')),
    
    array('id' => '08', 'name' => '침착도', 'name_en' => 'Calmness', 'phase' => 2, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '침착도 측정 및 학습 수행 적합도 판단',
     'dependencies' => array('11'), 'outputs' => array('07', '12')),
    
    array('id' => '09', 'name' => '학습관리', 'name_en' => 'Learning Management', 'phase' => 2, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '학습 계획 관리 및 추적',
     'dependencies' => array('02', '17'), 'outputs' => array('04', '14')),
    
    array('id' => '10', 'name' => '개념노트', 'name_en' => 'Concept Notes', 'phase' => 2, 'category' => 'analysis',
     'progress' => 60, 'standardized' => false, 'priority' => 'medium',
     'description' => '개념 학습 분석 및 이해도 평가',
     'dependencies' => array('09'), 'outputs' => array('07', '15')),
    
    array('id' => '11', 'name' => '문제노트', 'name_en' => 'Problem Notes', 'phase' => 2, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '오답 패턴 분석 및 복습 전략 설계',
     'dependencies' => array('04'), 'outputs' => array('07', '08', '15')),
    
    array('id' => '12', 'name' => '휴식루틴', 'name_en' => 'Rest Routine', 'phase' => 2, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '휴식 패턴 관리 및 피로도 모니터링',
     'dependencies' => array('05', '08'), 'outputs' => array('07', '13')),
    
    array('id' => '13', 'name' => '학습이탈', 'name_en' => 'Learning Dropout', 'phase' => 2, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '학습 이탈 조짐 감지 및 예방',
     'dependencies' => array('05', '12'), 'outputs' => array('07', '20')),
    
    // Phase 3: Diagnosis & Preparation
    array('id' => '14', 'name' => '현재위치', 'name_en' => 'Current Position', 'phase' => 3, 'category' => 'core',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '학생의 현재 학습 상태 및 진도 파악',
     'dependencies' => array('01'), 'outputs' => array('02', '07', '09', '17', '21')),
    
    array('id' => '15', 'name' => '문제재정의', 'name_en' => 'Problem Redefinition', 'phase' => 3, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '문제의 근본 원인 분석 및 재정의',
     'dependencies' => array('10', '11'), 'outputs' => array('07', '09', '17', '19')),
    
    array('id' => '16', 'name' => '상호작용준비', 'name_en' => 'Interaction Preparation', 'phase' => 3, 'category' => 'execution',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '상호작용 준비 및 세계관 선택',
     'dependencies' => array('07'), 'outputs' => array('19')),
    
    array('id' => '17', 'name' => '잔여활동', 'name_en' => 'Remaining Activities', 'phase' => 3, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '남은 학습량 관리 및 우선순위 설정',
     'dependencies' => array('02', '14'), 'outputs' => array('09', '20')),
    
    array('id' => '18', 'name' => '시그너처루틴', 'name_en' => 'Signature Routine', 'phase' => 3, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '개인화된 학습 루틴 발견 및 정교화',
     'dependencies' => array('01', '04'), 'outputs' => array('07', '09', '19')),
    
    array('id' => '19', 'name' => '상호작용컨텐츠', 'name_en' => 'Interaction Content', 'phase' => 3, 'category' => 'execution',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '맞춤형 상호작용 컨텐츠 생성',
     'dependencies' => array('16'), 'outputs' => array('20')),
    
    // Phase 4: Intervention & Improvement
    array('id' => '20', 'name' => '개입준비', 'name_en' => 'Intervention Preparation', 'phase' => 4, 'category' => 'execution',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '개입 위치 및 타이밍 결정',
     'dependencies' => array('19'), 'outputs' => array('21')),
    
    array('id' => '21', 'name' => '개입실행', 'name_en' => 'Intervention Execution', 'phase' => 4, 'category' => 'execution',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '실제 개입 실행 및 결과 모니터링',
     'dependencies' => array('20'), 'outputs' => array('05', '14', '22')),
    
    array('id' => '22', 'name' => '모듈개선', 'name_en' => 'Module Improvement', 'phase' => 4, 'category' => 'analysis',
     'progress' => 95, 'standardized' => true, 'priority' => 'done',
     'description' => '시스템 성능 분석 및 자가 개선',
     'dependencies' => array('*'), 'outputs' => array('*'))
);

// 통계 계산
$stats = array(
    'total' => count($agents),
    'completed' => 0,
    'in_progress' => 0,
    'pending' => 0,
    'average_progress' => 0,
    'standardized' => 0
);

$totalProgress = 0;
foreach ($agents as $agent) {
    $totalProgress += $agent['progress'];
    if ($agent['progress'] >= 95) {
        $stats['completed']++;
    } elseif ($agent['progress'] >= 50) {
        $stats['in_progress']++;
    } else {
        $stats['pending']++;
    }
    if ($agent['standardized']) {
        $stats['standardized']++;
    }
}
$stats['average_progress'] = round($totalProgress / $stats['total'], 1);

// Phase별 통계
$phaseStats = array();
for ($i = 1; $i <= 4; $i++) {
    $phaseAgents = array();
    foreach ($agents as $a) {
        if ($a['phase'] === $i) {
            $phaseAgents[] = $a;
        }
    }
    $phaseProgress = 0;
    foreach ($phaseAgents as $pa) {
        $phaseProgress += $pa['progress'];
    }
    $phaseStats[$i] = array(
        'count' => count($phaseAgents),
        'average' => count($phaseAgents) > 0 ? round($phaseProgress / count($phaseAgents), 1) : 0
    );
}

// 카테고리별 통계
$categoryStats = array();
$categories = array('core', 'analysis', 'support', 'execution');
foreach ($categories as $cat) {
    $catAgents = array();
    foreach ($agents as $a) {
        if ($a['category'] === $cat) {
            $catAgents[] = $a;
        }
    }
    $catProgress = 0;
    foreach ($catAgents as $ca) {
        $catProgress += $ca['progress'];
    }
    $categoryStats[$cat] = array(
        'count' => count($catAgents),
        'average' => count($catAgents) > 0 ? round($catProgress / count($catAgents), 1) : 0
    );
}

// 필터링 함수들
function filterByPhase($agents, $phase) {
    $result = array();
    foreach ($agents as $a) {
        if ($a['phase'] === $phase) {
            $result[] = $a;
        }
    }
    return $result;
}

function filterByStatus($agents, $status) {
    $result = array();
    foreach ($agents as $a) {
        if ($status === 'completed' && $a['progress'] >= 95) {
            $result[] = $a;
        } elseif ($status === 'pending' && $a['progress'] < 95) {
            $result[] = $a;
        }
    }
    return $result;
}

// 에이전트 카드 렌더링 함수
function renderAgentCard($agent, $progressClass) {
    $depHtml = '';
    if (empty($agent['dependencies']) || (isset($agent['dependencies'][0]) && $agent['dependencies'][0] === '*')) {
        $depHtml = '<span class="dep-item input">-</span>';
    } else {
        foreach ($agent['dependencies'] as $dep) {
            $depHtml .= '<span class="dep-item input">' . htmlspecialchars($dep) . '</span>';
        }
    }
    
    $outHtml = '';
    if (empty($agent['outputs']) || (isset($agent['outputs'][0]) && $agent['outputs'][0] === '*')) {
        $outHtml = '<span class="dep-item output">*</span>';
    } else {
        foreach ($agent['outputs'] as $out) {
            $outHtml .= '<span class="dep-item output">' . htmlspecialchars($out) . '</span>';
        }
    }
    
    $standardizedBadge = $agent['standardized'] ? 'standardized' : 'not-standardized';
    $standardizedText = $agent['standardized'] ? '✓ 표준화' : '✗ 미표준';
    
    $html = '
    <div class="agent-card ' . htmlspecialchars($agent['category']) . '">
        <div class="priority-indicator ' . htmlspecialchars($agent['priority']) . '"></div>
        
        <div class="agent-header">
            <div class="agent-id">' . htmlspecialchars($agent['id']) . '</div>
            <div class="agent-badges">
                <span class="badge phase">P' . htmlspecialchars($agent['phase']) . '</span>
                <span class="badge ' . htmlspecialchars($agent['category']) . '">' . ucfirst(htmlspecialchars($agent['category'])) . '</span>
                <span class="badge ' . $standardizedBadge . '">' . $standardizedText . '</span>
            </div>
        </div>
        
        <div class="agent-name">' . htmlspecialchars($agent['name']) . '</div>
        <div class="agent-name-en">' . htmlspecialchars($agent['name_en']) . '</div>
        <div class="agent-description">' . htmlspecialchars($agent['description']) . '</div>
        
        <div class="progress-container">
            <div class="progress-header">
                <span class="progress-label">완성도</span>
                <span class="progress-value ' . $progressClass . '">' . htmlspecialchars($agent['progress']) . '%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill ' . $progressClass . '" style="width: ' . htmlspecialchars($agent['progress']) . '%"></div>
            </div>
        </div>
        
        <div class="dependencies">
            <div class="dep-group">
                <div class="dep-label">← 입력</div>
                <div class="dep-list">' . $depHtml . '</div>
            </div>
            <div class="dep-group">
                <div class="dep-label">출력 →</div>
                <div class="dep-list">' . $outHtml . '</div>
            </div>
        </div>
    </div>';
    
    return $html;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>에이전트 진행 현황 대시보드</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Malgun Gothic', '맑은 고딕', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: #e8e8e8;
            line-height: 1.6;
        }
        
        /* Navigation */
        .nav-dropdown {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            gap: 2px;
        }
        
        .nav-dropdown select {
            padding: 10px 15px;
            border: none;
            border-bottom: 2px solid rgba(255,255,255,0.1);
            background: rgba(26, 26, 46, 0.95);
            color: #e8e8e8;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            height: 42px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            transition: all 0.2s;
        }
        
        .nav-dropdown select:hover {
            background: rgba(15, 52, 96, 0.95);
            border-color: #4fc3f7;
        }
        
        .nav-dropdown select option {
            background: #1a1a2e;
            color: #e8e8e8;
        }
        
        /* Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 70px 30px 30px;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(90deg, #4fc3f7, #81c784, #ffb74d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #90a4ae;
            font-size: 0.95rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(79, 195, 247, 0.5);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-value.completed { color: #81c784; }
        .stat-value.in-progress { color: #ffb74d; }
        .stat-value.pending { color: #e57373; }
        .stat-value.average { color: #4fc3f7; }
        .stat-value.standardized { color: #ba68c8; }
        
        .stat-label {
            color: #90a4ae;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Tabs */
        .tabs-container {
            margin-bottom: 25px;
        }
        
        .tabs {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            background: rgba(0,0,0,0.2);
            padding: 8px;
            border-radius: 12px;
        }
        
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #90a4ae;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab:hover {
            background: rgba(255,255,255,0.05);
            color: #e8e8e8;
        }
        
        .tab.active {
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: #1a1a2e;
            font-weight: 600;
        }
        
        .tab-badge {
            background: rgba(0,0,0,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
        
        .tab.active .tab-badge {
            background: rgba(255,255,255,0.3);
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Agent Grid */
        .agent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .agent-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .agent-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .agent-card.core::before { background: linear-gradient(90deg, #42a5f5, #1976d2); }
        .agent-card.analysis::before { background: linear-gradient(90deg, #ba68c8, #7b1fa2); }
        .agent-card.support::before { background: linear-gradient(90deg, #66bb6a, #388e3c); }
        .agent-card.execution::before { background: linear-gradient(90deg, #ffa726, #f57c00); }
        
        .agent-card:hover {
            transform: translateY(-5px);
            border-color: rgba(79, 195, 247, 0.3);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        
        .agent-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .agent-id {
            font-size: 2rem;
            font-weight: 800;
            color: rgba(255,255,255,0.15);
            line-height: 1;
        }
        
        .agent-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.phase { background: rgba(79, 195, 247, 0.2); color: #4fc3f7; }
        .badge.core { background: rgba(66, 165, 245, 0.2); color: #42a5f5; }
        .badge.analysis { background: rgba(186, 104, 200, 0.2); color: #ba68c8; }
        .badge.support { background: rgba(102, 187, 106, 0.2); color: #66bb6a; }
        .badge.execution { background: rgba(255, 167, 38, 0.2); color: #ffa726; }
        .badge.standardized { background: rgba(129, 199, 132, 0.2); color: #81c784; }
        .badge.not-standardized { background: rgba(229, 115, 115, 0.2); color: #e57373; }
        
        .agent-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 3px;
        }
        
        .agent-name-en {
            font-size: 0.8rem;
            color: #78909c;
            margin-bottom: 10px;
        }
        
        .agent-description {
            font-size: 0.85rem;
            color: #90a4ae;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        /* Progress Bar */
        .progress-container {
            margin-bottom: 15px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .progress-label {
            font-size: 0.8rem;
            color: #78909c;
        }
        
        .progress-value {
            font-size: 0.9rem;
            font-weight: 700;
        }
        
        .progress-value.high { color: #81c784; }
        .progress-value.medium { color: #ffb74d; }
        .progress-value.low { color: #e57373; }
        
        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .progress-fill.high { background: linear-gradient(90deg, #66bb6a, #81c784); }
        .progress-fill.medium { background: linear-gradient(90deg, #ffa726, #ffb74d); }
        .progress-fill.low { background: linear-gradient(90deg, #ef5350, #e57373); }
        
        /* Dependencies */
        .dependencies {
            display: flex;
            gap: 15px;
            font-size: 0.75rem;
        }
        
        .dep-group {
            flex: 1;
        }
        
        .dep-label {
            color: #78909c;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .dep-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        
        .dep-item {
            background: rgba(255,255,255,0.08);
            padding: 3px 8px;
            border-radius: 4px;
            color: #b0bec5;
            font-weight: 500;
        }
        
        .dep-item.input { border-left: 2px solid #4fc3f7; }
        .dep-item.output { border-left: 2px solid #81c784; }
        
        /* Priority Indicator */
        .priority-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .priority-indicator.high { background: #e57373; }
        .priority-indicator.medium { background: #ffb74d; }
        .priority-indicator.low { background: #81c784; }
        .priority-indicator.done { background: #4fc3f7; animation: none; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        
        /* Phase Summary */
        .phase-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .phase-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .phase-card:hover {
            border-color: rgba(79, 195, 247, 0.5);
            transform: translateY(-3px);
        }
        
        .phase-card.active {
            border-color: #4fc3f7;
            background: rgba(79, 195, 247, 0.1);
        }
        
        .phase-number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .phase-title {
            font-size: 0.85rem;
            color: #90a4ae;
            margin: 5px 0;
        }
        
        .phase-progress {
            font-size: 1.2rem;
            font-weight: 600;
            color: #81c784;
        }
        
        /* Legend */
        .legend {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #90a4ae;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        
        .legend-color.core { background: linear-gradient(135deg, #42a5f5, #1976d2); }
        .legend-color.analysis { background: linear-gradient(135deg, #ba68c8, #7b1fa2); }
        .legend-color.support { background: linear-gradient(135deg, #66bb6a, #388e3c); }
        .legend-color.execution { background: linear-gradient(135deg, #ffa726, #f57c00); }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #78909c;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 60px 15px 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .phase-summary {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .agent-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                justify-content: center;
            }
            
            .tab {
                padding: 10px 16px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="nav-dropdown">
        <select id="pageSelector" onchange="navigateToPage()">
            <option value="agentmission.html">1. 에이전트 미션</option>
            <option value="questions.html">2. 주요 요청들</option>
            <option value="dataindex.php">3. 데이터 통합</option>
            <option value="rules_viewer.html">4. 에이전트 룰들</option>
            <option value="progress_dashboard.php" selected>5. Mathking AI 조교</option>
            <option value="heartbeat_dashboard.html">6. Heartbeat Dashboard</option>
            <option value="../agent22_module_improvement/ui/index.php">7. 에이전트 가드닝</option>
            <option value="../agent01_onboarding/persona_system/test_chat.php">8. 페르소나 테스트</option>
            <option value="quantum_modeling.html">9. Quantum Modeling</option>
            <option value="Alignment_dashboard.php">10. Alignment Dashboard</option>
        </select>
    </div>

    <div class="container">
        <div class="header">
            <h1>📊 에이전트 진행 현황 대시보드</h1>
            <p class="subtitle">22개 에이전트의 완성도 및 표준화 상태를 실시간으로 모니터링합니다</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value completed"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">완료 (95%+)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value in-progress"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">진행 중 (50-94%)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value pending"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">미완료 (&lt;50%)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value average"><?php echo $stats['average_progress']; ?>%</div>
                <div class="stat-label">평균 진행률</div>
            </div>
            <div class="stat-card">
                <div class="stat-value standardized"><?php echo $stats['standardized']; ?>/<?php echo $stats['total']; ?></div>
                <div class="stat-label">표준화 완료</div>
            </div>
        </div>
        
        <!-- Phase Summary -->
        <div class="phase-summary">
            <?php 
            $phaseNames = array(
                1 => 'Daily Collection',
                2 => 'Real-time Interaction',
                3 => 'Diagnosis & Prep',
                4 => 'Intervention'
            );
            foreach ($phaseStats as $phase => $pstat): 
            ?>
            <div class="phase-card" onclick="showPhase(<?php echo $phase; ?>)">
                <div class="phase-number">P<?php echo $phase; ?></div>
                <div class="phase-title"><?php echo $phaseNames[$phase]; ?></div>
                <div class="phase-progress"><?php echo $pstat['average']; ?>%</div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color core"></div>
                <span>Core (<?php echo $categoryStats['core']['count']; ?>)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color analysis"></div>
                <span>Analysis (<?php echo $categoryStats['analysis']['count']; ?>)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color support"></div>
                <span>Support (<?php echo $categoryStats['support']['count']; ?>)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color execution"></div>
                <span>Execution (<?php echo $categoryStats['execution']['count']; ?>)</span>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs-container">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('all', this)">
                    전체 <span class="tab-badge"><?php echo $stats['total']; ?></span>
                </button>
                <button class="tab" onclick="switchTab('phase1', this)">
                    Phase 1 <span class="tab-badge"><?php echo $phaseStats[1]['count']; ?></span>
                </button>
                <button class="tab" onclick="switchTab('phase2', this)">
                    Phase 2 <span class="tab-badge"><?php echo $phaseStats[2]['count']; ?></span>
                </button>
                <button class="tab" onclick="switchTab('phase3', this)">
                    Phase 3 <span class="tab-badge"><?php echo $phaseStats[3]['count']; ?></span>
                </button>
                <button class="tab" onclick="switchTab('phase4', this)">
                    Phase 4 <span class="tab-badge"><?php echo $phaseStats[4]['count']; ?></span>
                </button>
                <button class="tab" onclick="switchTab('pending', this)">
                    🔴 미완료 <span class="tab-badge"><?php echo $stats['pending'] + $stats['in_progress']; ?></span>
                </button>
                <button class="tab" onclick="switchTab('completed', this)">
                    ✅ 완료 <span class="tab-badge"><?php echo $stats['completed']; ?></span>
                </button>
            </div>
            
            <!-- Tab: All -->
            <div class="tab-content active" id="tab-all">
                <div class="agent-grid">
                    <?php foreach ($agents as $agent): 
                        $progressClass = $agent['progress'] >= 80 ? 'high' : ($agent['progress'] >= 50 ? 'medium' : 'low');
                    ?>
                    <?php echo renderAgentCard($agent, $progressClass); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tab: Phase 1 -->
            <div class="tab-content" id="tab-phase1">
                <div class="agent-grid">
                    <?php 
                    $phase1Agents = filterByPhase($agents, 1);
                    foreach ($phase1Agents as $agent): 
                        $progressClass = $agent['progress'] >= 80 ? 'high' : ($agent['progress'] >= 50 ? 'medium' : 'low');
                    ?>
                    <?php echo renderAgentCard($agent, $progressClass); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tab: Phase 2 -->
            <div class="tab-content" id="tab-phase2">
                <div class="agent-grid">
                    <?php 
                    $phase2Agents = filterByPhase($agents, 2);
                    foreach ($phase2Agents as $agent): 
                        $progressClass = $agent['progress'] >= 80 ? 'high' : ($agent['progress'] >= 50 ? 'medium' : 'low');
                    ?>
                    <?php echo renderAgentCard($agent, $progressClass); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tab: Phase 3 -->
            <div class="tab-content" id="tab-phase3">
                <div class="agent-grid">
                    <?php 
                    $phase3Agents = filterByPhase($agents, 3);
                    foreach ($phase3Agents as $agent): 
                        $progressClass = $agent['progress'] >= 80 ? 'high' : ($agent['progress'] >= 50 ? 'medium' : 'low');
                    ?>
                    <?php echo renderAgentCard($agent, $progressClass); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tab: Phase 4 -->
            <div class="tab-content" id="tab-phase4">
                <div class="agent-grid">
                    <?php 
                    $phase4Agents = filterByPhase($agents, 4);
                    foreach ($phase4Agents as $agent): 
                        $progressClass = $agent['progress'] >= 80 ? 'high' : ($agent['progress'] >= 50 ? 'medium' : 'low');
                    ?>
                    <?php echo renderAgentCard($agent, $progressClass); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tab: Pending -->
            <div class="tab-content" id="tab-pending">
                <div class="agent-grid">
                    <?php 
                    $pendingAgents = filterByStatus($agents, 'pending');
                    if (count($pendingAgents) > 0):
                        foreach ($pendingAgents as $agent): 
                            $progressClass = $agent['progress'] >= 80 ? 'high' : ($agent['progress'] >= 50 ? 'medium' : 'low');
                    ?>
                    <?php echo renderAgentCard($agent, $progressClass); ?>
                    <?php endforeach; 
                    else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🎉</div>
                        <p>모든 에이전트가 완료되었습니다!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab: Completed -->
            <div class="tab-content" id="tab-completed">
                <div class="agent-grid">
                    <?php 
                    $completedAgents = filterByStatus($agents, 'completed');
                    foreach ($completedAgents as $agent): 
                        $progressClass = $agent['progress'] >= 80 ? 'high' : ($agent['progress'] >= 50 ? 'medium' : 'low');
                    ?>
                    <?php echo renderAgentCard($agent, $progressClass); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function navigateToPage() {
            var select = document.getElementById('pageSelector');
            window.location.href = select.value;
        }
        
        function switchTab(tabId, element) {
            // Remove active from all tabs and contents
            var tabs = document.querySelectorAll('.tab');
            var contents = document.querySelectorAll('.tab-content');
            
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            for (var j = 0; j < contents.length; j++) {
                contents[j].classList.remove('active');
            }
            
            // Add active to selected
            element.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }
        
        function showPhase(phase) {
            // Find and click the phase tab
            var tabs = document.querySelectorAll('.tab');
            for (var i = 0; i < tabs.length; i++) {
                if (tabs[i].textContent.indexOf('Phase ' + phase) !== -1) {
                    switchTab('phase' + phase, tabs[i]);
                    break;
                }
            }
            
            // Highlight phase card
            var phaseCards = document.querySelectorAll('.phase-card');
            for (var j = 0; j < phaseCards.length; j++) {
                if (j + 1 === phase) {
                    phaseCards[j].classList.add('active');
                } else {
                    phaseCards[j].classList.remove('active');
                }
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            var selector = document.getElementById('pageSelector');
            var currentPage = window.location.pathname.split('/').pop();
            for (var i = 0; i < selector.options.length; i++) {
                if (selector.options[i].value.indexOf(currentPage) !== -1) {
                    selector.options[i].selected = true;
                    break;
                }
            }
        });
    </script>
</body>
</html>
