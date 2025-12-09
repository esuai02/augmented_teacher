# 주제 전환 지시문

{% if strength >= 0.8 -%}
## 🔄 지금 주제를 전환하세요!

**{{current_topic}}**에서 **{{suggested_topic}}**으로 전환하는 것을 강력히 권장합니다.
{%- elif strength >= 0.6 -%}
## 📋 주제 전환 권고

**{{current_topic}}**에서 다음 주제로 전환을 고려해보세요:
- **{{suggested_topic}}**
{%- else -%}
## 💡 전환 제안

여유가 되면 **{{suggested_topic}}**으로 전환해보세요.
{%- endif %}

## 전환 정보

- **현재 주제**: {{current_topic}}
- **집중 시간**: {{time_on_current_min}}분
- **권장 주제**: {{suggested_topic}}
- **권장 휴식**: {{break_min}}분

{% if reason == 'fatigue' %}
## ⚠️ 전환 사유: 집중력 저하

장시간 같은 주제에 집중하면 학습 효율이 떨어집니다.
잠시 휴식 후 새로운 주제로 시작하세요.
{% elif reason == 'balance' %}
## ⚖️ 전환 사유: 학습 균형

전체 커리큘럼 균형을 위해 다른 주제도 학습이 필요합니다.
{% elif reason == 'diminishing_returns' %}
## 📉 전환 사유: 수확 체감

현재 주제에서 추가 학습 효과가 줄어들고 있습니다.
{% endif %}

## 기대 효과

{% for outcome in expected_outcomes %}
- {{ outcome }}
{% endfor %}

---
*강도: {{strength|round(2)}} | 우선순위: {{priority|round(2)}}*
*생성: {{timestamp}}*

