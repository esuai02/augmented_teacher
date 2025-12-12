<?php
/**
 * Onboarding Report Generator
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/report_generator.php
 * Location: Line 1
 */

require_once 'report_service.php';

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
if (!defined('ALT42_ALLOW_GUEST') || !ALT42_ALLOW_GUEST) {
    require_login();
}

// OpenAI 설정 로드 (omniui/config.php)
function load_openai_config() {
    static $loaded = false;
    if ($loaded) return;
    $candidates = [
        $_SERVER['DOCUMENT_ROOT'] . '/moodle/local/augmented_teacher/alt42/omniui/config.php',
        dirname(__DIR__, 3) . '/omniui/config.php',
        dirname(__DIR__, 4) . '/omniui/config.php'
    ];
    foreach ($candidates as $path) {
        if (is_string($path) && file_exists($path) && is_readable($path)) {
            include_once($path);
            $loaded = true;
            break;
        }
    }
}

load_openai_config();

// OpenAI Chat Completions 호출
function call_openai_chat(array $messages) {
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    $apiUrl = defined('OPENAI_API_URL') ? OPENAI_API_URL : 'https://api.openai.com/v1/chat/completions';
    $model  = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o';
    $temp   = defined('OPENAI_TEMPERATURE') ? OPENAI_TEMPERATURE : 0.7;
    // max_tokens: 상수 또는 GLOBALS 오버라이드 사용
    if (isset($GLOBALS['OPENAI_MAX_TOKENS_OVERRIDE'])) {
        $maxTok = $GLOBALS['OPENAI_MAX_TOKENS_OVERRIDE'];
    } else {
        $maxTok = defined('OPENAI_MAX_TOKENS') ? OPENAI_MAX_TOKENS : 1000;
    }
    $timeout= defined('OPENAI_TIMEOUT') ? OPENAI_TIMEOUT : 30;

    if (empty($apiKey)) {
        return ['success' => false, 'content' => '', 'error' => 'OPENAI_API_KEY not configured'];
    }

    $payload = [
        'model' => $model,
        'temperature' => $temp,
        'max_tokens' => $maxTok,
        'messages' => $messages,
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $err   = curl_error($ch);
    $status= curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return ['success' => false, 'content' => '', 'error' => 'cURL error: ' . $err];
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        return ['success' => false, 'content' => '', 'error' => 'Invalid JSON from OpenAI'];
    }

    $content = $json['choices'][0]['message']['content'] ?? '';
    if (empty($content)) {
        $errMsg = $json['error']['message'] ?? ('HTTP ' . $status);
        return ['success' => false, 'content' => '', 'error' => $errMsg];
    }
    return ['success' => true, 'content' => $content, 'error' => null];
}

function safe_truncate($text, $maxLen = 3000) {
    if (!is_string($text)) return '';
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $maxLen, 'UTF-8');
    }
    return substr($text, 0, $maxLen);
}

/**
 * Load agent knowledge files (Markdown and JSON-LD) for inclusion in the report
 * @return array<string,string> keyed by logical names
 */
function loadAgentKnowledgeFiles() {
    $baseDir = __DIR__;
    $mapping = [
        '평가목록.md' => 'evaluationList',
        '의사결정_실행.md' => 'decisionExecution',
        '의사결정_지식.md' => 'decisionKnowledge',
        '용어집.md' => 'glossary',
        '온톨로지.jsonld' => 'ontologyJsonld',
    ];

    $result = [];
    foreach ($mapping as $filename => $key) {
        $path = $baseDir . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($path) && is_readable($path)) {
            $content = @file_get_contents($path);
            if ($content !== false) {
                $result[$key] = $content;
            }
        }
    }
    return $result;
}

/**
 * Generate HTML report from onboarding data
 * @param array $data Combined onboarding data
 * @return string HTML report content
 */
