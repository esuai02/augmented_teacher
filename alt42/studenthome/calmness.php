<?php
// 에러 디스플레이 활성화 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;

// 페이지 URL 설정
// URL에서 id 또는 userid 파라미터 받기
$userid = optional_param('id', 0, PARAM_INT);
if (!$userid) {
    $userid = optional_param('userid', $USER->id, PARAM_INT);
}
// tb 파라미터 받기 (시간 경계 in seconds)
$tb = optional_param('tb', 0, PARAM_INT);

$PAGE->set_url('/local/augmented_teacher/alt42/studenthome/pages/calmness.php', array('userid' => $userid));

require_login();
$studentid = $userid;

// 페이지 컨텍스트 설정
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_title('침착도 분석');
$PAGE->set_heading('침착도 분석');
$PAGE->set_pagelayout('standard');

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;

// 사용자 정보 가져오기
$student_info = $DB->get_record('user', ['id' => $studentid]);
$student_name = $student_info ? $student_info->firstname . ' ' . $student_info->lastname : '학생';

// 시간 구간별 데이터 준비
$current_time = time();

// tb 파라미터가 있으면 그 값을 사용하여 커스텀 기간 추가
if ($tb > 0) {
    $custom_start = $current_time - $tb;
    echo "<!-- DEBUG: Custom period - tb: $tb seconds, start timestamp: $custom_start (" . date('Y-m-d H:i:s', $custom_start) . ") -->\n";
    
    $periods = [
        'custom' => ['name' => '요청 기간', 'start' => $custom_start],
        '12h' => ['name' => '최근 12시간', 'start' => $current_time - (12 * 60 * 60)],
        '1w' => ['name' => '최근 1주일', 'start' => $current_time - (7 * 24 * 60 * 60)],
        '1m' => ['name' => '최근 1개월', 'start' => $current_time - (30 * 24 * 60 * 60)],
        '3m' => ['name' => '최근 3개월', 'start' => $current_time - (90 * 24 * 60 * 60)]
    ];
} else {
    $periods = [
        '12h' => ['name' => '최근 12시간', 'start' => $current_time - (12 * 60 * 60)],
        '1w' => ['name' => '최근 1주일', 'start' => $current_time - (7 * 24 * 60 * 60)],
        '1m' => ['name' => '최근 1개월', 'start' => $current_time - (30 * 24 * 60 * 60)],
        '3m' => ['name' => '최근 3개월', 'start' => $current_time - (90 * 24 * 60 * 60)]
    ];
}

// 각 기간별 데이터 가져오기
$period_data = [];

// 먼저 테이블이 존재하는지 확인
$table_exists = false;
try {
    $test_sql = "SELECT COUNT(*) FROM mdl_alt42_calmness WHERE userid = ?";
    $count = $DB->count_records_sql($test_sql, [$studentid]);
    $table_exists = true;
    
    // 테이블 구조 확인
    $columns = $DB->get_columns('mdl_alt42_calmness');
    echo "<!-- DEBUG: Table columns: " . implode(', ', array_keys($columns)) . " -->\n";
    
} catch (Exception $e) {
    // 테이블이 없는 경우
    echo "<!-- DEBUG: Table mdl_alt42_calmness does not exist or error: " . $e->getMessage() . " -->\n";
}

