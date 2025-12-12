<?php
/**
 * Agent04 오답노트 진단 채팅 인터페이스
 *
 * 오답노트 작성 및 활용 시 발생하는 인지관성 패턴을 탐지하기 위한
 * 학생 설문 및 교사 관찰 기록 시스템
 *
 * @package AugmentedTeacher\Agent04\PersonaSystem
 * @version 1.0
 * @since 2025-12-04
 *
 * 관련 파일:
 * - rules04.yaml: 오답노트 페르소나 기반 룰 엔진
 * - persona_manager.php: 페르소나 관리자
 *
 * DB 테이블: mdl_agent04_chat_data
 * - id (int): Primary key
 * - userid (int): 사용자 ID
 * - data_type (varchar): student_wrong_answer_note / teacher_wrong_answer_note
 * - data_content (text): JSON 형태의 응답 데이터
 * - timecreated (int): 생성 시간
 */

// Moodle 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'save_student_response':
                $responses = $_POST['responses'] ?? [];

                if (empty($responses)) {
                    throw new Exception("응답 데이터가 없습니다. [chat04.php:45]");
                }

                $data = new stdClass();
                $data->userid = $USER->id;
                $data->data_type = 'student_wrong_answer_note';
                $data->data_content = json_encode([
                    'responses' => $responses,
                    'scenario' => 'wrong_answer_note',
                    'timestamp' => time()
                ], JSON_UNESCAPED_UNICODE);
                $data->timecreated = time();

                $existing = $DB->get_record('agent04_chat_data', [
                    'userid' => $USER->id,
                    'data_type' => 'student_wrong_answer_note'
                ]);

                if ($existing) {
                    $data->id = $existing->id;
                    $DB->update_record('agent04_chat_data', $data);
                } else {
                    $DB->insert_record('agent04_chat_data', $data);
                }

                echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
                break;

            case 'save_teacher_observation':
                if ($role === 'student') {
                    throw new Exception("권한이 없습니다. [chat04.php:73]");
                }

                $student_id = $_POST['student_id'] ?? 0;
                $observations = $_POST['observations'] ?? [];

                if (empty($student_id)) {
                    throw new Exception("학생 ID가 필요합니다. [chat04.php:80]");
                }

                $data = new stdClass();
                $data->userid = $student_id;
                $data->data_type = 'teacher_wrong_answer_note';
                $data->data_content = json_encode([
                    'observations' => $observations,
                    'teacher_id' => $USER->id,
                    'scenario' => 'wrong_answer_note',
                    'timestamp' => time()
                ], JSON_UNESCAPED_UNICODE);
                $data->timecreated = time();

                $existing = $DB->get_record_sql(
                    "SELECT * FROM {agent04_chat_data}
                     WHERE userid = ? AND data_type = 'teacher_wrong_answer_note'",
                    [$student_id]
                );

                if ($existing) {
                    $data->id = $existing->id;
                    $DB->update_record('agent04_chat_data', $data);
                } else {
                    $DB->insert_record('agent04_chat_data', $data);
                }

                echo json_encode(['success' => true, 'message' => '관찰 기록이 저장되었습니다.']);
                break;

            case 'load_data':
                $data_type = $_POST['data_type'] ?? 'student_wrong_answer_note';
                $target_user = $_POST['target_user'] ?? $USER->id;

                $record = $DB->get_record('agent04_chat_data', [
                    'userid' => $target_user,
                    'data_type' => $data_type
                ]);

                if ($record) {
                    echo json_encode([
                        'success' => true,
                        'data' => json_decode($record->data_content, true),
                        'timecreated' => $record->timecreated
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => '데이터가 없습니다.']);
                }
                break;

            case 'get_students':
                if ($role === 'student') {
                    throw new Exception("권한이 없습니다. [chat04.php:133]");
                }

                $students = $DB->get_records_sql(
                    "SELECT DISTINCT u.id, u.firstname, u.lastname
                     FROM {user} u
                     JOIN {agent04_chat_data} d ON u.id = d.userid
                     WHERE d.data_type = 'student_wrong_answer_note'
                     ORDER BY u.lastname, u.firstname"
                );

                echo json_encode(['success' => true, 'students' => array_values($students)]);
                break;

            default:
                throw new Exception("알 수 없는 액션입니다. [chat04.php:148]");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📒 오답노트 진단 - Agent04</title>
    <style>
        :root {
            --primary: #f97316;
            --primary-light: #fed7aa;
            --primary-dark: #ea580c;
            --bg-light: #fff7ed;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
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
            background: linear-gradient(135deg, var(--bg-light) 0%, #ffffff 100%);
            min-height: 100vh;
            color: var(--text-dark);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            padding: 30px;
            background: linear-gradient(135deg, var(--primary) 0%, #c2410c 100%);
            border-radius: 16px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content {
            text-align: left;
        }

        .header h1 {
            color: white;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.9);
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
            color: var(--text-dark);
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

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 24px;
            border: 2px solid var(--primary);
            background: white;
            color: var(--primary);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            background: var(--primary-light);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-content {
            display: none;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .tab-content.active {
            display: block;
        }

        .accordion {
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .accordion-header {
            padding: 15px 20px;
            background: var(--bg-light);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .accordion-header:hover {
            background: var(--primary-light);
        }

        .accordion-header .arrow {
            transition: transform 0.3s ease;
        }

        .accordion.open .accordion-header .arrow {
            transform: rotate(180deg);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .accordion.open .accordion-content {
            max-height: 2000px;
        }

        .question-group {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .question-group:last-child {
            border-bottom: none;
        }

        .question-label {
            display: block;
            margin-bottom: 12px;
            font-weight: 500;
            line-height: 1.5;
        }

        .likert-scale {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .likert-option {
            flex: 1;
            min-width: 60px;
        }

        .likert-option input {
            display: none;
        }

        .likert-option label {
            display: block;
            text-align: center;
            padding: 10px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .likert-option input:checked + label {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .likert-option label:hover {
            border-color: var(--primary);
        }

        .progress-container {
            margin-bottom: 30px;
        }

        .progress-bar {
            height: 8px;
            background: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            transition: width 0.3s ease;
            width: 0%;
        }

        .progress-text {
            text-align: center;
            margin-top: 8px;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--border-color);
            color: var(--text-dark);
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .teacher-section {
            padding: 20px;
            background: #fffbeb;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .teacher-section h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
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
            background: white;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .student-select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--bg-light);
            font-weight: 600;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-high {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-medium {
            background: #fef3c7;
            color: #d97706;
        }

        .status-low {
            background: #d1fae5;
            color: #059669;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        .hidden {
            display: none;
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
            }

            .likert-scale {
                flex-direction: column;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
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
                <h1>📒 오답노트 진단</h1>
                <p>오답노트 작성 및 활용 습관을 진단합니다</p>
            </div>
            <div class="nav-dropdown">
                <select id="pageNav" onchange="navigateToPage(this.value)">
                    <option value="">📑 페이지 이동</option>
                    <option value="chat03.php">📘 문제풀이 분석</option>
                    <option value="chat04.php" selected>📙 오답노트 분석</option>
                    <option value="chat05.php">📗 질의응답 분석</option>
                    <option value="chat06.php">📕 복습활동 분석</option>
                    <option value="chat_rules.php">📚 통합 규칙 뷰어</option>
                </select>
            </div>
        </div>

        <div id="alert-container"></div>

        <div class="tabs">
            <button class="tab-btn active" data-tab="student">📝 학생 대화</button>
            <?php if ($role !== 'student'): ?>
            <button class="tab-btn" data-tab="teacher">👨‍🏫 선생님 입력</button>
            <button class="tab-btn" data-tab="system">📊 시스템 데이터</button>
            <?php endif; ?>
        </div>

        <!-- 학생 탭 -->
        <div id="student-tab" class="tab-content active">
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text" id="progress-text">0 / 20 문항 완료</div>
            </div>

            <!-- S1: 오답노트 효능감 인식 -->
            <div class="accordion open">
                <div class="accordion-header">
                    <span>📌 S1: 오답노트 효능감 인식</span>
                    <span class="arrow">▼</span>
                </div>
                <div class="accordion-content">
                    <!-- Q1: uselessness_belief -->
                    <div class="question-group">
                        <label class="question-label">1. 오답노트를 써도 성적 향상에 별로 도움이 안 된다고 생각한다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s1_uselessness_belief" id="s1_q1_1" value="1">
                                <label for="s1_q1_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_uselessness_belief" id="s1_q1_2" value="2">
                                <label for="s1_q1_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_uselessness_belief" id="s1_q1_3" value="3">
                                <label for="s1_q1_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_uselessness_belief" id="s1_q1_4" value="4">
                                <label for="s1_q1_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_uselessness_belief" id="s1_q1_5" value="5">
                                <label for="s1_q1_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q2: time_waste_concern -->
                    <div class="question-group">
                        <label class="question-label">2. 오답노트 작성이 시간 낭비라고 느껴서 빠르게 넘기는 편이다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s1_time_waste_concern" id="s1_q2_1" value="1">
                                <label for="s1_q2_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_time_waste_concern" id="s1_q2_2" value="2">
                                <label for="s1_q2_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_time_waste_concern" id="s1_q2_3" value="3">
                                <label for="s1_q2_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_time_waste_concern" id="s1_q2_4" value="4">
                                <label for="s1_q2_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_time_waste_concern" id="s1_q2_5" value="5">
                                <label for="s1_q2_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q3: laziness_priority -->
                    <div class="question-group">
                        <label class="question-label">3. 오답노트 작성이 귀찮아서 대충 쓰거나 건너뛰는 경우가 많다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s1_laziness_priority" id="s1_q3_1" value="1">
                                <label for="s1_q3_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_laziness_priority" id="s1_q3_2" value="2">
                                <label for="s1_q3_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_laziness_priority" id="s1_q3_3" value="3">
                                <label for="s1_q3_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_laziness_priority" id="s1_q3_4" value="4">
                                <label for="s1_q3_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_laziness_priority" id="s1_q3_5" value="5">
                                <label for="s1_q3_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q4: perfectionism_burden -->
                    <div class="question-group">
                        <label class="question-label">4. 오답노트를 완벽하게 쓰려는 부담감 때문에 시작을 미루게 된다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s1_perfectionism_burden" id="s1_q4_1" value="1">
                                <label for="s1_q4_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_perfectionism_burden" id="s1_q4_2" value="2">
                                <label for="s1_q4_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_perfectionism_burden" id="s1_q4_3" value="3">
                                <label for="s1_q4_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_perfectionism_burden" id="s1_q4_4" value="4">
                                <label for="s1_q4_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s1_perfectionism_burden" id="s1_q4_5" value="5">
                                <label for="s1_q4_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- S2: 풀이노트 -->
            <div class="accordion">
                <div class="accordion-header">
                    <span>📝 S2: 풀이노트 작성</span>
                    <span class="arrow">▼</span>
                </div>
                <div class="accordion-content">
                    <!-- Q5: answer_copy_only -->
                    <div class="question-group">
                        <label class="question-label">5. 오답노트에 정답만 베끼고 풀이 과정을 분석하지 않는다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s2_answer_copy_only" id="s2_q1_1" value="1">
                                <label for="s2_q1_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_answer_copy_only" id="s2_q1_2" value="2">
                                <label for="s2_q1_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_answer_copy_only" id="s2_q1_3" value="3">
                                <label for="s2_q1_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_answer_copy_only" id="s2_q1_4" value="4">
                                <label for="s2_q1_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_answer_copy_only" id="s2_q1_5" value="5">
                                <label for="s2_q1_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q6: cause_not_analyzed -->
                    <div class="question-group">
                        <label class="question-label">6. 왜 틀렸는지 원인을 파악하지 않고 그냥 넘어가는 편이다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s2_cause_not_analyzed" id="s2_q2_1" value="1">
                                <label for="s2_q2_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_cause_not_analyzed" id="s2_q2_2" value="2">
                                <label for="s2_q2_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_cause_not_analyzed" id="s2_q2_3" value="3">
                                <label for="s2_q2_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_cause_not_analyzed" id="s2_q2_4" value="4">
                                <label for="s2_q2_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_cause_not_analyzed" id="s2_q2_5" value="5">
                                <label for="s2_q2_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q7: own_solution_ignored -->
                    <div class="question-group">
                        <label class="question-label">7. 나의 틀린 풀이를 분석하지 않고 정답 풀이만 본다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s2_own_solution_ignored" id="s2_q3_1" value="1">
                                <label for="s2_q3_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_own_solution_ignored" id="s2_q3_2" value="2">
                                <label for="s2_q3_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_own_solution_ignored" id="s2_q3_3" value="3">
                                <label for="s2_q3_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_own_solution_ignored" id="s2_q3_4" value="4">
                                <label for="s2_q3_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_own_solution_ignored" id="s2_q3_5" value="5">
                                <label for="s2_q3_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q8: no_retry -->
                    <div class="question-group">
                        <label class="question-label">8. 오답노트를 쓰고 나서 그 문제를 다시 풀어보지 않는다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s2_no_retry" id="s2_q4_1" value="1">
                                <label for="s2_q4_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_no_retry" id="s2_q4_2" value="2">
                                <label for="s2_q4_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_no_retry" id="s2_q4_3" value="3">
                                <label for="s2_q4_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_no_retry" id="s2_q4_4" value="4">
                                <label for="s2_q4_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s2_no_retry" id="s2_q4_5" value="5">
                                <label for="s2_q4_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- S3: 평가준비 -->
            <div class="accordion">
                <div class="accordion-header">
                    <span>📚 S3: 평가준비 활용</span>
                    <span class="arrow">▼</span>
                </div>
                <div class="accordion-content">
                    <!-- Q9: note_neglect -->
                    <div class="question-group">
                        <label class="question-label">9. 오답노트를 써놓고 시험 전에 다시 보지 않는다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s3_note_neglect" id="s3_q1_1" value="1">
                                <label for="s3_q1_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_note_neglect" id="s3_q1_2" value="2">
                                <label for="s3_q1_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_note_neglect" id="s3_q1_3" value="3">
                                <label for="s3_q1_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_note_neglect" id="s3_q1_4" value="4">
                                <label for="s3_q1_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_note_neglect" id="s3_q1_5" value="5">
                                <label for="s3_q1_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q10: new_problem_obsession -->
                    <div class="question-group">
                        <label class="question-label">10. 시험 전에 오답 복습보다 새로운 문제를 푸는 것을 선호한다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s3_new_problem_obsession" id="s3_q2_1" value="1">
                                <label for="s3_q2_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_new_problem_obsession" id="s3_q2_2" value="2">
                                <label for="s3_q2_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_new_problem_obsession" id="s3_q2_3" value="3">
                                <label for="s3_q2_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_new_problem_obsession" id="s3_q2_4" value="4">
                                <label for="s3_q2_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_new_problem_obsession" id="s3_q2_5" value="5">
                                <label for="s3_q2_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q11: key_point_missing -->
                    <div class="question-group">
                        <label class="question-label">11. 오답노트에서 시험에 나올 핵심 포인트를 따로 정리하지 않는다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s3_key_point_missing" id="s3_q3_1" value="1">
                                <label for="s3_q3_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_key_point_missing" id="s3_q3_2" value="2">
                                <label for="s3_q3_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_key_point_missing" id="s3_q3_3" value="3">
                                <label for="s3_q3_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_key_point_missing" id="s3_q3_4" value="4">
                                <label for="s3_q3_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s3_key_point_missing" id="s3_q3_5" value="5">
                                <label for="s3_q3_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- S4: 서술평가 -->
            <div class="accordion">
                <div class="accordion-header">
                    <span>✍️ S4: 서술평가 분석</span>
                    <span class="arrow">▼</span>
                </div>
                <div class="accordion-content">
                    <!-- Q12: essay_avoidance -->
                    <div class="question-group">
                        <label class="question-label">12. 서술형 오답은 분석이 어려워서 건너뛰는 편이다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s4_essay_avoidance" id="s4_q1_1" value="1">
                                <label for="s4_q1_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_essay_avoidance" id="s4_q1_2" value="2">
                                <label for="s4_q1_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_essay_avoidance" id="s4_q1_3" value="3">
                                <label for="s4_q1_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_essay_avoidance" id="s4_q1_4" value="4">
                                <label for="s4_q1_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_essay_avoidance" id="s4_q1_5" value="5">
                                <label for="s4_q1_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q13: logic_structure_ignored -->
                    <div class="question-group">
                        <label class="question-label">13. 서술형 답안의 논리적 흐름을 분석하지 않는다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s4_logic_structure_ignored" id="s4_q2_1" value="1">
                                <label for="s4_q2_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_logic_structure_ignored" id="s4_q2_2" value="2">
                                <label for="s4_q2_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_logic_structure_ignored" id="s4_q2_3" value="3">
                                <label for="s4_q2_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_logic_structure_ignored" id="s4_q2_4" value="4">
                                <label for="s4_q2_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_logic_structure_ignored" id="s4_q2_5" value="5">
                                <label for="s4_q2_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q14: partial_score_analysis_failure -->
                    <div class="question-group">
                        <label class="question-label">14. 서술형에서 어디서 감점됐는지 정확히 파악하지 못한다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s4_partial_score_analysis_failure" id="s4_q3_1" value="1">
                                <label for="s4_q3_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_partial_score_analysis_failure" id="s4_q3_2" value="2">
                                <label for="s4_q3_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_partial_score_analysis_failure" id="s4_q3_3" value="3">
                                <label for="s4_q3_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_partial_score_analysis_failure" id="s4_q3_4" value="4">
                                <label for="s4_q3_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_partial_score_analysis_failure" id="s4_q3_5" value="5">
                                <label for="s4_q3_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q15: model_answer_ignored -->
                    <div class="question-group">
                        <label class="question-label">15. 모범답안을 대충 훑어보고 제대로 분석하지 않는다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s4_model_answer_ignored" id="s4_q4_1" value="1">
                                <label for="s4_q4_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_model_answer_ignored" id="s4_q4_2" value="2">
                                <label for="s4_q4_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_model_answer_ignored" id="s4_q4_3" value="3">
                                <label for="s4_q4_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_model_answer_ignored" id="s4_q4_4" value="4">
                                <label for="s4_q4_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s4_model_answer_ignored" id="s4_q4_5" value="5">
                                <label for="s4_q4_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- S5: 반송 오답노트 대응 -->
            <div class="accordion">
                <div class="accordion-header">
                    <span>📮 S5: 반송 오답노트 대응</span>
                    <span class="arrow">▼</span>
                </div>
                <div class="accordion-content">
                    <!-- Q16: return_ignored -->
                    <div class="question-group">
                        <label class="question-label">16. 선생님이 반송한 오답노트를 바로 수정하지 않는다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s5_return_ignored" id="s5_q1_1" value="1">
                                <label for="s5_q1_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_return_ignored" id="s5_q1_2" value="2">
                                <label for="s5_q1_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_return_ignored" id="s5_q1_3" value="3">
                                <label for="s5_q1_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_return_ignored" id="s5_q1_4" value="4">
                                <label for="s5_q1_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_return_ignored" id="s5_q1_5" value="5">
                                <label for="s5_q1_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q17: feedback_misunderstanding -->
                    <div class="question-group">
                        <label class="question-label">17. 선생님의 피드백 내용을 이해하지 못할 때가 많다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s5_feedback_misunderstanding" id="s5_q2_1" value="1">
                                <label for="s5_q2_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_feedback_misunderstanding" id="s5_q2_2" value="2">
                                <label for="s5_q2_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_feedback_misunderstanding" id="s5_q2_3" value="3">
                                <label for="s5_q2_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_feedback_misunderstanding" id="s5_q2_4" value="4">
                                <label for="s5_q2_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_feedback_misunderstanding" id="s5_q2_5" value="5">
                                <label for="s5_q2_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q18: defensive_reaction -->
                    <div class="question-group">
                        <label class="question-label">18. 피드백을 받으면 방어적으로 반응하게 된다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s5_defensive_reaction" id="s5_q3_1" value="1">
                                <label for="s5_q3_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_defensive_reaction" id="s5_q3_2" value="2">
                                <label for="s5_q3_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_defensive_reaction" id="s5_q3_3" value="3">
                                <label for="s5_q3_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_defensive_reaction" id="s5_q3_4" value="4">
                                <label for="s5_q3_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_defensive_reaction" id="s5_q3_5" value="5">
                                <label for="s5_q3_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q19: repeated_same_mistakes -->
                    <div class="question-group">
                        <label class="question-label">19. 같은 피드백을 여러 번 받는데도 개선하지 못한다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s5_repeated_same_mistakes" id="s5_q4_1" value="1">
                                <label for="s5_q4_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_repeated_same_mistakes" id="s5_q4_2" value="2">
                                <label for="s5_q4_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_repeated_same_mistakes" id="s5_q4_3" value="3">
                                <label for="s5_q4_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_repeated_same_mistakes" id="s5_q4_4" value="4">
                                <label for="s5_q4_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_repeated_same_mistakes" id="s5_q4_5" value="5">
                                <label for="s5_q4_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>

                    <!-- Q20: partial_modification -->
                    <div class="question-group">
                        <label class="question-label">20. 피드백의 일부만 수정하고 나머지는 무시하는 편이다.</label>
                        <div class="likert-scale">
                            <div class="likert-option">
                                <input type="radio" name="s5_partial_modification" id="s5_q5_1" value="1">
                                <label for="s5_q5_1">전혀<br>아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_partial_modification" id="s5_q5_2" value="2">
                                <label for="s5_q5_2">아니다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_partial_modification" id="s5_q5_3" value="3">
                                <label for="s5_q5_3">보통</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_partial_modification" id="s5_q5_4" value="4">
                                <label for="s5_q5_4">그렇다</label>
                            </div>
                            <div class="likert-option">
                                <input type="radio" name="s5_partial_modification" id="s5_q5_5" value="5">
                                <label for="s5_q5_5">매우<br>그렇다</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button class="btn btn-primary" onclick="saveStudentResponse()">💾 저장하기</button>
            </div>
        </div>

        <?php if ($role !== 'student'): ?>
        <!-- 선생님 탭 -->
        <div id="teacher-tab" class="tab-content">
            <div class="teacher-section">
                <h3>👨‍🎓 학생 선택</h3>
                <select class="student-select" id="student-select" onchange="loadStudentData()">
                    <option value="">학생을 선택하세요</option>
                </select>
            </div>

            <!-- S1: 오답노트 효능감 인식 -->
            <div class="teacher-section">
                <h3>📌 S1: 오답노트 효능감 인식 패턴</h3>
                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_uselessness_belief" value="1">
                        오답노트 무용론 (uselessness_belief)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_time_waste_concern" value="1">
                        시간 낭비 인식 (time_waste_concern)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_laziness_priority" value="1">
                        귀찮음 우선 (laziness_priority)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_perfectionism_burden" value="1">
                        완벽주의 부담 (perfectionism_burden)
                    </label>
                </div>
            </div>

            <!-- S2: 풀이노트 -->
            <div class="teacher-section">
                <h3>📝 S2: 풀이노트 작성 패턴</h3>
                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_answer_copy_only" value="1">
                        정답만 베끼기 (answer_copy_only)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_cause_not_analyzed" value="1">
                        원인 미분석 (cause_not_analyzed)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_own_solution_ignored" value="1">
                        자기 풀이 무시 (own_solution_ignored)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_no_retry" value="1">
                        재풀이 안함 (no_retry)
                    </label>
                </div>
            </div>

            <!-- S3: 평가준비 -->
            <div class="teacher-section">
                <h3>📚 S3: 평가준비 활용 패턴</h3>
                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_note_neglect" value="1">
                        오답노트 방치 (note_neglect)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_new_problem_obsession" value="1">
                        새 문제 집착 (new_problem_obsession)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_key_point_missing" value="1">
                        핵심 요약 부재 (key_point_missing)
                    </label>
                </div>
            </div>

            <!-- S4: 서술평가 -->
            <div class="teacher-section">
                <h3>✍️ S4: 서술평가 분석 패턴</h3>
                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_essay_avoidance" value="1">
                        서술형 회피 (essay_avoidance)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_logic_structure_ignored" value="1">
                        논리구조 무시 (logic_structure_ignored)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_partial_score_analysis_failure" value="1">
                        감점 분석 실패 (partial_score_analysis_failure)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_model_answer_ignored" value="1">
                        모범답안 무시 (model_answer_ignored)
                    </label>
                </div>
            </div>

            <!-- S5: 반송 오답노트 대응 -->
            <div class="teacher-section">
                <h3>📮 S5: 반송 오답노트 대응 패턴</h3>
                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_return_ignored" value="1">
                        반송 무시 (return_ignored)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_feedback_misunderstanding" value="1">
                        피드백 미이해 (feedback_misunderstanding)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_defensive_reaction" value="1">
                        방어적 반응 (defensive_reaction)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_repeated_same_mistakes" value="1">
                        같은 실수 반복 (repeated_same_mistakes)
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="obs_partial_modification" value="1">
                        부분 수정만 (partial_modification)
                    </label>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button class="btn btn-primary" onclick="saveTeacherObservation()">💾 관찰 기록 저장</button>
            </div>
        </div>

        <!-- 시스템 데이터 탭 -->
        <div id="system-tab" class="tab-content">
            <h3 style="margin-bottom: 20px;">📊 저장된 데이터</h3>
            <div id="system-data-container">
                <p>학생을 선택하면 데이터가 표시됩니다.</p>
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
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                btn.classList.add('active');
                document.getElementById(btn.dataset.tab + '-tab').classList.add('active');
            });
        });

        // 아코디언 토글
        document.querySelectorAll('.accordion-header').forEach(header => {
            header.addEventListener('click', () => {
                header.parentElement.classList.toggle('open');
            });
        });

        // 진행률 업데이트
        function updateProgress() {
            const total = 20;
            const answered = document.querySelectorAll('#student-tab input[type="radio"]:checked').length;
            const percent = Math.round((answered / total) * 100);

            document.getElementById('progress-fill').style.width = percent + '%';
            document.getElementById('progress-text').textContent = answered + ' / ' + total + ' 문항 완료';
        }

        document.querySelectorAll('#student-tab input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', updateProgress);
        });

        // 학생 응답 저장
        function saveStudentResponse() {
            const responses = {};
            document.querySelectorAll('#student-tab input[type="radio"]:checked').forEach(radio => {
                responses[radio.name] = radio.value;
            });

            if (Object.keys(responses).length < 20) {
                showAlert('모든 문항에 응답해주세요.', 'error');
                return;
            }

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=save_student_response&' + new URLSearchParams({responses: JSON.stringify(responses)})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('저장되었습니다!', 'success');
                } else {
                    showAlert(data.error || '저장 실패', 'error');
                }
            })
            .catch(err => {
                showAlert('오류: ' + err.message + ' [chat04.php:JS]', 'error');
            });
        }

        // 선생님 관찰 기록 저장
        function saveTeacherObservation() {
            const studentId = document.getElementById('student-select').value;
            if (!studentId) {
                showAlert('학생을 선택해주세요.', 'error');
                return;
            }

            const observations = {};
            document.querySelectorAll('#teacher-tab input[type="checkbox"]:checked').forEach(cb => {
                observations[cb.name] = cb.value;
            });

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=save_teacher_observation&student_id=' + studentId +
                      '&observations=' + encodeURIComponent(JSON.stringify(observations))
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('관찰 기록이 저장되었습니다!', 'success');
                } else {
                    showAlert(data.error || '저장 실패', 'error');
                }
            })
            .catch(err => {
                showAlert('오류: ' + err.message + ' [chat04.php:JS]', 'error');
            });
        }

        // 학생 목록 로드
        function loadStudents() {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_students'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('student-select');
                    if (select) {
                        data.students.forEach(s => {
                            const opt = document.createElement('option');
                            opt.value = s.id;
                            opt.textContent = s.lastname + ' ' + s.firstname;
                            select.appendChild(opt);
                        });
                    }
                }
            });
        }

        // 학생 데이터 로드
        function loadStudentData() {
            const studentId = document.getElementById('student-select').value;
            if (!studentId) return;

            // 학생 응답 데이터
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=load_data&data_type=student_wrong_answer_note&target_user=' + studentId
            })
            .then(res => res.json())
            .then(data => {
                displaySystemData(data, 'student');
            });

            // 선생님 관찰 기록
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=load_data&data_type=teacher_wrong_answer_note&target_user=' + studentId
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.observations) {
                    document.querySelectorAll('#teacher-tab input[type="checkbox"]').forEach(cb => {
                        cb.checked = data.data.observations[cb.name] ? true : false;
                    });
                }
            });
        }

        // 시스템 데이터 표시
        function displaySystemData(data, type) {
            const container = document.getElementById('system-data-container');
            if (!data.success) {
                container.innerHTML = '<p>데이터가 없습니다.</p>';
                return;
            }

            let html = '<table class="data-table"><thead><tr><th>항목</th><th>응답</th><th>상태</th></tr></thead><tbody>';

            const labels = {
                's1_uselessness_belief': 'S1-1: 오답노트 무용론',
                's1_time_waste_concern': 'S1-2: 시간 낭비 인식',
                's1_laziness_priority': 'S1-3: 귀찮음 우선',
                's1_perfectionism_burden': 'S1-4: 완벽주의 부담',
                's2_answer_copy_only': 'S2-1: 정답만 베끼기',
                's2_cause_not_analyzed': 'S2-2: 원인 미분석',
                's2_own_solution_ignored': 'S2-3: 자기 풀이 무시',
                's2_no_retry': 'S2-4: 재풀이 안함',
                's3_note_neglect': 'S3-1: 오답노트 방치',
                's3_new_problem_obsession': 'S3-2: 새 문제 집착',
                's3_key_point_missing': 'S3-3: 핵심 요약 부재',
                's4_essay_avoidance': 'S4-1: 서술형 회피',
                's4_logic_structure_ignored': 'S4-2: 논리구조 무시',
                's4_partial_score_analysis_failure': 'S4-3: 감점 분석 실패',
                's4_model_answer_ignored': 'S4-4: 모범답안 무시',
                's5_return_ignored': 'S5-1: 반송 무시',
                's5_feedback_misunderstanding': 'S5-2: 피드백 미이해',
                's5_defensive_reaction': 'S5-3: 방어적 반응',
                's5_repeated_same_mistakes': 'S5-4: 같은 실수 반복',
                's5_partial_modification': 'S5-5: 부분 수정만'
            };

            const responses = data.data.responses || {};
            for (const [key, label] of Object.entries(labels)) {
                const value = responses[key] || '-';
                let status = '';
                if (value >= 4) {
                    status = '<span class="status-badge status-high">주의</span>';
                } else if (value >= 3) {
                    status = '<span class="status-badge status-medium">관찰</span>';
                } else if (value !== '-') {
                    status = '<span class="status-badge status-low">양호</span>';
                }
                html += '<tr><td>' + label + '</td><td>' + value + '</td><td>' + status + '</td></tr>';
            }

            html += '</tbody></table>';
            html += '<p style="margin-top: 15px; color: #6b7280;">저장 시간: ' +
                    new Date(data.timecreated * 1000).toLocaleString() + '</p>';

            container.innerHTML = html;
        }

        // 알림 표시
        function showAlert(message, type) {
            const container = document.getElementById('alert-container');
            container.innerHTML = '<div class="alert alert-' + type + '">' + message + '</div>';
            setTimeout(() => container.innerHTML = '', 3000);
        }

        // 초기화
        document.addEventListener('DOMContentLoaded', () => {
            updateProgress();
            if (document.getElementById('student-select')) {
                loadStudents();
            }

            // 기존 데이터 로드 (학생인 경우)
            <?php if ($role === 'student'): ?>
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=load_data&data_type=student_wrong_answer_note'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.responses) {
                    for (const [name, value] of Object.entries(data.data.responses)) {
                        const radio = document.querySelector('input[name="' + name + '"][value="' + value + '"]');
                        if (radio) radio.checked = true;
                    }
                    updateProgress();
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