function generateReportHTML($data) {
    if (!$data['success']) {
        return '<div class="error">데이터 로딩 실패: ' . htmlspecialchars($data['error'] ?? 'Unknown error') . '</div>';
    }

    $info = $data['info'];
    $assessment = $data['assessment'];

    $html = '<div class="onboarding-report">';

    // Header
    $html .= '<div class="report-header">';
    $html .= '<h2>온보딩 리포트</h2>';
    $html .= '<p class="generated-time">생성 시각: ' . date('Y-m-d H:i:s', $data['timestamp']) . '</p>';
    $html .= '</div>';

    // Basic Info Section (show only existing fields)
    $basicRows = [];
    if (!empty($info['studentName'])) $basicRows[] = '<tr><td><strong>이름:</strong></td><td>' . htmlspecialchars($info['studentName']) . '</td></tr>';
    if (!empty($info['email'])) $basicRows[] = '<tr><td><strong>이메일:</strong></td><td>' . htmlspecialchars($info['email']) . '</td></tr>';
    if (!empty($info['phone'])) $basicRows[] = '<tr><td><strong>전화:</strong></td><td>' . htmlspecialchars($info['phone']) . '</td></tr>';
    if (!empty($info['city'])) $basicRows[] = '<tr><td><strong>지역:</strong></td><td>' . htmlspecialchars($info['city']) . '</td></tr>';
    if (!empty($basicRows)) {
        $html .= '<div class="report-section">';
        $html .= '<h3 class="ob-title ob-title-basic">기본 정보 요약</h3>';
        $html .= '<table class="info-table">' . implode('', $basicRows) . '</table>';
        $html .= '</div>';
    }

    // Student Profile Section
    if (!empty($info['learning_style']) || !empty($info['mbti_type'])) {
        $html .= '<div class="report-section">';
        $html .= '<h3>학습 프로필</h3>';
        $html .= '<table class="info-table">';

        if (!empty($info['learning_style'])) {
            $html .= '<tr><td><strong>학습 스타일:</strong></td><td>' . htmlspecialchars($info['learning_style']) . '</td></tr>';
        }

        if (!empty($info['mbti_type'])) {
            $mbtiDisplay = htmlspecialchars($info['mbti_type']);
            if (!empty($info['mbti_timecreated'])) {
                $mbtiDisplay .= ' <small style="color: #6b7280;">(' . date('Y-m-d H:i', $info['mbti_timecreated']) . ')</small>';
            }
            $html .= '<tr><td><strong>MBTI:</strong></td><td>' . $mbtiDisplay . '</td></tr>';
        }

        if (!empty($info['preferred_motivator'])) {
            $html .= '<tr><td><strong>동기 유형:</strong></td><td>' . htmlspecialchars($info['preferred_motivator']) . '</td></tr>';
        }

        if (!empty($info['daily_active_time'])) {
            $html .= '<tr><td><strong>활동 시간대:</strong></td><td>' . htmlspecialchars($info['daily_active_time']) . '</td></tr>';
        }

        if (isset($info['streak_days']) && $info['streak_days'] > 0) {
            $html .= '<tr><td><strong>연속 학습:</strong></td><td>' . htmlspecialchars($info['streak_days']) . '일</td></tr>';
        }

        if (isset($info['total_interactions']) && $info['total_interactions'] > 0) {
            $html .= '<tr><td><strong>총 상호작용:</strong></td><td>' . htmlspecialchars($info['total_interactions']) . '회</td></tr>';
        }

        $html .= '</table>';
        $html .= '</div>';
    }

    // Onboarding Quick Summary (from omniui inputs)
    $onboardingKeys = [
        'math_level','concept_level','concept_progress','advanced_level','advanced_progress',
        'problem_preference','exam_style','math_confidence',
        'parent_style','stress_level','feedback_preference',
        'short_term_goal','mid_term_goal','long_term_goal','weekly_hours','academy_experience',
        'favorite_food','favorite_fruit','favorite_snack','hobbies_interests'
    ];
    $hasOnboarding = false;
    foreach ($onboardingKeys as $k) {
        if (isset($info[$k]) && $info[$k] !== '' && $info[$k] !== null) { $hasOnboarding = true; break; }
    }
    if ($hasOnboarding) {
        $html .= '<div class="report-section">';
        $html .= '<h3>온보딩 핵심 요약</h3>';
        $html .= '<table class="info-table">';
        if (!empty($info['math_level'])) {
            $html .= '<tr><td><strong>수학 수준:</strong></td><td>' . htmlspecialchars($info['math_level']) . '</td></tr>';
        }
        if (!empty($info['concept_level'])) {
            $progress = isset($info['concept_progress']) ? ' (' . htmlspecialchars((string)$info['concept_progress']) . ')' : '';
            $html .= '<tr><td><strong>개념 진도:</strong></td><td>' . htmlspecialchars($info['concept_level']) . $progress . '</td></tr>';
        }
        if (!empty($info['advanced_level'])) {
            $progress = isset($info['advanced_progress']) ? ' (' . htmlspecialchars((string)$info['advanced_progress']) . ')' : '';
            $html .= '<tr><td><strong>심화 진도:</strong></td><td>' . htmlspecialchars($info['advanced_level']) . $progress . '</td></tr>';
        }
        if (!empty($info['problem_preference'])) {
            $html .= '<tr><td><strong>문제풀이 선호:</strong></td><td>' . htmlspecialchars($info['problem_preference']) . '</td></tr>';
        }
        if (!empty($info['exam_style'])) {
            $html .= '<tr><td><strong>시험 대비 성향:</strong></td><td>' . htmlspecialchars($info['exam_style']) . '</td></tr>';
        }
        if (isset($info['math_confidence'])) {
            $html .= '<tr><td><strong>수학 자신감:</strong></td><td>' . htmlspecialchars((string)$info['math_confidence']) . '/10</td></tr>';
        }
        if (!empty($info['parent_style'])) {
            $html .= '<tr><td><strong>부모 지도 스타일:</strong></td><td>' . htmlspecialchars($info['parent_style']) . '</td></tr>';
        }
        if (!empty($info['stress_level'])) {
            $html .= '<tr><td><strong>학습 스트레스:</strong></td><td>' . htmlspecialchars($info['stress_level']) . '</td></tr>';
        }
        if (!empty($info['feedback_preference'])) {
            $html .= '<tr><td><strong>피드백 선호:</strong></td><td>' . htmlspecialchars($info['feedback_preference']) . '</td></tr>';
        }
        if (!empty($info['short_term_goal']) || !empty($info['mid_term_goal']) || !empty($info['long_term_goal'])) {
            $goals = [];
            if (!empty($info['short_term_goal'])) $goals[] = '단기: ' . htmlspecialchars($info['short_term_goal']);
            if (!empty($info['mid_term_goal'])) $goals[] = '중기: ' . htmlspecialchars($info['mid_term_goal']);
            if (!empty($info['long_term_goal'])) $goals[] = '장기: ' . htmlspecialchars($info['long_term_goal']);
            $html .= '<tr><td><strong>목표:</strong></td><td>' . implode(' · ', $goals) . '</td></tr>';
        }
        if (isset($info['weekly_hours'])) {
            $html .= '<tr><td><strong>주당 학습시간:</strong></td><td>' . htmlspecialchars((string)$info['weekly_hours']) . '시간</td></tr>';
        }
        if (!empty($info['academy_experience'])) {
            $html .= '<tr><td><strong>학원/과외 경험:</strong></td><td>' . htmlspecialchars($info['academy_experience']) . '</td></tr>';
        }
        // 간단 선호·취미
        $fav = [];
        if (!empty($info['favorite_food'])) $fav[] = '음식: ' . htmlspecialchars($info['favorite_food']);
        if (!empty($info['favorite_fruit'])) $fav[] = '과일: ' . htmlspecialchars($info['favorite_fruit']);
        if (!empty($info['favorite_snack'])) $fav[] = '과자: ' . htmlspecialchars($info['favorite_snack']);
        if (!empty($info['hobbies_interests'])) $fav[] = '취미: ' . htmlspecialchars($info['hobbies_interests']);
        if (!empty($fav)) {
            $html .= '<tr><td><strong>선호/취미:</strong></td><td>' . implode(' · ', $fav) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
    }

    // Assessment Section (omit if not available)
    if (!empty($assessment) && isset($assessment['id'])) {
        $html .= '<div class="report-section">';
        $html .= '<h3 class="ob-title ob-title-assessment">평가 요약</h3>';

        $html .= '<div class="score-grid">';
        $html .= '<div class="score-card">';
        $html .= '<h4>인지적 요소</h4>';
        $html .= '<div class="score">' . round($assessment['cognitive_score'] ?? 0, 1) . '<span>/5.0</span></div>';
        $html .= '</div>';

        $html .= '<div class="score-card">';
        $html .= '<h4>감정적 요소</h4>';
        $html .= '<div class="score">' . round($assessment['emotional_score'] ?? 0, 1) . '<span>/5.0</span></div>';
        $html .= '</div>';

        $html .= '<div class="score-card">';
        $html .= '<h4>행동적 요소</h4>';
        $html .= '<div class="score">' . round($assessment['behavioral_score'] ?? 0, 1) . '<span>/5.0</span></div>';
        $html .= '</div>';

        $html .= '<div class="score-card total">';
        $html .= '<h4>종합 점수</h4>';
        $html .= '<div class="score">' . round($assessment['overall_total'] ?? 0, 1) . '<span>/5.0</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';
    } else {
        $html .= '<div class="report-section">';
        $html .= '<p class="no-data">학습 스타일 평가 데이터가 없습니다.</p>';
        $html .= '</div>';
    }

    // Agent Knowledge Files Section (Markdown/JSON-LD)
    $knowledge = loadAgentKnowledgeFiles();
    if (!empty($knowledge)) {
        $html .= '<div class="report-section">';
        $html .= '<h3>에이전트 지식 및 기준</h3>';

        // Helper to render a collapsible block
        $renderBlock = function($title, $raw, $isJson = false) {
            $safe = '';
            if ($isJson) {
                $decoded = json_decode($raw, true);
                if ($decoded !== null) {
                    $pretty = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    $safe = htmlspecialchars($pretty, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                } else {
                    $safe = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }
            } else {
                $safe = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            return 
                '<details style="margin-bottom:10px;">'
              .   '<summary style="cursor:pointer;font-weight:600;color:#111827;margin-bottom:6px;">' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</summary>'
              .   '<pre style="white-space:pre-wrap;word-break:break-word;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-top:8px;">' . $safe . '</pre>'
              . '</details>';
        };

        if (!empty($knowledge['evaluationList'])) {
            $html .= $renderBlock('평가목록.md', $knowledge['evaluationList']);
        }
        if (!empty($knowledge['decisionKnowledge'])) {
            $html .= $renderBlock('의사결정_지식.md', $knowledge['decisionKnowledge']);
        }
        if (!empty($knowledge['decisionExecution'])) {
            $html .= $renderBlock('의사결정_실행.md', $knowledge['decisionExecution']);
        }
        if (!empty($knowledge['glossary'])) {
            $html .= $renderBlock('용어집.md', $knowledge['glossary']);
        }
        if (!empty($knowledge['ontologyJsonld'])) {
            $html .= $renderBlock('온톨로지.jsonld', $knowledge['ontologyJsonld'], true);
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

// GPT를 사용하여 온보딩 리포트 생성
function generateReportWithGPT($userid) {
    $data = getOnboardingData($userid);
    if (!$data['success']) {
        return ['success' => false, 'reportHTML' => '', 'error' => $data['error'] ?? 'getOnboardingData failed'];
    }
    $info = $data['info'] ?? [];
    $assessment = $data['assessment'] ?? [];
    $knowledge = loadAgentKnowledgeFiles();

    $system = [
        'role' => 'system',
        'content' => '너는 학습코칭 온보딩 분석 어시스턴트다. 온보딩/평가/지식 데이터를 바탕으로 한국어 리포트를 작성한다. 모든 분석과 제안은 주어진 데이터에 근거해 구체적으로 연결하고, 누락된 값은 언급하지 말고 자연스럽게 생략한다. 개인정보는 노출하지 말고, 실행지향적인 권고를 제시하라.'
    ];

    $userPayload = [
        'student' => [
            'name' => $info['studentName'] ?? '',
            'email' => $info['email'] ?? '',
            'phone' => $info['phone'] ?? '',
            'city' => $info['city'] ?? '',
            'mbti' => $info['mbti_type'] ?? '',
            'learning_style' => $info['learning_style'] ?? '',
            'preferred_motivator' => $info['preferred_motivator'] ?? '',
            'daily_active_time' => $info['daily_active_time'] ?? '',
            'streak_days' => $info['streak_days'] ?? 0,
            'total_interactions' => $info['total_interactions'] ?? 0,
        ],
        // 온보딩 UI에서 입력/저장된 상세값 포함
        'onboarding' => [
            'math_level' => $info['math_level'] ?? '',
            'concept_level' => $info['concept_level'] ?? '',
            'concept_progress' => $info['concept_progress'] ?? 0,
            'advanced_level' => $info['advanced_level'] ?? '',
            'advanced_progress' => $info['advanced_progress'] ?? 0,
            'notes' => $info['notes'] ?? '',
            'problem_preference' => $info['problem_preference'] ?? '',
            'exam_style' => $info['exam_style'] ?? '',
            'math_confidence' => $info['math_confidence'] ?? null,
            'parent_style' => $info['parent_style'] ?? '',
            'stress_level' => $info['stress_level'] ?? '',
            'feedback_preference' => $info['feedback_preference'] ?? '',
            'short_term_goal' => $info['short_term_goal'] ?? '',
            'mid_term_goal' => $info['mid_term_goal'] ?? '',
            'long_term_goal' => $info['long_term_goal'] ?? '',
            'goal_note' => $info['goal_note'] ?? '',
            'weekly_hours' => $info['weekly_hours'] ?? null,
            'academy_experience' => $info['academy_experience'] ?? '',
            'favorite_food' => $info['favorite_food'] ?? '',
            'favorite_fruit' => $info['favorite_fruit'] ?? '',
            'favorite_snack' => $info['favorite_snack'] ?? '',
            'hobbies_interests' => $info['hobbies_interests'] ?? '',
            'fandom_yn' => $info['fandom_yn'] ?? null,
            'data_consent' => $info['data_consent'] ?? null,
        ],
        'assessment' => $assessment,
        'knowledge_snippets' => [
            'evaluationList' => safe_truncate($knowledge['evaluationList'] ?? '', 1500),
            'decisionKnowledge' => safe_truncate($knowledge['decisionKnowledge'] ?? '', 1500),
            'decisionExecution' => safe_truncate($knowledge['decisionExecution'] ?? '', 1500),
            'glossary' => safe_truncate($knowledge['glossary'] ?? '', 1200),
            'ontologyJsonld' => safe_truncate($knowledge['ontologyJsonld'] ?? '', 1200),
        ],
        'instructions' => '아래 구조의 한국어 HTML을 산출하라. 각 항목은 제공된 데이터(onboarding, assessment, student)에 근거하여 작성하고, 누락된 값은 언급하지 말고 해당 항목을 생략한다. 표와 리스트를 적절히 활용하고, 과도한 장식/외부 CDN은 금지. 필수 인라인 스타일만 사용하고, 가독성 좋은 타이포그래피와 여백을 적용한다.
1) 제목/생성시각
2) 기본 정보 요약
3) 학습 프로필 요약(MBTI/학습스타일 포함)
4) 평가 요약(핵심 점수와 의미)
5) 핵심 인사이트 3~5개(학생에게 긍정적/실행지향 문장)
6) 다음 행동 제안(교사/학생 각각 3개)
7) 포괄형 질문 1: "이 학생의 현재 수학 학습 맥락을 종합해서, 첫 수업에서 무엇을 어떻게 시작해야 할지 알려줘." 
   - 온보딩 정보(수학 수준/개념·심화 진도/문제풀이·시험 성향/자신감 등)를 근거로, 수업 도입 루틴, 설명 전략, 자료 유형, 첫 시간 세부 운영안을 제시
8) 포괄형 질문 2: "이 학생의 성향과 목표를 기반으로, 커리큘럼과 루틴을 어떤 방향으로 최적화해야 할까?"
   - 단기·중기·장기 목표, 성향(문제풀이/시험/스트레스/부모 개입/자신감 등)을 근거로, 단원·난이도·문항비중·주차 루틴의 설계와 우선순위 제시
