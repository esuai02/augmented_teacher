# 노드별 학생 질문 자동 생성 시스템

## 📌 개요

사고 흐름도의 각 노드(단계)에서 학생들이 자연스럽게 가질 수 있는 질문들을 AI가 자동으로 생성하고, 클릭 시 맥락에 맞는 답변을 제공하는 인터랙티브 학습 시스템입니다.

## 🎯 주요 기능

### 1. 노드별 질문 아이콘
- **위치**: 사고 흐름도의 모든 노드(전제조건, 단계, 결론)
- **표시**: 📝 질문 (주황색 그라데이션 버튼)
- **동작**: 클릭 시 해당 노드에 맞는 학생 질문 2~3개 표시

### 2. 질문 타입별 자동 생성
- **전제조건 (premise)**: 개념 이해 확인 질문
  - 예: "왜 이 개념이 필요한가요?", "이게 무슨 뜻인가요?"
- **단계 (step)**: 실행 방법 질문
  - 예: "어떻게 계산하나요?", "다른 방법은 없나요?"
- **결론 (conclusion)**: 결과 검증 및 확장 질문
  - 예: "이 결과가 맞는지 어떻게 확인하나요?", "다른 문제에도 적용할 수 있나요?"

### 3. 답변 자동 생성
- **두괄식 구조**: 핵심 답을 먼저 제시
- **구체적 설명**: 예시와 계산 과정 포함
- **LaTeX 수식**: 수학 표기 지원
- **DB 캐싱**: 한 번 생성된 질문/답변은 DB에 저장되어 재사용

## 🏗️ 시스템 구조

### DB 테이블

#### 1. `mdl_abrainalignment_node_questions` (노드별 질문)
```sql
CREATE TABLE mdl_abrainalignment_node_questions (
    id INT(10) PRIMARY KEY AUTO_INCREMENT,
    contentsid INT(10) NOT NULL,
    contentstype INT(2) NOT NULL,
    nstep INT(5) NOT NULL DEFAULT 1,
    node_index INT(5) NOT NULL,
    node_content TEXT,
    node_type VARCHAR(50) DEFAULT 'premise',
    questions_json TEXT,
    timecreated INT(10) NOT NULL,
    timemodified INT(10) NOT NULL,
    INDEX idx_content_node (contentsid, contentstype, nstep, node_index)
);
```

#### 2. `mdl_abrainalignment_node_answers` (질문별 답변)
```sql
CREATE TABLE mdl_abrainalignment_node_answers (
    id INT(10) PRIMARY KEY AUTO_INCREMENT,
    contentsid INT(10) NOT NULL,
    contentstype INT(2) NOT NULL,
    nstep INT(5) NOT NULL DEFAULT 1,
    node_index INT(5) NOT NULL,
    question_index INT(5) NOT NULL,
    question TEXT,
    answer TEXT,
    timecreated INT(10) NOT NULL,
    timemodified INT(10) NOT NULL,
    INDEX idx_answer_lookup (contentsid, contentstype, nstep, node_index, question_index)
);
```

### API 엔드포인트

#### 1. `generate_node_questions.php`
**기능**: 노드별 학생 질문 2~3개 자동 생성

**입력 (JSON)**:
```json
{
    "nodeContent": "노드 내용",
    "nodeType": "premise|step|conclusion",
    "fullContext": "전체 사고 흐름도",
    "contentsid": 29565,
    "contentstype": 1,
    "nstep": 1,
    "nodeIndex": 0
}
```

**출력 (JSON)**:
```json
{
    "success": true,
    "questions": [
        "왜 이렇게 하나요?",
        "다른 방법은 없나요?",
        "이게 무슨 뜻인가요?"
    ],
    "source": "db|ai"
}
```

**처리 흐름**:
1. DB에서 기존 질문 확인 → 있으면 반환
2. 없으면 OpenAI GPT-4o-mini로 생성
3. 생성된 질문을 DB에 저장
4. JSON 형식으로 반환

#### 2. `generate_node_answer.php`
**기능**: 특정 질문에 대한 답변 생성 (두괄식, 3~5문장)

**입력 (JSON)**:
```json
{
    "question": "왜 이렇게 하나요?",
    "nodeContent": "노드 내용",
    "fullContext": "전체 사고 흐름도",
    "contentsid": 29565,
    "contentstype": 1,
    "nstep": 1,
    "nodeIndex": 0,
    "questionIndex": 0
}
```

**출력 (JSON)**:
```json
{
    "success": true,
    "answer": "핵심 답변입니다. 구체적 설명을 제공합니다. 전체 과정과의 관계를 설명합니다.",
    "source": "db|ai"
}
```

**처리 흐름**:
1. DB에서 기존 답변 확인 → 있으면 반환
2. 없으면 OpenAI GPT-4o-mini로 생성
3. 생성된 답변을 DB에 저장
4. JSON 형식으로 반환

## 🎨 UI/UX

### 질문 아이콘
```css
.node-question-icon {
    display: inline-block;
    margin-left: 8px;
    padding: 3px 8px;
    background: linear-gradient(135deg, #FFC107 0%, #FF9800 100%);
    color: white;
    border-radius: 12px;
    cursor: pointer;
    font-size: 11px;
}
```

### 질문 팝업
```css
.node-questions-popup {
    padding: 15px;
    background: #fff8e1;
    border-radius: 8px;
    border: 2px solid #FFC107;
    animation: popupSlide 0.3s ease-out;
}
```

