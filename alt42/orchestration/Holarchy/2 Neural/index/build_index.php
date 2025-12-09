<?php
/**
 * 홀론 인덱스 빌더
 * 모든 홀론 파일을 스캔하여 holon_index.json 및 connection_graph.json 생성
 * 
 * 파일: alt42/orchestration/Holarchy/2 Neural/index/build_index.php
 * 실행: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/2%20Neural/index/build_index.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

class HolonIndexBuilder {
    private $holonDir;
    private $index = [];
    private $graph = [];
    private $errors = [];
    
    public function __construct($baseDir) {
        $this->holonDir = $baseDir;
        $this->index = [
            'version' => '1.0',
            'built_at' => date('c'),
            'description' => '홀론 신경망 인덱스 - W 기반 빠른 연관성 판정을 위한 경량 인덱스',
            'holons' => []
        ];
        $this->graph = [
            'version' => '1.0',
            'built_at' => date('c'),
            'description' => '홀론 간 연결 그래프 - 신경망 전파 경로',
            'nodes' => [],
            'edges' => []
        ];
    }
    
    public function scanDirectory($dir) {
        if (!is_dir($dir)) {
            $this->errors[] = "디렉토리 없음: $dir";
            return;
        }
        
        $files = glob($dir . '/*.md');
        foreach ($files as $file) {
            $this->indexHolon($file);
        }
        
        // 하위 디렉토리도 스캔
        $subdirs = glob($dir . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            // _legacy, __pycache__ 등 제외
            $dirname = basename($subdir);
            if (strpos($dirname, '_') === 0 || strpos($dirname, '.') === 0) {
                continue;
            }
            $this->scanDirectory($subdir);
        }
    }
    
    private function indexHolon($filePath) {
        $content = file_get_contents($filePath);
        if (!$content) {
            $this->errors[] = "파일 읽기 실패: $filePath";
            return;
        }
        
        // JSON 블록 추출 (```json ... ```)
        if (!preg_match('/```json\s*(\{[\s\S]*?\})\s*```/u', $content, $matches)) {
            return; // JSON 블록이 없는 파일은 스킵
        }
        
        $json = json_decode($matches[1], true);
        if (!$json) {
            $this->errors[] = "JSON 파싱 실패: $filePath - " . json_last_error_msg();
            return;
        }
        
        if (!isset($json['holon_id'])) {
            return; // holon_id가 없는 파일은 스킵
        }
        
        $holonId = $json['holon_id'];
        $relativePath = str_replace($this->holonDir . '/', '', $filePath);
        
        // 섹션 라인 정보 추출
        $sections = $this->findSectionLines($content);
        
        // 인덱스 엔트리 생성
        $this->index['holons'][$holonId] = [
            'path' => $relativePath,
            'type' => $json['type'] ?? 'unknown',
            'module' => $json['module'] ?? null,
            'title' => $json['meta']['title'] ?? $holonId,
            'status' => $json['meta']['status'] ?? 'unknown',
            'priority' => $json['meta']['priority'] ?? 'normal',
            'W_summary' => $this->extractWSummary($json),
            'keywords' => $this->extractKeywords($json),
            'connections' => $this->extractConnections($json),
            'sections' => $sections,
            'total_lines' => count(explode("\n", $content))
        ];
        
        // 그래프 노드 추가
        $this->graph['nodes'][] = [
            'id' => $holonId,
            'type' => $json['type'] ?? 'unknown',
            'label' => $json['meta']['title'] ?? $holonId
        ];
        
        // 그래프 엣지 추가
        $this->addGraphEdges($holonId, $json);
    }
    
    private function extractWSummary($json) {
        // drive → goal.ultimate → title 순으로 시도
        if (isset($json['W']['will']['drive'])) {
            return mb_substr($json['W']['will']['drive'], 0, 150);
        }
        if (isset($json['W']['goal']['ultimate'])) {
            return $json['W']['goal']['ultimate'];
        }
        if (isset($json['W']['intention']['primary'])) {
            return $json['W']['intention']['primary'];
        }
        return $json['meta']['title'] ?? '';
    }
    
    private function extractKeywords($json) {
        $keywords = [];
        
        // 태그에서 추출
        if (isset($json['meta']['tags']['topic'])) {
            $keywords = array_merge($keywords, $json['meta']['tags']['topic']);
        }
        if (isset($json['meta']['tags']['module'])) {
            $keywords = array_merge($keywords, $json['meta']['tags']['module']);
        }
        
        // W.will.non_negotiables에서 추출
        if (isset($json['W']['will']['non_negotiables'])) {
            foreach ($json['W']['will']['non_negotiables'] as $item) {
                if (is_string($item) && mb_strlen($item) < 30) {
                    $keywords[] = $item;
                }
            }
        }
        
        return array_values(array_unique($keywords));
    }
    
    private function extractConnections($json) {
        $connections = [
            'parent' => null,
            'children' => [],
            'related' => [],
            'inputs' => [],
            'outputs' => []
        ];
        
        if (isset($json['links'])) {
            $connections['parent'] = $json['links']['parent'] ?? null;
            $connections['children'] = $json['links']['children'] ?? [];
            $connections['related'] = $json['links']['related'] ?? [];
        }
        
        if (isset($json['holon_connections'])) {
            if (isset($json['holon_connections']['inputs'])) {
                foreach ($json['holon_connections']['inputs'] as $input) {
                    $connections['inputs'][] = $input['from'] ?? null;
                }
            }
            if (isset($json['holon_connections']['outputs'])) {
                foreach ($json['holon_connections']['outputs'] as $output) {
                    $connections['outputs'][] = $output['to'] ?? null;
                }
            }
        }
        
        return $connections;
    }
    
    private function findSectionLines($content) {
        $lines = explode("\n", $content);
        $sections = [];
        $currentSection = null;
        $sectionStart = 0;
        $braceDepth = 0;
        $inSection = false;
        
        foreach ($lines as $lineNum => $line) {
            // WXSPERTA 섹션 시작 패턴
            if (preg_match('/^\s*"([WXSPERTA])":\s*\{/', $line, $match)) {
                // 이전 섹션 종료
                if ($currentSection && $inSection) {
                    $sections[$currentSection]['end_line'] = $lineNum;
                }
                
                $currentSection = $match[1];
                $sectionStart = $lineNum + 1;
                $sections[$currentSection] = [
                    'start_line' => $sectionStart,
                    'end_line' => null
                ];
                $inSection = true;
                $braceDepth = 1;
            } elseif ($inSection) {
                // 중괄호 카운팅
                $braceDepth += substr_count($line, '{');
                $braceDepth -= substr_count($line, '}');
                
                if ($braceDepth <= 0) {
                    $sections[$currentSection]['end_line'] = $lineNum + 1;
                    $inSection = false;
                    $currentSection = null;
                }
            }
        }
        
        // 마지막 섹션 종료
        if ($currentSection && $inSection) {
            $sections[$currentSection]['end_line'] = count($lines);
        }
        
        return $sections;
    }
    
    private function addGraphEdges($holonId, $json) {
        // parent 연결
        if (isset($json['links']['parent']) && $json['links']['parent']) {
            $this->graph['edges'][] = [
                'from' => $json['links']['parent'],
                'to' => $holonId,
                'type' => 'parent-child'
            ];
        }
        
        // children 연결
        if (isset($json['links']['children'])) {
            foreach ($json['links']['children'] as $child) {
                $this->graph['edges'][] = [
                    'from' => $holonId,
                    'to' => $child,
                    'type' => 'parent-child'
                ];
            }
        }
        
        // related 연결
        if (isset($json['links']['related'])) {
            foreach ($json['links']['related'] as $related) {
                $this->graph['edges'][] = [
                    'from' => $holonId,
                    'to' => $related,
                    'type' => 'related'
                ];
            }
        }
        
        // holon_connections 연결
        if (isset($json['holon_connections']['outputs'])) {
            foreach ($json['holon_connections']['outputs'] as $output) {
                if (isset($output['to'])) {
                    $this->graph['edges'][] = [
                        'from' => $holonId,
                        'to' => $output['to'],
                        'type' => 'signal',
                        'signal' => $output['signal'] ?? null
                    ];
                }
            }
        }
    }
    
    public function save($indexPath, $graphPath) {
        $indexResult = file_put_contents(
            $indexPath, 
            json_encode($this->index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        $graphResult = file_put_contents(
            $graphPath,
            json_encode($this->graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        
        return [
            'holon_count' => count($this->index['holons']),
            'node_count' => count($this->graph['nodes']),
            'edge_count' => count($this->graph['edges']),
            'index_saved' => $indexResult !== false,
            'graph_saved' => $graphResult !== false,
            'errors' => $this->errors
        ];
    }
    
    public function getIndex() {
        return $this->index;
    }
    
    public function getGraph() {
        return $this->graph;
    }
}

// 실행
try {
    $baseDir = dirname(__DIR__, 2) . '/0 Docs';
    $indexPath = __DIR__ . '/holon_index.json';
    $graphPath = __DIR__ . '/connection_graph.json';
    
    $builder = new HolonIndexBuilder($baseDir);
    $builder->scanDirectory($baseDir);
    $result = $builder->save($indexPath, $graphPath);
    
    echo json_encode([
        'success' => true,
        'message' => "인덱스 빌드 완료",
        'stats' => $result,
        'base_dir' => $baseDir,
        'index_path' => $indexPath,
        'graph_path' => $graphPath
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

