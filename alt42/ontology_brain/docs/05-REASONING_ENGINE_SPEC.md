# Reasoning Engine Specification (Rule Engine + LLM)

**Version**: 1.0.0
**Last Updated**: 2025-01-29
**Status**: Draft

---

## Purpose

This document specifies the **Reasoning Engine** architecture that combines Rule-based decision making with Large Language Model (LLM) inference for intelligent, context-aware student interventions in the Mathking system.

The Reasoning Engine serves as the **decision-making brain** that:
- Evaluates evidence using deterministic rule DSL
- Delegates ambiguous cases to LLM inference
- Calculates directive strength (intervention intensity)
- Generates personalized reports and directives
- Ensures safety through validation and guardrails

---

## 1. Architecture Overview

### 1.1 Hybrid Decision-Making Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      Reasoning Engine                            │
│                                                                   │
│  ┌───────────────┐         ┌──────────────────┐                │
│  │  Rule Engine  │────────▶│  LLM Reasoner    │                │
│  │  (DSL-based)  │         │  (GPT-4/Claude)  │                │
│  └───────────────┘         └──────────────────┘                │
│         │                            │                           │
│         │                            │                           │
│         ▼                            ▼                           │
│  ┌────────────────────────────────────────────┐                │
│  │       Directive Strength Calculator         │                │
│  │     (Persona + Urgency + Severity)          │                │
│  └────────────────────────────────────────────┘                │
│                       │                                          │
│                       ▼                                          │
│  ┌────────────────────────────────────────────┐                │
│  │          Output Generator                   │                │
│  │    (Reports + Directives + Actions)         │                │
│  └────────────────────────────────────────────┘                │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2 Decision Routing Logic

**When to Use Rule Engine**:
- ✅ **Clear thresholds**: Progress < -15%, accuracy < 60%
- ✅ **Binary conditions**: Content complete/incomplete, deadline passed/not passed
- ✅ **Deterministic logic**: If X and Y, then Z
- ✅ **Fast execution required**: <10ms response time
- ✅ **Auditable decisions**: Compliance, safety-critical

**When to Use LLM**:
- ✅ **Ambiguous patterns**: Multiple weak signals combining
- ✅ **Context-dependent**: Requires understanding student history, personality
- ✅ **Creative solutions**: Novel intervention strategies
- ✅ **Natural language required**: Report generation, explanation
- ✅ **Complex reasoning**: Multi-factor trade-offs

### 1.3 Integration Points

The Reasoning Engine integrates with:

| Component | Integration Method | Purpose |
|-----------|-------------------|---------|
| **Knowledge Base** (doc 03) | File read + DSL parser | Load rules, LLM prompts, action definitions |
| **Ontology** (doc 04) | SPARQL queries | Query agent tasks, personas, mappings |
| **Agents** (doc 01) | Evidence packages | Receive student evidence for evaluation |
| **Collaboration System** (doc 02) | Task links | Coordinate multi-agent interventions |
| **LMS Activities** (ontology 3.2) | API calls | Execute actions in Moodle |

---

## 2. Rule Engine Specification

### 2.1 DSL Parser Implementation

**Purpose**: Parse YAML-based rule DSL into executable Python objects.

**Input**: `knowledge/policies/rule_catalog.yaml`

**Output**: Dictionary of `Condition` and `Rule` objects

**Parser Algorithm**:

```python
import ast
from typing import Dict, Any, List
from dataclasses import dataclass

@dataclass
class Condition:
    """
    조건 표현식 (Condition expression)
    """
    id: str
    expression: str  # Python expression string
    ast_tree: ast.Expression  # Compiled AST
    description: str

@dataclass
class Rule:
    """
    규칙 (Rule)
    """
    id: str
    name: str
    when: str  # Condition ID
    then: Dict[str, Any]  # Action specification
    priority: float  # 0.0 ~ 1.0

class RuleDSLParser:
    """
    Rule DSL 파서
    """

    def __init__(self, catalog_path: str):
        self.catalog_path = catalog_path
        self.conditions: Dict[str, Condition] = {}
        self.rules: Dict[str, Rule] = {}

    def parse(self) -> None:
        """
        YAML 파일을 파싱하여 Condition과 Rule 객체 생성
        """
        import yaml

        with open(self.catalog_path, 'r', encoding='utf-8') as f:
            catalog = yaml.safe_load(f)

        # Step 1: Parse Conditions
        for cond_id, cond_def in catalog.get('Condition', {}).items():
            expression = cond_def['expression']
            description = cond_def['description']

            # Compile expression to AST for fast evaluation
            try:
                ast_tree = ast.parse(expression, mode='eval')
            except SyntaxError as e:
                raise ValueError(f"Invalid condition expression '{cond_id}': {e}")

            self.conditions[cond_id] = Condition(
                id=cond_id,
                expression=expression,
                ast_tree=ast_tree,
                description=description
            )

        # Step 2: Parse Rules
        for rule_id, rule_def in catalog.get('Rule', {}).items():
            when = rule_def['when']
            then = rule_def['then']
            priority = then.get('priority', 0.5)

            # Validate that 'when' references a valid condition
            if when not in self.conditions:
                raise ValueError(f"Rule '{rule_id}' references undefined condition '{when}'")

            self.rules[rule_id] = Rule(
                id=rule_id,
                name=rule_def.get('name', rule_id),
                when=when,
                then=then,
                priority=priority
            )

    def get_condition(self, cond_id: str) -> Condition:
        """조건 객체 반환"""
        return self.conditions.get(cond_id)

    def get_rule(self, rule_id: str) -> Rule:
        """규칙 객체 반환"""
        return self.rules.get(rule_id)

    def get_all_rules(self) -> List[Rule]:
        """모든 규칙 반환 (우선순위 내림차순 정렬)"""
        return sorted(self.rules.values(), key=lambda r: r.priority, reverse=True)
```

### 2.2 Condition Evaluator

**Purpose**: Evaluate condition expressions against evidence context.

**Evaluator Algorithm**:

```python
from typing import Dict, Any

class ConditionEvaluator:
    """
    조건 평가기
    """

    def __init__(self, parser: RuleDSLParser):
        self.parser = parser

    def evaluate(self, condition_id: str, context: Dict[str, Any]) -> bool:
        """
        주어진 컨텍스트에서 조건을 평가

        Args:
            condition_id: 평가할 조건 ID (e.g., "cond.progress.below_avg15")
            context: 평가 컨텍스트 (evidence 데이터)

        Returns:
            조건 만족 여부 (True/False)
        """
        condition = self.parser.get_condition(condition_id)
        if not condition:
            raise ValueError(f"Condition not found: {condition_id}")

        # AST 컴파일된 표현식 실행
        try:
            result = eval(
                compile(condition.ast_tree, '<string>', 'eval'),
                {"__builtins__": {}},  # Restrict built-ins for security
                context  # Provide evidence context
            )
            return bool(result)
        except Exception as e:
            # 평가 실패 시 False 반환 (로그 기록)
            print(f"[WARNING] Condition evaluation failed for '{condition_id}': {e}")
            return False

    def evaluate_all_matching_rules(self, context: Dict[str, Any]) -> List[Rule]:
        """
        모든 규칙을 평가하여 조건이 참인 규칙만 반환

        Returns:
            매칭된 규칙 리스트 (우선순위 내림차순 정렬)
        """
        matched_rules = []

        for rule in self.parser.get_all_rules():
            if self.evaluate(rule.when, context):
                matched_rules.append(rule)

        return matched_rules
```

### 2.3 Rule Execution Flow

**Execution Pipeline**:

