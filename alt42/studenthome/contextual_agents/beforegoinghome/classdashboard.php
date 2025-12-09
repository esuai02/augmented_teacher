<?php
/**
 * 귀가검사 리포트 반별 통계 대시보드
 * 파일: classdashboard.php
 * 목적: 선생님 반 전체 학생의 리포트 통계 데이터를 시각화
 */

// Moodle 및 OpenAI API 설정
include_once("/home/moodle/public_html/moodle/config.php");
include_once("../../config.php"); // OpenAI API 설정 포함
global $DB, $USER;
require_login();

// UTF-8mb4 연결 설정
try {
    $DB->execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    error_log("Failed to set connection charset to utf8mb4 at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
}

// 선생님 정보 가져오기
$teacherid = optional_param('userid', 0, PARAM_INT);
if (!$teacherid) {
    $teacherid = $USER->id;
}

// 선생님 정보 조회
$teacher = $DB->get_record('user', array('id' => $teacherid));
$teacherName = $teacher ? $teacher->firstname . ' ' . $teacher->lastname : '선생님';

// 선생님의 기호(symbol) 가져오기 (fieldid='79')
$teacherSymbol = $DB->get_record_sql("SELECT data AS symbol FROM {user_info_data} WHERE userid = ? AND fieldid = '79'", [$teacherid]);
$tsymbol = $teacherSymbol && $teacherSymbol->symbol ? $teacherSymbol->symbol : 'KTM';

// 협력 선생님들의 기호 가져오기
$colleagues = $DB->get_record_sql("SELECT * FROM {abessi_teacher_setting} WHERE userid = ?", [$teacherid]);
$tsymbol1 = 'KTM';
$tsymbol2 = 'KTM';
$tsymbol3 = 'KTM';

if ($colleagues) {
    if ($colleagues->mntr1) {
        $teacher1 = $DB->get_record_sql("SELECT data AS symbol FROM {user_info_data} WHERE userid = ? AND fieldid = '79'", [$colleagues->mntr1]);
        $tsymbol1 = $teacher1 && $teacher1->symbol ? $teacher1->symbol : 'KTM';
    }
    if ($colleagues->mntr2) {
        $teacher2 = $DB->get_record_sql("SELECT data AS symbol FROM {user_info_data} WHERE userid = ? AND fieldid = '79'", [$colleagues->mntr2]);
        $tsymbol2 = $teacher2 && $teacher2->symbol ? $teacher2->symbol : 'KTM';
    }
    if ($colleagues->mntr3) {
        $teacher3 = $DB->get_record_sql("SELECT data AS symbol FROM {user_info_data} WHERE userid = ? AND fieldid = '79'", [$colleagues->mntr3]);
        $tsymbol3 = $teacher3 && $teacher3->symbol ? $teacher3->symbol : 'KTM';
    }
}

// 학원 정보 가져오기 (fieldid='46')
$academyInfo = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = '46'", [$teacherid]);
$academy = $academyInfo && $academyInfo->data ? $academyInfo->data : '';

// 반 학생 목록 가져오기 (기호로 식별)
$amonthago6 = time() - (30 * 24 * 60 * 60); // 30일 전
$studentList = [];

try {
    $students = $DB->get_records_sql("
        SELECT id, firstname, lastname 
        FROM {user} 
        WHERE institution LIKE ? 
        AND lastaccess > ? 
        AND (firstname LIKE ? OR firstname LIKE ? OR firstname LIKE ? OR firstname LIKE ?) 
        AND suspended = 0
        ORDER BY id DESC
    ", [
        $academy ? '%' . $academy . '%' : '%',
        $amonthago6,
        '%' . $tsymbol . '%',
        '%' . $tsymbol1 . '%',
        '%' . $tsymbol2 . '%',
        '%' . $tsymbol3 . '%'
    ]);
    
    foreach ($students as $student) {
        $studentList[] = [
            'id' => $student->id,
            'name' => $student->firstname . ' ' . $student->lastname
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching student list at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    $studentList = [];
}

$studentIds = array_column($studentList, 'id');
$totalStudents = count($studentIds);

// 주차 계산 함수
function getWeekOfMonth($timestamp) {
    $date = getdate($timestamp);
    $firstDay = mktime(0, 0, 0, $date['mon'], 1, $date['year']);
    $firstDayOfWeek = date('w', $firstDay); // 0=일요일, 1=월요일
    $dayOfMonth = $date['mday'];
    
    // 월요일 기준으로 주차 계산 (월요일=1)
    $firstMonday = $firstDay;
    if ($firstDayOfWeek == 0) {
        $firstMonday = $firstDay + (1 * 24 * 60 * 60); // 일요일이면 다음날(월요일)
    } elseif ($firstDayOfWeek > 1) {
        $firstMonday = $firstDay - (($firstDayOfWeek - 1) * 24 * 60 * 60); // 월요일로 맞춤
    }
    
    $weekNumber = floor(($timestamp - $firstMonday) / (7 * 24 * 60 * 60)) + 1;
    if ($weekNumber < 1) $weekNumber = 1;
    
    return $weekNumber;
}

// 주차 시작일/종료일 계산
function getWeekRange($year, $month, $week) {
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $firstDayOfWeek = date('w', $firstDay);
    
    // 첫 번째 월요일 찾기
    $firstMonday = $firstDay;
    if ($firstDayOfWeek == 0) {
        $firstMonday = $firstDay + (1 * 24 * 60 * 60);
    } elseif ($firstDayOfWeek > 1) {
        $firstMonday = $firstDay - (($firstDayOfWeek - 1) * 24 * 60 * 60);
    }
    
    // 해당 주차의 시작일 (월요일)
    $weekStart = $firstMonday + (($week - 1) * 7 * 24 * 60 * 60);
    // 해당 주차의 종료일 (일요일)
    $weekEnd = $weekStart + (6 * 24 * 60 * 60) + (23 * 60 * 60) + (59 * 60) + 59; // 일요일 23:59:59
    
    return ['start' => $weekStart, 'end' => $weekEnd];
}

// 날짜 범위 설정
$now = time();
$oneWeekAgo = $now - (7 * 24 * 60 * 60);
$threeMonthsAgo = $now - (90 * 24 * 60 * 60);

// URL 파라미터에서 주차 정보 가져오기
$selectedYear = optional_param('year', date('Y'), PARAM_INT);
$selectedMonth = optional_param('month', date('n'), PARAM_INT);
$selectedWeek = optional_param('week', 0, PARAM_INT);

// 현재 주차 계산 (선택되지 않은 경우)
if ($selectedWeek == 0) {
    $selectedWeek = getWeekOfMonth($now);
}

// 선택된 주차의 날짜 범위 계산
$weekRange = getWeekRange($selectedYear, $selectedMonth, $selectedWeek);
$selectedWeekStart = $weekRange['start'];
$selectedWeekEnd = $weekRange['end'];

// 반 전체 최근 1주일 데이터 조회
$weekData = [];
$weekStats = [
    'total' => 0,
    'by_day' => [],
    'by_student' => []
];

// 리포트 조회 (학생 목록에 있거나 선택된 주차 범위 내의 리포트)
try {
    // 선택된 주차 범위 내의 모든 리포트 조회 (학생 목록 제한 완화)
    $weekRecords = $DB->get_records_sql("
        SELECT 
            r.id,
            r.userid,
            r.report_id,
            r.report_date,
            r.timecreated,
            r.timemodified,
            r.report_data,
            u.firstname,
            u.lastname
        FROM {alt42_goinghome_reports} r
        LEFT JOIN {user} u ON r.userid = u.id
        WHERE r.timecreated >= ? AND r.timecreated <= ?
        ORDER BY r.timecreated DESC
    ", [$selectedWeekStart, $selectedWeekEnd]);
        
        $weekStats['total'] = count($weekRecords);
        
        // 일별로 그룹화
        foreach ($weekRecords as $record) {
            // report_id가 비어있으면 건너뛰기 (하지만 userid는 있어야 함)
            if (empty($record->report_id)) {
                error_log("Report ID is empty at " . __FILE__ . ":" . __LINE__ . " - Record ID: " . $record->id . ", userid: " . ($record->userid ?? 'empty'));
                continue;
            }
            
            // userid가 비어있으면 건너뛰기
            if (empty($record->userid)) {
                error_log("User ID is empty at " . __FILE__ . ":" . __LINE__ . " - Record ID: " . $record->id . ", report_id: " . ($record->report_id ?? 'empty'));
                continue;
            }
            
            $dayKey = date('Y-m-d', $record->timecreated);
            if (!isset($weekStats['by_day'][$dayKey])) {
                $weekStats['by_day'][$dayKey] = 0;
            }
            $weekStats['by_day'][$dayKey]++;
            
            // 학생별로 그룹화
            if (!isset($weekStats['by_student'][$record->userid])) {
                $weekStats['by_student'][$record->userid] = 0;
            }
            $weekStats['by_student'][$record->userid]++;
            
            // 리포트 데이터 파싱
            $reportData = null;
            if (!empty($record->report_data)) {
                $reportData = json_decode($record->report_data, true);
            }
            
            // 학생 이름 찾기 (DB에서 가져온 이름 우선, 없으면 학생 목록에서 찾기)
            $studentName = '알 수 없음';
            if (!empty($record->firstname) || !empty($record->lastname)) {
                $studentName = trim(($record->firstname ?? '') . ' ' . ($record->lastname ?? ''));
            } else {
                foreach ($studentList as $student) {
                    if ($student['id'] == $record->userid) {
                        $studentName = $student['name'];
                        break;
                    }
                }
            }
            
            $weekData[] = [
                'id' => $record->id,
                'userid' => $record->userid,
                'student_name' => $studentName,
                'report_id' => $record->report_id,
                'report_date' => $record->report_date,
                'timecreated' => $record->timecreated,
                'timemodified' => $record->timemodified,
                'date' => date('Y-m-d', $record->timecreated),
                'datetime' => date('Y-m-d H:i:s', $record->timecreated),
                'report_data' => $reportData,
                'has_report_data' => !empty($reportData)
            ];
        }
        
        // 디버깅: 리포트 데이터 수집 확인
        if (count($weekRecords) > 0 && count($weekData) == 0) {
            error_log("Reports found but not added to weekData at " . __FILE__ . ":" . __LINE__ . " - Records count: " . count($weekRecords));
        }
    } catch (Exception $e) {
        error_log("Error fetching week data at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
        $weekData = [];
    }

// 반 전체 3개월간 주별 통계 조회
$monthlyStats = [];
$weeklyData = [];

if (count($studentIds) > 0) {
    try {
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $params = array_merge($studentIds, [$threeMonthsAgo]);
        
        $monthlyRecords = $DB->get_records_sql("
            SELECT 
                timecreated
            FROM {alt42_goinghome_reports}
            WHERE userid IN ($placeholders) AND timecreated >= ?
            ORDER BY timecreated ASC
        ", $params);
        
        // 주별로 그룹화
        $weeklyGroups = [];
        foreach ($monthlyRecords as $record) {
            $timestamp = $record->timecreated;
            $year = date('Y', $timestamp);
            $month = date('n', $timestamp);
            $week = getWeekOfMonth($timestamp);
            
            $weekKey = sprintf('%d-%02d-%d', $year, $month, $week);
            if (!isset($weeklyGroups[$weekKey])) {
                $weeklyGroups[$weekKey] = [
                    'year' => $year,
                    'month' => $month,
                    'week' => $week,
                    'count' => 0,
                    'label' => $month . '월' . $week . '주'
                ];
            }
            $weeklyGroups[$weekKey]['count']++;
        }
        
        // 주차별 데이터 배열로 변환 및 정렬
        foreach ($weeklyGroups as $weekKey => $weekData) {
            $weeklyData[] = $weekData;
        }
        
        // 날짜순으로 정렬
        usort($weeklyData, function($a, $b) {
            if ($a['year'] != $b['year']) {
                return $a['year'] - $b['year'];
            }
            if ($a['month'] != $b['month']) {
                return $a['month'] - $b['month'];
            }
            return $a['week'] - $b['week'];
        });
        
        // 통계 요약
        $monthlyStats['total'] = array_sum(array_column($weeklyData, 'count'));
        $monthlyStats['average_per_week'] = count($weeklyData) > 0 ? round($monthlyStats['total'] / count($weeklyData), 2) : 0;
        $monthlyStats['max_per_week'] = count($weeklyData) > 0 ? max(array_column($weeklyData, 'count')) : 0;
        $monthlyStats['weeks_with_reports'] = count(array_filter($weeklyData, function($item) {
            return $item['count'] > 0;
        }));
        
        // 학생별 리포트 수 계산
        $monthlyStats['students_with_reports'] = count($weekStats['by_student']);
        $monthlyStats['average_per_student'] = $monthlyStats['students_with_reports'] > 0 
            ? round($weekStats['total'] / $monthlyStats['students_with_reports'], 2) 
            : 0;
        
    } catch (Exception $e) {
        error_log("Error fetching monthly stats at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
        $weeklyData = [];
        $monthlyStats = [
            'total' => 0,
            'average_per_week' => 0,
            'max_per_week' => 0,
            'weeks_with_reports' => 0,
            'students_with_reports' => 0,
            'average_per_student' => 0
        ];
    }
} else {
    $monthlyStats = [
        'total' => 0,
        'average_per_week' => 0,
        'max_per_week' => 0,
        'weeks_with_reports' => 0,
        'students_with_reports' => 0,
        'average_per_student' => 0
    ];
}

// 학생별 리포트 수 및 통계 계산
$studentReportCounts = [];
$studentReportStats = []; // 학생별 리포트 통계
$studentReportDates = []; // 학생별 리포트 날짜 및 report_id 정보

// 각 학생의 리포트 데이터 수집
foreach ($weekData as $item) {
    $studentId = $item['userid'];
    if (!isset($studentReportStats[$studentId])) {
        $studentReportStats[$studentId] = [
            'count' => 0,
            'daily_mood' => [],
            'focus_help' => [],
            'responses_count' => 0,
            'total_responses' => 0
        ];
    }
    
    // 리포트 날짜 정보 수집 (report_id가 있는 경우만)
    if (!isset($studentReportDates[$studentId])) {
        $studentReportDates[$studentId] = [];
    }
    // report_id가 비어있지 않은 경우만 추가
    if (!empty($item['report_id'])) {
        $studentReportDates[$studentId][] = [
            'report_id' => $item['report_id'],
            'date' => date('Y-m-d', $item['timecreated']),
            'date_display' => date('n/j', $item['timecreated']), // 앞의 0 제거 (예: 10/13)
            'timecreated' => $item['timecreated']
        ];
    }
    
    $studentReportStats[$studentId]['count']++;
    
    // 리포트 데이터 분석
    if ($item['report_data'] && isset($item['report_data']['responses'])) {
        $responses = $item['report_data']['responses'];
        $studentReportStats[$studentId]['total_responses'] += count($responses);
        
        // daily_mood 수집
        if (isset($responses['daily_mood'])) {
            $studentReportStats[$studentId]['daily_mood'][] = $responses['daily_mood'];
        }
        
        // focus_help 수집
        if (isset($responses['focus_help'])) {
            $studentReportStats[$studentId]['focus_help'][] = $responses['focus_help'];
        }
        
        $studentReportStats[$studentId]['responses_count']++;
    }
}

// 학생별 리포트 날짜 정렬 (최신순)
foreach ($studentReportDates as $studentId => &$dates) {
    usort($dates, function($a, $b) {
        return $b['timecreated'] - $a['timecreated'];
    });
}
unset($dates);

// 모든 학생 표시 (상위 10명 제한 제거)
foreach ($studentList as $student) {
    $studentId = $student['id'];
    $count = isset($weekStats['by_student'][$studentId]) ? $weekStats['by_student'][$studentId] : 0;
    $stats = isset($studentReportStats[$studentId]) ? $studentReportStats[$studentId] : [
        'count' => 0,
        'daily_mood' => [],
        'focus_help' => [],
        'responses_count' => 0,
        'total_responses' => 0
    ];
    
    // 해당 학생의 리포트를 DB에서 직접 조회 (선택된 주차 범위 내)
    $reportDates = [];
    try {
        $studentReports = $DB->get_records_sql("
            SELECT 
                report_id,
                timecreated,
                report_data
            FROM {alt42_goinghome_reports}
            WHERE userid = ? AND timecreated >= ? AND timecreated <= ?
            ORDER BY timecreated DESC
        ", [$studentId, $selectedWeekStart, $selectedWeekEnd]);
        
        foreach ($studentReports as $report) {
            if (!empty($report->report_id)) {
                $reportDates[] = [
                    'report_id' => $report->report_id,
                    'date' => date('Y-m-d', $report->timecreated),
                    'date_display' => date('n/j', $report->timecreated), // 앞의 0 제거 (예: 10/13)
                    'timecreated' => $report->timecreated
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching student reports at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
        // 기존 리포트 날짜 정보 사용
        $reportDates = isset($studentReportDates[$studentId]) ? $studentReportDates[$studentId] : [];
    }
    
    // 통계 계산
    $avgResponses = $stats['responses_count'] > 0 ? round($stats['total_responses'] / $stats['responses_count'], 1) : 0;
    $moodCount = count($stats['daily_mood']);
    $focusHelpCount = count($stats['focus_help']);
    
    // daily_mood 빈도 계산
    $moodFrequency = [];
    foreach ($stats['daily_mood'] as $mood) {
        if (!isset($moodFrequency[$mood])) {
            $moodFrequency[$mood] = 0;
        }
        $moodFrequency[$mood]++;
    }
    $mostCommonMood = '';
    $maxMoodCount = 0;
    foreach ($moodFrequency as $mood => $freq) {
        if ($freq > $maxMoodCount) {
            $maxMoodCount = $freq;
            $mostCommonMood = $mood;
        }
    }
    
    $studentReportCounts[] = [
        'id' => $student['id'],
        'name' => $student['name'],
        'count' => $count,
        'avg_responses' => $avgResponses,
        'mood_count' => $moodCount,
        'focus_help_count' => $focusHelpCount,
        'most_common_mood' => $mostCommonMood,
        'mood_frequency' => $moodFrequency,
        'report_dates' => $reportDates
    ];
}
usort($studentReportCounts, function($a, $b) {
    return $b['count'] - $a['count'];
});
$allStudents = $studentReportCounts; // 모든 학생 (상위 10명 제한 제거)

// 월/주차별 링크 생성 (최근 12주)
$weekLinks = [];
$now = time();
$currentWeek = getWeekOfMonth($now);
$currentMonth = date('n', $now);
$currentYear = date('Y', $now);

// 과거 11주 전부터 현재 주차까지 총 12주 생성
for ($weekOffset = 11; $weekOffset >= 0; $weekOffset--) {
    // 현재 주차에서 과거로 이동
    $targetWeek = $currentWeek - $weekOffset;
    $targetMonth = $currentMonth;
    $targetYear = $currentYear;
    
    // 주차가 1보다 작아지면 이전 달로 이동
    while ($targetWeek < 1) {
        $targetMonth--;
        if ($targetMonth < 1) {
            $targetMonth = 12;
            $targetYear--;
        }
        // 이전 달의 주차 수 계산
        $firstDay = mktime(0, 0, 0, $targetMonth, 1, $targetYear);
        $lastDay = mktime(23, 59, 59, $targetMonth, date('t', $firstDay), $targetYear);
        
        $firstDayOfWeek = date('w', $firstDay);
        $firstMonday = $firstDay;
        if ($firstDayOfWeek == 0) {
            $firstMonday = $firstDay + (1 * 24 * 60 * 60);
        } elseif ($firstDayOfWeek > 1) {
            $firstMonday = $firstDay - (($firstDayOfWeek - 1) * 24 * 60 * 60);
        }
        
        // 해당 월의 주차 수 계산
        $weeksInMonth = 1;
        for ($week = 1; $week <= 5; $week++) {
            $weekRange = getWeekRange($targetYear, $targetMonth, $week);
            if ($weekRange['start'] <= $lastDay && $weekRange['end'] >= $firstDay) {
                $weeksInMonth = $week;
            } else {
                if ($weekRange['start'] > $lastDay) {
                    break;
                }
            }
        }
        
        $targetWeek = $weeksInMonth + $targetWeek; // 음수였던 주차를 양수로 변환
    }
    
    // 주차 범위 계산
    $weekRange = getWeekRange($targetYear, $targetMonth, $targetWeek);
    
    // 해당 주차에 리포트가 있는지 확인
    $hasReports = false;
    if (count($studentIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $checkParams = array_merge($studentIds, [$weekRange['start'], $weekRange['end']]);
        $reportCount = $DB->count_records_sql("
            SELECT COUNT(*) 
            FROM {alt42_goinghome_reports}
            WHERE userid IN ($placeholders) AND timecreated >= ? AND timecreated <= ?
        ", $checkParams);
        $hasReports = $reportCount > 0;
    }
    
    $weekLinks[] = [
        'year' => $targetYear,
        'month' => $targetMonth,
        'week' => $targetWeek,
        'label' => date('n', mktime(0, 0, 0, $targetMonth, 1, $targetYear)) . '월' . $targetWeek . '주',
        'has_reports' => $hasReports,
        'is_selected' => ($targetYear == $selectedYear && $targetMonth == $selectedMonth && $targetWeek == $selectedWeek)
    ];
}

// 날짜순으로 정렬 (년 -> 월 -> 주 순서로 오름차순, 1주부터 증가하는 순서)
usort($weekLinks, function($a, $b) {
    if ($a['year'] != $b['year']) {
        return $a['year'] - $b['year'];
    }
    if ($a['month'] != $b['month']) {
        return $a['month'] - $b['month'];
    }
    return $a['week'] - $b['week'];
});

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>귀가검사 리포트 반별 통계 - <?php echo htmlspecialchars($teacherName); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
            padding: 20px;
            line-height: 1.6;
        }
        
        .dashboard__container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard__header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .dashboard__title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        
        .dashboard__subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .dashboard__info {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .dashboard__stats-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .dashboard__stat-card {
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }
        
        .dashboard__stat-label {
            font-size: 12px;
            color: #666;
            white-space: nowrap;
        }
        
        .dashboard__stat-value {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            white-space: nowrap;
        }
        
        .dashboard__section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .dashboard__section-title {
            font-size: 22px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .dashboard__chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }
        
        .dashboard__week-list {
            list-style: none;
        }
        
        .dashboard__week-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard__week-item:last-child {
            border-bottom: none;
        }
        
        .dashboard__week-item-student {
            font-weight: 500;
            color: #333;
            margin-right: 15px;
        }
        
        .dashboard__week-item-date {
            font-weight: 500;
            color: #333;
        }
        
        .dashboard__week-item-time {
            color: #666;
            font-size: 14px;
        }
        
        .dashboard__week-item-id {
            color: #999;
            font-size: 12px;
            font-family: monospace;
        }
        
        .dashboard__week-item-id-link {
            color: #4a90e2;
            font-size: 12px;
            font-family: monospace;
            text-decoration: none;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .dashboard__week-item-id-link:hover {
            background-color: #f0f7ff;
            color: #2d6cb0;
            text-decoration: underline;
        }
        
        .dashboard__student-list {
            list-style: none;
        }
        
        .dashboard__student-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard__student-item:last-child {
            border-bottom: none;
        }
        
        .dashboard__student-name {
            font-weight: 500;
            color: #333;
        }
        
        .dashboard__student-count {
            font-size: 18px;
            font-weight: 600;
            color: #4a90e2;
        }
        
        .dashboard__empty {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .dashboard__empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .dashboard__week-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .dashboard__week-link {
            padding: 8px 16px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            text-decoration: none;
            color: #666;
            font-size: 14px;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .dashboard__week-link:hover {
            background: #f0f7ff;
            border-color: #4a90e2;
            color: #4a90e2;
        }
        
        .dashboard__week-link--selected {
            background: #4a90e2;
            border-color: #4a90e2;
            color: white;
            font-weight: 600;
        }
        
        .dashboard__week-link--has-reports {
            border-color: #28a745;
        }
        
        .dashboard__week-link--has-reports:hover {
            border-color: #28a745;
        }
        
        .dashboard__week-link--selected.dashboard__week-link--has-reports {
            background: #28a745;
            border-color: #28a745;
        }
        
        @media (max-width: 768px) {
            .dashboard__stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard__chart-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard__container">
        <div class="dashboard__header">
            <h1 class="dashboard__title">귀가검사 리포트 반별 통계</h1>
            <p class="dashboard__subtitle"><?php echo htmlspecialchars($teacherName); ?> 선생님 반 전체 리포트 통계 현황</p>
            <div class="dashboard__info">
                <strong>반 학생 수:</strong> <?php echo $totalStudents; ?>명 | 
                <strong>기호:</strong> <?php 
                    // 중복 제거를 위해 배열로 수집
                    $symbols = [];
                    if ($tsymbol != 'KTM') {
                        $symbols[] = $tsymbol;
                    }
                    if ($tsymbol1 != 'KTM' && !in_array($tsymbol1, $symbols)) {
                        $symbols[] = $tsymbol1;
                    }
                    if ($tsymbol2 != 'KTM' && !in_array($tsymbol2, $symbols)) {
                        $symbols[] = $tsymbol2;
                    }
                    if ($tsymbol3 != 'KTM' && !in_array($tsymbol3, $symbols)) {
                        $symbols[] = $tsymbol3;
                    }
                    // 중복 제거된 기호들을 쉼표로 구분하여 표시
                    echo htmlspecialchars(implode(', ', $symbols));
                ?>
            </div>
        </div>
        
        <!-- 통계 카드 (컴팩트) -->
        <div class="dashboard__stats-grid">
            <div class="dashboard__stat-card">
                <span class="dashboard__stat-label">반 학생 수</span>
                <span class="dashboard__stat-value"><?php echo $totalStudents; ?></span>
            </div>
            <div class="dashboard__stat-card">
                <span class="dashboard__stat-label">선택된 주차 리포트 수</span>
                <span class="dashboard__stat-value"><?php echo $weekStats['total']; ?></span>
            </div>
            <div class="dashboard__stat-card">
                <span class="dashboard__stat-label">3개월간 총 리포트 수</span>
                <span class="dashboard__stat-value"><?php echo $monthlyStats['total']; ?></span>
            </div>
            <div class="dashboard__stat-card">
                <span class="dashboard__stat-label">리포트 작성 학생 수</span>
                <span class="dashboard__stat-value"><?php echo $monthlyStats['students_with_reports']; ?></span>
            </div>
            <div class="dashboard__stat-card">
                <span class="dashboard__stat-label">주평균 리포트 수</span>
                <span class="dashboard__stat-value"><?php echo $monthlyStats['average_per_week']; ?></span>
            </div>
            <div class="dashboard__stat-card">
                <span class="dashboard__stat-label">학생당 평균 리포트 수</span>
                <span class="dashboard__stat-value"><?php echo $monthlyStats['average_per_student']; ?></span>
            </div>
        </div>
        
        <!-- 월/주차별 링크 -->
        <div class="dashboard__section">
            <h2 class="dashboard__section-title" style="display: flex; justify-content: space-between; align-items: center;">
                <span>월/주차별 리포트 보기</span>
                <span style="font-size: 16px; font-weight: normal; color: #4a90e2;">
                    선택: <?php echo $selectedYear; ?>년 <?php echo $selectedMonth; ?>월 <?php echo $selectedWeek; ?>주차 
                    (<?php echo date('n월 j일', $selectedWeekStart); ?> ~ <?php echo date('n월 j일', $selectedWeekEnd); ?>)
                </span>
            </h2>
            <div class="dashboard__week-links">
                <?php foreach ($weekLinks as $link): ?>
                    <a href="?userid=<?php echo $teacherid; ?>&year=<?php echo $link['year']; ?>&month=<?php echo $link['month']; ?>&week=<?php echo $link['week']; ?>" 
                       class="dashboard__week-link <?php echo $link['is_selected'] ? 'dashboard__week-link--selected' : ''; ?> <?php echo $link['has_reports'] ? 'dashboard__week-link--has-reports' : ''; ?>">
                        <?php echo htmlspecialchars($link['label']); ?>
                        <?php if ($link['has_reports']): ?>
                            <span style="margin-left: 5px;">✓</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
 

        <!-- 선택된 주차 리포트 목록 (날짜별 그룹화) -->
        <?php 
        // 리포트가 있는 경우에만 섹션 표시
        if (count($weekData) > 0): 
            // 시간순으로 정렬 (오래된 것부터)
            $sortedWeekData = $weekData;
            usort($sortedWeekData, function($a, $b) {
                return $a['timecreated'] - $b['timecreated'];
            });
            
            // 유효한 리포트만 필터링 (report_id와 userid가 모두 있어야 함)
            $validReports = [];
            foreach ($sortedWeekData as $item) {
                // report_id와 userid가 모두 있고, 비어있지 않은 경우만 추가
                if (!empty($item['report_id']) && !empty($item['userid']) && 
                    $item['report_id'] !== '' && $item['userid'] !== '') {
                    $validReports[] = $item;
                } else {
                    // 디버깅: 왜 리포트가 필터링되었는지 로그
                    error_log("Report filtered out at " . __FILE__ . ":" . __LINE__ . 
                        " - report_id: " . var_export($item['report_id'] ?? 'not set', true) . 
                        ", userid: " . var_export($item['userid'] ?? 'not set', true));
                }
            }
            
            // 유효한 리포트가 있는 경우에만 표시
            if (count($validReports) > 0):
                // 날짜별로 그룹화
                $reportsByDate = [];
                foreach ($validReports as $item) {
                    $dateKey = date('Y-m-d', $item['timecreated']);
                    if (!isset($reportsByDate[$dateKey])) {
                        $reportsByDate[$dateKey] = [];
                    }
                    $reportsByDate[$dateKey][] = $item;
                }
                // 날짜순으로 정렬 (최신순)
                krsort($reportsByDate);
        ?>
            <div class="dashboard__section">
                <h2 class="dashboard__section-title">날짜별 리포트 목록</h2>
                <?php foreach ($reportsByDate as $dateKey => $dateReports): ?>
                    <div style="margin-bottom: 25px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">
                            <?php echo date('Y년 n월 j일', strtotime($dateKey)); ?> (<?php echo count($dateReports); ?>개)
                        </h3>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; padding: 10px;">
                            <?php foreach ($dateReports as $item): ?>
                                <?php 
                                // dashboard.php와 같은 방식으로 날짜 표시
                                $displayDate = $item['report_date'] ?: $item['date'];
                                // report_date가 "2025년 11월 13일" 형식이 아니면 포맷팅
                                if (!preg_match('/년|월|일/', $displayDate)) {
                                    $displayDate = date('Y년 n월 j일', $item['timecreated']);
                                }
                                
                                // URL 생성: index.php?reportid=REPORT_xxx&userid=xxx
                                $reportUrl = 'index.php?reportid=' . urlencode($item['report_id']) . '&userid=' . urlencode($item['userid']);
                                ?>
                                <a href="<?php echo htmlspecialchars($reportUrl); ?>" 
                                   target="_blank"
                                   style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: <?php echo $item['has_report_data'] ? '#4a90e2' : '#999'; ?>; color: white; border-radius: 6px; text-decoration: none; font-size: 13px; transition: all 0.2s; cursor: pointer;"
                                   onmouseover="this.style.background='<?php echo $item['has_report_data'] ? '#2d6cb0' : '#777'; ?>'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.2)'"
                                   onmouseout="this.style.background='<?php echo $item['has_report_data'] ? '#4a90e2' : '#999'; ?>'; this.style.transform='translateY(0)'; this.style.boxShadow='none'"
                                   title="리포트 ID: <?php echo htmlspecialchars($item['report_id']); ?>&#10;학생 ID: <?php echo htmlspecialchars($item['userid']); ?>&#10;생성일: <?php echo htmlspecialchars($item['datetime']); ?>&#10;수정일: <?php echo date('Y-m-d H:i:s', $item['timemodified']); ?>&#10;데이터: <?php echo $item['has_report_data'] ? '있음' : '없음'; ?>">
                                    <?php echo htmlspecialchars($item['student_name']); ?> (<?php echo date('H:i', $item['timecreated']); ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php 
            endif; // 유효한 리포트가 있는 경우
        endif; // 리포트가 있는 경우
        ?>
        
        <!-- 학생별 리포트 수 및 통계 (모든 학생) -->
        <div class="dashboard__section">
            <h2 class="dashboard__section-title">학생별 리포트 통계  (<a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/contextual_agents/beforegoinghome/dailyreport.php?userid=<?php echo $teacherid; ?>" target="_blank">지난 수업 리포트</a>)</h2>
            <?php if (count($allStudents) > 0): ?>
                <ul class="dashboard__student-list">
                    <?php foreach ($allStudents as $student): ?>
                        <li class="dashboard__student-item" style="flex-direction: column; align-items: flex-start; padding: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; width: 100%; margin-bottom: 8px;">
                                <a href="../../../../teachers/timescaffolding.php?userid=<?php echo $student['id']; ?>" class="dashboard__student-name" style="text-decoration: none; color: #333; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#4a90e2';" onmouseout="this.style.color='#333';"><?php echo htmlspecialchars($student['name']); ?></a>
                                <?php 
                                // 리포트 날짜 정보가 있으면 표시
                                if (count($student['report_dates']) > 0): 
                                ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 5px; align-items: center;">
                                        <?php foreach ($student['report_dates'] as $reportDate): ?>
                                            <?php 
                                            // report_id와 userid가 모두 있어야 링크 생성
                                            if (empty($reportDate['report_id']) || empty($student['id'])) {
                                                continue;
                                            }
                                            
                                            // URL 생성: index.php?reportid=REPORT_xxx&userid=xxx
                                            $reportUrl = 'index.php?reportid=' . urlencode($reportDate['report_id']) . '&userid=' . urlencode($student['id']);
                                            ?>
                                            <a href="<?php echo htmlspecialchars($reportUrl); ?>" 
                                               target="_blank"
                                               style="display: inline-block; padding: 4px 10px; background: #4a90e2; color: white; border-radius: 4px; text-decoration: none; font-size: 12px; transition: all 0.2s; cursor: pointer;"
                                               onmouseover="this.style.background='#2d6cb0'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.2)'"
                                               onmouseout="this.style.background='#4a90e2'; this.style.transform='translateY(0)'; this.style.boxShadow='none'"
                                               title="리포트 ID: <?php echo htmlspecialchars($reportDate['report_id']); ?>&#10;학생 ID: <?php echo htmlspecialchars($student['id']); ?>&#10;날짜: <?php echo htmlspecialchars($reportDate['date']); ?>">
                                                <?php echo htmlspecialchars($reportDate['date_display']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php 
                                // 리포트가 있지만 날짜 정보가 없는 경우 (디버깅용)
                                elseif ($student['count'] > 0): 
                                ?>
                                    <span style="color: #999; font-size: 13px;">리포트 있음 (날짜 정보 없음)</span>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 13px;">리포트 없음</span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: 13px; color: #666; margin-top: 5px;">
                                <?php if ($student['avg_responses'] > 0): ?>
                                    <span><strong>평균 응답 수:</strong> <?php echo $student['avg_responses']; ?>개</span>
                                <?php endif; ?>
                                <?php if ($student['mood_count'] > 0): ?>
                                    <span><strong>하루 기분 기록:</strong> <?php echo $student['mood_count']; ?>회</span>
                                <?php endif; ?>
                                <?php if ($student['most_common_mood']): ?>
                                    <span><strong>가장 많은 기분:</strong> <?php echo htmlspecialchars($student['most_common_mood']); ?></span>
                                <?php endif; ?>
                                <?php if ($student['focus_help_count'] > 0): ?>
                                    <span><strong>집중 도움 요청:</strong> <?php echo $student['focus_help_count']; ?>회</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="dashboard__empty">
                    <div class="dashboard__empty-icon">👥</div>
                    <p>리포트를 작성한 학생이 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 선택된 주차 일별 통계 그래프 -->
        <div class="dashboard__section">
            <h2 class="dashboard__section-title">
                <?php echo $selectedYear; ?>년 <?php echo $selectedMonth; ?>월 <?php echo $selectedWeek; ?>주차 일별 리포트 수
            </h2>
            <?php if (count($weekStats['by_day']) > 0): ?>
                <div class="dashboard__chart-container">
                    <canvas id="weekChart"></canvas>
                </div>
            <?php else: ?>
                <div class="dashboard__empty">
                    <div class="dashboard__empty-icon">📈</div>
                    <p>해당 주차에 리포트 데이터가 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 최근 3개월 주별 리포트 수 -->
        <div class="dashboard__section">
            <h2 class="dashboard__section-title">최근 3개월 주별 리포트 수</h2>
            <?php if (count($weeklyData) > 0): ?>
                <div class="dashboard__chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            <?php else: ?>
                <div class="dashboard__empty">
                    <div class="dashboard__empty-icon">📊</div>
                    <p>3개월간 리포트 데이터가 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // 최근 1주일 일별 통계 그래프
        <?php if (count($weekStats['by_day']) > 0): ?>
        const weekCtx = document.getElementById('weekChart');
        if (weekCtx) {
            const weekLabels = <?php echo json_encode(array_keys($weekStats['by_day'])); ?>;
            const weekData = <?php echo json_encode(array_values($weekStats['by_day'])); ?>;
            
            const weekChart = new Chart(weekCtx, {
                type: 'bar',
                data: {
                    labels: weekLabels,
                    datasets: [{
                        label: '일별 리포트 수',
                        data: weekData,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
        
        // 최근 3개월 주별 리포트 수 그래프
        <?php if (count($weeklyData) > 0): ?>
        const weeklyCtx = document.getElementById('weeklyChart');
        if (weeklyCtx) {
            const weeklyLabels = <?php echo json_encode(array_column($weeklyData, 'label')); ?>;
            const weeklyCounts = <?php echo json_encode(array_column($weeklyData, 'count')); ?>;
            
            const weeklyChart = new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: weeklyLabels,
                    datasets: [{
                        label: '주별 리포트 수',
                        data: weeklyCounts,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>

