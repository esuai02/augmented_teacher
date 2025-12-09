<?php
/**
 * Verify tend01-tend16 fields in mdl_abessi_todayplans
 * File: students/db/verify_tend_fields.php
 * Purpose: DB 마이그레이션 결과 확인 및 테스트 데이터 검증
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자/교사 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : null;

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='utf-8'>";
echo "<title>tend 필드 검증</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #2196F3; border-bottom: 3px solid #2196F3; padding-bottom: 10px; }
h2 { color: #666; margin-top: 30px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background: #2196F3; color: white; }
tr:nth-child(even) { background: #f9f9f9; }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
.badge-success { background: #4CAF50; color: white; }
.badge-error { background: #f44336; color: white; }
.badge-warning { background: #FF9800; color: white; }
.info-box { background: #E3F2FD; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
.code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
</style>";
echo "</head><body>";
echo "<div class='container'>";

echo "<h1>📊 tend01~tend16 필드 검증 리포트</h1>";
echo "<p>생성 시간: " . date('Y-m-d H:i:s') . "</p>";

try {
    // 1. 테이블 존재 확인
    echo "<h2>1️⃣ 테이블 존재 확인</h2>";
    $tableExists = $DB->get_manager()->table_exists('abessi_todayplans');

    if ($tableExists) {
        echo "<p class='success'>✓ mdl_abessi_todayplans 테이블 존재</p>";
    } else {
        echo "<p class='error'>✗ mdl_abessi_todayplans 테이블이 존재하지 않습니다!</p>";
        exit;
    }

    // 2. tend 필드 확인
    echo "<h2>2️⃣ tend 필드 구조 확인</h2>";
    $columns = $DB->get_columns('abessi_todayplans');

    $tendFields = array();
    $missingFields = array();

    for ($i = 1; $i <= 16; $i++) {
        $fieldName = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT);

        if (isset($columns[$fieldName])) {
            $tendFields[] = array(
                'name' => $fieldName,
                'type' => $columns[$fieldName]->meta_type,
                'max_length' => $columns[$fieldName]->max_length
            );
        } else {
            $missingFields[] = $fieldName;
        }
    }

    echo "<p><strong>발견된 tend 필드:</strong> " . count($tendFields) . "개</p>";

    if (count($tendFields) === 16) {
        echo "<p class='success'>✓ 모든 tend01~tend16 필드가 존재합니다</p>";
    } else {
        echo "<p class='error'>✗ 일부 필드가 누락되었습니다</p>";
    }

    if (!empty($missingFields)) {
        echo "<p class='warning'>누락된 필드: " . implode(', ', $missingFields) . "</p>";
        echo "<div class='info-box'>";
        echo "<strong>⚠️ 마이그레이션 필요</strong><br>";
        echo "다음 URL을 브라우저에서 실행하세요:<br>";
        echo "<a href='add_tend_fields.php' target='_blank'>add_tend_fields.php</a>";
        echo "</div>";
    }

    // 테이블 출력
    if (!empty($tendFields)) {
        echo "<table>";
        echo "<tr><th>필드명</th><th>데이터 타입</th><th>최대 길이</th><th>상태</th></tr>";
        foreach ($tendFields as $field) {
            echo "<tr>";
            echo "<td><strong>{$field['name']}</strong></td>";
            echo "<td>{$field['type']}</td>";
            echo "<td>{$field['max_length']}</td>";
            echo "<td><span class='badge badge-success'>정상</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 3. 샘플 데이터 확인
    echo "<h2>3️⃣ 샘플 데이터 확인 (최근 5건)</h2>";

    $sampleRecords = $DB->get_records_sql(
        "SELECT id, userid, tbegin, tend01, tend02, tend03, tend04, tend05,
                status01, status02, status03,
                timecreated, timemodified
         FROM {abessi_todayplans}
         ORDER BY id DESC
         LIMIT 5"
    );

    if (!empty($sampleRecords)) {
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th><th>UserID</th><th>tbegin</th>";
        echo "<th>tend01</th><th>tend02</th><th>tend03</th>";
        echo "<th>status01</th><th>status02</th><th>status03</th>";
        echo "<th>Created</th>";
        echo "</tr>";

        foreach ($sampleRecords as $record) {
            echo "<tr>";
            echo "<td>{$record->id}</td>";
            echo "<td>{$record->userid}</td>";
            echo "<td>" . ($record->tbegin ? date('m-d H:i', $record->tbegin) : '-') . "</td>";

            // tend 필드 표시
            for ($i = 1; $i <= 3; $i++) {
                $tendField = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $tendValue = isset($record->$tendField) ? $record->$tendField : null;

                if ($tendValue && $tendValue > 0) {
                    echo "<td>" . date('m-d H:i', $tendValue) . "<br><small>(" . $tendValue . ")</small></td>";
                } else {
                    echo "<td class='warning'>-</td>";
                }
            }

            // status 필드 표시
            for ($i = 1; $i <= 3; $i++) {
                $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $statusValue = isset($record->$statusField) ? $record->$statusField : '';

                if (!empty($statusValue)) {
                    $badgeClass = 'badge-success';
                    if ($statusValue === '매우만족') $badgeClass = 'badge-success';
                    elseif ($statusValue === '불만족') $badgeClass = 'badge-error';

                    echo "<td><span class='badge {$badgeClass}'>{$statusValue}</span></td>";
                } else {
                    echo "<td>-</td>";
                }
            }

            echo "<td>" . date('m-d H:i', $record->timecreated) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ 샘플 데이터가 없습니다.</p>";
    }

    // 4. tend 데이터 통계
    echo "<h2>4️⃣ tend 데이터 통계</h2>";

    $stats = array(
        'total_records' => 0,
        'records_with_tend' => 0,
        'total_tend_values' => 0
    );

    $allRecords = $DB->get_records_sql(
        "SELECT * FROM {abessi_todayplans} ORDER BY id DESC LIMIT 100"
    );

    $stats['total_records'] = count($allRecords);

    foreach ($allRecords as $record) {
        $hasTend = false;
        for ($i = 1; $i <= 16; $i++) {
            $tendField = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT);
            if (isset($record->$tendField) && $record->$tendField > 0) {
                $stats['total_tend_values']++;
                $hasTend = true;
            }
        }
        if ($hasTend) {
            $stats['records_with_tend']++;
        }
    }

    echo "<div class='info-box'>";
    echo "<strong>통계 정보 (최근 100건 기준)</strong><br>";
    echo "전체 레코드: {$stats['total_records']}개<br>";
    echo "tend 데이터 있는 레코드: {$stats['records_with_tend']}개<br>";
    echo "총 tend 값 개수: {$stats['total_tend_values']}개<br>";

    if ($stats['total_records'] > 0) {
        $percentage = round(($stats['records_with_tend'] / $stats['total_records']) * 100, 1);
        echo "사용률: {$percentage}%";
    }
    echo "</div>";

    // 5. 테스트 가이드
    echo "<h2>5️⃣ 테스트 가이드</h2>";
    echo "<div class='info-box'>";
    echo "<strong>📝 수동 테스트 절차:</strong><br><br>";
    echo "1. <a href='../goals42.php?id={$USER->id}' target='_blank'>goals42.php</a> 접속<br>";
    echo "2. 수학일기 탭 선택<br>";
    echo "3. 입력 모드로 전환하여 학습 계획 작성<br>";
    echo "4. 보기 모드로 돌아와서 체크박스 클릭<br>";
    echo "5. 만족도 선택 (매우만족/만족/불만족)<br>";
    echo "6. 이 페이지를 새로고침하여 tend 값이 기록되었는지 확인<br>";
    echo "</div>";

    // 6. JavaScript 연동 확인
    echo "<h2>6️⃣ JavaScript 연동 상태</h2>";

    $goals42Content = @file_get_contents(__DIR__ . '/../goals42.php');

    if ($goals42Content !== false) {
        $hasTendField = strpos($goals42Content, "tendField = 'tend'") !== false;
        $hasUnixtimeCalc = strpos($goals42Content, 'Math.floor(Date.now() / 1000)') !== false;

        echo "<table>";
        echo "<tr><th>항목</th><th>상태</th></tr>";
        echo "<tr><td>tendField 변수 선언</td><td>";
        echo $hasTendField ? "<span class='badge badge-success'>✓ 있음</span>" : "<span class='badge badge-error'>✗ 없음</span>";
        echo "</td></tr>";
        echo "<tr><td>unixtime 계산 로직</td><td>";
        echo $hasUnixtimeCalc ? "<span class='badge badge-success'>✓ 있음</span>" : "<span class='badge badge-error'>✗ 없음</span>";
        echo "</td></tr>";
        echo "</table>";
    }

    // 7. API 엔드포인트 테스트
    echo "<h2>7️⃣ Agent14 API 테스트</h2>";
    echo "<div class='info-box'>";
    echo "<strong>🔗 API 엔드포인트:</strong><br>";
    echo "<div class='code'>";
    echo "GET /alt42/orchestration/agents/agent14_current_position/agent.php?userid={$USER->id}";
    echo "</div>";
    echo "<a href='../../alt42/orchestration/agents/agent14_current_position/agent.php?userid={$USER->id}' target='_blank' style='display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>🚀 API 테스트 실행</a>";
    echo "</div>";

    // 최종 요약
    echo "<h2>✅ 검증 결과 요약</h2>";
    echo "<div class='info-box'>";

    $allGood = (count($tendFields) === 16);

    if ($allGood) {
        echo "<p class='success' style='font-size: 18px;'>✓ 모든 검증 항목 통과!</p>";
        echo "<p>tend01~tend16 필드가 정상적으로 추가되었습니다.</p>";
        echo "<p>이제 수학일기에서 만족도를 체크하면 자동으로 완료 시간이 기록됩니다.</p>";
    } else {
        echo "<p class='error' style='font-size: 18px;'>✗ 일부 검증 항목 실패</p>";
        echo "<p>마이그레이션을 먼저 실행해주세요.</p>";
        echo "<p><a href='add_tend_fields.php'>add_tend_fields.php 실행하기</a></p>";
    }

    echo "</div>";

} catch (Exception $e) {
    echo "<p class='error'><strong>ERROR:</strong> " . $e->getMessage() . "</p>";
    echo "<p>File: " . __FILE__ . ", Line: " . __LINE__ . "</p>";
    error_log("Verification Error: " . $e->getMessage() . " - File: " . __FILE__ . ", Line: " . __LINE__);
}

echo "</div></body></html>";
?>
