<?php
// Moodle 및 OpenAI API 설정
include_once("/home/moodle/public_html/moodle/config.php");
include_once("../../config.php"); // OpenAI API 설정 포함
global $DB, $USER;
require_login();
 

// UTF-8mb4 연결 설정 (이모지 지원)
// 근본 원인: 테이블 컬럼은 utf8mb4지만 Moodle 연결은 기본적으로 utf8
// 해결책: 각 스크립트 시작 시 연결 charset을 utf8mb4로 강제 설정
try {
    $DB->execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    error_log("Failed to set connection charset to utf8mb4 at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    // 연결 설정 실패해도 계속 진행 (기존 동작 유지)
}


// 학생 정보 가져오기
$userid = optional_param('userid', 0, PARAM_INT);
$studentId = $userid ? $userid : $USER->id;

// 리포트 ID 파라미터 가져오기
$reportIdParam = optional_param('reportid', '', PARAM_TEXT);
 

// 학생 정보 조회
if ($userid && $userid != $USER->id) {
    // 다른 학생의 정보를 조회하는 경우 (선생님 권한 체크 필요)
    $student = $DB->get_record('user', array('id' => $studentId));
    $studentName = $student ? $student->firstname . ' ' . $student->lastname : '학생';
} else {
    $studentName = $USER->firstname . ' ' . $USER->lastname;
}
 

// 실제 데이터 가져오기
$aweekago = time() - (7 * 24 * 60 * 60);
$fourWeeksAgo = time() - (28 * 24 * 60 * 60); // 4주 전
$hoursago = time() - (24 * 60 * 60);
$todayStart = strtotime('today'); // 오늘 시작 시간

// 최근 1주일 침착도 데이터 가져오기 (calmness.php 방식)
$calmnessWeekData = [];
try {
    // 먼저 조건 없이 데이터 확인 (디버깅용)
    $debugRecords = $DB->get_records_sql("
        SELECT id, userid, level, timecreated, hide
        FROM mdl_alt42_calmness 
        WHERE userid = ? AND timecreated >= ?
        ORDER BY timecreated ASC", 
        [$studentId, $aweekago]);
    
    error_log('CALMNESS_DEBUG: Total records (no filter): ' . count($debugRecords));
    if (count($debugRecords) > 0) {
        $firstRecord = reset($debugRecords);
        error_log('CALMNESS_DEBUG: First record - level: ' . $firstRecord->level . ', hide: ' . ($firstRecord->hide ?? 'NULL') . ', timecreated: ' . date('Y-m-d H:i:s', $firstRecord->timecreated));
    }

    // 필터링된 데이터 가져오기
    $calmnessWeekRecords = $DB->get_records_sql("
        SELECT id, userid, level, timecreated 
        FROM mdl_alt42_calmness 
        WHERE userid = ? AND timecreated >= ? AND (hide IS NULL OR hide = 0) AND level > 1
        ORDER BY timecreated ASC", 
        [$studentId, $aweekago]);
    
    error_log('CALMNESS_DEBUG: Filtered records (hide=0, level>1): ' . count($calmnessWeekRecords));
    
    // 필터링된 데이터가 없으면 level 조건 완화해서 다시 시도
    if (count($calmnessWeekRecords) === 0) {
        $calmnessWeekRecords = $DB->get_records_sql("
            SELECT id, userid, level, timecreated 
            FROM mdl_alt42_calmness 
            WHERE userid = ? AND timecreated >= ? AND (hide IS NULL OR hide = 0)
            ORDER BY timecreated ASC", 
            [$studentId, $aweekago]);
        error_log('CALMNESS_DEBUG: Records without level filter: ' . count($calmnessWeekRecords));
    }
    
    foreach ($calmnessWeekRecords as $record) {
        $calmnessWeekData[] = [
            'x' => $record->timecreated * 1000,  // ms로 변환
            'y' => (int)$record->level,
            'ts' => $record->timecreated         // 초 단위 (통계용)
        ];
    }
    
    error_log('CALMNESS_DEBUG: Final data count: ' . count($calmnessWeekData));
} catch (Exception $e) {
    error_log('CALMNESS_WEEK_DATA_ERROR: ' . $e->getMessage());
    $calmnessWeekData = [];
}

// 오늘 작성한 노트 수 카운트 (mdl_abessi_messages의 timecreated 기준)
// Moodle의 count_records_sql은 자동으로 prefix를 붙이므로 {table} 형식 사용
$todayNoteCount = $DB->count_records_sql("
    SELECT COUNT(*) 
    FROM {abessi_messages} 
    WHERE userid = ? AND timecreated >= ? AND hide = 0
", [$studentId, $todayStart]);

// 침착도 데이터 - 가장 최근 값 (디버깅을 위해 에러 로그 추가)
$calmnessData = $DB->get_record_sql("
    SELECT level, timecreated
    FROM mdl_alt42_calmness 
    WHERE userid = ? 
    ORDER BY timecreated DESC 
    LIMIT 1", [$studentId]);

$actualCalmness = $calmnessData ? $calmnessData->level : null;
$calmnessGrade = '';
if ($actualCalmness !== null) {
    if ($actualCalmness >= 95) $calmnessGrade = 'A+';
    elseif ($actualCalmness >= 90) $calmnessGrade = 'A';
    elseif ($actualCalmness >= 85) $calmnessGrade = 'B+';
    elseif ($actualCalmness >= 80) $calmnessGrade = 'B';
    elseif ($actualCalmness >= 75) $calmnessGrade = 'C+';
    elseif ($actualCalmness >= 70) $calmnessGrade = 'C';
    else $calmnessGrade = 'F';
}

// 포모도르 일기 데이터 (만족도 포함) - 최근 12시간 이내 데이터 조회
// getPomodoroDiary12h 함수 방식 사용
try {
    $pomodoro12hStart = time() - 43200; // 12시간 전 (43200초)
    $currentTime = time();
    
    error_log('POMODORO_DEBUG: Starting data retrieval - userid=' . $studentId . ', currentTime=' . $currentTime . ' (' . date('Y-m-d H:i:s', $currentTime) . '), 12hStart=' . $pomodoro12hStart . ' (' . date('Y-m-d H:i:s', $pomodoro12hStart) . ')');
    
    // 먼저 최근 데이터 확인 (조건 없이)
    $allRecentData = $DB->get_records_sql("
        SELECT 
            status01, status02, status03, status04, status05, status06, status07, status08,
            status09, status10, status11, status12, status13, status14, status15, status16,
            timecreated,
            id
        FROM mdl_abessi_todayplans 
        WHERE userid = ?
        ORDER BY id DESC 
        LIMIT 5
    ", [$studentId]);
    
    error_log('POMODORO_DEBUG: Found ' . count($allRecentData) . ' recent records (no time filter)');
    foreach ($allRecentData as $idx => $rec) {
        error_log('POMODORO_DEBUG: Record ' . ($idx + 1) . ' - id=' . $rec->id . ', timecreated=' . $rec->timecreated . ' (' . date('Y-m-d H:i:s', $rec->timecreated) . '), diff=' . ($rec->timecreated - $pomodoro12hStart) . ' seconds');
    }
    
    // 최근 12시간 이내 포모도르 일기 데이터 조회
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
    ", [$studentId, $pomodoro12hStart]);
    
    $pomodoroSatisfactionCount = 0;
    $pomodoroSatisfactionSum = 0;
    $pomodoroTotalCount = 0;
    $pomodoroDiaryItems = []; // 포모도르 일기 항목 배열
    
    if ($pomodoroDiaryData) {
        $diary = $pomodoroDiaryData;
        $satisfactionMap = [
            '매우만족' => 3,
            '만족' => 2,
            '불만족' => 1
        ];
        
        // 디버깅 로그
        error_log('POMODORO_DEBUG: Found diary data within 12h - id=' . $diary->id . ', timecreated=' . $diary->timecreated . ' (' . date('Y-m-d H:i:s', $diary->timecreated) . ')');
        
        // 각 status 필드 확인
        for ($i = 1; $i <= 16; $i++) {
            $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $fieldValue = $diary->$statusField;
            
            if (!empty($fieldValue)) {
                $satisfaction = isset($satisfactionMap[$fieldValue]) 
                    ? $satisfactionMap[$fieldValue] 
                    : null;
                
                $pomodoroDiaryItems[] = [
                    'slot' => $i,
                    'status' => $fieldValue,
                    'satisfaction' => $satisfaction
                ];
                
                if ($satisfaction !== null) {
                    $pomodoroSatisfactionSum += $satisfaction;
                    $pomodoroSatisfactionCount++;
                }
                $pomodoroTotalCount++;
                
                error_log('POMODORO_DEBUG: Slot ' . $i . ' - status=' . $fieldValue . ', satisfaction=' . ($satisfaction !== null ? $satisfaction : 'null'));
            }
        }
        
        error_log('POMODORO_DEBUG: Processed data - totalCount=' . $pomodoroTotalCount . ', items=' . count($pomodoroDiaryItems) . ', satisfactionCount=' . $pomodoroSatisfactionCount);
    } else {
        // 12시간 조건 없이 최근 데이터 사용 (fallback)
        $fallbackData = $DB->get_record_sql("
            SELECT 
                status01, status02, status03, status04, status05, status06, status07, status08,
                status09, status10, status11, status12, status13, status14, status15, status16,
                timecreated, id
            FROM mdl_abessi_todayplans 
            WHERE userid = ?
            ORDER BY id DESC 
            LIMIT 1
        ", [$studentId]);
        
        if ($fallbackData) {
            $timeDiff = $currentTime - $fallbackData->timecreated;
            error_log('POMODORO_DEBUG: Using fallback data (outside 12h) - id=' . $fallbackData->id . ', timecreated=' . $fallbackData->timecreated . ' (' . date('Y-m-d H:i:s', $fallbackData->timecreated) . '), diff=' . $timeDiff . ' seconds (' . round($timeDiff / 3600, 2) . ' hours)');
            
            // 24시간 이내면 사용 (더 관대한 조건)
            if ($timeDiff <= 86400) {
                $diary = $fallbackData;
                $satisfactionMap = [
                    '매우만족' => 3,
                    '만족' => 2,
                    '불만족' => 1
                ];
                
                for ($i = 1; $i <= 16; $i++) {
                    $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
                    $fieldValue = $diary->$statusField;
                    
                    if (!empty($fieldValue)) {
                        $satisfaction = isset($satisfactionMap[$fieldValue]) 
                            ? $satisfactionMap[$fieldValue] 
                            : null;
                        
                        $pomodoroDiaryItems[] = [
                            'slot' => $i,
                            'status' => $fieldValue,
                            'satisfaction' => $satisfaction
                        ];
                        
                        if ($satisfaction !== null) {
                            $pomodoroSatisfactionSum += $satisfaction;
                            $pomodoroSatisfactionCount++;
                        }
                        $pomodoroTotalCount++;
                    }
                }
                
                error_log('POMODORO_DEBUG: Fallback processed - totalCount=' . $pomodoroTotalCount . ', items=' . count($pomodoroDiaryItems));
            } else {
                error_log('POMODORO_DEBUG: Fallback data too old (' . round($timeDiff / 3600, 2) . ' hours), not using');
            }
        } else {
            error_log('POMODORO_DEBUG: No pomodoro diary data found at all for user ' . $studentId);
        }
    }

    // NEW: Try tracking table as final fallback
    if (empty($pomodoroDiaryItems)) {
        error_log('POMODORO_DEBUG: No data in todayplans, trying tracking table fallback');

        try {
            $trackingRecords = $DB->get_records_sql("
                SELECT id, text, result, timecreated
                FROM mdl_abessi_tracking
                WHERE userid = ?
                AND timecreated >= ?
                AND hide = 0
                AND status = 'complete'
                AND result IS NOT NULL
                ORDER BY timecreated DESC
                LIMIT 16
            ", [$studentId, $pomodoro12hStart]);

            if (!empty($trackingRecords)) {
                error_log('POMODORO_DEBUG: Found ' . count($trackingRecords) . ' tracking records within 12h');

                // Transform tracking records to diary format
                $satisfactionMap = [
                    1 => 1,  // 불만족
                    2 => 2,  // 만족
                    3 => 3   // 매우만족
                ];

                $slotIndex = 1;
                foreach ($trackingRecords as $record) {
                    // Truncate long text for display
                    $statusText = strlen($record->text) > 50
                        ? substr($record->text, 0, 47) . '...'
                        : $record->text;

                    $satisfaction = isset($satisfactionMap[(int)$record->result])
                        ? $satisfactionMap[(int)$record->result]
                        : null;

                    $pomodoroDiaryItems[] = [
                        'slot' => $slotIndex,
                        'status' => $statusText,
                        'satisfaction' => $satisfaction
                    ];

                    if ($satisfaction !== null) {
                        $pomodoroSatisfactionSum += $satisfaction;
                        $pomodoroSatisfactionCount++;
                    }
                    $pomodoroTotalCount++;

                    error_log('POMODORO_DEBUG: Tracking slot ' . $slotIndex .
                              ' - text=' . substr($statusText, 0, 30) .
                              ', result=' . $record->result .
                              ', satisfaction=' . ($satisfaction !== null ? $satisfaction : 'null'));

                    $slotIndex++;
                }

                error_log('POMODORO_DEBUG: Tracking fallback complete - totalCount=' .
                          $pomodoroTotalCount .
                          ', items=' . count($pomodoroDiaryItems) .
                          ', satisfactionCount=' . $pomodoroSatisfactionCount);
            } else {
                error_log('POMODORO_DEBUG: No tracking records found within 12h');
            }
        } catch (Exception $e) {
            error_log('POMODORO_DEBUG: Tracking fallback error (file: ' . __FILE__ . ', line: ' . __LINE__ . '): ' . $e->getMessage());
        }
    }

    $pomodoroSatisfactionAvg = $pomodoroSatisfactionCount > 0 
        ? round($pomodoroSatisfactionSum / $pomodoroSatisfactionCount, 2) 
        : 0;
    
    error_log('POMODORO_DEBUG: Final result - totalCount=' . $pomodoroTotalCount . ', items=' . count($pomodoroDiaryItems) . ', satisfactionAvg=' . $pomodoroSatisfactionAvg);
        
} catch (Exception $e) {
    error_log('Error in pomodoro diary data retrieval (file: ' . __FILE__ . ', line: ' . __LINE__ . '): ' . $e->getMessage());
    $pomodoroDiaryData = null;
    $pomodoroSatisfactionCount = 0;
    $pomodoroSatisfactionSum = 0;
    $pomodoroTotalCount = 0;
    $pomodoroDiaryItems = [];
    $pomodoroSatisfactionAvg = 0;
}

// 포모도르 일기 집중 도움 여부 데이터 (오늘 기준) - 포모도르 확인 부분용
$todayFocusHelpData = $DB->get_records_sql("
    SELECT result, COUNT(*) as cnt
    FROM mdl_abessi_tracking 
    WHERE userid = ? 
    AND timecreated > ?
    AND hide = 0
    AND status = 'complete'
    AND result IS NOT NULL
    GROUP BY result
", [$studentId, $todayStart]);

$focusHelpVeryHelpful = 0;
$focusHelpHelpful = 0;
$focusHelpNormal = 0;
$focusHelpNotHelpful = 0;

if (!empty($todayFocusHelpData)) {
    foreach ($todayFocusHelpData as $data) {
        // result=3: 매우만족 (매우도움), result=2: 만족 (도움), result=1: 불만족 (별로)
        // result가 없거나 다른 값은 보통으로 처리
        if ($data->result == 3) {
            $focusHelpVeryHelpful = $data->cnt;
        } elseif ($data->result == 2) {
            $focusHelpHelpful = $data->cnt;
        } elseif ($data->result == 1) {
            $focusHelpNotHelpful = $data->cnt;
        } else {
            $focusHelpNormal += $data->cnt;
        }
    }
}

// 포모도르 일기 집중 도움 여부 데이터 (4주 기준) - 리포트용
$fourWeeksFocusHelpData = $DB->get_records_sql("
    SELECT result, COUNT(*) as cnt
    FROM mdl_abessi_tracking 
    WHERE userid = ? 
    AND timecreated > ?
    AND hide = 0
    AND status = 'complete'
    AND result IS NOT NULL
    GROUP BY result
", [$studentId, $fourWeeksAgo]);

$fourWeeksFocusHelpVeryHelpful = 0;
$fourWeeksFocusHelpHelpful = 0;
$fourWeeksFocusHelpNormal = 0;
$fourWeeksFocusHelpNotHelpful = 0;

if (!empty($fourWeeksFocusHelpData)) {
    foreach ($fourWeeksFocusHelpData as $data) {
        if ($data->result == 3) {
            $fourWeeksFocusHelpVeryHelpful = $data->cnt;
        } elseif ($data->result == 2) {
            $fourWeeksFocusHelpHelpful = $data->cnt;
        } elseif ($data->result == 1) {
            $fourWeeksFocusHelpNotHelpful = $data->cnt;
        } else {
            $fourWeeksFocusHelpNormal += $data->cnt;
        }
    }
}

// 포모도르 사용 여부 판단 (오늘 작성한 일기 기준)
$pomodoroUsage = '사용 안함';
if ($pomodoroTotalCount > 0) {
    // 오늘 포모도르 일기가 있으면 사용 여부 판단
    // 만족도 평균이 2.5 이상이면 알차게 사용, 1.5 이상이면 대충 사용
    if ($pomodoroSatisfactionAvg >= 2.5) {
        $pomodoroUsage = '알차게 사용';
    } elseif ($pomodoroSatisfactionAvg >= 1.5) {
        $pomodoroUsage = '대충 사용';
    } else {
        // 만족도가 낮거나 평가가 없으면 대충 사용으로 처리
        $pomodoroUsage = '대충 사용';
    }
}

// 최근 퀴즈 시작 지점 확인
$latestQuizStart = $DB->get_record_sql("
    SELECT timestart 
    FROM mdl_quiz_attempts 
    WHERE userid = ? AND timestart > ?
    ORDER BY timestart DESC 
    LIMIT 1", [$studentId, $aweekago]);

$latestQuizStartTime = $latestQuizStart ? $latestQuizStart->timestart : null;

// 오답노트 밀림 확인: 최근 퀴즈 시작 지점 이전의 미완료 오답노트 (active=1이고 status='begin' 또는 status='exam')
$errorNoteBacklogCount = 0;
try {
    if ($latestQuizStartTime) {
        $errorNoteBacklogData = $DB->get_records_sql("
            SELECT id
            FROM mdl_abessi_messages 
            WHERE userid = ? 
            AND active = 1
            AND (status = 'begin' OR status = 'exam')
            AND contentstype = 2
            AND hide = 0 
            AND timecreated < ?
            AND timecreated > ?
        ", [$studentId, $latestQuizStartTime, $aweekago]);
        
        $errorNoteBacklogCount = count($errorNoteBacklogData);
    } else {
        // 퀴즈 시작 지점이 없으면 최근 일주일 내 active=1이고 status='begin' 또는 status='exam'인 것만 카운트
        $errorNoteBacklogData = $DB->get_records_sql("
            SELECT id
            FROM mdl_abessi_messages 
            WHERE userid = ? 
            AND active = 1
            AND (status = 'begin' OR status = 'exam')
            AND contentstype = 2
            AND hide = 0 
            AND timecreated > ?
        ", [$studentId, $aweekago]);
        
        $errorNoteBacklogCount = count($errorNoteBacklogData);
    }
} catch (Exception $e) {
    error_log('Error checking error note backlog in index.php (line ' . __LINE__ . '): ' . $e->getMessage());
    $errorNoteBacklogCount = 0;
}

// 오답노트 데이터 (기존 로직 유지)
$errorNoteData = $DB->get_records_sql("
    SELECT * FROM mdl_abessi_messages 
    WHERE userid = ? AND (student_check = 1 OR turn = 1) AND hide = 0 AND timemodified > ? 
    ORDER BY timemodified DESC LIMIT 10", [$studentId, $hoursago]);

$errorNoteCount = count($errorNoteData);

// tremainbeforefinish 계산 (navbar.php 로직 가져옴)
$halfdayago = time() - 43200;
$timecreated = time();

// 오늘 목표 입력 시간 확인
$checkgoal2 = $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid = ? AND (type LIKE '오늘목표' OR type LIKE '검사요청') AND timecreated > ? ORDER BY id ASC LIMIT 1", [$studentId, $halfdayago]);
$tgoal = $checkgoal2 ? $checkgoal2->timecreated : $timecreated;

// 요일 계산
$jd = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$nday = jddayofweek($jd, 0);
if ($nday == 0) $nday = 7;

// 오늘 예정된 학습 시간 가져오기
$schedule = $DB->get_record_sql("SELECT * FROM mdl_abessi_schedule WHERE userid = ? AND pinned = '1' ORDER BY id DESC LIMIT 1", [$studentId]);
$hours = 0;
if ($schedule) {
    if ($nday == 1) $hours = $schedule->duration1;
    elseif ($nday == 2) $hours = $schedule->duration2;
    elseif ($nday == 3) $hours = $schedule->duration3;
    elseif ($nday == 4) $hours = $schedule->duration4;
    elseif ($nday == 5) $hours = $schedule->duration5;
    elseif ($nday == 6) $hours = $schedule->duration6;
    elseif ($nday == 7) $hours = $schedule->duration7;
}

// 브레이크 타임 지연 시간 가져오기
$breaktime = $DB->get_record_sql("SELECT timedelayed FROM mdl_abessi_breaktimelog WHERE userid = ? AND timecreated > ? ORDER BY id DESC LIMIT 1", [$studentId, $halfdayago]);
$breaktimeDelayed = $breaktime ? $breaktime->timedelayed : 0;

// tremainbeforefinish 계산 (분 단위)
$tremainbeforefinish = (int)((($tgoal + $hours * 3600 + $breaktimeDelayed) - $timecreated) / 60);

// 귀가검사 가능 여부 확인 (30분 이내인지 체크)
$canTakeGoingHomeCheck = ($tremainbeforefinish <= 30 && $tremainbeforefinish >= -30);

// AJAX 요청 처리
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] == 'save_report') {
        $responses = json_decode($_POST['responses'], true);
        $reportHtml = isset($_POST['report_html']) ? $_POST['report_html'] : '';
        $reportId = 'REPORT_' . time() . '_' . substr(md5(uniqid()), 0, 9);
        
        // 리포트 데이터 구성
        $reportData = new stdClass();
        $reportData->student_id = $studentId;
        $reportData->student_name = $studentName;
        $reportData->responses = $responses;
        $reportData->report_id = $reportId;
        $reportData->created_at = time();
        $reportData->date = date('Y년 n월 j일');
        
        // DB에 저장 (리포트 HTML과 데이터 모두 저장)
        $saveSuccess = false;
        $errorMessage = '';
        $debugInfo = [];
        
        try {
            // 리포트 HTML 크기 확인
            $reportHtmlSize = strlen($reportHtml);
            $debugInfo['report_html_size'] = $reportHtmlSize;
            $debugInfo['report_id'] = $reportId;
            $debugInfo['userid'] = $studentId;
            
            // 테이블 존재 여부 확인 (여러 방법으로 시도)
            $tableExists = false;
            $tableName = 'alt42_goinghome_reports';
            $fullTableName = 'mdl_' . $tableName;
            
            // 방법 1: get_manager()->table_exists() - Moodle은 prefix 없이 사용
            try {
                $tableExists = $DB->get_manager()->table_exists($tableName);
                $debugInfo['table_check_method1'] = $tableExists ? 'exists' : 'not_exists';
            } catch (Exception $e) {
                $debugInfo['table_check_method1_error'] = $e->getMessage();
            }
            
            // 방법 2: 직접 SQL 쿼리로 확인
            try {
                $sql = "SHOW TABLES LIKE ?";
                $result = $DB->get_records_sql($sql, [$fullTableName]);
                $tableExistsFromSQL = !empty($result);
                $debugInfo['table_check_method2'] = $tableExistsFromSQL ? 'exists' : 'not_exists';
                $debugInfo['table_check_method2_count'] = count($result);
                // SQL 결과가 있으면 tableExists를 true로 설정
                if ($tableExistsFromSQL) {
                    $tableExists = true;
                }
            } catch (Exception $e) {
                $debugInfo['table_check_method2_error'] = $e->getMessage();
            }
            
            // 방법 3: 테이블 구조 확인
            try {
                $sql = "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
                $result = $DB->get_record_sql($sql, [$fullTableName]);
                $tableExistsFromInfoSchema = $result && $result->cnt > 0;
                $debugInfo['table_check_method3'] = $tableExistsFromInfoSchema ? 'exists' : 'not_exists';
                if ($tableExistsFromInfoSchema) {
                    $tableExists = true;
                }
            } catch (Exception $e) {
                $debugInfo['table_check_method3_error'] = $e->getMessage();
            }
            
            $debugInfo['final_table_exists'] = $tableExists;
            $debugInfo['table_name_used'] = $tableName;
            $debugInfo['full_table_name'] = $fullTableName;
            
            if ($tableExists) {
                // ============================================================
                // Progressive Update 패턴: INSERT → UPDATE JSON → UPDATE HTML
                // 근본 원인 해결 후 이모지 처리 로직 제거 (utf8mb4 네이티브 지원)
                // ============================================================

                // JSON 데이터 준비
                $jsonData = json_encode($reportData, JSON_UNESCAPED_UNICODE);
                $jsonSize = strlen($jsonData);

                $debugInfo['json_size'] = $jsonSize;
                $debugInfo['json_size_mb'] = round($jsonSize / (1024 * 1024), 2);

                // JSON 크기 검증 (16MB 제한)
                $maxJsonSize = 16 * 1024 * 1024;
                if ($jsonSize > $maxJsonSize) {
                    $errorMessage = "JSON 데이터가 너무 큽니다: {$jsonSize} bytes (최대: {$maxJsonSize} bytes)";
                    $debugInfo['json_size_exceeded'] = true;
                    error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
                } else {
                    // HTML 데이터 준비 (이모지 처리 로직 제거 - utf8mb4로 그대로 저장)
                    $htmlData = $reportHtml;
                    $htmlSize = strlen($htmlData);

                    $debugInfo['html_size'] = $htmlSize;
                    $debugInfo['html_size_mb'] = round($htmlSize / (1024 * 1024), 2);

                    // HTML 크기 제한 (4MB)
                    $maxHtmlSize = 4 * 1024 * 1024;
                    if ($htmlSize > $maxHtmlSize) {
                        $htmlData = substr($htmlData, 0, $maxHtmlSize);
                        $debugInfo['html_truncated'] = true;
                        error_log("리포트 HTML 잘림 at " . __FILE__ . ":" . __LINE__ . " - 원본: {$htmlSize} bytes, 잘린 크기: {$maxHtmlSize} bytes");
                    }

                    // ============================================================
                    // Step 1: 기본 레코드 INSERT
                    // ============================================================
                    try {
                        $record = new stdClass();
                        $record->userid = $studentId;
                        $record->report_id = $reportId;
                        $record->report_html = ''; // 빈 값으로 시작
                        $record->report_data = ''; // 빈 값으로 시작
                        $record->report_date = date('Y년 n월 j일');
                        $record->timecreated = time();
                        $record->timemodified = time();

                        $insertId = $DB->insert_record($tableName, $record, true);

                        if (!$insertId || $insertId <= 0) {
                            $errorMessage = '기본 레코드 INSERT 실패: insert_record 반환값 없음 또는 0';
                            $debugInfo['insert_failed'] = true;
                            $debugInfo['insert_return_value'] = $insertId;
                            error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
                        } else {
                            $debugInfo['insert_id'] = $insertId;
                            $debugInfo['insert_success'] = true;

                            // ============================================================
                            // Step 2: JSON 데이터 UPDATE
                            // ============================================================
                            try {
                                $updateJson = new stdClass();
                                $updateJson->id = $insertId;
                                $updateJson->report_data = $jsonData;
                                $updateJson->timemodified = time();

                                $jsonUpdateSuccess = $DB->update_record($tableName, $updateJson);

                                if (!$jsonUpdateSuccess) {
                                    // JSON UPDATE 실패는 경고로 처리 (기본 레코드는 유지)
                                    $debugInfo['json_update_failed'] = true;
                                    error_log("JSON UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - report_id: {$reportId}, insert_id: {$insertId}");
                                } else {
                                    $debugInfo['json_update_success'] = true;
                                }

                            } catch (dml_exception $e) {
                                $debugInfo['json_update_dml_exception'] = $e->getMessage();
                                $debugInfo['json_update_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
                                error_log("JSON UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
                            } catch (Exception $e) {
                                $debugInfo['json_update_exception'] = $e->getMessage();
                                error_log("JSON UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
                            }

                            // ============================================================
                            // Step 3: HTML 데이터 UPDATE
                            // ============================================================
                            try {
                                $updateHtml = new stdClass();
                                $updateHtml->id = $insertId;
                                $updateHtml->report_html = $htmlData; // utf8mb4 인코딩으로 이모지 그대로 저장
                                $updateHtml->timemodified = time();

                                $htmlUpdateSuccess = $DB->update_record($tableName, $updateHtml);

                                if (!$htmlUpdateSuccess) {
                                    // HTML UPDATE 실패는 경고로 처리 (기본 레코드와 JSON은 유지)
                                    $debugInfo['html_update_failed'] = true;
                                    error_log("HTML UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - report_id: {$reportId}, insert_id: {$insertId}");
                                } else {
                                    $debugInfo['html_update_success'] = true;
                                }

                            } catch (dml_exception $e) {
                                $debugInfo['html_update_dml_exception'] = $e->getMessage();
                                $debugInfo['html_update_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
                                error_log("HTML UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
                                error_log("HTML size: {$htmlSize} bytes, contains emoji: " . (preg_match('/[\x{1F600}-\x{1F64F}]/u', $htmlData) ? 'yes' : 'no'));
                            } catch (Exception $e) {
                                $debugInfo['html_update_exception'] = $e->getMessage();
                                error_log("HTML UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
                            }

                            // ============================================================
                            // Step 4: 최종 성공 여부 결정
                            // ============================================================

                            // 기본 레코드 INSERT는 항상 성공했으므로 saveSuccess = true
                            $saveSuccess = true;

                            // 부분 실패 여부 확인
                            $partialSuccess = false;
                            if (!($debugInfo['json_update_success'] ?? false) || !($debugInfo['html_update_success'] ?? false)) {
                                $partialSuccess = true;
                                $debugInfo['partial_success'] = true;
                                $debugInfo['partial_reason'] = [];

                                if (!($debugInfo['json_update_success'] ?? false)) {
                                    $debugInfo['partial_reason'][] = 'JSON UPDATE 실패';
                                }
                                if (!($debugInfo['html_update_success'] ?? false)) {
                                    $debugInfo['partial_reason'][] = 'HTML UPDATE 실패';
                                }
                            }

                            $debugInfo['save_strategy'] = 'progressive_update';
                            $debugInfo['emoji_processing'] = 'utf8mb4_native';
                        }

                    } catch (dml_exception $e) {
                        $errorMessage = '기본 레코드 INSERT 중 DB 오류: ' . $e->getMessage();
                        $debugInfo['insert_dml_exception'] = $e->getMessage();
                        $debugInfo['insert_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
                        error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
                        error_log("Details: " . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
                    } catch (Exception $e) {
                        $errorMessage = '기본 레코드 INSERT 중 오류: ' . $e->getMessage();
                        $debugInfo['insert_exception'] = $e->getMessage();
                        $debugInfo['insert_exception_class'] = get_class($e);
                        error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
                    }
                }
            } else {
                $errorMessage = '리포트 테이블(alt42_goinghome_reports)이 존재하지 않습니다.';
                $debugInfo['table_exists'] = false;
                error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
            }
            
            // 기존 테이블에도 저장 (하위 호환성)
            try {
                $oldRecord = new stdClass();
                $oldRecord->userid = $studentId;
                $oldRecord->text = json_encode($reportData, JSON_UNESCAPED_UNICODE);
                $oldRecord->timecreated = time();
                
                $DB->insert_record('alt42_goinghome', $oldRecord);
            } catch (Exception $e) {
                // 기존 테이블 저장 실패는 무시 (하위 호환성용)
                error_log('Error saving to old table in index.php (line ' . __LINE__ . '): ' . $e->getMessage());
            }
        } catch (Exception $e) {
            $errorMessage = '리포트 저장 중 예외 발생: ' . $e->getMessage();
            $debugInfo['outer_exception'] = $e->getMessage();
            $debugInfo['outer_exception_class'] = get_class($e);
            error_log('Error saving goinghome report in index.php (line ' . __LINE__ . '): ' . $e->getMessage());
        }
        
        if ($saveSuccess) {
            echo json_encode(['success' => true, 'report_id' => $reportId, 'message' => '리포트 저장 완료']);
        } else {
            // 디버그 모드에서는 상세 정보 포함
            $response = [
                'success' => false, 
                'report_id' => $reportId, 
                'message' => $errorMessage ?: '리포트 저장 실패',
                'debug' => $debugInfo
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // 리포트 조회
    if ($_POST['action'] == 'get_report') {
        $reportId = isset($_POST['report_id']) ? $_POST['report_id'] : '';
        
        try {
            if ($DB->get_manager()->table_exists('alt42_goinghome_reports')) {
                $report = $DB->get_record('alt42_goinghome_reports', ['report_id' => $reportId, 'userid' => $studentId]);
                
                if ($report) {
                    echo json_encode([
                        'success' => true,
                        'report_html' => $report->report_html,
                        'report_data' => json_decode($report->report_data),
                        'report_date' => $report->report_date,
                        'timecreated' => $report->timecreated
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => '리포트를 찾을 수 없습니다.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => '리포트 테이블이 존재하지 않습니다.']);
            }
        } catch (Exception $e) {
            error_log('Error getting report in index.php (line ' . __LINE__ . '): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '리포트 조회 중 오류가 발생했습니다.']);
        }
        exit;
    }
    
    // 리포트 목록 조회 (최근 리포트)
    if ($_POST['action'] == 'get_recent_reports') {
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        try {
            if ($DB->get_manager()->table_exists('alt42_goinghome_reports')) {
                $reports = $DB->get_records_sql("
                    SELECT id, report_id, report_date, timecreated
                    FROM mdl_alt42_goinghome_reports
                    WHERE userid = ?
                    ORDER BY timecreated DESC
                    LIMIT ?
                ", [$studentId, $limit]);
                
                $reportList = [];
                foreach ($reports as $report) {
                    $reportList[] = [
                        'id' => $report->id,
                        'report_id' => $report->report_id,
                        'report_date' => $report->report_date,
                        'timecreated' => $report->timecreated,
                        'date_formatted' => date('Y-m-d H:i', $report->timecreated)
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'reports' => $reportList
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '리포트 테이블이 존재하지 않습니다.']);
            }
        } catch (Exception $e) {
            error_log('Error getting recent reports in index.php (line ' . __LINE__ . '): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '리포트 목록 조회 중 오류가 발생했습니다.']);
        }
        exit;
    }
    
    // 최근 리포트 조회 (가장 최근 리포트)
    if ($_POST['action'] == 'get_latest_report') {
        try {
            if ($DB->get_manager()->table_exists('alt42_goinghome_reports')) {
                $report = $DB->get_record_sql("
                    SELECT *
                    FROM mdl_alt42_goinghome_reports
                    WHERE userid = ?
                    ORDER BY timecreated DESC
                    LIMIT 1
                ", [$studentId]);
                
                if ($report) {
                    echo json_encode([
                        'success' => true,
                        'report_html' => $report->report_html,
                        'report_data' => json_decode($report->report_data),
                        'report_date' => $report->report_date,
                        'report_id' => $report->report_id,
                        'timecreated' => $report->timecreated
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => '저장된 리포트가 없습니다.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => '리포트 테이블이 존재하지 않습니다.']);
            }
        } catch (Exception $e) {
            error_log('Error getting latest report in index.php (line ' . __LINE__ . '): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '리포트 조회 중 오류가 발생했습니다.']);
        }
        exit;
    }
    
    if ($_POST['action'] == 'transform_message') {
        $message = $_POST['message'];
        $context = $_POST['context'] ?? '';
        
        // OpenAI API를 사용한 메시지 변환
        $transformedMessage = transformWithOpenAI($message, $context);
        
        echo json_encode(['success' => true, 'transformed' => $transformedMessage]);
        exit;
    }
    
    if ($_POST['action'] == 'generate_question') {
        $originalQuestion = $_POST['original_question'];
        $previousResponses = json_decode($_POST['previous_responses'] ?? '[]', true);
        
        // OpenAI API를 사용한 질문 재생성
        $newQuestion = generateCreativeQuestion($originalQuestion, $previousResponses);
        
        echo json_encode(['success' => true, 'question' => $newQuestion]);
        exit;
    }
    
    if ($_POST['action'] == 'generate_new_question') {
        $topic = $_POST['topic'];
        $topicDescription = $_POST['topic_description'];
        $previousResponses = json_decode($_POST['previous_responses'] ?? '[]', true);
        
        // OpenAI API를 사용한 완전히 새로운 질문 생성
        $result = generateCompletelyNewQuestion($topic, $topicDescription, $previousResponses);
        
        echo json_encode(['success' => true, 'question' => $result['question'], 'options' => $result['options']]);
        exit;
    }
}

// OpenAI API를 사용한 메시지 변환 함수
function transformWithOpenAI($message, $context = '') {
    $apiKey = OPENAI_API_KEY;
    $model = OPENAI_MODEL;
    
    $systemPrompt = "당신은 친근하고 격려하는 AI 교사입니다. 학생의 귀가 전 체크를 도와주고 있습니다.
    학생의 답변에 대해 공감하고 격려하는 피드백을 제공해주세요. 이모지를 적절히 사용하여 친근감을 표현해주세요.
    가끔은 살짝 장난스럽게, 때로는 약간의 비아냥(하지만 상처주지 않게)을 섞어서 자연스럽고 인간적인 대화를 만들어주세요.
    매번 같은 패턴의 답변을 피하고, 다양한 어투와 표현을 사용해주세요.";
    
    $userPrompt = "학생의 답변: $message\n맥락: $context\n\n위 답변에 대한 짧고 격려하는 피드백을 제공해주세요.";
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.8,
        'max_tokens' => 100
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5초 타임아웃
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // 연결 타임아웃 2초
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }
    }
    
    // 폴백 응답
    return "잘했어! 👍";
}

// OpenAI API를 사용한 창의적 질문 생성 함수
function generateCreativeQuestion($originalQuestion, $previousResponses = []) {
    $apiKey = OPENAI_API_KEY;
    $model = OPENAI_MODEL;
    
    $systemPrompt = "당신은 재치 있고 친근한 학원 선생님입니다. 
    학생에게 귀가 전 질문을 하는데, 같은 내용을 매번 다른 표현으로 물어봐야 합니다.
    재미있고 새로운 표현을 사용하되, 학생이 편하게 답할 수 있도록 해주세요.
    이모티콘을 적절히 사용하고, 가끔 트렌디한 표현이나 유행어도 섞어주세요.
    너무 딱딱하지 않고 친근한 반말로 물어봐주세요.";
    
    $previousText = !empty($previousResponses) ? 
        "\n\n이전 대화 내용: " . json_encode($previousResponses, JSON_UNESCAPED_UNICODE) : "";
    
    $userPrompt = "원래 질문: $originalQuestion\n\n위 질문을 전혀 다른 표현으로 재미있게 바꿔주세요. 
    의미는 같아야 하지만 표현은 완전히 달라야 합니다.$previousText";
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.9,
        'max_tokens' => 100
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5초 타임아웃
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // 연결 타임아웃 2초
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }
    }
    
    // 폴백 - 원래 질문 반환
    return $originalQuestion;
}

// OpenAI API를 사용한 완전히 새로운 질문 생성 함수
function generateCompletelyNewQuestion($topic, $topicDescription, $previousResponses = []) {
    $apiKey = OPENAI_API_KEY;
    $model = 'gpt-3.5-turbo'; // 더 빠른 모델 사용
    
    $systemPrompt = "당신은 한국 수학학원(mathking 시스템 사용)의 친근한 선생님입니다.
    학생의 하루 학습을 마무리하는 귀가검사에서 질문을 생성해야 합니다.
    매번 완전히 새로운 질문과 선택지를 만들어야 합니다.
    
    수학학원 수업 상황:
    - 오답노트: 틀린 문제를 정리하고 복습하는 활동
    - 테스트: 개념이나 유형 문제를 풀어보는 시험
    - 개념공부: 새로운 수학 개념을 배우고 이해하는 활동
    - 복습: 이전에 배운 내용을 다시 공부하는 활동
    - 수학일기 작성: 오늘 배운 내용을 정리하고 느낀 점을 기록
    - 지면평가: 선생님 앞에서 직접 설명하며 피드백 받는 활동
    - TTS 듣기: 음성으로 개념이나 문제 설명을 듣는 활동
    
    규칙:
    1. 주어진 주제에 대해 창의적이고 새로운 질문을 만드세요
    2. 질문은 친근한 반말로, 이모티콘을 적절히 사용하세요
    3. 최신 유행어나 MZ세대 표현을 가끔 섞어주세요
    4. 농담이나 비아냥을 살짝 섞되, 상처주지 않게 하세요
    5. 선택지는 반드시 정확히 5개로 만들어주세요 (5지선다)
    6. 수학학원 수업 상황(오답노트, 테스트, 개념공부, 복습, 수학일기, 지면평가, TTS 등)을 활용해 현장감 있게 질문하세요
    7. 절대 이전에 나온 질문과 똑같이 만들지 마세요";
    
    $previousText = !empty($previousResponses) ? 
        "\n\n이전 응답들: " . json_encode($previousResponses, JSON_UNESCAPED_UNICODE) : "";
    
    $userPrompt = "주제: $topicDescription\n\n위 주제에 대해 완전히 새로운 질문과 정확히 5개의 선택지를 만들어주세요.
    수학학원 수업 상황(오답노트, 테스트, 개념공부, 복습, 수학일기 작성, 지면평가, TTS 듣기 등)을 활용해 현장감 있게 질문해주세요.
    이전에 없던 참신한 관점으로 질문해주세요.$previousText\n\n응답 형식:
    질문: [여기에 질문]
    선택지:
    1. [선택지1]
    2. [선택지2]
    3. [선택지3]
    4. [선택지4]
    5. [선택지5]";
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.9,
        'max_tokens' => 150
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5초 타임아웃
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // 연결 타임아웃 2초
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            $content = $result['choices'][0]['message']['content'];
            
            // 응답 파싱
            preg_match('/질문:\s*(.+?)(?=선택지:)/s', $content, $questionMatch);
            preg_match_all('/\d+\.\s*(.+?)(?=\d+\.|$)/s', $content, $optionMatches);
            
            $question = isset($questionMatch[1]) ? trim($questionMatch[1]) : '';
            $options = isset($optionMatches[1]) ? array_map('trim', $optionMatches[1]) : [];
            
            // 옵션을 정확히 5개로 제한 (5지선다)
            if (count($options) > 5) {
                $options = array_slice($options, 0, 5);
            } elseif (count($options) < 5 && count($options) > 0) {
                // 부족한 경우 기본 옵션 추가
                $defaultOptions = ['완벽했어!', '괜찮았어', '보통이야', '조금 아쉬웠어', '별로였어'];
                $needed = 5 - count($options);
                $options = array_merge($options, array_slice($defaultOptions, 0, $needed));
            }
            
            if ($question && !empty($options) && count($options) === 5) {
                return ['question' => $question, 'options' => $options];
            }
        }
    }
    
    // 폴백 - 기본 질문 반환 (모두 5지선다)
    $fallbackQuestions = [
        'weekly_goal' => ['question' => '이번 주 목표 체크했어? 오늘은 뭐 했어? 🎯', 'options' => ['완벽하게 달성!', '거의 다 했어', '절반 정도?', '조금 했어', '음... 노코멘트']],
        'math_diary' => ['question' => '수학일기 썼어? 진짜로? 👀', 'options' => ['당연하지! 완벽해', '대충이라도 썼어', '조금 썼어', '아... 까먹었어', '수학일기가 뭐야?']],
        'problem_count' => ['question' => '오늘 문제 몇 개나 정복했어? 💪', 'options' => ['30개 이상!', '20개 정도', '10개 정도', '5개 정도', '세는 게 무의미해...']],
        'concept_study' => ['question' => '오늘 개념공부는 어땠어? 새로 배운 개념 이해됐어? 📚', 'options' => ['완벽하게 이해했어!', '대부분 이해했어', '반반이야', '조금 헷갈려', '전혀 모르겠어']],
        'error_note' => ['question' => '오답노트 정리했어? 틀린 문제 복습 제대로 했어? 📝', 'options' => ['완벽하게 정리했어!', '대부분 정리했어', '반 정도 정리했어', '조금만 정리했어', '아직 안했어']],
        'test' => ['question' => '오늘 테스트나 유형 문제 풀 때 어땠어? 자신 있어? 🧪', 'options' => ['완벽하게 풀었어!', '대부분 맞았어', '반반이야', '많이 틀렸어', '망했어...']],
        'review' => ['question' => '복습은 제대로 했어? 이전에 배운 내용 다시 확인했어? 🔄', 'options' => ['완벽하게 복습했어!', '대부분 복습했어', '반 정도 복습했어', '조금만 복습했어', '복습 안했어']],
        'face_evaluation' => ['question' => '지면평가 할 때 어땠어? 선생님 앞에서 설명할 때 떨렸어? 🎤', 'options' => ['완벽하게 설명했어!', '대부분 설명했어', '반 정도 설명했어', '조금만 설명했어', '너무 떨려서 못했어']],
        'tts_listening' => ['question' => 'TTS로 개념 설명 들었어? 음성 강의 도움됐어? 🔊', 'options' => ['완벽하게 이해했어!', '대부분 이해했어', '반반이야', '조금 헷갈려', '전혀 모르겠어']],
        'default' => ['question' => '오늘 수업 어땠어? 솔직히 말해봐 😏', 'options' => ['최고였어!', '괜찮았어', '그냥 그래', '조금 힘들었어', '힘들었어...']]
    ];
    
    return $fallbackQuestions[$topic] ?? $fallbackQuestions['default'];
}

// 랜덤 질문 주제 풀 (수학학원 수업 상황 반영)
$randomQuestionTopics = [
    'weekly_goal' => '주간목표 확인과 오늘 목표 설정',
    'math_diary' => '수학일기 작성 여부 (오늘 배운 내용 정리)',
    'problem_count' => '오늘 푼 문제 개수',
    'concept_study' => '개념공부 과정 (새로운 수학 개념 이해)',
    'error_note' => '오답노트 정리 (틀린 문제 복습)',
    'test' => '테스트나 유형 문제 풀이 (시험 상황)',
    'review' => '복습 활동 (이전 내용 다시 공부)',
    'face_evaluation' => '지면평가 (선생님 앞에서 설명)',
    'tts_listening' => 'TTS 듣기 (음성 강의 활용)',
    'questions_asked' => '필요한 질문 수행 여부',
    'rest_pattern' => '휴식과 집중의 패턴 유지',
    'satisfaction' => '오늘 수업 만족도',
    'boredom' => '지루한 구간 존재 여부',
    'stress_level' => '불안이나 스트레스 구간',
    'unsaid_words' => '선생님께 못한 말',
    'study_amount' => '공부양의 적절성',
    'difficulty_level' => '난이도의 적합성',
    'pace_anxiety' => '진도에 대한 불안감',
    'self_improvement' => '개선점 발견 여부',
    'positive_moment' => '수학에 대한 긍정적 인식',
    'missed_opportunity' => '망설임으로 놓친 기회',
    'intuition_solving' => '느낌으로 푼 문제',
    'forced_solving' => '무리한 풀이 강행',
    'easy_problems' => '너무 쉬운 문제만 풀기',
    'long_problem' => '한 문제에 너무 오래 매달림',
    'daily_plan' => '오늘 계획한 진도 달성',
    'inefficiency' => '비효율적 시간 사용 구간'
];

// 랜덤 질문 제거 (5/6, 6/6 단계 제거)
$selectedTopics = [];

// 선택된 주제를 JavaScript로 전달하기 위해 저장
$selectedTopicsJson = json_encode($selectedTopics);

// 현재 날짜
$today = date('Y년 n월 j일');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 귀가검사 도우미</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="pomodoro_reactions.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-primary: #f9fafb;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --border: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 1rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .container {
            max-width: 1024px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 2rem;
            color: var(--text-primary);
            text-shadow: 0 0 20px var(--accent);
        }
        
        .avatar-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .avatar {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .avatar:hover {
            transform: scale(1.05);
        }
        
        .avatar.wave {
            animation: bounce 0.5s ease-in-out;
        }
        
        .avatar.talk {
            animation: pulse 1s infinite;
        }
        
        .avatar.celebrate {
            animation: spin 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .main-content {
            background: var(--bg-card);
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }
        
        .message-area {
            margin: 0.5rem 0;
            min-height: 80px;
            margin-bottom: 0.75rem;
        }
        
        .message-text {
            font-size: 1.5rem;
            color: var(--text-primary);
            line-height: 1.8;
            font-weight: 500;
        }
        
        #completeMessage {
            text-align: center;
        }
        
        .typing-cursor {
            animation: blink 1s infinite;
            margin-left: 0.25rem;
        }
        
        .loading-text {
            color: var(--text-secondary);
            font-style: italic;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.75rem;
            animation: fadeIn 0.5s ease-out;
        }
        
        @media (max-width: 1200px) {
            .options-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .options-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .options-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .option-button {
            padding: 1rem 1.5rem;
            border: 2px solid var(--border);
            background: var(--bg-secondary);
            border-radius: 0.75rem;
            font-size: 1.2rem;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .option-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: var(--accent);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }
        
        .option-button:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        
        .option-button:hover::before {
            width: 300px;
            height: 300px;
        }
        
        /* 체크 표시 애니메이션 */
        .option-button.checked {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
            transform: scale(1.05);
            pointer-events: none;
        }
        
        .option-button.checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            font-size: 2rem;
            font-weight: bold;
            color: white;
            animation: checkmarkAppear 0.4s ease-out forwards;
        }
        
        @keyframes checkmarkAppear {
            0% {
                transform: translate(-50%, -50%) scale(0) rotate(-180deg);
                opacity: 0;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.2) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(1) rotate(0deg);
                opacity: 1;
            }
        }
        
        .action-button {
            padding: 1rem 2rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 auto;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .action-button:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }
        
        .action-button.green {
            background: var(--success);
        }
        
        .action-button.green:hover {
            background: #059669;
        }
        
        .progress-bar {
            background: var(--bg-card);
            border-radius: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            padding: 1rem;
            border: 1px solid var(--border);
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .progress-track {
            width: 100%;
            height: 0.5rem;
            background: var(--bg-secondary);
            border-radius: 9999px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, var(--accent), var(--accent-hover));
            border-radius: 9999px;
            transition: width 0.5s ease-out;
        }
        
        .report {
            background: var(--bg-card);
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            padding: 1.5rem;
            max-width: 768px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease-out;
            color: var(--text-primary);
        }
        
        .report h2 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .report-info {
            background: var(--bg-secondary);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .report-info p {
            margin: 0.25rem 0;
        }
        
        .attention-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .attention-box h3 {
            color: var(--danger);
            margin-bottom: 0.5rem;
        }
        
        .attention-box ul {
            color: var(--danger);
            margin-left: 1.5rem;
        }
        
        .response-item {
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .response-question {
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .response-answer {
            color: var(--accent);
            margin-top: 0.25rem;
        }
        
        .hidden {
            display: none;
        }
        
        .name-input-container {
            display: flex;
            gap: 0.5rem;
            max-width: 320px;
            margin: 0 auto;
        }
        
        .name-input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .name-input:focus {
            outline: none;
            ring: 2px solid var(--accent);
            border-color: var(--accent);
        }
        
        .celebration-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            margin: 0;
            text-align: center;
            padding: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 9999;
        }
        
        .confetti-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        
        .confetti {
            position: absolute;
            background: var(--accent);
            animation: confetti-fall 4s linear infinite;
            border-radius: 2px;
        }
        
        /* 다양한 크기와 모양의 confetti */
        .confetti:nth-child(1) { left: 5%; width: 12px; height: 12px; animation-delay: 0s; background: #ff6b6b; border-radius: 50%; }
        .confetti:nth-child(2) { left: 15%; width: 8px; height: 16px; animation-delay: 0.2s; background: #4ecdc4; transform: rotate(45deg); }
        .confetti:nth-child(3) { left: 25%; width: 10px; height: 10px; animation-delay: 0.4s; background: #ffe66d; border-radius: 50%; }
        .confetti:nth-child(4) { left: 35%; width: 14px; height: 8px; animation-delay: 0.6s; background: #a8e6cf; }
        .confetti:nth-child(5) { left: 45%; width: 10px; height: 10px; animation-delay: 0.8s; background: #ff8cc8; border-radius: 50%; }
        .confetti:nth-child(6) { left: 55%; width: 12px; height: 12px; animation-delay: 1s; background: #ff6b6b; transform: rotate(45deg); }
        .confetti:nth-child(7) { left: 65%; width: 8px; height: 16px; animation-delay: 1.2s; background: #4ecdc4; }
        .confetti:nth-child(8) { left: 75%; width: 10px; height: 10px; animation-delay: 1.4s; background: #ffe66d; border-radius: 50%; }
        .confetti:nth-child(9) { left: 85%; width: 12px; height: 8px; animation-delay: 1.6s; background: #a8e6cf; transform: rotate(45deg); }
        .confetti:nth-child(10) { left: 95%; width: 10px; height: 10px; animation-delay: 1.8s; background: #ff8cc8; border-radius: 50%; }
        .confetti:nth-child(11) { left: 10%; width: 8px; height: 14px; animation-delay: 2s; background: #ff6b6b; }
        .confetti:nth-child(12) { left: 20%; width: 12px; height: 12px; animation-delay: 2.2s; background: #4ecdc4; border-radius: 50%; }
        .confetti:nth-child(13) { left: 40%; width: 10px; height: 10px; animation-delay: 2.4s; background: #ffe66d; transform: rotate(45deg); }
        .confetti:nth-child(14) { left: 60%; width: 14px; height: 8px; animation-delay: 2.6s; background: #a8e6cf; }
        .confetti:nth-child(15) { left: 80%; width: 10px; height: 10px; animation-delay: 2.8s; background: #ff8cc8; border-radius: 50%; }
        
        @keyframes confetti-fall {
            0% {
                transform: translateY(-150px) rotate(0deg) scale(1);
                opacity: 1;
            }
            50% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(calc(100vh + 150px)) rotate(1080deg) scale(0.5);
                opacity: 0;
            }
        }
        
        .completion-stats {
            background: var(--bg-secondary);
            border-radius: 1rem;
            padding: 1.25rem;
            margin: 0.75rem 0;
            border: 2px solid var(--accent);
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.3);
        }
        
        .completion-content-wrapper {
            width: 100%;
            padding: 0.5rem 0;
        }
        
        .report-preview-container {
            margin-top: 2rem;
            max-height: none;
            overflow-y: visible;
            border: 2px solid var(--accent);
            border-radius: 1rem;
            padding: 1rem;
            background: var(--bg-card);
        }
        
        .report-preview-container .report {
            margin: 0;
            box-shadow: none;
        }
        
        .report-preview-container .report h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            font-size: 1.2rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
        }
        
        .stat-value {
            color: var(--accent);
            font-weight: bold;
        }
        
        .pulse {
            animation: pulse-glow 2s infinite;
        }
        
        @keyframes pulse-glow {
            0% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(99, 102, 241, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
            }
        }
        
        .data-comparison {
            background: var(--bg-secondary);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
            animation: fadeIn 0.5s ease-out;
        }
        
        .data-comparison-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }
        
        .data-label {
            font-weight: 500;
        }
        
        .data-value {
            color: var(--accent);
            font-weight: bold;
        }
        
        .data-match {
            color: var(--success);
        }
        
        .data-mismatch {
            color: var(--danger);
        }
        
        .calmness-feedback-box {
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            border-radius: 1.5rem;
            padding: 2rem;
            margin: 1.5rem 0;
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.4);
            animation: calmnessFeedbackAppear 0.6s ease-out;
            border: 3px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .calmness-feedback-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: calmnessShine 3s infinite;
        }
        
        .calmness-feedback-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-align: center;
            line-height: 1.6;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
            animation: calmnessTextPulse 2s ease-in-out infinite;
        }
        
        @keyframes calmnessFeedbackAppear {
            0% {
                opacity: 0;
                transform: scale(0.8) translateY(20px);
            }
            50% {
                transform: scale(1.05) translateY(-5px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        @keyframes calmnessShine {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        @keyframes calmnessTextPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
        }
        
        .calmness-feedback-emoji {
            font-size: 3rem;
            display: block;
            margin-bottom: 0.5rem;
            animation: calmnessEmojiBounce 1s ease-in-out infinite;
        }
        
        @keyframes calmnessEmojiBounce {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            25% {
                transform: translateY(-10px) rotate(-5deg);
            }
            75% {
                transform: translateY(-10px) rotate(5deg);
            }
        }
        
        @media print {
            body {
                background: white;
            }
            
            .avatar-container,
            .action-button,
            #progressBar {
                display: none !important;
            }
            
            .report {
                box-shadow: none;
                max-width: 100%;
                margin: 0;
                padding: 1rem;
            }
            
            h1 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .attention-box {
                background: #f9f9f9;
                border: 2px solid #333;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 AI 귀가검사 도우미</h1>
        
        <div class="avatar-container">
            <div class="avatar" id="avatar">
                <div style="color: white; font-size: 3rem;">👩‍🏫</div>
            </div>
        </div>
        
        <div class="main-content" id="mainContent">
            <!-- 질문 단계 -->
            <div id="questionsStep" class="step hidden">
                <div class="message-area">
                    <p class="message-text" id="questionText"></p>
                </div>
                <div class="options-grid" id="optionsGrid"></div>
                <div id="dataComparison" class="data-comparison hidden"></div>
            </div>
            
            <!-- 완료 단계 -->
            <div id="completeStep" class="step hidden">
                <div id="completionContent" class="completion-content-wrapper">
                    <div class="message-area">
                        <p class="message-text" id="completeMessage"></p>
                    </div>
                    <!-- 꽃비는 최상단으로 이동 -->
                    <div id="celebrationContainer" class="celebration-container">
                        <div class="confetti-wrapper">
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                        </div>
                    </div>
                    <!-- 리포트 미리보기 영역 -->
                    <div id="reportPreview" class="report-preview-container">
                        <!-- 리포트 내용이 여기에 자동으로 표시됨 -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 진행 상황 표시 -->
        <div id="progressBar" class="progress-bar hidden">
            <div class="progress-header">
                <span>진행 상황</span>
                <span id="progressText">1 / 4</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
            </div>
        </div>
        
        <!-- 리포트 -->
        <div id="reportSection" class="hidden"></div>
        
        <!-- 리포트 조회 링크 (우측 하단 고정) -->
        <div id="reportViewLink" style="position: fixed; right: 1rem; bottom: 1rem; z-index: 1000;">
            <button id="viewReportBtn" style="padding: 0.75rem 1.5rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: all 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.3)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)'">
                📋 리포트
            </button>
        </div>
    </div>
    
    <script>
        // 전역 변수
        let currentStep = 'questions';
        let currentQuestion = 0;
        let responses = {};
        let studentName = <?php echo isset($studentName) ? json_encode($studentName, JSON_UNESCAPED_UNICODE) : '""'; ?>;
        let studentId = <?php echo isset($studentId) ? (int)$studentId : 0; ?>;
        let selectedRandomQuestions = [];
        let typingTimeout = null;
        let reportGenerated = false;
        
        // PHP에서 전달된 선택된 주제들
        const selectedTopics = <?php 
            if (isset($selectedTopics) && is_array($selectedTopics)) {
                $json = json_encode($selectedTopics, JSON_UNESCAPED_UNICODE);
                echo ($json !== false) ? $json : '[]';
            } else {
                echo '[]';
            }
        ?>;
        
        // PHP에서 전달된 실제 데이터
        const actualCalmness = <?php echo isset($calmnessGrade) ? json_encode($calmnessGrade, JSON_UNESCAPED_UNICODE) : '""'; ?>;
        const actualCalmnessGrade = <?php echo isset($calmnessGrade) ? json_encode($calmnessGrade, JSON_UNESCAPED_UNICODE) : '""'; ?>;
        const actualCalmnessLevel = <?php echo (isset($actualCalmness) && $actualCalmness !== null && is_numeric($actualCalmness)) ? (int)$actualCalmness : 'null'; ?>;
        const actualCalmnessScore = <?php echo (isset($actualCalmness) && $actualCalmness !== null && is_numeric($actualCalmness)) ? (int)$actualCalmness : 'null'; ?>;
        const actualPomodoroUsage = <?php echo isset($pomodoroUsage) ? json_encode($pomodoroUsage, JSON_UNESCAPED_UNICODE) : '""'; ?>;
        const actualErrorNoteCount = <?php echo (isset($errorNoteCount) && is_numeric($errorNoteCount)) ? (int)$errorNoteCount : 0; ?>;
        const pomodoroSatisfactionAvg = <?php echo (isset($pomodoroSatisfactionAvg) && is_numeric($pomodoroSatisfactionAvg)) ? (float)$pomodoroSatisfactionAvg : 0; ?>;
        const pomodoroSatisfactionCount = <?php echo (isset($pomodoroSatisfactionCount) && is_numeric($pomodoroSatisfactionCount)) ? (int)$pomodoroSatisfactionCount : 0; ?>;
        const pomodoroTotalCount = <?php echo (isset($pomodoroTotalCount) && is_numeric($pomodoroTotalCount)) ? (int)$pomodoroTotalCount : 0; ?>;
        const canTakeGoingHomeCheck = <?php echo isset($canTakeGoingHomeCheck) && $canTakeGoingHomeCheck ? 'true' : 'false'; ?>;
        const tremainbeforefinish = <?php echo isset($tremainbeforefinish) ? (int)$tremainbeforefinish : 0; ?>;
        const pomodoroDiaryItems = <?php 
            if (isset($pomodoroDiaryItems) && is_array($pomodoroDiaryItems)) {
                $json = json_encode($pomodoroDiaryItems, JSON_UNESCAPED_UNICODE);
                echo ($json !== false) ? $json : '[]';
            } else {
                echo '[]';
            }
        ?>;
        const errorNoteBacklogCount = <?php echo (isset($errorNoteBacklogCount) && is_numeric($errorNoteBacklogCount)) ? (int)$errorNoteBacklogCount : 0; ?>;
        const todayNoteCount = <?php echo (isset($todayNoteCount) && is_numeric($todayNoteCount)) ? (int)$todayNoteCount : 0; ?>;
        const focusHelpVeryHelpful = <?php echo (isset($focusHelpVeryHelpful) && is_numeric($focusHelpVeryHelpful)) ? (int)$focusHelpVeryHelpful : 0; ?>;
        const focusHelpHelpful = <?php echo (isset($focusHelpHelpful) && is_numeric($focusHelpHelpful)) ? (int)$focusHelpHelpful : 0; ?>;
        const focusHelpNormal = <?php echo (isset($focusHelpNormal) && is_numeric($focusHelpNormal)) ? (int)$focusHelpNormal : 0; ?>;
        const focusHelpNotHelpful = <?php echo (isset($focusHelpNotHelpful) && is_numeric($focusHelpNotHelpful)) ? (int)$focusHelpNotHelpful : 0; ?>;
        
        // 4주 기준 집중 도움 여부 데이터 (리포트용)
        const fourWeeksFocusHelpVeryHelpful = <?php echo (isset($fourWeeksFocusHelpVeryHelpful) && is_numeric($fourWeeksFocusHelpVeryHelpful)) ? (int)$fourWeeksFocusHelpVeryHelpful : 0; ?>;
        const fourWeeksFocusHelpHelpful = <?php echo (isset($fourWeeksFocusHelpHelpful) && is_numeric($fourWeeksFocusHelpHelpful)) ? (int)$fourWeeksFocusHelpHelpful : 0; ?>;
        const fourWeeksFocusHelpNormal = <?php echo (isset($fourWeeksFocusHelpNormal) && is_numeric($fourWeeksFocusHelpNormal)) ? (int)$fourWeeksFocusHelpNormal : 0; ?>;
        const fourWeeksFocusHelpNotHelpful = <?php echo (isset($fourWeeksFocusHelpNotHelpful) && is_numeric($fourWeeksFocusHelpNotHelpful)) ? (int)$fourWeeksFocusHelpNotHelpful : 0; ?>;
        
        // 최근 1주일 침착도 데이터
        const calmnessWeekData = <?php 
            if (isset($calmnessWeekData) && is_array($calmnessWeekData)) {
                $json = json_encode($calmnessWeekData, JSON_UNESCAPED_UNICODE);
                echo ($json !== false) ? $json : '[]';
            } else {
                echo '[]';
            }
        ?>;
        let calmnessChart = null;
        let chartRenderComplete = false; // 그래프 렌더링 완료 플래그
        
        // "오늘 하루는 어떤 하루였어?" 테마별 5지 선다형
        const dailyMoodThemes = {
            fruit: {
                question: '오늘 하루는 어떤 과일 같았어? 🍎',
                options: [
                    '잘 익은 복숭아 같았어. 부드럽고 달달했거든. 🍑',
                    '레몬 같은 하루였어. 톡 쏘고 깔끔했지. 🍋',
                    '딸기 같은 하루였어. 새콤달콤하고 상큼했어. 🍓',
                    '수박 같은 하루였어. 시원하고 시원했지. 🍉',
                    '사과 같은 하루였어. 아삭하고 신선했어. 🍎'
                ]
            },
            animal: {
                question: '오늘 하루는 어떤 동물 같았어? 🐾',
                options: [
                    '고양이 같은 하루였어. 살짝 귀찮지만 편안했어. 🐱',
                    '강아지 같은 하루였지. 사람 만나서 에너지 생겼거든. 🐶',
                    '곰 같은 하루였어. 포근하고 따뜻했어. 🐻',
                    '토끼 같은 하루였어. 가볍고 활발했지. 🐰',
                    '펭귄 같은 하루였어. 차분하고 안정적이었어. 🐧'
                ]
            },
            weather: {
                question: '오늘 하루는 어떤 날씨 같았어? ☀️',
                options: [
                    '맑은 하늘 같은 하루였어. 기분이 가볍더라. ☀️',
                    '흐린 날씨 같았어. 머릿속이 조금 무거웠거든. ☁️',
                    '비 오는 날 같은 하루였어. 차분하고 잔잔했어. 🌧️',
                    '바람 부는 날 같은 하루였지. 시원하고 상쾌했어. 💨',
                    '무지개 같은 하루였어. 다양한 일들이 있었거든. 🌈'
                ]
            },
            food: {
                question: '오늘 하루는 어떤 음식 같았어? 🍜',
                options: [
                    '따끈한 국밥 같은 하루였어. 든든했지. 🍲',
                    '매운 라면 같은 하루였어. 정신이 번쩍 들더라. 🍜',
                    '달콤한 케이크 같은 하루였어. 기분이 좋았거든. 🍰',
                    '시원한 아이스크림 같은 하루였어. 상쾌하고 시원했지. 🍦',
                    '고소한 팝콘 같은 하루였어. 가볍고 즐거웠어. 🍿'
                ]
            },
            color: {
                question: '오늘 하루는 어떤 색깔 같았어? 🎨',
                options: [
                    '파스텔 블루 같은 하루였어. 잔잔하고 안정적이었어. 💙',
                    '네온 옐로우 같은 하루였어. 반짝반짝했지. 💛',
                    '따뜻한 오렌지 같은 하루였어. 활기차고 밝았어. 🧡',
                    '차분한 그린 같은 하루였어. 평온하고 편안했거든. 💚',
                    '로즈 골드 같은 하루였어. 우아하고 따뜻했어. 🌹'
                ]
            },
            sound: {
                question: '오늘 하루는 어떤 소리/음악 같았어? 🎵',
                options: [
                    '잔잔한 피아노곡 같은 하루야. 차분하게 흘렀어. 🎹',
                    '드럼 비트 같은 하루였어. 리듬감 있게 바빴지. 🥁',
                    '바이올린 연주 같은 하루였어. 우아하고 감성적이었어. 🎻',
                    '기타 연주 같은 하루였어. 편안하고 자유로웠거든. 🎸',
                    '재즈 같은 하루였어. 자유롭고 즉흥적이었어. 🎷'
                ]
            },
            nature: {
                question: '오늘 하루는 어떤 식물/자연 같았어? 🌿',
                options: [
                    '새싹 같은 하루였어. 작은 시작이 있었거든. 🌱',
                    '단풍잎 같은 하루였지. 차분하게 정리됐어. 🍂',
                    '벚꽃 같은 하루였어. 아름답고 순간적이었어. 🌸',
                    '소나무 같은 하루였어. 안정적이고 견고했지. 🌲',
                    '해바라기 같은 하루였어. 밝고 긍정적이었어. 🌻'
                ]
            },
            transport: {
                question: '오늘 하루는 어떤 교통수단 같았어? 🚗',
                options: [
                    '고속도로 같은 하루였어. 일들이 술술 풀렸지. 🛣️',
                    '지하철 러시아워 같았어. 조금 꽉 막혀 있었어. 🚇',
                    '자전거 타는 하루였어. 적당히 빠르고 자유로웠어. 🚲',
                    '비행기 같은 하루였어. 높이 날아올랐거든. ✈️',
                    '유람선 같은 하루였어. 천천히 흘러갔어. 🚢'
                ]
            },
            game: {
                question: '오늘 하루는 어떤 게임 같았어? 🎮',
                options: [
                    '경험치 잘 먹은 하루였어. 뭔가 성장한 느낌? 📈',
                    '보스전 같은 하루였지. 빡세지만 보람 있었어. 👾',
                    '퀘스트 완료 같은 하루였어. 목표를 달성했거든. ✅',
                    '레벨업 같은 하루였어. 한 단계 올라간 기분이야. ⬆️',
                    '아이템 획득 같은 하루였어. 좋은 것들을 얻었지. 💎'
                ]
            },
            space: {
                question: '오늘 하루는 어떤 우주/천체 같았어? 🌌',
                options: [
                    '별빛 같은 하루였어. 작은 순간들이 반짝였지. ⭐',
                    '블랙홀 같은 하루였어. 정신없이 빨려 들어갔어. 🕳️',
                    '달 같은 하루였어. 차분하고 고요했거든. 🌙',
                    '태양 같은 하루였어. 밝고 따뜻했어. ☀️',
                    '은하수 같은 하루였어. 아름답고 신비로웠어. 🌌'
                ]
            },
            fashion: {
                question: '오늘 하루는 어떤 옷 같았어? 👕',
                options: [
                    '후드티 같은 하루였어. 편하고 느슨했지. 👕',
                    '정장 같은 하루였어. 집중하고 긴장했어. 👔',
                    '운동복 같은 하루였어. 활동적이고 편안했거든. 🏃',
                    '잠옷 같은 하루였어. 편안하고 푹 쉬었어. 😴',
                    '드레스 같은 하루였어. 우아하고 특별했어. 👗'
                ]
            },
            health: {
                question: '오늘 하루는 어떤 건강/컨디션 같았어? 💪',
                options: [
                    '스트레칭 같은 하루였어. 몸도 마음도 풀리더라. 🧘',
                    '마라톤 같은 하루였어. 끝나니 후련해. 🏃',
                    '요가 같은 하루였어. 차분하고 평온했거든. 🧘‍♀️',
                    '헬스 같은 하루였어. 에너지 넘쳤어. 💪',
                    '스파 같은 하루였어. 편안하고 힐링됐어. 💆'
                ]
            },
            // 추가 테마 48개 (총 60개)
            book: {
                question: '오늘 하루는 어떤 책 같았어? 📚',
                options: [
                    '판타지 소설 같은 하루였어. 상상력이 풍부했거든. 🧙',
                    '추리 소설 같은 하루였어. 문제를 하나씩 풀어갔어. 🔍',
                    '자기계발서 같은 하루였어. 뭔가 배운 게 많았어. 📖',
                    '만화책 같은 하루였어. 가볍고 재미있었지. 📗',
                    '시집 같은 하루였어. 감성적이고 아름다웠어. 📝'
                ]
            },
            movie: {
                question: '오늘 하루는 어떤 영화 같았어? 🎬',
                options: [
                    '액션 영화 같은 하루였어. 스릴 넘쳤어. 💥',
                    '로맨스 영화 같은 하루였어. 따뜻하고 감성적이었어. 💕',
                    '코미디 영화 같은 하루였어. 웃음이 많았지. 😂',
                    '스릴러 영화 같은 하루였어. 긴장감이 있었어. 😱',
                    '다큐멘터리 같은 하루였어. 배운 게 많았어. 📺'
                ]
            },
            season: {
                question: '오늘 하루는 어떤 계절 같았어? 🍂',
                options: [
                    '봄 같은 하루였어. 새롭고 신선했어. 🌸',
                    '여름 같은 하루였어. 뜨겁고 활기찼어. ☀️',
                    '가을 같은 하루였어. 차분하고 여유로웠어. 🍁',
                    '겨울 같은 하루였어. 차갑지만 깨끗했어. ❄️',
                    '환절기 같은 하루였어. 변화가 많았어. 🌦️'
                ]
            },
            drink: {
                question: '오늘 하루는 어떤 음료 같았어? 🥤',
                options: [
                    '아메리카노 같은 하루였어. 깔끔하고 집중됐어. ☕',
                    '라떼 같은 하루였어. 부드럽고 편안했어. 🥛',
                    '에너지 드링크 같은 하루였어. 에너지 넘쳤어. ⚡',
                    '차 같은 하루였어. 차분하고 여유로웠어. 🍵',
                    '주스 같은 하루였어. 상큼하고 활기찼어. 🧃'
                ]
            },
            emotion: {
                question: '오늘 하루는 어떤 감정 같았어? 😊',
                options: [
                    '기쁨 같은 하루였어. 즐거움이 많았어. 😄',
                    '평온 같은 하루였어. 차분하고 안정적이었어. 😌',
                    '설렘 같은 하루였어. 뭔가 기대되는 일이 있었어. 🥰',
                    '만족 같은 하루였어. 뿌듯하고 보람 있었어. 😊',
                    '평범 같은 하루였어. 특별한 건 없었지만 괜찮았어. 😐'
                ]
            },
            study: {
                question: '오늘 하루는 어떤 공부 같았어? 📝',
                options: [
                    '개념 정리 같은 하루였어. 머릿속이 정리됐어. 📚',
                    '문제 풀이 같은 하루였어. 실전 연습했어. ✏️',
                    '복습 같은 하루였어. 다시 한번 확인했어. 🔄',
                    '예습 같은 하루였어. 새로운 걸 배웠어. 🆕',
                    '시험 준비 같은 하루였어. 긴장감 있었어. 📋'
                ]
            },
            time: {
                question: '오늘 하루는 어떤 시간대 같았어? ⏰',
                options: [
                    '아침 같은 하루였어. 상쾌하고 맑았어. 🌅',
                    '점심 같은 하루였어. 활기차고 바빴어. ☀️',
                    '저녁 같은 하루였어. 차분하고 여유로웠어. 🌆',
                    '밤 같은 하루였어. 고요하고 집중됐어. 🌙',
                    '새벽 같은 하루였어. 조용하고 깊었어. 🌃'
                ]
            },
            place: {
                question: '오늘 하루는 어떤 장소 같았어? 🏠',
                options: [
                    '도서관 같은 하루였어. 조용하고 집중됐어. 📚',
                    '카페 같은 하루였어. 편안하고 여유로웠어. ☕',
                    '운동장 같은 하루였어. 활기차고 움직였어. 🏃',
                    '집 같은 하루였어. 편안하고 안정적이었어. 🏡',
                    '학교 같은 하루였어. 규칙적이고 바빴어. 🏫'
                ]
            },
            music_genre: {
                question: '오늘 하루는 어떤 음악 장르 같았어? 🎵',
                options: [
                    '팝송 같은 하루였어. 경쾌하고 즐거웠어. 🎤',
                    '클래식 같은 하루였어. 우아하고 차분했어. 🎼',
                    '록 같은 하루였어. 강렬하고 에너지 넘쳤어. 🎸',
                    '재즈 같은 하루였어. 자유롭고 즉흥적이었어. 🎷',
                    '발라드 같은 하루였어. 감성적이고 부드러웠어. 🎹'
                ]
            },
            sport: {
                question: '오늘 하루는 어떤 운동 같았어? ⚽',
                options: [
                    '축구 같은 하루였어. 팀워크가 중요했어. ⚽',
                    '수영 같은 하루였어. 차분하고 집중됐어. 🏊',
                    '요가 같은 하루였어. 평온하고 안정적이었어. 🧘',
                    '달리기 같은 하루였어. 지속적이고 꾸준했어. 🏃',
                    '볼링 같은 하루였어. 집중하고 정확했어. 🎳'
                ]
            },
            hobby: {
                question: '오늘 하루는 어떤 취미 같았어? 🎨',
                options: [
                    '그림 그리기 같은 하루였어. 창의적이었어. 🎨',
                    '독서 같은 하루였어. 조용하고 깊었어. 📖',
                    '게임 같은 하루였어. 재미있고 즐거웠어. 🎮',
                    '요리 같은 하루였어. 창의적이고 만족스러웠어. 👨‍🍳',
                    '사진 같은 하루였어. 순간을 담았어. 📷'
                ]
            },
            weather_detail: {
                question: '오늘 하루는 어떤 날씨 같았어? 🌤️',
                options: [
                    '맑음 같은 하루였어. 깔끔하고 밝았어. ☀️',
                    '흐림 같은 하루였어. 차분하고 부드러웠어. ☁️',
                    '비 같은 하루였어. 차분하고 잔잔했어. 🌧️',
                    '눈 같은 하루였어. 깨끗하고 고요했어. ❄️',
                    '바람 같은 하루였어. 시원하고 상쾌했어. 💨'
                ]
            },
            texture: {
                question: '오늘 하루는 어떤 질감 같았어? ✨',
                options: [
                    '부드러운 하루였어. 편안하고 안정적이었어. 🪶',
                    '딱딱한 하루였어. 확실하고 명확했어. 🪨',
                    '미끄러운 하루였어. 빠르고 순조로웠어. 🧊',
                    '거친 하루였어. 도전적이었어. 🌵',
                    '매끄러운 하루였어. 완벽하고 깔끔했어. 💎'
                ]
            },
            speed: {
                question: '오늘 하루는 어떤 속도 같았어? 🚀',
                options: [
                    '빠른 하루였어. 바쁘고 활기찼어. ⚡',
                    '보통 하루였어. 적당한 속도였어. 🚶',
                    '느린 하루였어. 여유롭고 차분했어. 🐢',
                    '변화하는 하루였어. 때로 빠르고 때로 느렸어. 🎢',
                    '정지한 하루였어. 멈춰서 쉬었어. ⏸️'
                ]
            },
            temperature: {
                question: '오늘 하루는 어떤 온도 같았어? 🌡️',
                options: [
                    '따뜻한 하루였어. 포근하고 편안했어. 🔥',
                    '시원한 하루였어. 상쾌하고 깔끔했어. ❄️',
                    '뜨거운 하루였어. 열정적이었어. 🌋',
                    '차가운 하루였어. 차분하고 냉정했어. 🧊',
                    '미지근한 하루였어. 적당하고 편안했어. 🌡️'
                ]
            },
            size: {
                question: '오늘 하루는 어떤 크기 같았어? 📏',
                options: [
                    '큰 하루였어. 많은 일이 있었어. 📦',
                    '작은 하루였어. 소소하지만 의미 있었어. 📍',
                    '넓은 하루였어. 다양한 경험이 있었어. 🌍',
                    '좁은 하루였어. 집중하고 깊었어. 🔍',
                    '적당한 하루였어. 알맞고 편안했어. 📐'
                ]
            },
            shape: {
                question: '오늘 하루는 어떤 모양 같았어? 🔷',
                options: [
                    '원 같은 하루였어. 완벽하고 순환했어. ⭕',
                    '사각형 같은 하루였어. 규칙적이고 정돈됐어. ⬜',
                    '삼각형 같은 하루였어. 뾰족하고 집중됐어. 🔺',
                    '별 같은 하루였어. 반짝이고 특별했어. ⭐',
                    '구름 같은 하루였어. 부드럽고 자유로웠어. ☁️'
                ]
            },
            material: {
                question: '오늘 하루는 어떤 재료 같았어? 🧱',
                options: [
                    '나무 같은 하루였어. 자연스럽고 따뜻했어. 🌳',
                    '금속 같은 하루였어. 강하고 확실했어. ⚙️',
                    '유리 같은 하루였어. 투명하고 깨끗했어. 💎',
                    '천 같은 하루였어. 부드럽고 편안했어. 🧵',
                    '플라스틱 같은 하루였어. 가볍고 실용적이었어. 🪣'
                ]
            },
            smell: {
                question: '오늘 하루는 어떤 냄새 같았어? 👃',
                options: [
                    '꽃향기 같은 하루였어. 향기롭고 아름다웠어. 🌸',
                    '커피향 같은 하루였어. 깊고 진했어. ☕',
                    '바다 냄새 같은 하루였어. 시원하고 상쾌했어. 🌊',
                    '빵 냄새 같은 하루였어. 따뜻하고 포근했어. 🍞',
                    '비 냄새 같은 하루였어. 깨끗하고 신선했어. 🌧️'
                ]
            },
            taste: {
                question: '오늘 하루는 어떤 맛 같았어? 👅',
                options: [
                    '달콤한 하루였어. 즐겁고 행복했어. 🍭',
                    '쓴 하루였어. 힘들었지만 배운 게 있었어. ☕',
                    '신 맛 같은 하루였어. 새롭고 상큼했어. 🍋',
                    '짠 맛 같은 하루였어. 자극적이고 강렬했어. 🧂',
                    '고소한 하루였어. 만족스럽고 든든했어. 🥜'
                ]
            },
            light: {
                question: '오늘 하루는 어떤 빛 같았어? 💡',
                options: [
                    '밝은 하루였어. 환하고 긍정적이었어. ☀️',
                    '어두운 하루였어. 차분하고 집중됐어. 🌑',
                    '반짝이는 하루였어. 특별한 순간들이 있었어. ✨',
                    '부드러운 빛 같은 하루였어. 편안하고 따뜻했어. 🕯️',
                    '네온사인 같은 하루였어. 화려하고 눈에 띄었어. 💡'
                ]
            },
            movement: {
                question: '오늘 하루는 어떤 움직임 같았어? 🏃',
                options: [
                    '뛰는 하루였어. 빠르고 활기찼어. 🏃',
                    '걷는 하루였어. 차분하고 꾸준했어. 🚶',
                    '날아가는 하루였어. 높이 올라갔어. ✈️',
                    '수영하는 하루였어. 부드럽고 흐르듯 했어. 🏊',
                    '정지한 하루였어. 멈춰서 쉬었어. ⏸️'
                ]
            },
            energy: {
                question: '오늘 하루는 어떤 에너지 같았어? ⚡',
                options: [
                    '높은 에너지 하루였어. 활기차고 바빴어. 🔥',
                    '중간 에너지 하루였어. 적당하고 편안했어. ⚡',
                    '낮은 에너지 하루였어. 차분하고 여유로웠어. 🕯️',
                    '변동하는 에너지 하루였어. 때로 높고 때로 낮았어. 📈',
                    '균형잡힌 에너지 하루였어. 안정적이었어. ⚖️'
                ]
            },
            density: {
                question: '오늘 하루는 어떤 밀도 같았어? 📊',
                options: [
                    '높은 밀도 하루였어. 많은 일이 압축됐어. 📦',
                    '낮은 밀도 하루였어. 여유롭고 넓었어. 🌊',
                    '균일한 밀도 하루였어. 일정하고 안정적이었어. 📏',
                    '불균일한 밀도 하루였어. 때로 빽빽하고 때로 넓었어. 📈',
                    '적당한 밀도 하루였어. 알맞고 편안했어. ⚖️'
                ]
            },
            rhythm: {
                question: '오늘 하루는 어떤 리듬 같았어? 🥁',
                options: [
                    '빠른 리듬 하루였어. 템포가 빨랐어. ⚡',
                    '느린 리듬 하루였어. 여유롭고 차분했어. 🐢',
                    '규칙적인 리듬 하루였어. 일정하고 안정적이었어. ⏰',
                    '불규칙한 리듬 하루였어. 변화가 많았어. 🎵',
                    '조용한 리듬 하루였어. 평온하고 고요했어. 🤫'
                ]
            },
            pattern: {
                question: '오늘 하루는 어떤 패턴 같았어? 🔄',
                options: [
                    '반복 패턴 하루였어. 규칙적이었어. 🔁',
                    '변화 패턴 하루였어. 다양했어. 🔀',
                    '선형 패턴 하루였어. 순차적으로 진행됐어. ➡️',
                    '순환 패턴 하루였어. 돌고 돌았어. 🔄',
                    '무작위 패턴 하루였어. 예측 불가능했어. 🎲'
                ]
            },
            connection: {
                question: '오늘 하루는 어떤 연결 같았어? 🔗',
                options: [
                    '강한 연결 하루였어. 사람들과 깊게 소통했어. 🤝',
                    '약한 연결 하루였어. 혼자만의 시간이었어. 🧘',
                    '다양한 연결 하루였어. 많은 사람 만났어. 👥',
                    '깊은 연결 하루였어. 의미 있는 대화가 있었어. 💬',
                    '표면적 연결 하루였어. 가볍고 편안했어. 👋'
                ]
            },
            depth: {
                question: '오늘 하루는 어떤 깊이 같았어? 🌊',
                options: [
                    '깊은 하루였어. 깊이 생각하고 느꼈어. 🌊',
                    '얕은 하루였어. 가볍고 편안했어. 💧',
                    '중간 깊이 하루였어. 적당히 깊었어. 🏊',
                    '변화하는 깊이 하루였어. 때로 깊고 때로 얕았어. 🌊',
                    '균일한 깊이 하루였어. 일정했어. 📏'
                ]
            },
            complexity: {
                question: '오늘 하루는 어떤 복잡도 같았어? 🧩',
                options: [
                    '복잡한 하루였어. 많은 일이 얽혔어. 🧩',
                    '단순한 하루였어. 깔끔하고 명확했어. ⚪',
                    '중간 복잡도 하루였어. 적당히 복잡했어. 🔷',
                    '변화하는 복잡도 하루였어. 때로 복잡하고 때로 단순했어. 📊',
                    '균형잡힌 복잡도 하루였어. 적당했어. ⚖️'
                ]
            },
            clarity: {
                question: '오늘 하루는 어떤 명확도 같았어? 🔍',
                options: [
                    '명확한 하루였어. 뭐든 확실했어. ✅',
                    '모호한 하루였어. 애매하고 불확실했어. ❓',
                    '부분적 명확도 하루였어. 일부는 확실했어. 🔷',
                    '변화하는 명확도 하루였어. 때로 명확하고 때로 모호했어. 📈',
                    '균형잡힌 명확도 하루였어. 적당했어. ⚖️'
                ]
            },
            focus: {
                question: '오늘 하루는 어떤 집중도 같았어? 🎯',
                options: [
                    '높은 집중 하루였어. 깊이 몰입했어. 🎯',
                    '낮은 집중 하루였어. 산만했어. 🌊',
                    '중간 집중 하루였어. 적당히 집중했어. ⚡',
                    '변화하는 집중 하루였어. 때로 높고 때로 낮았어. 📈',
                    '균형잡힌 집중 하루였어. 안정적이었어. ⚖️'
                ]
            },
            balance: {
                question: '오늘 하루는 어떤 균형 같았어? ⚖️',
                options: [
                    '완벽한 균형 하루였어. 모든 게 알맞았어. ⚖️',
                    '불균형 하루였어. 한쪽으로 치우쳤어. 📊',
                    '부분적 균형 하루였어. 일부는 균형 잡혔어. 🔷',
                    '변화하는 균형 하루였어. 때로 균형 잡히고 때로 안 잡혔어. 📈',
                    '균형 회복 하루였어. 다시 맞춰졌어. 🔄'
                ]
            },
            flow: {
                question: '오늘 하루는 어떤 흐름 같았어? 🌊',
                options: [
                    '매끄러운 흐름 하루였어. 순조로웠어. 🌊',
                    '막힌 흐름 하루였어. 막혔어. 🚧',
                    '빠른 흐름 하루였어. 급하게 흘렀어. ⚡',
                    '느린 흐름 하루였어. 천천히 흘렀어. 🐢',
                    '변화하는 흐름 하루였어. 때로 빠르고 때로 느렸어. 📈'
                ]
            },
            challenge: {
                question: '오늘 하루는 어떤 도전 같았어? 🏔️',
                options: [
                    '큰 도전 하루였어. 어려웠지만 보람 있었어. 🏔️',
                    '작은 도전 하루였어. 가볍고 쉬웠어. 🗻',
                    '중간 도전 하루였어. 적당히 어려웠어. ⛰️',
                    '새로운 도전 하루였어. 처음 해봤어. 🆕',
                    '성공한 도전 하루였어. 극복했어. ✅'
                ]
            },
            achievement: {
                question: '오늘 하루는 어떤 성취 같았어? 🏆',
                options: [
                    '큰 성취 하루였어. 대단한 걸 이뤘어. 🏆',
                    '작은 성취 하루였어. 소소하지만 의미 있었어. 🎖️',
                    '누적 성취 하루였어. 조금씩 쌓였어. 📈',
                    '예상치 못한 성취 하루였어. 뜻밖의 성과가 있었어. 🎁',
                    '성취 없음 하루였어. 쉬었어. 😴'
                ]
            },
            learning: {
                question: '오늘 하루는 어떤 학습 같았어? 📚',
                options: [
                    '새로운 학습 하루였어. 처음 배웠어. 🆕',
                    '복습 학습 하루였어. 다시 확인했어. 🔄',
                    '심화 학습 하루였어. 깊이 파봤어. 🔍',
                    '실전 학습 하루였어. 직접 해봤어. ✏️',
                    '정리 학습 하루였어. 머릿속 정리했어. 📋'
                ]
            },
            interaction: {
                question: '오늘 하루는 어떤 상호작용 같았어? 👥',
                options: [
                    '많은 상호작용 하루였어. 사람들과 많이 만났어. 👥',
                    '적은 상호작용 하루였어. 혼자였어. 🧘',
                    '깊은 상호작용 하루였어. 의미 있는 대화가 있었어. 💬',
                    '가벼운 상호작용 하루였어. 편안하게 만났어. 👋',
                    '다양한 상호작용 하루였어. 여러 사람 만났어. 🌐'
                ]
            },
            space_feeling: {
                question: '오늘 하루는 어떤 공간감 같았어? 🏛️',
                options: [
                    '넓은 공간 하루였어. 여유로웠어. 🌍',
                    '좁은 공간 하루였어. 집중됐어. 🔍',
                    '개방된 공간 하루였어. 자유로웠어. 🌅',
                    '폐쇄된 공간 하루였어. 안전했어. 🏠',
                    '변화하는 공간 하루였어. 때로 넓고 때로 좁았어. 📐'
                ]
            },
            time_feeling: {
                question: '오늘 하루는 어떤 시간감 같았어? ⏳',
                options: [
                    '빠르게 지나간 하루였어. 시간이 금방 갔어. ⚡',
                    '느리게 지나간 하루였어. 시간이 길었어. 🐢',
                    '적당히 지나간 하루였어. 알맞았어. ⏰',
                    '멈춘 것 같은 하루였어. 시간이 안 갔어. ⏸️',
                    '변화하는 시간감 하루였어. 때로 빠르고 때로 느렸어. 📈'
                ]
            },
            satisfaction: {
                question: '오늘 하루는 어떤 만족도 같았어? 😊',
                options: [
                    '매우 만족한 하루였어. 완벽했어. 😊',
                    '만족한 하루였어. 좋았어. 🙂',
                    '보통 하루였어. 그냥 그래. 😐',
                    '불만족한 하루였어. 아쉬웠어. 😕',
                    '혼재된 하루였어. 만족스러운 부분도 있고 아쉬운 부분도 있었어. 🤔'
                ]
            },
            surprise: {
                question: '오늘 하루는 어떤 놀라움 같았어? 🎉',
                options: [
                    '놀라운 하루였어. 예상치 못한 일이 있었어. 🎉',
                    '예상대로인 하루였어. 계획대로 됐어. ✅',
                    '부분적 놀라움 하루였어. 일부는 예상 밖이었어. 🎁',
                    '작은 놀라움 하루였어. 소소한 일이 있었어. 🎈',
                    '놀라움 없음 하루였어. 평범했어. 📅'
                ]
            },
            growth: {
                question: '오늘 하루는 어떤 성장 같았어? 🌱',
                options: [
                    '큰 성장 하루였어. 많이 발전했어. 🌳',
                    '작은 성장 하루였어. 조금씩 발전했어. 🌱',
                    '누적 성장 하루였어. 쌓여갔어. 📈',
                    '새로운 성장 하루였어. 처음 배웠어. 🆕',
                    '성장 없음 하루였어. 유지했어. ⏸️'
                ]
            },
            rest: {
                question: '오늘 하루는 어떤 휴식 같았어? 😴',
                options: [
                    '충분한 휴식 하루였어. 많이 쉬었어. 😴',
                    '부족한 휴식 하루였어. 바빴어. ⚡',
                    '적당한 휴식 하루였어. 알맞게 쉬었어. 🛋️',
                    '활동적 휴식 하루였어. 쉬면서도 뭔가 했어. 🏃',
                    '휴식 없음 하루였어. 쉬지 못했어. 🔥'
                ]
            },
            creativity: {
                question: '오늘 하루는 어떤 창의성 같았어? 🎨',
                options: [
                    '높은 창의성 하루였어. 창의적이었어. 🎨',
                    '낮은 창의성 하루였어. 규칙적이었어. 📋',
                    '부분적 창의성 하루였어. 일부는 창의적이었어. 🔷',
                    '새로운 창의성 하루였어. 처음 시도했어. 🆕',
                    '창의성 없음 하루였어. 기계적이었어. ⚙️'
                ]
            },
            organization: {
                question: '오늘 하루는 어떤 정리 같았어? 📋',
                options: [
                    '완벽한 정리 하루였어. 모든 게 정돈됐어. ✅',
                    '부분적 정리 하루였어. 일부만 정리했어. 🔷',
                    '정리 없음 하루였어. 산만했어. 🌊',
                    '새로운 정리 하루였어. 처음 정리했어. 🆕',
                    '재정리 하루였어. 다시 정리했어. 🔄'
                ]
            },
            adventure: {
                question: '오늘 하루는 어떤 모험 같았어? 🗺️',
                options: [
                    '큰 모험 하루였어. 도전적이었어. 🏔️',
                    '작은 모험 하루였어. 가벼운 도전이었어. 🗻',
                    '새로운 모험 하루였어. 처음 해봤어. 🆕',
                    '예상치 못한 모험 하루였어. 뜻밖의 일이 있었어. 🎁',
                    '모험 없음 하루였어. 안전했어. 🏠'
                ]
            },
            comfort: {
                question: '오늘 하루는 어떤 편안함 같았어? 🛋️',
                options: [
                    '매우 편안한 하루였어. 완전히 편했어. 🛋️',
                    '편안한 하루였어. 괜찮았어. 😌',
                    '불편한 하루였어. 불편했어. 😣',
                    '부분적 편안함 하루였어. 일부는 편했어. 🔷',
                    '편안함 없음 하루였어. 긴장했어. 😰'
                ]
            },
            intensity: {
                question: '오늘 하루는 어떤 강도 같았어? 💪',
                options: [
                    '높은 강도 하루였어. 강렬했어. 💪',
                    '낮은 강도 하루였어. 부드러웠어. 🪶',
                    '중간 강도 하루였어. 적당했어. ⚡',
                    '변화하는 강도 하루였어. 때로 강하고 때로 약했어. 📈',
                    '균형잡힌 강도 하루였어. 안정적이었어. ⚖️'
                ]
            },
            novelty: {
                question: '오늘 하루는 어떤 새로움 같았어? 🆕',
                options: [
                    '매우 새로운 하루였어. 완전히 새로웠어. 🆕',
                    '새로운 하루였어. 처음 해봤어. ✨',
                    '익숙한 하루였어. 반복적이었어. 🔁',
                    '부분적 새로움 하루였어. 일부는 새로웠어. 🔷',
                    '새로움 없음 하루였어. 똑같았어. 📅'
                ]
            },
            tradition: {
                question: '오늘 하루는 어떤 전통 같았어? 🏛️',
                options: [
                    '전통적인 하루였어. 규칙적이었어. 🏛️',
                    '비전통적인 하루였어. 자유로웠어. 🆕',
                    '부분적 전통 하루였어. 일부는 전통적이었어. 🔷',
                    '새로운 전통 하루였어. 새로운 규칙을 만들었어. ✨',
                    '전통 없음 하루였어. 자유로웠어. 🕊️'
                ]
            },
            freedom: {
                question: '오늘 하루는 어떤 자유 같았어? 🕊️',
                options: [
                    '매우 자유로운 하루였어. 완전히 자유로웠어. 🕊️',
                    '자유로운 하루였어. 편했어. 🦅',
                    '제한된 하루였어. 규칙이 많았어. 📋',
                    '부분적 자유 하루였어. 일부는 자유로웠어. 🔷',
                    '자유 없음 하루였어. 구속됐어. 🔒'
                ]
            },
            structure: {
                question: '오늘 하루는 어떤 구조 같았어? 🏗️',
                options: [
                    '명확한 구조 하루였어. 잘 정리됐어. 🏗️',
                    '불명확한 구조 하루였어. 산만했어. 🌊',
                    '부분적 구조 하루였어. 일부만 정리됐어. 🔷',
                    '새로운 구조 하루였어. 처음 정리했어. 🆕',
                    '구조 없음 하루였어. 무질서했어. 🌀'
                ]
            },
            chaos: {
                question: '오늘 하루는 어떤 혼란 같았어? 🌀',
                options: [
                    '혼란스러운 하루였어. 정신없었어. 🌀',
                    '정돈된 하루였어. 깔끔했어. ✅',
                    '부분적 혼란 하루였어. 일부는 혼란스러웠어. 🔷',
                    '예상치 못한 혼란 하루였어. 뜻밖의 일이 있었어. 🎁',
                    '혼란 없음 하루였어. 평온했어. 😌'
                ]
            },
            order: {
                question: '오늘 하루는 어떤 질서 같았어? 📐',
                options: [
                    '완벽한 질서 하루였어. 모든 게 정돈됐어. 📐',
                    '무질서한 하루였어. 산만했어. 🌀',
                    '부분적 질서 하루였어. 일부만 정리됐어. 🔷',
                    '새로운 질서 하루였어. 처음 정리했어. 🆕',
                    '질서 없음 하루였어. 자유로웠어. 🕊️'
                ]
            },
            mystery: {
                question: '오늘 하루는 어떤 신비 같았어? 🔮',
                options: [
                    '신비로운 하루였어. 예상치 못한 일이 있었어. 🔮',
                    '명확한 하루였어. 모든 게 확실했어. ✅',
                    '부분적 신비 하루였어. 일부는 불명확했어. 🔷',
                    '새로운 신비 하루였어. 처음 경험했어. 🆕',
                    '신비 없음 하루였어. 평범했어. 📅'
                ]
            },
            predictability: {
                question: '오늘 하루는 어떤 예측가능성 같았어? 🔮',
                options: [
                    '예측 가능한 하루였어. 계획대로 됐어. 📅',
                    '예측 불가능한 하루였어. 뜻밖의 일이 있었어. 🎲',
                    '부분적 예측 가능 하루였어. 일부는 예상대로였어. 🔷',
                    '새로운 예측 하루였어. 처음 계획했어. 🆕',
                    '예측 없음 하루였어. 즉흥적이었어. 🎭'
                ]
            },
            spontaneity: {
                question: '오늘 하루는 어떤 즉흥성 같았어? 🎭',
                options: [
                    '매우 즉흥적인 하루였어. 계획 없이 흘러갔어. 🎭',
                    '계획적인 하루였어. 계획대로 됐어. 📋',
                    '부분적 즉흥 하루였어. 일부는 즉흥적이었어. 🔷',
                    '새로운 즉흥 하루였어. 처음 즉흥적으로 했어. 🆕',
                    '즉흥 없음 하루였어. 규칙적이었어. ⏰'
                ]
            },
            control: {
                question: '오늘 하루는 어떤 통제 같았어? 🎮',
                options: [
                    '완전한 통제 하루였어. 모든 걸 컨트롤했어. 🎮',
                    '통제 없음 하루였어. 자유로웠어. 🕊️',
                    '부분적 통제 하루였어. 일부만 컨트롤했어. 🔷',
                    '새로운 통제 하루였어. 처음 계획했어. 🆕',
                    '통제 실패 하루였어. 계획이 어긋났어. ❌'
                ]
            },
            surrender: {
                question: '오늘 하루는 어떤 포기 같았어? 🤲',
                options: [
                    '포기한 하루였어. 그냥 흘러갔어. 🤲',
                    '집착한 하루였어. 끝까지 해냈어. 💪',
                    '부분적 포기 하루였어. 일부는 포기했어. 🔷',
                    '새로운 포기 하루였어. 처음 포기했어. 🆕',
                    '포기 없음 하루였어. 완주했어. ✅'
                ]
            },
            effort: {
                question: '오늘 하루는 어떤 노력 같았어? 💪',
                options: [
                    '큰 노력 하루였어. 정말 열심히 했어. 💪',
                    '작은 노력 하루였어. 가볍게 했어. 🪶',
                    '중간 노력 하루였어. 적당히 했어. ⚡',
                    '변화하는 노력 하루였어. 때로 열심히 하고 때로 쉬었어. 📈',
                    '노력 없음 하루였어. 쉬었어. 😴'
                ]
            },
            ease: {
                question: '오늘 하루는 어떤 쉬움 같았어? 🪶',
                options: [
                    '매우 쉬운 하루였어. 수월했어. 🪶',
                    '어려운 하루였어. 힘들었어. 💪',
                    '중간 난이도 하루였어. 적당했어. ⚡',
                    '변화하는 난이도 하루였어. 때로 쉬고 때로 어려웠어. 📈',
                    '균형잡힌 난이도 하루였어. 안정적이었어. ⚖️'
                ]
            }
        };
        
        // 시간 기반으로 테마 선택 (로딩 시점의 초를 60으로 나눈 나머지 사용)
        function getRandomDailyMoodTheme() {
            const themeKeys = Object.keys(dailyMoodThemes);
            // 로딩 시점의 초(second)를 60으로 나눈 나머지(0-59)로 질문 선택
            const currentSecond = new Date().getSeconds();
            const themeIndex = currentSecond % 60; // 0-59 범위
            // 인덱스가 질문 개수보다 크면 질문 개수로 나눈 나머지 사용
            const safeIndex = themeIndex % themeKeys.length;
            return dailyMoodThemes[themeKeys[safeIndex]];
        }
        
        // 필수 질문
        const requiredQuestions = [
            {
                id: 'calmness',
                text: '오늘 수업 중 침착도는 어땠어?',
                options: [], // 동적으로 생성됨
                hasData: true,
                isQuiz: true, // 퀴즈 형식임을 표시
                correctAnswer: null, // 동적으로 설정됨
                followUp: {
                    'correct': '정답이야! 👍 자신의 침착도를 정확하게 알고 있네? 대단해!',
                    'incorrect': '아니야~ 😅 실제로는 다른 등급이야. 다시 확인해볼까?'
                }
            },
            {
                id: 'math_diary_count',
                text: '오늘 수학일기 작성 갯수는?',
                options: [], // 동적으로 생성됨
                hasData: true,
                isQuiz: true, // 퀴즈 형식임을 표시
                correctAnswer: null, // 동적으로 설정됨
                followUp: {
                    'correct': '정답이야! 👍 자신의 수학일기 작성 개수를 정확하게 알고 있네? 대단해!',
                    'incorrect': '아니야~ 😅 실제로는 다른 개수야. 다시 확인해볼까?'
                }
            },
            {
                id: 'pomodoro_summary',
                text: '포모도르 수학일기',
                options: [], // 동적으로 생성됨
                hasData: true,
                isSummary: true // 통합 페이지임을 표시
            },
            {
                id: 'error_note',
                text: '오답노트는 밀리지 않았어?',
                options: [], // 동적으로 생성됨
                hasData: true,
                isQuiz: true, // 퀴즈 형식임을 표시
                correctAnswer: null, // 동적으로 설정됨
                followUp: {
                    'correct': '정답이야! 👍 정확하게 알고 있네? 오답노트 관리 잘하고 있어!',
                    'incorrect': '아니야~ 😅 실제로는 다른 개수야. 다시 확인해볼까?'
                }
            }
        ];
        
        // 랜덤 질문 풀 (사용하지 않음 - OpenAI API로 대체)
        const randomQuestionPool = {};
        
        // 타이핑 효과
        function typeText(elementId, text, callback) {
            if (typingTimeout) {
                clearTimeout(typingTimeout);
            }
            
            const element = document.getElementById(elementId);
            element.innerHTML = '';
            let index = 0;
            
            function typeNextChar() {
                if (index < text.length) {
                    element.innerHTML += text[index];
                    index++;
                    typingTimeout = setTimeout(typeNextChar, 30);
                } else {
                    element.innerHTML += '<span class="typing-cursor">|</span>';
                    setTimeout(() => {
                        const cursor = element.querySelector('.typing-cursor');
                        if (cursor) cursor.remove();
                        if (callback) callback();
                    }, 500);
                }
            }
            
            typeNextChar();
        }
        
        // 아바타 애니메이션
        function triggerAvatarAnimation(animation) {
            const avatar = document.getElementById('avatar');
            avatar.classList.remove('wave', 'talk', 'celebrate');
            setTimeout(() => {
                avatar.classList.add(animation);
                setTimeout(() => {
                    avatar.classList.remove(animation);
                }, 2000);
            }, 10);
        }
        
        // 랜덤 질문 선택 (상관관계 고려)
        async function generateRandomQuestions() {
            const topicKeys = Object.keys(selectedTopics);
            
            // 랜덤 질문이 없으면 빈 배열 반환
            if (topicKeys.length === 0) {
                selectedRandomQuestions = [];
                return;
            }
            
            // 모든 API 호출을 병렬로 처리
            const questionPromises = topicKeys.map(async (topicKey) => {
                try {
                    const formData = new FormData();
                    formData.append('action', 'generate_new_question');
                    formData.append('topic', topicKey);
                    formData.append('topic_description', selectedTopics[topicKey]);
                    formData.append('previous_responses', JSON.stringify(responses));
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success && data.question && data.options) {
                        // 옵션을 정확히 5개로 제한 (5지선다)
                        let options = data.options;
                        if (options.length > 5) {
                            options = options.slice(0, 5);
                        } else if (options.length < 5 && options.length > 0) {
                            // 부족한 경우 기본 옵션 추가
                            const defaultOptions = ['완벽했어!', '괜찮았어', '보통이야', '조금 아쉬웠어', '별로였어'];
                            const needed = 5 - options.length;
                            options = options.concat(defaultOptions.slice(0, needed));
                        }
                        
                        return {
                            id: topicKey,
                            text: data.question,
                            options: options.length === 5 ? options : getDefaultQuestionForTopic(topicKey).options,
                            category: getCategoryForTopic(topicKey)
                        };
                    } else {
                        // 폴백: 기본 질문 사용
                        return getDefaultQuestionForTopic(topicKey);
                    }
                } catch (error) {
                    console.error('Failed to generate question for topic:', topicKey, error);
                    // 폴백: 기본 질문 사용
                    return getDefaultQuestionForTopic(topicKey);
                }
            });
            
            // 모든 Promise가 완료될 때까지 기다림
            const selected = await Promise.all(questionPromises);
            selectedRandomQuestions = selected;
        }
        
        // 주제별 카테고리 매핑 (수학학원 수업 상황 반영)
        function getCategoryForTopic(topic) {
            const categoryMap = {
                'weekly_goal': 'planning',
                'math_diary': 'process',
                'problem_count': 'process',
                'concept_study': 'process',
                'error_note': 'process',
                'test': 'process',
                'review': 'process',
                'face_evaluation': 'interaction',
                'tts_listening': 'process',
                'questions_asked': 'interaction',
                'rest_pattern': 'focus',
                'satisfaction': 'emotion',
                'boredom': 'emotion',
                'stress_level': 'emotion',
                'unsaid_words': 'interaction',
                'study_amount': 'focus',
                'difficulty_level': 'process',
                'pace_anxiety': 'planning',
                'self_improvement': 'reflection',
                'positive_moment': 'emotion',
                'missed_opportunity': 'reflection',
                'intuition_solving': 'reflection',
                'forced_solving': 'reflection',
                'easy_problems': 'process',
                'long_problem': 'focus',
                'daily_plan': 'planning',
                'inefficiency': 'focus'
            };
            return categoryMap[topic] || 'process';
        }
        
        // 폴백용 기본 질문 (모두 5지선다, 수학학원 수업 상황 반영)
        function getDefaultQuestionForTopic(topic) {
            const defaults = {
                'weekly_goal': {
                    id: 'weekly_goal',
                    text: '주간목표를 확인하고 오늘 목표를 정했어?',
                    options: ['네, 완벽하게 확인했어요', '네, 확인했어요', '대충 확인했어요', '깜빡했어요', '목표가 애매해요'],
                    category: 'planning'
                },
                'math_diary': {
                    id: 'math_diary',
                    text: '수학일기 썼어? 오늘 배운 내용 정리했어? 👀',
                    options: ['당연히 썼지! 완벽해', '대충 썼어', '조금 썼어', '깜빡했어...', '수학일기가 뭐야?'],
                    category: 'process'
                },
                'problem_count': {
                    id: 'problem_count',
                    text: '오늘 문제 몇 개나 정복했어? 💪',
                    options: ['30개 이상!', '20개 정도', '10개 정도', '5개 정도', '세는 게 무의미해...'],
                    category: 'process'
                },
                'concept_study': {
                    id: 'concept_study',
                    text: '오늘 개념공부는 어땠어? 새로 배운 개념 이해됐어? 📚',
                    options: ['완벽하게 이해했어!', '대부분 이해했어', '반반이야', '조금 헷갈려', '전혀 모르겠어'],
                    category: 'process'
                },
                'error_note': {
                    id: 'error_note',
                    text: '오답노트 정리했어? 틀린 문제 복습 제대로 했어? 📝',
                    options: ['완벽하게 정리했어!', '대부분 정리했어', '반 정도 정리했어', '조금만 정리했어', '아직 안했어'],
                    category: 'process'
                },
                'test': {
                    id: 'test',
                    text: '오늘 테스트나 유형 문제 풀 때 어땠어? 자신 있어? 🧪',
                    options: ['완벽하게 풀었어!', '대부분 맞았어', '반반이야', '많이 틀렸어', '망했어...'],
                    category: 'process'
                },
                'review': {
                    id: 'review',
                    text: '복습은 제대로 했어? 이전에 배운 내용 다시 확인했어? 🔄',
                    options: ['완벽하게 복습했어!', '대부분 복습했어', '반 정도 복습했어', '조금만 복습했어', '복습 안했어'],
                    category: 'process'
                },
                'face_evaluation': {
                    id: 'face_evaluation',
                    text: '지면평가 할 때 어땠어? 선생님 앞에서 설명할 때 떨렸어? 🎤',
                    options: ['완벽하게 설명했어!', '대부분 설명했어', '반 정도 설명했어', '조금만 설명했어', '너무 떨려서 못했어'],
                    category: 'interaction'
                },
                'tts_listening': {
                    id: 'tts_listening',
                    text: 'TTS로 개념 설명 들었어? 음성 강의 도움됐어? 🔊',
                    options: ['완벽하게 이해했어!', '대부분 이해했어', '반반이야', '조금 헷갈려', '전혀 모르겠어'],
                    category: 'process'
                },
                'inefficiency': {
                    id: 'inefficiency',
                    text: '오늘 비효율적으로 시간을 보낸 구간이 있었어?',
                    options: ['거의 없다', '조금 있다', '반 정도', '좀 많았다', '너무 많았다'],
                    category: 'focus'
                },
                'default': {
                    id: 'default',
                    text: '오늘 수업 어땠어? 솔직히 말해봐 😏',
                    options: ['최고였어!', '괜찮았어', '그냥 그래', '조금 힘들었어', '힘들었어...'],
                    category: 'emotion'
                }
            };
            return defaults[topic] || defaults['default'];
        }
        
        // 랜덤 질문 선택 (상관관계 고려) - 레거시 폴백
        function selectRandomQuestions() {
            const selected = [];
            const allCategories = ['planning', 'emotion', 'process', 'reflection', 'interaction', 'focus'];
            
            // 첫 번째 질문은 완전 랜덤
            const firstCategory = allCategories[Math.floor(Math.random() * allCategories.length)];
            const firstQuestions = randomQuestionPool.filter(q => q.category === firstCategory);
            const firstQuestion = firstQuestions[Math.floor(Math.random() * firstQuestions.length)];
            selected.push(firstQuestion);
            
            // 두 번째 질문은 첫 번째와 연관성 있게
            let secondCategory;
            const relatedCategories = {
                'planning': ['process', 'focus'],
                'emotion': ['reflection', 'interaction'],
                'process': ['planning', 'focus'],
                'reflection': ['emotion', 'interaction'],
                'interaction': ['emotion', 'reflection'],
                'focus': ['process', 'planning']
            };
            
            const possibleCategories = relatedCategories[firstCategory];
            secondCategory = possibleCategories[Math.floor(Math.random() * possibleCategories.length)];
            const secondQuestions = randomQuestionPool.filter(q => 
                q.category === secondCategory && q.id !== firstQuestion.id
            );
            const secondQuestion = secondQuestions[Math.floor(Math.random() * secondQuestions.length)];
            selected.push(secondQuestion);
            
            selectedRandomQuestions = selected;
        }
        
        // 페이지 로드 시 자동으로 질문 시작 또는 리포트 표시
        window.addEventListener('DOMContentLoaded', function() {
            studentName = <?php echo isset($studentName) ? json_encode($studentName, JSON_UNESCAPED_UNICODE) : '""'; ?>;
            studentId = <?php echo isset($studentId) ? (int)$studentId : 0; ?>;
            
            // URL 파라미터에서 reportid 확인
            const urlParams = new URLSearchParams(window.location.search);
            const reportId = urlParams.get('reportid');
            
            if (reportId) {
                // 리포트 ID가 있으면 해당 리포트 표시
                loadReportById(reportId);
            } else {
                // 리포트 ID가 없으면 질문 시작
                startQuestions();
            }
        });
        
        // 질문 시작
        async function startQuestions() {
            currentStep = 'questions';
            
            // 로딩 표시
            const questionText = document.getElementById('questionText');
            questionText.innerHTML = '<span class="loading-text">질문을 준비하고 있어요... 🤔</span>';
            
            showStep('questionsStep');
            document.getElementById('progressBar').classList.remove('hidden');
            
            // 첫 번째 질문: "오늘 하루는 어떤 하루였어?" (12가지 테마 중 랜덤 선택, 각 5지 선다)
            const selectedTheme = getRandomDailyMoodTheme();
            // 학생 이름을 포함한 질문으로 수정 (lastname만 사용, "!" 형식으로 호칭)
            const nameParts = studentName.split(' ');
            const studentLastName = nameParts.length > 1 ? nameParts[nameParts.length - 1] : nameParts[0]; // lastname 추출 (없으면 전체 이름)
            const questionWithName = selectedTheme.question.replace('오늘 하루는', `${studentLastName}!, 오늘 하루는`);
            typeText('questionText', questionWithName, () => {
                showOptions(selectedTheme.options);
            });
            triggerAvatarAnimation('wave');
            
            // 첫 번째 질문은 특별 처리 (일반 질문이 아님)
            currentQuestion = -1; // -1로 설정하여 다음에 0부터 시작하도록
            // 선택된 테마 정보 저장 (리포트에서 사용)
            window.selectedDailyMoodTheme = selectedTheme;
            
            // 환영 메시지가 표시되는 동안 백그라운드에서 질문 생성
            generateRandomQuestions().catch(error => {
                console.error('Failed to pre-generate questions:', error);
                // 에러가 나도 계속 진행 가능
            });
        }
        
        // 질문 표시
        async function showQuestion() {
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            const question = allQuestions[currentQuestion];
            
            // 오답노트 질문인 경우 퀴즈 형식으로 처리
            if (question.id === 'error_note' && question.isQuiz) {
                showErrorNoteQuiz(question);
                return;
            }
            
            // 침착도 질문인 경우 퀴즈 형식으로 처리
            if (question.id === 'calmness' && question.isQuiz) {
                showCalmnessQuiz(question);
                return;
            }
            
            // 수학일기 작성 개수 질문인 경우 퀴즈 형식으로 처리
            if (question.id === 'math_diary_count' && question.isQuiz) {
                showMathDiaryCountQuiz(question);
                return;
            }
            
            // 포모도르 요약 페이지인 경우
            if (question.id === 'pomodoro_summary' && question.isSummary) {
                showPomodoroSummary(question);
                return;
            }
            
            // 질문 표시 (OpenAI로 생성된 질문은 이미 다양하므로 그대로 사용)
            let questionText = question.text;
            
            typeText('questionText', questionText, () => {
                showOptions(question.options);
            });
            triggerAvatarAnimation('talk');
            updateProgress();
        }
        
        // 포모도르 통합 페이지 표시 (요약 + 상호작용)
        function showPomodoroSummary(question) {
            const questionText = document.getElementById('questionText');
            const optionsGrid = document.getElementById('optionsGrid');
            
            // 디버깅: JavaScript에서 받은 데이터 확인
            console.log('POMODORO_JS_DEBUG: pomodoroDiaryItems=', pomodoroDiaryItems);
            console.log('POMODORO_JS_DEBUG: pomodoroDiaryItems.length=', pomodoroDiaryItems ? pomodoroDiaryItems.length : 'null');
            console.log('POMODORO_JS_DEBUG: pomodoroTotalCount=', pomodoroTotalCount);
            console.log('POMODORO_JS_DEBUG: pomodoroSatisfactionCount=', pomodoroSatisfactionCount);
            console.log('POMODORO_JS_DEBUG: pomodoroSatisfactionAvg=', pomodoroSatisfactionAvg);
            
            // 포모도르 작성 내용 정리
            let summaryHTML = '<div style="text-align: left; max-width: 800px; margin: 0 auto;">';
            summaryHTML += '<h2 style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--text-primary);">📝 오늘의 포모도르 수학일기</h2>';
            
            // 배열이 비어있거나 null인지 확인
            if (!pomodoroDiaryItems || pomodoroDiaryItems.length === 0) {
                console.log('POMODORO_JS_DEBUG: No items found, showing empty message');
                summaryHTML += '<div style="padding: 2rem; background: var(--bg-secondary); border-radius: 1rem; text-align: center; color: var(--text-secondary); margin-bottom: 1.5rem;">';
                summaryHTML += '<p style="font-size: 1.2rem;">최근 12시간 이내 작성한 포모도르 일기가 없습니다.</p>';
                summaryHTML += '</div>';
            } else {
                console.log('POMODORO_JS_DEBUG: Found ' + pomodoroDiaryItems.length + ' items, displaying summary');
                // 요약 정보 표시
                summaryHTML += '<div style="background: var(--bg-secondary); border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem;">';
                
                // 포모도르 일기 작성 정보 (오늘 기준) - 리포트에서 제거하고 여기에만 표시
                if (pomodoroTotalCount > 0) {
                    summaryHTML += `<p style="font-size: 1.1rem; margin-bottom: 1rem;"><strong>포모도르 일기 작성 (오늘 기준):</strong> 총 ${pomodoroTotalCount}개 작성, 만족도 평균 ${pomodoroSatisfactionAvg.toFixed(2)} (${pomodoroSatisfactionCount}개 평가됨)</p>`;
                }
                
                // 포모도르 항목별 표시
                summaryHTML += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-top: 1rem;">';
                pomodoroDiaryItems.forEach((item, index) => {
                    const satisfactionEmoji = item.satisfaction === 3 ? '😊' : (item.satisfaction === 2 ? '🙂' : (item.satisfaction === 1 ? '😐' : '📝'));
                    const satisfactionText = item.satisfaction === 3 ? '매우만족' : (item.satisfaction === 2 ? '만족' : (item.satisfaction === 1 ? '불만족' : '미평가'));
                    summaryHTML += `<div style="background: var(--bg-card); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">`;
                    summaryHTML += `<div style="font-size: 1.8rem; margin-bottom: 0.5rem; text-align: center;">${satisfactionEmoji}</div>`;
                    summaryHTML += `<div style="font-size: 1rem; font-weight: bold; color: var(--text-primary); margin-bottom: 0.25rem; text-align: center;">${item.slot}번째 포모도르</div>`;
                    summaryHTML += `<div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem; text-align: center;">${item.status}</div>`;
                    summaryHTML += `<div style="font-size: 0.85rem; color: var(--text-secondary); text-align: center; padding-top: 0.25rem; border-top: 1px solid var(--border); margin-top: 0.5rem;">만족도: ${satisfactionText}</div>`;
                    summaryHTML += `</div>`;
                });
                summaryHTML += '</div>';
                summaryHTML += '</div>';
            }
            
            // 상호작용 질문 추가 (단순화: 바로 집중 도움 여부만 물어봄)
            summaryHTML += '<div style="background: var(--bg-card); border-radius: 1rem; padding: 1.5rem; margin-top: 1.5rem; border: 2px solid var(--border);">';
            summaryHTML += '<h3 style="font-size: 1.3rem; margin-bottom: 1rem; color: var(--text-primary);">💭 포모도르 수학일기가 집중에 도움이 됐어?</h3>';
            summaryHTML += '</div>';
            
            summaryHTML += '</div>';
            
            questionText.innerHTML = summaryHTML;
            optionsGrid.innerHTML = '';
            
            // 상호작용 옵션 표시 (집중 도움 여부 질문 - 5지선다)
            setTimeout(() => {
                const focusOptions = [];
                if (focusHelpVeryHelpful > 0) focusOptions.push('큰 도움');
                if (focusHelpHelpful > 0) focusOptions.push('도움');
                if (focusHelpNormal > 0) focusOptions.push('보통');
                if (focusHelpNotHelpful > 0) focusOptions.push('별로');
                
                // 5지선다를 위해 모든 옵션 포함
                const allFocusOptions = ['큰 도움', '도움', '보통', '별로', '전혀'];
                
                // 실제 데이터 기반 옵션이 있으면 우선 사용, 없으면 전체 옵션 사용
                if (focusOptions.length === 0) {
                    focusOptions.push(...allFocusOptions);
                } else {
                    // 실제 데이터 옵션 + 나머지 옵션으로 5개 구성
                    const remainingOptions = allFocusOptions.filter(opt => !focusOptions.includes(opt));
                    focusOptions.push(...remainingOptions.slice(0, 5 - focusOptions.length));
                }
                
                focusOptions.forEach((option, index) => {
                    setTimeout(() => {
                        const button = document.createElement('button');
                        button.className = 'option-button';
                        button.textContent = option;
                        button.onclick = () => handlePomodoroAnswer(option, question);
                        optionsGrid.appendChild(button);
                    }, index * 100);
                });
            }, 1000);
            
            triggerAvatarAnimation('talk');
            updateProgress();
        }
        
        // 포모도르 답변 처리 (단순화: 집중 도움 여부만 처리)
        function handlePomodoroAnswer(answer, question) {
            // 클릭된 버튼에 체크 표시 애니메이션 추가
            const buttons = document.querySelectorAll('.option-button');
            buttons.forEach(btn => {
                if (btn.textContent.trim() === answer) {
                    btn.classList.add('checked');
                } else {
                    btn.style.opacity = '0.5';
                    btn.style.pointerEvents = 'none';
                }
            });
            
            // 답변 저장
            responses['focus_help'] = answer;
            
            // 옵션 숨기기 (애니메이션 후)
            setTimeout(() => {
                document.getElementById('optionsGrid').innerHTML = '';
            }, 500);
            
            // 리액션 메시지 선택 (클릭 시점 시간을 60으로 나눈 나머지로 선택)
            const feedback = getPomodoroReaction(answer);
            typeText('questionText', feedback, () => {
                // 다음 질문으로 이동
                setTimeout(() => {
                const showNextQuestion = () => {
                    const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
                    if (currentQuestion < allQuestions.length - 1) {
                        currentQuestion++;
                        showQuestion();
                    } else {
                        showStep('completeStep');
                        showCompletionScreen();
                        document.getElementById('progressBar').classList.add('hidden');
                    }
                };
                showNextQuestion();
                }, 1000);
            });
        }
        
        // 침착도 등급 퀴즈 표시
        function showCalmnessQuiz(question) {
            const actualGrade = actualCalmness || 'F'; // 실제 침착도 등급
            
            // 선택지 생성 (정답 + 오답 3개)
            const options = [];
            const correctAnswer = actualGrade;
            
            // 정답 추가
            options.push(correctAnswer);
            
            // 오답 선택지 생성 (실제 등급과 다른 값들)
            const allGrades = ['A+', 'A', 'B+', 'B', 'C+', 'C', 'F'];
            const wrongAnswers = allGrades.filter(grade => grade !== correctAnswer);
            
            // 랜덤하게 4개 선택 (총 5개: 정답 1개 + 오답 4개)
            const shuffledWrong = wrongAnswers.sort(() => Math.random() - 0.5).slice(0, 4);
            options.push(...shuffledWrong);
            
            // 선택지 섞기
            for (let i = options.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [options[i], options[j]] = [options[j], options[i]];
            }
            
            // 정답 저장
            question.correctAnswer = correctAnswer;
            question.options = options;
            
            // 질문 텍스트 변경
            const quizText = `오늘 수업 중 침착도는 어땠어? 실제 침착도 등급은 무엇일까? 🤔`;
            
            typeText('questionText', quizText, () => {
                showOptions(options);
            });
            triggerAvatarAnimation('talk');
            updateProgress();
        }
        
        // 수학일기 작성 개수 퀴즈 표시
        function showMathDiaryCountQuiz(question) {
            const actualCount = pomodoroTotalCount; // 최근 12시간 이내 수학일기 작성 개수
            
            // 선택지 생성 (정답 + 오답 4개 = 총 5개)
            const options = [];
            const correctAnswer = actualCount.toString() + '개';
            
            // 정답 추가
            options.push(correctAnswer);
            
            // 오답 선택지 생성 (실제 개수와 다른 값들)
            const wrongAnswers = [];
            if (actualCount === 0) {
                wrongAnswers.push('1개', '2개', '3개', '4개');
            } else if (actualCount === 1) {
                wrongAnswers.push('0개', '2개', '3개', '4개');
            } else if (actualCount === 2) {
                wrongAnswers.push('0개', '1개', '3개', '4개');
            } else if (actualCount === 3) {
                wrongAnswers.push('0개', '1개', '2개', '4개');
            } else if (actualCount === 4) {
                wrongAnswers.push('0개', '1개', '2개', '3개');
            } else {
                // 5개 이상인 경우
                wrongAnswers.push(
                    (actualCount - 2).toString() + '개',
                    (actualCount - 1).toString() + '개',
                    (actualCount + 1).toString() + '개',
                    (actualCount + 2).toString() + '개'
                );
            }
            
            // 오답 선택지 추가 (총 5개: 정답 1개 + 오답 4개)
            options.push(...wrongAnswers);
            
            // 선택지 섞기
            for (let i = options.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [options[i], options[j]] = [options[j], options[i]];
            }
            
            // 정답 저장
            question.correctAnswer = correctAnswer;
            question.options = options;
            
            // 질문 텍스트 변경
            const quizText = `오늘 수학일기 작성 갯수는? 최근 12시간 이내 작성한 수학일기는 몇 개일까? 🤔`;
            
            typeText('questionText', quizText, () => {
                showOptions(options);
            });
            triggerAvatarAnimation('talk');
            updateProgress();
        }
        
        // 오답노트 밀림 개수 퀴즈 표시
        function showErrorNoteQuiz(question) {
            const actualCount = errorNoteBacklogCount;
            
            // 선택지 생성 (정답 + 오답 4개 = 총 5개)
            const options = [];
            const correctAnswer = actualCount.toString() + '개';
            
            // 정답 추가
            options.push(correctAnswer);
            
            // 오답 선택지 생성 (실제 개수와 다른 값들)
            const wrongAnswers = [];
            if (actualCount === 0) {
                wrongAnswers.push('1개', '2개', '3개', '4개');
            } else if (actualCount === 1) {
                wrongAnswers.push('0개', '2개', '3개', '4개');
            } else if (actualCount === 2) {
                wrongAnswers.push('0개', '1개', '3개', '4개');
            } else if (actualCount === 3) {
                wrongAnswers.push('0개', '1개', '2개', '4개');
            } else if (actualCount === 4) {
                wrongAnswers.push('0개', '1개', '2개', '3개');
            } else {
                // 5개 이상인 경우
                wrongAnswers.push(
                    (actualCount - 2).toString() + '개',
                    (actualCount - 1).toString() + '개',
                    (actualCount + 1).toString() + '개',
                    (actualCount + 2).toString() + '개'
                );
            }
            
            // 오답 선택지 추가 (총 5개: 정답 1개 + 오답 4개)
            options.push(...wrongAnswers);
            
            // 선택지 섞기
            for (let i = options.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [options[i], options[j]] = [options[j], options[i]];
            }
            
            // 정답 저장
            question.correctAnswer = correctAnswer;
            question.options = options;
            
            // 질문 텍스트 변경
            const quizText = `오답노트는 밀리지 않았어? 밀린 오답노트 수는 몇 개일까? 🤔`;
            
            typeText('questionText', quizText, () => {
                showOptions(options);
            });
            triggerAvatarAnimation('talk');
            updateProgress();
        }
        
        // 옵션 표시
        function showOptions(options) {
            const grid = document.getElementById('optionsGrid');
            grid.innerHTML = '';
            
            options.forEach((option, index) => {
                setTimeout(() => {
                    const button = document.createElement('button');
                    button.className = 'option-button';
                    button.textContent = option;
                    button.onclick = () => handleAnswer(option);
                    grid.appendChild(button);
                }, index * 100);
            });
        }
        
        // 답변 처리
        function handleAnswer(answer) {
            // 클릭된 버튼에 체크 표시 애니메이션 추가
            const buttons = document.querySelectorAll('.option-button');
            buttons.forEach(btn => {
                if (btn.textContent.trim() === answer) {
                    btn.classList.add('checked');
                } else {
                    btn.style.opacity = '0.5';
                    btn.style.pointerEvents = 'none';
                }
            });
            
            // 첫 번째 질문(오늘 하루 어땠어?) 처리
            if (currentQuestion === -1) {
                responses['daily_mood'] = answer;
                currentQuestion = 0; // 다음 질문으로 이동
                setTimeout(() => {
                    showQuestion();
                }, 1500);
                return;
            }
            
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            const question = allQuestions[currentQuestion];
            
            // 포모도르 통합 페이지인 경우
            if (question.id === 'pomodoro_summary' && question.isSummary) {
                handlePomodoroAnswer(answer, question);
                return;
            }
            
            responses[question.id] = answer;
            
            // 옵션 숨기기 (애니메이션 후)
            setTimeout(() => {
                document.getElementById('optionsGrid').innerHTML = '';
            }, 500);
            
            // 실제 데이터와 비교 표시
            if (question.hasData) {
                showDataComparison(question.id, answer);
            }
            
            // 피드백 표시
            const showNextQuestion = () => {
                // 데이터 비교 숨기기
                document.getElementById('dataComparison').classList.add('hidden');
                
                if (currentQuestion < allQuestions.length - 1) {
                    currentQuestion++;
                    showQuestion();
                } else {
                    // 완료
                    showStep('completeStep');
                    showCompletionScreen();
                    document.getElementById('progressBar').classList.add('hidden');
                }
            };
            
            // 침착도 퀴즈인 경우 정답/오답 피드백
            if (question.id === 'calmness' && question.isQuiz) {
                const isCorrect = answer === question.correctAnswer;
                let isPositive = false;
                let feedbackText;
                
                if (isCorrect) {
                    // 정답인 경우
                    feedbackText = question.followUp['correct'];
                    isPositive = true;
                } else {
                    // 오답인 경우 - 실제 데이터가 선택한 답보다 더 좋은지 확인
                    const actualGrade = actualCalmness || 'F';
                    const comparison = compareCalmnessGrade(actualGrade, answer);
                    
                    if (comparison > 0) {
                        // 실제 데이터가 선택한 답보다 더 좋은 경우 (긍정적 피드백)
                        feedbackText = getCalmnessPositiveFeedback();
                        isPositive = true;
                    } else {
                        // 실제 데이터가 선택한 답보다 나쁜 경우 (기존 피드백)
                        feedbackText = question.followUp['incorrect'] + ` 정답은 ${question.correctAnswer}야!`;
                        isPositive = false;
                    }
                }
                
                showCalmnessQuizFeedback(isPositive, feedbackText, question.correctAnswer, answer, showNextQuestion);
                return;
            }
            
            // 수학일기 작성 개수 퀴즈인 경우 정답/오답 피드백
            if (question.id === 'math_diary_count' && question.isQuiz) {
                const isCorrect = answer === question.correctAnswer;
                let isPositive = false;
                let feedbackText;
                let encouragementFeedback = null;
                
                // 선택한 답에서 숫자 추출
                const userAnswerNum = parseInt(answer.replace('개', '')) || 0;
                const actualCount = pomodoroTotalCount || 0;
                
                if (isCorrect) {
                    // 정답인 경우
                    const resultMessage = '정답!';
                    feedbackText = '자신의 수학일기 작성 개수를 정확하게 알고 있네? 대단해! 👍';
                    isPositive = true;
                    
                    // 수학일기 개수가 2개 이하인 경우 부모님 상담 관련 피드백 추가
                    if (pomodoroTotalCount <= 2) {
                        encouragementFeedback = getMathDiaryEncouragementFeedback();
                    }
                    
                    showMathDiaryCountQuizFeedback(isPositive, resultMessage, feedbackText, question.correctAnswer, encouragementFeedback, showNextQuestion);
                } else {
                    // 오답인 경우 - 실제 데이터가 선택한 답보다 더 많은지 확인
                    if (actualCount > userAnswerNum) {
                        // 실제 데이터가 선택한 답보다 더 많은 경우 (긍정적 피드백)
                        const resultMessage = '오답이지만...';
                        feedbackText = getMathDiaryCountPositiveFeedback();
                        isPositive = true;
                        
                        showMathDiaryCountQuizFeedback(isPositive, resultMessage, feedbackText, question.correctAnswer, encouragementFeedback, showNextQuestion);
                    } else {
                        // 실제 데이터가 선택한 답보다 적거나 같은 경우 (기존 피드백)
                        const resultMessage = '오답입니다 ^^';
                        feedbackText = `실제로는 ${question.correctAnswer}야. 다시 확인해볼까? 😅`;
                        isPositive = false;
                        
                        // 수학일기 개수가 2개 이하인 경우 부모님 상담 관련 피드백 추가
                        if (pomodoroTotalCount <= 2) {
                            encouragementFeedback = getMathDiaryEncouragementFeedback();
                        }
                        
                        showMathDiaryCountQuizFeedback(isPositive, resultMessage, feedbackText, question.correctAnswer, encouragementFeedback, showNextQuestion);
                    }
                }
                return;
            }
            
            // 오답노트 퀴즈인 경우 정답/오답 피드백
            if (question.id === 'error_note' && question.isQuiz) {
                const isCorrect = answer === question.correctAnswer;
                let isPositive = false;
                let feedbackText;
                
                if (isCorrect) {
                    // 정답인 경우
                    feedbackText = question.followUp['correct'];
                    isPositive = true;
                } else {
                    // 오답인 경우 - 입력한 값이 실제 데이터보다 많은지 확인 (자신을 과소평가했는지)
                    const userAnswerNum = parseInt(answer.replace('개', '')) || 0;
                    const actualCount = errorNoteBacklogCount || 0;
                    
                    if (userAnswerNum > actualCount) {
                        // 입력한 값이 실제 데이터보다 많은 경우 = 자신을 과소평가한 경우 (긍정적 피드백)
                        feedbackText = getErrorNotePositiveFeedback();
                        isPositive = true;
                    } else {
                        // 입력한 값이 실제 데이터보다 적거나 같은 경우 (기존 피드백)
                        feedbackText = question.followUp['incorrect'] + ` 정답은 ${question.correctAnswer}야!`;
                        isPositive = false;
                    }
                }
                
                showErrorNoteQuizFeedback(isPositive, feedbackText, question.correctAnswer, showNextQuestion);
                return;
            }
            
            if (question.followUp && question.followUp[answer]) {
                typeText('questionText', question.followUp[answer], () => {
                    setTimeout(showNextQuestion, 2000);
                });
            } else {
                // 더 다양하고 자연스러운 랜덤 응답
                const genericResponses = [
                    '오~ 그렇구나! 다음 질문 갈게~',
                    '음음, 알겠어! 메모해둘게 📝',
                    '아하! 그랬구나~ 이해했어!',
                    '오케이~ 다음 거!',
                    '흠... 흥미롭네? 🤔',
                    '그래그래~ 알겠어!',
                    '오호라~ 그렇군!',
                    '알았어 알았어~ 다음!',
                    '음... 나름 괜찮네? 계속 가보자!',
                    '좋아좋아~ 잘하고 있어!',
                    '오~ 의외인데? 😮',
                    '그렇겠지... 그럴 수 있지!',
                    '아 정말? 재밌네~',
                    '오케바리~ 다음 질문!',
                    '음... 뭐 그럴 수도 있지 뭐~'
                ];
                const randomResponse = genericResponses[Math.floor(Math.random() * genericResponses.length)];
                typeText('questionText', randomResponse, () => {
                    setTimeout(showNextQuestion, 1500);
                });
            }
        }
        
        // 침착도 등급 비교 함수 (높을수록 좋음: A+ > A > B+ > B > C+ > C > F)
        function compareCalmnessGrade(grade1, grade2) {
            const gradeOrder = {'A+': 6, 'A': 5, 'B+': 4, 'B': 3, 'C+': 2, 'C': 1, 'F': 0};
            const order1 = gradeOrder[grade1] || 0;
            const order2 = gradeOrder[grade2] || 0;
            if (order1 > order2) return 1; // grade1이 더 높음
            if (order1 < order2) return -1; // grade2가 더 높음
            return 0; // 같음
        }
        
        // 침착도 긍정적 피드백 메시지 60개 (실제 데이터가 선택한 답보다 더 좋은 경우)
        function getCalmnessPositiveFeedback() {
            const positiveFeedbacks = [
                '와! 실제 침착도가 생각보다 더 좋네! 자신을 과소평가했구나! 🌟',
                '실제로는 더 침착했어! 자신을 너무 낮게 평가했네? 대단해! ✨',
                '오! 실제 침착도가 더 높아! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎉',
                '실제 데이터를 보니 침착도가 더 좋았어! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 침착했구나! 자신을 너무 낮게 생각했어! 좋아! 🌈',
                '실제 침착도가 생각보다 더 좋네! 자신을 잘 모르고 있었구나? 대단해! ⭐',
                '오! 실제로는 더 침착했어! 자신을 과소평가했네? 좋은 소식이야! 🎊',
                '실제 데이터를 보니 침착도가 더 높아! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제 침착도가 생각보다 더 좋네! 자신을 잘 모르고 있었구나? 좋아! 🌟',
                '실제로는 더 침착했어! 자신을 과소평가했네? 대단해! ✨',
                '오! 실제 침착도가 더 높아! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎉',
                '실제 데이터를 보니 침착도가 더 좋았어! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 침착했구나! 자신을 과소평가했네? 좋아! 🌈',
                '실제 침착도가 생각보다 더 좋네! 자신을 너무 낮게 평가했어! 대단해! ⭐',
                '오! 실제로는 더 침착했어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎊',
                '실제 데이터를 보니 침착도가 더 높아! 자신을 과소평가했네! 👍',
                '와! 실제 침착도가 생각보다 더 좋네! 자신을 너무 낮게 생각했어! 좋아! 🌟',
                '실제로는 더 침착했어! 자신을 잘 모르고 있었구나? 대단해! ✨',
                '오! 실제 침착도가 더 높아! 자신을 과소평가했네? 좋은 소식이야! 🎉',
                '실제 데이터를 보니 침착도가 더 좋았어! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 침착했구나! 자신을 잘 모르고 있었구나? 좋아! 🌈',
                '실제 침착도가 생각보다 더 좋네! 자신을 과소평가했네? 대단해! ⭐',
                '오! 실제로는 더 침착했어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎊',
                '실제 데이터를 보니 침착도가 더 높아! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제 침착도가 생각보다 더 좋네! 자신을 과소평가했네? 좋아! 🌟',
                '실제로는 더 침착했어! 자신을 너무 낮게 평가했어! 대단해! ✨',
                '오! 실제 침착도가 더 높아! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎉',
                '실제 데이터를 보니 침착도가 더 좋았어! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 침착했구나! 자신을 너무 낮게 생각했어! 좋아! 🌈',
                '실제 침착도가 생각보다 더 좋네! 자신을 잘 모르고 있었구나? 대단해! ⭐',
                '오! 실제로는 더 침착했어! 자신을 과소평가했네? 좋은 소식이야! 🎊',
                '실제 데이터를 보니 침착도가 더 높아! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제 침착도가 생각보다 더 좋네! 자신을 잘 모르고 있었구나? 좋아! 🌟',
                '실제로는 더 침착했어! 자신을 과소평가했네? 대단해! ✨',
                '오! 실제 침착도가 더 높아! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎉',
                '실제 데이터를 보니 침착도가 더 좋았어! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 침착했구나! 자신을 과소평가했네? 좋아! 🌈',
                '실제 침착도가 생각보다 더 좋네! 자신을 너무 낮게 평가했어! 대단해! ⭐',
                '오! 실제로는 더 침착했어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎊',
                '실제 데이터를 보니 침착도가 더 높아! 자신을 과소평가했네! 👍',
                '와! 실제 침착도가 생각보다 더 좋네! 자신을 너무 낮게 생각했어! 좋아! 🌟',
                '실제로는 더 침착했어! 자신을 잘 모르고 있었구나? 대단해! ✨',
                '오! 실제 침착도가 더 높아! 자신을 과소평가했네? 좋은 소식이야! 🎉',
                '실제 데이터를 보니 침착도가 더 좋았어! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 침착했구나! 자신을 잘 모르고 있었구나? 좋아! 🌈',
                '실제 침착도가 생각보다 더 좋네! 자신을 과소평가했네? 대단해! ⭐',
                '오! 실제로는 더 침착했어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎊',
                '실제 데이터를 보니 침착도가 더 높아! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제 침착도가 생각보다 더 좋네! 자신을 과소평가했네? 좋아! 🌟',
                '실제로는 더 침착했어! 자신을 너무 낮게 평가했어! 대단해! ✨',
                '오! 실제 침착도가 더 높아! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎉',
                '실제 데이터를 보니 침착도가 더 좋았어! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 침착했구나! 자신을 너무 낮게 생각했어! 좋아! 🌈',
                '실제 침착도가 생각보다 더 좋네! 자신을 잘 모르고 있었구나? 대단해! ⭐',
                '오! 실제로는 더 침착했어! 자신을 과소평가했네? 좋은 소식이야! 🎊',
                '실제 데이터를 보니 침착도가 더 높아! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제 침착도가 생각보다 더 좋네! 자신을 잘 모르고 있었구나? 좋아! 🌟',
                '실제로는 더 침착했어! 자신을 과소평가했네? 대단해! ✨',
                '오! 실제 침착도가 더 높아! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎉',
                '실제 데이터를 보니 침착도가 더 좋았어! 자신을 잘 모르고 있었구나! 👍'
            ];
            
            // 현재 시간(초)을 60으로 나눈 나머지로 피드백 선택
            const currentSecond = new Date().getSeconds();
            const feedbackIndex = currentSecond % 60;
            
            return positiveFeedbacks[feedbackIndex];
        }
        
        // 오답노트 긍정적 피드백 메시지 60개 (입력한 값이 실제 데이터보다 많은 경우 = 자신을 과소평가한 경우)
        function getErrorNotePositiveFeedback() {
            const positiveFeedbacks = [
                '와! 실제로는 오답노트가 더 적게 밀렸어! 자신을 과소평가했구나! 좋은 소식이야! 🌟',
                '실제 데이터를 보니 오답노트 밀림이 더 적네! 자신을 너무 낮게 평가했어! 대단해! ✨',
                '오! 실제로는 오답노트가 더 적게 밀렸어! 자신을 잘 모르고 있었구나? 좋아! 🎉',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 과소평가했네? 좋은 소식이야! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 너무 낮게 생각했어! 좋아! 🌈',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 잘 모르고 있었구나? 대단해! ⭐',
                '오! 실제로는 더 적게 밀렸어! 자신을 과소평가했네? 좋은 소식이야! 🎊',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 잘 모르고 있었구나? 좋아! 🌟',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 과소평가했네? 대단해! ✨',
                '오! 실제로는 더 적게 밀렸어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎉',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 과소평가했네? 좋아! 🌈',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 너무 낮게 평가했어! 대단해! ⭐',
                '오! 실제로는 더 적게 밀렸어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎊',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 너무 낮게 생각했어! 좋아! 🌟',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 잘 모르고 있었구나? 대단해! ✨',
                '오! 실제로는 더 적게 밀렸어! 자신을 과소평가했네? 좋은 소식이야! 🎉',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 잘 모르고 있었구나? 좋아! 🌈',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 과소평가했네? 대단해! ⭐',
                '오! 실제로는 더 적게 밀렸어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎊',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 과소평가했네? 좋아! 🌟',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 너무 낮게 평가했어! 대단해! ✨',
                '오! 실제로는 더 적게 밀렸어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎉',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 너무 낮게 생각했어! 좋아! 🌈',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 잘 모르고 있었구나? 대단해! ⭐',
                '오! 실제로는 더 적게 밀렸어! 자신을 과소평가했네? 좋은 소식이야! 🎊',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 잘 모르고 있었구나? 좋아! 🌟',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 과소평가했네? 대단해! ✨',
                '오! 실제로는 더 적게 밀렸어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎉',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 과소평가했네? 좋아! 🌈',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 너무 낮게 평가했어! 대단해! ⭐',
                '오! 실제로는 더 적게 밀렸어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎊',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 너무 낮게 생각했어! 좋아! 🌟',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 잘 모르고 있었구나? 대단해! ✨',
                '오! 실제로는 더 적게 밀렸어! 자신을 과소평가했네? 좋은 소식이야! 🎉',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 잘 모르고 있었구나? 좋아! 🌈',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 과소평가했네? 대단해! ⭐',
                '오! 실제로는 더 적게 밀렸어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎊',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 과소평가했네? 좋아! 🌟',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 너무 낮게 평가했어! 대단해! ✨',
                '오! 실제로는 더 적게 밀렸어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎉',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 너무 낮게 생각했어! 좋아! 🌈',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 잘 모르고 있었구나? 대단해! ⭐',
                '오! 실제로는 더 적게 밀렸어! 자신을 과소평가했네? 좋은 소식이야! 🎊',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 적게 밀렸구나! 자신을 잘 모르고 있었구나? 좋아! 🌟',
                '실제 데이터를 보니 오답노트 밀림이 더 적어! 자신을 과소평가했네? 대단해! ✨',
                '오! 실제로는 더 적게 밀렸어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎉',
                '실제 오답노트 밀림이 생각보다 더 적네! 자신을 잘 모르고 있었구나! 👍'
            ];
            
            // 현재 시간(초)을 60으로 나눈 나머지로 피드백 선택
            const currentSecond = new Date().getSeconds();
            const feedbackIndex = currentSecond % 60;
            
            return positiveFeedbacks[feedbackIndex];
        }
        
        // 수학일기 긍정적 피드백 메시지 60개 (실제 데이터가 선택한 답보다 더 많은 경우)
        function getMathDiaryCountPositiveFeedback() {
            const positiveFeedbacks = [
                '와! 실제로는 수학일기를 더 많이 썼어! 자신을 과소평가했구나! 좋은 소식이야! 🌟',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많네! 자신을 너무 낮게 평가했어! 대단해! ✨',
                '오! 실제로는 더 많이 썼어! 자신을 잘 모르고 있었구나? 좋아! 🎉',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 과소평가했네? 좋은 소식이야! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 너무 낮게 생각했어! 좋아! 🌈',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 잘 모르고 있었구나? 대단해! ⭐',
                '오! 실제로는 더 많이 썼어! 자신을 과소평가했네? 좋은 소식이야! 🎊',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 잘 모르고 있었구나? 좋아! 🌟',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 과소평가했네? 대단해! ✨',
                '오! 실제로는 더 많이 썼어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎉',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 과소평가했네? 좋아! 🌈',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 너무 낮게 평가했어! 대단해! ⭐',
                '오! 실제로는 더 많이 썼어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎊',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 너무 낮게 생각했어! 좋아! 🌟',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 잘 모르고 있었구나? 대단해! ✨',
                '오! 실제로는 더 많이 썼어! 자신을 과소평가했네? 좋은 소식이야! 🎉',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 잘 모르고 있었구나? 좋아! 🌈',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 과소평가했네? 대단해! ⭐',
                '오! 실제로는 더 많이 썼어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎊',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 과소평가했네? 좋아! 🌟',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 너무 낮게 평가했어! 대단해! ✨',
                '오! 실제로는 더 많이 썼어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎉',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 너무 낮게 생각했어! 좋아! 🌈',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 잘 모르고 있었구나? 대단해! ⭐',
                '오! 실제로는 더 많이 썼어! 자신을 과소평가했네? 좋은 소식이야! 🎊',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 잘 모르고 있었구나? 좋아! 🌟',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 과소평가했네? 대단해! ✨',
                '오! 실제로는 더 많이 썼어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎉',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 과소평가했네? 좋아! 🌈',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 너무 낮게 평가했어! 대단해! ⭐',
                '오! 실제로는 더 많이 썼어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎊',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 너무 낮게 생각했어! 좋아! 🌟',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 잘 모르고 있었구나? 대단해! ✨',
                '오! 실제로는 더 많이 썼어! 자신을 과소평가했네? 좋은 소식이야! 🎉',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 잘 모르고 있었구나? 좋아! 🌈',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 과소평가했네? 대단해! ⭐',
                '오! 실제로는 더 많이 썼어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎊',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 잘 모르고 있었구나! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 과소평가했네? 좋아! 🌟',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 너무 낮게 평가했어! 대단해! ✨',
                '오! 실제로는 더 많이 썼어! 자신을 잘 모르고 있었구나? 좋은 소식이야! 🎉',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 과소평가했네! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 너무 낮게 생각했어! 좋아! 🌈',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 잘 모르고 있었구나? 대단해! ⭐',
                '오! 실제로는 더 많이 썼어! 자신을 과소평가했네? 좋은 소식이야! 🎊',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 너무 낮게 평가했어! 👍',
                '와! 실제로는 더 많이 썼구나! 자신을 잘 모르고 있었구나? 좋아! 🌟',
                '실제 데이터를 보니 수학일기 작성 개수가 더 많아! 자신을 과소평가했네? 대단해! ✨',
                '오! 실제로는 더 많이 썼어! 자신을 너무 낮게 생각했어! 좋은 소식이야! 🎉',
                '실제 수학일기 작성 개수가 생각보다 더 많네! 자신을 잘 모르고 있었구나! 👍'
            ];
            
            // 현재 시간(초)을 60으로 나눈 나머지로 피드백 선택
            const currentSecond = new Date().getSeconds();
            const feedbackIndex = currentSecond % 60;
            
            return positiveFeedbacks[feedbackIndex];
        }
        
        // 수학일기 효능 유도 피드백 60개 (현재 시간 기반 선택)
        function getMathDiaryEncouragementFeedback() {
            const mathDiaryFeedbacks = [
                '수학일기 쓰면 뇌가 "아하! 이거구나!" 하고 깨닫는 순간이 많아져! 📝✨',
                '이 수학일기는 부모님 상담 때 사용돼요. 제대로 써야 나중에 후회 안 해요! 📋👨‍👩‍👧',
                '수학일기는 나중에 보면 "아, 내가 이렇게 생각했구나" 하고 놀라게 돼! 😮📚',
                '일기 쓰면서 문제를 다시 보면, 새로운 해결 방법이 떠오를 때가 있어! 💭🔍',
                '이 일기는 부모님께 보여드리는 거예요. 성의 있게 써야 해요! 📝💼',
                '일기 쓰는 습관이 생기면, 수학 실력이 쑥쑥 올라가는 걸 느낄 수 있어! 📈🚀',
                '수학일기 쓰면 집중력도 좋아지고, 기억력도 좋아진대! 🧘‍♀️🧠',
                '부모님 상담 때 이 일기를 보여드려요. 긁어 부스럼 만들지 맙시다! 📔🙏',
                '수학일기는 실수한 부분을 다시 보는 거울이야! 반성하고 성장할 수 있어! 🪞📊',
                '일기 쓰는 순간, 수학이 단순 암기가 아니라 생각하는 과정이 돼! 🤔💭',
                '수학일기 쓰면 나중에 시험 볼 때 "아, 이거 일기에 썼었지!" 하고 기억나! ✍️🎯',
                '이 수학일기는 부모님과 상담할 때 중요한 자료예요. 신중하게 써주세요! 📋💬',
                '수학일기 = 나만의 수학 학습 로그! 시간이 지나도 내 공부 흐름을 알 수 있어! 📊📅',
                '일기 쓰면서 "왜 이렇게 풀었지?" 하고 생각하면, 더 깊이 이해하게 돼! 🤷‍♀️💡',
                '수학일기는 나중에 복습할 때 최고의 자료야! 내가 직접 쓴 거라 더 잘 이해돼! 📚👀',
                '부모님 상담 때 이 일기를 보여드려요. 제대로 써야 좋은 인상을 줄 수 있어요! 📝👨‍👩‍👧',
                '수학일기로 오늘의 성과를 기록하면, 내가 얼마나 성장했는지 알 수 있어! 📈🌟',
                '일기 쓰면서 문제를 다시 풀어보면, 새로운 관점이 생겨! 🔄💭',
                '이 일기는 부모님께 전달되는 거예요. 성실하게 써야 해요! 📋💼',
                '일기 쓰면 수학이 재미있어져! 내 생각을 정리하는 게 즐거워지거든! 😄📖',
                '부모님 상담 때 이 수학일기를 보여드려요. 긁어 부스럼 만들지 맙시다! 📔🙏',
                '일기 쓰면서 "이 문제는 이렇게 풀면 되겠다" 하고 정리하면, 실력이 늘어! ✍️📊',
                '수학일기로 오늘의 실수를 기록하면, 내일은 같은 실수를 안 할 수 있어! 🚫❌➡️✅',
                '일기 쓰는 순간, 수학이 단순 문제 풀이가 아니라 생각의 여정이 돼! 🛤️🧠',
                '수학일기는 나중에 보면 "내가 이렇게 생각했구나" 하고 웃게 돼! 😂📝',
                '이 수학일기는 부모님 상담 자료로 사용돼요. 제대로 써야 나중에 후회 안 해요! 📋👨‍👩‍👧',
                '수학일기 = 나만의 수학 학습 일지! 시간이 지나도 내 공부 과정을 볼 수 있어! 📅📚',
                '일기 쓰면 수학에 대한 관심도 높아지고, 더 공부하고 싶어져! 🔥📖',
                '수학일기로 오늘 배운 걸 정리하면, 내일 더 쉽게 시작할 수 있어! 📝➡️🚀',
                '부모님 상담 때 이 일기를 보여드려요. 성의 있게 써야 해요! 📝💼',
                '수학일기는 나중에 시험 공부할 때 최고의 복습 자료야! 내가 직접 쓴 거라 더 잘 기억나! 📚🎯',
                '일기 쓰는 습관이 생기면, 수학 실력뿐만 아니라 글쓰기 실력도 좋아져! ✍️📝',
                '이 수학일기는 부모님께 전달되는 거예요. 신중하게 써주세요! 📋💬',
                '일기 쓰면서 "왜 이렇게 생각했지?" 하고 되돌아보면, 더 깊이 이해하게 돼! 🔄💭',
                '수학일기로 오늘의 성과를 기록하면, 내가 얼마나 성장했는지 한눈에 볼 수 있어! 📊🌟',
                '일기 쓰면 수학이 단순 암기가 아니라 생각하는 과정이 돼! 🤔🧠',
                '수학일기는 나중에 친구들에게 설명할 때도 도움이 돼! 설명력이 좋아져! 🗣️💡',
                '일기 쓰면서 문제를 다시 풀어보면, 새로운 해결 방법이 떠오를 때가 있어! 🔍💭',
                '부모님 상담 때 이 수학일기를 보여드려요. 긁어 부스럼 만들지 맙시다! 📔🙏',
                '일기 쓰는 순간, 뇌가 "아하! 이거구나!" 하고 깨닫는 순간이 많아져! 🧠✨',
                '수학일기로 오늘 배운 걸 정리하면, 내일 더 쉽게 기억할 수 있어! 📝🧠',
                '이 일기는 부모님 상담 때 사용돼요. 제대로 써야 좋은 인상을 줄 수 있어요! 📝👨‍👩‍👧',
                '수학일기는 나중에 복습할 때 최고의 자료야! 내가 직접 쓴 거라 더 잘 이해돼! 📖👀',
                '일기 쓰면 수학에 대한 자신감도 생기고, 더 공부하고 싶어져! 💪🔥',
                '수학일기 = 나만의 수학 학습 로그! 시간이 지나도 내 공부 흐름을 알 수 있어! 📊📅',
                '일기 쓰면서 "이 문제는 이렇게 풀면 되겠다" 하고 정리하면, 실력이 늘어! ✍️📈',
                '수학일기로 오늘의 실수를 기록하면, 내일은 같은 실수를 안 할 수 있어! 🚫➡️✅',
                '일기 쓰는 습관이 생기면, 수학 실력이 쑥쑥 올라가는 걸 느낄 수 있어! 📈🚀',
                '수학일기는 나중에 보면 "내가 이렇게 생각했구나" 하고 놀라게 돼! 😮📝',
                '일기 쓰면서 문제를 다시 보면, 처음에 못 봤던 패턴이 보여! 🔍💡',
                '부모님 상담 때 이 수학일기를 보여드려요. 성실하게 써야 해요! 📋💼',
                '일기 쓰면 집중력도 좋아지고, 기억력도 좋아진대! 🧘‍♀️🧠',
                '수학일기로 오늘의 성과를 기록하면, 내가 얼마나 성장했는지 알 수 있어! 📊🌟',
                '이 수학일기는 부모님 상담 자료로 사용돼요. 긁어 부스럼 만들지 맙시다! 📔🙏',
                '수학일기는 나중에 시험 볼 때 "아, 이거 일기에 썼었지!" 하고 기억나! ✍️🎯',
                '일기 쓰는 순간, 수학이 단순 문제 풀이가 아니라 생각의 여정이 돼! 🛤️🧠',
                '수학일기 = 나만의 수학 학습 일지! 시간이 지나도 내 공부 과정을 볼 수 있어! 📅📚',
                '일기 쓰면 수학이 재미있어져! 내 생각을 정리하는 게 즐거워지거든! 😄📖',
                '수학일기로 오늘 배운 걸 정리하면, 내일 더 쉽게 시작할 수 있어! 📝➡️🚀',
                '일기 쓰면서 문제를 다시 풀어보면, 새로운 관점이 생겨! 🔄💭',
                '수학일기는 나중에 보면 "내가 이렇게 생각했구나" 하고 웃게 돼! 😂📝',
                '일기 쓰는 습관이 생기면, 수학 실력뿐만 아니라 글쓰기 실력도 좋아져! ✍️📝'
            ];
            
            // 현재 시간(초)을 60으로 나눈 나머지로 피드백 선택
            const currentSecond = new Date().getSeconds();
            const feedbackIndex = currentSecond % 60;
            
            return mathDiaryFeedbacks[feedbackIndex];
        }
        
        // 오답노트 퀴즈 피드백 표시 함수
        // 수학일기 작성 개수 퀴즈 피드백 표시 함수
        function showMathDiaryCountQuizFeedback(isPositive, resultMessage, feedbackText, correctAnswer, encouragementFeedback, callback) {
            const questionText = document.getElementById('questionText');
            const optionsGrid = document.getElementById('optionsGrid');
            
            // 기존 내용 숨기기
            questionText.innerHTML = '';
            optionsGrid.innerHTML = '';
            
            // 정답/오답과 피드백을 하나의 카드로 통합
            const feedbackBox = document.createElement('div');
            feedbackBox.className = 'calmness-feedback-box';
            feedbackBox.style.background = isPositive 
                ? 'linear-gradient(135deg, #10b981, #059669)' 
                : 'linear-gradient(135deg, #f59e0b, #d97706)'; // 빨간색 대신 주황색 사용
            
            const emoji = isPositive ? '✅' : '💡'; // ❌ 대신 💡 사용
            
            feedbackBox.innerHTML = `
                <span class="calmness-feedback-emoji">${emoji}</span>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div class="calmness-feedback-text" style="font-size: 1.3rem; font-weight: bold;">${resultMessage}</div>
                    <div id="mathDiaryFeedbackText" class="calmness-feedback-text" style="font-size: 1rem; opacity: 0.95;"></div>
                </div>
            `;
            
            // 질문 영역에 추가
            questionText.appendChild(feedbackBox);
            
            // 피드백 문구를 타이핑 효과로 표시
            typeText('mathDiaryFeedbackText', feedbackText, () => {
                // 부모님 상담 관련 피드백이 있는 경우 추가 박스 생성
                if (encouragementFeedback) {
                    const encouragementBox = document.createElement('div');
                    encouragementBox.className = 'calmness-feedback-box';
                    encouragementBox.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)'; // 파란색 그라데이션
                    encouragementBox.style.marginTop = '1rem';
                    
                    encouragementBox.innerHTML = `
                        <span class="calmness-feedback-emoji">💡</span>
                        <div id="mathDiaryEncouragementText" class="calmness-feedback-text"></div>
                    `;
                    
                    // 질문 영역에 추가
                    questionText.appendChild(encouragementBox);
                    
                    // 부모님 상담 피드백도 타이핑 효과로 표시
                    setTimeout(() => {
                        typeText('mathDiaryEncouragementText', encouragementFeedback, () => {
                            // 모든 피드백 표시 완료 후 다음 질문으로 이동
                            setTimeout(() => {
                                if (callback) callback();
                            }, 2000);
                        });
                    }, 500);
                } else {
                    // 부모님 상담 피드백이 없는 경우
                    setTimeout(() => {
                        if (callback) callback();
                    }, 2000);
                }
            });
            
            // 아바타 애니메이션
            if (isPositive || encouragementFeedback) {
                triggerAvatarAnimation('celebrate');
            } else {
                triggerAvatarAnimation('wave');
            }
        }
        
        function showErrorNoteQuizFeedback(isPositive, feedbackText, correctAnswer, callback) {
            const questionText = document.getElementById('questionText');
            const optionsGrid = document.getElementById('optionsGrid');
            
            // 기존 내용 숨기기
            questionText.innerHTML = '';
            optionsGrid.innerHTML = '';
            
            // 피드백 박스 생성
            const feedbackBox = document.createElement('div');
            feedbackBox.className = 'calmness-feedback-box';
            feedbackBox.style.background = isPositive 
                ? 'linear-gradient(135deg, #10b981, #059669)' 
                : 'linear-gradient(135deg, #f59e0b, #d97706)'; // 빨간색 대신 주황색 사용
            
            const emoji = isPositive ? '✅' : '💡'; // ❌ 대신 💡 사용
            
            feedbackBox.innerHTML = `
                <span class="calmness-feedback-emoji">${emoji}</span>
                <div class="calmness-feedback-text">${feedbackText}</div>
            `;
            
            // 질문 영역에 추가
            questionText.appendChild(feedbackBox);
            
            // 아바타 애니메이션
            if (isPositive) {
                triggerAvatarAnimation('celebrate');
            } else {
                triggerAvatarAnimation('wave');
            }
            
            // 다음 질문으로 이동
            setTimeout(() => {
                if (callback) callback();
            }, 3000); // 3초 후 다음 질문
        }
        
        // 침착도 퀴즈 피드백 표시 함수 (오답노트 퀴즈와 동일한 스타일)
        function showCalmnessQuizFeedback(isPositive, feedbackText, correctAnswer, userAnswer, callback) {
            const questionText = document.getElementById('questionText');
            const optionsGrid = document.getElementById('optionsGrid');
            
            // 기존 내용 숨기기
            questionText.innerHTML = '';
            optionsGrid.innerHTML = '';
            
            // 피드백 박스 생성
            const feedbackBox = document.createElement('div');
            feedbackBox.className = 'calmness-feedback-box';
            feedbackBox.style.background = isPositive 
                ? 'linear-gradient(135deg, #10b981, #059669)' 
                : 'linear-gradient(135deg, #f59e0b, #d97706)'; // 빨간색 대신 주황색 사용
            
            const emoji = isPositive ? '✅' : '💡'; // ❌ 대신 💡 사용
            
            feedbackBox.innerHTML = `
                <span class="calmness-feedback-emoji">${emoji}</span>
                <div class="calmness-feedback-text">${feedbackText}</div>
            `;
            
            // 질문 영역에 추가
            questionText.appendChild(feedbackBox);
            
            // 아바타 애니메이션
            if (isPositive) {
                triggerAvatarAnimation('celebrate');
            } else {
                triggerAvatarAnimation('wave');
            }
            
            // 다음 질문으로 이동
            setTimeout(() => {
                if (callback) callback();
            }, 3000); // 3초 후 다음 질문
        }
        
        // 침착도 불일치 시 표시할 문구 42가지
        const calmnessMismatchPhrases = [
            '선택과 데이터가 불일치.. 자신의 침착도도 모르고 귀가검사를? 이러다가 나머지 공부각?',
            '침착도도 모르고 검사하네? 이러다가 공부 각이 안 보이겠어~',
            '자신의 침착도도 모르고 검사하는 거야? 이러다가 나머지 공부 각이 안 보이겠어',
            '선택한 침착도와 실제 데이터가 다르네? 자신의 상태도 모르고 검사하면 어떡해?',
            '침착도 불일치.. 자신의 상태를 제대로 파악하고 검사해야지? 이러다가 공부 각이 안 보이겠어',
            '자신의 침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '선택과 데이터가 불일치.. 침착도도 모르고 검사하면 공부 각이 안 보이겠어',
            '침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '자신의 침착도도 모르고 검사하는 거야? 이러다가 공부 각이 안 보이겠어',
            '선택한 침착도와 실제 데이터가 다르네? 자신의 상태도 모르고 검사하면 어떡해?',
            '침착도 불일치.. 자신의 상태를 제대로 파악하고 검사해야지? 이러다가 공부 각이 안 보이겠어',
            '자신의 침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '선택과 데이터가 불일치.. 침착도도 모르고 검사하면 공부 각이 안 보이겠어',
            '침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '자신의 침착도도 모르고 검사하는 거야? 이러다가 공부 각이 안 보이겠어',
            '선택한 침착도와 실제 데이터가 다르네? 자신의 상태도 모르고 검사하면 어떡해?',
            '침착도 불일치.. 자신의 상태를 제대로 파악하고 검사해야지? 이러다가 공부 각이 안 보이겠어',
            '자신의 침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '선택과 데이터가 불일치.. 침착도도 모르고 검사하면 공부 각이 안 보이겠어',
            '침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '자신의 침착도도 모르고 검사하는 거야? 이러다가 공부 각이 안 보이겠어',
            '선택한 침착도와 실제 데이터가 다르네? 자신의 상태도 모르고 검사하면 어떡해?',
            '침착도 불일치.. 자신의 상태를 제대로 파악하고 검사해야지? 이러다가 공부 각이 안 보이겠어',
            '자신의 침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '선택과 데이터가 불일치.. 침착도도 모르고 검사하면 공부 각이 안 보이겠어',
            '침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '자신의 침착도도 모르고 검사하는 거야? 이러다가 공부 각이 안 보이겠어',
            '선택한 침착도와 실제 데이터가 다르네? 자신의 상태도 모르고 검사하면 어떡해?',
            '침착도 불일치.. 자신의 상태를 제대로 파악하고 검사해야지? 이러다가 공부 각이 안 보이겠어',
            '자신의 침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '선택과 데이터가 불일치.. 침착도도 모르고 검사하면 공부 각이 안 보이겠어',
            '침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '자신의 침착도도 모르고 검사하는 거야? 이러다가 공부 각이 안 보이겠어',
            '선택한 침착도와 실제 데이터가 다르네? 자신의 상태도 모르고 검사하면 어떡해?',
            '침착도 불일치.. 자신의 상태를 제대로 파악하고 검사해야지? 이러다가 공부 각이 안 보이겠어',
            '자신의 침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '선택과 데이터가 불일치.. 침착도도 모르고 검사하면 공부 각이 안 보이겠어',
            '침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~',
            '자신의 침착도도 모르고 검사하는 거야? 이러다가 공부 각이 안 보이겠어',
            '선택한 침착도와 실제 데이터가 다르네? 자신의 상태도 모르고 검사하면 어떡해?',
            '침착도 불일치.. 자신의 상태를 제대로 파악하고 검사해야지? 이러다가 공부 각이 안 보이겠어',
            '자신의 침착도도 모르고 귀가검사를? 이러다가 나머지 공부 각이 안 보이겠어~'
        ];
        
        // 실제 데이터와 비교 표시
        function showDataComparison(questionId, userAnswer) {
            const comparisonDiv = document.getElementById('dataComparison');
            let comparisonHTML = '';
            
            if (questionId === 'calmness' && actualCalmness) {
                const match = userAnswer === actualCalmness;
                const randomMismatchPhrase = calmnessMismatchPhrases[Math.floor(Math.random() * calmnessMismatchPhrases.length)];
                comparisonHTML = `
                    <div class="data-comparison-item">
                        <span class="data-label">실제 침착도 데이터:</span>
                        <span class="data-value ${match ? 'data-match' : 'data-mismatch'}">
                            ${actualCalmness} (${actualCalmnessScore !== null ? actualCalmnessScore + '점' : '데이터 없음'})
                            ${match ? '✅ 일치' : '❌ 불일치'}
                        </span>
                    </div>
                    ${!match ? `
                    <div class="data-comparison-item" style="font-size: 0.9rem; color: var(--warning); margin-top: 0.5rem; font-style: italic;">
                        ${randomMismatchPhrase}
                    </div>
                    ` : ''}
                `;
            } else if (questionId === 'pomodoro_diary') {
                const match = userAnswer === actualPomodoroUsage;
                comparisonHTML = `
                    <div class="data-comparison-item">
                        <span class="data-label">실제 포모도르 사용 데이터:</span>
                        <span class="data-value ${match ? 'data-match' : 'data-mismatch'}">
                            ${actualPomodoroUsage}
                            ${match ? '✅ 일치' : '❌ 불일치'}
                        </span>
                    </div>
                    ${pomodoroTotalCount > 0 ? `
                    <div class="data-comparison-item" style="margin-top: 0.5rem;">
                        <span class="data-label">포모도르 일기 작성 (오늘 기준):</span>
                        <span class="data-value">총 ${pomodoroTotalCount}개 작성, 만족도 평균 ${pomodoroSatisfactionAvg.toFixed(2)} (${pomodoroSatisfactionCount}개 평가됨)</span>
                    </div>
                    ` : ''}
                `;
            } else if (questionId === 'focus_help') {
                const totalFocusHelp = focusHelpVeryHelpful + focusHelpHelpful + focusHelpNormal + focusHelpNotHelpful;
                comparisonHTML = `
                    <div class="data-comparison-item">
                        <span class="data-label">오늘 포모도르 일기 집중 도움 여부 (실제 데이터):</span>
                        <span class="data-value">
                            ${totalFocusHelp > 0 ? `
                                매우도움: ${focusHelpVeryHelpful}개, 도움: ${focusHelpHelpful}개, 보통: ${focusHelpNormal}개, 별로: ${focusHelpNotHelpful}개
                            ` : '데이터 없음'}
                        </span>
                    </div>
                `;
            } else if (questionId === 'error_note') {
                let actualStatus = '전혀 안 밀렸어요';
                if (errorNoteBacklogCount === 0 && actualErrorNoteCount === 0) {
                    actualStatus = '오답노트 안 써요';
                } else if (errorNoteBacklogCount > 5) {
                    actualStatus = '많이 밀렸어요';
                } else if (errorNoteBacklogCount > 0) {
                    actualStatus = '조금 밀렸어요';
                }
                
                comparisonHTML = `
                    <div class="data-comparison-item">
                        <span class="data-label">실제 오답노트 밀림 상태:</span>
                        <span class="data-value">
                            ${errorNoteBacklogCount}개 밀림 (${actualStatus})
                        </span>
                    </div>
                `;
            }
            
            if (comparisonHTML) {
                comparisonDiv.innerHTML = comparisonHTML;
                comparisonDiv.classList.remove('hidden');
            }
        }
        
        // 진행 상황 업데이트
        function updateProgress() {
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            // 첫 번째 질문(오늘 하루 어땠어?) 포함하여 계산
            const totalQuestions = 1 + allQuestions.length;
            const currentProgress = currentQuestion === -1 ? 1 : currentQuestion + 2;
            const progress = (currentProgress / totalQuestions) * 100;
            
            document.getElementById('progressText').textContent = `${currentProgress} / ${totalQuestions}`;
            document.getElementById('progressFill').style.width = `${progress}%`;
        }
        
        // 리포트 생성
        function generateReport() {
            // 리포트 HTML 생성 (showReport 함수에서 생성하는 HTML을 먼저 생성)
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            
            // 리포트 HTML 생성 로직 (showReport 함수의 내용을 재사용)
            let reportHTML = generateReportHTML();
            
            // 리포트 HTML을 reportPreview에 먼저 표시
            const reportPreview = document.getElementById('reportPreview');
            if (reportPreview) {
                reportPreview.innerHTML = reportHTML;
                reportPreview.classList.remove('hidden');
            }
            
            // 리포트 섹션에도 저장
            document.getElementById('reportSection').innerHTML = reportHTML;
            
            // 그래프 렌더링 후 리포트 저장
            setTimeout(() => {
                drawCalmnessChart().then(() => {
                    setTimeout(() => {
                        chartRenderComplete = true;
                        // 업데이트된 리포트 HTML 가져오기
                        const updatedReportHTML = reportPreview ? reportPreview.innerHTML : reportHTML;
                        
                        // AJAX로 리포트 저장 (HTML 포함)
                        const formData = new FormData();
                        formData.append('action', 'save_report');
                        formData.append('responses', JSON.stringify(responses));
                        formData.append('report_html', updatedReportHTML);
                        
                        fetch('', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('리포트 저장 완료:', data.report_id);

                                // 저장 성공 즉시 버튼 활성화 (타이밍 이슈 방지)
                                const viewBtn = document.getElementById('viewReportBtn');
                                if (viewBtn) {
                                    viewBtn.disabled = false;
                                    viewBtn.style.opacity = '1';
                                    viewBtn.style.cursor = 'pointer';
                                    viewBtn.title = '저장된 리포트 보기';
                                }

                                // 추가로 500ms 후 DB에서 재확인 (확실성 보장)
                                setTimeout(() => {
                                    checkAndShowReportLink();
                                }, 500);
                            } else {
                                console.error('리포트 저장 실패:', data.message || '알 수 없는 오류');
                                console.error('디버그 정보:', data.debug || '디버그 정보 없음');
                                // 디버그 정보를 상세히 출력
                                if (data.debug) {
                                    console.group('리포트 저장 실패 상세 정보');
                                    Object.keys(data.debug).forEach(key => {
                                        console.log(key + ':', data.debug[key]);
                                    });
                                    console.groupEnd();
                                }
                                alert('리포트 저장에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
                            }
                        })
                        .catch(error => {
                            console.error('리포트 저장 중 네트워크 오류:', error);
                            alert('리포트 저장 중 오류가 발생했습니다: ' + error.message);
                        });
                    }, 500);
                }).catch(() => {
                    setTimeout(() => {
                        chartRenderComplete = true;
                        const updatedReportHTML = reportPreview ? reportPreview.innerHTML : reportHTML;
                        
                        const formData = new FormData();
                        formData.append('action', 'save_report');
                        formData.append('responses', JSON.stringify(responses));
                        formData.append('report_html', updatedReportHTML);
                        
                        fetch('', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('리포트 저장 완료:', data.report_id);

                                // 저장 성공 즉시 버튼 활성화 (타이밍 이슈 방지)
                                const viewBtn = document.getElementById('viewReportBtn');
                                if (viewBtn) {
                                    viewBtn.disabled = false;
                                    viewBtn.style.opacity = '1';
                                    viewBtn.style.cursor = 'pointer';
                                    viewBtn.title = '저장된 리포트 보기';
                                }

                                // 추가로 500ms 후 DB에서 재확인 (확실성 보장)
                                setTimeout(() => {
                                    checkAndShowReportLink();
                                }, 500);
                            } else {
                                console.error('리포트 저장 실패:', data.message || '알 수 없는 오류');
                                console.error('디버그 정보:', data.debug || '디버그 정보 없음');
                                // 디버그 정보를 상세히 출력
                                if (data.debug) {
                                    console.group('리포트 저장 실패 상세 정보');
                                    Object.keys(data.debug).forEach(key => {
                                        console.log(key + ':', data.debug[key]);
                                    });
                                    console.groupEnd();
                                }
                                alert('리포트 저장에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
                            }
                        })
                        .catch(error => {
                            console.error('리포트 저장 중 네트워크 오류:', error);
                            alert('리포트 저장 중 오류가 발생했습니다: ' + error.message);
                        });
                    }, 300);
                });
            }, 200);
        }
        
        // 리포트 HTML 생성 함수 (showReport 함수의 내용을 별도 함수로 분리)
        function generateReportHTML() {
            const allQuestions = [...requiredQuestions, ...selectedRandomQuestions];
            
            // 벌칙 표시 (포모도르 2개 이하 또는 침착도 80% 이하)
            const showPenalty = (pomodoroTotalCount <= 2) || (actualCalmnessLevel !== null && actualCalmnessLevel < 80);
            
            let reportHTML = `
                <div class="report" style="margin: 0 auto; max-width: 800px; padding: 2rem; position: relative; color: var(--text-primary);">
                    <h2 style="text-align: center; margin-bottom: 1.5rem; color: var(--text-primary);">📋 귀가검사 리포트</h2>
                    <div class="report-info" style="text-align: center; margin-bottom: 1.5rem;">
                        <p style="color: var(--text-secondary);">${new Date().toLocaleDateString('ko-KR', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
            `;
            
            // 벌칙 표시 (제목과 날짜 바로 아래)
            if (showPenalty) {
                reportHTML += `
                    <div style="margin-top: 1rem; margin-bottom: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, #ff6b6b, #ee5a6f); border-radius: 12px; border: 3px solid #ff4757; box-shadow: 0 8px 24px rgba(255, 71, 87, 0.3); text-align: center;">
                        <h2 style="font-size: 2rem; font-weight: bold; color: white; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                            ⚠️ 지면평가 하나 더 !
                        </h2>
                        <div style="margin-top: 0.5rem; font-size: 1rem; color: rgba(255,255,255,0.9);">
                            ${pomodoroTotalCount <= 2 ? '포모도르 일기 작성이 부족합니다 (' + pomodoroTotalCount + '개)' : ''}
                            ${(pomodoroTotalCount <= 2 && actualCalmnessLevel !== null && actualCalmnessLevel < 80) ? ' / ' : ''}
                            ${actualCalmnessLevel !== null && actualCalmnessLevel < 80 ? '침착도가 80% 미만입니다 (' + actualCalmnessLevel + '%)' : ''}
                        </div>
                    </div>
                `;
            }
            
            // 실제 학습 데이터 분석 섹션
            reportHTML += `
                <div class="actual-data-section" style="margin-top: 1.5rem; padding: 1rem; background-color: var(--bg-secondary); border-radius: 8px; border: 1px solid var(--accent);">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--accent); margin-bottom: 1rem;">📈 실제 학습 데이터 분석</h3>
                    <div style="display: grid; gap: 0.5rem; color: var(--text-primary);">
                        <p style="color: var(--text-primary);"><strong>침착도:</strong> ${actualCalmnessGrade ? actualCalmnessGrade + ' (' + (actualCalmnessLevel || 'N/A') + '%)' : '데이터 없음'}</p>
                        <p style="color: var(--text-primary);"><strong>수학일기 사용:</strong> ${actualPomodoroUsage}</p>
                        <p style="color: var(--text-primary);"><strong>오답노트 밀림:</strong> ${errorNoteBacklogCount}개 밀림</p>
                        <p style="color: var(--text-primary);"><strong>오답노트 활동:</strong> 최근 ${actualErrorNoteCount}개 작성</p>
                        ${responses['focus_help'] ? `<p style="color: var(--text-primary);"><strong>집중 도움 여부 (선택):</strong> ${responses['focus_help']}</p>` : ''}
                    </div>
                </div>
            `;
            
            // 침착도 그래프 섹션
            reportHTML += `
                <div class="engagement-graph-section" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">📊 최근 1주일 침착도 그래프</h3>
                    <div id="calmnessChartContainer" style="position: relative; width: 100%; height: 400px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-secondary); padding: 1rem;">
                        <canvas id="calmnessChart"></canvas>
                        <div id="calmnessNoData" style="display: none; text-align: center; padding: 2rem; color: var(--text-secondary);">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                            <div>최근 1주일 데이터가 없습니다.</div>
                        </div>
                    </div>
                </div>
            `;
            
            // 응답 내용 섹션
            reportHTML += '<div style="margin-top: 1.5rem; color: var(--text-primary);"><h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">📝 응답 내용</h3>';
            
            // 첫 번째 질문 표시
            if (responses['daily_mood']) {
                const themeQuestion = window.selectedDailyMoodTheme ? window.selectedDailyMoodTheme.question : '오늘 하루는 어떤 하루였어?';
                reportHTML += `
                    <div class="response-item" style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                        <p class="response-question" style="font-weight: 600; margin-bottom: 0.5rem;">${themeQuestion}</p>
                        <p class="response-answer" style="margin-bottom: 0.75rem;">→ ${responses['daily_mood']}</p>
                    </div>
                `;
            }
            
            // 나머지 질문들 표시
            allQuestions.forEach(q => {
                if (responses[q.id]) {
                    const userAnswer = responses[q.id];
                    reportHTML += `
                        <div class="response-item" style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                            <p class="response-question" style="font-weight: 600; margin-bottom: 0.5rem;">${q.text}</p>
                            <p class="response-answer" style="margin-bottom: 0.75rem;">→ ${userAnswer}</p>
                    `;
                    
                    // 실제 데이터 비교 정보 표시
                    if (q.hasData) {
                        let comparisonHTML = '';
                        
                        if (q.id === 'calmness' && actualCalmness) {
                            const match = userAnswer === actualCalmness;
                            const randomMismatchPhrase = calmnessMismatchPhrases[Math.floor(Math.random() * calmnessMismatchPhrases.length)];
                            comparisonHTML = `
                                <div style="background: var(--bg-secondary); padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem; font-size: 0.9rem;">
                                    <div style="margin-bottom: 0.25rem;">
                                        <strong>실제 데이터:</strong> <span style="color: ${match ? 'var(--success)' : 'var(--danger)'};">${actualCalmness} (${actualCalmnessScore !== null ? actualCalmnessScore + '점' : '데이터 없음'}) ${match ? '✅' : '❌'}</span>
                                    </div>
                                    ${!match ? `<div style="color: var(--warning); font-style: italic; margin-top: 0.25rem;">${randomMismatchPhrase}</div>` : ''}
                                </div>
                            `;
                        } else if (q.id === 'pomodoro_diary') {
                            const match = userAnswer === actualPomodoroUsage;
                            comparisonHTML = `
                                <div style="background: var(--bg-secondary); padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem; font-size: 0.9rem;">
                                    <div style="margin-bottom: 0.25rem;">
                                        <strong>실제 데이터:</strong> <span style="color: ${match ? 'var(--success)' : 'var(--danger)'};">${actualPomodoroUsage} ${match ? '✅' : '❌'}</span>
                                    </div>
                                    ${pomodoroTotalCount > 0 ? `
                                    <div style="margin-top: 0.25rem; color: var(--text-secondary);">
                                        포모도르 일기 작성: 총 ${pomodoroTotalCount}개 작성, 만족도 평균 ${pomodoroSatisfactionAvg.toFixed(2)} (${pomodoroSatisfactionCount}개 평가됨)
                                    </div>
                                    ` : ''}
                                </div>
                            `;
                        } else if (q.id === 'error_note') {
                            let actualStatus = '전혀 안 밀렸어요';
                            if (errorNoteBacklogCount === 0 && actualErrorNoteCount === 0) {
                                actualStatus = '오답노트 안 써요';
                            } else if (errorNoteBacklogCount > 5) {
                                actualStatus = '많이 밀렸어요';
                            } else if (errorNoteBacklogCount > 0) {
                                actualStatus = '조금 밀렸어요';
                            }
                            
                            comparisonHTML = `
                                <div style="background: var(--bg-secondary); padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem; font-size: 0.9rem;">
                                    <div>
                                        <strong>실제 데이터:</strong> ${errorNoteBacklogCount}개 밀림 (${actualStatus})
                                    </div>
                                </div>
                            `;
                        }
                        
                        if (comparisonHTML) {
                            reportHTML += comparisonHTML;
                        }
                    }
                    
                    // 피드백 메시지 표시
                    if (q.followUp && q.followUp[userAnswer]) {
                        reportHTML += `
                            <div style="background: rgba(99, 102, 241, 0.1); padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem; font-size: 0.9rem; color: var(--text-primary); border-left: 3px solid var(--accent);">
                                💬 ${q.followUp[userAnswer]}
                            </div>
                        `;
                    }
                    
                    reportHTML += `</div>`;
                }
            });
            
            reportHTML += `</div>`;
            
            // 포모도르 수학일기 시각화 (4주 기준)
            const totalFocusHelp = fourWeeksFocusHelpVeryHelpful + fourWeeksFocusHelpHelpful + fourWeeksFocusHelpNormal + fourWeeksFocusHelpNotHelpful;
            if (totalFocusHelp > 0) {
                const maxValue = Math.max(fourWeeksFocusHelpVeryHelpful, fourWeeksFocusHelpHelpful, fourWeeksFocusHelpNormal, fourWeeksFocusHelpNotHelpful);
                
                reportHTML += `
                    <div class="focus-help-visualization" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">📝 포모도르 수학일기 (4주 기준)</h3>
                        <div style="background: var(--bg-secondary); border-radius: 8px; border: 1px solid var(--border); padding: 1.5rem;">
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="flex: 0 0 100px; font-size: 0.9rem; color: var(--text-secondary);">매우도움</div>
                                    <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="flex: 1; height: 32px; background: var(--border); border-radius: 4px; overflow: hidden; position: relative;">
                                            <div style="height: 100%; width: ${maxValue > 0 ? (fourWeeksFocusHelpVeryHelpful / maxValue * 100) : 0}%; background: linear-gradient(90deg, #10b981, #34d399); transition: width 0.5s ease; display: flex; align-items: center; justify-content: flex-end; padding-right: 0.5rem;">
                                                ${fourWeeksFocusHelpVeryHelpful > 0 ? `<span style="color: white; font-weight: 600; font-size: 0.85rem;">${fourWeeksFocusHelpVeryHelpful}</span>` : ''}
                                            </div>
                                        </div>
                                        <div style="flex: 0 0 40px; text-align: right; font-size: 0.9rem; color: var(--text-primary); font-weight: 500;">${fourWeeksFocusHelpVeryHelpful}개</div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="flex: 0 0 100px; font-size: 0.9rem; color: var(--text-secondary);">도움</div>
                                    <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="flex: 1; height: 32px; background: var(--border); border-radius: 4px; overflow: hidden; position: relative;">
                                            <div style="height: 100%; width: ${maxValue > 0 ? (fourWeeksFocusHelpHelpful / maxValue * 100) : 0}%; background: linear-gradient(90deg, #3b82f6, #60a5fa); transition: width 0.5s ease; display: flex; align-items: center; justify-content: flex-end; padding-right: 0.5rem;">
                                                ${fourWeeksFocusHelpHelpful > 0 ? `<span style="color: white; font-weight: 600; font-size: 0.85rem;">${fourWeeksFocusHelpHelpful}</span>` : ''}
                                            </div>
                                        </div>
                                        <div style="flex: 0 0 40px; text-align: right; font-size: 0.9rem; color: var(--text-primary); font-weight: 500;">${fourWeeksFocusHelpHelpful}개</div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="flex: 0 0 100px; font-size: 0.9rem; color: var(--text-secondary);">보통</div>
                                    <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="flex: 1; height: 32px; background: var(--border); border-radius: 4px; overflow: hidden; position: relative;">
                                            <div style="height: 100%; width: ${maxValue > 0 ? (fourWeeksFocusHelpNormal / maxValue * 100) : 0}%; background: linear-gradient(90deg, #f59e0b, #fbbf24); transition: width 0.5s ease; display: flex; align-items: center; justify-content: flex-end; padding-right: 0.5rem;">
                                                ${fourWeeksFocusHelpNormal > 0 ? `<span style="color: white; font-weight: 600; font-size: 0.85rem;">${fourWeeksFocusHelpNormal}</span>` : ''}
                                            </div>
                                        </div>
                                        <div style="flex: 0 0 40px; text-align: right; font-size: 0.9rem; color: var(--text-primary); font-weight: 500;">${fourWeeksFocusHelpNormal}개</div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="flex: 0 0 100px; font-size: 0.9rem; color: var(--text-secondary);">별로</div>
                                    <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="flex: 1; height: 32px; background: var(--border); border-radius: 4px; overflow: hidden; position: relative;">
                                            <div style="height: 100%; width: ${maxValue > 0 ? (fourWeeksFocusHelpNotHelpful / maxValue * 100) : 0}%; background: linear-gradient(90deg, #ef4444, #f87171); transition: width 0.5s ease; display: flex; align-items: center; justify-content: flex-end; padding-right: 0.5rem;">
                                                ${fourWeeksFocusHelpNotHelpful > 0 ? `<span style="color: white; font-weight: 600; font-size: 0.85rem;">${fourWeeksFocusHelpNotHelpful}</span>` : ''}
                                            </div>
                                        </div>
                                        <div style="flex: 0 0 40px; text-align: right; font-size: 0.9rem; color: var(--text-primary); font-weight: 500;">${fourWeeksFocusHelpNotHelpful}개</div>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); text-align: center; font-size: 0.85rem; color: var(--text-secondary);">
                                총 ${totalFocusHelp}개 평가
                            </div>
                        </div>
                    </div>
                `;
            }
            
            reportHTML += `</div>`;
            
            return reportHTML;
        }
        
        // 리포트 표시
        function showReport(reportId) {
            reportGenerated = true;
            
            // generateReportHTML 함수를 사용하여 리포트 HTML 생성
            let reportHTML = generateReportHTML();
            
            // 리포트 ID 추가
            reportHTML = reportHTML.replace(
                /(<div class="report-info"[^>]*>[\s\S]*?)(<\/div>)/,
                `$1<p style="color: var(--text-secondary);">리포트 ID: ${reportId}</p>$2`
            );
            
            // 완료 화면의 리포트 미리보기 영역에 표시
            const reportPreview = document.getElementById('reportPreview');
            if (reportPreview) {
                reportPreview.innerHTML = reportHTML;
                reportPreview.classList.remove('hidden');
            }
            
            // 리포트 섹션에도 저장 (인쇄용)
            document.getElementById('reportSection').innerHTML = reportHTML;
            
            triggerAvatarAnimation('celebrate');
            
            // 그래프 렌더링 완료 플래그 초기화
            chartRenderComplete = false;
            
            // 리포트 섹션에 그래프가 추가된 후 그래프 그리기
            setTimeout(() => {
                drawCalmnessChart().then(() => {
                    // 그래프 렌더링 완료 후 추가 대기 (브라우저 리플로우/리페인트 완료 보장)
                    setTimeout(() => {
                        chartRenderComplete = true;
                    }, 500); // 500ms 추가 대기
                }).catch(() => {
                    // 그래프 렌더링 실패 시에도 캡처 진행 (데이터 없음)
                    setTimeout(() => {
                        chartRenderComplete = true;
                    }, 300);
                });
            }, 200); // 초기 대기 시간도 증가
        }
        
        // 단계 표시
        function showStep(stepId) {
            document.querySelectorAll('.step').forEach(step => {
                step.classList.add('hidden');
            });
            document.getElementById(stepId).classList.remove('hidden');
            
            // 완료 화면일 때 리포트 조회 링크 표시
            if (stepId === 'completeStep') {
                checkAndShowReportLink();
            }
        }
        
        // 리포트 조회 링크 표시 여부 확인 (리포트가 없으면 버튼 비활성화)
        function checkAndShowReportLink() {
            const formData = new FormData();
            formData.append('action', 'get_recent_reports');
            formData.append('limit', '1');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const reportLink = document.getElementById('reportViewLink');
                const viewBtn = document.getElementById('viewReportBtn');
                
                if (reportLink && viewBtn) {
                    // 버튼이 이미 활성화되어 있으면 유지 (저장 직후 타이밍 이슈 방지)
                    const isAlreadyEnabled = !viewBtn.disabled && viewBtn.style.opacity !== '0.5';
                    
                    if (data.success && data.reports && data.reports.length > 0) {
                        // 리포트가 있으면 활성화
                        viewBtn.disabled = false;
                        viewBtn.style.opacity = '1';
                        viewBtn.style.cursor = 'pointer';
                        viewBtn.title = '저장된 리포트 보기';
                    } else if (!isAlreadyEnabled) {
                        // 리포트가 없고 버튼이 이미 활성화되어 있지 않을 때만 비활성화
                        viewBtn.disabled = true;
                        viewBtn.style.opacity = '0.5';
                        viewBtn.style.cursor = 'not-allowed';
                        viewBtn.title = '저장된 리포트가 없습니다.';
                    }
                    // 이미 활성화되어 있으면 그대로 유지
                }
            })
            .catch(error => {
                console.error('리포트 확인 중 오류:', error);
            });
        }
        
        // 페이지 로드 시 리포트 링크 확인
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                checkAndShowReportLink();
            });
        } else {
            // 이미 로드된 경우 즉시 실행
            checkAndShowReportLink();
        }
        
        // 대시보드로 이동 (전역 함수로 명시적 할당)
        function goToDashboard() {
            const dashboardUrl = `dashboard.php?userid=${studentId}`;
            window.location.href = dashboardUrl;
        }
        
        // 전역 스코프에 명시적으로 할당
        window.goToDashboard = goToDashboard;
        
        // 버튼에 이벤트 리스너 추가 (onclick 대신 사용)
        document.addEventListener('DOMContentLoaded', function() {
            const viewReportBtn = document.getElementById('viewReportBtn');
            if (viewReportBtn) {
                viewReportBtn.addEventListener('click', function() {
                    goToDashboard();
                });
            }
        });
        
        // 리포트 ID로 리포트 불러오기
        async function loadReportById(reportId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_report');
                formData.append('report_id', reportId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.report_html) {
                    displayReport(data.report_html);
                } else {
                    alert(data.message || '리포트를 불러올 수 없습니다.');
                }
            } catch (error) {
                console.error('리포트 불러오기 오류:', error);
                alert('리포트를 불러오는 중 오류가 발생했습니다.');
            }
        }
        
        // 리포트 표시 함수 (공통)
        function displayReport(reportHTML) {
            // 완료 메시지 숨기기
            const completeMessage = document.getElementById('completeMessage');
            if (completeMessage) {
                completeMessage.style.display = 'none';
            }
            
            // 리포트 HTML에서 중복된 학생 이름 제거 (report-info 섹션 내의 이름)
            // report-info div 내부의 첫 번째 p 태그(이름)를 제거
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = reportHTML;
            const reportInfoDivs = tempDiv.querySelectorAll('.report-info');
            reportInfoDivs.forEach(div => {
                const paragraphs = div.querySelectorAll('p');
                if (paragraphs.length > 0) {
                    // 첫 번째 p 태그가 이름일 가능성이 높으므로 제거
                    const firstP = paragraphs[0];
                    const text = firstP.textContent.trim();
                    // 이름이 포함된 p 태그인지 확인 (이모지나 학생 이름이 포함된 경우)
                    if (text.includes('✨') || text.length < 20) {
                        firstP.remove();
                    }
                }
            });
            reportHTML = tempDiv.innerHTML;
            
            // 리포트 상단에 학생 이름과 성장 메시지 추가
            const growthMsg = `${studentName}, 이 만큼 성장한 날 ! 🎉`;
            
            // 통계 리포트 링크 (이름과 같은 높이)
            const dashboardLink = `dashboard.php?userid=${studentId}`;
            const statsLink = `
                <a href="${dashboardLink}" 
                   style="display: inline-block; padding: 0.5rem 1rem; background: var(--accent); color: white; text-decoration: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.2s ease; vertical-align: middle;"
                   onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.25)'"
                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.15)'">
                    📊 통계 리포트
                </a>
            `;
            
            const nameHeader = `
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 2rem; padding: 1.5rem; border: 2px solid var(--accent); border-radius: 12px; background: transparent; position: relative; gap: 1rem;">
                    <p style="color: var(--text-primary); font-size: 1.5rem; font-weight: 600; margin: 0; flex: 1; text-align: center;">${growthMsg}</p>
                    <div style="position: absolute; right: 1rem;">
                        ${statsLink}
                    </div>
                </div>
            `;
            
            // 리포트 시작 부분에 이름 헤더 추가 (기존 헤더가 있으면 교체)
            if (reportHTML.includes('<div class="report"')) {
                reportHTML = reportHTML.replace(/<div class="report"[^>]*>/, '<div class="report" style="margin: 0 auto; max-width: 800px; padding: 2rem; position: relative; color: var(--text-primary);">' + nameHeader);
            } else {
                reportHTML = nameHeader + reportHTML;
            }
            
            // 리포트 표시
            const reportPreview = document.getElementById('reportPreview');
            if (reportPreview) {
                reportPreview.innerHTML = reportHTML;
                reportPreview.classList.remove('hidden');
                
                // 완료 화면으로 이동
                showStep('completeStep');
                
                // 그래프 렌더링 (침착도 그래프가 있는 경우)
                setTimeout(() => {
                    const chartContainer = reportPreview.querySelector('#calmnessChartContainer');
                    const chartCanvas = reportPreview.querySelector('#calmnessChart');
                    
                    if (chartContainer && chartCanvas) {
                        // 기존 차트가 있으면 제거
                        if (calmnessChart) {
                            try {
                                calmnessChart.destroy();
                            } catch (e) {
                                console.warn('기존 차트 제거 중 오류:', e);
                            }
                            calmnessChart = null;
                        }
                        
                        // 리포트 내부의 차트를 메인 차트로 임시 설정
                        const originalContainer = document.getElementById('calmnessChartContainer');
                        const originalCanvas = document.getElementById('calmnessChart');
                        
                        // 원본 요소가 있으면 임시로 숨김
                        if (originalContainer && originalContainer !== chartContainer) {
                            originalContainer.style.display = 'none';
                        }
                        if (originalCanvas && originalCanvas !== chartCanvas) {
                            originalCanvas.style.display = 'none';
                        }
                        
                        // 리포트 내부 요소를 메인 ID로 임시 변경
                        chartContainer.id = 'calmnessChartContainer';
                        chartCanvas.id = 'calmnessChart';
                        
                        // 그래프 다시 그리기
                        drawCalmnessChart().then(() => {
                            console.log('저장된 리포트의 그래프 렌더링 완료');
                        }).catch(error => {
                            console.error('그래프 렌더링 오류:', error);
                        });
                    } else {
                        console.log('그래프 컨테이너를 찾을 수 없습니다.');
                    }
                }, 500);
                
                // 스크롤을 리포트로 이동
                setTimeout(() => {
                    reportPreview.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 800);
            }
        }
        
        // 최근 리포트 불러오기
        async function loadLatestReport() {
            const btn = document.getElementById('viewReportBtn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '⏳ 불러오는 중...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_latest_report');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.report_html) {
                    // URL에 reportid 파라미터 추가
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('reportid', data.report_id);
                    window.history.pushState({}, '', currentUrl);
                    
                    // 리포트 표시
                    displayReport(data.report_html);
                    
                    btn.textContent = '✅ 불러오기 완료';
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.disabled = false;
                    }, 2000);
                } else {
                    alert(data.message || '리포트를 불러올 수 없습니다.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('리포트 불러오기 오류:', error);
                alert('리포트를 불러오는 중 오류가 발생했습니다.');
                btn.textContent = originalText;
                btn.disabled = false;
            }
        }
        
        // 완료 화면 표시
        function showCompletionScreen() {
            const messages = [
                `대박! ${studentName}, 오늘 진짜 열심히 했네! 🎆`,
                `최고야! ★ ${studentName}, 이 만큼 성장한 하루! 🎊`,
                `짱이야! ${studentName}, 오늘 공부 완전 정복! 🚀`
            ];
            
            const randomMsg = messages[Math.floor(Math.random() * messages.length)];
            typeText('completeMessage', randomMsg);
            triggerAvatarAnimation('celebrate');
            
            // 자동으로 리포트 생성 및 표시
            setTimeout(() => {
                generateReport();
            }, 1000);
        }
        
        // 시간 간격 압축 함수: 1시간 이상인 구간을 1시간으로 압축
        function compressTimeGaps(data) {
            if (data.length <= 1) return data;
            
            const compressedData = [data[0]]; // 첫 번째 포인트는 그대로 유지
            const ONE_HOUR_MS = 60 * 60 * 1000; // 1시간 (ms)
            const COMPRESSED_GAP_MS = 60 * 60 * 1000; // 1시간 (ms)
            
            for (let i = 1; i < data.length; i++) {
                const prevPoint = compressedData[compressedData.length - 1];
                const currentPoint = data[i];
                const timeGap = currentPoint.x - prevPoint.x;
                
                if (timeGap >= ONE_HOUR_MS) {
                    // 1시간 이상 간격이면 1시간으로 압축
                    const compressedPoint = {
                        ...currentPoint,
                        x: prevPoint.x + COMPRESSED_GAP_MS,
                        originalX: currentPoint.x // 원본 타임스탬프 보관 (툴팁용)
                    };
                    compressedData.push(compressedPoint);
                } else {
                    // 1시간 미만이면 그대로 유지
                    compressedData.push(currentPoint);
                }
            }
            
            return compressedData;
        }
        
        // 날짜별로 데이터가 있는 날만 필터링 (수업 없는 날 제거)
        function filterDaysWithData(data) {
            if (data.length === 0) return data;
            
            // 날짜별로 그룹화
            const daysWithData = new Set();
            data.forEach(point => {
                const date = new Date(point.x);
                const dayKey = date.getFullYear() + '-' + 
                              (date.getMonth() + 1).toString().padStart(2, '0') + '-' + 
                              date.getDate().toString().padStart(2, '0');
                daysWithData.add(dayKey);
            });
            
            // 데이터가 있는 날만 반환
            return data;
        }
        
        // 침착도 그래프 그리기 함수 (Promise 반환)
        function drawCalmnessChart() {
            return new Promise((resolve, reject) => {
                const chartContainer = document.getElementById('calmnessChartContainer');
                const canvas = document.getElementById('calmnessChart');
                const noDataDiv = document.getElementById('calmnessNoData');
                
                // 디버깅: 데이터 확인
                console.log('CALMNESS_DEBUG: calmnessWeekData:', calmnessWeekData);
                console.log('CALMNESS_DEBUG: Data length:', calmnessWeekData ? calmnessWeekData.length : 0);
                
                if (!chartContainer || !canvas) {
                    console.warn('침착도 그래프 컨테이너를 찾을 수 없습니다.');
                    reject('Chart container not found');
                    return;
                }
                
                // 데이터가 없으면 메시지 표시
                if (!calmnessWeekData || calmnessWeekData.length === 0) {
                    console.warn('CALMNESS_DEBUG: No data available for chart');
                    if (noDataDiv) {
                        noDataDiv.style.display = 'block';
                    }
                    if (canvas) {
                        canvas.style.display = 'none';
                    }
                    resolve(); // 데이터 없어도 완료로 처리
                    return;
                }
            
            if (noDataDiv) {
                noDataDiv.style.display = 'none';
            }
            if (canvas) {
                canvas.style.display = 'block';
            }
            
            // 기존 차트가 있으면 제거
            if (calmnessChart) {
                try {
                    calmnessChart.destroy();
                } catch (e) {
                    console.warn('기존 차트 제거 중 오류:', e);
                }
            }
            
            const ctx = canvas.getContext('2d');
            
            // 매우 고해상도 렌더링을 위한 캔버스 크기 설정
            const dpr = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();
            // 고해상도 렌더링을 위해 더 높은 해상도 사용
            const scaleMultiplier = Math.max(3, dpr * 2);
            canvas.width = rect.width * scaleMultiplier;
            canvas.height = rect.height * scaleMultiplier;
            ctx.scale(scaleMultiplier, scaleMultiplier);
            canvas.style.width = rect.width + 'px';
            canvas.style.height = rect.height + 'px';
            
            // 고품질 렌더링 설정
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            
            // 데이터 처리: 시간 간격 압축
            const compressedData = compressTimeGaps(calmnessWeekData);
            
            // 시간 범위 계산 (압축된 데이터 기준)
            const timestamps = compressedData.map(d => d.x);
            const minTimestamp = Math.min(...timestamps);
            const maxTimestamp = Math.max(...timestamps);
            const timeRangeHours = (maxTimestamp - minTimestamp) / (3600 * 1000);
            
            // 시간 단위 결정
            let timeUnit, displayFormat, maxTicksLimit;
            if (timeRangeHours <= 24) {
                timeUnit = 'hour';
                displayFormat = 'HH:mm';
                maxTicksLimit = Math.min(12, compressedData.length);
            } else {
                timeUnit = 'day';
                displayFormat = 'MMM dd';
                // 데이터가 있는 날만 카운트
                const daysWithData = new Set();
                compressedData.forEach(point => {
                    const date = new Date(point.x);
                    const dayKey = date.getFullYear() + '-' + 
                                  (date.getMonth() + 1).toString().padStart(2, '0') + '-' + 
                                  date.getDate().toString().padStart(2, '0');
                    daysWithData.add(dayKey);
                });
                maxTicksLimit = Math.min(daysWithData.size, 7);
            }
            
            calmnessChart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: '침착도 수준',
                        data: compressedData,
                        parsing: false,
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.25,
                        pointBackgroundColor: 'rgb(102, 126, 234)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000, // 애니메이션 duration
                        onComplete: function() {
                            // 애니메이션 완료 후 약간의 추가 대기 (렌더링 완전히 완료되도록)
                            setTimeout(() => {
                                chartRenderComplete = true;
                                resolve();
                            }, 300);
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgb(102, 126, 234)',
                            borderWidth: 1,
                            callbacks: {
                                title: function(context) {
                                    const dataPoint = compressedData[context[0].dataIndex];
                                    // 원본 타임스탬프가 있으면 사용, 없으면 압축된 타임스탬프 사용
                                    const timestamp = dataPoint.originalX || dataPoint.x;
                                    const date = new Date(timestamp);
                                    const dateStr = date.toLocaleString('ko-KR', { 
                                        hour12: false,
                                        year: 'numeric',
                                        month: '2-digit',
                                        day: '2-digit',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                    // 압축된 경우 표시
                                    if (dataPoint.originalX) {
                                        return dateStr + ' (시간 간격 압축됨)';
                                    }
                                    return dateStr;
                                },
                                label: function(context) {
                                    return `침착도: ${context.parsed.y}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: timeUnit
                            },
                            // 데이터가 있는 날짜만 표시하도록 min/max 설정
                            min: minTimestamp,
                            max: maxTimestamp,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                maxTicksLimit: maxTicksLimit,
                                autoSkip: true,
                                autoSkipPadding: 5,
                                // 데이터가 있는 날짜만 표시
                                source: 'data',
                                callback: function(value, index, values) {
                                    // 실제 데이터 포인트에 해당하는 값만 표시
                                    const date = new Date(value);
                                    if (timeUnit === 'hour') {
                                        return date.getHours().toString().padStart(2, '0') + ':00';
                                    } else {
                                        // 해당 날짜에 데이터가 있는지 확인
                                        const dayKey = date.getFullYear() + '-' + 
                                                      (date.getMonth() + 1).toString().padStart(2, '0') + '-' + 
                                                      date.getDate().toString().padStart(2, '0');
                                        const hasData = compressedData.some(point => {
                                            const pointDate = new Date(point.x);
                                            const pointDayKey = pointDate.getFullYear() + '-' + 
                                                               (pointDate.getMonth() + 1).toString().padStart(2, '0') + '-' + 
                                                               pointDate.getDate().toString().padStart(2, '0');
                                            return pointDayKey === dayKey;
                                        });
                                        if (hasData) {
                                            return (date.getMonth() + 1) + '월 ' + date.getDate() + '일';
                                        }
                                        return null; // 데이터가 없는 날은 표시하지 않음
                                    }
                                }
                            }
                        },
                        y: {
                            beginAtZero: false,
                            min: function(context) {
                                const values = context.chart.data.datasets[0].data.map(d => d.y);
                                const minValue = Math.min(...values);
                                return Math.max(0, minValue - 10);
                            },
                            max: 105,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                },
                                stepSize: 10
                            }
                        }
                    }
                }
            });
            
            // 애니메이션이 비활성화되거나 즉시 완료되는 경우를 위한 fallback
            // 최대 2초 후에는 무조건 완료 처리
            setTimeout(() => {
                if (!chartRenderComplete) {
                    chartRenderComplete = true;
                    resolve();
                }
            }, 2000);
            }); // Promise 종료
        }
        
        // 초기화
        // 랜덤 질문은 startQuestions()에서 생성함
    </script>
</body>
</html>