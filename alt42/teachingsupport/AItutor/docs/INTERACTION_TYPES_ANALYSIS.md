# AI 튜터 상호작용 유형 분석

> 현재 구현된 룰과 온톨로지를 분석하고, 상호작용 유형을 **구현 완료 / 개발 필요**로 분류

**문서 버전**: 1.0  
**최종 수정일**: 2025-11-26  
**분석 대상**: `rules/`, `ontology/`, `services/`

---

## 1. 개요

### 1.1 분석 대상 파일

| 파일 | 내용 | 항목 수 |
|------|------|---------|
| `rules/immediate_rules.php` | 즉각 반응 룰 (U0-U4) | 17개 |
| `rules/persona_rules.php` | 페르소나별 룰셋 | 12 × 3 = 36개 |
| `rules/intervention_mapping.php` | 개입 활동 매핑 | 42 + 7 = 49개 |
| `ontology/problem_ontology.php` | 문항별 온톨로지 | 4개 개념, 4개 문항 |
| `services/interaction_service.php` | 상호작용 서비스 | 핵심 서비스 |
| `services/context_service.php` | 컨텍스트 서비스 | 누적 관리 |
| `services/ontology_service.php` | 온톨로지 서비스 | 조회/추론 |
| `services/will_validator.php` | Will Layer 검증 | 제약 검사 |

### 1.2 상호작용 흐름

```
[입력] → [처리] → [출력]
   │         │         │
   ▼         ▼         ▼
트리거   룰/온톨로지   개입활동
신호     평가·추론    실행
```

---

## 2. 상호작용 입력 유형 (트리거)

### 2.1 현재 구현 완료

| 입력 유형 | 트리거 신호 | 관련 룰 | 구현 파일 | 상태 |
|-----------|-------------|---------|-----------|------|
| **필기 정지** | `pause_duration >= 3` | IMM_U1_R1, IMM_U1_R2 | `learning_interface.js` | ✅ 완료 |
| **반복 지우기** | `erase_count >= 3` | IMM_U1_R3 | `painter_test.php` | ✅ 완료 |
| **제스처: 체크** | `gesture_type = check` | IMM_U2_R1 | `learning_interface.js` | ✅ 완료 |
| **제스처: X** | `gesture_type = cross` | IMM_U2_R2 | `learning_interface.js` | ✅ 완료 |
| **제스처: ?** | `gesture_type = question` | IMM_U2_R3 | `learning_interface.js` | ✅ 완료 |
| **제스처: ○** | `gesture_type = circle` | IMM_U2_R4 | `learning_interface.js` | ✅ 완료 |
| **제스처: →** | `gesture_type = arrow` | IMM_U2_R5 | `learning_interface.js` | ✅ 완료 |
| **감정: 자신있음** | `emotion_type = confident` | IMM_U3_R1 | `learning_interface.js` | ✅ 완료 |
| **감정: 막힘** | `emotion_type = stuck` | IMM_U3_R2 | `learning_interface.js` | ✅ 완료 |
| **감정: 불안** | `emotion_type = anxious` | IMM_U3_R3 | `learning_interface.js` | ✅ 완료 |
| **감정: 헷갈림** | `emotion_type = confused` | IMM_U3_R4 | `learning_interface.js` | ✅ 완료 |
| **세션 시작** | `event_type = session_start` | IMM_U0_R1 | `interaction_service.php` | ✅ 완료 |
| **단계 완료** | `step_status = completed` | IMM_U0_R2 | `interaction_service.php` | ✅ 완료 |
| **필기 지연 분석** | `pause_duration >= 5` + 캡처 | - | `analyze_writing.php` | ✅ 완료 |

### 2.2 개발 필요

| 입력 유형 | 트리거 신호 | 관련 룰 | 필요 기술 | 우선순위 |
|-----------|-------------|---------|-----------|----------|
| **필기 속도** | `solve_speed = fast/slow` | IMM_U1_R4, PERS_P004_R1 | 스트로크 타임스탬프 분석 | 🔴 높음 |
| **오류 패턴 감지** | `error_type = sign_error` | PERS_P004_R2 | OpenAI Vision 분석 | 🔴 높음 |
| **조기 포기 감지** | `quit_attempt = true` | PERS_P001_R1 | 화이트보드 상태 추적 | 🟡 중간 |
| **펜 내려놓음** | `pen_down_duration >= 5` | PERS_P001_R2 | 터치 이벤트 타임아웃 | 🟡 중간 |
| **확인 요청 빈도** | `confirm_request_count >= 3` | PERS_P002_R1 | 상호작용 카운터 | 🟡 중간 |
| **감정 변화** | `emotion_change = negative` | PERS_P003_R1 | 감정 히스토리 비교 | 🟡 중간 |
| **시선 이동** | `attention_drift = true` | PERS_P005_R1 | 웹캠 + 시선추적 | 🟢 낮음 |
| **한숨 감지** | `sigh_detected = true` | PERS_P003_R2 | 음성 분석 | 🟢 낮음 |
| **긴장 레벨** | `tension_level = high` | PERS_P008_R3 | 생체 신호 또는 추론 | 🟢 낮음 |

