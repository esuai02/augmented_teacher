<?php
// 필요한 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 단원 데이터
$unitData = [
    [
        'id' => 1,
        'title' => '함수와 그래프',
        'topics' => [
            [
                'id' => 11,
                'title' => '이차함수의 성질',
                'contents' => [
                    [
                        'id' => 111,
                        'title' => '개념 예제 노트',
                        'type' => 'note',
                        'status' => 'warning',
                        'stats' => [
                            'timeSpent' => '5분',
                            'completion' => '30%',
                            'attempts' => 2
                        ]
                    ],
                    [
                        'id' => 112,
                        'title' => '개념 예제 퀴즈',
                        'type' => 'quiz',
                        'status' => 'danger',
                        'stats' => [
                            'correctRate' => '40%',
                            'avgTime' => '8분',
                            'attempts' => 3
                        ]
                    ]
                ]
            ]
        ]
    ]
];

// 추천 피드백 데이터
$feedbackTemplates = [
    [
        'id' => 1,
        'type' => 'concept_understanding',
        'title' => '개념 이해 보완',
        'message' => '이차함수의 기본 성질에 대한 이해가 부족해 보입니다. 개념노트를 다시 한번 검토해보는 것이 좋겠습니다.',
        'suggestedActions' => ['개념노트 복습', '기초 예제 풀이'],
        'correlation' => 0.85,
        'usageCount' => 24,
        'successRate' => '75%'
    ],
    [
        'id' => 2,
        'type' => 'time_management',
        'title' => '학습 시간 조정',
        'message' => '문제 풀이에 충분한 시간을 할애하지 않고 있습니다. 시간을 더 투자하여 차근차근 풀어보세요.',
        'suggestedActions' => ['타이머 설정', '단계별 풀이'],
        'correlation' => 0.72,
        'usageCount' => 18,
        'successRate' => '68%'
    ]
];

// 피드백 이력 데이터
$feedbackHistory = [
    [
        'id' => 1,
        'date' => '2024-11-20',
        'type' => 'concept_understanding',
        'status' => 'in_progress',
        'improvement' => 3,
        'studentResponse' => '네, 개념노트를 다시 복습하고 있습니다.',
        'completionRate' => 60
    ]
];

// GET 파라미터로부터 상태 관리
$selectedUnit = isset($_GET['unit_id']) ? $_GET['unit_id'] : null;
$selectedTopic = isset($_GET['topic_id']) ? $_GET['topic_id'] : null;
$selectedContent = isset($_GET['content_id']) ? $_GET['content_id'] : null;
$selectedFeedback = isset($_GET['feedback_id']) ? $_GET['feedback_id'] : null;
$currentStep = isset($_GET['step']) ? $_GET['step'] : 'units';

// 상태에 따라 다음 단계 설정
if ($selectedUnit && !$selectedTopic) {
    $currentStep = 'topics';
} elseif ($selectedUnit && $selectedTopic && !$selectedContent) {
    $currentStep = 'contents';
} elseif ($selectedUnit && $selectedTopic && $selectedContent) {
    $currentStep = 'feedback';
}

// 상태 색상 함수
function getStatusColor($status)
{
    switch ($status) {
        case 'warning':
            return 'background-color: #FEF08A; color: #854D0E;'; // 노란색 배경
        case 'danger':
            return 'background-color: #FECACA; color: #991B1B;'; // 빨간색 배경
        default:
            return 'background-color: #E5E7EB; color: #374151;'; // 회색 배경
    }
}