9) 포괄형 질문 3: "이 학생이 중장기적으로 성장하기 위해 지금부터 어떤 부분을 특히 신경 써야 할까?"
   - 경시/진학/수학 자존감/피로 누적/루틴 유지 위험 요인을 근거로, 조기 리스크 예측과 트래킹할 우선지표(예: 학습시간, 정답률, 난이도 적합도, 감정메모 등)와 점검 주기 제시
10) 실용 요소를 반드시 포함: 첫 수업 30분 진행 안(분 단위), 1주/2주 루틴 샘플(시간·콘텐츠 수치 포함), 부모 커뮤니케이션 문장 2줄, 마이크로 습관 3개, 리스크 신호와 대응(체크리스트)
11) 문단 간 연결 문장을 추가해 전체 흐름이 자연스럽게 이어지도록 작성한다.
12) 섹션 제목에 다음 클래스를 부여하라(색상 스타일 적용 위해): 
   - 기본 정보 요약: <h3 class=\"ob-title ob-title-basic\">
   - 평가 요약: <h3 class=\"ob-title ob-title-assessment\">
   - 핵심 인사이트: <h3 class=\"ob-title ob-title-insights\">
   - 다음 행동 제안: <h3 class=\"ob-title ob-title-actions\">
   - 포괄형 질문: <h3 class=\"ob-title ob-title-questions\">
   - 실용 요소: <h3 class=\"ob-title ob-title-practical\">'
    ];

    $messages = [
        $system,
        [ 'role' => 'user', 'content' => json_encode($userPayload, JSON_UNESCAPED_UNICODE) ]
    ];

    $ai = call_openai_chat($messages);
    if (!$ai['success']) {
        return ['success' => false, 'reportHTML' => '', 'error' => $ai['error']];
    }

    $content = $ai['content'];
    $html = '<div class="onboarding-report" style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;line-height:1.75;color:#111827;">'
          . '<style>'
          . '.onboarding-report{background:#f7fafc;}'
          . '.ob-container{max-width:980px;margin:0 auto;padding:12px 8px;}'
          . '.report-header{background:#eef2ff;border:1px solid #e0e7ff;border-radius:10px;padding:12px 16px;margin:0 0 12px 0;}'
          . '.ob-h1{font-size:22px;font-weight:800;margin:0 0 6px 0;color:#0f172a;}'
          . '.ob-meta{margin:0;color:#475569;font-size:12px;}'
          . '.ob-section{background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin:12px 0;box-shadow:0 1px 2px rgba(0,0,0,0.04);}'
          . '.ob-section h3{font-size:16px;margin:0 0 10px 0;color:#111827;}'
          . '.ob-table{width:100%;border-collapse:collapse;}'
          . '.ob-table td{border:1px solid #e5e7eb;padding:8px;vertical-align:top;font-size:13px;}'
          . '.ob-callout{background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #6366f1;border-radius:8px;padding:12px;margin:12px 0;}'
          . '.ob-list{margin:0;padding-left:18px;}'
          . '.ob-list li{margin:4px 0;font-size:14px;}'
          . '.ob-kpi{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;}'
          . '.ob-kpi .item{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px;}'
          . '.report-body{font-size:14px;color:#1f2937;}'
          . '.report-body p{margin:0 0 12px 0;}'
          . '.report-body h3{font-size:16px;margin:18px 0 10px 0;color:#111827;}'
          . '.report-body h4{font-size:15px;margin:16px 0 8px 0;color:#111827;}'
          . '.report-body h5{font-size:14px;margin:14px 0 6px 0;color:#111827;}'
          . '.report-body ul{margin:0 0 12px 20px;padding:0;}'
          . '.report-body ol{margin:0 0 12px 20px;padding:0;}'
          . '.report-body li{margin:6px 0;}'
          . '.report-body table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin:10px 0;}'
          . '.report-body th,.report-body td{border-top:1px solid #e5e7eb;padding:10px;vertical-align:top;font-size:13px;}'
          . '.report-body th{background:#f3f4f6;color:#0f172a;text-align:left;}'
          . '.report-body pre{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;white-space:pre-wrap;word-break:break-word;margin:10px 0;}'
          . '.report-body code{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:4px;padding:0 4px;}'
          . '.report-body blockquote{margin:10px 0;padding:10px 12px;border-left:4px solid #6366f1;background:#f8fafc;border-radius:6px;}'
          . '.report-body hr{border:none;border-top:1px solid #e5e7eb;margin:16px 0;}'
          . '.report-body a{color:#2563eb;text-decoration:none;}'
          . '.report-body a:hover{text-decoration:underline;}'
          . '.ob-title{font-weight:800;}'
          . '.ob-title-basic{color:#0284c7;}'
          . '.ob-title-assessment{color:#059669;}'
          . '.ob-title-insights{color:#b45309;}'
          . '.ob-title-actions{color:#4f46e5;}'
          . '.ob-title-questions{color:#dc2626;}'
          . '.ob-title-practical{color:#7c3aed;}'
          . '@media (max-width:640px){.ob-kpi{grid-template-columns:1fr;}.ob-section{padding:12px}.ob-h1{font-size:18px}}'
          . '</style>'
          . '<div class="ob-container">'
          .   '<div class="report-header">'
          .     '<h2 class="ob-h1">온보딩 리포트 (GPT)</h2>'
          .     '<p class="ob-meta">생성 시각: ' . date('Y-m-d H:i:s') . '</p>'
          .   '</div>'
          .   '<div class="report-body">' . $content . '</div>'
          . '</div>'
          . '</div>';

    return ['success' => true, 'reportHTML' => $html, 'error' => null, 'source' => $data];
}