```python
from typing import List, Dict, Any

@dataclass
class RuleExecutionResult:
    """
    규칙 실행 결과
    """
    matched_rules: List[Rule]
    suggested_actions: List[Dict[str, Any]]
    max_priority: float
    execution_time_ms: float

class RuleEngine:
    """
    규칙 엔진 (Rule Engine)
    """

    def __init__(self, catalog_path: str):
        self.parser = RuleDSLParser(catalog_path)
        self.parser.parse()
        self.evaluator = ConditionEvaluator(self.parser)

    def execute(self, evidence: Dict[str, Any]) -> RuleExecutionResult:
        """
        증거를 바탕으로 규칙 실행

        Args:
            evidence: 학생 증거 데이터 (EvidencePackage)

        Returns:
            규칙 실행 결과
        """
        import time
        start_time = time.time()

        # Step 1: 컨텍스트 준비
        context = self._prepare_context(evidence)

        # Step 2: 매칭되는 규칙 찾기
        matched_rules = self.evaluator.evaluate_all_matching_rules(context)

        # Step 3: 제안 액션 추출
        suggested_actions = []
        max_priority = 0.0

        for rule in matched_rules:
            actions = rule.then.get('suggest', [])
            for action in actions:
                suggested_actions.append({
                    'rule_id': rule.id,
                    'rule_name': rule.name,
                    'action': action,
                    'priority': rule.priority
                })
                max_priority = max(max_priority, rule.priority)

        execution_time_ms = (time.time() - start_time) * 1000

        return RuleExecutionResult(
            matched_rules=matched_rules,
            suggested_actions=suggested_actions,
            max_priority=max_priority,
            execution_time_ms=execution_time_ms
        )

    def _prepare_context(self, evidence: Dict[str, Any]) -> Dict[str, Any]:
        """
        평가용 컨텍스트 준비

        Evidence 데이터를 평가 가능한 형태로 변환
        """
        context = {
            'evidence': evidence,
            # Helper functions for common calculations
            'abs': abs,
            'max': max,
            'min': min,
            'len': len
        }
        return context
```

### 2.4 Rule Priority Handling

**Priority Resolution Strategy**:

```python
def resolve_rule_conflicts(matched_rules: List[Rule]) -> Rule:
    """
    여러 규칙이 매칭될 때 우선순위 해결

    Strategy:
    1. 우선순위가 가장 높은 규칙 선택
    2. 동일 우선순위인 경우, 더 구체적인 조건을 가진 규칙 선택
    3. 여전히 동일한 경우, 규칙 ID 알파벳 순서

    Args:
        matched_rules: 매칭된 규칙 리스트

    Returns:
        최종 선택된 규칙
    """
    if not matched_rules:
        return None

    # Sort by priority (descending), then by specificity, then by ID
    sorted_rules = sorted(
        matched_rules,
        key=lambda r: (r.priority, _calculate_specificity(r), r.id),
        reverse=True
    )

    return sorted_rules[0]

def _calculate_specificity(rule: Rule) -> int:
    """
    규칙의 구체성 점수 계산

    조건 표현식의 복잡도를 기준으로 점수화
    (더 많은 조건을 포함할수록 구체적)
    """
    condition = rule.when
    # Count logical operators (and, or, not)
    specificity = condition.count(' and ') + condition.count(' or ') + condition.count(' not ')
    return specificity
```

---

## 3. LLM Reasoner Specification

### 3.1 LLM Integration Architecture

**Supported LLM Providers**:
- OpenAI GPT-4 / GPT-4 Turbo
- Anthropic Claude 2.1 / Claude 3 Opus
- Local models via Ollama (optional)

**LLM Reasoner Interface**:

```python
from typing import Dict, Any, Optional
from dataclasses import dataclass
from enum import Enum

class LLMProvider(Enum):
    OPENAI_GPT4 = "openai_gpt4"
    ANTHROPIC_CLAUDE = "anthropic_claude"
    OLLAMA_LOCAL = "ollama_local"

@dataclass
class LLMReasoningRequest:
    """
    LLM 추론 요청
    """
    student_id: str
    persona_type: str
    persona_similarity: float
    evidence: Dict[str, Any]
    context_summary: str
    request_type: str  # 'root_cause_analysis', 'intervention_strategy', 'priority_decision'
    constraints: Optional[Dict[str, Any]] = None

@dataclass
class LLMReasoningResponse:
    """
    LLM 추론 응답
    """
    root_cause: str
    intervention_strategy: str
    priority_score: float  # 0.0 ~ 1.0
    directive_strength: float  # 0.0 ~ 1.0
    expected_outcome: str
    risk_factors: List[str]
    confidence: float  # 0.0 ~ 1.0
    reasoning_trace: str  # LLM's chain of thought
    token_usage: int

class LLMReasoner:
    """
    LLM 기반 추론기
    """

    def __init__(self, provider: LLMProvider, api_key: str, model_name: str):
        self.provider = provider
        self.api_key = api_key
        self.model_name = model_name
        self.client = self._initialize_client()

    def _initialize_client(self):
        """
        LLM 클라이언트 초기화
        """
        if self.provider == LLMProvider.OPENAI_GPT4:
            from openai import OpenAI
            return OpenAI(api_key=self.api_key)
        elif self.provider == LLMProvider.ANTHROPIC_CLAUDE:
            from anthropic import Anthropic
            return Anthropic(api_key=self.api_key)
        else:
            raise ValueError(f"Unsupported provider: {self.provider}")

    def reason(self, request: LLMReasoningRequest) -> LLMReasoningResponse:
        """
        LLM을 사용하여 추론 수행

        Args:
            request: 추론 요청 객체

        Returns:
            추론 응답 객체
        """
        # Step 1: 프롬프트 생성
        prompt = self._build_prompt(request)

        # Step 2: LLM 호출
        raw_response = self._call_llm(prompt)

        # Step 3: 응답 파싱 및 구조화
        parsed_response = self._parse_response(raw_response)

        # Step 4: 응답 검증
        validated_response = self._validate_response(parsed_response, request)

        return validated_response

    def _build_prompt(self, request: LLMReasoningRequest) -> str:
        """
        LLM 프롬프트 구성

        knowledge/guides/llm_decision_prompt.md 템플릿 사용
        """
        from jinja2 import Template

        # Load prompt template
        with open('knowledge/guides/llm_decision_prompt.md', 'r', encoding='utf-8') as f:
            template_str = f.read()

        template = Template(template_str)

        # Render with request data
        prompt = template.render(
            student_id=request.student_id,
            persona_type=request.persona_type,
            persona_similarity=request.persona_similarity,
            evidence=request.evidence,
            context_summary=request.context_summary,
            request_type=request.request_type,
            constraints=request.constraints or {}
        )

        return prompt

    def _call_llm(self, prompt: str) -> Dict[str, Any]:
        """
        LLM API 호출
        """
        if self.provider == LLMProvider.OPENAI_GPT4:
            response = self.client.chat.completions.create(
                model=self.model_name,
                messages=[
                    {"role": "system", "content": "You are an AI tutor's decision engine."},
                    {"role": "user", "content": prompt}
                ],
                temperature=0.3,  # Lower temperature for more consistent reasoning
                max_tokens=1500
            )
            return {
                'content': response.choices[0].message.content,
                'token_usage': response.usage.total_tokens
            }
        elif self.provider == LLMProvider.ANTHROPIC_CLAUDE:
            response = self.client.messages.create(
                model=self.model_name,
                max_tokens=1500,
                temperature=0.3,
                messages=[
                    {"role": "user", "content": prompt}
                ]
            )
            return {
                'content': response.content[0].text,
                'token_usage': response.usage.input_tokens + response.usage.output_tokens
            }
        else:
            raise ValueError(f"Unsupported provider: {self.provider}")

    def _parse_response(self, raw_response: Dict[str, Any]) -> Dict[str, Any]:
        """
        LLM 응답 파싱

        Expected format (Markdown sections):
        ## 원인 분석
        ...

        ## 개입 전략
        ...

        ## 우선순위
        점수: 0.85
        이유: ...

        ## 지시성 강도
        강도: 0.75
        근거: ...

        ## 예상 효과
        ...

        ## 위험 요소
        - ...
        """
        import re

        content = raw_response['content']
        token_usage = raw_response['token_usage']

        # Extract sections using regex
        root_cause = self._extract_section(content, r'## 원인 분석\s*\n(.*?)(?=##|$)', "분석 불가")
        intervention = self._extract_section(content, r'## 개입 전략\s*\n(.*?)(?=##|$)', "전략 없음")
        expected_outcome = self._extract_section(content, r'## 예상 효과\s*\n(.*?)(?=##|$)', "예측 불가")
        risk_factors_str = self._extract_section(content, r'## 위험 요소\s*\n(.*?)(?=##|$)', "")

        # Extract numerical scores
        priority_match = re.search(r'점수:\s*([\d.]+)', content)
        priority = float(priority_match.group(1)) if priority_match else 0.5

        strength_match = re.search(r'강도:\s*([\d.]+)', content)
        strength = float(strength_match.group(1)) if strength_match else 0.5

        # Parse risk factors (bulleted list)
        risk_factors = [
            line.strip('- ').strip()
            for line in risk_factors_str.split('\n')
            if line.strip().startswith('-')
        ]

        return {
            'root_cause': root_cause.strip(),
            'intervention_strategy': intervention.strip(),
            'priority_score': priority,
            'directive_strength': strength,
            'expected_outcome': expected_outcome.strip(),
            'risk_factors': risk_factors,
            'confidence': 0.8,  # Default confidence
            'reasoning_trace': content,
            'token_usage': token_usage
        }

    def _extract_section(self, content: str, pattern: str, default: str) -> str:
        """
        정규식으로 섹션 추출
        """
        import re
        match = re.search(pattern, content, re.DOTALL)
        return match.group(1).strip() if match else default

    def _validate_response(self, parsed: Dict[str, Any], request: LLMReasoningRequest) -> LLMReasoningResponse:
        """
        응답 검증 및 안전 확인
        """
        # Clamp numerical values to valid ranges
        priority_score = max(0.0, min(1.0, parsed['priority_score']))
        directive_strength = max(0.0, min(1.0, parsed['directive_strength']))
        confidence = max(0.0, min(1.0, parsed['confidence']))

        # Safety checks
        if directive_strength > 0.9 and len(parsed['risk_factors']) == 0:
            # High intervention with no identified risks - add warning
            parsed['risk_factors'].append("⚠️ 높은 개입 강도이나 위험 요소가 명시되지 않음")

        return LLMReasoningResponse(
            root_cause=parsed['root_cause'],
            intervention_strategy=parsed['intervention_strategy'],
            priority_score=priority_score,
            directive_strength=directive_strength,
            expected_outcome=parsed['expected_outcome'],
            risk_factors=parsed['risk_factors'],
            confidence=confidence,
            reasoning_trace=parsed['reasoning_trace'],
            token_usage=parsed['token_usage']
        )
```