---

## 3. 상호작용 처리 유형 (룰/온톨로지 평가)

### 3.1 현재 구현 완료

| 처리 유형 | 설명 | 구현 파일 | 상태 |
|-----------|------|-----------|------|
| **조건 기반 룰 매칭** | AND/OR 조건 평가 | `includes/rule_evaluator.php` | ✅ 완료 |
| **우선순위 정렬** | priority 기반 정렬 | `includes/rule_evaluator.php` | ✅ 완료 |
| **페르소나별 룰 분기** | persona_id 조건 분기 | `services/interaction_service.php` | ✅ 완료 |
| **컨텍스트 로드** | 학생 컨텍스트 조회 | `services/context_service.php` | ✅ 완료 |
| **Will Layer 기본 검증** | 핵심 가치 위배 검사 | `services/will_validator.php` | ✅ 완료 |
| **문항 온톨로지 로드** | 문항별 개념/오류 로드 | `services/ontology_service.php` | ✅ 완료 |

### 3.2 개발 필요

| 처리 유형 | 설명 | 필요 구현 | 우선순위 |
|-----------|------|-----------|----------|
| **개념 관계 추론** | 선행-후행 개념 관계 활용 | `ontology_service.php` 확장 | 🔴 높음 |
| **오류 패턴 매칭** | 온톨로지 기반 오류 식별 | `ontology_service.php` 확장 | 🔴 높음 |
| **컨텍스트 누적 반영** | 세션 간 학습 데이터 활용 | `context_service.php` 확장 | 🟡 중간 |
| **페르소나 신뢰도 갱신** | 개입 효과 기반 업데이트 | `context_service.php` 확장 | 🟡 중간 |
| **다중 룰 조합** | 여러 룰 동시 적용 전략 | `interaction_service.php` 확장 | 🟡 중간 |
| **난이도 적응** | 온톨로지 기반 난이도 조절 | 신규 서비스 필요 | 🟢 낮음 |

---

## 4. 상호작용 출력 유형 (개입 활동)

### 4.1 카테고리 1: 멈춤/대기 (Pause & Wait) - 5개

| ID | 이름 | UI 액션 | 구현 상태 | 비고 |
|----|------|---------|-----------|------|
| INT_1_1 | 인지 부하 대기 | 3-5초 침묵 + 호흡 바 | 🟡 부분 | 호흡 바 UI 필요 |
| INT_1_2 | 필기 동기화 대기 | 필기 완료까지 대기 | ✅ 완료 | `analyze_writing.php` |
| INT_1_3 | 사고 여백 제공 | "한번 생각해봐" + 10초 | 🟡 부분 | 타이머 UI 필요 |
| INT_1_4 | 감정 진정 대기 | 침묵 + 호흡 바 | 🟡 부분 | 호흡 바 UI 필요 |
| INT_1_5 | 자기 수정 대기 | 5-10초 관찰 | 🟡 부분 | 후속 액션 연결 필요 |

### 4.2 카테고리 2: 재설명 (Repeat & Rephrase) - 6개

| ID | 이름 | UI 액션 | 구현 상태 | 비고 |
|----|------|---------|-----------|------|
| INT_2_1 | 동일 반복 | 천천히 재설명 | 🔴 미구현 | OpenAI 프롬프트 필요 |
| INT_2_2 | 강조점 이동 반복 | 하이라이트 위치 변경 | 🔴 미구현 | 하이라이트 UI 필요 |
| INT_2_3 | 단계 분해 | 미니 스텝 표시 | 🔴 미구현 | 단계별 UI 필요 |
| INT_2_4 | 역순 재구성 | 결론→시작 순 설명 | 🔴 미구현 | OpenAI 프롬프트 필요 |
| INT_2_5 | 연결고리 명시 | A→B→C 화살표 | 🔴 미구현 | 관계 시각화 UI 필요 |
| INT_2_6 | 요약 압축 | 한 문장 핵심 | 🔴 미구현 | OpenAI 프롬프트 필요 |

