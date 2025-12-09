<?php
// 누락된 카드 페이지들을 생성하는 스크립트

require_once __DIR__ . '/cards_data.php';

// 에이전트 ID와 폴더 이름 매핑
$agentNameMap = [
    1 => '01_time_capsule',
    2 => '02_timeline_synthesizer', 
    3 => '03_growth_elevator',
    4 => '04_performance_engine',
    5 => '05_motivation_engine',
    6 => '06_swot_analyzer',
    7 => '07_daily_command',
    8 => '08_inner_branding',
    9 => '09_vertical_explorer',
    10 => '10_resource_gardener',
    11 => '11_execution_pipeline',
    12 => '12_external_branding',
    13 => '13_growth_trigger',
    14 => '14_competitive_strategist',
    15 => '15_timecapsule_ceo',
    16 => '16_ai_gardener',
    17 => '17_neural_architect',
    18 => '18_info_hub',
    19 => '19_knowledge_network',
    20 => '20_knowledge_crystal',
    21 => '21_flexible_backbone'
];

// 카테고리 경로 매핑
$categoryPaths = [
    'future_design' => 'future_design',
    'execution' => 'execution',
    'branding' => 'branding',
    'knowledge_management' => 'knowledge_management'
];

// 이미 존재하는 카드들
$existingCards = [
    '01_time_capsule',
    '02_timeline_synthesizer',
    '03_growth_elevator', 
    '05_motivation_engine',
    '12_external_branding',
    '15_timecapsule_ceo'
];

// 각 카드에 대해 처리
foreach ($cards_data as $card) {
    $agentNumId = $card['number'];
    $agentFolder = $agentNameMap[$agentNumId];
    
    // 이미 존재하는 카드는 건너뛰기
    if (in_array($agentFolder, $existingCards)) {
        echo "Skipping existing card: {$agentFolder}\n";
        continue;
    }
    
    $category = $card['category'];
    $categoryPath = $categoryPaths[$category];
    
    $dirPath = __DIR__ . "/{$categoryPath}/{$agentFolder}";
    $filePath = "{$dirPath}/index.php";
    
    // 디렉토리 생성
    if (!file_exists($dirPath)) {
        mkdir($dirPath, 0777, true);
        echo "Created directory: {$dirPath}\n";
    }
    
    // 카드 페이지 생성
    generateCardPage($card, $dirPath, $filePath, $agentNumId);
    echo "Generated card page: {$filePath}\n";
}

function generateCardPage($card, $dirPath, $filePath, $agentNumId) {
    $cardName = $card['name'];
    $cardSubtitle = $card['subtitle'];
    $cardDescription = $card['description'];
    $connections = $card['connections'];
    $projects = $card['projects'];
    $cardId = $card['id'];
    
    // 연결 정보 처리
    $connectionInfo = [];
    foreach ($connections as $conn) {
        // 연결된 카드의 정보를 찾기
        global $cards_data;
        foreach ($cards_data as $c) {
            if ($c['id'] === $conn) {
                $connectionInfo[] = [
                    'name' => $c['name'],
                    'card_number' => $c['number'],
                    'description' => '프로젝트 간 시너지 효과 창출'
                ];
                break;
            }
        }
    }
    
    $content = <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$cardName} - {$cardSubtitle}</title>
    <link rel="stylesheet" href="../../styles/main.css">
    <style>
        .project-header {
            cursor: pointer;
            user-select: none;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        .project-header:hover {
            background-color: #e9ecef;
        }
        .project-checkbox {
            margin-right: 0.5rem;
        }
        .progress-container {
            background-color: #e9ecef;
            border-radius: 4px;
            height: 30px;
            margin: 1rem 0;
            position: relative;
        }
        .progress-bar {
            background-color: #28a745;
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .sub-projects.collapsed {
            display: none;
        }
        .agent-card.dimmed {
            opacity: 0.3;
        }
        .agent-card.highlighted {
            border: 3px solid #3498db;
            background-color: #e3f2fd;
        }
        .agent-card.connected {
            border: 2px solid #2ecc71;
            background-color: #e8f8f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>{$cardName}</h1>
            <p>{$cardSubtitle}</p>
        </header>

        <nav class="category-nav">
            <a href="../../index.php" class="nav-item">← 메인으로</a>
            <a href="#overview" class="nav-item">개요</a>
            <a href="#projects" class="nav-item">프로젝트</a>
            <a href="#connections" class="nav-item">연계</a>
            <a href="../../wxsperta_integrated.php?agent={$agentNumId}" class="nav-item" style="background-color: #3498db; color: white;">대화 시작</a>
        </nav>

        <main>
            <section id="overview" class="category-section">
                <h2>개요</h2>
                <p>{$cardDescription}</p>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: 0%">0%</div>
                </div>
            </section>

            <section id="projects" class="category-section">
                <h2>프로젝트 구조</h2>
                <div class="project-list">
HTML;

    foreach ($projects as $projectIndex => $project) {
        $projectNum = $projectIndex + 1;
        $projectId = substr($cardId, 0, 2) . "-p{$projectNum}";
        
        $content .= <<<HTML
                    <!-- 프로젝트 {$projectNum} -->
                    <div class="main-project">
                        <div class="project-header">
                            <input type="checkbox" class="project-checkbox" data-project-id="{$projectId}">
                            <strong>{$projectNum}. {$project['title']}</strong>
                        </div>
                        <p>{$project['description']}</p>
                        
                        <div class="sub-projects">
HTML;

        foreach ($project['subprojects'] as $subIndex => $subproject) {
            $subNum = $subIndex + 1;
            $subProjectId = "{$projectId}-{$subNum}";
            
            $content .= <<<HTML
                            <div class="sub-project">
                                <input type="checkbox" class="project-checkbox" data-project-id="{$subProjectId}">
                                <h4>{$subproject['title']}</h4>
                                <p>{$subproject['description']}</p>
                            </div>
HTML;
        }
        
        $content .= <<<HTML
                        </div>
                    </div>

HTML;
    }
    
    $content .= <<<HTML
                </div>
            </section>

            <section id="connections" class="category-section">
                <h2>다른 에이전트와의 연계</h2>
                <div class="connection-info">
                    <h3>주요 연계 에이전트:</h3>
                    <ul>
HTML;

    foreach ($connectionInfo as $connection) {
        $content .= <<<HTML
                        <li><strong>{$connection['name']} (카드 {$connection['card_number']}):</strong> {$connection['description']}</li>
HTML;
    }
    
    $content .= <<<HTML
                    </ul>
                </div>
            </section>

            <section class="category-section">
                <h2>실행 도구</h2>
                <div class="tool-container">
                    <p>실행 도구가 준비 중입니다.</p>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2024 AI 에이전트 프로젝트 시스템. 모든 권리 보유.</p>
        </footer>
    </div>

    <script src="../../scripts/main.js"></script>
</body>
</html>
HTML;

    file_put_contents($filePath, $content);
}

echo "Card generation complete!\n";