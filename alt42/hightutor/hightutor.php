
<!DOCTYPE html>
<!-- saved from url=(0059)http://34.64.175.237/local/classes/univ_exam/hightutor.html -->
<html lang="ko"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학 문제 학습 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Malgun Gothic', sans-serif;
            background-color: #f5f5f5;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* 진행률 바 */
        .progress-bar-container {
            background-color: #fff;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .problem-counter {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            min-width: 120px;
        }
        
        .progress-bar {
            flex: 1;
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s ease;
            width: 0%;
        }
        
        .score-display {
            font-size: 16px;
            color: #666;
            min-width: 100px;
            text-align: right;
        }
        
        /* 질문 카운터 */
        
        /* 문제 네비게이션 */
        .problem-nav {
            background-color: #fff;
            padding: 10px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .nav-button {
            padding: 6px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .nav-button:hover:not(:disabled) {
            background: #f5f5f5;
            border-color: #999;
        }
        
        .nav-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .nav-button.complete {
            background: #e8f5e9;
            border-color: #4CAF50;
            color: #2e7d32;
        }
        
        .nav-button.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        /* 메인 컨테이너 - 투 칼럼 레이아웃 */
        .main-container {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        
        /* 좌측 칼럼 - 문제 정보 */
        .left-column {
            width: 40%;
            background: white;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .problem-section {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .problem-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .problem-description {
            font-size: 16px;
            line-height: 1.6;
            color: #495057;
            margin-bottom: 15px;
        }
        
        .problem-box {
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            position: relative;
        }
        
        .equation {
            margin: 10px 0;
            font-size: 18px;
            color: #495057;
            position: relative;
        }
        
        /* 1등급 시선 섹션 */
        .insight-section {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            flex: 1;
            overflow-y: auto;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .insight-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }
        
        .insight-button:hover {
            background-color: #45a049;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .eye-icon {
            width: 16px;
            height: 16px;
        }
        
        #insightList {
            margin-top: 10px;
        }
        
        .insight-item {
            margin-bottom: 12px;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
            border-left: 3px solid #4CAF50;
            font-size: 14px;
            opacity: 0;
            transform: translateY(10px);
            animation: slideIn 0.5s forwards;
            display: block;
        }
        
        .insight-question {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .insight-question:hover {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 4px;
            margin: -4px;
        }
        
        .insight-text {
            flex: 1;
            line-height: 1.4;
        }
        
        .insight-text.typing::after {
            content: '|';
            animation: blink 0.8s infinite;
            color: #4CAF50;
            font-weight: bold;
        }
        
        .explain-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.8;
        }
        
        .explain-button:hover {
            opacity: 1;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
        }
        
        .explain-button.active {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        
        .explain-button:disabled {
            background: #ddd;
            color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .insight-answer {
            margin-top: 12px;
            padding: 12px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 6px;
            border-left: 3px solid #667eea;
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.4s ease;
        }
        
        .insight-answer.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        
        .insight-answer-content {
            color: #333;
            line-height: 1.5;
            font-size: 13px;
        }
        
        .insight-number {
            display: inline-block;
            width: 24px;
            height: 24px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            line-height: 24px;
            border-radius: 50%;
            font-size: 11px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .insight-number.question {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* 1등급 질문 섹션 */
        .creative-section {
            padding: 20px;
            background: white;
            overflow-y: auto;
            display: none;
        }
        
        .creative-section.active {
            display: block;
        }
        
        /* 우측 칼럼 - 해설 */
        .right-column {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .solution-container {
            flex: 1;
            padding: 20px 30px 120px 30px; /* 하단 패딩 추가 */
            overflow-y: auto;
            position: relative;
        }
        
        #explanationArea {
            max-width: 800px;
            margin: 0 auto;
            padding-bottom: 120px; /* 하단에 충분한 여백 확보 */
        }
        
        .explanation-step {
            margin: 20px 0;
            opacity: 0;
            animation: fadeIn 0.5s forwards;
        }
        
        .explanation-step.active-answer {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
            margin: 10px 0;
        }
        
        .question {
            color: #007bff;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            position: relative;
        }
        
        .thinking-indicator {
            display: inline-block;
            margin-left: 10px;
            color: #999;
            font-size: 16px;
            font-weight: normal;
        }
        
        .thinking-indicator::after {
            content: '';
            animation: dots 1.5s infinite;
        }
        
        .answer {
            color: #333;
            font-size: 18px;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .next-button {
            background-color: transparent;
            color: #999;
            border: none;
            padding: 15px 0;
            font-size: 14px;
            cursor: pointer;
            margin: 20px auto;
            transition: all 0.3s;
            width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
        }
        
        .next-button:hover {
            color: #666;
            border-bottom-color: #999;
        }
        
        .next-button:disabled {
            color: #e0e0e0;
            cursor: not-allowed;
            border-bottom-color: #f0f0f0;
        }
        
        .arrow-down {
            display: inline-block;
            width: 0;
            height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 8px solid currentColor;
            transition: transform 0.3s;
        }
        
        .next-button:hover .arrow-down {
            transform: translateY(2px);
        }
        
        /* 하이라이트 마크 */
        .highlight-mark {
            background-color: rgba(255, 235, 59, 0);
            transition: background-color 0.5s ease-in-out;
            padding: 2px 4px;
            border-radius: 2px;
            position: relative;
            display: inline-block;
        }
        
        .highlight-mark.active {
            background-color: rgba(255, 235, 59, 0.2);
            animation: pulse 0.5s;
        }
        
        .highlight-mark.active::after {
            content: attr(data-insight);
            position: absolute;
            right: -16px;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(76, 175, 80, 0.08);
            color: rgba(76, 175, 80, 0.7);
            padding: 0px 4px;
            font-size: 9px;
            font-weight: normal;
            opacity: 0;
            animation: fadeInSubtle 0.5s forwards;
            z-index: 10;
            pointer-events: none;
            border-radius: 0 8px 8px 0;
            border: 1px solid rgba(76, 175, 80, 0.15);
            border-left: none;
            line-height: 1.2;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .highlight-mark.active::before {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-top: 5px solid transparent;
            border-bottom: 5px solid transparent;
            border-right: 6px solid rgba(76, 175, 80, 0.08);
            opacity: 0;
            animation: fadeInSubtle 0.5s forwards;
            z-index: 9;
        }
        
        /* 우측 해설 영역의 창의적 질문 스타일 */
        .creative-questions-solution {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #667eea;
        }
        
        .creative-title-solution {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .creative-loading-solution {
            text-align: center;
            padding: 20px 0;
            color: #666;
            font-style: italic;
        }
        
        .creative-question-solution {
            background: white;
            padding: 16px;
            margin: 12px 0;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }
        
        .creative-question-solution:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-color: #667eea;
        }
        
        .q-header-solution {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .q-number-solution {
            background: #667eea;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .q-text-solution {
            font-size: 15px;
            color: #333;
            line-height: 1.5;
            flex: 1;
        }
        
        .q-hint-solution {
            background: #f0f7ff;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 13px;
            color: #0066cc;
            border-left: 2px solid #0066cc;
            margin-left: 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .hint-text {
            flex: 1;
        }
        
        .detail-link {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            font-weight: bold;
            transition: all 0.2s;
            margin-left: 10px;
            cursor: pointer;
        }
        
        .detail-link:hover:not(.disabled) {
            background: #5a67d8;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        .detail-link.selected {
            background: #4CAF50;
            color: white;
            cursor: default;
        }
        
        .detail-link.selected:hover {
            background: #4CAF50;
            color: white;
            transform: none;
        }
        
        .detail-link.disabled {
            background: #ddd;
            color: #888;
            cursor: not-allowed;
            transform: none;
            opacity: 0.7;
        }
        
        .detail-link.disabled:hover {
            background: #ddd;
            color: #888;
            transform: none;
            opacity: 0.7;
        }
        
        .creative-footer-solution {
            text-align: center;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #666;
        }

        /* 창의적 질문 스타일 */
        .creative-questions {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
            position: relative;
            overflow: hidden;
            opacity: 0;
            animation: fadeInUp 0.6s ease-out forwards;
            animation-delay: 0.3s;
        }
        
        .creative-questions::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .creative-loading {
            text-align: center;
            padding: 40px 0;
        }
        
        .thinking-dots {
            margin-top: 20px;
            font-size: 16px;
            color: #666;
        }
        
        .dots-animation {
            display: inline-block;
            width: 30px;
            text-align: left;
            animation: dots 1.5s infinite;
        }
        
        .creative-title {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            display: block;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .creative-question {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
            opacity: 0;
        }
        
        .creative-question::after {
            content: '→';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 24px;
            color: #667eea;
            opacity: 0;
            transition: all 0.3s;
        }
        
        .creative-question:hover {
            transform: translateX(10px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            border-color: #667eea;
        }
        
        .creative-question:hover::after {
            opacity: 0.5;
            right: 15px;
        }
        
        .q-number {
            display: inline-block;
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
            margin-right: 15px;
            font-size: 16px;
        }
        
        .q-text {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
            display: inline-block;
            width: calc(100% - 60px);
            vertical-align: middle;
        }
        
        .q-text.generating {
            color: #999;
            font-style: italic;
        }
        
        .generating-text {
            color: #999;
            font-style: italic;
        }
        
        .generating-dots {
            display: inline-block;
            width: 20px;
            text-align: left;
            animation: dots 1s infinite;
        }
        
        .q-hint {
            margin-top: 10px;
            margin-left: 50px;
            padding: 10px 15px;
            background: #f0f7ff;
            border-radius: 8px;
            font-size: 14px;
            color: #0066cc;
            border-left: 3px solid #0066cc;
            opacity: 0;
        }
        
        .creative-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px dashed #dee2e6;
            font-size: 16px;
            color: #666;
        }
        
        /* 질문 가능한 요소 스타일 */
        .questionable {
            position: relative;
            cursor: help;
            transition: all 0.3s ease;
            border-radius: 4px;
            display: inline-block;
        }
        
        .questionable:hover {
            border: 2px solid #667eea;
            border-radius: 4px;
        }
        
        /* Tooltip 스타일 - 스피커 아이콘용 */
        .context-tooltip {
            position: absolute;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            border-radius: 50%;
            font-size: 18px;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            opacity: 0;
            transform: translateY(10px) scale(0.8);
            transition: all 0.3s ease;
            cursor: pointer;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .context-tooltip.active {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .context-tooltip:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
        }
        
        .context-tooltip::before {
            content: '';
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid #667eea;
        }
        
        .speaker-icon {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }
        
        /* 원본 텍스트 숨김 효과 제거 */
        
        /* 질문 목록 팝업 - 더 이상 사용 안함 */
        .question-popup {
            display: none !important;
        }
        
        /* 기타 스타일 */
        .highlight {
            background-color: rgba(255, 235, 59, 0.4);
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        
        .important {
            color: #d32f2f;
            font-weight: bold;
            font-size: 20px;
        }
        
        .final-emphasis {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .typing {
            display: inline;
            border-right: 2px solid #333;
            animation: blink 0.8s infinite;
        }
        
        .loading-dots::after {
            content: '';
            animation: dots 1.5s infinite;
        }
        
        /* Blur 효과 */
        .blur-background {
            filter: blur(3px);
            opacity: 0.6;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .blur-background.clickable {
            pointer-events: auto;
        }
        
        .blur-background.clickable:hover {
            opacity: 0.7;
        }
        
        /* Unblurred step (토글된 상태) */
        .unblurred-step {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(102, 126, 234, 0.3);
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .unblurred-step:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
        
        /* 생성 중 표시기 */
        .generating-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(102, 126, 234, 0.9);
            color: white;
            padding: 10px;
            border-radius: 50%;
            z-index: 999;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            backdrop-filter: blur(10px);
            width: 40px;
            height: 40px;
        }
        
        .generating-indicator.active {
            display: flex;
        }
        
        .generating-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 애니메이션 */
        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
        
        @keyframes fadeInSubtle {
            0% { opacity: 0; transform: translateY(-50%) translateX(5px); }
            100% { opacity: 0.6; transform: translateY(-50%) translateX(0); }
        }
        
        @keyframes fadeInUp {
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
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.01); }
            100% { transform: scale(1); }
        }
        
        @keyframes blink {
            0%, 50% { border-color: #333; }
            51%, 100% { border-color: transparent; }
        }
        
        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }
        
        @keyframes fadeInSimple {
            from { opacity: 0; }
            to { opacity: 0.8; }
        }
        
        /* 화이트보드 스타일 */
        .whiteboard-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: white;
            opacity: 0;
            visibility: hidden;
            transition: all 0.8s ease;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }
        
        .whiteboard-container.active {
            opacity: 1;
            visibility: visible;
        }
        
        body.evaluation-mode {
            overflow: hidden;
        }
        
        body.evaluation-mode .progress-bar-container,
        body.evaluation-mode .problem-nav,
        body.evaluation-mode .main-container {
            display: none;
        }
        
        .whiteboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .close-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            color: white;
        }
        
        .close-button:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        .answer-selection {
            background: rgba(255,255,255,0.15);
            padding: 15px 20px;
            border-radius: 10px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .answer-selection label {
            font-size: 18px;
            font-weight: bold;
        }
        
        .answer-dropdown {
            background: white;
            color: #333;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            min-width: 150px;
            transition: all 0.3s;
        }
        
        .answer-dropdown:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .answer-dropdown:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.5);
        }
        
        .whiteboard-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .whiteboard-question {
            font-size: 18px;
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 12px;
            margin-top: 10px;
            line-height: 1.6;
        }
        
        .whiteboard-tools {
            background: #f8f9fa;
            padding: 15px 40px;
            display: flex;
            gap: 15px;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex-wrap: wrap;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tool-button {
            background: white;
            border: 2px solid #dee2e6;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .tool-button:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .tool-button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .color-picker {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .color-picker:hover {
            transform: scale(1.1);
        }
        
        .thickness-slider {
            width: 100px;
        }
        
        .canvas-wrapper {
            flex: 1;
            position: relative;
            overflow: hidden;
            background: #fafafa;
        }
        
        #whiteboardCanvas {
            position: absolute;
            top: 0;
            left: 0;
            cursor: crosshair;
            background: white;
            box-shadow: inset 0 0 30px rgba(0,0,0,0.02);
        }
        
        .submit-button {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 25px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        
        /* 전환 메시지 */
        .transition-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.5s ease;
            z-index: 999;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .transition-message.active {
            opacity: 1;
            visibility: visible;
        }
        
        .transition-icon {
            font-size: 60px;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .transition-text {
            font-size: 24px;
            color: #667eea;
            font-weight: bold;
        }
        
        /* 채점 결과 팝업 스타일 */
        .result-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 90%;
            display: none;
            z-index: 2001;
            text-align: center;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .result-popup.active {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        
        .result-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .result-popup-overlay.active {
            opacity: 1;
        }
        
        .result-popup-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .result-popup-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .result-popup-score {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .result-popup-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .result-popup-button {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .result-popup-button.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .result-popup-button.secondary {
            background: #f0f0f0;
            color: #666;
        }
        
        .result-popup-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        /* 해설 팝업 스타일 */
        .solution-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            display: none;
            z-index: 2003;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .solution-popup.active {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        
        .solution-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            z-index: 2002;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .solution-popup-overlay.active {
            opacity: 1;
        }
        
        .solution-popup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .solution-popup-title {
            font-size: 28px;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .solution-popup-content {
            line-height: 1.8;
            color: #333;
        }
        
        .solution-step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .solution-step-title {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .solution-step-content {
            font-size: 16px;
            color: #555;
        }
        
        .solution-answer-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }
        
        .solution-close-button {
            display: block;
            margin: 30px auto 0;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .solution-close-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        
        /* 다음 문제 버튼 */
        .next-problem-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
            display: none;
            z-index: 900;
        }
        
        .next-problem-button.active {
            display: block;
        }
        
        .next-problem-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        
        /* 질문 기록 패널 */
        
        
        
        /* 단계별 평가 화이트보드 */
        .step-evaluation {
            margin-top: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e9ecef;
            display: none;
            animation: slideDown 0.3s ease-out;
        }
        
        .step-evaluation.active {
            display: block;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .evaluation-question {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .evaluation-question::before {
            content: '🎙️';
            font-size: 20px;
        }
        
        .mini-whiteboard {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            height: 200px;
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
        }
        
        .mini-canvas {
            cursor: crosshair;
            display: block;
        }
        
        .evaluation-tools {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .eval-tool-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .eval-tool-btn:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }
        
        .eval-tool-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .voice-record-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .voice-record-btn:hover {
            background: #45a049;
        }
        
        .voice-record-btn.recording {
            background: #f44336;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }
        
        .evaluation-submit {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .submit-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .submit-btn.primary {
            background: #667eea;
            color: white;
        }
        
        .submit-btn.primary:hover {
            background: #5a67d8;
        }
        
        .submit-btn.secondary {
            background: #e9ecef;
            color: #666;
        }
        
        .submit-btn.secondary:hover {
            background: #dee2e6;
        }
        
        .submission-feedback {
            text-align: center;
            padding: 20px;
            background: #d4edda;
            border-radius: 8px;
            color: #155724;
            font-weight: bold;
            display: none;
        }
        
        .submission-feedback.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
        
        /* 음성 재생 인디케이터 - 최소화된 디자인 */
        .voice-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(10px);
            color: #667eea;
            padding: 8px 12px;
            border-radius: 20px;
            display: none;
            align-items: center;
            gap: 8px;
            z-index: 2000;
            font-size: 12px;
            border: 1px solid rgba(102, 126, 234, 0.2);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }
        
        .voice-indicator.active {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }
        
        .voice-wave {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .voice-bar {
            width: 2px;
            background: #667eea;
            border-radius: 1px;
            animation: wave 0.6s ease-in-out infinite;
            opacity: 0.8;
        }
        
        .voice-bar:nth-child(1) { height: 8px; animation-delay: 0s; }
        .voice-bar:nth-child(2) { height: 12px; animation-delay: 0.1s; }
        .voice-bar:nth-child(3) { height: 10px; animation-delay: 0.2s; }
        .voice-bar:nth-child(4) { height: 14px; animation-delay: 0.3s; }
        .voice-bar:nth-child(5) { height: 11px; animation-delay: 0.4s; }
        
        @keyframes wave {
            0%, 100% { transform: scaleY(1); }
            50% { transform: scaleY(1.5); }
        }
        
        /* 검수자 정보 */
        .reviewer-info {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #666;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 998;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .reviewer-info:hover {
            opacity: 1;
        }
        
        /* 모바일에서 위치 조정 */
        @media (max-width: 768px) {
            .reviewer-info {
                bottom: 15px;
                right: 15px;
                font-size: 11px;
                padding: 6px 10px;
            }
        }
        @media (max-width: 1024px) {
            .left-column {
                width: 50%;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .left-column {
                width: 100%;
                height: 40vh;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .right-column {
                height: 60vh;
            }
            
            .creative-question {
                padding: 15px;
            }
            
            .q-text {
                font-size: 14px;
                width: calc(100% - 50px);
            }
            
            .q-number {
                width: 30px;
                height: 30px;
                line-height: 30px;
                font-size: 14px;
                margin-right: 10px;
            }
            
            .q-hint {
                margin-left: 40px;
                font-size: 13px;
            }
            
            /* 모바일에서 창의적 질문 스타일 조정 */
            .creative-question-solution {
                padding: 12px;
                margin: 8px 0;
            }
            
            .q-header-solution {
                gap: 8px;
            }
            
            .q-number-solution {
                width: 20px;
                height: 20px;
                font-size: 11px;
            }
            
            .q-text-solution {
                font-size: 14px;
            }
            
            .q-hint-solution {
                margin-left: 28px;
                padding: 8px 10px;
                font-size: 12px;
            }
            
            .detail-link {
                font-size: 10px;
                padding: 3px 6px;
            }
            
            .detail-link.selected {
                font-size: 9px;
            }
            
            .detail-link.disabled {
                font-size: 9px;
            }
            
            /* 모바일에서 생성 표시기 위치 조정 */
            .generating-indicator {
                top: 15px;
                width: 35px;
                height: 35px;
            }
            
            .generating-indicator[style*="left"] {
                left: 15px !important;
                right: auto !important;
            }
            
            .generating-indicator:not([style*="left"]) {
                right: 15px;
                left: auto;
            }
            
            .generating-spinner {
                width: 14px;
                height: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- 진행률 바 -->
    <div class="progress-bar-container">
        <div class="problem-counter">문제 <span id="currentProblem">1</span> / <span id="totalProblems">20</span></div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 5%;"></div>
        </div>
        <div class="score-display">점수: <span id="totalScore">0</span>점</div>
    </div>
    
    <!-- 문제 네비게이션 -->
    <div class="problem-nav" id="problemNav"><button class="nav-button current">1</button><button class="nav-button" disabled="">2</button><button class="nav-button" disabled="">3</button><button class="nav-button" disabled="">4</button><button class="nav-button" disabled="">5</button><button class="nav-button" disabled="">6</button><button class="nav-button" disabled="">7</button><button class="nav-button" disabled="">8</button><button class="nav-button" disabled="">9</button><button class="nav-button" disabled="">10</button><button class="nav-button" disabled="">11</button><button class="nav-button" disabled="">12</button><button class="nav-button" disabled="">13</button><button class="nav-button" disabled="">14</button><button class="nav-button" disabled="">15</button><button class="nav-button" disabled="">16</button><button class="nav-button" disabled="">17</button><button class="nav-button" disabled="">18</button><button class="nav-button" disabled="">19</button><button class="nav-button" disabled="">20</button></div>
    
    <!-- 메인 컨테이너 -->
    <div class="main-container">
        <!-- 좌측 칼럼 -->
        <div class="left-column">
            <!-- 문제 섹션 -->
            <div class="problem-section">
                <h2 class="problem-title" id="problemTitle">대칭식 문제</h2>
                <p class="problem-description" id="problemDescription"><span class="highlight-mark questionable" data-insight="1">세 실수 a, b, c</span>가 <span class="highlight-mark questionable" data-insight="2">다음 조건을 모두 만족시킬 때</span>, <span class="highlight-mark questionable" data-insight="3">abc의 값</span>을 구하여라.</p>
                <div class="problem-box" id="problemBox">
                    <div id="conditionsArea"><div class="equation questionable">(가) <span class="highlight-mark questionable" data-insight="4">a³ - 5a² + 2a + 33</span> = <span class="highlight-mark questionable" data-insight="5">a² + b² + c²</span></div><div class="equation questionable">(나) <span class="highlight-mark questionable" data-insight="4">b³ - 5b² + 2b + 33</span> = <span class="highlight-mark questionable" data-insight="5">a² + b² + c²</span></div><div class="equation questionable">(다) <span class="highlight-mark questionable" data-insight="4">c³ - 5c² + 2c + 33</span> = <span class="highlight-mark questionable" data-insight="5">a² + b² + c²</span></div></div>
                </div>
            </div>
            
            <!-- 1등급 시선 섹션 -->
            <div class="insight-section" id="insightSection">
                <div class="section-header">
                    <h3 class="section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 5C7 5 2.73 8.11 1 12.5C2.73 16.89 7 20 12 20C17 20 21.27 16.89 23 12.5C21.27 8.11 17 5 12 5ZM12 17.5C9.24 17.5 7 15.26 7 12.5C7 9.74 9.24 7.5 12 7.5C14.76 7.5 17 9.74 17 12.5C17 15.26 14.76 17.5 12 17.5ZM12 9.5C10.34 9.5 9 10.84 9 12.5C9 14.16 10.34 15.5 12 15.5C13.66 15.5 15 14.16 15 12.5C15 10.84 13.66 9.5 12 9.5Z"></path>
                        </svg>
                        1등급 시선
                    </h3>
                    <button class="insight-button" id="insightButton">1등급 분석 시작</button>
                </div>
                <div id="insightList"></div>
            </div>
            
            <!-- 1등급 질문 섹션 -->
            <div class="creative-section" id="creativeSection" style="display: none;">
                <div class="section-header">
                    <h3 class="section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#667eea">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"></path>
                        </svg>
                        1등급 창의적 질문
                    </h3>
                </div>
                <div id="creativeQuestionsList"></div>
            </div>
        </div>
        
        <!-- 우측 칼럼 -->
        <div class="right-column">
            <div class="solution-container" id="solutionContainer">
                <div id="explanationArea"><div class="explanation-step"><div class="question">🤔 이 문제에서 가장 먼저 주목해야 할 특징은 무엇일까요?</div><div class="answer">세 개의 조건 (가), (나), (다)를 자세히 보면...

모두 우변이 <span class="highlight questionable">a² + b² + c²</span>로 같다는 것을 발견할 수 있습니다!

이것은 매우 중요한 단서입니다. 세 식의 좌변이 모두 같은 값이라는 의미죠.</div><div class="step-evaluation active" id="evaluation-0"><div class="evaluation-question"><div style="display: flex; align-items: center; gap: 10px;"><span>이 단계에서 가장 중요한 개념을 설명해 보세요.</span><button title="음성 녹음" style="font-size: 24px; cursor: pointer; border: 1px solid rgb(221, 221, 221); border-radius: 5px; padding: 5px 10px; margin-left: 10px; background: rgb(255, 204, 204);">🎤</button><button title="화이트보드" style="font-size: 24px; cursor: pointer; border: 1px solid rgb(221, 221, 221); border-radius: 5px; padding: 5px 10px; margin-left: 5px; background: rgb(204, 204, 255);">📋</button></div></div><div class="whiteboard-container" style="display: block; margin-top: 20px; padding: 15px; background-color: rgb(245, 245, 245); border-radius: 8px; border: 2px solid red; min-height: 150px; position: relative;"><div class="mini-whiteboard" style="background-color: white; border: 1px solid rgb(221, 221, 221); border-radius: 8px; height: 70px; position: relative; overflow: hidden;"><canvas class="mini-canvas" width="2526" height="70" data-tool="pen" style="position: absolute; top: 0px; left: 0px; cursor: crosshair;"></canvas></div><div class="whiteboard-tools" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;"><button class="eval-tool-btn active" title="펜" style="padding: 5px 10px; margin-right: 5px; cursor: pointer;">✏️</button><button class="eval-tool-btn" title="지우개" style="padding: 5px 10px; margin-right: 5px; cursor: pointer;">🧽</button><button class="eval-tool-btn" title="전체 지우기" style="padding: 5px 10px; margin-right: 5px; cursor: pointer;">🗑️</button><div style="flex: 1 1 0%; margin-left: 20px; position: relative;"><div class="timer-progress" style="width: 100%; height: 20px; background-color: rgb(224, 224, 224); border-radius: 10px; overflow: hidden; position: relative;"><div class="timer-bar" style="width: 56.6667%; height: 100%; background-color: rgb(76, 175, 80); transition: width 1s linear;"></div><div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: bold;">17초</div></div></div><button class="add-time-btn" title="30초 추가" style="padding: 5px 10px; font-size: 20px; cursor: pointer;">+<span style="margin-left: 5px; font-size: 14px;">(0)</span></button></div></div><div class="evaluation-submit"><button class="submit-btn primary">완료</button><button class="submit-btn secondary">건너뛰기</button></div><div class="submission-feedback" style="display: none;">✅ 선생님에게 전달되었습니다!</div></div></div><div class="explanation-step"><div class="question">💡 그렇다면 세 식의 좌변을 어떻게 정리할 수 있을까요?</div><div class="answer">조건 (가)에서: a³ - 5a² + 2a + 33 = (a² + b² + c²)
조건 (나)에서: b³ - 5b² + 2b + 33 = (a² + b² + c²)
조건 (다)에서: c³ - 5c² + 2c + 33 = (a² + b² + c²)

따라서 <span class="highlight questionable">a³ - 5a² + 2a + 33 = b³ - 5b² + 2b + 33 = c³ - 5c² + 2c + 33</span>

이것은 a, b, c가 모두 같은 형태의 식을 만족한다는 뜻입니다!</div><div class="step-evaluation active" id="evaluation-1"><div class="evaluation-question"><div style="display: flex; align-items: center; gap: 10px;"><span>이해한 내용을 설명해 보세요.</span><button title="음성 녹음" style="font-size: 24px; cursor: pointer; border: 1px solid rgb(221, 221, 221); border-radius: 5px; padding: 5px 10px; margin-left: 10px; background: white;">🎤</button><button title="화이트보드" style="font-size: 24px; cursor: pointer; border: 1px solid rgb(221, 221, 221); border-radius: 5px; padding: 5px 10px; margin-left: 5px; background: white;">📋</button></div></div><div class="whiteboard-container" style="display: none; margin-top: 20px; padding: 15px; background-color: rgb(245, 245, 245); border-radius: 8px;"><div class="mini-whiteboard" style="background-color: white; border: 1px solid rgb(221, 221, 221); border-radius: 8px; height: 70px; position: relative; overflow: hidden;"><canvas class="mini-canvas" width="600" height="70" data-tool="pen" style="position: absolute; top: 0px; left: 0px; cursor: crosshair;"></canvas></div><div class="whiteboard-tools" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;"><button class="eval-tool-btn active" title="펜" style="padding: 5px 10px; margin-right: 5px; cursor: pointer;">✏️</button><button class="eval-tool-btn" title="지우개" style="padding: 5px 10px; margin-right: 5px; cursor: pointer;">🧽</button><button class="eval-tool-btn" title="전체 지우기" style="padding: 5px 10px; margin-right: 5px; cursor: pointer;">🗑️</button><div style="flex: 1 1 0%; margin-left: 20px; position: relative;"><div class="timer-progress" style="width: 100%; height: 20px; background-color: rgb(224, 224, 224); border-radius: 10px; overflow: hidden; position: relative;"><div class="timer-bar" style="width: 100%; height: 100%; background-color: rgb(76, 175, 80); transition: width 1s linear;"></div><div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: bold;">30초</div></div></div><button class="add-time-btn" title="30초 추가" style="padding: 5px 10px; font-size: 20px; cursor: pointer;">+<span style="margin-left: 5px; font-size: 14px;">(0)</span></button></div></div><div class="evaluation-submit"><button class="submit-btn primary">완료</button><button class="submit-btn secondary">건너뛰기</button></div><div class="submission-feedback" style="display: none;">✅ 선생님에게 전달되었습니다!</div></div></div><div class="explanation-step"><div class="question">🎯 a, b, c가 만족하는 공통 방정식을 찾아볼까요?</div><div class="answer">x에 대한 삼차방정식을 세워보면:

<span class="highlight questionable">x³ - 5x² + 2x + 33 = (a² + b² + c²)</span>

이 방정식의 세 근이 바로 a, b, c입니다!

이를 정리하면:
x³ - 5x² + 2x + 33 - (a² + b² + c²) = 0</div><div class="step-evaluation active" id="evaluation-2"><div class="evaluation-question"><div style="display: flex; align-items: center; gap: 10px;"><span>이 단계의 핵심 아이디어를 정리해 보세요.</span><button title="음성 녹음" style="font-size: 24px; cursor: pointer; border: 1px solid rgb(221, 221, 221); border-radius: 5px; padding: 5px 10px; margin-left: 10px; background: white;">🎤</button><button title="화이트보드" style="font-size: 24px; cursor: pointer; border: 1px solid rgb(221, 221, 221); border-radius: 5px; padding: 5px 10px; margin-left: 5px; background: white;">📋</button></div></div><div class="whiteboard-container" style="display: none; margin-top: 20px; padding: 15px; background-color: rgb(245, 245, 245); border-radius: 8px;"><div class="mini-whiteboard" style="background-color: white; border: 1px solid rgb(221, 221, 221); border-radius: 8px; height: 70px; position: relative; overflow: hidden;"><canvas class="mini-canvas" width="600" height="70" data-tool="pen" style="position: absolute; top: 0px; left: 0px; cursor: crosshair;"></canvas></div><div class="whiteboard-tools" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;"><button class="eval-tool-btn active" title="펜" style="padding: 5px 10px; margin-right: 5px; cursor: pointer;">✏️</button><button class="eval-tool-btn" title="지우개" style="padding: 5px 10px; margin-right: 5px; cursor: pointer;">🧽</button><button class="eval-tool-btn" title="전체 지우기" style="padding: 5px 10px; margin-right: 5px; cursor: pointer;">🗑️</button><div style="flex: 1 1 0%; margin-left: 20px; position: relative;"><div class="timer-progress" style="width: 100%; height: 20px; background-color: rgb(224, 224, 224); border-radius: 10px; overflow: hidden; position: relative;"><div class="timer-bar" style="width: 100%; height: 100%; background-color: rgb(76, 175, 80); transition: width 1s linear;"></div><div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: bold;">30초</div></div></div><button class="add-time-btn" title="30초 추가" style="padding: 5px 10px; font-size: 20px; cursor: pointer;">+<span style="margin-left: 5px; font-size: 14px;">(0)</span></button></div></div><div class="evaluation-submit"><button class="submit-btn primary">완료</button><button class="submit-btn secondary">건너뛰기</button></div><div class="submission-feedback" style="display: none;">✅ 선생님에게 전달되었습니다!</div></div></div></div>
                <button class="next-button" id="nextButton" disabled="" style="display: block;"><span class="arrow-down"></span></button>
            </div>
        </div>
    </div>
    
    <!-- 전환 메시지 -->
    <div class="transition-message" id="transitionMessage">
        <div class="transition-icon">✍️</div>
        <div class="transition-text">서술평가를 시작합니다</div>
    </div>
    
    <!-- 화이트보드 컨테이너 -->
    <div class="whiteboard-container" id="whiteboardContainer">
        <div class="whiteboard-header">
            <button class="close-button" id="closeButton" title="종료">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path>
                </svg>
            </button>
            <div class="whiteboard-title">📝 서술형 평가</div>
            <div class="whiteboard-question" id="similarProblemDescription">
                다음 문제를 풀고, 풀이 과정을 자세히 작성하세요:<br>
                <strong>문제:</strong> <span id="similarProblemText"></span>
            </div>
            <div class="answer-selection">
                <label for="answerSelect">정답 선택:</label>
                <select id="answerSelect" class="answer-dropdown">
                    <!-- 동적으로 생성됨 -->
                </select>
            </div>
        </div>
        <div class="whiteboard-tools">
            <button class="tool-button active" id="penTool">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"></path>
                </svg>
                펜
            </button>
            <button class="tool-button" id="eraserTool">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15.14 3.63L12.37 6.4l4.24 4.24 2.77-2.77c.59-.59.59-1.54 0-2.12l-2.12-2.12c-.58-.59-1.53-.59-2.12 0zM11 7.83L3.41 15.41c-.78.78-.78 2.05 0 2.83l2.83 2.83c.78.78 2.05.78 2.83 0L16.66 13.48 11 7.83z"></path>
                </svg>
                지우개
            </button>
            <button class="tool-button" id="clearTool">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path>
                </svg>
                전체 지우기
            </button>
            <div style="display: flex; align-items: center; gap: 10px;">
                <label style="font-size: 14px;">색상:</label>
                <input type="color" class="color-picker" id="colorPicker" value="#000000">
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <label style="font-size: 14px;">굵기:</label>
                <input type="range" class="thickness-slider" id="thicknessSlider" min="1" max="20" value="2">
                <span id="thicknessValue">2</span>
            </div>
        </div>
        <div class="canvas-wrapper">
            <canvas id="whiteboardCanvas"></canvas>
            <button class="submit-button" id="submitButton">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path>
                </svg>
                제출하기
            </button>
        </div>
    </div>
    
    <!-- 채점 결과 팝업 -->
    <div class="result-popup-overlay" id="resultOverlay" style="display: none;"></div>
    <div class="result-popup" id="resultPopup" style="display: none;">
        <div class="result-popup-icon" id="resultIcon"></div>
        <div class="result-popup-title" id="resultTitle"></div>
        <div class="result-popup-score" id="resultScore"></div>
        <div class="result-popup-buttons">
            <button class="result-popup-button secondary" onclick="closeResultPopup()">닫기</button>
            <button class="result-popup-button primary" onclick="showSolution()">해설 보기</button>
        </div>
    </div>
    
    <!-- 해설 팝업 -->
    <div class="solution-popup-overlay" id="solutionOverlay" style="display: none;"></div>
    <div class="solution-popup" id="solutionPopup" style="display: none;">
        <div class="solution-popup-header">
            <h2 class="solution-popup-title">📚 문제 해설</h2>
        </div>
        <div class="solution-popup-content" id="solutionContent">
            <!-- 동적으로 생성됨 -->
        </div>
        <button class="solution-close-button" onclick="closeSolution()">닫기</button>
    </div>
    
    <!-- 다음 문제 버튼 -->
    <button class="next-problem-button" id="nextProblemButton" onclick="nextProblem()">다음 문제로 →</button>
    
    <!-- 질문 팝업 -->
    <div class="question-popup" id="questionPopup"></div>
    
    <!-- 생성 중 표시기 -->
    <div class="generating-indicator" id="generatingIndicator" style="left: auto; right: 20px; transform: none;">
        <div class="generating-spinner"></div>
    </div>
    
    <!-- 음성 재생 인디케이터 -->
    <div class="voice-indicator" id="voiceIndicator">
        <div class="voice-wave">
            <div class="voice-bar"></div>
            <div class="voice-bar"></div>
            <div class="voice-bar"></div>
            <div class="voice-bar"></div>
            <div class="voice-bar"></div>
        </div>
        <span>재생 중</span>
    </div>
    

    <!-- 검수자 정보 -->
    <div class="reviewer-info">
        검수 : 이태상 T
    </div>

    <script>
        // Blur 효과 관련 함수들
        function applyBlurEffect() {
            // 좌측 영역에만 blur 적용 (우측 해설 영역은 제외)
            const elementsToBlur = [
                document.querySelector('.progress-bar-container'),
                document.querySelector('.problem-nav'),
                document.querySelector('.left-column')
            ];
            
            elementsToBlur.forEach(element => {
                if (element && element.classList) {
                    element.classList.add('blur-background');
                }
            });
            
            // 생성 중 표시기를 우측으로 위치 (텍스트 없이 스피너만)
            const indicator = document.getElementById('generatingIndicator');
            if (indicator) {
                indicator.style.left = 'auto';
                indicator.style.right = '20px';
                indicator.classList.add('active');
            }
        }
        
        // 인사이트 생성 시 우측 칼럼 블러 처리
        function applyInsightBlurEffect() {
            // 우측 해설 영역에 blur 적용 (좌측은 제외)
            const elementsToBlur = [
                document.querySelector('.progress-bar-container'),
                document.querySelector('.problem-nav'),
                document.querySelector('.right-column')
            ];
            
            elementsToBlur.forEach(element => {
                if (element && element.classList) {
                    element.classList.add('blur-background');
                }
            });
            
            // 생성 중 표시기를 좌측으로 이동
            const indicator = document.getElementById('generatingIndicator');
            if (indicator) {
                indicator.style.right = 'auto';
                indicator.style.left = '20px';
                indicator.classList.add('active');
            }
        }
        
        function removeBlurEffect() {
            // blur 효과 제거 (모든 영역에서)
            const elementsToBlur = [
                document.querySelector('.progress-bar-container'),
                document.querySelector('.problem-nav'),
                document.querySelector('.left-column'),
                document.querySelector('.right-column'),
                document.querySelector('.problem-section')
            ];
            
            elementsToBlur.forEach(element => {
                if (element && element.classList) {
                    element.classList.remove('blur-background');
                }
            });
            
            // 우측 칼럼 blur는 제거하지 않음 (이전 단계들은 blur 유지)
            // removeRightColumnBlur();
            
            // 생성 중 표시기 비활성화 및 위치 초기화
            const indicator = document.getElementById('generatingIndicator');
            if (indicator && indicator.classList) {
                indicator.classList.remove('active');
                indicator.style.left = 'auto';
                indicator.style.right = '20px';
                indicator.style.transform = 'none';
            }
        }
        
        // 전역 함수들을 먼저 정의
        function closeResultPopup() {
            const overlay = document.getElementById('resultOverlay');
            const popup = document.getElementById('resultPopup');
            
            if (overlay && overlay.classList) {
                overlay.classList.remove('active');
                setTimeout(() => {
                    overlay.style.display = 'none';
                }, 300);
            }
            
            if (popup && popup.classList) {
                popup.classList.remove('active');
                setTimeout(() => {
                    popup.style.display = 'none';
                }, 300);
            }
        }
        
        function showSolution() {
            closeResultPopup();
            const solutionOverlay = document.getElementById('solutionOverlay');
            const solutionPopup = document.getElementById('solutionPopup');
            
            if (solutionOverlay) {
                solutionOverlay.style.display = 'block';
                setTimeout(() => {
                    if (solutionOverlay.classList) {
                        solutionOverlay.classList.add('active');
                    }
                }, 10);
            }
            
            if (solutionPopup) {
                solutionPopup.style.display = 'block';
                setTimeout(() => {
                    if (solutionPopup.classList) {
                        solutionPopup.classList.add('active');
                    }
                }, 10);
            }
        }
        
        function closeSolution() {
            const solutionOverlay = document.getElementById('solutionOverlay');
            const solutionPopup = document.getElementById('solutionPopup');
            
            if (solutionOverlay && solutionOverlay.classList) {
                solutionOverlay.classList.remove('active');
                setTimeout(() => {
                    solutionOverlay.style.display = 'none';
                }, 300);
            }
            
            if (solutionPopup && solutionPopup.classList) {
                solutionPopup.classList.remove('active');
                setTimeout(() => {
                    solutionPopup.style.display = 'none';
                }, 300);
            }
        }
        
        function checkAnswer(userAnswer) {
            const correctAnswer = currentProblemData.similarProblemAnswer;
            const isCorrect = userAnswer === correctAnswer;
            
            // 결과 팝업 표시
            const overlay = document.getElementById('resultOverlay');
            const popup = document.getElementById('resultPopup');
            const icon = document.getElementById('resultIcon');
            const title = document.getElementById('resultTitle');
            const score = document.getElementById('resultScore');
            
            if (!overlay || !popup || !icon || !title || !score) {
                alert('채점 결과를 표시할 수 없습니다. 페이지를 새로고침해주세요.');
                return;
            }
            
            // 유사문제 해설 내용 업데이트
            updateSimilarProblemSolution();
            
            // 팝업 표시
            overlay.style.display = 'block';
            popup.style.display = 'block';
            
            // 애니메이션을 위한 지연
            setTimeout(() => {
                if (overlay && overlay.classList) {
                    overlay.classList.add('active');
                }
                if (popup && popup.classList) {
                    popup.classList.add('active');
                }
            }, 10);
            
            if (isCorrect) {
                icon.innerHTML = '🎉';
                icon.style.color = '#4CAF50';
                title.textContent = '정답입니다!';
                score.textContent = '100점 획득! 문제를 완벽하게 이해하셨네요.';
                
                // 점수 추가
                totalScore += 100;
                updateScore();
                
                // 문제 완료 표시
                problemsCompleted[currentProblemIndex] = true;
                updateProblemNav();
                
                // 다음 문제 버튼 표시
                showNextProblemButton();
            } else {
                icon.innerHTML = '😢';
                icon.style.color = '#f44336';
                title.textContent = '아쉬워요!';
                score.textContent = `선택하신 답: ${userAnswer} (정답: ${correctAnswer})`;
            }
        }
        
        // 전역 함수들을 window 객체에 즉시 등록
        window.closeResultPopup = closeResultPopup;
        window.showSolution = showSolution;
        window.closeSolution = closeSolution;
        window.checkAnswer = checkAnswer;
        
        // 질문 관련 변수들
        let remainingQuestions = 10;
        let currentQuestionPopup = null;
        
        // 질문 관련 함수들
        function updateQuestionCounter() {
            document.getElementById('remainingQuestions').textContent = remainingQuestions;
        }
        
        function addQuestionableElements() {
            // 문제 설명, 조건들에 질문 가능한 클래스 추가
            const elements = document.querySelectorAll('.equation, .highlight-mark, .answer span');
            elements.forEach(el => {
                if (!el.classList.contains('questionable')) {
                    el.classList.add('questionable');
                    el.addEventListener('mouseenter', showContextTooltip);
                    el.addEventListener('mouseleave', hideContextTooltip);
                    el.addEventListener('click', handleQuestionClick);
                }
            });
        }
        
        function showContextTooltip(e) {
            if (remainingQuestions <= 0) return;
            
            const target = e.currentTarget;
            const rect = target.getBoundingClientRect();
            
            // 기존 tooltip 제거
            const existingTooltip = document.querySelector('.context-tooltip');
            if (existingTooltip) {
                existingTooltip.remove();
            }
            
            // tooltip 생성 (스피커 아이콘)
            const tooltip = document.createElement('div');
            tooltip.className = 'context-tooltip';
            tooltip.title = '클릭하면 음성으로 설명을 들을 수 있습니다';
            
            // 스피커 아이콘 SVG
            tooltip.innerHTML = `
                <svg class="speaker-icon" viewBox="0 0 24 24">
                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                </svg>
            `;
            
            // 위치 계산 (더 가까이)
            const tooltipY = rect.bottom + window.scrollY + 8;
            const tooltipX = rect.left + window.scrollX + (rect.width / 2) - 22; // 중앙 정렬
            
            tooltip.style.position = 'absolute';
            tooltip.style.top = tooltipY + 'px';
            tooltip.style.left = tooltipX + 'px';
            
            document.body.appendChild(tooltip);
            
            // 클릭 이벤트 추가
            tooltip.addEventListener('click', function(e) {
                e.stopPropagation();
                handleSpeakerClick(target);
            });
            
            // 마우스 leave 이벤트 추가
            tooltip.addEventListener('mouseleave', function() {
                this.classList.remove('active');
                setTimeout(() => {
                    if (this.parentNode) {
                        this.remove();
                    }
                }, 300);
                currentQuestionPopup = null;
            });
            
            // 애니메이션 시작
            setTimeout(() => {
                tooltip.classList.add('active');
            }, 10);
            
            currentQuestionPopup = {
                element: target,
                tooltip: tooltip
            };
        }
        
        function hideContextTooltip(e) {
            setTimeout(() => {
                const tooltip = document.querySelector('.context-tooltip');
                // 스피커 아이콘이 호버되고 있으면 숨기지 않음
                if (tooltip && !tooltip.matches(':hover') && currentQuestionPopup && currentQuestionPopup.tooltip === tooltip) {
                    tooltip.classList.remove('active');
                    setTimeout(() => {
                        if (tooltip.parentNode) {
                            tooltip.remove();
                        }
                    }, 300);
                    currentQuestionPopup = null;
                }
            }, 150);
        }
        
        function getContextualMeaning(element) {
            const text = element.textContent.toLowerCase();
            const originalText = element.textContent;
            
            // 수식 관련 문맥 분석
            if (text.includes('³') || text.includes('²')) {
                return {
                    title: "지수의 의미",
                    meaning: `'${originalText}'는 거듭제곱을 나타냅니다. 이 문제에서는 세 변수가 모두 같은 차수의 방정식을 만족함을 보여주는 핵심 단서입니다. 차수가 같다는 것은 대칭성을 암시합니다.`
                };
            }
            
            if (text.includes('=')) {
                return {
                    title: "등식의 의미",
                    meaning: `'${originalText}'는 좌변과 우변이 같음을 나타냅니다. 이 문제에서 여러 등식의 우변이 모두 같다는 것은 세 변수가 같은 방정식의 근임을 의미하는 결정적 단서입니다.`
                };
            }
            
            if (text.includes('a² + b² + c²')) {
                return {
                    title: "공통 우변의 의미",
                    meaning: `'${originalText}'가 모든 조건의 우변에 나타나는 것이 이 문제의 핵심입니다. 이는 a, b, c가 같은 삼차방정식의 세 근임을 알려주는 결정적 단서입니다.`
                };
            }
            
            if (text.includes('세 실수 a, b, c')) {
                return {
                    title: "변수의 특성",
                    meaning: `'${originalText}'는 이 문제가 3개의 미지수를 다룬다는 것을 명시합니다. 세 개의 조건과 세 개의 미지수가 있어 해가 유일하게 결정될 수 있음을 시사합니다.`
                };
            }
            
            if (text.includes('abc의 값')) {
                return {
                    title: "목표값의 의미",
                    meaning: `'${originalText}'는 세 근의 곱을 구하라는 것입니다. 근과 계수의 관계에서 세 근의 곱은 상수항과 직접적으로 연결되므로, 삼차방정식을 찾으면 쉽게 구할 수 있습니다.`
                };
            }
            
            if (text.includes('조건을 모두 만족')) {
                return {
                    title: "조건의 완전성",
                    meaning: `'${originalText}'는 세 조건이 동시에 성립해야 함을 강조합니다. 이는 단순히 개별 조건이 아니라 연립방정식으로 접근해야 함을 의미합니다.`
                };
            }
            
            // 방정식 형태 분석
            if (text.includes('a³ - 5a²') || text.includes('b³ - 5b²') || text.includes('c³ - 5c²')) {
                return {
                    title: "동일한 함수 형태",
                    meaning: `'${originalText}'는 f(x) = x³ - 5x² + 2x + 33 형태의 함수입니다. 세 조건이 모두 같은 함수 꼴이라는 것은 a, b, c가 이 함수값이 같은 세 점임을 의미합니다.`
                };
            }
            
            // 일반적인 경우
            if (element.classList.contains('equation')) {
                return {
                    title: "방정식의 역할",
                    meaning: `이 방정식 '${originalText}'는 문제 해결의 핵심 조건입니다. 다른 조건들과 함께 분석하면 변수들 사이의 관계를 파악할 수 있습니다.`
                };
            }
            
            if (element.classList.contains('highlight-mark')) {
                return {
                    title: "핵심 키워드",
                    meaning: `'${originalText}'는 이 문제의 핵심 개념입니다. 이 부분을 정확히 이해하면 문제 해결의 실마리를 찾을 수 있습니다.`
                };
            }
            
            return {
                title: "문맥 정보",
                meaning: `'${originalText}'는 문제 해결에 중요한 정보를 담고 있습니다. 전체 문제의 맥락에서 이 부분의 의미를 파악해보세요.`
            };
        }
        
        function handleQuestionClick(e) {
            if (remainingQuestions <= 0) {
                alert('오늘의 질문 횟수를 모두 사용했습니다.');
                return;
            }
            
            const target = e.currentTarget;
            const elementText = target.textContent;
            
            // 기본 질문 생성
            const contextInfo = getContextualMeaning(target);
            const question = `${contextInfo.title}에 대해 더 자세히 설명해주세요`;
            
            // 질문 횟수 차감
            remainingQuestions--;
            updateQuestionCounter();
            
            // tooltip 숨기기
            const tooltip = document.querySelector('.context-tooltip');
            if (tooltip) {
                tooltip.classList.remove('active');
                setTimeout(() => {
                    if (tooltip.parentNode) {
                        tooltip.remove();
                    }
                }, 300);
            }
            
            // 음성 설명 재생
            playVoiceExplanation(question, contextInfo.meaning);
        }
        
        function getQuestionsForElement(element) {
            const text = element.textContent.toLowerCase();
            const questions = [];
            
            // 수식 관련 질문
            if (text.includes('³') || text.includes('²')) {
                questions.push('이 지수는 무엇을 의미하나요?');
                questions.push('왜 이런 차수의 식이 나왔나요?');
            }
            
            if (text.includes('=')) {
                questions.push('이 등식은 어떤 의미인가요?');
                questions.push('왜 양변이 같아야 하나요?');
            }
            
            if (text.includes('+') || text.includes('-')) {
                questions.push('이 연산의 의미는 무엇인가요?');
                questions.push('다른 방법으로 계산할 수 있나요?');
            }
            
            // 조건 관련 질문
            if (element.classList.contains('equation')) {
                questions.push('이 조건이 왜 필요한가요?');
                questions.push('이 조건에서 주목해야 할 점은?');
            }
            
            // 일반적인 질문 추가
            questions.push('이 부분을 더 자세히 설명해주세요');
            questions.push('다른 예시로 설명해주세요');
            
            return questions;
        }
        
        
        
        function playVoiceExplanation(question, context) {
            // 음성 인디케이터 표시
            const indicator = document.getElementById('voiceIndicator');
            indicator.classList.add('active');
            
            // 설명 텍스트 생성
            const explanation = generateExplanation(question, context);
            
            // Web Speech API 사용
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(explanation);
                utterance.lang = 'ko-KR';
                utterance.rate = 0.9;
                utterance.pitch = 1.1;
                
                utterance.onend = () => {
                    indicator.classList.remove('active');
                };
                
                speechSynthesis.speak(utterance);
            } else {
                // 음성 합성을 지원하지 않는 경우
                setTimeout(() => {
                    indicator.classList.remove('active');
                    alert(`설명: ${explanation}`);
                }, 2000);
            }
        }
        
        function generateExplanation(question, context) {
            const element = currentQuestionPopup.element;
            const text = element.textContent.toLowerCase();
            
            // 간결한 3단계 구조: 설명 > 다른 설명 > 핵심 강조
            if (context.includes('거듭제곱')) {
                return `지수는 거듭제곱을 나타냅니다. 이 문제에서는 세 변수가 모두 같은 차수의 식을 만족합니다. 핵심은 대칭성입니다.`;
            }
            
            if (context.includes('등식') || context.includes('좌변과 우변')) {
                return `등식은 좌변과 우변이 같음을 의미합니다. 세 조건의 우변이 모두 같습니다. 핵심은 같은 방정식의 근이라는 것입니다.`;
            }
            
            if (context.includes('공통 우변')) {
                return `모든 조건의 우변이 a² + b² + c²로 동일합니다. 이는 매우 특별한 구조입니다. 핵심은 세 변수가 삼차방정식의 근이라는 단서입니다.`;
            }
            
            if (context.includes('3개의 미지수') || context.includes('세 개의 조건')) {
                return `세 개의 미지수와 세 개의 조건이 있습니다. 조건의 개수와 미지수의 개수가 일치합니다. 핵심은 해가 유일하게 결정된다는 것입니다.`;
            }
            
            if (context.includes('근의 곱') || context.includes('상수항')) {
                return `abc는 세 근의 곱을 구하는 것입니다. 근과 계수의 관계를 사용합니다. 핵심은 삼차방정식의 상수항과 직결된다는 것입니다.`;
            }
            
            if (context.includes('동일한 함수') || context.includes('같은 함수')) {
                return `같은 형태의 삼차함수입니다. f(a) = f(b) = f(c) = 같은 값이 됩니다. 핵심은 세 점에서 함수값이 같다는 것입니다.`;
            }
            
            if (text.includes('a² + b² + c²')) {
                return `세 변수의 제곱의 합입니다. 모든 조건의 공통 우변입니다. 핵심은 이것이 문제 해결의 열쇠라는 것입니다.`;
            }
            
            if (text.includes('세 실수 a, b, c')) {
                return `세 개의 실수 변수입니다. 이들이 주인공입니다. 핵심은 이들 사이의 대칭적 관계입니다.`;
            }
            
            if (text.includes('abc')) {
                return `세 변수의 곱입니다. 우리가 구해야 할 답입니다. 핵심은 근과 계수의 관계로 쉽게 구할 수 있다는 것입니다.`;
            }
            
            if (text.includes('조건을 모두 만족')) {
                return `세 조건이 동시에 성립해야 합니다. 연립방정식으로 접근합니다. 핵심은 조건들이 서로 연결되어 있다는 것입니다.`;
            }
            
            // 일반적인 경우
            return `이 부분은 문제의 중요한 조건입니다. 다른 조건들과 연결하여 분석합니다. 핵심은 전체 패턴을 파악하는 것입니다.`;
        }
        
        // DOM이 완전히 로드된 후 초기화
        window.addEventListener('load', function() {
            // 팝업들이 초기에는 숨겨져 있는지 확인
            const resultOverlay = document.getElementById('resultOverlay');
            const resultPopup = document.getElementById('resultPopup');
            const solutionOverlay = document.getElementById('solutionOverlay');
            const solutionPopup = document.getElementById('solutionPopup');
            
            if (resultOverlay) resultOverlay.style.display = 'none';
            if (resultPopup) resultPopup.style.display = 'none';
            if (solutionOverlay) solutionOverlay.style.display = 'none';
            if (solutionPopup) solutionPopup.style.display = 'none';
            
            // 질문 카운터 초기화
            updateQuestionCounter();
        });
        
        // 전역 변수들
        let problemsData = null;
        let currentProblemIndex = 0;
        let currentProblemData = null;
        let totalScore = 0;
        let problemsCompleted = [];
        let currentStep = -1;
        let isTyping = false;
        let insightStep = 0;
        let insightInterval;
        let isInsightActive = false;
        let autoNextTimeout;
        
        // 샘플 데이터 (실제로는 별도 JSON 파일에서 로드)
        const sampleData = {
            "problems": [
                {
                    "id": 1,
                    "title": "대칭식 문제",
                    "problemInfo": {
                        "description": "세 실수 a, b, c가 다음 조건을 모두 만족시킬 때, abc의 값을 구하여라.",
                        "conditions": [
                            "(가) a³ - 5a² + 2a + 33 = a² + b² + c²",
                            "(나) b³ - 5b² + 2b + 33 = a² + b² + c²",
                            "(다) c³ - 5c² + 2c + 33 = a² + b² + c²"
                        ]
                    },
                    "analysisQuestions": [
                        {
                            "question": "왜 세 개의 조건이 모두 같은 우변을 가질까?",
                            "answer": "세 조건의 우변이 모두 <span class='highlight'>a² + b² + c²</span>로 같다는 것이 핵심입니다.<br><br>이는 우연이 아니라, a, b, c가 특별한 관계에 있음을 의미합니다. 즉, 이들이 같은 방정식을 만족하는 세 근이라는 강력한 단서입니다."
                        },
                        {
                            "question": "왜 좌변의 형태가 모두 동일할까?",
                            "answer": "세 식 모두 <span class='highlight'>x³ - 5x² + 2x + 33</span> 형태입니다.<br><br>이는 함수 f(x) = x³ - 5x² + 2x + 33에서 f(a) = f(b) = f(c) = k (상수)임을 의미합니다. 같은 함수에서 같은 함수값을 갖는 세 점이 바로 a, b, c입니다."
                        },
                        {
                            "question": "왜 삼차식과 이차식이 같은 값을 가질까?",
                            "answer": "좌변은 삼차식, 우변은 이차식입니다.<br><br>이것이 가능한 이유는 a, b, c가 방정식 <span class='highlight'>x³ - 5x² + 2x + 33 = a² + b² + c²</span>의 세 근이기 때문입니다. 삼차방정식은 정확히 3개의 근을 가집니다."
                        },
                        {
                            "question": "왜 abc의 값을 구할 수 있을까?",
                            "answer": "근과 계수의 관계를 사용할 수 있기 때문입니다.<br><br>a, b, c가 삼차방정식의 근이므로, 비에타의 공식에 의해 <span class='highlight'>abc = -상수항</span>입니다. 따라서 방정식만 찾으면 답을 즉시 구할 수 있습니다."
                        },
                        {
                            "question": "왜 대칭성이 중요할까?",
                            "answer": "세 변수 a, b, c가 완전히 동등한 역할을 합니다.<br><br>이런 <span class='highlight'>대칭적 구조</span>는 수학에서 매우 강력한 도구입니다. 하나의 변수에 대해 성립하는 성질이 다른 변수들에도 똑같이 적용되기 때문입니다."
                        }
                    ],
                    "highlightTags": [
                        { "text": "세 실수 a, b, c", "insightNumber": 1 },
                        { "text": "다음 조건을 모두 만족시킬 때", "insightNumber": 2 },
                        { "text": "abc의 값", "insightNumber": 3 },
                        { "text": "a³ - 5a² + 2a + 33", "insightNumber": 4 },
                        { "text": "b³ - 5b² + 2b + 33", "insightNumber": 4 },
                        { "text": "c³ - 5c² + 2c + 33", "insightNumber": 4 },
                        { "text": "a² + b² + c²", "insightNumber": 5 }
                    ],
                    "solutionSteps": [
                        {
                            "question": "🤔 이 문제에서 가장 먼저 주목해야 할 특징은 무엇일까요?",
                            "answer": "세 개의 조건 (가), (나), (다)를 자세히 보면...\n\n모두 우변이 <span class='highlight'>a² + b² + c²</span>로 같다는 것을 발견할 수 있습니다!\n\n이것은 매우 중요한 단서입니다. 세 식의 좌변이 모두 같은 값이라는 의미죠."
                        },
                        {
                            "question": "💡 그렇다면 세 식의 좌변을 어떻게 정리할 수 있을까요?",
                            "answer": "조건 (가)에서: a³ - 5a² + 2a + 33 = (a² + b² + c²)\n조건 (나)에서: b³ - 5b² + 2b + 33 = (a² + b² + c²)\n조건 (다)에서: c³ - 5c² + 2c + 33 = (a² + b² + c²)\n\n따라서 <span class='highlight'>a³ - 5a² + 2a + 33 = b³ - 5b² + 2b + 33 = c³ - 5c² + 2c + 33</span>\n\n이것은 a, b, c가 모두 같은 형태의 식을 만족한다는 뜻입니다!"
                        },
                        {
                            "question": "🎯 a, b, c가 만족하는 공통 방정식을 찾아볼까요?",
                            "answer": "x에 대한 삼차방정식을 세워보면:\n\n<span class='highlight'>x³ - 5x² + 2x + 33 = (a² + b² + c²)</span>\n\n이 방정식의 세 근이 바로 a, b, c입니다!\n\n이를 정리하면:\nx³ - 5x² + 2x + 33 - (a² + b² + c²) = 0"
                        },
                        {
                            "question": "📐 삼차방정식의 근과 계수의 관계를 활용해볼까요?",
                            "answer": "삼차방정식 x³ + px² + qx + r = 0의 세 근을 α, β, γ라 하면:\n\n• α + β + γ = -p\n• αβ + βγ + γα = q\n• αβγ = -r\n\n우리 방정식에서는 최고차 계수가 1이므로:\n• <span class='highlight'>a + b + c = 5</span>\n• <span class='highlight'>ab + bc + ca = 2</span>\n• <span class='highlight'>abc = -33 + (a² + b² + c²)</span>"
                        },
                        {
                            "question": "🔍 이제 abc를 구하기 위해 (a² + b² + c²)의 값을 찾아야 합니다. 어떻게 구할까요?",
                            "answer": "항등식을 이용합니다!\n\n(a + b + c)² = a² + b² + c² + 2(ab + bc + ca)\n\n알고 있는 값을 대입하면:\n5² = (a² + b² + c²) + 2 × 2\n25 = (a² + b² + c²) + 4\n\n따라서 <span class='highlight'>a² + b² + c² = 21</span>"
                        },
                        {
                            "question": "✨ 드디어 마지막 단계! abc의 값은?",
                            "answer": "앞서 구한 관계식에서:\nabc = -33 + (a² + b² + c²)\n\n(a² + b² + c²) = 21을 대입하면:\n\nabc = -33 + 21\n<span class='important'>abc = -12</span>\n\n따라서 답은 <span class='important'>-12</span>입니다!"
                        },
                        {
                            "question": "📝 이 문제의 핵심 포인트를 정리해볼까요?",
                            "answer": "<div class='final-emphasis'>\n<span class='important'>🎓 이 문제에서 꼭 기억해야 할 핵심 아이디어:</span>\n\n1️⃣ <span class='highlight'>공통된 우변을 발견</span>하여 세 변수가 같은 방정식의 근임을 파악\n\n2️⃣ <span class='highlight'>대칭성</span>을 이용하여 문제를 단순화\n\n3️⃣ <span class='highlight'>근과 계수의 관계</span>를 활용하여 미지수들 사이의 관계식 유도\n\n4️⃣ <span class='highlight'>항등식 (a+b+c)² = a²+b²+c² + 2(ab+bc+ca)</span>을 이용한 계산\n\n💡 <span class='important'>가장 중요한 것은 '패턴 인식'입니다!</span>\n세 조건이 동일한 구조를 가진다는 것을 발견하는 순간,\n문제는 훨씬 간단해집니다.\n</div>"
                        }
                    ],
                    "creativeQuestions": {
                        "title": "💭 1등급이 하는 질문들",
                        "questions": [
                            {
                                "text": "만약 조건에서 우변이 <span class='highlight'>a² + b² + c²</span>가 아니라 <span class='highlight'>ab + bc + ca</span>였다면 어떻게 접근해야 할까요?",
                                "hint": "이 경우에도 세 변수가 같은 방정식의 근이 되지만, 계산 과정이 달라집니다."
                            },
                            {
                                "text": "이 문제를 일반화하여 <span class='highlight'>n개의 변수</span>에 대한 문제로 확장할 수 있을까요?",
                                "hint": "n차 방정식의 근과 계수의 관계를 생각해보세요."
                            },
                            {
                                "text": "실제로 a, b, c의 구체적인 값들을 구할 수 있을까요? 그 값들 사이에는 어떤 관계가 있을까요?",
                                "hint": "삼차방정식을 직접 풀어보고, 근들의 대칭성을 관찰해보세요."
                            }
                        ],
                        "footer": "🚀 이런 질문들을 스스로 만들어내는 것이 <span class='important'>수학적 사고력</span>을 기르는 핵심입니다!"
                    },
                    "keyPoints": [
                        "공통된 우변을 발견하여 세 변수가 같은 방정식의 근임을 파악",
                        "대칭성을 이용하여 문제를 단순화",
                        "근과 계수의 관계를 활용하여 미지수들 사이의 관계식 유도",
                        "항등식 (a+b+c)² = a²+b²+c² + 2(ab+bc+ca)을 이용한 계산"
                    ],
                    "similarProblem": {
                        "description": "세 실수 x, y, z가 x³-4x²+x+15 = y³-4y²+y+15 = z³-4z²+z+15 = x²+y²+z²를 만족할 때, xyz의 값을 구하시오.",
                        "options": [
                            { "value": 1, "text": "① 1" },
                            { "value": 0, "text": "② 0" },
                            { "value": -1, "text": "③ -1" },
                            { "value": -2, "text": "④ -2" },
                            { "value": 2, "text": "⑤ 2" }
                        ]
                    },
                    "similarProblemAnswer": -1,
                    "similarProblemSolution": {
                        "steps": [
                            {
                                "title": "1단계: 패턴 발견",
                                "content": "세 식의 우변이 모두 <strong>x² + y² + z²</strong>로 같다는 것을 발견합니다.<br>이는 x, y, z가 같은 방정식의 세 근임을 의미합니다."
                            },
                            {
                                "title": "2단계: 방정식 세우기",
                                "content": "t³ - 4t² + t + 15 = x² + y² + z²<br>이 방정식의 세 근이 x, y, z입니다."
                            },
                            {
                                "title": "3단계: 근과 계수의 관계",
                                "content": "• x + y + z = 4<br>• xy + yz + zx = 1<br>• xyz = -(15 - (x² + y² + z²))"
                            },
                            {
                                "title": "4단계: x² + y² + z² 계산",
                                "content": "(x + y + z)² = x² + y² + z² + 2(xy + yz + zx)<br>16 = x² + y² + z² + 2<br>따라서 <strong>x² + y² + z² = 14</strong>"
                            }
                        ],
                        "finalAnswer": "xyz = -(15 - 14) = -1"
                    }
                },
                {
                    "id": 2,
                    "title": "이차방정식의 근과 계수",
                    "problemInfo": {
                        "description": "이차방정식 x² - 2x + k = 0의 두 근이 α, β일 때, α² + β² = 10을 만족하는 상수 k의 값을 구하여라.",
                        "conditions": []
                    },
                    "analysisQuestions": [
                        {
                            "question": "왜 α² + β²를 직접 계산할 수 없을까?",
                            "answer": "α와 β의 개별 값을 모르기 때문입니다.<br><br>하지만 <span class='highlight'>근과 계수의 관계</span>를 통해 α + β = 2, αβ = k를 알 수 있습니다. 이를 이용하면 α² + β²를 간접적으로 구할 수 있습니다."
                        },
                        {
                            "question": "왜 (α + β)² 공식을 사용할까?",
                            "answer": "α² + β²를 α + β와 αβ로 표현하기 위해서입니다.<br><br><span class='highlight'>(α + β)² = α² + 2αβ + β²</span>이므로, α² + β² = (α + β)² - 2αβ로 변형할 수 있습니다."
                        },
                        {
                            "question": "왜 k = -3이 나올까?",
                            "answer": "주어진 조건 α² + β² = 10을 대입했기 때문입니다.<br><br>10 = 2² - 2k = 4 - 2k에서 2k = -6, 따라서 <span class='highlight'>k = -3</span>입니다."
                        },
                        {
                            "question": "왜 이 방법이 효율적일까?",
                            "answer": "개별 근을 구하지 않고도 답을 얻을 수 있기 때문입니다.<br><br><span class='highlight'>대칭식의 성질</span>을 이용하면, 복잡한 계산 없이 근과 계수의 관계만으로 문제를 해결할 수 있습니다."
                        }
                    ],
                    "highlightTags": [
                        { "text": "x² - 2x + k = 0", "insightNumber": 1 },
                        { "text": "두 근이 α, β", "insightNumber": 2 },
                        { "text": "α² + β² = 10", "insightNumber": 3 },
                        { "text": "상수 k의 값", "insightNumber": 4 }
                    ],
                    "solutionSteps": [
                        {
                            "question": "🤔 이차방정식의 근과 계수의 관계는 무엇일까요?",
                            "answer": "이차방정식 x² - 2x + k = 0에서:\n\n• 두 근의 합: <span class='highlight'>α + β = 2</span>\n• 두 근의 곱: <span class='highlight'>αβ = k</span>\n\n이것이 비에타의 공식입니다!"
                        },
                        {
                            "question": "💡 α² + β²를 어떻게 표현할 수 있을까요?",
                            "answer": "항등식을 이용합니다:\n\n<span class='highlight'>(α + β)² = α² + 2αβ + β²</span>\n\n따라서:\nα² + β² = (α + β)² - 2αβ"
                        },
                        {
                            "question": "🎯 주어진 조건에 대입해볼까요?",
                            "answer": "α² + β² = 10이고,\nα + β = 2, αβ = k이므로:\n\n10 = 2² - 2k\n10 = 4 - 2k\n\n따라서 <span class='highlight'>2k = -6</span>"
                        },
                        {
                            "question": "✨ k의 값은?",
                            "answer": "2k = -6에서:\n\n<span class='important'>k = -3</span>\n\n따라서 상수 k의 값은 <span class='important'>-3</span>입니다!"
                        }
                    ],
                    "creativeQuestions": {
                        "title": "💭 1등급이 하는 질문들",
                        "questions": [
                            {
                                "text": "판별식 D = 4 - 4k를 계산하면 어떤 의미가 있을까요? <span class='highlight'>k = -3</span>일 때 근의 성질은?",
                                "hint": "D > 0이면 서로 다른 두 실근, D = 0이면 중근, D < 0이면 허근입니다."
                            },
                            {
                                "text": "만약 조건이 <span class='highlight'>α³ + β³ = 28</span>이었다면 어떻게 풀어야 할까요?",
                                "hint": "a³ + b³ = (a + b)³ - 3ab(a + b) 공식을 활용해보세요."
                            },
                            {
                                "text": "이차방정식의 두 근 α, β와 계수들 사이의 기하학적 의미는 무엇일까요?",
                                "hint": "포물선 y = x² - 2x + k와 x축의 교점을 생각해보세요."
                            }
                        ],
                        "footer": "🚀 이런 질문들을 스스로 만들어내는 것이 <span class='important'>수학적 사고력</span>을 기르는 핵심입니다!"
                    },
                    "keyPoints": [
                        "이차방정식의 근과 계수의 관계 (비에타의 공식)",
                        "항등식 (α + β)² = α² + 2αβ + β² 활용",
                        "주어진 조건을 이용한 방정식 수립"
                    ],
                    "similarProblem": {
                        "description": "이차방정식 x² - 6x + m = 0의 두 근이 p, q일 때, p² + q² = 20을 만족하는 상수 m의 값을 구하시오.",
                        "options": [
                            { "value": 6, "text": "① 6" },
                            { "value": 7, "text": "② 7" },
                            { "value": 8, "text": "③ 8" },
                            { "value": 9, "text": "④ 9" },
                            { "value": 10, "text": "⑤ 10" }
                        ]
                    },
                    "similarProblemAnswer": 8,
                    "similarProblemSolution": {
                        "steps": [
                            {
                                "title": "1단계: 근과 계수의 관계",
                                "content": "p + q = 6, pq = m"
                            },
                            {
                                "title": "2단계: 항등식 적용",
                                "content": "p² + q² = (p + q)² - 2pq = 36 - 2m"
                            },
                            {
                                "title": "3단계: 조건 대입",
                                "content": "20 = 36 - 2m<br>2m = 16"
                            }
                        ],
                        "finalAnswer": "m = 8"
                    }
                }
            ]
        };
        
        // 20문제로 확장 (더미 데이터 추가)
        for (let i = 3; i <= 20; i++) {
            sampleData.problems.push({
                "id": i,
                "title": `문제 ${i}`,
                "problemInfo": {
                    "description": `이것은 ${i}번째 문제입니다. 주어진 조건을 만족하는 값을 구하시오.`,
                    "conditions": ["조건 1", "조건 2", "조건 3"]
                },
                "analysisQuestions": [
                    {
                        "question": "왜 이 조건들이 주어졌을까?",
                        "answer": "문제 해결에 필요한 핵심 정보들입니다.<br><br><span class='highlight'>조건들 사이의 관계</span>를 파악하는 것이 중요합니다."
                    },
                    {
                        "question": "왜 이런 접근이 필요할까?",
                        "answer": "단계별로 체계적으로 접근해야 하기 때문입니다.<br><br><span class='highlight'>패턴을 찾는 것</span>이 해결의 열쇠입니다."
                    },
                    {
                        "question": "왜 이 방법이 효과적일까?",
                        "answer": "논리적 사고 과정을 통해 답에 도달할 수 있습니다.<br><br><span class='highlight'>수학적 추론</span>의 힘을 보여줍니다."
                    }
                ],
                "highlightTags": [
                    { "text": "주어진 조건", "insightNumber": 1 }
                ],
                "solutionSteps": [
                    {
                        "question": "문제를 어떻게 접근할까요?",
                        "answer": "단계별로 차근차근 접근합니다."
                    },
                    {
                        "question": "핵심 포인트는 무엇인가요?",
                        "answer": "이 문제의 핵심은 조건을 잘 파악하는 것입니다."
                    }
                ],
                "creativeQuestions": {
                    "title": "💭 1등급이 하는 질문들",
                    "questions": [
                        {
                            "text": "이 문제의 조건을 다르게 변형한다면?",
                            "hint": "조건의 변화가 결과에 미치는 영향을 생각해보세요."
                        },
                        {
                            "text": "다른 접근 방법은 없을까요?",
                            "hint": "여러 관점에서 문제를 바라보세요."
                        },
                        {
                            "text": "이 문제의 일반화는 가능할까요?",
                            "hint": "특수한 경우에서 일반적인 경우로 확장해보세요."
                        }
                    ],
                    "footer": "🚀 이런 질문들을 스스로 만들어내는 것이 <span class='important'>수학적 사고력</span>을 기르는 핵심입니다!"
                },
                "keyPoints": ["핵심 포인트 1"],
                "similarProblem": {
                    "description": "유사 문제입니다.",
                    "options": [
                        { "value": 1, "text": "① 1" },
                        { "value": 2, "text": "② 2" },
                        { "value": 3, "text": "③ 3" },
                        { "value": 4, "text": "④ 4" },
                        { "value": 5, "text": "⑤ 5" }
                    ]
                },
                "similarProblemAnswer": 3,
                "similarProblemSolution": {
                    "steps": [{ "title": "풀이", "content": "풀이 과정" }],
                    "finalAnswer": "정답: 3"
                }
            });
        }
        
        // 문제 데이터 로드
        function loadProblemsData() {
            // 실제로는 fetch API로 JSON 파일을 로드
            // fetch('problems.json').then(response => response.json()).then(data => {...});
            
            // 여기서는 샘플 데이터 사용
            problemsData = sampleData;
            problemsCompleted = new Array(problemsData.problems.length).fill(false);
            
            // 초기화
            initializeProblemNav();
            loadProblem(0);
            updateProgress();
        }
        
        // 문제 네비게이션 초기화
        function initializeProblemNav() {
            const nav = document.getElementById('problemNav');
            nav.innerHTML = '';
            
            for (let i = 0; i < Math.min(20, problemsData.problems.length); i++) {
                const button = document.createElement('button');
                button.className = 'nav-button';
                button.textContent = i + 1;
                button.onclick = () => {
                    if (i <= currentProblemIndex || problemsCompleted[i-1]) {
                        loadProblem(i);
                    }
                };
                nav.appendChild(button);
            }
        }
        
        // 문제 네비게이션 업데이트
        function updateProblemNav() {
            const buttons = document.querySelectorAll('.nav-button');
            buttons.forEach((button, index) => {
                button.classList.remove('current', 'complete');
                if (index === currentProblemIndex) {
                    button.classList.add('current');
                } else if (problemsCompleted[index]) {
                    button.classList.add('complete');
                }
                
                // 이전 문제가 완료되었거나 현재/이전 문제인 경우만 활성화
                button.disabled = !(index <= currentProblemIndex || (index > 0 && problemsCompleted[index-1]));
            });
        }
        
        // 진행률 업데이트
        function updateProgress() {
            const progressFill = document.getElementById('progressFill');
            const currentProblemSpan = document.getElementById('currentProblem');
            const totalProblemsSpan = document.getElementById('totalProblems');
            
            const totalProblems = Math.min(20, problemsData.problems.length);
            const progress = ((currentProblemIndex + 1) / totalProblems) * 100;
            
            progressFill.style.width = progress + '%';
            currentProblemSpan.textContent = currentProblemIndex + 1;
            totalProblemsSpan.textContent = totalProblems;
        }
        
        // 점수 업데이트
        function updateScore() {
            document.getElementById('totalScore').textContent = totalScore;
        }
        
        // 문제 로드
        function loadProblem(index) {
            if (index >= problemsData.problems.length) {
                alert('모든 문제를 완료했습니다! 총 점수: ' + totalScore + '점');
                return;
            }
            
            // 초기화
            resetProblemState();
            
            currentProblemIndex = index;
            currentProblemData = problemsData.problems[index];
            
            // 문제 정보 업데이트
            updateProblemDisplay();
            
            // 진행 상황 업데이트
            updateProgress();
            updateProblemNav();
            
            // 질문 가능한 요소들 추가 (약간의 지연)
            setTimeout(addQuestionableElements, 100);
        }
        
        // 문제 표시 업데이트
        function updateProblemDisplay() {
            // 제목
            document.getElementById('problemTitle').textContent = currentProblemData.title;
            
            // 설명 (하이라이트 태그 적용)
            let description = currentProblemData.problemInfo.description;
            if (currentProblemData.highlightTags) {
                currentProblemData.highlightTags.forEach(tag => {
                    const regex = new RegExp(escapeRegExp(tag.text), 'g');
                    description = description.replace(regex, `<span class="highlight-mark" data-insight="${tag.insightNumber}">${tag.text}</span>`);
                });
            }
            document.getElementById('problemDescription').innerHTML = description;
            
            // 조건들
            const conditionsArea = document.getElementById('conditionsArea');
            conditionsArea.innerHTML = '';
            
            currentProblemData.problemInfo.conditions.forEach(condition => {
                const div = document.createElement('div');
                div.className = 'equation';
                
                let conditionHtml = condition;
                // 조건에도 하이라이트 태그 적용
                if (currentProblemData.highlightTags) {
                    currentProblemData.highlightTags.forEach(tag => {
                        const regex = new RegExp(escapeRegExp(tag.text), 'g');
                        conditionHtml = conditionHtml.replace(regex, `<span class="highlight-mark" data-insight="${tag.insightNumber}">${tag.text}</span>`);
                    });
                }
                
                div.innerHTML = conditionHtml;
                conditionsArea.appendChild(div);
            });
        }
        
        // 문제 상태 초기화
        function resetProblemState() {
            currentStep = -1;
            isTyping = false;
            insightStep = 0;
            isInsightActive = false;
            
            // Blur 효과 제거
            removeBlurEffect();
            
            // UI 초기화
            document.getElementById('explanationArea').innerHTML = '';
            document.getElementById('nextButton').innerHTML = '<span class="arrow-down"></span>';
            document.getElementById('nextButton').disabled = false;
            document.getElementById('nextButton').style.display = 'block';
            
            // 자세히 버튼 상태 초기화
            const allDetailLinks = document.querySelectorAll('.detail-link');
            allDetailLinks.forEach(link => {
                link.classList.remove('selected', 'disabled');
                link.innerHTML = '자세히';
                link.style.pointerEvents = 'auto';
            });
            
            // 인사이트 초기화
            const insightButton = document.getElementById('insightButton');
            if (insightButton) {
                insightButton.textContent = '1등급 분석 시작';
                insightButton.disabled = false;
                insightButton.onclick = showInsight;
            }
            document.getElementById('insightList').innerHTML = '';
            
            // 좌측 창의적 질문 섹션은 숨김 처리 (사용하지 않음)
            const creativeSection = document.getElementById('creativeSection');
            if (creativeSection) {
                creativeSection.style.display = 'none';
            }
            
            // 화이트보드 초기화
            document.body.classList.remove('evaluation-mode');
            const whiteboardContainer = document.getElementById('whiteboardContainer');
            if (whiteboardContainer) {
                whiteboardContainer.classList.remove('active');
            }
            
            // 다음 문제 버튼 숨기기
            const nextProblemButton = document.getElementById('nextProblemButton');
            if (nextProblemButton) {
                nextProblemButton.classList.remove('active');
            }
        }
        
        // 정규식 이스케이프
        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        // 유사문제 해설 업데이트
        function updateSimilarProblemSolution() {
            const solutionContent = document.getElementById('solutionContent');
            solutionContent.innerHTML = '';
            
            currentProblemData.similarProblemSolution.steps.forEach(step => {
                const stepDiv = document.createElement('div');
                stepDiv.className = 'solution-step';
                
                const titleDiv = document.createElement('div');
                titleDiv.className = 'solution-step-title';
                titleDiv.textContent = step.title;
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'solution-step-content';
                contentDiv.innerHTML = step.content;
                
                stepDiv.appendChild(titleDiv);
                stepDiv.appendChild(contentDiv);
                solutionContent.appendChild(stepDiv);
            });
            
            const answerBox = document.createElement('div');
            answerBox.className = 'solution-answer-box';
            answerBox.textContent = currentProblemData.similarProblemSolution.finalAnswer;
            solutionContent.appendChild(answerBox);
        }
        
        // 다음 문제 버튼 표시
        function showNextProblemButton() {
            const button = document.getElementById('nextProblemButton');
            if (button && currentProblemIndex < problemsData.problems.length - 1) {
                button.classList.add('active');
            }
        }
        
        // 다음 문제로 이동
        function nextProblem() {
            closeSolution();
            closeResultPopup();
            
            // 화이트보드 모드 해제
            document.body.classList.remove('evaluation-mode');
            document.getElementById('whiteboardContainer').classList.remove('active');
            
            // 다음 문제 버튼 숨기기
            document.getElementById('nextProblemButton').classList.remove('active');
            
            // 다음 문제 로드
            loadProblem(currentProblemIndex + 1);
        }
        
        window.nextProblem = nextProblem;
        
        // 인사이트 관련 함수들
        function showInsight() {
            try {
                const button = document.getElementById('insightButton');
                const insightList = document.getElementById('insightList');
                
                if (!button || !insightList) {
                    console.error('Required elements not found');
                    return;
                }
                
                if (isInsightActive) {
                    return;
                }
                
                isInsightActive = true;
                button.textContent = '분석 중...';
                button.disabled = true;
                
                // 양쪽에 Blur 효과 적용
                applyBothSidesBlurEffect();
                
                insightStep = 0;
                insightList.innerHTML = '<div style="color: #999; font-size: 13px; text-align: center; padding: 20px;">1등급의 시선으로 분석 중<span style="display: inline-block; width: 20px; text-align: left;" class="loading-dots"></span></div>';
                
                // 우측 해설 영역 준비
                const explanationArea = document.getElementById('explanationArea');
                explanationArea.innerHTML = '<div style="color: #999; font-size: 16px; text-align: center; padding: 40px; border: 2px dashed #ddd; border-radius: 10px; margin: 20px 0;">질문에 대한 답변이 여기에 표시됩니다</div>';
                
                // 순차적으로 질문과 답변 표시
                setTimeout(() => {
                    showQuestionsAndAnswers();
                }, 1000);
                
            } catch (error) {
                console.error('Error in showInsight:', error);
                removeBlurEffect();
            }
        }
        
        function applyBothSidesBlurEffect() {
            // 문제 영역만 blur 적용 (인사이트 영역은 제외)
            const elementsToBlur = [
                document.querySelector('.progress-bar-container'),
                document.querySelector('.problem-nav'),
                document.querySelector('.problem-section'),
                document.querySelector('.right-column')
            ];
            
            elementsToBlur.forEach(element => {
                if (element && element.classList) {
                    element.classList.add('blur-background');
                }
            });
            
            // 생성 중 표시기를 좌측으로
            const indicator = document.getElementById('generatingIndicator');
            if (indicator) {
                indicator.style.left = '20px';
                indicator.style.right = 'auto';
                indicator.style.transform = 'none';
                indicator.classList.add('active');
            }
        }
        
        function showQuestionsAndAnswers() {
            const insightList = document.getElementById('insightList');
            const explanationArea = document.getElementById('explanationArea');
            let questionIndex = 0;
            
            insightList.innerHTML = '';
            // 우측은 빈 상태로 시작
            explanationArea.innerHTML = '';
            
            function addNextQuestion() {
                if (questionIndex >= currentProblemData.analysisQuestions.length) {
                    // 모든 질문 완료
                    setTimeout(() => {
                        const insightButton = document.getElementById('insightButton');
                        if (insightButton) {
                            insightButton.textContent = '분석 완료';
                            insightButton.disabled = false;
                        }
                        removeBlurEffect();
                    }, 1000);
                    return;
                }
                
                const questionData = currentProblemData.analysisQuestions[questionIndex];
                
                // 질문 아이템 생성
                const questionItem = document.createElement('div');
                questionItem.className = 'insight-item';
                questionItem.style.animationDelay = '0.1s';
                questionItem.dataset.questionIndex = questionIndex;
                
                // 질문 영역
                const questionDiv = document.createElement('div');
                questionDiv.className = 'insight-question';
                
                const numberSpan = document.createElement('span');
                numberSpan.className = 'insight-number question';
                numberSpan.textContent = 'Q' + (questionIndex + 1);
                
                const textSpan = document.createElement('span');
                textSpan.className = 'insight-text';
                
                const explainButton = document.createElement('button');
                explainButton.className = 'explain-button';
                explainButton.textContent = '(설명)';
                explainButton.dataset.questionIndex = questionIndex;
                
                questionDiv.appendChild(numberSpan);
                questionDiv.appendChild(textSpan);
                questionDiv.appendChild(explainButton);
                
                // 답변 영역 (초기에는 숨김)
                const answerDiv = document.createElement('div');
                answerDiv.className = 'insight-answer';
                answerDiv.dataset.questionIndex = questionIndex;
                
                const answerContent = document.createElement('div');
                answerContent.className = 'insight-answer-content';
                answerDiv.appendChild(answerContent);
                
                questionItem.appendChild(questionDiv);
                questionItem.appendChild(answerDiv);
                insightList.appendChild(questionItem);
                
                // 설명 버튼 클릭 이벤트
                explainButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleAnswer(questionIndex, questionData.answer, this);
                });
                
                // 질문 타이핑
                typeWriterSimple(textSpan, questionData.question, () => {
                    questionIndex++;
                    setTimeout(addNextQuestion, 800);
                });
            }
            
            addNextQuestion();
        }
        
        function toggleAnswer(questionIndex, answerText, button) {
            const allAnswers = document.querySelectorAll('.insight-answer');
            const allButtons = document.querySelectorAll('.explain-button');
            const currentAnswer = document.querySelector(`.insight-answer[data-question-index="${questionIndex}"]`);
            const currentButton = button;
            const explanationArea = document.getElementById('explanationArea');
            
            // 모든 다른 답변 숨기기 및 버튼 비활성화
            allAnswers.forEach((answer, index) => {
                if (index !== questionIndex) {
                    answer.classList.remove('active');
                }
            });
            
            allButtons.forEach((btn, index) => {
                if (index !== questionIndex) {
                    btn.disabled = true;
                    btn.classList.remove('active');
                }
            });
            
            // 현재 답변 토글
            if (currentAnswer.classList.contains('active')) {
                // 이미 활성화된 경우 - 숨기기
                currentAnswer.classList.remove('active');
                currentButton.textContent = '(설명)';
                currentButton.classList.remove('active');
                
                // 우측 칼럼 정리 및 blur 해제
                explanationArea.innerHTML = '';
                removeRightColumnBlur();
                
                // 모든 버튼 다시 활성화
                allButtons.forEach(btn => {
                    btn.disabled = false;
                });
            } else {
                // 새로 활성화
                currentButton.disabled = true;
                currentButton.textContent = '(숨기기)';
                currentButton.classList.add('active');
                
                // 좌측 답변도 표시
                const answerContent = currentAnswer.querySelector('.insight-answer-content');
                if (!answerContent.innerHTML) {
                    currentAnswer.classList.add('active');
                    setTimeout(() => {
                        typeWriter(answerContent, answerText, () => {
                            currentButton.disabled = false;
                            setTimeout(addQuestionableElements, 100);
                        });
                    }, 200);
                } else {
                    currentAnswer.classList.add('active');
                    currentButton.disabled = false;
                }
                
                // 우측 칼럼에 상세 답변 표시
                showDetailedAnswerInRightColumn(questionIndex + 1, answerText);
            }
        }
        
        function showDetailedAnswerInRightColumn(questionNum, answerText) {
            const explanationArea = document.getElementById('explanationArea');
            
            // 기존 내용이 있으면 blur 처리
            applyRightColumnBlur();
            
            // 새로운 답변 영역 생성
            const answerDiv = document.createElement('div');
            answerDiv.className = 'explanation-step active-answer';
            answerDiv.id = 'currentActiveAnswer';
            
            const headerDiv = document.createElement('div');
            headerDiv.className = 'question';
            headerDiv.innerHTML = `🎯 Q${questionNum} 상세 답변`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'answer';
            
            answerDiv.appendChild(headerDiv);
            answerDiv.appendChild(contentDiv);
            explanationArea.appendChild(answerDiv);
            
            // 자동 스크롤 (새로운 내용으로)
            setTimeout(() => {
                const solutionContainer = document.getElementById('solutionContainer');
                const activeAnswer = document.getElementById('currentActiveAnswer');
                
                if (solutionContainer && activeAnswer) {
                    const containerRect = solutionContainer.getBoundingClientRect();
                    const answerRect = activeAnswer.getBoundingClientRect();
                    
                    // 답변이 화면 아래쪽에 있으면 스크롤
                    if (answerRect.bottom > containerRect.bottom) {
                        activeAnswer.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start',
                            inline: 'nearest'
                        });
                    }
                }
            }, 100);
            
            // 답변 타이핑
            typeWriter(contentDiv, answerText, () => {
                // 타이핑 완료 후 스크롤 업데이트
                setTimeout(() => {
                    const solutionContainer = document.getElementById('solutionContainer');
                    const activeAnswer = document.getElementById('currentActiveAnswer');
                    
                    if (solutionContainer && activeAnswer) {
                        // 내용이 컨테이너 하단에 닿으면 자동 스크롤
                        const containerHeight = solutionContainer.clientHeight;
                        const scrollHeight = solutionContainer.scrollHeight;
                        const scrollTop = solutionContainer.scrollTop;
                        
                        if (scrollHeight > containerHeight && 
                            (scrollTop + containerHeight + 50) >= scrollHeight) {
                            solutionContainer.scrollTop = scrollHeight;
                        }
                    }
                }, 100);
                
                // 질문 가능한 요소 추가
                setTimeout(addQuestionableElements, 100);
            });
        }
        
        function toggleStepBlur(event) {
            // 클릭한 요소의 blur 토글
            const step = event.currentTarget;
            if (step.classList.contains('blur-background')) {
                step.classList.remove('blur-background');
                step.classList.add('unblurred-step');
            } else {
                step.classList.add('blur-background');
                step.classList.remove('unblurred-step');
            }
            
            // 이벤트 버블링 방지
            event.stopPropagation();
        }
        
        function applyRightColumnBlur() {
            // 우측 칼럼의 기존 내용에 blur 적용
            const explanationArea = document.getElementById('explanationArea');
            const existingSteps = explanationArea.querySelectorAll('.explanation-step:not(.active-answer)');
            
            existingSteps.forEach(step => {
                step.classList.add('blur-background', 'clickable');
                
                // 클릭 이벤트 추가 (중복 방지를 위해 기존 이벤트 제거)
                step.removeEventListener('click', toggleStepBlur);
                step.addEventListener('click', toggleStepBlur);
            });
        }
        
        function removeRightColumnBlur() {
            // 우측 칼럼의 blur 해제
            const explanationArea = document.getElementById('explanationArea');
            const blurredSteps = explanationArea.querySelectorAll('.explanation-step.blur-background, .explanation-step.unblurred-step');
            
            blurredSteps.forEach(step => {
                step.classList.remove('blur-background', 'clickable', 'unblurred-step');
                step.removeEventListener('click', toggleStepBlur);
            });
            
            // 활성 답변 제거
            const activeAnswer = document.getElementById('currentActiveAnswer');
            if (activeAnswer) {
                activeAnswer.remove();
            }
        }
        
        function typeWriterSimple(element, text, callback) {
            let i = 0;
            element.innerHTML = '';
            if (element.classList) {
                element.classList.add('typing');
            }
            
            // HTML 태그를 처리하기 위한 임시 div
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = text;
            const nodes = Array.from(tempDiv.childNodes);
            
            function typeNode(nodeIndex) {
                if (nodeIndex >= nodes.length) {
                    if (element.classList) {
                        element.classList.remove('typing');
                    }
                    if (callback) callback();
                    return;
                }
                
                const node = nodes[nodeIndex];
                
                if (node.nodeType === Node.TEXT_NODE) {
                    // 텍스트 노드인 경우
                    const text = node.textContent;
                    let charIndex = 0;
                    const textSpan = document.createElement('span');
                    element.appendChild(textSpan);
                    
                    function typeChar() {
                        if (charIndex < text.length) {
                            textSpan.textContent += text[charIndex];
                            charIndex++;
                            
                            // 자동 스크롤 체크
                            autoScrollIfNeeded(element);
                            
                            setTimeout(typeChar, 15);
                        } else {
                            typeNode(nodeIndex + 1);
                        }
                    }
                    typeChar();
                } else {
                    // 엘리먼트 노드인 경우 (예: <span class='highlight'>)
                    const clonedNode = node.cloneNode(true);
                    element.appendChild(clonedNode);
                    setTimeout(() => typeNode(nodeIndex + 1), 50);
                }
            }
            
            if (nodes.length > 0) {
                typeNode(0);
            } else {
                // 단순 텍스트인 경우
                let charIndex = 0;
                function type() {
                    if (charIndex < text.length) {
                        element.innerHTML += text.charAt(charIndex);
                        charIndex++;
                        
                        // 자동 스크롤 체크
                        autoScrollIfNeeded(element);
                        
                        setTimeout(type, 15);
                    } else {
                        if (element.classList) {
                            element.classList.remove('typing');
                        }
                        if (callback) callback();
                    }
                }
                type();
            }
        }
        
        // 자동 스크롤 함수 (하단에 3줄 이상 여백 항상 유지)
        function autoScrollIfNeeded(element) {
            // 스크롤 컨테이너 찾기 (solution-container)
            const solutionContainer = document.getElementById('solutionContainer');
            if (!solutionContainer) return;
            
            const rect = element.getBoundingClientRect();
            const containerRect = solutionContainer.getBoundingClientRect();
            const lineHeight = parseInt(window.getComputedStyle(element).lineHeight) || 24;
            const minMarginBottom = lineHeight * 3.5; // 3.5줄 여백 (여유있게)
            
            // 요소가 컨테이너 내에서의 위치 계산
            const elementBottomInContainer = rect.bottom - containerRect.top;
            const containerVisibleHeight = containerRect.height;
            const containerBottomWithMargin = containerVisibleHeight - minMarginBottom;
            
            // 요소가 컨테이너의 여백 영역에 들어가면 스크롤
            if (elementBottomInContainer > containerBottomWithMargin) {
                // 현재 스크롤 위치에서 추가로 스크롤할 거리 계산
                const additionalScroll = elementBottomInContainer - containerBottomWithMargin;
                const newScrollTop = solutionContainer.scrollTop + additionalScroll;
                
                // 부드럽게 스크롤
                solutionContainer.scrollTo({
                    top: newScrollTop,
                    behavior: 'smooth'
                });
            }
        }
        
        function typeWriter(element, text, callback) {
            isTyping = true;
            let i = 0;
            element.innerHTML = '';
            if (element.classList) {
                element.classList.add('typing');
            }
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = text;
            const plainText = tempDiv.textContent;
            
            function type() {
                if (i < text.length) {
                    if (text[i] === '<') {
                        const tagEnd = text.indexOf('>', i);
                        const tag = text.substring(i, tagEnd + 1);
                        element.innerHTML = text.substring(0, tagEnd + 1);
                        i = tagEnd + 1;
                    } else {
                        element.innerHTML = text.substring(0, i + 1);
                        i++;
                    }
                    
                    // 자동 스크롤 체크
                    autoScrollIfNeeded(element);
                    
                    setTimeout(type, 30);
                } else {
                    if (element.classList) {
                        element.classList.remove('typing');
                    }
                    isTyping = false;
                    if (callback) callback();
                    
                    // 타이핑이 끝나면 질문 가능한 요소 추가
                    setTimeout(addQuestionableElements, 100);
                }
            }
            type();
        }
        
        // 우측 해설 영역에서 창의적 질문 생성하는 함수
        function generateCreativeQuestionsInSolution(containerElement, questionsData) {
            // 초기 제목 표시
            const titleDiv = document.createElement('div');
            titleDiv.className = 'question';
            titleDiv.innerHTML = '🧠 이제 더 깊이 생각해볼까요?';
            containerElement.appendChild(titleDiv);
            
            // 답변 영역 생성
            const answerDiv = document.createElement('div');
            answerDiv.className = 'answer';
            containerElement.appendChild(answerDiv);
            
            // 스크롤을 새로운 영역으로 이동
            setTimeout(() => {
                autoScrollIfNeeded(containerElement);
            }, 100);
            
            // 타이핑 효과로 설명 시작
            setTimeout(() => {
                typeWriter(answerDiv, `<div class="creative-questions-solution">
                    <div class="creative-title-solution">
                        <span>🎯</span>
                        <span>${questionsData.title}</span>
                    </div>
                    <div class="creative-loading-solution">
                        1등급 학생들은 어떤 질문을 할까요?<span class="dots-animation">...</span>
                    </div>
                </div>`, () => {
                    // 타이핑 완료 후 실제 질문들 생성
                    setTimeout(() => {
                        generateQuestionsSequentially(answerDiv, questionsData);
                    }, 1000);
                });
            }, 2000);
        }
        
        // 순차적으로 질문 생성
        function generateQuestionsSequentially(containerElement, questionsData) {
            const creativeDiv = containerElement.querySelector('.creative-questions-solution');
            
            // 로딩 메시지 제거하고 질문들 추가
            const loadingDiv = creativeDiv.querySelector('.creative-loading-solution');
            loadingDiv.remove();
            
            let questionIndex = 0;
            
            function addNextQuestionInSolution() {
                if (questionIndex >= questionsData.questions.length) {
                    // 모든 질문 생성 완료 후 footer 추가
                    setTimeout(() => {
                        const footerDiv = document.createElement('div');
                        footerDiv.className = 'creative-footer-solution';
                        footerDiv.innerHTML = questionsData.footer;
                        creativeDiv.appendChild(footerDiv);
                        
                        // 스크롤 업데이트
                        setTimeout(() => {
                            autoScrollIfNeeded(footerDiv);
                        }, 100);
                        
                        // 창의적 질문 생성 완료 후 모든 blur 효과 제거
                        setTimeout(() => {
                            removeBlurEffect();
                            removeRightColumnBlur();
                        }, 1000);
                    }, 500);
                    return;
                }
                
                const question = questionsData.questions[questionIndex];
                const questionDiv = document.createElement('div');
                questionDiv.className = 'creative-question-solution';
                
                // ChatGPT 링크 생성
                const detailUrl = generateDetailUrl(question);
                
                // 질문 생성
                const qHeaderDiv = document.createElement('div');
                qHeaderDiv.className = 'q-header-solution';
                
                const qNumberDiv = document.createElement('div');
                qNumberDiv.className = 'q-number-solution';
                qNumberDiv.textContent = questionIndex + 1;
                
                const qTextDiv = document.createElement('div');
                qTextDiv.className = 'q-text-solution';
                qTextDiv.innerHTML = question.text;
                
                qHeaderDiv.appendChild(qNumberDiv);
                qHeaderDiv.appendChild(qTextDiv);
                
                const qHintDiv = document.createElement('div');
                qHintDiv.className = 'q-hint-solution';
                
                const hintTextSpan = document.createElement('span');
                hintTextSpan.className = 'hint-text';
                hintTextSpan.textContent = `💡 ${question.hint}`;
                
                const detailLink = document.createElement('a');
                detailLink.href = detailUrl;
                detailLink.target = '_blank';
                detailLink.className = 'detail-link';
                detailLink.title = 'ChatGPT에서 자세한 답변 받기';
                detailLink.textContent = '자세히';
                
                // 클릭 이벤트 추가
                detailLink.addEventListener('click', function(e) {
                    // 이미 비활성화된 버튼이면 클릭 방지
                    if (this.classList.contains('disabled')) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // 현재 버튼을 선택됨 상태로 변경
                    this.classList.add('selected');
                    this.innerHTML = '✓ 선택됨';
                    
                    // 다른 모든 자세히 버튼 비활성화
                    disableOtherDetailLinks(this);
                    
                    // 약간의 지연 후 링크 열기 (시각적 피드백을 위해)
                    setTimeout(() => {
                        window.open(detailUrl, '_blank');
                    }, 200);
                    
                    // 기본 링크 동작 방지 (우리가 직접 처리)
                    e.preventDefault();
                });
                
                qHintDiv.appendChild(hintTextSpan);
                qHintDiv.appendChild(detailLink);
                
                questionDiv.appendChild(qHeaderDiv);
                questionDiv.appendChild(qHintDiv);
                
                creativeDiv.appendChild(questionDiv);
                
                // 애니메이션 효과
                questionDiv.style.opacity = '0';
                questionDiv.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    questionDiv.style.transition = 'all 0.3s ease-out';
                    questionDiv.style.opacity = '1';
                    questionDiv.style.transform = 'translateY(0)';
                    
                    // 스크롤 업데이트
                    setTimeout(() => {
                        autoScrollIfNeeded(questionDiv);
                    }, 100);
                    
                    // 다음 질문으로
                    questionIndex++;
                    setTimeout(addNextQuestionInSolution, 600);
                }, 100);
            }
            
            // 첫 질문 시작
            addNextQuestionInSolution();
        }
        
        // 다른 자세히 버튼들 비활성화
        function disableOtherDetailLinks(selectedLink) {
            // 현재 생성된 모든 자세히 버튼 찾기
            const allDetailLinks = document.querySelectorAll('.detail-link');
            
            allDetailLinks.forEach(link => {
                if (link !== selectedLink) {
                    link.classList.add('disabled');
                    link.innerHTML = '✗ 사용불가';
                    link.removeAttribute('href');
                    link.removeAttribute('target');
                    link.title = '이미 다른 질문을 선택했습니다';
                    
                    // 클릭 이벤트 방지
                    link.style.pointerEvents = 'none';
                }
            });
        }
        
        // ChatGPT 상세 링크 생성
        function generateDetailUrl(question) {
            try {
                const problemInfo = currentProblemData.problemInfo;
                const solutionSteps = currentProblemData.solutionSteps;
                
                // 문제 정보 요약
                let problemSummary = `문제: ${problemInfo.description}`;
                if (problemInfo.conditions && problemInfo.conditions.length > 0) {
                    problemSummary += `\n조건: ${problemInfo.conditions.join(', ')}`;
                }
                
                // 해설 요약 (주요 단계만)
                let solutionSummary = "\n주요 해설 과정:";
                solutionSteps.slice(0, 3).forEach((step, index) => {
                    // HTML 태그 제거
                    const cleanQuestion = step.question.replace(/<[^>]*>/g, '');
                    solutionSummary += `\n${index + 1}. ${cleanQuestion}`;
                });
                
                // 질문 내용 (HTML 태그 제거)
                const questionText = question.text.replace(/<[^>]*>/g, '');
                
                // URL 파라미터 생성
                const queryText = `${problemSummary}${solutionSummary}\n\n이 문제를 보고 다음과 같은 궁금증이 생깁니다. 응답을 구합니다:\n${questionText}\n\n문제풀이는 이미 이해했어. 문제 설명은 최소화하고 질문에 대한 직접적인 답변만 생성해줘`;
                
                // URL 인코딩
                const encodedQuery = encodeURIComponent(queryText);
                
                return `https://chatgpt.com/?model=o3&q=${encodedQuery}`;
            } catch (error) {
                console.error('URL 생성 오류:', error);
                // 기본 URL 반환
                return `https://chatgpt.com/?model=o3&q=${encodeURIComponent('수학 문제에 대한 질문: ' + question.text.replace(/<[^>]*>/g, '') + '\n\n문제풀이는 이미 이해했어. 문제 설명은 최소화하고 질문에 대한 직접적인 답변만 생성해줘')}`;
            }
        }
        function generateCreativeQuestions(containerElement, questionsData) {
            // 초기 로딩 상태
            containerElement.innerHTML = `
                <div class="creative-loading">
                    <span class="creative-title">${questionsData.title}</span>
                    <div class="thinking-dots">
                        <span>AI가 창의적 질문을 생성하고 있습니다</span>
                        <span class="dots-animation">...</span>
                    </div>
                </div>
            `;
            
            containerElement.style.opacity = '1';
            containerElement.style.animation = 'fadeInUp 0.6s ease-out forwards';
            
            let questionIndex = 0;
            
            setTimeout(() => {
                // 타이틀만 남기고 질문 생성 시작
                containerElement.innerHTML = `<span class="creative-title">${questionsData.title}</span>`;
                
                function addNextQuestion() {
                    if (questionIndex >= questionsData.questions.length) {
                        // 모든 질문 생성 완료 후 footer 추가
                        setTimeout(() => {
                            const footerDiv = document.createElement('div');
                            footerDiv.className = 'creative-footer';
                            footerDiv.innerHTML = questionsData.footer;
                            footerDiv.style.opacity = '0';
                            footerDiv.style.animation = 'fadeIn 0.5s ease-out forwards';
                            containerElement.appendChild(footerDiv);
                            
                            // 창의적 질문 생성 완료 후 blur 효과 제거
                            setTimeout(() => {
                                removeBlurEffect();
                            }, 1000);
                        }, 500);
                        return;
                    }
                    
                    const question = questionsData.questions[questionIndex];
                    const questionDiv = document.createElement('div');
                    questionDiv.className = 'creative-question';
                    questionDiv.style.opacity = '0';
                    
                    // 임시 로딩 상태
                    questionDiv.innerHTML = `
                        <span class="q-number">Q${questionIndex + 1}.</span>
                        <span class="q-text generating">
                            <span class="generating-text">질문 생성 중</span>
                            <span class="generating-dots">...</span>
                        </span>
                    `;
                    
                    containerElement.appendChild(questionDiv);
                    
                    // 애니메이션 시작
                    setTimeout(() => {
                        questionDiv.style.animation = 'slideInLeft 0.5s ease-out forwards';
                    }, 10);
                    
                    // 실제 질문 텍스트로 교체
                    setTimeout(() => {
                        const qText = questionDiv.querySelector('.q-text');
                        qText.classList.remove('generating');
                        
                        // 타이핑 효과로 질문 표시
                        typeWriterSimple(qText, question.text, () => {
                            // 힌트 추가
                            setTimeout(() => {
                                const hintDiv = document.createElement('div');
                                hintDiv.className = 'q-hint';
                                hintDiv.innerHTML = `💡 힌트: ${question.hint}`;
                                hintDiv.style.opacity = '0';
                                hintDiv.style.animation = 'fadeIn 0.3s ease-out forwards';
                                questionDiv.appendChild(hintDiv);
                                
                                // 다음 질문으로
                                questionIndex++;
                                setTimeout(addNextQuestion, 800);
                            }, 300);
                        });
                    }, 1000);
                }
                
                // 첫 질문 시작
                addNextQuestion();
                
            }, 2000);
        }
        
        function showNextStep() {
            if (isTyping) return;
            
            if (autoNextTimeout) {
                clearTimeout(autoNextTimeout);
                autoNextTimeout = null;
            }
            
            currentStep++;
            
            // 핵심 포인트 정리가 끝나면 창의적 질문 표시
            if (currentStep === currentProblemData.solutionSteps.length) {
                // 우측 해설 영역에서 창의적 질문 생성
                if (currentProblemData.creativeQuestions) {
                    // Blur 효과 적용 (좌측만)
                    applyBlurEffect();
                    
                    // 우측 칼럼의 이전 단계들에 blur 효과 적용
                    applyRightColumnBlur();
                    
                    // 우측 해설 영역에 창의적 질문 추가
                    const explanationArea = document.getElementById('explanationArea');
                    const creativeDiv = document.createElement('div');
                    creativeDiv.className = 'explanation-step';
                    creativeDiv.id = 'creativeQuestionsInSolution';
                    
                    explanationArea.appendChild(creativeDiv);
                    
                    // 창의적 질문 실시간 생성 (우측에서)
                    generateCreativeQuestionsInSolution(creativeDiv, currentProblemData.creativeQuestions);
                    
                    // 다음 버튼 텍스트 변경
                    document.getElementById('nextButton').innerHTML = '서술평가 시작하기';
                }
                return;
            } else if (currentStep > currentProblemData.solutionSteps.length) {
                // 서술평가로 전환
                document.getElementById('nextButton').style.display = 'none';
                
                const transitionMessage = document.getElementById('transitionMessage');
                if (transitionMessage && transitionMessage.classList) {
                    transitionMessage.classList.add('active');
                }
                
                setTimeout(() => {
                    if (transitionMessage && transitionMessage.classList) {
                        transitionMessage.classList.remove('active');
                    }
                    
                    const mainContainer = document.querySelector('.main-container');
                    if (mainContainer) {
                        mainContainer.style.transition = 'opacity 0.5s ease';
                        mainContainer.style.opacity = '0';
                    }
                    
                    setTimeout(() => {
                        if (document.body && document.body.classList) {
                            document.body.classList.add('evaluation-mode');
                        }
                        
                        const whiteboardContainer = document.getElementById('whiteboardContainer');
                        if (whiteboardContainer && whiteboardContainer.classList) {
                            whiteboardContainer.classList.add('active');
                            console.log('화이트보드 컨테이너 활성화됨');
                        } else {
                            console.error('화이트보드 컨테이너를 찾을 수 없습니다');
                        }
                        
                        // 화이트보드 초기화를 약간 지연시켜 DOM이 완전히 준비되도록 함
                        setTimeout(() => {
                            console.log('화이트보드 초기화 시도');
                            initWhiteboard();
                        }, 300);
                    }, 500);
                }, 2000);
                
                return;
            }
            
            // Blur 효과 적용 (생성 중일 때만)
            applyBlurEffect();
            
            // 우측 칼럼의 이전 단계들에 blur 효과 적용
            applyRightColumnBlur();
            
            const step = currentProblemData.solutionSteps[currentStep];
            // 평가 질문 추가 (각 단계마다 설정 가능)
            if (!step.evaluationQuestion) {
                // 기본 평가 질문 설정
                const defaultQuestions = [
                    "이 단계에서 가장 중요한 개념을 설명해 보세요.",
                    "이해한 내용을 설명해 보세요.",
                    "이 단계의 핵심 아이디어를 정리해 보세요.",
                    "왜 이런 방법을 사용했는지 설명해 보세요."
                ];
                step.evaluationQuestion = defaultQuestions[currentStep % defaultQuestions.length];
            }
            const explanationArea = document.getElementById('explanationArea');
            
            const stepDiv = document.createElement('div');
            stepDiv.className = 'explanation-step';
            
            const questionDiv = document.createElement('div');
            questionDiv.className = 'question';
            questionDiv.textContent = step.question;
            
            const answerDiv = document.createElement('div');
            answerDiv.className = 'answer';
            
            stepDiv.appendChild(questionDiv);
            stepDiv.appendChild(answerDiv);
            explanationArea.appendChild(stepDiv);
            
            // 새 요소 추가 후 자동 스크롤
            setTimeout(() => {
                autoScrollIfNeeded(stepDiv);
            }, 100);
            
            document.getElementById('nextButton').disabled = true;
            
            const thinkingSpan = document.createElement('span');
            thinkingSpan.className = 'thinking-indicator';
            thinkingSpan.textContent = '생각 중';
            questionDiv.appendChild(thinkingSpan);
            
            setTimeout(() => {
                thinkingSpan.remove();
                
                typeWriter(answerDiv, step.answer, () => {
                    // 타이핑 완료 후 모든 blur 효과 제거
                    removeBlurEffect();
                    removeRightColumnBlur();
                    
                    // 평가 화이트보드 추가
                    if (step.evaluationQuestion) {
                        const evalDiv = createStepEvaluation(stepDiv, step.evaluationQuestion, currentStep);
                        stepDiv.appendChild(evalDiv);
                        
                        // 평가 영역으로 스크롤
                        setTimeout(() => {
                            autoScrollIfNeeded(evalDiv);
                        }, 300);
                    } else {
                        // 평가가 없으면 다음 버튼 활성화
                        document.getElementById('nextButton').disabled = false;
                    }
                });
            }, 3000);
        }
        
        // 단계별 평가 생성 함수
        function createStepEvaluation(parentElement, question, stepNumber) {
            console.log('=== createStepEvaluation 함수 시작 ===');
            console.log('질문:', question);
            console.log('단계:', stepNumber);
            
            const evalDiv = document.createElement('div');
            evalDiv.className = 'step-evaluation';
            evalDiv.id = `evaluation-${stepNumber}`;
            
            // 평가 질문
            const questionDiv = document.createElement('div');
            questionDiv.className = 'evaluation-question';
            // 질문 텍스트와 아이콘들을 포함하는 컨테이너
            const questionContent = document.createElement('div');
            questionContent.style.display = 'flex';
            questionContent.style.alignItems = 'center';
            questionContent.style.gap = '10px';
            
            const questionText = document.createElement('span');
            questionText.textContent = question || `${stepNumber}단계에 대해 설명해 보세요.`;
            
            
            const micIcon = document.createElement('button');
            micIcon.innerHTML = '🎤';
            micIcon.style.fontSize = '24px';
            micIcon.style.cursor = 'pointer';
            micIcon.style.border = '1px solid #ddd';
            micIcon.style.borderRadius = '5px';
            micIcon.style.padding = '5px 10px';
            micIcon.style.marginLeft = '10px';
            micIcon.style.background = 'white';
            micIcon.title = '음성 녹음';
            
            const whiteboardIcon = document.createElement('button');
            whiteboardIcon.innerHTML = '📋';
            whiteboardIcon.style.fontSize = '24px';
            whiteboardIcon.style.cursor = 'pointer';
            whiteboardIcon.style.border = '1px solid #ddd';
            whiteboardIcon.style.borderRadius = '5px';
            whiteboardIcon.style.padding = '5px 10px';
            whiteboardIcon.style.marginLeft = '5px';
            whiteboardIcon.style.background = 'white';
            whiteboardIcon.title = '화이트보드';
            
            // 녹음 버튼 추가
            const recordButton = document.createElement('button');
            recordButton.innerHTML = '⏺️';
            recordButton.style.fontSize = '24px';
            recordButton.style.cursor = 'pointer';
            recordButton.style.border = '1px solid #ddd';
            recordButton.style.borderRadius = '5px';
            recordButton.style.padding = '5px 10px';
            recordButton.style.marginLeft = '5px';
            recordButton.style.background = 'white';
            recordButton.title = '녹음 시작/중지';
            recordButton.style.display = 'none'; // 초기에는 숨김
            
            questionContent.appendChild(questionText);
            questionContent.appendChild(micIcon);
            questionContent.appendChild(whiteboardIcon);
            questionContent.appendChild(recordButton);
            questionDiv.appendChild(questionContent);
            evalDiv.appendChild(questionDiv);
            
            
            // 디버깅을 위한 즉시 테스트
            console.log('마이크 아이콘:', micIcon);
            console.log('화이트보드 아이콘:', whiteboardIcon);
            console.log('마이크 onclick:', micIcon.onclick);
            console.log('화이트보드 onclick:', whiteboardIcon.onclick);
            
            // 요소가 DOM에 추가된 후 이벤트 리스너 동작 확인
            setTimeout(() => {
                console.log('아이콘들이 DOM에 존재하는지 확인:');
                console.log('마이크 아이콘 DOM 존재:', document.contains(micIcon));
                console.log('화이트보드 아이콘 DOM 존재:', document.contains(whiteboardIcon));
            }, 100);
            
            // 상태 변수들
            let isRecording = false;
            let isWhiteboardMode = false;
            let mediaRecorder = null;
            let audioChunks = [];
            let timerInterval = null;
            let totalTime = 30;
            let remainingTime = 30;
            let addedTimes = 0;
            
            // 미니 화이트보드 컨테이너 (초기에는 숨김)
            const whiteboardContainer = document.createElement('div');
            whiteboardContainer.className = 'whiteboard-container';
            whiteboardContainer.style.display = 'none';
            whiteboardContainer.style.marginTop = '20px';
            whiteboardContainer.style.padding = '15px';
            whiteboardContainer.style.backgroundColor = '#f5f5f5';
            whiteboardContainer.style.borderRadius = '8px';
            
            const whiteboardDiv = document.createElement('div');
            whiteboardDiv.className = 'mini-whiteboard';
            whiteboardDiv.style.backgroundColor = 'white';
            whiteboardDiv.style.border = '1px solid #ddd';
            whiteboardDiv.style.borderRadius = '8px';
            whiteboardDiv.style.height = '300px';
            whiteboardDiv.style.position = 'relative';
            whiteboardDiv.style.overflow = 'hidden';
            
            const canvas = document.createElement('canvas');
            canvas.className = 'mini-canvas';
            canvas.width = 600;
            canvas.height = 300;
            canvas.style.position = 'absolute';
            canvas.style.top = '0';
            canvas.style.left = '0';
            canvas.style.cursor = 'crosshair';
            whiteboardDiv.appendChild(canvas);
            whiteboardContainer.appendChild(whiteboardDiv);
            
            // 화이트보드 도구 및 타이머
            const whiteboardToolsDiv = document.createElement('div');
            whiteboardToolsDiv.className = 'whiteboard-tools';
            whiteboardToolsDiv.style.display = 'flex';
            whiteboardToolsDiv.style.alignItems = 'center';
            whiteboardToolsDiv.style.gap = '10px';
            whiteboardToolsDiv.style.marginTop = '10px';
            
            // 펜 도구
            const penBtn = document.createElement('button');
            penBtn.className = 'eval-tool-btn active';
            penBtn.textContent = '✏️';
            penBtn.title = '펜';
            penBtn.style.padding = '5px 10px';
            penBtn.style.marginRight = '5px';
            penBtn.style.cursor = 'pointer';
            penBtn.onclick = function() {
                console.log('펜 도구 선택');
                canvas.dataset.tool = 'pen';
                penBtn.classList.add('active');
                eraserBtn.classList.remove('active');
            };
            whiteboardToolsDiv.appendChild(penBtn);
            
            // 지우개
            const eraserBtn = document.createElement('button');
            eraserBtn.className = 'eval-tool-btn';
            eraserBtn.textContent = '🧽';
            eraserBtn.title = '지우개';
            eraserBtn.style.padding = '5px 10px';
            eraserBtn.style.marginRight = '5px';
            eraserBtn.style.cursor = 'pointer';
            eraserBtn.onclick = function() {
                console.log('지우개 도구 선택');
                canvas.dataset.tool = 'eraser';
                eraserBtn.classList.add('active');
                penBtn.classList.remove('active');
            };
            whiteboardToolsDiv.appendChild(eraserBtn);
            
            // 휴지통 (전체 지우기)
            const clearBtn = document.createElement('button');
            clearBtn.className = 'eval-tool-btn';
            clearBtn.textContent = '🗑️';
            clearBtn.title = '전체 지우기';
            clearBtn.style.padding = '5px 10px';
            clearBtn.style.marginRight = '5px';
            clearBtn.style.cursor = 'pointer';
            clearBtn.onclick = function() {
                console.log('캔버스 전체 지우기');
                clearCanvas(canvas);
            };
            whiteboardToolsDiv.appendChild(clearBtn);
            
            // 타이머 프로그레스 바
            const timerContainer = document.createElement('div');
            timerContainer.style.flex = '1';
            timerContainer.style.marginLeft = '20px';
            timerContainer.style.position = 'relative';
            
            const timerProgress = document.createElement('div');
            timerProgress.className = 'timer-progress';
            timerProgress.style.width = '100%';
            timerProgress.style.height = '20px';
            timerProgress.style.backgroundColor = '#e0e0e0';
            timerProgress.style.borderRadius = '10px';
            timerProgress.style.overflow = 'hidden';
            timerProgress.style.position = 'relative';
            
            const timerBar = document.createElement('div');
            timerBar.className = 'timer-bar';
            timerBar.style.width = '100%';
            timerBar.style.height = '100%';
            timerBar.style.backgroundColor = '#4caf50';
            timerBar.style.transition = 'width 1s linear';
            
            const timerText = document.createElement('div');
            timerText.style.position = 'absolute';
            timerText.style.top = '50%';
            timerText.style.left = '50%';
            timerText.style.transform = 'translate(-50%, -50%)';
            timerText.style.fontSize = '12px';
            timerText.style.fontWeight = 'bold';
            timerText.textContent = '30초';
            
            timerProgress.appendChild(timerBar);
            timerProgress.appendChild(timerText);
            timerContainer.appendChild(timerProgress);
            whiteboardToolsDiv.appendChild(timerContainer);
            
            // 플러스 버튼 (시간 추가)
            const addTimeBtn = document.createElement('button');
            addTimeBtn.className = 'add-time-btn';
            addTimeBtn.style.padding = '5px 10px';
            addTimeBtn.style.fontSize = '20px';
            addTimeBtn.style.cursor = 'pointer';
            addTimeBtn.innerHTML = '+';
            addTimeBtn.title = '30초 추가';
            
            const addCount = document.createElement('span');
            addCount.style.marginLeft = '5px';
            addCount.style.fontSize = '14px';
            addCount.textContent = '(0)';
            addTimeBtn.appendChild(addCount);
            
            whiteboardToolsDiv.appendChild(addTimeBtn);
            
            whiteboardContainer.appendChild(whiteboardToolsDiv);
            evalDiv.appendChild(whiteboardContainer);
            
            // 녹음 시작 함수 (비활성화)
            function startRecording() {
                console.log('녹음 기능이 비활성화되었습니다.');
                // 녹음 기능을 일시적으로 비활성화
                return;
                
                // mediaDevices API 존재 여부 확인
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    console.error('미디어 디바이스 API를 사용할 수 없습니다.');
                    console.error('현재 프로토콜:', location.protocol);
                    console.error('현재 호스트:', location.hostname);
                    alert('이 환경에서는 마이크를 사용할 수 없습니다.\n\nHTTPS 또는 localhost에서 접속해주세요.');
                    micIcon.style.backgroundColor = 'white';
                    isRecording = false;
                    return;
                }
                
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(stream => {
                        console.log('마이크 접근 성공');
                        mediaRecorder = new MediaRecorder(stream);
                        // stream 저장
                        mediaRecorder.streamRef = stream;
                        
                        mediaRecorder.ondataavailable = event => {
                            audioChunks.push(event.data);
                        };
                        mediaRecorder.onstop = () => {
                            const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                            evalDiv.dataset.audioData = URL.createObjectURL(audioBlob);
                            audioChunks = [];
                            // 스트림 정리
                            if (mediaRecorder.streamRef) {
                                mediaRecorder.streamRef.getTracks().forEach(track => track.stop());
                            }
                        };
                        mediaRecorder.start();
                        console.log('녹음 시작됨');
                    })
                    .catch(err => {
                        console.error('마이크 접근 오류:', err);
                        console.error('에러 이름:', err.name);
                        console.error('에러 메시지:', err.message);
                        
                        let errorMsg = '마이크 접근에 실패했습니다.\n\n';
                        if (err.name === 'NotAllowedError') {
                            errorMsg += '마이크 권한을 허용해주세요.';
                        } else if (err.name === 'NotFoundError') {
                            errorMsg += '마이크가 연결되어 있지 않습니다.';
                        } else if (err.name === 'NotReadableError') {
                            errorMsg += '마이크가 다른 프로그램에서 사용 중입니다.';
                        } else if (err.name === 'SecurityError' || err.name === 'TypeError') {
                            errorMsg += 'HTTPS 또는 localhost에서만 사용 가능합니다.';
                        } else {
                            errorMsg += err.message;
                        }
                        
                        alert(errorMsg);
                        micIcon.style.backgroundColor = 'white';
                        isRecording = false;
                    });
            }
            
            // 녹음 중지 함수
            function stopRecording() {
                console.log('녹음 중지 함수 호출됨');
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                    console.log('녹음 중지됨');
                }
            }
            
            // 타이머 시작 함수
            function startTimer() {
                remainingTime = totalTime;
                updateTimerDisplay();
                
                timerInterval = setInterval(() => {
                    remainingTime--;
                    updateTimerDisplay();
                    
                    if (remainingTime <= 0) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                        // 타이머 종료 시 자동으로 완료 처리
                        handleComplete();
                    }
                }, 1000);
            }
            
            // 타이머 표시 업데이트
            function updateTimerDisplay() {
                const percentage = (remainingTime / totalTime) * 100;
                timerBar.style.width = percentage + '%';
                timerText.textContent = remainingTime + '초';
                
                // 시간이 부족할 때 색상 변경
                if (remainingTime <= 10) {
                    timerBar.style.backgroundColor = '#ff5252';
                } else {
                    timerBar.style.backgroundColor = '#4caf50';
                }
            }
            
            // 시간 추가 버튼 클릭
            addTimeBtn.onclick = () => {
                totalTime += 30;
                remainingTime += 30;
                addedTimes++;
                addCount.textContent = `(${addedTimes})`;
                updateTimerDisplay();
            };
            
            // 완료 처리 함수
            function handleComplete() {
                stopRecording();
                if (timerInterval) {
                    clearInterval(timerInterval);
                }
                // 다음 단계로 진행
                document.getElementById('nextButton').disabled = false;
                document.getElementById('nextButton').click();
            }
            
            // 제출 버튼들
            const submitDiv = document.createElement('div');
            submitDiv.className = 'evaluation-submit';
            
            const submitBtn = document.createElement('button');
            submitBtn.className = 'submit-btn primary';
            submitBtn.textContent = '완료';
            submitBtn.onclick = () => {
                // 녹음 중지
                stopRecording();
                if (timerInterval) {
                    clearInterval(timerInterval);
                }
                
                // 피드백 표시
                feedbackDiv.style.display = 'block';
                submitBtn.disabled = true;
                skipBtn.disabled = true;
                
                // 1초 후 다음 단계로 진행
                setTimeout(() => {
                    document.getElementById('nextButton').disabled = false;
                    document.getElementById('nextButton').click();
                }, 1000);
            };
            
            const skipBtn = document.createElement('button');
            skipBtn.className = 'submit-btn secondary';
            skipBtn.textContent = '건너뛰기';
            skipBtn.onclick = () => {
                // 녹음 중지
                stopRecording();
                if (timerInterval) {
                    clearInterval(timerInterval);
                }
                
                // 바로 다음 단계로 진행
                document.getElementById('nextButton').disabled = false;
                document.getElementById('nextButton').click();
            };
            
            submitDiv.appendChild(submitBtn);
            submitDiv.appendChild(skipBtn);
            evalDiv.appendChild(submitDiv);
            
            // 제출 피드백
            const feedbackDiv = document.createElement('div');
            feedbackDiv.className = 'submission-feedback';
            feedbackDiv.textContent = '✅ 선생님에게 전달되었습니다!';
            feedbackDiv.style.display = 'none';
            evalDiv.appendChild(feedbackDiv);
            
            // 캔버스 초기화
            initCanvas(canvas);
            
            // 마이크 아이콘 클릭 이벤트 핸들러
            micIcon.onclick = function(e) {
                e.preventDefault();
                console.log('마이크 아이콘 클릭됨!');
                
                // HTTP/HTTPS 체크
                if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                    alert('음성 녹음은 HTTPS 또는 localhost에서만 사용 가능합니다.\n\n다음 중 하나를 시도해주세요:\n1. https://로 접속\n2. http://localhost로 접속\n3. 화이트보드 기능만 사용');
                    console.error('보안 컨텍스트가 아님:', location.protocol, location.hostname);
                    
                    // 화이트보드는 HTTP에서도 사용 가능하므로 배경색은 변경
                    if (!isRecording) {
                        micIcon.style.backgroundColor = '#ffcccc';
                        isRecording = true;
                    } else {
                        micIcon.style.backgroundColor = 'white';
                        isRecording = false;
                    }
                    return;
                }
                
                if (!isRecording) {
                    startRecording();
                    micIcon.style.backgroundColor = '#ffcccc';
                    isRecording = true;
                } else {
                    stopRecording();
                    micIcon.style.backgroundColor = 'white';
                    isRecording = false;
                }
            };
            
            // 화이트보드 아이콘 클릭 이벤트 핸들러
            whiteboardIcon.onclick = function(e) {
                e.preventDefault();
                console.log('화이트보드 아이콘 클릭됨!');
                console.log('화이트보드 컨테이너:', whiteboardContainer);
                console.log('현재 화이트보드 모드:', isWhiteboardMode);
                console.log('화이트보드 컨테이너 부모:', whiteboardContainer.parentNode);
                
                if (!isWhiteboardMode) {
                    console.log('화이트보드 표시 시도');
                    whiteboardContainer.style.display = 'block';
                    whiteboardContainer.style.border = '2px solid #ddd'; // 테두리
                    whiteboardContainer.style.minHeight = '350px'; // 최소 높이 설정
                    whiteboardContainer.style.position = 'relative'; // 포지션 설정
                    whiteboardIcon.style.backgroundColor = '#ccccff';
                    isWhiteboardMode = true;
                    console.log('화이트보드 display 설정 후:', whiteboardContainer.style.display);
                    console.log('화이트보드 컨테이너 크기:', whiteboardContainer.offsetWidth, 'x', whiteboardContainer.offsetHeight);
                    console.log('evalDiv에 포함되어 있나?:', evalDiv.contains(whiteboardContainer));
                    
                    // 녹음 버튼 표시
                    recordButton.style.display = 'inline-block';
                    
                    // HTTP/HTTPS 체크
                    const isSecureContext = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
                    if (!isSecureContext) {
                        console.warn('HTTP 환경에서는 음성 녹음이 불가능합니다. 화이트보드만 사용 가능합니다.');
                        recordButton.style.display = 'none'; // 보안 컨텍스트가 아니면 녹음 버튼 숨김
                    }
                    
                    // 타이머 시작
                    startTimer();
                    
                    // 캔버스 다시 초기화 (크기 조정)
                    canvas.width = whiteboardDiv.offsetWidth || 600;
                    canvas.height = 300;
                    initCanvas(canvas);
                } else {
                    console.log('화이트보드 숨기기');
                    whiteboardContainer.style.display = 'none';
                    whiteboardIcon.style.backgroundColor = 'white';
                    isWhiteboardMode = false;
                    
                    // 녹음 버튼 숨기기
                    recordButton.style.display = 'none';
                    
                    // 타이머 정지
                    if (timerInterval) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                    }
                    
                    // 녹음 중이면 중지
                    if (isRecording) {
                        stopRecording();
                        micIcon.style.backgroundColor = 'white';
                        isRecording = false;
                    }
                }
            };
            
            // 녹음 버튼 클릭 핸들러
            recordButton.onclick = function(e) {
                e.preventDefault();
                console.log('녹음 버튼 클릭됨!');
                
                const isSecureContext = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
                
                if (!isSecureContext) {
                    alert('음성 녹음은 HTTPS 또는 localhost에서만 사용 가능합니다.');
                    return;
                }
                
                if (!isRecording) {
                    // 녹음 시작
                    startRecording();
                    recordButton.innerHTML = '⏹️';
                    recordButton.title = '녹음 중지';
                    recordButton.style.backgroundColor = '#ffcccc';
                    micIcon.style.backgroundColor = '#ffcccc';
                    isRecording = true;
                } else {
                    // 녹음 중지
                    stopRecording();
                    recordButton.innerHTML = '⏺️';
                    recordButton.title = '녹음 시작/중지';
                    recordButton.style.backgroundColor = 'white';
                    micIcon.style.backgroundColor = 'white';
                    isRecording = false;
                }
            };
            
            // 애니메이션으로 표시
            setTimeout(() => {
                evalDiv.classList.add('active');
            }, 100);
            
            return evalDiv;
        }
        
        // 미니 캔버스 초기화
        function initMiniCanvas(canvas, evalDiv) {
            const ctx = canvas.getContext('2d');
            let isDrawing = false;
            let currentTool = 'pen';
            
            // 캔버스 크기 조정
            const resizeCanvas = () => {
                const rect = canvas.parentElement.getBoundingClientRect();
                canvas.width = rect.width;
                canvas.height = 70;
            };
            
            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);
            
            // 그리기 이벤트
            const startDrawing = (e) => {
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                ctx.beginPath();
                ctx.moveTo(x, y);
            };
            
            const draw = (e) => {
                if (!isDrawing) return;
                
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                if (evalDiv.dataset.currentTool === 'eraser') {
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.lineWidth = 20;
                } else {
                    ctx.globalCompositeOperation = 'source-over';
                    ctx.strokeStyle = '#000';
                    ctx.lineWidth = 2;
                }
                
                ctx.lineTo(x, y);
                ctx.stroke();
            };
            
            const stopDrawing = () => {
                isDrawing = false;
            };
            
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            
            // 터치 지원
            canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousedown', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            });
            
            canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousemove', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            });
            
            canvas.addEventListener('touchend', (e) => {
                e.preventDefault();
                const mouseEvent = new MouseEvent('mouseup', {});
                canvas.dispatchEvent(mouseEvent);
            });
        }
        
        // 도구 선택
        function selectTool(evalDiv, tool) {
            evalDiv.dataset.currentTool = tool;
            const buttons = evalDiv.querySelectorAll('.eval-tool-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            if (tool === 'pen') {
                buttons[0].classList.add('active');
            } else if (tool === 'eraser') {
                buttons[1].classList.add('active');
            }
        }
        
        // 캔버스 지우기
        function clearCanvas(canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
        
        // 캔버스 초기화
        function initCanvas(canvas) {
            console.log('initCanvas 호출됨', canvas);
            const ctx = canvas.getContext('2d');
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000000';
            
            // 캔버스 배경을 흰색으로 설정
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            let isDrawing = false;
            canvas.dataset.tool = 'pen'; // 기본 도구 설정
            
            canvas.addEventListener('mousedown', (e) => {
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                ctx.beginPath();
                ctx.moveTo(x, y);
            });
            
            canvas.addEventListener('mousemove', (e) => {
                if (!isDrawing) return;
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                if (canvas.dataset.tool === 'eraser') {
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.lineWidth = 10;
                } else {
                    ctx.globalCompositeOperation = 'source-over';
                    ctx.lineWidth = 2;
                    ctx.strokeStyle = '#000000';
                }
                
                ctx.lineTo(x, y);
                ctx.stroke();
            });
            
            canvas.addEventListener('mouseup', () => {
                isDrawing = false;
            });
            
            canvas.addEventListener('mouseleave', () => {
                isDrawing = false;
            });
            
            // 터치 이벤트 지원
            canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                isDrawing = true;
                ctx.beginPath();
                ctx.moveTo(x, y);
            });
            
            canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                if (!isDrawing) return;
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                
                if (canvas.dataset.tool === 'eraser') {
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.lineWidth = 10;
                } else {
                    ctx.globalCompositeOperation = 'source-over';
                    ctx.lineWidth = 2;
                    ctx.strokeStyle = '#000000';
                }
                
                ctx.lineTo(x, y);
                ctx.stroke();
            });
            
            canvas.addEventListener('touchend', () => {
                isDrawing = false;
            });
        }
        
        // 도구 선택
        function selectTool(evalDiv, tool) {
            const canvas = evalDiv.querySelector('.mini-canvas');
            canvas.dataset.tool = tool;
            
            // 버튼 활성화 상태 업데이트
            const buttons = evalDiv.querySelectorAll('.eval-tool-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            if (tool === 'pen') {
                buttons[0].classList.add('active');
            } else if (tool === 'eraser') {
                buttons[1].classList.add('active');
            }
        }
        
        // 음성 녹음 토글
        
        function toggleRecording(evalDiv, button) {
            if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                // 녹음 시작
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(stream => {
                        mediaRecorder = new MediaRecorder(stream);
                        audioChunks = [];
                        
                        mediaRecorder.ondataavailable = (event) => {
                            audioChunks.push(event.data);
                        };
                        
                        mediaRecorder.onstop = () => {
                            const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                            evalDiv.dataset.audioData = URL.createObjectURL(audioBlob);
                            stream.getTracks().forEach(track => track.stop());
                        };
                        
                        mediaRecorder.start();
                        button.classList.add('recording');
                        button.innerHTML = '<span>⏹️</span><span>녹음 중지</span>';
                    })
                    .catch(err => {
                        console.error('마이크 접근 권한이 필요합니다:', err);
                        alert('마이크 접근 권한이 필요합니다.');
                    });
            } else {
                // 녹음 중지
                mediaRecorder.stop();
                button.classList.remove('recording');
                button.innerHTML = '<span>🎤</span><span>음성 녹음</span>';
            }
        }
        
        // 평가 제출
        function submitEvaluation(evalDiv, stepNumber) {
            const canvas = evalDiv.querySelector('.mini-canvas');
            const canvasData = canvas.toDataURL();
            const audioData = evalDiv.dataset.audioData;
            
            // 제출 데이터 준비
            const submissionData = {
                step: stepNumber,
                canvas: canvasData,
                audio: audioData,
                timestamp: new Date().toISOString()
            };
            
            // 여기서 실제로 서버에 전송하거나 저장
            console.log('평가 제출:', submissionData);
            
            // 피드백 표시
            const feedback = evalDiv.querySelector('.submission-feedback');
            feedback.classList.add('show');
            
            // 버튼 비활성화
            evalDiv.querySelectorAll('.submit-btn').forEach(btn => {
                btn.disabled = true;
            });
            
            // 2초 후 다음 단계로
            setTimeout(() => {
                evalDiv.style.opacity = '0.6';
                document.getElementById('nextButton').disabled = false;
            }, 2000);
        }
        
        // 평가 건너뛰기
        function skipEvaluation(evalDiv, stepNumber) {
            evalDiv.style.opacity = '0.6';
            document.getElementById('nextButton').disabled = false;
        }
        
        // 화이트보드 초기화 함수 (전역 스코프에도 노출)
        window.initWhiteboard = function initWhiteboard() {
            console.log('initWhiteboard 시작');
            
            // currentProblemData 유효성 검사
            if (!currentProblemData || !currentProblemData.similarProblem) {
                console.error('currentProblemData 또는 similarProblem이 없습니다');
                console.log('currentProblemData:', currentProblemData);
                return;
            }
            
            // 유사문제 정보 업데이트
            const similarProblemTextElement = document.getElementById('similarProblemText');
            if (similarProblemTextElement) {
                similarProblemTextElement.textContent = currentProblemData.similarProblem.description;
            } else {
                console.error('similarProblemText 요소를 찾을 수 없습니다');
            }
            
            // 답안 선택 옵션 업데이트
            const answerSelect = document.getElementById('answerSelect');
            if (!answerSelect) {
                console.error('answerSelect 요소를 찾을 수 없습니다');
                return;
            }
            
            answerSelect.innerHTML = '<option value="">답을 선택하세요</option>';
            
            if (currentProblemData.similarProblem.options && Array.isArray(currentProblemData.similarProblem.options)) {
                currentProblemData.similarProblem.options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.value;
                    optionElement.textContent = option.text;
                    answerSelect.appendChild(optionElement);
                });
            } else {
                console.error('similarProblem.options가 없거나 배열이 아닙니다');
            }
            
            const canvas = document.getElementById('whiteboardCanvas');
            if (!canvas) {
                console.error('화이트보드 캔버스를 찾을 수 없습니다');
                return;
            }
            
            const ctx = canvas.getContext('2d');
            const canvasWrapper = document.querySelector('.canvas-wrapper');
            
            if (!canvasWrapper) {
                console.error('캔버스 wrapper를 찾을 수 없습니다');
                return;
            }
            
            function resizeCanvas() {
                canvas.width = canvasWrapper.offsetWidth;
                canvas.height = canvasWrapper.offsetHeight;
                // 캔버스 크기 변경 후 배경을 흰색으로 설정
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            }
            
            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);
            
            let isDrawing = false;
            let currentTool = 'pen';
            let currentColor = '#000000';
            let currentThickness = 2;
            
            document.getElementById('penTool').addEventListener('click', function() {
                currentTool = 'pen';
                updateToolButtons();
                canvas.style.cursor = 'crosshair';
            });
            
            document.getElementById('eraserTool').addEventListener('click', function() {
                currentTool = 'eraser';
                updateToolButtons();
                canvas.style.cursor = 'grab';
            });
            
            document.getElementById('clearTool').addEventListener('click', function() {
                if (confirm('화이트보드를 모두 지우시겠습니까?')) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
            });
            
            document.getElementById('colorPicker').addEventListener('change', function(e) {
                currentColor = e.target.value;
            });
            
            document.getElementById('thicknessSlider').addEventListener('input', function(e) {
                currentThickness = e.target.value;
                document.getElementById('thicknessValue').textContent = currentThickness;
            });
            
            function updateToolButtons() {
                document.querySelectorAll('.tool-button').forEach(btn => {
                    if (btn && btn.classList) {
                        btn.classList.remove('active');
                    }
                });
                
                if (currentTool === 'pen') {
                    const penTool = document.getElementById('penTool');
                    if (penTool && penTool.classList) {
                        penTool.classList.add('active');
                    }
                } else if (currentTool === 'eraser') {
                    const eraserTool = document.getElementById('eraserTool');
                    if (eraserTool && eraserTool.classList) {
                        eraserTool.classList.add('active');
                    }
                }
            }
            
            // 초기 도구 상태 설정
            updateToolButtons();
            
            // 캔버스 초기 배경 설정
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            
            canvas.addEventListener('touchstart', handleTouch);
            canvas.addEventListener('touchmove', handleTouch);
            canvas.addEventListener('touchend', stopDrawing);
            
            function startDrawing(e) {
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                ctx.beginPath();
                ctx.moveTo(x, y);
            }
            
            function draw(e) {
                if (!isDrawing) return;
                
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                ctx.lineWidth = currentThickness;
                ctx.lineCap = 'round';
                
                if (currentTool === 'pen') {
                    ctx.globalCompositeOperation = 'source-over';
                    ctx.strokeStyle = currentColor;
                } else if (currentTool === 'eraser') {
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.lineWidth = currentThickness * 3;
                }
                
                ctx.lineTo(x, y);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(x, y);
            }
            
            function stopDrawing() {
                if (isDrawing) {
                    isDrawing = false;
                    ctx.beginPath();
                }
            }
            
            function handleTouch(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' : 
                                                e.type === 'touchmove' ? 'mousemove' : 'mouseup', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }
            
            const submitBtn = document.getElementById('submitButton');
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const answerSelect = document.getElementById('answerSelect');
                    if (!answerSelect) {
                        alert('답안 선택 요소를 찾을 수 없습니다.');
                        return;
                    }
                    
                    const selectedAnswer = answerSelect.value;
                    
                    if (!selectedAnswer || selectedAnswer === '') {
                        alert('답을 선택해주세요.');
                        return;
                    }
                    
                    if (typeof window.checkAnswer === 'function') {
                        window.checkAnswer(parseInt(selectedAnswer));
                    } else {
                        alert('채점 기능에 오류가 발생했습니다.');
                    }
                });
            }
            
            const closeBtn = document.getElementById('closeButton');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    if (confirm('서술평가를 종료하시겠습니까?\n\n작성한 내용이 저장되지 않습니다.')) {
                        location.reload();
                    }
                });
            }
        }
        
        // DOMContentLoaded 이벤트 리스너
        document.addEventListener('DOMContentLoaded', function() {
            const insightButton = document.getElementById('insightButton');
            if (insightButton) {
                insightButton.addEventListener('click', showInsight);
            }
            
            const nextButton = document.getElementById('nextButton');
            if (nextButton) {
                nextButton.addEventListener('click', showNextStep);
            }
            
            document.addEventListener('click', function(e) {
                if (e.target && e.target.id === 'resultOverlay') {
                    closeResultPopup();
                } else if (e.target && e.target.id === 'solutionOverlay') {
                    closeSolution();
                }
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const solutionPopup = document.getElementById('solutionPopup');
                    const resultPopup = document.getElementById('resultPopup');
                    
                    if (solutionPopup && solutionPopup.classList && solutionPopup.classList.contains('active')) {
                        closeSolution();
                    } else if (resultPopup && resultPopup.classList && resultPopup.classList.contains('active')) {
                        closeResultPopup();
                    }
                }
            });
            
            // 문제 데이터 로드
            loadProblemsData();
        });
    </script>

</body></html>