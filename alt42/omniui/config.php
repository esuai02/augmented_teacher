<?php
/**
 * OpenAI API 설정 파일
 * 
 * 보안 경고: 실 운영 환경에서는 반드시 다음 사항을 준수하세요
 * 1. 이 파일을 .gitignore에 추가하여 버전 관리에서 제외
 * 2. API 키를 환경 변수로 관리 (예: $_ENV['OPENAI_API_KEY'])
 * 3. 절대 API 키를 하드코딩하지 마세요
 */ 
  
// OpenAI API 설정
define('OPENAI_API_KEY', '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'); // 실제 API 키로 교체 필요
define('OPENAI_MODEL', 'gpt-4o'); // 또는 'gpt-4', 'gpt-4-turbo-preview' 등
   
// OpenAI API 엔드포인트
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
   
// API 요청 설정
define('OPENAI_MAX_TOKENS', 1000);
define('OPENAI_TEMPERATURE', 0.7);
define('OPENAI_TIMEOUT', 30); // 초 단위

// 데이터베이스 설정 (기존 설정과 통합)
$CFG = new stdClass();
$CFG->dbhost = '58.180.27.46';
$CFG->dbname = 'mathking';
$CFG->dbuser = 'moodle';
$CFG->dbpass = '@MCtrigd7128';
$CFG->prefix = 'mdl_';

// MathKing Database Configuration
define('MATHKING_DB_HOST', '58.180.27.46');
define('MATHKING_DB_NAME', 'mathking');
define('MATHKING_DB_USER', 'moodle');
define('MATHKING_DB_PASS', '@MCtrigd7128');
define('MATHKING_DB_PREFIX', 'mdl_');

// 간편 사용을 위한 별칭 (DB_ 접두사)
define('DB_HOST', '58.180.27.46');
define('DB_NAME', 'mathking');
define('DB_USER', 'moodle');
define('DB_PASS', '@MCtrigd7128');

// Alt42t Database Configuration
// Please update these values with your actual database credentials
define('ALT42T_DB_HOST', 'localhost');
define('ALT42T_DB_NAME', 'alt42t');
define('ALT42T_DB_USER', 'root');
define('ALT42T_DB_PASS', '');
define('ALT42T_DB_PREFIX', '');

// System Configuration
define('SYSTEM_NAME', '수학킹 시험 대비 시스템');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Timezone setting
date_default_timezone_set('Asia/Seoul');

// 시스템 프롬프트 템플릿
define('SYSTEM_PROMPT', '당신은 한국 중학생들의 시험 공부를 도와주는 친절한 AI 튜터입니다. 
학생들이 업로드한 시험 자료(file_url)와 팁(tip_text)을 mdl_alt42t_exam_resources 테이블에서 가져와 분석합니다.

다음 원칙을 따라주세요:
1. 제공된 file_url과 tip_text 데이터를 우선적으로 참고하여 답변
2. 자료와 직접 관련 없는 질문에도 교육적이고 도움이 되는 답변 제공
3. 학생의 눈높이에 맞춰 쉽고 친근하게 설명 (중학생 대상)
4. 시험 준비에 실질적으로 도움이 되는 구체적인 조언 제공
5. 긍정적이고 격려하는 톤 유지
6. 이모지를 적절히 사용하여 친근감 표현
7. 시험 관련 키워드가 있으면 해당 주제에 대해 깊이 있게 답변

주요 답변 주제:
- 시험 전날 준비 방법
- 공식 암기 요령
- 시간 관리 전략
- 실수 줄이는 방법
- 벼락치기 전략
- 시험 긴장 극복
- 오답노트 작성법
- 집중력 향상 방법');

// AI 튜터 설정
define('AI_TUTOR_NAME', 'AI 튜터');
define('AI_TUTOR_GREETING', '안녕하세요! 저는 여러분의 시험 공부를 도와줄 AI 튜터예요. 📚');
define('AI_TUTOR_INTRO', '업로드된 시험 자료와 팁을 분석했어요. 무엇이든 물어보세요!');

// 오류 메시지
define('ERROR_API_KEY_MISSING', 'OpenAI API 키가 설정되지 않았습니다.');
define('ERROR_API_CALL_FAILED', 'AI 응답을 가져오는데 실패했습니다. 잠시 후 다시 시도해주세요.');
define('ERROR_INVALID_REQUEST', '잘못된 요청입니다.');

// 개발 모드 (디버깅용)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Claude API 설정 (환경변수 우선)
if (!defined('CLAUDE_API_KEY')) {
    define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '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');
}
if (!defined('CLAUDE_MODEL')) {
    define('CLAUDE_MODEL', getenv('CLAUDE_MODEL') ?: 'claude-3-5-sonnet-20241022');
}
if (!defined('CLAUDE_API_URL')) {
    define('CLAUDE_API_URL', getenv('CLAUDE_API_URL') ?: 'https://api.anthropic.com/v1/messages');
}

// Optional deploy overrides
if (!defined('DEPLOY_BASE_DIR')) {
    // writable web root dir for deployed artifacts (override in server as needed)
    define('DEPLOY_BASE_DIR', '/home/moodle/public_html/moodle/local/augmented_teacher/alt42');
}
if (!defined('DEPLOY_PUBLIC_BASE')) {
    define('DEPLOY_PUBLIC_BASE', '/moodle/local/augmented_teacher/alt42');
}
?>