### 3.2 Context Preparation for LLM

**Purpose**: Build rich, token-efficient context for LLM reasoning.

**Context Builder Algorithm**:

```python
from typing import Dict, Any, List

class ContextBuilder:
    """
    LLM 컨텍스트 구성기
    """

    def __init__(self, ontology_api, max_tokens: int = 3000):
        self.ontology_api = ontology_api
        self.max_tokens = max_tokens

    def build_context(self, student_id: str, evidence: Dict[str, Any]) -> str:
        """
        학생 컨텍스트 구성

        Args:
            student_id: 학생 ID
            evidence: 증거 패키지

        Returns:
            구조화된 컨텍스트 문자열
        """
        context_parts = []

        # Part 1: 학생 기본 정보
        student_profile = self._get_student_profile(student_id)
        context_parts.append(self._format_student_profile(student_profile))

        # Part 2: 페르소나 정보
        persona_info = self._get_persona_info(student_profile['persona_type'])
        context_parts.append(self._format_persona_info(persona_info))

        # Part 3: 최근 학습 이력 (요약)
        learning_history = self._get_learning_history(student_id, days=7)
        context_parts.append(self._format_learning_history(learning_history))

        # Part 4: 현재 증거 (상세)
        context_parts.append(self._format_evidence(evidence))

        # Part 5: 관련 규칙 (매칭된 규칙 설명)
        matched_rules = self._get_matched_rules(evidence)
        context_parts.append(self._format_matched_rules(matched_rules))

        # Combine and truncate if necessary
        full_context = "\n\n".join(context_parts)

        if self._estimate_tokens(full_context) > self.max_tokens:
            full_context = self._truncate_context(full_context, self.max_tokens)

        return full_context

    def _get_student_profile(self, student_id: str) -> Dict[str, Any]:
        """
        학생 프로필 조회 (DB 또는 ontology)
        """
        # Query from ontology or database
        return {
            'student_id': student_id,
            'name': 'Student Name',
            'grade': 8,
            'persona_type': 'perfectionist',
            'persona_similarity': 0.82,
            'learning_level': 'intermediate'
        }

    def _get_persona_info(self, persona_type: str) -> Dict[str, Any]:
        """
        페르소나 정보 조회
        """
        persona = self.ontology_api.find_persona(persona_type)
        return {
            'type': persona_type,
            'traits': persona.traits,
            'typical_behaviors': persona.typical_behaviors,
            'intervention_preferences': persona.intervention_preferences
        }

    def _get_learning_history(self, student_id: str, days: int) -> List[Dict[str, Any]]:
        """
        최근 학습 이력 조회 (요약)
        """
        # Query recent activity logs
        return [
            {'date': '2025-01-28', 'activity': 'Quiz 완료', 'score': 85},
            {'date': '2025-01-27', 'activity': 'Practice 세션', 'duration_min': 45},
            {'date': '2025-01-26', 'activity': 'Video 시청', 'duration_min': 20}
        ]

    def _get_matched_rules(self, evidence: Dict[str, Any]) -> List[str]:
        """
        매칭된 규칙 설명 조회
        """
        # Get rules that matched this evidence
        return [
            "규칙: 진도 미달 리포트 생성 (progress < -15%)",
            "규칙: 적응형 난이도 조정 (accuracy < 60%)"
        ]

    def _format_student_profile(self, profile: Dict[str, Any]) -> str:
        """학생 프로필 포맷팅"""
        return f"""## 학생 정보
- 학생 ID: {profile['student_id']}
- 학년: {profile['grade']}
- 페르소나: {profile['persona_type']} (유사도: {profile['persona_similarity']:.2f})
- 학습 레벨: {profile['learning_level']}"""

    def _format_persona_info(self, info: Dict[str, Any]) -> str:
        """페르소나 정보 포맷팅"""
        traits_str = ', '.join(info.get('traits', []))
        return f"""## 페르소나 특성
- 유형: {info['type']}
- 주요 특성: {traits_str}
- 전형적 행동: {', '.join(info.get('typical_behaviors', []))}"""

    def _format_learning_history(self, history: List[Dict[str, Any]]) -> str:
        """학습 이력 포맷팅"""
        history_lines = [
            f"- {item['date']}: {item['activity']}"
            for item in history[:5]  # 최근 5개만
        ]
        return f"""## 최근 학습 이력 (7일)
{chr(10).join(history_lines)}"""

    def _format_evidence(self, evidence: Dict[str, Any]) -> str:
        """증거 포맷팅"""
        metrics = evidence.get('metrics', {})
        return f"""## 현재 증거 (Evidence)
- 진도율 변화: {metrics.get('progress_delta', 'N/A')}
- 정답률: {metrics.get('accuracy_rate', 'N/A')}
- 평균 응답 시간: {metrics.get('response_time_avg', 'N/A')}초
- 재시도 횟수: {metrics.get('retry_count', 'N/A')}
- 완료율: {metrics.get('completion_rate', 'N/A')}
- 정서 상태: {evidence.get('state', {}).get('affect', 'N/A')}"""

    def _format_matched_rules(self, rules: List[str]) -> str:
        """매칭된 규칙 포맷팅"""
        if not rules:
            return "## 매칭된 규칙\n없음 (LLM 추론 필요)"
        rules_str = '\n'.join([f"- {rule}" for rule in rules])
        return f"""## 매칭된 규칙
{rules_str}"""

    def _estimate_tokens(self, text: str) -> int:
        """
        토큰 수 추정 (간단한 근사)
        실제 환경에서는 tiktoken 등 사용
        """
        return len(text.split()) * 1.3  # Rough approximation

    def _truncate_context(self, context: str, max_tokens: int) -> str:
        """
        컨텍스트 잘라내기 (토큰 제한)
        """
        # Simple word-based truncation
        words = context.split()
        estimated_words = int(max_tokens / 1.3)
        return ' '.join(words[:estimated_words]) + "\n\n... (컨텍스트 잘림)"
```

