# 난이도 조정 지시문

{% if adjustment < 0 -%}
## 📉 난이도 하향 조정 권고

{% if reason == 'progress_recovery' -%}
현재 진도 회복을 위해 **난이도를 {{adjustment|abs}}단계 낮추는 것**을 권장합니다.
{%- elif reason == 'confidence_building' -%}
자신감 회복을 위해 **난이도를 {{adjustment|abs}}단계 낮추는 것**을 권장합니다.
{%- elif reason == 'retry_failure' -%}
반복 실패로 인해 **난이도를 {{adjustment|abs}}단계 낮추는 것**을 권장합니다.
{%- else -%}
학습 효율을 위해 **난이도를 {{adjustment|abs}}단계 낮추는 것**을 권장합니다.
{%- endif %}
{%- else -%}
## 📈 난이도 상향 조정 권고

현재 수준에서 **난이도를 {{adjustment}}단계 높이는 것**을 권장합니다.
{%- endif %}

## 세부 내용

- **현재 난이도**: {% if current_difficulty == 'easy' %}쉬움 🟢{% elif current_difficulty == 'medium' %}보통 🟡{% elif current_difficulty == 'hard' %}어려움 🔴{% else %}{{current_difficulty}}{% endif %}
- **권장 난이도**: {% if suggested_difficulty == 'easy' %}쉬움 🟢{% elif suggested_difficulty == 'medium' %}보통 🟡{% elif suggested_difficulty == 'hard' %}어려움 🔴{% else %}{{suggested_difficulty}}{% endif %}
- **조정 폭**: {{adjustment|abs}}단계 {% if adjustment < 0 %}하향{% else %}상향{% endif %}

{% if metrics %}
## 📊 관련 지표

{% for key, value in metrics.items() %}
- **{{key}}**: {{value}}
{% endfor %}
{% endif %}

## 기대 효과

{% for outcome in expected_outcomes %}
- {{ outcome }}
{% endfor %}

{% if strength >= 0.8 %}
## ⚠️ 조치 권고

이 조정을 **즉시 적용**하는 것을 강력히 권장합니다.
{% elif strength >= 0.6 %}
## 💡 제안

다음 학습 세션부터 이 조정을 **고려해보세요**.
{% else %}
## ℹ️ 참고

필요시 이 조정을 **선택적으로 적용**할 수 있습니다.
{% endif %}

---
*강도: {{strength|round(2)}} | 우선순위: {{priority|round(2)}}*
*생성: {{timestamp}}*
*조정 사유: {{reason}}*

