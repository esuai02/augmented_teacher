<?php
// 자동 저장 AJAX 핸들러 (단일 구현)
session_start();
require_once 'config.php';
$newAdditionalFields = ['favoriteFood','favoriteFruit','favoriteSnack','hobbiesInterests','fandomYN'];

function ensureAdditionalInfoColumns($pdo) {
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
    $dbName = MATHKING_DB_NAME;
    $columnsToAdd = [
        'basic_info_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `basic_info_completed` TINYINT(1) DEFAULT 0 COMMENT '기본정보 완료'",
        'learning_progress_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_progress_completed` TINYINT(1) DEFAULT 0 COMMENT '학습진도 완료'",
        'learning_style_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_style_completed` TINYINT(1) DEFAULT 0 COMMENT '학습스타일 완료'",
        'learning_method_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_method_completed` TINYINT(1) DEFAULT 0 COMMENT '학습방식 완료'",
        'learning_goals_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_goals_completed` TINYINT(1) DEFAULT 0 COMMENT '학습목표 완료'",
        'additional_info_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `additional_info_completed` TINYINT(1) DEFAULT 0 COMMENT '추가정보 완료'",
        'data_consent' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `data_consent` TINYINT(1) DEFAULT 0 COMMENT '개인정보 동의'",
        'overall_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `overall_completed` TINYINT(1) DEFAULT 0 COMMENT '전체 완료'"
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

header('Content-Type: application/json; charset=utf-8');

// userid 파라미터로 세션 설정 허용 (iframe로 접근 시 초기 세션 세팅 보조)
if (isset($_GET['userid']) && intval($_GET['userid']) > 0) {
    $_SESSION['user_id'] = intval($_GET['userid']);
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

// 입력값 수신
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

if ($field === '') {
    echo json_encode(['success' => false, 'message' => '필드명이 없습니다.']);
    exit;
}

// 불린/정수 필드 보정
$booleanFields = ['dataConsent', 'marketingConsent', 'fandomYN'];
if (in_array($field, $booleanFields, true)) {
    $value = ($value === 'true' || $value === '1' || $value === 1 || $value === true) ? 1 : 0;
}
$intFields = ['conceptProgress', 'advancedProgress', 'mathConfidence', 'weeklyHours'];
if (in_array($field, $intFields, true)) {
    $value = intval($value);
}

try {
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $current_time = time();
    $success = false;
    $table_updated = '';

    // 신규 추가 필드 저장 전 컬럼 보장
    if (in_array($field, $newAdditionalFields, true)) {
        ensureAdditionalInfoColumns($pdo);
    }

    switch ($field) {
        case 'mathLevel':
        case 'conceptLevel':
        case 'conceptProgress':
        case 'advancedLevel':
        case 'advancedProgress':
        case 'notes':
        case 'weeklyHours':
        case 'academyExperience':
            $table_updated = 'learning_progress';
            $exists = $pdo->prepare("SELECT id FROM mdl_alt42g_learning_progress WHERE userid = ?");
            $exists->execute([$user_id]);
            $exists = $exists->fetch();

            $map = [
                'mathLevel' => 'math_level',
                'conceptLevel' => 'concept_level',
                'conceptProgress' => 'concept_progress',
                'advancedLevel' => 'advanced_level',
                'advancedProgress' => 'advanced_progress',
                'notes' => 'notes',
                'weeklyHours' => 'weekly_hours',
                'academyExperience' => 'academy_experience'
            ];
            $dbf = $map[$field] ?? $field;

            if ($exists) {
                $stmt = $pdo->prepare("UPDATE mdl_alt42g_learning_progress SET $dbf = ?, timemodified = ? WHERE userid = ?");
                $stmt->execute([$value, $current_time, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO mdl_alt42g_learning_progress (userid, $dbf, timecreated, timemodified) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $value, $current_time, $current_time]);
            }
            $success = true;
            break;

        case 'problemPreference':
        case 'examStyle':
        case 'mathConfidence':
            $table_updated = 'learning_style';
            $exists = $pdo->prepare("SELECT id FROM mdl_alt42g_learning_style WHERE userid = ?");
            $exists->execute([$user_id]);
            $exists = $exists->fetch();

            $map = [
                'problemPreference' => 'problem_preference',
                'examStyle' => 'exam_style',
                'mathConfidence' => 'math_confidence'
            ];
            $dbf = $map[$field] ?? $field;

            if ($exists) {
                $stmt = $pdo->prepare("UPDATE mdl_alt42g_learning_style SET $dbf = ?, timemodified = ? WHERE userid = ?");
                $stmt->execute([$value, $current_time, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO mdl_alt42g_learning_style (userid, $dbf, timecreated, timemodified) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $value, $current_time, $current_time]);
            }
            $success = true;
            break;

        case 'parentStyle':
        case 'stressLevel':
        case 'feedbackPreference':
            $table_updated = 'learning_method';
            $exists = $pdo->prepare("SELECT id FROM mdl_alt42g_learning_method WHERE userid = ?");
            $exists->execute([$user_id]);
            $exists = $exists->fetch();

            $map = [
                'parentStyle' => 'parent_style',
                'stressLevel' => 'stress_level',
                'feedbackPreference' => 'feedback_preference'
            ];
            $dbf = $map[$field] ?? $field;

            if ($exists) {
                $stmt = $pdo->prepare("UPDATE mdl_alt42g_learning_method SET $dbf = ?, timemodified = ? WHERE userid = ?");
                $stmt->execute([$value, $current_time, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO mdl_alt42g_learning_method (userid, $dbf, timecreated, timemodified) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $value, $current_time, $current_time]);
            }
            $success = true;
            break;

        case 'dataConsent':
        case 'marketingConsent':
        case 'favoriteFood':
        case 'favoriteFruit':
        case 'favoriteSnack':
        case 'hobbiesInterests':
        case 'fandomYN':
            $table_updated = 'additional_info';
            $exists = $pdo->prepare("SELECT id FROM mdl_alt42g_additional_info WHERE userid = ?");
            $exists->execute([$user_id]);
            $exists = $exists->fetch();

            $map = [
                'dataConsent' => 'data_consent',
                'marketingConsent' => 'marketing_consent',
                'favoriteFood' => 'favorite_food',
                'favoriteFruit' => 'favorite_fruit',
                'favoriteSnack' => 'favorite_snack',
                'hobbiesInterests' => 'hobbies_interests',
                'fandomYN' => 'fandom_yn'
            ];
            $dbf = $map[$field] ?? $field;

            if ($exists) {
                $stmt = $pdo->prepare("UPDATE mdl_alt42g_additional_info SET $dbf = ?, timemodified = ? WHERE userid = ?");
                $stmt->execute([$value, $current_time, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO mdl_alt42g_additional_info (userid, $dbf, timecreated, timemodified) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $value, $current_time, $current_time]);
            }
            $success = true;
            break;

        case 'shortTermGoal':
        case 'midTermGoal':
        case 'longTermGoal':
        case 'goalNote':
            $table_updated = 'learning_goals';
            $exists = $pdo->prepare("SELECT id FROM mdl_alt42g_learning_goals WHERE userid = ?");
            $exists->execute([$user_id]);
            $exists = $exists->fetch();

            $map = [
                'shortTermGoal' => 'short_term_goal',
                'midTermGoal' => 'mid_term_goal',
                'longTermGoal' => 'long_term_goal',
                'goalNote' => 'goal_note'
            ];
            $dbf = $map[$field] ?? $field;

            if ($exists) {
                $stmt = $pdo->prepare("UPDATE mdl_alt42g_learning_goals SET $dbf = ?, timemodified = ? WHERE userid = ?");
                $stmt->execute([$value, $current_time, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO mdl_alt42g_learning_goals (userid, $dbf, timecreated, timemodified) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $value, $current_time, $current_time]);
            }
            $success = true;
            break;

        default:
            $table_updated = 'user_info_data';
            $get = $pdo->prepare("SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = 23");
            $get->execute([$user_id]);
            $existing = $get->fetch();
            $json = $existing ? (json_decode($existing['data'], true) ?? []) : [];
            $json[$field] = $value;
            $json['updated_at'] = time();
            $jsonStr = json_encode($json, JSON_UNESCAPED_UNICODE);
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE mdl_user_info_data SET data = ? WHERE userid = ? AND fieldid = 23");
                $stmt->execute([$jsonStr, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO mdl_user_info_data (userid, fieldid, data, dataformat) VALUES (?, 23, ?, 0)");
                $stmt->execute([$user_id, $jsonStr]);
            }
            $success = true;
            break;
    }

    // 온보딩 상태 플래그 업데이트 (섹션별 완료 표기)
    if ($success) {
        // 상태 컬럼 보장 (과거 테이블에 누락된 경우 대비)
        ensureOnboardingStatusColumns($pdo);
        $exists = $pdo->prepare("SELECT id FROM mdl_alt42g_onboarding_status WHERE userid = ?");
        $exists->execute([$user_id]);
        $has = $exists->fetch();
        $statusField = 'basic_info_completed';
        if ($table_updated === 'learning_progress') $statusField = 'learning_progress_completed';
        elseif ($table_updated === 'learning_style') $statusField = 'learning_style_completed';
        elseif ($table_updated === 'learning_method') $statusField = 'learning_method_completed';
        elseif ($table_updated === 'learning_goals') $statusField = 'learning_goals_completed';
        elseif ($table_updated === 'additional_info') $statusField = 'additional_info_completed';

        if ($has) {
            $stmt = $pdo->prepare("UPDATE mdl_alt42g_onboarding_status SET $statusField = 1, timemodified = ? WHERE userid = ?");
            $stmt->execute([$current_time, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO mdl_alt42g_onboarding_status (userid, $statusField, timecreated, timemodified) VALUES (?, 1, ?, ?)");
            $stmt->execute([$user_id, $current_time, $current_time]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => '저장되었습니다.',
        'field' => $field,
        'value' => $value,
        'table' => $table_updated,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (PDOException $e) {
    error_log('Auto-save error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.', 'error' => $e->getMessage()]);
}
?>