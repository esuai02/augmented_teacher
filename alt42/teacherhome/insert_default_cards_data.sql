-- KTM 코파일럿 기본 카드를 플러그인 DB에 삽입
-- 작성일: 2025-01-16
-- 설명: 모든 기본 카드를 외부링크 열기 플러그인으로 DB에 저장

-- 변수 설정 (실제 사용자 ID로 변경 필요)
SET @user_id = 1;
SET @current_time = UNIX_TIMESTAMP();

-- 1. 분기활동 (quarterly) 카드들
-- 계획관리 탭
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
(@user_id, 'quarterly', '분기목표 설정 도우미', 0, 'external_link', 
'{"url": "#", "target": "_self", "description": "학습자의 현재 수준과 목표를 분석하여 분기별 달성 가능한 목표를 설정합니다.", "details": ["현재 수준 진단", "목표 설정 가이드", "달성 계획 수립", "진도 체크 시스템"]}', 
0, @current_time, @current_time),

(@user_id, 'quarterly', '분기목표 요청', 1, 'external_link',
'{"url": "#", "target": "_self", "description": "학습자가 직접 분기별 목표를 요청하고 승인받을 수 있는 시스템입니다.", "details": ["목표 요청 양식", "승인 프로세스", "수정 요청 기능", "진행 상황 추적"]}',
1, @current_time, @current_time),

(@user_id, 'quarterly', '장기적인 성장전망', 2, 'external_link',
'{"url": "#", "target": "_self", "description": "분기별 성과를 바탕으로 장기적인 학습 성장 경로를 제시합니다.", "details": ["성장 궤적 분석", "미래 예측 모델", "목표 조정 제안", "성과 예상 시나리오"]}',
2, @current_time, @current_time),

(@user_id, 'quarterly', '주간목표 분석', 3, 'external_link',
'{"url": "#", "target": "_self", "description": "주간 목표 달성률을 분석하여 분기 목표 달성 가능성을 평가합니다.", "details": ["주간 성과 분석", "달성률 계산", "위험 요소 식별", "개선 방안 제시"]}',
3, @current_time, @current_time),

(@user_id, 'quarterly', '학교생활 도우미', 4, 'external_link',
'{"url": "#", "target": "_self", "description": "학교 일정과 연계된 학습 계획을 수립하고 관리합니다.", "details": ["학교 일정 연동", "시험 일정 관리", "과제 마감일 추적", "학사 일정 알림"]}',
4, @current_time, @current_time),

-- 학부모상담 탭
(@user_id, 'quarterly', '성적관리', 5, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 성과와 성적 변화를 체계적으로 관리하고 분석합니다.", "details": ["성적 추이 분석", "과목별 성과 관리", "약점 영역 식별", "개선 계획 수립"]}',
5, @current_time, @current_time),

(@user_id, 'quarterly', '일정관리', 6, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 일정과 학교 활동을 통합적으로 관리합니다.", "details": ["통합 일정 관리", "우선순위 설정", "시간 배분 최적화", "일정 충돌 해결"]}',
6, @current_time, @current_time),

(@user_id, 'quarterly', '과제관리', 7, 'external_link',
'{"url": "#", "target": "_self", "description": "과제 진행 상황과 완성도를 체계적으로 추적합니다.", "details": ["과제 진행 추적", "완성도 평가", "지연 위험 관리", "품질 향상 지원"]}',
7, @current_time, @current_time),

(@user_id, 'quarterly', '도전관리', 8, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 과정에서 발생하는 도전과 어려움을 관리합니다.", "details": ["도전 과제 식별", "해결 전략 수립", "지원 체계 구축", "성취 인정 시스템"]}',
8, @current_time, @current_time),

(@user_id, 'quarterly', '상담관리', 9, 'external_link',
'{"url": "#", "target": "_self", "description": "정기적인 상담을 통해 학습 진행 상황을 점검합니다.", "details": ["상담 일정 관리", "상담 기록 보관", "문제 해결 추적", "후속 조치 계획"]}',
9, @current_time, @current_time),

