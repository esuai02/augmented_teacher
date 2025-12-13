<?php
// 오디오 디렉토리 설정 스크립트

$audioDir = __DIR__ . '/audio/';

echo "오디오 디렉토리 설정 중...\n";
echo "경로: " . $audioDir . "\n";

// 디렉토리 생성
if (!file_exists($audioDir)) {
    if (mkdir($audioDir, 0755, true)) {
        echo "✓ 디렉토리가 성공적으로 생성되었습니다.\n";
    } else {
        echo "✗ 디렉토리 생성 실패\n";
        exit(1);
    }
} else {
    echo "✓ 디렉토리가 이미 존재합니다.\n";
}

// 권한 설정
if (chmod($audioDir, 0755)) {
    echo "✓ 디렉토리 권한이 설정되었습니다 (755).\n";
} else {
    echo "✗ 디렉토리 권한 설정 실패\n";
}

// 쓰기 권한 확인
if (is_writable($audioDir)) {
    echo "✓ 디렉토리에 쓰기 권한이 있습니다.\n";
} else {
    echo "✗ 디렉토리에 쓰기 권한이 없습니다.\n";
    echo "다음 명령을 실행해보세요: chmod 755 " . $audioDir . "\n";
}

// 테스트 파일 생성
$testFile = $audioDir . 'test.txt';
if (file_put_contents($testFile, 'test') !== false) {
    echo "✓ 테스트 파일 생성 성공\n";
    unlink($testFile);
} else {
    echo "✗ 테스트 파일 생성 실패\n";
}

echo "\n설정 완료!\n";
?>