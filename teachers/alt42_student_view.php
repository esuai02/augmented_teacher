<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$currentActivity = [
    'subject' => '수학',
    'unit' => '함수와 그래프',
    'currentStep' => '개념 예제 퀴즈',
    'progress' => 65
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>학생 페이지</title>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- 스타일 -->
    <style>
        /* 필요한 스타일을 여기에 추가하세요 */
        .student-page {
            padding: 16px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #2563EB;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 8px;
        }
        .btn-secondary {
            background-color: #6B7280;
        }
        .alert {
            padding: 12px;
            background-color: #FEF3C7;
            color: #92400E;
            border-radius: 4px;
            margin-top: 16px;
        }
    </style>
</head>
<body>

<div class="student-page">
    <!-- 활동 구조 메뉴 -->
    <div class="activity-structure">
        <div class="breadcrumbs">
            <span><?php echo $currentActivity['subject']; ?></span> &gt;
            <span><?php echo $currentActivity['unit']; ?></span> &gt;
            <span class="current"><?php echo $currentActivity['currentStep']; ?></span>
        </div>
        <div class="progress-bar" style="background-color: #E5E7EB; height: 8px; border-radius: 4px; margin-top: 8px;">
            <div class="progress" style="width: <?php echo $currentActivity['progress']; ?>%; background-color: #2563EB; height: 8px; border-radius: 4px;"></div>
        </div>
        <div class="progress-text" style="margin-top: 4px;">
            <?php echo $currentActivity['progress']; ?>% 완료
        </div>
    </div>

    <!-- 퀴즈 결과 알림 -->
    <div class="alert alert-warning">
        <strong>퀴즈 결과 알림</strong><br>
        최근 퀴즈에서 어려움이 있었네요. 도움이 필요하신가요?
    </div>

    <!-- 학습 지원 요청 버튼 -->
    <button class="btn" id="support-request-button">학습 지원 요청하기</button>
</div>

<!-- SweetAlert2 스크립트 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript 코드 -->
<script>
    document.getElementById('support-request-button').addEventListener('click', function() {
        Swal.fire({
            title: '학습 지원 옵션',
            text: '필요한 지원을 선택하세요.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '닫기',
            html:
                '<div style="display: flex; flex-direction: column; align-items: center;">' +
                '<a href="#" class="btn btn-secondary" style="margin-bottom: 8px;" onclick="requestHint()">힌트 요청</a>' +
                '<a href="#" class="btn btn-secondary" style="margin-bottom: 8px;" onclick="requestSolution()">풀이 요청</a>' +
                '<a href="#" class="btn btn-secondary" style="margin-bottom: 8px;" onclick="requestVoiceExplanation()">음성 해설</a>' +
                '<a href="#" class="btn btn-secondary" onclick="requestSupplementaryStudy()">보충 학습</a>' +
                '</div>',
            showConfirmButton: false,
        });
    });

    // 각 옵션에 대한 함수 정의
    function requestHint() {
        Swal.close();
        // 힌트 요청 처리 로직을 여기에 추가하세요.
        Swal.fire('힌트 요청', '힌트를 요청하였습니다.', 'success');
    }

    function requestSolution() {
        Swal.close();
        // 풀이 요청 처리 로직을 여기에 추가하세요.
        Swal.fire('풀이 요청', '풀이를 요청하였습니다.', 'success');
    }

    function requestVoiceExplanation() {
        Swal.close();
        // 음성 해설 요청 처리 로직을 여기에 추가하세요.
        Swal.fire('음성 해설', '음성 해설을 요청하였습니다.', 'success');
    }

    function requestSupplementaryStudy() {
        Swal.close();
        // 보충 학습 요청 처리 로직을 여기에 추가하세요.
        Swal.fire('보충 학습', '보충 학습을 시작합니다.', 'success');
    }
</script>

</body>
</html>
