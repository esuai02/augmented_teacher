<?php
/**
 * Q-MIND: 성운 지도 (Knowledge Network)
 */
$cards_data = include(__DIR__ . '/../../cards_data.php');
$current_agent = null;
$entangled_agents = [];

foreach ($cards_data as $agent) {
    if ($agent['number'] === 19) {
        $current_agent = $agent;
        if (!empty($agent['connections'])) {
            foreach ($agent['connections'] as $conn_id) {
                foreach ($cards_data as $other) {
                    $match = false;
                    if (is_numeric($conn_id) && $other['number'] == $conn_id) $match = true;
                    elseif (is_string($conn_id) && strpos($conn_id, sprintf('%02d', $other['number'])) !== false) $match = true;
                    elseif ($other['id'] === $conn_id) $match = true;
                    if ($match) $entangled_agents[] = $other;
                }
            }
        }
        break;
    }
}
$projects = $current_agent['projects'] ?? [];
$base_path = '../..';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_agent['icon']; ?> Q-MIND: <?php echo htmlspecialchars($current_agent['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/styles/quantum.css">
</head>
<body>
    <div class="quantum-container">
        <header class="probability-field">
            <canvas id="particleCanvas"></canvas>
            <div class="state-indicator">|ψ⟩ 중첩 상태</div>
            <h1><?php echo $current_agent['icon']; ?> <?php echo htmlspecialchars($current_agent['name']); ?></h1>
            <p class="subtitle"><?php echo htmlspecialchars($current_agent['subtitle'] ?? $current_agent['description']); ?></p>
            <button class="observe-btn" onclick="startObservation()">🔭 관측 시작</button>
        </header>

        <nav class="quantum-nav">
            <a href="<?php echo $base_path; ?>/index.php" class="nav-item">← 탐험 지도</a>
            <a href="#future-anchor" class="nav-item">🎯 미래 앵커링</a>
            <a href="#fluctuation" class="nav-item">⚡ 에너지 모니터</a>
            <a href="#projects" class="nav-item">📋 프로젝트</a>
            <a href="#entanglement" class="nav-item">🔗 얽힘 네트워크</a>
        </nav>

        <section id="future-anchor" class="quantum-section">
            <div class="section-header">
                <div class="section-icon">🎯</div>
                <div>
                    <h2 class="section-title">미래 앵커링</h2>
                    <p class="section-subtitle">"따로따로인 지식들을 연결하면 성운처럼 아름다운 그림이 돼!"</p>
                </div>
            </div>
            <div class="reverse-timeline">
                <div class="timeline-node current"><div class="timeline-dot"></div><span class="timeline-label">단편</span></div>
                <div class="timeline-node"><div class="timeline-dot"></div><span class="timeline-label">연결</span></div>
                <div class="timeline-node"><div class="timeline-dot"></div><span class="timeline-label">패턴</span></div>
                <div class="timeline-node future"><div class="timeline-dot"></div><span class="timeline-label">✨ 성운</span></div>
            </div>
            <div class="future-memory-generator">
                <p class="memory-prompt">💭 모든 지식이 연결되어 하나의 그림이 되었을 때 어떤 감정을 느끼고 싶나요?</p>
                <div class="emotion-selector">
                    <button class="emotion-btn" data-emotion="thrilled" data-color="#ff4081"><span class="emoji">🌌</span> 경이로움</button>
                    <button class="emotion-btn" data-emotion="relieved" data-color="#00e676"><span class="emoji">😌</span> 명쾌함</button>
                    <button class="emotion-btn" data-emotion="confident" data-color="#00e5ff"><span class="emoji">💪</span> 통찰력</button>
                    <button class="emotion-btn" data-emotion="proud" data-color="#b388ff"><span class="emoji">✨</span> 뿌듯함</button>
                </div>
            </div>
        </section>

        <section id="fluctuation" class="quantum-section">
            <div class="section-header">
                <div class="section-icon">⚡</div>
                <div><h2 class="section-title">양자 요동 모니터</h2><p class="section-subtitle">"헤매임은 터널링을 위한 에너지"</p></div>
            </div>
            <div class="energy-dashboard">
                <div class="energy-header">
                    <div><span class="energy-level">30%</span><span class="energy-label">인지 에너지</span></div>
                    <span class="tunneling-alert">⚠️ 터널링 임박!</span>
                </div>
                <canvas id="waveformCanvas"></canvas>
                <div class="energy-bar-container">
                    <div class="energy-bar-label"><span>Low Energy</span><span>High Energy</span></div>
                    <div class="energy-bar"><div class="energy-bar-fill" style="width: 30%"></div></div>
                </div>
            </div>
        </section>

        <section id="tunneling" class="quantum-section">
            <div class="section-header">
                <div class="section-icon">🔮</div>
                <div><h2 class="section-title">터널링 인젝션</h2><p class="section-subtitle">"관점을 전환하면 장벽을 뚫을 수 있다"</p></div>
            </div>
            <div class="paradox-generator">
                <p class="paradox-question">"버튼을 눌러 새로운 관점을 얻어보세요"</p>
                <button class="shift-perspective-btn">🔄 관점 전환</button>
            </div>
        </section>

        <section id="projects" class="quantum-section projects-section">
            <div class="section-header">
                <div class="section-icon">📋</div>
                <div><h2 class="section-title"><?php echo htmlspecialchars($current_agent['name']); ?> 프로젝트</h2><p class="section-subtitle">모든 작업 완료 시 파동함수가 붕괴합니다</p></div>
            </div>
            <?php foreach ($projects as $project): ?>
            <div class="project-card">
                <div class="project-header">
                    <input type="checkbox" class="project-checkbox" data-project-id="<?php echo htmlspecialchars($project['id']); ?>">
                    <span class="project-title"><?php echo htmlspecialchars($project['title']); ?></span>
                    <span class="project-toggle">▼</span>
                </div>
                <p class="project-description"><?php echo htmlspecialchars($project['description']); ?></p>
                <?php if (!empty($project['subprojects'])): ?>
                <div class="sub-projects">
                    <?php foreach ($project['subprojects'] as $sub): ?>
                    <div class="sub-project">
                        <div class="sub-project-header">
                            <input type="checkbox" class="project-checkbox" data-project-id="<?php echo htmlspecialchars($sub['id']); ?>">
                            <h4><?php echo htmlspecialchars($sub['title']); ?></h4>
                        </div>
                        <p><?php echo htmlspecialchars($sub['description']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </section>

        <section class="quantum-section collapse-section">
            <div class="section-header" style="justify-content: center;">
                <div class="section-icon">💎</div>
                <div><h2 class="section-title">붕괴 관측소</h2><p class="section-subtitle">프로젝트 완료 시 파동함수가 결정체로 붕괴</p></div>
            </div>
            <canvas id="collapseCanvas"></canvas>
            <div class="collapse-message">✨ 관측 완료! 지식이 결정화되었습니다.</div>
            <div class="level-indicator">현재 상태: <span class="level">Level 1</span></div>
        </section>

        <section id="entanglement" class="quantum-section">
            <div class="section-header">
                <div class="section-icon">🔗</div>
                <div><h2 class="section-title">양자 얽힘 네트워크</h2><p class="section-subtitle">"얽힌 에이전트들은 즉시 상호 영향"</p></div>
            </div>
            <div class="entangled-agents">
                <?php foreach ($entangled_agents as $ea): 
                    $path = '';
                    switch($ea['category']) {
                        case 'future_design': $path = "../../future_design/{$ea['id']}/index.php"; break;
                        case 'execution': $path = "../../execution/{$ea['id']}/index.php"; break;
                        case 'branding': $path = "../../branding/{$ea['id']}/index.php"; break;
                        case 'knowledge_management': $path = "../{$ea['id']}/index.php"; break;
                    }
                ?>
                <div class="entangled-agent-card">
                    <div class="agent-icon"><?php echo $ea['icon']; ?></div>
                    <h3 class="agent-name"><?php echo htmlspecialchars($ea['name']); ?></h3>
                    <p class="agent-subtitle"><?php echo htmlspecialchars($ea['subtitle'] ?? ''); ?></p>
                    <div class="entanglement-indicator"><span class="entanglement-line"></span>양자 얽힘<span class="entanglement-line"></span></div>
                    <a href="<?php echo $path; ?>" class="agent-link">🚀 탐험하기</a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <footer class="quantum-footer">
            <p>🌌 Q-MIND: "Don't fix your past. Collapse your future."</p>
        </footer>
    </div>

    <script>const agentConfig = { id: '<?php echo $current_agent['id']; ?>' };</script>
    <script>const entangledAgentsData = <?php echo json_encode(array_map(function($a) { return ['id'=>$a['id'],'number'=>$a['number'],'name'=>$a['name'],'icon'=>$a['icon'],'category'=>$a['category']]; }, $entangled_agents)); ?>;</script>
    <script src="<?php echo $base_path; ?>/scripts/quantum.js"></script>
</body>
</html>
