<?php
session_start();
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// Get userid from URL parameter with proper validation
$userid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;

// Validation and fallback
if ($userid <= 0) {
    // Try Moodle user as fallback
    if (isset($USER->id) && $USER->id > 0) {
        $userid = $USER->id;
        error_log("Using Moodle USER->id as fallback: " . $userid);
    } else {
        error_log("WARNING: No valid userid found in GET parameter or Moodle session");
    }
}

// Debug logging
error_log("Final userid value: " . $userid); 
// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'save_answer') {
        if (!isset($_SESSION['answers'])) {
            $_SESSION['answers'] = array();
        }
        if (!isset($_SESSION['qa_texts'])) {
            $_SESSION['qa_texts'] = array();
        }

        // Store the answer value
        $questionId = $_POST['question_id'];
        $value = $_POST['value'];
        $_SESSION['answers'][$questionId] = $value;

        // Build QA text if question data is provided
        if (isset($_POST['question_text']) && isset($_POST['answer_text']) && isset($_POST['question_number'])) {
            $questionNum = $_POST['question_number'];
            $questionText = $_POST['question_text'];
            $answerText = $_POST['answer_text'];

            // Format QA text (same format as in helper file)
            $qaText = "Q{$questionNum}: {$questionText}\nA: {$answerText} (점수: {$value})";

            // Store in session with proper field name
            $qaField = sprintf('qa%02d', $questionNum);
            $_SESSION['qa_texts'][$qaField] = $qaText;

            // Debug logging
            error_log("Saved QA text for {$qaField}: " . substr($qaText, 0, 50) . "...");

            // Optionally save to database immediately (incremental save)
            // This part will be implemented after we add the incremental save function
        }

        echo json_encode(['status' => 'success', 'qa_saved' => isset($_SESSION['qa_texts'])]);
        exit;
    }

    if ($_POST['action'] === 'reset_assessment') {
        $_SESSION['answers'] = array();
        $_SESSION['qa_texts'] = array();
        $_SESSION['current_question'] = -1;
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($_POST['action'] === 'get_results') {
        $answers = $_SESSION['answers'] ?? array();
        echo json_encode(['answers' => $answers]);
        exit;
    }

    if ($_POST['action'] === 'save_results') {
        // Include the FINAL helper functions with correct field mapping
        require_once(__DIR__ . '/includes/learning_assessment_helper_final.php');

        $results = json_decode($_POST['results'], true);

        // Get answers from POST first, fallback to session
        if (isset($_POST['answers'])) {
            $answers = json_decode($_POST['answers'], true);
            error_log('Using answers from POST data: ' . count($answers) . ' answers');
        } else {
            $answers = $_SESSION['answers'] ?? [];
            error_log('Using answers from SESSION: ' . count($answers) . ' answers');
        }

        // Get user ID from POST request (not from GET)
        $saveUserId = isset($_POST['userid']) ? intval($_POST['userid']) : 0;

        // Validation and fallback
        if ($saveUserId <= 0) {
            error_log('WARNING: Invalid or missing userid in POST request');
            // Try to fallback to Moodle user if available
            if (isset($USER->id) && $USER->id > 0) {
                $saveUserId = $USER->id;
                error_log('Using Moodle USER->id as fallback: ' . $saveUserId);
            }
        }

        // Get questions array (defined later in the file, need to include here)
        $questions = getQuestionsArray();

        // Debug logging
        error_log('=== SAVE_RESULTS DEBUG ===');
        error_log('User ID: ' . $saveUserId);
        error_log('Session ID: ' . session_id());
        error_log('Answers count: ' . count($answers));
        error_log('Answers: ' . json_encode($answers));
        error_log('Results: ' . json_encode($results));
        error_log('Questions count: ' . count($questions));

        try {
            // Save to database with questions for QA text storage
            $insertId = saveLearningAssessmentResults($saveUserId, $answers, $results, session_id(), $questions);

            if ($insertId) {
                // Clear session data after successful save
                unset($_SESSION['answers']);
                unset($_SESSION['qa_texts']);

                // Verify the saved data
                error_log('Successfully saved with ID: ' . $insertId);

                // Optional: Verify saved data
                $savedRecord = $DB->get_record('alt42o_learning_assessment_results', ['id' => $insertId]);
                if ($savedRecord) {
                    error_log('Verification - Some fields from saved record:');
                    error_log('  reading_score: ' . ($savedRecord->reading_score ?? 'NULL'));
                    error_log('  qa01: ' . substr($savedRecord->qa01 ?? 'NULL', 0, 50));
                    error_log('  overall_total: ' . ($savedRecord->overall_total ?? 'NULL'));
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => '학습 스타일 평가가 저장되었습니다.',
                    'assessment_id' => $insertId
                ]);
            } else {
                error_log('ERROR: saveLearningAssessmentResults returned false');
                echo json_encode([
                    'status' => 'error',
                    'message' => '저장 중 오류가 발생했습니다.'
                ]);
            }
        } catch (Exception $e) {
            error_log('Learning assessment save error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            echo json_encode([
                'status' => 'error',
                'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// Initialize session variables
if (!isset($_SESSION['answers'])) {
    $_SESSION['answers'] = array();
}
if (!isset($_SESSION['qa_texts'])) {
    $_SESSION['qa_texts'] = array();
}
if (!isset($_SESSION['current_question'])) {
    $_SESSION['current_question'] = -1;
}

// Function to get questions array
function getQuestionsArray() {
    return [
    [
        'id' => 'reading',
        'category' => '인지',
        'question' => '수학 문제를 풀 때, 문제를 어떻게 읽나요?',
        'options' => [
            ['value' => 5, 'label' => '끝까지 꼼꼼히 여러 번 읽어요'],
            ['value' => 4, 'label' => '한 번은 천천히 끝까지 읽어요'],
            ['value' => 3, 'label' => '대충 읽고 바로 풀기 시작해요'],
            ['value' => 2, 'label' => '긴 문제는 읽다가 포기할 때가 많아요']
        ]
    ],
    [
        'id' => 'persistence',
        'category' => '행동',
        'question' => '어려운 문제를 만났을 때 보통 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '끝까지 붙잡고 꼭 풀어내려고 해요'],
            ['value' => 4, 'label' => '30분 정도는 고민해봐요'],
            ['value' => 3, 'label' => '10분 정도 시도하다가 답지를 봐요'],
            ['value' => 2, 'label' => '어려워 보이면 바로 넘겨요']
        ]
    ],
    [
        'id' => 'questioning',
        'category' => '행동',
        'question' => '모르는 내용이 있을 때 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '바로 선생님께 질문해요'],
            ['value' => 4, 'label' => '정리해서 나중에 물어봐요'],
            ['value' => 3, 'label' => '친구한테만 물어봐요'],
            ['value' => 2, 'label' => '그냥 넘어가는 편이에요']
        ]
    ],
    [
        'id' => 'timeManagement',
        'category' => '행동',
        'question' => '하루 중 수학 공부 시간을 어떻게 관리하고 있나요?',
        'options' => [
            ['value' => 5, 'label' => '계획표를 만들어서 규칙적으로 해요'],
            ['value' => 4, 'label' => '대략적인 시간은 정해두고 해요'],
            ['value' => 3, 'label' => '기분 내킬 때 해요'],
            ['value' => 2, 'label' => '시험 기간에만 몰아서 해요']
        ]
    ],
    [
        'id' => 'conceptUnderstanding',
        'category' => '인지',
        'question' => '새로운 수학 개념을 배울 때 어떤 스타일인가요?',
        'options' => [
            ['value' => 5, 'label' => '원리를 이해하려고 "왜?"를 계속 물어봐요'],
            ['value' => 4, 'label' => '예제를 통해 패턴을 찾아요'],
            ['value' => 3, 'label' => '공식을 외워서 문제를 풀어요'],
            ['value' => 2, 'label' => '이해가 안 되면 그냥 외워요']
        ]
    ],
    [
        'id' => 'errorAnalysis',
        'category' => '인지',
        'question' => '틀린 문제를 다시 볼 때 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '왜 틀렸는지 분석하고 비슷한 문제를 더 풀어요'],
            ['value' => 4, 'label' => '풀이를 보고 이해하려고 노력해요'],
            ['value' => 3, 'label' => '답만 확인하고 넘어가요'],
            ['value' => 2, 'label' => '틀린 문제는 잘 안 봐요']
        ]
    ],
    [
        'id' => 'logicalThinking',
        'category' => '인지',
        'question' => '문제를 풀 때 어떤 방식을 선호하나요?',
        'options' => [
            ['value' => 5, 'label' => '여러 방법으로 풀어보고 가장 좋은 걸 찾아요'],
            ['value' => 4, 'label' => '단계별로 차근차근 풀어나가요'],
            ['value' => 3, 'label' => '아는 방법 하나로만 풀어요'],
            ['value' => 2, 'label' => '감으로 푸는 경우가 많아요']
        ]
    ],
    [
        'id' => 'mathExpression',
        'category' => '인지',
        'question' => '수학 풀이를 쓸 때 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '과정을 깔끔하게 정리해서 써요'],
            ['value' => 4, 'label' => '중요한 과정은 다 써요'],
            ['value' => 3, 'label' => '머릿속으로 계산하고 답만 써요'],
            ['value' => 2, 'label' => '풀이 과정 쓰는 게 귀찮아요']
        ]
    ],
    [
        'id' => 'mathAnxiety',
        'category' => '감정',
        'question' => '수학 시험을 앞두고 어떤 기분이 드나요?',
        'options' => [
            ['value' => 5, 'label' => '자신 있어요! 빨리 보고 싶어요'],
            ['value' => 4, 'label' => '조금 긴장되지만 잘 볼 수 있을 거예요'],
            ['value' => 3, 'label' => '많이 떨리고 불안해요'],
            ['value' => 2, 'label' => '너무 무서워서 피하고 싶어요']
        ]
    ],
    [
        'id' => 'resilience',
        'category' => '감정',
        'question' => '문제를 틀렸을 때 당신의 마음은 어떤가요?',
        'options' => [
            ['value' => 5, 'label' => '다음엔 꼭 맞춰야지! 하고 의욕이 생겨요'],
            ['value' => 4, 'label' => '아쉽지만 다시 도전해요'],
            ['value' => 3, 'label' => '속상해서 잠깐 쉬어요'],
            ['value' => 2, 'label' => '자신감이 떨어지고 포기하고 싶어요']
        ]
    ],
    [
        'id' => 'motivation',
        'category' => '감정',
        'question' => '수학 공부를 하는 가장 큰 이유는 무엇인가요?',
        'options' => [
            ['value' => 5, 'label' => '수학이 재미있고 더 잘하고 싶어서요'],
            ['value' => 4, 'label' => '원하는 진로에 필요해서요'],
            ['value' => 3, 'label' => '부모님이 시켜서요'],
            ['value' => 2, 'label' => '안 하면 혼나니까요']
        ]
    ],
    [
        'id' => 'stressManagement',
        'category' => '감정',
        'question' => '수학 공부가 스트레스일 때 어떻게 하나요?',
        'options' => [
            ['value' => 5, 'label' => '잠깐 쉬었다가 다시 집중해요'],
            ['value' => 4, 'label' => '쉬운 문제부터 다시 시작해요'],
            ['value' => 3, 'label' => '그날은 수학 공부를 안 해요'],
            ['value' => 2, 'label' => '며칠씩 수학을 피해요']
        ]
    ],
    [
        'id' => 'studyHabits',
        'category' => '행동',
        'question' => '평소 수학 공부 패턴은 어떤가요?',
        'options' => [
            ['value' => 5, 'label' => '매일 정해진 시간에 꾸준히 해요'],
            ['value' => 4, 'label' => '일주일에 4-5일은 해요'],
            ['value' => 3, 'label' => '숙제 있을 때만 해요'],
            ['value' => 2, 'label' => '시험 전에만 벼락치기해요']
        ]
    ],
    [
        'id' => 'concentration',
        'category' => '행동',
        'question' => '수학 문제 하나를 집중해서 풀 수 있는 시간은?',
        'options' => [
            ['value' => 5, 'label' => '1시간 이상도 가능해요'],
            ['value' => 4, 'label' => '30분 정도는 집중할 수 있어요'],
            ['value' => 3, 'label' => '15분 정도면 힘들어요'],
            ['value' => 2, 'label' => '5분만 지나도 딴 생각을 해요']
        ]
    ],
    [
        'id' => 'collaboration',
        'category' => '행동',
        'question' => '친구들과 함께 수학 공부할 때는 어떤가요?',
        'options' => [
            ['value' => 5, 'label' => '서로 가르치고 배우면서 함께 성장해요'],
            ['value' => 4, 'label' => '모르는 것만 물어보고 도움을 줘요'],
            ['value' => 3, 'label' => '혼자 하는 게 더 편해요'],
            ['value' => 2, 'label' => '같이 하면 집중이 안 돼요']
        ]
    ],
    [
        'id' => 'selfDirected',
        'category' => '인지',
        'question' => '마지막 질문이에요! 자신의 수학 실력을 어떻게 생각하나요?',
        'options' => [
            ['value' => 5, 'label' => '내 강점과 약점을 정확히 알고 있어요'],
            ['value' => 4, 'label' => '대략적으로는 알고 있어요'],
            ['value' => 3, 'label' => '잘 모르겠어요'],
            ['value' => 2, 'label' => '생각해본 적이 없어요']
        ]
    ]
];
}

