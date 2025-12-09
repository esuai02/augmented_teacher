-- 60personas.txt 데이터를 mdl_alt42i_ 테이블에 삽입하는 SQL
-- 60개 수학 학습 패턴 데이터

-- 카테고리 ID 변수 설정
SET @cat_overload = (SELECT id FROM mdl_alt42i_pattern_categories WHERE category_code = 'cognitive_overload');
SET @cat_confidence = (SELECT id FROM mdl_alt42i_pattern_categories WHERE category_code = 'confidence_distortion');
SET @cat_mistake = (SELECT id FROM mdl_alt42i_pattern_categories WHERE category_code = 'mistake_patterns');
SET @cat_approach = (SELECT id FROM mdl_alt42i_pattern_categories WHERE category_code = 'approach_errors');
SET @cat_habit = (SELECT id FROM mdl_alt42i_pattern_categories WHERE category_code = 'study_habits');
SET @cat_time = (SELECT id FROM mdl_alt42i_pattern_categories WHERE category_code = 'time_pressure');
SET @cat_verify = (SELECT id FROM mdl_alt42i_pattern_categories WHERE category_code = 'verification_absence');
SET @cat_other = (SELECT id FROM mdl_alt42i_pattern_categories WHERE category_code = 'other_obstacles');

-- 수학 학습 패턴 데이터 삽입
INSERT INTO mdl_alt42i_math_patterns (pattern_id, pattern_name, pattern_desc, category_id, icon, priority, audio_time) VALUES
-- 인지 과부하 (1-8)
(1, '계산 실수 반복', '같은 유형의 계산 실수를 계속 반복함', @cat_overload, '🔢', 'high', '3분'),
(2, '문제 이해 부족', '문제를 제대로 읽지 않고 풀이 시작', @cat_overload, '📖', 'high', '4분'),
(3, '단계 건너뛰기', '풀이 과정에서 중요한 단계를 생략', @cat_overload, '⏭️', 'medium', '3분'),
(4, '공식 혼동', '비슷한 공식들을 자주 헷갈림', @cat_overload, '🔀', 'high', '5분'),
(5, '주의력 분산', '문제 풀이 중 집중력이 쉽게 흐트러짐', @cat_overload, '😵', 'medium', '3분'),
(6, '복잡한 문제 회피', '어려워 보이는 문제는 시도조차 안 함', @cat_overload, '🚫', 'high', '4분'),
(7, '암기 의존', '이해 없이 암기에만 의존하여 응용 불가', @cat_overload, '🧠', 'high', '5분'),
(8, '개념 연결 실패', '관련 개념들 간의 연결을 못 함', @cat_overload, '🔗', 'medium', '4분'),

-- 자신감 왜곡 (9-15)
(9, '과도한 자신감', '실력보다 문제를 쉽게 봄', @cat_confidence, '😎', 'medium', '3분'),
(10, '자신감 부족', '풀 수 있는 문제도 못 푼다고 생각', @cat_confidence, '😰', 'high', '4분'),
(11, '시험 불안', '시험 상황에서 극도로 긴장함', @cat_confidence, '😱', 'high', '5분'),
(12, '완벽주의', '100% 확신이 없으면 답을 쓰지 않음', @cat_confidence, '💯', 'medium', '3분'),
(13, '실수 공포', '실수할까봐 문제 풀이를 망설임', @cat_confidence, '😨', 'medium', '3분'),
(14, '비교 스트레스', '다른 학생과 비교하며 좌절', @cat_confidence, '📊', 'medium', '4분'),
(15, '포기 습관', '조금만 어려워도 쉽게 포기', @cat_confidence, '🏳️', 'high', '4분'),

