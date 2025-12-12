<?php
/**
 * Agent Garden UI - Main Interface
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/index.php
 * 
 * 21개 에이전트를 동작시키기 위한 메인 인터페이스
 * 채팅으로 요청하면 결과를 생성하는 단순한 UI 환경
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// URL 파라미터에서 userid 가져오기 (우선순위 1), 없으면 현재 로그인한 사용자 ID 사용
$targetUserId = null;
if (isset($_GET['userid']) && !empty($_GET['userid'])) {
    $targetUserId = intval($_GET['userid']);
} else {
    // userid가 없으면 현재 로그인한 사용자 ID 사용
    $targetUserId = isset($USER->id) && $USER->id > 0 ? intval($USER->id) : null;
}

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1");
$role = $userrole ? $userrole->data : 'student';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>에이전트 가든2 - Agent Garden</title>
    <link rel="stylesheet" href="agent_garden.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .nav-dropdown {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            gap: 2px;
            align-items: flex-start;
        }
        
        .top-right-links {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        
        .top-right-link {
            padding: 10px 20px;
            background: rgba(102, 126, 234, 0.95);
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.2s;
            display: block;
        }
        
        .top-right-link:first-child {
            border-radius: 0 0 0 8px;
        }
        
        .top-right-link:last-child {
            border-radius: 0 0 8px 0;
        }
        
        .top-right-link:hover {
            background: rgba(85, 104, 211, 0.95);
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
            transform: translateY(2px);
        }
        
        .nav-dropdown > *:first-child {
            border-radius: 0 0 0 8px;
        }
        
        .nav-dropdown > *:last-child {
            border-radius: 0 0 8px 0;
        }
        
        .nav-dropdown select {
            padding: 10px 15px;
            border: 2px solid rgba(0,0,0,0.1);
            border-top: none;
            border-left: none;
            border-right: none;
            background: rgba(255,255,255,0.95);
            color: #333;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            min-width: 200px;
            height: 42px;
            line-height: 1.5;
            box-sizing: border-box;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .nav-dropdown select:hover {
            border-color: rgba(0,0,0,0.2);
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }
        
        .agent-garden__container {
            padding-top: 42px; /* 네비게이션 메뉴 공간 확보 */
        }
        
        /* 포괄형 질문 스타일 */
        .comprehensive-questions {
            margin-top: 2rem;
        }
        
        .question-group {
            margin-bottom: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        
        .question-header {
            padding: 12px 16px;
            background: #f8f9fa;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s;
            user-select: none;
        }
        
        .question-header:hover {
            background: #e9ecef;
        }
        
        .question-icon {
            font-size: 0.8em;
            color: #667eea;
            transition: transform 0.3s;
            display: inline-block;
        }
        
        .question-group.expanded .question-icon {
            transform: rotate(90deg);
        }
        
        .question-title {
            font-weight: 600;
            color: #333;
            flex: 1;
        }
        
        .question-content {
            padding: 0;
            background: white;
        }
        
        /* agent01의 Q1, Q2, Q3는 기본적으로 펼쳐진 상태 */
        .question-group.expanded .question-content {
            display: block !important;
        }
        
        .question-main,
        .question-sub {
            padding: 10px 16px 10px 40px;
            cursor: pointer;
            transition: background 0.2s;
            border-top: 1px solid #f0f0f0;
        }
        
        .question-main {
            font-weight: 500;
            background: #f8f9fa;
        }
        
        .question-sub {
            font-size: 0.9em;
            color: #666;
        }
        
        .question-main:hover,
        .question-sub:hover {
            background: #e3f2fd;
        }
        
        .question-text {
            display: block;
        }
    </style>
</head>
<body>
    <div class="nav-dropdown">
        <select id="pageSelector" onchange="navigateToPage()">
            <option value="../../agent_orchestration/agentmission.html">1. 에이전트 미션</option>
            <option value="../../agent_orchestration/questions.html">2. 주요 요청들</option>
            <option value="../../agent_orchestration/dataindex.php">3. 데이터 통합</option>
            <option value="../../agent_orchestration/rules_viewer.html">4. 에이전트 룰들</option>
            <option value="../../../index.php">5. Mathking AI 조교</option>
            <option value="../../agent_orchestration/heartbeat_dashboard.html">6. Heartbeat Dashboard</option>
            <option value="index.php" selected>7. 에이전트 가드닝</option>
            <option value="../../agent01_onboarding/persona_system/test_chat.php">8. 페르소나 테스트</option>
        </select>
    </div>
    
    <div class="top-right-links">
        <a href="evolution_stages_viewer.php" class="top-right-link">📈 진화단계</a>
        <a href="../../ontology_engineering/docs/docindex.php" class="top-right-link">📚 온톨로지북</a>
    </div>
    
    <div class="agent-garden__container">
        <header class="agent-garden__header">
            <h1 class="agent-garden__title">🌱 홀로닉 에이전트 가든</h1>
            <p class="agent-garden__subtitle">21개의 에이전트와 대화하세요</p>
            <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="data_mapping_analysis.php?agentid=agent01_onboarding&studentid=<?php echo $targetUserId; ?>" 
                   style="padding: 0.75rem 1.5rem; background: #667eea; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" 
                   onmouseover="this.style.background='#5568d3'; this.style.transform='translateY(-2px)'" 
                   onmouseout="this.style.background='#667eea'; this.style.transform='translateY(0)'">
                    🔍 Agent01 데이터 매핑 분석
                </a>
                <a href="data_mapping_analysis.php?agentid=agent08_calmness&studentid=<?php echo $targetUserId; ?>" 
                   style="padding: 0.75rem 1.5rem; background: #10b981; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; transition: all 0.2s;" 
                   onmouseover="this.style.background='#059669'; this.style.transform='translateY(-2px)'" 
                   onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'">
                    🧘 Agent08 데이터 매핑 분석
                </a>
            </div>
        </header>

        <div class="agent-garden__main">
            <!-- 에이전트 목록 패널 -->
            <aside class="agent-garden__sidebar">
                <h2 class="agent-garden__sidebar-title">에이전트 목록</h2>
                <div class="agent-garden__agent-list" id="agentList">
                    <!-- JavaScript로 동적 생성 -->
                </div>
            </aside>

            <!-- 채팅 영역 -->
            <main class="agent-garden__chat-area">
                <div class="agent-garden__chat-header">
                    <span class="agent-garden__selected-agent" id="selectedAgent">에이전트를 선택하세요</span>
                </div>
                
                <div class="agent-garden__messages" id="messages">
                    <div class="agent-garden__welcome" id="welcomeSection">
                        <p style="margin-bottom: 1.5rem; font-size: 1.1em; font-weight: 600;">안녕하세요 AI 에이전트 정원에 오신것을 환영합니다.</p>
                        <p style="margin-bottom: 1.5rem; color: #666; font-size: 0.95em;">에이전트를 선택하면 해당 에이전트의 포괄형 질문 목록이 표시됩니다.</p>
                        
                        <!-- 포괄형 질문 목록 (동적으로 생성됨) -->
                        <div class="comprehensive-questions" id="comprehensiveQuestions">
                            <!-- JavaScript로 동적 생성 -->
                        </div>
                    </div>
                </div>

                <div class="agent-garden__input-area">
                    <textarea 
                        id="messageInput" 
                        class="agent-garden__input" 
                        placeholder="에이전트에게 요청을 입력하세요..."
                        rows="3"
                    ></textarea>
                    <button id="sendButton" class="agent-garden__send-btn">전송</button>
                </div>
            </main>
        </div>
    </div>

    <script>
        // PHP에서 전달된 userid와 API 경로를 JavaScript에 전달
        window.AGENT_GARDEN_CONFIG = {
            targetUserId: <?php echo $targetUserId ? json_encode($targetUserId) : 'null'; ?>,
            apiBase: <?php 
                // 현재 스크립트와 같은 디렉토리의 파일이므로 상대 경로 사용
                $apiPath = 'agent_garden.controller.php';
                // 또는 절대 경로가 필요한 경우
                // $apiPath = $_SERVER['PHP_SELF'];
                // $apiPath = dirname($apiPath) . '/agent_garden.controller.php';
                echo json_encode($apiPath); 
            ?>
        };
        
        // 페이지 네비게이션
        function navigateToPage() {
            const select = document.getElementById('pageSelector');
            const selectedPage = select.value;
            if (selectedPage !== 'index.php') {
                window.location.href = selectedPage;
            }
        }
        
        // 현재 페이지에 맞게 선택 메뉴 설정
        window.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const pageSelector = document.getElementById('pageSelector');
            if (pageSelector && (currentPage === 'index.php' || currentPage === '')) {
                pageSelector.value = 'index.php';
            }
        });
        
        // 질문 펼치기/접기 (전역 함수, agent_questions_renderer.js에서도 사용)
        window.toggleQuestion = function toggleQuestion(qId) {
            const content = document.getElementById(qId + '-content');
            const icon = document.getElementById(qId + '-icon');
            const group = content ? content.closest('.question-group') : null;
            
            if (content && group) {
                if (content.style.display === 'none' || content.style.display === '') {
                    content.style.display = 'block';
                    group.classList.add('expanded');
                    if (icon) {
                        icon.style.transform = 'rotate(90deg)';
                    }
                } else {
                    content.style.display = 'none';
                    group.classList.remove('expanded');
                    if (icon) {
                        icon.style.transform = 'rotate(0deg)';
                    }
                }
            }
        }
        
        // 질문 선택 및 자동 요청 (전역 함수)
        window.selectQuestion = function selectQuestion(questionText) {
            const selectedAgentEl = document.getElementById('selectedAgent');
            if (!selectedAgentEl || selectedAgentEl.textContent.includes('에이전트를 선택하세요')) {
                alert('먼저 에이전트를 선택해주세요.');
                return;
            }
            
            // 에이전트 ID 확인 (agent01인 경우 특별 처리)
            const agentId = window.selectedAgentId || null;
            console.log('[Agent Garden] Question selected for agent:', agentId, 'Question:', questionText.substring(0, 50) + '...');
            
            // 환영 메시지 숨기기
            const welcomeEl = document.getElementById('welcomeSection');
            if (welcomeEl) {
                welcomeEl.style.display = 'none';
            }
            
            // 질문을 입력란에 설정
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.value = questionText;
            }
            
            // 약간의 지연 후 전송 (agent_garden.js가 로드될 시간 확보)
            setTimeout(function() {
                // sendMessage 함수가 있으면 직접 호출, 없으면 버튼 클릭
                if (typeof window.sendMessage === 'function') {
                    console.log('[Agent Garden] Calling sendMessage function directly');
                    window.sendMessage();
                } else {
                    console.log('[Agent Garden] sendMessage not available, clicking send button');
                    const sendButton = document.getElementById('sendButton');
                    if (sendButton) {
                        sendButton.click();
                    } else {
                        console.error('[Agent Garden] Send button not found');
                    }
                }
            }, 100);
        }
    </script>
    <script src="agent_questions_data.js"></script>
    <script src="../../agent_orchestration/data_based_questions.js"></script>
    <script src="agent_questions_renderer.js"></script>
    <script src="agent_garden.js"></script>
</body>
</html>

