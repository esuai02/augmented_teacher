<?php
/**
 * 활성화 컨트롤러
 * 쿼리 기반으로 홀론을 선택적으로 활성화하고 Context Window 관리
 * 
 * 파일: alt42/orchestration/Holarchy/2 Neural/activation/activation_controller.php
 */

require_once __DIR__ . '/relevance_scorer.php';
require_once __DIR__ . '/../resolver/partial_reader.php';

class ActivationController {
    private $scorer;
    private $reader;
    private $index;
    private $maxTokens;
    private $activatedHolons = [];
    private $activationLog = [];
    
    public function __construct($holonBaseDir, $indexPath, $maxTokens = 4000) {
        $this->scorer = new RelevanceScorer();
        $this->reader = new PartialReader($holonBaseDir, $indexPath);
        $this->maxTokens = $maxTokens;
        
        if (file_exists($indexPath)) {
            $this->index = json_decode(file_get_contents($indexPath), true);
        } else {
            $this->index = ['holons' => []];
        }
    }
    
    /**
     * 쿼리 기반 홀론 활성화
     * 
     * @param string $query 검색 쿼리
     * @param array $options 옵션 (threshold, topK, expandSections 등)
     * @return array 활성화 결과
     */
    public function activate($query, $options = []) {
        $threshold = $options['threshold'] ?? 0.1;
        $topK = $options['topK'] ?? 5;
        $expandSections = $options['expandSections'] ?? true;
        $maxDepth = $options['maxDepth'] ?? 2;
        
        $startTime = microtime(true);
        $totalTokens = 0;
        $results = [];
        
        // 1. 쿼리 의도 분류
        $intent = $this->scorer->classifyIntent($query);
        $sectionPriority = $this->scorer->getSectionPriority($intent);
        
        $this->log('intent_classified', [
            'query' => $query,
            'intent' => $intent,
            'section_priority' => $sectionPriority
        ]);
        
        // 2. 모든 홀론의 W_summary로 연관성 점수 계산
        $rankedHolons = $this->scorer->rankHolons(
            $query, 
            $this->index['holons'],
            $threshold,
            $topK
        );
        
        $this->log('holons_ranked', [
            'total_holons' => count($this->index['holons']),
            'above_threshold' => count($rankedHolons)
        ]);
        
        // 3. 상위 홀론들의 관련 섹션 로드
        foreach ($rankedHolons as $holonInfo) {
            $holonId = $holonInfo['holon_id'];
            $holonResult = [
                'holon_id' => $holonId,
                'score' => $holonInfo['score'],
                'title' => $holonInfo['title'],
                'W_summary' => $holonInfo['W_summary'],
                'sections_loaded' => [],
                'tokens_used' => 0
            ];
            
            // 우선순위에 따라 섹션 로드
            if ($expandSections) {
                foreach ($sectionPriority as $section) {
                    if ($totalTokens >= $this->maxTokens) {
                        $holonResult['truncated'] = true;
                        break;
                    }
                    
                    $sectionData = $this->reader->readSectionLines($holonId, $section);
                    
                    if ($sectionData['success']) {
                        $sectionTokens = $sectionData['estimated_tokens'];
                        
                        if ($totalTokens + $sectionTokens <= $this->maxTokens) {
                            $holonResult['sections_loaded'][$section] = [
                                'content' => $sectionData['content'],
                                'tokens' => $sectionTokens
                            ];
                            $holonResult['tokens_used'] += $sectionTokens;
                            $totalTokens += $sectionTokens;
                        } else {
                            // 토큰 한도 초과, 요약만 저장
                            $holonResult['sections_loaded'][$section] = [
                                'summary_only' => true,
                                'tokens_needed' => $sectionTokens
                            ];
                        }
                    }
                }
            }
            
            $this->activatedHolons[$holonId] = $holonResult;
            $results[] = $holonResult;
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->log('activation_complete', [
            'holons_activated' => count($results),
            'total_tokens' => $totalTokens,
            'duration_ms' => $duration
        ]);
        
        return [
            'success' => true,
            'query' => $query,
            'intent' => $intent,
            'activated_holons' => $results,
            'stats' => [
                'total_tokens' => $totalTokens,
                'max_tokens' => $this->maxTokens,
                'token_usage_pct' => round(($totalTokens / $this->maxTokens) * 100, 1),
                'holons_scanned' => count($this->index['holons']),
                'holons_activated' => count($results),
                'duration_ms' => $duration
            ],
            'section_priority' => $sectionPriority
        ];
    }
    
    /**
     * 특정 좌표로 드릴다운
     */
    public function drillDown($coordinate) {
        $this->log('drill_down', ['coordinate' => $coordinate]);
        
        $result = $this->reader->read($coordinate);
        
        if ($result['success']) {
            $tokens = $result['meta']['estimated_tokens'];
            return [
                'success' => true,
                'coordinate' => $coordinate,
                'data' => $result['data'],
                'tokens' => $tokens
            ];
        }
        
        return $result;
    }
    
    /**
     * 연결된 홀론으로 전파
     */
    public function propagate($holonId, $depth = 1) {
        if ($depth > 3) {
            return ['propagated' => []];
        }
        
        if (!isset($this->index['holons'][$holonId])) {
            return ['propagated' => []];
        }
        
        $connections = $this->index['holons'][$holonId]['connections'];
        $propagated = [];
        
        // 연결된 홀론들의 W_summary 가져오기
        $related = array_merge(
            $connections['children'] ?? [],
            $connections['related'] ?? [],
            $connections['outputs'] ?? []
        );
        
        foreach ($related as $relatedId) {
            if (isset($this->index['holons'][$relatedId]) && 
                !isset($this->activatedHolons[$relatedId])) {
                $propagated[] = [
                    'holon_id' => $relatedId,
                    'W_summary' => $this->index['holons'][$relatedId]['W_summary'],
                    'connection_type' => 'related',
                    'depth' => $depth
                ];
            }
        }
        
        $this->log('propagated', [
            'from' => $holonId,
            'to' => array_column($propagated, 'holon_id'),
            'depth' => $depth
        ]);
        
        return ['propagated' => $propagated];
    }
    
    /**
     * 활성화된 홀론 요약
     */
    public function getSummary() {
        return [
            'activated_count' => count($this->activatedHolons),
            'holons' => array_map(function($h) {
                return [
                    'holon_id' => $h['holon_id'],
                    'score' => $h['score'],
                    'tokens_used' => $h['tokens_used']
                ];
            }, $this->activatedHolons),
            'total_tokens' => array_sum(array_column($this->activatedHolons, 'tokens_used')),
            'activation_log' => $this->activationLog
        ];
    }
    
    /**
     * 로그 기록
     */
    private function log($event, $data) {
        $this->activationLog[] = [
            'event' => $event,
            'data' => $data,
            'timestamp' => microtime(true)
        ];
    }
    
    /**
     * 상태 초기화
     */
    public function reset() {
        $this->activatedHolons = [];
        $this->activationLog = [];
    }
}

