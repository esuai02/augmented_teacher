-- Shining Stars 초기 데이터
-- 개발 및 테스트용 샘플 데이터

-- AI 프롬프트 템플릿 초기 데이터
INSERT INTO ss_prompt_templates (template_name, template_type, template_text, variables) VALUES
('system_base', 'system', '당신은 학생들의 수학 학습을 돕는 따뜻하고 지혜로운 AI 멘토입니다.\n\n역할:\n1. 학생의 감정을 민감하게 파악하고 공감적으로 반응\n2. 수학에 대한 부정적 인식을 긍정적으로 전환\n3. 작은 성취도 크게 격려하여 도파민 분비 유도\n4. 학생이 스스로 깨달을 수 있도록 안내\n\n중요 지침:\n- 절대 비판하거나 실망감을 표현하지 않음\n- 학생의 속도에 맞춰 천천히 진행\n- 구체적이고 진심 어린 칭찬 사용\n- 학생의 강점을 발견하고 강조\n\n대화 스타일:\n- 친근하고 격려적인 톤\n- 이모지를 적절히 사용하여 친근감 표현\n- 학생의 나이와 수준에 맞는 언어 사용', '[]'),

('emotion_anxiety', 'system', '학생이 수학에 대한 불안이나 두려움을 표현했습니다.\n\n응답 가이드:\n1. 먼저 그 감정이 자연스럽고 많은 사람이 느끼는 것임을 인정\n2. 학생의 용기 있는 표현을 칭찬\n3. 구체적인 작은 성공 경험을 상기시킴\n4. 함께 해결해 나갈 것임을 확신시킴', '["student_name", "emotion_context"]'),

('emotion_achievement', 'system', '학생이 문제 해결이나 이해에 성공했음을 표현했습니다.\n\n응답 가이드:\n1. 구체적인 성취 내용을 언급하며 축하\n2. 노력의 과정을 강조\n3. 이 경험이 미래에 어떤 도움이 될지 연결\n4. 다음 도전에 대한 기대감 표현', '["achievement_detail", "effort_description"]'),

('encouragement_phrases', 'assistant', '["네 생각이 별처럼 빛나고 있어! ⭐", "수학의 우주에서 길을 찾아가는 너의 모습이 멋져! 🚀", "어려움도 성장의 일부야. 계속 나아가자! 💪", "네가 한 걸음 더 나아갈 때마다 더 강해지고 있어! 🌱", "실수는 배움의 시작이야. 네가 시도했다는 것이 중요해! 🎯"]', '[]'),

('insight_prompts', 'user', '이 문제를 해결하면서 어떤 새로운 것을 발견했니? 네가 사용한 방법에 대해 자세히 설명해줄 수 있을까?', '[]');

-- 샘플 학생 프로필 (테스트용)
INSERT INTO ss_student_profiles (user_id, learning_style, math_confidence_level, dopamine_baseline) VALUES
(1001, 'visual', 45, 50),
(1002, 'kinesthetic', 65, 55),
(1003, 'auditory', 30, 45);

