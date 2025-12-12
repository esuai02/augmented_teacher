<?php
/**
 * 학생 온보딩 시스템 - 메인 엔트리 포인트
 *
 * 모듈화된 구조로 리팩토링된 버전
 * - 설정과 데이터베이스 로직 분리
 * - 템플릿 기반 렌더링
 * - 외부 CSS/JS 파일 사용
 */

// Moodle 세션과의 충돌 방지를 위해 native session_start() 제거
// session_start(); // Removed to prevent Moodle session conflict

// Moodle 설정 포함 - 다중 경로 시도
$moodle_loaded = false;
$moodle_paths = [
    "/home/moodle/public_html/moodle/config.php",
    $_SERVER['DOCUMENT_ROOT'] . "/moodle/config.php",
    $_SERVER['DOCUMENT_ROOT'] . "/config.php",
    dirname($_SERVER['DOCUMENT_ROOT']) . "/moodle/config.php",
    "../../../config.php",
    "../../../../config.php",
    "../../../../../config.php",
];

foreach ($moodle_paths as $path) {
    if (file_exists($path) && is_readable($path)) {
        $original_error_reporting = error_reporting(E_ERROR | E_PARSE);
        try {
            include_once($path);
            $moodle_loaded = true;
            error_reporting($original_error_reporting);
            break;
        } catch (Exception $e) {
            error_reporting($original_error_reporting);
            continue;
        }
    }
}

// Moodle 로드 실패 시 기본 객체 생성
if (!$moodle_loaded) {
    // 독립 실행 모드 - PHP native 세션 사용
    if (!session_id()) {
        session_start();
    }
    if (!isset($DB)) {
        $DB = new stdClass();
    }
    if (!isset($USER)) {
        $USER = new stdClass();
        $USER->id = 0;
    }
} else {
    // Moodle 로드 성공 - Moodle 세션 관리 사용
    global $CFG, $DB, $USER, $SESSION;

    // Moodle 세션 초기화 (이미 시작되지 않은 경우)
    if (!isset($SESSION)) {
        require_once($CFG->libdir . '/sessionlib.php');
        \core\session\manager::start_session();
    }

    // 로그인 상태 유지 (자동 로그아웃 방지)
    if (isloggedin() && !isguestuser()) {
        \core\session\manager::keepalive();
    }

    // 게스트 접근 허용 설정
    if (isset($_GET['userid']) || isset($_GET['test']) || isset($_GET['demo'])) {
        // URL 파라미터가 있으면 강제 로그인 비활성화
        $PAGE->set_context(context_system::instance());
        $PAGE->set_url('/local/augmented_teacher/alt42/orchestration/onboarding_info.php');
    }
}

global $DB, $USER;

// 온보딩 시스템 설정 및 함수 포함
require_once __DIR__ . '/onboarding/includes/config.php';
require_once __DIR__ . '/onboarding/includes/database.php';

// 사용자 ID 가져오기 - 다양한 소스에서 시도
$studentid = 0;

// 1. GET 파라미터 확인
if (isset($_GET['userid']) && intval($_GET['userid']) > 0) {
    $studentid = intval($_GET['userid']);
}
// 2. POST 파라미터 확인
elseif (isset($_POST['userid']) && intval($_POST['userid']) > 0) {
    $studentid = intval($_POST['userid']);
}
// 3. Moodle USER 객체 확인
elseif (isset($USER) && isset($USER->id) && $USER->id > 0) {
    $studentid = $USER->id;
}
// 4. 세션에서 확인
elseif (isset($_SESSION['studentId']) && $_SESSION['studentId'] > 0) {
    $studentid = $_SESSION['studentId'];
}
// 5. 쿠키에서 확인 (선택적)
elseif (isset($_COOKIE['moodle_userid']) && intval($_COOKIE['moodle_userid']) > 0) {
    $studentid = intval($_COOKIE['moodle_userid']);
}

// 테스트 모드 - 개발 환경용
if ($studentid == 0 && (isset($_GET['test']) || isset($_GET['demo']))) {
    $studentid = 2; // 테스트용 기본 사용자 ID
    $_SESSION['demo_mode'] = true;
}

// 사용자 ID가 여전히 0이고 Moodle이 로드되지 않은 경우 독립 실행 모드
if ($studentid == 0) {
    if (!$moodle_loaded) {
        // 독립 실행 모드 - 임시 사용자 ID 생성
        if (!isset($_SESSION['temp_userid'])) {
            $_SESSION['temp_userid'] = 1000 + rand(1, 999);
        }
        $studentid = $_SESSION['temp_userid'];
        $_SESSION['standalone_mode'] = true;
    } else if (isset($SESSION)) {
        // Moodle 세션 사용
        if (!isset($SESSION->temp_userid)) {
            $SESSION->temp_userid = 1000 + rand(1, 999);
        }
        $studentid = $SESSION->temp_userid;
        $SESSION->standalone_mode = true;
    }
}

