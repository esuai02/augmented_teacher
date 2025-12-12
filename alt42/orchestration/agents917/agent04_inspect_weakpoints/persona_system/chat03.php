<?php
/**
 * chat03.php - 문제풀이 진단 (Problem Solving Diagnostic)
 *
 * Agent04 페르소나 시스템 - rules03.yaml 기반
 * 10단계 문제풀이 과정의 인지관성 패턴 탐지
 *
 * @scenario problem_solving
 * @total_personas 60
 * @categories 8 (cognitive_overload, confidence_distortion, mistake_pattern,
 *               approach_error, learning_habit, time_pressure,
 *               verification_absence, emotional_block)
 * @phases 10 (reading, analysis, strategy, execution, calculation,
 *            answer_writing, verification, error_analysis, time_management, review)
 *
 * DB Tables:
 * - mdl_agent04_chat_data: 채팅 데이터 저장
 *   - id (INT): Primary key
 *   - userid (INT): 사용자 ID
 *   - sessionid (VARCHAR): 세션 ID
 *   - data_type (VARCHAR): 데이터 타입 (student_problem_solving, teacher_problem_solving)
 *   - data_content (TEXT): JSON 형식 데이터
 *   - timecreated (INT): 생성 시간
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';

// 현재 탭 확인
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'student';

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];

    if ($action === 'save_student_data') {
        // 학생 설문 데이터 저장
        $responses = isset($_POST['responses']) ? $_POST['responses'] : [];

        $data = new stdClass();
        $data->userid = $USER->id;
        $data->sessionid = session_id();
        $data->data_type = 'student_problem_solving';
        $data->data_content = json_encode([
            'responses' => $responses,
            'scenario' => 'problem_solving',
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
        $data->timecreated = time();

        try {
            $id = $DB->insert_record('mdl_agent04_chat_data', $data);
            echo json_encode(['success' => true, 'id' => $id, 'message' => '저장되었습니다.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'DB 저장 실패: ' . $e->getMessage() . ' (chat03.php:58)']);
        }
        exit;
    }

    if ($action === 'save_teacher_data') {
        // 교사 입력 데이터 저장
        $studentId = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $observations = isset($_POST['observations']) ? $_POST['observations'] : [];

        $data = new stdClass();
        $data->userid = $USER->id;
        $data->sessionid = session_id();
        $data->data_type = 'teacher_problem_solving';
        $data->data_content = json_encode([
            'target_student_id' => $studentId,
            'observations' => $observations,
            'scenario' => 'problem_solving',
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
        $data->timecreated = time();

        try {
            $id = $DB->insert_record('mdl_agent04_chat_data', $data);
            echo json_encode(['success' => true, 'id' => $id, 'message' => '저장되었습니다.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'DB 저장 실패: ' . $e->getMessage() . ' (chat03.php:81)']);
        }
        exit;
    }

    if ($action === 'get_system_data') {
        // 시스템 데이터 조회
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;

        try {
            $records = $DB->get_records_sql(
                "SELECT * FROM {mdl_agent04_chat_data}
                 WHERE data_type IN ('student_problem_solving', 'teacher_problem_solving')
                 ORDER BY timecreated DESC
                 LIMIT ?",
                [$limit]
            );
            echo json_encode(['success' => true, 'data' => array_values($records)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'DB 조회 실패: ' . $e->getMessage() . ' (chat03.php:99)']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => '알 수 없는 액션: ' . $action . ' (chat03.php:104)']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧮 문제풀이 진단 - Agent04</title>
    <style>
        :root {
            --primary: #ef4444;
            --primary-light: #fca5a5;
            --primary-dark: #b91c1c;
            --secondary: #fef2f2;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fef2f2 0%, #fff 100%);
            min-height: 100vh;
            color: var(--text);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            margin-bottom: 30px;
            padding: 30px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 16px;
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content {
            text-align: left;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        /* 네비게이션 드롭다운 */
        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown select {
            padding: 12px 40px 12px 16px;
            font-size: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 10px;
            background: rgba(255,255,255,0.15);
            color: white;
            cursor: pointer;
            appearance: none;
            min-width: 180px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-dropdown select:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
        }

        .nav-dropdown select option {
            background: white;
            color: var(--text);
            padding: 10px;
        }

        .nav-dropdown::after {
            content: '▼';
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            font-size: 10px;
            color: white;
        }

        /* 탭 스타일 */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: white;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid var(--border);
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            color: var(--text);
        }

        .tab-btn:hover {
            border-color: var(--primary-light);
            background: var(--secondary);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .tab-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f3f4f6;
        }

        /* 탭 컨텐츠 */
        .tab-content {
            display: none;
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .tab-content.active {
            display: block;
        }

        /* 아코디언 스타일 */
        .accordion-section {
            margin-bottom: 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .accordion-header {
            padding: 18px 20px;
            background: linear-gradient(135deg, var(--secondary) 0%, white 100%);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .accordion-header:hover {
            background: var(--secondary);
        }

        .accordion-header .phase-badge {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .accordion-header .toggle-icon {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
            color: var(--primary);
        }

        .accordion-section.open .toggle-icon {
            transform: rotate(180deg);
        }

        .accordion-content {
            display: none;
            padding: 20px;
            background: white;
            border-top: 1px solid var(--border);
        }

        .accordion-section.open .accordion-content {
            display: block;
        }

        /* 질문 스타일 */
        .question-item {
            margin-bottom: 20px;
            padding: 15px;
            background: #fafafa;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .question-item:last-child {
            margin-bottom: 0;
        }

        .question-label {
            display: block;
            font-weight: 500;
            margin-bottom: 12px;
            color: var(--text);
            line-height: 1.5;
        }

        .question-hint {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 10px;
            font-style: italic;
        }

        /* 라디오/체크박스 옵션 */
        .options-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .option-label {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            background: white;
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .option-label:hover {
            border-color: var(--primary-light);
            background: var(--secondary);
        }

        .option-label input {
            margin-right: 8px;
            accent-color: var(--primary);
        }

        .option-label input:checked + span {
            color: var(--primary);
            font-weight: 600;
        }

        /* Likert 스케일 */
        .likert-scale {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .likert-option {
            flex: 1;
            text-align: center;
        }

        .likert-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 8px;
            background: white;
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .likert-option label:hover {
            border-color: var(--primary-light);
            background: var(--secondary);
        }

        .likert-option input {
            margin-bottom: 6px;
            accent-color: var(--primary);
        }

        .likert-option input:checked + span {
            color: var(--primary);
            font-weight: 600;
        }

        .likert-option span {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        /* 버튼 스타일 */
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--secondary);
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        /* 시스템 데이터 테이블 */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background: var(--secondary);
            font-weight: 600;
            color: var(--primary-dark);
        }

        .data-table tr:hover {
            background: #fafafa;
        }

        /* 진행률 표시 */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-text {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 10px;
        }

        /* 알림 메시지 */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .tabs {
                flex-direction: column;
            }

            .likert-scale {
                flex-wrap: wrap;
            }

            .likert-option {
                flex: 0 0 calc(20% - 8px);
            }
        }

        /* 파일 전환 드랍업 메뉴 */
        .file-switcher {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }

        .file-switcher-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary, #4f46e5), var(--primary-dark, #3730a3));
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .file-switcher-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
        }

        .file-switcher-btn.active {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .file-switcher-menu {
            position: absolute;
            bottom: 70px;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            min-width: 180px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .file-switcher-menu.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .file-switcher-menu-header {
            padding: 12px 16px;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .file-switcher-menu-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            color: #4b5563;
            text-decoration: none;
            transition: background 0.2s;
            font-size: 14px;
        }

        .file-switcher-menu-item:hover {
            background: #f3f4f6;
        }

        .file-switcher-menu-item.current {
            background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(79,70,229,0.05));
            color: var(--primary, #4f46e5);
            font-weight: 600;
        }

        .file-switcher-menu-item .num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .file-switcher-menu-item.current .num {
            background: var(--primary, #4f46e5);
            color: white;
        }

        .file-switcher-menu-item:last-child {
            border-radius: 0 0 12px 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>🧮 문제풀이 진단</h1>
                <p>문제풀이 과정에서 나타나는 인지관성 패턴을 진단합니다</p>
                <p style="font-size: 0.85rem; margin-top: 8px; opacity: 0.8;">10단계 풀이 과정 × 60가지 페르소나 분석</p>
            </div>
            <div class="nav-dropdown">
                <select id="pageNav" onchange="navigateToPage(this.value)">
                    <option value="">📑 페이지 이동</option>
                    <option value="chat03.php" selected>📘 문제풀이 분석</option>
                    <option value="chat04.php">📙 오답노트 분석</option>
                    <option value="chat05.php">📗 질의응답 분석</option>
                    <option value="chat06.php">📕 복습활동 분석</option>
                    <option value="chat_rules.php">📚 통합 규칙 뷰어</option>
                </select>
            </div>
        </div>

        <!-- 탭 네비게이션 -->
        <div class="tabs">
            <a href="?tab=student" class="tab-btn <?php echo $tab === 'student' ? 'active' : ''; ?>">
                📝 학생 대화
            </a>
            <a href="?tab=teacher" class="tab-btn <?php echo $tab === 'teacher' ? 'active' : ''; ?> <?php echo $role === 'student' ? 'disabled' : ''; ?>"
               <?php echo $role === 'student' ? 'onclick="return false;"' : ''; ?>>
                👨‍🏫 선생님 입력
            </a>
            <a href="?tab=system" class="tab-btn <?php echo $tab === 'system' ? 'active' : ''; ?> <?php echo $role === 'student' ? 'disabled' : ''; ?>"
               <?php echo $role === 'student' ? 'onclick="return false;"' : ''; ?>>
                🔧 시스템 데이터
            </a>
        </div>

        <!-- 학생 대화 탭 -->
        <div class="tab-content <?php echo $tab === 'student' ? 'active' : ''; ?>" id="student-tab">
            <div class="progress-text">진행률: <span id="progress-percent">0</span>%</div>
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
            </div>

            <form id="student-form">
                <!-- S1: 문제 읽기 단계 -->
                <div class="accordion-section open">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>📖 S1. 문제 읽기 단계</span>
                        <span class="phase-badge">Reading Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q1: idea_auto_fire -->
                        <div class="question-item">
                            <label class="question-label">Q1. 문제를 읽자마자 아이디어가 떠오르면 바로 풀이를 시작하나요?</label>
                            <div class="question-hint">💡 패턴: 아이디어 자동발화 (idea_auto_fire)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_idea_auto_fire" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_idea_auto_fire" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_idea_auto_fire" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_idea_auto_fire" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_idea_auto_fire" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q2: defeat_premonition -->
                        <div class="question-item">
                            <label class="question-label">Q2. 문제를 보자마자 "못 풀 것 같다"는 느낌이 들면 쉽게 포기하나요?</label>
                            <div class="question-hint">💡 패턴: 3초 패배 예감 (defeat_premonition)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_defeat_premonition" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_defeat_premonition" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_defeat_premonition" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_defeat_premonition" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_defeat_premonition" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q3: working_memory_split -->
                        <div class="question-item">
                            <label class="question-label">Q3. 문제를 풀 때 다른 일정이나 잡생각이 자주 떠오르나요?</label>
                            <div class="question-hint">💡 패턴: 작업기억 분할 (working_memory_split)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_working_memory_split" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_working_memory_split" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_working_memory_split" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_working_memory_split" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s1_working_memory_split" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- S2: 문제 분석 단계 -->
                <div class="accordion-section">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>🔍 S2. 문제 분석 단계</span>
                        <span class="phase-badge">Analysis Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q4: overconfidence_tunnel -->
                        <div class="question-item">
                            <label class="question-label">Q4. 자신감이 높을 때 숫자나 기호의 작은 차이를 놓치는 경향이 있나요?</label>
                            <div class="question-hint">💡 패턴: 과신-시야 협착 (overconfidence_tunnel)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_overconfidence_tunnel" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_overconfidence_tunnel" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_overconfidence_tunnel" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_overconfidence_tunnel" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_overconfidence_tunnel" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q5: contradiction_fixation -->
                        <div class="question-item">
                            <label class="question-label">Q5. 풀이가 막히면 "내가 틀릴 리 없다"며 같은 방법만 계속 시도하나요?</label>
                            <div class="question-hint">💡 패턴: 모순 확신-답불가 (contradiction_fixation)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_contradiction_fixation" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_contradiction_fixation" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_contradiction_fixation" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_contradiction_fixation" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_contradiction_fixation" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q6: information_overload -->
                        <div class="question-item">
                            <label class="question-label">Q6. 문제에 정보가 많으면 어디서부터 시작해야 할지 혼란스러운가요?</label>
                            <div class="question-hint">💡 패턴: 정보 과부하 (information_overload)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_information_overload" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_information_overload" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_information_overload" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_information_overload" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s2_information_overload" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- S3: 풀이 전략 수립 단계 -->
                <div class="accordion-section">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>🎯 S3. 풀이 전략 수립 단계</span>
                        <span class="phase-badge">Strategy Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q7: half_surrender_creativity -->
                        <div class="question-item">
                            <label class="question-label">Q7. "어차피 틀릴 것"이라며 정석보다 창의적(?) 풀이만 시도하나요?</label>
                            <div class="question-hint">💡 패턴: 반포기 창의 탐색 (half_surrender_creativity)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_half_surrender_creativity" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_half_surrender_creativity" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_half_surrender_creativity" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_half_surrender_creativity" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_half_surrender_creativity" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q8: uncertain_forced -->
                        <div class="question-item">
                            <label class="question-label">Q8. 확실하지 않은데도 "일단 적용해보자"며 진행하다 오류가 연쇄되나요?</label>
                            <div class="question-hint">💡 패턴: 불확실 강행 (uncertain_forced)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_uncertain_forced" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_uncertain_forced" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_uncertain_forced" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_uncertain_forced" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_uncertain_forced" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q9: formula_blind_application -->
                        <div class="question-item">
                            <label class="question-label">Q9. 문제 상황을 파악하지 않고 익숙한 공식부터 적용하려 하나요?</label>
                            <div class="question-hint">💡 패턴: 공식 맹목 적용 (formula_blind_application)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_formula_blind_application" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_formula_blind_application" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_formula_blind_application" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_formula_blind_application" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s3_formula_blind_application" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- S4: 풀이 실행 단계 -->
                <div class="accordion-section">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>✏️ S4. 풀이 실행 단계</span>
                        <span class="phase-badge">Execution Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q10: unconscious_chain_mistake -->
                        <div class="question-item">
                            <label class="question-label">Q10. 손이 빠르게 움직이다가 사소한 계산 실수가 꼬리를 무나요?</label>
                            <div class="question-hint">💡 패턴: 무의식 연쇄 실수 (unconscious_chain_mistake)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_unconscious_chain_mistake" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_unconscious_chain_mistake" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_unconscious_chain_mistake" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_unconscious_chain_mistake" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_unconscious_chain_mistake" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q11: explanation_mix -->
                        <div class="question-item">
                            <label class="question-label">Q11. 해설을 보면서 풀 때 내 생각과 해설 내용이 뒤섞이나요?</label>
                            <div class="question-hint">💡 패턴: 해설지 혼합 착각 (explanation_mix)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_explanation_mix" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_explanation_mix" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_explanation_mix" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_explanation_mix" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_explanation_mix" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q12: practice_avoidance -->
                        <div class="question-item">
                            <label class="question-label">Q12. "이해했어"라고 생각하면 반복 연습을 건너뛰는 편인가요?</label>
                            <div class="question-hint">💡 패턴: 연습 회피 관성 (practice_avoidance)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_practice_avoidance" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_practice_avoidance" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_practice_avoidance" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_practice_avoidance" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s4_practice_avoidance" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- S5: 계산 과정 단계 -->
                <div class="accordion-section">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>🔢 S5. 계산 과정 단계</span>
                        <span class="phase-badge">Calculation Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q13: speed_pressure_block -->
                        <div class="question-item">
                            <label class="question-label">Q13. 시험 시간이 눈에 들어올 때마다 압박감에 머리가 멈추나요?</label>
                            <div class="question-hint">💡 패턴: 속도 압박 억제 (speed_pressure_block)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_speed_pressure_block" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_speed_pressure_block" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_speed_pressure_block" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_speed_pressure_block" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_speed_pressure_block" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q14: unit_conversion_skip -->
                        <div class="question-item">
                            <label class="question-label">Q14. 단위 환산을 건너뛰거나 잘못 적용하는 경우가 있나요?</label>
                            <div class="question-hint">💡 패턴: 단위 환산 생략 (unit_conversion_skip)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_unit_conversion_skip" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_unit_conversion_skip" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_unit_conversion_skip" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_unit_conversion_skip" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_unit_conversion_skip" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q15: sign_error_chain -->
                        <div class="question-item">
                            <label class="question-label">Q15. +/- 부호 실수가 연쇄적으로 발생하는 경험이 있나요?</label>
                            <div class="question-hint">💡 패턴: 부호 실수 연쇄 (sign_error_chain)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_sign_error_chain" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_sign_error_chain" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_sign_error_chain" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_sign_error_chain" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s5_sign_error_chain" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- S6: 답안 작성 단계 -->
                <div class="accordion-section">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>📝 S6. 답안 작성 단계</span>
                        <span class="phase-badge">Answer Writing Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q16: condition_neglect -->
                        <div class="question-item">
                            <label class="question-label">Q16. 문제의 조건이나 제약사항을 무시하고 답을 쓰는 경우가 있나요?</label>
                            <div class="question-hint">💡 패턴: 조건 확인 생략 (condition_neglect)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_condition_neglect" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_condition_neglect" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_condition_neglect" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_condition_neglect" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_condition_neglect" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q17: answer_format_error -->
                        <div class="question-item">
                            <label class="question-label">Q17. 요구하는 형식(분수, 소수, 단위 등)과 다르게 답을 쓰나요?</label>
                            <div class="question-hint">💡 패턴: 답안 형식 오류 (answer_format_error)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_answer_format_error" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_answer_format_error" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_answer_format_error" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_answer_format_error" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_answer_format_error" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q18: extreme_self_doubt -->
                        <div class="question-item">
                            <label class="question-label">Q18. 맞는 답도 의심하며 수정하다가 결국 틀리는 경험이 있나요?</label>
                            <div class="question-hint">💡 패턴: 극단적 자기 의심 (extreme_self_doubt)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_extreme_self_doubt" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_extreme_self_doubt" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_extreme_self_doubt" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_extreme_self_doubt" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s6_extreme_self_doubt" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- S7: 검산/검증 단계 -->
                <div class="accordion-section">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>✅ S7. 검산/검증 단계</span>
                        <span class="phase-badge">Verification Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q19: verification_skip -->
                        <div class="question-item">
                            <label class="question-label">Q19. "시간이 없어서" 검산 과정을 건너뛰는 경우가 많나요?</label>
                            <div class="question-hint">💡 패턴: 검산 생략 (verification_skip)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_verification_skip" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_verification_skip" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_verification_skip" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_verification_skip" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_verification_skip" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q20: partial_verification -->
                        <div class="question-item">
                            <label class="question-label">Q20. 일부만 검산하고 "다 확인했다"고 생각하나요?</label>
                            <div class="question-hint">💡 패턴: 부분 검산 (partial_verification)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_partial_verification" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_partial_verification" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_partial_verification" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_partial_verification" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_partial_verification" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q21: math_anxiety (검산 시 불안) -->
                        <div class="question-item">
                            <label class="question-label">Q21. 검산할 때 불안해서 오히려 더 실수하게 되나요?</label>
                            <div class="question-hint">💡 패턴: 수학 불안 (math_anxiety)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_math_anxiety" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_math_anxiety" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_math_anxiety" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_math_anxiety" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s7_math_anxiety" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- S8: 오답 분석 단계 -->
                <div class="accordion-section">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>🔬 S8. 오답 분석 단계</span>
                        <span class="phase-badge">Error Analysis Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q22: superficial_error_analysis -->
                        <div class="question-item">
                            <label class="question-label">Q22. 틀린 문제를 "실수했다"로만 정리하고 넘어가나요?</label>
                            <div class="question-hint">💡 패턴: 피상적 오답 분석 (superficial_error_analysis)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_superficial_error_analysis" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_superficial_error_analysis" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_superficial_error_analysis" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_superficial_error_analysis" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_superficial_error_analysis" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q23: same_mistake_repeat -->
                        <div class="question-item">
                            <label class="question-label">Q23. 같은 유형의 실수를 반복하면서 인지하지 못하나요?</label>
                            <div class="question-hint">💡 패턴: 동일 실수 반복 (same_mistake_repeat)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_same_mistake_repeat" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_same_mistake_repeat" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_same_mistake_repeat" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_same_mistake_repeat" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_same_mistake_repeat" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q24: frustration_cycle -->
                        <div class="question-item">
                            <label class="question-label">Q24. 틀리면 좌절하고, 좌절하면 더 틀리는 악순환을 경험하나요?</label>
                            <div class="question-hint">💡 패턴: 좌절 순환 (frustration_cycle)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_frustration_cycle" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_frustration_cycle" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_frustration_cycle" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_frustration_cycle" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s8_frustration_cycle" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- S9: 시간 관리 단계 -->
                <div class="accordion-section">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>⏱️ S9. 시간 관리 단계</span>
                        <span class="phase-badge">Time Management Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q25: time_blindness -->
                        <div class="question-item">
                            <label class="question-label">Q25. 한 문제에 과도하게 시간을 쓰다가 나중에 시간이 부족해지나요?</label>
                            <div class="question-hint">💡 패턴: 시간 맹목 (time_blindness)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_time_blindness" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_time_blindness" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_time_blindness" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_time_blindness" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_time_blindness" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q26: perfectionism_trap -->
                        <div class="question-item">
                            <label class="question-label">Q26. 모든 문제를 완벽하게 풀려다 시간을 낭비하나요?</label>
                            <div class="question-hint">💡 패턴: 완벽주의 함정 (perfectionism_trap)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_perfectionism_trap" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_perfectionism_trap" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_perfectionism_trap" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_perfectionism_trap" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_perfectionism_trap" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q27: skip_strategy_absence -->
                        <div class="question-item">
                            <label class="question-label">Q27. 어려운 문제를 건너뛰고 나중에 돌아오는 전략을 사용하지 않나요?</label>
                            <div class="question-hint">💡 패턴: 건너뛰기 전략 부재</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_skip_strategy_absence" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_skip_strategy_absence" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_skip_strategy_absence" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_skip_strategy_absence" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s9_skip_strategy_absence" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- S10: 복습/정리 단계 -->
                <div class="accordion-section">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>📚 S10. 복습/정리 단계</span>
                        <span class="phase-badge">Review Phase</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="accordion-content">
                        <!-- Q28: shallow_review -->
                        <div class="question-item">
                            <label class="question-label">Q28. 답만 확인하고 풀이 과정은 복습하지 않나요?</label>
                            <div class="question-hint">💡 패턴: 피상적 복습 (shallow_review)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_shallow_review" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_shallow_review" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_shallow_review" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_shallow_review" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_shallow_review" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q29: no_consolidation -->
                        <div class="question-item">
                            <label class="question-label">Q29. 배운 내용을 정리하지 않고 다음으로 넘어가나요?</label>
                            <div class="question-hint">💡 패턴: 정리 부재 (no_consolidation)</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_no_consolidation" value="1"><span>전혀 아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_no_consolidation" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_no_consolidation" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_no_consolidation" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_no_consolidation" value="5"><span>매우 그렇다</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- Q30: success_routine -->
                        <div class="question-item">
                            <label class="question-label">Q30. 성공했을 때의 학습 루틴을 기록하고 반복하나요?</label>
                            <div class="question-hint">💡 패턴: 성공 루틴 모델링 (success_routine) - 긍정 질문</div>
                            <div class="likert-scale">
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_success_routine" value="5"><span>매우 그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_success_routine" value="4"><span>그렇다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_success_routine" value="3"><span>보통</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_success_routine" value="2"><span>아니다</span></label>
                                </div>
                                <div class="likert-option">
                                    <label><input type="radio" name="s10_success_routine" value="1"><span>전혀 아니다</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">초기화</button>
                    <button type="submit" class="btn btn-primary">📊 진단 결과 저장</button>
                </div>
            </form>
        </div>

        <!-- 선생님 입력 탭 -->
        <div class="tab-content <?php echo $tab === 'teacher' ? 'active' : ''; ?>" id="teacher-tab">
            <?php if ($role === 'student'): ?>
                <div class="alert alert-info">
                    ℹ️ 이 탭은 선생님 전용입니다.
                </div>
            <?php else: ?>
                <form id="teacher-form">
                    <div class="question-item">
                        <label class="question-label">관찰 대상 학생 ID</label>
                        <input type="number" name="student_id" style="width: 100%; padding: 12px; border: 2px solid var(--border); border-radius: 8px; font-size: 1rem;" placeholder="학생 ID를 입력하세요">
                    </div>

                    <!-- 교사 관찰 영역: 10개 풀이 단계 -->
                    <div class="accordion-section open">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <span>👁️ 문제풀이 과정 관찰</span>
                            <span class="toggle-icon">▼</span>
                        </div>
                        <div class="accordion-content">
                            <!-- 읽기 단계 관찰 -->
                            <div class="question-item">
                                <label class="question-label">📖 읽기 단계 관찰 (idea_auto_fire, defeat_premonition, working_memory_split)</label>
                                <div class="options-group">
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_reading[]" value="idea_auto_fire">
                                        <span>성급한 풀이 시작</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_reading[]" value="defeat_premonition">
                                        <span>조기 포기 경향</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_reading[]" value="working_memory_split">
                                        <span>주의력 분산</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 분석 단계 관찰 -->
                            <div class="question-item">
                                <label class="question-label">🔍 분석 단계 관찰 (overconfidence_tunnel, contradiction_fixation)</label>
                                <div class="options-group">
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_analysis[]" value="overconfidence_tunnel">
                                        <span>세부사항 간과</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_analysis[]" value="contradiction_fixation">
                                        <span>시야 고착</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_analysis[]" value="information_overload">
                                        <span>정보 과부하</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 전략 단계 관찰 -->
                            <div class="question-item">
                                <label class="question-label">🎯 전략 단계 관찰 (half_surrender_creativity, uncertain_forced)</label>
                                <div class="options-group">
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_strategy[]" value="half_surrender_creativity">
                                        <span>비정석 접근 고집</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_strategy[]" value="uncertain_forced">
                                        <span>불확실 강행</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_strategy[]" value="formula_blind_application">
                                        <span>공식 맹목 적용</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 실행 단계 관찰 -->
                            <div class="question-item">
                                <label class="question-label">✏️ 실행 단계 관찰 (unconscious_chain_mistake, explanation_mix)</label>
                                <div class="options-group">
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_execution[]" value="unconscious_chain_mistake">
                                        <span>연쇄 실수</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_execution[]" value="explanation_mix">
                                        <span>해설 혼합</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_execution[]" value="practice_avoidance">
                                        <span>연습 회피</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 계산 단계 관찰 -->
                            <div class="question-item">
                                <label class="question-label">🔢 계산 단계 관찰 (speed_pressure_block, unit_conversion_skip, sign_error_chain)</label>
                                <div class="options-group">
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_calculation[]" value="speed_pressure_block">
                                        <span>속도 압박 블록</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_calculation[]" value="unit_conversion_skip">
                                        <span>단위 환산 생략</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_calculation[]" value="sign_error_chain">
                                        <span>부호 실수 연쇄</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 검산 단계 관찰 -->
                            <div class="question-item">
                                <label class="question-label">✅ 검산 단계 관찰 (verification_skip, partial_verification)</label>
                                <div class="options-group">
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_verification[]" value="verification_skip">
                                        <span>검산 생략</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_verification[]" value="partial_verification">
                                        <span>부분 검산</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_verification[]" value="math_anxiety">
                                        <span>수학 불안</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 시간 관리 관찰 -->
                            <div class="question-item">
                                <label class="question-label">⏱️ 시간 관리 관찰 (time_blindness, perfectionism_trap)</label>
                                <div class="options-group">
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_time[]" value="time_blindness">
                                        <span>시간 맹목</span>
                                    </label>
                                    <label class="option-label">
                                        <input type="checkbox" name="obs_time[]" value="perfectionism_trap">
                                        <span>완벽주의 함정</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 교사 메모 -->
                            <div class="question-item">
                                <label class="question-label">📝 추가 관찰 메모</label>
                                <textarea name="teacher_memo" style="width: 100%; min-height: 120px; padding: 12px; border: 2px solid var(--border); border-radius: 8px; font-size: 1rem; resize: vertical;" placeholder="학생의 문제풀이 과정에서 관찰된 특이사항이나 패턴을 기록하세요..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 관찰 기록 저장</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- 시스템 데이터 탭 -->
        <div class="tab-content <?php echo $tab === 'system' ? 'active' : ''; ?>" id="system-tab">
            <?php if ($role === 'student'): ?>
                <div class="alert alert-info">
                    ℹ️ 이 탭은 선생님 전용입니다.
                </div>
            <?php else: ?>
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="loadSystemData()">🔄 데이터 새로고침</button>
                </div>
                <div id="system-data-container">
                    <p style="color: var(--text-light); text-align: center; padding: 40px;">
                        '데이터 새로고침' 버튼을 클릭하여 저장된 데이터를 확인하세요.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // 페이지 네비게이션
        function navigateToPage(page) {
            if (page) {
                window.location.href = page;
            }
        }

        // 아코디언 토글
        function toggleAccordion(header) {
            const section = header.parentElement;
            section.classList.toggle('open');
        }

        // 진행률 업데이트
        function updateProgress() {
            const form = document.getElementById('student-form');
            if (!form) return;

            const totalQuestions = 30;
            const answeredQuestions = form.querySelectorAll('input[type="radio"]:checked').length;
            const percent = Math.round((answeredQuestions / totalQuestions) * 100);

            document.getElementById('progress-percent').textContent = percent;
            document.getElementById('progress-fill').style.width = percent + '%';
        }

        // 라디오 버튼 변경 시 진행률 업데이트
        document.querySelectorAll('#student-form input[type="radio"]').forEach(input => {
            input.addEventListener('change', updateProgress);
        });

        // 폼 초기화
        function resetForm() {
            if (confirm('모든 응답을 초기화하시겠습니까?')) {
                document.getElementById('student-form').reset();
                updateProgress();
            }
        }

        // 학생 폼 제출
        document.getElementById('student-form')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const responses = {};

            // 모든 라디오 버튼 값 수집
            this.querySelectorAll('input[type="radio"]:checked').forEach(input => {
                responses[input.name] = parseInt(input.value);
            });

            // 응답 개수 확인
            const answeredCount = Object.keys(responses).length;
            if (answeredCount < 30) {
                alert(`모든 질문에 답해주세요. (${answeredCount}/30 완료)`);
                return;
            }

            // AJAX 저장
            const postData = new FormData();
            postData.append('action', 'save_student_data');
            postData.append('responses', JSON.stringify(responses));

            fetch(window.location.href, {
                method: 'POST',
                body: postData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ 진단 결과가 저장되었습니다!');
                } else {
                    alert('❌ 저장 실패: ' + data.error);
                }
            })
            .catch(error => {
                alert('❌ 오류 발생: ' + error.message);
            });
        });

        // 교사 폼 제출
        document.getElementById('teacher-form')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const studentId = formData.get('student_id');

            if (!studentId) {
                alert('학생 ID를 입력해주세요.');
                return;
            }

            // 관찰 데이터 수집
            const observations = {
                reading: Array.from(this.querySelectorAll('input[name="obs_reading[]"]:checked')).map(el => el.value),
                analysis: Array.from(this.querySelectorAll('input[name="obs_analysis[]"]:checked')).map(el => el.value),
                strategy: Array.from(this.querySelectorAll('input[name="obs_strategy[]"]:checked')).map(el => el.value),
                execution: Array.from(this.querySelectorAll('input[name="obs_execution[]"]:checked')).map(el => el.value),
                calculation: Array.from(this.querySelectorAll('input[name="obs_calculation[]"]:checked')).map(el => el.value),
                verification: Array.from(this.querySelectorAll('input[name="obs_verification[]"]:checked')).map(el => el.value),
                time: Array.from(this.querySelectorAll('input[name="obs_time[]"]:checked')).map(el => el.value),
                memo: formData.get('teacher_memo')
            };

            const postData = new FormData();
            postData.append('action', 'save_teacher_data');
            postData.append('student_id', studentId);
            postData.append('observations', JSON.stringify(observations));

            fetch(window.location.href, {
                method: 'POST',
                body: postData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ 관찰 기록이 저장되었습니다!');
                    this.reset();
                } else {
                    alert('❌ 저장 실패: ' + data.error);
                }
            })
            .catch(error => {
                alert('❌ 오류 발생: ' + error.message);
            });
        });

        // 시스템 데이터 로드
        function loadSystemData() {
            const container = document.getElementById('system-data-container');
            container.innerHTML = '<p style="text-align: center; padding: 20px;">⏳ 데이터 로딩 중...</p>';

            const postData = new FormData();
            postData.append('action', 'get_system_data');
            postData.append('limit', '50');

            fetch(window.location.href, {
                method: 'POST',
                body: postData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.length === 0) {
                        container.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text-light);">저장된 데이터가 없습니다.</p>';
                        return;
                    }

                    let html = '<table class="data-table"><thead><tr>';
                    html += '<th>ID</th><th>사용자</th><th>타입</th><th>생성일시</th><th>데이터</th>';
                    html += '</tr></thead><tbody>';

                    data.data.forEach(record => {
                        const date = new Date(record.timecreated * 1000).toLocaleString('ko-KR');
                        const content = JSON.parse(record.data_content);
                        const summary = record.data_type === 'student_problem_solving'
                            ? `응답 ${Object.keys(content.responses || {}).length}개`
                            : `관찰 대상: ${content.target_student_id || 'N/A'}`;

                        html += `<tr>
                            <td>${record.id}</td>
                            <td>${record.userid}</td>
                            <td><span style="background: ${record.data_type.includes('student') ? 'var(--primary-light)' : '#dbeafe'}; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">${record.data_type.includes('student') ? '학생' : '교사'}</span></td>
                            <td>${date}</td>
                            <td>${summary}</td>
                        </tr>`;
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="text-align: center; padding: 40px; color: #ef4444;">❌ 데이터 로드 실패: ' + data.error + '</p>';
                }
            })
            .catch(error => {
                container.innerHTML = '<p style="text-align: center; padding: 40px; color: #ef4444;">❌ 오류 발생: ' + error.message + '</p>';
            });
        }

        // 초기 진행률 표시
        updateProgress();
    </script>
</body>
</html>
