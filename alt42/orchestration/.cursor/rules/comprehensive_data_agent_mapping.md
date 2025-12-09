# 데이터-에이전트 관련성 매핑 (전체 100개 항목)

## 매핑 기준
- **직접 관련 (Primary)**: 해당 에이전트의 핵심 미션과 직접적으로 연관된 데이터
- **보조 관련 (Secondary)**: 다른 에이전트에 배치되었지만 관련성이 있는 데이터

---

## 1. 기본 신상 정보 (10개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 1. 학생 이름 | agent01_onboarding | - | 기본 프로필 |
| 2. 학교 이름 | agent01_onboarding | agent02_exam_schedule | 시험 일정 참조용 |
| 3. 학교 급 (초/중/고) | agent01_onboarding | agent02_exam_schedule, agent03_goals_analysis | 시험/목표 설정 참조 |
| 4. 학년 | agent01_onboarding | agent02_exam_schedule, agent03_goals_analysis, agent14_current_position | 여러 에이전트에서 참조 |
| 5. 생년월일 | agent01_onboarding | - | 기본 프로필 |
| 6. 성별 | agent01_onboarding | - | 기본 프로필 |
| 7. 보호자 이름 | agent01_onboarding | agent06_teacher_feedback | 피드백 참조 |
| 8. 보호자 관계 | agent01_onboarding | agent06_teacher_feedback | 피드백 참조 |
| 9. 학생 연락처 | agent01_onboarding | - | 기본 프로필 |
| 10. 보호자 연락처 | agent01_onboarding | agent06_teacher_feedback | 피드백 참조 |

---

## 2. 위치 및 환경 정보 (5개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 11. 거주지 주소 | agent01_onboarding | - | 환경 정보 |
| 12. 등하교 시간 | agent01_onboarding | agent09_learning_management, agent17_remaining_activities | 시간 관리 참조 |
| 13. 통학 거리 | agent01_onboarding | agent09_learning_management | 시간 관리 참조 |
| 14. 학원/과외 거리 | agent01_onboarding | agent09_learning_management | 시간 관리 참조 |
| 15. 개인 학습 공간 유무 | agent01_onboarding | agent18_signature_routine | 루틴 최적화 참조 |

---

## 3. 수학 학습 진도 정보 (10개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 16. 개념 진도 | agent14_current_position | agent10_concept_notes, agent01_onboarding | 현재 위치 핵심 데이터 |
| 17. 심화 진도 | agent14_current_position | agent10_concept_notes | 현재 위치 핵심 데이터 |
| 18. 최근 학교 시험 범위 | agent02_exam_schedule | agent14_current_position | 시험 대비 핵심 |
| 19. 학년 대비 선행 진도 정도 | agent14_current_position | agent03_goals_analysis | 목표 설정 참조 |
| 20. 단원별 진도표 | agent14_current_position | agent02_exam_schedule, agent10_concept_notes | 현재 위치 핵심 |
| 21. 문제집 완료율 | agent14_current_position | agent11_problem_notes | 현재 위치 핵심 |
| 22. 교과서 활용 여부 | agent01_onboarding | agent02_exam_schedule | 교재 정보 |
| 23. 수학 내신 등급 | agent02_exam_schedule | agent14_current_position | 시험 관련 |
| 24. 경시/심화 경험 여부 | agent01_onboarding | agent02_exam_schedule | 학습 이력 |
| 25. 개념별 취약 영역 기록 | agent10_concept_notes | agent11_problem_notes, agent14_current_position | 개념 노트 핵심 |

---

## 4. 학습 성향 및 습관 (15개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 26. 개념 중심 vs 문제 중심 | agent10_concept_notes | agent11_problem_notes, agent04_problem_activity | 학습 스타일 |
| 27. 고난도 선호도 | agent04_problem_activity | agent18_signature_routine | 활동 선호도 |
| 28. 반복 학습 선호도 | agent10_concept_notes | agent18_signature_routine | 학습 습관 |
| 29. 집중 시간 평균 | agent18_signature_routine | agent12_rest_routine, agent05_learning_emotion | 루틴 최적화 |
| 30. 쉬는 시간 패턴 | agent12_rest_routine | agent18_signature_routine | 휴식 루틴 |
| 31. 포모도로 경험 유무 | agent09_learning_management | agent18_signature_routine | 학습 관리 |
| 32. 학습 루틴 시간대 | agent18_signature_routine | agent09_learning_management | 시그너처 루틴 |
| 33. 공부 장소 패턴 | agent01_onboarding | agent18_signature_routine | 환경 정보 |
| 34. 시험 공부 방식 | agent02_exam_schedule | agent18_signature_routine | 시험 대비 |
| 35. 숙제 수행률 | agent09_learning_management | agent14_current_position | 학습 관리 |
| 36. 문제 오답 정리 습관 | agent11_problem_notes | agent04_problem_activity | 오답 노트 |
| 37. 실수 vs 개념 미해결 비율 | agent08_calmness | agent11_problem_notes, agent10_concept_notes | 침착도 분석 |
| 38. 필기 습관 유무 | agent10_concept_notes | agent11_problem_notes | 노트 작성 |
| 39. 정리 도구(노션/노트 등) 사용 여부 | agent10_concept_notes | agent11_problem_notes | 학습 도구 |
| 40. 학습 자료 스스로 선택 여부 | agent04_problem_activity | agent18_signature_routine | 자기주도성 |

