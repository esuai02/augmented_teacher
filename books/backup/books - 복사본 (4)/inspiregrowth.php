<?php 
/////////////////////////////// code snippet ///////////////////////////////
// 오류 표시 설정 추가
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
// 디버깅 함수 추가
function debug_log($message) {
    error_log(print_r($message, true));
}

// 기본값 설정
$initial_ratings = [
    'interprete' => 5,
    'ideate' => 0,
    'solve' => 0
];
$wboard_id = '';
$studentid = 0;

try {
    $studentid = isset($_GET["userid"]) ? $_GET["userid"] : (isset($USER->id) ? $USER->id : 0);
    debug_log("학생 ID: " . $studentid);
    
    // 학생의 화이트보드 ID 가져오기
    $halfdayago = time() - 43200;
    
    // 최근 1주일간의 도전 문제 목록 가져오기
    $weekago = time() - 604800;
    $query = "SELECT * FROM mdl_abessi_messages_rating
              WHERE userid='$studentid' 
              AND status='complete'
              AND timemodified > '$weekago' 
              ORDER BY timemodified DESC";
    $recent_challenges = $DB->get_records_sql($query);
    
    $query = "SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND student_check='1' AND timemodified > '$halfdayago' ORDER BY timemodified DESC LIMIT 1";
    debug_log("실행 쿼리: " . $query);
    
    $student_whiteboard = $DB->get_record_sql($query);
    
    if ($student_whiteboard) {
        debug_log("화이트보드 찾음: " . print_r($student_whiteboard, true));
        $wboard_id = isset($student_whiteboard->wboardid) ? $student_whiteboard->wboardid : '';
        $contentsid = isset($student_whiteboard->contentsid) ? $student_whiteboard->contentsid : '';
        $contentstype = isset($student_whiteboard->contentstype) ? $student_whiteboard->contentstype : '';
    } else {
        debug_log("화이트보드 기록 없음");
    }
    
    // 평가 데이터 가져오기 (wboardid로 조회)
    if ($wboard_id) {
        $query = "SELECT * FROM mdl_abessi_messages_rating WHERE wboardid='$wboard_id' AND status='begin' AND timemodified > '$halfdayago' ORDER BY timemodified DESC LIMIT 1";
        debug_log("평가 쿼리: " . $query);
        
        $rating_data = $DB->get_record_sql($query);
        
        if ($rating_data) {
            debug_log("평가 데이터 찾음: " . print_r($rating_data, true));
            
            // 학생 정보가 있다면 데이터베이스에서 가져온 값 사용
            $initial_ratings = [
                'interprete' => $rating_data->interprete,
                'ideate' => $rating_data->ideate,
                'solve' => $rating_data->solve
            ];
        } else {
            debug_log("평가 데이터 없음, 기본값 사용");
         
            /*
            // 학생 정보가 없지만 화이트보드 ID가 있으면 새로운 레코드 생성
            $new_record = new stdClass();
            $new_record->wboardid = $wboard_id;
            $new_record->interprete = 0;
            $new_record->ideate = 0;
            $new_record->solve = 0;
            $new_record->timecreated = time();
            $new_record->timemodified = time();
            
            // 레코드 삽입 시도
            $new_id = $DB->insert_record('abessi_messages_rating', $new_record);
            debug_log("새 레코드 생성 결과: " . $new_id);
            */
        }
    } else {
        debug_log("화이트보드 ID 없음, 기본값 사용");
    }
} catch (Exception $e) {
    debug_log("오류 발생: " . $e->getMessage());
}

