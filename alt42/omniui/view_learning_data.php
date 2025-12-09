<?php
/**
 * 학습 데이터 조회 페이지
 * 저장된 학습 진도, 스타일, 목표 데이터를 확인
 */

session_start();
require_once 'config.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // MathKing DB 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 사용자 정보 조회
    $user_sql = "SELECT id, username, firstname, lastname, email FROM mdl_user WHERE id = ?";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();

    // 1. 학습 진도 데이터
    $progress_sql = "SELECT * FROM mdl_alt42g_learning_progress WHERE userid = ?";
    $progress_stmt = $pdo->prepare($progress_sql);
    $progress_stmt->execute([$user_id]);
    $progress = $progress_stmt->fetch();

    // 2. 학습 스타일 데이터
    $style_sql = "SELECT * FROM mdl_alt42g_learning_style WHERE userid = ?";
    $style_stmt = $pdo->prepare($style_sql);
    $style_stmt->execute([$user_id]);
    $style = $style_stmt->fetch();

    // 3. 학습 방식 데이터
    $method_sql = "SELECT * FROM mdl_alt42g_learning_method WHERE userid = ?";
    $method_stmt = $pdo->prepare($method_sql);
    $method_stmt->execute([$user_id]);
    $method = $method_stmt->fetch();

    // 4. 학습 목표 데이터
    $goals_sql = "SELECT * FROM mdl_alt42g_learning_goals WHERE userid = ?";
    $goals_stmt = $pdo->prepare($goals_sql);
    $goals_stmt->execute([$user_id]);
    $goals = $goals_stmt->fetch();

    // 5. 추가 정보 데이터
    $additional_sql = "SELECT * FROM mdl_alt42g_additional_info WHERE userid = ?";
    $additional_stmt = $pdo->prepare($additional_sql);
    $additional_stmt->execute([$user_id]);
    $additional = $additional_stmt->fetch();

    // 6. 온보딩 상태
    $status_sql = "SELECT * FROM mdl_alt42g_onboarding_status WHERE userid = ?";
    $status_stmt = $pdo->prepare($status_sql);
    $status_stmt->execute([$user_id]);
    $status = $status_stmt->fetch();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("데이터베이스 연결 오류");
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학습 데이터 확인</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-6xl mx-auto p-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">학습 데이터 확인</h1>
            <p class="text-gray-600 mt-2">
                사용자: <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                (ID: <?php echo $user_id; ?>)
            </p>
        </div>

        <?php if ($status): ?>
        <!-- 온보딩 상태 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">📊 온보딩 완료 상태</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="flex items-center">
                    <span class="mr-2"><?php echo $status['basic_info_completed'] ? '✅' : '⭕'; ?></span>
                    <span>기본정보</span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo $status['learning_progress_completed'] ? '✅' : '⭕'; ?></span>
                    <span>학습진도</span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo $status['learning_style_completed'] ? '✅' : '⭕'; ?></span>
                    <span>학습스타일</span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo $status['learning_method_completed'] ? '✅' : '⭕'; ?></span>
                    <span>학습방식</span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo $status['learning_goals_completed'] ? '✅' : '⭕'; ?></span>
                    <span>학습목표</span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo $status['additional_info_completed'] ? '✅' : '⭕'; ?></span>
                    <span>추가정보</span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo $status['data_consent'] ? '✅' : '⭕'; ?></span>
                    <span>개인정보동의</span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2"><?php echo $status['overall_completed'] ? '✅' : '⭕'; ?></span>
                    <span class="font-semibold">전체완료</span>
                </div>
            </div>
            <div class="text-sm text-gray-500 mt-4">
                최종 수정: <?php echo date('Y-m-d H:i:s', $status['timemodified']); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($progress): ?>
        <!-- 학습 진도 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">📚 학습 진도</h2>
            <div class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="font-medium">수학 실력:</span>
                        <?php echo htmlspecialchars($progress['math_level'] ?: '미입력'); ?>
                    </div>
                    <div>
                        <span class="font-medium">주당 학습시간:</span>
                        <?php echo htmlspecialchars($progress['weekly_hours'] ?: '미입력'); ?>시간
                    </div>
                </div>

                <div class="border-t pt-3">
                    <h3 class="font-medium mb-2">개념 학습</h3>
                    <div class="pl-4 space-y-1">
                        <p>레벨: <?php echo htmlspecialchars($progress['concept_level'] ?: '미입력'); ?></p>
                        <p>진도율: <?php echo $progress['concept_progress']; ?>%</p>
                        <?php if ($progress['concept_details']): ?>
                        <p>상세: <?php echo htmlspecialchars($progress['concept_details']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="border-t pt-3">
                    <h3 class="font-medium mb-2">심화 학습</h3>
                    <div class="pl-4 space-y-1">
                        <p>레벨: <?php echo htmlspecialchars($progress['advanced_level'] ?: '미입력'); ?></p>
                        <p>진도율: <?php echo $progress['advanced_progress']; ?>%</p>
                        <?php if ($progress['advanced_details']): ?>
                        <p>상세: <?php echo htmlspecialchars($progress['advanced_details']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($progress['notes']): ?>
                <div class="border-t pt-3">
                    <span class="font-medium">비고:</span>
                    <p class="mt-1"><?php echo htmlspecialchars($progress['notes']); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($progress['academy_experience']): ?>
                <div class="border-t pt-3">
                    <span class="font-medium">학원 경험:</span>
                    <p class="mt-1"><?php echo htmlspecialchars($progress['academy_experience']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($style): ?>
        <!-- 학습 스타일 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">🎯 학습 스타일</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="font-medium">문제 선호도:</span>
                    <?php
                    $pref_map = [
                        'easy' => '쉬운 문제 선호',
                        'balanced' => '균형잡힌 문제',
                        'challenge' => '도전적인 문제 선호'
                    ];
                    echo $pref_map[$style['problem_preference']] ?? $style['problem_preference'] ?: '미입력';
                    ?>
                </div>
                <div>
                    <span class="font-medium">시험 대비 스타일:</span>
                    <?php
                    $exam_map = [
                        'concept' => '개념 중심',
                        'types' => '유형별 학습',
                        'intensive' => '집중 학습'
                    ];
                    echo $exam_map[$style['exam_style']] ?? $style['exam_style'] ?: '미입력';
                    ?>
                </div>
                <div>
                    <span class="font-medium">수학 자신감:</span>
                    <?php echo $style['math_confidence']; ?>/10
                </div>
                <div>
                    <span class="font-medium">부모님 관여도:</span>
                    <?php
                    $parent_map = [
                        'direct' => '적극적 관여',
                        'indirect' => '간접적 지원',
                        'independent' => '자율 학습'
                    ];
                    echo $parent_map[$style['parent_style']] ?? $style['parent_style'] ?: '미입력';
                    ?>
                </div>
                <div>
                    <span class="font-medium">스트레스 수준:</span>
                    <?php
                    $stress_map = [
                        'low' => '낮음',
                        'medium' => '보통',
                        'high' => '높음'
                    ];
                    echo $stress_map[$style['stress_level']] ?? $style['stress_level'] ?: '미입력';
                    ?>
                </div>
                <div>
                    <span class="font-medium">피드백 선호:</span>
                    <?php
                    $feedback_map = [
                        'immediate' => '즉각적 피드백',
                        'summary' => '종합 피드백',
                        'minimal' => '최소 피드백'
                    ];
                    echo $feedback_map[$style['feedback_preference']] ?? $style['feedback_preference'] ?: '미입력';
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($method): ?>
        <!-- 학습 방식 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">🎯 학습 방식</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="font-medium">부모님 관여도:</span>
                    <?php
                    $parent_map = [
                        'direct' => '적극적 관여',
                        'indirect' => '간접적 지원',
                        'independent' => '자율 학습'
                    ];
                    echo $parent_map[$method['parent_style']] ?? $method['parent_style'] ?: '미입력';
                    ?>
                </div>
                <div>
                    <span class="font-medium">스트레스 수준:</span>
                    <?php
                    $stress_map = [
                        'low' => '낮음',
                        'medium' => '보통',
                        'high' => '높음'
                    ];
                    echo $stress_map[$method['stress_level']] ?? $method['stress_level'] ?: '미입력';
                    ?>
                </div>
                <div>
                    <span class="font-medium">피드백 선호:</span>
                    <?php
                    $feedback_map = [
                        'immediate' => '즉각적 피드백',
                        'summary' => '종합 피드백',
                        'minimal' => '최소 피드백'
                    ];
                    echo $feedback_map[$method['feedback_preference']] ?? $method['feedback_preference'] ?: '미입력';
                    ?>
                </div>
                <?php if ($method['study_environment']): ?>
                <div>
                    <span class="font-medium">학습 환경:</span>
                    <?php echo htmlspecialchars($method['study_environment']); ?>
                </div>
                <?php endif; ?>
                <?php if ($method['study_time_preference']): ?>
                <div>
                    <span class="font-medium">선호 학습 시간대:</span>
                    <?php echo htmlspecialchars($method['study_time_preference']); ?>
                </div>
                <?php endif; ?>
                <?php if ($method['concentration_duration']): ?>
                <div>
                    <span class="font-medium">집중 가능 시간:</span>
                    <?php echo $method['concentration_duration']; ?>분
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($goals): ?>
        <!-- 학습 목표 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">🎓 학습 목표</h2>
            <div class="space-y-4">
                <?php if ($goals['short_term_goal']): ?>
                <div>
                    <h3 class="font-medium text-blue-600">단기 목표 (1-3개월)</h3>
                    <p class="mt-1"><?php echo htmlspecialchars($goals['short_term_goal']); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($goals['mid_term_goal']): ?>
                <div>
                    <h3 class="font-medium text-green-600">중기 목표 (6개월)</h3>
                    <p class="mt-1"><?php echo htmlspecialchars($goals['mid_term_goal']); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($goals['long_term_goal']): ?>
                <div>
                    <h3 class="font-medium text-purple-600">장기 목표 (1년)</h3>
                    <p class="mt-1"><?php echo htmlspecialchars($goals['long_term_goal']); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($goals['goal_note']): ?>
                <div class="border-t pt-3">
                    <span class="font-medium">목표 메모:</span>
                    <p class="mt-1"><?php echo htmlspecialchars($goals['goal_note']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($additional): ?>
        <!-- 추가 정보 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">📝 추가 정보</h2>
            <div class="space-y-3">
                <?php if ($additional['weekly_hours']): ?>
                <div>
                    <span class="font-medium">주당 학습 시간:</span>
                    <?php echo $additional['weekly_hours']; ?>시간
                </div>
                <?php endif; ?>

                <?php if ($additional['academy_experience']): ?>
                <div>
                    <span class="font-medium">학원 경험:</span>
                    <?php echo htmlspecialchars($additional['academy_experience']); ?>
                </div>
                <?php endif; ?>

                <?php if ($additional['previous_math_score']): ?>
                <div>
                    <span class="font-medium">이전 수학 성적:</span>
                    <?php echo htmlspecialchars($additional['previous_math_score']); ?>
                </div>
                <?php endif; ?>

                <?php if ($additional['target_score']): ?>
                <div>
                    <span class="font-medium">목표 성적:</span>
                    <?php echo htmlspecialchars($additional['target_score']); ?>
                </div>
                <?php endif; ?>

                <?php if ($additional['special_requests']): ?>
                <div class="border-t pt-3">
                    <span class="font-medium">특별 요청사항:</span>
                    <p class="mt-1"><?php echo htmlspecialchars($additional['special_requests']); ?></p>
                </div>
                <?php endif; ?>

                <div class="border-t pt-3">
                    <div class="flex items-center space-x-4">
                        <div>
                            <span class="mr-2"><?php echo $additional['data_consent'] ? '✅' : '⭕'; ?></span>
                            <span>개인정보 수집 동의</span>
                        </div>
                        <?php if ($additional['marketing_consent'] !== null): ?>
                        <div>
                            <span class="mr-2"><?php echo $additional['marketing_consent'] ? '✅' : '⭕'; ?></span>
                            <span>마케팅 정보 수신 동의</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$progress && !$style && !$method && !$goals && !$additional): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <p class="text-yellow-800">아직 저장된 학습 데이터가 없습니다.</p>
            <a href="student_onboarding.php" class="inline-block mt-4 px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                온보딩 페이지로 이동
            </a>
        </div>
        <?php endif; ?>

        <div class="flex gap-4 mt-6">
            <a href="student_onboarding.php" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                온보딩 페이지
            </a>
            <a href="student_dashboard.php" class="px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                대시보드
            </a>
        </div>
    </div>
</body>
</html>