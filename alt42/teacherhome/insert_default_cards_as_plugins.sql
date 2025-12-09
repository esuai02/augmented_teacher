-- 기본 카드들을 외부링크 플러그인으로 DB에 삽입
-- 작성일: 2025-01-16
-- 설명: 각 모듈의 기본 카드들을 mdl_ktm_card_plugin_settings 테이블에 삽입

-- 사용자 ID 설정 (실제 환경에서는 동적으로 설정)
SET @user_id = 2;

-- 1. 분기활동 (quarterly) 카드들
-- 계획관리 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'quarterly', '분기목표 설정 도우미', 0, 'external_link', '{"url":"#quarterly-goal-helper","target":"_self","description":"학습자의 현재 수준과 목표를 분석하여 분기별 달성 가능한 목표를 설정합니다.","details":["현재 수준 진단","목표 설정 가이드","달성 계획 수립","진도 체크 시스템"]}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '분기목표 요청', 1, 'external_link', '{"url":"#quarterly-goal-request","target":"_self","description":"학습자가 직접 분기별 목표를 요청하고 승인받을 수 있는 시스템입니다.","details":["목표 요청 양식","승인 프로세스","수정 요청 기능","진행 상황 추적"]}', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '장기적인 성장전망', 2, 'external_link', '{"url":"#long-term-growth","target":"_self","description":"분기별 성과를 바탕으로 장기적인 학습 성장 경로를 제시합니다.","details":["성장 궤적 분석","미래 예측 모델","목표 조정 제안","성과 예상 시나리오"]}', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '주간목표 분석', 3, 'external_link', '{"url":"#weekly-goal-analysis","target":"_self","description":"주간 목표 달성률을 분석하여 분기 목표 달성 가능성을 평가합니다.","details":["주간 성과 분석","달성률 계산","위험 요소 식별","개선 방안 제시"]}', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '학교생활 도우미', 4, 'external_link', '{"url":"#school-life-helper","target":"_self","description":"학교 일정과 연계된 학습 계획을 수립하고 관리합니다.","details":["학교 일정 연동","시험 일정 관리","과제 마감일 추적","학사 일정 알림"]}', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 학부모상담 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'quarterly', '성적관리', 5, 'external_link', '{"url":"#grade-management","target":"_self","description":"학습 성과와 성적 변화를 체계적으로 관리하고 분석합니다.","details":["성적 추이 분석","과목별 성과 관리","약점 영역 식별","개선 계획 수립"]}', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '일정관리', 6, 'external_link', '{"url":"#schedule-management","target":"_self","description":"학습 일정과 학교 활동을 통합적으로 관리합니다.","details":["통합 일정 관리","우선순위 설정","시간 배분 최적화","일정 충돌 해결"]}', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '과제관리', 7, 'external_link', '{"url":"#homework-management","target":"_self","description":"과제 진행 상황과 완성도를 체계적으로 추적합니다.","details":["과제 진행 추적","완성도 평가","지연 위험 관리","품질 향상 지원"]}', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '도전관리', 8, 'external_link', '{"url":"#challenge-management","target":"_self","description":"학습 과정에서 발생하는 도전과 어려움을 관리합니다.","details":["도전 과제 식별","해결 전략 수립","지원 체계 구축","성취 인정 시스템"]}', 1, 8, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '상담관리', 9, 'external_link', '{"url":"#consultation-management","target":"_self","description":"정기적인 상담을 통해 학습 진행 상황을 점검합니다.","details":["상담 일정 관리","상담 기록 보관","문제 해결 추적","후속 조치 계획"]}', 1, 9, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '상담앱 활용', 10, 'external_link', '{"url":"#consultation-app","target":"_self","description":"디지털 상담 도구를 활용하여 효율적인 소통을 지원합니다.","details":["모바일 상담 앱","실시간 소통 기능","문서 공유 시스템","알림 서비스"]}', 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '상담지연 관리', 11, 'external_link', '{"url":"#consultation-delay","target":"_self","description":"상담 지연 시 대응 방안과 보완 조치를 제공합니다.","details":["지연 원인 분석","대안 상담 방법","응급 상담 시스템","지연 예방 체계"]}', 1, 11, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'quarterly', '다음 분기 시나리오 관리', 12, 'external_link', '{"url":"#next-quarter-scenario","target":"_self","description":"현재 성과를 바탕으로 다음 분기 계획을 수립합니다.","details":["성과 종합 분석","다음 분기 목표 설정","전략 수정 계획","자원 배분 조정"]}', 1, 12, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2. 주간활동 (weekly) 카드들
-- 주간계획 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'weekly', '주간 목표 설정', 0, 'external_link', '{"url":"#weekly-goal-setting","target":"_self","description":"주간 학습 목표를 체계적으로 설정하고 관리합니다.","details":["주간 목표 수립","우선순위 설정","일정 배분","진도 체크"]}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'weekly', '학습 일정 관리', 1, 'external_link', '{"url":"#weekly-schedule","target":"_self","description":"주간 학습 일정을 효율적으로 관리합니다.","details":["시간표 작성","과목별 시간 배분","휴식 시간 관리","일정 조정"]}', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'weekly', '진도 체크', 2, 'external_link', '{"url":"#weekly-progress-check","target":"_self","description":"주간 학습 진도를 점검하고 피드백을 제공합니다.","details":["진도율 확인","목표 대비 성과","보충 학습 계획","다음 주 준비"]}', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'weekly', '주간 리뷰', 3, 'external_link', '{"url":"#weekly-review","target":"_self","description":"한 주의 학습 성과를 종합적으로 검토합니다.","details":["성과 분석","개선점 도출","우수 사례 공유","동기 부여"]}', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 주간성과 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'weekly', '성과 분석', 4, 'external_link', '{"url":"#weekly-performance-analysis","target":"_self","description":"주간 학습 성과를 상세하게 분석합니다.","details":["과목별 성과","학습 시간 분석","효율성 평가","개선 방향"]}', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'weekly', '목표 달성률', 5, 'external_link', '{"url":"#weekly-achievement-rate","target":"_self","description":"설정한 목표 대비 달성률을 측정합니다.","details":["달성률 계산","미달성 원인 분석","보완 계획","성공 요인"]}', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'weekly', '피드백 관리', 6, 'external_link', '{"url":"#weekly-feedback","target":"_self","description":"학습 피드백을 체계적으로 관리합니다.","details":["교사 피드백","자기 평가","동료 평가","개선 계획"]}', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'weekly', '보상 시스템', 7, 'external_link', '{"url":"#weekly-reward-system","target":"_self","description":"주간 성과에 따른 보상 체계를 운영합니다.","details":["포인트 적립","배지 획득","랭킹 시스템","특별 보상"]}', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 3. 일일활동 (daily) 카드들
-- 오늘의 목표 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'daily', '오늘의 학습 목표', 0, 'external_link', '{"url":"#daily-learning-goal","target":"_self","description":"오늘 달성할 학습 목표를 설정합니다.","details":["목표 설정","우선순위 정하기","시간 계획","체크리스트"]}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'daily', '집중 시간 관리', 1, 'external_link', '{"url":"#daily-focus-time","target":"_self","description":"효과적인 학습을 위한 집중 시간을 관리합니다.","details":["포모도로 타이머","휴식 시간 설정","방해 요소 차단","집중도 측정"]}', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'daily', '오늘의 복습', 2, 'external_link', '{"url":"#daily-review","target":"_self","description":"학습한 내용을 효과적으로 복습합니다.","details":["복습 계획","핵심 내용 정리","문제 풀이","오답 노트"]}', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'daily', '일일 체크리스트', 3, 'external_link', '{"url":"#daily-checklist","target":"_self","description":"오늘 해야 할 일을 체크리스트로 관리합니다.","details":["할 일 목록","완료 체크","우선순위 조정","미완료 관리"]}', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 일일성과 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'daily', '학습 시간 기록', 4, 'external_link', '{"url":"#daily-time-record","target":"_self","description":"오늘의 학습 시간을 상세히 기록합니다.","details":["과목별 시간","집중도 평가","휴식 시간","총 학습 시간"]}', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'daily', '목표 달성 현황', 5, 'external_link', '{"url":"#daily-achievement-status","target":"_self","description":"오늘 설정한 목표의 달성 현황을 확인합니다.","details":["달성 항목","미달성 항목","달성률","보완 필요 사항"]}', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'daily', '오늘의 성찰', 6, 'external_link', '{"url":"#daily-reflection","target":"_self","description":"하루를 마무리하며 학습을 성찰합니다.","details":["잘한 점","개선할 점","느낀 점","내일 계획"]}', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'daily', '일일 보고서', 7, 'external_link', '{"url":"#daily-report","target":"_self","description":"오늘의 학습 활동을 종합한 보고서를 생성합니다.","details":["학습 요약","성과 분석","피드백","다음 계획"]}', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 4. 실시간 모니터링 (realtime) 카드들
-- 실시간 추적 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'realtime', '학습 진행 상황', 0, 'external_link', '{"url":"#realtime-learning-progress","target":"_self","description":"현재 진행 중인 학습 활동을 실시간으로 추적합니다.","details":["진행 중인 과제","경과 시간","집중도 측정","진도율"]}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'realtime', '집중도 모니터링', 1, 'external_link', '{"url":"#realtime-focus-monitoring","target":"_self","description":"학습 집중도를 실시간으로 모니터링합니다.","details":["집중도 그래프","방해 요소 감지","집중 시간 측정","알림 설정"]}', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'realtime', '실시간 피드백', 2, 'external_link', '{"url":"#realtime-feedback","target":"_self","description":"학습 중 즉각적인 피드백을 제공합니다.","details":["즉시 피드백","힌트 제공","오답 알림","격려 메시지"]}', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'realtime', '활동 타임라인', 3, 'external_link', '{"url":"#realtime-activity-timeline","target":"_self","description":"학습 활동을 시간순으로 기록합니다.","details":["활동 기록","시간대별 분석","패턴 파악","효율성 평가"]}', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 즉시 대응 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'realtime', '긴급 알림', 4, 'external_link', '{"url":"#realtime-urgent-alert","target":"_self","description":"중요한 학습 이벤트를 즉시 알립니다.","details":["마감 임박 알림","일정 변경 알림","중요 공지","시스템 알림"]}', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'realtime', '실시간 질문 답변', 5, 'external_link', '{"url":"#realtime-qa","target":"_self","description":"학습 중 발생하는 질문에 즉시 답변합니다.","details":["즉시 답변","참고 자료 제공","유사 문제 제시","추가 설명"]}', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'realtime', '학습 중단 관리', 6, 'external_link', '{"url":"#realtime-interruption-management","target":"_self","description":"학습 중단 상황을 감지하고 대응합니다.","details":["중단 감지","원인 분석","복귀 유도","대안 제시"]}', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'realtime', '실시간 동기부여', 7, 'external_link', '{"url":"#realtime-motivation","target":"_self","description":"학습 동기를 실시간으로 부여합니다.","details":["격려 메시지","성취 알림","진도 축하","동기 부여 팁"]}', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 5. 상호작용 (interaction) 카드들
-- 소통 관리 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'interaction', '교사-학생 소통', 0, 'external_link', '{"url":"#interaction-teacher-student","target":"_self","description":"교사와 학생 간의 원활한 소통을 지원합니다.","details":["메시지 교환","질문 답변","과제 피드백","상담 예약"]}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'interaction', '학부모 소통', 1, 'external_link', '{"url":"#interaction-parent-communication","target":"_self","description":"학부모와의 효과적인 소통 채널을 제공합니다.","details":["학습 현황 공유","상담 일정","알림 설정","문의 응답"]}', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'interaction', '동료 학습', 2, 'external_link', '{"url":"#interaction-peer-learning","target":"_self","description":"학생들 간의 협력 학습을 촉진합니다.","details":["스터디 그룹","토론 게시판","자료 공유","상호 평가"]}', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'interaction', '멘토링 시스템', 3, 'external_link', '{"url":"#interaction-mentoring","target":"_self","description":"선후배 간 멘토링을 체계적으로 운영합니다.","details":["멘토 매칭","멘토링 일정","활동 기록","성과 평가"]}', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 피드백 시스템 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'interaction', '학습 피드백', 4, 'external_link', '{"url":"#interaction-learning-feedback","target":"_self","description":"학습 과정에 대한 상세한 피드백을 제공합니다.","details":["과제 피드백","시험 분석","개선 제안","강점 분석"]}', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'interaction', '행동 피드백', 5, 'external_link', '{"url":"#interaction-behavior-feedback","target":"_self","description":"학습 태도와 행동에 대한 피드백을 제공합니다.","details":["참여도 평가","태도 개선","습관 형성","동기 강화"]}', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'interaction', '성과 공유', 6, 'external_link', '{"url":"#interaction-achievement-sharing","target":"_self","description":"학습 성과를 공유하고 축하합니다.","details":["성과 게시","축하 메시지","동기 부여","우수 사례"]}', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'interaction', '개선 제안', 7, 'external_link', '{"url":"#interaction-improvement-suggestions","target":"_self","description":"학습 개선을 위한 구체적인 제안을 제공합니다.","details":["약점 분석","개선 방법","실행 계획","진도 추적"]}', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 6. 인지관성 개선 (bias) 카드들
-- 학습 패턴 분석 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'bias', '인지 스타일 진단', 0, 'external_link', '{"url":"#bias-cognitive-style","target":"_self","description":"개인의 인지 스타일을 진단하고 분석합니다.","details":["인지 유형 테스트","강점 분석","약점 파악","맞춤 전략","hasChainInteraction":true}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'bias', '학습 습관 분석', 1, 'external_link', '{"url":"#bias-learning-habits","target":"_self","description":"현재 학습 습관의 효율성을 분석합니다.","details":["습관 패턴 분석","효율성 평가","개선점 도출","습관 개선 계획","hasChainInteraction":true}', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'bias', '오류 패턴 분석', 2, 'external_link', '{"url":"#bias-error-pattern","target":"_self","description":"반복되는 오류 패턴을 찾아 개선합니다.","details":["오류 유형 분류","원인 분석","패턴 인식","해결 전략","hasChainInteraction":true}', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'bias', '집중력 패턴 분석', 3, 'external_link', '{"url":"#bias-focus-pattern","target":"_self","description":"집중력 변화 패턴을 분석하여 최적화합니다.","details":["집중도 측정","시간대별 분석","방해 요소 파악","집중력 향상 전략","hasChainInteraction":true}', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 개선 전략 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'bias', '포모도로 설정', 4, 'external_link', '{"url":"#bias-pomodoro-setting","target":"_self","description":"개인에 맞는 포모도로 기법을 설정합니다.","details":["집중 시간 설정","휴식 시간 조정","사이클 최적화","효과 측정","hasChainInteraction":true}', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'bias', '학습 환경 최적화', 5, 'external_link', '{"url":"#bias-environment-optimization","target":"_self","description":"최적의 학습 환경을 조성합니다.","details":["환경 요소 분석","소음 관리","조명 최적화","정리 정돈","hasChainInteraction":true}', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'bias', '동기부여 전략', 6, 'external_link', '{"url":"#bias-motivation-strategy","target":"_self","description":"지속적인 학습 동기를 유지하는 전략을 제공합니다.","details":["동기 유형 파악","보상 체계 설계","목표 시각화","성취감 강화","hasChainInteraction":true}', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'bias', '메타인지 향상', 7, 'external_link', '{"url":"#bias-metacognition","target":"_self","description":"학습에 대한 인식과 조절 능력을 향상시킵니다.","details":["자기 평가 훈련","학습 전략 인식","계획 수립 능력","반성적 사고","hasChainInteraction":true}', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 7. 개발 (development) 카드들
-- 콘텐츠 개발 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'development', '학습 자료 제작', 0, 'external_link', '{"url":"#dev-learning-materials","target":"_self","description":"효과적인 학습 자료를 제작합니다.","details":["자료 기획","콘텐츠 제작","디자인 작업","품질 검토"]}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'development', '문제 은행 구축', 1, 'external_link', '{"url":"#dev-question-bank","target":"_self","description":"체계적인 문제 은행을 구축합니다.","details":["문제 분류","난이도 설정","해설 작성","태그 관리"]}', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'development', '멀티미디어 콘텐츠', 2, 'external_link', '{"url":"#dev-multimedia-content","target":"_self","description":"동영상, 오디오 등 멀티미디어 학습 콘텐츠를 제작합니다.","details":["스크립트 작성","녹화 및 편집","자막 제작","플랫폼 업로드"]}', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'development', '인터랙티브 콘텐츠', 3, 'external_link', '{"url":"#dev-interactive-content","target":"_self","description":"상호작용이 가능한 학습 콘텐츠를 개발합니다.","details":["시뮬레이션 제작","퀴즈 개발","게임화 요소","피드백 시스템"]}', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 앱 개발 탭 카드들
INSERT INTO mdl_ktm_card_plugin_settings (user_id, category, card_title, card_index, plugin_id, plugin_config, is_active, display_order, timecreated, timemodified) VALUES
(@user_id, 'development', '학습 앱 기획', 4, 'external_link', '{"url":"#dev-app-planning","target":"_self","description":"효과적인 학습 앱을 기획합니다.","details":["사용자 분석","기능 정의","UI/UX 설계","프로토타입"]}', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'development', '기능 개발', 5, 'external_link', '{"url":"#dev-feature-development","target":"_self","description":"학습 앱의 핵심 기능을 개발합니다.","details":["기능 구현","API 연동","데이터베이스 설계","테스트"]}', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'development', '사용자 테스트', 6, 'external_link', '{"url":"#dev-user-testing","target":"_self","description":"실제 사용자를 대상으로 테스트를 진행합니다.","details":["테스트 계획","사용자 모집","피드백 수집","개선 사항 도출"]}', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@user_id, 'development', '앱 배포 및 관리', 7, 'external_link', '{"url":"#dev-app-deployment","target":"_self","description":"개발된 앱을 배포하고 지속적으로 관리합니다.","details":["스토어 등록","버전 관리","사용자 지원","업데이트 계획"]}', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 8. 바이럴 마케팅 (viral) 카드들은 특별한 iframe 인터페이스를 사용하므로 별도 처리

-- 9. 상담 (consultation) 카드들은 getConsultationData() 함수에서 동적으로 생성되므로 별도 처리