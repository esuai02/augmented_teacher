<?php
/**
 * 시험 전략 생성 API
 * Step 3 목표분석 결과와 Step 2 시험일정을 결합하여 GPT로 맞춤형 전략 생성
 */

// 에러 출력 설정
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=UTF-8');

// API 설정 파일 로드 (omniui 우선 → 공통 gpt_config 보조)
// 1) omniui/config.php 시도: OPENAI_* 상수와 DB 별칭 제공
try {
    // 조용히 include (경고 억제)
    @include_once('/home/moodle/public_html/moodle/local/augmented_teacher/alt42/omniui/config.php');
} catch (Exception $e) {
    // ignore
}
// 2) 공통 gpt_config는 미정의일 때만 로드
if (!defined('OPENAI_API_KEY')) {
    @include_once(__DIR__ . '/../../common/api/gpt_config.php');
}
// 3) 호환: OPENAI_API_ENDPOINT만 있는 코드 대비
if (!defined('OPENAI_API_URL') && defined('OPENAI_API_ENDPOINT')) {
    define('OPENAI_API_URL', OPENAI_API_ENDPOINT);
}

// 전역 JSON 종료 플래그 및 오류/종료 핸들러 등록
$GLOBALS['__json_emitted'] = false;
set_error_handler(function($severity, $message, $file, $line) {
    // 모든 런타임 경고/공지 등은 로그만 남기고 중단하지 않음
    error_log("[Agent02 API] PHP error($severity): $message @ $file:$line");
    return true; // 기본 핸들링 방지
});
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && empty($GLOBALS['__json_emitted'])) {
        if (ob_get_length()) { @ob_clean(); }
        $payload = [
            'success' => false,
            'error' => 'fatal: ' . $err['message'],
            'message' => '전략 생성 중 서버 오류가 발생했습니다.'
        ];
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $payload['debug'] = [
                'type' => $err['type'] ?? null,
                'file' => $err['file'] ?? null,
                'line' => $err['line'] ?? null
            ];
        }
        echo json_encode($payload);
    }
});

