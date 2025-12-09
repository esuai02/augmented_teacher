<?php
// 에러 디스플레이 활성화 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$userid = optional_param('userid', $USER->id, PARAM_INT);
$days = optional_param('days', 7, PARAM_INT);
$points_per_day = optional_param('points', 10, PARAM_INT);

// 테이블이 없으면 생성
$table_sql = "CREATE TABLE IF NOT EXISTS mdl_alt42_calmness (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    level INT(3) NOT NULL,
    timecreated BIGINT(10) NOT NULL,
    hide TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    KEY userid_idx (userid),
    KEY timecreated_idx (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $DB->execute($table_sql);
    echo "테이블 생성/확인 완료<br>";
} catch (Exception $e) {
    echo "테이블 생성 오류: " . $e->getMessage() . "<br>";
}

if (optional_param('generate', 0, PARAM_INT)) {
    // 기존 데이터 삭제 옵션
    if (optional_param('clear', 0, PARAM_INT)) {
        $DB->delete_records('alt42_calmness', ['userid' => $userid]);
        echo "기존 데이터 삭제 완료<br>";
    }
    
    // 테스트 데이터 생성
    $current_time = time();
    $generated = 0;
    
    for ($d = 0; $d < $days; $d++) {
        for ($p = 0; $p < $points_per_day; $p++) {
            // 시간을 랜덤하게 분산
            $time_offset = $d * 24 * 60 * 60 + rand(0, 24 * 60 * 60);
            $timestamp = $current_time - $time_offset;
            
            // 침착도 레벨 생성 (정규분포 비슷하게)
            $base_level = 75;
            $variation = 15;
            $level = $base_level + rand(-$variation, $variation);
            
            // 시간대별로 침착도 변화 패턴 적용
            $hour = date('H', $timestamp);
            if ($hour >= 14 && $hour <= 16) {
                // 오후 2-4시 피로도로 인한 감소
                $level -= rand(5, 15);
            } elseif ($hour >= 9 && $hour <= 11) {
                // 오전 집중 시간대 증가
                $level += rand(5, 10);
            }
            
            // 0-100 범위로 제한
            $level = max(0, min(100, $level));
            
            $record = new stdClass();
            $record->userid = $userid;
            $record->level = $level;
            $record->timecreated = $timestamp;
            $record->hide = 0;
            
            $DB->insert_record('alt42_calmness', $record);
            $generated++;
        }
    }
    
    echo "<div style='color: green; font-weight: bold;'>✅ {$generated}개의 데이터를 성공적으로 생성했습니다!</div>";
    echo "<a href='calmness.php?userid={$userid}'>침착도 분석 페이지로 이동</a>";
}

// 현재 데이터 수 확인
$count = $DB->count_records('alt42_calmness', ['userid' => $userid]);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>침착도 테스트 데이터 생성</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }
        input[type="checkbox"] {
            margin-right: 10px;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
        }
        button:hover {
            background: #45a049;
        }
        .warning {
            color: #f44336;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧘‍♀️ 침착도 테스트 데이터 생성</h1>
        
        <div class="info">
            <p><strong>현재 사용자 ID:</strong> <?php echo $userid; ?></p>
            <p><strong>현재 데이터 수:</strong> <?php echo $count; ?>개</p>
        </div>
        
        <form method="get">
            <input type="hidden" name="userid" value="<?php echo $userid; ?>">
            
            <label for="days">생성할 일수:</label>
            <input type="number" id="days" name="days" value="7" min="1" max="90">
            
            <label for="points">일당 데이터 포인트 수:</label>
            <input type="number" id="points" name="points" value="10" min="1" max="50">
            
            <div class="checkbox-label">
                <input type="checkbox" id="clear" name="clear" value="1">
                <label for="clear">기존 데이터 삭제 후 생성</label>
            </div>
            <p class="warning">⚠️ 체크하면 이 사용자의 모든 침착도 데이터가 삭제됩니다!</p>
            
            <button type="submit" name="generate" value="1">데이터 생성</button>
        </form>
    </div>
</body>
</html>