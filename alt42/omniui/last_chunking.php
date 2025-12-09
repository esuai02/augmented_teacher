<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// URL 파라미터에서 userid 가져오기
$userid = optional_param('userid', 0, PARAM_INT);

// userid가 없으면 현재 로그인한 사용자 ID 사용
if ($userid == 0) {
    $userid = $USER->id;
}  

// 사용자 정보 가져오기
$user = $DB->get_record('user', array('id' => $userid));
if (!$user) {
    print_error('User not found');
}

// 사용자의 시험 정보 가져오기
require_once(__DIR__ . '/get_exam_data_alt42t.php');
$exam_info = getExamDataFromAlt42t($userid);

// 시험까지 남은 일수 계산 (수학 시험일 기준)
$daysLeft = 5; // 기본값
if ($exam_info && $exam_info->math_exam_date) {
    $today = new DateTime();
    $examDate = new DateTime($exam_info->math_exam_date);
    $interval = $today->diff($examDate);
    $daysLeft = $interval->days;
    if ($interval->invert) { // 시험일이 지났으면
        $daysLeft = 0;
    }
}

// D-Day가 5일 이상인 경우 처리 (제한하지 않음)
$isOverD5 = false;
if ($daysLeft > 5) {
    $isOverD5 = true;
    // $daysLeft는 실제 남은 일수 그대로 유지
}