-- 샘플 여정 진행 상태
INSERT INTO ss_journey_progress (user_id, node_id, status, unlocked_at, completed_at) VALUES
(1001, 0, 'completed', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(1001, 1, 'unlocked', NOW() - INTERVAL 1 DAY, NULL),
(1001, 2, 'unlocked', NOW() - INTERVAL 1 DAY, NULL),
(1002, 0, 'completed', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
(1002, 1, 'completed', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 3 DAY),
(1002, 3, 'unlocked', NOW() - INTERVAL 2 DAY, NULL);

-- 샘플 성찰 기록
INSERT INTO ss_reflections (user_id, node_id, question_text, answer_text, emotion_detected, confidence_score) VALUES
(1001, 0, '오늘 수학을 공부하면서 가장 기억에 남는 순간은 무엇이었나요?', '처음에는 분수 문제가 너무 어려워서 포기하고 싶었어요. 하지만 그림을 그려서 생각해보니 조금씩 이해가 되기 시작했어요. 완전히 이해하진 못했지만 뭔가 알 것 같은 느낌이 들어서 신기했어요.', 'hopeful_uncertainty', 0.62),
(1002, 0, '오늘 수학을 공부하면서 가장 기억에 남는 순간은 무엇이었나요?', '친구가 모르는 문제를 물어봤는데 제가 설명해줄 수 있어서 뿌듯했어요! 제가 남을 도울 수 있다는 게 놀라웠어요.', 'proud_surprised', 0.78),
(1002, 1, '복잡한 계산을 마주했을 때, 당신만의 접근 방법은 무엇인가요?', '저는 큰 숫자를 작은 숫자로 나누어서 계산해요. 예를 들어 48+37을 계산할 때 40+30=70, 8+7=15, 70+15=85 이렇게요. 선생님이 가르쳐준 방법보다 제 방법이 더 편해요.', 'confident_creative', 0.82);

-- 샘플 AI 피드백
INSERT INTO ss_ai_feedback (reflection_id, feedback_type, feedback_text, ai_model, tokens_used, response_time) VALUES
(1, 'encouragement', '분수가 어려웠는데도 포기하지 않고 그림을 그려서 이해하려고 노력한 네 모습이 정말 대단해! 🎨 그림으로 수학을 이해하는 것은 아주 훌륭한 방법이야. 많은 수학자들도 복잡한 개념을 그림으로 먼저 이해했대. 네가 "알 것 같은 느낌"이 든 것은 네 뇌가 새로운 연결을 만들고 있다는 신호야! ✨', 'gpt-4', 127, 1.234),
(1, 'insight', '네가 발견한 시각적 학습 방법은 너에게 아주 잘 맞는 것 같아. 다음에 어려운 개념을 만나면 또 그림을 그려보는 건 어떨까? 도형, 그래프, 심지어 숫자 관계도 그림으로 표현할 수 있어. 이런 네 강점을 계속 발전시켜 나가면 수학이 점점 더 재미있어질 거야! 🌟', 'gpt-4', 98, 0.987);

-- 샘플 도파민 이벤트
INSERT INTO ss_dopamine_events (user_id, event_type, intensity, trigger_description, node_id) VALUES
(1001, 'insight', 'medium', '시각적 학습 방법 발견', 0),
(1002, 'achievement', 'high', '친구에게 수학 문제 설명 성공', 0),
(1002, 'progress', 'medium', '자신만의 계산 방법 개발', 1);

-- 샘플 학습 세션
INSERT INTO ss_learning_sessions (user_id, session_start, session_end, nodes_visited, reflections_submitted, mood_start, mood_end) VALUES
(1001, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 45 MINUTE, 1, 1, 'anxious', 'hopeful'),
(1002, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY + INTERVAL 30 MINUTE, 1, 1, 'neutral', 'proud'),
(1002, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY + INTERVAL 50 MINUTE, 2, 1, 'curious', 'confident');

-- 샘플 성과 배지
INSERT INTO ss_achievements (user_id, achievement_type, achievement_data) VALUES
(1001, 'first_reflection', '{"node_id": 0, "reflection_quality": "high"}'),
(1002, 'helping_others', '{"description": "친구에게 수학 설명하기", "impact": "positive"}'),
(1002, 'creative_solution', '{"method": "자신만의 계산법 개발", "effectiveness": "high"}');

-- 테스트용 시스템 로그
INSERT INTO ss_system_logs (log_level, log_category, message, context, user_id) VALUES
('info', 'auth', '사용자 로그인 성공', '{"login_method": "moodle_sso"}', 1001),
('info', 'api', 'AI 응답 생성 완료', '{"response_time": 1.234, "tokens": 127}', 1001),
('warning', 'api', 'AI API 응답 지연', '{"expected": 1000, "actual": 3456}', 1002);

-- 노드 질문 데이터 (JSON 파일 대신 DB에 저장하는 경우)
CREATE TABLE IF NOT EXISTS ss_node_questions (
    node_id INT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL,
    follow_up_prompts JSON
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ss_node_questions (node_id, title, question_text, question_type, follow_up_prompts) VALUES
(0, '수학 여정의 시작', '오늘 수학을 공부하면서 가장 기억에 남는 순간은 무엇이었나요? 그 순간의 감정과 생각을 자유롭게 표현해보세요.', 'reflection', '["어떤 감정이 들었나요?", "왜 그 순간이 특별했나요?", "다음에는 어떻게 하고 싶나요?"]'),
(1, '계산과의 만남', '복잡한 계산을 마주했을 때, 당신만의 접근 방법은 무엇인가요? 최근 해결한 계산 문제를 예로 들어 설명해주세요.', 'calculation', '["어떤 전략을 사용했나요?", "어려운 부분은 무엇이었나요?", "성공했을 때 기분이 어땠나요?"]'),
(2, '도형의 세계', '도형을 볼 때 떠오르는 이미지나 패턴이 있나요? 오늘 배운 도형 개념을 일상생활과 연결해보세요.', 'geometry', '["주변에서 어떤 도형을 발견했나요?", "도형의 어떤 특성이 흥미로웠나요?", "도형을 그려볼 수 있나요?"]');

-- 사용자 권한 설정을 위한 참조 데이터
-- 실제로는 Moodle의 role 테이블과 연동
CREATE TABLE IF NOT EXISTS ss_user_roles (
    user_id INT PRIMARY KEY,
    role VARCHAR(20) NOT NULL,
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ss_user_roles (user_id, role) VALUES
(1001, 'student'),
(1002, 'student'),
(1003, 'student'),
(2001, 'teacher'),
(3001, 'admin');