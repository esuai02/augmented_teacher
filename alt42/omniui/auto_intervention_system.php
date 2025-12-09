<?php
/**
 * Mathking 자동개입 시스템 v1.0
 * 21단계 AI 에이전트 기반 맞춤형 학습 개입 시스템
 *
 * @package    local_augmented_teacher
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 세션 시작
session_start();

// 설정 파일 포함
require_once('config.php');

// 보안 체크 - 로그인 필요
if (!isset($_SESSION['user_id'])) {
    // header('Location: login.php');
    // exit();
}

// 사용자 정보 가져오기
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

// CSRF 토큰 생성
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// 데이터베이스 연결 설정
try {
    // MathKing DB 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Alt42t DB 연결 (시험 정보용)
    $dsn_alt = "mysql:host=" . ALT42T_DB_HOST . ";dbname=" . ALT42T_DB_NAME . ";charset=utf8mb4";
    $pdo_alt = new PDO($dsn_alt, ALT42T_DB_USER, ALT42T_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    $pdo = null;
    $pdo_alt = null;
}

// 실제 학생 데이터 로드 함수
function loadStudentData($pdo, $pdo_alt, $user_id) {
    $studentData = [
        'profile' => [],
        'exam_info' => [],
        'recent_activity' => [],
        'learning_progress' => [],
        'ai_analysis' => []
    ];

    if (!$pdo || !$user_id) return $studentData;

    try {
        // 1. 학생 프로필 정보
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.firstname, u.lastname, u.email,
                   uid.data as role
            FROM mdl_user u
            LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid AND uid.fieldid = 22
            WHERE u.id = ? AND u.deleted = 0
        ");
        $stmt->execute([$user_id]);
        $studentData['profile'] = $stmt->fetch() ?: [];

        // 2. 시험 정보 (Alt42t DB)
        if ($pdo_alt) {
            $stmt = $pdo_alt->prepare("
                SELECT * FROM student_exam_settings
                WHERE user_id = ?
                ORDER BY exam_start_date DESC
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $studentData['exam_info'] = $stmt->fetch() ?: [];
        }

        // 3. 최근 학습 활동 (최근 7일)
        $stmt = $pdo->prepare("
            SELECT page, COUNT(*) as activity_count,
                   MAX(timecreated) as last_activity
            FROM mdl_abessi_missionlog
            WHERE userid = ? AND timecreated > ?
            GROUP BY page
            ORDER BY activity_count DESC
            LIMIT 10
        ");
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        $stmt->execute([$user_id, $sevenDaysAgo]);
        $studentData['recent_activity'] = $stmt->fetchAll();

        // 4. 학습 진행 상황
        $stmt = $pdo->prepare("
            SELECT progress_data, timecreated
            FROM mdl_abessi_progress
            WHERE userid = ?
            ORDER BY timecreated DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $progress = $stmt->fetchAll();
        foreach ($progress as $p) {
            $studentData['learning_progress'][] = [
                'data' => json_decode($p['progress_data'], true),
                'date' => date('Y-m-d', $p['timecreated'])
            ];
        }

        // 5. 기존 AI 분석 결과 (있는 경우)
        $stmt = $pdo->prepare("
            SELECT agent_type, agent_level, analysis_data,
                   confidence_score, recommendations
            FROM mdl_abessi_ai_analysis
            WHERE userid = ? AND created_date = ?
            ORDER BY agent_level
        ");
        $stmt->execute([$user_id, date('Y-m-d')]);
        $studentData['ai_analysis'] = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log("Error loading student data: " . $e->getMessage());
    }

    return $studentData;
}

// 페이지 로드 시 학생 데이터 가져오기
$studentData = [];
if ($user_id > 0 && isset($pdo) && isset($pdo_alt)) {
    $studentData = loadStudentData($pdo, $pdo_alt, $user_id);
}

// 헤더 설정
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>🚀 Mathking 자동개입 시스템 v1.0</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2d3748;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            background: rgba(0,0,0,0.2);
            padding: 10px 15px;
            border-radius: 8px;
        }

        .mode-badge {
            background: #3182ce;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }

        .tab-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .tab-nav {
            display: flex;
            background: #f7fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab-nav button {
            flex: 1;
            padding: 15px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            color: #4a5568;
            transition: all 0.3s;
            position: relative;
        }

        .tab-nav button.active {
            color: #3182ce;
            background: white;
        }

        .tab-nav button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: #3182ce;
        }

        .tab-content {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .agent-analysis {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .agent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .agent-card {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffd700;
        }

        .agent-card h4 {
            margin-bottom: 8px;
            font-size: 1.1em;
        }

        .diagnostic-section {
            background: #f7fafc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .diagnostic-section h3 {
            color: #2b6cb0;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .problem-redefinition {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .improvement-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .improvement-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }

        .improvement-card:hover {
            border-color: #3182ce;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .improvement-card h4 {
            color: #2b6cb0;
            margin-bottom: 10px;
        }

        .student-interface {
            background: linear-gradient(135deg, #38b2ac 0%, #319795 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
        }

        .routine-builder {
            background: white;
            color: #2d3748;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .routine-step {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #3182ce;
        }

        .step-number {
            background: #3182ce;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }

        .time-tracker {
            background: #fed7d7;
            border: 2px solid #fc8181;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
        }

        .progress-bar {
            background: #e2e8f0;
            height: 8px;
            border-radius: 4px;
            margin: 15px 0;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, #48bb78, #38a169);
            height: 100%;
            transition: width 0.3s;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #3182ce, #2c5aa0);
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #38a169, #2f855a);
            transform: translateY(-2px);
        }

        .signature-routine {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .coaching-message {
            background: #e6fffa;
            border-left: 4px solid #38b2ac;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 6px 6px 0;
        }

        .alert-box {
            background: #fef5e7;
            border: 2px solid #f6ad55;
            color: #744210;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .view-toggle {
            background: #e2e8f0;
            border-radius: 6px;
            padding: 4px;
            display: inline-flex;
            margin-bottom: 20px;
        }

        .view-toggle button {
            padding: 8px 16px;
            border: none;
            background: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .view-toggle button.active {
            background: #3182ce;
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 2em;
            }

            .tab-nav {
                flex-direction: column;
            }

            .action-buttons {
                flex-direction: column;
            }

            .user-info {
                position: static;
                margin-bottom: 20px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($user_id > 0): ?>
        <div class="user-info">
            👤 <?php echo htmlspecialchars($username); ?>
        </div>
        <?php endif; ?>

        <div class="header">
            <h1>🚀 Mathking 자동개입 시스템 v1.0</h1>
            <p>21단계 AI 에이전트 기반 맞춤형 학습 개입 시스템</p>
            <div class="mode-badge">📄 시험대비 중심 모드</div>
        </div>

        <div class="view-toggle">
            <button onclick="switchView('tabs')" class="active" id="tab-view-btn">탭 보기</button>
            <button onclick="switchView('scroll')" id="scroll-view-btn">스크롤 보기</button>
        </div>

        <div class="tab-container" id="tab-container">
            <div class="tab-nav">
                <button class="tab-button active" onclick="showTab('analysis')">📊 에이전트 분석</button>
                <button class="tab-button" onclick="showTab('interaction')">🎯 학생 상호작용</button>
            </div>

            <div id="analysis" class="tab-content">
                <div class="agent-analysis">
                    <h2>🤖 21단계 AI 에이전트 종합 분석 결과</h2>

                    <?php if (!empty($studentData['profile'])): ?>
                    <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="color: white;">
                            <strong>학생:</strong> <?php echo htmlspecialchars($studentData['profile']['firstname'] . ' ' . $studentData['profile']['lastname']); ?>
                            <?php if (!empty($studentData['exam_info'])): ?>
                                | <strong>시험:</strong> <?php echo htmlspecialchars($studentData['exam_info']['exam_type']); ?>
                                (D-<?php echo max(0, floor((strtotime($studentData['exam_info']['exam_start_date']) - time()) / 86400)); ?>)
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="agent-grid">
                        <?php
                        // 실제 데이터 기반 AI 분석 결과 생성
                        $agentAnalysis = [
                            [
                                'level' => 1,
                                'title' => '📝 1. 온보딩 분석',
                                'content' => '학습 프로필 분석 중...'
                            ],
                            [
                                'level' => 2,
                                'title' => '📅 2. 시험일정 식별',
                                'content' => 'D-' . (isset($studentData['exam_info']['exam_start_date']) ?
                                    max(0, floor((strtotime($studentData['exam_info']['exam_start_date']) - time()) / 86400)) : '?') .
                                    ' 집중 전략 필요'
                            ],
                            [
                                'level' => 3,
                                'title' => '⚙️ 3. 활동 조정',
                                'content' => count($studentData['recent_activity']) > 0 ?
                                    '최근 활동: ' . htmlspecialchars($studentData['recent_activity'][0]['page']) :
                                    '활동 패턴 분석 필요'
                            ],
                            [
                                'level' => 4,
                                'title' => '🎯 4. 문제활동 식별',
                                'content' => isset($studentData['exam_info']['study_level']) ?
                                    '현재 모드: ' . htmlspecialchars($studentData['exam_info']['study_level']) :
                                    '학습 모드 설정 필요'
                            ],
                            [
                                'level' => 5,
                                'title' => '💭 5. 학습감정 분석',
                                'content' => '학습 동기 분석 중...'
                            ],
                            [
                                'level' => 6,
                                'title' => '🎪 6. 상호작용 타게팅',
                                'content' => '맞춤형 개입 전략 수립 중...'
                            ]
                        ];

                        // 기존 AI 분석 결과가 있으면 병합
                        if (!empty($studentData['ai_analysis'])) {
                            foreach ($studentData['ai_analysis'] as $analysis) {
                                $level = $analysis['agent_level'];
                                if ($level <= 6) {
                                    $agentAnalysis[$level - 1]['content'] =
                                        json_decode($analysis['analysis_data'], true)['summary'] ??
                                        $agentAnalysis[$level - 1]['content'];
                                }
                            }
                        }

                        // 분석 결과 카드 출력
                        foreach ($agentAnalysis as $agent):
                        ?>
                        <div class="agent-card">
                            <h4><?php echo $agent['title']; ?></h4>
                            <p><?php echo htmlspecialchars($agent['content']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="diagnostic-section">
                    <h3>🔍 핵심 진단 결과</h3>
                    <div class="problem-redefinition">
                        <h4>📌 문제 재정의</h4>
                        <?php
                        // 학생 데이터 기반 동적 진단
                        $currentScore = 75; // 기본값
                        $targetScore = 90;  // 기본값
                        $keyIssue = "문제 접근 시 초기 판단 속도 개선 필요";
                        $hiddenStrength = "체계적 학습 패턴";

                        // 최근 진행 상황이 있으면 점수 업데이트
                        if (!empty($studentData['learning_progress'])) {
                            $latestProgress = $studentData['learning_progress'][0]['data'] ?? [];
                            if (isset($latestProgress['current_score'])) {
                                $currentScore = $latestProgress['current_score'];
                            }
                            if (isset($latestProgress['target_score'])) {
                                $targetScore = $latestProgress['target_score'];
                            }
                        }

                        // 활동 패턴 분석
                        if (count($studentData['recent_activity']) > 3) {
                            $hiddenStrength = "꾸준한 학습 습관 (" . count($studentData['recent_activity']) . "개 활동)";
                        }

                        ?>
                        <p><strong>현재 상황:</strong> <?php echo $currentScore; ?>점에서 <?php echo $targetScore; ?>점으로 <?php echo ($targetScore - $currentScore); ?>점 상승 목표</p>
                        <p><strong>핵심 이슈:</strong> <?php echo htmlspecialchars($keyIssue); ?></p>
                        <p><strong>숨겨진 강점:</strong> <?php echo htmlspecialchars($hiddenStrength); ?></p>
                    </div>

                    <div class="improvement-cards">
                        <div class="improvement-card">
                            <h4>⚡ 즉시 개선</h4>
                            <p>문제 읽기 후 30초 멈추기 루틴 도입</p>
                            <p>→ 성급한 판단 방지</p>
                        </div>
                        <div class="improvement-card">
                            <h4>📈 단기 개선 (1-2주)</h4>
                            <p>취약 유형별 집중 공략</p>
                            <p>→ 확실한 점수 확보 영역 확장</p>
                        </div>
                        <div class="improvement-card">
                            <h4>🚀 장기 개선 (1개월)</h4>
                            <p>실전 시뮬레이션 정기 실행</p>
                            <p>→ 시험 환경 적응력 향상</p>
                        </div>
                    </div>
                </div>

                <div class="coaching-message">
                    <h4>💡 AI 코치의 핵심 통찰</h4>
                    <p>당신의 가장 큰 적은 "10초의 조급함"입니다. 대신 "30초의 여유"를 가져보세요.
                    이 작은 변화가 15점 상승의 열쇠가 될 것입니다.</p>
                </div>
            </div>

            <div id="interaction" class="tab-content" style="display: none;">
                <div class="student-interface">
                    <h2>🎯 너만의 시그니처 루틴 만들기</h2>
                    <p>지금부터 2주 동안, 매일 이 루틴으로 연습해보자!</p>
                </div>

                <div class="routine-builder">
                    <h3>🏆 "30초 사고 마스터" 루틴</h3>

                    <div class="routine-step">
                        <div class="step-number">1</div>
                        <div>
                            <strong>문제 읽기 (10초)</strong><br>
                            천천히, 핵심 단어에 동그라미 치면서 읽기
                        </div>
                    </div>

                    <div class="routine-step">
                        <div class="step-number">2</div>
                        <div>
                            <strong>멈추기 (30초)</strong><br>
                            "잠깐!" 하고 멈춘 후, 관련 개념 3개 떠올리기
                        </div>
                    </div>

                    <div class="routine-step">
                        <div class="step-number">3</div>
                        <div>
                            <strong>전략 선택</strong><br>
                            "이 문제는 ○○ 유형이니까, ○○ 방법으로 풀자"
                        </div>
                    </div>

                    <div class="routine-step">
                        <div class="step-number">4</div>
                        <div>
                            <strong>실행하기</strong><br>
                            선택한 전략으로 차근차근 풀어보기
                        </div>
                    </div>
                </div>

                <div class="time-tracker">
                    <h4>⏱️ 오늘의 30초 루틴 도전</h4>
                    <p>목표: 5문제 연속 30초 루틴 적용하기</p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                    <p id="progress-text">0/5 문제 완료</p>
                </div>

                <div class="signature-routine">
                    <h4>🎯 2주 후 너의 모습</h4>
                    <p style="font-style: italic; line-height: 1.6;">
                        "어? 이 문제... 잠깐, 30초만!"<br>
                        "삼각비랑 피타고라스 같이 쓰면 되겠네?"<br>
                        "해설지? 나중에 확인하지, 먼저 내가 해볼게!"<br><br>
                        <strong>더 이상 10초의 노예가 아닌, 30초의 주인이 된 너!</strong>
                    </p>
                </div>

                <div class="alert-box">
                    <strong>⚠️ 선생님과의 대화 준비</strong><br>
                    30분 후 선생님께 이렇게 말해보자:
                    "선생님, 오늘부터 문제 풀 때 30초 루틴을 적용해보고 있어요. 어떤 문제에서 특히 효과적일지 조언해주세요!"
                </div>

                <div class="action-buttons">
                    <button class="btn btn-success" onclick="startRoutine()">
                        🏃‍♂️ 지금 바로 30초 루틴 시작!
                    </button>
                    <button class="btn btn-primary" onclick="trackProgress()">
                        📊 진행상황 체크하기
                    </button>
                </div>
            </div>
        </div>

        <div id="scroll-container" style="display: none;">
            <!-- 스크롤 버전 내용 -->
            <div class="agent-analysis">
                <h2>🤖 21단계 AI 에이전트 종합 분석 결과</h2>

                <div class="agent-grid">
                    <div class="agent-card">
                        <h4>📝 1-7. 학습 상태 진단</h4>
                        <p>온보딩부터 침착도까지 종합 분석 완료</p>
                    </div>
                    <div class="agent-card">
                        <h4>🔍 8-14. 심층 분석</h4>
                        <p>이탈 패턴, 내용 분석, 노트 상태 파악</p>
                    </div>
                    <div class="agent-card">
                        <h4>🚀 15-21. 개입 실행</h4>
                        <p>문제 재정의부터 시스템 개선까지</p>
                    </div>
                </div>
            </div>

            <div class="student-interface">
                <h2>🎯 통합 학습 경험</h2>
                <p>분석과 실행이 하나로 통합된 학습 여정</p>
            </div>
        </div>
    </div>

    <script>
        // PHP 변수를 JavaScript로 전달
        const userId = <?php echo json_encode($user_id); ?>;
        const csrfToken = '<?php echo $csrf_token; ?>';
        const studentData = <?php echo json_encode($studentData); ?>;

        let currentProgress = 0;
        let isTabView = true;

        function showTab(tabName) {
            // 모든 탭 내용 숨기기
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.style.display = 'none');

            // 모든 탭 버튼 비활성화
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));

            // 선택된 탭 보이기
            document.getElementById(tabName).style.display = 'block';

            // 클릭된 버튼 활성화
            event.target.classList.add('active');

            // 서버에 탭 변경 기록
            logTabChange(tabName);
        }

        function switchView(viewType) {
            const tabContainer = document.getElementById('tab-container');
            const scrollContainer = document.getElementById('scroll-container');
            const tabBtn = document.getElementById('tab-view-btn');
            const scrollBtn = document.getElementById('scroll-view-btn');

            if (viewType === 'tabs') {
                tabContainer.style.display = 'block';
                scrollContainer.style.display = 'none';
                tabBtn.classList.add('active');
                scrollBtn.classList.remove('active');
                isTabView = true;
            } else {
                tabContainer.style.display = 'none';
                scrollContainer.style.display = 'block';
                scrollBtn.classList.add('active');
                tabBtn.classList.remove('active');
                isTabView = false;
            }
        }

        function startRoutine() {
            // 서버에 루틴 시작 기록
            if (userId > 0) {
                saveActivity('routine_start');
            }

            // 학습 링크 열기
            window.open('https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/learning_routine.php', '_blank');

            alert('🎯 30초 루틴 훈련이 시작됩니다!\n\n새 탭에서 연습 문제를 풀면서\n1. 문제 읽기 (10초)\n2. 멈추고 생각하기 (30초)\n3. 전략 선택\n4. 실행\n\n이 순서를 지켜보세요!');
        }

        function trackProgress() {
            currentProgress = Math.min(currentProgress + 1, 5);
            const progressPercent = (currentProgress / 5) * 100;

            document.querySelector('.progress-fill').style.width = progressPercent + '%';
            document.getElementById('progress-text').textContent = currentProgress + '/5 문제 완료';

            // 서버에 진행상황 저장
            if (userId > 0) {
                saveProgress(currentProgress);
            }

            if (currentProgress === 5) {
                alert('🏆 축하합니다! 오늘의 30초 루틴 도전을 완주했습니다!\n\n내일도 이 루틴을 계속 연습해보세요. 2주 후에는 완전히 다른 실력을 경험하게 될 것입니다!');
            } else {
                alert(`👍 좋습니다! ${currentProgress}번째 문제 완료!\n${5-currentProgress}문제 더 화이팅!`);
            }
        }

        // 서버에 활동 기록
        function saveActivity(activityType) {
            fetch('ajax_save_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    user_id: userId,
                    activity: activityType,
                    timestamp: Date.now()
                })
            });
        }

        // 서버에 진행상황 저장
        function saveProgress(progress) {
            fetch('ajax_save_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    user_id: userId,
                    routine_progress: progress,
                    date: new Date().toISOString().split('T')[0]
                })
            });
        }

        // 탭 변경 기록
        function logTabChange(tabName) {
            if (userId > 0) {
                saveActivity('tab_' + tabName);
            }
        }

        // 초기 로딩 시 진행상황 체크
        document.addEventListener('DOMContentLoaded', function() {
            // 30초 루틴 설명 애니메이션
            const routineSteps = document.querySelectorAll('.routine-step');
            routineSteps.forEach((step, index) => {
                setTimeout(() => {
                    step.style.transform = 'translateX(0)';
                    step.style.opacity = '1';
                }, index * 200);
            });

            // 서버에서 저장된 진행상황 불러오기
            if (userId > 0) {
                loadProgress();
                // AI 분석 데이터 로드 (비동기)
                loadAIAnalysis();
                // 학생 인사이트 로드 (비동기)
                loadStudentInsights();
            }
        });

        // 서버에서 진행상황 불러오기
        function loadProgress() {
            fetch('ajax_get_progress.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.progress) {
                        currentProgress = parseInt(data.progress);
                        const progressPercent = (currentProgress / 5) * 100;
                        document.querySelector('.progress-fill').style.width = progressPercent + '%';
                        document.getElementById('progress-text').textContent = currentProgress + '/5 문제 완료';
                    }
                });
        }

        // AI 분석 데이터 로드
        function loadAIAnalysis() {
            fetch('ajax_get_ai_analysis.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.analysis) {
                        console.log('AI Analysis loaded:', data.analysis);
                        // 필요시 UI 업데이트
                        updateAIAnalysisUI(data.analysis);
                    }
                })
                .catch(error => {
                    console.error('AI Analysis load error:', error);
                });
        }

        // 학생 인사이트 로드
        function loadStudentInsights() {
            fetch('ajax_get_student_insights.php?user_id=' + userId + '&type=general')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        console.log('Student insights loaded:', data.data);
                        // 코칭 메시지 업데이트
                        updateCoachingMessage(data.data.insight);
                    }
                })
                .catch(error => {
                    console.error('Insights load error:', error);
                });
        }

        // AI 분석 UI 업데이트
        function updateAIAnalysisUI(analysisData) {
            // 21단계 분석 결과로 에이전트 카드 업데이트
            if (analysisData.length > 0) {
                const agentCards = document.querySelectorAll('.agent-card');
                analysisData.slice(0, 6).forEach((analysis, index) => {
                    if (agentCards[index]) {
                        const data = JSON.parse(analysis.analysis_data || '{}');
                        const p = agentCards[index].querySelector('p');
                        if (p && data.summary) {
                            p.textContent = data.summary;
                        }
                    }
                });
            }
        }

        // 코칭 메시지 업데이트
        function updateCoachingMessage(insight) {
            const coachingElement = document.querySelector('.coaching-message p');
            if (coachingElement && insight) {
                // 기본 메시지 유지하되, 인사이트 추가
                coachingElement.innerHTML = insight.replace(/\n/g, '<br>');
            }
        }

        // 새로운 AI 분석 요청
        function refreshAIAnalysis() {
            fetch('ajax_get_ai_analysis.php?user_id=' + userId + '&refresh=true')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateAIAnalysisUI(data.analysis);
                        alert('AI 분석이 업데이트되었습니다.');
                    }
                });
        }
    </script>
</body>
</html>