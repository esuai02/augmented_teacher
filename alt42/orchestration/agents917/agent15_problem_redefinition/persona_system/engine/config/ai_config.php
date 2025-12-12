<?php
/**
 * AI Configuration for Agent15 Problem Redefinition
 *
 * OpenAI API 및 AI 관련 설정
 * 문제 재정의에 특화된 프롬프트 및 파라미터
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 */

return [
    /**
     * OpenAI API 설정
     */
    'openai' => [
        // API 키 (환경변수에서 로드하거나 직접 설정)
        'api_key' => getenv('OPENAI_API_KEY') ?: '',

        // API 엔드포인트
        'api_endpoint' => 'https://api.openai.com/v1/chat/completions',

        // 사용할 모델
        'model' => 'gpt-4o-mini',

        // 기본 파라미터
        'default_params' => [
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        ],

        // 타임아웃 (초)
        'timeout' => 30,

        // 재시도 설정
        'retry' => [
            'max_attempts' => 3,
            'delay_ms' => 1000
        ]
    ],

    /**
     * NLU (자연어 이해) 설정
     */
    'nlu' => [
        // AI 기반 분석 활성화 여부
        'ai_enabled' => true,

        // AI 분석 임계값 (규칙 기반으로 해결 못할 때 AI 사용)
        'ai_threshold' => 0.5,

        // 의도 분석용 프롬프트
        'intent_prompt' => <<<'PROMPT'
다음 학생의 메시지를 분석하여 의도를 파악해주세요.

학생 메시지: "{message}"

다음 중 가장 적합한 의도를 선택하고, 신뢰도(0-1)를 함께 반환해주세요:
- help_request: 도움 요청
- problem_report: 문제 상황 보고
- clarification: 설명 요청
- confirmation: 확인/동의
- rejection: 거부/반대
- emotional_expression: 감정 표현
- progress_update: 진행 상황 업데이트
- question: 질문

JSON 형식으로 응답:
{"intent": "의도", "confidence": 0.0, "keywords": ["키워드1", "키워드2"]}
PROMPT,

        // 감정 분석용 프롬프트
        'emotion_prompt' => <<<'PROMPT'
다음 학생의 메시지에서 감정 상태를 분석해주세요.

학생 메시지: "{message}"

다음 감정 중 해당되는 것과 강도(0-1)를 반환해주세요:
- frustration: 좌절감
- anxiety: 불안
- confusion: 혼란
- boredom: 지루함
- hopelessness: 무력감
- motivation: 동기 부여됨
- confidence: 자신감
- neutral: 중립

JSON 형식으로 응답:
{"emotion": "감정", "intensity": 0.0, "indicators": ["지표1", "지표2"]}
PROMPT
    ],

    /**
     * 문제 재정의 프롬프트
     */
    'problem_redefinition' => [
        // 근본 원인 분석 프롬프트
        'cause_analysis_prompt' => <<<'PROMPT'
학생의 학습 문제를 분석하고 근본 원인을 파악해주세요.

## 학생 정보
- 이름: {student_name}
- 학습 수준: {student_level}

## 현재 상황 (트리거: {trigger_scenario})
{situation_description}

## 수집된 데이터
- 성과 추이: {performance_data}
- 학습 패턴: {study_patterns}
- 감정 로그: {emotion_logs}

## 분석 요청
다음 4가지 레이어에서 가능한 원인을 분석해주세요:

1. 인지적 요인 (cognitive): 개념 이해, 선수학습, 문제해결 전략
2. 행동적 요인 (behavioral): 학습 루틴, 시간 관리, 집중력
3. 동기적 요인 (motivational): 학습 의욕, 목표 인식, 자기효능감
4. 환경적 요인 (environmental): 학습 환경, 지원 체계, 외부 요인

JSON 형식으로 응답:
{
    "cognitive": {"factors": [{"type": "타입", "description": "설명", "severity": 0.0}], "confidence": 0.0},
    "behavioral": {"factors": [...], "confidence": 0.0},
    "motivational": {"factors": [...], "confidence": 0.0},
    "environmental": {"factors": [...], "confidence": 0.0},
    "primary_cause": "가장 핵심적인 원인",
    "redefined_problem": "재정의된 문제 설명"
}
PROMPT,

        // 조치안 생성 프롬프트
        'action_plan_prompt' => <<<'PROMPT'
분석된 원인에 대한 맞춤형 조치안을 생성해주세요.

## 학생 정보
- 이름: {student_name}
- 학습 수준: {student_level}
- 페르소나 특성: {persona_characteristics}

## 분석된 원인
{cause_analysis}

## 재정의된 문제
{redefined_problem}

## 요청
학생의 특성을 고려하여 실행 가능하고 구체적인 조치안 3개를 제안해주세요.
각 조치안에는 다음 정보를 포함:
- 제목
- 상세 설명
- 예상 소요 시간
- 우선순위 (긴급도)
- 구체적 실행 단계

JSON 형식으로 응답:
{
    "actions": [
        {
            "title": "조치 제목",
            "description": "상세 설명",
            "duration": "예상 소요",
            "urgency": 0.0,
            "steps": ["단계1", "단계2", "단계3"]
        }
    ],
    "overall_strategy": "전체 전략 요약"
}
PROMPT
    ],

    /**
     * 페르소나 관련 설정
     */
    'persona' => [
        // 페르소나 선택 프롬프트
        'selection_prompt' => <<<'PROMPT'
학생 상황에 가장 적합한 페르소나를 선택해주세요.

## 학생 상황
- 트리거: {trigger_scenario}
- 감정 상태: {emotion_state}
- 이전 페르소나: {previous_persona}

## 가용 페르소나 시리즈
- R-Series (인식형): 문제 상황 인식 및 공감
- A-Series (귀인형): 원인 분석 및 설명
- V-Series (검증형): 가설 검증 및 확인
- S-Series (솔루션형): 해결책 제시 및 실행 지원
- E-Series (정서형): 정서적 지원 및 동기 부여

각 시리즈의 세부 페르소나:
- R1, R2, R3: 인식 깊이별
- A1, A2, A3, A4: 귀인 스타일별
- V1, V2, V3, V4: 검증 방식별
- S1, S2, S3, S4: 솔루션 유형별
- E1, E2: 정서 지원 스타일별

JSON 형식으로 응답:
{"persona_id": "ID", "persona_name": "이름", "confidence": 0.0, "rationale": "선택 이유"}
PROMPT,

        // 페르소나별 특성
        'characteristics' => [
            'R1' => ['empathetic', 'observant', 'non_judgmental'],
            'R2' => ['analytical', 'curious', 'supportive'],
            'R3' => ['proactive', 'insightful', 'encouraging'],
            'A1' => ['logical', 'systematic', 'clear'],
            'A2' => ['patient', 'thorough', 'explanatory'],
            'A3' => ['investigative', 'precise', 'evidence_based'],
            'A4' => ['holistic', 'contextual', 'connecting'],
            'V1' => ['methodical', 'careful', 'confirming'],
            'V2' => ['questioning', 'critical', 'verifying'],
            'V3' => ['testing', 'experimental', 'iterative'],
            'V4' => ['comprehensive', 'validating', 'conclusive'],
            'S1' => ['practical', 'action_oriented', 'specific'],
            'S2' => ['creative', 'alternative_thinking', 'flexible'],
            'S3' => ['structured', 'step_by_step', 'measurable'],
            'S4' => ['supportive', 'collaborative', 'adaptive'],
            'E1' => ['warm', 'empathetic', 'reassuring'],
            'E2' => ['motivating', 'encouraging', 'positive']
        ]
    ],

    /**
     * 응답 생성 설정
     */
    'response' => [
        // 최대 응답 길이
        'max_length' => 2000,

        // 톤 조정 설정
        'tone_adjustment' => [
            'avoidant' => ['soft', 'gentle', 'non_threatening'],
            'defensive' => ['collaborative', 'choice_offering', 'respectful'],
            'anxious' => ['reassuring', 'calming', 'supportive'],
            'confident' => ['direct', 'challenging', 'growth_oriented']
        ],

        // 응답 스타일 프롬프트
        'style_prompt' => <<<'PROMPT'
다음 조건에 맞게 학생에게 전달할 응답을 작성해주세요.

## 페르소나: {persona_name}
- 특성: {persona_characteristics}

## 학생 특성
- 이름: {student_name}
- 톤 조정: {tone_requirements}

## 전달 내용
{content}

## 작성 요구사항
1. 학생의 특성에 맞는 톤으로 작성
2. 공감과 이해를 먼저 표현
3. 구체적이고 실행 가능한 조언 제공
4. 긍정적이고 격려하는 마무리

최대 {max_length}자 이내로 작성해주세요.
PROMPT
    ],

    /**
     * 캐시 설정
     */
    'cache' => [
        // 캐시 활성화
        'enabled' => true,

        // 기본 TTL (초)
        'default_ttl' => 3600,

        // 캐시 디렉토리
        'directory' => sys_get_temp_dir() . '/agent15_ai_cache',

        // 캐시 키 prefix
        'prefix' => 'agent15_ai_'
    ],

    /**
     * 로깅 설정
     */
    'logging' => [
        // AI 요청/응답 로깅
        'log_requests' => true,

        // 에러 로깅
        'log_errors' => true,

        // 로그 레벨 (debug, info, warning, error)
        'level' => 'info'
    ],

    /**
     * 폴백 설정
     */
    'fallback' => [
        // AI 실패 시 규칙 기반 폴백 활성화
        'enabled' => true,

        // 기본 응답 메시지
        'default_messages' => [
            'error' => '죄송합니다. 현재 분석 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
            'timeout' => '응답 시간이 초과되었습니다. 다시 시도해주세요.',
            'unavailable' => '현재 서비스를 이용할 수 없습니다.'
        ]
    ]
];