-- 실수 패턴 (16-25)
(16, '부호 실수', '+/- 부호를 자주 틀림', @cat_mistake, '➕', 'high', '2분'),
(17, '단위 실수', '단위 변환에서 실수 반복', @cat_mistake, '📏', 'medium', '3분'),
(18, '계산 순서 오류', '연산 순서를 자주 틀림', @cat_mistake, '🔄', 'high', '3분'),
(19, '옮겨 쓰기 실수', '숫자나 식을 옮겨 쓸 때 실수', @cat_mistake, '✍️', 'medium', '2분'),
(20, '그래프 해석 오류', '그래프 읽기에서 반복적 실수', @cat_mistake, '📈', 'medium', '4분'),
(21, '소수점 실수', '소수점 위치를 자주 틀림', @cat_mistake, '🔵', 'medium', '2분'),
(22, '약분 실수', '분수 약분에서 실수 반복', @cat_mistake, '➗', 'medium', '3분'),
(23, '풀이 누락', '답은 맞는데 풀이 과정 누락', @cat_mistake, '📝', 'low', '2분'),
(24, '문제 번호 실수', '다른 문제의 답을 씀', @cat_mistake, '🔢', 'low', '2분'),
(25, '시간 부족 실수', '시간에 쫓겨 실수 증가', @cat_mistake, '⏰', 'high', '3분'),

-- 접근 전략 오류 (26-33)
(26, '무작정 대입', '체계 없이 숫자만 대입해봄', @cat_approach, '🎲', 'medium', '3분'),
(27, '한 가지 방법 고집', '익숙한 방법만 고집함', @cat_approach, '🔨', 'medium', '4분'),
(28, '거꾸로 풀기 미숙', '역산이 필요한 문제 접근 실패', @cat_approach, '⬅️', 'medium', '4분'),
(29, '그림 활용 부족', '그림이나 도표 활용을 안 함', @cat_approach, '🖼️', 'medium', '3분'),
(30, '단위 분석 부재', '단위를 통한 검증을 안 함', @cat_approach, '⚖️', 'low', '3분'),
(31, '극단값 검토 부재', '극단적인 경우를 고려 안 함', @cat_approach, '🎯', 'low', '3분'),
(32, '패턴 인식 부족', '문제의 패턴을 파악 못함', @cat_approach, '🔍', 'high', '4분'),
(33, '조건 활용 미흡', '주어진 조건을 충분히 활용 안 함', @cat_approach, '📋', 'medium', '3분'),

-- 학습 습관 (34-42)
(34, '복습 부재', '한 번 푼 문제는 다시 안 봄', @cat_habit, '🔁', 'high', '3분'),
(35, '오답 정리 미흡', '틀린 문제를 제대로 정리 안 함', @cat_habit, '📓', 'high', '4분'),
(36, '질문 회피', '모르는 것을 질문하지 않음', @cat_habit, '🤐', 'medium', '3분'),
(37, '혼자 학습 고집', '도움 받기를 거부함', @cat_habit, '🏝️', 'low', '3분'),
(38, '벼락치기', '시험 직전에만 공부함', @cat_habit, '⚡', 'high', '4분'),
(39, '정리 노트 부재', '체계적인 정리를 안 함', @cat_habit, '📚', 'medium', '3분'),
(40, '연습 부족', '개념만 보고 문제 풀이 연습 부족', @cat_habit, '💪', 'high', '3분'),
(41, '피드백 무시', '선생님 피드백을 반영 안 함', @cat_habit, '👂', 'medium', '3분'),
(42, '목표 설정 부재', '구체적인 학습 목표가 없음', @cat_habit, '🎯', 'medium', '3분'),

