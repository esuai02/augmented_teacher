<?php
/**
 * 진행 상황 테이블 생성 (외래 키 없는 간단한 버전)
 */

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// 관리자 권한 확인
if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

echo "<h2>진행 상황 테이블 생성 (간단 버전)</h2>";

try {
    // 1. 외래 키 체크 비활성화
    echo "<p>외래 키 체크 비활성화 중...</p>";
    $DB->execute("SET FOREIGN_KEY_CHECKS = 0");
    
    // 2. 기존 테이블 삭제 (있는 경우)
    echo "<p>기존 테이블 확인 중...</p>";
    if ($DB->get_manager()->table_exists('alt42i_user_pattern_progress')) {
        $DB->execute("DROP TABLE IF EXISTS mdl_alt42i_user_pattern_progress");
        echo "<p>기존 테이블을 삭제했습니다.</p>";
    }
    
    // 3. 테이블 생성 (외래 키 없이)
    echo "<p>새 테이블 생성 중...</p>";
    $sql = "CREATE TABLE mdl_alt42i_user_pattern_progress (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT(10) UNSIGNED NOT NULL,
        pattern_id INT(10) UNSIGNED NOT NULL,
        is_collected TINYINT(1) DEFAULT 0,
        mastery_level INT(3) DEFAULT 0,
        practice_count INT(10) DEFAULT 0,
        last_practice_at DATETIME DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user_pattern (user_id, pattern_id),
        KEY idx_pattern (pattern_id),
        UNIQUE KEY unique_user_pattern (user_id, pattern_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql);
    echo "<p style='color: green;'>✅ 테이블이 성공적으로 생성되었습니다!</p>";
    
    // 4. 다른 테이블도 생성 (선택사항)
    echo "<h3>추가 테이블 생성</h3>";
    
    // 연습 로그 테이블
    $sql2 = "CREATE TABLE IF NOT EXISTS mdl_alt42i_pattern_practice_logs (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT(10) UNSIGNED NOT NULL,
        pattern_id INT(10) UNSIGNED NOT NULL,
        practice_type VARCHAR(50) DEFAULT 'self',
        duration_seconds INT(10) DEFAULT 0,
        feedback TEXT DEFAULT NULL,
        is_completed TINYINT(1) DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user_pattern_date (user_id, pattern_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql2);
    echo "<p style='color: green;'>✅ 연습 로그 테이블이 생성되었습니다.</p>";
    
    // 오디오 재생 로그 테이블
    $sql3 = "CREATE TABLE IF NOT EXISTS mdl_alt42i_audio_play_logs (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT(10) UNSIGNED NOT NULL,
        pattern_id INT(10) UNSIGNED NOT NULL,
        played_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user_pattern_play (user_id, pattern_id, played_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql3);
    echo "<p style='color: green;'>✅ 오디오 재생 로그 테이블이 생성되었습니다.</p>";
    
    // 5. 외래 키 체크 다시 활성화
    $DB->execute("SET FOREIGN_KEY_CHECKS = 1");
    echo "<p>외래 키 체크를 다시 활성화했습니다.</p>";
    
    // 6. 테이블 확인
    echo "<h3>생성된 테이블 확인</h3>";
    $tables = [
        'alt42i_user_pattern_progress' => '사용자 진행 상황',
        'alt42i_pattern_practice_logs' => '연습 로그',
        'alt42i_audio_play_logs' => '오디오 재생 로그'
    ];
    
    echo "<ul>";
    foreach ($tables as $table => $desc) {
        if ($DB->get_manager()->table_exists($table)) {
            echo "<li style='color: green;'>✅ {$desc} 테이블이 존재합니다.</li>";
        } else {
            echo "<li style='color: red;'>❌ {$desc} 테이블이 없습니다.</li>";
        }
    }
    echo "</ul>";
    
    // 7. 샘플 데이터 추가
    echo "<h3>샘플 데이터 추가</h3>";
    
    // 첫 5개 패턴에 대한 샘플 데이터
    for ($i = 1; $i <= 5; $i++) {
        $existing = $DB->get_record('alt42i_user_pattern_progress', [
            'user_id' => $USER->id,
            'pattern_id' => $i
        ]);
        
        if (!$existing) {
            $progress = new stdClass();
            $progress->user_id = $USER->id;
            $progress->pattern_id = $i;
            $progress->is_collected = 1;
            $progress->mastery_level = rand(30, 80);
            $progress->practice_count = rand(1, 5);
            $progress->created_at = date('Y-m-d H:i:s');
            $progress->updated_at = date('Y-m-d H:i:s');
            
            $DB->insert_record('alt42i_user_pattern_progress', $progress);
            echo "<p>패턴 #{$i}에 대한 샘플 데이터를 추가했습니다.</p>";
        }
    }
    
    echo "<h3>✅ 완료!</h3>";
    echo "<p>모든 작업이 성공적으로 완료되었습니다.</p>";
    echo "<p><a href='show_math_patterns.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>수학 인지관성 도감 보기</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 오류 발생: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    
    // 디버깅 정보
    echo "<h3>디버깅 정보</h3>";
    echo "<p>데이터베이스 타입: " . $CFG->dbtype . "</p>";
    echo "<p>테이블 프리픽스: " . $CFG->prefix . "</p>";
}