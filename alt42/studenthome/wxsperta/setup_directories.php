<?php
// 디렉토리 생성 스크립트 (별도로 실행)

$base_dir = __DIR__;
$dirs = [
    $base_dir . '/logs',
    $base_dir . '/cache'
];

echo "<h2>디렉토리 생성</h2>\n";
echo "<pre>\n";

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0755, true)) {
            echo "✓ 생성됨: $dir\n";
        } else {
            echo "✗ 실패: $dir - 수동으로 생성해주세요.\n";
            echo "  명령어: mkdir -p $dir && chmod 755 $dir\n";
        }
    } else {
        echo "✓ 이미 존재: $dir\n";
    }
}

echo "</pre>\n";
echo "<p>디렉토리를 수동으로 생성하려면:</p>\n";
echo "<pre>\n";
echo "cd " . $base_dir . "\n";
echo "mkdir -p logs cache\n";
echo "chmod 755 logs cache\n";
echo "</pre>\n";
?>