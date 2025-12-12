시험일정 기반 학습전략 수립 에이전트가 현실 세계에서 완벽하게 작동하기 위해서는 **시험 대비 전략 수립·조정·실행 협업에 실제로 필요한 데이터**가 필요합니다. 아래는 **Agent 02 - Exam Schedule** 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

## 📅 1. 시험 일정 및 범위 정보 (12)

1. 시험명 (중간/기말/단원평가 등)
2. 시험 시작일 / 종료일
3. D-day (남은 일수, ExamDDay)
4. 과목명 (수학/과학/국어 등)
5. 시험 범위(단원명, 쪽수, 개념목록 포함, ExamScope)
6. 시험 범위 내 주요 개념 난이도 분포
7. 시험 범위 내 유형 분류(기본문제/서술형/고난도)
8. 시험 범위 내 출제 비중(단원별 %)
9. 과거 동일 단원 출제 빈도
10. 학교별 출제 경향 데이터
11. 시험 중요도 (내신 반영 비율, 성적 영향도)
12. 시험 준비 시작일 및 계획 착수일

---

## 📘 2. 교재·콘텐츠 정보 (10)

13. 학교 교재명, 출판사
14. 학원 교재명 (쎈/RPM/블랙라벨 등, AcademyTextbook)
15. 교재별 단원 커버리지(시험범위 대비 %, TextbookCoverage)
16. 교재별 난이도 편차 지수
17. 교재별 풀이 속도(분/문항)
18. 온라인 콘텐츠(강의/문제풀이) 이용 내역
19. 교재별 완료 페이지/단원 수
20. 교재별 남은 학습량(%)
21. AI 추천 콘텐츠 매칭 내역
22. 교재별 문제유형 태그(개념/응용/심화)

---

## 🧮 3. 학원 관련 데이터 (10)

23. 학원명 / 반 / 레벨 (AcademyName, AcademyGrade)
24. 학원 수업 요일 및 시간 (AcademySchedule)
25. 학원 진도 단원 (AcademyProgress)
26. 학원 진도와 학교 진도의 차이(주 단위, ProgressGap)
27. 학원 과제 목록 및 완료율(%)
28. 학원 과제별 난이도 태그
29. 학원 모의고사 일정 (MockExamSchedule)
30. 학원 등수/석차 (AcademyRank)
31. 학원 피드백 데이터 (AcademyFeedbackData)
32. 학원 교재별 활용 전략 (AcademyTextbookStrategy)

---

## 📚 4. 수학 학습 진도 정보 (8)

33. 최근 학교 시험 범위 (SchoolExamScope)
34. 수학 내신 등급
35. 단원별 정답률 (UnitAccuracyRate)
36. 교재별 진도율 (TextbookProgressRate)
37. 개념:유형:심화:기출 비율 (StrategyRatio)
38. 단원 우선순위 (UnitPriority)
39. 시간 대비 효율 가중치 (TimeWeightedEfficiency)
40. 현재 예상 점수 (CurrentExpectedScore)

---

## 🎯 5. 목표 및 성적 정보 (6)

41. 목표 점수 (TargetScore)
42. 점수 격차 (ScoreGap)
43. 학교 성적 (SchoolScore)
44. 학원 등수와 학교 성적 일관성 (RankScoreConsistency)
45. 문항 유형별 성공률 (QuestionTypeSuccessRate)
46. 과거 시험 성적 히스토리

---

## 🧠 6. 학습 성향 및 습관 (2)

47. 시험 공부 방식 (ExamStyle)
48. 학습 루틴 유지율 (RoutineMaintenanceRate)

---

## 🔄 7. 학습 정렬 및 시간 배분 (8)

49. 학원-학교-집 학습 정렬 (AcademySchoolHomeAlignment)
50. 진도 격차 분석 (ProgressGapAnalysis)
51. 시험 D-day별 시간 자원 배분 (TimeResourceAllocation)
52. 학원 과제 소요 시간 (AcademyAssignmentTime)
53. 학원 과제 피로도 (AcademyAssignmentFatigue)
54. 집 공부 루틴 (HomeStudyRoutine)
55. 시간 배분 리셋 루틴 (TimeAllocationReset)
56. 최적 학습 시간표 (OptimalSchedule)

---

## 📊 8. 시험 분석 및 개선 (10)

57. 학원 교재 효과 지표 (TextbookEffectivenessIndex)
58. 문제 커버리지 vs 시험 적중률 (CoverageVsHitRate)
59. 반복 실수 패턴 (RepeatedErrorPattern)
60. 계산 오류 비율 (CalculationErrorRatio)
61. 개념 오류 비율 (ConceptErrorRatio)
62. 학습 루틴 유지율 변동 (RoutineMaintenanceVariation)
63. 학부모 피드백 반영률 (ParentFeedbackReflectionRate)
64. 학원 피드백 반영률 (AcademyFeedbackReflectionRate)
65. 다음 시험 루프 초기화 전략 (NextExamLoopInit)
66. 보완 단원 리스트 (ReinforcementUnitList)

---

## 🧾 9. 학습 이력 (2)

67. 과거 시험 성적 히스토리
68. 최근 3회 시험 점수

---

**참고**: 
- 다른 에이전트에 배치된 관련 데이터는 각 에이전트의 dataindex.html에서 확인할 수 있습니다.
- 목표 관련 데이터는 Agent 03에서 관리됩니다.
- 감정 및 피로 관련 데이터는 Agent 05에서 관리됩니다.
- 루틴 관련 데이터는 Agent 09, Agent 18에서 관리됩니다.
- 취약점 분석 데이터는 Agent 04에서 관리됩니다.