### 3.3 LLM Response Validation

**Validation Rules**:

```python
from typing import Dict, Any, List

class LLMResponseValidator:
    """
    LLM 응답 검증기
    """

    def validate(self, response: LLMReasoningResponse, request: LLMReasoningRequest) -> Dict[str, Any]:
        """
        LLM 응답 검증

        Returns:
            검증 결과 딕셔너리 {
                'valid': bool,
                'warnings': List[str],
                'errors': List[str]
            }
        """
        warnings = []
        errors = []

        # Rule 1: 필수 필드 존재 확인
        if not response.root_cause or response.root_cause == "분석 불가":
            errors.append("원인 분석이 누락되었습니다.")

        if not response.intervention_strategy or response.intervention_strategy == "전략 없음":
            errors.append("개입 전략이 누락되었습니다.")

        # Rule 2: 수치 범위 검증
        if not (0.0 <= response.priority_score <= 1.0):
            errors.append(f"우선순위 점수가 범위를 벗어남: {response.priority_score}")

        if not (0.0 <= response.directive_strength <= 1.0):
            errors.append(f"지시성 강도가 범위를 벗어남: {response.directive_strength}")

        # Rule 3: 위험 요소 확인 (고강도 개입 시)
        if response.directive_strength > 0.8 and len(response.risk_factors) == 0:
            warnings.append("높은 강도의 개입이지만 위험 요소가 명시되지 않았습니다.")

        # Rule 4: 페르소나 일관성 확인
        if request.persona_type == 'avoidant' and response.directive_strength > 0.7:
            warnings.append("회피형 페르소나에게 강한 지시는 역효과를 낼 수 있습니다.")

        # Rule 5: 신뢰도 확인
        if response.confidence < 0.5:
            warnings.append(f"신뢰도가 낮습니다 ({response.confidence:.2f}). 결과를 신중히 검토하세요.")

        # Rule 6: 토큰 사용량 확인
        if response.token_usage > 2000:
            warnings.append(f"토큰 사용량이 높습니다 ({response.token_usage}). 프롬프트 최적화를 고려하세요.")

        return {
            'valid': len(errors) == 0,
            'warnings': warnings,
            'errors': errors
        }
```

---

## 4. Decision Pipeline

### 4.1 Integrated Decision Flow

**Complete Pipeline**:

```python
from typing import Dict, Any, Optional
from dataclasses import dataclass

@dataclass
class DecisionResult:
    """
    최종 의사결정 결과
    """
    decision_type: str  # 'rule_based', 'llm_based', 'hybrid'
    action: str  # 'generate_report', 'generate_directive', 'take_action'
    template_id: str
    params: Dict[str, Any]
    directive_strength: float
    priority: float
    reasoning: str
    confidence: float
    execution_time_ms: float

class ReasoningEngine:
    """
    통합 추론 엔진 (Rule Engine + LLM)
    """

    def __init__(self, rule_catalog_path: str, llm_provider: LLMProvider, llm_api_key: str, llm_model: str):
        self.rule_engine = RuleEngine(rule_catalog_path)
        self.llm_reasoner = LLMReasoner(llm_provider, llm_api_key, llm_model)
        self.context_builder = ContextBuilder(ontology_api=None)  # TODO: inject ontology API
        self.directive_calculator = DirectiveStrengthCalculator()

    def decide(self, student_id: str, evidence: Dict[str, Any]) -> DecisionResult:
        """
        통합 의사결정 수행

        Args:
            student_id: 학생 ID
            evidence: 증거 패키지

        Returns:
            의사결정 결과
        """
        import time
        start_time = time.time()

        # Step 1: Rule Engine 실행
        rule_result = self.rule_engine.execute(evidence)

        # Step 2: 결정 라우팅 (Rule vs LLM)
        if self._should_use_llm(rule_result, evidence):
            # LLM 추론 필요
            decision_result = self._decide_with_llm(student_id, evidence, rule_result)
        else:
            # Rule 기반 결정으로 충분
            decision_result = self._decide_with_rules(rule_result)

        # Step 3: 지시성 강도 계산
        directive_strength = self.directive_calculator.calculate(
            decision_result,
            evidence,
            student_id
        )
        decision_result.directive_strength = directive_strength

        execution_time_ms = (time.time() - start_time) * 1000
        decision_result.execution_time_ms = execution_time_ms

        return decision_result

    def _should_use_llm(self, rule_result: RuleExecutionResult, evidence: Dict[str, Any]) -> bool:
        """
        LLM 추론 필요 여부 판단

        Criteria:
        1. 매칭된 규칙이 없음
        2. 여러 규칙이 충돌 (우선순위 유사)
        3. 증거가 모호함 (여러 지표가 혼재)
        4. 복잡한 컨텍스트 (학습 이력 고려 필요)
        """
        # Criterion 1: 매칭된 규칙 없음
        if len(rule_result.matched_rules) == 0:
            return True

        # Criterion 2: 규칙 충돌 (우선순위 차이 < 0.1)
        if len(rule_result.matched_rules) > 1:
            priorities = [r.priority for r in rule_result.matched_rules]
            if max(priorities) - min(priorities) < 0.1:
                return True

        # Criterion 3: 증거 모호성 (여러 카테고리에 걸침)
        evidence_categories = self._count_evidence_categories(evidence)
        if evidence_categories > 3:
            return True

        # Criterion 4: 복잡한 컨텍스트 (명시적 플래그)
        if evidence.get('require_llm', False):
            return True

        return False

    def _count_evidence_categories(self, evidence: Dict[str, Any]) -> int:
        """
        증거가 속한 카테고리 수 계산
        """
        categories = set()
        metrics = evidence.get('metrics', {})

        if 'progress_delta' in metrics or 'completion_rate' in metrics:
            categories.add('academic_performance')
        if 'affect' in evidence.get('state', {}):
            categories.add('emotional_state')
        if 'time_on_task' in metrics:
            categories.add('time_management')
        if 'retry_count' in metrics:
            categories.add('cognitive_load')

        return len(categories)

    def _decide_with_rules(self, rule_result: RuleExecutionResult) -> DecisionResult:
        """
        규칙 기반 의사결정
        """
        # 가장 높은 우선순위의 액션 선택
        if not rule_result.suggested_actions:
            return DecisionResult(
                decision_type='rule_based',
                action='no_action',
                template_id=None,
                params={},
                directive_strength=0.0,
                priority=0.0,
                reasoning="매칭된 규칙이 없습니다.",
                confidence=1.0,
                execution_time_ms=0.0
            )

        top_action = rule_result.suggested_actions[0]

        return DecisionResult(
            decision_type='rule_based',
            action=top_action['action'].get('template', 'unknown'),
            template_id=top_action['action'].get('template'),
            params=top_action['action'].get('params', {}),
            directive_strength=0.0,  # Will be calculated later
            priority=top_action['priority'],
            reasoning=f"규칙 '{top_action['rule_name']}' 적용",
            confidence=1.0,
            execution_time_ms=rule_result.execution_time_ms
        )

    def _decide_with_llm(self, student_id: str, evidence: Dict[str, Any], rule_result: RuleExecutionResult) -> DecisionResult:
        """
        LLM 기반 의사결정
        """
        # Step 1: 컨텍스트 구성
        context_summary = self.context_builder.build_context(student_id, evidence)

        # Step 2: LLM 추론 요청
        llm_request = LLMReasoningRequest(
            student_id=student_id,
            persona_type=evidence.get('persona_type', 'unknown'),
            persona_similarity=evidence.get('persona_similarity', 0.5),
            evidence=evidence,
            context_summary=context_summary,
            request_type='intervention_strategy'
        )

        llm_response = self.llm_reasoner.reason(llm_request)

        # Step 3: LLM 응답 검증
        validator = LLMResponseValidator()
        validation = validator.validate(llm_response, llm_request)

        if not validation['valid']:
            # 검증 실패 시 폴백 (규칙 기반 또는 안전한 기본 액션)
            return self._fallback_decision(validation['errors'])

        # Step 4: LLM 추천을 의사결정 결과로 변환
        return DecisionResult(
            decision_type='llm_based',
            action='generate_directive',
            template_id='directive_llm_generated',
            params={
                'strategy': llm_response.intervention_strategy,
                'root_cause': llm_response.root_cause,
                'expected_outcome': llm_response.expected_outcome
            },
            directive_strength=llm_response.directive_strength,
            priority=llm_response.priority_score,
            reasoning=llm_response.reasoning_trace,
            confidence=llm_response.confidence,
            execution_time_ms=0.0  # LLM call time tracked separately
        )

    def _fallback_decision(self, errors: List[str]) -> DecisionResult:
        """
        폴백 의사결정 (LLM 실패 시)
        """
        return DecisionResult(
            decision_type='fallback',
            action='generate_report',
            template_id='report_generic',
            params={'errors': errors},
            directive_strength=0.3,
            priority=0.5,
            reasoning=f"LLM 추론 실패로 폴백: {'; '.join(errors)}",
            confidence=0.3,
            execution_time_ms=0.0
        )
```

