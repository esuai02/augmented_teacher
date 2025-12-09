<?php
/**
 * 질문 생성기
 * 대화 분석 결과를 바탕으로 포괄적 질문과 세부 질문 생성
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class QuestionGenerator {
    
    /**
     * 포괄적 질문 생성
     * Agent01의 포괄형 질문 구조 적용
     * 
     * @param array $analysis 대화 분석 결과
     * @return array 포괄적 질문 목록
     */
    public function generateComprehensiveQuestions($analysis) {
        $questions = [];

        // 질문 1: 단원 학습 시작 전략
        if ($analysis['unit']) {
            $questions[] = [
                'id' => 'Q1',
                'type' => 'comprehensive',
                'question' => "이 학생이 {$analysis['unit']['korean']} 단원을 학습하기 시작할 때, 어떤 순서와 방법으로 접근해야 할까?",
                'context' => [
                    'unit' => $analysis['unit'],
                    'prerequisites' => $analysis['prerequisites'],
                    'difficulty' => $analysis['difficulty_level']
                ],
                'focus_areas' => [
                    '선행 단원 완료 여부 확인',
                    '단원 난이도와 학생 수준 매칭',
                    '학습 스타일 기반 접근 방법',
                    '단원 학습 계획 수립'
                ]
            ];
        }

        // 질문 2: 개념 학습 최적화
        if (!empty($analysis['concepts'])) {
            $questions[] = [
                'id' => 'Q2',
                'type' => 'comprehensive',
                'question' => "이 학생의 {$analysis['unit']['korean']} 단원 학습을 어떤 방향으로 최적화해야 할까?",
                'context' => [
                    'concepts' => $analysis['concepts'],
                    'teaching_methods' => $analysis['teaching_methods'],
                    'student_responses' => $analysis['student_responses']
                ],
                'focus_areas' => [
                    '개념 이해도 기반 학습 순서 조정',
                    '문제 유형 비중 최적화',
                    '학습 속도 조절',
                    '취약 개념 집중 학습'
                ]
            ];
        }

        // 질문 3: 학습 성장 전략
        if (!empty($analysis['problems'])) {
            $questions[] = [
                'id' => 'Q3',
                'type' => 'comprehensive',
                'question' => "이 학생이 {$analysis['unit']['korean']} 단원을 완전히 마스터하기 위해 어떤 부분을 특히 신경 써야 할까?",
                'context' => [
                    'problems' => $analysis['problems'],
                    'learning_sequence' => $analysis['learning_sequence'],
                    'difficulty' => $analysis['difficulty_level']
                ],
                'focus_areas' => [
                    '단원 마스터리 리스크 예측',
                    '후속 단원 연결성 고려',
                    '장기 학습 계획 수립',
                    '취약점 보완 전략'
                ]
            ];
        }

        return $questions;
    }

    /**
     * 세부 질문 생성
     * 
     * @param array $analysis 대화 분석 결과
     * @return array 세부 질문 목록
     */
    public function generateDetailedQuestions($analysis) {
        $questions = [];

        // 개념별 세부 질문
        foreach ($analysis['concepts'] as $concept) {
            $questions[] = [
                'id' => 'DQ_' . uniqid(),
                'type' => 'detailed',
                'category' => 'concept',
                'question' => "{$concept['name']} 개념을 어떻게 설명해야 할까?",
                'concept' => $concept,
                'suggested_approach' => $this->suggestConceptApproach($concept)
            ];
        }

        // 문제별 세부 질문
        foreach ($analysis['problems'] as $index => $problem) {
            $questions[] = [
                'id' => 'DQ_PROBLEM_' . ($index + 1),
                'type' => 'detailed',
                'category' => 'problem',
                'question' => "이 문제를 어떤 순서로 풀이해야 할까?",
                'problem' => $problem,
                'suggested_steps' => $this->suggestProblemSteps($problem)
            ];
        }

        // 교수법별 세부 질문
        foreach ($analysis['teaching_methods'] as $method) {
            $questions[] = [
                'id' => 'DQ_METHOD_' . uniqid(),
                'type' => 'detailed',
                'category' => 'teaching_method',
                'question' => "{$method['method']} 방법을 어떻게 활용해야 할까?",
                'method' => $method,
                'suggested_application' => $this->suggestMethodApplication($method)
            ];
        }

        // 학생 응답 기반 질문
        foreach ($analysis['student_responses'] as $index => $response) {
            if ($response['understanding_level'] === 'low') {
                $questions[] = [
                    'id' => 'DQ_RESPONSE_' . ($index + 1),
                    'type' => 'detailed',
                    'category' => 'remediation',
                    'question' => "학생이 이해하지 못한 부분을 어떻게 보완해야 할까?",
                    'response' => $response,
                    'suggested_remediation' => $this->suggestRemediation($response)
                ];
            }
        }

        return $questions;
    }

    /**
     * 개념 접근 방법 제안
     */
    private function suggestConceptApproach($concept) {
        $approaches = [];

        if ($concept['type'] === 'problem_type') {
            $approaches[] = '문제 유형의 특징과 해결 전략 설명';
            $approaches[] = '유사 문제 예시 제공';
            $approaches[] = '단계별 풀이 과정 안내';
        } elseif ($concept['type'] === 'concept') {
            $approaches[] = '기본 정의와 성질 설명';
            $approaches[] = '시각적 예시 제공';
            $approaches[] = '연습 문제를 통한 이해도 확인';
        }

        return $approaches;
    }

    /**
     * 문제 풀이 단계 제안
     */
    private function suggestProblemSteps($problem) {
        $steps = [];

        if ($problem['type'] === '근의 분리') {
            $steps[] = '1. 함수로 표현하기';
            $steps[] = '2. 그래프의 형태 파악하기';
            $steps[] = '3. 조건을 함수값으로 변환하기';
            $steps[] = '4. 판별식 확인하기';
            $steps[] = '5. 조건들을 종합하여 범위 구하기';
        } elseif ($problem['type'] === '그래프 활용') {
            $steps[] = '1. 함수 식 확인하기';
            $steps[] = '2. 그래프 그리기';
            $steps[] = '3. 조건에 맞는 그래프 위치 확인하기';
            $steps[] = '4. 함수값 계산하기';
        } else {
            $steps[] = '1. 문제 이해하기';
            $steps[] = '2. 관련 개념 확인하기';
            $steps[] = '3. 풀이 방법 선택하기';
            $steps[] = '4. 단계별 계산하기';
            $steps[] = '5. 답 확인하기';
        }

        return $steps;
    }

    /**
     * 교수법 적용 방법 제안
     */
    private function suggestMethodApplication($method) {
        $applications = [];

        if ($method['method'] === '시각화') {
            $applications[] = '그래프를 그려서 개념 설명';
            $applications[] = '그래프의 특징을 시각적으로 보여주기';
            $applications[] = '그래프를 통한 조건 해석';
        } elseif ($method['method'] === '단계별 설명') {
            $applications[] = '문제를 작은 단계로 나누기';
            $applications[] = '각 단계별로 이해도 확인';
            $applications[] = '단계 간 연결성 설명';
        } elseif ($method['method'] === '대화형 설명') {
            $applications[] = '질문을 통한 이해도 확인';
            $applications[] = '학생 응답에 따른 피드백 제공';
            $applications[] = '상호작용을 통한 개념 정착';
        }

        return $applications;
    }

    /**
     * 보완 방법 제안
     */
    private function suggestRemediation($response) {
        $remediations = [];

        if ($response['understanding_level'] === 'low') {
            $remediations[] = '기본 개념부터 다시 설명';
            $remediations[] = '더 쉬운 예시 제공';
            $remediations[] = '단계별로 천천히 설명';
            $remediations[] = '시각적 자료 활용';
        }

        if ($response['confidence'] === 'low') {
            $remediations[] = '긍정적 피드백 제공';
            $remediations[] = '작은 성공 경험 제공';
            $remediations[] = '자신감 회복을 위한 쉬운 문제 제시';
        }

        return $remediations;
    }
}