---

## 5. 정서 및 동기 정보 (10개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 41. 수학에 대한 자신감 (1–10) | agent05_learning_emotion | agent18_signature_routine | 감정 분석 핵심 |
| 42. 수학 스트레스 정도 | agent05_learning_emotion | agent12_rest_routine | 감정 분석 핵심 |
| 43. 실패 경험에 대한 반응 | agent05_learning_emotion | agent15_problem_redefinition | 감정 분석 |
| 44. 성취 경험 빈도 | agent05_learning_emotion | agent18_signature_routine | 감정 분석 |
| 45. 부모의 칭찬/비판 패턴 | agent05_learning_emotion | agent06_teacher_feedback | 감정 분석 |
| 46. 학습 목표 스스로 설정하는지 | agent03_goals_analysis | agent05_learning_emotion | 목표 분석 |
| 47. 목표 도달 경험 유무 | agent03_goals_analysis | agent05_learning_emotion | 목표 분석 |
| 48. 수업 중 감정 상태 기록 | agent05_learning_emotion | agent04_problem_activity | 감정 분석 핵심 |
| 49. 질문 요청 경향 | agent04_problem_activity | agent05_learning_emotion | 활동 분석 |
| 50. 경쟁심 or 협동성 | agent05_learning_emotion | agent04_problem_activity | 감정 분석 |

---

## 6. 학습 이력 (10개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 51. 학원/과외 경험 | agent01_onboarding | agent09_learning_management | 학습 이력 |
| 52. 경험 기간 (총 개월수) | agent01_onboarding | agent09_learning_management | 학습 이력 |
| 53. 과거 교재 목록 | agent01_onboarding | agent02_exam_schedule | 학습 이력 |
| 54. 과거 시험 성적 히스토리 | agent02_exam_schedule | agent14_current_position | 시험 이력 |
| 55. 최근 3회 시험 점수 | agent02_exam_schedule | agent14_current_position | 시험 이력 |
| 56. 개념 완성 이력 | agent10_concept_notes | agent14_current_position | 개념 이력 |
| 57. 누적 오답노트 보유 여부 | agent11_problem_notes | agent04_problem_activity | 오답 이력 |
| 58. 자가진단/레벨 테스트 이력 | agent01_onboarding | agent14_current_position | 학습 이력 |
| 59. 과목별 튜터링 여부 | agent01_onboarding | agent09_learning_management | 학습 이력 |
| 60. 학기별 학습 강도 변화 | agent09_learning_management | agent14_current_position | 학습 관리 |

---

## 7. 목표 설정 정보 (10개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 61. 단기 목표 (예: 숙제 완수) | agent03_goals_analysis | agent17_remaining_activities | 목표 분석 핵심 |
| 62. 중기 목표 (예: 개념 완성) | agent03_goals_analysis | agent17_remaining_activities | 목표 분석 핵심 |
| 63. 장기 목표 (예: 진학, 경시대회) | agent03_goals_analysis | agent18_signature_routine | 목표 분석 핵심 |
| 64. 목표 우선순위 | agent03_goals_analysis | agent17_remaining_activities | 목표 분석 |
| 65. 본인이 설정한 목표 vs 부모 설정 | agent03_goals_analysis | agent05_learning_emotion | 목표 분석 |
| 66. 목표에 대한 지속력 | agent03_goals_analysis | agent18_signature_routine | 목표 분석 |
| 67. 목표 달성 후 보상 방식 | agent03_goals_analysis | agent05_learning_emotion | 목표 분석 |
| 68. 목표 리뷰 빈도 | agent03_goals_analysis | agent09_learning_management | 목표 분석 |
| 69. 목표 실패 원인 분석 능력 | agent15_problem_redefinition | agent03_goals_analysis | 문제 재정의 |
| 70. 목표 기반 루틴 이행률 | agent18_signature_routine | agent03_goals_analysis | 시그너처 루틴 |

---

