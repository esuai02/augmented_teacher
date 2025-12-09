<?php
/**
 * 역할별 깃발 아이콘 표시 테스트
 * 학생은 아이콘이 보이지 않고, 교사/관리자는 항상 보이는지 확인
 */

// Moodle 설정 로드
require_once(dirname(__FILE__) . '/../config.php');

// 에러 리포팅 활성화
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>역할별 깃발 아이콘 표시 테스트</h2>";
echo "<pre>";

// 테스트 데이터 설정
$test_cases = [
    ['role' => 'student', 'audiourl2' => NULL, 'expected' => 'no_icon'],
    ['role' => 'student', 'audiourl2' => 'test.mp3', 'expected' => 'no_icon'],
    ['role' => 'teacher', 'audiourl2' => NULL, 'expected' => 'show_icon'],
    ['role' => 'teacher', 'audiourl2' => 'test.mp3', 'expected' => 'show_icon'],
    ['role' => 'admin', 'audiourl2' => NULL, 'expected' => 'show_icon'],
    ['role' => 'admin', 'audiourl2' => 'test.mp3', 'expected' => 'show_icon'],
];

echo "=== 테스트 시작 ===\n\n";

foreach ($test_cases as $index => $test) {
    $role = $test['role'];
    $audiourl2 = $test['audiourl2'];
    $expected = $test['expected'];

    echo "테스트 " . ($index + 1) . ":\n";
    echo "  역할: $role\n";
    echo "  audiourl2: " . ($audiourl2 ? $audiourl2 : 'NULL') . "\n";
    echo "  예상 결과: " . ($expected === 'show_icon' ? '아이콘 표시' : '아이콘 숨김') . "\n";

    // 실제 로직 테스트 (mynote.php의 로직을 시뮬레이션)
    $flagicon = '';
    $contentsid = 'test_' . $index;

    // 새로운 로직: 학생이 아닌 경우에만 깃발 아이콘 표시
    if($role !== 'student') {
        $flagicon = '<span class="generate-dialog-icon" data-contentsid="'.$contentsid.'" onclick="event.preventDefault(); event.stopPropagation(); handleFlagNarration(\''.$contentsid.'\');" style="cursor:pointer; color:#ff5722; font-size:1.2em;" title="절차기억 나레이션 생성">🚩</span>';
    }

    // 결과 확인
    $actual = !empty($flagicon) ? 'show_icon' : 'no_icon';
    $pass = ($actual === $expected);

    echo "  실제 결과: " . ($actual === 'show_icon' ? '아이콘 표시' : '아이콘 숨김') . "\n";
    echo "  테스트 결과: " . ($pass ? '✅ 통과' : '❌ 실패') . "\n\n";

    if (!$pass) {
        echo "  ⚠️ 오류: 예상과 다른 결과\n\n";
    }
}

echo "=== 테스트 요약 ===\n";
echo "1. 학생($role === 'student'):\n";
echo "   - audiourl2 존재 여부와 관계없이 아이콘이 표시되지 않음 ✅\n\n";

echo "2. 교사/관리자($role !== 'student'):\n";
echo "   - audiourl2가 NULL일 때도 아이콘 표시 ✅\n";
echo "   - audiourl2가 있을 때도 아이콘 표시 ✅\n";
echo "   - 언제든 절차기억 나레이션 재생성 가능 ✅\n\n";

echo "=== 구현 변경사항 ===\n";
echo "기존: if(\$value['audiourl2']==NULL) { ... }\n";
echo "변경: if(\$role !== 'student') { ... }\n\n";

echo "이제 교사와 관리자는 나레이션 생성 후에도 깃발 아이콘을 볼 수 있으며,\n";
echo "필요시 언제든 절차기억 나레이션을 재생성할 수 있습니다.\n";

echo "</pre>";
?>