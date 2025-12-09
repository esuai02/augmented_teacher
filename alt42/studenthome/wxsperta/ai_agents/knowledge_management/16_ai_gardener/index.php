<?php
/**
 * Q-MIND: AI 정원사 (Quantum Knowledge Gardener)
 * 
 * 양자 학습 모델 기반 지식 구조화 시스템
 * - 미래로의 붕괴(Collapse into the Future)
 * - 양자 요동(Quantum Fluctuation)을 통한 터널링
 */

// 에이전트 데이터 로드
$cards_data = include(__DIR__ . '/../../cards_data.php');

// 현재 에이전트 정보 (16번: AI 정원사)
$current_agent = null;
$entangled_agents = [];

foreach ($cards_data as $agent) {
    if ($agent['number'] === 16) {
        $current_agent = $agent;
        
        // 연결된 에이전트 찾기
        if (!empty($agent['connections'])) {
            foreach ($agent['connections'] as $conn_id) {
                foreach ($cards_data as $other) {
                    // ID 매칭 (숫자 또는 문자열)
                    $match = false;
                    if (is_numeric($conn_id) && $other['number'] == $conn_id) {
                        $match = true;
                    } elseif (is_string($conn_id) && strpos($conn_id, sprintf('%02d', $other['number'])) !== false) {
                        $match = true;
                    } elseif ($other['id'] === $conn_id) {
                        $match = true;
                    }
                    
                    if ($match) {
                        $entangled_agents[] = $other;
                    }
                }
            }
        }
        break;
    }
}

