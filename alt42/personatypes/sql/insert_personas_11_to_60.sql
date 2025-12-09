-- Insert personas 11-60 from 60personas.txt into the database
-- This script inserts the data into mdl_alt42i_math_patterns and mdl_alt42i_pattern_solutions tables

-- Make sure category data is already inserted (from create_math_persona_tables.sql)

-- Insert patterns for personas 11-60

-- Persona 11: 속도 압박 억제형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(11, '속도 압박 억제형', '시험 시간이 눈에 들어올 때마다 ''빨리 해야 한다''는 압박이 새 아이디어와 기억을 눌러 버리는 패턴.', 6, '⏰', 'high', '1:50', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(11, '시작과 동시에 손목시계·휴대폰 시계 뒤집기 → 조용 타이머를 15분 간격으로 설정(삐 소리 X, 진동 O) → 타이머 울릴 때마다 현재 문제를 1문장으로 요약 후 진행 여부 판단', '15분 타이머를 4번 돌렸는지, 진동이 왔을 때 요약이 적절했는지 확인', '시간 압박을 느끼면 오히려 실수가 늘어납니다. 시계를 뒤집어 놓고 15분마다 울리는 조용한 타이머를 사용하세요. 타이머가 울릴 때마다 현재 상황을 한 문장으로 정리하면 페이스를 유지할 수 있습니다.', '15분 타이머를 4번 돌렸는데 진동이 왔을 때 제 요약이 적절했는지, 한 번만 확인 부탁드려요.', NOW(), NOW());

-- Persona 12: 시험 트라우마 악수형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(12, '시험 트라우마 악수형', '과거에 시험을 망친 기억이 문제 순서·전략에 투영돼 ''악수''를 두는 패턴.', 6, '💔', 'high', '2:35', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(12, '시작 2분 내에 ''가장 쉬운 2문제''를 골라 먼저 해결 → 성공감이 생기면 그다음 문제를 난도별 라벨링(L·M·H) 후 착수 → 45분 세션 후 성공 → 어려움 순서를 다시 리뷰', 'Easy-Start 전략으로 첫 2문제를 풀었는지, 난이도 라벨이 정확했는지 피드백', '과거의 실패 경험이 현재 시험에 영향을 주지 않도록 하세요. 시작할 때 가장 쉬운 2문제를 먼저 풀어 성공감을 만드세요. 그 다음 문제들을 난이도별로 분류하고 전략적으로 접근하면 트라우마를 극복할 수 있습니다.', 'Easy-Start 전략으로 첫 2문제를 풀었어요. 제 난이도 라벨이 정확했는지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 13: 징검다리 난도적형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(13, '징검다리 난도적형', '청킹 없이 산발적으로 추론해 전역 구조를 놓치는 패턴.', 4, '🪨', 'medium', '2:45', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(13, '문제를 3~4개 ''청크''로 나누고 각 단계에 번호(①②③…) 붙이기 → 단계 끝마다 ''다음 단계 조건''을 한 줄 메모 → 최종 답 후 번호 순서를 거꾸로 점검(③→②→①)', '청크 3단계를 거꾸로 리뷰했는지, 연결 고리가 자연스러운지 확인', '복잡한 문제는 작은 단위로 나누어 접근하세요. 3-4개의 청크로 나누고 각각에 번호를 붙여 단계적으로 해결하세요. 마지막에는 거꾸로 점검하여 논리적 연결이 맞는지 확인하면 전체 구조를 놓치지 않습니다.', '청크 3단계를 거꾸로 리뷰했습니다. 제 연결 고리가 자연스러운지 봐주실 수 있나요?', NOW(), NOW());

-- Persona 14: 무의식 재현 루프형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(14, '무의식 재현 루프형', '예전에 성공했던 공식을 맹목적으로 재사용하며 문제 특성을 무시하는 패턴.', 5, '🔄', 'low', '2:15', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(14, '공식 사용할 때 ''조건 동일?'' 체크박스를 옆에 그리기 → 조건이 다르면 즉시 다른 방법(그래프, 역함수, 대수 등) 후보를 메모 → 학습 후 ''조건 불일치 발견 목록''을 주간 로그에 기록', '오늘 조건 체크박스를 5번 그렸는데, 2번은 불일치였다면 다른 대안이 적절했는지 검토', '과거에 성공했던 방법이라도 새로운 문제에는 맞지 않을 수 있습니다. 공식을 사용하기 전에 조건이 동일한지 체크박스로 확인하세요. 조건이 다르면 다른 접근 방법을 고려하고 이를 기록해두세요.', '오늘 조건 체크박스를 5번 그렸는데, 2번은 불일치였습니다. 다른 대안이 적절했는지 검토 부탁드립니다.', NOW(), NOW());

-- Persona 15: 조건 회피-추론 생략형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(15, '조건 회피-추론 생략형', '복잡한 조건을 ''시야 밖''으로 밀어두고 직감만으로 추론을 강행하는 패턴.', 7, '👁️', 'high', '1:40', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(15, '문제의 각 조건 옆에 ✔︎를 표시하고 한글로 5-7단어 요약 작성 → 풀이 중 조건을 사용할 때마다 ✔︎ 색깔을 검정 → 초록으로 변경 → 남은 검정 ✔︎가 있으면 풀이 완료 전 반드시 조건 재적용', '초록으로 바뀌지 않은 조건이 하나 남았는지, 어디에 반영해야 할지 조언', '긴 조건을 무시하고 직감으로만 풀면 실수하기 쉽습니다. 각 조건 옆에 체크표시를 하고 간단히 요약하세요. 사용한 조건은 색을 바꿔가며 표시하고, 사용하지 않은 조건이 있으면 반드시 다시 확인하세요.', '초록으로 바뀌지 않은 조건이 하나 남았는데, 어디에 반영해야 할지 조언 부탁드려요.', NOW(), NOW());

-- Persona 16: 확률적 답안 던지기형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(16, '확률적 답안 던지기형', '근거가 부족한데도 ''일단 찍어보자'' 식으로 답을 기입해 오류가 연쇄되는 패턴.', 4, '🎯', 'medium', '1:55', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(16, '근거가 약할 때는 노란 포스트잇에 ''확신 ★☆☆'' 등급 표시 → ★ 1·2개가 붙은 줄은 풀이 끝에 재검산(역대입, 단위 확인 등) 필수 → 최종 제출 전, ★ 표시가 있는 줄만 모아서 1분 스피드 셀프 퀴즈', '★ 표시를 붙인 줄을 모아 1분 퀴즈를 했는지, 재검 과정이 충분했는지 확인', '답을 확신할 수 없을 때 무작정 찍지 마세요. 노란 포스트잇에 확신도를 별점으로 표시하고, 확신도가 낮은 부분은 반드시 재검산하세요. 제출 전에는 이런 부분들만 모아서 빠르게 다시 확인하는 것이 중요합니다.', '★ 표시를 붙인 줄을 모아 1분 퀴즈를 했습니다. 재검 과정이 충분했는지 확인해 주실 수 있나요?', NOW(), NOW());

-- Persona 17: 방심 단기 기억 증발형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(17, '방심 단기 기억 증발형', '잠깐 산만해지면서 방금 세운 관계식이나 조건을 잊어버리는 패턴.', 8, '💭', 'low', '1:45', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(17, '새 식·조건을 세울 때마다 왼쪽 여백에 번호 목록으로 기록 → 산만함을 느끼면 즉시 목록을 큰 소리로 1줄 복창 → 풀이 종료 후 목록과 실제 풀이를 체크‧매칭', '목록에 적은 5개의 식을 복창했는지, 연결이 부자연스러운 부분이 있는지 확인', '집중력이 흐트러지면 방금 전에 세운 식이나 조건을 잊기 쉽습니다. 중요한 식은 왼쪽 여백에 번호를 매겨 기록하고, 산만해질 때 큰 소리로 복창하세요. 이렇게 하면 기억을 되살릴 수 있습니다.', '목록에 적은 5개의 식을 복창했는데, 연결이 부자연스러운 부분이 있는지 봐주실래요?', NOW(), NOW());

-- Persona 18: 도구 의존 과적형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(18, '도구 의존 과적형', 'CAS·계산기에 과도하게 의존해 개념 이해·추론 회로가 비활성화되는 패턴.', 8, '🔧', 'low', '2:30', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(18, 'CAS 입력 전에 예상 결과 범위(↑↓)·부호·대략 값을 손으로 스케치 → 계산 결과가 나오면 예상 vs 결과를 3초 비교해 차이를 표시 → 차이가 크면 계산 단계나 모델링 방식을 수작업으로 한 번 더 검산', '예상한 범위와 CAS 결과가 다를 때 어떤 개념을 더 확인해야 할지 조언', '계산기나 CAS에만 의존하면 개념 이해가 약해집니다. 도구를 사용하기 전에 예상 결과를 손으로 적어보고, 실제 결과와 비교하세요. 차이가 크면 개념을 다시 확인하고 손으로 검산해보는 것이 중요합니다.', '제가 예상한 범위와 CAS 결과가 다를 때 어떤 개념을 더 확인해야 할지 조언 부탁드립니다.', NOW(), NOW());

-- Persona 19: 과거 방식 고착형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(19, '과거 방식 고착형', '새로운 유형도 과거에 익숙했던 공식·방법만 고집하는 패턴.', 5, '📚', 'medium', '2:10', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(19, '문제를 읽고 30초 간 ''이 유형을 처음 본다면?'' 스스로 질문 → 떠오른 대안 풀이를 메모 2줄로 적어보기 → 실제 풀이 후 기존 공식 vs 대안 풀이의 장·단점 비교 작성', '30초 질문으로 떠올린 대안 풀이가 있었는지, 타당했는지 피드백', '익숙한 방법만 고집하면 새로운 문제를 해결하기 어렵습니다. 문제를 볼 때 ''처음 본다면 어떻게 풀까?'' 하고 30초간 생각해보세요. 새로운 접근법을 시도하고 기존 방법과 비교하면 더 넓은 시야를 가질 수 있습니다.', '30초 질문으로 떠올린 대안 풀이가 있었는데, 타당했는지 피드백을 듣고 싶어요.', NOW(), NOW());

-- Persona 20: 불완전 개념 종결형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(20, '불완전 개념 종결형', '정의·조건을 끝까지 읽지 않고 ''충분해''라고 판단해 풀이를 서둘러 종결하는 패턴.', 7, '✂️', 'high', '1:30', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(20, '문제에 나온 용어·명제는 노트 하단에 정의 원문을 그대로 필사 → 풀이 중 해당 정의를 적용할 때 밑줄 + 옆에 페이지 참조 표시 → 풀이 후 ''정의 적용 위치''를 하이라이트 색으로 모두 확인', '원문 정의를 필사했는지, 적용한 부분이 정의 조건과 일치하는지 검토', '정의나 조건을 대충 읽고 넘어가면 중요한 부분을 놓칩니다. 문제의 용어 정의를 노트에 그대로 베껴 쓰고, 풀이할 때 정확히 적용했는지 표시하세요. 나중에 하이라이트로 다시 확인하면 실수를 방지할 수 있습니다.', '원문 정의를 필사했는데, 제가 적용한 부분이 정의 조건과 일치하는지 검토해 주세요.', NOW(), NOW());

-- Persona 21: 피로-오답 포용형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(21, '피로-오답 포용형', '체력이 떨어질수록 오류 감지력이 급감해 ''이 정도면 됐겠지'' 하고 넘어가는 패턴.', 8, '😴', 'medium', '2:00', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(21, '30분 집중 + 2분 눈·목 스트레칭 루틴(타이머 필수) → 피로 신호(눈 따가움, 하품) 느끼면 물 3모금 + 10초 눈감기 → 세션 마지막 5분은 반드시 검산 전용으로 예약', '30 + 2 루틴을 4세트 돌렸는지, 마지막 검산 전/후에 찾은 오류 확인', '피로가 쌓이면 실수를 놓치기 쉽습니다. 30분 집중하고 2분간 눈과 목을 스트레칭하세요. 피로를 느끼면 물을 마시고 잠시 눈을 감으세요. 세션 마지막 5분은 검산 시간으로 확보하는 것이 중요합니다.', '30 + 2 루틴을 4세트 돌렸습니다. 마지막 검산 전/후에 찾은 오류를 함께 확인해 주실 수 있을까요?', NOW(), NOW());

-- Persona 22: 감정 전염 스트레스형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(22, '감정 전염 스트레스형', '옆 친구·교사 표정 / 소음에 불안이 증폭돼 작업기억 용량이 급락하는 패턴.', 8, '😟', 'medium', '1:50', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(22, '불안을 느끼면 즉시 4-7-8 호흡법(4초 들숨-7초 정지-8초 날숨) 1회 → 집중 음악(화이트노이즈·Lo-fi) 1곡 반복 설정 → 방해 요소가 지속되면 A6 메모지에 감정 상태 한 단어 적고 덮기', '오늘 4-7-8 호흡을 세 번 했는지, 집중도 변화가 보였는지 피드백', '주변 환경에 영향을 받아 불안해지면 4-7-8 호흡법을 사용하세요. 4초 들이쉬고, 7초 멈추고, 8초 내쉬세요. 집중 음악을 들으며 감정 상태를 메모하면 마음이 안정됩니다.', '오늘 4-7-8 호흡을 세 번 했습니다. 제 집중도 변화가 보였는지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 23: 과다 정보 섭취형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(23, '과다 정보 섭취형', '한 문제를 풀며 해설·영상·블로그 등 여러 자료를 동시에 열어 인지 부하가 폭발하는 패턴.', 1, '📱', 'medium', '2:15', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(23, '문제당 참고자료 최대 2개 원칙(노트 상단에 자료명 기입) → 추가 자료가 필요하면 기존 2개 중 1개를 닫고 새로 연다 → 학습 끝나면 참고자료 목록을 요약 5줄로 정리', '두 자료만 사용해 5줄 요약을 작성했는지, 중요한 포인트가 빠졌는지 확인', '너무 많은 자료를 동시에 보면 오히려 혼란스러워집니다. 문제당 참고자료는 최대 2개로 제한하고, 더 필요하면 기존 것을 닫고 새로 여세요. 학습 후에는 핵심만 5줄로 요약하면 정리가 잘됩니다.', '두 자료만 사용해 5줄 요약을 작성했습니다. 중요한 포인트가 빠졌는지 확인해 주세요.', NOW(), NOW());

-- Persona 24: 이론-연산 전도형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(24, '이론-연산 전도형', '개념 증명·이론에 깊게 몰입하다가 정작 필수 계산(연산)을 뒤로 밀어 실수를 유발하는 패턴.', 4, '🔢', 'low', '2:05', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(24, '증명 줄이 10줄을 넘기면 바로 계산 단계 체크 박스 작성 → 증명 ↔ 계산을 N:1 교차(10줄마다 계산 1번) 구조로 강제 → 최종 답 후 증명·계산 단계를 색깔 다른 하이라이터로 구분 표시', 'N:1 교차 구조를 적용했는지, 계산 삽입 위치가 적절했는지 확인', '이론에만 빠져 계산을 소홀히 하면 실수가 생깁니다. 증명이 10줄을 넘으면 반드시 계산을 한 번씩 하세요. 증명과 계산을 교대로 하면서 균형을 맞추고, 나중에 색깔로 구분해서 표시하면 좋습니다.', 'N:1 교차 구조를 적용했는데, 계산 삽입 위치가 적절했는지 봐주실래요?', NOW(), NOW());

-- Persona 25: 단일 예시 착시형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(25, '단일 예시 착시형', '특정 예제에서 성공한 방식을 새 문제에 그대로 적용해 예외 상황을 놓치는 패턴.', 5, '🔍', 'medium', '1:55', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(25, '새 문제 시작 시 ''예시와 다른 점 3개''를 빠르게 메모 → 풀이 중 3개의 차이가 모두 반영됐는지 중간·최종에 체크 → 주간 회고 때 ''예시 착시 → 교정 성공 사례''를 포트폴리오에 기록', '예시와 다른 점 3개 중 2개만 반영된 것 같다면, 남은 1개를 어디서 고려해야 할지 조언', '하나의 예시만 보고 모든 문제를 풀 수는 없습니다. 새 문제를 볼 때 예시와 다른 점 3개를 먼저 찾아 메모하세요. 풀이 중에 이 차이점들이 모두 반영됐는지 확인하면 실수를 줄일 수 있습니다.', '예시와 다른 점 3개 중 2개만 반영된 것 같습니다. 남은 1개를 어디서 고려해야 할지 조언 부탁드립니다.', NOW(), NOW());

-- Persona 26: 시간 왜곡 긴장형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(26, '시간 왜곡 긴장형', '제한 시간을 실제보다 덜/더 급하게 느껴 불필요한 조급함·지연을 만드는 패턴.', 6, '⏳', 'medium', '2:20', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(26, '세션 60분을 45분 타이머 + 15분 여유로 나누기 → 45분 타이머 종료 시 현재 진행도를 %로 적기(예: 70%) → 남은 15분은 검산·보완 전용 영역으로만 사용', '45분 지점에서 진행도를 68%로 측정했다면, 남은 32%를 15분에 채우는 전략이 적절했는지 조언', '시간을 왜곡해서 느끼면 불필요하게 서두르거나 늦어집니다. 60분을 45분 작업과 15분 여유로 나누고, 45분 지점에서 진행도를 체크하세요. 남은 시간은 검산과 보완에만 사용하면 시간 관리가 효율적입니다.', '45분 지점에서 제 진행도를 68%로 측정했어요. 남은 32%를 15분에 채우는 전략이 적절했는지 조언 부탁드립니다.', NOW(), NOW());

-- Persona 27: 보상 심리 도박형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(27, '보상 심리 도박형', '앞선 실수를 만회하려는 집착으로 복잡한(때론 불필요한) 해법을 억지로 채택하는 패턴.', 8, '🎰', 'medium', '2:10', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(27, '''분노 수정'' 감정을 느끼면 2분 워킹 브레이크(자리서 20걸음 왕복) → 돌아와서 현재 문제 난이도를 L·M·H 중 다시 판단 → 고난도(''H'')로 변질되면, 바로 새로운 문제로 전환 후 나중에 재도전', '실수 뒤 2분 걷고 난 뒤 난이도를 재평가했는지, 전환 시점을 올바르게 잡았는지 확인', '실수를 만회하려고 무리하면 더 큰 실수를 하게 됩니다. 화가 나면 2분간 걸으며 마음을 진정시키고, 문제 난이도를 다시 평가하세요. 너무 어려워졌다면 다른 문제로 넘어가는 것이 현명합니다.', '실수 뒤 2분 걷고 난 뒤 난이도를 재평가했습니다. 제가 전환 시점을 올바르게 잡았는지 확인해 주세요.', NOW(), NOW());

-- Persona 28: 공간-시각 혼선형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(28, '공간-시각 혼선형', '도형·그래프·좌표를 머릿속에 잘못 배치해 관계를 뒤집어 버리는 패턴.', 3, '📐', 'medium', '2:25', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(28, '문제를 읽자마자 A6 메모지에 빠른 스케치(축·꼭짓점·변수 기입) → 변수나 길이 변화가 생길 때마다 스케치를 즉시 업데이트 → 풀이 완료 후 스케치 ↔ 최종 답을 색펜 화살표로 연결', '업데이트한 스케치를 보여드릴 때, 변수 변화 반영이 제대로 됐는지 확인', '도형이나 그래프를 머릿속으로만 그리면 착각하기 쉽습니다. 문제를 읽자마자 A6 메모지에 빠르게 스케치하고, 변화가 있을 때마다 즉시 업데이트하세요. 마지막에 스케치와 답을 연결해서 확인하면 실수를 방지할 수 있습니다.', '업데이트한 스케치를 보여드릴게요. 변수 변화 반영이 제대로 됐는지 확인 부탁드립니다.', NOW(), NOW());

-- Persona 29: 자기긍정 과열형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(29, '자기긍정 과열형', '''이건 내가 잘하던 유형''이라는 자기암시로 검산·근거 검토를 생략하는 패턴.', 2, '💪', 'low', '1:50', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(29, '''익숙유형'' 생각이 들면 문제 번호 옆에 검산 플래그★ 표시 → 풀이 후 ★이 있는 문제는 역대입·조건 체크 2단계 검산 필수 → 주간 회고에서 ★ 문제의 실제 정답률을 통계로 기록(주간 %)', '★ 표시한 두 문제를 역대입으로 검산했는지, 놓친 조건이 있었는지 피드백', '잘하는 유형이라고 생각해도 방심하면 안 됩니다. 익숙한 문제일수록 검산 플래그를 표시하고 더 꼼꼼히 검산하세요. 주간 회고에서 실제 정답률을 확인하면 자신의 실력을 객관적으로 볼 수 있습니다.', '★ 표시한 두 문제를 역대입으로 검산했습니다. 놓친 조건이 있었는지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 30: 메타인지 고갈형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(30, '메타인지 고갈형', '문제 진행 중 ''내가 뭘 모르는지'' 평가 기능이 고갈돼 학습이 무의식적 반복으로 변하는 패턴.', 8, '🎯', 'medium', '2:00', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(30, '20분마다 알람 → ''내가 모르는 부분 1문장 메모'' 루틴 → 메모한 문장을 과녁표 (🎯) 표시 목록에 모으기 → 세션 종료 후 과녁표 항목을 자료 탐색·질문 리스트로 전환', '🎯 리스트에서 3개를 추렸다면, 어떤 순서로 해결하면 좋을지 안내', '공부하다 보면 자신이 무엇을 모르는지 파악하기 어려워집니다. 20분마다 알람을 맞춰놓고 ''내가 모르는 부분''을 한 문장으로 메모하세요. 이를 모아서 나중에 집중적으로 해결하면 효율적입니다.', '🎯 리스트에서 3개를 추렸습니다. 어떤 순서로 해결하면 좋을지 안내 부탁드립니다.', NOW(), NOW());

-- Persona 31: 개념-용어 혼동형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(31, '개념-용어 혼동형', '정의·기호를 모호하게 기억해 비슷한 단어와 혼동, 조건 매칭에 실패하는 패턴.', 7, '🏷️', 'medium', '2:15', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(31, '개념 등장 시 색상 코드 지정: 정의(파란), 정리(초록), 예외(보라) → 유사 용어는 노트 오른쪽에 ''헷갈림 리스트''로 별도 기록 → 학습 종료 전 헷갈림 리스트를 퀴즈 카드로 3분 복습', '헷갈림 리스트의 ''congruent'' vs ''consistent''를 구분 정리했는지, 설명이 맞는지 확인', '비슷한 용어나 기호를 혼동하면 문제를 제대로 풀 수 없습니다. 개념마다 색상 코드를 정해서 구분하고, 헷갈리는 용어는 따로 리스트를 만들어 관리하세요. 학습 끝에 퀴즈로 복습하면 확실히 구분할 수 있습니다.', '헷갈림 리스트의 ''congruent'' vs ''consistent''를 구분 정리했는데, 설명이 맞는지 확인 부탁드립니다.', NOW(), NOW());

-- Persona 32: 역추적 단절형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(32, '역추적 단절형', '답을 먼저 보고 거꾸로 이유를 찾다 논리 사다리가 중간에서 끊기는 패턴.', 4, '⬆️', 'medium', '2:05', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(32, '답 확인 전 역방향 체크리스트(①→②→③)를 빈칸으로 작성 → 체크리스트를 채우며 필요 근거를 파란색, 이미 있는 근거를 검정으로 표시 → 빈칸이 남으면 앞단계를 정·역방향 교차 검토', '역방향 체크리스트에서 빈칸 두 개가 있었다면, 보충 근거가 적절한지 검토', '답부터 거꾸로 추적하면 중간 단계를 놓치기 쉽습니다. 역방향 체크리스트를 만들어 빈칸을 채워가며 논리를 완성하세요. 빈칸이 남으면 정방향과 역방향을 교차해서 검토하면 빠진 부분을 찾을 수 있습니다.', '역방향 체크리스트에서 빈칸 두 개가 있었는데, 보충 근거가 적절한지 검토 부탁드립니다.', NOW(), NOW());

-- Persona 33: 사다리 건너뛰기형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(33, '사다리 건너뛰기형', '중간 논증을 생략하고 결론으로 직행, 근거 빈칸을 스스로 인식하지 못하는 패턴.', 4, '🪜', 'high', '1:55', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(33, '논증 단계에 번호(①②③…)와 화살표를 모두 명시 → 결론에 도달하면 ①부터 화살표를 역방향으로 따라가며 근거 문장 점검 → 빠진 단계가 있으면 빨간펜으로 ''Missing Step!'' 태그', 'Missing Step 태그가 두 군데 나왔다면, 적절한 중간 근거를 추가했는지 확인', '논증의 중간 단계를 건너뛰면 논리가 약해집니다. 각 단계에 번호와 화살표를 표시하고, 결론에서 거꾸로 따라가며 점검하세요. 빠진 단계가 있으면 ''Missing Step!''이라고 표시하고 보충하면 논리가 탄탄해집니다.', 'Missing Step 태그가 두 군데 나왔습니다. 적절한 중간 근거를 추가했는지 확인해 주세요.', NOW(), NOW());

-- Persona 34: 조건 재정렬 미흡형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(34, '조건 재정렬 미흡형', '복합 조건의 순서를 무시해 필수·보조 정보를 혼선시키는 패턴.', 7, '📋', 'medium', '2:10', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(34, '모든 조건 앞에 순번 스티커(①②③) 부착 후 순서 고정 → 풀이 중 해당 조건을 사용하면 순번 옆에 체크✔︎ → 체크되지 않은 조건이 남으면 순서를 재검토해 적용 위치 보완', '③번 조건이 늦게 체크되었다면, 적용 순서가 논리에 맞는지 피드백', '복잡한 조건들의 순서를 제대로 정리하지 않으면 혼란스러워집니다. 모든 조건에 번호를 붙이고 사용할 때마다 체크하세요. 사용하지 않은 조건이 있으면 순서를 다시 검토해서 빠뜨린 부분이 없는지 확인하세요.', '③번 조건이 늦게 체크되었습니다. 적용 순서가 논리에 맞는지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 35: 공식 암기 과신형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(35, '공식 암기 과신형', '문제 특성과 무관하게 외운 공식만 기계적으로 대입, 오적용 위험이 큰 패턴.', 5, '📖', 'medium', '2:20', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(35, '공식을 적을 때 오른쪽에 ''출처·조건''을 1줄 주석 → 공식 사용 전 조건 매칭 질문 3개(예: ''연속? 미분 가능?'') 답 체크 → 매주 사용한 공식·조건 목록을 통계로 정리 → 오용 사례 표시', '이번 주 공식-조건 통계에서 오용 사례가 1건 나왔다면, 올바른 조건 확인 절차가 충분했는지 조언', '공식을 무작정 외워서 적용하면 오류가 생깁니다. 공식을 쓸 때 출처와 조건을 함께 적고, 사용 전에 조건이 맞는지 확인하세요. 매주 사용한 공식을 정리하고 오용 사례를 표시하면 실수를 줄일 수 있습니다.', '이번 주 공식-조건 통계에서 오용 사례가 1건 나왔습니다. 올바른 조건 확인 절차가 충분했는지 조언 부탁드립니다.', NOW(), NOW());

-- Persona 36: 근사치 타협형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(36, '근사치 타협형', '''대략 맞겠지'' 하고 근사 계산으로 풀이를 종료, 오차 검증을 생략하는 패턴.', 7, '≈', 'low', '2:00', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(36, '근사값을 쓸 때마다 옆에 ''±오차 범위''를 바로 기입 → 최종 답 전 오차 ≤ 목표 허용치? 체크박스에 ✔︎ → 오차 초과 시 정확 계산 또는 더 정밀한 근사법(테일러, 분할 적분 등) 재적용', '±오차 0.02까지 확인했다면, 이 허용치가 적절한지 검토', '근사값을 사용할 때는 오차 범위를 항상 확인해야 합니다. 근사값 옆에 오차 범위를 적고, 최종 답에서 허용치 이내인지 체크하세요. 오차가 크면 더 정밀한 방법을 사용해야 합니다.', '±오차 0.02까지 확인했는데, 이 허용치가 적절한지 검토 부탁드립니다.', NOW(), NOW());

-- Persona 37: 개념-문제 불일치 간과형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(37, '개념-문제 불일치 간과형', '문제에서 요구하는 개념과 다른 영역 해법을 고집해 방향이 어긋나는 패턴.', 4, '🎭', 'medium', '2:25', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(37, '문제 읽자마자 상단에 ''필수 개념'' 1줄 제목 작성 → 풀이 중 개념이 바뀌면 제목 옆에 🚨표시 후 이유 메모 → 최종 답 후 제목과 실제 사용 개념이 일치? 불일치? 이중 체크', '필수 개념 제목을 ''벡터 기하''로 잡았는데, 중간에 미적분 개념을 섞었다면 전환 시점이 논리에 맞는지 확인', '문제가 요구하는 개념과 다른 방법을 사용하면 방향이 틀어집니다. 문제를 읽자마자 필수 개념을 적고, 풀이 중에 개념이 바뀌면 표시하세요. 마지막에 처음 계획과 실제 사용한 개념이 일치하는지 확인하면 좋습니다.', '필수 개념 제목을 ''벡터 기하''로 잡았는데, 중간에 미적분 개념을 섞었습니다. 전환 시점이 논리에 맞는지 확인해 주세요.', NOW(), NOW());

-- Persona 38: 단위 무시형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(38, '단위 무시형', '길이·각도·π 변환 등 단위 체크를 생략해 결과가 엇갈리는 패턴.', 3, '📏', 'high', '1:45', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(38, '단위 변환이 필요할 때마다 둥근 박스로 원·목표 단위 표시 → 변환 후 박스 옆에 ''변환 OK'' 스탬프(✔︎) 찍기 → 답안 작성 직전 모든 박스를 훑어 미검증 박스=0 확인', '라디안→도 변환 박스를 놓칠 뻔했다면, 전체 박스 검토가 충분했는지 확인', '단위를 무시하면 답이 완전히 달라집니다. 단위 변환이 필요할 때마다 박스로 표시하고, 변환 후 ''OK'' 스탬프를 찍으세요. 답을 쓰기 전에 모든 박스를 확인하면 단위 실수를 방지할 수 있습니다.', '라디안→도 변환 박스를 놓칠 뻔했는데, 전체 박스 검토가 충분했는지 봐주실 수 있나요?', NOW(), NOW());

-- Persona 39: 시각화 회피형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(39, '시각화 회피형', '그래프·도형 그리기를 귀찮아해 공간적 관계를 착시·오독하는 패턴.', 3, '📊', 'medium', '2:15', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(39, '도형·그래프 문제는 A6 메모지에 60초 제한 스케치를 필수 → 변수 값이 변할 때마다 색펜으로 동적 업데이트 → 풀이 후 스케치와 알지브라식 답을 화살표 연결해 일치 여부 확인', '60초 스케치를 보여드릴 때, 변수 변화가 올바르게 반영됐는지 피드백', '그래프나 도형을 그리지 않고 머릿속으로만 생각하면 착각하기 쉽습니다. 60초 안에 빠르게 스케치하고, 변수가 바뀔 때마다 색펜으로 업데이트하세요. 마지막에 그림과 식을 연결해서 확인하면 실수를 줄일 수 있습니다.', '60초 스케치를 보여드릴게요. 변수 변화가 올바르게 반영됐는지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 40: 메모 불능 기억 과신형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(40, '메모 불능 기억 과신형', '''머릿속에 다 있어''라며 메모 없이 진행, 항목 순서가 뒤섞이는 패턴.', 8, '🧠', 'medium', '1:50', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(40, '조건·중간값을 ''미니 메모칩''(포스트잇)으로 즉시 기록 → 노트에 붙이기 → 풀이 단계 전환 때마다 메모칩을 눈으로 터치 확인 → 풀이 후 메모칩을 순서대로 재정렬하며 논리 흐름 검산', '메모칩을 순서대로 재정렬했다면, 논리 흐름이 자연스러운지 검토', '머릿속으로만 기억하면 순서가 뒤섞입니다. 중요한 조건이나 중간값은 포스트잇에 적어 노트에 붙이세요. 단계마다 확인하고, 마지막에 순서대로 정렬해서 논리 흐름을 검토하면 실수를 방지할 수 있습니다.', '메모칩을 순서대로 재정렬했는데, 논리 흐름이 자연스러운지 검토해 주세요.', NOW(), NOW());

-- Persona 41: 지식-실행 단절형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(41, '지식-실행 단절형', '개념은 이해했지만 문제 적용 단계에서 멈칫해 ''알아도 못 푸는'' 상황이 반복되는 패턴.', 5, '🔗', 'high', '2:05', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(41, '새 개념 학습 직후 예제 1문제를 즉시 해결(3분 제한) → 예제가 막히면 ''개념 → 절차 → 예시'' 흐름을 음성으로 20초 복창 → 복창 후 다시 풀어 보고 성공 여부를 O/X로 기록', '20초 복창 후 예제를 다시 풀어 봤다면, 절차 설명이 명확했는지 피드백', '개념을 이해해도 문제에 적용하지 못하면 소용없습니다. 새 개념을 배우면 즉시 예제를 3분 안에 풀어보세요. 막히면 개념-절차-예시를 소리내어 정리하고 다시 시도하면 연결고리가 생깁니다.', '20초 복창 후 예제를 다시 풀어 봤습니다. 절차 설명이 명확했는지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 42: 노이즈 필터 실패형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(42, '노이즈 필터 실패형', '지문 속 중요치 않은 숫자·문장이 작업기억을 점유해 핵심 정보를 덮어버리는 패턴.', 1, '🔇', 'medium', '2:10', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(42, '문제를 처음 읽을 때 밑줄(핵심) / 연필 흐림선(노이즈) 2단계 표시 → 풀이 중 노이즈 부분은 괄호로 접어두기(접힌 종이 시각 효과) → 최종 검산 시 노이즈가 풀이에 영향을 줬는지 체크표 작성', '노이즈 표시한 문장을 접어뒀다면, 핵심을 올바르게 추려냈는지 확인', '문제에는 중요하지 않은 정보도 많습니다. 핵심은 밑줄, 노이즈는 연하게 표시하고, 풀이할 때는 노이즈를 괄호로 접어두세요. 이렇게 하면 중요한 정보에만 집중할 수 있습니다.', '노이즈 표시한 문장을 접어뒀는데, 제가 핵심을 올바르게 추려냈는지 확인해 주세요.', NOW(), NOW());

-- Persona 43: 인터럽트 리셋 불능형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(43, '인터럽트 리셋 불능형', '알림·대화 등 외부 방해 후 이전 맥락을 복구하지 못해 흐름이 끊기는 패턴.', 8, '🔄', 'medium', '1:55', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(43, '방해를 받기 전 단계를 한 줄로 요약해 상단 포스트잇에 써둔다 → 방해가 끝나면 포스트잇을 소리 내어 읽고 동일 단계에서 재시작 → 포스트잇을 떼어 노트 하단에 붙이며 ''Context Restored'' 체크', '방해 후 포스트잇 요약으로 복귀했다면, 단계 연결이 자연스러운지 확인', '방해를 받으면 이전에 하던 것을 잊기 쉽습니다. 방해받기 전에 현재 단계를 한 줄로 요약해 포스트잇에 적어두세요. 돌아와서 이를 읽고 같은 지점에서 다시 시작하면 흐름을 이어갈 수 있습니다.', '방해 후 포스트잇 요약으로 복귀했는데, 단계 연결이 자연스러운지 봐주실래요?', NOW(), NOW());

-- Persona 44: 감정 보상 과다형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(44, '감정 보상 과다형', '작은 성공에 과도한 도파민 보상이 발생해 주의력이 이완되고 다음 단계가 느슨해지는 패턴.', 8, '🎉', 'low', '2:00', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(44, '성공 시 10초 셀프 칭찬(속삭이기) 후 바로 타이머 재가동 → 다음 단계 착수 전 ''다음 할 일 5단어'' 메모 → 학습 끝에 총 셀프 칭찬 시간을 분 단위로 기록(1분 이내 목표)', '셀프 칭찬 6회, 총 50초였다면, 다음 할 일 메모가 충분했는지 확인', '성공했을 때 너무 들뜨면 다음 단계에서 실수합니다. 10초만 조용히 자신을 칭찬하고 바로 다음 할 일을 5단어로 메모하세요. 전체 칭찬 시간을 1분 이내로 유지하면 집중력을 잃지 않습니다.', '셀프 칭찬 6회, 총 50초였습니다. 다음 할 일 메모가 충분했는지 확인 부탁드립니다.', NOW(), NOW());

-- Persona 45: 휴식 부족 저하형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(45, '휴식 부족 저하형', '장시간 집중 후 인지 피로가 누적돼 오류 검출률이 급락하는 패턴.', 8, '😪', 'high', '2:20', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(45, '90분 세션 → 15분 휴식 ''Pomodoro Plus'' 스케줄 설정 → 휴식 시간엔 스트레칭 + 물 1컵 + 창밖 2분 바라보기 수행 → 휴식 후 첫 문제를 검산 문제로 선택해 집중도 회복 확인', '90 + 15 루틴을 2세트 돌렸다면, 휴식 후 검산 정확도가 나아졌는지 확인', '장시간 집중하면 피로가 쌓여 실수가 늘어납니다. 90분 공부하고 15분 휴식하는 패턴을 유지하세요. 휴식 시간에는 스트레칭, 물 마시기, 창밖 보기를 하고, 돌아와서 검산으로 집중도를 확인하세요.', '90 + 15 루틴을 2세트 돌렸습니다. 휴식 후 검산 정확도가 나아졌는지 확인해 주세요.', NOW(), NOW());

-- Persona 46: 전환 비용 과소평가형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(46, '전환 비용 과소평가형', '여러 문제·풀이법을 빈번히 바꾸며 작업기억을 재로딩, 집중 에너지를 낭비하는 패턴.', 6, '💱', 'medium', '2:15', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(46, '문제 전환 전 현재 풀이를 2줄 요약해 노트 여백에 작성 → 새 문제로 넘어갈 때 요약 옆에 타임스탬프 기록 → 하루 학습 끝에 전환 횟수와 소요 시간을 막대그래프로 시각화', '오늘 문제 전환 5회, 총 8분 소요였다면, 전환 요약이 충분했는지 피드백', '문제를 자주 바꾸면 매번 머리를 재부팅해야 해서 에너지가 낭비됩니다. 전환하기 전에 현재 풀이를 2줄로 요약하고 시간을 기록하세요. 하루 끝에 전환 패턴을 분석하면 효율성을 높일 수 있습니다.', '오늘 문제 전환 5회, 총 8분 소요였습니다. 전환 요약이 충분했는지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 47: 반례 무시형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(47, '반례 무시형', '풀이가 순조로우면 ''예외 없겠지''라며 반례 검증을 생략하는 패턴.', 7, '❌', 'high', '2:05', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(47, '풀이 과정 중 ''반례 가능성 칸''을 만들고 최소 1개 쓰기 → 최종 답 전 반례를 실제로 수치·그림으로 확인 → 반례가 존재하면 풀이를 분기해 조건 보강 또는 전략 수정', '반례 칸에 적은 예를 테스트했는데 조건을 추가해야 했다면, 수정이 타당한지 검토', '문제가 잘 풀린다고 해서 반례를 무시하면 안 됩니다. 풀이 중에 반례 가능성을 최소 1개는 생각해보고, 마지막에 실제로 확인하세요. 반례가 있으면 조건을 보강하거나 전략을 수정해야 합니다.', '반례 칸에 적은 예를 테스트했는데 조건을 추가해야 했습니다. 수정이 타당한지 검토해 주세요.', NOW(), NOW());

-- Persona 48: 관성적 읽기 스킵형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(48, '관성적 읽기 스킵형', '익숙해 보이는 문제라 생각해 지문의 끝을 읽지 않고 풀이를 시작하는 패턴.', 3, '⏭️', 'medium', '1:50', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(48, '문장 끝마다 ''／'' 표시해 끝까지 시각적으로 확인 → 표시 후 마지막 문장을 큰 소리로 1번 읽고 착수 → 풀이 중 조건 충돌이 생기면 스킵 여부를 체크표로 기록', '''／'' 표시를 모두 달았는데 마지막 문장이 중요 조건이더라면, 해당 조건 반영이 잘 됐는지 확인', '익숙한 문제라고 끝까지 읽지 않으면 중요한 조건을 놓칩니다. 문장 끝마다 슬래시를 표시하고, 마지막 문장을 소리내어 읽은 후 시작하세요. 이렇게 하면 숨어있는 조건을 놓치지 않습니다.', '''／'' 표시를 모두 달았는데 마지막 문장이 중요 조건이더군요. 해당 조건 반영이 잘 됐는지 확인 부탁드립니다.', NOW(), NOW());

-- Persona 49: 조건 재해석 과잉형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(49, '조건 재해석 과잉형', '애매한 문구를 자의적으로 해석해 핵심 의미를 빗나가는 패턴.', 7, '🔮', 'medium', '2:10', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(49, '애매 문구는 즉시 질문 카드 작성 → 교사·AI 튜터에게 전송 → 답변을 받을 때까지 임시 해석에 ''?'' 마크 붙여 진행 → 확정 해석 후 ''?'' 마크 부분을 빨간펜 정정', '질문 카드로 받은 답변을 반영해 ''?'' 마크를 정정했다면, 해석이 맞는지 최종 확인', '애매한 문구를 자기 마음대로 해석하면 문제를 잘못 이해합니다. 불분명한 부분은 질문 카드에 적어 선생님께 물어보고, 그동안은 물음표를 붙여 진행하세요. 확실한 답변을 받으면 수정하면 됩니다.', '질문 카드로 받은 답변을 반영해 ''?'' 마크를 정정했습니다. 해석이 맞는지 최종 확인 부탁드립니다.', NOW(), NOW());

-- Persona 50: 단계 통합 과속형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(50, '단계 통합 과속형', '두세 단계를 한 줄로 압축해 적으면서 오류 추적이 불가능해지는 패턴.', 3, '🏃‍♂️', 'medium', '1:55', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(50, '2단계 이상은 반드시 화살표 대신 연속 번호(①②)로 구분 → 통합 줄 작성 후 각 번호 옆에 중간 결과를 따로 산출 → 검산 시 중간 결과와 최종 결과 간 일관성을 확인', '①②로 나눈 중간 결과가 최종 결과와 연결됐는지 검산했다면, 추가 개선점이 있을지 조언', '여러 단계를 한 줄로 압축하면 오류를 찾기 어렵습니다. 2단계 이상은 번호로 구분하고 각각의 중간 결과를 따로 적으세요. 검산할 때 중간 결과들이 최종 답과 일치하는지 확인하면 실수를 찾을 수 있습니다.', '①②로 나눈 중간 결과가 최종 결과와 연결됐는지 검산했습니다. 추가 개선점이 있을까요?', NOW(), NOW());

-- Persona 51: 중간점검 생략형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(51, '중간점검 생략형', '풀이가 절반쯤 진행됐을 때 검산 없이 끝까지 돌진, 오류를 초기에 놓치는 패턴.', 7, '⏸️', 'high', '2:00', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(51, '문제 착수와 동시에 자동 알람을 풀이 예상시간의 50% 지점에 설정 → 알람이 울리면 즉시 진행 중인 식에 역대입 검증(또는 그래프 확인) 수행 → 검산 결과를 O／Δ／X 기호로 표시 후 계속 진행', '50% 알람에서 Δ 표시가 나왔다면, 수정 방식이 적절했는지 확인', '중간에 한 번 멈춰서 확인하면 큰 실수를 방지할 수 있습니다. 예상 시간의 절반 지점에 알람을 맞추고, 울리면 지금까지 푼 것을 역대입으로 검증하세요. 문제가 있으면 즉시 수정할 수 있습니다.', '50% 알람에서 Δ 표시가 나왔는데, 수정 방식이 적절했는지 확인 부탁드립니다.', NOW(), NOW());

-- Persona 52: 검산 회피형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(52, '검산 회피형', '시간 아까워 검산을 건너뛰어 정답률이 흔들리는 패턴.', 7, '🚫', 'high', '1:45', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(52, '최종 답 기입 직후 검산 메뉴 3개(역대입·단위·추가 조건) 중 1개를 무조건 실행 → 검산 완료 시 문제 번호 옆에 ✔︎ 스탬프 찍기 → 주간 회고 때 검산 스탬프 개수와 실제 정답률을 분석 그래프로 비교', '검산 스탬프가 10개 중 9개라면, 스킵한 1문제가 괜찮았는지 검토', '검산을 아까워하면 오히려 시간을 더 낭비하게 됩니다. 답을 쓴 직후 역대입, 단위 확인, 조건 체크 중 하나는 반드시 하세요. 검산한 문제에 스탬프를 찍고 정답률과 비교하면 효과를 확인할 수 있습니다.', '검산 스탬프가 10개 중 9개입니다. 스킵한 1문제가 괜찮았는지 검토 부탁드립니다.', NOW(), NOW());

-- Persona 53: 계산 체계 혼합형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(53, '계산 체계 혼합형', '분수↔소수, 라디안↔도 등 단위를 혼용하다 값이 뒤섞이는 패턴.', 3, '🔀', 'medium', '2:10', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(53, '변환이 일어날 때마다 변환표(예: π↔°, 1/3↔0.333…)를 노트 옆에 작성 → 최종 계산 단계에서 ''최종 단위 일관?'' 체크박스를 ✔︎ → 혼합 오류가 나오면 변환표를 색펜으로 강조 재정리', '변환표를 만들었다면, 최종 일관성 체크가 충분했는지 확인', '다른 단위 체계를 섞어 쓰면 계산이 꼬입니다. 변환이 필요할 때마다 변환표를 만들어 옆에 두고, 마지막에 단위가 일관되는지 체크하세요. 오류가 있으면 변환표를 색펜으로 다시 정리하면 됩니다.', '변환표를 만들었는데, 최종 일관성 체크가 충분했는지 확인해 주세요.', NOW(), NOW());

-- Persona 54: 음운 혼동형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(54, '음운 혼동형', '''sine''↔''sign'', ''root''↔''route'' 등 비슷한 발음을 착각해 기호·용어를 바꾸는 패턴.', 3, '🗣️', 'low', '1:50', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(54, '유사 음 용어를 색깔로 구분(예: 수학 기호=파랑, 일반 단어=검정) → 필기 시 발음을 속삭이며 기호를 다시 한번 확인 → 학습 후 유사 음 용어 목록을 퀴즈 카드로 2분 복습', '색깔 구분과 속삭이기 전략 적용 후 오기가 줄었는지 확인', '발음이 비슷한 용어를 혼동하면 완전히 다른 의미가 됩니다. 수학 기호는 파란색, 일반 단어는 검은색으로 구분하고, 쓸 때 발음을 속삭여 확인하세요. 비슷한 용어들을 모아 퀴즈로 복습하면 확실히 구분됩니다.', '색깔 구분과 속삭이기 전략 적용 후 오기가 줄었는지 봐주실 수 있나요?', NOW(), NOW());

-- Persona 55: 참조 프레임 불일치형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(55, '참조 프레임 불일치형', '좌표 원점·축 방향 전환을 놓쳐 그래프·변수를 잘못 배치하는 패턴.', 3, '🧭', 'medium', '2:15', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(55, '좌표 변환이 나오면 작은 스케치로 새 원점·축을 즉시 표시 → 변수·길이를 옮길 때마다 스케치 상에 마커 펜으로 업데이트 → 풀이 완료 후 스케치와 대수식 관계를 검산 화살표로 연결', '새 원점·축을 그린 스케치를 보여드리겠다면, 변수 위치가 정확한지 피드백', '좌표계가 바뀔 때 원점과 축 방향을 놓치면 전체가 틀어집니다. 변환이 나오면 즉시 새로운 좌표계를 스케치하고, 변수를 옮길 때마다 업데이트하세요. 마지막에 그림과 식을 연결해서 확인하면 실수를 방지할 수 있습니다.', '새 원점·축을 그린 스케치를 보여드리겠습니다. 변수 위치가 정확한지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 56: 전략 중복 추적 피로형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(56, '전략 중복 추적 피로형', '동시에 3가지 이상 풀이를 전개하다 작업기억이 분산-탈진하는 패턴.', 1, '🤹', 'medium', '2:05', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(56, '동시에 2개 풀이만 허용, 3번째 아이디어는 대기 메모 칸에 보류 → 두 풀이 중 하나가 막히면 대기 칸에서 1개만 꺼내 진행 → 세션 종료 후 사용 안 한 아이디어를 ''보류 로그''로 분류‧검토', '오늘 두 개 풀이만 병행했고, 보류 로그에 2개를 남겼다면, 전략 전환 시점이 적절했는지 조언', '여러 풀이를 동시에 시도하면 머리가 복잡해집니다. 최대 2개만 동시에 진행하고, 추가 아이디어는 메모해두세요. 막히면 보류한 아이디어를 하나씩 꺼내 쓰면 체계적으로 문제를 해결할 수 있습니다.', '오늘 두 개 풀이만 병행했고, 보류 로그에 2개를 남겼습니다. 전략 전환 시점이 적절했는지 조언 부탁드립니다.', NOW(), NOW());

-- Persona 57: 목표-행동 단절형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(57, '목표-행동 단절형', '''개념 학습''이 ''풀이 수집''으로 변질돼 원래 목표를 잊는 패턴.', 5, '🎯', 'high', '2:20', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(57, '학습 시작 전 ''오늘 목표 1문장''을 화면 상단에 고정 → 30분마다 목표 문장을 소리 내어 읽고 현재 행동과 매칭 여부 체크 → 세션 끝에 목표 달성도를 0~100%로 자평·기록', '오늘 목표 달성도를 85%로 평가했다면, 행동이 목표와 얼마나 일치했는지 확인', '공부하다 보면 원래 목표를 잊고 엉뚱한 일을 하게 됩니다. 시작 전에 목표를 한 문장으로 적어 눈에 띄게 두고, 30분마다 읽으며 지금 하는 일이 목표와 맞는지 확인하세요. 끝날 때 달성도를 평가하면 효율성이 높아집니다.', '오늘 목표 달성도를 85%로 평가했습니다. 제 행동이 목표와 얼마나 일치했는지 확인 부탁드립니다.', NOW(), NOW());

-- Persona 58: 피드백 과민형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(58, '피드백 과민형', '작은 지적에도 불안이 급등해 작업기억 용량이 급락하는 패턴.', 8, '😣', 'medium', '1:55', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(58, '부정적 피드백을 받으면 30초 눈 감고 복식호흡 → 노트에 ''교정 = 성장'' 문장을 써서 시야에 두기 → 피드백을 ''사실'' ''해석'' ''다음 행동'' 3열 표로 분리 기록', '''사실-해석-다음 행동'' 표를 작성했다면, 해석이 과민하지 않았는지 피드백', '피드백을 받으면 감정적으로 반응하기 쉽습니다. 30초간 눈을 감고 숨을 고른 후, ''교정은 성장''이라고 적어두세요. 피드백을 사실, 해석, 다음 행동으로 나누어 정리하면 객관적으로 받아들일 수 있습니다.', '''사실-해석-다음 행동'' 표를 작성했습니다. 해석이 과민하지 않았는지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 59: 다중 문제 스위칭 과부하형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(59, '다중 문제 스위칭 과부하형', '시험 직전에 여러 문제를 빠르게 훑다 인지 세트업이 실패하는 패턴.', 6, '📚', 'high', '2:10', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(59, '시험 전날 최대 3세트(L·M·H 각 1세트)만 선정 → 각 세트 완료 후 5분 정리 노트로 핵심만 요약 → 요약 노트를 아침 리콜(5분)로 다시 읽고 시험장 입장', '3세트 요약 노트를 만들었다면, 핵심 추출이 충분한지 검토', '시험 직전에 너무 많은 문제를 보면 오히려 혼란스러워집니다. 쉬운, 중간, 어려운 문제 각 1세트만 선택하고, 각각 5분씩 핵심을 정리하세요. 시험 당일 아침에 이 요약을 5분간 다시 읽으면 준비가 완료됩니다.', '3세트 요약 노트를 만들었습니다. 핵심 추출이 충분한지 검토해 주세요.', NOW(), NOW());

-- Persona 60: 자기평가 누적 오류형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(60, '자기평가 누적 오류형', '진행 중 정확도 추정이 계속 어긋나 자기효능감이 왜곡되는 패턴.', 8, '📊', 'medium', '2:00', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(60, '각 문제 해결 후 난이도·정확도 5점 척도 자체 채점 → 세션 끝에 실제 채점 결과와 산포도 그래프로 비교 → 편향(과·과소 평가)을 발견하면 다음 세션 보정 목표 설정', '자기평가 vs 실제 점수 산포도를 그렸다면, 편향 보정 계획이 적절한지 피드백', '자신의 실력을 정확히 평가하지 못하면 학습 방향이 틀어집니다. 문제를 풀 때마다 난이도와 정확도를 5점 척도로 예측하고, 나중에 실제 결과와 비교하세요. 편향을 발견하면 다음번에 보정할 목표를 세우면 됩니다.', '자기평가 vs 실제 점수 산포도를 그렸습니다. 편향 보정 계획이 적절한지 피드백 부탁드립니다.', NOW(), NOW());
