<?php
/**
 * Agent 09 - Learning Management Analysis
 * File: agents/agent09_learning_management/agent.php
 *
 * Analyzes 5 key learning management patterns:
 * 1. 출결분석 (Attendance Analysis)
 * 2. 목표입력 분석 (Goal Input Analysis)
 * 3. 포모도르 (Pomodoro Analysis)
 * 4. 오답노트 작성 패턴 (Wrong Answer Note Writing Patterns)
 * 5. 테스트 응시 패턴 (Test Taking Patterns)
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;
// Set JSON response header
header('Content-Type: application/json');

try {
    // Determine action mode
    $action = $_GET['action'] ?? 'analyze';

    // Get student ID from request
    $studentid = $_GET['userid'] ?? $USER->id;

    if (empty($studentid)) {
        throw new Exception('Student ID is required - File: agent.php, Line: ' . __LINE__);
    }

    // Verify student exists
    $student = $DB->get_record('user', ['id' => $studentid], 'id, firstname, lastname', MUST_EXIST);

    // ============================================================
    // 1. 출결분석 (Attendance Analysis) - Mock Data
    // ============================================================
    $attendance_data = [
        'attendance_rate' => 87.5,
        'total_days' => 40,
        'attended_days' => 35,
        'absent_days' => 5,
        'late_days' => 3,
        'trend' => 'improving',
        'weekly_pattern' => [
            'Mon' => 95,
            'Tue' => 90,
            'Wed' => 85,
            'Thu' => 80,
            'Fri' => 75
        ],
        'time_pattern' => [
            'morning' => 95,    // 오전 출석률
            'afternoon' => 85,  // 오후 출석률
            'evening' => 70     // 저녁 출석률
        ],
        'insights' => [
            '전체적으로 우수한 출석률을 보이고 있습니다',
            '금요일 오후 시간대 출석률이 낮은 편입니다',
            '최근 2주간 출석률이 개선되는 추세입니다'
        ],
        'risk_level' => 'low',
        'recommendations' => [
            '현재 출석 패턴을 유지하세요',
            '금요일 학습 계획을 조정하여 참여율을 높일 수 있습니다'
        ]
    ];

    // ============================================================
    // 2. 목표입력 분석 (Goal Input Analysis) - Mock Data
    // ============================================================
    $goal_data = [
        'total_goals' => 12,
        'completed_goals' => 8,
        'in_progress_goals' => 3,
        'abandoned_goals' => 1,
        'completion_rate' => 66.7,
        'average_goal_duration' => 14.5, // days
        'goal_quality_score' => 78,
        'goal_categories' => [
            'academic' => 60,
            'skill_development' => 30,
            'personal_growth' => 10
        ],
        'recent_goals' => [
            [
                'title' => '수학 미적분 완성',
                'status' => 'completed',
                'progress' => 100,
                'deadline' => '2025-10-15',
                'actual_completion' => '2025-10-14'
            ],
            [
                'title' => '영어 단어 500개 암기',
                'status' => 'in_progress',
                'progress' => 65,
                'deadline' => '2025-10-25'
            ],
            [
                'title' => '물리 실험 보고서 작성',
                'status' => 'in_progress',
                'progress' => 40,
                'deadline' => '2025-10-30'
            ]
        ],
        'insights' => [
            '목표 설정 능력이 우수하며 달성률도 높은 편입니다',
            '장기 목표보다 단기 목표에서 더 높은 성취율을 보입니다',
            '학업 관련 목표가 전체의 60%를 차지합니다'
        ],
        'recommendations' => [
            '🎯 장기 목표를 여러 단계로 나누어 관리하세요',
            '📊 균형잡힌 목표 설정을 위해 다양한 영역의 목표를 고려하세요',
            '⏰ 목표 달성 기한을 현실적으로 설정하는 습관이 좋습니다'
        ]
    ];

    // ============================================================
    // 3. 포모도르 (Pomodoro Analysis) - Mock Data
    // ============================================================
    $pomodoro_data = [
        'total_sessions' => 156,
        'completed_sessions' => 142,
        'incomplete_sessions' => 14,
        'completion_rate' => 91.0,
        'total_study_time' => 3900, // minutes = 65 hours
        'average_session_length' => 25.5, // minutes
        'average_break_length' => 6.2, // minutes
        'peak_performance_time' => '14:00-16:00',
        'daily_pattern' => [
            '06:00-09:00' => 8,
            '09:00-12:00' => 18,
            '12:00-15:00' => 12,
            '15:00-18:00' => 24,
            '18:00-21:00' => 20,
            '21:00-24:00' => 10
        ],
        'weekly_stats' => [
            'Mon' => 28,
            'Tue' => 26,
            'Wed' => 24,
            'Thu' => 22,
            'Fri' => 18,
            'Sat' => 16,
            'Sun' => 14
        ],
        'focus_quality' => [
            'excellent' => 45,  // %
            'good' => 35,
            'fair' => 15,
            'poor' => 5
        ],
        'insights' => [
            '포모도로 기법을 효과적으로 활용하고 있습니다',
            '오후 2-4시 사이 집중력이 가장 높습니다',
            '주 초반에 학습 세션이 더 많이 이루어집니다',
            '휴식 시간 관리가 우수합니다'
        ],
        'recommendations' => [
            '🕐 현재의 오후 집중 시간대를 최대한 활용하세요',
            '📈 주말 학습 시간을 조금 늘리면 더 균형잡힌 학습이 가능합니다',
            '💡 집중도가 낮은 시간대는 가벼운 복습 활동에 활용하세요'
        ]
    ];

    // ============================================================
    // 4. 오답노트 작성 패턴 (Wrong Answer Note Writing Patterns) - Mock Data
    // ============================================================
    $wrong_note_data = [
        'total_notes' => 89,
        'notes_with_solution' => 76,
        'notes_reviewed' => 62,
        'notes_mastered' => 45,
        'average_review_count' => 2.8,
        'note_quality_score' => 82,
        'subject_distribution' => [
            '수학' => 38,
            '과학' => 24,
            '영어' => 15,
            '사회' => 8,
            '기타' => 4
        ],
        'error_type_analysis' => [
            'concept_misunderstanding' => 35,  // %
            'careless_mistake' => 28,
            'calculation_error' => 20,
            'insufficient_practice' => 12,
            'other' => 5
        ],
        'review_pattern' => [
            'immediate' => 25,     // % - 바로 복습
            'within_1day' => 35,   // 1일 이내
            'within_3days' => 25,  // 3일 이내
            'within_week' => 10,   // 1주일 이내
            'delayed' => 5         // 1주일 이상 지연
        ],
        'mastery_time' => [
            'fast' => 30,      // < 3 reviews
            'normal' => 50,    // 3-5 reviews
            'slow' => 20       // > 5 reviews
        ],
        'recent_notes' => [
            [
                'subject' => '수학',
                'topic' => '미적분 - 극한값',
                'error_type' => '개념 오해',
                'created' => '2025-10-15',
                'review_count' => 3,
                'mastered' => true
            ],
            [
                'subject' => '물리',
                'topic' => '운동량 보존',
                'error_type' => '계산 실수',
                'created' => '2025-10-16',
                'review_count' => 1,
                'mastered' => false
            ],
            [
                'subject' => '화학',
                'topic' => '산화환원 반응',
                'error_type' => '부족한 연습',
                'created' => '2025-10-16',
                'review_count' => 2,
                'mastered' => false
            ]
        ],
        'insights' => [
            '오답노트 작성 습관이 잘 형성되어 있습니다',
            '수학 과목에서 가장 많은 오답노트를 작성하고 있습니다',
            '개념 이해 관련 오류가 가장 많은 비중을 차지합니다',
            '복습 주기가 효과적으로 관리되고 있습니다'
        ],
        'recommendations' => [
            '🎯 개념 오해 문제는 기본 개념 복습부터 시작하세요',
            '📝 실수 패턴을 분석하여 체크리스트를 만들어보세요',
            '🔄 마스터한 내용도 주기적으로 재점검하세요',
            '📚 과학 과목의 오답노트 작성을 늘려보세요'
        ]
    ];

    // ============================================================
    // 5. 테스트 응시 패턴 (Test Taking Patterns) - Mock Data
    // ============================================================
    $test_pattern_data = [
        'total_tests' => 24,
        'completed_tests' => 22,
        'incomplete_tests' => 2,
        'average_score' => 78.5,
        'highest_score' => 95,
        'lowest_score' => 62,
        'score_trend' => 'improving',
        'average_time_per_test' => 42.5, // minutes
        'time_management_score' => 85,
        'subject_performance' => [
            '수학' => ['score' => 85, 'tests' => 8],
            '과학' => ['score' => 76, 'tests' => 6],
            '영어' => ['score' => 82, 'tests' => 5],
            '사회' => ['score' => 72, 'tests' => 5]
        ],
        'difficulty_performance' => [
            'easy' => 92,
            'medium' => 78,
            'hard' => 64
        ],
        'test_time_pattern' => [
            'morning' => ['count' => 8, 'avg_score' => 82],
            'afternoon' => ['count' => 10, 'avg_score' => 78],
            'evening' => ['count' => 6, 'avg_score' => 74]
        ],
        'question_solving_pattern' => [
            'sequential' => 65,    // % - 순차적 풀이
            'difficulty_based' => 25,  // 난이도 기반
            'mixed' => 10         // 혼합
        ],
        'review_behavior' => [
            'reviews_answers' => 80,  // % - 답안 재검토 비율
            'average_review_time' => 8.5  // minutes
        ],
        'recent_tests' => [
            [
                'subject' => '수학',
                'title' => '미적분 중간고사',
                'score' => 88,
                'date' => '2025-10-15',
                'time_taken' => 45,
                'difficulty' => 'medium'
            ],
            [
                'subject' => '물리',
                'title' => '역학 단원평가',
                'score' => 76,
                'date' => '2025-10-14',
                'time_taken' => 40,
                'difficulty' => 'hard'
            ],
            [
                'subject' => '영어',
                'title' => '독해 실전모의고사',
                'score' => 82,
                'date' => '2025-10-13',
                'time_taken' => 50,
                'difficulty' => 'medium'
            ]
        ],
        'insights' => [
            '전반적인 성적이 향상되는 추세입니다',
            '수학 과목에서 가장 우수한 성적을 보입니다',
            '오전 시간대 테스트 점수가 가장 높습니다',
            '시간 관리 능력이 우수합니다',
            '답안 재검토 습관이 잘 형성되어 있습니다'
        ],
        'recommendations' => [
            '🎯 어려운 난이도 문제 해결 능력 향상에 집중하세요',
            '⏰ 중요한 시험은 오전 시간대를 활용하세요',
            '📊 과학 과목 학습 시간을 늘려보세요',
            '💡 틀린 문제 유형을 분석하여 약점을 보완하세요'
        ]
    ];

    // ============================================================
    // Generate Agent Prompts for Other Agents
    // ============================================================
    $agent_prompts = [
        'for_agent_10' => [
            'title' => 'Agent 10 (개념노트 분석)을 위한 프롬프트',
            'prompt' => "학생의 학습 패턴 분석 결과:\n" .
                "- 출석률: {$attendance_data['attendance_rate']}%\n" .
                "- 목표 달성률: {$goal_data['completion_rate']}%\n" .
                "- 포모도로 완성률: {$pomodoro_data['completion_rate']}%\n" .
                "- 오답노트 작성: {$wrong_note_data['total_notes']}건\n" .
                "- 평균 시험 점수: {$test_pattern_data['average_score']}점\n\n" .
                "최적 학습 시간대: {$pomodoro_data['peak_performance_time']}\n" .
                "주요 학습 과목: 수학, 과학, 영어\n\n" .
                "개념노트 작성 시 다음 사항을 고려하세요:\n" .
                "1. 개념 오해가 가장 많은 영역에 집중\n" .
                "2. 수학 미적분 부분 심화 학습 필요\n" .
                "3. 오후 2-4시 집중 시간대 활용 권장"
        ],
        'for_agent_11' => [
            'title' => 'Agent 11 (문제노트 분석)을 위한 프롬프트',
            'prompt' => "오답 패턴 종합 분석:\n" .
                "- 총 오답노트: {$wrong_note_data['total_notes']}건\n" .
                "- 마스터 완료: {$wrong_note_data['notes_mastered']}건\n" .
                "- 평균 복습 횟수: {$wrong_note_data['average_review_count']}회\n\n" .
                "주요 오류 유형:\n" .
                "1. 개념 오해: {$wrong_note_data['error_type_analysis']['concept_misunderstanding']}%\n" .
                "2. 실수: {$wrong_note_data['error_type_analysis']['careless_mistake']}%\n" .
                "3. 계산 오류: {$wrong_note_data['error_type_analysis']['calculation_error']}%\n\n" .
                "문제노트 분석 시 집중할 영역:\n" .
                "- 수학 미적분 개념 재학습\n" .
                "- 물리 운동량 보존 연습 문제 추가\n" .
                "- 화학 산화환원 반응 심화 학습"
        ],
        'for_agent_15' => [
            'title' => 'Agent 15 (문제 재정의 & 개선방안)을 위한 프롬프트',
            'prompt' => "학습 관리 종합 진단:\n\n" .
                "강점:\n" .
                "- 높은 출석률과 목표 달성률\n" .
                "- 우수한 포모도로 활용\n" .
                "- 체계적인 오답노트 관리\n" .
                "- 꾸준한 성적 향상 추세\n\n" .
                "개선 필요 영역:\n" .
                "- 금요일 오후 학습 참여율 저조\n" .
                "- 어려운 난이도 문제 해결력 부족\n" .
                "- 과학 과목 상대적 취약\n" .
                "- 개념 오해 비율 높음\n\n" .
                "핵심 개선 방안:\n" .
                "1. 주말 학습 시간 증대\n" .
                "2. 과학 과목 집중 학습 계획\n" .
                "3. 기본 개념 재학습 프로그램\n" .
                "4. 고난도 문제 풀이 연습"
        ],
        'for_agent_19' => [
            'title' => 'Agent 19 (상호작용 컨텐츠 생성)을 위한 프롬프트',
            'prompt' => "맞춤형 학습 컨텐츠 생성 가이드:\n\n" .
                "학생 특성:\n" .
                "- 오후 2-4시 최고 집중력\n" .
                "- 포모도로 기법 효과적 활용\n" .
                "- 체계적 학습 관리 성향\n" .
                "- 수학 강점, 과학 보완 필요\n\n" .
                "권장 컨텐츠 유형:\n" .
                "1. 오후 시간대 집중 학습용 심화 문제\n" .
                "2. 25분 단위 구조화된 학습 모듈\n" .
                "3. 개념 이해 중심의 시각화 자료\n" .
                "4. 과학 과목 기초 강화 프로그램\n" .
                "5. 실전 모의고사 (오전 시간대 권장)\n\n" .
                "상호작용 방식:\n" .
                "- 목표 기반 학습 진도 추적\n" .
                "- 즉각적인 피드백 제공\n" .
                "- 오답 자동 노트 생성\n" .
                "- 주간 학습 리포트"
        ]
    ];

    // ============================================================
    // Compile Final Response
    // ============================================================
    // 지식파일 로드
    $knowledgePath = __DIR__ . '/agent09_learning_management.md';
    $knowledgeText = file_exists($knowledgePath) ? file_get_contents($knowledgePath) : '';

    // Overall insights
    $overall_insights = [
        '학습 관리 능력이 전반적으로 우수합니다',
        '체계적인 학습 습관이 잘 형성되어 있습니다',
        '성적이 꾸준히 향상되고 있습니다',
        '시간 관리와 자기 주도 학습 능력이 뛰어납니다',
        '과학 과목과 고난도 문제에 더 집중이 필요합니다'
    ];

    $priority_actions = [
        '🎯 과학 과목 학습 시간 20% 증대',
        '📚 주말 학습 루틴 구축',
        '💡 개념 이해 중심 학습 강화',
        '⏰ 오전 시간대 시험 대비 집중',
        '🔍 어려운 문제 풀이 연습 강화'
    ];

    $response = [
        'success' => true,
        'student_id' => $studentid,
        'student_name' => $student->firstname . ' ' . $student->lastname,
        'analysis_date' => date('Y-m-d H:i:s'),
        'data' => [
            'attendance' => $attendance_data,
            'goals' => $goal_data,
            'pomodoro' => $pomodoro_data,
            'wrong_notes' => $wrong_note_data,
            'test_patterns' => $test_pattern_data
        ],
        'agent_prompts' => $agent_prompts,
        'knowledge' => $knowledgeText,
        'overall_insights' => $overall_insights,
        'priority_actions' => $priority_actions
    ];

    // ============================================================
    // Handle Artifact Creation if requested
    // ============================================================
    if ($action === 'create_artifact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Generate artifact ID
        $artifact_id = 'artf_agent09_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 8);

        // Create summary text
        $summary_text = "Agent 09 학습관리 분석 결과\n\n";
        $summary_text .= "학생: {$student->firstname} {$student->lastname}\n";
        $summary_text .= "출석률: {$attendance_data['attendance_rate']}%\n";
        $summary_text .= "목표 달성률: {$goal_data['completion_rate']}%\n";
        $summary_text .= "포모도로 완성률: {$pomodoro_data['completion_rate']}%\n";
        $summary_text .= "평균 시험 점수: {$test_pattern_data['average_score']}점\n\n";
        $summary_text .= "종합 평가: " . implode(', ', array_slice($overall_insights, 0, 2));

        // Call artifacts API
        $artifact_payload = [
            'artifact_id' => $artifact_id,
            'agent_id' => 9,
            'student_id' => $studentid,
            'task_id' => $_POST['task_id'] ?? null,
            'summary_text' => $summary_text,
            'full_data' => [
                'analysis_date' => date('Y-m-d H:i:s'),
                'data' => $response['data'],
                'agent_prompts' => $agent_prompts,
                'overall_insights' => $overall_insights,
                'priority_actions' => $priority_actions
            ]
        ];

        // Make internal API call to create artifact
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/api/artifacts.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($artifact_payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Cookie: ' . $_SERVER['HTTP_COOKIE']
        ]);

        $artifact_result = curl_exec($ch);
        $artifact_response = json_decode($artifact_result, true);
        curl_close($ch);

        if ($artifact_response && $artifact_response['success']) {
            $response['artifact'] = [
                'created' => true,
                'artifact_id' => $artifact_id,
                'message' => 'Artifact created successfully'
            ];
        } else {
            $response['artifact'] = [
                'created' => false,
                'error' => $artifact_response['error'] ?? 'Failed to create artifact - File: agent.php, Line: ' . __LINE__
            ];
        }
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => 'agent.php',
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Database Tables Used:
 * - mdl_user: Student information (id, firstname, lastname)
 *
 * Note: This version uses mock data for demonstration.
 * Production version should query:
 * - mdl_logstore_standard_log: For attendance analysis
 * - mdl_course_modules_completion: For goal/activity tracking
 * - Custom pomodoro_sessions table: For pomodoro data
 * - Custom wrong_notes table: For error pattern analysis
 * - mdl_quiz_attempts + mdl_quiz_grades: For test pattern analysis
 */