### 4.3 카테고리 3: 전환 설명 (Alternative Explanation) - 7개

| ID | 이름 | UI 액션 | 구현 상태 | 비고 |
|----|------|---------|-----------|------|
| INT_3_1 | 일상 비유 | 비유 메시지 | 🔴 미구현 | OpenAI 프롬프트 필요 |
| INT_3_2 | 시각화 전환 | 다이어그램 표시 | 🔴 미구현 | 시각화 컴포넌트 필요 |
| INT_3_3 | 구체적 수 대입 | 숫자 대입 애니메이션 | 🔴 미구현 | 수식 렌더링 필요 |
| INT_3_4 | 극단적 예시 | 0, ∞ 예시 | 🔴 미구현 | OpenAI 프롬프트 필요 |
| INT_3_5 | 반례 제시 | 잘못된 방법 시연 | 🔴 미구현 | OpenAI 프롬프트 필요 |
| INT_3_6 | 학생 언어 번역 | 용어 변환 | 🔴 미구현 | 학생 언어 DB 필요 |
| INT_3_7 | 신체/동작 비유 | 제스처 안내 | 🔴 미구현 | 애니메이션 필요 |

### 4.4 카테고리 4: 강조/주의환기 (Emphasis & Alerting) - 5개

| ID | 이름 | UI 액션 | 구현 상태 | 비고 |
|----|------|---------|-----------|------|
| INT_4_1 | 핵심 반복 강조 | "⭐ 중요" 표시 | 🟡 부분 | 반복 로직 필요 |
| INT_4_2 | 대비 강조 | ❌ vs ✅ 비교 | 🔴 미구현 | 비교 UI 필요 |
| INT_4_3 | 톤/속도 변화 | 주의 애니메이션 | 🔴 미구현 | 애니메이션 필요 |
| INT_4_4 | 시각적 마킹 | 동그라미/밑줄 | 🔴 미구현 | 마킹 UI 필요 |
| INT_4_5 | 예고 신호 | "🎯 시험에 나와" | 🟡 부분 | 메시지만 구현 |

### 4.5 카테고리 5: 질문/탐색 (Questioning & Probing) - 7개

| ID | 이름 | UI 액션 | 구현 상태 | 비고 |
|----|------|---------|-----------|------|
| INT_5_1 | 확인 질문 | 예/아니오 버튼 | 🟡 부분 | 버튼 UI 필요 |
| INT_5_2 | 예측 질문 | 열린 질문 | 🟡 부분 | 응답 처리 필요 |
| INT_5_3 | 역질문 | "왜 그렇게 생각?" | 🟡 부분 | 응답 분석 필요 |
| INT_5_4 | 선택지 질문 | A/B 선택 버튼 | 🔴 미구현 | 선택 UI 필요 |
| INT_5_5 | 힌트 질문 | 방향 유도 질문 | ✅ 완료 | `analyze_writing.php` |
| INT_5_6 | 연결 질문 | 기존 지식 연결 | 🔴 미구현 | 학습 히스토리 활용 |
| INT_5_7 | 메타인지 질문 | 자기 상태 인식 | 🟡 부분 | 응답 처리 필요 |

### 4.6 카테고리 6: 즉시 개입 (Immediate Intervention) - 6개

| ID | 이름 | UI 액션 | 구현 상태 | 비고 |
|----|------|---------|-----------|------|
| INT_6_1 | 즉시 교정 | "잠깐!" 경고 | 🟡 부분 | 오류 감지 연동 필요 |
| INT_6_2 | 부분 인정 확장 | "거기까진 맞아" | 🟡 부분 | 부분 정답 판별 필요 |
| INT_6_3 | 함께 완성 | 협업 모드 | 🔴 미구현 | 협업 UI 필요 |
| INT_6_4 | 되물어 확인 | 재구성 확인 | 🔴 미구현 | 응답 파싱 필요 |
| INT_6_5 | 오개념 즉시 분리 | 개념 구분 설명 | 🔴 미구현 | 온톨로지 활용 필요 |
| INT_6_6 | 실시간 시범 | 화이트보드 시연 | 🔴 미구현 | 시범 재생 필요 |

### 4.7 카테고리 7: 정서 조절 (Emotional Regulation) - 6개

