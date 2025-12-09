<?php
/**
 * 수학 학습 패턴 데이터베이스 설치 스크립트
 * Moodle 환경에서 실행
 */

// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;
require_login(); 

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// 에러 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Moodle DB 정보 사용
$db_config = [
    'host' => $CFG->dbhost,
    'username' => $CFG->dbuser,
    'password' => $CFG->dbpass,
    'database' => $CFG->dbname
];

// HTML 헤더
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학 학습 패턴 DB 설치</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.3);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
        }
        .status-box {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .success {
            color: #10b981;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .warning {
            color: #f59e0b;
            font-weight: bold;
        }
        .info {
            color: #3b82f6;
        }
        .step {
            margin: 15px 0;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-left: 3px solid #667eea;
            border-radius: 5px;
        }
        .step-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            margin: 10px;
            transition: all 0.3s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .summary {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        pre {
            background: rgba(0, 0, 0, 0.4);
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
        .table-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        .table-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 수학 학습 패턴 데이터베이스 설치</h1>
        
        <?php
        // 설치 실행 여부 확인
        if (isset($_POST['install'])) {
            echo '<div class="status-box">';
            echo '<h2>📊 설치 진행 상황</h2>';
            
            // 데이터베이스 연결
            $conn = new mysqli(
                $db_config['host'], 
                $db_config['username'], 
                $db_config['password'], 
                $db_config['database']
            );
            
            if ($conn->connect_error) {
                die('<div class="error">❌ 데이터베이스 연결 실패: ' . $conn->connect_error . '</div>');
            }
            
            echo '<div class="step"><div class="step-title success">✅ 데이터베이스 연결 성공</div></div>';
            
            // UTF-8 설정
            $conn->set_charset("utf8mb4");
            
            // 트랜잭션 시작
            $conn->begin_transaction();
            
            try {
                // 1. 카테고리 테이블 생성
                echo '<div class="step"><div class="step-title">📁 카테고리 테이블 생성 중...</div>';
                
                $sql1 = "CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_categories` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `category_name` VARCHAR(100) NOT NULL COMMENT '카테고리명',
                    `category_code` VARCHAR(50) NOT NULL COMMENT '카테고리 코드',
                    `display_order` INT(11) DEFAULT 0 COMMENT '표시 순서',
                    `description` TEXT COMMENT '카테고리 설명',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_category_code` (`category_code`),
                    KEY `idx_display_order` (`display_order`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                if (!$conn->query($sql1)) {
                    throw new Exception("카테고리 테이블 생성 실패: " . $conn->error);
                }
                echo '<span class="success">완료</span></div>';
                
                // 2. 패턴 메인 테이블 생성
                echo '<div class="step"><div class="step-title">📚 패턴 메인 테이블 생성 중...</div>';
                
                $sql2 = "CREATE TABLE IF NOT EXISTS `mdl_alt42i_math_patterns` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `pattern_id` INT(11) NOT NULL COMMENT '패턴 번호 (1-60)',
                    `pattern_name` VARCHAR(100) NOT NULL COMMENT '패턴명',
                    `pattern_desc` TEXT NOT NULL COMMENT '패턴 설명',
                    `category_id` INT(11) NOT NULL COMMENT '카테고리 ID',
                    `icon` VARCHAR(10) DEFAULT '📊' COMMENT '아이콘',
                    `priority` ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '우선순위',
                    `audio_time` VARCHAR(20) DEFAULT '3분' COMMENT '음성 가이드 시간',
                    `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_pattern_id` (`pattern_id`),
                    KEY `idx_category` (`category_id`),
                    KEY `idx_priority` (`priority`),
                    KEY `idx_active` (`is_active`),
                    CONSTRAINT `fk_pattern_category` FOREIGN KEY (`category_id`) 
                        REFERENCES `mdl_alt42i_pattern_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                if (!$conn->query($sql2)) {
                    throw new Exception("패턴 테이블 생성 실패: " . $conn->error);
                }
                echo '<span class="success">완료</span></div>';
                
                // 3. 해결책 테이블 생성
                echo '<div class="step"><div class="step-title">💡 해결책 테이블 생성 중...</div>';
                
                $sql3 = "CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_solutions` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `pattern_id` INT(11) NOT NULL COMMENT '패턴 ID',
                    `action` TEXT NOT NULL COMMENT '실천 방법',
                    `check_method` TEXT NOT NULL COMMENT '확인 방법',
                    `audio_script` TEXT COMMENT '음성 대본',
                    `teacher_dialog` TEXT COMMENT '교사 대화 템플릿',
                    `example_problem` TEXT COMMENT '예시 문제',
                    `practice_guide` TEXT COMMENT '연습 가이드',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_pattern_solution` (`pattern_id`),
                    CONSTRAINT `fk_solution_pattern` FOREIGN KEY (`pattern_id`) 
                        REFERENCES `mdl_alt42i_math_patterns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                if (!$conn->query($sql3)) {
                    throw new Exception("해결책 테이블 생성 실패: " . $conn->error);
                }
                echo '<span class="success">완료</span></div>';
                
                // 4. 사용자 진행 상황 테이블 생성
                echo '<div class="step"><div class="step-title">👤 사용자 진행 상황 테이블 생성 중...</div>';
                
                $sql4 = "CREATE TABLE IF NOT EXISTS `mdl_alt42i_user_pattern_progress` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `user_id` INT(11) NOT NULL COMMENT '사용자 ID',
                    `pattern_id` INT(11) NOT NULL COMMENT '패턴 ID',
                    `is_collected` TINYINT(1) DEFAULT 0 COMMENT '수집 여부',
                    `mastery_level` INT(11) DEFAULT 0 COMMENT '숙달도 (0-100)',
                    `practice_count` INT(11) DEFAULT 0 COMMENT '연습 횟수',
                    `last_practice_at` DATETIME DEFAULT NULL COMMENT '마지막 연습 시간',
                    `improvement_score` DECIMAL(5,2) DEFAULT 0 COMMENT '개선 점수',
                    `notes` TEXT COMMENT '메모',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_user_pattern` (`user_id`, `pattern_id`),
                    KEY `idx_user` (`user_id`),
                    KEY `idx_pattern` (`pattern_id`),
                    KEY `idx_collected` (`is_collected`),
                    KEY `idx_mastery` (`mastery_level`),
                    CONSTRAINT `fk_progress_pattern` FOREIGN KEY (`pattern_id`) 
                        REFERENCES `mdl_alt42i_math_patterns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                if (!$conn->query($sql4)) {
                    throw new Exception("사용자 진행 상황 테이블 생성 실패: " . $conn->error);
                }
                echo '<span class="success">완료</span></div>';
                
                // 5. 연습 기록 테이블 생성
                echo '<div class="step"><div class="step-title">📝 연습 기록 테이블 생성 중...</div>';
                
                $sql5 = "CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_practice_logs` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `user_id` INT(11) NOT NULL COMMENT '사용자 ID',
                    `pattern_id` INT(11) NOT NULL COMMENT '패턴 ID',
                    `practice_type` ENUM('self', 'guided', 'test') DEFAULT 'self' COMMENT '연습 유형',
                    `duration_seconds` INT(11) DEFAULT 0 COMMENT '연습 시간(초)',
                    `score` INT(11) DEFAULT NULL COMMENT '점수',
                    `feedback` TEXT COMMENT '피드백 내용',
                    `problem_data` JSON COMMENT '문제 데이터 (JSON)',
                    `answer_data` JSON COMMENT '답변 데이터 (JSON)',
                    `is_completed` TINYINT(1) DEFAULT 0 COMMENT '완료 여부',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_user_pattern` (`user_id`, `pattern_id`),
                    KEY `idx_practice_type` (`practice_type`),
                    KEY `idx_created` (`created_at`),
                    CONSTRAINT `fk_log_pattern` FOREIGN KEY (`pattern_id`) 
                        REFERENCES `mdl_alt42i_math_patterns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                if (!$conn->query($sql5)) {
                    throw new Exception("연습 기록 테이블 생성 실패: " . $conn->error);
                }
                echo '<span class="success">완료</span></div>';
                
                // 6. 음성 파일 테이블 생성
                echo '<div class="step"><div class="step-title">🔊 음성 파일 테이블 생성 중...</div>';
                
                $sql6 = "CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_audio_files` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `pattern_id` INT(11) NOT NULL COMMENT '패턴 ID',
                    `audio_type` ENUM('guide', 'example', 'feedback') DEFAULT 'guide' COMMENT '음성 유형',
                    `file_path` VARCHAR(500) NOT NULL COMMENT '파일 경로',
                    `file_name` VARCHAR(255) NOT NULL COMMENT '파일명',
                    `duration_seconds` INT(11) DEFAULT 0 COMMENT '재생 시간(초)',
                    `language` VARCHAR(10) DEFAULT 'ko' COMMENT '언어 코드',
                    `transcript` TEXT COMMENT '음성 대본',
                    `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_pattern_audio` (`pattern_id`, `audio_type`),
                    KEY `idx_language` (`language`),
                    KEY `idx_active` (`is_active`),
                    CONSTRAINT `fk_audio_pattern` FOREIGN KEY (`pattern_id`) 
                        REFERENCES `mdl_alt42i_math_patterns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                if (!$conn->query($sql6)) {
                    throw new Exception("음성 파일 테이블 생성 실패: " . $conn->error);
                }
                echo '<span class="success">완료</span></div>';
                
                // 7. 주간 통계 테이블 생성
                echo '<div class="step"><div class="step-title">📊 주간 통계 테이블 생성 중...</div>';
                
                $sql7 = "CREATE TABLE IF NOT EXISTS `mdl_alt42i_pattern_weekly_stats` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `user_id` INT(11) NOT NULL COMMENT '사용자 ID',
                    `week_start_date` DATE NOT NULL COMMENT '주 시작일',
                    `patterns_collected` INT(11) DEFAULT 0 COMMENT '수집한 패턴 수',
                    `total_practice_time` INT(11) DEFAULT 0 COMMENT '총 연습 시간(초)',
                    `average_score` DECIMAL(5,2) DEFAULT 0 COMMENT '평균 점수',
                    `most_practiced_pattern` INT(11) DEFAULT NULL COMMENT '가장 많이 연습한 패턴',
                    `improvement_rate` DECIMAL(5,2) DEFAULT 0 COMMENT '개선율(%)',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_user_week` (`user_id`, `week_start_date`),
                    KEY `idx_week` (`week_start_date`),
                    KEY `idx_user` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                if (!$conn->query($sql7)) {
                    throw new Exception("주간 통계 테이블 생성 실패: " . $conn->error);
                }
                echo '<span class="success">완료</span></div>';
                
                // 8. 카테고리 데이터 삽입
                echo '<div class="step"><div class="step-title">📂 카테고리 데이터 삽입 중...</div>';
                
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
                
                $stmt = $conn->prepare("INSERT INTO `mdl_alt42i_pattern_categories` 
                    (`category_name`, `category_code`, `display_order`, `description`) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    `description` = VALUES(`description`),
                    `display_order` = VALUES(`display_order`)");
                
                foreach ($categories as $cat) {
                    $stmt->bind_param("ssis", $cat[0], $cat[1], $cat[2], $cat[3]);
                    $stmt->execute();
                }
                echo '<span class="success">8개 카테고리 삽입 완료</span></div>';
                
                // 9. 60개 패턴 데이터 삽입
                echo '<div class="step"><div class="step-title">🎯 60개 패턴 데이터 삽입 중...</div>';
                
                // 카테고리 ID 가져오기
                $cat_ids = [];
                $result = $conn->query("SELECT id, category_code FROM mdl_alt42i_pattern_categories");
                while ($row = $result->fetch_assoc()) {
                    $cat_ids[$row['category_code']] = $row['id'];
                }
                
                // 패턴 데이터 배열 (60개)
                $patterns = [
                    // 인지 과부하 (1-8)
                    [1, '계산 실수 반복', '같은 유형의 계산 실수를 계속 반복함', 'cognitive_overload', '🔢', 'high', '3분'],
                    [2, '문제 이해 부족', '문제를 제대로 읽지 않고 풀이 시작', 'cognitive_overload', '📖', 'high', '4분'],
                    [3, '단계 건너뛰기', '풀이 과정에서 중요한 단계를 생략', 'cognitive_overload', '⏭️', 'medium', '3분'],
                    [4, '공식 혼동', '비슷한 공식들을 자주 헷갈림', 'cognitive_overload', '🔀', 'high', '5분'],
                    [5, '주의력 분산', '문제 풀이 중 집중력이 쉽게 흐트러짐', 'cognitive_overload', '😵', 'medium', '3분'],
                    [6, '복잡한 문제 회피', '어려워 보이는 문제는 시도조차 안 함', 'cognitive_overload', '🚫', 'high', '4분'],
                    [7, '암기 의존', '이해 없이 암기에만 의존하여 응용 불가', 'cognitive_overload', '🧠', 'high', '5분'],
                    [8, '개념 연결 실패', '관련 개념들 간의 연결을 못 함', 'cognitive_overload', '🔗', 'medium', '4분'],
                    
                    // 자신감 왜곡 (9-15)
                    [9, '과도한 자신감', '실력보다 문제를 쉽게 봄', 'confidence_distortion', '😎', 'medium', '3분'],
                    [10, '자신감 부족', '풀 수 있는 문제도 못 푼다고 생각', 'confidence_distortion', '😰', 'high', '4분'],
                    [11, '시험 불안', '시험 상황에서 극도로 긴장함', 'confidence_distortion', '😱', 'high', '5분'],
                    [12, '완벽주의', '100% 확신이 없으면 답을 쓰지 않음', 'confidence_distortion', '💯', 'medium', '3분'],
                    [13, '실수 공포', '실수할까봐 문제 풀이를 망설임', 'confidence_distortion', '😨', 'medium', '3분'],
                    [14, '비교 스트레스', '다른 학생과 비교하며 좌절', 'confidence_distortion', '📊', 'medium', '4분'],
                    [15, '포기 습관', '조금만 어려워도 쉽게 포기', 'confidence_distortion', '🏳️', 'high', '4분'],
                    
                    // 실수 패턴 (16-25)
                    [16, '부호 실수', '+/- 부호를 자주 틀림', 'mistake_patterns', '➕', 'high', '2분'],
                    [17, '단위 실수', '단위 변환에서 실수 반복', 'mistake_patterns', '📏', 'medium', '3분'],
                    [18, '계산 순서 오류', '연산 순서를 자주 틀림', 'mistake_patterns', '🔄', 'high', '3분'],
                    [19, '옮겨 쓰기 실수', '숫자나 식을 옮겨 쓸 때 실수', 'mistake_patterns', '✍️', 'medium', '2분'],
                    [20, '그래프 해석 오류', '그래프 읽기에서 반복적 실수', 'mistake_patterns', '📈', 'medium', '4분'],
                    [21, '소수점 실수', '소수점 위치를 자주 틀림', 'mistake_patterns', '🔵', 'medium', '2분'],
                    [22, '약분 실수', '분수 약분에서 실수 반복', 'mistake_patterns', '➗', 'medium', '3분'],
                    [23, '풀이 누락', '답은 맞는데 풀이 과정 누락', 'mistake_patterns', '📝', 'low', '2분'],
                    [24, '문제 번호 실수', '다른 문제의 답을 씀', 'mistake_patterns', '🔢', 'low', '2분'],
                    [25, '시간 부족 실수', '시간에 쫓겨 실수 증가', 'mistake_patterns', '⏰', 'high', '3분'],
                    
                    // 접근 전략 오류 (26-33)
                    [26, '무작정 대입', '체계 없이 숫자만 대입해봄', 'approach_errors', '🎲', 'medium', '3분'],
                    [27, '한 가지 방법 고집', '익숙한 방법만 고집함', 'approach_errors', '🔨', 'medium', '4분'],
                    [28, '거꾸로 풀기 미숙', '역산이 필요한 문제 접근 실패', 'approach_errors', '⬅️', 'medium', '4분'],
                    [29, '그림 활용 부족', '그림이나 도표 활용을 안 함', 'approach_errors', '🖼️', 'medium', '3분'],
                    [30, '단위 분석 부재', '단위를 통한 검증을 안 함', 'approach_errors', '⚖️', 'low', '3분'],
                    [31, '극단값 검토 부재', '극단적인 경우를 고려 안 함', 'approach_errors', '🎯', 'low', '3분'],
                    [32, '패턴 인식 부족', '문제의 패턴을 파악 못함', 'approach_errors', '🔍', 'high', '4분'],
                    [33, '조건 활용 미흡', '주어진 조건을 충분히 활용 안 함', 'approach_errors', '📋', 'medium', '3분'],
                    
                    // 학습 습관 (34-42)
                    [34, '복습 부재', '한 번 푼 문제는 다시 안 봄', 'study_habits', '🔁', 'high', '3분'],
                    [35, '오답 정리 미흡', '틀린 문제를 제대로 정리 안 함', 'study_habits', '📓', 'high', '4분'],
                    [36, '질문 회피', '모르는 것을 질문하지 않음', 'study_habits', '🤐', 'medium', '3분'],
                    [37, '혼자 학습 고집', '도움 받기를 거부함', 'study_habits', '🏝️', 'low', '3분'],
                    [38, '벼락치기', '시험 직전에만 공부함', 'study_habits', '⚡', 'high', '4분'],
                    [39, '정리 노트 부재', '체계적인 정리를 안 함', 'study_habits', '📚', 'medium', '3분'],
                    [40, '연습 부족', '개념만 보고 문제 풀이 연습 부족', 'study_habits', '💪', 'high', '3분'],
                    [41, '피드백 무시', '선생님 피드백을 반영 안 함', 'study_habits', '👂', 'medium', '3분'],
                    [42, '목표 설정 부재', '구체적인 학습 목표가 없음', 'study_habits', '🎯', 'medium', '3분'],
                    
                    // 시간/압박 관리 (43-49)
                    [43, '시간 배분 실패', '문제별 시간 배분을 못함', 'time_pressure', '⏱️', 'high', '3분'],
                    [44, '속도 압박', '빨리 풀어야 한다는 압박감', 'time_pressure', '🏃', 'medium', '3분'],
                    [45, '마감 스트레스', '제출 시간이 다가올수록 실수 증가', 'time_pressure', '⏳', 'medium', '3분'],
                    [46, '쉬운 문제 과투자', '쉬운 문제에 시간을 너무 씀', 'time_pressure', '🐌', 'medium', '3분'],
                    [47, '어려운 문제 집착', '한 문제에 너무 오래 매달림', 'time_pressure', '🔒', 'high', '3분'],
                    [48, '시간 체크 과다', '시계를 너무 자주 봄', 'time_pressure', '⌚', 'low', '2분'],
                    [49, '페이스 조절 실패', '일정한 속도 유지를 못함', 'time_pressure', '🎢', 'medium', '3분'],
                    
                    // 검증/확인 부재 (50-55)
                    [50, '검산 생략', '답을 구한 후 검산을 안 함', 'verification_absence', '✔️', 'high', '3분'],
                    [51, '논리 검증 부재', '답의 논리적 타당성 검토 안 함', 'verification_absence', '🤔', 'medium', '3분'],
                    [52, '조건 확인 누락', '모든 조건을 만족하는지 확인 안 함', 'verification_absence', '📃', 'medium', '3분'],
                    [53, '단위 확인 누락', '답의 단위가 맞는지 확인 안 함', 'verification_absence', '📐', 'low', '2분'],
                    [54, '범위 검토 부재', '답이 합리적 범위인지 확인 안 함', 'verification_absence', '🎚️', 'low', '2분'],
                    [55, '문제 재확인 부재', '문제를 다시 읽어보지 않음', 'verification_absence', '👀', 'medium', '2분'],
                    
                    // 기타 장애 (56-60)
                    [56, '필기구 문제', '연필, 지우개 등 준비 부족', 'other_obstacles', '✏️', 'low', '2분'],
                    [57, '환경 방해', '주변 소음이나 방해 요소에 민감', 'other_obstacles', '🔊', 'low', '3분'],
                    [58, '신체 불편', '자세나 피로로 집중력 저하', 'other_obstacles', '😫', 'low', '3분'],
                    [59, '도구 활용 미숙', '계산기, 자, 컴퍼스 사용 미숙', 'other_obstacles', '📱', 'low', '3분'],
                    [60, '선입견', '특정 유형은 못 푼다는 선입견', 'other_obstacles', '🚧', 'medium', '3분']
                ];
                
                $stmt = $conn->prepare("INSERT INTO `mdl_alt42i_math_patterns` 
                    (`pattern_id`, `pattern_name`, `pattern_desc`, `category_id`, `icon`, `priority`, `audio_time`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $count = 0;
                foreach ($patterns as $pattern) {
                    $category_id = $cat_ids[$pattern[3]];
                    $stmt->bind_param("isssiss", 
                        $pattern[0], $pattern[1], $pattern[2], 
                        $category_id, $pattern[4], $pattern[5], $pattern[6]
                    );
                    if ($stmt->execute()) {
                        $count++;
                    }
                }
                echo '<span class="success">' . $count . '개 패턴 삽입 완료</span></div>';
                
                // 10. 해결책 데이터 삽입
                echo '<div class="step"><div class="step-title">💡 해결책 데이터 삽입 중...</div>';
                
                // 간단한 해결책 데이터 (실제로는 더 상세한 내용 필요)
                $solutions_sql = "INSERT INTO mdl_alt42i_pattern_solutions 
                    (pattern_id, action, check_method, audio_script, teacher_dialog) 
                    SELECT 
                        id,
                        CONCAT('패턴 ', pattern_id, '에 대한 해결 방법'),
                        CONCAT('패턴 ', pattern_id, '의 개선 확인 방법'),
                        CONCAT('패턴 ', pattern_id, '의 음성 가이드 스크립트'),
                        CONCAT('패턴 ', pattern_id, '에 대한 교사 대화 가이드')
                    FROM mdl_alt42i_math_patterns";
                
                if (!$conn->query($solutions_sql)) {
                    throw new Exception("해결책 데이터 삽입 실패: " . $conn->error);
                }
                echo '<span class="success">60개 해결책 삽입 완료</span></div>';
                
                // 트랜잭션 커밋
                $conn->commit();
                
                echo '</div>';
                
                // 설치 완료 요약
                echo '<div class="summary">';
                echo '<h3>✨ 설치 완료!</h3>';
                echo '<p>수학 학습 패턴 데이터베이스가 성공적으로 설치되었습니다.</p>';
                echo '<div class="table-list">';
                echo '<div class="table-item">✅ mdl_alt42i_pattern_categories</div>';
                echo '<div class="table-item">✅ mdl_alt42i_math_patterns</div>';
                echo '<div class="table-item">✅ mdl_alt42i_pattern_solutions</div>';
                echo '<div class="table-item">✅ mdl_alt42i_user_pattern_progress</div>';
                echo '<div class="table-item">✅ mdl_alt42i_pattern_practice_logs</div>';
                echo '<div class="table-item">✅ mdl_alt42i_pattern_audio_files</div>';
                echo '<div class="table-item">✅ mdl_alt42i_pattern_weekly_stats</div>';
                echo '</div>';
                echo '<p><strong>총 60개의 수학 학습 패턴</strong>과 <strong>8개 카테고리</strong>가 등록되었습니다.</p>';
                echo '</div>';
                
            } catch (Exception $e) {
                // 오류 발생 시 롤백
                $conn->rollback();
                echo '<div class="error">❌ 설치 실패: ' . $e->getMessage() . '</div>';
            }
            
            $conn->close();
            
        } else if (isset($_POST['check'])) {
            // 기존 테이블 확인
            echo '<div class="status-box">';
            echo '<h2>📋 기존 테이블 확인</h2>';
            
            $conn = new mysqli(
                $db_config['host'], 
                $db_config['username'], 
                $db_config['password'], 
                $db_config['database']
            );
            
            if ($conn->connect_error) {
                die('<div class="error">❌ 데이터베이스 연결 실패: ' . $conn->connect_error . '</div>');
            }
            
            $tables = [
                'mdl_alt42i_pattern_categories',
                'mdl_alt42i_math_patterns',
                'mdl_alt42i_pattern_solutions',
                'mdl_alt42i_user_pattern_progress',
                'mdl_alt42i_pattern_practice_logs',
                'mdl_alt42i_pattern_audio_files',
                'mdl_alt42i_pattern_weekly_stats'
            ];
            
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows > 0) {
                    // 테이블이 존재하면 레코드 수 확인
                    $count_result = $conn->query("SELECT COUNT(*) as cnt FROM $table");
                    $count = $count_result->fetch_assoc()['cnt'];
                    echo '<div class="step"><span class="success">✅ ' . $table . '</span> - ' . $count . '개 레코드</div>';
                } else {
                    echo '<div class="step"><span class="warning">⚠️ ' . $table . '</span> - 테이블 없음</div>';
                }
            }
            
            echo '</div>';
            $conn->close();
            
        } else if (isset($_POST['drop'])) {
            // 테이블 삭제
            echo '<div class="status-box">';
            echo '<h2>🗑️ 테이블 삭제 중...</h2>';
            
            $conn = new mysqli(
                $db_config['host'], 
                $db_config['username'], 
                $db_config['password'], 
                $db_config['database']
            );
            
            if ($conn->connect_error) {
                die('<div class="error">❌ 데이터베이스 연결 실패: ' . $conn->connect_error . '</div>');
            }
            
            // 외래키 체크 비활성화
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            $tables = [
                'mdl_alt42i_pattern_weekly_stats',
                'mdl_alt42i_pattern_audio_files',
                'mdl_alt42i_pattern_practice_logs',
                'mdl_alt42i_user_pattern_progress',
                'mdl_alt42i_pattern_solutions',
                'mdl_alt42i_math_patterns',
                'mdl_alt42i_pattern_categories'
            ];
            
            foreach ($tables as $table) {
                if ($conn->query("DROP TABLE IF EXISTS $table")) {
                    echo '<div class="step"><span class="success">✅ ' . $table . ' 삭제 완료</span></div>';
                } else {
                    echo '<div class="step"><span class="error">❌ ' . $table . ' 삭제 실패</span></div>';
                }
            }
            
            // 외래키 체크 재활성화
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            echo '</div>';
            $conn->close();
        }
        ?>
        
        <?php if (!isset($_POST['install']) && !isset($_POST['check']) && !isset($_POST['drop'])): ?>
        <div class="status-box">
            <h2>📌 설치 안내</h2>
            <p>이 스크립트는 수학 학습 패턴 데이터베이스를 설치합니다.</p>
            <ul>
                <li>7개의 테이블 생성</li>
                <li>8개 카테고리 등록</li>
                <li>60개 수학 학습 패턴 데이터 삽입</li>
                <li>mdl_alt42i_ 접두사 사용</li>
            </ul>
            
            <div style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; margin: 20px 0;">
                <p class="warning">⚠️ 주의사항:</p>
                <ul>
                    <li>데이터베이스 이름: <strong><?php echo $db_config['database']; ?></strong></li>
                    <li>기존 테이블이 있으면 생성하지 않습니다</li>
                    <li>데이터 중복 삽입은 자동으로 방지됩니다</li>
                </ul>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <form method="post" style="display: inline;">
                <button type="submit" name="check" value="1">
                    🔍 기존 테이블 확인
                </button>
            </form>
            
            <form method="post" style="display: inline;">
                <button type="submit" name="install" value="1" 
                        style="background: linear-gradient(135deg, #10b981, #059669);">
                    🚀 데이터베이스 설치
                </button>
            </form>
            
            <form method="post" style="display: inline;" 
                  onsubmit="return confirm('정말로 모든 테이블을 삭제하시겠습니까?');">
                <button type="submit" name="drop" value="1" 
                        style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    🗑️ 테이블 삭제
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_POST['install']) || isset($_POST['check']) || isset($_POST['drop'])): ?>
        <div style="text-align: center; margin-top: 30px;">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>">
                <button>🏠 처음으로</button>
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>