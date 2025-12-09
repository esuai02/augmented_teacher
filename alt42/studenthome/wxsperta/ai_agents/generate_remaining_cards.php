<?php
// Include the cards data
include 'cards_data.php';

// Template function to generate index.php content
function generateIndexContent($card) {
    $connections_html = '';
    if (!empty($card['connections'])) {
        $connections_html .= '<ul>';
        foreach ($card['connections'] as $connection_id) {
            // Find the connected card details
            global $cards;
            $connected_card = null;
            foreach ($cards as $c) {
                if ($c['id'] === $connection_id) {
                    $connected_card = $c;
                    break;
                }
            }
            if ($connected_card) {
                $connections_html .= sprintf(
                    '<li><strong>%s (카드 %d):</strong> 연계된 기능 설명</li>',
                    htmlspecialchars($connected_card['name']),
                    $connected_card['number']
                );
            }
        }
        $connections_html .= '</ul>';
    }

    $projects_html = '';
    foreach ($card['projects'] as $project) {
        $subprojects_html = '';
        foreach ($project['subprojects'] as $subproject) {
            $subprojects_html .= sprintf('
                            <div class="sub-project">
                                <input type="checkbox" class="project-checkbox" data-project-id="%s">
                                <h4>%s</h4>
                                <p>%s</p>
                            </div>',
                htmlspecialchars($subproject['id']),
                htmlspecialchars($subproject['title']),
                htmlspecialchars($subproject['description'])
            );
        }

        $projects_html .= sprintf('
                    <!-- 프로젝트 %d -->
                    <div class="main-project">
                        <div class="project-header">
                            <input type="checkbox" class="project-checkbox" data-project-id="%s">
                            <strong>%d. %s</strong>
                        </div>
                        <p>%s</p>
                        
                        <div class="sub-projects">%s
                        </div>
                    </div>',
            array_search($project, $card['projects']) + 1,
            htmlspecialchars($project['id']),
            array_search($project, $card['projects']) + 1,
            htmlspecialchars($project['title']),
            htmlspecialchars($project['description']),
            $subprojects_html
        );
    }

    return <<<HTML
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
            <h1>{$card['name']}</h1>
            <p>{$card['description']}</p>
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
                <p>{$card['name']}는 {$card['description']} AI 에이전트입니다.</p>
                
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

            <section class="category-section">
                <h2>실행 도구</h2>
                <div class="tool-container">
                    <a href="tools/tool1.php" class="card-link">도구 1</a>
                    <a href="tools/tool2.php" class="card-link">도구 2</a>
                    <a href="tools/tool3.php" class="card-link">도구 3</a>
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
}

// List of cards that already have index.php
$existing_cards = ['01_time_capsule', '02_timeline_synthesizer', '05_motivation_engine', '12_external_branding', '15_timecapsule_ceo'];

// Generate index.php for remaining cards
$generated_count = 0;
foreach ($cards as $card) {
    if (!in_array($card['id'], $existing_cards)) {
        $category_path = $card['category'];
        $card_path = $card['id'];
        $file_path = __DIR__ . "/{$category_path}/{$card_path}/index.php";
        
        // Create directory if it doesn't exist
        $dir_path = dirname($file_path);
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0777, true);
        }
        
        // Generate and write the content
        $content = generateIndexContent($card);
        file_put_contents($file_path, $content);
        $generated_count++;
        echo "Generated: {$file_path}\n";
    }
}

echo "\nTotal cards generated: {$generated_count}\n";
?>