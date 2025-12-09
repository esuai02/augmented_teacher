# 룰 기반 답변 생성 시스템 검토

**작성일**: 2025-01-27  
**목적**: GPT API 없이 룰만으로 답을 생성하는 방법 검토 및 구현 방안 제시  
**관련 파일**: `alt42/orchestration/agents/`

---

## 📋 요약

현재 시스템은 **룰 엔진 기반 답변 생성**의 기반이 이미 구축되어 있으나, 실제 사용 단계로 연결되지 않은 상태입니다. 이 문서는 LLM 기능을 룰 기반으로 완전히 대체하는 방법을 검토합니다.

---

## 🔍 현재 상태 분석

### 1. 이미 구현된 구성 요소

#### ✅ 룰 엔진 (`onboarding_rule_engine.py`)
- **위치**: `agent01_onboarding/rules/onboarding_rule_engine.py`
- **기능**:
  - YAML 룰 파일 파싱
  - 다양한 operator 지원 (==, <=, in, matches, contains 등)
  - 중첩 필드 접근 (goals.long_term)
  - 조건 평가 및 액션 실행
  - 우선순위 기반 룰 매칭

#### ✅ 룰 정의 파일 (`rules.yaml`)
- **위치**: `agent01_onboarding/rules/rules.yaml`
- **구조**:
  ```yaml
  rules:
    - rule_id: "S0_R1_math_learning_style_collection"
      priority: 99
      conditions:
        - field: "math_learning_style"
          operator: "=="
          value: null
      action:
        - "collect_info: 'math_learning_style'"
        - "question: '수학 문제를 풀 때 어떤 방식이 편하신가요?...'"
        - "display_message: '...'"
  ```

#### ✅ Gendata 생성 스크립트
- **위치**: `create_gendata.py`, `create_gendata.ps1`
- **기능**: 여러 룰 파일들(mission.md, questions.md, metadata.md, rules.yaml)을 통합하여 `gendata.md` 생성

#### ✅ 온톨로지 시스템
- **위치**: `ontology_agents/`
- **구조**: JSON-LD 기반 다층 온톨로지 시스템
- **기능**: 구조화된 지식 표현 및 추론

### 2. 미완성/미연결된 부분

#### ❌ 룰 엔진과 실제 시스템 미연결
- `agent.php`에서 룰 엔진 호출 없음
- 실제 질문/답변 생성 로직 미연결
- 액션 처리 로직 부재

#### ❌ 컨텐츠 생성 로직 부재
- 룰의 `action` 필드에 정의된 액션들이 실제로 실행되지 않음
- 템플릿 기반 답변 생성 시스템 없음
- 동적 컨텐츠 생성 로직 없음

#### ❌ 온톨로지와 룰 엔진 통합 부재
- 온톨로지 데이터를 룰 엔진이 활용하지 않음
- 구조화된 데이터를 온톨로지처럼 동작하도록 만드는 메커니즘 없음

---

## 🎯 LLM 기능을 룰 기반으로 대체하는 방법

### 1. Gendata 만들기 (✅ 이미 구현됨)

**현재 상태**: 
- `create_gendata.py`로 각 에이전트의 `rules/` 폴더에서 파일들을 통합하여 `gendata.md` 생성
- `mission.md`, `questions.md`, `metadata.md`, `rules.yaml` 통합

**개선 방안**:
```python
# create_gendata.py 확장
def create_structured_gendata(agent_dir):
    """
    구조화된 gendata 생성 (JSON-LD 형식)
    """
    gendata = {
        "@context": "https://schema.org",
        "@type": "AgentRuleSet",
        "agent_id": agent_dir.name,
        "mission": load_markdown("mission.md"),
        "questions": parse_questions("questions.md"),
        "metadata": parse_metadata("metadata.md"),
        "rules": load_yaml_rules("rules.yaml"),
        "ontology_mapping": map_to_ontology()  # 온톨로지 매핑 추가
    }
    return gendata
```

### 2. 결과를 컨텐츠화 (❌ 구현 필요)

**목표**: 룰 엔진의 `action` 필드를 실제 컨텐츠로 변환

#### 2.1 템플릿 기반 답변 생성

