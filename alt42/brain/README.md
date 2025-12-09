# 🧠 Brain Layer - 양자 판단 엔진

> **실시간 AI 튜터의 핵심: 언제 개입할지 결정하는 뇌**

## 📁 폴더 구조 (Phase 0-3 완료)

```
brain/
├── README.md                    # 이 파일
│
│   === API Endpoints ===
├── brain_api.php                # REST API ✅
├── brain_stream_api.php         # SSE 스트리밍 API ✅
├── brain_demo.php               # 데모/테스트 UI ✅
│
│   === Core Components ===
├── QuantumDecisionEngine.php    # 양자 판단 엔진 ✅
├── StateCollector.php           # 상태 수집기 ✅
├── WavefunctionCalculator.php   # 13종 파동함수 계산 ✅
│
│   === Integration Layer ===
├── RealtimeTutor.php            # 통합 컨트롤러 ✅
├── BrainAgentBridge.php         # 에이전트 브릿지 ✅
│
│   === Streaming & Real-time ===
├── StreamingPipeline.php        # LLM 스트리밍 ✅
├── BackchannelEngine.php        # 추임새 엔진 ✅
├── InterruptionHandler.php      # 끼어들기 핸들러 ✅
│
│   === Client Library ===
└── brain_client.js              # JavaScript 클라이언트 ✅
```

## 🎯 핵심 개념

### 양자 역학 비유

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│  학생의 인지 상태 = 양자 중첩 상태                               │
│                                                                 │
│  "이해함" ─┬─ 중첩 ─┬─ "이해 못함"                               │
│           │       │                                            │
│           ▼       ▼                                            │
│     튜터 개입(관측) → 상태 붕괴 → 확정                           │
│                                                                 │
│  핵심: 너무 빠른 개입 = 자기 발견 기회 박탈                       │
│       너무 느린 개입 = 좌절 및 이탈                              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 붕괴 확률 (CP - Collapse Probability)

```php
// CP(t) = f(혼란도, 에너지, 시간, 이탈위험)
$cp = (
    0.25 * $confusion +      // 혼란이 높으면 개입 필요
    0.20 * (1 - $energy) +   // 에너지가 낮으면 개입 필요
    0.25 * $dropout +        // 이탈 위험이 높으면 개입 필요
    0.15 * $timeDecay +      // 시간이 지나면 개입 필요
    0.15 * (1 - $affect)     // 부정적 감정이면 개입 필요
);
```

### 개입 결정 임계값

| CP 범위 | 결정 | 에이전트 |
|--------|------|---------|
| 0.7 ~ 1.0 | **즉시 개입** | Agent 20, 21, 16, 19 |
| 0.4 ~ 0.7 | 미세 힌트 | Agent 21 |
| 0.2 ~ 0.4 | 관찰 (추임새 가능) | - |
| 0.0 ~ 0.2 | 개입 금지 | - |

## 📊 13종 파동함수

### 코어 상태
| 파동함수 | 설명 | 계산 요소 |
|---------|------|----------|
| ψ_Core | 핵심 개념 이해 | 정답률, 메타인지 |
| ψ_Align | 목표 정렬도 | 진도, 세션 진행 |

### 감정 상태
| 파동함수 | 설명 | 계산 요소 |
|---------|------|----------|
| ψ_Affect | 감정 상태 | Valence, 좌절, 불안, 자신감 |
| ψ_Trust | 튜터 신뢰도 | 상호작용 이력, 세션 시간 |

### 인지 상태
| 파동함수 | 설명 | 계산 요소 |
|---------|------|----------|
| ψ_WM | 작업 기억 (집중) | 비활성 시간, 인지 부하 |
| ψ_Schema | 스키마 활성화 | 정답률, 시도 횟수 |
| ψ_Transfer | 전이 가능성 | 이해도, 정답률 |

### 동기 상태
| 파동함수 | 설명 | 계산 요소 |
|---------|------|----------|
| ψ_Reward | 보상 기대 | 자신감, 최근 성공 |
| ψ_Aha | "아하!" 임박 | 혼란+에너지+비포기 |
| ψ_Flow | 몰입 상태 | 감정, 집중, 침착, 이탈 |

