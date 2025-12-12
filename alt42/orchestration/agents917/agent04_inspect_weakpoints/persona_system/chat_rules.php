<?php
/**
 * chat_rules.php - 통합 규칙 뷰어 (rules/rules.yaml)
 * Agent04: 학습활동별 취약점 분석 통합 규칙 인터페이스
 *
 * 8가지 학습활동 + 복합상황 규칙 조회 및 관리
 * - ① 개념이해 (CU) / ② 유형학습 (TL) / ③ 문제풀이 (PS) / ④ 오답노트 (EN)
 * - ⑤ 질의응답 (QA) / ⑥ 복습활동 (RV) / ⑦ 포모도르 (PJ) / ⑧ 귀가검사 (RC)
 * - 복합 상황 대응 (CR)
 *
 * Created: 2025-01-27
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';

// YAML 파일 경로
$yaml_file = __DIR__ . '/../rules/rules.yaml';

// AJAX 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    try {
        switch($action) {
            case 'load_rules':
                if (!file_exists($yaml_file)) {
                    throw new Exception("rules.yaml 파일을 찾을 수 없습니다: " . $yaml_file . " (chat_rules.php:" . __LINE__ . ")");
                }

                $yaml_content = file_get_contents($yaml_file);
                if ($yaml_content === false) {
                    throw new Exception("YAML 파일 읽기 실패 (chat_rules.php:" . __LINE__ . ")");
                }

                // YAML 파싱 (PHP 7.1 호환)
                $rules_data = parseYamlContent($yaml_content);

                echo json_encode([
                    'success' => true,
                    'data' => $rules_data,
                    'file_size' => filesize($yaml_file),
                    'last_modified' => date('Y-m-d H:i:s', filemtime($yaml_file))
                ], JSON_UNESCAPED_UNICODE);
                break;

            case 'search_rules':
                $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
                $category = isset($_POST['category']) ? $_POST['category'] : 'all';

                if (!file_exists($yaml_file)) {
                    throw new Exception("rules.yaml 파일을 찾을 수 없습니다 (chat_rules.php:" . __LINE__ . ")");
                }

                $yaml_content = file_get_contents($yaml_file);
                $rules_data = parseYamlContent($yaml_content);

                // 필터링
                $filtered_rules = [];
                foreach ($rules_data['rules'] as $rule) {
                    $match_category = ($category === 'all' || strpos($rule['rule_id'], $category) === 0);
                    $match_keyword = empty($keyword) ||
                        stripos($rule['rule_id'], $keyword) !== false ||
                        stripos($rule['description'], $keyword) !== false ||
                        stripos($rule['rationale'], $keyword) !== false;

                    if ($match_category && $match_keyword) {
                        $filtered_rules[] = $rule;
                    }
                }

                echo json_encode([
                    'success' => true,
                    'rules' => $filtered_rules,
                    'count' => count($filtered_rules)
                ], JSON_UNESCAPED_UNICODE);
                break;

            default:
                throw new Exception("알 수 없는 액션: $action (chat_rules.php:" . __LINE__ . ")");
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/**
 * 간단한 YAML 파서 (PHP 7.1 호환)
 */
