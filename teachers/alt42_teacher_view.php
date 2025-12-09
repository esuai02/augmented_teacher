<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 이탈 이벤트 데이터
$deviationEvents = [
    [
        'id' => 1,
        'student' => '김학생',
        'type' => 'quiz_failure',
        'timestamp' => '2024-11-20 14:30',
        'page' => '이차함수의 성질 - 개념 예제 퀴즈',
        'details' => '3문제 연속 오답'
    ]
];

// 선택된 이벤트 ID를 세션 또는 GET 파라미터에서 가져옵니다.
$selectedEventId = isset($_GET['event_id']) ? $_GET['event_id'] : null;

// 피드백 플로우를 보여줄지 여부
$showFeedbackFlow = isset($_GET['feedback']) && $_GET['feedback'] == '1';

if ($showFeedbackFlow) {
    include 'alt42_feedback_flow.php';
} else {
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>학습 이탈 알림</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .teacher-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px;
        }
        .col-8, .col-4 {
            padding: 10px;
        }
        .col-8 {
            flex: 0 0 66.666%;
        }
        .col-4 {
            flex: 0 0 33.333%;
        }
        .card {
            background-color: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        h2 {
            margin-top: 0;
            border-bottom: 2px solid #eeeeee;
            padding-bottom: 10px;
        }
        .event {
            border-bottom: 1px solid #eeeeee;
            padding: 15px 0;
        }
        .event:last-child {
            border-bottom: none;
        }
        .event.selected {
            background-color: #e9f5ff;
        }
        .event h3 {
            margin: 0 0 10px 0;
        }
        .event p {
            margin: 5px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            color: #ffffff;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:disabled, .btn.btn-disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .feedback-template {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .feedback-template h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
<div class="teacher-page">
    <div class="row">
        <!-- 이탈 알림 대시보드 -->
        <div class="col-8">
            <div class="card">
                <h2>학습 이탈 알림</h2>
                <?php foreach ($deviationEvents as $event): ?>
                <div class="event <?php echo $selectedEventId == $event['id'] ? 'selected' : ''; ?>">
                    <h3><?php echo $event['student']; ?></h3>
                    <p><strong>페이지:</strong> <?php echo $event['page']; ?></p>
                    <p><strong>시간:</strong> <?php echo $event['timestamp']; ?></p>
                    <p><strong>세부사항:</strong> <?php echo $event['details']; ?></p>
                    <a href="?view=teacher&event_id=<?php echo $event['id']; ?>" class="btn">선택</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 피드백 생성 패널 -->
        <div class="col-4">
            <div class="card">
                <h2>피드백 작성</h2>
                <div class="feedback-template">
                    <h3>개념 이해 강화 필요</h3>
                    <p>기본 개념부터 차근차근 다시 살펴보면 좋겠어요.</p>
                </div>
                <?php if ($selectedEventId): ?>
                <!-- 피드백 선택하기 버튼 -->
                <a href="?view=teacher&event_id=<?php echo $selectedEventId; ?>&feedback=1" class="btn">피드백 선택하기</a>
                <?php else: ?>
                <!-- 버튼 비활성화 -->
                <button class="btn btn-disabled" disabled>피드백 선택하기</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php } ?>
