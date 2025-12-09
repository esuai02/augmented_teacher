<?php
include_once("/home/moodle/public_html/moodle/config.php");
include_once("/home/moodle/public_html/moodle/configwhiteboard.php");

global $DB, $USER;

// 로그인 체크
require_login();

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pattern Bank History</title>
    <!-- MathJax for LaTeX rendering -->
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)'], ['$', '$']],
                displayMath: [['\\[', '\\]'], ['$$', '$$']]
            }
        };
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f5f6fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
        }

        .period-selector {
            display: flex;
            gap: 10px;
        }

        .period-btn {
            padding: 10px 20px;
            background-color: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .period-btn:hover {
            background-color: #e9ecef;
            transform: translateY(-1px);
        }

        .period-btn.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
        }

        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .content-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .content-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            cursor: pointer;
            user-select: none;
        }

        .content-header:hover {
            opacity: 0.8;
        }

        .toggle-icon {
            margin-right: 10px;
            transition: transform 0.3s;
        }

        .content-header.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }

        .content-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            flex: 1;
        }

        .content-count {
            background-color: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }

        .problem-list {
            display: none;
            margin-top: 20px;
        }

        .content-section.expanded .problem-list {
            display: block;
        }

        .problem-item {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .problem-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .problem-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .problem-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .problem-type.similar {
            background-color: #90EE90;
            color: #2c7a2c;
        }

        .problem-type.modified {
            background-color: #87CEEB;
            color: #1e5f8e;
        }

        .problem-date {
            font-size: 12px;
            color: #7f8c8d;
        }

        .problem-content {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }

        .problem-content.expanded {
            max-height: none;
        }

        .expand-btn {
            color: #3498db;
            cursor: pointer;
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
        }

        .expand-btn:hover {
            text-decoration: underline;
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .author-info {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
            }

            .period-selector {
                flex-wrap: wrap;
                justify-content: center;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 패턴뱅크 히스토리</h1>
            <div class="period-selector">
                <button class="period-btn" data-period="today">오늘</button>
                <button class="period-btn active" data-period="week">1주일</button>
                <button class="period-btn" data-period="month">1개월</button>
                <button class="period-btn" data-period="quarter">3개월</button>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number" id="totalProblems">0</div>
                <div class="stat-label">전체 문제</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalContents">0</div>
                <div class="stat-label">원본 콘텐츠</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="similarCount">0</div>
                <div class="stat-label">유사문제</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="modifiedCount">0</div>
                <div class="stat-label">변형문제</div>
            </div>
        </div>

        <div id="contentContainer" class="content-container">
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>데이터를 불러오는 중...</p>
            </div>
        </div>
    </div>

    <script>
        let currentPeriod = 'week';
        
        // 페이지 로드 시 초기 데이터 로드
        document.addEventListener('DOMContentLoaded', function() {
            loadHistoryData(currentPeriod);
            
            // 기간 선택 버튼 이벤트
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelector('.period-btn.active').classList.remove('active');
                    this.classList.add('active');
                    currentPeriod = this.dataset.period;
                    loadHistoryData(currentPeriod);
                });
            });
        });

        async function loadHistoryData(period) {
            const container = document.getElementById('contentContainer');
            container.innerHTML = `
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>데이터를 불러오는 중...</p>
                </div>
            `;

            try {
                const formData = new FormData();
                formData.append('action', 'get_history');
                formData.append('period', period);

                const response = await fetch('history_ajax.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                // 응답 상태 확인
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // 응답 타입 확인
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('서버에서 올바른 응답을 받지 못했습니다.');
                }

                const data = await response.json();

                if (data.success) {
                    displayHistoryData(data);
                } else {
                    console.error('Error from server:', data.error);
                    container.innerHTML = `<div class="no-data">오류: ${data.error || '데이터를 불러올 수 없습니다.'}</div>`;
                }
            } catch (error) {
                console.error('Error loading history:', error);
                container.innerHTML = `<div class="no-data">데이터를 불러오는 중 오류가 발생했습니다.<br>${error.message}</div>`;
            }
        }

        function displayHistoryData(data) {
            // 통계 업데이트
            document.getElementById('totalProblems').textContent = data.stats.total_problems;
            document.getElementById('totalContents').textContent = data.stats.total_contents;
            document.getElementById('similarCount').textContent = data.stats.similar_count;
            document.getElementById('modifiedCount').textContent = data.stats.modified_count;

            // 콘텐츠별 문제 표시
            const container = document.getElementById('contentContainer');
            
            if (data.contents.length === 0) {
                container.innerHTML = '<div class="no-data">선택한 기간에 생성된 문제가 없습니다.</div>';
                return;
            }

            container.innerHTML = data.contents.map(content => `
                <div class="content-section" data-content-id="${content.content_id}">
                    <div class="content-header" onclick="toggleContent(this)">
                        <span class="toggle-icon">▼</span>
                        <div class="content-title">${escapeHtml(content.content_title)}</div>
                        <span class="content-count">${content.problems.length}개</span>
                    </div>
                    <div class="problem-list">
                        ${content.problems.map(problem => createProblemHTML(problem)).join('')}
                    </div>
                </div>
            `).join('');

            // MathJax 렌더링
            if (window.MathJax) {
                MathJax.typesetPromise();
            }
        }

        function createProblemHTML(problem) {
            const typeClass = problem.type === 'modified' ? 'modified' : 'similar';
            const typeText = problem.type === 'modified' ? '변형문제' : '유사문제';
            const date = new Date(problem.timecreated * 1000).toLocaleDateString('ko-KR');

            return `
                <div class="problem-item">
                    <div class="problem-header">
                        <span class="problem-type ${typeClass}">${typeText}</span>
                        <span class="problem-date">${date}</span>
                    </div>
                    <div class="problem-content" id="problem-${problem.id}">
                        ${escapeHtml(problem.question)}
                    </div>
                    <div class="author-info">작성자: ${escapeHtml(problem.author_name)}</div>
                    ${problem.question.length > 200 ? `
                        <span class="expand-btn" onclick="toggleProblem(${problem.id})">더 보기</span>
                    ` : ''}
                </div>
            `;
        }

        function toggleContent(header) {
            const section = header.parentElement;
            section.classList.toggle('expanded');
            header.classList.toggle('collapsed');
        }

        function toggleProblem(problemId) {
            const content = document.getElementById(`problem-${problemId}`);
            const btn = event.target;
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                btn.textContent = '더 보기';
            } else {
                content.classList.add('expanded');
                btn.textContent = '접기';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>