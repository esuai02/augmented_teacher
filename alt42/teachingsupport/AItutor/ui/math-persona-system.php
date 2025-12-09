<?php
/**
 * 📚 수학 인지관성 도감 - 게임형 페르소나 정복 시스템
 * 60개의 인지 페르소나를 정복해 나가는 인터페이스
 * 음성 파일: https://mathking.kr/Contents/personas/인지관성 유형분석/{id}.wav
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentId = $_GET['studentid'] ?? $USER->id;
$userId = $USER->id;

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid='22'", [$userId]);
$role = $userrole->data ?? 'student';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo htmlspecialchars($userId); ?>">
    <meta name="student-id" content="<?php echo htmlspecialchars($studentId); ?>">
    <title>📚 수학 인지관성 도감 - 페르소나 정복</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            color: #e0e0e0;
            min-height: 100vh;
        }
        .header {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header h1 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .header-stats { display: flex; gap: 1.5rem; }
        .stat-box {
            text-align: center;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
        }
        .stat-value { font-size: 1.5rem; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 0.75rem; color: #9ca3af; }
        .main-container { display: flex; height: calc(100vh - 80px); }
        .category-sidebar {
            width: 200px;
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }
        .category-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .category-item:hover { background: rgba(255, 255, 255, 0.1); }
        .category-item.active { background: rgba(102, 126, 234, 0.2); border-left-color: #667eea; }
        .category-icon { font-size: 1.25rem; }
        .category-name { font-size: 0.875rem; flex: 1; }
        .category-count {
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.125rem 0.375rem;
            border-radius: 9999px;
        }
        .persona-grid-container { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }
        .filter-btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: transparent;
            color: #9ca3af;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn:hover { background: rgba(102, 126, 234, 0.3); border-color: #667eea; }
        .filter-btn.active { background: #667eea; border-color: #667eea; color: white; }
        .persona-grid {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-content: flex-start;
        }
        .persona-card {
            position: relative;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            border-radius: 1rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 140px;
            height: 170px;
            flex-shrink: 0;
        }
        .persona-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        .persona-card.conquered {
            background: linear-gradient(145deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.05));
            border-color: #10b981;
        }
        .persona-card.conquered::after {
            content: '✓ 정복';
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #10b981;
            color: white;
            font-size: 0.5rem;
            padding: 0.125rem 0.25rem;
            border-radius: 9999px;
            font-weight: 600;
        }
        .persona-icon { font-size: 2rem; margin-bottom: 0.5rem; filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3)); }
        .persona-id { font-size: 0.5625rem; color: #9ca3af; margin-bottom: 0.125rem; }
        .persona-name {
            font-size: 0.6875rem;
            font-weight: 600;
            color: #f3f4f6;
            line-height: 1.3;
            margin-bottom: 0.375rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .persona-category {
            font-size: 0.5625rem;
            padding: 0.125rem 0.375rem;
            border-radius: 9999px;
            background: rgba(102, 126, 234, 0.2);
            color: #a5b4fc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        .persona-priority {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .persona-priority.high { background: #ef4444; }
        .persona-priority.medium { background: #f59e0b; }
        .persona-priority.low { background: #10b981; }

        /* 필터 모드: 카드 확대 (전체 모드 아닐 때) */
        .persona-grid.filtered .persona-card {
            width: 280px;
            height: 340px;
            padding: 1.5rem;
        }
        .persona-grid.filtered .persona-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .persona-grid.filtered .persona-id {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .persona-grid.filtered .persona-name {
            font-size: 1.125rem;
            margin-bottom: 0.75rem;
            -webkit-line-clamp: 3;
        }
        .persona-grid.filtered .persona-category {
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
        }
        .persona-grid.filtered .persona-priority {
            width: 12px;
            height: 12px;
        }
        .detail-panel {
            width: 630px;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            padding: 2rem;
            overflow-y: auto;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            display: none;
            flex-shrink: 0;
        }
        .detail-panel.open { display: block; }
        .detail-header {
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .detail-icon { font-size: 4rem; margin-bottom: 0.75rem; }
        .detail-name { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
        .detail-desc { font-size: 0.875rem; color: #9ca3af; line-height: 1.6; }
        .detail-section { margin-bottom: 1.5rem; }
        .detail-section-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .detail-content {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            line-height: 1.7;
        }
        .audio-player {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.85));
            padding: 1.25rem;
            border-radius: 1rem;
            margin-top: 0.75rem;
            border: 1px solid rgba(0, 245, 255, 0.15);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }
        .audio-player::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(0, 245, 255, 0.5) 20%, rgba(102, 126, 234, 0.8) 50%, rgba(0, 245, 255, 0.5) 80%, transparent 100%);
        }
        .audio-player::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 0%, transparent 50%, rgba(0, 245, 255, 0.02) 100%);
            pointer-events: none;
        }
        .audio-player.playing {
            border-color: rgba(0, 245, 255, 0.3);
            box-shadow: 0 4px 32px rgba(0, 245, 255, 0.15), 0 0 60px rgba(102, 126, 234, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }
        .audio-player.playing::before {
            animation: scan-line 2s linear infinite;
        }
        @keyframes scan-line {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        .audio-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            width: 100%;
        }
        .audio-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(0, 245, 255, 0.9), rgba(102, 126, 234, 0.9));
            border: 2px solid rgba(0, 245, 255, 0.3);
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 20px rgba(0, 245, 255, 0.4), 0 4px 15px rgba(102, 126, 234, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            flex-shrink: 0;
            position: relative;
            z-index: 2;
        }
        .audio-btn::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, transparent, rgba(0, 245, 255, 0.3), transparent, rgba(102, 126, 234, 0.3), transparent);
            opacity: 0;
            transition: opacity 0.3s;
            animation: rotate-glow 3s linear infinite paused;
        }
        .audio-btn:hover::before { opacity: 1; animation-play-state: running; }
        @keyframes rotate-glow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .audio-btn:hover { 
            transform: scale(1.08); 
            box-shadow: 0 0 30px rgba(0, 245, 255, 0.6), 0 6px 20px rgba(102, 126, 234, 0.4); 
            border-color: rgba(0, 245, 255, 0.5);
        }
        .audio-btn.playing { 
            background: linear-gradient(135deg, rgba(244, 114, 182, 0.9), rgba(239, 68, 68, 0.9)); 
            border-color: rgba(244, 114, 182, 0.4);
            box-shadow: 0 0 24px rgba(244, 114, 182, 0.5), 0 4px 15px rgba(239, 68, 68, 0.4);
            animation: pulse-glow 1.5s ease-in-out infinite;
        }
        .audio-btn.playing::before { opacity: 1; animation-play-state: running; }
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 0 24px rgba(244, 114, 182, 0.5), 0 4px 15px rgba(239, 68, 68, 0.4);
                transform: scale(1);
            }
            50% { 
                box-shadow: 0 0 36px rgba(244, 114, 182, 0.7), 0 4px 25px rgba(239, 68, 68, 0.6);
                transform: scale(1.05);
            }
        }
        .audio-time-display {
            font-size: 0.8125rem;
            font-weight: 500;
            color: rgba(0, 245, 255, 0.9);
            min-width: 85px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s, text-shadow 0.3s;
            font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
            letter-spacing: 0.5px;
            text-shadow: 0 0 8px rgba(0, 245, 255, 0.4);
        }
        .audio-time-display.visible { 
            opacity: 1; 
            text-shadow: 0 0 12px rgba(0, 245, 255, 0.6);
        }
        .audio-progress-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            position: relative;
            z-index: 1;
        }
        .audio-progress-bar {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 2px;
            overflow: visible;
            position: relative;
            cursor: pointer;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        .audio-progress-bar::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(90deg, transparent, rgba(0, 245, 255, 0.1), transparent);
            border-radius: 4px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .audio-progress-bar:hover::before { opacity: 1; }
        .audio-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #00f5ff, #667eea, #a855f7, #f472b6);
            background-size: 200% 100%;
            border-radius: 2px;
            transition: width 0.1s linear;
            position: relative;
            box-shadow: 0 0 8px rgba(0, 245, 255, 0.5), 0 0 16px rgba(102, 126, 234, 0.3);
        }
        .audio-player.playing .audio-progress-fill {
            animation: progress-glow 2s ease-in-out infinite;
        }
        @keyframes progress-glow {
            0%, 100% { background-position: 0% 50%; box-shadow: 0 0 8px rgba(0, 245, 255, 0.5), 0 0 16px rgba(102, 126, 234, 0.3); }
            50% { background-position: 100% 50%; box-shadow: 0 0 12px rgba(0, 245, 255, 0.7), 0 0 24px rgba(168, 85, 247, 0.4); }
        }
        .audio-progress-fill::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background: radial-gradient(circle, #fff 30%, #00f5ff 100%);
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(0, 245, 255, 0.9), 0 0 16px rgba(102, 126, 234, 0.6);
            opacity: 0;
            transition: opacity 0.2s, transform 0.2s;
        }
        .audio-progress-bar:hover .audio-progress-fill::after { 
            opacity: 1; 
            transform: translateY(-50%) scale(1.1);
        }
        .ai-visualizer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2px;
            height: 48px;
            width: 100%;
            position: relative;
            padding: 8px 0;
        }
        .ai-visualizer::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(102, 126, 234, 0.15) 0%, transparent 70%);
            filter: blur(8px);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .ai-visualizer.playing::before { opacity: 1; }
        .ai-bar {
            width: 3px;
            height: 6px;
            background: linear-gradient(180deg, #00f5ff, #667eea, #a855f7);
            border-radius: 2px;
            transition: height 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 0 4px rgba(102, 126, 234, 0.3);
        }
        .ai-visualizer.playing .ai-bar {
            box-shadow: 0 0 8px rgba(0, 245, 255, 0.6), 0 0 16px rgba(102, 126, 234, 0.4);
        }
        /* 대칭적 웨이브 - 중앙이 가장 높고 양쪽으로 퍼져나감 */
        .ai-visualizer.playing .ai-bar:nth-child(1) { animation: ai-wave-outer 0.8s ease-in-out infinite 0.35s; }
        .ai-visualizer.playing .ai-bar:nth-child(2) { animation: ai-wave-mid 0.7s ease-in-out infinite 0.3s; }
        .ai-visualizer.playing .ai-bar:nth-child(3) { animation: ai-wave-outer 0.9s ease-in-out infinite 0.25s; }
        .ai-visualizer.playing .ai-bar:nth-child(4) { animation: ai-wave-mid 0.6s ease-in-out infinite 0.2s; }
        .ai-visualizer.playing .ai-bar:nth-child(5) { animation: ai-wave-inner 0.8s ease-in-out infinite 0.15s; }
        .ai-visualizer.playing .ai-bar:nth-child(6) { animation: ai-wave-mid 0.7s ease-in-out infinite 0.1s; }
        .ai-visualizer.playing .ai-bar:nth-child(7) { animation: ai-wave-inner 0.65s ease-in-out infinite 0.08s; }
        .ai-visualizer.playing .ai-bar:nth-child(8) { animation: ai-wave-peak 0.55s ease-in-out infinite 0.05s; }
        .ai-visualizer.playing .ai-bar:nth-child(9) { animation: ai-wave-inner 0.6s ease-in-out infinite 0.03s; }
        .ai-visualizer.playing .ai-bar:nth-child(10) { animation: ai-wave-peak 0.5s ease-in-out infinite 0s; }
        .ai-visualizer.playing .ai-bar:nth-child(11) { animation: ai-wave-center 0.45s ease-in-out infinite 0s; }
        .ai-visualizer.playing .ai-bar:nth-child(12) { animation: ai-wave-peak 0.5s ease-in-out infinite 0s; }
        .ai-visualizer.playing .ai-bar:nth-child(13) { animation: ai-wave-inner 0.6s ease-in-out infinite 0.03s; }
        .ai-visualizer.playing .ai-bar:nth-child(14) { animation: ai-wave-peak 0.55s ease-in-out infinite 0.05s; }
        .ai-visualizer.playing .ai-bar:nth-child(15) { animation: ai-wave-inner 0.65s ease-in-out infinite 0.08s; }
        .ai-visualizer.playing .ai-bar:nth-child(16) { animation: ai-wave-mid 0.7s ease-in-out infinite 0.1s; }
        .ai-visualizer.playing .ai-bar:nth-child(17) { animation: ai-wave-inner 0.8s ease-in-out infinite 0.15s; }
        .ai-visualizer.playing .ai-bar:nth-child(18) { animation: ai-wave-mid 0.6s ease-in-out infinite 0.2s; }
        .ai-visualizer.playing .ai-bar:nth-child(19) { animation: ai-wave-outer 0.9s ease-in-out infinite 0.25s; }
        .ai-visualizer.playing .ai-bar:nth-child(20) { animation: ai-wave-mid 0.7s ease-in-out infinite 0.3s; }
        .ai-visualizer.playing .ai-bar:nth-child(21) { animation: ai-wave-outer 0.8s ease-in-out infinite 0.35s; }
        @keyframes ai-wave-center {
            0%, 100% { height: 12px; opacity: 0.7; background: linear-gradient(180deg, #00f5ff, #667eea); }
            50% { height: 44px; opacity: 1; background: linear-gradient(180deg, #00f5ff, #a855f7, #f472b6); }
        }
        @keyframes ai-wave-peak {
            0%, 100% { height: 10px; opacity: 0.6; }
            50% { height: 38px; opacity: 1; }
        }
        @keyframes ai-wave-inner {
            0%, 100% { height: 8px; opacity: 0.5; }
            50% { height: 32px; opacity: 0.95; }
        }
        @keyframes ai-wave-mid {
            0%, 100% { height: 6px; opacity: 0.4; }
            50% { height: 24px; opacity: 0.85; }
        }
        @keyframes ai-wave-outer {
            0%, 100% { height: 4px; opacity: 0.3; }
            50% { height: 16px; opacity: 0.7; }
        }
        /* 글로우 펄스 효과 */
        .ai-visualizer.playing .ai-bar:nth-child(11) {
            background: linear-gradient(180deg, #00f5ff, #667eea, #f472b6);
            box-shadow: 0 0 12px rgba(0, 245, 255, 0.8), 0 0 24px rgba(102, 126, 234, 0.5);
        }
        .audio-error-msg { 
            color: #ef4444; 
            font-size: 0.75rem; 
            text-align: center;
            padding: 0.25rem;
        }
        audio { display: none; }
        .conquer-btn {
            width: 100%;
            padding: 1rem;
            margin-top: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 0.5rem;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .conquer-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4); }
        .conquer-btn.conquered { background: #10b981; cursor: default; }
        .progress-section {
            padding: 1rem 2rem;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .progress-bar { height: 8px; background: rgba(255, 255, 255, 0.1); border-radius: 4px; overflow: hidden; }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2, #10b981);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .progress-text { display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.75rem; color: #9ca3af; }
        @keyframes conquerPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .conquered-animation { animation: conquerPulse 0.5s ease; }
        @media (max-width: 1024px) {
            .category-sidebar { width: 60px; }
            .category-name, .category-count { display: none; }
            .detail-panel { width: 100%; position: fixed; top: 0; right: 0; bottom: 0; z-index: 100; }
        }
        
        /* 풀이 단계별 보기 스타일 */
        .stage-view-container {
            display: none;
            flex-direction: column;
            gap: 2rem;
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        .stage-view-container.active {
            display: flex;
        }
        .stage-section {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .stage-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .stage-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        .stage-icon.stage-1 { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stage-icon.stage-2 { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .stage-icon.stage-3 { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stage-icon.stage-4 { background: linear-gradient(135deg, #10b981, #059669); }
        .stage-icon.stage-5 { background: linear-gradient(135deg, #ec4899, #be185d); }
        .stage-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #f3f4f6;
        }
        .stage-subtitle {
            font-size: 0.875rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }
        .stage-count {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            color: #e5e7eb;
        }
        .stage-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .stage-cards .persona-card {
            width: 130px;
            height: 160px;
        }
        .stage-persona-badge {
            position: absolute;
            top: 0.375rem;
            right: 0.375rem;
            font-size: 0.5rem;
            padding: 0.125rem 0.25rem;
            border-radius: 4px;
            font-weight: 600;
            color: white;
        }
        .stage-persona-badge.stage-1 { background: #3b82f6; }
        .stage-persona-badge.stage-2 { background: #8b5cf6; }
        .stage-persona-badge.stage-3 { background: #f59e0b; }
        .stage-persona-badge.stage-4 { background: #10b981; }
        .stage-persona-badge.stage-5 { background: #ec4899; }
    </style>
</head>
<body>
    <header class="header">
        <div style="display:flex;align-items:center;gap:1rem;">
            <h1>📚 수학 인지관성 도감</h1>
            <button id="recommendOrderBtn" style="background:linear-gradient(135deg,#f59e0b,#ea580c);padding:0.5rem 1rem;border-radius:0.5rem;color:white;border:none;cursor:pointer;font-size:0.875rem;display:flex;align-items:center;gap:0.5rem;">🎯 추천 순서</button>
            <button id="stageViewBtn" style="background:linear-gradient(135deg,#06b6d4,#0891b2);padding:0.5rem 1rem;border-radius:0.5rem;color:white;border:none;cursor:pointer;font-size:0.875rem;display:flex;align-items:center;gap:0.5rem;">📊 풀이 단계별 페르소나 보기</button>
            <a href="persona-conquest-map.php" style="background:linear-gradient(135deg,#667eea,#764ba2);padding:0.5rem 1rem;border-radius:0.5rem;color:white;text-decoration:none;font-size:0.875rem;display:flex;align-items:center;gap:0.5rem;">✨ 9가지 전략</a>
        </div>
        <div class="header-stats">
            <div class="stat-box"><div class="stat-value" id="conqueredCount">0</div><div class="stat-label">정복 완료</div></div>
            <div class="stat-box"><div class="stat-value" id="totalCount">60</div><div class="stat-label">전체</div></div>
            <div class="stat-box"><div class="stat-value" id="streakCount">0</div><div class="stat-label">연속 정복</div></div>
        </div>
    </header>
    <div class="main-container">
        <aside class="category-sidebar">
            <div class="category-item active" data-category="all">
                <span class="category-icon">🌟</span>
                <span class="category-name">전체</span>
                <span class="category-count">60</span>
            </div>
            <div class="category-item" data-category="인지 과부하"><span class="category-icon">🧠</span><span class="category-name">인지 과부하</span></div>
            <div class="category-item" data-category="자신감 왜곡"><span class="category-icon">😰</span><span class="category-name">자신감 왜곡</span></div>
            <div class="category-item" data-category="실수 패턴"><span class="category-icon">⚡</span><span class="category-name">실수 패턴</span></div>
            <div class="category-item" data-category="접근 전략 오류"><span class="category-icon">🎯</span><span class="category-name">접근 전략 오류</span></div>
            <div class="category-item" data-category="학습 습관"><span class="category-icon">📚</span><span class="category-name">학습 습관</span></div>
            <div class="category-item" data-category="시간/압박 관리"><span class="category-icon">⏰</span><span class="category-name">시간/압박 관리</span></div>
            <div class="category-item" data-category="검증/확인 부재"><span class="category-icon">✔️</span><span class="category-name">검증/확인 부재</span></div>
            <div class="category-item" data-category="기타 장애"><span class="category-icon">🔧</span><span class="category-name">기타 장애</span></div>
        </aside>
        <main class="persona-grid-container">
            <div class="filter-buttons" id="filterButtons">
                <button class="filter-btn active" data-filter="all">전체</button>
                <button class="filter-btn" data-filter="conquered">정복 완료</button>
                <button class="filter-btn" data-filter="remaining">미정복</button>
                <button class="filter-btn" data-filter="high">긴급</button>
            </div>
            <div class="persona-grid" id="personaGrid"></div>
            <div class="stage-view-container" id="stageViewContainer"></div>
        </main>
        <aside class="detail-panel" id="detailPanel">
            <div class="detail-header">
                <div class="detail-icon" id="detailIcon">🧠</div>
                <div class="detail-name" id="detailName">페르소나 이름</div>
                <div class="detail-desc" id="detailDesc">설명</div>
            </div>
            <div class="detail-section">
                <div class="detail-section-title">🎯 해결 전략</div>
                <div class="detail-content" id="detailAction"></div>
            </div>
            <div class="detail-section">
                <div class="detail-section-title">✅ 확인 포인트</div>
                <div class="detail-content" id="detailCheck"></div>
            </div>
            <div class="detail-section">
                <div class="detail-section-title">💬 선생님께 이렇게 말해보세요</div>
                <div class="detail-content" id="detailTeacher"></div>
            </div>
            <div class="detail-section">
                <div class="detail-section-title">🔊 이 페르소나 정복하는 방법</div>
                <div class="audio-player" id="audioPlayer">
                    <div class="ai-visualizer" id="aiVisualizer">
                        <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                        <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                        <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                        <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                        <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                        <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                        <div class="ai-bar"></div><div class="ai-bar"></div><div class="ai-bar"></div>
                    </div>
                    <div class="audio-progress-container">
                        <div class="audio-progress-bar" id="audioProgressBar">
                            <div class="audio-progress-fill" id="audioProgressFill"></div>
                        </div>
                    </div>
                    <div class="audio-controls">
                        <div class="audio-time-display" id="audioTimeDisplay">0:00 / 0:00</div>
                        <button class="audio-btn" id="audioPlayBtn" onclick="toggleAudio()">▶</button>
                        <div class="audio-time-display" id="audioTimeRemaining"></div>
                    </div>
                    <div class="audio-error-msg" id="audioError" style="display:none;"></div>
                </div>
                <audio id="audioElement"></audio>
            </div>
            <button class="conquer-btn" id="conquerBtn">🏆 이 패턴 정복하기</button>
        </aside>
    </div>
    <div class="progress-section">
        <div class="progress-bar"><div class="progress-fill" id="progressFill" style="width: 0%"></div></div>
        <div class="progress-text"><span>정복 진행률</span><span id="progressPercent">0%</span></div>
    </div>
    <script>
    // 60개 페르소나 데이터 (60personas.txt 기반)
    const personas = [
        {id:1,name:"아이디어 해방 자동발화형",desc:"번쩍이는 아이디어가 떠오르면 검증 없이 바로 써 내려가 결국 오답을 양산하는 패턴.",category:"인지 과부하",icon:"🧠",priority:"high",audioTime:"2:15",solution:{action:"아이디어가 떠오르면 5초 멈춤 → 아이디어를 한 줄로 요약 후, '약점 가설' 1개를 곧바로 적는다 → 문제 지문을 다시 읽고, 가설과 비교한다",check:"5초 멈춤→가설 쓰기 루틴을 세 번 성공했는지 확인. 요약이 적절했는지 짧게 피드백",teacherDialog:"선생님, 오늘 '5초 멈춤→가설 쓰기' 루틴을 세 번 성공했어요. 제 요약이 적절했는지 짧게 피드백 부탁드립니다!"}},
        {id:2,name:"3초 패배 예감형",desc:"'못 풀 것 같다'는 느낌이 3초 만에 뇌를 잠그고, 관련 개념 연결이 끊어지는 패턴.",category:"자신감 왜곡",icon:"😰",priority:"high",audioTime:"1:45",solution:{action:"'포기 신호'를 감지하면 3분 타이머를 켜고 문제 해석을 처음부터 다시 적는다 → 막힌 부분을 눈으로 3분간 응시하며 조건·단어를 재색인한다",check:"'3분 재해석' 루틴을 두 번 사용했는지, 다시 읽은 메모에서 놓친 단어가 있었는지 검토",teacherDialog:"저는 오늘 '3분 재해석' 루틴을 두 번 썼습니다. 다시 읽은 메모에서 놓친 단어가 있었는지 검토해 주실 수 있나요?"}},
        {id:3,name:"과신-시야 협착형",desc:"과한 자신감으로 숫자·기호의 미세한 차이를 인식하지 못하는 패턴.",category:"자신감 왜곡",icon:"🎯",priority:"medium",audioTime:"2:30",solution:{action:"풀이 착수 전 심호흡 10회 → 비슷한 기호·수치를 색펜으로 구분 표시 → 계산 단계마다 '작은 차이 체크' 칸에 ✔︎",check:"색펜 표시한 부분을 같이 보며, 놓친 차이가 있었는지 확인",teacherDialog:"색펜 표시한 부분을 같이 보며, 제가 놓친 차이가 있었는지 알려주시면 감사하겠습니다."}},
        {id:4,name:"무의식 연쇄 실수형",desc:"손이 먼저 움직여 사소한 계산 실수가 꼬리를 무는 패턴.",category:"실수 패턴",icon:"⚡",priority:"high",audioTime:"1:55",solution:{action:"숫자 한 줄 쓸 때마다 펜을 내려놓고 1초 휴식 → 매일 풀이 후 '실수 장면' 1개 기록 → 다음 날 첫 학습 전에 그 기록을 재확인",check:"어제 적은 실수 장면을 보여드릴 때, 비슷한 실수를 막는 팁 제공",teacherDialog:"어제 적은 실수 장면을 보여드릴게요. 비슷한 실수를 막는 팁이 더 있을까요?"}},
        {id:5,name:"모순 확신-답불가형",desc:"'틀린 곳이 없다'는 집착으로 시야가 좁아져 교정을 못 하는 패턴.",category:"자신감 왜곡",icon:"🔒",priority:"medium",audioTime:"2:10",solution:{action:"답이 안 나올 때 '간단 실수 90%' 문장을 써서 관점을 전환 → 풀이를 거꾸로 읽으며 '사소한 실수 찾기' 게임화 → 한 번은 다른 색 펜으로 다시 써보기",check:"'간단 실수 게임'으로 찾은 오류를 검산, 또 다른 시야 전환 방법 제안",teacherDialog:"제가 '간단 실수 게임'으로 찾은 오류를 검산해 주실 수 있나요? 또 다른 시야 전환 방법이 있다면 알려주세요."}},
        {id:6,name:"작업기억 ⅔ 할당형",desc:"다음 일정·잡생각이 머릿속을 스치며 2/3만 집중하는 패턴.",category:"인지 과부하",icon:"🧩",priority:"high",audioTime:"2:25",solution:{action:"떠오른 일정은 포스트잇에 적고 덮어두기 → 25분 집중 / 5분 휴식 Pomodoro 타이머 사용 → 휴식 때만 메모 확인·업데이트",check:"25분 집중 세션 3번 돌렸는지, 중간에 잡생각 메모를 몇 번 했는지 확인",teacherDialog:"25분 집중 세션 3번 돌렸는데, 중간에 잡생각 메모를 몇 번 했는지 확인해 주실 수 있나요?"}},
        {id:7,name:"반(半)포기 창의 탐색형",desc:"'어차피 틀릴 것'이라며 낮은 확률의 창의 풀이만 헤매는 패턴.",category:"접근 전략 오류",icon:"🎨",priority:"medium",audioTime:"2:40",solution:{action:"정석 접근 A안을 먼저 10분 시도 → 실패 시 A안 문제점 1줄 정리 → B안 스케치 → B안도 막히면 과감히 답안·해설 구조 분석",check:"A안 10분, B안 5분 전략으로 풀어봤는지, A안 분석이 적절했는지 확인",teacherDialog:"오늘 A안 10분, B안 5분 전략으로 풀어봤어요. 제 A안 분석이 적절했는지 봐주실래요?"}},
        {id:8,name:"해설지-혼합 착각형",desc:"내 생각과 해설 내용을 섞어 쓰다 근거가 뒤섞이는 패턴.",category:"학습 습관",icon:"📖",priority:"medium",audioTime:"2:05",solution:{action:"내 풀이=파란색, 해설=빨간색 두 색깔 분리 기록 → 해설을 읽을 때 '왜 다른가?' 차이 2개 메모 → 하루 뒤, 파란·빨간 노트를 다시 읽어 통합 정리",check:"파란·빨간 차이 두 가지를 설명드릴 때, 해설 흡수 과정 피드백",teacherDialog:"파란·빨간 차이 두 가지를 설명드릴게요. 제 해설 흡수 과정이 괜찮은지 피드백 부탁드립니다."}},
        {id:9,name:"연습 회피 관성형",desc:"'이해했어' 착각으로 반복 연습을 건너뛰고 넘어가는 패턴.",category:"학습 습관",icon:"🏃",priority:"high",audioTime:"1:35",solution:{action:"새 개념 배우면 즉시 난이도 Low·Mid·High 1문제씩 풀기 → Low / Mid 틀리면 해당 개념 '불완전'로 표시 후 재학습 → 주간 체크리스트: 개념당 최소 3회 재방문",check:"Low·Mid·High 3문제 중 어떤 것을 틀렸는지, 어떤 부분을 더 연습해야 할지 조언",teacherDialog:"Low·Mid·High 3문제 중 Mid를 틀렸어요. 어떤 부분을 더 연습해야 할까요?"}},
        {id:10,name:"불확실 강행형",desc:"근거 부족인데도 '일단 적용'해서 오류가 연쇄되는 패턴.",category:"접근 전략 오류",icon:"🎲",priority:"medium",audioTime:"2:20",solution:{action:"근거 약하면 노란 포스트잇에 '확신 ★☆☆' 등급 표시 → 별 1‧2개인 줄은 풀이 끝에 재검산 표시(✔︎) → 검산 단계에서 ★ 1‧2 지점 우선 점검",check:"노란 포스트잇으로 ★ 표시한 부분을 같이 검산, 다른 '확신 체크' 방법 제안",teacherDialog:"노란 포스트잇으로 ★ 표시한 부분을 같이 검산해 주시면 좋겠습니다. 다른 '확신 체크' 방법이 있을까요?"}},
        {id:11,name:"속도 압박 억제형",desc:"시험 시간이 눈에 들어올 때마다 '빨리 해야 한다'는 압박이 새 아이디어와 기억을 눌러 버리는 패턴.",category:"시간/압박 관리",icon:"⏰",priority:"high",audioTime:"1:50",solution:{action:"시작과 동시에 손목시계·휴대폰 시계 뒤집기 → 조용 타이머를 15분 간격으로 설정(삐 소리 X, 진동 O) → 타이머 울릴 때마다 현재 문제를 1문장으로 요약 후 진행 여부 판단",check:"15분 타이머를 4번 돌렸는지, 진동이 왔을 때 요약이 적절했는지 확인",teacherDialog:"15분 타이머를 4번 돌렸는데 진동이 왔을 때 제 요약이 적절했는지, 한 번만 확인 부탁드려요."}},
        {id:12,name:"시험 트라우마 악수형",desc:"과거에 시험을 망친 기억이 문제 순서·전략에 투영돼 '악수'를 두는 패턴.",category:"시간/압박 관리",icon:"💔",priority:"high",audioTime:"2:35",solution:{action:"시작 2분 내에 '가장 쉬운 2문제'를 골라 먼저 해결 → 성공감이 생기면 그다음 문제를 난도별 라벨링(L·M·H) 후 착수 → 45분 세션 후 성공 → 어려움 순서를 다시 리뷰",check:"Easy-Start 전략으로 첫 2문제를 풀었는지, 난이도 라벨이 정확했는지 피드백",teacherDialog:"Easy-Start 전략으로 첫 2문제를 풀었어요. 제 난이도 라벨이 정확했는지 피드백 부탁드립니다."}},
        {id:13,name:"징검다리 난도적형",desc:"청킹 없이 산발적으로 추론해 전역 구조를 놓치는 패턴.",category:"접근 전략 오류",icon:"🪨",priority:"medium",audioTime:"2:45",solution:{action:"문제를 3~4개 '청크'로 나누고 각 단계에 번호(①②③…) 붙이기 → 단계 끝마다 '다음 단계 조건'을 한 줄 메모 → 최종 답 후 번호 순서를 거꾸로 점검(③→②→①)",check:"청크 3단계를 거꾸로 리뷰했는지, 연결 고리가 자연스러운지 확인",teacherDialog:"청크 3단계를 거꾸로 리뷰했습니다. 제 연결 고리가 자연스러운지 봐주실 수 있나요?"}},
        {id:14,name:"무의식 재현 루프형",desc:"예전에 성공했던 공식을 맹목적으로 재사용하며 문제 특성을 무시하는 패턴.",category:"학습 습관",icon:"🔄",priority:"low",audioTime:"2:15",solution:{action:"공식 사용할 때 '조건 동일?' 체크박스를 옆에 그리기 → 조건이 다르면 즉시 다른 방법(그래프, 역함수, 대수 등) 후보를 메모 → 학습 후 '조건 불일치 발견 목록'을 주간 로그에 기록",check:"오늘 조건 체크박스를 5번 그렸는데, 2번은 불일치였다면 다른 대안이 적절했는지 검토",teacherDialog:"오늘 조건 체크박스를 5번 그렸는데, 2번은 불일치였습니다. 다른 대안이 적절했는지 검토 부탁드립니다."}},
        {id:15,name:"조건 회피-추론 생략형",desc:"복잡한 조건을 '시야 밖'으로 밀어두고 직감만으로 추론을 강행하는 패턴.",category:"검증/확인 부재",icon:"👁️",priority:"high",audioTime:"1:40",solution:{action:"문제의 각 조건 옆에 ✔︎를 표시하고 한글로 5-7단어 요약 작성 → 풀이 중 조건을 사용할 때마다 ✔︎ 색깔을 검정 → 초록으로 변경 → 남은 검정 ✔︎가 있으면 풀이 완료 전 반드시 조건 재적용",check:"초록으로 바뀌지 않은 조건이 하나 남았는지, 어디에 반영해야 할지 조언",teacherDialog:"초록으로 바뀌지 않은 조건이 하나 남았는데, 어디에 반영해야 할지 조언 부탁드려요."}},
        {id:16,name:"확률적 답안 던지기형",desc:"근거가 부족한데도 '일단 찍어보자' 식으로 답을 기입해 오류가 연쇄되는 패턴.",category:"접근 전략 오류",icon:"🎯",priority:"medium",audioTime:"1:55",solution:{action:"근거가 약할 때는 노란 포스트잇에 '확신 ★☆☆' 등급 표시 → ★ 1·2개가 붙은 줄은 풀이 끝에 재검산(역대입, 단위 확인 등) 필수 → 최종 제출 전, ★ 표시가 있는 줄만 모아서 1분 스피드 셀프 퀴즈",check:"★ 표시를 붙인 줄을 모아 1분 퀴즈를 했는지, 재검 과정이 충분했는지 확인",teacherDialog:"★ 표시를 붙인 줄을 모아 1분 퀴즈를 했습니다. 재검 과정이 충분했는지 확인해 주실 수 있나요?"}},
        {id:17,name:"방심 단기 기억 증발형",desc:"잠깐 산만해지면서 방금 세운 관계식이나 조건을 잊어버리는 패턴.",category:"기타 장애",icon:"💭",priority:"low",audioTime:"1:45",solution:{action:"새 식·조건을 세울 때마다 왼쪽 여백에 번호 목록으로 기록 → 산만함을 느끼면 즉시 목록을 큰 소리로 1줄 복창 → 풀이 종료 후 목록과 실제 풀이를 체크‧매칭",check:"목록에 적은 5개의 식을 복창했는지, 연결이 부자연스러운 부분이 있는지 확인",teacherDialog:"목록에 적은 5개의 식을 복창했는데, 연결이 부자연스러운 부분이 있는지 봐주실래요?"}},
        {id:18,name:"도구 의존 과적형",desc:"CAS·계산기에 과도하게 의존해 개념 이해·추론 회로가 비활성화되는 패턴.",category:"기타 장애",icon:"🔧",priority:"low",audioTime:"2:30",solution:{action:"CAS 입력 전에 예상 결과 범위(↑↓)·부호·대략 값을 손으로 스케치 → 계산 결과가 나오면 예상 vs 결과를 3초 비교해 차이를 표시 → 차이가 크면 계산 단계나 모델링 방식을 수작업으로 한 번 더 검산",check:"예상한 범위와 CAS 결과가 다를 때 어떤 개념을 더 확인해야 할지 조언",teacherDialog:"제가 예상한 범위와 CAS 결과가 다를 때 어떤 개념을 더 확인해야 할지 조언 부탁드립니다."}},
        {id:19,name:"과거 방식 고착형",desc:"새로운 유형도 과거에 익숙했던 공식·방법만 고집하는 패턴.",category:"학습 습관",icon:"📚",priority:"medium",audioTime:"2:10",solution:{action:"문제를 읽고 30초 간 '이 유형을 처음 본다면?' 스스로 질문 → 떠오른 대안 풀이를 메모 2줄로 적어보기 → 실제 풀이 후 기존 공식 vs 대안 풀이의 장·단점 비교 작성",check:"30초 질문으로 떠올린 대안 풀이가 있었는지, 타당했는지 피드백",teacherDialog:"30초 질문으로 떠올린 대안 풀이가 있었는데, 타당했는지 피드백을 듣고 싶어요."}},
        {id:20,name:"불완전 개념 종결형",desc:"정의·조건을 끝까지 읽지 않고 '충분해'라고 판단해 풀이를 서둘러 종결하는 패턴.",category:"검증/확인 부재",icon:"✂️",priority:"high",audioTime:"1:30",solution:{action:"문제에 나온 용어·명제는 노트 하단에 정의 원문을 그대로 필사 → 풀이 중 해당 정의를 적용할 때 밑줄 + 옆에 페이지 참조 표시 → 풀이 후 '정의 적용 위치'를 하이라이트 색으로 모두 확인",check:"원문 정의를 필사했는지, 적용한 부분이 정의 조건과 일치하는지 검토",teacherDialog:"원문 정의를 필사했는데, 제가 적용한 부분이 정의 조건과 일치하는지 검토해 주세요."}},
        {id:21,name:"피로-오답 포용형",desc:"체력이 떨어질수록 오류 감지력이 급감해 '이 정도면 됐겠지' 하고 넘어가는 패턴.",category:"기타 장애",icon:"😴",priority:"medium",audioTime:"2:00",solution:{action:"30분 집중 + 2분 눈·목 스트레칭 루틴(타이머 필수) → 피로 신호(눈 따가움, 하품) 느끼면 물 3모금 + 10초 눈감기 → 세션 마지막 5분은 반드시 검산 전용으로 예약",check:"30 + 2 루틴을 4세트 돌렸는지, 마지막 검산 전/후에 찾은 오류 확인",teacherDialog:"30 + 2 루틴을 4세트 돌렸습니다. 마지막 검산 전/후에 찾은 오류를 함께 확인해 주실 수 있을까요?"}},
        {id:22,name:"감정 전염 스트레스형",desc:"옆 친구·교사 표정 / 소음에 불안이 증폭돼 작업기억 용량이 급락하는 패턴.",category:"기타 장애",icon:"😟",priority:"medium",audioTime:"1:50",solution:{action:"불안을 느끼면 즉시 4-7-8 호흡법(4초 들숨-7초 정지-8초 날숨) 1회 → 집중 음악(화이트노이즈·Lo-fi) 1곡 반복 설정 → 방해 요소가 지속되면 A6 메모지에 감정 상태 한 단어 적고 덮기",check:"오늘 4-7-8 호흡을 세 번 했는지, 집중도 변화가 보였는지 피드백",teacherDialog:"오늘 4-7-8 호흡을 세 번 했습니다. 제 집중도 변화가 보였는지 피드백 부탁드립니다."}},
        {id:23,name:"과다 정보 섭취형",desc:"한 문제를 풀며 해설·영상·블로그 등 여러 자료를 동시에 열어 인지 부하가 폭발하는 패턴.",category:"인지 과부하",icon:"📱",priority:"medium",audioTime:"2:15",solution:{action:"문제당 참고자료 최대 2개 원칙(노트 상단에 자료명 기입) → 추가 자료가 필요하면 기존 2개 중 1개를 닫고 새로 연다 → 학습 끝나면 참고자료 목록을 요약 5줄로 정리",check:"두 자료만 사용해 5줄 요약을 작성했는지, 중요한 포인트가 빠졌는지 확인",teacherDialog:"두 자료만 사용해 5줄 요약을 작성했습니다. 중요한 포인트가 빠졌는지 확인해 주세요."}},
        {id:24,name:"이론-연산 전도형",desc:"개념 증명·이론에 깊게 몰입하다가 정작 필수 계산(연산)을 뒤로 밀어 실수를 유발하는 패턴.",category:"접근 전략 오류",icon:"🔢",priority:"low",audioTime:"2:05",solution:{action:"증명 줄이 10줄을 넘기면 바로 계산 단계 체크 박스 작성 → 증명 ↔ 계산을 N:1 교차(10줄마다 계산 1번) 구조로 강제 → 최종 답 후 증명·계산 단계를 색깔 다른 하이라이터로 구분 표시",check:"N:1 교차 구조를 적용했는지, 계산 삽입 위치가 적절했는지 확인",teacherDialog:"N:1 교차 구조를 적용했는데, 계산 삽입 위치가 적절했는지 봐주실래요?"}},
        {id:25,name:"단일 예시 착시형",desc:"특정 예제에서 성공한 방식을 새 문제에 그대로 적용해 예외 상황을 놓치는 패턴.",category:"학습 습관",icon:"🔍",priority:"medium",audioTime:"1:55",solution:{action:"새 문제 시작 시 '예시와 다른 점 3개'를 빠르게 메모 → 풀이 중 3개의 차이가 모두 반영됐는지 중간·최종에 체크 → 주간 회고 때 '예시 착시 → 교정 성공 사례'를 포트폴리오에 기록",check:"예시와 다른 점 3개 중 2개만 반영된 것 같다면, 남은 1개를 어디서 고려해야 할지 조언",teacherDialog:"예시와 다른 점 3개 중 2개만 반영된 것 같습니다. 남은 1개를 어디서 고려해야 할지 조언 부탁드립니다."}},
        {id:26,name:"시간 왜곡 긴장형",desc:"제한 시간을 실제보다 덜/더 급하게 느껴 불필요한 조급함·지연을 만드는 패턴.",category:"시간/압박 관리",icon:"⏳",priority:"medium",audioTime:"2:20",solution:{action:"세션 60분을 45분 타이머 + 15분 여유로 나누기 → 45분 타이머 종료 시 현재 진행도를 %로 적기(예: 70%) → 남은 15분은 검산·보완 전용 영역으로만 사용",check:"45분 지점에서 진행도를 68%로 측정했다면, 남은 32%를 15분에 채우는 전략이 적절했는지 조언",teacherDialog:"45분 지점에서 제 진행도를 68%로 측정했어요. 남은 32%를 15분에 채우는 전략이 적절했는지 조언 부탁드립니다."}},
        {id:27,name:"보상 심리 도박형",desc:"앞선 실수를 만회하려는 집착으로 복잡한(때론 불필요한) 해법을 억지로 채택하는 패턴.",category:"기타 장애",icon:"🎰",priority:"medium",audioTime:"2:10",solution:{action:"'분노 수정' 감정을 느끼면 2분 워킹 브레이크(자리서 20걸음 왕복) → 돌아와서 현재 문제 난이도를 L·M·H 중 다시 판단 → 고난도('H')로 변질되면, 바로 새로운 문제로 전환 후 나중에 재도전",check:"실수 뒤 2분 걷고 난 뒤 난이도를 재평가했는지, 전환 시점을 올바르게 잡았는지 확인",teacherDialog:"실수 뒤 2분 걷고 난 뒤 난이도를 재평가했습니다. 제가 전환 시점을 올바르게 잡았는지 확인해 주세요."}},
        {id:28,name:"공간-시각 혼선형",desc:"도형·그래프·좌표를 머릿속에 잘못 배치해 관계를 뒤집어 버리는 패턴.",category:"실수 패턴",icon:"📐",priority:"medium",audioTime:"2:25",solution:{action:"문제를 읽자마자 A6 메모지에 빠른 스케치(축·꼭짓점·변수 기입) → 변수나 길이 변화가 생길 때마다 스케치를 즉시 업데이트 → 풀이 완료 후 스케치 ↔ 최종 답을 색펜 화살표로 연결",check:"업데이트한 스케치를 보여드릴 때, 변수 변화 반영이 제대로 됐는지 확인",teacherDialog:"업데이트한 스케치를 보여드릴게요. 변수 변화 반영이 제대로 됐는지 확인 부탁드립니다."}},
        {id:29,name:"자기긍정 과열형",desc:"'이건 내가 잘하던 유형'이라는 자기암시로 검산·근거 검토를 생략하는 패턴.",category:"자신감 왜곡",icon:"💪",priority:"low",audioTime:"1:50",solution:{action:"'익숙유형' 생각이 들면 문제 번호 옆에 검산 플래그★ 표시 → 풀이 후 ★이 있는 문제는 역대입·조건 체크 2단계 검산 필수 → 주간 회고에서 ★ 문제의 실제 정답률을 통계로 기록(주간 %)",check:"★ 표시한 두 문제를 역대입으로 검산했는지, 놓친 조건이 있었는지 피드백",teacherDialog:"★ 표시한 두 문제를 역대입으로 검산했습니다. 놓친 조건이 있었는지 피드백 부탁드립니다."}},
        {id:30,name:"메타인지 고갈형",desc:"문제 진행 중 '내가 뭘 모르는지' 평가 기능이 고갈돼 학습이 무의식적 반복으로 변하는 패턴.",category:"기타 장애",icon:"🎯",priority:"medium",audioTime:"2:00",solution:{action:"20분마다 알람 → '내가 모르는 부분 1문장 메모' 루틴 → 메모한 문장을 과녁표 (🎯) 표시 목록에 모으기 → 세션 종료 후 과녁표 항목을 자료 탐색·질문 리스트로 전환",check:"🎯 리스트에서 3개를 추렸다면, 어떤 순서로 해결하면 좋을지 안내",teacherDialog:"🎯 리스트에서 3개를 추렸습니다. 어떤 순서로 해결하면 좋을지 안내 부탁드립니다."}},
        {id:31,name:"개념-용어 혼동형",desc:"정의·기호를 모호하게 기억해 비슷한 단어와 혼동, 조건 매칭에 실패하는 패턴.",category:"검증/확인 부재",icon:"🏷️",priority:"medium",audioTime:"2:15",solution:{action:"개념 등장 시 색상 코드 지정: 정의(파란), 정리(초록), 예외(보라) → 유사 용어는 노트 오른쪽에 '헷갈림 리스트'로 별도 기록 → 학습 종료 전 헷갈림 리스트를 퀴즈 카드로 3분 복습",check:"헷갈림 리스트의 'congruent' vs 'consistent'를 구분 정리했는지, 설명이 맞는지 확인",teacherDialog:"헷갈림 리스트의 'congruent' vs 'consistent'를 구분 정리했는데, 설명이 맞는지 확인 부탁드립니다."}},
        {id:32,name:"역추적 단절형",desc:"답을 먼저 보고 거꾸로 이유를 찾다 논리 사다리가 중간에서 끊기는 패턴.",category:"접근 전략 오류",icon:"⬆️",priority:"medium",audioTime:"2:05",solution:{action:"답 확인 전 역방향 체크리스트(①→②→③)를 빈칸으로 작성 → 체크리스트를 채우며 필요 근거를 파란색, 이미 있는 근거를 검정으로 표시 → 빈칸이 남으면 앞단계를 정·역방향 교차 검토",check:"역방향 체크리스트에서 빈칸 두 개가 있었다면, 보충 근거가 적절한지 검토",teacherDialog:"역방향 체크리스트에서 빈칸 두 개가 있었는데, 보충 근거가 적절한지 검토 부탁드립니다."}},
        {id:33,name:"사다리 건너뛰기형",desc:"중간 논증을 생략하고 결론으로 직행, 근거 빈칸을 스스로 인식하지 못하는 패턴.",category:"접근 전략 오류",icon:"🪜",priority:"high",audioTime:"1:55",solution:{action:"논증 단계에 번호(①②③…)와 화살표를 모두 명시 → 결론에 도달하면 ①부터 화살표를 역방향으로 따라가며 근거 문장 점검 → 빠진 단계가 있으면 빨간펜으로 'Missing Step!' 태그",check:"Missing Step 태그가 두 군데 나왔다면, 적절한 중간 근거를 추가했는지 확인",teacherDialog:"Missing Step 태그가 두 군데 나왔습니다. 적절한 중간 근거를 추가했는지 확인해 주세요."}},
        {id:34,name:"조건 재정렬 미흡형",desc:"복합 조건의 순서를 무시해 필수·보조 정보를 혼선시키는 패턴.",category:"검증/확인 부재",icon:"📋",priority:"medium",audioTime:"2:10",solution:{action:"모든 조건 앞에 순번 스티커(①②③) 부착 후 순서 고정 → 풀이 중 해당 조건을 사용하면 순번 옆에 체크✔︎ → 체크되지 않은 조건이 남으면 순서를 재검토해 적용 위치 보완",check:"③번 조건이 늦게 체크되었다면, 적용 순서가 논리에 맞는지 피드백",teacherDialog:"③번 조건이 늦게 체크되었습니다. 적용 순서가 논리에 맞는지 피드백 부탁드립니다."}},
        {id:35,name:"공식 암기 과신형",desc:"문제 특성과 무관하게 외운 공식만 기계적으로 대입, 오적용 위험이 큰 패턴.",category:"학습 습관",icon:"📖",priority:"medium",audioTime:"2:20",solution:{action:"공식을 적을 때 오른쪽에 '출처·조건'을 1줄 주석 → 공식 사용 전 조건 매칭 질문 3개(예: '연속? 미분 가능?') 답 체크 → 매주 사용한 공식·조건 목록을 통계로 정리 → 오용 사례 표시",check:"이번 주 공식-조건 통계에서 오용 사례가 1건 나왔다면, 올바른 조건 확인 절차가 충분했는지 조언",teacherDialog:"이번 주 공식-조건 통계에서 오용 사례가 1건 나왔습니다. 올바른 조건 확인 절차가 충분했는지 조언 부탁드립니다."}},
        {id:36,name:"근사치 타협형",desc:"'대략 맞겠지' 하고 근사 계산으로 풀이를 종료, 오차 검증을 생략하는 패턴.",category:"검증/확인 부재",icon:"≈",priority:"low",audioTime:"2:00",solution:{action:"근사값을 쓸 때마다 옆에 '±오차 범위'를 바로 기입 → 최종 답 전 오차 ≤ 목표 허용치? 체크박스에 ✔︎ → 오차 초과 시 정확 계산 또는 더 정밀한 근사법(테일러, 분할 적분 등) 재적용",check:"±오차 0.02까지 확인했다면, 이 허용치가 적절한지 검토",teacherDialog:"±오차 0.02까지 확인했는데, 이 허용치가 적절한지 검토 부탁드립니다."}},
        {id:37,name:"개념-문제 불일치 간과형",desc:"문제에서 요구하는 개념과 다른 영역 해법을 고집해 방향이 어긋나는 패턴.",category:"접근 전략 오류",icon:"🎭",priority:"medium",audioTime:"2:25",solution:{action:"문제 읽자마자 상단에 '필수 개념' 1줄 제목 작성 → 풀이 중 개념이 바뀌면 제목 옆에 🚨표시 후 이유 메모 → 최종 답 후 제목과 실제 사용 개념이 일치? 불일치? 이중 체크",check:"필수 개념 제목을 '벡터 기하'로 잡았는데, 중간에 미적분 개념을 섞었다면 전환 시점이 논리에 맞는지 확인",teacherDialog:"필수 개념 제목을 '벡터 기하'로 잡았는데, 중간에 미적분 개념을 섞었습니다. 전환 시점이 논리에 맞는지 확인해 주세요."}},
        {id:38,name:"단위 무시형",desc:"길이·각도·π 변환 등 단위 체크를 생략해 결과가 엇갈리는 패턴.",category:"실수 패턴",icon:"📏",priority:"high",audioTime:"1:45",solution:{action:"단위 변환이 필요할 때마다 둥근 박스로 원·목표 단위 표시 → 변환 후 박스 옆에 '변환 OK' 스탬프(✔︎) 찍기 → 답안 작성 직전 모든 박스를 훑어 미검증 박스=0 확인",check:"라디안→도 변환 박스를 놓칠 뻔했다면, 전체 박스 검토가 충분했는지 확인",teacherDialog:"라디안→도 변환 박스를 놓칠 뻔했는데, 전체 박스 검토가 충분했는지 봐주실 수 있나요?"}},
        {id:39,name:"시각화 회피형",desc:"그래프·도형 그리기를 귀찮아해 공간적 관계를 착시·오독하는 패턴.",category:"실수 패턴",icon:"📊",priority:"medium",audioTime:"2:15",solution:{action:"도형·그래프 문제는 A6 메모지에 60초 제한 스케치를 필수 → 변수 값이 변할 때마다 색펜으로 동적 업데이트 → 풀이 후 스케치와 알지브라식 답을 화살표 연결해 일치 여부 확인",check:"60초 스케치를 보여드릴 때, 변수 변화가 올바르게 반영됐는지 피드백",teacherDialog:"60초 스케치를 보여드릴게요. 변수 변화가 올바르게 반영됐는지 피드백 부탁드립니다."}},
        {id:40,name:"메모 불능 기억 과신형",desc:"'머릿속에 다 있어'라며 메모 없이 진행, 항목 순서가 뒤섞이는 패턴.",category:"기타 장애",icon:"🧠",priority:"medium",audioTime:"1:50",solution:{action:"조건·중간값을 '미니 메모칩'(포스트잇)으로 즉시 기록 → 노트에 붙이기 → 풀이 단계 전환 때마다 메모칩을 눈으로 터치 확인 → 풀이 후 메모칩을 순서대로 재정렬하며 논리 흐름 검산",check:"메모칩을 순서대로 재정렬했다면, 논리 흐름이 자연스러운지 검토",teacherDialog:"메모칩을 순서대로 재정렬했는데, 논리 흐름이 자연스러운지 검토해 주세요."}},
        {id:41,name:"지식-실행 단절형",desc:"개념은 이해했지만 문제 적용 단계에서 멈칫해 '알아도 못 푸는' 상황이 반복되는 패턴.",category:"학습 습관",icon:"🔗",priority:"high",audioTime:"2:05",solution:{action:"새 개념 학습 직후 예제 1문제를 즉시 해결(3분 제한) → 예제가 막히면 '개념 → 절차 → 예시' 흐름을 음성으로 20초 복창 → 복창 후 다시 풀어 보고 성공 여부를 O/X로 기록",check:"20초 복창 후 예제를 다시 풀어 봤다면, 절차 설명이 명확했는지 피드백",teacherDialog:"20초 복창 후 예제를 다시 풀어 봤습니다. 절차 설명이 명확했는지 피드백 부탁드립니다."}},
        {id:42,name:"노이즈 필터 실패형",desc:"지문 속 중요치 않은 숫자·문장이 작업기억을 점유해 핵심 정보를 덮어버리는 패턴.",category:"인지 과부하",icon:"🔇",priority:"medium",audioTime:"2:10",solution:{action:"문제를 처음 읽을 때 밑줄(핵심) / 연필 흐림선(노이즈) 2단계 표시 → 풀이 중 노이즈 부분은 괄호로 접어두기(접힌 종이 시각 효과) → 최종 검산 시 노이즈가 풀이에 영향을 줬는지 체크표 작성",check:"노이즈 표시한 문장을 접어뒀다면, 핵심을 올바르게 추려냈는지 확인",teacherDialog:"노이즈 표시한 문장을 접어뒀는데, 제가 핵심을 올바르게 추려냈는지 확인해 주세요."}},
        {id:43,name:"인터럽트 리셋 불능형",desc:"알림·대화 등 외부 방해 후 이전 맥락을 복구하지 못해 흐름이 끊기는 패턴.",category:"기타 장애",icon:"🔄",priority:"medium",audioTime:"1:55",solution:{action:"방해를 받기 전 단계를 한 줄로 요약해 상단 포스트잇에 써둔다 → 방해가 끝나면 포스트잇을 소리 내어 읽고 동일 단계에서 재시작 → 포스트잇을 떼어 노트 하단에 붙이며 'Context Restored' 체크",check:"방해 후 포스트잇 요약으로 복귀했다면, 단계 연결이 자연스러운지 확인",teacherDialog:"방해 후 포스트잇 요약으로 복귀했는데, 단계 연결이 자연스러운지 봐주실래요?"}},
        {id:44,name:"감정 보상 과다형",desc:"작은 성공에 과도한 도파민 보상이 발생해 주의력이 이완되고 다음 단계가 느슨해지는 패턴.",category:"기타 장애",icon:"🎉",priority:"low",audioTime:"2:00",solution:{action:"성공 시 10초 셀프 칭찬(속삭이기) 후 바로 타이머 재가동 → 다음 단계 착수 전 '다음 할 일 5단어' 메모 → 학습 끝에 총 셀프 칭찬 시간을 분 단위로 기록(1분 이내 목표)",check:"셀프 칭찬 6회, 총 50초였다면, 다음 할 일 메모가 충분했는지 확인",teacherDialog:"셀프 칭찬 6회, 총 50초였습니다. 다음 할 일 메모가 충분했는지 확인 부탁드립니다."}},
        {id:45,name:"휴식 부족 저하형",desc:"장시간 집중 후 인지 피로가 누적돼 오류 검출률이 급락하는 패턴.",category:"기타 장애",icon:"😪",priority:"high",audioTime:"2:20",solution:{action:"90분 세션 → 15분 휴식 'Pomodoro Plus' 스케줄 설정 → 휴식 시간엔 스트레칭 + 물 1컵 + 창밖 2분 바라보기 수행 → 휴식 후 첫 문제를 검산 문제로 선택해 집중도 회복 확인",check:"90 + 15 루틴을 2세트 돌렸다면, 휴식 후 검산 정확도가 나아졌는지 확인",teacherDialog:"90 + 15 루틴을 2세트 돌렸습니다. 휴식 후 검산 정확도가 나아졌는지 확인해 주세요."}},
        {id:46,name:"전환 비용 과소평가형",desc:"여러 문제·풀이법을 빈번히 바꾸며 작업기억을 재로딩, 집중 에너지를 낭비하는 패턴.",category:"시간/압박 관리",icon:"💱",priority:"medium",audioTime:"2:15",solution:{action:"문제 전환 전 현재 풀이를 2줄 요약해 노트 여백에 작성 → 새 문제로 넘어갈 때 요약 옆에 타임스탬프 기록 → 하루 학습 끝에 전환 횟수와 소요 시간을 막대그래프로 시각화",check:"오늘 문제 전환 5회, 총 8분 소요였다면, 전환 요약이 충분했는지 피드백",teacherDialog:"오늘 문제 전환 5회, 총 8분 소요였습니다. 전환 요약이 충분했는지 피드백 부탁드립니다."}},
        {id:47,name:"반례 무시형",desc:"풀이가 순조로우면 '예외 없겠지'라며 반례 검증을 생략하는 패턴.",category:"검증/확인 부재",icon:"❌",priority:"high",audioTime:"2:05",solution:{action:"풀이 과정 중 '반례 가능성 칸'을 만들고 최소 1개 쓰기 → 최종 답 전 반례를 실제로 수치·그림으로 확인 → 반례가 존재하면 풀이를 분기해 조건 보강 또는 전략 수정",check:"반례 칸에 적은 예를 테스트했는데 조건을 추가해야 했다면, 수정이 타당한지 검토",teacherDialog:"반례 칸에 적은 예를 테스트했는데 조건을 추가해야 했습니다. 수정이 타당한지 검토해 주세요."}},
        {id:48,name:"관성적 읽기 스킵형",desc:"익숙해 보이는 문제라 생각해 지문의 끝을 읽지 않고 풀이를 시작하는 패턴.",category:"실수 패턴",icon:"⏭️",priority:"medium",audioTime:"1:50",solution:{action:"문장 끝마다 '／' 표시해 끝까지 시각적으로 확인 → 표시 후 마지막 문장을 큰 소리로 1번 읽고 착수 → 풀이 중 조건 충돌이 생기면 스킵 여부를 체크표로 기록",check:"'／' 표시를 모두 달았는데 마지막 문장이 중요 조건이더라면, 해당 조건 반영이 잘 됐는지 확인",teacherDialog:"'／' 표시를 모두 달았는데 마지막 문장이 중요 조건이더군요. 해당 조건 반영이 잘 됐는지 확인 부탁드립니다."}},
        {id:49,name:"조건 재해석 과잉형",desc:"애매한 문구를 자의적으로 해석해 핵심 의미를 빗나가는 패턴.",category:"검증/확인 부재",icon:"🔮",priority:"medium",audioTime:"2:10",solution:{action:"애매 문구는 즉시 질문 카드 작성 → 교사·AI 튜터에게 전송 → 답변을 받을 때까지 임시 해석에 '?' 마크 붙여 진행 → 확정 해석 후 '?' 마크 부분을 빨간펜 정정",check:"질문 카드로 받은 답변을 반영해 '?' 마크를 정정했다면, 해석이 맞는지 최종 확인",teacherDialog:"질문 카드로 받은 답변을 반영해 '?' 마크를 정정했습니다. 해석이 맞는지 최종 확인 부탁드립니다."}},
        {id:50,name:"단계 통합 과속형",desc:"두세 단계를 한 줄로 압축해 적으면서 오류 추적이 불가능해지는 패턴.",category:"실수 패턴",icon:"🏃‍♂️",priority:"medium",audioTime:"1:55",solution:{action:"2단계 이상은 반드시 화살표 대신 연속 번호(①②)로 구분 → 통합 줄 작성 후 각 번호 옆에 중간 결과를 따로 산출 → 검산 시 중간 결과와 최종 결과 간 일관성을 확인",check:"①②로 나눈 중간 결과가 최종 결과와 연결됐는지 검산했다면, 추가 개선점이 있을지 조언",teacherDialog:"①②로 나눈 중간 결과가 최종 결과와 연결됐는지 검산했습니다. 추가 개선점이 있을까요?"}},
        {id:51,name:"중간점검 생략형",desc:"풀이가 절반쯤 진행됐을 때 검산 없이 끝까지 돌진, 오류를 초기에 놓치는 패턴.",category:"검증/확인 부재",icon:"⏸️",priority:"high",audioTime:"2:00",solution:{action:"문제 착수와 동시에 자동 알람을 풀이 예상시간의 50% 지점에 설정 → 알람이 울리면 즉시 진행 중인 식에 역대입 검증(또는 그래프 확인) 수행 → 검산 결과를 O／Δ／X 기호로 표시 후 계속 진행",check:"50% 알람에서 Δ 표시가 나왔다면, 수정 방식이 적절했는지 확인",teacherDialog:"50% 알람에서 Δ 표시가 나왔는데, 수정 방식이 적절했는지 확인 부탁드립니다."}},
        {id:52,name:"검산 회피형",desc:"시간 아까워 검산을 건너뛰어 정답률이 흔들리는 패턴.",category:"검증/확인 부재",icon:"🚫",priority:"high",audioTime:"1:45",solution:{action:"최종 답 기입 직후 검산 메뉴 3개(역대입·단위·추가 조건) 중 1개를 무조건 실행 → 검산 완료 시 문제 번호 옆에 ✔︎ 스탬프 찍기 → 주간 회고 때 검산 스탬프 개수와 실제 정답률을 분석 그래프로 비교",check:"검산 스탬프가 10개 중 9개라면, 스킵한 1문제가 괜찮았는지 검토",teacherDialog:"검산 스탬프가 10개 중 9개입니다. 스킵한 1문제가 괜찮았는지 검토 부탁드립니다."}},
        {id:53,name:"계산 체계 혼합형",desc:"분수↔소수, 라디안↔도 등 단위를 혼용하다 값이 뒤섞이는 패턴.",category:"실수 패턴",icon:"🔀",priority:"medium",audioTime:"2:10",solution:{action:"변환이 일어날 때마다 변환표(예: π↔°, 1/3↔0.333…)를 노트 옆에 작성 → 최종 계산 단계에서 '최종 단위 일관?' 체크박스를 ✔︎ → 혼합 오류가 나오면 변환표를 색펜으로 강조 재정리",check:"변환표를 만들었다면, 최종 일관성 체크가 충분했는지 확인",teacherDialog:"변환표를 만들었는데, 최종 일관성 체크가 충분했는지 확인해 주세요."}},
        {id:54,name:"음운 혼동형",desc:"'sine'↔'sign', 'root'↔'route' 등 비슷한 발음을 착각해 기호·용어를 바꾸는 패턴.",category:"실수 패턴",icon:"🗣️",priority:"low",audioTime:"1:50",solution:{action:"유사 음 용어를 색깔로 구분(예: 수학 기호=파랑, 일반 단어=검정) → 필기 시 발음을 속삭이며 기호를 다시 한번 확인 → 학습 후 유사 음 용어 목록을 퀴즈 카드로 2분 복습",check:"색깔 구분과 속삭이기 전략 적용 후 오기가 줄었는지 확인",teacherDialog:"색깔 구분과 속삭이기 전략 적용 후 오기가 줄었는지 봐주실 수 있나요?"}},
        {id:55,name:"참조 프레임 불일치형",desc:"좌표 원점·축 방향 전환을 놓쳐 그래프·변수를 잘못 배치하는 패턴.",category:"실수 패턴",icon:"🧭",priority:"medium",audioTime:"2:15",solution:{action:"좌표 변환이 나오면 작은 스케치로 새 원점·축을 즉시 표시 → 변수·길이를 옮길 때마다 스케치 상에 마커 펜으로 업데이트 → 풀이 완료 후 스케치와 대수식 관계를 검산 화살표로 연결",check:"새 원점·축을 그린 스케치를 보여드리겠다면, 변수 위치가 정확한지 피드백",teacherDialog:"새 원점·축을 그린 스케치를 보여드리겠습니다. 변수 위치가 정확한지 피드백 부탁드립니다."}},
        {id:56,name:"전략 중복 추적 피로형",desc:"동시에 3가지 이상 풀이를 전개하다 작업기억이 분산-탈진하는 패턴.",category:"인지 과부하",icon:"🤹",priority:"medium",audioTime:"2:05",solution:{action:"동시에 2개 풀이만 허용, 3번째 아이디어는 대기 메모 칸에 보류 → 두 풀이 중 하나가 막히면 대기 칸에서 1개만 꺼내 진행 → 세션 종료 후 사용 안 한 아이디어를 '보류 로그'로 분류‧검토",check:"오늘 두 개 풀이만 병행했고, 보류 로그에 2개를 남겼다면, 전략 전환 시점이 적절했는지 조언",teacherDialog:"오늘 두 개 풀이만 병행했고, 보류 로그에 2개를 남겼습니다. 전략 전환 시점이 적절했는지 조언 부탁드립니다."}},
        {id:57,name:"목표-행동 단절형",desc:"'개념 학습'이 '풀이 수집'으로 변질돼 원래 목표를 잊는 패턴.",category:"학습 습관",icon:"🎯",priority:"high",audioTime:"2:20",solution:{action:"학습 시작 전 '오늘 목표 1문장'을 화면 상단에 고정 → 30분마다 목표 문장을 소리 내어 읽고 현재 행동과 매칭 여부 체크 → 세션 끝에 목표 달성도를 0~100%로 자평·기록",check:"오늘 목표 달성도를 85%로 평가했다면, 행동이 목표와 얼마나 일치했는지 확인",teacherDialog:"오늘 목표 달성도를 85%로 평가했습니다. 제 행동이 목표와 얼마나 일치했는지 확인 부탁드립니다."}},
        {id:58,name:"피드백 과민형",desc:"작은 지적에도 불안이 급등해 작업기억 용량이 급락하는 패턴.",category:"기타 장애",icon:"😣",priority:"medium",audioTime:"1:55",solution:{action:"부정적 피드백을 받으면 30초 눈 감고 복식호흡 → 노트에 '교정 = 성장' 문장을 써서 시야에 두기 → 피드백을 '사실' '해석' '다음 행동' 3열 표로 분리 기록",check:"'사실-해석-다음 행동' 표를 작성했다면, 해석이 과민하지 않았는지 피드백",teacherDialog:"'사실-해석-다음 행동' 표를 작성했습니다. 해석이 과민하지 않았는지 피드백 부탁드립니다."}},
        {id:59,name:"다중 문제 스위칭 과부하형",desc:"시험 직전에 여러 문제를 빠르게 훑다 인지 세트업이 실패하는 패턴.",category:"시간/압박 관리",icon:"📚",priority:"high",audioTime:"2:10",solution:{action:"시험 전날 최대 3세트(L·M·H 각 1세트)만 선정 → 각 세트 완료 후 5분 정리 노트로 핵심만 요약 → 요약 노트를 아침 리콜(5분)로 다시 읽고 시험장 입장",check:"3세트 요약 노트를 만들었다면, 핵심 추출이 충분한지 검토",teacherDialog:"3세트 요약 노트를 만들었습니다. 핵심 추출이 충분한지 검토해 주세요."}},
        {id:60,name:"자기평가 누적 오류형",desc:"진행 중 정확도 추정이 계속 어긋나 자기효능감이 왜곡되는 패턴.",category:"기타 장애",icon:"📊",priority:"medium",audioTime:"2:00",solution:{action:"각 문제 해결 후 난이도·정확도 5점 척도 자체 채점 → 세션 끝에 실제 채점 결과와 산포도 그래프로 비교 → 편향(과·과소 평가)을 발견하면 다음 세션 보정 목표 설정",check:"자기평가 vs 실제 점수 산포도를 그렸다면, 편향 보정 계획이 적절한지 피드백",teacherDialog:"자기평가 vs 실제 점수 산포도를 그렸습니다. 편향 보정 계획이 적절한지 피드백 부탁드립니다."}}
    ];
    </script>
    <script>
        const studentId = <?php echo json_encode($studentId); ?>;
        let conqueredSet = new Set();
        let currentPersonaId = null;
        let currentAudio = null;

        document.addEventListener('DOMContentLoaded', () => {
            renderCards();
            loadProgress();
            bindEvents();
        });

        function renderCards() {
            const grid = document.getElementById('personaGrid');
            grid.innerHTML = personas.map(p => `
                <div class="persona-card" data-id="${p.id}" data-category="${p.category}" data-priority="${p.priority}">
                    <div class="persona-priority ${p.priority}"></div>
                    <div class="persona-icon">${p.icon}</div>
                    <div class="persona-id">#${String(p.id).padStart(2, '0')}</div>
                    <div class="persona-name">${p.name}</div>
                    <div class="persona-category">${p.category}</div>
                </div>
            `).join('');
        }

        function loadProgress() {
            const saved = localStorage.getItem(`persona_progress_${studentId}`);
            if (saved) { conqueredSet = new Set(JSON.parse(saved)); updateUI(); }
        }

        function saveProgress() {
            localStorage.setItem(`persona_progress_${studentId}`, JSON.stringify([...conqueredSet]));
            updateUI();
        }

        function updateUI() {
            // 일반 그리드와 단계별 보기 모두에서 정복 상태 업데이트
            document.querySelectorAll('.persona-card').forEach(card => {
                const id = parseInt(card.dataset.id);
                card.classList.toggle('conquered', conqueredSet.has(id));
            });
            document.getElementById('conqueredCount').textContent = conqueredSet.size;
            const percent = Math.round((conqueredSet.size / 60) * 100);
            document.getElementById('progressFill').style.width = `${percent}%`;
            document.getElementById('progressPercent').textContent = `${percent}%`;
            if (currentPersonaId && conqueredSet.has(currentPersonaId)) {
                const btn = document.getElementById('conquerBtn');
                btn.textContent = '✓ 정복 완료!';
                btn.classList.add('conquered');
            }
            
            // 단계별 보기 모드일 때 카드 재렌더링
            if (typeof isStageViewMode !== 'undefined' && isStageViewMode && typeof renderStageView === 'function') {
                renderStageView();
            }
        }

        function bindEvents() {
            document.querySelectorAll('.category-item').forEach(item => {
                item.addEventListener('click', () => {
                    document.querySelectorAll('.category-item').forEach(i => i.classList.remove('active'));
                    item.classList.add('active');
                    filterByCategory(item.dataset.category);
                });
            });
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    applyFilter(btn.dataset.filter);
                });
            });
            document.getElementById('personaGrid').addEventListener('click', (e) => {
                const card = e.target.closest('.persona-card');
                if (card) openDetail(parseInt(card.dataset.id));
            });
            document.getElementById('conquerBtn').addEventListener('click', conquerCurrentPersona);
        }

        function filterByCategory(category) {
            const grid = document.getElementById('personaGrid');
            const isFiltered = category !== 'all';
            grid.classList.toggle('filtered', isFiltered);
            
            document.querySelectorAll('.persona-card').forEach(card => {
                card.style.display = (category === 'all' || card.dataset.category === category) ? '' : 'none';
            });
        }

        function applyFilter(filter) {
            const grid = document.getElementById('personaGrid');
            const isFiltered = filter !== 'all';
            grid.classList.toggle('filtered', isFiltered);
            
            document.querySelectorAll('.persona-card').forEach(card => {
                const id = parseInt(card.dataset.id);
                const isConquered = conqueredSet.has(id);
                let show = false;
                switch (filter) {
                    case 'all': show = true; break;
                    case 'conquered': show = isConquered; break;
                    case 'remaining': show = !isConquered; break;
                    case 'high': show = card.dataset.priority === 'high'; break;
                }
                card.style.display = show ? '' : 'none';
            });
        }

        function openDetail(id) {
            currentPersonaId = id;
            const p = personas.find(x => x.id === id);
            if (!p) return;

            stopAudio();
            document.getElementById('detailIcon').textContent = p.icon;
            document.getElementById('detailName').textContent = p.name;
            document.getElementById('detailDesc').textContent = p.desc;
            document.getElementById('detailAction').textContent = p.solution?.action || '';
            document.getElementById('detailCheck').textContent = p.solution?.check || '';
            document.getElementById('detailTeacher').textContent = p.solution?.teacherDialog || '';
            document.getElementById('audioTimeDisplay').textContent = `0:00 / 0:00`;
            document.getElementById('audioTimeDisplay').classList.remove('visible');
            document.getElementById('audioProgressFill').style.width = '0%';
            document.getElementById('audioError').style.display = 'none';

            // 오디오 파일 설정
            const audioUrl = `https://mathking.kr/Contents/personas/인지관성 유형분석/${id}.wav`;
            const audioEl = document.getElementById('audioElement');
            audioEl.src = audioUrl;

            const btn = document.getElementById('conquerBtn');
            if (conqueredSet.has(id)) {
                btn.textContent = '✓ 정복 완료!';
                btn.classList.add('conquered');
            } else {
                btn.textContent = '🏆 이 패턴 정복하기';
                btn.classList.remove('conquered');
            }
            document.getElementById('detailPanel').classList.add('open');
        }

        function formatTime(seconds) {
            if (isNaN(seconds) || !isFinite(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        function toggleAudio() {
            const audioEl = document.getElementById('audioElement');
            const btn = document.getElementById('audioPlayBtn');
            const visualizer = document.getElementById('aiVisualizer');
            const audioPlayer = document.getElementById('audioPlayer');
            const timeDisplay = document.getElementById('audioTimeDisplay');
            const errorMsg = document.getElementById('audioError');

            if (audioEl.paused) {
                audioEl.play().then(() => {
                    btn.textContent = '⏸';
                    btn.classList.add('playing');
                    visualizer.classList.add('playing');
                    audioPlayer.classList.add('playing');
                    timeDisplay.classList.add('visible');
                    errorMsg.style.display = 'none';
                }).catch(err => {
                    console.error('Audio error:', err);
                    errorMsg.textContent = '재생 실패 - 파일 확인 필요';
                    errorMsg.style.display = 'block';
                });
            } else {
                audioEl.pause();
                btn.textContent = '▶';
                btn.classList.remove('playing');
                visualizer.classList.remove('playing');
                audioPlayer.classList.remove('playing');
            }
        }

        function stopAudio() {
            const audioEl = document.getElementById('audioElement');
            const btn = document.getElementById('audioPlayBtn');
            const visualizer = document.getElementById('aiVisualizer');
            const audioPlayer = document.getElementById('audioPlayer');
            const timeDisplay = document.getElementById('audioTimeDisplay');
            const progressFill = document.getElementById('audioProgressFill');
            
            audioEl.pause();
            audioEl.currentTime = 0;
            btn.textContent = '▶';
            btn.classList.remove('playing');
            visualizer.classList.remove('playing');
            audioPlayer.classList.remove('playing');
            timeDisplay.classList.remove('visible');
            progressFill.style.width = '0%';
        }

        function updateAudioProgress() {
            const audioEl = document.getElementById('audioElement');
            const progressFill = document.getElementById('audioProgressFill');
            const timeDisplay = document.getElementById('audioTimeDisplay');
            
            if (audioEl.duration && !isNaN(audioEl.duration)) {
                const progress = (audioEl.currentTime / audioEl.duration) * 100;
                progressFill.style.width = `${progress}%`;
                timeDisplay.textContent = `${formatTime(audioEl.currentTime)} / ${formatTime(audioEl.duration)}`;
            }
        }

        // 오디오 이벤트 리스너
        document.getElementById('audioElement').addEventListener('timeupdate', updateAudioProgress);
        
        document.getElementById('audioElement').addEventListener('loadedmetadata', () => {
            const audioEl = document.getElementById('audioElement');
            const timeDisplay = document.getElementById('audioTimeDisplay');
            timeDisplay.textContent = `0:00 / ${formatTime(audioEl.duration)}`;
        });

        document.getElementById('audioElement').addEventListener('ended', () => {
            const btn = document.getElementById('audioPlayBtn');
            const visualizer = document.getElementById('aiVisualizer');
            const audioPlayer = document.getElementById('audioPlayer');
            const progressFill = document.getElementById('audioProgressFill');
            
            btn.textContent = '▶';
            btn.classList.remove('playing');
            visualizer.classList.remove('playing');
            audioPlayer.classList.remove('playing');
            progressFill.style.width = '100%';
        });

        // 프로그레스 바 클릭 시 해당 위치로 이동
        document.getElementById('audioProgressBar').addEventListener('click', (e) => {
            const audioEl = document.getElementById('audioElement');
            const progressBar = document.getElementById('audioProgressBar');
            const rect = progressBar.getBoundingClientRect();
            const clickPosition = (e.clientX - rect.left) / rect.width;
            
            if (audioEl.duration && !isNaN(audioEl.duration)) {
                audioEl.currentTime = clickPosition * audioEl.duration;
            }
        });

        function conquerCurrentPersona() {
            if (!currentPersonaId || conqueredSet.has(currentPersonaId)) return;
            conqueredSet.add(currentPersonaId);
            saveProgress();
            const card = document.querySelector(`.persona-card[data-id="${currentPersonaId}"]`);
            if (card) {
                card.classList.add('conquered', 'conquered-animation');
                setTimeout(() => card.classList.remove('conquered-animation'), 500);
            }
            const btn = document.getElementById('conquerBtn');
            btn.textContent = '🎉 정복 완료!';
            btn.classList.add('conquered');
        }

        // 추천 순서 마스터 경로
        const recommendedOrder = [1,6,23,42,56,2,58,22,21,44,7,24,37,19,25,33,15,34,31,14,4,28,39,38,53,48,54,47,51,52,36,32,20,11,12,26,46,59,30,17,41,40,43];
        let isRecommendedMode = false;

        document.getElementById('recommendOrderBtn').addEventListener('click', () => {
            isRecommendedMode = !isRecommendedMode;
            const btn = document.getElementById('recommendOrderBtn');
            const grid = document.getElementById('personaGrid');
            
            if (isRecommendedMode) {
                btn.style.background = 'linear-gradient(135deg,#10b981,#059669)';
                btn.innerHTML = '✓ 순서 정복 모드';
                grid.classList.add('filtered');
                
                // 추천 순서대로 카드 재배열
                const fragment = document.createDocumentFragment();
                recommendedOrder.forEach((id, idx) => {
                    const card = document.querySelector(`.persona-card[data-id="${id}"]`);
                    if (card) {
                        card.style.display = '';
                        // 순서 번호 표시
                        let orderBadge = card.querySelector('.order-badge');
                        if (!orderBadge) {
                            orderBadge = document.createElement('div');
                            orderBadge.className = 'order-badge';
                            orderBadge.style.cssText = 'position:absolute;bottom:0.5rem;right:0.5rem;background:linear-gradient(135deg,#f59e0b,#ea580c);color:white;font-size:0.625rem;font-weight:bold;padding:0.125rem 0.375rem;border-radius:9999px;';
                            card.appendChild(orderBadge);
                        }
                        orderBadge.textContent = `${idx + 1}`;
                        fragment.appendChild(card);
                    }
                });
                // 추천 순서에 없는 카드는 숨기기
                document.querySelectorAll('.persona-card').forEach(card => {
                    const id = parseInt(card.dataset.id);
                    if (!recommendedOrder.includes(id)) {
                        card.style.display = 'none';
                    }
                });
                grid.innerHTML = '';
                grid.appendChild(fragment);
                
                // 안내 메시지 표시
                showRecommendMessage();
            } else {
                btn.style.background = 'linear-gradient(135deg,#f59e0b,#ea580c)';
                btn.innerHTML = '🎯 추천 순서';
                grid.classList.remove('filtered');
                
                // 원래 순서로 복원
                document.querySelectorAll('.order-badge').forEach(b => b.remove());
                renderCards();
                loadProgress();
                hideRecommendMessage();
            }
        });

        function showRecommendMessage() {
            let msgBox = document.getElementById('recommendMessage');
            if (!msgBox) {
                msgBox = document.createElement('div');
                msgBox.id = 'recommendMessage';
                msgBox.style.cssText = 'position:fixed;top:80px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,rgba(245,158,11,0.95),rgba(234,88,12,0.95));color:white;padding:1rem 2rem;border-radius:1rem;z-index:200;text-align:center;max-width:600px;box-shadow:0 10px 40px rgba(0,0,0,0.3);';
                document.body.appendChild(msgBox);
            }
            msgBox.innerHTML = `
                <div style="font-weight:bold;font-size:1.125rem;margin-bottom:0.5rem;">🎯 추천 정복 순서</div>
                <div style="font-size:0.875rem;line-height:1.6;">
                    <strong>인지부하 → 감정 → 전략 → 논리 → 실수 → 검증 → 시간 → 메타인지</strong><br>
                    이 순서가 학습자 뇌 상태 변화와 가장 유사한 최적 경로입니다.<br>
                    <span style="opacity:0.8;">카드를 클릭해 순서대로 정복해보세요!</span>
                </div>
                <button onclick="hideRecommendMessage()" style="margin-top:0.75rem;background:rgba(0,0,0,0.2);border:none;color:white;padding:0.375rem 1rem;border-radius:0.5rem;cursor:pointer;font-size:0.75rem;">확인</button>
            `;
            msgBox.style.display = 'block';
        }

        function hideRecommendMessage() {
            const msgBox = document.getElementById('recommendMessage');
            if (msgBox) msgBox.style.display = 'none';
        }

        // 풀이 단계별 페르소나 분류 (통일: 문제해석, 식세우기, 풀이과정, 점검, 장기기억화)
        const solvingStages = {
            '문제해석': {
                icon: '📖',
                subtitle: '문제를 읽고 조건을 파악하는 단계',
                ids: [15, 20, 31, 42, 48, 49]
            },
            '식세우기': {
                icon: '🚀',
                subtitle: '어떻게 풀지 전략을 세우고 방정식 설정하는 단계',
                ids: [2, 3, 7, 12, 19, 35, 37, 41]
            },
            '풀이과정': {
                icon: '✏️',
                subtitle: '실제로 풀이를 진행하며 시간/감정을 조절하는 단계',
                ids: [1, 4, 5, 6, 10, 11, 13, 14, 17, 22, 23, 24, 25, 26, 27, 28, 33, 38, 39, 43, 44, 46, 50, 53, 54, 55, 56]
            },
            '점검': {
                icon: '🔍',
                subtitle: '중간·최종 검산 및 피로 관리 단계',
                ids: [16, 21, 29, 32, 34, 36, 45, 47, 51, 52]
            },
            '장기기억화': {
                icon: '🏁',
                subtitle: '반복 연습으로 장기기억에 정착시키는 단계',
                ids: [8, 9, 18, 30, 40, 57, 58, 59, 60]
            }
        };

        let isStageViewMode = false;

        document.getElementById('stageViewBtn').addEventListener('click', () => {
            isStageViewMode = !isStageViewMode;
            const btn = document.getElementById('stageViewBtn');
            const grid = document.getElementById('personaGrid');
            const stageContainer = document.getElementById('stageViewContainer');
            const filterButtons = document.getElementById('filterButtons');
            const categorySidebar = document.querySelector('.category-sidebar');
            
            // 추천 순서 모드가 활성화되어 있으면 먼저 비활성화
            if (isRecommendedMode) {
                document.getElementById('recommendOrderBtn').click();
            }
            
            if (isStageViewMode) {
                btn.style.background = 'linear-gradient(135deg,#10b981,#059669)';
                btn.innerHTML = '✓ 단계별 보기 모드';
                grid.style.display = 'none';
                filterButtons.style.display = 'none';
                categorySidebar.style.display = 'none';
                stageContainer.classList.add('active');
                
                renderStageView();
                showStageMessage();
            } else {
                btn.style.background = 'linear-gradient(135deg,#06b6d4,#0891b2)';
                btn.innerHTML = '📊 풀이 단계별 페르소나 보기';
                grid.style.display = '';
                filterButtons.style.display = '';
                categorySidebar.style.display = '';
                stageContainer.classList.remove('active');
                stageContainer.innerHTML = '';
                
                hideStageMessage();
            }
        });

        function renderStageView() {
            const container = document.getElementById('stageViewContainer');
            const stageNames = Object.keys(solvingStages);
            
            container.innerHTML = stageNames.map((stageName, idx) => {
                const stage = solvingStages[stageName];
                const stageNum = idx + 1;
                const stagePersonas = stage.ids.map(id => personas.find(p => p.id === id)).filter(Boolean);
                
                return `
                    <div class="stage-section">
                        <div class="stage-header">
                            <div class="stage-icon stage-${stageNum}">${stage.icon}</div>
                            <div>
                                <div class="stage-title">${stageNum}. ${stageName}</div>
                                <div class="stage-subtitle">${stage.subtitle}</div>
                            </div>
                            <div class="stage-count">${stagePersonas.length}개 페르소나</div>
                        </div>
                        <div class="stage-cards">
                            ${stagePersonas.map(p => `
                                <div class="persona-card ${conqueredSet.has(p.id) ? 'conquered' : ''}" 
                                     data-id="${p.id}" data-category="${p.category}" data-priority="${p.priority}">
                                    <div class="persona-priority ${p.priority}"></div>
                                    <span class="stage-persona-badge stage-${stageNum}">${stageName}</span>
                                    <div class="persona-icon">${p.icon}</div>
                                    <div class="persona-id">#${String(p.id).padStart(2, '0')}</div>
                                    <div class="persona-name">${p.name}</div>
                                    <div class="persona-category">${p.category}</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }).join('');
            
            // 카드 클릭 이벤트 바인딩
            container.querySelectorAll('.persona-card').forEach(card => {
                card.addEventListener('click', () => {
                    openDetail(parseInt(card.dataset.id));
                });
            });
        }

        function showStageMessage() {
            let msgBox = document.getElementById('stageMessage');
            if (!msgBox) {
                msgBox = document.createElement('div');
                msgBox.id = 'stageMessage';
                msgBox.style.cssText = 'position:fixed;top:80px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,rgba(6,182,212,0.95),rgba(8,145,178,0.95));color:white;padding:1rem 2rem;border-radius:1rem;z-index:200;text-align:center;max-width:700px;box-shadow:0 10px 40px rgba(0,0,0,0.3);';
                document.body.appendChild(msgBox);
            }
            msgBox.innerHTML = `
                <div style="font-weight:bold;font-size:1.125rem;margin-bottom:0.5rem;">📊 풀이 단계별 페르소나 보기</div>
                <div style="font-size:0.875rem;line-height:1.6;">
                    수학 문제 풀이의 5단계에 맞춰 페르소나를 분류했습니다.<br>
                    <strong>📖 문제해석 → 🚀 식세우기 → ✏️ 풀이과정 → 🔍 점검 → 🏁 장기기억화</strong><br>
                    <span style="opacity:0.8;">각 단계에서 발생하는 인지관성을 확인하고 정복해보세요!</span>
                </div>
                <button onclick="hideStageMessage()" style="margin-top:0.75rem;background:rgba(0,0,0,0.2);border:none;color:white;padding:0.375rem 1rem;border-radius:0.5rem;cursor:pointer;font-size:0.75rem;">확인</button>
            `;
            msgBox.style.display = 'block';
        }

        function hideStageMessage() {
            const msgBox = document.getElementById('stageMessage');
            if (msgBox) msgBox.style.display = 'none';
        }
    </script>
</body>
</html>

