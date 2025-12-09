<?php
/**
 * Experiment Model
 * 실험 관련 데이터 관리 모델
 */

class ExperimentModel {
    private $connection;
    
    public function __construct() {
        $this->connection = $this->connectToMoodleDB();
        $this->createTablesIfNotExist();
    }
    
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
    
    private function createTablesIfNotExist() {
        if (!$this->connection) return;
        
        // 테이블이 이미 생성되었다고 가정하고 로그만 남김
        try {
            $stmt = $this->connection->prepare("SHOW TABLES LIKE 'mdl_alt42_experiments'");
            $stmt->execute();
            $exists = $stmt->fetch();
            
            if ($exists) {
                error_log('실험 테이블들이 이미 존재합니다.');
            } else {
                error_log('실험 테이블을 찾을 수 없습니다. 수동으로 생성해주세요.');
            }
        } catch (PDOException $e) {
            error_log('테이블 확인 실패: ' . $e->getMessage());
        }
    }
    
    /**
     * 실험 생성 또는 업데이트
     */
    public function saveExperiment($experimentData) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $currentTime = time();
            
            if (isset($experimentData['id']) && $experimentData['id'] > 0) {
                // 업데이트
                $sql = "UPDATE mdl_alt42_experiments SET 
                        experiment_name = ?, 
                        description = ?, 
                        start_date = ?, 
                        duration_weeks = ?, 
                        status = ?,
                        timemodified = ?
                        WHERE id = ?";
                
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([
                    $experimentData['experiment_name'],
                    $experimentData['description'],
                    strtotime($experimentData['start_date']),
                    $experimentData['duration_weeks'],
                    $experimentData['status'] ?? 'planned',
                    $currentTime,
                    $experimentData['id']
                ]);
                
                $experimentId = $experimentData['id'];
            } else {
                // 새 실험 생성
                $sql = "INSERT INTO mdl_alt42_experiments 
                        (experiment_name, description, start_date, duration_weeks, status, created_by, timecreated, timemodified) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([
                    $experimentData['experiment_name'],
                    $experimentData['description'],
                    strtotime($experimentData['start_date']),
                    $experimentData['duration_weeks'],
                    $experimentData['status'] ?? 'planned',
                    $experimentData['created_by'],
                    $currentTime,
                    $currentTime
                ]);
                
                $experimentId = $this->connection->lastInsertId();
            }
            
