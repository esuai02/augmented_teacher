# Agent 14: Current Position Evaluation

## 목적
수학일기 데이터를 기반으로 학습자의 현재 위치와 진행 상태를 평가하여, 계획 대비 실제 진행 정도와 감정 상태를 종합 분석합니다.

## 주요 기능

### 1. 진행 상태 평가 (Progress Status Evaluation)
- **지연 (Delayed)**: 예상 완료 시간보다 30분 이상 지연
- **적절 (On-Time)**: 예상 완료 시간 ±30분 이내
- **원활 (Early)**: 예상 완료 시간보다 30분 이상 빠름

### 2. 감정 상태 분석 (Emotional State Analysis)
- **매우 긍정**: 매우만족 응답 다수
- **긍정**: 만족 응답 다수
- **부정**: 불만족 응답 다수
- **중립**: 균형적 분포

### 3. 종합 분석 리포트
- 완료율 계산
- 지연/적절/원활 항목 통계
- 감정 상태 집계
- 다른 에이전트 전달용 요약 생성

## 데이터 소스

### mdl_abessi_todayplans 테이블
```
- plan1~plan16: 학습 계획 내용
- due1~due16: 계획된 소요 시간 (분)
- status01~status16: 만족도 ('매우만족', '만족', '불만족')
- tend01~tend16: 실제 완료 시점 (unixtime)
- tbegin: 일기 작성 시작 시점 (unixtime)
```

## 계산 로직

### 예상 완료 시간 계산
```
1. tbegin 기준 5분 단위 반올림하여 시작 시간 설정
2. 각 항목의 duration을 순차적으로 더하여 예상 종료 시간 계산
3. 실제 완료 시간(tend)과 비교하여 지연 시간 산출
```

### 지연 시간 계산
```
delay_minutes = (tend - expected_end) / 60

- delay > 30분: 지연
- -30분 ≤ delay ≤ 30분: 적절
- delay < -30분: 원활
```

## API 엔드포인트

### GET /alt42/orchestration/agents/agent14_current_position/agent.php

**Parameters:**
- `userid` (optional): 분석 대상 학생 ID (기본값: 현재 로그인 사용자)

**Response:**
```json
{
  "success": true,
  "data": {
    "student_id": 123,
    "diary_id": 456,
    "analysis_time": 1234567890,
    "overall_status": "지연|적절|원활",
    "emotional_state": "매우 긍정|긍정|중립|부정",
    "completion_rate": 75.5,
    "statistics": {
      "total_entries": 8,
      "completed": 6,
      "delayed": 2,
      "on_time": 3,
      "early": 1,
      "total_planned_minutes": 240,
      "satisfaction": {
        "매우만족": 3,
        "만족": 2,
        "불만족": 1
      }
    },
    "entries": [...],
    "insights": ["..."],
    "recommendations": ["..."],
    "agent_summary": "[Agent14 분석] 완료율 75% | 진행상태: 적절..."
  }
}
```

## 휴리스틱 및 코칭 템플릿

### 지연 상황
- **인사이트**: "현재 N개 항목이 지연되고 있습니다."
- **추천**: "지연 항목을 2회로 분할하여 이번 주에 나눠서 해결하세요."

### 원활 상황
- **인사이트**: "예상보다 빠르게 진행되고 있습니다."
- **추천**: "여유 시간을 활용하여 심화 학습을 추천합니다."

### 감정 부정
- **인사이트**: "불만족 응답이 많습니다. 학습 방식 점검이 필요합니다."
- **추천**: "어려운 부분은 건너뛰고 쉬운 문제부터 해결하여 자신감을 회복하세요."

## 다른 에이전트와의 연계

### Agent Summary 형식
```
[Agent14 분석] 완료율 XX% | 진행상태: [지연|적절|원활]
(N개 지연, N개 적절, N개 원활) | 감정상태: [긍정|중립|부정]
(N만족 N만족 N불만족) | 권장: [조치사항]
```

이 요약은 다음 에이전트들에게 전달됩니다:
- **Agent 3 (Goals Analysis)**: 목표 조정 필요성 판단
- **Agent 5 (Learning Emotion)**: 감정 상태 연계 분석
- **Agent 7 (Interaction Targeting)**: 개입 타이밍 결정

## 사용 예시

### JavaScript에서 호출
```javascript
fetch('https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_current_position/agent.php?userid=123')
  .then(response => response.json())
  .then(data => {
    console.log('진행 상태:', data.data.overall_status);
    console.log('감정 상태:', data.data.emotional_state);
    console.log('요약:', data.data.agent_summary);
  });
```

## 버전 히스토리
- **v1.0** (2025-10-21): 초기 버전 생성
  - tend01~tend16 필드 기반 진행 상태 분석
  - 감정 상태 통합 평가
  - 에이전트 간 요약 전달 기능

## 관련 파일
- `agent.php`: 메인 분석 로직
- `README.md`: 문서 (이 파일)
- `../../students/db/add_tend_fields.php`: DB 마이그레이션
- `../../students/save_todayplan.php`: tend 저장 로직
- `../../students/goals42.php`: UI 및 체크박스 이벤트

## 주의사항
1. **12시간 윈도우**: 최근 12시간 이내 일기만 분석
2. **완료 시점 필수**: tend 값이 없으면 미완료로 처리
3. **시간 반올림**: tbegin 기준 5분 단위로 반올림하여 정확도 향상
4. **순차 계산**: 각 항목의 시작 시간은 이전 항목의 종료 시간

Last-Updated: 2025-10-21
Version: 1.0