// 프로젝트 데이터
$projects = $current_agent['projects'] ?? [];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌱 Q-MIND: <?php echo htmlspecialchars($current_agent['name'] ?? 'AI 정원사'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="quantum_styles.css">
</head>
<body>
    <div class="quantum-container">
        
        <!-- ==================== 헤더: 가능성 구름 ==================== -->
        <header class="probability-field">
            <canvas id="particleCanvas"></canvas>
            
            <div class="state-indicator">|ψ⟩ 중첩 상태</div>
            
            <h1>🌱 <?php echo htmlspecialchars($current_agent['name'] ?? 'AI 정원사'); ?></h1>
            <p class="subtitle"><?php echo htmlspecialchars($current_agent['subtitle'] ?? '지식 가꾸기를 통한 지식 구조화'); ?></p>
            
            <button class="observe-btn" onclick="startObservation()">
                🔭 관측 시작
            </button>
        </header>

        <!-- ==================== 네비게이션 ==================== -->
        <nav class="quantum-nav">
            <a href="../../index.php" class="nav-item">← 탐험 지도</a>
            <a href="#future-anchor" class="nav-item">🎯 미래 앵커링</a>
            <a href="#fluctuation" class="nav-item">⚡ 에너지 모니터</a>
            <a href="#tunneling" class="nav-item">🔮 터널링</a>
            <a href="#projects" class="nav-item">📋 프로젝트</a>
            <a href="#entanglement" class="nav-item">🔗 얽힘 네트워크</a>
        </nav>

        <!-- ==================== 미래 앵커링 섹션 ==================== -->
        <section id="future-anchor" class="future-anchor-section">
            <div class="section-header">
                <div class="section-icon">🎯</div>
                <div>
                    <h2 class="section-title">미래 앵커링 (Future Anchoring)</h2>
                    <p class="section-subtitle">"미래가 현재를 끌어당긴다" - 강력한 목표 이미지가 파동함수 붕괴 방향을 결정합니다</p>
                </div>
            </div>
            
            <!-- 역방향 타임라인 -->
            <div class="reverse-timeline">
                <div class="timeline-node current">
                    <div class="timeline-dot"></div>
                    <span class="timeline-label">현재</span>
                </div>
                <div class="timeline-node">
                    <div class="timeline-dot"></div>
                    <span class="timeline-label">1주 후</span>
                </div>
                <div class="timeline-node">
                    <div class="timeline-dot"></div>
                    <span class="timeline-label">1개월 후</span>
                </div>
                <div class="timeline-node future">
                    <div class="timeline-dot"></div>
                    <span class="timeline-label">✨ 마스터</span>
                </div>
            </div>
            
            <!-- 미래 기억 생성기 -->
            <div class="future-memory-generator">
                <p class="memory-prompt">
                    💭 지식 그래프를 완성한 미래의 당신은 어떤 감정을 느끼고 있나요?
                </p>
                <div class="emotion-selector">
                    <button class="emotion-btn" data-emotion="thrilled" data-color="#ff4081">
                        <span class="emoji">⚡</span> 짜릿함
                    </button>
                    <button class="emotion-btn" data-emotion="relieved" data-color="#00e676">
                        <span class="emoji">😌</span> 안도감
                    </button>
                    <button class="emotion-btn" data-emotion="confident" data-color="#00e5ff">
                        <span class="emoji">💪</span> 자신감
                    </button>
                    <button class="emotion-btn" data-emotion="proud" data-color="#b388ff">
                        <span class="emoji">🏆</span> 뿌듯함
                    </button>
                </div>
                </div>
            </section>

        <!-- ==================== 양자 요동 대시보드 ==================== -->
        <section id="fluctuation" class="fluctuation-section">
            <div class="section-header">
                <div class="section-icon">⚡</div>
                <div>
                    <h2 class="section-title">양자 요동 모니터 (Fluctuation Monitor)</h2>
                    <p class="section-subtitle">"헤매임은 터널링을 위한 에너지" - 당신의 탐구 에너지를 실시간으로 관측합니다</p>
                </div>
                        </div>
                        
            <div class="energy-dashboard">
                <div class="energy-header">
                    <div>
                        <span class="energy-level">30%</span>
                        <span class="energy-label">인지 에너지</span>
                    </div>
                    <span class="tunneling-alert">⚠️ 터널링 임박!</span>
                            </div>
                
                <canvas id="waveformCanvas"></canvas>
                
                <div class="energy-bar-container">
                    <div class="energy-bar-label">
                        <span>Low Energy</span>
                        <span>High Energy (터널링 가능)</span>
                            </div>
                    <div class="energy-bar">
                        <div class="energy-bar-fill" style="width: 30%"></div>
                            </div>
                        </div>
                    </div>
        </section>

        <!-- ==================== 터널링 인젝션 ==================== -->
        <section id="tunneling" class="tunneling-section">
            <div class="section-header">
                <div class="section-icon">🔮</div>
                <div>
                    <h2 class="section-title">터널링 인젝션 (Tunneling Injection)</h2>
                    <p class="section-subtitle">"논리를 우회하면 장벽을 뚫을 수 있다" - 역설적 질문으로 사고의 벽을 뚫습니다</p>
                </div>
            </div>
            
            <div class="paradox-generator">
                <p class="paradox-question">
                    "버튼을 눌러 새로운 관점을 얻어보세요"
                </p>
                <button class="shift-perspective-btn">
                    🔄 관점 전환
                </button>
            </div>
        </section>
        
        <!-- ==================== 프로젝트 섹션 ==================== -->
        <section id="projects" class="projects-section">
            <div class="section-header">
                <div class="section-icon">📋</div>
                <div>
                    <h2 class="section-title">지식 농장 프로젝트</h2>
                    <p class="section-subtitle">각 프로젝트를 완료할 때마다 에너지가 축적됩니다. 모든 작업 완료 시 파동함수가 붕괴합니다.</p>
                </div>
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

        <!-- ==================== 붕괴 시각화 ==================== -->
        <section class="collapse-section">
            <div class="section-header" style="justify-content: center;">
                <div class="section-icon">💎</div>
                <div>
                    <h2 class="section-title">붕괴 관측소 (Collapse Observatory)</h2>
                    <p class="section-subtitle">"모든 프로젝트 완료 시 파동함수가 결정체로 붕괴합니다"</p>
                </div>
                        </div>
            
            <canvas id="collapseCanvas"></canvas>
                        
            <div class="collapse-message">
                ✨ 관측 완료! 지식이 결정화되었습니다.
                            </div>
            
            <div class="level-indicator">
                현재 상태: <span class="level">Level 1</span>
                            </div>
        </section>
        
        <!-- ==================== 에이전트 얽힘 네트워크 ==================== -->
        <section id="entanglement" class="entanglement-section">
            <div class="section-header">
                <div class="section-icon">🔗</div>
                <div>
                    <h2 class="section-title">양자 얽힘 네트워크 (Entanglement Network)</h2>
                    <p class="section-subtitle">"얽힌 에이전트들은 즉시 상호 영향을 받습니다" - 하나가 변하면 연결된 모든 것이 변합니다</p>
                            </div>
                        </div>
            
            <div class="entangled-agents">
                <?php foreach ($entangled_agents as $agent): ?>
                <div class="entangled-agent-card">
                    <div class="agent-icon"><?php echo $agent['icon']; ?></div>
                    <h3 class="agent-name"><?php echo htmlspecialchars($agent['name']); ?></h3>
                    <p class="agent-subtitle"><?php echo htmlspecialchars($agent['subtitle'] ?? $agent['description']); ?></p>
                    
                    <div class="entanglement-indicator">
                        <span class="entanglement-line"></span>
                        양자 얽힘 상태
                        <span class="entanglement-line"></span>
                    </div>
                    
                    <?php
                    // 에이전트 링크 생성
                    $agent_path = '';
                    switch ($agent['category']) {
                        case 'future_design':
                            $agent_path = "../../future_design/{$agent['id']}/index.php";
                            break;
                        case 'execution':
                            $agent_path = "../../execution/{$agent['id']}/index.php";
                            break;
                        case 'branding':
                            $agent_path = "../../branding/{$agent['id']}/index.php";
                            break;
                        case 'knowledge_management':
                            $agent_path = "../{$agent['id']}/index.php";
                            break;
                    }
                    ?>
                    <a href="<?php echo $agent_path; ?>" class="agent-link">
                        🚀 탐험하기
                    </a>
                </div>
                <?php endforeach; ?>

                <?php if (empty($entangled_agents)): ?>
                <div class="entangled-agent-card">
                    <div class="agent-icon">🌌</div>
                    <h3 class="agent-name">성운 지도</h3>
                    <p class="agent-subtitle">지식들을 연결하여 성운처럼 아름다운 그림을 만듭니다</p>
                    <div class="entanglement-indicator">
                        <span class="entanglement-line"></span>
                        양자 얽힘 상태
                        <span class="entanglement-line"></span>
                    </div>
                    <a href="../19_knowledge_network/index.php" class="agent-link">🚀 탐험하기</a>
                </div>
                
                <div class="entangled-agent-card">
                    <div class="agent-icon">🔬</div>
                    <h3 class="agent-name">딥 스캔</h3>
                    <p class="agent-subtitle">궁금한 것을 끝까지 파서 진짜 답을 찾습니다</p>
                    <div class="entanglement-indicator">
                        <span class="entanglement-line"></span>
                        양자 얽힘 상태
                        <span class="entanglement-line"></span>
                    </div>
                    <a href="../../execution/09_vertical_explorer/index.php" class="agent-link">🚀 탐험하기</a>
                </div>
                <?php endif; ?>
                </div>
            </section>
        
        <!-- ==================== 푸터 ==================== -->
        <footer class="quantum-footer">
            <p>
                🌌 Q-MIND: Quantum-Mindset Insight Navigation Design<br>
                "Don't fix your past. Collapse your future."
            </p>
            <p style="margin-top: 0.5rem; opacity: 0.7;">
                © 2024 AI 에이전트 프로젝트 시스템
            </p>
        </footer>
    </div>

    <!-- 연결된 에이전트 데이터 전달 -->
    <script>
        const entangledAgentsData = <?php echo json_encode(array_map(function($a) {
            return [
                'id' => $a['id'],
                'number' => $a['number'],
                'name' => $a['name'],
                'icon' => $a['icon'],
                'category' => $a['category']
            ];
        }, $entangled_agents)); ?>;
    </script>
    <script src="quantum_app.js"></script>
</body>
</html>
