<?php
/**
 * Chapter42 Final Integration
 * 완전히 리팩터링된 버전 - Bootstrap 5, 컴포넌트 기반 구조
 */

// 필수 파일 포함
require_once('includes/chapter42_logic.php');
require_once('components/sidebar.php');
require_once('components/progress-bar.php');
require_once('components/topic-card.php');
require_once('components/modals.php');

// 세션 및 초기화
session_start();
require_once('../../../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/lib/adminlib.php');

// 파라미터 처리
$cid = optional_param('cid', 0, PARAM_INT);
$nch = optional_param('nch', 0, PARAM_INT);
$studentid = optional_param('studentid', 0, PARAM_INT);
$type = optional_param('type', '', PARAM_TEXT);
$mode = optional_param('mode', 'normal', PARAM_TEXT);

// 데이터베이스 연결
global $DB, $USER;

// 데이터 초기화
$pageData = [
    'cid' => $cid,
    'nch' => $nch,
    'studentid' => $studentid,
    'type' => $type,
    'mode' => $mode,
    'USER' => $USER
];

// 커리큘럼 데이터 가져오기
$curri = $DB->get_record_sql("SELECT * FROM mdl_abessi_hd_abedunet where id='$cid' ORDER BY id DESC LIMIT 1");
if (!$curri) {
    die('Invalid curriculum ID');
}

// 체크리스트 처리
$checklistid = ($mode === 'review') ? $curri->cntitem2 : $curri->cntitem1;
$checklistData = processChecklistItems($DB, $checklistid, $studentid, $pageData);

// 챕터 리스트 생성
$chapterlist = '';
$chapters = $DB->get_records_sql("SELECT * FROM mdl_abessi_hd_abedunet where mtid='$curri->mtid' AND subject='$curri->subject' ORDER BY chapterseq ASC");
foreach($chapters as $chapter) {
    $active = ($chapter->id == $cid) ? 'class="current-chapter bg-light"' : '';
    $chapterlist .= '<tr ' . $active . '>';
    $chapterlist .= '<td><a href="chapter42.php?cid=' . $chapter->id . '&nch=' . $chapter->chapterseq . '&studentid=' . $studentid . '&type=init">';
    $chapterlist .= $chapter->chapterseq . '. ' . $chapter->chaptername . '</a></td>';
    $chapterlist .= '</tr>';
}

