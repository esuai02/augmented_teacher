<?php
/**
 * Agent 01 Onboarding - Main UI
 * File: agents/agent01_onboarding/index.php:1
 *
 * 학생 온보딩 및 프로필 관리 UI
 * 교사 피드백 패널 통합
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 학생 ID 가져오기
$studentid = $_GET["userid"] ?? $USER->id;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent 01 - 학생 온보딩</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #1e293b;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #64748b;
            font-size: 16px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        .panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .panel h2 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-weight: 600;
        }

        .info-value {
            color: #1e293b;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        .error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="header">
            <h1>🎓 Agent 01 - 학생 온보딩</h1>
            <p>학생 프로필 및 학습 이력 관리 시스템</p>
        </div>

        <div class="main-content">
            <!-- 좌측: 학생 프로필 -->
            <div class="panel">
                <h2>📋 학생 프로필</h2>
                <div id="student-profile">
                    <div class="loading">프로필을 불러오는 중...</div>
                </div>
            </div>

            <!-- 우측: 교사 피드백 패널 -->
            <div class="panel">
                <h2>👨‍🏫 교사 피드백</h2>
                <?php
                // 교사 피드백 패널 컴포넌트 로드
                include_once(__DIR__ . '/../common/components/teacher_feedback_panel.php');
                ?>
            </div>
        </div>
    </div>

    <script>
        /**
         * Agent 01 Main Script
         * @file index.php:149
         */

        const studentId = <?php echo $studentid; ?>;

        // 페이지 로드 시 학생 프로필 가져오기
        document.addEventListener('DOMContentLoaded', function() {
            loadStudentProfile();
        });

        /**
         * 학생 프로필 로드
         */
        async function loadStudentProfile() {
            try {
                const response = await fetch(`agent.php?userid=${studentId}`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status} [index.php:167]`);
                }

                const data = await response.json();
                console.log('[index.php:171] Student profile data:', data);

                if (data.success) {
                    displayStudentProfile(data.data);
                } else {
                    displayError(data.error || '프로필을 불러올 수 없습니다.');
                }

            } catch (error) {
                console.error('[index.php:180] Fetch error:', error);
                displayError('프로필 로드 중 오류가 발생했습니다: ' + error.message);
            }
        }

        /**
         * 학생 프로필 표시
         * @param {object} profile 프로필 데이터
         */
        function displayStudentProfile(profile) {
            const container = document.getElementById('student-profile');

            const html = `
                <div class="info-row">
                    <span class="info-label">학생 ID</span>
                    <span class="info-value">${profile.student_id}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">이름</span>
                    <span class="info-value">${escapeHtml(profile.student_name)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">이메일</span>
                    <span class="info-value">${escapeHtml(profile.email)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">MBTI</span>
                    <span class="info-value">${profile.mbti}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">프로필 상태</span>
                    <span class="info-value">${profile.profile_complete ? '✅ 완료' : '⚠️ 미완료'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">마지막 로그인</span>
                    <span class="info-value">${formatDate(profile.last_login)}</span>
                </div>
            `;

            container.innerHTML = html;
        }

        /**
         * 에러 표시
         * @param {string} message 에러 메시지
         */
        function displayError(message) {
            const container = document.getElementById('student-profile');
            container.innerHTML = `
                <div class="error">
                    <strong>오류:</strong> ${escapeHtml(message)}
                </div>
            `;
        }

        /**
         * HTML 이스케이프
         * @param {string} text 원본 텍스트
         * @returns {string} 이스케이프된 텍스트
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Unix timestamp를 날짜 문자열로 변환
         * @param {number} timestamp Unix timestamp
         * @returns {string} 날짜 문자열
         */
        function formatDate(timestamp) {
            if (!timestamp || timestamp === 0) {
                return '없음';
            }

            const date = new Date(timestamp * 1000);
            return date.toLocaleString('ko-KR');
        }
    </script>
</body>
</html>

<!--
Database Tables Used:
- mdl_user: 학생 기본 정보
  Fields: id, firstname, lastname, email, lastaccess
- mdl_alt42_student_profiles: 학생 프로필
  Fields: userid, mbti
- mdl_abessi_todayplans: 수학일기 (교사 피드백 패널에서 사용)
  Fields: userid, timecreated, plan1-16, due1-16, url1-16, status01-16
-->