            return [
                'success' => true,
                'experiment_id' => $experimentId,
                'message' => '실험이 저장되었습니다.'
            ];
            
        } catch (PDOException $e) {
            return ['error' => '실험 저장 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 개입 방법 저장
     */
    public function saveInterventionMethod($experimentId, $methodData) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $currentTime = time();
            
            $sql = "INSERT INTO mdl_alt42_intervention_methods 
                    (experiment_id, method_type, method_name, description, is_active, timecreated, timemodified) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        method_name = VALUES(method_name),
                        description = VALUES(description),
                        is_active = VALUES(is_active),
                        timemodified = VALUES(timemodified)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $experimentId,
                $methodData['method_type'],
                $methodData['method_name'],
                $methodData['description'],
                $methodData['is_active'] ?? 1,
                $currentTime,
                $currentTime
            ]);
            
            return [
                'success' => true,
                'message' => '개입 방법이 저장되었습니다.'
            ];
            
        } catch (PDOException $e) {
            return ['error' => '개입 방법 저장 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 추적 설정 저장
     */
    public function saveTrackingConfig($experimentId, $configData) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $currentTime = time();
            
            $sql = "INSERT INTO mdl_alt42_tracking_configs 
                    (experiment_id, config_name, description, tracking_type, data_source, collection_frequency, is_active, timecreated, timemodified) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $experimentId,
                $configData['config_name'],
                $configData['description'],
                $configData['tracking_type'],
                $configData['data_source'],
                $configData['collection_frequency'],
                $configData['is_active'] ?? 1,
                $currentTime,
                $currentTime
            ]);
            
            return [
                'success' => true,
                'tracking_config_id' => $this->connection->lastInsertId(),
                'message' => '추적 설정이 저장되었습니다.'
            ];
            
        } catch (PDOException $e) {
            return ['error' => '추적 설정 저장 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 그룹 배정 저장
     */
    public function saveGroupAssignment($experimentId, $userId, $groupType, $interventionMethodId = null, $teacherId = null, $assignedBy = null) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $currentTime = time();
            
            $sql = "INSERT INTO mdl_alt42_group_assignments 
                    (experiment_id, user_id, group_type, intervention_method_id, teacher_id, assigned_by, timecreated, timemodified) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        group_type = VALUES(group_type),
                        intervention_method_id = VALUES(intervention_method_id),
                        teacher_id = VALUES(teacher_id),
                        assigned_by = VALUES(assigned_by),
                        timemodified = VALUES(timemodified)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $experimentId,
                $userId,
                $groupType,
                $interventionMethodId,
                $teacherId,
                $assignedBy,
                $currentTime,
                $currentTime
            ]);
            
            return [
                'success' => true,
                'message' => '그룹 배정이 저장되었습니다.'
            ];
            
        } catch (PDOException $e) {
            return ['error' => '그룹 배정 저장 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * DB 연결 정보 저장
     */
    public function saveDatabaseConnection($experimentId, $tableName, $databaseName = 'mathking', $purpose = null, $conditions = null) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $currentTime = time();
            
            $sql = "INSERT INTO mdl_alt42_database_connections 
                    (experiment_id, table_name, database_name, connection_purpose, query_conditions, timecreated, timemodified) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        connection_purpose = VALUES(connection_purpose),
                        query_conditions = VALUES(query_conditions),
                        timemodified = VALUES(timemodified)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $experimentId,
                $tableName,
                $databaseName,
                $purpose,
                is_array($conditions) ? json_encode($conditions) : $conditions,
                $currentTime,
                $currentTime
            ]);
            
            return [
                'success' => true,
                'message' => 'DB 연결 정보가 저장되었습니다.'
            ];
            
        } catch (PDOException $e) {
            return ['error' => 'DB 연결 정보 저장 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 실험 결과 저장
     */
    public function saveExperimentResult($experimentId, $resultData) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $currentTime = time();
            $collectionDate = isset($resultData['collection_date']) ? 
                              strtotime($resultData['collection_date']) : $currentTime;
            
            $sql = "INSERT INTO mdl_alt42_experiment_results 
                    (experiment_id, result_type, result_title, result_content, result_data, author_id, collection_date, timecreated, timemodified) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $experimentId,
                $resultData['result_type'],
                $resultData['result_title'],
                $resultData['result_content'],
                is_array($resultData['result_data']) ? json_encode($resultData['result_data']) : $resultData['result_data'],
                $resultData['author_id'],
                $collectionDate,
                $currentTime,
                $currentTime
            ]);
            
            return [
                'success' => true,
                'result_id' => $this->connection->lastInsertId(),
                'message' => '실험 결과가 저장되었습니다.'
            ];
            
        } catch (PDOException $e) {
            return ['error' => '실험 결과 저장 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 가설 저장
     */
    public function saveHypothesis($experimentId, $hypothesisText, $hypothesisType = 'primary', $authorId = null) {
        error_log("가설 저장 시도: experimentId=$experimentId, text=$hypothesisText, type=$hypothesisType, authorId=$authorId");
        
        if (!$this->connection) {
            error_log('가설 저장 실패: DB 연결이 없음');
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $currentTime = time();
            
            $sql = "INSERT INTO mdl_alt42_hypotheses 
                    (experiment_id, hypothesis_text, hypothesis_type, author_id, timecreated, timemodified) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            error_log("가설 저장 SQL: $sql");
            error_log("가설 저장 파라미터: " . json_encode([$experimentId, $hypothesisText, $hypothesisType, $authorId, $currentTime, $currentTime]));
            
            $stmt = $this->connection->prepare($sql);
            $executeResult = $stmt->execute([
                $experimentId,
                $hypothesisText,
                $hypothesisType,
                $authorId,
                $currentTime,
                $currentTime
            ]);
            
            if (!$executeResult) {
                error_log('가설 저장 실패: execute() 반환값이 false');
                return ['error' => '가설 저장 실행 실패'];
            }
            
            $hypothesisId = $this->connection->lastInsertId();
            error_log("가설 저장 성공: ID=$hypothesisId");
            
            return [
                'success' => true,
                'hypothesis_id' => $hypothesisId,
                'message' => '가설이 저장되었습니다.'
            ];
            
        } catch (PDOException $e) {
            error_log('가설 저장 PDO 오류: ' . $e->getMessage());
            error_log('가설 저장 PDO 오류 상세: ' . $e->getTraceAsString());
            return ['error' => '가설 저장 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 실험 조회
     */
    public function getExperiment($experimentId) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $sql = "SELECT * FROM mdl_alt42_experiments WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$experimentId]);
            $experiment = $stmt->fetch();
            
            if (!$experiment) {
                return ['error' => '실험을 찾을 수 없습니다.'];
            }
            
            return [
                'success' => true,
                'experiment' => $experiment
            ];
            
        } catch (PDOException $e) {
            return ['error' => '실험 조회 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 실험 목록 조회
     */
    public function getExperimentsList($createdBy = null, $status = null, $limit = 50, $offset = 0) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $whereClause = '';
            $params = [];
            
            if ($createdBy) {
                $whereClause = ' WHERE created_by = ?';
                $params[] = $createdBy;
            }
            
            if ($status) {
                $whereClause .= ($whereClause ? ' AND' : ' WHERE') . ' status = ?';
                $params[] = $status;
            }
            
            $sql = "SELECT * FROM mdl_alt42_experiments{$whereClause} ORDER BY timecreated DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $experiments = $stmt->fetchAll();
            
            return [
                'success' => true,
                'experiments' => $experiments
            ];
            
        } catch (PDOException $e) {
            return ['error' => '실험 목록 조회 실패: ' . $e->getMessage()];
        }
    }
    
    /**
     * 실험 진행 로그 기록
     */
    public function logExperimentActivity($experimentId, $logType, $logMessage, $logData = null, $userId = null) {
        if (!$this->connection) {
            return ['error' => 'DB 연결 실패'];
        }
        
        try {
            $currentTime = time();
            
            $sql = "INSERT INTO mdl_alt42_experiment_logs 
                    (experiment_id, log_type, log_message, log_data, user_id, timecreated) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $experimentId,
                $logType,
                $logMessage,
                is_array($logData) ? json_encode($logData) : $logData,
                $userId,
                $currentTime
            ]);
            
            return [
                'success' => true,
                'message' => '로그가 기록되었습니다.'
            ];
            
        } catch (PDOException $e) {
            return ['error' => '로그 기록 실패: ' . $e->getMessage()];
        }
    }
}