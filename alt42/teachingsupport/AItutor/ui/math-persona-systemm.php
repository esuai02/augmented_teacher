<?php
/**
 * 📚 수학 인지관성 도감 - 모바일 버전
 * 60개의 인지 페르소나를 정복해 나가는 인터페이스
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// URL에 id가 없으면 로그인 사용자 정보 사용
$studentId = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : $USER->id;
$userId = $USER->id;

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid='22'", [$userId]);
$role = $userrole->data ?? 'student';

// Get student info
$student = $DB->get_record('user', array('id' => $studentId));
$studentname = $student ? $student->firstname . $student->lastname : '';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="student-id" content="<?php echo htmlspecialchars($studentId); ?>">
    <title>📚 인지관성 도감</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            color: #e0e0e0;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        .header {
            background: rgba(0, 0, 0, 0.3);
            padding: 16px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        .header h1 {
            font-size: 18px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 12px;
        }
        .stat-item { text-align: center; }
        .stat-value { font-size: 20px; font-weight: 700; color: #667eea; }
        .stat-label { font-size: 10px; color: #9ca3af; }
        .content { padding: 16px; }
        .category-tabs {
            display: flex;
            overflow-x: auto;
            gap: 8px;
            padding-bottom: 12px;
            -webkit-overflow-scrolling: touch;
        }
        .category-tab {
            flex-shrink: 0;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 20px;
            color: #9ca3af;
            font-size: 12px;
            cursor: pointer;
            white-space: nowrap;
        }
        .category-tab.active {
            background: #667eea;
            color: white;
        }
        .persona-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .persona-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        .persona-card:active {
            transform: scale(0.98);
        }
        .persona-card.conquered {
            background: linear-gradient(145deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.05));
            border-color: #10b981;
        }
        .persona-card.conquered::after {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 8px;
            background: #10b981;
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .persona-icon { font-size: 32px; margin-bottom: 8px; }
        .persona-id { font-size: 10px; color: #9ca3af; }
        .persona-name {
            font-size: 12px;
            font-weight: 600;
            color: #f3f4f6;
            margin-top: 4px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .priority-dot {
            position: absolute;
            top: 8px;
            left: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .priority-dot.high { background: #ef4444; }
        .priority-dot.medium { background: #f59e0b; }
        .priority-dot.low { background: #10b981; }
        .progress-bar {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px 16px;
            position: fixed;
            bottom: 60px;
            left: 0;
            right: 0;
        }
        .progress-track {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2, #10b981);
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #9ca3af;
            margin-top: 4px;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 12, 41, 0.95);
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
            z-index: 100;
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #666;
            font-size: 10px;
            padding: 4px 8px;
        }
        .nav-item.active { color: #667eea; }
        .nav-item span { font-size: 20px; margin-bottom: 2px; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 200;
            padding: 16px;
            overflow-y: auto;
        }
        .modal.open { display: block; }
        .modal-content {
            background: linear-gradient(145deg, #1a1a2e, #16213e);
            border-radius: 16px;
            padding: 20px;
            margin-top: 40px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
        }
        .modal-header {
            text-align: center;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 16px;
        }
        .modal-icon { font-size: 48px; margin-bottom: 8px; }
        .modal-title { font-size: 18px; font-weight: 700; }
        .modal-desc { font-size: 13px; color: #9ca3af; margin-top: 8px; line-height: 1.5; }
        .modal-section {
            margin-bottom: 16px;
        }
        .modal-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 8px;
        }
        .modal-section-content {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.6;
        }
        .conquer-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
        }
        .conquer-btn.conquered {
            background: #10b981;
            cursor: default;
        }
        
        /* 세로 방향 (Portrait) */
        @media screen and (orientation: portrait) {
            .persona-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            .persona-card {
                padding: 20px;
            }
            .persona-icon { font-size: 40px; margin-bottom: 12px; }
            .persona-id { font-size: 11px; }
            .persona-name { font-size: 14px; -webkit-line-clamp: 3; }
            .header h1 { font-size: 20px; }
            .stats-bar { gap: 32px; margin-top: 16px; }
            .stat-value { font-size: 24px; }
            .stat-label { font-size: 12px; }
            .category-tabs { gap: 10px; padding-bottom: 16px; }
            .category-tab { padding: 10px 18px; font-size: 13px; }
            .content { padding: 16px; }
            .modal-content { padding: 24px; margin-top: 60px; }
            .modal-icon { font-size: 56px; }
            .modal-title { font-size: 20px; }
            .modal-desc { font-size: 14px; }
            .modal-section-title { font-size: 16px; }
            .modal-section-content { font-size: 14px; padding: 16px; }
        }
        
        /* 가로 방향 (Landscape) */
        @media screen and (orientation: landscape) {
            .persona-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
            }
            .persona-card { padding: 12px; }
            .persona-icon { font-size: 28px; margin-bottom: 6px; }
            .persona-name { font-size: 11px; }
            .header { padding: 12px; }
            .header h1 { font-size: 16px; }
            .stats-bar { margin-top: 8px; }
            .stat-value { font-size: 18px; }
            .category-tabs { padding-bottom: 10px; }
            .category-tab { padding: 6px 12px; font-size: 11px; }
            .content { padding: 10px; }
            .progress-bar { bottom: 50px; padding: 8px 12px; }
            body { padding-bottom: 70px; }
            .bottom-nav { padding: 4px 0; }
            .nav-item { font-size: 9px; padding: 2px 6px; }
            .nav-item span { font-size: 16px; }
            .modal-content { margin-top: 20px; padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📚 수학 인지관성 도감</h1>
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value" id="conqueredCount">0</div>
                <div class="stat-label">정복</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">60</div>
                <div class="stat-label">전체</div>
            </div>
        </div>
    </div>
    
    <div class="content">
        <div class="category-tabs">
            <button class="category-tab active" data-category="all">전체</button>
            <button class="category-tab" data-category="인지 과부하">🧠 인지 과부하</button>
            <button class="category-tab" data-category="자신감 왜곡">😰 자신감</button>
            <button class="category-tab" data-category="실수 패턴">⚡ 실수</button>
            <button class="category-tab" data-category="접근 전략 오류">🎯 전략</button>
            <button class="category-tab" data-category="학습 습관">📚 습관</button>
            <button class="category-tab" data-category="시간/압박 관리">⏰ 시간</button>
            <button class="category-tab" data-category="검증/확인 부재">✔️ 검증</button>
        </div>
        
        <div class="persona-grid" id="personaGrid"></div>
    </div>
    
    <div class="progress-bar">
        <div class="progress-track">
            <div class="progress-fill" id="progressFill" style="width: 0%"></div>
        </div>
        <div class="progress-text">
            <span>정복 진행률</span>
            <span id="progressPercent">0%</span>
        </div>
    </div>
    
    <!-- 하단 네비게이션 -->
    <div class="bottom-nav">
        <a href="../../../../students/index42m.php?id=<?php echo $studentId; ?>" class="nav-item">
            <span>🏠</span>홈
        </a>
        <a href="../../../../students/today42m.php?id=<?php echo $studentId; ?>" class="nav-item">
            <span>📝</span>오늘
        </a>
        <a href="../../../../students/schedule42m.php?id=<?php echo $studentId; ?>" class="nav-item">
            <span>📅</span>일정
        </a>
        <a href="../../../../students/goals42m.php?id=<?php echo $studentId; ?>" class="nav-item">
            <span>🎯</span>목표
        </a>
        <a href="../../student_inboxm.php?studentid=<?php echo $studentId; ?>" class="nav-item">
            <span>📩</span>메세지
        </a>
        <a href="math-persona-systemm.php?id=<?php echo $studentId; ?>" class="nav-item active">
            <span>🤖</span>AI
        </a>
    </div>
    
    <!-- Modal -->
    <div class="modal" id="detailModal">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" id="modalIcon">🧠</div>
                <div class="modal-title" id="modalTitle">페르소나 이름</div>
                <div class="modal-desc" id="modalDesc">설명</div>
            </div>
            <div class="modal-section">
                <div class="modal-section-title">🎯 해결 전략</div>
                <div class="modal-section-content" id="modalAction"></div>
            </div>
            <div class="modal-section">
                <div class="modal-section-title">✅ 확인 포인트</div>
                <div class="modal-section-content" id="modalCheck"></div>
            </div>
            <button class="conquer-btn" id="conquerBtn" onclick="conquerPersona()">🏆 이 패턴 정복하기</button>
        </div>
    </div>
    
    <script>
    const studentId = <?php echo json_encode($studentId); ?>;
    let conqueredSet = new Set();
    let currentPersonaId = null;
    
    // 60개 페르소나 데이터 (축약)
    const personas = [
        {id:1,name:"아이디어 해방 자동발화형",desc:"번쩍이는 아이디어가 떠오르면 검증 없이 바로 써 내려가 결국 오답을 양산하는 패턴.",category:"인지 과부하",icon:"🧠",priority:"high",solution:{action:"아이디어가 떠오르면 5초 멈춤 → 아이디어를 한 줄로 요약 후, '약점 가설' 1개를 곧바로 적는다",check:"5초 멈춤→가설 쓰기 루틴을 세 번 성공했는지 확인"}},
        {id:2,name:"3초 패배 예감형",desc:"'못 풀 것 같다'는 느낌이 3초 만에 뇌를 잠그고, 관련 개념 연결이 끊어지는 패턴.",category:"자신감 왜곡",icon:"😰",priority:"high",solution:{action:"'포기 신호'를 감지하면 3분 타이머를 켜고 문제 해석을 처음부터 다시 적는다",check:"'3분 재해석' 루틴을 두 번 사용했는지 검토"}},
        {id:3,name:"과신-시야 협착형",desc:"과한 자신감으로 숫자·기호의 미세한 차이를 인식하지 못하는 패턴.",category:"자신감 왜곡",icon:"🎯",priority:"medium",solution:{action:"풀이 착수 전 심호흡 10회 → 비슷한 기호·수치를 색펜으로 구분 표시",check:"색펜 표시한 부분에서 놓친 차이가 있었는지 확인"}},
        {id:4,name:"무의식 연쇄 실수형",desc:"손이 먼저 움직여 사소한 계산 실수가 꼬리를 무는 패턴.",category:"실수 패턴",icon:"⚡",priority:"high",solution:{action:"숫자 한 줄 쓸 때마다 펜을 내려놓고 1초 휴식",check:"어제 적은 실수 장면을 보여드릴 때 피드백"}},
        {id:5,name:"모순 확신-답불가형",desc:"'틀린 곳이 없다'는 집착으로 시야가 좁아져 교정을 못 하는 패턴.",category:"자신감 왜곡",icon:"🔒",priority:"medium",solution:{action:"답이 안 나올 때 '간단 실수 90%' 문장을 써서 관점을 전환",check:"'간단 실수 게임'으로 찾은 오류 검산"}},
        {id:6,name:"작업기억 ⅔ 할당형",desc:"다음 일정·잡생각이 머릿속을 스치며 2/3만 집중하는 패턴.",category:"인지 과부하",icon:"🧩",priority:"high",solution:{action:"떠오른 일정은 포스트잇에 적고 덮어두기 → 25분 집중 / 5분 휴식",check:"25분 집중 세션 3번 돌렸는지 확인"}},
        {id:7,name:"반(半)포기 창의 탐색형",desc:"'어차피 틀릴 것'이라며 낮은 확률의 창의 풀이만 헤매는 패턴.",category:"접근 전략 오류",icon:"🎨",priority:"medium",solution:{action:"정석 접근 A안을 먼저 10분 시도 → 실패 시 A안 문제점 1줄 정리",check:"A안 10분, B안 5분 전략으로 풀어봤는지 확인"}},
        {id:8,name:"해설지-혼합 착각형",desc:"내 생각과 해설 내용을 섞어 쓰다 근거가 뒤섞이는 패턴.",category:"학습 습관",icon:"📖",priority:"medium",solution:{action:"내 풀이=파란색, 해설=빨간색 두 색깔 분리 기록",check:"파란·빨간 차이 두 가지 설명"}},
        {id:9,name:"연습 회피 관성형",desc:"'이해했어' 착각으로 반복 연습을 건너뛰고 넘어가는 패턴.",category:"학습 습관",icon:"🏃",priority:"high",solution:{action:"새 개념 배우면 즉시 난이도 Low·Mid·High 1문제씩 풀기",check:"Low·Mid·High 3문제 중 어떤 것을 틀렸는지 확인"}},
        {id:10,name:"불확실 강행형",desc:"근거 부족인데도 '일단 적용'해서 오류가 연쇄되는 패턴.",category:"접근 전략 오류",icon:"🎲",priority:"medium",solution:{action:"근거 약하면 노란 포스트잇에 '확신 ★☆☆' 등급 표시",check:"노란 포스트잇으로 ★ 표시한 부분 같이 검산"}},
        {id:11,name:"속도 압박 억제형",desc:"시험 시간이 눈에 들어올 때마다 압박이 새 아이디어를 눌러 버리는 패턴.",category:"시간/압박 관리",icon:"⏰",priority:"high",solution:{action:"시작과 동시에 시계 뒤집기 → 15분 간격 진동 타이머",check:"15분 타이머를 4번 돌렸는지 확인"}},
        {id:12,name:"시험 트라우마 악수형",desc:"과거에 시험을 망친 기억이 문제 순서·전략에 투영돼 '악수'를 두는 패턴.",category:"시간/압박 관리",icon:"💔",priority:"high",solution:{action:"시작 2분 내에 '가장 쉬운 2문제'를 골라 먼저 해결",check:"Easy-Start 전략으로 첫 2문제를 풀었는지 확인"}},
        {id:13,name:"징검다리 난도적형",desc:"청킹 없이 산발적으로 추론해 전역 구조를 놓치는 패턴.",category:"접근 전략 오류",icon:"🪨",priority:"medium",solution:{action:"문제를 3~4개 '청크'로 나누고 각 단계에 번호 붙이기",check:"청크 3단계를 거꾸로 리뷰했는지 확인"}},
        {id:14,name:"무의식 재현 루프형",desc:"예전에 성공했던 공식을 맹목적으로 재사용하는 패턴.",category:"학습 습관",icon:"🔄",priority:"low",solution:{action:"공식 사용할 때 '조건 동일?' 체크박스를 옆에 그리기",check:"조건 체크박스를 5번 그렸는지 확인"}},
        {id:15,name:"조건 회피-추론 생략형",desc:"복잡한 조건을 '시야 밖'으로 밀어두고 직감만으로 추론하는 패턴.",category:"검증/확인 부재",icon:"👁️",priority:"high",solution:{action:"문제의 각 조건 옆에 ✔︎를 표시하고 한글로 5-7단어 요약",check:"초록으로 바뀌지 않은 조건이 남았는지 확인"}}
    ];

    // 나머지 페르소나 추가 (간략화)
    for(let i = 16; i <= 60; i++) {
        const categories = ["인지 과부하", "자신감 왜곡", "실수 패턴", "접근 전략 오류", "학습 습관", "시간/압박 관리", "검증/확인 부재", "기타 장애"];
        const icons = ["🧠", "😰", "⚡", "🎯", "📖", "⏰", "✔️", "🔧"];
        const priorities = ["high", "medium", "low"];
        personas.push({
            id: i,
            name: `인지패턴 ${i}`,
            desc: `인지관성 패턴 ${i}번에 대한 설명입니다.`,
            category: categories[i % 8],
            icon: icons[i % 8],
            priority: priorities[i % 3],
            solution: {
                action: "해결 전략을 실행해보세요.",
                check: "확인 포인트를 점검해보세요."
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderCards();
        loadProgress();
        bindEvents();
    });

    function renderCards(category = 'all') {
        const grid = document.getElementById('personaGrid');
        const filtered = category === 'all' ? personas : personas.filter(p => p.category === category);
        
        grid.innerHTML = filtered.map(p => `
            <div class="persona-card ${conqueredSet.has(p.id) ? 'conquered' : ''}" data-id="${p.id}">
                <div class="priority-dot ${p.priority}"></div>
                <div class="persona-icon">${p.icon}</div>
                <div class="persona-id">#${String(p.id).padStart(2, '0')}</div>
                <div class="persona-name">${p.name}</div>
            </div>
        `).join('');
    }

    function loadProgress() {
        const saved = localStorage.getItem(`persona_progress_${studentId}`);
        if (saved) {
            conqueredSet = new Set(JSON.parse(saved));
            updateUI();
        }
    }

    function saveProgress() {
        localStorage.setItem(`persona_progress_${studentId}`, JSON.stringify([...conqueredSet]));
        updateUI();
    }

    function updateUI() {
        document.querySelectorAll('.persona-card').forEach(card => {
            const id = parseInt(card.dataset.id);
            card.classList.toggle('conquered', conqueredSet.has(id));
        });
        document.getElementById('conqueredCount').textContent = conqueredSet.size;
        const percent = Math.round((conqueredSet.size / 60) * 100);
        document.getElementById('progressFill').style.width = `${percent}%`;
        document.getElementById('progressPercent').textContent = `${percent}%`;
    }

    function bindEvents() {
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                renderCards(tab.dataset.category);
            });
        });

        document.getElementById('personaGrid').addEventListener('click', (e) => {
            const card = e.target.closest('.persona-card');
            if (card) openModal(parseInt(card.dataset.id));
        });
    }

    function openModal(id) {
        currentPersonaId = id;
        const p = personas.find(x => x.id === id);
        if (!p) return;

        document.getElementById('modalIcon').textContent = p.icon;
        document.getElementById('modalTitle').textContent = p.name;
        document.getElementById('modalDesc').textContent = p.desc;
        document.getElementById('modalAction').textContent = p.solution?.action || '';
        document.getElementById('modalCheck').textContent = p.solution?.check || '';

        const btn = document.getElementById('conquerBtn');
        if (conqueredSet.has(id)) {
            btn.textContent = '✓ 정복 완료!';
            btn.classList.add('conquered');
        } else {
            btn.textContent = '🏆 이 패턴 정복하기';
            btn.classList.remove('conquered');
        }

        document.getElementById('detailModal').classList.add('open');
    }

    function closeModal() {
        document.getElementById('detailModal').classList.remove('open');
    }

    function conquerPersona() {
        if (!currentPersonaId || conqueredSet.has(currentPersonaId)) return;
        conqueredSet.add(currentPersonaId);
        saveProgress();
        
        const btn = document.getElementById('conquerBtn');
        btn.textContent = '🎉 정복 완료!';
        btn.classList.add('conquered');
        
        renderCards(document.querySelector('.category-tab.active').dataset.category);
    }

    // Modal 외부 클릭시 닫기
    document.getElementById('detailModal').addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) closeModal();
    });
    </script>
</body>
</html>

