<?php
/**
 * 60 페르소나 데이터 삽입 스크립트 (최소 버전)
 * audio_files 테이블 없이 작동
 */

require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;

require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

header('Content-Type: text/html; charset=utf-8');

// 60 페르소나 데이터 (첫 3개만 포함)
$patterns = [
    [
        'id' => 1,
        'name' => '아이디어 해방 자동발화형',
        'desc' => '번쩍이는 아이디어가 떠오르면 검증 없이 바로 써 내려가 결국 오답을 양산하는 패턴.',
        'category' => '인지 과부하',
        'icon' => 'brain',
        'priority' => 'high',
        'audioTime' => '2:15',
        'action' => '아이디어가 떠오르면 5초 멈춤 → "이게 정말 맞나?" 질문 → 가설로 적고 검증 표시(○△×) → 확실한 것만 답안에 사용.',
        'check' => '5초 멈춤→가설 쓰기 루틴을 세 번 성공했는지 확인, 답안지에 검증 표시가 있는지 체크.',
        'audioScript' => '번쩍이는 아이디어가 떠오르면 바로 답을 쓰고 싶죠? 하지만 잠깐! 5초만 멈춰보세요. "이게 정말 맞을까?" 스스로에게 물어보고, 확실하지 않다면 일단 가설로 적어두세요. 그리고 간단한 검증을 해보는 거예요. 이렇게 하면 실수를 많이 줄일 수 있어요.',
        'teacherDialog' => '선생님, 오늘 \'5초 멈춤→가설 쓰기\' 루틴을 연습했어요. 아이들이 처음엔 답답해했지만, 실제로 오답이 줄어드는 걸 보고 신기해했답니다.'
    ],
    [
        'id' => 2,
        'name' => '병렬 처리 과부하형',
        'desc' => '여러 조건을 동시에 처리하려다 놓치거나 혼동하여 실수를 만드는 패턴.',
        'category' => '인지 과부하',
        'icon' => 'brain',
        'priority' => 'high',
        'audioTime' => '2:30',
        'action' => '조건 나열하기 → 번호 매기기 → 하나씩 체크박스 만들어 해결 → 모든 박스에 체크되었는지 확인.',
        'check' => '문제의 모든 조건에 번호가 매겨져 있는지, 체크박스가 모두 채워져 있는지 확인.',
        'audioScript' => '복잡한 문제를 만나면 머릿속이 복잡해지죠? 여러 조건을 한 번에 처리하려고 하면 꼭 하나씩 놓치게 돼요. 그래서 우리는 조건을 하나씩 나열하고 번호를 매길 거예요. 그리고 각 조건 옆에 체크박스를 만들어서, 해결할 때마다 체크! 이렇게 하면 놓치는 조건이 없어져요.',
        'teacherDialog' => '체크박스 방법을 가르쳤더니, 한 학생이 "게임 퀘스트 같아요!"라고 하더라고요. 맞아요, 모든 퀘스트를 완료해야 다음 단계로 갈 수 있는 것처럼요.'
    ],
    [
        'id' => 3,
        'name' => '작업 기억 한계 초과형',
        'desc' => '계산 중간 결과를 머릿속에만 담아두려다 잊어버려 처음부터 다시 하는 패턴.',
        'category' => '인지 과부하',
        'icon' => 'brain',
        'priority' => 'medium',
        'audioTime' => '2:45',
        'action' => '계산 단계마다 중간 결과 적기 → "메모 은행"에 저장 → 필요할 때 꺼내 쓰기 → 최종 답 도출.',
        'check' => '메모 은행(여백 활용)에 중간 결과들이 정리되어 있는지, 계산 과정이 추적 가능한지 확인.',
        'audioScript' => '머릿속으로만 계산하다가 "어? 아까 뭐였더라?" 하면서 처음부터 다시 계산한 적 있나요? 우리 뇌는 한 번에 담을 수 있는 정보가 한정되어 있어요. 그래서 "메모 은행"을 만들어볼 거예요. 중간 결과를 여백에 적어두고, 필요할 때 꺼내 쓰는 거죠. 이게 바로 프로의 비밀이에요!',
        'teacherDialog' => '메모 은행 개념을 도입했더니, 계산 실수가 현저히 줄었어요. 한 아이는 "이제 제 뇌가 편해졌어요"라고 표현하더군요.'
    ]
];

