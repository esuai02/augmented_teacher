<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userid = $_GET["userid"] ?? $USER->id;  // 선생님 ID
$studentid = $_GET["studentid"] ?? 0;   // 학생 ID
$role = $_GET["role"] ?? '';  // 역할 파라미터 추가
$contentsid = $_GET["contentsid"] ?? 0;  // 컨텐츠 ID
$contentstype = $_GET["contentstype"] ?? 0;  // 컨텐츠 타입

// 학생 정보 가져오기
$student = null;
if ($studentid) {
    $student = $DB->get_record('user', array('id' => $studentid));
}

if($studentid==NULL)$studentid=817;
// 학생 ID가 없으면 오류 표시
if (!$studentid) {
    print_error('학생 ID가 필요합니다. URL에 studentid 파라미터를 추가해주세요.');
}

// 학생 모드인지 확인
$isStudentMode = ($role === 'student');
?>
 
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>문제풀이 지원 시스템</title>
    <!-- MathJax for mathematical notation -->
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

    <!-- Step-by-Step TTS Player Styles -->
    <link rel="stylesheet" href="/moodle/local/augmented_teacher/alt42/teachingsupport/css/step_player_modal.css">

    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true,
                processEnvironments: true
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
            },
            startup: {
                ready() {
                    console.log('MathJax is loaded and ready');
                    MathJax.startup.defaultReady();
                }
            }
        };
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box; 
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .status-bar {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-item label {
            font-weight: 500;
            color: #666;
        }

        .status-item span {
            color: #3498db;
            font-weight: bold;
        }

        /* 학생 이름 링크 스타일 */
        #studentName a:hover {
            text-decoration: underline;
            opacity: 0.8;
        }

        .student-name:hover {
            opacity: 0.85 !important;
            transform: scale(1.02);
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr; /* 좌우 1:1 비율 */
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .main-content.student-mode {
            grid-template-columns: 1fr;
            max-width: 600px;
            margin: 0 auto 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* TTS 진행상황 스타일 */
        #ttsProgress {
            font-size: 14px;
            color: #1565c0;
        }
        
        #ttsProgressBar {
            background-color: #90caf9;
            height: 4px;
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .panel {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .panel h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .image-preview {
            max-width: 100%;
            margin-top: 15px;
            border-radius: 5px;
            display: none;
        }

        .solution-content {
            font-size: 16px;
            line-height: 1.8;
            color: #2d3748;
        }
        
        /* 기본 해설 표시용 (모달 아닌 곳) */
        .panel .solution-content {
            min-height: 200px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            overflow-y: auto;
            max-height: 400px;
            font-size: 14px;
        }

        .solution-content h3 {
            color: #2c3e50;
            margin-top: 15px;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .solution-content ul, .solution-content ol {
            margin-left: 20px;
            margin-bottom: 10px;
        }

        .solution-content li {
            margin-bottom: 5px;
        }

        .answer-box {
            background-color: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 5px;
            padding: 10px 15px;
            margin: 10px 0;
            font-weight: bold;
            color: #2e7d32;
            display: block;
            clear: both;
        }

        .solution-content h3 {
            background-color: #e3f2fd;
            padding: 8px 12px;
            border-radius: 5px;
            margin: 15px 0 10px 0;
            color: #1565c0;
            font-size: 16px;
        }

        .solution-content strong {
            color: #1976d2;
        }

        .narration-content {
            margin-top: 10px;
            padding: 0;
        }

        .narration-content h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 8px;
        }

        #narrationText {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .audio-control-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            margin-left: 10px;
            transition: all 0.3s ease;
        }

        .audio-control-btn:hover {
            transform: scale(1.1);
        }

        .audio-control-btn svg {
            width: 24px;
            height: 24px;
            fill: #3498db;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-success {
            background-color: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background-color: #229954;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .history-panel {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .history-section {
            margin-bottom: 30px;
        }
        
        .history-section h3 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .history-section.pending h3 {
            color: #e74c3c;
        }
        
        .history-section.completed h3 {
            color: #27ae60;
        }
        
        .history-item-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .no-history {
            text-align: center;
            color: #999;
            padding: 20px;
            font-size: 14px;
        }
        

        .history-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .history-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .history-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .history-item-content {
            margin-bottom: 15px;
        }
        
        .history-item-content img {
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .history-item-content p {
            margin-top: 10px;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .history-item-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .student-name {
            color: #3498db;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .history-item:hover {
            background-color: #f8f9fa;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .history-date {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }

        .history-title {
            font-weight: 500;
            color: #2c3e50;
        }

        .history-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }

        .type-exam {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .type-school {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .type-mathking {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .type-textbook {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .audio-player {
            margin-top: 15px;
            display: none;
        }

        .audio-player audio {
            width: 100%;
        }

        select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
            font-size: 14px;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: #3498db;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 50px auto;
            padding: 20px;
            width: 90%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .modal-close:hover {
            color: #333;
        }
        
        /* 강의 모달 스타일 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
            visibility: visible;
            transform: scale(1);
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            border-radius: 0;
        }
        
        /* 화이트보드 컨테이너 */
        .whiteboard-container {
            position: relative;
            width: 100%;
            height: 100%;
            background: #f5f5f5;
        }
        
        .whiteboard-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* 플로팅 헤드폰 아이콘 (단계별 나레이션) */
        .listening-test-container {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50px;
            padding: 15px 25px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            z-index: 10002;
            min-width: 300px;
            transition: all 0.3s ease;
        }
        
        .listening-test-container.minimized {
            width: 60px;
            height: 60px;
            min-width: 60px;
            padding: 0;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .listening-test-container.minimized .listening-header {
            display: none;
        }
        
        .listening-test-container.minimized .listening-body {
            display: none;
        }
        
        .listening-test-container.minimized::before {
            content: '🎧';
            font-size: 28px;
        }
        
        .listening-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
            margin-bottom: 15px;
        }
        
        .listening-body {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        
        .listening-progress-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .progress-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .progress-dot.active {
            background: white;
            transform: scale(1.3);
        }
        
        .progress-dot.completed {
            background: #4CAF50;
        }
        
        .listening-nav-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .listening-nav-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .listening-nav-btn:hover:not(:disabled) {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        .listening-nav-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        .speed-control-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .speed-control-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .listening-minimize-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s;
        }
        
        .listening-minimize-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* 우측 질문 패널 */
        .question-panel {
            position: fixed;
            right: 0;
            top: 0;
            width: 33.33vw;
            min-width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -4px 0 20px rgba(0,0,0,0.15);
            z-index: 10001;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s ease-out;
        }
        
        .question-panel.active {
            transform: translateX(0);
        }
        
        .question-panel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .question-panel-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .question-panel-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .question-panel-close:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        .question-panel-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .question-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            background: white;
        }
        
        .question-header {
            padding: 15px;
            background: #f8f9fa;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }
        
        .question-header:hover {
            background: #e9ecef;
        }
        
        .question-icon {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .question-answer {
            padding: 15px;
            display: none;
        }
        
        .question-card.active .question-answer {
            display: block;
        }
        
        .question-embed-whiteboard {
            margin-top: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #f5f5f5;
        }
        
        .question-embed-whiteboard iframe {
            width: 100%;
            height: 400px;
            border: none;
            display: block;
        }
        
        .toggle-icon {
            transition: transform 0.3s;
        }
        
        .question-card.active .toggle-icon {
            transform: rotate(180deg);
        }
        
        .solution-line {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            margin-bottom: 15px;
            font-size: 16px;
            line-height: 1.8;
        }

        .solution-line.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .solution-line.teacher {
            background: #ebf8ff;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #4299e1;
        }

        .solution-line.student {
            background: #f0fff4;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #48bb78;
            margin-left: 40px;
        }
        
        /* 수식 스타일 */
        .solution-line .MathJax {
            font-size: 1.1em !important;
        }

        .solution-line h3 {
            color: #2d3748;
            margin: 20px 0 10px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .solution-line ul, .solution-line ol {
            margin: 10px 0;
            padding-left: 30px;
        }

        .solution-line li {
            margin: 5px 0;
        }

        .solution-line strong {
            color: #2b6cb0;
            font-weight: bold;
        }

        .solution-line code {
            background: #f0f4f8;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }

        .speaker-label {
            font-weight: bold;
            color: #2b6cb0;
            margin-bottom: 5px;
        }

        .student .speaker-label {
            color: #276749;
        }

        /* 이미지 확대 모달 스타일 */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            overflow: auto;
        }

        .image-modal-content {
            position: relative;
            margin: auto;
            padding: 0;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 5vh;
        }

        .image-modal-content img {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .image-modal-close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10001;
            transition: color 0.3s;
        }

        .image-modal-close:hover,
        .image-modal-close:focus {
            color: #bbb;
        }

        .clickable-image {
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .clickable-image:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>문제풀이 지원 시스템</h1>
            <div class="status-bar">
                <?php if (!$isStudentMode): ?>
                <div class="status-item">
                    <label>선생님:</label>
                    <span id="teacherName"><?php echo fullname($USER); ?></span>
                </div>
                <div class="status-item">
                    <label>학생:</label>
                    <span id="studentName">
                        <?php
                        if ($student) {
                            echo '<a href="student_inbox.php?studentid=' . $studentid . '" style="color: #2196f3; text-decoration: none; font-weight: 500;">'
                                 . fullname($student) . ' (ID: ' . $studentid . ')'
                                 . '</a>';
                        } else {
                            echo '학생 정보 없음';
                        }
                        ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="status-item">
                    <label>문제 유형:</label>
                    <select id="problemType">
                        <option value="">선택하세요</option>
                        <option value="exam">내신 기출</option>
                        <option value="school">학교 프린트</option>
                        <option value="mathking">MathKing 문제</option>
                        <option value="textbook" selected>시중교재</option>
                    </select>
                </div>
                <div class="status-item">
                    <label>처리 상태:</label>
                    <span id="processStatus">대기중</span>
                </div>
                <?php if (!$isStudentMode): ?>
                <div class="status-item">
                    <a href="interaction_history.php?userid=<?php echo $userid; ?>&studentid=<?php echo $studentid; ?>" 
                       class="btn btn-secondary" target="_blank" style="margin-left: 10px;">
                        📊 질의응답 현황판
                    </a>
                </div>
                <div class="status-item">
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/optimize_prompt.php" 
                       class="btn btn-secondary" target="_blank" style="margin-left: 10px;">
                        📊 프롬프트 최적화
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 문제 업로드 패널 제거됨 - 새로운 풀이요청에서만 풀이 시작하기 사용 -->
        <div id="imagePreview" class="image-preview" style="display: none;" alt="문제 미리보기"></div>
        <input type="text" id="additionalPrompt" class="form-control" 
               placeholder="추가 요청사항 (예: 더 자세히 설명해주세요, 다른 풀이 방법도 알려주세요)" 
               style="display: none; width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
        <button id="startTutoringBtn" class="btn btn-primary" style="display: none;" disabled>
            <?php echo $isStudentMode ? '📤 제출하기' : '🚀 하이튜터링 시작'; ?>
        </button>

        <?php if (!$isStudentMode): ?>
        <div class="main-content<?php echo $isStudentMode ? ' student-mode' : ''; ?>">
            <!-- 좌측 칼럼: 최근 문제 해설 기록 -->
            <div class="history-panel">
                <h2>최근 문제 해설 기록</h2>
                
                <!-- 새로운 풀이요청 섹션 -->
                <div class="history-section new-requests" style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="color: #2e7d32;">
                        <span>🆕</span>
                        새로운 풀이요청
                    </h3>
                    <div id="newRequestsList" class="history-item-list">
                        <div class="loading">
                            <div class="spinner"></div>
                            <p>불러오는 중...</p>
                        </div>
                    </div>
                </div>
                
                <!-- 완료된 해설 기록 섹션 -->
                <div class="history-section completed-requests" style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    <h3 style="color: #616161; font-size: 16px; margin-bottom: 10px;">
                        <span>✅</span>
                        완료된 해설 기록
                    </h3>
                    <div id="completedRequestsList" class="history-item-list" style="max-height: 400px; overflow-y: auto;">
                        <div class="loading">
                            <div class="spinner"></div>
                            <p>불러오는 중...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 우측 칼럼: AI 해설 및 TTS 대본 -->
            <div class="panel">
                <h2>
                    TTS 대본
                </h2>
                <div class="loading" id="solutionLoading">
                    <div class="spinner"></div>
                    <p>AI가 문제를 분석중입니다...</p>
                </div>
                <!-- 해설 영역 (숨김) -->
                <div class="solution-content" id="solutionContent" style="display: none;">
                    해설이 여기에 표시됩니다.
                </div>
                <!-- TTS 대본 영역 (표시) -->
                <div class="narration-content" id="narrationContent" style="display: block;">
                    <div id="narrationText" style="min-height: 100px; padding: 10px; background-color: #fff; border: 1px solid #e0e0e0; border-radius: 4px; overflow-y: auto; max-height: 500px; font-size: 14px; line-height: 1.6; color: #333; white-space: pre-wrap; display: block;">
                        잠시 후 TTS 대사가 준비됩니다.
                    </div>
                    <!-- 진행상황 표시 -->
                    <div id="ttsProgress" style="margin-top: 10px; padding: 8px; background-color: #f5f5f5; border-radius: 4px; display: none;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="spinner" style="width: 18px; height: 18px;"></div>
                            <span id="ttsProgressText" style="font-size: 13px;">음성 생성 중...</span>
                        </div>
                        <div id="ttsProgressBar" style="margin-top: 8px; height: 3px; background-color: #4caf50; border-radius: 2px; width: 0%; transition: width 0.3s ease;"></div>
                    </div>
                </div>
                <div class="action-buttons" style="margin-top: 10px;">
                    <button id="sendMessageBtn" class="btn btn-success" disabled
                            style="width: 100%; padding: 12px; border-radius: 4px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s ease; background-color: #27ae60; color: white;">
                        응답하기
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$isStudentMode): ?>
    <!-- 메시지 전송 모달 -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" id="modalClose">&times;</span>
            <h3>학생에게 메시지 전송</h3>
            <textarea id="messageText" style="width: 100%; height: 100px; margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" placeholder="추가 메시지를 입력하세요..."></textarea>
            <div class="action-buttons">
                <button id="confirmSendBtn" class="btn btn-success">전송</button>
                <button id="cancelSendBtn" class="btn btn-secondary">취소</button>
            </div>
        </div>
    </div>

    <!-- 다른 풀이 입력 모달 -->
    <div id="customSolutionModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px;">
            <span class="modal-close" onclick="closeCustomSolutionModal()">&times;</span>
            <h3 style="color: #9c27b0; margin-bottom: 15px;">
                📝 다른 풀이 입력
            </h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                제공된 해설 이미지 대신 사용할 풀이를 직접 입력하세요. 입력된 풀이를 기반으로 TTS 대본이 생성됩니다.
            </p>
            <textarea id="customSolutionText"
                      style="width: 100%; height: 300px; margin: 10px 0; padding: 15px; border: 2px solid #9c27b0; border-radius: 8px; font-size: 14px; line-height: 1.6; font-family: inherit;"
                      placeholder="예시:&#10;&#10;1단계: 주어진 식을 정리합니다.&#10;   x² + 2x + 1 = 0&#10;&#10;2단계: 인수분해를 합니다.&#10;   (x + 1)² = 0&#10;&#10;3단계: 해를 구합니다.&#10;   x = -1&#10;&#10;따라서 답은 x = -1입니다."></textarea>
            <div class="action-buttons" style="margin-top: 15px;">
                <button id="confirmCustomSolutionBtn" class="btn btn-success" style="background: #9c27b0;">
                    ✅ 이 풀이로 TTS 생성
                </button>
                <button onclick="closeCustomSolutionModal()" class="btn btn-secondary">
                    취소
                </button>
            </div>
        </div>
    </div>
    
    <!-- 풀이 스타일 선택 팝업 모달 -->
    <div id="solutionStyleModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px; padding: 0; border-radius: 12px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%); color: white; padding: 20px;">
                <h3 style="margin: 0; font-size: 18px;">📋 풀이 스타일 선택</h3>
                <p style="margin: 8px 0 0; font-size: 13px; opacity: 0.9;">원하는 풀이 스타일을 선택하세요</p>
            </div>
            <div style="padding: 15px;">
                <div class="style-option" onclick="selectSolutionStyle('default')" 
                     style="padding: 14px 16px; cursor: pointer; background: #e3f2fd; border-left: 4px solid #1976d2; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;">
                    <strong>✅ 표준풀이 시작하기</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">일반적인 풀이 스타일</p>
                </div>
                <div class="style-option" onclick="selectSolutionStyle('concise')" 
                     style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #42a5f5; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <strong>⚡ 수식·기호·화살표 위주 간결 풀이</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">핵심만 빠르게</p>
                </div>
                <div class="style-option" onclick="selectSolutionStyle('textbook')" 
                     style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #5c6bc0; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <strong>📖 시중 교재 해설지 스타일</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">교재처럼 정돈된 형식</p>
                </div>
                <div class="style-option" onclick="selectSolutionStyle('detailed')" 
                     style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #26a69a; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <strong>📋 단계별 상세 설명 풀이</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">모든 단계를 자세히</p>
                </div>
                <div class="style-option" onclick="selectSolutionStyle('examples')" 
                     style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #66bb6a; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <strong>💡 예시를 넣어 설명하는 스타일</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">이해를 돕는 예시 포함</p>
                </div>
                <div class="style-option" onclick="selectSolutionStyle('exam')" 
                     style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #ffa726; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <strong>🎯 실전풀이(시험장 버전)</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">시험장에서 쓰는 빠른 풀이</p>
                </div>
                <div class="style-option" onclick="selectSolutionStyle('concept')" 
                     style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #ab47bc; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <strong>🔑 핵심 개념 강조 풀이</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">개념 중심 설명</p>
                </div>
                <div style="border-top: 1px solid #e0e0e0; margin-top: 8px; padding-top: 8px;">
                    <div class="style-option" onclick="openCustomSolutionFromStyleModal()" 
                         style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #7e57c2; border-radius: 6px; transition: all 0.2s;"
                         onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                        <strong>📝 다른 풀이 입력</strong>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #666;">직접 풀이 입력하기</p>
                    </div>
                </div>
            </div>
            <div style="padding: 15px; border-top: 1px solid #e0e0e0; text-align: right;">
                <button onclick="closeSolutionStyleModal()" class="btn btn-secondary" style="padding: 10px 20px;">
                    취소
                </button>
            </div>
        </div>
    </div>

    <!-- 이미지 편집 모달 (type=capture일 때 사용) -->
    <div id="imageEditModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 90vw; max-height: 90vh; padding: 0; border-radius: 12px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%); color: white; padding: 20px;">
                <h3 style="margin: 0; font-size: 18px;">✏️ 이미지 편집</h3>
                <p style="margin: 8px 0 0; font-size: 13px; opacity: 0.9;">이미지 크기를 조정하거나 지우개로 불필요한 부분을 지우세요</p>
            </div>
            <div style="padding: 15px;">
                <!-- 도구 모음 -->
                <div style="display: flex; gap: 15px; margin-bottom: 15px; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label style="font-weight: 500;">📐 크기:</label>
                        <button onclick="adjustImageSize(-10)" class="btn btn-sm" style="padding: 5px 12px;">-</button>
                        <span id="imageSizePercent" style="min-width: 50px; text-align: center;">100%</span>
                        <button onclick="adjustImageSize(10)" class="btn btn-sm" style="padding: 5px 12px;">+</button>
                        <button onclick="resetImageSize()" class="btn btn-sm" style="padding: 5px 12px; background: #607d8b; color: white;">원본</button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label style="font-weight: 500;">🧹 지우개:</label>
                        <button id="eraserToggleBtn" onclick="toggleEraser()" class="btn btn-sm" style="padding: 5px 12px;">OFF</button>
                        <label style="font-size: 12px;">크기:</label>
                        <input type="range" id="eraserSize" min="10" max="100" value="30" style="width: 80px;" onchange="updateEraserSize()">
                        <span id="eraserSizeValue">30px</span>
                    </div>
                    <button onclick="undoErase()" class="btn btn-sm" style="padding: 5px 12px; background: #9c27b0; color: white;">↩️ 되돌리기</button>
                </div>
                <!-- 캔버스 영역 -->
                <div id="imageEditCanvasContainer" style="overflow: auto; max-height: 60vh; border: 2px dashed #ccc; border-radius: 8px; background: #f5f5f5; position: relative;">
                    <canvas id="imageEditCanvas" style="cursor: crosshair;"></canvas>
                </div>
            </div>
            <div style="padding: 15px; border-top: 1px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 10px;">
                <button onclick="closeImageEditModal()" class="btn btn-secondary" style="padding: 10px 20px;">
                    취소
                </button>
                <button onclick="confirmImageEdit()" class="btn btn-primary" style="padding: 10px 20px; background: linear-gradient(135deg, #4caf50 0%, #81c784 100%); color: white;">
                    ✅ 확인 후 스타일 선택
                </button>
            </div>
        </div>
    </div>

    <!-- 힌트 종류 선택 팝업 모달 -->
    <div id="hintTypeModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px; padding: 0; border-radius: 12px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #00bcd4 0%, #4dd0e1 100%); color: white; padding: 20px;">
                <h3 style="margin: 0; font-size: 18px;">💡 힌트 종류 선택</h3>
                <p style="margin: 8px 0 0; font-size: 13px; opacity: 0.9;">원하는 힌트 수준을 선택하세요</p>
            </div>
            <div style="padding: 15px;">
                <div class="hint-option" onclick="selectHintType('explain')" 
                     style="padding: 14px 16px; cursor: pointer; background: #e0f7fa; border-left: 4px solid #00bcd4; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;">
                    <strong>📖 힌트 생성 (문제해설)</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">문제 읽기 + 취지/개념/공식 + 식 세우기</p>
                </div>
                <div class="hint-option" onclick="selectHintType('early')" 
                     style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #26c6da; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <strong>🔰 힌트 생성 (초반풀이)</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">문제해설 + 주의사항 + 풀이 초반부</p>
                </div>
                <div class="hint-option" onclick="selectHintType('middle')" 
                     style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #4db6ac; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <strong>📝 힌트 생성 (중반풀이)</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">문제해설 + 주의사항 + 풀이 중반부까지</p>
                </div>
                <div class="hint-option" onclick="selectHintType('full')" 
                     style="padding: 14px 16px; cursor: pointer; border: 1px solid #e0e0e0; border-left: 4px solid #80cbc4; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <strong>📋 힌트 생성 (전체해설)</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">계산 없이 전체 풀이과정 해설</p>
                </div>
                <hr style="margin: 12px 0; border: none; border-top: 1px dashed #ccc;">
                <div class="hint-option" onclick="openCustomHintFromTypeModal()" 
                     style="padding: 14px 16px; cursor: pointer; border: 2px solid #9c27b0; border-left: 4px solid #9c27b0; margin-bottom: 8px; border-radius: 6px; transition: all 0.2s; background: #f3e5f5;"
                     onmouseover="this.style.background='#e1bee7'" onmouseout="this.style.background='#f3e5f5'">
                    <strong>✍️ 직접 힌트 입력</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #666;">AI 생성 대신 직접 힌트 대본 작성</p>
                </div>
            </div>
            <div style="padding: 15px; border-top: 1px solid #e0e0e0; text-align: right;">
                <button onclick="closeHintTypeModal()" class="btn btn-secondary" style="padding: 10px 20px;">
                    취소
                </button>
            </div>
        </div>
    </div>
    
    <!-- 직접 힌트 입력 모달 -->
    <div id="customHintModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px;">
            <button class="modal-close" onclick="closeCustomHintModal()">&times;</button>
            <h3 style="color: #00bcd4; margin-bottom: 15px;">
                ✍️ 직접 힌트 입력
            </h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                AI 생성 대신 직접 힌트를 입력하세요. 입력된 힌트를 기반으로 TTS 대본이 생성됩니다.
            </p>
            <textarea id="customHintText"
                      style="width: 100%; height: 300px; margin: 10px 0; padding: 15px; border: 2px solid #00bcd4; border-radius: 8px; font-size: 14px; line-height: 1.6; font-family: inherit;"
                      placeholder="예시:&#10;&#10;이 문제는 분수의 나눗셈을 묻는 문제야.@&#10;&#10;나눗셈을 곱셈으로 바꾸려면 나누는 수를 뒤집어서 곱해야 해.@&#10;&#10;5 나누기 7/8은 5 곱하기 8/7이 되는 거지.@&#10;&#10;이제 분자끼리, 분모끼리 곱해보렴.@&#10;&#10;(@ 기호는 TTS 문장 구분점입니다)"></textarea>
            <div class="action-buttons" style="margin-top: 15px;">
                <button onclick="submitCustomHint()" class="btn btn-primary" style="background: linear-gradient(135deg, #00bcd4 0%, #4dd0e1 100%); color: white; padding: 12px 24px; font-size: 14px;">
                    ✅ 이 힌트로 TTS 생성
                </button>
                <button onclick="closeCustomHintModal()" class="btn btn-secondary" style="margin-left: 10px; padding: 12px 24px;">
                    취소
                </button>
            </div>
        </div>
    </div>
    
    <!-- 강의 재생 모달 (전체 화면) -->
    <div class="modal-overlay" id="lectureModal">
        <div class="modal-content" style="max-width: 100vw; width: 100vw; height: 100vh; display: flex; flex-direction: column; border-radius: 0; margin: 0; padding: 0;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <h2 class="modal-title" style="font-size: 20px; font-weight: bold;">📚 문제 해설 강의</h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button class="control-btn" id="btn-question-panel" title="자주하는 질문" onclick="initStepQuestions()" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.4); padding: 10px 15px; border-radius: 25px; cursor: pointer; font-size: 16px;">
                        🔍
                    </button>
                    <button class="modal-close" onclick="closeLectureModal()" style="background: none; border: none; color: white; font-size: 28px; cursor: pointer;">&times;</button>
                </div>
            </div>
            <div class="modal-body" style="display: flex; flex: 1; overflow: hidden; position: relative;">
                <div class="whiteboard-container">
                    <iframe id="whiteboardFrame" src="" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 질문 패널 -->
    <div id="questionPanel" class="question-panel">
        <div class="question-panel-header">
            <h3>자주하는 질문</h3>
            <button class="question-panel-close" onclick="closeQuestionPanel()">&times;</button>
        </div>
        <div id="questionPanelContent" class="question-panel-content"></div>
    </div>
    
    <audio id="modalAudioPlayer" style="display: none;"></audio>
    <?php endif; ?>

    <script>
        // 학생 모드 플래그 추가
        const isStudentMode = <?php echo $isStudentMode ? 'true' : 'false'; ?>;
        // 전역 변수
        let uploadedFile = null;
        let currentSolution = '';
        
        // 이미지 URL 생성 함수
        function getImageUrl(imagePath) {
            if (!imagePath) return '';
            
            // base64 데이터인 경우
            if (imagePath.startsWith('data:')) {
                return imagePath;
            }
            
            // 절대 URL인 경우
            if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
                return imagePath;
            }
            
            // images/ 로 시작하는 경우
            if (imagePath.startsWith('images/')) {
                return 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/' + imagePath;
            }
            
            // 파일명만 있는 경우
            return 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/images/' + imagePath;
        }
        let currentAudioUrl = '';
        let currentNarration = '';
        let audioElement = null;
        let currentInteractionId = null;
        let currentImageUrl = '';

        // DOM 요소
        const imagePreview = document.getElementById('imagePreview');
        const startTutoringBtn = document.getElementById('startTutoringBtn');
        const solutionContent = document.getElementById('solutionContent');
        const solutionLoading = document.getElementById('solutionLoading');
        const generateNarrationBtn = document.getElementById('generateNarrationBtn');
        const generateTTSBtn = document.getElementById('generateTTSBtn');
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        const saveContentBtn = document.getElementById('saveContentBtn');
        const audioPlayer = document.getElementById('audioPlayer');
        const audioElementPlayer = document.getElementById('audioElement');
        const narrationContent = document.getElementById('narrationContent');
        const narrationText = document.getElementById('narrationText');
        const playAudioBtn = document.getElementById('playAudioBtn');
        const pauseAudioBtn = document.getElementById('pauseAudioBtn');
        const problemType = document.getElementById('problemType');
        const processStatus = document.getElementById('processStatus');
        const newRequestsList = document.getElementById('newRequestsList');
        const messageModal = document.getElementById('messageModal');
        const modalClose = document.getElementById('modalClose');
        const messageText = document.getElementById('messageText');
        const confirmSendBtn = document.getElementById('confirmSendBtn');
        const cancelSendBtn = document.getElementById('cancelSendBtn');

        // 수식 포맷팅 함수
        function formatMathContent(content) {
            // 줄 단위로 처리
            const lines = content.split('\n');
            const formattedLines = lines.map(line => {
                // 섹션 헤더 처리 (대괄호로 둘러싸인 텍스트)
                if (line.match(/^\[.*\]$/)) {
                    return '<h3>' + line.substring(1, line.length - 1) + '</h3>';
                }
                
                // 번호 목록 처리
                line = line.replace(/^(\d+)\.\s/, '<strong>$1.</strong> ');
                
                // 답 강조 처리
                if (line.startsWith('답:')) {
                    return '<div class="answer-box">' + line + '</div>';
                }
                
                // 리스트 아이템 처리
                line = line.replace(/^-\s/, '• ');
                
                return line;
            });
            
            // 줄바꿈으로 다시 결합
            return formattedLines.join('<br>');
        }

        // 기존 풀이 복사 함수
        async function copyExistingSolution(existingInteraction, studentId) {
            try {
                processStatus.textContent = '기존 풀이 복사 중...';
                
                const copyResponse = await fetch('save_interaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'copy_interaction',
                        sourceInteractionId: existingInteraction.id,
                        studentId: studentId,
                        teacherId: <?php echo $USER->id; ?>
                    })
                });
                
                const copyData = await copyResponse.json();
                
                if (copyData.success && copyData.interactionId) {
                    currentInteractionId = copyData.interactionId;
                    
                    // 복사된 데이터로 UI 업데이트
                    if (existingInteraction.problem_image) {
                        imagePreview.src = getImageUrl(existingInteraction.problem_image);
                        imagePreview.style.display = 'block';
                    }
                    
                    if (existingInteraction.solution_text) {
                        currentSolution = existingInteraction.solution_text;
                        solutionContent.innerHTML = formatMathContent(currentSolution);
                        solutionContent.style.display = 'none';
                    }
                    
                    if (existingInteraction.narration_text) {
                        currentNarration = existingInteraction.narration_text;
                        narrationText.textContent = currentNarration;
                    }
                    
                    if (existingInteraction.audio_url) {
                        currentAudioUrl = existingInteraction.audio_url;
                    }
                    
                    // 문제 유형 설정
                    if (existingInteraction.problem_type && problemType) {
                        problemType.value = existingInteraction.problem_type;
                    }
                    
                    processStatus.textContent = '✅ 기존 풀이 복사 완료!';
                    alert('기존 풀이가 복사되었습니다.');
                    
                    // MathJax 렌더링
                    if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                        MathJax.typesetPromise([solutionContent]).catch((err) => {
                            console.error('MathJax 렌더링 오류:', err);
                        });
                    }
                } else {
                    throw new Error(copyData.error || '기존 풀이 복사 실패');
                }
            } catch (error) {
                console.error('[teachingagent.php] 기존 풀이 복사 중 오류:', error);
                alert('기존 풀이 복사 중 오류가 발생했습니다: ' + error.message);
                processStatus.textContent = '오류 발생';
            } finally {
                startTutoringBtn.disabled = false;
            }
        }

        // 파일 업로드 처리 함수 (새로운 풀이요청에서 이미지 로드 시 사용)
        function handleFileUpload(file) {
            if (!file.type.startsWith('image/')) {
                alert('이미지 파일만 업로드 가능합니다.');
                return;
            }

            uploadedFile = file;
            const reader = new FileReader();
            
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                startTutoringBtn.disabled = false;
                startTutoringBtn.style.display = 'inline-block';
                processStatus.textContent = '업로드 완료';
            };
            
            reader.readAsDataURL(file);
        }

        // 하이튜터링 시작 또는 문제 제출 (학생 모드)
        startTutoringBtn.addEventListener('click', async () => {
            if (!uploadedFile) {
                console.error('[teachingagent.php] uploadedFile이 없습니다.');
                alert('문제 이미지가 없습니다. 새로운 풀이요청에서 풀이 시작하기를 클릭해주세요.');
                return;
            }

            if (!problemType.value) {
                alert('문제 유형을 선택해주세요.');
                return;
            }

            // contentsid와 contentstype이 있는 경우 기존 풀이 확인
            const contentsid = <?php echo $contentsid; ?>;
            const contentstype = <?php echo $contentstype; ?>;
            const studentid = <?php echo $studentid; ?>;
            
            if (contentsid && contentstype && !isStudentMode) {
                try {
                    const checkResponse = await fetch('check_existing_solution.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            contentsid: contentsid,
                            contentstype: contentstype,
                            studentid: studentid
                        })
                    });
                    
                    const checkData = await checkResponse.json();
                    
                    if (checkData.success && checkData.exists) {
                        // 기존 풀이가 있는 경우 선택 다이얼로그 표시
                        const useExisting = confirm(
                            '이미 풀이가 존재합니다.\n\n' +
                            '새로운 풀이를 시작하려면 "확인"을 클릭하세요.\n' +
                            '기존 풀이를 사용하려면 "취소"를 클릭하세요.'
                        );
                        
                        if (!useExisting) {
                            // 기존 풀이 사용 선택
                            await copyExistingSolution(checkData.interaction, studentid);
                            return;
                        }
                        // 새로운 풀이 시작 (기존 로직 계속 진행)
                    }
                } catch (error) {
                    console.error('[teachingagent.php] 기존 풀이 확인 중 오류:', error);
                    // 오류 발생 시에도 계속 진행
                }
            }

            startTutoringBtn.disabled = true;
            
            if (isStudentMode) {
                // 학생 모드: 문제만 제출
                processStatus.textContent = '문제 제출 중...';
                
                try {
                    // 문제 제출 로직 (필요한 경우 구현)
                    // 현재는 간단하게 알림만 표시
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    processStatus.textContent = '✅ 제출 완료!';
                    alert('문제가 성공적으로 제출되었습니다.\n선생님이 확인 후 답변해 드릴 예정입니다.');
                    
                    // 초기화
                    clearForm();
                } catch (error) {
                    console.error('Error:', error);
                    alert('문제 제출 중 오류가 발생했습니다: ' + error.message);
                    processStatus.textContent = '오류 발생';
                } finally {
                    startTutoringBtn.disabled = false;
                }
            } else {
                // 선생님 모드: 기존 하이튜터링 프로세스
                solutionLoading.classList.add('active');
                solutionContent.textContent = '';
                
                try {
                    // 파라미터 확인
                    console.log('Teacher ID:', '<?php echo $userid; ?>');
                    console.log('Student ID:', '<?php echo $studentid; ?>');
                    
                    if (!'<?php echo $studentid; ?>') {
                        throw new Error('학생 ID가 설정되지 않았습니다.');
                    }
                    
                    // 1단계: 문제 분석
                    processStatus.textContent = '1/3 문제 분석 중...';
                    await analyzeProblem();
                    
                    // 2단계: 나레이션 생성 (단계별 TTS 전용 대본 생성)
                    processStatus.textContent = '2/3 단계별 TTS 대본 생성 중...';
                    await generateNarration();
                    
                    // 3단계: 메시지 발송
                    processStatus.textContent = '3/3 학생에게 메시지 발송 중...';
                    await sendMessage();
                    
                    processStatus.textContent = '✅ 하이튜터링 완료!';
                    
                    // 추가 프롬프트 입력창 초기화
                    const additionalPromptInput = document.getElementById('additionalPrompt');
                    if (additionalPromptInput) {
                        additionalPromptInput.value = '';
                    }
                    
                    // 학생에게 메시지함 확인 안내
                    alert('하이튜터링이 완료되었습니다!\n학생이 메시지함에서 해설 강의를 확인할 수 있습니다.');
                    
                } catch (error) {
                    console.error('Error:', error);
                    alert('하이튜터링 중 오류가 발생했습니다: ' + error.message);
                    processStatus.textContent = '오류 발생';
                } finally {
                    solutionLoading.classList.remove('active');
                    startTutoringBtn.disabled = false;
                }
            }
        });

        // 문제 분석 함수
        async function analyzeProblem() {
            // 기존 interactionId가 있는지 확인
            if (!currentInteractionId) {
                // 새로운 상호작용 레코드 생성
                console.log('Creating NEW interaction record...');
            } else {
                // 기존 레코드 사용
                console.log('Using EXISTING interaction ID:', currentInteractionId);
            }
            // 디버깅 정보
            console.log('Before creating interaction:');
            console.log('window.requestedTeacherId:', window.requestedTeacherId);
            console.log('window.requestedStudentId:', window.requestedStudentId);
            console.log('PHP userid:', <?php echo $userid; ?>);
            console.log('PHP studentid:', <?php echo $studentid; ?>);
            console.log('PHP USER->id:', <?php echo $USER->id; ?>);
            
            // 디버깅: 사용될 ID 값들 확인
            const finalTeacherId = window.requestedTeacherId || parseInt('<?php echo $userid; ?>') || 0;
            const finalStudentId = window.requestedStudentId || parseInt('<?php echo $studentid; ?>') || parseInt('<?php echo $USER->id; ?>');
            
            console.log('Creating interaction with:');
            console.log('  finalTeacherId:', finalTeacherId);
            console.log('  finalStudentId:', finalStudentId);
            console.log('  problemType:', problemType.value);
            console.log('  hasImage:', !!imagePreview.src);
            console.log('  modificationPrompt:', window.modificationPrompt || 'none');
            
            // currentInteractionId가 없을 때만 새로운 레코드 생성
            if (!currentInteractionId) {
                const interactionData = {
                    action: 'create_interaction',
                    teacherId: <?php echo $USER->id; ?>,  // 현재 사용자 ID 사용
                    studentId: finalStudentId,
                    problemType: problemType.value,
                    problemImage: imagePreview.src,
                    modificationPrompt: window.modificationPrompt || ''
                };
                
                console.log('Sending interaction data:', interactionData);
                
                const createResponse = await fetch('save_interaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(interactionData)
                });
                
                const createData = await createResponse.json();
                console.log('Create interaction response:', createData);
                
                if (createData.success && createData.interactionId) {
                    currentInteractionId = createData.interactionId;
                    console.log('NEW Interaction ID created:', currentInteractionId);
                    
                    // 새로운 요청 목록 즉시 새로고침
                    console.log('Reloading new requests after creation...');
                    setTimeout(() => {
                        loadNewRequests();
                    }, 1000);
                } else {
                    throw new Error('상호작용 레코드 생성 실패: ' + (createData.error || '알 수 없는 오류'));
                }
            } else {
                console.log('Using existing interaction ID:', currentInteractionId);
                
                // 기존 레코드에 추가 프롬프트 업데이트
                if (window.modificationPrompt) {
                    const updateResponse = await fetch('save_interaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_solution',
                            interactionId: currentInteractionId,
                            solution: '',  // 해설은 나중에 업데이트
                            modificationPrompt: window.modificationPrompt
                        })
                    });
                    
                    const updateData = await updateResponse.json();
                    console.log('Updated modification prompt:', updateData);
                }
            }

            // FormData 생성
            const formData = new FormData();
            formData.append('image', uploadedFile);
            formData.append('problemType', problemType.value);
            formData.append('teacherId', '<?php echo $USER->id; ?>');  // 현재 사용자 ID 사용
            formData.append('studentId', '<?php echo $studentid; ?>');
            
            // 기존 interactionId가 있으면 전달
            if (currentInteractionId) {
                formData.append('interactionId', currentInteractionId);
            }
            
            // 추가 프롬프트 가져오기
            const additionalPromptInput = document.getElementById('additionalPrompt');
            const additionalPrompt = additionalPromptInput ? additionalPromptInput.value.trim() : '';
            
            // 수정 프롬프트가 있으면 추가 (기존 재요청 처리)
            if (window.modificationPrompt) {
                formData.append('modificationPrompt', window.modificationPrompt);
                // 사용 후 초기화
                window.modificationPrompt = null;
            } else if (additionalPrompt) {
                // 새로운 추가 프롬프트가 있으면 추가
                formData.append('modificationPrompt', additionalPrompt);
            }

            // 선택된 풀이 스타일 추가
            if (window.selectedSolutionStyle) {
                formData.append('solutionStyle', window.selectedSolutionStyle);
                console.log('[하이튜터링 시작] 선택된 풀이 스타일:', window.selectedSolutionStyle);
                // 사용 후 초기화
                window.selectedSolutionStyle = null;
            } else {
                formData.append('solutionStyle', 'default');
            }

            // OpenAI API를 통한 문제 분석
            const response = await fetch('analyze_problem.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                currentSolution = data.solution;
                currentImageUrl = data.imageUrl || '';
                
                // 해설 저장
                if (currentInteractionId) {
                    await fetch('save_interaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_solution',
                            interactionId: currentInteractionId,
                            solution: currentSolution,
                            imageUrl: currentImageUrl
                        })
                    });
                }
                
                // 해설은 숨김 처리 (TTS 대본만 표시)
                solutionContent.innerHTML = formatMathContent(currentSolution);
                solutionContent.style.display = 'none';
                // MathJax에게 새로운 수식을 렌더링하도록 지시 (숨김 상태에서도 렌더링)
                if (window.MathJax && window.MathJax.typesetPromise) {
                    await window.MathJax.typesetPromise([solutionContent]);
                }
            } else {
                throw new Error(data.error || '분석 실패');
            }
        }

        // 나레이션 생성 함수 (단계별 TTS 전용 대본만 생성)
        async function generateNarration() {
            if (!currentSolution) throw new Error('해설이 없습니다.');
            if (!currentInteractionId) throw new Error('상호작용 ID가 없습니다.');

            // 진행상황 표시 시작
            const ttsProgress = document.getElementById('ttsProgress');
            const ttsProgressText = document.getElementById('ttsProgressText');
            const ttsProgressBar = document.getElementById('ttsProgressBar');
            const narrationText = document.getElementById('narrationText');
            const solutionLoading = document.getElementById('solutionLoading');
            
            // TTS 생성 중일 때는 문제 분석 스피너 숨김
            if (solutionLoading) {
                solutionLoading.style.display = 'none';
            }
            
            narrationText.style.display = 'none'; // 생성 시작 시 대본 숨김
            ttsProgress.style.display = 'block';
            ttsProgressText.textContent = '단계별 TTS 대본 생성 중...';
            ttsProgressBar.style.width = '0%';

            try {
                // 절차기억 나레이션 생성 (단계별 TTS 전용 대본)
                const response = await fetch('generate_dialog_narration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        interactionId: currentInteractionId,
                        solution: currentSolution,
                        generateTTS: 'true'
                    })
                });

                // 진행상황 업데이트
                ttsProgressBar.style.width = '50%';
                ttsProgressText.textContent = 'TTS 대본 생성 중...';

                const data = await response.json();
                
                if (data.success) {
                    // 단계별 TTS 전용 대본 저장
                    currentNarration = data.narrationText;
                    
                    // TTS 대본만 화면에 표시 (해설은 숨김)
                    narrationText.textContent = currentNarration;
                    narrationText.style.display = 'block'; // 생성 완료 후 대본 표시
                    narrationContent.style.display = 'block';
                    solutionContent.style.display = 'none';

                    console.log('[teachingagent.php] 단계별 TTS 대본 생성 완료');
                    console.log('[teachingagent.php] 섹션 수:', data.sectionCount || 0);
                    console.log('[teachingagent.php] 음성 파일:', data.sectionFiles || []);

                    // 진행상황 업데이트
                    ttsProgressBar.style.width = '75%';
                    ttsProgressText.textContent = 'TTS 음성 파일 생성 중...';

                    // Step-by-step TTS 음성 생성 (단계별 TTS 대본 기준)
                    if (data.sectionFiles && data.sectionFiles.length > 0) {
                        console.log('[teachingagent.php] Step-by-step TTS 감지, 음성 생성 시작');

                        // 각 섹션별로 TTS 생성 진행상황 표시
                        const sections = currentNarration.split('@').filter(s => s.trim());
                        let completedSections = 0;

                        // Store section data globally for modal player
                        window.currentStepAudioData = {
                            sectionFiles: data.sectionFiles,
                            narrationText: currentNarration,
                            contentsid: currentInteractionId,
                            contentstype: 1 // essay_instruction
                        };

                        // 각 섹션별 TTS 생성 완료 시 진행상황 업데이트
                        const totalSections = sections.length;
                        completedSections = data.sectionFiles.length; // 이미 생성된 파일 수

                        // 진행상황 완료
                        ttsProgressBar.style.width = '100%';
                        ttsProgressText.textContent = `✅ TTS 생성 완료 (${completedSections}/${totalSections} 섹션)`;
                        
                        setTimeout(() => {
                            ttsProgress.style.display = 'none';
                            narrationText.style.display = 'block'; // 생성 완료 후 대본 표시
                        }, 2000);

                        // Open step player modal automatically
                        if (typeof StepPlayer !== 'undefined' && StepPlayer.open) {
                            StepPlayer.open(currentInteractionId);
                            console.log('[teachingagent.php] Step player modal opened successfully');
                        } else {
                            console.error('[teachingagent.php] StepPlayer not loaded or open() method missing');
                        }
                    } else {
                        // 섹션 파일이 없는 경우
                        ttsProgressBar.style.width = '100%';
                        ttsProgressText.textContent = '✅ TTS 대본 생성 완료';
                        setTimeout(() => {
                            ttsProgress.style.display = 'none';
                            narrationText.style.display = 'block'; // 생성 완료 후 대본 표시
                        }, 2000);
                    }
                } else {
                    const errorMsg = data.message || data.error || '나레이션 생성 실패';
                    const errorDetails = data.errorDetails || {};
                    const fullErrorMsg = errorDetails.file && errorDetails.line 
                        ? `[${errorDetails.file}:${errorDetails.line}] ${errorMsg}`
                        : errorMsg;
                    console.error('나레이션 생성 실패:', fullErrorMsg, errorDetails);
                    throw new Error(fullErrorMsg);
                }
            } catch (error) {
                ttsProgress.style.display = 'none';
                console.error('[teachingagent.php] generateNarration 에러:', error);
                throw error;
            }
        }

        // TTS 생성 함수는 더 이상 사용하지 않음
        // TTS 생성은 generateNarration() 함수 내에서 단계별 TTS 전용 대본을 기준으로 자동 처리됨

        // 메시지 전송 함수
        async function sendMessage() {
            if (!currentInteractionId) {
                console.error('currentInteractionId is null or undefined');
                throw new Error('상호작용 ID가 없습니다. 먼저 문제 분석을 완료해주세요.');
            }
            
            // 자동 메시지 생성
            const autoMessage = `안녕하세요! 선생님이 문제 해설을 준비했습니다. 
            
📚 문제 유형: ${problemType.options[problemType.selectedIndex].text}
🎯 해설 완료: ${new Date().toLocaleString()}
🔊 음성 설명이 포함되어 있습니다.

아래 '나의 풀이 메시지함'에서 상세한 설명을 확인하세요!`;

            // 메시지 전송 API 호출
            const response = await fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    studentId: '<?php echo $studentid; ?>',
                    teacherId: '<?php echo $userid; ?>',
                    interactionId: currentInteractionId,
                    message: autoMessage,
                    solutionText: currentSolution,
                    narrationText: currentNarration,
                    audioUrl: currentAudioUrl
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || '메시지 전송 실패');
            }
            
            console.log('Message sent successfully:', data);
            
            // 상태를 completed로 업데이트
            try {
                const updateResponse = await fetch('save_interaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_status',
                        interactionId: currentInteractionId,
                        status: 'completed'
                    })
                });
                
                const updateData = await updateResponse.json();
                if (updateData.success) {
                    console.log('상태 업데이트 완료');
                    
                    // 현재 처리한 항목을 DOM에서 즉시 제거
                    const currentItem = document.querySelector(`[data-id="${currentInteractionId}"]`);
                    if (currentItem) {
                        currentItem.style.transition = 'opacity 0.3s';
                        currentItem.style.opacity = '0';
                        setTimeout(() => {
                            currentItem.remove();
                            // 목록이 비었는지 확인
                            const requestsListElement = document.getElementById('newRequestsList');
                            if (requestsListElement && requestsListElement.children.length === 0) {
                                requestsListElement.innerHTML = '<div class="no-history">새로운 풀이요청이 없습니다.</div>';
                            }
                        }, 300);
                    }
                    
                    // 새로운 요청 목록 새로고침
                    setTimeout(() => {
                        loadNewRequests();
                        // 완료된 항목 목록도 새로고침
                        loadCompletedRequests();
                    }, 1000);
                } else {
                    console.error('상태 업데이트 실패:', updateData.error);
                }
            } catch (error) {
                console.error('상태 업데이트 중 오류:', error);
            }
        }

        // 모달 관련 이벤트 리스너 (학생 모드가 아닐 때만)
        if (modalClose) {
            modalClose.addEventListener('click', () => {
                if (messageModal) {
                    messageModal.style.display = 'none';
                }
            });
        }

        if (cancelSendBtn) {
            cancelSendBtn.addEventListener('click', () => {
                if (messageModal) {
                    messageModal.style.display = 'none';
                }
            });
        }

        if (confirmSendBtn) {
            confirmSendBtn.addEventListener('click', async () => {
            const additionalMessage = messageText.value;
            
            try {
                // API 호출 (실제 구현시 백엔드 엔드포인트로 변경)
                // const response = await fetch('/api/send-message', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json'
                //     },
                //     body: JSON.stringify({
                //         studentId: '<?php echo $studentid; ?>',
                //         solution: currentSolution,
                //         audioUrl: currentAudioUrl,
                //         additionalMessage: additionalMessage
                //     })
                // });

                alert('메시지가 성공적으로 전송되었습니다.');
                if (messageModal) {
                    messageModal.style.display = 'none';
                }
                if (processStatus) {
                    processStatus.textContent = '전송 완료';
                }
                
                // 새로운 요청 새로고침
                if (!isStudentMode) {
                    loadNewRequests();
                    // 완료된 항목 목록도 새로고침
                    loadCompletedRequests();
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('메시지 전송 중 오류가 발생했습니다.');
            }
            });
        }

        // 응답하기 버튼 이벤트 (학생에게 풀이 전송)
        if (sendMessageBtn) {
            sendMessageBtn.addEventListener('click', async () => {
                if (!currentInteractionId) {
                    alert('상호작용 ID가 없습니다. 먼저 문제 분석을 완료해주세요.');
                    return;
                }

                try {
                    // 응답하기 버튼 비활성화 (중복 클릭 방지)
                    sendMessageBtn.disabled = true;
                    sendMessageBtn.textContent = '전송 중...';

                    // sendMessage 함수 호출
                    await sendMessage();

                    // 성공 메시지
                    alert('풀이가 학생에게 성공적으로 전송되었습니다!');

                    // 프로세스 상태 업데이트
                    if (processStatus) {
                        processStatus.innerHTML = '<span style="color: #27ae60;">✅ 학생에게 풀이 전송 완료</span>';
                    }

                } catch (error) {
                    console.error('[sendMessageBtn] 오류:', error);
                    alert('전송 중 오류가 발생했습니다: ' + error.message);

                    // 버튼 다시 활성화
                    sendMessageBtn.disabled = false;
                    sendMessageBtn.textContent = '응답하기';
                }
            });
        }

        // 컨텐츠 저장
        if (saveContentBtn) {
            saveContentBtn.addEventListener('click', async () => {
            if (!currentSolution) return;

            try {
                // API 호출 (실제 구현시 백엔드 엔드포인트로 변경)
                // const response = await fetch('/api/save-content', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json'
                //     },
                //     body: JSON.stringify({
                //         studentId: '<?php echo $studentid; ?>',
                //         problemType: problemType.value,
                //         solution: currentSolution,
                //         audioUrl: currentAudioUrl
                //     })
                // });

                alert('컨텐츠가 저장되었습니다.');
                if (processStatus) {
                    processStatus.textContent = '저장 완료';
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('컨텐츠 저장 중 오류가 발생했습니다.');
            }
            });
        }

        // 초기화 함수 (필요시 호출)
        function clearForm() {
            uploadedFile = null;
            imagePreview.src = '';
            imagePreview.style.display = 'none';
            solutionContent.innerHTML = '해설이 여기에 표시됩니다.';
            audioPlayer.style.display = 'none';
            audioElementPlayer.src = '';
            startTutoringBtn.disabled = true;
            startTutoringBtn.style.display = 'none';
            problemType.value = '';
            processStatus.textContent = '대기중';
            currentSolution = '';
            currentNarration = '';
            currentAudioUrl = '';
            currentInteractionId = null;
            // 추가 프롬프트 입력창 초기화
            const additionalPromptInput = document.getElementById('additionalPrompt');
            if (additionalPromptInput) {
                additionalPromptInput.value = '';
            }
            narrationContent.style.display = 'none';
            narrationText.textContent = '';
            playAudioBtn.style.display = 'none';
            pauseAudioBtn.style.display = 'none';
            if (audioElement) {
                audioElement.pause();
                audioElement.src = '';
            }
            
            // 선생님 설명 보기 버튼 제거
            const viewExplanationBtn = document.getElementById('viewExplanationBtn');
            if (viewExplanationBtn) {
                viewExplanationBtn.remove();
            }
        }

        // 히스토리 추가
        function addToHistory() {
            const now = new Date();
            const dateStr = now.toLocaleDateString('ko-KR');
            const timeStr = now.toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' });
            
            const historyItem = document.createElement('div');
            historyItem.className = 'history-item';
            historyItem.innerHTML = `
                <div class="history-date">${dateStr} ${timeStr}</div>
                <div class="history-title">
                    문제 해설
                    <span class="history-type type-${problemType.value}">${problemType.options[problemType.selectedIndex].text}</span>
                </div>
            `;
            
            historyList.insertBefore(historyItem, historyList.firstChild);
            
            // 최대 10개까지만 표시
            while (historyList.children.length > 10) {
                historyList.removeChild(historyList.lastChild);
            }
        }

        
        // 새로운 풀이요청 로드 함수
        async function loadNewRequests() {
            // newRequestsList 요소 다시 찾기 (DOM이 변경되었을 수 있음)
            const requestsListElement = document.getElementById('newRequestsList');
            if (!requestsListElement) {
                console.error('[loadNewRequests] newRequestsList element not found in DOM');
                return;
            }
            
            // 전역 변수 업데이트
            if (requestsListElement !== newRequestsList) {
                console.log('[loadNewRequests] Updating newRequestsList reference');
                window.newRequestsList = requestsListElement;
            }
            
            try {
                // 실제 로그인한 사용자 ID 사용 (PHP $USER->id)
                const teacherId = <?php echo $USER->id; ?>;
                const url = `get_new_requests.php?teacherid=${teacherId}`;
                console.log('[loadNewRequests] Loading new requests from:', url);
                console.log('[loadNewRequests] Teacher ID:', teacherId);
                
                const response = await fetch(url);
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('[loadNewRequests] HTTP error! status:', response.status, 'Response:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('[loadNewRequests] Response:', data);
                console.log('[loadNewRequests] Success:', data.success);
                console.log('[loadNewRequests] Requests count:', data.requests ? data.requests.length : 0);
                console.log('[loadNewRequests] Total:', data.total);
                
                if (data.success) {
                    if (data.requests && data.requests.length > 0) {
                        console.log('[loadNewRequests] Found', data.requests.length, 'new requests');
                        displayNewRequests(data.requests);
                    } else {
                        console.log('[loadNewRequests] No new requests found (empty array)');
                        displayNewRequests([]);
                    }
                } else {
                    console.error('[loadNewRequests] API returned error:', data.error);
                    // 에러 메시지도 표시
                    const requestsListElement = document.getElementById('newRequestsList');
                    if (requestsListElement) {
                        requestsListElement.innerHTML = `<div class="no-history" style="color: #d32f2f;">⚠️ 오류: ${data.error || '알 수 없는 오류'}</div>`;
                    }
                }
            } catch (error) {
                console.error('[loadNewRequests] Error loading new requests:', error);
                const requestsListElement = document.getElementById('newRequestsList');
                if (requestsListElement) {
                    requestsListElement.innerHTML = `<div class="no-history" style="color: #d32f2f;">⚠️ 요청 목록을 불러오는 중 오류가 발생했습니다: ${error.message}</div>`;
                }
            }
        }
        
        // 새로운 풀이요청 표시
        function displayNewRequests(items) {
            // newRequestsList 요소 다시 찾기
            const requestsListElement = document.getElementById('newRequestsList');
            if (!requestsListElement) {
                console.error('[displayNewRequests] newRequestsList element not found in DOM');
                return;
            }
            
            console.log('[displayNewRequests] Displaying', items.length, 'items');
            
            // 완료된 항목을 새로운 풀이영역에서 완전히 제거 (강화된 필터링)
            console.log('Before filtering:', items.length, 'items');
            items = items.filter(item => {
                console.log(`Item ${item.id}: status=[${item.status}], type=${typeof item.status}`);
                
                // status 필드 정리 (공백 제거, 소문자 변환)
                const status = (item.status || '').trim().toLowerCase();
                
                // 완료된 상태들을 새로운 풀이영역에서 완전히 제거
                const completedStatuses = [
                    'completed', 'complete', 'sent', 'finished', 'done', 
                    '완료', '전송완료', 'success', 'delivered'
                ];
                if (completedStatuses.includes(status)) {
                    console.log(`Filtering out completed item ${item.id} with status: [${item.status}]`);
                    return false;
                }
                
                // 추가 완료 상태 체크 (다른 필드들도 확인)
                if (item.completion_time || item.sent_time || item.delivered_time) {
                    console.log(`Filtering out completed item ${item.id} based on completion fields`);
                    return false;
                }
                
                // pending, processing, new, received 상태 또는 빈 문자열/NULL인 경우 표시
                const validStatuses = ['pending', 'processing', 'new', 'received', ''];
                if (status && !validStatuses.includes(status)) {
                    console.log(`Filtering out item ${item.id} with non-pending status: [${item.status}]`);
                    return false;
                }
                
                return true;
            });
            console.log('After filtering:', items.length, 'items');
            
            if (items.length === 0) {
                requestsListElement.innerHTML = '<div class="no-history">새로운 풀이요청이 없습니다.</div>';
                return;
            }
            
            requestsListElement.innerHTML = items.map(item => {
                const isReRequest = item.isReRequest;
                const bgColor = isReRequest ? '#fff3e0' : '#f1f8e9';
                const borderColor = isReRequest ? '#ff9800' : '#aed581';
                const labelBg = isReRequest ? '#ff5722' : '#388e3c';
                
                return `
                    <div class="history-item" data-id="${item.id}" style="display: flex; gap: 15px; background: ${bgColor}; border: 2px solid ${borderColor};">
                        <div style="flex: 1; min-width: 0;">
                            <div class="history-item-header">
                                <span class="history-time">${item.timeAgo}</span>
                                <a href="student_inbox.php?studentid=${item.studentId}" style="text-decoration: none;">
                                    <span class="student-name" style="background: ${labelBg}; color: white; padding: 2px 8px; border-radius: 4px; cursor: pointer; transition: opacity 0.2s;">
                                        ${isReRequest ? '🔄 재요청 - ' : '🆕 '} ${item.studentName}
                                    </span>
                                </a>
                            </div>
                            <div class="history-item-content">
                                ${item.problemImage ? `<img src="${getImageUrl(item.problemImage)}" alt="문제 이미지" class="clickable-image" onclick="event.stopPropagation(); openImageModal('${getImageUrl(item.problemImage)}');" style="max-width: 200px; margin-top: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">` : ''}
                                ${isReRequest ? `
                                    <p style="background: #ffccbc; padding: 10px; border-radius: 4px; margin-top: 10px;">
                                        <strong>🔄 재요청 사유:</strong><br>
                                        ${item.reRequestReason}
                                    </p>
                                ` : item.additionalRequest ? `
                                    <p style="background: #fff3cd; padding: 8px; border-radius: 4px; margin-top: 10px;">
                                        <strong>요청사항:</strong> ${item.additionalRequest}
                                    </p>
                                ` : ''}
                            </div>
                        </div>
                        <div class="history-item-actions" style="display: flex; flex-direction: column; gap: 6px; min-width: 280px; max-width: 280px;">
                            <!-- 풀이 스타일 선택 버튼 (팝업 호출) -->
                            <button class="btn btn-sm" 
                                    onclick="openSolutionStyleModal(${item.id}, '${item.problemImage || ''}', ${isReRequest}, '${item.type || ''}')"
                                    style="background: #1976d2; color: white; width: 100%; text-align: left; padding: 10px 12px; font-size: 13px; border-radius: 6px; font-weight: 500; display: flex; justify-content: space-between; align-items: center;">
                                <span>${isReRequest ? '🔄 풀이 스타일 선택' : '✅ 풀이 스타일 선택'}</span>
                                <span style="font-size: 10px;">▶</span>
                            </button>
                            
                            ${item.type === 'askhint' ? `
                            <!-- 힌트 요청인 경우: 힌트 종류 선택 버튼 (팝업 호출) -->
                            <button class="btn btn-sm" onclick="openHintTypeModal(${item.id}, '${item.problemImage || ''}')"
                                    style="background: #00bcd4; color: white; width: 100%; text-align: left; padding: 10px 12px; font-size: 13px; border-radius: 6px; font-weight: 500; border: 2px solid #0097a7; display: flex; justify-content: space-between; align-items: center;">
                                <span>💡 힌트 종류 선택</span>
                                <span style="font-size: 10px;">▶</span>
                            </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        // 새로운 요청 수락하고 풀이 시작
        async function acceptNewRequest(interactionId, problemImage, isReRequest = false, isEditedImage = false) {
            if (!problemImage) {
                alert('문제 이미지를 찾을 수 없습니다.');
                return;
            }
            
            try {
                // teacherid 업데이트 (클릭한 사람의 아이디로)
                const teacherId = <?php echo $USER->id; ?>;
                try {
                    const updateTeacherResponse = await fetch('save_interaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_teacherid',
                            interactionId: interactionId,
                            teacherId: teacherId
                        })
                    });
                    const updateTeacherData = await updateTeacherResponse.json();
                    if (updateTeacherData.success) {
                        console.log('[acceptNewRequest] teacherid 업데이트 완료:', teacherId);
                    } else {
                        console.error('[acceptNewRequest] teacherid 업데이트 실패:', updateTeacherData.error);
                    }
                } catch (updateError) {
                    console.error('[acceptNewRequest] teacherid 업데이트 중 오류:', updateError);
                }
                
                // type을 'asksolution'으로 업데이트 (askhint 타입에서 풀이 스타일 선택 시 힌트가 생성되는 문제 해결)
                try {
                    const updateTypeResponse = await fetch('save_interaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_type',
                            interactionId: interactionId,
                            type: 'asksolution'
                        })
                    });
                    const updateTypeData = await updateTypeResponse.json();
                    if (updateTypeData.success) {
                        console.log('[acceptNewRequest] type을 asksolution으로 업데이트 완료 (이전:', updateTypeData.old_type + ')');
                    } else {
                        console.error('[acceptNewRequest] type 업데이트 실패:', updateTypeData.error);
                    }
                } catch (typeError) {
                    console.error('[acceptNewRequest] type 업데이트 중 오류:', typeError);
                }
                
                // interaction 정보 가져오기
                const infoResponse = await fetch(`get_interaction_data.php?interactionid=${interactionId}`);
                const infoData = await infoResponse.json();
                
                if (infoData.success && infoData.interaction) {
                    const interaction = infoData.interaction;
                    
                    // 추가 요청사항/재요청 사유 설정
                    if (interaction.modification_prompt) {
                        window.modificationPrompt = interaction.modification_prompt;
                        console.log('Modification prompt:', interaction.modification_prompt);
                        
                        // 추가 프롬프트 입력창에 표시
                        const additionalPromptInput = document.getElementById('additionalPrompt');
                        if (additionalPromptInput) {
                            additionalPromptInput.value = interaction.modification_prompt;
                        }
                    }
                    
                    // 문제 유형 설정
                    if (interaction.problem_type) {
                        problemType.value = interaction.problem_type;
                    }
                    
                    // 학생 ID 설정
                    if (interaction.userid) {
                        window.requestedStudentId = interaction.userid;
                    }
                }
                
                // 이미지를 Blob으로 변환
                let blob;
                if (isEditedImage && problemImage.startsWith('data:')) {
                    // 편집된 이미지(Base64)인 경우
                    console.log('[acceptNewRequest] 편집된 이미지(Base64) 처리');
                    blob = await base64ToBlob(problemImage);
                } else {
                    // 일반 URL인 경우
                    const response = await fetch(problemImage);
                    blob = await response.blob();
                }
                
                // File 객체 생성
                const fileName = `problem_${interactionId}.png`;
                uploadedFile = new File([blob], fileName, { type: blob.type || 'image/png' });
                
                // 이미지 미리보기 표시
                imagePreview.src = problemImage;
                imagePreview.style.display = 'block';
                
                // 하이튜터링 시작 버튼 활성화 및 표시
                startTutoringBtn.disabled = false;
                startTutoringBtn.style.display = 'inline-block';
                
                // 기존 interactionId 저장
                currentInteractionId = interactionId;
                
                // 프로세스 상태 업데이트
                if (isReRequest) {
                    processStatus.textContent = '🔄 재요청을 처리합니다...';
                    processStatus.style.color = '#ff5722';
                } else {
                    processStatus.textContent = '🆕 새로운 풀이요청을 처리합니다...';
                    processStatus.style.color = '#388e3c';
                }
                
                // 풀이 시작하기 버튼으로 스크롤
                if (startTutoringBtn) {
                    startTutoringBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                // 하이튜터링 시작
                setTimeout(() => {
                    startTutoringBtn.click();
                }, 1000);
                
                // 새로운 요청 목록 새로고침
                setTimeout(loadNewRequests, 2000);
                
            } catch (error) {
                console.error('이미지 로드 오류:', error);
                alert('이미지를 불러오는 중 오류가 발생했습니다.');
            }
        }

        // 스타일별 풀이 시작 (6가지 스타일)
        async function acceptNewRequestWithStyle(interactionId, problemImage, isReRequest = false, solutionStyle = 'default', isEditedImage = false) {
            if (!problemImage) {
                alert('문제 이미지를 찾을 수 없습니다.');
                return;
            }

            // 스타일 이름 매핑
            const styleNames = {
                'concise': '⚡ 수식·기호·화살표 위주 간결 풀이',
                'textbook': '📖 시중 교재 해설지 스타일',
                'detailed': '📋 단계별 상세 설명 풀이',
                'examples': '💡 예시를 넣어 설명하는 스타일',
                'exam': '🎯 실전풀이(시험장 버전)',
                'concept': '🔑 핵심 개념 강조 풀이',
                'default': '기본 풀이'
            };

            try {
                // teacherid 업데이트 (클릭한 사람의 아이디로)
                const teacherId = <?php echo $USER->id; ?>;
                try {
                    const updateTeacherResponse = await fetch('save_interaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_teacherid',
                            interactionId: interactionId,
                            teacherId: teacherId
                        })
                    });
                    const updateTeacherData = await updateTeacherResponse.json();
                    if (updateTeacherData.success) {
                        console.log('[acceptNewRequestWithStyle] teacherid 업데이트 완료:', teacherId);
                    } else {
                        console.error('[acceptNewRequestWithStyle] teacherid 업데이트 실패:', updateTeacherData.error);
                    }
                } catch (updateError) {
                    console.error('[acceptNewRequestWithStyle] teacherid 업데이트 중 오류:', updateError);
                }

                // type을 'asksolution'으로 업데이트 (askhint 타입에서 풀이 스타일 선택 시 힌트가 생성되는 문제 해결)
                try {
                    const updateTypeResponse = await fetch('save_interaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_type',
                            interactionId: interactionId,
                            type: 'asksolution'
                        })
                    });
                    const updateTypeData = await updateTypeResponse.json();
                    if (updateTypeData.success) {
                        console.log('[acceptNewRequestWithStyle] type을 asksolution으로 업데이트 완료 (이전:', updateTypeData.old_type + ')');
                    } else {
                        console.error('[acceptNewRequestWithStyle] type 업데이트 실패:', updateTypeData.error);
                    }
                } catch (typeError) {
                    console.error('[acceptNewRequestWithStyle] type 업데이트 중 오류:', typeError);
                }

                // interaction 정보 가져오기
                const infoResponse = await fetch(`get_interaction_data.php?interactionid=${interactionId}`);
                const infoData = await infoResponse.json();

                if (infoData.success && infoData.interaction) {
                    const interaction = infoData.interaction;

                    // 추가 요청사항/재요청 사유 설정
                    if (interaction.modification_prompt) {
                        window.modificationPrompt = interaction.modification_prompt;
                        console.log('Modification prompt:', interaction.modification_prompt);

                        // 추가 프롬프트 입력창에 표시
                        const additionalPromptInput = document.getElementById('additionalPrompt');
                        if (additionalPromptInput) {
                            additionalPromptInput.value = interaction.modification_prompt;
                        }
                    }

                    // 문제 유형 설정
                    if (interaction.problem_type) {
                        problemType.value = interaction.problem_type;
                    }

                    // 학생 ID 설정
                    if (interaction.userid) {
                        window.requestedStudentId = interaction.userid;
                    }
                }

                // 선택된 스타일 저장 (전역 변수)
                window.selectedSolutionStyle = solutionStyle;
                console.log('[acceptNewRequestWithStyle] 선택된 풀이 스타일:', solutionStyle, '-', styleNames[solutionStyle]);

                // 이미지를 Blob으로 변환
                let blob;
                if (isEditedImage && problemImage.startsWith('data:')) {
                    // 편집된 이미지(Base64)인 경우
                    console.log('[acceptNewRequestWithStyle] 편집된 이미지(Base64) 처리');
                    blob = await base64ToBlob(problemImage);
                } else {
                    // 일반 URL인 경우
                    const response = await fetch(problemImage);
                    blob = await response.blob();
                }

                // File 객체 생성
                const fileName = `problem_${interactionId}.png`;
                uploadedFile = new File([blob], fileName, { type: blob.type || 'image/png' });

                // 이미지 미리보기 표시
                imagePreview.src = problemImage;
                imagePreview.style.display = 'block';

                // 하이튜터링 시작 버튼 활성화 및 표시
                startTutoringBtn.disabled = false;
                startTutoringBtn.style.display = 'inline-block';

                // 기존 interactionId 저장
                currentInteractionId = interactionId;

                // 프로세스 상태 업데이트 (스타일 이름 포함)
                if (isReRequest) {
                    processStatus.textContent = `🔄 재요청을 ${styleNames[solutionStyle]}로 처리합니다...`;
                    processStatus.style.color = '#ff5722';
                } else {
                    processStatus.textContent = `🆕 ${styleNames[solutionStyle]}로 풀이합니다...`;
                    processStatus.style.color = '#388e3c';
                }

                // 풀이 시작하기 버튼으로 스크롤
                if (startTutoringBtn) {
                    startTutoringBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                // 하이튜터링 시작
                setTimeout(() => {
                    startTutoringBtn.click();
                }, 1000);

                // 새로운 요청 목록 새로고침
                setTimeout(loadNewRequests, 2000);

            } catch (error) {
                console.error('이미지 로드 오류:', error);
                alert('이미지를 불러오는 중 오류가 발생했습니다.');
            }
        }

        // 다른 풀이 입력 모달 관련 변수
        let customSolutionData = {
            interactionId: null,
            problemImage: null,
            isReRequest: false
        };

        // 다른 풀이 입력 모달 열기
        function openCustomSolutionModal(interactionId, problemImage, isReRequest = false) {
            console.log('[openCustomSolutionModal] 호출:', { interactionId, problemImage, isReRequest });

            // 데이터 저장
            customSolutionData.interactionId = interactionId;
            customSolutionData.problemImage = problemImage;
            customSolutionData.isReRequest = isReRequest;

            // 모달 표시
            const modal = document.getElementById('customSolutionModal');
            const textarea = document.getElementById('customSolutionText');

            if (modal && textarea) {
                modal.style.display = 'flex';
                textarea.value = ''; // 초기화
                textarea.focus();
            } else {
                console.error('[openCustomSolutionModal] 모달 또는 textarea 요소를 찾을 수 없습니다.');
            }
        }

        // ========================================
        // 풀이 스타일 선택 팝업 관련
        // ========================================
        let solutionStyleData = {
            interactionId: null,
            problemImage: null,
            isReRequest: false,
            type: ''
        };

        // 풀이 스타일 선택 팝업 열기
        function openSolutionStyleModal(interactionId, problemImage, isReRequest = false, type = '') {
            console.log('[openSolutionStyleModal] 호출:', { interactionId, problemImage, isReRequest, type });
            
            // 데이터 저장
            solutionStyleData.interactionId = interactionId;
            solutionStyleData.problemImage = problemImage;
            solutionStyleData.isReRequest = isReRequest;
            solutionStyleData.type = type;
            
            // type이 'capture'인 경우 이미지 편집 모달 먼저 표시
            if (type === 'capture' && problemImage) {
                console.log('[openSolutionStyleModal] type=capture, 이미지 편집 모달 열기');
                openImageEditModal(problemImage);
                return;
            }
            
            // 모달 표시
            const modal = document.getElementById('solutionStyleModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        // 풀이 스타일 선택 팝업 닫기
        function closeSolutionStyleModal() {
            const modal = document.getElementById('solutionStyleModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // 풀이 스타일 선택 처리
        function selectSolutionStyle(style) {
            console.log('[selectSolutionStyle] 선택된 스타일:', style);
            
            closeSolutionStyleModal();
            
            // 편집된 이미지가 있으면 사용, 없으면 원본 이미지 사용
            const imageToUse = solutionStyleData.editedImage || solutionStyleData.problemImage;
            const hasEditedImage = !!solutionStyleData.editedImage;
            
            console.log('[selectSolutionStyle] 편집된 이미지 사용:', hasEditedImage);
            
            if (style === 'default') {
                acceptNewRequest(solutionStyleData.interactionId, imageToUse, solutionStyleData.isReRequest, hasEditedImage);
            } else {
                acceptNewRequestWithStyle(solutionStyleData.interactionId, imageToUse, solutionStyleData.isReRequest, style, hasEditedImage);
            }
            
            // 사용 후 편집된 이미지 데이터 초기화
            solutionStyleData.editedImage = null;
        }

        // 스타일 모달에서 다른 풀이 입력으로 전환
        function openCustomSolutionFromStyleModal() {
            closeSolutionStyleModal();
            // 편집된 이미지가 있으면 사용
            const imageToUse = solutionStyleData.editedImage || solutionStyleData.problemImage;
            openCustomSolutionModal(solutionStyleData.interactionId, imageToUse, solutionStyleData.isReRequest);
            solutionStyleData.editedImage = null;
        }

        // ========================================
        // 이미지 편집 모달 관련 (type=capture용)
        // ========================================
        let imageEditData = {
            canvas: null,
            ctx: null,
            originalImage: null,
            currentScale: 100,
            isEraserOn: false,
            eraserSize: 30,
            isDrawing: false,
            history: [],
            maxHistory: 20
        };

        // 이미지 편집 모달 열기
        function openImageEditModal(imageUrl) {
            console.log('[openImageEditModal] 이미지 URL:', imageUrl);
            
            const modal = document.getElementById('imageEditModal');
            const canvas = document.getElementById('imageEditCanvas');
            const ctx = canvas.getContext('2d');
            
            imageEditData.canvas = canvas;
            imageEditData.ctx = ctx;
            imageEditData.currentScale = 100;
            imageEditData.isEraserOn = false;
            imageEditData.history = [];
            
            // 지우개 상태 초기화
            document.getElementById('eraserToggleBtn').textContent = 'OFF';
            document.getElementById('eraserToggleBtn').style.background = '';
            document.getElementById('imageSizePercent').textContent = '100%';
            
            // 이미지 로드
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                imageEditData.originalImage = img;
                
                // 캔버스 크기 설정
                canvas.width = img.width;
                canvas.height = img.height;
                
                // 이미지 그리기
                ctx.drawImage(img, 0, 0);
                
                // 초기 상태 저장
                saveHistory();
                
                // 이벤트 리스너 설정
                setupCanvasEvents();
                
                console.log('[openImageEditModal] 이미지 로드 완료:', img.width, 'x', img.height);
            };
            img.onerror = function() {
                console.error('[openImageEditModal] 이미지 로드 실패:', imageUrl);
                alert('이미지를 불러올 수 없습니다.');
            };
            img.src = getImageUrl(imageUrl);
            
            modal.style.display = 'flex';
        }

        // 이미지 편집 모달 닫기
        function closeImageEditModal() {
            const modal = document.getElementById('imageEditModal');
            if (modal) {
                modal.style.display = 'none';
            }
            // 이벤트 리스너 제거
            removeCanvasEvents();
        }

        // 캔버스 이벤트 설정
        function setupCanvasEvents() {
            const canvas = imageEditData.canvas;
            if (!canvas) return;
            
            canvas.addEventListener('mousedown', startErase);
            canvas.addEventListener('mousemove', erase);
            canvas.addEventListener('mouseup', stopErase);
            canvas.addEventListener('mouseleave', stopErase);
            
            // 터치 이벤트 지원
            canvas.addEventListener('touchstart', handleTouchStart);
            canvas.addEventListener('touchmove', handleTouchMove);
            canvas.addEventListener('touchend', stopErase);
        }

        // 캔버스 이벤트 제거
        function removeCanvasEvents() {
            const canvas = imageEditData.canvas;
            if (!canvas) return;
            
            canvas.removeEventListener('mousedown', startErase);
            canvas.removeEventListener('mousemove', erase);
            canvas.removeEventListener('mouseup', stopErase);
            canvas.removeEventListener('mouseleave', stopErase);
            canvas.removeEventListener('touchstart', handleTouchStart);
            canvas.removeEventListener('touchmove', handleTouchMove);
            canvas.removeEventListener('touchend', stopErase);
        }

        // 터치 이벤트 핸들러
        function handleTouchStart(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            startErase(mouseEvent);
        }

        function handleTouchMove(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            erase(mouseEvent);
        }

        // 지우개 시작
        function startErase(e) {
            if (!imageEditData.isEraserOn) return;
            imageEditData.isDrawing = true;
            erase(e);
        }

        // 지우기
        function erase(e) {
            if (!imageEditData.isDrawing || !imageEditData.isEraserOn) return;
            
            const canvas = imageEditData.canvas;
            const ctx = imageEditData.ctx;
            const rect = canvas.getBoundingClientRect();
            
            // 스케일 보정
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            
            const x = (e.clientX - rect.left) * scaleX;
            const y = (e.clientY - rect.top) * scaleY;
            
            // 지우개 크기도 스케일에 맞춰 조정
            const eraserSize = imageEditData.eraserSize * Math.max(scaleX, scaleY);
            
            // 흰색으로 지우기
            ctx.fillStyle = 'white';
            ctx.beginPath();
            ctx.arc(x, y, eraserSize / 2, 0, Math.PI * 2);
            ctx.fill();
        }

        // 지우기 중단
        function stopErase() {
            if (imageEditData.isDrawing) {
                imageEditData.isDrawing = false;
                // 히스토리 저장
                saveHistory();
            }
        }

        // 지우개 토글
        function toggleEraser() {
            imageEditData.isEraserOn = !imageEditData.isEraserOn;
            const btn = document.getElementById('eraserToggleBtn');
            if (imageEditData.isEraserOn) {
                btn.textContent = 'ON';
                btn.style.background = '#f44336';
                btn.style.color = 'white';
                imageEditData.canvas.style.cursor = 'crosshair';
            } else {
                btn.textContent = 'OFF';
                btn.style.background = '';
                btn.style.color = '';
                imageEditData.canvas.style.cursor = 'default';
            }
        }

        // 지우개 크기 업데이트
        function updateEraserSize() {
            const size = document.getElementById('eraserSize').value;
            imageEditData.eraserSize = parseInt(size);
            document.getElementById('eraserSizeValue').textContent = size + 'px';
        }

        // 이미지 크기 조정
        function adjustImageSize(delta) {
            let newScale = imageEditData.currentScale + delta;
            newScale = Math.max(10, Math.min(200, newScale));
            imageEditData.currentScale = newScale;
            
            document.getElementById('imageSizePercent').textContent = newScale + '%';
            
            // 캔버스 크기 조정 (원본 비율 유지)
            const canvas = imageEditData.canvas;
            const img = imageEditData.originalImage;
            if (!img) return;
            
            const newWidth = img.width * (newScale / 100);
            const newHeight = img.height * (newScale / 100);
            
            // 현재 캔버스 내용 저장
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height;
            tempCanvas.getContext('2d').drawImage(canvas, 0, 0);
            
            // 캔버스 크기 변경
            canvas.width = newWidth;
            canvas.height = newHeight;
            
            // 기존 내용 다시 그리기 (스케일 적용)
            const ctx = canvas.getContext('2d');
            ctx.drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, 0, 0, newWidth, newHeight);
            
            imageEditData.ctx = ctx;
        }

        // 원본 크기로 리셋
        function resetImageSize() {
            const img = imageEditData.originalImage;
            if (!img) return;
            
            imageEditData.currentScale = 100;
            document.getElementById('imageSizePercent').textContent = '100%';
            
            const canvas = imageEditData.canvas;
            canvas.width = img.width;
            canvas.height = img.height;
            
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            imageEditData.ctx = ctx;
            
            // 히스토리 초기화
            imageEditData.history = [];
            saveHistory();
        }

        // 히스토리 저장
        function saveHistory() {
            const canvas = imageEditData.canvas;
            if (!canvas) return;
            
            // 최대 히스토리 개수 제한
            if (imageEditData.history.length >= imageEditData.maxHistory) {
                imageEditData.history.shift();
            }
            
            imageEditData.history.push(canvas.toDataURL());
        }

        // 되돌리기
        function undoErase() {
            if (imageEditData.history.length <= 1) {
                console.log('[undoErase] 더 이상 되돌릴 수 없습니다.');
                return;
            }
            
            // 현재 상태 제거
            imageEditData.history.pop();
            
            // 이전 상태 복원
            const previousState = imageEditData.history[imageEditData.history.length - 1];
            
            const img = new Image();
            img.onload = function() {
                const canvas = imageEditData.canvas;
                const ctx = imageEditData.ctx;
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
            };
            img.src = previousState;
        }

        // 이미지 편집 확인 후 스타일 선택 모달 열기
        function confirmImageEdit() {
            const canvas = imageEditData.canvas;
            if (!canvas) {
                closeImageEditModal();
                return;
            }
            
            // 편집된 이미지를 Base64로 저장
            const editedImageData = canvas.toDataURL('image/png');
            
            // solutionStyleData에 편집된 이미지 저장
            solutionStyleData.editedImage = editedImageData;
            
            console.log('[confirmImageEdit] 편집된 이미지 저장 완료');
            
            // 이미지 편집 모달 닫기
            closeImageEditModal();
            
            // 풀이 스타일 선택 모달 열기
            const modal = document.getElementById('solutionStyleModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        // Base64 데이터를 Blob으로 변환하는 헬퍼 함수
        async function base64ToBlob(base64Data) {
            // data:image/png;base64,... 형식에서 실제 base64 데이터 추출
            const parts = base64Data.split(',');
            const mimeType = parts[0].match(/:(.*?);/)?.[1] || 'image/png';
            const base64String = parts[1];
            
            // Base64를 바이너리로 변환
            const byteCharacters = atob(base64String);
            const byteNumbers = new Array(byteCharacters.length);
            
            for (let i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            
            const byteArray = new Uint8Array(byteNumbers);
            return new Blob([byteArray], { type: mimeType });
        }

        // ========================================
        // 힌트 종류 선택 팝업 관련
        // ========================================
        let hintTypeData = {
            interactionId: null,
            problemImage: null
        };

        // 힌트 종류 선택 팝업 열기
        function openHintTypeModal(interactionId, problemImage) {
            console.log('[openHintTypeModal] 호출:', { interactionId, problemImage });
            
            // 데이터 저장
            hintTypeData.interactionId = interactionId;
            hintTypeData.problemImage = problemImage;
            
            // 모달 표시
            const modal = document.getElementById('hintTypeModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        // 힌트 종류 선택 팝업 닫기
        function closeHintTypeModal() {
            const modal = document.getElementById('hintTypeModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // 힌트 종류 선택 처리
        function selectHintType(hintLevel) {
            console.log('[selectHintType] 선택된 힌트 레벨:', hintLevel);
            
            closeHintTypeModal();
            
            // 힌트 레벨과 함께 generateHint 호출
            generateHint(hintTypeData.interactionId, hintTypeData.problemImage, hintLevel);
        }

        // ========================================
        // 직접 힌트 입력 모달 관련
        // ========================================
        let customHintData = {
            interactionId: null,
            problemImage: null
        };

        // 힌트 타입 모달에서 직접 힌트 입력으로 전환
        function openCustomHintFromTypeModal() {
            closeHintTypeModal();
            openCustomHintModal(hintTypeData.interactionId, hintTypeData.problemImage);
        }

        // 직접 힌트 입력 모달 열기
        function openCustomHintModal(interactionId, problemImage) {
            console.log('[openCustomHintModal] 모달 열기:', { interactionId, problemImage });
            
            // 데이터 저장
            customHintData.interactionId = interactionId;
            customHintData.problemImage = problemImage;
            
            // 모달 표시
            const modal = document.getElementById('customHintModal');
            const textarea = document.getElementById('customHintText');
            
            if (modal && textarea) {
                modal.style.display = 'flex';
                textarea.value = ''; // 초기화
                textarea.focus();
            }
        }

        // 직접 힌트 입력 모달 닫기
        function closeCustomHintModal() {
            const modal = document.getElementById('customHintModal');
            if (modal) {
                modal.style.display = 'none';
            }
            
            // 데이터 초기화
            customHintData = {
                interactionId: null,
                problemImage: null
            };
        }

        // 직접 입력한 힌트로 TTS 생성
        async function submitCustomHint() {
            const textarea = document.getElementById('customHintText');
            const customHintText = textarea ? textarea.value.trim() : '';
            
            if (!customHintText) {
                alert('힌트 내용을 입력해주세요.');
                return;
            }
            
            if (!customHintData.interactionId) {
                alert('상호작용 ID가 없습니다.');
                return;
            }
            
            // 모달 닫기 전에 데이터를 로컬 변수에 저장 (closeCustomHintModal에서 초기화되므로)
            const savedInteractionId = customHintData.interactionId;
            const savedProblemImage = customHintData.problemImage;
            
            console.log('[submitCustomHint] 직접 힌트 제출:', {
                interactionId: savedInteractionId,
                hintLength: customHintText.length
            });
            
            // 모달 닫기
            closeCustomHintModal();
            
            // 현재 상호작용 ID 설정
            currentInteractionId = savedInteractionId;
            
            // 문제 이미지 로드
            const imagePreview = document.getElementById('imagePreview');
            if (savedProblemImage && imagePreview) {
                imagePreview.src = getImageUrl(savedProblemImage);
                imagePreview.style.display = 'block';
            }
            
            // 우측 패널 초기화
            const solutionContent = document.getElementById('solutionContent');
            const narrationContent = document.getElementById('narrationContent');
            const narrationText = document.getElementById('narrationText');
            
            if (solutionContent) solutionContent.style.display = 'none';
            if (narrationContent) narrationContent.style.display = 'block';
            if (narrationText) {
                narrationText.innerHTML = '✍️ 직접 입력한 힌트로 TTS를 생성 중입니다...';
            }
            
            // 프로세스 상태 업데이트
            const processStatus = document.getElementById('processStatus');
            if (processStatus) {
                processStatus.innerHTML = '<span style="color: #9c27b0;">✍️ 직접 입력 힌트 TTS 생성 중...</span>';
            }
            
            try {
                // TTS 프로그레스 표시
                const ttsProgress = document.getElementById('ttsProgress');
                const ttsProgressText = document.getElementById('ttsProgressText');
                const ttsProgressBar = document.getElementById('ttsProgressBar');
                
                if (ttsProgress) ttsProgress.style.display = 'block';
                if (ttsProgressText) ttsProgressText.textContent = '직접 입력 힌트 TTS 생성 중...';
                if (ttsProgressBar) ttsProgressBar.style.width = '30%';
                
                // generate_dialog_narration.php 호출 (customHint=true 전달)
                const response = await fetch('generate_dialog_narration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        interactionId: savedInteractionId,
                        solution: customHintText,  // 직접 입력한 힌트를 solution으로 전달
                        generateTTS: 'true',
                        customSolution: 'true',  // 직접 입력 모드
                        hintLevel: 'custom'  // 커스텀 힌트임을 표시
                    })
                });
                
                const data = await response.json();
                console.log('[submitCustomHint] 응답:', data);
                
                if (data.success) {
                    if (ttsProgressBar) ttsProgressBar.style.width = '100%';
                    if (ttsProgressText) ttsProgressText.textContent = 'TTS 생성 완료!';
                    
                    // 나레이션 표시
                    if (narrationText && data.narration) {
                        narrationText.innerHTML = data.narration.replace(/\n/g, '<br>');
                        narrationText.style.display = 'block';
                    }
                    
                    // 오디오 플레이어 표시
                    if (data.audioUrls && data.audioUrls.length > 0) {
                        const audioPlayer = document.getElementById('audioPlayer');
                        if (audioPlayer) {
                            audioPlayer.src = data.audioUrls[0];
                            audioPlayer.style.display = 'block';
                        }
                    }
                    
                    if (processStatus) {
                        processStatus.innerHTML = '<span style="color: #4caf50;">✅ 직접 입력 힌트 TTS 생성 완료</span>';
                    }
                    
                    // 상태 업데이트
                    try {
                        await fetch('update_interaction.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'update_status',
                                interactionId: savedInteractionId,
                                status: 'completed'
                            })
                        });
                    } catch (statusError) {
                        console.error('[submitCustomHint] 상태 업데이트 오류:', statusError);
                    }
                    
                    // 목록 새로고침
                    setTimeout(loadNewRequests, 1000);
                    setTimeout(loadCompletedRequests, 1000);
                    
                } else {
                    throw new Error(data.error || '힌트 TTS 생성 실패');
                }
                
            } catch (error) {
                console.error('[submitCustomHint] 오류:', error);
                alert('직접 힌트 TTS 생성 중 오류가 발생했습니다: ' + error.message);
                
                if (processStatus) {
                    processStatus.innerHTML = '<span style="color: #f44336;">❌ 힌트 TTS 생성 오류</span>';
                }
            }
        }

        // 드롭다운 토글 함수
        function toggleStyleDropdown(button) {
            const dropdown = button.nextElementSibling;
            const isOpen = dropdown.style.display === 'block';
            
            // 다른 모든 드롭다운 닫기
            closeAllDropdowns();
            
            // 현재 드롭다운 토글
            if (!isOpen) {
                dropdown.style.display = 'block';
            }
        }
        
        // 모든 드롭다운 닫기
        function closeAllDropdowns() {
            document.querySelectorAll('.style-dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
        
        // 드롭다운 외부 클릭 시 닫기
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.style-dropdown')) {
                closeAllDropdowns();
            }
        });

        // 다른 풀이 입력 모달 닫기
        function closeCustomSolutionModal() {
            const modal = document.getElementById('customSolutionModal');
            if (modal) {
                modal.style.display = 'none';
            }

            // 데이터 초기화
            customSolutionData = {
                interactionId: null,
                problemImage: null,
                isReRequest: false
            };
        }

        // 힌트 생성하기 (type='askhint'인 경우 사용)
        // hintLevel: 'explain' (문제해설), 'early' (초반풀이), 'middle' (중반풀이), 'full' (전체해설)
        async function generateHint(interactionId, problemImage, hintLevel = 'early') {
            console.log('[generateHint] 힌트 생성 시작:', { interactionId, problemImage, hintLevel });
            
            // 힌트 레벨별 이름
            const hintLevelNames = {
                'explain': '📖 문제해설',
                'early': '🔰 초반풀이',
                'middle': '📝 중반풀이',
                'full': '📋 전체해설'
            };
            
            if (!interactionId) {
                alert('상호작용 ID가 없습니다.');
                return;
            }
            
            try {
                // teacherid 업데이트
                const teacherId = <?php echo $USER->id; ?>;
                try {
                    const updateTeacherResponse = await fetch('save_interaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_teacherid',
                            interactionId: interactionId,
                            teacherId: teacherId
                        })
                    });
                    const updateTeacherData = await updateTeacherResponse.json();
                    if (updateTeacherData.success) {
                        console.log('[generateHint] teacherid 업데이트 완료:', teacherId);
                    }
                } catch (updateError) {
                    console.error('[generateHint] teacherid 업데이트 중 오류:', updateError);
                }

                // 현재 상호작용 ID 설정
                currentInteractionId = interactionId;

                // 문제 이미지 로드 (없어도 힌트 생성은 진행)
                const imagePreview = document.getElementById('imagePreview');
                if (problemImage && imagePreview) {
                    imagePreview.src = getImageUrl(problemImage);
                    imagePreview.style.display = 'block';
                } else if (imagePreview) {
                    // 이미지가 없는 경우 안내 메시지
                    imagePreview.style.display = 'none';
                    console.log('[generateHint] 문제 이미지가 없습니다. contentsid로 힌트 생성을 진행합니다.');
                }

                // 우측 패널 초기화
                const solutionContent = document.getElementById('solutionContent');
                const narrationContent = document.getElementById('narrationContent');
                const narrationText = document.getElementById('narrationText');

                if (solutionContent) solutionContent.style.display = 'none';
                if (narrationContent) narrationContent.style.display = 'block';
                if (narrationText) {
                    narrationText.innerHTML = `${hintLevelNames[hintLevel] || '힌트'} 대본을 생성 중입니다...`;
                }

                // 프로세스 상태 업데이트
                const processStatus = document.getElementById('processStatus');
                if (processStatus) {
                    processStatus.innerHTML = `<span style="color: #00bcd4;">💡 ${hintLevelNames[hintLevel] || '힌트'}를 생성합니다...</span>`;
                }

                // 힌트 생성 (generate_dialog_narration.php에 hintLevel 전달)
                const response = await fetch('generate_dialog_narration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        interactionId: interactionId,
                        solution: '',  // 힌트 모드에서는 빈 문자열
                        generateTTS: 'true',
                        customSolution: 'false',
                        hintLevel: hintLevel  // 힌트 레벨 파라미터 추가
                    })
                });

                const data = await response.json();
                console.log('[generateHint] API 응답:', data);

                if (data.success) {
                    // 나레이션 표시
                    if (narrationText && data.narration) {
                        narrationText.innerHTML = data.narration.replace(/\n/g, '<br>');
                    }
                    
                    if (processStatus) {
                        processStatus.innerHTML = '<span style="color: #00bcd4;">✅ 힌트 생성 완료!</span>';
                    }

                    // 오디오 URL이 있으면 재생 버튼 활성화
                    if (data.audio_url) {
                        const playButton = document.getElementById('playNarrationBtn');
                        if (playButton) {
                            playButton.disabled = false;
                            playButton.dataset.audioUrl = data.audio_url;
                        }
                    }

                    // 상태 업데이트 (completed로)
                    try {
                        await fetch('save_interaction.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'update_status',
                                interactionId: interactionId,
                                status: 'completed'
                            })
                        });
                        console.log('[generateHint] 상태 업데이트 완료: completed');
                    } catch (statusError) {
                        console.error('[generateHint] 상태 업데이트 오류:', statusError);
                    }

                    // 새로운 요청 목록 새로고침
                    setTimeout(loadNewRequests, 1000);
                    setTimeout(loadCompletedRequests, 1000);

                } else {
                    throw new Error(data.error || '힌트 생성 실패');
                }

            } catch (error) {
                console.error('[generateHint] 오류:', error);
                alert('힌트 생성 중 오류가 발생했습니다: ' + error.message);
                
                const processStatus = document.getElementById('processStatus');
                if (processStatus) {
                    processStatus.innerHTML = '<span style="color: #f44336;">❌ 힌트 생성 오류</span>';
                }
            }
        }

        // 사용자 입력 풀이로 TTS 생성
        async function acceptWithCustomSolution() {
            const textarea = document.getElementById('customSolutionText');
            const customSolution = textarea ? textarea.value.trim() : '';

            if (!customSolution) {
                alert('풀이 내용을 입력해주세요.');
                return;
            }

            if (!customSolutionData.interactionId) {
                alert('상호작용 ID가 없습니다.');
                return;
            }

            console.log('[acceptWithCustomSolution] 사용자 입력 풀이로 TTS 생성:', {
                interactionId: customSolutionData.interactionId,
                customSolutionLength: customSolution.length
            });

            // ⚠️ 중요: 모달 닫기 전에 데이터 저장 (closeCustomSolutionModal이 데이터를 초기화하므로)
            const savedInteractionId = customSolutionData.interactionId;
            const savedProblemImage = customSolutionData.problemImage;
            const savedIsReRequest = customSolutionData.isReRequest;

            try {
                // 모달 닫기
                closeCustomSolutionModal();

                // teacherid 업데이트
                const teacherId = <?php echo $USER->id; ?>;
                try {
                    const updateTeacherResponse = await fetch('save_interaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_teacherid',
                            interactionId: savedInteractionId,
                            teacherId: teacherId
                        })
                    });
                    const updateTeacherData = await updateTeacherResponse.json();
                    if (updateTeacherData.success) {
                        console.log('[acceptWithCustomSolution] teacherid 업데이트 완료:', teacherId);
                    }
                } catch (updateError) {
                    console.error('[acceptWithCustomSolution] teacherid 업데이트 중 오류:', updateError);
                }

                // 현재 상호작용 ID 설정
                currentInteractionId = savedInteractionId;

                // 문제 이미지 로드 (있는 경우)
                if (savedProblemImage) {
                    const imagePreview = document.getElementById('imagePreview');
                    if (imagePreview) {
                        imagePreview.src = getImageUrl(savedProblemImage);
                        imagePreview.style.display = 'block';
                    }
                }

                // 우측 패널 초기화
                const solutionContent = document.getElementById('solutionContent');
                const narrationContent = document.getElementById('narrationContent');
                const narrationText = document.getElementById('narrationText');

                if (solutionContent) solutionContent.style.display = 'none';
                if (narrationContent) narrationContent.style.display = 'block';
                if (narrationText) {
                    narrationText.innerHTML = '사용자 입력 풀이로 TTS 대본을 생성 중입니다...';
                }

                // 프로세스 상태 업데이트
                const processStatus = document.getElementById('processStatus');
                if (processStatus) {
                    processStatus.innerHTML = savedIsReRequest
                        ? '<span style="color: #ff5722;">🔄 재풀이를 사용자 입력 풀이로 생성합니다...</span>'
                        : '<span style="color: #9c27b0;">📝 사용자 입력 풀이로 TTS를 생성합니다...</span>';
                }

                // TTS 대본 생성 (사용자 입력 풀이 사용, customSolution 플래그 전달)
                const response = await fetch('generate_dialog_narration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        interactionId: savedInteractionId,
                        solution: customSolution,
                        generateTTS: 'true',
                        customSolution: 'true'  // 사용자 입력 풀이 플래그
                    })
                });

                const data = await response.json();

                // 전체 API 응답 로깅 (디버깅용)
                console.log('[acceptWithCustomSolution] API 전체 응답:', data);

                if (data.success) {
                    console.log('[acceptWithCustomSolution] TTS 대본 생성 완료');

                    // 현재 나레이션 저장
                    currentNarration = data.narrationText;

                    // TTS 대본 표시
                    if (narrationText) {
                        narrationText.innerHTML = data.narrationText.replace(/\n/g, '<br>');
                    }

                    // 응답하기 버튼 활성화
                    const sendMessageBtn = document.getElementById('sendMessageBtn');
                    if (sendMessageBtn) {
                        sendMessageBtn.disabled = false;
                    }

                    // 프로세스 상태 업데이트
                    if (processStatus) {
                        processStatus.innerHTML = '<span style="color: #4caf50;">✅ TTS 대본 생성 완료 (사용자 입력 풀이 기반)</span>';
                    }

                    // 새로운 요청 목록 새로고침
                    setTimeout(loadNewRequests, 2000);
                } else {
                    // 상세한 오류 정보 로깅
                    console.error('[acceptWithCustomSolution] API 오류 응답:', {
                        success: data.success,
                        message: data.message,
                        error: data.error,
                        errorDetails: data.errorDetails,
                        debug: data.debug
                    });

                    // 가능한 한 상세한 오류 메시지 표시
                    const errorMessage = data.message || data.error || data.errorDetails || 'TTS 대본 생성 실패';
                    throw new Error(errorMessage);
                }

            } catch (error) {
                console.error('[acceptWithCustomSolution] 오류:', error);
                alert('TTS 대본 생성 중 오류가 발생했습니다: ' + error.message);

                // 프로세스 상태 업데이트
                const processStatus = document.getElementById('processStatus');
                if (processStatus) {
                    processStatus.innerHTML = '<span style="color: #d32f2f;">❌ 오류: ' + error.message + '</span>';
                }
            }
        }

        // 완료된 해설 기록 로드 함수
        async function loadCompletedRequests() {
            const completedListElement = document.getElementById('completedRequestsList');
            if (!completedListElement) {
                console.error('[loadCompletedRequests] completedRequestsList element not found in DOM');
                return;
            }
            
            try {
                const teacherId = <?php echo $userid; ?>; // URL의 userid 사용
                const url = `get_completed_requests.php?teacherid=${teacherId}`;
                console.log('[loadCompletedRequests] Loading completed requests from:', url);
                
                const response = await fetch(url);
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('[loadCompletedRequests] HTTP error! status:', response.status, 'Response:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                console.log('[loadCompletedRequests] Raw response:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('[loadCompletedRequests] JSON parse error:', e);
                    completedListElement.innerHTML = `<div class="no-history" style="color: #d32f2f;">⚠️ 응답 파싱 오류: ${e.message}</div>`;
                    return;
                }
                
                console.log('[loadCompletedRequests] Parsed response:', data);
                console.log('[loadCompletedRequests] Debug info:', data.debug);
                
                if (data.success) {
                    if (data.requests && data.requests.length > 0) {
                        console.log('[loadCompletedRequests] Found', data.requests.length, 'completed requests');
                        console.log('[loadCompletedRequests] First item:', data.requests[0]);
                        displayCompletedRequests(data.requests);
                    } else {
                        console.log('[loadCompletedRequests] No completed requests found. Debug:', data.debug);
                        displayCompletedRequests([]);
                    }
                } else {
                    console.error('[loadCompletedRequests] API returned error:', data.error);
                    completedListElement.innerHTML = `<div class="no-history" style="color: #d32f2f;">⚠️ 오류: ${data.error || '알 수 없는 오류'}</div>`;
                }
            } catch (error) {
                console.error('[loadCompletedRequests] Error loading completed requests:', error);
                completedListElement.innerHTML = `<div class="no-history" style="color: #d32f2f;">⚠️ 완료된 기록을 불러오는 중 오류가 발생했습니다: ${error.message}</div>`;
            }
        }
        
        // 완료된 해설 기록 표시 함수
        function displayCompletedRequests(items) {
            const completedListElement = document.getElementById('completedRequestsList');
            if (!completedListElement) {
                console.error('[displayCompletedRequests] completedRequestsList element not found in DOM');
                return;
            }
            
            console.log('[displayCompletedRequests] Displaying', items.length, 'completed items');
            
            if (items.length === 0) {
                completedListElement.innerHTML = '<div class="no-history" style="color: #757575; font-size: 13px;">완료된 해설 기록이 없습니다.</div>';
                return;
            }
            
            completedListElement.innerHTML = items.map(item => {
                const problemText = item.problemText || '';
                const displayText = problemText.length > 30 ? problemText.substring(0, 30) + '...' : (problemText || '해설 완료');
                
                return `
                    <div class="history-item" data-id="${item.id}" style="background: #ffffff; border: 1px solid #e0e0e0; margin-bottom: 8px; padding: 10px; border-radius: 5px; cursor: pointer;"
                         onclick="viewCompletedSolution(${item.id}, ${item.studentId || 0})">
                        <div class="history-item-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <a href="student_inbox.php?studentid=${item.studentId}" style="text-decoration: none;" onclick="event.stopPropagation();">
                                <span class="student-name" style="background: #757575; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; cursor: pointer; transition: background 0.2s;">
                                    ${item.studentName || '학생'}
                                </span>
                            </a>
                            <span class="history-time" style="color: #9e9e9e; font-size: 11px;">
                                ${item.timeAgo || '알 수 없음'}
                            </span>
                        </div>
                        <div class="history-item-content" style="display: flex; align-items: center; gap: 10px;">
                            ${item.problemImage ? `<img src="${getImageUrl(item.problemImage)}" alt="문제 이미지" class="clickable-image" onclick="event.stopPropagation(); openImageModal('${getImageUrl(item.problemImage)}');" style="max-width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #e0e0e0;" onerror="this.style.display='none';">` : '<div style="width: 60px; height: 60px; background: #f5f5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #9e9e9e; font-size: 20px;">📝</div>'}
                            <div style="flex: 1; min-width: 0;">
                                ${item.problemType ? `<div style="color: #616161; font-size: 11px; margin-bottom: 4px;">${item.problemType}</div>` : ''}
                                <div style="color: #424242; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    ${displayText}
                                </div>
                            </div>
                            <span style="color: #4caf50; font-size: 18px;">✓</span>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        // 완료된 해설 보기 함수
        function viewCompletedSolution(interactionId, studentId) {
            console.log('[viewCompletedSolution] Viewing completed solution for interaction:', interactionId, 'studentId:', studentId);
            // 해당 학생의 student_inbox.php로 이동
            if (studentId && studentId > 0) {
                window.location.href = `student_inbox.php?studentid=${studentId}`;
            } else {
                console.error('[viewCompletedSolution] studentId가 유효하지 않습니다:', studentId);
                alert('학생 정보를 찾을 수 없습니다.');
            }
        }
        
        
        // 강의 모달 관련 변수
        let lectureAudioPlayer = null;
        let modalDialogueLines = [];
        let modalCurrentLineIndex = 0;
        let isLecturePlaying = false;
        let modalSyncTimer = null;

        // 강의 모달 관련 변수
        let currentInteractionData = null;
        let listeningContainer = null;
        let currentSectionIndex = 0;
        let sectionAudioBuffers = [];
        let currentAudioSource = null;
        let audioCtx = null;
        const studentId = <?php echo $studentid; ?>;
        const apikey = '<?php echo $secret_key; ?>';
        
        function getAudioContext() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        }
        
        // 강의 모달 열기
        async function openLectureModal(interactionId) {
            const modal = document.getElementById('lectureModal');
            modal.classList.add('active');
            
            const iframe = document.getElementById('whiteboardFrame');
            iframe.src = 'about:blank'; // 초기화
            
            // 데이터 로드
            try {
                const response = await fetch(`get_dialogue_data.php?cid=${interactionId}&ctype=interaction&studentid=${studentId}`);
                const data = await response.json();
                
                console.log('Loaded data:', data);
                
                if (data.success) {
                    currentInteractionData = data;
                    
                    // 화이트보드 iframe URL 구성
                    const contentsid = data.contentsid || data.interactionData?.id || interactionId;
                    const contentstype = 2; // teachingagent.php에서도 항상 2
                    const wboardid = data.wboardid || generateWboardId(interactionId);
                    
                    const whiteboardUrl = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id=${wboardid}&contentsid=${contentsid}&contentstype=${contentstype}&studentid=${studentId}`;
                    
                    console.log('=== 화이트보드 정보 ===');
                    console.log('WBoard ID:', wboardid);
                    console.log('Contents ID:', contentsid);
                    console.log('Content Type:', contentstype);
                    console.log('Student ID:', studentId);
                    console.log('Full URL:', whiteboardUrl);
                    
                    iframe.src = whiteboardUrl;
                    
                    // 플로팅 헤드폰 아이콘 생성
                    createFloatingHeadphoneIcon(data);
                    
                } else {
                    console.error('Failed to load interaction data:', data.error);
                    iframe.src = 'about:blank';
                }
            } catch (error) {
                console.error('Error loading interaction:', error);
                iframe.src = 'about:blank';
            }
        }
        
        // wboardid 생성
        function generateWboardId(interactionId) {
            return 'WB_' + interactionId + '_' + Date.now();
        }
        
        // 플로팅 헤드폰 아이콘 생성
        function createFloatingHeadphoneIcon(data) {
            // 기존 컨테이너 제거
            const existing = document.getElementById('listeningContainer');
            if (existing) existing.remove();
            
            const container = document.createElement('div');
            container.id = 'listeningContainer';
            container.className = 'listening-test-container minimized';
            container.innerHTML = `
                <div class="listening-header" id="listeningHeader">
                    <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                        <button class="speed-control-btn" id="speedControlBtn" onclick="event.stopPropagation(); cyclePlaybackSpeed();" title="재생 속도 조절">1.0x</button>
                    </div>
                    <button class="listening-minimize-btn" id="minimizeBtn" onclick="event.stopPropagation(); toggleListeningPlayer();">+</button>
                </div>
                <div class="listening-body">
                    <div class="listening-progress-dots" id="progressDots"></div>
                    <div class="listening-nav-buttons">
                        <button class="listening-nav-btn" id="prevSectionBtn" onclick="event.stopPropagation(); playPreviousSection();" disabled>◀</button>
                        <button class="listening-nav-btn" id="playSectionBtn" onclick="event.stopPropagation(); playCurrentSection();">▶</button>
                        <button class="listening-nav-btn" id="nextSectionBtn" onclick="event.stopPropagation(); playNextSection();">▶</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(container);
            listeningContainer = container;
            
            // 클릭 이벤트: 최소화 상태일 때 확장
            container.addEventListener('click', function(e) {
                if (container.classList.contains('minimized')) {
                    container.classList.remove('minimized');
                }
            });
            
            // 나레이션 데이터가 있으면 준비
            if (data.solutionText || data.narrationText) {
                prepareNarrationSections(data.solutionText || data.narrationText);
            }
        }
        
        // 나레이션 섹션 준비 (@ 기호로 분리)
        function prepareNarrationSections(text) {
            const sections = text.split('@').filter(s => s.trim());
            const dotsContainer = document.getElementById('progressDots');
            dotsContainer.innerHTML = '';
            
            sections.forEach((section, index) => {
                const dot = document.createElement('div');
                dot.className = 'progress-dot';
                dot.setAttribute('data-section', index);
                dot.onclick = () => playSection(index);
                dotsContainer.appendChild(dot);
            });
            
            sectionAudioBuffers = [];
            currentSectionIndex = 0;
        }
        
        // 섹션 재생
        async function playSection(index) {
            if (!currentInteractionData) return;
            
            const text = currentInteractionData.solutionText || currentInteractionData.narrationText || '';
            const sections = text.split('@').filter(s => s.trim());
            
            if (index >= sections.length) return;
            
            const sectionText = sections[index].trim();
            currentSectionIndex = index;
            
            // 진행 표시 업데이트
            document.querySelectorAll('.progress-dot').forEach((dot, i) => {
                dot.classList.remove('active', 'completed');
                if (i === index) dot.classList.add('active');
                if (i < index) dot.classList.add('completed');
            });
            
            // TTS 생성 및 재생
            try {
                const buffer = await generateSpeech(sectionText, "alloy");
                playAudioBuffer(buffer, () => {
                    // 재생 완료
                    document.querySelectorAll('.progress-dot')[index].classList.remove('active');
                    document.querySelectorAll('.progress-dot')[index].classList.add('completed');
                });
            } catch (e) {
                console.error('TTS generation failed:', e);
            }
        }
        
        function playCurrentSection() {
            playSection(currentSectionIndex);
        }
        
        function playNextSection() {
            const text = currentInteractionData.solutionText || currentInteractionData.narrationText || '';
            const sections = text.split('@').filter(s => s.trim());
            if (currentSectionIndex < sections.length - 1) {
                playSection(currentSectionIndex + 1);
            }
        }
        
        function playPreviousSection() {
            if (currentSectionIndex > 0) {
                playSection(currentSectionIndex - 1);
            }
        }
        
        function toggleListeningPlayer() {
            const container = document.getElementById('listeningContainer');
            if (container) {
                container.classList.toggle('minimized');
            }
        }
        
        let currentPlaybackSpeed = 1.0;
        function cyclePlaybackSpeed() {
            const speeds = [0.75, 1.0, 1.25, 1.5];
            const currentIndex = speeds.indexOf(currentPlaybackSpeed);
            currentPlaybackSpeed = speeds[(currentIndex + 1) % speeds.length];
            document.getElementById('speedControlBtn').textContent = currentPlaybackSpeed + 'x';
            if (currentAudioSource) {
                currentAudioSource.playbackRate.value = currentPlaybackSpeed;
            }
        }
        
        const generateSpeech = async (text, voice) => {
            if (!apikey) {
                throw new Error("API Key not found");
            }
            const fetchOptions = {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${apikey}`
                },
                body: JSON.stringify({
                    model: "tts-1",
                    voice: voice,
                    input: text
                }),
            };

            const response = await fetch("https://api.openai.com/v1/audio/speech", fetchOptions);
            if (!response.ok) throw new Error("음성 생성 실패");
            const audioData = await response.arrayBuffer();
            const ctx = getAudioContext();
            return await ctx.decodeAudioData(audioData);
        };
        
        const playAudioBuffer = (buffer, onEnded) => {
            const ctx = getAudioContext();
            if (currentAudioSource) {
                try { currentAudioSource.stop(); } catch(e) {}
            }
            
            currentAudioSource = ctx.createBufferSource();
            currentAudioSource.buffer = buffer;
            currentAudioSource.playbackRate.value = currentPlaybackSpeed;
            currentAudioSource.connect(ctx.destination);
            if (onEnded) {
                currentAudioSource.onended = onEnded;
            }
            currentAudioSource.start();
        };
        
        // 모달 닫기
        function closeLectureModal() {
            const modal = document.getElementById('lectureModal');
            modal.classList.remove('active');
            
            // 오디오 정지
            if (currentAudioSource) {
                try {
                    currentAudioSource.stop();
                } catch(e) {}
                currentAudioSource = null;
            }
            
            // 플로팅 플레이어 제거
            const container = document.getElementById('listeningContainer');
            if (container) container.remove();
            
            // 질문 패널 닫기
            closeQuestionPanel();
            
            // 초기화
            currentInteractionData = null;
            sectionAudioBuffers = [];
            currentSectionIndex = 0;
        }
        
        // 단계별 질문 생성
        function initStepQuestions() {
            if (!currentInteractionData) return;
            openQuestionPanel();
        }
        
        function openQuestionPanel() {
            const panel = document.getElementById('questionPanel');
            panel.classList.add('active');
            
            const content = document.getElementById('questionPanelContent');
            content.innerHTML = `
                <div id="questions-loading" style="text-align:center; padding:20px;">
                    <div class="loading-spinner" style="margin: 0 auto 10px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite;"></div>
                    <p>단계별 질문을 생성하고 있습니다...</p>
                </div>
                <div id="questions-container"></div>
                <style>
                    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                </style>
            `;
            
            const text = currentInteractionData.solutionText || '';
            generateQuestionsLogic(text);
        }
        
        function closeQuestionPanel() {
            const panel = document.getElementById('questionPanel');
            panel.classList.remove('active');
        }

        async function generateQuestionsLogic(nodeContent) {
            try {
                const response = await fetch('../../books/generate_questions_with_answers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        nodeContent: nodeContent,
                        nodeType: 'step',
                        fullContext: currentInteractionData.problemText || '',
                        contentsid: currentInteractionData.interactionData?.id || 0,
                        contentstype: 2,
                        nstep: 1,
                        totalSteps: 1,
                        nodeIndex: 0
                    })
                });

                const data = await response.json();
                const container = document.getElementById('questions-container');
                const loading = document.getElementById('questions-loading');
                if (loading) loading.style.display = 'none';

                if (data.success && data.qa_pairs) {
                    const questionsToShow = data.qa_pairs.slice(0, 3);
                    
                    const questionPromises = questionsToShow.map(async (qa, index) => {
                        const contentsid = currentInteractionData.interactionData?.id || currentInteractionData.contentsid || 0;
                        const contentstype = 2;
                        const questionNumber = index + 1;
                        
                        let questionWboardId = null;
                        try {
                            const wbResponse = await fetch(`get_whiteboard_id.php?cid=${contentsid}&ctype=${contentstype}&userid=${studentId}&qnum=${questionNumber}`);
                            const wbData = await wbResponse.json();
                            
                            if (wbData.success) {
                                questionWboardId = wbData.wboardid;
                            } else {
                                questionWboardId = `stepquiz_q${questionNumber}_${contentsid}_user${studentId}`;
                            }
                        } catch (error) {
                            questionWboardId = `stepquiz_q${questionNumber}_${contentsid}_user${studentId}`;
                        }
                        
                        const stepquizUrl = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_stepquiz.php?id=${questionWboardId}&cid=${contentsid}&ctype=${contentstype}&userid=${studentId}&nstep=${questionNumber}`;
                        
                        const card = document.createElement('div');
                        card.className = 'question-card';
                        card.innerHTML = `
                            <div class="question-header" onclick="toggleQuestion(this)">
                                <div style="display:flex; align-items:center;">
                                    <span class="question-icon">Q${questionNumber}</span>
                                    <span>${qa.question}</span>
                                </div>
                                <i class="fas fa-chevron-down toggle-icon"></i>
                            </div>
                            <div class="question-answer">
                                ${qa.answer}
                                <div class="question-embed-whiteboard">
                                    <iframe src="${stepquizUrl}" frameborder="0"></iframe>
                                </div>
                            </div>
                        `;
                        container.appendChild(card);
                    });
                    
                    await Promise.all(questionPromises);
                    
                    if (window.MathJax) {
                        window.MathJax.typesetPromise([container]);
                    }
                } else {
                    container.innerHTML = `<div style="color:red; text-align:center;">질문 생성 실패: ${data.error || '알 수 없는 오류'}</div>`;
                }
            } catch (error) {
                console.error(error);
                const loading = document.getElementById('questions-loading');
                if (loading) loading.style.display = 'none';
                
                const container = document.getElementById('questions-container');
                if (container) container.innerHTML = `<div style="color:red; text-align:center;">오류 발생: ${error.message}</div>`;
            }
        }

        function toggleQuestion(header) {
            const card = header.parentElement;
            card.classList.toggle('active');
        }

        // 대화 파싱
        function parseDialogue(text, isModal = false) {
            if (!text) return;
            
            const solutionContent = document.getElementById(isModal ? 'modalSolutionContent' : 'solutionContent');
            if (!solutionContent) return;
            
            solutionContent.innerHTML = '';
            if (isModal) {
                modalDialogueLines = [];
            } else {
                dialogueLines = [];
            }
            
            // 해설 내용을 섹션별로 파싱
            const sections = [];
            let currentSection = '';
            
            // 텍스트를 줄바꿈으로 분리
            const lines = text.split('\n');
            
            lines.forEach(line => {
                const trimmedLine = line.trim();
                if (!trimmedLine) return;
                
                // 섹션 헤더 감지 ([문제 분석], [풀이 과정] 등)
                if (trimmedLine.match(/^\[.+\]$/)) {
                    if (currentSection) {
                        sections.push(currentSection);
                    }
                    currentSection = trimmedLine + '\n';
                } else {
                    currentSection += trimmedLine + '\n';
                }
            });
            
            if (currentSection) {
                sections.push(currentSection);
            }
            
            // 섹션별로 처리
            sections.forEach(section => {
                const lines = section.split('\n').filter(line => line.trim());
                
                lines.forEach(line => {
                    const lineDiv = document.createElement('div');
                    lineDiv.className = 'solution-line';
                    const currentLines = isModal ? modalDialogueLines : dialogueLines;
                    lineDiv.setAttribute('data-index', currentLines.length);
                    
                    // 섹션 헤더 스타일
                    if (line.match(/^\[.+\]$/)) {
                        lineDiv.innerHTML = `<h3>${line.replace(/[\[\]]/g, '')}</h3>`;
                    }
                    // 선생님/학생 대화 처리
                    else if (line.includes('선생님:') || line.includes('학생:')) {
                        const isTeacher = line.includes('선생님:');
                        lineDiv.className += isTeacher ? ' teacher' : ' student';
                        const speaker = isTeacher ? '선생님:' : '학생:';
                        const content = line.replace(speaker, '').trim();
                        lineDiv.innerHTML = `
                            <div class="speaker-label" style="font-weight: bold; color: ${isTeacher ? '#2b6cb0' : '#276749'}; margin-bottom: 5px;">${speaker}</div>
                            <div>${content}</div>
                        `;
                    }
                    // 일반 내용
                    else {
                        // 수식 태그 변환 (LaTeX 형식 유지)
                        let formattedLine = line;
                        // 리스트 항목 처리
                        if (formattedLine.match(/^[-*]\s/)) {
                            formattedLine = '• ' + formattedLine.substring(2);
                        }
                        lineDiv.innerHTML = formattedLine;
                    }
                    
                    solutionContent.appendChild(lineDiv);
                    const lineData = {
                        element: lineDiv,
                        text: line,
                        duration: line.replace(/<[^>]*>/g, '').length * 0.05
                    };
                    
                    if (isModal) {
                        modalDialogueLines.push(lineData);
                    } else {
                        dialogueLines.push(lineData);
                    }
                });
            });
        }
        
        // 재생/일시정지 토글
        function togglePlayPause() {
            if (isLecturePlaying) {
                pauseAudio();
            } else {
                playAudio();
            }
        }

        // 오디오 재생
        function playAudio() {
            if (!lectureAudioPlayer) return;
            
            lectureAudioPlayer.play();
            isLecturePlaying = true;
            
            // 아이콘 변경
            document.getElementById('playIcon').style.display = 'none';
            document.getElementById('pauseIcon').style.display = 'block';
            
            // 텍스트 싱크 시작
            startTextSync();
        }

        // 오디오 일시정지
        function pauseAudio() {
            if (!lectureAudioPlayer) return;
            
            lectureAudioPlayer.pause();
            isLecturePlaying = false;
            
            // 아이콘 변경
            document.getElementById('playIcon').style.display = 'block';
            document.getElementById('pauseIcon').style.display = 'none';
            
            // 텍스트 싱크 중지
            if (modalSyncTimer) {
                clearInterval(modalSyncTimer);
                modalSyncTimer = null;
            }
        }

        // 텍스트 싱크 시작
        function startTextSync() {
            if (!modalDialogueLines.length || !lectureAudioPlayer.duration) return;
            
            const totalDuration = lectureAudioPlayer.duration;
            
            // 각 라인의 누적 시간 계산
            let cumulativeTime = 0;
            const lineTimings = modalDialogueLines.map((line, index) => {
                const start = cumulativeTime;
                const duration = line.duration || (totalDuration / modalDialogueLines.length);
                cumulativeTime += duration;
                return { start, end: cumulativeTime };
            });
            
            // 전체 시간에 맞게 조정
            const scaleFactor = totalDuration / cumulativeTime;
            lineTimings.forEach(timing => {
                timing.start *= scaleFactor;
                timing.end *= scaleFactor;
            });
            
            // 현재 재생 위치에 맞는 라인 찾기
            const currentTime = lectureAudioPlayer.currentTime;
            modalCurrentLineIndex = 0;
            for (let i = 0; i < lineTimings.length; i++) {
                if (currentTime >= lineTimings[i].start) {
                    modalDialogueLines[i].element.classList.add('visible');
                    modalCurrentLineIndex = i;
                } else {
                    break;
                }
            }
            
            // 싱크 타이머 시작
            modalSyncTimer = setInterval(() => {
                const currentTime = lectureAudioPlayer.currentTime;
                
                while (modalCurrentLineIndex < modalDialogueLines.length && 
                       currentTime >= lineTimings[modalCurrentLineIndex].start) {
                    const line = modalDialogueLines[modalCurrentLineIndex];
                    line.element.classList.add('visible');
                    
                    // 부드러운 스크롤 (현재 라인을 뷰포트 중앙으로)
                    const container = line.element.parentElement;
                    const containerRect = container.getBoundingClientRect();
                    const lineRect = line.element.getBoundingClientRect();
                    const scrollTop = container.scrollTop;
                    const targetScroll = scrollTop + (lineRect.top - containerRect.top) - (containerRect.height / 2) + (lineRect.height / 2);
                    
                    container.scrollTo({
                        top: targetScroll,
                        behavior: 'smooth'
                    });
                    
                    modalCurrentLineIndex++;
                }
            }, 50); // 더 자주 체크하여 부드러운 싱크
        }

        // 진행률 업데이트
        function updateProgress() {
            if (!lectureAudioPlayer || !lectureAudioPlayer.duration) return;
            
            const progress = (lectureAudioPlayer.currentTime / lectureAudioPlayer.duration) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('currentTime').textContent = formatTime(lectureAudioPlayer.currentTime);
        }

        // 오디오 종료 시
        function onAudioEnded() {
            isLecturePlaying = false;
            document.getElementById('playIcon').style.display = 'block';
            document.getElementById('pauseIcon').style.display = 'none';
            
            // 오디오 종료 시에만 모든 텍스트 표시
            modalDialogueLines.forEach(line => line.element.classList.add('visible'));
            
            if (modalSyncTimer) {
                clearInterval(modalSyncTimer);
                modalSyncTimer = null;
            }
        }

        // 시크 기능
        function seekAudio(event) {
            if (!lectureAudioPlayer || !lectureAudioPlayer.duration) return;
            
            const progressContainer = event.currentTarget;
            const clickX = event.offsetX;
            const width = progressContainer.offsetWidth;
            const percentage = clickX / width;
            
            lectureAudioPlayer.currentTime = percentage * lectureAudioPlayer.duration;
            
            // 텍스트 싱크 재조정
            if (isLecturePlaying) {
                if (modalSyncTimer) clearInterval(modalSyncTimer);
                startTextSync();
            }
        }

        // 재생 속도 설정
        function setSpeed(speed) {
            if (!lectureAudioPlayer) return;
            
            lectureAudioPlayer.playbackRate = speed;
            
            // 버튼 활성화 상태 변경
            document.querySelectorAll('.speed-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // 시간 포맷
        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }

        // 페이지 로드시 초기화
        window.addEventListener('load', () => {
            console.log('[Page Load] Initializing...');
            console.log('[Page Load] isStudentMode:', isStudentMode);
            
            // 새로운 요청 로드
            if (!isStudentMode) {
                // DOM 요소 확인
                const requestsListElement = document.getElementById('newRequestsList');
                console.log('[Page Load] newRequestsList element:', requestsListElement);
                
                if (requestsListElement) {
                    console.log('[Page Load] Loading new requests...');
                    loadNewRequests();
                    // 1분마다 새로운 요청 새로고침
                    setInterval(loadNewRequests, 60000);
                } else {
                    console.error('[Page Load] newRequestsList element not found!');
                }
                
                // 완료된 해설 기록 로드
                const completedListElement = document.getElementById('completedRequestsList');
                if (completedListElement) {
                    console.log('[Page Load] Loading completed requests...');
                    loadCompletedRequests();
                } else {
                    console.error('[Page Load] completedRequestsList element not found!');
                }
                
                // 페이지 포커스 시에도 새로고침
                window.addEventListener('focus', function() {
                    console.log('Page focused - refreshing requests');
                    loadNewRequests();
                    loadCompletedRequests();
                });
                
                // 가시성 변경 시에도 새로고침
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) {
                        console.log('Page visible - refreshing requests');
                        loadNewRequests();
                        loadCompletedRequests();
                    }
                });
            }
            
            // postMessage 리스너 추가 (student_inbox.php에서 전달받기)
            window.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'newQuestion') {
                    console.log('Received new question from student_inbox:', event.data);
                    
                    const questionData = event.data.data;
                    const fromStudentInbox = event.data.fromStudentInbox || false;
                    
                    console.log('Question data:', questionData);
                    console.log('From student inbox:', fromStudentInbox);
                    console.log('Teacher ID from message:', questionData.teacherId);
                    console.log('Student ID from message:', questionData.studentId);
                    
                    // 이미지 데이터를 File 객체로 변환
                    fetch(questionData.imageData)
                        .then(res => res.blob())
                        .then(blob => {
                            const file = new File([blob], 'question.png', { type: blob.type });
                            uploadedFile = file;
                            
                            // 이미지 미리보기 표시
                            imagePreview.src = questionData.imageData;
                            imagePreview.style.display = 'block';
                            
                            // 문제 유형 설정
                            problemType.value = questionData.problemType;
                            
                            // 추가 요청사항이 있으면 설정
                            if (questionData.additionalRequest) {
                                window.modificationPrompt = questionData.additionalRequest;
                            }
                            
                            // student_inbox에서 온 경우 ID들 설정
                            if (fromStudentInbox) {
                                if (questionData.teacherId) {
                                    window.requestedTeacherId = parseInt(questionData.teacherId);
                                    console.log('Set requestedTeacherId to:', window.requestedTeacherId);
                                }
                                if (questionData.studentId) {
                                    window.requestedStudentId = parseInt(questionData.studentId);
                                    console.log('Set requestedStudentId to:', window.requestedStudentId);
                                }
                                
                                // student_inbox에서 온 경우 자동으로 제출 실행
                                setTimeout(() => {
                                    startTutoringBtn.click();
                                }, 500);
                            }
                            
                            // 시작 버튼 활성화 및 표시
                            startTutoringBtn.disabled = false;
                            startTutoringBtn.style.display = 'inline-block';
                            processStatus.textContent = '문제가 업로드되었습니다. 하이튜터링 시작 버튼을 클릭하세요.';
                        });
                }
            });
        });

        // 오디오 재생 컨트롤 (학생 모드가 아닐 때만)
        if (playAudioBtn) {
            playAudioBtn.addEventListener('click', () => {
                if (audioElement && audioElement.src) {
                    audioElement.play();
                    if (playAudioBtn) playAudioBtn.style.display = 'none';
                    if (pauseAudioBtn) pauseAudioBtn.style.display = 'inline-flex';
                }
            });
        }

        if (pauseAudioBtn) {
            pauseAudioBtn.addEventListener('click', () => {
                if (audioElement) {
                    audioElement.pause();
                    if (pauseAudioBtn) pauseAudioBtn.style.display = 'none';
                    if (playAudioBtn) playAudioBtn.style.display = 'inline-flex';
                }
            });
        } 

        // 오디오 종료 시 버튼 상태 변경
        if (audioElement && playAudioBtn && pauseAudioBtn) {
            audioElement.addEventListener('ended', () => {
                if (pauseAudioBtn) pauseAudioBtn.style.display = 'none';
                if (playAudioBtn) playAudioBtn.style.display = 'inline-flex';
            });
        }

 
        // 모달 외부 클릭시 닫기
        if (messageModal) {
            window.addEventListener('click', (e) => {
                if (e.target === messageModal) {
                    messageModal.style.display = 'none';
                }
            });
        }

        // 다른 풀이 입력 모달 이벤트 리스너
        const customSolutionModal = document.getElementById('customSolutionModal');
        const confirmCustomSolutionBtn = document.getElementById('confirmCustomSolutionBtn');

        if (confirmCustomSolutionBtn) {
            confirmCustomSolutionBtn.addEventListener('click', acceptWithCustomSolution);
        }

        // 다른 풀이 입력 모달 외부 클릭시 닫기
        if (customSolutionModal) {
            window.addEventListener('click', (e) => {
                if (e.target === customSolutionModal) {
                    closeCustomSolutionModal();
                }
            });
        }
        
        // 풀이 스타일 선택 모달 외부 클릭시 닫기
        const solutionStyleModal = document.getElementById('solutionStyleModal');
        if (solutionStyleModal) {
            window.addEventListener('click', (e) => {
                if (e.target === solutionStyleModal) {
                    closeSolutionStyleModal();
                }
            });
        }
        
        // 힌트 종류 선택 모달 외부 클릭시 닫기
        const hintTypeModal = document.getElementById('hintTypeModal');
        if (hintTypeModal) {
            window.addEventListener('click', (e) => {
                if (e.target === hintTypeModal) {
                    closeHintTypeModal();
                }
            });
        }
        
        // 직접 힌트 입력 모달 외부 클릭시 닫기
        const customHintModal = document.getElementById('customHintModal');
        if (customHintModal) {
            window.addEventListener('click', (e) => {
                if (e.target === customHintModal) {
                    closeCustomHintModal();
                }
            });
        }
    </script>

    <?php
    // Include step-by-step TTS player modal component
    require_once(__DIR__ . '/components/step_player_modal.php');
    ?>

    <!-- Step-by-Step TTS Player Script -->
    <script src="/moodle/local/augmented_teacher/alt42/teachingsupport/js/step_player.js"></script>

    <!-- 이미지 확대 모달 -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal(event)">
        <span class="image-modal-close" onclick="closeImageModal(event)">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" src="" alt="확대 이미지">
        </div>
    </div>

    <script>
        // 이미지 확대 모달 열기
        function openImageModal(imageSrc) {
            console.log('[openImageModal] Opening modal with image:', imageSrc);
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');

            if (modal && modalImg) {
                modal.style.display = 'block';
                modalImg.src = imageSrc;

                // body 스크롤 방지
                document.body.style.overflow = 'hidden';
            }
        }

        // 이미지 확대 모달 닫기
        function closeImageModal(event) {
            if (event) {
                event.stopPropagation();
            }

            const modal = document.getElementById('imageModal');
            if (modal) {
                modal.style.display = 'none';

                // body 스크롤 복원
                document.body.style.overflow = 'auto';
            }
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImageModal(event);
            }
        });
    </script>
</body>
</html> 