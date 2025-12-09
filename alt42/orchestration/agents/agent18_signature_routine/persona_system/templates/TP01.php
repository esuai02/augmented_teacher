<?php
/**
 * Agent18 Signature Routine - Template TP01
 * 시간 패턴 분석 컨텍스트
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/templates/TP01.php
 */

return [
    'title' => '시간 패턴 분석',
    'description' => '학습 시간대별 패턴을 분석하여 보여주는 템플릿',
    'template' => <<<TEMPLATE
{{greeting}}

시간대별 학습 패턴을 분석해 봤어요! ⏰

---

### 📊 시간대별 학습 현황

{{time_pattern_table}}

---

### 🔍 분석 결과

{{analysis_summary}}

### 💡 인사이트

{{insight}}

{{recommendation}}

{{ending}}
TEMPLATE
];
