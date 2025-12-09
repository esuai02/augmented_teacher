<?php
// header.php - 공통 헤더 컴포넌트
// 이 파일을 포함하기 전에 필요한 변수들:
// $studentid - 학생 ID
// $current_mode - 현재 학습 모드 (optional)
// $mode_display - 모드 표시 배열 (optional)
// $active_page - 현재 활성 페이지 ('home', 'index1', 'index2', 'index3', 'index4')

// 기본값 설정
if (!isset($active_page)) {
    $active_page = basename($_SERVER['PHP_SELF'], '.php');
}

// 모드 표시 배열이 없으면 정의
if (!isset($mode_display)) {
    $mode_display = array(
        'curriculum' => array('title' => '커리큘럼 중심', 'icon' => '📚'),
        'custom' => array('title' => '맞춤학습 중심', 'icon' => '🎯'),
        'exam' => array('title' => '시험대비 중심', 'icon' => '✏️'),
        'mission' => array('title' => '단기미션 중심', 'icon' => '⚡'),
        'reflection' => array('title' => '자기성찰 중심', 'icon' => '🧠'),
        'selfled' => array('title' => '자기주도 중심', 'icon' => '🚀'),
        'cognitive' => array('title' => '도제학습 중심', 'icon' => '🔍'),
        'timecentered' => array('title' => '시간성찰 중심', 'icon' => '🕒'),
        'curiositycentered' => array('title' => '탐구학습 중심', 'icon' => '🔭')
    );
}
?>

