<?php
/**
 * Database Controller
 * 데이터베이스 연결 및 쿼리 처리를 담당
 */

require_once(__DIR__ . '/../models/DatabaseModel.php');

class DatabaseController {
    private $dbModel;
    
    public function __construct() {
        $this->dbModel = new DatabaseModel();
    }
    
    /**
     * DB 테이블 목록 가져오기 (페이지네이션 지원)
     */
    public function getDBTables($page = 1, $limit = 50, $search = '') {
        try {
            // 실제 Moodle DB 테이블 조회
            $realTables = $this->dbModel->getDBTables($search);
            
            if (isset($realTables['error'])) {
                // DB 연결 실패 시 테스트 데이터 사용
                $allTestTables = $this->generateTestTables();
                
                // 검색 필터링
                if ($search) {
                    $allTestTables = array_filter($allTestTables, function($table) use ($search) {
                        return stripos($table['name'], $search) !== false;
                    });
                }
                
                $totalTables = count($allTestTables);
                $totalPages = ceil($totalTables / $limit);
                $offset = ($page - 1) * $limit;
                
                // 페이지네이션 적용
                $pagedTables = array_slice($allTestTables, $offset, $limit);
                
                return [
                    'success' => true,
                    'tables' => $pagedTables,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_tables' => $totalTables,
                        'limit' => $limit,
                        'offset' => $offset
                    ],
                    'note' => 'DB 연결 실패 - 테스트 데이터 사용중'
                ];
            }
            
            // 실제 데이터 사용
            $allTables = $realTables['tables'];
            $totalTables = count($allTables);
            $totalPages = ceil($totalTables / $limit);
            $offset = ($page - 1) * $limit;
            
            // 페이지네이션 적용
            $pagedTables = array_slice($allTables, $offset, $limit);
            
            return [
                'success' => true,
                'tables' => $pagedTables,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_tables' => $totalTables,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 테이블 필드 정보 가져오기
     */
    public function getTableFields($tableName) {
        return $this->dbModel->getTableFields($tableName);
    }
    
    /**
     * 테이블 데이터 가져오기
     */
    public function getTableData($tableName, $limit = 10, $offset = 0) {
        return $this->dbModel->getTableData($tableName, $limit, $offset);
    }
    
    /**
     * DB 통계 정보 가져오기
     */
    public function getDBStats() {
        return $this->dbModel->getDBStats();
    }
    
    /**
     * DB 연결 테스트
     */
    public function testDBConnection() {
        return $this->dbModel->testDBConnection();
    }
    
    /**
     * 테스트 데이터 생성
     */
    private function generateTestTables() {
        $allTestTables = [];
        for ($i = 1; $i <= 127; $i++) {
            $tableTypes = ['user', 'course', 'module', 'grade', 'quiz', 'assignment', 'forum', 'book', 'lesson', 'scorm'];
            $tableType = $tableTypes[$i % count($tableTypes)];
            
            $allTestTables[] = [
                'name' => "mdl_{$tableType}_{$i}",
                'records' => rand(10, 5000),
                'engine' => 'MyISAM',
                'collation' => 'utf8mb4_unicode_ci',
                'size' => rand(100, 10000) . ' KB',
                'last_update' => date('Y-m-d H:i:s', strtotime("-" . rand(1, 365) . " days"))
            ];
        }
        return $allTestTables;
    }
}