<?php
/**
 * Student Manual System - Main Page
 * File: alt42/orchestration/agents/studentmanual/index.php
 *
 * 학생용 알파튜터42 메뉴얼 시스템 메인 페이지
 * 검색, 필터링, 메뉴얼 항목 표시 기능 포함
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Include error handler
require_once(__DIR__ . '/includes/error_handler.php');

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? 'student';

// 학생만 접근 가능
if ($role !== 'student') {
    // 교사는 관리 페이지로 리다이렉트
    if (in_array($role, ['teacher', 'admin'])) {
        header("Location: admin/index.php");
        exit;
    }
    StudentManualErrorHandler::displayErrorPage(
        "접근 권한 없음",
        "학생만 이 페이지에 접근할 수 있습니다.",
        ['file' => __FILE__, 'line' => __LINE__]
    );
}

// 데이터베이스에서 메뉴얼 항목 조회
$manualItems = [];
try {
    $items = $DB->get_records('at42_studentmanual_items', null, 'created_at DESC');
    
    foreach ($items as $item) {
        // 각 항목의 연결된 컨텐츠 조회
        $sql = "SELECT c.*, ic.display_order 
                FROM {at42_stumanual_item_cnts} ic
                JOIN {at42_studentmanual_contents} c ON ic.content_id = c.id
                WHERE ic.item_id = ?
                ORDER BY ic.display_order ASC";
        $contents = $DB->get_records_sql($sql, [$item->id]);
        
        $manualItems[] = [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'agent_id' => $item->agent_id,
            'created_at' => $item->created_at,
            'contents' => array_values($contents)
        ];
    }
} catch (Exception $e) {
    StudentManualErrorHandler::handleDbError($e, "Loading manual items");
    $manualItems = [];
}

// 에이전트 목록 (주요 에이전트만)
$agents = [
    'agent01' => '온보딩',
    'agent02' => '시험 일정',
    'agent03' => '목표 분석',
    'agent04' => '약점 분석',
    'agent05' => '학습 감정',
    'agent07' => '상호작용 타겟팅'
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알파튜터42 메뉴얼</title>
    <link rel="stylesheet" href="assets/css/manual.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="manual-container">
        <!-- 헤더 -->
        <header class="manual-header">
            <h1>📚 알파튜터42 메뉴얼</h1>
            <p class="subtitle">시스템 사용법을 쉽게 찾아보세요</p>
        </header>

        <!-- 검색 및 필터 영역 -->
        <div class="search-filter-section">
            <!-- 검색 바 -->
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="메뉴얼 검색... (제목, 설명으로 검색)" autocomplete="off">
                <button id="search-btn" aria-label="검색">🔍</button>
            </div>

            <!-- 필터 -->
            <div class="filter-section">
                <label>에이전트 필터:</label>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-agent="all">전체</button>
                    <?php foreach ($agents as $agentId => $agentName): ?>
                        <button class="filter-btn" data-agent="<?php echo htmlspecialchars($agentId); ?>">
                            <?php echo htmlspecialchars($agentName); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 메뉴얼 항목 그리드 -->
        <main class="manual-grid" id="manual-grid">
            <?php if (empty($manualItems)): ?>
                <div class="empty-state">
                    <p>아직 등록된 메뉴얼이 없습니다.</p>
                </div>
            <?php else: ?>
                <?php foreach ($manualItems as $item): ?>
                    <div class="manual-card" data-agent="<?php echo htmlspecialchars($item['agent_id']); ?>" 
                         data-title="<?php echo htmlspecialchars(strtolower($item['title'])); ?>"
                         data-description="<?php echo htmlspecialchars(strtolower($item['description'])); ?>">
                        <div class="card-header">
                            <span class="agent-badge"><?php echo htmlspecialchars($agents[$item['agent_id']] ?? $item['agent_id']); ?></span>
                            <h3 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        </div>
                        <div class="card-body">
                            <p class="card-description"><?php echo htmlspecialchars(mb_substr($item['description'], 0, 100)) . (mb_strlen($item['description']) > 100 ? '...' : ''); ?></p>
                            <?php if (!empty($item['contents'])): ?>
                                <div class="card-contents-preview">
                                    <?php 
                                    $contentTypes = [];
                                    foreach ($item['contents'] as $content) {
                                        $contentTypes[] = $content->content_type;
                                    }
                                    $uniqueTypes = array_unique($contentTypes);
                                    foreach ($uniqueTypes as $type): 
                                    ?>
                                        <span class="content-type-badge" data-type="<?php echo htmlspecialchars($type); ?>">
                                            <?php 
                                            $typeNames = ['image' => '🖼️ 이미지', 'video' => '🎥 동영상', 'audio' => '🎵 음성', 'link' => '🔗 링크'];
                                            echo $typeNames[$type] ?? $type;
                                            ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <button class="view-detail-btn" data-item-id="<?php echo $item['id']; ?>">자세히 보기</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <!-- 검색 결과 없음 메시지 -->
        <div class="no-results hidden" id="no-results">
            <p>검색 결과가 없습니다.</p>
        </div>
    </div>

    <!-- 상세 보기 모달 -->
    <div id="detail-modal" class="modal hidden">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <div id="modal-body"></div>
        </div>
    </div>

    <!-- 이미지 확대 모달 -->
    <div id="image-modal" class="image-modal hidden">
        <span class="image-modal-close">&times;</span>
        <img id="modal-image" src="" alt="확대 이미지">
    </div>

    <!-- 데이터 전달 -->
    <script>
        window.manualData = <?php echo json_encode($manualItems, JSON_UNESCAPED_UNICODE); ?>;
        window.agents = <?php echo json_encode($agents, JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="assets/js/manual.js?v=<?php echo time(); ?>"></script>
</body>
</html>

