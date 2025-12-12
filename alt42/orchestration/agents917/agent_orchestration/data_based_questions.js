// 나머지 에이전트들(Agent 01~22)의 상세 질문 세트
// questions.html에서 이 파일을 로드하여 사용

// Agent 01~22의 상세 질문 세트를 기존 dataBasedQuestionSets 객체에 병합
(function() {
    if (!window.dataBasedQuestionSets) {
        window.dataBasedQuestionSets = {};
    }
    
    // Agent 01: 온보딩 (Onboarding)
    window.dataBasedQuestionSets.agent01 = {
        1: { // 포괄형 질문 1: 첫 수업 시작 전략
            questionSets: [
                {
                    title: '학생의 수학 학습 맥락 종합 분석',
                    questions: [
                        { text: '학생의 온보딩 정보(학년, 학교, 학원 정보)를 기반으로 첫 수업의 적절한 난이도와 진도는?', dataSources: ['agent_data.agent01_data.student_grade', 'agent_data.agent01_data.school_name', 'agent_data.agent01_data.academy_name', 'agent_data.agent01_data.academy_grade', 'agent_data.agent01_data.onboarding_info'] },
                        { text: '학생의 개념/심화 진도 상태를 고려하여 첫 수업에서 다뤄야 할 단원과 내용 범위는?', dataSources: ['agent_data.agent01_data.concept_progress', 'agent_data.agent01_data.advanced_progress', 'agent_data.agent01_data.math_unit_mastery', 'agent_data.agent01_data.current_progress_position'] },
                        { text: '학생의 수학 학습 스타일(계산형/개념형/응용형)에 맞는 첫 수업 설명 전략과 자료 유형은?', dataSources: ['agent_data.agent01_data.math_learning_style', 'agent_data.agent01_data.study_style', 'agent_data.agent01_data.learning_style'] },
                        { text: '학생의 시험 대비 성향과 자신감 수준을 반영한 첫 수업 도입 루틴과 상호작용 방식은?', dataSources: ['agent_data.agent01_data.exam_style', 'agent_data.agent01_data.math_confidence', 'agent_data.agent01_data.confidence_level', 'agent_data.agent01_data.math_stress_level'] }
                    ]
                },
                {
                    title: '수업 도입 전략 및 자료 선택',
                    questions: [
                        { text: '학생의 수학 수준과 학습 스타일을 종합하여 첫 수업에서 사용할 교재와 문제 유형은?', dataSources: ['agent_data.agent01_data.math_level', 'agent_data.agent01_data.textbooks', 'agent_data.agent01_data.academy_textbook', 'agent_data.agent01_data.math_learning_style'] },
                        { text: '학생의 자신감 수준에 맞는 첫 수업 문제 난이도와 피드백 톤은?', dataSources: ['agent_data.agent01_data.math_confidence', 'agent_data.agent01_data.confidence_level', 'agent_data.agent01_data.low_math_confidence', 'agent_data.agent01_data.high_math_confidence'] },
                        { text: '학생의 학원 진도와 학교 진도를 고려한 첫 수업 내용 정렬 전략은?', dataSources: ['agent_data.agent01_data.academy_progress', 'agent_data.agent01_data.concept_progress', 'agent_data.agent01_data.curriculum_alignment', 'agent_data.agent01_data.academy_school_home_alignment'] }
                    ]
                }
            ],
            ontology: [
                { name: 'OnboardingContext', description: '온보딩 정보와 학습 맥락을 온톨로지로 표현 (Agent 01 핵심 온톨로지)' },
                { name: 'FirstClassStrategy', description: '첫 수업 시작 전략을 온톨로지로 표현' },
                { name: 'LearningContextIntegration', description: '학생의 학습 맥락(진도, 스타일, 자신감) 통합 분석을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 학생의 온보딩 정보, 진도, 학습 스타일, 자신감을 종합하여 첫 수업 시작 전략을 도출합니다. rules.yaml의 S0_R1~S0_R6, S1_R1~S1_R3 룰과 직접 연계됩니다.',
                answerAnalysis: '온보딩 정보는 S0_R2 룰이 수집하고, 수학 학습 스타일은 S0_R1 룰이 분석합니다. 진도 정보는 S0_R5 룰이 평가하고, 자신감 수준은 S1_R2 룰이 반영합니다.',
                ruleBasedActions: 'rules.yaml의 S0_R1~S0_R6 룰이 수학 특화 정보를 수집하고, S1_R1~S1_R3 룰이 첫 수업 준비 가이드를 생성합니다.'
            }
        },
        2: { // 포괄형 질문 2: 커리큘럼과 루틴 최적화
            questionSets: [
                {
                    title: '학생 성향과 목표 기반 커리큘럼 설계',
                    questions: [
                        { text: '학생의 단기/중기/장기 목표를 분석하여 커리큘럼의 진도별 우선순위는?', dataSources: ['agent_data.agent03_data.short_term_goal', 'agent_data.agent03_data.mid_term_goal', 'agent_data.agent03_data.long_term_goal', 'agent_data.agent03_data.goal_analysis', 'agent_data.agent03_data.goal_setting'] },
                        { text: '학생의 학습 성향(개념 정리 위주/문제풀이 위주)에 맞는 학습 흐름과 문제 유형 비중은?', dataSources: ['agent_data.agent01_data.study_style', 'agent_data.agent01_data.learning_style', 'agent_data.agent01_data.math_learning_style', 'agent_data.agent01_data.concept_progress', 'agent_data.agent01_data.advanced_progress'] },
                        { text: '학생의 스트레스 수준과 자신감을 고려한 커리큘럼 난이도 조절 전략은?', dataSources: ['agent_data.agent01_data.math_stress_level', 'agent_data.agent01_data.math_confidence', 'agent_data.agent05_data.emotion_score', 'agent_data.agent05_data.stress_level'] }
                    ]
                },
                {
                    title: '부모 개입도와 학습 환경 고려',
                    questions: [
                        { text: '학생의 부모 개입도와 스타일을 반영한 커리큘럼 공유 및 피드백 방식은?', dataSources: ['agent_data.agent01_data.parent_style', 'agent_data.agent01_data.parent_involvement', 'agent_data.agent01_data.parent_student_relationship', 'agent_data.agent01_data.parent_notification'] },
                        { text: '학생의 학습 시간과 환경을 고려한 루틴 설계와 시간 배분은?', dataSources: ['agent_data.agent01_data.study_hours_per_week', 'agent_data.agent01_data.learning_environment', 'agent_data.agent01_data.personal_study_space', 'agent_data.agent09_data.routine_design'] }
                    ]
                },
                {
                    title: '커스터마이징 설계 요소',
                    questions: [
                        { text: '학생의 목표와 성향을 종합하여 문제 유형 비중(개념:유형:심화:기출)을 어떻게 조절할까?', dataSources: ['agent_data.agent03_data.goal_analysis', 'agent_data.agent01_data.study_style', 'agent_data.agent01_data.math_learning_style', 'agent_data.agent01_data.concept_progress', 'agent_data.agent01_data.advanced_progress'] },
                        { text: '학생의 학습 리듬과 피로 패턴을 고려한 루틴 유지 전략은?', dataSources: ['agent_data.agent05_data.emotion_curve', 'agent_data.agent05_data.fatigue_pattern', 'agent_data.agent12_data.rest_routine', 'agent_data.agent18_data.signature_routine'] }
                    ]
                }
            ],
            ontology: [
                { name: 'CurriculumOptimization', description: '학생 성향과 목표 기반 커리큘럼 최적화를 온톨로지로 표현 (Agent 01 핵심 온톨로지)' },
                { name: 'RoutineCustomization', description: '학생 특성에 맞는 루틴 커스터마이징을 온톨로지로 표현' },
                { name: 'GoalBasedCurriculum', description: '목표 기반 커리큘럼 설계를 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 학생의 목표, 학습 성향, 스트레스/자신감, 부모 개입도를 종합하여 커리큘럼과 루틴을 최적화합니다. rules.yaml의 S2, S3 시나리오와 Agent 03, Agent 09, Agent 18과 연계됩니다.',
                answerAnalysis: '목표 분석은 Agent 03의 goal_analysis를 활용하고, 학습 성향은 S0_R1 룰이 분석합니다. 스트레스/자신감은 Agent 05의 emotion 데이터를 활용하며, 부모 개입도는 S0 시나리오에서 수집합니다.',
                ruleBasedActions: 'rules.yaml의 S2, S3 룰이 커리큘럼 최적화를 수행하고, Agent 09의 routine_design과 Agent 18의 signature_routine이 연계됩니다.'
            }
        },
        3: { // 포괄형 질문 3: 중장기 성장 전략
            questionSets: [
                {
                    title: '경시 준비 및 진학 목표 분석',
                    questions: [
                        { text: '학생의 경시 대회 준비 목표와 현재 수준을 비교하여 필요한 준비 기간과 전략은?', dataSources: ['agent_data.agent03_data.long_term_goal', 'agent_data.agent01_data.math_level', 'agent_data.agent01_data.advanced_progress', 'agent_data.agent01_data.math_confidence', 'agent_data.agent01_data.study_hours_per_week'] },
                        { text: '학생의 진학 목표와 수학 성적 목표를 달성하기 위한 중장기 학습 로드맵은?', dataSources: ['agent_data.agent03_data.long_term_goal', 'agent_data.agent01_data.math_recent_score', 'agent_data.agent01_data.academic_level', 'agent_data.agent01_data.target_score'] }
                    ]
                },
                {
                    title: '수학 자존감 성장 및 피로 관리',
                    questions: [
                        { text: '학생의 현재 수학 자존감 수준과 성장 패턴을 분석하여 자존감 향상 전략은?', dataSources: ['agent_data.agent01_data.math_confidence', 'agent_data.agent01_data.confidence_level', 'agent_data.agent05_data.emotion_score', 'agent_data.agent05_data.math_confidence_growth'] },
                        { text: '학생의 피로 누적 패턴을 분석하여 장기적으로 루틴 유지를 방해하는 요인은?', dataSources: ['agent_data.agent05_data.fatigue_pattern', 'agent_data.agent05_data.recovery_time_avg', 'agent_data.agent12_data.rest_routine', 'agent_data.agent13_data.learning_dropout_risk'] }
                    ]
                },
                {
                    title: '조기 리스크 예측 및 트래킹',
                    questions: [
                        { text: '학생의 학습 패턴과 목표를 종합하여 중장기적으로 발생 가능한 리스크 요소는?', dataSources: ['agent_data.agent01_data.study_style', 'agent_data.agent03_data.goal_feasibility', 'agent_data.agent05_data.emotion_stability', 'agent_data.agent13_data.dropout_pattern', 'agent_data.agent13_data.risk_level'] },
                        { text: '학생의 루틴 유지 여부를 모니터링하고 지속 가능성을 평가하는 지표는?', dataSources: ['agent_data.agent09_data.routine_maintenance', 'agent_data.agent18_data.routine_stability', 'agent_data.agent05_data.routine_consistency', 'agent_data.agent12_data.rest_pattern'] },
                        { text: '학생의 중장기 성장을 위해 지금부터 특히 주의해야 할 트래킹 우선요소는?', dataSources: ['agent_data.agent01_data.math_confidence', 'agent_data.agent05_data.emotion_stability', 'agent_data.agent09_data.learning_consistency', 'agent_data.agent13_data.early_warning_signals'] }
                    ]
                }
            ],
            ontology: [
                { name: 'LongTermGrowthStrategy', description: '중장기 성장 전략을 온톨로지로 표현 (Agent 01 핵심 온톨로지)' },
                { name: 'RiskPrediction', description: '조기 리스크 예측을 온톨로지로 표현' },
                { name: 'RoutineSustainability', description: '루틴 유지 지속성을 온톨로지로 표현' },
                { name: 'MathConfidenceGrowth', description: '수학 자존감 성장 패턴을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 경시 준비, 진학 목표, 수학 자존감 성장, 피로 누적 방지, 루틴 유지 여부를 포괄적으로 분석하여 조기 리스크 예측 및 트래킹 우선요소를 추천합니다. rules.yaml의 S3 시나리오와 Agent 03, Agent 05, Agent 09, Agent 12, Agent 13, Agent 18과 연계됩니다.',
                answerAnalysis: '경시/진학 목표는 Agent 03의 long_term_goal을 활용하고, 수학 자존감은 Agent 01의 math_confidence와 Agent 05의 emotion 데이터를 활용합니다. 피로 패턴은 Agent 05와 Agent 12가 분석하며, 루틴 유지는 Agent 09와 Agent 18이 모니터링합니다.',
                ruleBasedActions: 'rules.yaml의 S3 룰이 중장기 성장 전략을 수립하고, Agent 13의 risk_prediction과 Agent 18의 routine_sustainability가 연계됩니다.'
            }
        }
    };
    
    // Agent 02: 시험일정 (Exam Schedule)
    window.dataBasedQuestionSets.agent02 = {
        1: { // 포괄형 질문 1: 학원·학교·집 학습 정렬
            questionSets: [
                {
                    title: '학원·학교·집 학습 진도 격차 분석',
                    questions: [
                        { text: '학원 진도와 학교 시험 범위 간 진도 격차는 얼마나 되나요?', dataSources: ['agent_data.agent02_data.academy_progress', 'agent_data.agent02_data.school_exam_scope', 'agent_data.agent02_data.progress_gap', 'agent_data.agent02_data.academy_school_progress_difference'] },
                        { text: '학원 과제 소요 시간과 피로도가 집 공부 루틴에 미치는 영향은?', dataSources: ['agent_data.agent02_data.academy_assignment_time', 'agent_data.agent02_data.academy_assignment_fatigue', 'agent_data.agent02_data.home_study_routine', 'agent_data.agent05_data.fatigue_pattern'] },
                        { text: '시험 D-day별로 사용 가능한 시간 자원과 학원·학교·집 학습 시간 배분은?', dataSources: ['agent_data.agent02_data.exam_d_day', 'agent_data.agent02_data.available_time_by_d_day', 'agent_data.agent02_data.academy_time', 'agent_data.agent02_data.school_time', 'agent_data.agent02_data.home_time'] },
                        { text: '학원 과제와 학교 과제, 교재 간 중복 구간은 어디인가요?', dataSources: ['agent_data.agent02_data.academy_assignment_overlap', 'agent_data.agent02_data.school_assignment_overlap', 'agent_data.agent02_data.textbook_overlap', 'agent_data.agent02_data.duplicate_sections'] }
                    ]
                },
                {
                    title: '3축 정렬 플랜 및 시간 배분 리셋',
                    questions: [
                        { text: '학원-학교-집 3축 정렬을 위한 우선순위와 조정 전략은?', dataSources: ['agent_data.agent02_data.academy_school_home_alignment', 'agent_data.agent02_data.alignment_priority', 'agent_data.agent02_data.alignment_strategy'] },
                        { text: '시간 배분 리셋 루틴을 설계하기 위한 현재 시간 사용 패턴은?', dataSources: ['agent_data.agent02_data.current_time_allocation', 'agent_data.agent02_data.time_allocation_pattern', 'agent_data.agent09_data.routine_design'] },
                        { text: '학원 수업 일정과 학교 시험 일정을 고려한 최적 학습 시간표는?', dataSources: ['agent_data.agent02_data.academy_schedule', 'agent_data.agent02_data.school_exam_schedule', 'agent_data.agent02_data.optimal_schedule'] }
                    ]
                }
            ],
            ontology: [
                { name: 'AcademySchoolHomeAlignment', description: '학원-학교-집 학습 정렬을 온톨로지로 표현 (Agent 02 핵심 온톨로지)' },
                { name: 'ProgressGapAnalysis', description: '진도 격차 분석을 온톨로지로 표현' },
                { name: 'TimeResourceAllocation', description: '시험 D-day별 시간 자원 배분을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 학원 진도, 학교 시험 범위, 집 공부 루틴을 동시에 매칭하여 진도 격차, 과제 소요 시간, 피로도, 시험 D-day별 시간 자원을 분석합니다. rules.yaml의 S0_R1~S0_R4, S1~S8 룰과 직접 연계됩니다.',
                answerAnalysis: '학원 진도는 S0_R4 룰이 수집하고, 학교 시험 범위는 S0_R3 룰이 분석합니다. 진도 격차는 S1 룰이 평가하고, 시간 배분은 S2~S8 룰이 최적화합니다.',
                ruleBasedActions: 'rules.yaml의 S0_R1~S0_R4 룰이 시험 계획 수립을 위한 필수 정보를 수집하고, S1~S8 룰이 D-day별 학습 전략을 수립합니다.'
            }
        },
        2: { // 포괄형 질문 2: 시험 8주 루프 점수 상승 잠재력 극대화
            questionSets: [
                {
                    title: '목표 점수 및 단원별 정답률 분석',
                    questions: [
                        { text: '학생의 목표 점수와 현재 예상 점수 간 차이는 얼마나 되나요?', dataSources: ['agent_data.agent02_data.target_score', 'agent_data.agent02_data.current_expected_score', 'agent_data.agent03_data.goal_analysis', 'agent_data.agent02_data.score_gap'] },
                        { text: '단원별 정답률을 분석하여 시간 대비 효율이 높은 단원은?', dataSources: ['agent_data.agent02_data.unit_accuracy_rate', 'agent_data.agent02_data.unit_by_unit_accuracy', 'agent_data.agent04_data.weakpoint_analysis', 'agent_data.agent02_data.time_efficiency_by_unit'] },
                        { text: '교재별 진도율과 학원 피드백 데이터를 종합한 학습 효율은?', dataSources: ['agent_data.agent02_data.textbook_progress_rate', 'agent_data.agent02_data.academy_feedback_data', 'agent_data.agent06_data.teacher_feedback', 'agent_data.agent02_data.learning_efficiency'] }
                    ]
                },
                {
                    title: '학습 전략 비율 재조정 및 단원 우선순위',
                    questions: [
                        { text: '개념:유형:심화:기출 비율을 어떻게 재조정하면 점수 상승 잠재력이 극대화될까요?', dataSources: ['agent_data.agent02_data.concept_ratio', 'agent_data.agent02_data.type_ratio', 'agent_data.agent02_data.advanced_ratio', 'agent_data.agent02_data.past_exam_ratio', 'agent_data.agent02_data.current_strategy_ratio'] },
                        { text: '시간 대비 효율 가중치를 고려한 단원 우선순위는?', dataSources: ['agent_data.agent02_data.unit_priority_by_efficiency', 'agent_data.agent02_data.time_weighted_efficiency', 'agent_data.agent02_data.unit_priority'] },
                        { text: '학원 교재별 활용 전략(쎈/RPM/블랙라벨 등)을 어떻게 조정할까요?', dataSources: ['agent_data.agent02_data.academy_textbook_strategy', 'agent_data.agent02_data.textbook_utilization_strategy', 'agent_data.agent01_data.academy_textbook', 'agent_data.agent02_data.textbook_effectiveness'] }
                    ]
                },
                {
                    title: '단기 vs 중기 루틴 배치',
                    questions: [
                        { text: '시험 8주 루프 안에서 단기 루틴(1~2주)과 중기 루틴(3~8주)을 어떻게 배치할까요?', dataSources: ['agent_data.agent02_data.exam_8week_loop', 'agent_data.agent02_data.short_term_routine', 'agent_data.agent02_data.mid_term_routine', 'agent_data.agent09_data.routine_design', 'agent_data.agent18_data.signature_routine'] },
                        { text: '시험 D-day에 따른 루틴 전환 시점과 전략 변경 타이밍은?', dataSources: ['agent_data.agent02_data.exam_d_day', 'agent_data.agent02_data.routine_transition_point', 'agent_data.agent02_data.strategy_change_timing', 'agent_data.agent02_data.d_day_based_routine'] }
                    ]
                }
            ],
            ontology: [
                { name: 'ScoreImprovementPotential', description: '시험 8주 루프 안에서 점수 상승 잠재력을 온톨로지로 표현 (Agent 02 핵심 온톨로지)' },
                { name: 'StrategyRatioOptimization', description: '학습 전략 비율(개념:유형:심화:기출) 최적화를 온톨로지로 표현' },
                { name: 'UnitPriorityByEfficiency', description: '시간 대비 효율 가중치 기반 단원 우선순위를 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 목표 점수, 단원별 정답률, 교재별 진도율, 학원 피드백 데이터를 종합하여 학습 전략 비율을 재조정하고 단원 우선순위를 결정합니다. rules.yaml의 S3~S8 룰과 Agent 03, Agent 04, Agent 06, Agent 09, Agent 18과 연계됩니다.',
                answerAnalysis: '목표 점수는 Agent 03의 goal_analysis를 활용하고, 단원별 정답률은 Agent 04의 weakpoint_analysis를 활용합니다. 교재별 진도율은 S0_R4 룰이 수집하며, 학습 전략 비율은 S3~S8 룰이 최적화합니다.',
                ruleBasedActions: 'rules.yaml의 S3~S8 룰이 D-day별 학습 전략을 수립하고, 단원 우선순위와 학습 비율을 조정합니다.'
            }
        },
        3: { // 포괄형 질문 3: 시험 종료 후 다음 시험 주기 개선 패턴
            questionSets: [
                {
                    title: '학원·학교 데이터 종합 평가',
                    questions: [
                        { text: '학원 등수와 학교 성적을 비교하여 일관성과 격차는?', dataSources: ['agent_data.agent02_data.academy_rank', 'agent_data.agent02_data.school_score', 'agent_data.agent02_data.rank_score_consistency', 'agent_data.agent02_data.academy_school_gap'] },
                        { text: '문항 유형별 성공률을 분석하여 강점과 약점 유형은?', dataSources: ['agent_data.agent02_data.question_type_success_rate', 'agent_data.agent02_data.question_type_analysis', 'agent_data.agent04_data.error_pattern', 'agent_data.agent02_data.strength_weakness_by_type'] },
                        { text: '학원 교재 효과 지표(문제 커버리지 vs 시험 적중률)는?', dataSources: ['agent_data.agent02_data.textbook_coverage', 'agent_data.agent02_data.exam_hit_rate', 'agent_data.agent02_data.textbook_effectiveness_index', 'agent_data.agent02_data.coverage_vs_hit_rate'] }
                    ]
                },
                {
                    title: '반복 실수 패턴 및 루틴 유지율 분석',
                    questions: [
                        { text: '반복 실수 패턴에서 계산 오류와 개념 오류의 비율은?', dataSources: ['agent_data.agent02_data.repeated_error_pattern', 'agent_data.agent02_data.calculation_error_ratio', 'agent_data.agent02_data.concept_error_ratio', 'agent_data.agent04_data.error_category'] },
                        { text: '학습 루틴 유지율 변동 패턴과 루틴 붕괴 시점은?', dataSources: ['agent_data.agent02_data.routine_maintenance_rate', 'agent_data.agent02_data.routine_maintenance_variation', 'agent_data.agent09_data.routine_maintenance', 'agent_data.agent13_data.routine_collapse_pattern'] },
                        { text: '학부모·학원 피드백 반영률과 효과성은?', dataSources: ['agent_data.agent02_data.parent_feedback_reflection_rate', 'agent_data.agent02_data.academy_feedback_reflection_rate', 'agent_data.agent06_data.teacher_feedback', 'agent_data.agent02_data.feedback_effectiveness'] }
                    ]
                },
                {
                    title: '다음 시험 루프 초기화 전략',
                    questions: [
                        { text: '다음 시험 루프 초기화를 위한 핵심 개선 포인트는?', dataSources: ['agent_data.agent02_data.improvement_points', 'agent_data.agent02_data.next_exam_loop_init', 'agent_data.agent02_data.core_improvement_areas'] },
                        { text: '보완이 필요한 단원 리스트와 우선순위는?', dataSources: ['agent_data.agent02_data.reinforcement_unit_list', 'agent_data.agent02_data.unit_reinforcement_priority', 'agent_data.agent04_data.weakpoint_analysis'] },
                        { text: '다음 시험 주기에서 유지할 전략과 변경할 전략은?', dataSources: ['agent_data.agent02_data.strategy_to_maintain', 'agent_data.agent02_data.strategy_to_change', 'agent_data.agent02_data.next_cycle_strategy'] }
                    ]
                }
            ],
            ontology: [
                { name: 'ExamCycleImprovement', description: '시험 주기 개선 패턴을 온톨로지로 표현 (Agent 02 핵심 온톨로지)' },
                { name: 'TextbookEffectivenessAnalysis', description: '학원 교재 효과 지표 분석을 온톨로지로 표현' },
                { name: 'RoutineMaintenancePattern', description: '학습 루틴 유지율 변동 패턴을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 학원 등수, 학교 성적, 문항 유형별 성공률, 학원 교재 효과 지표를 결합하여 종합 평가하고 다음 시험 루프 초기화 전략을 생성합니다. rules.yaml의 S8 룰과 Agent 04, Agent 06, Agent 09, Agent 13과 연계됩니다.',
                answerAnalysis: '학원 등수와 학교 성적은 S8 룰이 분석하고, 문항 유형별 성공률은 Agent 04의 error_pattern을 활용합니다. 교재 효과 지표는 S8 룰이 평가하며, 루틴 유지율은 Agent 09와 Agent 13이 모니터링합니다.',
                ruleBasedActions: 'rules.yaml의 S8 룰이 시험 종료 후 평가를 수행하고, 다음 시험 루프 초기화 전략을 수립합니다.'
            }
        }
    };
    
    // Agent 03: 목표분석 (Goals Analysis)
    window.dataBasedQuestionSets.agent03 = {
        1: { // 포괄형 질문 1: 목표와 계획의 유기적 작동 분석
            questionSets: [
                {
                    title: '목표 간 연결성 및 계획 현실성 분석',
                    questions: [
                        { text: '분기·주간·오늘 목표 간 연결성이 얼마나 강한가요?', dataSources: ['agent_data.agent03_data.quarterly_goal', 'agent_data.agent03_data.weekly_goal', 'agent_data.agent03_data.daily_goal', 'agent_data.agent03_data.goal_connection_strength', 'agent_data.agent03_data.goal_alignment'] },
                        { text: '현재 계획의 현실성 점수는 얼마나 되나요?', dataSources: ['agent_data.agent03_data.goal_reality_score', 'agent_data.agent03_data.planning_method_score', 'agent_data.agent03_data.plan_reality_assessment'] },
                        { text: '일정 변동 대응력과 계획 수정 빈도는?', dataSources: ['agent_data.agent03_data.schedule_variability', 'agent_data.agent03_data.schedule_change_response', 'agent_data.agent03_data.plan_adjustment_frequency', 'agent_data.agent03_data.exam_schedule_reflected'] },
                        { text: '회복탄력성 지표(목표 달성 실패 후 재시도율)는?', dataSources: ['agent_data.agent03_data.resilience_index', 'agent_data.agent03_data.goal_achievement_failure_rate', 'agent_data.agent03_data.retry_rate_after_failure', 'agent_data.agent03_data.recovery_pattern'] }
                    ]
                },
                {
                    title: '실제 루틴 궤도 진단 및 구조적 재설계',
                    questions: [
                        { text: '실제 루틴이 목표 궤도 안에서 작동 중인지 진단 지표는?', dataSources: ['agent_data.agent03_data.weekly_completion_rate', 'agent_data.agent03_data.routine_orbit_status', 'agent_data.agent03_data.goal_plan_mismatch', 'agent_data.agent09_data.routine_maintenance'] },
                        { text: '목표-계획 불일치가 발생한 지점과 원인은?', dataSources: ['agent_data.agent03_data.execution_breakpoints', 'agent_data.agent03_data.goal_plan_mismatch_diagnosis', 'agent_data.agent03_data.execution_flow_interruption'] },
                        { text: '구조적 재설계가 필요한 영역과 우선순위는?', dataSources: ['agent_data.agent03_data.structural_redesign_needed', 'agent_data.agent03_data.redesign_priority', 'agent_data.agent03_data.goal_or_plan_adjustment_decision'] }
                    ]
                }
            ],
            ontology: [
                { name: 'GoalPlanOrganicOperation', description: '목표와 계획의 유기적 작동을 온톨로지로 표현 (Agent 03 핵심 온톨로지)' },
                { name: 'GoalConnectionStrength', description: '분기·주간·오늘 목표 간 연결성을 온톨로지로 표현' },
                { name: 'ResilienceIndex', description: '회복탄력성 지표를 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 분기·주간·오늘 목표 간 연결성, 계획의 현실성, 일정 변동 대응력, 회복탄력성 지표를 함께 분석하여 실제 루틴이 궤도 안에서 작동 중인지 진단합니다. rules.yaml의 R1a~R1d 룰과 직접 연계됩니다.',
                answerAnalysis: '목표 간 연결성은 R1a 룰이 분석하고, 일정 변동 대응력은 R1b 룰이 평가합니다. 실행 흐름 단절은 R1c 룰이 식별하며, 목표-계획 조정 결정은 R1d 룰이 수행합니다.',
                ruleBasedActions: 'rules.yaml의 R1a~R1d 룰이 목표-계획 불일치를 감지하고, 구조적 재설계를 제안합니다.'
            }
        },
        2: { // 포괄형 질문 2: 학습 궤도 안정성 분석
            questionSets: [
                {
                    title: '장기 커리큘럼과 목표 궤적 정합성',
                    questions: [
                        { text: '분기목표와 장기 커리큘럼의 정합성 점수는?', dataSources: ['agent_data.agent03_data.quarterly_goal', 'agent_data.agent03_data.long_term_curriculum', 'agent_data.agent03_data.curriculum_goal_alignment', 'agent_data.agent03_data.goal_curriculum_consistency'] },
                        { text: '시험일정 반영률과 목표 조정 빈도는?', dataSources: ['agent_data.agent03_data.exam_schedule_reflection_rate', 'agent_data.agent03_data.exam_schedule_reflected', 'agent_data.agent02_data.exam_schedule', 'agent_data.agent03_data.goal_adjustment_frequency'] },
                        { text: '진학 목표와의 궤도 일치 정도는?', dataSources: ['agent_data.agent03_data.long_term_goal', 'agent_data.agent03_data.career_goal', 'agent_data.agent03_data.orbit_alignment_with_career', 'agent_data.agent03_data.goal_trajectory_match'] }
                    ]
                },
                {
                    title: '궤도 이탈·지연·과속 진단',
                    questions: [
                        { text: '학습 궤도에서 이탈·지연·과속 여부를 판단하는 지표는?', dataSources: ['agent_data.agent03_data.orbit_deviation', 'agent_data.agent03_data.orbit_delay', 'agent_data.agent03_data.orbit_overspeed', 'agent_data.agent03_data.learning_orbit_status'] },
                        { text: '궤도 이탈 원인과 발생 시점은?', dataSources: ['agent_data.agent03_data.orbit_deviation_cause', 'agent_data.agent03_data.orbit_deviation_timing', 'agent_data.agent03_data.goal_deviation'] },
                        { text: '커리큘럼·루틴·시간배분을 어떻게 조정해야 궤도 재진입이 가능한가요?', dataSources: ['agent_data.agent03_data.curriculum_adjustment', 'agent_data.agent03_data.routine_adjustment', 'agent_data.agent03_data.time_allocation_adjustment', 'agent_data.agent03_data.orbit_reentry_strategy', 'agent_data.agent09_data.routine_design'] }
                    ]
                }
            ],
            ontology: [
                { name: 'LearningOrbitStability', description: '학습 궤도 안정성을 온톨로지로 표현 (Agent 03 핵심 온톨로지)' },
                { name: 'CurriculumGoalAlignment', description: '장기 커리큘럼과 목표 궤적 정합성을 온톨로지로 표현' },
                { name: 'OrbitReentryStrategy', description: '궤도 재진입 전략을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 분기목표와 장기 커리큘럼의 정합성, 시험일정 반영률, 진학 목표와의 궤도 일치 정도를 통합 분석하여 이탈·지연·과속 여부를 판단합니다. rules.yaml의 R3a~R3d 룰과 Agent 02, Agent 09와 연계됩니다.',
                answerAnalysis: '커리큘럼-목표 정합성은 R3a 룰이 분석하고, 시험일정 반영률은 R3b 룰이 평가합니다. 궤도 이탈 진단은 R3c 룰이 수행하며, 궤도 재진입 전략은 R3d 룰이 제안합니다.',
                ruleBasedActions: 'rules.yaml의 R3a~R3d 룰이 학습 궤도 안정성을 분석하고, 궤도 재진입 전략을 수립합니다.'
            }
        },
        3: { // 포괄형 질문 3: 목표 설계 구조의 회복 가능성
            questionSets: [
                {
                    title: '목표 설계의 단계성 및 탄력성 평가',
                    questions: [
                        { text: '목표 설계의 단계성(단기→중기→장기 연결) 수준은?', dataSources: ['agent_data.agent03_data.goal_staging_level', 'agent_data.agent03_data.short_term_goal', 'agent_data.agent03_data.mid_term_goal', 'agent_data.agent03_data.long_term_goal', 'agent_data.agent03_data.goal_segmentation'] },
                        { text: '목표 설계의 탄력성(변동 상황 대응 능력) 점수는?', dataSources: ['agent_data.agent03_data.goal_elasticity', 'agent_data.agent03_data.goal_flexibility', 'agent_data.agent03_data.variability_response_capacity'] },
                        { text: '일정 변화 대응 전략의 효과성은?', dataSources: ['agent_data.agent03_data.schedule_change_response_strategy', 'agent_data.agent03_data.strategy_effectiveness', 'agent_data.agent03_data.adaptation_success_rate'] }
                    ]
                },
                {
                    title: '감정 기반 회복 패턴 및 교사 피드백 반영',
                    questions: [
                        { text: '목표 달성 실패 후 감정 기반 회복 패턴은?', dataSources: ['agent_data.agent03_data.emotion_based_recovery_pattern', 'agent_data.agent05_data.emotion_score', 'agent_data.agent05_data.recovery_time_avg', 'agent_data.agent03_data.goal_failure_recovery'] },
                        { text: '교사 피드백 반영률과 목표 수정 빈도는?', dataSources: ['agent_data.agent03_data.teacher_feedback_reflection_rate', 'agent_data.agent06_data.teacher_feedback', 'agent_data.agent03_data.goal_revision_frequency', 'agent_data.agent03_data.feedback_integration'] },
                        { text: '목표 시스템이 "무너지지 않고 다시 일어서는 구조"로 되어 있는지 평가 지표는?', dataSources: ['agent_data.agent03_data.goal_system_resilience', 'agent_data.agent03_data.collapse_resistance', 'agent_data.agent03_data.recovery_capability', 'agent_data.agent03_data.goal_system_stability'] }
                    ]
                },
                {
                    title: '교사 노하우 연계 회복 전략 자동생성',
                    questions: [
                        { text: '교사 노하우와 연계된 회복 전략 자동생성 로직은?', dataSources: ['agent_data.agent03_data.teacher_knowhow_integration', 'agent_data.agent06_data.teacher_feedback', 'agent_data.agent03_data.recovery_strategy_auto_generation', 'agent_data.agent03_data.knowhow_based_recovery'] },
                        { text: '과거 성공한 회복 패턴과 실패한 패턴의 차이는?', dataSources: ['agent_data.agent03_data.successful_recovery_pattern', 'agent_data.agent03_data.failed_recovery_pattern', 'agent_data.agent03_data.recovery_pattern_comparison'] }
                    ]
                }
            ],
            ontology: [
                { name: 'GoalDesignResilience', description: '목표 설계 구조의 회복 가능성을 온톨로지로 표현 (Agent 03 핵심 온톨로지)' },
                { name: 'GoalStagingElasticity', description: '목표 설계의 단계성과 탄력성을 온톨로지로 표현' },
                { name: 'TeacherKnowhowRecovery', description: '교사 노하우 연계 회복 전략을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 목표 설계의 단계성·탄력성, 일정 변화 대응 전략, 감정 기반 회복 패턴, 교사 피드백 반영률을 분석하여 목표 시스템이 "무너지지 않고 다시 일어서는 구조"로 되어 있는지를 평가합니다. rules.yaml의 R4a~R4d 룰과 Agent 05, Agent 06과 연계됩니다.',
                answerAnalysis: '목표 설계의 단계성은 R4a 룰이 평가하고, 탄력성은 R4b 룰이 분석합니다. 감정 기반 회복 패턴은 R4c 룰이 분석하며, 교사 피드백 반영은 R4d 룰이 평가합니다.',
                ruleBasedActions: 'rules.yaml의 R4a~R4d 룰이 목표 설계 구조의 회복 가능성을 평가하고, 교사 노하우 연계 회복 전략을 자동생성합니다.'
            }
        }
    };
    
    // Agent 04: 취약점검사 (8가지 활동 영역)
    window.dataBasedQuestionSets.agent04 = {
        1: { // ① 개념이해
            questionSets: [
                {
                    title: '개념이해 과정의 취약구간 탐지',
                    questions: [
                        { text: '학생은 개념이해 단계 중 어떤 부분(이해/정리/적용)에서 가장 자주 멈추나요?', dataSources: ['concept_stage', 'pause_frequency', 'pause_stage', 'concept_progress'] },
                        { text: '개념설명(TTS)을 듣는 동안 시선집중도나 필기패턴에 변화가 있나요?', dataSources: ['gaze_attention_score', 'note_taking_pattern_change', 'learning_method', 'activity_type'] },
                        { text: '개념이해 중 혼동이 자주 일어나는 개념쌍(예: 정의 vs 예시, 공식 vs 조건)은 무엇인가요?', dataSources: ['concept_confusion_detected', 'confusion_type', 'activity_type'] }
                    ]
                },
                {
                    title: '학습스타일과 개념공부 방식의 적합성 평가',
                    questions: [
                        { text: '학생의 개념공부 방식(TTS, 필기, 예제)이 현재 페르소나(예: 감각형/분석형)와 일치하나요?', dataSources: ['persona_type', 'current_method', 'method_persona_match_score', 'activity_type'] },
                        { text: '학생은 개념을 읽을 때 시각 자료(그림, 표, 색상)에 반응을 보이나요?', dataSources: ['visual_content_present', 'visual_response_score', 'activity_type'] },
                        { text: '개념을 이해할 때 "글로 정리"보다 "예제로 확인"하는 쪽에 더 반응하나요?', dataSources: ['text_organization_score', 'example_verification_score', 'activity_type'] }
                    ]
                },
                {
                    title: '몰입 유도 및 학습 조합 최적화',
                    questions: [
                        { text: '학생은 개념학습 중 어느 활동 조합(TTS, 필기, 예제풀이)에서 몰입도가 가장 높았나요?', dataSources: ['immersion_score_by_combination', 'best_combination', 'activity_type'] },
                        { text: '개념공부 도중 지루함 또는 집중 이탈이 발생한 시간대는 언제인가요?', dataSources: ['boredom_detected', 'attention_drop_time', 'emotion_state', 'activity_type'] },
                        { text: '개념 이해의 효율을 높이기 위해 어떤 형태의 피드백(TTS 코칭, 요약퀴즈, 실생활예시)이 가장 효과적일까요?', dataSources: ['feedback_types_tested', 'feedback_effectiveness_score', 'activity_type'] }
                    ]
                }
            ],
            ontology: [
                { name: 'ConceptWeakpoint', description: '개념 이해 취약점을 온톨로지로 표현 (Agent 04 핵심 온톨로지)' },
                { name: 'ConceptLearningStyle', description: '개념 학습 스타일과 페르소나 매칭을 온톨로지로 표현' },
                { name: 'ConceptImmersionPattern', description: '개념 학습 몰입 패턴과 활동 조합을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 개념이해 과정의 취약구간 탐지, 학습스타일 적합성 평가, 몰입 유도 최적화를 종합 분석합니다. rules.yaml의 CU_A1~CU_C3 룰과 직접 연계됩니다.',
                answerAnalysis: '개념이해 단계별 멈춤 지점은 CU_A1 룰이 분석하고, TTS 주의집중 패턴은 CU_A2 룰이 평가합니다. 개념쌍 혼동은 CU_A3 룰이 탐지합니다. 페르소나-방식 매칭은 CU_B1 룰이 평가하고, 몰입 활동 조합은 CU_C1 룰이 식별합니다.',
                ruleBasedActions: 'rules.yaml의 CU_A1~CU_A3 룰이 취약구간을 탐지하고, CU_B1~CU_B3 룰이 학습스타일 적합성을 평가하며, CU_C1~CU_C3 룰이 몰입 유도를 최적화합니다.'
            }
        },
        2: { // ② 유형학습
            questionSets: [
                {
                    title: '유형학습 루틴의 구조 및 효율 탐지',
                    questions: [
                        { text: '학생은 유형학습 과정에서 문제풀이 순서(기본 → 응용 → 심화)를 어떻게 조정하나요?', dataSources: ['problem_sequence', 'sequence_efficiency_score', 'activity_type'] },
                        { text: '유형별 난이도 변화에 따라 풀이속도나 집중도가 일정하게 유지되나요?', dataSources: ['difficulty_change', 'speed_consistency_score', 'focus_consistency_score', 'activity_type'] },
                        { text: '유형학습 세션 중 어느 단계(시작, 중간, 후반)에서 집중이 가장 오래 지속되나요?', dataSources: ['session_stage', 'focus_duration_by_stage', 'activity_type'] }
                    ]
                },
                {
                    title: '전략 선택 및 반복학습 태도 분석',
                    questions: [
                        { text: '학생은 유형문제를 풀 때 어떤 접근전략(공식회상형 / 유추형 / 비교형)을 가장 많이 사용하나요?', dataSources: ['approach_strategy', 'strategy_usage_frequency', 'activity_type'] },
                        { text: '유형문제 반복 풀이 시, 같은 오류가 반복되는 경향이 있나요?', dataSources: ['repeated_error_count', 'error_type', 'activity_type'] },
                        { text: '반복 시도 중 "포기"나 "지루함"의 패턴이 나타나는 시점은 언제인가요?', dataSources: ['repetition_count', 'giveup_or_boredom_detected', 'detection_timing', 'activity_type'] }
                    ]
                },
                {
                    title: '감정·몰입·동기 루프 최적화',
                    questions: [
                        { text: '유형학습 중 학생이 가장 높은 몰입감을 보인 활동은 무엇이었나요? (예: 대표유형 풀이, 서술평가, 보충문제 등)', dataSources: ['sub_activity_type', 'immersion_score_by_activity', 'activity_type'] },
                        { text: '문제 난이도 상승 시 불안·흥미·도전 중 어떤 감정이 가장 강하게 나타나나요?', dataSources: ['difficulty_increase', 'emotion_response', 'activity_type'] },
                        { text: '유형학습 후 피드백(TTS 코칭, 시각화 리포트, 교사 코멘트) 중 어떤 형태가 재도전에 가장 효과적이었나요?', dataSources: ['feedback_types', 'retry_effectiveness_score', 'activity_type'] }
                    ]
                }
            ],
            ontology: [
                { name: 'TypeLearningRoutine', description: '유형학습 루틴 구조와 효율을 온톨로지로 표현 (Agent 04 핵심 온톨로지)' },
                { name: 'TypeLearningStrategy', description: '유형학습 접근 전략과 반복 학습 태도를 온톨로지로 표현' },
                { name: 'TypeLearningEmotionLoop', description: '유형학습 감정-몰입-동기 루프를 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 유형학습 루틴 구조 분석, 전략 선택 태도 평가, 감정-몰입 루프 최적화를 종합 분석합니다. rules.yaml의 TL_A1~TL_C3 룰과 직접 연계됩니다.',
                answerAnalysis: '문제풀이 순서 조정은 TL_A1 룰이 분석하고, 난이도 변화 대응은 TL_A2 룰이 평가합니다. 접근 전략 분석은 TL_B1 룰이 수행하고, 반복 오류 패턴은 TL_B2 룰이 탐지합니다. 몰입 활동 식별은 TL_C1 룰이 수행합니다.',
                ruleBasedActions: 'rules.yaml의 TL_A1~TL_A3 룰이 루틴 구조를 분석하고, TL_B1~TL_B3 룰이 전략과 반복 학습을 평가하며, TL_C1~TL_C3 룰이 감정-몰입 루프를 최적화합니다.'
            }
        },
        3: { // ③ 문제풀이
            questionSets: [
                {
                    title: '사고 흐름 및 풀이 전략 분석',
                    questions: [
                        { text: '학생은 문제를 읽을 때 핵심 조건과 불필요한 정보를 어떻게 구분하나요?', dataSources: ['problem_reading_stage', 'key_condition_identification_score', 'activity_type'] },
                        { text: '풀이를 시작하기 전에 문제 전체를 구조적으로 파악하는 습관이 있나요?', dataSources: ['problem_reading_stage', 'structural_analysis_before_solving', 'activity_type'] },
                        { text: '풀이 중 막혔을 때 전략 전환(공식 → 그림, 역추론 등)을 스스로 시도하나요?', dataSources: ['solving_stage', 'stuck_detected', 'strategy_switch_attempted', 'activity_type'] }
                    ]
                },
                {
                    title: '인지부하와 감정 반응 탐지',
                    questions: [
                        { text: '문제풀이 도중 시선 이탈, 멈춤, 표정 변화가 자주 나타나는 구간은 어디인가요?', dataSources: ['gaze_detection', 'gaze_away_frequency', 'pause_frequency', 'activity_type'] },
                        { text: '풀이 과정에서 학생이 느끼는 긴장감·피로감·집중감 중 어떤 감정이 지배적이었나요?', dataSources: ['emotion_during_solving', 'emotion_intensity', 'activity_type'] },
                        { text: '풀이가 길어질수록 효율(시간 대비 정확도)이 유지되나요, 하락하나요?', dataSources: ['solving_duration', 'efficiency_trend', 'activity_type'] }
                    ]
                },
                {
                    title: '메타인지적 검토 및 자기조절 평가',
                    questions: [
                        { text: '문제를 푼 뒤 자신이 어디서 틀릴 수 있었는지를 스스로 설명할 수 있나요?', dataSources: ['solving_stage', 'self_explanation_score', 'activity_type'] },
                        { text: '풀이를 마친 후 답을 검토하는 루틴이 일정하게 유지되나요?', dataSources: ['solving_stage', 'review_routine_consistency', 'activity_type'] },
                        { text: '문제풀이 후 자기 확신도(확실·애매·모름) 판단이 실제 결과와 얼마나 일치하나요?', dataSources: ['self_confidence_level', 'actual_result', 'confidence_accuracy_match_score', 'activity_type'] }
                    ]
                }
            ],
            ontology: [
                { name: 'ProblemSolvingStrategy', description: '문제풀이 사고 흐름과 전략을 온톨로지로 표현 (Agent 04 핵심 온톨로지)' },
                { name: 'CognitiveLoadPattern', description: '문제풀이 인지부하와 감정 반응 패턴을 온톨로지로 표현' },
                { name: 'MetacognitiveReview', description: '문제풀이 메타인지적 검토와 자기조절을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 문제풀이 사고 흐름 분석, 인지부하 탐지, 메타인지적 검토 평가를 종합 분석합니다. rules.yaml의 PS_A1~PS_C3 룰과 직접 연계됩니다.',
                answerAnalysis: '핵심 조건 구분 능력은 PS_A1 룰이 분석하고, 구조적 파악 습관은 PS_A2 룰이 평가합니다. 인지부하 신호는 PS_B1 룰이 탐지하고, 감정 반응은 PS_B2 룰이 분석합니다. 자기 설명 능력은 PS_C1 룰이 평가합니다.',
                ruleBasedActions: 'rules.yaml의 PS_A1~PS_A3 룰이 사고 흐름을 분석하고, PS_B1~PS_B3 룰이 인지부하를 탐지하며, PS_C1~PS_C3 룰이 메타인지적 검토를 평가합니다.'
            }
        },
        4: { // ④ 오답노트
            questionSets: [
                {
                    title: '오답 발생 원인 및 사고 패턴 탐지',
                    questions: [
                        { text: '학생은 오답의 원인이 개념오류, 계산실수, 문제이해 착오 중 어느 쪽이 가장 빈번한가요?', dataSources: ['error_occurred', 'error_category', 'activity_type'] },
                        { text: '오답이 발생한 직전의 풀이 행동(멈춤, 서두름, 시선전환)에는 어떤 공통 패턴이 있나요?', dataSources: ['error_occurred', 'pre_error_behavior', 'activity_type'] },
                        { text: '학생은 오답 후 자신의 사고흐름을 되짚는 습관이 있나요, 아니면 바로 다음 문제로 넘어가나요?', dataSources: ['error_occurred', 'post_error_reflection', 'activity_type'] }
                    ]
                },
                {
                    title: '인지적 회복력 및 재도전 태도 분석',
                    questions: [
                        { text: '오답을 인식한 후 학생은 "왜 틀렸는지" 대신 "어떻게 다시 풀지"를 먼저 생각하나요?', dataSources: ['error_recognized', 'reflection_focus', 'activity_type'] },
                        { text: '틀린 문제를 다시 시도할 때 불안감, 흥미, 도전감 중 어떤 감정이 더 강하게 나타나나요?', dataSources: ['retry_attempted', 'retry_emotion', 'activity_type'] },
                        { text: '같은 유형의 문제를 다시 풀 때, 이전 실수를 피하기 위한 전략 변화가 보이나요?', dataSources: ['same_type_retry', 'strategy_change_detected', 'activity_type'] }
                    ]
                },
                {
                    title: '피드백 수용 및 행동변화 루프 평가',
                    questions: [
                        { text: '오답 피드백(TTS 해설, 교사 코멘트)을 들은 후 학생의 표정·시선·반응 속도에 어떤 변화가 있나요?', dataSources: ['feedback_provided', 'feedback_type', 'reception_indicators', 'activity_type'] },
                        { text: '피드백을 받은 뒤 실제 행동(필기 수정, 풀이노트 재작성)으로 연결되는 비율은 어느 정도인가요?', dataSources: ['feedback_provided', 'action_taken', 'feedback_to_action_rate', 'activity_type'] },
                        { text: '오답노트 활동 후 개선된 풀이 패턴이 다음 단원에서도 유지되나요?', dataSources: ['improved_pattern_detected', 'next_unit_maintenance', 'activity_type'] }
                    ]
                }
            ],
            ontology: [
                { name: 'ErrorPattern', description: '오답 발생 원인과 사고 패턴을 온톨로지로 표현 (Agent 04 핵심 온톨로지)' },
                { name: 'ErrorRecoveryResilience', description: '오답 인지적 회복력과 재도전 태도를 온톨로지로 표현' },
                { name: 'FeedbackTransfer', description: '오답 피드백 수용과 행동변화 루프를 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 오답 원인 탐지, 인지적 회복력 평가, 피드백 수용 루프 분석을 종합 분석합니다. rules.yaml의 EN_A1~EN_C3 룰과 직접 연계됩니다.',
                answerAnalysis: '오답 원인 분류는 EN_A1 룰이 분석하고, 오답 전 행동 패턴은 EN_A2 룰이 탐지합니다. 오답 후 성찰 습관은 EN_A3 룰이 평가합니다. 재도전 감정은 EN_B2 룰이 분석하고, 피드백 행동 전이는 EN_C2 룰이 평가합니다.',
                ruleBasedActions: 'rules.yaml의 EN_A1~EN_A3 룰이 오답 원인을 탐지하고, EN_B1~EN_B3 룰이 회복력을 평가하며, EN_C1~EN_C3 룰이 피드백 수용 루프를 분석합니다.'
            }
        },
        5: { // ⑤ 질의응답
            questionSets: [
                {
                    title: '질문 발생과 타이밍 분석',
                    questions: [
                        { text: '학생은 어떤 상황(문제 막힘 / 개념 혼동 / 복습 중)에 가장 자주 질문을 떠올리나요?', dataSources: ['question_occurred', 'question_context', 'activity_type'] },
                        { text: '질문이 생겼을 때 바로 표현하는가, 아니면 일정 시간 후에야 시도하는가?', dataSources: ['question_occurred', 'expression_timing', 'delay_duration', 'activity_type'] },
                        { text: '학습 세션 중 질문이 가장 많이 발생하는 시점(시작·중간·마무리)은 언제인가요?', dataSources: ['session_stage', 'question_frequency_by_stage', 'activity_type'] }
                    ]
                },
                {
                    title: '질문 내용 및 사고 깊이 평가',
                    questions: [
                        { text: '학생의 질문은 주로 개념확인형(사실질문)인가, 이유탐구형(이해질문)인가?', dataSources: ['question_occurred', 'question_type', 'activity_type'] },
                        { text: '질문이 단순한 의문 표현을 넘어서 "비교"나 "응용"을 포함하는 경우가 있나요?', dataSources: ['question_occurred', 'question_complexity', 'activity_type'] },
                        { text: '같은 개념에 대해 반복적으로 유사한 질문을 하는 패턴이 있나요?', dataSources: ['repeated_question_count', 'question_topic', 'activity_type'] }
                    ]
                },
                {
                    title: '피드백 반응과 질문 루프 지속성 평가',
                    questions: [
                        { text: 'AI나 교사의 답변 후, 학생은 자신의 질문이 해결되었다고 느끼나요?', dataSources: ['answer_provided', 'satisfaction_score', 'activity_type'] },
                        { text: '답변을 들은 후 스스로 추가 질문을 이어가거나 요약 정리를 시도하나요?', dataSources: ['answer_provided', 'follow_up_action', 'activity_type'] },
                        { text: '질문 피드백 후 학생의 사고 전환("아, 이래서 그랬구나!" 순간)이 시선/표정/발언에 나타나나요?', dataSources: ['answer_provided', 'insight_moment_detected', 'insight_indicators', 'activity_type'] }
                    ]
                }
            ],
            ontology: [
                { name: 'QuestionTimingPattern', description: '질문 발생 타이밍과 패턴을 온톨로지로 표현 (Agent 04 핵심 온톨로지)' },
                { name: 'QuestionDepth', description: '질문 내용과 사고 깊이를 온톨로지로 표현' },
                { name: 'QuestionFeedbackLoop', description: '질문 피드백 반응과 루프 지속성을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 질문 발생 타이밍 분석, 질문 내용 깊이 평가, 피드백 반응 루프 분석을 종합 분석합니다. rules.yaml의 QA_A1~QA_C3 룰과 직접 연계됩니다.',
                answerAnalysis: '질문 발생 상황은 QA_A1 룰이 분석하고, 질문 표현 타이밍은 QA_A2 룰이 평가합니다. 질문 유형 분석은 QA_B1 룰이 수행하고, 반복 질문 패턴은 QA_B3 룰이 탐지합니다. 사고 전환 순간은 QA_C3 룰이 포착합니다.',
                ruleBasedActions: 'rules.yaml의 QA_A1~QA_A3 룰이 질문 타이밍을 분석하고, QA_B1~QA_B3 룰이 질문 깊이를 평가하며, QA_C1~QA_C3 룰이 피드백 루프를 분석합니다.'
            }
        },
        6: { // ⑥ 복습활동
            questionSets: [
                {
                    title: '복습 타이밍·주기·분량 최적화',
                    questions: [
                        { text: '학생은 학습 후 몇 시간 혹은 며칠 뒤에 복습을 하는 패턴이 있나요?', dataSources: ['review_timing', 'review_timing_category', 'activity_type'] },
                        { text: '복습 시기별(즉시, 다음날, 일주일 후) 효율 차이가 어떻게 나타나나요?', dataSources: ['review_timing_comparison', 'efficiency_by_timing', 'activity_type'] },
                        { text: '복습 분량이 많아질수록 집중도나 감정 리듬에 어떤 변화가 있나요?', dataSources: ['review_volume', 'focus_decline', 'emotion_rhythm_change', 'activity_type'] }
                    ]
                },
                {
                    title: '복습 방식 및 내용 구조 분석',
                    questions: [
                        { text: '학생은 복습을 주로 개념 재확인, 문제풀이, 요약정리 중 어떤 방식으로 진행하나요?', dataSources: ['review_method', 'method_preference_score', 'activity_type'] },
                        { text: '복습 중 새로운 연결(개념 간, 단원 간, 실생활 사례)을 시도하는 경향이 있나요?', dataSources: ['connection_attempt', 'activity_type'] },
                        { text: '복습 시 노트, 화이트보드, 디지털 화면 등 매체 선호도가 일정한가요?', dataSources: ['review_medium', 'medium_preference_consistency', 'activity_type'] }
                    ]
                },
                {
                    title: '정서적 몰입과 루틴 지속성 평가',
                    questions: [
                        { text: '복습을 시작할 때 학생이 보이는 감정 상태는 어떤가요? (안정 / 회피 / 의욕 / 피로)', dataSources: ['review_start_emotion', 'activity_type'] },
                        { text: '복습 도중 집중이 떨어지거나 회피 행동이 나타나는 시점은 언제인가요?', dataSources: ['resistance_detected', 'resistance_timing', 'activity_type'] },
                        { text: '복습을 마친 뒤 스스로 만족감이나 효능감을 표현하는 행동이 있나요?', dataSources: ['review_completed', 'satisfaction_expression', 'activity_type'] }
                    ]
                }
            ],
            ontology: [
                { name: 'ReviewTimingOptimization', description: '복습 타이밍·주기·분량 최적화를 온톨로지로 표현 (Agent 04 핵심 온톨로지)' },
                { name: 'ReviewMethodStructure', description: '복습 방식과 내용 구조를 온톨로지로 표현' },
                { name: 'ReviewEmotionRoutine', description: '복습 정서적 몰입과 루틴 지속성을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 복습 타이밍 최적화, 복습 방식 분석, 정서적 몰입 평가를 종합 분석합니다. rules.yaml의 RV_A1~RV_C3 룰과 직접 연계됩니다.',
                answerAnalysis: '복습 타이밍 패턴은 RV_A1 룰이 분석하고, 시기별 효율 비교는 RV_A2 룰이 평가합니다. 복습 방식 선호도는 RV_B1 룰이 분석하고, 연결 시도는 RV_B2 룰이 평가합니다. 저항감 발생 시점은 RV_C2 룰이 탐지합니다.',
                ruleBasedActions: 'rules.yaml의 RV_A1~RV_A3 룰이 복습 타이밍을 최적화하고, RV_B1~RV_B3 룰이 복습 방식을 분석하며, RV_C1~RV_C3 룰이 정서적 몰입을 평가합니다.'
            }
        },
        7: { // ⑦ 포모도르 수학일기
            questionSets: [
                {
                    title: '집중 리듬 및 세션 설계 평가',
                    questions: [
                        { text: '학생의 평균 집중 지속시간은 몇 분이며, 포모도르 단위와 잘 맞나요?', dataSources: ['average_focus_duration', 'pomodoro_unit_match_score', 'activity_type'] },
                        { text: '세션 초반·중반·후반 중 어느 구간에서 집중력이 가장 안정적으로 유지되나요?', dataSources: ['session_stage', 'focus_stability_by_stage', 'activity_type'] },
                        { text: '세션 사이 휴식 시간(짧은·긴)에서 회복 패턴이 일정하게 나타나나요?', dataSources: ['rest_duration_type', 'recovery_pattern_consistency', 'activity_type'] }
                    ]
                },
                {
                    title: '자기 성찰 및 학습 메타인지 수준 평가',
                    questions: [
                        { text: '학생은 일기에서 "무엇을 배웠는가"보다 "어떻게 배웠는가"를 언급하나요?', dataSources: ['journal_content_analyzed', 'what_learned_ratio', 'how_learned_ratio', 'activity_type'] },
                        { text: '일기 내용에 학습의 어려움이나 감정 변화를 언급하는 비율은 어느 정도인가요?', dataSources: ['journal_content_analyzed', 'emotion_mention_ratio', 'activity_type'] },
                        { text: '포모도르 일기 중 자신의 실수를 인식하고 개선 다짐을 남기는 패턴이 있나요?', dataSources: ['journal_content_analyzed', 'mistake_recognition_pattern', 'activity_type'] }
                    ]
                },
                {
                    title: '감정표현·성장인식·루틴화 평가',
                    questions: [
                        { text: '학생은 일기에서 긍정·부정 감정을 어떻게 균형 있게 표현하나요?', dataSources: ['journal_content_analyzed', 'emotion_balance_score', 'activity_type'] },
                        { text: '감정표현 후 학습 태도(다음 세션 몰입, 자발적 재도전)가 달라지나요?', dataSources: ['emotion_expressed', 'subsequent_behavior_change', 'activity_type'] },
                        { text: '포모도르 일기 기록이 학습 루틴의 강화로 이어지는 징후(패턴 언급, 반복 의지)가 보이나요?', dataSources: ['journal_consistency_days', 'routine_mention_frequency', 'repeat_willingness', 'activity_type'] }
                    ]
                }
            ],
            ontology: [
                { name: 'PomodoroFocusRhythm', description: '포모도르 집중 리듬과 세션 설계를 온톨로지로 표현 (Agent 04 핵심 온톨로지)' },
                { name: 'MetacognitiveReflection', description: '포모도르 일기 자기 성찰과 메타인지 수준을 온톨로지로 표현' },
                { name: 'EmotionRoutineFormation', description: '포모도르 일기 감정표현과 루틴 형성을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 포모도르 집중 리듬 분석, 자기 성찰 수준 평가, 감정표현 루틴화 분석을 종합 분석합니다. rules.yaml의 PJ_A1~PJ_C3 룰과 직접 연계됩니다.',
                answerAnalysis: '집중 지속시간은 PJ_A1 룰이 분석하고, 세션 단계별 집중력은 PJ_A2 룰이 평가합니다. 메타인지적 성찰 수준은 PJ_B1 룰이 평가하고, 감정 언급 비율은 PJ_B2 룰이 분석합니다. 루틴 형성 징후는 PJ_C3 룰이 탐지합니다.',
                ruleBasedActions: 'rules.yaml의 PJ_A1~PJ_A3 룰이 집중 리듬을 분석하고, PJ_B1~PJ_B3 룰이 자기 성찰을 평가하며, PJ_C1~PJ_C3 룰이 감정표현 루틴화를 분석합니다.'
            }
        },
        8: { // ⑧ 귀가검사
            questionSets: [
                {
                    title: '학습 마무리 인식 및 성취 정리',
                    questions: [
                        { text: '학생은 귀가검사에서 오늘 학습의 핵심 성취를 명확히 말할 수 있나요?', dataSources: ['return_check_stage', 'achievement_clarity_score', 'activity_type'] },
                        { text: '하루 동안 가장 의미 있었다고 느낀 학습 순간을 구체적으로 설명하나요?', dataSources: ['return_check_stage', 'meaningful_moment_identified', 'activity_type'] },
                        { text: '귀가검사 시, 학생이 스스로 느끼는 만족감과 피로감의 균형은 어떤가요?', dataSources: ['return_check_stage', 'satisfaction_fatigue_balance_score', 'activity_type'] }
                    ]
                },
                {
                    title: '피드백 수용 및 행동전이 분석',
                    questions: [
                        { text: '교사 피드백 중 어떤 유형(칭찬, 교정, 조언)에 학생이 가장 강하게 반응하나요?', dataSources: ['return_check_stage', 'feedback_type', 'response_intensity', 'activity_type'] },
                        { text: '피드백을 받은 후, 즉시 수정 행동(노트 보완, 개념 재정리)을 수행하나요?', dataSources: ['return_check_stage', 'feedback_provided', 'immediate_action_taken', 'activity_type'] },
                        { text: '피드백에 대해 방어적 반응(핑계, 회피) 또는 성장형 반응(수용, 재시도)이 나타나나요?', dataSources: ['return_check_stage', 'feedback_provided', 'reception_type', 'activity_type'] }
                    ]
                },
                {
                    title: '개선 루틴 추적 및 다음 루프 연결',
                    questions: [
                        { text: '귀가검사에서 도출된 개선 포인트가 다음 학습일정(개념이해 or 복습)에 반영되나요?', dataSources: ['return_check_stage', 'improvement_point_identified', 'next_schedule_reflection', 'activity_type'] },
                        { text: '귀가검사 이후에도 학생이 스스로 개선 루틴을 점검하려는 행동이 보이나요?', dataSources: ['return_check_stage', 'self_check_behavior', 'activity_type'] },
                        { text: '반복 피드백 후, 루틴 유지 기간이 점점 길어지는 패턴이 있나요?', dataSources: ['return_check_stage', 'feedback_repeat_count', 'routine_maintenance_trend', 'activity_type'] }
                    ]
                }
            ],
            ontology: [
                { name: 'ReturnCheckAchievement', description: '귀가검사 학습 마무리 인식과 성취 정리를 온톨로지로 표현 (Agent 04 핵심 온톨로지)' },
                { name: 'FeedbackAcceptanceTransfer', description: '귀가검사 피드백 수용과 행동전이를 온톨로지로 표현' },
                { name: 'RoutineLoopConnection', description: '귀가검사 개선 루틴 추적과 다음 루프 연결을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 귀가검사 성취 정리, 피드백 수용 분석, 개선 루틴 추적을 종합 분석합니다. rules.yaml의 RC_A1~RC_C3 룰과 직접 연계됩니다.',
                answerAnalysis: '성취 명확화는 RC_A1 룰이 평가하고, 의미 있는 순간 인식은 RC_A2 룰이 평가합니다. 피드백 유형별 반응은 RC_B1 룰이 분석하고, 즉시 행동 전이는 RC_B2 룰이 평가합니다. 루틴 유지 개선은 RC_C3 룰이 분석합니다.',
                ruleBasedActions: 'rules.yaml의 RC_A1~RC_A3 룰이 성취 정리를 평가하고, RC_B1~RC_B3 룰이 피드백 수용을 분석하며, RC_C1~RC_C3 룰이 개선 루틴 추적을 평가합니다.'
            }
        },
        
        // Agent 04 전체 온톨로지 사용 영역 식별 및 추천
        ontologyRecommendations: {
            coreOntologies: [
                {
                    name: 'LearningActivityWeakpoint',
                    description: '8가지 학습 활동 영역(개념이해, 유형학습, 문제풀이, 오답노트, 질의응답, 복습활동, 포모도르일기, 귀가검사)의 취약점을 통합적으로 표현하는 핵심 온톨로지',
                    usage: '각 활동 영역의 취약점 패턴을 비교 분석하고, 활동 간 연관성을 파악하는 데 활용',
                    priority: 'high',
                    relatedRules: ['CU_A1~CU_C3', 'TL_A1~TL_C3', 'PS_A1~PS_C3', 'EN_A1~EN_C3', 'QA_A1~QA_C3', 'RV_A1~RV_C3', 'PJ_A1~PJ_C3', 'RC_A1~RC_C3']
                },
                {
                    name: 'ActivityPersonaMatch',
                    description: '학습 활동별 페르소나 매칭 관계를 온톨로지로 표현 (예: 개념이해-감각형, 유형학습-분석형)',
                    usage: '학생의 페르소나와 각 활동 영역의 적합성을 평가하고 맞춤형 활동 조합을 추천하는 데 활용',
                    priority: 'high',
                    relatedRules: ['CU_B1', 'TL_B1', 'PS_A2', 'EN_B1', 'QA_B1', 'RV_B1', 'PJ_B1', 'RC_B1']
                },
                {
                    name: 'EmotionImmersionLoop',
                    description: '학습 활동별 감정-몰입 루프를 온톨로지로 표현 (감정 상태 → 몰입 진입 → 활동 효과성)',
                    usage: '각 활동에서 감정 상태가 몰입과 학습 효과에 미치는 영향을 분석하고 최적화하는 데 활용',
                    priority: 'high',
                    relatedRules: ['CU_C1~CU_C3', 'TL_C1~TL_C3', 'PS_B2', 'EN_B2', 'QA_C3', 'RV_C1~RV_C3', 'PJ_C1~PJ_C3', 'RC_B1']
                },
                {
                    name: 'FeedbackTransferPattern',
                    description: '학습 활동 간 피드백 전이 패턴을 온톨로지로 표현 (예: 오답노트 → 문제풀이 개선)',
                    usage: '한 활동에서의 피드백이 다른 활동에 미치는 영향을 추적하고 활동 간 시너지를 극대화하는 데 활용',
                    priority: 'medium',
                    relatedRules: ['EN_C2', 'RC_B2', 'PS_C1', 'RV_C3', 'PJ_C2']
                },
                {
                    name: 'MetacognitiveReflectionLevel',
                    description: '학습 활동별 메타인지적 성찰 수준을 온톨로지로 표현 (자기 인식, 자기 조절, 자기 평가)',
                    usage: '학생의 메타인지 능력이 각 활동에서 어떻게 발휘되는지 분석하고 향상시키는 데 활용',
                    priority: 'medium',
                    relatedRules: ['PS_C1~PS_C3', 'EN_A3', 'EN_B1', 'PJ_B1~PJ_B3', 'RC_C2']
                },
                {
                    name: 'ActivityRoutineStability',
                    description: '학습 활동별 루틴 안정성과 지속성을 온톨로지로 표현',
                    usage: '각 활동의 루틴이 얼마나 안정적으로 유지되는지 평가하고 루틴 붕괴를 예방하는 데 활용',
                    priority: 'medium',
                    relatedRules: ['CU_C2', 'TL_A2', 'PS_C2', 'RV_C2', 'PJ_C3', 'RC_C3']
                }
            ],
            crossActivityOntologies: [
                {
                    name: 'ActivitySequenceOptimization',
                    description: '8가지 활동 간 최적 순서와 전이 패턴을 온톨로지로 표현',
                    usage: '하루 학습 루틴에서 활동 순서를 최적화하고 활동 간 전이 효율을 높이는 데 활용',
                    priority: 'high',
                    example: '개념이해 → 유형학습 → 문제풀이 → 오답노트 순서의 효율성 분석'
                },
                {
                    name: 'WeakpointCorrelation',
                    description: '여러 활동 영역에서 공통으로 나타나는 취약점 패턴의 상관관계를 온톨로지로 표현',
                    usage: '한 활동의 취약점이 다른 활동에 미치는 영향을 분석하고 종합적 개입 전략을 수립하는 데 활용',
                    priority: 'high',
                    example: '개념이해 취약점과 문제풀이 오류 패턴의 연관성 분석'
                },
                {
                    name: 'ActivityPerformancePrediction',
                    description: '학습 활동별 성과 예측 모델을 온톨로지로 표현 (과거 패턴 기반 미래 성과 예측)',
                    usage: '학생의 활동별 성과를 예측하고 조기 개입이 필요한 활동을 식별하는 데 활용',
                    priority: 'medium',
                    example: '유형학습 패턴 기반 문제풀이 성과 예측'
                }
            ],
            integrationRecommendations: [
                {
                    area: 'Agent 05 (학습감정)와의 통합',
                    description: '각 활동 영역의 감정 패턴을 Agent 05의 감정 분석과 연계하여 활동별 감정 최적화 전략 수립',
                    ontology: ['EmotionImmersionLoop', 'ActivityPersonaMatch'],
                    benefit: '감정 상태에 따른 활동 선택 및 조정으로 학습 효율 향상'
                },
                {
                    area: 'Agent 09 (학습관리)와의 통합',
                    description: '활동별 취약점 데이터를 학습관리 시스템과 연계하여 루틴 자동 조정',
                    ontology: ['ActivityRoutineStability', 'ActivitySequenceOptimization'],
                    benefit: '취약점 기반 자동 루틴 최적화로 학습 지속성 향상'
                },
                {
                    area: 'Agent 18 (시그너처루틴)와의 통합',
                    description: '각 활동에서 발견된 몰입 패턴을 시그너처 루틴 설계에 반영',
                    ontology: ['EmotionImmersionLoop', 'ActivitySequenceOptimization'],
                    benefit: '개인별 최적 몰입 루틴 설계로 학습 효과 극대화'
                },
                {
                    area: 'Agent 20 (개입준비)와의 통합',
                    description: '활동별 취약점 탐지 시점을 개입 타이밍 결정에 활용',
                    ontology: ['LearningActivityWeakpoint', 'WeakpointCorrelation'],
                    benefit: '정확한 개입 타이밍으로 개입 효과성 향상'
                }
            ],
            implementationPriority: {
                phase1: [
                    'LearningActivityWeakpoint',
                    'ActivityPersonaMatch',
                    'EmotionImmersionLoop'
                ],
                phase2: [
                    'FeedbackTransferPattern',
                    'MetacognitiveReflectionLevel',
                    'ActivityRoutineStability'
                ],
                phase3: [
                    'ActivitySequenceOptimization',
                    'WeakpointCorrelation',
                    'ActivityPerformancePrediction'
                ]
            }
        }
    };
    
    // Agent 05: 학습감정 (Learning Emotion)
    window.dataBasedQuestionSets.agent05 = {
        1: { // 포괄형 질문 1: 감정 흐름 패턴 분석
            questionSets: [
                {
                    title: '학습활동 전반 감정 흐름 패턴',
                    questions: [
                        { text: '학생의 학습활동 전반에서 감정의 흐름은 어떤 패턴으로 움직이고 있나요?', dataSources: ['agent_data.agent05_data.emotion_score', 'agent_data.agent05_data.emotion_data_count', 'agent_data.agent05_data.learning_immersion_estimate', 'agent_data.agent05_data.emotion_curve'] },
                        { text: '집중-피로-회복-몰입의 순환 패턴이 안정적인가요?', dataSources: ['agent_data.agent05_data.emotion_curve', 'agent_data.agent05_data.fatigue_pattern', 'agent_data.agent05_data.recovery_time_avg', 'agent_data.agent05_data.immersion_pattern'] },
                        { text: '학생이 몰입에 진입하거나 빠져나오는 감정 전환 구간은 어디인가요?', dataSources: ['agent_data.agent05_data.emotion_drop_detection', 'agent_data.agent05_data.immersion_maintenance_time', 'agent_data.agent05_data.emotion_stabilization_point', 'agent_data.agent05_data.emotion_transition_zone'] }
                    ]
                },
                {
                    title: '시그너처루틴 레벨 감정패턴 모델링',
                    questions: [
                        { text: '시그너처루틴 레벨에서 감정패턴을 모델링하기 위한 핵심 지표는?', dataSources: ['agent_data.agent05_data.emotion_pattern_by_routine', 'agent_data.agent18_data.signature_routine', 'agent_data.agent05_data.routine_emotion_mapping'] },
                        { text: '현재 감정 루틴의 안정도 점수는?', dataSources: ['agent_data.agent05_data.emotion_routine_stability_score', 'agent_data.agent05_data.routine_consistency', 'agent_data.agent05_data.emotion_stability'] }
                    ]
                }
            ],
            ontology: [
                { name: 'EmotionFlowPattern', description: '감정 흐름 패턴(집중-피로-회복-몰입)을 온톨로지로 표현 (Agent 05 핵심 온톨로지)' },
                { name: 'EmotionTransitionZone', description: '몰입 진입/이탈 감정 전환 구간을 온톨로지로 표현' },
                { name: 'SignatureRoutineEmotionPattern', description: '시그너처루틴 레벨 감정패턴을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 개념 이해부터 문제풀이, 복습, 귀가검사까지의 감정 곡선을 분석하여 집중-피로-회복-몰입의 순환 패턴이 안정적인지 진단합니다. rules.yaml의 R1~R5 룰과 직접 연계됩니다.',
                answerAnalysis: '감정 흐름 패턴은 R1~R5 룰이 분석하고, 순환 패턴 안정성은 R2 룰이 평가합니다. 감정 전환 구간은 R3 룰이 식별하며, 감정 루틴 안정도는 R4 룰이 계산합니다.',
                ruleBasedActions: 'rules.yaml의 R1~R5 룰이 상황별 감정을 탐지하고, 시그너처루틴 레벨 감정패턴을 모델링합니다.'
            }
        },
        2: { // 포괄형 질문 2: 감정 페르소나 기반 피드백 최적화
            questionSets: [
                {
                    title: '감정형 페르소나 식별',
                    questions: [
                        { text: '학생의 감정형 페르소나(불안-보완형, 도전-몰입형, 회피-저항형)는 무엇인가요?', dataSources: ['agent_data.agent05_data.emotion_persona', 'agent_data.agent01_data.math_confidence', 'agent_data.agent01_data.math_stress_level', 'agent_data.agent05_data.failure_reaction', 'agent_data.agent05_data.emotion_score'] },
                        { text: '감정형 페르소나별 피드백 수용률과 회복속도는?', dataSources: ['agent_data.agent05_data.feedback_acceptance_rate', 'agent_data.agent05_data.recovery_time_avg', 'agent_data.agent05_data.emotion_tone', 'agent_data.agent05_data.persona_feedback_effectiveness'] },
                        { text: '감정 반응에 맞는 피드백 구조(TTS 톤, 인터페이스 타이밍, 과제 순서, 휴식 삽입 시점)는?', dataSources: ['agent_data.agent05_data.preferred_interaction_channel', 'agent_data.agent05_data.interaction_tone_preference', 'agent_data.agent05_data.rest_routine_pattern', 'agent_data.agent05_data.feedback_structure_optimization'] }
                    ]
                },
                {
                    title: '맞춤형 피드백 및 개입 방식',
                    questions: [
                        { text: '선생님/AI의 개입 방식(논리형·격려형·리듬형) 중 어떤 방식이 가장 효과적인가요?', dataSources: ['agent_data.agent05_data.intervention_type_effectiveness', 'agent_data.agent05_data.logical_intervention_score', 'agent_data.agent05_data.encouragement_intervention_score', 'agent_data.agent05_data.rhythm_intervention_score'] },
                        { text: '감정형 페르소나별 학습 효율 극대화를 위한 피드백 조정 전략은?', dataSources: ['agent_data.agent05_data.persona_based_feedback_strategy', 'agent_data.agent05_data.learning_efficiency_maximization', 'agent_data.agent06_data.teacher_feedback'] }
                    ]
                }
            ],
            ontology: [
                { name: 'EmotionPersona', description: '감정형 페르소나를 온톨로지로 표현 (Agent 05 핵심 온톨로지)' },
                { name: 'PersonaBasedFeedback', description: '감정형 페르소나 기반 피드백 구조를 온톨로지로 표현' },
                { name: 'InterventionTypeOptimization', description: '개입 방식(논리형·격려형·리듬형) 최적화를 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 학생의 감정형 페르소나를 식별한 뒤 TTS 톤, 인터페이스 타이밍, 과제 순서, 휴식 삽입 시점 등 감정 반응에 맞는 피드백 구조를 최적화합니다. rules.yaml의 R6~R10 룰과 Agent 06과 연계됩니다.',
                answerAnalysis: '감정형 페르소나는 R6 룰이 식별하고, 피드백 수용률은 R7 룰이 분석합니다. 피드백 구조 최적화는 R8 룰이 수행하며, 개입 방식은 R9 룰이 결정합니다.',
                ruleBasedActions: 'rules.yaml의 R6~R10 룰이 감정 페르소나를 분석하고, 맞춤형 피드백 구조를 최적화합니다.'
            }
        },
        3: { // 포괄형 질문 3: 감정 자기조절 능력 향상
            questionSets: [
                {
                    title: '감정 회복 및 피로 패턴 분석',
                    questions: [
                        { text: '학생의 감정 회복속도, 피로 누적 패턴, 메타인지적 감정 인식도는?', dataSources: ['agent_data.agent05_data.recovery_time_avg', 'agent_data.agent05_data.fatigue_pattern', 'agent_data.agent05_data.emotion_awareness', 'agent_data.agent05_data.metacognitive_emotion_recognition'] },
                        { text: '정서적 회복 루틴과 자기통제 루틴(감정 명시 → 행동 전환 → 성취 인식) 설계는?', dataSources: ['agent_data.agent05_data.emotion_recovery_routine', 'agent_data.agent05_data.self_control_routine', 'agent_data.agent05_data.achievement_recognition', 'agent_data.agent05_data.emotion_naming_to_action'] },
                        { text: '장기적으로 감정의 파동이 학습 효율에 미치는 영향을 예측·조정하는 방법은?', dataSources: ['agent_data.agent05_data.emotion_impact_prediction', 'agent_data.agent05_data.learning_efficiency', 'agent_data.agent05_data.emotion_curve', 'agent_data.agent05_data.long_term_emotion_learning_correlation'] }
                    ]
                },
                {
                    title: '감정 기반 자기조절 성장 로드맵',
                    questions: [
                        { text: '학습감정의 자가 피드백 시스템을 형성하기 위한 루틴은?', dataSources: ['agent_data.agent05_data.emotion_self_feedback_system', 'agent_data.agent05_data.self_regulation_routine', 'agent_data.agent18_data.signature_routine'] },
                        { text: '감정의 파동이 학습 효율에 미치는 영향을 예측·조정하는 모델은?', dataSources: ['agent_data.agent05_data.emotion_wave_prediction', 'agent_data.agent05_data.learning_efficiency_impact', 'agent_data.agent05_data.emotion_adjustment_model'] }
                    ]
                }
            ],
            ontology: [
                { name: 'EmotionSelfRegulation', description: '감정 자기조절 능력을 온톨로지로 표현 (Agent 05 핵심 온톨로지)' },
                { name: 'EmotionRecoveryRoutine', description: '정서적 회복 루틴을 온톨로지로 표현' },
                { name: 'EmotionImpactPrediction', description: '감정의 파동이 학습 효율에 미치는 영향 예측을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 감정 회복속도, 피로 누적 패턴, 메타인지적 감정 인식도를 종합 분석하여 정서적 회복 루틴과 자기통제 루틴을 설계합니다. rules.yaml의 R11~R15 룰과 Agent 18과 연계됩니다.',
                answerAnalysis: '감정 회복속도는 R11 룰이 분석하고, 피로 누적 패턴은 R12 룰이 평가합니다. 메타인지적 감정 인식도는 R13 룰이 평가하며, 자기조절 루틴은 R14 룰이 설계합니다.',
                ruleBasedActions: 'rules.yaml의 R11~R15 룰이 감정 자기조절 능력을 향상시키고, 감정 기반 자기조절 성장 로드맵을 생성합니다.'
            }
        }
    };
    
    // Agent 06: 교사피드백 (Teacher Feedback)
    window.dataBasedQuestionSets.agent06 = {
        1: { // 포괄형 질문 A: 핵심 의도 및 페르소나 톤 도출
            questionSets: [
                {
                    title: '핵심 의도 및 페르소나 톤 도출',
                    questions: [
                        { text: '최근 상호작용(메모/녹취/귀가검사)을 바탕으로, 일관되게 전하고 싶은 핵심 의도(성장/도전/안정/회복)는?', dataSources: ['agent_data.agent06_data.recent_interactions', 'agent_data.agent06_data.teacher_memo', 'agent_data.agent04_data.departure_check_data', 'agent_data.agent06_data.core_intention'] },
                        { text: '핵심 의도를 가장 자연스럽게 전달하는 페르소나 톤과 언어 패턴은?', dataSources: ['agent_data.agent06_data.teacher_feedback_style', 'agent_data.agent05_data.interaction_tone_preference', 'agent_data.agent05_data.feedback_acceptance_rate', 'agent_data.agent06_data.persona_tone'] },
                        { text: '위험 신호 체크리스트와 즉시 사용 가능한 대체 스크립트는?', dataSources: ['agent_data.agent06_data.risk_signals', 'agent_data.agent06_data.feedback_templates', 'agent_data.agent06_data.interaction_history', 'agent_data.agent06_data.alternative_scripts'] }
                    ]
                }
            ],
            ontology: [
                { name: 'TeacherPersonaTone', description: '교사 페르소나 톤과 언어 패턴을 온톨로지로 표현 (Agent 06 핵심 온톨로지)' },
                { name: 'CoreIntention', description: '교사의 핵심 의도(성장/도전/안정/회복)를 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 최근 상호작용을 바탕으로 교사의 핵심 의도와 페르소나 톤을 도출합니다. rules.yaml의 R1a~R1d 룰과 직접 연계됩니다.',
                answerAnalysis: '최근 상호작용 데이터는 R1a 룰이 분석하고, 페르소나 톤은 R1b 룰이 도출합니다. 위험 신호는 R1c 룰이 탐지하며, 대체 스크립트는 R1d 룰이 생성합니다.',
                ruleBasedActions: 'rules.yaml의 R1a~R1d 룰이 학생 변화를 감지하고, 핵심 의도와 페르소나 톤을 도출합니다.'
            }
        },
        2: { // 포괄형 질문 B: 3단계 개입 시나리오 설계
            questionSets: [
                {
                    title: '3단계 개입 시나리오 설계',
                    questions: [
                        { text: '학생의 최근 변화 신호(집중/감정/진도)와 다가오는 문맥(시험·모의·진학 상담)을 종합한 오프닝 전략은?', dataSources: ['agent_data.agent06_data.concentration_change', 'agent_data.agent05_data.emotion_change', 'agent_data.agent06_data.progress_change', 'agent_data.agent02_data.upcoming_context', 'agent_data.agent06_data.opening_strategy'] },
                        { text: '코칭 단계에서 전달할 핵심 메시지와 톤 스위치 규칙은?', dataSources: ['agent_data.agent06_data.core_message', 'agent_data.agent06_data.tone_switch_rules', 'agent_data.agent06_data.coaching_strategy'] },
                        { text: '클로징 단계의 과제·리마인드 카드와 보호자 안내 문구는?', dataSources: ['agent_data.agent06_data.task_assignment', 'agent_data.agent06_data.reminder_card', 'agent_data.agent06_data.parent_communication'] }
                    ]
                }
            ],
            ontology: [
                { name: 'InteractionScenario', description: '3단계 개입 시나리오(오프닝→코칭→클로징)를 온톨로지로 표현 (Agent 06 핵심 온톨로지)' },
                { name: 'ToneSwitchRules', description: '톤 스위치 규칙을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 학생의 변화 신호와 다가오는 문맥을 종합하여 3단계 개입 시나리오를 설계합니다. rules.yaml의 R2a~R2d 룰과 Agent 02, Agent 05와 연계됩니다.',
                answerAnalysis: '변화 신호 분석은 R2a 룰이 수행하고, 오프닝 전략은 R2b 룰이 설계합니다. 코칭 전략은 R2c 룰이 수립하며, 클로징 전략은 R2d 룰이 생성합니다.',
                ruleBasedActions: 'rules.yaml의 R2a~R2d 룰이 3단계 개입 시나리오를 설계하고, 톤 스위치 규칙을 적용합니다.'
            }
        },
        3: { // 포괄형 질문 C: 누락 정보 자동 수집
            questionSets: [
                {
                    title: '누락 정보 자동 수집',
                    questions: [
                        { text: '상호작용 최적화에 필요한 핵심 정보(목표, 시험 일정, 톤 선호, 최근 감정 패턴) 중 누락된 것은?', dataSources: ['agent_data.agent03_data.goals', 'agent_data.agent02_data.exam_schedule', 'agent_data.agent05_data.tone_preference', 'agent_data.agent05_data.recent_emotion_pattern', 'agent_data.agent06_data.missing_info'] },
                        { text: '누락 정보의 우선순위와 수집 메시지 템플릿은?', dataSources: ['agent_data.agent06_data.missing_info_priority', 'agent_data.agent06_data.collection_message_template', 'agent_data.agent06_data.info_collection_strategy'] },
                        { text: '자동 업데이트 규칙과 전/후 비교 리포트는?', dataSources: ['agent_data.agent06_data.auto_update_rules', 'agent_data.agent06_data.before_after_comparison', 'agent_data.agent06_data.info_completeness_report'] }
                    ]
                }
            ],
            ontology: [
                { name: 'InformationCollection', description: '정보 수집 프로세스를 온톨로지로 표현 (Agent 06 핵심 온톨로지)' },
                { name: 'InfoCompleteness', description: '정보 완전성 평가를 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 상호작용 최적화에 필요한 핵심 정보 중 누락된 것을 식별하고 자동 수집 절차를 설계합니다. rules.yaml의 R3a~R3d 룰과 Agent 02, Agent 03, Agent 05와 연계됩니다.',
                answerAnalysis: '누락 정보 식별은 R3a 룰이 수행하고, 우선순위 결정은 R3b 룰이 평가합니다. 수집 메시지 템플릿은 R3c 룰이 생성하며, 자동 업데이트 규칙은 R3d 룰이 설정합니다.',
                ruleBasedActions: 'rules.yaml의 R3a~R3d 룰이 누락 정보를 식별하고, 자동 수집 절차를 설계합니다.'
            }
        }
    };
    
    // Agent 07: 상호작용타게팅
    window.dataBasedQuestionSets.agent07 = {
        1: {
            questionSets: [{
                title: '상호작용 타이밍 최적화',
                questions: [
                    { text: '현재 학습 상태(집중도, 감정 곡선, 학습 시간대, 피로도)를 고려한 최적 개입 타이밍은?', dataSources: ['current_concentration', 'emotion_curve', 'learning_time_slot', 'fatigue_level'] },
                    { text: '루틴 위치(수업 중/휴식 중/귀가 전)와 직전 상호작용 효과를 고려한 개입 결정은?', dataSources: ['routine_position', 'previous_interaction_effect', 'interaction_history'] },
                    { text: '지금 개입할지, 기다릴지, 유도형으로 바꿀지 판단 기준은?', dataSources: ['intervention_priority', 'wait_time', 'guidance_type'] }
                ]
            }],
            ontology: [{ name: 'InteractionTiming', description: '상호작용 타이밍을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '학습 상태와 루틴 위치를 종합하여 최적 타이밍을 결정합니다.', answerAnalysis: '집중도, 감정, 피로도 데이터를 실시간 분석합니다.', ruleBasedActions: 'rules.yaml의 타이밍 결정 룰이 트리거됩니다.' }
        },
        2: {
            questionSets: [{
                title: '상호작용 형태 최적화',
                questions: [
                    { text: '학생에게 가장 효과적인 감정 톤(활동적·차분·공감형)은?', dataSources: ['emotion_tone', 'preferred_interaction_tone', 'feedback_acceptance_rate'] },
                    { text: '최적의 채널(음성·텍스트·시각)과 메시지 길이(짧게·길게)는?', dataSources: ['preferred_interaction_channel', 'interaction_length_preference', 'channel_effectiveness'] },
                    { text: '적절한 표현 강도(격려/조정/리마인드)는?', dataSources: ['expression_intensity', 'interaction_effectiveness', 'student_response_pattern'] }
                ]
            }],
            ontology: [{ name: 'InteractionForm', description: '상호작용 형태를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '감정 톤, 채널, 메시지 길이, 표현 강도를 종합하여 최적 형태를 결정합니다.', answerAnalysis: '학생의 선호도와 효과성 데이터를 분석합니다.', ruleBasedActions: 'rules.yaml의 형태 최적화 룰이 트리거됩니다.' }
        },
        3: {
            questionSets: [{
                title: '장기 루틴과 정서 루프 연계',
                questions: [
                    { text: '이번 개입의 목표(즉시 위로/격려, 루틴 강화, 정서 회복, 자기인식 촉진)는?', dataSources: ['intervention_goal', 'routine_status', 'emotion_status', 'self_awareness_level'] },
                    { text: '학생의 장기 루틴과 정서 루프 안에서 이 개입이 가지는 의미는?', dataSources: ['long_term_routine', 'emotion_loop', 'intervention_context'] },
                    { text: '메타인지 대화를 통한 자기인식 촉진이 필요한가?', dataSources: ['metacognition_level', 'self_awareness_need', 'intervention_history'] }
                ]
            }],
            ontology: [{ name: 'LongTermRoutineEmotionLoop', description: '장기 루틴과 정서 루프를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '단기 개입을 장기 루틴과 정서 루프에 연결하여 의미를 부여합니다.', answerAnalysis: '루틴 상태와 정서 상태를 종합 분석합니다.', ruleBasedActions: 'rules.yaml의 장기 연계 룰이 트리거됩니다.' }
        }
    };
    
    // Agent 08: 침착도
    window.dataBasedQuestionSets.agent08 = {
        1: {
            questionSets: [{
                title: '사고 속도와 판단 균형 분석',
                questions: [
                    { text: '학생의 풀이 시간, 답 선택 패턴, 정답률을 기반으로 사고의 빠름·느림 불균형은?', dataSources: ['solving_time', 'answer_selection_pattern', 'correct_rate', 'emotion_stability'] },
                    { text: '침착도를 높이는 사고 루틴(검증→선택→점검)과 멈춤 전략(5초 점검, 근거 확언)은?', dataSources: ['calmness_score', 'thinking_routine', 'pause_strategy'] },
                    { text: '감정 안정도와 피로도가 사고 속도에 미치는 영향은?', dataSources: ['emotion_stability', 'fatigue_level', 'thinking_speed'] }
                ]
            }],
            ontology: [{ name: 'ThinkingSpeedBalance', description: '사고 속도와 판단 균형을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '사고 속도와 판단의 균형을 분석하여 침착도 향상 전략을 도출합니다.', answerAnalysis: '풀이 시간, 정답률, 감정 안정도를 종합 분석합니다.', ruleBasedActions: 'rules.yaml의 침착도 분석 룰이 트리거됩니다.' }
        },
        2: {
            questionSets: [{
                title: '사고 패턴 강화 및 교정',
                questions: [
                    { text: '최근 오답 패턴, 확신 신호, 무리한 시도 비율, 사고 생략률을 종합한 주요 리스크 패턴은?', dataSources: ['error_pattern', 'confidence_signal', 'over_attempt_ratio', 'thinking_omission_rate'] },
                    { text: '직감형 판단, 불확신형 회피, 생략형 사고 중 어떤 패턴이 주요 리스크인가?', dataSources: ['intuitive_judgment', 'uncertainty_avoidance', 'omission_thinking'] },
                    { text: '각 패턴에 대응하는 피드백 전략(자기검증 질문, 근거 명시 훈련, 시간 관리 루틴)은?', dataSources: ['feedback_strategy', 'self_verification_questions', 'evidence_training', 'time_management_routine'] }
                ]
            }],
            ontology: [{ name: 'ThinkingPattern', description: '사고 패턴(직감형/불확신형/생략형)을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '사고 패턴의 리스크를 식별하고 교정 전략을 설계합니다.', answerAnalysis: '오답 패턴과 확신 신호를 종합 분석합니다.', ruleBasedActions: 'rules.yaml의 패턴 교정 룰이 트리거됩니다.' }
        },
        3: {
            questionSets: [{
                title: '중장기 사고 습관 개선',
                questions: [
                    { text: '최근 1개월간의 침착도·확신도·정답률 추이를 비교한 사고 안정성 성장곡선은?', dataSources: ['calmness_trend', 'confidence_trend', 'correct_rate_trend'] },
                    { text: '감정·피로·집중도 데이터와 교차한 장기적 사고 습관 개선 방향(과속형→점검형, 회피형→근거형)은?', dataSources: ['emotion_data', 'fatigue_data', 'concentration_data', 'thinking_habit_improvement'] },
                    { text: '사고 습관 개선을 위한 단계별 훈련 계획은?', dataSources: ['training_plan', 'improvement_stages', 'habit_formation'] }
                ]
            }],
            ontology: [{ name: 'ThinkingHabitImprovement', description: '사고 습관 개선을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '침착도 변화 추이를 분석하여 중장기 사고 습관 개선 방향을 제시합니다.', answerAnalysis: '1개월간의 추이 데이터를 분석하여 성장곡선을 도출합니다.', ruleBasedActions: 'rules.yaml의 습관 개선 룰이 트리거됩니다.' }
        }
    };
    
    // Agent 09: 학습관리
    window.dataBasedQuestionSets.agent09 = {
        1: {
            questionSets: [{
                title: '학습관리 취약점 진단',
                questions: [
                    { text: '데이터 생성 빈도, 루틴 유지율, 포모도르 누락률, 감정기복, 시스템 신뢰도를 종합한 취약점은?', dataSources: ['data_generation_frequency', 'routine_maintenance_rate', 'pomodoro_missing_rate', 'emotion_fluctuation', 'system_reliability'] },
                    { text: '학습 관리력 저하의 근본 원인 패턴은 무엇인가?', dataSources: ['management_decline_pattern', 'root_cause_analysis'] },
                    { text: '등급화된 리스크 요약 리포트는?', dataSources: ['risk_level', 'risk_summary'] }
                ]
            }],
            ontology: [{ name: 'LearningManagementWeakness', description: '학습관리 취약점을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '학습관리 데이터 전체를 종합하여 취약점을 구조적으로 진단합니다.', answerAnalysis: '데이터 생성 빈도, 루틴 유지율 등을 종합 분석합니다.', ruleBasedActions: 'rules.yaml의 관리 취약점 진단 룰이 트리거됩니다.' }
        },
        2: {
            questionSets: [{
                title: '피드백 루프 재설계',
                questions: [
                    { text: '데이터 불균형(개념/문제/복습 간), 루틴 붕괴 주기, 자동화 저항 패턴을 분석한 우선순위 높은 피드백 영역은?', dataSources: ['data_imbalance', 'routine_collapse_cycle', 'automation_resistance'] },
                    { text: '계획-실행-검증 중 어느 단계의 피드백 루프를 재설계해야 하는가?', dataSources: ['planning_feedback', 'execution_feedback', 'verification_feedback'] },
                    { text: '우선순위 높은 피드백 영역에 맞는 행동전략과 알림 방식은?', dataSources: ['behavior_strategy', 'notification_method', 'feedback_priority'] }
                ]
            }],
            ontology: [{ name: 'FeedbackLoopRedesign', description: '피드백 루프 재설계를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '피드백 루프를 재설계하여 지속적 성장을 만듭니다.', answerAnalysis: '데이터 불균형과 루틴 붕괴 패턴을 분석합니다.', ruleBasedActions: 'rules.yaml의 피드백 루프 재설계 룰이 트리거됩니다.' }
        },
        3: {
            questionSets: [{
                title: '관리 안정성 향상',
                questions: [
                    { text: '최근 4주간의 데이터 일관성, 루틴 유지 성공률, 피드백 반영률, 시스템 의존도, 회복속도는?', dataSources: ['data_consistency', 'routine_success_rate', 'feedback_reflection_rate', 'system_dependency', 'recovery_speed'] },
                    { text: '관리 안정성 향상을 위한 습관화 루틴과 자동화 개입 포인트는?', dataSources: ['habitual_routine', 'automation_intervention_points'] },
                    { text: '단계별 관리 안정성 향상 시나리오는?', dataSources: ['stability_improvement_scenario', 'improvement_stages'] }
                ]
            }],
            ontology: [{ name: 'ManagementStability', description: '학습관리 안정성을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '관리 안정성을 높이기 위한 루틴적 습관과 시스템 연동 방식을 강화합니다.', answerAnalysis: '4주간의 데이터 일관성을 분석하여 안정성 향상 방안을 도출합니다.', ruleBasedActions: 'rules.yaml의 안정성 향상 룰이 트리거됩니다.' }
        }
    };
    
    // Agent 10: 개념노트
    window.dataBasedQuestionSets.agent10 = {
        1: {
            questionSets: [{
                title: '첫 개념 수업 설계',
                questions: [
                    { text: '학생의 개념 공부 패턴(필기량, 체류시간, TTS 활용, 재방문 패턴, 단계별 이해도)을 종합한 개념 이해 방식은?', dataSources: ['note_taking_amount', 'stay_time', 'tts_usage', 'revisit_pattern', 'step_by_step_understanding'] },
                    { text: '학생의 개념 이해 방식(시각형/청각형/요약형)에 맞는 첫 개념 수업 설명 방식과 교재 구성은?', dataSources: ['concept_understanding_style', 'learning_preferences', 'textbook_composition'] },
                    { text: '첫 개념 수업에서의 필기 유도 전략은?', dataSources: ['note_taking_habit', 'note_taking_preference', 'note_taking_tool'] }
                ]
            }],
            ontology: [{ name: 'ConceptLearningPattern', description: '개념 학습 패턴을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '개념 공부 패턴을 종합하여 첫 개념 수업을 설계합니다.', answerAnalysis: '필기량, 체류시간, TTS 활용 등 데이터를 분석합니다.', ruleBasedActions: 'rules.yaml의 개념 학습 룰이 트리거됩니다.' }
        },
        2: {
            questionSets: [{
                title: '개념 학습 루틴 최적화',
                questions: [
                    { text: '개념요약–이해–체크–예제–대표유형–테스트 간의 전이 효율은?', dataSources: ['concept_summary', 'understanding', 'check', 'example', 'representative_type', 'test'] },
                    { text: 'TTS·필기·체류 로그 간 상관관계와 오답 발생 구간은?', dataSources: ['tts_log', 'note_taking_log', 'stay_log', 'error_occurrence_section'] },
                    { text: '학생 개인에게 가장 적합한 개념학습 루틴 시퀀스(순서·시간·형태)와 집중 블록 구조는?', dataSources: ['optimal_sequence', 'time_allocation', 'activity_form', 'focus_block_structure'] }
                ]
            }],
            ontology: [{ name: 'ConceptRoutineOptimization', description: '개념 학습 루틴 최적화를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '개념 학습 데이터를 기반으로 가장 효율적인 루틴을 설계합니다.', answerAnalysis: '전이 효율과 상관관계를 분석하여 최적 루틴을 도출합니다.', ruleBasedActions: 'rules.yaml의 루틴 최적화 룰이 트리거됩니다.' }
        },
        3: {
            questionSets: [{
                title: '중장기 개념 학습 효율 향상',
                questions: [
                    { text: '오답 원인(이해 부족/주의력 저하/필기누락), 루틴 유지율, 단계별 이해도 편차, 재방문 이유 분석은?', dataSources: ['error_cause', 'routine_maintenance_rate', 'understanding_deviation', 'revisit_reason'] },
                    { text: '개념이해 안정성 지표, 청각/시각 입력 의존도, 루틴 지속 리스크 요인은?', dataSources: ['understanding_stability', 'input_dependency', 'routine_sustainability_risk'] },
                    { text: '향후 개념학습 지속성 향상 전략(습관·도구·피드백 루프)은?', dataSources: ['sustainability_improvement_strategy', 'habit_formation', 'tool_usage', 'feedback_loop'] }
                ]
            }],
            ontology: [{ name: 'ConceptLearningSustainability', description: '개념 학습 지속성을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '중장기적으로 개념 학습 효율을 높이기 위한 보완 사항을 분석합니다.', answerAnalysis: '오답 원인과 루틴 유지율을 종합 분석합니다.', ruleBasedActions: 'rules.yaml의 지속성 향상 룰이 트리거됩니다.' }
        }
    };
    
    // Agent 11: 문제노트
    window.dataBasedQuestionSets.agent11 = {
        1: {
            questionSets: [{
                title: '오답노트 기반 사고습관 분석',
                questions: [
                    { text: '학생의 오답노트 작성 방식, 필기량·시간·성찰문·오답원인 기록을 분석한 사고습관(성찰형/반복형/회피형/실전형)은?', dataSources: ['note_writing_method', 'note_amount', 'note_time', 'reflection_text', 'error_cause_record'] },
                    { text: '학생이 왜 틀리는지에 대한 자기이해 수준은?', dataSources: ['self_understanding_level', 'error_awareness'] },
                    { text: '각 유형에 맞는 첫 개입 루틴(오답정리 방식, 피드백 톤, 복습주기)은?', dataSources: ['error_review_method', 'feedback_tone', 'review_cycle'] }
                ]
            }],
            ontology: [{ name: 'ThinkingHabitFromNotes', description: '오답노트 기반 사고습관을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '오답노트 데이터를 종합하여 사고습관과 성찰패턴을 분석합니다.', answerAnalysis: '작성 방식과 성찰문을 분석하여 사고습관을 분류합니다.', ruleBasedActions: 'rules.yaml의 오답노트 분석 룰이 트리거됩니다.' }
        },
        2: {
            questionSets: [{
                title: '복습 루틴 및 교사 개입 최적화',
                questions: [
                    { text: '최근 3~5회 노트의 reflection_score, completeness_score, 오답유형, 복습효과를 종합한 루틴 안정도·지속력·개선도는?', dataSources: ['reflection_score', 'completeness_score', 'error_type', 'review_effectiveness'] },
                    { text: '복습주기 조정, 교사 피드백 강도, AI 자동 피드백 빈도 등 학습 루프 설계 요소 최적화는?', dataSources: ['review_cycle', 'teacher_feedback_intensity', 'ai_feedback_frequency'] },
                    { text: '학생의 루틴 안정도와 지속력을 고려한 개입 방향은?', dataSources: ['routine_stability', 'routine_persistence', 'intervention_direction'] }
                ]
            }],
            ontology: [{ name: 'ReviewRoutineOptimization', description: '복습 루틴 최적화를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '오답노트 패턴을 기반으로 복습 루틴과 교사 개입 방향을 최적화합니다.', answerAnalysis: 'reflection_score와 completeness_score를 분석합니다.', ruleBasedActions: 'rules.yaml의 복습 루틴 최적화 룰이 트리거됩니다.' }
        },
        3: {
            questionSets: [{
                title: '중장기 성장 루프 형성',
                questions: [
                    { text: '오답노트 축적 데이터에서 오류유형 분포, 성찰깊이 변화, 복습효과 지속기간 분석은?', dataSources: ['error_type_distribution', 'reflection_depth_change', 'review_effect_duration'] },
                    { text: '학생의 시그너처 루틴 진화단계(Level 1~5)와 다음 단계로 넘어가기 위한 구체적 루틴 행동 가이드는?', dataSources: ['signature_routine_level', 'routine_evolution_stage', 'next_stage_guide'] },
                    { text: '지속적인 성장 루프(성찰–복습–재도전)를 형성하기 위한 서술평가 빈도, 오답요약 방식, 재시도 간격은?', dataSources: ['narrative_assessment_frequency', 'error_summary_method', 'retry_interval'] }
                ]
            }],
            ontology: [{ name: 'GrowthLoopFormation', description: '성장 루프 형성을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '중장기적으로 성장하기 위한 오답노트 기반 습관과 루틴을 형성합니다.', answerAnalysis: '오류유형 분포와 성찰깊이 변화를 분석합니다.', ruleBasedActions: 'rules.yaml의 성장 루프 형성 룰이 트리거됩니다.' }
        }
    };
    
    // Agent 12: 휴식루틴
    window.dataBasedQuestionSets.agent12 = {
        1: {
            questionSets: [
                {
                    title: '휴식 패턴 및 회복 리듬 종합 분석',
                    questions: [
                        {
                            text: '휴식 버튼 사용 주기와 빈도가 학습 지속력에 어떤 영향을 미치는가? 요일별, 시간대별 휴식 패턴은 어떻게 나타나는가?',
                            dataSources: ['rest_button_clicked', 'rest_count', 'hourly_rest_frequency', 'daily_rest_frequency', 'weekly_rest_trend', 'rest_patterns']
                        },
                        {
                            text: '휴식 전후 집중도 변화를 측정했을 때, 회복 효과가 있는 휴식과 효과가 없는 휴식을 어떻게 구분할 수 있는가?',
                            dataSources: ['focus_level_before_rest', 'focus_level_after_rest', 'focus_level_history', 'learning_efficiency_before_rest', 'learning_efficiency_after_rest', 'recovery_effectiveness_index']
                        },
                        {
                            text: '피로 누적 지수 계산 시 집중 시간 감소율, 휴식 빈도 증가율, 학습 효율 감소율을 어떻게 종합하는가?',
                            dataSources: ['focus_duration_history', 'focus_duration_change_rate', 'rest_frequency_change_rate', 'learning_efficiency_history', 'learning_efficiency_change_rate', 'fatigue_index']
                        },
                        {
                            text: '감정 안정도 데이터를 기반으로 휴식 전후 감정 상태 변화를 어떻게 측정하고, 정서적 회복 효과를 평가하는가?',
                            dataSources: ['emotional_state_before_rest', 'emotional_state_after_rest', 'emotional_recovery_index', 'emotion_stability', 'rest_count']
                        },
                        {
                            text: '회복 루틴의 질적 수준(즉흥적·계획적·보상형)을 진단하기 위해 어떤 데이터를 분석해야 하는가?',
                            dataSources: ['rest_activity_type', 'rest_duration', 'rest_timing_pattern', 'rest_planning_level', 'rest_reward_type', 'rest_routine_quality']
                        },
                        {
                            text: '수업 중 휴식과 학습 전환이 자연스럽게 이어지도록 하는 리듬형 개입 전략을 어떻게 도출하는가?',
                            dataSources: ['rest_during_class', 'learning_transition', 'rhythm_intervention', 'focus_time_rest_alignment', 'optimal_rest_time_slots']
                        }
                    ]
                },
                {
                    title: '시간대별 및 학습량 기반 휴식 패턴 분석',
                    questions: [
                        {
                            text: '시간대별 휴식 패턴 분석에서 특정 시간대에 휴식이 집중되는 패턴을 어떻게 확인하고, 집중도 변화와의 연관성을 파악하는가?',
                            dataSources: ['hourly_rest_frequency', 'daily_rest_frequency', 'focus_level_by_time_slot', 'time_based_rest_pattern_analysis']
                        },
                        {
                            text: '학습 단원 난이도와 휴식 연관성 분석에서 어려운 단원을 공부할 때 휴식 빈도가 증가하는 패턴을 어떻게 확인하는가?',
                            dataSources: ['learning_activity_before_rest', 'unit_difficulty_before_rest', 'rest_frequency_by_unit_difficulty', 'difficult_unit_rest_pattern', 'rest_count']
                        },
                        {
                            text: '학습량(문제 수)과 휴식 필요성 관계 분석에서 문제 수와 학습 시간에 따른 적절한 학습 청크 크기를 어떻게 추천하는가?',
                            dataSources: ['problem_count_before_rest', 'study_duration_before_rest', 'rest_frequency_by_problem_count', 'optimal_problem_count_before_rest', 'optimal_learning_chunk_size']
                        },
                        {
                            text: '휴식 지속 시간과 회복 효과 관계 분석에서 최적 휴식 시간을 어떻게 추천하고, 과도한 휴식을 어떻게 감지하는가?',
                            dataSources: ['rest_duration', 'rest_duration_recovery_curve', 'optimal_rest_duration_by_activity', 'over_rest_detection', 'recovery_effectiveness_index']
                        }
                    ]
                },
                {
                    title: '주간 피로 누적 패턴 및 회복 탄력성 분석',
                    questions: [
                        {
                            text: '주단위 피로 누적 패턴 분석에서 월요일부터 금요일까지 피로가 점진적으로 증가하는 패턴을 어떻게 확인하는가?',
                            dataSources: ['rest_count', 'daily_rest_frequency', 'weekly_fatigue_trend', 'day_of_week_rest_pattern', 'fatigue_accumulation_rate']
                        },
                        {
                            text: '회복 탄력성 평가에서 휴식 후 회복 시간과 회복 정도를 측정하여 개인별 회복 탄력성을 어떻게 평가하는가?',
                            dataSources: ['recovery_time_history', 'recovery_effectiveness_history', 'recovery_resilience_score', 'average_recovery_time', 'average_recovery_effectiveness', 'rest_count']
                        },
                        {
                            text: '일시적 피로 vs 누적 피로 구분에서 피로 지속 기간과 외부 요인을 분석하여 어떻게 분류하는가?',
                            dataSources: ['fatigue_index', 'fatigue_duration', 'fatigue_duration_pattern', 'fatigue_type', 'sleep_hours', 'external_schedule_info']
                        }
                    ]
                }
            ],
            ontology: [
                {
                    name: 'RestPatternRecoveryRhythm',
                    description: '휴식 패턴과 회복 리듬을 온톨로지로 표현하여 학습 지속력 영향 분석에 활용 (Agent 12 핵심 온톨로지)'
                },
                {
                    name: 'TimeBasedRestPattern',
                    description: '시간대별 휴식 패턴(요일별, 시간대별)을 온톨로지로 표현하여 개인 최적 학습 시간대 추론에 활용'
                },
                {
                    name: 'LearningVolumeRestCorrelation',
                    description: '학습량(문제 수, 학습 시간)과 휴식 필요성 관계를 온톨로지로 표현하여 적절한 학습 청크 크기 추천에 활용'
                },
                {
                    name: 'RestDurationEffectiveness',
                    description: '휴식 지속 시간과 회복 효과 관계를 온톨로지로 표현하여 최적 휴식 시간 추천에 활용'
                },
                {
                    name: 'WeeklyFatigueAccumulation',
                    description: '주간 피로 누적 패턴을 온톨로지로 표현하여 장기 슬럼프 신호 감지에 활용'
                },
                {
                    name: 'RecoveryResilience',
                    description: '회복 탄력성(회복 시간, 회복 정도)을 온톨로지로 표현하여 개인화 전략 수립에 활용'
                }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 휴식 패턴과 회복 리듬을 종합하여 현재 학습 지속력에 미치는 영향을 분석합니다. rules.yaml의 S1 휴식 패턴 분석, S3 휴식 효과성 평가 룰과 직접 연계됩니다.',
                answerAnalysis: '휴식 버튼 사용 주기가 10회 이상이면 S1_R1 룰이 시간대별 패턴을 분석합니다. 휴식 전후 집중도 변화가 있으면 S3_R1 룰이 회복 효과를 측정합니다. 피로 누적 지수가 5 이상이면 S2_R1 룰이 피로도 지수를 계산합니다. 회복 탄력성 평가는 S5_R5 룰이 수행합니다.',
                ruleBasedActions: 'rules.yaml의 S1_R1~S1_R5 룰이 휴식 패턴을 분석하고, S2_R1~S2_R5 룰이 피로 누적을 감지하며, S3_R1~S3_R6 룰이 휴식 효과성을 평가합니다. S5_R5 룰이 회복 탄력성을 평가합니다.'
            }
        },
        2: {
            questionSets: [
                {
                    title: '피로 누적 최소화 및 개인화 회복 루틴 설계',
                    questions: [
                        {
                            text: '주간 피로 상승 패턴 분석에서 피로가 점진적으로 증가하는 패턴을 어떻게 감지하고, 피로 누적 신호를 어떻게 조기 경보하는가?',
                            dataSources: ['weekly_fatigue_trend', 'fatigue_accumulation_rate', 'focus_duration_trend', 'learning_efficiency_trend', 'trend_duration', 'fatigue_accumulation_early_warning']
                        },
                        {
                            text: '감정 회복 속도 측정에서 휴식 전후 감정 상태 변화를 어떻게 분석하고, 감정 회복이 충분하지 않은 경우 개인화된 회복 루틴을 어떻게 제안하는가?',
                            dataSources: ['emotional_state_before_rest', 'emotional_state_after_rest', 'emotional_recovery_index', 'emotional_recovery_by_activity', 'rest_activity_type', 'rest_count']
                        },
                        {
                            text: '외부 일정(학교 일정, 학원 일정) 영향 분석에서 외부 일정 변동이 피로에 미치는 영향을 어떻게 분석하고, 일시적 과부하와 누적된 패턴을 어떻게 구분하는가?',
                            dataSources: ['external_schedule_info', 'school_event_schedule', 'academy_class_schedule', 'school_schedule_fatigue_correlation', 'academy_schedule_fatigue_correlation', 'fatigue_index']
                        },
                        {
                            text: '휴식 중 감정 톤 변화 분석에서 휴식 활동 유형별 감정 변화를 어떻게 측정하고, 가장 효과적인 휴식 활동을 어떻게 추천하는가?',
                            dataSources: ['rest_activity_type', 'rest_emotion_tone', 'emotional_state_after_rest', 'effectiveness_by_activity_type', 'activity_effectiveness_ranking', 'optimal_rest_activities']
                        },
                        {
                            text: '개인별 회복 루틴(짧은 휴식/감각 전환/정서 안정 중심) 맞춤 설계에서 학습 스타일과 최적 집중 시간대를 어떻게 반영하는가?',
                            dataSources: ['learning_style', 'optimal_focus_time', 'personalized_rest_strategy', 'learning_style_rest_needs', 'focus_time_rest_alignment']
                        },
                        {
                            text: '학습-휴식 전환을 원활하게 만드는 리듬 재조정 알고리즘을 어떻게 설계하고, 최적 휴식 타이밍을 어떻게 예측하는가?',
                            dataSources: ['learning_rest_transition', 'rhythm_adjustment_algorithm', 'current_learning_duration', 'problem_count_since_last_rest', 'current_emotional_state', 'optimal_rest_timing', 'rest_urgency_score']
                        }
                    ]
                },
                {
                    title: '휴식 활동 유형별 효과성 및 감정 기반 최적화',
                    questions: [
                        {
                            text: '휴식 활동 유형별 효과 측정에서 눈 휴식, 스트레칭, 간식, 스마트폰, 산책, 음악 듣기, 명상 등 각 활동의 회복 효과를 어떻게 비교하는가?',
                            dataSources: ['rest_activity_type', 'recovery_effectiveness_index', 'effectiveness_by_activity_type', 'activity_effectiveness_ranking', 'rest_count']
                        },
                        {
                            text: '감정 상태별 휴식 활동 추천에서 짜증, 피로, 불안 등 현재 감정 상태에 따라 적절한 휴식 활동을 어떻게 매칭하는가?',
                            dataSources: ['emotional_state_before_rest', 'emotional_state_after_rest', 'emotional_state_activity_mapping', 'personalized_rest_activity', 'rest_activity_type']
                        },
                        {
                            text: '단원 난이도 기반 동적 휴식 조정에서 어려운 단원일 때 휴식 간격을 줄이고, 쉬운 단원일 때 연속 학습을 권장하는 전략을 어떻게 구현하는가?',
                            dataSources: ['unit_difficulty', 'current_learning_unit', 'difficulty_based_rest_interval', 'dynamic_rest_interval', 'unit_difficulty_before_rest']
                        },
                        {
                            text: '목표 달성 기반 휴식 보상 시스템에서 학습 목표 달성 시 보상 휴식 활동과 연장된 휴식 시간을 어떻게 제안하는가?',
                            dataSources: ['learning_goal_achieved', 'rest_reward_enabled', 'rest_reward_activity', 'extended_rest_duration']
                        }
                    ]
                },
                {
                    title: '예방적 조정 및 자동 루틴 조정',
                    questions: [
                        {
                            text: '피로 누적 사전 감지에서 집중 시간과 학습 효율 트렌드가 지속적으로 감소할 때 조기 경보를 어떻게 발동하는가?',
                            dataSources: ['focus_duration_trend', 'learning_efficiency_trend', 'trend_duration', 'fatigue_accumulation_early_detection', 'fatigue_trend_severity', 'preventive_rest_adjustment']
                        },
                        {
                            text: '휴식 루틴 자동 조정에서 피로도 지수가 7 이상이고 5일 이상 지속될 때 휴식 간격과 휴식 시간을 어떻게 자동 조정하는가?',
                            dataSources: ['fatigue_index', 'fatigue_duration', 'rest_interval', 'rest_duration', 'rest_activity_type', 'automatic_rest_routine_adjustment']
                        },
                        {
                            text: '과도한 휴식 감지에서 휴식 시간이 10분 초과이고 학습 효율이 오히려 하락하는 경우를 어떻게 감지하고 조정하는가?',
                            dataSources: ['rest_duration', 'learning_efficiency_after_rest', 'learning_efficiency_before_rest', 'over_rest_detection', 'over_rest_pattern', 'optimal_rest_duration_adjustment']
                        }
                    ]
                }
            ],
            ontology: [
                {
                    name: 'FatigueMinimizationStrategy',
                    description: '피로 누적 최소화 전략(주간 패턴, 조기 감지, 자동 조정)을 온톨로지로 표현하여 몰입 회복에 활용'
                },
                {
                    name: 'PersonalizedRecoveryRoutine',
                    description: '개인별 회복 루틴(짧은 휴식/감각 전환/정서 안정)을 온톨로지로 표현하여 맞춤 설계에 활용'
                },
                {
                    name: 'RestActivityEffectiveness',
                    description: '휴식 활동 유형별 효과성(눈 휴식, 스트레칭, 산책 등)을 온톨로지로 표현하여 최적 활동 추천에 활용'
                },
                {
                    name: 'EmotionBasedRestActivity',
                    description: '감정 상태별 휴식 활동 매핑을 온톨로지로 표현하여 맞춤형 회복 루틴 설계에 활용'
                },
                {
                    name: 'DynamicRestAdjustment',
                    description: '동적 휴식 조정(단원 난이도 기반, 목표 달성 보상)을 온톨로지로 표현하여 학습-휴식 전환 최적화에 활용'
                },
                {
                    name: 'PreventiveRestStrategy',
                    description: '예방적 휴식 전략(조기 감지, 자동 조정)을 온톨로지로 표현하여 피로 누적 예방에 활용'
                }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 휴식 습관과 정서 반응을 기반으로 피로 누적을 최소화하고 몰입을 회복시키는 루틴을 최적화합니다. rules.yaml의 S2 피로도 누적 감지, S4 개인화 휴식 전략, S5 예방적 조정 알고리즘 룰과 직접 연계됩니다.',
                answerAnalysis: '주간 피로 상승 패턴은 S1_R5 룰이 분석하고, 감정 회복 속도는 S3_R6 룰이 측정합니다. 외부 일정 영향은 S7_R1~S7_R2 룰이 분석합니다. 휴식 활동 유형별 효과는 S3_R2 룰이 측정하고, 감정 상태별 활동 추천은 S4_R5 룰이 수행합니다. 피로 누적 조기 감지는 S5_R1 룰이 수행하고, 자동 조정은 S5_R3 룰이 수행합니다.',
                ruleBasedActions: 'rules.yaml의 S2_R1~S2_R5 룰이 피로 누적을 감지하고, S3_R2 룰이 활동 유형별 효과를 측정하며, S4_R1~S4_R5 룰이 개인화 전략을 수립합니다. S5_R1~S5_R5 룰이 예방적 조정을 수행합니다.'
            }
        },
        3: {
            questionSets: [
                {
                    title: '장기 피로 누적 및 회복탄력성 추세 분석',
                    questions: [
                        {
                            text: '장기 데이터에서 피로 누적 지수 추세를 어떻게 분석하고, 피로가 지속적으로 증가하는 패턴을 어떻게 감지하는가?',
                            dataSources: ['long_term_fatigue_index', 'fatigue_index', 'fatigue_duration', 'fatigue_accumulation_rate', 'weekly_fatigue_trend', 'fatigue_accumulation_detected']
                        },
                        {
                            text: '회복탄력성 추세 분석에서 휴식 후 회복 시간과 회복 정도가 시간에 따라 어떻게 변화하는지, 회복 탄력성이 저하되는 신호를 어떻게 감지하는가?',
                            dataSources: ['resilience_trend', 'recovery_resilience_score', 'recovery_time_history', 'recovery_effectiveness_history', 'average_recovery_time', 'average_recovery_effectiveness']
                        },
                        {
                            text: '감정루틴 일관성 분석에서 휴식 전후 감정 패턴이 일관되게 유지되는지, 감정 기복이 증가하는 패턴을 어떻게 감지하는가?',
                            dataSources: ['emotion_routine_consistency', 'emotional_state_before_rest', 'emotional_state_after_rest', 'emotional_recovery_index', 'emotional_volatility', 'rest_count']
                        },
                        {
                            text: '슬럼프 예측 신호 분석에서 장기 학습 프로젝트 진행 중 슬럼프 진입 가능성을 어떻게 예측하고, 원인군(정서/인지/환경)을 어떻게 분류하는가?',
                            dataSources: ['slump_prediction_signal', 'long_term_slump_prevention', 'slump_root_causes', 'fatigue_index', 'fatigue_duration', 'learning_efficiency']
                        }
                    ]
                },
                {
                    title: '정서 회복력 강화 루틴 및 예방형 휴식전략',
                    questions: [
                        {
                            text: '지속적 성장을 위한 정서 회복력 강화 루틴을 어떻게 설계하고, 감정 기반 자기조절 능력을 어떻게 향상시키는가?',
                            dataSources: ['emotional_recovery_strengthening', 'self_regulation_routine', 'emotion_regulation_ability', 'emotional_recovery_routine', 'recovery_resilience_score']
                        },
                        {
                            text: '보편적 피로 구간(예: 시험기간)에 대비한 예방형 휴식전략을 어떻게 설계하고, 시험 대비 2주차에 피로 누적 신호가 상승할 때 어떻게 대응하는가?',
                            dataSources: ['preventive_rest_strategy', 'exam_period_fatigue', 'exam_weeks_remaining', 'school_schedule_fatigue_correlation', 'fatigue_accumulation_early_warning']
                        },
                        {
                            text: '수면 패턴 통합 분석에서 수면 시간과 수면 질이 피로도에 미치는 영향을 어떻게 분석하고, 수면 개선 전략을 어떻게 제안하는가?',
                            dataSources: ['sleep_hours', 'sleep_quality', 'sleep_fatigue_correlation', 'sleep_impact_on_fatigue', 'sleep_improvement_strategy', 'fatigue_index']
                        },
                        {
                            text: '기상 패턴과 학습 시간대 최적화에서 아침형/올빼미형을 어떻게 분류하고, 개인별 최적 학습 스케줄을 어떻게 추천하는가?',
                            dataSources: ['wake_time', 'optimal_focus_time', 'wake_time_focus_alignment', 'chronotype', 'optimal_learning_schedule']
                        }
                    ]
                },
                {
                    title: '장기 슬럼프 예방 및 종합 분석',
                    questions: [
                        {
                            text: '장기 슬럼프 예방에서 2주 이상 피로가 누적되고 학습 효율이 저하될 때 적극적인 개입 전략을 어떻게 제안하는가?',
                            dataSources: ['long_term_slump_prevention', 'fatigue_index', 'fatigue_duration', 'learning_efficiency', 'slump_root_causes', 'aggressive_intervention_strategy']
                        },
                        {
                            text: '휴식 루틴 종합 분석에서 휴식 패턴, 피로 누적 상태, 휴식 효과성을 종합적으로 분석하여 최적화 방안을 어떻게 제시하는가?',
                            dataSources: ['comprehensive_rest_routine_analysis', 'comprehensive_rest_pattern', 'fatigue_accumulation_status', 'rest_effectiveness_evaluation', 'personalized_optimization_opportunities', 'rest_count']
                        },
                        {
                            text: '맞춤형 휴식 루틴 코칭 템플릿 생성에서 학생의 휴식 유형, 피로도, 학습 스타일을 종합하여 어떻게 개인화된 코칭 메시지를 제공하는가?',
                            dataSources: ['rest_routine_coaching_template', 'rest_type', 'fatigue_index', 'learning_style', 'personalized_coaching_message', 'custom_rest_routine', 'rest_activity_schedule']
                        },
                        {
                            text: '장기적으로 안정적인 학습 리듬을 유지하기 위한 휴식 관리 우선순위를 어떻게 결정하고, 핵심 관리 포인트를 어떻게 식별하는가?',
                            dataSources: ['long_term_rhythm_stability', 'rest_management_priority', 'comprehensive_rest_routine_analysis', 'rest_routine_optimization']
                        }
                    ]
                },
                {
                    title: '에이전트 연동 및 종합 피드백',
                    questions: [
                        {
                            text: '귀가검사 에이전트 연동 시 오늘 하루의 휴식 루틴 효과를 어떻게 요약하고, 피로 누적 지수, 회복 점수, 감정 안정도를 어떻게 전달하는가?',
                            dataSources: ['agent_integration_summary', 'rest_routine_summary', 'fatigue_index', 'recovery_score', 'emotional_stability', 'daily_summary_requested']
                        },
                        {
                            text: '감정-인지 복합 분석에서 감정 상태와 휴식 필요성을 어떻게 예측하고, 스트레스 지수를 어떻게 계산하여 휴식 전략을 조정하는가?',
                            dataSources: ['emotional_state_rest_need_prediction', 'emotional_log', 'current_emotional_state', 'stress_index_calculation', 'stress_rest_correlation', 'rest_strategy_by_stress']
                        },
                        {
                            text: '학습 동기와 휴식 효과 관계 분석에서 동기 수준이 높을 때 휴식이 학습 효율을 더 크게 향상시키는 패턴을 어떻게 분석하는가?',
                            dataSources: ['motivation_rest_effectiveness_relationship', 'motivation_level', 'recovery_effectiveness_index', 'motivation_rest_effectiveness_correlation', 'rest_count']
                        }
                    ]
                }
            ],
            ontology: [
                {
                    name: 'LongTermFatigueTrend',
                    description: '장기 피로 누적 지수 추세를 온톨로지로 표현하여 지속적 성장 분석에 활용'
                },
                {
                    name: 'RecoveryResilienceTrend',
                    description: '회복탄력성 추세(회복 시간, 회복 정도 변화)를 온톨로지로 표현하여 장기 리듬 유지에 활용'
                },
                {
                    name: 'EmotionRoutineConsistency',
                    description: '감정루틴 일관성을 온톨로지로 표현하여 정서 회복력 강화에 활용'
                },
                {
                    name: 'SlumpPredictionSignal',
                    description: '슬럼프 예측 신호(정서/인지/환경 원인군)를 온톨로지로 표현하여 예방형 전략 수립에 활용'
                },
                {
                    name: 'EmotionalRecoveryStrengthening',
                    description: '정서 회복력 강화 루틴을 온톨로지로 표현하여 지속적 성장에 활용'
                },
                {
                    name: 'PreventiveRestStrategy',
                    description: '예방형 휴식전략(시험기간 대비)을 온톨로지로 표현하여 보편적 피로 구간 대응에 활용'
                },
                {
                    name: 'SleepPatternIntegration',
                    description: '수면 패턴 통합 분석(수면 시간, 수면 질, 기상 패턴)을 온톨로지로 표현하여 피로 원인 분석에 활용'
                },
                {
                    name: 'ComprehensiveRestRoutineAnalysis',
                    description: '휴식 루틴 종합 분석(패턴, 피로 상태, 효과성)을 온톨로지로 표현하여 최적화 방안 도출에 활용'
                }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 장기적으로 안정적인 학습 리듬을 유지하기 위한 휴식 관리 방안을 분석합니다. rules.yaml의 S5 예방적 조정 알고리즘, S6 감정-인지 복합 분석, S7 외부 요인 통합 분석, S8 종합 분석 및 코칭 룰과 직접 연계됩니다.',
                answerAnalysis: '장기 피로 누적 지수는 S2_R1 룰이 계산하고, 회복탄력성 추세는 S5_R5 룰이 평가합니다. 슬럼프 예측은 S5_R4 룰이 수행하고, 정서 회복력 강화는 S6_R1~S6_R4 룰이 분석합니다. 수면 패턴 통합은 S7_R3 룰이 분석하고, 종합 분석은 S8_R1 룰이 수행합니다. 에이전트 연동은 S8_R5 룰이 수행합니다.',
                ruleBasedActions: 'rules.yaml의 S5_R1~S5_R5 룰이 예방적 조정을 수행하고, S6_R1~S6_R4 룰이 감정-인지 복합 분석을 수행하며, S7_R1~S7_R4 룰이 외부 요인을 통합 분석합니다. S8_R1~S8_R5 룰이 종합 분석 및 코칭을 수행합니다.'
            }
        }
    };
    
    // Agent 13: 학습이탈 (Learning Dropout)
    window.dataBasedQuestionSets.agent13 = {
        1: { // 포괄형 질문 1: 학습 몰입 단절 조기 탐지
            questionSets: [{
                title: '학습 몰입 단절 조기 탐지',
                questions: [
                    { text: '최근 포모도르 패턴, 감정 변화율, 필기량 추세, 목표 입력 지연, 루틴 붕괴 시점을 통합 분석한 이탈 전조(지연, 피로, 권태, 산만)는?', dataSources: ['pomodoro_pattern', 'emotion_change_rate', 'note_taking_trend', 'goal_input_delay', 'routine_collapse_point'] },
                    { text: '이탈 전조를 예측하고 즉각적 개입 타이밍·메시지·행동 루틴은?', dataSources: ['dropout_prediction', 'intervention_timing', 'intervention_message', 'action_routine'] },
                    { text: '학습 몰입 흐름이 어느 시점에서 끊기고 있는지, 그 원인은?', dataSources: ['immersion_break_point', 'dropout_cause'] }
                ]
            }],
            ontology: [
                { name: 'DropoutEarlyDetection', description: '학습 이탈 조기 탐지를 온톨로지로 표현 (Agent 13 핵심 온톨로지)' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 학습 몰입 흐름이 어느 시점에서 끊기고 있는지 조기 탐지하고 예방 개입을 설계합니다. rules.yaml의 S0_R1~S0_R6, S1_R1~S1_R3, S8_R1~S8_R4 룰과 직접 연계됩니다.',
                answerAnalysis: '포모도르 패턴은 S8_R1 룰이 분석하고, 감정 변화율은 Agent 05의 emotion 데이터를 활용합니다. 필기량 추세는 note_taking_trend로 추적하며, 목표 입력 지연은 goal_input_delay로 모니터링합니다. 루틴 붕괴 시점은 S1_R1 룰이 탐지합니다.',
                ruleBasedActions: 'rules.yaml의 S0_R1~S0_R6 룰이 수학 특화 정보를 수집하고, S1_R1~S1_R3 룰이 이탈 조기 탐지 및 개입을 수행하며, S8_R1~S8_R4 룰이 기본 이탈 탐지 및 위험도 평가를 수행합니다. Agent 05(감정 분석)와 Agent 18(시그너처 루틴)과 연계됩니다.'
            }
        },
        2: { // 포괄형 질문 2: 집중 회복력 향상
            questionSets: [{
                title: '집중 회복력 향상',
                questions: [
                    { text: '복귀 성공률, 이탈 후 복귀 지연시간, 감정 회복속도, 피로도 누적지수, 루틴 유지 안정도를 종합 분석한 복귀 루프 성공 확률은?', dataSources: ['return_success_rate', 'return_delay_after_dropout', 'emotion_recovery_speed', 'fatigue_accumulation_index', 'routine_maintenance_stability'] },
                    { text: '복귀 루프(이탈→개입→복귀→유지) 성공 확률을 높이는 맞춤형 루틴 보정 전략(시간·강도·보상형)은?', dataSources: ['return_loop_strategy', 'routine_adjustment', 'time_intensity_reward'] },
                    { text: '집중 회복력을 높이기 위한 루틴과 환경 변수 조정은?', dataSources: ['concentration_recovery', 'routine_adjustment', 'environment_variables'] }
                ]
            }],
            ontology: [
                { name: 'ConcentrationRecovery', description: '집중 회복력을 온톨로지로 표현 (Agent 13 핵심 온톨로지)' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 집중 회복력을 높이기 위한 루틴과 환경 변수를 조정합니다. rules.yaml의 S8_R3, S1_R2, S2_R1~S2_R3 룰과 직접 연계됩니다.',
                answerAnalysis: '복귀 성공률은 return_success_rate로 추적하고, 이탈 후 복귀 지연시간은 return_delay_after_dropout으로 측정합니다. 감정 회복속도는 Agent 05의 emotion_recovery_speed를 활용하며, 피로도 누적지수는 Agent 05와 Agent 12의 fatigue 데이터를 종합합니다. 루틴 유지 안정도는 Agent 18의 routine_maintenance_stability를 활용합니다.',
                ruleBasedActions: 'rules.yaml의 S8_R3 룰이 루틴 보정을 수행하고, S1_R2 룰이 단원별 맞춤형 개입을 제공하며, S2_R1~S2_R3 룰이 난이도별 이탈 분석을 수행합니다. Agent 05(감정 회복), Agent 12(휴식 루틴), Agent 18(루틴 조정)과 연계됩니다.'
            }
        },
        3: { // 포괄형 질문 3: 자기조절 루틴 구축
            questionSets: [{
                title: '자기조절 루틴 구축',
                questions: [
                    { text: '주간 이탈 트렌드, 감정·휴식 데이터, 교사 개입 효과, 피드백 내성도, 복귀 루프 완결률을 기반으로 한 단기 개입(즉시 리포커스)과 중장기 개입(자기조절 루틴·정서 회복 루틴) 병행 설계는?', dataSources: ['weekly_dropout_trend', 'emotion_rest_data', 'teacher_intervention_effect', 'feedback_tolerance', 'return_loop_completion_rate'] },
                    { text: '자기조절 루틴과 정서 회복 루틴 설계는?', dataSources: ['self_regulation_routine', 'emotional_recovery_routine'] },
                    { text: '장기적으로 이탈이 줄고 자기조절 루틴이 자리잡도록 하는 피드백 체계는?', dataSources: ['long_term_dropout_reduction', 'self_regulation_establishment', 'feedback_system'] }
                ]
            }],
            ontology: [
                { name: 'SelfRegulationRoutine', description: '자기조절 루틴을 온톨로지로 표현 (Agent 13 핵심 온톨로지)' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 장기적으로 이탈이 줄고 자기조절 루틴이 자리잡도록 피드백 체계를 구축합니다. rules.yaml의 S8_R4, S3_R1~S3_R3, S4_R1~S4_R3 룰과 직접 연계됩니다.',
                answerAnalysis: '주간 이탈 트렌드는 weekly_dropout_trend로 분석하고, 감정·휴식 데이터는 Agent 05와 Agent 12의 데이터를 종합합니다. 교사 개입 효과는 Agent 06의 teacher_intervention_effect를 활용하며, 피드백 내성도는 feedback_tolerance로 측정합니다. 복귀 루프 완결률은 return_loop_completion_rate로 추적합니다.',
                ruleBasedActions: 'rules.yaml의 S8_R4 룰이 에스컬레이션을 수행하고, S3_R1~S3_R3 룰이 학습 단계별 이탈 분석을 수행하며, S4_R1~S4_R3 룰이 학원 맥락 이탈 분석을 수행합니다. Agent 05(감정/휴식 데이터), Agent 06(교사 개입 효과), Agent 18(자기조절 루틴)과 연계됩니다.'
            }
        }
    };
    
    // Agent 14: 현재위치 (Current Position)
    window.dataBasedQuestionSets.agent14 = {
        1: { // 포괄형 질문 1: 현재 학습 진행 상태 정밀 진단
            questionSets: [{
                title: '현재 학습 진행 상태 정밀 진단',
                questions: [
                    { text: '수학일기 기록, 진도율, 지연 패턴, 감정곡선, 포모도르 리듬을 종합 분석한 현재 학습 위치는?', dataSources: ['math_diary_record', 'progress_rate', 'delay_pattern', 'emotion_curve', 'pomodoro_rhythm'] },
                    { text: '학생이 어디까지 와 있고, 어느 지점에서 속도나 이해가 끊겼는지 현직 수학 선생님 수준으로 설명은?', dataSources: ['current_position', 'break_point', 'speed_interruption', 'understanding_interruption'] },
                    { text: '현재 수학 학습 진행 상태를 정밀하게 진단한 결과는?', dataSources: ['comprehensive_diagnosis', 'current_status'] }
                ]
            }],
            ontology: [
                { name: 'CurrentLearningPosition', description: '현재 학습 위치를 온톨로지로 표현 (Agent 14 핵심 온톨로지)' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 현재 수학 학습 진행 상태를 정밀하게 진단합니다. rules.yaml의 S0_R1~S0_R3 룰과 직접 연계됩니다.',
                answerAnalysis: '수학일기 기록은 math_diary_record로 추적하고, 진도율은 progress_rate로 계산합니다. 지연 패턴은 delay_pattern으로 분석하며, 감정 곡선은 Agent 05의 emotion_curve를 활용합니다. 포모도르 리듬은 Agent 07의 pomodoro_rhythm을 활용합니다.',
                ruleBasedActions: 'rules.yaml의 S0_R1 룰이 기본 진행 상태를 계산하고, S0_R2 룰이 포모도르 리듬 패턴을 분석하며, S0_R3 룰이 감정 곡선을 분석합니다. Agent 05(감정 곡선), Agent 07(포모도르 리듬), Agent 12(리듬 점수)와 연계됩니다.'
            }
        },
        2: { // 포괄형 질문 2: 학습 단계별 강약점 구조적 정리
            questionSets: [{
                title: '학습 단계별 강약점 구조적 정리',
                questions: [
                    { text: '단원별/난이도별/단계별(개념–유형–심화–기출) 데이터를 토대로 각 단계에서의 이해도·진도율·소요시간·오류유형 분석은?', dataSources: ['unit_by_unit_data', 'difficulty_level_data', 'stage_by_stage_data', 'understanding_rate', 'progress_rate', 'time_spent', 'error_type'] },
                    { text: '어디서 병목이 발생하는지, 어떤 구간이 원활한지 시각적 구분은?', dataSources: ['bottleneck_identification', 'smooth_section_identification'] },
                    { text: '학습 단계별 강약점을 구조적으로 정리한 결과는?', dataSources: ['strength_weakness_analysis', 'structural_summary'] }
                ]
            }],
            ontology: [
                { name: 'LearningStageStrengthWeakness', description: '학습 단계별 강약점을 온톨로지로 표현 (Agent 14 핵심 온톨로지)' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 학습 단계별 강약점을 구조적으로 정리합니다. rules.yaml의 S1_R1~S1_R3, S2_R1~S2_R3, S3_R1~S3_R3 룰과 직접 연계됩니다.',
                answerAnalysis: '단원별 데이터는 unit_by_unit_data로 분석하고, 난이도별 데이터는 difficulty_level_data로 분석합니다. 단계별 데이터는 stage_by_stage_data로 분석하며, 이해도는 Agent 10의 understanding_rate를 활용합니다. 오류 유형은 Agent 11의 error_type을 활용합니다.',
                ruleBasedActions: 'rules.yaml의 S1_R1~S1_R3 룰이 단원별 진도를 분석하고, S2_R1~S2_R3 룰이 난이도별 진도를 분석하며, S3_R1~S3_R3 룰이 학습 단계별 진도를 분석합니다. Agent 04(취약점 분석), Agent 10(개념 이해도), Agent 11(오류 유형)과 연계됩니다.'
            }
        },
        3: { // 포괄형 질문 3: 다음 수업 및 개입 방향 설계
            questionSets: [{
                title: '다음 수업 및 개입 방향 설계',
                questions: [
                    { text: '진행률, 감정 곡선, 리듬 점수, 위험도 지수를 종합한 현재 시점에서의 의사결정(심화로 갈지, 복습으로 돌아갈지, 휴식 리듬을 조정할지)은?', dataSources: ['progress_rate', 'emotion_curve', 'rhythm_score', 'risk_index'] },
                    { text: '교사 수준으로 의사결정 로직을 제시한 다음 수업 방향은?', dataSources: ['decision_logic', 'next_class_direction'] },
                    { text: '현재 학습 위치를 기준으로 한 다음 수업과 개입 방향 설계는?', dataSources: ['current_position_based_design', 'intervention_direction'] }
                ]
            }],
            ontology: [
                { name: 'NextClassInterventionDesign', description: '다음 수업 및 개입 방향 설계를 온톨로지로 표현 (Agent 14 핵심 온톨로지)' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 현재 학습 위치를 기준으로 다음 수업과 개입 방향을 설계합니다. rules.yaml의 S4_R1~S4_R3, S5_R1~S5_R3 룰과 직접 연계됩니다.',
                answerAnalysis: '진행률은 progress_rate로 계산하고, 감정 곡선은 Agent 05의 emotion_curve를 활용합니다. 리듬 점수는 Agent 12의 rhythm_score를 활용하며, 위험도 지수는 Agent 13의 risk_index를 활용합니다.',
                ruleBasedActions: 'rules.yaml의 S4_R1~S4_R3 룰이 종합 의사결정을 수행하고, S5_R1~S5_R3 룰이 다음 수업 방향을 설계합니다. Agent 05(감정 곡선), Agent 12(리듬 점수), Agent 13(위험도 지수)와 연계됩니다.'
            }
        }
    };
    
    // Agent 15: 문제재정의
    // Agent 15: 문제재정의 (상세 작성)
    window.dataBasedQuestionSets.agent15 = {
        1: {
            questionSets: [
                {
                    title: '반복 패턴 기반 문제 재정의 및 근본 원인 분석',
                    questions: [
                        {
                            text: '최근 2주간 점수, 목표 달성률, 몰입지표가 동반 하락한 경우, 수학 단원별 성과 하락 패턴과 오류 유형 증가를 어떻게 분석하는가?',
                            dataSources: ['recent_2weeks_performance', 'score_decline', 'goal_completion_rate', 'immersion_indicator', 'agent_data.agent10_data.concept_scores', 'agent_data.agent11_data.error_pattern', 'math_unit_vulnerability']
                        },
                        {
                            text: '단원별 취약점 정보(단원명, 취약 유형, 빈도, 심각도)를 기반으로 선행 단원 완료 여부와 관련 단원 영향도를 어떻게 평가하는가?',
                            dataSources: ['math_unit_vulnerability', 'prerequisite_unit_completion', 'related_units_impact', 'math_unit_relations']
                        },
                        {
                            text: '학생 수준(하위권/중위권/상위권)에 따라 문제 재정의를 어떻게 차별화하는가? 하위권은 개념 이해 문제, 상위권은 심화 문제 해결 능력 문제로 재정의하는 기준은?',
                            dataSources: ['student_level.overall_level', 'student_level.unit_level', 'student_level.recent_trend', 'goal_completion_rate', 'concept_test_scores']
                        },
                        {
                            text: '수학 오류 유형(계산 실수 vs 개념 오류 vs 응용 실패)을 어떻게 분류하고, 각 유형별로 다른 대응 전략을 어떻게 수립하는가?',
                            dataSources: ['math_error_types.calculation_error', 'math_error_types.concept_error', 'math_error_types.application_error', 'agent_data.agent11_data.error_pattern']
                        },
                        {
                            text: '오답, 진도 지연, 감정 변동 패턴을 근거로 겉으로 드러난 문제를 재정의하고, 인지·정서·습관·환경 요인 중 어느 것이 근본 원인인지 어떻게 구분하는가?',
                            dataSources: ['repeated_error_pattern', 'progress_delay_pattern', 'emotion_fluctuation_pattern', 'cognitive_factor', 'emotional_factor', 'habit_factor', 'environment_factor']
                        },
                        {
                            text: '표준 진단코드(C01~C09)로 매핑하고 우선순위 3개를 선정하는 기준과 각각의 실행 가능한 개선 시나리오는 무엇인가?',
                            dataSources: ['diagnostic_code', 'priority_3', 'improvement_scenario', 'standard_diagnosis_codes']
                        }
                    ]
                },
                {
                    title: '학원 맥락 및 학습이탈 문제 재정의',
                    questions: [
                        {
                            text: '학원 수업 이해도가 50% 미만이고 학습이탈이 발생한 경우, 학원 맥락을 반영하여 예습/복습 문제로 재정의하는 기준은?',
                            dataSources: ['academy_context.academy_class_understanding', 'academy_context.academy_homework_completion_rate', 'academy_context.academy_progress', 'agent_data.agent13_data.dropout_events']
                        },
                        {
                            text: '학원 과제 완료율이 80% 미만인 경우, 학원 과제 시간 관리 문제로 재정의하고 우선순위를 상향 조정하는 근거는?',
                            dataSources: ['academy_context.academy_homework_completion_rate', 'academy_context.academy_class_understanding', 'time_management_issue']
                        }
                    ]
                }
            ],
            ontology: [
                {
                    name: 'ProblemRedefinition',
                    description: '문제 재정의(근본 원인/진단코드)를 온톨로지로 표현 (Agent 15 핵심 온톨로지)'
                },
                {
                    name: 'MathUnitVulnerability',
                    description: '수학 단원별 취약점(단원명, 취약 유형, 빈도, 심각도)을 온톨로지로 표현하여 단원별 문제 재정의에 활용'
                },
                {
                    name: 'MathErrorTypeClassification',
                    description: '수학 오류 유형 분류(계산 실수/개념 오류/응용 실패)를 온톨로지로 표현하여 오류 유형별 맞춤형 대응 전략 수립에 활용'
                },
                {
                    name: 'StudentLevelDifferentiation',
                    description: '학생 수준별 차별화(하위권/중위권/상위권)를 온톨로지로 표현하여 수준별 문제 재정의에 활용'
                },
                {
                    name: 'AcademyContext',
                    description: '학원 맥락 정보(수업 이해도, 과제 완료율, 진도)를 온톨로지로 표현하여 학원 특화 문제 재정의에 활용'
                },
                {
                    name: 'RootCauseAnalysis',
                    description: '근본 원인 분석(인지·정서·습관·환경 요인)을 온톨로지로 표현하여 문제 재정의의 핵심 근거로 활용'
                }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 반복 패턴을 근거로 문제를 재정의하고 근본 원인을 구분하며, 수학 특화 분석(단원별 취약점, 오류 유형, 학생 수준)을 포함합니다. rules.yaml의 S1, S2, S3 시나리오와 직접 연계됩니다.',
                answerAnalysis: '수학 단원별 성과 하락은 S1_R1 룰이 분석하고, 단원별 취약점은 S1_R2 룰이 평가합니다. 학생 수준별 차별화는 S1_R3 룰이 수행합니다. 수학 오류 유형 분류는 S3_R1, S3_R2 룰이 수행하고, 학원 맥락 반영은 S2_R1, S5_R1 룰이 평가합니다.',
                ruleBasedActions: 'rules.yaml의 S1_R1~S1_R3 룰이 학습 성과 하락을 분석하고, S3_R1~S3_R3 룰이 동일 오답 반복을 진단하며, S2_R1, S5_R1 룰이 학원 맥락을 반영합니다. S0_R1~S0_R5 룰이 필수 정보를 수집합니다.'
            }
        },
        2: {
            questionSets: [
                {
                    title: '루틴 붕괴 원인 진단 및 표준코드 분류',
                    questions: [
                        {
                            text: '포모도로 완료율이 50% 미만이고 계획 대비 실제 진도가 60% 미만인 경우, 루틴 불안정의 원인을 인지·행동·정서 관점에서 어떻게 재정의하는가?',
                            dataSources: ['agent_data.agent07_data.pomodoro_completion_rate', 'agent_data.agent14_data.actual_progress_vs_planned', 'agent_data.agent09_data.learning_routine', 'math_unit_vulnerability']
                        },
                        {
                            text: '수학 학습 시간 배분 문제로 확인된 경우, 단원별 학습 시간 재배분 문제로 재정의하는 기준과 우선순위는?',
                            dataSources: ['agent_data.agent07_data.pomodoro_completion_rate', 'math_unit_vulnerability', 'unit_study_time_allocation', 'agent_data.agent14_data.current_position']
                        },
                        {
                            text: '학습 계획 시간과 실제 수행시간 차이가 심한 경우, 시간관리 실패의 근본 원인을 어떻게 분석하고 실행 습관 재설계 전략을 어떻게 수립하는가?',
                            dataSources: ['agent_data.agent09_data.planning_time', 'agent_data.agent09_data.actual_time', 'time_management_issue', 'agent_data.agent06_data.teacher_feedback', 'agent_data.agent14_data.learning_rhythm']
                        },
                        {
                            text: '표준코드 체계에 따라 전략 불일치/시간관리/정서 리듬 문제 중 어디에 속하는지 판별하는 기준과 각 분류별 루틴 회복 또는 전략 재설계 방안은?',
                            dataSources: ['strategy_mismatch', 'time_management_issue', 'emotional_rhythm_issue', 'agent_data.agent01_data.math_learning_style', 'agent_data.agent04_data.actual_learning_behavior']
                        },
                        {
                            text: '각 분류별로 필요한 상호작용 톤(격려형/코치형/공감형)과 개입 시점을 어떻게 결정하는가?',
                            dataSources: ['interaction_tone', 'intervention_timing', 'agent_data.agent05_data.emotion_state', 'agent_data.agent16_data.interaction_preparation']
                        }
                    ]
                },
                {
                    title: '수학 학습 스타일 불일치 문제 재정의',
                    questions: [
                        {
                            text: '설정된 수학 학습 스타일(계산형/개념형/응용형)과 실제 행동패턴이 불일치하는 경우, 학습 스타일 정렬 문제로 재정의하는 기준은?',
                            dataSources: ['agent_data.agent01_data.math_learning_style', 'agent_data.agent04_data.actual_learning_behavior', 'strategy_mismatch']
                        },
                        {
                            text: '계산형 학생이 개념만 공부하는 경우, 계산 연습 강화 문제로 재정의하고 전략을 어떻게 조정하는가?',
                            dataSources: ['agent_data.agent01_data.math_learning_style', 'agent_data.agent04_data.actual_learning_behavior', 'math_error_types.calculation_error']
                        }
                    ]
                }
            ],
            ontology: [
                {
                    name: 'RoutineCollapseRecovery',
                    description: '루틴 붕괴 원인 진단 및 회복을 온톨로지로 표현 (Agent 15 핵심 온톨로지)'
                },
                {
                    name: 'MathStudyTimeAllocation',
                    description: '수학 학습 시간 배분(단원별 학습 시간)을 온톨로지로 표현하여 시간 배분 문제 재정의에 활용'
                },
                {
                    name: 'TimeManagementFailure',
                    description: '시간관리 실패(계획-실행 시간 차이)를 온톨로지로 표현하여 실행 습관 재설계에 활용'
                },
                {
                    name: 'MathLearningStyleMismatch',
                    description: '수학 학습 스타일 불일치(설정 스타일 vs 실제 행동)를 온톨로지로 표현하여 전략 정렬에 활용'
                },
                {
                    name: 'StandardCodeClassification',
                    description: '표준코드 분류(전략 불일치/시간관리/정서 리듬)를 온톨로지로 표현하여 루틴 회복 전략 수립에 활용'
                }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 루틴 붕괴 원인을 진단하고 표준코드 체계에 따라 분류하며, 수학 특화 시간 배분과 학습 스타일 불일치를 분석합니다. rules.yaml의 S4, S5, S9 시나리오와 직접 연계됩니다.',
                answerAnalysis: '루틴 불안정은 S4_R1 룰이 분석하고, 수학 학습 시간 배분은 S4_R1 룰이 평가합니다. 시간관리 실패는 S5_R1 룰이 분석하고, 수학 학습 스타일 불일치는 S9_R1 룰이 평가합니다.',
                ruleBasedActions: 'rules.yaml의 S4_R1 룰이 루틴 불안정을 분석하고, S5_R1 룰이 시간관리 실패를 진단하며, S9_R1 룰이 수학 학습 스타일 불일치를 평가합니다.'
            }
        },
        3: {
            questionSets: [
                {
                    title: '구조적 문제 재정의 및 협력 해결 방안',
                    questions: [
                        {
                            text: '최근 2주간 학습성과(점수, 진도, 감정안정도)가 하락세일 때, 단기적인 피드백만으로는 해결되지 않는 구조적 문제를 어떻게 재정의하는가?',
                            dataSources: ['recent_2weeks_performance', 'score_decline', 'progress_decline', 'emotion_stability_decline', 'agent_data.agent10_data.concept_scores', 'agent_data.agent11_data.error_rate', 'agent_data.agent05_data.emotion_stability']
                        },
                        {
                            text: '모든 데이터(단원별 취약점, 오류 유형, 학생 수준, 학원 맥락)를 통합하여 수학 특화 종합 문제 재정의를 수행하는 기준과 프로세스는?',
                            dataSources: ['math_unit_vulnerability', 'math_error_types', 'student_level', 'academy_context', 'agent_data']
                        },
                        {
                            text: 'AI·교사·학생이 협력하여 해결해야 할 핵심 진단코드 3개를 선정하는 기준과 각 코드별 우선순위는?',
                            dataSources: ['core_diagnostic_code_1', 'core_diagnostic_code_2', 'core_diagnostic_code_3', 'standard_diagnosis_codes', 'priority_3']
                        },
                        {
                            text: '각 진단코드별로 예상 리스크, 개입 효과, 모니터링 변수를 어떻게 정리하고 추적하는가?',
                            dataSources: ['expected_risk', 'intervention_effect', 'monitoring_variables', 'agent_data.agent14_data.monitoring_data']
                        },
                        {
                            text: '선행 단원 미완료로 확인된 개념 이해 부진을 선행 단원 재학습 문제로 재정의하는 기준과 단원 선후관계 확인 방법은?',
                            dataSources: ['agent_data.agent10_data.concept_test_scores', 'prerequisite_unit_completion', 'math_unit_relations', 'math_unit_vulnerability']
                        },
                        {
                            text: '교사 피드백(기본기 부족, 집중력 저하, 패턴 반복)을 수학 특화 문제로 재정의하는 방법과 진단코드 매핑은?',
                            dataSources: ['agent_data.agent06_data.teacher_feedback', 'math_unit_vulnerability', 'agent_data.agent11_data.error_pattern', 'agent_data.agent05_data.focus_score']
                        }
                    ]
                },
                {
                    title: '수학 특화 정서 문제 및 회복 실패 재정의',
                    questions: [
                        {
                            text: '수학 불안 수준이 0.7 이상이고 동기 저하가 발생한 경우, 수학 불안 완화 문제로 재정의하고 작은 성공 경험 설계 전략은?',
                            dataSources: ['agent_data.agent05_data.math_anxiety_level', 'agent_data.agent05_data.motivation_level', 'agent_data.agent11_data.error_rate']
                        },
                        {
                            text: '휴식 후 집중도가 50% 미만으로 회복되지 않고 2회 이상 지속되는 경우, 수학 학습 회복 루틴 재설계 문제로 재정의하는 기준은?',
                            dataSources: ['agent_data.agent12_data.recovery_rate', 'agent_data.agent12_data.concentration_recovery', 'agent_data.agent05_data.focus_score']
                        }
                    ]
                }
            ],
            ontology: [
                {
                    name: 'StructuralProblemDiagnosis',
                    description: '구조적 문제 진단을 온톨로지로 표현 (Agent 15 핵심 온톨로지)'
                },
                {
                    name: 'ComprehensiveMathRedefinition',
                    description: '수학 특화 종합 문제 재정의(모든 데이터 통합)를 온톨로지로 표현하여 현직 선생님 수준의 통합 분석에 활용'
                },
                {
                    name: 'CollaborativeProblemSolving',
                    description: 'AI·교사·학생 협력 문제 해결(핵심 진단코드 3개)을 온톨로지로 표현하여 협력 해결 방안 수립에 활용'
                },
                {
                    name: 'PrerequisiteUnitAnalysis',
                    description: '선행 단원 분석(단원 선후관계 확인)을 온톨로지로 표현하여 개념 이해 부진 문제 재정의에 활용'
                },
                {
                    name: 'MathAnxietyRedefinition',
                    description: '수학 불안 재정의(불안 완화 전략)를 온톨로지로 표현하여 정서 문제 재정의에 활용'
                },
                {
                    name: 'MathRecoveryFailure',
                    description: '수학 학습 회복 실패(회복 루틴 재설계)를 온톨로지로 표현하여 회복 전략 수립에 활용'
                }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 구조적 문제를 재정의하고 AI·교사·학생 협력 해결 방안을 제시하며, 수학 특화 종합 분석과 정서 문제를 포함합니다. rules.yaml의 S7, S8, S10, COMP 시나리오와 직접 연계됩니다.',
                answerAnalysis: '구조적 문제 재정의는 COMP_R1 룰이 수행하고, 표준진단 항목 매핑은 COMP_R2 룰이 수행합니다. 선행 단원 미완료는 S7_R1 룰이 분석하고, 교사 피드백 기반 재정의는 S8_R1 룰이 수행합니다. 수학 불안은 S6_R1 룰이 분석하고, 회복 실패는 S10_R1 룰이 평가합니다.',
                ruleBasedActions: 'rules.yaml의 COMP_R1~COMP_R3 룰이 종합 문제 재정의를 수행하고, S7_R1~S7_R2 룰이 개념 이해 부진을 분석하며, S8_R1 룰이 교사 피드백을 반영합니다. S6_R1 룰이 수학 불안을 분석하고, S10_R1 룰이 회복 실패를 평가합니다.'
            }
        }
    };
    
    // Agent 16: 상호작용준비 (상세 버전은 아래에 정의됨)
    
    // Agent 17: 잔여활동
    window.dataBasedQuestionSets.agent17 = {
        1: {
            questionSets: [{
                title: '학습 흐름 단절 원인 및 리듬 회복',
                questions: [
                    { text: '잔여시간, 피로도, 감정 상태, 집중 흐름, 병목 원인을 종합 분석한 리듬 붕괴의 원인 진단은?', dataSources: ['remaining_time', 'fatigue_level', 'emotion_status', 'concentration_flow', 'bottleneck_cause'] },
                    { text: '단기 회복 루틴(예열–핵심–정리)과 정서 안정 장치를 포함한 조정 전략은?', dataSources: ['short_term_recovery_routine', 'warmup_core_summary', 'emotional_stability_device'] },
                    { text: '학습 흐름이 왜 멈췄는지, 어떤 리듬 회복 전략으로 다시 이어가야 할까?', dataSources: ['flow_interruption_cause', 'rhythm_recovery_strategy'] }
                ]
            }],
            ontology: [{ name: 'FlowInterruptionRecovery', description: '학습 흐름 단절 및 리듬 회복을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '학습 흐름이 왜 멈췄는지 분석하고 리듬 회복 전략을 도출합니다.', answerAnalysis: '잔여시간, 피로도, 감정 상태를 종합 분석합니다.', ruleBasedActions: 'rules.yaml의 흐름 회복 룰이 트리거됩니다.' }
        },
        2: {
            questionSets: [{
                title: '활동 재배치 최적화',
                questions: [
                    { text: '가용 시간, 인지 부하, 활동 난이도, 우선순위를 함께 고려한 핵심 20% 활동 선별은?', dataSources: ['available_time', 'cognitive_load', 'activity_difficulty', 'priority'] },
                    { text: '시간 슬롯 단위 재배치와 피로 누적 방지형 루틴 구성은?', dataSources: ['time_slot_reallocation', 'fatigue_prevention_routine'] },
                    { text: '현재 남은 학습 시간과 에너지 수준을 고려한 가장 현실적이고 지속 가능한 활동 재배치는?', dataSources: ['remaining_learning_time', 'energy_level', 'sustainable_reallocation'] }
                ]
            }],
            ontology: [{ name: 'ActivityReallocation', description: '활동 재배치를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '현재 남은 학습 시간과 에너지 수준을 고려하여 활동을 재배치합니다.', answerAnalysis: '가용 시간, 인지 부하, 활동 난이도를 분석합니다.', ruleBasedActions: 'rules.yaml의 활동 재배치 룰이 트리거됩니다.' }
        },
        3: {
            questionSets: [{
                title: '장기 학습 리듬 유지',
                questions: [
                    { text: '최근 리듬 패턴, 정서 루프, 집중 유지 시간, 루틴 붕괴 이력을 기반으로 한 단기 → 중기 → 장기 리듬 안정화 패턴은?', dataSources: ['recent_rhythm_pattern', 'emotional_loop', 'concentration_maintenance_time', 'routine_collapse_history'] },
                    { text: '루틴 복귀 지속성 예측과 오늘 만들어야 할 회복 패턴은?', dataSources: ['routine_return_persistence', 'recovery_pattern_for_today'] },
                    { text: '장기적으로 학습 리듬을 유지하기 위한 오늘의 회복 패턴은?', dataSources: ['long_term_rhythm_maintenance', 'today_recovery_pattern'] }
                ]
            }],
            ontology: [{ name: 'LongTermRhythmMaintenance', description: '장기 학습 리듬 유지를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '장기적으로 학습 리듬을 유지하기 위한 회복 패턴을 만듭니다.', answerAnalysis: '최근 리듬 패턴과 정서 루프를 분석합니다.', ruleBasedActions: 'rules.yaml의 장기 리듬 유지 룰이 트리거됩니다.' }
        }
    };
    
    // Agent 18: 시그너처루틴
    window.dataBasedQuestionSets.agent18 = {
        1: {
            questionSets: [{
                title: '몰입 패턴 및 감정 리듬 종합 분석',
                questions: [
                    { text: '온보딩 데이터와 최신 선호동 정보를 결합하여 학생이 몰입에 진입하는 심리 트리거(감정·환경·행동요소)는?', dataSources: ['onboarding_data', 'latest_preference', 'immersion_psychological_trigger', 'emotion_trigger', 'environment_trigger', 'behavior_trigger'] },
                    { text: '시작 루틴–집중 루틴–회복 루틴 구조로 정리된 시그너처 루틴 초안은?', dataSources: ['start_routine', 'concentration_routine', 'recovery_routine', 'signature_routine_draft'] },
                    { text: '지금 시점에서 가장 자연스럽게 몰입이 일어나는 조건은?', dataSources: ['natural_immersion_condition', 'current_immersion_point'] }
                ]
            }],
            ontology: [{ name: 'ImmersionPatternEmotionRhythm', description: '몰입 패턴과 감정 리듬을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '몰입 패턴과 감정 리듬을 종합하여 자연스러운 몰입 조건을 찾습니다.', answerAnalysis: '온보딩 데이터와 선호동 정보를 결합 분석합니다.', ruleBasedActions: 'rules.yaml의 몰입 패턴 분석 룰이 트리거됩니다.' }
        },
        2: {
            questionSets: [{
                title: '루틴 유지 및 강화 요소',
                questions: [
                    { text: '학습 중 감정곡선, 몰입 지속시간, 강화 피드백 반응 패턴을 기반으로 한 루틴 유지 요인(강화 자극·시간대·감정톤)은?', dataSources: ['learning_emotion_curve', 'immersion_duration', 'reinforcement_feedback_response'] },
                    { text: '루틴이 무너질 때 빠르게 회복할 수 있는 보상 루프 설계 및 피드백 전략은?', dataSources: ['reward_loop_design', 'recovery_feedback_strategy'] },
                    { text: '시그너처 루틴을 안정적으로 유지·강화하기 위한 감정적 강화 요소와 상호작용 방식은?', dataSources: ['emotional_reinforcement', 'interaction_method_for_routine'] }
                ]
            }],
            ontology: [{ name: 'RoutineMaintenanceReinforcement', description: '루틴 유지 및 강화를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '시그너처 루틴을 안정적으로 유지·강화하기 위한 요소를 분석합니다.', answerAnalysis: '감정곡선과 몰입 지속시간을 분석합니다.', ruleBasedActions: 'rules.yaml의 루틴 유지 룰이 트리거됩니다.' }
        },
        3: {
            questionSets: [{
                title: '루틴 진화 경로 설계',
                questions: [
                    { text: '루틴 반복 안정성, 감정 진폭, 인지 부하 패턴을 종합 분석한 성취형→탐구형→의미형 루틴 진화 경로는?', dataSources: ['routine_repetition_stability', 'emotion_amplitude', 'cognitive_load_pattern'] },
                    { text: '각 단계에서 필요한 정서·인지적 보정 포인트는?', dataSources: ['emotional_correction_point', 'cognitive_correction_point'] },
                    { text: '장기적으로 몰입을 지속하며 성장하기 위한 시그너처 루틴 진화 방향은?', dataSources: ['long_term_immersion_growth', 'signature_routine_evolution'] }
                ]
            }],
            ontology: [{ name: 'RoutineEvolutionPath', description: '루틴 진화 경로를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '장기적으로 몰입을 지속하며 성장하기 위한 루틴 진화 방향을 제시합니다.', answerAnalysis: '루틴 반복 안정성과 감정 진폭을 분석합니다.', ruleBasedActions: 'rules.yaml의 루틴 진화 룰이 트리거됩니다.' }
        }
    };
    
    // Agent 19: 상호작용컨텐츠
    window.dataBasedQuestionSets.agent19 = {
        1: {
            questionSets: [
                {
                    title: '상호작용 유형 및 템플릿 선택',
                    questions: [
                        { text: '학습 이탈, 지연, 오답 반복, 침착도 저하 등 다중 신호를 분석한 가장 적합한 상호작용 유형(텍스트 전달/루틴 개선/비선형 등)은?', dataSources: ['learning_dropout', 'learning_delay', 'repeated_errors', 'calmness_decline', 'engagement_score', 'input_event_count', 'emotion_state'] },
                        { text: '선택된 상호작용 유형에 맞는 템플릿과 룰 링크 매핑은?', dataSources: ['interaction_template', 'rule_link_mapping', 'template_library_match'] },
                        { text: '현재 감지된 학습 상황(S1~S7)을 종합한 최적 상호작용 유형과 템플릿은?', dataSources: ['detected_learning_situation', 'optimal_interaction_type', 'situation_code', 'detection_source'] }
                    ]
                },
                {
                    title: 'S1: 학습 이탈 조짐 감지 후 재진입 유도',
                    questions: [
                        { text: '이전 30분 동안 학습 집중도와 입력 이벤트가 급감했고, 감정 상태가 권태로 기록된 경우, 재진입을 유도할 상호작용 컨텐츠는?', dataSources: ['engagement_score', 'input_event_count', 'time_window_minutes', 'emotion_state', 'immersion_level', 'current_activity_difficulty'] },
                        { text: '이탈 조짐 감지 시 적합한 상호작용 유형(상호작용 컨텐츠/활동반려)과 템플릿(미니 재진입 챌린지/가벼운 루틴 재시작 인터페이스)은?', dataSources: ['interaction_type', 'template_type', 'easy_win_zone_link', 'alternative_easy_activity'] },
                        { text: '감정 상태(권태/지루함/피로)와 MBTI를 고려한 개인화된 재진입 경로는?', dataSources: ['emotion_state', 'mbti_type', 'emotion_adaptive_reentry', 'emotion_support_content'] }
                    ]
                },
                {
                    title: 'S2: 현재 위치 지연 감지',
                    questions: [
                        { text: '오늘의 목표 대비 진행률이 65% 이하이고, 학생이 지연 구간에 있는 경우, 부담 완화 및 동기 회복을 유도할 상호작용 유형은?', dataSources: ['progress_rate', 'current_position_status', 'detection_source', 'pressure_level', 'study_hours_per_week'] },
                        { text: '지연 상태 감지 시 타임쉬프팅 템플릿과 페이스 조정 링크 구성은?', dataSources: ['interaction_type', 'template_type', 'pace_adjustment_guide', 'alternative_learning_path'] },
                        { text: '지연 상태와 자신감 하락이 함께 나타날 때 단계별 접근으로 점진적 회복을 유도하는 방법은?', dataSources: ['confidence_change', 'confidence_recovery_multiturn', 'motivation_content'] }
                    ]
                },
                {
                    title: 'S3: 휴식 루틴 이상 탐지',
                    questions: [
                        { text: '휴식 패턴이 비정상적으로 짧거나 누락되고, 최근 피로 지표가 누적 상승 중인 경우, 휴식 리셋 상호작용 설계는?', dataSources: ['rest_pattern_status', 'fatigue_accumulation', 'detection_source', 'rest_interval_minutes', 'study_session_duration', 'rest_missing_count', 'consecutive_study_minutes'] },
                        { text: '휴식 루틴 개선과 타임쉬프팅을 통한 종합적 해결 방안은?', dataSources: ['interaction_type', 'template_type', 'rest_routine_guide', 'optimal_rest_pattern', 'rest_activity_content'] },
                        { text: '휴식 누락이 심각할 경우 강제 휴식 유도 상호작용은?', dataSources: ['mandatory_rest_intervention', 'rest_activity_content'] }
                    ]
                },
                {
                    title: 'S4: 오답 패턴 반복',
                    questions: [
                        { text: '동일 유형의 오답을 3회 이상 반복하고, 개념 이해 단계에서 필기 체류시간이 짧은 경우, 학습 루틴 개선형 상호작용은?', dataSources: ['error_repeat_count', 'error_type', 'detection_source', 'concept_review_time_seconds', 'error_category', 'study_style', 'concept_mastery_level'] },
                        { text: '오답 루프 탈출을 위한 가이드형 상호작용과 개념 보강 링크 제공은?', dataSources: ['error_loop_escape_guide', 'concept_reinforcement', 'concept_explanation_content', 'balanced_learning_approach'] },
                        { text: '학습 스타일(문제풀이 위주)과 오답 패턴 연계 분석 기반 루틴 개선은?', dataSources: ['study_style_adaptive_error_improvement', 'balanced_learning_approach'] }
                    ]
                },
                {
                    title: 'S5: 정서적 침착도 저하',
                    questions: [
                        { text: '침착도 점수가 지난주 대비 25% 이상 하락하고, 문제 풀이 중 선택 오류가 빈번하며 감정 로그에 조급함이 기록된 경우, 감정 안정형 상호작용 컨텐츠는?', dataSources: ['calmness_score_change', 'selection_error_frequency', 'emotion_log', 'detection_source', 'mbti_type', 'calmness_score', 'mistake_repeat_count'] },
                        { text: 'MBTI(특히 INFP형)를 고려한 맞춤형 감정 안정화 상호작용과 톤 조정은?', dataSources: ['mbti_type', 'infp_emotional_support', 'tone', 'infp_support_content', 'emotional_stability_content'] },
                        { text: '실수 반복으로 인한 좌절감이 침착도를 떨어뜨릴 경우 단계별 감정 지원은?', dataSources: ['mistake_recovery_multiturn', 'resilience_building_content'] }
                    ]
                },
                {
                    title: 'S6: 목표 대비 활동 불균형',
                    questions: [
                        { text: '목표 대비 활동 분포에서 개념 공부에 과도하게 집중하고 문제풀이는 미흡한 경우, 학습지점 변경 또는 활동반려 상호작용은?', dataSources: ['activity_distribution_balance', 'concept_study_ratio', 'problem_solving_ratio', 'detection_source', 'goal_type', 'user_resistance_to_change', 'previous_intervention_count'] },
                        { text: '활동 균형 재배치 제안형 UI와 대안 활동 링크 제공은?', dataSources: ['activity_balance_reallocation', 'alternative_activity_links', 'goal_aligned_activity_switch', 'problem_solving_activity'] },
                        { text: '활동 전환에 저항이 있을 경우 점진적 접근으로 부드러운 전환 유도는?', dataSources: ['gradual_activity_transition', 'balanced_activity_mix'] }
                    ]
                },
                {
                    title: 'S7: 시그너처 루틴 형성 시점',
                    questions: [
                        { text: '몰입 루틴(시그너처 루틴)을 형성한 경우, 루틴을 강화하고 지속하도록 돕는 상호작용 컨텐츠는?', dataSources: ['signature_routine_detected', 'immersion_level', 'detection_source', 'routine_consistency_days', 'routine_success_rate'] },
                        { text: '루틴 강화 챕터형 상호작용과 보상형 콘텐츠 링크 제공은?', dataSources: ['routine_reinforcement_multiturn', 'reward_content', 'routine_consolidation_support', 'routine_maintenance_tips'] },
                        { text: '루틴 성공 달성 시 보상형 콘텐츠로 동기 강화 및 지속성 확보는?', dataSources: ['routine_success_celebration', 'reward_content'] }
                    ]
                }
            ],
            ontology: [
                { name: 'InteractionTypeTemplate', description: '상호작용 유형과 템플릿을 온톨로지로 표현 (Agent 19 핵심 온톨로지)' },
                { name: 'LearningSituationDetection', description: '학습 상황(S1~S7) 감지와 상호작용 유형 매핑을 온톨로지로 표현' },
                { name: 'ReentryInteraction', description: '학습 이탈 후 재진입 유도 상호작용을 온톨로지로 표현' },
                { name: 'DelayRecoveryInteraction', description: '학습 지연 상태 회복 상호작용을 온톨로지로 표현' },
                { name: 'RestRoutineImprovement', description: '휴식 루틴 개선 상호작용을 온톨로지로 표현' },
                { name: 'ErrorPatternRecovery', description: '오답 패턴 반복 회복 상호작용을 온톨로지로 표현' },
                { name: 'EmotionalStabilityInteraction', description: '정서적 침착도 저하 대응 상호작용을 온톨로지로 표현' },
                { name: 'ActivityBalanceInteraction', description: '목표 대비 활동 불균형 조정 상호작용을 온톨로지로 표현' },
                { name: 'SignatureRoutineReinforcement', description: '시그너처 루틴 강화 상호작용을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 현재 감지된 학습 상황(S1~S7)을 종합하여 최적 상호작용 유형과 템플릿을 선택합니다. rules.yaml의 S1R1~S7R3 룰과 직접 연계됩니다.',
                answerAnalysis: '학습 이탈, 지연, 오답 반복, 침착도 저하 등 다중 신호를 분석하여 알고리즘으로 자동 선택합니다. 각 상황별로 S1R1~S1R3(이탈), S2R1~S2R3(지연), S3R1~S3R3(휴식), S4R1~S4R3(오답), S5R1~S5R3(침착도), S6R1~S6R3(불균형), S7R1~S7R3(루틴 강화) 룰이 트리거됩니다.',
                ruleBasedActions: 'rules.yaml의 S1R1~S1R3 룰이 학습 이탈 조짐 감지 후 재진입 유도 상호작용을 생성하고, S2R1~S2R3 룰이 현재 위치 지연 감지 시 부담 완화 및 동기 회복 상호작용을 생성합니다. S3R1~S3R3 룰이 휴식 루틴 이상 탐지 시 휴식 리셋 상호작용을 생성하고, S4R1~S4R3 룰이 오답 패턴 반복 시 학습 루틴 개선형 상호작용을 생성합니다. S5R1~S5R3 룰이 정서적 침착도 저하 시 감정 안정형 상호작용을 생성하고, S6R1~S6R3 룰이 목표 대비 활동 불균형 시 학습지점 변경 또는 활동반려 상호작용을 생성하며, S7R1~S7R3 룰이 시그너처 루틴 형성 시점에 루틴 강화 상호작용을 생성합니다.'
            }
        },
        2: {
            questionSets: [
                {
                    title: '템플릿 패키징 및 맞춤화',
                    questions: [
                        { text: 'MBTI, 집중 시간대, 학습 스타일, 정서 로그를 활용한 기존 템플릿 라이브러리 재사용 가능성 평가는?', dataSources: ['mbti_type', 'concentration_time_slot', 'learning_style', 'emotion_log', 'template_library_has_match', 'template_match_score'] },
                        { text: '맞춤형 UI/톤/링크 구성과 HTML·CSS·JS 코드 수준의 패키징 전략은?', dataSources: ['customized_ui', 'customized_tone', 'customized_link', 'packaging_strategy', 'use_existing_template', 'customize_template'] },
                        { text: '개인 특성(MBTI·학습 스타일·정서 상태)에 맞게 템플릿을 어떻게 패키징해야 할까?', dataSources: ['personal_characteristics', 'template_packaging', 'create_new_template', 'register_to_library', 'generate_code'] }
                    ]
                },
                {
                    title: 'MBTI 기반 개인화',
                    questions: [
                        { text: '내향형(I) 학생을 위한 조용한 상호작용 스타일과 톤 조정은?', dataSources: ['mbti_type', 'adjust_tone', 'reduce_interruption', 'prefer_text_over_visual'] },
                        { text: '외향형(E) 학생을 위한 활발한 상호작용 스타일과 시각적 표현 강화는?', dataSources: ['mbti_type', 'adjust_tone', 'prefer_visual_over_text', 'increase_engagement_prompts'] }
                    ]
                },
                {
                    title: '템플릿 재사용 및 관리',
                    questions: [
                        { text: '기존 템플릿 우선 재사용 원칙과 맞춤화 적용은?', dataSources: ['template_library_has_match', 'template_match_score', 'use_existing_template', 'customize_template', 'update_template_usage_count'] },
                        { text: '기존 템플릿 부재 시 신규 템플릿 생성 및 라이브러리 등록은?', dataSources: ['template_library_has_match', 'create_new_template', 'register_to_library', 'generate_code'] }
                    ]
                }
            ],
            ontology: [
                { name: 'TemplatePackaging', description: '템플릿 패키징을 온톨로지로 표현' },
                { name: 'MBTIPersonalization', description: 'MBTI 기반 개인화 상호작용을 온톨로지로 표현' },
                { name: 'TemplateReuseManagement', description: '템플릿 재사용 및 관리를 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '선택된 상호작용 유형을 기반으로 개인 특성에 맞게 템플릿을 패키징합니다. rules.yaml의 MBTI1~MBTI2, TMP1~TMP2 룰과 직접 연계됩니다.',
                answerAnalysis: 'MBTI, 학습 스타일, 정서 로그를 활용하여 기존 템플릿 라이브러리 재사용 가능성을 평가하고, 필요시 맞춤화하거나 신규 템플릿을 생성합니다.',
                ruleBasedActions: 'rules.yaml의 MBTI1~MBTI2 룰이 MBTI 기반 개인화를 수행하고, TMP1 룰이 기존 템플릿 우선 재사용을 적용하며, TMP2 룰이 신규 템플릿 생성을 담당합니다.'
            }
        },
        3: {
            questionSets: [
                {
                    title: '상호작용 효과성 검증 및 추적',
                    questions: [
                        { text: '상호작용 결과 트래킹 지표(참여율, 클릭률, 재진입 성공률) 기반 효과성 분석은?', dataSources: ['participation_rate', 'click_rate', 'reentry_success_rate', 'track_click_rate', 'track_engagement_rate', 'track_improvement_rate'] },
                        { text: '템플릿 효율 평가와 룰 보정 및 다음 개입에 반영하는 피드백 루프 설계는?', dataSources: ['template_efficiency', 'rule_correction', 'feedback_loop_design', 'update_template_effectiveness', 'send_to_agent22'] },
                        { text: '생성된 상호작용 컨텐츠가 실제로 학습 행동 변화에 효과적인지 검증하고 추적하는 방법은?', dataSources: ['behavior_change_effectiveness', 'verification_method', 'tracking_method', 'interaction_delivered'] }
                    ]
                },
                {
                    title: '복합 상황 대응',
                    questions: [
                        { text: '이탈 조짐과 지연 상태가 복합적으로 나타날 때 종합 대응 전략은?', dataSources: ['engagement_score', 'progress_rate', 'current_position_status', 'complex_situation_resolution', 'comprehensive_support_content'] },
                        { text: '피로 누적과 오답 패턴 반복이 함께 나타날 때 종합 개선 가이드는?', dataSources: ['fatigue_accumulation', 'error_repeat_count', 'rest_pattern_status', 'fatigue_error_comprehensive_improvement', 'rest_and_learning_balance'] },
                        { text: '활동 불균형과 침착도 저하가 함께 나타날 때 종합 개선 경로는?', dataSources: ['activity_distribution_balance', 'calmness_score', 'emotion_log', 'balance_calmness_comprehensive', 'balanced_emotional_stability'] }
                    ]
                },
                {
                    title: '수학 교과 특화 상호작용',
                    questions: [
                        { text: '수학 단원별 학습 컨텐츠 링크 매핑과 단원 특화 상호작용은?', dataSources: ['current_unit', 'learning_stage', 'unit_content_link', 'weak_units', 'weak_unit_reinforcement_content'] },
                        { text: '학원 교재별 학습 컨텐츠 링크 매핑과 교재 특화 상호작용은?', dataSources: ['academy_textbook', 'textbook_level', 'textbook_content_link'] },
                        { text: '수학 문제 유형별 맞춤 상호작용 템플릿 선택과 피드백은?', dataSources: ['problem_type', 'error_type', 'problem_type_specific', 'math_specific_feedback'] },
                        { text: '학생의 취약 단원 기반 맞춤 컨텐츠 추천은?', dataSources: ['weak_units', 'current_unit', 'weak_unit_reinforcement_content'] },
                        { text: '수학 학습 맥락을 고려한 재진입 유도와 학습 스타일 기반 맞춤 상호작용은?', dataSources: ['current_unit', 'learning_stage', 'provide_unit_specific_link', 'math_learning_style', 'style_specific_content'] },
                        { text: '수학 학습 단계별(개념/유형/심화) 상호작용 전략은?', dataSources: ['learning_stage', 'concept_understanding_check', 'type_practice_interaction', 'advanced_challenge_interaction'] },
                        { text: '학원 수업 전후 예습/복습 상호작용은?', dataSources: ['academy_class_time', 'academy_unit', 'preview_preparation', 'review_reinforcement'] },
                        { text: '수학 특화 피드백(계산 실수/개념 오류)과 동기 부여는?', dataSources: ['error_type', 'calculation_error_feedback', 'concept_error_feedback', 'math_achievement_motivation', 'unit_completion_motivation'] }
                    ]
                }
            ],
            ontology: [
                { name: 'InteractionEffectivenessVerification', description: '상호작용 효과성 검증을 온톨로지로 표현' },
                { name: 'ComplexSituationResponse', description: '복합 상황 대응 상호작용을 온톨로지로 표현' },
                { name: 'MathSubjectSpecificInteraction', description: '수학 교과 특화 상호작용을 온톨로지로 표현' },
                { name: 'MathUnitContentMapping', description: '수학 단원별 컨텐츠 링크 매핑을 온톨로지로 표현' },
                { name: 'MathLearningStageInteraction', description: '수학 학습 단계별 상호작용 전략을 온톨로지로 표현' },
                { name: 'MathSpecificFeedback', description: '수학 특화 피드백을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '생성된 상호작용 컨텐츠의 효과성을 검증하고 추적하며, 복합 상황 대응과 수학 교과 특화 상호작용을 제공합니다. rules.yaml의 INTERACTION_EFFECTIVENESS_TRACKING, CR1~CR3, MATH_UNIT_LINK_MAPPING 등 룰과 직접 연계됩니다.',
                answerAnalysis: '참여율, 클릭률, 재진입 성공률을 분석하여 효과성을 추적하고, 복합 상황 시 비선형 상호작용으로 다각도 접근하며, 수학 단원/교재/문제 유형별 맞춤 상호작용을 제공합니다.',
                ruleBasedActions: 'rules.yaml의 INTERACTION_EFFECTIVENESS_TRACKING 룰이 효과성을 추적하고, CR1~CR3 룰이 복합 상황을 대응하며, MATH_UNIT_LINK_MAPPING, ACADEMY_TEXTBOOK_LINK_MAPPING, PROBLEM_TYPE_INTERACTION_TEMPLATE, WEAK_UNIT_CONTENT_RECOMMENDATION 등 룰이 수학 교과 특화 상호작용을 제공합니다.'
            }
        }
    };
    
    // Agent 21: 개입실행
    window.dataBasedQuestionSets.agent21 = {
        1: { // 포괄형 질문 1: 개입 실행 최적화
            questionSets: [{
                title: '개입 실행 최적화',
                questions: [
                    { text: '최근 활동, 감정상태(Agent 05), 집중시간대(Agent 01), 개입계획(Agent 20)을 종합한 실행 우선순위와 최적 실행 시점은?', dataSources: ['recent_activity', 'emotion_status_agent05', 'concentration_time_slot_agent01', 'intervention_plan_agent20'] },
                    { text: '개입 강도(즉시/지연/완화)와 전달 방식(메시지·채팅·알림) 결정 및 예상 반응 패턴 예측은?', dataSources: ['intervention_intensity', 'delivery_method', 'expected_response_pattern'] },
                    { text: '지금 이 학생에게 개입을 실행한다면, 어떤 방식·타이밍·강도로 해야 가장 효과적일까?', dataSources: ['optimal_execution_method', 'optimal_timing', 'optimal_intensity'] }
                ]
            }],
            ontology: [{ name: 'InterventionExecutionOptimization', description: '개입 실행 최적화를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '지금 이 학생에게 개입을 실행할 때 가장 효과적인 방식·타이밍·강도를 결정합니다.', answerAnalysis: '최근 활동, 감정상태, 집중시간대, 개입계획을 종합합니다.', ruleBasedActions: 'rules.yaml의 MATH_STAGE_01~04, ACADEMY_01~06, REALTIME_01~04 룰이 트리거됩니다.' }
        },
        2: { // 포괄형 질문 2: 개입 조합 및 조정
            questionSets: [{
                title: '개입 조합 및 조정',
                questions: [
                    { text: '하루 메시지 제한, 연쇄 트리거 여부, 선행·후속 개입 관계를 고려한 병합·연기·우선 실행해야 할 개입 구분은?', dataSources: ['daily_message_limit', 'chain_trigger', 'predecessor_successor_relationship'] },
                    { text: '학생의 피로도·집중도 변화를 최소화하는 실행 시퀀스 설계는?', dataSources: ['fatigue_minimization', 'concentration_change_minimization', 'execution_sequence'] },
                    { text: '현재 대기 중인 개입들을 어떻게 조합·조정하면 과부하 없이 자연스럽게 실행할 수 있을까?', dataSources: ['pending_interventions', 'intervention_combination', 'natural_execution'] }
                ]
            }],
            ontology: [{ name: 'InterventionCombination', description: '개입 조합 및 조정을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '현재 대기 중인 개입들을 조합·조정하여 과부하 없이 자연스럽게 실행합니다.', answerAnalysis: '하루 메시지 제한과 연쇄 트리거를 고려합니다.', ruleBasedActions: 'rules.yaml의 ACADEMY_06, REALTIME_04 룰이 트리거됩니다.' }
        },
        3: { // 포괄형 질문 3: 개입 실행 전략 조정
            questionSets: [{
                title: '개입 실행 전략 조정',
                questions: [
                    { text: '실행 기록(읽음률, 응답시간, 효과성 점수), 행동 변화, 감정 회복 속도를 분석한 효과성이 높았던 패턴과 낮았던 패턴 구분은?', dataSources: ['read_rate', 'response_time', 'effectiveness_score', 'behavior_change', 'emotion_recovery_speed'] },
                    { text: '다음 개입의 전달 톤, 시간대, 개입 주기를 최적화하는 개선 루프는?', dataSources: ['next_intervention_tone', 'next_intervention_time', 'next_intervention_cycle', 'improvement_loop'] },
                    { text: '최근 실행된 개입들의 반응과 성과를 바탕으로 다음 개입 실행 전략을 어떻게 조정해야 할까?', dataSources: ['recent_intervention_response', 'recent_intervention_performance', 'strategy_adjustment'] }
                ]
            }],
            ontology: [{ name: 'InterventionStrategyAdjustment', description: '개입 실행 전략 조정을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '최근 실행된 개입들의 반응과 성과를 바탕으로 다음 개입 실행 전략을 조정합니다.', answerAnalysis: '읽음률, 응답시간, 효과성 점수를 분석합니다.', ruleBasedActions: 'rules.yaml의 효과성 검증 룰이 트리거됩니다.' }
        },
        4: { // ① 데이터 트리거 발생 시
            questionSets: [{
                title: '데이터 트리거 발생 시 즉시 개입 실행',
                questions: [
                    { text: '침착도 하락, 필기 지연, 이탈 위험 등의 실시간 트리거가 감지되었을 때 트리거 유형과 심각도는?', dataSources: ['trigger_type', 'trigger_severity', 'calmness_drop', 'note_delay', 'dropout_risk'] },
                    { text: '현재 학생의 감정 상태(Agent 05)와 집중 시간대(Agent 01)를 기반으로 즉시 실행해야 할 개입 유형은?', dataSources: ['emotion_status_agent05', 'concentration_time_slot_agent01', 'immediate_intervention_type'] },
                    { text: '트리거 발생 시점에서 최적 실행 시점과 개입 강도(즉시/지연/완화) 결정은?', dataSources: ['trigger_timing', 'optimal_execution_timing', 'intervention_intensity'] }
                ]
            }],
            ontology: [{ name: 'DataTriggerIntervention', description: '데이터 트리거 발생 시 즉시 개입 실행을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '침착도 하락, 필기 지연, 이탈 위험 등의 실시간 트리거가 감지되었을 때 즉시 실행해야 할 개입을 결정합니다.', answerAnalysis: '트리거 유형, 감정 상태, 집중 시간대를 종합 분석합니다.', ruleBasedActions: 'rules.yaml의 REALTIME_01~04 룰이 트리거됩니다.' }
        },
        5: { // ② 개입 계획 실행
            questionSets: [{
                title: '개입 계획 도착 후 실행 단계',
                questions: [
                    { text: 'Agent 20에서 전달된 개입 계획의 내용, 예정 시점, 우선순위는?', dataSources: ['intervention_plan_agent20', 'planned_content', 'scheduled_timing', 'planned_priority'] },
                    { text: '기존 대기 중인 개입 목록과의 충돌 여부 및 우선순위 재조정 필요성은?', dataSources: ['pending_intervention_list', 'conflict_detection', 'priority_readjustment_needed'] },
                    { text: '개입 계획을 현재 개인 개입 목록에 추가하고 최적 실행 시점과 실행 방식을 결정하는 방법은?', dataSources: ['intervention_list_update', 'optimal_execution_timing', 'execution_method'] }
                ]
            }],
            ontology: [{ name: 'InterventionPlanExecution', description: '개입 계획 도착 후 실행 단계를 온톨로지로 표현' }],
            analysis: { questionAnalysis: 'Agent 20에서 전달된 개입 계획을 받아 실행 단계로 전환합니다.', answerAnalysis: '개입 계획 데이터와 기존 대기 목록을 비교하여 우선순위를 재조정합니다.', ruleBasedActions: 'rules.yaml의 ACADEMY_01~06 룰이 트리거됩니다.' }
        },
        6: { // ③ 메시지 과다 또는 충돌 상황
            questionSets: [{
                title: '메시지 과다 또는 충돌 상황 처리',
                questions: [
                    { text: '오늘 발송된 개입 메시지 수와 하루 메시지 제한 상태는?', dataSources: ['daily_message_count', 'daily_message_limit', 'limit_exceeded'] },
                    { text: '대기 중인 개입 목록과 각 개입의 우선순위, 연쇄 트리거 여부는?', dataSources: ['pending_interventions', 'intervention_priority', 'chain_trigger_detected'] },
                    { text: '하루 제한 내에서 우선순위를 다시 정하고, 선생님 승인 필요 여부를 판단하는 기준은?', dataSources: ['priority_reordering', 'teacher_approval_needed', 'approval_criteria'] }
                ]
            }],
            ontology: [{ name: 'MessageOverloadConflict', description: '메시지 과다 또는 충돌 상황 처리를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '개입이 겹치거나 일일 한도 초과 등 문제 발생 시 우선순위를 재조정합니다.', answerAnalysis: '메시지 한도 상태와 대기 개입 목록을 분석합니다.', ruleBasedActions: 'rules.yaml의 메시지 제한 및 충돌 처리 룰이 트리거됩니다.' }
        },
        7: { // ④ 집중 시간대 또는 활동 전환 직전
            questionSets: [{
                title: '집중 시간대 또는 활동 전환 직전 개입 실행',
                questions: [
                    { text: '학생의 다음 집중 시간대 시작 시점과 현재 시간의 차이는?', dataSources: ['next_concentration_time_slot', 'current_time', 'time_until_concentration'] },
                    { text: '집중 시간대에 가장 효과적인 개입 유형과 효과성 예측 점수는?', dataSources: ['effective_intervention_types', 'effectiveness_prediction_score', 'concentration_time_interventions'] },
                    { text: '집중 시간대에 실행할 개입 2가지를 선택하고, 실행 순서와 시간 오프셋을 계산하는 방법은?', dataSources: ['selected_interventions', 'execution_order', 'time_offset_calculation'] }
                ]
            }],
            ontology: [{ name: 'ConcentrationTimeIntervention', description: '집중 시간대 또는 활동 전환 직전 개입 실행을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '학생의 루틴상 개입 효과가 최고일 때 집중 시간대에 맞춰 개입을 실행합니다.', answerAnalysis: '집중 시간대 데이터와 효과성 예측 모델을 활용합니다.', ruleBasedActions: 'rules.yaml의 ACADEMY_01, ACADEMY_02 룰이 트리거됩니다.' }
        },
        8: { // ⑤ 선생님 직접 개입 필요 시
            questionSets: [{
                title: '선생님 직접 개입 필요 시 처리',
                questions: [
                    { text: '학생의 감정 상태가 불안정하거나 자동 개입이 부적절한 상황인지 판단하는 기준은?', dataSources: ['emotion_stability', 'emotion_instability_detected', 'auto_intervention_inappropriate'] },
                    { text: '현재 대기 중인 개입 중 선생님 직접 전달이 더 효과적인 항목과 그 이유는?', dataSources: ['pending_interventions', 'teacher_delivery_more_effective', 'effectiveness_comparison'] },
                    { text: '자동 개입을 보류 표시로 전환하고 선생님 피드백 전달 요청을 생성하는 프로세스는?', dataSources: ['auto_intervention_hold', 'teacher_feedback_request', 'intervention_status_change'] }
                ]
            }],
            ontology: [{ name: 'TeacherDirectIntervention', description: '선생님 직접 개입 필요 시 처리를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '자동 개입이 부적절하거나 정서 조율이 필요할 때 선생님 직접 개입으로 전환합니다.', answerAnalysis: '감정 상태 분석과 개입 효과성 기록을 비교합니다.', ruleBasedActions: 'rules.yaml의 선생님 개입 전환 룰이 트리거됩니다.' }
        },
        9: { // ⑥ 효과성 검증 및 리포트 요청
            questionSets: [{
                title: '효과성 검증 및 리포트 요청',
                questions: [
                    { text: '특정 개입의 실행 히스토리(실행 시점, 전달 방식, 내용)와 학생의 읽음·응답 패턴은?', dataSources: ['intervention_execution_history', 'read_pattern', 'response_pattern', 'delivery_method'] },
                    { text: '개입 후 목표 달성도, 행동 변화도, 감정 회복 속도를 기반으로 한 효과성 점수는?', dataSources: ['goal_achievement_rate', 'behavior_change_degree', 'emotion_recovery_speed', 'effectiveness_score'] },
                    { text: '효과성이 높았던 패턴과 낮았던 패턴을 구분하고, 다음 개입 개선점을 제안하는 방법은?', dataSources: ['effective_patterns', 'ineffective_patterns', 'improvement_recommendations', 'next_intervention_improvements'] }
                ]
            }],
            ontology: [{ name: 'InterventionEffectivenessVerification', description: '효과성 검증 및 리포트 요청을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '개입 후 학생 반응 및 성과를 분석하여 효과성을 검증하고 리포트를 생성합니다.', answerAnalysis: '실행 히스토리, 반응 기록, 효과성 점수를 종합 분석합니다.', ruleBasedActions: 'rules.yaml의 효과성 검증 룰이 트리거됩니다.' }
        },
        
        // Agent 21 전체 온톨로지 사용 영역 식별 및 추천
        ontologyRecommendations: {
            coreOntologies: [
                {
                    name: 'InterventionExecutionFlow',
                    description: '개입 실행 전체 흐름(트리거 감지 → 계획 수립 → 실행 → 검증)을 온톨로지로 표현하는 핵심 온톨로지',
                    usage: '개입 실행의 전 과정을 추적하고 최적화하는 데 활용',
                    priority: 'high',
                    relatedRules: ['MATH_STAGE_01~04', 'ACADEMY_01~06', 'REALTIME_01~04']
                },
                {
                    name: 'InterventionTimingOptimization',
                    description: '개입 타이밍 최적화(집중 시간대, 활동 전환, 학습 단계별)를 온톨로지로 표현',
                    usage: '학생의 학습 리듬에 맞춘 최적 개입 시점 결정에 활용',
                    priority: 'high',
                    relatedRules: ['ACADEMY_01', 'ACADEMY_02', 'REALTIME_01~04']
                },
                {
                    name: 'InterventionDeliveryMethod',
                    description: '개입 전달 방식(메시지·채팅·알림·선생님 직접)을 온톨로지로 표현',
                    usage: '상황에 맞는 최적 전달 방식 선택에 활용',
                    priority: 'high',
                    relatedRules: ['MATH_STAGE_01~04', 'ACADEMY_01~06']
                },
                {
                    name: 'InterventionEffectivenessPattern',
                    description: '개입 효과성 패턴(읽음률, 응답시간, 행동 변화, 감정 회복)을 온톨로지로 표현',
                    usage: '개입 효과성을 예측하고 개선하는 데 활용',
                    priority: 'high',
                    relatedRules: ['효과성 검증 룰']
                },
                {
                    name: 'InterventionConflictResolution',
                    description: '개입 충돌 해결(메시지 제한, 우선순위 재조정, 선생님 승인)을 온톨로지로 표현',
                    usage: '개입 과다나 충돌 상황을 해결하는 데 활용',
                    priority: 'medium',
                    relatedRules: ['메시지 제한 룰', '충돌 처리 룰']
                },
                {
                    name: 'LearningStageInterventionMapping',
                    description: '학습 단계별(개념/유형/심화/기출) 개입 매핑을 온톨로지로 표현',
                    usage: '학습 단계에 맞는 개입 유형 선택에 활용',
                    priority: 'high',
                    relatedRules: ['MATH_STAGE_01~04']
                }
            ],
            crossAgentOntologies: [
                {
                    name: 'Agent20To21InterventionTransfer',
                    description: 'Agent 20(개입준비)에서 Agent 21(개입실행)로의 개입 계획 전달을 온톨로지로 표현',
                    usage: '개입 준비 단계에서 실행 단계로의 원활한 전환에 활용',
                    priority: 'high',
                    example: 'Agent 20에서 생성된 개입 계획을 Agent 21이 받아 실행 목록에 추가하고 우선순위 재조정'
                },
                {
                    name: 'Agent05EmotionBasedIntervention',
                    description: 'Agent 05(학습감정)의 감정 데이터를 기반으로 한 개입 실행을 온톨로지로 표현',
                    usage: '감정 상태에 따른 개입 강도와 방식 조정에 활용',
                    priority: 'high',
                    example: '침착도 하락 감지 시 감정 상태에 맞는 즉시 개입 실행'
                },
                {
                    name: 'Agent01ConcentrationTimeIntervention',
                    description: 'Agent 01(온보딩)의 집중 시간대 데이터를 활용한 개입 타이밍 결정을 온톨로지로 표현',
                    usage: '학생의 집중 시간대에 맞춘 개입 실행에 활용',
                    priority: 'high',
                    example: '집중 시간대 30분 전에 동기부여 개입 실행'
                }
            ],
            integrationRecommendations: [
                {
                    area: 'Agent 20 (개입준비)와의 통합',
                    description: '개입 준비 단계에서 생성된 계획을 실행 단계로 원활히 전달하고 우선순위 재조정',
                    ontology: ['Agent20To21InterventionTransfer', 'InterventionExecutionFlow'],
                    benefit: '개입 준비와 실행의 연속성 확보로 개입 효과성 향상'
                },
                {
                    area: 'Agent 05 (학습감정)와의 통합',
                    description: '감정 상태 데이터를 기반으로 개입 강도와 방식을 실시간 조정',
                    ontology: ['Agent05EmotionBasedIntervention', 'InterventionDeliveryMethod'],
                    benefit: '감정 상태에 맞는 맞춤형 개입으로 학생 반응 개선'
                },
                {
                    area: 'Agent 01 (온보딩)와의 통합',
                    description: '집중 시간대 데이터를 활용하여 최적 개입 타이밍 결정',
                    ontology: ['Agent01ConcentrationTimeIntervention', 'InterventionTimingOptimization'],
                    benefit: '학생의 학습 리듬에 맞춘 개입으로 효과성 극대화'
                },
                {
                    area: 'Agent 19 (상호작용컨텐츠)와의 통합',
                    description: '생성된 상호작용 컨텐츠를 개입 실행 단계에서 활용',
                    ontology: ['InterventionDeliveryMethod', 'InterventionEffectivenessPattern'],
                    benefit: '맞춤형 컨텐츠를 통한 개입 효과성 향상'
                }
            ],
            implementationPriority: {
                phase1: [
                    'InterventionExecutionFlow',
                    'InterventionTimingOptimization',
                    'InterventionDeliveryMethod'
                ],
                phase2: [
                    'InterventionEffectivenessPattern',
                    'LearningStageInterventionMapping',
                    'Agent20To21InterventionTransfer'
                ],
                phase3: [
                    'InterventionConflictResolution',
                    'Agent05EmotionBasedIntervention',
                    'Agent01ConcentrationTimeIntervention'
                ]
            }
        }
    };
    
    // Agent 16: 상호작용준비 (Interaction Preparation)
    window.dataBasedQuestionSets.agent16 = {
        1: { // 포괄형 질문 1: 세계관 선택
            questionSets: [
                {
                    title: '학생 상태 기반 세계관 추천 분석',
                    questions: [
                        { text: '학생의 최근 감정 안정도, 루틴 유지율, 목표 모드, 피로도, 몰입도 데이터를 종합하여 어떤 세계관이 가장 적합한가요?', dataSources: ['emotion_stability', 'routine_maintenance_rate', 'goal_mode', 'fatigue_level', 'immersion_score', 'learning_emotion'] },
                        { text: '학생의 수학 학습 단계(개념학습/유형연습/심화/기출)와 현재 수준(하위권/중위권/상위권)을 고려할 때 어떤 세계관이 효과적일까요?', dataSources: ['math_learning_stage', 'student_level', 'math_recent_accuracy', 'unit_accuracy'] },
                        { text: '학생의 학습 스타일(계산형/개념형/응용형)과 최근 감정 상태를 반영하여 어떤 스토리 톤(격려형/코치형/공감형)이 적합한가요?', dataSources: ['math_learning_style', 'learning_emotion', 'emotion_state', 'persona_type'] }
                    ]
                },
                {
                    title: '세계관별 상황 매핑 및 선택 기준',
                    questions: [
                        { text: '학생이 장기 목표 흐름에서 방향을 잃었을 때 커리큘럼 세계관을 선택해야 하는지, 감정·수준 변화로 맞춤학습 세계관이 필요한지 판단 기준은?', dataSources: ['long_term_goal_deviation', 'emotion_change', 'level_change', 'curriculum_alignment'] },
                        { text: '시험 D-14일 기준으로 시험대비 세계관이 적합한지, 단기 목표 지연으로 단기미션 세계관이 필요한지 판단 데이터는?', dataSources: ['exam_d_day', 'exam_schedule', 'short_term_goal_delay', 'motivation_level'] },
                        { text: '학생의 루틴 붕괴 정도와 시간 관리 이슈를 고려하여 시간성찰 세계관이 필요한지, 호기심 저하로 탐구학습 세계관이 필요한지 판단은?', dataSources: ['routine_disruption', 'time_management_issue', 'curiosity_level', 'question_frequency'] }
                    ]
                },
                {
                    title: '세계관 선택을 위한 종합 데이터 분석',
                    questions: [
                        { text: '이전 상호작용에서 사용된 세계관과의 호환성, 학생의 선호도, 효과성 데이터를 종합하여 최적의 세계관은?', dataSources: ['previous_worldview', 'worldview_compatibility', 'student_preference', 'worldview_effectiveness_data', 'interaction_history'] },
                        { text: '학원 수업 맥락(수업 전/후, 문제 풀이 중)과 현재 학습 상황을 고려하여 어떤 세계관이 가장 자연스러운가요?', dataSources: ['academy_class_time', 'academy_class_completed', 'pre_class_interaction_needed', 'post_class_interaction_needed', 'problem_solving_status'] },
                        { text: '학생의 취약 단원과 현재 학습 단원이 일치할 때 도제학습 세계관이 필요한지, 자기주도 학습이 가능한 상황인지 판단은?', dataSources: ['weak_units', 'current_unit', 'current_unit_accuracy', 'self_directed_learning_capability'] }
                    ]
                }
            ],
            ontology: [
                { name: 'WorldviewSelection', description: '학생 상태 기반 세계관 선택을 온톨로지로 표현 (Agent 16 핵심 온톨로지)' },
                { name: 'WorldviewSituationMapping', description: '세계관별 상황 매핑과 선택 기준을 온톨로지로 표현' },
                { name: 'WorldviewCompatibility', description: '이전 상호작용과의 세계관 호환성을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 학생 상태 기반 세계관 추천, 상황별 매핑, 종합 데이터 분석을 통해 최적의 세계관을 선택합니다. rules.yaml의 S1_R1~S1_R4, S5_R1, S6_R1~S6_R2 룰과 직접 연계됩니다.',
                answerAnalysis: '학생의 감정 안정도, 루틴 유지율, 학습 단계는 S1_R1~S1_R4 룰이 분석하고, 수준별 세계관 선택은 S5_R1 룰이 수행합니다. 이전 상호작용 연속성은 S6_R1 룰이 평가하고, 개인화 선호도는 S6_R2 룰이 학습합니다.',
                ruleBasedActions: 'rules.yaml의 S1_R1~S1_R4 룰이 학습 단계별 세계관을 매핑하고, S5_R1~S5_R2 룰이 학생 수준별 차별화를 수행하며, S6_R1~S6_R2 룰이 상호작용 연속성과 개인화를 관리합니다.'
            }
        },
        2: { // 포괄형 질문 2: 스토리 테마 및 내러티브 구조
            questionSets: [
                {
                    title: '피드백 내용 기반 스토리 테마 선택',
                    questions: [
                        { text: 'Agent 15에서 도출된 문제 개선 아이디어와 학생의 페르소나, 감정톤, 세계관 데이터를 반영하여 어떤 스토리 테마(단기 집중형/성찰형/도전형/휴식형)가 적합한가요?', dataSources: ['problem_redefinition', 'problem_improvement_ideas', 'persona_type', 'emotion_tone', 'worldview_data'] },
                        { text: '학생의 현재 감정 상태와 학습 피로도를 고려하여 격려 중심의 성찰형 테마가 필요한지, 동기 부여의 도전형 테마가 필요한지 판단은?', dataSources: ['learning_emotion', 'emotion_state', 'fatigue_level', 'motivation_level', 'student_level'] },
                        { text: '최근 오답 패턴과 학습 감정 데이터를 반영하여 실수를 성장 경험으로 전환하는 자기성찰 테마가 적합한지 판단은?', dataSources: ['error_pattern', 'recent_error_count', 'learning_emotion', 'error_recovery_resilience'] }
                    ]
                },
                {
                    title: '서사 구조(도입-전개-결말) 설계',
                    questions: [
                        { text: '학생의 집중력 패턴과 학습 세션 단계별 몰입도를 고려하여 도입부의 길이와 톤, 전개부의 구조, 결말부의 메시지 강도는 어떻게 설계해야 할까요?', dataSources: ['focus_pattern', 'session_stage', 'immersion_score_by_stage', 'attention_duration'] },
                        { text: '이전 상호작용의 감정 흐름과 대화 톤을 이어받아 자연스러운 서사 전개를 위해 어떤 연결 요소가 필요한가요?', dataSources: ['previous_emotional_flow', 'previous_dialogue_tone', 'narrative_continuity', 'character_consistency'] },
                        { text: '학생의 메타인지 수준과 자기 설명 능력을 고려하여 서사 구조에서 어떤 단계에서 질문을 던지고, 어떤 단계에서 인사이트를 제공해야 할까요?', dataSources: ['metacognitive_level', 'self_explanation_score', 'question_timing', 'insight_provision_timing'] }
                    ]
                },
                {
                    title: '감정 흐름 설계 및 톤 조정',
                    questions: [
                        { text: '학생의 감정 회복 속도와 정서적 복귀 패턴을 분석하여 서사에서 감정의 곡선(상승-정점-하강-회복)을 어떻게 설계해야 할까요?', dataSources: ['emotion_recovery_speed', 'emotional_return_pattern', 'emotion_curve', 'recovery_resilience'] },
                        { text: '학생의 학습 스타일과 선호하는 대화 톤(안내형/지원형/격려 중심/도전 중심)을 반영하여 어떤 감정 톤이 가장 효과적일까요?', dataSources: ['math_learning_style', 'preferred_dialogue_tone', 'tone_effectiveness_data', 'interaction_history'] },
                        { text: '학원 수업 맥락(수업 전/후)과 문제 풀이 상황을 고려하여 감정 흐름의 강도와 방향을 어떻게 조정해야 할까요?', dataSources: ['academy_context', 'pre_class_emotion', 'post_class_emotion', 'problem_solving_emotion', 'solving_stage'] }
                    ]
                }
            ],
            ontology: [
                { name: 'StoryThemeSelection', description: '피드백 내용 기반 스토리 테마 선택을 온톨로지로 표현 (Agent 16 핵심 온톨로지)' },
                { name: 'NarrativeStructure', description: '서사 구조(도입-전개-결말) 설계를 온톨로지로 표현' },
                { name: 'EmotionalFlowDesign', description: '감정 흐름 설계 및 톤 조정을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 피드백 내용 기반 스토리 테마 선택, 서사 구조 설계, 감정 흐름 설계를 종합 분석합니다. rules.yaml의 S2_R1~S2_R2, S3_R1~S3_R3, S4_R1~S4_R2 룰과 직접 연계됩니다.',
                answerAnalysis: '문제 개선 아이디어는 Agent 15에서 가져오고, 학원 수업 맥락은 S2_R1~S2_R2 룰이 분석합니다. 문제 풀이 중 상호작용은 S3_R1~S3_R3 룰이 준비하고, 단원별 전략은 S4_R1~S4_R2 룰이 반영합니다.',
                ruleBasedActions: 'rules.yaml의 S2_R1~S2_R2 룰이 학원 수업 맥락을 고려하고, S3_R1~S3_R3 룰이 문제 풀이 중 실시간 상호작용을 준비하며, S4_R1~S4_R2 룰이 단원별 취약점 기반 전략을 설계합니다.'
            }
        },
        3: { // 포괄형 질문 3: 상호작용 연속성
            questionSets: [
                {
                    title: '이전 상호작용 맥락 추적 및 연결',
                    questions: [
                        { text: '이전 상호작용에서 사용된 세계관, 감정 톤, 대화 흐름, 캐릭터 역할을 추적하여 이번 상호작용과의 호환성은 어떤가요?', dataSources: ['previous_worldview', 'previous_emotional_tone', 'previous_dialogue_flow', 'previous_character_role', 'worldview_compatibility'] },
                        { text: '이전 상호작용의 주제와 이번 피드백 내용 간의 연결고리를 찾아 자연스러운 연속성을 만들기 위해 어떤 연결 문장이 필요한가요?', dataSources: ['previous_interaction_topic', 'current_feedback_content', 'topic_connection', 'narrative_continuity'] },
                        { text: '이전 상호작용에서 학생이 보인 반응(읽음률, 응답시간, 행동 변화)을 분석하여 이번 상호작용의 톤과 구조를 어떻게 조정해야 할까요?', dataSources: ['previous_interaction_response', 'read_rate', 'response_time', 'behavior_change', 'interaction_effectiveness'] }
                    ]
                },
                {
                    title: '캐릭터 일관성 및 대화 톤 매칭',
                    questions: [
                        { text: '이전 상호작용에서 설정된 캐릭터 역할(멘토/코치/동반자 등)을 유지해야 하는지, 상황 변화로 역할 전환이 필요한지 판단은?', dataSources: ['previous_character_role', 'character_consistency', 'situation_change', 'role_transition_needed'] },
                        { text: '이전 대화 톤(격려형/코치형/공감형)과 이번 피드백의 목적을 고려하여 톤을 유지할지, 조정할지 결정 기준은?', dataSources: ['previous_dialogue_tone', 'current_feedback_purpose', 'tone_maintenance', 'tone_adjustment'] },
                        { text: '학생이 이전 상호작용에서 선호했던 표현 방식과 대화 스타일을 학습하여 이번 상호작용에 어떻게 반영할까요?', dataSources: ['preferred_expression_style', 'preferred_dialogue_style', 'interaction_history', 'student_preference'] }
                    ]
                },
                {
                    title: '연속성 유지를 위한 개인화 전략',
                    questions: [
                        { text: '상호작용 이력이 5회 이상일 때 학생별 선호 세계관과 효과성 데이터를 분석하여 이번 상호작용의 세계관 선택에 어떻게 반영할까요?', dataSources: ['interaction_history_count', 'worldview_effectiveness_data', 'preferred_worldview', 'worldview_priority'] },
                        { text: '이전 상호작용에서 효과적이었던 서사 구조와 감정 흐름 패턴을 이번 상호작용에 어떻게 재현하거나 개선할까요?', dataSources: ['effective_narrative_structure', 'effective_emotional_flow', 'pattern_replication', 'pattern_improvement'] },
                        { text: '학생의 장기 학습 궤도와 이전 상호작용들의 누적 효과를 고려하여 이번 상호작용이 전체 스토리에서 어떤 역할을 해야 할까요?', dataSources: ['long_term_learning_trajectory', 'cumulative_interaction_effects', 'narrative_role', 'story_continuity'] }
                    ]
                }
            ],
            ontology: [
                { name: 'InteractionContinuity', description: '이전 상호작용 맥락 추적 및 연결을 온톨로지로 표현 (Agent 16 핵심 온톨로지)' },
                { name: 'CharacterConsistency', description: '캐릭터 일관성 및 대화 톤 매칭을 온톨로지로 표현' },
                { name: 'PersonalizationStrategy', description: '연속성 유지를 위한 개인화 전략을 온톨로지로 표현' }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 이전 상호작용 맥락 추적, 캐릭터 일관성 유지, 개인화 전략을 종합 분석합니다. rules.yaml의 S6_R1~S6_R2 룰과 직접 연계됩니다.',
                answerAnalysis: '이전 상호작용 연속성은 S6_R1 룰이 관리하고, 학생별 선호 세계관 학습은 S6_R2 룰이 수행합니다. 캐릭터 일관성과 톤 매칭은 S6_R1 룰의 maintain_character_consistency와 preserve_emotional_tone 액션이 처리합니다.',
                ruleBasedActions: 'rules.yaml의 S6_R1 룰이 이전 상호작용과의 호환성을 확인하고 연속성을 유지하며, S6_R2 룰이 학생별 선호 세계관을 학습하고 우선 적용합니다.'
            }
        }
    };
    
    // Agent 20: 개입준비 (Intervention Preparation)
    window.dataBasedQuestionSets.agent20 = {
        1: { // 포괄형 질문 1: 개입 진입 전략
            questionSets: [
                {
                    title: '개입 트리거 유형 및 타이밍 분석',
                    questions: [
                        {
                            text: '학습 지연 감지(포모도로 미작성 2회 이상, 목표 진행률 60% 이하) 시 어떤 인터페이스 위치에서 개입이 가장 효과적인가?',
                            dataSources: ['pomodoro_missing_count', 'goal_completion_rate', 'learning_delay_detected', 'interface_location', 'intervention_effectiveness_by_location']
                        },
                        {
                            text: '침착도 변화나 루틴 진행률 데이터를 기준으로 최적 개입 타이밍(즉시/지연/완화)은 언제인가?',
                            dataSources: ['calmness_change', 'routine_progress_rate', 'optimal_intervention_timing', 'intervention_intensity']
                        },
                        {
                            text: '환경 데이터(학원 수업 시간, 집중 시간대, 피로도)를 종합하여 개입 위치(대시보드/학습 화면/알림) 추천은?',
                            dataSources: ['academy_class_time', 'focus_time_window', 'fatigue_level', 'environment_data', 'intervention_location_recommendation']
                        },
                        {
                            text: '데이터 기반 트리거(자동 감지) vs 인터페이스 기반 트리거(사용자 요청) 중 어떤 유형이 이 상황에 적합한가?',
                            dataSources: ['data_based_trigger', 'interface_based_trigger', 'trigger_type_effectiveness', 'situation_type']
                        }
                    ]
                },
                {
                    title: '학습 흐름 상태 종합 분석',
                    questions: [
                        {
                            text: '학생의 현재 학습 흐름이 정상 궤도인지, 이탈 중인지, 복귀 중인지를 판단하는 지표는 무엇인가?',
                            dataSources: ['learning_flow_status', 'routine_compliance_rate', 'goal_deviation', 'recovery_indicator']
                        },
                        {
                            text: '루틴 진행률과 학습 지연 패턴을 분석하여 개입이 필요한 시점을 어떻게 예측할 수 있는가?',
                            dataSources: ['routine_progress_rate', 'learning_delay_pattern', 'intervention_need_prediction', 'pattern_analysis']
                        },
                        {
                            text: '침착도 변화 추이와 학습 활동 데이터를 결합하여 개입 효과가 가장 높은 시간대는?',
                            dataSources: ['calmness_trend', 'learning_activity_data', 'intervention_effectiveness_by_time', 'time_window_analysis']
                        }
                    ]
                }
            ],
            ontology: [
                {
                    name: 'InterventionTrigger',
                    description: '개입 트리거 유형(데이터/인터페이스)과 타이밍을 온톨로지로 표현 (Agent 20 핵심 온톨로지)'
                },
                {
                    name: 'InterventionTiming',
                    description: '최적 개입 타이밍과 강도(즉시/지연/완화)를 온톨로지로 표현'
                },
                {
                    name: 'InterventionLocation',
                    description: '개입 위치(대시보드/학습 화면/알림)와 인터페이스 매핑을 온톨로지로 표현'
                },
                {
                    name: 'LearningFlowState',
                    description: '학습 흐름 상태(정상/이탈/복귀)를 온톨로지로 표현하여 개입 필요성 판단에 활용'
                }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 개입 트리거 유형, 최적 타이밍, 개입 위치를 종합 분석하여 개입 진입 전략을 도출합니다. rules.yaml의 ①~⑧ 상황별 룰과 직접 연계됩니다.',
                answerAnalysis: '학습 지연 감지는 ① 룰이 분석하고, 침착도 변화는 ② 룰이 평가합니다. 루틴 진행률은 ③ 룰이 모니터링하고, 장기 패턴 이상은 ⑤ 룰이 탐지합니다.',
                ruleBasedActions: 'rules.yaml의 ① 룰이 학습 지연 감지 시 개입 위치를 추천하고, ② 룰이 감정/침착도 이상 시 타이밍을 결정하며, ③ 룰이 루틴 종료 전 점검을 수행합니다. ⑤ 룰이 장기 패턴 이상 시 루틴 재정렬 개입을 준비합니다.'
            }
        },
        2: { // 포괄형 질문 2: 개입 전달 전략
            questionSets: [
                {
                    title: '개입 방식 및 메시지 톤 결정',
                    questions: [
                        {
                            text: '학생의 감정 안정도와 피로도를 고려할 때 알림/메시지/채팅/호출 중 어떤 방식이 가장 적합한가?',
                            dataSources: ['emotion_stability', 'fatigue_level', 'intervention_method_effectiveness', 'intervention_method_type']
                        },
                        {
                            text: '집중도와 목표 중요도를 반영하여 메시지 톤(격려형/코치형/공감형/경고형)을 어떻게 선택할까?',
                            dataSources: ['concentration_level', 'goal_importance', 'message_tone_effectiveness', 'message_tone_type']
                        },
                        {
                            text: '최근 개입 반응 패턴(읽음률, 응답시간, 효과성)을 분석하여 이번 개입의 어조와 길이는 어떻게 조정할까?',
                            dataSources: ['recent_intervention_read_rate', 'recent_intervention_response_time', 'recent_intervention_effectiveness', 'message_length', 'message_tone']
                        },
                        {
                            text: '학생의 학습 감정 상태(Agent 05)와 피로도 수준에 따라 시각적 표현(이모지, 색상, 아이콘)을 어떻게 활용할까?',
                            dataSources: ['learning_emotion', 'emotion_state', 'fatigue_level', 'visual_expression_effectiveness', 'emoji_usage', 'color_scheme']
                        }
                    ]
                },
                {
                    title: '개입 전달 최적화 분석',
                    questions: [
                        {
                            text: '학생의 집중 시간대(Agent 01)와 일정 데이터를 종합하여 개입 전달 시점을 어떻게 결정할까?',
                            dataSources: ['focus_time_window', 'schedule_data', 'optimal_delivery_timing', 'intervention_delivery_time']
                        },
                        {
                            text: '목표 중요도와 학습 우선순위를 고려하여 개입 메시지의 강도와 빈도를 어떻게 조절할까?',
                            dataSources: ['goal_importance', 'learning_priority', 'intervention_intensity', 'intervention_frequency']
                        },
                        {
                            text: '이전 개입의 성공/실패 패턴을 학습하여 이번 개입의 전달 방식을 어떻게 개선할 수 있을까?',
                            dataSources: ['previous_intervention_success_pattern', 'previous_intervention_failure_pattern', 'delivery_method_improvement', 'pattern_learning']
                        }
                    ]
                }
            ],
            ontology: [
                {
                    name: 'InterventionMethod',
                    description: '개입 방식(알림/메시지/채팅/호출) 선택을 온톨로지로 표현 (Agent 20 핵심 온톨로지)'
                },
                {
                    name: 'MessageTone',
                    description: '메시지 톤(격려형/코치형/공감형/경고형)과 어조를 온톨로지로 표현'
                },
                {
                    name: 'InterventionDelivery',
                    description: '개입 전달 시점, 강도, 빈도를 온톨로지로 표현하여 최적화에 활용'
                },
                {
                    name: 'VisualExpression',
                    description: '시각적 표현(이모지, 색상, 아이콘) 활용을 온톨로지로 표현'
                }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 개입 방식, 메시지 톤, 전달 시점을 종합 분석하여 개입 전달 전략을 도출합니다. rules.yaml의 ②, ⑥, ⑧ 상황별 룰과 직접 연계됩니다.',
                answerAnalysis: '감정 안정도와 피로도는 ② 룰이 분석하여 개입 방식을 결정하고, 집중도와 목표 중요도는 ② 룰이 메시지 톤을 선택합니다. 최근 개입 반응 패턴은 ⑥ 룰이 학습하여 전달 방식을 최적화합니다.',
                ruleBasedActions: 'rules.yaml의 ② 룰이 감정/침착도 이상 시 메시지 톤과 전달시점을 결정하고, ⑥ 룰이 맞춤형 상호작용 전달 직전에 타이밍과 위치를 최적화합니다. ⑧ 룰이 이벤트성 개입 시 동기부여형 메시지를 구성합니다.'
            }
        },
        3: { // 포괄형 질문 3: 개입 설계 질문
            questionSets: [
                {
                    title: '개입 목적 설정 및 리스크 예측',
                    questions: [
                        {
                            text: '학생의 중장기 패턴과 개입 이력을 분석하여 이번 개입의 핵심 목적(정서 회복/루틴 복귀/집중 강화)은 무엇인가?',
                            dataSources: ['long_term_pattern', 'intervention_history', 'intervention_purpose', 'emotional_recovery_needed', 'routine_recovery_needed', 'focus_enhancement_needed']
                        },
                        {
                            text: '장기 패턴 이상과 개입 성공률 데이터를 기반으로 이번 개입의 예상 리스크는 무엇인가?',
                            dataSources: ['long_term_pattern_anomaly', 'intervention_success_rate', 'predicted_risks', 'risk_factors']
                        },
                        {
                            text: '반복 실패 요인과 교사/보호자 협력도를 고려하여 개입 실패 가능성을 어떻게 최소화할 수 있을까?',
                            dataSources: ['repeated_failure_factors', 'teacher_cooperation_level', 'parent_cooperation_level', 'intervention_failure_prevention']
                        }
                    ]
                },
                {
                    title: '사전 준비 및 리소스 배치',
                    questions: [
                        {
                            text: '개입 목적에 따라 필요한 사전 리소스(메시지 템플릿, 콘텐츠, 피드백 자료)는 무엇인가?',
                            dataSources: ['intervention_purpose', 'required_resources', 'message_templates', 'content_resources', 'feedback_materials']
                        },
                        {
                            text: '개입 실행 시 책임자(교사/멘토/시스템) 배치와 협력 체계는 어떻게 구성할까?',
                            dataSources: ['intervention_responsible_person', 'teacher_availability', 'mentor_availability', 'cooperation_system']
                        },
                        {
                            text: '개입 실행 후 모니터링 지표와 피드백 수집 방식을 어떻게 설계할까?',
                            dataSources: ['monitoring_indicators', 'feedback_collection_method', 'intervention_tracking_metrics', 'success_evaluation_criteria']
                        }
                    ]
                },
                {
                    title: '종합 개입 설계 및 시나리오 구성',
                    questions: [
                        {
                            text: '시험 대비 기간(④), 장기 패턴 이상(⑤), 멘토 개입 필요(⑦) 등 상황별로 개입 설계를 어떻게 차별화할까?',
                            dataSources: ['exam_preparation_period', 'long_term_pattern_anomaly', 'mentor_intervention_needed', 'situation_specific_design', 'intervention_scenario']
                        },
                        {
                            text: '개입 이력에서 성공한 패턴과 실패한 패턴을 학습하여 이번 개입 설계에 어떻게 반영할까?',
                            dataSources: ['successful_intervention_patterns', 'failed_intervention_patterns', 'pattern_learning', 'design_improvement']
                        },
                        {
                            text: '개입 실행 전 검증 단계와 롤백 계획은 어떻게 수립할까?',
                            dataSources: ['pre_intervention_verification', 'rollback_plan', 'safety_measures', 'contingency_plan']
                        }
                    ]
                }
            ],
            ontology: [
                {
                    name: 'InterventionPurpose',
                    description: '개입 목적(정서 회복/루틴 복귀/집중 강화)을 온톨로지로 표현 (Agent 20 핵심 온톨로지)'
                },
                {
                    name: 'InterventionRisk',
                    description: '개입 리스크 예측과 관리를 온톨로지로 표현'
                },
                {
                    name: 'InterventionResource',
                    description: '개입 사전 준비 리소스와 책임자 배치를 온톨로지로 표현'
                },
                {
                    name: 'InterventionScenario',
                    description: '상황별 개입 시나리오와 설계 패턴을 온톨로지로 표현'
                },
                {
                    name: 'InterventionHistory',
                    description: '개입 이력과 성공/실패 패턴을 온톨로지로 표현하여 학습에 활용'
                }
            ],
            analysis: {
                questionAnalysis: '이 질문 세트는 개입 목적 설정, 리스크 예측, 사전 준비를 종합 분석하여 개입 설계를 완성합니다. rules.yaml의 ④, ⑤, ⑦ 상황별 룰과 직접 연계됩니다.',
                answerAnalysis: '중장기 패턴 분석은 ⑤ 룰이 수행하고, 개입 성공률은 개입 이력 데이터에서 추출합니다. 반복 실패 요인은 ⑦ 룰이 분석하고, 교사/보호자 협력도는 협력 이력에서 평가합니다.',
                ruleBasedActions: 'rules.yaml의 ④ 룰이 시험 대비 기간 개입 설계를 수행하고, ⑤ 룰이 장기 패턴 이상 시 루틴 재정렬 개입을 준비하며, ⑦ 룰이 멘토/담임 개입 필요 시 준비 단계 메시지를 생성합니다.'
            }
        }
    };
    
    // Agent 22: 모듈개선
    window.dataBasedQuestionSets.agent22 = {
        1: {
            questionSets: [{
                title: '시스템 비효율 및 불안정성 진단',
                questions: [
                    { text: '에이전트별 실행 로그·룰 작동 빈도·리소스 사용량·실패 패턴을 통합 분석한 병목 지점, 불균형 동작, 데이터 누락 등 구조적 취약지점은?', dataSources: ['agent_execution_log', 'rule_activation_frequency', 'resource_usage', 'failure_pattern'] },
                    { text: '우선적으로 점검해야 할 모듈과 대응 절차는?', dataSources: ['priority_check_modules', 'response_procedures'] },
                    { text: '전체 에이전트들의 실행 데이터를 종합한 현재 시스템의 비효율이나 불안정성은?', dataSources: ['all_agent_execution_data', 'system_inefficiency', 'system_instability'] }
                ]
            }],
            ontology: [{ name: 'SystemInefficiencyInstability', description: '시스템 비효율 및 불안정성을 온톨로지로 표현' }],
            analysis: { questionAnalysis: '전체 에이전트들의 실행 데이터를 종합하여 시스템의 비효율이나 불안정성을 진단합니다.', answerAnalysis: '에이전트별 실행 로그와 룰 작동 빈도를 분석합니다.', ruleBasedActions: 'rules.yaml의 시스템 진단 룰이 트리거됩니다.' }
        },
        2: {
            questionSets: [{
                title: '룰 네트워크 최적화',
                questions: [
                    { text: 'rules.yaml 간 조건 중복·충돌 가능성·엣지케이스 누락 여부를 기준으로 한 룰 네트워크 최적화 전략(통합·모듈화·의존성 정리·검증 시퀀스)은?', dataSources: ['rule_condition_duplication', 'rule_conflict_possibility', 'edge_case_missing'] },
                    { text: '영향도·노력도 기반의 개선 우선순위 맵은?', dataSources: ['impact_effort_matrix', 'improvement_priority_map'] },
                    { text: '각 에이전트의 룰 구조와 분석 패턴을 비교한 최적의 개선 방향은?', dataSources: ['rule_structure_comparison', 'analysis_pattern_comparison', 'optimal_improvement_direction'] }
                ]
            }],
            ontology: [{ name: 'RuleNetworkOptimization', description: '룰 네트워크 최적화를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '각 에이전트의 룰 구조와 분석 패턴을 비교하여 최적의 개선 방향을 제안합니다.', answerAnalysis: '조건 중복, 충돌 가능성, 엣지케이스 누락을 분석합니다.', ruleBasedActions: 'rules.yaml의 룰 최적화 룰이 트리거됩니다.' }
        },
        3: {
            questionSets: [{
                title: '자동 진화 구조 설계',
                questions: [
                    { text: '자가 업그레이드 루프(데이터 수집 → 취약점 진단 → 3 File 생성 → AI 검토 → 검증 → 배포) 전체 점검은?', dataSources: ['data_collection', 'vulnerability_diagnosis', 'file_generation', 'ai_review', 'verification', 'deployment'] },
                    { text: '지속적 성능 향상·오류 자가복구·자동화 검증 파이프라인 중심의 중장기 진화 로드맵은?', dataSources: ['continuous_performance_improvement', 'error_self_recovery', 'automated_verification_pipeline'] },
                    { text: '장기적으로 안정적이고 자동 진화 가능한 구조로 가기 위한 업그레이드 루프와 검증 체계는?', dataSources: ['upgrade_loop', 'verification_system', 'long_term_stability'] }
                ]
            }],
            ontology: [{ name: 'AutoEvolutionStructure', description: '자동 진화 구조를 온톨로지로 표현' }],
            analysis: { questionAnalysis: '장기적으로 안정적이고 자동 진화 가능한 구조로 가기 위한 업그레이드 루프와 검증 체계를 설계합니다.', answerAnalysis: '자가 업그레이드 루프 전체를 점검합니다.', ruleBasedActions: 'rules.yaml의 진화 구조 설계 룰이 트리거됩니다.' }
        }
    };
    
    // ========== Agent 19 Ontology 사용 영역 추천 ==========
    // 
    // Agent 19의 상호작용 컨텐츠 생성 및 패키징 기능을 강화하기 위해
    // 다음과 같은 온톨로지 영역을 추가로 식별하고 추천합니다:
    //
    // 1. **상호작용 유형-템플릿 매핑 온톨로지 (InteractionTypeTemplateMapping)**
    //    - 목적: 상호작용 유형(텍스트 전달/루틴 개선/비선형/멀티턴 등)과 템플릿 간의
    //      매핑 관계를 체계적으로 표현하여 자동 선택 알고리즘의 정확도 향상
    //    - 활용: S1~S7 상황별 최적 템플릿 자동 선택, 템플릿 재사용 가능성 평가
    //    - 연계: rules.yaml의 S1R1~S7R3 룰, TMP1~TMP2 룰
    //
    // 2. **학습 상황-상호작용 전략 매핑 온톨로지 (LearningSituationInteractionMapping)**
    //    - 목적: 학습 상황(S1~S7)과 상호작용 전략 간의 다대다 매핑 관계를 표현하여
    //      복합 상황에서의 최적 전략 조합 도출
    //    - 활용: 복합 상황(이탈+지연, 피로+오답 등) 대응 전략 자동 생성
    //    - 연계: rules.yaml의 CR1~CR3 룰
    //
    // 3. **개인화 특성-템플릿 적합도 온톨로지 (PersonalizationTemplateFitness)**
    //    - 목적: MBTI, 학습 스타일, 정서 상태 등 개인 특성과 템플릿 적합도를
    //      온톨로지로 표현하여 맞춤형 패키징 자동화
    //    - 활용: 템플릿 재사용 가능성 평가, 맞춤형 UI/톤/링크 구성 자동화
    //    - 연계: rules.yaml의 MBTI1~MBTI2 룰, TMP1~TMP2 룰
    //
    // 4. **템플릿 라이브러리 관리 온톨로지 (TemplateLibraryManagement)**
    //    - 목적: 템플릿의 버전 관리, 재사용 빈도, 효과성 점수 등을 온톨로지로 표현하여
    //      템플릿 라이브러리의 지속적 개선 자동화
    //    - 활용: 템플릿 효율 평가, 템플릿 업데이트 우선순위 결정
    //    - 연계: rules.yaml의 INTERACTION_EFFECTIVENESS_TRACKING 룰, TMP1~TMP2 룰
    //
    // 5. **수학 교과 특화 상호작용 온톨로지 (MathSubjectSpecificInteraction)**
    //    - 목적: 수학 단원, 문제 유형, 오류 유형, 학습 단계 등 수학 교과 특화 요소와
    //      상호작용 전략 간의 매핑을 온톨로지로 표현
    //    - 활용: 수학 단원별/문제 유형별 맞춤 상호작용 자동 생성
    //    - 연계: rules.yaml의 MATH_UNIT_LINK_MAPPING, PROBLEM_TYPE_INTERACTION_TEMPLATE,
    //            MATH_LEARNING_STAGE_CONCEPT/PRACTICE/ADVANCED 룰
    //
    // 6. **상호작용 효과성 추적 온톨로지 (InteractionEffectivenessTracking)**
    //    - 목적: 상호작용 결과(참여율, 클릭률, 재진입 성공률 등)와 학습 행동 변화 간의
    //      인과 관계를 온톨로지로 표현하여 효과성 검증 자동화
    //    - 활용: 템플릿 효율 평가, 룰 보정, 피드백 루프 설계
    //    - 연계: rules.yaml의 INTERACTION_EFFECTIVENESS_TRACKING 룰
    //
    // 7. **링크 제공 전략 온톨로지 (LinkProvisionStrategy)**
    //    - 목적: 상황별/상호작용 유형별 링크 제공 전략(쉬운 승리 구간, 개념 보강, 대안 활동 등)을
    //      온톨로지로 표현하여 자동 링크 매핑
    //    - 활용: 상호작용 컨텐츠에 적절한 링크 자동 포함
    //    - 연계: rules.yaml의 LNK1~LNK3 룰, MATH_UNIT_LINK_MAPPING 등
    //
    // 8. **복합 상황 해결 경로 온톨로지 (ComplexSituationResolutionPath)**
    //    - 목적: 여러 학습 상황이 동시에 발생할 때의 해결 경로와 우선순위를
    //      온톨로지로 표현하여 복합 상황 대응 자동화
    //    - 활용: 복합 상황 감지 시 최적 해결 경로 자동 도출
    //    - 연계: rules.yaml의 CR1~CR3 룰
    //
    // 이러한 온톨로지들을 구축하면 Agent 19의 상호작용 컨텐츠 생성 및 패키징 기능이
    // 더욱 정교하고 자동화된 수준으로 발전할 수 있습니다.
})();