if ($table_exists) {
    // 먼저 전체 데이터 수 확인 (WHERE 조건 없이)
    $total_sql_all = "SELECT COUNT(*) as cnt FROM mdl_alt42_calmness";
    $total_count_all = $DB->get_record_sql($total_sql_all);
    echo "<!-- DEBUG: Total records in table: " . ($total_count_all ? $total_count_all->cnt : 0) . " -->\n";
    
    // 특정 사용자의 데이터 수 확인
    $total_sql = "SELECT COUNT(*) as cnt FROM mdl_alt42_calmness WHERE userid = ?";
    $total_count = $DB->get_record_sql($total_sql, [$studentid]);
    echo "<!-- DEBUG: Total records for user $studentid: " . ($total_count ? $total_count->cnt : 0) . " -->\n";
    echo "<!-- DEBUG: URL Parameters - id: " . optional_param('id', 'none', PARAM_TEXT) . ", userid: " . optional_param('userid', 'none', PARAM_TEXT) . ", tb: $tb -->\n";
    echo "<!-- DEBUG: Final studentid being used: $studentid -->\n";
    
    // 최근 10개 데이터 샘플 확인 (모든 사용자)
    $sample_sql = "SELECT id, userid, level, timecreated, hide FROM mdl_alt42_calmness ORDER BY timecreated DESC LIMIT 10";
    $samples = $DB->get_records_sql($sample_sql);
    echo "<!-- DEBUG: Recent 10 records sample:\n";
    foreach ($samples as $sample) {
        echo "  ID: {$sample->id}, UserID: {$sample->userid}, Level: {$sample->level}, ";
        echo "Time: " . date('Y-m-d H:i:s', $sample->timecreated) . " (timestamp: {$sample->timecreated}), ";
        echo "Hide: " . (is_null($sample->hide) ? 'NULL' : $sample->hide) . "\n";
    }
    echo "-->\n";
    
    // 특별히 user 1877의 데이터 확인
    if ($studentid == 1877) {
        $user_samples_sql = "SELECT id, userid, level, timecreated, hide FROM mdl_alt42_calmness WHERE userid = 1877 ORDER BY timecreated DESC LIMIT 10";
        $user_samples = $DB->get_records_sql($user_samples_sql);
        echo "<!-- DEBUG: User 1877 specific records:\n";
        foreach ($user_samples as $sample) {
            echo "  ID: {$sample->id}, Level: {$sample->level}, ";
            echo "Time: " . date('Y-m-d H:i:s', $sample->timecreated) . " (timestamp: {$sample->timecreated}), ";
            echo "Hide: " . (is_null($sample->hide) ? 'NULL' : $sample->hide) . "\n";
        }
        echo "-->\n";
    }
    
    // hide 필드 값 분포 확인
    $hide_sql = "SELECT hide, COUNT(*) as cnt FROM mdl_alt42_calmness WHERE userid = ? GROUP BY hide";
    $hide_counts = $DB->get_records_sql($hide_sql, [$studentid]);
    foreach ($hide_counts as $hc) {
        echo "<!-- DEBUG: hide=" . (is_null($hc->hide) ? 'NULL' : $hc->hide) . " count=" . $hc->cnt . " -->\n";
    }
    
    foreach ($periods as $key => $period) {
        // hide 조건 제거하고 먼저 모든 데이터 확인
        $sql_all = "SELECT id, userid, level, timecreated, hide 
                    FROM mdl_alt42_calmness 
                    WHERE userid = ? AND timecreated >= ?
                    ORDER BY timecreated ASC";
        
        $records_all = $DB->get_records_sql($sql_all, [$studentid, $period['start']]);
        $raw_count = count($records_all);
        
        // 12h 구간에 대한 상세 디버그 로그
        if ($key === '12h') {
            error_log("CALM_DEBUG step1_raw_records=" . $raw_count);
        }
        
        // hide 조건을 수정: hide IS NULL OR hide = 0, level > 1
        $sql = "SELECT id, userid, level, timecreated 
                FROM mdl_alt42_calmness 
                WHERE userid = ? AND timecreated >= ? AND (hide IS NULL OR hide = 0) AND level > 1
                ORDER BY timecreated ASC";
        
        $records = $DB->get_records_sql($sql, [$studentid, $period['start']]);
        $after_hide_count = count($records);
        
        // 12h 구간에 대한 상세 디버그 로그
        if ($key === '12h') {
            error_log("CALM_DEBUG step2_after_hide_and_level_filter=" . $after_hide_count);
            
            // level <= 1인 데이터 수 확인
            $low_level_count = 0;
            foreach ($records_all as $rec) {
                if ($rec->level <= 1) $low_level_count++;
            }
            error_log("CALM_DEBUG excluded_low_level_count=" . $low_level_count);
            
            // 처음 5개 레코드 정보 로그
            $debug_records = array_slice($records, 0, 5, true);
            $debug_info = [];
            foreach ($debug_records as $rec) {
                $hide_val = isset($rec->hide) ? $rec->hide : 'NULL';
                $debug_info[] = "(id:{$rec->id}, level:{$rec->level}, timecreated:{$rec->timecreated}, hide:{$hide_val})";
            }
            error_log("CALM_DEBUG first_ids: " . implode(', ', $debug_info));
        }
        
        echo "<!-- DEBUG: Period $key total records (without hide filter): " . $raw_count . " -->\n";
        echo "<!-- DEBUG: Period $key found " . $after_hide_count . " records (with hide and level > 1 filter) -->\n";
        echo "<!-- DEBUG: Period $key start time: " . date('Y-m-d H:i:s', $period['start']) . " (timestamp: {$period['start']}) -->\n";
        echo "<!-- DEBUG: Current time: " . date('Y-m-d H:i:s', $current_time) . " (timestamp: {$current_time}) -->\n";
        
        // 처음 몇 개 레코드의 상세 정보 출력
        if (count($records) > 0) {
            echo "<!-- DEBUG: First few records for period $key:\n";
            $i = 0;
            foreach ($records as $record) {
                if ($i++ >= 5) break;
                echo "  Level: {$record->level}, Time: " . date('Y-m-d H:i:s', $record->timecreated) . " (timestamp: {$record->timecreated})\n";
            }
            echo "-->\n";
        }
        
        $data = [];
        foreach ($records as $record) {
            $data[] = [
                'x' => $record->timecreated * 1000,  // ms로 변환
                'y' => (int)$record->level,
                'ts' => $record->timecreated         // 초 단위 (통계용)
            ];
        }
        
        // 12h 메타 정보 추가
        if ($key === '12h') {
            error_log("CALM_DEBUG json_12h_length=" . count($data));
            $period_data['_meta_12h'] = [
                'raw' => $raw_count,
                'after_hide' => $after_hide_count
            ];
        }
        
        $period_data[$key] = $data;
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>침착도 분석 - <?php echo htmlspecialchars($student_name); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .period-selector {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            background: #f8f9fa;
            color: #495057;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .period-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .period-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .chart-container {
            position: relative;
            height: 500px;
            margin-bottom: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .timestamp-info {
            position: absolute;
            top: 20px;
            right: 30px;
            background: rgba(255,255,255,0.9);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            color: #495057;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 6px;
            line-height: 1.2;
        }
        
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.9;
            line-height: 1.4;
        }
        
        .stat-label small {
            font-size: 0.75rem;
            display: block;
            margin-top: 4px;
            opacity: 0.85;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 20px;
            }
            
            .chart-container {
                height: 400px;
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 10px;
                margin-top: 15px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .stat-value {
                font-size: 1.5rem;
                margin-bottom: 5px;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
            
            .stat-label small {
                font-size: 0.7rem;
                margin-top: 3px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧘‍♀️ 침착도 분석</h1>
            <p><?php echo htmlspecialchars($student_name); ?>님의 수학 문제 풀이 침착도 추이</p>
        </div>
        
        <div class="content">
            <div class="timestamp-info">
                <span id="currentTime">현재 시간: <?php echo date('Y년 m월 d일 H:i:s'); ?></span>
            </div>
            <div class="period-selector">
                <?php if ($tb > 0): ?>
                <button class="period-btn active" data-period="custom">요청 기간 (<?php echo round($tb / 3600); ?>시간)</button>
                <button class="period-btn" data-period="12h">최근 12시간</button>
                <?php else: ?>
                <button class="period-btn" data-period="12h">최근 12시간</button>
                <?php endif; ?>
                <button class="period-btn <?php echo $tb > 0 ? '' : 'active'; ?>" data-period="1w">최근 1주일</button>
                <button class="period-btn" data-period="1m">최근 1개월</button>
                <button class="period-btn" data-period="3m">최근 3개월</button>
            </div>
            
            <div class="chart-container">
                <canvas id="calmnessChart"></canvas>
                <div id="noData" class="no-data" style="display: none;">
                    <div>📊 선택한 기간에 데이터가 없습니다.</div>
                </div>
            </div>
            
            <div class="stats-grid" id="statsGrid">
                <!-- 통계 카드들이 여기에 동적으로 추가됩니다 -->
            </div>
        </div>
    </div>

    <script>
        // PHP에서 JavaScript로 데이터 전달
        const periodData = <?php echo json_encode($period_data); ?>;
        const studentName = "<?php echo htmlspecialchars($student_name); ?>";
        
        // 디버깅용 콘솔 출력
        console.log('Period Data:', periodData);
        console.log('Student Name:', studentName);
        
        let chart = null;
        
        // 디버깅 함수 추가
        window.debugCalmness = function() {
            const arr = periodData['12h'] || [];
            const m = {};
            arr.forEach(p => { 
                m[p.x] = (m[p.x] || 0) + 1; 
            });
            console.log('12h length=', arr.length, 'distinct=', Object.keys(m).length);
            console.log('duplicates=', Object.entries(m).filter(([k,v]) => v > 1));
        };
        
        // 자동 검증 스크립트
        (function() {
            const d = periodData['12h'] || [];
            console.log('[VERIFY] 12h length=', d.length);
            if (d.length) {
                const invalid = d.filter(p => isNaN(p.x));
                console.log('[VERIFY] invalid x count=', invalid.length);
                console.log('[VERIFY] sample first 3', d.slice(0, 3));
            }
            
            // hide 필터 경고
            const meta = periodData['_meta_12h'];
            if (meta && meta.raw > meta.after_hide) {
                console.warn('Most points filtered by hide:', meta.raw, '->', meta.after_hide);
            }
        })();
        
        // 페이지 로드시 초기 데이터 로드
        document.addEventListener('DOMContentLoaded', function() {
            // tb 파라미터가 있으면 custom 기간 표시, 없으면 1w (최근 1주일)
            const initialPeriod = <?php echo $tb > 0 ? "'custom'" : "'1w'"; ?>;
            showPeriodData(initialPeriod);
            updateCurrentTime();
            setInterval(updateCurrentTime, 1000);
        });
        
        // 현재 시간 업데이트
        function updateCurrentTime() {
            const now = new Date();
            const timeStr = now.getFullYear() + '년 ' + 
                           (now.getMonth() + 1).toString().padStart(2, '0') + '월 ' +
                           now.getDate().toString().padStart(2, '0') + '일 ' +
                           now.getHours().toString().padStart(2, '0') + ':' +
                           now.getMinutes().toString().padStart(2, '0') + ':' +
                           now.getSeconds().toString().padStart(2, '0');
            document.getElementById('currentTime').textContent = '현재 시간: ' + timeStr;
        }
        
        // 기간 선택 버튼 이벤트
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // 활성 버튼 변경
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // 데이터 표시
                showPeriodData(this.dataset.period);
            });
        });
        
        function showPeriodData(period) {
            const data = periodData[period] || [];
            
            if (data.length === 0) {
                showNoData(true);
                updateStats([]);
                return;
            }
            
            showNoData(false);
            updateChart(data, period);
            updateStats(data);
        }
        
        // 시간 간격 압축 함수: 1시간 이상인 구간을 30분으로 압축
        function compressTimeGaps(data) {
            if (data.length <= 1) return data;
            
            const compressedData = [data[0]]; // 첫 번째 포인트는 그대로 유지
            const ONE_HOUR_MS = 60 * 60 * 1000; // 1시간 (ms)
            const COMPRESSED_GAP_MS = 30 * 60 * 1000; // 30분 (ms)
            
            for (let i = 1; i < data.length; i++) {
                const prevPoint = compressedData[compressedData.length - 1];
                const currentPoint = data[i];
                const timeGap = currentPoint.x - prevPoint.x;
                
                if (timeGap >= ONE_HOUR_MS) {
                    // 1시간 이상 간격이면 30분으로 압축
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
        
        // 날짜 변경 지점 찾기 함수 (인덱스 포함)
        function findDateChangePoints(data) {
            const dateChanges = [];
            for (let i = 1; i < data.length; i++) {
                const prevDate = new Date(data[i - 1].x);
                const currentDate = new Date(data[i].x);
                
                // 날짜가 바뀌는 경우 (년, 월, 일 중 하나라도 다름)
                if (prevDate.getFullYear() !== currentDate.getFullYear() ||
                    prevDate.getMonth() !== currentDate.getMonth() ||
                    prevDate.getDate() !== currentDate.getDate()) {
                    dateChanges.push({
                        x: data[i].x,
                        date: currentDate,
                        index: i  // 인덱스 추가
                    });
                }
            }
            return dateChanges;
        }
        
        function updateChart(data, period) {
            const ctx = document.getElementById('calmnessChart').getContext('2d');
            
            if (chart) {
                try {
                    chart.destroy();
                } catch (e) {
                    console.warn('Error destroying chart:', e);
                }
            }
            
            // 시간 간격 압축 적용
            const compressedData = compressTimeGaps(data);
            // 날짜 변경 지점은 원본 데이터 기준으로 찾되, 압축된 데이터의 x 좌표 매핑
            const originalDateChanges = findDateChangePoints(data);
            const dateChangePoints = [];
            originalDateChanges.forEach(change => {
                // 인덱스를 직접 사용하여 압축된 데이터에서 해당 포인트 찾기
                if (change.index >= 0 && change.index < compressedData.length) {
                    dateChangePoints.push({
                        x: compressedData[change.index].x,
                        date: change.date
                    });
                }
            });
            
            // 데이터의 최소/최대 타임스탬프 계산 (ms 기준) - 압축된 데이터 기준
            const timestamps = compressedData.map(d => d.x);
            const minTimestamp = Math.min(...timestamps);
            const maxTimestamp = Math.max(...timestamps);
            const timeRangeHours = (maxTimestamp - minTimestamp) / (3600 * 1000); // 시간 단위로 변환
            const dataCount = compressedData.length;
            
            // 단일 데이터 포인트 경우 특별 처리
            if (dataCount === 1) {
                console.log('Single data point detected');
            }
            
            // 시간 단위와 표시 형식을 데이터 범위와 포인트 수에 따라 동적으로 결정
            let timeUnit, displayFormat, maxTicksLimit;
            
            if (timeRangeHours <= 1) {
                // 1시간 이하: 분 단위
                timeUnit = 'minute';
                displayFormat = 'HH:mm';
                maxTicksLimit = Math.min(12, dataCount);
            } else if (timeRangeHours <= 12) {
                // 12시간 이하: 시간 단위
                timeUnit = 'hour';
                displayFormat = 'HH:mm';
                maxTicksLimit = Math.min(12, dataCount);
            } else if (timeRangeHours <= 24 * 7) {
                // 1주일 이하: 일 단위 또는 시간 단위
                if (dataCount > 50) {
                    timeUnit = 'day';
                    displayFormat = 'MMM dd';
                    maxTicksLimit = 7;
                } else {
                    timeUnit = 'hour';
                    displayFormat = 'MMM dd HH:mm';
                    maxTicksLimit = Math.min(12, dataCount);
                }
            } else if (timeRangeHours <= 24 * 30) {
                // 1개월 이하: 일 단위
                timeUnit = 'day';
                displayFormat = 'MMM dd';
                maxTicksLimit = Math.min(15, Math.ceil(timeRangeHours / 24));
            } else {
                // 1개월 초과: 주 단위
                timeUnit = 'week';
                displayFormat = 'MMM dd';
                maxTicksLimit = Math.min(12, Math.ceil(timeRangeHours / (24 * 7)));
            }
            
            console.log('Time range:', timeRangeHours, 'hours, Data points:', dataCount, 
                       'Unit:', timeUnit, 'Max ticks:', maxTicksLimit);
            
            // 물결 표시 및 날짜 구분선 플러그인
            const wavePlugin = {
                id: 'wavePlugin',
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    const chartArea = chart.chartArea;
                    const meta = chart.getDatasetMeta(0);
                    
                    if (!meta.data.length) return;
                    
                    // 데이터의 최소값 찾기
                    const values = chart.data.datasets[0].data.map(d => d.y);
                    const minValue = Math.min(...values);
                    const yScale = chart.scales.y;
                    const xScale = chart.scales.x;
                    
                    // 날짜 구분선 그리기
                    ctx.save();
                    dateChangePoints.forEach(point => {
                        const xPos = xScale.getPixelForValue(point.x);
                        if (xPos >= chartArea.left && xPos <= chartArea.right) {
                            // 수직 구분선
                            ctx.strokeStyle = 'rgba(255, 0, 0, 0.5)';
                            ctx.lineWidth = 2;
                            ctx.setLineDash([5, 5]);
                            ctx.beginPath();
                            ctx.moveTo(xPos, chartArea.top);
                            ctx.lineTo(xPos, chartArea.bottom);
                            ctx.stroke();
                            
                            // 날짜 레이블
                            const dateStr = (point.date.getMonth() + 1) + '월 ' + point.date.getDate() + '일';
                            ctx.fillStyle = 'rgba(255, 0, 0, 0.8)';
                            ctx.font = 'bold 12px Arial';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'top';
                            ctx.fillText(dateStr, xPos, chartArea.top + 5);
                        }
                    });
                    ctx.restore();
                    
                    // 0과 최소값 사이에 물결 표시가 필요한지 확인
                    if (minValue > 20) {
                        const zeroY = yScale.getPixelForValue(0);
                        const minY = yScale.getPixelForValue(minValue - 5);
                        
                        // 물결 패턴 그리기
                        ctx.save();
                        ctx.strokeStyle = 'rgba(0, 0, 0, 0.3)';
                        ctx.lineWidth = 2;
                        ctx.setLineDash([5, 5]);
                        
                        // 물결 그리기
                        ctx.beginPath();
                        const waveHeight = 8;
                        const waveWidth = 15;
                        let x = chartArea.left;
                        
                        ctx.moveTo(x, minY);
                        while (x < chartArea.right) {
                            ctx.quadraticCurveTo(
                                x + waveWidth/2, minY - waveHeight,
                                x + waveWidth, minY
                            );
                            x += waveWidth;
                        }
                        ctx.stroke();
                        
                        // 물결 영역 채우기
                        ctx.fillStyle = 'rgba(200, 200, 200, 0.1)';
                        ctx.fillRect(chartArea.left, minY, chartArea.right - chartArea.left, zeroY - minY);
                        
                        // "생략됨" 텍스트 추가
                        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
                        ctx.font = '12px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText('~ 중간 생략 ~', chartArea.left + (chartArea.right - chartArea.left) / 2, (minY + zeroY) / 2);
                        
                        ctx.restore();
                    }
                }
            };
            
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: '침착도 수준',
                        data: compressedData,
                        parsing: false,  // 수동 파싱 비활성화
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.25,  // 더 부드러운 곡선
                        pointBackgroundColor: 'rgb(102, 126, 234)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,  // 기본 포인트 크기 축소
                        pointHoverRadius: 6  // 호버 시 크기
                    }]
                },
                plugins: [wavePlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        decimation: {
                            enabled: true,
                            algorithm: 'lttb',
                            samples: 300
                        },
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
                            // min/max 자동 계산하도록 제거
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                maxTicksLimit: maxTicksLimit,
                                autoSkip: true,
                                autoSkipPadding: 5,
                                callback: function(value, index, values) {
                                    // 날짜 포맷을 한국어로 커스터마이즈
                                    const date = new Date(value);
                                    if (timeUnit === 'minute') {
                                        return date.getHours().toString().padStart(2, '0') + ':' + 
                                               date.getMinutes().toString().padStart(2, '0');
                                    } else if (timeUnit === 'hour') {
                                        if (displayFormat === 'HH:mm') {
                                            return date.getHours().toString().padStart(2, '0') + ':00';
                                        } else {
                                            return (date.getMonth() + 1) + '/' + date.getDate() + ' ' + 
                                                   date.getHours().toString().padStart(2, '0') + '시';
                                        }
                                    } else if (timeUnit === 'day') {
                                        return (date.getMonth() + 1) + '월 ' + date.getDate() + '일';
                                    } else {
                                        return (date.getMonth() + 1) + '월 ' + date.getDate() + '일';
                                    }
                                }
                            }
                        },
                        y: {
                            beginAtZero: false,
                            // 데이터 기반 동적 범위 설정
                            min: function(context) {
                                const values = context.chart.data.datasets[0].data.map(d => d.y);
                                const minValue = Math.min(...values);
                                // 최소값에서 10% 여유 공간 (최소 0)
                                return Math.max(0, minValue - 10);
                            },
                            max: 105, // 100% 위에 5% 여유 공간
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
        }
        
        function updateStats(data) {
            const statsGrid = document.getElementById('statsGrid');
            
            if (data.length === 0) {
                statsGrid.innerHTML = '<div class="no-data">데이터가 없습니다.</div>';
                return;
            }
            
            if (data.length === 1) {
                statsGrid.innerHTML = '<div class="no-data">첫 데이터가 기록되었습니다.</div>';
                return;
            }
            
            const levels = data.map(d => d.y);
            const average = levels.reduce((a, b) => a + b, 0) / levels.length;
            const max = Math.max(...levels);
            const min = Math.min(...levels);
            const latest = levels[levels.length - 1];
            
            // 최신 데이터 시간 정보 (x는 ms 단위)
            const latestData = data[data.length - 1];
            const firstData = data[0];
            const latestTime = new Date(latestData.x);
            const firstTime = new Date(firstData.x);
            
            // 침착도 등급 계산
            function getCalmnessGrade(level) {
                if (level >= 90) return { grade: 'S+', color: '#28a745', desc: '매우 침착' };
                if (level >= 80) return { grade: 'S', color: '#20c997', desc: '침착' };
                if (level >= 70) return { grade: 'A+', color: '#17a2b8', desc: '양호' };
                if (level >= 60) return { grade: 'A', color: '#007bff', desc: '보통' };
                if (level >= 50) return { grade: 'B+', color: '#6f42c1', desc: '불안정' };
                if (level >= 40) return { grade: 'B', color: '#fd7e14', desc: '매우 불안정' };
                return { grade: 'C', color: '#dc3545', desc: '위험' };
            }
            
            const avgGrade = getCalmnessGrade(Math.round(average));
            const latestGrade = getCalmnessGrade(latest);
            const calmRatio = Math.round(levels.filter(l => l >= 80).length / levels.length * 100);
            
            // 시간 포맷팅 함수
            function formatTime(date) {
                return date.getFullYear() + '년 ' + 
                       (date.getMonth() + 1) + '월 ' +
                       date.getDate() + '일 ' +
                       date.getHours().toString().padStart(2, '0') + ':' +
                       date.getMinutes().toString().padStart(2, '0');
            }
            
            // 시간차 계산
            function getTimeDiff(start, end) {
                const diff = end - start;
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                
                if (hours > 24) {
                    const days = Math.floor(hours / 24);
                    return `${days}일 전`;
                } else if (hours > 0) {
                    return `${hours}시간 ${minutes}분 전`;
                } else {
                    return `${minutes}분 전`;
                }
            }
            
            const timeSinceLatest = getTimeDiff(latestTime, new Date());
            
            statsGrid.innerHTML = `
                <div class="stat-card" style="background: linear-gradient(135deg, ${avgGrade.color}, ${avgGrade.color}dd);">
                    <div class="stat-value">${Math.round(average)}%</div>
                    <div class="stat-label">평균 침착도<br><small>${avgGrade.desc}</small></div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, ${latestGrade.color}, ${latestGrade.color}dd);">
                    <div class="stat-value">${latest}%</div>
                    <div class="stat-label">최근 침착도<br><small>${latestGrade.desc} · ${timeSinceLatest}</small></div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #28a745dd);">
                    <div class="stat-value">${max}%</div>
                    <div class="stat-label">최고 침착도</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #dc3545dd);">
                    <div class="stat-value">${min}%</div>
                    <div class="stat-label">최저 침착도</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${data.length}</div>
                    <div class="stat-label">총 데이터 수<br><small>${formatTime(firstTime)} ~ ${formatTime(latestTime)}</small></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${calmRatio}%</div>
                    <div class="stat-label">침착 비율<br><small>(80% 이상)</small></div>
                </div>
            `;
        }
        
        function showNoData(show) {
            const noData = document.getElementById('noData');
            const chart = document.getElementById('calmnessChart');
            
            if (show) {
                noData.style.display = 'block';
                chart.style.display = 'none';
            } else {
                noData.style.display = 'none';
                chart.style.display = 'block';
            }
        }
    </script>
</body>
</html>