(@user_id, 'quarterly', '상담앱 활용', 10, 'external_link',
'{"url": "#", "target": "_self", "description": "디지털 상담 도구를 활용하여 효율적인 소통을 지원합니다.", "details": ["모바일 상담 앱", "실시간 소통 기능", "문서 공유 시스템", "알림 서비스"]}',
10, @current_time, @current_time),

(@user_id, 'quarterly', '상담지연 관리', 11, 'external_link',
'{"url": "#", "target": "_self", "description": "상담 지연 시 대응 방안과 보완 조치를 제공합니다.", "details": ["지연 원인 분석", "대안 상담 방법", "응급 상담 시스템", "지연 예방 체계"]}',
11, @current_time, @current_time),

(@user_id, 'quarterly', '다음 분기 시나리오 관리', 12, 'external_link',
'{"url": "#", "target": "_self", "description": "현재 성과를 바탕으로 다음 분기 계획을 수립합니다.", "details": ["성과 종합 분석", "다음 분기 목표 설정", "전략 수정 계획", "자원 배분 조정"]}',
12, @current_time, @current_time);

-- 2. 주간활동 (weekly) 카드들
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
-- 주간계획 탭
(@user_id, 'weekly', '주간학습 계획수립', 0, 'external_link',
'{"url": "#", "target": "_self", "description": "효과적인 주간 학습 계획을 수립하고 관리합니다.", "details": ["주간 목표 설정", "일정 배분 계획", "우선순위 결정", "진행 상황 모니터링"]}',
0, @current_time, @current_time),

(@user_id, 'weekly', '주간성과 분석', 1, 'external_link',
'{"url": "#", "target": "_self", "description": "주간 학습 성과를 분석하고 개선점을 찾습니다.", "details": ["성과 데이터 수집", "목표 달성률 분석", "문제점 파악", "개선 방안 도출"]}',
1, @current_time, @current_time),

(@user_id, 'weekly', '주간피드백 수집', 2, 'external_link',
'{"url": "#", "target": "_self", "description": "학습자와 학부모의 주간 피드백을 수집합니다.", "details": ["피드백 양식 제공", "의견 수렴", "피드백 분석", "개선 계획 수립"]}',
2, @current_time, @current_time),

(@user_id, 'weekly', '다음주 목표설정', 3, 'external_link',
'{"url": "#", "target": "_self", "description": "이번 주 성과를 바탕으로 다음 주 목표를 설정합니다.", "details": ["성과 평가", "목표 조정", "실행 계획 수립", "자원 배분"]}',
3, @current_time, @current_time),

-- 활동기록 탭
(@user_id, 'weekly', '학습활동 기록', 4, 'external_link',
'{"url": "#", "target": "_self", "description": "주간 학습 활동을 상세히 기록합니다.", "details": ["일별 활동 기록", "학습 시간 추적", "완료 과제 목록", "특이사항 메모"]}',
4, @current_time, @current_time),

(@user_id, 'weekly', '숙제완료 체크', 5, 'external_link',
'{"url": "#", "target": "_self", "description": "주간 숙제 완료 상황을 체크합니다.", "details": ["숙제 목록 관리", "완료 상태 확인", "품질 평가", "피드백 제공"]}',
5, @current_time, @current_time),

(@user_id, 'weekly', '출결현황 관리', 6, 'external_link',
'{"url": "#", "target": "_self", "description": "주간 출결 현황을 관리합니다.", "details": ["출석 체크", "지각/결석 기록", "보충 계획", "출결 통계"]}',
6, @current_time, @current_time),

(@user_id, 'weekly', '특별활동 기록', 7, 'external_link',
'{"url": "#", "target": "_self", "description": "특별 활동 참여를 기록합니다.", "details": ["활동 내용 기록", "성과 평가", "사진/영상 저장", "포트폴리오 관리"]}',
7, @current_time, @current_time);

-- 3. 일일활동 (daily) 카드들
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
-- 오늘목표 탭
(@user_id, 'daily', '오늘의 학습목표', 0, 'external_link',
'{"url": "#", "target": "_self", "description": "오늘 달성할 학습 목표를 설정합니다.", "details": ["목표 설정", "우선순위 정하기", "시간 배분", "체크리스트 작성"]}',
0, @current_time, @current_time),

