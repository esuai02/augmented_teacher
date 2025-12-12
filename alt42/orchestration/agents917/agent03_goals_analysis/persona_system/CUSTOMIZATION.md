# Agent03 Goals Analysis Customization Guide
# 목표 분석 페르소나 커스터마이징 가이드

**Version**: 1.0
**Last Updated**: 2025-12-02

---

## 목차

1. [개요](#1-개요)
2. [페르소나 커스터마이징](#2-페르소나-커스터마이징)
3. [템플릿 커스터마이징](#3-템플릿-커스터마이징)
4. [컨텍스트 규칙 수정](#4-컨텍스트-규칙-수정)
5. [위기 감지 설정](#5-위기-감지-설정)
6. [어조(Tone) 확장](#6-어조tone-확장)
7. [개입 패턴 추가](#7-개입-패턴-추가)
8. [다국어 지원](#8-다국어-지원)
9. [고급 설정](#9-고급-설정)

---

## 1. 개요

### 1.1 커스터마이징 가능 요소

| 요소 | 파일 | 난이도 | 영향 범위 |
|------|------|--------|----------|
| 응답 템플릿 | `templates/goal_templates.php` | ⭐ 쉬움 | 응답 텍스트 |
| 컨텍스트 키워드 | `rules.yaml` | ⭐⭐ 보통 | 상황 감지 |
| 페르소나 추가 | `templates/` + `rules.yaml` | ⭐⭐ 보통 | 대화 스타일 |
| 위기 감지 키워드 | `api/goals_chat.php` | ⭐⭐⭐ 주의 | 위기 대응 |
| 엔진 로직 | `engine/Agent03PersonaEngine.php` | ⭐⭐⭐⭐ 고급 | 전체 시스템 |

### 1.2 수정 전 체크리스트

```
□ 백업 생성 완료
□ 테스트 환경에서 먼저 테스트
□ 관련 문서(personas.md, contextlist.md) 업데이트 계획
□ 변경 로그 기록 준비
```

---

## 2. 페르소나 커스터마이징

### 2.1 기존 페르소나 수정

**파일**: `templates/goal_templates.php`

```php
// 기존 페르소나 템플릿 수정
'G1_P2_achievement' => [
    'tone' => 'Encouraging',        // 어조 변경 가능
    'intervention' => 'EmotionalSupport',  // 개입 패턴 변경 가능
    'templates' => [
        // 템플릿 메시지 수정
        '{{user_name}}{{honorific}}, 정말 대단해요! 🎉 목표를 달성하셨군요!',
        '축하드려요! {{goal_title}} 목표를 성공적으로 이루셨네요.',
        // 새 템플릿 추가
        '{{user_name}}{{honorific}}의 노력이 빛을 발했어요! 자랑스러워요!',
    ]
],
```

### 2.2 새 페르소나 추가

#### Step 1: 템플릿 정의

```php
// templates/goal_templates.php 에 추가

// 새로운 G1 페르소나: 데이터 분석가
'G1_P6_data_analyst' => [
    'tone' => 'Analytical',
    'intervention' => 'InformationProvision',
    'templates' => [
        '{{user_name}}{{honorific}}, 현재 진행률 {{progress}}%입니다. ' .
        '지난 주 대비 {{weekly_change}}% {{change_direction}}했어요.',

        '데이터를 분석해 보니, {{user_name}}{{honorific}}은 ' .
        '{{best_day}}에 가장 활발하게 학습하시네요.',

        '목표 달성까지 {{days_left}}일 남았고, 현재 페이스라면 ' .
        '{{estimated_completion}} 완료 예상입니다.',
    ],
    'conditions' => [
        'data_available' => true,
        'user_preference' => 'data_driven'
    ]
],
```

#### Step 2: rules.yaml에 등록

```yaml
# rules.yaml 에 추가

personas:
  G1:
    - id: G1_P6
      name: "데이터 기반 분석가"
      description: "수치와 데이터로 진행 상황을 분석하는 페르소나"
      tone: Analytical
      intervention: InformationProvision
      conditions:
        - "사용자가 데이터 기반 피드백 선호"
        - "충분한 활동 데이터 존재"
      priority: 6
```

#### Step 3: personas.md 문서 업데이트

```markdown
### G1_P6: 데이터 기반 분석가 (Data Analyst)

**역할**: 수치와 통계로 목표 진행 상황을 객관적으로 분석

**활성화 조건**:
- 사용자가 데이터 기반 피드백 선호 설정
- 최소 7일 이상의 활동 데이터 존재

**어조**: Analytical (분석적, 객관적)

**개입 패턴**: InformationProvision

**대표 응답**:
> "현재 진행률 67%입니다. 지난 주 대비 12% 상승했어요."
```

### 2.3 페르소나 비활성화

```yaml
# rules.yaml 에서 비활성화

personas:
  G1:
    - id: G1_P3
      name: "속도 조절자"
      enabled: false  # 비활성화
      # ... 나머지 설정
```

---

## 3. 템플릿 커스터마이징

### 3.1 템플릿 변수

#### 사용 가능한 변수

| 변수 | 설명 | 예시 값 |
|------|------|--------|
| `{{user_name}}` | 사용자 이름 | 김철수 |
| `{{honorific}}` | 경칭 | 님, 학생 |
| `{{goal_title}}` | 목표 제목 | 수학 90점 |
| `{{progress}}` | 진행률 (%) | 45 |
| `{{days_left}}` | 남은 일수 | 30 |
| `{{target_date}}` | 목표일 | 2025-03-01 |
| `{{current_value}}` | 현재 값 | 75 |
| `{{target_value}}` | 목표 값 | 90 |
| `{{category}}` | 목표 카테고리 | 학업 |
| `{{streak_days}}` | 연속 학습일 | 7 |

#### 커스텀 변수 추가

**엔진 파일 수정** (`engine/Agent03PersonaEngine.php`):

```php
protected function getTemplateVariables($userId, $goalData) {
    $vars = parent::getTemplateVariables($userId, $goalData);

    // 커스텀 변수 추가
    $vars['weekly_change'] = $this->calculateWeeklyChange($goalData);
    $vars['best_day'] = $this->findBestPerformanceDay($userId);
    $vars['motivation_quote'] = $this->getRandomMotivationQuote();

    return $vars;
}

private function calculateWeeklyChange($goalData) {
    // 주간 변화율 계산 로직
    return round($goalData['weekly_progress_delta'], 1);
}
```

### 3.2 조건부 템플릿

```php
'G1_P1_progress' => [
    'tone' => 'Professional',
    'intervention' => 'InformationProvision',
    'templates' => [
        // 기본 템플릿
        'default' => '{{user_name}}{{honorific}}, {{goal_title}} 목표의 진행률은 {{progress}}%입니다.',

        // 조건부 템플릿
        'high_progress' => [
            'condition' => 'progress >= 80',
            'template' => '{{user_name}}{{honorific}}, 거의 다 왔어요! {{progress}}% 달성!'
        ],
        'low_progress' => [
            'condition' => 'progress < 30',
            'template' => '{{user_name}}{{honorific}}, 아직 {{progress}}%지만 괜찮아요. 함께 해봐요!'
        ],
        'stagnant' => [
            'condition' => 'days_since_activity > 7',
            'template' => '{{user_name}}{{honorific}}, 잠시 쉬고 계셨군요. 다시 시작해볼까요?'
        ]
    ]
],
```

### 3.3 이모지 및 포맷팅

```php
'G1_P2_achievement' => [
    'tone' => 'Encouraging',
    'intervention' => 'EmotionalSupport',
    'templates' => [
        // 이모지 사용
        '🎉 축하해요 {{user_name}}{{honorific}}! 목표 달성!',

        // 줄바꿈 포맷팅
        "{{user_name}}{{honorific}}, 대단해요!\n\n" .
        "✅ 목표: {{goal_title}}\n" .
        "📊 달성률: {{progress}}%\n" .
        "📅 소요 기간: {{days_taken}}일",

        // 강조 포맷팅
        "**{{goal_title}}** 목표를 완수하셨습니다! 👏",
    ]
],
```

---

## 4. 컨텍스트 규칙 수정

### 4.1 키워드 추가/수정

**파일**: `rules.yaml`

```yaml
contexts:
  G0:
    name: "목표 설정"
    keywords:
      # 기존 키워드
      - "세우고 싶"
      - "목표를 정하"
      # 새 키워드 추가
      - "올해 계획"
      - "다짐을 하고"
      - "새해 목표"
      - "버킷리스트"

    # 제외 키워드 (false positive 방지)
    exclude_keywords:
      - "목표를 달성"  # G1으로 분류되어야 함
      - "목표가 힘들"  # G2로 분류되어야 함

  G2:
    name: "정체/위기"
    keywords:
      - "못하겠"
      - "힘들어"
      # 학업 특화 키워드 추가
      - "성적이 안 올라"
      - "공부가 싫"
      - "시험 망했"
```

### 4.2 컨텍스트 우선순위 조정

```yaml
context_detection:
  # 우선순위 순서 (앞이 높음)
  priority_order:
    - CRISIS    # 항상 최우선
    - G2        # 위기/정체 우선 감지
    - G0        # 목표 설정
    - G3        # 재설정
    - G1        # 기본 진행 (fallback)

  # 신뢰도 임계값
  confidence_threshold: 0.6

  # 다중 컨텍스트 감지 시 처리
  multi_context_strategy: "highest_priority"  # 또는 "highest_confidence"
```

### 4.3 서브 컨텍스트 정의

```yaml
contexts:
  G1:
    name: "목표 진행"
    sub_contexts:
      G1.1:
        name: "순조로운 진행"
        indicators:
          - progress_rate: ">= expected"
          - emotional_state: "positive"
        default_persona: "G1_P5"

      G1.2:
        name: "마일스톤 달성"
        indicators:
          - milestone_reached: true
        default_persona: "G1_P2"

      G1.3:
        name: "진행률 확인 요청"
        indicators:
          - intent: "check_progress"
        default_persona: "G1_P1"

      G1.4:
        name: "과도한 속도"
        indicators:
          - progress_rate: "> 150% of expected"
          - burnout_risk: true
        default_persona: "G1_P3"
```

---

## 5. 위기 감지 설정

### 5.1 키워드 수정

**파일**: `api/goals_chat.php`

```php
function checkCrisisSignals($message) {
    $crisisKeywords = [
        // Level 0: 즉시 개입 (가장 심각)
        'level_0' => [
            '죽고 싶',
            '자살',
            '자해',
            '사라지고 싶',
            '끝내고 싶',
            '살기 싫',
            // 추가 키워드
            '죽어버릴',
            '세상 떠나',
        ],

        // Level 1: 긴급 (높은 위험)
        'level_1' => [
            '못 견디겠',
            '미치겠',
            '무너질 것 같',
            '너무 힘들',
            '더 이상 못',
            // 추가 키워드
            '한계야',
            '견딜 수 없',
        ],

        // Level 2: 주의 (중간 위험)
        'level_2' => [
            '아무도 없',
            '혼자야',
            '외로워',
            '이해 못 해',
            '소용없어',
        ],

        // Level 3: 관찰 (낮은 위험)
        'level_3' => [
            '힘들어',
            '지쳤어',
            '스트레스',
            '우울해',
            '불안해',
        ]
    ];

    // ... 감지 로직
}
```

### 5.2 위기 응답 커스터마이징

```php
function generateCrisisResponse($crisisResult, $userId) {
    $responses = [
        'level_0' => [
            'text' => "지금 많이 힘드시군요. 당신의 안전이 가장 중요해요.\n\n" .
                     "📞 자살예방상담전화: 1393 (24시간)\n" .
                     "📞 정신건강위기상담전화: 1577-0199\n" .
                     "📞 생명의전화: 1588-9191\n\n" .  // 추가 연락처
                     "언제든 이야기 나눌 준비가 되어 있어요.",
            'persona' => 'CRISIS_P1',
            'tone' => 'Calm',
            'intervention' => 'CrisisIntervention',
            // 추가 액션
            'notify_admin' => true,
            'priority' => 'critical'
        ],
        // ... 다른 레벨
    ];
}
```

### 5.3 위기 감지 민감도 조정

```yaml
# rules.yaml

crisis_detection:
  enabled: true

  # 민감도 설정
  sensitivity:
    level_0: 0.95  # 매우 높은 신뢰도에서만 감지
    level_1: 0.85
    level_2: 0.70
    level_3: 0.60

  # 문맥 고려 (false positive 방지)
  context_aware: true
  exclude_patterns:
    - "드라마에서 죽고 싶다고"  # 인용
    - "게임 캐릭터가"           # 게임 관련

  # 알림 설정
  notifications:
    level_0: ["admin", "counselor", "log"]
    level_1: ["counselor", "log"]
    level_2: ["log"]
    level_3: ["log"]
```

---

## 6. 어조(Tone) 확장

### 6.1 기본 어조 스타일

| 어조 | 설명 | 사용 상황 |
|------|------|----------|
| Professional | 전문적, 객관적 | 정보 제공, 분석 |
| Warm | 따뜻한, 친근한 | 일상적 대화, 격려 |
| Encouraging | 응원하는, 긍정적 | 성취 축하, 동기 부여 |
| Calm | 차분한, 안정적 | 위기 상황, 불안 시 |
| Empathetic | 공감하는, 이해하는 | 감정적 어려움 |
| Direct | 직접적, 명확한 | 중요한 피드백 |
| Playful | 유쾌한, 가벼운 | 가벼운 상황, 젊은 사용자 |

### 6.2 새 어조 추가

```php
// engine/Agent03PersonaEngine.php

protected $toneStyles = [
    // 기존 어조
    'Professional' => [
        'formality' => 'high',
        'emoji_usage' => 'minimal',
        'sentence_style' => 'declarative'
    ],

    // 새 어조 추가
    'Analytical' => [
        'formality' => 'high',
        'emoji_usage' => 'none',
        'sentence_style' => 'data_driven',
        'features' => ['numbers', 'percentages', 'comparisons']
    ],

    'Motivational' => [
        'formality' => 'medium',
        'emoji_usage' => 'moderate',
        'sentence_style' => 'imperative',
        'features' => ['action_verbs', 'future_focus', 'can_do']
    ],

    'Storytelling' => [
        'formality' => 'low',
        'emoji_usage' => 'moderate',
        'sentence_style' => 'narrative',
        'features' => ['anecdotes', 'metaphors', 'examples']
    ]
];
```

### 6.3 사용자별 어조 선호도

```php
// 사용자 선호 어조 저장/조회
function getUserTonePreference($userId) {
    global $DB;

    $pref = $DB->get_field('at_user_preferences', 'value',
        ['userid' => $userId, 'name' => 'preferred_tone']);

    return $pref ?: 'Warm';  // 기본값
}

function setUserTonePreference($userId, $tone) {
    global $DB;

    $existing = $DB->get_record('at_user_preferences',
        ['userid' => $userId, 'name' => 'preferred_tone']);

    if ($existing) {
        $DB->update_record('at_user_preferences', [
            'id' => $existing->id,
            'value' => $tone,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        $DB->insert_record('at_user_preferences', [
            'userid' => $userId,
            'name' => 'preferred_tone',
            'value' => $tone,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
```

---

## 7. 개입 패턴 추가

### 7.1 기존 개입 패턴

| 패턴 | 설명 | 적용 상황 |
|------|------|----------|
| EmotionalSupport | 정서적 지지 제공 | 감정적 어려움 |
| InformationProvision | 정보 및 데이터 제공 | 진행 상황 확인 |
| SkillBuilding | 기술/습관 형성 지원 | 학습 방법 개선 |
| BehaviorModification | 행동 변화 유도 | 습관 교정 |
| SafetyNet | 안전망 연결 | 외부 자원 필요 시 |
| PlanDesign | 계획 수립 지원 | 목표 설계 |
| AssessmentDesign | 평가 기준 수립 | SMART 목표 설정 |
| GapAnalysis | 현재-목표 격차 분석 | 현실적 목표 조정 |
| GoalSetting | 목표 설정 가이드 | 새 목표 수립 |
| CrisisIntervention | 위기 개입 | 긴급 상황 |

### 7.2 새 개입 패턴 정의

```yaml
# rules.yaml

intervention_patterns:
  # 새 패턴 추가
  Gamification:
    description: "게임화 요소로 동기 부여"
    strategies:
      - "포인트/뱃지 시스템 활용"
      - "레벨업 개념 적용"
      - "미니 챌린지 제안"
    applicable_contexts: ["G1", "G2"]
    target_users: ["young", "game_preference"]

  PeerComparison:
    description: "또래 비교를 통한 동기 부여"
    strategies:
      - "익명 통계 제공"
      - "랭킹 정보 공유"
      - "성공 사례 소개"
    applicable_contexts: ["G1", "G2"]
    caution: "부정적 비교 주의"

  MicroGoals:
    description: "작은 목표로 분할"
    strategies:
      - "큰 목표를 작은 단위로 분해"
      - "일일/주간 미니 목표 설정"
      - "즉각적 성취감 제공"
    applicable_contexts: ["G0", "G2", "G3"]
```

### 7.3 개입 패턴 적용

```php
// templates/goal_templates.php

'G2_P6_gamification' => [
    'tone' => 'Playful',
    'intervention' => 'Gamification',
    'templates' => [
        '{{user_name}}{{honorific}}, 오늘의 미션이 있어요! 🎮\n' .
        '✨ 미션: {{mini_goal}}\n' .
        '🏆 보상: {{reward_points}} 포인트\n' .
        '도전해볼래요?',

        '레벨업 기회에요! 🚀\n' .
        '현재: Level {{current_level}}\n' .
        '{{mini_goal}} 완료하면 Level {{next_level}}!',
    ]
],
```

---

## 8. 다국어 지원

### 8.1 언어별 템플릿 구조

```php
// templates/goal_templates.php

class Agent03ResponseTemplates {
    private $templates = [];
    private $language = 'ko';  // 기본 언어

    public function __construct($language = 'ko') {
        $this->language = $language;
        $this->loadTemplates();
    }

    private function loadTemplates() {
        $templateFile = __DIR__ . "/lang/{$this->language}/templates.php";

        if (file_exists($templateFile)) {
            $this->templates = include $templateFile;
        } else {
            // 기본 한국어 템플릿
            $this->templates = include __DIR__ . "/lang/ko/templates.php";
        }
    }
}
```

### 8.2 영어 템플릿 예시

**파일**: `templates/lang/en/templates.php`

```php
<?php
return [
    'G0_P5_balanced' => [
        'tone' => 'Balanced',
        'intervention' => 'PlanDesign',
        'templates' => [
            "Hi {{user_name}}, let's set a meaningful goal together! " .
            "What area would you like to focus on?",

            "Setting goals is a great start, {{user_name}}! " .
            "What would you like to achieve this semester?",
        ]
    ],

    'G1_P2_achievement' => [
        'tone' => 'Encouraging',
        'intervention' => 'EmotionalSupport',
        'templates' => [
            "Congratulations {{user_name}}! 🎉 You've reached your goal!",

            "Amazing work, {{user_name}}! You've achieved {{goal_title}}!",
        ]
    ],

    // 위기 응답
    'CRISIS_level_0' => [
        'text' => "I hear you're going through a really difficult time. " .
                 "Your safety matters most.\n\n" .
                 "📞 National Suicide Prevention: 988\n" .
                 "📞 Crisis Text Line: Text HOME to 741741\n\n" .
                 "I'm here whenever you need to talk.",
    ],
];
```

### 8.3 언어 감지 및 전환

```php
// api/goals_chat.php

function detectUserLanguage($userId, $message) {
    // 1. 사용자 설정 확인
    global $DB;
    $userLang = $DB->get_field('user', 'lang', ['id' => $userId]);

    if ($userLang && in_array($userLang, ['ko', 'en', 'ja', 'zh'])) {
        return $userLang;
    }

    // 2. 메시지 언어 감지 (간단한 휴리스틱)
    if (preg_match('/[\x{AC00}-\x{D7A3}]/u', $message)) {
        return 'ko';  // 한글 감지
    }
    if (preg_match('/[\x{3040}-\x{309F}]/u', $message)) {
        return 'ja';  // 히라가나 감지
    }
    if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $message)) {
        return 'zh';  // 한자 감지
    }

    return 'en';  // 기본값
}
```

---

## 9. 고급 설정

### 9.1 A/B 테스트 설정

```yaml
# rules.yaml

ab_testing:
  enabled: true
  experiments:
    - name: "tone_comparison"
      description: "Warm vs Encouraging 어조 비교"
      variants:
        A:
          personas: ["G1_P5"]  # Warm
          weight: 50
        B:
          personas: ["G1_P2"]  # Encouraging
          weight: 50
      metrics: ["response_satisfaction", "follow_up_rate"]
      duration: "2025-12-01 to 2025-12-31"

    - name: "emoji_usage"
      description: "이모지 사용 여부 비교"
      variants:
        A:
          emoji_enabled: true
          weight: 50
        B:
          emoji_enabled: false
          weight: 50
```

### 9.2 머신러닝 연동 준비

```php
// engine/Agent03PersonaEngine.php

class Agent03PersonaEngine extends AbstractPersonaEngine {

    // ML 모델 예측 결과 활용
    protected function selectPersonaWithML($context, $userState, $messageAnalysis) {
        // 1. 규칙 기반 후보 선정
        $candidates = $this->getRuleBasedCandidates($context, $userState);

        // 2. ML 예측 (외부 서비스 호출)
        $mlPrediction = $this->callMLService([
            'user_features' => $this->extractUserFeatures($userState),
            'message_features' => $this->extractMessageFeatures($messageAnalysis),
            'candidates' => $candidates
        ]);

        // 3. 규칙 + ML 점수 결합
        if ($mlPrediction && $mlPrediction['confidence'] > 0.7) {
            return $mlPrediction['persona_id'];
        }

        // 4. Fallback to rule-based
        return $this->selectPersona($context, $userState, $messageAnalysis);
    }

    private function callMLService($features) {
        // ML 서비스 API 호출 (향후 구현)
        // return $this->httpClient->post('ml-service/predict', $features);
        return null;  // 현재는 비활성화
    }
}
```

### 9.3 성능 모니터링 설정

```php
// 성능 메트릭 수집
function logPerformanceMetrics($startTime, $result, $userId) {
    global $DB;

    $metrics = [
        'userid' => $userId,
        'agent_id' => 'agent03',
        'processing_time_ms' => (microtime(true) - $startTime) * 1000,
        'context_detected' => $result['context']['detected'],
        'confidence' => $result['context']['confidence'],
        'persona_used' => $result['persona']['persona_id'],
        'response_source' => $result['response']['source'],
        'created_at' => date('Y-m-d H:i:s')
    ];

    $DB->insert_record('at_agent_performance_log', $metrics);
}
```

### 9.4 캐싱 설정

```php
// 템플릿 캐싱
class TemplateCache {
    private static $cache = [];
    private static $ttl = 3600;  // 1시간

    public static function get($key) {
        if (isset(self::$cache[$key])) {
            if (self::$cache[$key]['expires'] > time()) {
                return self::$cache[$key]['data'];
            }
            unset(self::$cache[$key]);
        }
        return null;
    }

    public static function set($key, $data) {
        self::$cache[$key] = [
            'data' => $data,
            'expires' => time() + self::$ttl
        ];
    }

    public static function clear() {
        self::$cache = [];
    }
}
```

---

## 변경 로그 템플릿

커스터마이징 시 다음 형식으로 변경 사항을 기록하세요:

```markdown
## 변경 로그

### [날짜] - [작성자]

**변경 유형**: 페르소나 추가 / 템플릿 수정 / 규칙 변경 / 기타

**변경 내용**:
- 상세 변경 사항 기술

**영향 범위**:
- 영향받는 컨텍스트/페르소나

**테스트 결과**:
- 테스트 시나리오 및 결과

**롤백 계획**:
- 문제 발생 시 롤백 방법
```

---

**파일 위치**: `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent03_goals_analysis/persona_system/CUSTOMIZATION.md`