-- 시간/압박 관리 (43-49)
(43, '시간 배분 실패', '문제별 시간 배분을 못함', @cat_time, '⏱️', 'high', '3분'),
(44, '속도 압박', '빨리 풀어야 한다는 압박감', @cat_time, '🏃', 'medium', '3분'),
(45, '마감 스트레스', '제출 시간이 다가올수록 실수 증가', @cat_time, '⏳', 'medium', '3분'),
(46, '쉬운 문제 과투자', '쉬운 문제에 시간을 너무 씀', @cat_time, '🐌', 'medium', '3분'),
(47, '어려운 문제 집착', '한 문제에 너무 오래 매달림', @cat_time, '🔒', 'high', '3분'),
(48, '시간 체크 과다', '시계를 너무 자주 봄', @cat_time, '⌚', 'low', '2분'),
(49, '페이스 조절 실패', '일정한 속도 유지를 못함', @cat_time, '🎢', 'medium', '3분'),

-- 검증/확인 부재 (50-55)
(50, '검산 생략', '답을 구한 후 검산을 안 함', @cat_verify, '✔️', 'high', '3분'),
(51, '논리 검증 부재', '답의 논리적 타당성 검토 안 함', @cat_verify, '🤔', 'medium', '3분'),
(52, '조건 확인 누락', '모든 조건을 만족하는지 확인 안 함', @cat_verify, '📃', 'medium', '3분'),
(53, '단위 확인 누락', '답의 단위가 맞는지 확인 안 함', @cat_verify, '📐', 'low', '2분'),
(54, '범위 검토 부재', '답이 합리적 범위인지 확인 안 함', @cat_verify, '🎚️', 'low', '2분'),
(55, '문제 재확인 부재', '문제를 다시 읽어보지 않음', @cat_verify, '👀', 'medium', '2분'),

-- 기타 장애 (56-60)
(56, '필기구 문제', '연필, 지우개 등 준비 부족', @cat_other, '✏️', 'low', '2분'),
(57, '환경 방해', '주변 소음이나 방해 요소에 민감', @cat_other, '🔊', 'low', '3분'),
(58, '신체 불편', '자세나 피로로 집중력 저하', @cat_other, '😫', 'low', '3분'),
(59, '도구 활용 미숙', '계산기, 자, 컴퍼스 사용 미숙', @cat_other, '📱', 'low', '3분'),
(60, '선입견', '특정 유형은 못 푼다는 선입견', @cat_other, '🚧', 'medium', '3분');

