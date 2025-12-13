<?php
/**
 * 대화 분석기
 * 선생님-학생 대화를 분석하여 학습 맥락 추출
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class DialogueAnalyzer {
    
    /**
     * 대화 내용 분석
     * 
     * @param string $textContent 텍스트 내용
     * @param string $imageData 이미지 데이터 (base64)
     * @return array 분석 결과
     */
    public function analyze($textContent, $imageData = '') {
        $analysis = [
            'unit' => $this->extractUnit($textContent),
            'concepts' => $this->extractConcepts($textContent),
            'problems' => $this->extractProblems($textContent),
            'teaching_methods' => $this->extractTeachingMethods($textContent),
            'student_responses' => $this->extractStudentResponses($textContent),
            'difficulty_level' => $this->assessDifficulty($textContent),
            'prerequisites' => $this->identifyPrerequisites($textContent),
            'learning_sequence' => $this->identifyLearningSequence($textContent)
        ];

        return $analysis;
    }

    /**
     * 단원 추출
     */
    private function extractUnit($text) {
        $units = [
            '이차방정식' => 'equations',
            '함수' => 'functions',
            '미분' => 'differentiation',
            '적분' => 'integration',
            '평면도형' => 'plane_figures',
            '입체도형' => 'solid_figures'
        ];

        foreach ($units as $korean => $english) {
            if (strpos($text, $korean) !== false) {
                return [
                    'korean' => $korean,
                    'code' => $english,
                    'confidence' => 0.9
                ];
            }
        }

        return null;
    }

    /**
     * 개념 추출
     */
    private function extractConcepts($text) {
        $concepts = [];
        
        // 이차방정식 관련 개념
        if (strpos($text, '이차방정식') !== false) {
            if (strpos($text, '근의 분리') !== false) {
                $concepts[] = [
                    'name' => '근의 분리',
                    'type' => 'problem_type',
                    'description' => '이차방정식의 두 근이 특정 조건을 만족하는 경우의 범위 구하기'
                ];
            }
            if (strpos($text, '판별식') !== false) {
                $concepts[] = [
                    'name' => '판별식',
                    'type' => 'concept',
                    'description' => '이차방정식의 근의 개수를 판별하는 식'
                ];
            }
            if (strpos($text, '그래프') !== false || strpos($text, '함수') !== false) {
                $concepts[] = [
                    'name' => '이차함수와 그래프',
                    'type' => 'concept',
                    'description' => '이차방정식을 함수로 표현하여 그래프로 해석'
                ];
            }
        }

        return $concepts;
    }

    /**
     * 문제 추출
     */
    private function extractProblems($text) {
        $problems = [];
        
        // 문제 패턴 찾기
        preg_match_all('/문제[가-힣]*[.:]\s*([^선생님|학생]*?)(?=선생님|학생|$)/u', $text, $matches);
        
        foreach ($matches[1] as $match) {
            $problemText = trim($match);
            if (strlen($problemText) > 10) {
                $problems[] = [
                    'text' => $problemText,
                    'type' => $this->classifyProblemType($problemText),
                    'difficulty' => $this->assessProblemDifficulty($problemText)
                ];
            }
        }

        return $problems;
    }

    /**
     * 교수법 추출
     */
    private function extractTeachingMethods($text) {
        $methods = [];

        // 그래프 활용
        if (strpos($text, '그래프') !== false) {
            $methods[] = [
                'method' => '시각화',
                'description' => '그래프를 활용한 개념 설명',
                'frequency' => substr_count($text, '그래프')
            ];
        }

        // 단계별 설명
        if (strpos($text, '첫 번째') !== false || strpos($text, '두 번째') !== false) {
            $methods[] = [
                'method' => '단계별 설명',
                'description' => '문제를 단계별로 나누어 설명',
                'frequency' => substr_count($text, '첫 번째') + substr_count($text, '두 번째')
            ];
        }

        // 질문-답변 형식
        if (preg_match_all('/선생님[^학생]*?학생[^선생님]*?/u', $text)) {
            $methods[] = [
                'method' => '대화형 설명',
                'description' => '질문-답변을 통한 상호작용',
                'frequency' => preg_match_all('/선생님[^학생]*?학생[^선생님]*?/u', $text)
            ];
        }

        return $methods;
    }

    /**
     * 학생 응답 추출
     */
    private function extractStudentResponses($text) {
        $responses = [];
        
        preg_match_all('/학생[^선생님]*?/u', $text, $matches);
        
        foreach ($matches[0] as $response) {
            $cleanResponse = trim(str_replace('학생:', '', $response));
            if (strlen($cleanResponse) > 5) {
                $responses[] = [
                    'text' => $cleanResponse,
                    'understanding_level' => $this->assessUnderstanding($cleanResponse),
                    'confidence' => $this->assessConfidence($cleanResponse)
                ];
            }
        }

        return $responses;
    }

    /**
     * 난이도 평가
     */
    private function assessDifficulty($text) {
        $difficulty = 3; // 기본값: 중간
        
        // 고난이도 키워드
        $hardKeywords = ['근의 분리', '복잡한', '특수한', '연립'];
        foreach ($hardKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $difficulty = min(5, $difficulty + 1);
            }
        }

        // 저난이도 키워드
        $easyKeywords = ['기본', '간단한', '쉬운'];
        foreach ($easyKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $difficulty = max(1, $difficulty - 1);
            }
        }

        return $difficulty;
    }

    /**
     * 선행 개념 식별
     */
    private function identifyPrerequisites($text) {
        $prerequisites = [];

        if (strpos($text, '이차방정식') !== false) {
            $prerequisites[] = '일차방정식';
            $prerequisites[] = '이차함수';
            if (strpos($text, '그래프') !== false) {
                $prerequisites[] = '평면좌표';
            }
        }

        return $prerequisites;
    }

    /**
     * 학습 순서 식별
     */
    private function identifyLearningSequence($text) {
        $sequence = [];

        // 단계별 순서 추출
        if (preg_match_all('/(\d+)단계|(\d+)번째|첫\s*번째|두\s*번째/u', $text, $matches)) {
            foreach ($matches[0] as $step) {
                $sequence[] = $step;
            }
        }

        return $sequence;
    }

    /**
     * 문제 유형 분류
     */
    private function classifyProblemType($text) {
        if (strpos($text, '근') !== false && strpos($text, '범위') !== false) {
            return '근의 분리';
        }
        if (strpos($text, '그래프') !== false) {
            return '그래프 활용';
        }
        return '일반';
    }

    /**
     * 문제 난이도 평가
     */
    private function assessProblemDifficulty($text) {
        return $this->assessDifficulty($text);
    }

    /**
     * 이해도 평가
     */
    private function assessUnderstanding($response) {
        $positiveKeywords = ['맞아요', '좋아요', '이해', '알겠', '네'];
        $negativeKeywords = ['모르', '어려', '혼란', '음...'];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveKeywords as $keyword) {
            if (strpos($response, $keyword) !== false) {
                $positiveCount++;
            }
        }
        
        foreach ($negativeKeywords as $keyword) {
            if (strpos($response, $keyword) !== false) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'high';
        } elseif ($negativeCount > $positiveCount) {
            return 'low';
        }
        return 'medium';
    }

    /**
     * 자신감 평가
     */
    private function assessConfidence($response) {
        $confidentKeywords = ['확실히', '완벽', '정답', '맞아요'];
        $uncertainKeywords = ['음...', '아마', '생각', '모르'];
        
        foreach ($confidentKeywords as $keyword) {
            if (strpos($response, $keyword) !== false) {
                return 'high';
            }
        }
        
        foreach ($uncertainKeywords as $keyword) {
            if (strpos($response, $keyword) !== false) {
                return 'low';
            }
        }

        return 'medium';
    }
}