### 4.2 Decision Caching Strategy

**Purpose**: Cache frequent decision patterns to reduce LLM API costs.

**Cache Strategy**:

```python
import hashlib
import json
from typing import Dict, Any, Optional

class DecisionCache:
    """
    의사결정 결과 캐시
    """

    def __init__(self, ttl_seconds: int = 3600):
        self.cache: Dict[str, tuple] = {}  # {cache_key: (result, timestamp)}
        self.ttl_seconds = ttl_seconds

    def get(self, student_id: str, evidence: Dict[str, Any]) -> Optional[DecisionResult]:
        """
        캐시에서 의사결정 결과 조회
        """
        cache_key = self._generate_cache_key(student_id, evidence)

        if cache_key in self.cache:
            result, timestamp = self.cache[cache_key]
            import time
            if time.time() - timestamp < self.ttl_seconds:
                return result
            else:
                # 만료된 캐시 삭제
                del self.cache[cache_key]

        return None

    def set(self, student_id: str, evidence: Dict[str, Any], result: DecisionResult):
        """
        의사결정 결과를 캐시에 저장
        """
        cache_key = self._generate_cache_key(student_id, evidence)
        import time
        self.cache[cache_key] = (result, time.time())

    def _generate_cache_key(self, student_id: str, evidence: Dict[str, Any]) -> str:
        """
        캐시 키 생성 (학생 ID + 증거 해시)
        """
        # 증거의 핵심 필드만 사용하여 해시 생성
        cache_data = {
            'student_id': student_id,
            'progress_delta': evidence.get('metrics', {}).get('progress_delta'),
            'accuracy_rate': evidence.get('metrics', {}).get('accuracy_rate'),
            'affect': evidence.get('state', {}).get('affect')
        }
        cache_str = json.dumps(cache_data, sort_keys=True)
        return hashlib.md5(cache_str.encode()).hexdigest()
```

---

## 5. Directive Strength Calculator

### 5.1 Directive Strength Formula

**Purpose**: Calculate intervention intensity (0.0 ~ 1.0) based on multiple factors.

**Formula**:

```
directive_strength = base_strength × persona_factor × urgency_factor × severity_factor

Where:
- base_strength: 의사결정 결과의 초기 강도 (규칙 또는 LLM에서 제공)
- persona_factor: 페르소나 유사도 기반 조정 (0.5 ~ 1.5)
- urgency_factor: 긴급도 (1.0 ~ 2.0)
- severity_factor: 심각도 (1.0 ~ 2.0)

Final clamping: max(0.0, min(1.0, directive_strength))
```

**Implementation**:

```python
from typing import Dict, Any
import math

class DirectiveStrengthCalculator:
    """
    지시성 강도 계산기
    """

    def calculate(self, decision: DecisionResult, evidence: Dict[str, Any], student_id: str) -> float:
        """
        지시성 강도 계산

        Args:
            decision: 의사결정 결과
            evidence: 증거 패키지
            student_id: 학생 ID

        Returns:
            지시성 강도 (0.0 ~ 1.0)
        """
        # Base strength from decision
        base_strength = decision.directive_strength or decision.priority

        # Factor 1: Persona similarity
        persona_similarity = evidence.get('persona_similarity', 0.5)
        persona_factor = self._calculate_persona_factor(persona_similarity)

        # Factor 2: Urgency
        urgency_factor = self._calculate_urgency_factor(evidence)

        # Factor 3: Severity
        severity_factor = self._calculate_severity_factor(evidence)

        # Combined calculation
        strength = base_strength * persona_factor * urgency_factor * severity_factor

        # Clamp to [0.0, 1.0]
        strength = max(0.0, min(1.0, strength))

        return strength

    def _calculate_persona_factor(self, persona_similarity: float) -> float:
        """
        페르소나 유사도 기반 조정

        높은 유사도 → 더 강한 개입 가능
        낮은 유사도 → 개입 강도 낮춤

        Args:
            persona_similarity: 0.0 ~ 1.0

        Returns:
            조정 계수 (0.5 ~ 1.5)
        """
        # Linear mapping: 0.0 → 0.5, 1.0 → 1.5
        return 0.5 + persona_similarity

    def _calculate_urgency_factor(self, evidence: Dict[str, Any]) -> float:
        """
        긴급도 계산

        Criteria:
        - 시험 임박 여부
        - 진도 지연 정도
        - 최근 활동 빈도

        Returns:
            긴급도 계수 (1.0 ~ 2.0)
        """
        urgency_score = 1.0

        # Criterion 1: 시험 임박
        days_until_exam = evidence.get('days_until_exam', float('inf'))
        if days_until_exam < 7:
            urgency_score += 0.5
        elif days_until_exam < 14:
            urgency_score += 0.3

        # Criterion 2: 진도 지연
        progress_delta = evidence.get('metrics', {}).get('progress_delta', 0.0)
        if progress_delta < -0.3:  # 30% 이상 뒤처짐
            urgency_score += 0.4
        elif progress_delta < -0.15:  # 15% 이상 뒤처짐
            urgency_score += 0.2

        # Criterion 3: 최근 비활성
        days_since_last_activity = evidence.get('days_since_last_activity', 0)
        if days_since_last_activity > 3:
            urgency_score += 0.3

        return min(urgency_score, 2.0)

    def _calculate_severity_factor(self, evidence: Dict[str, Any]) -> float:
        """
        심각도 계산

        Criteria:
        - 정답률 하락 정도
        - 정서 상태 악화
        - 재시도 횟수 증가

        Returns:
            심각도 계수 (1.0 ~ 2.0)
        """
        severity_score = 1.0

        # Criterion 1: 정답률 하락
        accuracy_rate = evidence.get('metrics', {}).get('accuracy_rate', 1.0)
        if accuracy_rate < 0.4:  # 40% 미만
            severity_score += 0.5
        elif accuracy_rate < 0.6:  # 60% 미만
            severity_score += 0.3

        # Criterion 2: 정서 상태
        affect = evidence.get('state', {}).get('affect', 'neutral')
        if affect in ['frustrated', 'anxious', 'discouraged']:
            severity_score += 0.4

        # Criterion 3: 재시도 횟수
        retry_count = evidence.get('metrics', {}).get('retry_count', 0)
        if retry_count > 5:
            severity_score += 0.3

        return min(severity_score, 2.0)
```

