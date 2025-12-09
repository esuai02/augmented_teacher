<?php
/**
 * 최근 12시간 이내 포모도르 기록 정보 추출
 * timescaffolding.php에서 사용하는 방식 정리
 * 
 * 사용 방법:
 * include_once("/home/moodle/public_html/moodle/config.php");
 * global $DB, $USER;
 * require_login();
 * 
 * $studentid = $USER->id; // 또는 특정 학생 ID
 * $pomodoroData = getPomodoroRecords12h($DB, $studentid);
 */

/**
 * 최근 12시간 이내 포모도르 기록 추출 (mdl_abessi_tracking 테이블 기준)
 * timescaffolding.php의 방식 사용
 * 
 * @param object $DB Moodle DB 객체
 * @param int $studentid 학생 ID
 * @return array 포모도르 기록 배열
 */
function getPomodoroRecords12h($DB, $studentid) {
    try {
        $timecreated = time();
        $halfdayago = $timecreated - 43200; // 12시간 전 (43200초)
        
        // 최근 12시간 이내 완료된 포모도르 기록 조회
        $records = $DB->get_records_sql("
            SELECT 
                id,
                userid,
                text,
                status,
                timecreated,
                duration,
                timefinished,
                result,
                ndisengagement,
                nwboard,
                wbtimeave,
                comment,
                feedback,
                hide
            FROM mdl_abessi_tracking 
            WHERE userid = ? 
            AND timecreated >= ? 
            AND hide = 0
            AND status = 'complete'
            ORDER BY timecreated DESC
        ", [$studentid, $halfdayago]);
        
        $pomodoroData = array(
            'total_count' => 0,
            'total_result' => 0,
            'average_result' => 0,
            'records' => array()
        );
        
        if (!empty($records)) {
            $totalResult = 0;
            $validCount = 0;
            
            foreach ($records as $record) {
                // 실제 소요 시간 계산 (분 단위)
                $actualMinutes = 0;
                if ($record->timefinished > $record->timecreated) {
                    $actualMinutes = round(($record->timefinished - $record->timecreated) / 60, 0);
                    if ($actualMinutes < 0) $actualMinutes = 0;
                    if ($actualMinutes > 60) $actualMinutes = 60; // 최대 60분으로 제한
                }
                
                // 예상 시간 계산 (분 단위)
                $expectedMinutes = round(($record->duration - $record->timecreated) / 60, 0);
                
                $pomodoroData['records'][] = array(
                    'id' => $record->id,
                    'text' => $record->text,
                    'timecreated' => $record->timecreated,
                    'timecreated_formatted' => date("Y-m-d H:i:s", $record->timecreated),
                    'timefinished' => $record->timefinished,
                    'duration' => $record->duration,
                    'actual_minutes' => $actualMinutes,
                    'expected_minutes' => $expectedMinutes,
                    'result' => $record->result, // 3: 매우만족, 2: 만족, 1: 불만족
                    'result_text' => getResultText($record->result),
                    'ndisengagement' => $record->ndisengagement,
                    'nwboard' => $record->nwboard,
                    'wbtimeave' => round($record->wbtimeave, 0),
                    'comment' => $record->comment,
                    'feedback' => $record->feedback
                );
                
                // 평균 계산을 위한 합계
                if ($record->result !== null && $record->result > 0) {
                    $totalResult += $record->result;
                    $validCount++;
                }
            }
            
            $pomodoroData['total_count'] = count($records);
            $pomodoroData['total_result'] = $totalResult;
            $pomodoroData['average_result'] = $validCount > 0 ? round($totalResult / $validCount, 2) : 0;
        }
        
        return $pomodoroData;
        
    } catch (Exception $e) {
        error_log('Error in getPomodoroRecords12h (file: ' . __FILE__ . ', line: ' . __LINE__ . '): ' . $e->getMessage());
        return array(
            'total_count' => 0,
            'total_result' => 0,
            'average_result' => 0,
            'records' => array(),
            'error' => $e->getMessage()
        );
    }
}

/**
 * 포모도르 만족도 텍스트 변환
 * 
 * @param int $result 만족도 값 (3: 매우만족, 2: 만족, 1: 불만족)
 * @return string 만족도 텍스트
 */
function getResultText($result) {
    switch ($result) {
        case 3:
            return '매우 만족';
        case 2:
            return '만족';
        case 1:
            return '불만족';
        default:
            return '미평가';
    }
}

/**
 * 최근 12시간 이내 포모도르 일기 데이터 추출 (mdl_abessi_todayplans 테이블 기준)
 * beforegoinghome/index.php의 방식 사용
 * 
 * @param object $DB Moodle DB 객체
 * @param int $studentid 학생 ID
 * @return array 포모도르 일기 데이터
 */
function getPomodoroDiary12h($DB, $studentid) {
    try {
        $pomodoro12hStart = time() - 43200; // 12시간 전
        
        $pomodoroDiaryData = $DB->get_record_sql("
            SELECT 
                status01, status02, status03, status04, status05, status06, status07, status08,
                status09, status10, status11, status12, status13, status14, status15, status16,
                timecreated,
                id
            FROM mdl_abessi_todayplans 
            WHERE userid = ? AND timecreated >= ?
            ORDER BY id DESC 
            LIMIT 1
        ", [$studentid, $pomodoro12hStart]);
        
        $result = array(
            'found' => false,
            'timecreated' => null,
            'timecreated_formatted' => null,
            'total_count' => 0,
            'satisfaction_count' => 0,
            'satisfaction_sum' => 0,
            'satisfaction_avg' => 0,
            'items' => array()
        );
        
        if ($pomodoroDiaryData) {
            $diary = $pomodoroDiaryData;
            $satisfactionMap = array(
                '매우만족' => 3,
                '만족' => 2,
                '불만족' => 1
            );
            
            $satisfactionSum = 0;
            $satisfactionCount = 0;
            $totalCount = 0;
            $items = array();
            
            for ($i = 1; $i <= 16; $i++) {
                $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
                if (!empty($diary->$statusField)) {
                    $satisfaction = isset($satisfactionMap[$diary->$statusField]) 
                        ? $satisfactionMap[$diary->$statusField] 
                        : null;
                    
                    $items[] = array(
                        'slot' => $i,
                        'status' => $diary->$statusField,
                        'satisfaction' => $satisfaction
                    );
                    
                    if ($satisfaction !== null) {
                        $satisfactionSum += $satisfaction;
                        $satisfactionCount++;
                    }
                    $totalCount++;
                }
            }
            
            $result['found'] = true;
            $result['timecreated'] = $diary->timecreated;
            $result['timecreated_formatted'] = date('Y-m-d H:i:s', $diary->timecreated);
            $result['total_count'] = $totalCount;
            $result['satisfaction_count'] = $satisfactionCount;
            $result['satisfaction_sum'] = $satisfactionSum;
            $result['satisfaction_avg'] = $satisfactionCount > 0 
                ? round($satisfactionSum / $satisfactionCount, 2) 
                : 0;
            $result['items'] = $items;
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log('Error in getPomodoroDiary12h (file: ' . __FILE__ . ', line: ' . __LINE__ . '): ' . $e->getMessage());
        return array(
            'found' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * 사용 예제
 */
/*
// 1. Moodle 환경 설정
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;

// 2. 최근 12시간 이내 포모도르 기록 추출 (mdl_abessi_tracking)
$trackingData = getPomodoroRecords12h($DB, $studentid);

echo "총 포모도르 기록: " . $trackingData['total_count'] . "개\n";
echo "평균 만족도: " . $trackingData['average_result'] . "\n";
echo "\n기록 목록:\n";
foreach ($trackingData['records'] as $record) {
    echo "- " . $record['timecreated_formatted'] . ": " . $record['text'] . 
         " (" . $record['actual_minutes'] . "분, " . $record['result_text'] . ")\n";
}

// 3. 최근 12시간 이내 포모도르 일기 데이터 추출 (mdl_abessi_todayplans)
$diaryData = getPomodoroDiary12h($DB, $studentid);

if ($diaryData['found']) {
    echo "\n포모도르 일기 데이터:\n";
    echo "작성 시간: " . $diaryData['timecreated_formatted'] . "\n";
    echo "총 슬롯 수: " . $diaryData['total_count'] . "\n";
    echo "평균 만족도: " . $diaryData['satisfaction_avg'] . "\n";
    echo "\n슬롯별 상세:\n";
    foreach ($diaryData['items'] as $item) {
        echo "- 슬롯 " . $item['slot'] . ": " . $item['status'];
        if ($item['satisfaction'] !== null) {
            echo " (만족도: " . $item['satisfaction'] . ")";
        }
        echo "\n";
    }
} else {
    echo "\n최근 12시간 이내 포모도르 일기 데이터가 없습니다.\n";
}
*/
?>