try {
    // Moodle 연결
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;

    // XMLDB 라이브러리 로드
    require_once($CFG->libdir.'/ddllib.php');

    // 로그인 확인 - 더 유연하게 처리
    $is_logged_in = function_exists('isloggedin') && isloggedin();
    if (!$is_logged_in) {
        // 세션에서 사용자 정보 복원 시도
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $fallback_userid = $_SESSION['user_id'] ?? $_GET['userid'] ?? null;
        if ($fallback_userid) {
            try {
                $user_record = $DB->get_record('user', ['id' => $fallback_userid]);
                if ($user_record) {
                    $USER = $user_record;
                    $is_logged_in = true;
                }
            } catch (Exception $e) {
                // 무시
            }
        }

        if (!$is_logged_in) {
            throw new Exception('로그인이 필요합니다');
        }
    }

    // POST 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);
    $exam_timeline = $input['exam_timeline'] ?? '';
    $userid = $input['userid'] ?? $USER->id;

    if (empty($exam_timeline)) {
        throw new Exception('시험일정을 선택해주세요');
    }

    // 사용자 정보 가져오기
    $user_info = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
    if (!$user_info) {
        throw new Exception('사용자를 찾을 수 없습니다');
    }

    // 이모지 제거 함수 - 더 포괄적인 패턴
    function remove_emojis($text) {
        if (empty($text)) return '';

        // 모든 이모지 범위를 포함하는 더 광범위한 패턴
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text); // Emoticons
        $text = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $text); // Misc Symbols
        $text = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $text); // Transport
        $text = preg_replace('/[\x{1F1E0}-\x{1F1FF}]/u', '', $text); // Flags
        $text = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $text);   // Misc symbols
        $text = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $text);   // Dingbats
        $text = preg_replace('/[\x{1F900}-\x{1F9FF}]/u', '', $text); // Supplemental
        $text = preg_replace('/[\x{1FA70}-\x{1FAFF}]/u', '', $text); // Extended
        $text = preg_replace('/[\x{FE00}-\x{FE0F}]/u', '', $text);   // Variation

        // 추가로 4바이트 UTF-8 문자 제거
        $text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);

        // 특수 문자 정리
        $text = str_replace(['🎯', '📋', '📅', '📆', '⏰', '🚨', '🔥', '💯', '📖', '🏖️'], '', $text);

        // 공백 정리
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    $student_name = trim($user_info->firstname . ' ' . $user_info->lastname);
    $student_name = remove_emojis($student_name);
    $student_name = trim($student_name);

    // omniui 설정 로드 및 PDO 연결 (실데이터 사용)
    $omniuiCfgIncluded = false;
    try {
        // omniui/config.php 포함 중 발생 가능한 출력/경고는 모두 버퍼로 흡수 후 폐기
        error_reporting(0);
        ini_set('display_errors', 0);
        $incOb = false;
        if (ob_get_level() === 0) { ob_start(); $incOb = true; }

        // Moodle $CFG 보존
        $moodleCFG = isset($CFG) ? clone $CFG : null;

        @include_once('/home/moodle/public_html/moodle/local/augmented_teacher/alt42/omniui/config.php');

        // omniui/config.php가 재정의한 $CFG에서 DB 접속 정보만 추출
        $omniHost = isset($CFG->dbhost) ? $CFG->dbhost : null;
        $omniName = isset($CFG->dbname) ? $CFG->dbname : null;
        $omniUser = isset($CFG->dbuser) ? $CFG->dbuser : null;
        $omniPass = isset($CFG->dbpass) ? $CFG->dbpass : null;

        // Moodle $CFG 복구
        if ($moodleCFG !== null) {
            $CFG = $moodleCFG;
        }

        if ($incOb) { ob_end_clean(); }

        if ($omniHost && $omniName) {
            $dsn = "mysql:host={$omniHost};dbname={$omniName};charset=utf8mb4";
            $pdo = new PDO($dsn, $omniUser, $omniPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $omniuiCfgIncluded = true;
        }
    } catch (Exception $e) {
        // 무시하고 Moodle $DB만 사용
        error_log('omniui config include/DB init error: ' . $e->getMessage());
    }

    // omniui/대시보드와 동일 스키마에서 실데이터 조회
    $student_fullname = trim(($user_info->firstname ?? '') . ' ' . ($user_info->lastname ?? ''));
    $school = '';
    $grade_display = '';
    $semester = '';
    $exam_type = '';
    $start_date = '';
    $end_date = '';
    $math_exam_date = '';
    $exam_scope = '';
    $study_level = '';
    $today_goal = '';
    $weekly_goal = '';
    $quarter_goal = '';

    if (!empty($pdo)) {
        // alt42t 사용자 정보
        $u = $pdo->prepare("SELECT * FROM mdl_alt42t_users WHERE userid = ?");
        $u->execute([$userid]);
        $alt_user = $u->fetch();

        // 시험/날짜/범위/단계
        if ($alt_user) {
            $ex = $pdo->prepare("SELECT * FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? LIMIT 1");
            $ex->execute([$alt_user['school_name'], $alt_user['grade']]);
            $exam = $ex->fetch();

            if ($exam) {
                $ed = $pdo->prepare("SELECT * FROM mdl_alt42t_exam_dates WHERE exam_id = ? AND user_id = ?");
                $ed->execute([$exam['exam_id'], $alt_user['id']]);
                $edrow = $ed->fetch();

                $sr = $pdo->prepare("SELECT * FROM mdl_alt42t_exam_resources WHERE exam_id = ? AND user_id = ?");
                $sr->execute([$exam['exam_id'], $alt_user['id']]);
                $res = $sr->fetch();

                $ss = $pdo->prepare("SELECT * FROM mdl_alt42t_study_status WHERE user_id = ? AND exam_id = ?");
                $ss->execute([$alt_user['id'], $exam['exam_id']]);
                $stage = $ss->fetch();

                $exam_type = $exam['exam_type'] ?? '';
                $start_date = $edrow['start_date'] ?? '';
                $end_date = $edrow['end_date'] ?? '';
                // 일부 환경에서 math_exam_date 컬럼명이 math_date일 수 있음
                $math_exam_date = $edrow['math_exam_date'] ?? ($edrow['math_date'] ?? '');
                $exam_scope = '';
                if ($res && !empty($res['tip_text'])) {
                    $exam_scope = str_replace('시험 범위: ', '', $res['tip_text']);
                }
                $study_level = $stage['status'] ?? '';
            }

            $school = $alt_user['school_name'] ?? '';
            // 학년 표시: alt42t 저장 숫자를 그대로 사용(예: 1,2,3)
            if (!empty($alt_user['grade'])) {
                $grade_display = $alt_user['grade'] . '학년';
            }
        }

        // 학기: 시험종류에서 유추
        if ($exam_type) {
            $semester = (mb_strpos($exam_type, '1학기') !== false) ? '1학기' : ((mb_strpos($exam_type, '2학기') !== false) ? '2학기' : '');
        }

        // 목표(오늘/주간/분기)
        $tg = $pdo->prepare("SELECT text FROM mdl_abessi_today WHERE userid = ? AND type LIKE '오늘목표' ORDER BY id DESC LIMIT 1");
        $tg->execute([$userid]);
        $today_goal = $tg->fetchColumn() ?: '';

        $wg = $pdo->prepare("SELECT text FROM mdl_abessi_today WHERE userid = ? AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
        $wg->execute([$userid]);
        $weekly_goal = $wg->fetchColumn() ?: '';

        $qg = $pdo->prepare("SELECT text FROM mdl_abessi_today WHERE userid = ? AND type LIKE '시험목표' ORDER BY id DESC LIMIT 1");
        $qg->execute([$userid]);
        $quarter_goal = $qg->fetchColumn() ?: '';
    }

    // 8주 전 진입 판별
    $target_date_str = $math_exam_date ?: ($start_date ?: $end_date);
    $eight_week_flag = false;
    $days_to_exam = null;
    if (!empty($target_date_str)) {
        try {
            $today = new DateTime('today');
            $examAt = new DateTime($target_date_str);
            $diff = $today->diff($examAt);
            $days_to_exam = $diff->invert ? -$diff->days : $diff->days;
            if ($days_to_exam !== null && $days_to_exam <= 56 && $days_to_exam >= 0) {
                $eight_week_flag = true;
            }
        } catch (Exception $e) {}
    }

    // 최신 목표분석 결과 가져오기 (Step 3에서 생성된 데이터)
    $goal_analysis_record = null;
    $goal_analysis_data = '';

    // mdl_alt42_goal_analysis 테이블에서 최신 분석 결과 조회
    try {
        $goal_analysis_record = $DB->get_record_sql(
            "SELECT * FROM mdl_alt42_goal_analysis
             WHERE userid = ?
             ORDER BY timecreated DESC
             LIMIT 1",
            [$userid]
        );

        if ($goal_analysis_record && !empty($goal_analysis_record->analysis_result)) {
            $goal_analysis_data = $goal_analysis_record->analysis_result;
        }
    } catch (Exception $e) {
        // 목표분석 테이블이 없거나 데이터가 없는 경우: 프롬프트에서 언급하지 않도록 공란 유지
        $goal_analysis_data = "";
    }

    // 시험일정별 맞춤 설명
    $timeline_descriptions = [
        '🏖️ 방학' => '여유로운 방학 기간을 활용한 체계적 장기 학습 계획',
        '📅 D-2개월' => '2개월 여유를 둔 체계적이고 균형잡힌 학습 전략',
        '📆 D-1개월' => '1개월 집중 학습을 위한 효율적 시간 배분 전략',
        '⏰ D-2주' => '2주 단기집중 핵심 개념 마스터 전략',
        '🚨 D-1주' => '1주 최종 점검 및 실전 대비 전략',
        '🔥 D-3일' => '3일 최종 마무리 및 컨디션 관리 전략',
        '💯 D-1일' => '시험 전날 최종 점검 및 멘탈 관리',
        '📖 시험없음' => '평상시 꾸준한 학습 루틴 구축 전략'
    ];

    $timeline_desc = $timeline_descriptions[$exam_timeline] ?? $exam_timeline;

    // GPT API 호출 함수
    function call_gpt_api($api_key, $prompt) {
        $start_time = microtime(true);

        $data = [
            'model' => OPENAI_MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '당신은 개인 맞춤형 학습 전략 전문가입니다. 학생의 목표분석 결과와 시험일정을 바탕으로 실용적이고 구체적인 시험준비 전략을 제공합니다.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => OPENAI_MAX_TOKENS,
            'temperature' => OPENAI_TEMPERATURE
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, OPENAI_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, OPENAI_TIMEOUT);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $end_time = microtime(true);
        $generation_time = round(($end_time - $start_time) * 1000); // 밀리초

        if ($http_code !== 200) {
            throw new Exception('GPT API 호출 실패: HTTP ' . $http_code);
        }

        $result = json_decode($response, true);
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('GPT API 응답 형식 오류');
        }

        return [
            'content' => $result['choices'][0]['message']['content'],
            'generation_time' => $generation_time
        ];
    }

    // GPT 프롬프트 구성 (실제 데이터 기반, 사용자 요구 사양 반영)
    $student_name = $student_fullname ?: $student_name;

    $lines = [];
    $lines[] = "다음의 실제 DB 값(하드코딩 금지)을 기반으로 개인화된 중등/고등 수학 시험 대비 전략을 생성하세요.";
    $lines[] = "이 에이전트는 매주 첫 수업시간 또는 요청 시 실행되며, 시험일 기준 8주 전 진입 시점부터 전략 수립을 시작합니다.";
    $lines[] = "기본 전략 + 학생의 수준, 준비상태, 목표점수, 학년, 시험 중요도, 개인 동기, 부모님 대화내용, 취약단원/유형 분포, 사용하는 교재, 기출문제, 실전 경험, MBTI, '개념공부-유형연습-심화학습-기출풀이' 순서를 가정하되, 가상 시뮬레이션을 통해 최적화된 선택과 집중을 구성합니다.";
    $lines[] = "학생이 수용·실행할 만해야 하며, 정서/노력/실제 공부량/컨텐츠당 소요시간의 수치적 계산을 반영해 학습·정리·실전훈련 조합을 재구성합니다.";
    $lines[] = "";
    $lines[] = "[입력 데이터]";
    $lines[] = "- 선택한 시험일정: {$exam_timeline} ({$timeline_desc})";
    $lines[] = "- 이름: " . ($student_name ?: '');
    $lines[] = "- 학교: " . ($school ?: '');
    $lines[] = "- 학년: " . ($grade_display ?: '');
    $lines[] = "- 학기: " . ($semester ?: '');
    $lines[] = "- 시험종류: " . ($exam_type ?: '');
    $lines[] = "- 시험시작일: " . ($start_date ?: '');
    $lines[] = "- 시험종료일: " . ($end_date ?: '');
    $lines[] = "- 수학시험일: " . ($math_exam_date ?: '');
    $lines[] = "- 시험범위: " . ($exam_scope ?: '');
    $lines[] = "- 단계선택: " . ($study_level ?: '');
    $lines[] = "- 오늘의 목표: " . ($today_goal ?: '');
    $lines[] = "- 주간 목표: " . ($weekly_goal ?: '');
    $lines[] = "- 분기 목표: " . ($quarter_goal ?: '');
    if ($days_to_exam !== null) {
        $lines[] = "- 시험까지 남은 일수: D-" . max(0, $days_to_exam);
        $lines[] = "- 8주 전 진입 여부: " . ($eight_week_flag ? '예' : '아니오');
    }
    $lines[] = "";
    if (trim($goal_analysis_data) !== '') {
        $lines[] = "[목표분석 요약]";
        $lines[] = trim($goal_analysis_data);
        $lines[] = "";
    }
    $lines[] = "[출력 요구] (HTML로 출력하고, 누락된 값이나 데이터 없음은 언급하지 않고 생략한다)";
    $lines[] = "0) 전체를 하나의 전략 문서처럼 연결 문장으로 자연스럽게 이어서 작성";
    $lines[] = "1) 8주→6주→4주→2주→1주→D-3→D-1 구간별 핵심전략과 주당 학습시간/콘텐츠 수 권장량";
    $lines[] = "2) 개념→유형→심화→기출 풀이의 최적 배치(학생의 단계선택과 목표 반영)";
    $lines[] = "3) 약점 단원/유형에 대한 선택과 집중(시험범위 기반)";
    $lines[] = "4) 실전훈련(모의/타이머/스피드서술 등) 투입 시점과 횟수";
    $lines[] = "5) 정서/동기 관리(실행가능한 미세습관 3개, 부모 코칭 문장 2줄)";
    $lines[] = "6) 콘텐츠당 소요시간 가정과 총 소화량 산출(간단 표로 수치화)";
    $lines[] = "7) 주간 체크리스트(체크박스)와 위험 신호(리스크) 감지 기준";
    $lines[] = "8) 첫 주 7일 운영 샘플 타임라인(분 단위 요약)";
    $lines[] = "9) 제목은 다음 클래스를 사용: ";
    $lines[] = "   - 계획 개요: <h3 class=\"ex-title ex-title-overview\">";
    $lines[] = "   - 구간별 전략: <h3 class=\"ex-title ex-title-phases\">";
    $lines[] = "   - 실전훈련: <h3 class=\"ex-title ex-title-practice\">";
    $lines[] = "   - 체크리스트: <h3 class=\"ex-title ex-title-checklist\">";
    $lines[] = "   - 실용 요소: <h3 class=\"ex-title ex-title-practical\">";
    $lines[] = "10) 표/리스트/문단은 HTML로 출력";

    $gpt_prompt = implode("\n", $lines);

    $generated_strategy = '';
    $generation_time_ms = 0;

    // GPT API 호출
    if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
        try {
            $gpt_result = call_gpt_api(OPENAI_API_KEY, $gpt_prompt);
            $generated_strategy = $gpt_result['content'];
            $generation_time_ms = $gpt_result['generation_time'];
        } catch (Exception $e) {
            // GPT API 실패 시 기본 전략 제공
            $generated_strategy = generate_fallback_strategy($student_name, $exam_timeline, $timeline_desc);
        }
    } else {
        // API 키가 없는 경우 기본 전략 제공
        $generated_strategy = generate_fallback_strategy($student_name, $exam_timeline, $timeline_desc);
    }

    // 전략 요약 생성
    $strategy_summary = generate_strategy_summary($exam_timeline);

    // 데이터베이스에 저장 - 이모지 제거 및 안전한 처리
    $record = new stdClass();
    $record->userid = intval($userid);

    // exam_timeline 매핑 테이블 - 이모지 포함 원본을 깨끗한 텍스트로
    $timeline_map = [
        '🏖️ 방학' => 'vacation',
        '📅 D-2개월' => 'D-2month',
        '📆 D-1개월' => 'D-1month',
        '⏰ D-2주' => 'D-2week',
        '🚨 D-1주' => 'D-1week',
        '🔥 D-3일' => 'D-3day',
        '💯 D-1일' => 'D-1day',
        '📖 시험없음' => 'no-exam'
    ];

    // exam_timeline 정제
    $clean_timeline = '';
    if (isset($timeline_map[$exam_timeline])) {
        // 정확한 매칭이 있으면 사용
        $clean_timeline = $timeline_map[$exam_timeline];
    } else {
        // 없으면 이모지 제거 후 사용
        $clean_timeline = remove_emojis($exam_timeline);
        if (empty($clean_timeline)) {
            $clean_timeline = 'unknown';
        }
    }

    $record->exam_timeline = substr($clean_timeline, 0, 50);

    // 모든 텍스트 필드 이모지 제거
    $record->goal_analysis_data = substr(remove_emojis($goal_analysis_data), 0, 60000);
    $record->generated_strategy = substr(remove_emojis($generated_strategy), 0, 60000);
    $record->strategy_summary = substr(remove_emojis($strategy_summary), 0, 900);
    $record->gpt_model = substr(OPENAI_MODEL, 0, 50);
    $record->generation_time_ms = intval($generation_time_ms);
    $record->timecreated = time();
    $record->timemodified = time();

    // 테이블이 존재하지 않으면 생성
    $dbman = $DB->get_manager();
    $table_exists = false;

    try {
        // Moodle 표준 테이블 존재 확인 (mdl_ 접두사 제외)
        $table_exists = $dbman->table_exists('alt42g_exam_strategies');

        if (!$table_exists) {
            // 테이블 생성 시도 - 전체 테이블명 사용 (mdl_ 포함)
            $sql_create = "CREATE TABLE IF NOT EXISTS mdl_alt42g_exam_strategies (
                id BIGINT(10) NOT NULL AUTO_INCREMENT,
                userid BIGINT(10) NOT NULL,
                exam_timeline VARCHAR(50) NOT NULL,
                goal_analysis_data LONGTEXT,
                generated_strategy LONGTEXT,
                strategy_summary TEXT,
                gpt_model VARCHAR(50) DEFAULT 'gpt-4o',
                generation_time_ms INT DEFAULT 0,
                timecreated BIGINT(10) NOT NULL,
                timemodified BIGINT(10) NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_userid (userid),
                INDEX idx_exam_timeline (exam_timeline),
                INDEX idx_timecreated (timecreated)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $DB->execute($sql_create);

            // 메타데이터 테이블도 생성
            $sql_meta = "CREATE TABLE IF NOT EXISTS mdl_alt42g_exam_strategy_meta (
                id BIGINT(10) NOT NULL AUTO_INCREMENT,
                strategy_type VARCHAR(100) NOT NULL,
                description TEXT,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                timecreated BIGINT(10) NOT NULL,
                timemodified BIGINT(10) NOT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $DB->execute($sql_meta);

            // 기본 메타데이터 삽입
            try {
                $meta1 = new stdClass();
                $meta1->strategy_type = 'exam_preparation';
                $meta1->description = '시험 준비 전략 생성';
                $meta1->is_active = 1;
                $meta1->timecreated = time();
                $meta1->timemodified = time();
                $DB->insert_record('alt42g_exam_strategy_meta', $meta1);
            } catch (Exception $e) {
                // 이미 존재하면 무시
            }
        }
    } catch (Exception $e) {
        // 테이블 생성 실패 시 상세 오류 로깅
        error_log('ALT42 테이블 생성 오류: ' . $e->getMessage());
    }

    // 데이터 삽입 시도 (실패해도 전략은 반환)
    $strategy_id = 0;
    $db_save_success = false;
    $db_error_message = '';
    $debug_info = [];

    try {
        // 디버깅: 삽입할 데이터 확인
        $debug_info['record'] = [
            'userid' => $record->userid,
            'exam_timeline' => $record->exam_timeline,
            'goal_data_len' => strlen($record->goal_analysis_data),
            'strategy_len' => strlen($record->generated_strategy),
            'summary_len' => strlen($record->strategy_summary),
            'gpt_model' => $record->gpt_model,
            'generation_time_ms' => $record->generation_time_ms
        ];

        $strategy_id = $DB->insert_record('alt42g_exam_strategies', $record);
        $db_save_success = true;
    } catch (Exception $e) {
        // 데이터베이스 저장 실패 - 직접 SQL로 재시도
        $db_error_message = $e->getMessage();
        error_log('ALT42 데이터 삽입 오류 (insert_record): ' . $db_error_message);

        // 대체 방법: 직접 SQL 사용 (full table name with mdl_ prefix)
        try {
            $sql = "INSERT INTO mdl_alt42g_exam_strategies
                    (userid, exam_timeline, goal_analysis_data, generated_strategy,
                     strategy_summary, gpt_model, generation_time_ms, timecreated, timemodified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $record->userid,
                $record->exam_timeline,
                $record->goal_analysis_data,
                $record->generated_strategy,
                $record->strategy_summary,
                $record->gpt_model,
                $record->generation_time_ms,
                $record->timecreated,
                $record->timemodified
            ];

            $DB->execute($sql, $params);
            $strategy_id = $DB->get_field_sql("SELECT LAST_INSERT_ID()");
            $db_save_success = true;
            error_log('ALT42: Direct SQL insert successful - ID: ' . $strategy_id);
        } catch (Exception $e2) {
            // 직접 SQL도 실패
            $db_error_message .= ' | Direct SQL: ' . $e2->getMessage();
            error_log('ALT42 데이터 삽입 오류 (direct SQL): ' . $e2->getMessage());
            error_log('ALT42 삽입 데이터: ' . json_encode($debug_info));

            // Moodle의 상세 오류 정보
            if (method_exists($DB, 'get_last_error')) {
                $last_error = $DB->get_last_error();
                error_log('ALT42 DB Last Error: ' . $last_error);
                $db_error_message .= ' | DB: ' . $last_error;
            }

            // 임시 ID 생성 (데이터베이스에 저장되지 않음)
            $strategy_id = 'temp_' . time() . '_' . rand(1000, 9999);
        }
    }

    // 성공 응답 (데이터베이스 저장 여부와 관계없이)
    $response = [
        'success' => true,
        'strategy_id' => $strategy_id,
        'exam_timeline' => $exam_timeline,
        'generated_strategy' => $generated_strategy,
        'strategy_summary' => $strategy_summary,
        'generation_time_ms' => $generation_time_ms,
        'has_goal_analysis' => !empty($goal_analysis_data) && $goal_analysis_data !== "목표분석 데이터가 없습니다. Step 3에서 목표분석을 먼저 실행해주세요.",
        'db_saved' => $db_save_success,
        'message' => $db_save_success
            ? '시험준비 전략이 성공적으로 생성되고 저장되었습니다!'
            : '시험준비 전략이 생성되었습니다. (데이터베이스 저장은 건너뜀)'
    ];

    // 디버그 모드에서만 오류 메시지 포함
    if (defined('DEBUG_MODE') && DEBUG_MODE && !$db_save_success) {
        $response['db_error'] = $db_error_message;
    }

    // JSON만 깨끗하게 출력되도록 버퍼 정리 후 출력
    if (ob_get_length()) { ob_clean(); }
    $GLOBALS['__json_emitted'] = true;
    echo json_encode($response);

} catch (Exception $e) {
    // 에러 응답
    if (ob_get_length()) { ob_clean(); }
    $GLOBALS['__json_emitted'] = true;
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => '전략 생성 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}

// 기본 전략 생성 함수
function generate_fallback_strategy($student_name, $exam_timeline, $timeline_desc) {
    $safeName = htmlspecialchars($student_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeTimeline = htmlspecialchars($exam_timeline, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeDesc = htmlspecialchars($timeline_desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<div class="ex-report">'
        . '<div class="ex-section">'
        .   '<h3 class="ex-title ex-title-overview">개인 맞춤형 시험준비 전략 개요</h3>'
        .   '<p><strong>' . $safeName . '</strong> 학생을 위한 <strong>' . $safeTimeline . '</strong> 상황 전략입니다. ' . $safeDesc . '을 바탕으로 즉시 실행 가능한 학습 계획을 제시합니다.</p>'
        . '</div>'
        . '<div class="ex-section">'
        .   '<h3 class="ex-title ex-title-phases">구간별 핵심 전략</h3>'
        .   '<ul>'
        .     '<li>8~6주: 개념 압축 정리, 주당 3~4회 학습</li>'
        .     '<li>4~2주: 유형·심화 혼합, 기출 패턴 노출</li>'
        .     '<li>1주~D-1: 약점 보완, 실전 타임어택, 컨디션 관리</li>'
        .   '</ul>'
        . '</div>'
        . '<div class="ex-section">'
        .   '<h3 class="ex-title ex-title-practice">실전훈련</h3>'
        .   '<p>타이머 기반 풀이, 스피드 서술 1세트/일, 모의 점검(격일)</p>'
        . '</div>'
        . '<div class="ex-section">'
        .   '<h3 class="ex-title ex-title-practical">체크리스트/루틴</h3>'
        .   '<ul>'
        .     '<li>[ ] 오늘의 핵심 30분 인출</li>'
        .     '<li>[ ] 기출 10문/시간 25~30분</li>'
        .     '<li>[ ] 약점 1개 요약/오답 기록</li>'
        .   '</ul>'
        . '</div>'
        . '</div>';
}

// 전략 요약 생성 함수
function generate_strategy_summary($exam_timeline) {
    $summaries = [
        '🏖️ 방학' => '여유로운 방학 기간 활용 장기 학습 계획',
        '📅 D-2개월' => '2개월 체계적 균형 학습 전략',
        '📆 D-1개월' => '1개월 집중 효율 학습 전략',
        '⏰ D-2주' => '2주 단기집중 핵심 마스터 전략',
        '🚨 D-1주' => '1주 최종 점검 실전 대비 전략',
        '🔥 D-3일' => '3일 최종 마무리 컨디션 관리',
        '💯 D-1일' => '시험 전날 최종 점검 멘탈 관리',
        '📖 시험없음' => '평상시 꾸준한 학습 루틴 구축'
    ];

    return $summaries[$exam_timeline] ?? '개인 맞춤형 학습 전략';
}
?>