(@user_id, 'daily', '시간관리 도우미', 1, 'external_link',
'{"url": "#", "target": "_self", "description": "효율적인 시간 관리를 돕습니다.", "details": ["시간표 작성", "집중 시간 설정", "휴식 시간 계획", "시간 추적"]}',
1, @current_time, @current_time),

(@user_id, 'daily', '집중력 향상 도구', 2, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 집중력을 높이는 도구를 제공합니다.", "details": ["포모도로 타이머", "집중 음악", "방해 요소 차단", "집중도 측정"]}',
2, @current_time, @current_time),

(@user_id, 'daily', '오늘의 성과 정리', 3, 'external_link',
'{"url": "#", "target": "_self", "description": "하루의 학습 성과를 정리합니다.", "details": ["완료 과제 체크", "학습 시간 기록", "성취도 평가", "내일 계획 수립"]}',
3, @current_time, @current_time),

-- 학습추적 탭
(@user_id, 'daily', '실시간 진도 체크', 4, 'external_link',
'{"url": "#", "target": "_self", "description": "실시간으로 학습 진도를 확인합니다.", "details": ["진도율 표시", "목표 대비 현황", "예상 완료 시간", "진도 알림"]}',
4, @current_time, @current_time),

(@user_id, 'daily', '문제풀이 기록', 5, 'external_link',
'{"url": "#", "target": "_self", "description": "문제 풀이 과정을 기록합니다.", "details": ["문제 번호 기록", "정답률 추적", "오답 노트", "풀이 시간 측정"]}',
5, @current_time, @current_time),

(@user_id, 'daily', '학습시간 측정', 6, 'external_link',
'{"url": "#", "target": "_self", "description": "과목별 학습 시간을 측정합니다.", "details": ["타이머 기능", "과목별 시간 기록", "일일 통계", "주간 리포트"]}',
6, @current_time, @current_time),

(@user_id, 'daily', '오답노트 관리', 7, 'external_link',
'{"url": "#", "target": "_self", "description": "오답을 체계적으로 관리합니다.", "details": ["오답 기록", "유형별 분류", "재학습 계획", "오답률 분석"]}',
7, @current_time, @current_time);

-- 4. 실시간 관리 (realtime) 카드들
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
-- 모니터링 탭
(@user_id, 'realtime', '학습상태 모니터링', 0, 'external_link',
'{"url": "#", "target": "_self", "description": "실시간으로 학습 상태를 모니터링합니다.", "details": ["현재 학습 활동", "집중도 측정", "진도 현황", "이상 징후 감지"]}',
0, @current_time, @current_time),

(@user_id, 'realtime', '집중도 실시간 체크', 1, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 집중도를 실시간으로 확인합니다.", "details": ["집중 시간 측정", "산만도 체크", "휴식 알림", "집중 패턴 분석"]}',
1, @current_time, @current_time),

(@user_id, 'realtime', '질문사항 즉시대응', 2, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 중 발생하는 질문에 즉시 대응합니다.", "details": ["실시간 질문 접수", "즉시 답변", "FAQ 연결", "튜터 연결"]}',
2, @current_time, @current_time),

(@user_id, 'realtime', '긴급알림 시스템', 3, 'external_link',
'{"url": "#", "target": "_self", "description": "중요한 사항을 즉시 알립니다.", "details": ["긴급 공지", "일정 변경 알림", "과제 마감 알림", "시스템 알림"]}',
3, @current_time, @current_time),

-- 즉시피드백 탭
(@user_id, 'realtime', '학습결과 즉시분석', 4, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 결과를 즉시 분석합니다.", "details": ["정답률 분석", "취약점 파악", "개선점 제시", "추천 학습"]}',
4, @current_time, @current_time),

(@user_id, 'realtime', '실시간 피드백 전달', 5, 'external_link',
'{"url": "#", "target": "_self", "description": "학습에 대한 피드백을 실시간으로 전달합니다.", "details": ["즉시 피드백", "격려 메시지", "개선 제안", "성과 인정"]}',
5, @current_time, @current_time),

(@user_id, 'realtime', '동기부여 메시지', 6, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 동기를 높이는 메시지를 전달합니다.", "details": ["격려 메시지", "성취 축하", "목표 상기", "동기 부여 팁"]}',
6, @current_time, @current_time),

