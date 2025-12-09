<?php
/**
 * File: /students/db/create_breaktimelog_table.php
 * Purpose: 학생 휴식 시간 기록 테이블 생성
 *
 * Table: mdl_abessi_breaktimelog
 * - userid: 학생 ID
 * - duration: 휴식 시간(초 단위)
 * - timecreated: 휴식 종료 시간
 *
 * Error Output: 파일명과 라인 번호 포함
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

try {
    echo "<h2>📊 휴식 시간 기록 테이블 생성</h2>";
    echo "<hr>";

    // 테이블 생성 SQL
    $sql = "CREATE TABLE IF NOT EXISTS mdl_abessi_breaktimelog (
        id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) NOT NULL COMMENT '학생 ID',
        duration INT(11) NOT NULL COMMENT '휴식 시간(초 단위)',
        timecreated BIGINT(10) NOT NULL COMMENT '휴식 종료 시간',
        PRIMARY KEY (id),
        KEY idx_userid (userid),
        KEY idx_timecreated (timecreated),
        KEY idx_userid_time (userid, timecreated)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='학생 휴식 시간 기록 - 120초 이상 휴식만 기록';";

    // 테이블 생성 실행
    $DB->execute($sql);

    echo "<div style='background-color:#d4edda; padding:15px; border:1px solid #c3e6cb; border-radius:5px;'>";
    echo "<h3 style='color:#155724;'>✅ 테이블 생성 성공</h3>";
    echo "<p><strong>테이블명:</strong> mdl_abessi_breaktimelog</p>";
    echo "<p><strong>컬럼 구조:</strong></p>";
    echo "<ul>";
    echo "<li><code>id</code>: AUTO_INCREMENT 기본키</li>";
    echo "<li><code>userid</code>: 학생 ID (BIGINT)</li>";
    echo "<li><code>duration</code>: 휴식 시간 초 단위 (INT)</li>";
    echo "<li><code>timecreated</code>: 휴식 종료 시간 (BIGINT, UNIX timestamp)</li>";
    echo "</ul>";
    echo "<p><strong>인덱스:</strong></p>";
    echo "<ul>";
    echo "<li>PRIMARY KEY (id)</li>";
    echo "<li>INDEX idx_userid (userid) - 학생별 조회 최적화</li>";
    echo "<li>INDEX idx_timecreated (timecreated) - 시간별 조회 최적화</li>";
    echo "<li>INDEX idx_userid_time (userid, timecreated) - 복합 조회 최적화</li>";
    echo "</ul>";
    echo "<p><strong>기록 조건:</strong> 120초(2분) 이상 휴식만 기록</p>";
    echo "</div>";

    // 테이블 구조 확인
    echo "<hr>";
    echo "<h3>📋 테이블 구조 확인</h3>";
    $tableInfo = $DB->get_records_sql("SHOW CREATE TABLE mdl_abessi_breaktimelog");
    echo "<pre style='background-color:#f8f9fa; padding:10px; border:1px solid #dee2e6; overflow-x:auto;'>";
    foreach($tableInfo as $info) {
        echo htmlspecialchars($info->{'create table'});
    }
    echo "</pre>";

    // 샘플 데이터 확인 (있는 경우)
    $sampleCount = $DB->count_records('abessi_breaktimelog');
    echo "<hr>";
    echo "<h3>📊 현재 레코드 수</h3>";
    echo "<p>총 <strong>{$sampleCount}개</strong>의 휴식 기록</p>";

    echo "<hr>";
    echo "<div style='background-color:#fff3cd; padding:15px; border:1px solid #ffeeba; border-radius:5px;'>";
    echo "<h3 style='color:#856404;'>ℹ️ 다음 단계</h3>";
    echo "<ol>";
    echo "<li><strong>check.php 수정</strong>: eventid=33 로직에 duration 기록 추가</li>";
    echo "<li><strong>테스트</strong>: DMN 휴식 버튼 클릭 후 기록 확인</li>";
    echo "<li><strong>검증</strong>: verify_breaktime_log.php로 통계 확인</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background-color:#f8d7da; padding:15px; border:1px solid #f5c6cb; border-radius:5px;'>";
    echo "<h3 style='color:#721c24;'>❌ 오류 발생</h3>";
    echo "<p><strong>파일:</strong> " . __FILE__ . "</p>";
    echo "<p><strong>라인:</strong> " . __LINE__ . "</p>";
    echo "<p><strong>오류 메시지:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>오류 코드:</strong> " . $e->getCode() . "</p>";
    echo "<pre style='background-color:#f8f9fa; padding:10px; border:1px solid #dee2e6;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "</div>";
}
?>

<!--
DB 관련 정보:
- Table: mdl_abessi_breaktimelog
- Fields:
  * id (BIGINT AUTO_INCREMENT)
  * userid (BIGINT) - mdl_user.id 참조
  * duration (INT) - 초 단위 휴식 시간
  * timecreated (BIGINT) - UNIX timestamp
-->