## 8. 보호자 정보 및 참여 (10개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 71. 학습에 대한 관심도 | agent01_onboarding | agent06_teacher_feedback | 보호자 정보 |
| 72. 자주 확인하는 항목 | agent01_onboarding | agent06_teacher_feedback | 보호자 정보 |
| 73. 피드백 방식 (칭찬, 지적 등) | agent06_teacher_feedback | agent05_learning_emotion | 선생님 피드백 핵심 |
| 74. 학습 계획 세워주는지 여부 | agent01_onboarding | agent03_goals_analysis | 보호자 정보 |
| 75. 학습 내용 공유 빈도 | agent01_onboarding | agent06_teacher_feedback | 보호자 정보 |
| 76. 학습 스트레스 조율 방식 | agent12_rest_routine | agent05_learning_emotion | 휴식 루틴 |
| 77. 보호자 직업군 (교육 관련 여부) | agent01_onboarding | agent06_teacher_feedback | 보호자 정보 |
| 78. 보호자의 수학 이해도 | agent01_onboarding | agent06_teacher_feedback | 보호자 정보 |
| 79. 가정 내 학습 분위기 | agent01_onboarding | agent05_learning_emotion | 환경 정보 |
| 80. 주말/방학 학습 지도 방식 | agent01_onboarding | agent09_learning_management | 보호자 정보 |

---

## 9. 시스템 연계 정보 (10개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 81. LMS(학습관리시스템) 연동 여부 | agent01_onboarding | agent09_learning_management | 시스템 연계 |
| 82. 출결 체크 방식 | agent09_learning_management | agent01_onboarding | 학습 관리 |
| 83. 온라인 수업 수강 시간 | agent09_learning_management | agent14_current_position | 학습 관리 |
| 84. AI 콘텐츠 사용 이력 | agent01_onboarding | agent19_interaction_content | 시스템 연계 |
| 85. 진단 평가 API 연동 | agent01_onboarding | agent14_current_position | 시스템 연계 |
| 86. 문제풀이 로그 트래킹 | agent11_problem_notes | agent14_current_position | 문제 노트 |
| 87. 포모도로 타이머 데이터 | agent09_learning_management | agent18_signature_routine | 학습 관리 |
| 88. 진도 자동 측정 도구 연계 | agent14_current_position | agent09_learning_management | 현재 위치 |
| 89. 콘텐츠 추천 알고리즘 연동 여부 | agent19_interaction_content | agent01_onboarding | 상호작용 컨텐츠 |
| 90. 학부모 앱 연동 여부 | agent01_onboarding | agent06_teacher_feedback | 시스템 연계 |

---

## 10. AI 분석 및 추론용 메타 정보 (10개)

| 항목 | 주 담당 에이전트 | 보조 관련 에이전트 | 비고 |
|------|----------------|------------------|------|
| 91. 학습 몰입도 추정값 | agent05_learning_emotion | agent18_signature_routine | 감정 분석 |
| 92. 학습 이탈 패턴 로그 | agent13_learning_dropout | agent14_current_position | 이탈 분석 핵심 |
| 93. 반복 실수 유형 | agent08_calmness | agent11_problem_notes | 침착도 분석 |
| 94. 선행-복습 최적 타이밍 분석 | agent10_concept_notes | agent18_signature_routine | 개념 노트 |
| 95. 루틴 유지 성공률 | agent18_signature_routine | agent09_learning_management | 시그너처 루틴 핵심 |
| 96. 학습 난이도 반응 로그 | agent05_learning_emotion | agent04_problem_activity | 감정 분석 |
| 97. 감정 변동 예측 로그 | agent05_learning_emotion | agent18_signature_routine | 감정 분석 |
| 98. 질문 타이밍 패턴 | agent04_problem_activity | agent05_learning_emotion | 활동 분석 |
| 99. 개입 전/후 효과 측정 데이터 | agent21_intervention_execution | agent20_intervention_preparation | 개입 실행 핵심 |
| 100. 시그너처 루틴 매칭 결과 | agent18_signature_routine | agent05_learning_emotion | 시그너처 루틴 핵심 |

---

## 요약 통계

### 주 담당 에이전트별 데이터 항목 수
- agent01_onboarding: 25개
- agent02_exam_schedule: 5개
- agent03_goals_analysis: 8개
- agent04_problem_activity: 4개
- agent05_learning_emotion: 8개
- agent06_teacher_feedback: 1개
- agent08_calmness: 2개
- agent09_learning_management: 6개
- agent10_concept_notes: 6개
- agent11_problem_notes: 3개
- agent12_rest_routine: 2개
- agent13_learning_dropout: 1개
- agent14_current_position: 6개
- agent15_problem_redefinition: 1개
- agent18_signature_routine: 6개
- agent19_interaction_content: 1개
- agent21_intervention_execution: 1개

**총합: 85개** (일부 데이터는 여러 에이전트에서 공유)

---

## 다음 단계
1. 각 에이전트의 metadata.md 파일 업데이트
2. 관련 데이터 매핑 파일 생성 (JSON)
3. dataindex.html 업데이트 스크립트 개발
4. dataindex.html 파일 업데이트

