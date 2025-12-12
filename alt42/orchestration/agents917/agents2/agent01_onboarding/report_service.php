<?php
/**
 * Onboarding Report Data Service
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/report_service.php
 * Location: Line 1
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
if (!defined('ALT42_ALLOW_GUEST') || !ALT42_ALLOW_GUEST) {
    require_login();
}

/**
 * Get all onboarding data for a user
 * @param int $userid User ID
 * @return array Combined data from info and assessment
 */
function getOnboardingData($userid) {
    global $DB;

    try {
        // Get basic info from session or database
        $infoData = [];

        // Load Moodle user data
        $user = null;
        try {
            $user = $DB->get_record('user', ['id' => $userid],
                'id,firstname,lastname,email,phone1,city,country');
        } catch (Exception $e) {
            // ignore
        }

        if ($user) {
            $infoData['studentName'] = $user->firstname . ' ' . $user->lastname;
            $infoData['email'] = $user->email;
            $infoData['phone'] = $user->phone1;
            $infoData['city'] = $user->city;
            $infoData['country'] = $user->country;
        }

        // Get student profile data from mdl_alt42_student_profiles (optional)
        $profile = null;
        try {
            if ($DB->get_manager()->table_exists(new xmldb_table('alt42_student_profiles'))) {
                $profile = $DB->get_record('alt42_student_profiles', ['user_id' => $userid]);
            }
        } catch (Exception $e) {
            // ignore
        }

        if ($profile) {
            $infoData['learning_style'] = $profile->learning_style ?? '';
            $infoData['interests'] = $profile->interests ? json_decode($profile->interests, true) : [];
            $infoData['goals'] = $profile->goals ? json_decode($profile->goals, true) : [];
            $infoData['mbti_type'] = $profile->mbti_type ?? '';
            $infoData['preferred_motivator'] = $profile->preferred_motivator ?? '';
            $infoData['daily_active_time'] = $profile->daily_active_time ?? '';
            $infoData['streak_days'] = $profile->streak_days ?? 0;
            $infoData['total_interactions'] = $profile->total_interactions ?? 0;
            $infoData['last_active'] = $profile->last_active ?? '';
        }

        // Get latest MBTI from mdl_abessi_mbtilog (case-insensitive)
        $mbtiLog = null;
        try {
            if ($DB->get_manager()->table_exists(new xmldb_table('abessi_mbtilog'))) {
                $mbtiLog = $DB->get_record_sql(
                    "SELECT * FROM {abessi_mbtilog}
                     WHERE userid = ?
                     ORDER BY timecreated DESC
                     LIMIT 1",
                    [$userid]
                );
            }
        } catch (Exception $e) {
            // ignore
        }

        if ($mbtiLog && !empty($mbtiLog->mbti)) {
            // Store MBTI in uppercase for consistency
            $infoData['mbti_type'] = strtoupper($mbtiLog->mbti);
            $infoData['mbti_timecreated'] = $mbtiLog->timecreated;
            $infoData['mbti_log_id'] = $mbtiLog->id;
        }

        // Merge onboarding inputs saved via omniui (mathking DB, mdl_alt42g_* tables)
        // Load omniui/config.php to get MATHKING DB credentials
        try {
            $omniCandidates = [
                $_SERVER['DOCUMENT_ROOT'] . '/moodle/local/augmented_teacher/alt42/omniui/config.php',
                dirname(__DIR__, 3) . '/omniui/config.php',
                dirname(__DIR__, 4) . '/omniui/config.php'
            ];
            foreach ($omniCandidates as $cfgPath) {
                if (is_string($cfgPath) && file_exists($cfgPath) && is_readable($cfgPath)) {
                    include_once($cfgPath);
                    break;
                }
            }
            if (defined('MATHKING_DB_HOST') && defined('MATHKING_DB_NAME') && defined('MATHKING_DB_USER')) {
                $dsn = 'mysql:host=' . MATHKING_DB_HOST . ';dbname=' . MATHKING_DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                // learning_progress
                $stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_learning_progress WHERE userid = ?");
                $stmt->execute([$userid]);
                if ($row = $stmt->fetch()) {
                    $infoData['math_level'] = $row['math_level'] ?? '';
                    $infoData['concept_level'] = $row['concept_level'] ?? '';
                    $infoData['concept_progress'] = isset($row['concept_progress']) ? (int)$row['concept_progress'] : 0;
                    $infoData['advanced_level'] = $row['advanced_level'] ?? '';
                    $infoData['advanced_progress'] = isset($row['advanced_progress']) ? (int)$row['advanced_progress'] : 0;
                    $infoData['notes'] = $row['notes'] ?? '';
                    if (isset($row['weekly_hours'])) $infoData['weekly_hours'] = (int)$row['weekly_hours'];
                    if (!empty($row['academy_experience'])) $infoData['academy_experience'] = $row['academy_experience'];
                }
                // learning_style
                $stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_learning_style WHERE userid = ?");
                $stmt->execute([$userid]);
                if ($row = $stmt->fetch()) {
                    if (!empty($row['problem_preference'])) $infoData['problem_preference'] = $row['problem_preference'];
                    if (!empty($row['exam_style'])) $infoData['exam_style'] = $row['exam_style'];
                    if (isset($row['math_confidence'])) $infoData['math_confidence'] = (int)$row['math_confidence'];
                }
                // learning_method
                $stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_learning_method WHERE userid = ?");
                $stmt->execute([$userid]);
                if ($row = $stmt->fetch()) {
                    if (!empty($row['parent_style'])) $infoData['parent_style'] = $row['parent_style'];
                    if (!empty($row['stress_level'])) $infoData['stress_level'] = $row['stress_level'];
                    if (!empty($row['feedback_preference'])) $infoData['feedback_preference'] = $row['feedback_preference'];
                }
                // learning_goals
                $stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_learning_goals WHERE userid = ?");
                $stmt->execute([$userid]);
                if ($row = $stmt->fetch()) {
                    if (!empty($row['short_term_goal'])) $infoData['short_term_goal'] = $row['short_term_goal'];
                    if (!empty($row['mid_term_goal'])) $infoData['mid_term_goal'] = $row['mid_term_goal'];
                    if (!empty($row['long_term_goal'])) $infoData['long_term_goal'] = $row['long_term_goal'];
                    if (!empty($row['goal_note'])) $infoData['goal_note'] = $row['goal_note'];
                }
                // additional_info
                $stmt = $pdo->prepare("SELECT * FROM mdl_alt42g_additional_info WHERE userid = ?");
                $stmt->execute([$userid]);
                if ($row = $stmt->fetch()) {
                    if (!empty($row['favorite_food'])) $infoData['favorite_food'] = $row['favorite_food'];
                    if (!empty($row['favorite_fruit'])) $infoData['favorite_fruit'] = $row['favorite_fruit'];
                    if (!empty($row['favorite_snack'])) $infoData['favorite_snack'] = $row['favorite_snack'];
                    if (!empty($row['hobbies_interests'])) $infoData['hobbies_interests'] = $row['hobbies_interests'];
                    if (isset($row['fandom_yn'])) $infoData['fandom_yn'] = (bool)$row['fandom_yn'];
                    if (isset($row['data_consent'])) $infoData['data_consent'] = (bool)$row['data_consent'];
                }
            }
        } catch (Exception $e) {
            // Soft-fail: onboarding tables might not exist yet
            error_log("onboarding merge warn: " . $e->getMessage());
        }

        // Get latest learning assessment
        $assessment = null;
        try {
            if ($DB->get_manager()->table_exists(new xmldb_table('alt42o_learning_assessment_results'))) {
                $assessment = $DB->get_record_sql(
                    "SELECT * FROM {alt42o_learning_assessment_results}
                     WHERE userid = ?
                     ORDER BY created_at DESC
                     LIMIT 1",
                    [$userid]
                );
            }
        } catch (Exception $e) {
            // ignore
        }

        $assessmentData = [];
        if ($assessment) {
            $assessmentData = [
                'id' => $assessment->id,
                'cognitive_score' => $assessment->cognitive_score ?? 0,
                'emotional_score' => $assessment->emotional_score ?? 0,
                'behavioral_score' => $assessment->behavioral_score ?? 0,
                'overall_total' => $assessment->overall_total ?? 0,
                'created_at' => $assessment->created_at ?? time(),
                'session_id' => $assessment->session_id ?? ''
            ];

            // Get Q&A texts (qa01 to qa16)
            for ($i = 1; $i <= 16; $i++) {
                $field = sprintf('qa%02d', $i);
                if (property_exists($assessment, $field)) {
                    $assessmentData[$field] = $assessment->$field ?? '';
                }
            }
        }

        return [
            'success' => true,
            'info' => $infoData,
            'assessment' => $assessmentData,
            'timestamp' => time()
        ];

    } catch (Exception $e) {
        error_log("getOnboardingData error: " . $e->getMessage() .
                  " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ];
    }
}

/**
 * Check if report exists for user
 * @param int $userid User ID
 * @return array Report data or null
 */
function getExistingReport($userid) {
    global $DB;

    try {
        $report = $DB->get_record_sql(
            "SELECT * FROM {alt42o_onboarding_reports}
             WHERE userid = ? AND status != 'archived'
             ORDER BY generated_at DESC
             LIMIT 1",
            [$userid]
        );

        if ($report) {
            return [
                'success' => true,
                'exists' => true,
                'report' => $report
            ];
        } else {
            return [
                'success' => true,
                'exists' => false,
                'report' => null
            ];
        }

    } catch (Exception $e) {
        error_log("getExistingReport error: " . $e->getMessage() .
                  " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ];
    }
}

// Handle AJAX requests (can be disabled by defining ALT42_SERVICE_DISABLE_DIRECT_ACTION)
if (!defined('ALT42_SERVICE_DISABLE_DIRECT_ACTION') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $userid = isset($_POST['userid']) ? intval($_POST['userid']) : 0;

    if ($userid <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid user ID',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
        exit;
    }

    switch ($_POST['action']) {
        case 'getOnboardingData':
            echo json_encode(getOnboardingData($userid));
            break;

        case 'checkExistingReport':
            echo json_encode(getExistingReport($userid));
            break;

        case 'saveMBTI':
            $mbti = isset($_POST['mbti']) ? trim($_POST['mbti']) : '';

            if (empty($mbti)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'MBTI value is required',
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                exit;
            }

            // Validate MBTI format (4 characters)
            if (strlen($mbti) !== 4) {
                echo json_encode([
                    'success' => false,
                    'error' => 'MBTI must be 4 characters (e.g., ENFP, ISTJ)',
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                exit;
            }

            try {
                global $DB;

                // Convert to uppercase for consistency
                $mbtiUpper = strtoupper($mbti);

                // Insert new MBTI record
                $record = new stdClass();
                $record->userid = $userid;
                $record->mbti = $mbtiUpper;
                $record->timecreated = time();

                $mbtiId = $DB->insert_record('abessi_mbtilog', $record);

                echo json_encode([
                    'success' => true,
                    'mbti_id' => $mbtiId,
                    'mbti' => $mbtiUpper,
                    'timecreated' => $record->timecreated,
                    'message' => 'MBTI saved successfully'
                ]);

            } catch (Exception $e) {
                error_log("saveMBTI error: " . $e->getMessage() .
                          " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Unknown action',
                'file' => __FILE__,
                'line' => __LINE__
            ]);
    }
    exit;
}
