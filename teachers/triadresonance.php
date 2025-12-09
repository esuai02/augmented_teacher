<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
 

// 필수 변수 초기화
if (!isset($teacherid)) {
    $teacherid = isset($_GET['teacherid']) ? intval($_GET['teacherid']) : 0;
}

$timecreated = time();

// 오류 디버깅 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 로그 기록 - SQL 인젝션 방지를 위해 매개변수화된 쿼리 사용
if(isset($USER) && isset($USER->id) && $USER->id == $teacherid) {
    $DB->execute("INSERT INTO {abessi_missionlog} (userid,eventid,page,timecreated) VALUES(?, 71, 'chainreaction', ?)", 
        array($USER->id, $timecreated));
}

$tlastaccess = $timecreated - 604800*30;
require_login();
$halfdayago = $timecreated - 43200;
$aweekago = $timecreated - 604800;
$amonthago6 = $timecreated - 604800*30;
$timestart = date("Y-m-d", $timecreated);
$minutes5 = $timecreated - 300;

// 사용자 정보 가져오기 - 안전한 쿼리
$username = null;
if ($teacherid > 0) {
    $username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($teacherid));
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>연계 피드백 시스템</title>
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #475569;
            --bg: #f8fafc;
            --card: #ffffff;
            --accent: #16a34a;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --focus: #cbd5e1;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        .card {
            background: var(--card);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
            border: 1px solid var(--border);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.75rem;
        }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            background: var(--secondary);
            color: white;
        }
        .badge-primary {
            background: var(--primary);
        }
        .badge-accent {
            background: var(--accent);
        }
        .badge-pending {
            background: #facc15;
            color: #1e293b;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1rem;
        }
        .col-8 {
            grid-column: span 8;
        }
        .col-4 {
            grid-column: span 4;
        }
        .progress-line {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 2rem 0;
        }
        .progress-point {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--secondary);
            border: 2px solid var(--card);
            z-index: 1;
        }
        .progress-point.active {
            background-color: var(--primary);
        }
        .progress-point.completed {
            background-color: var(--accent);
        }
        .progress-line::before {
            content: '';
            position: absolute;
            top: 5px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--secondary);
            z-index: 0;
        }
        .feedback-item {
            border-left: 3px solid var(--border);
            padding-left: 0.75rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .feedback-item.current {
            border-left-color: var(--primary);
        }
        .feedback-item.completed {
            border-left-color: var(--accent);
        }
        .feedback-entry {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background-color: var(--bg);
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .skill-item {
            display: flex;
            align-items: center;
            font-size: 0.8rem;
        }
        .rating {
            display: flex;
            gap: 2px;
            margin-left: auto;
        }
        .rating-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--focus);
        }
        .rating-dot.active {
            background-color: var(--primary);
        }
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            resize: vertical;
            font-family: inherit;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            background-color: var(--bg);
        }
        textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        .btn {
            padding: 0.5rem 1rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-small {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .btn-accent {
            background-color: var(--accent);
        }
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        .flex {
            display: flex;
        }
        .flex-col {
            flex-direction: column;
        }
        .justify-between {
            justify-content: space-between;
        }
        .items-center {
            align-items: center;
        }
        .mt-2 {
            margin-top: 1rem;
        }
        .mb-4 {
            margin-bottom: 1rem;
        }
        .gap-2 {
            gap: 0.5rem;
        }
        .text-sm {
            font-size: 0.85rem;
        }
        .text-xs {
            font-size: 0.75rem;
        }
        .text-secondary {
            color: var(--text-light);
        }
        .insight-card {
            border-left: 3px solid var(--primary);
            padding: 0.75rem;
            background-color: rgba(37, 99, 235, 0.05);
            border-radius: 0 4px 4px 0;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
        }
        .insight-title {
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        .teacher-sync {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .teacher-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .sync-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--accent);
            margin-left: 0.25rem;
        }
        .tab-container {
            display: flex;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }
        .tab {
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-size: 0.85rem;
            border-bottom: 2px solid transparent;
        }
        .tab.active {
            border-bottom-color: var(--primary);
            font-weight: 600;
        }
        .sync-message {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            background-color: rgba(22, 163, 74, 0.1);
            border-radius: 4px;
            color: var(--accent);
            margin-top: 0.5rem;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1 style="font-size: 1.25rem; font-weight: 600;">연계 학습 시스템</h1> 
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary">수학 문제 풀이</span>
                <span class="badge">고3 김민준</span>
            </div>
        </div>

        <div class="grid">
            <div class="col-8">
                <div class="card">
                    <div class="title">피드백 진행 과정</div>
                    <div class="progress-line">
                        <div class="progress-point completed" title="이민정 선생님"></div>
                        <div class="progress-point active" title="박지훈 선생님"></div>
                        <div class="progress-point" title="최우진 선생님"></div>
                        <div class="progress-point" title="유다정 선생님"></div>
                        <div class="progress-point" title="김상현 선생님"></div>
                        <div class="progress-point" title="원장 선생님"></div>
                    </div>
                    
                    <div class="tab-container">
                        <div class="tab active">피드백 흐름</div>
                        <div class="tab">문제 풀이 과정</div>
                        <div class="tab">변화 추적</div>
                    </div>
                    
                    <div class="feedback-item completed">
                        <div class="flex justify-between">
                            <div>
                                <strong>이민정 선생님</strong>
                                <span class="badge badge-accent text-xs">완료</span>
                            </div>
                            <span class="text-xs text-secondary">14:15</span>
                        </div>
                        <div class="feedback-entry">
                            논리적 사고력과 문제 해결 접근 방식이 우수함. 개념 이해 명확하나 실수 자주 발생.
                            <div class="sync-message">분석적 사고 → 실수 예방 필요</div>
                        </div>
                        <div class="skills-grid mt-2">
                            <div class="skill-item">
                                <span>개념 이해력</span>
                                <div class="rating">
                                    <span class="rating-dot active"></span>
                                    <span class="rating-dot active"></span>
                                    <span class="rating-dot active"></span>
                                    <span class="rating-dot active"></span>
                                    <span class="rating-dot"></span>
                                </div>
                            </div>
                            <div class="skill-item">
                                <span>문제 해석력</span>
                                <div class="rating">
                                    <span class="rating-dot active"></span>
                                    <span class="rating-dot active"></span>
                                    <span class="rating-dot active"></span>
                                    <span class="rating-dot active"></span>
                                    <span class="rating-dot"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="feedback-item current">
                        <div class="flex justify-between">
                            <div>
                                <strong>박지훈 선생님</strong>
                                <span class="badge badge-pending text-xs">진행중</span>
                            </div>
                            <span class="text-xs text-secondary">14:22 (현재)</span>
                        </div>
                        <div class="feedback-entry">
                            <div class="text-secondary text-xs">이전 피드백 연계 포인트:</div>
                            실수 패턴을 집중 관찰. 불안감으로 인한 계산 오류 발생. 다항식 인수분해 과정에서 중간 단계 생략 경향.
                        </div>
                        <div class="mt-2">
                            <div class="title">피드백 입력:</div>
                            <div class="skills-grid">
                                <div class="skill-item">
                                    <span>계산 정확도</span>
                                    <div class="rating">
                                        <span class="rating-dot active"></span>
                                        <span class="rating-dot active"></span>
                                        <span class="rating-dot"></span>
                                        <span class="rating-dot"></span>
                                        <span class="rating-dot"></span>
                                    </div>
                                </div>
                                <div class="skill-item">
                                    <span>중간과정 표현</span>
                                    <div class="rating">
                                        <span class="rating-dot active"></span>
                                        <span class="rating-dot active"></span>
                                        <span class="rating-dot active"></span>
                                        <span class="rating-dot"></span>
                                        <span class="rating-dot"></span>
                                    </div>
                                </div>
                            </div>
                            <textarea rows="3" placeholder="피드백 입력...">설명 요청 시 살짝 당황하였으나, 힌트 후 빠르게 회복. 기초 개념은 충분하나 자신감 문제로 계산 실수 반복. 중간과정 기록 필요.</textarea>
                            <div class="flex justify-between mt-2">
                                <div class="teacher-sync">
                                    <div class="teacher-avatar">이</div>
                                    <div class="teacher-avatar">최</div>
                                    <span class="text-xs text-secondary">실시간 공유 <span class="sync-indicator"></span></span>
                                </div>
                                <button class="btn">다음 선생님에게 전달</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="feedback-item">
                        <div class="flex justify-between">
                            <div>
                                <strong>최우진 선생님</strong>
                                <span class="badge text-xs">대기중</span>
                            </div>
                            <span class="text-xs text-secondary">예정</span>
                        </div>
                        <div class="text-xs text-secondary">이전 선생님의 피드백 수신 대기중...</div>
                    </div>
                </div>
            </div>
            
            <div class="col-4">
                <div class="card">
                    <div class="title">학생 정보</div>
                    <div class="text-sm">
                        <div class="flex justify-between mb-4">
                            <span>최근 성적:</span>
                            <span>수학 92점</span>
                        </div>
                        <div class="flex justify-between mb-4">
                            <span>취약 영역:</span>
                            <span>다항식 계산, 인수분해</span>
                        </div>
                        <div class="flex justify-between">
                            <span>심리 상태:</span>
                            <span>시험 불안, 기복형</span>
                        </div>
                    </div>
                    <br>
                    <hr>
                    <div class="title mt-2">현재 풀이 중인 문제</div>
                    <div class="text-sm">
                        <pre style="background: var(--bg); padding: 0.5rem; border-radius: 4px; overflow-x: auto; font-size: 0.8rem;">x^2 - 5x + 6 = 0 의 해를 구하고,
이를 이용하여 f(x) = x^3 - 5x^2 + 6x - 3
의 인수분해 과정 증명</pre>
                    </div>
                    
                    <div class="title mt-2">연계 피드백 요약</div>
                    <div class="insight-card">
                        <p><strong>핵심 개선점:</strong> 계산 실수 줄이기 위한 중간과정 작성</p>
                        <p><strong>강점:</strong> 문제 해석력과 개념 이해도 우수</p>
                        <p><strong>교정 방향:</strong> 자신감 향상 → 정확한 계산력 강화</p>
                    </div>
                    
                    <div class="flex justify-between mt-2">
                        <button class="btn btn-outline btn-small">문제 변경</button>
                        <button class="btn btn-small">문제 풀이 과정 보기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