/**
 * Save generated report to database
 * @param int $userid User ID
 * @param array $data Source data
 * @param string $reportHTML Generated HTML
 * @param string $reportType Type: 'initial' or 'regenerated'
 * @return array Result with report ID
 */
function saveReport($userid, $data, $reportHTML, $reportType = 'initial') {
    global $DB;

    try {
        $record = new stdClass();
        $record->userid = $userid;
        $record->report_type = $reportType;
        $record->info_data = json_encode($data['info']);
        $record->assessment_id = $data['assessment']['id'] ?? null;
        $record->report_content = $reportHTML;
        $record->generated_at = time();
        $record->generated_by = 'agent01_onboarding';
        $record->status = 'published';
        $record->metadata = json_encode([
            'cognitive_score' => $data['assessment']['cognitive_score'] ?? 0,
            'emotional_score' => $data['assessment']['emotional_score'] ?? 0,
            'behavioral_score' => $data['assessment']['behavioral_score'] ?? 0,
            'overall_total' => $data['assessment']['overall_total'] ?? 0
        ]);

        $reportId = $DB->insert_record('alt42o_onboarding_reports', $record);

        return [
            'success' => true,
            'reportId' => $reportId,
            'message' => 'Report saved successfully'
        ];

    } catch (Exception $e) {
        error_log("saveReport error: " . $e->getMessage() .
                  " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ];
    }
}

// Handle AJAX requests (skip when guard is set for include usage)
if (!defined('ALT42_DISABLE_DIRECT_ACTION') && (($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET'))) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? $_GET['action'] ?? null;
    if (!$action) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing action',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
        exit;
    }

    $userid = isset($_POST['userid']) ? intval($_POST['userid']) : (isset($_GET['userid']) ? intval($_GET['userid']) : 0);

    if ($userid <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid user ID',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
        exit;
    }

    switch ($action) {
        case 'generateReport':
            // Get data
            $data = getOnboardingData($userid);

            if (!$data['success']) {
                echo json_encode($data);
                exit;
            }

            // Generate HTML
            $reportHTML = generateReportHTML($data);

            // Check if regenerating
            $existing = getExistingReport($userid);
            $reportType = ($existing['exists']) ? 'regenerated' : 'initial';

            // Archive old report if exists
            if ($existing['exists'] && isset($existing['report']->id)) {
                $DB->set_field('alt42o_onboarding_reports', 'status', 'archived',
                              ['id' => $existing['report']->id]);
            }

            // Save new report
            $result = saveReport($userid, $data, $reportHTML, $reportType);

            if ($result['success']) {
                $result['reportHTML'] = $reportHTML;
                $result['reportType'] = $reportType;
            } else {
                // 저장 실패 시에도 미리보기 용으로 리포트는 반환
                $result['reportHTML'] = $reportHTML;
                $result['reportType'] = 'preview';
            }

            echo json_encode($result);
            break;

        case 'generateReportGPT':
            $gpt = generateReportWithGPT($userid);
            if (!$gpt['success']) {
                echo json_encode([
                    'success' => false,
                    'error' => $gpt['error'] ?? 'GPT generation failed',
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                break;
            }

            $srcData = $gpt['source'] ?? getOnboardingData($userid);
            $reportType = 'gpt';
            $save = saveReport($userid, is_array($srcData) ? $srcData : ['info'=>[], 'assessment'=>[]], $gpt['reportHTML'], $reportType);
            if ($save['success']) {
                $save['reportHTML'] = $gpt['reportHTML'];
                $save['reportType'] = $reportType;
                echo json_encode($save);
            } else {
                echo json_encode([
                    'success' => true,
                    'reportHTML' => $gpt['reportHTML'],
                    'reportType' => 'gpt-preview'
                ]);
            }
            break;

        default:
            // 폴백: 알 수 없는 action도 GPT 생성 시도
            $gpt = generateReportWithGPT($userid);
            if ($gpt['success']) {
                echo json_encode([
                    'success' => true,
                    'reportHTML' => $gpt['reportHTML'],
                    'reportType' => 'gpt-fallback'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Unknown action: ' . (is_string($action) ? $action : 'null') . ' / ' . ($gpt['error'] ?? 'gpt failed'),
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
            }
    }
    exit;
}
