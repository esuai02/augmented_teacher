<?php
/**
 * 사이드바 컴포넌트
 * 좌측 네비게이션 메뉴를 렌더링합니다.
 */

/**
 * 사이드바 렌더링 함수
 * 
 * @param array $params 사이드바 렌더링에 필요한 파라미터들
 * @return string HTML 문자열
 */
function renderSidebar($params) {
    extract($params);
    
    // 필수 변수 체크
    $chaptertitle = $chaptertitle ?? '';
    $subjectname = $subjectname ?? '';
    $chapterlist = $chapterlist ?? '';
    $modechange = $modechange ?? '';
    $modetext = $modetext ?? '';
    $domchapters = $domchapters ?? '';
    $cid = $cid ?? '';
    $studentid = $studentid ?? '';
    $USER = $USER ?? null;
    
    ob_start();
    ?>
    <div class="left-column">
        <div class="left-sidebar">
            <?php echo $chaptertitle; ?>
            <h3 class="sidebar-title"><b>▶ <?php echo $subjectname; ?></b></h3>
            <br>
            
            <!-- 챕터 목록 테이블 -->
            <div class="chapter-list">
                <table class="table table-sm">
                    <?php echo $chapterlist; ?>
                </table>
            </div>
            
            <hr class="sidebar-divider">
            
            <!-- 모드 변경 링크 -->
            <div class="mode-controls">
                <a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?<?php echo $modechange; ?>cid=<?php echo $cid; ?>&nch=1&studentid=<?php echo $studentid; ?>&type=init" 
                   class="mode-link">
                    <img style="margin-bottom:5px;" 
                         loading="lazy" 
                         src="https://mathking.kr/Contents/IMAGES/learningpath.png" 
                         width="20" 
                         alt="Learning Path">
                    &nbsp;
                    <span style="font-size:16px;"><?php echo $modetext; ?></span>
                </a>
                
                <?php if($USER && isset($USER->id)): ?>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic42.php?id=<?php echo $USER->id; ?>&userid=<?php echo $studentid; ?>"
                   class="synergetic-link">
                    <img style="margin-bottom:10px;" 
                         src="https://mathking.kr/moodle/local/augmented_teacher/IMAGES/circulation.png" 
                         width="40" 
                         alt="Synergetic">
                </a>
                <?php endif; ?>
            </div>
            
            <br><br>
            
            <!-- 도메인 챕터 목록 -->
            <div class="domain-chapters">
                <?php echo $domchapters; ?>
            </div>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * 모바일용 사이드바 렌더링 함수
 * 토글 가능한 모바일 사이드바를 생성합니다.
 * 
 * @param array $params 사이드바 렌더링에 필요한 파라미터들
 * @return string HTML 문자열
 */
function renderMobileSidebar($params) {
    extract($params);
    
    ob_start();
    ?>
    <!-- 모바일 사이드바 토글 버튼 -->
    <button class="btn btn-primary d-lg-none mobile-sidebar-toggle" 
            type="button" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#mobileSidebar" 
            aria-controls="mobileSidebar"
            style="position: fixed; top: 10px; left: 10px; z-index: 1000;">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- 모바일 사이드바 (Offcanvas) -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mobileSidebarLabel"><?php echo $subjectname; ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <?php echo $chaptertitle; ?>
            
            <!-- 챕터 목록 -->
            <div class="chapter-list-mobile">
                <table class="table table-sm">
                    <?php echo $chapterlist; ?>
                </table>
            </div>
            
            <hr>
            
            <!-- 모드 변경 링크 -->
            <div class="mode-controls-mobile">
                <a href="https://mathking.kr/moodle/local/augmented_teacher/books/chapter42.php?<?php echo $modechange; ?>cid=<?php echo $cid; ?>&nch=1&studentid=<?php echo $studentid; ?>&type=init" 
                   class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <img src="https://mathking.kr/Contents/IMAGES/learningpath.png" width="16" alt="">
                    <?php echo $modetext; ?>
                </a>
            </div>
            
            <!-- 도메인 챕터 목록 -->
            <div class="domain-chapters-mobile">
                <?php echo $domchapters; ?>
            </div>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * 사이드바 초기화 스크립트
 * 사이드바 관련 JavaScript 초기화 코드를 생성합니다.
 * 
 * @return string JavaScript 코드
 */
function initSidebarScript() {
    ob_start();
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 사이드바 현재 챕터 하이라이트
        const currentChapter = document.querySelector('.chapter-list .current-chapter');
        if (currentChapter) {
            currentChapter.classList.add('bg-light', 'fw-bold');
        }
        
        // 모바일 사이드바 토글 처리
        const mobileSidebarToggle = document.querySelector('.mobile-sidebar-toggle');
        if (mobileSidebarToggle && window.innerWidth < 992) {
            mobileSidebarToggle.style.display = 'block';
        }
        
        // 윈도우 리사이즈 시 사이드바 조정
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                // 데스크톱 모드
                const offcanvas = document.querySelector('#mobileSidebar');
                if (offcanvas && bootstrap.Offcanvas.getInstance(offcanvas)) {
                    bootstrap.Offcanvas.getInstance(offcanvas).hide();
                }
            }
        });
        
        // 사이드바 스크롤 위치 저장 및 복원
        const sidebar = document.querySelector('.left-sidebar');
        if (sidebar) {
            // 저장된 스크롤 위치 복원
            const savedScrollPos = sessionStorage.getItem('sidebarScrollPos');
            if (savedScrollPos) {
                sidebar.scrollTop = parseInt(savedScrollPos);
            }
            
            // 스크롤 위치 저장
            sidebar.addEventListener('scroll', function() {
                sessionStorage.setItem('sidebarScrollPos', sidebar.scrollTop);
            });
        }
    });
    </script>
    <?php
    
    return ob_get_clean();
}
?>