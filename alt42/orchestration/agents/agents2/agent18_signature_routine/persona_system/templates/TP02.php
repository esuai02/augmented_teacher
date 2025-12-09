<?php
/**
 * Agent18 Signature Routine - Template TP02
 * 골든타임 발견 컨텍스트
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/templates/TP02.php
 */

return [
    'title' => '골든타임 발견',
    'description' => '학습자의 최적 학습 시간(골든타임)을 발견했을 때 사용하는 템플릿',
    'template' => <<<TEMPLATE
{{greeting}}

🌟 **골든타임을 발견했어요!**

당신의 학습 데이터를 분석한 결과, 최고의 집중력을 발휘하는 시간대를 찾았습니다.

---

## ⭐ 나의 골든타임

| 항목 | 값 |
|------|-----|
| 🕐 **최적 시간대** | {{golden_time}} |
| 📈 **평균 학습 점수** | {{avg_score}}점 |
| 🔄 **분석 세션 수** | {{session_count}}회 |
| 📊 **신뢰도** | {{confidence}}% |

---

### 🔬 왜 이 시간이 골든타임일까요?

{{golden_time_reason}}

### 💪 골든타임 활용법

{{recommendation}}

### 📌 기억하세요

> 골든타임은 **가장 중요하고 어려운 학습**에 활용하세요.
> 단순 암기나 복습은 다른 시간대에 해도 괜찮아요!

{{ending}}
TEMPLATE
];
