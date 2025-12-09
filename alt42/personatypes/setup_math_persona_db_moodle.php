<?php
/**
 * 수학 학습 패턴 데이터베이스 설치 스크립트 - Moodle API 버전
 * Moodle의 DB API를 사용하여 안전하게 테이블 생성
 */

// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;
require_login(); 

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// 페이지 설정
$PAGE->set_url('/shiningstars/setup_math_persona_db_moodle.php');
$PAGE->set_context($context);
$PAGE->set_title('수학 학습 패턴 DB 설치');
$PAGE->set_heading('수학 학습 패턴 데이터베이스 설치');

// 출력 시작
echo $OUTPUT->header();
?>

<style>
    .setup-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    .status-box {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
    }
    .success {
        color: #28a745;
        font-weight: bold;
    }
    .error {
        color: #dc3545;
        font-weight: bold;
    }
    .warning {
        color: #ffc107;
        font-weight: bold;
    }
    .step {
        margin: 10px 0;
        padding: 10px;
        background: #ffffff;
        border-left: 3px solid #007bff;
        border-radius: 3px;
    }
    .btn-install {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
    }
    .btn-check {
        background: #17a2b8;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
    }
    .btn-delete {
        background: #dc3545;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
    }
</style>

<div class="setup-container">
    <h2>🎯 Moodle 수학 학습 패턴 데이터베이스 설치</h2>
    
    <?php
    $action = optional_param('action', '', PARAM_ALPHA);
    
    if ($action === 'install') {
        echo '<div class="status-box">';
        echo '<h3>📊 설치 진행 상황</h3>';
        
        $transaction = $DB->start_delegated_transaction();
        
        try {
            // 1. 카테고리 테이블 확인 및 생성
            echo '<div class="step">카테고리 테이블 확인 중...</div>';
            
            $table = new xmldb_table('alt42i_pattern_categories');
            if (!$DB->get_manager()->table_exists($table)) {
                // 테이블이 없으면 직접 SQL로 생성
                $sql = "CREATE TABLE IF NOT EXISTS {alt42i_pattern_categories} (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    category_name VARCHAR(100) NOT NULL,
                    category_code VARCHAR(50) NOT NULL,
                    display_order BIGINT(10) DEFAULT 0,
                    description TEXT,
                    timecreated BIGINT(10) NOT NULL,
                    timemodified BIGINT(10) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uk_category_code (category_code),
                    KEY idx_display_order (display_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $DB->execute($sql);
                echo '<div class="step"><span class="success">✅ 카테고리 테이블 생성 완료</span></div>';
            } else {
                echo '<div class="step"><span class="warning">⚠️ 카테고리 테이블 이미 존재</span></div>';
            }
            
            // 2. 패턴 메인 테이블
            echo '<div class="step">패턴 메인 테이블 확인 중...</div>';
            
            $table = new xmldb_table('alt42i_math_patterns');
            if (!$DB->get_manager()->table_exists($table)) {
                $sql = "CREATE TABLE IF NOT EXISTS {alt42i_math_patterns} (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    pattern_id BIGINT(10) NOT NULL,
                    pattern_name VARCHAR(100) NOT NULL,
                    pattern_desc TEXT NOT NULL,
                    category_id BIGINT(10) NOT NULL,
                    icon VARCHAR(10) DEFAULT '📊',
                    priority VARCHAR(10) DEFAULT 'medium',
                    audio_time VARCHAR(20) DEFAULT '3분',
                    is_active TINYINT(1) DEFAULT 1,
                    timecreated BIGINT(10) NOT NULL,
                    timemodified BIGINT(10) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uk_pattern_id (pattern_id),
                    KEY idx_category (category_id),
                    KEY idx_priority (priority),
                    KEY idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $DB->execute($sql);
                echo '<div class="step"><span class="success">✅ 패턴 테이블 생성 완료</span></div>';
            } else {
                echo '<div class="step"><span class="warning">⚠️ 패턴 테이블 이미 존재</span></div>';
            }
            
            // 3. 해결책 테이블
            echo '<div class="step">해결책 테이블 확인 중...</div>';
            
            $table = new xmldb_table('alt42i_pattern_solutions');
            if (!$DB->get_manager()->table_exists($table)) {
                $sql = "CREATE TABLE IF NOT EXISTS {alt42i_pattern_solutions} (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    pattern_id BIGINT(10) NOT NULL,
                    action TEXT NOT NULL,
                    check_method TEXT NOT NULL,
                    audio_script TEXT,
                    teacher_dialog TEXT,
                    example_problem TEXT,
                    practice_guide TEXT,
                    timecreated BIGINT(10) NOT NULL,
                    timemodified BIGINT(10) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uk_pattern_solution (pattern_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $DB->execute($sql);
                echo '<div class="step"><span class="success">✅ 해결책 테이블 생성 완료</span></div>';
            } else {
                echo '<div class="step"><span class="warning">⚠️ 해결책 테이블 이미 존재</span></div>';
            }
            
            // 4. 사용자 진행 상황 테이블
            echo '<div class="step">사용자 진행 상황 테이블 확인 중...</div>';
            
            $table = new xmldb_table('alt42i_user_pattern_progress');
            if (!$DB->get_manager()->table_exists($table)) {
                $sql = "CREATE TABLE IF NOT EXISTS {alt42i_user_pattern_progress} (
                    id BIGINT(10) NOT NULL AUTO_INCREMENT,
                    userid BIGINT(10) NOT NULL,
                    pattern_id BIGINT(10) NOT NULL,
                    is_collected TINYINT(1) DEFAULT 0,
                    mastery_level BIGINT(10) DEFAULT 0,
                    practice_count BIGINT(10) DEFAULT 0,
                    last_practice_at BIGINT(10) DEFAULT NULL,
                    improvement_score DECIMAL(5,2) DEFAULT 0,
                    notes TEXT,
                    timecreated BIGINT(10) NOT NULL,
                    timemodified BIGINT(10) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uk_user_pattern (userid, pattern_id),
                    KEY idx_user (userid),
                    KEY idx_pattern (pattern_id),
                    KEY idx_collected (is_collected),
                    KEY idx_mastery (mastery_level)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $DB->execute($sql);
                echo '<div class="step"><span class="success">✅ 사용자 진행 상황 테이블 생성 완료</span></div>';
            } else {
                echo '<div class="step"><span class="warning">⚠️ 진행 상황 테이블 이미 존재</span></div>';
            }
            
            // 카테고리 데이터 삽입
            echo '<div class="step">카테고리 데이터 삽입 중...</div>';
            
            $categories = [
                ['인지 과부하', 'cognitive_overload', 1, '정보 처리 용량 초과로 인한 학습 장애'],
                ['자신감 왜곡', 'confidence_distortion', 2, '자신감 수준과 실제 능력 간의 불일치'],
                ['실수 패턴', 'mistake_patterns', 3, '반복적으로 나타나는 실수 유형'],
                ['접근 전략 오류', 'approach_errors', 4, '문제 해결 전략의 부적절한 선택'],
                ['학습 습관', 'study_habits', 5, '비효율적인 학습 방법과 습관'],
                ['시간/압박 관리', 'time_pressure', 6, '시간 관리 및 압박 대처 문제'],
                ['검증/확인 부재', 'verification_absence', 7, '답안 검토 및 확인 과정 부족'],
                ['기타 장애', 'other_obstacles', 8, '기타 학습 장애 요인']
            ];
            
            $now = time();
            foreach ($categories as $cat) {
                if (!$DB->record_exists('alt42i_pattern_categories', ['category_code' => $cat[1]])) {
                    $record = new stdClass();
                    $record->category_name = $cat[0];
                    $record->category_code = $cat[1];
                    $record->display_order = $cat[2];
                    $record->description = $cat[3];
                    $record->timecreated = $now;
                    $record->timemodified = $now;
                    
                    $DB->insert_record('alt42i_pattern_categories', $record);
                }
            }
            echo '<div class="step"><span class="success">✅ 카테고리 데이터 삽입 완료</span></div>';
            
            // 패턴 데이터 삽입은 별도 함수로 처리
            echo '<div class="step">60개 패턴 데이터 준비 중...</div>';
            
            // 트랜잭션 완료
            $transaction->allow_commit();
            
            echo '<div class="step"><span class="success">✅ 모든 테이블 생성 완료!</span></div>';
            echo '<p>이제 패턴 데이터를 삽입할 수 있습니다.</p>';
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            echo '<div class="error">❌ 설치 실패: ' . $e->getMessage() . '</div>';
        }
        
        echo '</div>';
        
    } else if ($action === 'check') {
        // 테이블 확인
        echo '<div class="status-box">';
        echo '<h3>📋 테이블 상태 확인</h3>';
        
        $tables = [
            'alt42i_pattern_categories' => '카테고리',
            'alt42i_math_patterns' => '패턴',
            'alt42i_pattern_solutions' => '해결책',
            'alt42i_user_pattern_progress' => '사용자 진행'
        ];
        
        foreach ($tables as $table => $name) {
            $xmldb_table = new xmldb_table($table);
            if ($DB->get_manager()->table_exists($xmldb_table)) {
                $count = $DB->count_records($table);
                echo '<div class="step"><span class="success">✅ ' . $name . ' 테이블</span> - ' . $count . '개 레코드</div>';
            } else {
                echo '<div class="step"><span class="warning">⚠️ ' . $name . ' 테이블</span> - 없음</div>';
            }
        }
        
        echo '</div>';
        
    } else if ($action === 'insertdata') {
        // 60개 패턴 데이터 삽입
        echo '<div class="status-box">';
        echo '<h3>📊 패턴 데이터 삽입</h3>';
        
        // 여기에 60개 패턴 데이터 삽입 코드 추가
        echo '<div class="step">데이터 삽입 기능은 별도 구현 필요</div>';
        echo '</div>';
        
    } else {
        // 기본 화면
        ?>
        <div class="status-box">
            <h3>📌 설치 안내</h3>
            <p>Moodle 환경에서 수학 학습 패턴 데이터베이스를 설치합니다.</p>
            <ul>
                <li>Moodle DB API 사용</li>
                <li>트랜잭션으로 안전한 설치</li>
                <li>mdl_alt42i_ 접두사 테이블</li>
                <li>관리자 권한 필요</li>
            </ul>
            
            <p><strong>현재 사용자:</strong> <?php echo fullname($USER); ?></p>
            <p><strong>데이터베이스:</strong> <?php echo $CFG->dbname; ?></p>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="?action=check" class="btn-check">🔍 테이블 확인</a>
            <a href="?action=install" class="btn-install">🚀 테이블 생성</a>
        </div>
        <?php
    }
    ?>
</div>

<?php
echo $OUTPUT->footer();
?>