| ID | 이름 | UI 액션 | 구현 상태 | 비고 |
|----|------|---------|-----------|------|
| INT_7_1 | 노력 인정 | "👏 열심히 했네" | ✅ 완료 | `showFeedback()` |
| INT_7_2 | 정상화 | "다 어려워해" | ✅ 완료 | `showFeedback()` |
| INT_7_3 | 난이도 조정 예고 | "천천히 가자" | ✅ 완료 | `showFeedback()` |
| INT_7_4 | 작은 성공 만들기 | 쉬운 질문 제시 | 🔴 미구현 | 난이도 조절 필요 |
| INT_7_5 | 유머/가벼운 전환 | "😊 쉬어가자" | ✅ 완료 | `showFeedback()` |
| INT_7_6 | 선택권 부여 | 선택 제공 | 🔴 미구현 | 선택 UI 필요 |

### 4.8 시스템 액션 - 7개

| ID | 이름 | UI 액션 | 구현 상태 | 비고 |
|----|------|---------|-----------|------|
| STEP_ADVANCE | 단계 진행 | 다음 단계 이동 | ✅ 완료 | `handleStepClick()` |
| ITEM_ADVANCE | 문항 이동 | 다음 문항 이동 | 🔴 미구현 | 문항 네비게이션 필요 |
| SESSION_INIT | 세션 초기화 | 세션 시작 | ✅ 완료 | `startSession()` |
| UPDATE_PROGRESS | 진행률 업데이트 | 진행 바 갱신 | ✅ 완료 | `updateProgress()` |
| NON_INTRUSIVE_QUESTION | 비침습적 질문 | 모서리 표시 | 🔴 미구현 | 위치 조정 필요 |
| SUGGEST_CHALLENGE | 도전 제안 | 고난도 제안 | 🟡 부분 | 난이도 조절 연동 필요 |
| LOG_EFFECTIVENESS | 효과 로깅 | 백그라운드 저장 | ✅ 완료 | `saveInteraction()` |

---

## 5. 구현 상태 요약

### 5.1 전체 통계

| 카테고리 | 전체 | ✅ 완료 | 🟡 부분 | 🔴 미구현 |
|----------|------|---------|---------|-----------|
| 입력 (트리거) | 23 | 14 (61%) | 0 | 9 (39%) |
| 처리 (룰/온톨로지) | 12 | 6 (50%) | 0 | 6 (50%) |
| 출력 (개입활동) | 49 | 11 (22%) | 13 (27%) | 25 (51%) |
| **합계** | **84** | **31 (37%)** | **13 (15%)** | **40 (48%)** |

### 5.2 카테고리별 완성도

```
멈춤/대기     ████░░░░░░ 40% (2/5)
재설명       ░░░░░░░░░░  0% (0/6)
전환설명     ░░░░░░░░░░  0% (0/7)
강조/주의    ██░░░░░░░░ 20% (1/5)
질문/탐색    ████░░░░░░ 43% (3/7)
즉시개입     ██░░░░░░░░ 17% (1/6)
정서조절     ████████░░ 67% (4/6)
시스템액션   ██████░░░░ 57% (4/7)
```

---

## 6. 개발 우선순위 권장

### 6.1 Phase 1: 핵심 기능 (1-2주)

| 우선순위 | 항목 | 이유 |
|----------|------|------|
| 1 | 필기 속도 분석 | 빠른데허술형(P004) 대응 필수 |
| 2 | 오류 패턴 자동 감지 | 온톨로지 기반 피드백 핵심 |
| 3 | 선택지 질문 UI (INT_5_4) | 부담 경감 주요 개입 |
| 4 | 대비 강조 UI (INT_4_2) | 오개념 교정 핵심 |

### 6.2 Phase 2: 확장 기능 (3-4주)

| 우선순위 | 항목 | 이유 |
|----------|------|------|
| 5 | 단계 분해 (INT_2_3) | 회피형(P001) 대응 |
| 6 | 구체적 수 대입 (INT_3_3) | 추상약함형(P009) 대응 |
| 7 | 함께 완성 (INT_6_3) | 상호작용의존형(P010) 대응 |
| 8 | 작은 성공 만들기 (INT_7_4) | 무기력형(P011) 대응 |

### 6.3 Phase 3: 고급 기능 (5-8주)

| 우선순위 | 항목 | 이유 |
|----------|------|------|
| 9 | 개념 관계 추론 | 온톨로지 활용 고도화 |
| 10 | 컨텍스트 누적 반영 | 장기 학습 효과 |
| 11 | 페르소나 신뢰도 갱신 | 적응형 개인화 |
| 12 | 시각화 전환 (INT_3_2) | 다중 표현 지원 |

---

## 7. 관련 파일 참조

### 7.1 룰 파일

