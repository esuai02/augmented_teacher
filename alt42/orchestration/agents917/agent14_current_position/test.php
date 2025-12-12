<?php
/**
 * Agent 14 - Quick Test Page
 * File: alt42/orchestration/agents/agent14_current_position/test.php
 * Agent14 빠른 테스트 페이지
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = isset($_GET['id']) ? intval($_GET['id']) : $USER->id;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent14 테스트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2196F3;
            border-bottom: 3px solid #2196F3;
            padding-bottom: 10px;
        }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .test-section h2 {
            color: #666;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .link-box {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .link-btn {
            display: inline-block;
            padding: 15px 25px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            transition: background 0.3s;
        }
        .link-btn:hover {
            background: #1976D2;
        }
        .link-btn.secondary {
            background: #4CAF50;
        }
        .link-btn.secondary:hover {
            background: #388E3C;
        }
        .link-btn.tertiary {
            background: #FF9800;
        }
        .link-btn.tertiary:hover {
            background: #F57C00;
        }
        .code-box {
            background: #263238;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 15px 0;
        }
        .info-box {
            background: #E3F2FD;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
        }
        .success-box {
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Agent 14 - Current Position Evaluation Test</h1>

        <div class="info-box">
            <strong>📋 테스트 대상:</strong> 학생 ID <?php echo $studentid; ?><br>
            <strong>🎯 목적:</strong> 수학일기 기반 현재 위치 평가 및 진행 상태 분석
        </div>

        <!-- 1. Dashboard UI Test -->
        <div class="test-section">
            <h2>1️⃣ Dashboard UI 테스트</h2>
            <p>시각화된 대시보드에서 분석 결과를 확인합니다.</p>
            <div class="link-box">
                <a href="ui/dashboard.php?id=<?php echo $studentid; ?>" class="link-btn" target="_blank">
                    📊 대시보드 열기
                </a>
            </div>
        </div>

        <!-- 2. API Direct Test -->
        <div class="test-section">
            <h2>2️⃣ API 직접 테스트</h2>
            <p>JSON 형식으로 원시 데이터를 확인합니다.</p>
            <div class="link-box">
                <a href="agent.php?userid=<?php echo $studentid; ?>" class="link-btn secondary" target="_blank">
                    🔌 API 호출 (JSON)
                </a>
            </div>
            <div class="code-box">
GET /alt42/orchestration/agents/agent14_current_position/agent.php?userid=<?php echo $studentid; ?>
            </div>
        </div>

        <!-- 3. Goals42 Integration -->
        <div class="test-section">
            <h2>3️⃣ Goals42 통합 테스트</h2>
            <p>수학일기 페이지에서 Agent14 버튼을 확인합니다.</p>
            <div class="link-box">
                <a href="../../students/goals42.php?id=<?php echo $studentid; ?>" class="link-btn tertiary" target="_blank">
                    📝 수학일기 페이지 (goals42.php)
                </a>
            </div>
            <div class="success-box">
                <strong>✅ 확인 사항:</strong><br>
                • 수학일기 탭에 "현재 위치 평가 (Agent14)" 버튼이 있는지 확인<br>
                • 버튼 클릭 시 대시보드가 열리는지 확인
            </div>
        </div>

        <!-- 4. Test Data Setup -->
        <div class="test-section">
            <h2>4️⃣ 테스트 데이터 준비</h2>
            <p>다음 순서대로 테스트 데이터를 생성하세요:</p>
            <ol style="line-height: 2; margin-top: 15px;">
                <li><strong>수학일기 작성:</strong> goals42.php → 수학일기 탭 → 입력 모드</li>
                <li><strong>학습 계획 입력:</strong>
                    <ul style="margin-top: 10px;">
                        <li>항목 1: "미적분 복습" - 30분</li>
                        <li>항목 2: "수학 문제 풀이" - 45분</li>
                        <li>항목 3: "개념 정리" - 20분</li>
                    </ul>
                </li>
                <li><strong>보기 모드 전환:</strong> "보기 모드" 버튼 클릭</li>
                <li><strong>만족도 체크:</strong> 각 항목의 체크박스 클릭하여 만족도 선택
                    <ul style="margin-top: 10px;">
                        <li>매우만족 / 만족 / 불만족 중 선택</li>
                        <li>체크 시 자동으로 완료 시간(tend) 기록됨</li>
                    </ul>
                </li>
                <li><strong>Agent14 실행:</strong> "현재 위치 평가" 버튼 클릭</li>
            </ol>
        </div>

        <!-- 5. Expected Results -->
        <div class="test-section">
            <h2>5️⃣ 예상 결과</h2>
            <div class="info-box">
                <strong>📊 대시보드에 표시되는 정보:</strong><br><br>
                <strong>1. 전체 진행 상태:</strong> 지연/적절/원활 (±30분 기준)<br>
                <strong>2. 감정 상태:</strong> 매우 긍정/긍정/부정/중립<br>
                <strong>3. 완료율:</strong> 완료된 항목 / 전체 항목 × 100<br>
                <strong>4. 통계:</strong> 지연/적절/원활 항목 개수<br>
                <strong>5. 인사이트:</strong> 분석 결과에 따른 통찰<br>
                <strong>6. 추천사항:</strong> 개선을 위한 구체적 조언<br>
                <strong>7. 세부 분석:</strong> 각 항목별 예상 vs 실제 시간 비교
            </div>
        </div>

        <!-- 6. API Response Example -->
        <div class="test-section">
            <h2>6️⃣ API 응답 예시</h2>
            <div class="code-box" style="font-size: 12px; line-height: 1.6;">
{
  "success": true,
  "data": {
    "student_id": <?php echo $studentid; ?>,
    "overall_status": "적절",
    "emotional_state": "긍정",
    "completion_rate": 66.7,
    "statistics": {
      "total_entries": 3,
      "completed": 2,
      "delayed": 0,
      "on_time": 2,
      "early": 0,
      "satisfaction": {
        "매우만족": 1,
        "만족": 1,
        "불만족": 0
      }
    },
    "insights": [
      "대체로 계획대로 진행되고 있습니다.",
      "매우 긍정적인 학습 경험을 하고 있습니다."
    ],
    "recommendations": [
      "현재 페이스를 유지하며 계획된 목표를 진행하세요."
    ],
    "agent_summary": "[Agent14 분석] 완료율 66.7% | 진행상태: 적절..."
  }
}
            </div>
        </div>

        <!-- Back Button -->
        <div style="margin-top: 30px; text-align: center;">
            <a href="../../students/goals42.php?id=<?php echo $studentid; ?>"
               style="display: inline-block; padding: 12px 30px; background: #9E9E9E; color: white; text-decoration: none; border-radius: 8px;">
                ← 목표관리로 돌아가기
            </a>
        </div>
    </div>
</body>
</html>