### 위험 상태
| 파동함수 | 설명 | 계산 요소 |
|---------|------|----------|
| ψ_Dropout | 이탈 위험 | Agent13 연동 |
| ψ_Confusion | 혼란도 | 인지부하, 정답률, 불안 |
| ψ_Tunnel | 터널링 가능성 | 메타인지, 자기효능감, 동기 |

## 🔧 사용법

### 기본 사용

```php
require_once(__DIR__ . '/brain/QuantumDecisionEngine.php');

$engine = QuantumDecisionEngine::getInstance();
$decision = $engine->decide($studentId);

if ($decision->type === 'intervene') {
    // 즉시 개입 실행
    foreach ($decision->agents as $agentId) {
        InterAgentBus::send($agentId, 'intervene', $decision->style);
    }
} elseif ($decision->type === 'micro_hint') {
    // 미세 힌트 제공
    $hint = LLMClient::quickResponse("작은 힌트를 주세요", 'tutor');
    TTSClient::synthesize($hint, $decision->style);
}
```

### 디버그 모드

```php
$debug = $engine->getDebugInfo($studentId);

echo "현재 상태: " . json_encode($debug['state'], JSON_PRETTY_PRINT);
echo "파동함수: " . json_encode($debug['wavefunctions'], JSON_PRETTY_PRINT);
echo "결정: " . json_encode($debug['decision'], JSON_PRETTY_PRINT);
```

### 파동함수 개별 조회

```php
$calc = WavefunctionCalculator::getInstance();
$state = StateCollector::getInstance()->collectRealtime($studentId);

$affect = $calc->calculate('psi_affect', $state);
$confusion = $calc->calculate('psi_confusion', $state);

echo "감정 상태: {$affect}";
echo "혼란도: {$confusion}";
```

## 🔗 에이전트 연동

### 데이터 소스

```
StateCollector ← Agent 05 (감정)
             ← Agent 08 (침착도)
             ← Agent 13 (이탈 위험)
             ← Agent 14 (현재 위치)
             ← DB (문제 풀이 기록)
```

### 출력 대상

```
QuantumDecisionEngine → Agent 20 (개입 준비)
                     → Agent 21 (개입 실행)
                     → Agent 16, 19 (컨텐츠 생성)
```

## 📈 Phase별 확장 계획

### Phase 1 (현재)
- [x] StateCollector
- [x] WavefunctionCalculator
- [x] QuantumDecisionEngine

### Phase 2
- [x] Mind Layer 연결 (LLM 프롬프트 생성) ✅
- [x] Mouth Layer 연결 (TTS 스타일 적용) ✅

### Phase 3
- [ ] BackchannelEngine (추임새 강화)
- [ ] StreamingPipeline (지연 제로)
- [ ] Interruption (끼어들기)

---

## 🌐 HTTP API 사용법

### API 엔드포인트

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/brain/brain_api.php
```

### 1. 세션 시작

```bash
curl -X POST "brain_api.php?action=start" \
  -H "Content-Type: application/json" \
  -d '{"student_id": 123, "mode": "guide"}'
```

### 2. 실시간 판단 (Polling)

```bash
curl -X POST "brain_api.php?action=tick" \
  -H "Content-Type: application/json" \
  -d '{"event": {"type": "mouse_idle", "data": {}}}'
```

### 3. 현재 상태 조회

```bash
curl "brain_api.php?action=state&student_id=123"
```

### 4. 파동함수 조회

```bash
curl "brain_api.php?action=wavefunctions&student_id=123"
```

### 5. TTS 테스트

```bash
curl -X POST "brain_api.php?action=test_tts" \
  -H "Content-Type: application/json" \
  -d '{"text": "안녕하세요!", "tone": "excited"}'
```

### JavaScript 예제

```javascript
// 실시간 튜터 초기화
async function initTutor(studentId) {
    const response = await fetch('/brain_api.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: studentId, mode: 'guide' })
    });
    return await response.json();
}

// 폴링 루프
async function tutorTick(event) {
    const response = await fetch('/brain_api.php?action=tick', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ event })
    });
    const result = await response.json();
    
    if (result.success && result.data.action === 'speak') {
        // 음성 재생
        playAudio(result.data.audio);
    }
    
    return result;
}

// 음성 재생
function playAudio(base64Audio) {
    const audio = new Audio('data:audio/mp3;base64,' + base64Audio);
    audio.play();
}
```

---

**생성일**: 2025-12-08  
**버전**: 1.1.0 (Phase 1 완료)

