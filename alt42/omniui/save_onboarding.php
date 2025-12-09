<?php
session_start();
require_once 'config.php';
$newAdditionalColumnsEnsured = false;
function ensureAdditionalInfoColumns($pdo) {
    static $done = false; if ($done) return; $done = true;
    $dbName = MATHKING_DB_NAME;
    $columnsToAdd = [
        'favorite_food' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `favorite_food` TEXT DEFAULT NULL COMMENT '좋아하는 음식'",
        'favorite_fruit' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `favorite_fruit` TEXT DEFAULT NULL COMMENT '좋아하는 과일'",
        'favorite_snack' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `favorite_snack` TEXT DEFAULT NULL COMMENT '좋아하는 과자'",
        'hobbies_interests' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `hobbies_interests` TEXT DEFAULT NULL COMMENT '취미/관심분야'",
        'fandom_yn' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `fandom_yn` TINYINT(1) DEFAULT 0 COMMENT '덕질 여부'",
    ];
    foreach ($columnsToAdd as $col => $alterSQL) {
        $checkSQL = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'mdl_alt42g_additional_info' AND COLUMN_NAME = :col";
        $chk = $pdo->prepare($checkSQL);
        $chk->execute([':schema' => $dbName, ':col' => $col]);
        $exists = (int)$chk->fetchColumn() > 0;
        if (!$exists) {
            $pdo->exec($alterSQL);
        }
    }
}

function ensureOnboardingStatusColumns($pdo) {
    static $done = false; if ($done) return; $done = true;
    $dbName = MATHKING_DB_NAME;
    $columnsToAdd = [
        'basic_info_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `basic_info_completed` TINYINT(1) DEFAULT 0 COMMENT '기본정보 완료'",
        'learning_progress_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_progress_completed` TINYINT(1) DEFAULT 0 COMMENT '학습진도 완료'",
        'learning_style_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_style_completed` TINYINT(1) DEFAULT 0 COMMENT '학습스타일 완료'",
        'learning_method_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_method_completed` TINYINT(1) DEFAULT 0 COMMENT '학습방식 완료'",
        'learning_goals_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_goals_completed` TINYINT(1) DEFAULT 0 COMMENT '학습목표 완료'",
        'additional_info_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `additional_info_completed` TINYINT(1) DEFAULT 0 COMMENT '추가정보 완료'",
        'data_consent' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `data_consent` TINYINT(1) DEFAULT 0 COMMENT '개인정보 동의'",
        'overall_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `overall_completed` TINYINT(1) DEFAULT 0 COMMENT '전체 완료'",
    ];
    foreach ($columnsToAdd as $col => $alterSQL) {
        $checkSQL = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'mdl_alt42g_onboarding_status' AND COLUMN_NAME = :col";
        $chk = $pdo->prepare($checkSQL);
        $chk->execute([':schema' => $dbName, ':col' => $col]);
        $exists = (int)$chk->fetchColumn() > 0;
        if (!$exists) {
            $pdo->exec($alterSQL);
        }
    }
}

// 로그인/사용자 식별: GET(userid) 우선 적용 가능
if (isset($_GET['userid']) && intval($_GET['userid']) > 0) {
    $_SESSION['user_id'] = intval($_GET['userid']);
}

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// POST 데이터 받기
$user_id = intval($_SESSION['user_id']);
$data = $_POST;

