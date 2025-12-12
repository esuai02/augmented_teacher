<?php
/**
 * 온톨로지 파일 검증기
 * File: agent22_module_improvement/ontology/OntologyValidator.php
 * 
 * 온톨로지 파일의 유효성 검증 기능 제공
 */

require_once(__DIR__ . '/OntologyFileLoader.php');

class OntologyValidator {
    
    /**
     * 온톨로지 파일 검증
     * 
     * @param string $agentId 에이전트 ID
     * @return array 검증 결과 ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public static function validate(string $agentId): array {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            // 정규화
            $agentId = OntologyConfig::normalizeAgentId($agentId);
            
            // 파일 존재 여부 확인
            if (!OntologyFileLoader::exists($agentId)) {
                $result['valid'] = false;
                $result['errors'][] = "Ontology file not found for agent: {$agentId}";
                return $result;
            }
            
            // 파일 로드
            $content = OntologyFileLoader::load($agentId);
            if ($content === null) {
                $result['valid'] = false;
                $result['errors'][] = "Failed to load ontology file for agent: {$agentId}";
                return $result;
            }
            
            // XML 파싱 가능 여부 확인
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);
            $xmlErrors = libxml_get_errors();
            libxml_clear_errors();
            
            if ($xml === false) {
                $result['valid'] = false;
                foreach ($xmlErrors as $error) {
                    $result['errors'][] = "XML parsing error: " . trim($error->message);
                }
                return $result;
            }
            
            // 기본 구조 확인
            $structureCheck = self::checkBasicStructure($xml, $agentId);
            $result['errors'] = array_merge($result['errors'], $structureCheck['errors']);
            $result['warnings'] = array_merge($result['warnings'], $structureCheck['warnings']);
            
            if (!empty($result['errors'])) {
                $result['valid'] = false;
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("[OntologyValidator] Error validating ontology file for {$agentId}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            $result['valid'] = false;
            $result['errors'][] = "Validation error: " . $e->getMessage();
            return $result;
        }
    }
    
    /**
     * 기본 구조 확인
     * 
     * @param SimpleXMLElement $xml XML 객체
     * @param string $agentId 에이전트 ID
     * @return array 검증 결과
     */
    private static function checkBasicStructure(SimpleXMLElement $xml, string $agentId): array {
        $errors = [];
        $warnings = [];
        
        // RDF 네임스페이스 확인
        $namespaces = $xml->getNamespaces(true);
        if (empty($namespaces)) {
            $errors[] = "No namespaces found in ontology file";
        }
        
        // OWL 네임스페이스 확인
        if (!isset($namespaces['owl'])) {
            $warnings[] = "OWL namespace not found";
        }
        
        // RDF 네임스페이스 확인
        if (!isset($namespaces['rdf'])) {
            $warnings[] = "RDF namespace not found";
        }
        
        // Ontology 요소 확인
        $ontology = $xml->xpath('//owl:Ontology');
        if (empty($ontology)) {
            $warnings[] = "No owl:Ontology element found";
        }
        
        // Class 요소 확인 (최소한 하나는 있어야 함)
        $classes = $xml->xpath('//owl:Class');
        if (empty($classes)) {
            $warnings[] = "No owl:Class elements found";
        }
        
        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * 빠른 검증 (파일 존재 여부만 확인)
     * 
     * @param string $agentId 에이전트 ID
     * @return bool 파일 존재 여부
     */
    public static function quickValidate(string $agentId): bool {
        return OntologyFileLoader::exists($agentId);
    }
}

