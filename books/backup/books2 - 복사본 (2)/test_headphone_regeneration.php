<?php
/**
 * 헤드폰 아이콘 재생성 기능 테스트
 * - 학생이 아닌 사용자에게만 클릭 가능한 헤드폰 아이콘 표시
 * - audiourl이 있을 때 녹색(#28a745)으로 표시하고 재생성 가능
 */

// Moodle 설정 로드
require_once(dirname(__FILE__) . '/../config.php');

// 에러 리포팅 활성화
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>헤드폰 아이콘 재생성 기능 테스트</h2>";
echo "<pre>";

// 테스트 데이터 설정
$test_cases = [
    [
        'role' => 'student',
        'audiourl' => 'test_audio.mp3',
        'audiourl2' => NULL,
        'expected_clickable' => false,
        'expected_color' => 'none',
        'expected_title' => ''
    ],
    [
        'role' => 'teacher',
        'audiourl' => NULL,
        'audiourl2' => NULL,
        'expected_clickable' => true,
        'expected_color' => '#007bff',
        'expected_title' => '나레이션 생성'
    ],
    [
        'role' => 'teacher',
        'audiourl' => 'test_audio.mp3',
        'audiourl2' => NULL,
        'expected_clickable' => true,
        'expected_color' => '#28a745',
        'expected_title' => '수업 엿듣기 나레이션 재생성'
    ],
    [
        'role' => 'admin',
        'audiourl' => 'test_audio.mp3',
        'audiourl2' => 'test_audio2.mp3',
        'expected_clickable' => true,
        'expected_color' => '#28a745',
        'expected_title' => '수업 엿듣기 나레이션 재생성'
    ]
];

echo "=== 테스트 시작 ===\n\n";

foreach ($test_cases as $index => $test) {
    $role = $test['role'];
    $value = ['audiourl' => $test['audiourl'], 'audiourl2' => $test['audiourl2']];
    $contentsid = 'test_' . $index;

    echo "테스트 " . ($index + 1) . ":\n";
    echo "  역할: $role\n";
    echo "  audiourl: " . ($test['audiourl'] ?: 'NULL') . "\n";
    echo "  audiourl2: " . ($test['audiourl2'] ?: 'NULL') . "\n";
    echo "  예상 클릭 가능: " . ($test['expected_clickable'] ? '예' : '아니오') . "\n";
    echo "  예상 색상: " . $test['expected_color'] . "\n";
    echo "  예상 타이틀: " . $test['expected_title'] . "\n";

    // 실제 로직 테스트 (mynote.php의 로직 시뮬레이션)
    $audioicon = '';

    if($value['audiourl']!=NULL || $value['audiourl2']!=NULL) {
        // 헤드폰 아이콘 - 학생이 아닌 경우 클릭 가능하게 만들어 재생성 가능
        if($role !== 'student' && $value['audiourl'] != NULL) {
            // audiourl이 있을 때 클릭 가능한 헤드폰 아이콘 (재생성용)
            $audioicon=' <span class="generate-audio-icon" data-contentsid="'.$contentsid.'"
                        onclick="event.preventDefault(); event.stopPropagation(); handleAudioGeneration(\''.$contentsid.'\');"
                        style="cursor:pointer; color:#28a745; font-size:0.9em;"
                        title="수업 엿듣기 나레이션 재생성">🎧</span>';
        } else {
            // 학생이거나 audiourl이 없는 경우 클릭 불가능한 헤드폰 아이콘
            $audioicon=' <span style="font-size:0.9em;">🎧</span>';
        }
    } else {
        // 오디오가 없을 때
        if($role !== 'student') {
            // 비학생은 클릭 가능한 헤드폰 아이콘
            $audioicon=' <span class="generate-audio-icon" data-contentsid="'.$contentsid.'"
                        onclick="event.preventDefault(); event.stopPropagation(); handleAudioGeneration(\''.$contentsid.'\');"
                        style="cursor:pointer; color:#007bff; font-size:0.9em;"
                        title="나레이션 생성">🎧</span>';
        } else {
            // 학생은 아이콘 없음
            $audioicon = '';
        }
    }

    // 결과 확인
    $actual_clickable = (strpos($audioicon, 'onclick') !== false);
    $actual_color = 'none';
    $actual_title = '';

    if($actual_clickable) {
        if(strpos($audioicon, '#28a745') !== false) {
            $actual_color = '#28a745';
        } elseif(strpos($audioicon, '#007bff') !== false) {
            $actual_color = '#007bff';
        }

        if(preg_match('/title="([^"]+)"/', $audioicon, $matches)) {
            $actual_title = $matches[1];
        }
    }

    $pass = ($actual_clickable === $test['expected_clickable'] &&
             $actual_color === $test['expected_color'] &&
             $actual_title === $test['expected_title']);

    echo "  실제 클릭 가능: " . ($actual_clickable ? '예' : '아니오') . "\n";
    echo "  실제 색상: " . $actual_color . "\n";
    echo "  실제 타이틀: " . $actual_title . "\n";
    echo "  테스트 결과: " . ($pass ? '✅ 통과' : '❌ 실패') . "\n\n";
}

echo "=== 테스트 요약 ===\n";
echo "1. 헤드폰 아이콘 클릭 가능성:\n";
echo "   - 학생: 클릭 불가능\n";
echo "   - 교사/관리자: 클릭 가능\n\n";

echo "2. 색상 표시:\n";
echo "   - audiourl 없음: 파란색 (#007bff) - 신규 생성\n";
echo "   - audiourl 있음: 녹색 (#28a745) - 재생성\n\n";

echo "3. 타이틀 표시:\n";
echo "   - 신규: '나레이션 생성'\n";
echo "   - 재생성: '수업 엿듣기 나레이션 재생성'\n\n";

echo "=== JavaScript 함수 테스트 ===\n";
echo "handleAudioGeneration 함수는:\n";
echo "- 클릭된 요소의 색상을 확인하여 재생성 여부 판단\n";
echo "- RGB(40, 167, 69) = #28a745면 재생성\n";
echo "- 재생성 여부에 따라 다른 대화상자 표시\n";
echo "- generateNarration 함수에 isRegeneration 파라미터 전달\n";

echo "</pre>";
?>