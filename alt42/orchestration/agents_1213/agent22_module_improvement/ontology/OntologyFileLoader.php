<?php
/**
 * 온톨로지 파일 로더
 * File: agent22_module_improvement/ontology/OntologyFileLoader.php
 * 
 * 온톨로지 파일 로드 및 캐싱 기능 제공
 */

require_once(__DIR__ . '/OntologyConfig.php');

class OntologyFileLoader {
    
    /**
     * 파일 캐시 (메모리 캐싱)
     * 
     * @var array 에이전트 ID => 파일 내용
     */
    private static $fileCache = [];
    
    /**
     * 파일 존재 여부 캐시
     * 
     * @var array 에이전트 ID => 존재 여부
     */
    private static $existsCache = [];
    
    /**
     * 온톨로지 파일 로드
     * 
     * @param string $agentId 에이전트 ID
     * @return string|null 파일 내용 또는 null (파일이 없거나 읽을 수 없는 경우)
     */
    public static function load(string $agentId): ?string {
        try {
            // 정규화
            $agentId = OntologyConfig::normalizeAgentId($agentId);
            
            // 캐시 확인
            if (isset(self::$fileCache[$agentId])) {
                error_log("[OntologyFileLoader] Using cached file for {$agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return self::$fileCache[$agentId];
            }
            
            // 파일 경로 가져오기
            $filePath = OntologyConfig::getOntologyFilePath($agentId);
            if (!$filePath) {
                error_log("[OntologyFileLoader] No ontology file path for {$agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return null;
            }
            
            // 파일 존재 여부 확인
            if (!file_exists($filePath)) {
                error_log("[OntologyFileLoader] Ontology file not found: {$filePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                self::$existsCache[$agentId] = false;
                return null;
            }
            
            // 파일 읽기
            $content = file_get_contents($filePath);
            if ($content === false) {
                error_log("[OntologyFileLoader] Failed to read ontology file: {$filePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return null;
            }
            
            // 캐시에 저장
            self::$fileCache[$agentId] = $content;
            self::$existsCache[$agentId] = true;
            
            error_log("[OntologyFileLoader] Loaded ontology file for {$agentId}: {$filePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return $content;
            
        } catch (Exception $e) {
            error_log("[OntologyFileLoader] Error loading ontology file for {$agentId}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * 온톨로지 파일 존재 여부 확인
     * 
     * @param string $agentId 에이전트 ID
     * @return bool 파일 존재 여부
     */
    public static function exists(string $agentId): bool {
        try {
            // 정규화
            $agentId = OntologyConfig::normalizeAgentId($agentId);
            
            // 캐시 확인
            if (isset(self::$existsCache[$agentId])) {
                return self::$existsCache[$agentId];
            }
            
            // 파일 경로 가져오기
            $filePath = OntologyConfig::getOntologyFilePath($agentId);
            if (!$filePath) {
                self::$existsCache[$agentId] = false;
                return false;
            }
            
            // 파일 존재 여부 확인
            $exists = file_exists($filePath);
            self::$existsCache[$agentId] = $exists;
            
            return $exists;
            
        } catch (Exception $e) {
            error_log("[OntologyFileLoader] Error checking ontology file existence for {$agentId}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return false;
        }
    }
    
    /**
     * 온톨로지 파일 경로 가져오기
     * 
     * @param string $agentId 에이전트 ID
     * @return string|null 파일 경로 또는 null
     */
    public static function getFilePath(string $agentId): ?string {
        $agentId = OntologyConfig::normalizeAgentId($agentId);
        return OntologyConfig::getOntologyFilePath($agentId);
    }
    
    /**
     * 캐시 초기화
     * 
     * @param string|null $agentId 특정 에이전트만 캐시 초기화 (null이면 전체)
     */
    public static function clearCache(?string $agentId = null): void {
        if ($agentId === null) {
            self::$fileCache = [];
            self::$existsCache = [];
            error_log("[OntologyFileLoader] Cleared all caches [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } else {
            $agentId = OntologyConfig::normalizeAgentId($agentId);
            unset(self::$fileCache[$agentId]);
            unset(self::$existsCache[$agentId]);
            error_log("[OntologyFileLoader] Cleared cache for {$agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    /**
     * 캐시 상태 확인
     * 
     * @return array 캐시 상태 정보
     */
    public static function getCacheStatus(): array {
        return [
            'cached_files' => array_keys(self::$fileCache),
            'cached_count' => count(self::$fileCache),
            'exists_cache_count' => count(self::$existsCache)
        ];
    }
}

