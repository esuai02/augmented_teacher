<?php
/**
 * chat05.php - 질의응답(QA Session) 설문 인터페이스
 *
 * rules05.yaml 기반 30개 pattern_hint 연결
 * 시나리오: qa_session
 * 10개 sub_items: qa_efficacy, doubt_occurrence, question_generation, focused_resolution,
 *                 question_decision, self_directed_qa, intervention_method, closing,
 *                 tracking_followup, close_session
 *
 * @version 1.0
 * @date 2025-12-04
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        if ($_POST['action'] === 'save_student_response') {
            $responses = isset($_POST['responses']) ? $_POST['responses'] : [];

            $data = new stdClass();
            $data->userid = $USER->id;
            $data->nagent = 4;
            $data->data_type = 'student_qa_session';
            $data->data_content = json_encode([
                'responses' => $responses,
                'scenario' => 'qa_session',
                'timestamp' => time()
            ], JSON_UNESCAPED_UNICODE);
            $data->timecreated = time();
            $data->timemodified = time();

            // 기존 데이터 확인
            $existing = $DB->get_record_sql(
                "SELECT id FROM mdl_agent04_chat_data WHERE userid = ? AND nagent = 4 AND data_type = 'student_qa_session' ORDER BY id DESC LIMIT 1",
                [$USER->id]
            );

            if ($existing) {
                $data->id = $existing->id;
                $data->timemodified = time();
                $DB->update_record('mdl_agent04_chat_data', $data);
                echo json_encode(['success' => true, 'message' => '응답이 업데이트되었습니다.', 'id' => $existing->id]);
            } else {
                $newid = $DB->insert_record('mdl_agent04_chat_data', $data);
                echo json_encode(['success' => true, 'message' => '응답이 저장되었습니다.', 'id' => $newid]);
            }
            exit;
        }

        if ($_POST['action'] === 'save_teacher_observation') {
            $observations = isset($_POST['observations']) ? $_POST['observations'] : [];
            $target_userid = isset($_POST['target_userid']) ? intval($_POST['target_userid']) : 0;

            $data = new stdClass();
            $data->userid = $USER->id;
            $data->nagent = 4;
            $data->data_type = 'teacher_qa_session';
            $data->data_content = json_encode([
                'observations' => $observations,
                'target_userid' => $target_userid,
                'scenario' => 'qa_session',
                'timestamp' => time()
            ], JSON_UNESCAPED_UNICODE);
            $data->timecreated = time();
            $data->timemodified = time();

            $newid = $DB->insert_record('mdl_agent04_chat_data', $data);
            echo json_encode(['success' => true, 'message' => '관찰 기록이 저장되었습니다.', 'id' => $newid]);
            exit;
        }

        if ($_POST['action'] === 'load_data') {
            $data_type = isset($_POST['data_type']) ? $_POST['data_type'] : 'student_qa_session';

            $records = $DB->get_records_sql(
                "SELECT * FROM mdl_agent04_chat_data WHERE nagent = 4 AND data_type = ? ORDER BY timecreated DESC LIMIT 100",
                [$data_type]
            );

            $result = [];
            foreach ($records as $record) {
                $result[] = [
                    'id' => $record->id,
                    'userid' => $record->userid,
                    'data_content' => json_decode($record->data_content, true),
                    'timecreated' => date('Y-m-d H:i:s', $record->timecreated)
                ];
            }

            echo json_encode(['success' => true, 'data' => $result]);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '오류: ' . $e->getMessage() . ' (chat05.php:' . $e->getLine() . ')']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>질의응답 패턴 분석 (QA Session)</title>
    <style>
        :root {
            --primary: #06b6d4;
            --primary-dark: #0891b2;
            --primary-light: #67e8f9;
            --bg-dark: #1a1a2e;
            --bg-card: #16213e;
            --bg-input: #0f3460;
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --border-color: #2d3748;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, var(--bg-card), var(--bg-input));
            border-radius: 15px;
            border: 1px solid var(--primary);
            position: relative;
        }

        .header h1 {
            color: var(--primary-light);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--text-secondary);
        }

        .nav-dropdown {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .nav-dropdown select {
            padding: 8px 15px;
            background: var(--bg-input);
            color: var(--text-primary);
            border: 1px solid var(--primary);
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .nav-dropdown select:hover {
            background: var(--primary-dark);
        }

        .role-badge {
            display: inline-block;
            padding: 5px 15px;
            background: var(--primary);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 24px;
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            color: var(--text-secondary);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .tab-btn:hover {
            border-color: var(--primary);
            color: var(--primary-light);
        }

        .tab-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .card-title {
            color: var(--primary-light);
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
        }

        .accordion {
            margin-bottom: 15px;
        }

        .accordion-header {
            background: var(--bg-input);
            padding: 15px 20px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .accordion-header:hover {
            border-color: var(--primary);
        }

        .accordion-header.active {
            background: var(--primary-dark);
            border-color: var(--primary);
            border-radius: 10px 10px 0 0;
        }

        .accordion-header h3 {
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .accordion-icon {
            font-size: 1.5rem;
            transition: transform 0.3s ease;
        }

        .accordion-header.active .accordion-icon {
            transform: rotate(45deg);
        }

        .accordion-content {
            display: none;
            background: var(--bg-input);
            padding: 20px;
            border-radius: 0 0 10px 10px;
            border: 1px solid var(--border-color);
            border-top: none;
        }

        .accordion-content.active {
            display: block;
        }

        .question-item {
            background: rgba(6, 182, 212, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary);
        }

        .question-item:last-child {
            margin-bottom: 0;
        }

        .question-text {
            font-size: 1rem;
            color: var(--text-primary);
            margin-bottom: 15px;
        }

        .likert-scale {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .likert-option {
            flex: 1;
            min-width: 80px;
        }

        .likert-option input {
            display: none;
        }

        .likert-option label {
            display: block;
            padding: 10px;
            text-align: center;
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .likert-option input:checked + label {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .likert-option label:hover {
            border-color: var(--primary);
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: var(--bg-input);
            border-radius: 5px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 5px;
            transition: width 0.5s ease;
        }

        .progress-text {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: var(--bg-card);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkbox-item:hover {
            background: rgba(6, 182, 212, 0.2);
        }

        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--bg-input);
            color: var(--primary-light);
            font-weight: 600;
        }

        .data-table tr:hover {
            background: rgba(6, 182, 212, 0.1);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        .sub-item-badge {
            display: inline-block;
            padding: 3px 10px;
            background: var(--primary);
            color: white;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .teacher-section {
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(6, 182, 212, 0.05);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .teacher-section h4 {
            color: var(--primary-light);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .tabs {
                flex-direction: column;
            }

            .tab-btn {
                width: 100%;
                text-align: center;
            }

            .likert-scale {
                flex-direction: column;
            }

            .likert-option {
                min-width: 100%;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
            }

            .nav-dropdown {
                position: static;
                margin-top: 15px;
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
            <div class="nav-dropdown">
                <select id="pageNav" onchange="navigateToPage(this.value)">
                    <option value="">📑 페이지 이동</option>
                    <option value="chat03.php">📘 문제풀이 분석</option>
                    <option value="chat04.php">📙 오답노트 분석</option>
                    <option value="chat05.php" selected>📗 질의응답 분석</option>
                    <option value="chat06.php">📕 복습활동 분석</option>
                    <option value="chat_rules.php">📚 통합 규칙 분석</option>
                </select>
            </div>
            <h1>🗣️ 질의응답 패턴 분석</h1>
            <p>QA Session - 질문하고 답변을 이해하는 과정에서의 학습 패턴을 분석합니다</p>
            <span class="role-badge"><?php echo $role === 'teacher' ? '👨‍🏫 교사' : '👨‍🎓 학생'; ?></span>
        </div>

        <div id="alertBox" class="alert"></div>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('student')">👨‍🎓 학생 대화</button>
            <?php if ($role === 'teacher'): ?>
            <button class="tab-btn" onclick="switchTab('teacher')">👨‍🏫 선생님 입력</button>
            <button class="tab-btn" onclick="switchTab('data')">📊 시스템 데이터</button>
            <?php endif; ?>
        </div>

        <!-- 학생 대화 탭 -->
        <div id="student-tab" class="tab-content active">
            <div class="card">
                <h2 class="card-title">질의응답 학습 습관 자가진단</h2>

                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                </div>
                <p class="progress-text"><span id="progressText">0</span>/30 문항 완료</p>

                <form id="studentForm">
                    <!-- S1: 질의응답 효능감 인식 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>📌 질의응답 효능감 <span class="sub-item-badge">S1: qa_efficacy</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">1. 질문해도 도움이 안 될 것 같아서 질문을 포기한 적이 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="question_uselessness" id="q1_1" value="1">
                                        <label for="q1_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_uselessness" id="q1_2" value="2">
                                        <label for="q1_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_uselessness" id="q1_3" value="3">
                                        <label for="q1_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_uselessness" id="q1_4" value="4">
                                        <label for="q1_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_uselessness" id="q1_5" value="5">
                                        <label for="q1_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">2. 질문하면 바보처럼 보일까봐 질문을 피한 적이 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="question_embarrassment" id="q2_1" value="1">
                                        <label for="q2_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_embarrassment" id="q2_2" value="2">
                                        <label for="q2_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_embarrassment" id="q2_3" value="3">
                                        <label for="q2_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_embarrassment" id="q2_4" value="4">
                                        <label for="q2_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_embarrassment" id="q2_5" value="5">
                                        <label for="q2_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">3. 모든 것을 혼자 해결해야 한다고 생각해서 질문을 기피하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="self_solve_obsession" id="q3_1" value="1">
                                        <label for="q3_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="self_solve_obsession" id="q3_2" value="2">
                                        <label for="q3_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="self_solve_obsession" id="q3_3" value="3">
                                        <label for="q3_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="self_solve_obsession" id="q3_4" value="4">
                                        <label for="q3_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="self_solve_obsession" id="q3_5" value="5">
                                        <label for="q3_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S2: 의문발생 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>🤔 의문발생 <span class="sub-item-badge">S2: doubt_occurrence</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">4. 이해가 안 되는 부분이 있어도 그것을 의문으로 인식하지 못할 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_recognition_failure" id="q4_1" value="1">
                                        <label for="q4_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_recognition_failure" id="q4_2" value="2">
                                        <label for="q4_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_recognition_failure" id="q4_3" value="3">
                                        <label for="q4_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_recognition_failure" id="q4_4" value="4">
                                        <label for="q4_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_recognition_failure" id="q4_5" value="5">
                                        <label for="q4_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">5. 의문이 생겨도 중요하지 않다고 판단해서 무시한 적이 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_dismissal" id="q5_1" value="1">
                                        <label for="q5_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_dismissal" id="q5_2" value="2">
                                        <label for="q5_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_dismissal" id="q5_3" value="3">
                                        <label for="q5_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_dismissal" id="q5_4" value="4">
                                        <label for="q5_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_dismissal" id="q5_5" value="5">
                                        <label for="q5_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">6. 모든 것에 의문을 품어서 학습 진행이 어려운 적이 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_overflow" id="q6_1" value="1">
                                        <label for="q6_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_overflow" id="q6_2" value="2">
                                        <label for="q6_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_overflow" id="q6_3" value="3">
                                        <label for="q6_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_overflow" id="q6_4" value="4">
                                        <label for="q6_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="doubt_overflow" id="q6_5" value="5">
                                        <label for="q6_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S3: 질문생성 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>💬 질문생성 <span class="sub-item-badge">S3: question_generation</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">7. 의문은 있는데 그것을 질문으로 표현하지 못할 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="question_verbalization_failure" id="q7_1" value="1">
                                        <label for="q7_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_verbalization_failure" id="q7_2" value="2">
                                        <label for="q7_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_verbalization_failure" id="q7_3" value="3">
                                        <label for="q7_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_verbalization_failure" id="q7_4" value="4">
                                        <label for="q7_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_verbalization_failure" id="q7_5" value="5">
                                        <label for="q7_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">8. 질문이 너무 광범위하거나 모호해서 답변받기 어려울 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="vague_question" id="q8_1" value="1">
                                        <label for="q8_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="vague_question" id="q8_2" value="2">
                                        <label for="q8_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="vague_question" id="q8_3" value="3">
                                        <label for="q8_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="vague_question" id="q8_4" value="4">
                                        <label for="q8_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="vague_question" id="q8_5" value="5">
                                        <label for="q8_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">9. 진짜 모르는 것 대신 덜 중요한 질문만 할 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="core_question_avoidance" id="q9_1" value="1">
                                        <label for="q9_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="core_question_avoidance" id="q9_2" value="2">
                                        <label for="q9_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="core_question_avoidance" id="q9_3" value="3">
                                        <label for="q9_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="core_question_avoidance" id="q9_4" value="4">
                                        <label for="q9_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="core_question_avoidance" id="q9_5" value="5">
                                        <label for="q9_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S4: 집중해결 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>🎯 집중해결 <span class="sub-item-badge">S4: focused_resolution</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">10. 답변을 들으면서 다른 생각을 하거나 집중하지 못할 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="answer_inattention" id="q10_1" value="1">
                                        <label for="q10_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="answer_inattention" id="q10_2" value="2">
                                        <label for="q10_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="answer_inattention" id="q10_3" value="3">
                                        <label for="q10_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="answer_inattention" id="q10_4" value="4">
                                        <label for="q10_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="answer_inattention" id="q10_5" value="5">
                                        <label for="q10_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">11. 답변을 받고 이해했는지 확인하지 않고 넘어갈 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="understanding_check_skip" id="q11_1" value="1">
                                        <label for="q11_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="understanding_check_skip" id="q11_2" value="2">
                                        <label for="q11_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="understanding_check_skip" id="q11_3" value="3">
                                        <label for="q11_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="understanding_check_skip" id="q11_4" value="4">
                                        <label for="q11_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="understanding_check_skip" id="q11_5" value="5">
                                        <label for="q11_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">12. 일부만 이해하고도 완전히 이해했다고 생각할 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="partial_understanding_satisfaction" id="q12_1" value="1">
                                        <label for="q12_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="partial_understanding_satisfaction" id="q12_2" value="2">
                                        <label for="q12_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="partial_understanding_satisfaction" id="q12_3" value="3">
                                        <label for="q12_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="partial_understanding_satisfaction" id="q12_4" value="4">
                                        <label for="q12_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="partial_understanding_satisfaction" id="q12_5" value="5">
                                        <label for="q12_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S5: 질문결정 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>⚖️ 질문결정 <span class="sub-item-badge">S5: question_decision</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">13. 여러 의문 중 어떤 것을 질문할지 결정하지 못할 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="question_selection_paralysis" id="q13_1" value="1">
                                        <label for="q13_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_selection_paralysis" id="q13_2" value="2">
                                        <label for="q13_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_selection_paralysis" id="q13_3" value="3">
                                        <label for="q13_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_selection_paralysis" id="q13_4" value="4">
                                        <label for="q13_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_selection_paralysis" id="q13_5" value="5">
                                        <label for="q13_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">14. 언제 질문해야 할지 적절한 타이밍을 잡지 못할 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="timing_decision_failure" id="q14_1" value="1">
                                        <label for="q14_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="timing_decision_failure" id="q14_2" value="2">
                                        <label for="q14_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="timing_decision_failure" id="q14_3" value="3">
                                        <label for="q14_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="timing_decision_failure" id="q14_4" value="4">
                                        <label for="q14_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="timing_decision_failure" id="q14_5" value="5">
                                        <label for="q14_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">15. 완벽한 질문을 만들려다가 결국 질문하지 못한 적이 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="perfect_question_obsession" id="q15_1" value="1">
                                        <label for="q15_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="perfect_question_obsession" id="q15_2" value="2">
                                        <label for="q15_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="perfect_question_obsession" id="q15_3" value="3">
                                        <label for="q15_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="perfect_question_obsession" id="q15_4" value="4">
                                        <label for="q15_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="perfect_question_obsession" id="q15_5" value="5">
                                        <label for="q15_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S6: 질의응답 스스로 주도하기 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>🚀 스스로 주도하기 <span class="sub-item-badge">S6: self_directed_qa</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">16. 선생님이 물어봐야만 질문하고, 스스로 질문을 시작하지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="passive_qa" id="q16_1" value="1">
                                        <label for="q16_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="passive_qa" id="q16_2" value="2">
                                        <label for="q16_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="passive_qa" id="q16_3" value="3">
                                        <label for="q16_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="passive_qa" id="q16_4" value="4">
                                        <label for="q16_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="passive_qa" id="q16_5" value="5">
                                        <label for="q16_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">17. 표면적인 질문만 하고 깊이 있는 질문을 피하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_question_preference" id="q17_1" value="1">
                                        <label for="q17_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_question_preference" id="q17_2" value="2">
                                        <label for="q17_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_question_preference" id="q17_3" value="3">
                                        <label for="q17_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_question_preference" id="q17_4" value="4">
                                        <label for="q17_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_question_preference" id="q17_5" value="5">
                                        <label for="q17_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">18. 답변을 받은 후 후속 질문으로 이어가지 못할 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="question_connection_failure" id="q18_1" value="1">
                                        <label for="q18_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_connection_failure" id="q18_2" value="2">
                                        <label for="q18_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_connection_failure" id="q18_3" value="3">
                                        <label for="q18_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_connection_failure" id="q18_4" value="4">
                                        <label for="q18_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_connection_failure" id="q18_5" value="5">
                                        <label for="q18_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S7: 질문 듣는 중 개입방법 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>✋ 개입방법 <span class="sub-item-badge">S7: intervention_method</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">19. 설명 중간에 너무 자주 끊거나, 아예 끊지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="intervention_timing_error" id="q19_1" value="1">
                                        <label for="q19_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="intervention_timing_error" id="q19_2" value="2">
                                        <label for="q19_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="intervention_timing_error" id="q19_3" value="3">
                                        <label for="q19_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="intervention_timing_error" id="q19_4" value="4">
                                        <label for="q19_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="intervention_timing_error" id="q19_5" value="5">
                                        <label for="q19_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">20. 이해가 안 되는데 다시 설명해달라고 요청하지 못하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="reexplanation_request_hesitation" id="q20_1" value="1">
                                        <label for="q20_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="reexplanation_request_hesitation" id="q20_2" value="2">
                                        <label for="q20_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="reexplanation_request_hesitation" id="q20_3" value="3">
                                        <label for="q20_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="reexplanation_request_hesitation" id="q20_4" value="4">
                                        <label for="q20_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="reexplanation_request_hesitation" id="q20_5" value="5">
                                        <label for="q20_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">21. 추상적 설명을 듣고도 구체적 예시를 요청하지 못하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="example_request_failure" id="q21_1" value="1">
                                        <label for="q21_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="example_request_failure" id="q21_2" value="2">
                                        <label for="q21_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="example_request_failure" id="q21_3" value="3">
                                        <label for="q21_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="example_request_failure" id="q21_4" value="4">
                                        <label for="q21_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="example_request_failure" id="q21_5" value="5">
                                        <label for="q21_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S8: 마무리 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>📝 마무리 <span class="sub-item-badge">S8: closing</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">22. 질의응답 후 배운 내용을 정리하지 않고 넘어가나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="closing_summary_skip" id="q22_1" value="1">
                                        <label for="q22_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="closing_summary_skip" id="q22_2" value="2">
                                        <label for="q22_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="closing_summary_skip" id="q22_3" value="3">
                                        <label for="q22_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="closing_summary_skip" id="q22_4" value="4">
                                        <label for="q22_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="closing_summary_skip" id="q22_5" value="5">
                                        <label for="q22_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">23. 마무리 단계에서 추가로 궁금한 점을 확인하지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="additional_question_missed" id="q23_1" value="1">
                                        <label for="q23_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="additional_question_missed" id="q23_2" value="2">
                                        <label for="q23_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="additional_question_missed" id="q23_3" value="3">
                                        <label for="q23_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="additional_question_missed" id="q23_4" value="4">
                                        <label for="q23_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="additional_question_missed" id="q23_5" value="5">
                                        <label for="q23_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">24. 도움을 받고도 적절한 감사를 표현하지 않을 때가 있나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="gratitude_omission" id="q24_1" value="1">
                                        <label for="q24_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="gratitude_omission" id="q24_2" value="2">
                                        <label for="q24_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="gratitude_omission" id="q24_3" value="3">
                                        <label for="q24_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="gratitude_omission" id="q24_4" value="4">
                                        <label for="q24_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="gratitude_omission" id="q24_5" value="5">
                                        <label for="q24_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S9: 추적 및 후속 학습 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>🔍 추적/후속학습 <span class="sub-item-badge">S9: tracking_followup</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">25. 질문으로 해결한 내용을 나중에 다시 확인하지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="followup_check_skip" id="q25_1" value="1">
                                        <label for="q25_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="followup_check_skip" id="q25_2" value="2">
                                        <label for="q25_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="followup_check_skip" id="q25_3" value="3">
                                        <label for="q25_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="followup_check_skip" id="q25_4" value="4">
                                        <label for="q25_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="followup_check_skip" id="q25_5" value="5">
                                        <label for="q25_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">26. 질문으로 이해한 후 비슷한 문제로 연습하지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="similar_problem_practice_avoidance" id="q26_1" value="1">
                                        <label for="q26_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="similar_problem_practice_avoidance" id="q26_2" value="2">
                                        <label for="q26_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="similar_problem_practice_avoidance" id="q26_3" value="3">
                                        <label for="q26_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="similar_problem_practice_avoidance" id="q26_4" value="4">
                                        <label for="q26_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="similar_problem_practice_avoidance" id="q26_5" value="5">
                                        <label for="q26_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">27. 과거 질문과 답변을 기록하고 관리하지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="question_history_unmanaged" id="q27_1" value="1">
                                        <label for="q27_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_history_unmanaged" id="q27_2" value="2">
                                        <label for="q27_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_history_unmanaged" id="q27_3" value="3">
                                        <label for="q27_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_history_unmanaged" id="q27_4" value="4">
                                        <label for="q27_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="question_history_unmanaged" id="q27_5" value="5">
                                        <label for="q27_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S10: 닫기 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>🔚 세션 종료 <span class="sub-item-badge">S10: close_session</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">28. 질의응답을 제대로 마무리하지 않고 급하게 끝내나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="hasty_closure" id="q28_1" value="1">
                                        <label for="q28_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="hasty_closure" id="q28_2" value="2">
                                        <label for="q28_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="hasty_closure" id="q28_3" value="3">
                                        <label for="q28_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="hasty_closure" id="q28_4" value="4">
                                        <label for="q28_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="hasty_closure" id="q28_5" value="5">
                                        <label for="q28_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">29. 해결되지 않은 의문을 남겨둔 채 세션을 종료하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="unresolved_abandonment" id="q29_1" value="1">
                                        <label for="q29_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unresolved_abandonment" id="q29_2" value="2">
                                        <label for="q29_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unresolved_abandonment" id="q29_3" value="3">
                                        <label for="q29_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unresolved_abandonment" id="q29_4" value="4">
                                        <label for="q29_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unresolved_abandonment" id="q29_5" value="5">
                                        <label for="q29_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">30. 질의응답에서 배운 것을 다음 학습과 연결하지 못하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="learning_connection_failure" id="q30_1" value="1">
                                        <label for="q30_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="learning_connection_failure" id="q30_2" value="2">
                                        <label for="q30_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="learning_connection_failure" id="q30_3" value="3">
                                        <label for="q30_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="learning_connection_failure" id="q30_4" value="4">
                                        <label for="q30_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="learning_connection_failure" id="q30_5" value="5">
                                        <label for="q30_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="button" class="btn btn-primary" onclick="saveStudentResponse()">
                            💾 응답 저장하기
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($role === 'teacher'): ?>
        <!-- 선생님 입력 탭 -->
        <div id="teacher-tab" class="tab-content">
            <div class="card">
                <h2 class="card-title">학생 질의응답 패턴 관찰 기록</h2>

                <form id="teacherForm">
                    <!-- S1: 질의응답 효능감 -->
                    <div class="teacher-section">
                        <h4>📌 S1: 질의응답 효능감 인식 (qa_efficacy)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_question_uselessness" value="1">
                                질문 무용감 - 질문해도 도움이 안 된다고 생각함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_question_embarrassment" value="1">
                                질문 당혹감 - 질문하면 바보처럼 보일까 걱정
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_self_solve_obsession" value="1">
                                자기해결 강박 - 모든 것을 혼자 해결하려 함
                            </label>
                        </div>
                    </div>

                    <!-- S2: 의문발생 -->
                    <div class="teacher-section">
                        <h4>🤔 S2: 의문발생 (doubt_occurrence)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_doubt_recognition_failure" value="1">
                                의문 인식 실패 - 모르는 것을 의문으로 인식 못함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_doubt_dismissal" value="1">
                                의문 무시 - 의문이 있어도 중요하지 않다고 판단
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_doubt_overflow" value="1">
                                의문 과잉 - 모든 것에 의문을 품어 진행 어려움
                            </label>
                        </div>
                    </div>

                    <!-- S3: 질문생성 -->
                    <div class="teacher-section">
                        <h4>💬 S3: 질문생성 (question_generation)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_question_verbalization_failure" value="1">
                                언어화 실패 - 의문을 질문으로 표현하지 못함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_vague_question" value="1">
                                모호한 질문 - 질문이 광범위하거나 모호함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_core_question_avoidance" value="1">
                                핵심 질문 회피 - 진짜 모르는 것 대신 덜 중요한 것만 질문
                            </label>
                        </div>
                    </div>

                    <!-- S4: 집중해결 -->
                    <div class="teacher-section">
                        <h4>🎯 S4: 집중해결 (focused_resolution)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_answer_inattention" value="1">
                                답변 미집중 - 답변 들으면서 다른 생각 함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_understanding_check_skip" value="1">
                                이해 확인 생략 - 답변 후 이해 확인 없이 넘어감
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_partial_understanding_satisfaction" value="1">
                                부분 이해 만족 - 일부만 이해하고 완전히 이해했다고 착각
                            </label>
                        </div>
                    </div>

                    <!-- S5: 질문결정 -->
                    <div class="teacher-section">
                        <h4>⚖️ S5: 질문결정 (question_decision)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_question_selection_paralysis" value="1">
                                질문 선택 마비 - 여러 의문 중 선택 못함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_timing_decision_failure" value="1">
                                타이밍 결정 실패 - 적절한 질문 시점 파악 못함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_perfect_question_obsession" value="1">
                                완벽한 질문 강박 - 완벽한 질문 만들려다 포기
                            </label>
                        </div>
                    </div>

                    <!-- S6: 스스로 주도하기 -->
                    <div class="teacher-section">
                        <h4>🚀 S6: 스스로 주도하기 (self_directed_qa)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_passive_qa" value="1">
                                수동적 질의응답 - 선생님이 물어봐야만 질문
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_shallow_question_preference" value="1">
                                표면 질문 선호 - 깊이 있는 질문 회피
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_question_connection_failure" value="1">
                                질문 연결 실패 - 답변 후 후속 질문으로 이어가지 못함
                            </label>
                        </div>
                    </div>

                    <!-- S7: 개입방법 -->
                    <div class="teacher-section">
                        <h4>✋ S7: 개입방법 (intervention_method)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_intervention_timing_error" value="1">
                                개입 타이밍 오류 - 너무 자주 끊거나 아예 안 끊음
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_reexplanation_request_hesitation" value="1">
                                재설명 요청 주저 - 다시 설명해달라고 못함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_example_request_failure" value="1">
                                예시 요청 실패 - 구체적 예시 요청을 못함
                            </label>
                        </div>
                    </div>

                    <!-- S8: 마무리 -->
                    <div class="teacher-section">
                        <h4>📝 S8: 마무리 (closing)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_closing_summary_skip" value="1">
                                마무리 정리 생략 - 배운 내용 정리 없이 넘어감
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_additional_question_missed" value="1">
                                추가 질문 누락 - 마무리 시 추가 질문 확인 안 함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_gratitude_omission" value="1">
                                감사 누락 - 도움 받고 적절한 감사 표현 안 함
                            </label>
                        </div>
                    </div>

                    <!-- S9: 추적/후속학습 -->
                    <div class="teacher-section">
                        <h4>🔍 S9: 추적 및 후속학습 (tracking_followup)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_followup_check_skip" value="1">
                                후속 점검 미실시 - 해결 내용 나중에 확인 안 함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_similar_problem_practice_avoidance" value="1">
                                유사 문제 연습 회피 - 이해 후 비슷한 문제 연습 안 함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_question_history_unmanaged" value="1">
                                질문 이력 미관리 - 과거 질문/답변 기록 안 함
                            </label>
                        </div>
                    </div>

                    <!-- S10: 세션 종료 -->
                    <div class="teacher-section">
                        <h4>🔚 S10: 세션 종료 (close_session)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_hasty_closure" value="1">
                                급한 종료 - 제대로 마무리 없이 급하게 끝냄
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_unresolved_abandonment" value="1">
                                미해결 방치 - 해결 안 된 의문 남겨둔 채 종료
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_learning_connection_failure" value="1">
                                학습 연계 실패 - 배운 것을 다음 학습과 연결 못함
                            </label>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button type="button" class="btn btn-success" onclick="saveTeacherObservation()">
                            📝 관찰 기록 저장
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 시스템 데이터 탭 -->
        <div id="data-tab" class="tab-content">
            <div class="card">
                <h2 class="card-title">질의응답 패턴 데이터 조회</h2>

                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="loadData('student_qa_session')">학생 응답 조회</button>
                    <button class="btn btn-success" onclick="loadData('teacher_qa_session')">교사 관찰 조회</button>
                </div>

                <div id="dataTableContainer">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>사용자 ID</th>
                                <th>생성일시</th>
                                <th>데이터</th>
                            </tr>
                        </thead>
                        <tbody id="dataTableBody">
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-secondary);">
                                    데이터를 조회하려면 위 버튼을 클릭하세요.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // 페이지 네비게이션
        function navigateToPage(page) {
            if (page) {
                window.location.href = page;
            }
        }

        // 탭 전환
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // 아코디언 토글
        function toggleAccordion(header) {
            header.classList.toggle('active');
            header.nextElementSibling.classList.toggle('active');
        }

        // 진행률 업데이트
        function updateProgress() {
            const totalQuestions = 30;
            const answeredQuestions = document.querySelectorAll('#studentForm input[type="radio"]:checked').length;
            const percentage = Math.round((answeredQuestions / totalQuestions) * 100);

            document.getElementById('progressFill').style.width = percentage + '%';
            document.getElementById('progressText').textContent = answeredQuestions;
        }

        // 라디오 버튼 변경 이벤트 리스너
        document.querySelectorAll('#studentForm input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', updateProgress);
        });

        // 알림 표시
        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = 'alert alert-' + type;
            alertBox.textContent = message;
            alertBox.style.display = 'block';

            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 3000);
        }

        // 학생 응답 저장
        function saveStudentResponse() {
            const form = document.getElementById('studentForm');
            const formData = new FormData(form);
            const responses = {};

            for (let [key, value] of formData.entries()) {
                responses[key] = value;
            }

            if (Object.keys(responses).length === 0) {
                showAlert('최소 하나 이상의 문항에 응답해주세요.', 'error');
                return;
            }

            fetch('chat05.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=save_student_response&responses=' + encodeURIComponent(JSON.stringify(responses))
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('저장 중 오류가 발생했습니다. (chat05.php:saveStudentResponse)', 'error');
                console.error('Error:', error);
            });
        }

        // 교사 관찰 저장
        function saveTeacherObservation() {
            const form = document.getElementById('teacherForm');
            const formData = new FormData(form);
            const observations = {};

            for (let [key, value] of formData.entries()) {
                if (form.querySelector('[name="' + key + '"]').checked) {
                    observations[key] = value;
                }
            }

            fetch('chat05.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=save_teacher_observation&observations=' + encodeURIComponent(JSON.stringify(observations))
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('저장 중 오류가 발생했습니다. (chat05.php:saveTeacherObservation)', 'error');
                console.error('Error:', error);
            });
        }

        // 데이터 조회
        function loadData(dataType) {
            fetch('chat05.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=load_data&data_type=' + dataType
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('dataTableBody');

                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-secondary);">데이터가 없습니다.</td></tr>';
                        return;
                    }

                    tbody.innerHTML = data.data.map(item => `
                        <tr>
                            <td>${item.id}</td>
                            <td>${item.userid}</td>
                            <td>${item.timecreated}</td>
                            <td><pre style="max-width: 400px; overflow-x: auto; white-space: pre-wrap; font-size: 0.85rem;">${JSON.stringify(item.data_content, null, 2)}</pre></td>
                        </tr>
                    `).join('');
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('데이터 조회 중 오류가 발생했습니다. (chat05.php:loadData)', 'error');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
<?php
/**
 * 관련 DB 정보
 * ============
 * 테이블: mdl_agent04_chat_data
 *
 * Fields:
 * - id (bigint) : 기본키
 * - userid (bigint) : 사용자 ID (mdl_user.id 참조)
 * - nagent (int) : 에이전트 번호 (4 = Agent04)
 * - data_type (varchar) : 데이터 유형 ('student_qa_session', 'teacher_qa_session')
 * - data_content (longtext) : JSON 형식 데이터
 * - timecreated (bigint) : 생성 시간 (Unix timestamp)
 * - timemodified (bigint) : 수정 시간 (Unix timestamp)
 *
 * Pattern Hints (30개):
 * S1 (qa_efficacy): question_uselessness, question_embarrassment, self_solve_obsession
 * S2 (doubt_occurrence): doubt_recognition_failure, doubt_dismissal, doubt_overflow
 * S3 (question_generation): question_verbalization_failure, vague_question, core_question_avoidance
 * S4 (focused_resolution): answer_inattention, understanding_check_skip, partial_understanding_satisfaction
 * S5 (question_decision): question_selection_paralysis, timing_decision_failure, perfect_question_obsession
 * S6 (self_directed_qa): passive_qa, shallow_question_preference, question_connection_failure
 * S7 (intervention_method): intervention_timing_error, reexplanation_request_hesitation, example_request_failure
 * S8 (closing): closing_summary_skip, additional_question_missed, gratitude_omission
 * S9 (tracking_followup): followup_check_skip, similar_problem_practice_avoidance, question_history_unmanaged
 * S10 (close_session): hasty_closure, unresolved_abandonment, learning_connection_failure
 */
?>
