<?php
/**
 * MathKing AI 시험대비 시스템
 * 실제 데이터만 사용 - 하드코딩 없음
 * GPT API 연동으로 맞춤형 시그니처 루틴 생성
 */

session_start();
require_once 'config.php';

// 데이터베이스 연결
try {
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    die("연결 실패: " . $e->getMessage());
}

// 사용자 확인
$userid = $_GET['userid'] ?? $_SESSION['userid'] ?? $_SESSION['user_id'] ?? null;
if (!$userid) {
    header('Location: login.php');
    exit;
}

// 학생 정보 조회
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.firstname, u.lastname, u.email
    FROM mdl_user u
    WHERE u.id = ? AND u.deleted = 0
");
$stmt->execute([$userid]);
$user = $stmt->fetch();

if (!$user) {
    die("사용자 정보를 찾을 수 없습니다.");
}

// 학습 진도 데이터 조회 (테이블 존재 확인)
$progress = null;
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'mdl_alt42g_learning_progress'");
    if ($check_table->rowCount() > 0) {
        $progress_stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_learning_progress WHERE userid = ?");
        $progress_stmt->execute([$userid]);
        $progress = $progress_stmt->fetch();
    }
} catch (PDOException $e) {
    error_log("Learning progress table not found: " . $e->getMessage());
}

// 학습 스타일 데이터 조회 (테이블 존재 확인)
$style = null;
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'mdl_alt42g_learning_style'");
    if ($check_table->rowCount() > 0) {
        $style_stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_learning_style WHERE userid = ?");
        $style_stmt->execute([$userid]);
        $style = $style_stmt->fetch();
    }
} catch (PDOException $e) {
    error_log("Learning style table not found: " . $e->getMessage());
}

// 학습 목표 데이터 조회 (테이블 존재 확인)
$goals = null;
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'mdl_alt42g_learning_goals'");
    if ($check_table->rowCount() > 0) {
        $goals_stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_learning_goals WHERE userid = ?");
        $goals_stmt->execute([$userid]);
        $goals = $goals_stmt->fetch();
    }
} catch (PDOException $e) {
    error_log("Learning goals table not found: " . $e->getMessage());
}

// 출결 통계 초기화
$attendance_stats = [
    'total_absences' => 0,
    'makeup_complete' => 0
];

// 출결 기록 조회 (테이블이 존재하는 경우만)
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'mdl_abessi_attendance_record'");
    if ($check_table->rowCount() > 0) {
        $attendance_stmt = $pdo->prepare("
            SELECT * FROM mdl_abessi_attendance_record
            WHERE userid = ?
            ORDER BY date DESC
            LIMIT 30
        ");
        $attendance_stmt->execute([$userid]);
        $attendance_records = $attendance_stmt->fetchAll();

        foreach ($attendance_records as $record) {
            if ($record['type'] == 'absence') {
                $attendance_stats['total_absences']++;
            } elseif ($record['type'] == 'makeup_complete') {
                $attendance_stats['makeup_complete']++;
            }
        }
    }
} catch (PDOException $e) {
    error_log("Attendance table not found: " . $e->getMessage());
}

// 최근 활동 조회 (테이블이 존재하는 경우만)
$last_activity = null;
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'mdl_abessi_missionlog'");
    if ($check_table->rowCount() > 0) {
        $activity_stmt = $pdo->prepare("
            SELECT MAX(timecreated) as last_activity
            FROM mdl_abessi_missionlog
            WHERE userid = ?
        ");
        $activity_stmt->execute([$userid]);
        $last_activity = $activity_stmt->fetch();
    }
} catch (PDOException $e) {
    error_log("Missionlog table not found: " . $e->getMessage());
}