-- 패턴별 해결책 데이터 삽입
INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog) 
SELECT 
    p.id,
    CASE p.pattern_id
        WHEN 1 THEN '계산 과정을 단계별로 나누어 쓰기, 각 단계마다 확인'
        WHEN 2 THEN '문제를 두 번 읽고 중요 정보에 밑줄 긋기'
        WHEN 3 THEN '풀이 템플릿을 만들어 모든 단계 체크하기'
        WHEN 4 THEN '공식 카드를 만들어 차이점 명확히 정리'
        WHEN 5 THEN '25분 집중 - 5분 휴식 패턴으로 학습'
        WHEN 6 THEN '복잡한 문제를 작은 단계로 나누어 접근'
        WHEN 7 THEN '공식 유도 과정을 이해하고 예제와 연결'
        WHEN 8 THEN '개념 맵을 그려 관계를 시각화'
        WHEN 9 THEN '문제 풀이 후 반드시 검산하는 습관'
        WHEN 10 THEN '작은 성공 경험을 기록하고 축적'
        WHEN 11 THEN '심호흡과 긍정적 자기 대화 연습'
        WHEN 12 THEN '70% 확신이면 답을 쓰고 표시해두기'
        WHEN 13 THEN '실수를 학습 기회로 인식하기'
        WHEN 14 THEN '자신의 성장에만 집중하기'
        WHEN 15 THEN '포기하기 전 3분만 더 시도하기'
        WHEN 16 THEN '부호는 별도로 체크하는 습관 만들기'
        WHEN 17 THEN '단위 변환표를 항상 참고하기'
        WHEN 18 THEN 'PEMDAS 순서를 매번 확인하기'
        WHEN 19 THEN '한 줄씩 천천히 옮겨 쓰기'
        WHEN 20 THEN '그래프의 축과 단위 먼저 확인'
        WHEN 21 THEN '소수점 위치를 별도 표시'
        WHEN 22 THEN '최대공약수 찾기 연습'
        WHEN 23 THEN '풀이 과정 템플릿 사용'
        WHEN 24 THEN '문제 번호를 크게 표시'
        WHEN 25 THEN '시간 압박 상황 연습'
        WHEN 26 THEN '문제 유형별 접근법 정리'
        WHEN 27 THEN '다양한 풀이 방법 시도'
        WHEN 28 THEN '결과에서 거꾸로 추론 연습'
        WHEN 29 THEN '문제를 그림으로 표현하기'
        WHEN 30 THEN '단위 분석으로 식 검증'
        WHEN 31 THEN '극값 대입으로 답 검증'
        WHEN 32 THEN '유사 문제 비교 분석'
        WHEN 33 THEN '조건을 체크리스트로 정리'
        WHEN 34 THEN '주기적 복습 스케줄 만들기'
        WHEN 35 THEN '오답노트 체계적 작성'
        WHEN 36 THEN '질문 목록 만들어 준비'
        WHEN 37 THEN '스터디 그룹 참여'
        WHEN 38 THEN '일일 학습 계획 수립'
        WHEN 39 THEN '단원별 요약 노트 작성'
        WHEN 40 THEN '매일 문제 풀이 연습'
        WHEN 41 THEN '피드백 체크리스트 만들기'
        WHEN 42 THEN 'SMART 목표 설정'
        WHEN 43 THEN '문제별 예상 시간 설정'
        WHEN 44 THEN '정확도 우선 전략'
        WHEN 45 THEN '마지막 5분 검토 시간 확보'
        WHEN 46 THEN '문제 난이도 빠른 파악'
        WHEN 47 THEN '3분 규칙 적용'
        WHEN 48 THEN '시간은 10분 단위로만 체크'
        WHEN 49 THEN '일정한 호흡 유지'
        WHEN 50 THEN '검산 체크리스트 사용'
        WHEN 51 THEN '상식선에서 답 검토'
        WHEN 52 THEN '조건 체크 박스 만들기'
        WHEN 53 THEN '단위 일치 확인'
        WHEN 54 THEN '예상 범위와 비교'
        WHEN 55 THEN '제출 전 문제 재독'
        WHEN 56 THEN '필기구 체크리스트'
        WHEN 57 THEN '집중력 향상 훈련'
        WHEN 58 THEN '올바른 자세 유지'
        WHEN 59 THEN '도구 사용법 연습'
        WHEN 60 THEN '성공 경험 기록하기'
        ELSE '기본 해결 방법'
    END,
    CASE p.pattern_id
        WHEN 1 THEN '계산 후 역산으로 검증했는가?'
        WHEN 2 THEN '문제의 모든 조건을 파악했는가?'
        WHEN 3 THEN '모든 풀이 단계를 거쳤는가?'
        WHEN 4 THEN '올바른 공식을 선택했는가?'
        WHEN 5 THEN '집중력이 유지되었는가?'
        WHEN 6 THEN '문제를 단계별로 나누었는가?'
        WHEN 7 THEN '공식의 의미를 이해했는가?'
        WHEN 8 THEN '개념 간 연결을 파악했는가?'
        WHEN 9 THEN '검산을 수행했는가?'
        WHEN 10 THEN '자신감이 향상되었는가?'
        WHEN 11 THEN '긴장이 완화되었는가?'
        WHEN 12 THEN '답안을 작성했는가?'
        WHEN 13 THEN '실수를 받아들였는가?'
        WHEN 14 THEN '비교를 멈추었는가?'
        WHEN 15 THEN '끝까지 시도했는가?'
        WHEN 16 THEN '부호를 확인했는가?'
        WHEN 17 THEN '단위가 일치하는가?'
        WHEN 18 THEN '연산 순서가 맞는가?'
        WHEN 19 THEN '정확히 옮겨 썼는가?'
        WHEN 20 THEN '그래프를 올바르게 읽었는가?'
        WHEN 21 THEN '소수점 위치가 맞는가?'
        WHEN 22 THEN '약분이 완료되었는가?'
        WHEN 23 THEN '풀이 과정이 완전한가?'
        WHEN 24 THEN '문제 번호가 일치하는가?'
        WHEN 25 THEN '시간 내에 완료했는가?'
        WHEN 26 THEN '체계적으로 접근했는가?'
        WHEN 27 THEN '다른 방법을 시도했는가?'
        WHEN 28 THEN '역산을 활용했는가?'
        WHEN 29 THEN '그림을 그렸는가?'
        WHEN 30 THEN '단위를 확인했는가?'
        WHEN 31 THEN '극값을 검토했는가?'
        WHEN 32 THEN '패턴을 발견했는가?'
        WHEN 33 THEN '모든 조건을 활용했는가?'
        WHEN 34 THEN '복습을 수행했는가?'
        WHEN 35 THEN '오답을 정리했는가?'
        WHEN 36 THEN '질문을 준비했는가?'
        WHEN 37 THEN '협력 학습을 했는가?'
        WHEN 38 THEN '꾸준히 학습했는가?'
        WHEN 39 THEN '노트를 정리했는가?'
        WHEN 40 THEN '충분히 연습했는가?'
        WHEN 41 THEN '피드백을 반영했는가?'
        WHEN 42 THEN '목표를 설정했는가?'
        WHEN 43 THEN '시간을 잘 배분했는가?'
        WHEN 44 THEN '침착하게 풀었는가?'
        WHEN 45 THEN '여유를 가졌는가?'
        WHEN 46 THEN '효율적으로 풀었는가?'
        WHEN 47 THEN '적절히 넘어갔는가?'
        WHEN 48 THEN '시간에 얽매이지 않았는가?'
        WHEN 49 THEN '일정한 속도를 유지했는가?'
        WHEN 50 THEN '검산을 완료했는가?'
        WHEN 51 THEN '논리적으로 타당한가?'
        WHEN 52 THEN '모든 조건을 만족하는가?'
        WHEN 53 THEN '단위가 올바른가?'
        WHEN 54 THEN '답이 합리적인가?'
        WHEN 55 THEN '문제를 재확인했는가?'
        WHEN 56 THEN '도구가 준비되었는가?'
        WHEN 57 THEN '집중할 수 있었는가?'
        WHEN 58 THEN '편안한 상태인가?'
        WHEN 59 THEN '도구를 잘 사용했는가?'
        WHEN 60 THEN '선입견을 극복했는가?'
        ELSE '기본 확인 사항'
    END,
    CASE p.pattern_id
        WHEN 1 THEN '계산 실수는 서두르지 않고 차근차근 풀면 줄일 수 있어요. 한 단계씩 확인하며 풀어봅시다.'
        WHEN 2 THEN '문제를 꼼꼼히 읽는 것이 정답의 첫걸음입니다. 중요한 정보에 표시하며 읽어보세요.'
        ELSE '이 패턴을 인식한 것만으로도 큰 발전입니다. 함께 개선해 나가요.'
    END,
    CASE p.pattern_id
        WHEN 1 THEN '계산 실수가 자주 일어나는 것 같네요. 함께 단계별로 확인하는 방법을 연습해볼까요?'
        WHEN 2 THEN '문제 이해에 어려움이 있나요? 문제를 함께 분석해보면서 중요한 정보를 찾아봅시다.'
        ELSE '이런 어려움을 겪는 것은 자연스러운 일입니다. 함께 해결 방법을 찾아보겠습니다.'
    END
FROM mdl_alt42i_math_patterns p;

-- 데이터 삽입 완료 메시지
SELECT 'Math persona data inserted successfully' AS status,
       COUNT(*) as total_patterns 
FROM mdl_alt42i_math_patterns;