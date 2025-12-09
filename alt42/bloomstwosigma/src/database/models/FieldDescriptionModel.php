<?php
/**
 * Field Description Model
 * 테이블 필드 설명 관리 모델
 */

class FieldDescriptionModel {
    private $connection;
    
    public function __construct() {
        $this->connection = $this->connectToMoodleDB();
        // mdl_alt42_field_descriptions 테이블 사용 중단 - MySQL COLUMN COMMENT 사용
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
     * 테이블 생성 (존재하지 않을 경우) - 더이상 사용하지 않음
     * @deprecated MySQL COLUMN COMMENT 사용으로 대체됨
     */
    /*
    private function createTableIfNotExists() {
        if (!$this->connection) return;
        
        try {
            $sql = "CREATE TABLE IF NOT EXISTS mdl_alt42_field_descriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                table_name VARCHAR(255) NOT NULL COMMENT '테이블명',
                field_name VARCHAR(255) NOT NULL COMMENT '필드명',
                description TEXT DEFAULT NULL COMMENT '필드 설명',
                type VARCHAR(100) DEFAULT NULL COMMENT '데이터 타입 (선택사항)',
                timecreated INT(10) NOT NULL COMMENT '생성 시간 (unixtime)',
                timemodified INT(10) NOT NULL COMMENT '수정 시간 (unixtime)',
                
                UNIQUE KEY unique_table_field (table_name, field_name),
                INDEX idx_table_name (table_name),
                INDEX idx_field_name (field_name),
                INDEX idx_timemodified (timemodified)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='테이블 필드 설명 정보'";
            
            $this->connection->exec($sql);
        } catch (PDOException $e) {
            error_log('테이블 생성 실패: ' . $e->getMessage());
        }
    }
    */
    
    /**
     * 필드 설명 저장 또는 업데이트 (ALTER TABLE MODIFY COLUMN)
     */
    public function saveFieldDescription($tableName, $fieldName, $description, $type = null) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            // 먼저 해당 테이블의 필드 정보를 가져옴
            $sql = "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND COLUMN_NAME = ?";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$tableName, $fieldName]);
            $fieldInfo = $stmt->fetch();
            
            if (!$fieldInfo) {
                return ['error' => '필드를 찾을 수 없습니다.'];
            }
            
            // ALTER TABLE 쿼리 생성
            $alterSql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$fieldName}` {$fieldInfo['COLUMN_TYPE']}";
            
            // NULL 허용 여부
            if ($fieldInfo['IS_NULLABLE'] === 'NO') {
                $alterSql .= " NOT NULL";
            } else {
                $alterSql .= " NULL";
            }
            
            // 기본값
            if ($fieldInfo['COLUMN_DEFAULT'] !== null) {
                $alterSql .= " DEFAULT '{$fieldInfo['COLUMN_DEFAULT']}'";
            }
            
            // EXTRA (auto_increment 등)
            if ($fieldInfo['EXTRA']) {
                $alterSql .= " {$fieldInfo['EXTRA']}";
            }
            
            // COMMENT 추가
            $alterSql .= " COMMENT ?";
            
            // ALTER TABLE 실행
            $stmt = $this->connection->prepare($alterSql);
            $result = $stmt->execute([$description]);
            
            // 디버깅 정보 로그
            error_log("Field comment saved: {$tableName}.{$fieldName} = {$description}");
            
            return [
                'success' => true,
                'message' => '필드 설명이 저장되었습니다.',
                'affected_rows' => $stmt->rowCount(),
                'table_name' => $tableName,
                'field_name' => $fieldName,
                'description' => $description
            ];
            
        } catch (PDOException $e) {
            error_log('Field comment save error: ' . $e->getMessage());
            return ['error' => '저장 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 특정 테이블의 필드 설명 조회 (INFORMATION_SCHEMA에서)
     */
    public function getFieldDescriptions($tableName) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $sql = "SELECT COLUMN_NAME, COLUMN_COMMENT, COLUMN_TYPE 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND COLUMN_COMMENT != '' 
                    ORDER BY ORDINAL_POSITION";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$tableName]);
            $results = $stmt->fetchAll();
            
            // 디버깅 정보 로그
            error_log("Loading field comments for table: {$tableName}, found: " . count($results) . " records");
            
            // 필드명을 키로 하는 배열로 변환
            $descriptions = [];
            foreach ($results as $row) {
                $descriptions[$row['COLUMN_NAME']] = [
                    'description' => $row['COLUMN_COMMENT'],
                    'type' => $row['COLUMN_TYPE']
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
     * 특정 필드 설명 조회 (INFORMATION_SCHEMA에서)
     */
    public function getFieldDescription($tableName, $fieldName) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $sql = "SELECT COLUMN_COMMENT, COLUMN_TYPE 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND COLUMN_NAME = ?";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$tableName, $fieldName]);
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'success' => true,
                    'description' => $result['COLUMN_COMMENT'],
                    'type' => $result['COLUMN_TYPE']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '해당 필드를 찾을 수 없습니다.'
                ];
            }
            
        } catch (PDOException $e) {
            return ['error' => '조회 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 필드 설명 삭제 (COMMENT를 빈 문자열로 설정)
     */
    public function deleteFieldDescription($tableName, $fieldName) {
        // COMMENT를 삭제하려면 빈 문자열로 설정
        return $this->saveFieldDescription($tableName, $fieldName, '');
    }
    
    /**
     * 테이블별 필드 설명 통계 (INFORMATION_SCHEMA에서)
     */
    public function getDescriptionStats($tableName = null) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            if ($tableName) {
                // 특정 테이블의 통계
                $sql = "SELECT 
                            TABLE_NAME as table_name,
                            COUNT(*) as total_fields,
                            COUNT(CASE WHEN COLUMN_COMMENT IS NOT NULL AND COLUMN_COMMENT != '' THEN 1 END) as described_fields
                        FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = ?
                        GROUP BY TABLE_NAME";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$tableName]);
            } else {
                // 전체 테이블의 통계
                $sql = "SELECT 
                            TABLE_NAME as table_name,
                            COUNT(*) as total_fields,
                            COUNT(CASE WHEN COLUMN_COMMENT IS NOT NULL AND COLUMN_COMMENT != '' THEN 1 END) as described_fields
                        FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = DATABASE()
                        GROUP BY TABLE_NAME
                        ORDER BY TABLE_NAME";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute();
            }
            
            $results = $stmt->fetchAll();
            
            return [
                'success' => true,
                'stats' => $results
            ];
            
        } catch (PDOException $e) {
            return ['error' => '통계 조회 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 일괄 필드 설명 저장
     */
    public function saveMultipleFieldDescriptions($tableName, $fieldDescriptions) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $this->connection->beginTransaction();
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            foreach ($fieldDescriptions as $fieldName => $description) {
                if (!empty($description)) {
                    $result = $this->saveFieldDescription($tableName, $fieldName, $description);
                    if (isset($result['success']) && $result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "필드 '{$fieldName}': " . ($result['error'] ?? '저장 실패');
                    }
                }
            }
            
            $this->connection->commit();
            
            return [
                'success' => true,
                'message' => "필드 설명 저장 완료 (성공: {$successCount}, 실패: {$errorCount})",
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->connection->rollback();
            return ['error' => '일괄 저장 실패: ' . $e->getMessage()];
        }
    }
}