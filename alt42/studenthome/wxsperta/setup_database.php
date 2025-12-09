<?php
require_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");

global $DB, $CFG;
require_login();

// 관리자 권한 확인
if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

echo "<h2>WXsperta 데이터베이스 설정</h2>";

// SQL 파일 읽기
$sql_file = __DIR__ . '/create_tables.sql';
if (!file_exists($sql_file)) {
    die("SQL 파일을 찾을 수 없습니다: $sql_file");
}

$sql_content = file_get_contents($sql_file);

// SQL 문을 개별 쿼리로 분리
$queries = array_filter(array_map('trim', explode(';', $sql_content)));

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<pre>";
foreach ($queries as $query) {
    if (empty($query)) continue;
    
    try {
        // Moodle DB API는 CREATE TABLE을 직접 지원하지 않으므로 execute 사용
        $DB->execute($query);
        echo "✓ 성공: " . substr($query, 0, 50) . "...\n";
        $success_count++;
    } catch (Exception $e) {
        $error_msg = "✗ 실패: " . $e->getMessage() . "\n";
        echo $error_msg;
        $errors[] = $error_msg;
        $error_count++;
    }
}
echo "</pre>";

echo "<h3>실행 결과</h3>";
echo "<p>성공: $success_count 개</p>";
echo "<p>실패: $error_count 개</p>";