```php
<?php
// services/content_generator.php

class ContentGenerator {
    private $templates = [];
    private $context = [];
    
    public function __construct($templates_dir) {
        $this->loadTemplates($templates_dir);
    }
    
    /**
     * 룰 액션을 컨텐츠로 변환
     */
    public function generateFromActions($actions, $context) {
        $this->context = $context;
        $content = [];
        
        foreach ($actions as $action) {
            $type = $this->parseActionType($action);
            
            switch ($type) {
                case 'question':
                    $content['question'] = $this->generateQuestion($action);
                    break;
                case 'display_message':
                    $content['message'] = $this->generateMessage($action);
                    break;
                case 'generate_description':
                    $content['description'] = $this->generateDescription($action);
                    break;
                case 'recommend_path':
                    $content['recommendation'] = $this->generateRecommendation($action);
                    break;
            }
        }
        
        return $content;
    }
    
    /**
     * 질문 생성 (템플릿 기반)
     */
    private function generateQuestion($action) {
        $questionTemplate = $this->extractQuestion($action);
        return $this->renderTemplate($questionTemplate, $this->context);
    }
    
    /**
     * 메시지 생성
     */
    private function generateMessage($action) {
        $messageTemplate = $this->extractMessage($action);
        return $this->renderTemplate($messageTemplate, $this->context);
    }
    
    /**
     * 설명 생성 (룰 기반)
     */
    private function generateDescription($action) {
        $descriptionType = $this->extractDescriptionType($action);
        
        // 룰 기반 설명 생성
        $rules = $this->getDescriptionRules($descriptionType);
        $description = $this->buildDescriptionFromRules($rules, $this->context);
        
        return $description;
    }
    
    /**
     * 추천 경로 생성
     */
    private function generateRecommendation($action) {
        $pathTemplate = $this->extractPath($action);
        $recommendation = $this->renderTemplate($pathTemplate, $this->context);
        
        // 온톨로지 기반 검증 및 보강
        $recommendation = $this->enrichWithOntology($recommendation);
        
        return $recommendation;
    }
    
    /**
     * 템플릿 렌더링 (변수 치환)
     */
    private function renderTemplate($template, $context) {
        $result = $template;
        
        // {변수명} 형식 치환
        preg_match_all('/\{([^}]+)\}/', $template, $matches);
        foreach ($matches[1] as $var) {
            $value = $this->getNestedValue($context, $var);
            $result = str_replace('{' . $var . '}', $value, $result);
        }
        
        return $result;
    }
}
```

#### 2.2 룰 기반 설명 생성

```php
<?php
// services/description_generator.php

class DescriptionGenerator {
    private $descriptionRules = [];
    
    /**
     * 룰 기반 설명 생성
     */
    public function generate($descriptionType, $context) {
        $rules = $this->getDescriptionRules($descriptionType);
        $parts = [];
        
        foreach ($rules as $rule) {
            if ($this->evaluateCondition($rule['condition'], $context)) {
                $parts[] = $this->renderDescriptionPart($rule['template'], $context);
            }
        }
        
        return $this->combineParts($parts);
    }
    
    /**
     * 설명 룰 예시
     */
    private function getDescriptionRules($type) {
        return [
            'complete_student_profile_summary_with_math_specialization' => [
                [
                    'condition' => ['math_level' => '!=', 'value' => null],
                    'template' => '학생의 수학 수준은 {math_level}입니다.'
                ],
                [
                    'condition' => ['math_confidence' => '<=', 'value' => 5],
                    'template' => '수학 자신감이 낮아 기초 개념 강화가 필요합니다.'
                ],
                [
                    'condition' => ['math_learning_style' => '==', 'value' => '계산형'],
                    'template' => '계산형 학습 스타일이므로 정확한 계산 연습을 중점적으로 진행합니다.'
                ]
            ]
        ][$type] ?? [];
    }
}
```

### 3. 구조적 데이터로 온톨로지처럼 동작 (❌ 구현 필요)

**목표**: 룰 엔진의 결과를 온톨로지 형식으로 변환하여 구조화된 데이터로 활용

#### 3.1 룰 결과를 JSON-LD로 변환