### 5.2 Directive Strength Interpretation

**Strength Ranges and Meanings**:

| Range | Interpretation | Directive Style | Example |
|-------|----------------|-----------------|---------|
| 0.0 - 0.3 | **제안** (Suggestion) | "다음 활동을 참고해보세요" | Optional recommendation |
| 0.3 - 0.6 | **권장** (Recommendation) | "이 활동을 우선 후보로 고려하세요" | Recommended action |
| 0.6 - 0.8 | **강력 권장** (Strong Recommendation) | "다음 활동을 우선적으로 진행하세요" | Strongly advised |
| 0.8 - 1.0 | **즉시 실행** (Immediate Action) | "지금 바로 다음 활동을 시작하세요" | Urgent intervention |

**Template Selection Based on Strength**:

```python
def select_template_by_strength(directive_strength: float, base_template: str) -> str:
    """
    지시성 강도에 따라 적절한 템플릿 변형 선택

    Args:
        directive_strength: 0.0 ~ 1.0
        base_template: 기본 템플릿 ID (e.g., 'directive_curriculum_adjust')

    Returns:
        조정된 템플릿 ID
    """
    if directive_strength >= 0.8:
        return f"{base_template}_urgent"
    elif directive_strength >= 0.6:
        return f"{base_template}_strong"
    elif directive_strength >= 0.3:
        return f"{base_template}_recommend"
    else:
        return f"{base_template}_suggest"
```

---

## 6. Performance Optimization

### 6.1 Token Budget Management

**Goal**: Keep LLM API costs under control while maintaining decision quality.

**Budget Allocation Strategy**:

```python
from dataclasses import dataclass

@dataclass
class TokenBudget:
    """
    토큰 예산 설정
    """
    daily_limit: int = 100000  # 일일 토큰 한도
    per_student_limit: int = 5000  # 학생당 토큰 한도
    per_decision_limit: int = 3000  # 의사결정당 토큰 한도

class TokenBudgetManager:
    """
    토큰 예산 관리자
    """

    def __init__(self, budget: TokenBudget):
        self.budget = budget
        self.usage: Dict[str, int] = {
            'daily': 0,
            'per_student': {},
            'per_decision': []
        }

    def can_use_llm(self, student_id: str, estimated_tokens: int) -> bool:
        """
        LLM 사용 가능 여부 확인

        Returns:
            사용 가능하면 True, 예산 초과 시 False
        """
        # Check daily limit
        if self.usage['daily'] + estimated_tokens > self.budget.daily_limit:
            return False

        # Check per-student limit
        student_usage = self.usage['per_student'].get(student_id, 0)
        if student_usage + estimated_tokens > self.budget.per_student_limit:
            return False

        return True

    def record_usage(self, student_id: str, tokens: int):
        """
        토큰 사용 기록
        """
        self.usage['daily'] += tokens
        self.usage['per_student'][student_id] = self.usage['per_student'].get(student_id, 0) + tokens
        self.usage['per_decision'].append(tokens)

    def reset_daily_usage(self):
        """
        일일 사용량 초기화 (매일 자정 실행)
        """
        self.usage['daily'] = 0
        self.usage['per_decision'] = []
```

### 6.2 Batch Processing for Multiple Students

**Purpose**: Process multiple students in parallel to reduce latency.

**Batch Processing Algorithm**:

```python
from concurrent.futures import ThreadPoolExecutor, as_completed
from typing import List

class BatchReasoningEngine:
    """
    배치 추론 엔진
    """

    def __init__(self, reasoning_engine: ReasoningEngine, max_workers: int = 10):
        self.reasoning_engine = reasoning_engine
        self.max_workers = max_workers

    def decide_batch(self, student_evidence_pairs: List[tuple]) -> List[DecisionResult]:
        """
        여러 학생의 의사결정을 병렬 처리

        Args:
            student_evidence_pairs: [(student_id, evidence), ...]

        Returns:
            의사결정 결과 리스트
        """
        results = []

        with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
            # Submit all tasks
            future_to_student = {
                executor.submit(self.reasoning_engine.decide, student_id, evidence): student_id
                for student_id, evidence in student_evidence_pairs
            }

            # Collect results as they complete
            for future in as_completed(future_to_student):
                student_id = future_to_student[future]
                try:
                    result = future.result()
                    results.append(result)
                except Exception as e:
                    print(f"[ERROR] Decision failed for student {student_id}: {e}")
                    results.append(None)

        return results
```

### 6.3 Rule Engine Caching

**Purpose**: Cache compiled rule conditions to avoid repeated parsing.

**Cache Implementation** (already shown in RuleDSLParser):
- Condition AST compiled once during parsing
- Rules loaded into memory
- No repeated file I/O or parsing overhead

---

## 7. Safety and Validation

### 7.1 Output Safety Guardrails

**Safety Rules**:

```python
from typing import Dict, Any, List

class SafetyGuardrails:
    """
    안전 가드레일 (Safety guardrails)
    """

    @staticmethod
    def validate_output(decision: DecisionResult, evidence: Dict[str, Any]) -> Dict[str, Any]:
        """
        의사결정 결과의 안전성 검증

        Returns:
            검증 결과 {
                'safe': bool,
                'violations': List[str],
                'recommendations': List[str]
            }
        """
        violations = []
        recommendations = []

        # Rule 1: 과도한 난이도 하향 방지
        if decision.params.get('difficulty_adjustment') == 'decrease':
            current_level = evidence.get('current_difficulty_level', 'medium')
            if current_level == 'easy':
                violations.append("이미 최저 난이도입니다. 더 이상 하향 불가.")

        # Rule 2: 과도한 개입 빈도 방지 (하루 최대 3회)
        student_id = evidence.get('student_id')
        intervention_count_today = SafetyGuardrails._get_intervention_count_today(student_id)
        if intervention_count_today >= 3 and decision.directive_strength > 0.5:
            violations.append("오늘 이미 3회 개입했습니다. 과도한 개입은 학생에게 부담.")
            recommendations.append("내일로 미루거나 강도를 낮추세요.")

        # Rule 3: 페르소나 부적합 개입 방지
        persona_type = evidence.get('persona_type')
        if persona_type == 'avoidant' and decision.directive_strength > 0.7:
            violations.append("회피형 학생에게 강한 개입은 역효과 (도망칠 위험).")
            recommendations.append("강도를 0.5 이하로 낮추세요.")

        # Rule 4: 위험한 액션 방지
        if decision.action == 'skip_entire_module':
            violations.append("전체 모듈 스킵은 학습 공백을 초래할 수 있습니다.")
            recommendations.append("일부 섹션만 스킵하거나 요약 콘텐츠 제공을 고려하세요.")

        # Rule 5: LLM 신뢰도 확인
        if decision.decision_type == 'llm_based' and decision.confidence < 0.6:
            violations.append(f"LLM 신뢰도가 낮습니다 ({decision.confidence:.2f}).")
            recommendations.append("규칙 기반 폴백 또는 사람 검토를 고려하세요.")

        return {
            'safe': len(violations) == 0,
            'violations': violations,
            'recommendations': recommendations
        }

    @staticmethod
    def _get_intervention_count_today(student_id: str) -> int:
        """
        오늘 학생에게 이루어진 개입 횟수 조회 (DB 또는 로그)
        """
        # TODO: Query from database or log
        return 0  # Placeholder
```