function parseYamlContent($content) {
    $lines = explode("\n", $content);
    $result = [
        'version' => '',
        'scenario' => '',
        'description' => '',
        'rules' => []
    ];

    $current_rule = null;
    $in_rules = false;
    $in_conditions = false;
    $in_action = false;
    $current_condition = [];

    foreach ($lines as $line_num => $line) {
        // 주석 및 빈 줄 건너뛰기
        if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
            continue;
        }

        $trimmed = trim($line);

        // 버전 정보
        if (strpos($line, 'version:') === 0) {
            $result['version'] = trim(str_replace(['version:', '"'], '', $line));
        }
        // 시나리오
        elseif (strpos($line, 'scenario:') === 0) {
            $result['scenario'] = trim(str_replace(['scenario:', '"'], '', $line));
        }
        // 설명
        elseif (strpos($line, 'description:') === 0 && !$in_rules) {
            $result['description'] = trim(str_replace(['description:', '"'], '', $line));
        }
        // 규칙 섹션 시작
        elseif (strpos($line, 'rules:') === 0) {
            $in_rules = true;
        }
        // 새 규칙 시작
        elseif ($in_rules && preg_match('/^\s{2}-\s*rule_id:\s*"?([^"]+)"?/', $line, $matches)) {
            if ($current_rule !== null) {
                $result['rules'][] = $current_rule;
            }
            $current_rule = [
                'rule_id' => $matches[1],
                'priority' => 0,
                'description' => '',
                'conditions' => [],
                'action' => [],
                'confidence' => 0,
                'rationale' => ''
            ];
            $in_conditions = false;
            $in_action = false;
        }
        // 우선순위
        elseif ($current_rule !== null && preg_match('/^\s+priority:\s*(\d+)/', $line, $matches)) {
            $current_rule['priority'] = (int)$matches[1];
        }
        // 설명
        elseif ($current_rule !== null && preg_match('/^\s+description:\s*"?([^"]*)"?/', $line, $matches)) {
            $current_rule['description'] = $matches[1];
        }
        // 신뢰도
        elseif ($current_rule !== null && preg_match('/^\s+confidence:\s*([\d.]+)/', $line, $matches)) {
            $current_rule['confidence'] = (float)$matches[1];
        }
        // 근거
        elseif ($current_rule !== null && preg_match('/^\s+rationale:\s*"?([^"]*)"?/', $line, $matches)) {
            $current_rule['rationale'] = $matches[1];
        }
        // conditions 시작
        elseif ($current_rule !== null && strpos($trimmed, 'conditions:') === 0) {
            $in_conditions = true;
            $in_action = false;
        }
        // action 시작
        elseif ($current_rule !== null && strpos($trimmed, 'action:') === 0) {
            $in_conditions = false;
            $in_action = true;
        }
        // 조건 파싱
        elseif ($in_conditions && $current_rule !== null && preg_match('/^\s+-\s*field:\s*"?([^"]+)"?/', $line, $matches)) {
            $current_condition = ['field' => $matches[1], 'operator' => '', 'value' => ''];
        }
        elseif ($in_conditions && $current_rule !== null && preg_match('/^\s+operator:\s*"?([^"]+)"?/', $line, $matches)) {
            $current_condition['operator'] = $matches[1];
        }
        elseif ($in_conditions && $current_rule !== null && preg_match('/^\s+value:\s*(.+)/', $line, $matches)) {
            $val = trim($matches[1]);
            // 배열 값 처리
            if (strpos($val, '[') === 0) {
                $val = preg_replace('/[\[\]"]/', '', $val);
            } else {
                $val = trim($val, '"');
            }
            $current_condition['value'] = $val;
            if (!empty($current_condition['field'])) {
                $current_rule['conditions'][] = $current_condition;
                $current_condition = [];
            }
        }
        // 액션 파싱
        elseif ($in_action && $current_rule !== null && preg_match('/^\s+-\s*"?([^"]+)"?/', $line, $matches)) {
            $current_rule['action'][] = trim($matches[1], '"');
        }
    }

    // 마지막 규칙 추가
    if ($current_rule !== null) {
        $result['rules'][] = $current_rule;
    }

    return $result;
}

