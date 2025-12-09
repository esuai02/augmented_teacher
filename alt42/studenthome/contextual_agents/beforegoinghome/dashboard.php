<?php
/**
 * 귀가검사 리포트 통계 대시보드
 * 파일: dashboard.php
 * 목적: mdl_alt42_goinghome_reports 테이블의 통계 데이터를 시각화
 */

// Moodle 및 OpenAI API 설정
include_once("/home/moodle/public_html/moodle/config.php");
include_once("../../config.php"); // OpenAI API 설정 포함
global $DB, $USER;

// 페이지 방문 카운트
include("../../../../pagecount.php");


// UTF-8mb4 연결 설정
try {
    $DB->execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    error_log("Failed to set connection charset to utf8mb4 at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
}

// 학생 정보 가져오기
$userid = optional_param('userid', 0, PARAM_INT);
$studentId = $userid ? $userid : $USER->id;

// 학생 정보 조회
if ($userid && $userid != $USER->id) {
    $student = $DB->get_record('user', array('id' => $studentId));
    $studentName = $student ? $student->firstname . ' ' . $student->lastname : '학생';
} else {
    $studentName = $USER->firstname . ' ' . $USER->lastname;
}

// 날짜 범위 설정
$now = time();
$oneWeekAgo = $now - (7 * 24 * 60 * 60);
$threeMonthsAgo = $now - (90 * 24 * 60 * 60);

// 최근 1주일 데이터 조회
$weekData = [];
$weekStats = [
    'total' => 0,
    'by_day' => []
];

try {
    $weekRecords = $DB->get_records_sql("
        SELECT 
            id,
            userid,
            report_id,
            report_date,
            timecreated,
            timemodified,
            report_data
        FROM {alt42_goinghome_reports}
        WHERE userid = ? AND timecreated >= ?
        ORDER BY timecreated DESC
    ", [$studentId, $oneWeekAgo]);
    
    $weekStats['total'] = count($weekRecords);
    
    // 일별로 그룹화
    foreach ($weekRecords as $record) {
        $dayKey = date('Y-m-d', $record->timecreated);
        if (!isset($weekStats['by_day'][$dayKey])) {
            $weekStats['by_day'][$dayKey] = 0;
        }
        $weekStats['by_day'][$dayKey]++;
        
        // 리포트 데이터 파싱
        $reportData = null;
        if (!empty($record->report_data)) {
            $reportData = json_decode($record->report_data, true);
        }
        
        $weekData[] = [
            'id' => $record->id,
            'report_id' => $record->report_id,
            'report_date' => $record->report_date,
            'timecreated' => $record->timecreated,
            'date' => date('Y-m-d', $record->timecreated),
            'datetime' => date('Y-m-d H:i:s', $record->timecreated),
            'report_data' => $reportData
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching week data at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    $weekData = [];
}

// 3개월간 총 리포트 수 및 리포트 데이터 통계 분석
$monthlyStats = ['total' => 0];
$reportStatistics = [
    'daily_mood' => [],
    'focus_help' => [],
    'all_responses' => [],
    'response_fields' => [],
    'total_reports_with_data' => 0,
    'total_responses' => 0
];

try {
    // 3개월간 모든 리포트 데이터 조회
    $allReports = $DB->get_records_sql("
        SELECT 
            id,
            report_id,
            report_data,
            timecreated
        FROM {alt42_goinghome_reports}
        WHERE userid = ? AND timecreated >= ?
        ORDER BY timecreated DESC
    ", [$studentId, $threeMonthsAgo]);
    
    $monthlyStats['total'] = count($allReports);
    
    // 리포트 데이터 분석
    foreach ($allReports as $report) {
        if (empty($report->report_data)) {
            continue;
        }
        
        $reportData = json_decode($report->report_data, true);
        if (!$reportData || !isset($reportData['responses'])) {
            continue;
        }
        
        $responses = $reportData['responses'];
        if (!is_array($responses)) {
            continue;
        }
        
        $reportStatistics['total_reports_with_data']++;
        $reportStatistics['total_responses'] += count($responses);
        
        // 각 응답 필드 분석
        foreach ($responses as $key => $value) {
            if (empty($value)) {
                continue;
            }
            
            // 필드별 통계
            if (!isset($reportStatistics['response_fields'][$key])) {
                $reportStatistics['response_fields'][$key] = [
                    'count' => 0,
                    'values' => []
                ];
            }
            
            $reportStatistics['response_fields'][$key]['count']++;
            
            // daily_mood 특별 처리
            if ($key === 'daily_mood') {
                if (!isset($reportStatistics['daily_mood'][$value])) {
                    $reportStatistics['daily_mood'][$value] = 0;
                }
                $reportStatistics['daily_mood'][$value]++;
            }
            
            // focus_help 특별 처리
            if ($key === 'focus_help') {
                if (!isset($reportStatistics['focus_help'][$value])) {
                    $reportStatistics['focus_help'][$value] = 0;
                }
                $reportStatistics['focus_help'][$value]++;
            }
            
            // 모든 응답 값 수집 (중복 제거)
            $valueStr = is_array($value) ? json_encode($value) : (string)$value;
            if (!in_array($valueStr, $reportStatistics['response_fields'][$key]['values'])) {
                $reportStatistics['response_fields'][$key]['values'][] = $valueStr;
            }
        }
    }
    
    // 통계 계산
    $reportStatistics['avg_responses_per_report'] = $reportStatistics['total_reports_with_data'] > 0 
        ? round($reportStatistics['total_responses'] / $reportStatistics['total_reports_with_data'], 2) 
        : 0;
    
} catch (Exception $e) {
    error_log("Error fetching monthly stats at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
    $monthlyStats['total'] = 0;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>귀가검사 통계 - <?php echo htmlspecialchars($studentName); ?></title>
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
        
        .dashboard__stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard__stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .dashboard__stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .dashboard__stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
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
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .dashboard__week-item {
            padding: 0;
            border: none;
            display: block;
        }
        
        .dashboard__week-item-link {
            display: inline-block;
            padding: 10px 16px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .dashboard__week-item-link:hover {
            background: #f0f7ff;
            border-color: #4a90e2;
            color: #4a90e2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .dashboard__week-item-date {
            font-weight: 500;
            color: inherit;
        }
        
        .dashboard__week-item-time {
            display: none;
        }
        
        .dashboard__week-item-id {
            display: none;
        }
        
        .dashboard__week-item-id-link {
            display: none;
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
        
        .dashboard__stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .dashboard__stats-table th,
        .dashboard__stats-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .dashboard__stats-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .dashboard__stats-table tr:hover {
            background: #f8f9fa;
        }
        
        .dashboard__distribution-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        
        .dashboard__distribution-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .dashboard__distribution-item:last-child {
            border-bottom: none;
        }
        
        .dashboard__distribution-label {
            font-weight: 500;
            color: #333;
        }
        
        .dashboard__distribution-count {
            color: #666;
            font-weight: 600;
        }
        
        .dashboard__distribution-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .dashboard__distribution-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4a90e2, #5ba3f5);
            transition: width 0.3s ease;
        }
        
        .dashboard__mood-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px;
            justify-content: center;
            align-items: center;
        }
        
        .dashboard__mood-item {
            display: inline-block;
            padding: 10px 18px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 20px;
            font-size: 15px;
            color: #495057;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .dashboard__mood-item:nth-child(odd) {
            transform: rotate(-2deg);
        }
        
        .dashboard__mood-item:nth-child(even) {
            transform: rotate(2deg);
        }
        
        .dashboard__mood-item:nth-child(3n) {
            transform: rotate(-3deg);
        }
        
        .dashboard__mood-item:nth-child(4n) {
            transform: rotate(3deg);
        }
        
        .dashboard__mood-item:hover {
            transform: rotate(0deg) scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        }
        
        .dashboard__efficacy-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px;
            justify-content: center;
            align-items: center;
        }
        
        .dashboard__efficacy-item {
            display: inline-block;
            padding: 10px 18px;
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-radius: 20px;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            color: #e65100;
        }
        
        .dashboard__efficacy-item:nth-child(odd) {
            transform: rotate(-2deg);
        }
        
        .dashboard__efficacy-item:nth-child(even) {
            transform: rotate(2deg);
        }
        
        .dashboard__efficacy-item:nth-child(3n) {
            transform: rotate(-3deg);
        }
        
        .dashboard__efficacy-item:nth-child(4n) {
            transform: rotate(3deg);
        }
        
        .dashboard__efficacy-item.efficacy-small {
            font-size: 13px;
            padding: 8px 14px;
            opacity: 0.8;
        }
        
        .dashboard__efficacy-item.efficacy-medium {
            font-size: 15px;
            padding: 10px 18px;
        }
        
        .dashboard__efficacy-item.efficacy-large {
            font-size: 17px;
            padding: 12px 20px;
            font-weight: 600;
            background: linear-gradient(135deg, #ffcc80 0%, #ffb74d 100%);
        }
        
        .dashboard__efficacy-item:hover {
            transform: rotate(0deg) scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
            background: linear-gradient(135deg, #ffb74d 0%, #ff9800 100%);
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
            <h1 class="dashboard__title">귀가검사 현황</h1>
            <p class="dashboard__subtitle"><?php echo htmlspecialchars($studentName); ?> 학생의 리포트 통계 현황</p>
        </div>
        
        <!-- 통계 카드 -->
        <div class="dashboard__stats-grid">
            <div class="dashboard__stat-card">
                <div class="dashboard__stat-label">최근 1주일 리포트 수</div>
                <div class="dashboard__stat-value"><?php echo $weekStats['total']; ?></div>
            </div>
            <div class="dashboard__stat-card">
                <div class="dashboard__stat-label">3개월간 총 리포트 수</div>
                <div class="dashboard__stat-value"><?php echo $monthlyStats['total']; ?></div>
            </div>
            <div class="dashboard__stat-card">
                <div class="dashboard__stat-label">데이터 포함 리포트 수</div>
                <div class="dashboard__stat-value"><?php echo $reportStatistics['total_reports_with_data']; ?></div>
            </div>
            <div class="dashboard__stat-card">
                <div class="dashboard__stat-label">평균 응답 수</div>
                <div class="dashboard__stat-value"><?php echo $reportStatistics['avg_responses_per_report']; ?></div>
            </div>
        </div>
        
        <!-- 최근 1주일 상세 목록 -->
        <div class="dashboard__section">
            <h2 class="dashboard__section-title">최근 1주일 리포트</h2>
            <?php if (count($weekData) > 0): ?>
                <ul class="dashboard__week-list">
                    <?php foreach ($weekData as $item): ?>
                        <li class="dashboard__week-item">
                            <a href="index.php?reportid=<?php echo urlencode($item['report_id']); ?>&userid=<?php echo $studentId; ?>" 
                               class="dashboard__week-item-link" 
                               target="_blank" 
                               title="리포트 보기">
                                <span class="dashboard__week-item-date"><?php echo htmlspecialchars($item['report_date'] ?: $item['date']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="dashboard__empty">
                    <div class="dashboard__empty-icon">📝</div>
                    <p>최근 1주일간 리포트가 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 리포트 데이터 통계 및 분포 -->
        <?php if ($reportStatistics['total_reports_with_data'] > 0): ?>
        <div class="dashboard__section">
            <h2 class="dashboard__section-title">리포트 데이터 통계 및 분포</h2>
            
            <!-- 하루 기분 분포 -->
            <?php if (count($reportStatistics['daily_mood']) > 0): ?>
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #333;">하루 기분</h3>
                <div class="dashboard__mood-cloud">
                    <?php 
                    // 모든 기분을 가져와서 예술적으로 배치
                    $moods = array_keys($reportStatistics['daily_mood']);
                    // 약간의 랜덤성을 위해 섞기
                    shuffle($moods);
                    
                    foreach ($moods as $mood): 
                    ?>
                        <span class="dashboard__mood-item">
                            <?php echo htmlspecialchars($mood); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 수학일기 체감 효능감 -->
            <?php if (count($reportStatistics['focus_help']) > 0): ?>
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #333;">수학일기 체감 효능감</h3>
                <div class="dashboard__efficacy-cloud">
                    <?php 
                    // 빈도순으로 정렬
                    arsort($reportStatistics['focus_help']);
                    $maxCount = max($reportStatistics['focus_help']);
                    $minCount = min($reportStatistics['focus_help']);
                    
                    // 빈도에 따라 여러 번 표시하고 크기 조정
                    $efficacyArray = [];
                    foreach ($reportStatistics['focus_help'] as $help => $count) {
                        // 빈도에 따라 크기 분류
                        $ratio = $maxCount > 0 ? ($count / $maxCount) : 0;
                        $sizeClass = 'efficacy-small';
                        if ($ratio >= 0.7) {
                            $sizeClass = 'efficacy-large';
                        } elseif ($ratio >= 0.4) {
                            $sizeClass = 'efficacy-medium';
                        }
                        
                        // 빈도에 따라 여러 번 추가 (중복 표시)
                        $repeatCount = max(1, min($count, 5)); // 최대 5번까지
                        for ($i = 0; $i < $repeatCount; $i++) {
                            $efficacyArray[] = [
                                'text' => $help,
                                'size' => $sizeClass,
                                'count' => $count
                            ];
                        }
                    }
                    
                    // 예술적인 배치를 위해 섞기
                    shuffle($efficacyArray);
                    
                    foreach ($efficacyArray as $item): 
                    ?>
                        <span class="dashboard__efficacy-item <?php echo $item['size']; ?>">
                            <?php echo htmlspecialchars($item['text']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 응답 필드별 통계 -->
            <?php if (count($reportStatistics['response_fields']) > 0): ?>
            <div>
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #333;">응답 필드별 통계</h3>
                <table class="dashboard__stats-table">
                    <thead>
                        <tr>
                            <th>필드명</th>
                            <th>응답 횟수</th>
                            <th>고유 값 수</th>
                            <th>응답률</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // 응답 횟수 순으로 정렬
                        uasort($reportStatistics['response_fields'], function($a, $b) {
                            return $b['count'] - $a['count'];
                        });
                        
                        foreach ($reportStatistics['response_fields'] as $fieldName => $fieldData): 
                            $responseRate = $reportStatistics['total_reports_with_data'] > 0 
                                ? round(($fieldData['count'] / $reportStatistics['total_reports_with_data']) * 100, 1) 
                                : 0;
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($fieldName); ?></strong></td>
                                <td><?php echo $fieldData['count']; ?>회</td>
                                <td><?php echo count($fieldData['values']); ?>개</td>
                                <td><?php echo $responseRate; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