(@user_id, 'realtime', '보상시스템 운영', 7, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 성과에 대한 보상을 제공합니다.", "details": ["포인트 적립", "배지 획득", "레벨 상승", "보상 교환"]}',
7, @current_time, @current_time);

-- 5. 상호작용 (interaction) 카드들
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
-- 소통관리 탭
(@user_id, 'interaction', '학생-교사 메시징', 0, 'external_link',
'{"url": "#", "target": "_self", "description": "학생과 교사 간 메시지 소통을 관리합니다.", "details": ["메시지 전송", "답변 관리", "대화 기록", "알림 설정"]}',
0, @current_time, @current_time),

(@user_id, 'interaction', '학부모 소통채널', 1, 'external_link',
'{"url": "#", "target": "_self", "description": "학부모와의 소통 채널을 운영합니다.", "details": ["공지사항 전달", "상담 예약", "진도 공유", "피드백 수렴"]}',
1, @current_time, @current_time),

(@user_id, 'interaction', '그룹 스터디 관리', 2, 'external_link',
'{"url": "#", "target": "_self", "description": "그룹 스터디를 효과적으로 관리합니다.", "details": ["그룹 생성", "일정 조율", "역할 분담", "성과 공유"]}',
2, @current_time, @current_time),

(@user_id, 'interaction', '질의응답 포럼', 3, 'external_link',
'{"url": "#", "target": "_self", "description": "질문과 답변을 공유하는 포럼을 운영합니다.", "details": ["질문 등록", "답변 작성", "베스트 답변", "FAQ 관리"]}',
3, @current_time, @current_time),

-- 피드백 탭
(@user_id, 'interaction', '학습피드백 수집', 4, 'external_link',
'{"url": "#", "target": "_self", "description": "학습에 대한 피드백을 수집합니다.", "details": ["피드백 양식", "만족도 조사", "개선 의견", "통계 분석"]}',
4, @current_time, @current_time),

(@user_id, 'interaction', '성과리뷰 시스템', 5, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 성과를 정기적으로 리뷰합니다.", "details": ["성과 평가", "강점 분석", "개선점 도출", "목표 재설정"]}',
5, @current_time, @current_time),

(@user_id, 'interaction', '동료평가 관리', 6, 'external_link',
'{"url": "#", "target": "_self", "description": "동료 학습자 간 평가를 관리합니다.", "details": ["평가 기준", "상호 평가", "피드백 교환", "개선 협력"]}',
6, @current_time, @current_time),

(@user_id, 'interaction', '멘토링 프로그램', 7, 'external_link',
'{"url": "#", "target": "_self", "description": "멘토-멘티 프로그램을 운영합니다.", "details": ["멘토 매칭", "멘토링 일정", "성장 기록", "성과 공유"]}',
7, @current_time, @current_time);

-- 6. 인지관성 개선 (bias) 카드들
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
-- 인지패턴분석 탭
(@user_id, 'bias', '집중력 패턴 분석', 0, 'external_link',
'{"url": "#", "target": "_self", "description": "개인별 집중력 패턴을 분석하여 최적의 학습 시간을 찾습니다.", "details": ["시간대별 집중도 측정", "최적 학습 시간 도출", "방해 요인 분석", "맞춤형 시간표 제안"], "hasChainInteraction": true}',
0, @current_time, @current_time),

(@user_id, 'bias', '문제풀이 습관 진단', 1, 'external_link',
'{"url": "#", "target": "_self", "description": "문제 풀이 과정의 비효율적 습관을 찾아 개선합니다.", "details": ["풀이 시간 분석", "실수 패턴 파악", "건너뛰기 습관 체크", "효율적 풀이법 제안"], "hasChainInteraction": true}',
1, @current_time, @current_time),

(@user_id, 'bias', '기억력 강화 전략', 2, 'external_link',
'{"url": "#", "target": "_self", "description": "개인별 기억 특성에 맞는 암기 전략을 제공합니다.", "details": ["기억 유형 테스트", "최적 암기법 매칭", "반복 주기 설정", "장기 기억 전환"], "hasChainInteraction": true}',
2, @current_time, @current_time),