```php
<?php
// services/ontology_converter.php

class OntologyConverter {
    /**
     * 룰 엔진 결과를 JSON-LD 온톨로지 형식으로 변환
     */
    public function convertRuleResultToOntology($ruleResult, $context) {
        $ontology = [
            '@context' => [
                'schema' => 'https://schema.org/',
                'alt42' => 'https://alt42.org/ontology/'
            ],
            '@type' => 'alt42:AgentDecision',
            '@id' => 'decision:' . uniqid(),
            'schema:agent' => [
                '@type' => 'alt42:Agent',
                'schema:name' => $ruleResult['rule_id']
            ],
            'schema:student' => [
                '@type' => 'schema:Person',
                '@id' => 'student:' . $context['student_id']
            ],
            'alt42:matchedRule' => [
                '@type' => 'alt42:Rule',
                'alt42:ruleId' => $ruleResult['rule_id'],
                'alt42:confidence' => $ruleResult['confidence'],
                'alt42:rationale' => $ruleResult['rationale']
            ],
            'alt42:actions' => $this->convertActionsToOntology($ruleResult['actions']),
            'alt42:context' => $this->convertContextToOntology($context),
            'schema:timestamp' => $ruleResult['timestamp']
        ];
        
        return $ontology;
    }
    
    /**
     * 액션을 온톨로지 형식으로 변환
     */
    private function convertActionsToOntology($actions) {
        $ontologyActions = [];
        
        foreach ($actions as $idx => $action) {
            $actionType = $this->extractActionType($action);
            $ontologyActions[] = [
                '@type' => 'alt42:Action',
                'alt42:actionType' => $actionType,
                'alt42:actionContent' => $this->extractActionContent($action),
                'alt42:order' => $idx
            ];
        }
        
        return $ontologyActions;
    }
    
    /**
     * 컨텍스트를 온톨로지 형식으로 변환
     */
    private function convertContextToOntology($context) {
        return [
            '@type' => 'alt42:StudentContext',
            'alt42:studentId' => $context['student_id'],
            'alt42:mathLevel' => $context['math_level'] ?? null,
            'alt42:mathConfidence' => $context['math_confidence'] ?? null,
            'alt42:studyStyle' => $context['study_style'] ?? null,
            'alt42:goals' => $this->convertGoalsToOntology($context['goals'] ?? [])
        ];
    }
    
    /**
     * 목표를 온톨로지 형식으로 변환
     */
    private function convertGoalsToOntology($goals) {
        return [
            '@type' => 'alt42:StudentGoals',
            'alt42:shortTerm' => $goals['short_term'] ?? null,
            'alt42:mediumTerm' => $goals['medium_term'] ?? null,
            'alt42:longTerm' => $goals['long_term'] ?? null
        ];
    }
}
```

#### 3.2 온톨로지 기반 추론

```php
<?php
// services/ontology_reasoner.php

class OntologyReasoner {
    private $ontology;
    
    public function __construct($ontologyData) {
        $this->ontology = $ontologyData;
    }
    
    /**
     * 온톨로지에서 관련 정보 추론
     */
    public function infer($query) {
        // SPARQL 쿼리 또는 PHP 기반 추론
        $results = [];
        
        // 예: 학생의 수학 수준에 따른 추천 학습 경로 추론
        if (isset($query['math_level'])) {
            $results['recommended_paths'] = $this->findRelatedPaths($query['math_level']);
        }
        
        // 예: 유사한 학생들의 성공 사례 추론
        if (isset($query['student_profile'])) {
            $results['similar_cases'] = $this->findSimilarCases($query['student_profile']);
        }
        
        return $results;
    }
    
    /**
     * 관련 학습 경로 찾기
     */
    private function findRelatedPaths($mathLevel) {
        // 온톨로지에서 수학 수준과 연관된 학습 경로 조회
        // 예: "수학이 어려워요" -> "기초 개념 강화" -> "단계별 문제풀이"
        return [
            [
                '@type' => 'alt42:LearningPath',
                'alt42:pathName' => '기초 개념 강화',
                'alt42:steps' => [
                    '개념 이해',
                    '기초 문제 풀이',
                    '응용 문제 풀이'
                ]
            ]
        ];
    }
}
```

---

## 🏗️ 통합 아키텍처 설계

### 전체 흐름도

```
┌─────────────────┐
│  Student Data   │
│   (DB Query)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Context Collector│
│  (PHP Service)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Rule Engine    │
│   (Python)      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Rule Result     │
│  (JSON)         │
└────────┬────────┘
         │
         ├─────────────────┐
         │                 │
         ▼                 ▼
┌─────────────────┐  ┌─────────────────┐
│ Content         │  │ Ontology        │
│ Generator       │  │ Converter       │
│ (PHP)           │  │ (PHP)           │
└────────┬────────┘  └────────┬────────┘
         │                    │
         │                    │
         └──────────┬─────────┘
                    │
                    ▼
         ┌─────────────────┐
         │ Final Response  │
         │ (JSON-LD)       │
         └─────────────────┘
```

### 파일 구조

```
agent01_onboarding/
├── agent.php                    # 메인 에이전트 (수정 필요)
├── services/
│   ├── context_collector.php    # DB에서 컨텍스트 수집
│   ├── content_generator.php   # 룰 액션을 컨텐츠로 변환 (신규)
│   ├── description_generator.php # 설명 생성 (신규)
│   ├── ontology_converter.php   # 온톨로지 변환 (신규)
│   └── ontology_reasoner.php    # 온톨로지 추론 (신규)
├── rules/
│   ├── rules.yaml              # 룰 정의
│   ├── onboarding_rule_engine.py # 룰 엔진
│   ├── templates/              # 템플릿 파일들 (신규)
│   │   ├── questions.yaml
│   │   ├── messages.yaml
│   │   └── descriptions.yaml
│   └── ontology_mapping.yaml   # 온톨로지 매핑 (신규)
└── templates/
    └── response.jsonld         # JSON-LD 응답 템플릿 (신규)
```

---

## 📝 구현 단계별 계획

