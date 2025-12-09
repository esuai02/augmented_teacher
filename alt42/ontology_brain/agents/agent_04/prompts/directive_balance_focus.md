# 균형 학습 지시문

{% if strength >= 0.8 -%}
## 🎯 우선 학습 지시

다음 단원들을 **우선적으로** 학습하세요:
{%- elif strength >= 0.6 -%}
## 📋 학습 균형 권고

다음 단원들에 더 집중해보세요:
{%- else -%}
## 💡 학습 제안

여유가 되면 다음 단원들을 고려해보세요:
{%- endif %}

## 우선순위 단원

{% for unit in priority_units %}
### {{loop.index}}. {{unit.name}}

- **현재 진도**: {{unit.current_progress|percent}}
- **목표 진도**: {{unit.target_progress|percent}}
- **격차**: {{unit.gap|percent}}
- **예상 소요**: {{unit.estimated_hours}}시간
- **권장 활동**: {{unit.suggested_activity}}

{% endfor %}

## 학습 전략

{% if mode == 'intensive' %}
### 집중 모드

각 단원당 최소 **30분** 이상 연속 학습하세요.
{% elif mode == 'distributed' %}
### 분산 모드

하루에 **2-3개** 단원을 번갈아가며 학습하세요.
{% else %}
### 균형 모드

자신의 페이스에 맞게 **순차적으로** 학습하세요.
{% endif %}

## 일정 제안

{% for day in weekly_plan %}
- **{{day.date}}**: {{day.units|join(', ')}} ({{day.hours}}시간)
{% endfor %}

## 기대 효과

{% for outcome in expected_outcomes %}
- {{ outcome }}
{% endfor %}

---
*강도: {{strength|round(2)}} | 우선순위: {{priority|round(2)}}*
*생성: {{timestamp}}*
*균형 지수: {{balance_index|round(2)}}*