(@user_id, 'bias', '수학적 사고력 개선', 3, 'external_link',
'{"url": "#", "target": "_self", "description": "수학적 사고의 막힘을 해결하는 단계별 전략입니다.", "details": ["사고 과정 분석", "논리적 단계 훈련", "직관력 개발", "문제 해석 능력 향상"], "hasChainInteraction": true}',
3, @current_time, @current_time),

-- 개선도구 탭
(@user_id, 'bias', '포모도로 맞춤 설정', 4, 'external_link',
'{"url": "#", "target": "_self", "description": "개인의 집중력 패턴에 맞춘 포모도로 기법을 설정합니다.", "details": ["집중 시간 최적화", "휴식 시간 조절", "과목별 설정", "성과 추적"], "hasChainInteraction": true}',
4, @current_time, @current_time),

(@user_id, 'bias', '오답 패턴 극복기', 5, 'external_link',
'{"url": "#", "target": "_self", "description": "반복되는 오답 패턴을 분석하고 극복 방법을 제시합니다.", "details": ["오답 유형 분류", "원인 분석", "맞춤형 연습 문제", "개선도 측정"], "hasChainInteraction": true}',
5, @current_time, @current_time),

(@user_id, 'bias', '시험 불안 해소법', 6, 'external_link',
'{"url": "#", "target": "_self", "description": "시험 불안을 줄이고 실력을 발휘할 수 있도록 돕습니다.", "details": ["불안도 측정", "이완 기법 훈련", "자신감 향상 프로그램", "모의 시험 적응"], "hasChainInteraction": true}',
6, @current_time, @current_time),

(@user_id, 'bias', '학습 동기 부스터', 7, 'external_link',
'{"url": "#", "target": "_self", "description": "지속적인 학습 동기를 유지하는 개인별 전략입니다.", "details": ["동기 유형 분석", "목표 시각화", "보상 시스템", "성취감 극대화"], "hasChainInteraction": true}',
7, @current_time, @current_time);

-- 7. 개발 (development) 카드들
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
-- 컨텐츠개발 탭
(@user_id, 'development', '학습자료 제작도구', 0, 'external_link',
'{"url": "#", "target": "_self", "description": "효과적인 학습 자료를 제작하는 도구입니다.", "details": ["템플릿 제공", "멀티미디어 편집", "인터랙티브 요소", "배포 관리"]}',
0, @current_time, @current_time),

(@user_id, 'development', '문제은행 구축', 1, 'external_link',
'{"url": "#", "target": "_self", "description": "체계적인 문제은행을 구축하고 관리합니다.", "details": ["문제 등록", "난이도 설정", "유형별 분류", "출제 이력 관리"]}',
1, @current_time, @current_time),

(@user_id, 'development', '동영상강의 제작', 2, 'external_link',
'{"url": "#", "target": "_self", "description": "고품질 동영상 강의를 제작합니다.", "details": ["녹화 도구", "편집 기능", "자막 추가", "플랫폼 업로드"]}',
2, @current_time, @current_time),

(@user_id, 'development', '인터랙티브 콘텐츠', 3, 'external_link',
'{"url": "#", "target": "_self", "description": "상호작용이 가능한 학습 콘텐츠를 개발합니다.", "details": ["시뮬레이션 제작", "게임화 요소", "실시간 피드백", "학습 분석"]}',
3, @current_time, @current_time),

-- 앱개발 탭
(@user_id, 'development', '학습앱 프로토타입', 4, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 앱의 프로토타입을 개발합니다.", "details": ["UI/UX 설계", "기능 구현", "테스트 진행", "피드백 수렴"]}',
4, @current_time, @current_time),

(@user_id, 'development', 'AI 튜터 개발', 5, 'external_link',
'{"url": "#", "target": "_self", "description": "AI 기반 개인 튜터를 개발합니다.", "details": ["AI 모델 훈련", "대화 시나리오", "학습 추천 알고리즘", "성과 분석"]}',
5, @current_time, @current_time),

(@user_id, 'development', '학습분석 대시보드', 6, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 데이터를 시각화하는 대시보드를 개발합니다.", "details": ["데이터 수집", "시각화 설계", "리포트 생성", "인사이트 도출"]}',
6, @current_time, @current_time),

