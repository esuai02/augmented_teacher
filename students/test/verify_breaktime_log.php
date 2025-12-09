<?php
/**
 * File: /students/test/verify_breaktime_log.php
 * Purpose: 휴식 시간 기록 검증 및 통계 조회
 *
 * 기능:
 * 1. 테이블 존재 확인
 * 2. 최근 휴식 기록 조회
 * 3. 통계 (총 횟수, 총 시간, 평균/최장/최단)
 *
 * Error Output: 파일명과 라인 번호 포함
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;

// 학생 ID 파라미터
$userid = $_GET['userid'] ?? $USER->id;
$days = $_GET['days'] ?? 7; // 기본 7일

// 날짜 범위 계산
$timeFrom = time() - ($days * 86400);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>휴식 시간 기록 검증</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        .success {
            background-color: #d4edda;
            padding: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            color: #155724;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            color: #721c24;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            padding: 15px;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            color: #0c5460;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            padding: 15px;
            border: 1px solid #ffeeba;
            border-radius: 5px;
            color: #856404;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th {
            background-color: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        table tr:hover {
            background-color: #f5f5f5;
        }
        .stat-box {
            display: inline-block;
            width: 200px;
            padding: 20px;
            margin: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .filter-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .filter-form input, .filter-form select {
            padding: 8px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-form button {
            padding: 8px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-form button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📊 휴식 시간 기록 검증 시스템</h1>
    <p>학생 ID: <strong><?php echo $userid; ?></strong> | 조회 기간: 최근 <strong><?php echo $days; ?>일</strong></p>
    <hr>

    <!-- 필터 폼 -->
    <div class="filter-form">
        <form method="GET">
            <label>학생 ID: <input type="number" name="userid" value="<?php echo $userid; ?>" required></label>
            <label>조회 기간:
                <select name="days">
                    <option value="1" <?php echo $days==1 ? 'selected' : ''; ?>>1일</option>
                    <option value="7" <?php echo $days==7 ? 'selected' : ''; ?>>7일</option>
                    <option value="30" <?php echo $days==30 ? 'selected' : ''; ?>>30일</option>
                    <option value="90" <?php echo $days==90 ? 'selected' : ''; ?>>90일</option>
                </select>
            </label>
            <button type="submit">조회</button>
        </form>
    </div>

    <?php
    try {
        // 1. 테이블 존재 확인
        echo "<h2>1️⃣ 테이블 존재 확인</h2>";
        $tableExists = $DB->get_manager()->table_exists('abessi_breaktimelog');

        if($tableExists) {
            echo "<div class='success'>";
            echo "✅ <strong>mdl_abessi_breaktimelog</strong> 테이블이 정상적으로 존재합니다.";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>mdl_abessi_breaktimelog</strong> 테이블이 존재하지 않습니다.<br>";
            echo "먼저 <code>create_breaktimelog_table.php</code>를 실행해주세요.";
            echo "</div>";
            exit;
        }

        // 2. 통계 조회
        echo "<h2>2️⃣ 휴식 통계</h2>";
        $stats = $DB->get_record_sql(
            "SELECT COUNT(*) as total_breaks,
                    SUM(duration) as total_duration,
                    AVG(duration) as avg_duration,
                    MAX(duration) as max_duration,
                    MIN(duration) as min_duration
             FROM {abessi_breaktimelog}
             WHERE userid = ? AND timecreated >= ?",
            [$userid, $timeFrom]
        );

        if($stats && $stats->total_breaks > 0) {
            echo "<div style='text-align:center; margin: 30px 0;'>";

            // 총 휴식 횟수
            echo "<div class='stat-box'>";
            echo "<div class='stat-label'>총 휴식 횟수</div>";
            echo "<div class='stat-value'>{$stats->total_breaks}</div>";
            echo "<div class='stat-label'>회</div>";
            echo "</div>";

            // 총 휴식 시간
            $totalMinutes = floor($stats->total_duration / 60);
            echo "<div class='stat-box' style='background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);'>";
            echo "<div class='stat-label'>총 휴식 시간</div>";
            echo "<div class='stat-value'>{$totalMinutes}</div>";
            echo "<div class='stat-label'>분</div>";
            echo "</div>";

            // 평균 휴식 시간
            $avgMinutes = floor($stats->avg_duration / 60);
            $avgSeconds = $stats->avg_duration % 60;
            echo "<div class='stat-box' style='background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);'>";
            echo "<div class='stat-label'>평균 휴식 시간</div>";
            echo "<div class='stat-value'>{$avgMinutes}:{$avgSeconds}</div>";
            echo "<div class='stat-label'>분:초</div>";
            echo "</div>";

            // 최장 휴식
            $maxMinutes = floor($stats->max_duration / 60);
            $maxSeconds = $stats->max_duration % 60;
            echo "<div class='stat-box' style='background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);'>";
            echo "<div class='stat-label'>최장 휴식</div>";
            echo "<div class='stat-value'>{$maxMinutes}:{$maxSeconds}</div>";
            echo "<div class='stat-label'>분:초</div>";
            echo "</div>";

            echo "</div>";
        } else {
            echo "<div class='warning'>";
            echo "⚠️ 최근 {$days}일 동안 기록된 휴식이 없습니다.";
            echo "</div>";
        }

        // 3. 최근 휴식 기록 조회
        echo "<h2>3️⃣ 최근 휴식 기록</h2>";
        $recentBreaks = $DB->get_records_sql(
            "SELECT id, userid, duration, timecreated,
                    FROM_UNIXTIME(timecreated, '%Y-%m-%d %H:%i:%s') as break_time
             FROM {abessi_breaktimelog}
             WHERE userid = ? AND timecreated >= ?
             ORDER BY timecreated DESC
             LIMIT 20",
            [$userid, $timeFrom]
        );

        if($recentBreaks) {
            echo "<table>";
            echo "<tr>";
            echo "<th>ID</th>";
            echo "<th>휴식 시간 (초)</th>";
            echo "<th>휴식 시간 (분:초)</th>";
            echo "<th>종료 시간</th>";
            echo "<th>상태</th>";
            echo "</tr>";

            foreach($recentBreaks as $break) {
                $minutes = floor($break->duration / 60);
                $seconds = $break->duration % 60;

                // 휴식 시간에 따른 상태 표시
                $status = '';
                $statusColor = '';
                if($break->duration < 300) { // 5분 미만
                    $status = '짧은 휴식';
                    $statusColor = '#28a745'; // 초록색
                } elseif($break->duration < 900) { // 15분 미만
                    $status = '적정 휴식';
                    $statusColor = '#007bff'; // 파랑색
                } else { // 15분 이상
                    $status = '긴 휴식';
                    $statusColor = '#ffc107'; // 노랑색
                }

                echo "<tr>";
                echo "<td>{$break->id}</td>";
                echo "<td>{$break->duration}초</td>";
                echo "<td><strong>{$minutes}분 {$seconds}초</strong></td>";
                echo "<td>{$break->break_time}</td>";
                echo "<td><span style='color:{$statusColor}; font-weight:bold;'>{$status}</span></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>";
            echo "ℹ️ 최근 {$days}일 동안 기록된 휴식이 없습니다.";
            echo "</div>";
        }

        // 4. 일별 통계
        echo "<h2>4️⃣ 일별 휴식 통계</h2>";
        $dailyStats = $DB->get_records_sql(
            "SELECT DATE(FROM_UNIXTIME(timecreated)) as break_date,
                    COUNT(*) as daily_breaks,
                    SUM(duration) as daily_duration,
                    AVG(duration) as avg_duration
             FROM {abessi_breaktimelog}
             WHERE userid = ? AND timecreated >= ?
             GROUP BY DATE(FROM_UNIXTIME(timecreated))
             ORDER BY break_date DESC",
            [$userid, $timeFrom]
        );

        if($dailyStats) {
            echo "<table>";
            echo "<tr>";
            echo "<th>날짜</th>";
            echo "<th>휴식 횟수</th>";
            echo "<th>총 휴식 시간</th>";
            echo "<th>평균 휴식 시간</th>";
            echo "</tr>";

            foreach($dailyStats as $stat) {
                $totalMin = floor($stat->daily_duration / 60);
                $avgMin = floor($stat->avg_duration / 60);
                $avgSec = $stat->avg_duration % 60;

                echo "<tr>";
                echo "<td>{$stat->break_date}</td>";
                echo "<td>{$stat->daily_breaks}회</td>";
                echo "<td>{$totalMin}분</td>";
                echo "<td>{$avgMin}분 {$avgSec}초</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

        // 5. 링크
        echo "<hr>";
        echo "<div class='info'>";
        echo "<h3>🔗 관련 페이지</h3>";
        echo "<ul>";
        echo "<li><a href='https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding.php?userid={$userid}' target='_blank'>학생 학습 페이지</a></li>";
        echo "<li><a href='create_breaktimelog_table.php' target='_blank'>테이블 생성 페이지</a></li>";
        echo "</ul>";
        echo "</div>";

    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ 오류 발생</h3>";
        echo "<p><strong>파일:</strong> " . __FILE__ . "</p>";
        echo "<p><strong>라인:</strong> " . __LINE__ . "</p>";
        echo "<p><strong>오류 메시지:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre style='background-color:#f8f9fa; padding:10px; border:1px solid #dee2e6; overflow-x:auto;'>";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
        echo "</div>";
    }
    ?>

</div>

</body>
</html>

<!--
DB 관련 정보:
- Table: mdl_abessi_breaktimelog
- Fields:
  * id (BIGINT AUTO_INCREMENT)
  * userid (BIGINT) - mdl_user.id
  * duration (INT) - 초 단위
  * timecreated (BIGINT) - UNIX timestamp
-->
