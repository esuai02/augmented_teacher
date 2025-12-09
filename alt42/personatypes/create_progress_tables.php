<?php
/**
 * 사용자 진행 상황 추적 테이블 생성
 */

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
require_capability('moodle/site:config', context_system::instance());

// 페이지 설정
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/augmented_teacher/alt42/shiningstars/create_progress_tables.php');
$PAGE->set_title('진행 상황 테이블 생성');
$PAGE->set_heading('사용자 진행 상황 추적 테이블 생성');

echo $OUTPUT->header();

echo '<div style="max-width: 800px; margin: 0 auto; padding: 20px;">';
echo '<h2>테이블 생성 프로세스</h2>';

try {
    // 1. User Pattern Progress Table 생성
    echo '<h3>1. 사용자 패턴 진행 상황 테이블 (mdl_alt42i_user_pattern_progress)</h3>';
    
    $sql1 = "CREATE TABLE IF NOT EXISTS mdl_alt42i_user_pattern_progress (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT(10) UNSIGNED NOT NULL,
        pattern_id INT(10) UNSIGNED NOT NULL,
        is_collected TINYINT(1) DEFAULT 0,
        mastery_level INT(3) DEFAULT 0,
        practice_count INT(10) DEFAULT 0,
        last_practice_at DATETIME DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_user_pattern (user_id, pattern_id),
        KEY idx_pattern (pattern_id),
        CONSTRAINT fk_progress_pattern FOREIGN KEY (pattern_id) 
            REFERENCES mdl_alt42i_math_patterns(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_pattern (user_id, pattern_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql1);
    echo '<p style="color: green;">✓ 사용자 패턴 진행 상황 테이블이 생성되었습니다.</p>';
    
    // 2. Pattern Practice Logs Table 생성 (선택사항)
    echo '<h3>2. 패턴 연습 로그 테이블 (mdl_alt42i_pattern_practice_logs) - 선택사항</h3>';
    
    $sql2 = "CREATE TABLE IF NOT EXISTS mdl_alt42i_pattern_practice_logs (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT(10) UNSIGNED NOT NULL,
        pattern_id INT(10) UNSIGNED NOT NULL,
        practice_type VARCHAR(50) DEFAULT 'self',
        duration_seconds INT(10) DEFAULT 0,
        feedback TEXT DEFAULT NULL,
        is_completed TINYINT(1) DEFAULT 1,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_user_pattern_date (user_id, pattern_id, created_at),
        CONSTRAINT fk_log_pattern FOREIGN KEY (pattern_id) 
            REFERENCES mdl_alt42i_math_patterns(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql2);
    echo '<p style="color: green;">✓ 패턴 연습 로그 테이블이 생성되었습니다.</p>';
    
    // 3. Audio Play Logs Table 생성 (선택사항)
    echo '<h3>3. 오디오 재생 로그 테이블 (mdl_alt42i_audio_play_logs) - 선택사항</h3>';
    
    $sql3 = "CREATE TABLE IF NOT EXISTS mdl_alt42i_audio_play_logs (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT(10) UNSIGNED NOT NULL,
        pattern_id INT(10) UNSIGNED NOT NULL,
        played_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_user_pattern_play (user_id, pattern_id, played_at),
        CONSTRAINT fk_audio_pattern FOREIGN KEY (pattern_id) 
            REFERENCES mdl_alt42i_math_patterns(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql3);
    echo '<p style="color: green;">✓ 오디오 재생 로그 테이블이 생성되었습니다.</p>';
    
    // 4. 테이블 확인
    echo '<h3>4. 생성된 테이블 확인</h3>';
    
    $tables = [
        'alt42i_user_pattern_progress' => '사용자 패턴 진행 상황',
        'alt42i_pattern_practice_logs' => '패턴 연습 로그',
        'alt42i_audio_play_logs' => '오디오 재생 로그'
    ];
    
    echo '<ul>';
    foreach ($tables as $table => $desc) {
        $exists = $DB->get_manager()->table_exists($table);
        $status = $exists ? '<span style="color: green;">✓ 존재함</span>' : '<span style="color: red;">✗ 없음</span>';
        echo "<li>{$desc} ({$table}): {$status}</li>";
    }
    echo '</ul>';
    
    // 5. 샘플 데이터 추가 (선택사항)
    echo '<h3>5. 샘플 진행 데이터 추가</h3>';
    
    // 현재 사용자를 위한 샘플 데이터 추가
    $sample_progress = new stdClass();
    $sample_progress->user_id = $USER->id;
    $sample_progress->pattern_id = 1; // 첫 번째 패턴
    $sample_progress->is_collected = 1;
    $sample_progress->mastery_level = 50;
    $sample_progress->practice_count = 3;
    $sample_progress->last_practice_at = date('Y-m-d H:i:s');
    $sample_progress->notes = '이 패턴을 연습하면서 많은 도움이 되었습니다.';
    $sample_progress->created_at = date('Y-m-d H:i:s');
    $sample_progress->updated_at = date('Y-m-d H:i:s');
    
    // 중복 체크
    $existing = $DB->get_record('alt42i_user_pattern_progress', [
        'user_id' => $USER->id,
        'pattern_id' => 1
    ]);
    
    if (!$existing) {
        $DB->insert_record('alt42i_user_pattern_progress', $sample_progress);
        echo '<p style="color: green;">✓ 샘플 진행 데이터가 추가되었습니다.</p>';
    } else {
        echo '<p style="color: orange;">⚠ 이미 진행 데이터가 존재합니다.</p>';
    }
    
    echo '<h3>완료!</h3>';
    echo '<p>모든 테이블이 성공적으로 생성되었습니다.</p>';
    echo '<p><a href="show_math_patterns.php" class="btn btn-primary">수학 인지관성 도감 보기</a></p>';
    echo '<p><a href="index.php" class="btn btn-secondary">메인 페이지로 돌아가기</a></p>';
    
} catch (Exception $e) {
    echo '<div style="color: red; padding: 10px; background: #ffe0e0; border-radius: 5px;">';
    echo '<strong>오류 발생:</strong> ' . $e->getMessage();
    echo '</div>';
    
    echo '<h3>문제 해결 방법:</h3>';
    echo '<ol>';
    echo '<li>데이터베이스 권한을 확인하세요.</li>';
    echo '<li>mdl_alt42i_math_patterns 테이블이 먼저 생성되어 있어야 합니다.</li>';
    echo '<li>외래 키 제약 조건 오류가 발생하면 다음 SQL을 먼저 실행하세요:<br>';
    echo '<pre>SET FOREIGN_KEY_CHECKS = 0;</pre></li>';
    echo '</ol>';
}

echo '</div>';

echo $OUTPUT->footer();