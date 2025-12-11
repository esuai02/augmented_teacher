# AlphaTutor42 개입 시스템

## 개요

AI 튜터의 행동유형 42가지 체계를 기반으로 한 수학 수업 흐름 조절 시스템입니다.

## 구조

### 7개 대분류

1. **멈춤/대기 (Pause & Wait)** - 5개
   - 인지 부하 대기
   - 필기 동기화 대기
   - 사고 여백 제공
   - 감정 진정 대기
   - 자기 수정 대기

2. **재설명 (Repeat & Rephrase)** - 6개
   - 동일 반복
   - 강조점 이동 반복
   - 단계 분해
   - 역순 재구성
   - 연결고리 명시
   - 요약 압축

3. **전환 설명 (Alternative Explanation)** - 7개
   - 일상 비유
   - 시각화 전환
   - 구체적 수 대입
   - 극단적 예시
   - 반례 제시
   - 학생 언어 번역
   - 신체/동작 비유

4. **강조/주의환기 (Emphasis & Alerting)** - 5개
   - 핵심 반복 강조
   - 대비 강조
   - 톤/속도 변화
   - 시각적 마킹
   - 예고 신호

5. **질문/탐색 (Questioning & Probing)** - 7개
   - 확인 질문
   - 예측 질문
   - 역질문
   - 선택지 질문
   - 힌트 질문
   - 연결 질문
   - 메타인지 질문

6. **즉시 개입 (Immediate Intervention)** - 6개
   - 즉시 교정
   - 부분 인정 확장
   - 함께 완성
   - 되물어 확인
   - 오개념 즉시 분리
   - 실시간 시범

7. **정서 조절 (Emotional Regulation)** - 6개
   - 노력 인정
   - 정상화
   - 난이도 조정 예고
   - 작은 성공 만들기
   - 유머/가벼운 전환
   - 선택권 부여

## 사용 방법

### 1. 개입 활동 조회

```php
require_once('includes/intervention_manager.php');

$interventionManager = new InterventionManager();

// 모든 개입 활동 조회
$allInterventions = $interventionManager->getAllInterventions();

// 카테고리별 조회
$pauseWaitInterventions = $interventionManager->getInterventionsByCategory('pause_wait');

// 페르소나별 조회
$personaInterventions = $interventionManager->getInterventionsByPersona('P001');
```

### 2. 트리거 신호 기반 개입 선택

```php
// 학생의 신호 추출
$signals = ['되묻기', '부분적 이해', '막연한 모름'];

// 페르소나 ID (선택사항)
$personaId = 'P001';

// 적절한 개입 활동 선택
$selectedInterventions = $interventionManager->selectInterventionBySignals($signals, $personaId);

// 상위 3개가 점수 순으로 반환됨
foreach ($selectedInterventions as $item) {
    echo $item['intervention']['name'] . " (점수: " . $item['score'] . ")\n";
}
```

### 3. 개입 활동 실행 기록

```php
// 개입 활동 실행 기록
$interventionManager->logInterventionExecution(
    'INT_2_1',  // activity_id
    $studentId,  // 학생 ID
    [
        'user_input' => '학생 입력',
        'persona_id' => 'P001'
    ]
);
```

### 4. API 사용

```javascript
// 모든 개입 활동 조회
fetch('/api/intervention_api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        action: 'get_all_interventions'
    })
});

// 트리거 신호 기반 선택
fetch('/api/intervention_api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        action: 'select_intervention',
        signals: ['되묻기', '부분적 이해'],
        persona_id: 'P001'
    })
});
```

## 페르소나 매핑

각 개입 활동은 특정 페르소나와 매핑되어 있습니다:

- **P001**: 막힘-회피형
- **P002**: 확인요구형
- **P003**: 감정출렁형
- **P004**: 빠른데 허술형
- **P005**: 집중 튐형
- **P006**: 패턴추론형
- **P007**: 최대한 쉬운길 찾기형
- **P008**: 불안과몰입형
- **P009**: 추상-언어 약함형
- **P010**: 상호작용 의존형
- **P011**: 무기력·저동기형
- **P012**: 메타인지 고수형

## 트리거 신호

각 개입 활동은 특정 학생 신호에 반응합니다:

- **되묻기**: "네?", "다시요?"
- **부분적 이해**: "앞부분은 알겠는데..."
- **막연한 모름**: "모르겠어요"
- **이유 모름**: "왜 이렇게 되는지 모르겠어요"
- **변수 두려움**: "x가 뭔데요"
- **자책**: "나만 못해요"
- **자기 수정 시도**: "아 잠깐..."
- **핵심 파악 못함**: "그래서 뭐가 중요한 거예요?"

## 자동 통합

`PersonaBasedTutoring` 클래스에 자동으로 통합되어 있습니다:

1. 학생 입력에서 트리거 신호 자동 추출
2. 현재 페르소나 기반 개입 활동 선택
3. 선택된 개입 활동을 응답에 자동 적용
4. 개입 활동 실행 기록 자동 저장

## 데이터 구조

### intervention_activities 테이블

```json
{
    "activity_id": "INT_1_1",
    "category": "pause_wait",
    "name": "인지 부하 대기",
    "description": "설명을 멈추고 3~5초 침묵, 처리 시간 확보",
    "trigger_signals": ["눈 깜빡임 증가", "시선 고정", "멍한 표정"],
    "persona_mapping": ["P001", "P005", "P009"],
    "metadata": {
        "duration": "3-5초",
        "priority": 1
    }
}
```

### intervention_executions 테이블

```json
{
    "activity_id": "INT_1_1",
    "student_id": 123,
    "executed_at": "2024-01-01 12:00:00",
    "context": {
        "user_input": "학생 입력",
        "persona_id": "P001"
    },
    "effectiveness": 0.5,
    "metadata": {}
}
```

## 우선순위

각 개입 활동은 우선순위(priority)를 가지고 있습니다:

- **1**: 높은 우선순위 (즉시 적용)
- **2**: 중간 우선순위
- **3**: 낮은 우선순위 (보조적)

선택 알고리즘은 다음을 고려합니다:

1. 트리거 신호 매칭 (3점)
2. 페르소나 매칭 (5점)
3. 우선순위 (4-priority 점)

## 확장

새로운 개입 활동을 추가하려면:

1. `InterventionManager::getDefaultInterventions()`에 추가
2. 또는 FileDB에 직접 INSERT

```php
$db->insert('intervention_activities', [
    'activity_id' => 'INT_8_1',
    'category' => 'new_category',
    'name' => '새로운 개입',
    'description' => '설명',
    'trigger_signals' => ['신호1', '신호2'],
    'persona_mapping' => ['P001'],
    'metadata' => ['priority' => 1]
]);
```

