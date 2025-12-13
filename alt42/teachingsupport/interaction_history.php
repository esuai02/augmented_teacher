<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

$userid = $_GET["userid"] ?? $USER->id;  // 선생님 ID
$studentid = $_GET["studentid"] ?? 0;   // 학생 ID (선택적)

// 선생님 정보 가져오기
$teacher = null;
if ($userid) {
    $teacher = $DB->get_record('user', array('id' => $userid));
}

// 학생 정보 가져오기 (선택적)
$student = null;
if ($studentid) {
    $student = $DB->get_record('user', array('id' => $studentid));
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 교수학습 상호작용 히스토리 - <?php echo $student ? fullname($student) : '전체 학생'; ?> (담당: <?php echo $teacher ? fullname($teacher) : '선생님'; ?>)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .status-bar {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-item label {
            font-weight: 500;
            color: #666;
        }

        .status-item span {
            color: #3498db;
            font-weight: bold;
        }

        .interactions-panel {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .interactions-panel h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .interaction-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Compact single-line message layout - matching student_inbox.php */
        .interaction-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.2s ease;
            border-left: 4px solid #10b981;
            min-height: 50px;
        }

        .interaction-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-color: #cbd5e0;
        }

        .problem-thumbnail {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
            cursor: pointer;
        }

        .message-content-compact {
            flex: 1;
            min-width: 0;
        }

        .message-text {
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-btn-compact {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .teacher-info-compact {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .teacher-avatar-compact {
            width: 28px;
            height: 28px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .teacher-name-compact {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .message-time-compact {
            font-size: 11px;
            color: #9ca3af;
            flex-shrink: 0;
            min-width: 60px;
            text-align: right;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            flex-shrink: 0;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background-color: #229954;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .no-interactions {
            text-align: center;
            color: #999;
            padding: 40px;
            font-size: 16px;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            padding: 0;
            background: white;
            border-radius: 8px;
            padding: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .filter-tab {
            padding: 8px 16px;
            border: none;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .filter-tab.active,
        .filter-tab:hover {
            background: #3498db;
            color: white;
        }

        .stats-summary {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 10px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2e7d32;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }

        /* 모달 스타일 - student_inbox.php와 동일 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 1200px;
            width: 100%;
            height: 90vh;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: bold;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .modal-close:hover {
            opacity: 1;
        }

        .modal-body {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .problem-section {
            flex: 0 0 40%;
            min-width: 350px;
            padding: 30px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
        }

        .problem-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .problem-image:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.2);
        }

        .solution-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .solution-image:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.2);
        }

        .solution-section {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            position: relative;
        }

        .solution-content {
            font-size: 16px;
            line-height: 1.8;
            color: #2d3748;
        }

        .solution-line {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            margin-bottom: 15px;
            font-size: 16px;
            line-height: 1.8;
        }

        .solution-line.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .solution-line.teacher {
            background: #ebf8ff;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #4299e1;
        }

        .solution-line.student {
            background: #f0fff4;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #48bb78;
        }

        .speaker-label {
            font-weight: bold;
            color: #2b6cb0;
            margin-bottom: 5px;
        }

        .solution-line .MathJax {
            font-size: 1.1em !important;
        }

        .solution-line h3 {
            color: #2d3748;
            margin: 20px 0 10px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .solution-line ul, .solution-line ol {
            margin: 10px 0;
            padding-left: 30px;
        }

        .solution-line li {
            margin: 5px 0;
        }

        .solution-line strong {
            color: #2b6cb0;
            font-weight: bold;
        }

        .solution-line code {
            background: #f0f4f8;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        .audio-controls {
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
        }

        .play-pause-btn {
            width: 48px;
            height: 48px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .play-pause-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .progress-container {
            flex: 1;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
            width: 0%;
            transition: width 0.1s ease;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .modal-content {
                height: 95vh;
                border-radius: 15px;
            }

            .modal-body {
                flex-direction: column;
            }

            .problem-section {
                flex: 0 0 auto;
                min-width: unset;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
                max-height: 40vh;
            }

            .solution-section {
                flex: 1;
                padding: 20px;
            }

            .stats-summary {
                flex-direction: column;
            }

            .interaction-item {
                flex-wrap: wrap;
                gap: 8px;
                padding: 10px 12px;
            }

            .action-btn-compact {
                font-size: 11px;
                padding: 5px 8px;
            }
        }
    </style>
    
    <!-- MathJax 설정 및 로드 -->
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true,
                processEnvironments: true
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
                ignoreHtmlClass: 'tex2jax_ignore',
                processHtmlClass: 'tex2jax_process'
            }
        };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 교수학습 상호작용 히스토리</h1>
            <div class="status-bar">
                <?php if ($teacher): ?>
                <div class="status-item">
                    <label>담당 선생님:</label>
                    <span><?php echo fullname($teacher); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($student): ?>
                <div class="status-item">
                    <label>학생:</label>
                    <span><?php echo fullname($student); ?> (ID: <?php echo $studentid; ?>)</span>
                </div>
                <?php else: ?>
                <div class="status-item">
                    <span>전체 학생 현황</span>
                </div>
                <?php endif; ?>
                <div class="status-item">
                    <button onclick="location.reload()" class="btn btn-secondary">새로고침</button>
                </div>
            </div>
        </div>

        <!-- 필터 탭 제거 (완료된 항목만 표시) -->

        <!-- 통계 요약 -->
        <div class="stats-summary" id="statsSummary">
            <div class="stat-item">
                <div class="stat-number" id="totalCount">0</div>
                <div class="stat-label">총 상호작용</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="completedCount">0</div>
                <div class="stat-label">완료됨</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="pendingCount">0</div>
                <div class="stat-label">대기중</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="progressCount">0</div>
                <div class="stat-label">진행중</div>
            </div>
        </div>

        <!-- 상호작용 목록 -->
        <div class="interactions-panel">
            <h2>풀이 상호작용 목록</h2>
            
            <!-- 로딩 상태 -->
            <div class="loading" id="loadingIndicator">
                <div class="spinner"></div>
                <p>상호작용 목록을 불러오는 중...</p>
            </div>
            
            <!-- 상호작용 목록 -->
            <div class="interaction-list" id="interactionList" style="display: none;">
                <!-- 동적으로 생성됨 -->
            </div>
            
            <!-- 빈 상태 -->
            <div class="no-interactions" id="noInteractions" style="display: none;">
                <h3>📭 상호작용 히스토리가 없습니다</h3>
                <p>학생들과의 교수학습 상호작용이 시작되면 여기에 표시됩니다.</p>
            </div>
        </div>
    </div>

    <script>
        // 전역 변수
        let currentFilter = 'completed';
        const userid = <?php echo $userid ? $userid : 'null'; ?>;
        const studentid = <?php echo $studentid ? $studentid : 'null'; ?>;

        // DOM 요소들
        const loadingIndicator = document.getElementById('loadingIndicator');
        const interactionList = document.getElementById('interactionList');
        const noInteractions = document.getElementById('noInteractions');

        // 수식 처리 함수
        function processMathContent(content) {
            if (!content) return content;
            
            // LaTeX 수식 태그들을 안전하게 처리
            let processedContent = content
                // \text{} 태그 처리
                .replace(/\\text\{([^}]+)\}/g, '$1')
                // \frac{a}{b} 태그를 분수 형태로 변환
                .replace(/\\frac\{([^}]+)\}\{([^}]+)\}/g, '($1)/($2)')
                // \sqrt{} 태그 처리
                .replace(/\\sqrt\{([^}]+)\}/g, '√($1)')
                // \times 태그 처리
                .replace(/\\times/g, '×')
                // \cdot 태그 처리
                .replace(/\\cdot/g, '·')
                // \pm 태그 처리
                .replace(/\\pm/g, '±')
                // \div 태그 처리
                .replace(/\\div/g, '÷')
                // \pi 태그 처리
                .replace(/\\pi/g, 'π')
                // \alpha, \beta 등 그리스 문자 처리
                .replace(/\\alpha/g, 'α')
                .replace(/\\beta/g, 'β')
                .replace(/\\gamma/g, 'γ')
                .replace(/\\delta/g, 'δ')
                .replace(/\\theta/g, 'θ')
                .replace(/\\lambda/g, 'λ')
                .replace(/\\mu/g, 'μ')
                .replace(/\\sigma/g, 'σ')
                .replace(/\\omega/g, 'ω')
                // 부등호 처리
                .replace(/\\leq/g, '≤')
                .replace(/\\geq/g, '≥')
                .replace(/\\neq/g, '≠')
                .replace(/\\approx/g, '≈')
                // 집합 기호 처리
                .replace(/\\in/g, '∈')
                .replace(/\\subset/g, '⊂')
                .replace(/\\supset/g, '⊃')
                .replace(/\\cup/g, '∪')
                .replace(/\\cap/g, '∩')
                // 기타 수학 기호
                .replace(/\\infty/g, '∞')
                .replace(/\\sum/g, '∑')
                .replace(/\\prod/g, '∏')
                .replace(/\\int/g, '∫')
                // 중괄호 제거 (남은 것들)
                .replace(/\{/g, '')
                .replace(/\}/g, '');
            
            return processedContent;
        }

        // 마크다운 텍스트를 HTML로 변환하는 함수
        function processMarkdownContent(content) {
            if (!content) return content;
            
            let processedContent = content
                // Bold text: **text** -> <strong>text</strong>
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                // Italic text: *text* -> <em>text</em> (수식 기호와 구분하기 위해 더 정확한 패턴 사용)
                .replace(/\*([^*\s][^*]*[^*\s])\*/g, '<em>$1</em>')
                // Code inline: `code` -> <code>code</code>
                .replace(/`([^`\n]+?)`/g, '<code style="background: #f1f5f9; padding: 2px 4px; border-radius: 3px; font-family: monospace;">$1</code>')
                // Strikethrough: ~~text~~ -> <del>text</del>
                .replace(/~~(.*?)~~/g, '<del>$1</del>')
                // Underline: __text__ -> <u>text</u>
                .replace(/__(.*?)__/g, '<u>$1</u>')
                // 줄바꿈 처리: \n -> <br>
                .replace(/\n/g, '<br>')
                // 번호 목록: 1. text -> <ol><li>text</li></ol> (간단한 형태)
                .replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>')
                // 불릿 목록: - text 또는 * text -> <ul><li>text</li></ul>
                .replace(/^[\-\*]\s+(.+)$/gm, '<li>$1</li>');
            
            return processedContent;
        }

        // 통합 콘텐츠 처리 함수
        function processContent(content) {
            if (!content) return content;
            
            // 1단계: 수식 처리
            let processed = processMathContent(content);
            
            // 2단계: 마크다운 처리
            processed = processMarkdownContent(processed);
            
            return processed;
        }

        // MathJax 재렌더링 함수
        function rerenderMath() {
            if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                MathJax.typesetPromise().catch((err) => {
                    console.warn('MathJax rendering error:', err);
                });
            }
        }

        // 페이지 로드 시 실행
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📊 상호작용 히스토리 시스템 시작');
            console.log('Teacher ID:', userid);
            console.log('Student ID:', studentid);
            loadInteractions();
        });

        // 상호작용 목록 로드
        async function loadInteractions() {
            showLoading();
            
            try {
                // userid가 teacherid인 풀이 목록을 가져옴
                const params = new URLSearchParams({
                    teacherid: userid,
                    filter: currentFilter
                });
                
                if (studentid && studentid !== 'null') {
                    params.append('studentid', studentid);
                }

                console.log(`🔄 상호작용 목록 로드 중... (teacher: ${userid}, student: ${studentid}, filter: ${currentFilter})`);
                
                // ktm_teaching_interactions 테이블에서 데이터 가져오기
                const response = await fetch(`get_teacher_interactions_simple.php?${params}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || '상호작용 목록을 불러오는데 실패했습니다.');
                }
                
                console.log(`✅ ${data.interactions.length}개의 상호작용 로드됨`);
                console.log('Debug info:', data.debug);
                console.log('Stats:', data.stats);
                
                // 통계 업데이트
                updateStats(data.stats);
                
                // 상호작용 목록 렌더링
                renderInteractions(data.interactions);
                
            } catch (error) {
                console.error('❌ 상호작용 목록 로드 실패:', error);
                console.error('Error details:', error);
                
                let errorMessage = '상호작용 목록을 불러오는데 실패했습니다.';
                if (error.message) {
                    errorMessage = error.message;
                }
                
                showError(errorMessage);
            } finally {
                hideLoading();
            }
        }

        // 통계 업데이트
        function updateStats(stats) {
            document.getElementById('totalCount').textContent = stats.total || 0;
            document.getElementById('completedCount').textContent = stats.completed || 0;
            document.getElementById('pendingCount').textContent = stats.pending || 0;
            document.getElementById('progressCount').textContent = stats.in_progress || 0;
        }

        // 상호작용 목록 렌더링
        function renderInteractions(interactions) {
            if (!interactions || interactions.length === 0) {
                interactionList.innerHTML = '';
                interactionList.style.display = 'none';
                noInteractions.style.display = 'block';
                return;
            }
            
            noInteractions.style.display = 'none';
            interactionList.style.display = 'block';
            
            interactionList.innerHTML = interactions.map(interaction => createInteractionCard(interaction)).join('');
        }

        // 상호작용 카드 생성 - student_inbox.php 스타일 매칭
        function createInteractionCard(interaction) {
            const timeFormatted = formatTimeCompact(interaction.timecreated);
            
            // 이미지 URL 생성
            const imageUrl = interaction.problem_image ? 
                getImageUrl(interaction.problem_image) : null;
            
            const problemText = interaction.problem_text || interaction.modification_prompt || '풀이 완료';
            const truncatedText = truncateText(problemText, 60);
            
            return `
                <div class="interaction-item" data-id="${interaction.id}">
                    <!-- 문제 이미지 썸네일 -->
                    ${imageUrl ? `
                        <img class="problem-thumbnail" 
                             src="${imageUrl}" 
                             alt="문제 이미지"
                             onmouseover="showImageTooltip(event, '${imageUrl}')"
                             onmouseout="hideImageTooltip()"
                             onerror="this.style.display='none'">
                    ` : `
                        <div class="problem-thumbnail" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 18px;">📄</div>
                    `}
                    
                    <!-- 메시지 내용 -->
                    <div class="message-content-compact">
                        <div class="message-text">
                            ✅ ${truncatedText}
                        </div>
                    </div>
                    
                    <!-- 풀이보기 버튼 -->
                    <button class="action-btn-compact btn-primary" onclick="viewSolution(${interaction.id})" title="풀이보기">
                        📖 풀이보기
                    </button>
                    
                    <!-- 학생 정보 -->
                    <div class="teacher-info-compact">
                        <div class="teacher-avatar-compact">
                            ${interaction.student_name.charAt(0)}
                        </div>
                        <span class="teacher-name-compact">${interaction.student_name}</span>
                    </div>
                    
                    <!-- 시간 -->
                    <div class="message-time-compact">
                        ${timeFormatted}
                    </div>
                    
                    <!-- 완료 상태 인디케이터 -->
                    <div class="status-indicator" title="완료됨"></div>
                </div>
            `;
        }

        // 이미지 URL 생성
        function getImageUrl(imagePath) {
            if (!imagePath) return '';
            
            if (imagePath.startsWith('data:')) {
                return imagePath;
            }
            
            if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
                return imagePath;
            }
            
            if (imagePath.startsWith('images/')) {
                return 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/' + imagePath;
            }
            
            return 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/student_problems/' + imagePath;
        }

        // 상태별 텍스트
        function getStatusText(status) {
            switch (status) {
                case 'completed': return '✅ 완료됨';
                case 'pending': return '⏳ 대기중';
                case 'in_progress': return '🔄 진행중';
                default: return '📋 요청됨';
            }
        }

        // 시간 포맷팅 (컴팩트 버전 - student_inbox.php 스타일)
        function formatTimeCompact(timestamp) {
            const date = new Date(timestamp * 1000);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) {
                return '방금';
            } else if (diff < 3600000) {
                return Math.floor(diff / 60000) + '분';
            } else if (diff < 86400000) {
                return Math.floor(diff / 3600000) + '시간';
            } else if (diff < 86400000 * 7) {
                return Math.floor(diff / 86400000) + '일';
            } else {
                return date.toLocaleDateString('ko-KR', { month: 'short', day: 'numeric' });
            }
        }

        // 텍스트 자르기 함수
        function truncateText(text, maxLength) {
            if (!text) return '';
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }

        // 이미지 툴팁 표시/숨기기
        function showImageTooltip(event, imageUrl) {
            const tooltip = document.getElementById('imageTooltip') || createImageTooltip();
            tooltip.innerHTML = `<img src="${imageUrl}" style="max-width: 300px; max-height: 300px; border-radius: 8px;">`;
            tooltip.style.display = 'block';
            tooltip.style.left = (event.pageX + 10) + 'px';
            tooltip.style.top = (event.pageY + 10) + 'px';
        }

        function hideImageTooltip() {
            const tooltip = document.getElementById('imageTooltip');
            if (tooltip) {
                tooltip.style.display = 'none';
            }
        }

        function createImageTooltip() {
            const tooltip = document.createElement('div');
            tooltip.id = 'imageTooltip';
            tooltip.style.cssText = 'position: absolute; z-index: 1000; background: white; border: 1px solid #ddd; border-radius: 8px; padding: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: none;';
            document.body.appendChild(tooltip);
            return tooltip;
        }

        // 필터는 완료된 항목으로 고정
        function setFilter(filter) {
            // 완료된 항목만 표시하도록 고정
            currentFilter = 'completed';
            loadInteractions();
        }

        // 해설 보기 - student_inbox.php와 동일한 모달 방식
        function viewSolution(interactionId) {
            openLectureModal(interactionId);
        }

        // 강의 모달 열기 (student_inbox.php와 동일한 기능)
        let audioPlayer = null;
        let dialogueLines = [];
        let currentLineIndex = 0;
        let isPlaying = false;
        let syncTimer = null;
        let currentAudioFiles = [];
        let currentAudioIndex = 0;
        
        async function openLectureModal(interactionId) {
            const modal = document.getElementById('lectureModal') || createLectureModal();
            modal.classList.add('active');
            
            // 오디오 플레이어 초기화
            audioPlayer = document.getElementById('modalAudioPlayer');
            
            // 초기화
            document.getElementById('modalProblemImage').style.display = 'none';
            document.getElementById('modalProblemText').innerHTML = '문제를 불러오는 중...';
            document.getElementById('solutionContent').innerHTML = '해설을 불러오는 중...';
            
            // 해설 이미지 섹션 초기화
            const solutionImageSection = document.getElementById('solutionImageSection');
            if (solutionImageSection) {
                solutionImageSection.style.display = 'none';
                document.getElementById('modalSolutionImage').src = '';
            }
            
            try {
                const response = await fetch(`get_dialogue_data.php?cid=${interactionId}&ctype=interaction`);
                const data = await response.json();
                
                if (data.success) {
                    // 문제 이미지 표시
                    if (data.problemImage) {
                        const problemImage = document.getElementById('modalProblemImage');
                        let fullImageUrl = getImageUrl(data.problemImage);
                        problemImage.src = fullImageUrl;
                        problemImage.style.display = 'block';
                    }
                    
                    // 문제 텍스트 표시
                    if (data.problemText) {
                        document.getElementById('modalProblemText').innerHTML = data.problemText;
                        // MathJax 렌더링
                        if (window.MathJax) {
                            window.MathJax.typesetPromise([document.getElementById('modalProblemText')]);
                        }
                    } else {
                        document.getElementById('modalProblemText').innerHTML = '<em>문제 텍스트가 없습니다.</em>';
                    }
                    
                    // 해설 이미지 표시
                    if (data.solutionImage && solutionImageSection) {
                        const solutionImage = document.getElementById('modalSolutionImage');
                        let fullSolutionImageUrl = getImageUrl(data.solutionImage);
                        solutionImage.src = fullSolutionImageUrl;
                        solutionImageSection.style.display = 'block';
                    }
                    
                    // 해설 텍스트 표시
                    const solutionHtml = data.solutionText || data.narrationText || '';
                    if (solutionHtml) {
                        parseDialogue(solutionHtml);
                        // parseDialogue 내부에서 MathJax 렌더링이 이미 처리됨
                    } else {
                        document.getElementById('solutionContent').innerHTML = '<em>해설이 없습니다.</em>';
                    }
                    
                    // 오디오 설정
                    if (data.audioUrl) {
                        // 전체 URL 경로 구성
                        const fullAudioUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/' + data.audioUrl;
                        
                        // 오디오 파일 배열 초기화
                        currentAudioFiles = [fullAudioUrl];
                        currentAudioIndex = 0;
                        
                        audioPlayer.src = fullAudioUrl;
                        audioPlayer.addEventListener('loadedmetadata', () => {
                            const timeDisplay = document.getElementById('timeDisplay');
                            if (timeDisplay) {
                                timeDisplay.textContent = formatTime(audioPlayer.duration);
                            }
                        });
                        audioPlayer.addEventListener('timeupdate', updateProgress);
                        audioPlayer.addEventListener('ended', onAudioEnded);
                    } else {
                        // 오디오가 없는 경우 배열 초기화
                        currentAudioFiles = [];
                        currentAudioIndex = 0;
                    }
                } else {
                    throw new Error(data.error || '데이터를 불러올 수 없습니다.');
                }
            } catch (error) {
                console.error('Error loading interaction details:', error);
                document.getElementById('solutionContent').innerHTML = 
                    `<div style="color: #e74c3c; text-align: center; padding: 20px;">해설을 불러오는데 실패했습니다: ${error.message}</div>`;
            }
        }

        function createLectureModal() {
            const modalHTML = `
                <div class="modal-overlay" id="lectureModal">
                    <div class="modal-content" style="display: flex; flex-direction: column; height: 90vh;">
                        <div class="modal-header">
                            <h2 class="modal-title">📚 문제 해설 강의</h2>
                            <button class="modal-close" onclick="closeLectureModal()">&times;</button>
                        </div>
                        <div class="modal-body" style="display: flex; flex: 1; overflow: hidden; height: calc(100% - 80px);">
                            <div class="problem-section" style="flex: 0 0 40%; min-width: 350px; padding: 30px; background: #f8fafc; border-right: 1px solid #e2e8f0; overflow-y: auto; display: block;">
                                <h3 style="margin-bottom: 20px; color: #2d3748;">문제</h3>
                                <img id="modalProblemImage" class="problem-image" src="" alt="문제 이미지" style="display: none; width: 100%; height: auto; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                <div id="modalProblemText" style="font-size: 16px; line-height: 1.6; margin-bottom: 30px;"></div>
                                
                                <div id="solutionImageSection" style="display: none;">
                                    <h3 style="margin-bottom: 20px; color: #2d3748;">해설 이미지</h3>
                                    <img id="modalSolutionImage" class="solution-image" src="" alt="해설 이미지" style="width: 100%; height: auto; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                </div>
                            </div>
                            <div class="solution-section" style="flex: 1; padding: 30px; overflow-y: auto; position: relative; display: flex; flex-direction: column;">
                                <h3 style="margin-bottom: 20px; color: #2d3748;">해설</h3>
                                <div id="solutionContent" class="solution-content" style="flex: 1; overflow-y: auto;"></div>
                                <div class="audio-controls" style="position: sticky; bottom: 0; background: white; border-top: 1px solid #e2e8f0; padding: 20px; display: flex; align-items: center; gap: 20px; box-shadow: 0 -4px 12px rgba(0,0,0,0.05); margin: -30px -30px 0 -30px;">
                                    <button class="play-pause-btn" id="playPauseBtn" onclick="togglePlayPause()">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                    <div class="progress-container" style="flex: 1; height: 4px; background: #e2e8f0; border-radius: 2px; cursor: pointer;" onclick="seekAudio(event)">
                                        <div class="progress-bar" id="progressBar" style="height: 100%; background: #3498db; border-radius: 2px; width: 0%; transition: width 0.1s;"></div>
                                    </div>
                                    <span id="timeDisplay" style="font-size: 12px; color: #6b7280; min-width: 80px; text-align: right;">0:00 / 0:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <audio id="modalAudioPlayer" style="display: none;"></audio>
            `;
            
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHTML;
            document.body.appendChild(modalContainer.firstElementChild);
            document.body.appendChild(modalContainer.querySelector('audio'));
            
            return document.getElementById('lectureModal');
        }

        function closeLectureModal() {
            const modal = document.getElementById('lectureModal');
            if (modal) {
                modal.classList.remove('active');
            }
            
            // 오디오 정리
            if (audioPlayer) {
                audioPlayer.pause();
                audioPlayer.currentTime = 0;
            }
            isPlaying = false;
            currentAudioFiles = [];
            currentAudioIndex = 0;
            updatePlayPauseButton();
        }

        // 대화 파싱
        function parseDialogue(text) {
            if (!text) return;
            
            const solutionContent = document.getElementById('solutionContent');
            solutionContent.innerHTML = '';
            dialogueLines = [];
            
            // 해설 내용을 섹션별로 파싱
            const sections = [];
            let currentSection = '';
            
            // 텍스트를 줄바꿈으로 분리
            const lines = text.split('\n');
            
            lines.forEach(line => {
                const trimmedLine = line.trim();
                if (!trimmedLine) return;
                
                // 섹션 헤더 감지 ([문제 분석], [풀이 과정] 등)
                if (trimmedLine.match(/^\[.+\]$/)) {
                    if (currentSection) {
                        sections.push(currentSection);
                    }
                    currentSection = trimmedLine + '\n';
                } else {
                    currentSection += trimmedLine + '\n';
                }
            });
            
            if (currentSection) {
                sections.push(currentSection);
            }
            
            // 섹션별로 처리
            sections.forEach(section => {
                const lines = section.split('\n').filter(line => line.trim());
                
                lines.forEach(line => {
                    const lineDiv = document.createElement('div');
                    lineDiv.className = 'solution-line';
                    lineDiv.setAttribute('data-index', dialogueLines.length);
                    
                    // 섹션 헤더 스타일
                    if (line.match(/^\[.+\]$/)) {
                        lineDiv.innerHTML = `<h3>${line.replace(/[\[\]]/g, '')}</h3>`;
                    }
                    // 선생님/학생 대화 처리
                    else if (line.includes('선생님:') || line.includes('학생:')) {
                        const isTeacher = line.includes('선생님:');
                        lineDiv.className += isTeacher ? ' teacher' : ' student';
                        const speaker = isTeacher ? '선생님:' : '학생:';
                        const content = line.replace(speaker, '').trim();
                        
                        // 콘텐츠 처리 (마크다운 및 수식)
                        const processedContent = processContent(content);
                        
                        lineDiv.innerHTML = `
                            <div class="speaker-label">${speaker}</div>
                            <div>${processedContent}</div>
                        `;
                    }
                    // 일반 내용
                    else {
                        // 콘텐츠 처리 (마크다운 및 수식)
                        let formattedLine = processContent(line);
                        
                        // 리스트 항목 처리
                        if (line.match(/^[-*]\s/)) {
                            const listContent = line.substring(2).trim();
                            formattedLine = '• ' + processContent(listContent);
                        }
                        
                        lineDiv.innerHTML = formattedLine;
                    }
                    
                    solutionContent.appendChild(lineDiv);
                    dialogueLines.push({
                        element: lineDiv,
                        text: line,
                        duration: line.replace(/<[^>]*>/g, '').length * 0.05
                    });
                });
            });
            
            // MathJax 렌더링 (모든 텍스트 추가 후)
            setTimeout(() => {
                rerenderMath();
            }, 100);
        }
        
        // 대화 라인 생성 헬퍼 함수
        function createDialogueLine(line, container) {
            const isTeacher = line.includes('선생님:');
            const isStudent = line.includes('학생:');
            
            const lineDiv = document.createElement('div');
            lineDiv.className = `solution-line ${isTeacher ? 'teacher' : isStudent ? 'student' : ''}`;
            lineDiv.setAttribute('data-index', dialogueLines.length);
            
            if (isTeacher || isStudent) {
                const speaker = isTeacher ? '선생님:' : '학생:';
                const content = line.replace(speaker, '').trim();
                
                lineDiv.innerHTML = `
                    <div class="speaker-label">${speaker}</div>
                    <div>${content}</div>
                `;
            } else {
                // HTML 태그를 유지하면서 표시
                lineDiv.innerHTML = line;
            }
            
            container.appendChild(lineDiv);
            dialogueLines.push({
                element: lineDiv,
                text: line,
                duration: line.replace(/<[^>]*>/g, '').length * 0.05 // HTML 태그 제외한 글자 수로 계산
            });
        }

        // 오디오 재생
        function playAudio() {
            if (!audioPlayer) return;
            
            audioPlayer.play();
            isPlaying = true;
            updatePlayPauseButton();
            startTextSync();
        }

        // 오디오 일시정지
        function pauseAudio() {
            if (!audioPlayer) return;
            
            audioPlayer.pause();
            isPlaying = false;
            updatePlayPauseButton();
            
            // 텍스트 싱크 중지
            if (syncTimer) {
                clearInterval(syncTimer);
                syncTimer = null;
            }
        }

        // 텍스트 싱크 시작
        function startTextSync() {
            if (!dialogueLines.length || !audioPlayer.duration) return;
            
            const totalDuration = audioPlayer.duration;
            
            // 각 라인의 누적 시간 계산
            let cumulativeTime = 0;
            const lineTimings = dialogueLines.map((line, index) => {
                const start = cumulativeTime;
                const duration = line.duration || (totalDuration / dialogueLines.length);
                cumulativeTime += duration;
                return { start, end: cumulativeTime };
            });
            
            // 전체 시간에 맞게 조정
            const scaleFactor = totalDuration / cumulativeTime;
            lineTimings.forEach(timing => {
                timing.start *= scaleFactor;
                timing.end *= scaleFactor;
            });
            
            // 현재 재생 위치에 맞는 라인 찾기
            const currentTime = audioPlayer.currentTime;
            currentLineIndex = 0;
            for (let i = 0; i < lineTimings.length; i++) {
                if (currentTime >= lineTimings[i].start) {
                    dialogueLines[i].element.classList.add('visible');
                    currentLineIndex = i;
                } else {
                    break;
                }
            }
            
            // 싱크 타이머 시작
            syncTimer = setInterval(() => {
                const currentTime = audioPlayer.currentTime;
                let hasNewVisible = false;
                
                while (currentLineIndex < dialogueLines.length && 
                       currentTime >= lineTimings[currentLineIndex].start) {
                    dialogueLines[currentLineIndex].element.classList.add('visible');
                    currentLineIndex++;
                    hasNewVisible = true;
                }
                
                // 새로운 텍스트가 표시되었을 때 MathJax 렌더링
                if (hasNewVisible) {
                    setTimeout(rerenderMath, 50);
                }
            }, 100);
        }

        function togglePlayPause() {
            if (!audioPlayer || !audioPlayer.src) {
                console.log('No audio available');
                return;
            }
            
            if (isPlaying) {
                pauseAudio();
            } else {
                playAudio();
            }
        }

        function updatePlayPauseButton() {
            const btn = document.getElementById('playPauseBtn');
            if (btn) {
                btn.innerHTML = isPlaying ? 
                    '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>' :
                    '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
            }
        }

        function playNextAudio() {
            if (currentAudioIndex < currentAudioFiles.length) {
                const audioFile = currentAudioFiles[currentAudioIndex];
                audioPlayer.src = audioFile;
                audioPlayer.play();
                currentAudioIndex++;
            } else {
                // 모든 오디오 재생 완료
                isPlaying = false;
                updatePlayPauseButton();
            }
        }

        // 오디오 이벤트 리스너 설정
        document.addEventListener('DOMContentLoaded', function() {
            // 오디오 플레이어 이벤트 설정은 모달 생성 시 처리
        });

        function seekAudio(event) {
            if (!audioPlayer || !audioPlayer.duration) return;
            
            const progressContainer = event.currentTarget;
            const rect = progressContainer.getBoundingClientRect();
            const percentage = (event.clientX - rect.left) / rect.width;
            const newTime = percentage * audioPlayer.duration;
            
            audioPlayer.currentTime = newTime;
        }

        function updateProgress() {
            if (!audioPlayer || !audioPlayer.duration) return;
            
            const progressBar = document.getElementById('progressBar');
            const timeDisplay = document.getElementById('timeDisplay');
            
            if (progressBar && timeDisplay) {
                const percentage = (audioPlayer.currentTime / audioPlayer.duration) * 100;
                progressBar.style.width = percentage + '%';
                
                const currentMinutes = Math.floor(audioPlayer.currentTime / 60);
                const currentSeconds = Math.floor(audioPlayer.currentTime % 60);
                const durationMinutes = Math.floor(audioPlayer.duration / 60);
                const durationSeconds = Math.floor(audioPlayer.duration % 60);
                
                timeDisplay.textContent = 
                    `${currentMinutes}:${currentSeconds.toString().padStart(2, '0')} / ` +
                    `${durationMinutes}:${durationSeconds.toString().padStart(2, '0')}`;
            }
        }

        // 시간 포맷팅 함수
        function formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }

        // 오디오 종료 이벤트 핸들러
        function onAudioEnded() {
            isPlaying = false;
            updatePlayPauseButton();
            
            // 모든 텍스트 표시
            dialogueLines.forEach(line => line.element.classList.add('visible'));
            
            // 모든 텍스트 표시 후 MathJax 렌더링
            setTimeout(rerenderMath, 100);
            
            if (syncTimer) {
                clearInterval(syncTimer);
                syncTimer = null;
            }
        }

        // 자동 새로고침 간격을 60초로 증가 (완료된 항목만 표시하므로)
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                loadInteractions();
            }
        }, 60000);

        // 문제 다운로드
        function downloadProblem(interactionId) {
            const link = document.createElement('a');
            link.href = `download_problem.php?id=${interactionId}`;
            link.download = `problem_${interactionId}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // 로딩/에러 상태 관리
        function showLoading() {
            loadingIndicator.style.display = 'block';
            interactionList.style.display = 'none';
            noInteractions.style.display = 'none';
        }

        function hideLoading() {
            loadingIndicator.style.display = 'none';
        }

        function showError(message) {
            interactionList.innerHTML = `
                <div style="text-align: center; color: #e74c3c; padding: 40px;">
                    <h3>❌ 오류 발생</h3>
                    <p>${message}</p>
                    <button onclick="loadInteractions()" class="btn btn-primary" style="margin-top: 15px;">
                        다시 시도
                    </button>
                </div>
            `;
            interactionList.style.display = 'block';
            noInteractions.style.display = 'none';
        }

    </script>
</body>
</html>