### 7.2 Rollback Mechanism

**Purpose**: Undo inappropriate interventions based on feedback.

**Rollback Policy** (from `knowledge/policies/rollback.yaml`):

```yaml
# rollback.yaml

rollback_triggers:
  student_negative_feedback:
    condition: "student reports 'too difficult' or 'not helpful'"
    action: revert_to_previous_state
    log: true

  performance_decline:
    condition: "accuracy drops by >20% after intervention"
    action: revert_intervention
    alert: true

  repeated_skips:
    condition: "student skips recommended content 3+ times"
    action: mark_intervention_as_ineffective
    disable_for: 7_days

rollback_history:
  retention_days: 30
  max_rollbacks_per_student: 5
```

**Rollback Implementation**:

```python
from typing import Dict, Any

class RollbackManager:
    """
    롤백 관리자
    """

    def __init__(self, rollback_policy_path: str):
        self.policy = self._load_policy(rollback_policy_path)
        self.rollback_history: Dict[str, List] = {}

    def _load_policy(self, path: str) -> Dict[str, Any]:
        """
        롤백 정책 로드
        """
        import yaml
        with open(path, 'r', encoding='utf-8') as f:
            return yaml.safe_load(f)

    def should_rollback(self, student_id: str, intervention_id: str, feedback: Dict[str, Any]) -> bool:
        """
        롤백 필요 여부 판단

        Args:
            student_id: 학생 ID
            intervention_id: 개입 ID
            feedback: 피드백 데이터 (성과 지표, 학생 응답 등)

        Returns:
            롤백 필요 시 True
        """
        # Check triggers
        for trigger_name, trigger_def in self.policy['rollback_triggers'].items():
            if self._evaluate_trigger(trigger_def['condition'], feedback):
                print(f"[ROLLBACK] Trigger '{trigger_name}' activated for student {student_id}")
                return True

        return False

    def execute_rollback(self, student_id: str, intervention_id: str):
        """
        롤백 실행

        Steps:
        1. 이전 상태 복구 (난이도, 진도, 추천 콘텐츠 등)
        2. 개입 효과 무효화
        3. 롤백 이력 기록
        """
        # Step 1: 이전 상태 조회
        previous_state = self._get_previous_state(student_id, intervention_id)

        # Step 2: 상태 복구
        self._restore_state(student_id, previous_state)

        # Step 3: 이력 기록
        self._record_rollback(student_id, intervention_id)

        print(f"[ROLLBACK] Intervention {intervention_id} rolled back for student {student_id}")

    def _evaluate_trigger(self, condition: str, feedback: Dict[str, Any]) -> bool:
        """
        트리거 조건 평가 (간단한 문자열 매칭)
        """
        # Simple keyword-based evaluation
        if 'too difficult' in condition and feedback.get('student_comment', '').lower().find('too difficult') >= 0:
            return True
        if 'accuracy drops' in condition and feedback.get('accuracy_drop', 0) > 0.2:
            return True
        if 'skips recommended content' in condition and feedback.get('skip_count', 0) >= 3:
            return True

        return False

    def _get_previous_state(self, student_id: str, intervention_id: str) -> Dict[str, Any]:
        """
        이전 상태 조회 (DB 또는 스냅샷)
        """
        # TODO: Query from database
        return {}

    def _restore_state(self, student_id: str, state: Dict[str, Any]):
        """
        상태 복구 (DB 업데이트)
        """
        # TODO: Update database
        pass

    def _record_rollback(self, student_id: str, intervention_id: str):
        """
        롤백 이력 기록
        """
        if student_id not in self.rollback_history:
            self.rollback_history[student_id] = []

        self.rollback_history[student_id].append({
            'intervention_id': intervention_id,
            'timestamp': 'YYYY-MM-DD HH:MM:SS',
            'reason': 'negative feedback or performance decline'
        })
```

---

## 8. Integration with Other Components

### 8.1 Integration with Knowledge Base (Doc 03)

**How Reasoning Engine Uses Knowledge Base**:

1. **Rule DSL Loading**: `RuleDSLParser` reads `knowledge/policies/rule_catalog.yaml`
2. **LLM Prompt Templates**: `ContextBuilder` loads `knowledge/guides/llm_decision_prompt.md`
3. **Action Definitions**: Decision results reference action IDs from `knowledge/actions/*.yaml`
4. **Safety Policies**: `SafetyGuardrails` loads `knowledge/policies/safety.yaml`

**Example Integration**:

```python
# In ReasoningEngine.__init__
self.rule_engine = RuleEngine('knowledge/policies/rule_catalog.yaml')
self.context_builder = ContextBuilder(ontology_api=None)
# Context builder internally loads 'knowledge/guides/llm_decision_prompt.md'
```

### 8.2 Integration with Ontology (Doc 04)

**How Reasoning Engine Queries Ontology**:

1. **Agent Task Retrieval**: Query which tasks an agent can perform
2. **Persona Information**: Fetch persona traits, behaviors, intervention preferences
3. **LMS Activity Mapping**: Map Mathking actions to Moodle API calls
4. **Collaboration Patterns**: Find multi-agent collaboration opportunities

**Example Ontology Query** (used in ContextBuilder):

```python
# In ContextBuilder._get_persona_info
def _get_persona_info(self, persona_type: str) -> Dict[str, Any]:
    """
    온톨로지에서 페르소나 정보 조회
    """
    from ontology_query_api import OntologyQueryAPI

    api = OntologyQueryAPI()
    persona = api.find_persona(persona_type)

    return {
        'type': persona_type,
        'traits': persona.traits,
        'typical_behaviors': persona.typical_behaviors,
        'intervention_preferences': persona.intervention_preferences
    }
```

### 8.3 Integration with Agents (Doc 01)

**How Reasoning Engine Receives Evidence**:

- **Input**: Agents send `EvidencePackage` objects to Reasoning Engine
- **Format**: Evidence contains metrics, state, context metadata
- **Processing**: Reasoning Engine evaluates evidence using rules and/or LLM

**Evidence Package Structure** (reminder from doc 01):

```python
EvidencePackage = {
    'agent_id': 'agent_curriculum',
    'student_id': 'student_12345',
    'timestamp': '2025-01-29T10:30:00Z',
    'category': 'academic_performance',
    'metrics': {
        'progress_delta': -0.18,
        'accuracy_rate': 0.55,
        'completion_rate': 0.70,
        'time_on_task': 120,
        'retry_count': 3
    },
    'state': {
        'affect': 'frustrated',
        'engagement': 'low'
    },
    'persona_type': 'perfectionist',
    'persona_similarity': 0.82
}
```

### 8.4 Integration with Collaboration System (Doc 02)

**How Reasoning Engine Coordinates Multi-Agent Interventions**:

- **Output**: Decision results can trigger `CollaborationPattern` activation
- **Mechanism**: If evidence matches collaboration trigger categories, activate multi-agent collaboration
- **Coordination**: Reasoning Engine sends task links to participating agents

**Example**:

```python
# After deciding, check if collaboration is needed
if decision.action == 'trigger_collaboration':
    collaboration_pattern = ontology_api.find_collaboration_pattern(
        evidence_categories=['academic_performance', 'emotional_state']
    )
    activate_collaboration(collaboration_pattern, student_id)
```

---

## 9. Performance Metrics

### 9.1 Latency Targets

| Decision Type | Target Latency | Max Acceptable |
|---------------|----------------|----------------|
| **Rule-based** | <10ms | <50ms |
| **LLM-based** | <2s | <5s |
| **Hybrid** | <2.5s | <6s |

### 9.2 Token Usage Targets

| Scenario | Target Tokens | Max Tokens |
|----------|---------------|------------|
| **Simple rule evaluation** | 0 | 0 |
| **LLM context preparation** | 1000-1500 | 3000 |
| **LLM inference** | 1500-2000 | 3000 |
| **Total per decision** | 2500-3500 | 6000 |

