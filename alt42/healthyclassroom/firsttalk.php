<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
$studentid = $_GET["userid"] ?? null;

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학부모 페르소나 생성기</title>
    <link rel="stylesheet" href="css/firsttalk.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <span style="font-size: 40px;">👤</span>
                <h1>학부모 페르소나 생성기</h1>
            </div>
            <p class="header-subtitle">한국 학부모 특유의 말투와 패턴으로 자연스럽게 대화하는 AI를 만들어보세요</p>
            <p class="header-desc">조금씩 털어놓는 리얼한 상담 → 실제와 같은 대화 흐름 경험</p>
            <div class="usage-tip">
                <span style="color: #fbbf24; font-weight: 500;">💡 사용법:</span>
                <span style="color: #cbd5e1; font-size: 0.875rem;">수업 중 만났던 특정 학부모를 떠올린 다음 진행하면 효과적입니다.</span>
            </div>
        </div>

        <!-- 선택 영역 - 투칼럼 그리드 -->
        <div class="selection-grid">
            <div class="selection-column">
                <!-- 학년 선택 -->
                <div class="card">
                    <h2 class="card-title">
                        <span>🎓</span>
                        학년 선택
                    </h2>
                    <div class="grade-grid" id="grade-container">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                </div>

                <!-- 수준 선택 -->
                <div class="card" style="margin-top: 24px;">
                    <h2 class="card-title">
                        <span>🧠</span>
                        학업 수준
                    </h2>
                    <div class="level-list" id="level-container">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                </div>
            </div>

            <div class="selection-column">
                <!-- 걱정사항 선택 -->
                <div class="card">
                    <h2 class="card-title">
                        <span>⚠️</span>
                        학부모의 염려 <span style="font-size: 0.875rem; color: #94a3b8; margin-left: 8px;">(복수 선택 가능)</span>
                    </h2>
                    <div class="concern-categories" id="concerns-container">
                        <!-- JavaScript로 동적 생성 -->
                    </div>
                </div>
            </div>
        </div>

        <!-- 생성 버튼 -->
        <div class="action-buttons">
            <button onclick="generatePersona()" class="btn-primary" id="generate-btn" disabled>
                <span>💡</span>
                <span style="margin-left: 8px;">페르소나 생성하기</span>
            </button>
            <button onclick="resetForm()" class="btn-secondary">
                <span>🔄</span>
                <span style="margin-left: 8px;">초기화</span>
            </button>
        </div>

        <!-- 결과 영역 - 원칼럼 -->
        <div class="results-section">
            <div id="result-area">
                <!-- JavaScript로 동적 생성 -->
            </div>
            
            <div id="prompt-area">
                <!-- JavaScript로 동적 생성 -->
            </div>
        </div>
    </div>

    <!-- Loading Animation -->
    <div id="loading-animation" class="loading-animation hidden">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>ChatGPT로 이동 중...</p>
        </div>
    </div>

    <script src="js/firsttalk.js"></script>
</body>
</html>