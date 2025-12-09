<?php
// Moodle 설정 파일 포함
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;

// Moodle 로그인 확인
require_login();

// 교사 권한 확인
// 이름에 T가 포함되어 있으면 교사로 판단
$isTeacher = false;
if (strpos(strtoupper($USER->firstname), 'T') !== false || strpos(strtoupper($USER->lastname), 'T') !== false) {
    $isTeacher = true;
} else {
    // 이름에 T가 없으면 기존 방식으로 권한 확인
    $userrole = $DB->get_record_sql("SELECT data AS role FROM {user_info_data} WHERE userid = ? AND fieldid = 22", array($USER->id));
    if ($userrole && $userrole->role !== 'student') {
        $isTeacher = true;
    }
}

// 교사인 경우 출결관리 페이지로 리다이렉트
if ($isTeacher) {
    header('Location: teacher_attendance.php');
    exit;
} else {
    // 학생인 경우 안내 메시지
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>교사 전용 페이지</title>
        <style>
            body {
                font-family: 'Noto Sans KR', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                margin: 0;
            }
            .message-box {
                background: white;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
            h1 {
                color: #2d3748;
                margin-bottom: 20px;
            }
            p {
                color: #4a5568;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                transition: transform 0.3s ease;
            }
            .button:hover {
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="message-box">
            <h1>📚 교사 전용 페이지</h1>
            <p>
                이 페이지는 교사만 접근할 수 있습니다.<br>
                교사 계정으로 로그인하시거나,<br>
                학생이시라면 학생 포털을 이용해주세요.
            </p>
            <a href="https://mathking.kr" class="button">MathKing 메인으로 이동</a>
        </div>
    </body>
    </html>
    <?php
}
?>