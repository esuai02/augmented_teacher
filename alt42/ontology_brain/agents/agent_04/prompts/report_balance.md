# 학습 균형 리포트 ({{date}})

## 커리큘럼 개요

- **전체 진도**: {{overall_progress|percent}}
- **단원 커버리지**: {{curriculum_coverage|percent}}
- **균형 지수**: {{balance_index|round(2)}} / 1.0
- **분석 기간**: 최근 {{lookback_days}}일

## 단원별 현황

{% for unit in units %}
- **{{unit.name}}**: {{unit.progress|percent}} {% if unit.status == 'behind' %}🔴{% elif unit.status == 'on_track' %}🟢{% else %}🟡{% endif %}
{% endfor %}

## 분석 결과

{% if balance_index >= 0.8 %}
✅ **우수**: 전체적으로 균형 잡힌 학습을 하고 있습니다!
{% elif balance_index >= 0.6 %}
🟡 **양호**: 대체로 균형적이지만 일부 단원에 더 집중이 필요합니다.
{% elif balance_index >= 0.4 %}
⚠️ **주의**: 학습 불균형이 감지되었습니다. 아래 단원에 집중하세요.
{% else %}
🔴 **경고**: 심각한 학습 불균형 상태입니다. 즉시 조정이 필요합니다.
{% endif %}

{% if weak_units %}
## 📌 집중 필요 단원

{% for unit in weak_units %}
1. **{{unit.name}}** - 현재 {{unit.progress|percent}}, 목표 대비 {{unit.gap|percent}} 부족
{% endfor %}
{% endif %}

## 권장 학습 계획

{% if strength >= 0.8 -%}
**다음 주간 우선 학습 목표:**
{%- elif strength >= 0.6 -%}
**권장 학습 순서:**
{%- else -%}
참고 학습 순서:
{%- endif %}

{% for priority in priority_units %}
{{loop.index}}. **{{priority.name}}** - 예상 소요: {{priority.estimated_hours}}시간
{% endfor %}

{% if chart_ref %}
## 그래프

![단원별 진도]({{chart_ref}})
{% endif %}

---
*생성 시각: {{timestamp}}*
*신뢰도: {{confidence|round(2)}}*