// GPT API를 사용한 시그니처 루틴 생성 함수
function generateSignatureRoutine($user_data, $progress_data, $style_data, $goals_data) {
    if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY == '' ||
        strpos(OPENAI_API_KEY, 'sk-') !== 0) {
        return null;
    }

    // 사용자 데이터를 기반으로 프롬프트 생성
    $user_info = "학생 이름: " . $user_data['firstname'] . " " . $user_data['lastname'] . "\n";

    if ($progress_data) {
        $user_info .= "수학 실력: " . ($progress_data['math_level'] ?? '미평가') . "\n";
        $user_info .= "개념 진도: " . ($progress_data['concept_progress'] ?? 0) . "%\n";
        $user_info .= "심화 진도: " . ($progress_data['advanced_progress'] ?? 0) . "%\n";
        $user_info .= "주당 학습시간: " . ($progress_data['weekly_hours'] ?? 0) . "시간\n";
    }

    if ($style_data) {
        $user_info .= "수학 자신감: " . ($style_data['math_confidence'] ?? 5) . "/10\n";
        $user_info .= "스트레스 수준: " . ($style_data['stress_level'] ?? 'medium') . "\n";
        $user_info .= "문제 선호도: " . ($style_data['problem_preference'] ?? '균형') . "\n";
        $user_info .= "피드백 선호: " . ($style_data['feedback_preference'] ?? '즉각적') . "\n";
    }

    if ($goals_data) {
        $user_info .= "단기 목표: " . ($goals_data['short_term_goal'] ?? '없음') . "\n";
        $user_info .= "중기 목표: " . ($goals_data['mid_term_goal'] ?? '없음') . "\n";
    }

    $prompt = "다음 학생을 위한 맞춤형 시그니처 학습 루틴을 생성해주세요:\n\n" .
              $user_info . "\n" .
              "이 학생에게 가장 적합한 일일 학습 루틴을 4단계로 구성해주세요. " .
              "각 단계는 구체적이고 실행 가능해야 하며, 학생의 현재 상태와 목표를 고려해야 합니다. " .
              "응답은 JSON 형식으로 다음과 같이 작성해주세요:\n" .
              '{"routine_title": "제목", "total_minutes": 분, "steps": [{"step_number": 1, "title": "단계 제목", "duration": "시간", "description": "설명"}]}';

    $data = [
        'model' => OPENAI_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => '당신은 한국 중학생을 위한 수학 학습 전문가입니다.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 1000
    ];

    $ch = curl_init(OPENAI_API_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, OPENAI_TIMEOUT);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        error_log("OpenAI API error: HTTP $http_code");
        return null;
    }

    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        $content = $result['choices'][0]['message']['content'];
        // JSON 부분만 추출
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            return json_decode($matches[0], true);
        }
    }

    return null;
}

