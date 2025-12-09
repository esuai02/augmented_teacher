<?php
/**
 * 온톨로지 설정 관리
 * File: agent22_module_improvement/ontology/OntologyConfig.php
 * 
 * 에이전트별 온톨로지 파일 경로 매핑 및 설정 관리
 */

class OntologyConfig {
    
    /**
     * 에이전트별 온톨로지 파일 경로 매핑
     * 
     * @return array 에이전트 ID => 온톨로지 파일 경로
     */
    public static function getOntologyFilePaths(): array {
        $basePath = __DIR__ . '/../../ontology_engineering/modules';
        
        return [
            'agent01' => $basePath . '/agent01.owl',
            'agent02' => $basePath . '/agent02.owl',
            'agent03' => $basePath . '/agent03.owl',
            'agent04' => $basePath . '/agent04.owl',
            'agent05' => $basePath . '/agent05.owl',
            'agent06' => $basePath . '/agent06.owl',
            'agent07' => $basePath . '/agent07.owl',
            'agent08' => $basePath . '/agent08.owl',
            'agent09' => $basePath . '/agent09.owl',
            'agent10' => $basePath . '/agent10.owl',
            'agent11' => $basePath . '/agent11.owl',
            'agent12' => $basePath . '/agent12.owl',
            'agent13' => $basePath . '/agent13.owl',
            'agent14' => $basePath . '/agent14.owl',
            'agent15' => $basePath . '/agent15.owl',
            'agent16' => $basePath . '/agent16.owl',
            'agent17' => $basePath . '/agent17.owl',
            'agent18' => $basePath . '/agent18.owl',
            'agent19' => $basePath . '/agent19.owl',
            'agent20' => $basePath . '/agent20.owl',
            'agent21' => $basePath . '/agent21.owl',
            'agent22' => $basePath . '/agent22.owl',
        ];
    }
    
    /**
     * 특정 에이전트의 온톨로지 파일 경로 가져오기
     * 
     * @param string $agentId 에이전트 ID (예: 'agent01')
     * @return string|null 온톨로지 파일 경로 또는 null
     */
    public static function getOntologyFilePath(string $agentId): ?string {
        $paths = self::getOntologyFilePaths();
        return $paths[$agentId] ?? null;
    }
    
    /**
     * 에이전트별 온톨로지 네임스페이스 매핑
     * 
     * @return array 에이전트 ID => 네임스페이스 URI
     */
    public static function getOntologyNamespaces(): array {
        return [
            'agent01' => 'https://mathking.kr/ontology/mathking/',
            'agent02' => 'https://mathking.kr/ontology/mathking/',
            'agent03' => 'https://mathking.kr/ontology/mathking/',
            'agent04' => 'https://mathking.kr/ontology/mathking/',
            'agent05' => 'https://mathking.kr/ontology/mathking/',
            'agent06' => 'https://mathking.kr/ontology/mathking/',
            'agent07' => 'https://mathking.kr/ontology/mathking/',
            'agent08' => 'https://mathking.kr/ontology/mathking/',
            'agent09' => 'https://mathking.kr/ontology/mathking/',
            'agent10' => 'https://mathking.kr/ontology/mathking/',
            'agent11' => 'https://mathking.kr/ontology/mathking/',
            'agent12' => 'https://mathking.kr/ontology/mathking/',
            'agent13' => 'https://mathking.kr/ontology/mathking/',
            'agent14' => 'https://mathking.kr/ontology/mathking/',
            'agent15' => 'https://mathking.kr/ontology/mathking/',
            'agent16' => 'https://mathking.kr/ontology/mathking/',
            'agent17' => 'https://mathking.kr/ontology/mathking/',
            'agent18' => 'https://mathking.kr/ontology/mathking/',
            'agent19' => 'https://mathking.kr/ontology/mathking/',
            'agent20' => 'https://mathking.kr/ontology/mathking/',
            'agent21' => 'https://mathking.kr/ontology/mathking/',
            'agent22' => 'https://mathking.kr/ontology/alphatutor/',
        ];
    }
    
    /**
     * 특정 에이전트의 온톨로지 네임스페이스 가져오기
     * 
     * @param string $agentId 에이전트 ID
     * @return string 네임스페이스 URI
     */
    public static function getOntologyNamespace(string $agentId): string {
        $namespaces = self::getOntologyNamespaces();
        return $namespaces[$agentId] ?? 'https://mathking.kr/ontology/mathking/';
    }
    
    /**
     * 에이전트별 온톨로지 프리픽스 매핑
     * 
     * @return array 에이전트 ID => 프리픽스
     */
    public static function getOntologyPrefixes(): array {
        return [
            'agent01' => 'mk:',
            'agent02' => 'mk:',
            'agent03' => 'mk:',
            'agent04' => 'mk:',
            'agent05' => 'mk:',
            'agent06' => 'mk:',
            'agent07' => 'mk:',
            'agent08' => 'mk:',
            'agent09' => 'mk:',
            'agent10' => 'mk:',
            'agent11' => 'mk:',
            'agent12' => 'mk:',
            'agent13' => 'mk:',
            'agent14' => 'mk:',
            'agent15' => 'mk:',
            'agent16' => 'mk:',
            'agent17' => 'mk:',
            'agent18' => 'mk:',
            'agent19' => 'mk:',
            'agent20' => 'mk:',
            'agent21' => 'mk:',
            'agent22' => 'at:',
        ];
    }
    
    /**
     * 특정 에이전트의 온톨로지 프리픽스 가져오기
     * 
     * @param string $agentId 에이전트 ID
     * @return string 프리픽스
     */
    public static function getOntologyPrefix(string $agentId): string {
        $prefixes = self::getOntologyPrefixes();
        return $prefixes[$agentId] ?? 'mk:';
    }
    
    /**
     * 에이전트 ID 정규화 (agent01, agent1 등 다양한 형식 지원)
     * 
     * @param string $agentId 에이전트 ID
     * @return string 정규화된 에이전트 ID (예: 'agent01')
     */
    public static function normalizeAgentId(string $agentId): string {
        // agent01, agent1, agent_01 등 다양한 형식 지원
        $agentId = strtolower(trim($agentId));
        
        // agent_01 -> agent01
        $agentId = str_replace('_', '', $agentId);
        
        // agent1 -> agent01
        if (preg_match('/^agent(\d+)$/', $agentId, $matches)) {
            $num = intval($matches[1]);
            $agentId = 'agent' . str_pad($num, 2, '0', STR_PAD_LEFT);
        }
        
        return $agentId;
    }
    
    /**
     * 에이전트 ID 유효성 검증
     * 
     * @param string $agentId 에이전트 ID
     * @return bool 유효한 에이전트 ID인지 여부
     */
    public static function isValidAgentId(string $agentId): bool {
        $normalized = self::normalizeAgentId($agentId);
        $paths = self::getOntologyFilePaths();
        return isset($paths[$normalized]);
    }
}