// 현재 단계 결정 (1-7단계, D-5 이상은 모두 단계 1)
$currentPhase = 1;
if ($daysLeft > 5) {
    $currentPhase = 0; // D-5 이상 (자유 계획 모드)
} else if ($daysLeft == 5) {
    $currentPhase = 1; // D-5
} else if ($daysLeft == 4) {
    $currentPhase = 2; // D-4
} else if ($daysLeft == 3) {
    $currentPhase = 3; // D-3
} else if ($daysLeft == 2) {
    $currentPhase = 4; // D-2
} else if ($daysLeft == 1) {
    $currentPhase = 5; // D-1
} else {
    $currentPhase = 6; // D-Day
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>라스트 청킹 - 시험 최적화 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2d3748;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* 헤더 - 항상 상단 고정 */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.2);
            z-index: 100;
            padding: 15px 0;
            transition: all 0.3s ease;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5em;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 0.9em;
            color: #4a5568;
            flex: 1;
            justify-content: space-between;
        }
        
        .header-center {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: 20px;
        }

        .d-day {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        /* 인트로 애니메이션 */
        .intro-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            transition: opacity 0.5s ease;
        }

        .intro-overlay.hide {
            opacity: 0;
            pointer-events: none;
        }

        .typing-text {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 20px;
            min-height: 2.2em;
        }

        .typing-subtext {
            font-size: 1.1em;
            opacity: 0.9;
            min-height: 1.5em;
        }

        /* 메인 컨테이너 */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 40px;
            min-height: 100vh;
        }

        /* 단계별 디자인 테마 */
        .theme-preparation {
            --primary-color: #4299e1;
            --secondary-color: #63b3ed;
            --bg-color: rgba(235, 248, 255, 0.9);
            --accent-color: #2b6cb0;
        }

        .theme-focus {
            --primary-color: #ed8936;
            --secondary-color: #f6ad55;
            --bg-color: rgba(255, 245, 231, 0.9);
            --accent-color: #c05621;
        }

        .theme-final {
            --primary-color: #38a169;
            --secondary-color: #68d391;
            --bg-color: rgba(240, 255, 244, 0.9);
            --accent-color: #22543d;
        }

        /* 대시보드 */
        .dashboard {
            background: var(--bg-color, rgba(255,255,255,0.95));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.5s ease;
        }
        
        .dashboard.collapsed {
            padding-bottom: 15px;
        }
        
        .dashboard.collapsed .progress-container {
            display: none;
        }

        .dashboard-title {
            font-size: 2.2em;
            font-weight: 700;
            color: var(--primary-color, #4299e1);
            margin-bottom: 10px;
            text-align: center;
            cursor: pointer;
            user-select: none;
            position: relative;
        }
        
        .dashboard-title::after {
            content: '▼';
            font-size: 0.6em;
            margin-left: 15px;
            opacity: 0.6;
            transition: transform 0.3s ease;
            display: inline-block;
        }
        
        .dashboard-title:hover {
            color: var(--accent-color, #2b6cb0);
        }
        
        .dashboard.collapsed .dashboard-title::after {
            transform: rotate(-90deg);
        }

        .dashboard-subtitle {
            text-align: center;
            color: #4a5568;
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        
        .dashboard.collapsed .dashboard-subtitle {
            margin-bottom: 10px;
        }

        /* 진행 상태 */
        .progress-container {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 40px;
            align-items: center;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .progress-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        .progress-circle {
            width: 140px;
            height: 140px;
            position: relative;
            margin: 0 auto;
        }

        .progress-svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .progress-bg {
            fill: none;
            stroke: rgba(255,255,255,0.3);
            stroke-width: 8;
        }

        .progress-bar {
            fill: none;
            stroke: var(--primary-color, #4299e1);
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease;
            filter: drop-shadow(0 0 6px var(--primary-color, #4299e1));
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color, #4299e1);
        }

        .progress-info {
            text-align: left;
        }

        .progress-phase {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--accent-color, #2b6cb0);
            margin-bottom: 10px;
        }

        .progress-description {
            font-size: 1.2em;
            color: #4a5568;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background: rgba(255,255,255,0.7);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--primary-color, #4299e1);
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 1.6em;
            font-weight: bold;
            color: var(--primary-color, #4299e1);
        }

        .stat-label {
            font-size: 0.9em;
            color: #4a5568;
            margin-top: 4px;
        }

        /* 액션 영역 */
        .action-section {
            background: rgba(255,255,255,0.9);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .action-section.collapsed {
            padding-bottom: 20px;
        }
        
        .action-section.collapsed .action-grid {
            display: none;
        }
        
        .action-section.collapsed .section-title {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.4em;
            font-weight: 600;
            color: var(--accent-color, #2b6cb0);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
            position: relative;
        }
        
        .section-title.collapsible::after {
            content: '▼';
            position: absolute;
            right: 0;
            transition: transform 0.3s ease;
            font-size: 0.8em;
            opacity: 0.6;
        }
        
        .section-title:hover {
            color: var(--primary-color, #4299e1);
        }
        
        .section-title.collapsed::after {
            transform: rotate(-90deg);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: var(--bg-color, rgba(235, 248, 255, 0.5));
            border: 2px solid var(--secondary-color, #63b3ed);
            border-radius: 12px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
            border-color: var(--primary-color, #4299e1);
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color, #4299e1), var(--secondary-color, #63b3ed));
        }

        .card-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            display: block;
        }

        .card-title {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--accent-color, #2b6cb0);
            margin-bottom: 10px;
        }

        .card-description {
            font-size: 0.95em;
            color: #4a5568;
            line-height: 1.5;
        }

        .card-progress {
            margin-top: 15px;
            font-size: 0.85em;
            color: var(--primary-color, #4299e1);
            font-weight: 600;
        }

        /* 체크리스트 */
        .checklist-container {
            background: rgba(255,255,255,0.9);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .checklist {
            list-style: none;
            padding: 0;
        }

        .checklist-item {
            display: flex;
            align-items: flex-start;
            padding: 16px;
            margin-bottom: 12px;
            background: rgba(255,255,255,0.7);
            border-radius: 12px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checklist-item:hover {
            border-color: var(--primary-color, #4299e1);
            background: rgba(255,255,255,0.9);
        }

        .checklist-item.completed {
            background: var(--bg-color, rgba(235, 248, 255, 0.7));
            border-color: var(--secondary-color, #63b3ed);
        }

        .checklist-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            margin-top: 2px;
            accent-color: var(--primary-color, #4299e1);
            cursor: pointer;
        }

        .checklist-item label {
            flex: 1;
            cursor: pointer;
            line-height: 1.5;
        }

        .checklist-item.completed label {
            text-decoration: line-through;
            opacity: 0.7;
        }

        /* 모티베이션 메시지 */
        .motivation-banner {
            background: linear-gradient(135deg, var(--primary-color, #4299e1), var(--secondary-color, #63b3ed));
            color: white;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }

        .motivation-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s infinite;
        }

        .motivation-text {
            font-size: 1.3em;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        /* 단계 전환 버튼 */
        .phase-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .phase-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.9);
            color: #667eea;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .phase-btn:hover {
            transform: scale(1.1);
            background: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .date-display {
            background: rgba(255,255,255,0.9);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            min-width: 120px;
            text-align: center;
        }
        
        .date-display:hover {
            border-color: #667eea;
            transform: translateY(-1px);
        }

        /* 애니메이션 */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.6s ease forwards;
        }

        /* 모달 스타일 */
        .concept-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .concept-modal.show {
            display: flex;
        }

        .concept-modal-content {
            background: white;
            border-radius: 20px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideUp 0.4s ease;
        }

        .concept-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 2px solid #e2e8f0;
            background: linear-gradient(135deg, #4299e1, #63b3ed);
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .concept-modal-header h2 {
            margin: 0;
            font-size: 1.5em;
            font-weight: 700;
        }

        .close-concept-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-concept-modal:hover {
            background: rgba(255,255,255,0.2);
        }

        .concept-modal-body {
            padding: 30px;
        }

        .concept-intro {
            background: #f7fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 4px solid #4299e1;
        }

        .concept-intro p {
            margin: 0;
            font-size: 1.1em;
            line-height: 1.7;
            color: #2d3748;
        }

        .concept-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 600px) {
            .concept-comparison {
                grid-template-columns: 1fr;
            }
        }

        .wrong-method, .right-method {
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .wrong-method {
            background: #fed7d7;
            border: 2px solid #feb2b2;
        }

        .right-method {
            background: #c6f6d5;
            border: 2px solid #9ae6b4;
        }

        .wrong-method h3, .right-method h3 {
            margin: 0 0 10px 0;
            font-size: 1.2em;
        }

        .wrong-method p, .right-method p {
            font-weight: 600;
            margin: 0 0 15px 0;
        }

        .wrong-method ul, .right-method ul {
            text-align: left;
            margin: 15px 0;
            padding-left: 20px;
        }

        .wrong-method li, .right-method li {
            margin-bottom: 8px;
        }

        .result {
            font-style: italic;
            font-weight: 600 !important;
            color: #2d3748;
        }

        .concept-principle {
            margin-bottom: 25px;
        }

        .concept-principle h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .principle-box {
            background: linear-gradient(135deg, #fef5e7, #fed7aa);
            border: 2px solid #f6ad55;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-size: 1.1em;
            line-height: 1.7;
        }

        .principle-box strong {
            color: #c05621;
            font-size: 1.2em;
            display: block;
            margin-bottom: 10px;
        }

        .concept-evidence {
            margin-bottom: 25px;
        }

        .concept-evidence h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .concept-evidence ul {
            background: #f0fff4;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #48bb78;
        }

        .concept-evidence li {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .source {
            font-size: 0.9em;
            color: #4a5568;
            font-style: italic;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .concept-stages h3 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 1.3em;
        }

        .stage-timeline {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .stage-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .stage-prep {
            background: rgba(235, 248, 255, 0.8);
            border: 2px solid #63b3ed;
        }

        .stage-focus {
            background: rgba(255, 245, 231, 0.8);
            border: 2px solid #f6ad55;
        }

        .stage-final {
            background: rgba(240, 255, 244, 0.8);
            border: 2px solid #68d391;
        }

        .stage-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color, #4299e1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .stage-prep .stage-number {
            background: #4299e1;
        }

        .stage-focus .stage-number {
            background: #ed8936;
        }

        .stage-final .stage-number {
            background: #38a169;
        }

        .stage-content h4 {
            margin: 0 0 8px 0;
            color: #2d3748;
            font-size: 1.1em;
        }

        .stage-content p {
            margin: 0;
            color: #4a5568;
            line-height: 1.5;
        }

        /* 홈 버튼 추가 */
        .home-button {
            position: fixed;
            top: 90px;
            left: 30px;
            background: white;
            color: var(--primary-color, #4299e1);
            border: 2px solid var(--primary-color, #4299e1);
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            z-index: 100;
        }

        .home-button:hover {
            background: var(--primary-color, #4299e1);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .container {
                padding: 90px 15px 40px;
            }
            
            .dashboard {
                padding: 20px;
            }
            
            .dashboard-title {
                font-size: 1.8em;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .phase-controls {
                bottom: 20px;
                right: 20px;
            }

            .concept-modal-content {
                margin: 10px;
                max-height: 95vh;
            }

            .concept-modal-body {
                padding: 20px;
            }

            .home-button {
                top: 80px;
                left: 15px;
                padding: 8px 16px;
                font-size: 0.9em;
            }
        }

        /* 할 일 추가 버튼 */
        .add-task-btn {
            background: var(--primary-color, #4299e1);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            margin-left: auto;
            transition: all 0.3s ease;
        }
        
        .add-task-btn:hover {
            background: var(--accent-color, #2b6cb0);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        /* 태스크 편집/삭제 버튼 */
        .task-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        .task-edit-btn, .task-delete-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            opacity: 0.6;
            transition: all 0.2s ease;
        }
        
        .task-edit-btn:hover {
            opacity: 1;
            background: #4299e1;
            color: white;
        }
        
        .task-delete-btn:hover {
            opacity: 1;
            background: #f56565;
            color: white;
        }
        
        /* 태스크 추가 모달 */
        .task-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .task-modal.show {
            display: flex;
        }
        
        .task-modal-content {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideUp 0.4s ease;
        }
        
        .task-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .task-modal-header h3 {
            margin: 0;
            font-size: 1.3em;
            color: #2d3748;
        }
        
        .task-modal-body {
            padding: 25px;
        }
        
        .task-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        .task-input:focus {
            outline: none;
            border-color: var(--primary-color, #4299e1);
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        
        .task-modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding: 0 25px 25px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .modal-btn-cancel {
            background: #f3f4f6;
            color: #4a5568;
        }
        
        .modal-btn-cancel:hover {
            background: #e5e7eb;
        }
        
        .modal-btn-save {
            background: var(--primary-color, #4299e1);
            color: white;
        }
        
        .modal-btn-save:hover {
            background: var(--accent-color, #2b6cb0);
        }
        
        /* 헤더 중앙 날짜 표시 */
        .header-center {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
        
        .phase-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(0,0,0,0.05);
            padding: 6px 12px;
            border-radius: 30px;
        }
        
        .phase-btn {
            background: none;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: #4a5568;
        }
        
        .phase-btn:hover {
            background: rgba(0,0,0,0.1);
            transform: scale(1.1);
        }
        
        .date-display {
            padding: 10px 20px;
            background: white;
            border-radius: 25px;
            font-weight: 600;
            color: #2d3748;
            cursor: pointer;
            min-width: 140px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        .date-display::before {
            content: '📅';
            font-size: 1.1em;
        }
        
        .date-display:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transform: translateY(-1px);
            background: #f7fafc;
        }
        
        /* 날짜 선택 모달 */
        .date-picker-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .date-picker-modal.show {
            display: flex;
        }
        
        .date-picker-content {
            background: white;
            border-radius: 16px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideUp 0.4s ease;
        }
        
        .date-picker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .date-picker-header h3 {
            margin: 0;
            font-size: 1.3em;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .date-picker-header h3::before {
            content: '📅';
            font-size: 1.2em;
        }
        
        .date-picker-body {
            padding: 25px;
        }
        
        .date-input-group {
            margin-bottom: 20px;
        }
        
        .date-input-label {
            display: block;
            font-size: 0.9em;
            color: #4a5568;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .date-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        .date-input:focus {
            outline: none;
            border-color: var(--primary-color, #4299e1);
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        
        .quick-dates {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .quick-date-btn {
            padding: 8px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            background: white;
            color: #4a5568;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quick-date-btn:hover {
            border-color: var(--primary-color, #4299e1);
            color: var(--primary-color, #4299e1);
            background: rgba(66, 153, 225, 0.05);
        }
        
        /* 추가 애니메이션 */
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        @keyframes modalSlideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="theme-preparation" id="mainBody">
    <!-- 홈 버튼 추가 -->
    <a href="exam_preparation_system.php?userid=<?php echo $userid; ?>" class="home-button">← 홈으로</a>

    <!-- 인트로 오버레이 -->
    <div class="intro-overlay" id="introOverlay">
        <div class="typing-text" id="typingText"></div>
        <div class="typing-subtext" id="typingSubtext"></div>
    </div>

    <!-- 고정 헤더 -->
    <header class="header">
        <div class="header-content">
            <a href="#" class="logo">📚 라스트 청킹</a>
            <div class="header-info">
                <div class="header-center">
                    <!-- 날짜 선택 컨트롤 -->
                    <div class="phase-controls">
                        <div class="date-display" id="currentDateDisplay" onclick="showDatePicker()">
                            <span id="dateDisplayText">오늘</span>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <?php if ($exam_info && $exam_info->math_exam_date): ?>
                    <span>수학 시험: <?php echo date('m월 d일', strtotime($exam_info->math_exam_date)); ?></span>
                    <?php endif; ?>
                    <span class="d-day" id="headerDDay">D-<?php echo $daysLeft; ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- 대시보드 -->
        <section class="dashboard animate-in" id="dashboardSection">
            <h1 class="dashboard-title" id="dashboardTitle" onclick="toggleSection('dashboardSection')">시험 준비 최적화</h1>
            <p class="dashboard-subtitle" id="dashboardSubtitle">체계적인 라스트 청킹으로 기억을 재배선하세요</p>
            
            <div class="progress-container">
                <div class="progress-circle">
                    <svg class="progress-svg" viewBox="0 0 120 120">
                        <circle class="progress-bg" cx="60" cy="60" r="54"/>
                        <circle class="progress-bar" cx="60" cy="60" r="54" 
                               stroke-dasharray="339.29" stroke-dashoffset="339.29" id="progressCircle"/>
                    </svg>
                    <div class="progress-text" id="progressText">0%</div>
                </div>
                
                <div class="progress-info">
                    <div class="progress-phase" id="currentPhase">📊 전체 현황 파악</div>
                    <div class="progress-description" id="phaseDescription">
                        시험 범위와 일정을 정리하고 전체적인 계획을 세우는 단계입니다.
                    </div>
                    <div class="quick-stats">
                        <div class="stat-card">
                            <div class="stat-number" id="completedTasks">0</div>
                            <div class="stat-label">완료</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="totalTasks">12</div>
                            <div class="stat-label">전체</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="daysLeft"><?php echo $daysLeft; ?></div>
                            <div class="stat-label">남은 일수</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="streakDays">0</div>
                            <div class="stat-label">연속 실행</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 액션 카드 -->
        <section class="action-section animate-in" id="actionSection" style="animation-delay: 0.2s">
            <h2 class="section-title collapsible" onclick="toggleSection('actionSection')">
                <span>🎯</span>
                <span id="actionSectionTitle">오늘의 우선순위</span>
            </h2>
            <div class="action-grid" id="actionGrid">
                <!-- 동적으로 생성됨 -->
            </div>
        </section>

        <!-- 체크리스트 -->
        <section class="checklist-container animate-in" style="animation-delay: 0.4s">
            <h2 class="section-title">
                <span>✅</span>
                <span>실행 체크리스트</span>
                <button class="add-task-btn" onclick="showAddTaskModal()" title="할 일 추가">
                    <span>+ 할 일 추가</span>
                </button>
            </h2>
            <ul class="checklist" id="mainChecklist">
                <!-- 동적으로 생성됨 -->
            </ul>
        </section>

        <!-- 모티베이션 배너 -->
        <div class="motivation-banner animate-in" style="animation-delay: 0.6s">
            <div class="motivation-text" id="motivationText">
                지금 이 순간이 당신의 기억을 재배선할 최적의 시간입니다! 💪
            </div>
        </div>
    </div>



    <!-- 태스크 추가/수정 모달 -->
    <div class="task-modal" id="taskModal">
        <div class="task-modal-content">
            <div class="task-modal-header">
                <h3 id="taskModalTitle">할 일 추가</h3>
                <button class="close-concept-modal" onclick="closeTaskModal()">✕</button>
            </div>
            <div class="task-modal-body">
                <input type="text" class="task-input" id="taskInput" 
                       placeholder="할 일을 입력하세요" maxlength="100"
                       onkeypress="if(event.key==='Enter') saveTask()">
            </div>
            <div class="task-modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeTaskModal()">취소</button>
                <button class="modal-btn modal-btn-save" onclick="saveTask()">저장</button>
            </div>
        </div>
    </div>

    <!-- 날짜 선택 모달 -->
    <div class="date-picker-modal" id="datePickerModal">
        <div class="date-picker-content">
            <div class="date-picker-header">
                <h3>날짜 선택</h3>
                <button class="close-concept-modal" onclick="closeDatePicker()">✕</button>
            </div>
            <div class="date-picker-body">
                <div class="quick-dates">
                    <button class="quick-date-btn" onclick="selectQuickDate(-2)">그제</button>
                    <button class="quick-date-btn" onclick="selectQuickDate(-1)">어제</button>
                    <button class="quick-date-btn" onclick="selectQuickDate(0)">오늘</button>
                    <button class="quick-date-btn" onclick="selectQuickDate(1)">내일</button>
                    <button class="quick-date-btn" onclick="selectQuickDate(2)">모레</button>
                </div>
                
                <div class="date-input-group">
                    <label class="date-input-label">날짜 선택</label>
                    <input type="date" class="date-input" id="dateInput" 
                           onchange="selectCustomDate(this.value)">
                </div>
                
                <div class="date-input-group">
                    <label class="date-input-label">선택한 날짜</label>
                    <div style="padding: 12px 16px; background: #f3f4f6; border-radius: 8px; font-weight: 500;">
                        <span id="selectedDateDisplay">오늘</span>
                    </div>
                </div>
            </div>
            <div class="task-modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeDatePicker()">취소</button>
                <button class="modal-btn modal-btn-save" onclick="confirmDateSelection()">이동</button>
            </div>
        </div>
    </div>

    <script>
        // PHP에서 전달받은 데이터
        const phpData = {
            userid: <?php echo $userid; ?>,
            currentPhase: <?php echo $currentPhase; ?>,
            daysLeft: <?php echo $daysLeft; ?>,
            username: '<?php echo addslashes($user->firstname . ' ' . $user->lastname); ?>',
            school: '<?php echo addslashes($exam_info->school ?? ''); ?>',
            grade: '<?php echo addslashes($exam_info->grade ?? ''); ?>',
            examType: '<?php echo addslashes($exam_info->exam_type ?? ''); ?>',
            mathExamDate: '<?php echo addslashes($exam_info->math_exam_date ?? ''); ?>'
        };

        // 전역 상태
        let currentPhase = phpData.currentPhase;
        let userData = {
            userid: phpData.userid,
            currentDay: phpData.daysLeft,
            completedTasks: 0,
            totalTasks: 0,
            streakDays: 0,
            dailyProgress: {},
            startDate: null,
            customTasks: {}, // 사용자 정의 태스크 저장 - 날짜별로 저장됨
            sectionStates: {}, // 섹션 펼침/접힘 상태 저장
            selectedDate: null // 현재 선택된 날짜
        };
        let editingTaskIndex = -1; // 편집 중인 태스크 인덱스

        // 단계별 설정 (D-5 이상부터 D-Day까지)
        const phaseConfig = {
            0: { // D-5 이상 (자유 계획 모드)
                theme: 'theme-preparation',
                title: '시험 준비 계획',
                subtitle: '충분한 시간이 있습니다. 체계적인 계획을 세워보세요',
                phase: '📅 장기 계획 수립',
                description: '시험까지 충분한 시간이 있으니 꼼꼼한 준비 계획을 세우는 단계입니다.',
                headerText: 'D-' + phpData.daysLeft,
                dDay: phpData.daysLeft,
                actions: [
                    {
                        icon: '📚',
                        title: '전체 범위 파악',
                        description: '시험 범위를 전체적으로 파악하고 학습량을 계산합니다.',
                        progress: '범위 파악 중'
                    },
                    {
                        icon: '📊',
                        title: '진도 계획 수립',
                        description: '남은 기간 동안의 일별/주별 학습 계획을 세웁니다.',
                        progress: '계획 수립 중'
                    },
                    {
                        icon: '📝',
                        title: '약점 분석',
                        description: '현재 실력을 점검하고 보완이 필요한 부분을 파악합니다.',
                        progress: '분석 중'
                    },
                    {
                        icon: '🎯',
                        title: '목표 설정',
                        description: '구체적이고 달성 가능한 목표를 설정합니다.',
                        progress: '목표 설정 중'
                    }
                ],
                checklist: [
                    // 사용자가 직접 추가할 수 있는 빈 체크리스트
                ],
                motivation: [
                    '시간이 충분합니다! 체계적으로 준비하세요! 📚',
                    '꾸준한 노력이 최고의 결과를 만듭니다! 💪',
                    '매일 조금씩, 꾸준히 전진하세요! 🚀'
                ],
                allowCustomTasks: true // 사용자 정의 태스크 허용
            },
            1: { // D-5
                theme: 'theme-preparation',
                title: '전체 현황 파악',
                subtitle: '시험 전체를 조감하고 기본 정보를 정리하세요',
                phase: '📊 시험 정보 수집 및 분석',
                description: '시험 범위, 일정, 출제 경향 등 기본 정보를 체계적으로 파악하는 단계입니다.',
                headerText: 'D-5',
                dDay: 5,
                actions: [
                    {
                        icon: '📋',
                        title: '시험 정보 정리',
                        description: '시험 범위, 출제 경향, 배점 등 기본 정보를 체계적으로 정리합니다.',
                        progress: '정보 수집 중'
                    },
                    {
                        icon: '📚',
                        title: '기출문제 수집',
                        description: '최근 3년간 기출문제를 수집하고 대략적으로 훑어봅니다.',
                        progress: '자료 수집 중'
                    },
                    {
                        icon: '📝',
                        title: '전체 계획 수립',
                        description: '5일간의 전체적인 학습 계획을 거시적으로 세웁니다.',
                        progress: '계획 수립 중'
                    },
                    {
                        icon: '🎯',
                        title: '목표 설정',
                        description: '현실적이고 구체적인 목표 점수를 설정합니다.',
                        progress: '목표 설정 중'
                    }
                ],
                checklist: [
                    '시험 일정과 시간표 확인',
                    '과목별 출제 범위 리스트업',
                    '기출문제 3년치 다운로드',
                    '전체 학습량 파악',
                    '공부 환경 점검 및 정비',
                    '필요한 교재와 도구 준비'
                ],
                motivation: [
                    '체계적인 정보 수집이 성공의 첫걸음입니다! 📊',
                    '지금 투자하는 시간이 나중에 큰 효과를 낼 거예요! 💡',
                    '전체를 파악하면 불안감이 줄어들어요! 🎯'
                ],
                allowCustomTasks: true
            },
            2: { // D-4
                theme: 'theme-preparation',
                title: '기출 패턴 분석',
                subtitle: '출제 경향을 파악하여 효율적인 학습 방향을 설정하세요',
                phase: '🔍 출제 경향 및 패턴 분석',
                description: '기출문제를 분석하여 자주 나오는 유형과 중요 포인트를 파악하는 단계입니다.',
                headerText: 'D-4',
                dDay: 4,
                actions: [
                    {
                        icon: '🔍',
                        title: '기출문제 분석',
                        description: '최근 3년간 기출문제의 패턴과 빈출 유형을 세밀하게 분석합니다.',
                        progress: '패턴 분석 중'
                    },
                    {
                        icon: '📈',
                        title: '출제 빈도 조사',
                        description: '단원별, 유형별 출제 빈도를 조사하여 우선순위를 정합니다.',
                        progress: '빈도 분석 중'
                    },
                    {
                        icon: '📌',
                        title: '핵심 포인트 추출',
                        description: '반드시 알아야 할 핵심 개념과 공식을 추출합니다.',
                        progress: '핵심 추출 중'
                    },
                    {
                        icon: '🗂️',
                        title: '자료 체계화',
                        description: '분석한 내용을 바탕으로 학습 자료를 체계적으로 정리합니다.',
                        progress: '자료 정리 중'
                    }
                ],
                checklist: [
                    '기출문제 유형별 분류',
                    '출제 빈도가 높은 단원 표시',
                    '자주 틀리는 유형 파악',
                    '중요 공식과 개념 리스트업',
                    '학습 우선순위 설정',
                    '취약 부분 식별'
                ],
                motivation: [
                    '패턴을 알면 공부가 훨씬 효율적이 돼요! 🔍',
                    '출제자의 의도를 파악하는 중이에요! 🎯',
                    '이제 무엇을 공부해야 할지 명확해졌어요! 📈'
                ],
                allowCustomTasks: true
            },
            3: { // D-3
                theme: 'theme-preparation',
                title: '핵심 내용 정리',
                subtitle: '중요한 개념들을 압축하여 빠르게 인출할 수 있도록 준비하세요',
                phase: '📝 핵심 개념 압축 및 정리',
                description: '가장 중요한 내용들을 한눈에 볼 수 있도록 압축 정리하는 단계입니다.',
                headerText: 'D-3',
                dDay: 3,
                actions: [
                    {
                        icon: '📝',
                        title: '핵심 개념 요약',
                        description: '각 단원의 핵심 개념을 한 페이지로 압축 요약합니다.',
                        progress: '개념 압축 중'
                    },
                    {
                        icon: '🗂️',
                        title: '공식 정리',
                        description: '중요한 공식들을 카드 형태로 정리합니다.',
                        progress: '공식 정리 중'
                    },
                    {
                        icon: '🔗',
                        title: '연결고리 찾기',
                        description: '개념 간의 연결고리를 찾아 체계화합니다.',
                        progress: '체계화 중'
                    },
                    {
                        icon: '💭',
                        title: '암기법 개발',
                        description: '나만의 암기법과 연상법을 개발합니다.',
                        progress: '암기법 개발 중'
                    }
                ],
                checklist: [
                    '과목별 핵심 개념 5개씩 정리',
                    '중요 공식 플래시카드 제작',
                    '개념 간 연결망 그리기',
                    '나만의 암기 키워드 만들기',
                    'A4 한 장 요약본 작성',
                    '혼동하기 쉬운 개념 구분'
                ],
                motivation: [
                    '압축된 정리는 나중에 큰 힘이 될 거예요! 📝',
                    '복잡한 것을 단순하게 만드는 과정이에요! 🎯',
                    '이제 핵심이 무엇인지 명확해졌어요! 💡'
                ],
                allowCustomTasks: true
            },
            4: { // D-2
                theme: 'theme-focus',
                title: '집중 학습',
                subtitle: '핵심 내용을 반복하여 기억을 강화하세요',
                phase: '🔥 핵심 내용 집중 학습',
                description: '정리한 내용을 반복 학습하여 장기기억에서 작업기억으로 옮기는 단계입니다.',
                headerText: 'D-2',
                dDay: 2,
                actions: [
                    {
                        icon: '🔄',
                        title: '반복 학습',
                        description: '정리한 핵심 내용을 반복하여 기억을 강화합니다.',
                        progress: '반복 학습 중'
                    },
                    {
                        icon: '❌',
                        title: '오답 노트 집중',
                        description: '과거 틀린 문제들을 분석하여 같은 실수를 방지합니다.',
                        progress: '오답 분석 중'
                    },
                    {
                        icon: '⚡',
                        title: '빠른 인출 연습',
                        description: '중요 내용들을 빠르게 떠올리는 연습을 합니다.',
                        progress: '인출 연습 중'
                    },
                    {
                        icon: '⏱️',
                        title: '시간 배분 연습',
                        description: '실제 시험과 같은 시간 조건에서 연습합니다.',
                        progress: '시간 연습 중'
                    }
                ],
                checklist: [
                    '핵심 요약본 3번 반복 읽기',
                    '오답노트 전체 재검토',
                    '중요 공식 암기 완료',
                    '빠른 인출 연습 10회',
                    '시간 배분 시뮬레이션',
                    '실수 방지 체크리스트 작성'
                ],
                motivation: [
                    '반복이 완벽을 만듭니다! 계속 화이팅! 💪',
                    '지금이 기억을 재배선하는 골든타임이에요! 🔥',
                    '실력이 확실히 늘고 있어요! 🚀'
                ],
                allowCustomTasks: true
            },
            5: { // D-1
                theme: 'theme-focus',
                title: '약점 보완',
                subtitle: '마지막 점검과 함께 부족한 부분을 보완하세요',
                phase: '💪 약점 보완 및 최종 점검',
                description: '가장 자신 없는 부분을 보완하고 전체적으로 최종 점검하는 단계입니다.',
                headerText: 'D-1',
                dDay: 1,
                actions: [
                    {
                        icon: '🎯',
                        title: '약점 집중 공략',
                        description: '가장 자신 없는 영역을 집중적으로 보완합니다.',
                        progress: '약점 보완 중'
                    },
                    {
                        icon: '📋',
                        title: '최종 점검',
                        description: '전체 내용을 마지막으로 점검합니다.',
                        progress: '최종 점검 중'
                    },
                    {
                        icon: '🧘',
                        title: '심리적 준비',
                        description: '시험에 대한 불안감을 줄이고 자신감을 키웁니다.',
                        progress: '심리 준비 중'
                    },
                    {
                        icon: '📦',
                        title: '시험 준비',
                        description: '시험장 위치, 준비물 등을 최종 확인합니다.',
                        progress: '준비물 점검 중'
                    }
                ],
                checklist: [
                    '가장 취약한 단원 집중 학습',
                    '전체 요약본 마지막 점검',
                    '자주 틀리는 문제 재확인',
                    '시험장 위치 및 교통편 확인',
                    '준비물 체크리스트 작성',
                    '긍정적 자기 암시하기'
                ],
                motivation: [
                    '마지막 하루도 소중해요! 끝까지 화이팅! 💪',
                    '약점을 보완하면 더 완벽해져요! 🎯',
                    '내일이면 그동안의 노력이 결실을 맺어요! ✨'
                ],
                allowCustomTasks: true
            },
            6: { // D-Day
                theme: 'theme-final',
                title: '시험 당일',
                subtitle: '자신감을 갖고 최상의 컨디션으로 시험에 임하세요',
                phase: '✨ 최종 점검 및 컨디션 관리',
                description: '새로운 학습보다는 컨디션 관리와 마음 준비에 집중하는 단계입니다.',
                headerText: 'D-Day',
                dDay: 0,
                actions: [
                    {
                        icon: '📖',
                        title: '가벼운 복습',
                        description: '요약본을 가볍게 훑어보며 기억을 상기시킵니다.',
                        progress: '가벼운 복습 중'
                    },
                    {
                        icon: '🧘',
                        title: '컨디션 관리',
                        description: '몸과 마음을 최상의 상태로 유지합니다.',
                        progress: '컨디션 관리 중'
                    },
                    {
                        icon: '🎯',
                        title: '시험 전략 확인',
                        description: '시간 배분과 문제 풀이 순서를 재확인합니다.',
                        progress: '전략 확인 중'
                    },
                    {
                        icon: '💝',
                        title: '자신감 충전',
                        description: '그동안의 노력을 인정하고 자신감을 갖습니다.',
                        progress: '마음 준비 중'
                    }
                ],
                checklist: [
                    '요약본만 가볍게 읽기 (30분 이내)',
                    '충분한 수면과 가벼운 아침 식사',
                    '시험장 일찍 도착하기',
                    '준비물 최종 확인',
                    '긍정적인 자기 암시',
                    '깊은 호흡으로 긴장 완화'
                ],
                motivation: [
                    '당신은 이미 충분히 준비했습니다! 자신감을 가지세요! ✨',
                    '지금까지의 노력이 빛을 발할 시간이에요! 🌟',
                    '최고의 결과가 기다리고 있어요! 파이팅! 🏆'
                ],
                allowCustomTasks: true
            }
        };

        // 인트로 타이핑 효과
        function startIntroAnimation() {
            const messages = [
                { main: '안녕하세요! 👋', sub: '라스트 청킹 시스템에 오신 것을 환영합니다' },
                { main: '시험까지 얼마 남지 않았네요 📚', sub: '하지만 걱정하지 마세요!' },
                { main: '과학적인 방법으로 📊', sub: '기억을 재배선하여 최적의 결과를 만들어보겠습니다' },
                { main: '함께 시작해볼까요? 🚀', sub: '체계적인 준비로 성공을 향해!' }
            ];
            
            let messageIndex = 0;
            
            function typeMessage() {
                if (messageIndex >= messages.length) {
                    setTimeout(() => {
                        document.getElementById('introOverlay').classList.add('hide');
                        setTimeout(() => {
                            document.getElementById('introOverlay').style.display = 'none';
                            initializeApp();
                        }, 500);
                    }, 1000);
                    return;
                }
                
                const currentMessage = messages[messageIndex];
                typeText('typingText', currentMessage.main, 50, () => {
                    setTimeout(() => {
                        typeText('typingSubtext', currentMessage.sub, 30, () => {
                            setTimeout(() => {
                                document.getElementById('typingText').textContent = '';
                                document.getElementById('typingSubtext').textContent = '';
                                messageIndex++;
                                typeMessage();
                            }, 1500);
                        });
                    }, 500);
                });
            }
            
            typeMessage();
        }

        function typeText(elementId, text, speed, callback) {
            const element = document.getElementById(elementId);
            let i = 0;
            
            function type() {
                if (i < text.length) {
                    element.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                } else if (callback) {
                    callback();
                }
            }
            
            type();
        }

        // 앱 초기화
        function initializeApp() {
            loadUserData();
            
            // 시험 날짜 설정 (로컬 시간으로 정확히 파싱)
            if (phpData.mathExamDate) {
                const [year, month, day] = phpData.mathExamDate.split('-').map(Number);
                actualExamDate = new Date(year, month - 1, day);
                actualExamDate.setHours(0, 0, 0, 0);
            }
            
            // 항상 오늘 날짜로 시작
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            userData.selectedDate = todayStr;
            
            // 오늘 날짜로 이동
            navigateToDate(today);
            
            restoreSectionStates();
            startPeriodicUpdates();
        }

        // 데이터 로드/저장
        function loadUserData() {
            const storageKey = 'lastChunkingData_' + phpData.userid;
            const saved = localStorage.getItem(storageKey);
            let isFirstVisit = true;
            
            if (saved) {
                try {
                    userData = { ...userData, ...JSON.parse(saved) };
                    isFirstVisit = false;
                } catch (e) {
                    console.warn('데이터 로드 실패:', e);
                }
            }
            
            // PHP에서 받은 데이터로 업데이트
            userData.userid = phpData.userid;
            userData.currentDay = phpData.daysLeft;
            
            if (!userData.startDate) {
                userData.startDate = new Date().toISOString().split('T')[0];
            }
            
            // 처음 방문이면 기본 섹션 상태 설정
            if (isFirstVisit || !userData.sectionStates) {
                userData.sectionStates = {
                    'dashboardSection': true,
                    'actionSection': true
                };
            }
        }

        function saveUserData() {
            try {
                const storageKey = 'lastChunkingData_' + userData.userid;
                localStorage.setItem(storageKey, JSON.stringify(userData));
            } catch (e) {
                console.warn('데이터 저장 실패:', e);
            }
        }

        // 단계 업데이트
        function updatePhase(phase) {
            currentPhase = phase;
            const config = phaseConfig[phase];
            const body = document.getElementById('mainBody');
            
            // 테마 변경
            body.className = config.theme;
            
            // D-Day 업데이트
            userData.currentDay = config.dDay;
            
            // 대시보드 업데이트
            document.getElementById('dashboardTitle').textContent = config.title;
            document.getElementById('dashboardSubtitle').textContent = config.subtitle;
            document.getElementById('currentPhase').textContent = config.phase;
            document.getElementById('phaseDescription').textContent = config.description;
            
            // D-5 이상인 경우 동적으로 headerText 업데이트
            if (phase === 0) {
                config.headerText = 'D-' + userData.currentDay;
                config.dDay = userData.currentDay;
            }
            // Date display는 navigateToDate에서 이미 업데이트하므로 여기서는 하지 않음
            document.getElementById('actionSectionTitle').textContent = '오늘의 우선순위';
            
            // 액션 카드 업데이트
            updateActionCards(config.actions);
            
            // 체크리스트 업데이트
            updateChecklist(config.checklist);
            
            // 모티베이션 메시지 업데이트
            updateMotivation(config.motivation);
            
            // 애니메이션 재실행
            setTimeout(() => {
                document.querySelectorAll('.animate-in').forEach((el, index) => {
                    el.style.animation = 'none';
                    setTimeout(() => {
                        el.style.animation = `fadeInUp 0.6s ease forwards`;
                        el.style.animationDelay = `${index * 0.2}s`;
                    }, 50);
                });
            }, 100);
        }

        function updateActionCards(actions) {
            const grid = document.getElementById('actionGrid');
            grid.innerHTML = '';
            
            actions.forEach((action, index) => {
                const card = document.createElement('div');
                card.className = 'action-card';
                card.onclick = () => handleActionClick(index);
                
                card.innerHTML = `
                    <span class="card-icon">${action.icon}</span>
                    <h3 class="card-title">${action.title}</h3>
                    <p class="card-description">${action.description}</p>
                    <div class="card-progress">${action.progress}</div>
                `;
                
                grid.appendChild(card);
            });
        }

        function updateChecklist(items) {
            const checklist = document.getElementById('mainChecklist');
            checklist.innerHTML = '';
            
            // 사용자 정의 태스크 병합
            const config = phaseConfig[currentPhase];
            let allTasks = [...items];
            
            // 현재 날짜의 키 생성
            const dateKey = userData.selectedDate || new Date().toISOString().split('T')[0];
            
            // 현재 날짜의 사용자 정의 태스크 추가
            if (userData.customTasks[dateKey]) {
                allTasks = allTasks.concat(userData.customTasks[dateKey]);
            }
            
            userData.totalTasks = allTasks.length;
            let completed = 0;
            
            allTasks.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'checklist-item';
                
                // 날짜별로 완료 상태 저장
                const taskKey = `${dateKey}-${index}`;
                const isCompleted = userData.dailyProgress[taskKey] === true;
                if (isCompleted) {
                    li.classList.add('completed');
                    completed++;
                }
                
                const isCustomTask = index >= items.length;
                
                li.innerHTML = `
                    <input type="checkbox" id="task-${index}" ${isCompleted ? 'checked' : ''} 
                           onchange="toggleTask(${index})">
                    <label for="task-${index}">${item}</label>
                    ${isCustomTask ? `
                        <div class="task-actions">
                            <button class="task-edit-btn" onclick="editTask(${index}, '${item.replace(/'/g, "\\'")}')">✏️</button>
                            <button class="task-delete-btn" onclick="deleteTask(${index})">🗑️</button>
                        </div>
                    ` : ''}
                `;
                
                checklist.appendChild(li);
            });
            
            userData.completedTasks = completed;
        }

        function updateMotivation(messages) {
            const motivationEl = document.getElementById('motivationText');
            const randomMessage = messages[Math.floor(Math.random() * messages.length)];
            motivationEl.textContent = randomMessage;
        }

        // UI 업데이트
        function updateUI() {
            // 진행률 업데이트
            const progress = userData.totalTasks > 0 ? 
                Math.round((userData.completedTasks / userData.totalTasks) * 100) : 0;
            
            document.getElementById('progressText').textContent = progress + '%';
            
            const progressCircle = document.getElementById('progressCircle');
            const circumference = 339.29;
            const offset = circumference - (progress / 100 * circumference);
            progressCircle.style.strokeDashoffset = offset;
            
            // 통계 업데이트
            document.getElementById('completedTasks').textContent = userData.completedTasks;
            document.getElementById('totalTasks').textContent = userData.totalTasks;
            document.getElementById('daysLeft').textContent = userData.currentDay;
            document.getElementById('headerDDay').textContent = `D-${userData.currentDay}`;
            document.getElementById('streakDays').textContent = userData.streakDays;
        }

        // 이벤트 핸들러
        function handleActionClick(index) {
            const config = phaseConfig[currentPhase];
            const action = config.actions[index];
            
            // 간단한 피드백
            showNotification(`${action.title} 작업을 시작합니다! 💪`);
        }

        function toggleTask(index) {
            const dateKey = userData.selectedDate || new Date().toISOString().split('T')[0];
            const taskKey = `${dateKey}-${index}`;
            userData.dailyProgress[taskKey] = !userData.dailyProgress[taskKey];
            
            // 연속 실행 일수 계산
            if (userData.dailyProgress[taskKey]) {
                userData.streakDays++;
                showNotification('한 단계 더 발전했어요! 🎉');
            } else {
                userData.streakDays = Math.max(0, userData.streakDays - 1);
            }
            
            saveUserData();
            updateChecklist(phaseConfig[currentPhase].checklist);
            updateUI();
            
            // 전체 완료 체크
            if (userData.completedTasks === userData.totalTasks) {
                setTimeout(() => {
                    showNotification('오늘의 모든 목표를 달성했습니다! 🏆');
                }, 500);
            }
        }

        // 알림 표시
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 30px;
                background: var(--primary-color, #4299e1);
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                z-index: 1000;
                animation: slideInRight 0.3s ease;
                max-width: 300px;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // 주기적 업데이트
        function startPeriodicUpdates() {
            // 자동 저장
            setInterval(saveUserData, 30000);
            
            // 모티베이션 메시지 변경
            setInterval(() => {
                updateMotivation(phaseConfig[currentPhase].motivation);
            }, 60000);
        }

        // 초기화
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(startIntroAnimation, 500);
        });
        
        // 페이지 언로드시 저장
        window.addEventListener('beforeunload', saveUserData);
        
        // 섹션 펼치기/접기 토글
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.classList.toggle('collapsed');
                
                // 상태 저장
                if (!userData.sectionStates) {
                    userData.sectionStates = {};
                }
                userData.sectionStates[sectionId] = section.classList.contains('collapsed');
                saveUserData();
                
                // 애니메이션 효과
                const title = section.querySelector('.section-title, .dashboard-title');
                if (title) {
                    title.classList.toggle('collapsed');
                }
            }
        }
        
        // 저장된 섹션 상태 복원
        function restoreSectionStates() {
            // 처음 방문 시 기본적으로 접힌 상태로 설정
            if (!userData.sectionStates || Object.keys(userData.sectionStates).length === 0) {
                userData.sectionStates = {
                    'dashboardSection': true,
                    'actionSection': true
                };
                
                // 섹션들을 접힌 상태로 설정
                ['dashboardSection', 'actionSection'].forEach(sectionId => {
                    const section = document.getElementById(sectionId);
                    if (section) {
                        section.classList.add('collapsed');
                        const title = section.querySelector('.section-title, .dashboard-title');
                        if (title) {
                            title.classList.add('collapsed');
                        }
                    }
                });
                
                saveUserData();
            } else {
                // 기존 상태 복원
                Object.keys(userData.sectionStates).forEach(sectionId => {
                    if (userData.sectionStates[sectionId]) {
                        const section = document.getElementById(sectionId);
                        if (section) {
                            section.classList.add('collapsed');
                            const title = section.querySelector('.section-title, .dashboard-title');
                            if (title) {
                                title.classList.add('collapsed');
                            }
                        }
                    }
                });
            }
        }
        
        // 태스크 관리 함수들
        function showAddTaskModal() {
            editingTaskIndex = -1;
            document.getElementById('taskModalTitle').textContent = '할 일 추가';
            document.getElementById('taskInput').value = '';
            document.getElementById('taskModal').classList.add('show');
            document.getElementById('taskInput').focus();
        }
        
        function closeTaskModal() {
            document.getElementById('taskModal').classList.remove('show');
            document.getElementById('taskInput').value = '';
            editingTaskIndex = -1;
        }
        
        function saveTask() {
            const taskInput = document.getElementById('taskInput');
            const taskText = taskInput.value.trim();
            
            if (!taskText) {
                showNotification('할 일을 입력해주세요! 📝');
                return;
            }
            
            // 현재 날짜의 키 생성
            const dateKey = userData.selectedDate || new Date().toISOString().split('T')[0];
            
            // 현재 날짜의 사용자 정의 태스크 배열 초기화
            if (!userData.customTasks[dateKey]) {
                userData.customTasks[dateKey] = [];
            }
            
            const config = phaseConfig[currentPhase];
            const baseTasksLength = config.checklist.length;
            
            if (editingTaskIndex === -1) {
                // 새 태스크 추가
                userData.customTasks[dateKey].push(taskText);
                showNotification('할 일이 추가되었습니다! ✅');
            } else {
                // 기존 태스크 수정
                if (editingTaskIndex < baseTasksLength) {
                    // 기본 태스크는 수정할 수 없음 (날짜별로 다르므로)
                    showNotification('기본 태스크는 수정할 수 없습니다.');
                    closeTaskModal();
                    return;
                } else {
                    // 사용자 정의 태스크 수정
                    const customIndex = editingTaskIndex - baseTasksLength;
                    userData.customTasks[dateKey][customIndex] = taskText;
                    showNotification('할 일이 수정되었습니다! ✏️');
                }
            }
            
            saveUserData();
            updateChecklist(config.checklist);
            updateUI();
            closeTaskModal();
        }
        
        function editTask(index, currentText) {
            editingTaskIndex = index;
            document.getElementById('taskModalTitle').textContent = '할 일 수정';
            document.getElementById('taskInput').value = currentText;
            document.getElementById('taskModal').classList.add('show');
            document.getElementById('taskInput').focus();
        }
        
        function deleteTask(index) {
            if (!confirm('이 할 일을 삭제하시겠습니까?')) {
                return;
            }
            
            const config = phaseConfig[currentPhase];
            const baseTasksLength = config.checklist.length;
            const dateKey = userData.selectedDate || new Date().toISOString().split('T')[0];
            
            if (index < baseTasksLength) {
                // 기본 태스크는 삭제할 수 없음
                showNotification('기본 태스크는 삭제할 수 없습니다.');
                return;
            } else {
                // 사용자 정의 태스크 삭제
                const customIndex = index - baseTasksLength;
                if (userData.customTasks[dateKey]) {
                    userData.customTasks[dateKey].splice(customIndex, 1);
                }
            }
            
            // 삭제된 태스크의 완료 상태도 제거
            delete userData.dailyProgress[`${dateKey}-${index}`];
            
            // 인덱스 재정렬
            const newProgress = {};
            Object.keys(userData.dailyProgress).forEach(key => {
                const parts = key.split('-');
                // YYYY-MM-DD-index 형식이므로 마지막 부분이 index
                const taskIndex = parseInt(parts[parts.length - 1]);
                const keyDateParts = parts.slice(0, -1);
                const keyDate = keyDateParts.join('-');
                
                if (keyDate === dateKey && taskIndex > index) {
                    newProgress[`${keyDate}-${taskIndex - 1}`] = userData.dailyProgress[key];
                } else if (keyDate !== dateKey || taskIndex < index) {
                    newProgress[key] = userData.dailyProgress[key];
                }
            });
            userData.dailyProgress = newProgress;
            
            saveUserData();
            updateChecklist(config.checklist);
            updateUI();
            showNotification('할 일이 삭제되었습니다! 🗑️');
        }

        // 날짜 선택 관련 변수
        let selectedDate = new Date();
        let actualExamDate = null;

        // 날짜 선택 모달 열기
        function showDatePicker() {
            document.getElementById('datePickerModal').classList.add('show');
            
            // 현재 날짜로 초기화
            const today = new Date();
            document.getElementById('dateInput').value = today.toISOString().split('T')[0];
            updateSelectedDateDisplay(today);
            
            // 시험 날짜 설정 (로컬 시간으로 정확히 파싱)
            if (phpData.mathExamDate && !actualExamDate) {
                const [year, month, day] = phpData.mathExamDate.split('-').map(Number);
                actualExamDate = new Date(year, month - 1, day);
                actualExamDate.setHours(0, 0, 0, 0);
            }
        }

        // 날짜 선택 모달 닫기
        function closeDatePicker() {
            document.getElementById('datePickerModal').classList.remove('show');
        }

        // 빠른 날짜 선택 (어제, 오늘, 내일)
        function selectQuickDate(dayOffset) {
            const date = new Date();
            date.setDate(date.getDate() + dayOffset);
            selectedDate = date;
            
            document.getElementById('dateInput').value = date.toISOString().split('T')[0];
            updateSelectedDateDisplay(date);
        }

        // 사용자 정의 날짜 선택
        function selectCustomDate(dateString) {
            selectedDate = new Date(dateString + 'T00:00:00');
            updateSelectedDateDisplay(selectedDate);
        }

        // 선택한 날짜 표시 업데이트
        function updateSelectedDateDisplay(date) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const selected = new Date(date);
            selected.setHours(0, 0, 0, 0);
            
            const diffTime = selected - today;
            const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
            
            let displayText = '';
            
            if (diffDays === 0) {
                displayText = '오늘';
            } else if (diffDays === -1) {
                displayText = '어제';
            } else if (diffDays === 1) {
                displayText = '내일';
            } else {
                displayText = selected.toLocaleDateString('ko-KR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
            
            // D-Day 계산
            if (actualExamDate) {
                const examDiffTime = actualExamDate - selected;
                const examDiffDays = Math.round(examDiffTime / (1000 * 60 * 60 * 24));
                
                if (examDiffDays >= 0) {
                    displayText += ` (D-${examDiffDays})`;
                } else {
                    displayText += ` (D+${Math.abs(examDiffDays)})`;
                }
            }
            
            document.getElementById('selectedDateDisplay').textContent = displayText;
        }

        // 날짜 선택 확인
        function confirmDateSelection() {
            navigateToDate(selectedDate);
            closeDatePicker();
        }

        // 특정 날짜로 이동
        function navigateToDate(date) {
            // actualExamDate가 설정되지 않았으면 설정
            if (!actualExamDate && phpData.mathExamDate) {
                const [year, month, day] = phpData.mathExamDate.split('-').map(Number);
                actualExamDate = new Date(year, month - 1, day);
                actualExamDate.setHours(0, 0, 0, 0);
            }
            
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const targetDate = new Date(date);
            targetDate.setHours(0, 0, 0, 0);
            
            
            // 시험날짜까지 남은 일수 계산
            if (actualExamDate) {
                const examDiffTime = actualExamDate - targetDate;
                const examDiffDays = Math.round(examDiffTime / (1000 * 60 * 60 * 24));
                
                if (examDiffDays < 0) {
                    showNotification('시험이 끝난 날짜입니다.');
                    return;
                }
                
                // D-Day 업데이트
                userData.currentDay = examDiffDays;
                
                // 적절한 phase 결정
                let newPhase = 0;
                if (examDiffDays > 5) {
                    newPhase = 0; // D-5 이상
                } else if (examDiffDays === 5) {
                    newPhase = 1; // D-5
                } else if (examDiffDays === 4) {
                    newPhase = 2; // D-4
                } else if (examDiffDays === 3) {
                    newPhase = 3; // D-3
                } else if (examDiffDays === 2) {
                    newPhase = 4; // D-2
                } else if (examDiffDays === 1) {
                    newPhase = 5; // D-1
                } else if (examDiffDays === 0) {
                    newPhase = 6; // D-Day
                }
                
                // 선택한 날짜 저장 (updatePhase 전에 저장해야 함)
                userData.selectedDate = targetDate.toISOString().split('T')[0];
                
                // phase 업데이트
                updatePhase(newPhase);
                
                // 날짜 표시 업데이트
                updateDateDisplay(targetDate);
                updateUI();
                
                // 데이터 저장
                saveUserData();
            } else {
                // 시험 날짜가 없는 경우에도 동작하도록
                updateDateDisplay(targetDate);
                userData.selectedDate = targetDate.toISOString().split('T')[0];
                saveUserData();
                updateUI();
            }
        }

        // 상단 날짜 표시 업데이트
        function updateDateDisplay(date) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const selected = new Date(date);
            selected.setHours(0, 0, 0, 0);
            
            const diffTime = selected - today;
            const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
            
            let displayText = '';
            
            if (diffDays === 0) {
                displayText = '오늘';
            } else if (diffDays === -1) {
                displayText = '어제';
            } else if (diffDays === 1) {
                displayText = '내일';
            } else {
                displayText = selected.toLocaleDateString('ko-KR', {
                    month: 'numeric',
                    day: 'numeric'
                }) + ' (' + ['일', '월', '화', '수', '목', '금', '토'][selected.getDay()] + ')';
            }
            
            document.getElementById('dateDisplayText').textContent = displayText;
        }

    </script>
</body>
</html>