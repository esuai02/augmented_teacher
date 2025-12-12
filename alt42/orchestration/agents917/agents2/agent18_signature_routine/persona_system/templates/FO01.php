<?php
/**
 * Agent18 Signature Routine - Template FO01
 * 루틴 최적화 추천 컨텍스트
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/templates/FO01.php
 */

return [
    'title' => '루틴 최적화 추천',
    'description' => '발견된 루틴을 바탕으로 최적화 추천을 제공하는 템플릿',
    'template' => <<<TEMPLATE
{{greeting}}

루틴을 더 효과적으로 만들 수 있는 방법을 찾았어요! 🚀

---

### 📋 현재 루틴 요약

{{current_routine_summary}}

---

### 🎯 최적화 추천

{{optimization_suggestions}}

---

### 📈 예상 효과

{{expected_benefits}}

### ⚡ 바로 시작하기

1. {{action_step_1}}
2. {{action_step_2}}
3. {{action_step_3}}

---

작은 변화부터 시작해 보세요.
새로운 루틴이 자리잡기까지 보통 **2-3주**가 걸려요.

{{ending}}
TEMPLATE
];