| 파일 | 룰 수 | 주요 내용 |
|------|-------|-----------|
| `rules/immediate_rules.php` | 17개 | U0-U4 즉각 반응 |
| `rules/persona_rules.php` | 36개 | 12개 페르소나별 |
| `rules/intervention_mapping.php` | 49개 | 개입 활동 정의 |

### 7.2 온톨로지 파일

| 파일 | 내용 | 활용 |
|------|------|------|
| `ontology/problem_ontology.php` | 개념/문항/오류 | 추론 기반 피드백 |

### 7.3 서비스 파일

| 파일 | 역할 | 주요 함수 |
|------|------|-----------|
| `services/interaction_service.php` | 상호작용 처리 | `processEvent()`, `executeIntervention()` |
| `services/context_service.php` | 컨텍스트 관리 | `getOrCreateContext()`, `updateContext()` |
| `services/ontology_service.php` | 온톨로지 조회 | `getProblemOntology()`, `getConceptRelations()` |
| `services/will_validator.php` | Will 검증 | `validate()`, `checkConstraints()` |

### 7.4 UI 파일

| 파일 | 역할 | 주요 함수 |
|------|------|-----------|
| `ui/learning_interface.js` | 프론트엔드 | `showFeedback()`, `handleGestureAction()` |
| `ui/learning_interface.php` | 페이지 렌더링 | 세션 초기화 |
| `api/analyze_writing.php` | 필기 분석 API | OpenAI Vision 호출 |

---

## 8. 부록: 룰-개입활동 매핑 테이블

### 8.1 즉각 반응 룰 → 개입 활동

| 룰 ID | 트리거 | 개입 활동 |
|-------|--------|-----------|
| IMM_U1_R1 | pause >= 3s | INT_1_1 (인지 부하 대기) |
| IMM_U1_R2 | pause >= 10s | INT_5_5 (힌트 질문) |
| IMM_U1_R3 | erase >= 3회 | INT_5_7 (메타인지 질문) |
| IMM_U1_R4 | fast solve | INT_6_1 (즉시 교정) |
| IMM_U2_R1 | gesture: ✓ | STEP_ADVANCE |
| IMM_U2_R2 | gesture: ✗ | INT_2_1 (동일 반복) |
| IMM_U2_R3 | gesture: ? | NON_INTRUSIVE_QUESTION |
| IMM_U2_R4 | gesture: ○ | INT_6_4 (되물어 확인) |
| IMM_U2_R5 | gesture: → | ITEM_ADVANCE |
| IMM_U3_R1 | confident | SUGGEST_CHALLENGE |
| IMM_U3_R2 | stuck | INT_5_5 (힌트 질문) |
| IMM_U3_R3 | anxious | INT_7_3 (난이도 예고) |
| IMM_U3_R4 | confused | INT_5_4 (선택지 질문) |

### 8.2 페르소나별 주요 개입 활동

| 페르소나 | Primary | Avoid |
|----------|---------|-------|
| P001 (회피형) | INT_1_1, INT_1_3, INT_5_5, INT_6_3 | INT_4_5 |
| P002 (확인요구형) | INT_2_1, INT_5_1, INT_6_2, INT_6_4 | INT_1_5 |
| P003 (감정출렁형) | INT_1_4, INT_7_1~7_5 | INT_6_1 |
| P004 (빠른허술형) | INT_1_5, INT_4_1, INT_4_2, INT_6_1 | INT_2_6 |
| P005 (집중튐형) | INT_1_1, INT_2_2, INT_3_2, INT_4_3~4 | INT_3_4 |
| P006 (패턴추론형) | INT_1_3, INT_2_4, INT_2_5, INT_3_4, INT_5_6 | INT_2_3 |
| P007 (쉬운길형) | INT_2_5, INT_2_6, INT_4_5 | INT_2_1 |
| P008 (불안과몰입형) | INT_1_2, INT_3_5, INT_6_5, INT_7_3, INT_7_5 | INT_4_2 |
| P009 (추상약함형) | INT_1_1, INT_2_3, INT_3_1~3_3, INT_3_6 | INT_2_4 |
| P010 (상호작용의존형) | INT_2_1, INT_3_7, INT_5_2, INT_6_3, INT_6_6, INT_7_6 | INT_1_3 |
| P011 (무기력형) | INT_1_4, INT_3_1, INT_4_3, INT_5_4, INT_7_1~7_4 | INT_1_3 |
| P012 (메타인지고수형) | INT_1_3, INT_1_5, INT_2_4, INT_3_4, INT_5_3, INT_5_7 | INT_2_1, INT_5_4 |

---

**문서 버전**: 1.0  
**최종 수정일**: 2025-11-26  
**작성자**: AI 튜터 설계 팀

