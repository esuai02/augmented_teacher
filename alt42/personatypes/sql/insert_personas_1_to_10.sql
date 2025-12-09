-- Insert personas 1-10 from 60personas.txt into the database
-- This script inserts the data into mdl_alt42i_math_patterns and mdl_alt42i_pattern_solutions tables

-- Make sure category data is already inserted (from create_math_persona_tables.sql)

-- Insert patterns for personas 1-10

-- Persona 1: 아이디어 해방 자동발화형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(1, '아이디어 해방 자동발화형', '번쩍이는 아이디어가 떠오르면 검증 없이 바로 써 내려가 결국 오답을 양산하는 패턴.', 1, '🧠', 'high', '2:15', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(1, '아이디어가 떠오르면 5초 멈춤 → 아이디어를 한 줄로 요약 후, ''약점 가설'' 1개를 곧바로 적는다 → 문제 지문을 다시 읽고, 가설과 비교한다', '5초 멈춤→가설 쓰기 루틴을 세 번 성공했는지 확인. 요약이 적절했는지 짧게 피드백', '번쩍이는 아이디어가 떠오르면 즉시 적지 말고 5초간 멈춰서 생각해보세요. 핵심 논거를 한 줄로 정리하고, 혹시 틀릴 수 있는 부분은 없는지 오류 가설을 하나 세워보세요. 이렇게 하면 충동적인 실수를 크게 줄일 수 있습니다.', '선생님, 오늘 ''5초 멈춤→가설 쓰기'' 루틴을 세 번 성공했어요. 제 요약이 적절했는지 짧게 피드백 부탁드립니다!', NOW(), NOW());

-- Persona 2: 3초 패배 예감형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(2, '3초 패배 예감형', '''못 풀 것 같다''는 느낌이 3초 만에 뇌를 잠그고, 관련 개념 연결이 끊어지는 패턴.', 2, '😰', 'high', '1:45', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(2, '''포기 신호''를 감지하면 3분 타이머를 켜고 문제 해석을 처음부터 다시 적는다 → 막힌 부분을 눈으로 3분간 응시하며 조건·단어를 재색인한다', '''3분 재해석'' 루틴을 두 번 사용했는지, 다시 읽은 메모에서 놓친 단어가 있었는지 검토', '포기하고 싶은 마음이 들면 바로 타이머를 3분으로 설정하세요. 그리고 문제를 다시 읽으면서 다른 관점에서 해석해보세요. 3분이 지나면 놀랍게도 새로운 아이디어가 떠오를 것입니다.', '저는 오늘 ''3분 재해석'' 루틴을 두 번 썼습니다. 다시 읽은 메모에서 놓친 단어가 있었는지 검토해 주실 수 있나요?', NOW(), NOW());

-- Persona 3: 과신-시야 협착형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(3, '과신-시야 협착형', '과한 자신감으로 숫자·기호의 미세한 차이를 인식하지 못하는 패턴.', 2, '🎯', 'medium', '2:30', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(3, '풀이 착수 전 심호흡 10회 → 비슷한 기호·수치를 색펜으로 구분 표시 → 계산 단계마다 ''작은 차이 체크'' 칸에 ✔︎', '색펜 표시한 부분을 같이 보며, 놓친 차이가 있었는지 확인', '자신감이 넘칠 때일수록 실수하기 쉽습니다. 풀이를 시작하기 전에 심호흡을 10회 하고, 비슷해 보이는 기호나 숫자를 색펜으로 구분해서 표시하세요. 작은 차이가 큰 오류로 이어질 수 있으니 계산할 때마다 체크하는 습관을 들이세요.', '색펜 표시한 부분을 같이 보며, 제가 놓친 차이가 있었는지 알려주시면 감사하겠습니다.', NOW(), NOW());

-- Persona 4: 무의식 연쇄 실수형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(4, '무의식 연쇄 실수형', '손이 먼저 움직여 사소한 계산 실수가 꼬리를 무는 패턴.', 3, '⚡', 'high', '1:55', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(4, '숫자 한 줄 쓸 때마다 펜을 내려놓고 1초 휴식 → 매일 풀이 후 ''실수 장면'' 1개 기록 → 다음 날 첫 학습 전에 그 기록을 재확인', '어제 적은 실수 장면을 보여드릴 때, 비슷한 실수를 막는 팁 제공', '계산 실수는 연쇄적으로 일어나기 쉽습니다. 숫자를 한 줄 쓸 때마다 펜을 잠시 내려놓고 1초의 여유를 가지세요. 매일 실수한 부분을 기록하고 다음날 확인하면 같은 실수를 반복하지 않게 됩니다.', '어제 적은 실수 장면을 보여드릴게요. 비슷한 실수를 막는 팁이 더 있을까요?', NOW(), NOW());

-- Persona 5: 모순 확신-답불가형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(5, '모순 확신-답불가형', '''틀린 곳이 없다''는 집착으로 시야가 좁아져 교정을 못 하는 패턴.', 2, '🔒', 'medium', '2:10', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(5, '답이 안 나올 때 ''간단 실수 90%'' 문장을 써서 관점을 전환 → 풀이를 거꾸로 읽으며 ''사소한 실수 찾기'' 게임화 → 한 번은 다른 색 펜으로 다시 써보기', '''간단 실수 게임''으로 찾은 오류를 검산, 또 다른 시야 전환 방법 제안', '답이 나오지 않을 때는 대부분 간단한 실수가 원인입니다. ''틀린 곳이 없다''는 확신을 버리고 ''간단 실수 90%''라고 적어보세요. 풀이를 거꾸로 읽으며 실수 찾기를 게임처럼 해보면 의외로 쉽게 오류를 발견할 수 있습니다.', '제가 ''간단 실수 게임''으로 찾은 오류를 검산해 주실 수 있나요? 또 다른 시야 전환 방법이 있다면 알려주세요.', NOW(), NOW());

-- Persona 6: 작업기억 ⅔ 할당형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(6, '작업기억 ⅔ 할당형', '다음 일정·잡생각이 머릿속을 스치며 2/3만 집중하는 패턴.', 1, '🧩', 'high', '2:25', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(6, '떠오른 일정은 포스트잇에 적고 덮어두기 → 25분 집중 / 5분 휴식 Pomodoro 타이머 사용 → 휴식 때만 메모 확인·업데이트', '25분 집중 세션 3번 돌렸는지, 중간에 잡생각 메모를 몇 번 했는지 확인', '머릿속에 다른 일정이나 잡생각이 떠오르면 작업기억의 용량이 줄어듭니다. 떠오른 생각은 포스트잇에 적고 잠시 덮어두세요. 25분 집중하고 5분 쉬는 뽀모도로 타이머를 사용하면 집중력을 유지할 수 있습니다.', '25분 집중 세션 3번 돌렸는데, 중간에 잡생각 메모를 몇 번 했는지 확인해 주실 수 있나요?', NOW(), NOW());

-- Persona 7: 반(半)포기 창의 탐색형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(7, '반(半)포기 창의 탐색형', '''어차피 틀릴 것''이라며 낮은 확률의 창의 풀이만 헤매는 패턴.', 4, '🎨', 'medium', '2:40', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(7, '정석 접근 A안을 먼저 10분 시도 → 실패 시 A안 문제점 1줄 정리 → B안 스케치 → B안도 막히면 과감히 답안·해설 구조 분석', 'A안 10분, B안 5분 전략으로 풀어봤는지, A안 분석이 적절했는지 확인', '포기하고 싶은 마음에 무작정 창의적인 방법만 시도하지 마세요. 먼저 정석적인 접근을 10분간 시도해보고, 안 되면 문제점을 정리한 후 다른 방법을 시도하세요. 균형 잡힌 접근이 중요합니다.', '오늘 A안 10분, B안 5분 전략으로 풀어봤어요. 제 A안 분석이 적절했는지 봐주실래요?', NOW(), NOW());

-- Persona 8: 해설지-혼합 착각형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(8, '해설지-혼합 착각형', '내 생각과 해설 내용을 섞어 쓰다 근거가 뒤섞이는 패턴.', 5, '📖', 'medium', '2:05', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(8, '내 풀이=파란색, 해설=빨간색 두 색깔 분리 기록 → 해설을 읽을 때 ''왜 다른가?'' 차이 2개 메모 → 하루 뒤, 파란·빨간 노트를 다시 읽어 통합 정리', '파란·빨간 차이 두 가지를 설명드릴 때, 해설 흡수 과정 피드백', '자신의 풀이와 해설을 구분해서 기록하는 것이 중요합니다. 파란색으로 내 풀이를, 빨간색으로 해설을 적고, 차이점을 명확히 파악하세요. 하루 뒤에 다시 읽으며 통합하면 개념이 더 잘 정리됩니다.', '파란·빨간 차이 두 가지를 설명드릴게요. 제 해설 흡수 과정이 괜찮은지 피드백 부탁드립니다.', NOW(), NOW());

-- Persona 9: 연습 회피 관성형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(9, '연습 회피 관성형', '''이해했어'' 착각으로 반복 연습을 건너뛰고 넘어가는 패턴.', 5, '🏃', 'high', '1:35', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(9, '새 개념 배우면 즉시 난이도 Low·Mid·High 1문제씩 풀기 → Low / Mid 틀리면 해당 개념 ''불완전''로 표시 후 재학습 → 주간 체크리스트: 개념당 최소 3회 재방문', 'Low·Mid·High 3문제 중 어떤 것을 틀렸는지, 어떤 부분을 더 연습해야 할지 조언', '이해했다고 생각해도 실제로 문제를 풀어보면 다릅니다. 새로운 개념을 배우면 즉시 쉬운, 중간, 어려운 문제를 하나씩 풀어보세요. 틀리면 그 개념을 다시 학습하고 최소 3번은 재방문하세요.', 'Low·Mid·High 3문제 중 Mid를 틀렸어요. 어떤 부분을 더 연습해야 할까요?', NOW(), NOW());

-- Persona 10: 불확실 강행형
INSERT INTO mdl_alt42i_math_patterns (id, name, description, category_id, icon, priority, audio_time, created_at, updated_at) VALUES
(10, '불확실 강행형', '근거 부족인데도 ''일단 적용''해서 오류가 연쇄되는 패턴.', 4, '🎲', 'medium', '2:20', NOW(), NOW());

INSERT INTO mdl_alt42i_pattern_solutions (pattern_id, action, check_method, audio_script, teacher_dialog, created_at, updated_at) VALUES
(10, '근거 약하면 노란 포스트잇에 ''확신 ★☆☆'' 등급 표시 → 별 1‧2개인 줄은 풀이 끝에 재검산 표시(✔︎) → 검산 단계에서 ★ 1‧2 지점 우선 점검', '노란 포스트잇으로 ★ 표시한 부분을 같이 검산, 다른 ''확신 체크'' 방법 제안', '근거가 부족한데 억지로 진행하면 오류가 계속 이어집니다. 확신이 없으면 노란 포스트잇에 별점으로 표시하고, 나중에 그 부분을 우선적으로 검산하세요. 확실한 근거를 가지고 진행하는 것이 중요합니다.', '노란 포스트잇으로 ★ 표시한 부분을 같이 검산해 주시면 좋겠습니다. 다른 ''확신 체크'' 방법이 있을까요?', NOW(), NOW());
