<?php
/**
 * 카드 설명 수정 스크립트
 * 기존 카드들의 card_description 필드를 올바르게 설정합니다.
 */

// 에러 리포팅 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 데이터베이스 설정 포함
require_once __DIR__ . '/plugin_db_config.php';

try {
    // 데이터베이스 연결
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "데이터베이스 연결 성공\n";
    echo "===================\n\n";
    
    // 카드 설명 매핑 정의
    $cardDescriptions = [
        '신규학생' => '신규 학생 상담 및 레벨 테스트',
        '정기상담' => '정기적인 학습 상담 일정 관리',
        '상황맞춤' => '학생별 맞춤 상담 진행',
        '분기목표' => '분기별 학습 목표 설정 및 관리',
        '성과측정' => '학습 성과 평가 및 분석',
        '학부모상담' => '학부모와의 정기 상담 관리',
        '주간계획' => '주간 학습 계획 수립 및 관리',
        '주간리뷰' => '주간 학습 성과 리뷰',
        '피드백수집' => '학생 피드백 수집 및 분석',
        '오늘목표' => '일일 학습 목표 설정',
        '일일체크' => '일일 학습 진도 체크',
        '포모도로' => '포모도로 학습법 타이머',
        '수업시작' => '실시간 수업 모니터링 시작',
        '진도체크' => '실시간 학습 진도 확인',
        '집중도분석' => '학습 집중도 실시간 분석',
        '학생대화' => '학생과의 실시간 상호작용',
        '동기부여' => '학습 동기부여 메시지 전송',
        '질문응답' => '실시간 질문 응답 관리',
        '문제분석' => '학습 문제점 분석 및 개선',
        '습관개선' => '학습 습관 개선 프로그램',
        '사고전환' => '사고방식 전환 트레이닝',
        '맞춤교재' => '맞춤형 교재 개발',
        '학습앱' => '학습 앱 개발 및 관리',
        '인터렉티브' => '인터렉티브 콘텐츠 제작'
    ];
    
    // 모든 카드 설정 조회
    $sql = "SELECT id, plugin_id, plugin_config, card_title FROM mdl_alt42DB_card_plugin_settings WHERE plugin_id = 'agent'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cards = $stmt->fetchAll();
    
    echo "총 " . count($cards) . "개의 에이전트 카드를 찾았습니다.\n\n";
    
    $updatedCount = 0;
    
    foreach ($cards as $card) {
        $config = json_decode($card['plugin_config'], true);
        
        // 설정이 없으면 건너뛰기
        if (!$config) {
            echo "ID {$card['id']}: 설정 파싱 실패\n";
            continue;
        }
        
        $pluginName = $config['plugin_name'] ?? '';
        $currentDescription = $config['card_description'] ?? null;
        
        // 카드 이름으로 올바른 설명 찾기
        $correctDescription = null;
        foreach ($cardDescriptions as $cardName => $description) {
            if (strpos($pluginName, $cardName) !== false) {
                $correctDescription = $description;
                break;
            }
        }
        
        // agent_config에서 설명 가져오기 시도
        if (!$correctDescription && isset($config['agent_config']['description'])) {
            $correctDescription = $config['agent_config']['description'];
        }
        
        // 설명이 없거나 잘못된 경우 업데이트
        if ($correctDescription && ($currentDescription !== $correctDescription || 
            $currentDescription === '팝업창에서 멀티턴 작업 실행')) {
            
            echo "ID {$card['id']} ({$pluginName}): ";
            echo "'{$currentDescription}' -> '{$correctDescription}'\n";
            
            // config 업데이트
            $config['card_description'] = $correctDescription;
            
            // 데이터베이스 업데이트
            $updateSql = "UPDATE mdl_alt42DB_card_plugin_settings 
                         SET plugin_config = ?, timemodified = UNIX_TIMESTAMP() 
                         WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([json_encode($config), $card['id']]);
            
            $updatedCount++;
        }
    }
    
    echo "\n===================\n";
    echo "총 {$updatedCount}개의 카드가 업데이트되었습니다.\n";
    
    // 업데이트 결과 확인
    echo "\n업데이트된 카드 확인:\n";
    $checkSql = "SELECT card_title, plugin_config FROM mdl_alt42DB_card_plugin_settings 
                WHERE plugin_id = 'agent' AND plugin_config LIKE '%신규학생%' LIMIT 5";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute();
    $checkResults = $checkStmt->fetchAll();
    
    foreach ($checkResults as $result) {
        $config = json_decode($result['plugin_config'], true);
        echo "- {$result['card_title']}: {$config['plugin_name']} -> {$config['card_description']}\n";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>