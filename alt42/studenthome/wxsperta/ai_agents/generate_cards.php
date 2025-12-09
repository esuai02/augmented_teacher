<?php
// 카드 데이터 로드
require_once 'cards_data.php';

// 카드별 index.php 생성 함수
function generateCardPage($card) {
    $category_path = $card['category'];
    $card_path = "{$category_path}/{$card['id']}";
    
    // 연결된 카드 정보 생성
    $connections_html = '';
    if (!empty($card['connections'])) {
        $connections_html = '<ul>';
        foreach ($card['connections'] as $conn_id) {
            global $cards_data;
            // 연결된 카드 찾기
            foreach ($cards_data as $conn_card) {
                if ($conn_card['id'] == $conn_id) {
                    $connections_html .= "<li><strong>{$conn_card['name']} (카드 {$conn_card['number']}):</strong> {$conn_card['subtitle']}</li>";
                    break;
                }
            }
        }
        $connections_html .= '</ul>';
    }
    
    // 프로젝트 HTML 생성
    $projects_html = '';
    foreach ($card['projects'] as $project) {
        $subprojects_html = '';
        foreach ($project['subprojects'] as $subproject) {
            $subprojects_html .= <<<HTML
                            <div class="sub-project">
                                <input type="checkbox" class="project-checkbox" data-project-id="{$subproject['id']}">
                                <h4>{$subproject['title']}</h4>
                                <p>{$subproject['description']}</p>
                            </div>
HTML;
        }
        
        $projects_html .= <<<HTML
                    <!-- 프로젝트 -->
                    <div class="main-project">
                        <div class="project-header">
                            <input type="checkbox" class="project-checkbox" data-project-id="{$project['id']}">
                            <strong>{$project['title']}</strong>
                        </div>
                        <p>{$project['description']}</p>
                        
                        <div class="sub-projects">
{$subprojects_html}
                        </div>
                    </div>

HTML;
    }
    
    $content = <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$card['name']} - {$card['subtitle']}</title>
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
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>{$card['name']}</h1>
            <p>{$card['subtitle']}</p>
        </header>

        <nav class="category-nav">
            <a href="../../index.php" class="nav-item">← 메인으로</a>
            <a href="#overview" class="nav-item">개요</a>
            <a href="#projects" class="nav-item">프로젝트</a>
            <a href="#connections" class="nav-item">연계</a>
        </nav>

        <main>
            <section id="overview" class="category-section">
                <h2>개요</h2>
                <p>{$card['description']}</p>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: 0%">0%</div>
                </div>
            </section>

            <section id="projects" class="category-section">
                <h2>프로젝트 구조</h2>
                <div class="project-list">
{$projects_html}
                </div>
            </section>

            <section id="connections" class="category-section">
                <h2>다른 에이전트와의 연계</h2>
                <div class="connection-info">
                    <h3>주요 연계 에이전트:</h3>
                    {$connections_html}
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
    
    return $content;
}

// 모든 카드에 대해 index.php 생성
foreach ($cards_data as $card) {
    if ($card['id'] == '01_time_capsule') {
        // 이미 생성된 첫 번째 카드는 건너뛰기
        continue;
    }
    
    $category_path = $card['category'];
    $card_path = "{$category_path}/{$card['id']}";
    $file_path = __DIR__ . "/{$card_path}/index.php";
    
    // 디렉토리가 존재하는지 확인
    if (!is_dir(dirname($file_path))) {
        echo "디렉토리 없음: " . dirname($file_path) . "\n";
        continue;
    }
    
    // 파일 생성
    $content = generateCardPage($card);
    file_put_contents($file_path, $content);
    echo "생성 완료: {$card_path}/index.php\n";
}

echo "모든 카드 페이지 생성이 완료되었습니다.\n";
?>