### 질문 리스트
```css
.node-question-list li {
    padding: 8px 12px;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    border-left: 3px solid #2196F3;
}
```

### 답변 영역
```css
.node-answer-area {
    padding: 12px;
    background: #e8f5e9;
    border-radius: 6px;
    border-left: 3px solid #4CAF50;
    animation: answerReveal 0.4s ease-out;
}
```

## 🔄 JavaScript 주요 함수

### 1. `toggleNodeQuestions(nodeIndex, nodeType, iconElement)`
**기능**: 질문 아이콘 클릭 시 질문 팝업 토글

**동작**:
1. 팝업이 이미 열려있으면 닫기
2. 다른 팝업들 모두 닫기
3. 로딩 표시
4. `generate_node_questions.php` API 호출
5. 질문 목록 표시

### 2. `showNodeAnswer(nodeIndex, questionIndex, question, nodeContent, nodeType, liElement)`
**기능**: 질문 클릭 시 답변 표시 (토글)

**동작**:
1. 이미 답변이 있으면 show/hide 토글
2. 없으면 새로 생성
3. `generate_node_answer.php` API 호출
4. 답변을 질문 아래에 표시
5. MathJax 렌더링

### 3. `renderFlowchart(container, thinkingText)`
**기능**: 사고 흐름도 렌더링 (수정됨)

**변경 사항**:
- 모든 노드에 `data-node-index` 속성 추가
- 각 노드 끝에 📝 질문 아이콘 추가
- 질문 팝업 컨테이너 추가

## 📦 설치 및 사용

### 1. DB 테이블 생성
```
https://mathking.kr/moodle/local/augmented_teacher/books/create_node_questions_table.php
```
브라우저에서 위 URL에 접속하여 테이블 생성

### 2. 파일 구성
```
books/
├── drillingmath.php                    # 메인 페이지 (수정됨)
├── generate_node_questions.php         # 질문 생성 API (신규)
├── generate_node_answer.php            # 답변 생성 API (신규)
├── create_node_questions_table.php     # DB 테이블 생성 (신규)
└── NODE_QUESTIONS_README.md            # 이 문서 (신규)
```

### 3. 사용 방법
1. 사고 흐름도 표시 (기존 기능)
2. 각 노드 끝의 📝 질문 아이콘 클릭
3. 생성된 질문 목록에서 궁금한 질문 클릭
4. 답변 자동 생성 및 표시 (blur 애니메이션)
5. 다시 클릭하면 답변 숨기기

## 🔧 일반화 및 확장성

### 범용성
- **모든 contentstype 지원**: icontent(1), question(2) 등
- **구간별 지원**: nstep 파라미터로 구간 구분
- **노드 타입별 질문**: premise, step, conclusion 자동 구분

### 캐싱 전략
- 한 번 생성된 질문은 DB에 저장
- `(contentsid, contentstype, nstep, node_index)` 조합으로 식별
- 답변도 `question_index`까지 포함하여 저장
- 재요청 시 API 호출 없이 즉시 반환

### 확장 가능성
- 질문 유형 추가 가능 (노드 타입 확장)
- 답변 길이 조절 가능 (프롬프트 수정)
- 다국어 지원 가능 (프롬프트 언어 변경)
- 학생별 맞춤 질문 생성 가능 (학습 이력 활용)

## 🎓 교육적 가치

### 학생 관점
1. **능동적 학습**: 각 단계에서 스스로 질문 생성
2. **즉각적 피드백**: 질문에 대한 즉시 답변
3. **깊이 있는 이해**: 단순 암기가 아닌 이해 중심
4. **자기주도 학습**: 궁금한 부분을 선택적으로 탐색

### 교사 관점
1. **학생 질문 예측**: 자주 나오는 질문 파악
2. **교수법 개선**: 설명이 부족한 부분 발견
3. **시간 절약**: 반복 질문 자동 응답
4. **학습 분석**: 질문 패턴 분석 가능

## 📊 성능 최적화

### DB 인덱스
- `idx_content_node`: 질문 조회 최적화
- `idx_answer_lookup`: 답변 조회 최적화

### API 응답 시간
- 질문 생성: ~2초 (캐시 시 <100ms)
- 답변 생성: ~3초 (캐시 시 <100ms)

### 토큰 사용량
- 질문 생성: ~500 tokens
- 답변 생성: ~300 tokens

## 🐛 에러 처리

### 로깅
모든 에러는 다음 형식으로 로깅됩니다:
```
[파일명] File: 파일명, Line: 라인번호, 메시지
```

### 사용자 피드백
- 질문 생성 실패: "질문 생성 실패" (빨간색)
- 답변 생성 실패: "답변 생성 실패" (빨간색)
- 네트워크 오류: "질문 로딩 오류" / "답변 생성 오류"

## 📝 향후 개선 계획

1. **질문 난이도 조절**: 학생 수준별 질문 생성
2. **질문 투표 시스템**: 유용한 질문 평가
3. **질문 추천**: 다른 학생들이 많이 본 질문 표시
4. **답변 편집**: 교사가 답변 수정 가능
5. **통계 대시보드**: 질문 패턴 분석 및 시각화

---

**최종 수정**: 2025-01-26
**버전**: 1.0
**담당자**: AI Learning System