### Phase 1: 기본 컨텐츠 생성 (1주)

1. **템플릿 시스템 구축**
   - `templates/questions.yaml` 생성
   - `templates/messages.yaml` 생성
   - 변수 치환 로직 구현

2. **ContentGenerator 구현**
   - `services/content_generator.php` 작성
   - 룰 액션 파싱 로직
   - 템플릿 렌더링 로직

3. **통합 테스트**
   - `agent.php`에 ContentGenerator 연결
   - 실제 룰 결과로 컨텐츠 생성 확인

### Phase 2: 설명 생성 로직 (1주)

1. **DescriptionGenerator 구현**
   - `services/description_generator.php` 작성
   - 룰 기반 설명 생성 로직
   - 조건부 설명 부분 조합

2. **설명 룰 정의**
   - 각 설명 타입별 룰 정의
   - 템플릿 작성

### Phase 3: 온톨로지 통합 (1-2주)

1. **OntologyConverter 구현**
   - `services/ontology_converter.php` 작성
   - 룰 결과를 JSON-LD로 변환
   - 컨텍스트를 온톨로지 형식으로 변환

2. **OntologyReasoner 구현**
   - `services/ontology_reasoner.php` 작성
   - 온톨로지 기반 추론 로직
   - 관련 정보 조회

3. **온톨로지 매핑 정의**
   - `rules/ontology_mapping.yaml` 작성
   - 룰 필드와 온톨로지 클래스 매핑

### Phase 4: 통합 및 최적화 (1주)

1. **전체 통합**
   - 모든 서비스 연결
   - 에러 핸들링 강화
   - 로깅 추가

2. **성능 최적화**
   - 캐싱 전략
   - 쿼리 최적화

---

## ✅ 검증 방법

### 1. 기능 검증

```php
<?php
// tests/test_rule_based_generation.php

// 1. 컨텍스트 수집 테스트
$context = $contextCollector->collect($studentId);
assert(isset($context['student_id']));

// 2. 룰 평가 테스트
$ruleResult = $ruleEngine->decide($context);
assert(isset($ruleResult['actions']));

// 3. 컨텐츠 생성 테스트
$content = $contentGenerator->generateFromActions($ruleResult['actions'], $context);
assert(isset($content['question']) || isset($content['message']));

// 4. 온톨로지 변환 테스트
$ontology = $ontologyConverter->convertRuleResultToOntology($ruleResult, $context);
assert(isset($ontology['@type']));
assert($ontology['@type'] === 'alt42:AgentDecision');
```

### 2. 결과 검증

- **질문 생성**: 룰의 `question` 액션이 실제 질문 텍스트로 변환되는지 확인
- **메시지 생성**: `display_message` 액션이 메시지로 변환되는지 확인
- **설명 생성**: `generate_description` 액션이 구조화된 설명으로 변환되는지 확인
- **온톨로지 변환**: 룰 결과가 유효한 JSON-LD 형식으로 변환되는지 확인

---

## 🎯 장점 및 한계

### 장점

1. **비용 절감**: GPT API 호출 비용 없음
2. **예측 가능성**: 룰 기반이므로 결과가 일관적
3. **투명성**: 룰이 명시적으로 정의되어 있어 디버깅 용이
4. **확장성**: 새로운 룰 추가가 쉬움
5. **구조화**: 온톨로지 형식으로 데이터 구조화

### 한계

1. **유연성 부족**: LLM만큼 유연한 답변 생성 불가
2. **룰 관리 복잡도**: 룰이 많아질수록 관리 복잡도 증가
3. **컨텍스트 이해**: 복잡한 맥락 이해에 한계
4. **자연어 생성**: 완전히 자연스러운 문장 생성은 어려움

### 보완 방안

- **템플릿 다양화**: 다양한 상황에 대응할 수 있는 템플릿 확보
- **조건부 로직 강화**: 더 복잡한 조건 평가 로직 추가
- **하이브리드 접근**: 간단한 경우는 룰, 복잡한 경우는 LLM 사용

---

## 📚 참고 자료

- 룰 엔진: `agent01_onboarding/rules/onboarding_rule_engine.py`
- 룰 정의: `agent01_onboarding/rules/rules.yaml`
- 온톨로지 설계: `ontology_agents/docs/04-ONTOLOGY_SYSTEM_DESIGN.md`
- 시스템 데이터: `docs/systemdata.md`

---

## 🚀 다음 단계

1. **즉시 시작**: Phase 1 (기본 컨텐츠 생성) 구현
2. **검토 필요**: 온톨로지 매핑 전략 수립
3. **협의 필요**: 템플릿 작성 가이드라인 정의

---

**작성자**: AI Assistant  
**검토 필요**: 온톨로지 전문가, 룰 엔진 개발자  
**마지막 업데이트**: 2025-01-27

