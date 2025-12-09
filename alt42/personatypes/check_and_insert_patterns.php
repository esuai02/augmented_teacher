<?php
/**
 * 60개 패턴 데이터 확인 및 삽입
 * 누락된 패턴이 있으면 자동으로 삽입
 */

// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;
require_login();

// 관리자 권한 확인
if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

echo "<h1>60개 패턴 데이터 확인 및 삽입</h1>";

// 현재 패턴 수 확인
$current_count = $DB->count_records('alt42i_math_patterns');
echo "<p>현재 저장된 패턴 수: $current_count / 60</p>";

if ($current_count < 60) {
    echo "<p>누락된 패턴을 삽입합니다...</p>";
    
    // 60개 패턴 데이터 (60personas.txt 기반)
    $patterns_data = [
        // 인지 과부하 (1-10)
        ['id' => 1, 'name' => '계산 순서 혼동', 'desc' => '복잡한 식에서 연산 순서를 헷갈려함', 'category' => 'cognitive_overload', 'icon' => '🔄', 'priority' => 'high'],
        ['id' => 2, 'name' => '부호 처리 실수', 'desc' => '음수와 양수 계산에서 부호를 놓침', 'category' => 'cognitive_overload', 'icon' => '➖', 'priority' => 'high'],
        ['id' => 3, 'name' => '단위 변환 어려움', 'desc' => '다른 단위 간 변환을 어려워함', 'category' => 'cognitive_overload', 'icon' => '📏', 'priority' => 'medium'],
        ['id' => 4, 'name' => '복잡한 분수 계산', 'desc' => '분수의 사칙연산에서 실수가 잦음', 'category' => 'cognitive_overload', 'icon' => '➗', 'priority' => 'high'],
        ['id' => 5, 'name' => '다단계 문제 해결', 'desc' => '여러 단계를 거쳐야 하는 문제를 어려워함', 'category' => 'cognitive_overload', 'icon' => '🎯', 'priority' => 'high'],
        ['id' => 6, 'name' => '변수 개념 혼동', 'desc' => '문자를 사용한 식에서 혼란을 느낌', 'category' => 'cognitive_overload', 'icon' => '🔤', 'priority' => 'medium'],
        ['id' => 7, 'name' => '공식 암기 부담', 'desc' => '많은 공식을 외우는 것을 힘들어함', 'category' => 'cognitive_overload', 'icon' => '📚', 'priority' => 'medium'],
        ['id' => 8, 'name' => '그래프 해석 어려움', 'desc' => '그래프와 식의 관계를 이해하기 어려워함', 'category' => 'cognitive_overload', 'icon' => '📊', 'priority' => 'medium'],
        ['id' => 9, 'name' => '증명 과정 이해', 'desc' => '논리적 증명 과정을 따라가기 힘들어함', 'category' => 'cognitive_overload', 'icon' => '🔍', 'priority' => 'low'],
        ['id' => 10, 'name' => '추상적 개념 이해', 'desc' => '추상적인 수학 개념을 구체화하기 어려워함', 'category' => 'cognitive_overload', 'icon' => '💭', 'priority' => 'low'],
        
        // 자신감 왜곡 (11-20)
        ['id' => 11, 'name' => '수학 공포증', 'desc' => '수학 자체에 대한 두려움과 거부감', 'category' => 'confidence_distortion', 'icon' => '😰', 'priority' => 'high'],
        ['id' => 12, 'name' => '실수 반복 두려움', 'desc' => '한번 틀린 유형을 다시 틀릴까 두려워함', 'category' => 'confidence_distortion', 'icon' => '😟', 'priority' => 'high'],
        ['id' => 13, 'name' => '비교 열등감', 'desc' => '다른 학생과 비교하며 자신감을 잃음', 'category' => 'confidence_distortion', 'icon' => '😔', 'priority' => 'medium'],
        ['id' => 14, 'name' => '완벽주의 압박', 'desc' => '모든 문제를 완벽하게 풀어야 한다는 부담', 'category' => 'confidence_distortion', 'icon' => '💯', 'priority' => 'medium'],
        ['id' => 15, 'name' => '시험 불안', 'desc' => '시험 상황에서 극도로 긴장함', 'category' => 'confidence_distortion', 'icon' => '📝', 'priority' => 'high'],
        ['id' => 16, 'name' => '질문 기피', 'desc' => '모르는 것을 물어보기 부끄러워함', 'category' => 'confidence_distortion', 'icon' => '🤐', 'priority' => 'medium'],
        ['id' => 17, 'name' => '포기 습관', 'desc' => '조금만 어려워도 쉽게 포기함', 'category' => 'confidence_distortion', 'icon' => '🏳️', 'priority' => 'high'],
        ['id' => 18, 'name' => '자기 능력 과소평가', 'desc' => '실제보다 자신의 능력을 낮게 평가함', 'category' => 'confidence_distortion', 'icon' => '📉', 'priority' => 'medium'],
        ['id' => 19, 'name' => '부정적 자기 대화', 'desc' => '"나는 수학을 못해"라는 생각에 갇힘', 'category' => 'confidence_distortion', 'icon' => '💬', 'priority' => 'medium'],
        ['id' => 20, 'name' => '성공 경험 무시', 'desc' => '잘 푼 문제는 무시하고 못 푼 문제만 기억함', 'category' => 'confidence_distortion', 'icon' => '🚫', 'priority' => 'low'],
        
        // 실수 패턴 (21-27)
        ['id' => 21, 'name' => '계산 실수', 'desc' => '단순 계산에서 자주 실수함', 'category' => 'mistake_patterns', 'icon' => '❌', 'priority' => 'high'],
        ['id' => 22, 'name' => '문제 오독', 'desc' => '문제를 제대로 읽지 않고 풀이 시작', 'category' => 'mistake_patterns', 'icon' => '👁️', 'priority' => 'high'],
        ['id' => 23, 'name' => '조건 누락', 'desc' => '문제의 중요한 조건을 놓침', 'category' => 'mistake_patterns', 'icon' => '⚠️', 'priority' => 'high'],
        ['id' => 24, 'name' => '단위 표기 누락', 'desc' => '답에 단위를 쓰지 않거나 잘못 씀', 'category' => 'mistake_patterns', 'icon' => '📐', 'priority' => 'medium'],
        ['id' => 25, 'name' => '중간 과정 생략', 'desc' => '풀이 과정을 건너뛰어 실수 발생', 'category' => 'mistake_patterns', 'icon' => '⏭️', 'priority' => 'medium'],
        ['id' => 26, 'name' => '검산 습관 부재', 'desc' => '답을 구한 후 확인하지 않음', 'category' => 'mistake_patterns', 'icon' => '🔄', 'priority' => 'high'],
        ['id' => 27, 'name' => '유사 문제 혼동', 'desc' => '비슷해 보이는 문제를 같은 방법으로 풂', 'category' => 'mistake_patterns', 'icon' => '🔀', 'priority' => 'medium'],
        
        // 접근 전략 오류 (28-37)
        ['id' => 28, 'name' => '무작정 계산', 'desc' => '문제 파악 없이 바로 계산 시작', 'category' => 'approach_errors', 'icon' => '🏃', 'priority' => 'high'],
        ['id' => 29, 'name' => '패턴 무시', 'desc' => '문제의 규칙성을 파악하지 못함', 'category' => 'approach_errors', 'icon' => '🔢', 'priority' => 'medium'],
        ['id' => 30, 'name' => '역방향 사고 부족', 'desc' => '답에서 거꾸로 생각하는 방법을 모름', 'category' => 'approach_errors', 'icon' => '⬅️', 'priority' => 'medium'],
        ['id' => 31, 'name' => '도구 활용 미숙', 'desc' => '그림, 표, 도형 등을 활용하지 못함', 'category' => 'approach_errors', 'icon' => '🛠️', 'priority' => 'medium'],
        ['id' => 32, 'name' => '일반화 능력 부족', 'desc' => '특수한 경우에서 일반 원리를 찾지 못함', 'category' => 'approach_errors', 'icon' => '🌐', 'priority' => 'low'],
        ['id' => 33, 'name' => '문제 분해 실패', 'desc' => '복잡한 문제를 작은 단위로 나누지 못함', 'category' => 'approach_errors', 'icon' => '🧩', 'priority' => 'high'],
        ['id' => 34, 'name' => '유형 의존', 'desc' => '암기한 유형에만 의존하여 응용력 부족', 'category' => 'approach_errors', 'icon' => '📋', 'priority' => 'high'],
        ['id' => 35, 'name' => '시행착오 회피', 'desc' => '틀릴까봐 다양한 방법을 시도하지 않음', 'category' => 'approach_errors', 'icon' => '🚧', 'priority' => 'medium'],
        ['id' => 36, 'name' => '핵심 개념 파악 실패', 'desc' => '문제가 묻는 핵심을 파악하지 못함', 'category' => 'approach_errors', 'icon' => '🎯', 'priority' => 'high'],
        ['id' => 37, 'name' => '연결성 인식 부족', 'desc' => '배운 개념들 간의 연결을 보지 못함', 'category' => 'approach_errors', 'icon' => '🔗', 'priority' => 'medium'],
        
        // 학습 습관 (38-47)
        ['id' => 38, 'name' => '벼락치기', 'desc' => '시험 직전에만 집중적으로 공부함', 'category' => 'study_habits', 'icon' => '⚡', 'priority' => 'high'],
        ['id' => 39, 'name' => '반복 학습 부족', 'desc' => '한 번 푼 문제를 다시 풀어보지 않음', 'category' => 'study_habits', 'icon' => '🔁', 'priority' => 'high'],
        ['id' => 40, 'name' => '오답 정리 미흡', 'desc' => '틀린 문제를 제대로 분석하지 않음', 'category' => 'study_habits', 'icon' => '📝', 'priority' => 'high'],
        ['id' => 41, 'name' => '개념 이해 없는 문제풀이', 'desc' => '개념 학습 없이 문제만 많이 풂', 'category' => 'study_habits', 'icon' => '📖', 'priority' => 'high'],
        ['id' => 42, 'name' => '정리 노트 부재', 'desc' => '배운 내용을 체계적으로 정리하지 않음', 'category' => 'study_habits', 'icon' => '📓', 'priority' => 'medium'],
        ['id' => 43, 'name' => '질문 미루기', 'desc' => '모르는 것을 그때그때 해결하지 않음', 'category' => 'study_habits', 'icon' => '❓', 'priority' => 'medium'],
        ['id' => 44, 'name' => '학습 계획 부재', 'desc' => '체계적인 학습 계획 없이 공부함', 'category' => 'study_habits', 'icon' => '📅', 'priority' => 'medium'],
        ['id' => 45, 'name' => '집중력 부족', 'desc' => '공부할 때 자주 딴짓을 함', 'category' => 'study_habits', 'icon' => '🎯', 'priority' => 'high'],
        ['id' => 46, 'name' => '피드백 무시', 'desc' => '선생님의 조언을 실천하지 않음', 'category' => 'study_habits', 'icon' => '💬', 'priority' => 'medium'],
        ['id' => 47, 'name' => '자기 주도 학습 부족', 'desc' => '스스로 공부하는 습관이 없음', 'category' => 'study_habits', 'icon' => '🎓', 'priority' => 'medium'],
        
        // 시간/압박 관리 (48-53)
        ['id' => 48, 'name' => '시간 배분 실패', 'desc' => '쉬운 문제에 너무 많은 시간 소비', 'category' => 'time_pressure', 'icon' => '⏰', 'priority' => 'high'],
        ['id' => 49, 'name' => '마감 압박 스트레스', 'desc' => '시간이 부족하면 극도로 당황함', 'category' => 'time_pressure', 'icon' => '⏱️', 'priority' => 'high'],
        ['id' => 50, 'name' => '속도 조절 실패', 'desc' => '너무 빨리 풀거나 너무 느리게 풂', 'category' => 'time_pressure', 'icon' => '🏃', 'priority' => 'medium'],
        ['id' => 51, 'name' => '우선순위 설정 오류', 'desc' => '중요한 문제와 덜 중요한 문제 구분 못함', 'category' => 'time_pressure', 'icon' => '📊', 'priority' => 'medium'],
        ['id' => 52, 'name' => '마무리 습관 부재', 'desc' => '시험 종료 전 검토 시간을 갖지 않음', 'category' => 'time_pressure', 'icon' => '✅', 'priority' => 'high'],
        ['id' => 53, 'name' => '시간 예측 오류', 'desc' => '문제 풀이 시간을 잘못 예상함', 'category' => 'time_pressure', 'icon' => '🔮', 'priority' => 'low'],
        
        // 검증/확인 부재 (54-57)
        ['id' => 54, 'name' => '답 검증 생략', 'desc' => '구한 답이 맞는지 확인하지 않음', 'category' => 'verification_absence', 'icon' => '✔️', 'priority' => 'high'],
        ['id' => 55, 'name' => '과정 검토 부족', 'desc' => '풀이 과정의 논리성을 점검하지 않음', 'category' => 'verification_absence', 'icon' => '🔍', 'priority' => 'high'],
        ['id' => 56, 'name' => '대입 검증 미사용', 'desc' => '답을 원래 식에 대입해보지 않음', 'category' => 'verification_absence', 'icon' => '↩️', 'priority' => 'medium'],
        ['id' => 57, 'name' => '상식 검토 부재', 'desc' => '답이 현실적으로 타당한지 생각하지 않음', 'category' => 'verification_absence', 'icon' => '💡', 'priority' => 'medium'],
        
        // 기타 장애 (58-60)
        ['id' => 58, 'name' => '주의력 산만', 'desc' => '문제 풀이 중 집중력이 흐트러짐', 'category' => 'other_obstacles', 'icon' => '🌀', 'priority' => 'medium'],
        ['id' => 59, 'name' => '환경적 방해', 'desc' => '주변 환경이 공부를 방해함', 'category' => 'other_obstacles', 'icon' => '🔊', 'priority' => 'low'],
        ['id' => 60, 'name' => '신체적 피로', 'desc' => '피곤하여 수학 공부에 집중하기 어려움', 'category' => 'other_obstacles', 'icon' => '😴', 'priority' => 'low']
    ];
    
    // 카테고리 ID 매핑
    $category_map = [
        'cognitive_overload' => 1,
        'confidence_distortion' => 2,
        'mistake_patterns' => 3,
        'approach_errors' => 4,
        'study_habits' => 5,
        'time_pressure' => 6,
        'verification_absence' => 7,
        'other_obstacles' => 8
    ];
    
    $inserted = 0;
    $updated = 0;
    
    foreach ($patterns_data as $pattern) {
        // 이미 존재하는지 확인
        $existing = $DB->get_record('alt42i_math_patterns', ['pattern_id' => $pattern['id']]);
        
        if (!$existing) {
            // 새로 삽입
            $record = new stdClass();
            $record->pattern_id = $pattern['id'];
            $record->pattern_name = $pattern['name'];
            $record->pattern_desc = $pattern['desc'];
            $record->category_id = $category_map[$pattern['category']];
            $record->icon = $pattern['icon'];
            $record->priority = $pattern['priority'];
            $record->audio_time = '3:00'; // 기본값
            $record->is_active = 1;
            $record->created_at = time();
            
            $DB->insert_record('alt42i_math_patterns', $record);
            $inserted++;
        } else {
            // 업데이트 (필요시)
            $existing->pattern_name = $pattern['name'];
            $existing->pattern_desc = $pattern['desc'];
            $existing->icon = $pattern['icon'];
            $existing->priority = $pattern['priority'];
            
            $DB->update_record('alt42i_math_patterns', $existing);
            $updated++;
        }
    }
    
    echo "<p>✅ 작업 완료: $inserted 개 삽입, $updated 개 업데이트</p>";
    
    // 솔루션 데이터도 확인/삽입
    $solution_count = $DB->count_records('alt42i_pattern_solutions');
    echo "<p>현재 솔루션 수: $solution_count</p>";
    
    if ($solution_count < 60) {
        echo "<p>솔루션 데이터를 삽입합니다...</p>";
        
        // 모든 패턴에 대해 기본 솔루션 생성
        $patterns = $DB->get_records('alt42i_math_patterns');
        $solution_inserted = 0;
        
        foreach ($patterns as $pattern) {
            $existing_solution = $DB->get_record('alt42i_pattern_solutions', ['pattern_id' => $pattern->id]);
            
            if (!$existing_solution) {
                $solution = new stdClass();
                $solution->pattern_id = $pattern->id;
                $solution->action = "이 패턴을 극복하기 위한 구체적인 행동 방법";
                $solution->check_method = "개선 여부를 확인하는 방법";
                $solution->audio_script = "음성 가이드 스크립트";
                $solution->teacher_dialog = "교사를 위한 대화 가이드";
                $solution->created_at = time();
                
                $DB->insert_record('alt42i_pattern_solutions', $solution);
                $solution_inserted++;
            }
        }
        
        echo "<p>✅ 솔루션 삽입 완료: $solution_inserted 개</p>";
    }
    
    // 오디오 파일 정보 업데이트
    $audio_count = $DB->count_records('alt42i_pattern_audio_files');
    echo "<p>현재 오디오 파일 수: $audio_count</p>";
    
    if ($audio_count < 60) {
        echo "<p>오디오 파일 정보를 삽입합니다...</p>";
        
        $patterns = $DB->get_records('alt42i_math_patterns');
        $audio_inserted = 0;
        
        foreach ($patterns as $pattern) {
            $existing_audio = $DB->get_record('alt42i_pattern_audio_files', ['pattern_id' => $pattern->id]);
            
            if (!$existing_audio) {
                $audio = new stdClass();
                $audio->pattern_id = $pattern->id;
                $audio->file_url = 'http://mathking.kr/Contents/personas/mathlearning/thinkinginertia' . 
                                  str_pad($pattern->pattern_id, 2, '0', STR_PAD_LEFT) . '.mp3';
                $audio->duration = '180'; // 3분
                $audio->transcript = "음성 내용 전사본";
                $audio->created_at = time();
                
                $DB->insert_record('alt42i_pattern_audio_files', $audio);
                $audio_inserted++;
            }
        }
        
        echo "<p>✅ 오디오 정보 삽입 완료: $audio_inserted 개</p>";
    }
}

// 최종 확인
$final_count = $DB->count_records('alt42i_math_patterns');
$final_solution_count = $DB->count_records('alt42i_pattern_solutions');
$final_audio_count = $DB->count_records('alt42i_pattern_audio_files');

echo "<h2>최종 데이터 상태</h2>";
echo "<ul>";
echo "<li>패턴: $final_count / 60</li>";
echo "<li>솔루션: $final_solution_count / 60</li>";
echo "<li>오디오: $final_audio_count / 60</li>";
echo "</ul>";

if ($final_count == 60 && $final_solution_count == 60 && $final_audio_count == 60) {
    echo '<p style="color: green; font-weight: bold;">✅ 모든 데이터가 정상적으로 준비되었습니다!</p>';
} else {
    echo '<p style="color: red; font-weight: bold;">⚠️ 일부 데이터가 누락되었습니다.</p>';
}

echo '<p><a href="test_patterns.php">패턴 테스트 페이지로 이동</a></p>';
echo '<p><a href="test_math_persona.html">수학 인지관성 도감 테스트 페이지로 이동</a></p>';
?>