// AJAX 요청 처리
if (isset($_POST['action'])) {
    $response = [
        'success' => false,
        'message' => '처리 실패'
    ];
    
    try {
        if ($_POST['action'] === 'update_ratings') {
            if (isset($_POST['wboardid']) && isset($_POST['interprete']) && isset($_POST['ideate']) && isset($_POST['solve'])) {
                $wboardid = $_POST['wboardid'];
                $interprete = floatval($_POST['interprete']);
                $ideate = floatval($_POST['ideate']);
                $solve = floatval($_POST['solve']);
                
                // 레코드 확인
                $record = $DB->get_record_sql("SELECT * FROM mdl_abessi_messages_rating WHERE wboardid='$wboardid' AND timemodified > '$halfdayago' AND status='begin' ORDER BY id DESC LIMIT 1");
                
                if ($record) {
                    // 기존 레코드 업데이트
                    $record->interprete = $interprete;
                    $record->ideate = $ideate;
                    $record->solve = $solve;
                    $record->timemodified = time();
                    
                    $result = $DB->update_record('abessi_messages_rating', $record);
                    
                    if ($result) {
                        $response = [
                            'success' => true,
                            'message' => '데이터 업데이트 성공'
                        ];
                    }
                } else if ($wboardid) {
                    /*
                    // 레코드가 없지만 wboardid가 있으면 새로 생성
                    $new_record = new stdClass();
                    $new_record->wboardid = $wboardid;
                    $new_record->interprete = $interprete;
                    $new_record->ideate = $ideate;
                    $new_record->solve = $solve;
                    $new_record->timecreated = time();
                    $new_record->timemodified = time();
                    
                    $new_id = $DB->insert_record('abessi_messages_rating', $new_record);
                    */
                    if ($new_id) {
                        $response = [
                            'success' => true,
                            'message' => '데이터 생성 성공'
                        ];
                    }
                }
            }
        } else if ($_POST['action'] === 'complete_status') {
            if (isset($_POST['wboardid']) && isset($_POST['status'])) {
                $wboardid = $_POST['wboardid'];
                $status = $_POST['status'];
                
                // 메시지 테이블에서 상태 업데이트
                $record = $DB->get_record_sql("SELECT * FROM mdl_abessi_messages WHERE wboardid='$wboardid' ORDER BY id DESC LIMIT 1");
                
                if ($record) {
                    $record->status = $status;
                    $record->timemodified = time();
                    
                    $result = $DB->update_record('abessi_messages', $record);
                    
                    if ($result) {
                        // rating 테이블에서도 상태 업데이트
                        $rating_record = $DB->get_record_sql("SELECT * FROM mdl_abessi_messages_rating WHERE wboardid='$wboardid' AND status='begin' AND timemodified > '$halfdayago' ORDER BY id DESC LIMIT 1");
                        
                        if ($rating_record) {
                            $rating_record->status = $status;
                            $rating_record->timemodified = time();
                            
                            $rating_result = $DB->update_record('abessi_messages_rating', $rating_record);
                            
                            if ($rating_result) {
                                $response = [
                                    'success' => true,
                                    'message' => '상태 업데이트 성공'
                                ];
                            } else {
                                $response = [
                                    'success' => false,
                                    'message' => '평가 상태 업데이트 실패'
                                ];
                            }
                        } else {
                            /*
                            // rating 레코드가 없으면 새로 생성
                            $new_rating = new stdClass();
                            $new_rating->wboardid = $wboardid;
                            $new_rating->status = $status;
                            $new_rating->timecreated = time();
                            $new_rating->timemodified = time();
                            
                            $new_rating_id = $DB->insert_record('abessi_messages_rating', $new_rating);
                            */
                            if ($new_rating_id) {
                                $response = [
                                    'success' => true,
                                    'message' => '상태 업데이트 성공'
                                ];
                            } else {
                                $response = [
                                    'success' => false,
                                    'message' => '평가 상태 생성 실패'
                                ];
                            }
                        }
                    } else {
                        $response = [
                            'success' => false,
                            'message' => '메시지 상태 업데이트 실패'
                        ];
                    }
                } else {
                    $response = [
                        'success' => false,
                        'message' => '레코드를 찾을 수 없습니다'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => '필수 파라미터가 누락되었습니다'
                ];
            }
        }
    } catch (Exception $e) {
        $response['message'] = '오류: ' . $e->getMessage();
        debug_log("AJAX 오류: " . $e->getMessage());
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// 스테이지 라벨 정의
$stage_labels = [
    'interprete' => ["해석 : 못함", "해석 : 초기", "해석 : 맥락 해석", "해석 : 핵심 파악", "해석 : 준비 완료"],
    'ideate' => ["발상 : 시작 못함", "발상 : 초기 아이디어", "발상 : 방향포착", "발상 : 효율적 접근", "발상 : 최적화 접근"],
    'solve' => ["풀이 : 시작 못함", "풀이 : 풀이 시작", "풀이 : 전반적인 풀이", "풀이 : 중요 부분 완료", "풀이 : 완전히 해결"]
];

// 각 평가 항목의 단계 인덱스 계산
$stages = [0, 2, 5, 8, 10];

function getNearestStageIndex($value, $stages) {
    $closest_index = 0;
    $min_distance = 100;
    
    foreach ($stages as $index => $stage) {
        $distance = abs($stage - $value);
        if ($distance < $min_distance) {
            $min_distance = distance;
            $closest_index = $index;
        }
    }
    
    return $closest_index;
}

$interprete_index = getNearestStageIndex($initial_ratings['interprete'], $stages);
$ideate_index = getNearestStageIndex($initial_ratings['ideate'], $stages);
$solve_index = getNearestStageIndex($initial_ratings['solve'], $stages);

// 총 진행도 계산
$total_progress = round(($initial_ratings['interprete'] + $initial_ratings['ideate'] + $initial_ratings['solve']) / 30 * 100);

// 성장 마인드셋 메시지 배열 추가
$growth_mindset_messages = [
    "수업이 진행되면서 보이지 않던 부분이 보이고 점점 더 나아지는 것을 느껴보세요. 막힌 지점에 길이 있습니다.",
    "조금 전보다 지금 문제를 더 잘 보고 있다면, 당신의 뇌는 이미 성장 중입니다.",
    "막혔던 그 문제, 조금 전과는 다른 눈으로 보고 있나요? 뇌의 변화입니다.",
    "불과 몇 분 전의 당신보다 지금의 당신이 문제에 한발 더 다가섰습니다.",
    "조금 전 이해하지 못했던 개념이 지금 선명하게 다가온다면, 당신의 뇌는 방금 전 성장했습니다.",
    "같은 문제라도 조금 전과 다른 답을 떠올릴 수 있다면, 이미 성장형 뇌의 힘이 작용하고 있습니다.",
    "조금 전까지 보이지 않던 길이 지금 보인다면, 당신의 뇌가 막 성장했기 때문입니다.",
    "바로 조금 전에 막힌 지점이 지금 명확해졌다면, 축하합니다, 당신의 뇌는 지금 성장했습니다.",
    "몇 초 전과 다른 이해가 생겼다면, 당신은 지금 성장형 뇌를 경험하고 있습니다.",
    "지금의 이해가 조금 전의 혼란을 이겼다면, 당신의 뇌는 성장하고 있습니다.",
    "조금 전 어렵다고 생각한 문제를 지금은 다르게 바라보고 있습니다. 바로 이것이 성장입니다.",
    "조금 전과 지금 사이에 일어난 인지 변화는 당신의 뇌가 성장했다는 강력한 증거입니다.",
    "조금 전의 당신보다 문제를 더 정확하게 보고 있다면, 그 차이가 바로 뇌의 성장을 말해줍니다.",
    "조금 전과 비교해 이해가 깊어진 지금, 당신은 성장형 뇌를 체험 중입니다.",
    "잠깐 사이의 변화가 의미 있게 느껴진다면, 바로 그 순간 당신의 뇌가 성장한 것입니다.",
    "같은 문제를 조금 전과 다르게 느낀다면, 성장형 뇌가 작동하는 순간을 경험 중입니다.",
    "조금 전의 문제해결 방식과 지금 방식의 차이가 바로 성장형 뇌의 증거입니다.",
    "문제에 대한 당신의 시선이 조금 전과 확연히 달라졌다면, 바로 이것이 뇌의 성장입니다.",
    "조금 전까지 닫혀 있던 길이 지금 열렸다면, 뇌가 스스로 새로운 연결을 만든 것입니다.",
    "방금 전 문제에 답이 없다고 느꼈다면, 지금의 발견은 당신의 뇌가 방금 성장했다는 증거입니다.",
    "조금 전의 어려움을 지금의 깨달음으로 바꾼 건 당신의 성장형 뇌 덕분입니다.",
    "조금 전까지 알 수 없던 것을 지금 이해하게 되었다면, 그것은 뇌의 성장을 의미합니다.",
    "방금 전에 없던 답이 지금 눈앞에 나타났다면, 당신의 뇌는 이미 성장했습니다.",
    "조금 전까지의 막막함이 사라졌다면, 지금 이 순간 당신의 뇌는 성장 중입니다.",
    "조금 전의 고민과 지금의 명확한 이해 사이에는 뇌의 성장이 존재합니다.",
    "방금 전과 다른 아이디어를 떠올렸다면, 당신의 뇌는 이미 다음 단계로 성장한 것입니다.",
    "조금 전보다 명확해진 이 문제는 당신이 성장하고 있음을 보여줍니다.",
    "조금 전의 혼돈이 지금의 명확함으로 변했다면, 당신은 지금 성장형 뇌를 경험하고 있습니다.",
    "조금 전의 당신과 지금의 당신이 다르다면, 바로 그 차이가 뇌의 성장입니다.",
    "조금 전의 막힘과 지금의 뚫림 사이, 당신의 뇌는 분명히 성장했습니다.",
    "조금 전까지 모호했던 것이 명확하게 보인다면, 당신의 뇌는 방금 전에 성장했습니다.",
    "방금 전 어려웠던 그 문제를 지금 더 잘 이해한다면, 당신의 뇌는 계속 성장 중입니다.",
    "조금 전과는 다른 명쾌한 이해가 지금 생겼다면, 당신의 뇌는 그 짧은 순간 성장한 것입니다.",
    "조금 전까지 멈춰 있던 생각이 지금 움직이고 있다면, 뇌의 성장을 경험하는 중입니다.",
    "방금 전의 답답함이 지금은 분명함으로 바뀌었다면, 당신의 뇌는 성장형 뇌입니다.",
    "조금 전의 혼란이 지금 정리되는 느낌이라면, 당신의 뇌는 방금 전 성장을 경험했습니다.",
    "조금 전의 막연한 감각이 지금 구체적으로 변했다면, 성장형 뇌가 작동하고 있습니다.",
    "방금 전까지 어두웠던 문제가 지금 밝아졌다면, 당신의 뇌는 방금 성장했습니다.",
    "조금 전보다 훨씬 편안하게 문제를 바라본다면, 당신의 뇌는 성장형입니다.",
    "같은 문제를 조금 전보다 깊이 이해했다면, 바로 이것이 성장형 뇌입니다.",
    "조금 전까지 보이지 않던 해결책이 지금 눈앞에 있다면, 당신의 뇌는 성장했습니다.",
    "조금 전과 지금의 이해력 차이가 당신의 성장형 뇌를 보여줍니다.",
    "방금 전과 다르게 문제를 보게 된 지금의 시각, 당신의 뇌는 방금 성장했습니다.",
    "조금 전까지 어렵던 문제가 지금 간단하게 느껴진다면, 당신은 뇌의 성장을 경험 중입니다.",
    "조금 전과 지금 사이에서 변화된 당신의 사고방식이 바로 성장의 증거입니다.",
    "방금 전까지 해결 불가능해 보였던 문제가 지금 가능해 보인다면, 뇌가 성장한 것입니다.",
    "조금 전보다 당신이 뚜렷한 시야를 가지게 되었다면, 바로 그 순간 당신의 뇌는 성장했습니다.",
    "조금 전과 비교하여 지금의 명확한 시야는 뇌의 성장을 분명하게 나타냅니다.",
    "조금 전까지는 어려웠던 문제를 지금 쉽게 풀 수 있다면, 당신의 뇌는 방금 성장했습니다.",
    "조금 전의 시각과 지금의 시각이 다르다면, 당신은 성장형 뇌를 체험하고 있습니다.",
    "조금 전 이해하지 못한 것을 지금 이해했다면, 뇌가 그 짧은 순간 성장한 것입니다.",
    "방금 전 모호했던 개념이 지금 명확하게 이해된다면, 당신은 뇌의 성장을 경험 중입니다.",
    "조금 전과 지금의 차이는 성장형 뇌가 작동하고 있다는 신호입니다.",
    "조금 전과 다른 아이디어가 떠오른 지금, 당신의 뇌는 이미 성장하고 있습니다.",
    "방금 전보다 지금 더 잘 보이는 이 문제, 당신의 뇌는 분명 성장했습니다.",
    "조금 전까지 흐릿했던 문제가 지금 뚜렷해졌다면, 성장형 뇌가 활성화된 순간입니다.",
    "방금 전까지 생각하지 못했던 것을 지금 생각했다면, 당신의 뇌는 방금 성장했습니다.",
    "조금 전과 다른 통찰이 지금 당신에게 나타났다면, 뇌가 방금 전에 성장한 것입니다.",
    "조금 전의 답답함이 지금의 명확함으로 바뀌는 과정에서, 당신의 뇌는 성장했습니다.",
    "방금 전의 혼란이 지금의 분명한 해답으로 바뀌었다면, 당신의 뇌는 성장 중입니다.",
    "조금 전과 지금을 비교하며 당신의 성장을 인지하세요. 바로 이것이 성장형 마인드셋입니다."
];

// 랜덤 메시지 선택
$random_message = $growth_mindset_messages[array_rand($growth_mindset_messages)];

// "뇌"와 "성장" 단어를 노란색으로 강조
$random_message = str_replace("뇌", "<span style=\"color: yellow;\">뇌</span>", $random_message);
$random_message = str_replace("성장", "<span style=\"color: yellow;\">성장</span>", $random_message);

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학 학습 진단</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif;
            background-color: #1a2035;
            color: #e4e6eb;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        
        .container {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0;
            margin: 0;
        }
        
        .header {
            background: linear-gradient(to right, #1a2035, #292d3e);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #3a3f55;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: bold;
            background: linear-gradient(to right, #a78bfa, #ec4899, #f43f5e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .progress-display {
            display: flex;
            align-items: center;
        }
        
        .progress-icon {
            margin-right: 8px;
            color: #8b5cf6;
        }
        
        .main-content {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        .whiteboard-section {
            flex: 0.8;
            border-right: 1px solid #3a3f55;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .rating-section {
            flex: 0.2;
            background: linear-gradient(to bottom right, #1f2937, #1d253c);
            display: flex;
            flex-direction: column;
            padding: 10px;
            overflow-y: auto;
        }
        
        .steps-title {
            color: #a78bfa;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
            padding: 5px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .step-item {
            color: #d1d5db;
            font-size: 14px;
            padding: 4px 8px;
            border-radius: 4px;
            background: rgba(31, 41, 55, 0.5);
            transition: background-color 0.3s ease;
        }
        
        .step-item.active {
            background: rgba(139, 92, 246, 0.3);
            color: #fff;
        }
        
        .whiteboard-header {
            background: linear-gradient(to right, #1f2937, #374151);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #3a3f55;
        }
        
        .whiteboard-title {
            display: flex;
            align-items: center;
        }
        
        .whiteboard-icon {
            margin-right: 8px;
            color: #a78bfa;
        }
        
        .focus-button {
            background-color: rgba(139, 92, 246, 0.2);
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: #a78bfa;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.2s;
            margin-right: 10px;
        }
        
        .focus-button:hover {
            background-color: rgba(139, 92, 246, 0.3);
        }
        
        .fullscreen-button {
            background-color: rgba(139, 92, 246, 0.2);
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: #a78bfa;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .fullscreen-button:hover {
            background-color: rgba(139, 92, 246, 0.3);
        }
        
        .fullscreen-icon {
            margin-left: 4px;
        }
        
        .whiteboard-container {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        
        iframe {
            width: 100%;
            height: 100%;
            border: none;
            background-color: #f1f5f9;
        }
        
        .rating-title {
            color: #a78bfa;
            font-weight: 500;
            margin-bottom: 20px;
            font-size: 18px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(109, 40, 217, 0.3);
        }
        
        .rating-items {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 5px;
        }
        
        .rating-item {
            background-color: rgba(31, 41, 55, 0.5);
            border: 1px solid rgba(75, 85, 99, 0.7);
            border-radius: 8px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .rating-label {
            font-size: 16px;
            color: #d1d5db;
            margin-bottom: 12px;
            font-weight: 500;
        }
        
        .star-container {
            display: flex;
            margin: 8px 0;
        }
        
        .star {
            font-size: 28px;
            cursor: pointer;
            transition: transform 0.2s;
            color: #4b5563;
            margin: 0 2px;
            position: relative;
        }
        
        .star:hover {
            transform: scale(1.1);
        }
        
        .star.active {
            color: #f59e0b;
        }
        
        /* 툴팁 스타일 추가 */
        .star-tooltip {
            position: absolute;
            background-color: rgba(17, 24, 39, 0.9);
            color: #e4e6eb;
            font-size: 12px;
            padding: 5px 8px;
            border-radius: 4px;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            z-index: 10;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            pointer-events: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(75, 85, 99, 0.7);
        }
        
        .star:hover .star-tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        .rating-description {
            font-size: 14px;
            text-align: center;
            color: #9ca3af;
            margin-top: 10px;
        }
        
        /* 완료 버튼 스타일 */
        .complete-button {
            background: linear-gradient(to right, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .complete-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        .complete-button:active {
            transform: translateY(0);
        }
        
        /* 모바일에서 툴팁 위치 조정 */
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            
            .whiteboard-section {
                flex: none;
                height: 60vh;
                border-right: none;
                border-bottom: 1px solid #3a3f55;
            }
            
            .rating-section {
                flex: none;
                height: calc(40vh - 0px); /* 헤더 높이 고려 */
            }
            
            .rating-items {
                flex-direction: row;
                overflow-x: auto;
                gap: 10px;
                padding-bottom: 10px;
            }
            
            .rating-item {
                min-width: 200px;
            }
            
            .progress-container {
                margin-top: 10px;
                padding-top: 0px;
            }
            
            .star-tooltip {
                bottom: -25px;
            }
        }
        
        .progress-container {
            margin-top: 10px;
            padding-top: 0px;
            border-top: 1px solid rgba(109, 40, 217, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .progress-value {
            font-size: 16px;
            color: #d1d5db;
            font-weight: 600;
            text-align: center;
            margin: 10px 0;
        }
        
        /* 기존 프로그레스 바 제거하고 나무 스타일 추가 */
        .tree-container {
            width: 120px;
            height: 160px;
            position: relative;
            margin: 10px auto;
        }
        
        .tree-trunk {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            background: linear-gradient(to top, #8B4513, #A0522D);
            border-radius: 3px;
            transition: height 1s ease-out;
        }
        
        .tree-leaves {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(to bottom right, #4CAF50, #388E3C);
            transition: all 1s ease-out;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .tree-leaves.small {
            width: 40px;
            height: 40px;
            opacity: 0.7;
        }
        
        .tree-leaves.medium {
            width: 60px;
            height: 60px;
            opacity: 0.8;
        }
        
        .tree-leaves.large {
            width: 80px;
            height: 80px;
            opacity: 1;
        }
        
        .tree-soil {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 15px;
            background: linear-gradient(to bottom, #5D4037, #3E2723);
            border-radius: 50% 50% 5px 5px;
        }
        
        .tree-label {
            font-size: 14px;
            color: #9ca3af;
            text-align: center;
            margin-top: 10px;
        }

        /* 중세 성 건설 시각화 스타일 추가 */
        .castle-container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .svg-container {
            width: 100%;
            height: 250px;
            margin-bottom: 5px;
            background-color: #f0f9ff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .progress-container {
            width: 100%;
            max-width: 100%;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid rgba(109, 40, 217, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .progress-bar-container {
            width: 90%;
            height: 24px;
            background-color: #e5e7eb;
            border-radius: 9999px;
            position: relative;
            cursor: pointer;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 8px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, #3b82f6, #6366f1);
            border-radius: 9999px;
            transition: width 0.1s ease-out;
        }
        
        .progress-handle {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            margin: auto 0;
            width: 24px;
            height: 24px;
            background-color: white;
            border: 2px solid #3b82f6;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transform: translateX(-50%);
            pointer-events: none;
        }
        
        .progress-labels {
            width: 100%;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        
        .progress-value {
            font-size: 16px;
            font-weight: 600;
            color: #d1d5db;
            text-align: center;
            margin: 10px 0;
        }
        
        .progress-description {
            font-size: 14px;
            color: #9ca3af;
            text-align: center;
            margin-top: 8px;
        }
        
        @media (max-width: 768px) {
            .svg-container {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="card-title">오늘의 도전 문제</div>
            <div class="progress-display">
                <div class="progress-icon">📊</div>
                <span style="font-size: 14px; color: #9ca3af;">진행도: </span>
                <span style="font-size: 14px; font-weight: bold; color: white; margin-left: 4px;"><?php echo $total_progress; ?>%</span>
            
            </div>
            <div id="real-time-clock" style="font-size: 14px; color: #a78bfa; margin-left: 15px;"></div>
          </div>
        
        <div class="main-content">
            <!-- 화이트보드 섹션 (왼쪽 70%) -->
            <div class="whiteboard-section">
                <div class="whiteboard-header">
                    <div class="whiteboard-title">
                        <div class="whiteboard-icon">🧠</div>
                        <span style="font-size: 14px; font-weight: 500;"><b>생각변화 추적하기 </b>  <?php echo $random_message; ?></span>
              </div>
              
                    <div style="display: flex; align-items: center;">
                        <?php if ($role !== 'student'): ?>
                        <button class="focus-button" onclick="openFocusSession()">
                            <span>Focus Session</span>
                        </button>
                        <?php endif; ?>
                        <button class="fullscreen-button" onclick="openFullscreen()">
                            <span>전체화면</span>
                            <span class="fullscreen-icon">→</span>
                        </button>
                    </div>
            </div>
                <div class="whiteboard-container">
            <?php if (empty($wboard_id) || empty($contentsid) || empty($contentstype) || empty($rating_data)): ?>
                <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100%; color: #666; font-size: 18px;">
                    <div style="margin-bottom: 20px;">지면평가가 없습니다.</div>
                    <div style="display: flex; gap: 20px; font-size: 14px; margin-bottom: 30px;">
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id=<?php echo $studentid; ?>" style="color: #a78bfa; text-decoration: none;">내 공부방</a>
                        <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today.php?id=<?php echo $studentid; ?>&tb=604800" style="color: #a78bfa; text-decoration: none;">활동결과</a>
                        <a href="http://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid=<?php echo $studentid; ?>" style="color: #a78bfa; text-decoration: none;">학습일지</a>
                    </div>
                    <?php if (!empty($recent_challenges)): ?>
                        <div style="width: 80%; max-width: 600px; text-align: left;">
                            <div style="font-size: 16px; color: #a78bfa; margin-bottom: 15px;">최근 1주일간의 도전 문제</div>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($recent_challenges as $challenge): ?>
                                    <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id=<?php echo $challenge->wboardid; ?>" target="_blank"
                                       style="color: #666; text-decoration: none; padding: 10px; background: rgba(167, 139, 250, 0.1); border-radius: 8px; transition: all 0.2s;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span><?php echo date('Y-m-d H:i', $challenge->timemodified); ?></span>
                                            <span style="color: #a78bfa;">→</span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <iframe 
                    id="whiteboard-iframe"
                    src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id=<?php echo $wboard_id; ?>&contentsid=<?php echo $contentsid; ?>&contentstype=<?php echo $contentstype; ?>&studentid=<?php echo $studentid; ?>" 
                    title="학습 화이트보드"
                ></iframe>
            <?php endif; ?>
                </div>
            </div>
            
            <!-- 학습 진단 섹션 (오른쪽 30%) -->
            <div class="rating-section">
             
                <div class="steps-title">
                    <span class="step-item">해석</span>
                    <span class="step-item">발상</span>
                    <span class="step-item">풀이</span>
                </div>
                <div class="rating-items">
                    <!-- 해석 평가 -->
                    <div class="rating-item">
                        <div class="star-container" id="interprete-stars">
                            <?php 
                                $interprete_stars = ceil($initial_ratings['interprete'] / 2);
                                for ($i = 1; $i <= 5; $i++) {
                                    $active_class = $i <= $interprete_stars ? 'active' : '';
                                    $tooltip_text = $stage_labels['interprete'][$i-1];
                                    echo "<span class='star $active_class' data-category='interprete' data-value='$i'>★<span class='star-tooltip'>$tooltip_text</span></span>";
                                }
                            ?>
                        </div>
                        <div class="rating-description"><?php echo $stage_labels['interprete'][$interprete_index]; ?></div>
                    </div>
                    
                    <!-- 발상 평가 -->
                    <div class="rating-item">
                         <div class="star-container" id="ideate-stars">
                            <?php 
                                $ideate_stars = ceil($initial_ratings['ideate'] / 2);
                                for ($i = 1; $i <= 5; $i++) {
                                    $active_class = $i <= $ideate_stars ? 'active' : '';
                                    $tooltip_text = $stage_labels['ideate'][$i-1];
                                    echo "<span class='star $active_class' data-category='ideate' data-value='$i'>★<span class='star-tooltip'>$tooltip_text</span></span>";
                                }
                            ?>
                        </div>
                        <div class="rating-description"><?php echo $stage_labels['ideate'][$ideate_index]; ?></div>
                    </div>
                    
                    <!-- 풀이 평가 -->
                    <div class="rating-item">
                         <div class="star-container" id="solve-stars">
                            <?php 
                                $solve_stars = ceil($initial_ratings['solve'] / 2);
                                for ($i = 1; $i <= 5; $i++) {
                                    $active_class = $i <= $solve_stars ? 'active' : '';
                                    $tooltip_text = $stage_labels['solve'][$i-1];
                                    echo "<span class='star $active_class' data-category='solve' data-value='$i'>★<span class='star-tooltip'>$tooltip_text</span></span>";
                                }
                            ?>
                        </div>
                        <div class="rating-description"><?php echo $stage_labels['solve'][$solve_index]; ?></div>
                    </div>
            </div>
            <button id="complete-button" class="complete-button">완료하기</button>

                <!-- 진행 막대 (오른쪽 영역 하단) -->
                <div class="progress-container">
                   
                    <div class="castle-container">
                        <div class="svg-container" id="castle-svg-container">
                            <!-- SVG 성 그림이 여기에 들어갑니다 -->
                        </div>
                        
                        <div class="progress-labels">
                            <span>0%</span>
                            <span>100%</span>
                        </div>
                        
                        <div class="progress-bar-container" id="castle-progress-bar">
                            <div class="progress-bar" id="castle-progress-fill" style="width: <?php echo $total_progress; ?>%;"></div>
                            <div class="progress-handle" id="castle-progress-handle" style="left: <?php echo $total_progress; ?>%;"></div>
                        </div>
                        
                    
                    </div>
                </div>
            </div>
          </div>
    </div>

    <script>
        // 실시간 시계 함수
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;
            document.getElementById('real-time-clock').textContent = timeString;
        }
        
        // 페이지 로드 시 시계 초기화 및 1초마다 업데이트
        updateClock();
        setInterval(updateClock, 1000);
        
        // 화이트보드 전체화면
        function openFullscreen() {
            var url = "https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id=<?php echo $wboard_id; ?>&studentid=<?php echo $studentid; ?>";
            window.open(url, '_blank');
        }
        
        function openFocusSession() {
            var url = "https://mathking.kr/moodle/local/augmented_teacher/teachers/triadresonance.php?id=2";
            window.open(url, '_blank');
        }
        
        // 평가 업데이트
        $(document).ready(function() {
            // 현재 평가 값
            var ratings = {
                interprete: <?php echo $initial_ratings['interprete']; ?>,
                ideate: <?php echo $initial_ratings['ideate']; ?>,
                solve: <?php echo $initial_ratings['solve']; ?>
            };
            
            // 스테이지 라벨
            var stageLabels = {
                interprete: <?php echo json_encode($stage_labels['interprete']); ?>,
                ideate: <?php echo json_encode($stage_labels['ideate']); ?>,
                solve: <?php echo json_encode($stage_labels['solve']); ?>
            };
            
            // 스테이지 값
            var stages = [0, 2, 5, 8, 10];
            
            // 서버에 별점 저장하는 함수
            function saveRatings() {
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        action: 'update_ratings',
                        wboardid: '<?php echo $wboard_id; ?>',
                        interprete: ratings.interprete,
                        ideate: ratings.ideate,
                        solve: ratings.solve
                    },
                    success: function(response) {
                        if (!response.success) {
                            console.error('별점 저장 실패:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX 오류:', error);
                    }
                });
            }
            
            // 별점 클릭 이벤트
            $('.star').on('click', function() {
                var category = $(this).data('category');
                var value = parseInt($(this).data('value'));
                
                // 별점 UI 업데이트
                $('#' + category + '-stars .star').removeClass('active');
                $('#' + category + '-stars .star').each(function(index) {
                    if (index < value) {
                        $(this).addClass('active');
                    }
                });
                
                // 값 업데이트 (별 하나당 2점)
                var newValue = value * 2;
                ratings[category] = newValue;
                
                // 설명 업데이트
                var stageIndex = getNearestStageIndex(newValue, stages);
                $(this).parent().siblings('.rating-description').text(stageLabels[category][stageIndex]);
                
                // 전체 진행도 업데이트
                updateTotalProgress();
                
                // 단계별 배경색 업데이트
                updateStarRatings();
                
                // 서버에 데이터 저장
                saveRatings();
            });
            
            // 가장 가까운 단계 인덱스 찾기
            function getNearestStageIndex(value, stages) {
                var closestIndex = 0;
                var minDistance = 100;
                
                for (var i = 0; i < stages.length; i++) {
                    var distance = Math.abs(stages[i] - value);
                    if (distance < minDistance) {
                        minDistance = distance;
                        closestIndex = i;
                    }
                }
                
                return closestIndex;
            }
            
            // 총 진행도 업데이트
            function updateTotalProgress() {
                var total = (ratings.interprete + ratings.ideate + ratings.solve) / 30 * 100;
                total = Math.round(total);
                
                // 성 업데이트
                castleProgress = total / 100;
                updateCastleSvg(castleProgress);
                
                // 진행도 텍스트 업데이트
                $('.progress-value').text('진행도: ' + total + '%');
            }
            
            // 중세 성 시각화 초기화 및 이벤트 처리
            var castleProgress = <?php echo $total_progress / 100; ?>;
            var castleIsDragging = false;
            var castleProgressBarEl = document.getElementById('castle-progress-bar');
            var castleProgressFillEl = document.getElementById('castle-progress-fill');
            var castleProgressHandleEl = document.getElementById('castle-progress-handle');
            var castleSvgContainer = document.getElementById('castle-svg-container');
            
            // SVG 초기화 함수
            function initCastleSvg() {
                var svgContent = `
                <svg viewBox="0 0 1000 600" width="100%" height="100%">
                    <!-- 정의 부분 -->
                    <defs>
                        <!-- 하늘 그라데이션 -->
                        <linearGradient id="skyGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#6BB4EF" />
                            <stop offset="100%" stop-color="#B3D8F8" />
                        </linearGradient>
                        
                        <!-- 잔디 그라데이션 -->
                        <linearGradient id="grassGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#7CAA2D" />
                            <stop offset="100%" stop-color="#5C8022" />
                        </linearGradient>
                        
                        <!-- 물 그라데이션 -->
                        <linearGradient id="waterGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#4A90E2" />
                            <stop offset="100%" stop-color="#3D7BBF" />
                        </linearGradient>
                        
                        <!-- 돌 질감 패턴 -->
                        <pattern id="stonePattern" patternUnits="userSpaceOnUse" width="60" height="60">
                            <rect width="60" height="60" fill="#8E8E8E" />
                            <rect x="0" y="0" width="30" height="30" fill="#7A7A7A" />
                            <rect x="30" y="30" width="30" height="30" fill="#7A7A7A" />
                            <rect x="15" y="15" width="30" height="30" fill="#9A9A9A" />
                            <rect x="45" y="45" width="15" height="15" fill="#6A6A6A" />
                        </pattern>
                        
                        <!-- 벽돌 패턴 -->
                        <pattern id="brickPattern" patternUnits="userSpaceOnUse" width="80" height="40">
                            <rect width="80" height="40" fill="#B25D45" />
                            <rect x="0" y="0" width="35" height="15" fill="#A04A35" />
                            <rect x="40" y="0" width="35" height="15" fill="#993D2D" />
                            <rect x="20" y="20" width="35" height="15" fill="#A04A35" />
                            <rect x="60" y="20" width="20" height="15" fill="#993D2D" />
                        </pattern>
                        
                        <!-- 지붕 타일 패턴 -->
                        <pattern id="roofPattern" patternUnits="userSpaceOnUse" width="60" height="30">
                            <rect width="60" height="30" fill="#383838" />
                            <path d="M0,15 L15,0 L30,15 L45,0 L60,15 L60,30 L0,30 Z" fill="#262626" />
                            <path d="M0,15 L15,30 L30,15 L45,30 L60,15" stroke="#1A1A1A" stroke-width="2" fill="none" />
                        </pattern>
                        
                        <!-- 나무 잎 패턴 -->
                        <pattern id="foliagePattern" patternUnits="userSpaceOnUse" width="100" height="100">
                            <rect width="100" height="100" fill="#2E7D32" />
                            <ellipse cx="20" cy="20" rx="20" ry="20" fill="#1B5E20" />
                            <ellipse cx="70" cy="30" rx="25" ry="25" fill="#388E3C" />
                            <ellipse cx="50" cy="70" rx="30" ry="30" fill="#1B5E20" />
                            <ellipse cx="90" cy="80" rx="15" ry="15" fill="#388E3C" />
                        </pattern>
                    </defs>
                    
                    <!-- 하늘 -->
                    <rect x="0" y="0" width="1000" height="400" fill="url(#skyGradient)" />
                    
                    <!-- 각 요소 그룹 - 진행도에 따라 투명도가 변함 -->
                    <!-- 태양 -->
                    <g id="castle-part-1" opacity="0">
                        <circle cx="800" cy="100" r="60" fill="#FFD700" />
                        <circle cx="800" cy="100" r="70" fill="#FFD700" fill-opacity="0.3" />
                        <circle cx="800" cy="100" r="80" fill="#FFD700" fill-opacity="0.1" />
                    </g>
                    
                    <!-- 원거리 산맥 -->
                    <g id="castle-part-2" opacity="0">
                        <path d="M0,400 L200,250 L350,350 L500,200 L650,300 L800,220 L1000,350 L1000,400 Z" fill="#8E8E8E" fill-opacity="0.6" />
                        <path d="M0,400 L150,320 L300,380 L450,300 L600,350 L750,280 L900,380 L1000,350 L1000,400 Z" fill="#6A6A6A" fill-opacity="0.4" />
                    </g>
                    
                    <!-- 바닥과 들판 -->
                    <g id="castle-part-3" opacity="0">
                        <rect x="0" y="400" width="1000" height="200" fill="url(#grassGradient)" />
                        
                        <!-- 농작물 들판 -->
                        <rect x="50" y="450" width="200" height="100" fill="#AC9B69" />
                        <g>
                            ${Array(10).fill(0).map((_, row) => (
                                Array(20).fill(0).map((_, col) => (
                                    `<line x1="${col * 10 + 50}" y1="450" x2="${col * 10 + 50}" y2="550" stroke="#8D7E55" stroke-width="1" />`
                                )).join('')
                            )).join('')}
                            ${Array(10).fill(0).map((_, row) => (
                                `<line x1="50" y1="${row * 10 + 450}" x2="250" y2="${row * 10 + 450}" stroke="#8D7E55" stroke-width="1" />`
                            )).join('')}
                        </g>
                        
                        <!-- 시냇물/강 -->
                        <path d="M800,400 C750,450 800,500 750,550 C700,600 650,550 600,400" fill="url(#waterGradient)" />
                    </g>
                    
                    <!-- 나무들 -->
                    <g id="castle-part-4" opacity="0">
                        <!-- 왼쪽 나무 -->
                        <rect x="120" y="350" width="20" height="80" fill="#5D4037" />
                        <ellipse cx="130" cy="320" rx="50" ry="60" fill="url(#foliagePattern)" />
                        
                        <!-- 오른쪽 나무들 -->
                        <rect x="850" y="350" width="25" height="100" fill="#5D4037" />
                        <ellipse cx="860" cy="310" rx="60" ry="70" fill="url(#foliagePattern)" />
                        
                        <rect x="920" y="380" width="15" height="60" fill="#5D4037" />
                        <ellipse cx="925" cy="350" rx="40" ry="50" fill="url(#foliagePattern)" />
                    </g>
                    
                    <!-- 성 기초 및 플랫폼 -->
                    <g id="castle-part-5" opacity="0">
                        <path d="M350,440 L650,440 L650,400 L350,400 Z" fill="#8E8E8E" stroke="#6A6A6A" stroke-width="2" />
                        <rect x="350" y="440" width="300" height="20" fill="#6A6A6A" />
                        
                        <!-- 기초 질감 세부 사항 -->
                        <line x1="350" y1="410" x2="650" y2="410" stroke="#6A6A6A" stroke-width="1" />
                        <line x1="350" y1="420" x2="650" y2="420" stroke="#6A6A6A" stroke-width="1" />
                        <line x1="350" y1="430" x2="650" y2="430" stroke="#6A6A6A" stroke-width="1" />
                        
                        <!-- 세로선 -->
                        ${Array(15).fill(0).map((_, i) => (
                            `<line x1="${370 + i * 20}" y1="400" x2="${370 + i * 20}" y2="440" stroke="#6A6A6A" stroke-width="1" />`
                        )).join('')}
                    </g>
                    
                    <!-- 외벽 -->
                    <g id="castle-part-6" opacity="0">
                        <!-- 주요 외벽 -->
                        <rect x="370" y="350" width="260" height="50" fill="url(#stonePattern)" />
                        
                        <!-- 벽 세부 사항 -->
                        <rect x="370" y="350" width="260" height="5" fill="#6A6A6A" />
                        <rect x="370" y="395" width="260" height="5" fill="#6A6A6A" />
                        
                        <!-- 정면 벽 입구 틈 -->
                        <rect x="470" y="370" width="60" height="30" fill="#000" fill-opacity="0.7" />
                    </g>
                    
                    <!-- 모서리 탑 -->
                    <g id="castle-part-7" opacity="0">
                        <!-- 왼쪽 전면 탑 -->
                        <rect x="350" y="300" width="40" height="100" fill="url(#stonePattern)" />
                        <path d="M350,300 L370,280 L390,300" fill="#6A6A6A" stroke="#4A4A4A" stroke-width="1" />
                        
                        <!-- 오른쪽 전면 탑 -->
                        <rect x="610" y="300" width="40" height="100" fill="url(#stonePattern)" />
                        <path d="M610,300 L630,280 L650,300" fill="#6A6A6A" stroke="#4A4A4A" stroke-width="1" />
                        
                        <!-- 탑 창문 -->
                        <rect x="365" y="330" width="10" height="15" fill="#000" fill-opacity="0.7" />
                        <rect x="625" y="330" width="10" height="15" fill="#000" fill-opacity="0.7" />
                    </g>
                    
                    <!-- 주 성 구조물 -->
                    <g id="castle-part-8" opacity="0">
                        <!-- 주 성 건물 -->
                        <rect x="400" y="320" width="200" height="80" fill="url(#brickPattern)" />
                        
                        <!-- 성 창문 -->
                        <rect x="420" y="340" width="15" height="20" fill="#000" fill-opacity="0.7" />
                        <rect x="465" y="340" width="15" height="20" fill="#000" fill-opacity="0.7" />
                        <rect x="520" y="340" width="15" height="20" fill="#000" fill-opacity="0.7" />
                        <rect x="565" y="340" width="15" height="20" fill="#000" fill-opacity="0.7" />
                        
                        <!-- 주문 프레임 -->
                        <rect x="480" y="360" width="40" height="40" fill="#5D4037" />
                        <rect x="485" y="365" width="30" height="35" fill="#8B5A2B" />
                        
                        <!-- 문 세부 사항 -->
                        <line x1="500" y1="365" x2="500" y2="400" stroke="#5D4037" stroke-width="2" />
                        <circle cx="495" cy="385" r="2" fill="#D4AF37" />
                    </g>
                    
                    <!-- 중앙 탑 -->
                    <g id="castle-part-9" opacity="0">
                        <rect x="450" y="250" width="100" height="70" fill="url(#brickPattern)" />
                        
                        <!-- 탑 창문 -->
                        <rect x="485" y="270" width="30" height="20" fill="#000" fill-opacity="0.7" />
                        <path d="M485,270 A15,10 0 0 1 515,270" fill="none" stroke="#5D4037" stroke-width="2" />
                        
                        <!-- 탑 상단 -->
                        <rect x="440" y="240" width="120" height="10" fill="#6A6A6A" />
                    </g>
                    
                    <!-- 지붕 -->
                    <g id="castle-part-10" opacity="0">
                        <!-- 주 건물 지붕 -->
                        <path d="M390,320 L500,270 L610,320" fill="url(#roofPattern)" />
                        
                        <!-- 중앙 탑 지붕 -->
                        <path d="M440,240 L500,200 L560,240" fill="url(#roofPattern)" />
                        
                        <!-- 탑 지붕 -->
                        <path d="M350,300 L370,260 L390,300" fill="url(#roofPattern)" />
                        <path d="M610,300 L630,260 L650,300" fill="url(#roofPattern)" />
                    </g>
                    
                    <!-- 지붕 세부 사항 -->
                    <g id="castle-part-11" opacity="0">
                        <circle cx="500" cy="200" r="10" fill="#D4AF37" /> <!-- 중앙 탑 금빛 상단 -->
                        <circle cx="370" cy="260" r="5" fill="#D4AF37" /> <!-- 왼쪽 탑 금빛 상단 -->
                        <circle cx="630" cy="260" r="5" fill="#D4AF37" /> <!-- 오른쪽 탑 금빛 상단 -->
                        
                        <!-- 깃발 -->
                        <line x1="500" y1="190" x2="500" y2="160" stroke="#5D4037" stroke-width="2" />
                        <path d="M500,160 L525,170 L500,180" fill="#C62828" />
                    </g>
                    
                    <!-- 성 세부 사항 -->
                    <g id="castle-part-12" opacity="0">
                        <!-- 벽 성가퀴 -->
                        ${Array(13).fill(0).map((_, i) => (
                            `<rect x="${370 + i * 20}" y="340" width="10" height="10" fill="#6A6A6A" />`
                        )).join('')}
                        
                        <!-- 탑 성가퀴 -->
                        ${Array(4).fill(0).map((_, i) => (
                            `<rect x="${350 + i * 10}" y="290" width="5" height="10" fill="#6A6A6A" />`
                        )).join('')}
                        ${Array(4).fill(0).map((_, i) => (
                            `<rect x="${610 + i * 10}" y="290" width="5" height="10" fill="#6A6A6A" />`
                        )).join('')}
                        
                        <!-- 중앙 탑 성가퀴 -->
                        ${Array(10).fill(0).map((_, i) => (
                            `<rect x="${440 + i * 12}" y="230" width="6" height="10" fill="#6A6A6A" />`
                        )).join('')}
                    </g>
                    
                    <!-- 다리와 길 -->
                    <g id="castle-part-13" opacity="0">
                        <!-- 다리 -->
                        <rect x="470" y="400" width="60" height="10" fill="#8B5A2B" />
                        <rect x="470" y="410" width="60" height="3" fill="#5D4037" />
                        
                        <!-- 길 -->
                        <path d="M470,440 C420,480 390,460 350,500" fill="none" stroke="#A1887F" stroke-width="10" stroke-linecap="round" />
                    </g>
                    
                    <!-- 세부 사항 및 분위기 -->
                    <g id="castle-part-14" opacity="0">
                        <!-- 창문 불빛 -->
                        <rect x="422" y="342" width="11" height="16" fill="#FFD54F" fill-opacity="0.7" />
                        <rect x="467" y="342" width="11" height="16" fill="#FFD54F" fill-opacity="0.7" />
                        <rect x="522" y="342" width="11" height="16" fill="#FFD54F" fill-opacity="0.7" />
                        <rect x="567" y="342" width="11" height="16" fill="#FFD54F" fill-opacity="0.7" />
                        <rect x="487" y="272" width="26" height="16" fill="#FFD54F" fill-opacity="0.7" />
                        
                        <!-- 탑 창문 불빛 -->
                        <rect x="367" y="332" width="6" height="11" fill="#FFD54F" fill-opacity="0.7" />
                        <rect x="627" y="332" width="6" height="11" fill="#FFD54F" fill-opacity="0.7" />
                        
                        <!-- 굴뚝에서 나오는 연기 -->
                        <ellipse cx="530" cy="260" rx="3" ry="5" fill="#9E9E9E" fill-opacity="0.7" />
                        <ellipse cx="530" cy="250" rx="5" ry="7" fill="#9E9E9E" fill-opacity="0.5" />
                        <ellipse cx="528" cy="235" rx="8" ry="10" fill="#9E9E9E" fill-opacity="0.3" />
                        
                        <!-- 새들 -->
                        <path d="M700,150 C705,145 710,150 715,145" fill="none" stroke="#000" stroke-width="1" />
                        <path d="M720,170 C725,165 730,170 735,165" fill="none" stroke="#000" stroke-width="1" />
                        <path d="M680,190 C685,185 690,190 695,185" fill="none" stroke="#000" stroke-width="1" />
                    </g>
                </svg>
                `;
                
                castleSvgContainer.innerHTML = svgContent;
                updateCastleSvg(castleProgress);
            }
            
            // 프로그레스 바 값을 기반으로 SVG 업데이트
            function updateCastleSvg(progress) {
                // 각 요소의 투명도 계산
                function getOpacity(startPercent, endPercent) {
                    var start = startPercent / 100;
                    var end = endPercent / 100;
                    if (progress < start) return 0;
                    if (progress > end) return 1;
                    return (progress - start) / (end - start);
                }
                
                // 각 부분 업데이트
                document.getElementById('castle-part-1').style.opacity = getOpacity(0, 5);
                document.getElementById('castle-part-2').style.opacity = getOpacity(5, 10);
                document.getElementById('castle-part-3').style.opacity = getOpacity(10, 15);
                document.getElementById('castle-part-4').style.opacity = getOpacity(15, 20);
                document.getElementById('castle-part-5').style.opacity = getOpacity(20, 25);
                document.getElementById('castle-part-6').style.opacity = getOpacity(25, 35);
                document.getElementById('castle-part-7').style.opacity = getOpacity(35, 45);
                document.getElementById('castle-part-8').style.opacity = getOpacity(45, 60);
                document.getElementById('castle-part-9').style.opacity = getOpacity(60, 70);
                document.getElementById('castle-part-10').style.opacity = getOpacity(70, 80);
                document.getElementById('castle-part-11').style.opacity = getOpacity(80, 85);
                document.getElementById('castle-part-12').style.opacity = getOpacity(85, 90);
                document.getElementById('castle-part-13').style.opacity = getOpacity(90, 95);
                document.getElementById('castle-part-14').style.opacity = getOpacity(95, 100);
                
                // UI 업데이트
                castleProgressFillEl.style.width = (progress * 100) + '%';
                castleProgressHandleEl.style.left = (progress * 100) + '%';
            }
            
            // 별점 UI 업데이트
            function updateStarRatings() {
                ['interprete', 'ideate', 'solve'].forEach(function(category) {
                    var stars = Math.ceil(ratings[category] / 2);
                    $('#' + category + '-stars .star').removeClass('active');
                    $('#' + category + '-stars .star').each(function(index) {
                        if (index < stars) {
                            $(this).addClass('active');
                        }
                    });
                    
                    // 설명 업데이트
                    var stageIndex = getNearestStageIndex(ratings[category], stages);
                    $('#' + category + '-stars').siblings('.rating-description').text(stageLabels[category][stageIndex]);
                });

                // 단계별 배경색 업데이트
                $('.step-item').removeClass('active');
                
                // 해석 단계 (첫 번째 단계)
                if (ratings.interprete >= 10) {  // 모든 별이 채워졌을 때
                    $('.step-item:first').addClass('active');
                    
                    // 발상 단계 (두 번째 단계)
                    if (ratings.ideate >= 10) {  // 모든 별이 채워졌을 때
                        $('.step-item:nth-child(2)').addClass('active');
                        
                        // 풀이 단계 (세 번째 단계)
                        if (ratings.solve >= 10) {  // 모든 별이 채워졌을 때
                            $('.step-item:last').addClass('active');
                        }
                    }
                }
            }
            
            // 마우스/터치 위치에 따라 진행도 업데이트
            function updateProgressFromEvent(clientX) {
                if (!castleProgressBarEl) return;
                
                var rect = castleProgressBarEl.getBoundingClientRect();
                var barWidth = rect.width;
                var x = clientX - rect.left;
                var newProgress = Math.max(0, Math.min(1, x / barWidth));
                
                castleProgress = newProgress;
                updateCastleSvg(newProgress);
                
                // 서버에 업데이트된 값 전송
                var newRatingValue = Math.round(newProgress * 30);
                var newUnderstanding = Math.min(10, newRatingValue);
                var newIdeation = Math.min(10, Math.max(0, newRatingValue - 10));
                var newMethodology = Math.min(10, Math.max(0, newRatingValue - 20));
                
                ratings.interprete = newUnderstanding;
                ratings.ideate = newIdeation;
                ratings.solve = newMethodology;
                
                // 별점 UI 업데이트
                updateStarRatings();
                
                // 서버에 저장
                saveRatings();
            }

            // 마우스 이벤트 핸들러
            function handleMouseDown(e) {
                castleIsDragging = true;
                updateProgressFromEvent(e.clientX);
            }

            function handleMouseMove(e) {
                if (castleIsDragging) {
                    updateProgressFromEvent(e.clientX);
                }
            }

            function handleMouseUp() {
                castleIsDragging = false;
            }

            // 터치 이벤트 핸들러
            function handleTouchStart(e) {
                castleIsDragging = true;
                updateProgressFromEvent(e.touches[0].clientX);
            }

            function handleTouchMove(e) {
                if (castleIsDragging && e.touches.length > 0) {
                    updateProgressFromEvent(e.touches[0].clientX);
                    e.preventDefault();
                }
            }

            function handleTouchEnd() {
                castleIsDragging = false;
            }

            // 초기화 및 이벤트 설정
            initCastleSvg();

            if (castleProgressBarEl) {
                castleProgressBarEl.addEventListener('mousedown', handleMouseDown);
                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
                
                castleProgressBarEl.addEventListener('touchstart', handleTouchStart);
                document.addEventListener('touchmove', handleTouchMove, { passive: false });
                document.addEventListener('touchend', handleTouchEnd);
            }

            // 초기 상태에서도 배경색 업데이트 실행
            updateStarRatings();

            // 완료 버튼 클릭 이벤트
            $('#complete-button').on('click', function() {
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        action: 'complete_status',
                        wboardid: '<?php echo $wboard_id; ?>',
                        status: 'complete'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: '지면평가 안내',
                                text: '이제 지면평가를 진행해 주시기 바랍니다',
                                icon: 'info',
                                timer: 1000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: '오류',
                                text: '상태 업데이트 실패: ' + response.message,
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX 오류:', error);
                        Swal.fire({
                            title: '오류',
                            text: '상태 업데이트 중 오류가 발생했습니다.',
                            icon: 'error'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>