// 디버깅 로그
if (DEBUG_MODE || isset($_GET['debug'])) {
    error_log("=== Onboarding System Start ===");
    error_log("Student ID: " . $studentid);
    error_log("GET userid: " . (isset($_GET['userid']) ? $_GET['userid'] : 'NOT SET'));
    error_log("POST userid: " . (isset($_POST['userid']) ? $_POST['userid'] : 'NOT SET'));
    error_log("Moodle USER->id: " . ($USER->id ?? 'NULL'));
}

// AJAX 요청 처리
if (isset($_POST['action']) && !empty($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $response = ['success' => false, 'message' => ''];
        $action = $_POST['action'];

        switch ($action) {
            case 'loadOnboardingData':
                $userid = isset($_POST['userid']) ? intval($_POST['userid']) : 0;
                if ($userid > 0) {
                    // Load both Moodle data and onboarding data
                    $moodleData = loadMoodleUserData($userid);
                    $onboardingData = loadOnboardingData($userid);
                    $data = array_merge($moodleData, $onboardingData);

                    $response = [
                        'success' => true,
                        'data' => $data
                    ];

                    if (DEBUG_MODE) {
                        $response['debug'] = [
                            'moodle_fields' => array_keys($moodleData),
                            'onboarding_fields' => array_keys($onboardingData),
                            'hierarchical_fields' => [
                                'conceptEducationLevel' => $data['conceptEducationLevel'] ?? 'not set',
                                'conceptSemesters' => $data['conceptSemesters'] ?? 'not set'
                            ]
                        ];
                    }
                } else {
                    $response['message'] = 'Invalid user ID';
                }
                break;

            case 'saveOnboarding':
                $userid = isset($_POST['userid']) ? intval($_POST['userid']) : 0;

                // Handle formData - can be JSON string or array
                $formData = [];
                if (isset($_POST['formData'])) {
                    if (is_string($_POST['formData'])) {
                        // Decode JSON string
                        $formData = json_decode($_POST['formData'], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $response['message'] = 'Invalid JSON data: ' . json_last_error_msg();
                            break;
                        }
                    } else {
                        // Direct array
                        $formData = $_POST['formData'];
                    }
                }

                // Debug logging
                if (DEBUG_MODE || isset($_GET['debug'])) {
                    error_log("SaveOnboarding - UserID: $userid");
                    error_log("SaveOnboarding - FormData keys: " . implode(', ', array_keys($formData)));
                    error_log("SaveOnboarding - Has hierarchical fields: " .
                              (isset($formData['conceptEducationLevel']) ? 'Yes' : 'No'));
                }

                if ($userid > 0 && !empty($formData)) {
                    try {
                        $result = saveOnboardingData($userid, $formData);
                        if ($result && isset($result['success']) && $result['success']) {
                            // Moodle 데이터 동기화
                            syncToMoodle($userid, $formData);
                            $response = [
                                'success' => true,
                                'message' => '온보딩 정보가 성공적으로 저장되었습니다.',
                                'onboardingId' => $result['onboardingId'] ?? null
                            ];

                            if (DEBUG_MODE) {
                                $response['debug'] = [
                                    'userid' => $userid,
                                    'fields_saved' => count($formData),
                                    'onboarding_id' => $result['onboardingId'] ?? null
                                ];
                            }
                        } else {
                            $response['message'] = isset($result['error']) ?
                                                   $result['error'] :
                                                   '저장 중 오류가 발생했습니다.';
                            if (DEBUG_MODE && isset($result['error'])) {
                                $response['debug_error'] = $result['error'];
                            }
                        }
                    } catch (Exception $e) {
                        error_log("SaveOnboarding Exception: " . $e->getMessage());
                        $response['message'] = '저장 중 예외 발생: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = $userid <= 0 ?
                                          'Invalid user ID' :
                                          'No data provided to save';
                    if (DEBUG_MODE) {
                        $response['debug'] = [
                            'userid' => $userid,
                            'formData_empty' => empty($formData)
                        ];
                    }
                }
                break;

            case 'loadAssessmentData':
                $userid = isset($_POST['userid']) ? intval($_POST['userid']) : 0;
                if ($userid > 0) {
                    $data = loadAssessmentData($userid);
                    $response = [
                        'success' => true,
                        'data' => $data
                    ];
                } else {
                    $response['message'] = 'Invalid user ID';
                }
                break;

            case 'getSessionData':
                // 세션에서 데이터 반환 (Moodle vs Native)
                $formData = null;
                if ($moodle_loaded && isset($SESSION) && isset($SESSION->formData)) {
                    $formData = $SESSION->formData;
                } else if (isset($_SESSION['formData'])) {
                    $formData = $_SESSION['formData'];
                }

                // If no session data, load from database
                if (!$formData && $studentid > 0) {
                    $moodleData = loadMoodleUserData($studentid);
                    $onboardingData = loadOnboardingData($studentid);
                    $formData = array_merge($moodleData, $onboardingData);

                    if (DEBUG_MODE) {
                        error_log("Loading fresh data for session - hierarchical fields: " . json_encode([
                            'mathLevel' => $formData['mathLevel'] ?? 'not set',
                            'conceptEducationLevel' => $formData['conceptEducationLevel'] ?? 'not set',
                            'conceptSemesters' => $formData['conceptSemesters'] ?? 'not set'
                        ]));
                    }
                }

                if ($formData) {
                    $response = [
                        'success' => true,
                        'data' => $formData
                    ];

                    if (DEBUG_MODE) {
                        $response['debug'] = [
                            'source' => isset($_SESSION['formData']) ? 'session' : 'database',
                            'total_fields' => count($formData),
                            'mathLevel' => $formData['mathLevel'] ?? 'missing',
                            'hierarchical_check' => [
                                'conceptEducationLevel' => $formData['conceptEducationLevel'] ?? 'missing',
                                'conceptSemesters' => $formData['conceptSemesters'] ?? 'missing'
                            ]
                        ];
                    }
                } else {
                    $response = [
                        'success' => false,
                        'data' => null
                    ];
                }
                break;

            case 'saveSessionData':
                // 세션에 데이터 저장 (Moodle vs Native)
                if (isset($_POST['data'])) {
                    if ($moodle_loaded && isset($SESSION)) {
                        $SESSION->formData = $_POST['data'];
                    } else {
                        $_SESSION['formData'] = $_POST['data'];
                    }
                    $response = [
                        'success' => true,
                        'message' => 'Session data saved'
                    ];
                } else {
                    $response['message'] = 'No data provided';
                }
                break;

            default:
                $response['message'] = 'Unknown action';
        }

    } catch (Exception $e) {
        error_log("Onboarding API Error: " . $e->getMessage());
        $response = [
            'success' => false,
            'message' => '처리 중 오류가 발생했습니다.',
            'error' => DEBUG_MODE ? $e->getMessage() : null
        ];
    }

    echo json_encode($response);
    exit;
}

// 초기 데이터 로드
$initialData = [];
$assessmentData = [];
$studentName = '';

if ($studentid > 0) {
    // Moodle 사용자 데이터 로드
    $moodleData = loadMoodleUserData($studentid);

    // 기존 온보딩 데이터 로드
    $onboardingData = loadOnboardingData($studentid);

    // 평가 데이터 로드
    $assessmentData = loadAssessmentData($studentid);

    // 데이터 병합 (온보딩 데이터가 우선)
    $initialData = array_merge($moodleData, $onboardingData);

    // 학생 이름 설정
    $studentName = $initialData['studentName'] ?? '';

    if (DEBUG_MODE || isset($_GET['debug'])) {
        error_log("=== Initial Data Loading Debug ===");
        error_log("Student ID: " . $studentid);
        error_log("Student Name: " . $studentName);
        error_log("Moodle data keys: " . implode(', ', array_keys($moodleData)));
        error_log("Onboarding data keys: " . implode(', ', array_keys($onboardingData)));
        error_log("Hierarchical fields check:");
        error_log("  conceptEducationLevel: " . ($initialData['conceptEducationLevel'] ?? 'not set'));
        error_log("  conceptSemesters: " . json_encode($initialData['conceptSemesters'] ?? 'not set'));
        error_log("  advancedEducationLevel: " . ($initialData['advancedEducationLevel'] ?? 'not set'));
        error_log("  advancedOptions: " . json_encode($initialData['advancedOptions'] ?? 'not set'));
        error_log("  mathLevel: " . ($initialData['mathLevel'] ?? 'not set'));
    }
}

// 세션에 초기 데이터 저장 (Moodle vs Native 세션 구분)
if ($moodle_loaded && isset($SESSION)) {
    // Moodle 세션 사용
    $SESSION->formData = $initialData;
    $SESSION->studentId = $studentid;
} else {
    // Native PHP 세션 사용
    $_SESSION['formData'] = $initialData;
    $_SESSION['studentId'] = $studentid;
}

// 템플릿 렌더링
include __DIR__ . '/onboarding/templates/header.php';
?>

<!-- 메인 컨텐츠 영역 -->
<?php if ($studentid > 0): ?>

    <!-- 저장된 정보 섹션 -->
    <?php if (!empty($onboardingData) && isset($onboardingData['isOnboardingComplete']) && $onboardingData['isOnboardingComplete']): ?>
        <?php include __DIR__ . '/onboarding/templates/saved-info.php'; ?>
    <?php endif; ?>

    <!-- 평가 데이터 섹션 -->
    <?php if (!empty($assessmentData)): ?>
        <?php include __DIR__ . '/onboarding/templates/assessment-section.php'; ?>
    <?php endif; ?>

    <!-- 온보딩 폼 섹션 -->
    <?php include __DIR__ . '/onboarding/templates/form-sections.php'; ?>

<?php else: ?>
    <!-- 사용자 ID가 없는 경우 -->
    <div class="content-card">
        <div style="background: #fee2e2; padding: 2rem; border-radius: 0.75rem; text-align: center;">
            <h2 style="color: #991b1b; margin-bottom: 1rem;">⚠️ 접근 권한 오류</h2>
            <p style="color: #991b1b;">
                유효한 사용자 정보를 찾을 수 없습니다.<br>
                로그인 상태를 확인해주세요.
            </p>
        </div>
    </div>
<?php endif; ?>

<?php
// 푸터 템플릿 포함
include __DIR__ . '/onboarding/templates/footer.php';
?>