// 카테고리 정의
$categories = [
    'CU' => ['name' => '① 개념이해', 'color' => '#3b82f6', 'icon' => '📘', 'desc' => 'Concept Understanding'],
    'TL' => ['name' => '② 유형학습', 'color' => '#8b5cf6', 'icon' => '📗', 'desc' => 'Type Learning'],
    'PS' => ['name' => '③ 문제풀이', 'color' => '#ef4444', 'icon' => '📕', 'desc' => 'Problem Solving'],
    'EN' => ['name' => '④ 오답노트', 'color' => '#f97316', 'icon' => '📙', 'desc' => 'Error Notes'],
    'QA' => ['name' => '⑤ 질의응답', 'color' => '#06b6d4', 'icon' => '💬', 'desc' => 'Q&A'],
    'RV' => ['name' => '⑥ 복습활동', 'color' => '#a855f7', 'icon' => '🔄', 'desc' => 'Review Activity'],
    'PJ' => ['name' => '⑦ 포모도르', 'color' => '#ec4899', 'icon' => '🍅', 'desc' => 'Pomodoro Journal'],
    'RC' => ['name' => '⑧ 귀가검사', 'color' => '#10b981', 'icon' => '🏠', 'desc' => 'Return Check'],
    'CR' => ['name' => '복합상황', 'color' => '#6366f1', 'icon' => '🔗', 'desc' => 'Complex Rules']
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 통합 규칙 뷰어 - Agent04</title>
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #a5b4fc;
            --primary-dark: #4338ca;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* 헤더 */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header-content p {
            opacity: 0.9;
            font-size: 14px;
        }

        /* 네비게이션 드롭다운 */
        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown select {
            padding: 12px 40px 12px 16px;
            font-size: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 10px;
            background: rgba(255,255,255,0.15);
            color: white;
            cursor: pointer;
            appearance: none;
            min-width: 200px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-dropdown select:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
        }

        .nav-dropdown select option {
            background: var(--card-bg);
            color: var(--text-primary);
            padding: 10px;
        }

        .nav-dropdown::after {
            content: '▼';
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            font-size: 10px;
            color: white;
        }

        /* 메타 정보 */
        .meta-info {
            background: var(--card-bg);
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            flex-wrap: wrap;
            gap: 16px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .meta-item strong {
            color: var(--text-primary);
        }

        /* 검색 및 필터 */
        .search-filter {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .search-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .filter-select {
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            min-width: 180px;
            cursor: pointer;
        }

        .search-btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* 카테고리 탭 */
        .category-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .category-tab {
            padding: 10px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: var(--card-bg);
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .category-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .category-tab.active {
            color: white;
            border-color: transparent;
        }

        .category-tab .count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        .category-tab.active .count {
            background: rgba(255,255,255,0.3);
        }

        /* 통계 카드 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .stat-card h3 {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .stat-card p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* 규칙 목록 */
        .rules-container {
            display: grid;
            gap: 16px;
        }

        .rule-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .rule-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .rule-header {
            padding: 16px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid var(--primary);
        }

        .rule-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .rule-id {
            font-family: 'Consolas', monospace;
            font-size: 13px;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
        }

        .rule-description {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }

        .rule-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .priority-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .priority-high {
            background: #fef2f2;
            color: #dc2626;
        }

        .priority-medium {
            background: #fffbeb;
            color: #d97706;
        }

        .priority-low {
            background: #f0fdf4;
            color: #16a34a;
        }

        .confidence-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #eff6ff;
            color: #2563eb;
        }

        .expand-icon {
            font-size: 12px;
            transition: transform 0.3s;
            color: var(--text-secondary);
        }

        .rule-card.expanded .expand-icon {
            transform: rotate(180deg);
        }

        .rule-body {
            display: none;
            padding: 0 20px 20px;
            border-top: 1px solid var(--border-color);
        }

        .rule-card.expanded .rule-body {
            display: block;
        }

        .rule-section {
            margin-top: 16px;
        }

        .rule-section h4 {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .conditions-list, .actions-list {
            list-style: none;
            padding: 0;
        }

        .conditions-list li, .actions-list li {
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 13px;
            font-family: 'Consolas', monospace;
        }

        .condition-field {
            color: #2563eb;
            font-weight: 600;
        }

        .condition-operator {
            color: #dc2626;
        }

        .condition-value {
            color: #16a34a;
        }

        .rationale {
            padding: 12px 16px;
            background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text-primary);
            border-left: 3px solid var(--primary);
        }

        /* 로딩 */
        .loading {
            text-align: center;
            padding: 60px;
            color: var(--text-secondary);
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border-color);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 빈 상태 */
        .empty-state {
            text-align: center;
            padding: 60px;
            color: var(--text-secondary);
        }

        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .meta-info {
                flex-direction: column;
                text-align: center;
            }

            .search-row {
                flex-direction: column;
            }

            .search-input {
                min-width: 100%;
            }

            .rule-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .rule-header-right {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="header">
            <div class="header-content">
                <h1>📚 통합 규칙 뷰어</h1>
                <p>Agent04: 학습활동별 취약점 분석 규칙 (rules/rules.yaml)</p>
            </div>
            <div class="nav-dropdown">
                <select id="pageNav" onchange="navigateToPage(this.value)">
                    <option value="">📑 페이지 이동</option>
                    <option value="chat03.php">📘 문제풀이 분석</option>
                    <option value="chat04.php">📙 오답노트 분석</option>
                    <option value="chat05.php">📗 질의응답 분석</option>
                    <option value="chat06.php">📕 복습활동 분석</option>
                    <option value="chat_rules.php" selected>📚 통합 규칙 뷰어</option>
                </select>
            </div>
        </div>

        <!-- 메타 정보 -->
        <div class="meta-info" id="metaInfo">
            <div class="meta-item">
                <span>📋</span>
                <span>버전: <strong id="fileVersion">-</strong></span>
            </div>
            <div class="meta-item">
                <span>📁</span>
                <span>파일 크기: <strong id="fileSize">-</strong></span>
            </div>
            <div class="meta-item">
                <span>🕐</span>
                <span>최종 수정: <strong id="lastModified">-</strong></span>
            </div>
            <div class="meta-item">
                <span>📊</span>
                <span>전체 규칙: <strong id="totalRules">0</strong>개</span>
            </div>
        </div>

        <!-- 검색 및 필터 -->
        <div class="search-filter">
            <div class="search-row">
                <input type="text" id="searchKeyword" class="search-input"
                       placeholder="규칙 ID, 설명, 근거 검색...">
                <select id="categoryFilter" class="filter-select">
                    <option value="all">모든 카테고리</option>
                    <?php foreach ($categories as $key => $cat): ?>
                    <option value="<?php echo $key; ?>"><?php echo $cat['icon'] . ' ' . $cat['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="search-btn" onclick="searchRules()">🔍 검색</button>
            </div>
        </div>

        <!-- 카테고리 탭 -->
        <div class="category-tabs" id="categoryTabs">
            <div class="category-tab active" data-category="all" style="background: var(--primary); border-color: var(--primary);">
                <span>📋</span>
                <span>전체</span>
                <span class="count" id="countAll">0</span>
            </div>
            <?php foreach ($categories as $key => $cat): ?>
            <div class="category-tab" data-category="<?php echo $key; ?>"
                 style="--tab-color: <?php echo $cat['color']; ?>">
                <span><?php echo $cat['icon']; ?></span>
                <span><?php echo $cat['name']; ?></span>
                <span class="count" id="count<?php echo $key; ?>">0</span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 통계 카드 -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card" style="border-left-color: #3b82f6;">
                <h3 id="statHighPriority">0</h3>
                <p>높은 우선순위 (90+)</p>
            </div>
            <div class="stat-card" style="border-left-color: #f59e0b;">
                <h3 id="statMediumPriority">0</h3>
                <p>중간 우선순위 (80-89)</p>
            </div>
            <div class="stat-card" style="border-left-color: #10b981;">
                <h3 id="statHighConfidence">0</h3>
                <p>높은 신뢰도 (0.9+)</p>
            </div>
            <div class="stat-card" style="border-left-color: #6366f1;">
                <h3 id="statCategories">9</h3>
                <p>카테고리 수</p>
            </div>
        </div>

        <!-- 규칙 목록 -->
        <div class="rules-container" id="rulesContainer">
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>규칙 데이터를 불러오는 중...</p>
            </div>
        </div>
    </div>

    <script>
        // 전역 변수
        let allRules = [];
        let rulesData = null;
        const currentCategory = 'all';

        // 카테고리 색상 매핑
        const categoryColors = {
            'CU': '#3b82f6',
            'TL': '#8b5cf6',
            'PS': '#ef4444',
            'EN': '#f97316',
            'QA': '#06b6d4',
            'RV': '#a855f7',
            'PJ': '#ec4899',
            'RC': '#10b981',
            'CR': '#6366f1',
            'default': '#6366f1'
        };

        // 카테고리 이름 매핑
        const categoryNames = {
            'CU': '개념이해',
            'TL': '유형학습',
            'PS': '문제풀이',
            'EN': '오답노트',
            'QA': '질의응답',
            'RV': '복습활동',
            'PJ': '포모도르',
            'RC': '귀가검사',
            'CR': '복합상황'
        };

        // 페이지 로드 시 규칙 불러오기
        document.addEventListener('DOMContentLoaded', function() {
            loadRules();
            setupEventListeners();
        });

        // 이벤트 리스너 설정
        function setupEventListeners() {
            // 카테고리 탭 클릭
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const category = this.dataset.category;
                    selectCategory(category);
                });
            });

            // 검색 입력 엔터키
            document.getElementById('searchKeyword').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchRules();
                }
            });
        }

        // 규칙 불러오기
        function loadRules() {
            const formData = new FormData();
            formData.append('action', 'load_rules');

            fetch('chat_rules.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    rulesData = data.data;
                    allRules = data.data.rules || [];

                    // 메타 정보 업데이트
                    document.getElementById('fileVersion').textContent = data.data.version || '-';
                    document.getElementById('fileSize').textContent = formatFileSize(data.file_size);
                    document.getElementById('lastModified').textContent = data.last_modified;
                    document.getElementById('totalRules').textContent = allRules.length;

                    // 카테고리별 카운트 업데이트
                    updateCategoryCounts();

                    // 통계 업데이트
                    updateStatistics();

                    // 규칙 표시
                    displayRules(allRules);
                } else {
                    showError(data.error);
                }
            })
            .catch(error => {
                showError('데이터 로드 중 오류: ' + error.message);
            });
        }

        // 파일 크기 포맷
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        // 카테고리별 카운트 업데이트
        function updateCategoryCounts() {
            const counts = { all: allRules.length };

            Object.keys(categoryColors).forEach(key => {
                if (key !== 'default') {
                    counts[key] = allRules.filter(r => r.rule_id.startsWith(key)).length;
                }
            });

            // DOM 업데이트
            document.getElementById('countAll').textContent = counts.all;
            Object.keys(counts).forEach(key => {
                const el = document.getElementById('count' + key);
                if (el) el.textContent = counts[key] || 0;
            });
        }

        // 통계 업데이트
        function updateStatistics() {
            const highPriority = allRules.filter(r => r.priority >= 90).length;
            const mediumPriority = allRules.filter(r => r.priority >= 80 && r.priority < 90).length;
            const highConfidence = allRules.filter(r => r.confidence >= 0.9).length;

            document.getElementById('statHighPriority').textContent = highPriority;
            document.getElementById('statMediumPriority').textContent = mediumPriority;
            document.getElementById('statHighConfidence').textContent = highConfidence;
        }

        // 카테고리 선택
        function selectCategory(category) {
            // 탭 활성화
            document.querySelectorAll('.category-tab').forEach(tab => {
                const isActive = tab.dataset.category === category;
                tab.classList.toggle('active', isActive);

                if (isActive) {
                    const color = tab.style.getPropertyValue('--tab-color') || 'var(--primary)';
                    tab.style.background = color;
                    tab.style.borderColor = color;
                    tab.style.color = 'white';
                } else {
                    tab.style.background = 'var(--card-bg)';
                    tab.style.borderColor = 'var(--border-color)';
                    tab.style.color = 'var(--text-primary)';
                }
            });

            // 필터 적용
            let filteredRules = allRules;
            if (category !== 'all') {
                filteredRules = allRules.filter(r => r.rule_id.startsWith(category));
            }

            displayRules(filteredRules);
        }

        // 검색
        function searchRules() {
            const keyword = document.getElementById('searchKeyword').value.trim().toLowerCase();
            const category = document.getElementById('categoryFilter').value;

            let filteredRules = allRules;

            // 카테고리 필터
            if (category !== 'all') {
                filteredRules = filteredRules.filter(r => r.rule_id.startsWith(category));
            }

            // 키워드 검색
            if (keyword) {
                filteredRules = filteredRules.filter(r =>
                    r.rule_id.toLowerCase().includes(keyword) ||
                    r.description.toLowerCase().includes(keyword) ||
                    (r.rationale && r.rationale.toLowerCase().includes(keyword))
                );
            }

            displayRules(filteredRules);
        }

        // 규칙 표시
        function displayRules(rules) {
            const container = document.getElementById('rulesContainer');

            if (rules.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="icon">📭</div>
                        <h3>검색 결과가 없습니다</h3>
                        <p>다른 검색어나 카테고리를 선택해 보세요.</p>
                    </div>
                `;
                return;
            }

            let html = '';
            rules.forEach((rule, index) => {
                const categoryPrefix = rule.rule_id.split('_')[0];
                const color = categoryColors[categoryPrefix] || categoryColors.default;
                const priorityClass = rule.priority >= 90 ? 'priority-high' :
                                     (rule.priority >= 80 ? 'priority-medium' : 'priority-low');

                html += `
                    <div class="rule-card" data-index="${index}">
                        <div class="rule-header" style="border-left-color: ${color};" onclick="toggleRule(this)">
                            <div class="rule-header-left">
                                <span class="rule-id" style="background: ${color}20; color: ${color};">${rule.rule_id}</span>
                                <span class="rule-description">${rule.description || '설명 없음'}</span>
                            </div>
                            <div class="rule-header-right">
                                <span class="priority-badge ${priorityClass}">P${rule.priority}</span>
                                <span class="confidence-badge">${(rule.confidence * 100).toFixed(0)}%</span>
                                <span class="expand-icon">▼</span>
                            </div>
                        </div>
                        <div class="rule-body">
                            ${renderRuleBody(rule)}
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // 규칙 상세 내용 렌더링
        function renderRuleBody(rule) {
            let html = '';

            // 조건
            if (rule.conditions && rule.conditions.length > 0) {
                html += `
                    <div class="rule-section">
                        <h4>📋 조건 (Conditions)</h4>
                        <ul class="conditions-list">
                            ${rule.conditions.map(c => `
                                <li>
                                    <span class="condition-field">${c.field}</span>
                                    <span class="condition-operator">${c.operator}</span>
                                    <span class="condition-value">${c.value}</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                `;
            }

            // 액션
            if (rule.action && rule.action.length > 0) {
                html += `
                    <div class="rule-section">
                        <h4>⚡ 액션 (Actions)</h4>
                        <ul class="actions-list">
                            ${rule.action.map(a => `<li>${a}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }

            // 근거
            if (rule.rationale) {
                html += `
                    <div class="rule-section">
                        <h4>💡 근거 (Rationale)</h4>
                        <div class="rationale">${rule.rationale}</div>
                    </div>
                `;
            }

            return html;
        }

        // 규칙 토글
        function toggleRule(header) {
            const card = header.closest('.rule-card');
            card.classList.toggle('expanded');
        }

        // 에러 표시
        function showError(message) {
            const container = document.getElementById('rulesContainer');
            container.innerHTML = `
                <div class="empty-state" style="color: var(--danger);">
                    <div class="icon">⚠️</div>
                    <h3>오류 발생</h3>
                    <p>${message}</p>
                </div>
            `;
        }

        // 페이지 이동
        function navigateToPage(page) {
            if (page) {
                window.location.href = page;
            }
        }
    </script>
</body>
</html>
<?php
/**
 * 관련 DB 테이블: mdl_agent04_chat_data
 * - id (bigint, auto_increment, primary key)
 * - student_id (bigint, not null)
 * - course_id (bigint, not null)
 * - nagent (int, default 4)
 * - data_type (varchar(100), not null) - 예: 'rules_view_log'
 * - data_content (longtext) - JSON 형식 데이터
 * - created_at (datetime)
 * - updated_at (datetime)
 */
?>
