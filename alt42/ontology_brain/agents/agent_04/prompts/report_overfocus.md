# 과도 집중 리포트 ({{date}})

## 학습 분석

- **집중 주제**: {{focused_topic}}
- **집중 시간**: {{time_on_single_topic_min}}분
- **다양성 지수**: {{diversity_score|round(2)}} / 1.0
- **균형 점수**: {{balance_score|round(2)}} / 1.0

## 상태 진단

{% if time_on_single_topic_min >= 60 %}
⚠️ **주의**: **{{focused_topic}}**에 **{{time_on_single_topic_min}}분** 이상 집중하고 있습니다.
장시간 단일 주제 집중은 학습 효율을 저하시킬 수 있습니다.
{% elif time_on_single_topic_min >= 45 %}
ℹ️ **참고**: **{{focused_topic}}**에 **{{time_on_single_topic_min}}분** 집중 중입니다.
다른 주제로 전환을 고려해보세요.
{% else %}
✅ 적절한 집중 시간입니다.
{% endif %}

## 주제별 분포

{% for topic in topic_distribution %}
- **{{topic.name}}**: {{topic.time_min}}분 ({{topic.percentage|round(1)}}%)
{% endfor %}

## 제안

{% if strength >= 0.8 -%}
**지금 바로 주제를 전환하세요:**
{%- elif strength >= 0.6 -%}
**다음 활동으로 전환을 고려하세요:**
{%- else -%}
참고로 다음 활동을 살펴보세요:
{%- endif %}

- 📚 **{{suggested_topic}}** 학습
- ⏱️ 권장 시간: **{{break_min}}분** 휴식 후 시작
- 🎯 목표: 학습 균형 회복

{% if chart_ref %}
## 그래프

![주제 분포]({{chart_ref}})
{% endif %}

---
*생성 시각: {{timestamp}}*
*신뢰도: {{confidence|round(2)}}*

