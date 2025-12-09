<?php
/**
 * Agent18 Signature Routine - Template SR02
 * 패턴 발견 컨텍스트
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/templates/SR02.php
 */

return [
    'title' => '루틴 패턴 발견',
    'description' => '학습 패턴이 발견되었을 때 사용하는 템플릿',
    'template' => <<<TEMPLATE
{{greeting}}

흥미로운 패턴을 발견했어요! ✨

### 발견된 패턴

{{pattern_description}}

---

이런 루틴이 당신에게 잘 맞는 것 같아요.

### 추천 사항

{{recommendation}}

조금만 더 데이터가 모이면 **시그너처 루틴**을 완성할 수 있어요!

{{ending}}
TEMPLATE
];
