<?php
/**
 * 홀론 부분 읽기 엔진
 * 파일에서 특정 섹션만 읽어 Context Window 절약
 * 
 * 파일: alt42/orchestration/Holarchy/2 Neural/resolver/partial_reader.php
 */

require_once __DIR__ . '/coordinate_parser.php';

class PartialReader {
    private $holonBaseDir;
    private $index;
    
    public function __construct($holonBaseDir, $indexPath = null) {
        $this->holonBaseDir = rtrim($holonBaseDir, '/');
        
        if ($indexPath && file_exists($indexPath)) {
            $this->index = json_decode(file_get_contents($indexPath), true);
        } else {
            $this->index = null;
        }
    }
    
    /**
     * 좌표 기반으로 홀론의 특정 부분만 읽기
     * 
     * @param string $coordinate 홀론 좌표
     * @return array 읽은 데이터와 메타정보
     */
    public function read($coordinate) {
        $parsed = CoordinateParser::parse($coordinate);
        
        if (!$parsed['valid']) {
            return [
                'success' => false,
                'error' => '잘못된 좌표 형식',
                'coordinate' => $coordinate
            ];
        }
        
        $holonId = $parsed['holon_id'];
        
        // 인덱스에서 파일 경로 찾기
        $filePath = $this->findHolonPath($holonId);
        
        if (!$filePath) {
            return [
                'success' => false,
                'error' => '홀론을 찾을 수 없음',
                'holon_id' => $holonId
            ];
        }
        
        // 파일에서 JSON 추출
        $content = file_get_contents($filePath);
        if (!$content) {
            return [
                'success' => false,
                'error' => '파일 읽기 실패',
                'path' => $filePath
            ];
        }
        
        // JSON 블록 추출
        if (!preg_match('/```json\s*(\{[\s\S]*?\})\s*```/u', $content, $matches)) {
            return [
                'success' => false,
                'error' => 'JSON 블록을 찾을 수 없음',
                'path' => $filePath
            ];
        }
        
        $json = json_decode($matches[1], true);
        if (!$json) {
            return [
                'success' => false,
                'error' => 'JSON 파싱 실패',
                'json_error' => json_last_error_msg()
            ];
        }
        
        // 좌표에 해당하는 데이터 추출
        $data = CoordinateParser::resolve($json, $parsed);
        
        // 토큰 수 추정 (대략 4글자 = 1토큰)
        $dataStr = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : (string)$data;
        $estimatedTokens = ceil(mb_strlen($dataStr) / 4);
        
        return [
            'success' => true,
            'coordinate' => $coordinate,
            'holon_id' => $holonId,
            'section' => $parsed['section'],
            'path' => $parsed['path'],
            'data' => $data,
            'meta' => [
                'file_path' => $filePath,
                'estimated_tokens' => $estimatedTokens,
                'data_type' => gettype($data)
            ]
        ];
    }
    
    /**
     * 여러 좌표를 한 번에 읽기 (배치 처리)
     * 
     * @param array $coordinates 좌표 배열
     * @param int $maxTokens 최대 토큰 수 제한
     * @return array 읽은 데이터 배열
     */
    public function readBatch($coordinates, $maxTokens = 4000) {
        $results = [];
        $totalTokens = 0;
        
        foreach ($coordinates as $coord) {
            $result = $this->read($coord);
            
            if ($result['success']) {
                $tokens = $result['meta']['estimated_tokens'];
                
                if ($totalTokens + $tokens > $maxTokens) {
                    $results[] = [
                        'success' => false,
                        'error' => '토큰 한도 초과',
                        'coordinate' => $coord,
                        'tokens_needed' => $tokens,
                        'tokens_remaining' => $maxTokens - $totalTokens
                    ];
                    break;
                }
                
                $totalTokens += $tokens;
            }
            
            $results[] = $result;
        }
        
        return [
            'results' => $results,
            'total_tokens' => $totalTokens,
            'coordinates_processed' => count($results)
        ];
    }
    
    /**
     * 홀론의 특정 섹션 라인 범위만 읽기 (더 경량화)
     */
    public function readSectionLines($holonId, $section) {
        if (!$this->index) {
            return $this->read("$holonId.$section");
        }
        
        if (!isset($this->index['holons'][$holonId])) {
            return [
                'success' => false,
                'error' => '인덱스에 홀론 없음',
                'holon_id' => $holonId
            ];
        }
        
        $holonInfo = $this->index['holons'][$holonId];
        
        if (!isset($holonInfo['sections'][$section])) {
            return [
                'success' => false,
                'error' => '섹션 정보 없음',
                'section' => $section
            ];
        }
        
        $sectionInfo = $holonInfo['sections'][$section];
        $filePath = $this->holonBaseDir . '/' . $holonInfo['path'];
        
        // 파일에서 해당 라인만 읽기
        $lines = file($filePath);
        if (!$lines) {
            return [
                'success' => false,
                'error' => '파일 읽기 실패'
            ];
        }
        
        $startLine = max(0, $sectionInfo['start_line'] - 1);
        $endLine = min(count($lines), $sectionInfo['end_line']);
        
        $sectionLines = array_slice($lines, $startLine, $endLine - $startLine);
        $sectionContent = implode('', $sectionLines);
        
        return [
            'success' => true,
            'holon_id' => $holonId,
            'section' => $section,
            'content' => $sectionContent,
            'lines' => [
                'start' => $sectionInfo['start_line'],
                'end' => $sectionInfo['end_line']
            ],
            'estimated_tokens' => ceil(mb_strlen($sectionContent) / 4)
        ];
    }
    
    /**
     * 홀론 파일 경로 찾기
     */
    private function findHolonPath($holonId) {
        // 인덱스가 있으면 인덱스에서 찾기
        if ($this->index && isset($this->index['holons'][$holonId])) {
            return $this->holonBaseDir . '/' . $this->index['holons'][$holonId]['path'];
        }
        
        // 인덱스가 없으면 파일 시스템 검색
        $patterns = [
            $this->holonBaseDir . "/*/{$holonId}.md",
            $this->holonBaseDir . "/*/*/{$holonId}.md",
            $this->holonBaseDir . "/*/{$holonId}-*.md",
            $this->holonBaseDir . "/*/*/{$holonId}-*.md"
        ];
        
        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            if (!empty($files)) {
                return $files[0];
            }
        }
        
        // slug로도 검색
        $allFiles = glob($this->holonBaseDir . '/*/*.md');
        foreach ($allFiles as $file) {
            $content = file_get_contents($file);
            if (preg_match('/"holon_id":\s*"' . preg_quote($holonId) . '"/', $content)) {
                return $file;
            }
        }
        
        return null;
    }
    
    /**
     * 홀론의 W_summary만 빠르게 가져오기 (연관성 판정용)
     */
    public function getWSummary($holonId) {
        if ($this->index && isset($this->index['holons'][$holonId])) {
            return [
                'success' => true,
                'holon_id' => $holonId,
                'W_summary' => $this->index['holons'][$holonId]['W_summary'],
                'keywords' => $this->index['holons'][$holonId]['keywords']
            ];
        }
        
        // 인덱스가 없으면 직접 읽기
        $result = $this->read("$holonId.W.will.drive");
        if ($result['success']) {
            return [
                'success' => true,
                'holon_id' => $holonId,
                'W_summary' => is_string($result['data']) ? $result['data'] : json_encode($result['data']),
                'keywords' => []
            ];
        }
        
        return $result;
    }
}

