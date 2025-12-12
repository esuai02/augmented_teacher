<?php
/**
 * Agent 14 - Current Position Dashboard
 * File: alt42/orchestration/agents/agent14_current_position/ui/dashboard.php
 * 현재 위치 평가 대시보드 UI
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = isset($_GET['id']) ? intval($_GET['id']) : $USER->id;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : null;

// 권한 확인
if ($USER->id != $studentid && $role !== 'teacher' && $role !== 'admin') {
    echo '<br><br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;다른 사용자의 정보에 접근하실 수 없습니다.';
    exit;
}

// 사용자 정보
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid'");
$studentname = $username->firstname . $username->lastname;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>현재 위치 평가 - <?php echo $studentname; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .loading {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .loading i {
            font-size: 48px;
            color: #667eea;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .card h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 i {
            color: #667eea;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-delayed {
            background: #FF6B6B;
            color: white;
        }

        .status-ontime {
            background: #4CAF50;
            color: white;
        }

        .status-early {
            background: #2196F3;
            color: white;
        }

        .emotion-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }

        .emotion-positive {
            background: #4CAF50;
            color: white;
        }

        .emotion-very-positive {
            background: #2196F3;
            color: white;
        }

        .emotion-negative {
            background: #FF9800;
            color: white;
        }

        .emotion-neutral {
            background: #9E9E9E;
            color: white;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .stat-item {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.5s ease;
        }

        .insights-list {
            list-style: none;
            margin-top: 15px;
        }

        .insights-list li {
            padding: 12px;
            background: #E3F2FD;
            border-left: 4px solid #2196F3;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .insights-list li i {
            color: #2196F3;
            margin-right: 10px;
        }

        .recommendations-list {
            list-style: none;
            margin-top: 15px;
        }

        .recommendations-list li {
            padding: 12px;
            background: #FFF3E0;
            border-left: 4px solid #FF9800;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .recommendations-list li i {
            color: #FF9800;
            margin-right: 10px;
        }

        .entries-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }

        .entries-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }

        .entries-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .entries-table tr:hover {
            background: #f5f5f5;
        }

        .error-box {
            background: #ffebee;
            border: 2px solid #f44336;
            border-radius: 10px;
            padding: 20px;
            color: #c62828;
        }

        .error-box i {
            font-size: 24px;
            margin-right: 10px;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #764ba2;
        }

        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .summary-box h3 {
            font-size: 18px;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .summary-text {
            font-size: 16px;
            line-height: 1.6;
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-map-marked-alt"></i> 현재 위치 평가</h1>
            <p>학생: <?php echo $studentname; ?> | Agent 14 - Current Position Analysis</p>
        </div>

        <div id="loadingBox" class="loading">
            <i class="fas fa-spinner"></i>
            <p style="margin-top: 20px; color: #666;">데이터를 분석하는 중입니다...</p>
        </div>

        <div id="dashboardContent" style="display: none;">
            <!-- Summary Box -->
            <div class="summary-box">
                <h3><i class="fas fa-robot"></i> Agent 요약 (다른 에이전트 전달용)</h3>
                <div class="summary-text" id="agentSummary"></div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard">
                <!-- Overall Status Card -->
                <div class="card">
                    <h2><i class="fas fa-chart-line"></i> 전체 진행 상태</h2>
                    <div id="overallStatus"></div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="completionProgress"></div>
                    </div>
                </div>

                <!-- Emotional State Card -->
                <div class="card">
                    <h2><i class="fas fa-heart"></i> 감정 상태</h2>
                    <div id="emotionalState"></div>
                </div>

                <!-- Statistics Card -->
                <div class="card">
                    <h2><i class="fas fa-chart-bar"></i> 통계</h2>
                    <div class="stat-grid" id="statisticsGrid"></div>
                </div>
            </div>

            <!-- Insights Card -->
            <div class="card">
                <h2><i class="fas fa-lightbulb"></i> 분석 인사이트</h2>
                <ul class="insights-list" id="insightsList"></ul>
            </div>

            <!-- Recommendations Card -->
            <div class="card">
                <h2><i class="fas fa-clipboard-check"></i> 추천 사항</h2>
                <ul class="recommendations-list" id="recommendationsList"></ul>
            </div>

            <!-- Entries Detail Card -->
            <div class="card">
                <h2><i class="fas fa-list"></i> 세부 항목 분석</h2>
                <table class="entries-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>계획</th>
                            <th>예상시간</th>
                            <th>실제완료</th>
                            <th>지연</th>
                            <th>상태</th>
                            <th>만족도</th>
                        </tr>
                    </thead>
                    <tbody id="entriesTableBody"></tbody>
                </table>
            </div>

            <a href="../../../students/goals42.php?id=<?php echo $studentid; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> 목표관리 페이지로 돌아가기
            </a>
        </div>

        <div id="errorBox" style="display: none;" class="card error-box">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="errorMessage"></span>
        </div>
    </div>

    <script>
        // API 호출 및 데이터 렌더링
        fetch('../agent.php?userid=<?php echo $studentid; ?>')
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingBox').style.display = 'none';

                if (!data.success) {
                    showError(data.error || '데이터를 불러오는데 실패했습니다.');
                    return;
                }

                const analysisData = data.data;

                // 데이터 없음 처리
                if (analysisData.status === 'no_data') {
                    showError(analysisData.message);
                    return;
                }

                // 대시보드 표시
                document.getElementById('dashboardContent').style.display = 'block';

                // Agent 요약
                document.getElementById('agentSummary').textContent = analysisData.agent_summary;

                // 전체 진행 상태
                const statusClass = getStatusClass(analysisData.overall_status);
                document.getElementById('overallStatus').innerHTML = `
                    <p style="font-size: 18px; margin: 10px 0;">진행 상태</p>
                    <span class="status-badge ${statusClass}">${analysisData.overall_status}</span>
                `;

                // 완료율 프로그레스 바
                const completionRate = analysisData.completion_rate || 0;
                document.getElementById('completionProgress').style.width = completionRate + '%';
                document.getElementById('completionProgress').textContent = completionRate.toFixed(1) + '%';

                // 감정 상태
                const emotionClass = getEmotionClass(analysisData.emotional_state);
                document.getElementById('emotionalState').innerHTML = `
                    <p style="font-size: 18px; margin: 10px 0;">감정 상태</p>
                    <span class="emotion-badge ${emotionClass}">${analysisData.emotional_state}</span>
                    <div style="margin-top: 15px; font-size: 14px; color: #666;">
                        <div>매우만족: ${analysisData.statistics.satisfaction.매우만족}개</div>
                        <div>만족: ${analysisData.statistics.satisfaction.만족}개</div>
                        <div>불만족: ${analysisData.statistics.satisfaction.불만족}개</div>
                    </div>
                `;

                // 통계
                const stats = analysisData.statistics;
                document.getElementById('statisticsGrid').innerHTML = `
                    <div class="stat-item">
                        <div class="stat-value">${stats.total_entries}</div>
                        <div class="stat-label">전체 항목</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${stats.completed}</div>
                        <div class="stat-label">완료</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${stats.delayed}</div>
                        <div class="stat-label">지연</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${stats.on_time}</div>
                        <div class="stat-label">적절</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${stats.early}</div>
                        <div class="stat-label">원활</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${stats.total_planned_minutes}</div>
                        <div class="stat-label">계획 시간 (분)</div>
                    </div>
                `;

                // 인사이트
                let insightsHTML = '';
                analysisData.insights.forEach(insight => {
                    insightsHTML += `<li><i class="fas fa-info-circle"></i>${insight}</li>`;
                });
                document.getElementById('insightsList').innerHTML = insightsHTML;

                // 추천사항
                let recommendationsHTML = '';
                analysisData.recommendations.forEach(rec => {
                    recommendationsHTML += `<li><i class="fas fa-check-circle"></i>${rec}</li>`;
                });
                document.getElementById('recommendationsList').innerHTML = recommendationsHTML;

                // 세부 항목
                let entriesHTML = '';
                analysisData.entries.forEach(entry => {
                    const expectedTime = formatTime(entry.expected_start) + '~' + formatTime(entry.expected_end);
                    const actualTime = entry.actual_completion ? formatTime(entry.actual_completion) : '-';
                    const delay = entry.delay_minutes !== null ? entry.delay_minutes + '분' : '-';
                    const statusBadge = getStatusBadge(entry.progress_status);
                    const satisfactionBadge = entry.status || '-';

                    entriesHTML += `
                        <tr>
                            <td><strong>${entry.index}</strong></td>
                            <td>${entry.plan}</td>
                            <td>${expectedTime}</td>
                            <td>${actualTime}</td>
                            <td>${delay}</td>
                            <td>${statusBadge}</td>
                            <td>${satisfactionBadge}</td>
                        </tr>
                    `;
                });
                document.getElementById('entriesTableBody').innerHTML = entriesHTML;
            })
            .catch(error => {
                document.getElementById('loadingBox').style.display = 'none';
                showError('API 호출 오류: ' + error.message);
            });

        function showError(message) {
            document.getElementById('errorBox').style.display = 'block';
            document.getElementById('errorMessage').textContent = message;
        }

        function getStatusClass(status) {
            if (status === '지연') return 'status-delayed';
            if (status === '적절') return 'status-ontime';
            if (status === '원활') return 'status-early';
            return '';
        }

        function getEmotionClass(emotion) {
            if (emotion === '매우 긍정') return 'emotion-very-positive';
            if (emotion === '긍정') return 'emotion-positive';
            if (emotion === '부정') return 'emotion-negative';
            return 'emotion-neutral';
        }

        function getStatusBadge(status) {
            const classes = {
                '지연': 'status-delayed',
                '적절': 'status-ontime',
                '원활': 'status-early',
                '미완료': 'emotion-neutral'
            };
            const className = classes[status] || 'emotion-neutral';
            return `<span class="status-badge ${className}">${status}</span>`;
        }

        function formatTime(unixtime) {
            const date = new Date(unixtime * 1000);
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return hours + ':' + minutes;
        }
    </script>
</body>
</html>