// 사이드바 파라미터 준비
$sidebarParams = [
    'chaptertitle' => '<h2>' . $curri->grade . ' ' . $curri->subject . '</h2>',
    'subjectname' => $curri->chaptername,
    'chapterlist' => $chapterlist,
    'modechange' => ($mode === 'review') ? '' : 'mode=review&',
    'modetext' => ($mode === 'review') ? '일반 모드로' : '복습 모드로',
    'domchapters' => generateSubjectList($curri, ($mode === 'review' ? '' : 'mode=review&'), $studentid),
    'cid' => $cid,
    'studentid' => $studentid,
    'USER' => $USER
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $curri->chaptername; ?> - Chapter42</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/chapter42.css">
    <link rel="stylesheet" href="assets/css/chapter42-responsive.css">
    
    <!-- Component Styles -->
    <?php 
    echo getTopicCardStyles();
    echo getModalStyles();
    ?>
    
    <!-- jQuery (기존 코드 호환성) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <!-- 메인 컨테이너 (CSS Grid) -->
    <div class="chapter-container">
        <!-- 사이드바 영역 -->
        <aside class="chapter-sidebar">
            <?php echo renderSidebar($sidebarParams); ?>
        </aside>
        
        <!-- 헤더 영역 (옵션) -->
        <header class="chapter-header d-none d-md-block">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h4 mb-0"><?php echo $curri->chaptername; ?></h1>
                <div class="header-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="showProgressModal()">
                        <i class="fas fa-chart-line"></i> 진행 상황
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="showGoalModal()">
                        <i class="fas fa-bullseye"></i> 목표 설정
                    </button>
                </div>
            </div>
        </header>
        
        <!-- 메인 콘텐츠 영역 -->
        <main class="chapter-main">
            <div class="container-fluid">
                <!-- 진행률 표시 -->
                <div class="row mb-4">
                    <div class="col-12">
                        <?php 
                        echo renderProgressBar(
                            $checklistData['progressfilled'], 
                            $checklistData['bgtype'],
                            [
                                'showLabel' => true,
                                'showTooltip' => true,
                                'description' => getProgressMessage($checklistData['progressfilled'])
                            ]
                        );
                        ?>
                    </div>
                </div>
                
                <!-- 토픽 카드 그리드 -->
                <div class="row">
                    <div class="col-12">
                        <div id="tableContainer" class="topic-cards-container">
                            <?php 
                            // 토픽 카드 데이터 준비 (실제 구현에서는 checklistData에서 가져옴)
                            $topicCards = []; // processChecklistItems에서 생성된 카드 데이터
                            
                            if (!empty($topicCards)) {
                                echo renderTopicCardGroup($mode, $topicCards, [
                                    'showProgress' => true,
                                    'showFilter' => true
                                ]);
                            } else {
                                // 토픽 리스트 직접 출력 (기존 HTML)
                                echo $checklistData['topiclist'];
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- 추가 기능 영역 -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">학습 통계</h5>
                                <div class="stats-summary">
                                    <p>완료한 토픽: <strong><?php echo $checklistData['nstage']; ?></strong></p>
                                    <p>진행률: <strong><?php echo round($checklistData['progressfilled']); ?>%</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">빠른 작업</h5>
                                <div class="quick-actions">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="showReviewModal()">
                                        <i class="fas fa-redo"></i> 복습 추가
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="openCallbackModal()">
                                        <i class="fas fa-bell"></i> 알림 설정
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- 모바일 사이드바 -->
    <?php echo renderMobileSidebar($sidebarParams); ?>
    
    <!-- 콜백 버튼 -->
    <button id="callbackButton" class="btn btn-primary" style="display: none;">
        <i class="fas fa-bell"></i> 콜백
    </button>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/chapter42.js"></script>
    <script src="assets/js/responsive.js"></script>
    
    <!-- Component Scripts -->
    <?php 
    echo initSidebarScript();
    echo initProgressBarScript();
    echo initTopicCardScript();
    echo initModalScript();
    ?>
    
    <!-- 모달 렌더링 -->
    <?php 
    echo renderCallbackModal(['studentId' => $studentid, 'chapterId' => $cid]);
    echo renderGoalModal();
    echo renderProgressModal($checklistData['progressfilled'], [
        'completed' => $checklistData['nstage'],
        'total' => 10, // 실제 토픽 수로 대체
        'timeSpent' => 0,
        'lastAccess' => date('Y-m-d')
    ]);
    echo renderReviewModal();
    ?>
    
    <!-- 페이지별 초기화 스크립트 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 테이블 컨테이너 페이드인 효과
        const tableContainer = document.getElementById('tableContainer');
        if (tableContainer) {
            setTimeout(() => {
                tableContainer.style.opacity = '1';
            }, 300);
        }
        
        // 모니터링 상태 체크
        if (typeof ChapterModule !== 'undefined') {
            ChapterModule.checkMonitoringStatus();
        }
        
        // 디바이스 변경 이벤트 리스너
        document.addEventListener('devicechange', function(e) {
            console.log('Device changed:', e.detail);
            // 디바이스 변경에 따른 추가 처리
        });
    });
    
    // 전역 함수 호환성 (기존 onclick 핸들러용)
    function CheckProgress(ntopic, thismenutext, chkitemid, studentid, cid, nstage) {
        if (window.ChapterModule) {
            window.ChapterModule.checkProgress(ntopic, thismenutext, chkitemid, studentid);
        }
    }
    </script>
</body>
</html>