// GPT API로 시그니처 루틴 생성 (데이터가 있는 경우만)
$gpt_routine = null;
if ($progress || $style || $goals) {
    $gpt_routine = generateSignatureRoutine($user, $progress, $style, $goals);
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Mathking AI 시험대비 시스템</title>
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
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 1200px;
            margin: 0 auto;
            overflow: hidden;
        }

        .user-info {
            background: #f7f9fc;
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-name {
            font-weight: 600;
            color: #374151;
        }

        .nav-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            margin-left: 10px;
        }

        .nav-btn:hover {
            background: #2563eb;
        }

        .tabs {
            display: flex;
            background: #f7f9fc;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab.active {
            color: #dc2626;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: #dc2626;
        }

        .tab:hover {
            background: #f1f5f9;
        }

        .content {
            padding: 40px;
            min-height: 500px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .diagnosis-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            border: 2px solid #cbd5e1;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .metric-item {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 5px;
        }

        .metric-label {
            color: #64748b;
            font-size: 14px;
        }

        .agent-item {
            background: #f8fafc;
            border-left: 4px solid #dc2626;
            padding: 15px 20px;
            margin: 10px 0;
            border-radius: 0 10px 10px 0;
        }

        .problem-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .problem-item {
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }

        .problem-false {
            background: #fee2e2;
            border: 2px solid #fca5a5;
        }

        .problem-true {
            background: #d1fae5;
            border: 2px solid #6ee7b7;
        }

        .success-box {
            background: #f0fdf4;
            border: 2px solid #4ade80;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
        }

        .routine-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }

        .routine-step {
            display: flex;
            align-items: flex-start;
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 10px;
            gap: 15px;
        }

        .step-number {
            background: #dc2626;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .no-data {
            background: #f3f4f6;
            border: 2px dashed #9ca3af;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            color: #6b7280;
            margin: 20px 0;
        }

        .daily-plan {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .day-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1f2937;
            margin-bottom: 20px;
        }

        h2 {
            color: #374151;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        h3 {
            color: #4b5563;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            <span class="user-name">
                👤 <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
            </span>
            <div>
                <a href="student_dashboard.php?userid=<?php echo $userid; ?>" class="nav-btn">대시보드</a>
                <a href="view_learning_data.php" class="nav-btn">학습 데이터</a>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('report')">📊 AI 진단 리포트</button>
            <button class="tab" onclick="showTab('strategy')">🎯 시험대비 전략</button>
            <button class="tab" onclick="showTab('routine')">⚡ 시그니처 루틴</button>
        </div>

        <div class="content">
            <!-- AI 진단 리포트 -->
            <div id="report" class="tab-content active">
                <h1>📊 Mathking AI 진단 리포트</h1>

                <?php if ($progress || $style || $goals): ?>
                <div class="diagnosis-card">
                    <h3>👤 학생 프로필 분석</h3>
                    <div class="metric-grid">
                        <?php if ($progress && $progress['concept_progress']): ?>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo $progress['concept_progress']; ?>%</div>
                            <div class="metric-label">개념 진도</div>
                        </div>
                        <?php endif; ?>

                        <?php if ($progress && $progress['advanced_progress']): ?>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo $progress['advanced_progress']; ?>%</div>
                            <div class="metric-label">심화 진도</div>
                        </div>
                        <?php endif; ?>

                        <?php if ($style && $style['math_confidence']): ?>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo $style['math_confidence']; ?>/10</div>
                            <div class="metric-label">자신감</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <h2>🔍 학습 데이터 기반 분석</h2>

                <?php if ($progress): ?>
                <div class="agent-item">
                    <strong>학습 상태 분석</strong><br>
                    <?php if ($progress['math_level']): ?>
                    • 현재 수학 실력: <?php echo htmlspecialchars($progress['math_level']); ?><br>
                    <?php endif; ?>
                    <?php if ($progress['concept_progress'] !== null): ?>
                    • 개념 진도: <?php echo $progress['concept_progress']; ?>%<br>
                    <?php endif; ?>
                    <?php if ($progress['advanced_progress'] !== null): ?>
                    • 심화 진도: <?php echo $progress['advanced_progress']; ?>%<br>
                    <?php endif; ?>
                    <?php if ($progress['weekly_hours']): ?>
                    • 주당 학습시간: <?php echo $progress['weekly_hours']; ?>시간<br>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($style): ?>
                <div class="agent-item">
                    <strong>학습 패턴 분석</strong><br>
                    <?php if ($style['math_confidence'] !== null): ?>
                    • 수학 자신감: <?php echo $style['math_confidence']; ?>/10<br>
                    <?php endif; ?>
                    <?php if ($style['stress_level']): ?>
                    • 스트레스: <?php
                        $stress_map = ['low' => '낮음', 'medium' => '보통', 'high' => '높음'];
                        echo $stress_map[$style['stress_level']] ?? $style['stress_level'];
                    ?><br>
                    <?php endif; ?>
                    <?php if ($style['problem_preference']): ?>
                    • 문제 선호도: <?php
                        $pref_map = ['easy' => '쉬운 문제', 'balanced' => '균형', 'challenge' => '도전적'];
                        echo $pref_map[$style['problem_preference']] ?? $style['problem_preference'];
                    ?><br>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($attendance_stats['total_absences'] > 0): ?>
                <div class="agent-item">
                    <strong>출결 분석</strong><br>
                    • 최근 결석: <?php echo $attendance_stats['total_absences']; ?>회<br>
                    • 보충 완료: <?php echo $attendance_stats['makeup_complete']; ?>회
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="no-data">
                    <h3>📝 학습 데이터가 없습니다</h3>
                    <p style="margin-top: 10px;">온보딩을 완료하면 AI 진단을 받을 수 있습니다.</p>
                    <a href="student_onboarding.php" class="nav-btn" style="display: inline-block; margin-top: 20px;">온보딩 시작</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- 시험대비 전략 -->
            <div id="strategy" class="tab-content">
                <h1>🎯 시험 정복 전략</h1>

                <?php if ($goals): ?>
                <div class="diagnosis-card">
                    <h3>🎓 나의 학습 목표</h3>
                    <?php if ($goals['short_term_goal']): ?>
                    <p><strong>단기:</strong> <?php echo htmlspecialchars($goals['short_term_goal']); ?></p>
                    <?php endif; ?>
                    <?php if ($goals['mid_term_goal']): ?>
                    <p style="margin-top: 10px;"><strong>중기:</strong> <?php echo htmlspecialchars($goals['mid_term_goal']); ?></p>
                    <?php endif; ?>
                    <?php if ($goals['long_term_goal']): ?>
                    <p style="margin-top: 10px;"><strong>장기:</strong> <?php echo htmlspecialchars($goals['long_term_goal']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($progress && $progress['concept_progress'] !== null): ?>
                <h2>📅 진도 기반 학습 전략</h2>
                <div class="daily-plan">
                    <?php if ($progress['concept_progress'] < 30): ?>
                    <div class="day-card">
                        <h4 style="color: #dc2626;">긴급</h4>
                        <div>기초 개념 집중</div>
                    </div>
                    <?php elseif ($progress['concept_progress'] < 60): ?>
                    <div class="day-card">
                        <h4 style="color: #f59e0b;">중요</h4>
                        <div>개념 완성</div>
                    </div>
                    <?php else: ?>
                    <div class="day-card">
                        <h4 style="color: #10b981;">진행중</h4>
                        <div>심화 학습</div>
                    </div>
                    <?php endif; ?>

                    <?php if ($progress['advanced_progress'] < 30): ?>
                    <div class="day-card">
                        <h4 style="color: #6366f1;">다음 단계</h4>
                        <div>응용 문제 시작</div>
                    </div>
                    <?php elseif ($progress['advanced_progress'] < 60): ?>
                    <div class="day-card">
                        <h4 style="color: #6366f1;">진행중</h4>
                        <div>응용력 강화</div>
                    </div>
                    <?php else: ?>
                    <div class="day-card">
                        <h4 style="color: #6366f1;">마무리</h4>
                        <div>실전 연습</div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <h3>📝 학습 목표를 설정해주세요</h3>
                    <p style="margin-top: 10px;">학습 데이터가 있어야 전략을 수립할 수 있습니다.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 시그니처 루틴 -->
            <div id="routine" class="tab-content">
                <h1>⚡ 나만의 시그니처 루틴</h1>

                <?php if ($gpt_routine && isset($gpt_routine['steps'])): ?>
                <div class="success-box">
                    <strong>AI 생성 맞춤 루틴:</strong> <?php echo htmlspecialchars($gpt_routine['routine_title'] ?? '맞춤형 학습 루틴'); ?>
                </div>

                <div class="routine-card">
                    <h3>🔥 <?php echo htmlspecialchars($gpt_routine['routine_title'] ?? '일일 학습 루틴'); ?></h3>
                    <p style="color: #6b7280; margin-bottom: 20px;">총 학습 시간: <?php echo $gpt_routine['total_minutes'] ?? '120'; ?>분</p>

                    <?php foreach ($gpt_routine['steps'] as $step): ?>
                    <div class="routine-step">
                        <div class="step-number"><?php echo $step['step_number']; ?></div>
                        <div>
                            <strong><?php echo htmlspecialchars($step['title']); ?> (<?php echo $step['duration']; ?>)</strong><br>
                            <?php echo htmlspecialchars($step['description']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php elseif ($progress || $style): ?>
                <!-- 데이터 기반 기본 루틴 (GPT API 실패 시) -->
                <div class="routine-card">
                    <h3>🔥 기본 학습 루틴</h3>

                    <?php if ($progress && $progress['weekly_hours']): ?>
                    <p style="color: #6b7280; margin-bottom: 20px;">
                        주간 학습 시간 기준: <?php echo $progress['weekly_hours']; ?>시간
                    </p>
                    <?php endif; ?>

                    <div class="routine-step">
                        <div class="step-number">1</div>
                        <div>
                            <strong>준비 단계</strong><br>
                            오늘의 학습 목표 확인
                        </div>
                    </div>

                    <?php if ($progress && $progress['concept_progress'] < 70): ?>
                    <div class="routine-step">
                        <div class="step-number">2</div>
                        <div>
                            <strong>개념 학습</strong><br>
                            현재 진도: <?php echo $progress['concept_progress']; ?>% - 개념 완성 필요
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($progress && $progress['advanced_progress'] < 50): ?>
                    <div class="routine-step">
                        <div class="step-number">3</div>
                        <div>
                            <strong>심화 학습</strong><br>
                            현재 진도: <?php echo $progress['advanced_progress']; ?>% - 응용력 향상 필요
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="routine-step">
                        <div class="step-number">4</div>
                        <div>
                            <strong>마무리</strong><br>
                            오늘 학습 내용 정리
                        </div>
                    </div>
                </div>

                <?php if ($progress): ?>
                <h2>📊 현재 학습 상태</h2>
                <div class="metric-grid">
                    <?php if ($progress['concept_progress'] !== null): ?>
                    <div class="metric-item">
                        <div class="metric-value"><?php echo $progress['concept_progress']; ?>%</div>
                        <div class="metric-label">개념 진도</div>
                    </div>
                    <?php endif; ?>

                    <?php if ($progress['advanced_progress'] !== null): ?>
                    <div class="metric-item">
                        <div class="metric-value"><?php echo $progress['advanced_progress']; ?>%</div>
                        <div class="metric-label">심화 진도</div>
                    </div>
                    <?php endif; ?>

                    <?php if ($style && $style['math_confidence'] !== null): ?>
                    <div class="metric-item">
                        <div class="metric-value"><?php echo $style['math_confidence']; ?>/10</div>
                        <div class="metric-label">자신감</div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="no-data">
                    <h3>📝 학습 데이터가 필요합니다</h3>
                    <p style="margin-top: 10px;">온보딩을 완료하면 AI가 맞춤 루틴을 생성합니다.</p>
                    <a href="student_onboarding.php" class="nav-btn" style="display: inline-block; margin-top: 20px;">온보딩 시작</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // 모든 탭과 콘텐츠 비활성화
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            // 선택된 탭과 콘텐츠 활성화
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }
    </script>
</body>
</html>