try {
    // MathKing DB 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 트랜잭션 시작
    $pdo->beginTransaction();

    $current_time = time();

    // 1. 학습 진도 테이블 (mdl_alt42g_learning_progress) 저장
    $progress_check_sql = "SELECT id FROM mdl_alt42g_learning_progress WHERE userid = ?";
    $progress_check_stmt = $pdo->prepare($progress_check_sql);
    $progress_check_stmt->execute([$user_id]);
    $progress_exists = $progress_check_stmt->fetch();

    if ($progress_exists) {
        $progress_update_sql = "UPDATE mdl_alt42g_learning_progress SET
                                math_level = ?, concept_level = ?, concept_progress = ?,
                                concept_details = ?, advanced_level = ?, advanced_progress = ?,
                                advanced_details = ?, notes = ?, weekly_hours = ?,
                                academy_experience = ?, timemodified = ?
                                WHERE userid = ?";
        $progress_update_stmt = $pdo->prepare($progress_update_sql);
        $progress_update_stmt->execute([
            $data['mathLevel'] ?? null,
            $data['conceptLevel'] ?? null,
            $data['conceptProgress'] ?? 0,
            $data['conceptDetails'] ?? null,
            $data['advancedLevel'] ?? null,
            $data['advancedProgress'] ?? 0,
            $data['advancedDetails'] ?? null,
            $data['notes'] ?? null,
            $data['weeklyHours'] ?? null,
            $data['academyExperience'] ?? null,
            $current_time,
            $user_id
        ]);
    } else {
        $progress_insert_sql = "INSERT INTO mdl_alt42g_learning_progress
                                (userid, math_level, concept_level, concept_progress,
                                concept_details, advanced_level, advanced_progress,
                                advanced_details, notes, weekly_hours,
                                academy_experience, timecreated, timemodified)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $progress_insert_stmt = $pdo->prepare($progress_insert_sql);
        $progress_insert_stmt->execute([
            $user_id,
            $data['mathLevel'] ?? null,
            $data['conceptLevel'] ?? null,
            $data['conceptProgress'] ?? 0,
            $data['conceptDetails'] ?? null,
            $data['advancedLevel'] ?? null,
            $data['advancedProgress'] ?? 0,
            $data['advancedDetails'] ?? null,
            $data['notes'] ?? null,
            $data['weeklyHours'] ?? null,
            $data['academyExperience'] ?? null,
            $current_time,
            $current_time
        ]);
    }

    // 2. 학습 스타일 테이블 (mdl_alt42g_learning_style) 저장
    $style_check_sql = "SELECT id FROM mdl_alt42g_learning_style WHERE userid = ?";
    $style_check_stmt = $pdo->prepare($style_check_sql);
    $style_check_stmt->execute([$user_id]);
    $style_exists = $style_check_stmt->fetch();

    if ($style_exists) {
        $style_update_sql = "UPDATE mdl_alt42g_learning_style SET
                            problem_preference = ?, exam_style = ?, math_confidence = ?,
                            parent_style = ?, stress_level = ?, feedback_preference = ?,
                            timemodified = ?
                            WHERE userid = ?";
        $style_update_stmt = $pdo->prepare($style_update_sql);
        $style_update_stmt->execute([
            $data['problemPreference'] ?? null,
            $data['examStyle'] ?? null,
            $data['mathConfidence'] ?? 5,
            $data['parentStyle'] ?? null,
            $data['stressLevel'] ?? null,
            $data['feedbackPreference'] ?? null,
            $current_time,
            $user_id
        ]);
    } else {
        $style_insert_sql = "INSERT INTO mdl_alt42g_learning_style
                            (userid, problem_preference, exam_style, math_confidence,
                            parent_style, stress_level, feedback_preference,
                            timecreated, timemodified)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $style_insert_stmt = $pdo->prepare($style_insert_sql);
        $style_insert_stmt->execute([
            $user_id,
            $data['problemPreference'] ?? null,
            $data['examStyle'] ?? null,
            $data['mathConfidence'] ?? 5,
            $data['parentStyle'] ?? null,
            $data['stressLevel'] ?? null,
            $data['feedbackPreference'] ?? null,
            $current_time,
            $current_time
        ]);
    }

    // 3. 학습 방식 테이블 (mdl_alt42g_learning_method) 저장
    $method_check_sql = "SELECT id FROM mdl_alt42g_learning_method WHERE userid = ?";
    $method_check_stmt = $pdo->prepare($method_check_sql);
    $method_check_stmt->execute([$user_id]);
    $method_exists = $method_check_stmt->fetch();

    if ($method_exists) {
        $method_update_sql = "UPDATE mdl_alt42g_learning_method SET
                            parent_style = ?, stress_level = ?, feedback_preference = ?,
                            timemodified = ?
                            WHERE userid = ?";
        $method_update_stmt = $pdo->prepare($method_update_sql);
        $method_update_stmt->execute([
            $data['parentStyle'] ?? null,
            $data['stressLevel'] ?? null,
            $data['feedbackPreference'] ?? null,
            $current_time,
            $user_id
        ]);
    } else {
        $method_insert_sql = "INSERT INTO mdl_alt42g_learning_method
                            (userid, parent_style, stress_level, feedback_preference,
                            timecreated, timemodified)
                            VALUES (?, ?, ?, ?, ?, ?)";
        $method_insert_stmt = $pdo->prepare($method_insert_sql);
        $method_insert_stmt->execute([
            $user_id,
            $data['parentStyle'] ?? null,
            $data['stressLevel'] ?? null,
            $data['feedbackPreference'] ?? null,
            $current_time,
            $current_time
        ]);
    }

    // 4. 학습 목표 테이블 (mdl_alt42g_learning_goals) 저장
    $goals_check_sql = "SELECT id FROM mdl_alt42g_learning_goals WHERE userid = ?";
    $goals_check_stmt = $pdo->prepare($goals_check_sql);
    $goals_check_stmt->execute([$user_id]);
    $goals_exists = $goals_check_stmt->fetch();

    if ($goals_exists) {
        $goals_update_sql = "UPDATE mdl_alt42g_learning_goals SET
                            short_term_goal = ?, mid_term_goal = ?, long_term_goal = ?,
                            goal_note = ?, timemodified = ?
                            WHERE userid = ?";
        $goals_update_stmt = $pdo->prepare($goals_update_sql);
        $goals_update_stmt->execute([
            $data['shortTermGoal'] ?? null,
            $data['midTermGoal'] ?? null,
            $data['longTermGoal'] ?? null,
            $data['goalNote'] ?? null,
            $current_time,
            $user_id
        ]);
    } else {
        $goals_insert_sql = "INSERT INTO mdl_alt42g_learning_goals
                            (userid, short_term_goal, mid_term_goal, long_term_goal,
                            goal_note, timecreated, timemodified)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $goals_insert_stmt = $pdo->prepare($goals_insert_sql);
        $goals_insert_stmt->execute([
            $user_id,
            $data['shortTermGoal'] ?? null,
            $data['midTermGoal'] ?? null,
            $data['longTermGoal'] ?? null,
            $data['goalNote'] ?? null,
            $current_time,
            $current_time
        ]);
    }

    // 5. 추가 정보 테이블 (mdl_alt42g_additional_info) 저장
    $additional_check_sql = "SELECT id FROM mdl_alt42g_additional_info WHERE userid = ?";
    $additional_check_stmt = $pdo->prepare($additional_check_sql);
    $additional_check_stmt->execute([$user_id]);
    $additional_exists = $additional_check_stmt->fetch();

    // 추가 정보 테이블 신규 컬럼 보장
    ensureAdditionalInfoColumns($pdo);

    if ($additional_exists) {
        $additional_update_sql = "UPDATE mdl_alt42g_additional_info SET
                                weekly_hours = ?, academy_experience = ?,
                                favorite_food = ?, favorite_fruit = ?, favorite_snack = ?,
                                hobbies_interests = ?, fandom_yn = ?,
                                data_consent = ?, timemodified = ?
                                WHERE userid = ?";
        $additional_update_stmt = $pdo->prepare($additional_update_sql);
        $additional_update_stmt->execute([
            $data['weeklyHours'] ?? null,
            $data['academyExperience'] ?? null,
            $data['favoriteFood'] ?? null,
            $data['favoriteFruit'] ?? null,
            $data['favoriteSnack'] ?? null,
            $data['hobbiesInterests'] ?? null,
            isset($data['fandomYN']) ? (in_array($data['fandomYN'], ['1', 1, 'true', true], true) ? 1 : 0) : 0,
            isset($data['dataConsent']) ? 1 : 0,
            $current_time,
            $user_id
        ]);
    } else {
        $additional_insert_sql = "INSERT INTO mdl_alt42g_additional_info
                                (userid, weekly_hours, academy_experience,
                                favorite_food, favorite_fruit, favorite_snack,
                                hobbies_interests, fandom_yn,
                                data_consent, timecreated, timemodified)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $additional_insert_stmt = $pdo->prepare($additional_insert_sql);
        $additional_insert_stmt->execute([
            $user_id,
            $data['weeklyHours'] ?? null,
            $data['academyExperience'] ?? null,
            $data['favoriteFood'] ?? null,
            $data['favoriteFruit'] ?? null,
            $data['favoriteSnack'] ?? null,
            $data['hobbiesInterests'] ?? null,
            isset($data['fandomYN']) ? (in_array($data['fandomYN'], ['1', 1, 'true', true], true) ? 1 : 0) : 0,
            isset($data['dataConsent']) ? 1 : 0,
            $current_time,
            $current_time
        ]);
    }

    // 6. 온보딩 상태 테이블 (mdl_alt42g_onboarding_status) 업데이트
    $status_check_sql = "SELECT id FROM mdl_alt42g_onboarding_status WHERE userid = ?";
    $status_check_stmt = $pdo->prepare($status_check_sql);
    $status_check_stmt->execute([$user_id]);
    $status_exists = $status_check_stmt->fetch();

    // 각 섹션 완료 상태 체크
    $basic_completed = !empty($data['school']) && !empty($data['studentName']) ? 1 : 0;
    $progress_completed = !empty($data['mathLevel']) || !empty($data['conceptLevel']) ? 1 : 0;
    $style_completed = !empty($data['problemPreference']) || !empty($data['examStyle']) ? 1 : 0;
    $method_completed = !empty($data['parentStyle']) || !empty($data['stressLevel']) || !empty($data['feedbackPreference']) ? 1 : 0;
    $goals_completed = !empty($data['shortTermGoal']) || !empty($data['midTermGoal']) ? 1 : 0;
    $additional_completed = (
        !empty($data['weeklyHours']) ||
        !empty($data['academyExperience']) ||
        !empty($data['favoriteFood']) ||
        !empty($data['favoriteFruit']) ||
        !empty($data['favoriteSnack']) ||
        !empty($data['hobbiesInterests']) ||
        (isset($data['fandomYN']) && (in_array($data['fandomYN'], ['1', 1, 'true', true], true)))
    ) ? 1 : 0;
    $data_consent = isset($data['dataConsent']) ? 1 : 0;
    $overall_completed = ($basic_completed && $progress_completed && $style_completed && $method_completed && $goals_completed && $additional_completed && $data_consent) ? 1 : 0;

    // 상태 컬럼 보장 (과거 테이블에 누락된 경우 대비)
    ensureOnboardingStatusColumns($pdo);

    if ($status_exists) {
        $status_update_sql = "UPDATE mdl_alt42g_onboarding_status SET
                             basic_info_completed = ?, learning_progress_completed = ?,
                             learning_style_completed = ?, learning_method_completed = ?,
                             learning_goals_completed = ?, additional_info_completed = ?,
                             data_consent = ?, overall_completed = ?, timemodified = ?
                             WHERE userid = ?";
        $status_update_stmt = $pdo->prepare($status_update_sql);
        $status_update_stmt->execute([
            $basic_completed,
            $progress_completed,
            $style_completed,
            $method_completed,
            $goals_completed,
            $additional_completed,
            $data_consent,
            $overall_completed,
            $current_time,
            $user_id
        ]);
    } else {
        $status_insert_sql = "INSERT INTO mdl_alt42g_onboarding_status
                             (userid, basic_info_completed, learning_progress_completed,
                             learning_style_completed, learning_method_completed,
                             learning_goals_completed, additional_info_completed,
                             data_consent, overall_completed, timecreated, timemodified)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $status_insert_stmt = $pdo->prepare($status_insert_sql);
        $status_insert_stmt->execute([
            $user_id,
            $basic_completed,
            $progress_completed,
            $style_completed,
            $method_completed,
            $goals_completed,
            $additional_completed,
            $data_consent,
            $overall_completed,
            $current_time,
            $current_time
        ]);
    }

    // 기존 JSON 형식으로도 백업 저장 (호환성 유지)
    $onboarding_data = json_encode([
        'school' => $data['school'] ?? '',
        'student_name' => $data['studentName'] ?? '',
        'student_phone' => $data['studentPhone'] ?? '',
        'parent_phone_father' => $data['parentPhoneFather'] ?? '',
        'parent_phone_mother' => $data['parentPhoneMother'] ?? '',
        'address' => $data['address'] ?? '',
        'course_level' => $data['courseLevel'] ?? '',
        'grade_detail' => $data['gradeDetail'] ?? '',
        'math_level' => $data['mathLevel'] ?? '',
        'concept_level' => $data['conceptLevel'] ?? '',
        'concept_progress' => $data['conceptProgress'] ?? 0,
        'advanced_level' => $data['advancedLevel'] ?? '',
        'advanced_progress' => $data['advancedProgress'] ?? 0,
        'notes' => $data['notes'] ?? '',
        'problem_preference' => $data['problemPreference'] ?? '',
        'exam_style' => $data['examStyle'] ?? '',
        'math_confidence' => $data['mathConfidence'] ?? 5,
        'parent_style' => $data['parentStyle'] ?? '',
        'stress_level' => $data['stressLevel'] ?? '',
        'feedback_preference' => $data['feedbackPreference'] ?? '',
        'short_term_goal' => $data['shortTermGoal'] ?? '',
        'mid_term_goal' => $data['midTermGoal'] ?? '',
        'long_term_goal' => $data['longTermGoal'] ?? '',
        'goal_note' => $data['goalNote'] ?? '',
        'weekly_hours' => $data['weeklyHours'] ?? 10,
        'academy_experience' => $data['academyExperience'] ?? '',
        'favorite_food' => $data['favoriteFood'] ?? '',
        'favorite_fruit' => $data['favoriteFruit'] ?? '',
        'favorite_snack' => $data['favoriteSnack'] ?? '',
        'hobbies_interests' => $data['hobbiesInterests'] ?? '',
        'fandom_yn' => isset($data['fandomYN']) ? (in_array($data['fandomYN'], ['1', 1, 'true', true], true) ? 1 : 0) : 0,
        'data_consent' => isset($data['dataConsent']) ? 1 : 0,
        'created_at' => time()
    ], JSON_UNESCAPED_UNICODE);

    // 기존 온보딩 데이터가 있는지 확인 (fieldid 23)
    $check_sql = "SELECT id FROM mdl_user_info_data WHERE userid = ? AND fieldid = 23";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$user_id]);
    $existing = $check_stmt->fetch();

    if ($existing) {
        // 업데이트
        $update_sql = "UPDATE mdl_user_info_data
                      SET data = ?, dataformat = 0
                      WHERE userid = ? AND fieldid = 23";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$onboarding_data, $user_id]);
    } else {
        // 새로 삽입
        $insert_sql = "INSERT INTO mdl_user_info_data (userid, fieldid, data, dataformat)
                      VALUES (?, 23, ?, 0)";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([$user_id, $onboarding_data]);
    }

    // 트랜잭션 커밋
    $pdo->commit();

    // 사용자 기본 정보 업데이트
    if (!empty($data['studentPhone'])) {
        $update_user_sql = "UPDATE mdl_user SET phone1 = ? WHERE id = ?";
        $update_user_stmt = $pdo->prepare($update_user_sql);
        $update_user_stmt->execute([$data['studentPhone'], $user_id]);
    }

    // 부모님 연락처 업데이트
    $parent_phone = !empty($data['parentPhoneFather']) && $data['parentPhoneFather'] !== '010-'
                    ? $data['parentPhoneFather']
                    : (!empty($data['parentPhoneMother']) && $data['parentPhoneMother'] !== '010-'
                        ? $data['parentPhoneMother']
                        : '');

    if (!empty($parent_phone)) {
        $update_parent_sql = "UPDATE mdl_user SET phone2 = ? WHERE id = ?";
        $update_parent_stmt = $pdo->prepare($update_parent_sql);
        $update_parent_stmt->execute([$parent_phone, $user_id]);
    }

    // Alt42t DB에도 저장 (시험 시스템용)
    if (!empty($data['school']) && !empty($data['gradeDetail'])) {
        try {
            $alt_dsn = "mysql:host=" . ALT42T_DB_HOST . ";dbname=" . ALT42T_DB_NAME . ";charset=utf8mb4";
            $alt_pdo = new PDO($alt_dsn, ALT42T_DB_USER, ALT42T_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // student_exam_settings 테이블에 기본 정보 저장
            $alt_check_sql = "SELECT id FROM student_exam_settings WHERE user_id = ?";
            $alt_check_stmt = $alt_pdo->prepare($alt_check_sql);
            $alt_check_stmt->execute([$user_id]);
            $alt_existing = $alt_check_stmt->fetch();

            if ($alt_existing) {
                $alt_update_sql = "UPDATE student_exam_settings
                                  SET name = ?, school = ?, grade = ?, updated_at = NOW()
                                  WHERE user_id = ?";
                $alt_update_stmt = $alt_pdo->prepare($alt_update_sql);
                $alt_update_stmt->execute([
                    $data['studentName'] ?? '',
                    $data['school'] ?? '',
                    $data['gradeDetail'] ?? '',
                    $user_id
                ]);
            } else {
                $alt_insert_sql = "INSERT INTO student_exam_settings
                                  (user_id, name, school, grade, created_at, updated_at)
                                  VALUES (?, ?, ?, ?, NOW(), NOW())";
                $alt_insert_stmt = $alt_pdo->prepare($alt_insert_sql);
                $alt_insert_stmt->execute([
                    $user_id,
                    $data['studentName'] ?? '',
                    $data['school'] ?? '',
                    $data['gradeDetail'] ?? ''
                ]);
            }
        } catch (PDOException $e) {
            error_log("Alt42t DB Error: " . $e->getMessage());
            // Alt42t DB 에러는 무시하고 계속 진행
        }
    }

    // AJAX 요청인지 확인
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $isAjax = $isAjax || (isset($_POST['ajax']) && $_POST['ajax'] == '1');
    $isAjax = $isAjax || (isset($_GET['ajax']) && $_GET['ajax'] == '1');

    if ($isAjax) {
        // AJAX 요청인 경우 JSON 응답
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => '온보딩 정보가 성공적으로 저장되었습니다.',
            'user_id' => $user_id,
            'timestamp' => $current_time
        ]);
        exit;
    }

    // 일반 요청인 경우 리다이렉트
    $_SESSION['onboarding_complete'] = true;
    header('Location: student_dashboard.php?msg=onboarding_success');
    exit;

} catch (PDOException $e) {
    // 트랜잭션 롤백
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error: " . $e->getMessage());
    
    // AJAX 요청인지 확인
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $isAjax = $isAjax || (isset($_POST['ajax']) && $_POST['ajax'] == '1');
    $isAjax = $isAjax || (isset($_GET['ajax']) && $_GET['ajax'] == '1');
    
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => '저장 중 오류가 발생했습니다.',
            'error' => DEBUG_MODE ? $e->getMessage() : 'Database error'
        ]);
        exit;
    }
    
    header('Location: student_onboarding.php?error=save_failed');
    exit;
}
?>