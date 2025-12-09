<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB,$USER;
require_login();
$studentid=$_GET["userid"];
$cntid=$_GET["cntid"];
$cnttype=$_GET["cnttype"];

$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  ");
$role=$userrole->data;

// 문항 정보 가져오기
$cntpages=$DB->get_records_sql("SELECT * FROM mdl_icontent_pages where cmid='$cntid' ORDER BY pagenum ASC");
$result = json_decode(json_encode($cntpages), True);

// studentid가 없으면 기본값 설정
if($studentid==NULL)$studentid=2;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학 집중 훈련 시스템</title>
    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        @keyframes slideOutLeft {
            0% {
                transform: translateX(0);
                opacity: 1;
            }
            100% {
                transform: translateX(-100%);
                opacity: 0;
            }
        }
        
        @keyframes slideInRight {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            0% {
                opacity: 1;
                transform: scale(1);
            }
            100% {
                opacity: 0;
                transform: scale(0.95);
            }
        }
        
        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: scale(1.05);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes loadingPulse {
            0%, 100% {
                opacity: 0.4;
            }
            50% {
                opacity: 1;
            }
        }
        
        @keyframes loadingRotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        @keyframes loadingWave {
            0%, 40%, 100% {
                transform: scaleY(0.4);
            }
            20% {
                transform: scaleY(1.0);
            }
        }
        
        .slide-out-left {
            animation: slideOutLeft 0.5s ease-in-out forwards;
        }
        
        .slide-in-right {
            animation: slideInRight 0.5s ease-in-out forwards;
        }
        
        .fade-out {
            animation: fadeOut 0.3s ease-out forwards;
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Loading overlay styles */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            opacity: 0;
            transition: opacity 0.3s ease-out;
            pointer-events: none;
            visibility: hidden;
        }
        
        .loading-overlay.active {
            opacity: 1;
            pointer-events: auto;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .loading-spinner::before {
            content: '';
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 4px solid #e5e7eb;
            border-top-color: #3b82f6;
            position: absolute;
            animation: loadingRotate 0.8s linear infinite;
        }
        
        .loading-text {
            margin-top: 1.5rem;
            font-size: 1.125rem;
            color: #6b7280;
            animation: loadingPulse 1.5s ease-in-out infinite;
        }
        
        .loading-wave {
            display: flex;
            gap: 0.25rem;
            margin-top: 2rem;
        }
        
        .loading-wave span {
            display: block;
            width: 4px;
            height: 20px;
            background: #3b82f6;
            animation: loadingWave 1.2s ease-in-out infinite;
        }
        
        .loading-wave span:nth-child(2) {
            animation-delay: -1.1s;
        }
        
        .loading-wave span:nth-child(3) {
            animation-delay: -1.0s;
        }
        
        .loading-wave span:nth-child(4) {
            animation-delay: -0.9s;
        }
        
        .loading-wave span:nth-child(5) {
            animation-delay: -0.8s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            min-height: 100vh;
            background: #ffffff;
            color: #111827;
            padding: 2rem;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        body.light-mode {
            background: #ffffff;
            color: #111827;
        }

        /* During training - lock body scroll */
        body.training-mode {
            height: 100vh;
            overflow: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            min-height: calc(100vh - 4rem);
        }

        /* Full screen mode */
        .fullscreen-mode {
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        /* Right sidebar for controls */
        .right-sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            padding: 2rem 1rem;
            background: rgba(0, 0, 0, 0.05);
            min-width: 200px;
            height: 100vh;
            overflow-y: auto;
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }

        .control-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .time-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .pause-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            background: rgba(220, 38, 38, 0.8);
            border: none;
            border-radius: 0.5rem;
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .pause-btn:hover {
            background: #dc2626;
        }
        
        .skip-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            background: rgba(59, 130, 246, 0.8);
            border: none;
            border-radius: 0.5rem;
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 0.5rem;
        }

        .skip-btn:hover {
            background: #3b82f6;
        }
        
        .algorithm-selector {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: rgba(99, 102, 241, 0.8);
            border: none;
            border-radius: 0.5rem;
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 0.5rem;
        }

        .algorithm-selector:hover {
            background: #6366f1;
        }
        
        .algorithm-icon {
            font-size: 1rem;
        }
        
        .algorithm-text {
            flex: 1;
            text-align: center;
        }
        
        .algorithm-icon {
            font-size: 1rem;
        }

        .progress-display {
            font-size: 0.75rem;
        }

        /* Main problem area in fullscreen */
        .fullscreen-problem-area {
            background: #ffffff;
            flex: 1;
            color: #111827;
            display: flex;
            flex-direction: row;
            height: 100vh;
        }

        /* Split layout - only when training is running */
        .fullscreen-problem-area.split-layout {
            display: flex;
            flex-direction: row;
        }


        .fullscreen-problem-area.split-layout .whiteboard-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            width: 100%;
            height: 100%;
            border-right: 1px solid rgba(229, 231, 235, 0.2);
            position: relative;
            z-index: 1;
        }
        
        /* Ensure iframe is always interactive when visible */
        .whiteboard-section iframe {
            pointer-events: auto !important;
            position: relative;
            z-index: 1;
        }
        
        /* Ensure loading overlay doesn't block when hidden */
        .loading-overlay:not(.active) {
            pointer-events: none !important;
            visibility: hidden !important;
        }

        .problem-header-fullscreen {
            text-align: center;
            margin-bottom: 2rem;
        }

        .problem-number-small {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .problem-text-large {
            font-size: 2.5rem;
            font-weight: bold;
            color: #111827;
            line-height: 1.2;
            padding: 2rem;
            background: #ffffff;
            border-radius: 1rem;
            border: none;
            margin: 1.5rem 0;
        }
        
        .problem-text-large img {
            max-width: 100%;
            height: auto;
            border-radius: 0.75rem;
            box-shadow: none;
            background: transparent;
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: zoom-in;
        }
        
        .problem-text-large img:hover {
            transform: scale(1.15);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            z-index: 10;
            position: relative;
        }

        .canvas-container {
            flex: 1;
            padding: 1rem;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
        }

        .canvas-fullscreen {
            width: 100%;
            min-height: 800px;
            background: #f9fafb;
            border-radius: 0.5rem;
            cursor: crosshair;
            border: 1px solid #e5e7eb;
        }

        /* Enhanced canvas for training mode */
        .fullscreen-problem-area.split-layout .canvas-container {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .fullscreen-problem-area.split-layout .canvas-fullscreen {
            min-height: calc(200vh - 2rem);
        }

        .clear-canvas-btn {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            color: #6b7280;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e5e7eb;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .clear-canvas-btn:hover {
            color: #374151;
            background: #ffffff;
        }

        .choices-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
            padding-bottom: 4rem; /* Increased to avoid overlap with bottom bar */
        }

        .choices-container-vertical {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
            max-width: 300px;
            margin-top: 2rem;
        }

        .choice-btn {
            min-width: 80px;
            padding: 0.75rem 1rem;
            background: #e5e7eb;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            color: #111827;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .choice-btn:hover {
            background: #d1d5db;
            transform: translateY(-1px);
        }

        .choice-btn-vertical {
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1rem;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #111827;
        }

        .header p {
            color: #6b7280;
        }
        
        body.dark-mode .header h1 {
            color: white;
        }
        
        body.dark-mode .header p {
            color: #d1d5db;
        }

        /* Controls */
        .controls {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }

        @media (min-width: 1024px) {
            .controls {
                flex-direction: row;
                justify-content: center;
            }
        }

        .control-group {
            background: #f3f4f6;
            backdrop-filter: blur(10px);
            border-radius: 0.5rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .control-group label {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        body.dark-mode .control-group {
            background: rgba(255, 255, 255, 0.1);
        }
        
        body.dark-mode .control-group label {
            color: #d1d5db;
        }

        .time-btn {
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        body.dark-mode .time-btn {
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .time-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .time-btn.active {
            background: #2563eb;
        }

        .divider {
            height: 1.5rem;
            width: 1px;
            background: rgba(255, 255, 255, 0.3);
        }

        .order-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            color: #374151;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.875rem;
        }
        
        body.dark-mode .order-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
        }

        .order-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.3);
        }

        .order-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .start-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(to right, #2563eb, #7c3aed);
            border: none;
            border-radius: 0.25rem;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .start-btn:hover {
            background: linear-gradient(to right, #1d4ed8, #6d28d9);
        }

        /* Cycle mode buttons */
        .cycle-btn {
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .cycle-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .cycle-btn.active {
            background: #7c3aed;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #d1d5db;
        }

        /* Progress Bar */
        .progress-container {
            margin-bottom: 2rem;
        }

        .progress-bar {
            height: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, #3b82f6, #10b981);
            transition: width 0.5s;
        }

        /* Problem Area */
        .problem-area {
            background: #ffffff;
            border-radius: 1rem;
            padding: 2.5rem;
            margin-bottom: 2rem;
            color: #111827;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            border: none;
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }

        .problem-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .problem-number {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .problem-text {
            font-size: 1.875rem;
            font-weight: bold;
            color: #111827;
            padding: 1.5rem;
            background: #ffffff;
            border-radius: 0.75rem;
            border: none;
            margin: 1rem 0;
        }
        
        .problem-text img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: none;
            background: transparent;
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: zoom-in;
        }
        
        .problem-text img:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            z-index: 10;
            position: relative;
        }

        .cycle-badge {
            display: inline-block;
            margin-left: 0.5rem;
            padding: 0.25rem 0.5rem;
            background: #7c3aed;
            color: white;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }

        /* Whiteboard */
        .whiteboard-container {
            margin-bottom: 1.5rem;
        }

        .whiteboard {
            width: 100%;
            height: 16rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            cursor: crosshair;
            border: 2px solid #e5e7eb;
        }

        /* Completion Screen */
        .completion-screen {
            background: #d1fae5;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            color: #065f46;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .completion-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #10b981;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            padding: 2rem;
        }

        .modal-content {
            width: 95%;
            max-width: 1600px;
            margin: 2rem auto;
            background: #ffffff;
            border-radius: 1rem;
            padding: 2rem;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        body.dark-mode .modal {
            background: rgba(0, 0, 0, 0.8);
        }
        
        body.dark-mode .modal-content {
            background: #1f2937;
        }

        .modal-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .modal-header h2 {
            font-size: 1.875rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #111827;
        }
        
        .modal-header p {
            color: #6b7280;
        }
        
        body.dark-mode .modal-header h2 {
            color: white;
        }
        
        body.dark-mode .modal-header p {
            color: #d1d5db;
        }

        .order-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .order-item {
            padding: 2rem;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .order-item:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        .order-item.selected {
            background: #dbeafe;
            border-color: #3b82f6;
        }
        
        body.dark-mode .order-item {
            background: rgba(255, 255, 255, 0.1);
            border-color: transparent;
        }
        
        body.dark-mode .order-item:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        body.dark-mode .order-item.selected {
            background: #2563eb;
            border-color: #3b82f6;
        }

        .order-badge {
            background: #3b82f6;
            color: white;
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-right: 0.5rem;
        }

        .modal-footer {
            text-align: center;
        }

        .modal-footer button {
            margin: 0 0.5rem;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .confirm-btn {
            background: #10b981;
            color: white;
        }

        .confirm-btn:hover {
            background: #059669;
        }

        .cancel-btn {
            background: #6b7280;
            color: white;
        }

        .cancel-btn:hover {
            background: #4b5563;
        }
        
        /* Modal view mode buttons */
        .modal-view-btn {
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            color: #374151;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .modal-view-btn:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }
        
        .modal-view-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        body.dark-mode .modal-view-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
        }
        
        body.dark-mode .modal-view-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        body.dark-mode .modal-view-btn.active {
            background: #3b82f6;
        }
        
        /* Order scroll mode */
        .order-scroll {
            max-height: 60vh;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .order-scroll-item {
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .order-scroll-item:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        
        .order-scroll-item.selected {
            background: #dbeafe;
            border-color: #3b82f6;
        }
        
        .order-scroll-item h3 {
            color: #111827;
        }
        
        body.dark-mode .order-scroll-item {
            background: rgba(255, 255, 255, 0.1);
            border-color: transparent;
        }
        
        body.dark-mode .order-scroll-item:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        body.dark-mode .order-scroll-item.selected {
            background: #2563eb;
            border-color: #3b82f6;
        }
        
        body.dark-mode .order-scroll-item h3 {
            color: white;
        }
        
        body.dark-mode #modalSelectedCounter {
            color: #d1d5db !important;
        }
        
        .order-scroll-item img {
            width: 50%;
            height: auto;
            max-height: 300px;
            object-fit: contain;
            border-radius: 0.5rem;
        }

        /* Button styles for modals */
        .btn-primary {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            padding: 10px 20px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #6b7280;
            background: none;
            border: none;
            cursor: pointer;
        }

        .close-btn:hover {
            color: #374151;
        }

        .modal-header {
            position: relative;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        /* Cycle order modal */
        .cycle-modal-content {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
        }

        .cycle-modal-header h2 {
            font-size: 1.875rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .cycle-modal-header p {
            color: #d1d5db;
            margin-bottom: 2rem;
        }

        .cycle-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .cycle-option-btn {
            width: 100%;
            padding: 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: white;
        }

        .cycle-option-btn.random {
            background: linear-gradient(to right, #dc2626, #ec4899);
        }

        .cycle-option-btn.random:hover {
            background: linear-gradient(to right, #b91c1c, #db2777);
        }

        .cycle-option-btn.number {
            background: linear-gradient(to right, #2563eb, #06b6d4);
        }

        .cycle-option-btn.number:hover {
            background: linear-gradient(to right, #1d4ed8, #0891b2);
        }

        .cycle-option-btn.order {
            background: linear-gradient(to right, #10b981, #059669);
        }

        .cycle-option-btn.order:hover {
            background: linear-gradient(to right, #059669, #047857);
        }

        .cycle-option-title {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .cycle-option-desc {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        /* Warning */
        .warning {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #dc2626;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: none;
            animation: pulse 2s infinite;
        }

        .hidden {
            display: none !important;
        }

        /* Problem Overview Grid */
        .problem-overview {
            background: #ffffff;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: none;
        }
        
        .problem-overview h2 {
            color: #111827;
        }

        .problem-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .problem-card {
            background: #ffffff;
            border-radius: 0.5rem;
            padding: 1rem;
            border: none;
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
            height: 140px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .problem-card:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .problem-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to bottom, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.6) 50%,
                rgba(255, 255, 255, 1) 100%);
            pointer-events: none;
            border-radius: 0 0 0.5rem 0.5rem;
        }

        .problem-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            flex-shrink: 0;
        }

        .problem-card-number {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 600;
        }

        .problem-status {
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-correct {
            background: #10b981;
            color: white;
        }

        .status-incorrect {
            background: #ef4444;
            color: white;
        }

        .status-unanswered {
            background: #6b7280;
            color: white;
        }

        .status-current {
            background: #3b82f6;
            color: white;
        }

        .problem-card-question {
            font-size: 0.95rem;
            font-weight: 500;
            color: #374151;
            line-height: 1.3;
            flex: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
        }

        .problem-card-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: auto;
            padding-top: 0.5rem;
            flex-shrink: 0;
        }

        .problem-order {
            background: #f3f4f6;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.625rem;
            color: #6b7280;
        }
        
        /* Pause selection styles */
        .pause-selectable {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .pause-selectable:hover {
            border-color: #3b82f6;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .pause-selected {
            border-color: #2563eb !important;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2) !important;
        }
        
        .pause-selected .problem-order {
            background: #2563eb;
            color: white;
            font-weight: 600;
        }
        
        /* Mode controls */
        .mode-btn {
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            color: #374151;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        body.dark-mode .mode-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
        }
        
        .mode-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .mode-btn.active {
            background: #3b82f6;
        }
        
        .theme-toggle-btn {
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            color: #374151;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        body.dark-mode .theme-toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
        }
        
        .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Tab mode styles */
        .tab-navigation {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 0.5rem 0.5rem 0 0;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .tab-btn.active {
            background: #ffffff;
            color: #111827;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        /* Scroll mode styles */
        .scroll-container {
            display: none;
            max-width: 50%;
            margin: 0 auto;
        }
        
        .scroll-container.active {
            display: block;
        }
        
        .scroll-problem-card {
            background: #ffffff;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            min-height: 300px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .scroll-problem-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        
        .scroll-problem-card img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 1rem 0;
            transition: transform 0.3s ease;
        }
        
        .scroll-problem-card img:hover {
            transform: scale(1.05);
        }
        
        /* Dark mode styles */
        body.dark-mode {
            background: linear-gradient(to bottom right, #0f172a, #1e293b);
        }
        
        body.dark-mode .problem-overview,
        body.dark-mode .problem-area,
        body.dark-mode .problem-card,
        body.dark-mode .scroll-problem-card {
            background: #1e293b;
            color: #e2e8f0;
        }
        
        body.dark-mode .problem-card-number,
        body.dark-mode .problem-card-details {
            color: #94a3b8;
        }
        
        body.dark-mode .problem-order {
            background: #334155;
            color: #cbd5e1;
        }
        
        body.dark-mode .tab-btn.active {
            background: #1e293b;
            color: #e2e8f0;
        }
        
        body.dark-mode .fullscreen-problem-area,
        body.dark-mode .fullscreen-problem-area.split-layout .whiteboard-section {
            background: #1e293b;
        }
        
        body.dark-mode .problem-text,
        body.dark-mode .problem-text-large {
            background: #1e293b;
            color: #e2e8f0;
        }
    </style>
    <!-- Chart.js for histogram -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div id="app">
        <!-- Full screen mode when running -->
        <div id="fullscreenMode" class="fullscreen-mode hidden">
            <!-- Main problem area -->
            <div class="fullscreen-problem-area" id="fullscreenProblemArea">
                <!-- Whiteboard iframe for split layout -->
                <div class="whiteboard-section" style="display: none; position: relative; z-index: 1;" id="whiteboardSection">
                    <iframe 
                        id="whiteboardIframe"
                        src="" 
                        style="width: 100%; height: 100%; border: none; position: relative; z-index: 1;">
                    </iframe>
                    <!-- Loading overlay -->
                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">문제를 불러오는 중...</div>
                        <div class="loading-wave">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>

                <!-- Right sidebar with all controls -->
                <div class="right-sidebar">
                    <!-- Control info -->
                    <div class="control-info">
                        <div class="time-display">
                            <span>⏱️</span>
                            <span style="font-family: monospace; font-size: 1.5rem;" id="fullscreenTime">3:00</span>
                        </div>
                        
                        <div class="progress-display" style="font-size: 1rem;">
                            <span id="fullscreenProgress">0/<?php echo count($result); ?></span> 완료
                        </div>
                        
                        <div class="total-time-display" style="font-size: 0.875rem; margin-top: 0.5rem;">
                            총시간: <span id="fullscreenTotalTime" style="font-family: monospace;">0:00</span>
                        </div>
                        
                        <div class="algorithm-selector" onclick="cycleAlgorithm()">
                            <span class="algorithm-icon" id="algorithmIcon">🎲</span>
                            <span class="algorithm-text" id="algorithmText">랜덤</span>
                        </div>
                    </div>

                    <!-- Complete button -->
                    <button class="skip-btn" style="background: rgba(16, 185, 129, 0.8);" onclick="completeProblem()">
                        <span>✓</span>
                        <span>완료</span>
                    </button>
                    
                    <!-- Skip button -->
                    <button class="skip-btn" onclick="skipToProblem()">
                        <span>⏭</span>
                        <span>SKIP</span>
                    </button>

                    <!-- Pause button -->
                    <button class="pause-btn" onclick="pauseTraining()">
                        <span>❚❚</span>
                        <span>일시정지</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Normal mode -->
        <div id="normalMode" class="container">
            <!-- Header -->
            <div class="header">
                <h1>6월 모의고사 만접 공략 시스템템</h1>
                <p>자동 전환 • 순서 선택 • 미완료 문제 순환</p>
                
                <!-- Mode Controls -->
                <div class="mode-controls" style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <!-- View Mode Toggle -->
                    <div class="mode-toggle-group" style="display: flex; gap: 0.5rem; background: rgba(255, 255, 255, 0.1); padding: 0.25rem; border-radius: 0.5rem;">
                        <button id="tabModeBtn" class="mode-btn active" onclick="setViewMode('tab')">
                            <span>📑</span> 탭 모드
                        </button>
                        <button id="scrollModeBtn" class="mode-btn" onclick="setViewMode('scroll')">
                            <span>📜</span> 스크롤 모드
                        </button>
                    </div>
                    
                    <!-- Theme Toggle -->
                    <button id="themeToggleBtn" class="theme-toggle-btn" onclick="toggleTheme()">
                        <span id="themeIcon">🌙</span>
                        <span id="themeText">다크 모드</span>
                    </button>
                </div>
            </div>

            <!-- Controls -->
            <div class="controls">
                <div class="control-group">
                    <label>문항당 시간:</label>
                    <button class="time-btn" data-time="60">1분</button>
                    <button class="time-btn" data-time="120">2분</button>
                    <button class="time-btn active" data-time="180">3분</button>
                    <button class="time-btn" data-time="240">4분</button>
                    <button class="time-btn" data-time="300">5분</button>
                    
                    <div class="divider"></div>
                    
                    <button id="orderBtn" class="order-btn" onclick="openOrderModal()">
                        ⚙️
                        <span>풀이순서 선택</span>
                    </button>
                    
                    <div class="divider"></div>
                    
                    <button class="start-btn" onclick="togglePlayPause()">
                        <span id="playIcon">▶</span>
                        <span id="playText">시작하기</span>
                    </button>
                </div>

                <!-- Next Cycle Mode -->
                <div id="cycleMode" class="control-group hidden">
                    <label>다음 순서:</label>
                    <button class="cycle-btn active" data-mode="order" onclick="setNextCycleMode('order')">
                        → 순서대로
                    </button>
                    <button class="cycle-btn" data-mode="number" onclick="setNextCycleMode('number')">
                        ✓ 번호대로
                    </button>
                    <button class="cycle-btn" data-mode="random" onclick="setNextCycleMode('random')">
                        🔀 랜덤으로
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-value" id="timeRemaining">3:00</div>
                    <div class="stat-label">남은 시간</div>
                </div>
                <div class="stat-card" onclick="showHistogram()" style="cursor: pointer;">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value" id="avgAttempts">0</div>
                    <div class="stat-label">평균 반복</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✓</div>
                    <div class="stat-value" id="completedCount">0/<?php echo count($result); ?></div>
                    <div class="stat-label">완료 문제</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-value" id="cycleCount">1</div>
                    <div class="stat-label">사이클</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-value" id="totalTime">0:00</div>
                    <div class="stat-label">총 시간</div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar" style="width: 0%"></div>
                </div>
            </div>

            <!-- Problem Overview Grid -->
            <div id="problemOverview" class="problem-overview">
                <h2 style="text-align: center; margin-bottom: 2rem; font-size: 1.5rem;" id="problemOverviewTitle">문제 목록</h2>
                
                <!-- Tab Mode Container -->
                <div id="tabModeContainer" class="tab-mode-container">
                    <div class="tab-navigation" id="tabNavigation"></div>
                    <div class="tab-contents" id="tabContents"></div>
                </div>
                
                <!-- Scroll Mode Container -->
                <div id="scrollModeContainer" class="scroll-container">
                    <div id="scrollProblemList"></div>
                </div>
                
                <!-- Default Grid Mode (for pause selection) -->
                <div class="problem-grid" id="problemGrid" style="display: none;"></div>
                
                <!-- Order selection during pause -->
                <div id="pauseOrderSelection" class="hidden" style="margin-top: 2rem;">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <p style="color: #6b7280; margin-bottom: 1rem;">남은 문항들의 풀이 순서를 변경하려면 원하는 순서대로 클릭하세요.</p>
                        <p style="color: #374151; font-weight: 500;">선택된 문항: <span id="pauseSelectedCount">0</span>개 / <span id="pauseRemainingCount">0</span>개</p>
                    </div>
                    <div style="text-align: center; gap: 1rem; display: flex; justify-content: center; flex-wrap: wrap;">
                        <button id="pauseConfirmBtn" class="confirm-btn" onclick="confirmPauseOrder()" style="padding: 0.75rem 2rem; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; background: #10b981; color: white;">순서 적용하기</button>
                        <button id="pauseResetBtn" class="cancel-btn" onclick="resetPauseOrder()" style="padding: 0.75rem 2rem; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; background: #6b7280; color: white;">순서 초기화</button>
                    </div>
                </div>
            </div>

            <!-- Hidden Main Problem Area (shown during training) -->
            <div id="problemArea" class="problem-area hidden">
                <div class="problem-header">
                    <div class="problem-number" id="problemNumber">문제 1</div>
                    <div class="problem-text" id="problemText">12 × 15 = ?</div>
                </div>

                <!-- Whiteboard -->
                <div class="whiteboard-container">
                    <canvas id="whiteboard" class="whiteboard"></canvas>
                    <button class="clear-canvas-btn" onclick="clearCanvas('whiteboard')">화이트보드 지우기</button>
                </div>

            </div>

            <!-- Completion Screen -->
            <div id="completionScreen" class="completion-screen hidden">
                <div class="completion-icon">✅</div>
                <h2>모든 문제 완료!</h2>
                <p>총 소요 시간: <span id="finalTime">0:00</span></p>
            </div>
        </div>
    </div>

    <!-- Order Selection Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>풀이 순서 선택</h2>
                <p style="color: #d1d5db;">원하는 순서대로 문항을 클릭하세요. 선택하지 않은 문항은 랜덤으로 배치됩니다.</p>
                
                <!-- View Mode Toggle in Modal -->
                <div style="margin-top: 1rem; display: flex; justify-content: center; gap: 0.5rem;">
                    <button id="modalGridBtn" class="modal-view-btn active" onclick="setModalViewMode('grid')">
                        <span>⚏</span> 그리드
                    </button>
                    <button id="modalScrollBtn" class="modal-view-btn" onclick="setModalViewMode('scroll')">
                        <span>☰</span> 스크롤
                    </button>
                </div>
            </div>
            
            <div id="orderGrid" class="order-grid"></div>
            <div id="orderScroll" class="order-scroll" style="display: none;"></div>
            
            <div id="modalSelectedCounter" style="text-align: center; margin-bottom: 1rem; color: #6b7280;">
                선택된 문항: <span id="selectedCount">0</span>개 / <?php echo count($result); ?>개
            </div>
            
            <div class="modal-footer">
                <button class="confirm-btn" onclick="confirmOrder()">순서 확정하기</button>
                <button class="cancel-btn" onclick="closeOrderModal()">취소</button>
            </div>
        </div>
    </div>

    <!-- Histogram Modal -->
    <div id="histogramModal" class="modal">
        <div class="modal-content" style="max-width: 900px; width: 90%;">
            <div class="modal-header">
                <h2>문항별 반복 횟수 분석</h2>
                <button class="close-btn" onclick="closeHistogram()">×</button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <canvas id="attemptHistogram" width="800" height="400"></canvas>
                <div id="recommendationSection" style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-radius: 8px;">
                    <h3 style="margin-top: 0;">📚 추천 유사문제</h3>
                    <div id="recommendedProblems"></div>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px;">
                <button class="btn-secondary" onclick="closeHistogram()">닫기</button>
                <button class="btn-primary" onclick="openSimilarQuiz()">유사문제 풀기</button>
            </div>
        </div>
    </div>

    <!-- Cycle Order Modal -->
    <div id="cycleOrderModal" class="modal">
        <div class="cycle-modal-content">
            <div class="cycle-modal-header">
                <h2>다음 라운드 순서 선택</h2>
                <p>미완료 문제들을 어떤 순서로 풀어볼까요?</p>
            </div>
            
            <div class="cycle-options">
                <button class="cycle-option-btn random" onclick="handleCycleOrderSelection('random')">
                    <span style="font-size: 1.5rem;">🔀</span>
                    <div>
                        <div class="cycle-option-title">랜덤 순서</div>
                        <div class="cycle-option-desc">무작위로 섞어서 풀기</div>
                    </div>
                </button>
                
                <button class="cycle-option-btn number" onclick="handleCycleOrderSelection('number')">
                    <span style="font-size: 1.5rem;">✓</span>
                    <div>
                        <div class="cycle-option-title">번호 순서</div>
                        <div class="cycle-option-desc">문제 번호대로 풀기</div>
                    </div>
                </button>
                
                <button class="cycle-option-btn order" onclick="handleCycleOrderSelection('order')">
                    <span style="font-size: 1.5rem;">→</span>
                    <div>
                        <div class="cycle-option-title">선택 순서</div>
                        <div class="cycle-option-desc">처음 선택한 순서대로 풀기</div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Warning -->
    <div id="warning" class="warning">
        ⚠️ 30초 남음!
    </div>


    <script>
        // Problems data from PHP
        const problems = [
            <?php
            $problemIndex = 0;
            foreach($result as $value) {
                $problemIndex++;
                $contentsid = $value['id'];
                $wboardid = 'jnrsorksqcrark' . $contentsid . '_user' . $studentid;
                
                // 문항 이미지 정보 추출
                $getimg = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid'");
                $ctext = $getimg->pageicontent;
                
                $htmlDom = new DOMDocument;
                @$htmlDom->loadHTML($ctext);
                $imageTags = $htmlDom->getElementsByTagName('img');
                
                $imgSrc = '';
                foreach($imageTags as $imageTag) {
                    $imgSrc = $imageTag->getAttribute('src');
                    $imgSrc = str_replace(' ', '%20', $imgSrc);
                    if(strpos($imgSrc, 'MATRIX') !== false || strpos($imgSrc, 'MATH') !== false || strpos($imgSrc, 'imgur') !== false) {
                        break;
                    }
                }
                $imgSrc = str_replace('MathNote', 'MathNote_exam', $imgSrc);
                
                echo "{ 
                    id: $problemIndex, 
                    contentsid: '$contentsid',
                    wboardid: '$wboardid',
                    imgSrc: '$imgSrc',
                    question: '', 
                    answer: '', 
                    choices: []
                }";
                
                if($problemIndex < count($result)) {
                    echo ",\n            ";
                }
            }
            ?>
        ];

        // State
        let currentProblemIndex = 0;
        let userAnswers = {};
        let timePerProblem = 180; // 기본값 3분
        let timeRemaining = 180; // 초기값은 timePerProblem과 동일
        let totalTime = 0;
        let isRunning = false;
        let cycleCount = 1;
        let selectedOrder = [];
        let hasCompletedFirstCycle = false;
        let nextCycleMode = 'order';
        let originalOrder = [];
        let problemOrder = [];
        let timer = null;
        let attemptedProblems = new Set();
        let problemAttemptCounts = {}; // 문항별 반복 횟수 추적
        let showCycleOrderModal = false;
        let currentAlgorithm = 'random';
        let pauseSelectedOrder = [];
        let remainingProblems = [];
        let currentViewMode = 'tab';
        let currentTheme = 'light';
        let currentTabIndex = 0;
        let modalViewMode = 'grid';

        // Canvas variables
        let canvases = {};
        let contexts = {};
        let isDrawing = false;

        // Initialize
        window.onload = function() {
            // Set default order
            problemOrder = problems.map((_, index) => index);
            originalOrder = [...problemOrder];

            // Canvas setup
            setupCanvas('whiteboard');
            setupCanvas('fullscreenCanvas');

            // Event listeners
            document.querySelectorAll('.time-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (isRunning) return;
                    document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    timePerProblem = parseInt(this.dataset.time);
                    timeRemaining = timePerProblem;
                    updateDisplay();
                });
            });

            updateDisplay();
            renderChoices();
            renderProblemsByMode();
        };
        
        // Set view mode (tab or scroll)
        function setViewMode(mode) {
            currentViewMode = mode;
            
            // Update button states
            if (mode === 'tab') {
                document.getElementById('tabModeBtn').classList.add('active');
                document.getElementById('scrollModeBtn').classList.remove('active');
            } else {
                document.getElementById('tabModeBtn').classList.remove('active');
                document.getElementById('scrollModeBtn').classList.add('active');
            }
            
            // Render problems in the selected mode
            renderProblemsByMode();
        }
        
        // Toggle theme between light and dark
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('themeIcon');
            const themeText = document.getElementById('themeText');
            
            if (currentTheme === 'light') {
                currentTheme = 'dark';
                body.classList.add('dark-mode');
                themeIcon.textContent = '☀️';
                themeText.textContent = '라이트 모드';
            } else {
                currentTheme = 'light';
                body.classList.remove('dark-mode');
                themeIcon.textContent = '🌙';
                themeText.textContent = '다크 모드';
            }
        }
        
        // Render problems based on current view mode
        function renderProblemsByMode() {
            const isPauseMode = !isRunning && attemptedProblems.size > 0;
            
            if (isPauseMode) {
                // In pause mode, always use grid for selection
                renderProblemGrid();
                document.getElementById('problemGrid').style.display = 'grid';
                document.getElementById('tabModeContainer').style.display = 'none';
                document.getElementById('scrollModeContainer').classList.remove('active');
            } else if (currentViewMode === 'tab') {
                renderTabMode();
                document.getElementById('problemGrid').style.display = 'none';
                document.getElementById('tabModeContainer').style.display = 'block';
                document.getElementById('scrollModeContainer').classList.remove('active');
            } else {
                renderScrollMode();
                document.getElementById('problemGrid').style.display = 'none';
                document.getElementById('tabModeContainer').style.display = 'none';
                document.getElementById('scrollModeContainer').classList.add('active');
            }
        }
        
        // Render tab mode with 5 problems per tab
        function renderTabMode() {
            const tabNavigation = document.getElementById('tabNavigation');
            const tabContents = document.getElementById('tabContents');
            
            tabNavigation.innerHTML = '';
            tabContents.innerHTML = '';
            
            const problemsPerTab = 5;
            const totalTabs = Math.ceil(problems.length / problemsPerTab);
            
            for (let tabIndex = 0; tabIndex < totalTabs; tabIndex++) {
                // Create tab button
                const tabBtn = document.createElement('button');
                tabBtn.className = `tab-btn ${tabIndex === currentTabIndex ? 'active' : ''}`;
                const startNum = tabIndex * problemsPerTab + 1;
                const endNum = Math.min((tabIndex + 1) * problemsPerTab, problems.length);
                tabBtn.textContent = `${startNum}-${endNum}번`;
                tabBtn.onclick = () => switchTab(tabIndex);
                tabNavigation.appendChild(tabBtn);
                
                // Create tab content
                const tabContent = document.createElement('div');
                tabContent.className = `tab-content ${tabIndex === currentTabIndex ? 'active' : ''}`;
                tabContent.id = `tab-${tabIndex}`;
                
                const startIdx = tabIndex * problemsPerTab;
                const endIdx = Math.min(startIdx + problemsPerTab, problems.length);
                
                for (let i = startIdx; i < endIdx; i++) {
                    const problem = problems[i];
                    const card = createProblemCard(problem, i);
                    tabContent.appendChild(card);
                }
                
                tabContents.appendChild(tabContent);
            }
        }
        
        // Switch between tabs
        function switchTab(tabIndex) {
            currentTabIndex = tabIndex;
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach((btn, idx) => {
                if (idx === tabIndex) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            // Update tab contents
            document.querySelectorAll('.tab-content').forEach((content, idx) => {
                if (idx === tabIndex) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        }
        
        // Render scroll mode with larger cards
        function renderScrollMode() {
            const scrollList = document.getElementById('scrollProblemList');
            scrollList.innerHTML = '';
            
            problems.forEach((problem, index) => {
                const card = document.createElement('div');
                card.className = 'scroll-problem-card';
                
                // Determine status
                let status = 'unanswered';
                let statusText = '미입력';
                
                if (userAnswers[problem.id]) {
                    status = 'correct';
                    statusText = '완료';
                }
                
                if (index === currentProblemIndex && isRunning) {
                    status = 'current';
                    statusText = '진행중';
                }
                
                card.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.5rem; font-weight: bold; color: ${currentTheme === 'dark' ? '#e2e8f0' : '#111827'};">문제 ${problem.id}</h3>
                        <div class="problem-status status-${status}" style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600;">${statusText}</div>
                    </div>
                    ${problem.imgSrc ? `
                        <div style="text-align: center;">
                            <img src="${problem.imgSrc}" alt="문제 ${problem.id}" style="max-width: 100%; height: auto; border-radius: 0.75rem; cursor: zoom-in;" 
                                onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 12px 30px rgba(0,0,0,0.15)';" 
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                        </div>
                    ` : `<div style="text-align: center; font-size: 1.25rem; color: #6b7280;">문제 ${problem.id}</div>`}
                    <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 0.875rem; color: #6b7280;">
                            ${originalOrder.indexOf(index) !== -1 ? `순서: ${originalOrder.indexOf(index) + 1}` : '랜덤'}
                        </div>
                    </div>
                `;
                
                scrollList.appendChild(card);
            });
        }
        
        // Create a problem card for tab mode
        function createProblemCard(problem, index) {
            const card = document.createElement('div');
            card.className = 'problem-card';
            
            // Determine status
            let status = 'unanswered';
            let statusText = '미입력';
            
            if (userAnswers[problem.id]) {
                status = 'correct';
                statusText = '완료';
            }
            
            if (index === currentProblemIndex && isRunning) {
                status = 'current';
                statusText = '진행중';
            }
            
            const orderIndex = originalOrder.indexOf(index);
            const orderText = orderIndex !== -1 ? `순서: ${orderIndex + 1}` : '랜덤';
            
            card.innerHTML = `
                <div class="problem-card-header">
                    <div class="problem-card-number">문제 ${problem.id}</div>
                    <div class="problem-status status-${status}">${statusText}</div>
                </div>
                <div class="problem-card-question" style="padding: 0.75rem; background: #ffffff; border-radius: 0.375rem;">
                    ${problem.imgSrc ? `<img src="${problem.imgSrc}" style="max-width: 100%; height: 50px; object-fit: contain; border-radius: 0.25rem; background: transparent; transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: zoom-in;" onmouseover="this.style.transform='scale(1.8)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'; this.style.zIndex='20'; this.style.position='relative';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'; this.style.zIndex='auto'; this.style.position='static';">` : `문제 ${problem.id}`}
                </div>
                <div class="problem-card-details">
                    <div class="problem-order">${orderText}</div>
                    <div style="font-size: 0.625rem;">&nbsp;</div>
                </div>
            `;
            
            return card;
        }

        // Canvas setup
        function setupCanvas(canvasId) {
            const canvas = document.getElementById(canvasId);
            const ctx = canvas.getContext('2d');
            
            // Set canvas size based on container
            if (canvasId === 'fullscreenCanvas') {
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;
            } else if (canvasId === 'fullscreenCanvasSplit') {
                const container = canvas.parentElement;
                canvas.width = container.offsetWidth - 32; // Account for padding
                canvas.height = window.innerHeight * 2; // Double screen height for scrolling
            } else {
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;
            }
            
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#1f2937';
            ctx.lineWidth = 3;
            
            canvases[canvasId] = canvas;
            contexts[canvasId] = ctx;

            canvas.addEventListener('mousedown', (e) => startDrawing(e, canvasId));
            canvas.addEventListener('mousemove', (e) => draw(e, canvasId));
            canvas.addEventListener('mouseup', () => stopDrawing());
            canvas.addEventListener('mouseout', () => stopDrawing());
        }

        // Drawing functions
        function startDrawing(e, canvasId) {
            isDrawing = true;
            const rect = canvases[canvasId].getBoundingClientRect();
            contexts[canvasId].beginPath();
            contexts[canvasId].moveTo(e.clientX - rect.left, e.clientY - rect.top);
        }

        function draw(e, canvasId) {
            if (!isDrawing) return;
            const rect = canvases[canvasId].getBoundingClientRect();
            contexts[canvasId].lineTo(e.clientX - rect.left, e.clientY - rect.top);
            contexts[canvasId].stroke();
        }

        function stopDrawing() {
            if (!isDrawing) return;
            isDrawing = false;
        }

        function clearCanvas(canvasId) {
            const canvas = canvases[canvasId];
            const ctx = contexts[canvasId];
            if (canvas && ctx) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        }

        // Timer functions
        function startTimer() {
            timer = setInterval(() => {
                timeRemaining--;
                totalTime++;

                if (timeRemaining <= 0) {
                    transitionToNextProblem();
                }

                if (timeRemaining === 30) {
                    document.getElementById('warning').style.display = 'block';
                } else if (timeRemaining > 30) {
                    document.getElementById('warning').style.display = 'none';
                }

                // Update cycle count every 30 minutes
                if (totalTime > 0 && totalTime % 1800 === 0) {
                    cycleCount++;
                }

                updateDisplay();
            }, 1000);
        }
        
        // Skip to next problem
        function skipToProblem() {
            transitionToNextProblem();
        }
        
        // Complete current problem
        function completeProblem() {
            if (!isRunning) return;
            
            const currentProblem = problems[currentProblemIndex];
            userAnswers[currentProblem.id] = 'completed';
            transitionToNextProblem();
        }
        
        // Add transition effect when moving to next problem
        function transitionToNextProblem() {
            // Show loading overlay
            showLoadingOverlay();
            
            // Move to next problem
            moveToNextUnsolved();
        }
        
        // Show loading overlay
        function showLoadingOverlay() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = '';
                // Small delay to ensure display change is processed
                setTimeout(() => {
                    loadingOverlay.classList.add('active');
                }, 10);
            }
        }
        
        // Hide loading overlay
        function hideLoadingOverlay() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.classList.remove('active');
                // Ensure overlay is completely out of the way
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 300); // After transition completes
            }
        }

        function stopTimer() {
            clearInterval(timer);
            document.getElementById('warning').style.display = 'none';
        }

        // Problem navigation
        function moveToNextUnsolved() {
            const unsolvedIndices = problemOrder.filter(index => !userAnswers[problems[index].id]);
            
            if (unsolvedIndices.length === 0) {
                completeTraining();
                return;
            }

            // Mark current problem as attempted
            attemptedProblems.add(currentProblemIndex);
            
            // Track attempt count for each problem
            const problemId = problems[currentProblemIndex].id;
            if (!problemAttemptCounts[problemId]) {
                problemAttemptCounts[problemId] = 0;
            }
            if (!userAnswers[problemId]) {
                problemAttemptCounts[problemId]++;
            }

            // Check if all problems have been attempted once
            const allProblemsAttempted = problemOrder.every(index => 
                attemptedProblems.has(index) || index === currentProblemIndex
            );
            
            if (allProblemsAttempted && unsolvedIndices.length > 1) {
                // Round complete - show cycle order modal
                pauseTraining();
                showCycleOrderModal = true;
                document.getElementById('cycleOrderModal').style.display = 'block';
                return;
            }

            const currentOrderIndex = unsolvedIndices.indexOf(currentProblemIndex);
            const nextIndex = unsolvedIndices[(currentOrderIndex + 1) % unsolvedIndices.length];
            currentProblemIndex = nextIndex;
            timeRemaining = timePerProblem; // Reset timer for new problem
            clearCanvas('whiteboard');
            updateDisplay();
            renderChoices();
        }

        function handleCycleOrderSelection(mode) {
            nextCycleMode = mode;
            const unsolvedIndices = problemOrder.filter(index => !userAnswers[problems[index].id]);
            
            let newOrder;
            switch (mode) {
                case 'order':
                    newOrder = originalOrder.filter(index => !userAnswers[problems[index].id]);
                    break;
                case 'number':
                    newOrder = unsolvedIndices.sort((a, b) => problems[a].id - problems[b].id);
                    break;
                case 'random':
                    newOrder = [...unsolvedIndices].sort(() => Math.random() - 0.5);
                    break;
                default:
                    newOrder = unsolvedIndices;
            }
            
            problemOrder = newOrder;
            currentProblemIndex = newOrder[0];
            attemptedProblems = new Set();
            showCycleOrderModal = false;
            document.getElementById('cycleOrderModal').style.display = 'none';
            hasCompletedFirstCycle = true;
            document.getElementById('cycleMode').classList.remove('hidden');
            updateDisplay();
            renderChoices();
            startTraining();
        }


        // Render problem grid
        function renderProblemGrid() {
            const grid = document.getElementById('problemGrid');
            grid.innerHTML = '';
            
            problems.forEach((problem, index) => {
                const card = document.createElement('div');
                card.className = 'problem-card';
                
                // Determine status
                let status = 'unanswered';
                let statusText = '미입력';
                
                if (userAnswers[problem.id]) {
                    status = 'correct';
                    statusText = '완료';
                }
                
                if (index === currentProblemIndex && isRunning) {
                    status = 'current';
                    statusText = '진행중';
                }
                
                // Check if this problem is in pause selection mode
                const isPauseMode = !isRunning && attemptedProblems.size > 0;
                const isRemaining = !userAnswers[problem.id];
                const isPauseSelected = pauseSelectedOrder.includes(index);
                
                // Add selection functionality in pause mode for remaining problems
                if (isPauseMode && isRemaining) {
                    card.classList.add('pause-selectable');
                    if (isPauseSelected) {
                        card.classList.add('pause-selected');
                    }
                    card.onclick = () => togglePauseSelection(index);
                }
                
                // Get order position or pause selection position
                let orderText = '';
                if (isPauseMode && isRemaining) {
                    if (isPauseSelected) {
                        const pauseOrderIndex = pauseSelectedOrder.indexOf(index);
                        orderText = `선택: ${pauseOrderIndex + 1}`;
                    } else {
                        orderText = '미선택';
                    }
                } else {
                    const orderIndex = originalOrder.indexOf(index);
                    orderText = orderIndex !== -1 ? `순서: ${orderIndex + 1}` : '랜덤';
                }
                
                card.innerHTML = `
                    <div class="problem-card-header">
                        <div class="problem-card-number">문제 ${problem.id}</div>
                        <div class="problem-status status-${status}">${statusText}</div>
                    </div>
                    <div class="problem-card-question" style="padding: 0.75rem; background: #ffffff; border-radius: 0.375rem;">${problem.imgSrc ? `<img src="${problem.imgSrc}" style="max-width: 100%; height: 50px; object-fit: contain; border-radius: 0.25rem; background: transparent; transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: zoom-in;" onmouseover="this.style.transform='scale(1.8)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'; this.style.zIndex='20'; this.style.position='relative';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'; this.style.zIndex='auto'; this.style.position='static';">` : `문제 ${problem.id}`}</div>
                    <div class="problem-card-details">
                        <div class="problem-order">${orderText}</div>
                        <div style="font-size: 0.625rem;">&nbsp;</div>
                    </div>
                `;
                
                grid.appendChild(card);
            });
        }

        // Render choices - removed since no answer selection needed
        function renderChoices() {
            // Empty function - no choices needed for self-learning
        }

        // Play/Pause control
        function togglePlayPause() {
            if (isRunning) {
                pauseTraining();
            } else {
                startTraining();
            }
        }

        function startTraining() {
            isRunning = true;
            document.getElementById('orderBtn').disabled = true;
            document.getElementById('playIcon').textContent = '❚❚';
            document.getElementById('playText').textContent = '일시정지';
            
            // Reset pause order state when starting training
            resetPauseOrderState();
            
            // Lock body scroll and activate training mode
            document.body.classList.add('training-mode');
            
            // Hide problem overview and show problem area
            document.getElementById('problemOverview').classList.add('hidden');
            document.getElementById('problemArea').classList.remove('hidden');
            
            // Switch to fullscreen mode with split layout
            document.getElementById('normalMode').style.display = 'none';
            document.getElementById('fullscreenMode').classList.remove('hidden');
            
            // Activate split layout
            const problemArea = document.getElementById('fullscreenProblemArea');
            problemArea.classList.add('split-layout');
            
            // Show whiteboard section and update iframe src
            document.getElementById('whiteboardSection').style.display = 'flex';
            updateWhiteboardSrc();
            
            renderChoices();
            startTimer();
        }

        function pauseTraining() {
            isRunning = false;
            document.getElementById('orderBtn').disabled = false;
            document.getElementById('playIcon').textContent = '▶';
            
            // Check if any problems have been attempted to show "계속하기" instead of "시작하기"
            const hasStarted = attemptedProblems.size > 0 || totalTime > 0;
            document.getElementById('playText').textContent = hasStarted ? '계속하기' : '시작하기';
            
            // Unlock body scroll and deactivate training mode
            document.body.classList.remove('training-mode');
            
            // Show problem overview and hide problem area
            document.getElementById('problemOverview').classList.remove('hidden');
            document.getElementById('problemArea').classList.add('hidden');
            
            // Deactivate split layout
            const problemArea = document.getElementById('fullscreenProblemArea');
            problemArea.classList.remove('split-layout');
            
            // Hide whiteboard section
            document.getElementById('whiteboardSection').style.display = 'none';
            
            // Switch to normal mode
            document.getElementById('normalMode').style.display = 'block';
            document.getElementById('fullscreenMode').classList.add('hidden');
            
            // Setup pause order selection if training has started
            if (hasStarted) {
                setupPauseOrderSelection();
            }
            
            // Update problem grid to show current status
            renderProblemsByMode();
            renderChoices();
            stopTimer();
        }
        
        // Setup pause order selection mode
        function setupPauseOrderSelection() {
            // Get remaining problems
            remainingProblems = problemOrder.filter(index => !userAnswers[problems[index].id]);
            pauseSelectedOrder = [];
            
            // Show pause order selection UI
            document.getElementById('pauseOrderSelection').classList.remove('hidden');
            document.getElementById('problemOverviewTitle').textContent = '남은 문항 순서 선택';
            
            // Update counters
            updatePauseOrderCounters();
        }
        
        // Toggle pause selection
        function togglePauseSelection(index) {
            if (pauseSelectedOrder.includes(index)) {
                pauseSelectedOrder = pauseSelectedOrder.filter(i => i !== index);
            } else {
                pauseSelectedOrder.push(index);
            }
            updatePauseOrderCounters();
            renderProblemGrid();
        }
        
        // Update pause order counters
        function updatePauseOrderCounters() {
            document.getElementById('pauseSelectedCount').textContent = pauseSelectedOrder.length;
            document.getElementById('pauseRemainingCount').textContent = remainingProblems.length;
        }
        
        // Confirm pause order selection
        function confirmPauseOrder() {
            // Get unselected remaining problems
            const unselected = remainingProblems.filter(i => !pauseSelectedOrder.includes(i));
            
            // Create new order: selected first, then unselected (shuffled)
            const shuffledUnselected = unselected.sort(() => Math.random() - 0.5);
            problemOrder = [...pauseSelectedOrder, ...shuffledUnselected];
            
            // Update current problem to first in new order if current is completed
            if (userAnswers[problems[currentProblemIndex].id] && problemOrder.length > 0) {
                currentProblemIndex = problemOrder[0];
            }
            
            // Reset pause selection state
            resetPauseOrderState();
            
            // Update display
            updateDisplay();
            renderChoices();
            renderProblemsByMode();
            
            // Show success message and auto-resume training after a short delay
            showOrderConfirmationMessage();
        }
        
        // Show order confirmation message and auto-resume
        function showOrderConfirmationMessage() {
            // Update title to show confirmation
            document.getElementById('problemOverviewTitle').textContent = '순서가 적용되었습니다';
            
            // Add confirmation message
            const confirmationMsg = document.createElement('div');
            confirmationMsg.id = 'orderConfirmationMsg';
            confirmationMsg.style.cssText = `
                text-align: center;
                padding: 1rem;
                background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                border-radius: 0.5rem;
                margin-bottom: 2rem;
                color: #065f46;
                font-weight: 500;
            `;
            confirmationMsg.innerHTML = `
                <div style="font-size: 1.125rem; margin-bottom: 0.5rem;">✅ 순서가 성공적으로 적용되었습니다</div>
                <div style="font-size: 0.875rem;">3초 후 자동으로 훈련이 재개됩니다...</div>
            `;
            
            const overview = document.getElementById('problemOverview');
            overview.insertBefore(confirmationMsg, overview.firstChild.nextSibling);
            
            // Countdown and auto-resume
            let countdown = 3;
            const countdownInterval = setInterval(() => {
                countdown--;
                const countdownEl = confirmationMsg.querySelector('div:last-child');
                if (countdown > 0) {
                    countdownEl.textContent = `${countdown}초 후 자동으로 훈련이 재개됩니다...`;
                } else {
                    clearInterval(countdownInterval);
                    confirmationMsg.remove();
                    document.getElementById('problemOverviewTitle').textContent = '문제 목록';
                    startTraining();
                }
            }, 1000);
        }
        
        // Reset pause order selection
        function resetPauseOrder() {
            pauseSelectedOrder = [];
            updatePauseOrderCounters();
            renderProblemGrid();
        }
        
        // Reset pause order state
        function resetPauseOrderState() {
            pauseSelectedOrder = [];
            remainingProblems = [];
            document.getElementById('pauseOrderSelection').classList.add('hidden');
            document.getElementById('problemOverviewTitle').textContent = '문제 목록';
        }

        function completeTraining() {
            pauseTraining();
            document.getElementById('problemArea').classList.add('hidden');
            document.getElementById('completionScreen').classList.remove('hidden');
            document.getElementById('finalTime').textContent = formatTime(totalTime);
        }

        // Histogram and analytics functions
        function showHistogram() {
            const modal = document.getElementById('histogramModal');
            modal.style.display = 'block';
            drawHistogram();
            generateRecommendations();
        }

        function closeHistogram() {
            document.getElementById('histogramModal').style.display = 'none';
        }

        function drawHistogram() {
            const ctx = document.getElementById('attemptHistogram').getContext('2d');
            
            // Prepare data for histogram
            const labels = [];
            const data = [];
            const backgroundColors = [];
            
            problems.forEach((problem, index) => {
                const attemptCount = problemAttemptCounts[problem.id] || 0;
                labels.push(`문제 ${problem.id}`);
                data.push(attemptCount);
                
                // Color coding based on attempt count
                if (attemptCount === 0) {
                    backgroundColors.push('rgba(34, 197, 94, 0.6)'); // Green - not attempted
                } else if (attemptCount <= 2) {
                    backgroundColors.push('rgba(59, 130, 246, 0.6)'); // Blue - low attempts
                } else if (attemptCount <= 4) {
                    backgroundColors.push('rgba(251, 146, 60, 0.6)'); // Orange - moderate attempts
                } else {
                    backgroundColors.push('rgba(239, 68, 68, 0.6)'); // Red - high attempts
                }
            });

            // Destroy existing chart if it exists
            if (window.attemptChart) {
                window.attemptChart.destroy();
            }

            // Create new histogram
            window.attemptChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '반복 횟수',
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: backgroundColors.map(color => color.replace('0.6', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            title: {
                                display: true,
                                text: '완료 전 반복 횟수'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: '문항 번호'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const attempts = context.parsed.y;
                                    const status = userAnswers[problems[context.dataIndex].id] ? '완료' : '미완료';
                                    return `반복: ${attempts}회 (${status})`;
                                }
                            }
                        }
                    },
                    onClick: function(event, elements) {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const problemId = problems[index].id;
                            const attempts = problemAttemptCounts[problemId] || 0;
                            if (attempts > 2) {
                                recommendSimilarProblems(problemId, attempts);
                            }
                        }
                    }
                }
            });

            // Update average attempts
            const totalAttempts = Object.values(problemAttemptCounts).reduce((sum, count) => sum + count, 0);
            const avgAttempts = totalAttempts > 0 ? (totalAttempts / Object.keys(problemAttemptCounts).length).toFixed(1) : 0;
            document.getElementById('avgAttempts').textContent = avgAttempts;
        }

        function generateRecommendations() {
            const recommendations = [];
            
            // Find problems with high attempt counts
            problems.forEach((problem, index) => {
                const attempts = problemAttemptCounts[problem.id] || 0;
                if (attempts > 2 && !userAnswers[problem.id]) {
                    recommendations.push({
                        problemId: problem.id,
                        attempts: attempts,
                        difficulty: attempts > 4 ? '높음' : '중간'
                    });
                }
            });

            // Sort by attempt count (descending)
            recommendations.sort((a, b) => b.attempts - a.attempts);

            // Display recommendations
            const container = document.getElementById('recommendedProblems');
            if (recommendations.length === 0) {
                container.innerHTML = '<p>현재 추천할 유사문제가 없습니다. 문제를 더 풀어보세요!</p>';
            } else {
                let html = '<div style="display: flex; flex-direction: column; gap: 10px;">';
                recommendations.slice(0, 5).forEach(rec => {
                    html += `
                        <div style="padding: 10px; background: white; border-radius: 8px; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>문제 ${rec.problemId}</strong>
                                <span style="color: #6b7280; margin-left: 10px;">반복: ${rec.attempts}회</span>
                                <span style="color: ${rec.difficulty === '높음' ? '#ef4444' : '#f59e0b'}; margin-left: 10px;">난이도: ${rec.difficulty}</span>
                            </div>
                            <button onclick="openSimilarQuiz(${rec.problemId})" style="padding: 5px 15px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                유사문제 풀기
                            </button>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            }
        }

        function recommendSimilarProblems(problemId, attempts) {
            const message = `문제 ${problemId}번을 ${attempts}회 반복하셨네요. 유사문제를 풀어보시겠습니까?`;
            if (confirm(message)) {
                openSimilarQuiz(problemId);
            }
        }

        function openSimilarQuiz(problemId) {
            // 추후 실제 유사문제 페이지 URL로 변경
            const similarQuizUrl = `/similar-quiz?problem=${problemId || 'all'}&difficulty=${problemAttemptCounts[problemId] > 4 ? 'high' : 'medium'}`;
            
            // 현재는 가상의 페이지 알림
            alert(`유사문제 퀴즈 페이지로 이동합니다.\n\n[개발 중]\n대상 문제: ${problemId ? '문제 ' + problemId : '전체'}\n추천 난이도: ${problemAttemptCounts[problemId] > 4 ? '높음' : '중간'}\n\n실제 페이지 URL: ${similarQuizUrl}`);
            
            // 실제 구현 시 아래 주석 해제
            // window.open(similarQuizUrl, '_blank');
        }

        // Cycle mode selection
        function setNextCycleMode(mode) {
            nextCycleMode = mode;
            document.querySelectorAll('.cycle-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.mode === mode) {
                    btn.classList.add('active');
                }
            });
        }

        // Order selection modal
        function openOrderModal() {
            if (isRunning) return;
            
            const modal = document.getElementById('orderModal');
            modalViewMode = 'grid'; // Reset to grid mode
            document.getElementById('modalGridBtn').classList.add('active');
            document.getElementById('modalScrollBtn').classList.remove('active');
            
            renderOrderModalContent();
            modal.style.display = 'block';
            updateSelectedCount();
        }
        
        // Set modal view mode
        function setModalViewMode(mode) {
            modalViewMode = mode;
            
            // Update button states
            if (mode === 'grid') {
                document.getElementById('modalGridBtn').classList.add('active');
                document.getElementById('modalScrollBtn').classList.remove('active');
                document.getElementById('orderGrid').style.display = 'grid';
                document.getElementById('orderScroll').style.display = 'none';
            } else {
                document.getElementById('modalGridBtn').classList.remove('active');
                document.getElementById('modalScrollBtn').classList.add('active');
                document.getElementById('orderGrid').style.display = 'none';
                document.getElementById('orderScroll').style.display = 'block';
            }
            
            renderOrderModalContent();
        }
        
        // Render order modal content based on view mode
        function renderOrderModalContent() {
            if (modalViewMode === 'grid') {
                const grid = document.getElementById('orderGrid');
                grid.innerHTML = '';
                
                problems.forEach((problem, index) => {
                    const div = document.createElement('div');
                    div.className = 'order-item';
                    if (selectedOrder.includes(index)) {
                        div.classList.add('selected');
                    }
                    const textColor = currentTheme === 'dark' ? '#d1d5db' : '#6b7280';
                    div.innerHTML = `
                        <div style="font-size: 1.25rem; color: ${textColor}; margin-bottom: 1rem;">
                            ${selectedOrder.includes(index) ? `<span class="order-badge" style="font-size: 1.125rem; padding: 0.25rem 0.75rem;">${selectedOrder.indexOf(index) + 1}</span>` : ''}
                            문제 ${problem.id}
                        </div>
                        <div style="font-weight: 600; font-size: 1.125rem; padding: 1rem; background: #ffffff; border-radius: 0.5rem; border: 1px solid #e5e7eb;">${problem.imgSrc ? `<img src="${problem.imgSrc}" style="max-width: 400px; height: 120px; object-fit: contain; border-radius: 0.25rem; background: transparent; transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: zoom-in;" onmouseover="this.style.transform='scale(1.3)'; this.style.boxShadow='0 12px 30px rgba(0,0,0,0.2)'; this.style.zIndex='30'; this.style.position='relative';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'; this.style.zIndex='auto'; this.style.position='static';">` : `문제 ${problem.id}`}</div>
                    `;
                    div.onclick = () => toggleOrderSelection(index);
                    grid.appendChild(div);
                });
            } else {
                const scroll = document.getElementById('orderScroll');
                scroll.innerHTML = '';
                
                problems.forEach((problem, index) => {
                    const div = document.createElement('div');
                    div.className = 'order-scroll-item';
                    if (selectedOrder.includes(index)) {
                        div.classList.add('selected');
                    }
                    
                    const orderNum = selectedOrder.includes(index) ? selectedOrder.indexOf(index) + 1 : '';
                    const titleColor = currentTheme === 'dark' ? 'white' : '#111827';
                    const textColor = currentTheme === 'dark' ? '#d1d5db' : '#6b7280';
                    
                    div.innerHTML = `
                        <div style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 1.5rem; color: ${titleColor};">문제 ${problem.id}</h3>
                            ${orderNum ? `<span class="order-badge" style="font-size: 1.25rem; padding: 0.5rem 1rem;">${orderNum}</span>` : ''}
                        </div>
                        ${problem.imgSrc ? `<img src="${problem.imgSrc}" alt="문제 ${problem.id}" style="border: 1px solid #e5e7eb; border-radius: 0.5rem;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 12px 30px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">` : `<div style="font-size: 1.25rem; color: ${textColor};">문제 ${problem.id}</div>`}
                    `;
                    div.onclick = () => toggleOrderSelection(index);
                    scroll.appendChild(div);
                });
            }
        }

        function closeOrderModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        function toggleOrderSelection(index) {
            if (selectedOrder.includes(index)) {
                selectedOrder = selectedOrder.filter(i => i !== index);
            } else {
                selectedOrder.push(index);
            }
            renderOrderModalContent(); // Refresh display
            updateSelectedCount();
        }

        function updateSelectedCount() {
            document.getElementById('selectedCount').textContent = selectedOrder.length;
        }

        function confirmOrder() {
            const unselected = problems.map((_, i) => i).filter(i => !selectedOrder.includes(i));
            const shuffled = unselected.sort(() => Math.random() - 0.5);
            
            problemOrder = [...selectedOrder, ...shuffled];
            originalOrder = [...problemOrder];
            currentProblemIndex = problemOrder[0];
            
            closeOrderModal();
            updateDisplay();
            renderChoices();
            renderProblemsByMode();
        }

        // Display update
        function updateDisplay() {
            const completedCount = Object.keys(userAnswers).length;
            const progress = (completedCount / problems.length) * 100;
            
            // Update time
            const timeStr = formatTime(timeRemaining);
            document.getElementById('timeRemaining').textContent = timeStr;
            document.getElementById('fullscreenTime').textContent = timeStr;
            
            // Update progress
            const progressStr = `${completedCount}/${problems.length}`;
            document.getElementById('completedCount').textContent = progressStr;
            document.getElementById('fullscreenProgress').textContent = progressStr;
            
            // Update other stats
            document.getElementById('cycleCount').textContent = cycleCount;
            document.getElementById('totalTime').textContent = formatTime(totalTime);
            document.getElementById('fullscreenTotalTime').textContent = formatTime(totalTime);
            document.getElementById('progressBar').style.width = `${progress}%`;
            
            // Update problem display
            if (problems[currentProblemIndex]) {
                const problem = problems[currentProblemIndex];
                let numberText = `문제 ${problem.id}`;
                let numberHtml = numberText;
                
                if (hasCompletedFirstCycle) {
                    const modeText = nextCycleMode === 'order' ? '순서대로' : 
                                   nextCycleMode === 'number' ? '번호대로' : '랜덤으로';
                    numberHtml += `<span class="cycle-badge">${modeText}</span>`;
                }
                
                document.getElementById('problemNumber').innerHTML = numberHtml;
                
                // Display problem image instead of text
                const problemTextEl = document.getElementById('problemText');
                if (problem.imgSrc) {
                    problemTextEl.innerHTML = `<img src="${problem.imgSrc}" alt="문제 ${problem.id}" style="max-width: 100%; height: auto; border-radius: 0.5rem; background: transparent; transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: zoom-in;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.15)'; this.style.zIndex='10'; this.style.position='relative';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'; this.style.zIndex='auto'; this.style.position='static';">`;
                } else {
                    problemTextEl.textContent = problem.question || `문제 ${problem.id}`;
                }
                
                // Update whiteboard iframe src
                updateWhiteboardSrc();
            }
        }
        
        // Update whiteboard iframe src with current problem's wboardid
        function updateWhiteboardSrc() {
            if (problems[currentProblemIndex]) {
                const problem = problems[currentProblemIndex];
                const iframe = document.getElementById('whiteboardIframe');
                if (iframe && problem.wboardid) {
                    // Only update if src is different to prevent reload
                    const newSrc = `https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id=${problem.wboardid}&contentsid=${problem.contentsid}&studentid=<?php echo $studentid; ?>&test=on`;
                    if (iframe.src !== newSrc) {
                        // Show loading overlay before changing src
                        showLoadingOverlay();
                        
                        // Set up load event listener
                        iframe.onload = function() {
                            // Hide loading overlay when iframe is loaded
                            setTimeout(() => {
                                hideLoadingOverlay();
                                // Ensure iframe is interactive
                                iframe.style.pointerEvents = 'auto';
                                iframe.contentWindow.focus();
                            }, 50); // Very short delay for immediate interaction
                        };
                        
                        // Ensure iframe is ready for interaction
                        iframe.style.pointerEvents = 'none';
                        
                        // Set new src
                        iframe.src = newSrc;
                    }
                }
            }
        }

        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        // Algorithm selection functions
        function cycleAlgorithm() {
            const algorithms = ['random', 'number', 'order'];
            const currentIndex = algorithms.indexOf(currentAlgorithm);
            const nextIndex = (currentIndex + 1) % algorithms.length;
            const nextAlgorithm = algorithms[nextIndex];
            
            selectAlgorithm(nextAlgorithm);
        }

        function selectAlgorithm(algorithm) {
            currentAlgorithm = algorithm;
            
            // Update display text and icon
            const algorithmText = document.getElementById('algorithmText');
            const algorithmIcon = document.getElementById('algorithmIcon');
            
            switch (algorithm) {
                case 'random':
                    algorithmText.textContent = '랜덤';
                    algorithmIcon.textContent = '🎲';
                    break;
                case 'number':
                    algorithmText.textContent = '번호순서';
                    algorithmIcon.textContent = '🔢';
                    break;
                case 'order':
                    algorithmText.textContent = '풀이순서';
                    algorithmIcon.textContent = '📝';
                    break;
            }
            
            // Apply algorithm if training is running
            if (isRunning) {
                applyCurrentAlgorithm();
            }
        }

        function applyCurrentAlgorithm() {
            const unsolvedIndices = problemOrder.filter(index => !userAnswers[problems[index].id]);
            
            let newOrder;
            switch (currentAlgorithm) {
                case 'random':
                    newOrder = [...unsolvedIndices].sort(() => Math.random() - 0.5);
                    break;
                case 'number':
                    newOrder = unsolvedIndices.sort((a, b) => problems[a].id - problems[b].id);
                    break;
                case 'order':
                    newOrder = originalOrder.filter(index => !userAnswers[problems[index].id]);
                    break;
                default:
                    newOrder = unsolvedIndices;
            }
            
            problemOrder = newOrder;
            
            // Move to first problem in new order if current problem is solved
            if (userAnswers[problems[currentProblemIndex].id]) {
                currentProblemIndex = newOrder[0];
                updateDisplay();
                renderChoices();
            }
        }
    </script>
</body>
</html>