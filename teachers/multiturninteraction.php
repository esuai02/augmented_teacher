<?php
// 에러 표시 활성화 (가장 먼저 설정)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 페이지가 로드되었는지 확인하기 위한 기본 출력
echo "<!-- PHP 동작 확인 -->";

// 기본 변수만 설정
$currentTime = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>테스트 페이지</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
            margin: 40px;
            line-height: 1.6;
        }
        div {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>기본 테스트 페이지</h1>
    
    <div>
        <h2>기본 정보</h2>
        <p>현재 시간: <?php echo $currentTime; ?></p>
        <p>PHP 버전: <?php echo phpversion(); ?></p>
    </div>
    
    <div>
        <h2>서버 정보</h2>
        <p>서버 소프트웨어: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
        <p>실행 경로: <?php echo __FILE__; ?></p>
    </div>
</body>
</html> 