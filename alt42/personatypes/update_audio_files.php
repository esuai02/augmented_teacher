<?php
/**
 * 음성 파일 정보를 DB에 업데이트하는 스크립트
 * 60개 패턴에 대한 음성 파일 URL 등록
 */

// Moodle 설정 포함
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;
require_login(); 

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// 페이지 설정
$PAGE->set_url('/shiningstars/update_audio_files.php');
$PAGE->set_context($context);
$PAGE->set_title('음성 파일 업데이트');

echo $OUTPUT->header();
?>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <h2>🎵 수학 인지관성 음성 파일 업데이트</h2>
    
    <?php
    $action = optional_param('action', '', PARAM_ALPHA);
    
    if ($action === 'update') {
        echo '<div style="background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;">';
        echo '<h3>업데이트 진행 중...</h3>';
        
        $transaction = $DB->start_delegated_transaction();
        
        try {
            // 모든 패턴 가져오기
            $patterns = $DB->get_records('alt42i_math_patterns', null, 'pattern_id ASC');
            $count = 0;
            
            foreach ($patterns as $pattern) {
                // 음성 파일 URL 생성
                $file_number = str_pad($pattern->pattern_id, 2, '0', STR_PAD_LEFT);
                $file_url = "http://mathking.kr/Contents/personas/mathlearning/thinkinginertia{$file_number}.mp3";
                $file_name = "thinkinginertia{$file_number}.mp3";
                
                // 기존 레코드 확인
                $existing = $DB->get_record('alt42i_pattern_audio_files', [
                    'pattern_id' => $pattern->id,
                    'audio_type' => 'guide'
                ]);
                
                if ($existing) {
                    // 업데이트
                    $existing->file_path = $file_url;
                    $existing->file_name = $file_name;
                    $existing->timemodified = time();
                    $DB->update_record('alt42i_pattern_audio_files', $existing);
                } else {
                    // 새로 삽입
                    $audio = new stdClass();
                    $audio->pattern_id = $pattern->id;
                    $audio->audio_type = 'guide';
                    $audio->file_path = $file_url;
                    $audio->file_name = $file_name;
                    $audio->duration_seconds = 180; // 기본 3분
                    $audio->language = 'ko';
                    $audio->transcript = $DB->get_field('alt42i_pattern_solutions', 'audio_script', ['pattern_id' => $pattern->id]);
                    $audio->is_active = 1;
                    $audio->timecreated = time();
                    $audio->timemodified = time();
                    
                    $DB->insert_record('alt42i_pattern_audio_files', $audio);
                }
                
                $count++;
                echo "<p>✅ {$pattern->pattern_name} - {$file_name} 연결 완료</p>";
            }
            
            $transaction->allow_commit();
            
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
            echo "<strong>✨ 완료!</strong> 총 {$count}개의 음성 파일이 연결되었습니다.";
            echo "</div>";
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px;">❌ 오류: ' . $e->getMessage() . '</div>';
        }
        
        echo '</div>';
        
    } else {
        // 현재 상태 표시
        $total_patterns = $DB->count_records('alt42i_math_patterns');
        $total_audio = $DB->count_records('alt42i_pattern_audio_files');
        ?>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>📊 현재 상태</h3>
            <p>전체 패턴: <strong><?php echo $total_patterns; ?>개</strong></p>
            <p>등록된 음성 파일: <strong><?php echo $total_audio; ?>개</strong></p>
            
            <h3>🔗 음성 파일 URL 구조</h3>
            <p><code>http://mathking.kr/Contents/personas/mathlearning/thinkinginertia01.mp3</code></p>
            <p>~ </p>
            <p><code>http://mathking.kr/Contents/personas/mathlearning/thinkinginertia60.mp3</code></p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?action=update" class="btn btn-primary" style="padding: 10px 30px; font-size: 1.1em;">
                🎵 음성 파일 연결 시작
            </a>
        </div>
        
        <?php
    }
    ?>
</div>

<?php
echo $OUTPUT->footer();
?>