<?php
/**
 * 컨텐츠 로더
 * 룰과 온톨로지 기반 관련 컨텐츠 로드
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class ContentLoader {
    private $contentsBankPath;
    
    public function __construct($contentsBankPath = null) {
        $this->contentsBankPath = $contentsBankPath ?? __DIR__ . '/../contentsbank';
    }
    
    /**
     * 관련 컨텐츠 로드
     * 
     * @param array $matchedRules 매칭된 룰 목록
     * @param array $ontology 온톨로지
     * @return array 관련 컨텐츠 목록
     */
    public function loadRelatedContents($matchedRules, $ontology) {
        $contents = [];
        
        // 룰 기반 컨텐츠 로드
        foreach ($matchedRules as $rule) {
            $ruleContents = $this->loadRuleContents($rule['rule_id']);
            $contents = array_merge($contents, $ruleContents);
        }
        
        // 온톨로지 기반 컨텐츠 로드
        if (isset($ontology['ontology'])) {
            foreach ($ontology['ontology'] as $node) {
                $nodeContents = $this->loadOntologyContents($node);
                $contents = array_merge($contents, $nodeContents);
            }
        }
        
        return $contents;
    }
    
    /**
     * 룰 기반 컨텐츠 로드
     */
    private function loadRuleContents($ruleId) {
        $contents = [];
        
        // 룰 ID로 컨텐츠 파일 찾기
        $pattern = $this->contentsBankPath . '/rule_*_' . str_replace([':', '/'], '_', $ruleId) . '_*.json';
        $files = glob($pattern);
        
        foreach ($files as $file) {
            $content = $this->loadContentFile($file);
            if ($content) {
                $contents[] = $content;
            }
        }
        
        return $contents;
    }
    
    /**
     * 온톨로지 기반 컨텐츠 로드
     */
    private function loadOntologyContents($node) {
        $contents = [];
        
        // 노드 클래스로 컨텐츠 찾기
        $nodeClass = str_replace('mk:', '', $node['class'] ?? '');
        if ($nodeClass) {
            $pattern = $this->contentsBankPath . '/ontology_*' . $nodeClass . '*.json';
            $files = glob($pattern);
            
            foreach ($files as $file) {
                $content = $this->loadContentFile($file);
                if ($content) {
                    $contents[] = $content;
                }
            }
        }
        
        return $contents;
    }
    
    /**
     * 컨텐츠 파일 로드
     */
    private function loadContentFile($filepath) {
        if (!file_exists($filepath)) {
            return null;
        }
        
        $content = file_get_contents($filepath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Content file parse error: " . $filepath . " - " . json_last_error_msg());
            return null;
        }
        
        return $data['content'] ?? $data;
    }
    
    /**
     * 컨텐츠 인덱스 생성
     */
    public function generateContentIndex() {
        $index = [
            'rules' => [],
            'ontology' => [],
            'scenarios' => [],
            'test_cases' => []
        ];
        
        $files = glob($this->contentsBankPath . '/*.json');
        
        foreach ($files as $file) {
            $content = $this->loadContentFile($file);
            if (!$content) continue;
            
            $filename = basename($file);
            
            if (strpos($filename, 'rule_verification') !== false) {
                $index['rules'][] = [
                    'file' => $filename,
                    'rule_id' => $content['rule_id'] ?? null,
                    'type' => 'verification'
                ];
            } elseif (strpos($filename, 'rule_scenario') !== false) {
                $index['scenarios'][] = [
                    'file' => $filename,
                    'rule_id' => $content['rule_id'] ?? null,
                    'type' => 'scenario'
                ];
            } elseif (strpos($filename, 'rule_test_case') !== false) {
                $index['test_cases'][] = [
                    'file' => $filename,
                    'rule_id' => $content['rule_id'] ?? null,
                    'type' => 'test_case'
                ];
            } elseif (strpos($filename, 'ontology') !== false) {
                $index['ontology'][] = [
                    'file' => $filename,
                    'type' => 'ontology'
                ];
            }
        }
        
        // 인덱스 파일 저장
        file_put_contents(
            $this->contentsBankPath . '/index.json',
            json_encode($index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        
        return $index;
    }
}