// Removed problematic userid overwrite - userid already set from $_GET at line 5
// Check user role if we have a valid Moodle user
if (isset($USER->id) && $USER->id > 0) {
    $userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid=? AND fieldid='22'", array($USER->id));
    $role = $userrole ? $userrole->role : '';
}

// Get questions array
$questions = getQuestionsArray();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>카이스트 터치수학 - 학습 스타일 평가</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans KR', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        h1, h2, h3, h4 {
            color: #1f2937;
        }

        .text-center {
            text-align: center;
        }

        .mb-8 { margin-bottom: 2rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-2 { margin-bottom: 0.5rem; }

        /* Progress Bar */
        .progress-container {
            margin-bottom: 2rem;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: #e5e7eb;
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #60a5fa 0%, #a78bfa 100%);
            border-radius: 9999px;
            transition: width 0.5s ease;
        }

        /* Question Display */
        .question-area {
            min-height: 120px;
            margin-bottom: 2rem;
        }

        .question-text {
            font-size: 1.25rem;
            line-height: 1.75;
            color: #1f2937;
            white-space: pre-line;
        }

        .typing-cursor {
            display: inline-block;
            width: 2px;
            height: 1.5rem;
            background: #3b82f6;
            animation: blink 1s infinite;
            margin-left: 2px;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }

        /* Buttons */
        .btn {
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2563eb 0%, #7c3aed 100%);
            transform: scale(1.02);
        }

        .btn-secondary {
            background: #4b5563;
            color: white;
        }

        .btn-secondary:hover {
            background: #374151;
        }

        .btn-success {
            background: linear-gradient(90deg, #10b981 0%, #14b8a6 100%);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #059669 0%, #0d9488 100%);
        }

        .btn-full {
            width: 100%;
        }

        /* Options */
        .options-container {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .option-btn {
            width: 100%;
            text-align: left;
            padding: 1rem 1.5rem;
            background: #f9fafb;
            border: 2px solid transparent;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #374151;
            font-size: 1rem;
            animation: slideIn 0.5s ease-out backwards;
        }

        .option-btn:hover {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            transform: scale(1.02);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Results */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .result-card {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .result-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .result-icon {
            width: 24px;
            height: 24px;
            margin-right: 0.5rem;
        }

        .result-score {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .result-level {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
        }

        .level-excellent { color: #059669; }
        .level-good { color: #2563eb; }
        .level-average { color: #d97706; }
        .level-needs-improvement { color: #dc2626; }

        .summary-card {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .area-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .strength-card {
            background: rgba(16, 185, 129, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .strength-card h4 {
            color: #059669;
            margin-bottom: 0.75rem;
        }

        .weakness-card {
            background: rgba(239, 68, 68, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .weakness-card h4 {
            color: #dc2626;
            margin-bottom: 0.75rem;
        }

        .area-list {
            list-style: none;
        }

        .area-list li {
            color: #374151;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        /* Button Group */
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .card {
                padding: 1.5rem;
            }

            .results-grid {
                grid-template-columns: 1fr;
            }

            .area-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Hide elements initially */
        .hidden {
            display: none;
        }

        /* Icons using Unicode */
        .icon-chevron-right::after { content: ' →'; }
        .icon-refresh::before { content: '↻ '; }
        .icon-print::before { content: '🖨️ '; }
        .icon-user::before { content: '👤 '; }
        .icon-brain::before { content: '🧠 '; }
        .icon-heart::before { content: '❤️ '; }
        .icon-activity::before { content: '⚡ '; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- Welcome Screen -->
            <div id="welcomeScreen">
                <div class="question-area">
                    <div id="welcomeText" class="question-text"></div>
                    <span id="typingCursor" class="typing-cursor"></span>
                </div>

                <div id="startButtonContainer" class="hidden">
                    <button id="startBtn" class="btn btn-primary btn-full icon-chevron-right">
                        시작하기
                    </button>
                    <div class="text-center" style="margin-top: 1rem;">
                        <a href="onboarding_info.php"
                           style="color: #6b7280; font-size: 0.875rem; text-decoration: underline;">
                            온보딩 페이지로 이동
                        </a>
                    </div>
                </div>
            </div>

            <!-- Question Screen -->
            <div id="questionScreen" class="hidden">
                <div class="progress-container">
                    <div class="progress-info">
                        <span>진행률</span>
                        <span id="progressText">1 / 16</span>
                    </div>
                    <div class="progress-bar">
                        <div id="progressFill" class="progress-fill" style="width: 0%;"></div>
                    </div>
                </div>

                <div class="question-area">
                    <div id="questionText" class="question-text"></div>
                    <span id="questionCursor" class="typing-cursor hidden"></span>
                </div>

                <div id="optionsContainer" class="options-container hidden"></div>
            </div>

            <!-- Results Screen -->
            <div id="resultsScreen" class="hidden">
                <h2 class="text-center mb-8">학생 평가 결과 📊</h2>

                <div id="categoryResults" class="results-grid"></div>

                <div id="totalResult" class="summary-card"></div>

                <div id="analysisCards" class="area-cards"></div>

                <div class="btn-group">
                    <button id="printBtn" class="btn btn-secondary icon-print">
                        결과 출력
                    </button>
                    <button id="restartBtn" class="btn btn-primary icon-refresh">
                        다시 평가하기
                    </button>
                </div>

                <div style="margin-top: 1rem;">
                    <a href="https://claude.ai/public/artifacts/a93fb499-df35-48eb-a76c-367bf650559b?fullscreen=true"
                       target="_blank"
                       class="btn btn-success btn-full icon-user">
                        온보딩 시작하기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Questions data from PHP
        const questions = <?php echo json_encode($questions); ?>;

        // Pass userid from PHP to JavaScript
        const currentUserId = <?php echo json_encode($userid ?? 0); ?>;
        console.log('Current User ID:', currentUserId);

        // State management
        let currentQuestion = -1;
        let answers = {};
        let isTyping = false;
        let started = false;
        let isComplete = false;

        // DOM elements
        const welcomeScreen = document.getElementById('welcomeScreen');
        const questionScreen = document.getElementById('questionScreen');
        const resultsScreen = document.getElementById('resultsScreen');
        const welcomeText = document.getElementById('welcomeText');
        const typingCursor = document.getElementById('typingCursor');
        const startButtonContainer = document.getElementById('startButtonContainer');
        const questionText = document.getElementById('questionText');
        const questionCursor = document.getElementById('questionCursor');
        const optionsContainer = document.getElementById('optionsContainer');
        const progressText = document.getElementById('progressText');
        const progressFill = document.getElementById('progressFill');

        // Initialize
        window.addEventListener('DOMContentLoaded', function() {
            showWelcomeMessage();

            document.getElementById('startBtn').addEventListener('click', startAssessment);
            document.getElementById('printBtn').addEventListener('click', function() {
                window.print();
            });
            document.getElementById('restartBtn').addEventListener('click', restartAssessment);
        });

        // Typing animation function
        function typeText(element, text, callback) {
            let index = 0;
            isTyping = true;
            element.textContent = '';

            const cursor = element === welcomeText ? typingCursor : questionCursor;
            cursor.classList.remove('hidden');

            const timer = setInterval(function() {
                if (index <= text.length) {
                    element.textContent = text.slice(0, index);
                    index++;
                } else {
                    clearInterval(timer);
                    isTyping = false;
                    cursor.classList.add('hidden');
                    if (callback) {
                        setTimeout(callback, 300);
                    }
                }
            }, 30);
        }

        // Show welcome message
        function showWelcomeMessage() {
            const welcomeMessage = "안녕하세요,\n카이스트 터치수학에 오신 것을 환영합니다.\n평상시 수학공부 장면들을 떠올리며 다음 내용들에 답해주세요.\n몇 가지 질문을 통해 학습 스타일을 파악해보겠습니다.";

            typeText(welcomeText, welcomeMessage, function() {
                startButtonContainer.classList.remove('hidden');
                startButtonContainer.style.animation = 'fadeIn 0.5s ease-out';
            });
        }

        // Start assessment
        function startAssessment() {
            started = true;
            currentQuestion = 0;
            welcomeScreen.classList.add('hidden');
            questionScreen.classList.remove('hidden');
            showQuestion();
        }

        // Show question
        function showQuestion() {
            if (currentQuestion >= questions.length) {
                showResults();
                return;
            }

            const question = questions[currentQuestion];

            // Update progress
            progressText.textContent = `${currentQuestion + 1} / ${questions.length}`;
            progressFill.style.width = `${((currentQuestion + 1) / questions.length) * 100}%`;

            // Clear options
            optionsContainer.innerHTML = '';
            optionsContainer.classList.add('hidden');

            // Type question
            typeText(questionText, question.question, function() {
                showOptions();
            });
        }

        // Show options
        function showOptions() {
            const question = questions[currentQuestion];
            optionsContainer.classList.remove('hidden');

            question.options.forEach(function(option, index) {
                const btn = document.createElement('button');
                btn.className = 'option-btn';
                btn.textContent = option.label;
                btn.style.animationDelay = `${index * 0.1}s`;

                btn.addEventListener('click', function() {
                    handleAnswer(option.value);
                });

                optionsContainer.appendChild(btn);
            });
        }

        // Handle answer
        function handleAnswer(value) {
            const question = questions[currentQuestion];
            answers[question.id] = value;

            console.log(`Answer saved: ${question.id} = ${value}`);

            // Get the selected option's text
            let answerText = '';
            if (question.options && Array.isArray(question.options)) {
                const selectedOption = question.options.find(opt => opt.value === value);
                if (selectedOption) {
                    answerText = selectedOption.label;
                }
            }

            // Save answer via AJAX with complete Q&A data
            const formData = new URLSearchParams();
            formData.append('action', 'save_answer');
            formData.append('userid', currentUserId);
            formData.append('question_id', question.id);
            formData.append('value', value);
            formData.append('question_text', question.question);
            formData.append('answer_text', answerText);
            formData.append('question_number', currentQuestion + 1); // 1-based numbering

            fetch('onboarding_learningtype.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'success') {
                    console.error('Failed to save answer:', data);
                } else {
                    console.log('Answer saved successfully to session');
                    if (data.qa_saved) {
                        console.log('QA text also saved for question ' + (currentQuestion + 1));
                    }
                }
            })
            .catch(error => {
                console.error('AJAX error saving answer:', error);
            });

            // Move to next question
            currentQuestion++;

            if (currentQuestion < questions.length) {
                optionsContainer.classList.add('hidden');
                setTimeout(function() {
                    showQuestion();
                }, 300);
            } else {
                showResults();
            }
        }

        // Calculate results
        function calculateResults() {
            const categories = {
                '인지': [],
                '감정': [],
                '행동': []
            };

            questions.forEach(function(q) {
                if (answers[q.id]) {
                    categories[q.category].push(answers[q.id]);
                }
            });

            const results = {};
            for (let category in categories) {
                const values = categories[category];
                if (values.length > 0) {
                    results[category] = values.reduce((a, b) => a + b, 0) / values.length;
                } else {
                    results[category] = 0;
                }
            }

            // Calculate total
            const allValues = Object.values(answers);
            results['전체'] = allValues.length > 0 ?
                allValues.reduce((a, b) => a + b, 0) / allValues.length : 0;

            return results;
        }

        // Get level description
        function getLevel(score) {
            if (score >= 4.5) return { level: '매우 우수', className: 'level-excellent' };
            if (score >= 3.5) return { level: '양호', className: 'level-good' };
            if (score >= 2.5) return { level: '보통', className: 'level-average' };
            return { level: '개선 필요', className: 'level-needs-improvement' };
        }

        // Get detailed analysis
        function getDetailedAnalysis() {
            const weakAreas = [];
            const strongAreas = [];

            questions.forEach(function(q) {
                if (answers[q.id] <= 2) {
                    weakAreas.push(q.id);
                } else if (answers[q.id] >= 4) {
                    strongAreas.push(q.id);
                }
            });

            return { weakAreas, strongAreas };
        }

        // Get area description
        function getAreaDescription(areaId, isStrength) {
            const descriptions = {
                strength: {
                    'reading': '꼼꼼한 문제 독해',
                    'persistence': '높은 문제 집착력',
                    'questioning': '적극적인 질문 태도',
                    'timeManagement': '우수한 시간 관리',
                    'conceptUnderstanding': '깊이 있는 개념 이해',
                    'mathAnxiety': '수학에 대한 자신감',
                    'motivation': '내적 동기 충만',
                    'errorAnalysis': '체계적인 오답 분석',
                    'logicalThinking': '논리적 사고력',
                    'mathExpression': '명확한 풀이 표현',
                    'resilience': '높은 회복탄력성',
                    'stressManagement': '우수한 스트레스 관리',
                    'studyHabits': '규칙적인 학습 습관',
                    'concentration': '뛰어난 집중력',
                    'collaboration': '협동 학습 능력',
                    'selfDirected': '높은 메타인지'
                },
                weakness: {
                    'reading': '문제 읽기 습관 개선',
                    'persistence': '끈기와 인내심 향상',
                    'questioning': '질문 습관 형성',
                    'timeManagement': '체계적 시간 관리',
                    'errorAnalysis': '오답 분석 능력',
                    'mathAnxiety': '수학 불안감 해소',
                    'concentration': '집중력 향상 훈련',
                    'conceptUnderstanding': '개념 이해 심화',
                    'logicalThinking': '논리적 접근법 연습',
                    'mathExpression': '풀이 과정 작성 연습',
                    'resilience': '실패 극복 능력',
                    'motivation': '학습 동기 강화',
                    'stressManagement': '스트레스 대처법',
                    'studyHabits': '학습 루틴 확립',
                    'collaboration': '협력 학습 기술',
                    'selfDirected': '자기 평가 능력'
                }
            };

            const type = isStrength ? 'strength' : 'weakness';
            return descriptions[type][areaId] || '';
        }

        // Show results
        function showResults() {
            isComplete = true;
            questionScreen.classList.add('hidden');
            resultsScreen.classList.remove('hidden');

            const results = calculateResults();
            const { weakAreas, strongAreas } = getDetailedAnalysis();

            // Display category results
            const categoryResultsDiv = document.getElementById('categoryResults');
            categoryResultsDiv.innerHTML = '';

            ['인지', '감정', '행동'].forEach(function(category) {
                const score = results[category];
                const { level, className } = getLevel(score);
                const icon = category === '인지' ? '🧠' : (category === '감정' ? '❤️' : '⚡');

                const resultCard = document.createElement('div');
                resultCard.className = 'result-card';
                resultCard.innerHTML = `
                    <div class="result-header">
                        <span class="result-icon">${icon}</span>
                        <h3>${category}적 요소</h3>
                    </div>
                    <div class="result-score">${(score * 20).toFixed(0)}점</div>
                    <div class="result-level ${className}">${level}</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${(score / 5) * 100}%"></div>
                    </div>
                `;
                categoryResultsDiv.appendChild(resultCard);
            });

            // Display total result
            const totalResultDiv = document.getElementById('totalResult');
            const totalScore = results['전체'];
            const { level: totalLevel, className: totalClassName } = getLevel(totalScore);

            totalResultDiv.innerHTML = `
                <h3 style="margin-bottom: 1rem;">종합 평가</h3>
                <div class="result-score">${(totalScore * 20).toFixed(0)}점</div>
                <div class="result-level ${totalClassName}">${totalLevel}</div>
            `;

            // Display analysis cards
            const analysisCardsDiv = document.getElementById('analysisCards');
            analysisCardsDiv.innerHTML = '';

            // Strengths
            const strengthCard = document.createElement('div');
            strengthCard.className = 'strength-card';
            strengthCard.innerHTML = '<h4>🌟 강점 영역</h4><ul class="area-list">';

            if (strongAreas.length > 0) {
                strongAreas.slice(0, 3).forEach(function(area) {
                    const li = document.createElement('li');
                    li.textContent = '• ' + getAreaDescription(area, true);
                    strengthCard.querySelector('ul').appendChild(li);
                });
            } else {
                const li = document.createElement('li');
                li.textContent = '• 더 많은 연습이 필요합니다';
                strengthCard.querySelector('ul').appendChild(li);
            }

            analysisCardsDiv.appendChild(strengthCard);

            // Weaknesses
            const weaknessCard = document.createElement('div');
            weaknessCard.className = 'weakness-card';
            weaknessCard.innerHTML = '<h4>📚 개선 필요 영역</h4><ul class="area-list">';

            if (weakAreas.length > 0) {
                weakAreas.slice(0, 3).forEach(function(area) {
                    const li = document.createElement('li');
                    li.textContent = '• ' + getAreaDescription(area, false);
                    weaknessCard.querySelector('ul').appendChild(li);
                });
            } else {
                const li = document.createElement('li');
                li.textContent = '• 전반적으로 우수합니다';
                weaknessCard.querySelector('ul').appendChild(li);
            }

            analysisCardsDiv.appendChild(weaknessCard);

            // Debug: Log what we're sending
            console.log('Saving results to database...');
            console.log('Answers:', answers);
            console.log('Results:', results);
            console.log('Weak areas:', weakAreas);
            console.log('Strong areas:', strongAreas);

            // Save results
            fetch('onboarding_learningtype.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=save_results&userid=${currentUserId}&results=${JSON.stringify(results)}&answers=${JSON.stringify(answers)}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Save results response:', data);
                if (data.status === 'success') {
                    console.log('Assessment saved successfully with ID:', data.assessment_id);

                    // Show success message in UI
                    const successDiv = document.createElement('div');
                    successDiv.className = 'success-message';
                    successDiv.style.cssText = `
                        background: #d4edda;
                        color: #155724;
                        padding: 12px 20px;
                        border-radius: 8px;
                        margin: 20px 0;
                        border-left: 4px solid #28a745;
                        display: flex;
                        align-items: center;
                        font-weight: 500;
                    `;
                    successDiv.innerHTML = `
                        <span style="font-size: 24px; margin-right: 10px;">✅</span>
                        평가가 성공적으로 저장되었습니다! (ID: ${data.assessment_id})
                    `;

                    // Insert success message at top of results screen
                    const resultsTitle = resultsScreen.querySelector('h2');
                    if (resultsTitle && resultsTitle.nextSibling) {
                        resultsTitle.parentNode.insertBefore(successDiv, resultsTitle.nextSibling);
                    } else {
                        resultsScreen.insertBefore(successDiv, resultsScreen.firstChild.nextSibling);
                    }
                } else {
                    console.error('Failed to save assessment:', data.message);
                    alert('평가 결과 저장 중 오류가 발생했습니다. 자세한 내용은 콘솔을 확인하세요.');
                }
            })
            .catch(error => {
                console.error('AJAX error saving results:', error);
                alert('네트워크 오류가 발생했습니다. 다시 시도해주세요.');
            });
        }

        // Restart assessment
        function restartAssessment() {
            // Reset state
            currentQuestion = -1;
            answers = {};
            isComplete = false;
            started = false;

            // Reset server-side session
            fetch('onboarding_learningtype.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=reset_assessment&userid=${currentUserId}`
            });

            // Show welcome screen
            resultsScreen.classList.add('hidden');
            welcomeScreen.classList.remove('hidden');
            startButtonContainer.classList.add('hidden');
            showWelcomeMessage();
        }
    </script>
</body>
</html>