<style>
    /* Navigation Header Styles */
    .nav-top {
        background: linear-gradient(135deg, #8B5CF6 0%, #A855F7 50%, #7C3AED 100%);
        padding: 20px 0;
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);
        position: sticky;
        top: 0;
        z-index: 1000;
        height: 88px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
    }

    .content-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .nav-controls {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .header-nav {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .nav-btn {
        padding: 12px 24px;
        background: rgba(255,255,255,0.15);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .nav-btn:hover {
        background: rgba(255,255,255,0.25);
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .nav-btn.active {
        background: rgba(255,255,255,0.95);
        color: #7C3AED;
        font-weight: 700;
        border: 2px solid rgba(255,255,255,0.3);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .nav-btn.active:hover {
        background: rgba(255,255,255,1);
        color: #7C3AED;
        transform: translateY(-1px);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
    }

    .nav-btn.mode-selected {
        background: linear-gradient(135deg, #FF6B6B 0%, #FFE66D 100%);
        color: white;
        font-weight: 700;
        border: 2px solid rgba(255,255,255,0.4);
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }

    .nav-btn.mode-selected:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 25px rgba(255, 107, 107, 0.4);
    }

    .nav-btn.mode-not-selected {
        background: linear-gradient(135deg, #6B7DFF 0%, #9B66FF 100%);
        color: white;
        font-weight: 600;
        border: 2px solid rgba(255,255,255,0.3);
        animation: pulse 2s infinite;
    }

    .nav-btn.mode-not-selected:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 25px rgba(107, 125, 255, 0.4);
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(155, 102, 255, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(155, 102, 255, 0); }
        100% { box-shadow: 0 0 0 0 rgba(155, 102, 255, 0); }
    }

    .view-controls {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 100;
    }

    /* Minimap Styles */
    .minimap-dropdown {
        position: absolute;
        top: 50px;
        right: 0;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        padding: 1.5rem;
        display: none;
        min-width: 250px;
        z-index: 2000;
    }

    .minimap-dropdown.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    .minimap-title {
        font-size: 1.2rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .minimap-item {
        padding: 0.75rem 1rem;
        margin: 0.5rem 0;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        color: #333;
    }

    .minimap-item:hover {
        background: #f0f4ff;
        transform: translateX(5px);
    }

    .minimap-item.current {
        background: #7C3AED;
        color: white;
        font-weight: bold;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .view-toggle-btn {
        padding: 10px 15px;
        background: rgba(255,255,255,0.2);
        color: white;
        text-decoration: none;
        border-radius: 50%;
        font-size: 20px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .view-toggle-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: scale(1.1);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-top {
            padding: 15px 0;
        }

        .content-container {
            padding: 0 15px;
        }

        .header-nav {
            gap: 8px;
        }

        .nav-btn {
            padding: 10px 18px;
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
        .nav-top {
            padding: 12px 0;
        }

        .content-container {
            padding: 0 10px;
        }

        .header-nav {
            gap: 6px;
        }

        .nav-btn {
            padding: 8px 12px;
            font-size: 12px;
            border-radius: 25px;
        }
    }
</style>

<div class="nav-top">
    <div class="content-container">
        <div class="nav-controls">
            <div class="header-nav">
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php?userid=<?php echo $studentid; ?>"
                   class="nav-btn <?php echo ($active_page === 'index') ? 'active' : ''; ?>">
                    🏠 홈
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/index42.php?id=<?php echo $studentid; ?>"
                   class="nav-btn">
                    👩🏻‍🎨‍ 내공부방
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php?id=<?php echo $studentid; ?>"
                   class="nav-btn">
                    📝 오늘
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule42.php?id=<?php echo $studentid; ?>"
                   class="nav-btn">
                    📅 일정
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/goals42.php?id=<?php echo $studentid; ?>"
                   class="nav-btn">
                    🎯 목표
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/student_inbox.php?studentid=<?php echo $studentid; ?>"
                   class="nav-btn">
                    📩 메세지
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php?userid=<?php echo $studentid; ?>"
                   class="nav-btn">
                    📅 수학일기
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/index.php"
                   class="nav-btn">
                    🚀 AI튜터
                </a>
                <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/indexm.php?userid=<?php echo $studentid; ?>"
                   class="nav-btn">
                    🧠 메타인지
                </a>
            </div>
            <div class="view-controls">
                <button class="view-toggle-btn" onclick="toggleMinimap()" title="미니맵">
                    🗺️
                </button>
                <div class="minimap-dropdown" id="minimapDropdown">
                    <h3 class="minimap-title">
                        <span>🗺️</span>
                        <span>학습 목차</span>
                    </h3>
                    <a href="index.php?userid=<?php echo $studentid; ?>" class="minimap-item <?php echo ($active_page === 'index') ? 'current' : ''; ?>">
                        <span>🏠</span>
                        <span>메인 홈</span>
                    </a>
                    <a href="index1.php?userid=<?php echo $studentid; ?>" class="minimap-item <?php echo ($active_page === 'index1') ? 'current' : ''; ?>">
                        <span>📚</span>
                        <span>개념학습</span>
                    </a>
                    <a href="index2.php?userid=<?php echo $studentid; ?>" class="minimap-item <?php echo ($active_page === 'index2') ? 'current' : ''; ?>">
                        <span>🎯</span>
                        <span>심화학습</span>
                    </a>
                    <a href="index3.php?userid=<?php echo $studentid; ?>" class="minimap-item <?php echo ($active_page === 'index3') ? 'current' : ''; ?>">
                        <span>📝</span>
                        <span>내신준비</span>
                    </a>
                    <a href="index4.php?userid=<?php echo $studentid; ?>" class="minimap-item <?php echo ($active_page === 'index4') ? 'current' : ''; ?>">
                        <span>🎓</span>
                        <span>수능대비</span>
                    </a>
                    <a href="indexm.php?userid=<?php echo $studentid; ?>" class="minimap-item <?php echo ($active_page === 'indexm') ? 'current' : ''; ?>">
                        <span>🧠</span>
                        <span>메타인지</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMinimap() {
    const dropdown = document.getElementById('minimapDropdown');
    dropdown.classList.toggle('active');
}

// Close minimap when clicking outside
document.addEventListener('click', function(event) {
    const minimap = document.getElementById('minimapDropdown');
    const button = event.target.closest('[onclick="toggleMinimap()"]');

    if (!button && minimap && !minimap.contains(event.target)) {
        minimap.classList.remove('active');
    }
});
</script>