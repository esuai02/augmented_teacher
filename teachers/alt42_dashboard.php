<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
 
$totalFeedback = 248;
$ongoingCorrections = 12;
$completedCorrections = 36;
$deviationCauses = [
    '시간 관리 실패' => 45,
    '개념 이해 부족' => 30,
    '문제 해결력 부족' => 25
];
?>
<div class="dashboard">
    <div class="stats">
        <div class="stat-card">
            <p>총 피드백</p>
            <h3><?php echo $totalFeedback; ?></h3>
        </div>
        <div class="stat-card">
            <p>진행중인 습관 교정</p>
            <h3><?php echo $ongoingCorrections; ?></h3>
        </div>
        <div class="stat-card">
            <p>완료된 교정</p>
            <h3><?php echo $completedCorrections; ?></h3>
        </div>
    </div>

    <div class="tracking">
        <h2>진행중인 습관 교정</h2>
        <!-- 진행중인 습관 교정 항목들을 표시합니다. -->
    </div>

    <div class="analysis">
        <h2>주요 이탈 원인</h2>
        <?php foreach ($deviationCauses as $cause => $percentage): ?>
        <div class="cause">
            <span><?php echo $cause; ?></span>
            <span><?php echo $percentage; ?>%</span>
        </div>
        <?php endforeach; ?>
    </div>
</div>