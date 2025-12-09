<?php
/**
 * 수학 학습 패턴 데이터베이스 관리 클래스
 * 60personas.txt 내용을 DB에서 관리
 */

class MathPersonaDB {
    private $conn;
    private $table_prefix = 'mdl_alt42i_';
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * 모든 패턴 조회
     */
    public function getAllPatterns($user_id = null) {
        $sql = "SELECT 
                    p.*, 
                    c.category_name,
                    c.category_code,
                    s.action,
                    s.check_method,
                    s.audio_script,
                    s.teacher_dialog";
        
        if ($user_id) {
            $sql .= ", COALESCE(up.is_collected, 0) as is_collected,
                      COALESCE(up.mastery_level, 0) as mastery_level,
                      COALESCE(up.practice_count, 0) as practice_count";
        }
        
        $sql .= " FROM {$this->table_prefix}math_patterns p
                  LEFT JOIN {$this->table_prefix}pattern_categories c ON p.category_id = c.id
                  LEFT JOIN {$this->table_prefix}pattern_solutions s ON p.id = s.pattern_id";
        
        if ($user_id) {
            $sql .= " LEFT JOIN {$this->table_prefix}user_pattern_progress up 
                      ON p.id = up.pattern_id AND up.user_id = ?";
        }
        
        $sql .= " WHERE p.is_active = 1 
                  ORDER BY c.display_order, p.pattern_id";
        
        $stmt = $this->conn->prepare($sql);
        if ($user_id) {
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * 특정 패턴 조회
     */
    public function getPattern($pattern_id, $user_id = null) {
        $sql = "SELECT 
                    p.*, 
                    c.category_name,
                    c.category_code,
                    s.action,
                    s.check_method,
                    s.audio_script,
                    s.teacher_dialog,
                    s.example_problem,
                    s.practice_guide";
        
        if ($user_id) {
            $sql .= ", COALESCE(up.is_collected, 0) as is_collected,
                      COALESCE(up.mastery_level, 0) as mastery_level,
                      COALESCE(up.practice_count, 0) as practice_count,
                      up.last_practice_at,
                      up.improvement_score";
        }
        
        $sql .= " FROM {$this->table_prefix}math_patterns p
                  LEFT JOIN {$this->table_prefix}pattern_categories c ON p.category_id = c.id
                  LEFT JOIN {$this->table_prefix}pattern_solutions s ON p.id = s.pattern_id";
        
        if ($user_id) {
            $sql .= " LEFT JOIN {$this->table_prefix}user_pattern_progress up 
                      ON p.id = up.pattern_id AND up.user_id = ?";
        }
        
        $sql .= " WHERE p.pattern_id = ? AND p.is_active = 1";
        
        $stmt = $this->conn->prepare($sql);
        if ($user_id) {
            $stmt->bind_param("ii", $user_id, $pattern_id);
        } else {
            $stmt->bind_param("i", $pattern_id);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * 카테고리별 패턴 조회
     */
    public function getPatternsByCategory($category_code, $user_id = null) {
        $sql = "SELECT 
                    p.*, 
                    c.category_name,
                    s.action,
                    s.check_method";
        
        if ($user_id) {
            $sql .= ", COALESCE(up.is_collected, 0) as is_collected,
                      COALESCE(up.mastery_level, 0) as mastery_level";
        }
        
        $sql .= " FROM {$this->table_prefix}math_patterns p
                  JOIN {$this->table_prefix}pattern_categories c ON p.category_id = c.id
                  LEFT JOIN {$this->table_prefix}pattern_solutions s ON p.id = s.pattern_id";
        
        if ($user_id) {
            $sql .= " LEFT JOIN {$this->table_prefix}user_pattern_progress up 
                      ON p.id = up.pattern_id AND up.user_id = ?";
        }
        
        $sql .= " WHERE c.category_code = ? AND p.is_active = 1 
                  ORDER BY p.pattern_id";
        
        $stmt = $this->conn->prepare($sql);
        if ($user_id) {
            $stmt->bind_param("is", $user_id, $category_code);
        } else {
            $stmt->bind_param("s", $category_code);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * 사용자 진행 상황 업데이트
     */
    public function updateUserProgress($user_id, $pattern_id, $data) {
        // 먼저 패턴 ID 가져오기
        $pattern_sql = "SELECT id FROM {$this->table_prefix}math_patterns WHERE pattern_id = ?";
        $stmt = $this->conn->prepare($pattern_sql);
        $stmt->bind_param("i", $pattern_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $pattern_row = $result->fetch_assoc();
        $pattern_db_id = $pattern_row['id'];
        
        // 진행 상황 업데이트 또는 삽입
        $sql = "INSERT INTO {$this->table_prefix}user_pattern_progress 
                (user_id, pattern_id, is_collected, mastery_level, practice_count, last_practice_at, improvement_score, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                is_collected = VALUES(is_collected),
                mastery_level = VALUES(mastery_level),
                practice_count = VALUES(practice_count),
                last_practice_at = VALUES(last_practice_at),
                improvement_score = VALUES(improvement_score),
                notes = VALUES(notes),
                updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiiisds", 
            $user_id,
            $pattern_db_id,
            $data['is_collected'],
            $data['mastery_level'],
            $data['practice_count'],
            $data['last_practice_at'],
            $data['improvement_score'],
            $data['notes']
        );
        
        return $stmt->execute();
    }
    
    /**
     * 연습 기록 추가
     */
    public function addPracticeLog($user_id, $pattern_id, $log_data) {
        // 먼저 패턴 ID 가져오기
        $pattern_sql = "SELECT id FROM {$this->table_prefix}math_patterns WHERE pattern_id = ?";
        $stmt = $this->conn->prepare($pattern_sql);
        $stmt->bind_param("i", $pattern_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $pattern_row = $result->fetch_assoc();
        $pattern_db_id = $pattern_row['id'];
        
        $sql = "INSERT INTO {$this->table_prefix}pattern_practice_logs 
                (user_id, pattern_id, practice_type, duration_seconds, score, feedback, 
                 problem_data, answer_data, is_completed)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iisiiissi", 
            $user_id,
            $pattern_db_id,
            $log_data['practice_type'],
            $log_data['duration_seconds'],
            $log_data['score'],
            $log_data['feedback'],
            $log_data['problem_data'],
            $log_data['answer_data'],
            $log_data['is_completed']
        );
        
        if ($stmt->execute()) {
            // 사용자 진행 상황도 업데이트
            $this->incrementPracticeCount($user_id, $pattern_id);
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * 연습 횟수 증가
     */
    private function incrementPracticeCount($user_id, $pattern_id) {
        $pattern_sql = "SELECT id FROM {$this->table_prefix}math_patterns WHERE pattern_id = ?";
        $stmt = $this->conn->prepare($pattern_sql);
        $stmt->bind_param("i", $pattern_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $pattern_row = $result->fetch_assoc();
        $pattern_db_id = $pattern_row['id'];
        
        $sql = "UPDATE {$this->table_prefix}user_pattern_progress 
                SET practice_count = practice_count + 1,
                    last_practice_at = NOW()
                WHERE user_id = ? AND pattern_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $pattern_db_id);
        
        return $stmt->execute();
    }
    
    /**
     * 사용자 통계 조회
     */
    public function getUserStats($user_id) {
        $stats = [];
        
        // 전체 통계
        $sql = "SELECT 
                    COUNT(DISTINCT pattern_id) as total_collected,
                    AVG(mastery_level) as avg_mastery,
                    SUM(practice_count) as total_practices
                FROM {$this->table_prefix}user_pattern_progress
                WHERE user_id = ? AND is_collected = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['overall'] = $stmt->get_result()->fetch_assoc();
        
        // 카테고리별 통계
        $sql = "SELECT 
                    c.category_name,
                    c.category_code,
                    COUNT(DISTINCT p.id) as total_patterns,
                    COUNT(DISTINCT up.pattern_id) as collected_patterns,
                    AVG(up.mastery_level) as avg_mastery
                FROM {$this->table_prefix}pattern_categories c
                LEFT JOIN {$this->table_prefix}math_patterns p ON c.id = p.category_id
                LEFT JOIN {$this->table_prefix}user_pattern_progress up 
                    ON p.id = up.pattern_id AND up.user_id = ? AND up.is_collected = 1
                GROUP BY c.id
                ORDER BY c.display_order";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['by_category'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // 최근 연습 기록
        $sql = "SELECT 
                    p.pattern_name,
                    pl.practice_type,
                    pl.score,
                    pl.created_at
                FROM {$this->table_prefix}pattern_practice_logs pl
                JOIN {$this->table_prefix}math_patterns p ON pl.pattern_id = p.id
                WHERE pl.user_id = ?
                ORDER BY pl.created_at DESC
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['recent_practices'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $stats;
    }
    
    /**
     * 주간 통계 업데이트
     */
    public function updateWeeklyStats($user_id, $week_start_date) {
        // 주간 데이터 계산
        $sql = "SELECT 
                    COUNT(DISTINCT pattern_id) as patterns_collected,
                    SUM(duration_seconds) as total_practice_time,
                    AVG(score) as average_score,
                    pattern_id as most_practiced_pattern,
                    COUNT(*) as practice_count
                FROM {$this->table_prefix}pattern_practice_logs
                WHERE user_id = ? 
                    AND DATE(created_at) >= ? 
                    AND DATE(created_at) < DATE_ADD(?, INTERVAL 7 DAY)
                GROUP BY pattern_id
                ORDER BY practice_count DESC
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $week_start_date, $week_start_date);
        $stmt->execute();
        $week_data = $stmt->get_result()->fetch_assoc();
        
        if ($week_data) {
            // 주간 통계 업데이트 또는 삽입
            $sql = "INSERT INTO {$this->table_prefix}pattern_weekly_stats 
                    (user_id, week_start_date, patterns_collected, total_practice_time, 
                     average_score, most_practiced_pattern)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    patterns_collected = VALUES(patterns_collected),
                    total_practice_time = VALUES(total_practice_time),
                    average_score = VALUES(average_score),
                    most_practiced_pattern = VALUES(most_practiced_pattern),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isiidi", 
                $user_id,
                $week_start_date,
                $week_data['patterns_collected'],
                $week_data['total_practice_time'],
                $week_data['average_score'],
                $week_data['most_practiced_pattern']
            );
            
            return $stmt->execute();
        }
        
        return false;
    }
    
    /**
     * 패턴 수집 (카드 획득)
     */
    public function collectPattern($user_id, $pattern_id) {
        $pattern_sql = "SELECT id FROM {$this->table_prefix}math_patterns WHERE pattern_id = ?";
        $stmt = $this->conn->prepare($pattern_sql);
        $stmt->bind_param("i", $pattern_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $pattern_row = $result->fetch_assoc();
        $pattern_db_id = $pattern_row['id'];
        
        $sql = "INSERT INTO {$this->table_prefix}user_pattern_progress 
                (user_id, pattern_id, is_collected, mastery_level, practice_count)
                VALUES (?, ?, 1, 0, 0)
                ON DUPLICATE KEY UPDATE 
                is_collected = 1,
                updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $pattern_db_id);
        
        return $stmt->execute();
    }
}
?>