### 9.3 Decision Quality Metrics

**Quality Dimensions**:

1. **Accuracy**: 개입이 실제로 학습 성과 향상으로 이어지는가?
   - **Target**: 70% 이상의 개입이 긍정적 결과
   - **Measurement**: A/B testing, pre-post comparison

2. **Relevance**: 개입이 학생의 실제 필요와 일치하는가?
   - **Target**: 80% 이상의 학생이 개입을 유용하다고 평가
   - **Measurement**: 학생 설문, 피드백 분석

3. **Timeliness**: 개입이 적시에 이루어지는가?
   - **Target**: 90% 이상의 개입이 30분 이내에 전달
   - **Measurement**: Heartbeat 로그 분석

4. **Safety**: 개입이 학생에게 부정적 영향을 미치지 않는가?
   - **Target**: 위반 사례 <1%
   - **Measurement**: Safety guardrail violations 모니터링

---

## 10. Deployment Considerations

### 10.1 Environment Configuration

**Required Environment Variables**:

```bash
# LLM API Configuration
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
LLM_PROVIDER=openai_gpt4  # or anthropic_claude
LLM_MODEL_NAME=gpt-4-turbo  # or claude-3-opus-20240229

# Token Budget
DAILY_TOKEN_LIMIT=100000
PER_STUDENT_TOKEN_LIMIT=5000

# Cache Configuration
DECISION_CACHE_TTL_SECONDS=3600

# Safety
MAX_INTERVENTION_PER_DAY=3
ENABLE_SAFETY_GUARDRAILS=true

# Paths
KNOWLEDGE_BASE_PATH=/path/to/knowledge/
ONTOLOGY_PATH=/path/to/ontology/ontology.jsonld
```

### 10.2 Monitoring and Logging

**Key Metrics to Monitor**:

1. **Decision Latency**: Rule vs LLM vs Hybrid
2. **Token Usage**: Daily total, per-student, per-decision
3. **LLM API Errors**: Timeouts, rate limits, parsing failures
4. **Decision Quality**: Student feedback, outcome tracking
5. **Safety Violations**: Guardrail triggers, rollback frequency

**Logging Strategy**:

```python
import logging
import json

class ReasoningEngineLogger:
    """
    추론 엔진 로거
    """

    def __init__(self, log_file: str):
        self.logger = logging.getLogger('ReasoningEngine')
        handler = logging.FileHandler(log_file)
        formatter = logging.Formatter('%(asctime)s - %(levelname)s - %(message)s')
        handler.setFormatter(formatter)
        self.logger.addHandler(handler)
        self.logger.setLevel(logging.INFO)

    def log_decision(self, student_id: str, evidence: Dict[str, Any], decision: DecisionResult):
        """
        의사결정 로그 기록 (JSONL 형식)
        """
        log_entry = {
            'timestamp': 'YYYY-MM-DD HH:MM:SS',
            'student_id': student_id,
            'evidence_summary': self._summarize_evidence(evidence),
            'decision_type': decision.decision_type,
            'action': decision.action,
            'directive_strength': decision.directive_strength,
            'priority': decision.priority,
            'execution_time_ms': decision.execution_time_ms,
            'reasoning': decision.reasoning[:200]  # 처음 200자만
        }
        self.logger.info(json.dumps(log_entry, ensure_ascii=False))

    def _summarize_evidence(self, evidence: Dict[str, Any]) -> str:
        """
        증거 요약 (간결하게)
        """
        metrics = evidence.get('metrics', {})
        return f"progress={metrics.get('progress_delta')}, accuracy={metrics.get('accuracy_rate')}"
```

---

## 11. Testing Strategy

### 11.1 Unit Tests

**Test Coverage**:

1. **Rule DSL Parser**: Parse valid and invalid YAML
2. **Condition Evaluator**: Evaluate conditions with various contexts
3. **LLM Response Parser**: Parse well-formed and malformed responses
4. **Directive Strength Calculator**: Verify formula correctness
5. **Safety Guardrails**: Trigger safety violations

**Example Test**:

```python
import unittest

class TestConditionEvaluator(unittest.TestCase):
    def setUp(self):
        self.parser = RuleDSLParser('test_rule_catalog.yaml')
        self.parser.parse()
        self.evaluator = ConditionEvaluator(self.parser)

    def test_progress_below_avg_15(self):
        """
        조건 cond.progress.below_avg15 테스트
        """
        context = {
            'evidence': {
                'metrics': {
                    'progress_delta': -0.20  # 20% 뒤처짐
                }
            }
        }
        result = self.evaluator.evaluate('cond.progress.below_avg15', context)
        self.assertTrue(result)

    def test_progress_above_avg(self):
        """
        조건 cond.progress.below_avg15 테스트 (False case)
        """
        context = {
            'evidence': {
                'metrics': {
                    'progress_delta': 0.05  # 5% 앞서감
                }
            }
        }
        result = self.evaluator.evaluate('cond.progress.below_avg15', context)
        self.assertFalse(result)
```

### 11.2 Integration Tests

**Test Scenarios**:

1. **End-to-End Decision Flow**: Evidence → Rule → LLM → Decision
2. **Multi-Agent Collaboration Trigger**: Evidence matches collaboration pattern
3. **Safety Guardrail Activation**: Unsafe decision blocked
4. **Rollback Execution**: Negative feedback triggers rollback

**Example Integration Test**:

```python
def test_end_to_end_decision():
    """
    통합 테스트: 증거 입력 → 의사결정 출력
    """
    reasoning_engine = ReasoningEngine(
        rule_catalog_path='knowledge/policies/rule_catalog.yaml',
        llm_provider=LLMProvider.OPENAI_GPT4,
        llm_api_key='test_key',
        llm_model='gpt-4-turbo'
    )

    evidence = {
        'student_id': 'test_student',
        'metrics': {
            'progress_delta': -0.25,
            'accuracy_rate': 0.50
        },
        'state': {'affect': 'frustrated'},
        'persona_type': 'perfectionist',
        'persona_similarity': 0.85
    }

    decision = reasoning_engine.decide('test_student', evidence)

    # Assertions
    assert decision is not None
    assert decision.decision_type in ['rule_based', 'llm_based', 'hybrid']
    assert 0.0 <= decision.directive_strength <= 1.0
    assert decision.execution_time_ms < 5000  # <5 seconds
```

---

## 12. Next Steps

After completing this Reasoning Engine specification, the next documents should be:

1. **06-INTEGRATION_ARCHITECTURE.md**:
   - Complete system integration design
   - How all 6 layers connect and communicate
   - Data flow diagrams
   - API specifications
   - Deployment architecture

2. **07-IMPLEMENTATION_ROADMAP.md**:
   - Phase-by-phase implementation guide
   - Milestones and deliverables
   - Team roles and responsibilities
   - Testing and validation checkpoints
   - Risk mitigation strategies

---

## 13. Glossary

| Term | Definition |
|------|------------|
| **Rule DSL** | Domain-Specific Language for defining decision rules in YAML |
| **Condition** | Boolean expression evaluated against evidence context |
| **LLM Reasoner** | Component that delegates ambiguous decisions to Large Language Model |
| **Directive Strength** | Intervention intensity (0.0 ~ 1.0) calculated based on multiple factors |
| **Context Builder** | Prepares rich, token-efficient context for LLM reasoning |
| **Safety Guardrails** | Rules that prevent unsafe or inappropriate interventions |
| **Rollback** | Mechanism to undo ineffective or harmful interventions |
| **Evidence Package** | Structured data containing student metrics, state, and context |
| **Decision Pipeline** | Complete flow from evidence input to decision output |

---

**End of Document**

**Version History**:
- v1.0.0 (2025-01-29): Initial specification with Rule Engine, LLM integration, decision pipeline, safety mechanisms
