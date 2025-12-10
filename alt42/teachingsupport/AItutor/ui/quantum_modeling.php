<?php
/**
 * 양자 모델링 인터페이스
 * ID로 컨텐츠 정보를 받아 문제/해설 이미지 표시
 * OpenAI API로 다양한 문제풀이 및 오개념 풀이 탐색
 * 탐색 결과를 DB에 저장하여 양자 붕괴 회로 업데이트
 *
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 *
 * 관련 DB 테이블:
 * - mdl_question (id, questiontext, generalfeedback)
 * - mdl_abessi_messages (wboardid, contentsid, tlaststroke)
 * - mdl_alt42_quantum_solutions (id, content_id, solution_type, solution_data, created_at)
 * - mdl_alt42_quantum_misconceptions (id, content_id, misconception_type, misconception_data, created_at)
 * - mdl_alt42_quantum_collapse_circuit (id, content_id, circuit_state, last_updated)
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(__DIR__ . '/../../../config.php'); // OpenAI API 키 설정
require_once(__DIR__ . '/../includes/db_manager.php');

// URL 파라미터에서 ID 추출
$fullId = $_GET['id'] ?? null;
$studentId = $USER->id;

if (!$fullId) {
    die("오류: ID 파라미터가 필요합니다. (quantum_modeling.php)");
}

// ID 파싱: Q7MQFA3856470tsDoHfRT_user1831_2025_12_10 형식
$idParts = explode('_user', $fullId);
$wboardId = $idParts[0] ?? $fullId;
$userPart = $idParts[1] ?? '';

// 사용자 ID 추출 (user1831_2025_12_10 → 1831)
if (preg_match('/^(\d+)/', $userPart, $matches)) {
    $studentId = intval($matches[1]);
}

// wboardId로 컨텐츠 정보 조회
$thisboard = $DB->get_record_sql(
    "SELECT * FROM mdl_abessi_messages WHERE wboardid = ? ORDER BY tlaststroke DESC LIMIT 1",
    [$wboardId]
);

$contentId = $thisboard->contentsid ?? null;

// 문제/해설 이미지 추출
$imgSrc1 = null; // 해설 이미지
$imgSrc2 = null; // 문제 이미지
$questionText = '';
$solutionText = '';

if ($contentId) {
    $qtext = $DB->get_record_sql(
        "SELECT questiontext, generalfeedback FROM mdl_question WHERE id = ? LIMIT 1",
        [$contentId]
    );

    if ($qtext) {
        $questionText = $qtext->questiontext;
        $solutionText = $qtext->generalfeedback;

        // 해설 이미지 추출
        $htmlDom1 = new DOMDocument;
        @$htmlDom1->loadHTML($qtext->generalfeedback);
        $imageTags1 = $htmlDom1->getElementsByTagName('img');
        foreach ($imageTags1 as $imageTag1) {
            $imgSrc1 = $imageTag1->getAttribute('src');
            $imgSrc1 = str_replace(' ', '%20', $imgSrc1);
            if (strpos($imgSrc1, 'MATRIX/MATH') !== false && strpos($imgSrc1, 'hintimages') === false) break;
        }

        // 문제 이미지 추출
        $htmlDom2 = new DOMDocument;
        @$htmlDom2->loadHTML($qtext->questiontext);
        $imageTags2 = $htmlDom2->getElementsByTagName('img');
        foreach ($imageTags2 as $imageTag2) {
            $imgSrc2 = $imageTag2->getAttribute('src');
            $imgSrc2 = str_replace(' ', '%20', $imgSrc2);
            if (strpos($imgSrc2, 'hintimages') === false && (strpos($imgSrc2, '.png') !== false || strpos($imgSrc2, '.jpg') !== false)) break;
        }
    }
}

// 기존 양자 모델링 데이터 조회
$existingSolutions = [];
$existingMisconceptions = [];
$quantumCircuit = null;

try {
    // 기존 풀이 방법 조회
    $existingSolutions = $DB->get_records_sql(
        "SELECT * FROM {alt42_quantum_solutions} WHERE content_id = ? ORDER BY created_at DESC",
        [$contentId]
    );

    // 기존 오개념 조회
    $existingMisconceptions = $DB->get_records_sql(
        "SELECT * FROM {alt42_quantum_misconceptions} WHERE content_id = ? ORDER BY created_at DESC",
        [$contentId]
    );

    // 양자 붕괴 회로 상태 조회
    $quantumCircuit = $DB->get_record_sql(
        "SELECT * FROM {alt42_quantum_collapse_circuit} WHERE content_id = ? LIMIT 1",
        [$contentId]
    );
} catch (Exception $e) {
    error_log("[quantum_modeling.php] DB 조회 오류: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>양자 모델링 - 문제풀이 탐색</title>
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* 헤더 */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-title h1 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .quantum-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .header-info {
            display: flex;
            gap: 20px;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* 메인 레이아웃 */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
        }

        /* 카드 스타일 */
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            background: rgba(99, 102, 241, 0.05);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        /* 이미지 컨테이너 */
        .image-container {
            position: relative;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-container img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
        }

        .image-placeholder {
            color: var(--text-secondary);
            text-align: center;
        }

        /* 탭 네비게이션 */
        .tabs {
            display: flex;
            gap: 4px;
            padding: 4px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .tab-btn {
            flex: 1;
            padding: 10px 16px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .tab-btn:hover:not(.active) {
            background: rgba(99, 102, 241, 0.2);
        }

        /* 탐색 버튼 */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 결과 영역 */
        .results-area {
            min-height: 200px;
        }

        .result-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border-left: 3px solid var(--primary-color);
        }

        .result-item.misconception {
            border-left-color: var(--danger-color);
        }

        .result-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .result-item-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .result-item-meta {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .result-item-content {
            font-size: 0.875rem;
            line-height: 1.6;
            color: var(--text-secondary);
            white-space: pre-wrap;
        }

        /* 양자 붕괴 회로 시각화 */
        .quantum-circuit {
            background: rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .circuit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .circuit-title {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .circuit-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .circuit-status.active {
            background: var(--success-color);
            color: white;
        }

        .circuit-status.pending {
            background: var(--warning-color);
            color: white;
        }

        .circuit-visualization {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .circuit-node {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .circuit-node.active {
            background: var(--primary-color);
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.6);
        }

        .circuit-node.collapsed {
            background: var(--success-color);
        }

        .circuit-connector {
            width: 20px;
            height: 2px;
            background: var(--border-color);
        }

        .circuit-connector.active {
            background: var(--primary-color);
        }

        /* 빈 상태 */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* 로딩 오버레이 */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            z-index: 10;
        }

        .loading-text {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <header class="header">
            <div class="header-title">
                <h1>🔮 양자 모델링</h1>
                <span class="quantum-badge">Quantum Collapse Circuit</span>
            </div>
            <div class="header-info">
                <span>📝 Content ID: <?php echo htmlspecialchars($contentId ?? 'N/A'); ?></span>
                <span>👤 Student: <?php echo htmlspecialchars($studentId); ?></span>
                <span>🔗 <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/" target="_blank" style="color: var(--primary-color);">Orchestration</a></span>
            </div>
        </header>

        <!-- 메인 레이아웃 -->
        <div class="main-layout">
            <!-- 좌측: 문제/해설 이미지 -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>📚</span>
                        <span>문제 / 해설</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="tabs">
                        <button class="tab-btn active" data-tab="question" onclick="switchImageTab('question')">문제</button>
                        <button class="tab-btn" data-tab="solution" onclick="switchImageTab('solution')">해설</button>
                    </div>

                    <div id="questionImageContainer" class="image-container">
                        <?php if ($imgSrc2): ?>
                            <img src="<?php echo htmlspecialchars($imgSrc2); ?>" alt="문제 이미지" id="questionImage">
                        <?php else: ?>
                            <div class="image-placeholder">
                                <div class="empty-state-icon">📷</div>
                                <p>문제 이미지가 없습니다</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="solutionImageContainer" class="image-container hidden">
                        <?php if ($imgSrc1): ?>
                            <img src="<?php echo htmlspecialchars($imgSrc1); ?>" alt="해설 이미지" id="solutionImage">
                        <?php else: ?>
                            <div class="image-placeholder">
                                <div class="empty-state-icon">📷</div>
                                <p>해설 이미지가 없습니다</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 우측: 탐색 결과 -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>🔍</span>
                        <span>탐색 결과</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- 탐색 버튼 -->
                    <div class="action-buttons">
                        <button class="btn btn-primary" id="exploreSolutionsBtn" onclick="exploreSolutions()">
                            <span id="solutionBtnIcon">🧠</span>
                            <span id="solutionBtnText">다양한 풀이 탐색</span>
                            <span id="solutionSpinner" class="spinner hidden"></span>
                        </button>
                        <button class="btn btn-secondary" id="exploreMisconceptionsBtn" onclick="exploreMisconceptions()">
                            <span id="misconceptionBtnIcon">⚠️</span>
                            <span id="misconceptionBtnText">오개념 풀이 탐색</span>
                            <span id="misconceptionSpinner" class="spinner hidden"></span>
                        </button>
                    </div>

                    <!-- 탭 네비게이션 -->
                    <div class="tabs">
                        <button class="tab-btn active" data-tab="solutions" onclick="switchResultTab('solutions')">
                            풀이 방법 (<span id="solutionCount"><?php echo count($existingSolutions); ?></span>)
                        </button>
                        <button class="tab-btn" data-tab="misconceptions" onclick="switchResultTab('misconceptions')">
                            오개념 (<span id="misconceptionCount"><?php echo count($existingMisconceptions); ?></span>)
                        </button>
                    </div>

                    <!-- 풀이 방법 결과 -->
                    <div id="solutionsResults" class="results-area">
                        <?php if (empty($existingSolutions)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">💡</div>
                                <p>"다양한 풀이 탐색" 버튼을 클릭하여<br>여러 가지 풀이 방법을 찾아보세요</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($existingSolutions as $solution): ?>
                                <?php $solutionData = json_decode($solution->solution_data, true); ?>
                                <div class="result-item">
                                    <div class="result-item-header">
                                        <div class="result-item-title">
                                            <span>💡</span>
                                            <span><?php echo htmlspecialchars($solutionData['title'] ?? '풀이 방법'); ?></span>
                                        </div>
                                        <div class="result-item-meta">
                                            <?php echo date('Y-m-d H:i', strtotime($solution->created_at)); ?>
                                        </div>
                                    </div>
                                    <div class="result-item-content">
                                        <?php echo nl2br(htmlspecialchars($solutionData['content'] ?? '')); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- 오개념 결과 -->
                    <div id="misconceptionsResults" class="results-area hidden">
                        <?php if (empty($existingMisconceptions)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">⚠️</div>
                                <p>"오개념 풀이 탐색" 버튼을 클릭하여<br>흔히 하는 실수를 찾아보세요</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($existingMisconceptions as $misconception): ?>
                                <?php $misconceptionData = json_decode($misconception->misconception_data, true); ?>
                                <div class="result-item misconception">
                                    <div class="result-item-header">
                                        <div class="result-item-title">
                                            <span>⚠️</span>
                                            <span><?php echo htmlspecialchars($misconceptionData['title'] ?? '오개념'); ?></span>
                                        </div>
                                        <div class="result-item-meta">
                                            <?php echo date('Y-m-d H:i', strtotime($misconception->created_at)); ?>
                                        </div>
                                    </div>
                                    <div class="result-item-content">
                                        <?php echo nl2br(htmlspecialchars($misconceptionData['content'] ?? '')); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 양자 붕괴 회로 -->
        <div class="quantum-circuit">
            <div class="circuit-header">
                <div class="circuit-title">
                    <span>⚡</span>
                    <span>양자 붕괴 회로 상태</span>
                </div>
                <span class="circuit-status <?php echo $quantumCircuit ? 'active' : 'pending'; ?>">
                    <?php echo $quantumCircuit ? '활성' : '대기중'; ?>
                </span>
            </div>
            <div class="circuit-visualization" id="circuitVisualization">
                <!-- JavaScript로 동적 생성 -->
                <div class="circuit-node" data-stage="input" title="입력">IN</div>
                <div class="circuit-connector"></div>
                <div class="circuit-node" data-stage="parse" title="분석">PA</div>
                <div class="circuit-connector"></div>
                <div class="circuit-node" data-stage="explore" title="탐색">EX</div>
                <div class="circuit-connector"></div>
                <div class="circuit-node" data-stage="model" title="모델링">MD</div>
                <div class="circuit-connector"></div>
                <div class="circuit-node" data-stage="collapse" title="붕괴">CL</div>
                <div class="circuit-connector"></div>
                <div class="circuit-node" data-stage="output" title="출력">OUT</div>
            </div>
        </div>
    </div>

    <script>
        // 전역 설정
        const CONFIG = {
            contentId: <?php echo json_encode($contentId); ?>,
            studentId: <?php echo json_encode($studentId); ?>,
            wboardId: <?php echo json_encode($wboardId); ?>,
            questionImage: <?php echo json_encode($imgSrc2); ?>,
            solutionImage: <?php echo json_encode($imgSrc1); ?>,
            apiUrl: '/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api/'
        };

        // 이미지 탭 전환
        function switchImageTab(tab) {
            document.querySelectorAll('.tabs .tab-btn').forEach(btn => {
                if (btn.closest('.card-body').querySelector('#questionImageContainer')) {
                    btn.classList.toggle('active', btn.dataset.tab === tab);
                }
            });

            document.getElementById('questionImageContainer').classList.toggle('hidden', tab !== 'question');
            document.getElementById('solutionImageContainer').classList.toggle('hidden', tab !== 'solution');
        }

        // 결과 탭 전환
        function switchResultTab(tab) {
            document.querySelectorAll('.tabs .tab-btn').forEach(btn => {
                if (btn.dataset.tab === 'solutions' || btn.dataset.tab === 'misconceptions') {
                    btn.classList.toggle('active', btn.dataset.tab === tab);
                }
            });

            document.getElementById('solutionsResults').classList.toggle('hidden', tab !== 'solutions');
            document.getElementById('misconceptionsResults').classList.toggle('hidden', tab !== 'misconceptions');
        }

        // 다양한 풀이 탐색
        async function exploreSolutions() {
            const btn = document.getElementById('exploreSolutionsBtn');
            const spinner = document.getElementById('solutionSpinner');
            const btnText = document.getElementById('solutionBtnText');
            const btnIcon = document.getElementById('solutionBtnIcon');

            btn.disabled = true;
            spinner.classList.remove('hidden');
            btnIcon.classList.add('hidden');
            btnText.textContent = '탐색 중...';

            // 회로 상태 업데이트
            updateCircuitNode('explore', 'active');

            try {
                const response = await fetch(CONFIG.apiUrl + 'quantum_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'explore_solutions',
                        content_id: CONFIG.contentId,
                        student_id: CONFIG.studentId,
                        question_image: CONFIG.questionImage,
                        solution_image: CONFIG.solutionImage
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // 결과 표시
                    displaySolutions(result.data.solutions);

                    // 카운트 업데이트
                    document.getElementById('solutionCount').textContent = result.data.total_count || 0;

                    // 회로 상태 업데이트
                    updateCircuitNode('explore', 'collapsed');
                    updateCircuitNode('model', 'active');
                } else {
                    alert('탐색 실패: ' + (result.error || '알 수 없는 오류'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('탐색 중 오류가 발생했습니다: ' + error.message);
            } finally {
                btn.disabled = false;
                spinner.classList.add('hidden');
                btnIcon.classList.remove('hidden');
                btnText.textContent = '다양한 풀이 탐색';
            }
        }

        // 오개념 풀이 탐색
        async function exploreMisconceptions() {
            const btn = document.getElementById('exploreMisconceptionsBtn');
            const spinner = document.getElementById('misconceptionSpinner');
            const btnText = document.getElementById('misconceptionBtnText');
            const btnIcon = document.getElementById('misconceptionBtnIcon');

            btn.disabled = true;
            spinner.classList.remove('hidden');
            btnIcon.classList.add('hidden');
            btnText.textContent = '탐색 중...';

            try {
                const response = await fetch(CONFIG.apiUrl + 'quantum_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'explore_misconceptions',
                        content_id: CONFIG.contentId,
                        student_id: CONFIG.studentId,
                        question_image: CONFIG.questionImage,
                        solution_image: CONFIG.solutionImage
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // 결과 표시
                    displayMisconceptions(result.data.misconceptions);

                    // 카운트 업데이트
                    document.getElementById('misconceptionCount').textContent = result.data.total_count || 0;

                    // 오개념 탭으로 전환
                    switchResultTab('misconceptions');
                } else {
                    alert('탐색 실패: ' + (result.error || '알 수 없는 오류'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('탐색 중 오류가 발생했습니다: ' + error.message);
            } finally {
                btn.disabled = false;
                spinner.classList.add('hidden');
                btnIcon.classList.remove('hidden');
                btnText.textContent = '오개념 풀이 탐색';
            }
        }

        // 풀이 결과 표시
        function displaySolutions(solutions) {
            const container = document.getElementById('solutionsResults');

            if (!solutions || solutions.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <p>탐색된 풀이 방법이 없습니다</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = solutions.map((sol, idx) => `
                <div class="result-item">
                    <div class="result-item-header">
                        <div class="result-item-title">
                            <span>💡</span>
                            <span>${escapeHtml(sol.title || '풀이 방법 ' + (idx + 1))}</span>
                        </div>
                        <div class="result-item-meta">방금 전</div>
                    </div>
                    <div class="result-item-content">${escapeHtml(sol.content || sol.description || '')}</div>
                </div>
            `).join('');
        }

        // 오개념 결과 표시
        function displayMisconceptions(misconceptions) {
            const container = document.getElementById('misconceptionsResults');

            if (!misconceptions || misconceptions.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <p>탐색된 오개념이 없습니다</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = misconceptions.map((mis, idx) => `
                <div class="result-item misconception">
                    <div class="result-item-header">
                        <div class="result-item-title">
                            <span>⚠️</span>
                            <span>${escapeHtml(mis.title || '오개념 ' + (idx + 1))}</span>
                        </div>
                        <div class="result-item-meta">방금 전</div>
                    </div>
                    <div class="result-item-content">${escapeHtml(mis.content || mis.description || '')}</div>
                </div>
            `).join('');
        }

        // 회로 노드 상태 업데이트
        function updateCircuitNode(stage, state) {
            const node = document.querySelector(`.circuit-node[data-stage="${stage}"]`);
            if (node) {
                node.classList.remove('active', 'collapsed');
                if (state) {
                    node.classList.add(state);
                }
            }

            // 이전 커넥터도 활성화
            const nodes = document.querySelectorAll('.circuit-node');
            const connectors = document.querySelectorAll('.circuit-connector');
            let found = false;

            nodes.forEach((n, idx) => {
                if (n.dataset.stage === stage) {
                    found = true;
                }
                if (!found && idx < connectors.length) {
                    connectors[idx].classList.add('active');
                }
            });
        }

        // HTML 이스케이프
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/\n/g, '<br>');
        }

        // 초기화
        document.addEventListener('DOMContentLoaded', function() {
            // 초기 회로 상태 설정
            updateCircuitNode('input', 'collapsed');
            updateCircuitNode('parse', 'collapsed');

            <?php if ($contentId): ?>
            console.log('Quantum Modeling 초기화 완료 - Content ID:', CONFIG.contentId);
            <?php endif; ?>
        });
    </script>
</body>
</html>
