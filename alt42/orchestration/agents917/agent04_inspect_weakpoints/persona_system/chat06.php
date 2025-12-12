<?php
/**
 * chat06.php - 복습활동(Review Activity) 설문 인터페이스
 *
 * rules06.yaml 기반 28개 pattern_hint 연결
 * 시나리오: review_activity
 * 7개 sub_items: review_efficacy, review_time_setting, need_analysis, review_curriculum,
 *                review_execution, review_closing, closing_feedback
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
            $data->data_type = 'student_review_activity';
            $data->data_content = json_encode([
                'responses' => $responses,
                'scenario' => 'review_activity',
                'timestamp' => time()
            ], JSON_UNESCAPED_UNICODE);
            $data->timecreated = time();
            $data->timemodified = time();

            // 기존 데이터 확인
            $existing = $DB->get_record_sql(
                "SELECT id FROM mdl_agent04_chat_data WHERE userid = ? AND nagent = 4 AND data_type = 'student_review_activity' ORDER BY id DESC LIMIT 1",
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
            $data->data_type = 'teacher_review_activity';
            $data->data_content = json_encode([
                'observations' => $observations,
                'target_userid' => $target_userid,
                'scenario' => 'review_activity',
                'timestamp' => time()
            ], JSON_UNESCAPED_UNICODE);
            $data->timecreated = time();
            $data->timemodified = time();

            $newid = $DB->insert_record('mdl_agent04_chat_data', $data);
            echo json_encode(['success' => true, 'message' => '관찰 기록이 저장되었습니다.', 'id' => $newid]);
            exit;
        }

        if ($_POST['action'] === 'load_data') {
            $data_type = isset($_POST['data_type']) ? $_POST['data_type'] : 'student_review_activity';

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
        echo json_encode(['success' => false, 'message' => '오류: ' . $e->getMessage() . ' (chat06.php:' . $e->getLine() . ')']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>복습활동 패턴 분석 (Review Activity)</title>
    <style>
        :root {
            --primary: #a855f7;
            --primary-dark: #9333ea;
            --primary-light: #d8b4fe;
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
            background: rgba(168, 85, 247, 0.1);
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
            background: rgba(168, 85, 247, 0.2);
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
            background: rgba(168, 85, 247, 0.1);
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
            background: rgba(168, 85, 247, 0.05);
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
                    <option value="chat05.php">📗 질의응답 분석</option>
                    <option value="chat06.php" selected>📕 복습활동 분석</option>
                    <option value="chat_rules.php">📚 통합 규칙 분석</option>
                </select>
            </div>
            <h1>📚 복습활동 패턴 분석</h1>
            <p>Review Activity - 복습 과정에서의 학습 패턴과 습관을 분석합니다</p>
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
                <h2 class="card-title">복습활동 학습 습관 자가진단</h2>

                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                </div>
                <p class="progress-text"><span id="progressText">0</span>/28 문항 완료</p>

                <form id="studentForm">
                    <!-- S1: 복습루틴 효능감 인식 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>💡 복습 효능감 <span class="sub-item-badge">S1: review_efficacy</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">1. 복습해도 의미없다고 생각하여 복습을 회피하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="review_uselessness_belief" id="q1_1" value="1">
                                        <label for="q1_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_uselessness_belief" id="q1_2" value="2">
                                        <label for="q1_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_uselessness_belief" id="q1_3" value="3">
                                        <label for="q1_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_uselessness_belief" id="q1_4" value="4">
                                        <label for="q1_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_uselessness_belief" id="q1_5" value="5">
                                        <label for="q1_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">2. 새로운 내용 학습만 선호하고 복습을 지루하게 여기나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="review_boredom" id="q2_1" value="1">
                                        <label for="q2_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_boredom" id="q2_2" value="2">
                                        <label for="q2_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_boredom" id="q2_3" value="3">
                                        <label for="q2_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_boredom" id="q2_4" value="4">
                                        <label for="q2_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_boredom" id="q2_5" value="5">
                                        <label for="q2_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">3. 다 안다고 생각하여 복습을 생략하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="overconfidence_skip" id="q3_1" value="1">
                                        <label for="q3_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="overconfidence_skip" id="q3_2" value="2">
                                        <label for="q3_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="overconfidence_skip" id="q3_3" value="3">
                                        <label for="q3_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="overconfidence_skip" id="q3_4" value="4">
                                        <label for="q3_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="overconfidence_skip" id="q3_5" value="5">
                                        <label for="q3_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">4. 정기적인 복습 습관이 형성되지 않았나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="routine_absence" id="q4_1" value="1">
                                        <label for="q4_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="routine_absence" id="q4_2" value="2">
                                        <label for="q4_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="routine_absence" id="q4_3" value="3">
                                        <label for="q4_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="routine_absence" id="q4_4" value="4">
                                        <label for="q4_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="routine_absence" id="q4_5" value="5">
                                        <label for="q4_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S2: 복습시간 정하기 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>⏰ 복습시간 설정 <span class="sub-item-badge">S2: review_time_setting</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">5. 언제 복습할지 구체적인 시간을 정하지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="time_unset" id="q5_1" value="1">
                                        <label for="q5_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="time_unset" id="q5_2" value="2">
                                        <label for="q5_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="time_unset" id="q5_3" value="3">
                                        <label for="q5_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="time_unset" id="q5_4" value="4">
                                        <label for="q5_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="time_unset" id="q5_5" value="5">
                                        <label for="q5_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">6. 실행 불가능할 정도로 많은 복습량을 계획하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="unrealistic_plan" id="q6_1" value="1">
                                        <label for="q6_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unrealistic_plan" id="q6_2" value="2">
                                        <label for="q6_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unrealistic_plan" id="q6_3" value="3">
                                        <label for="q6_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unrealistic_plan" id="q6_4" value="4">
                                        <label for="q6_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unrealistic_plan" id="q6_5" value="5">
                                        <label for="q6_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">7. 집중력이 낮은 시간대(피곤한 시간)에 복습을 배정하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="tired_time_allocation" id="q7_1" value="1">
                                        <label for="q7_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="tired_time_allocation" id="q7_2" value="2">
                                        <label for="q7_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="tired_time_allocation" id="q7_3" value="3">
                                        <label for="q7_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="tired_time_allocation" id="q7_4" value="4">
                                        <label for="q7_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="tired_time_allocation" id="q7_5" value="5">
                                        <label for="q7_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">8. 에빙하우스 망각곡선(1일-3일-7일-30일)을 고려하지 않고 복습하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="interval_ignorance" id="q8_1" value="1">
                                        <label for="q8_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="interval_ignorance" id="q8_2" value="2">
                                        <label for="q8_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="interval_ignorance" id="q8_3" value="3">
                                        <label for="q8_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="interval_ignorance" id="q8_4" value="4">
                                        <label for="q8_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="interval_ignorance" id="q8_5" value="5">
                                        <label for="q8_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S3: 필요영역 분석 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>🎯 필요영역 분석 <span class="sub-item-badge">S3: need_analysis</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">9. 모든 내용을 똑같이 복습하려 하여 효율이 떨어지나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="complete_review_insistence" id="q9_1" value="1">
                                        <label for="q9_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="complete_review_insistence" id="q9_2" value="2">
                                        <label for="q9_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="complete_review_insistence" id="q9_3" value="3">
                                        <label for="q9_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="complete_review_insistence" id="q9_4" value="4">
                                        <label for="q9_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="complete_review_insistence" id="q9_5" value="5">
                                        <label for="q9_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">10. 어떤 부분이 약한지 파악하지 못하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_analysis_failure" id="q10_1" value="1">
                                        <label for="q10_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_analysis_failure" id="q10_2" value="2">
                                        <label for="q10_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_analysis_failure" id="q10_3" value="3">
                                        <label for="q10_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_analysis_failure" id="q10_4" value="4">
                                        <label for="q10_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_analysis_failure" id="q10_5" value="5">
                                        <label for="q10_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">11. 약한 부분은 피하고 자신있는 부분만 복습하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_avoidance" id="q11_1" value="1">
                                        <label for="q11_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_avoidance" id="q11_2" value="2">
                                        <label for="q11_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_avoidance" id="q11_3" value="3">
                                        <label for="q11_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_avoidance" id="q11_4" value="4">
                                        <label for="q11_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="weakness_avoidance" id="q11_5" value="5">
                                        <label for="q11_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">12. 어떤 내용부터 복습해야 할지 결정하지 못하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="priority_confusion" id="q12_1" value="1">
                                        <label for="q12_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="priority_confusion" id="q12_2" value="2">
                                        <label for="q12_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="priority_confusion" id="q12_3" value="3">
                                        <label for="q12_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="priority_confusion" id="q12_4" value="4">
                                        <label for="q12_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="priority_confusion" id="q12_5" value="5">
                                        <label for="q12_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S4: 복습 커리큘럼 정하기 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>📋 복습 커리큘럼 <span class="sub-item-badge">S4: review_curriculum</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">13. 복습 순서와 계획 없이 무작위로 복습하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="unplanned_review" id="q13_1" value="1">
                                        <label for="q13_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unplanned_review" id="q13_2" value="2">
                                        <label for="q13_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unplanned_review" id="q13_3" value="3">
                                        <label for="q13_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unplanned_review" id="q13_4" value="4">
                                        <label for="q13_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="unplanned_review" id="q13_5" value="5">
                                        <label for="q13_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">14. 특정 과목/단원만 반복 복습하고 다른 것은 무시하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="subject_bias" id="q14_1" value="1">
                                        <label for="q14_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="subject_bias" id="q14_2" value="2">
                                        <label for="q14_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="subject_bias" id="q14_3" value="3">
                                        <label for="q14_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="subject_bias" id="q14_4" value="4">
                                        <label for="q14_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="subject_bias" id="q14_5" value="5">
                                        <label for="q14_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">15. 항상 같은 방법으로만 복습하여 효과가 떨어지나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="monotonous_method" id="q15_1" value="1">
                                        <label for="q15_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="monotonous_method" id="q15_2" value="2">
                                        <label for="q15_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="monotonous_method" id="q15_3" value="3">
                                        <label for="q15_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="monotonous_method" id="q15_4" value="4">
                                        <label for="q15_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="monotonous_method" id="q15_5" value="5">
                                        <label for="q15_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">16. 관련된 내용을 연결하지 않고 개별적으로 복습하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="connection_ignorance" id="q16_1" value="1">
                                        <label for="q16_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="connection_ignorance" id="q16_2" value="2">
                                        <label for="q16_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="connection_ignorance" id="q16_3" value="3">
                                        <label for="q16_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="connection_ignorance" id="q16_4" value="4">
                                        <label for="q16_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="connection_ignorance" id="q16_5" value="5">
                                        <label for="q16_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S5: 복습실행 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>▶️ 복습실행 <span class="sub-item-badge">S5: review_execution</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">17. 단순히 읽기만 하는 수동적 복습을 하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="passive_review" id="q17_1" value="1">
                                        <label for="q17_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="passive_review" id="q17_2" value="2">
                                        <label for="q17_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="passive_review" id="q17_3" value="3">
                                        <label for="q17_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="passive_review" id="q17_4" value="4">
                                        <label for="q17_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="passive_review" id="q17_5" value="5">
                                        <label for="q17_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">18. 복습 중에 다른 것에 신경 쓰여 집중하지 못하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="distracted_review" id="q18_1" value="1">
                                        <label for="q18_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="distracted_review" id="q18_2" value="2">
                                        <label for="q18_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="distracted_review" id="q18_3" value="3">
                                        <label for="q18_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="distracted_review" id="q18_4" value="4">
                                        <label for="q18_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="distracted_review" id="q18_5" value="5">
                                        <label for="q18_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">19. 피상적으로만 훑고 지나가는 복습을 하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_review" id="q19_1" value="1">
                                        <label for="q19_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_review" id="q19_2" value="2">
                                        <label for="q19_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_review" id="q19_3" value="3">
                                        <label for="q19_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_review" id="q19_4" value="4">
                                        <label for="q19_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="shallow_review" id="q19_5" value="5">
                                        <label for="q19_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">20. 복습을 시작했다가 중간에 포기하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="review_abandonment" id="q20_1" value="1">
                                        <label for="q20_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_abandonment" id="q20_2" value="2">
                                        <label for="q20_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_abandonment" id="q20_3" value="3">
                                        <label for="q20_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_abandonment" id="q20_4" value="4">
                                        <label for="q20_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="review_abandonment" id="q20_5" value="5">
                                        <label for="q20_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S6: 복습 마무리 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>✅ 복습 마무리 <span class="sub-item-badge">S6: review_closing</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">21. 복습 후 이해도를 확인하지 않고 끝내나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="verification_skip" id="q21_1" value="1">
                                        <label for="q21_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="verification_skip" id="q21_2" value="2">
                                        <label for="q21_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="verification_skip" id="q21_3" value="3">
                                        <label for="q21_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="verification_skip" id="q21_4" value="4">
                                        <label for="q21_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="verification_skip" id="q21_5" value="5">
                                        <label for="q21_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">22. 복습한 내용과 결과를 기록하지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="record_skip" id="q22_1" value="1">
                                        <label for="q22_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="record_skip" id="q22_2" value="2">
                                        <label for="q22_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="record_skip" id="q22_3" value="3">
                                        <label for="q22_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="record_skip" id="q22_4" value="4">
                                        <label for="q22_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="record_skip" id="q22_5" value="5">
                                        <label for="q22_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">23. 다음 복습 일정을 정하지 않고 끝내나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="next_review_unplanned" id="q23_1" value="1">
                                        <label for="q23_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="next_review_unplanned" id="q23_2" value="2">
                                        <label for="q23_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="next_review_unplanned" id="q23_3" value="3">
                                        <label for="q23_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="next_review_unplanned" id="q23_4" value="4">
                                        <label for="q23_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="next_review_unplanned" id="q23_5" value="5">
                                        <label for="q23_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">24. 복습을 통한 성장을 인식하지 못하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="growth_recognition_failure" id="q24_1" value="1">
                                        <label for="q24_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="growth_recognition_failure" id="q24_2" value="2">
                                        <label for="q24_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="growth_recognition_failure" id="q24_3" value="3">
                                        <label for="q24_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="growth_recognition_failure" id="q24_4" value="4">
                                        <label for="q24_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="growth_recognition_failure" id="q24_5" value="5">
                                        <label for="q24_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- S7: 마무리 피드백 -->
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <h3>💬 마무리 피드백 <span class="sub-item-badge">S7: closing_feedback</span></h3>
                            <span class="accordion-icon">+</span>
                        </div>
                        <div class="accordion-content">
                            <div class="question-item">
                                <p class="question-text">25. 복습 결과에 대한 피드백을 받아들이지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_rejection" id="q25_1" value="1">
                                        <label for="q25_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_rejection" id="q25_2" value="2">
                                        <label for="q25_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_rejection" id="q25_3" value="3">
                                        <label for="q25_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_rejection" id="q25_4" value="4">
                                        <label for="q25_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_rejection" id="q25_5" value="5">
                                        <label for="q25_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">26. 피드백을 받았지만 다음 복습에 반영하지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_unreflected" id="q26_1" value="1">
                                        <label for="q26_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_unreflected" id="q26_2" value="2">
                                        <label for="q26_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_unreflected" id="q26_3" value="3">
                                        <label for="q26_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_unreflected" id="q26_4" value="4">
                                        <label for="q26_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="feedback_unreflected" id="q26_5" value="5">
                                        <label for="q26_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">27. 실제보다 높거나 낮게 복습 효과를 평가하나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="self_assessment_distortion" id="q27_1" value="1">
                                        <label for="q27_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="self_assessment_distortion" id="q27_2" value="2">
                                        <label for="q27_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="self_assessment_distortion" id="q27_3" value="3">
                                        <label for="q27_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="self_assessment_distortion" id="q27_4" value="4">
                                        <label for="q27_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="self_assessment_distortion" id="q27_5" value="5">
                                        <label for="q27_5">5<br>매우 그렇다</label>
                                    </div>
                                </div>
                            </div>

                            <div class="question-item">
                                <p class="question-text">28. 복습 후 무엇을 개선할지 방향을 정하지 않나요?</p>
                                <div class="likert-scale">
                                    <div class="likert-option">
                                        <input type="radio" name="improvement_direction_unset" id="q28_1" value="1">
                                        <label for="q28_1">1<br>전혀 아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="improvement_direction_unset" id="q28_2" value="2">
                                        <label for="q28_2">2<br>아님</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="improvement_direction_unset" id="q28_3" value="3">
                                        <label for="q28_3">3<br>보통</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="improvement_direction_unset" id="q28_4" value="4">
                                        <label for="q28_4">4<br>그렇다</label>
                                    </div>
                                    <div class="likert-option">
                                        <input type="radio" name="improvement_direction_unset" id="q28_5" value="5">
                                        <label for="q28_5">5<br>매우 그렇다</label>
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
                <h2 class="card-title">학생 복습활동 패턴 관찰 기록</h2>

                <form id="teacherForm">
                    <!-- S1: 복습 효능감 -->
                    <div class="teacher-section">
                        <h4>💡 S1: 복습 효능감 (review_efficacy)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_review_uselessness_belief" value="1">
                                복습 무용론 - 복습해도 의미없다고 생각
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_review_boredom" value="1">
                                복습 기피 - 새로운 것만 선호, 복습 지루해함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_overconfidence_skip" value="1">
                                과신 복습 생략 - 다 안다고 생각해서 복습 안 함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_routine_absence" value="1">
                                루틴 부재 - 정기적 복습 습관이 없음
                            </label>
                        </div>
                    </div>

                    <!-- S2: 복습시간 설정 -->
                    <div class="teacher-section">
                        <h4>⏰ S2: 복습시간 설정 (review_time_setting)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_time_unset" value="1">
                                시간 미설정 - 구체적 복습 시간 없음
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_unrealistic_plan" value="1">
                                비현실적 계획 - 실행 불가능한 복습량 계획
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_tired_time_allocation" value="1">
                                피곤한 시간 배정 - 집중력 낮은 시간에 복습
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_interval_ignorance" value="1">
                                간격 무시 - 에빙하우스 망각곡선 미적용
                            </label>
                        </div>
                    </div>

                    <!-- S3: 필요영역 분석 -->
                    <div class="teacher-section">
                        <h4>🎯 S3: 필요영역 분석 (need_analysis)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_complete_review_insistence" value="1">
                                전체 복습 고집 - 모든 내용 똑같이 복습
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_weakness_analysis_failure" value="1">
                                약점 분석 실패 - 어떤 부분이 약한지 모름
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_weakness_avoidance" value="1">
                                약점 회피 - 약한 부분 피하고 자신있는 것만
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_priority_confusion" value="1">
                                우선순위 혼란 - 뭐부터 복습할지 결정 못함
                            </label>
                        </div>
                    </div>

                    <!-- S4: 복습 커리큘럼 -->
                    <div class="teacher-section">
                        <h4>📋 S4: 복습 커리큘럼 (review_curriculum)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_unplanned_review" value="1">
                                무계획 복습 - 순서나 계획 없이 무작위 복습
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_subject_bias" value="1">
                                과목 편중 - 특정 과목만 반복, 다른 것 무시
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_monotonous_method" value="1">
                                복습 방법 단조 - 항상 같은 방법으로만 복습
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_connection_ignorance" value="1">
                                연결성 무시 - 관련 내용 연결 없이 개별 복습
                            </label>
                        </div>
                    </div>

                    <!-- S5: 복습실행 -->
                    <div class="teacher-section">
                        <h4>▶️ S5: 복습실행 (review_execution)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_passive_review" value="1">
                                수동적 복습 - 단순히 읽기만 함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_distracted_review" value="1">
                                산만한 복습 - 다른 것에 신경 쓰여 집중 못함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_shallow_review" value="1">
                                피상적 복습 - 피상적으로만 훑고 지나감
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_review_abandonment" value="1">
                                복습 중단 - 시작했다가 중간에 포기
                            </label>
                        </div>
                    </div>

                    <!-- S6: 복습 마무리 -->
                    <div class="teacher-section">
                        <h4>✅ S6: 복습 마무리 (review_closing)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_verification_skip" value="1">
                                검증 생략 - 복습 후 이해도 확인 안 함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_record_skip" value="1">
                                기록 미작성 - 복습 내용/결과 기록 안 함
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_next_review_unplanned" value="1">
                                다음 복습 미계획 - 다음 일정 정하지 않음
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_growth_recognition_failure" value="1">
                                성과 인정 실패 - 복습으로 인한 성장 인식 못함
                            </label>
                        </div>
                    </div>

                    <!-- S7: 마무리 피드백 -->
                    <div class="teacher-section">
                        <h4>💬 S7: 마무리 피드백 (closing_feedback)</h4>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_feedback_rejection" value="1">
                                피드백 거부 - 복습 결과 피드백 안 받아들임
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_feedback_unreflected" value="1">
                                피드백 미반영 - 피드백 받고도 다음에 미적용
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_self_assessment_distortion" value="1">
                                자기 평가 왜곡 - 실제보다 높거나 낮게 평가
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="obs_improvement_direction_unset" value="1">
                                개선 방향 미설정 - 뭘 개선할지 방향 없음
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
                <h2 class="card-title">복습활동 패턴 데이터 조회</h2>

                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="loadData('student_review_activity')">학생 응답 조회</button>
                    <button class="btn btn-success" onclick="loadData('teacher_review_activity')">교사 관찰 조회</button>
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
            const totalQuestions = 28;
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

            fetch('chat06.php', {
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
                showAlert('저장 중 오류가 발생했습니다. (chat06.php:saveStudentResponse)', 'error');
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

            fetch('chat06.php', {
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
                showAlert('저장 중 오류가 발생했습니다. (chat06.php:saveTeacherObservation)', 'error');
                console.error('Error:', error);
            });
        }

        // 데이터 조회
        function loadData(dataType) {
            fetch('chat06.php', {
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
                showAlert('데이터 조회 중 오류가 발생했습니다. (chat06.php:loadData)', 'error');
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
 * - data_type (varchar) : 데이터 유형 ('student_review_activity', 'teacher_review_activity')
 * - data_content (longtext) : JSON 형식 데이터
 * - timecreated (bigint) : 생성 시간 (Unix timestamp)
 * - timemodified (bigint) : 수정 시간 (Unix timestamp)
 *
 * Pattern Hints (28개):
 * S1 (review_efficacy): review_uselessness_belief, review_boredom, overconfidence_skip, routine_absence
 * S2 (review_time_setting): time_unset, unrealistic_plan, tired_time_allocation, interval_ignorance
 * S3 (need_analysis): complete_review_insistence, weakness_analysis_failure, weakness_avoidance, priority_confusion
 * S4 (review_curriculum): unplanned_review, subject_bias, monotonous_method, connection_ignorance
 * S5 (review_execution): passive_review, distracted_review, shallow_review, review_abandonment
 * S6 (review_closing): verification_skip, record_skip, next_review_unplanned, growth_recognition_failure
 * S7 (closing_feedback): feedback_rejection, feedback_unreflected, self_assessment_distortion, improvement_direction_unset
 */
?>