if (!empty($errors)) {
    echo "<h4>오류 상세:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// 기본 에이전트 데이터 삽입
if ($success_count > 0) {
    echo "<h3>기본 에이전트 데이터 삽입</h3>";
    
    $agents_data = [
        [
            'name' => '시간 수정체',
            'icon' => 'Target',
            'color' => 'from-purple-500 to-pink-500',
            'category' => 'future',
            'layer_id' => 'worldView',
            'description' => '미래 자아 스토리로 감정적 동기부여',
            'short_desc' => '미래 자아 시각화',
            'world_view' => '미래의 나는 현재의 선택으로 만들어진다. 시간은 선형이 아닌 결정의 연속체이다.',
            'context' => '학생의 현재 상황과 미래 목표 사이의 간극을 인식하고 연결점을 찾는다.',
            'structure' => '과거-현재-미래의 타임라인을 시각화하고 각 시점의 자아를 구체화한다.',
            'process' => '1) 미래 목표 설정 2) 현재 상태 분석 3) 갭 분석 4) 연결 경로 도출',
            'execution' => '주기적인 미래 자아 편지 작성, 시각화 보드 제작, 일일 미래 연결점 찾기',
            'reflection' => '목표 달성도를 측정하고 미래 비전의 현실성을 지속적으로 검증한다.',
            'transfer' => '성공 스토리를 문서화하고 다른 학생들과 공유할 수 있는 템플릿으로 변환한다.',
            'abstraction' => '시간을 통한 자아 실현과 성장의 본질을 추출한다.'
        ],
        [
            'name' => '타임라인 합성기',
            'icon' => 'Timer',
            'color' => 'from-blue-500 to-cyan-500',
            'category' => 'future',
            'layer_id' => 'context',
            'description' => '현실적 계획 수립 (간트차트 + 마일스톤)',
            'short_desc' => '계획 현실화',
            'world_view' => '모든 큰 성취는 작은 단계들의 체계적인 연결에서 시작된다.',
            'context' => '복잡한 목표를 달성 가능한 단위로 분해하고 시간축에 배치한다.',
            'structure' => '간트 차트와 마일스톤을 활용한 프로젝트 관리 체계를 구축한다.',
            'process' => '1) 목표 분해 2) 시간 할당 3) 의존성 분석 4) 버퍼 설정 5) 추적 시스템 구축',
            'execution' => '주간/월간 계획 수립, 진행상황 시각화, 자동 리마인더 설정',
            'reflection' => '계획 대비 실행률을 분석하고 병목 구간을 식별하여 개선한다.',
            'transfer' => '효과적인 계획 수립 노하우를 템플릿화하여 공유한다.',
            'abstraction' => '시간 관리의 핵심은 우선순위와 실행의 균형이다.'
        ],
        [
            'name' => '성장 엘리베이터',
            'icon' => 'TrendingUp',
            'color' => 'from-green-500 to-emerald-500',
            'category' => 'future',
            'layer_id' => 'structure',
            'description' => '성과 분석 및 진화 궤도 추적',
            'short_desc' => '성장 분석',
            'world_view' => '성장은 계단이 아닌 엘리베이터처럼 가속할 수 있다.',
            'context' => '현재의 성장 속도와 패턴을 분석하여 가속 포인트를 찾는다.',
            'structure' => '성장 지표를 다차원으로 측정하고 상관관계를 분석한다.',
            'process' => '1) 성장 지표 정의 2) 데이터 수집 3) 패턴 분석 4) 가속 전략 도출',
            'execution' => '일일 성장 로그 작성, 주간 성장 그래프 분석, 월간 전략 조정',
            'reflection' => '성장 궤적을 분석하고 정체 구간의 원인을 파악한다.',
            'transfer' => '성장 패턴과 돌파 전략을 케이스 스터디로 정리한다.',
            'abstraction' => '지속가능한 성장의 핵심은 복리 효과를 만드는 것이다.'
        ]
    ];
    
    try {
        foreach ($agents_data as $agent) {
            $DB->insert_record('wxsperta_agents', (object)$agent);
        }
        echo "<p>✓ 기본 에이전트 3개가 성공적으로 추가되었습니다.</p>";
        
        // 기본 질문 템플릿 추가
        $questions = [
            ['agent_id' => 1, 'question' => '💎 5년 뒤 스스로에게 편지를 써 본 적 있니?', 'question_type' => 'ask'],
            ['agent_id' => 1, 'question' => '그 편지에 반드시 들어가야 할 사건 3가지를 골라 볼까?', 'question_type' => 'ask'],
            ['agent_id' => 1, 'question' => '✨ 미래 자아와 연결 고리가 1개 늘어났어!', 'question_type' => 'success_cue'],
            ['agent_id' => 1, 'question' => '⏳ 아직 미래가 흐릿해. 오늘 하루를 회고해 보자.', 'question_type' => 'fail_cue'],
            
            ['agent_id' => 2, 'question' => '📅 이번 주 가장 중요한 목표 3가지는 뭐야?', 'question_type' => 'ask'],
            ['agent_id' => 2, 'question' => '각 목표를 위해 필요한 시간을 예상해볼까?', 'question_type' => 'ask'],
            ['agent_id' => 2, 'question' => '🎯 계획대로 진행 중! 다음 마일스톤까지 화이팅!', 'question_type' => 'success_cue'],
            ['agent_id' => 2, 'question' => '⚠️ 계획 조정이 필요해 보여. 우선순위를 다시 정해볼까?', 'question_type' => 'fail_cue'],
            
            ['agent_id' => 3, 'question' => '📈 이번 주 가장 크게 성장한 부분은 어디야?', 'question_type' => 'ask'],
            ['agent_id' => 3, 'question' => '성장을 가속하기 위해 더 집중해야 할 것은?', 'question_type' => 'ask'],
            ['agent_id' => 3, 'question' => '🚀 성장 가속도가 붙었어! 이 기세를 유지하자!', 'question_type' => 'success_cue'],
            ['agent_id' => 3, 'question' => '💪 잠시 정체기야. 새로운 돌파구를 찾아보자.', 'question_type' => 'fail_cue']
        ];
        
        foreach ($questions as $q) {
            $DB->insert_record('wxsperta_agent_questions', (object)$q);
        }
        echo "<p>✓ 기본 질문 템플릿이 추가되었습니다.</p>";
        
    } catch (Exception $e) {
        echo "<p>✗ 에이전트 데이터 삽입 실패: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='wxsperta.php'>WXsperta 메인 페이지로 이동</a></p>";

wxsperta_log("Database setup completed. Success: $success_count, Errors: $error_count", 'INFO');
?>