(@user_id, 'development', '맞춤형 알고리즘', 7, 'external_link',
'{"url": "#", "target": "_self", "description": "개인별 맞춤 학습 알고리즘을 개발합니다.", "details": ["학습 패턴 분석", "추천 시스템", "난이도 조절", "최적화 알고리즘"]}',
7, @current_time, @current_time);

-- 8. 바이럴 마케팅 (viral) 카드들
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
-- 블로그 탭
(@user_id, 'viral', '바이럴 포스트 작성', 0, 'external_link',
'{"url": "#", "target": "_self", "description": "화제성 있는 블로그 포스트 제작", "details": ["트렌드 분석", "훅 제목 작성", "공유 유도 콘텐츠", "소셜 버튼 배치"]}',
0, @current_time, @current_time),

(@user_id, 'viral', '키워드 해킹', 1, 'external_link',
'{"url": "#", "target": "_self", "description": "검색량 높은 키워드 공략", "details": ["키워드 트렌드 분석", "경쟁사 분석", "롱테일 키워드 발굴"]}',
1, @current_time, @current_time),

(@user_id, 'viral', '백링크 구축', 2, 'external_link',
'{"url": "#", "target": "_self", "description": "도메인 권위도 향상 전략", "details": ["게스트 포스팅", "인플루언서 협업", "언론사 기고"]}',
2, @current_time, @current_time),

-- 유튜브 탭
(@user_id, 'viral', '바이럴 쇼츠 제작', 3, 'external_link',
'{"url": "#", "target": "_self", "description": "짧고 임팩트 있는 쇼츠 콘텐츠", "details": ["후크 포인트 설정", "15초 스토리텔링", "트렌드 음악 활용"]}',
3, @current_time, @current_time),

(@user_id, 'viral', '썸네일 최적화', 4, 'external_link',
'{"url": "#", "target": "_self", "description": "클릭률 높이는 썸네일 전략", "details": ["A/B 테스팅", "심리학적 디자인", "텍스트 배치 최적화"]}',
4, @current_time, @current_time),

(@user_id, 'viral', '알고리즘 해킹', 5, 'external_link',
'{"url": "#", "target": "_self", "description": "유튜브 알고리즘 최적화", "details": ["시청 지속 시간 늘리기", "댓글 유도 전략", "재생목록 활용"]}',
5, @current_time, @current_time);

-- 9. 상담 (consultation) 카드들 - 가상의 데이터 추가
INSERT INTO mdl_alt42DB_card_plugin_settings 
(user_id, category, card_title, card_index, plugin_id, plugin_config, display_order, timecreated, timemodified)
VALUES
-- 학생상담 탭
(@user_id, 'consultation', '진로상담 가이드', 0, 'external_link',
'{"url": "#", "target": "_self", "description": "학생의 진로 탐색을 돕는 상담 가이드", "details": ["적성 검사", "진로 탐색", "대학 정보", "직업 정보"]}',
0, @current_time, @current_time),

(@user_id, 'consultation', '학습고민 상담', 1, 'external_link',
'{"url": "#", "target": "_self", "description": "학습 관련 고민을 해결하는 상담", "details": ["학습 방법 상담", "시간 관리", "스트레스 관리", "동기 부여"]}',
1, @current_time, @current_time),

-- 학부모소통 탭
(@user_id, 'consultation', '학부모 상담 준비', 2, 'external_link',
'{"url": "#", "target": "_self", "description": "효과적인 학부모 상담을 위한 준비", "details": ["상담 자료 준비", "성과 보고서", "개선 계획", "협력 방안"]}',
2, @current_time, @current_time),

(@user_id, 'consultation', '정기 소통 관리', 3, 'external_link',
'{"url": "#", "target": "_self", "description": "학부모와의 정기적인 소통 관리", "details": ["월간 리포트", "주요 이슈 공유", "피드백 수렴", "협력 강화"]}',
3, @current_time, @current_time);

-- 카드 설정이 성공적으로 저장되었는지 확인
SELECT COUNT(*) as total_cards FROM mdl_alt42DB_card_plugin_settings WHERE user_id = @user_id;