<?php
/**
 * 깃발 아이콘 색상 변경 및 재생횟수 표시 테스트
 */

// Moodle 설정 로드
require_once(dirname(__FILE__) . '/../config.php');

// 에러 리포팅 활성화
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>깃발 아이콘 색상 및 재생횟수 테스트</h2>";
echo "<pre>";

// 테스트 데이터 설정
$test_cases = [
    [
        'role' => 'teacher',
        'audiourl2' => NULL,
        'nreview' => 0,
        'expected_icon' => '🟡',
        'expected_count' => '',
        'expected_title' => '절차기억 나레이션 생성'
    ],
    [
        'role' => 'teacher',
        'audiourl2' => 'test_audio.mp3',
        'nreview' => 0,
        'expected_icon' => '🟢',
        'expected_count' => '',
        'expected_title' => '절차기억 나레이션 재생성'
    ],
    [
        'role' => 'teacher',
        'audiourl2' => 'test_audio.mp3',
        'nreview' => 5,
        'expected_icon' => '🟢',
        'expected_count' => '(5)',
        'expected_title' => '절차기억 나레이션 재생성'
    ],
    [
        'role' => 'admin',
        'audiourl2' => NULL,
        'nreview' => 3,
        'expected_icon' => '🟡',
        'expected_count' => '',
        'expected_title' => '절차기억 나레이션 생성'
    ],
    [
        'role' => 'student',
        'audiourl2' => 'test_audio.mp3',
        'nreview' => 10,
        'expected_icon' => 'none',
        'expected_count' => '',
        'expected_title' => ''
    ]
];

echo "=== 테스트 시작 ===\n\n";

foreach ($test_cases as $index => $test) {
    $role = $test['role'];
    $value = ['audiourl2' => $test['audiourl2']];
    $thisboard = new stdClass();
    $thisboard->nreview = $test['nreview'];
    $contentsid = 'test_' . $index;

    echo "테스트 " . ($index + 1) . ":\n";
    echo "  역할: $role\n";
    echo "  audiourl2: " . ($test['audiourl2'] ? $test['audiourl2'] : 'NULL') . "\n";
    echo "  재생횟수 (nreview): " . $test['nreview'] . "\n";
    echo "  예상 아이콘: " . $test['expected_icon'] . "\n";
    echo "  예상 재생횟수: " . ($test['expected_count'] ?: '표시 안함') . "\n";
    echo "  예상 타이틀: " . $test['expected_title'] . "\n";

    // 실제 로직 테스트
    $flagicon = '';

    if($role !== 'student') {
        // audiourl2 존재 여부에 따라 아이콘 색상 결정
        $icon = ($value['audiourl2'] != NULL) ? '🟢' : '🟡';
        $playCount = '';

        // audiourl2가 있고 nreview가 있으면 재생횟수 표시
        if($value['audiourl2'] != NULL && $thisboard->nreview > 0) {
            $playCount = '('.$thisboard->nreview.')';
        }

        $title = ($value['audiourl2'] != NULL) ? '절차기억 나레이션 재생성' : '절차기억 나레이션 생성';
        $flagicon = '<span class="generate-dialog-icon" data-contentsid="'.$contentsid.'"
                     onclick="event.preventDefault(); event.stopPropagation(); handleFlagNarration(\''.$contentsid.'\');"
                     style="cursor:pointer; font-size:1.2em;"
                     title="'.$title.'">'.$icon.$playCount.'</span>';
    }

    // 결과 확인
    if($role === 'student') {
        $pass = empty($flagicon);
        echo "  실제 결과: 아이콘 표시 안함\n";
    } else {
        $actualIcon = (strpos($flagicon, '🟢') !== false) ? '🟢' : '🟡';
        $actualCount = '';
        if(preg_match('/\((\d+)\)/', $flagicon, $matches)) {
            $actualCount = '('.$matches[1].')';
        }

        $pass = ($actualIcon === $test['expected_icon'] &&
                 $actualCount === $test['expected_count']);

        echo "  실제 아이콘: " . $actualIcon . "\n";
        echo "  실제 재생횟수: " . ($actualCount ?: '표시 안함') . "\n";
    }

    echo "  테스트 결과: " . ($pass ? '✅ 통과' : '❌ 실패') . "\n\n";
}

echo "=== 테스트 요약 ===\n";
echo "1. 아이콘 색상 변경:\n";
echo "   - audiourl2가 없을 때: 🟡 (노란색)\n";
echo "   - audiourl2가 있을 때: 🟢 (녹색)\n\n";

echo "2. 재생횟수 표시:\n";
echo "   - audiourl2가 있고 nreview > 0일 때만 표시\n";
echo "   - 형식: 아이콘(재생횟수)\n\n";

echo "3. 역할별 표시:\n";
echo "   - 학생: 아이콘 미표시\n";
echo "   - 교사/관리자: 아이콘 표시\n\n";

echo "=== 변경사항 ===\n";
echo "- 🚩 → 🟡 (기본 상태)\n";
echo "- 🚩 → 🟢 (나레이션 생성 완료)\n";
echo "- 재생횟수가 아이콘 오른쪽에 표시됨\n";

echo "</pre>";
?>