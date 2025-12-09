<?php
/**
 * Database Model
 * 실제 데이터베이스 연결 및 쿼리 실행을 담당
 */

class DatabaseModel {
    private $connection;
    private $mathkingConnection;
    
    public function __construct() {
        $this->connection = $this->connectToMoodleDB();
        $this->mathkingConnection = $this->connectToMathkingDB();
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
     * Mathking DB 연결
     */
    private function connectToMathkingDB() {
        try {
            global $CFG;
            
            $mathkingDB = new PDO(
                'mysql:host=' . $CFG->dbhost . ';dbname=mathking;charset=utf8mb4', 
                $CFG->dbuser, 
                $CFG->dbpass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            
            return $mathkingDB;
        } catch (PDOException $e) {
            error_log('Mathking DB 연결 실패: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 실제 DB 테이블 목록 가져오기
     */
    public function getDBTables($search = '') {
        try {
            $allTables = [];
            
            // Moodle DB 테이블 가져오기
            if ($this->connection) {
                $moodleTables = $this->getTablesFromConnection($this->connection, $search, 'moodle');
                $allTables = array_merge($allTables, $moodleTables);
            }
            
            // Mathking DB 테이블 가져오기
            if ($this->mathkingConnection) {
                $mathkingTables = $this->getTablesFromConnection($this->mathkingConnection, $search, 'mathking');
                $allTables = array_merge($allTables, $mathkingTables);
            }
            
            if (empty($allTables)) {
                return ['error' => 'DB 연결 실패'];
            }
            
            return [
                'success' => true,
                'tables' => $allTables
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 특정 연결에서 테이블 가져오기
     */
    private function getTablesFromConnection($connection, $search, $dbName) {
        $tableList = [];
        
        try {
            $query = "SHOW TABLE STATUS";
            if ($search) {
                $query .= " WHERE Name LIKE :search";
            }
            
            $stmt = $connection->prepare($query);
            if ($search) {
                $stmt->bindValue(':search', '%' . $search . '%');
            }
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($tables as $table) {
                $tableList[] = [
                    'name' => $table['Name'],
                    'database' => $dbName,
                    'records' => $table['Rows'],
                    'engine' => $table['Engine'],
                    'collation' => $table['Collation'],
                    'size' => $this->formatBytes($table['Data_length'] + $table['Index_length']),
                    'last_update' => $table['Update_time'] ? date('Y-m-d H:i:s', strtotime($table['Update_time'])) : 'N/A'
                ];
            }
        } catch (Exception $e) {
            error_log("테이블 조회 실패 ($dbName): " . $e->getMessage());
        }
        
        return $tableList;
    }
    
    /**
     * 테이블 필드 정보 가져오기 (COMMENT 포함)
     */
    public function getTableFields($tableName) {
        try {
            // 테이블이 어느 DB에 있는지 확인
            $connection = $this->getConnectionForTable($tableName);
            
            if (!$connection) {
                return ['error' => 'DB 연결 실패 또는 테이블을 찾을 수 없음'];
            }
            
            // INFORMATION_SCHEMA를 사용하여 COMMENT도 함께 가져오기
            $sql = "SELECT 
                        COLUMN_NAME as 'Field',
                        COLUMN_TYPE as 'Type',
                        IS_NULLABLE as 'Null',
                        COLUMN_KEY as 'Key',
                        COLUMN_DEFAULT as 'Default',
                        EXTRA as 'Extra',
                        COLUMN_COMMENT as 'Comment'
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    ORDER BY ORDINAL_POSITION";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute([$tableName]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $fieldList = [];
            foreach ($fields as $field) {
                $fieldList[] = [
                    'name' => $field['Field'],
                    'type' => $field['Type'],
                    'null' => $field['Null'],
                    'key' => $field['Key'],
                    'default' => $field['Default'],
                    'extra' => $field['Extra'],
                    'comment' => $field['Comment']
                ];
            }
            
            return [
                'success' => true,
                'table_name' => $tableName,
                'fields' => $fieldList
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 테이블 데이터 가져오기 (페이지네이션 지원)
     */
    public function getTableData($tableName, $limit = 10, $offset = 0) {
        try {
            // 테이블이 어느 DB에 있는지 확인
            $connection = $this->getConnectionForTable($tableName);
            
            if (!$connection) {
                return ['error' => 'DB 연결 실패 또는 테이블을 찾을 수 없음'];
            }
            
            // 전체 레코드 수 조회
            $countStmt = $connection->prepare("SELECT COUNT(*) FROM `$tableName`");
            $countStmt->execute();
            $totalRecords = $countStmt->fetchColumn();
            
            // 컬럼 정보 가져오기
            $columnsStmt = $connection->prepare("DESCRIBE `$tableName`");
            $columnsStmt->execute();
            $columnsInfo = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_column($columnsInfo, 'Field');
            
            // 데이터 가져오기
            $stmt = $connection->prepare("SELECT * FROM `$tableName` LIMIT ? OFFSET ?");
            $stmt->execute([(int)$limit, (int)$offset]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $data,
                'columns' => $columns,
                'total_records' => $totalRecords,
                'current_page' => floor($offset / $limit) + 1,
                'total_pages' => ceil($totalRecords / $limit),
                'limit' => $limit,
                'offset' => $offset
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * DB 통계 정보 가져오기
     */
    public function getDBStats() {
        try {
            if (!$this->connection) {
                return ['error' => 'DB 연결 실패'];
            }
            
            // 실제 DB 통계 정보 조회
            $dbName = $this->connection->query("SELECT DATABASE()")->fetchColumn();
            $tableCount = $this->connection->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$dbName'")->fetchColumn();
            $version = $this->connection->query("SELECT VERSION()")->fetchColumn();
            
            // 데이터베이스 크기 조회
            $sizeQuery = "SELECT 
                            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB'
                         FROM information_schema.tables 
                         WHERE table_schema = '$dbName'";
            $size = $this->connection->query($sizeQuery)->fetchColumn();
            
            return [
                'success' => true,
                'database_name' => $dbName,
                'table_count' => $tableCount,
                'total_size' => $size . ' MB',
                'version' => $version
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * DB 연결 테스트
     */
    public function testDBConnection() {
        global $CFG;
        
        $result = [
            'moodle_config' => [
                'dbhost' => $CFG->dbhost ?? 'not set',
                'dbname' => $CFG->dbname ?? 'not set',
                'dbuser' => $CFG->dbuser ?? 'not set',
                'dbpass' => $CFG->dbpass ? 'set' : 'not set'
            ],
            'connection_test' => [],
            'available_databases' => []
        ];
        
        try {
            $testDB = new PDO(
                'mysql:host=' . $CFG->dbhost . ';charset=utf8mb4', 
                $CFG->dbuser, 
                $CFG->dbpass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $result['connection_test']['status'] = 'success';
            $result['connection_test']['message'] = 'MySQL 연결 성공';
            
            $stmt = $testDB->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $result['available_databases'] = $databases;
            
            $result['mathking_exists'] = in_array('mathking', $databases);
            
            if ($this->connection) {
                $tablesStmt = $this->connection->query("SHOW TABLES");
                $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
                $result['moodle_db_tables'] = array_slice($tables, 0, 10);
                $result['moodle_db_table_count'] = count($tables);
            }
            
            if ($this->mathkingConnection) {
                $tablesStmt = $this->mathkingConnection->query("SHOW TABLES");
                $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
                $result['mathking_db_tables'] = array_slice($tables, 0, 10);
                $result['mathking_db_table_count'] = count($tables);
            }
            
        } catch (Exception $e) {
            $result['connection_test']['status'] = 'error';
            $result['connection_test']['message'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * 테이블에 맞는 연결 반환
     */
    private function getConnectionForTable($tableName) {
        // 먼저 moodle DB에서 찾기
        if ($this->connection) {
            try {
                $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$tableName]);
                if ($stmt->rowCount() > 0) {
                    return $this->connection;
                }
            } catch (Exception $e) {
                // 무시
            }
        }
        
        // mathking DB에서 찾기
        if ($this->mathkingConnection) {
            try {
                $stmt = $this->mathkingConnection->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$tableName]);
                if ($stmt->rowCount() > 0) {
                    return $this->mathkingConnection;
                }
            } catch (Exception $e) {
                // 무시
            }
        }
        
        return null;
    }
    
    /**
     * 바이트를 읽기 쉬운 형태로 변환
     */
    public static function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}