<?php
/**
 * 가설 저장 테스트
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;

require_once(__DIR__ . '/src/database/models/ExperimentModel.php');

echo "<h1>가설 저장 테스트</h1>";

try {
    // 실험 모델 생성
    $experimentModel = new ExperimentModel();
    
    // 먼저 테스트 실험 생성
    $experimentData = [
        'experiment_name' => '테스트 실험 - ' . date('Y-m-d H:i:s'),
        'description' => '가설 테스트를 위한 실험',
        'start_date' => time(),
        'duration_weeks' => 8,
        'status' => 'planned',
        'created_by' => 1
    ];
    
    echo "<h2>1. 실험 생성</h2>";
    $experimentResult = $experimentModel->saveExperiment($experimentData);
    echo "<pre>";
    print_r($experimentResult);
    echo "</pre>";
    
    if ($experimentResult['success']) {
        $experimentId = $experimentResult['experiment_id'];
        
        // 가설 저장 테스트
        echo "<h2>2. 가설 저장</h2>";
        $hypothesisResult = $experimentModel->saveHypothesis(
            $experimentId,
            '메타인지 피드백을 받은 학생들이 통제군보다 더 나은 학습 성과를 보일 것이다.',
            'primary',
            1
        );
        echo "<pre>";
        print_r($hypothesisResult);
        echo "</pre>";
        
        if ($hypothesisResult['success']) {
            echo "<h2>3. 저장된 가설 확인</h2>";
            
            // 데이터베이스에서 직접 확인
            $sql = "SELECT * FROM mdl_alt42_hypotheses WHERE experiment_id = ? ORDER BY timecreated DESC";
            $stmt = $DB->prepare($sql);
            $stmt->execute([$experimentId]);
            $hypotheses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<pre>";
            print_r($hypotheses);
            echo "</pre>";
            
            echo "<h2>4. 실험 로그 확인</h2>";
            $logSql = "SELECT * FROM mdl_alt42_experiment_logs WHERE experiment_id = ? ORDER BY timecreated DESC";
            $logStmt = $DB->prepare($logSql);
            $logStmt->execute([$experimentId]);
            $logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<pre>";
            print_r($logs);
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>오류 발생</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>";
    print_r($e->getTraceAsString());
    echo "</pre>";
}
?>