// HTML 출력 시작
?>
<div class="teacher-page">
    <!-- 뒤로가기 버튼 -->
    <button onclick="window.history.back();" style="margin-bottom: 16px; color: #2563EB;">
        뒤로가기
    </button>

    <!-- 단계 표시 -->
    <div style="display: flex; align-items: center; margin-bottom: 24px; color: #6B7280;">
        <span style="<?php echo $currentStep === 'units' ? 'color: #2563EB; font-weight: bold;' : ''; ?>">단원 선택</span>
        <span style="margin: 0 8px;">></span>
        <span style="<?php echo $currentStep === 'topics' ? 'color: #2563EB; font-weight: bold;' : ''; ?>">주제 선택</span>
        <span style="margin: 0 8px;">></span>
        <span style="<?php echo $currentStep === 'contents' ? 'color: #2563EB; font-weight: bold;' : ''; ?>">컨텐츠 선택</span>
        <span style="margin: 0 8px;">></span>
        <span style="<?php echo $currentStep === 'feedback' ? 'color: #2563EB; font-weight: bold;' : ''; ?>">피드백 작성</span>
    </div>

    <div style="display: flex; gap: 16px;">
        <!-- 왼쪽 패널 -->
        <div style="flex: 2;">
            <div class="card">
                <h2>학습 컨텐츠 선택</h2>
                <div class="card-content">
                    <?php foreach ($unitData as $unit): ?>
                        <div style="margin-bottom: 16px;">
                            <!-- 단원 선택 -->
                            <div style="padding: 12px; border: 1px solid <?php echo $selectedUnit == $unit['id'] ? '#BFDBFE' : '#D1D5DB'; ?>; border-radius: 8px; background-color: <?php echo $selectedUnit == $unit['id'] ? '#EFF6FF' : '#FFFFFF'; ?>;">
                                <a href="?view=teacher&event_id=<?php echo $_GET['event_id']; ?>&feedback=1&unit_id=<?php echo $unit['id']; ?>" style="text-decoration: none; color: inherit;">
                                    <span><?php echo $unit['title']; ?></span>
                                </a>
                            </div>
                            <?php if ($selectedUnit == $unit['id']): ?>
                                <?php foreach ($unit['topics'] as $topic): ?>
                                    <!-- 주제 선택 -->
                                    <div style="margin-left: 16px; margin-top: 8px; padding: 12px; border: 1px solid <?php echo $selectedTopic == $topic['id'] ? '#BFDBFE' : '#D1D5DB'; ?>; border-radius: 8px; background-color: <?php echo $selectedTopic == $topic['id'] ? '#EFF6FF' : '#FFFFFF'; ?>;">
                                        <a href="?view=teacher&event_id=<?php echo $_GET['event_id']; ?>&feedback=1&unit_id=<?php echo $unit['id']; ?>&topic_id=<?php echo $topic['id']; ?>" style="text-decoration: none; color: inherit;">
                                            <span><?php echo $topic['title']; ?></span>
                                        </a>
                                    </div>
                                    <?php if ($selectedTopic == $topic['id']): ?>
                                        <?php foreach ($topic['contents'] as $content): ?>
                                            <!-- 컨텐츠 선택 -->
                                            <div style="margin-left: 32px; margin-top: 8px; padding: 12px; border: 1px solid <?php echo $selectedContent == $content['id'] ? '#BFDBFE' : '#D1D5DB'; ?>; border-radius: 8px; background-color: <?php echo $selectedContent == $content['id'] ? '#EFF6FF' : '#FFFFFF'; ?>;">
                                                <a href="?view=teacher&event_id=<?php echo $_GET['event_id']; ?>&feedback=1&unit_id=<?php echo $unit['id']; ?>&topic_id=<?php echo $topic['id']; ?>&content_id=<?php echo $content['id']; ?>" style="text-decoration: none; color: inherit;">
                                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                                        <div>
                                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                                <span><?php echo $content['title']; ?></span>
                                                                <span style="font-size: 12px; padding: 4px 8px; border-radius: 4px; <?php echo getStatusColor($content['status']); ?>">
                                                                    <?php echo $content['status'] === 'warning' ? '주의' : '경고'; ?>
                                                                </span>
                                                            </div>
                                                            <div style="margin-top: 8px; font-size: 14px; color: #6B7280;">
                                                                <?php if ($content['type'] === 'quiz'): ?>
                                                                    <span>정답률: <?php echo $content['stats']['correctRate']; ?></span><br>
                                                                    <span>평균 시간: <?php echo $content['stats']['avgTime']; ?></span><br>
                                                                    <span>시도: <?php echo $content['stats']['attempts']; ?>회</span>
                                                                <?php else: ?>
                                                                    <span>학습 시간: <?php echo $content['stats']['timeSpent']; ?></span><br>
                                                                    <span>완료율: <?php echo $content['stats']['completion']; ?></span><br>
                                                                    <span>접속: <?php echo $content['stats']['attempts']; ?>회</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 오른쪽 패널 -->
        <div style="flex: 1;">
            <?php if ($currentStep === 'feedback' && $selectedContent): ?>
                <div class="card">
                    <h2>추천 피드백</h2>
                    <div class="card-content">
                        <?php foreach ($feedbackTemplates as $template): ?>
                            <!-- 피드백 템플릿 선택 -->
                            <div style="padding: 16px; border: 1px solid <?php echo $selectedFeedback == $template['id'] ? '#BFDBFE' : '#D1D5DB'; ?>; border-radius: 8px; background-color: <?php echo $selectedFeedback == $template['id'] ? '#EFF6FF' : '#FFFFFF'; ?>; margin-bottom: 16px;">
                                <a href="?view=teacher&event_id=<?php echo $_GET['event_id']; ?>&feedback=1&unit_id=<?php echo $selectedUnit; ?>&topic_id=<?php echo $selectedTopic; ?>&content_id=<?php echo $selectedContent; ?>&feedback_id=<?php echo $template['id']; ?>" style="text-decoration: none; color: inherit;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                        <span><?php echo $template['title']; ?></span>
                                        <span style="font-size: 12px; background-color: #DBEAFE; color: #1D4ED8; padding: 4px 8px; border-radius: 4px;">
                                            상관도 <?php echo $template['correlation'] * 100; ?>%
                                        </span>
                                    </div>
                                    <p style="font-size: 14px; color: #6B7280; margin-bottom: 8px;"><?php echo $template['message']; ?></p>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                                        <?php foreach ($template['suggestedActions'] as $action): ?>
                                            <span style="font-size: 12px; background-color: #F3F4F6; color: #374151; padding: 4px 8px; border-radius: 4px;"><?php echo $action; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="font-size: 12px; color: #6B7280; display: flex; justify-content: space-between;">
                                        <span>사용 <?php echo $template['usageCount']; ?>회</span>
                                        <span>성공률 <?php echo $template['successRate']; ?></span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($selectedFeedback): ?>
                            <!-- 피드백 전송 버튼 -->
                            <form method="post" action="?view=teacher&event_id=<?php echo $_GET['event_id']; ?>&feedback=1&unit_id=<?php echo $selectedUnit; ?>&topic_id=<?php echo $selectedTopic; ?>&content_id=<?php echo $selectedContent; ?>&feedback_id=<?php echo $selectedFeedback; ?>&step=send_feedback">
                                <button type="submit" style="width: 100%; padding: 12px; background-color: #2563EB; color: #FFFFFF; border: none; border-radius: 8px;">피드백 전송</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 피드백 이력 -->
                <div class="card" style="margin-top: 16px;">
                    <h2>피드백 이력</h2>
                    <div class="card-content">
                        <?php foreach ($feedbackHistory as $history): ?>
                            <div style="padding: 12px; border: 1px solid #D1D5DB; border-radius: 8px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="font-size: 14px; color: #6B7280;"><?php echo $history['date']; ?></span>
                                    <span style="font-size: 12px; background-color: #DBEAFE; color: #1D4ED8; padding: 4px 8px; border-radius: 4px;">진행 중</span>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                        <span style="font-size: 14px;">개선도</span>
                                        <div style="display: flex; gap: 4px;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <div style="width: 16px; height: 16px; border-radius: 50%; background-color: <?php echo $i <= $history['improvement'] ? '#3B82F6' : '#E5E7EB'; ?>;"></div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p style="font-size: 14px; color: #6B7280;"><?php echo $history['studentResponse']; ?></p>
                                    <div style="width: 100%; background-color: #E5E7EB; border-radius: 4px; height: 8px; margin-top: 8px;">
                                        <div style="width: <?php echo $history['completionRate']; ?>%; background-color: #2563EB; height: 8px; border-radius: 4px;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// 피드백 전송 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['step']) && $_GET['step'] === 'send_feedback') {
    // 여기에 피드백 전송 로직을 추가하세요.

    // 예를 들어, 데이터베이스에 저장하거나, 학생에게 알림을 보내는 등의 작업을 수행합니다.

    // 전송 후 교사 화면으로 리다이렉트
    header('Location: ?view=teacher');
    exit();
}
?>
