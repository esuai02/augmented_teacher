<?php
/**
 * Table Description Model
 * 테이블 설명 관리 모델
 */

class TableDescriptionModel {
    private $connection;
    
    public function __construct() {
        $this->connection = $this->connectToMoodleDB();
        $this->createTableIfNotExists();
    }
    
    /**
     * Moodle DB 연결
     */
    private function connectToMoodleDB() {
        try {
            global $CFG;
            
            $moodleDB = new PDO(
                'mysql:host=' . $CFG->dbhost . ';dbname=' . $CFG->dbname . ';charset=utf8mb4', 
                $CFG->dbuser, 
                $CFG->dbpass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            
            return $moodleDB;
        } catch (PDOException $e) {
            error_log('DB 연결 실패: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 테이블 생성 (존재하지 않을 경우)
     */
    private function createTableIfNotExists() {
        if (!$this->connection) return;
        
        try {
            $sql = "CREATE TABLE IF NOT EXISTS mdl_alt42_table_descriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL COMMENT '테이블 타입',
                table_name VARCHAR(255) NOT NULL COMMENT '테이블명',
                description TEXT DEFAULT NULL COMMENT '테이블 설명',
                timecreated INT(10) NOT NULL COMMENT '생성 시간 (unixtime)',
                timemodified INT(10) NOT NULL COMMENT '수정 시간 (unixtime)',
                
                UNIQUE KEY unique_table_name (table_name),
                INDEX idx_type (type),
                INDEX idx_timemodified (timemodified)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='테이블 설명 정보'";
            
            $this->connection->exec($sql);
        } catch (PDOException $e) {
            error_log('테이블 생성 실패: ' . $e->getMessage());
        }
    }
    
    /**
     * 테이블 설명 저장 또는 업데이트 (UPSERT)
     */
    public function saveTableDescription($tableName, $type, $description) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $currentTime = time();
            
            // INSERT ... ON DUPLICATE KEY UPDATE 사용
            $sql = "INSERT INTO mdl_alt42_table_descriptions (type, table_name, description, timecreated, timemodified) 
                    VALUES (?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                        type = VALUES(type),
                        description = VALUES(description),
                        timemodified = VALUES(timemodified)";
            
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute([
                $type,
                $tableName,
                $description,
                $currentTime,
                $currentTime
            ]);
            
            // 디버깅 정보 로그
            error_log("Table description saved: {$tableName} (Type: {$type}) = {$description}");
            
            return [
                'success' => true,
                'message' => '테이블 설명이 저장되었습니다.',
                'affected_rows' => $stmt->rowCount(),
                'table_name' => $tableName,
                'type' => $type,
                'description' => $description,
                'timestamp' => $currentTime
            ];
            
        } catch (PDOException $e) {
            error_log('Table description save error: ' . $e->getMessage());
            return ['error' => '저장 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 특정 테이블의 설명 조회
     */
    public function getTableDescription($tableName) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $sql = "SELECT * FROM mdl_alt42_table_descriptions WHERE table_name = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$tableName]);
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'success' => true,
                    'type' => $result['type'],
                    'description' => $result['description'],
                    'timecreated' => $result['timecreated'],
                    'timemodified' => $result['timemodified']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '해당 테이블 설명을 찾을 수 없습니다.'
                ];
            }
            
        } catch (PDOException $e) {
            return ['error' => '조회 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 모든 테이블 설명 조회
     */
    public function getAllTableDescriptions() {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $sql = "SELECT * FROM mdl_alt42_table_descriptions ORDER BY table_name";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            // 테이블명을 키로 하는 배열로 변환
            $descriptions = [];
            foreach ($results as $row) {
                $descriptions[$row['table_name']] = [
                    'type' => $row['type'],
                    'description' => $row['description'],
                    'timecreated' => $row['timecreated'],
                    'timemodified' => $row['timemodified']
                ];
            }
            
            return [
                'success' => true,
                'descriptions' => $descriptions,
                'count' => count($results)
            ];
            
        } catch (PDOException $e) {
            return ['error' => '조회 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 테이블 설명 삭제
     */
    public function deleteTableDescription($tableName) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $sql = "DELETE FROM mdl_alt42_table_descriptions WHERE table_name = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$tableName]);
            
            return [
                'success' => true,
                'message' => '테이블 설명이 삭제되었습니다.',
                'affected_rows' => $stmt->rowCount()
            ];
            
        } catch (PDOException $e) {
            return ['error' => '삭제 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 타입별 테이블 통계
     */
    public function getTableStats() {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $sql = "SELECT 
                        type,
                        COUNT(*) as table_count,
                        COUNT(CASE WHEN description IS NOT NULL AND description != '' THEN 1 END) as described_count
                    FROM mdl_alt42_table_descriptions 
                    GROUP BY type
                    ORDER BY type";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            return [
                'success' => true,
                'stats' => $results
            ];
            
        } catch (PDOException $e) {
            return ['error' => '통계 조회 실패: ' . $e->getMessage()];
        }
    }
}