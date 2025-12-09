<?php
/**
 * 수학인지관성 도감 - DB 데이터 표시
 */

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userid = $_GET["userid"] ?? $USER->id; 

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$userid' AND fieldid='22'"); 
$role = $userrole->data ?? 'student';

// 패턴 데이터 가져오기
$patterns = $DB->get_records_sql("
    SELECT 
        p.id,
        p.name,
        p.description,
        p.icon,
        p.priority,
        p.audio_time,
        p.category_id,
        c.category_name,
        s.action,
        s.check_method,
        s.audio_script,
        s.teacher_dialog
    FROM {alt42i_math_patterns} p
    JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
    JOIN {alt42i_pattern_solutions} s ON p.id = s.pattern_id
    ORDER BY p.category_id, p.id
");

// 카테고리 가져오기
$categories = $DB->get_records('alt42i_pattern_categories', null, 'id');

// 사용자 진행 상황 가져오기 (테이블이 있는 경우만)
$progress_map = [];
$collected_count = 0;

if ($DB->get_manager()->table_exists('alt42i_user_pattern_progress')) {
    $progress = $DB->get_records('alt42i_user_pattern_progress', ['user_id' => $userid]);
    foreach ($progress as $p) {
        $progress_map[$p->pattern_id] = $p;
        if ($p->is_collected) {
            $collected_count++;
        }
    }
} else {
    // 테이블이 없으면 안내 메시지 표시
    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px; border-radius: 5px;">';
    echo '<strong>알림:</strong> 진행 상황 추적 테이블이 없습니다. ';
    echo '<a href="create_progress_tables.php">여기를 클릭</a>하여 테이블을 생성하세요.';
    echo '</div>';
}

$progress_percent = round(($collected_count / 60) * 100);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학인지관성 도감</title>
    <link rel="stylesheet" href="css/pattern_guide.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .pattern-guide-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .guide-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 20px;
        }
        .guide-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .progress-overview {
            max-width: 600px;
            margin: 0 auto 40px;
        }
        .progress-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            display: block;
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            display: block;
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .progress-bar-container {
            width: 100%;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: width 0.5s ease;
            width: <?php echo $progress_percent; ?>%;
        }
        .category-section {
            margin-bottom: 50px;
        }
        .category-header h2 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 20px;
            padding-left: 10px;
            border-left: 4px solid #667eea;
        }
        .patterns-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .pattern-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            border: 2px solid transparent;
        }
        .pattern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        /* 모달 애니메이션 */
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .modal-content {
            animation: modalFadeIn 0.3s ease;
        }
        
        /* 스크롤바 스타일 */
        #pattern-modal-content {
            scrollbar-width: thin;
            scrollbar-color: #667eea #f0f0f0;
        }
        
        #pattern-modal-content::-webkit-scrollbar {
            width: 8px;
        }
        
        #pattern-modal-content::-webkit-scrollbar-track {
            background: #f0f0f0;
        }
        
        #pattern-modal-content::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 4px;
        }
        
        #pattern-modal-content::-webkit-scrollbar-thumb:hover {
            background: #5a67d8;
        }
        .pattern-card.collected {
            border-color: #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.02));
        }
        .pattern-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .pattern-number {
            font-size: 0.9em;
            color: #999;
            font-weight: bold;
        }
        .pattern-icon {
            font-size: 2em;
        }
        .pattern-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .pattern-desc {
            color: #666;
            font-size: 0.95em;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        .pattern-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .priority-high {
            background: #fee2e2;
            color: #dc2626;
        }
        .priority-medium {
            background: #fef3c7;
            color: #f59e0b;
        }
        .priority-low {
            background: #dbeafe;
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="pattern-guide-container">
        <!-- 헤더 -->
        <div class="guide-header">
            <h1>📚 수학인지관성 도감</h1>
            <p class="subtitle">60개의 수학 학습 패턴을 정복하세요</p>
        </div>

        <!-- 진행 상황 -->
        <div class="progress-overview">
            <div class="progress-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $collected_count; ?></span>
                    <span class="stat-label">수집한 패턴</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">60</span>
                    <span class="stat-label">전체 패턴</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $progress_percent; ?>%</span>
                    <span class="stat-label">완성도</span>
                </div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar"></div>
            </div>
        </div>

        <!-- 카테고리별 패턴 표시 -->
        <?php foreach ($categories as $category): ?>
        <div class="category-section">
            <div class="category-header">
                <h2><?php echo htmlspecialchars($category->category_name); ?></h2>
            </div>
            <div class="patterns-grid">
                <?php foreach ($patterns as $pattern): ?>
                    <?php if ($pattern->category_id == $category->id): ?>
                        <?php 
                        $is_collected = isset($progress_map[$pattern->id]) && $progress_map[$pattern->id]->is_collected;
                        $mastery_level = isset($progress_map[$pattern->id]) ? $progress_map[$pattern->id]->mastery_level : 0;
                        ?>
                        <div class="pattern-card <?php echo $is_collected ? 'collected' : ''; ?>" onclick="showPatternDetail(<?php echo $pattern->id; ?>)">
                            <div class="pattern-card-header">
                                <span class="pattern-number">#<?php echo str_pad($pattern->id, 2, '0', STR_PAD_LEFT); ?></span>
                                <span class="pattern-icon"><?php echo htmlspecialchars($pattern->icon); ?></span>
                            </div>
                            <div class="pattern-name"><?php echo htmlspecialchars($pattern->name); ?></div>
                            <div class="pattern-desc"><?php echo htmlspecialchars($pattern->description); ?></div>
                            <div class="pattern-footer">
                                <span class="priority-badge priority-<?php echo $pattern->priority; ?>">
                                    우선순위: <?php echo $pattern->priority == 'high' ? '높음' : ($pattern->priority == 'medium' ? '중간' : '낮음'); ?>
                                </span>
                                <?php if ($is_collected): ?>
                                    <span style="color: #10b981;">✅ 수집됨</span>
                                <?php else: ?>
                                    <span style="color: #999;">🔒 미수집</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 모달 HTML -->
    <div id="pattern-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
        <div id="modal-inner" class="modal-content" style="position: relative; background: white; margin: 50px auto; padding: 0; max-width: 800px; max-height: 90vh; overflow-y: auto; border-radius: 15px; box-shadow: 0 5px 30px rgba(0,0,0,0.3);">
            <div id="pattern-modal-content">
                <!-- 동적으로 내용이 들어갈 부분 -->
            </div>
        </div>
    </div>

    <script>
    // 패턴 데이터 (PHP에서 JavaScript로 전달)
    const patternsData = <?php 
        $patterns_json = [];
        foreach ($patterns as $p) {
            $patterns_json[$p->id] = [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'icon' => $p->icon,
                'priority' => $p->priority,
                'audio_time' => $p->audio_time,
                'category_name' => $p->category_name,
                'action' => $p->action,
                'check_method' => $p->check_method,
                'audio_script' => $p->audio_script,
                'teacher_dialog' => $p->teacher_dialog
            ];
        }
        echo json_encode($patterns_json, JSON_UNESCAPED_UNICODE);
    ?>;

    function showPatternDetail(patternId) {
        const pattern = patternsData[patternId];
        if (!pattern) return;
        
        const modal = document.getElementById('pattern-modal');
        const content = document.getElementById('pattern-modal-content');
        
        // 모달 내용 생성
        content.innerHTML = `
            <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 15px 15px 0 0;">
                <button onclick="closeModal()" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; font-size: 24px; width: 40px; height: 40px; border-radius: 50%; cursor: pointer;">&times;</button>
                <div style="text-align: center;">
                    <div style="font-size: 60px; margin-bottom: 10px;">${pattern.icon}</div>
                    <h2 style="margin: 0; font-size: 28px;">${pattern.name}</h2>
                    <p style="margin-top: 10px; opacity: 0.9;">${pattern.category_name}</p>
                </div>
            </div>
            
            <div style="padding: 30px;">
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #667eea; margin-bottom: 15px;">📋 패턴 설명</h3>
                    <p style="line-height: 1.6; color: #555;">${pattern.description}</p>
                    <div style="margin-top: 15px; display: inline-block; padding: 8px 16px; background: ${getPriorityColor(pattern.priority)}; color: white; border-radius: 20px; font-size: 14px;">
                        우선순위: ${pattern.priority === 'high' ? '높음' : pattern.priority === 'medium' ? '중간' : '낮음'}
                    </div>
                </div>
                
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #667eea; margin-bottom: 15px;">💡 해결 방법</h3>
                    <div style="background: #f0f4ff; padding: 20px; border-radius: 10px; border-left: 4px solid #667eea;">
                        <p style="line-height: 1.6; color: #333;">${pattern.action}</p>
                    </div>
                </div>
                
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #667eea; margin-bottom: 15px;">✅ 확인 방법</h3>
                    <p style="line-height: 1.6; color: #555;">${pattern.check_method}</p>
                </div>
                
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #667eea; margin-bottom: 15px;">🎧 음성 가이드</h3>
                    <div style="background: #f9fafb; padding: 20px; border-radius: 10px;">
                        <p style="line-height: 1.6; color: #555; font-style: italic;">"${pattern.audio_script}"</p>
                        <p style="margin-top: 10px; color: #999;">재생 시간: ${pattern.audio_time}</p>
                        <button id="play-audio-btn-${patternId}" onclick="playPatternAudio(${patternId})" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; margin-top: 15px; font-size: 16px; transition: background-color 0.2s ease;">
                            🔊 음성 재생
                        </button>
                        <div id="audio-status-${patternId}" style="margin-top: 10px; font-size: 14px; color: #666;"></div>
                        <audio id="pattern-audio-${patternId}" style="display: none;">
                            <source src="" type="audio/wav">
                            브라우저가 오디오 재생을 지원하지 않습니다.
                        </audio>
                    </div>
                </div>
                
                <?php if ($role == 'teacher'): ?>
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #667eea; margin-bottom: 15px;">👩‍🏫 교사 대화 가이드</h3>
                    <div style="background: #fef3c7; padding: 20px; border-radius: 10px; border-left: 4px solid #f59e0b;">
                        <p style="line-height: 1.6; color: #333;">${pattern.teacher_dialog}</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button onclick="closeModal()" style="padding: 12px 30px; background: #e5e7eb; color: #333; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; margin-right: 10px;">닫기</button>
                    <button onclick="practicePattern(${patternId})" style="padding: 12px 30px; background: #10b981; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">연습하기</button>
                </div>
            </div>
        `;
        
        // 모달 표시
        modal.style.display = 'block';
        
        // 스크롤 방지
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        const modal = document.getElementById('pattern-modal');
        modal.style.display = 'none';
        
        // 스크롤 복원
        document.body.style.overflow = '';
    }
    
    function practicePattern(patternId) {
        alert('패턴 #' + patternId + ' 연습을 시작합니다!');
        // 실제로는 연습 페이지로 이동하거나 연습 모드 시작
    }
    
    // 음성 파일 재생 함수
    function playPatternAudio(patternId) {
        const pattern = patternsData[patternId];
        if (!pattern) return;
        
        const audio = document.getElementById(`pattern-audio-${patternId}`);
        const btn = document.getElementById(`play-audio-btn-${patternId}`);
        
        if (!audio) return;
        
        // 음성 파일 URL 설정 - 단순히 번호.wav 형식
        const baseUrl = "https://mathking.kr/Contents/personas/인지관성 유형분석/";
        const fileName = `${patternId}.wav`;
        const audioUrl = baseUrl + fileName;
        
        console.log('음성 파일 URL:', audioUrl);
        
        // 오디오 소스 설정
        audio.src = audioUrl;
        
        const statusDiv = document.getElementById(`audio-status-${patternId}`);
        
        // 재생 상태 확인
        if (audio.paused) {
            // 로딩 상태 표시
            statusDiv.innerHTML = '🔄 음성 파일을 불러오는 중...';
            
            // 재생
            audio.play()
                .then(() => {
                    btn.innerHTML = '⏸️ 일시정지';
                    btn.style.background = '#9333ea';
                    statusDiv.innerHTML = '▶️ 재생 중...';
                })
                .catch((error) => {
                    console.error('음성 재생 오류:', error);
                    statusDiv.innerHTML = '❌ 음성 파일을 재생할 수 없습니다.';
                    btn.innerHTML = '🔊 음성 재생';
                    btn.style.background = '#667eea';
                });
        } else {
            // 일시정지
            audio.pause();
            btn.innerHTML = '🔊 음성 재생';
            btn.style.background = '#667eea';
            statusDiv.innerHTML = '⏸️ 일시정지됨';
        }
        
        // 재생 종료 이벤트
        audio.addEventListener('ended', function() {
            btn.innerHTML = '🔊 음성 재생';
            btn.style.background = '#667eea';
            statusDiv.innerHTML = '✅ 재생 완료';
        }, { once: true });
    }
    
    
    function getPriorityColor(priority) {
        switch(priority) {
            case 'high': return '#dc2626';
            case 'medium': return '#f59e0b';
            case 'low': return '#3b82f6';
            default: return '#666';
        }
    }
    
    // 모달 외부 클릭 시 닫기
    document.getElementById('pattern-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    </script>
</body>
</html>