// 카테고리 매핑
$category_map = [
    '인지 과부하' => ['code' => 'cognitive_overload', 'icon' => 'brain'],
    '자신감 왜곡' => ['code' => 'confidence_distortion', 'icon' => 'anxious'],
    '실수 패턴' => ['code' => 'mistake_patterns', 'icon' => 'error'],
    '접근 전략 오류' => ['code' => 'approach_errors', 'icon' => 'target'],
    '학습 습관' => ['code' => 'study_habits', 'icon' => 'book'],
    '시간/압박 관리' => ['code' => 'time_pressure', 'icon' => 'clock'],
    '검증/확인 부재' => ['code' => 'verification_absence', 'icon' => 'check'],
    '기타 장애' => ['code' => 'other_obstacles', 'icon' => 'tool']
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>60 페르소나 데이터 삽입 (최소 버전)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; color: #0c5460; }
        .warning { background-color: #fff3cd; color: #856404; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>60 페르소나 데이터 삽입 (최소 버전)</h1>
    
    <div class="status warning">
        <strong>주의:</strong> 이 버전은 audio_files 테이블 없이 작동합니다.<br>
        오디오 URL은 API에서 자동 생성됩니다.
    </div>

    <?php
    // 필요한 테이블 확인
    $dbman = $DB->get_manager();
    $required_tables = [
        'alt42i_pattern_categories' => true,
        'alt42i_math_patterns' => true,
        'alt42i_pattern_solutions' => true,
        'alt42i_audio_files' => false // 선택사항
    ];
    
    $missing_tables = [];
    foreach ($required_tables as $table => $required) {
        if ($required && !$dbman->table_exists($table)) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo "<div class='status error'>";
        echo "<strong>필수 테이블이 없습니다:</strong><br>";
        echo implode(', ', $missing_tables) . "<br>";
        echo "<a href='create_missing_tables.php'>테이블 생성하기</a>";
        echo "</div>";
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'insert') {
        echo "<h2>데이터 삽입 진행 중...</h2>";
        
        $transaction = $DB->start_delegated_transaction();
        
        try {
            // 1. 카테고리 확인 및 삽입
            echo "<h3>1. 카테고리 데이터 처리</h3>";
            $category_ids = [];
            $order = 1;
            
            foreach ($category_map as $name => $data) {
                $existing = $DB->get_record('alt42i_pattern_categories', ['category_name' => $name]);
                
                if (!$existing) {
                    $category = new stdClass();
                    $category->category_code = $data['code'];
                    $category->category_name = $name;
                    $category->display_order = $order++;
                    
                    $category_id = $DB->insert_record('alt42i_pattern_categories', $category);
                    $category_ids[$name] = $category_id;
                    echo "<div class='status success'>✓ 카테고리 추가됨: {$name}</div>";
                } else {
                    $category_ids[$name] = $existing->id;
                    echo "<div class='status info'>- 카테고리 이미 존재: {$name}</div>";
                }
            }
            
            // 2. 패턴 데이터 삽입
            echo "<h3>2. 패턴 데이터 처리</h3>";
            $inserted = 0;
            $updated = 0;
            
            foreach ($patterns as $pattern_data) {
                $existing = $DB->get_record('alt42i_math_patterns', ['pattern_id' => $pattern_data['id']]);
                
                $pattern = new stdClass();
                $pattern->pattern_id = $pattern_data['id'];
                $pattern->pattern_name = $pattern_data['name'];
                $pattern->pattern_desc = $pattern_data['desc'];
                $pattern->category_id = $category_ids[$pattern_data['category']];
                $pattern->icon = $pattern_data['icon'];
                $pattern->priority = $pattern_data['priority'];
                $pattern->audio_time = $pattern_data['audioTime'];
                $pattern->is_active = 1;
                
                if (!$existing) {
                    $pattern_id = $DB->insert_record('alt42i_math_patterns', $pattern);
                    $inserted++;
                    echo "<div class='status success'>✓ 패턴 #{$pattern_data['id']}: {$pattern_data['name']}</div>";
                    
                    // 솔루션 데이터
                    $solution = new stdClass();
                    $solution->pattern_id = $pattern_id;
                    $solution->action = $pattern_data['action'];
                    $solution->check_method = $pattern_data['check'];
                    $solution->audio_script = $pattern_data['audioScript'];
                    $solution->teacher_dialog = $pattern_data['teacherDialog'];
                    
                    $DB->insert_record('alt42i_pattern_solutions', $solution);
                    
                    // audio_files 테이블이 있는 경우에만 삽입
                    if ($dbman->table_exists('alt42i_audio_files')) {
                        $audio = new stdClass();
                        $audio->pattern_id = $pattern_id;
                        $audio->file_type = 'primary';
                        $audio->file_path = 'http://mathking.kr/Contents/personas/mathlearning/thinkinginertia' . 
                                          str_pad($pattern_data['id'], 2, '0', STR_PAD_LEFT) . '.mp3';
                        $audio->duration = $pattern_data['audioTime'];
                        
                        $DB->insert_record('alt42i_audio_files', $audio);
                    }
                    
                } else {
                    $pattern->id = $existing->id;
                    $DB->update_record('alt42i_math_patterns', $pattern);
                    $updated++;
                    echo "<div class='status info'>- 패턴 #{$pattern_data['id']} 업데이트됨</div>";
                }
            }
            
            $transaction->allow_commit();
            
            echo "<div class='status success'><strong>✓ 테스트 완료!</strong></div>";
            echo "<div class='status info'>새로 추가: {$inserted}개, 업데이트: {$updated}개</div>";
            
            if (!$dbman->table_exists('alt42i_audio_files')) {
                echo "<div class='status warning'>주의: audio_files 테이블이 없어서 오디오 정보는 저장하지 않았습니다. API에서 자동 생성됩니다.</div>";
            }
            
            echo "<hr>";
            echo "<p><a href='check_db_status.php'>데이터베이스 상태 확인</a></p>";
            echo "<p><a href='test_math_persona.html'>수학 인지관성 도감 테스트</a></p>";
            echo "<p><a href='index.php'>메인 페이지로 이동</a></p>";
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            echo "<div class='status error'>오류 발생: " . $e->getMessage() . "</div>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        
    } else {
        ?>
        <div class="status info">
            <strong>이 버전의 특징:</strong><br>
            - audio_files 테이블 없이도 작동<br>
            - 필수 테이블만 사용<br>
            - 이모지를 텍스트로 저장<br>
            - 처음 3개 패턴만 테스트
        </div>
        
        <form method="post">
            <input type="hidden" name="action" value="insert">
            <button type="submit">테스트 데이터 삽입 (3개 패턴)</button>
        </form>
        
        <hr>
        <h3>다른 옵션</h3>
        <ul>
            <li><a href="create_missing_tables.php">누락된 테이블 생성</a></li>
            <li><a href="check_db_status.php">데이터베이스 상태 확인</a></li>
            <li><a href="check_table_schema.php">테이블 구조 확인</a></li>
        </ul>
        <?php
    }
    ?>
</body>
</html>