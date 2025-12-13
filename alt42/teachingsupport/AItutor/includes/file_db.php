<?php
/**
 * 파일 기반 데이터베이스
 * 로컬 파일을 DB처럼 사용하는 클래스
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class FileDB {
    private $dbPath;
    private $tables;
    private $indexes;
    
    public function __construct($dbPath = null) {
        $this->dbPath = $dbPath ?? __DIR__ . '/../data';
        $this->tables = [];
        $this->indexes = [];
        
        // 데이터 디렉토리 생성
        if (!is_dir($this->dbPath)) {
            mkdir($this->dbPath, 0755, true);
        }
        
        // 인덱스 파일 로드
        $this->loadIndexes();
    }
    
    /**
     * 테이블 생성
     */
    public function createTable($tableName, $schema = []) {
        $tablePath = $this->getTablePath($tableName);
        
        if (!is_dir($tablePath)) {
            mkdir($tablePath, 0755, true);
        }
        
        // 스키마 저장
        $schemaFile = $tablePath . '/_schema.json';
        file_put_contents($schemaFile, json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        // 인덱스 초기화
        $this->indexes[$tableName] = [];
        $this->saveIndexes();
        
        return true;
    }
    
    /**
     * 데이터 삽입
     */
    public function insert($tableName, $data) {
        // ID 생성
        if (!isset($data['id'])) {
            $data['id'] = $this->generateId($tableName);
        }
        
        // 타임스탬프 추가
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // 테이블 디렉토리 확인 및 생성
        $tablePath = $this->getTablePath($tableName);
        if (!is_dir($tablePath)) {
            if (!mkdir($tablePath, 0755, true)) {
                error_log("FileDB: 테이블 디렉토리 생성 실패: " . $tablePath);
                throw new Exception("테이블 디렉토리 생성 실패: " . $tableName);
            }
        }
        
        // 파일로 저장
        $filePath = $this->getRecordPath($tableName, $data['id']);
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        if ($jsonData === false) {
            error_log("FileDB: JSON 인코딩 실패: " . json_last_error_msg());
            throw new Exception("JSON 인코딩 실패: " . json_last_error_msg());
        }
        
        $result = file_put_contents($filePath, $jsonData);
        
        if ($result === false) {
            error_log("FileDB: 파일 저장 실패: " . $filePath);
            throw new Exception("파일 저장 실패: " . $filePath);
        }
        
        error_log("FileDB: 레코드 저장 성공: " . $filePath . " (" . strlen($jsonData) . " bytes)");
        
        // 인덱스 업데이트
        $this->updateIndex($tableName, $data['id'], $data);
        
        return $data['id'];
    }
    
    /**
     * 데이터 조회 (단일)
     */
    public function find($tableName, $id) {
        $filePath = $this->getRecordPath($tableName, $id);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $content = file_get_contents($filePath);
        return json_decode($content, true);
    }
    
    /**
     * 데이터 조회 (다중)
     */
    public function findAll($tableName, $conditions = [], $orderBy = null, $limit = null) {
        $records = $this->getAllRecords($tableName);
        
        // 조건 필터링
        if (!empty($conditions)) {
            $records = $this->filterRecords($records, $conditions);
        }
        
        // 정렬
        if ($orderBy) {
            $records = $this->sortRecords($records, $orderBy);
        }
        
        // 제한
        if ($limit) {
            $records = array_slice($records, 0, $limit);
        }
        
        return $records;
    }
    
    /**
     * 데이터 업데이트
     */
    public function update($tableName, $id, $data) {
        $existing = $this->find($tableName, $id);
        
        if (!$existing) {
            return false;
        }
        
        // 기존 데이터와 병합
        $updated = array_merge($existing, $data);
        $updated['updated_at'] = date('Y-m-d H:i:s');
        $updated['id'] = $id; // ID 유지
        
        // 파일 업데이트
        $filePath = $this->getRecordPath($tableName, $id);
        file_put_contents($filePath, json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        // 인덱스 업데이트
        $this->updateIndex($tableName, $id, $updated);
        
        return true;
    }
    
    /**
     * 데이터 삭제
     */
    public function delete($tableName, $id) {
        $filePath = $this->getRecordPath($tableName, $id);
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        unlink($filePath);
        
        // 인덱스에서 제거
        $this->removeFromIndex($tableName, $id);
        
        return true;
    }
    
    /**
     * 쿼리 실행
     */
    public function query($tableName, $query) {
        $records = $this->getAllRecords($tableName);
        
        // WHERE 조건
        if (isset($query['where'])) {
            $records = $this->filterRecords($records, $query['where']);
        }
        
        // ORDER BY
        if (isset($query['orderBy'])) {
            $records = $this->sortRecords($records, $query['orderBy']);
        }
        
        // LIMIT
        if (isset($query['limit'])) {
            $records = array_slice($records, 0, $query['limit']);
        }
        
        // OFFSET
        if (isset($query['offset'])) {
            $records = array_slice($records, $query['offset']);
        }
        
        return $records;
    }
    
    /**
     * 인덱스 생성
     */
    public function createIndex($tableName, $field) {
        if (!isset($this->indexes[$tableName])) {
            $this->indexes[$tableName] = [];
        }
        
        if (!isset($this->indexes[$tableName][$field])) {
            $this->indexes[$tableName][$field] = [];
        }
        
        // 모든 레코드 스캔하여 인덱스 구축
        $records = $this->getAllRecords($tableName);
        
        foreach ($records as $record) {
            if (isset($record[$field])) {
                $value = $record[$field];
                if (!isset($this->indexes[$tableName][$field][$value])) {
                    $this->indexes[$tableName][$field][$value] = [];
                }
                $this->indexes[$tableName][$field][$value][] = $record['id'];
            }
        }
        
        $this->saveIndexes();
        
        return true;
    }
    
    /**
     * 인덱스를 사용한 빠른 검색
     */
    public function findByIndex($tableName, $field, $value) {
        if (!isset($this->indexes[$tableName][$field][$value])) {
            return [];
        }
        
        $ids = $this->indexes[$tableName][$field][$value];
        $records = [];
        
        foreach ($ids as $id) {
            $record = $this->find($tableName, $id);
            if ($record) {
                $records[] = $record;
            }
        }
        
        return $records;
    }
    
    /**
     * 테이블 경로 가져오기
     */
    private function getTablePath($tableName) {
        return $this->dbPath . '/' . $tableName;
    }
    
    /**
     * 레코드 파일 경로 가져오기
     */
    private function getRecordPath($tableName, $id) {
        $tablePath = $this->getTablePath($tableName);
        return $tablePath . '/' . $id . '.json';
    }
    
    /**
     * 모든 레코드 가져오기
     */
    private function getAllRecords($tableName) {
        $tablePath = $this->getTablePath($tableName);
        
        if (!is_dir($tablePath)) {
            return [];
        }
        
        $records = [];
        $files = glob($tablePath . '/*.json');
        
        foreach ($files as $file) {
            // 스키마 파일 제외
            if (basename($file) === '_schema.json') {
                continue;
            }
            
            $content = file_get_contents($file);
            $record = json_decode($content, true);
            
            if ($record) {
                $records[] = $record;
            }
        }
        
        return $records;
    }
    
    /**
     * 레코드 필터링
     */
    private function filterRecords($records, $conditions) {
        $filtered = [];
        
        foreach ($records as $record) {
            $match = true;
            
            foreach ($conditions as $field => $condition) {
                if (!$this->evaluateCondition($record, $field, $condition)) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                $filtered[] = $record;
            }
        }
        
        return $filtered;
    }
    
    /**
     * 조건 평가
     */
    private function evaluateCondition($record, $field, $condition) {
        if (!isset($record[$field])) {
            return false;
        }
        
        $value = $record[$field];
        
        // 배열 조건 (다양한 연산자 지원)
        if (is_array($condition)) {
            if (isset($condition['$eq'])) {
                return $value == $condition['$eq'];
            }
            if (isset($condition['$ne'])) {
                return $value != $condition['$ne'];
            }
            if (isset($condition['$gt'])) {
                return $value > $condition['$gt'];
            }
            if (isset($condition['$gte'])) {
                return $value >= $condition['$gte'];
            }
            if (isset($condition['$lt'])) {
                return $value < $condition['$lt'];
            }
            if (isset($condition['$lte'])) {
                return $value <= $condition['$lte'];
            }
            if (isset($condition['$in'])) {
                return in_array($value, $condition['$in']);
            }
            if (isset($condition['$like'])) {
                return strpos($value, $condition['$like']) !== false;
            }
            if (isset($condition['$regex'])) {
                return preg_match('/' . $condition['$regex'] . '/u', $value) === 1;
            }
        }
        
        // 단순 값 비교
        return $value == $condition;
    }
    
    /**
     * 레코드 정렬
     */
    private function sortRecords($records, $orderBy) {
        if (is_string($orderBy)) {
            $orderBy = [$orderBy => 'ASC'];
        }
        
        usort($records, function($a, $b) use ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $aVal = $this->getNestedValue($a, $field);
                $bVal = $this->getNestedValue($b, $field);
                
                if ($aVal == $bVal) {
                    continue;
                }
                
                $result = $aVal < $bVal ? -1 : 1;
                
                if (strtoupper($direction) === 'DESC') {
                    $result *= -1;
                }
                
                return $result;
            }
            
            return 0;
        });
        
        return $records;
    }
    
    /**
     * 중첩 필드 값 가져오기
     */
    private function getNestedValue($array, $field) {
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            $value = $array;
            foreach ($parts as $part) {
                if (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } else {
                    return null;
                }
            }
            return $value;
        }
        
        return $array[$field] ?? null;
    }
    
    /**
     * ID 생성
     */
    private function generateId($tableName) {
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return $tableName . '_' . $timestamp . '_' . $random;
    }
    
    /**
     * 인덱스 업데이트
     */
    private function updateIndex($tableName, $id, $data) {
        if (!isset($this->indexes[$tableName])) {
            return;
        }
        
        foreach ($this->indexes[$tableName] as $field => &$index) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if (!isset($index[$value])) {
                    $index[$value] = [];
                }
                if (!in_array($id, $index[$value])) {
                    $index[$value][] = $id;
                }
            }
        }
        
        $this->saveIndexes();
    }
    
    /**
     * 인덱스에서 제거
     */
    private function removeFromIndex($tableName, $id) {
        if (!isset($this->indexes[$tableName])) {
            return;
        }
        
        foreach ($this->indexes[$tableName] as $field => &$index) {
            foreach ($index as $value => &$ids) {
                $key = array_search($id, $ids);
                if ($key !== false) {
                    unset($ids[$key]);
                    $ids = array_values($ids); // 인덱스 재정렬
                }
            }
        }
        
        $this->saveIndexes();
    }
    
    /**
     * 인덱스 로드
     */
    private function loadIndexes() {
        $indexFile = $this->dbPath . '/_indexes.json';
        
        if (file_exists($indexFile)) {
            $content = file_get_contents($indexFile);
            $this->indexes = json_decode($content, true) ?? [];
        }
    }
    
    /**
     * 인덱스 저장
     */
    private function saveIndexes() {
        $indexFile = $this->dbPath . '/_indexes.json';
        file_put_contents($indexFile, json_encode($this->indexes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    /**
     * 테이블 존재 확인
     */
    public function tableExists($tableName) {
        return is_dir($this->getTablePath($tableName));
    }
    
    /**
     * 테이블 삭제
     */
    public function dropTable($tableName) {
        $tablePath = $this->getTablePath($tableName);
        
        if (is_dir($tablePath)) {
            $files = glob($tablePath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tablePath);
        }
        
        // 인덱스에서 제거
        if (isset($this->indexes[$tableName])) {
            unset($this->indexes[$tableName]);
            $this->saveIndexes();
        }
        
        return true;
    }
    
    /**
     * 통계 정보
     */
    public function getStats($tableName) {
        $records = $this->getAllRecords($tableName);
        
        return [
            'table' => $tableName,
            'record_count' => count($records),
            'indexes' => array_keys($this->indexes[$tableName] ?? []),
            'last_updated' => $this->getLastUpdated($tableName)
        ];
    }
    
    /**
     * 마지막 업데이트 시간
     */
    private function getLastUpdated($tableName) {
        $records = $this->getAllRecords($tableName);
        
        if (empty($records)) {
            return null;
        }
        
        $latest = null;
        foreach ($records as $record) {
            if (isset($record['updated_at'])) {
                if (!$latest || $record['updated_at'] > $latest) {
                    $latest = $record['updated_at'];
                }
            }
        }
        
        return $latest;
    }
}

