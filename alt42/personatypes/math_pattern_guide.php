<?php
/**
 * 수학인지관성 도감 - 별도 페이지
 * 60가지 수학 학습 패턴을 시각적으로 보여주는 도감
 */

require_once(__DIR__ . '/../../../../../../config.php');

// 페이지 설정
$PAGE->set_url(new moodle_url('/local/augmented_teacher/alt42/shiningstars/math_pattern_guide.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('수학인지관성 도감');
$PAGE->set_heading('수학인지관성 도감');
$PAGE->set_pagelayout('standard');

// 로그인 확인
require_login();

// CSS와 JavaScript 추가
$PAGE->requires->css('/local/augmented_teacher/alt42/shiningstars/css/pattern_guide.css');
$PAGE->requires->js('/local/augmented_teacher/alt42/shiningstars/js/pattern_guide.js');

// 사용자 ID
$user_id = $USER->id;

// 카테고리별 패턴 데이터 가져오기
$categories = $DB->get_records('mdl_alt42i_pattern_categories', null, 'display_order ASC');

// 각 카테고리별 패턴 수 계산
foreach ($categories as $category) {
    $category->pattern_count = $DB->count_records('mdl_alt42i_math_patterns', ['category_id' => $category->id]);
}

// 사용자의 수집된 패턴 수 계산
$collected_patterns = $DB->count_records_sql(
    "SELECT COUNT(DISTINCT mp.id) 
     FROM {alt42i_math_patterns} mp
     JOIN {alt42i_user_pattern_progress} upp ON mp.id = upp.pattern_id
     WHERE upp.user_id = ? AND upp.is_collected = 1",
    [$user_id]
);

$total_patterns = $DB->count_records('mdl_alt42i_math_patterns');
$collection_rate = $total_patterns > 0 ? round(($collected_patterns / $total_patterns) * 100, 1) : 0;

// 출력 시작
echo $OUTPUT->header();
?>

<div class="pattern-guide-container">
    <!-- 헤더 영역 -->
    <div class="guide-header">
        <h1>📚 수학인지관성 도감</h1>
        <p class="subtitle">60가지 수학 학습 패턴을 발견하고 극복해보세요!</p>
        
        <!-- 진행 상황 표시 -->
        <div class="progress-overview">
            <div class="progress-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $collected_patterns; ?></span>
                    <span class="stat-label">수집된 패턴</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_patterns; ?></span>
                    <span class="stat-label">전체 패턴</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $collection_rate; ?>%</span>
                    <span class="stat-label">달성률</span>
                </div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $collection_rate; ?>%"></div>
            </div>
        </div>
    </div>

    <!-- 필터 영역 -->
    <div class="filter-section">
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">전체 보기</button>
            <?php foreach ($categories as $category): ?>
                <button class="filter-btn" data-filter="category-<?php echo $category->id; ?>">
                    <?php echo s($category->category_name); ?> (<?php echo $category->pattern_count; ?>)
                </button>
            <?php endforeach; ?>
        </div>
        
        <div class="view-options">
            <button class="view-btn active" data-view="grid">
                <i class="fa fa-th"></i> 격자
            </button>
            <button class="view-btn" data-view="list">
                <i class="fa fa-list"></i> 목록
            </button>
        </div>
    </div>

    <!-- 카테고리별 패턴 표시 -->
    <div class="categories-container">
        <?php foreach ($categories as $category): ?>
            <?php
            // 카테고리별 패턴 가져오기
            $patterns = $DB->get_records_sql(
                "SELECT mp.*, ps.action, ps.check_method,
                        COALESCE(upp.is_collected, 0) as is_collected,
                        COALESCE(upp.mastery_level, 0) as mastery_level
                 FROM {alt42i_math_patterns} mp
                 JOIN {alt42i_pattern_solutions} ps ON mp.id = ps.pattern_id
                 LEFT JOIN {alt42i_user_pattern_progress} upp 
                      ON mp.id = upp.pattern_id AND upp.user_id = ?
                 WHERE mp.category_id = ?
                 ORDER BY mp.id",
                [$user_id, $category->id]
            );
            ?>
            
            <div class="category-section" data-category="category-<?php echo $category->id; ?>">
                <div class="category-header">
                    <h2><?php echo s($category->category_name); ?></h2>
                    <p class="category-desc"><?php echo s($category->description); ?></p>
                </div>
                
                <div class="patterns-grid">
                    <?php foreach ($patterns as $pattern): ?>
                        <div class="pattern-card <?php echo $pattern->is_collected ? 'collected' : 'not-collected'; ?>" 
                             data-pattern-id="<?php echo $pattern->id; ?>"
                             data-priority="<?php echo $pattern->priority; ?>">
                            
                            <div class="pattern-card-header">
                                <span class="pattern-number">#<?php echo $pattern->id; ?></span>
                                <span class="pattern-icon"><?php echo $pattern->icon; ?></span>
                                <?php if ($pattern->is_collected): ?>
                                    <span class="collected-badge">✅</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="pattern-card-body">
                                <h3 class="pattern-name"><?php echo s($pattern->name); ?></h3>
                                <p class="pattern-desc"><?php echo s($pattern->description); ?></p>
                                
                                <?php if ($pattern->is_collected): ?>
                                    <div class="mastery-indicator">
                                        <span class="mastery-label">숙달도</span>
                                        <div class="mastery-bar">
                                            <div class="mastery-fill" style="width: <?php echo $pattern->mastery_level; ?>%"></div>
                                        </div>
                                        <span class="mastery-percent"><?php echo $pattern->mastery_level; ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="pattern-card-footer">
                                <span class="priority-badge priority-<?php echo $pattern->priority; ?>">
                                    <?php 
                                    echo match($pattern->priority) {
                                        'high' => '높음',
                                        'medium' => '보통',
                                        'low' => '낮음',
                                        default => '보통'
                                    };
                                    ?>
                                </span>
                                <span class="audio-time">
                                    <i class="fa fa-headphones"></i> <?php echo s($pattern->audio_time); ?>
                                </span>
                                <button class="detail-btn" onclick="showPatternDetail(<?php echo $pattern->id; ?>)">
                                    자세히 보기
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 패턴 상세 정보 모달 -->
<div id="pattern-detail-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <button class="modal-close" onclick="closePatternDetail()">×</button>
        <div id="pattern-detail-content">
            <!-- AJAX로 내용이 로드됨 -->
        </div>
    </div>
</div>

<!-- 메인 페이지로 돌아가기 버튼 -->
<div style="text-align: center; margin: 40px 0;">
    <a href="index.php" class="back-to-main-btn">
        <i class="fa fa-arrow-left"></i> Shining Stars로 돌아가기
    </a>
</div>

<?php
echo $OUTPUT->footer();
?>