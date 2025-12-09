<?php
/**
 * 연관성 점수 계산기
 * 쿼리와 홀론 W_summary 간 유사도 계산
 * 
 * 파일: alt42/orchestration/Holarchy/2 Neural/activation/relevance_scorer.php
 */

class RelevanceScorer {
    private $stopWords = [
        '의', '를', '을', '이', '가', '에', '에서', '으로', '로', '와', '과',
        '은', '는', '도', '만', '까지', '부터', '에게', '한테', '께',
        'the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been',
        'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'from'
    ];
    
    /**
     * 텍스트를 토큰화
     */
    public function tokenize($text) {
        // 소문자 변환 및 특수문자 제거
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // 공백으로 분리
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // 불용어 제거
        $tokens = array_filter($tokens, function($token) {
            return !in_array($token, $this->stopWords) && mb_strlen($token) > 1;
        });
        
        return array_values($tokens);
    }
    
    /**
     * 두 텍스트 간 Jaccard 유사도 계산
     */
    public function jaccardSimilarity($text1, $text2) {
        $tokens1 = array_unique($this->tokenize($text1));
        $tokens2 = array_unique($this->tokenize($text2));
        
        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }
        
        $intersection = array_intersect($tokens1, $tokens2);
        $union = array_unique(array_merge($tokens1, $tokens2));
        
        return count($intersection) / count($union);
    }
    
    /**
     * 키워드 매칭 점수 계산
     */
    public function keywordMatchScore($query, $keywords) {
        if (empty($keywords)) {
            return 0.0;
        }
        
        $queryTokens = $this->tokenize($query);
        $matchCount = 0;
        
        foreach ($keywords as $keyword) {
            $keywordTokens = $this->tokenize($keyword);
            foreach ($keywordTokens as $kt) {
                if (in_array($kt, $queryTokens)) {
                    $matchCount++;
                }
            }
        }
        
        return min(1.0, $matchCount / max(1, count($keywords)));
    }
    
    /**
     * 쿼리와 홀론 간 종합 연관성 점수 계산
     * 
     * @param string $query 검색 쿼리
     * @param array $holonInfo 홀론 정보 (W_summary, keywords 포함)
     * @return float 0~1 사이 점수
     */
    public function score($query, $holonInfo) {
        $wSummary = $holonInfo['W_summary'] ?? '';
        $keywords = $holonInfo['keywords'] ?? [];
        $title = $holonInfo['title'] ?? '';
        
        // 1. W_summary와 쿼리 유사도 (가중치 0.4)
        $summarySim = $this->jaccardSimilarity($query, $wSummary);
        
        // 2. 키워드 매칭 (가중치 0.35)
        $keywordScore = $this->keywordMatchScore($query, $keywords);
        
        // 3. 제목 매칭 (가중치 0.25)
        $titleSim = $this->jaccardSimilarity($query, $title);
        
        // 가중 평균
        $score = ($summarySim * 0.4) + ($keywordScore * 0.35) + ($titleSim * 0.25);
        
        return round($score, 4);
    }
    
    /**
     * 여러 홀론 중 상위 K개 선택
     * 
     * @param string $query 검색 쿼리
     * @param array $holons 홀론 정보 배열 (holon_id => info)
     * @param float $threshold 최소 점수 임계값
     * @param int $topK 상위 K개 선택
     * @return array 정렬된 결과
     */
    public function rankHolons($query, $holons, $threshold = 0.1, $topK = 10) {
        $scores = [];
        
        foreach ($holons as $holonId => $info) {
            $score = $this->score($query, $info);
            
            if ($score >= $threshold) {
                $scores[] = [
                    'holon_id' => $holonId,
                    'score' => $score,
                    'title' => $info['title'] ?? $holonId,
                    'W_summary' => $info['W_summary'] ?? '',
                    'keywords' => $info['keywords'] ?? []
                ];
            }
        }
        
        // 점수 내림차순 정렬
        usort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // 상위 K개만 반환
        return array_slice($scores, 0, $topK);
    }
    
    /**
     * 쿼리 의도 분류
     */
    public function classifyIntent($query) {
        $queryLower = mb_strtolower($query);
        
        $patterns = [
            'error' => ['오류', '에러', '버그', 'error', 'bug', '문제', '안됨', '실패'],
            'howto' => ['방법', '어떻게', 'how', '하는법', '설정', '사용'],
            'feature' => ['기능', '추가', '개선', 'feature', '구현'],
            'status' => ['상태', '현황', '진행', 'status', '완료'],
            'explain' => ['설명', '무엇', 'what', '정의', '개념']
        ];
        
        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($queryLower, $kw) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * 의도에 따라 우선 탐색할 섹션 결정
     */
    public function getSectionPriority($intent) {
        $priorities = [
            'error' => ['X', 'E', 'R', 'P'],      // 문맥 → 실행 → 성찰
            'howto' => ['P', 'E', 'S', 'X'],      // 절차 → 실행 → 구조
            'feature' => ['W', 'E', 'P', 'X'],    // 세계관 → 실행 → 절차
            'status' => ['X', 'P', 'E', 'R'],     // 문맥 → 절차 → 실행
            'explain' => ['W', 'S', 'X', 'A'],    // 세계관 → 구조 → 문맥
            'general' => ['W', 'X', 'E', 'P']     // 세계관 → 문맥 → 실행
        ];
        
        return $priorities[$intent] ?? $priorities['general'];
    }
}

