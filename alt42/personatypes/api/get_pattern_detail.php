<?php
/**
 * 패턴 상세 정보 API
 */

require_once(__DIR__ . '/../../../../../../../config.php');

// 로그인 확인
require_login();

// 헤더 설정
header('Content-Type: application/json; charset=utf-8');

$pattern_id = required_param('pattern_id', PARAM_INT);
$user_id = $USER->id;

try {
    // 패턴 정보 가져오기
    $pattern = $DB->get_record_sql(
        "SELECT mp.*, ps.action, ps.check_method, ps.audio_script, ps.teacher_dialog,
                pc.category_name,
                COALESCE(upp.is_collected, 0) as is_collected,
                COALESCE(upp.mastery_level, 0) as mastery_level,
                COALESCE(upp.practice_count, 0) as practice_count,
                upp.last_practice_at,
                upp.notes
         FROM {alt42i_math_patterns} mp
         JOIN {alt42i_pattern_solutions} ps ON mp.id = ps.pattern_id
         JOIN {alt42i_pattern_categories} pc ON mp.category_id = pc.id
         LEFT JOIN {alt42i_user_pattern_progress} upp 
              ON mp.id = upp.pattern_id AND upp.user_id = ?
         WHERE mp.id = ?",
        [$user_id, $pattern_id]
    );
    
    if (!$pattern) {
        throw new Exception('패턴을 찾을 수 없습니다.');
    }
    
    // HTML 생성
    $html = '<div class="pattern-detail">';
    
    // 헤더
    $html .= '<div class="detail-header">';
    $html .= '<span class="detail-icon">' . $pattern->icon . '</span>';
    $html .= '<div class="detail-title">';
    $html .= '<h2>' . s($pattern->name) . '</h2>';
    $html .= '<p class="detail-category">' . s($pattern->category_name) . '</p>';
    $html .= '</div>';
    $html .= '</div>';
    
    // 설명
    $html .= '<div class="detail-section">';
    $html .= '<h3>패턴 설명</h3>';
    $html .= '<p>' . s($pattern->description) . '</p>';
    $html .= '</div>';
    
    // 실천 방법
    $html .= '<div class="detail-section">';
    $html .= '<h3>💡 실천 방법</h3>';
    $html .= '<p class="action-text">' . s($pattern->action) . '</p>';
    $html .= '</div>';
    
    // 확인 방법
    $html .= '<div class="detail-section">';
    $html .= '<h3>✅ 확인 방법</h3>';
    $html .= '<p>' . s($pattern->check_method) . '</p>';
    $html .= '</div>';
    
    // 음성 스크립트
    $html .= '<div class="detail-section">';
    $html .= '<h3>🎧 음성 가이드</h3>';
    $html .= '<p class="audio-script">' . s($pattern->audio_script) . '</p>';
    $html .= '<p class="audio-time">재생 시간: ' . s($pattern->audio_time) . '</p>';
    $html .= '</div>';
    
    // 교사 대화
    $html .= '<div class="detail-section">';
    $html .= '<h3>👩‍🏫 교사와의 대화</h3>';
    $html .= '<p class="teacher-dialog">' . s($pattern->teacher_dialog) . '</p>';
    $html .= '</div>';
    
    // 진행 상황
    if ($pattern->is_collected) {
        $html .= '<div class="detail-section progress-section">';
        $html .= '<h3>📊 나의 진행 상황</h3>';
        $html .= '<div class="progress-info">';
        $html .= '<div class="progress-item">';
        $html .= '<span class="progress-label">숙달도:</span>';
        $html .= '<div class="mastery-bar">';
        $html .= '<div class="mastery-fill" style="width: ' . $pattern->mastery_level . '%"></div>';
        $html .= '</div>';
        $html .= '<span class="progress-value">' . $pattern->mastery_level . '%</span>';
        $html .= '</div>';
        $html .= '<div class="progress-item">';
        $html .= '<span class="progress-label">연습 횟수:</span>';
        $html .= '<span class="progress-value">' . $pattern->practice_count . '회</span>';
        $html .= '</div>';
        if ($pattern->last_practice_at) {
            $html .= '<div class="progress-item">';
            $html .= '<span class="progress-label">마지막 연습:</span>';
            $html .= '<span class="progress-value">' . userdate(strtotime($pattern->last_practice_at)) . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // 메모
        if ($pattern->notes) {
            $html .= '<div class="user-notes">';
            $html .= '<h4>📝 나의 메모</h4>';
            $html .= '<p>' . s($pattern->notes) . '</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="not-collected-notice">';
        $html .= '<p>🔒 아직 수집되지 않은 패턴입니다.</p>';
        $html .= '<p>Shining Stars 여정을 진행하면서 이 패턴을 발견해보세요!</p>';
        $html .= '</div>';
    }
    
    // 액션 버튼
    $html .= '<div class="detail-actions">';
    if ($pattern->is_collected) {
        $html .= '<button class="practice-btn" onclick="startPractice(' . $pattern_id . ')">연습하기</button>';
        $html .= '<button class="note-btn" onclick="editNote(' . $pattern_id . ')">메모 수정</button>';
    }
    $html .= '<button class="close-btn" onclick="closePatternDetail()">닫기</button>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'pattern' => [
            'id' => $pattern->id,
            'name' => $pattern->name,
            'is_collected' => (bool)$pattern->is_collected,
            'mastery_level' => (int)$pattern->mastery_level
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}